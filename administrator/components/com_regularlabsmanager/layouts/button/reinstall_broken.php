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

$text    = JText::_('RLEM_REINSTALL_BROKEN');
$icon    = 'refresh';
$class   = 'btn btn-warning';
$onclick = 'RegularLabs.Manager.reinstall();';
?>

<?php echo JLayoutHelper::render('button', compact('text', 'icon', 'class', 'onclick')); ?>
