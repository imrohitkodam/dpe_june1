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
<div class="container">
	<div class="text-center">
		<?php
		if ($this->permissions->move_ticket)
		{
			echo '<div>' . $this->bulkForm->getField('bulk_department_id')->input . '</div>';
		}
		if ($this->permissions->assign_tickets)
		{
			echo '<div>' . $this->bulkForm->getField('bulk_staff_id')->input . '</div>';
		}
		if ($this->permissions->update_ticket)
		{
			echo '<div>' . $this->bulkForm->getField('bulk_priority_id')->input . '</div>';
		}
		if ($this->permissions->change_ticket_status)
		{
			echo '<div>' . $this->bulkForm->getField('bulk_status_id')->input . '</div>';
		}

		if ($this->autocloseEnabled)
		{
			echo '<div>' . $this->bulkForm->getField('bulk_notify')->input . '</div>';
		}

		if ($this->permissions->delete_ticket)
		{
			echo '<div>' . $this->bulkForm->getField('bulk_delete')->input . '</div>';
		}
		?>
	</div>
</div>
