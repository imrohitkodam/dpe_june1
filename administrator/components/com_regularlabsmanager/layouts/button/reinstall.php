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

$extension = $item->name . ' v' . $item->version->version . ($item->version->is_pro ? 'PRO' : '');

$text        = JText::_('RLEM_TITLE_REINSTALL');
$title       = $text . ': ' . $extension;
$hidden_text = $extension;
$icon        = 'refresh';
$class       = 'btn btn-sm btn-warning';
$onclick     = 'RegularLabs.Manager.reinstall(\'' . $item->alias . '\');';
?>

<?php echo JLayoutHelper::render('button', compact('text', 'hidden_text', 'title', 'icon', 'class', 'onclick')); ?>
