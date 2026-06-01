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
 * AutotweetAPI.
 *
 * @since       1.0
 */
abstract class AutotweetAPI
{
    public const PRIORITY_IMMEDIATE = 3;
    public const PRIORITY_NORMAL = 9;

    /**
     * insertRequest.
     *
     * @param int    $ref_id           Param
     * @param string $plugin           Param
     * @param date   $publish_up       Param
     * @param string $description      Param
     * @param int    $typeinfo         Param
     * @param string $url              Param
     * @param string $imageUrl        Param
     * @param json   &$native_object   Param
     * @param object &$advanced_attrs  Param
     * @param object &$params          Param
     * @param string $content_language Param
     * @param int    $priority         Param
     *
     * @return mixed (bool or request Id)
     */
    public static function insertRequest($ref_id, $plugin, $publish_up, $description, $typeinfo = 0, $url = '', $imageUrl = '', &$native_object = null, &$advanced_attrs = null, &$params = null, $content_language = null, $priority = self::PRIORITY_NORMAL)
    {
        if (0 === (int) $publish_up) {
            $publish_up = \Joomla\CMS\Factory::getDate()->toSql();
        }

        if ($advanced_attrs) {
            if (!empty($advanced_attrs->description)) {
                $description = $advanced_attrs->description;
            }

            if (!empty($advanced_attrs->hashtags)) {
                $description .= ' '.$advanced_attrs->hashtags;
            }

            if (PostShareManager::POSTTHIS_IMMEDIATELY === (int) $advanced_attrs->postthis) {
                $priority = self::PRIORITY_IMMEDIATE;
            }

            if (PostShareManager::POSTTHIS_NO === (int) $advanced_attrs->postthis) {
                // Post this or not
                return null;
            }

            if (($image = $advanced_attrs->image) && (!empty($image))) {
                // This image
                if ('none' === $image) {
                    $imageUrl = null;
                } else {
                    $imageUrl = $image;
                }
            }

            if ((isset($advanced_attrs->image_url)) && ($image = $advanced_attrs->image_url) && (!empty($image))) {
                $imageUrl = $image;
            }

            if (($agenda = $advanced_attrs->agenda) && (count($agenda) > 0)) {
                // The first date, it's the next date
                $publish_up = AdvancedAttributesHelper::getNextAgendaDate($agenda);

                if (empty($publish_up)) {
                    $logger = AutotweetLogger::getInstance();
                    $logger->log(\Joomla\CMS\Log\Log::INFO, 'insertRequest: ref_id='.$ref_id.' - No Next Agenda Date.');

                    return false;
                }
            }
        }

        $result = RequestHelper::insertRequest($ref_id, $plugin, $publish_up, $description, $typeinfo, $url, $imageUrl, $native_object, $advanced_attrs, $params, $content_language, $priority);

        return $result;
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
        return PostHelper::cancelPosts($ref_id, $plugin, $userid);
    }

    /**
     * cancelEvergreens.
     *
     * @param string $ref_id Param
     * @param string $plugin Param
     * @param int    $userid Param
     *
     * @return bool
     */
    public static function cancelEvergreens($ref_id, $plugin, $userid)
    {
        return RequestHelper::cancelEvergreens($ref_id, $plugin, $userid);
    }

    /**
     * cancelRequests.
     *
     * @param string $ref_id Param
     * @param string $plugin Param
     * @param int    $userid Param
     *
     * @return bool
     */
    public static function cancelRequests($ref_id, $plugin, $userid)
    {
        return RequestHelper::cancelRequests($ref_id, $plugin, $userid);
    }
}
