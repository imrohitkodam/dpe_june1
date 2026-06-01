<?php
/**
 * @package    Com_Timelog
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');
use Joomla\Data\DataObject;
use Joomla\CMS\Filter\InputFilter;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Date\Date;

/**
 * Methods supporting a list of Timelog Activities records.
 *
 * @since  __DEPLOY_VERSION__
 */
class TimelogModelActivities extends ListModel
{
/**
	* Constructor.
	*
	* @param   array  $config  An optional associative array of configuration settings.
	*
	* @since      __DEPLOY_VERSION__
	*/
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'id', 'a.id',
				'activity_type_id', 'a.activity_type_id',
				'client', 'a.client',
				'client_id', 'a.client_id',
				'activity_note', 'a.activity_note',
				'created_date', 'a.created_date',
				'spent_time', 'a.spent_time',
				'state', 'a.state',
				'attachment', 'a.attachment',
				'created_by', 'a.created_by',
				'modified_by', 'a.modified_by','todo.title',
				'date_from', 'date_to'
			);
		}

		parent::__construct($config);
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   string  $ordering   Ordering
	 * @param   string  $direction  Ordering dir
	 *
	 * @since    __DEPLOY_VERSION__
	 *
	 * @return  void
	 */
	protected function populateState($ordering = 'a.id', $direction = 'desc')
	{
		$app = Factory::getApplication();

		$slaActivityId = $app->getUserStateFromRequest($this->context . '.filter.client_id', 'sla_activity');
		$this->setState('filter.client_id', $slaActivityId);

		// Code Added For DPE
		$licenseId = $app->input->getInt('licence_id', 0);
		$this->setState('filter.license_id', $licenseId);

		$clusterId = $app->input->getInt('cluster_id', 0);
		$this->setState('filter.cluster_id', $clusterId);

		// If archive param is true then set licence state to archive
		if ($app->input->get('archive'))
		{
			$this->setState('filter.licence_state', 2);
		}

		// Set date filter to blank if not set in url

		$dateFilters = $app->input->get('filter', array(), 'array');

		if (empty($dateFilters['date_from']))
		{
			$app->setUserState($this->context . '.filter.date_from', '');
		}

		if (empty($dateFilters['date_to']))
		{
			$app->setUserState($this->context . '.filter.date_to', '');
		}

		parent::populateState($ordering, $direction);
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return   \DataObjectbaseQuery
	 *
	 * @since    __DEPLOY_VERSION__
	 */
	protected function getListQuery()
	{
		// Create a new query object.
		$db = $this->getDbo();
		$query = $db->getQuery(true);

		// Select the required fields from the table.
		$query->select(
			$this->getState(
				'list.select', 'DISTINCT a.*'
			)
		);

		// Below query shows "20hr 50min" format for "20:50:00" timelog value
		$query->select('TIME_FORMAT(timelog, "%Hhr %imin") AS spent_time');
		$query->from($db->qn('#__timelog_activities', 'a'));

		// Join over the user field 'created_by'
		$query->select('`created_by`.name AS `created_by`');
		$query->join('LEFT', $db->qn('#__users', 'created_by')
		. ' ON (' . $db->qn('created_by.id') . ' = ' . $db->qn('a.created_by') . ')');

		// Join over the user field 'modified_by'
		$query->select('`modified_by`.name AS `modified_by`');
		$query->join('LEFT', $db->qn('#__users', 'modified_by')
		. ' ON (' . $db->qn('modified_by.id') . ' = ' . $db->qn('a.modified_by') . ')');

		// Join over the activity_type field 'activity_type_id'
		$query->select('activity_type.`title`');
		$query->join('LEFT', $db->qn('#__timelog_activity_type', 'activity_type')
		. ' ON (' . $db->qn('activity_type.id') . ' = ' . $db->qn('a.activity_type_id') . ')');

		// Join over the client_id field 'client_id'
		$query->select('todo.title AS activity_title, todo.sender_msg AS activity_description');

		$query->join('INNER', $db->quoteName('#__tj_sla_activities', 'sa')
		. ' ON (' . $db->quoteName('sa.id') . ' = ' . $db->quoteName('a.client_id') . ')');

		$query->join('INNER', $db->quoteName('#__jlike_todos', 'todo')
		. ' ON (' . $db->quoteName('todo.id') . ' = ' . $db->quoteName('sa.todo_id') . ')');

		// Join over the sla_service_id field 'sla_service_id'
		$query->join('INNER', $db->quoteName('#__tjmultiagency_licences', 'ml')
		. ' ON (' . $db->quoteName('ml.id') . ' = ' . $db->quoteName('sa.license_id') . ')');

		// Filter by published state
		$published = $this->getState('filter.state');

		if (is_numeric($published))
		{
			$query->where('a.state = ' . (int) $published);
		}
		elseif ($published === '')
		{
			$query->where('a.state = 1');
		}

		// Filter by client_id
		$clientId = $this->getState('filter.client_id');

		if (!empty($clientId))
		{
			$query->where('a.client_id = ' . (int) $clientId);
		}

		// Filter by search in title
		$search = $this->getState('filter.search');

		if (!empty($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where('a.id = ' . (int) substr($search, 3));
			}
			else
			{
				$search = $db->Quote('%' . $db->escape($search, true) . '%');
				$query->where(
				'( activity_type.title LIKE ' . $search .
				' OR  a.client LIKE ' . $search .
				' OR  todo.title LIKE ' . $search .
				' OR  a.activity_note LIKE ' . $search .
				' OR  created_by.name LIKE ' . $search .
				' OR  modified_by.name LIKE ' . $search .
				')');
			}
		}

		// Filtering by activity type
		$activityType = $this->getState('filter.activity_type');

		if (!empty($activityType))
		{
			$query->where($db->qn('a.activity_type_id') . ' = ' . (int) $activityType);
		}

		// Filtering by license_id
		$licenseId = $this->getState('filter.license_id');

		if (!empty($licenseId))
		{
			$query->where($db->qn('sa.license_id') . ' = ' . (int) $licenseId);
		}

		// Get timelog by cluster id
		$clusterId = $this->getState('filter.cluster_id');

		if (!empty($clusterId))
		{
			$query->where($db->qn('sa.cluster_id') . ' = ' . (int) $clusterId);
		}

		// Get timelog of active/archive licence
		$licenceState = $this->getState('filter.licence_state');

		if (!empty($licenceState))
		{
			$query->where($db->qn('ml.state') . ' = ' . (int) $licenceState);
		}

		// Filter by begin date.
		$dateFrom = $this->getState('filter.date_from');

		if (!empty($dateFrom))
		{
			$dateFrom = new Date($dateFrom, 'UTC');
			$query->where($db->quoteName('a.created_date') . ' >= ' . $db->quote($dateFrom));
		}

		// Filter by end date.
		$dateTo = $this->getState('filter.date_to');

		if (!empty($dateTo))
		{
			$dateTo = new Date($dateTo, 'UTC');
			$query->where($db->quoteName('a.created_date') . ' <= ' . $db->quote($dateTo));
		}

		// Add the list ordering clause.
		$orderCol  = $this->state->get('list.ordering', "a.id");
		$orderDirn = $this->state->get('list.direction', "DESC");

		if ($orderCol && $orderDirn)
		{
			$query->order($db->escape($orderCol . ' ' . $orderDirn));
		}

		return $query;
	}

	/**
	 * Method to get a list of courses.
	 *
	 * @return  mixed  An array of data items on success, false on failure.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function getItems()
	{
		$items = parent::getItems();
		$filter = InputFilter::getInstance();

		if (!empty($items))
		{
			// Include media library models
			BaseDatabaseModel::addIncludePath(JPATH_SITE . '/libraries/techjoomla/media/models');

			foreach ($items as $item)
			{
				// Create TJMediaXref class object
				$modelMediaXref = BaseDatabaseModel::getInstance('Xref', 'TJMediaModel', array('ignore_request' => true));
				$modelMediaXref->setState('filter.clientId', $item->id);
				$modelMediaXref->setState('filter.client', 'com_timelog.activity');

				$mediaData = $modelMediaXref->getItems();

				if (!empty($mediaData))
				{
					$item->mediaFiles = $mediaData;
				}
			}
		}

		return $items;
	}
}
