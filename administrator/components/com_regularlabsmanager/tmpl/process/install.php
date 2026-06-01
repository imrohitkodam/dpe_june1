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

use Joomla\CMS\Factory as JFactory;
use Joomla\CMS\Language\Text as JText;
use Joomla\CMS\Layout\LayoutHelper as JLayoutHelper;

if (JFactory::getApplication()->isClient('site'))
{
    die();
}
?>
<h2>
    <?php echo JText::_('RLEM_INSTALLING'); ?>...
</h2>

<table class="table">
    <thead>
        <tr>
            <th scope="col" class="rl-w-md-25">
                <?php echo JText::_('RLEM_EXTENSION'); ?>
            </th>
            <th scope="col" style="width: 140px;">
                <?php echo JText::_('RLEM_VERSION'); ?>
            </th>
            <th scope="col">
            </th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($this->items as $item) : ?>
            <tr>
                <td class="has-context">
                    <?php echo JLayoutHelper::render('name', compact('item')); ?>
                </td>
                <td>
                    <?php echo JLayoutHelper::render('version', [
                        'item'    => $item,
                        'version' => $item->version,
                        'class'   => 'success',
                    ]); ?>
                </td>
                <td>
                    <?php echo JLayoutHelper::render('progress', compact('item')); ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
