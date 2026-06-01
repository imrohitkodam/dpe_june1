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
final class PostShareManager
{
    // States of post and for publish_all
    // New message is ready for sending
    public const POST_SUCCESS = 'success';
    public const POST_ERROR = 'error';
    public const POST_APPROVE = 'approve';
    public const POST_CRONJOB = 'cronjob';
    public const POST_CANCELLED = 'cancelled';

    // Static Text modes
    public const STATICTEXT_OFF = 'off';
    public const STATICTEXT_BEGINNING = 'beginning_of_message';
    public const STATICTEXT_END = 'end_of_message';

    // Url mode
    public const SHOWURL_OFF = 'off';
    public const SHOWURL_BEGINNING = 'beginning_of_message';
    public const SHOWURL_END = 'end_of_message';

    public const POSTTHIS_DEFAULT = 1;
    public const POSTTHIS_NO = 2;
    public const POSTTHIS_YES = 3;
    public const POSTTHIS_IMMEDIATELY = 4;
    public const POSTTHIS_ONLYONCE = 5;

    // Internal Code to retrieve any evergreen, even not processed
    public const POSTTHIS_YES_ALL = 44;

    private function __construct()
    {
    }

    /**
     * getCheckDate.
     *
     * Get msgs from queue (sending is allowed only, when publish date is not in the future)
     * Sub 1 minute to avoid problems when automator plugin and extension plugin are
     * executed at the same time...
     *
     * @return JDate
     */
    public static function getCheckDate()
    {
        $now = \Joomla\CMS\Factory::getDate();
        $check_date = $now->toUnix();
        $mincheck_time_intval = EParameter::getComponentParam(CAUTOTWEETNG, 'mincheck_time_intval', 60);
        $check_date = $check_date - $mincheck_time_intval;
        $check_date = \Joomla\CMS\Factory::getDate($check_date);

        return $check_date;
    }

    /**
     * postQueuedMessages.
     *
     * @param int $max Param
     *
     * @return bool
     */
    public static function postQueuedMessages($max)
    {
        $now = \Joomla\CMS\Factory::getDate();
        $logger = AutotweetLogger::getInstance();

        if ((PERFECT_PUB_PRO) && (!VirtualManager::isWorking($now))) {
            $logger->log(\Joomla\CMS\Log\Log::INFO, 'PostShareManager - VM not working now '.$now->toISO8601(true));

            return false;
        }

        $check_date = self::getCheckDate();
        $requests = RequestHelper::getRequestList($check_date, $max);

        $sharingHelper = SharingHelper::getInstance();

        $logger->log(\Joomla\CMS\Log\Log::INFO, 'postQueuedMessages Requests: '.count($requests));

        foreach ($requests as $request) {
            $result = false;

            $message = null;

            try {
                $result = $sharingHelper->publishRequest($request);
            } catch (Exception $e) {
                $message = $e->getMessage();
                $logger->log(\Joomla\CMS\Log\Log::ERROR, 'postQueuedMessages: Exception! '.$message);
            }

            if ($result) {
                RequestHelper::processed($request->id);
            } else {
                RequestHelper::saveError($request->id, $message);
            }
        }

        if ((PERFECT_PUB_PRO)
            && ((empty($requests))
            || (EParameter::getComponentParam(CAUTOTWEETNG, 'force_evergreens', 0)))) {
            $logger->log(\Joomla\CMS\Log\Log::INFO, 'VirtualManager: anything else to publish?');

            VirtualManager::enqueueEvergreenMessage($max);
        }
    }

    public static function isPostThisEnabled($advancedAttrs, $refId)
    {
        if (!$advancedAttrs) {
            return false;
        }

        if (self::POSTTHIS_YES === (int) $advancedAttrs->postthis ||
            self::POSTTHIS_IMMEDIATELY === (int) $advancedAttrs->postthis) {
            return true;
        }

        if (self::POSTTHIS_ONLYONCE === (int) $advancedAttrs->postthis) {
            // Only checks if the post exists - (bool) RequestHelper::exists($refId);
            $requestExists = false;
            $postExists = (bool) PostHelper::exists($refId);

            return !$requestExists && !$postExists;
        }

        return false;
    }
}
