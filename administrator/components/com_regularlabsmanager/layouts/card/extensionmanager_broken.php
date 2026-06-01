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
 * @var   object $item
 */

if (empty($item))
{
    return;
}

$class = 'danger';

?>

<div class="card mb-4 border-2 border-<?php echo $class; ?>">

    <h2 class="card-header bg-<?php echo $class; ?> text-white rounded-0">
        <span class="icon-warning text-white me-2" aria-hidden="true"></span>
        <?php echo JText::_('RLEM_EXTENSIONMANAGER_BROKEN'); ?>
    </h2>

    <div class="card-body">
        <div class="alert alert-<?php echo $class; ?> rl-alert-light">
            <?php echo JText::_('RLEM_EXTENSIONMANAGER_BROKEN_DESC'); ?>
        </div>

        <?php echo JLayoutHelper::render('button', [
            'text'    => JText::_('RLEM_EXTENSIONMANAGER_REINSTALL'),
            'icon'    => 'refresh',
            'class'   => 'btn btn btn-warning',
            'onclick' => 'RegularLabs.Manager.reinstall(\'' . $item->alias . '\');',
        ]); ?>

        <?php echo JLayoutHelper::render('table.broken', ['items' => [$item], 'show_action_buttons' => false]); ?>
    </div>

</div>
