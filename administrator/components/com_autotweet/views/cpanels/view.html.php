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

use XTP_BUILD\Extly\Infrastructure\Service\Cms\Joomla\ScriptHelper;

/**
 * AutotweetViewCpanels.
 *
 * @since       1.0
 */
class AutotweetViewCpanels extends AutoTweetDefaultView
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

        $data = new JRegistry();
        GridHelper::loadComponentInfo($data);
        GridHelper::loadStats($data);
        GridHelper::loadStatsTimeline($data);
        $this->assign('data', $data);

        ScriptHelper::addScript('//cdnjs.cloudflare.com/ajax/libs/d3/3.5.5/d3.min.js');
        ScriptHelper::addScript('//cdnjs.cloudflare.com/ajax/libs/nvd3/1.7.0/nv.d3.min.js');
        ScriptHelper::addStyleSheet('//cdnjs.cloudflare.com/ajax/libs/nvd3/1.7.0/nv.d3.min.css');

        // Get component parameter - Offline mode
        $version_check = EParameter::getComponentParam(CAUTOTWEETNG, 'version_check', 1);
        $this->assign('version_check', $version_check);

        $platform = XTF0FPlatform::getInstance();

        if (($version_check) && ($platform->isBackend())) {
            $file = EHtml::getRelativeFile('js', 'com_autotweet/liveupdate.min.js');

            if ($file) {
                $dependencies = [];
                $dependencies['liveupdate'] = ['extlycore'];

                // Extly::initApp(CAUTOTWEETNG_VERSION, $file, $dependencies);
                Extly::getSimpleScriptManager()->initApp(CAUTOTWEETNG_VERSION, $file, $dependencies);
            }
        }

        $dependencies = [];
        $fileStats = EHtml::getRelativeFile('js', 'com_autotweet/stats.min.js');
        Extly::getSimpleScriptManager()->initApp(CAUTOTWEETNG_VERSION, $fileStats, $dependencies);

        parent::onBrowse($tpl);
    }
}
