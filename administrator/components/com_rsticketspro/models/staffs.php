<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproModelStaffs extends JModelList
{
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'username', 'name', 'email', 'group_name', 'priority_name', 'u.id'
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
		
		$query->select('s.*')
			->select($db->qn('g.name', 'group_name'))
			->select($db->qn('u.username'))
			->select($db->qn('u.name'))
			->select($db->qn('u.email'))
			->select($db->qn('p.name', 'priority_name'))
			->from($db->qn('#__rsticketspro_staff', 's'))
			->join('left', $db->qn('#__rsticketspro_groups', 'g') . ' ON (' . $db->qn('s.group_id') . '=' . $db->qn('g.id') . ')')
			->join('left', $db->qn('#__users', 'u') . ' ON (' . $db->qn('s.user_id') . '=' . $db->qn('u.id') . ')')
			->join('left', $db->qn('#__rsticketspro_priorities', 'p') . ' ON (' . $db->qn('s.priority_id') . '=' . $db->qn('p.id') . ')');

		// search
		if (strlen($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where($db->qn('s.id') . ' = ' . (int) substr($search, 3));
			}
			else
			{
				$search = $db->q('%'.str_replace(' ', '%', $db->escape($search, true)).'%', false);

				$query->where($db->qn('g.name') . ' LIKE ' . $search, 'OR');
				$query->where($db->qn('u.username') . ' LIKE ' . $search, 'OR');
				$query->where($db->qn('u.name') . ' LIKE ' . $search, 'OR');
				$query->where($db->qn('u.email') . ' LIKE ' . $search, 'OR');
			}
		}

		// order by
		$query->order($db->qn($this->getState('list.ordering', 'group_name')).' '.$db->escape($this->getState('list.direction', 'asc')));
		
		return $query;
	}
	
	protected function populateState($ordering = 'group_name', $direction = 'asc')
	{
		$this->setState('filter.search', 	$this->getUserStateFromRequest($this->context.'.filter.search', 'filter_search'));
		
		// List state information.
		parent::populateState($ordering, $direction);
	}
}