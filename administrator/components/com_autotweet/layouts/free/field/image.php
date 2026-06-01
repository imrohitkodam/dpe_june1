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

$attrs = [];

if (array_key_exists('controller', $displayData)) {
    $attrs['ng-model'] = $displayData['controller'].'.image_url_value';
}

echo EHtml::imageControl(
    null,
    'image_url',
    'COM_AUTOTWEET_REQ_IMAGE',
    'COM_AUTOTWEET_REQ_IMAGE_DESC',
    'image_url',
    true,
    $attrs
);
