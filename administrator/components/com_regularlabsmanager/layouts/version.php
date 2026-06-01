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

use Joomla\CMS\Language\Text as JText;

extract($displayData);

/**
 * @var   object $item
 * @var   object $version
 * @var   string $class
 * @var   string $changelog
 * @var   string $joomla_version
 */
?>

<?php if (isset($changelog)) : ?>
<a href="https://regularlabs.com/<?php echo $item->alias; ?>/changelog" target="_blank" class="rl-no-styling">
    <?php endif; ?>

    <span class="badge bg-<?php echo $class; ?> rl-badge rl-min-w-4em"><?php echo $version->version; ?></span>
    <?php if ($version->is_pro) : ?>
        <span class="badge bg-info rl-badge">PRO</span>
    <?php endif; ?>

    <?php if (isset($joomla_version) && $joomla_version !== 4) : ?>
        <br>
        <span class="badge bg-danger rl-badge"><?php echo JText::sprintf('RLEM_FOR_JOOMLA_VERSION', $joomla_version); ?></span>
    <?php endif; ?>

    <?php if (isset($changelog)) : ?>
</a>
    <div class="rl-popover rl-popover-full">
        <small>
            <div style="width: 100%;">
                <?php echo $changelog; ?>
            </div>
        </small>
    </div>
<?php endif; ?>
