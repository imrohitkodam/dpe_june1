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
 * @var   object $items
 */

if (empty($items))
{
    return;
}
?>

<div class="card mb-4 border-2 border-danger">

    <h2 class="card-header bg-danger text-white rounded-0">
        <span class="icon-warning text-white me-2" aria-hidden="true"></span>
        <?php echo JText::_('RLEM_BROKEN'); ?>
    </h2>

    <div class="card-body">
        <?php echo JLayoutHelper::render('button.reinstall_broken'); ?>

        <div class="alert alert-danger rl-alert-light">
            <?php echo JText::_('RLEM_BROKEN_DESC'); ?>
        </div>

        <?php echo JLayoutHelper::render('table.broken', compact('items')); ?>
    </div>

</div>
