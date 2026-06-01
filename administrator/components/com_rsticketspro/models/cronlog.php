<?php
/**
* @version 2.0.0
* @package RSTickets! Pro 2.0.0
* @copyright (C) 2010 www.rsjoomla.com
* @license GPL, http://www.gnu.org/licenses/gpl-2.0.html
*/

defined('_JEXEC') or die('Restricted access');

class RsticketsproModelCronlog extends JModelList
{
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'a.name', 'al.date', 'al.subject'
			);
		}

		parent::__construct($config);
	}
	
	protected function getListQuery()
	{
		$db 	= JFactory::getDbo();
		$query 	= $db->getQuery(true);
		
		// get filtering states
		$search = $this->getState('filter.search');
		
		$query->select($db->qn('al').'.*')
			->select($db->qn('a.name'))
			->from($db->qn('#__rsticketspro_accounts_log','al'))
			->join('LEFT',$db->qn('#__rsticketspro_accounts','a').' ON '.$db->qn('al.account_id').' = '.$db->qn('a.id'));

		// search
		if ($search != '')
		{
			$search = $db->q('%'.str_replace(' ', '%', $db->escape($search, true)).'%', false);
			$query->where('('.$db->qn('al.description').' LIKE '.$search.' OR '.$db->qn('al.subject').' LIKE '.$search.')');
		}
		
		// order by
		$query->order($db->qn($this->getState('list.ordering', 'al.date')).' '.$db->escape($this->getState('list.direction', 'desc')));
		
		return $query;
	}
	
	protected function populateState($ordering = 'al.date', $direction = 'desc')
	{
		$this->setState('filter.search', 	$this->getUserStateFromRequest($this->context.'.filter.search', 'filter_search'));
		
		// List state information.
		parent::populateState($ordering, $direction);
	}

	public function delete(&$pks)
	{
		foreach ($pks as $i => $pk)
		{
			$db		= JFactory::getDbo();
			$query 	= $db->getQuery(true);

			$query->delete()
				->from($db->qn('#__rsticketspro_accounts_log'))
				->where($db->qn('id') . ' = ' . $db->q($pk));

			$db->setQuery($query)->execute();
		}

		// Clear the component's cache
		$this->cleanCache();

		return true;
	}
}