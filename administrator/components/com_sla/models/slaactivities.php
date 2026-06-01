<?php
/**
 * @package    Sla
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');
use Joomla\Data\DataObject;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Component\ComponentHelper;

JLoader::import('components.com_sla.includes.sla', JPATH_ADMINISTRATOR);
JLoader::import('components.com_multiagency.includes.multiagency', JPATH_SITE);


/**
 * Methods supporting a list of records.
 *
 * @since  1.0.0
 */
class SlaModelSlaActivities extends ListModel
{
	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see        JController
	 * @since      1.0.0
	 */
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'id', 'sa.id',
				'sla_activity_type_id', 'sa.sla_activity_type_id',
				'sla_id', 'sa.sla_id',
				'sla_service_id', 'sa.sla_service_id',
				'cluster_id', 'sa.cluster_id',
				'license_id', 'sa.license_id',
				'status', 'todo.status',
				'ideal_time', 'todo.ideal_time',
				'ordering', 'sa.ordering',
				'state', 'sa.state',
				'created_by', 'sa.created_by',
				'users.id', 'todo.start_date',
				'start_date', 'todo.due_date',
				'due_date',
				'lead_consultant_id',
				'sla_status',
				'tags'
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
	 * @since    1.6
	 *
	 * @return  void
	 */
	protected function populateState($ordering = 'sa.id', $direction = 'desc')
	{
		$app = Factory::getApplication();

		$licenseId = $app->getUserStateFromRequest($this->context . '.filter.license_id', 'license_id');
		$this->setState('filter.license_id', $licenseId);

		parent::populateState($ordering, $direction);
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return   DataObjectbaseQuery
	 *
	 * @since    1.0.0
	 */
	protected function getListQuery()
	{
		// Initialize variables.
		$app = Factory::getApplication();
		$db    = $this->getDbo();
		$query = $db->getQuery(true);

		$user = Factory::getUser();

		// Create the base select statement.
		$query->select(
		array('sa.id', 'sa.todo_id','sa.cluster_id','sa.license_id', 'todo.title as sla_service_title', 'users.name as uname', 'cl.name as school_name',
		'sa.state')
		);
		$query->select(array('todo.status as todo_status', 'todo.ideal_time as todo_ideal_time'));
		$query->select(array('todo.start_date as todo_start_date', 'todo.due_date as todo_due_date'));
		$query->select(array('sat.title as activity_type_title'));
		$query->from($db->quoteName('#__tj_sla_activities', 'sa'));

		$query->join('INNER', $db->quoteName('#__tj_sla_activity_types', 'sat')
			. ' ON (' . $db->quoteName('sat.id') . ' = ' . $db->quoteName('sa.sla_activity_type_id') . ')');

		/*$query->join('LEFT', $db->quoteName('#__tj_sla_services', 'sl')
			. ' ON (' . $db->quoteName('sl.id') . ' = ' . $db->quoteName('sa.sla_service_id') . ')');*/

		$query->join('INNER', $db->quoteName('#__tj_clusters', 'cl')
					. ' ON (' . $db->quoteName('sa.cluster_id') . ' = ' . $db->quoteName('cl.id') . ')');

		$query->join('INNER', $db->quoteName('#__jlike_todos', 'todo')
					. ' ON (' . $db->quoteName('todo.id') . ' = ' . $db->quoteName('sa.todo_id') . ')');

		$query->join('LEFT', $db->quoteName('#__users', 'users') . ' ON (' . $db->quoteName('todo.assigned_to') . ' = ' . $db->quoteName('users.id') . ')');

		/* If the sum of timelog is "20:50:00" the below query shows "20hr 50min" format */
		/*
		$subQuery = $db->getQuery(true);
		$subQuery->select('TIME_FORMAT(SEC_TO_TIME(SUM(TIME_TO_SEC(timelog))), "%Hhr %imin" )');
		$subQuery->from($db->quoteName('#__timelog_activities', 'tl'));
		$subQuery->where($db->quoteName('tl.client_id') . ' = ' . $db->qn('sa.id'));

		$query->select('(' . $subQuery . ') AS spentTime');
		*/

		// Filter by dashboard_id
		$id = $this->getState('filter.id');

		if (!empty($id))
		{
			$query->where($db->quoteName('sa.id') . ' = ' . (int) $id);
		}

		// Filter by search in title.
		$search = $this->getState('filter.search');

		if (!empty($search))
		{
			$query->select(array('s.title as sla_title'));

			$query->join('LEFT', $db->quoteName('#__tj_slas', 's') . ' ON (' . $db->quoteName('s.id') . ' = ' . $db->quoteName('sa.sla_id') . ')');
		}

		if (!empty($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where('sa.id = ' . (int) substr($search, 3));
			}
			else
			{
				$search = $db->quote('%' . str_replace(' ', '%', $db->escape(trim($search), true) . '%'));
				$query->where('(s.title LIKE ' . $search . ' OR todo.title LIKE ' . $search .
					' OR cl.name LIKE ' . $search . ' )');
			}
		}

		// Filter by Activity start
		$dateFilterFormat = Text::_('COM_SLA_DB_DATETIME');
		$activityStartDate = $this->getState('filter.start_date');

		if (!empty($activityStartDate))
		{
			$activityStartDate .= ' 00:00:00';
			$activityStartDate = new Date($activityStartDate, 'UTC');
		}

		// Filter by Activity due dates
		$activityDueDate   = $this->getState('filter.due_date');

		if (!empty($activityDueDate))
		{
			$activityDueDate .= ' 23:59:59';
			$activityDueDate = new Date($activityDueDate, 'UTC');
		}

		// Check activitites between dates
		if (!empty($activityStartDate))
		{
			$query->where("DATE_FORMAT(todo.due_date, '$dateFilterFormat')" . ' >= ' . $db->quote($activityStartDate));
		}

		if (!empty($activityDueDate))
		{
			$query->where("DATE_FORMAT(todo.due_date, '$dateFilterFormat')" . ' <= ' . $db->quote($activityDueDate));
		}

		// Filter by sla_activity_type_id
		$slaActivityTypeId = $this->getState('filter.sla_activity_type_id');

		if (!empty($slaActivityTypeId))
		{
			$query->where($db->quoteName('sa.sla_activity_type_id') . ' = ' . (int) $slaActivityTypeId);
		}

		// Filter by leadconsultant
		$leadconsultantId = $this->getState('filter.lead_consultant_id');

		if (!empty($leadconsultantId))
		{
			$query->where($db->quoteName('users.id') . ' = ' . (int) $leadconsultantId);
		}

		// Filter by todos sla status
		$slaStatus = $this->getState('filter.sla_status');

		if (!empty($slaStatus))
		{
			$query->where($db->quoteName('todo.status') . ' = ' . $db->quote($slaStatus));
		}

		// Filter by created_by
		$created_by = $this->getState('filter.created_by');

		if (!empty($created_by))
		{
			$query->where($db->quoteName('sa.created_by') . ' = ' . (int) $created_by);
		}

		// Filter by activity state and default state is active
		$state = $this->getState('filter.state', 1);

		if (is_numeric($state))
		{
			$query->where('sa.state = ' . (int) $state);
		}

		// Filter by sla
		$sla = $this->getState('filter.sla_id');

		if (is_numeric($sla))
		{
			$query->where('sa.sla_id = ' . (int) $sla);
		}

		// Filter by sla service
		$slaService = $this->getState('filter.sla_service_id');

		if (is_numeric($slaService))
		{
			$query->where('sa.sla_service_id = ' . (int) $slaService);
		}

		// Filter by tags (DPE Hack)
		$agencyTag = array_filter((array) $this->getState('filter.tags'));

		if (is_array($agencyTag))
		{
			foreach ($agencyTag as $key => $agencyTags)
			{
				if (!is_int($agencyTags))
				{
					$agencyTag[$key] = (int) $agencyTags;
				}
			}
		}

		$clusterIdsByTags = null;
	
		if (!empty($agencyTag) && ($user->authorise('core.manageall', 'com_cluster') ))
		{
			JModelLegacy::addIncludePath(JPATH_SITE . '/components/com_dpe/models', 'DpeModel');
			$dashBoardModel = JModelLegacy::getInstance('Dashboard', 'DpeModel');
			$clusterIdsByTags = $dashBoardModel->getClusterIdsByTags($agencyTag);
		}

		if ($clusterIdsByTags)
		{
			if (is_array($clusterIdsByTags))
			{
				$query->where('sa.cluster_id IN (' . implode(',', $clusterIdsByTags) . ')');
			}
			else
			{
				$query->where('sa.cluster_id = ' . (int) $clusterIdsByTags);
			}
		}
		else
		{
			// Filter by cluster
			$clusterId = $this->getState('filter.cluster_id');

			if (is_numeric($clusterId))
			{
				$query->where('sa.cluster_id = ' . (int) $clusterId);
			}
		}

		if (!$user->authorise('core.manageall', 'com_cluster'))
		{
			JLoader::import("/components/com_cluster/includes/cluster", JPATH_ADMINISTRATOR);
			$clusterUserModel = ClusterFactory::model('ClusterUser', array('ignore_request' => true));
			$clusters = $clusterUserModel->getUsersClusters($user->id);

			foreach ($clusters as $cluster)
			{
				if (!empty($cluster->cluster_id))
				{
					// Todo: Need to introduce cluster level permission in com_sla
					if (RBACL::check($user->id, 'com_cluster', 'core.adduser', 'com_multiagency', $cluster->cluster_id))
					{
						$clusterIds[] = $cluster->cluster_id;
					}
				}
			}

			$query->where($db->qn('sa.cluster_id') . " IN ('" . implode("','", $clusterIds) . "')");
		}

		// Code start to reset the licence filter on state change
		$newState = $this->getState('filter.state');
		$session  = Factory::getSession();
		$oldState = $session->get("dpe_sla_activity_state");
		$session->set("dpe_sla_activity_state", $newState);

		//  commented due to state change  it won't show the selected license data if we select license first and then state.

		/*if (! empty ($oldState && $newState))
		{
			if ($oldState != $newState)
			{
				 $this->setState('filter.license_id', '');
			}
		}*/

		// Code end

		// Filter by cluster
		 $licenseId = $this->getState('filter.license_id');

		if (is_numeric($licenseId))
		{
			$query->where('sa.license_id = ' . (int) $licenseId);
		}

		// Check permission to manage activities
		$canManageOwnActivity = $user->authorise('core.manage.activity.own', 'com_sla');
		$canManageAllActivity = $user->authorise('core.manage.activity', 'com_sla');

		if ($canManageOwnActivity && !$canManageAllActivity)
		{
			$query->where('todo.assigned_to = ' . (int) $user->id);
		}

		// School Manager, School Admin can see only own school associated activities.
		if (!$user->authorise('core.manageall', 'com_cluster'))
		{
			$query->join('INNER', $db->quoteName('#__tj_cluster_nodes', 'cnode')
					. ' ON (' . $db->quoteName('cnode.cluster_id') . ' = ' . $db->quoteName('cl.id') . ')');
			$query->where('cnode.user_id = ' . (int) $user->id);
		}

		// Add the list ordering clause.
		$orderCol  = $this->state->get('list.ordering', 'sa.id');
		if ($orderCol == 'sa.id')
		{
			$orderDirn='Desc';
		}else
		{
			$orderDirn = $this->state->get('list.direction', 'DESC');
		}

		if (!in_array(strtoupper($orderDirn), array('ASC', 'DESC', '')))
		{
			$orderDirn = 'DESC';
		}


		$fullordering = $this->state->get('list.fullordering');

		if(($fullordering =='sa.id desc') || ( strpos($fullordering, "null")!== false))
		{
			 $query->order("
    CASE
        WHEN DATE(todo.due_date) = CURDATE() THEN 1          
        WHEN DATE(todo.due_date) > CURDATE() THEN 2         
        WHEN DATE(todo.due_date) < CURDATE() THEN 3          
        WHEN todo.due_date IS NULL THEN 4                    
        ELSE 5
    END,
    ABS(DATEDIFF(todo.due_date, CURDATE())) ASC
");
		}
		   
			    // Apply user-defined ordering
		  $query->order($db->escape($orderCol . ' ' . $orderDirn));
		
		return $query;
	}

	/**
	 * Get Items functions
	 *
	 * @return	Object
	 *
	 * @since	1.0.0
	 */
	public function getItems()
	{
		$items = parent::getItems();

		foreach ($items as $item)
		{
			$attachement = $this->checkAttachment($item->id);

			if ($attachement)
			{
				$item->media = $attachement;
			}

			$item->spentTime = $this->getSpentTime($item->id);
		}

		return $items;
	}

	/**
	 * function to check attachment for sla activity
	 *
	 * @param   int  $slaActivityId  id
	 *
	 * @return	Object
	 *
	 * @since	1.0.0
	 */
	public function checkAttachment($slaActivityId)
	{
		if ($slaActivityId)
		{
			$db    = $this->getDbo();
			$query = $db->getQuery(true);
			$query->select('media.id as media');
			$query->from($db->quoteName('#__tj_media_files_xref', 'media'));
			$query->join('LEFT', $db->quoteName('#__timelog_activities', 'ta')
					. ' ON (' . $db->quoteName('media.client_id') . ' = ' . $db->quoteName('ta.id') . ')');
			$query->join('LEFT', $db->quoteName('#__tj_sla_activities', 'sa')
					. ' ON (' . $db->quoteName('sa.id') . ' = ' . $db->quoteName('ta.client_id') . ')');
			$query->where($db->quoteName('sa.id') . ' = ' . (int) $slaActivityId);

			$db->setQuery($query);

			return $db->loadResult();
		}
	}

	/**
	 * function to check attachment for sla activity
	 *
	 * @param   int  $slaActivityId  id
	 *
	 * @return	Object
	 *
	 * @since	1.0.0
	 */
	public function getSpentTime($slaActivityId)
	{
		if ($slaActivityId)
		{
			$db    = $this->getDbo();
			$query = $db->getQuery(true);
			$query->select('TIME_FORMAT(SEC_TO_TIME(SUM(TIME_TO_SEC(timelog))), "%Hhr %imin" )');
			$query->from($db->quoteName('#__timelog_activities', 'tl'));
			$query->where($db->quoteName('tl.client_id') . ' = ' . $db->quote($slaActivityId));

			$db->setQuery($query);

			return $db->loadResult();
		}
	}

	/**
	 * function to update the state of activities
	 *
	 * @param   int  $licenceId  id
	 * @param   int  $state      state
	 *
	 * @return	boolean
	 *
	 * @since	__DEPLOY_VERSION__
	 */
	public function updateActivitiesState($licenceId, $state)
	{
		$user = Factory::getUser();

		if ($licenceId && $state)
		{
			$db    = Factory::getDbo();
			$query = $db->getQuery(true);

			$fields = array($db->quoteName('state') . ' = ' . (int) $state);

			// Conditions for which records should be updated.
			$conditions = array($db->quoteName('license_id') . ' = ' . (int) $licenceId);

			$query->update($db->quoteName('#__tj_sla_activities'))->set($fields)->where($conditions);
			$db->setQuery($query);

			return $db->execute();
		}

		return true;
	}

	/**
	 * Function to update the sla id of activities
	 *
	 * @param   integer  $licenceId  Licence Id
	 *
	 * @param   integer  $slaId      Sla id
	 *
	 * @return  void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function updateActivitiesSlaId($licenceId, $slaId)
	{
		if ($licenceId && $slaId)
		{
			// Update Sla ids in activities
			$db     = Factory::getDbo();
			$query  = $db->getQuery(true);
			$fields = array($db->quoteName('sla_id') . ' =  ' . (int) $slaId);

			// Conditions for which records should be updated.
			$conditions = array($db->quoteName('license_id') . ' = ' . (int) $licenceId);

			$query->update($db->quoteName('#__tj_sla_activities'))->set($fields)->where($conditions);
			$db->setQuery($query);

			$db->execute();
		}
	}

	/**
	 * Function to create activities for provided activity type
	 *
	 * @param   array    $data    Licence data
	 * @param   integer  $typeId  Activity type id
	 * @param   integer  $count   Activity count
	 *
	 * @return  void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function createActivities($data, $typeId, $count)
	{
		if (!empty($data) && $typeId && $count)
		{
			$db         = Factory::getDbo();
			$nullDate   = $db->getNullDate();
			$slaTypeObj = SlaSlaActivityType::getInstance($typeId);

			// Before creating activities check the licence is available
			$licenceTable = Multiagency::table('licence');
			$licenceTable->load(array('id' => $data['id']));

			if (!$licenceTable->id)
			{
				return false;
			}

			// Create Services
			$slaServiceObj                       = SlaSlaService::getInstance();
			$slaServiceObj->sla_id               = $data['sla_id'];
			$slaServiceObj->sla_activity_type_id = $typeId;
			$slaServiceObj->title                = $slaTypeObj->title;
			$slaServiceObj->state                = 1;
			$slaServiceObj->save();

			// Add todo
			$todoData = array();
			$todoData['start_date']  = $nullDate;
			$todoData['due_date']    = $nullDate;
			$todoData['assigned_to'] = $data['lead_consultant_id'];

			// This will use when activities created by cron
			if ($data['key'])
			{
				$todoData['key'] = $data['key'];
			}

			for ($i = 1; $i <= $count; $i++)
			{
				$todoData['title'] = $slaTypeObj->title . ' ' . $i;
				$jlikeTodoId = $slaServiceObj->saveTodo($todoData);

				// Insert SLA activities
				$slaSlaActivity                       = SlaSlaActivity::getInstance();
				$slaSlaActivity->sla_activity_type_id = $typeId;
				$slaSlaActivity->sla_id               = $data['sla_id'];
				$slaSlaActivity->sla_service_id       = $slaServiceObj->id;
				$slaSlaActivity->cluster_id           = $data['cluster_id'];
				$slaSlaActivity->license_id           = $data['id'];
				$slaSlaActivity->todo_id              = $jlikeTodoId;

				if ($data['state'])
				{
					$slaSlaActivity->state = $data['state'];
				}

				$slaSlaActivity->save();
			}
		}
	}

	/**
	 * Function to delete activities
	 *
	 * @param   integer  $licenceId  licence id
	 *
	 * @return  void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function deleteActivities($licenceId)
	{
		if ($licenceId)
		{
			$db    = Factory::getDbo();
			$query = $db->getQuery(true);

			// State 3 used to delete the upcoming activities
			$conditions = array(
			$db->quoteName('license_id') . ' = ' . (int) $licenceId,
			$db->quoteName('state') . ' = 3'
			);

			$query->delete($db->quoteName('#__tj_sla_activities'));
			$query->where($conditions);

			$db->setQuery($query);

			$result = $db->execute();
		}
	}

	/**
	 * Function to delete tools
	 *
	 * @param   integer  $licenceId  licence id
	 *
	 * @return  void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function deleteTools($licenceId)
	{
		if ($licenceId)
		{
			$db    = Factory::getDbo();
			$query = $db->getQuery(true);

			$conditions = array(
			$db->quoteName('licence_id') . ' = ' . (int) $licenceId
			);

			$query->delete($db->quoteName('#__tjmultiagency_licences_xref'));
			$query->where($conditions);

			$db->setQuery($query);

			$db->execute();
		}
	}
}
