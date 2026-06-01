<?php
/**
 * @package     Multiagency
 * @subpackage  com_multiagency
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;
use Joomla\Data\DataObject;

jimport('joomla.application.component.modellist');
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

/**
 * Methods supporting a list of Multiagency records.
 *
 * @since  __DEPLOY__VERSION__
 */
class MultiagencyModelMultiagences extends ListModel
{
	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see        JController
	 * @since      __DEPLOY__VERSION__
	 */
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'id', 'a.id',
				'ordering', 'a.ordering',
				'state', 'a.state',
				'created_by', 'a.created_by',
				'modified_by', 'a.modified_by',
				'title', 'a.title',
				'name', 'b.name',
				'phone_no', 'a.phone_no',
				'country_id', 'a.country_id',
				'state_id', 'a.state_id',
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
	 *
	 * @since    __DEPLOY__VERSION__
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		$app  = Factory::getApplication();
		$list = $app->getUserState($this->context . '.list');

		$list['limit']     = (int) Factory::getConfig()->get('list_limit', 5);
		$list['start']     = $app->input->getInt('start', 0);

		$app->setUserState($this->context . '.list', $list);
		$app->input->set('list', null);

		// List state information.
		parent::populateState($ordering, $direction);

		$orderCol = $app->input->get('filter_order', 'a.ordering');

		if (!in_array($orderCol, $this->filter_fields))
		{
			$orderCol = 'a.ordering';
		}

		$this->setState('list.ordering', $orderCol);

		$listOrder = $app->input->get('filter_order_Dir', 'ASC');

		if (!in_array(strtoupper($listOrder), array('ASC', 'DESC', '')))
		{
			$listOrder = 'ASC';
		}

		$this->setState('list.direction', $listOrder);

		// Load the filter state.
		$search = $app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		$start = $app->getUserStateFromRequest($this->context . '.limitstart', 'limitstart', 0, 'int');
		$limit = $app->getUserStateFromRequest($this->context . '.limit', 'limit', 0, 'int');

		if ($limit == 0)
		{
			$limit = $app->get('list_limit', 0);
		}

		$this->setState('list.limit', $limit);
		$this->setState('list.start', $start);
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return   DataObjectbaseQuery
	 *
	 * @since    __DEPLOY__VERSION__
	 */
	protected function getListQuery()
	{
		// Create a new query object.
		$db    = $this->getDbo();
		$query = $db->getQuery(true);

		// Select the required fields from the table.
		$query
			->select(
				$this->getState(
					'list.select', 'DISTINCT a.*,
					(SELECT count(DISTINCT(su.user_id)) FROM #__tjsu_users AS su, #__users AS u WHERE u.id = su.user_id
					AND u.block = 0 AND su.client_id = a.id AND client="com_multiagency" ) as manager_count'
				)
			);
		$query->from($db->qn('#__tjmultiagency_multiagency', 'a'));

		$query->where($db->qn('a.state') . '=1');

		// Filter by search in title
		$search = $this->getState('filter.search');

		if (!empty($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where($db->qn('a.id') . '=' . (int) substr($search, 3));
			}
			else
			{
				$search = $db->Quote('%' . $db->escape($search, true) . '%');
				$query->where('(' . $db->qn('a.title') . ' LIKE ' . $search . ')');
			}
		}

		// Add the list ordering clause.
		$orderCol  = $this->state->get('list.ordering');
		$orderDirn = $this->state->get('list.direction');

		if ($orderCol && $orderDirn)
		{
			$query->order($db->escape($orderCol . ' ' . $orderDirn));
		}

		return $query;
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
	 * Overrides the default function to check Date fields format, identified by
	 * "_dateformat" suffix, and erases the field if it's not correct.
	 *
	 * @return void
	 */
	protected function loadFormData()
	{
		$app              = Factory::getApplication();
		$filters          = $app->getUserState($this->context . '.filter', array());
		$error_dateformat = false;

		foreach ($filters as $key => $value)
		{
			if (strpos($key, '_dateformat') && !empty($value) && $this->isValidDate($value) == null)
			{
				$filters[$key]    = '';
				$error_dateformat = true;
			}
		}

		if ($error_dateformat)
		{
			$app->enqueueMessage(Text::_("COM_MULTIAGENCY_SEARCH_FILTER_DATE_FORMAT"), "warning");
			$app->setUserState($this->context . '.filter', $filters);
		}

		return parent::loadFormData();
	}

	/**
	 * Checks if a given date is valid and in a specified format (YYYY-MM-DD)
	 *
	 * @param   string  $date  Date to be checked
	 *
	 * @return bool
	 */
	private function isValidDate($date)
	{
		$date = str_replace('/', '-', $date);

		return (date_create($date)) ? Factory::getDate($date)->format("Y-m-d") : null;
	}

	/**
	 * Method to get an array of data items
	 *
	 * @param   string  $pk  primary key
	 *
	 * @return  mixed An array of data on success, false on failure.
	 */
	public function getuserId($pk)
	{
		$db = Factory::getDBO();
		$query = $db->getQuery(true);
		$query->SELECT('manager_id');
		$query->FROM('#__tjmultiagency_multiagency');
		$query->WHERE('id=' . (int) $pk);

		$db->setQuery($query);
		$managerId = $db->loadObject();
		$userId = $managerId->manager_id;

		return $userId;
	}

	/**
	 * Method to get an array of data items
	 *
	 * @return  mixed An array of data on success, false on failure.
	 */
	public function getAllAgencies()
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select($db->quoteName(array('id', 'title')));
		$query->from($db->quoteName('#__tjmultiagency_multiagency'));
		$query->where($db->quoteName('state') . ' =  1');
		$query->order($db->quoteName('title') . ' ASC');
		$db->setQuery($query);
		$agencies = $db->loadObjectList();

		return $agencies;
	}

	/**
	 * Get Cluster Client Ids using userid
	 *
	 * @param   int  $userId  User id for which you want to get the clients
	 *
	 * @return   array
	 *
	 * @since    1.0.0
	 */
	public function getClusterClientIds($userId)
	{
		if ($userId)
		{
			// Initialize variables.
			$db    = $this->getDbo();
			$query = $db->getQuery(true);

			$query->select('cl.client_id');
			$query->from($db->quoteName('#__tj_cluster_nodes', 'cu'));
			$query->join('INNER', $db->quoteName('#__users', 'users') . ' ON (' . $db->quoteName('cu.user_id') . ' = ' . $db->quoteName('users.id') . ')');
			$query->join('INNER', $db->quoteName('#__tj_clusters', 'cl') . ' ON (' . $db->quoteName('cl.id') . ' = ' . $db->quoteName('cu.cluster_id') . ')');
			$query->where($db->quoteName('cu.user_id') . ' = ' . (int) $userId);

			$db->setQuery($query);

			$ids = $db->loadObjectList();

			foreach ($ids as $client)
			{
				$clientIds[] = $client->client_id;
			}

			return $clientIds;
		}
	}
}
