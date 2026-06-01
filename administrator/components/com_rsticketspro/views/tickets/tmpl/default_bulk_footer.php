<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die;
?>
<button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-dismiss="modal">
	<?php echo JText::_('JCANCEL'); ?>
</button>
<button type="button" class="btn btn-success" onclick="Joomla.submitbutton('ticket.bulkupdate');">
	<?php echo JText::_('RST_UPDATE'); ?>
</button>
