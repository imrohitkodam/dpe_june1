<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproModelKnowledgebase extends JModelLegacy
{
	protected $_total = 0;
	protected $_pagination = null;
	protected $_db = null;
	protected $is_staff;
	
	public function __construct()
	{
		parent::__construct();
		$mainframe = JFactory::getApplication();
		$this->_db = JFactory::getDbo();
		
		$this->params   = $mainframe->getParams('com_rsticketspro');
		$this->is_staff = RSTicketsProHelper::isStaff();
		
		// Get pagination request variables
		$limit		= $mainframe->getUserStateFromRequest('com_rsticketspro.categories.limit', 'limit', $mainframe->get('list_limit'));
		$limitstart	= $mainframe->input->get('limitstart', 0, '', 'int');

		// In case limit has been changed, adjust it
		$limitstart = ($limit != 0 ? (floor($limitstart / $limit) * $limit) : 0);

		$this->setState('com_rsticketspro.categories.limit', $limit);
		$this->setState('com_rsticketspro.categories.limitstart', $limitstart);
		
		$this->category_id = $mainframe->input->getInt('cid', 0);
	}
	
	public function getCategories($options = array('inherited' => true, 'id' => 0))
	{
		$category_id	= $options['inherited'] ? $this->category_id : $options['id'];
		$query			= $this->_db->getQuery(true);
		
		$query->select('*')
			->from($this->_db->qn('#__rsticketspro_kb_categories'))
			->where($this->_db->qn('published') . ' = ' . $this->_db->q('1'))
			->where($this->_db->qn('parent_id') . ' = ' . $this->_db->q($category_id))
			->order($this->_db->qn('ordering') . ' ' . $this->_db->escape('asc'));
		
		if (!$this->is_staff)
		{
			$query->where($this->_db->qn('private').' = '.$this->_db->q('0'));
		}

		return $this->_db->setQuery($query)->loadObjectList();
	}
	
	public function getCategory($options = array('inherited' => true, 'id' => 0))
	{
		$category_id	= $options['inherited'] ? $this->category_id : $options['id'];
		$row			= JTable::getInstance('Kbcategories', 'RsticketsproTable');
		$category		= JTable::getInstance('Kbcategories', 'RsticketsproTable');

		$row->load($category_id);
		
		if ($row->parent_id)
		{
			$parent_id = $row->parent_id;
			$category->load($parent_id);
			
			while ($parent_id > 0)
			{
				$parent_id = $category->parent_id;
				$category->load($parent_id);
				
				if ($category->private)
				{
					$row->private = 1;
				}

				if (!$category->published)
				{
					$row->published = 0;
				}
			}
		}
		
		if ((!$this->is_staff && $row->private) || !$row->published)
		{
			$mainframe = JFactory::getApplication();
			$mainframe->enqueueMessage(JText::_('RST_CANNOT_VIEW_CATEGORY'), 'warning');
			$mainframe->redirect('index.php?option=com_rsticketspro&view=knowledgebase');
		}
		
		return $row;
	}
	
	public function getContent($options = array('inherited' => true, 'id' => 0))
	{
		$category_id	= $options['inherited'] ? $this->category_id : $options['id'];
		$query			= $this->_db->getQuery(true);
		
		$query	->select('*')
				->from($this->_db->qn('#__rsticketspro_kb_content', 'a'))
				->where($this->_db->qn('published').' = '.$this->_db->q('1'))
				->where($this->_db->qn('category_id').' = '.$this->_db->q($category_id));
		
		if (!$this->is_staff)
		{
			$query->where($this->_db->qn('private').' = '.$this->_db->q('0'));
		}
		
		$filter_word = $this->getFilterWord();
		if (!empty($filter_word))
		{
			$filter_word = $this->_db->escape($filter_word);
			$filter_word = str_replace('%', '\%', $filter_word);
			$filter_word = str_replace(' ', '%', $filter_word);

			$query->where('('.$this->_db->qn('name').' LIKE '.$this->_db->q('%'.$filter_word.'%').' OR '.$this->_db->qn('text').' LIKE '.$this->_db->q('%'.$filter_word.'%').')');
		}
		
		$filter_tag = $this->getFilterTag();
		if (!empty($filter_tag))
		{
			$query	->join('left', $this->_db->qn('#__rsticketspro_kb_content_tags', 't') . ' ON (' . $this->_db->qn('a.id') . '=' . $this->_db->qn('t.article_id') . ')')
					->where($this->_db->qn('t.tag') . '=' . $this->_db->q($filter_tag));
		}
		
		$this->_db->setQuery($query);
		$this->_db->execute();
		$this->_total = $this->_db->getNumRows();
		
		$sortColumn = $this->getSortColumn();
		$sortColumn = $this->_db->qn($sortColumn);
		
		$sortOrder = $this->getSortOrder();
		$sortOrder = $this->_db->escape($sortOrder);
		
		$query->order($sortColumn . ' ' . $sortOrder);
		$this->_db->setQuery($query, $this->getState('com_rsticketspro.categories.limitstart'), $this->getState('com_rsticketspro.categories.limit'));
		
		return $this->_db->loadObjectList();
	}
	
	public function getResults()
	{
		$value	= $this->getResultsWord();
		
		if (!$value)
		{
			return array();
		}
		
		$escvalue = $this->_db->escape($value);
		$escvalue = str_replace('%','\%',$escvalue);
		$escvalue = str_replace(' ','%',$escvalue);
		$is_staff = RSTicketsProHelper::isStaff();
		$query	  = $this->_db->getQuery(true);
		
		$query->select($this->_db->qn('id'))
			->from($this->_db->qn('#__rsticketspro_kb_categories'))
			->where($this->_db->qn('published').' = '.$this->_db->q('1'));
		
		if (!$is_staff)
		{
			$query->where($this->_db->qn('private').' = '.$this->_db->q('0'));
		}
		
		$this->_db->setQuery($query);
		$cat_ids = $this->_db->loadColumn();
		$cat_ids[] = 0;

		$cat_ids = array_map('intval', $cat_ids);
		
		$query->clear()
			->select($this->_db->qn('c').'.*')
			->select($this->_db->qn('cat.name','category_name'))
			->from($this->_db->qn('#__rsticketspro_kb_content','c'))
			->join('LEFT',$this->_db->qn('#__rsticketspro_kb_categories','cat').' ON '.$this->_db->qn('c.category_id').' = '.$this->_db->qn('cat.id'))
			->where('('.$this->_db->qn('c.name').' LIKE '.$this->_db->q('%'.$escvalue.'%').' OR '.$this->_db->qn('c.text').' LIKE '.$this->_db->q('%'.$escvalue.'%').')')
			->where($this->_db->qn('c.published').' = '.$this->_db->q('1'))
			->order($this->_db->qn('cat.ordering').', '.$this->_db->qn('c.ordering'));
			
		if (!$is_staff)
		{
			$query->where($this->_db->qn('c.private').' = '.$this->_db->q('0'));
		}

		if ($cat_ids)
		{
			$query->where($this->_db->qn('c.category_id').' IN ('.implode(',',$cat_ids).')');
		}
		
		$this->_db->setQuery($query, $this->getState('com_rsticketspro.categories.limitstart'), $this->getState('com_rsticketspro.categories.limit'));
		$results = $this->_db->loadObjectList();
		
		$this->_total = 0;
		
		if ($results)
		{
			$category = JTable::getInstance('Kbcategories', 'RsticketsproTable');
			
			foreach ($results as $i => $result)
			{
				$parent_id = $result->category_id;
				$category->load($parent_id);
				
				while ($parent_id > 0)
				{
					$parent_id = $category->parent_id;
					$category->load($parent_id);
					
					if ($category->private)
					{
						$result->private = 1;
					}

					if (!$category->published)
					{
						$result->published = 0;
					}
				}
				
				if ((!$this->is_staff && $result->private) || !$result->published)
				{
					unset($results[$i]);
				}
			}
		}
		
		$this->_total = count($results);
		
		return $results;
	}
	
	public function getFilterWord()
	{
		return JFactory::getApplication()->getUserStateFromRequest('com_rsticketspro.kbcontent.filter', 'search', '');
	}
	
	public function getFilterTag()
	{
		return JFactory::getApplication()->getUserStateFromRequest('com_rsticketspro.kbcontent.filter'.md5(JUri::getInstance()), 'tag', '');
	}
	
	public function getSortColumn()
	{
		$allowed 	= array('ordering', 'hits', 'created', 'modified', 'name');
		$order 		= JFactory::getApplication()->getUserStateFromRequest('com_rsticketspro.kbcontent.filter_order', 'filter_order', $this->params->get('order_by', 'ordering'));
		
		if (!in_array($order, $allowed))
		{
			$order = 'ordering';
		}
		
		return $order;
	}
	
	public function getSortOrder()
	{
		$allowed = array('ASC', 'DESC');
		$dir 	 = JFactory::getApplication()->getUserStateFromRequest('com_rsticketspro.kbcontent.filter_order_Dir', 'filter_order_Dir', $this->params->get('order_dir', 'ASC'));
		
		if (!in_array(strtoupper($dir), $allowed))
		{
			$dir = 'ASC';
		}
		
		return $dir;
	}
	
	public function getContentTotal()
	{
		return $this->_total;
	}
	
	public function getContentPagination()
	{
		if (empty($this->_pagination))
		{
			$this->_pagination = new JPagination($this->getContentTotal(), $this->getState('com_rsticketspro.categories.limitstart'), $this->getState('com_rsticketspro.categories.limit'));
		}

		return $this->_pagination;
	}
	
	public function getResultsWord()
	{
		return JFactory::getApplication()->getUserStateFromRequest('com_rsticketspro.kbresults.search', 'search', '');
	}
	
	public function getResultsTotal()
	{
		return $this->_total;
	}
	
	public function getResultsPagination()
	{
		if (empty($this->_pagination))
		{
			$this->_pagination = new JPagination($this->getResultsTotal(), $this->getState('com_rsticketspro.categories.limitstart'), $this->getState('com_rsticketspro.categories.limit'));
		}
		return $this->_pagination;
	}
	
	public function getPath()
	{
		$return		= array();
		$parent_id	= $this->category_id;
		$row		= JTable::getInstance('Kbcategories', 'RsticketsproTable');
		
		while ($parent_id > 0)
		{
			$row->load($parent_id);
			$parent_id = $row->parent_id;
			
			$obj = new stdClass();
			$obj->name = $row->name;
			$obj->link = RSTicketsProHelper::route('index.php?option=com_rsticketspro&view=knowledgebase&cid='.$row->id.':'.JFilterOutput::stringURLSafe($row->name));
			
			$return[] = $obj;
		}
		
		krsort($return);
		return $return;
	}
}