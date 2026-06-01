<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Tc
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2016-2017 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

jimport('joomla.application.component.modellist');

/**
 * Methods supporting a list of Tc records.
 *
 * @since  1.6
 */
class TcModelContents extends JModelList
{
/**
	* Constructor.
	*
	* @param   array  $config  An optional associative array of configuration settings.
	*
	* @see        JController
	* @since      1.6
	*/
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'tc_id', 'a.tc_id',
				'ordering', 'a.ordering',
				'state', 'a.state',
				'created_by', 'a.created_by',
				'modified_by', 'a.modified_by',
				'title', 'a.title',
				'version', 'a.version',
				'client', 'a.client',
				'start_date', 'a.start_date',
				'content', 'a.content'
			);
		}

		parent::__construct($config);
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   string  $ordering   Elements order
	 * @param   string  $direction  Order direction
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	protected function populateState($ordering = 'a.tc_id', $direction = 'DESC')
	{
		// Initialise variables.
		$app = JFactory::getApplication('administrator');

		if ($filters = $app->getUserStateFromRequest($this->context . '.filter', 'filter', array(), 'array'))
		{
			foreach ($filters as $name => $value)
			{
				$this->setState('filter.' . $name, $value);
			}
		}

		parent::populateState($ordering, $direction);
	}

	/**
	 * Method to get a store id based on model configuration state.
	 *
	 * This is necessary because the model is used by the component and
	 * different modules that might need different sets of data or different
	 * ordering requirements.
	 *
	 * @param   string  $id  A prefix for the store id.
	 *
	 * @return   string A store id.
	 *
	 * @since    1.6
	 */
	protected function getStoreId($id = '')
	{
		// Compile the store id.
		$id .= ':' . $this->getState('filter.search');
		$id .= ':' . $this->getState('filter.state');

		return parent::getStoreId($id);
	}

	/**
	 * Method to get an array of data items
	 *
	 * @return  mixed An array of data on success, false on failure.
	 */
	public function getItems()
	{
		$items = parent::getItems();

		return $items;
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return   JDatabaseQuery
	 *
	 * @since    1.6
	 */
	protected function getListQuery()
	{
		// Create a new query object.
		$db    = $this->getDbo();
		$query = $db->getQuery(true);

		// Select the required fields from the table.
		$query->select(
			$this->getState(
				'list.select', 'DISTINCT a.*'
			)
		);
		$query->from($db->quoteName('#__tc_content', 'a'));

		// Join over the users for the checked out user
		$query->select($db->quoteName('uc.name', 'editor'));
		$query->join("LEFT", $db->quoteName('#__users', 'uc') . 'ON (' . $db->quoteName('uc.id') . ' = ' .
		$db->quoteName('a.checked_out') . ')');

		// Join over the user field 'created_by'
		$query->select('`created_by`.name AS `created_by`');
		$query->join("LEFT", $db->quoteName('#__users', 'created_by') . 'ON (' . $db->quoteName('created_by.id') . ' = ' .
		$db->quoteName('a.created_by') . ')');

		// Join over the user field 'modified_by'
		$query->select('`modified_by`.name AS `modified_by`');
		$query->join("LEFT", $db->quoteName('#__users', 'modified_by') . 'ON (' . $db->quoteName('modified_by.id') . ' = ' .
		$db->quoteName('a.modified_by') . ')');

		// Filter by published state
		$published = $this->getState('filter.state');
		$client = $this->getState('filter.client');

		if (is_numeric($published))
		{
			$query->where($db->quoteName('a.state') . ' = ' . $db->quote((int) $published));
		}
		elseif ($published === '')
		{
			$query->where($db->quoteName('a.state') . 'IN (0, 1)');
		}

		if (!empty($client))
		{
			$query->where($db->quoteName('a.client') . ' = ' . $db->quote($client));
		}

		// Filter by search in title
		$search = $this->getState('filter.search');

		if (!empty($search))
		{
			if (stripos($search, 'tc_id:') === 0)
			{
				$query->where('a.tc_id = ' . (int) substr($search, 6));
			}
			else
			{
				$search = $db->Quote('%' . $db->escape($search, true) . '%');
				$query->where('( a.title LIKE ' . $search . '  OR  a.version LIKE
				 ' . $search . '  OR  a.client LIKE ' . $search . ' )');
			}
		}

		// Add the list ordering clause.
		$orderCol  = $this->state->get('list.ordering', 'a.tc_id');
		$orderDirn = $this->state->get('list.direction', 'DESC');

		if ($orderCol && $orderDirn)
		{
			$query->order($db->escape($orderCol . ' ' . $orderDirn));
		}

		// Get ordering
		$fullorderCol = $this->state->get('list.fullordering', 'a.tc_id DESC');

		// Apply ordering
		if (!empty($fullorderCol))
		{
			$query->order($db->escape($fullorderCol));
		}

		return $query;
	}

	/**
	 * Gets an array of objects from the results of database query.
	 *
	 * @param   string   $query       The query.
	 * @param   integer  $limitstart  Offset.
	 * @param   integer  $limit       The number of records.
	 *
	 * @return  object[]  An array of results.
	 *
	 * @since   12.2
	 * @throws  RuntimeException
	 */
	protected function _getList($query, $limitstart = 0, $limit = 0)
	{
		$this->getDbo()->setQuery($query, $limitstart, $limit);

		$show = $this->getState('filter.show');

		if ($show == 'latest')
		{
			$latestTCS = $this->getDbo()->loadObjectList('client');

			$getLatest = array();

			foreach ($latestTCS as $tc)
			{
				array_push($getLatest, $tc);
			}

			return $getLatest;
		}
		else
		{
			return $this->getDbo()->loadObjectList();
		}
	}
}
