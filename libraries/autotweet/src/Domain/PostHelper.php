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
final class PostHelper
{
    private function __construct()
    {
    }

    /**
     * savePost.
     *
     * @param string $state      Param
     * @param string $result_msg Param
     * @param object &$post      Param
     * @param object $userid     Param
     * @param object $url        Param
     *
     * @return bool
     */
    public static function savePost($state, $result_msg, &$post, $userid, $url = null)
    {
        $row = XTF0FModel::getTmpInstance('Posts', 'AutoTweetModel')->getTable();
        $row->reset();

        if ($post->id) {
            $row->load($post->id);
        }

        // Avoid databse warnings when desc is longer then expected
        if (!empty($result_msg)) {
            $result_msg = substr($result_msg, 0, 254);
        }

        // Params
        if (!isset($post->xtform)) {
            $post->xtform = new JRegistry();
        }

        $params = (string) $post->xtform;

        if ($url) {
            $post->url = $url;
        }

        $post->pubstate = $state;
        $post->resultmsg = $result_msg;
        $post->params = $params;
        $post->created_by = $userid;
        $post->modified_by = $userid;
        $post->modified = \Joomla\CMS\Factory::getDate()->toSql();

        // It's already in the post queue
        unset($post->autopublish, $post->published);

        $stored = $row->save($post);

        $logger = AutotweetLogger::getInstance();

        if ($stored) {
            $logger->log(\Joomla\CMS\Log\Log::INFO, 'savePost', $post->message);
        } else {
            $logger->log(\Joomla\CMS\Log\Log::ERROR, 'savePost ref_id = '.$post->ref_id.', error message = '.$row->getError());
            $logger->log(\Joomla\CMS\Log\Log::ERROR, 'savePost ref_id = '.$post->ref_id, $post);
        }

        return $stored;
    }

    /**
     * publishPosts - This static function should used from backend only for manual (re)posting attempts.
     *
     * @param array $posts  Param
     * @param int   $userid Param
     *
     * @return bool
     */
    public static function publishPosts($posts, $userid = null)
    {
        $cron_enabled = EParameter::getComponentParam(CAUTOTWEETNG, 'cron_enabled', 0);
        $success = false;

        $sharinghelper = SharingHelper::getInstance();
        $post = XTF0FModel::getTmpInstance('Posts', 'AutoTweetModel')->getTable();

        foreach ($posts as $pid) {
            $post->reset();
            $post->load($pid);
            $post->xtform = EForm::paramsToRegistry($post);

            if ((PostShareManager::POST_APPROVE === $post->pubstate) && ($cron_enabled)) {
                $success = self::savePost(PostShareManager::POST_CRONJOB, 'COM_AUTOTWEET_MSG_POSTRESULT_CRONJOB', $post, $userid, $post->url);
            } else {
                $success = $sharinghelper->publishPost($post, $userid);
            }
        }

        return $success;
    }

    /**
     * moveToState - This static function should used from backend only for manual (re)posting attempts.
     *
     * @param array  $posting_ids Param
     * @param int    $userid      Param
     * @param string $pubstate    Param
     *
     * @return bool
     */
    public static function moveToState($posting_ids, $userid = null, $pubstate = null)
    {
        if (empty($pubstate)) {
            $pubstate = PostShareManager::POST_CANCELLED;
        }

        if (!empty($posting_ids)) {
            $idslist = implode(',', $posting_ids);
            $idslist = '('.$idslist.')';

            $now = \Joomla\CMS\Factory::getDate();

            $db = \Joomla\CMS\Factory::getDBO();
            $query = $db->getQuery(true);
            $query->update('#__autotweet_posts')
                ->set($db->qn('pubstate').' = '.$db->q($pubstate))
                ->set($db->qn('resultmsg').' = '.$db->q(JText::_('COM_AUTOTWEET_MSG_POSTRESULT_MOVED')))
                ->set($db->qn('modified').' = '.$db->q($now->toSql()))
                ->set($db->qn('modified_by').' = '.$db->q($userid))
                ->where($db->qn('id').' IN '.$idslist);
            $db->setQuery($query);
            $db->execute();
        }

        return true;
    }

    /**
     * publishCronjobPosts.
     *
     * @param int $limit Param
     *
     * @return bool
     */
    public static function publishCronjobPosts($limit)
    {
        $postsModel = XTF0FModel::getTmpInstance('Posts', 'AutoTweetModel');
        $postsModel->set('pubstate', PostShareManager::POST_CRONJOB);

        $check_date = PostShareManager::getCheckDate();
        $postsModel->set('before_date', $check_date);

        $postsModel->set('filter_order', 'postdate');
        $postsModel->set('filter_order_Dir', 'ASC');
        $postsModel->set('limit', $limit);
        $posts = $postsModel->getItemList();

        $sharingHelper = SharingHelper::getInstance();

        $logger = AutotweetLogger::getInstance();
        $logger->log(\Joomla\CMS\Log\Log::INFO, 'publishCronjobPosts Posts: '.count($posts));

        foreach ($posts as $post) {
            $logger->log(\Joomla\CMS\Log\Log::INFO, 'Sending Post ID: '.$post->id.' Channel: '.$post->channel_id.' Plugin: '.$post->plugin);

            $post->xtform = EForm::paramsToRegistry($post);
            $sharingHelper->publishPost($post);
        }
    }

    /**
     * isDuplicatedPost.
     *
     * @param int    $id          Param
     * @param int    $ref_id      Param
     * @param string $plugin      Param
     * @param int    $channel_id  Param
     * @param string $message     Param
     * @param int    $time_intval Param
     *
     * @return bool
     */
    public static function isDuplicatedPost($id, $ref_id, $plugin, $channel_id, $message, $time_intval)
    {
        // Duplicate post detection: check message log for message in time interval
        $is_duplicate = false;

        // Calculate date for interval
        $now = \Joomla\CMS\Factory::getDate();
        $check_date = $now->toUnix();
        $check_date = $check_date - $time_intval;
        $check_date = \Joomla\CMS\Factory::getDate($check_date);

        // Get articles only when they are not in the queue and not in the message log for time horizon
        $postsModel = XTF0FModel::getTmpInstance('Posts', 'AutoTweetModel');
        $postsModel->set('not_id', $id);

        // $postsModel->set('ref_id', $ref_id);
        $postsModel->set('message', $message);

        $postsModel->set('plugin', $plugin);
        $postsModel->set('channel', $channel_id);
        $postsModel->set('pubstate', PostShareManager::POST_SUCCESS);
        $postsModel->set('after_date', $check_date->toSql());
        $posts = $postsModel->getItemList(true);

        if (count($posts) > 0) {
            $is_duplicate = true;
        }

        return $is_duplicate;
    }

    /**
     * isBannedPost.
     *
     * @param string &$message      Param
     * @param string &$banned_words Param
     *
     * @return bool
     */
    public static function isBannedPost(&$message, &$banned_words)
    {
        return preg_match('~\b('.$banned_words.')\b~i', $message);
    }

    /**
     * moveToEvergeen - This static function should used from backend only for manual (re)posting attempts.
     *
     * @param array  $ids       Param
     * @param int    $userid    Param
     * @param string $evergreen Param
     *
     * @return bool
     */
    public static function moveToEvergeen($ids, $userid = null, $evergreen = null)
    {
        $advancedattrs = XTF0FModel::getTmpInstance('Advancedattrs', 'AutoTweetModel')->getAdvancedattrs();

        return $advancedattrs;
    }

    /**
     * cancelPosts.
     *
     * @param string $ref_id Param
     * @param string $plugin Param
     * @param int    $userid Param
     *
     * @return bool
     */
    public static function cancelPosts($ref_id, $plugin, $userid)
    {
        $ids = [];
        $pubstates = [PostShareManager::POST_APPROVE, PostShareManager::POST_CRONJOB];

        $postsModel = XTF0FModel::getTmpInstance('Posts', 'AutoTweetModel');
        $postsModel->set('ref_id', $ref_id);
        $postsModel->set('plugin', $plugin);
        $postsModel->set('pubstate', $pubstates);

        $items = $postsModel->getItemList();

        foreach ($items as $item) {
            $ids[] = $item->id;
        }

        return self::moveToState($ids, $userid, 'cancelled');
    }

    public static function getAltText($status, $data)
    {
        $nativeObject = $data->xtform->get('native_object');
        $hasImages = $nativeObject && !empty($nativeObject->images);
        $images = null;

        if ($hasImages) {
            $images = json_decode($nativeObject->images);
        }

        if ($images && !empty($images->image_intro_alt)) {
            return $images->image_intro_alt;
        }

        if (!empty($data->title)) {
            return $data->title;
        }

        return $status;
    }

    /**
     * exists.
     *
     * @param string $refId Param
     *
     * @return bool
     */
    public static function exists($refId)
    {
        $model = XTF0FModel::getTmpInstance('Posts', 'AutoTweetModel');
        $model->set('ref_id', $refId);

        return $model->getFirstItem()->id > 0;
    }
}
