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

namespace RegularLabs\Component\RegularLabsExtensionsManager\Administrator\Model;

use Joomla\CMS\Factory as JFactory;
use Joomla\CMS\Installer\Installer as JInstaller;
use Joomla\CMS\Installer\InstallerHelper as JInstallerHelper;
use Joomla\CMS\Language\Text as JText;
use Joomla\CMS\MVC\Model\ListModel as JListModel;
use Joomla\CMS\Plugin\PluginHelper as JPluginHelper;
use Joomla\CMS\Updater\Update as JUpdate;
use Joomla\CMS\Uri\Uri as JUri;
use RegularLabs\Component\RegularLabsExtensionsManager\Administrator\Helper\ExtensionsHelper;
use RegularLabs\Library\DB as RL_DB;
use RegularLabs\Library\Input as RL_Input;
use RegularLabs\Library\Language as RL_Language;
use RegularLabs\Library\Parameters as RL_Parameters;

defined('_JEXEC') or die;

class ProcessModel extends JListModel
{
    protected $config;

    /**
     * @var   string    The prefix to use with controller messages.
     */
    protected $text_prefix = 'RL';

    /**
     * @param array    An optional associative array of configuration settings.
     */
    public function __construct($config = [])
    {
        parent::__construct($config);

        $this->config = RL_Parameters::getComponent('regularlabsmanager');
    }

    public function getItems()
    {
        $view       = RL_Input::getCmd('view');
        $extensions = ExtensionsHelper::getFromUrl();

        if ( ! empty($extensions))
        {
            return $extensions;
        }

        return match ($view)
        {
            'reinstall' => ExtensionsHelper::getBroken(),
            'update'    => ExtensionsHelper::getUpdates(),
            default     => [],
        };
    }

    /**
     * @return  boolean
     */
    public function install()
    {
        RL_Language::load('com_installer', JPATH_ADMINISTRATOR);

        $app = JFactory::getApplication();

        // Load installer plugins for assistance if required:
        JPluginHelper::importPlugin('installer');

        $package = $this->getPackageFromUrl();


        // This event allows a custom installation of the package or a customization of the package:
        $results = $app->triggerEvent('onInstallerBeforeInstaller', [$this, &$package]);

        if (in_array(true, $results, true))
        {
            return true;
        }

        if (in_array(false, $results, true))
        {
            $this->cleanupInstall($package);

            return false;
        }

        // Check if package was uploaded successfully.
        if ( ! is_array($package))
        {
            $app->enqueueMessage(JText::_('COM_INSTALLER_UNABLE_TO_FIND_INSTALL_PACKAGE'), 'error');

            return false;
        }

        // Was the package unpacked?
        if (empty($package['type']))
        {
            JInstallerHelper::cleanupInstall($package['packagefile'], $package['extractdir']);

            $app->enqueueMessage(JText::_('JLIB_INSTALLER_ABORT_DETECTMANIFEST'), 'error');

            return false;
        }

        $installer = JInstaller::getInstance();

        $success = $installer->install($package['dir']) ? true : false;
        $msg     = '';

        $messages = $app->getMessageQueue();

        foreach ($messages as $message)
        {
            if ($message['type'] == 'error')
            {
                $this->cleanupInstall($package);

                return false;
            }
        }

        if ( ! $success)
        {
            $msg = JText::sprintf('COM_INSTALLER_INSTALL_ERROR', JText::_('COM_INSTALLER_TYPE_TYPE_' . strtoupper($package['type'])));
        }

        // This event allows a custom a post-flight:
        $app->triggerEvent('onInstallerAfterInstaller', [$this, &$package, $installer, &$success, &$msg]);

        if ( ! $success)
        {
            $app->enqueueMessage($msg, 'error');
        }

        $this->cleanupInstall($package);

        // Clear the cached extension data and menu cache
        $this->cleanCache('_system');
        $this->cleanCache('com_modules');
        $this->cleanCache('com_plugins');
        $this->cleanCache('mod_menu');

        return $success;
    }

    /**
     * @return  boolean
     */
    public function uninstall()
    {
        RL_Language::load('com_installer', JPATH_ADMINISTRATOR);

        $app = JFactory::getApplication();

        if ( ! $app->getIdentity()->authorise('core.delete', 'com_installer'))
        {
            $app->enqueueMessage(JText::_('JERROR_CORE_DELETE_NOT_PERMITTED'), 'error');

            return false;
        }

        $extension = $this->getExtensionFromUrl();

        if ( ! $extension || ! $extension->type)
        {
            $app->enqueueMessage('Could not find an extension to uninstall.', 'error');

            return false;
        }

        $extension_type = JText::_('COM_INSTALLER_TYPE_TYPE_' . strtoupper($extension->type));


        $installer = JInstaller::getInstance();

        $result = $installer->uninstall($extension->type, $extension->id);

        // Clear the cached extension data and menu cache
        $this->cleanCache('_system');
        $this->cleanCache('com_modules');
        $this->cleanCache('com_plugins');
        $this->cleanCache('mod_menu');

        if ($result === false)
        {
            $app->enqueueMessage(JText::sprintf('COM_INSTALLER_UNINSTALL_ERROR', $extension_type), 'error');

            return false;
        }

        $app->enqueueMessage(JText::sprintf('COM_INSTALLER_UNINSTALL_SUCCESS', $extension_type), 'success');

        return true;
    }

    /**
     * @return  object|false
     */
    protected function getExtensionFromUrl()
    {
        $extensions = ExtensionsHelper::getFromUrl();

        if (empty($extensions))
        {
            return false;
        }

        $extension = $extensions[0];

        $query = RL_DB::getQuery()
            ->select([
                RL_DB::quoteName('extension_id', 'id'),
                'type',
            ])
            ->from('#__extensions');

        [$type, $folder] = explode('_', $extension->types[0]->type . '_');

        switch ($type)
        {
            case 'pkg':
                $query->where(RL_DB::is('type', 'package'))
                    ->where(RL_DB::is('element', 'pkg_' . $extension->extname));
                break;

            case 'com':
                $query->where(RL_DB::is('type', 'component'))
                    ->where(RL_DB::is('element', 'com_' . $extension->extname));
                break;

            case 'mod':
                $query->where(RL_DB::is('type', 'module'))
                    ->where(RL_DB::is('element', 'mod_' . $extension->extname));
                break;

            case 'plg':
                $query->where(RL_DB::is('type', 'plugin'))
                    ->where(RL_DB::is('folder', $folder))
                    ->where(RL_DB::is('element', $extension->extname));
                break;

            default:
                return false;
        }

        return RL_DB::get()->setQuery($query)->loadObject();
    }

    /**
     * @return  bool|array  Package details or false on failure.
     */
    protected function getPackageFromUrl()
    {
        $url = base64_decode(RL_Input::getBase64('url'));

        // Did you give us a URL?
        if ( ! $url)
        {
            JFactory::getApplication()->enqueueMessage(JText::_('COM_INSTALLER_MSG_INSTALL_ENTER_A_URL'), 'error');

            return false;
        }

        // We only allow http & https here
        $uri = new JUri($url);

        if ( ! in_array($uri->getScheme(), ['http', 'https']))
        {
            JFactory::getApplication()->enqueueMessage(JText::_('COM_INSTALLER_MSG_INSTALL_INVALID_URL_SCHEME'), 'error');

            return false;
        }

        // Handle updater XML file case:
        if (preg_match('/\.xml\s*$/', $url))
        {
            $update = new JUpdate;
            $update->loadFromXml($url);
            $package_url = trim($update->get('downloadurl', false)->_data);

            if ($package_url)
            {
                $url = $package_url;
            }

            unset($update);
        }

        // Download the package at the URL given.
        $p_file = JInstallerHelper::downloadPackage($url);

        // Was the package downloaded?
        if ( ! $p_file)
        {
            JFactory::getApplication()->enqueueMessage(JText::_('COM_INSTALLER_MSG_INSTALL_INVALID_URL'), 'error');

            return false;
        }

        $tmp_dest = JFactory::getApplication()->get('tmp_path');

        // Unpack the downloaded package file.
        $package = JInstallerHelper::unpack($tmp_dest . '/' . $p_file, true);

        return $package;
    }

    private function cleanupInstall($package)
    {
        // Cleanup the install files.
        if ( ! is_file($package['packagefile']))
        {
            $package['packagefile'] = JFactory::getApplication()->get('tmp_path') . '/' . $package['packagefile'];
        }

        JInstallerHelper::cleanupInstall($package['packagefile'], $package['extractdir']);
    }
}
