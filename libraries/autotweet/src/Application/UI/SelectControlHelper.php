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

use XTP_BUILD\Extly\Infrastructure\Service\Cms\Joomla\ScriptHelper;

/**
 * SelectControlHelper.
 *
 * @since       1.0
 */
class SelectControlHelper
{
    public const REQ_ICON_YES = '<i class="xticon fas fa-check text-success"></i>';

    public const REQ_ICON_NO = '<i class="xticon far fa-clock text-warning"></i>';

    public const MEDIA_MODE_TEXT_ONLY_POST = 'message';

    public const MEDIA_MODE_POST_WITH_IMAGE = 'message-with-image';

    public static $cache_channels = null;

    public static $cache_channels_type = null;

    /**
     * channels.
     *
     * @param string $selected The key that is selected
     * @param string $name     The name for the field
     * @param array  $attribs  Additional HTML attributes for the <select> tag
     * @param string $idTag    Param
     *
     * @return string HTML
     */
    public static function channels($selected = null, $name = 'channel', $attribs = [], $idTag = null)
    {
        if (EXTLY_J4) {
            $attribs['class'] = 'no-chosen';
        }

        if ((!isset($attribs['class']) || false === strpos($attribs['class'], 'no-chosen')) && (!empty($idTag))) {
            JHtml::_('formbehavior.chosen', '#'.$idTag);
        }

        $channelsModel = XTF0FModel::getTmpInstance('Channels', 'AutoTweetModel');
        $channelsModel->set('published', 1);
        $items = $channelsModel->getItemList(true);

        $options = [];

        if ((!array_key_exists('multiple', $attribs)) || (!$attribs['multiple'])) {
            $options[] = JHTML::_('select.option', null, '-'.JText::_('JSELECT').'-');
        }

        if (count($items)) {
            foreach ($items as $item) {
                $options[] = JHTML::_('select.option', $item->id, $item->name);
            }
        }

        return EHtmlSelect::customGenericList($options, $name, $attribs, $selected, $idTag);
    }

    /**
     * appChannels.
     *
     * @return string HTML
     */
    public static function appChannels()
    {
        $channelsModel = XTF0FModel::getTmpInstance('Channels', 'AutoTweetModel');
        $channelsModel->set('published', 1);
        $items = $channelsModel->getItemList(true);

        $channels = [];
        $c = new stdClass();
        $c->id = 0;
        $c->name = '-Select-';

        $channels[] = $c;

        if (!empty($items)) {
            foreach ($items as $item) {
                $c = new stdClass();
                $c->id = $item->id;
                $c->name = $item->name;

                $channels[] = $c;
            }
        }

        return $channels;
    }

    /**
     * channelsMultiRadio.
     *
     * @param string $selected The key that is selected
     * @param string $name     The name for the field
     * @param array  $attribs  Additional HTML attributes for the <select> tag
     * @param string $idTag    Param
     *
     * @return string HTML
     */
    public static function channelsMultiRadio($selected = null, $name = 'channel', $attribs = [], $idTag = null)
    {
        $channelsModel = XTF0FModel::getTmpInstance('Channels', 'AutoTweetModel');
        $channelsModel->set('published', 1);
        $items = $channelsModel->getItemList(true);

        $options = [];

        if (count($items)) {
            foreach ($items as $item) {
                $options[] = JHTML::_('select.option', $item->id, $item->name);
            }
        }

        return EHtmlSelect::checkboxList($options, $name, $attribs, 'value', 'text', $selected, $idTag);
    }

    /**
     * plugins.
     *
     * @param string $selected The key that is selected
     * @param string $name     The name for the field
     * @param array  $attribs  Additional HTML attributes for the <select> tag
     * @param array  &$config  Config
     *
     * @return string HTML
     */
    public static function plugins($selected = null, $name = 'plugin', $attribs = [], &$config = null)
    {
        $pluginsModel = XTF0FModel::getTmpInstance('Plugins', 'AutoTweetModel');
        $pluginsModel->set('extension_plugins_only', true);
        $pluginsModel->set('published_only', true);
        $items = $pluginsModel->getItemList(true);

        $options = [];
        $options[] = JHTML::_('select.option', null, '-'.JText::_('JSELECT').'-');

        if (count($items)) {
            foreach ($items as $item) {
                $nameValue = $pluginsModel->getSimpleName($item->element);
                $options[] = JHTML::_('select.option', $item->element, $nameValue);
            }
        }

        return EHtmlSelect::customGenericList($options, $name, $attribs, $selected, $name);
    }

    /**
     * ruletypes.
     *
     * @param string $selected The key that is selected
     * @param string $name     The name for the field
     * @param array  $attribs  Additional HTML attributes for the <select> tag*
     *
     * @return string HTML
     */
    public static function ruletypes($selected = null, $name = 'ruletype', $attribs = [])
    {
        $channeltypes = XTF0FModel::getTmpInstance('Ruletypes', 'AutotweetModel');
        $items = $channeltypes->getItemList(true);

        $options = [];
        $options[] = JHTML::_('select.option', null, '-'.JText::_('JSELECT').'-');

        if (count($items)) {
            foreach ($items as $item) {
                $options[] = JHTML::_('select.option', $item->id, $item->name);
            }
        }

        return EHtmlSelect::customGenericList($options, $name, $attribs, $selected, $name);
    }

    /**
     * channeltypes.
     *
     * @param string $selected             The key that is selected
     * @param string $name                 The name for the field
     * @param array  $attribs              Additional HTML attributes for the <select> tag
     * @param bool   $onlyFrontEndChannels Param
     *
     * @return string HTML
     */
    public static function channeltypes($selected = null, $name = 'channeltype', $attribs = [], $onlyFrontEndChannels = false)
    {
        $channeltypes = XTF0FModel::getTmpInstance('Channeltypes', 'AutotweetModel');
        $items = $channeltypes->getItemList(true);

        $items = XTP_collect($items)->filter(function ($item) {
            return !XTP_starts_with($item->name, '― Deprecated ―');
        })->toArray();

        $options = [];
        $options[] = JHTML::_('select.option', null, '-'.JText::_('JSELECT').'-');

        if ($onlyFrontEndChannels) {
            $channels = XTF0FModel::getTmpInstance('Channels', 'AutotweetModel');
            $channels->setState('frontendchannel', 1);
            $frontChannels = $channels->getItemList(true);

            $ids = $channels->getChannelTypes($frontChannels);
        }

        if (!empty($items)) {
            foreach ($items as $item) {
                if ((!$onlyFrontEndChannels) || (in_array($item->id, $ids, true))) {
                    $options[] = JHTML::_('select.option', $item->id, $item->name);
                }
            }
        }

        return EHtmlSelect::customGenericList($options, $name, $attribs, $selected, $name);
    }

    /**
     * getRuletypeName.
     *
     * @param string $ruletype Param
     *
     * @return string
     */
    public static function getRuletypeName($ruletype)
    {
        static $ruletypes = null;

        if (null === $ruletypes) {
            $ruletypesModel = XTF0FModel::getTmpInstance('Ruletypes', 'AutotweetModel');
            $items = $ruletypesModel->getItemList(true);

            if (count($items)) {
                foreach ($items as $item) {
                    $ruletypes[$item->id] = $item->name;
                }
            } else {
                return '?';
            }
        }

        if (array_key_exists($ruletype, $ruletypes)) {
            return $ruletypes[$ruletype];
        }

        return '?';
    }

    /**
     * getChanneltypeName.
     *
     * @param string $channeltype Param
     *
     * @return string
     */
    public static function getChanneltypeName($channeltype)
    {
        static $channeltypes = null;

        if (null === $channeltypes) {
            $channeltypesModel = XTF0FModel::getTmpInstance('Channeltypes', 'AutotweetModel');
            $items = $channeltypesModel->getItemList(true);

            if (count($items)) {
                foreach ($items as $item) {
                    $channeltypes[$item->id] = $channeltypesModel->getIcon($item->id).' - '.$item->name;
                }
            } else {
                return '?';
            }
        }

        if (array_key_exists($channeltype, $channeltypes)) {
            return $channeltypes[$channeltype];
        }

        return '?';
    }

    /**
     * getChannelName.
     *
     * @param int  $channel  Param
     * @param bool $isModule Param
     *
     * @return string
     */
    public static function getChannelName($channel, $isModule = false)
    {
        self::_loadChannels($isModule);

        if (array_key_exists($channel, self::$cache_channels)) {
            return self::$cache_channels[$channel];
        }

        return '?';
    }

    /**
     * getChannelType.
     *
     * @param int $channel Param
     *
     * @return string
     */
    public static function getChannelType($channel)
    {
        self::_loadChannels();

        if (array_key_exists($channel, self::$cache_channels)) {
            return self::$cache_channels_type[$channel];
        }

        return null;
    }

    /**
     * showstatictext.
     *
     * @param string $selected The key that is selected
     * @param string $name     The name for the field
     * @param array  $attribs  Additional HTML attributes for the <select> tag*
     *
     * @return string HTML
     */
    public static function showstatictext($selected = null, $name = 'show_static_text', $attribs = [])
    {
        $options = [];

        $selected = ($selected ?: 'selected');

        // Get media modes
        $modes = self::getShowStaticTextEnum();

        // Generate html
        foreach ($modes as $mode) {
            $options[] = JHtml::_('select.option', $mode, JText::_(self::getTextForEnum($mode)), 'value', 'text');
        }

        return EHtmlSelect::customGenericList($options, $name, $attribs, $selected, $name);
    }

    /**
     * showurl.
     *
     * @param string $selected The key that is selected
     * @param string $name     The name for the field
     * @param array  $attribs  Additional HTML attributes for the <select> tag*
     *
     * @return string HTML
     */
    public static function showurl($selected = null, $name = 'show_url', $attribs = [])
    {
        $options = [];

        $selected = ($selected ?: 'selected');

        // Get media modes
        $modes = self::getShowurlEnum();

        // Generate html
        foreach ($modes as $mode) {
            $options[] = JHtml::_('select.option', $mode, JText::_(self::getTextForEnum($mode)), 'value', 'text');
        }

        return EHtmlSelect::customGenericList($options, $name, $attribs, $selected, $name);
    }

    /**
     * mediamodes.
     *
     * @param string $selected The key that is selected
     * @param string $name     The name for the field
     * @param array  $attribs  Additional HTML attributes for the <select> tag*
     *
     * @return string HTML
     */
    public static function autopublish($selected = null, $name = 'autopublish', $attribs = [])
    {
        $options = [];

        $selected = ($selected ?: 'on');

        // Get media modes
        $modes = self::getAutopublishEnum();

        // Generate html
        foreach ($modes as $mode) {
            $options[] = JHtml::_('select.option', $mode, JText::_(self::getTextForEnum($mode)), 'value', 'text');
        }

        return EHtmlSelect::customGenericList($options, $name, $attribs, $selected, $name);
    }

    /**
     * mediamodes.
     *
     * @param string $selected The key that is selected
     * @param string $name     The name for the field
     * @param array  $attribs  Additional HTML attributes for the <select> tag*
     *
     * @return string HTML
     */
    public static function mediamodes($selected = null, $name = 'type', $attribs = [])
    {
        $options = [
            ['name' => JText::_('JYES'), 'value' => self::MEDIA_MODE_POST_WITH_IMAGE],
            ['name' => JText::_('JNO'), 'value' => self::MEDIA_MODE_TEXT_ONLY_POST],
        ];

        return EHtmlSelect::btnGroupList(
            $selected,
            $name,
            $attribs,
            $options,
            $name
        );
    }

    /**
     * pubstates.
     *
     * @param string $selected The key that is selected
     * @param string $name     The name for the field
     * @param array  $attribs  Additional HTML attributes for the <select> tag*
     * @param string $idTag    Param
     * @param bool   $isFilter Param
     *
     * @return string HTML
     */
    public static function pubstates($selected = null, $name = 'type', $attribs = [], $idTag = null, $isFilter = false)
    {
        $platform = XTF0FPlatform::getInstance();
        $input = new \Joomla\CMS\Input\Input($_REQUEST);
        $editstate = $platform->authorise('core.edit.state', $input->getCmd('option', 'com_foobar'));

        if (($editstate) || ($isFilter)) {
            if ((!$isFilter) && (null === $selected)) {
                $selected = 'approve';
            }

            $options = [];
            $options[] = JHTML::_('select.option', null, '-'.JText::_('JSELECT').'-');

            // Get media modes
            $modes = self::getPubstateEnum();

            // Generate html
            foreach ($modes as $mode) {
                $options[] = JHtml::_('select.option', $mode, JText::_(self::getTextForEnum($mode)), 'value', 'text');
            }

            return EHtmlSelect::customGenericList($options, $name, $attribs, $selected, $name);
        }

        if (!$selected) {
            $selected = 'approve';
        }

        $tag = JText::_(self::getTextForEnum($selected));

        $control = EHtml::readonlyText($tag, $name.'-readonly');
        $control .= '<input type="hidden" value="'.$selected.'" name="'.$name.'" id="'.$idTag.'">';

        return $control;
    }

    /**
     * pubstatesControl.
     *
     * @param string $selected The key that is selected
     * @param string $name     The name for the field
     * @param string $label    Param
     * @param string $desc     Param
     * @param array  $attribs  Additional HTML attributes for the <select> tag
     * @param string $idTag    Param
     *
     * @return string HTML
     */
    public static function pubstatesControl($selected = null, $name = 'type', $label = null, $desc = null, $attribs = [], $idTag = null)
    {
        if (!$idTag) {
            $idTag = EHtml::generateIdTag();
        }

        $control = self::pubstates($selected, $name, $attribs, $idTag);

        return EHtml::genericControl(
            JText::_($label),
            JText::_($desc),
            $name,
            $control
        );
    }

    /**
     * tasktypes.
     *
     * @param string $selected The key that is selected
     * @param string $name     The name for the field
     * @param array  $attribs  Additional HTML attributes for the <select> tag*
     *
     * @return string HTML
     */
    public static function tasktypes($selected = null, $name = 'type', $attribs = [])
    {
        $options = [];
        $options[] = JHTML::_('select.option', null, '-'.JText::_('JSELECT').'-');

        $options[] = JHTML::_('select.option', '1', JText::_('COM_AUTOTWEET_TASKS_TYPE_1'));
        $options[] = JHTML::_('select.option', '2', JText::_('COM_AUTOTWEET_TASKS_TYPE_2'));
        $options[] = JHTML::_('select.option', '3', JText::_('COM_AUTOTWEET_TASKS_TYPE_3'));
        $options[] = JHTML::_('select.option', '4', JText::_('COM_AUTOTWEET_TASKS_TYPE_4'));
        $options[] = JHTML::_('select.option', '5', JText::_('COM_AUTOTWEET_TASKS_TYPE_5'));
        $options[] = JHTML::_('select.option', '6', JText::_('COM_AUTOTWEET_TASKS_TYPE_6'));
        $options[] = JHTML::_('select.option', '7', JText::_('COM_AUTOTWEET_TASKS_TYPE_7'));

        return EHtmlSelect::customGenericList($options, $name, $attribs, $selected, $name);
    }

    /**
     * Method to getPubstateEnum.
     *
     * @return array
     */
    public static function getPubstateEnum()
    {
        return ['success', 'error', 'approve', 'cronjob', 'cancelled'];
    }

    /**
     * Method to getAutopublishEnum.
     *
     * @return array
     */
    public static function getAutopublishEnum()
    {
        return ['selected', 'on', 'off', 'cancel'];
    }

    /**
     * Method to getShowUrlEnum.
     *
     * @return array
     */
    public static function getShowUrlEnum()
    {
        return ['selected', 'off', 'beginning_of_message', 'end_of_message'];
    }

    /**
     * Method to getShowStaticTextEnum.
     *
     * @return array
     */
    public static function getShowStaticTextEnum()
    {
        return ['off', 'beginning_of_message', 'end_of_message'];
    }

    /**
     * Method to getTextForEnum.
     *
     * @param string $enum_string Param
     * @param bool   $with_icon   Param
     * @param bool   $isModule    Param
     *
     * @return string
     */
    public static function getTextForEnum($enum_string, $with_icon = false, $isModule = false)
    {
        switch ($enum_string) {
            case 'selected':
                $result = '-'.JText::_('JSELECT').'-';

                break;
            case 'default':
                $result = JText::_('JDEFAULT');

                break;
            case 'on':
                $result = JText::_('JON');

                break;
            case 'off':
                $result = JText::_('JOFF');

                break;
            case 'cancel':
                $result = JText::_('JCANCEL');

                break;
            case self::MEDIA_MODE_TEXT_ONLY_POST:
                $result = JText::_('COM_AUTOTWEET_OPTION_MEDIAMODE_MESSAGE_TEXT_ONLY');

                break;
            case self::MEDIA_MODE_POST_WITH_IMAGE:
                $result = JText::_('COM_AUTOTWEET_OPTION_MEDIAMODE_MESSAGE_WITH_IMAGE');

                    // no break
            case 'beginning_of_message':
                $result = JText::_('COM_AUTOTWEET_OPTION_POSITION_BEGINNINGOFMESSAGE');

                break;
            case 'end_of_message':
                $result = JText::_('COM_AUTOTWEET_OPTION_POSITION_ENDOFMESSAGE');

                break;
            case 'success':
                $result = ($with_icon ? '<i class="xticon fas fa-check text-success"></i> - ' : '').($isModule ? '' : JText::_('COM_AUTOTWEET_STATE_PUBSTATE_SUCCESS'));

                break;
            case 'error':
                $result = ($with_icon ? ' <i class="xticon fas fa-times text-error"></i> - ' : '').($isModule ? '' : JText::_('COM_AUTOTWEET_STATE_PUBSTATE_ERROR'));

                break;
            case 'approve':
                $result = ($with_icon ? ' <i class="xticon far fa-square"></i> - ' : '').($isModule ? '' : JText::_('COM_AUTOTWEET_STATE_PUBSTATE_APPROVE'));

                break;
            case 'cronjob':
                $result = ($with_icon ? ' <i class="xticon far fa-clock"></i> - ' : '').($isModule ? '' : JText::_('COM_AUTOTWEET_STATE_PUBSTATE_CRONJOB'));

                break;
            case 'cancelled':
                $result = ($with_icon ? ' <i class="xticon far fa-thumbs-down muted"></i> - ' : '').($isModule ? '' : JText::_('COM_AUTOTWEET_STATE_PUBSTATE_CANCELLED'));

                break;
            case 'feedcontent':
                $result = 'Joomla Content (Articles)';

                break;
            default:
                $result = 'AUTOTWEET_MISSING_LANGUAGE_STRING';

                break;
        }

        return $result;
    }

    /**
     * fbchannels.
     *
     * @param string $selected     The key that is selected
     * @param string $name         The name for the field
     * @param array  $attribs      Additional HTML attributes for the <select> tag*
     * @param string $app_id       Params
     * @param string $secret       Params
     * @param string $access_token Params
     * @param int    $channelType  Params
     * @param int    $channelId    Params
     *
     * @return string HTML
     */
    public static function fbchannels(
        $selected = null,
        $name = 'xtform[fbchannel_id]',
        $attribs = [],
        $app_id = null,
        $secret = null,
        $access_token = null,
        $channelType = null,
        $channelId = null
    ) {
        $options = [];
        $attribs = [];

        if ($access_token) {
            try {
                $fbAppHelper = new FbAppHelper($app_id, $secret, $access_token);

                if ($fbAppHelper->login()) {
                    $channels = $fbAppHelper->getChannels();
                    $icon = XTF0FModel::getTmpInstance('Channeltypes', 'AutoTweetModel')
                        ->getIcon(AutotweetModelChanneltypes::TYPE_FB_CHANNEL);

                    foreach ($channels as $channel) {
                        $nm = $channel['name'];

                        if ((empty($nm)) || ('null' === $nm)) {
                            $nm = $channel['id'];
                        }

                        $opt = JHTML::_('select.option', $channel['id'], $channel['type'].': '.$nm);

                        if (array_key_exists('access_token', $channel)) {
                            $opt->access_token = [
                                'access_token' => $channel['access_token'],
                                'social_icon' => $icon,
                                'social_url' => $channel['url'],
                                'data_type' => $channel['type'],
                            ];
                        }

                        $options[] = $opt;
                    }

                    $attribs['id'] = $name;
                    $attribs['list.attr'] = null;
                    $attribs['list.translate'] = false;
                    $attribs['option.key'] = 'value';
                    $attribs['option.text'] = 'text';
                    $attribs['list.select'] = $selected;
                    $attribs['option.attr'] = 'access_token';

                    return EHtmlSelect::genericlist($options, $name, $attribs);
                }

                $error_message = 'Facebook Login Failed!';
                $options[] = JHTML::_('select.option', '', $error_message);
            } catch (Exception $e) {
                $error_message = $e->getMessage();
                $options[] = JHTML::_('select.option', '', $error_message);
            }
        } else {
            $options[] = JHTML::_('select.option', null, '-'.JText::_('JSELECT').'-');
        }

        return EHtmlSelect::customGenericList($options, $name, $attribs, $selected, $name, ['option.attr' => 'access_token']);
    }

    /**
     * ligroups.
     *
     * @param string $selected          The key that is selected
     * @param string $name              The name for the field
     * @param array  $attribs           Additional HTML attributes for the <select> tag*
     * @param string $api_key           Params
     * @param string $secret_key        Params
     * @param string $oauth_user_token  Params
     * @param string $oauth_user_secret Params
     *
     * @return string HTML
     */
    public static function ligroups(
        $selected = null,
        $name = 'xtform[group_id]',
        $attribs = [],
        $api_key = null,
        $secret_key = null,
        $oauth_user_token = null,
        $oauth_user_secret = null
    ) {
        $options = [];
        $attribs = [];

        if (!empty($oauth_user_secret)) {
            try {
                $liAppHelper = new LiAppHelper($api_key, $secret_key, $oauth_user_token, $oauth_user_secret);

                if ($liAppHelper->login()) {
                    $channels = $liAppHelper->getMyGroups();

                    if ((count($channels) > 0) && ($channels[0])) {
                        $icon = XTF0FModel::getTmpInstance('Channeltypes', 'AutoTweetModel')
                            ->getIcon(AutotweetModelChanneltypes::TYPE_LIGROUP_CHANNEL);

                        foreach ($channels as $channel) {
                            $nm = $channel->name;

                            if ((empty($nm)) || ('null' === $nm)) {
                                $nm = $channel->id;
                            }

                            $attr = 'social_url="'.$channel->url.'" social_icon="'.$icon.'"';
                            $attrs = [
                                'attr' => $attr,
                                'option.attr' => 'social_url',

                                'option.key' => 'value',
                                'option.text' => 'text',
                                'disable' => false,
                            ];

                            $opt = JHTML::_('select.option', $channel->id, $nm, $attrs);

                            $options[] = $opt;
                        }
                    }

                    $attribs['id'] = $name;
                    $attribs['list.attr'] = null;
                    $attribs['list.translate'] = false;
                    $attribs['option.key'] = 'value';
                    $attribs['option.text'] = 'text';
                    $attribs['option.attr'] = 'social_url';
                    $attribs['list.select'] = $selected;

                    return EHtmlSelect::genericlist($options, $name, $attribs);
                }

                $error_message = 'LinkedIn Login Failed (Groups)!';
                $options[] = JHTML::_('select.option', '', $error_message);
            } catch (Exception $e) {
                $error_message = $e->getMessage();
                $options[] = JHTML::_('select.option', '', $error_message);
            }
        } else {
            $options[] = JHTML::_('select.option', null, '-'.JText::_('JSELECT').'-');
        }

        return EHtmlSelect::customGenericList($options, $name, $attribs, $selected, $name, ['option.attr' => 'access_token']);
    }

    /**
     * lioauth2companies.
     *
     * @param string $selected   The key that is selected
     * @param string $name       The name for the field
     * @param array  $attribs    Additional HTML attributes for the <select> tag*
     * @param string $channel_id Params
     *
     * @return string HTML
     */
    public static function lioauth2companies(
        $selected = null,
        $name = 'xtform[company_id]',
        $attribs = [],
        $channel_id = null
    ) {
        $options = [];
        $attribs = [];

        if (!empty($channel_id)) {
            try {
                $channel = XTF0FTable::getAnInstance('Channel', 'AutoTweetTable');
                $result = $channel->load($channel_id);

                if (!$result) {
                    throw new Exception('LinkedIn OAuth2 '.JText::_('COM_AUTOTWEET_CHANNEL_NOTLOADED'));
                }

                $lioauth2ChannelHelper = new LiOAuth2CompanyChannelHelper($channel);
                $channels = $lioauth2ChannelHelper->getMyCompanies();

                if ((count($channels) > 0) && ($channels[0])) {
                    $icon = XTF0FModel::getTmpInstance('Channeltypes', 'AutoTweetModel')
                        ->getIcon(AutotweetModelChanneltypes::TYPE_LIOAUTH2COMPANY_CHANNEL);

                    foreach ($channels as $channel) {
                        $nm = $channel->name;

                        if ((empty($nm)) || ('null' === $nm)) {
                            $nm = $channel->id;
                        }

                        $attr = 'social_url="'.$channel->url.'" social_icon="'.$icon.'"';
                        $attrs = [
                            'attr' => $attr,
                            'option.attr' => 'social_url',

                            'option.key' => 'value',
                            'option.text' => 'text',
                            'disable' => false,
                        ];

                        $opt = JHTML::_('select.option', $channel->id, $nm, $attrs);
                        $options[] = $opt;
                    }
                }

                $attribs['id'] = $name;
                $attribs['list.attr'] = null;
                $attribs['list.translate'] = false;
                $attribs['option.key'] = 'value';
                $attribs['option.text'] = 'text';
                $attribs['option.attr'] = 'social_url';
                $attribs['list.select'] = $selected;

                return EHtmlSelect::genericlist($options, $name, $attribs);
            } catch (Exception $e) {
                $error_message = $e->getMessage();
                $options[] = JHTML::_('select.option', '', $error_message);
            }
        } else {
            $options[] = JHTML::_('select.option', null, '-'.JText::_('JSELECT').'-');
        }

        return EHtmlSelect::customGenericList($options, $name, $attribs, $selected, $name, ['option.attr' => 'access_token']);
    }

    /**
     * category.
     *
     * @param string $name       The key that is selected
     * @param string $extension  The name for the field
     * @param string $selected   Additional HTML attributes for the <select> tag*
     * @param string $javascript Params
     * @param string $order      Params
     * @param string $size       Params
     * @param string $sel_cat    Params
     * @param string $readonly   Params
     *
     * @return string HTML
     */
    public static function category($name, $extension, $selected = null, $javascript = null, $order = null, $size = 1, $sel_cat = 1, $readonly = false)
    {
        $categories = JHtml::_('category.options', $extension);

        if ($sel_cat) {
            array_unshift($categories, JHTML::_('select.option', '0', JText::_('JOption_Select_Category')));
        }

        $category = JHTML::_(
            'select.genericlist',
            $categories,
            $name,
            ($readonly ? 'readonly="readonly" ' : '').'class="inputbox" size="'.$size.'" '.$javascript,
            'value',
            'text',
            $selected
        );

        return $category;
    }

    /**
     * languages.
     *
     * @param string $selected    The key that is selected
     * @param string $name        The name for the field
     * @param array  $attribs     Additional HTML attributes for the <select> tag*
     * @param string $show_select Params
     *
     * @return string HTML
     */
    public static function languages($selected = null, $name = 'language', $attribs = [], $show_select = false)
    {
        $languages = JLanguageHelper::getLanguages('lang_code');
        $options = [];

        if ($show_select) {
            $options[] = JHTML::_('select.option', '', '---');
        }

        $options[] = JHTML::_('select.option', '*', JText::_('JALL_LANGUAGE'));

        if (!empty($languages)) {
            foreach ($languages as $key => $lang) {
                $options[] = JHTML::_('select.option', $key, $lang->title);
            }
        }

        return EHtmlSelect::customGenericList($options, $name, $attribs, $selected, $name);
    }

    /**
     * fbcities.
     *
     * @param string $name The name for the field
     *
     * @return string HTML
     */
    public static function fbselect($name = 'fbcities')
    {
        $options = [];
        $attribs = [];

        $options[] = JHTML::_('select.option', null, '-'.JText::_('JSELECT').'-');

        $selected = null;

        return EHtmlSelect::customGenericList($options, $name, $attribs, $selected, $name, ['option.attr' => 'access_token']);
    }

    /**
     * contenttypes.
     *
     * @param string $selected The key that is selected
     * @param string $name     The name for the field
     * @param array  $attribs  Additional HTML attributes for the <select> tag*
     *
     * @return string HTML
     */
    public static function contenttypes($selected = null, $name = 'contenttypes', $attribs = [])
    {
        $options = [];

        $selected = ($selected ?: 'on');

        // Get media modes
        $modes = self::getContenttypesEnum();

        $options[] = JHTML::_('select.option', null, '-'.JText::_('JSELECT').'-');

        // Generate html
        foreach ($modes as $mode) {
            $options[] = JHtml::_('select.option', $mode, JText::_(self::getTextForEnum($mode)), 'value', 'text');
        }

        return EHtmlSelect::customGenericList($options, $name, $attribs, $selected, $name);
    }

    /**
     * Method to getFeedtypeEnum.
     *
     * @return array
     */
    public static function getContenttypesEnum()
    {
        if (PERFECT_PUB_PRO) {
            return ['feedcontent'];
        }

        return ['feedcontent'];
    }

    /**
     * getContenttypesName.
     *
     * @param string $value Param
     *
     * @return string
     */
    public static function getContenttypesName($value)
    {
        return JText::_(self::getTextForEnum($value));
    }

    /**
     * saveauthors.
     *
     * @param string $selected The key that is selected
     * @param string $name     The name for the field
     * @param array  $attribs  Additional HTML attributes for the <select> tag*
     *
     * @return string HTML
     */
    public static function saveauthors($selected = null, $name = 'saveauthors', $attribs = [])
    {
        $options = [];
        $options[] = JHTML::_('select.option', 0, JText::_('JOPTION_USE_DEFAULT'));

        $options[] = JHTML::_('select.option', 1, JText::_('Use default alias'));
        $options[] = JHTML::_('select.option', 2, JText::_('Use custom alias'));
        $options[] = JHTML::_('select.option', 3, JText::_('Use feed author alias, or title'));
        $options[] = JHTML::_('select.option', 4, JText::_('Use feed author alias, or custom'));

        return EHtmlSelect::customGenericList($options, $name, $attribs, $selected, $name);
    }

    /**
     * authorarticles.
     *
     * @param string $selected The key that is selected
     * @param string $name     The name for the field
     * @param array  $attribs  Additional HTML attributes for the <select> tag*
     *
     * @return string HTML
     */
    public static function authorarticles($selected = null, $name = 'authorarticles', $attribs = [])
    {
        $options = [];
        $options[] = ['name' => 'No', 'value' => 'no'];
        $options[] = ['name' => 'Top', 'value' => 'top'];
        $options[] = ['name' => 'Bottom', 'value' => 'bottom'];

        return EHtmlSelect::btnGroupList($selected, $name, $attribs, $options, null);
    }

    /**
     * feedCategories.
     *
     * @param string $contenttype_id The contenttype
     * @param string $selected       The key that is selected
     * @param string $name           The name for the field
     * @param array  $attribs        Additional HTML attributes for the <select> tag*
     * @param string $idTag          The id for the field
     *
     * @return string HTML
     */
    public static function feedCategories($contenttype_id, $selected = null, $name = 'catid', $attribs = [], $idTag = null)
    {
        $categories = [];

        $items = JHtml::_('category.options', 'com_content');

        $c = [];
        foreach ($items as $item) {
            $c[] = [
                'id' => $item->value,
                'title' => $item->text,
            ];
        }

        $categories['feedcontent'] = $c;
        ScriptHelper::addScriptDeclaration('var feedCategories = '.json_encode($categories).';');

        $options = array_merge(
            [JHTML::_('select.option', null, '-'.JText::_('JSELECT').'-')],
            $items
        );

        return EHtmlSelect::customGenericList($options, $name, $attribs, $selected, $idTag);
    }

    /**
     * vkgroups.
     *
     * @param string $selected     The key that is selected
     * @param string $name         The name for the field
     * @param array  $attribs      Additional HTML attributes for the <select> tag*
     * @param string $access_token Params
     * @param int    $channel_id   Params
     *
     * @return string HTML
     */
    public static function vkgroups(
        $selected = null,
        $name = 'xtform[vkgroup_id]',
        $attribs = [],
        $access_token = null,
        $channel_id = null
    ) {
        $options = [];
        $attribs = [];

        if ((!empty($access_token)) && (!empty($channel_id))) {
            $options[] = JHTML::_('select.option', null, '-'.JText::_('JSELECT').'-');

            try {
                $ch = XTF0FTable::getAnInstance('Channel', 'AutoTweetTable');
                $result = $ch->load($channel_id);

                if (!$result) {
                    return null;
                }

                $params = $ch->params;
                $registry = new JRegistry();
                $registry->loadString($params);
                $registry->set('access_token', $access_token);
                $ch->bind(['params' => (string) $registry]);

                $vkChannelHelper = new VkChannelHelper($ch);
                $result = $vkChannelHelper->getGroups();

                if ($result['status']) {
                    $groups = $result['items'];

                    $icon = XTF0FModel::getTmpInstance('Channeltypes', 'AutoTweetModel')
                        ->getIcon(AutotweetModelChanneltypes::TYPE_VK_CHANNEL);

                    foreach ($groups as $group) {
                        $nm = $group['name'];

                        if ((empty($nm)) || ('null' === $nm)) {
                            $nm = $group['gid'];
                        }

                        $attr = 'social_url="'.$group['url'].'" social_icon="'.$icon.'"';
                        $attrs = [
                            'attr' => $attr,
                            'option.attr' => 'social_url',

                            'option.key' => 'value',
                            'option.text' => 'text',
                            'disable' => false,
                        ];

                        $opt = JHTML::_('select.option', $group['gid'], $nm, $attrs);
                        $options[] = $opt;
                    }
                }

                $attribs['id'] = $name;
                $attribs['list.attr'] = null;
                $attribs['list.translate'] = false;
                $attribs['option.key'] = 'value';
                $attribs['option.text'] = 'text';
                $attribs['option.attr'] = 'social_url';
                $attribs['list.select'] = $selected;

                return EHtmlSelect::genericlist($options, $name, $attribs);
            } catch (Exception $e) {
                $error_message = $e->getMessage();
                $options[] = JHTML::_('select.option', '', $error_message);
            }
        } else {
            $options[] = JHTML::_('select.option', null, '-'.JText::_('JSELECT').'-');
        }

        return EHtmlSelect::customGenericList($options, $name, $attribs, $selected, $name, ['option.attr' => 'access_token']);
    }

    /**
     * workingDays.
     *
     * @param string $name     The name for the field
     * @param string $selected The key that is selected
     * @param string $label    Param
     * @param array  $desc     Param
     * @param string $idtag    Param
     *
     * @return string HTML
     */
    public static function workingDaysControl($name, $selected, $label, $desc, $idtag = null)
    {
        $data = [];

        $data[] = JHTML::_('select.option', 0, 'SUNDAY');
        $data[] = JHTML::_('select.option', 1, 'MONDAY');
        $data[] = JHTML::_('select.option', 2, 'TUESDAY');
        $data[] = JHTML::_('select.option', 3, 'WEDNESDAY');
        $data[] = JHTML::_('select.option', 4, 'THURSDAY');
        $data[] = JHTML::_('select.option', 5, 'FRIDAY');
        $data[] = JHTML::_('select.option', 6, 'SATURDAY');

        echo EHtmlSelect::checkboxListControl(
            $data,
            $name,
            null,
            $selected,
            $label,
            $desc,
            $idtag
        );
    }

    /**
     * evergreenTypeControl.
     *
     * @param string $name     The name for the field
     * @param string $selected The key that is selected
     * @param string $label    Param
     * @param array  $desc     Param
     * @param string $idtag    Param
     *
     * @return string HTML
     */
    public static function evergreenTypeControl($name, $selected, $label, $desc, $idtag = null)
    {
        $options = [];
        $options[] = JHTML::_('select.option', '1', JText::_('COM_AUTOTWEET_EVERGREEN_TYPE_RANDOM'));

        /*
        $options[] = JHTML::_('select.option', '2', JText::_('COM_AUTOTWEET_EVERGREEN_TYPE_RANDOM_HITS'));
        $options[] = JHTML::_('select.option', '3', JText::_('COM_AUTOTWEET_EVERGREEN_TYPE_RANDOM_DATER'));
        $options[] = JHTML::_('select.option', '4', JText::_('COM_AUTOTWEET_EVERGREEN_TYPE_RANDOM_DATEO'));
        */

        $options[] = JHTML::_('select.option', '5', JText::_('COM_AUTOTWEET_EVERGREEN_TYPE_SEQUENCE'));

        return EHtmlSelect::customGenericListControl($options, $name, [], $selected, $label, $desc, $idtag);
    }

    /**
     * Method to create a clickable icon to change the state of an item.
     *
     * @param mixed $value    Either the scalar value or an object (for backward compatibility, deprecated)
     * @param int   $i        The index
     * @param bool  $withLink Param
     *
     * @return string
     */
    public static function processedWithIcons($value, $i, $withLink = null)
    {
        if (is_object($value)) {
            $value = $value->published;
        }

        $img = $value ? self::REQ_ICON_YES : self::REQ_ICON_NO;

        if (null === $withLink) {
            $platform = XTF0FPlatform::getInstance();
            $input = new \Joomla\CMS\Input\Input($_REQUEST);
            $withLink = $platform->authorise('core.edit.state', $input->getCmd('option', 'com_foobar'));
        }

        if (!$withLink) {
            return $img;
        }

        $task = $value ? 'unpublish' : 'publish';
        $alt = $value ? JText::_('JPUBLISHED') : JText::_('JUNPUBLISHED');
        $action = $value ? JText::_('JLIB_HTML_UNPUBLISH_ITEM') : JText::_('JLIB_HTML_PUBLISH_ITEM');
        $href = '<a href="#" onclick="return Joomla.listItemTask(\'cb'.$i.'\',\''.$task.'\')" title="'.$action.'">'.$img.'</a>';

        return $href;
    }

    /**
     * sharedWith.
     *
     * @param string $selected The key that is selected
     * @param string $name     The name for the field
     * @param array  $attribs  Additional HTML attributes for the <select> tag*
     *
     * @return string HTML
     */
    public static function sharedWith($selected = 'EVERYONE', $name = 'sharedwith', $attribs = [])
    {
        $options = [];

        if (PERFECT_PUB_PRO) {
            $shares = ['EVERYONE', 'ALL_FRIENDS', 'FRIENDS_OF_FRIENDS', 'SELF'];
        } else {
            $shares = ['EVERYONE'];
        }

        // Generate html
        foreach ($shares as $share) {
            $options[] = JHtml::_('select.option', $share, JText::_('COM_AUTOTWEET_VIEW_CHANNEL_SHARED_'.$share), 'value', 'text');
        }

        return EHtmlSelect::customGenericList($options, $name, $attribs, $selected, $name);
    }

    /**
     * tumblrBlogs.
     *
     * @param array  $blogs    Params
     * @param string $name     The name for the field
     * @param array  $attribs  Additional HTML attributes for the <select> tag
     * @param string $selected The key that is selected
     * @param string $idTag    Params
     *
     * @return string HTML
     */
    public static function tumblrBlogs($blogs, $name, $attribs = [], $selected = null, $idTag = null)
    {
        $blogs = self::tumblrOptions($blogs);

        $options = [];
        $options[] = JHTML::_('select.option', null, '-'.JText::_('JSELECT').'-');

        foreach ($blogs as $blog) {
            $options[] = JHTML::_('select.option', $blog->id, $blog->name);
        }

        return EHtmlSelect::customGenericList($options, $name, $attribs, $selected, $idTag);
    }

    /**
     * tumblrOptions.
     *
     * @param array $blogs Params
     *
     * @return string HTML
     */
    public static function tumblrOptions($blogs)
    {
        $options = [];

        foreach ($blogs as $blog) {
            $uri = new JUri();
            $uri->parse($blog->url);

            $option = new stdClass();
            $option->id = $uri->getHost();
            $option->name = $blog->name;
            $options[] = $option;
        }

        return $options;
    }

    /**
     * tumblrPostTypes.
     *
     * @param string $selected The key that is selected
     * @param string $name     The name for the field
     * @param array  $attribs  Additional HTML attributes for the <select> tag*
     * @param string $idTag    The id for the field
     *
     * @return string HTML
     */
    public static function tumblrPostTypes($selected = null, $name = 'posttype', $attribs = [], $idTag = false)
    {
        // Text, photo, quote, link, chat, audio, video

        $options = [];
        $options[] = [
            'name' => 'Text',
            'value' => 'text',
        ];
        $options[] = [
            'name' => 'Photo',
            'value' => 'photo',
        ];
        $options[] = [
            'name' => 'Link',
            'value' => 'link',
        ];

        return EHtmlSelect::btngrouplist($selected, $name, $attribs, $options, $idTag);
    }

    /**
     * bloggerBlogs.
     *
     * @param array  $blogs    Params
     * @param string $name     The name for the field
     * @param array  $attribs  Additional HTML attributes for the <select> tag
     * @param string $selected The key that is selected
     * @param string $idTag    Params
     *
     * @return string HTML
     */
    public static function bloggerBlogs($blogs, $name, $attribs = [], $selected = null, $idTag = null)
    {
        $options = [];
        $options[] = JHTML::_('select.option', null, '-'.JText::_('JSELECT').'-');
        $items = $blogs['items'];

        foreach ($items as $blog) {
            $options[] = JHTML::_('select.option', $blog['id'], $blog['name']);
        }

        return EHtmlSelect::customGenericList($options, $name, $attribs, $selected, $idTag);
    }

    /**
     * pinterestBoards.
     *
     * @param array  $boards   Params
     * @param string $name     The name for the field
     * @param array  $attribs  Additional HTML attributes for the <select> tag
     * @param string $selected The key that is selected
     * @param string $idTag    Params
     *
     * @return string HTML
     */
    public static function pinterestBoards($boards, $name, $attribs = [], $selected = null, $idTag = null)
    {
        $options = [];
        $options[] = JHTML::_('select.option', null, '-'.JText::_('JSELECT').'-');

        foreach ($boards as $board) {
            $options[] = JHTML::_('select.option', $board->id, $board->name);
        }

        return EHtmlSelect::customGenericList($options, $name, $attribs, $selected, $idTag);
    }

    /**
     * easySocialTargets.
     *
     * @param array  $esTargets Params
     * @param string $name      The name for the field
     * @param array  $attribs   Additional HTML attributes for the <select> tag
     * @param string $selected  The key that is selected
     * @param string $idTag     Params
     *
     * @return string HTML
     */
    public static function easySocialTargets($esTargets, $name, $attribs = [], $selected = null, $idTag = null)
    {
        $options = [];
        $options[] = JHTML::_('select.option', null, '- '.JText::_('JALL').' -');

        if (!empty($esTargets)) {
            foreach ($esTargets as $target) {
                $options[] = JHTML::_('select.option', $target->id, $target->title);
            }
        }

        return EHtmlSelect::customGenericList($options, $name, $attribs, $selected, $idTag);
    }

    /**
     * myBusinessLocations.
     *
     * @param array  $locations Params
     * @param string $name      The name for the field
     * @param array  $attribs   Additional HTML attributes for the <select> tag
     * @param string $selected  The key that is selected
     * @param string $idTag     Params
     *
     * @return string HTML
     */
    public static function myBusinessLocations($locations, $name, $attribs = [], $selected = null, $idTag = null)
    {
        $options = [];
        $options[] = JHTML::_('select.option', null, '-'.JText::_('JSELECT').'-');

        foreach ($locations as $locations) {
            $options[] = JHTML::_('select.option', $locations['name'], $locations['locationName']);
        }

        return EHtmlSelect::customGenericList($options, $name, $attribs, $selected, $idTag);
    }

    /**
     * _loadChannels.
     *
     * @param bool $isModule Param
     */
    private static function _loadChannels($isModule = false)
    {
        if (null !== self::$cache_channels) {
            return;
        }

        self::$cache_channels = [];
        self::$cache_channels_type = [];

        XTF0FModel::getTmpInstance('Channeltypes', 'AutotweetModel');
        $itemsModel = XTF0FModel::getTmpInstance('Channels', 'AutotweetModel');
        $itemsModel->set('published', true);

        $items = $itemsModel->getItemList(true);

        if (empty($items)) {
            return;
        }

        foreach ($items as $item) {
            self::$cache_channels_type[$item->id] = $item->channeltype_id;
            $icon = AutotweetModelChanneltypes::getIcon($item->channeltype_id);

            if ($isModule) {
                self::$cache_channels[$item->id] = $icon;
            } else {
                self::$cache_channels[$item->id] = $icon.' - '.$item->name;
            }
        }
    }
}
