<?php
/**
 * @package         Regular Labs Extension Manager
 * @version         9.2.5
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            https://regularlabs.com
 * @copyright       Copyright © 2025 Regular Labs All Rights Reserved
 * @license         GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

extract($displayData);

/**
 * @var   string $text
 * @var   string $title
 * @var   string $hidden_text
 * @var   string $icon
 * @var   string $class
 * @var   string $url
 */

$text        ??= '';
$title       ??= '';
$hidden_text ??= '';
$class       ??= 'btn btn-sm btn-success rl-no-styling';
?>
<a href="<?php echo $url; ?>" target="_blank"
   class="<?php echo $class; ?>" type="button" title="<?php echo $title; ?>">
    <?php if ($icon) : ?>
        <span class="icon-<?php echo $icon; ?>" aria-hidden="true"></span>
    <?php endif; ?>
    <?php echo $text; ?>
    <?php if ($hidden_text) : ?>
        <span class="visually-hidden"><?php echo $hidden_text; ?></span>
    <?php endif; ?>
</a>
