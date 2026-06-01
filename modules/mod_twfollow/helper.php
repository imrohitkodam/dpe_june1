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
use Joomla\CMS\Version;

/**
 * modtwfollowHelper class.
 *
 * @since       1.0
 */
class ModTwfollowHelper
{
    public const TWITTER_PATH = 'https://twitter.com/';

    /**
     * getTwitterData.
     *
     * @param array $params the module options
     */
    public static function getTwitterData($params)
    {
        $consumer_key = $params->get('consumer_key');
        $consumer_secret = $params->get('consumer_secret');
        $access_token = $params->get('access_token');
        $access_token_secret = $params->get('access_token_secret');

        $twUsername = $params->get('twUsername', '');
        $twMaxTweets = (int) $params->get('twMaxTweets', 5);
        $twEnableCache = (int) $params->get('twEnableCache', 1);

        $result = null;

        // Access to twitter via twitter service
        if (!empty($twUsername)) {
            $timeline = null;

            try {
                // Caching
                if ($twEnableCache) {
                    $version = new Version();

                    if (version_compare($version->getShortVersion(), '1.7', '>=')) {
                        // Joomla 1.7 or higher: interval is now in minutes!
                        $twCacheTime = (int) $params->get('twCacheTime', 10);
                    } else {
                        $twCacheTime = (int) $params->get('twCacheTime', 10) * 60;
                    }

                    $cache = \Joomla\CMS\Factory::getCache('TWFollow');
                    $cache->setCaching(true);
                    $cache->setLifeTime($twCacheTime);
                    $timeline = $cache->get(
                        [
                            'ModTwfollowHelper',
                            'getTimeline',
                        ],
                        [
                            $consumer_key,
                            $consumer_secret,
                            $access_token,
                            $access_token_secret,
                            $twUsername,
                            $twMaxTweets,
                        ]
                    );
                } else {
                    $timeline = self::getTimeline($consumer_key, $consumer_secret, $access_token, $access_token_secret, $twUsername, $twMaxTweets);
                }
            } catch (Exception $e) {
                echo $e->getMessage();
                AutotweetLogger::getInstance()->log(\Joomla\CMS\Log\Log::ERROR, $e->getMessage());

                return null;
            }

            // Generate html output
            if (!empty($timeline)) {
                // User info
                $result = [
                    'follow_link' => self::TWITTER_PATH.$twUsername,
                    'timeline' => $timeline,
                ];
            } else {
                if ($twEnableCache) {
                    $cache->clean('TWFollow');
                }
            }
        }

        return $result;
    }

    /**
     * getTimeline.
     *
     * @param string $consumer_key        Param
     * @param string $consumer_secret     Param
     * @param string $access_token        Param
     * @param string $access_token_secret Param
     * @param string $twUsername          Param
     * @param int    $twMaxTweets         Param
     */
    public static function getTimeline($consumer_key, $consumer_secret, $access_token, $access_token_secret, $twUsername, $twMaxTweets)
    {
        $channel = [];

        $appHelper = new TwAppHelper($consumer_key, $consumer_secret, $access_token, $access_token_secret);
        $response = $appHelper->getUserTimeline($twUsername, $twMaxTweets);

        // If response is false or empty, twitter is not available or the profile is protected
        if (($response) && (!empty($response)) && (is_array($response))) {
            $first_status = $response[0];

            // Initialize array for channel data
            $channel = [
                'screen_name' => $first_status->user->screen_name,
                'profile_image_url' => $first_status->user->profile_image_url,
                'tweets' => [],
            ];

            // Get and save entrys
            foreach ($response as $tweet) {
                $text = (!empty($tweet->text)) ? $tweet->text : '';
                $created_at = (!empty($tweet->created_at)) ? $tweet->created_at : '';
                $channel['tweets'][] = [
                    'text' => (string) $text,
                    'created_at' => (string) $created_at,
                ];
            }
        }

        return $channel;
    }
}
