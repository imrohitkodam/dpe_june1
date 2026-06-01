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

if (!defined('AUTOTWEET_API') && !@include_once(JPATH_ADMINISTRATOR.'/components/com_autotweet/api/autotweetapi.php')) {
    return;
}

/**
 * PlgSystemAutotweetAutomator class.
 *
 * @since       1.0
 */
class PlgSystemAutotweetAutomator extends \Joomla\CMS\Plugin\CMSPlugin
{
    protected $cron_enabled = false;

    protected $max_posts = 1;

    protected $interval = 180;

    protected $detect_bots = 0;

    protected $crawlers = 'Google|Rambler|Yahoo|accoona|ASPSeek|Lycos|Scooter|AltaVista|eStyle|Scrubby|Yandex|Speedy|Ezooms|ichiro|Minisearch|Gist|TweetedTimes|Facebook|Twitter';

    protected $crawler_patterns = 'crawl|bot|spider|hunter|checker|discovery|Java';

    protected $additional_crawlers = '';

    protected $blocked_ips = '';

    /**
     * plgSystemAutotweetAutomator.
     *
     * @param string &$subject Params
     * @param array  $params   Params
     */
    public function __construct(&$subject, $params)
    {
        parent::__construct($subject, $params);

        $pluginParams = $this->params;

        $this->max_posts = (int) $pluginParams->get('max_posts', 1);
        $this->interval = (int) $pluginParams->get('interval', 180);
        $this->detect_bots = (int) $pluginParams->get('detect_bots', 0);
        $this->additional_crawlers = $pluginParams->get('crawlers', '');
        $this->blocked_ips = $pluginParams->get('blocked_ips', '');

        // Correct value if value is under the minimum
        if ($this->interval < 180) {
            $this->interval = 180;
        }

        // Load component language file for use with plugin
        $jlang = \Joomla\CMS\Factory::getLanguage();
        $jlang->load('com_autotweet');
    }

    /**
     * onAfterRender.
     */
    public function onAfterRender()
    {
        if ((class_exists('Extly')) && (Extly::hasApp())) {
            $app = \Joomla\CMS\Factory::getApplication();

            // Get the response body .... an additional check for J! 3.0.0
            if (method_exists($app, 'getBody')) {
                $body = $app->getBody();
            } else {
                $body = \Joomla\CMS\Factory::getApplication()->getBody();
            }

            Extly::insertDependencyManager($body);

            if (method_exists($app, 'setBody')) {
                $app->setBody($body);
            } else {
                \Joomla\CMS\Factory::getApplication()->setBody($body);
            }
        }

        $this->_onAfterRender();
    }

    /**
     * Checks for new events in the database (no triggers).
     */
    private function _onAfterRender()
    {
        $app = \Joomla\CMS\Factory::getApplication();

        if ($app->isClient('administrator')) {
            return;
        }

        $option = $app->input->get('option');
        $task = $app->input->get('task');

        if (('com_autotweet' === $option) && ('route' === $task)) {
            return;
        }

        $this->cron_enabled = EParameter::getComponentParam(CAUTOTWEETNG, 'cron_enabled', false);

        if ($this->cron_enabled) {
            return;
        }

        $automators = XTF0FModel::getTmpInstance('Automators', 'AutoTweetModel');

        if (!$automators->lastRunCheck('automator', $this->interval)) {
            return;
        }

        $logger = AutotweetLogger::getInstance();

        // Bot/crawler detection
        $http_user_agent = ($_SERVER['HTTP_USER_AGENT'] ?? null);
        $remote_addr = ($_SERVER['REMOTE_ADDR'] ?? null);

        if ((0 < $this->detect_bots) && ($this->detectCrawlerByAgent($http_user_agent) || $this->detectCrawlerByIP($remote_addr))) {
            $logger->log(\Joomla\CMS\Log\Log::WARNING, 'AutoTweet NG Automator-Plugin - crawler detected. IP: '.$remote_addr.', Agent: '.$http_user_agent);

            return;
        }

        $logger->log(\Joomla\CMS\Log\Log::INFO, 'AutoTweet NG Automator-Plugin - executed - IP: '.$remote_addr.', Agent: '.$http_user_agent);

        define('AUTOTWEET_AUTOMATOR_RUNNING', true);

        PostShareManager::postQueuedMessages($this->max_posts);

        if (EParameter::getComponentParam(CAUTOTWEETNG, 'feeds_enabled', false)) {
            FeedLoaderHelper::importFeeds();
        }
    }

    /**
     * detectCrawlerByAgent.
     *
     * @param string $userAgent param
     *
     * @return string
     */
    private function detectCrawlerByAgent($userAgent)
    {
        $crawlers = $this->crawlers.'|'.$this->crawler_patterns;
        $additional_crawlers = trim($this->additional_crawlers);

        if (!empty($additional_crawlers)) {
            $c = str_replace(',', '|', $additional_crawlers);
            $crawlers = $crawlers.'|'.$c;
        }

        return preg_match("/${crawlers}/i", $userAgent) > 0;
    }

    /**
     * detectCrawlerByIP.
     *
     * @param string $userIP param
     *
     * @return bool
     */
    private function detectCrawlerByIP($userIP)
    {
        $result = false;
        $blocked_ips = trim($this->blocked_ips);

        if (!empty($blocked_ips)) {
            $ip_list = str_replace(',', '|', $blocked_ips);
            $result = (preg_match("/${ip_list}/", $userIP) > 0);
        }

        return $result;
    }
}
