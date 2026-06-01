<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproTableTickets extends JTable
{
	public $id;
	public $department_id;
	public $staff_id;
	public $customer_id;
	public $code;
	public $subject;
	public $status_id;
	public $priority_id;
	public $date;
	public $alternative_email;
	public $last_reply;
	public $last_reply_customer;
	public $replies;
	public $autoclose_sent;
	public $closed;
	public $flagged;
	public $agent;
	public $referer;
	public $ip;
	public $logged;
	public $feedback;
	public $followup_sent;
	public $has_files;
	public $time_spent;

	public function __construct(& $db)
	{
		parent::__construct('#__rsticketspro_tickets', 'id', $db);
	}

	public function check()
	{
		if (!$this->id)
		{
			$this->closed = JFactory::getDbo()->getNullDate();

			if ($this->alternative_email === null)
			{
				$this->alternative_email = '';
			}

			$this->last_reply_customer = 1;
			$this->replies = 0;
			$this->autoclose_sent = 0;
			$this->flagged = 0;
			$this->feedback = 0;
			$this->has_files = 0;
			$this->time_spent = '0.00';
			$this->followup_sent = 0;
		}

		return true;
	}

	public function delete($pk = null)
	{
		$deleted = parent::delete($pk);
		if ($deleted)
		{
			$db    = $this->getDbo();
			$query = $db->getQuery(true);

			// delete all custom field values
			$query->delete('#__rsticketspro_custom_fields_values')
				->where($db->qn('ticket_id') . '=' . $db->q($pk));
			$db->setQuery($query)->execute();

			// delete all messages
			$query->clear();
			$query->delete('#__rsticketspro_ticket_messages')
				->where($db->qn('ticket_id') . '=' . $db->q($pk));
			$db->setQuery($query)->execute();

			// delete all notes
			$query->clear();
			$query->delete('#__rsticketspro_ticket_notes')
				->where($db->qn('ticket_id') . '=' . $db->q($pk));
			$db->setQuery($query)->execute();

			// delete all history
			$query->clear();
			$query->delete('#__rsticketspro_ticket_history')
				->where($db->qn('ticket_id') . '=' . $db->q($pk));
			$db->setQuery($query)->execute();

			// delete all files
			// physical files
			$query->clear();
			$query->select($db->qn('id'))
				->select($db->qn('ticket_message_id'))
				->from($db->qn('#__rsticketspro_ticket_files'))
				->where($db->qn('ticket_id') . '=' . $db->q($pk));
			$db->setQuery($query);
			if ($files = $db->loadObjectList())
			{

				foreach ($files as $file)
				{
					$hash = md5($file->id . ' ' . $file->ticket_message_id);
					JFile::delete(RST_UPLOAD_FOLDER . '/' . $hash);
				}
			}
			// from the database
			$query->clear();
			$query->delete('#__rsticketspro_ticket_files')
				->where($db->qn('ticket_id') . '=' . $db->q($pk));
			$db->setQuery($query)->execute();
		}

		return $deleted;
	}

	public function load($keys = null, $reset = true)
	{
		$loaded = parent::load($keys, $reset);

		if ($loaded)
		{
			// load customer
			$this->customer = JFactory::getUser($this->customer_id);

			// load staff
			$this->staff = JFactory::getUser($this->staff_id);

			// load department
			$this->department = $this->getInstance('Departments', 'RsticketsproTable');
			$this->department->load($this->department_id);

			// load status
			$this->status = $this->getInstance('Statuses', 'RsticketsproTable');
			$this->status->load($this->status_id);

			// load priority
			$this->priority = $this->getInstance('Priorities', 'RsticketsproTable');
			$this->priority->load($this->priority_id);

			// get custom fields
			$this->fields = $this->getCustomFields();

			// load number of notes
			$this->notes = $this->getNotesCount();
		}

		return $loaded;
	}

	protected function getCustomFields()
	{
		$db     = $this->getDbo();
		$query  = $db->getQuery(true);
		$return = array();

		// get custom fields
		$query->select('*')
			->from($db->qn('#__rsticketspro_custom_fields'))
			->where($db->qn('department_id') . '=' . $db->q($this->department_id))
			->where($db->qn('published') . '=' . $db->q(1))
			->order($db->qn('ordering') . ' ' . $db->escape('asc'));
		$db->setQuery($query);
		$fields = $db->loadObjectList();

		// get values as well, sort them by custom field ids
		$query->clear();
		$query->select('*')
			->from($db->qn('#__rsticketspro_custom_fields_values'))
			->where($db->qn('ticket_id') . '=' . $db->q($this->id));
		$db->setQuery($query);
		$values = $db->loadObjectList('custom_field_id');

		foreach ($fields as $field)
		{
			$field->value = '';
			if (isset($values[$field->id]))
			{
				$field->value = $values[$field->id]->value;
			}

			$return[] = $field;
		}

		return $fields;
	}

	protected function getNotesCount()
	{
		$db    = $this->getDbo();
		$query = $db->getQuery(true);

		$query->select('COUNT(' . $db->qn('id') . ')')
			->from($db->qn('#__rsticketspro_ticket_notes'))
			->where($db->qn('ticket_id') . '=' . $db->q($this->id));
		$db->setQuery($query);

		return $db->loadResult();
	}
}