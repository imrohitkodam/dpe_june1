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
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Form\FormHelper;

JLoader::import('com_tjreports.models.reports', JPATH_SITE . '/components');
JLoader::import("/components/com_dpe/includes/dpe", JPATH_SITE);

/**
 * SLA report plugin of TJReport
 *
 * @since  1.0.0
 */
class TjreportsModelSlaactivityreport extends TjreportsModelReports
{
	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see     BaseDatabaseModel
	 * @since   1.0.0
	 */
	public function __construct($config = array())
	{
		Factory::getApplication()->input->set('report', 'slaactivityreport');

		$this->columns = array(
			'school_name' => array('table_column' => '', 'title' => 'PLG_TJREPORTS_DPE_SCHOOL'),
			'sla_title' => array('table_column' => '', 'title' => 'PLG_TJREPORTS_DPE_SLA_TITLE'),
			'uname' => array('table_column' => '', 'title' => 'PLG_TJREPORTS_DPE_LEAD_CONSULTANT'),
			'activity_type_title' => array('table_column' => '', 'title' => 'PLG_TJREPORTS_DPE_ACTIVITY_TITLE'),
			'sla_service_title' => array('table_column' => '', 'title' => 'PLG_TJREPORTS_DPE_ACTIVITY_NAME'),
			'todo_status' => array('table_column' => '', 'title' => 'PLG_TJREPORTS_DPE_STATUS'),
			'todo_due_date' => array('table_column' => '', 'title' => 'PLG_TJREPORTS_DPE_DUE_DATE'),
			'spentTime' => array('table_column' => '', 'title' => 'PLG_TJREPORTS_DPE_SPENT_TIME'),
			'school_member' => array('table_column' => '', 'title' => 'PLG_TJREPORTS_DPE_SCHOOL_MEMBER'),
			'activity_description' => array('table_column' => '', 'title' => 'PLG_TJREPORTS_DPE_DESCRIPTION','disable_sorting' => true)
		);

		parent::__construct($config);
	}

	/**
	 * Get client of this plugin
	 *
	 * @return array<string,mixed|string> Plugin Details
	 *
	 * @since   1.0.0
	 * */
	public function getPluginDetail()
	{
		return $detail = array('client' => 'com_sla', 'title' => Text::_('PLG_TJREPORTS_SLA_ACTIVITY_REPORT'));
	}

	/**
	 * Function to get the leadConsultant filter
	 *
	 * @return  array
	 *
	 * @since 1.0.0
	 */
	public function getLeadConsultant()
	{
		// Get user groups as per Config
		$params                = ComponentHelper::getParams('com_multiagency');
		$dpeAdminGroupId       = (int) $params->get('multiagency_admin_group', '0');
		$leadConsultantGroupId = (int) $params->get('multiagency_leadconsultant_group', '0');
		$dpeAdminList          = array();
		$lcList                = array();
		$options               = array();
		$user                  = Factory::getUser();

		if ($dpeAdminGroupId)
		{
			// Get DPE Admin List
			$db = Factory::getDbo();
			$query = $db->getQuery(true);

			$query->select(array('u.id', 'u.name'));
			$query->from('`#__users` AS u');
			$query->join('LEFT', $db->quoteName('#__user_usergroup_map', 'map') . ' ON ' . $db->quoteName('u.id') . ' = ' . $db->quoteName('map.user_id'));
			$query->where($db->quoteName('map.group_id') . '= ' . (int) $dpeAdminGroupId);
			$query->where('u.block = 0');
			$query->group($db->quoteName('u.id'));
			$query->order($db->quoteName('u.name') . ' ASC');

			$db->setQuery($query);

			$dpeAdminList = $db->loadObjectList();
		}

		if (!$user->authorise('core.manageall', 'com_cluster'))
		{
			return $options;
		}

		if ($leadConsultantGroupId)
		{
			// Get External LC List
			$db = Factory::getDbo();
			$query = $db->getQuery(true);

			$query->select(array('u.id', 'u.name'));
			$query->from('`#__users` AS u');
			$query->join('LEFT', $db->quoteName('#__user_usergroup_map', 'map') . ' ON ' . $db->quoteName('u.id') . ' = ' . $db->quoteName('map.user_id'));
			$query->where($db->quoteName('map.group_id') . '= ' . (int) $leadConsultantGroupId);
			$query->where('u.block = 0');
			$query->group($db->quoteName('u.id'));
			$query->order($db->quoteName('u.name') . ' ASC');

			$db->setQuery($query);

			$lcList = $db->loadObjectList();
		}

		// Construct Drop Down
		$options[] = HTMLHelper::_('select.option', '', Text::_('COM_MULTIAGENCY_SELECT_DPE_STAFF'));
		$options[] = HTMLHelper::_('select.options', Text::_('COM_MULTIAGENCY_DPE_ADMIN_LIST_TITLE'));

		// Dpe admin users
		foreach ($dpeAdminList as $dpeAdminUser)
		{
			$options[] = HTMLHelper::_('select.option', $dpeAdminUser->id, $dpeAdminUser->name);
		}

		$options[] = HTMLHelper::_('select.options', '');

		if (!empty($lcList))
		{
			$options[] = HTMLHelper::_('select.options', Text::_('COM_MULTIAGENCY_EXTERNAL_LC_LIST_TITLE'));
		}

		// External LC
		foreach ($lcList as $lc)
		{
			$options[] = HTMLHelper::_('select.option', $lc->id, $lc->name);
		}

		return $options;
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
	 * @since    1.0.9
	 */
	public function displayFilters()
	{
		FormHelper::addFieldPath(JPATH_ADMINISTRATOR . '/components/com_tjfields/models/fields');
		$clusterList = FormHelper::loadFieldType('Cluster', false);
		$clusterOptions = $clusterList->getOptionsExternally();
		
		// To Add the All instead of emppty value for the organisations to shwo the all value for multiple organisations.
		$user          = Factory::getUser();

		if($user->authorise('core.manageall', 'com_cluster') && (count($clusterOptions)>1))
		{
		  $clusterOptions[0]->value = "All" ;
		}

		$courseStatusFilter = array("" => "Select Status","C" => "Completed", "I" => "Incomplete","CN" => "Cancelled");
		$user               = Factory::getUser();
		$activityType       = $this->getActivityType();
		$leadConsultant     = array();

		FormHelper::addFieldPath(JPATH_SITE . '/components/com_dpe/models/fields');
		$activityStatus             = FormHelper::loadFieldType('genericstatus', false);
		$activityStatusFilterOption = $activityStatus->getOptionsExternally();

		if ($user->authorise('core.manageall', 'com_cluster'))
		{
			$leadConsultant = $this->getLeadConsultant();
		}

		$filters = (array) $this->getState('filters');

		if (!$filters['activityStatus'])
		{
			// Set default active
			$filters['activityStatus'] = 1;
		}

		// To set the calendar field date format
		$filters['dateFormat'] = Text::_('PLG_TJREPORTS_DPE_DUE_DATE_FORMAT');
		$this->setState('filters', $filters);

		$dispFilters = array(
			array(
				'activity_type_title' => array(
					'search_type' => 'text', 'searchin' => 'sat.title'),
				'sla_service_title' => array(
						'search_type' => 'text', 'searchin' => 'todo.title')
			)
			,
				array(
					'agency' => array(
								'search_type' => 'select', 'select_options' => $clusterOptions, 'type' => 'equal', 'searchin' => 'cl.id'
					),
					'todo_status' => array(
							'search_type' => 'select', 'select_options' => $courseStatusFilter, 'type' => 'equal', 'searchin' => 'todo.status'
					),
					'sat_id' => array(
						'search_type' => 'select', 'select_options' => $activityType, 'type' => 'equal', 'searchin' => 'sat.id'
					),
					'todo.due_date' => array(
						'search_type' => 'date.range',
						'searchin' => 'todo.due_date',
						'todo.due_date_from' => array('attrib' => array('placeholder' => Text::_('PLG_TJREPORTS_DPE_DUE_DATE_PLACEHOLDER_FORM'))),
						'todo.due_date_to' => array('attrib' => array('placeholder' => Text::_('PLG_TJREPORTS_DPE_DUE_DATE_PLACEHOLDER_TO'))),
					)
				)
		);

		if (!empty($leadConsultant))
		{
			$dispFilters[1]['userId'] = array(
				'search_type' => 'select', 'select_options' => $leadConsultant, 'type' => 'equal', 'searchin' => 'users.id'
			);
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
		$db      = $this->_db;
		$user    = Factory::getUser();
		$clients = array();
		$query   = parent::getListQuery();
		$filters = $this->getState('filters');

		// Create the base select statement.
		if ($countQuery)
		{
			$query->select('COUNT(sa.sla_id)');
		}
		else
		{
			$query->select(array('s.title as sla_title', 'todo.title as sla_service_title', 'users.name as uname', 'cl.name as school_name'));
			$query->select(array('todo.status as todo_status','todo.sender_msg AS activity_description'));
			$query->select(array('todo.due_date as todo_due_date'));
			$query->select(array('sat.title as activity_type_title'));
			$query->select(array('users1.name as school_member'));

			// If the sum of timelog is "20:50:00" the below query shows "20hr 50min" format
			$subQuery = $db->getQuery(true);
			$subQuery->select('TIME_FORMAT(SEC_TO_TIME(SUM(TIME_TO_SEC(timelog))), "' . Text::_('COM_DPE_TIME_FORMAT_DB_HRMIN') . '" )');
			$subQuery->from($db->quoteName('#__timelog_activities', 'tl'));
			$subQuery->where($db->quoteName('tl.client_id') . ' = ' . $db->qn('sa.id'));
			$query->select('(' . $subQuery . ') AS spentTime');
		}

		$query->from($db->quoteName('#__tj_sla_activities', 'sa'));
		$query->join('INNER', $db->quoteName('#__tj_slas', 's') . ' ON (' . $db->quoteName('s.id') . ' = ' . $db->quoteName('sa.sla_id') . ')');
		$query->join('LEFT', $db->quoteName('#__tj_sla_activity_types', 'sat')
		. ' ON (' . $db->quoteName('sat.id') . ' = ' . $db->quoteName('sa.sla_activity_type_id') . ')');

		$query->join('INNER', $db->quoteName('#__tjmultiagency_licences', 'ml')
		. ' ON (' . $db->quoteName('sa.license_id') . ' = ' . $db->quoteName('ml.id') . ')');

		$query->join('INNER', $db->quoteName('#__tj_clusters', 'cl')
		. ' ON (' . $db->quoteName('sa.cluster_id') . ' = ' . $db->quoteName('cl.id') . ')');
		$query->join('LEFT', $db->quoteName('#__jlike_todos', 'todo')
		. ' ON (' . $db->quoteName('todo.id') . ' = ' . $db->quoteName('sa.todo_id') . ')');
		$query->join('LEFT', $db->quoteName('#__jlike_todos', 'todo1')
		. ' ON (' . $db->quoteName('todo.id') . ' = ' . $db->quoteName('todo1.parent_id') . ')');
		$query->join('LEFT', $db->quoteName('#__users', 'users') . ' ON (' . $db->quoteName('todo.assigned_to') . ' = ' . $db->quoteName('users.id') . ')');
		$query->join('LEFT', $db->quoteName('#__users', 'users1') .
		' ON (' . $db->quoteName('todo1.assigned_to') . ' = ' . $db->quoteName('users1.id') . ')');

		// Cluster check added instead of agency

		$usersClusters = array();

		if (!$user->authorise('core.manageall', 'com_cluster'))
		{
			$cluster = FormHelper::loadFieldType('cluster', false);
			$clusterList = $cluster->getOptionsExternally();

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

		$query->where("(" . $db->qn('sa.sla_activity_type_id') . ' != 0 OR ' . $db->qn('sa.sla_activity_type_id') . " !='')");

		// Load active activities by default
		if ($filters['activityStatus'] == 1)
		{
			// Set default active
			$query->where($db->quoteName('sa.state') . " = 1 ");
		}

		return $query;
	}

	/**
	 * Method to get an array of data items.
	 *
	 * @return  mixed  An array of data items on success, false on failure.
	 *
	 * @since   1.0.0
	 */
	public function getItems()
	{
		// To get and set the session filter value of organisation
		$filters = (array) $this->getState('filters');
		
		$session  = Factory::getSession();

		if (!empty($filters['agency']) && $filters['agency'] != 'All') {
	    	// Add session when filter has some value
			$session->set('reportCluster', $filters['agency']);

		}elseif($filters['agency'] == 'All')
		{	
			$session->clear('reportCluster');
			$filters['agency'] = '';
			$this->setState('filters', $filters);
		} else {
			$filters['agency'] = $session->get('reportCluster');
			$this->setState('filters', $filters);
		}

		// Changing status as per values
		$items = parent::getItems();

		$params = DPE::config();

		foreach ($items as &$item)
		{
			if ($item['todo_status'] == 'I')
			{
				$item['todo_status'] = 'Incomplete';
			}

			if ($item['todo_status'] == 'C')
			{
				$item['todo_status'] = 'Complete';
			}

			if ($item['todo_status'] == 'CN')
			{
				$item['todo_status'] = 'Cancelled';
			}

			if (!empty($item['todo_due_date']) && $item['todo_due_date'] != '0000-00-00 00:00:00')
			{
				$item['todo_due_date'] = HTMLHelper::_('date', $item['todo_due_date'], (String) $params->get('dateFormat'));
			}
			else
			{
				$item['todo_due_date'] = '';
			}
		}

		return $items;
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
			$query->clear('select')->clear('order')->clear('limit')->clear('offset')->select('COUNT(sa.sla_id)');

			$this->cache[$store] = (int) $this->_getListCount($query);
		}
		catch (\RuntimeException $e)
		{
			$this->setError($e->getMessage());

			return false;
		}

		return $this->cache[$store];
	}
}
