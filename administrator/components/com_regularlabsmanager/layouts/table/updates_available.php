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
?>
<table class="table">
    <thead>
        <tr>
            <?php echo JLayoutHelper::render('head_name'); ?>
            <th scope="col" class="d-md-none rl-w-9em">
                <?php echo JText::_('RLEM_VERSION'); ?>
            </th>
            <th scope="col" class="d-none d-md-table-cell rl-w-9em">
                <?php echo JText::_('RLEM_INSTALLED'); ?>
            </th>
            <th scope="col" class="d-none d-md-table-cell rl-w-9em">
                <?php echo JText::_('RLEM_AVAILABLE'); ?>
            </th>
            <?php echo JLayoutHelper::render('head_actions'); ?>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($items as $item) : ?>
            <tr data-state="updates_available" data-extension="<?php echo $item->alias; ?>" class="rl-popover-parent">
                <?php echo JLayoutHelper::render('row_name', compact('item')); ?>
                <td class="d-md-none">
                    <h4><?php echo JText::_('RLEM_INSTALLED'); ?></h4>
                    <?php echo JLayoutHelper::render('version', [
                        'item'           => $item,
                        'version'        => $item->current_version,
                        'class'          => 'warning text-black',
                        'joomla_version' => $item->joomla_version,
                    ]); ?>
                    <h4 class="mt-2"><?php echo JText::_('RLEM_AVAILABLE'); ?></h4>
                    <?php echo JLayoutHelper::render('version', [
                        'item'      => $item,
                        'version'   => $item->version,
                        'class'     => 'success',
                        'changelog' => str_replace('font-size:1.2em;', '', $item->changelog),
                    ]); ?>
                </td>
                <td class="d-none d-md-table-cell">
                    <?php echo JLayoutHelper::render('version', [
                        'item'           => $item,
                        'version'        => $item->current_version,
                        'class'          => 'warning text-black',
                        'joomla_version' => $item->joomla_version,
                    ]); ?>
                </td>
                <td class="d-none d-md-table-cell">
                    <?php echo JLayoutHelper::render('version', [
                        'item'      => $item,
                        'version'   => $item->version,
                        'class'     => 'success',
                        'changelog' => str_replace('font-size:1.2em;', '', $item->changelog),
                    ]); ?>
                </td>
                <td class="text-right">
                    <?php if ($show_action_buttons) : ?>
                        <?php echo JLayoutHelper::render('button.update', compact('item')); ?>
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
