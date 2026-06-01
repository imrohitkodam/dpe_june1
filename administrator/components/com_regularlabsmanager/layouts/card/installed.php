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
<div class="card mb-4 border-2 border-success">

    <h2 class="card-header"><?php echo JText::_('RLEM_INSTALLED'); ?></h2>

    <div class="card-body">
        <?php echo JLayoutHelper::render('table.installed', compact('items')); ?>
    </div>

</div>
