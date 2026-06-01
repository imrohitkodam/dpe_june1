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
 * @var   object  $types
 * @var   boolean $add_links
 */

$add_links ??= true;
?>
<?php foreach ($types as $type) : ?>
    <?php
    if ($type->type == 'pkg')
    {
        continue;
    }
    ?>
    <?php if ($add_links && $type->url) : ?>
        <a href="<?php echo $type->url; ?>" target="_blank" class="rl-no-styling"
        title="<?php echo $type->text; ?>">
    <?php endif; ?>
    <span class="d-xxl-none badge bg-<?php echo $type->class; ?> rl-badge rl-min-w-2em" title="<?php echo $type->text; ?>">
        <?php echo $type->letter; ?>
    </span>
    <span class="d-none d-xxl-inline-block badge bg-<?php echo $type->class; ?> rl-badge mb-1">
        <?php echo $type->text; ?>
    </span>
    <?php if ($add_links) : ?>
        </a>
    <?php endif; ?>
<?php endforeach; ?>
