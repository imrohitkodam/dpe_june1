<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

foreach ($this->ticket->fields as $field)
{
	echo RSTicketsProHelper::showCustomField($field, array(), !empty($this->permissions->update_ticket_custom_fields));
}

if (!empty($this->permissions->update_ticket_custom_fields))
{
	?>
	<p><button type="button" onclick="Joomla.submitbutton('ticket.updatefields')" class="btn btn-primary"><?php echo JText::_('RST_UPDATE'); ?></button></p>
	<?php
}