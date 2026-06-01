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
 * Helper for posts form AutoTweet to channels (twitter, Facebook, ...).
 *
 * @since       1.0
 */
final class FeedLoaderHelper
{
    private function __construct()
    {
    }

    /**
     * getPreview.
     *
     * @param object &$feed Params
     *
     * @return object
     */
    public static function getPreview(&$feed)
    {
        if (isset($feed->params)) {
            $feed->xtform = EForm::paramsToRegistry($feed);
        }

        $import_limit = $feed->xtform->get('import_limit');
        $feed->xtform->set('import_limit', 3);

        $check_existing = $feed->xtform->get('check_existing');
        $feed->xtform->set('check_existing', 0);

        $loadResult = null;

        try {
            $start_time = time();

            $feedImporterHelper = new FeedImporterHelper();
            $feedProcessorHelper = new FeedProcessorHelper();
            $feedGeneratorHelper = new FeedGeneratorHelper();

            $loadResult = $feedImporterHelper->import($feed);
            $contents = $feedProcessorHelper->process($feed, $loadResult);
            $feedGeneratorHelper->generateContent($contents, $feed->xtform);

            if ($loadResult) {
                $loadResult->processed_time = time() - $start_time;
                $loadResult->preview = $contents;
            }
        } catch (Exception $e) {
            ELog::showMessage($e->getMessage(), \Joomla\CMS\Log\Log::ERROR);
        }

        $feed->xtform->set('import_limit', $import_limit);
        $feed->xtform->set('check_existing', $check_existing);

        return $loadResult;
    }

    /**
     * importFeeds.
     *
     * @param array $cid Params
     *
     * @return int
     */
    public static function importFeeds($cid = [])
    {
        $logger = AutotweetLogger::getInstance();
        $init_date = \Joomla\CMS\Factory::getDate()->format(JText::_('COM_AUTOTWEET_DATE_FORMAT'));
        $logger->log(\Joomla\CMS\Log\Log::INFO, 'importFeeds: Starting '.$init_date);

        // Allows importing images and text
        if (!ini_get('allow_url_fopen')) {
            ini_set('allow_url_fopen', 1);
        }

        $feedsModel = XTF0FModel::getTmpInstance('Feeds', 'AutoTweetModel');
        $feedsModel->set('published', 1);
        $feedsModel->set('ids', $cid);
        $feeds = $feedsModel->getItemList(true);

        $total_time = 0;
        $item_counter = 0;

        // Process each feed
        foreach ($feeds as $feed) {
            // Attempt to stop timeouts and errors stopping all imports in cron/pseudo-cron
            try {
                $start_time = time();
                $logger->log(\Joomla\CMS\Log\Log::INFO, 'importFeeds: Feed '.$feed->name.' ('.$feed->id.')');

                $loadResult = self::importFeed($feed);

                $processed_time = time() - $start_time;

                $logger->log(
                    \Joomla\CMS\Log\Log::INFO,
                    'importFeeds: Items='.$loadResult->added_items.' Processed time '.$processed_time.' secs.'
                );

                $total_time += $processed_time;
                $item_counter += $loadResult->added_items;
            } catch (Exception $e) {
                $logger->log(\Joomla\CMS\Log\Log::ERROR, 'importFeeds: Exception! '.$e->getMessage());
            }
        }

        $logger->log(
            \Joomla\CMS\Log\Log::INFO,
            'importFeeds: Total Items='.$item_counter.' Processed time '.$total_time.' secs.'
        );

        return $item_counter;
    }

    /**
     * importFeed.
     *
     * @param object &$feed Params
     *
     * @return object
     */
    public static function importFeed(&$feed)
    {
        $noresult = new StdClass();
        $noresult->added_items = 0;

        if (isset($feed->params)) {
            $feed->xtform = EForm::paramsToRegistry($feed);
        }

        if (('feedcontent' !== $feed->xtform->get('contenttype_id')) &&
            ('feedk2' !== $feed->xtform->get('contenttype_id'))) {
            return $noresult;
        }

        $feedImporterHelper = new FeedImporterHelper();
        $feedProcessorHelper = new FeedProcessorHelper();
        $feedGeneratorHelper = new FeedGeneratorHelper();

        $loadResult = $feedImporterHelper->import($feed);

        if ((!isset($loadResult->items)) || (0 === count($loadResult->items))) {
            return $noresult;
        }

        $contents = $feedProcessorHelper->process($feed, $loadResult);

        if (0 === count($contents)) {
            return $noresult;
        }

        // Simple check for duplicates in feed contents
        $feedGeneratorHelper->removeDuplicates($contents);

        if (0 === count($contents)) {
            return $noresult;
        }

        $feedGeneratorHelper->generateContent($contents, $feed->xtform);

        $loadResult->added_items = $feedGeneratorHelper->save($contents, $feed->xtform);

        return $loadResult;
    }
}
