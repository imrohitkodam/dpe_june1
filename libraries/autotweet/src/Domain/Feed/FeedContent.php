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
 * FeedContent.
 *
 * @since       1.0
 */
class FeedContent
{
    public $id = 0;

    public $cat_id = 0;

    public $access = 0;

    public $featured = 0;

    public $language = 0;

    public $hash;

    public $permalink;

    public $feedItemBase;

    public $namePrefix;

    public $title;

    public $alias;

    public $blacklisted;

    public $whitelisted;

    public $created_by;

    public $created_by_alias;

    public $images;

    public $enclosures;

    public $showEnclosureImage;

    public $showDefaultImage;

    public $introtext;

    public $fulltext;

    public $shortlink;

    public $metakey = '';

    public $metadesc = '';

    public $created;

    public $publish_up;

    public $state;

    public $publish_down;
}
