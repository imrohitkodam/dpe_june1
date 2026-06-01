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
 * AutotweetViewFeeds.
 *
 * @since       1.0
 */
class AutotweetViewFeeds extends AutoTweetDefaultView
{
    /**
     * onBrowse.
     *
     * @param string $tpl Param
     */
    protected function onBrowse($tpl = null)
    {
        FeedImporterHelper::loadAjaxImporter($this);
        Extly::loadAwesome();

        return parent::onBrowse($tpl);
    }
}
