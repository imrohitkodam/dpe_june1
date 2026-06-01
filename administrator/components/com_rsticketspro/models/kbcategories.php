<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproModelKbcategories extends JModelList
{
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'name', 'private', 'published', 'ordering', 'id', 'state'
			);
		}

		parent::__construct($config);
	}
	
	protected function getListQuery()
	{
		$db 	= JFactory::getDbo();
		$query 	= $db->getQuery(true);
		
		// get filtering states
		$search 	= $this->getState('filter.search', '');
		$state 		= $this->getState('filter.state');
		$private 	= $this->getState('filter.private');

		$query->select('*')
			  ->from($db->qn('#__rsticketspro_kb_categories'));
		// search
		if (strlen($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where($db->qn('id') . ' = ' . (int) substr($search, 3));
			}
			else
			{
				$search = $db->q('%'.str_replace(' ', '%', $db->escape($search, true)).'%', false);

				$query->where('(' . $db->qn('name') . ' LIKE ' . $search . ') OR (' . $db->qn('description') . ' LIKE ' . $search . ')');
			}
		}

		// published/unpublished
		if ($state != '')
		{
			$query->where($db->qn('published') . '=' . $db->q($state));
		}

		if ($private != '')
		{
			$query->where($db->qn('private') . '=' . $db->q($private));
		}

		// order by
		$query->order($db->qn($this->getState('list.ordering', 'ordering')).' '.$db->escape($this->getState('list.direction', 'asc')));
		
		return $query;
	}
	
	public function getItems()
	{
		$listOrdering 	= $this->getState('list.ordering', 'ordering');
		$search 		= $this->getState('filter.search');
		if ($listOrdering == 'ordering' || $search != '')
		{
			// Load the list items.
			$query = $this->_getListQuery();
			$items = $this->_getList($query, 0, 0);
			$children = array();
			
			// first pass - collect children
			if ($items)
			{
				foreach ($items as $item)
				{
					$parent	= $item->parent_id;
					$item->parent = $parent;
					$item->title = '';
					$list = isset($children[$parent]) ? $children[$parent] : array();
					array_push($list, $item);
					$children[$parent] = $list;
				}
			}

			// second pass - get an indent list of the items
			$list = JHtml::_('menu.treerecurse', 0, '', array(), $children, 9999, 0, 0);

			if ($this->getState('list.limit'))
			{
				$list = array_slice($list, $this->getStart(), $this->getState('list.limit'));
			}
			
			return $list;
		}
		
		return parent::getItems();
	}
	
	protected function populateState($ordering = 'ordering', $direction = 'asc')
	{
		$this->setState('filter.search', $this->getUserStateFromRequest($this->context.'.filter.search', 'filter_search'));
		$this->setState('filter.state', $this->getUserStateFromRequest($this->context.'.filter.state', 'filter_state'));
		$this->setState('filter.private', $this->getUserStateFromRequest($this->context.'.filter.private', 'filter_private'));

		// List state information.
		parent::populateState($ordering, $direction);
	}
}