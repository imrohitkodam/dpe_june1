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

$downgrade = version_compare($item->current_version->version, $item->version->stable, '>');

$action = 'reinstall';
$icon   = 'upload';
$text   = JText::_('RLEM_TITLE_REINSTALL');

if ($downgrade)
{
    $action = 'downgrade';
    $icon   = 'undo';
    $text   = JText::_('RLEM_TITLE_INSTALL_STABLE');
}

$extension = $item->name . ' v' . $item->version->version . ($item->version->is_pro ? 'PRO' : '');

$title       = $text . ': ' . $extension;
$hidden_text = $extension;
$class       = 'btn btn-sm btn-link';
$onclick     = 'RegularLabs.Manager.' . $action . '(\'' . $item->alias . '\');';
?>

<?php echo JLayoutHelper::render('button', compact('text', 'hidden_text', 'title', 'icon', 'class', 'onclick')); ?>
