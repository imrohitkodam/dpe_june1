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

?>
<p>
    <?php echo TextUtil::autoLink($message); ?>
</p>
<?php

if (!empty($imageUrl)) {
    ?>
<p>
    <a href="<?php echo $originalUrl; ?>">
        <img src="<?php echo $imageUrl; ?>">
    </a>
</p>
    <?php
}

if (!empty($originalUrl)) {
    ?>
<p>
    <a href="<?php echo $originalUrl; ?>">
        <?php echo $originalUrl; ?>
    </a>
</p>
    <?php
}
