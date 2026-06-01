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
 * OGItem.
 *
 * @since       1.0
 */
class OGItem
{
    public $title;

    public $metadesc;

    public $introtext;

    public $publish_up;

    public $modified;

    public $created_by;

    // Advanced properties

    public $firstContentImage;

    public $introImage;

    public $fullTextImage;

    public $url;

    public $category_title;

    public $imageArray = [];
}
