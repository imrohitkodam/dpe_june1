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
 * @var   object $item
 */

if (empty($items))
{
    return;
}

$item          = array_pop($items);
$layout_button = ($item->state == 'broken') ? 'broken' : 'update';

?>

<?php echo JLayoutHelper::render('card.extensionmanager_' . $layout_button, ['item' => $item]); ?>
