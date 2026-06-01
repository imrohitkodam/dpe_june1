<?php

/**
 * @author      Extly, CB. <team@extly.com>
 * @copyright   Copyright (c)2012-2020 Extly, CB. All rights reserved.
 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 *
 * @see        https://www.extly.com
 */
defined('_JEXEC') || exit;

$message = $displayData['message'];
$data = $displayData['data'];

$imageUrl = $data->image_url;

$shortUrl = $data->url;
$originalUrl = $data->org_url;
$url = $shortUrl ?? $originalUrl;

$fulltext = $data->fulltext;
$shortFulltext = TextUtil::truncateHtml($fulltext, 384);
$hashtags = $data->xtform->get('hashtags');
$nativeObject = $data->xtform->get('native_object');
$publishUp = $nativeObject->publish_up;

$altText = PostHelper::getAltText($message, $data);

$fulltext = TextUtil::autoLink($fulltext);
$shortFulltext = TextUtil::autoLink($shortFulltext);

/*
Styled text with message entities
Telegram supports styled text using message entities.

# https://core.telegram.org/api/entities
*/
?>
<b><?php echo $message; ?></b>
<?php

echo '<br>';

if (!empty($fulltext)) {
    echo $shortFulltext;
}

echo '<br>';
