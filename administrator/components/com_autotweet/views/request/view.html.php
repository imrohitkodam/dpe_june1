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
 * AutotweetViewRequest.
 *
 * @since       1.0
 */
class AutotweetViewRequest extends AutoTweetDefaultView
{
    /**
     * onAdd.
     *
     * @param string $tpl Param
     */
    public function onAdd($tpl = null)
    {
        Extly::loadAwesome();

        $file = EHtml::getRelativeFile('js', 'com_autotweet/post.min.js');

        if ($file) {
            $dependencies = [];
            $dependencies['post'] = ['extlycore'];
            Extly::initApp(CAUTOTWEETNG_VERSION, $file, $dependencies);
        }

        return parent::onAdd($tpl);
    }
}
