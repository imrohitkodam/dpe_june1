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

$article = $displayData['article'];
$params = $displayData['params'];

$imagesCounter = count($article->images);
$downloadImages = (($imagesCounter > 0) && ($params->get('save_img')));

if ($downloadImages) {
    foreach ($article->images as $image) {
        $image->download($params);
    }
}

$authors = null;

if (($params->get('author_article')) && (!empty($article->created_by_alias))) {
    $authors = '<p class="authors"><span class="label">'.JText::_('COM_AUTOTWEET_FEED_AUTHORS').':</span> <span class="author">'.$article->created_by_alias.'</span></p>';
}

if (('top' === $params->get('author_article')) && ($authors)) {
    echo $authors;
}

// Default image, or Enclosure Image
if ((($article->showDefaultImage) || ($article->showEnclosureImage) || ($article->showImageFromText))
    && (!empty($article->images))) {
    $image = $article->images[0];
    echo $image->generateTag();
}

if ((($params->get('onlyintro')) || (empty($article->fulltext))) && (empty($article->introtext))) {
    $article->introtext = '<p>'.$params->get('default_introtext').'</p>';
}

$text = FeedTextHelper::joinArticleText($article->introtext, $article->fulltext);

if ($downloadImages) {
    foreach ($article->images as $image) {
        $text = str_replace($image->original_src, $image->src, $text);
    }
}

echo $text;

/*
if (($params->get('process_enc')) && (count($article->enclosures))) {
    $enclosures = FeedGeneratorHelper::formatEnclosures($article);
    echo $enclosures;
}
*/

if (('bottom' === $params->get('author_article')) && ($authors)) {
    echo $authors;
}

if ($params->get('show_orig_link')) {
    // Trackback Processing
    $readonlink = FeedGeneratorHelper::formatReadonLink($article, $params);
    echo $readonlink;
}
