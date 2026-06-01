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
 * AutotweetControllerCron.
 *
 * @since       1.0
 */
class AutotweetControllerCron extends ExtlyController
{
    /**
     * The tasks for which caching should be enabled by default.
     *
     * @var array
     */
    protected $cacheableTasks = [];

    /**
     * run.
     *
     * Example: http://YOUR_SITE/index.php?option=com_autotweet&view=cron&task=run
     */
    public function run()
    {
        header('Content-type: text/plain');

        $logger = AutotweetLogger::getInstance();

        $secret_word = EParameter::getComponentParam(CAUTOTWEETNG, 'frontend_secret_word');

        if ((empty($secret_word)) || ($secret_word !== \Joomla\CMS\Factory::getApplication()->input->get('key'))) {
            echo 'Access denied';
            $logger->log(\Joomla\CMS\Log\Log::ERROR, 'Access denied (frontend_secret_word)');

            flush();
            \Joomla\CMS\Factory::getApplication()->close();
        }

        define('AUTOTWEET_CRONJOB_RUNNING', true);

        $now = \Joomla\CMS\Factory::getDate();
        $msg = 'AutotweetControllerCron run: '.$now->toSql();
        $logger->log(\Joomla\CMS\Log\Log::INFO, $msg);

        @ob_end_clean();
        echo $msg;

        // Disable caching.
        $config = \Joomla\CMS\Factory::getConfig();
        $config->set('caching', 0);
        $config->set('cache_handler', 'file');

        // Starting Indexer.
        $logger->log(\Joomla\CMS\Log\Log::INFO, JText::_('AUTOTWEET_CLI_STARTING_PROCESS'));

        // Remove the script time limit.
        @set_time_limit(0);

        // Initialize the time value
        $timetrack = microtime(true);

        $max_posts = EParameter::getComponentParam(CAUTOTWEETNG, 'max_posts', 1);
        PostShareManager::postQueuedMessages($max_posts);

        $cronjobHelper = CronjobHelper::getInstance();
        $cronjobHelper->publishPosts();

        if (EParameter::getComponentParam(CAUTOTWEETNG, 'feeds_enabled', false)) {
            FeedLoaderHelper::importFeeds();
        }

        $cronjobHelper->contentPolling();

        // Total reporting.
        $logger->log(\Joomla\CMS\Log\Log::INFO, JText::sprintf('AUTOTWEET_CLI_PROCESS_COMPLETE', round(microtime(true) - $timetrack, 3)));

        flush();
        \Joomla\CMS\Factory::getApplication()->close();
    }
}
