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

if ($item->alias == 'extensionmanager')
{
    return;
}

$extension = $item->name;

$text        = '';
$title       = JText::_('RLEM_TITLE_UNINSTALL') . ': ' . $extension;
$hidden_text = $title;
$icon        = 'trash';
$class       = 'btn btn-sm btn-danger';
$action      = "RegularLabs.Manager.uninstall('" . $item->alias . "');";
$onclick     = "if ( confirm( '" . str_replace(['<br>', "\n", "'"], ['\n', '\n', "\\'"], JText::_('RL_ARE_YOU_SURE')) . "' ) ) {" . $action . "}";
?>

<?php echo JLayoutHelper::render('button', compact('text', 'hidden_text', 'title', 'icon', 'class', 'onclick')); ?>
