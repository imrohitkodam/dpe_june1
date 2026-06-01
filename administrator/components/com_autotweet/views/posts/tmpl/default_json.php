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

XTF0FModel::getTmpInstance('Plugins', 'AutoTweetModel');

$result = AdvancedAttributesHelper::generatePostsForComposerApp($this->items);

echo TextUtil::encodeJsonPackage($result);
