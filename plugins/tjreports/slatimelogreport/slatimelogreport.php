<?php
/**
 * @package    DPE
 * @author     TechJoomla | <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2019. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Data\DataObject;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

JLoader::import('com_tjreports.models.reports', JPATH_SITE . '/components');

/**
 * SLA timelog report plugin of TJReport
 *
 * @since  1.0.0
 */
class TjreportsModelSlatimelogreport extends TjreportsModelReports
{
	// To hide search icon/button from report filters
	public $showSearchResetButton = -1;

	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see     BaseDatabaseModel
	 * @since   1.0
	 */
	public function __construct($config = array())
	{
		Factory::getApplication()->input->set('report', 'slatimelogreport');
		$this->columns = array(
			'school_name' => array('table_column' => '', 'title' => 'PLG_TJREPORTS_DPE_SCHOOL'),
			'created_by' => array('table_column' => '', 'title' => 'PLG_TJREPORTS_DPE_SLA_TIMELOG_CREATEDBY'),
			'activity_title' => array('table_column' => '', 'title' => 'PLG_TJREPORTS_DPE_SLA_TIMELOG_TITLE'),
			'activity_type_title' => array('table_column' => '', 'title' => 'PLG_TJREPORTS_DPE_SLA_TIMELOG_TYPE'),
			'log_date' => array('table_column' => '', 'title' => 'PLG_TJREPORTS_DPE_SLA_TIMELOG_LOGDATE'),
			'spent_time' => array('table_column' => '', 'title' => 'PLG_TJREPORTS_DPE_SLA_TIMELOG_LOGTIME'),
			'description' => array('table_column' => '', 'title' => 'PLG_TJREPORTS_DPE_SLA_TIMELOG_DESCRIPTION')
		);

		parent::__construct($config);
	}

	/**
	 * Get client of this plugin
	 *
	 * @return array<string,mixed|string> Plugin Details
	 *
	 * @since   2.0
	 * */
	public function getPluginDetail()
	{
		return $detail = array('client' => 'com_sla', 'title' => Text::_('PLG_TJREPORTS_SLA_TIMELOG_REPORT'));
	}

	/**
	 * Function to get the users filter
	 *
	 * @return  array
	 *
	 * @since 1.0.0
	 */
	public function getUsers()
	{
		$activityUserFilter = array();
		$db = $this->_db;
		$query = $db->getQuery(true);
		$query->select('distinct(users.id),users.name');
		$query->from('#__users as users');
		$query->join('INNER', $db->qn('#__timelog_activities', 'actvities') .
		' ON (' . $db->qn('actvities.created_by') . ' = ' . $db->qn('users.id') . ')');
		$query->where('users.block = 0');
		$query->order($db->escape('users.name' . ' ' . 'asc'));
		$db->setQuery($query);
		$users = $db->loadObjectList();

		$activityUserFilter[] = HTMLHelper::_('select.option', '', Text::_('COM_TJREPORTS_FILTER_SELECT_USER'));

		if (!empty($users))
		{
			foreach ($users as $eachUsers)
			{
				$activityUserFilter[] = HTMLHelper::_('select.option', $eachUsers->id, $eachUsers->name);
			}
		}

		return $activityUserFilter;
	}

	/**
	 * Function to get the activityType filter
	 *
	 * @return  array
	 *
	 * @since 1.0.0
	 */
	public function getActivityType()
	{
		$activityTypeFilter = array();
		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select('at.id,at.title');
		$query->from('#__tj_sla_activity_types as at');
		$query->order($db->escape('at.title' . ' ' . 'asc'));
		$db->setQuery($query);
		$activityType = $db->loadObjectList();

		$activityTypeFilter[] = HTMLHelper::_('select.option', '', Text::_('COM_TJREPORTS_FILTER_SELECT_ACTIVITY_TYPE'));

		if (!empty($activityType))
		{
			foreach ($activityType as $eachactivityType)
			{
				$activityTypeFilter[] = HTMLHelper::_('select.option', $eachactivityType->id, $eachactivityType->title);
			}
		}

		return $activityTypeFilter;
	}

	/**
	 * Create an array of filters
	 *
	 * @return    ARRAY Filters used in reports
	 *
	 * @since    1.0
	 */
	public function displayFilters()
	{
		$users         = $this->getUsers();
		$loggedInUser = Factory::getUser();

		// To get Agency dropdown list
		FormHelper::addFieldPath(JPATH_ADMINISTRATOR . '/components/com_tjfields/models/fields');
		$clusterList    = FormHelper::loadFieldType('Cluster', false);
		$clusterOptions = $clusterList->getOptionsExternally();
		$activityType   = $this->getActivityType();

		$user          = Factory::getUser();

		// To add all value if the user has admin dpe admin acess and admin acess with multiple organisation.
		if ($user->authorise('core.manageall', 'com_cluster') && count($clusterOptions)>1)
		{
		  $clusterOptions[0]->value = "All" ;
		}

		FormHelper::addFieldPath(JPATH_SITE . '/components/com_dpe/models/fields');
		$activityStatus             = FormHelper::loadFieldType('genericstatus', false);
		$activityStatusFilterOption = $activityStatus->getOptionsExternally();

		$filters = (array) $this->getState('filters');

		if (!$filters['activityStatus'])
		{
			// Set default active
			$filters['activityStatus'] = 1;
		}

		$this->setState('filters', $filters);

		$dispFilters = array(
			array(
				'activity_title' => array(
					'search_type' => 'text', 'searchin' => 'todo.title'),
				'created_by' => array(
					'search_type' => 'text', 'searchin' => 'created_by.name')
			),
			array(
				'cluster_id' => array(
					'search_type' => 'select', 'select_options' => $clusterOptions, 'type' => 'equal', 'searchin' => 'cl.id'
				),
				'sat_id' => array(
					'search_type' => 'select', 'select_options' => $activityType, 'type' => 'equal', 'searchin' => 'sat.id'
				),
				'created_by_id' => array(
					'search_type' => 'select', 'select_options' => $users, 'type' => 'equal', 'searchin' => 'created_by.id'
				)
			)
		);

		if ($loggedInUser->authorise('core.manageall', 'com_cluster'))
		{
			$dispFilters[1]['activityStatus'] = array(
				'search_type' => 'select', 'select_options' => $activityStatusFilterOption, 'type' => 'equal', 'searchin' => 'sa.state'
			);
		}

		return $dispFilters;
	}

	/**
	 * Method to get a DataObjectbaseQuery object for retrieving the data set from a database.
	 *
	 * @param   boolean  $countQuery  Check its count query or not
	 *
	 * @return  DataObjectbaseQuery  A DataObjectbaseQuery object to retrieve the data set.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	protected function getListQuery($countQuery = false)
	{
		$db        = $this->_db;
		$user      = Factory::getUser();
		$query     = parent::getListQuery();
		$colToshow = (array) $this->getState('colToshow');
		$filters   = $this->getState('filters');

		if ($countQuery)
		{
			$query->select('COUNT(a.id)');
		}
		else
		{
			// Below query shows "20hr 50min" format for "20:50:00" timelog value
			$query->select('TIME_FORMAT(timelog, "' . Text::_('COM_DPE_TIME_FORMAT_DB_HRMIN') . '") AS spent_time');
			$query->select('a.activity_note as description');
			$query->select('`created_by`.name AS `created_by`', '`created_by`.id AS `created_by_id`');
			$query->select('`modified_by`.name AS `modified_by`');
			$query->select(array('sat.title as activity_type_title'));
			$query->select('cl.name as school_name');
			$query->select('todo.title AS activity_title');
			$query->select('a.created_date AS log_date');
		}

		$query->from($db->qn('#__timelog_activities', 'a'));

		// Join over the user field 'created_by'
		$query->join('LEFT', $db->qn('#__users', 'created_by')
		. ' ON (' . $db->qn('created_by.id') . ' = ' . $db->qn('a.created_by') . ')');

		// Join over the user field 'modified_by'
		$query->join('LEFT', $db->qn('#__users', 'modified_by')
		. ' ON (' . $db->qn('modified_by.id') . ' = ' . $db->qn('a.modified_by') . ')');

		// Join over the client_id AND #__tj_sla_activities id
		$query->join('INNER', $db->qn('#__tj_sla_activities', 'sa')
		. ' ON (' . $db->qn('sa.id') . ' = ' . $db->qn('a.client_id') . ')');

		$query->join('LEFT', $db->qn('#__tj_sla_activity_types', 'sat')
		. ' ON (' . $db->qn('sat.id') . ' = ' . $db->qn('sa.sla_activity_type_id') . ')');

		$query->join('INNER', $db->quoteName('#__tjmultiagency_licences', 'ml')
		. ' ON (' . $db->quoteName('sa.license_id') . ' = ' . $db->quoteName('ml.id') . ')');

		// Join over the cluster_id
		$query->join('INNER', $db->qn('#__tj_clusters', 'cl')
		. ' ON (' . $db->qn('sa.cluster_id') . ' = ' . $db->qn('cl.id') . ')');

		if (!$countQuery)
		{
			// Join over the todo_id
			$query->join('INNER', $db->qn('#__jlike_todos', 'todo')
			. ' ON (' . $db->qn('todo.id') . ' = ' . $db->qn('sa.todo_id') . ')');
		}

		if (!$user->authorise('core.manageall', 'com_cluster'))
		{
			$cluster = FormHelper::loadFieldType('cluster', false);
			$clusterList = $cluster->getOptionsExternally();
			$usersClusters = array();

			if (!empty($clusterList))
			{
				foreach ($clusterList as $clusterList)
				{
					if (!empty($clusterList->value))
					{
						$usersClusters[] = $clusterList->value;
					}
				}
			}

			$query->where($db->qn('cl.id') . " IN ('" . implode("','", $usersClusters) . "')");
		}

		// Load active activities by default
		if ($filters['activityStatus'] == 1)
		{
			// Set default active
			$query->where($db->quoteName('sa.state') . " = 1 ");
		}

		return $query;
	}

	/**
	 * Method to get the total number of items for the data set.
	 *
	 * @return  integer  The total number of items available in the data set.
	 *
	 * @since   1.6
	 */
	public function getTotal()
	{
		// Get a storage key.
		$store = $this->getStoreId('getTotal');

		// Try to load the data from internal storage.
		if (isset($this->cache[$store]))
		{
			return $this->cache[$store];
		}

		try
		{
			// Load the total and add the total to the internal cache.
			$query = $this->getListQuery(true);
			$query->clear('order')->clear('limit')->clear('offset');

			$this->cache[$store] = (int) $this->_getListCount($query);
		}
		catch (\RuntimeException $e)
		{
			$this->setError($e->getMessage());

			return false;
		}

		return $this->cache[$store];
	}

	/**
	 * Method to get an array of data items.
	 *
	 * @return  mixed  An array of data items on success, false on failure.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function getItems()
	{
		$filters = (array) $this->getState('filters');

		// get session value for the clsuers.
		 $session = Factory::getSession();

		 if (!empty($filters['cluster_id']) && $filters['cluster_id'] != 'All') {
	    	// Add session when filter has some value
			$session->set('reportCluster', $filters['cluster_id']);

		}elseif($filters['cluster_id'] == 'All')
		{
			$session->clear('reportCluster');
			$filters['cluster_id'] = '';
			$this->setState('filters', $filters);
		} else {
			$filters['cluster_id'] = $session->get('reportCluster');
			$this->setState('filters', $filters);
		}

		$items = parent::getItems();
		$params = DPE::config();

		// Change date format for report
		foreach ($items as &$item)
		{
			if (!empty($item['log_date']) && $item['log_date'] != '0000-00-00 00:00:00')
			{
				$item['log_date'] = HTMLHelper::_('date', $item['log_date'], (String) $params->get('dateFormat'));
			}
			else
			{
				$item['log_date'] = "";
			}
		}

		return $items;
	}
}
