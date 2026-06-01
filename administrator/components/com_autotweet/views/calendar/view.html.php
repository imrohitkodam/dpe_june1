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
 * AutotweetViewCalendar.
 *
 * @since       1.0
 */
class AutotweetViewCalendar extends XTF0FViewHtml
{
    /**
     * Runs before rendering the view template, echoing HTML to put before the
     * view template's generated HTML.
     */
    protected function preRender()
    {
        $view = $this->input->getCmd('view', 'cpanel');
        $task = $this->getModel()->getState('task', 'browse');

        $renderer = $this->getRenderer();
        $renderer->preRender($view, $task, $this->input, $this->config);
    }

    /**
     * Executes before rendering a generic page, default to actions necessary
     * for the Browse task.
     *
     * @param string $tpl Subtemplate to use
     *
     * @return bool Return true to allow rendering of the page
     */
    protected function onDisplay($tpl = null)
    {
        Extly::loadAwesome();

        return true;
    }
}
