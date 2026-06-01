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
            <th scope="col" class="rl-w-9em">
                <?php echo JText::_('RLEM_VERSION'); ?>
            </th>
            <th scope="col" class="d-none d-md-table-cell rl-min-w-16em">
            </th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($items as $item) : ?>
            <tr data-state="installed" data-extension="<?php echo $item->alias; ?>">
                <?php echo JLayoutHelper::render('row_name', compact('item')); ?>
                <td>
                    <?php echo JLayoutHelper::render('version', [
                        'item'    => $item,
                        'version' => $item->current_version,
                        'class'   => 'success',
                    ]); ?>
                </td>
                <td class="text-right d-none d-md-table-cell">
                    <?php if ($show_action_buttons) : ?>
                        <?php echo JLayoutHelper::render('button.downgrade', compact('item')); ?>
                        <?php echo JLayoutHelper::render('button.uninstall', compact('item')); ?>
                    <?php endif; ?>
                    <?php echo JLayoutHelper::render('button.link', compact('item')); ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
