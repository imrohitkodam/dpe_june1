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
 * AutotweetViewRequests.
 *
 * @since       1.0
 */
class AutotweetViewRequests extends AutoTweetDefaultView
{
    /**
     * onBrowse.
     *
     * @param string $tpl Param
     */
    protected function onBrowse($tpl = null)
    {
        Extly::initApp(CAUTOTWEETNG_VERSION);
        Extly::loadAwesome();

        return parent::onBrowse($tpl);
    }
}
