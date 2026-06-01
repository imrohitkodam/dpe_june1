<?php
/**
* @package RSSeo!
* @copyright (C) 2016 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Factory;

class rsseoModelKeywords extends ListModel
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
				'id', 'keyword'
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
		$query->from($db->qn('#__rsseo_keywords'));
		
		// Filter by search in title
		$search = $this->getState('filter.search');
		if (!empty($search)) {
			$search = $db->q('%'.$db->escape($search, true).'%');
			$query->where($db->qn('keyword').' LIKE '.$search.' ');
		}
		
		// Add the list ordering clause
		$listOrdering = $this->getState('list.ordering', 'id');
		$listDirn = $db->escape($this->getState('list.direction', 'desc'));
		$query->order($db->qn($listOrdering).' '.$listDirn);
		
		return $query;
	}
}