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

use Joomla\CMS\HTML\HTMLHelper as JHtml;
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
            <th scope="col" class="w-1">
                <?php echo JHtml::_('grid.checkall'); ?>
            </th>
            <?php echo JLayoutHelper::render('head_name'); ?>
            <th scope="col rl-w-9em">
                <?php echo JText::_('RLEM_VERSION'); ?>
            </th>
            <?php echo JLayoutHelper::render('head_actions'); ?>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($items as $item) : ?>
            <tr data-state="not_installed" data-extension="<?php echo $item->alias; ?>">
                <td>
                    <label for="cb<?php echo $item->alias; ?>"><span class="visually-hidden">
                            <?php echo JText::_('JSELECT'); ?>
                            <?php echo $item->name; ?>
                        </span></label><input
                        class="form-check-input" autocomplete="off" type="checkbox"
                        id="cb<?php echo $item->alias; ?>" name="extensions[]"
                        value="<?php echo $item->alias; ?>"
                    >
                </td>
                <?php echo JLayoutHelper::render('row_name', compact('item', 'add_type_links')); ?>
                <td>
                    <?php echo JLayoutHelper::render('version', [
                        'item'    => $item,
                        'version' => $item->version,
                        'class'   => 'success',
                    ]); ?>
                </td>
                <td class="text-right">
                    <?php if ($show_action_buttons) : ?>
                        <?php echo JLayoutHelper::render('button.install', compact('item')); ?>
                    <?php endif; ?>
                    <span class="d-none d-md-inline">
                        <?php echo JLayoutHelper::render('button.download', compact('item')); ?>
                        <?php echo JLayoutHelper::render('button.link', compact('item')); ?>
                    </span>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
