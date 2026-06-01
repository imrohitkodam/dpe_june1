<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.model');

class RsticketsproModelKnowledgebasebox extends JModelLegacy
{
	var $_data = null;
	var $_total = 0;
	var $_pagination = null;
	var $_db = null;
	
	function __construct()
	{
		parent::__construct();
		$this->_db = JFactory::getDbo();
		
		$mainframe = JFactory::getApplication();
		$option    = 'com_rsticketspro';
		
		// Get pagination request variables
		$limit = $mainframe->getUserStateFromRequest($option.'.kbbox.limit', 'limit', $mainframe->get('list_limit'), 'int');
		$limitstart = $mainframe->getUserStateFromRequest($option.'.kbbox.limitstart', 'limitstart', 0, 'int');

		// In case limit has been changed, adjust it
		$limitstart = ($limit != 0 ? (floor($limitstart / $limit) * $limit) : 0);

		$this->setState($option.'.kbbox.limit', $limit);
		$this->setState($option.'.kbbox.limitstart', $limitstart);
		
		$this->category_id = $mainframe->input->getInt('category_id', 0);
	}
	
	function getKBCategories()
	{
		$mainframe      = JFactory::getApplication();
		$category_id    = $this->category_id;
		
		$query = "SELECT * FROM #__rsticketspro_kb_categories WHERE `published`='1' AND `parent_id`='".$category_id."'";
		
		$sortColumn = $mainframe->input->getString('filter_order', 'ordering');
		$sortColumn = $this->_db->escape($sortColumn);
		
		$sortOrder = $mainframe->input->getString('filter_order_Dir', 'ASC');
		$sortOrder = $this->_db->escape($sortOrder);
		
		$query .= " ORDER BY `".$sortColumn."` ".$sortOrder;

		return $this->_getList($query);
	}
	
	function getKBContent()
	{
		$option = 'com_rsticketspro';
		
		$category_id = $this->category_id;
		
		$query = "SELECT * FROM #__rsticketspro_kb_content WHERE `published`='1' AND `category_id`='".$category_id."'";
		
		$filter_word = JFactory::getApplication()->input->getCmd('search', '');
		$category_state = JFactory::getApplication()->input->getInt('category_state', -1);
		if (!empty($filter_word))
		{
			$categories = array();
			$categories[] = $category_state;
			// need to search subcategories as well
			if ($category_state > 0)
			{
				$this->_db->setQuery("SELECT `id` FROM #__rsticketspro_kb_categories WHERE parent_id = '".$category_state."' AND published=1");
				while ($category = $this->_db->loadResult())
				{
					$categories[] = $category;
					$this->_db->setQuery("SELECT `id` FROM #__rsticketspro_kb_categories WHERE parent_id = '".$category."' AND published=1");
				}
			}
			$query = "SELECT * FROM #__rsticketspro_kb_content WHERE `published`='1' AND (`name` LIKE '%".$filter_word."%' OR `text` LIKE '%".$filter_word."%')".($category_state != -1 ? " AND `category_id` IN (".implode(',', $categories).")" : "");
		}
		
		$this->_total = $this->_getListCount($query);
		
		$sortColumn = JFactory::getApplication()->input->getString('filter_order', 'ordering');
		$sortColumn = $this->_db->escape($sortColumn);
		
		$sortOrder = JFactory::getApplication()->input->getString('filter_order_Dir', 'ASC');
		$sortOrder = $this->_db->escape($sortOrder);
		
		$query .= " ORDER BY `".$sortColumn."` ".$sortOrder;
		
		return $this->_getList($query, $this->getState($option.'.kbbox.limitstart'), $this->getState($option.'.kbbox.limit'));
	}
	
	function getKBContentTotal()
	{
		return $this->_total;
	}
	
	function getKBContentPagination()
	{
		if (empty($this->_pagination))
		{
			$option = 'com_rsticketspro';
			jimport('joomla.html.pagination');
			$this->_pagination = new JPagination($this->getKBContentTotal(), $this->getState($option.'.kbcategories.limitstart'), $this->getState($option.'.kbcategories.limit'));
		}
		
		return $this->_pagination;
	}
	
	function getCurrentKBCategory()
	{
		$category_id = $this->category_id;
		
		if ($category_id == 0)
			return JText::_('RST_KB_NO_PARENT');
		
		$row = JTable::getInstance('RSTicketsPro_KB_Categories','Table');
		$row->load($category_id);
		
		return $row->name;
	}
	
	function getPreviousKBCategoryId()
	{
		$category_id = $this->category_id;
		
		if ($category_id == 0)
			return 0;
		
		$row = JTable::getInstance('RSTicketsPro_KB_Categories','Table');
		$row->load($category_id);
		
		return $row->parent_id;
	}
	
	function getKBArticle()
	{
		$content_id = JFactory::getApplication()->input->getInt('content_id', 0);
		
		$row = JTable::getInstance('RSTicketsPro_KB_Content','Table');
		$row->load($content_id);
		
		return $row;
	}
	
	function getCurrentKBCategoryId()
	{
		return $this->category_id;
	}
}
?>