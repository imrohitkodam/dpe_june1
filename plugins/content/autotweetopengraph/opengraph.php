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
 * Opengraph class.
 *
 * @since       1.0
 */
class OpengraphHelper
{
    public const OG_TITLE = 'og:title';

    public const OG_TYPE = 'og:type';

    public const OG_DESC = 'og:description';

    public const OG_LOCALE = 'og:locale';

    public const OG_SITENAME = 'og:site_name';

    public const OG_IMAGE = 'og:image';

    public const OG_IMAGE_ALT = 'og:image:alt';

    public const OG_URL = 'og:url';

    public const OG_TIMEPUB = 'article:published_time';

    public const OG_TIMEMOD = 'article:modified_time';

    public const OG_SECTION = 'article:section';

    public const OG_AUTHOR = 'article:author';

    public const OG_FBAPPID = 'fb:app_id';

    public const OG_FBPAGES = 'fb:pages';
    public $title;

    public $type;

    public $description;

    public $locale;

    public $siteName;

    public $imgSrc;

    public $imgAlt;

    public $url;

    public $timePublish;

    public $timeModified;

    public $section;

    public $author;

    public $fbAppId;

    public $fbPages;

    /**
     * __construct - Class for inserting OG-tags in the HTML header.
     *
     * @param string $title        Param
     * @param string $type         Param
     * @param string $desc         Param
     * @param string $locale       Param
     * @param string $siteName     Param
     * @param string $img          Param
     * @param string $url          Param
     * @param string $timePublish  Param
     * @param string $timeModified Param
     * @param string $section      Param
     * @param string $author       Param
     * @param string $fbAppId      Param
     * @param string $fbPages      Param
     */
    public function __construct($title = '', $type = '', $desc = '', $locale = '', $siteName = '', $img = '', $url = '', $timePublish = '', $timeModified = '', $section = '', $author = '', $fbAppId = '', $fbPages = '', $imgAlt = '')
    {
        $this->title = $title;
        $this->type = $type;
        $this->description = $desc;
        $this->locale = $locale;
        $this->siteName = $siteName;
        $this->imgSrc = $img;
        $this->imgAlt = $imgAlt;
        $this->url = $url;
        $this->timePublish = $timePublish;
        $this->timeModified = $timeModified;
        $this->section = $section;
        $this->author = $author;
        $this->fbAppId = $fbAppId;
        $this->fbPages = $fbPages;
    }

    /**
     * Method for creating and inserting meta tags in the html header.
     *
     * @param string $propertyName Param
     * @param string $content      Param
     */
    public function insertTag($propertyName, $content)
    {
        $document = \Joomla\CMS\Factory::getDocument();
        $doctype = $document->getType();
        $sanitizedContent = htmlentities(strip_tags($content), \ENT_QUOTES, 'UTF-8');

        if ('html' !== $doctype || empty($content) || empty($sanitizedContent)) {
            return;
        }

        // Avoid colissions
        if (isset($document->_metaTags['property'][$propertyName])) {
            return;
        }

        // Avoid colissions
        if (isset($document->_metaTags['name'][$propertyName])) {
            return;
        }

        $document->setMetaData($propertyName, $sanitizedContent, 'property');
    }

    /**
     * Method for inserting tags in the HTML header.
     */
    public function insertTags()
    {
        $this->insertTag(self::OG_TITLE, $this->title);
        $this->insertTag(self::OG_TYPE, $this->type);
        $this->insertTag(self::OG_DESC, $this->description);
        $this->insertTag(self::OG_LOCALE, $this->locale);
        $this->insertTag(self::OG_SITENAME, $this->siteName);
        $this->insertTag(self::OG_IMAGE, $this->imgSrc);
        $this->insertTag(self::OG_IMAGE_ALT, $this->imgAlt);
        $this->insertTag(self::OG_URL, $this->url);
        $this->insertTag(self::OG_TIMEPUB, $this->timePublish);
        $this->insertTag(self::OG_TIMEMOD, $this->timeModified);
        $this->insertTag(self::OG_SECTION, $this->section);
        $this->insertTag(self::OG_AUTHOR, $this->author);
        $this->insertTag(self::OG_FBAPPID, $this->fbAppId);
        $this->insertTag(self::OG_FBPAGES, $this->fbPages);
    }
}
