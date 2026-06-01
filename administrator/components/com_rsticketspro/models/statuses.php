<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproModelStatuses extends JModelList
{
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'name', 'published', 'ordering', 'id', 'state'
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
		
		$query->select('*')
			->from('#__rsticketspro_statuses');

		// search
		if (strlen($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where($db->qn('id') . ' = ' . (int) substr($search, 3));
			}
			else
			{
				$query->where($db->qn('name') . ' LIKE ' . $db->q('%'.str_replace(' ', '%', $db->escape($search, true)).'%', false));
			}
		}

		// published/unpublished
		if ($state != '')
		{
			$query->where($db->qn('published') . '=' . $db->q($state));
		}

		// order by
		$query->order($db->qn($this->getState('list.ordering', 'ordering')).' '.$db->escape($this->getState('list.direction', 'asc')));
		
		return $query;
	}

	protected function populateState($ordering = 'ordering', $direction = 'asc')
	{
		$this->setState('filter.search', $this->getUserStateFromRequest($this->context.'.filter.search', 'filter_search'));
		$this->setState('filter.state', $this->getUserStateFromRequest($this->context.'.filter.state', 'filter_state'));

		// List state information.
		parent::populateState($ordering, $direction);
	}
}