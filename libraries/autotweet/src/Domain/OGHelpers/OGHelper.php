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

final class OGHelper
{
    public const OPT_TITLE_ARTICLE = 1;

    public const OPT_TITLE_CUSTOM = 2;

    public const OPT_DESC_META = 1;

    public const OPT_DESC_INTRO = 2;

    public const OPT_DESC_SITE = 3;

    public const OPT_DESC_CUSTOM = 4;

    public const OPT_DESC_TITLE = 5;

    public const OPT_AUTHOR_ARTICLE = 1;

    public const OPT_AUTHOR_CUSTOM = 2;

    public const OPT_IMG_PRIO_FIRST = 1;

    public const OPT_IMG_PRIO_INTRO = 2;

    public const OPT_IMG_PRIO_FULL = 3;

    public const OPT_IMG_PRIO_CLASS = 4;

    public const OPT_IMG_PRIO_CUSTOM = 5;

    public static function title($params, $contentItem)
    {
        $title = null;

        switch ($params->get('og-title', 1)) {
            case self::OPT_TITLE_ARTICLE:
                $title = $contentItem->title;

                break;
            case self::OPT_TITLE_CUSTOM:
                $title = $params->get('og-title-custom');

                break;
            default:
                break;
        }

        return $title;
    }

    public static function getImageWithClass($images, $cssClass)
    {
        if (empty($images)) {
            return;
        }

        foreach ($images as $image) {
            $classes = explode(' ', $image['class']);

            if (in_array($cssClass, $classes, true)) {
                return $image['src'];
            }
        }
    }

    public static function getImageAltWithClass($images, $cssClass)
    {
        if (empty($images)) {
            return;
        }

        foreach ($images as $image) {
            $classes = explode(' ', $image['class']);

            if (in_array($cssClass, $classes, true)) {
                return $image['alt'];
            }
        }
    }

    public static function description($params, $contentItem)
    {
        $desc = null;

        switch ($params->get('og-desc', 1)) {
            case self::OPT_DESC_META:
                $desc = $contentItem->metadesc;

                break;
            case self::OPT_DESC_INTRO:
            case self::OPT_DESC_SITE:
                $desc = $contentItem->introtext;

                if (empty($desc)) {
                    $config = \Joomla\CMS\Factory::getConfig();
                    $desc = $config->get('MetaDesc');
                }

                break;
            case self::OPT_DESC_CUSTOM:
                $desc = $params->get('og-desc-custom');

                break;
            case self::OPT_DESC_TITLE:
                $desc = $contentItem->title;

                break;
            default:
                break;
        }

        $desc = TextUtil::cleanText($desc);
        $desc = TextUtil::truncString($desc, 512);

        return $desc;
    }

    public static function author($params, $contentItem)
    {
        if (self::OPT_AUTHOR_CUSTOM === (int) $params->get('og-author', self::OPT_AUTHOR_ARTICLE)) {
            return $params->get('og-author-custom');
        }

        $author = JUser::getInstance($contentItem->created_by);

        return $author->name;
    }

    public static function image($params, $contentItem)
    {
        $image = self::imageWithOption($params->get('og-img-prio1', 2), $params, $contentItem);

        if (empty($image)) {
            $image = self::imageWithOption($params->get('og-img-prio2', 3), $params, $contentItem);
        }

        if (empty($image)) {
            $image = self::imageWithOption($params->get('og-img-prio3', 1), $params, $contentItem);
        }

        return $image;
    }

    public static function imageAlt($params, $contentItem)
    {
        $image = self::imageAltWithOption($params->get('og-img-prio1', 2), $params, $contentItem);

        if (empty($image)) {
            $image = self::imageAltWithOption($params->get('og-img-prio2', 3), $params, $contentItem);
        }

        if (empty($image)) {
            $image = self::imageAltWithOption($params->get('og-img-prio3', 1), $params, $contentItem);
        }

        return $image;
    }

    private static function imageWithOption($option, $params, $contentItem)
    {
        switch ($option) {
            case self::OPT_IMG_PRIO_FIRST:
                $image = $contentItem->firstContentImage ?: null;

                break;
            case self::OPT_IMG_PRIO_INTRO:
                $image = $contentItem->introImage ?: null;

                break;
            case self::OPT_IMG_PRIO_FULL:
                $image = $contentItem->fullTextImage ?: null;

                break;
            case self::OPT_IMG_PRIO_CLASS:
                $images = (!empty($contentItem->imageArray)) ? $contentItem->imageArray : null;
                $cssClass = $params->get('og-img-class', 'xt-image');
                $classImage = self::getImageWithClass($images, $cssClass);
                $image = $classImage ?: null;

                break;
            case self::OPT_IMG_PRIO_CUSTOM:
                if (!empty($params->get('og-img-custom'))) {
                    $image = 'images/'.$params->get('og-img-custom');
                }

                break;
            default:
                return null;

                break;
        }

        if (!empty($image)) {
            $image = RouteHelp::getInstance()->getAbsoluteUrl($image, true);

            return $image;
        }

        return null;
    }

    private static function imageAltWithOption($option, $params, $contentItem)
    {
        switch ($option) {
            case self::OPT_IMG_PRIO_FIRST:
                return $contentItem->firstContentImage ? $contentItem->firstContentImageAlt : null;

                break;
            case self::OPT_IMG_PRIO_INTRO:
                return $contentItem->introImage ? $contentItem->introImageAlt : null;

                break;
            case self::OPT_IMG_PRIO_FULL:
                return $contentItem->fullTextImage ? $contentItem->fullTextImageAlt : null;

                break;
            case self::OPT_IMG_PRIO_CLASS:
                $images = !empty($contentItem->imageArray) ? $contentItem->imageArray : null;

                if (!$images) {
                    return null;
                }

                $cssClass = $params->get('og-img-class', 'xt-image');
                $classImage = self::getImageAltWithClass($images, $cssClass);
                $image = (isset($classImage)) ? $classImage : null;

                break;
        }

        return null;
    }
}
