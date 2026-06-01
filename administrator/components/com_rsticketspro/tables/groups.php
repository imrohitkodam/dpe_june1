<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproTableGroups extends JTable
{
	public $id = null;
	
	public $name = '';
	public $add_ticket = 1;
	public $add_ticket_customers = 1;
	public $add_ticket_staff = 1;
	public $update_ticket = 1;
	public $update_ticket_custom_fields = 1;
	public $delete_ticket = 1;
	public $answer_ticket = 1;
	public $update_ticket_replies = 1;
	public $update_ticket_replies_customers = 1;
	public $update_ticket_replies_staff = 1;
	public $delete_ticket_replies = 1;
	public $delete_ticket_replies_customers = 1;
	public $delete_ticket_replies_staff = 1;
	public $assign_tickets = 1;
	public $change_ticket_status = 1;
	public $see_unassigned_tickets = 1;
	public $see_other_tickets = 1;
	public $move_ticket = 1;
	public $view_notes = 1;
	public $add_note = 1;
	public $update_note = 1;
	public $update_note_staff = 1;
	public $delete_note = 1;
	public $delete_note_staff = 1;
	
	public function __construct(&$db)
	{
		parent::__construct('#__rsticketspro_groups', 'id', $db);
	}
	
	public function delete($pk = null)
	{
		$deleted = parent::delete($pk);
		if ($deleted)
		{
			$db 	= $this->getDbo();
			$query 	= $db->getQuery(true);
			
			$query->select($db->qn('user_id'))
				  ->from('#__rsticketspro_staff')
				  ->where($db->qn('group_id') . '=' . $db->q($pk));

			if ($users = $db->setQuery($query)->loadColumn())
			{
				$query->clear();
				// set tickets to "unassigned" since we've removed the staff members
				$query->update('#__rsticketspro_tickets')
					  ->set($db->qn('staff_id') . '=' . $db->q(0))
					  ->where($db->qn('staff_id') . ' IN (' . implode(',', $users) . ')');
				$db->setQuery($query)->execute();
				
				$query->clear();
				// delete staff members belonging to this group
				$query->delete('#__rsticketspro_staff')
					  ->where($db->qn('group_id') . '=' . $db->q($pk));
				$db->setQuery($query)->execute();
			}
		}

		return $deleted;
	}
}