<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

if ($this->otherTickets)
{
	?>
	<table class="table table-striped">
		<thead>
			<tr>
				<th><?php echo JText::_('RST_TICKET_CODE'); ?> <?php echo JText::_('RST_TICKET_SUBJECT'); ?></th>
				<th><?php echo JText::_('RST_TICKET_STATUS'); ?></th>
				<th><?php echo JText::_('RST_TICKET_REPLIES'); ?></th>
				<th><?php echo JText::_('RST_TICKET_DATE'); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php
		foreach ($this->otherTickets as $ticket)
		{
			?>
			<tr>
				<td><a href="<?php echo JRoute::_('index.php?option=com_rsticketspro&view=ticket&id=' . $ticket->id); ?>" title="<?php echo $this->escape($ticket->subject); ?>">[<?php echo $this->escape($ticket->code); ?>] <?php echo $this->escape($ticket->subject); ?></a></td>
				<td><?php echo JText::_($ticket->status_name); ?></td>
				<td><?php echo JText::sprintf('RST_TICKET_REPLIES_NUM', $ticket->replies); ?></td>
				<td><?php echo JHtml::_('date', $ticket->date, $this->dateFormat); ?></td>
			</tr>
			<?php
		}
		?>
		</tbody>
	</table>
<?php
}
else
{
	?>
	<p><?php echo JText::_('RST_NO_TICKET_HISTORY'); ?></p>
	<?php
}