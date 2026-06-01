<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproModelKbrules extends JModelList
{
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'r.name', 'c.name', 'r.published', 'r.id', 'state'
			);
		}

		parent::__construct($config);
	}
	
	protected function getListQuery()
	{
		$db 	= JFactory::getDbo();
		$query 	= $db->getQuery(true);
		
		// get filtering states
		$search = $this->getState('filter.search', '');
		$state 	= $this->getState('filter.state');
		
		$query->select($db->qn('r.id'))
			  ->select($db->qn('r.category_id'))
			  ->select($db->qn('r.name'))
			  ->select($db->qn('r.published'))
			  ->select($db->qn('c.name', 'category_name'))
			  ->from($db->qn('#__rsticketspro_kb_rules', 'r'));
		
		// join categories
		$query->join('left', $db->qn('#__rsticketspro_kb_categories', 'c').' ON ('.$db->qn('r.category_id').'='.$db->qn('c.id').')');

		// search
		if (strlen($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where($db->qn('r.id') . ' = ' . (int) substr($search, 3));
			}
			else
			{
				$query->where($db->qn('r.name') . ' LIKE ' . $db->q('%'.str_replace(' ', '%', $db->escape($search, true)).'%', false));
			}
		}

		// published/unpublished
		if ($state != '')
		{
			$query->where($db->qn('r.published') . '=' . $db->q($state));
		}
		// order by
		$query->order($db->qn($this->getState('list.ordering', 'r.name')).' '.$db->escape($this->getState('list.direction', 'asc')));
		
		return $query;
	}
	
	protected function populateState($ordering = 'r.name', $direction = 'asc')
	{
		$this->setState('filter.search', $this->getUserStateFromRequest($this->context.'.filter.search', 'filter_search'));
		$this->setState('filter.state', $this->getUserStateFromRequest($this->context.'.filter.state', 'filter_state'));
		
		// List state information.
		parent::populateState($ordering, $direction);
	}
	
	public function getDepartments()
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true)
			->select($db->qn('id'))
			->select($db->qn('name'))
			->from($db->qn('#__rsticketspro_departments'))
			->order($db->qn('ordering') . ' asc');
		if ($results = $this->_getList($query))
		{
			foreach ($results as $result)
			{
				$result->name = JText::_($result->name);
			}
		}
		
		return $results;
	}
	
	public function getPriorities()
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true)
			->select($db->qn('id'))
			->select($db->qn('name'))
			->from($db->qn('#__rsticketspro_priorities'))
			->order($db->qn('ordering') . ' asc');

		if ($results = $this->_getList($query))
		{
			foreach ($results as $result)
			{
				$result->name = JText::_($result->name);
			}
		}
		
		return $results;
	}
	
	public function getStatuses()
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true)
			->select($db->qn('id'))
			->select($db->qn('name'))
			->from($db->qn('#__rsticketspro_statuses'))
			->order($db->qn('ordering') . ' asc');

		if ($results = $this->_getList($query))
		{
			foreach ($results as $result)
			{
				$result->name = JText::_($result->name);
			}
		}
		
		return $results;
	}
	
	public function getCustomFields()
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true)
			->select('*')
			->from($db->qn('#__rsticketspro_custom_fields'))
			->order($db->qn('department_id') . ' asc')
			->order($db->qn('ordering') . ' asc');

		return $this->_getList($query);
	}
	
	public function getCustomFieldValues()
	{
		$return = array();
		$db 	= JFactory::getDbo();
		$cfid 	= JFactory::getApplication()->input->getInt('cfid');

		$query = $db->getQuery(true)
			->select($db->qn('values'))
			->from($db->qn('#__rsticketspro_custom_fields'))
			->where($db->qn('id') . ' = ' . $db->q($cfid));

		if ($values = $db->setQuery($query)->loadResult())
		{
			$values = str_replace("\r\n", "\n", $values);
			$values = explode("\n", $values);
			foreach ($values as $value)
			{
				$tmp = new stdClass();
				$tmp->id = $tmp->name = $value;
				
				$return[] = $tmp;
			}
		}
		
		return $return;
	}
}