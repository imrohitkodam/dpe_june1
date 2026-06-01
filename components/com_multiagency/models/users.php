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
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

jimport('joomla.application.component.modellist');

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;

/**
 * Methods supporting a list of Subusers records.
 *
 * @since  __DEPLOY__VERSION__
 */
class MultiagencyModelUsers extends ListModel
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
				'user_id', 'b.user_id',
				'email', 'a.email',
				'username', 'a.username',
				'a.name', 'name',
				'c.title', 'title',
				'b.role_id', 'role_id',
				'agencies',
				'sla_filter'  //DPE Hack
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
		$this->params = $app->getParams('com_multiagency');
		$list = $app->getUserState($this->context . '.list');
		$ordering  = isset($list['filter_order'])     ? $list['filter_order']     : null;
		$direction = isset($list['filter_order_Dir']) ? $list['filter_order_Dir'] : null;
		$user  = Factory::getUser();

		$list['limit']     = (int) Factory::getConfig()->get('list_limit', 20);
		$list['start']     = $app->input->getInt('start', 0);
		$list['ordering']  = $ordering;
		$list['direction'] = $direction;

		$app->setUserState($this->context . '.list', $list);
		$app->input->set('list', null);

		// Set Agency Filter
		$agencies = (int) $app->getUserStateFromRequest($this->context . '.filter.agencies', 'agencies', '');
		$this->setState('filter.agencies', $agencies);

		// List state information.
		parent::populateState($ordering, $direction);


		// Load the filter state.
		$search = $app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		$slaFilter = $app->getUserStateFromRequest($this->context . '.filter.sla_filter', 'sla_filter', 'all');
		$this->setState('filter.sla_filter', $slaFilter);

		$orderCol = $app->input->get('filter_order', 'c.ordering');

		if (!in_array($orderCol, $this->filter_fields))
		{
			$orderCol = 'c.ordering';
		}

		$this->setState('list.ordering', $orderCol);

		$listOrder = $app->input->get('filter_order_Dir', 'ASC');

		if (!in_array(strtoupper($listOrder), array('ASC', 'DESC', '')))
		{
			$listOrder = 'ASC';
		}

		$this->setState('list.direction', $listOrder);

		$start = $app->getUserStateFromRequest($this->context . '.limitstart', 'limitstart', 0, 'int');
		$limit = $app->getUserStateFromRequest($this->context . '.limit', 'limit', 0, 'int');

		
		// if ($limit == 0)
		// {
		// 	$limit = $app->get('list_limit', 0);
		// }

		// Dpe Hack start		
		$orderCol  = $app->getUserState('com_multiagency.users.filter_order', $ordering);
		$orderDirn = $app->getUserState('com_multiagency.users.filter_order_Dir', $direction);

		$this->setState('list.ordering', $orderCol);
		$this->setState('list.direction', $orderDirn);
		// Dpe Hack end		


		$this->setState('list.limit', $limit);
		$this->setState('list.start', $start);
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return   DataObjectbaseQuery
	 *
	 * @since   __DEPLOY__VERSION__
	 */
	protected function getListQuery()
	{
		// If Super user is logged then show organization wise user
		$jinput = Factory::getApplication()->input;
		$user  = Factory::getUser();

		// If logged user is Super User or Admin
		$uid = $user->id;
		$db    = $this->getDbo();

		// Populate Agency
		$agencies = $this->getState('filter.agencies');

		// Filter by search in organisation name
		$search = $this->getState('filter.search');
		$dpeParam         = ComponentHelper::getParams('com_dpe');

		$jobTitleFieldId = $dpeParam->get('jobtitle', '0', 'INT');

		$params = ComponentHelper::getParams('com_multiagency');

		JLoader::import("/components/com_subusers/includes/rbacl", JPATH_ADMINISTRATOR);
		$adminRoleId    = $params->get('multyagency_admin_role_id', '0', 'INT');
		$orgAdminRoleId = $params->get('school_admin_role_id', '0', 'INT');
		$trusteeRoleId  = (int) $params->get('organization_trustee_role_id');
		$dpeAdmin       = RBACL::getRoleByUser($user->id, 'com_multiagency', 0);

		// Code execute for those users(Orphan) who doesn't having any agency
		if ($user->authorise('core.admin') || in_array($adminRoleId, $dpeAdmin))
		{
			if (!empty($agencies) && $agencies == strtolower(Text::_('COM_MULTIAGENCY_SELECT_NONE')))
			{
				$queryselect = $db->getQuery(true);
				$queryselect->select('a.*')
					->from($db->qn('#__users', 'a'))
					->join('LEFT', $db->qn('#__tjsu_users', 'b') . ' ON (' . $db->qn('a.id') . ' = ' . $db->qn('b.user_id') . ')')
					->where($db->qn('a.block') . ' = 0')
					->where('b.user_id IS NULL');

				if (!empty($search))
				{
					if (stripos($search, 'id:') === 0)
					{
						$queryselect->where($db->qn('a.id') . ' = ' . (int) substr($search, 3));
					}
					elseif (stripos($search, 'username:') === 0)
					{
						$search = $db->quote('%' . $db->escape(substr($search, 9), true) . '%');
						$queryselect->where('a.username LIKE ' . $search);
					}
					else
					{
						// Escape the search token.
						$search = $db->quote('%' . str_replace(' ', '%', $db->escape(trim($search), true) . '%'));

						// Compile the different search clauses.
						$searches   = array();
						$searches[] = 'a.name LIKE ' . $search;
						$searches[] = 'a.username LIKE ' . $search;
						$searches[] = 'a.email LIKE ' . $search;

						// Add the clauses to the query.
						$queryselect->where('(' . implode(' OR ', $searches) . ')');
					}
				}

				return $queryselect;
			}
		}

		$query = $db->getQuery(true);

		// Select the required fields from the table.
		$query
			->select(
				$this->getState(
					'list.select', 'DISTINCT a.*,b.id AS su_id, c.id AS client_id, c.title, b.created_by,r.name as role_title,r.id as roleId,cluster.id as clusterId, job.dpelead as dpelead'.$jobtitleQuery
				)
			);

		$query->from($db->qn('#__users', 'a'));

		$query->join('INNER', $db->qn('#__tjsu_users', 'b') .
		' ON (' . $db->qn('a.id') . ' = ' . $db->qn('b.user_id') . ' AND ' . $db->qn('b.client') . ' = "com_multiagency" )');

		$query->join('INNER', $db->qn('#__tjmultiagency_multiagency', 'c') . ' ON (' . $db->qn('b.client_id') . ' = ' . $db->qn('c.id') . ')');
		$query->join('INNER', $db->qn('#__tj_clusters', 'cluster') . ' ON (' . $db->qn('c.id') . ' = ' . $db->qn('cluster.client_id') . ')');
		$query->join('INNER', $db->qn('#__tjsu_roles', 'r') .
		' ON (' . $db->qn('r.id') . ' = ' . $db->qn('b.role_id') . ' AND ' . $db->qn('r.state') . ' = 1 )');
		$query->leftJoin($db->quoteName('#__job_title_user_xref', 'job') . ' ON (' . $db->quoteName('job.user_id') . ' = ' . $db->quoteName('b.user_id') . ' AND ' . $db->quoteName('cluster.id') . ' = ' . $db->quoteName('job.cluster_id') .')');


		// if ($jobTitleFieldId)
		// {
		// 	$query->join('LEFT', $db->qn('#__tjfields_fields_value', 'tjfv') .
		// ' ON ('. $db->qn('tjfv.content_id') . ' = '.$db->qn('job.ucm_id').' AND ' . $db->qn('tjfv.field_id') . ' = '.$jobTitleFieldId. ')');	
		// }
		


		// Load plugin to remove school admin from school manager
		
		PluginHelper::importPlugin('system', 'dpe');
		$queryResult = Factory::getApplication()->triggerEvent('onStaffLoad', array($user->get('groups')));

		if ($queryResult)
		{
			// Comment lagacy code
			// $query->where($queryResult[0]);
		}

		$query->where($db->qn('a.block') . ' = 0');
		$memberRole           = $params->get('member_role_id', '0', 'INT');
		$leadConsultantRoleId = $params->get('organization_lead_consultant_role_id', '0', 'INT');

		/*
		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_multiagency/models');
		$MultiagencyModel = BaseDatabaseModel::getInstance('Multiagency', 'MultiagencyModel', array('ignore_request' => true));
		$agenciesList     = $MultiagencyModel->getAllocatedAgencies($uid, array($memberRole, $leadConsultantRoleId));
		*/

		// Load clusters

		$clusterIds       = array();
		$clusterUserModel = ClusterFactory::model('ClusterUser', array('ignore_request' => true));
		$clusterList      = $clusterUserModel->getUsersClusters($user->id);

		$allocatedAgency = array();

		if (count($clusterList) > 0)
		{
			/*
			foreach ($agenciesList as $agency)
			{
				$allocatedAgency[] = $agency->client_id;
			}
			*/

			// Check clusters where user is org admin and used to show users of own org on users list view

			foreach ($clusterList as $cluster)
			{
				$viewUsers = true;

				// Check user have permission to manage all clusters
				if (!$user->authorise('core.manageall', 'com_cluster'))
				{
					$coreRoleId = RBACL::getCoreRoleByUser($user->id, 'com_cluster', $cluster->cluster_id);

					if (!in_array($orgAdminRoleId, $coreRoleId))
					{
						$viewUsers = false;
					}
				}

				if ($viewUsers)
				{
					$allocatedAgency[] = $cluster->cluster_id;
				}
			}

			// Default set agency filter for school manager and school admin
			if ((empty($agencies) || !(int) ($agencies)) && !$user->authorise('core.manageall', 'com_cluster'))
			{
				$agencies = $clusterList[0]->cluster_id;
				$agencyId = $clusterList[0]->client_id;
			}
		}

			$agencyTags = $this->getState('filter.tags');
			
			if (is_array($agencyTags))
				{
					foreach($agencyTags as $key => $agencyTag)
					{

						if (!is_int($agencyTag))
						{
							$agencyTags[$key] = (int) $agencyTag;
						}
					}
				 }
		
			// DPE Hack 
			
			if ($agencyTags && ($agencies == 'all') && $user->authorise('core.manageall', 'com_cluster'))
			{	
				BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_dpe/models', 'DpeModel');
				$dashBoardModel = BaseDatabaseModel::getInstance('Dashboard', 'DpeModel');

				$agencies = $dashBoardModel->getClusterIdsByTags($agencyTags);
			}
			
			// Dpe Hack end		
		
		if (!empty($agencies) && in_array($agencies, $allocatedAgency))
		{
			if (is_array($agencies))
			{
				$query->where('cluster.id IN (' .  implode(',', $agencies) . ')');
			}
			else
			{
				$query->where('cluster.id = ' . (INT) $agencies);
			}

			if (!$user->authorise('core.manageall', 'com_cluster'))
			{
				$userFormModel = BaseDatabaseModel::getInstance('UserForm', 'MultiagencyModel', array('ignore_request' => true));

				$roles = $userFormModel->getUserAgencyRole((int) $agencyId);

				if (!empty($roles))
				{
					$allowRoles = array_column($roles, 'role_id');

					// Manager can see Admin in list but cant edit them

					// Commented, needed for ref $query->where("b.role_id  IN ('" . implode("','", $allowRoles) . "')");

					// Dont show Trustee Users other than who have access to all cluster

					/*
					if ($trusteeRoleId && !in_array($orgAdminRoleId, $allowRoles))
					{
						$query->where($db->quoteName('b.role_id') . ' != ' . $db->quote((int) $trusteeRoleId));
					}
					*/
				}
			}
		}
		elseif (!empty($agencies) && (int) $agencies && $user->authorise('core.manageall', 'com_cluster'))
		{

			if (is_array($agencies))
			{
				$query->where('cluster.id IN ( ' . implode(",", $agencies) .')');
			}
			else
			{
				$query->where('cluster.id = ' . (INT) $agencies );
			}
			
		}
		else
		{
			$query->where("cluster.id  IN ('" . implode("','", $allocatedAgency) . "')");
		}

		$roleId = $this->getState('filter.role_id');

		if (!empty($roleId))
		{
			$query->where($db->qn('b.role_id') . ' = ' . (int) $roleId);
		}

		if (!empty($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where('a.id = ' . (int) substr($search, 3));
			}
			elseif (stripos($search, 'username:') === 0)
			{
				$search = $db->quote('%' . $db->escape(substr($search, 9), true) . '%');
				$query->where('a.username LIKE ' . $search);
			}
			elseif (stripos($search, 'title:') === 0)
			{
				$search = $db->quote('%' . $db->escape(substr($search, 9), true) . '%');
				$query->where('c.title LIKE ' . $search);
			}
			else
			{
				// Escape the search token.
				$search = $db->quote('%' . str_replace(' ', '%', $db->escape(trim($search), true) . '%'));

				// Compile the different search clauses.
				$searches   = array();
				$searches[] = 'a.name LIKE ' . $search;
				$searches[] = 'a.username LIKE ' . $search;
				$searches[] = 'a.email LIKE ' . $search;
				$searches[] = 'c.title LIKE ' . $search;

				// Add the clauses to the query.
				$query->where('(' . implode(' OR ', $searches) . ')');
			}
		}

		// DPE  hack for dpe lead 

		if ($this->getState('filter.dpelead') == 'dpelead')
		{
			$query->where($db->qn('job.dpelead') . ' = 1 AND ' .$db->qn('b.role_id') .' = '. $orgAdminRoleId );
		}

		// DPE Hack Start
		// SLA Filter
		$slaFilter = $this->getState('filter.sla_filter');

		if ($slaFilter && in_array($slaFilter, ['active', 'inactive']))
		{
			$currentDate = Factory::getDate('now', 'UTC')->toSql();

			$subQuery = $db->getQuery(true)
				->select('DISTINCT l.multiagency_id')
				->from('#__tjmultiagency_licences AS l')
				->where('l.state = 1')
				->where('l.start_date <= ' . $db->quote($currentDate))
				->where('l.end_date >= ' . $db->quote($currentDate));

			if ($slaFilter === 'active')
			{
				$query->where('c.id IN (' . $subQuery . ')');
			}
			else
			{
				$query->where('c.id NOT IN (' . $subQuery . ')');
			}
		}
		// DPE Hack End

		// Add the list ordering clause.
		$orderCol  = $this->state->get('list.ordering');
		$orderDirn = $this->state->get('list.direction');

		if ($orderCol && $orderDirn)
		{
			$query->order($db->escape($orderCol . ' ' . $orderDirn));
		}

		// DPE Hack Start
		$cluster = $this->getState('filter.agencies');
		$app  = Factory::getApplication();

		$limit = $this->getState('list.limit');



		// DPE Hack Start
		// Allow limit=0 (All) if any filter (Search, Cluster, Tags) is active.
		$search = $this->getState('filter.search');
		$hasSearch = !empty($search);
		$hasTags = (is_array($agencyTags) && count($agencyTags) > 0);
		$hasCluster = ($cluster != 'all' && $cluster != '');

		if ($limit == 0)
		{
			if ($hasSearch || $hasTags || $hasCluster)
			{
				$limit = 0;
			}
			else
			{
				$limit = $app->get('list_limit', 0);
			}
		}

		$this->setState('list.limit', $limit);
		// DPE Hack end

		return $query;
	}

	/**
	 * Method to get the total number of items for the data set.
	 *
	 * @return  integer  The total number of items available in the data set.
	 *
	 * @since   __DEPLOY__VERSION__
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
			$query->clear('select')->clear('order')->clear('limit')->clear('offset')->select('COUNT(a.id)');
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
	 * Method to get an array of data items
	 *
	 * @return  mixed An array of data on success, false on failure.
	 */
	public function getItems()
	{
		$items = parent::getItems();
		$params = ComponentHelper::getParams('com_multiagency');

		BaseDatabaseModel::addIncludePath(JPATH_ROOT . '/components/com_dpe/models');
		$userModel = BaseDatabaseModel::getInstance('Users', 'DpeModel');
		// Get JOB Title

		foreach($items as $key => $item)
		{

			$jobtitle = $userModel->getJobTitle($item->id, $item->clusterId);
			$items[$key]->jobtitle = $jobtitle[0];
		}	

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

}
