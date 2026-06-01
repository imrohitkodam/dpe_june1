<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproModelKbarticles extends JModelList
{
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'a.name', 'c.name', 'a.hits', 'a.private', 'a.published', 'a.ordering', 'a.id', 'state', 'category_id', 'private'
			);
		}

		parent::__construct($config);
	}
	
	protected function getListQuery()
	{
		$db 	= JFactory::getDbo();
		$query 	= $db->getQuery(true);
		
		// get filtering states
		$search 	 = $this->getState('filter.search', '');
		$state 		 = $this->getState('filter.state');
		$private 	 = $this->getState('filter.private');
		$category_id = $this->getState('filter.category_id');
		
		$query->select($db->qn('a.id'))
			  ->select($db->qn('a.name'))
			  ->select($db->qn('a.category_id'))
			  ->select($db->qn('a.hits'))
			  ->select($db->qn('a.private'))
			  ->select($db->qn('a.published'))
			  ->select($db->qn('a.ordering'))
			  ->select($db->qn('c.name', 'category_name'))
			  ->from($db->qn('#__rsticketspro_kb_content', 'a'));
		
		// join categories
		$query->join('left', $db->qn('#__rsticketspro_kb_categories', 'c') . ' ON (' . $db->qn('a.category_id') . '=' . $db->qn('c.id') . ')');
		
		// search
		if (strlen($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where($db->qn('a.id') . ' = ' . (int) substr($search, 3));
			}
			else
			{
				$search = $db->q('%'.str_replace(' ', '%', $db->escape($search, true)).'%', false);

				$query->where('(' . $db->qn('a.name') . ' LIKE ' . $search . ') OR (' . $db->qn('a.text') . ' LIKE ' . $search . ')');
			}
		}

		// searching for a category?
		if ($category_id !== '')
		{
			// let's search for all children
			if ($category_id == 0)
			{
				$categories = array($category_id);
			}
			else
			{
				$categories = $this->getAllChildren($category_id);
			}
			
			if (!is_null($category_id))
			{
				$query->where($db->qn('a.category_id').' IN ('.implode(',', $categories).')');
			}
		}
		// published/unpublished
		if ($state != '')
		{
			$query->where($db->qn('a.published') . '=' . $db->q($state));
		}

		if ($private != '')
		{
			$query->where($db->qn('a.private') . '=' . $db->q($private));
		}

		// order by
		$query->order($db->qn($this->getState('list.ordering', 'a.ordering')).' '.$db->escape($this->getState('list.direction', 'asc')));
		$query->order($db->qn('a.category_id'));
		
		return $query;
	}
	
	protected function populateState($ordering = 'a.ordering', $direction = 'asc')
	{
		$this->setState('filter.search', $this->getUserStateFromRequest($this->context.'.filter.search', 'filter_search'));
		$this->setState('filter.state', $this->getUserStateFromRequest($this->context.'.filter.state', 'filter_state'));
		$this->setState('filter.private', $this->getUserStateFromRequest($this->context.'.filter.private', 'filter_private'));
		$this->setState('filter.category_id', $this->getUserStateFromRequest($this->context.'.filter.category_id', 'filter_category_id'));
		
		// List state information.
		parent::populateState($ordering, $direction);
	}
	
	protected function getAllChildren($parent_id)
	{
		$db 	= $this->getDbo();
		$query 	= $db->getQuery(true);
		$children = array($parent_id);
		
		$query->select($db->qn('id'))
			  ->from('#__rsticketspro_kb_categories')
			  ->where($db->qn('parent_id') . '=' . $db->q($parent_id));
		$db->setQuery($query);
		if ($ids = $db->loadColumn())
		{
			foreach ($ids as $id)
			{
				$children = array_merge($children, $this->getAllChildren($id));
			}
		}
		
		return $children;
	}
}