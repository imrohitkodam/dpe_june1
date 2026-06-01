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

if (!defined('AUTOTWEET_API') && !@include_once(JPATH_ADMINISTRATOR.'/components/com_autotweet/api/autotweetapi.php')) {
    return;
}

require_once 'opengraph.php';

final class PlgContentAutotweetOpenGraph extends \Joomla\CMS\Plugin\CMSPlugin
{
    public const OPT_OFF = 0;

    public const OPT_TYPE_ARTICLE = 1;

    public const OPT_TYPE_WEBSITE = 2;

    public const OPT_TYPE_BOOK = 3;

    public const OPT_TYPE_PROFILE = 4;

    public const OPT_TYPE_CUSTOM = 5;

    public const OPT_LOCALE_ARTICLE = 2;

    public const OPT_LOCALE_CUSTOM = 3;

    public const OPT_SITENAME_SITE = 1;

    public const OPT_SITENAME_CUSTOM = 2;

    private $contentItem;

    private $ogTagsProcessed = false;

    /**
     * onContentPrepare.
     *
     * @param string $context    the context of the content being passed to the plugin
     * @param object &$item      The item object.  Note $article->text is also available
     * @param object &$params    The article params
     * @param int    $page       The 'page' number
     * @param mixed  $limitstart
     *
     * @return void
     */
    public function onContentPrepare($context, &$item, &$params, $limitstart = 0)
    {
        if ($this->ogTagsProcessed) {
            return;
        }

        $input = \Joomla\CMS\Factory::getApplication()->input;
        $component = $input->get('option');

        $includedComponents = $this->params->get('included_components', 'com_content,com_autotweet');
        $includedComponents = explode(',', str_replace(['\n', ' '], [',', ''], $includedComponents));

        if (!in_array($component, $includedComponents, true)) {
            $this->ogTagsProcessed = true;

            return;
        }

        $this->ogTagsProcessed = true;

        $id = (int) $input->get('id');

        // Content - Front
        $id = $id ?: (int) $input->get('a_id');

        if (!$id) {
            $this->ogTagsProcessed = true;

            return;
        }

        // Process the article
        if (($id) && ($articleHelper = OGArticleFactory::getHelper($component, $context, $item))) {
            $this->contentItem = $articleHelper->getArticle();
        }

        if ($this->params->get('smart-loader', 1)) {
            $ogSmartLoader = new OGSmartLoader($this->contentItem);

            if (($id) && ('com_autotweet' === $component)) {
                $this->contentItem = $ogSmartLoader->getItemByPost($id);
            }

            if ((!$this->contentItem) && ($this->params->get('smart-loader-by-url', 1))) {
                $this->contentItem = $ogSmartLoader->getItemByUrl();
            }
        }

        if ($this->contentItem->title) {
            $this->insertOpenGraphTags();
        }

        $this->ogTagsProcessed = true;
    }

    private function insertOpenGraphTags()
    {
        if (!$this->contentItem) {
            return;
        }

        $opengraphHelper = new OpengraphHelper();
        $opengraphHelper->title = OGHelper::title($this->params, $this->contentItem);
        $opengraphHelper->type = $this->type();
        $opengraphHelper->description = OGHelper::description($this->params, $this->contentItem);
        $opengraphHelper->locale = $this->locale();
        $opengraphHelper->siteName = $this->siteName();
        $opengraphHelper->author = OGHelper::author($this->params, $this->contentItem);
        $opengraphHelper->imgSrc = OGHelper::image($this->params, $this->contentItem);
        $opengraphHelper->url = $this->contentItem->url;
        $opengraphHelper->timePublish = $this->timePublish();
        $opengraphHelper->timeModified = $this->timeModified();
        $opengraphHelper->section = $this->contentItem->category_title;
        $opengraphHelper->fbAppId = $this->params->get('og-fbappid');
        $opengraphHelper->fbPages = $this->params->get('og-fbpages');

        // No image yet, but we have the default image
        if (empty($opengraphHelper->imgSrc)) {
            $imageUrl = EParameter::getComponentParam(CAUTOTWEETNG, 'default_image');
            $opengraphHelper->imgSrc = RouteHelp::getInstance()->getAbsoluteUrl($imageUrl, true);
        }

        if ($opengraphHelper->imgSrc) {
            $opengraphHelper->imgAlt = OGHelper::imageAlt($this->params, $this->contentItem);
        }

        $opengraphHelper->insertTags();
    }

    /**
     * type.
     *
     * @return string
     */
    private function type()
    {
        switch ($this->params->get('og-type', 1)) {
            case self::OPT_TYPE_ARTICLE:
                return 'article';

                break;
            case self::OPT_TYPE_WEBSITE:
                return 'website';

                break;
            case self::OPT_TYPE_BOOK:
                return 'book';

                break;
            case self::OPT_TYPE_PROFILE:
                return 'profile';

                break;
            case self::OPT_TYPE_CUSTOM:
                return $this->params->get('og-type-custom');

                break;
        }

        return null;
    }

    /**
     * siteName.
     *
     * @return string
     */
    private function siteName()
    {
        if (self::OPT_SITENAME_SITE === (int) $this->params->get('og-sitename', self::OPT_SITENAME_SITE)) {
            return \Joomla\CMS\Factory::getConfig()->get('sitename');
        }

        return $this->params->get('og-sitename-custom');
    }

    /**
     * timePublish.
     *
     * @return string
     */
    private function timePublish()
    {
        if (!$this->contentItem->publish_up) {
            return null;
        }

        return date('c', strtotime($this->contentItem->publish_up));
    }

    /**
     * timeModified.
     *
     * @return string
     */
    private function timeModified()
    {
        if (!$this->contentItem->modified) {
            return null;
        }

        return date('c', strtotime($this->contentItem->modified));
    }

    /**
     * locale.
     *
     * @return string
     */
    private function locale()
    {
        if (self::OPT_LOCALE_CUSTOM === (int) $this->params->get('og-locale', self::OPT_LOCALE_ARTICLE)) {
            return str_replace('-', '_', $this->params->get('og-locale-custom'));
        }

        return str_replace('-', '_', \Joomla\CMS\Factory::getLanguage()->getTag());
    }
}
