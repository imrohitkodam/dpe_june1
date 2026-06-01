<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

use Joomla\Registry\Registry;

class RsticketsproModelTicket extends JModelAdmin
{
	public function __construct()
	{
		parent::__construct();
	}

	public function getTable($type = 'Tickets', $prefix = 'RsticketsproTable', $config = array())
	{
		return parent::getTable($type, $prefix, $config);
	}

	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm('com_rsticketspro.ticket', 'ticket', array('control' => 'ticket', 'load_data' => $loadData));
		if (empty($form))
		{
			return false;
		}

		$ticket = $this->getItem();
		$isStaff = $this->isStaff();
		$permissions = $this->getStaffPermissions();
		$userField = RSTicketsProHelper::getConfig('show_user_info');

		if (!$isStaff || !$permissions->update_ticket)
		{
			$form->setFieldAttribute('subject', 'type', 'rsticketsprohtml');
			$form->setFieldAttribute('subject', 'escape', 'true');

			$form->setFieldAttribute('priority_id', 'type', 'rsticketsprohtml');
			$form->setFieldAttribute('priority_id', 'escape', 'true');
			$form->setValue('priority_id', null, JText::_($ticket->priority->name));
		}

		if (!$isStaff || !$permissions->move_ticket)
		{
			$form->setFieldAttribute('department_id', 'type', 'rsticketsprohtml');
			$form->setFieldAttribute('department_id', 'escape', 'true');
			$form->setValue('department_id', null, JText::_($ticket->department->name));
		}

		if (!$isStaff || !$permissions->change_ticket_status)
		{
			$form->setFieldAttribute('status_id', 'type', 'rsticketsprohtml');
			$form->setFieldAttribute('status_id', 'escape', 'true');
			$form->setValue('status_id', null, JText::_($ticket->status->name));
		}

		if (!$isStaff || !$permissions->assign_tickets)
		{
			$form->setFieldAttribute('staff_id', 'type', 'rsticketsprohtml');
			$form->setFieldAttribute('staff_id', 'escape', 'true');
			$form->setValue('staff_id', null, $ticket->staff_id > 0 ? $ticket->staff->get($userField) : JText::_('RST_UNASSIGNED'));
		}

		if (!$isStaff || (!$permissions->add_ticket_customers && !$permissions->add_ticket_staff))
		{
			$form->setFieldAttribute('customer_id', 'type', 'rsticketsprohtml');
			$form->setFieldAttribute('customer_id', 'escape', 'true');
			$form->setValue('customer_id', null, $ticket->customer->get($userField));

			$form->setFieldAttribute('alternative_email', 'type', 'rsticketsprohtml');
			$form->setFieldAttribute('alternative_email', 'escape', 'true');
		}

		$form->setValue('date', null, JHtml::_('date', $ticket->date, RSTicketsProHelper::getConfig('date_format')));

		if (JFactory::getApplication()->isClient('site'))
		{
			$form->setFieldAttribute('search', 'class', 'input-xlarge');
			$form->setFieldAttribute('message', 'class', 'input-xlarge');

			if (!RSTicketsProHelper::getConfig('use_btn_group_radio'))
			{
				$form->setFieldAttribute('use_signature', 'class', '');
				$form->setFieldAttribute('reply_as_customer', 'class', '');
			}
		}

		return $form;
	}

	protected function loadFormData()
	{
		$data = array();
		if ($item = $this->getItem())
		{
			$data = (array) $item->getProperties();
		}

		$validData = array();
		foreach ($data as $k => $v)
		{
			if (!is_object($data[$k]))
			{
				$validData[$k] = $v;
			}
		}

		// workaround to get the message
		$data = JFactory::getApplication()->getUserState('com_rsticketspro.edit.ticket.data', null);
		if (is_array($data) && isset($data['message']))
		{
			$validData['message'] = $data['message'];
		}

		return $validData;
	}

	public function getTicket($id)
	{
		static $cache = array();
		if (!isset($cache[$id]))
		{
			$table = $this->getTable();
			if ($table->load($id))
			{
				$cache[$id] = $table;
			}
			else
			{
				$cache[$id] = false;
			}
		}

		return $cache[$id];
	}

	public function getTicketMessages($id = null, $nosyslog = false)
	{
		if (is_null($id))
		{
			$ticket = $this->getItem();
		}
		else
		{
			$ticket = $this->getTicket($id);
		}
		$customer_id = $ticket->customer_id;
		$ticket_id   = $ticket->id;

		$direction = RSTicketsProHelper::getConfig('messages_direction');

		$db    = JFactory::getDbo();
		$app   = JFactory::getApplication();
		$query = $db->getQuery(true);

		$query->select('*')
			->from($db->qn('#__rsticketspro_ticket_messages'))
			->where($db->qn('ticket_id') . '=' . $db->q($ticket_id))
			->order($db->qn('date') . ' ' . $db->escape($direction));
		if ($nosyslog)
		{
			$query->where($db->qn('user_id') . ' <> ' . $db->q('-1'));
		}
		$db->setQuery($query);
		$messages = $db->loadObjectList('id');

		if ($app->isClient('administrator'))
		{
			$pattern = '/src=[\'"]?([^\'" >]+)[\'" >]/';
			foreach ($messages as $mid => $message)
			{
				if (preg_match_all($pattern, $message->message, $matches))
				{
					if (!empty($matches[1]))
					{
						foreach ($matches[1] as $i => $image)
						{
							if (strpos($image, 'viewinline') !== false)
							{
								$new_image               = str_replace(JUri::root(), JUri::root() . 'administrator/', $image);
								$messages[$mid]->message = str_replace($matches[1][$i], $new_image, $messages[$mid]->message);
							}
						}
					}
				}
			}
		}

		$query->clear();
		$query->select('*')
			->from($db->qn('#__rsticketspro_ticket_files'))
			->where($db->qn('ticket_id') . '=' . $db->q($ticket_id));
		$db->setQuery($query);
		if ($files = $db->loadObjectList())
		{
			foreach ($files as $file)
			{
				$message_id = $file->ticket_message_id;

				if (!empty($messages[$message_id]))
				{
					$message = &$messages[$message_id];

					// add the file to the array
					if (!isset($message->files))
					{
						$message->files = array();
					}
					$message->files[] = $file;
				}
			}
		}

		return $messages;
	}

	public function getItem($pk = null)
	{
		$id = $this->getId();

		return $this->getTicket($id);
	}

	protected function getUser()
	{
		return JFactory::getUser();
	}

	public function isGuest()
	{
		$user = $this->getUser();

		return $user->get('guest');
	}

	public function getIsStaff()
	{
		return $this->isStaff();
	}

	public function isStaff()
	{
		static $result;
		if (is_null($result))
		{
			$user   = $this->getUser();
			$result = RSTicketsProHelper::isStaff($user->get('id'));
		}

		return $result;
	}

	public function canDeleteTimeTracking() {
		static $result;
		if (is_null($result))
		{
			$user   = $this->getUser();
			$result = RSTicketsProHelper::canDeleteTimeTracking($user->get('id'));
		}

		return $result;
	}

	public function getTimeTrackingDeleteStatus() {
		return $this->canDeleteTimeTracking();
	}

	public function canDeleteOwnTimeTracking($ticket_id, $entry_id) {
		static $result = array();

		$hash = md5($ticket_id.$entry_id);
		if (!isset($result[$hash]))
		{
			$user   = $this->getUser();
			$option = RSTicketsProHelper::canDeleteTimeTracking($user->get('id'), 'can_delete_own_time_history');

			if($option && $this->checkIfExistsTimeSpentEntry($ticket_id, $entry_id, $user->get('id')))
			{
				$result[$hash] = true;
			}
			else
			{
				$result[$hash] = false;
			}
		}

		return $result[$hash];
	}

	public function getStaffDepartments()
	{
		static $departments;
		if (is_null($departments))
		{
			$departments = RSTicketsProHelper::getCurrentDepartments();
		}

		return $departments;
	}

	public function getStaffPermissions()
	{
		static $permissions;
		if (is_null($permissions))
		{
			$permissions = RSTicketsProHelper::getCurrentPermissions();
		}

		return $permissions;
	}

	// @int $id - the id of the ticket
	// @returns true on success
	public function hasPermission($id)
	{
		$user  = $this->getUser();
		$table = $this->getTable();

		$is_staff    = $this->isStaff();
		$departments = $this->getStaffDepartments();
		$permissions = $this->getStaffPermissions();

		if ($ticket = $this->getTicket($id))
		{ // found a ticket
			// staff members
			if ($is_staff)
			{
				// staff - check if belongs to department only if he is not the customer
				if ($ticket->customer_id != $user->get('id') && !in_array($ticket->department_id, $departments))
				{
					$this->setError(JText::_('RST_STAFF_CANNOT_VIEW_TICKET'));

					return false;
				}

				// check if department can be seen by this staff member
				if (RSTicketsProHelper::getConfig('staff_force_departments') && !in_array($ticket->department_id, $departments))
				{
					$this->setError(JText::_('RST_STAFF_CANNOT_VIEW_TICKET'));

					return false;
				}

				// is this ticket unassigned?
				if (!$permissions->see_unassigned_tickets && !$ticket->staff_id)
				{
					$this->setError(JText::_('RST_STAFF_CANNOT_VIEW_TICKET'));

					return false;
				}

				// does this ticket belong to another staff member?
				if (!$permissions->see_other_tickets && $ticket->staff_id > 0 && $ticket->staff_id != $user->get('id'))
				{
					$this->setError(JText::_('RST_STAFF_CANNOT_VIEW_TICKET'));

					return false;
				}
			}
			else
			{

				// Hack for DPE : Check cc users are from same cluster and email is vailable in cc

				$rsTktXrefTable = DPE::table('RsticketXref ');
				$rsTktXrefTable->load(array('ticket_id' => $id));
				$rsTktXrefTable->agency_id;

				JLoader::import("/components/com_cluster/libraries/cluster", JPATH_ADMINISTRATOR);
				$cluster = ClusterCluster::getInstance($rsTktXrefTable->agency_id);

				$emails = new Registry($rsTktXrefTable->emails);


				// Check email replied user is a member of cluster
				$isValid = $cluster->isMember($user->get('id'));

				// Hack for DPE : Check cc users are from same cluster and email is vailable in cc

				$isStaff = false;

				if (RBACL::authorise($user->id, 'com_cluster', 'core.view.all',  'com_multiagency', $cluster->id))
				{
					$isStaff = true;
				}

				// If user is dpe staff then check staff is member of ticket's school
				// and check user is available in cc of tickets
				// DPE HAck to obtain the php 8 issue 
				$condition = !$user->authorise('core.manageall', 'com_cluster') &&
				($isStaff ? (!$isValid || !in_array($user->get('email'), (array)$emails['email'])) : !$isValid);

				if ($condition)
				{
					if ($ticket->customer_id != $user->get('id'))
					{
						$this->setError(JText::_('RST_CUSTOMER_CANNOT_VIEW_TICKET'));

						return false;
					}
				}
			}

			return true;
		}

		return false;
	}

	public function hasDownloadPermission($access_code, $file_id, $ticket_id) {
		if (!$access_code || strlen($access_code) != 32) {
			return false;
		}

		require_once JPATH_ADMINISTRATOR . '/components/com_rsticketspro/helpers/ticket.php';

		$helper  = new RSTicketsProTicketHelper;
		$files   = $helper->getTicketAttachments($ticket_id);

		if (empty($files[$file_id])) {
			return false;
		}

		$hash = md5($ticket_id . '|' . $files[$file_id]->id . '|' . $files[$file_id]->filename);

		return $hash === $access_code;
	}

	public function setFlag($id, $flagged)
	{
		if ($ticket = $this->getTicket($id))
		{
			$object = (object) array(
				'id'      => $id,
				'flagged' => $flagged
			);

			return JFactory::getDbo()->updateObject('#__rsticketspro_tickets', $object, array('id'));
		}

		return false;
	}

	public function delete(&$id)
	{
		if ($ticket = $this->getTicket($id))
		{
			return $ticket->delete($id);
		}

		return false;
	}

	public function notify($id)
	{
		// set the interval
		static $interval;
		if (is_null($interval))
		{
			$interval = RSTicketsProHelper::getConfig('autoclose_email_interval') * 86400;
			if ($interval < 86400)
			{
				$interval = 86400;
			}
		}

		// get the date
		$date = JFactory::getDate();

		if ($ticket = $this->getTicket($id))
		{
			$last_reply = JFactory::getDate($ticket->last_reply)->toUnix();
			if ($ticket->last_reply_customer || $ticket->autoclose_sent || $last_reply + $interval > $date->toUnix())
			{
				return false;
			}

			$overdue = floor(($date->toUnix() - $last_reply) / 86400);
			$closed  = RSTicketsProHelper::getConfig('autoclose_interval');

			// get email sending settings
			static $emailConfig = array();
			if (!isset($emailConfig[$ticket->department_id]))
			{
				if (RSTicketsProHelper::getConfig('email_use_global'))
				{
					// are we using global Joomla! config ?
					$app                        = JFactory::getApplication();
					$emailConfig['from']        = $app->get('mailfrom');
					$emailConfig['fromName']    = $app->get('fromname');
					$emailConfig['replyTo']     = $app->get('replyto');
					$emailConfig['replyToName'] = $app->get('replytoname');
				}
				else
				{
					// this means we are using the RSTickets! Pro config
					$emailConfig['from']        = RSTicketsProHelper::getConfig('email_address');
					$emailConfig['fromName']    = RSTicketsProHelper::getConfig('email_address_fullname');
					$emailConfig['replyTo']     = RSTicketsProHelper::getConfig('email_address_reply_to');
					$emailConfig['replyToName'] = RSTicketsProHelper::getConfig('email_address_reply_to_name');
				}

				// let's see if the department has different settings
				$department = $this->getTable('Departments');
				$department->load($ticket->department_id);
				if (!$department->email_use_global)
				{
					$emailConfig['from']        = $department->email_address;
					$emailConfig['fromName']    = $department->email_address_fullname;
					$emailConfig['replyTo']     = $department->email_address_reply_to;
					$emailConfig['replyToName'] = $department->email_address_reply_to_name;
				}
			}

			if ($email = RSTicketsProHelper::getEmail('notification_email'))
			{
                $replacements = array(
                    '{live_site}' => JUri::root(),
                    '{ticket}' => RSTicketsProHelper::route(JUri::root() . 'index.php?option=com_rsticketspro&view=ticket&cid=' . $ticket->id . ':' . JFilterOutput::stringURLSafe($ticket->subject)),
                    '{customer_name}' => $ticket->customer->get('name'),
                    '{customer_username}' => $ticket->customer->get('username'),
                    '{customer_email}' => $ticket->customer->get('email'),
                    '{staff_name}' => $ticket->staff->get('name'),
                    '{staff_username}' => $ticket->staff->get('username'),
                    '{staff_email}' => $ticket->staff->get('email'),
                    '{code}' => $ticket->code,
                    '{subject}' => $ticket->subject,
                    '{priority}' => JText::_($ticket->priority->name),
                    '{status}' => JText::_($ticket->status->name),
                    '{inactive_interval}' => $overdue,
                    '{close_interval}' => $closed
                );

                $email_subject = str_replace(array_keys($replacements), array_values($replacements), $email->subject);
                $email_message = str_replace(array_keys($replacements), array_values($replacements), $email->message);

                // send the notification message
                RSTicketsProHelper::sendMail($emailConfig['from'], $emailConfig['fromName'], $ticket->customer->get('email'), $email_subject, $email_message, 1, null, null, null, $emailConfig['replyTo'], $emailConfig['replyToName']);
            }

			// the autoclose has been sent, mark it in the db
			$object = (object) array(
				'id'             => $ticket->id,
				'autoclose_sent' => $date->toUnix()
			);
			JFactory::getDbo()->updateObject('#__rsticketspro_tickets', $object, array('id'));

			RSTicketsProHelper::addHistory($ticket->id, 'notify');

			return true;
		}
	}

	public function getTicketTimeSpentIntervals($ticket_id = null) {
		if (empty($ticket_id)) {
			$ticket		 = $this->getItem();
			$ticket_id   = $ticket->id;
		}

		// if the ticket_id is still not found return null
		if (empty($ticket_id)) {
			return array();
		}

		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query->select($db->qn('start'))
			->select($db->qn('end'))
			->select($db->qn('staff_id'))
			->select($db->qn('id'))
			->from($db->qn('#__rsticketspro_timespent'))
			->where($db->qn('ticket_id') . ' = ' . $db->q($ticket_id));

		$db->setQuery($query);

		if ($intervals = $db->loadObjectList())
		{
			// calculate the duration
			foreach ($intervals as $interval)
			{
				if ($interval->end == '0000-00-00 00:00:00')
				{
					$interval->duration = '0';
				}
				else
				{
					$int_start = JFactory::getDate($interval->start);
					$int_end = JFactory::getDate($interval->end);

					$int_start = $int_start->getTimestamp();
					$int_end = $int_end->getTimestamp();
					$duration = $int_end - $int_start;

					$interval->duration = $duration;
				}

				if(!empty($interval->staff_id)) {
					$interval->staff_member = $this->getUsername($interval->staff_id);
				}

				$interval->can_delete = $this->canDeleteOwnTimeTracking($ticket_id, $interval->id) || $this->canDeleteTimeTracking();
			}

			return $intervals;
		}

		return array();
	}

	public function checkIfExistsTimeSpentEntry($ticket_id, $entry_id, $staff_id = null) {
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query->select('COUNT(*)')
			->from($db->qn('#__rsticketspro_timespent'))
			->where($db->qn('ticket_id') . ' = ' . $db->q($ticket_id))
			->where($db->qn('id') . ' = ' . $db->q($entry_id));

		if (!is_null($staff_id))
		{
			$query->where($db->qn('staff_id') . ' = ' . $db->q($staff_id));
		}

		$db->setQuery($query);
		
		$exists = $db->loadResult();

		return !empty($exists);
	}

	protected function getUsername($id) {
		static $names = array();

		if (!isset($names[$id]))
		{
			$user = JFactory::getUser($id);
			if ($user && !$user->get('guest'))
			{
				$names[$id] = $user->get('name');
			}
			else
			{
				$names[$id] = '';
			}
		}

		return $names[$id];
	}

	public function clearTimeTracking($ticket_id = null, $id = null) {
		if (empty($ticket_id)) {
			return;
		}

		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query->delete($db->qn('#__rsticketspro_timespent'))
			->where($db->qn('ticket_id') . ' = ' . $db->q($ticket_id))
			->where($db->qn('end') . ' != ' . $db->q('0000-00-00 00:00:00'));

		if (!empty($id)) {
			$query->where($db->qn('id') . ' = ' . $db->q($id));
		}

		$db->setQuery($query);

		$db->execute();

		// update time_spent for the list view
		if (!empty($id)) {
			$this->remakeTimeSpent($ticket_id, true);
		} else {
			$object = (object) array(
				'id' => $ticket_id,
				'time_spent' => 0
			);

			JFactory::getDbo()->updateObject('#__rsticketspro_tickets', $object, array('id'));
		}
	}

	public function toggleTime($ticket_id, $state)
	{
		$isStaff              = $this->isStaff();
		$enableTimeSpent      = RSTicketsProHelper::getConfig('enable_time_spent');

		if ($isStaff && $enableTimeSpent)
		{
			$db    = JFactory::getDbo();

			if ($state)
			{
				$object = (object) array(
					'staff_id' => $this->getUser()->id,
					'ticket_id' => $ticket_id,
					'start' => JFactory::getDate('now')->toSql(),
					'end' => $db->getNullDate()
				);

				$db->insertObject('#__rsticketspro_timespent', $object, 'id');
			}
			else
			{
				$query = $db->getQuery(true)
					->update($db->qn('#__rsticketspro_timespent'))
					->set($db->qn('end') . ' = ' . $db->q(JFactory::getDate('now')->toSql()))
					->where($db->qn('ticket_id') . ' = ' . $db->q($ticket_id))
					->where($db->qn('start') . ' != ' . $db->q($db->getNullDate()))
					->where($db->qn('end') . ' = ' . $db->q($db->getNullDate()));

				$db->setQuery($query);
				$db->execute();

				// update time spent on the ticket based on all the start/stop entries, only when the state is set to stop (0)
				if ($db->getAffectedRows())
				{
					$this->remakeTimeSpent($ticket_id);
				}
			}

			return true;
		}

		return false;
	}

	protected function remakeTimeSpent($ticket_id, $force_zero = false){
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query->select($db->qn('start'))
			->select($db->qn('end'))
			->from($db->qn('#__rsticketspro_timespent'))
			->where($db->qn('ticket_id') . ' = ' . $db->q($ticket_id));

		$db->setQuery($query);
		if ($intervals = $db->loadObjectList())
		{
			// hold all the seconds
			$total_time_sec = 0;
			foreach ($intervals as $interval)
			{
				$total_time_sec +=  JFactory::getDate($interval->end)->toUnix() - JFactory::getDate($interval->start)->toUnix();
			}

			if ($total_time_sec > 0)
			{
				$hours      = floor(($total_time_sec / 60) / 60);
				$minutes    = ($total_time_sec / 60) % 60;

				// 1.3 h (1 hour and 30 minutes) is not as 1.03 h (1 hour and 3 minutes)
				$minutes    = str_pad($minutes, 2, '0', STR_PAD_LEFT);
				$total_time = sprintf('%d.%s', $hours, $minutes);

				$object = (object) array(
					'id' => $ticket_id,
					'time_spent' => $total_time
				);
				JFactory::getDbo()->updateObject('#__rsticketspro_tickets', $object, array('id'));
			}
		}
		else if ($force_zero)
		{
			$object = (object) array(
				'id' => $ticket_id,
				'time_spent' => 0
			);
			JFactory::getDbo()->updateObject('#__rsticketspro_tickets', $object, array('id'));
		}
	}

	public function getTicketSections()
	{
		$isStaff              = $this->isStaff();
		$sections             = array();
		$sections['messages'] = JText::_('RST_TICKET_MESSAGES');
		$sections['info']     = JText::_('RST_TICKET_INFORMATION');
		$enableTimeSpent      = RSTicketsProHelper::getConfig('enable_time_spent');
		$showInfo             = RSTicketsProHelper::getConfig('show_ticket_info');

		if ($isStaff && $enableTimeSpent)
		{
			$sections['time'] = JText::_('RST_TIME_SPENT');
		}

		if ($isStaff && $showInfo)
		{
			$sections['submitter'] = JText::_('RST_SUBMITTER_INFORMATION');
		}

		$sections['custom_fields'] = JText::_('RST_TICKET_CUSTOM_FIELDS');

		if ($isStaff)
		{
			$sections['history'] = JText::_('RST_TICKET_HISTORY');
		}

		return $sections;
	}

	public function getOtherTickets()
	{
		$ticket      = $this->getItem();
		$customer_id = $ticket->customer_id;
		$ticket_id   = $ticket->id;

		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query->select($db->qn('t.id'))
			->select($db->qn('t.subject'))
			->select($db->qn('t.replies'))
			->select($db->qn('t.code'))
			->select($db->qn('t.date'))
			->select($db->qn('s.name', 'status_name'))
			->from($db->qn('#__rsticketspro_tickets', 't'))
			->join('left', $db->qn('#__rsticketspro_statuses', 's') . ' ON (' . $db->qn('t.status_id') . '=' . $db->qn('s.id') . ')')
			->where($db->qn('t.id') . '!=' . $db->q($ticket_id))
			->where($db->qn('t.customer_id') . '=' . $db->q($customer_id))
			->order($db->qn('date') . ' ' . $db->escape('desc'));
		$db->setQuery($query);

		return $db->loadObjectList();
	}

	public function getDepartment()
	{
		// get model
		$model       = $this->getInstance('Submit', 'RsticketsproModel', array(
			'option'     => 'com_rsticketspro',
			'table_path' => JPATH_ADMINISTRATOR . '/components/com_rsticketspro/tables'
		));
		$departments = $model->getDepartments();
		$ticket      = $this->getItem();

		return $departments[$ticket->department_id];
	}

	public function getDepartments()
	{
		$db      = JFactory::getDbo();
		$query   = $db->getQuery(true);
		$options = array();

		$query->select($db->qn('id'))
			->select($db->qn('name'))
			->from('#__rsticketspro_departments')
			->where($db->qn('published') . '=' . $db->q(1))
			->order($db->qn('ordering') . ' ' . $db->escape('asc'));
		$db->setQuery($query);
		if ($departments = $db->loadObjectList())
		{
			foreach ($departments as $department)
			{
				$tmp = JHtml::_('select.option', $department->id, JText::_($department->name));

				// Add the option object to the result set.
				$options[] = $tmp;
			}
		}

		return $options;
	}

	public function getStatuses()
	{
		$db      = JFactory::getDbo();
		$query   = $db->getQuery(true);
		$options = array();

		$query->select($db->qn('id'))
			->select($db->qn('name'))
			->from('#__rsticketspro_statuses')
			->where($db->qn('published') . '=' . $db->q(1))
			->order($db->qn('ordering') . ' ' . $db->escape('asc'));
		$db->setQuery($query);
		if ($statuses = $db->loadObjectList())
		{
			foreach ($statuses as $status)
			{
				$tmp = JHtml::_('select.option', $status->id, JText::_($status->name));

				// Add the option object to the result set.
				$options[] = $tmp;
			}
		}

		return $options;
	}

	public function getPriorities()
	{
		$db      = JFactory::getDbo();
		$query   = $db->getQuery(true);
		$options = array();

		$query->select($db->qn('id'))
			->select($db->qn('name'))
			->from('#__rsticketspro_priorities')
			->where($db->qn('published') . '=' . $db->q(1))
			->order($db->qn('ordering') . ' ' . $db->escape('asc'));
		$db->setQuery($query);
		if ($priorities = $db->loadObjectList())
		{
			foreach ($priorities as $priority)
			{
				$tmp = JHtml::_('select.option', $priority->id, JText::_($priority->name));

				// Add the option object to the result set.
				$options[] = $tmp;
			}
		}

		return $options;
	}

	public function updateFields($id, $fields)
	{
		$db    = $this->getDbo();
		$query = $db->getQuery(true);

		$ticket = $this->getTicket($id);

		foreach ($fields as $field => $value)
		{
			// convert arrays to strings
			if (is_array($value))
			{
				$value = implode("\n", $value);
			}

			// get field id
			$query->select($db->qn('id'))
				->from($db->qn('#__rsticketspro_custom_fields'))
				->where($db->qn('name') . '=' . $db->q($field))
				->where($db->qn('department_id') . '=' . $db->q($ticket->department_id))
				->where($db->qn('published') . '=' . $db->q(1));
			$db->setQuery($query);
			if ($field_id = $db->loadResult())
			{
				$query->clear();

				$query->select($db->qn('id'))
					->from($db->qn('#__rsticketspro_custom_fields_values'))
					->where($db->qn('custom_field_id') . '=' . $db->q($field_id))
					->where($db->qn('ticket_id') . '=' . $db->q($id));
				$db->setQuery($query);
				$value_id = $db->loadResult();

				$table = JTable::getInstance('Customfieldsvalues', 'RsticketsproTable');
				$table->save(array(
					'id'              => $value_id,
					'custom_field_id' => $field_id,
					'ticket_id'       => $id,
					'value'           => $value
				));
			}

			$query->clear();
		}
	}

	public function updateInfo($id, $data)
	{
		// bind id to data array
		$data['id'] = $id;
		// get db object
		$db = $this->getDbo();
		// original ticket
		$original = $this->getTicket($id);

		if (empty($data['department_id']))
		{
			$data['department_id'] = $original->department_id;
		}

		// department has changed
		if (!empty($data['department_id']) && $data['department_id'] != $original->department_id)
		{
			// generate new code based on department
			require_once JPATH_ADMINISTRATOR . '/components/com_rsticketspro/helpers/ticket.php';
			$data['code'] = RSTicketsProTicketHelper::generateCode($data['department_id']);

			// update custom fields if they match the ones of the new department
			$query = $db->getQuery(true);
			$query->select($db->qn('v.custom_field_id'))
				->select($db->qn('v.value'))
				->select($db->qn('cf.type'))
				->select($db->qn('cf.name'))
				->from($db->qn('#__rsticketspro_custom_fields_values', 'v'))
				->join('left', $db->qn('#__rsticketspro_custom_fields', 'cf') . ' ON (' . $db->qn('cf.id') . '=' . $db->qn('v.custom_field_id') . ')')
				->where($db->qn('v.ticket_id') . '=' . $db->q($id))
				->where($db->qn('cf.published') . '=' . $db->q(1));
			$db->setQuery($query);
			if ($currentFields = $this->_db->loadObjectList())
			{
				foreach ($currentFields as $field)
				{
					// check if there's a field that matches
					$query = $db->getQuery(true);
					$query->select($db->qn('id'))
						->from($db->qn('#__rsticketspro_custom_fields'))
						->where($db->qn('department_id') . '=' . $db->q($data['department_id']))
						->where($db->qn('name') . ' LIKE ' . $db->q($field->name))
						->where($db->qn('published') . '=' . $db->q(1));
					$db->setQuery($query);
					// found a field with the same name
					if ($found = $db->loadObject())
					{
						$query = $db->getQuery(true);
						$query->select($db->qn('id'))
							->from($db->qn('#__rsticketspro_custom_fields_values'))
							->where($db->qn('custom_field_id') . '=' . $db->q($found->id))
							->where($db->qn('ticket_id') . '=' . $db->q($id));
						$db->setQuery($query);
						// did not find a duplicate
						if (!$db->loadResult())
						{
							// add the new value
							$value = JTable::getInstance('Customfieldsvalues', 'RsticketsproTable');
							$value->save(array(
								'custom_field_id' => $found->id,
								'ticket_id'       => $id,
								'value'           => $field->value
							));
						}
					}
				}
			}

			// If assigned staff does not have access to this new department, set it as unassigned
			if ($original->staff_id > 0 && !$this->staffHasAccessToDepartment($original->staff_id, $data['department_id']))
			{
				// If we change the department & staff member at the same time, make sure the new staff member has access
				if (!empty($data['staff_id']))
				{
					if (!$this->staffHasAccessToDepartment($data['staff_id'], $data['department_id']))
					{
						$data['staff_id'] = 0;
					}
				}
				else
				{
					$data['staff_id'] = 0;
				}
			}

			// send email to the staff member that gets assigned this ticket
			require_once JPATH_ADMINISTRATOR . '/components/com_rsticketspro/helpers/emails.php';
			RSTicketsProEmailsHelper::sendEmail('notification_department_change', array(
				'ticket'        => $original,
				'department_id' => $original->department_id,
				'to'            => $data['department_id'],
				'code'			=> $data['code']
			));

			RSTicketsProHelper::saveSystemMessage($id, array(
				'type' => 'department',
				'from' => $original->department_id,
				'to'   => $data['department_id'],
			));
		}

		// staff member has changed
		if (isset($data['staff_id']) && $data['staff_id'] != $original->staff_id)
		{
			RSTicketsProHelper::saveSystemMessage($id, array(
				'type' => 'staff',
				'from' => $original->staff_id,
				'to'   => $data['staff_id'],
			));
		}

		if (!empty($data['staff_id']) && $data['staff_id'] != $original->staff_id)
		{
			if (!$this->staffHasAccessToDepartment($data['staff_id'], $data['department_id']))
			{
				unset($data['staff_id']);
				JFactory::getApplication()->enqueueMessage(JText::sprintf('RST_COULD_NOT_CHANGE_STAFF_MEMBER_DOES_NOT_BELONG_TO_TICKET_DEPARTMENT', $original->code), 'warning');
			}
			else
			{
				// get department
				$department = RSTicketsProHelper::getDepartment($data['department_id']);
				if ($department->notify_assign)
				{
					// bind new data
					$original->bind($data);

					// send email to the staff member that gets assigned this ticket
					require_once JPATH_ADMINISTRATOR . '/components/com_rsticketspro/helpers/emails.php';
					RSTicketsProEmailsHelper::sendEmail('add_ticket_staff', array(
						'ticket'        => $original,
						'department_id' => $department->id
					));
				}
			}
		}

		if (isset($data['status_id']) && $data['status_id'] != $original->status_id)
		{
			// add in History 
			if ($data['status_id'] == RST_STATUS_OPEN)
			{
				RSTicketsProHelper::addHistory($id, 'reopen');
			}
			elseif ($data['status_id'] == RST_STATUS_CLOSED)
			{
				$db    = $this->getDbo();
				$query = $db->getQuery(true);
				$query->clear()
					->update($db->qn('#__rsticketspro_tickets'))
					->set($db->qn('closed') . ' = ' . $db->q(JFactory::getDate()->toSql()))
					->where($db->qn('id') . ' = ' . $db->q($id));
				$db->setQuery($query);
				$db->execute();

				RSTicketsProHelper::addHistory($id, 'close');
			}
			elseif ($data['status_id'] == RST_STATUS_ON_HOLD)
			{
				RSTicketsProHelper::addHistory($id, 'onhold');
			}
			
			// If we've re-opened this ticket, don't close it again.
			if ($original->status_id == RST_STATUS_CLOSED)
			{
				$data['autoclose_sent'] = 0;
			}

			RSTicketsProHelper::saveSystemMessage($id, array(
				'type' => 'status',
				'from' => $original->status_id,
				'to'   => $data['status_id']
			));
		}

		if (isset($data['priority_id']) && $data['priority_id'] != $original->priority_id)
		{
			RSTicketsProHelper::saveSystemMessage($id, array(
				'type' => 'priority',
				'from' => $original->priority_id,
				'to'   => $data['priority_id']
			));
		}
		
		// validate the provided alternative email address if any
		if (!empty($data['alternative_email'])) {
			// remove any whitespaces
			$data['alternative_email'] = trim($data['alternative_email']);

			if (strlen($data['alternative_email']) > 0 && !JMailHelper::isEmailAddress($data['alternative_email'])) {
				unset($data['alternative_email']);
			}
		}

		$ticket = $this->getTable();
		$ticket->save($data);

   		// Hack start : to save school in xref table while update

		RSTicketsProHelper::trigger('onAfterStoreTicket', array($data, $ticket));

		// Hack end : to save school in xref table while update
	}

	protected function staffHasAccessToDepartment($user_id, $department_id)
	{
		static $cache;

		if (!is_array($cache))
		{
			$cache 	= array();
			$db 	= $this->getDbo();

			$query = $db->getQuery(true)
				->select('*')
				->from($db->qn('#__rsticketspro_staff_to_department'));
			if ($results = $db->setQuery($query)->loadObjectList())
			{
				foreach ($results as $result)
				{
					if (!isset($cache[$result->user_id]))
					{
						$cache[$result->user_id] = array();
					}

					$cache[$result->user_id][] = $result->department_id;
				}
			}
		}
		
		if (!isset($cache[$user_id]))
		{
			return false;
		}

		return in_array($department_id, $cache[$user_id]);
	}

	public function reply($id, $data, $files)
	{
		$model       = $this->getInstance('Submit', 'RsticketsproModel');
		$departments = $model->getDepartments();
		$ticket      = $this->getTicket($id);
		$department  = $departments[$ticket->department_id];
		$isStaff     = RSTicketsProHelper::isStaff($data['user_id']);

		$data['files'] = array();
		// let's validate files if the department allows uploads for this user
		if ($department->upload)
		{
			// too many files
			if ($department->upload_files > 0 && count($files) > $department->upload_files)
			{
				$files = array_slice($files, 0, $department->upload_files);
			}

			$upload_extensions = explode(', ', $department->upload_extensions);

			foreach ($files as $file)
			{
				if ($file['error'] == UPLOAD_ERR_NO_FILE)
				{
					continue;
				}

				if ($file['error'] != UPLOAD_ERR_OK)
				{
					switch ($file['error'])
					{
						default:
							$msg = 'RST_TICKET_UPLOAD_ERROR';
							break;
						case UPLOAD_ERR_INI_SIZE:
							$msg = 'RST_TICKET_UPLOAD_ERROR_INI_SIZE';
							break;
						case UPLOAD_ERR_FORM_SIZE:
							$msg = 'RST_TICKET_UPLOAD_ERROR_FORM_SIZE';
							break;
						case UPLOAD_ERR_PARTIAL:
							$msg = 'RST_TICKET_UPLOAD_ERROR_PARTIAL';
							break;
						case UPLOAD_ERR_NO_TMP_DIR:
							$msg = 'RST_TICKET_UPLOAD_ERROR_NO_TMP_DIR';
							break;
						case UPLOAD_ERR_CANT_WRITE:
							$msg = 'RST_TICKET_UPLOAD_ERROR_CANT_WRITE';
							break;
						case UPLOAD_ERR_EXTENSION:
							$msg = 'RST_TICKET_UPLOAD_ERROR_PHP_EXTENSION';
							break;
					}

					$this->setError(JText::sprintf($msg, $file['name']));

					return false;
				}

				// is this an allowed extension?
				if (!RSTicketsProHelper::isAllowedExtension(RSTicketsProHelper::getExtension($file['name']), $upload_extensions))
				{
					$this->setError(JText::sprintf('RST_TICKET_UPLOAD_EXTENSION_ERROR', $file['name'], $department->upload_extensions));

					return false;
				}
				// check file size
				if ($department->upload_size > 0 && $file['size'] > $department->upload_size * 1048576)
				{
					$this->setError(JText::sprintf('RST_TICKET_UPLOAD_SIZE_ERROR', $file['name'], $department->upload_size));

					return;
				}

				$data['files'][] = array(
					'src'      => 'upload',
					'tmp_name' => $file['tmp_name'],
					'name'     => $file['name']
				);
			}
		}

		// must write a message
		if (empty($data['message']))
		{
			$this->setError(JText::_('RST_TICKET_REPLY_ERROR'));

			return false;
		}

        // Need to check consent
        if (RSTicketsProHelper::getConfig('forms_consent'))
        {
	        if ($isStaff && RSTicketsProHelper::getConfig('forms_consent_staff_skip'))
	        {
		        $data['consent'] = 1;
	        }

	        if (empty($data['consent']))
	        {
		        $this->setError(JText::_('COM_RSTICKETSPRO_CONSENT_IS_NEEDED_TO_SUBMIT_THIS_FORM'));
		        return false;
	        }
        }

		require_once JPATH_ADMINISTRATOR . '/components/com_rsticketspro/helpers/ticket.php';

		// trigger event after saving the reply
		RSTicketsProHelper::trigger('onBeforeStoreTicketReply', array($data));

		$ticket = new RSTicketsProTicketHelper();
		$ticket->bind($data);
		if (!$ticket->saveMessage())
		{
			$this->setError($ticket->getError());

			return false;
		}

		// trigger event after saving the reply
		RSTicketsProHelper::trigger('onAfterStoreTicketReply', array($data));

		return true;
	}

	public function setRating($id, $rating)
	{
		if ($rating > 5)
		{
			$rating = 5;
		}
		if ($rating < 1)
		{
			$rating = 1;
		}

		// original ticket
		$object = (object) array(
			'id'       => $id,
			'feedback' => $rating
		);
		JFactory::getDbo()->updateObject('#__rsticketspro_tickets', $object, array('id'));
	}

	public function isConvertedToKB($id)
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('*')
			->from($db->qn('#__rsticketspro_kb_content'))
			->where($db->qn('from_ticket_id') . '=' . $db->q($id));
		$db->setQuery($query);

		return $db->loadObject();
	}

	public function getIsPrint()
	{
		return JFactory::getApplication()->input->getInt('print');
	}

	public function getRSFieldset()
	{
		require_once JPATH_ADMINISTRATOR . '/components/com_rsticketspro/helpers/adapters/fieldset.php';

		$fieldset = new RSFieldset();

		return $fieldset;
	}

	public function getRSTabs()
	{
		return new RsticketsproAdapterTabs('com-rsticketspro-ticket');
	}

	public function getRSAccordion()
	{
		require_once JPATH_ADMINISTRATOR . '/components/com_rsticketspro/helpers/adapters/accordion.php';

		$tabs = new RSAccordion('com-rsticketspro-ticket');

		return $tabs;
	}

	public function getRSPlain()
	{
		$plain = new RsticketsproAdapterPlain('com-rsticketspro-ticket');

		return $plain;
	}

	protected function getId()
	{
		$input = JFactory::getApplication()->input;
		$id    = $input->getInt('id', 0);
		$cid   = $input->getInt('cid', 0);

		if (!empty($cid) && empty($id))
		{
			return $cid;
		}

		return $id;
	}
}
