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
 * AutotweetViewManager.
 *
 * @since       1.0
 */
class AutotweetViewManager extends XTF0FViewHtml
{
    /**
     * onAdd.
     *
     * @param string $tpl Param
     */
    public function onAdd($tpl = null)
    {
        $result = parent::onAdd($tpl);

        Extly::loadAwesome();

        $file = EHtml::getRelativeFile('js', 'com_autotweet/manager.min.js');

        if ($file) {
            $dependencies = [];
            $dependencies['manager'] = ['extlycore'];
            $this->assign('managerjs', $file);
            Extly::initApp(CAUTOTWEETNG_VERSION, $file, $dependencies);
        }

        return $result;
    }
}
