<?php
/**
* @package RSSeo!
* @copyright (C) 2016 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Factory;

class rsseoModelErrorlinks extends ListModel
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
				'id', 'url', 'count', 'code'
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
		$query->from($db->qn('#__rsseo_error_links'));
		
		// Filter by search in title
		$search = $this->getState('filter.search');
		if (!empty($search)) {
			$search = $db->q('%'.$db->escape($search, true).'%');
			$query->where($db->qn('url').' LIKE '.$search);
		}
		
		// Add the list ordering clause
		$listOrdering = $this->getState('list.ordering', 'id');
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
		$db		= Factory::getDBO();
		$query	= $db->getQuery(true);
		$items	= parent::getItems();
		
		foreach ($items as $i => $item) {
			$query->clear()
				->select('COUNT('.$db->qn('id').')')
				->from($db->qn('#__rsseo_error_links_referer'))
				->where($db->qn('idl').' = '.$db->q($item->id));
			$db->setQuery($query);
			$items[$i]->referer = (int) $db->loadResult();
		}
		
		return $items;
	}
	
	public function delete($pks) {
		$db 	= Factory::getDBO();
		$query 	= $db->getQuery(true);
		
		$query->delete($db->qn('#__rsseo_error_links'))
			->where($db->qn('id').' IN ('.implode(',',$pks).')');
		$db->setQuery($query);
		$db->execute();
		
		$query->clear()->delete($db->qn('#__rsseo_error_links_referer'))
			->where($db->qn('idl').' IN ('.implode(',',$pks).')');
		$db->setQuery($query);
		$db->execute();
	}
	
	public function getReferrals() {
		$db 	= Factory::getDBO();
		$query 	= $db->getQuery(true);
		$id		= Factory::getApplication()->input->getInt('id',0);
		
		$query->select('*')
			->from($db->qn('#__rsseo_error_links_referer'))
			->where($db->qn('idl').' = '.$db->q($id));
		$db->setQuery($query);
		return $db->loadObjectList();
	}

	public function referrals($id) {
		$db		= Factory::getDbo();
		$query	= $db->getQuery(true);

		$query->clear()
			->delete($db->qn('#__rsseo_error_links_referer'))
			->where($db->qn('idl').' = '.$db->q($id));
		$db->setQuery($query);
		$db->execute();
	}
}