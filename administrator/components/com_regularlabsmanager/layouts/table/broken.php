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
use Joomla\CMS\Layout\LayoutHelper as JLayoutHelper;

extract($displayData);

/**
 * @var   object  $items
 * @var   boolean $show_action_buttons
 */

if (empty($items))
{
    return;
}

$show_action_buttons ??= true;
$add_type_links      = false;
?>

<table class="table">
    <thead>
        <tr>
            <?php echo JLayoutHelper::render('head_name'); ?>
            <th scope="col" class="rl-w-9em">
                <?php echo JText::_('RLEM_VERSION'); ?>
            </th>
            <?php echo JLayoutHelper::render('head_actions'); ?>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($items as $item) : ?>
            <tr data-state="broken" data-extension="<?php echo $item->alias; ?>">
                <?php echo JLayoutHelper::render('row_name', compact('item', 'add_type_links')); ?>
                <td>
                    <?php echo JLayoutHelper::render('version', [
                        'item'           => $item,
                        'version'        => $item->version,
                        'class'          => 'success',
                        'joomla_version' => $item->joomla_version,
                    ]); ?>
                </td>
                <td class="text-right">
                    <?php if ($show_action_buttons) : ?>
                        <?php echo JLayoutHelper::render('button.reinstall', compact('item')); ?>
                    <?php endif; ?>
                    <span class="d-none d-md-inline">
                        <?php if ($show_action_buttons) : ?>
                            <?php echo JLayoutHelper::render('button.uninstall', compact('item')); ?>
                        <?php endif; ?>
                        <?php echo JLayoutHelper::render('button.download', compact('item')); ?>
                        <?php echo JLayoutHelper::render('button.link', compact('item')); ?>
                    </span>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
