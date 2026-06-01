<?php
/**
* @version 2.0.0
* @package RSTickets! Pro 2.0.0
* @copyright (C) 2010 www.rsjoomla.com
* @license GPL, http://www.gnu.org/licenses/gpl-2.0.html
*/

defined('_JEXEC') or die('Restricted access');

class RsticketsproModelCrons extends JModelList
{	
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'name', 'ordering', 'server', 'id', 'published', 'state'
			);
		}

		parent::__construct($config);
	}
	
	protected function getListQuery() {
		$db 	= JFactory::getDbo();
		$query 	= $db->getQuery(true);
		
		// get filtering states
		$search = $this->getState('filter.search', '');
		$state	= $this->getState('filter.state');
		
		$query->select('*')->from('#__rsticketspro_accounts');
		// search
		if (strlen($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where($db->qn('id') . ' = ' . (int) substr($search, 3));
			}
			else
			{
				// Escape the search token.
				$token = $db->q('%' . str_replace(' ', '%', $db->escape($search, true)) . '%', false);

				// Compile the different search clauses.
				$searches = array();
				$searches[] = $db->qn('name').' LIKE ' . $token;
				$searches[] = $db->qn('server').' LIKE ' . $token;

				// Add the clauses to the query.
				$query->where('(' . implode(' OR ', $searches) . ')');
			}
		}
		// state
		if ($state != '')
		{
			$query->where($db->qn('published').' = '.(int) $state);
		}
		
		// order by
		$query->order($db->qn($this->getState('list.ordering', 'name')).' '.$db->escape($this->getState('list.direction', 'asc')));
		
		return $query;
	}
	
	protected function populateState($ordering = 'name', $direction = 'asc')
	{
		$this->setState('filter.search', 	$this->getUserStateFromRequest($this->context.'.filter.search', 'filter_search'));
		$this->setState('filter.state', 	$this->getUserStateFromRequest($this->context.'.filter.state', 'filter_state'));
		
		// List state information.
		parent::populateState($ordering, $direction);
	}
}