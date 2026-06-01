<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproModelCustomfields extends JModelList
{
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'f.name', 'department_name', 'label', 'type', 'required', 'f.published', 'f.ordering', 'f.id', 'state', 'department_id'
			);
		}

		parent::__construct($config);
	}
	
	protected function getListQuery()
	{
		$db 	= JFactory::getDbo();
		$query 	= $db->getQuery(true);
		
		// get filtering states
		$search 		= $this->getState('filter.search', '');
		$state 			= $this->getState('filter.state');
		$department_id 	= $this->getState('filter.department_id');
		
		$query->select('f.*')
			  ->select($db->qn('d.name', 'department_name'))
			  ->from($db->qn('#__rsticketspro_custom_fields', 'f'))
			  ->join('left', $db->qn('#__rsticketspro_departments', 'd').' ON ('.$db->qn('f.department_id').' = '.$db->qn('d.id').')');
		
		if ($department_id)
		{
			$query->where($db->qn('department_id').'='.$db->q($department_id));
		}
		
		// search
		if (strlen($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where($db->qn('f.id') . ' = ' . (int) substr($search, 3));
			}
			else
			{
				$query->where($db->qn('f.name') . ' LIKE ' . $db->q('%'.str_replace(' ', '%', $db->escape($search, true)).'%', false));
			}
		}
		// published/unpublished
		if ($state != '')
		{
			$query->where($db->qn('f.published').'='.$db->q($state));
		}
		// order by
		$query->order($db->qn($this->getState('list.ordering', 'f.ordering')).' '.$db->escape($this->getState('list.direction', 'asc')));
		
		return $query;
	}
	
	protected function populateState($ordering = 'f.ordering', $direction = 'asc')
	{
		$this->setState('filter.search', $this->getUserStateFromRequest($this->context.'.filter.search', 'filter_search'));
		$this->setState('filter.state', $this->getUserStateFromRequest($this->context.'.filter.state',  'filter_state'));
		$this->setState('filter.department_id', $this->getUserStateFromRequest($this->context.'.filter.department_id', 'filter_department_id'));
		
		// List state information.
		parent::populateState($ordering, $direction);
	}
}