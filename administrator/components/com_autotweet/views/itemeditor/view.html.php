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
 * AutotweetViewItemEditor.
 *
 * @since       1.0
 */
class AutotweetViewItemEditor extends AutoTweetDefaultView
{
    /**
     * onBrowse.
     *
     * @param string $tpl Param
     */
    protected function onAdd($tpl = null)
    {
        Extly::loadAwesome();

        $root = \Joomla\CMS\Uri\Uri::root();

        $attribs = ['defer' => false, 'async' => false];
        ScriptHelper::addScript($root.'media/lib_perfect-publisher/js/backbone/underscore.min.js', [], $attribs);
        ScriptHelper::addScript($root.'media/lib_perfect-publisher/js/angular/angular.min.js', [], $attribs);
        ScriptHelper::addScript($root.'media/lib_perfect-publisher/js/angular/angular-resource.min.js', [], $attribs);
        ScriptHelper::addScript($root.'media/lib_perfect-publisher/js/angular/ui-bootstrap-buttons.min.js', [], $attribs);

        ScriptHelper::addScriptVersion($root.'media/lib_perfect-publisher/js/extlycoreng.min.js', [], $attribs);
        ScriptHelper::addScriptVersion($root.'media/com_autotweet/js/itemeditor.min.js', [], $attribs);

        return parent::onAdd($tpl);
    }
}
