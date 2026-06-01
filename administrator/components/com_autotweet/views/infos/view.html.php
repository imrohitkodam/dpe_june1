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

// Load framework base classes
jimport('joomla.application.component.view');

/**
 * AutotweetViewInfos.
 *
 * @since       1.0
 */
class AutotweetViewInfos extends AutoTweetDefaultView
{
    /**
     * onBrowse.
     *
     * @param string $tpl Param
     *
     * @return bool
     */
    protected function onBrowse($tpl = null)
    {
        Extly::loadAwesome();

        // Load the model
        $info = XTF0FModel::getTmpInstance('Update', 'AutoTweetModel');

        $this->assign('comp', $info->getComponentInfo());
        $this->assign('plugins', $info->getPluginInfo());
        $this->assign('thirdparty', $info->getThirdpartyInfo());
        $this->assign('sysinfo', $info->getSystemInfo());

        Extly::initApp(CAUTOTWEETNG_VERSION);
    }
}
