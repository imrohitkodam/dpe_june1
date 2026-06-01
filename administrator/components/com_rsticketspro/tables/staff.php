<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproTableStaff extends JTable
{
	public $id = null;
	
	public $group_id = null;
	public $user_id = null;
	public $priority_id = null;
	public $department_id = null;
	
	public $signature = '';
	
	public function __construct(& $db)
	{
		parent::__construct('#__rsticketspro_staff', 'id', $db);
	}
	
	public function load($keys = null, $reset = true)
	{
		$loaded = parent::load($keys, $reset);
		
		if ($loaded)
		{
			$db 	= $this->getDbo();
			$query  = $db->getQuery(true);
			
			$query->select($db->qn('department_id'))
				  ->from('#__rsticketspro_staff_to_department')
				  ->where($db->qn('user_id').'='.$db->q($this->user_id));
			
			$db->setQuery($query);
			
			$this->department_id = implode(',', $db->loadColumn());
		}
		
		return $loaded;
	}
	
	public function check()
	{
		try
		{
			if (!$this->user_id)
			{
				throw new Exception(JText::_('RST_STAFF_USER_ERROR'));
			}

			if (empty($this->department_id))
			{
				throw new Exception(JText::_('RST_STAFF_DEPARTMENT_ERROR'));
			}

			$db 	= $this->getDbo();
			$query  = $db->getQuery(true);

			$query->select('id')
				->from('#__rsticketspro_staff')
				->where($db->qn('user_id') . '=' . $db->q($this->user_id));

			if ($this->id)
			{
				$query->where($db->qn('id') . '!=' . $db->q($this->id));
			}

			if ($db->setQuery($query)->loadResult())
			{
				throw new Exception(JText::_('RST_STAFF_USER_EXISTS'));
			}
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());
			return false;
		}
		
		return true;
	}
	
	public function store($updateNulls = false)
	{
		$result = parent::store($updateNulls);
		if ($result)
		{
			$db 	= $this->getDbo();
			$query  = $db->getQuery(true);
			
			$query->delete('#__rsticketspro_staff_to_department')
				  ->where($db->qn('user_id') . '=' . $db->q($this->user_id));
			$db->setQuery($query)->execute();
			
			foreach ($this->department_id as $department_id)
			{
				$row = JTable::getInstance('Stafftodepartment', 'RsticketsproTable');
				$row->save(array(
					'id' => null,
					'user_id' => $this->user_id,
					'department_id' => $department_id
				));
			}
		}
		
		return $result;
	}
	
	public function delete($pk = null)
	{
		$deleted = parent::delete($pk);
		if ($deleted)
		{
			$db 	= $this->getDbo();
			$query  = $db->getQuery(true);
			
			// remove references from the #__rsticketspro_staff_to_department table
			$query->delete('#__rsticketspro_staff_to_department')
				  ->where($db->qn('user_id') . '=' . $db->q($this->user_id));
			$db->setQuery($query)->execute();
			
			// unassign all tickets assigned to this staff member
			$query->clear();
			$query->update('#__rsticketspro_tickets')
				  ->set($db->qn('staff_id') . '=' . $db->q(0))
				  ->where($db->qn('staff_id') . '=' . $db->q($this->user_id));
			$db->setQuery($query)->execute();
		}
		
		return $deleted;
	}
}