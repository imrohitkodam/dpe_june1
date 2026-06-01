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
final class SharingHelper
{
    // Seconds
    public const RESEND_DELAY = 1;

    // Max chars for title, introtext, and fulltext (to avoid database and other errors
    public const MAX_CHARS_TITLE = 2560;
    public const MAX_CHARS_INTROTEXT = 2560;
    public const MAX_CHARS_FULLTEXT = 5120;

    // Cron job mode
    protected $cron_enabled = 0;

    // Duplicate post detection
    protected $dpcheck_enabled = 1;

    // Banned words check
    protected $bannedwordscheck_enabled = 1;

    protected $banned_words = '';

    // 3 hours
    protected $dpcheck_time_intval = 10800;

    protected $shorturl_always = 1;

    protected $resend_attempts = 2;

    // Logging
    protected $logger;

    protected $routeHelp;

    protected $current_short_url = [];

    // Deny All Rule Mode
    protected $denyall_rulemode = 0;

    private static $_instance = null;

    /**
     * SharingHelper.
     */
    protected function __construct()
    {
        // Cron job mode
        $this->cron_enabled = EParameter::getComponentParam(CAUTOTWEETNG, 'cron_enabled', 0);

        // Duplicate post detection
        $this->dpcheck_enabled = EParameter::getComponentParam(CAUTOTWEETNG, 'dpcheck_enabled', 1);

        // Banned Words check
        $this->banned_words = EParameter::getComponentParam(CAUTOTWEETNG, 'banned_words', '');
        $this->bannedwordscheck_enabled = !empty($this->banned_words);

        if ($this->bannedwordscheck_enabled) {
            $this->banned_words = str_replace(',', '|', $this->banned_words);
        }

        // Hours to seconds
        $this->dpcheck_time_intval = EParameter::getComponentParam(CAUTOTWEETNG, 'dpcheck_time_intval', 12) * 3600;

        $this->shorturl_always = EParameter::getComponentParam(CAUTOTWEETNG, 'shorturl_always', 1);

        $this->resend_attempts = EParameter::getComponentParam(CAUTOTWEETNG, 'resend_attempts', 2);

        // Deny All Rule Mode
        $this->denyall_rulemode = EParameter::getComponentParam(CAUTOTWEETNG, 'denyall_rulemode', 0);

        // Init AutoTweet logging
        $this->logger = AutotweetLogger::getInstance();
    }

    /**
     * getInstance.
     *
     * @return Instance
     */
    public static function &getInstance()
    {
        if (!self::$_instance) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * publishRequest.
     *
     * @param object $request Param
     * @param object $userid  Param
     *
     * @return bool
     */
    public function publishRequest($request, $userid = null)
    {
        $this->logger->log(\Joomla\CMS\Log\Log::INFO, 'publishRequest request', $request->description);

        // Default: generate new entry for repost of entry with state success
        $postid = 0;
        $data = $this->_getContentData($request);

        if (!$data) {
            return false;
        }

        // Convert array to object
        $json = json_encode($data);
        $post = json_decode($json);

        // Weird PHP 5.3 inconsistencies
        if ((null === $post->text) && (!empty($data['text']))) {
            $post->text = $data['text'];
        }

        if (PERFECT_PUB_PRO) {
            $params = AdvancedAttributesHelper::getByRequest($request->id);
        }

        $post->ref_id = $request->ref_id;
        $post->plugin = $request->plugin;
        $post->priority = $request->priority;
        $post->postdate = $request->publish_up;
        $post->message = $post->text;
        unset($post->text);

        // Url
        if ((isset($post->url)) && (!empty($post->url))) {
            $routeHelp = RouteHelp::getInstance();
            $url = $routeHelp->getAbsoluteUrl($post->url);
            $post->url = $url;
        } elseif ((isset($request->url)) && (!empty($request->url))) {
            $post->url = $request->url;
        } else {
            $this->logger->log(\Joomla\CMS\Log\Log::INFO, 'publishRequest: No url');
            $post->url = '';
        }

        $this->logger->log(\Joomla\CMS\Log\Log::INFO, 'publishRequest: url = '.$post->url);

        // Image url
        if ((isset($post->image_url)) && (!empty($post->image_url))) {
            // If defined in getExtendedData, use this image_url
            $routeHelp = RouteHelp::getInstance();
            $url = $routeHelp->getAbsoluteUrl($post->image_url, true);

            // Only if it's a valid Url
            if (!ImageUtil::getInstance()->isValidImageUrl($url)) {
                $url = null;
            }

            $post->image_url = $url;
        } elseif ((isset($request->image_url)) && (!empty($request->image_url))) {
            $url = $request->image_url;

            // Only if it's a valid Url
            if (!ImageUtil::getInstance()->isValidImageUrl($url)) {
                $url = null;
            }

            // Use this image_url (it's already routed)
            $post->image_url = $url;
        } else {
            $this->logger->log(\Joomla\CMS\Log\Log::INFO, 'publishRequest: No image url');
            $post->image_url = null;
        }

        $this->logger->log(\Joomla\CMS\Log\Log::INFO, 'publishRequest: image url = '.$post->image_url);

        // Title
        // Truncate title and fulltext for new messages ('if' is for backward compatibillity)
        if (isset($post->title)) {
            $title = TextUtil::cleanText($post->title);
            $post->title = TextUtil::truncString($title, self::MAX_CHARS_TITLE);
        } else {
            $post->title = '';
        }

        // Fulltext
        if (!isset($post->fulltext)) {
            $post->fulltext = '';
        }

        if (PERFECT_PUB_PRO) {
            if ((isset($params->fulltext)) && (!empty($params->fulltext))) {
                $post->fulltext = $params->fulltext;
            }
        }

        if ((PERFECT_PUB_PRO)
            && (EParameter::getComponentParam(CAUTOTWEETNG, 'paywall_mode'))
            && ($fulltext = EParameter::getComponentParam(CAUTOTWEETNG, 'paywall_titleonly'))) {
            $post->fulltext = $fulltext;
        }

        $fulltext = TextUtil::cleanText($post->fulltext);
        $post->fulltext = TextUtil::truncString($fulltext, self::MAX_CHARS_FULLTEXT);

        $post->xtform = new JRegistry();

        // Catids
        if (isset($post->catids)) {
            $catids = $post->catids;
            unset($post->catids);
            $post->xtform->set('catids', $catids);
        } else {
            $post->xtform->set('catids', []);
        }

        // Author
        if (isset($post->author)) {
            $author = $post->author;
            unset($post->author);
            $post->xtform->set('author', $author);
        }

        // Language
        if (isset($post->language)) {
            $language = $post->language;
            unset($post->language);
            $post->xtform->set('language', $language);
        }

        // Access
        if (isset($post->access)) {
            $access = $post->access;
            unset($post->access);
            $post->xtform->set('access', $access);
        }

        // Hashtags
        if (isset($post->hashtags)) {
            $hashtags = $post->hashtags;
            unset($post->hashtags);
            $post->xtform->set('hashtags', $hashtags);
        }

        // Native object
        if (isset($request->native_object)) {
            $native_object = TextUtil::json_decode($request->native_object);

            if ($native_object) {
                $post->xtform->set('native_object', $native_object);
            } else {
                $this->logger->log(\Joomla\CMS\Log\Log::INFO, 'publishRequest: Inavlid JSON native_object');
            }
        }

        // Featured
        if (isset($post->featured)) {
            $post->xtform->set('featured', $post->featured);
            unset($post->featured);
        }

        $post->xtform->set('req_id_src', $request->id);

        if (($request->xtform->get('feed_generated'))) {
            $post->xtform->set('feed_generated', true);
        }

        // Evergreen generated
        if ((PERFECT_PUB_PRO) && ($request->xtform->get('evergreen_generated'))) {
            $post->xtform->set('evergreen_generated', true);
        }

        // Immediate generated
        if ((PERFECT_PUB_PRO) && (isset($params->postthis)) && (PostShareManager::POSTTHIS_IMMEDIATELY === (int) $params->postthis)) {
            $post->xtform->set('is_immediate', true);
        }

        return $this->sendRequest($request, $post, $userid);
    }

    /**
     * _getContentData.
     *
     * @param object &$request Param
     *
     * @return data
     */
    public function _getContentData(&$request)
    {
        $this->logger->log(\Joomla\CMS\Log\Log::INFO, '_getContentData', $request->description);

        // Get source plugin for message
        // Gets the plugin that has triggered the message
        $pluginsModel = XTF0FModel::getTmpInstance('Plugins', 'AutoTweetModel');
        $plugin = $pluginsModel->createPlugin($request->plugin);

        if (empty($plugin)) {
            $this->logger->log(\Joomla\CMS\Log\Log::WARNING, '_getContentData: Unknown plugin. Source: '.$request->plugin);
            $post = $request;
        } else {
            $plugin->setMessage($request->description);

            $request->xtform = EForm::paramsToRegistry($request);
            $plugin->setHashtags($request->xtform->get('hashtags'));

            // Get data from plugin
            if (method_exists($plugin, 'getExtendedData')) {
                if (!isset($request->native_object) && isset($request->params)) {
                    $request->native_object = $request->params;
                }

                $data = $plugin->getExtendedData($request->ref_id, $request->typeinfo, $request->native_object);
            } else {
                $data = $plugin->getData($request->ref_id, $request->typeinfo);
            }
        }

        // Check if post is valid to avoid spam; if not remove post from queue
        if (empty($data) || !array_key_exists('is_valid', $data) || (!$data['is_valid'])) {
            $this->logger->log(\Joomla\CMS\Log\Log::WARNING, '_getContentData: Message is not valid or item is not ready to be published. Queue id = '.$request->id);
            RequestHelper::saveError($request->id, 'COM_AUTOTWEET_ERROR_PUBLISHREQUEST');

            return null;
        }

        $data['autopublish'] = $plugin->isAutopublish();
        $data['show_url'] = $plugin->getShowUrlMode();

        return $data;
    }

    /**
     * publishPost.
     *
     * @param object $post   Param
     * @param object $userid Param
     *
     * @return bool
     */
    public function publishPost($post, $userid = null)
    {
        // Where are here, so ...
        $post->autopublish = true;

        // Decode event data back to array
        $post->event = json_decode($post->event_data, true);

        return $this->sendPost($post, $userid);
    }

    /**
     * sendRequest.
     *
     * @param object &$request Param
     * @param object &$post    Param
     * @param object $userid   Param
     *
     * @return bool
     */
    protected function sendRequest(&$request, &$post, $userid = null)
    {
        $success = false;
        $this->logger->log(\Joomla\CMS\Log\Log::INFO, 'sendRequest request', $request->description);
        $this->logger->log(\Joomla\CMS\Log\Log::INFO, 'sendRequest post', $post->message);

        $rule_engine = RuleEngineHelper::getInstance();

        $plugin = $request->plugin;
        $rule_engine->load($plugin);

        // Channels - Rules
        $channel_rules = $rule_engine->getChannels($request->plugin, $post);
        $hasRules = !empty($channel_rules);

        if ($hasRules) {
            $channel_rules_ids = array_keys($channel_rules);
            $this->logger->log(\Joomla\CMS\Log\Log::INFO, 'getChannels:Rules found for plugin '.$plugin.' n='.count($channel_rules));
        } else {
            $this->logger->log(\Joomla\CMS\Log\Log::INFO, 'getChannels: No rules found for plugin '.$plugin);
            $channel_rules_ids = [];
        }

        $channels = ChannelFactory::getInstance()->getChannels();

        if (PERFECT_PUB_PRO) {
            $params = AdvancedAttributesHelper::getByRequest($request->id);

            if ((isset($params->channels)) && (is_array($params->channels)) && (count($params->channels) > 0)) {
                $this->logger->log(\Joomla\CMS\Log\Log::INFO, 'Channels filter by req '.$request->id, $params->channels);

                $filtered_channels = [];

                foreach ($params->channels as $c) {
                    if (array_key_exists($c, $channels)) {
                        $filtered_channels[$c] = $channels[$c];
                    }
                }

                $filteredChannelsKeys = array_keys($filtered_channels);
                $this->logger->log(\Joomla\CMS\Log\Log::INFO, 'Filtered channels ', $filteredChannelsKeys);

                $channels = $filtered_channels;
            }
        }

        if ($this->denyall_rulemode) {
            $this->logger->log(\Joomla\CMS\Log\Log::INFO, 'sendRequest denyall_rulemode');

            // Only rule channels are processed
            $remaining_channels_ids = [];
            $success = true;
        } else {
            // Rest of the Channels
            $channels_ids = array_keys($channels);
            $remaining_channels_ids = array_diff($channels_ids, $channel_rules_ids);
        }

        // Save orginal url for log and other usages
        $post->org_url = $post->url;

        // A request for each Channel - Rule
        $result_msg = '';
        $initial_autopublish_state = $post->autopublish;
        $initial_show_url_state = $post->show_url;

        $this->logger->log(\Joomla\CMS\Log\Log::INFO, 'sendRequest channel_rules', $channel_rules_ids);
        $this->logger->log(\Joomla\CMS\Log\Log::INFO, 'sendRequest remaining_channels_ids', $remaining_channels_ids);

        foreach ($channel_rules as $channel_id => $rule) {
            $this->logger->log(\Joomla\CMS\Log\Log::INFO, 'sendRequest channel_rules processing: '.$channel_id);

            // There's a rule, but the channel is not enabled
            if (!isset($channels[$channel_id])) {
                continue;
            }

            $channel = $channels[$channel_id];
            $channelpost = clone $post;

            $channelpost->id = 0;
            $channelpost->channel_id = $channel_id;
            $channelpost->autopublish = ($channel->isAutopublish() && $initial_autopublish_state);
            $channelpost->show_url = $initial_show_url_state;

            $rule_engine->executeRule($rule, $channel, $channelpost);

            $success = $this->_sendRequest($channel, $channelpost);

            // If one channel fails, it's stopped
            if (!$success) {
                $this->logger->log(\Joomla\CMS\Log\Log::ERROR, 'sendRequest: failed, stopping process (1).');

                return false;
            }
        }

        // A request for each of the remaining Channels
        foreach ($remaining_channels_ids as $channel_id) {
            $this->logger->log(\Joomla\CMS\Log\Log::INFO, 'sendRequest remaining_channels_ids processing: '.$channel_id);

            $channel = $channels[$channel_id];

            $post->id = 0;
            $post->channel_id = $channel_id;
            $post->autopublish = ($channel->isAutopublish() && $initial_autopublish_state);
            $post->show_url = $initial_show_url_state;

            $success = $this->_sendRequest($channel, $post, $userid);

            // If one channel fails, it's stopped
            if (!$success) {
                $this->logger->log(\Joomla\CMS\Log\Log::ERROR, 'sendRequest: failed, stopping process (2).');

                return false;
            }
        }

        $success = true;
        $this->logger->log(\Joomla\CMS\Log\Log::INFO, 'sendRequest: success, no more channels to process.');

        // True when message is sent
        return $success;
    }

    /**
     * _sendRequest.
     *
     * @param string &$channel Param
     * @param object $post     Param
     * @param object $userid   Param
     *
     * @return bool
     */
    protected function _sendRequest(&$channel, $post, $userid = null)
    {
        $nextstate = null;

        if (isset($post->nextstate)) {
            $nextstate = $post->nextstate;
            unset($post->nextstate);
        }

        // Send new post when autopublish is enabled and cron is disabled
        $send_now = ($post->autopublish && !$this->cron_enabled);

        // Sending is allowed, when autopublish for channel and plugin is allowed or post is approved from backend (and not in cron modeI)
        if ($send_now) {
            $response = $this->sharePost($channel, $post, $userid);
            $state = $response['state'];
            $result_msg = $response['result_msg'];
        } else {
            if (!$post->autopublish) {
                if ('cancel' === $nextstate) {
                    // Cancelled
                    $state = PostShareManager::POST_CANCELLED;
                    $result_msg = 'COM_AUTOTWEET_MSG_POSTRESULT_CANCELLED';
                } else {
                    // Approval needed
                    $state = PostShareManager::POST_APPROVE;
                    $result_msg = 'COM_AUTOTWEET_MSG_POSTRESULT_APPROVE';
                }
            } elseif ($this->cron_enabled) {
                // Cron mode enabled
                $state = PostShareManager::POST_CRONJOB;
                $result_msg = 'COM_AUTOTWEET_MSG_POSTRESULT_CRONJOB';
            } else {
                // ERROR: This sould NOT happen!
                $state = PostShareManager::POST_ERROR;
                $result_msg = 'COM_AUTOTWEET_MSG_POSTRESULT_ERROR';
            }
        }

        // Store message in log
        return PostHelper::savePost($state, $result_msg, $post, $userid, $post->url);
    }

    /**
     * sendPost.
     *
     * @param object &$post  Param
     * @param int    $userid Param
     *
     * @return bool
     */
    protected function sendPost(&$post, $userid = null)
    {
        $this->logger->log(\Joomla\CMS\Log\Log::INFO, 'sendPost: postid = '.$post->id);

        // Post for specific channel
        $channel = ChannelFactory::getInstance()->getChannel($post->channel_id);

        if (!$channel) {
            PostHelper::savePost(PostShareManager::POST_ERROR, 'COM_AUTOTWEET_ERR_CHANNEL_NOTFOUND', $post, $userid, $post->url);

            return false;
        }

        $response = $this->sharePost($channel, $post, $userid);

        // Store message in log
        return PostHelper::savePost($response['state'], $response['result_msg'], $post, $userid, $post->url);
    }

    /**
     * sharePost.
     *
     * @param object &$channel Param
     * @param object &$post    Param
     * @param int    $userid   Param
     *
     * @return array
     */
    protected function sharePost(&$channel, &$post, $userid = null)
    {
        // Check for duplicate post
        if ($this->dpcheck_enabled) {
            $isDuplicated = PostHelper::isDuplicatedPost($post->id, $post->ref_id, $post->plugin, $post->channel_id, $post->message, $this->dpcheck_time_intval);

            if ($isDuplicated) {
                $this->logger->log(
                    \Joomla\CMS\Log\Log::INFO,
                    'sharePost: duplicated post, Item ID = '
                    .$post->ref_id.', plugin = '.$post->plugin.', interval = '.$this->dpcheck_time_intval
                );

                return [
                    'state' => PostShareManager::POST_ERROR,
                    'result_msg' => 'COM_AUTOTWEET_ERROR_DUPLICATED',
                ];
            }
        }

        // Check for banned post
        if ($this->bannedwordscheck_enabled) {
            $isBanned = PostHelper::isBannedPost($post->message, $this->banned_words);

            if ($isBanned) {
                $this->logger->log(
                    \Joomla\CMS\Log\Log::INFO,
                    'sharePost: banned post, Item ID = '
                    .$post->ref_id.', plugin = '.$post->plugin
                );

                return [
                    'state' => PostShareManager::POST_ERROR,
                    'result_msg' => 'COM_AUTOTWEET_ERROR_BANNED',
                ];
            }
        }

        // Get short url one time and if needed only (better performance)
        if ((PostShareManager::SHOWURL_OFF !== $post->show_url)
            && (!empty($post->org_url))
            && (!array_key_exists($post->org_url, $this->current_short_url))) {
            $shorturlHelper = ShorturlHelper::getInstance();
            $this->current_short_url[$post->org_url] = $shorturlHelper->getShortUrl($post->org_url);
        }

        $current_short_url = null;

        if (array_key_exists($post->org_url, $this->current_short_url)) {
            $current_short_url = $this->current_short_url[$post->org_url];
        }

        // Construct url and truncate message, if necessary
        $finalUrlMessage = TextUtil::getMessageWithUrl($channel, $post, $current_short_url, $this->shorturl_always);

        if ($post->fulltext) {
            $post->fulltext = TextUtil::generateCr($post->fulltext, '. ');
        }

        // Switch original url to short url to use short url also for all other links
        $post->url = $finalUrlMessage['url'];
        $message = $finalUrlMessage['message'];

        // Just in case we want to repeat the message
        // $post->message = $message;

        return $this->sharePostChannel($message, $channel, $post);
    }

    /**
     * sharePostChannel.
     *
     * @param string $message  Param
     * @param object &$channel Param
     * @param object &$post    Param
     *
     * @return array
     */
    protected function sharePostChannel($message, &$channel, &$post)
    {
        // Send message
        $attempt = 0;
        $resend_attempts = $this->resend_attempts;

        $response = [
            'state' => PostShareManager::POST_ERROR,
            'result_msg' => 'COM_AUTOTWEET_ERR_CHANNEL_UNPROCESSED',
        ];

        // Post message for channel; multiple attempts if needed
        do {
            $resend = false;
            ++$attempt;

            $this->logger->log(
                \Joomla\CMS\Log\Log::INFO,
                "sharePostChannel - Message ({$attempt} / {$resend_attempts}): ",
                $message
            );

            // Post to channel, et the result code
            $result_status = $channel->sendMessage($message, $post);

            if (($attempt < $this->resend_attempts) && !$result_status[0]) {
                $resend = true;

                $this->logger->log(\Joomla\CMS\Log\Log::WARNING, 'sendRequest: '.$channel->getChannelType().':'.$channel->getChannelName().' - error code = '.$result_status[1].' - sending message again in '.self::RESEND_DELAY.' seconds');

                sleep(self::RESEND_DELAY);
            }
        } while ($resend);

        $result_msg = $result_status[1];
        $response['result_msg'] = $result_msg;

        if ($result_status[0]) {
            $response['state'] = PostShareManager::POST_SUCCESS;
            $this->logger->log(\Joomla\CMS\Log\Log::INFO, 'sendRequest: '.$channel->getChannelType().':'.$channel->getChannelName().' - status has been updated, ref_id = '.$post->ref_id.'  (attempts: '.$attempt.')');
        } else {
            $response['state'] = PostShareManager::POST_ERROR;

            $this->logger->log(\Joomla\CMS\Log\Log::ERROR, 'sendRequest: error when sending message to '.$channel->getChannelType().':'.$channel->getChannelName().', - ref_id = '.$post->ref_id.', return code = '.$result_status[1]);

            TextUtil::adminNotification($channel->getChannelName(), $result_msg, $post);
        }

        return $response;
    }
}
