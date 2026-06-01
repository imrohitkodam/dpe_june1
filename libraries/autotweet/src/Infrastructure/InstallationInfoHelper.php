<?php

/*
 * @package     Perfect Publisher
 *
 * @author      Extly, CB. <team@extly.com>
 * @copyright   Copyright (c)2012-2022 Extly, CB. All rights reserved.
 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 *
 * @see         https://www.extly.com
 */

defined('_JEXEC') || exit;

/**
 * InstallationInfoHelper - Helper to get some infos about installed AutoTweet extensions.
 *
 * @since       1.0
 */
class InstallationInfoHelper
{
    public const EXT_NOTINSTALLED = 'COM_AUTOTWEET_STATE_PLUGIN_NOTINSTALLED';
    public const EXT_DISABLED = 'COM_AUTOTWEET_STATE_PLUGIN_DISABLED';
    public const EXT_ENABLED = 'COM_AUTOTWEET_STATE_PLUGIN_ENABLED';
    public const EXT_UNKNOWN = 'COM_AUTOTWEET_VIEW_ABOUT_VERSIONINFO_UNKNOWN';

    public const SYSINFO_PHP = 1;
    public const SYSINFO_MYSQL = 9;
    public const SYSINFO_UTF8MB4 = 11;
    public const SYSINFO_JOOMLA = 2;
    public const SYSINFO_CURL = 3;
    public const SYSINFO_SSL = 4;
    public const SYSINFO_JSON = 5;
    public const SYSINFO_TIMESTAMP = 6;
    public const SYSINFO_HMAC = 8;
    public const SYSINFO_TIDY = 10;

    public const SYSINFO_OK = '<span class="xt-badge xt-badge-success"><i class="xticon fas fa-check"></i></span>';
    public const SYSINFO_FAIL = '<span class="xt-badge xt-badge-error"><i class="xticon fas fa-times"></i></span>';

    public const SERVER_INI_FILE = 'perfect-publisher.ini';
    public const SERVER_INI_PATH = 'http://cdn.extly.com/download/';
    public const COMP_INSTALL_FILE = 'autotweet.xml';
    public const KEY_COMP = 'component';
    public const EXTLY_SOURCE = 'Extly Tech';

    // Seconds
    public const CXN_TIMEOUT = 3;

    // Seconds
    public const EXEC_TIMEOUT = 3;

    private static $_compinfo = null;

    private static $_pluginfo = null;

    private static $_thirdparty = null;

    /**
     * no public access (static class).
     */
    private function __construct()
    {
        // Static class
    }

    /**
     * getComponentInfo.
     *
     * @return array
     */
    public static function getComponentInfo()
    {
        self::loadINI();

        return self::$_compinfo;
    }

    /**
     * getPluginInfo.
     *
     * @return array
     */
    public static function getPluginInfo()
    {
        self::loadINI();

        return self::$_pluginfo;
    }

    /**
     * getThirdpartyInfo.
     *
     * @return array
     */
    public static function getThirdpartyInfo()
    {
        self::loadINI();

        return self::$_thirdparty;
    }

    /**
     * getSystemInfo.
     *
     * @return array
     */
    public static function getSystemInfo()
    {
        try {
            $sysinfo = [];

            // PHP Version
            // Check for PHP4
            if (defined('PHP_VERSION')) {
                $version = \PHP_VERSION;
            } elseif (function_exists('phpversion')) {
                $version = \PHP_VERSION;
            } else {
                // No version info. I'll lie and hope for the best.
                $version = '5.0.0';
            }

            $db = \Joomla\CMS\Factory::getDBO();
            $sysinfo[self::SYSINFO_PHP] = version_compare($version, '7.4.0', '>=');

            $mysql_version = $db->getVersion();

            if ((preg_match('/([0-9]+\\.[0-9]+\\.[0-9]+)/', $mysql_version, $matches)) && (count($matches))) {
                $mysql_version = $matches[0];
            }

            $sysinfo[self::SYSINFO_MYSQL] = version_compare($mysql_version, '5.7.0', '>=');
            $sysinfo[self::SYSINFO_UTF8MB4] = ETable::hasUTF8mb4Support();
            $sysinfo[self::SYSINFO_CURL] = function_exists('curl_init');
            $sysinfo[self::SYSINFO_SSL] = function_exists('openssl_get_publickey');
            $sysinfo[self::SYSINFO_JSON] = function_exists('json_encode');
            $sysinfo[self::SYSINFO_HMAC] = function_exists('hash_hmac');
            $sysinfo[self::SYSINFO_TIDY] = function_exists('tidy_parse_string');

            $input = new \Joomla\CMS\Input\Input($_REQUEST);
            $view = $input->get('view');

            if ('infos' === $view) {
                $sysinfo[self::SYSINFO_TIMESTAMP] = TwAppHelper::checkTimestamp();
            }

            // Detect UTF8mb4 not converted and execute conversion
            if (($sysinfo[self::SYSINFO_UTF8MB4]) && ('utf8mb4_unicode_ci' !== ETable::getTableCollation('#__autotweet_requests'))) {
                $file = JPATH_ADMINISTRATOR.'/components/com_autotweet/sql/updates/convert-utf8mb4.sql';

                if (ETable::convertUTF8mb4($file)) {
                    \Joomla\CMS\Factory::getApplication()->enqueueMessage(JText::_('COM_AUTOTWEET_VIEW_ABOUT_SYSINFO_UTF8MB4_CONV'), 'success');
                }
            }

            return $sysinfo;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * loadINI.
     *
     * @return bool
     */
    protected static function loadINI()
    {
        if ((self::$_compinfo) && (self::$_pluginfo)) {
            return true;
        }

        self::$_compinfo = [];
        self::$_pluginfo = [];
        self::$_thirdparty = [];

        // Get component parameter
        $version_check = EParameter::getComponentParam(CAUTOTWEETNG, 'version_check', 1);

        $remoteFile = self::SERVER_INI_PATH.self::SERVER_INI_FILE;
        $localFile = JPATH_AUTOTWEET.'/'.self::SERVER_INI_FILE;
        $file = $localFile;

        if ($version_check) {
            try {
                $ch = curl_init($remoteFile);

                curl_setopt($ch, \CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, \CURLOPT_CONNECTTIMEOUT, self::CXN_TIMEOUT);
                curl_setopt($ch, \CURLOPT_TIMEOUT, self::EXEC_TIMEOUT);

                $data = curl_exec($ch);
                curl_close($ch);
                file_put_contents($localFile, $data);
            } catch (Exception $e) {
                $msg = $e->getMessage();
                $logger->log(\Joomla\CMS\Log\Log::ERROR, 'PerfectPublisher - '.$msg);
            }
        }

        $registry = new JRegistry();

        if (!$registry->loadFile(
            $file,
            'INI',
            [
                'processSections' => 'true',
            ]
        )) {
            $logger->log(\Joomla\CMS\Log\Log::ERROR, 'PerfectPublisher - error reading INI file '.$file);

            return false;
        }

        // Init logging
        $logger = AutotweetLogger::getInstance();

        $db = \Joomla\CMS\Factory::getDBO();

        // Get component info and remove from array
        $data = \JInstaller::parseXMLInstallFile(JPATH_COMPONENT_ADMINISTRATOR.\DIRECTORY_SEPARATOR.self::COMP_INSTALL_FILE);
        self::$_compinfo = [
            'id' => $registry->get('component.id'),
            'name' => $registry->get('component.name'),
            'server_version' => $registry->get('component.version'),
            'client_version' => $data['version'],
            'home' => $registry->get('component.home'),
            'faq' => $registry->get('component.faq'),
            'download' => $registry->get('component.download'),
            'support' => $registry->get('component.support'),
            'products' => $registry->get('component.products'),
            'twitter' => $registry->get('component.twitter'),
            'jed' => $registry->get('component.jed'),
            'message' => $registry->get('component.message'),
            'news' => $registry->get('component.news'),
        ];
        $extensions = TextUtil::listToArray($registry->get('component.extensions'));

        foreach ($extensions as $extension) {
            $state = self::EXT_NOTINSTALLED;
            $config = '';
            $client_version = '';
            $type = $registry->get($extension.'.type');
            $id = $registry->get($extension.'.id');
            $source = $registry->get($extension.'.source');

            if ('module' === $type) {
                $mod_filename = 'mod_'.$id;

                // Get the module id and set url for config
                $pluginsModel = XTF0FModel::getTmpInstance('Extensions', 'ExtlyModel');
                $pluginsModel->savestate(false)->setState('element', $mod_filename);
                $rows = $pluginsModel->getItemList();

                if (!empty($rows)) {
                    $row = $rows[0];

                    if ($row->client_id) {
                        $path = JPATH_ADMINISTRATOR.'/modules/'.$mod_filename.\DIRECTORY_SEPARATOR.$mod_filename.'.xml';
                    } else {
                        $path = JPATH_ROOT.'/modules/'.$mod_filename.\DIRECTORY_SEPARATOR.$mod_filename.'.xml';
                    }

                    $data = \JInstaller::parseXMLInstallFile($path);
                    $client_version = $data['version'];

                    if (self::_isEnabled($mod_filename)) {
                        $state = self::EXT_ENABLED;
                    } else {
                        $state = self::EXT_DISABLED;
                    }

                    // $config = 'index.php?option=com_modules&task=module.edit&id=' . $row->extension_id;
                }
            } else {
                // Get the plugin id and set url for config
                $pluginsModel = XTF0FModel::getTmpInstance('Plugins', 'AutoTweetModel');
                $pluginsModel->savestate(false)->set('element_id', $id);
                $rows = $pluginsModel->getItemList();

                if (!empty($rows)) {
                    $row = $rows[0];
                    $path = JPATH_PLUGINS.\DIRECTORY_SEPARATOR.$row->folder.\DIRECTORY_SEPARATOR.$row->element.\DIRECTORY_SEPARATOR.$row->element.'.xml';
                    $data = \JInstaller::parseXMLInstallFile($path);

                    $client_version = $data['version'];

                    if (\Joomla\CMS\Plugin\PluginHelper::isEnabled($row->folder, $row->element)) {
                        $state = self::EXT_ENABLED;
                    } else {
                        $state = self::EXT_DISABLED;
                    }

                    $config = 'index.php?option=com_plugins&task=plugin.edit&extension_id='.$row->id;
                }
            }

            $typeLabel = '<span class="xt-label xt-label-info">'.('module' === $type ? 'Module' : 'Plugin').'</span> ';

            if (self::EXTLY_SOURCE === $source) {
                self::$_pluginfo[] = [
                    'id' => $id,
                    'name' => $typeLabel.$registry->get($extension.'.name'),
                    'state' => $state,
                    'client_version' => $client_version,
                    'server_version' => $registry->get($extension.'.version'),
                    'message' => $registry->get($extension.'.message'),
                    'config' => $config,
                ];

                continue;
            }

            self::$_thirdparty[] = [
                'id' => $id,
                'name' => $typeLabel.$registry->get($extension.'.name'),
                'state' => $state,
                'client_version' => $client_version,
                'message' => $registry->get($extension.'.message'),
                'config' => $config,
                'source' => $source,
                'download' => $registry->get($extension.'.download'),
            ];
        }

        // Add installed plugins without entry in ini file to 3rd party list
        $pluginsModel = XTF0FModel::getTmpInstance('Plugins', 'AutoTweetModel');
        $pluginsModel->savestate(false);
        $plugins = $pluginsModel->getItemList();

        foreach ($plugins as $plugin) {
            $id = $plugin->element;
            $type = $plugin->folder;

            if (!self::in_array_recursive($id, self::$_pluginfo) && !self::in_array_recursive($id, self::$_thirdparty)) {
                $path = JPATH_PLUGINS.\DIRECTORY_SEPARATOR.$type.\DIRECTORY_SEPARATOR.$id.\DIRECTORY_SEPARATOR.$id.'.xml';
                $data = \JInstaller::parseXMLInstallFile($path);

                $client_version = $data['version'];

                if (\Joomla\CMS\Plugin\PluginHelper::isEnabled($type, $id)) {
                    $state = self::EXT_ENABLED;
                } else {
                    $state = self::EXT_DISABLED;
                }

                $config = 'index.php?option=com_plugins&task=plugin.edit&extension_id='.$plugin->id;

                if (!empty($data['authorUrl'])) {
                    $source = $data['authorUrl'];
                    $download = $data['authorUrl'];
                } else {
                    $source = self::EXT_UNKNOWN;
                    $download = '';
                }

                self::$_thirdparty[] = [
                    'id' => $id,
                    'name' => $plugin->name,
                    'state' => $state,
                    'client_version' => $client_version,
                    'message' => 'unknown extension plugin',
                    'config' => $config,
                    'source' => $source,
                    'download' => $download,
                ];
            }
        }

        return true;
    }

    /**
     * Helper to search an array recursive for a given value.
     *
     * @param string $needle   Param
     * @param string $haystack Param
     *
     * @return bool
     */
    private static function in_array_recursive($needle, $haystack)
    {
        $it = new RecursiveIteratorIterator(new RecursiveArrayIterator($haystack));

        foreach ($it as $element) {
            if ($element === $needle) {
                return true;
            }
        }

        return false;
    }

    /**
     * _isEnabled.
     *
     * @param string $module_name Param
     *
     * @return bool
     */
    private static function _isEnabled($module_name)
    {
        $db = \Joomla\CMS\Factory::getDbo();
        $query = $db->getQuery(true)
            ->select('m.id, m.title, m.module, m.position, m.content, m.showtitle, m.params, mm.menuid')
            ->from('#__modules AS m')
            ->join('LEFT', '#__modules_menu AS mm ON mm.moduleid = m.id')
            ->where('m.published = 1')
            ->join('LEFT', '#__extensions AS e ON e.element = m.module AND e.client_id = m.client_id')
            ->where('e.enabled = 1')
            ->where('m.module = '.$db->q($module_name));
        $db->setQuery($query);
        $module = $db->loadObject();

        if ($module) {
            return true;
        }

        return false;
    }
}
