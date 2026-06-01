<?php
/**
 * @version    CVS: 1.0.0
 * @package    Com_Dpe
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2017 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Factory;
use Joomla\Data\DataObject;
use Joomla\CMS\Table\Table;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
jimport('joomla.application.component.modellist');
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\User\UserHelper;
use Joomla\CMS\Authentication\Authentication;
use Joomla\Registry\Registry;



JLoader::import('components.com_subusers.includes.rbacl', JPATH_ADMINISTRATOR);

/**
 * Methods supporting a list of Subusers records.
 *
 * @since  1.6
 */
class DpeModelUsers extends ListModel
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
				'id', 'a.id',
				'user_id', 'b.user_id',
				'email', 'a.email',
				'username', 'a.username',
				'rolname', 'r.name',
				'name', 'a.name',
				'agencies','c.id','c.title',
				'rolelist'
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
	 * @since    1.6
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		$app  = Factory::getApplication();
		$this->params = $app->getParams('com_multiagency');
		$list = $app->getUserState($this->context . '.list');
		$ordering  = isset($list['filter_order'])     ? $list['filter_order']     : null;
		$direction = isset($list['filter_order_Dir']) ? $list['filter_order_Dir'] : null;
		$user  = Factory::getUser();

		// List state information.
		parent::populateState($ordering, $direction);

		// Load the filter state.
		$search = $app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		$orderCol = $app->input->get('filter_order', 'a.name');

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
		$limit = $app->getUserStateFromRequest($this->context . '.list.limit', 'limit', $app->get('list_limit'));

		$this->setState('list.limit', "");
		$this->setState('list.start', $start);
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return   DataObjectbaseQuery
	 *
	 * @since   1.6
	 */
	protected function getListQuery()
	{
		// If Super user is logged then show organization wise user
		$jinput        = Factory::getApplication()->input;
		$user          = Factory::getUser();
		$params        = ComponentHelper::getParams('com_multiagency');
		$trusteeRoleId = (int) $params->get('organization_trustee_role_id');

		$lessonId = $jinput->getInt('element_id');
		$db    = $this->getDbo();
		$query = $db->getQuery(true);

		// Select the required fields from the table.
		$query
		->select(
			$this->getState(
				'list.select', 'DISTINCT a.*,cluster.id as clusterId,b.id AS su_id, c.id AS client_id, c.title, b.created_by,r.name as role_title'
			)
		);

		if ($jinput->get('layout', '', 'string') == "users")
		{
			$query
			->select(
				$this->getState(
					'list.select', 'jt.due_date AS todo_due_date'
				)
			);
		}

		$query->from($db->qn('#__users', 'a'));
		$query->join('INNER', $db->qn('#__tjsu_users', 'b') . ' ON (' . $db->qn('a.id') . ' = ' . $db->qn('b.user_id') . ')');
		$query->join('INNER', $db->qn('#__tjmultiagency_multiagency', 'c') . ' ON (' . $db->qn('b.client_id') . ' = ' . $db->qn('c.id')
			. ' AND ' . $db->qn('b.client') . " = 'com_multiagency' )");
		$query->join('INNER', $db->qn('#__tjsu_roles', 'r') . ' ON (' . $db->qn('r.id') . ' = ' . $db->qn('b.role_id') . ')');
		$query->where($db->qn('a.block') . ' = 0');

		// Filter by search in organisation name
		$mainframe = Factory::getApplication();
		$search = $this->getState('filter.search');

		// Get Search Role
		$role = $this->getState('filter.rolelist');

		// To get cluster id
		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpe/tables');
		$table = Table::getInstance('TjlmsClusterXref', 'DpeTable');
		$table->load(array('lesson_id' => $lessonId));

		// Add Join with cluster to get associated users
		$query->join('INNER', $db->qn('#__tj_clusters', 'cluster') . ' ON (' . $db->qn('cluster.client_id') . ' = ' . $db->qn('c.id') . ')');
		$query->where($db->qn('cluster.id') . ' = ' . $table->cluster_id);

		// To check user assigned role
		if (!empty($role))
		{
			$query->where($db->quoteName('b.role_id') . ' = ' . $db->quote((int) $role));
		}

		// Dont show Trustee Users on document assignment

		/*
		if ($trusteeRoleId)
		{
			$query->where($db->quoteName('b.role_id') . ' != ' . $db->quote((int) $trusteeRoleId));
		}
		*/

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

		// Get the model
		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_jlike/models');
		$jlikeModelRecommendations = BaseDatabaseModel::getInstance('recommendations', 'JlikeModel', array('ignore_request' => true));

		JLoader::import('contentform', JPATH_SITE . '/components/com_jlike/models');

		$contentData = array();

		$contentData['element'] = 'com_tjlms.lesson';
		$contentData['url'] = 'index.php?option=com_tjlms&view=lesson&lesson_id=' . $lessonId;
		$contentData['element_id'] = $jinput->getInt('element_id');
		$contentData['title'] = $jinput->get('title', '', 'STR');

		$contentId = JlikeModelContentForm::getContentID($contentData);

		if ($contentId)
		{
			$jlikeModelRecommendations->setState("content_id", $contentId);
			$jlikeModelRecommendations->setState("filter.client", 'com_tjlms.lesson');
			$jlikeModelRecommendations->setState("type", 'assign');

			$assignedUsers = $jlikeModelRecommendations->getItems();

			// If no user assigned then don't execute query for deassign feature
			if ($jinput->get('layout', '', 'string') == "users" && empty($assignedUsers))
			{
				return false;
			}

			foreach ($assignedUsers as $assignedUser)
			{
				$assignedUsersIds[] = $assignedUser->assigned_to;
			}

			if (!empty($assignedUsers))
			{
				if ($jinput->get('layout', '', 'string') == "users")
				{
					$query->join('LEFT', $db->quoteName('#__jlike_todos', 'jt') . ' ON ' . $db->quoteName('a.id') . ' = ' . $db->quoteName('jt.assigned_to'));
					$query->where($db->quoteName('jt.content_id') . ' = ' . $db->quote($contentId));
					$query->where($db->quoteName('jt.type') . ' = ' . $db->quote('assign'));

					$query->where($db->qn('a.id') . 'IN (' . implode(',', $db->q($assignedUsersIds)) . ')');
				}
				else
				{
					$query->where($db->qn('a.id') . 'NOT IN (' . implode(',', $db->q($assignedUsersIds)) . ')');
				}
			}
		}

		// Add the list ordering clause.
		$orderCol  = $this->state->get('list.ordering');
		$orderDirn = $this->state->get('list.direction');

		if ($orderCol && $orderDirn)
		{
			$query->order($db->escape($orderCol . ' ' . $orderDirn));
		}

		$query->group($db->quoteName('b.user_id'));
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
	 * Method to get an array of data items
	 *
	 * @param   array   $userIds      Selected user ids
	 * @param   object  $currentUser  loggedin user
	 *
	 * @return  mixed An array of data on success, false on failure.
	 */
	public function getAgencyUserIds($userIds, $currentUser)
	{
		$params = ComponentHelper::getParams('com_multiagency');
		$memberRole = $params->get('member_role_id', '0', 'INT');
		$adminRole = $params->get('multyagency_admin_role_id', '0', 'INT');

		$db    = $this->getDbo();
		$query = $db->getQuery(true);

		$query->select('DISTINCT b.user_id');
		$query->from($db->qn('#__tjmultiagency_multiagency') . ' AS ml');

		$query->join('INNER', $db->qn('#__tjsu_users', 'su') . ' ON (' . $db->qn('su.client_id') . ' = ' . $db->qn('ml.id')
			. ' AND ' . $db->qn('su.client') . " = 'com_multiagency' )");

		$query->join('INNER', $db->qn('#__tjsu_users', 'b') . ' ON (' . $db->qn('b.client_id') . ' = ' . $db->qn('ml.id')
			. ' AND ' . $db->qn('b.client') . " = 'com_multiagency' )");

		$query->join('INNER', $db->qn('#__tjmultiagency_multiagency', 'c') . ' ON (' . $db->qn('b.client_id') . ' = ' . $db->quoteName('c.id') . ')');
		$query->join('INNER', $db->qn('#__users', 'u') . ' ON (' . $db->qn('u.id') . ' = ' . $db->qn('b.user_id') . ')');

		$currentUserSuperUser = $currentUser->authorise('core.admin');

		if (!$currentUserSuperUser)
		{
			// Check User role
			JLoader::import("/components/com_subusers/includes/rbacl", JPATH_ADMINISTRATOR);
			$dpeAdmin = RBACL::getRoleByUser($currentUser->id, 'com_multiagency', 0);

			if (!in_array($adminRole, $dpeAdmin))
			{
				$query->where($db->qn('su.user_id') . ' = ' . $db->q($currentUser->id));
			}
		}

		$query->where($db->qn('su.client') . ' LIKE ' . $db->q('%' . $db->escape('com_multiagency', true) . '%'));
		$query->where($db->qn('ml.state') . ' =  1 AND ' . $db->qn('su.role_id') . ' NOT IN (' . $db->q($memberRole) . ')');
		$query->where($db->qn('u.block') . ' = 0 AND ' . $db->qn('b.user_id') . ' IN (' . implode(',', $userIds) . ')');
		$query->order($db->qn('b.user_id') . ' ASC');

		$db->setQuery($query);

		$assignUser = $db->loadColumn();

		$userIds = array_intersect($userIds, $assignUser);

		return $userIds;
	}

	/**
	 * Method to delete users from document
	 *
	 * @param   array  $userIds   Selected user ids
	 * @param   int    $lessonId  lesson id
	 *
	 * @return  mixed An array of data on success, false on failure.
	 */
	public function deassign($userIds,$lessonId)
	{
		
		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_jlike/tables');
		$jlikeContentTable = Table::getInstance('Content', 'JlikeTable');
		$jlikeContentTable->load(array('element_id' => $lessonId, 'element' => 'com_tjlms.lesson'));

		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select('id');
		$query->from($db->quoteName('#__jlike_todos'));
		$query->where($db->quoteName('content_id') . ' = ' . $db->quote($jlikeContentTable->id));
		$query->where($db->qn('assigned_to') . 'IN (' . implode(',', $db->q($userIds)) . ')');

		// Reset the query using our newly populated query object.
		$db->setQuery($query);

		// Load the results as a list of stdClass objects (see later for more options on retrieving data).
		$todoIds = $db->loadColumn();

		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_jlike/models');
		$recommendationModel = BaseDatabaseModel::getInstance('Recommendation', 'JlikeModel', array('ignore_request' => true));


		if ($recommendationModel->delete($todoIds))
		{
			// Delete user data from todo extended table
			BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_jlike/models', 'JlikeModel');
			$interactionModel = BaseDatabaseModel::getInstance('Interaction', 'JlikeModel', array());
			$interactionModel->delete($todoIds);

			// Delete user data from tjlms lesson track table
			Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjlms/tables');
			$lessonTrackTable = Table::getInstance('Lessontrack', 'TjlmsTable');
			
			foreach ($userIds as $userId)
			{
				$lessonTrackTable->load(array('lesson_id' => $lessonId, 'user_id' => $userId));

				if ($lessonTrackTable->id)
				{
					$lessonTrackTable->delete($lessonTrackTable->id);
				}

				// deassign the todo from todo_cluster_xref table

				PluginHelper::importPlugin('system');
				Factory::getApplication()->triggerEvent('onAfterDeassignUsers', array($todoIds));
			}

			return true;
		}
	}

	/**
	 * Function getUserSchoolRole to get user associated schools with role
	 *
	 * @param   STRING  $client  Component Name
	 *
	 * @param   INT     $userId  User Id
	 *
	 * @return  boolean
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function getUserSchoolRole($client = 'com_multiagency', $userId = null)
	{
		$userId = Factory::getuser($userId)->id;

		$db = Factory::getDbo();
		$query = $db->getQuery(true);

		$query->select('m.title , rl.name as rolename, rl.id as role_id, m.id as client_id');
		$query->from($db->qn('#__tjsu_users', 'a'));
		$query->join('INNER', $db->qn('#__tjsu_roles', 'rl') . ' ON (' . $db->qn('rl.id') . ' = ' . $db->qn('a.role_id') . ')');
		$query->join('INNER', $db->qn('#__tjmultiagency_multiagency', 'm') . ' ON (' . $db->qn('m.id') . ' = ' . $db->qn('a.client_id') . ')');

		$query->where($db->qn('a.user_id') . " = " . (int) $userId);
		$query->where($db->qn('a.client') . " = " . $db->q($client));
		$db->setQuery($query);

		return $db->loadObjectList();
	}

	/**
	 * Method to get Dpe Admins
	 *
	 * @return  void | array An array of data on success, false on failure.
	 */
	public function getDpeAdmins()
	{
		$dpeAdminGroup = Table::getInstance('Usergroup', 'JTable');
		$dpeAdminGroup->load(array('title' => 'DPE Admin'));

		if (property_exists($dpeAdminGroup, 'id'))
		{
			$dpeAdminGroupId = $dpeAdminGroup->id;
		}

		if (!$dpeAdminGroupId)
		{
			return;
		}

		// Get a db connection.
		$db = Factory::getDbo();

		// Create a new query object.
		$query = $db->getQuery(true);

		// Select all records from the user which have DPE Admin group
		$query->select(array('u.id'));
		$query->from('`#__users` AS u');
		$query->join('LEFT', $db->quoteName('#__user_usergroup_map', 'map') . ' ON ' . $db->quoteName('u.id') . ' = ' . $db->quoteName('map.user_id'));
		$query->where($db->qn('map.group_id') . ' = ' . (int) $dpeAdminGroupId);

		$query->where('u.block = 0');
		$query->group($db->quoteName('u.id'));
		$query->order($db->quoteName('u.name') . ' ASC');

		// Reset the query using our newly populated query object.
		$db->setQuery($query);

		return $db->loadColumn();
	}

	/**
	 * Function to get the leadConsultant filter
	 *
	 * @return  array
	 *
	 * @since __DEPLOY_VERSION__
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

		if (!$user->authorise('core.manageall', 'com_cluster'))
		{
			return $options;
		}

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

		if (!empty($lcList))
		{
			$options[] = HTMLHelper::_('select.options', '');
		}

		return $options;
	}

	/**
	 * Function is used to get the users active licence clusters 
	 *
	 * @param   Integer  $userId  User Id
	 * 
	 * @return  array
	 *
	 * @since  1.0.0
	 */
	public function getUsersActiveLicenceClusters($userId)
	{
		$clusterUserModel = ClusterFactory::model('ClusterUser', array('ignore_request' => true));
		$clusters         = $clusterUserModel->getUsersClusters($userId);
		$adminClusters    = array();
		$staffClusters    = array();
		$orgAdmin         = ComponentHelper::getParams('com_multiagency')->get('school_admin_role_id');

		foreach ($clusters as $cluster)
		{
			$coreRoleId = RBACL::getCoreRoleByUser($userId, 'com_cluster', $cluster->cluster_id);

			// Check user is admin of org
			if (in_array($orgAdmin, $coreRoleId))
			{
				$adminClusters[] = $cluster->cluster_id;
			}
			else
			{
				$staffClusters[] = $cluster->cluster_id;
			}
		}

		$adminClusters = array_filter($adminClusters);
		$staffClusters = array_filter($staffClusters);

		return array(
			'adminClusters' => $adminClusters,
			'staffClusters' => $staffClusters
		);
	}

	/**
	 * Function is used to get the user id for todo 
	 *
	 * @param   Integer  $courseId   course Id
	 * 
	 * @param   Integer  $cstatus    cstatus 
	 * 
	 * @param   Integer  $clusterId  clusterId
	 * 
	 * @return  array
	 *
	 * @since  1.0.0
	 */
	public function getAgencyUserId($courseId, $cstatus = null, $clusterId = '')
	{
		if (empty($clusterId) || empty($courseId) || empty($cstatus))
		{
			$returnData = array();
			$returnData['msg'] = Text::_('PLG_CONTENT_JLIKE_MULTIAGENCY_FIELD_COURSE_STATUS_ERROR');

			return $returnData;
		}

		$db = Factory::getDbo();
		$user    = Factory::getUser();
		$query = $db->getQuery(true);

		// Must have columns to get details of non linked data like completion
		$query->select(array('u.id as user_id', 'tjc.name as school_name', 'tjc.client_id as schoolId'));
		$query->from('#__users AS u');

		$enrolledStatusJoin = $db->quoteName('eus.course_id') . ' = ' . (int) $courseId;

		$query->join('LEFT', $db->qn('#__tjlms_enrolled_users', 'eu') . ' ON (' .
			$db->qn('u.id') . ' = ' . $db->qn('eu.user_id')
			. ' AND ' . $db->qn('eu.course_id') . ' = ' . $courseId . ' ) ');

			// Get enrolled status of selected course
		$enrolledStatusJoin = $db->quoteName('eus.course_id') . ' = ' . (int) $courseId;
		$query->join('LEFT', $db->qn('#__tjlms_enrolled_users', 'eus')
			. ' ON (' . $db->qn('u.id') . ' = ' . $db->qn('eus.user_id') . ') AND ' . $enrolledStatusJoin
		);

			// $enrolledCourseStatusJoin = $db->quoteName('cst.course_id') . ' = ' . (int) $courseId;

		$query->join('INNER', $db->qn('#__tjlms_course_track', 'cst') . ' ON (' . $db->qn('cst.course_id') .
			' = ' . $db->qn('eus.course_id') . ' AND ' . $db->qn('cst.user_id') . ' = ' . $db->qn('eu.user_id')
			. ' AND ' . $db->qn('cst.course_id') . ' = ' . $courseId . ' ) ');

		$query->join('LEFT', $db->qn('#__tjlms_courses', 'c') . ' ON (' . $db->qn('c.id') . ' = ' .
			$db->qn('eu.course_id') . ')');

		if (isset($cstatus) && !empty($cstatus))
		{
			if ($cstatus == "I")
			{
				$query->where("(cst.status= '' OR cst.status IS NULL OR cst.status= 'I')");
			}
			elseif ($cstatus == "C")
			{
				$query->where("cst.status='C'");
			}
		}

		$query->join('INNER', $db->qn('#__tj_cluster_nodes', 'tjcn') . ' ON (' .
			$db->qn('u.id') . ' = ' . $db->qn('tjcn.user_id') . ')');
		$query->join('INNER', $db->qn('#__tj_clusters', 'tjc') . ' ON (' .
			$db->qn('tjcn.cluster_id') . ' = ' . $db->qn('tjc.id') . ')');

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
						// Load data for org's who have elearing access
						if (RBACL::check($user->id, 'com_cluster', 'core.viewShika', 'com_tjlms', $clusterList->value))
						{
							$usersClusters[] = $clusterList->value;
						}
					}
				}
			}
		}

		$query->where($db->qn('u.block') . ' = 0');
		$query->where($db->qn('tjc.id') . " IN ('" . $clusterId . "')");
		$db->setQuery($query);
		$userList = $db->loadObjectList();

		return $userList;
	}

	// DPE HACK FOR GET COUNT INCOMPLETE DATA

/**
 * Return todo data for count incomplete todo .
 *
 * @param    Integer $status is used to get cluster status data
 * 
 * @param    Integer $assigned_to is used to get        user id
 * 
 * @param    Integer $status is used to get          cluster id
 * 
 * @return    array of data 
 */
public function getTodoCount($status,$assigned_to,$cluster)
{
	$db    = $this->getDbo();
	$query = $db->getQuery(true);

			// Select the required fields from the table.
	$query->select('DISTINCT a.*');
	$query->from('`#__jlike_todos` AS a');

			// Join over the users for the checked out user.
	$query->select('uc.name AS editor');
	$query->join('LEFT', '#__users AS uc ON uc.id=a.checked_out');
	$query->join('LEFT', '#__users AS users ON users.id = a.created_by');
	$query->select(array('clusters.id as cluster_id', 'clusters.name as agency_title'));
	$query->join('LEFT', $db->quoteName('#__jlike_todos_cluster_xref', 'todoxref')
		. ' ON (' . $db->qn('todoxref.todo_id') . ' = ' . $db->qn('a.id') . ')');
	$query->join('LEFT', $db->quoteName('#__tj_clusters', 'clusters')
		. ' ON (' . $db->qn('clusters.id') . ' = ' . $db->qn('todoxref.cluster_id') . ')');
	$query->join('LEFT', $db->quoteName('#__tjmultiagency_multiagency', 'tm')
		. ' ON (' . $db->qn('tm.id') . ' = ' . $db->qn('clusters.client_id') . ')');
	$query->where('tm.state = 1');
	$query->where('clusters.state = 1');

	if ($cluster)
	{
		$query->where(($db->qn('clusters.id') . ' IN (' . $cluster . ')'));
	}

	if ($assigned_to)
	{
		$query->where('a.assigned_to = ' . $db->quote($assigned_to));
	}

	if ($status && $status != "O")
	{
		$query->where('a.status = ' . $db->quote($status));
	}

	$query->order('a.due_date desc');

	$db->setQuery($query);
	$data = $db->loadObjectList();

	return $data;
}
		// Dpe Hack end for todo data

	/**
	 * check Job title data if assigned to any user.
	 *
	 * @param    Integer ucmId is used to get job title relation with user
	 * 
	 * @return    boolean true
	 */
	public function checkUserForJobTitle($ucmId)
	{ 
		if (!$ucmId)
		{
			return false;
		}

		Table::addIncludePath(JPATH_SITE . '/components/com_dpe/tables');
		$tjlmsClusterXrefTable = Table::getInstance('JobtitleExtend', 'DpeTable');
		$jobTitleDetails       = $tjlmsClusterXrefTable->load(array('ucm_id'=>$ucmId));

		if ($tjlmsClusterXrefTable->id)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Get the todo ids of the content id and user id from todo table
	 * 
	 * @param    Integer contentId is content Id of the document
	 * 
	 * @param    Integer userId is user Id
	 * 
	 * @return    boolean true
	 */
	public function getTodoIdsBycontentId($contentId,$userId)
	{		

		if(!$contentId || !$userId)
		{
			return false;
		}

		$db        = Factory::getDbo();
		$query     = $db->getQuery(true);

		$query->select($db->quoteName(array('id', 'sender_msg')))->from($db->qn( '#__jlike_todos'));
		$query->where($db->qn('content_id') . ' = ' . (int) $contentId);
		$query->where($db->qn('assigned_to') . ' = ' . (int) $userId);

		$db->setQuery($query);
		return $db->loadAssocList();

	}

	/**
	 * Function to get field value of user
	 *
	 * @param   INT  $itemId     Id of ucm content
	 * 
	 * @param   INT  $clusterId  cluster id
	 *
	 * @return  array|boolean
	 */
	public function getFieldValues($fieldId, $contentId)
	{
		
		if (!$fieldId || !$contentId)
		{
			return false;
		}
		$db    = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select($db->qn('value'));
		$query->from($db->qn('#__tjfields_fields_value', 'fv'));
		$query->where($db->qn('fv.field_id') . ' = ' . (int) $fieldId);
		$query->where($db->qn('fv.content_id') . ' = ' . (int) $contentId);

		$db->setQuery($query);

		return $db->loadAssocList();
	}

	/**
	 * Function to get field id by contentid
	 *
	 * @param   INT  $contentId     Id of ucm content
	 * 
	 * @return  array|boolean
	 */

	public function getFieldsValueBycontentId($contentId)
	{
		$db        = Factory::getDbo();
		$query     = $db->getQuery(true);

		$query->select('field_id')->from($db->qn( '#__tjfields_fields_value'));
		$query->where($db->qn('content_id') . ' = ' . (int) $contentId);

		$db->setQuery($query);
		return $db->loadColumn();
	}

	/**
	 * Function to get jobtitle by userid and contentid
	 *
	 * @param   INT  $userId     Id of user
	 * 
 	 * @param   INT  $clusterId  cluster id
 	 * 
	 * @return  array
	 */
	public function getJobTitle($userId, $clusterId)
	{
		$dpeParam         = ComponentHelper::getParams('com_dpe');

		$jobTitleFieldId = $dpeParam->get('jobtitle', '0', 'INT');

		$db        = Factory::getDbo();
		$query     = $db->getQuery(true);

		$query->select('tjfv.value')->from($db->qn( '#__job_title_user_xref', 'job'));

		$query->join('LEFT', $db->qn('#__tjfields_fields_value', 'tjfv') .
			' ON ('. $db->qn('tjfv.content_id') . ' = '.$db->qn('job.ucm_id').' AND ' . $db->qn('tjfv.field_id') . ' = '.$jobTitleFieldId. ')');	

		$query->where($db->qn('job.user_id') . ' = ' . (int) $userId);
		$query->where($db->qn('job.cluster_id') . ' = ' . (int) $clusterId);

		$db->setQuery($query);
		return $db->loadColumn();
	}

	//DPE Onboard start
	/**
	 * Method to save the default set
	 *
	 * @param Array $data 
	 * 
	 * @return  Array
	 */
	public function saveonboardusers($data)
	{
		if (empty($data)) {
			return false;
		}

		Table::addIncludePath(JPATH_SITE . '/components/com_dpe/tables');
		$onboarduserdata = Table::getInstance('OnboardXref', 'DpeTable');

		if (!$onboarduserdata) {
			return false;
		}

		$onboarduserdataID = ($data['onboardsetId']) ? array('id' => $data['onboardsetId']) : array('type_of_set'=>'jobtitleset','ucmid'=>$data['ucmrecordId']);

		$onboarduserdata->load($onboarduserdataID);
		$user  = Factory::getUser();
		
		// $data['clusterId'] = $data['clusterId'];
		$data['created_by'] = $user->id;
		$data['onboardtitle']  = ($data['jobroletitle'])?$data['jobroletitle']:$data['onboardtitle'];
		$data['onboardtitle'] = ($data['jobroletitle']) ? $data['jobroletitle']: (str_contains($data['onboardtitle'], '_default set') ? $data['onboardtitle'] : $data['onboardtitle'] . "_default set");

		$formdata = json_encode($data);
		$ucmid = $data['ucmrecordId'];
		$date = Factory::getDate();
		$modifiedDate = $date->format('d/m/Y H:i:s');
		$createdDate = $date->format('Y-m-d H:i:s');
		$data['start_date'] = Factory::getDate($data['start_date'])->format('Y-m-d');

		if (!$onboarduserdata->id) {

			try {

				if (!$onboarduserdata->save(array('title'=> $data['onboardtitle'],'formdata'=>$formdata, 'ucmid'=>$ucmid,'modified_date'=>$modifiedDate,'type_of_set'=>$data['type_of_set'],'cluster_id'=>$data['clusterId'],'new_old_default_set'=>1,'created_date' => $createdDate,'start_date'=>$data['start_date']))) {

					$errors = $onboarduserdata->getErrors();
					foreach ($errors as $error) {
						echo $error . '<br>';
					}
					return false;
				}else {
					
					if ((($data['userassignmentstatus'] != 'nousers' ) || (!$data['start_date'])) && ($ucmid == ''))
					{
						PluginHelper::importPlugin('system', 'dpe');
						$queryResult = Factory::getApplication()->triggerEvent('onUserOnboardNotificationToUsers', array('formdata'=>$formdata));
					}

					return true;
				}
			}catch (Exception $e) {
				Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
				echo "Caught exception: " . $e->getMessage();
			}


		}else
		{
			$onboarduserdatas = array('id'=>$onboarduserdataID->onboardsetId, 'title'=> $data['onboardtitle'],'formdata'=>$formdata, 'ucmid'=>$ucmid,'modified_date'=>$modifiedDate,'new_old_default_set'=>2);

			if($onboarduserdata->save($onboarduserdatas))
			{
				if ($data['userassignmentstatus'] != 'nousers' || !$data['start_date'] )
				{

				}

				return true;
			}
			else
			{
				return false;
			}
		}
	}

	/**
	 * Method to get all the default set by cluster ID
	 *
	 * @param INT $clusterId  default set cluster ID
	 * 
	 * @return  Array
	 */
	public function getAllDefaultSet($clusterId = NULL)
	{

		$db        = Factory::getDbo();
		$query     = $db->getQuery(true);

		$query->select('*')->from($db->quoteName('#__dpe_useronboard_xref'));
		$query->where($db->qn('cluster_id') . ' = ' . (int) $clusterId);
		$query->where($db->qn('type_of_set') . ' = "defaultset"');
		$query->where($db->qn('state') . ' = "1"');
		
		$db->setQuery($query); 
		return $db->loadAssocList();
	}

	/**
	 * Method to get all the Template set by cluster ID
	 *
	 * @param INT $clusterId  default set cluster ID
	 * 
	 * @return  Array
	 */
	public function getAllTododTemplateByCluster($clusterId = NULL)
	{

		$db        = Factory::getDbo();
		$query     = $db->getQuery(true);

		$query->select('*')->from($db->quoteName('#__onboard_todo_tmplate'));
		$query->where($db->qn('cluster_id') . ' = ' . (int) $clusterId);

		$db->setQuery($query); 
		return $db->loadAssocList();
	}
	/**
	 * Method to aget the default set by its ID
	 *
	 * @param INT $id  default set id
	 * 
	 * @return  Array
	 */

	public function getDefaultsetById($id)
	{

		if (!$id)
		{
			return false;
		}

		$db        = Factory::getDbo();
		$query     = $db->getQuery(true);

		$query->select('*')->from($db->quoteName('#__dpe_useronboard_xref'));
		$query->where($db->qn('id') . ' = ' . (int) $id);		
		$db->setQuery($query); 
		return $db->loadAssocList();
		
	}

	/**
	 * Method to assign the todos as per sefault set configurations.
	 *
	 * @param Array $data 
	 * 
	 * @return  bool
	 */
	public function onboardUsersTodoAssign($data)
	{
		$baseUrl = Uri::base();
		$config = Factory::getConfig();
		$siteUrl = $config->get('live_site');

		$typeOfSet= $data->type_of_set;
		$OnboardTitle = $data->onboardtitle;
		$courseAssignDetail = ($data->elearning_subform)?$data->elearning_subform:$data['elearning_subform'];
		$documentAssignDetail = ($data->document_subform)?$data->document_subform:$data['document_subform'];
		$todoAssignData		= ($data->todo_subform)?$data->todo_subform:$data['todo_subform'];
		$userAssignmentType = ($data->userassignmentstatus)?$data->userassignmentstatus:'';
		$specificUserToAssign = ($data->selectuseforonboarding)?$data->selectuseforonboarding:'';
		$token = ComponentHelper::getParams('com_dpe')->get('private_key_storage_cron');
		
		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjlms/tables');
		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_jlike/tables');
		JLoader::import('components.com_jlike.includes.jlike', JPATH_SITE);
		JLoader::import('components.com_jlike.models.recommendationform', JPATH_SITE);

		if($specificUserToAssign)
		{
			$data->selectuseforonboarding = $specificUserToAssign;
			$data->selectuseforonboarding = str_replace("'", '"', $data->selectuseforonboarding);

			$data->selectuseforonboarding = $data->selectuseforonboarding;	
		}

		if($data->userassignmentstatus == 'allusers')
		{

			$allUsers = $this->getUsersByClusterId($data->clusterId);
		}

		

//1st Elearning Todo Assign
		if (!empty($courseAssignDetail))
		{
			foreach($courseAssignDetail as $coursedetail)
			{

				$courseId = ($coursedetail->coursefilter)?$coursedetail->coursefilter:$coursedetail['coursefilter'];
				$courseDueDay = !empty($coursedetail->coursecompletionday) ? $coursedetail->coursecompletionday 
				: (is_array($coursedetail) && isset($coursedetail['coursecompletionday']) 
					? $coursedetail['coursecompletionday'] 
					: '');

				$currentDate = new DateTime();
				$currentDate->modify('+'.$courseDueDay.' days');
				$dueDate = $currentDate->format('Y-m-d H:i:s');

				$todayDate = new DateTime();
				$todayDate = $todayDate->format('Y-m-d H:i:s');


				$courseTable = Table::getInstance('course', 'TjlmsTable');
				$courseTable->load(array('id' => $courseId));

				$contentTable = Table::getInstance('content', 'JlikeTable');
				$contentTable->load(array('element_id' => $courseId,'element'=>'com_tjlms.course'));

				$contentData               = array();
				$contentData['element']    = 'com_tjlms.course';
				$contentData['url']        = '/index.php/component/tjlms/' . $courseTable->id;
				$contentData['title']      = $courseTable->title;

				if (!$contentTable->id)
				{
					$contentFormModel        = Jlike::model('Contentform', array('ignore_request' => true));
					$coursetodo['content_id'] = $contentFormModel->getContentID($contentData);
				}
				// Course Todo Data
				$coursetodo['clusters'] = ($data->clusterId)?$data->clusterId:$data['clusterId'];
				$coursetodo['title'] = 'Complete the Course ' . $courseTable->title;
				$coursetodo['due_date'] = $dueDate;
				$coursetodo['id'] = 0;
				$coursetodo['element'] = 'com_jlike.generic_todo';
				$coursetodo['element_id'] = 1;
				$coursetodo['content_id'] = $contentTable->id;
				$actualUrl = $siteUrl;
				$coursetodo['current_page_link'] = $actualUrl.'/index.php/component/tjlms/' . $courseTable->alias;
				$coursetodo['course_id'] = $courseId;
				$coursetodo['contentId'] = '';
				$coursetodo['all_cluster_users'] = ' ';
				$coursetodo['is_todo_specific'] = 1;
				$coursetodo['Course Name'] = $courseId;
				$coursetodo['Enroll status'] = '';
				$coursetodo['state'] = 1;
				$coursetodo['status'] = 'I';
				$coursetodo['cc_users'] = '';
				$coursetodo['context'] = ' ';
				$coursetodo['assigned_by'] = ($data->created_by)?$data->created_by:$data['created_by'];				
				$coursetodo['type'] = 'assign';
				$coursetodo['params'] = '{"current_page_link":"'.$actualUrl.'/index.php/component/tjlms/'.$courseTable->alias.'"}';
				$coursetodo['description'] = '';

				if ($data->userassignmentstatus == 'allusers')
				{
					foreach($allUsers as $assignedtoUsers)
					{
						$recommendationFormModel = BaseDatabaseModel::getInstance('RecommendationForm', 'JlikeModel', array('ignore_request' => true));
						$coursetodo['assigned_to'] = $assignedtoUsers->user_id;
						
						// Check Course is already assigned or certificate is expired or not.

						$courseEnrolledTable = Table::getInstance('Enrolledusers', 'TjlmsTable');
						$courseEnrolledTable->load(array('user_id'=>$assignedtoUsers->user_id,'course_id' => $courseId));

						if($courseEnrolledTable->id)
						{
							$courseCertificateStatus = $this->getCourseTrackByUserId($courseId, $assignedtoUsers->user_id);

							if(!($courseCertificateStatus->expired_on > $todayDate))
							{
								$coursResult = $recommendationFormModel->save($coursetodo);
							}
						}else
						{
							$coursResult = $recommendationFormModel->save($coursetodo);
						}						
					}		
				}
				else
				{
					foreach($data->selectuseforonboarding as $assignedtoUsers)
					{
						$recommendationFormModel = BaseDatabaseModel::getInstance('RecommendationForm', 'JlikeModel', array('ignore_request' => true));

						$coursetodo['assigned_to'] = $assignedtoUsers;

						$courseEnrolledTable = Table::getInstance('Enrolledusers', 'TjlmsTable');
						$courseEnrolledTable->load(array('user_id'=>$assignedtoUsers->user_id,'course_id' => $courseId));

						if($courseEnrolledTable->id)
						{
							$courseCertificateStatus = $this->getCourseTrackByUserId($courseId, $assignedtoUsers->user_id);

							if(!($courseCertificateStatus->expired_on > $todayDate))
							{
								$coursResult = $recommendationFormModel->save($coursetodo);
							}
						}else
						{
							$coursResult = $recommendationFormModel->save($coursetodo);
						}		
					}

				}

				if (is_array($data))
				{
					if($data['userid'])
					{
						$recommendationFormModel = BaseDatabaseModel::getInstance('RecommendationForm', 'JlikeModel', array('ignore_request' => true));

						$coursetodo['assigned_to'] = $data['userid'];
						$coursetodo['key'] = $token;
						$courseEnrolledTable = Table::getInstance('Enrolledusers', 'TjlmsTable');
						$courseEnrolledTable->load(array('user_id'=>$assignedtoUsers->user_id,'course_id' => $courseId));

						if($courseEnrolledTable->id)
						{
							$courseCertificateStatus = $this->getCourseTrackByUserId($courseId, $assignedtoUsers->user_id);

							if(!($courseCertificateStatus->expired_on > $todayDate))
							{
								$coursResult = $recommendationFormModel->save($coursetodo);
							}//
						}else
						{
							$coursResult = $recommendationFormModel->save($coursetodo);
						}		
					}
				}
				
			}
		}

		// Assign Documents

		if (!empty($documentAssignDetail))
		{
			foreach($documentAssignDetail as $documentDetail)
			{

				$lessonTable = Table::getInstance('lesson', 'TjlmsTable');
				$lessonIds = ($documentDetail->lessonfilter) ? 
				$documentDetail->lessonfilter : 
				((is_array($documentDetail) && isset($documentDetail['lessonfilter'])) ? 
					$documentDetail['lessonfilter'] : '');
				
				if(!$lessonIds)
				{
					continue;
				}
				
				$lessonTable->load(array('id' => $lessonIds));

				$contentTable = Table::getInstance('content', 'JlikeTable');
				$contentTable->load(array('element_id' => $lessonIds,'element'=>'com_tjlms.lesson'));

				$contentData               = array();
				$contentData['element']    = 'com_tjlms.lesson';
				$contentData['url']        = '/index.php/component/tjlms/' . $lessonTable->id;
				$contentData['title']      = $lessonTable->title;

				$documentTodo['content_id'] = $contentTable->id;
				
				if (!$contentTable->id)
				{
					$contentFormModel        = Jlike::model('Contentform', array('ignore_request' => true));
					$documentTodo['content_id'] = $contentFormModel->getContentID($contentData);
				}

				$lessonDueDay = ($documentDetail->lessonscompletionday)?$documentDetail->lessonscompletionday:(is_array($documentDetail)?$documentDetail['lessonscompletionday']:$documentDetail->lessonscompletionday);

				$currentDate = new DateTime();
				$createdDate = $currentDate->format('Y-m-d H:i:s');
				$currentDate->modify('+'.$lessonDueDay.' days');
				$dueDate = $currentDate->format('Y-m-d H:i:s');
				$documentTodo['clusterId'] = ($data->clusterId)?$data->clusterId:$data['clusterId'];

				$documentTodo['element'] = 'com_tjlms.lesson';
				$documentTodo['url'] = 'index.php?option=com_tjlms&view=lesson&lesson_id='.$lessonIds;
				$documentTodo['element_id'] = $lessonIds;
				$documentTodo['title'] = $lessonTable->title;
				$documentTodo['assigned_by'] = ($data->created_by)?$data->created_by:$data['created_by'];
				$documentTodo['type'] = 'assign';
				$documentTodo['start_date'] = $createdDate;
				$documentTodo['due_date'] = $dueDate;
				$documentTodo['created_date'] = $createdDate;
				$documentTodo['status'] = 'I';
				$documentTodo['state'] = '1';
				$documentTodo['created_by'] = ($data->created_by)?$data->created_by:$data['created_by'];
				$documentTodo['cc_users'] = '';
				$documentTodo['params'] = array('current_page_link' => 'index.php?option=com_tjlms&view=lesson&lesson_id=' . $lessonIds);
				$documentTodo['cc_users'] = '0';
				$documentTodo['description'] = '';

				if($data->userassignmentstatus == 'allusers')
				{
					foreach($allUsers as $assignedtoUsers)
					{
						$documentTodo['assigned_to'] = $assignedtoUsers->user_id;


						$jlikeModel = BaseDatabaseModel::getInstance('recommendation_child', 'DpeModel', array('ignore_request' => true));
						// Save the items.
						$result = $jlikeModel->setTodo($documentTodo, '1');
					}		
				}
				else
				{
					foreach($data->selectuseforonboarding as $assignedtoUsers)
					{
						$documentTodo['assigned_to'] = $assignedtoUsers;

						$jlikeModel = BaseDatabaseModel::getInstance('recommendation_child', 'DpeModel', array('ignore_request' => true));
						// Save the items.
						$result = $jlikeModel->setTodo($documentTodo, '1');
					}
				}
				if (is_array($data))
				{
					if(isset($data['userid']))
					{
						$documentTodo['assigned_to'] = $data['userid'];
						$documentTodo['key'] = $token;

						$jlikeModel = BaseDatabaseModel::getInstance('recommendation_child', 'DpeModel', array('ignore_request' => true));
					// Save the items.
						$documentResult = $jlikeModel->setTodo($documentTodo, 1);
					}
				}
				
			}
		}

// DPE TODO Assign
		if (!empty($todoAssignData))
		{
			foreach($todoAssignData as $todoData)
			{
				
				$todoDueDay      = ($todoData->todocompletionday)?$todoData->todocompletionday:(is_array($todoData)?$todoData['todocompletionday']:$todoData->todocompletionday);	
				$todoReminderDay = ($todoData->todoreminderday)?$todoData->todoreminderday:(is_array($todoData)?$todoData['todoreminderday']:$todoDat->todoreminderday);
				$currentDate = new DateTime();
				$currentDate->modify('+'.$todoDueDay.' days');
				$dueDate = $currentDate->format('Y-m-d H:i:s');

				$contentTable = Table::getInstance('content', 'JlikeTable');
				$contentTable->load(array('element_id' => '1','element'=>'com_jlike.generic_todo'));

				// Course Todo Data
				$generaltodo['clusters'] = ($data->clusterId)?$data->clusterId:$data['clusterId'];
				$generaltodo['title'] = ($todoData->todotitle) ? $todoData->todotitle : ((is_array($todoData) && isset($todoData['todotitle'])) ? $todoData['todotitle'] : '');

				$generaltodo['due_date'] = $dueDate;
				$generaltodo['id'] = 0;
				$generaltodo['element'] = 'com_jlike.generic_todo';
				$generaltodo['element_id'] = 1;
				$generaltodo['content_id'] = $contentTable->id;
				$generaltodo['current_page_link'] = null;
				// $generaltodo['course_id'] = $courseId;
				// $generaltodo['contentId'] = '';
				$generaltodo['all_cluster_users'] = ' ';
				$generaltodo['is_todo_specific'] = 1;
				$generaltodo['state'] = 1;
				$generaltodo['cc_users'] = '';
				$generaltodo['context'] = ' ';
				$generaltodo['assigned_by'] = ($data->created_by)?$data->created_by:$data['created_by'];
				$generaltodo['type'] = 'assign';
				$generaltodo['params'] = '';
				$generaltodo['sender_msg'] =  ($todoData->tododescription) ? $todoData->tododescription : ((is_array($todoData) && isset($todoData['tododescription'])) ? $todoData['tododescription'] : ''); 
				$generaltodo['reminder'] = Array
				(
					'reminder0' => Array
					(   'id' => '',
						'duration' => $todoReminderDay,
						'time_measure' => 'days'
					)

				);

				if ($data->userassignmentstatus == 'allusers')
				{
					foreach($allUsers as $assignedtoUsers)
					{
						$generaltodo['assigned_to'] = $assignedtoUsers->user_id;

						$recommendationFormModel = BaseDatabaseModel::getInstance('RecommendationForm', 'JlikeModel', array('ignore_request' => true));
						$todoResult = $recommendationFormModel->save($generaltodo);
					}		
				}
				else
				{
					foreach($data->selectuseforonboarding as $assignedtoUsers)
					{
						
						$generaltodo['assigned_to'] = $assignedtoUsers;

						$recommendationFormModel = BaseDatabaseModel::getInstance('RecommendationForm', 'JlikeModel', array('ignore_request' => true));
						$todoResult = $recommendationFormModel->save($generaltodo);
					}
				}
				if (is_array($data))
				{
					if(isset($data['userid']))
					{
						$generaltodo['assigned_to'] = $data['userid'];
						$generaltodo['key'] = $token;


						$recommendationFormModel = BaseDatabaseModel::getInstance('RecommendationForm', 'JlikeModel', array('ignore_request' => true));
						$todoResult = $recommendationFormModel->save($generaltodo);
					}
				}


			}
		}

		return true;
	}

	/**
	 * Method to get user list depending on the client chosen.
	 *
	 * @return   user list
	 *
	 * @since    1.0.0
	 */
	public function getUsersByClusterId($clusterId)
	{
		
		
		$app = Factory::getApplication();

		if (!$clusterId)
		{
			echo new JsonResponse(null, Text::_("COM_SLA_ACTIVITY_LICENSE_NOT_SELECTED"), true);
			$app->close();
		}
		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_cluster/tables');
		$clusterInstance = Table::getInstance('Clusters', 'ClusterTable');

				// Get cluster Id
		$clusterInstance->load(array('id' => $clusterId));
		$multiagencyId = $clusterInstance->client_id;
		// Get all users from cluster
		$subusersModelUsers = RBACL::model('users', array('ignore_request' => true));
		$subusersModelUsers->setState('filter.client_id', $multiagencyId);
		$subusersModelUsers->setState('filter.client', 'com_multiagency');
		$subusersModelUsers->setState('group_by', 'user_id');
		$subusersModelUsers->setState('filter.state', 0);
		$subusersModelUsers->setState('list.ordering', 'uc.name');
		$subusersModelUsers->setState('list.direction', 'asc');
		$allUsers = array();

		return $subusersModelUsers->getItems();
	}

 	/**
	 * Method to get the course track by course id and user id
	 * 
	 * @param Int $courseId courseId 
	 *
 	 * @param Int $oluserId user Id 
 	 * 
	 * @return  Array
	 */
 	public function getCourseTrackByUserId($courseId, $oluserId)
 	{
 		$db   = Factory::getDBO();
 		$input = Factory::getApplication()->input;

 		$courseProgress = array();

 		if ($courseId > 0 && $oluserId)
 		{
 			try
 			{
 				$query = $db->getQuery(true);
 				$query->select(array("*"));

 				$query->from($db->qn('#__tj_certificate_issue'));
 				$query->where($db->qn('client_id') . ' = ' . $db->q((int) $courseId));
 				$query->where($db->qn('user_id') . ' = ' . $db->q((int) $oluserId));

 				$db->setQuery($query);
 				return $track = $db->loadObject();

 			}catch (Exception $e)
 			{
 				return false;
 			}
 		}
 	}
   /**
	 * Method to set  main default set.
	 * 
	 * @param Int $id id of the default set
	 *
 	 * @param Int $clusterId clusterId of the default set
	 * @return  bool
	 */
   public function setMainDefaultSet($id, $clusterId)
   {

   	if(!$id || !$clusterId)
   	{
   		return false;
   	}



   	$allDefaultSets = $this->getAllDefaultSet($clusterId);

   	foreach($allDefaultSets as $allDefaultSet)
   	{
   		if (($allDefaultSet['id'] == $id ) && ($allDefaultSet['set_as_main_default_set'] == 0))
   		{	
   			$db   = Factory::getDBO();
   			$query = $db->getQuery(true);
   			$fields = array($db->quoteName('set_as_main_default_set') . ' = 1' );
   			$conditions = array($db->quoteName('id') . ' ='.$id);
   			$query->update($db->quoteName('#__dpe_useronboard_xref'))->set($fields)->where($conditions);
   			$db->setQuery($query);
   			$db->execute();
   		}
   		elseif($allDefaultSet['set_as_main_default_set'] != 0)
   		{	
   			$db   = Factory::getDBO();
   			$query = $db->getQuery(true);
   			$fields = array($db->quoteName('set_as_main_default_set') . ' = 0' );
   			$conditions = array(	$db->quoteName('id') . ' ='.$allDefaultSet['id']);
   			$query->update($db->quoteName('#__dpe_useronboard_xref'))->set($fields)->where($conditions);
   			$db->setQuery($query);
   			$db->execute();
   		}
   	}

   	return true;
   }

    /**
	 * Method to assign the default set as per start date.
	 *
	 * @param String $start_date
	 * 
	 * @return  bool
	 */
    public function assginedDefaultSetWithStartDate($start_date)
    {
    	$db   = Factory::getDBO();
    	$suceessCount=array();
    	try
    	{
    		$query = $db->getQuery(true);
    		$query->select(array("*"));

    		$query->from($db->qn('#__dpe_useronboard_xref'));
    		$query->where($db->qn('start_date') . ' = ' . $db->q($start_date));
    		$query->where($db->qn('state') . ' = 1');
    		$query->where($db->qn('type_of_set') . ' = "defaultset"');

    		$db->setQuery($query);
    		$result = $db->loadAssocList();

    		foreach($result as $key => $assignTodo)
    		{
    			$formData = json_decode($assignTodo['formdata']);
    			$suceessCount[$key]['count'] = $this->onboardUsersTodoAssign($formData);
    			$suceessCount[$key]['title'] = $assignTodo['title'];
    		}

    		return count($suceessCount);

    	}catch (Exception $e)
    	{
    		return false;
    	}

    }

    /**
	 * Method to get the asset rule by asset id.
	 *
	 * @param int $id  is asset id
	 * 
	 * @return  bool
	 */

    public function getAssetRuleByAssetId($id)
    {
    	$db   = Factory::getDBO();
    	$query = $db->getQuery(true)
    	->select($db->qn('rules'))
    	->from($db->qn('#__assets'))
    	->where($db->qn('id') . ' = ' . $db->q($id));
    	$db->setQuery($query, 0, 1);
    	$rulesJSON	 = $db->loadResult();
    	return $rules		 = json_decode($rulesJSON, true);
    }

	/**
	 * Get user roles by user id and client id
	 *
	 * @param   integer  $userId           userId
	 * @param   string   $client           client for role
	 * @param   mixed    $clientContentId  content id
	 *
	 * @return  array
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public static function getRoleByUser($userId, $client = '', $clientContentId = null)
	{
		$roles = array();

		if ($userId)
		{
			$db = Factory::getDbo();
			$query = $db->getQuery(true);
			$query->select('DISTINCT role_id');
			$query->from($db->quoteName('#__tjsu_users'));
			$query->where($db->quoteName('user_id') . " = " . $db->quote($userId));

			if (!empty($client))
			{
				$query->where($db->quoteName('client') . " = " . $db->quote($client));
			}

			$db->setQuery($query);
			$roles = $db->loadColumn();
		}

		return $roles;
	}

	/**
	 * Send OTP to the Users
	 *
	 * @param   string  $username           username
	 * @param   string   $password           password of the user
	
	 *
	 * @return  array
	 *
	 * @since   __DEPLOY_VERSION__
	 */

	public function sendOtpToUser($username, $password)
	{
		$app = Factory::getApplication();		

		// Joomla does not like blank passwords
		if (empty($password))
		{
			$response->status        = Authentication::STATUS_FAILURE;
			$response->error_message = Text::_('JGLOBAL_AUTH_EMPTY_PASS_NOT_ALLOWED');

			return;
		}

		// Get a database object
		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
		->select($db->quoteName(['id', 'password', 'params']))
		->from($db->quoteName('#__users'))
		->where($db->quoteName('username') . ' = ' . $db->quote($username) . 
			' OR ' . $db->quoteName('email') . ' = ' . $db->quote($username));

		$db->setQuery($query);
		$query->dump(); // Debugging: Outputs the query for inspection
		$userData = $db->loadObject();



		// Check password for user

		if ($userData)
		{
			$match = UserHelper::verifyPassword($password, $userData->password, $userData->id);
		}
		
		if(!$match)
		{
			$msg['type']='error';
			$msg['msg']=Text::_('JGLOBAL_AUTH_NO_USER');
			return $msg ;
		}
		// Get user object
		$user      = Factory::getUser($userData->id);

		$plugin = PluginHelper::getPlugin('authentication', 'dpemfalogin');
		$allowedUserGroups = '';

		if ($plugin) {
			$params = new \Joomla\Registry\Registry($plugin->params);

			$allowedUserGroups = $params->get('dpe_usergroup');
		}

		if (count(array_intersect($user->groups, $allowedUserGroups)) == 0)
		{
			if ($match)
			{

				$tz          = $user->getTimezone();
				$date        = Factory::getDate('now');
				$currentdate = $date->setTimezone($tz);
				$db          = Factory::getDbo();
				$query       = $db->getQuery(true);

				// Query to get activated licesce school(s) of logged in user
				$query->select('DISTINCT c.id, c.title');
				$query->from($db->qn('#__users', 'a'));
				$query->join('INNER', $db->qn('#__tjsu_users', 'b') .
					' ON (' . $db->qn('a.id') . ' = ' . $db->qn('b.user_id') . ' AND ' . $db->qn('b.client') . ' = "com_multiagency" )');
				$query->join('INNER', $db->qn('#__tjmultiagency_multiagency', 'c') . ' ON (' . $db->qn('b.client_id') . ' = ' . $db->qn('c.id') . ')');
				$query->join('INNER', $db->qn('#__tjmultiagency_licences', 'd') . ' ON (' . $db->qn('d.multiagency_id') . ' = ' . $db->qn('c.id') . ' )');
				$query->where($db->quoteName('a.id') . ' = ' . $db->quote((int) $user->id));
				$query->where($db->quoteName('d.state') . ' = 1');
				$query->where($db->quoteName('d.end_date') . ' >= ' . $db->quote($currentdate));
				$db->setQuery($query);

				$result  = $db->loadObjectList();

				// Query to get user related to any organization of logged in user
				$query = $db->getQuery(true);
				$query->select('a.id');
				$query->from($db->qn('#__tjsu_users', 'a'));
				$query->where($db->quoteName('a.user_id') . ' = ' . $db->quote((int) $user->id));
				$db->setQuery($query);

				$existingUsers  = $db->loadObjectList();


				if (!$existingUsers)
				{
					$app = Factory::getApplication();
					$menu       = $app->getMenu();
					$url=Route::_('index.php?option=com_sppagebuilder&view=page&id=125',false);
					$msg['type']='error';
					$msg['msg']=Text::sprintf('JGLOBAL_AUTH_FAILED', Text::sprintf('DPE_AUTH_ACCESS_PUBLIC_DENIED', $url));
					$msg['action']= false;
					return $msg;
				}


				if (!$result)
				{
					$app = Factory::getApplication();
					$menu       = $app->getMenu();
					$msg['type']='error';
					$msg['msg']=Text::sprintf('JGLOBAL_AUTH_FAILED', Text::_('DPE_AUTH_ACCESS_DENIED'));
					$msg['action']= false;
					return $msg;

				}
				else
				{
					if($user->authorise('core.admin'))
					{

						$msg['type']='error';
						$msg['msg']=Text::_('DPE_AUTH_ACCESS_DENIED_AS_DPEADMIN_ACESS');
						$msg['action']= false;
						return $msg;
					}
					if(!$user->authorise('core.admin'))
					{
						Table::addIncludePath(JPATH_SITE . '/components/com_dpe/tables');

						$dpeOtpLogin = Table::getInstance('UserOtpLogin', 'DpeTable');

						$dpeOtpLogin->load(array('user_id' => $userData->id));

						$otp = rand(100000, 999999);

						$date = Factory::getDate();
						$otpGeneratedDate = $date->format('Y-m-d H:i:s');

						if (!$dpeOtpLogin->id)
						{	

							$result = $dpeOtpLogin->save(array('user_id'=> (int)$userData->id,'otp' => (int)$otp, 'otp_generated_date_time'=>$otpGeneratedDate));

						}else
						{

							$result = $dpeOtpLogin->save(array('id'=>$dpeOtpLogin->id,'user_id'=> (int)$userData->id,'otp' => (int)$otp, 'otp_generated_date_time'=>$otpGeneratedDate,'otp_used'=>0,'otp_used_date_time'=>$otpGeneratedDate));
						}


						if(!$result)
						{
							$app = Factory::getApplication();
							$menu       = $app->getMenu();
							$menuId = $menu->getItems('link', 'index.php?option=com_users&view=login', true);

							$msg['type']='error';
							$msg['msg']=Text::_('DPE_MSG_FOR_MFA_LOGIN_ERROR');
							$msg['action']= false;
							return $msg;								
						}

					}

					$config  = Factory::getConfig();
					$data    = $user->getProperties();
					$data['fromname']	= $config->get('fromname');
					$data['mailfrom']	= $config->get('mailfrom');
					$data['sitename']	= $config->get('sitename');
					$data['siteurl']	= JUri::base();
					$data['activate'] = $otp;

		                // Set body of mail
					if(!$isRegistration)
					{
			            // DPE - Hack Dont send registration email
						$body = Text::sprintf("PLG_BBPASS_LOGIN_LINK_WITH_OTP", $user->name, $data['activate']);
						$mailer = Factory::getMailer();
						$mailer->isHtml(true);

						if(empty($options['type']) && ($options['type']!='bbpautologin'))
						{
						        //send link to login the user
							$test = $mailer->setSender( array(
								$data['mailfrom'],
								$data['fromname']
							))
							->addRecipient($data['email'])
							->setSubject(Text::_('PLG_BBPASS_EMAIL_SUBJECT_OTP'))
							->setBody($body)
							->Send();

							$msg['type']='Success';
							$msg['msg']= Text::_('DPE_MSG_FOR_MFA_LOGIN_OTP');
							$msg['action']= true;
							return $msg;
						}
						else
						{
							$msg['type']='error';
							$msg['msg']= Text::_('DPE_MSG_FOR_MFA_LOGIN_OTP_ERROR');
							$msg['action']= false;
							return $msg;

						}
					}
				}
			}	
		}
		
		$msg['type']='error';
		$msg['msg']=Text::_('DPE_AUTH_ACCESS_DENIED_AS_DPEADMIN_ACESS');
		$msg['action']= false;
		return $msg;
	}

	/**
	 * fucntion is check OTP insert by the user
	 *
	 * @param   string  $username           username
	 * @param   string   $password           password of the user
	 * @param   string   $otp           	 otp given by the user
	 *
	 * @return  array
	 *
	 * @since   __DEPLOY_VERSION__
	 */

	public function checkOtp($username, $password, $otp)
	{
		$username = trim($_POST['username'] ?? '');
		$username = str_replace(' ', '+', $username); // restore '+' from space
		
		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
		->select($db->quoteName(['id', 'password', 'params']))
		->from($db->quoteName('#__users'))
		->where($db->quoteName('username') . ' = ' . $db->quote($username) . 
			' OR ' . $db->quoteName('email') . ' = ' . $db->quote($username));

		$db->setQuery($query);
		$query->dump(); // Debugging: Outputs the query for inspection
		$userData = $db->loadObject();

		// Check password for user

		if ($userData)
		{
			$match = UserHelper::verifyPassword($password, $userData->password, $userData->id);
		}

		if ($match)
		{
			Table::addIncludePath(JPATH_SITE . '/components/com_dpe/tables');
			$dpeOtpLogin = Table::getInstance('UserOtpLogin', 'DpeTable');
			$dpeOtpLogin->load(array('user_id' => $userData->id));

			$generatedOtp  =  $dpeOtpLogin->otp;
			$generatedTime =  $dpeOtpLogin->otp_generated_date_time;

			$date = Factory::getDate();
			$currentDate = $date->format('Y-m-d H:i:s');
			$currentDateTime = new DateTime($currentDate);
			$generatedDateTime = new DateTime($generatedTime);

			$interval = $currentDateTime->diff($generatedDateTime);
			$interval->format('%y years %m months %d days %h hours %i minutes %s seconds');

			Table::addIncludePath(JPATH_SITE . '/components/com_dpe/tables');
			$dpeOtpLogin = Table::getInstance('UserOtpLogin', 'DpeTable');
			$dpeOtpLogin->load(array('user_id' => $userData->id));

			// Example to check if the difference is more than 1 hour
			if ($interval->h > 1 || ($interval->days > 0) || ($dpeOtpLogin->otp_used == 1)) {
				$msg['msg'] = Text::_("COM_DPE_OTP_IS_EXPIRED");
				$msg['success'] = false;
				return $msg;
			}
			elseif ($otp != $generatedOtp)
			{
				$msg['msg'] = Text::_("COM_DPE_OTP_MISMATCH");
				$msg['success'] = false;
				$msg['type'] = 'error';
				return $msg;
			}
			else
			{
				$msg['msg'] = Text::_("COM_DPE_OTP_MATCHED");
				$msg['success'] = true;
				$msg['type'] = 'success';

				if($dpeOtpLogin->id)
				{
					$date = Factory::getDate();
					$otpusedDate = $date->format('Y-m-d H:i:s');

					$result = $dpeOtpLogin->save(array('id'=>$dpeOtpLogin->id, 'otp_used_date_time'=>$otpusedDate,'otp_used'=>1));
				}

				return $msg;
			}

		}
	}

	/**
	 * function is to validate of the user
	 *
	 * @param   string  $username           username
	 *
	 * @return  array
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function checkUserValidation($username)
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
		->select($db->quoteName(['id', 'password', 'params']))
		->from($db->quoteName('#__users'))
		->where($db->quoteName('username') . ' = ' . $db->quote($username) . 
			' OR ' . $db->quoteName('email') . ' = ' . $db->quote($username));

		$db->setQuery($query);
		$query->dump(); // Debugging: Outputs the query for inspection
		$userData = $db->loadObject();

		$user      = Factory::getUser($userData->id);

		if($user->authorise('core.admin') || $user->authorise('core.manageall', 'com_cluster'))
		{
			return true;
		}else
		{
			return false;
		}
	}
	/**
	 * Method to get the subscribed users details
	 *
	 * @param   string  $email           email
	 *
	 * @return  array
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function getSubscribeduserDetails($email)
	{	
		if (!$email)
		{
			return false;
		}

		$db = Factory::getDbo();
		$query = $db->getQuery(true)
		->select('*')
		->from($db->quoteName('#__jma_subscribers'))
		->where($db->quoteName('email_id') . ' = ' . $db->quote($email));
		$db->setQuery($query);
		$result = $db->loadObjectList();

		return $result;
	}
	/**
	 * Method to get the subscribed users details
	 *
	 * @param   string  $email           email
	 *
	 * @return  array
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function unsubscribeGuestUser($alertId, $emailid)
	{
		if (!$emailid || !$alertId)
		{
			return false;
		}
			// Get the database object
		$db = Factory::getDbo();

			// Build the delete query
		$query = $db->getQuery(true)
		->delete($db->quoteName('#__jma_subscribers'))
		->where($db->quoteName('email_id') . ' = ' . $db->quote($emailid))
		->where($db->quoteName('alert_id') . ' = ' . $db->quote($alertId))->setLimit(1);

		$db->setQuery($query);

		try {
			$db->execute();
			return $msg = 'success';
		} catch (Exception $e) {
			return $e->getMessage();
		}
	}

	/**
     * Save JMailAlerts subscription preferences for a (guest) user coming from an event page.
     *
     * @param  string  $email                 Email address to subscribe
     * @return array                          ['success'=>bool, 'inserted'=>int, 'errors'=>array]
     */
	public function saveAlertPrefernceFromEvent(string $email)
	{
		$app   = Factory::getApplication();
		$input = $app->input;
		/** @var DatabaseDriver $db */
		$result = ['success' => false, 'inserted' => 0, 'errors' => []];

		try {
            // Load JMailAlerts model
			BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_jmailalerts/models/');
			/** @var \JmailalertsModelEmails $model */
			$model = BaseDatabaseModel::getInstance('Emails', 'JmailalertsModel');

            // Nothing to do if there are no alerts configured
			$cntalert = (int) $model->gettotalalertcount();
			if ($cntalert === 0) {
				$result['success'] = true;
				return $result;
			}

            // Build plugins list from CONCAT query (quoted parts)
			$plugins  = [];
			$qryConcat = (array) $model->alertqryconcat();
			foreach ($qryConcat as $value) {
				preg_match_all("/'([^']+)'/", (string) $value, $matches);
				if (empty($matches[1])) {
					continue;
				}
				if (count($matches[1]) === 1) {
					$plugins[] = $matches[1][0];
				} else {
                    // store as bracketed string per your required format
					$plugins[] = '[' . implode(',', $matches[1]) . ']';
				}
			}

            // Get defaults / alert ids
            $defaultoption = (array) $model->getdefaultalertid();   // frequencies per alert index/key
            $altid         = (array) $model->get_all_alertid();     // array of alert ids

            // Build plugins_subscribed_to string from request
            // Expected: request fields named by plugin element, each containing key=>value pairs
            $dbPlugEntry = '';

$pluginOutputs = []; // Store results per index

foreach ($plugins as $index => $plugName)
{
	$group = [];

    // Case 1: formatted like [pluginA,pluginB]
	if (preg_match('/^\[(.+)\]$/', trim($plugName), $m)) {
		$group = array_map('trim', explode(',', $m[1]));
	}
	else
	{
		$group = (array)$plugName;
	}

    // Determine which plugins to process
	$candidates = $group ?: [trim($plugName)];

    $entryBlock = ''; // output for this array index

    foreach ($candidates as $candidate)
    {
        // Load plugin from folder `emailalerts` (change if needed)
    	$plugin = PluginHelper::getPlugin('emailalerts', $candidate);

    	if ($plugin)
    	{
    		$params = new Registry($plugin->params);

            // Get parameter values
    		$userCategoriesMethod = $params->get('user_categories_method', 'default');
    		$catid                = $params->get('catid', 'default');
            // Append formatted lines
    		$entryBlock .= $candidate . '|user_categories_method=' . $userCategoriesMethod . "\n";
    		$entryBlock .= $candidate . '|catid=' . (is_array($catid) ? implode(',', $catid) : $catid) . "\n";
    	}
    }

    // Assign to this index in final array (trim for clean formatting)
    $pluginOutputs[$index] = trim($entryBlock);


}

           // Ensure we have at least a minimal entry so the column is not empty
if ($dbPlugEntry === '') {
	$dbPlugEntry = "none|selected=0";
}

            // Prepare common values
			$today = Factory::getDate()->Format('Y-m-d H:i:s');
            $name  = $email;                // guest name as email (adjust if you capture a name)

            // Transaction for safety
            Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_jmailalerts/tables');

            foreach ($altid as $idx => $alertId) {


			$jmailalertsTablealert = Table::getInstance('alert', 'JmailalertsTable', array());
			$jmailalertsTablealert->load(array('id' => $alertId));

    		$decodedGroups = json_decode($jmailalertsTablealert->usergroup, true);
  			$userGroup = is_array($decodedGroups) ? array_map('intval', $decodedGroups) : [];
		    
				$db = Factory::getContainer()->get('DatabaseDriver');

				$query = $db->getQuery(true)
				    ->select($db->quoteName('id'))
				    ->from($db->quoteName('#__usergroups'))
				    ->where($db->quoteName('title') . ' = ' . $db->quote('guest'));

				$db->setQuery($query);

				 $dpeGuestpId = (int) $db->loadResult();			
    			 $guestUserGroup = array_intersect((array)$dpeGuestpId, (array)$userGroup);
	

			if(!$guestUserGroup)
				{	
					continue ;
				}

				$db = Factory::getDbo();

				$db1 = Factory::getDbo();

				$query = $db1->getQuery(true)
				->select('alert_id')
				->from($db1->quoteName('#__jma_subscribers'))
				->where($db1->quoteName('email_id') . ' = ' . $db1->quote($email))->where($db1->quoteName('alert_id') . ' = ' . $db1->quote($alertId));
				$db1->setQuery($query);
				$subscribedAlertId = $db1->loadObjectList();

				
				if( $subscribedAlertId[0]->alert_id == $alertId)
				{
					return $msg = 'subscribed';
				}


            	$alertId = (int) $alertId;

                // Frequency mapping from defaultoption (fallback to 1)
            	$frequency = (int) ($defaultoption[$idx] ?? 3);// This code will change later for frequency for now its 3 .
            	$dbPlugEntry =  $pluginOutputs[$idx];

            	$query = $db->getQuery(true)
            	->insert($db->quoteName('#__jma_subscribers'))
            	->columns([
            		$db->quoteName('user_id'),
            		$db->quoteName('alert_id'),
            		$db->quoteName('name'),
            		$db->quoteName('email_id'),
            		$db->quoteName('frequency'),
            		$db->quoteName('date'),
            		$db->quoteName('plugins_subscribed_to'),
            		$db->quoteName('state'),
            	])
            	->values(implode(',', [
            		0,
            		$db->quote($alertId),
            		$db->quote($name),
            		$db->quote($email),
            		$db->quote($frequency),
            		$db->quote($today),
            		$db->quote($dbPlugEntry),
            		$db->quote('1'),
            	]));

            	$db->setQuery($query);
            	$db->execute();

            	$subscriptionId = (int) $db->insertid();

            	$data['user_id'] =  0;

                // Fire plugin event for each insert
            	$data = [
            		'name'=>$name,
            		'email_id'=>$email,
            		'date'                  => $today,
            		'state'                 => '1',
            		'alert_id'              => $alertId,
            		'frequency'             => $frequency,
            		'plugins_subscribed_to' => $dbPlugEntry,
            		'subscriptionId'        => $subscriptionId,
            		'userOrganisation'      => $userOrganisation,
            		'fromevent'=>1,
            	];

            	PluginHelper::importPlugin('dpe');
            	$app->triggerEvent('onAfterJmaAlertSubscriptionSaveNotifyGuestUser', [$data, '1']);
            	$result['inserted']++;
            }

            return $msg = 'success';

        } catch (\Throwable $e) {
        	$result['errors'][] = $e->getMessage();
        	return $e->getMessage();
        }
    }



	/**
	 * Retrieves user export data based on applied filters such as
	 * agencies, roles, SLA status, and tags.
	 *
	 * @return  array  List of user objects prepared for export
	 *
	 * @throws  Exception  When no matching active/inactive SLA clusters are found
	 *
	 * @since   __DEPLOY__VERSION__
	 */
	public function getExportData($filters)
	{
		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_multiagency/models', 'MultiagencyModel');
		$model = BaseDatabaseModel::getInstance('Users', 'MultiagencyModel', ['ignore_request' => true]);

		$model->setState('filter.search', $filters['search']);
		$model->setState('filter.agencies', $filters['agencies']);
		$model->setState('filter.role_id', $filters['role_id']);
		$model->setState('filter.tags', $filters['tags']);
		$model->setState('filter.sla_filter', $filters['sla_filter']);

		$model->setState('list.start', 0);
		$model->setState('list.limit', 0);

		return $model->getItems();
	}

}
