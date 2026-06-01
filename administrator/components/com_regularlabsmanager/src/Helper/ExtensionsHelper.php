<?php
/**
 * @package         Regular Labs Extension Manager
 * @version         9.2.5
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            https://regularlabs.com
 * @copyright       Copyright © 2025 Regular Labs All Rights Reserved
 * @license         GNU General Public License version 2 or later
 */

namespace RegularLabs\Component\RegularLabsExtensionsManager\Administrator\Helper;

use Joomla\CMS\Installer\Installer;
use Joomla\CMS\Language\Text as JText;
use RegularLabs\Library\Cache as RL_Cache;
use RegularLabs\Library\DB as RL_DB;
use RegularLabs\Library\DownloadKey as RL_DownloadKey;
use RegularLabs\Library\Http as RL_Http;
use RegularLabs\Library\Input as RL_Input;
use RegularLabs\Library\Language as RL_Language;
use RegularLabs\Library\Parameters as RL_Parameters;
use RegularLabs\Library\RegEx as RL_RegEx;

defined('_JEXEC') or die;

class ExtensionsHelper
{
    public static function get($refresh = false)
    {
        $extensions = self::getExternalData($refresh);

        if (empty($extensions))
        {
            return [];
        }

        $items = (object) [
            'extensionmanager'  => [],
            'updates_available' => [],
            'installed'         => [],
            'not_installed'     => [],
            'no_access'         => [],
            'broken'            => [],
        ];

        foreach ($extensions as $alias => &$extension)
        {
            if ( ! $extension->can_download)
            {
                unset($extensions[$alias]);
                continue;
            }

            self::initItem($extension);

            if ($extension->state === 'not_installed')
            {
                continue;
            }

            $has_update = self::hasUpdate($extension);

            if ( ! $extension->has_access
                && ($has_update || $extension->state === 'broken')
            )
            {
                $extension->state = 'no_access';
                continue;
            }

            if ($has_update)
            {
                $extension->state = 'updates_available';
                continue;
            }

            if ($extension->state == 'broken')
            {
                continue;
            }

            $extension->state = 'installed';
        }

        foreach ($extensions as $alias => &$extension)
        {
            if (
                $extension->alias === 'extensionmanager'
                && in_array($extension->state, ['updates_available', 'broken'])
            )
            {
                $items->extensionmanager[$alias] = $extension;
                continue;
            }

            $items->{$extension->state}[$alias] = $extension;
        }

        return $items;
    }

    public static function getAvailable($refresh = false)
    {
        $extensions = self::get($refresh);

        return $extensions->not_installed;
    }

    public static function getBroken($refresh = false)
    {
        $extensions = self::get($refresh);

        return $extensions->broken;
    }

    public static function getByAlias($alias, $refresh = false)
    {
        $extensions = self::getExternalData($refresh);

        return $extensions[$alias] ?? false;
    }

    public static function getFromUrl($refresh = false)
    {
        $extensions = self::get($refresh);
        $selection  = RL_Input::getAsArray('extensions');

        if (empty($selection))
        {
            $extension = RL_Input::getCmd('extension');
            $selection = $extension ? [$extension] : [];
        }

        if (empty($selection))
        {
            return [];
        }

        $list = [];

        foreach ($extensions as $group)
        {
            foreach ($group as $extension)
            {
                if ( ! in_array($extension->alias, $selection))
                {
                    continue;
                }

                $list[] = $extension;
            }
        }

        return $list;
    }

    public static function getInstalled($refresh = false)
    {
        $extensions = self::get($refresh);

        return $extensions->installed;
    }

    public static function getUpdates($refresh = false)
    {
        $extensions = self::get($refresh);

        return $extensions->updates_available;
    }

    private static function getAvailableVersion($extension)
    {
        if (is_object($extension->version))
        {
            return $extension->version;
        }

        return (object) [
            'version' => $extension->version,
            'stable'  => self::getStableVersion($extension),
            'is_pro'  => $extension->pro && $extension->has_access,
        ];
    }

    private static function getCurrentVersion($extension)
    {
        if (isset($extension->current_version))
        {
            return $extension->current_version;
        }

        $version = 0;

        foreach ($extension->types as $type)
        {
            if (empty($type->version))
            {
                continue;
            }

            $version = $type->version;
            break;
        }

        if ( ! $version)
        {
            return (object) [
                'version' => '',
                'is_pro'  => false,
            ];
        }

        return (object) [
            'version' => str_ireplace(['PRO', 'FREE'], '', $version),
            'is_pro'  => stripos($version, 'PRO') !== false,
        ];
    }

    /**
     * @param false $refresh
     *
     * @return array
     */
    private static function getExternalData($refresh = false)
    {
        $cache = new RL_Cache('ExtensionsManager.getExternalData');
        $cache->useFiles(5);

        if ( ! $refresh && $cache->exists())
        {
            return $cache->get();
        }

        $config = RL_Parameters::getComponent('regularlabsmanager');
        $url    = 'https://download.regularlabs.com/extensions.json?j=4';

        if ($config->updatesource == 'dev')
        {
            $url .= '&dev=1';
        }

        $key = RL_DownloadKey::get(false);

        if ($key)
        {
            $url .= '&k=' . $key;
        }

        $timeout = ((int) $config->timeout ?? 5) ?: 5;
        $content = RL_Http::getFromUrl($url, $timeout);

        if ( ! $content)
        {
            return [];
        }

        $content = (array) json_decode($content);

        return $cache->set($content);
    }

    private static function getInstalledState($extension)
    {
        $installed = false;

        foreach ($extension->types as $type)
        {
            if (empty($type->version))
            {
                continue;
            }

            $installed = true;
            break;
        }

        foreach ($extension->types as $type)
        {
            if (empty($type->version) && $installed)
            {
                return 'broken';
            }
        }

        return $installed ? 'installed' : 'not_installed';
    }

    private static function getJoomlaVersion($path)
    {
        $cache = new RL_Cache;

        if ($cache->exists())
        {
            return $cache->get();
        }

        if ( ! $path)
        {
            return $cache->set(false);
        }

        if ( ! file_exists($path))
        {
            return $cache->set(false);
        }

        $xml = simplexml_load_file($path);

        if ( ! $xml)
        {
            return $cache->set(false);
        }

        return $cache->set((int) ($xml->attributes()['version'] ?? 0));
    }

    private static function getStableVersion($extension)
    {
        if ( ! str_contains($extension->version, 'dev'))
        {
            return $extension->version;
        }

        RL_RegEx::match('>[0-9]+-[a-zA-Z]+-[0-9]+ : v([0-9\\.]+)<br>', $extension->changelog, $stable_version);

        if (empty($stable_version))
        {
            return strtok($extension->version, '-');
        }

        return $stable_version[1];
    }

    private static function getTypeData($type, $extension)
    {
        switch ($type)
        {
            case 'package':
            case 'pkg':
                return (object) [
                    'type'     => 'pkg',
                    'xml_path' => JPATH_ADMINISTRATOR . '/manifests/packages/pkg_' . $extension->extname . '.xml',
                ];
            case 'component':
            case 'com':
                return (object) [
                    'type'     => 'com',
                    'text'     => JText::_('RL_COM'),
                    'letter'   => 'C',
                    'class'    => 'success',
                    'url'      => self::getURL($type, $extension->extname),
                    'xml_path' => JPATH_ADMINISTRATOR . '/components/com_' . $extension->extname . '/' . $extension->extname . '.xml',
                ];
            case 'module':
            case 'mod':
                return (object) [
                    'type'     => 'mod',
                    'text'     => JText::_('RL_MOD'),
                    'letter'   => 'M',
                    'class'    => 'danger',
                    'url'      => self::getURL($type, $extension->extname),
                    'xml_path' => JPATH_ADMINISTRATOR . '/modules/mod_' . $extension->extname . '/mod_' . $extension->extname . '.xml',
                ];
            case 'library':
            case 'lib':
                return (object) [
                    'type'     => 'lib',
                    'text'     => JText::_('RL_LIB'),
                    'letter'   => 'L',
                    'class'    => 'warning text-black',
                    'url'      => '',
                    'xml_path' => JPATH_LIBRARIES . '/' . $extension->extname . '/' . $extension->extname . '.xml',
                ];
            case 'plg_actionlog':
            case 'plg_editors-xtd':
            case 'plg_fields':
            case 'plg_system':
            default:
                $plugin_type   = substr($type, 4);
                $plugin_letter = strtoupper(substr($plugin_type, 0, 1));

                if ($plugin_type === 'editors-xtd')
                {
                    $plugin_letter = 'B';
                }

                return (object) [
                    'type'     => $type,
                    'text'     => JText::_('RL_' . strtoupper($type)),
                    'letter'   => 'P<small>' . $plugin_letter . '</small>',
                    'class'    => 'info',
                    'url'      => self::getURL($type, $extension->extname),
                    'xml_path' => JPATH_PLUGINS . '/' . $plugin_type . '/' . $extension->extname . '/' . $extension->extname . '.xml',
                ];
        }
    }

    /**
     * Get the extension url
     */
    private static function getURL($type, $element, $client_id = 1)
    {
        [$type, $folder] = explode('_', $type . '_');

        switch ($type)
        {
            case 'com':
                return 'index.php?option=com_' . $element;

            case 'mod':
                return 'index.php?option=com_modules&client_id=' . $client_id
                    . '&filter[module]=mod_' . $element . '&filter[search]=';

            case 'plg':
                $query = RL_DB::getQuery()
                    ->select('name')
                    ->from('#__extensions')
                    ->where(RL_DB::is('type', 'plugin'))
                    ->where(RL_DB::is('folder', $folder))
                    ->where(RL_DB::is('element', $element));
                $name  = RL_DB::get()->setQuery($query)->loadResult();

                RL_Language::load('plg_' . $folder . '_' . $element . '.sys', '', true);
                $name = JText::_($name);
                $name = RL_RegEx::replace('^(.*?)\?.*$', '\1', $name);

                return 'index.php?option=com_plugins&filter[folder]=&filter[search]=' . $name;

            default:
                return '';
        }
    }

    private static function getXMLVersion($path)
    {
        $cache = new RL_Cache;

        if ($cache->exists())
        {
            return $cache->get();
        }

        if ( ! $path)
        {
            return $cache->set(false);
        }

        $xml = Installer::parseXMLInstallFile($path);

        return $cache->set($xml['version'] ?? '');
    }

    private static function hasAccess($extension)
    {
        if ( ! $extension->has_pro)
        {
            return true;
        }

        if ( ! $extension->current_version->is_pro)
        {
            return true;
        }

        return $extension->pro;
    }

    private static function hasUpdate($extension)
    {
        if ($extension->joomla_version !== 4)
        {
            return true;
        }

        $current = self::getCurrentVersion($extension);

        if ( ! $current->is_pro && $extension->pro)
        {
            return true;
        }

        return version_compare($current->version, $extension->version->version, '<');
    }

    private static function initItem(&$extension)
    {
        self::setTypeData($extension);

        $extension->current_version = self::getCurrentVersion($extension);
        $extension->has_access      = self::hasAccess($extension);
        $extension->version         = self::getAvailableVersion($extension);
        $extension->state           = self::getInstalledState($extension);

        if ($extension->alias == 'extensionmanager')
        {
            $extension->name = 'Regular Labs Extension Manager';
        }

    }

    private static function setTypeData(&$extension)
    {
        $extension->joomla_version = 4;

        $pkg_file = JPATH_ADMINISTRATOR . '/manifests/packages/pkg_' . $extension->extname . '.xml';

        if (file_exists($pkg_file))
        {
            array_unshift($extension->types, 'pkg');
        }

        foreach ($extension->types as &$type)
        {
            if (is_object($type))
            {
                continue;
            }

            $type = self::getTypeData($type, $extension);

            $type->version      = self::getXMLVersion($type->xml_path);
            $xml_joomla_version = self::getJoomlaVersion($type->xml_path);

            if ($xml_joomla_version)
            {
                $extension->joomla_version = min($extension->joomla_version, $xml_joomla_version);
            }
        }
    }
}
