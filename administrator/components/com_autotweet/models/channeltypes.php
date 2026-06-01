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
 * AutotweetModelChannelTypes.
 *
 * @since       1.0
 */
class AutotweetModelChannelTypes extends XTF0FModel
{
    public const TYPE_FB_CHANNEL = 2;

    // Const TYPE_FBEVENT_CHANNEL = 4;
    // Const TYPE_FBLINK_CHANNEL = 7;

    // const TYPE_FBPHOTO_CHANNEL = 8;

    // Const TYPE_FBVIDEO_CHANNEL = 9;
    // Const TYPE_LINK_CHANNEL = 5;

    public const TYPE_LIGROUP_CHANNEL = 6;

    // Const TYPE_LICOMPANY_CHANNEL = 10;

    public const TYPE_LIOAUTH2_CHANNEL = 20;
    public const TYPE_LIOAUTH2COMPANY_CHANNEL = 21;

    public const TYPE_MAIL_CHANNEL = 3;
    public const TYPE_TW_CHANNEL = 1;

    // Const TYPE_GPLUS_CHANNEL = 13;

    // PerfectPublisher PRO Channels
    // const TYPE_VK_CHANNEL = 11;
    public const TYPE_VKGROUP_CHANNEL = 12;
    public const TYPE_SCOOPIT_CHANNEL = 14;
    // const TYPE_XING_CHANNEL = 15;
    public const TYPE_TUMBLR_CHANNEL = 16;
    public const TYPE_BLOGGER_CHANNEL = 17;
    // const TYPE_JOMSOCIAL_CHANNEL = 18;
    public const TYPE_EASYSOCIAL_CHANNEL = 19;
    // const TYPE_FBINSTANT_CHANNEL = 22;
    public const TYPE_TELEGRAM_CHANNEL = 23;
    public const TYPE_MEDIUM_CHANNEL = 24;
    public const TYPE_PUSHWOOSH_WEB_CHANNEL = 25;
    public const TYPE_ONESIGNAL_WEB_CHANNEL = 26;
    public const TYPE_PUSHWOOSH_PUSH_CHANNEL = 27;
    public const TYPE_ONESIGNAL_PUSH_CHANNEL = 28;
    public const TYPE_PUSHALERT_PUSH_CHANNEL = 29;
    public const TYPE_PAGESPEED_CHANNEL = 30;
    public const TYPE_PINTEREST_CHANNEL = 31;
    public const TYPE_MYBUSINESS_CHANNEL = 32;
    public const TYPE_INSTAGRAM_CHANNEL = 33;
    public const TYPE_TELEGRAM_PHOTO_CHANNEL = 34;

    /**
     * getParamsForm.
     *
     * @param int $channeltypeid Param
     *
     * @return string
     */
    public static function getParamsForm($channeltypeid)
    {
        if (self::TYPE_BLOGGER_CHANNEL === (int) $channeltypeid) {
            return 'bloggerchannel';
        }

        if (self::TYPE_EASYSOCIAL_CHANNEL === (int) $channeltypeid) {
            return 'easysocialchannel';
        }

        if (self::TYPE_FB_CHANNEL === (int) $channeltypeid) {
            return 'fbchannel';
        }

        if (self::TYPE_LIGROUP_CHANNEL === (int) $channeltypeid) {
            return 'ligroupchannel';
        }

        if (self::TYPE_LIOAUTH2_CHANNEL === (int) $channeltypeid) {
            return 'lioauth2channel';
        }

        if (self::TYPE_LIOAUTH2COMPANY_CHANNEL === (int) $channeltypeid) {
            return 'lioauth2companychannel';
        }

        if (self::TYPE_MAIL_CHANNEL === (int) $channeltypeid) {
            return 'mailchannel';
        }

        if (self::TYPE_SCOOPIT_CHANNEL === (int) $channeltypeid) {
            return 'scoopitchannel';
        }

        if (self::TYPE_TW_CHANNEL === (int) $channeltypeid) {
            return 'twchannel';
        }

        if (self::TYPE_TELEGRAM_CHANNEL === (int) $channeltypeid) {
            return 'telegramchannel';
        }

        if (self::TYPE_MEDIUM_CHANNEL === (int) $channeltypeid) {
            return 'mediumchannel';
        }

        if (self::TYPE_PUSHWOOSH_WEB_CHANNEL === (int) $channeltypeid) {
            return 'pushwooshchannel';
        }

        if (self::TYPE_ONESIGNAL_WEB_CHANNEL === (int) $channeltypeid) {
            return 'onesignalchannel';
        }

        if (self::TYPE_PUSHWOOSH_PUSH_CHANNEL === (int) $channeltypeid) {
            return 'pushwooshchannel';
        }

        if (self::TYPE_TUMBLR_CHANNEL === (int) $channeltypeid) {
            return 'tumblrchannel';
        }

        if (self::TYPE_ONESIGNAL_PUSH_CHANNEL === (int) $channeltypeid) {
            return 'onesignalchannel';
        }

        if (self::TYPE_PUSHALERT_PUSH_CHANNEL === (int) $channeltypeid) {
            return 'pushalertchannel';
        }

        if (self::TYPE_PINTEREST_CHANNEL === (int) $channeltypeid) {
            return 'pinterestchannel';
        }

        if (self::TYPE_PAGESPEED_CHANNEL === (int) $channeltypeid) {
            return 'pagespeedchannel';
        }

        if (self::TYPE_MYBUSINESS_CHANNEL === (int) $channeltypeid) {
            return 'mybusinesschannel';
        }

        if (self::TYPE_INSTAGRAM_CHANNEL === (int) $channeltypeid) {
            return 'instagramchannel';
        }

        if (self::TYPE_TELEGRAM_PHOTO_CHANNEL === (int) $channeltypeid) {
            return 'telegramchannel';
        }

        return null;
    }

    /**
     * getIcon.
     *
     * @param int $channeltypeid Param
     *
     * @return string
     */
    public static function getIcon($channeltypeid)
    {
        $rawIcon = self::getRawIcon($channeltypeid);

        if ($rawIcon) {
            return '<i class=\'xticon '.$rawIcon.'\'></i>';
        }

        return '<i class=\'xticon fas fa-heart-broken\'></i>';
    }

    /**
     * getRawIcon.
     *
     * @param int $channeltypeid Param
     *
     * @return string
     */
    public static function getRawIcon($channeltypeid)
    {
        if (self::TYPE_BLOGGER_CHANNEL === (int) $channeltypeid) {
            return 'fab fa-google';
        }

        if (self::TYPE_EASYSOCIAL_CHANNEL === (int) $channeltypeid) {
            return 'fas fa-users';
        }

        if (self::TYPE_FB_CHANNEL === (int) $channeltypeid) {
            return 'fab fa-facebook-f';
        }

        if ((self::TYPE_LIGROUP_CHANNEL === (int) $channeltypeid)
            || (self::TYPE_LIOAUTH2_CHANNEL === (int) $channeltypeid)
            || (self::TYPE_LIOAUTH2COMPANY_CHANNEL === (int) $channeltypeid)) {
            return 'fab fa-linkedin-in';
        }

        if (self::TYPE_MAIL_CHANNEL === (int) $channeltypeid) {
            return 'far fa-envelope';
        }

        if (self::TYPE_SCOOPIT_CHANNEL === (int) $channeltypeid) {
            return 'fas fa-exclamation';
        }

        if (self::TYPE_TW_CHANNEL === (int) $channeltypeid) {
            return 'fab fa-twitter';
        }

        if (self::TYPE_TELEGRAM_CHANNEL === (int) $channeltypeid) {
            return 'fab fa-telegram-plane';
        }

        if (self::TYPE_MEDIUM_CHANNEL === (int) $channeltypeid) {
            return 'fab fa-medium-m';
        }

        if (self::TYPE_PUSHWOOSH_WEB_CHANNEL === (int) $channeltypeid) {
            return 'far fa-bell';
        }

        if (self::TYPE_ONESIGNAL_WEB_CHANNEL === (int) $channeltypeid) {
            return 'far fa-bell';
        }

        if (self::TYPE_PUSHWOOSH_PUSH_CHANNEL === (int) $channeltypeid) {
            return 'far fa-bell';
        }

        if (self::TYPE_ONESIGNAL_PUSH_CHANNEL === (int) $channeltypeid) {
            return 'far fa-bell';
        }

        if (self::TYPE_PUSHALERT_PUSH_CHANNEL === (int) $channeltypeid) {
            return 'far fa-bell';
        }

        if (self::TYPE_PAGESPEED_CHANNEL === (int) $channeltypeid) {
            return 'fab fa-google';
        }

        if (self::TYPE_PINTEREST_CHANNEL === (int) $channeltypeid) {
            return 'fab fa-pinterest-p';
        }

        if (self::TYPE_MYBUSINESS_CHANNEL === (int) $channeltypeid) {
            return 'fas fa-shopping-bag';
        }

        if (self::TYPE_TUMBLR_CHANNEL === (int) $channeltypeid) {
            return 'fab fa-tumblr';
        }

        if (self::TYPE_INSTAGRAM_CHANNEL === (int) $channeltypeid) {
            return 'fab fa-instagram';
        }

        if (self::TYPE_TELEGRAM_PHOTO_CHANNEL === (int) $channeltypeid) {
            return 'fab fa-telegram-plane';
        }

        return 'fas fa-heart-broken';
    }

    /**
     * getChannelClass.
     *
     * @param int $channeltypeid Param
     *
     * @return string
     */
    public static function getChannelClass($channeltypeid)
    {
        switch ($channeltypeid) {
            case self::TYPE_FB_CHANNEL:
                return 'FacebookChannelHelper';
            case self::TYPE_LIGROUP_CHANNEL:
                return 'LinkedinGroupChannelHelper';
            case self::TYPE_LIOAUTH2_CHANNEL:
                return 'LiOAuth2ChannelHelper';
            case self::TYPE_LIOAUTH2COMPANY_CHANNEL:
                return 'LiOAuth2CompanyChannelHelper';
            case self::TYPE_MAIL_CHANNEL:
                return 'MailChannelHelper';
            case self::TYPE_TW_CHANNEL:
                return 'TwitterChannelHelper';
            case self::TYPE_SCOOPIT_CHANNEL:
                return 'ScoopitChannelHelper';
            case self::TYPE_TUMBLR_CHANNEL:
                return 'TumblrChannelHelper';
            case self::TYPE_BLOGGER_CHANNEL:
                return 'BloggerChannelHelper';
            case self::TYPE_TELEGRAM_CHANNEL:
                return 'TelegramChannelHelper';
            case self::TYPE_MEDIUM_CHANNEL:
                return 'MediumChannelHelper';
            case self::TYPE_PUSHWOOSH_WEB_CHANNEL:
            case self::TYPE_PUSHWOOSH_PUSH_CHANNEL:
                return 'PushwooshChannelHelper';
            case self::TYPE_ONESIGNAL_WEB_CHANNEL:
            case self::TYPE_ONESIGNAL_PUSH_CHANNEL:
                return 'OneSignalChannelHelper';
            case self::TYPE_PUSHALERT_PUSH_CHANNEL:
                return 'PushAlertChannelHelper';
            case self::TYPE_PINTEREST_CHANNEL:
                return 'PinterestChannelHelper';
            case self::TYPE_PAGESPEED_CHANNEL:
                return 'PageSpeedChannelHelper';
            case self::TYPE_MYBUSINESS_CHANNEL:
                return 'MyBusinessChannelHelper';
            case self::TYPE_INSTAGRAM_CHANNEL:
                return 'InstagramChannelHelper';
            case self::TYPE_EASYSOCIAL_CHANNEL:
                return 'EasySocialChannelHelper';
            case self::TYPE_TELEGRAM_PHOTO_CHANNEL:
                return 'TelegramPhotoChannelHelper';
        }

        return null;
    }

    /**
     * buildQuery.
     *
     * @param bool $overrideLimits Param
     *
     * @return XTF0FQuery
     */
    public function buildQuery($overrideLimits = false)
    {
        $db = $this->getDBO();
        $query = parent::buildQuery($overrideLimits);
        $query->order('name REGEXP \'^[a-z]\' DESC, name');

        return $query;
    }

    /**
     * formatUrl.
     *
     * @param int    $channeltypeid Param
     * @param string $socialUrl     Param
     *
     * @return string
     */
    public static function formatUrl($channeltypeid, $socialUrl)
    {
        $socialIcon = self::getIcon($channeltypeid);

        return '<p><a href="'.$socialUrl.'" target="_blank">'.$socialIcon.' '.$socialUrl.'</a></p>';
    }

    /**
     * getAuthCallback.
     *
     * @param int $channelId Param
     *
     * @return string
     */
    public static function getAuthCallback($channelId)
    {
        $channeltypeid = XTF0FModel::getTmpInstance('Channels', 'AutoTweetModel')
            ->getItem($channelId)
            ->get('channeltype_id');

        switch ($channeltypeid) {
            case self::TYPE_BLOGGER_CHANNEL:
                return 'bloggerchannels';
            case self::TYPE_SCOOPIT_CHANNEL:
                return 'scoopitchannels';
            case self::TYPE_LIOAUTH2_CHANNEL:
                return 'lioauth2channels';
            case self::TYPE_LIOAUTH2COMPANY_CHANNEL:
                return 'lioauth2channels';
            case self::TYPE_PINTEREST_CHANNEL:
                return 'pinterestchannels';
            case self::TYPE_PAGESPEED_CHANNEL:
                return 'pagespeedchannels';
            case self::TYPE_MYBUSINESS_CHANNEL:
                return 'mybusinesschannels';
            case self::TYPE_TUMBLR_CHANNEL:
                return 'tumblrchannels';
        }

        return null;
    }

    /**
     * isProChannel.
     *
     * @param int $channelTypeId Param
     *
     * @return string
     */
    public static function isProChannel($channelTypeId)
    {
        switch ($channelTypeId) {
            case self::TYPE_BLOGGER_CHANNEL:
            case self::TYPE_EASYSOCIAL_CHANNEL:
            case self::TYPE_INSTAGRAM_CHANNEL:
            case self::TYPE_MEDIUM_CHANNEL:
            case self::TYPE_MYBUSINESS_CHANNEL:
            case self::TYPE_ONESIGNAL_PUSH_CHANNEL:
            case self::TYPE_ONESIGNAL_WEB_CHANNEL:
            case self::TYPE_PAGESPEED_CHANNEL:
            case self::TYPE_PINTEREST_CHANNEL:
            case self::TYPE_PUSHALERT_PUSH_CHANNEL:
            case self::TYPE_PUSHWOOSH_PUSH_CHANNEL:
            case self::TYPE_PUSHWOOSH_WEB_CHANNEL:
            case self::TYPE_SCOOPIT_CHANNEL:
            case self::TYPE_TELEGRAM_CHANNEL:
            case self::TYPE_TELEGRAM_PHOTO_CHANNEL:
            case self::TYPE_TUMBLR_CHANNEL:
                return true;
        }

        return false;
    }
}
