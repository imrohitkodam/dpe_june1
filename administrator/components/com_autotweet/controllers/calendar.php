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
 * AutotweetControllerCalendar.
 *
 * @since       1.0
 */
class AutotweetControllerCalendar extends ExtlyController
{
    /**
     * Default task. Assigns a model to the view and asks the view to render
     * itself.
     *
     * YOU MUST NOT USETHIS TASK DIRECTLY IN A URL. It is supposed to be
     * used ONLY inside your code. In the URL, use task=browse instead.
     *
     * @param bool   $cachable  Is this view cacheable?
     * @param bool   $urlparams Add your safe URL parameters (see further down in the code)
     * @param string $tpl       The name of the template file to parse
     *
     * @return bool
     */
    public function display($cachable = false, $urlparams = false, $tpl = null)
    {
        return parent::display(false, $urlparams, $tpl);
    }
}
