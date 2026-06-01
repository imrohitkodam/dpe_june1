<?php
/**
 * @package         Regular Labs Library
 * @version         25.11.14282
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            https://regularlabs.com
 * @copyright       Copyright © 2025 Regular Labs All Rights Reserved
 * @license         GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

/**
 * @var   object $displayData
 */

$button = $displayData;

if (empty($button->name))
{
    return;
}

$is_modal = $button->modal ?? false;

$class   = 'btn';
$class   .= ! empty($button->class) ? ' ' . $button->class : ' btn-secondary';
$class   .= $is_modal ? ' modal-button' : null;
$onclick = ! empty($button->onclick) ? ' onclick="' . str_replace('"', '&quot;', $button->onclick) . '"' : '';
$title   = ! empty($button->title) ? $button->title : ($button->text ?? '');
$icon    = ! empty($button->icon) ? $button->icon : $button->name;

$href = $is_modal
    ? 'data-bs-target="#' . strtolower($button->name) . '_modal"'
    : 'href="' . ($button->link ?? '#') . '"';
?>
<button type="button" <?php echo $href; ?>
        class="<?php echo $class; ?>" <?php echo $button->modal ? 'data-bs-toggle="modal"' : '' ?>
        title="<?php echo $title; ?>" <?php echo $onclick; ?>>
    <span class="icon-<?php echo $icon; ?>" aria-hidden="true"></span>
    <?php echo $button->text ?? ''; ?>
</button>
