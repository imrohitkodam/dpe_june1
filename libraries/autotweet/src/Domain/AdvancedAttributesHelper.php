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
 * AdvancedAttributesHelper.
 *
 * @since       1.0
 */
final class AdvancedAttributesHelper
{
    private function __construct()
    {
    }

    /**
     * get.
     *
     * @param string $extension_option Params
     * @param int    $ref_id           Params
     *
     * @return object
     */
    public static function get($extension_option, $ref_id)
    {
        $advanced_attrs = null;

        if (PERFECT_PUB_PRO) {
            $advancedAttrsModel = XTF0FModel::getTmpInstance('Advancedattrs', 'AutoTweetModel');
            $advancedAttrsModel->set('option-filter', $extension_option);
            $advancedAttrsModel->set('ref_id', $ref_id);
            $advancedAttrs = $advancedAttrsModel->getFirstItem();

            if ($advancedAttrs) {
                $advanced_attrs = json_decode($advancedAttrs->params);
            }
        }

        return $advanced_attrs;
    }

    /**
     * getByRequest.
     *
     * @param int $req_id Params
     *
     * @return object
     */
    public static function getByRequest($req_id)
    {
        $advanced_attrs = null;

        if (PERFECT_PUB_PRO) {
            $advancedAttrsModel = XTF0FModel::getTmpInstance('Advancedattrs', 'AutoTweetModel');
            $advancedAttrsModel->set('request_id', $req_id);
            $advancedAttrs = $advancedAttrsModel->getFirstItem();

            if (($advancedAttrs) && ($advancedAttrs->request_id === $req_id)) {
                $advanced_attrs = json_decode($advancedAttrs->params);
            }
        }

        return $advanced_attrs;
    }

    /**
     * fromQueryParams.
     *
     * @param string &$autotweet_advanced Params
     *
     * @return object
     */
    public static function fromQueryParams(&$autotweet_advanced)
    {
        if (is_array($autotweet_advanced)) {
            $autotweet_advanced = (object) $autotweet_advanced;
        }

        if (is_string($autotweet_advanced)) {
            $autotweet_advanced = json_decode($autotweet_advanced);
        }

        unset(
            $autotweet_advanced->editorTitle,
            $autotweet_advanced->postthisLabel,
            $autotweet_advanced->evergreenLabel,
            $autotweet_advanced->agendaLabel,
            $autotweet_advanced->unix_mhdmdLabel,
            $autotweet_advanced->repeat_untilLabel,
            $autotweet_advanced->imageLabel,
            $autotweet_advanced->channelLabel,
            $autotweet_advanced->postthisDefaultLabel,
            $autotweet_advanced->postthisYesLabel,
            $autotweet_advanced->postthisNoLabel,
            $autotweet_advanced->postthisImmediatelyLabel,
            $autotweet_advanced->postthisOnlyOnceLabel,
            $autotweet_advanced->descriptionLabel,
            $autotweet_advanced->hashtagsLabel,
            $autotweet_advanced->fulltextLabel
        );

        AutotweetBaseHelper::convertLocalUTCAgenda($autotweet_advanced->agenda);

        $advanced_attrs = $autotweet_advanced;

        [$isAdmin, $option, $controller, $task, $view, $layout, $articleid] = AutotweetBaseHelper::getControllerParams($autotweet_advanced);

        $isThirdPartyRequest = (
            (CAUTOTWEETNG === $option)
                && (
                    // Other Joomla Extensions
                    (('requests' === $view) && (('save' === $task) || ('applyAjaxPluginAction' === $task)))
                )
        );

        if (!$isThirdPartyRequest) {
            $advanced_attrs->client_id = $isAdmin;
            $advanced_attrs->option = $option;
            $advanced_attrs->controller = $controller;
            $advanced_attrs->task = $task;
            $advanced_attrs->view = $view;
            $advanced_attrs->layout = $layout;
            $advanced_attrs->ref_id = $articleid;
        }

        return $advanced_attrs;
    }

    /**
     * saveAdvancedAttrs.
     *
     * @param object &$advanced_attrs Params
     * @param int    $articleid       Params
     *
     * @return int
     */
    public static function save(&$advanced_attrs, $articleid)
    {
        static $data = null;
        static $attr_id = null;

        $json_advanced_attrs = json_encode($advanced_attrs);
        $hash = md5($json_advanced_attrs);

        if ($data === $hash) {
            // What! Saved, but unidentifed ... flexicontent ...
            if (!isset($advanced_attrs->attr_id)) {
                $advanced_attrs->attr_id = $attr_id;
            }

            return;
        }

        $data = $hash;
        $row = self::getFirstItem($advanced_attrs, $articleid);
        $id = $row->id;
        $row->reset();

        if ($id) {
            $row->load($id);

            if (!empty($row->params)) {
                $params = json_decode($row->params);
                $advanced_attrs = (object) array_merge((array) $params, (array) $advanced_attrs);
                $json_advanced_attrs = json_encode($advanced_attrs);
                $hash = md5($json_advanced_attrs);
                $data = $hash;
            }
        }

        $advancedAttrs = [
            'id' => $id,
            'client_id' => (int) $advanced_attrs->client_id,
            'option' => $advanced_attrs->option,
            'controller' => $advanced_attrs->controller,
            'task' => $advanced_attrs->task,
            'view' => $advanced_attrs->view,
            'layout' => $advanced_attrs->layout,
            'ref_id' => $articleid,
            'params' => $json_advanced_attrs,
            'created' => \Joomla\CMS\Factory::getDate()->toSql(),
            'created_by' => \Joomla\CMS\Factory::getUser()->id,
        ];

        $result = $row->save($advancedAttrs);

        if (!$id) {
            $id = $row->id;
        }

        $advanced_attrs->attr_id = $id;
        $attr_id = $id;

        $logger = AutotweetLogger::getInstance();

        if (!$result) {
            $logger->log(\Joomla\CMS\Log\Log::ERROR, 'advanced_attrs: error storing message to database advanced_attrs, article id = '.$articleid.', error message = '.$row->getError());
        } else {
            $logger->log(\Joomla\CMS\Log\Log::INFO, 'Saved advanced_attrs', $advancedAttrs);
        }

        return $id;
    }

    /**
     * assignRequestId.
     *
     * @param int $attr_id Params
     * @param int $req_id  Params
     *
     * @return int
     */
    public static function assignRequestId($attr_id, $req_id)
    {
        $row = XTF0FModel::getTmpInstance('Advancedattrs', 'AutoTweetModel')->getTable();
        $row->load($attr_id);
        $data = [
            'id' => $attr_id,
            'request_id' => $req_id,
        ];
        $row->save($data);
    }

    /**
     * execute.
     *
     * @param int   $req_id Params
     * @param array &$data  Params
     */
    public static function execute($req_id, &$data)
    {
        // Advanced Attrs - Initialization
        $advancedAttrsModel = XTF0FModel::getTmpInstance('Advancedattrs', 'AutoTweetModel');
        $advancedAttrsModel->set('request_id', $req_id);
        $advancedAttrs = $advancedAttrsModel->getFirstItem();

        if (!$advancedAttrs) {
            return;
        }

        $params = json_decode($advancedAttrs->params);

        // No params
        if (!$params) {
            return;
        }

        // Joocial AND Params

        if ((!isset($params->agenda)) || (empty($params->agenda))) {
            $agendaAdd = EParameter::getComponentParam(CAUTOTWEETNG, 'joocial_agenda_add');

            if (!empty($agendaAdd)) {
                $nativeObject = json_decode($data['native_object'], true);
                $publishUp = $nativeObject['publish_up'];
                $params->agenda = self::generateAgenda($publishUp, $agendaAdd);
            }
        }

        // Checking if there are future tasks
        if ((isset($params->agenda)) && (count($params->agenda) > 0)) {
            $publish_up = self::getNextAgendaDate($params->agenda);

            if ($publish_up) {
                $data['publish_up'] = $publish_up;

                // Not finished yet, we have at least a date to schedule
                $data['published'] = false;
            } else {
                $data['published'] = true;
            }
        }

        // Repeat
        if ((isset($params->unix_mhdmd)) && (!empty($params->unix_mhdmd))) {
            $repeat_until = null;

            if (isset($params->repeat_until)) {
                $repeat_until = $params->repeat_until;
            }

            // Not finished yet, we have at least a date to schedule
            $nextRepeatDate = self::getNextRepeatPublishUp($params->unix_mhdmd, $repeat_until);

            if ($nextRepeatDate) {
                $data['publish_up'] = $nextRepeatDate->toSql();
                $data['published'] = false;
            } else {
                $data['published'] = true;
            }
        }

        $params = json_encode($params);
        $advancedattr_data = ['params' => $params];
        $advancedAttrs->save($advancedattr_data);
    }

    /**
     * getNextAgendaDate.
     *
     * @param array $agenda Param
     *
     * @return string
     */
    public static function getNextAgendaDate($agenda)
    {
        $n = count($agenda);

        if (0 === $n) {
            return null;
        }

        $now = \Joomla\CMS\Factory::getDate();
        $now_unix = $now->toUnix();

        // The first date

        $i = 0;

        do {
            $publish_up = $agenda[$i];
            $publish_up_unix = \Joomla\CMS\Factory::getDate($publish_up)->toUnix();
            ++$i;
        } while (($i < $n) && ($publish_up_unix < $now_unix));

        if ($publish_up_unix > $now_unix) {
            return $publish_up;
        }

        return null;
    }

    /**
     * generateAgenda.
     *
     * @param string $publishUp Param
     * @param string $agendaAdd Param
     *
     * @return string
     */
    public static function generateAgenda($publishUp, $agendaAdd)
    {
        $agenda = [];
        $now = \Joomla\CMS\Factory::getDate($publishUp);

        $intervals = array_filter(explode(',', $agendaAdd));

        foreach ($intervals as $interval) {
            $dateInterval = DateInterval::createFromDateString($interval);
            $date = $now->add($dateInterval);
            $agenda[] = $date->toSql();
        }

        return $agenda;
    }

    /**
     * getEditLink.
     *
     * @param string $client_id Param
     * @param string $option    Param
     * @param string $ref_id    Param
     * @param string $req_id    Param
     *
     * @return string
     */
    public static function getEditLink($client_id, $option, $ref_id, $req_id)
    {
        $link = null;

        switch ($option) {
            case 'com_content':
                $link = 'index.php?option=com_content&task=article.edit&id='.$ref_id;

                break;
            case 'com_autotweet':
                $link = 'index.php?option=com_autotweet&view=composer&req-id='.$req_id;

                break;
            case 'com_k2':
                $link = 'index.php?option=com_k2&view=item&cid='.$ref_id;

                break;
            case 'com_easyblog':
                // $link = 'index.php?option=com_easyblog&c=blogs&task=edit&blogid=' . $ref_id;
                $link = 'index.php?option=com_easyblog&view=composer&tmpl=component&uid='.$ref_id;

                break;
            case 'com_flexicontent':
                $link = 'index.php?option=com_flexicontent&task=items.edit&cid[]='.$ref_id;

                break;
            case 'com_zoo':
                $link = 'index.php?option=com_zoo&controller=item&task=edit&cid[]='.$ref_id;

                break;
        }

        return $link;
    }

    /**
     * getEvergreens.
     *
     * @param array $items Param
     *
     * @return array
     */
    public static function getEvergreens($items)
    {
        $evergreens = [];

        $ids = array_map(
            function ($o) {
                return $o->id;
            },
            $items
        );

        $advancedattrs = XTF0FModel::getTmpInstance('Advancedattrs', 'AutoTweetModel');
        $advancedattrs->setState('evergreentype_id', PostShareManager::POSTTHIS_YES_ALL);
        $advancedattrs->setState('request_ids', $ids);

        $attrs = $advancedattrs->getList();

        foreach ($attrs as $attr) {
            $evergreens[$attr->request_id] = $attr;
        }

        return $evergreens;
    }

    /**
     * getImmediates.
     *
     * @param array $items Param
     *
     * @return array
     */
    public static function getImmediates($items)
    {
        $immediates = [];

        $ids = array_map(
            function ($o) {
                return $o->id;
            },
            $items
        );

        $advancedattrs = XTF0FModel::getTmpInstance('Advancedattrs', 'AutoTweetModel');
        $advancedattrs->setState('postthis', PostShareManager::POSTTHIS_IMMEDIATELY);
        $advancedattrs->setState('request_ids', $ids);

        $attrs = $advancedattrs->getList();

        foreach ($attrs as $attr) {
            $immediates[$attr->request_id] = $attr;
        }

        return $immediates;
    }

    /**
     * getEvergreen.
     *
     * @param int $id Param
     *
     * @return object
     */
    public static function getEvergreen($id)
    {
        $advancedattrs = XTF0FModel::getTmpInstance('Advancedattrs', 'AutoTweetModel');
        $advancedattrs->setState('evergreentype_id', PostShareManager::POSTTHIS_YES_ALL);
        $advancedattrs->setState('request_id', $id);

        return $advancedattrs->getFirstItem();
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

            // Quick and dirty!
            $db = \Joomla\CMS\Factory::getDBO();
            $query = $db->getQuery(true);
            $query->update('#__autotweet_advanced_attrs')
                ->set($db->qn('evergreentype_id').' = '.$db->q($to))
                ->set(
                    $db->qn('params').' = REPLACE(params, \'"evergreen":"'
                    .$from.'"\', \'"evergreen":"'.$to.'"\')'
                )
                ->set($db->qn('modified').' = '.$db->q($now->toSql()))
                ->set($db->qn('modified_by').' = '.$db->q($userid))
                ->where($db->qn('id').' IN '.$idslist);

            $db->setQuery($query);
            $db->execute();
        }

        return $success;
    }

    public static function generateRequestsForComposerApp($requests)
    {
        if (empty($requests)) {
            return [];
        }

        XTF0FModel::getTmpInstance('Plugins', 'AutoTweetModel');
        $result = [];

        if (PERFECT_PUB_PRO) {
            $evergreens = self::getEvergreens($requests);
            $immediates = self::getImmediates($requests);
        }

        foreach ($requests as $item) {
            $native_object = TextUtil::json_decode($item->native_object);
            $has_error = ((isset($native_object->error)) && ($native_object->error));
            $description = TextUtil::truncString($item->description, AutoTweetDefaultView::MAX_CHARS_TITLE_SCREEN, true);

            $is_evergreen = false;
            $is_immediate = false;

            if (PERFECT_PUB_PRO) {
                $item->xtform = EForm::paramsToRegistry($item);

                $is_evergreen = ((array_key_exists($item->id, $evergreens))
                        || ($item->xtform->get('evergreen_generated')));
                $is_immediate = array_key_exists($item->id, $immediates);
            }

            $elem = [
                'id' => $item->id,
                'title' => $description,
                'title_raw' => $item->description,
                'start' => JHtml::_('date', $item->publish_up, JText::_('COM_AUTOTWEET_DATE_FORMAT')),
                'className' => ($item->published ?
                        ($has_error ? 'req-error' : 'req-success') :
                        ($has_error ? 'req-warning' : 'req-info')),

                'checked_out' => (bool) $item->checked_out,
                'publish_up' => JHtml::_('date', $item->publish_up, JText::_('COM_AUTOTWEET_DATE_FORMAT')),
                'description' => $description,
                'plugin_simple_name' => AutoTweetModelPlugins::getSimpleName($item->plugin),
                'is_evergreen' => $is_evergreen,
                'is_immediate' => $is_immediate,
                'published' => $item->published,
            ];

            if (!empty($item->url)) {
                $elem['url'] = TextUtil::renderUrl($item->url);
            }

            if (!empty($item->image_url)) {
                $elem['image_url'] = TextUtil::renderUrl($item->image_url);
            }

            $result[] = $elem;
        }

        return $result;
    }

    public static function generatePostsForComposerApp($posts)
    {
        if (empty($posts)) {
            return [];
        }

        XTF0FModel::getTmpInstance('Plugins', 'AutoTweetModel');
        $result = [];
        $channeltypes = XTF0FModel::getTmpInstance('Channeltypes', 'AutoTweetModel');

        foreach ($posts as $item) {
            $message = $item->message;
            $message = TextUtil::truncString($message, AutoTweetDefaultView::MAX_CHARS_TITLE_SCREEN, true);

            $url = $item->url;

            if (empty($url)) {
                $url = $item->org_url;
            }

            $channel_type = SelectControlHelper::getChannelType($item->channel_id);

            $is_evergreen = false;
            $evergreen_link = null;
            $is_immediate = false;

            if (PERFECT_PUB_PRO) {
                $item->xtform = EForm::paramsToRegistry($item);

                if ($req_id_src = $item->xtform->get('req_id_src')) {
                    $evergreen_item = self::getEvergreen($req_id_src);

                    if (isset($evergreen_item->id)) {
                        $is_evergreen = true;
                        $evergreen_link = self::getEditLink($evergreen_item->client_id, $evergreen_item->option, $evergreen_item->ref_id, $evergreen_item->id);
                    }
                } else {
                    $is_evergreen = ($item->xtform->get('evergreen_generated'));
                }

                $is_immediate = ($item->xtform->get('is_immediate'));
            }

            $elem = [
                'id' => $item->id,
                'pubstate' => $item->pubstate,
                'postdate' => JHtml::_('date', $item->postdate, JText::_('COM_AUTOTWEET_DATE_FORMAT')),
                'message' => $message,
                'message_raw' => $item->message,
                'url' => TextUtil::renderUrl($url),
                'org_url' => TextUtil::renderUrl($item->org_url),
                'image_url' => TextUtil::renderUrl($item->image_url),
                'channel_id' => $item->channel_id,
                'channel_name' => SelectControlHelper::getChannelName($item->channel_id),
                'channel_type' => $channel_type,
                'channel_icon' => $channeltypes->getRawIcon($channel_type),
                'resultmsg' => JText::_($item->resultmsg),
                'plugin_simple_name' => AutoTweetModelPlugins::getSimpleName($item->plugin),
                'is_evergreen' => $is_evergreen,
                'evergreen_link' => $evergreen_link,
                'is_immediate' => $is_immediate,
                'checked_out' => (bool) $item->checked_out,
            ];

            $result[] = $elem;
        }

        return $result;
    }

    public static function generateForComposerApp($request)
    {
        $params = self::getByRequest($request->id);
        AutotweetBaseHelper::convertUTCLocalAgenda($params->agenda);

        if (!empty($params->image)) {
            $request->image_url = $params->image;
            $params->image = null;
        }

        XTF0FModel::getTmpInstance('Plugins', 'AutoTweetModel');
        $request->plugin_simple_name = AutoTweetModelPlugins::getSimpleName($request->plugin);
        $request->autotweet_advanced_attrs = $params;
    }

    /**
     * getRepeatPublishUpDates.
     *
     * @param string $repeat       Param
     * @param date   $repeat_until Param
     * @param int    $n            Param
     *
     * @return Countable|array
     */
    public static function getRepeatPublishUpDates($repeat, $repeat_until, $n = 30)
    {
        $dates = [];

        try {
            $current_date = \Joomla\CMS\Factory::getDate();
            $dpcheck = EParameter::getComponentParam(CAUTOTWEETNG, 'dpcheck_time_intval', 12) * 3600;
            $current_unix = $current_date->toUnix() + $dpcheck;

            $datePublishUp = \Joomla\CMS\Factory::getDate();
            $i = 0;

            while ($i < $n) {
                $datePublishUp->setTimestamp($current_unix);

                $publish_up = TextUtil::nextScheduledDate($repeat, $datePublishUp);

                if (($publish_up) && (!empty($repeat_until))
                    && ($publish_up->toUnix() > \Joomla\CMS\Factory::getDate($repeat_until)->toUnix())) {
                    break;
                }

                $dates[] = $publish_up;
                $current_date = \Joomla\CMS\Factory::getDate($publish_up);
                $current_unix = $current_date->toUnix();
                ++$i;
            }
        } catch (Exception $e) {
            $logger = AutotweetLogger::getInstance();
            $logger->log(\Joomla\CMS\Log\Log::ERROR, 'getRepeatPublishUpDates'.$e->getMessage());
        }

        return $dates;
    }

    /**
     * getFirstItem.
     *
     * @param object &$advanced_attrs Params
     * @param int    $articleid       Params
     *
     * @return object
     */
    private static function getFirstItem(&$advanced_attrs, $articleid)
    {
        $advancedAttrsModel = XTF0FModel::getTmpInstance('Advancedattrs', 'AutoTweetModel');

        if (isset($advanced_attrs->option)) {
            $advancedAttrsModel->set('option-filter', $advanced_attrs->option);
        } elseif (isset($advanced_attrs->option_filter)) {
            $advancedAttrsModel->set('option-filter', $advanced_attrs->option_filter);
        }

        unset($advanced_attrs->option_filter);

        $advancedAttrsModel->set('ref_id', $articleid);
        $row = $advancedAttrsModel->getFirstItem();

        return $row;
    }

    /**
     * getNextRepeatPublishUp.
     *
     * @param string $repeat       Param
     * @param date   $repeat_until Param
     *
     * @return object
     */
    private static function getNextRepeatPublishUp($repeat, $repeat_until)
    {
        $dates = self::getRepeatPublishUpDates($repeat, $repeat_until, 1);

        if (1 == count($dates)) {
            return $dates[0];
        }

        return null;
    }
}
