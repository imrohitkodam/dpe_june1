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
 * FeedImporterHelper class.
 *
 * @since       1.0
 */
class FeedImporterHelper
{
    public const ALWAYS_EXPRESSION = '* * * * *';

    /**
     * import.
     *
     * @param object $feed Params
     */
    public function import(&$feed)
    {
        if (isset($feed->params)) {
            $feed->xtform = EForm::paramsToRegistry($feed);
        }

        $import_frequency = $feed->xtform->get('import_frequency', self::ALWAYS_EXPRESSION);

        if (self::ALWAYS_EXPRESSION !== $import_frequency) {
            $automators = XTF0FModel::getTmpInstance('Automators', 'AutoTweetModel');
            $key = 'feed-'.$feed->id;

            if (!$automators->lastRunCheckFreqMhdmd($key, $import_frequency)) {
                AutotweetLogger::getInstance()->log(\Joomla\CMS\Log\Log::INFO, "Feed import: {$key} lastRunCheck skipped!");

                return;
            }
        }

        $result = new StdClass();
        $result->added_items = 0;

        $simplePie = $this->createSimplePie($feed);

        if ($simplePie->get_type() & SIMPLEPIE_TYPE_NONE) {
            throw new Exception(JText::sprintf('COM_AUTOTWEET_FEED_UNABLE_TO_PROCESS', $feed->xtform->get('title').' ('.$feed->xtform->get('feed').')'));
        }
        if ($simplePie->error) {
            throw new Exception("SimplePie error (ID={$feed->id}): ".$simplePie->error.' for '.$feed->xtform->get('title').' ('.$feed->xtform->get('feed').')');
        }

        $title = $simplePie->get_title();

        $c = (int) $feed->xtform->get('import_limit');
        $items = $simplePie->get_items(0, $c);

        $result->title = $title;
        $result->items = $items;

        $simplePie->__destruct();
        unset($items, $simplePie);

        // End SimplePie processing

        return $result;
    }

    /**
     * loadAjaxImporter.
     *
     * @param object $view Param
     */
    public static function loadAjaxImporter($view)
    {
        $ajax_import = EParameter::getComponentParam(CAUTOTWEETNG, 'ajax_import', true);
        $view->assignRef('ajax_import', $ajax_import);

        if ($ajax_import) {
            ScriptHelper::addScriptVersion(\Joomla\CMS\Uri\Uri::root().'media/com_autotweet/js/cryptojslib/core-min.js');
            ScriptHelper::addScriptVersion(\Joomla\CMS\Uri\Uri::root().'media/com_autotweet/js/cryptojslib/enc-base64-min.js');                

            $file = EHtml::getRelativeFile('js', 'com_autotweet/import.min.js');

            if ($file) {
                $dependencies = [];
                $dependencies['import'] = ['extlycore'];

                Extly::initApp(CAUTOTWEETNG_VERSION, $file, $dependencies);
            }
        } else {
            Extly::initApp(CAUTOTWEETNG_VERSION);
        }
    }

    /**
     * createSimplePie.
     *
     * @param object &$feed Params
     *
     * @return object
     */
    private function createSimplePie($feed)
    {
        // Process the feed with SimplePie
        $simplePie = new \XTS_SimplePie();
        $simplePie->set_feed_url($feed->xtform->get('url'));
        $simplePie->set_stupidly_fast(true);
        $simplePie->enable_order_by_date(true);
        $simplePie->set_input_encoding($feed->xtform->get('encoding'));

        if ($feed->xtform->get('force_fsockopen')) {
            $simplePie->force_fsockopen(true);
        }

        $simplePie->enable_cache(false);
        $use_sp_cache = EParameter::getComponentParam(CAUTOTWEETNG, 'use_sp_cache', true);

        if (($use_sp_cache) && (is_writable(JPATH_CACHE))) {
            $simplePie->set_cache_location(JPATH_CACHE);
            $simplePie->enable_cache(true);
        }

        $set_sp_timeout = EParameter::getComponentParam(CAUTOTWEETNG, 'set_sp_timeout', 10);

        if ($set_sp_timeout) {
            $simplePie->set_timeout((int) $set_sp_timeout);
        }

        $simplePie->init();

        return $simplePie;
    }
}
