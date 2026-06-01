<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproTableDepartments extends JTable
{
	public $id = null;
	
	public $name = '';
	public $prefix = '';
	public $assignment_type = 1; // 0 - static, 1 - auto
	public $generation_rule = 1; // 0 - sequential, 1 - random
	public $next_number = 1;
	public $email_use_global = 1;
	public $email_address = '';
	public $email_address_fullname = '';
	public $email_address_reply_to = '';
	public $email_address_reply_to_name = '';
	public $customer_send_email = 1; // 0 - no, 1 - yes
	public $customer_send_copy_email = 1; // 0 - no, 1 - yes
	public $customer_attach_email = 1;
	public $staff_send_email = 1; // 0 - no, 1 - yes
	public $staff_attach_email = 1;
	public $upload = 1; // 0 - no, 1 - yes, 2 - registered
	public $upload_extensions = 'zip';
	public $upload_size = 0;
	public $upload_files = 0;
	public $download_type = 'attachment';
	public $notify_new_tickets_to = '';
	public $notify_assign = 0; // 0 - no, 1 - yes
	public $priority_id = 0;
	public $cc = '';
	public $bcc = '';
	public $predefined_subjects = '';
	public $jgroups = '';

	public $published = 1;
	public $ordering = null;

	protected $_jsonEncode = array('jgroups');
	
	public function __construct(&$db)
	{
		parent::__construct('#__rsticketspro_departments', 'id', $db);
	}

	public function bind($src, $ignore = array())
	{
		// Nothing selected sends no value, so we force an empty value here
		if (!array_key_exists('jgroups', $src))
		{
			$src['jgroups'] = '';
		}

		return parent::bind($src, $ignore);
	}

	public function check()
	{
		try
		{
			$db 	= $this->getDbo();
			$query 	= $db->getQuery(true);

			// this needs to be in uppercase
			$this->prefix = strtoupper($this->prefix);

			// need to make sure the prefix is unique
			$query->select($db->qn('id'))
				->from('#__rsticketspro_departments')
				->where($db->qn('prefix').'='.$db->q($this->prefix));

			if ($this->id)
			{
				$query->where($db->qn('id').'!='.$db->q($this->id));
			}

			$db->setQuery($query);
			if ($db->loadResult())
			{
				throw new Exception(JText::sprintf('RST_DEPARTMENT_UNIQUE_PREFIX_ERROR', $this->prefix));
			}

			if (!$this->email_use_global)
			{
				if (!strlen($this->email_address))
				{
					throw new Exception(JText::_('RST_DEPARTMENT_FROM_EMAIL_ERROR'));
				}

				if (!strlen($this->email_address_fullname))
				{
					throw new Exception(JText::_('RST_DEPARTMENT_FROM_NAME_ERROR'));
				}
			}

			if (!$this->id && !$this->ordering)
			{
				$this->ordering = $this->getNextOrder();
			}
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());
			return false;
		}
		
		return true;
	}
	
	public function delete($pk = null)
	{
		$deleted = parent::delete($pk);
		if ($deleted)
		{
			$db = $this->getDbo();
			$query = $db->getQuery(true);
			
			// do we have any custom fields that we need to delete?
			$query->select($db->qn('id'))
				  ->from('#__rsticketspro_custom_fields')
				  ->where($db->qn('department_id').'='.$db->q($pk));
			$db->setQuery($query);
			if ($custom_field_ids = $db->loadColumn())
			{
				// delete values
				$query->clear();
				$query->delete('#__rsticketspro_custom_fields_values')
					  ->where($db->qn('custom_field_id').' IN ('.implode(',', $custom_field_ids).')');
				$db->setQuery($query)->execute();
				
				// delete custom fields
				$query->clear();
				$query->delete('#__rsticketspro_custom_fields')
					  ->where($db->qn('id').' IN ('.implode(',', $custom_field_ids).')');
				$db->setQuery($query)->execute();
			}
			
			// remove the department from existing staff members
			$query->clear();
			$query->delete('#__rsticketspro_staff_to_department')
				  ->where($db->qn('department_id').'='.$db->q($pk));
			$db->setQuery($query)->execute();
			
			// create the subquery that gets all tickets belonging to this department
			$subquery = $db->getQuery(true);
			$subquery->select($db->qn('id'))
					 ->from('#__rsticketspro_tickets')
					 ->where($db->qn('department_id').'='.$db->q($pk));
			
			// delete messages			
			$query->clear();
			$query->delete('#__rsticketspro_ticket_messages')
				  ->where($db->qn('ticket_id').' IN ('.(string) $subquery.')');
			$db->setQuery($query)->execute();
			
			// delete notes 
			$query->clear();
			$query->delete('#__rsticketspro_ticket_notes')
				  ->where($db->qn('ticket_id').' IN ('.(string) $subquery.')');
			$db->setQuery($query)->execute();
			
			// delete files
			$query->clear();
			$query->delete('#__rsticketspro_ticket_files')
				  ->where($db->qn('ticket_id').' IN ('.(string) $subquery.')');
			$db->setQuery($query)->execute();
			
			// delete tickets
			$query->clear();
			$query->delete('#__rsticketspro_tickets')
				  ->where($db->qn('department_id').'='.$db->q($pk));
			$db->setQuery($query)->execute();
		}
		
		return $deleted;
	}
}