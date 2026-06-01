<?php

/**
 * @version     admin/classes/appsswitcher.php 2024-03-27 zanardigit
 * @package     Watchful Client
 * @author      Watchful
 * @authorUrl   https://watchful.net
 * @copyright   Copyright (c) 2012-2023 Watchful
 * @license     GNU/GPL v3 or later
 */

use Joomla\CMS\Application\AdministratorApplication;
use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Installer\Installer;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Service\Provider\Database;
use Joomla\Database\DatabaseInterface;

class WatchfulliAppSwitcher
{
    /** @var CMSApplicationInterface */
    private $originalApp;

    public function __construct()
    {
        $this->originalApp = Factory::getApplication();
    }

    private function shouldSwitchToWatchfulApp()
    {
        if (!$this->originalApp->input->getBool('switchToWatchfulApp', true)) {
            return false;
        }

        if (Watchfulli::joomla()->getShortVersion() == '3.2.0')
        {
            return false;
        }

        if ($this->originalApp instanceof AdministratorApplication)
        {
            return false;
        }

        return true;
    }

    public function switchToWatchfulApp()
    {
        if (!$this->shouldSwitchToWatchfulApp())
        {
            return;
        }

        $this->resetDbDriver();

        Factory::$application = new WatchfulliApplication();
        Factory::$application->setOriginalApp($this->originalApp);
    }

    public function switchToOriginalApp()
    {
        if (Factory::$application instanceof WatchfulliApplication)
        {
            Factory::$application = $this->originalApp;
        }
    }

    // Reset db driver override made by "System - FaLang Database Driver" plugin
    // which prevents normal updates.
    private function resetDbDriver()
    {
        if (!PluginHelper::isEnabled('system', 'falangdriver')) {
            return;
        }

        Factory::$database = null;

        if (version_compare(Watchfulli::joomla()->getShortVersion(), '4.2', '<'))
        {
            return;
        }

        $container = Factory::getContainer();
        $container->registerServiceProvider(new Database());

        Installer::getInstance()->setDatabase($container->get(DatabaseInterface::class));
    }
}
