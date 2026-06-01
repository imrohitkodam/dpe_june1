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
 * AutotweetViewRule.
 *
 * @since       1.0
 */
class AutotweetViewRule extends XTF0FViewHtml
{
    /**
     * onEdit.
     *
     * @param string $tpl Param
     */
    public function onAdd($tpl = null)
    {
        Extly::loadAwesome();

        $file = EHtml::getRelativeFile('js', 'com_autotweet/rule.min.js');

        if ($file) {
            Extly::getSimpleScriptManager()->initApp(CAUTOTWEETNG_VERSION, $file);
            Extly::initApp(CAUTOTWEETNG_VERSION);
        }

        return parent::onAdd($tpl);
    }
}
