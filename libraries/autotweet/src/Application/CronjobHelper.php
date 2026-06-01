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
 * @since       2.5
 */
final class CronjobHelper
{
    protected static $instance = null;

    protected $cron_enabled = 0;

    // Posts per job
    protected $max_posts = 1;

    /**
     * Run the job.
     */
    protected function __construct()
    {
        // Cronjob params
        $this->cron_enabled = EParameter::getComponentParam(CAUTOTWEETNG, 'cron_enabled', false);
        $this->max_posts = EParameter::getComponentParam(CAUTOTWEETNG, 'max_posts', 1);
    }

    /**
     * getInstance.
     *
     * @return object
     */
    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * postMessages.
     */
    public function publishPosts()
    {
        if ($this->cron_enabled) {
            if (PERFECT_PUB_PRO) {
                $now = \Joomla\CMS\Factory::getDate();

                if (VirtualManager::isWorking($now)) {
                    PostHelper::publishCronjobPosts($this->max_posts);
                } else {
                    $logger = AutotweetLogger::getInstance();
                    $logger->log(\Joomla\CMS\Log\Log::INFO, 'CronjobHelper::publishPosts - VM not working now '.$now->toISO8601(true));
                }
            } else {
                PostHelper::publishCronjobPosts($this->max_posts);
            }
        }
    }

    /**
     * contentPolling.
     */
    public function contentPolling()
    {
        $this->executePlgContentPolling('autotweetcontent');
        $this->executePlgContentPolling('autotweetk2');
        $this->executePlgContentPolling('autotweetohanah');
    }

    /**
     * executePlgContentPolling.
     *
     * @param string $pluginName Param
     */
    private function executePlgContentPolling($pluginName)
    {
        $plugin = \XTP_BUILD\Extly\Infrastructure\Service\Cms\Joomla\DispatcherHelper::getPlugin(
            'system',
            $pluginName
        );

        if (empty($plugin)) {
            return;
        }

        $plugin->onContentPolling();
    }
}
