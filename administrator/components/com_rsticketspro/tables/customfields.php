<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproTableCustomfields extends JTable
{
	public $id;
	public $department_id;
	public $name;
	public $label;
	public $type;
	public $values;
	public $additional;
	public $validation;
	public $required;
	public $description;
	public $published;
	public $ordering;
	
	public function __construct(&$db)
	{
		parent::__construct('#__rsticketspro_custom_fields', 'id', $db);
	}
	
	public function check()
	{
		try
		{
			if (in_array($this->type, array('select', 'multipleselect', 'checkbox', 'radio')) && !strlen($this->values))
			{
				throw new Exception(JText::_('RST_CUSTOM_FIELD_VALUES_ERROR'));
			}

			// this needs to be filtered
			if (strlen($this->name))
			{
				$this->name = JFilterOutput::stringURLSafe($this->name);
			}

			// check if there's a custom field with the same name
			$db = $this->getDbo();
			$query = $db->getQuery(true);
			$query->select($db->qn('id'))
				->from('#__rsticketspro_custom_fields')
				->where($db->qn('name').'='.$db->q($this->name))
				->where($db->qn('department_id').'='.$db->q($this->department_id));

			if ($this->id)
			{
				$query->where($db->qn('id').'!='.$db->q($this->id));
			}

			$db->setQuery($query);
			if ($db->loadResult())
			{
				throw new Exception(JText::sprintf('RST_CUSTOM_FIELD_UNIQUE_NAME_ERROR', $this->name));
			}

			if (!$this->id && !$this->ordering)
			{
				$this->ordering = $this->getNextOrder($db->qn('department_id') . ' = ' . $db->q($this->department_id));
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
			
			// delete all values pertaining to this custom field
			$query->delete('#__rsticketspro_custom_fields_values')
				  ->where($db->qn('custom_field_id').'='.$db->q($pk));
			$db->setQuery($query)->execute();
		}
		
		return $deleted;
	}
}