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
 * AutotweetViewFeed.
 *
 * @since       1.0
 */
class AutotweetViewFeed extends AutoTweetDefaultView
{
    /**
     * onAdd.
     *
     * @param string $tpl Param
     */
    public function onAdd($tpl = null)
    {
        $jlang = \Joomla\CMS\Factory::getLanguage();
        $jlang->load('com_content');

        Extly::loadAwesome();

        $file = EHtml::getRelativeFile('js', 'com_autotweet/feed.min.js');

        if ($file) {
            $dependencies = [];
            $paths = [];

            $ajax_import = EParameter::getComponentParam(CAUTOTWEETNG, 'ajax_import', true);
            $this->assignRef('ajax_import', $ajax_import);

            if ($ajax_import) {
                ScriptHelper::addScriptVersion(\Joomla\CMS\Uri\Uri::root().'media/com_autotweet/js/cryptojslib/core-min.js');
                ScriptHelper::addScriptVersion(\Joomla\CMS\Uri\Uri::root().'media/com_autotweet/js/cryptojslib/enc-base64-min.js');                

                $paths['import'] = 'media/com_autotweet/js/import.min';
            }

            Extly::initApp(CAUTOTWEETNG_VERSION, $file, $dependencies, $paths);
        }

        return parent::onAdd($tpl);
    }
}
