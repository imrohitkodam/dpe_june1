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

// No direct access
defined('_JEXEC') || exit();

class Pkg_AutotweetInstallerScript
{
    /**
     * The name of our package, e.g. pkg_example. Used for dependency tracking.
     *
     * @var string
     */
    protected $packageName = 'pkg_autotweet';

    /**
     * The name of our component, e.g. com_example. Used for dependency tracking.
     *
     * @var string
     */
    protected $componentName = 'com_autotweet';

    /**
     * The minimum PHP version required to install this extension.
     *
     * @var string
     */
    protected $minimumPHPVersion = '7.3.0';

    /**
     * The minimum Joomla! version required to install this extension.
     *
     * @var string
     */
    protected $minimumJoomlaVersion = '3.8.0';

    /**
     * The maximum Joomla! version this extension can be installed on.
     *
     * @var string
     */
    protected $maximumJoomlaVersion = '4.99.99';

    /**
     * A list of extensions (modules, plugins) to enable after installation. Each item has four values, in this order:
     * type (plugin, module, ...), name (of the extension), client (0=site, 1=admin), group (for plugins).
     *
     * These extensions are ONLY enabled when you do a clean installation of the package, i.e. it will NOT run on update
     *
     * @var array
     */
    protected $extensionsToEnable = [
        // Modules at the component level

        ['plugin', 'autotweetautomator', 1, 'system'],
        ['plugin', 'autotweetcontent', 1, 'system'],
        ['plugin', 'autotweetpost', 1, 'autotweet'],
        ['plugin', 'joocialeditor', 1, 'editors-xtd'],
    ];

    protected $modulesToPublish = [
        // * modules => { (folder) => { (module) => { (position), (published) } }* }*
        'modules' => [
            'admin' => [
                'autotweet_latest' => ['cpanel', 1],
                'joocial_menu' => ['menu', 1],
            ],
            'site' => [
                'twfollow' => ['left', 0],
                'light_rss' => ['left', 0],
            ],
        ],
    ];

    /**
     * Like above, but enable these extensions on installation OR update. Use this sparingly. It overrides the
     * preferences of the user. Ideally, this should only be used for installer plugins.
     *
     * @var array
     */
    protected $extensionsToAlwaysEnable = [
        // ['plugin', 'autotweetbackup', 1, 'installer'],
    ];

    /**
     * A list of plugins to uninstall when installing or updating the package. Each item has two values, in this order:
     * name (element), folder.
     *
     * @var array
     */
    protected $uninstallPlugins = [
        // ['jsonapi', 'autotweetbackup'],
        // ['legacyapi', 'autotweetbackup'],
    ];

    /**
     * =================================================================================================================
     * DO NOT EDIT BELOW THIS LINE
     * =================================================================================================================.
     *
     * @param mixed $type
     * @param mixed $parent
     */

    /**
     * Joomla! pre-flight event. This runs before Joomla! installs or updates the package. This is our last chance to
     * tell Joomla! if it should abort the installation.
     *
     * In here we'll try to install FOF. We have to do that before installing the component since it's using an
     * installation script extending FOF's InstallScript class. We can't use a <file> tag in the manifest to install FOF
     * since the FOF installation is expected to fail if a newer version of FOF is already installed on the site.
     *
     * @param string                    $type   Installation type (install, update, discover_install)
     * @param \JInstallerAdapterPackage $parent Parent object
     *
     * @return bool True to let the installation proceed, false to halt the installation
     */
    public function preflight($type, $parent)
    {
        // Check the minimum PHP version
        if (!version_compare(\PHP_VERSION, $this->minimumPHPVersion, 'ge')) {
            $msg = "<p>You need PHP {$this->minimumPHPVersion} or later to install this package</p>";
            \Joomla\CMS\Log\Log::add($msg, \Joomla\CMS\Log\Log::WARNING, 'jerror');

            return false;
        }

        // Check the minimum Joomla! version
        if (!version_compare(JVERSION, $this->minimumJoomlaVersion, 'ge')) {
            $msg = "<p>You need Joomla! {$this->minimumJoomlaVersion} or later to install this component</p>";
            \Joomla\CMS\Log\Log::add($msg, \Joomla\CMS\Log\Log::WARNING, 'jerror');

            return false;
        }

        // Check the maximum Joomla! version
        if (!version_compare(JVERSION, $this->maximumJoomlaVersion, 'le')) {
            $msg = "<p>You need Joomla! {$this->maximumJoomlaVersion} or earlier to install this component</p>";
            \Joomla\CMS\Log\Log::add($msg, \Joomla\CMS\Log\Log::WARNING, 'jerror');

            return false;
        }

        // HHVM made sense in 2013, now PHP 7 is a way better solution than an hybrid PHP interpreter
        if (defined('HHVM_VERSION')) {
            $msg = '<p>We have detected that you are running HHVM instead of PHP. This software WILL NOT WORK properly on HHVM. Please switch to PHP 7 instead.</p>';
            \Joomla\CMS\Log\Log::add($msg, \Joomla\CMS\Log\Log::WARNING, 'jerror');

            return false;
        }

        if (!defined('XTF0F_INCLUDED')) {
            if (file_exists(__DIR__.'/library/vendor/autoload.php')) {
                require_once __DIR__.'/library/vendor/autoload.php';
            } elseif (file_exists(JPATH_ADMINISTRATOR.'/components/com_autotweet/api/autotweetapi.php')) {
                require_once JPATH_ADMINISTRATOR.'/components/com_autotweet/api/autotweetapi.php';
            }
        }

        if (!defined('XTF0F_INCLUDED')) {
            $msg = '<p>Perfect Publisher library failed to load.</p>';
            \Joomla\CMS\Log\Log::add($msg, \Joomla\CMS\Log\Log::WARNING, 'jerror');

            return false;
        }

        if (version_compare(JVERSION, '4.0', 'ge')) {
            $this->modulesToPublish['modules']['admin']['joocial_menu'] = ['title', 1];
        }

        return true;
    }

    /**
     * Runs after install, update or discover_update. In other words, it executes after Joomla! has finished installing
     * or updating your component. This is the last chance you've got to perform any additional installations, clean-up,
     * database updates and similar housekeeping functions.
     *
     * @param string                      $type   install, update or discover_update
     * @param \JInstallerAdapterComponent $parent Parent object
     */
    public function postflight($type, $parent)
    {
        // Always uninstall these plugins
        if (isset($this->uninstallPlugins) && !empty($this->uninstallPlugins)) {
            foreach ($this->uninstallPlugins as $pluginInfo) {
                try {
                    $this->uninstallPlugin($pluginInfo[1], $pluginInfo[0]);
                } catch (Exception $e) {
                    // No op.
                }
            }
        }

        // Always enable these extensions
        if (isset($this->extensionsToAlwaysEnable) && !empty($this->extensionsToAlwaysEnable)) {
            $this->enableExtensions($this->extensionsToAlwaysEnable);
        }

        if (isset($this->modulesToPublish) && !empty($this->modulesToPublish)) {
            $this->publishModules($parent, $this->modulesToPublish);
        }

        /**
         * Try to install FEF. We only need to do this in postflight. A failure, while detrimental to the display of the
         * extension, is non-fatal to the installation and can be rectified by manual installation of the FEF package.
         * We can't use a <file> tag in our package manifest because FEF's package is *supposed* to fail to install if
         * a newer version is already installed. This would unfortunately cancel the installation of the entire package,
         * so we have to get a bit tricky.
         */
        // NO FEF
        // $this->installOrUpdateFEF($parent);

        /**
         * Clean the cache after installing the package.
         *
         * See bug report https://github.com/joomla/joomla-cms/issues/16147
         */
        $conf = \Joomla\CMS\Factory::getConfig();
        $clearGroups = ['_system', 'com_modules', 'mod_menu', 'com_plugins', 'com_modules'];
        $cacheClients = [0, 1];

        foreach ($clearGroups as $group) {
            foreach ($cacheClients as $client_id) {
                try {
                    $options = [
                        'defaultgroup' => $group,
                        'cachebase' => ($client_id) ? JPATH_ADMINISTRATOR.'/cache' : $conf->get('cache_path', JPATH_SITE.'/cache'),
                    ];

                    /** @var JCache $cache */
                    $cache = \JCache::getInstance('callback', $options);
                    $cache->clean();
                } catch (Exception $exception) {
                    $options['result'] = false;
                }

                // Trigger the onContentCleanCache event.
                try {
                    \Joomla\CMS\Factory::getApplication()->triggerEvent('onContentCleanCache', $options);
                } catch (Exception $e) {
                    // Suck it up
                }
            }
        }
    }

    /**
     * Runs on installation (but not on upgrade). This happens in install and discover_install installation routes.
     *
     * @param \JInstallerAdapterPackage $parent Parent object
     *
     * @return bool
     */
    public function install($parent)
    {
        // Enable the extensions we need to install
        $this->enableExtensions();

        return true;
    }

    /**
     * Enable modules and plugins after installing them.
     *
     * @param mixed $extensions
     */
    private function enableExtensions($extensions = [])
    {
        if (empty($extensions)) {
            $extensions = $this->extensionsToEnable;
        }

        foreach ($extensions as $ext) {
            $this->enableExtension($ext[0], $ext[1], $ext[2], $ext[3]);
        }
    }

    /**
     * Enable an extension.
     *
     * @param string $type   the extension type
     * @param string $name   the name of the extension (the element field)
     * @param int    $client the application id (0: Joomla CMS site; 1: Joomla CMS administrator)
     * @param string $group  the extension group (for plugins)
     */
    private function enableExtension($type, $name, $client = 1, $group = null)
    {
        try {
            $db = \Joomla\CMS\Factory::getDbo();
            $query = $db->getQuery(true)
                ->update('#__extensions')
                ->set($db->qn('enabled').' = '.$db->q(1))
                ->where('type = '.$db->quote($type))
                ->where('element = '.$db->quote($name));
        } catch (\Exception $e) {
            return;
        }

        switch ($type) {
            case 'plugin':
                // Plugins have a folder but not a client
                $query->where('folder = '.$db->quote($group));

                break;
            case 'language':
            case 'module':
            case 'template':
                // Languages, modules and templates have a client but not a folder
                $client = JApplicationHelper::getClientInfo($client, true);
                $query->where('client_id = '.(int) $client->id);

                break;
            default:
            case 'library':
            case 'package':
            case 'component':
                // Components, packages and libraries don't have a folder or client.
                // Included for completeness.
                break;
        }

        try {
            $db->setQuery($query);
            $db->execute();
        } catch (\Exception $e) {
        }
    }

    private function uninstallPlugin($folder, $element)
    {
        $db = \Joomla\CMS\Factory::getDbo();

        // Does the plugin exist?
        $query = $db->getQuery(true)
            ->select('*')
            ->from('#__extensions')
            ->where($db->qn('type').' = '.$db->q('plugin'))
            ->where($db->qn('folder').' = '.$db->q($folder))
            ->where($db->qn('element').' = '.$db->q($element));

        try {
            $result = $db->setQuery($query)->loadAssoc();

            if (empty($result)) {
                return;
            }

            $eid = $result['extension_id'];
        } catch (Exception $e) {
            return;
        }

        /*
         * Here's a bummer. If you try to uninstall a plugin Joomla throws a nonsensical error message about the
         * plugin's XML manifest missing -- after it has already uninstalled the plugin! This error causes the package
         * installation to fail which results in the extension being installed BUT the database record of the package
         * NOT being present which makes it impossible to uninstall.
         *
         * So I have to hack my way around it which is ugly but the only viable alternative :(
         */
        try {
            // Safely delete the row in the extensions table
            $row = \Joomla\CMS\Table\Table::getInstance('extension');
            $row->load((int) $eid);
            $row->delete($eid);

            // Delete the plugin's files
            $pluginPath = sprintf('%s/%s/%s', JPATH_PLUGINS, $folder, $element);

            if (is_dir($pluginPath)) {
                JFolder::delete($pluginPath);
            }

            // Delete the plugin's language files
            $langFiles = [
                sprintf('%s/language/en-GB/en-GB.plg_%s_%s.ini', JPATH_ADMINISTRATOR, $folder, $element),
                sprintf('%s/language/en-GB/en-GB.plg_%s_%s.sys.ini', JPATH_ADMINISTRATOR, $folder, $element),
            ];

            foreach ($langFiles as $file) {
                if (@is_file($file)) {
                    unlink($file);
                }
            }
        } catch (Exception $e) {
            // I tried, I failed. Dear user, do NOT try to enable that old plugin. Bye!
        }
    }

    private function publishModules($parent, $extensions = [])
    {
        $src = $parent->getParent()->getPath('source');
        $db = XTF0FPlatform::getInstance()->getDbo();

        if (empty($extensions)) {
            return;
        }

        // Modules installation
        foreach ($extensions['modules']  as $folder => $modules) {
            if (empty($modules)) {
                continue;
            }

            foreach ($modules as $module => $modulePreferences) {
                $path = "${src}/${folder}/${module}";

                if (!is_dir($path)) {
                    $path = "${src}/${folder}/mod_${module}";
                }

                if (!is_dir($path)) {
                    $path = "${src}/${module}";
                }

                if (!is_dir($path)) {
                    $path = "${src}/mod_${module}";
                }

                if (!is_dir($path)) {
                    continue;
                }

                // Was the module already installed?
                $sql = $db->getQuery(true)
                    ->select('COUNT(*)')
                    ->from('#__modules')
                    ->where($db->qn('module').' = '.$db->q('mod_'.$module));
                $db->setQuery($sql);

                try {
                    $count = (int) $db->loadResult();
                } catch (Exception $exc) {
                    $count = 0;
                }

                // $installer = new JInstaller();
                // $result = $installer->install($path);
                // $status->modules[] = [
                //     'name' => 'mod_'.$module,
                //     'client' => $folder,
                //     'result' => $result,
                // ];

                // Modify where it's published and its published state
                if (0 === $count) {
                    continue;
                }

                // A. Position and state
                [$modulePosition, $modulePublished] = $modulePreferences;

                $sql = $db->getQuery(true)
                    ->update($db->qn('#__modules'))
                    ->set($db->qn('position').' = '.$db->q($modulePosition))
                    ->where($db->qn('module').' = '.$db->q('mod_'.$module));

                if ($modulePublished) {
                    $sql->set($db->qn('published').' = '.$db->q('1'));
                }

                $db->setQuery($sql);

                try {
                    $db->execute();
                } catch (Exception $exc) {
                    // Nothing
                }

                // B. Change the ordering of back-end modules to 1 + max ordering
                if ('admin' === $folder) {
                    try {
                        $query = $db->getQuery(true);
                        $query->select('MAX('.$db->qn('ordering').')')
                            ->from($db->qn('#__modules'))
                            ->where($db->qn('position').'='.$db->q($modulePosition));
                        $db->setQuery($query);
                        $position = $db->loadResult();
                        ++$position;

                        $query = $db->getQuery(true);
                        $query->update($db->qn('#__modules'))
                            ->set($db->qn('ordering').' = '.$db->q($position))
                            ->where($db->qn('module').' = '.$db->q('mod_'.$module));
                        $db->setQuery($query);
                        $db->execute();
                    } catch (Exception $exc) {
                        // Nothing
                    }
                }

                // C. Link to all pages
                try {
                    $query = $db->getQuery(true);
                    $query->select('id')->from($db->qn('#__modules'))
                        ->where($db->qn('module').' = '.$db->q('mod_'.$module));
                    $db->setQuery($query);
                    $moduleid = $db->loadResult();

                    $query = $db->getQuery(true);
                    $query->select('*')->from($db->qn('#__modules_menu'))
                        ->where($db->qn('moduleid').' = '.$db->q($moduleid));
                    $db->setQuery($query);
                    $assignments = $db->loadObjectList();
                    $isAssigned = !empty($assignments);

                    if (!$isAssigned) {
                        $o = (object) [
                            'moduleid' => $moduleid,
                            'menuid' => 0,
                        ];
                        $db->insertObject('#__modules_menu', $o);
                    }
                } catch (Exception $exc) {
                    // Nothing
                }
            }
        }
    }
}
