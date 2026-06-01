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

// Autotweet Model for info dialog.

jimport('joomla.application.component.model');

/**
 * AutotweetModelUpdate.
 *
 * @since       1.0
 */
class AutotweetModelUpdate extends XTF0FModel
{
    /**
     * getComponentInfo.
     *
     * @return array
     */
    public function getComponentInfo()
    {
        static $compdata = null;

        if (!$compdata) {
            $compdata = InstallationInfoHelper::getComponentInfo();

            if (!$compdata) {
                $this->setError(JText::sprintf('COM_AUTOTWEET_MSG_ERROR_FILENOTFOUND', 'version information'));

                return null;
            }
        }

        return $compdata;
    }

    /**
     * getPluginInfo.
     *
     * @return array
     */
    public function getPluginInfo()
    {
        static $plugdata = null;

        if (!$plugdata) {
            $plugdata = InstallationInfoHelper::getPluginInfo();

            if (!$plugdata) {
                $this->setError(JText::sprintf('COM_AUTOTWEET_MSG_ERROR_FILENOTFOUND', 'version information'));

                return null;
            }
        }

        return $plugdata;
    }

    /**
     * getThirdpartyInfo.
     *
     * @return array
     */
    public function getThirdpartyInfo()
    {
        static $thirdparty = null;

        if (!$thirdparty) {
            $thirdparty = InstallationInfoHelper::getThirdpartyInfo();

            if (!$thirdparty) {
                $this->setError(JText::sprintf('COM_AUTOTWEET_MSG_ERROR_FILENOTFOUND', 'version information'));

                return null;
            }

            // Load language to get the name for unknown plugins with language support
            $jlang = \Joomla\CMS\Factory::getLanguage();

            foreach ($thirdparty as $plugin) {
                $jlang->load($plugin['name']);
            }
        }

        return $thirdparty;
    }

    /**
     * getSystemInfo.
     *
     * @return array
     */
    public function getSystemInfo()
    {
        static $sysdata = null;

        if (!$sysdata) {
            $sysdata = InstallationInfoHelper::getSystemInfo();

            if (!$sysdata) {
                $this->setError('No system info available!');

                return null;
            }
        }

        return $sysdata;
    }
}
