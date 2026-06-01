<?php
/**
 * @package     TJLms
 * @subpackage  com_shika
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Access\Access;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

use Joomla\CMS\Table\Table;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Component\ComponentHelper;

/**
 * Methods supporting a list of Tjlms records.
 *
 * @since  1.0.0
 */
class TjlmsModelEnrolment extends ListModel
{
	protected $errorCode;

	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @since   1.0.0
	 */
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'id', 'uc.id',
				'name', 'uc.name',
				'email', 'uc.email',
				'status', 'uc.block',
				'username', 'uc.username',
				'groupfilter', 'uum.group_id',
				'subuserfilter',
			);
		}

		JLoader::register('TjlmsCoursesHelper', JPATH_SITE . '/components/com_tjlms/helpers/courses.php');
		$this->tjlmsCoursesHelper = new TjlmsCoursesHelper;

		parent::__construct($config);
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   string  $ordering   An optional ordering field.
	 * @param   string  $direction  An optional direction (asc|desc).
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		// Initialise variables.
		// List state information.
		parent::populateState('uc.username', 'asc');

		$app = Factory::getApplication();

		// Load the filter state.
		$search = $app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		// Filtering type
		$this->setState('filter.accesslevel', $app->getUserStateFromRequest($this->context . '.filter.accesslevel', 'accesslevel', '', 'STRING'));

		// Filtering group
		$groupfilter = $app->getUserStateFromRequest($this->context . '.filter.groupfilter', 'groupfilter', '', 'INT');
		$this->setState('filter.groupfilter', $groupfilter);

		// Filter for selected courses
		$selectedcourse = $app->getUserStateFromRequest($this->context . '.filter.selectedcourse', 'selectedcourse', '', 'ARRAY');
		$this->setState('filter.selectedcourse', $selectedcourse);

		// Load the parameters.
		$params = ComponentHelper::getParams('com_tjlms');
		$this->setState('params', $params);
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
	 * @return  string  A store id.
	 *
	 * @since	1.6
	 */
	protected function getStoreId($id = '')
	{
		// Compile the store id.
		$id .= ':' . $this->getState('filter.search');
		$id .= ':' . $this->getState('filter.state');

		return parent::getStoreId($id);
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return	JDatabaseQuery
	 *
	 * @since	1.6
	 */
	protected function getListQuery()
	{
		// Create a new query object.
		$db = $this->getDbo();
		$query = $db->getQuery(true);

		$query->select($this->getState('list.select', 'distinct(uc.id), uc.name, uc.username,uc.block'));
		$query->from($db->qn('#__users', 'uc'));
		$query->join('INNER', $db->qn('#__user_usergroup_map', 'uum') . ' ON (' . $db->qn('uc.id') . ' = ' . $db->qn('uum.user_id') . ')');
		$query->where($db->qn('uc.block') . ' = 0');

		// Filter by search in title
		$search = $this->getState('filter.search');

		if (!empty($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where($db->qn('uc.id') . ' = ' . (int) substr($search, 3));
			}
			else
			{
				$search = $db->Quote('%' . $db->escape($search, true) . '%');
				$query->where('(( uc.name LIKE ' . $search . ' ) OR ( uc.username LIKE ' . $search . ' ) OR ( uc.id LIKE ' . $search . ' ) OR ( uc.email LIKE ' . $search . ' ))');
			}
		}

		$input    = Factory::getApplication()->input;
		$groupFilter = $this->getState('filter.groupfilter');

		// Get user ID if view called from course list view to view enrolled users.
		if (!$groupFilter)
		{
			$courseFilter = $this->getState('filter.coursefilter');
		}

		// Filtering type
		if ($groupFilter != '')
		{
			if (!is_array($groupFilter))
			{
				$groupFilter = (array) $groupFilter;
			}

			$query->where($db->qn('uum.group_id') . ' IN (' . implode(',', $db->q($groupFilter)) . ')');
		}

		// Filtering groups
		$accessLevel = $this->state->get('filter.accesslevel');

		if ($accessLevel != '')
		{
			$query->where($db->qn('uum.group_id') . ' = ' . $db->q((int) $accessLevel));
		}

		$where = $this->_buildContentWhere();

		if ($where)
		{
			$query->where(' ' . $where);
		}

		$subUsers = $this->getState('filter.subuserfilter', 0);

		if ($subUsers == 1)
		{
			JLoader::import('administrator.components.com_tjlms.helpers.tjlms', JPATH_SITE);
			$hasUsers = TjlmsHelper::getSubusers();

			if (!$hasUsers)
			{
				$hasUsers = array(0);
			}

			$query->where($db->qn('uc.id') . 'IN(' . implode(',', $db->q($hasUsers)) . ')');
		}

		$type = $this->getState('type', 0);
		$cId = $this->getState('filter.selectedcourse');

		if ($type === 'reco')
		{
			JLoader::register('JlikeModelRecommend', JPATH_SITE . '/components/com_jlike/models/recommend.php');
			$jlikeModelRecommend = new JlikeModelRecommend;
			$recommendedUsers = $jlikeModelRecommend->getTypewiseUsers($cId[0], 'com_tjlms.course', $type);

			if ($recommendedUsers)
			{
				$query->where($db->qn('uc.id') . 'NOT IN (' . implode(',', $db->q($recommendedUsers)) . ')');
			}
		}

		// Add the list ordering clause.
		$orderCol = $this->state->get('list.ordering');
		$orderDirn = $this->state->get('list.direction');

		if ($orderCol && $orderDirn)
		{
			$query->order($db->escape($orderCol . ' ' . $orderDirn));
		}

		return $query;
	}

	/**
	 * To get where condition for query.
	 *
	 * @return  query
	 *
	 * @since  1.0.0
	 */
	public function _buildContentWhere()
	{
		$input = Factory::getApplication()->input;
		$db = Factory::getDBO();
		$cId = $this->getState('filter.selectedcourse');

		if (count($cId) == 1)
		{
			$enrolledUsers = $this->getCourseEnrolledUsers($cId[0]);
		}

		$where = array();

		if (!empty($enrolledUsers))
		{
			$where[] = $db->qn('uc.id') . " NOT IN(" . implode(',', $db->q($enrolledUsers)) . ")";
		}

		$cAl = $input->get('course_al', '', 'STRING');
		$uGroups = '';

		// If course has access level get groups applicable for that access level

		if ($cAl && $cAl != 1)
		{
			$uGroups = $this->getGroups($cAl);
		}

		if (!empty($uGroups['0']))
		{
			$where[] = $db->qn('uum.group_id') . "IN(" . implode(',', $db->q($uGroups)) . ")";
		}

		if (!empty($where))
		{
			$where = (count($where) ?  implode(' AND ', $where) : '');
		}

		return $where;
	}

	/**
	 * To plublish and unpublish enrolledment.
	 *
	 * @param   JRegistry  $items  The item to update.
	 * @param   JRegistry  $state  The state for the item.
	 *
	 * @return  true or false
	 *
	 * @since  1.0.0
	 */
	public function setItemState($items, $state)
	{
		$db = Factory::getDBO();

		if (is_array($items))
		{
			try
			{
				foreach ($items as $id)
				{
					$db = Factory::getDbo();
					$query = $db->getQuery(true);
					$fields = array($db->quoteName('state') . ' = ' . $db->quote($state));

					// Conditions for which records should be updated.
					$conditions = array($db->quoteName('id') . ' = ' . $db->quote($id));
					$query->update($db->quoteName('#__tjlms_enrolled_users'))->set($fields)->where($conditions);
					$db->setQuery($query);

					if (!$db->execute())
					{
						$this->setError($this->_db->getErrorMsg());

						return false;
					}
				}
			}
			catch (Exception $e)
			{
				$this->setError($e->getMessage());

				return false;
			}
		}

		return true;
	}

	/**
	 * To get user already enrolled in the course.
	 *
	 * @param   INT  $c_id  The course ID.
	 *
	 * @return  result
	 *
	 * @since  1.0.0
	 */
	public function getCourseEnrolledUsers($c_id)
	{
		try
		{
			// Get a db connection.
			$db = Factory::getDbo();

			// Create a new query object.
			$query = $db->getQuery(true);
			$query->select($db->qn('eu.user_id'));
			$query->from($db->qn('#__tjlms_enrolled_users', 'eu'));
			$query->where($db->qn('eu.course_id') . ' = ' . $db->q((int) $c_id));

			$db->setQuery($query);

			// Load the results
			$enrolledUsers = $db->loadColumn();

			$allowFlexiEnrolments = TjLms::config()->get('allow_flexi_enrolments', 0);

			// If allowFlexi Enrolmnets is enabled then include only those users whose entry not present in the enrollment table.
			if ($allowFlexiEnrolments)
			{
				JLoader::import('components.com_tjlms.models.orders', JPATH_ADMINISTRATOR);
				$ordersModel = BaseDatabaseModel::getInstance('Orders', 'TjlmsModel', array('ignore_request' => true));

				$ordersModel->setState("filter.coursefilter", $c_id);
				$ordersModel->setState("filter.enrollment_id", "0");
				$ordersModel->setState("filter.statusfilter", "C");
				$orderData = $ordersModel->getItems();
				$result = array_column($orderData, 'u_id');

				if (is_array($result) && !empty($enrolledUsers) && is_array($enrolledUsers))
				{
					$enrolledUsers = array_unique(array_merge($result, $enrolledUsers));
				}
			}

			return $enrolledUsers;
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}
	}

	/**
	 * To get groups for the repected access level.
	 *
	 * @param   INT  $cAl  The access level ID.
	 *
	 * @return  Object
	 *
	 * @since  1.0.0
	 */
	public function getGroups($cAl)
	{
		try
		{
			$alGroups = array();
			$db	= Factory::getDBO();
			$query = $db->getQuery(true);
			$query->select($db->qn('rules'));
			$query->from($db->qn('#__viewlevels'));

			// If public get all access levels
			if ($cAl == 1)
			{
				$db->setQuery($query);
				$tempLevels = $db->loadObjectlist();

				foreach ($tempLevels as $tempLevel)
				{
					$temp = json_decode($tempLevel->rules);

					$alGroups = array_merge($alGroups, $temp);
					$alGroups = array_unique($alGroups);
				}
			}
			else
			{
				$query->where($db->qn('id') . ' = ' . $db->q((int) $cAl));

				$db->setQuery($query);
				$temp = json_decode($db->loadResult());

				if (isset($temp))
				{
					$alGroups = array_merge($alGroups, $temp);
				}
			}

			return $alGroups;
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}
	}

	/**
	 * To get the records
	 *
	 * @return  Object
	 *
	 * @since  1.0.0
	 */
	public function getItems()
	{
		try
		{
			$items = parent::getItems();
			$db	= Factory::getDBO();
			$query = $db->getQuery(true);
			$query->select($db->quoteName(array('title','id')));
			$query->from($db->quoteName('#__usergroups'));
			$db->setQuery($query);
			$groups = $db->loadAssocList('id', 'title');

			foreach ($items as $k => $obj)
			{
				$userGroups = Access::getGroupsByUser($obj->id, false);
				$userGroups = array_flip($userGroups);
				$group 		= array_intersect_key($groups, $userGroups);
				$items[$k]->groups = implode('<br />', $group);
			}

			return $items;
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}
	}

	/**
	 * To get user already enrolled in the course.
	 *
	 * @param   INT  $courseId  course ID.
	 *
	 * @param   INT  $userId    user ID.
	 *
	 * @return  result
	 *
	 * @since  1.0.0
	 */
	public function getEnrolledUserParams($courseId, $userId)
	{
		try
		{
			$db = Factory::getDBO();
			$query = $db->getQuery(true);
			$query->select('params');
			$query->from($db->quoteName('#__tjlms_enrolled_users'));
			$query->where($db->quoteName('course_id') . " = " . $db->quote((int) $courseId));
			$query->where($db->quoteName('user_id') . " = " . $db->quote((int) $userId));
			$db->setQuery($query);
			$result = $db->loadObject();

			return json_decode($result->params, true);
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}
	}

	/**
	 * To get user already enrolled in the course.
	 *
	 * @param   INT    $courseId  course ID.
	 * @param   INT    $userId    user ID.
	 * @param   ARRAY  $col       column array.
	 *
	 * @return  result
	 *
	 * @since  1.0.0
	 */
	public function getEnrolledUserColumn($courseId, $userId, $col)
	{
		try
		{
			$db = Factory::getDBO();
			$query = $db->getQuery(true);
			$query->select($col);
			$query->from($db->quoteName('#__tjlms_enrolled_users'));
			$query->where($db->quoteName('course_id') . " = " . $db->quote((int) $courseId));
			$query->where($db->quoteName('user_id') . " = " . $db->quote((int) $userId));
			$db->setQuery($query);

			return $db->loadObject();
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}
	}

	/**
	 * Check user is enrolled to the course or not
	 *
	 * @param   INT  $courseId  course ID.
	 * @param   INT  $userId    user ID.
	 *
	 * @return  enrolled user id on success
	 *
	 * @since  1.1.3
	 */
	public function checkUserEnrollment($courseId, $userId)
	{
		try
		{
			$db = Factory::getDBO();
			$query = $db->getQuery(true);
			$query->select($db->quoteName('id'));
			$query->from($db->quoteName('#__tjlms_enrolled_users', 'a'));
			$query->where($db->quoteName('a.course_id') . ' = ' . (int) $courseId);
			$query->where($db->quoteName('a.user_id') . ' = ' . (int) $userId);
			$db->setQuery($query);

			return $db->loadResult();
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}
	}

	/**
	 * Function used to enroll user to course
	 *
	 * @param   array  $data  array of data
	 *
	 * @return integer|boolean enrollment id
	 *
	 * @since 1.0.0
	 */
	public function save($data)
	{
		$userId               = empty($data['user_id']) ? Factory::getUser()->id : $data['user_id'];
		$courseId             = $data['course_id'];
		$state                = isset($data['state']) ? $data['state'] : 0;
		$notifyUser           = empty($data['notify_user']) ? 0 : $data['notify_user'];
		$coursestatus         = empty($data['coursestatus']) ? '' : $data['coursestatus'];
		$timestart            = empty($data['timestart']) ? '' : $data['timestart'];
		$timeend              = empty($data['timeend']) ? '' : $data['timeend'];
		$loggedInUser         = Factory::getUser()->id;
		$params               = ComponentHelper::getParams('com_tjlms');
		$allowFlexiEnrolments = $params->get('allow_flexi_enrolments', 0, 'INT');

		$path = JPATH_ADMINISTRATOR . '/components/com_tjlms/helpers/tjlms.php';
		JLoader::register('TjlmsHelper', $path);
		JLoader::load('TjlmsHelper');

		// Should not enrol if auto-enrol is enabled
		if (!$allowFlexiEnrolments)
		{
			$canEnroll = TjlmsHelper::canSelfEnrollCourse($courseId, $userId);

			if (!$canEnroll && $loggedInUser == $userId)
			{
				$this->setError(Text::sprintf('COM_TJLMS_COURSE_ENROLL_NOT_ALLOWED'));

				return false;
			}
		}

		// If enrolling user is different
		if ($loggedInUser != $userId)
		{
			$canManageEnrollment = TjlmsHelper::canManageCourseEnrollment($courseId, $loggedInUser);

			if (!$canManageEnrollment)
			{
				$this->setError(Text::sprintf('COM_TJLMS_COURSE_MANAGE_ENROLL_NOT_ALLOWED'));

				return false;
			}
		}

		try
		{
			// Check user is valid or not
			if (!Factory::getUser($userId)->id)
			{
				throw new Exception(Text::sprintf("COM_TJLMS_INVALID_USER"), 404);
			}
		}
		catch (Exception $e)
		{
			$this->errorCode = $e->getCode();
			$this->setError($e->getMessage());

			return false;
		}

		$db = Factory::getDBO();

		try
		{
			$courseObj = $this->tjlmsCoursesHelper->getcourseInfo($data['course_id']);

			// Check course is present or not
			if (empty($courseObj))
			{
				throw new Exception(Text::_("COM_TJLMS_COURSE_NOT_EXISTS"), 404);
			}
		}
		catch (Exception $e)
		{
			$this->errorCode = $e->getCode();
			$this->setError($e->getMessage());

			return false;
		}

		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjlms/tables');
		$enrollTableObj = Table::getInstance('Enrolledusers', 'TjlmsTable', array('dbo', $db));
		$enrollTableObj->load(array('user_id' => (int) $userId, 'course_id' => (int) $courseId));

		// Compute the start date of enrolment
		if (isset($data['enrolled_on_time']))
		{
			$now = $data['enrolled_on_time'];
		}
		else
		{
			$now = Factory::getDate()->toSql(true);
		}

		// Prepare the order object
		if (!$enrollTableObj->id)
		{
			$enrollTableObj->id = '';
			$enrollTableObj->user_id          = $userId;
			$enrollTableObj->course_id        = $courseId;
			$enrollTableObj->enrolled_on_time = $now;
			$enrollTableObj->modified_time    = $now;
			$enrollTableObj->end_time = '0000-00-00 00:00:00';
			$enrollTableObj->modified_time = '0000-00-00 00:00:00';
			$enrollTableObj->before_expiry_mail = '0';
			$enrollTableObj->after_expiry_mail = '0';
			$enrollTableObj->params	 = '0';
		}
		else
		{
			$enrollTableObj->modified_time = $now;
		}

		$enrollTableObj->unlimited_plan = empty($data['unlimited_plan']) ? 0 : $data['unlimited_plan'];
		$enrollTableObj->state          = $state;
		$enrolledBy                     = $enrollTableObj->enrolled_by = Factory::getUser()->id;

		PluginHelper::importPlugin('tjlms');

		// Trigger all "onBeforeCourseEnrol" plugins method
		$result = Factory::getApplication()->triggerEvent('onBeforeCourseEnrol', array($courseId, $userId));

		if (in_array(false, $result, true))
		{
			Factory::getApplication()->enqueueMessage(Text::_('COM_TJLMS_VIEW_COURSE_PREREQUISITE_RESTRICT_MESSAGE'), 'error');

			return false;
		}

		try
		{
			$enrollTableObj->store();

			if (!empty($data['com_fields']))
			{
				$manageenrollmentArray = array();

				$manageenrollmentArray['id'] = $enrollTableObj->id;
				$manageenrollmentArray['com_fields'] = $data['com_fields'];

				BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjlms/models');
				$manageenrollmentModel = BaseDatabaseModel::getInstance('Manageenrollment', 'TjlmsModel');

				$manageenrollmentModel->save($manageenrollmentArray);
			}
		}
		catch (RuntimeException $e)
		{
			$this->errorCode = $e->getCode();
			$this->setError($e->getMessage());

			return false;
		}

		PluginHelper::importPlugin('system');
		PluginHelper::importPlugin('tjlms');

		// Trigger all "onAfterCourseEnrol" plugins method
		Factory::getApplication()->triggerEvent('onAfterCourseEnrol', array(
				$userId,
				$enrollTableObj->state,
				$courseId,
				$enrollTableObj->enrolled_by,
				$notifyUser,
				$coursestatus,
				$timestart,
				$timeend
			)
		);

		return $enrollTableObj->id;
	}

	/**
	 * used to get the error code set in exception
	 *
	 * @return  error code
	 *
	 * @since  1.1.3
	 */
	public function getErrorCode()
	{
		return $this->errorCode;
	}

	/**
	 * To get non enrolled users for a course
	 *
	 * @param   ARRAY  $userIds   selected users array
	 * @param   INT    $courseId  course id
	 *
	 * @return  mixed
	 *
	 * @since  1.0.0
	 */
	public function getNonEnrolledUsers($userIds, $courseId)
	{
		try
		{
			$courseId = (int) $courseId;
			$db = Factory::getDBO();
			$query = $db->getQuery(true);
			$query->select($db->quoteName('user_id'));
			$query->from($db->quoteName('#__tjlms_enrolled_users'));
			$query->where($db->quoteName('course_id') . " = " . $db->quote($courseId));
			$query->where($db->quoteName('user_id') . "IN (" . implode(',', $db->quote($userIds)) . " ) ");
			$db->setQuery($query);
			$enrolledUsers = $db->loadColumn();
			$result = array_unique(array_diff($userIds, $enrolledUsers));

			return  $result;
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}
	}

	/**
	 * Function used to enroll user to course
	 *
	 * @param   array  $data  array of data
	 *
	 * @return integer|boolean enrollment id
	 *
	 * @since 1.0.0
	 */
	public function userEnrollment($data)
	{
		if (empty($data['course_id']))
		{
			return false;
		}

		$courseInfo = $this->tjlmsCoursesHelper->getcourseInfo($data['course_id']);

		if (!$courseInfo || $courseInfo->state != 1)
		{
			$this->setError(Text::_('COM_TJLMS_COURSE_IS_UNPUBLISH'));

			return false;
		}

		$user = Factory::getUser((int) $data['user_id']);

		if (!$user->id)
		{
			$this->setError(Text::_('COM_TJLMS_ERROR_USER_NOT_FOUND'));

			return false;
		}

		if (array_search($courseInfo->access, $user->getAuthorisedViewLevels()) === false)
		{
			$this->setError(Text::_('COM_TJLMS_ERROR_NOT_AUTHORIZED_TO_VIEW_COURSE'));

			return false;
		}

		PluginHelper::importPlugin('tjlms');

		// Trigger all "onBeforeCourseEnrol" plugins method
		$result = Factory::getApplication()->triggerEvent('onBeforeCourseEnrol', array($data['course_id'], $data['user_id']));

		if (in_array(false, $result, true))
		{
			Factory::getApplication()->enqueueMessage(Text::_('COM_TJLMS_VIEW_COURSE_PREREQUISITE_RESTRICT_MESSAGE'), 'error');

			return false;
		}

		$params = ComponentHelper::getParams('com_tjlms');
		$data['course_type'] = $courseInfo->type;

		if (!empty($data['due_date']) && isset($courseInfo))
		{
			return $this->userAssignment($data);
		}
		elseif (isset($courseInfo->type) && $courseInfo->type == 1)
		{
			$data['state'] = isset($data['state']) ? $data['state'] : !$params->get('paid_course_admin_approval', '0', 'INT');

			if ($params->get('allow_flexi_enrolments', 0, 'INT'))
			{
				$loggedInUser = Factory::getUser();

				JLoader::import('components.com_tjlms.models.orders', JPATH_ADMINISTRATOR);
				$ordersModel = BaseDatabaseModel::getInstance('Orders', 'TjlmsModel', array('ignore_request' => true));

				$ordersModel->setState("filter.userfilter", $data['user_id']);
				$ordersModel->setState("filter.coursefilter", $data['course_id']);
				$ordersModel->setState("filter.enrollment_id", "0");
				$orderData = $ordersModel->getItems();

				if (($loggedInUser->authorise('view.own.manageenrollment', 'com_tjlms') || $loggedInUser->authorise('view.manageenrollment', 'com_tjlms'))
					&& empty($orderData))
				{
					$orderedData = $ordersModel->placeOrder($data['course_id'], $data['user_id']);

					if ($orderedData)
					{
						return true;
					}

					return false;
				}

				$enrolmentId    = (int) $this->save($data);
				$getPlanDetails = $this->tjlmsCoursesHelper->getCourseSubplans($data['course_id'], $data['user_id']);

				if (!empty($getPlanDetails))
				{
					$orderId = $this->updateEnrollmentId($data['course_id'], $enrolmentId, $data['user_id']);
					$this->tjlmsCoursesHelper->addEnrolmentHistory($orderId, $enrolmentId);

					return $this->tjlmsCoursesHelper->updateEndTimeForCourse($getPlanDetails[0]->id, $enrolmentId);
				}
				else
				{
					$this->setError(Text::_('COM_TJLMS_NO_SUBSCRIPTION_PLAN_ACCESS'));

					return false;
				}
			}
			else
			{
				return $this->paidEnrollment($data);
			}
		}
		else
		{
			$data['state'] = !$params->get('admin_approval', '0', 'INT')? $data['state']: '0';

			return $this->save($data);
		}
	}

	/**
	 * Function to update the enrolment id in the order table in case of flexi enrolment
	 *
	 * @param   integer  $courseId     The id of the course being enroled
	 *
	 * @param   integer  $enrolmentId  The id of the enrolment done
	 *
	 * @param   integer  $userId       The id of the logged user enroling to the course
	 *
	 * @return integer $orderId The order id that corresponds to the enrolment id
	 *
	 * @since 1.0.0
	 */
	public function updateEnrollmentId($courseId, $enrolmentId, $userId)
	{
		$db = Factory::getDbo();

		$query = $db->getQuery(true);

		// Fields to update.
		$fields = array(
			$db->quoteName('enrollment_id') . ' = ' . (int) $enrolmentId
		);

		// Conditions for which records should be updated.
		$conditions = array(
			$db->quoteName('course_id') . ' = ' . (int) $courseId,
			$db->quoteName('user_id') . ' = ' . (int) $userId,
			$db->quoteName('status') . ' = ' . $db->quote('C'),
		);

		$query->update($db->quoteName('#__tjlms_orders'))->set($fields)->where($conditions);

		$db->setQuery($query);

		$orderId = 0;

		if ($db->execute())
		{
			$orderModel = BaseDatabaseModel::getInstance('Orders', 'TjlmsModel', array('ignore_request' => true));
			$objOrder   = $orderModel->getOrderByEnrollmentId($enrolmentId);

			$orderId = $objOrder->id;
		}

		return $orderId;
	}

	/**
	 * Function used to user assignment to course
	 *
	 * @param   array  $data  array of data
	 *
	 * @return boolean enrollment id
	 *
	 * @since 1.0.0
	 */
	public function userAssignment($data)
	{
		JLoader::import('comtjlmsHelper', '/components/com_tjlms/helpers/main.php');
		$comtjlmsHelper = new comtjlmsHelper;
		$tjlmsCoursesHelper = new tjlmsCoursesHelper;
		$flag = 1;
		$courseInfo = $tjlmsCoursesHelper->getCourseColumn($data['course_id'], array('type', 'created_by', 'state'));

		if (!$courseInfo || $courseInfo->state != 1)
		{
			$this->setError(Text::_('COM_TJLMS_ASSIGN_COURSE_IS_UNPUBLISH'));

			return false;
		}

		if ($courseInfo->type == 1 && $data['type'] != 'reco')
		{
			$getPlanDetails = $this->tjlmsCoursesHelper->getCourseSubplans($data['course_id'], $data['user_id']);

			if (empty($getPlanDetails))
			{
				$this->setError(Text::_('COM_TJLMS_NO_SUBSCRIPTION_PLAN_ACCESS'));

				return false;
			}

			JLoader::register('TjlmsModelOrders', JPATH_ADMINISTRATOR . '/components/com_tjlms/models/orders.php');
			$ordersModel = new TjlmsModelOrders;

			$isEnrolled = $this->checkIfUserEnrolled($data['user_id'], $data['course_id']);
			$successfulOrdered = true;

			if (!$isEnrolled)
			{
				$successfulOrdered = $ordersModel->placeOrder($data['course_id'], $data['user_id']);
			}

			if (!$successfulOrdered)
			{
				$flag = 0;
				$msg = Text::_('COM_TJLMS_COURSE_ENROLL_ORDER_FAIL');
			}
		}

		if ($flag == 1)
		{
			$data['element']    = 'com_tjlms.course';
			$data['element_id'] = $data['course_id'];
			$data['type'] = ($data['type'] == 'reco' ? $data['type'] : 'assign');
			$courseUrl         = 'index.php?option=com_tjlms&view=course&id=' . $data['course_id'];

			$itemId = $comtjlmsHelper->getitemid($courseUrl);
			$data['url'] = $courseUrl . '&Itemid=' . $itemId;

			BaseDatabaseModel::addIncludePath(JPATH_ROOT . '/administrator/components/com_jlike/models');
			$JlikeModelContentForm = BaseDatabaseModel::getInstance('ContentForm', 'JlikeModel', array('ignore_request' => true));
			$contentId = $JlikeModelContentForm->getConentId($data);

			BaseDatabaseModel::addIncludePath(JPATH_ROOT . '/components/com_jlike/models');
			$jlikeModelRecommendations = BaseDatabaseModel::getInstance('Recommendations', 'JlikeModel', array('ignore_request' => true));

			if ($contentId)
			{
				$jlikeModelRecommendations->setState("content_id", $contentId);
			}

			$jlikeModelRecommendations->setState("type", "myassign");
			$jlikeModelRecommendations->setState("user_id", $data['user_id']);
			$jlikeModelRecommendations->setState('client', $data['element']);
			$jlikeModelRecommendations->setState('element_id', $data['element_id']);
			$jlikeModelRecommendations->setState('status', '1');

			$assignDetails     = $jlikeModelRecommendations->getItems();
			$data['recommend_friends'] = array($data['user_id']);
			$data['todo_id'] = 0;

			if (isset($assignDetails[0]->id))
			{
				$data['todo_id'] = $assignDetails[0]->id;
			}

			JLoader::register('TjlmsModelManageenrollments', JPATH_ADMINISTRATOR . '/components/com_tjlms/models/manageenrollments.php');
			$manageenrollmentsModel = new TjlmsModelManageenrollments;

			return $manageenrollmentsModel->updateAssignmentDate($data);
		}
	}

	/**
	 * Function used to enroll user to paid course
	 *
	 * @param   array  $data  array of data
	 *
	 * @return integer|boolean enrollment id
	 *
	 * @since 1.0.0
	 */
	public function paidEnrollment($data)
	{
		$getPlanDetails = $this->tjlmsCoursesHelper->getCourseSubplans($data['course_id'], $data['user_id']);

		if (empty($getPlanDetails))
		{
			$this->setError(Text::_('COM_TJLMS_NO_SUBSCRIPTION_PLAN_ACCESS'));

			return false;
		}

		JLoader::register('TjlmsModelOrders', JPATH_ADMINISTRATOR . '/components/com_tjlms/models/orders.php');
		$ordersModel = new TjlmsModelOrders;
		$orderedData = $ordersModel->placeOrder($data['course_id'], $data['user_id']);

		if ($orderedData)
		{
			$session    = Factory::getSession();
			$session->set('lms_orderid', 0);

			if ($orderedData['time_measure'] == 'unlimited')
			{
				$data['unlimited_plan'] = 1;
			}

			$enrollmentId = (int) $this->save($data);

			if ($enrollmentId)
			{
				$orderInfo['enrollment_id'] = $enrollmentId;
				$buymodel = BaseDatabaseModel::getInstance('buy', 'TjlmsModel', array('ignore_request' => true));
				$buymodel->updateOrderDetails($orderedData['order_id'], $orderInfo);
				$this->tjlmsCoursesHelper->updateEndTimeForCourse($orderedData['plan_id'], $enrollmentId);

				return true;
			}
		}
	}

	/**
	 * To plublish and unpublish enrollment.
	 *
	 * @param   INT  $userId    user id
	 * @param   INT  $courseId  the course id
	 * @param   INT  $state     state
	 *
	 * @return  true or false
	 *
	 * @since  1.0.0
	 */
	public function ItemState($userId, $courseId, $state)
	{
		$db = Factory::getDBO();
		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjlms/tables');
		$enrollTableObj = Table::getInstance('Enrolledusers', 'TjlmsTable', array('dbo', $db));
		$enrollTableObj->load(array('user_id' => (int) $userId, 'course_id' => (int) $courseId));

		if (!$enrollTableObj->id)
		{
			$this->setError(Text::_('COM_TJLMS_COURSE_ENROLLMENT_NOT_FOUND'));

			return false;
		}

		$enrollTableObj->state = $state;

		try
		{
			$enrollTableObj->store();
		}
		catch (RuntimeException $e)
		{
			$this->setError($e->getMessage());
		}

		$courseTableObj = Table::getInstance('Course', 'TjlmsTable', array('dbo', $db));
		$courseTableObj->load(array('id' => (int) $courseId));

		if ($state == '1' && $courseTableObj->type)
		{
			BaseDatabaseModel::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjlms/models');
			$manageenrollmentsModel = BaseDatabaseModel::getInstance('Manageenrollments', 'TjlmsModel', array('ignore_request' => true));

			$planId = $anageenrollmentsModel->getPlanId($enrollTableObj->id);

			if (!empty($planId))
			{
				$endTime = $this->tjlmsCoursesHelper->updateEndTimeForCourse($planId, $enrollTableObj->id);
			}
		}

		PluginHelper::importPlugin('system');
		Factory::getApplication()->triggerEvent('onAfterEnrolementStatusChange', array($enrollTableObj->id));

		return $enrollTableObj;
	}

	/**
	 * To check user is enrolled or not
	 *
	 * @param   INT  $userId    user id
	 * @param   INT  $courseId  the course id
	 *
	 * @return  true or false
	 *
	 * @since  1.0.0
	 */
	public function checkIfUserEnrolled($userId, $courseId)
	{
		$db = Factory::getDBO();
		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjlms/tables');
		$enrollTableObj = Table::getInstance('Enrolledusers', 'TjlmsTable', array('dbo', $db));
		$enrollTableObj->load(array('user_id' => (int) $userId, 'course_id' => (int) $courseId));

		return $enrollTableObj->id;
	}
}
