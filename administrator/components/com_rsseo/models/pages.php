<?php
/**
* @package RSSeo!
* @copyright (C) 2016 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Factory;

class rsseoModelPages extends ListModel
{	
	/**
	 * Constructor.
	 *
	 * @param	array	An optional associative array of configuration settings.
	 * @see		JController
	 * @since	1.6
	 */
	public function __construct($config = array()) {
		if (empty($config['filter_fields'])) {
			$config['filter_fields'] = array(
				'id', 'url', 'title', 'published',
				'level', 'grade', 'crawled', 'status',
				'date', 'hits', 'modified', 'insitemap'
			);
		}

		parent::__construct($config);
	}
	
	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return	JDatabaseQuery
	 * @since	1.6
	 */
	protected function getListQuery() {
		$db 	= Factory::getDBO();
		$query 	= $db->getQuery(true);
		
		// Select fields
		$query->select('*');
		
		// Select from table
		$query->from($db->qn('#__rsseo_pages'));
		
		// Filter by level.
		if ($level = $this->getState('filter.level')) {
			$query->where($db->qn('level').' = ' . (int) $level);
		}
		
		// Filter by HTTP status
		if ($status = $this->getState('filter.status')) {
			$query->where($db->qn('status').' = ' . (int) $status);
		}
		
		// Filter by sitemap.
		$insitemap = $this->getState('filter.insitemap');
		if (is_numeric($insitemap)) {
			$query->where($db->qn('insitemap').' = ' . (int) $insitemap);
		}
		
		// Filter by modified page.
		$modified = $this->getState('filter.modified');
		if (is_numeric($modified)) {
			$query->where($db->qn('modified').' = ' . (int) $modified);
		}
		
		// Filter by hash
		if ($hash = Factory::getApplication()->input->getString('hash','')) {
			list($column, $md5) = explode('|', $hash, 2);
			$query->where('MD5('.$db->qn($column).') = ' . $db->q($md5));
		}
		
		// Filter by published state
		$published = $this->getState('filter.published');
		if (is_numeric($published)) {
			$query->where($db->qn('published').' = ' . (int) $published);
		}
		elseif ($published === '' || $published === null) {
			$query->where($db->qn('published').' IN (0,1)');
		}
		
		// Filter by search in title
		$search = $this->getState('filter.search');
		if (!empty($search)) {
			
			$url = str_replace(array('&amp;','&apos;','&quot;','&gt;','&lt;'),array("&","'",'"',">","<"),$search);
			$url = str_replace(array("&","'",'"',">","<"),array('&amp;','&apos;','&quot;','&gt;','&lt;'),$url);
			
			$search = $db->q('%'.$db->escape($search, true).'%');
			$query->where('('.$db->qn('url').' LIKE '.$search.' OR '.$db->qn('url').' LIKE '.$db->q('%'.$db->escape($url, true).'%').' OR '.$db->qn('sef').' LIKE '.$db->q('%'.$db->escape($url, true).'%').' OR '.$db->qn('title').' LIKE '.$search.')');
		}
		
		// Add the list ordering clause
		$listOrdering = $this->getState('list.ordering', 'level');
		$listDirn = $db->escape($this->getState('list.direction', 'asc'));
		$query->order($db->qn($listOrdering).' '.$listDirn);

		return $query;
	}
	
	/**
	 * Method to get the items list.
	 *
	 * @return	mixed	An array of data items on success, false on failure.
	 * @since	1.6.1
	 */
	public function getItems() {
		$items	= parent::getItems();
		
		foreach ($items as $i => $item) {
			switch($item->grade) {
				case ($item->grade >= 0 && $item->grade < 33): 
					$items[$i]->color = 'red'; 
				break;
				
				case ($item->grade >= 33 && $item->grade < 66):
					$items[$i]->color = 'orange'; 
				break;
				
				case -1:
					$items[$i]->color = '';
				break;
				
				default: 
					$items[$i]->color = 'green'; 
				break;
			}
		}
		
		return $items;
	}
	
	public function getBatchFields() {
		Form::addFormPath(JPATH_ADMINISTRATOR.'/components/com_rsseo/models/forms');
		
		return Form::getInstance('batch', 'batch', array('control' => 'batch'));
	}
}