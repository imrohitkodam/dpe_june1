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
 * Rule engine handles publushing rules.
 *
 * @since       1.0
 */
final class RuleEngineHelper
{
    // Rule types
    public const CATEGORY_IN = 1;
    public const CATEGORY_NOTIN = 2;
    public const TERM_OR = 3;
    public const TERM_AND = 4;
    public const CATCH_ALL_NOTFITS = 5;
    public const WORDTERM_OR = 6;
    public const WORDTERM_AND = 7;
    public const REG_EXPR = 8;
    public const TERM_NOTIN = 9;
    public const WORDTERM_NOTIN = 10;
    public const AUTHOR_IN = 11;
    public const AUTHOR_NOTIN = 12;
    public const CATCH_ALL = 13;
    public const LANGUAGE_IN = 14;
    public const LANGUAGE_NOTIN = 15;
    public const ACCESS_IN = 16;
    public const ACCESS_NOTIN = 17;
    public const AUTHORGROUP_IN = 22;
    public const AUTHORGROUP_NOTIN = 23;
    public const FEATURED_IS = 24;
    public const FEATURED_ISNOT = 25;
    public const MEDIA_HAS = 26;
    public const MEDIA_HASNOT = 27;
    public const EVERGREEN_IS = 28;
    public const EVERGREEN_ISNOT = 29;
    public const TAGS_IN = 30;
    public const TAGS_NOTIN = 31;

    // Joocial types
    public const CHANNEL_OWNERGROUP_IN = 20;
    public const CHANNEL_OWNERGROUP_NOTIN = 21;

    public const REGISTERED_GROUP = 2;

    // Token separators
    public const TOKEN_DELIMITER = " ,.;!?\n\t\"'\\/";

    // Rule params
    public const RULE_AUTOPUBLISH = 'autopublish';
    public const RULE_SHOW_URL = 'show_url';
    public const RULE_RMC_TEXTPATTERN = 'rmc_textpattern';
    public const RULE_SHOW_STATIC_TEXT = 'show_static_text';
    public const RULE_STATIC_TEXT = 'statix_text';
    public const RULE_REG_EX = 'reg_ex';
    public const RULE_REG_REPLACE = 'reg_replace';

    private $rules = [];

    private static $instance;

    /**
     * getInstance.
     *
     * @return object
     */
    public static function &getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * init.
     *
     * @param string $plugin_name Params
     */
    public function load($plugin_name)
    {
        $requestsModel = XTF0FModel::getTmpInstance('Rules', 'AutoTweetModel');
        $requestsModel->set('plugin', $plugin_name);
        $requestsModel->set('published', 1);
        $requestsModel->set('filter_order', 'ordering');
        $requestsModel->set('filter_order_Dir', 'ASC');
        $this->rules = $requestsModel->getItemList(true);
    }

    /**
     * getChannels.
     *
     * @param string $plugin Param
     * @param array  &$post  Param
     *
     * @return array
     */
    public function getChannels($plugin, &$post)
    {
        $channels = $this->getAllowedChannels($post);
        $channel_ids = array_keys($channels);

        $logger = AutotweetLogger::getInstance();
        $logger->log(\Joomla\CMS\Log\Log::INFO, 'getChannels allowedChannels', $channel_ids);

        // There's one Universal Channel rule
        if (1 === count($channels)) {
            reset($channels);
            $key = key($channels);
            $rule = $channels[$key];

            // There's one Universal Channel rule CHANNEL-ID = 0
            if (0 === (int) $key) {
                $channels = [];
                $all_channels = ChannelFactory::getInstance()->getChannels();
                $channel_ids = array_keys($all_channels);

                $logger->log(\Joomla\CMS\Log\Log::INFO, 'Universal Channel rule generating: ', $channel_ids);

                // Generate virtual rules
                foreach ($all_channels as $channel_id => $channel) {
                    $channels[$channel_id] = $rule;
                }
            }
        }

        return $channels;
    }

    /**
     * executeRule.
     *
     * @param object &$rule    Params
     * @param object &$channel Params
     * @param object &$post    Params
     */
    public static function executeRule(&$rule, &$channel, &$post)
    {
        // Correct autopublish options when rules engine is used
        $post->nextstate = self::getValue($rule, self::RULE_AUTOPUBLISH);

        switch ($post->nextstate) {
            case 'on':
                $post->autopublish = true;

                break;
            case 'off':
                $post->autopublish = false;

                break;
            case 'cancel':
                $post->autopublish = false;

                break;
            case 'default':
                // Use default value from plugin/channel: do nothing
                break;
        }

        // Correct url link mode options when rules engine is used
        $show_url = self::getValue($rule, self::RULE_SHOW_URL);

        if ('default' !== $show_url) {
            $post->show_url = $show_url;
        }

        // Target_id
        if (!isset($rule->xtform)) {
            $rule->xtform = Eform::paramsToRegistry($rule);
        }

        $message = TextUtil::cleanText($post->message);

        // Create message for new post (logged posts uses existing message text)
        // Filter first full and msgtext if there is an regex in rule
        $rule_reg_ex = self::getValue($rule, self::RULE_REG_EX);

        if (!empty($rule_reg_ex)) {
            $is_json = json_decode($rule_reg_ex);

            if ($is_json) {
                $rule_reg_ex = $is_json;
            }

            $rule_reg_replace = self::getValue($rule, self::RULE_REG_REPLACE);
            $is_json = json_decode($rule_reg_replace);

            if ($is_json) {
                $rule_reg_replace = $is_json;
            }

            $logger = AutotweetLogger::getInstance();

            $newMessage = preg_replace($rule_reg_ex, $rule_reg_replace, preg_quote($message));
            $newMessage = stripslashes($newMessage);

            if (empty($newMessage)) {
                $logger->log(\Joomla\CMS\Log\Log::ERROR, 'executeRule: invalid regular expression: ('.$rule_reg_ex.' , '.$rule_reg_replace.').');
            } else {
                $message = $newMessage;
            }

            $newTitle = preg_replace($rule_reg_ex, $rule_reg_replace, preg_quote($post->title));
            $newTitle = stripslashes($newTitle);

            if (empty($newTitle)) {
                $logger->log(\Joomla\CMS\Log\Log::ERROR, 'executeRule: invalid regular expression: ('.$rule_reg_ex.' , '.$rule_reg_replace.').');
            } else {
                $post->title = $newTitle;
            }

            $newFulltext = preg_replace($rule_reg_ex, $rule_reg_replace, preg_quote($post->fulltext));
            $newFulltext = stripslashes($newFulltext);

            if (!empty($post->fulltext) && empty($newFulltext)) {
                $logger->log(\Joomla\CMS\Log\Log::ERROR, 'executeRule: invalid regular expression: ('.$rule_reg_ex.' , '.$rule_reg_replace.').');
            } else {
                $post->fulltext = $newFulltext;
            }
        }

        $post->message = $message;

        // Apply a custom pattern to the text
        $pattern = self::getValue($rule, self::RULE_RMC_TEXTPATTERN);

        if (!empty($pattern)) {
            AutotweetBaseHelper::applyTextPattern($pattern, $post);
        }

        $message = $post->message;

        // Add static text from rules engine
        $show_static_text = self::getValue($rule, self::RULE_SHOW_STATIC_TEXT);
        $rule_static_text = self::getValue($rule, self::RULE_STATIC_TEXT);
        $message = AutotweetBaseHelper::addStatictext($show_static_text, $message, $rule_static_text);

        $post->message = $message;
    }

    /**
     * getAllowedChannels.
     *
     * @param object &$post Params
     *
     * @return array
     */
    private function getAllowedChannels(&$post)
    {
        $logger = AutotweetLogger::getInstance();
        $logger->log(\Joomla\CMS\Log\Log::INFO, 'getAllowedChannels', $post->message);

        $categories_to_check = $post->xtform->get('catids');
        $text_to_check = $post->message;

        if (isset($post->title)) {
            $text_to_check .= ' '.$post->title;
        }

        if (isset($post->fulltext)) {
            $text_to_check .= ' '.$post->fulltext;
        }

        $author_to_check = $post->xtform->get('author');
        $language_to_check = $post->xtform->get('language');
        $access_to_check = $post->xtform->get('access');
        $featured_to_check = $post->xtform->get('featured');
        $evergreen_to_check = $post->xtform->get('evergreen_generated');
        $has_media = !empty($post->image_url);

        $allowed_channels = [];
        $catch_all_channels = [];

        $this->fillUniversalRules($this->rules);

        $logger->log(\Joomla\CMS\Log\Log::INFO, 'getAllowedChannels rules n='.count($this->rules));

        foreach ($this->rules as $rule) {
            $logger->log(\Joomla\CMS\Log\Log::INFO, 'getAllowedChannels Rule ('.$rule->id.', '.$rule->ruletype_id.')');

            if (!isset($rule->xtform)) {
                $rule->xtform = Eform::paramsToRegistry($rule);
            }

            if (self::REG_EXPR === (int) $rule->ruletype_id) {
                $condition = $rule->cond;
            } else {
                $condition = TextUtil::listToArray(trim($rule->cond));
            }

            try {
                $channel = ChannelFactory::getInstance()->getChannel($rule->channel_id);

                if (!$channel) {
                    continue;
                }
            } catch (Exception $e) {
                $logger->log(\Joomla\CMS\Log\Log::INFO, 'getAllowedChannels failed to load channel id '.$rule->channel_id);

                continue;
            }

            $created_by = $channel->getField('created_by');
            $user = \Joomla\CMS\Factory::getUser($created_by);
            $authorisedGroups = $user->getAuthorisedGroups();

            if (($rule->xtform->get('only_featured')) && (!$featured_to_check)) {
                continue;
            }

            $hasMatched = false;

            switch ($rule->ruletype_id) {
                case self::CATEGORY_IN:
                    $matched = array_intersect($condition, $categories_to_check);

                    if (!empty($matched) && !array_key_exists($rule->channel_id, $allowed_channels)) {
                        $hasMatched = true;
                    }

                    break;
                case self::CATEGORY_NOTIN:
                    $matched = array_intersect($condition, $categories_to_check);

                    if (empty($matched) && !array_key_exists($rule->channel_id, $allowed_channels)) {
                        $hasMatched = true;
                    }

                    break;
                case self::TERM_OR:
                    $matched = '';

                    foreach ($condition as $term) {
                        $matched = stristr($text_to_check, $term);

                        if (!empty($matched)) {
                            break;
                        }
                    }

                    if (!empty($matched) && !array_key_exists($rule->channel_id, $allowed_channels)) {
                        $hasMatched = true;
                    }

                    break;
                case self::TERM_AND:
                    $matched = '';

                    foreach ($condition as $term) {
                        $matched = stristr($text_to_check, $term);

                        if (empty($matched)) {
                            break;
                        }
                    }

                    if (!empty($matched) && !array_key_exists($rule->channel_id, $allowed_channels)) {
                        $hasMatched = true;
                    }

                    break;
                case self::TERM_NOTIN:
                    $matched = '';

                    foreach ($condition as $term) {
                        $matched = stristr($text_to_check, $term);

                        if (!empty($matched)) {
                            break;
                        }
                    }

                    if (empty($matched) && !array_key_exists($rule->channel_id, $allowed_channels)) {
                        $hasMatched = true;
                    }

                    break;
                case self::WORDTERM_OR:
                    $matched = '';
                    $token = strtok($text_to_check, self::TOKEN_DELIMITER);

                    while (false !== $token) {
                        $matched = in_array(trim($token), $condition, true);

                        if (!empty($matched)) {
                            break;
                        }

                        $token = strtok(self::TOKEN_DELIMITER);
                    }

                    if (!empty($matched) && !array_key_exists($rule->channel_id, $allowed_channels)) {
                        $hasMatched = true;
                    }

                    break;
                case self::WORDTERM_AND:
                    $matched = '';
                    $text = [];
                    $token = strtok($text_to_check, self::TOKEN_DELIMITER);

                    while (false !== $token) {
                        $text[] = trim($token);
                        $token = strtok(self::TOKEN_DELIMITER);
                    }

                    foreach ($condition as $term) {
                        $matched = in_array($term, $text, true);

                        if (empty($matched)) {
                            break;
                        }
                    }

                    if (!empty($matched) && !array_key_exists($rule->channel_id, $allowed_channels)) {
                        $hasMatched = true;
                    }

                    break;
                case self::WORDTERM_NOTIN:
                    $matched = '';
                    $text = [];
                    $token = strtok($text_to_check, self::TOKEN_DELIMITER);

                    while (false !== $token) {
                        $text[] = trim($token);
                        $token = strtok(self::TOKEN_DELIMITER);
                    }

                    foreach ($condition as $term) {
                        $matched = in_array($term, $text, true);

                        if (!empty($matched)) {
                            break;
                        }
                    }

                    if (empty($matched) && !array_key_exists($rule->channel_id, $allowed_channels)) {
                        $hasMatched = true;
                    }

                    break;
                case self::AUTHOR_IN:
                    $matched = '';

                    foreach ($condition as $term) {
                        // Take care: strcmp returns 0 if strings are matching!
                        $matched = strcmp(trim($author_to_check), trim($term));

                        if (0 === (int) $matched) {
                            break;
                        }
                    }

                    if ((0 === (int) $matched) && !array_key_exists($rule->channel_id, $allowed_channels)) {
                        $hasMatched = true;
                    }

                    break;
                case self::AUTHOR_NOTIN:
                    $matched = '';

                    foreach ($condition as $term) {
                        // Take care: strcmp returns 0 if strings are matching!
                        $matched = strcmp(trim($author_to_check), trim($term));

                        if (0 === (int) $matched) {
                            break;
                        }
                    }

                    if ((0 !== (int) $matched) && !array_key_exists($rule->channel_id, $allowed_channels)) {
                        $hasMatched = true;
                    }

                    break;
                case self::AUTHORGROUP_IN:
                    $user_id = \Joomla\CMS\User\UserHelper::getUserId($author_to_check);
                    $user = \Joomla\CMS\Factory::getUser($user_id);
                    $matched = array_intersect($user->groups, $condition);
                    $matched = !empty($matched);

                    if (($matched) && (!array_key_exists($rule->channel_id, $allowed_channels))) {
                        $hasMatched = true;
                    }

                    break;
                case self::AUTHORGROUP_NOTIN:
                    $user_id = \Joomla\CMS\User\UserHelper::getUserId($author_to_check);
                    $user = \Joomla\CMS\Factory::getUser($user_id);
                    $matched = array_intersect($user->groups, $condition);
                    $matched = !empty($matched);

                    if ((!$matched) && (!array_key_exists($rule->channel_id, $allowed_channels))) {
                        $hasMatched = true;
                    }

                    break;
                case self::LANGUAGE_IN:
                    $matched = '';

                    foreach ($condition as $term) {
                        // Take care: strcmp returns 0 if strings are matching!
                        $matched = strcmp(trim($language_to_check), trim($term));

                        if (0 === (int) $matched) {
                            break;
                        }
                    }

                    if ((0 === (int) $matched) && !array_key_exists($rule->channel_id, $allowed_channels)) {
                        $hasMatched = true;
                    }

                    break;
                case self::LANGUAGE_NOTIN:
                    $matched = '';

                    foreach ($condition as $term) {
                        // Take care: strcmp returns 0 if strings are matching!
                        $matched = strcmp(trim($language_to_check), trim($term));

                        if (0 === (int) $matched) {
                            break;
                        }
                    }

                    if ((0 !== (int) $matched) && !array_key_exists($rule->channel_id, $allowed_channels)) {
                        $hasMatched = true;
                    }

                    break;
                case self::ACCESS_IN:
                    $matched = '';

                    foreach ($condition as $term) {
                        // Take care: strcmp returns 0 if strings are matching!
                        $matched = strcmp(trim($access_to_check), trim($term));

                        if (0 === (int) $matched) {
                            break;
                        }
                    }

                    if ((0 === (int) $matched) && !array_key_exists($rule->channel_id, $allowed_channels)) {
                        $hasMatched = true;
                    }

                    break;
                case self::ACCESS_NOTIN:
                    $matched = '';

                    foreach ($condition as $term) {
                        // Take care: strcmp returns 0 if strings are matching!
                        $matched = strcmp(trim($access_to_check), trim($term));

                        if (0 === (int) $matched) {
                            break;
                        }
                    }

                    if ((0 !== (int) $matched) && !array_key_exists($rule->channel_id, $allowed_channels)) {
                        $hasMatched = true;
                    }

                    break;
                case self::REG_EXPR:
                    $matched = 1 === preg_match($condition, $text_to_check);

                    if (($matched) && (!array_key_exists($rule->channel_id, $allowed_channels))) {
                        $hasMatched = true;
                    }

                    break;
                case self::FEATURED_IS:
                    $hasMatched = $featured_to_check;

                    break;
                case self::FEATURED_ISNOT:
                    $hasMatched = !$featured_to_check;

                    break;
                case self::MEDIA_HAS:
                    $hasMatched = $has_media;

                    break;
                case self::MEDIA_HASNOT:
                    $hasMatched = !$has_media;

                    break;
                case self::TAGS_IN:
                    $tags = $this->getTags($post);
                    $hasMatched = !empty(array_intersect($condition, $tags));

                    break;
                case self::TAGS_NOTIN:
                    $tags = $this->getTags($post);
                    $hasMatched = empty(array_intersect($condition, $tags));

                    break;
                case self::CHANNEL_OWNERGROUP_IN:
                    $hasMatched = array_intersect($condition, $authorisedGroups);
                    $hasMatched = !empty($hasMatched);

                    break;
                case self::CHANNEL_OWNERGROUP_NOTIN:
                    $hasMatched = array_intersect($condition, $authorisedGroups);
                    $hasMatched = empty($hasMatched);

                    break;
                case self::EVERGREEN_IS:
                    $hasMatched = $evergreen_to_check;

                    break;
                case self::EVERGREEN_ISNOT:
                    $hasMatched = !$evergreen_to_check;

                    break;
                case self::CATCH_ALL:
                    if (!array_key_exists($rule->channel_id, $allowed_channels)) {
                        $hasMatched = true;
                    }

                    break;
                case self::CATCH_ALL_NOTFITS:
                    if (!array_key_exists($rule->channel_id, $catch_all_channels)) {
                        $catch_all_channels[$rule->channel_id] = $rule;
                    }

                    break;
            }

            if ($hasMatched) {
                $allowed_channels[$rule->channel_id] = $rule;
            }
        }

        if (empty($allowed_channels)) {
            $allowed_channels = $catch_all_channels;
        }

        return $allowed_channels;
    }

    /**
     * fillUniversalRules.
     *
     * @param array &$rules Params
     */
    private function fillUniversalRules(&$rules)
    {
        $new_rules = [];

        if (empty($rules)) {
            $rules = $new_rules;

            return;
        }

        $channels = ChannelFactory::getInstance()->getChannels();

        $logger = AutotweetLogger::getInstance();
        $channels_ids = array_keys($channels);
        $logger->log(\Joomla\CMS\Log\Log::INFO, 'fillUniversalRules rules n='.count($rules).' channels:', $channels_ids);

        foreach ($rules as $rule) {
            // Universal rule
            if (empty($rule->channel_id)) {
                $logger->log(\Joomla\CMS\Log\Log::INFO, 'Universal rule ('.$rule->id.')');

                foreach ($channels as $channel) {
                    $new_rule = clone $rule;
                    $new_rule->channel_id = $channel->getField('id');
                    $new_rules[] = $new_rule;

                    $logger->log(\Joomla\CMS\Log\Log::INFO, 'Generating rule for channelId: '.$new_rule->channel_id);
                }
            } else {
                $logger->log(\Joomla\CMS\Log\Log::INFO, 'Rule ('.$rule->id.', '.$rule->channel_id.')');

                $new_rules[] = $rule;
            }
        }

        $rules = $new_rules;
    }

    /**
     * getValue.
     *
     * @param object &$rule Param
     * @param string $key   Param
     *
     * @return string
     */
    private static function getValue(&$rule, $key)
    {
        if (isset($rule->{$key})) {
            return $rule->{$key};
        }

        return null;
    }

    private function getTags($post)
    {
        if ('autotweetcontent' !== $post->plugin) {
            return [];
        }

        $itemId = $post->ref_id;

        $tagsHelper = new \Joomla\CMS\Helper\TagsHelper();
        $tags = $tagsHelper->getItemTags('com_content.article', $itemId);
        $titles = XTS_BUILD\Illuminate\Support\Arr::pluck($tags, 'title');

        return $titles;
    }
}
