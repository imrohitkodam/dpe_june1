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
final class RequestHelper
{
    private function __construct()
    {
    }

    /**
     * queueMessage.
     *
     * @param string $articleid        Param
     * @param string $source_plugin    Param
     * @param string $publish_up       Param
     * @param string $description      Param
     * @param string $typeinfo         Param
     * @param string $url              Param
     * @param string $imageUrl         Param
     * @param object &$native_object   Param
     * @param string &$advanced_attrs  Param
     * @param string &$params          Param
     * @param string $content_language Param
     * @param int    $priority         Param
     *
     * @return mixed - false, or id of request
     */
    public static function insertRequest($articleid, $source_plugin, $publish_up, $description, $typeinfo = 0, $url = '', $imageUrl = '', &$native_object = null, &$advanced_attrs = null, &$params = null, $content_language = null, $priority = self::PRIORITY_NORMAL)
    {
        $logger = AutotweetLogger::getInstance();

        // Check if message is already queued (it makes no sense to queue message more than once when modfied)
        // if message is already queued, correct the publish date

        $requestsModel = XTF0FModel::getTmpInstance('Requests', 'AutoTweetModel');
        $requestsModel->set('ref_id', $articleid);
        $requestsModel->set('plugin', $source_plugin);
        $requestsModel->set('typeinfo', $typeinfo);
        $row = $requestsModel->getFirstItem();

        $id = $row->id;

        // Avoid databse warnings when desc is longer then expected
        if (!empty($description)) {
            $description = TextUtil::cleanText($description);
            $description = substr($description, 0, SharingHelper::MAX_CHARS_TITLE);
        }

        $routeHelp = RouteHelp::getInstance();

        if ($content_language) {
            $routeHelp->setContentLanguage($content_language);
        }

        if ((PERFECT_PUB_PRO)
            && (EParameter::getComponentParam(CAUTOTWEETNG, 'paywall_mode'))
            && (EParameter::getComponentParam(CAUTOTWEETNG, 'paywall_donot_post_url'))) {
            $url = 'index.php';
        }

        $url = $routeHelp->getAbsoluteUrl($url);

        if ((PERFECT_PUB_PRO)
            && (EParameter::getComponentParam(CAUTOTWEETNG, 'paywall_mode'))
            && (EParameter::getComponentParam(CAUTOTWEETNG, 'paywall_donot_image_url'))) {
            $imageUrl = null;
        }

        if (empty($imageUrl)) {
            // Default image: used in media mode when no image is available
            $imageUrl = EParameter::getComponentParam(CAUTOTWEETNG, 'default_image', '');
        }

        if (!empty($imageUrl)) {
            $imageUrl = $routeHelp->getAbsoluteUrl($imageUrl, true);
        }

        $row->reset();

        if ($id) {
            $row->load($id);
        }

        // If there's no date, it means now
        if (empty($publish_up)) {
            $publish_up = \Joomla\CMS\Factory::getDate()->toSql();
        }

        $request = [
            'id' => $id,
            'ref_id' => $articleid,
            'plugin' => $source_plugin,
            'priority' => $priority,
            'publish_up' => $publish_up,
            'description' => $description,
            'typeinfo' => $typeinfo,
            'url' => $url,
            'image_url' => $imageUrl,
            'native_object' => $native_object,
            'params' => $params,
            'published' => 0,
        ];

        $logger->log(\Joomla\CMS\Log\Log::INFO, 'Enqueued request', $description);

        // Saving the request
        $queued = $row->save($request);

        if (!$queued) {
            $logger->log(\Joomla\CMS\Log\Log::ERROR, 'queueMessage: error storing message to database message queue, article id = '.$articleid.', error message = '.$row->getError());
        } else {
            $logger->log(\Joomla\CMS\Log\Log::INFO, 'queueMessage: message stored/updated to database message queue, article id = '.$articleid);
        }

        if (!$id) {
            $id = $row->id;
        }

        if (($advanced_attrs) && isset($advanced_attrs->attr_id)) {
            $row = XTF0FModel::getTmpInstance('Advancedattrs', 'AutoTweetModel')->getTable();
            $row->reset();
            $row->load($advanced_attrs->attr_id);

            $attr = [
                'id' => $advanced_attrs->attr_id,
                'request_id' => $id,
            ];

            // Updating attr
            $result = $row->save($attr);

            if (!$result) {
                $logger->log(\Joomla\CMS\Log\Log::ERROR, 'Updating attr, attr_id = '.$advanced_attrs->attr_id.', error message = '.$row->getError());
            } else {
                $logger->log(\Joomla\CMS\Log\Log::INFO, 'Updating attr, attr_id = '.$advanced_attrs->attr_id);
            }
        }

        $app = \Joomla\CMS\Factory::getApplication();

        if (($app->isClient('administrator')) && (\Joomla\CMS\Factory::getConfig()->get('show_req_notification', true))) {
            $msg = VersionHelper::getFlavourName().': '.JText::sprintf('COM_AUTOTWEET_REQUEST_ENQUEUED_MSG', $id);
            $app->enqueueMessage($msg);
        }

        return $queued ? $id : false;
    }

    /**
     * processRequests.
     *
     * @param array $rids Param
     *
     * @return bool
     */
    public static function processRequests($rids)
    {
        $requestsModel = XTF0FModel::getTmpInstance('Requests', 'AutoTweetModel');
        $requestsModel->set('rids', $rids);
        $requests = $requestsModel->getItemList(true);

        return self::publishRequests($requests);
    }

    /**
     * publishRequests.
     *
     * @param array &$requests Param
     *
     * @return bool
     */
    public static function publishRequests(&$requests)
    {
        $sharinghelper = SharingHelper::getInstance();

        foreach ($requests as $request) {
            try {
                if ($sharinghelper->publishRequest($request)) {
                    // Remove only, when post is logged successfully
                    self::processed($request->id);
                } else {
                    self::saveError($request->id);
                }
            } catch (Exception $e) {
                $message = $e->getMessage();
                self::saveError($request->id, $message);
            }
        }

        return true;
    }

    /**
     * processed.
     *
     * @param int $id Param
     *
     * @return bool
     */
    public static function processed($id)
    {
        $request = XTF0FModel::getTmpInstance('Requests', 'AutotweetModel')->getTable();
        $request->reset();

        if (!$request->load($id)) {
            return;
        }

        // Native Object
        if (isset($request->native_object)) {
            $nativeObject = TextUtil::json_decode($request->native_object);
        } else {
            $nativeObject = new StdClass();
        }

        $nativeObject->error = false;
        $nativeObject->error_message = 'Ok!';

        // It's processed
        $data = [];
        $data['published'] = true;
        $data['native_object'] = json_encode($nativeObject);

        // Saving
        if (!PERFECT_PUB_PRO) {
            $request->save($data);

            return;
        }

        AdvancedAttributesHelper::execute($id, $data);

        $request->save($data);
    }

    /**
     * saveError.
     *
     * @param int    $id      Param
     * @param string $message Param
     *
     * @return bool
     */
    public static function saveError($id, $message = null)
    {
        $request = XTF0FModel::getTmpInstance('Requests', 'AutotweetModel')->getTable();
        $request->reset();

        if ($request->load($id)) {
            $nativeObject = TextUtil::json_decode($request->native_object);

            if (empty($nativeObject)) {
                $nativeObject = new StdClass();
            }

            $nativeObject->error = true;

            if ($message) {
                $nativeObject->error_message = $message;
            } else {
                $nativeObject->error_message = 'COM_AUTOTWEET_ERROR_PROCESSING';
            }

            $data = [];
            $data['native_object'] = json_encode($nativeObject);
            $data['published'] = true;

            $request->save($data);
        }
    }

    /**
     * getRequestList.
     *
     * @param JDate $check_date Param
     * @param int   $limit      Param
     *
     * @return array
     */
    public static function getRequestList($check_date, $limit)
    {
        $requestsModel = XTF0FModel::getTmpInstance('Requests', 'AutoTweetModel');
        $requestsModel->set('until_date', $check_date->toSql());
        $requestsModel->set('filter_order', 'publish_up');
        $requestsModel->set('filter_order_Dir', 'ASC');
        $requestsModel->set('limit', $limit);

        return $requestsModel->getItemList();
    }

    /**
     * moveToState - This static function should used from backend only for manual (re)posting attempts.
     *
     * @param array  $ids       Param
     * @param int    $userid    Param
     * @param string $published Param
     *
     * @return bool
     */
    public static function moveToState($ids, $userid = null, $published = null)
    {
        $success = true;

        if (!$published) {
            $published = 0;
        }

        if (!empty($ids)) {
            $idslist = implode(',', $ids);
            $idslist = '('.$idslist.')';

            $now = \Joomla\CMS\Factory::getDate();

            $db = \Joomla\CMS\Factory::getDBO();
            $query = $db->getQuery(true);
            $query->update('#__autotweet_requests')
                ->set($db->qn('published').' = '.$db->q($published))
                ->set($db->qn('modified').' = '.$db->q($now->toSql()))
                ->set($db->qn('modified_by').' = '.$db->q($userid))
                ->where($db->qn('id').' IN '.$idslist);

            $db->setQuery($query);
            $db->execute();
        }

        return $success;
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
        $success = true;

        if (!$evergreen) {
            $published = 0;
        }

        if (!empty($ids)) {
            $idslist = implode(',', $ids);
            $idslist = '('.$idslist.')';

            $now = \Joomla\CMS\Factory::getDate();

            if ($evergreen) {
                $from = PostShareManager::POSTTHIS_NO;
                $to = PostShareManager::POSTTHIS_YES;
            } else {
                $from = PostShareManager::POSTTHIS_YES;
                $to = PostShareManager::POSTTHIS_NO;
            }

            $db = \Joomla\CMS\Factory::getDBO();
            $query = $db->getQuery(true);
            $query->select('count(*)')
                ->from('#__autotweet_advanced_attrs')
                ->where($db->qn('request_id').' IN '.$idslist);
            $db->setQuery($query);
            $c = $db->loadResult();

            if ($c < count($ids)) {
                $advancedattrs = XTF0FModel::getTmpInstance('Advancedattrs', 'AutoTweetModel')->getAdvancedattrs();
                $advancedattrs->evergreen = PostShareManager::POSTTHIS_YES;
                $params = json_encode($advancedattrs);

                $query = 'INSERT INTO `#__autotweet_advanced_attrs` (`option`, ref_id, params, request_id, evergreentype_id) SELECT REPLACE(REPLACE(r.plugin, \'autotweet\', \'com_\'), \'com_flexicontent\', \'com_content\'), r.ref_id, \''.
                    $params.'\', r.`id`, 2 FROM `#__autotweet_requests` r  LEFT OUTER JOIN `#__autotweet_advanced_attrs` a ON r.id = a.request_id WHERE a.request_id IS NULL AND r.`id` IN '.$idslist;
                $db->setQuery($query);
                $db->execute();
            }

            // Quick and dirty!
            $query = $db->getQuery(true);
            $query->update('#__autotweet_advanced_attrs')
                ->set($db->qn('evergreentype_id').' = '.$db->q($to))
                ->set(
                    $db->qn('params').' = REPLACE(params, \'"evergreen":"'
                        .$from.'"\', \'"evergreen":"'.$to.'"\')'
                )
                ->set($db->qn('modified').' = '.$db->q($now->toSql()))
                ->set($db->qn('modified_by').' = '.$db->q($userid))
                ->where($db->qn('request_id').' IN '.$idslist);

            $db->setQuery($query);
            $db->execute();
        }

        return $success;
    }

    /**
     * getAjaxData.
     *
     * @param JRegistry $input Param
     *
     * @return array
     */
    public static function getAjaxData($input)
    {
        $data = [];

        $publish_up = $input->get('publish_up', null, 'string');

        if (empty($publish_up)) {
            $publish_up = EParameter::convertUTCLocal(\Joomla\CMS\Factory::getDate()->toSql());
        }

        $description = $input->get('description', null, 'string');

        if (empty($description)) {
            throw new Exception('Invalid message');
        }

        $url = $input->get('url', null, 'string');
        $title = $input->get('title', null, 'string');

        if (empty($title)) {
            $title = $description;
        }

        $data['publish_up'] = $publish_up;
        $data['plugin'] = $input->get('plugin', null, 'cmd');
        $data['ref_id'] = $input->get('ref_id', null, 'string');
        $data['description'] = $description;
        $data['url'] = $url;
        $data['image_url'] = $input->get('image_url', null, 'string');
        $data['published'] = $input->get('published', 0, 'int');
        $data['id'] = $input->get('id', 0, 'int');

        $xtform = [];

        $xtform['title'] = $title;
        $xtform['hashtags'] = $input->get('hashtags', '', 'string');
        $xtform['fulltext'] = $input->get('fulltext', null, 'string');
        $xtform['catid'] = $input->get('catid', null, 'string');

        // $input->get('author', null, 'string');
        $username = \Joomla\CMS\Factory::getUser()->username;

        if ('run' === $input->get('task') && 'mapis' === $input->get('view')) {
            $apiAuthor = EParameter::getComponentParam(CAUTOTWEETNG, 'api_author');
            $username = \Joomla\CMS\Factory::getUser($apiAuthor)->username;
        }

        $xtform['author'] = $username;

        $xtform['language'] = $input->get('language', \Joomla\CMS\Factory::getLanguage()->getTag(), 'string');

        // Public
        $xtform['access'] = $input->get('access', 1, 'string');

        $data['xtform'] = $xtform;

        if (PERFECT_PUB_PRO) {
            $data['autotweet_advanced_attrs'] = $input->get('autotweet_advanced_attrs', null, 'string');
        }

        return $data;
    }

    /**
     * cancelRequests.
     *
     * @param string $refId  Param
     * @param string $plugin Param
     * @param int    $userid Param
     *
     * @return bool
     */
    public static function cancelRequests($refId, $plugin, $userid)
    {
        $ids = [];

        $requestsModel = XTF0FModel::getTmpInstance('Requests', 'AutoTweetModel');
        $requestsModel->set('ref_id', $refId);
        $requestsModel->set('plugin', $plugin);
        $items = $requestsModel->getItemList();

        foreach ($items as $item) {
            $ids[] = $item->id;
        }

        return self::moveToState($ids, $userid, true);
    }

    /**
     * cancelEvergreens.
     *
     * @param string $refId  Param
     * @param string $plugin Param
     * @param int    $userid Param
     *
     * @return bool
     */
    public static function cancelEvergreens($refId, $plugin, $userid)
    {
        $ids = [];

        $requestsModel = XTF0FModel::getTmpInstance('Requests', 'AutoTweetModel');
        $requestsModel->set('ref_id', $refId);
        $requestsModel->set('plugin', $plugin);
        $items = $requestsModel->getItemList();

        foreach ($items as $item) {
            $ids[] = $item->id;
        }

        return self::moveToEvergeen($ids, $userid, false);
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
        $model = XTF0FModel::getTmpInstance('Requests', 'AutoTweetModel');
        $model->set('ref_id', $refId);

        return $model->getFirstItem()->id > 0;
    }
}
