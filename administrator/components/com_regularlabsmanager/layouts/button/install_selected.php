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

$text    = JText::_('RLEM_INSTALL_SELECTION');
$icon    = 'upload';
$class   = 'btn btn-primary';
$onclick = 'RegularLabs.Manager.install();';
?>

<?php echo JLayoutHelper::render('button', compact('text', 'icon', 'class', 'onclick')); ?>
