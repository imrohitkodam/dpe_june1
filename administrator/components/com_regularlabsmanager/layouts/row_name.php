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

use Joomla\CMS\Layout\LayoutHelper as JLayoutHelper;

extract($displayData);

/**
 * @var   object  $item
 * @var   boolean $add_type_links
 */

$types     = $item->types;
$add_links = $add_type_links ?? true;

?>
<td class="has-context">
    <?php echo JLayoutHelper::render('name', compact('item')); ?>
    <div class="d-lg-none">
        <?php echo JLayoutHelper::render('types', compact('types', 'add_links')); ?>
    </div>
</td>
<td class="d-none d-lg-table-cell">
    <?php echo JLayoutHelper::render('types', compact('types', 'add_links')); ?>
</td>
