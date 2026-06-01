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
class TwittercardHelper
{
    public const OG_TITLE = 'twitter:title';

    public const OG_TYPE = 'twitter:card';

    public const OG_DESC = 'twitter:description';

    public const OG_AUTHOR = 'twitter:creator';

    public const OG_IMAGE = 'twitter:image:src';

    public const OG_IMAGE_ALT = 'twitter:image:alt';

    public $title;

    public $type;

    public $description;

    public $imgSrc;

    public $imgAlt;

    public $author;

    /**
     * __construct - Class for inserting OG-tags in the HTML header.
     *
     * @param string $title  Param
     * @param string $type   Param
     * @param string $desc   Param
     * @param string $img    Param
     * @param string $author Param
     */
    public function __construct($title = '', $type = '', $desc = '', $img = '', $author = '', $imgAlt = '')
    {
        $this->title = $title;
        $this->type = $type;
        $this->description = $desc;
        $this->imgSrc = $img;
        $this->imgAlt = $imgAlt;
        $this->author = $author;
    }

    /**
     * Method for creating and inserting meta tags in the html header.
     *
     * @param string $propertyName Param
     * @param string $content      Param
     *
     * @return void
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
     *
     * @return void
     */
    public function insertTags()
    {
        $this->insertTag(self::OG_TITLE, $this->title);
        $this->insertTag(self::OG_TYPE, $this->type);
        $this->insertTag(self::OG_DESC, $this->description);
        $this->insertTag(self::OG_IMAGE, $this->imgSrc);
        $this->insertTag(self::OG_IMAGE_ALT, $this->imgAlt);
        $this->insertTag(self::OG_AUTHOR, $this->author);
    }
}
