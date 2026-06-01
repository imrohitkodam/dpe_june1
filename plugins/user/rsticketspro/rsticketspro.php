<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die( 'Restricted access' );

class plgUserRsticketspro extends JPlugin
{
	public function __construct(&$subject, $config) {
		parent::__construct($subject, $config);
	}

	public static function onUserLogin($user, $options) {
		// Initialize variables
		$success = true;
		
		if (!file_exists(JPATH_ADMINISTRATOR.'/components/com_rsticketspro/helpers/rsticketspro.php')) {
			return $success;
		}
		
		require_once JPATH_ADMINISTRATOR.'/components/com_rsticketspro/helpers/rsticketspro.php';
		
		$app 	 = JFactory::getApplication();
		$db	 	 = JFactory::getDbo();
		$session = JFactory::getSession();
		
		if (isset($user['username'])) {
			$user_id = JUserHelper::getUserId($user['username']);
			$logged_user = JFactory::getUser($user_id);
		} else {
			$logged_user = JFactory::getUser();
		}
		$user_id = $logged_user->get('id');
		
		// is staff
		$query = $db->getQuery(true);
		$query->select($db->qn('group_id'))
			  ->from($db->qn('#__rsticketspro_staff'))
			  ->where($db->qn('user_id').'='.$db->q($user_id));
		$db->setQuery($query);
		$group_id = $db->loadResult();
		$is_staff = !empty($group_id);
		if (!$is_staff) {
			// must check if he's an admin
			$admin_groups = RSTicketsProHelper::getAdminGroups();
			$user_groups = $logged_user->getAuthorisedGroups();
			foreach ($user_groups as $user_group_id) {
				if (in_array($user_group_id, $admin_groups)) {
					$is_staff = true;
					break;
				}
			}
		}
		$session->set('rsticketspro.is_staff', $is_staff);
		
		// permissions and department
		if ($is_staff) {
			// permissions
			if ($group_id) {
				$query = $db->getQuery(true);
				$query->select('*')
					  ->from($db->qn('#__rsticketspro_groups'))
					  ->where($db->qn('id').'='.$db->q($group_id));
				$db->setQuery($query);
				$permissions = $db->loadObject();
			} else {
				// JTable::getInstance('RSTicketsPro_Groups','Table');
				$permissions = new stdClass();
				$permissions->name = '';
				$permissions->add_ticket = 1;
				$permissions->add_ticket_customers = 1;
				$permissions->add_ticket_staff = 1;
				$permissions->update_ticket = 1;
				$permissions->update_ticket_custom_fields = 1;
				$permissions->delete_ticket = 1;
				$permissions->answer_ticket = 1;
				$permissions->update_ticket_replies = 1;
				$permissions->update_ticket_replies_customers = 1;
				$permissions->update_ticket_replies_staff = 1;
				$permissions->delete_ticket_replies_customers = 1;
				$permissions->delete_ticket_replies_staff = 1;
				$permissions->delete_ticket_replies = 1;
				$permissions->assign_tickets = 1;
				$permissions->change_ticket_status = 1;
				$permissions->see_unassigned_tickets = 1;
				$permissions->see_other_tickets = 1;
				$permissions->move_ticket = 1;
				$permissions->view_notes = 1;
				$permissions->add_note = 1;
				$permissions->update_note = 1;
				$permissions->update_note_staff = 1;
				$permissions->delete_note = 1;
				$permissions->delete_note_staff = 1;
				$permissions->export_tickets = 1;
			}
			$session->set('rsticketspro.permissions', $permissions);
			
			// get departments
			$query = $db->getQuery(true);
			$query->select($db->qn('department_id'))
				  ->from($db->qn('#__rsticketspro_staff_to_department'))
				  ->where($db->qn('user_id').'='.$db->q($user_id));
			$db->setQuery($query);
			$departments = $db->loadColumn();
			if (empty($departments)) {
				$query = $db->getQuery(true);
				
				$query->select($db->qn('id'))
					  ->from($db->qn('#__rsticketspro_departments'))
					  ->where($db->qn('published').'='.$db->q(1));
				$db->setQuery($query);
				$departments = $db->loadColumn();
			}
			$session->set('rsticketspro.departments', $departments);
			
			// searches
			$query = $db->getQuery(true);
			$query->select('*')
				  ->from($db->qn('#__rsticketspro_searches'))
				  ->where($db->qn('user_id').'='.$db->q($user_id))
				  ->where($db->qn('default').'='.$db->q(1));
			$db->setQuery($query);
			if ($search = $db->loadObject()) {
				if ($params = unserialize(base64_decode($search->params)))
				{
					JTable::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_rsticketspro/tables');
					JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_rsticketspro/models');

					$table = JTable::getInstance('Searches','RsticketsproTable');
					$table->load($search->id);

					$model = JModelLegacy::getInstance('Tickets', 'RsticketsproModel', array(
						'option' => 'com_rsticketspro',
						'table_path' => JPATH_ADMINISTRATOR . '/components/com_rsticketspro/tables'
					));
					
					$model->performSearch($table);
				}
			}
		}
		
		return $success;
	}

	public function onUserLogout($user) {
		// Initialize variables
		$success = true;

		$session = JFactory::getSession();
		$session->set('rsticketspro.is_staff', false);
		$session->set('rsticketspro.permissions', false);

		return $success;
	}
	
	public function onAfterDeleteUser($user, $success, $msg) {
		$db = JFactory::getDbo();
		$id = (int) $user['id'];
		
		if ($id) {
			// remove this staff
			$query = $db->getQuery(true);
			$query->delete('#__rsticketspro_staff')
				  ->where($db->qn('user_id').'='.$db->q($id));
			$db->setQuery($query)->execute();
			
			$query->clear();
			$query->delete('#__rsticketspro_staff_to_department')
				  ->where($db->qn('user_id').'='.$db->q($id));
			$db->setQuery($query)->execute();
			
			// unassign all tickets assigned to this staff member
			$query->clear();
			$query->update('#__rsticketspro_tickets')
				  ->set($db->qn('staff_id').'='.$db->q(0))
				  ->where($db->qn('staff_id').'='.$db->q($id));
			$db->setQuery($query)->execute();
		}
		
		return true;
	}
}