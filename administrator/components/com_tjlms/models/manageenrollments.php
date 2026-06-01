<?php
/**
 * @package     Shika
 * @subpackage  com_tjlms
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;
jimport('joomla.application.component.modellist');

use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Utilities\ArrayHelper;

/**
 * Methods supporting a list of Tjlms records.
 *
 * @since  1.0.0
 */
class TjlmsModelManageenrollments extends ListModel
{
	public $JlikeModelRecommendations;

	public $ComtjlmsHelper;
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
				'id', 'b.id',
				'state', 'a.state',
				'course_id', 'co.title',
				'user_id', 'b.user_id',
				'timestart', 'b.timestart',
				'name', 'uc.name',
				'username', 'uc.username',
				'email', 'uc.email',
				'coursefilter',
				'subuserfilter',
				'a.start_date',
				'a.due_date'
			);
		}

		$this->ComtjlmsHelper = new ComtjlmsHelper;

		$path                 = JPATH_SITE . '/components/com_tjlms/helpers/main.php';
		$this->comtjlmsHelper = '';

		if (File::exists($path))
		{
			if (!class_exists('comtjlmsHelper'))
			{
				JLoader::register('comtjlmsHelper', $path);
				JLoader::load('comtjlmsHelper');
			}

			$this->comtjlmsHelper = new comtjlmsHelper;
		}

		// Load jlike main helper to call api function for assigndetails and other
		$path = JPATH_SITE . '/components/com_jlike/helpers/main.php';
		$this->comjlikeMainHelper = "";

		if (File::exists($path))
		{
			if (!class_exists('ComjlikeMainHelper'))
			{
				JLoader::register('ComjlikeMainHelper', $path);
				JLoader::load('ComjlikeMainHelper');
			}

			$this->comjlikeMainHelper = new ComjlikeMainHelper;
		}

		// Load jlike model to call api function for assigndetails and other
		$path = JPATH_SITE . '/components/com_jlike/models/recommendations.php';
		$this->JlikeModelRecommendations = "";

		if (File::exists($path))
		{
			if (!class_exists('JlikeModelRecommendations'))
			{
				JLoader::register('JlikeModelRecommendations', $path);
				JLoader::load('JlikeModelRecommendations');
			}

			$this->JlikeModelRecommendations = new JlikeModelRecommendations;
		}

		// Load jlike admin model content form to call api to get content id
		$path = JPATH_SITE . '/administrator/components/com_jlike/models/contentform.php';

		$this->JlikeModelContentForm = "";

		if (File::exists($path))
		{
			if (!class_exists('JlikeModelContentForm'))
			{
				JLoader::register('JlikeModelContentForm', $path);
				JLoader::load('JlikeModelContentForm');
			}

			$this->JlikeModelContentForm = new JlikeModelContentForm;
		}

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
	protected function populateState($ordering = 'b.id', $direction = 'DESC')
	{
		// Initialise variables.
		$app = Factory::getApplication('administrator');

		// Load the filter state.
		$search = $app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		$published = $app->getUserStateFromRequest($this->context . '.filter.state', 'filter_published', '', 'string');
		$this->setState('filter.state', $published);

		// Filtering course
		$coursefilter = $app->getUserStateFromRequest($this->context . '.filter.coursefilter', 'coursefilter', '', 'INT');

		$this->setState('filter.coursefilter', $coursefilter);

		// Filtering course type
		$this->setState('filter.type', $app->getUserStateFromRequest($this->context . '.filter.type', 'filter_type', '', 'INT'));

		$enroll_starts = $app->getUserStateFromRequest($this->context . '.filter.enroll_starts', 'filter_enroll_starts');
		$this->setState('filter.enroll_starts', $enroll_starts);

		$enroll_ends = $app->getUserStateFromRequest($this->context . '.filter.enroll_ends', 'filter_enroll_ends');
		$this->setState('filter.enroll_ends', $enroll_ends);

		$created_by = $app->getUserStateFromRequest($this->context . '.filter.created_by', 'created_by', '', 'INT');
		$this->setState('filter.created_by', $created_by);

		$user_id = $app->getUserStateFromRequest($this->context . '.filter.user_id', 'user_id', 0, 'INT');
		$this->setState('filter.user_id', $user_id);

		// Load the parameters.
		$params = ComponentHelper::getParams('com_tjlms');
		$this->setState('params', $params);

		// List state information.
		parent::populateState($ordering, $direction);
	}

	/**
	 * Method to get a store id based on model configuration state.
	 *
	 * This is necessary because the model is used by the component and
	 * different modules that might need different sets of data or different
	 * ordering requirements.
	 *
	 * @param   int  $id  A prefix for the store id.
	 *
	 * @return    string        A store id.
	 *
	 * @since    1.0
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
	 * @return    JDatabaseQuery
	 *
	 * @since    1.0
	 */
	protected function getListQuery()
	{
		$user     = Factory::getUser();
		$olUserid = $user->id;
		$isroot   = $user->authorise('core.admin');

		// Create a new query object.

		$query = $this->_db->getQuery(true);

		// Select the required fields from the table.

		$query->select(array('ct.status as courseStatus, ct.id as courseTrackId'));
		$query->select(
					$this->getState('list.select', 'b.id, b.state, b.course_id, b.user_id, b.enrolled_on_time, b.end_time,
					co.title, co.type, uc.name, uc.username,a.start_date,a.due_date,a.id as todo_id')
		);
		$query->from($this->_db->qn('#__tjlms_enrolled_users', 'b'));
		$query->join('INNER', $this->_db->qn('#__tjlms_courses', 'co') . ' ON (
		' . $this->_db->qn('co.id') . ' = ' . $this->_db->qn('b.course_id') . ')');
		$query->join('INNER', $this->_db->qn('#__users', 'uc') . ' ON (' . $this->_db->qn('b.user_id') . ' = ' . $this->_db->qn('uc.id') . ')');
		$query->join('INNER', $this->_db->qn('#__tjlms_course_track', 'ct') . ' ON (
		' . $this->_db->qn('ct.course_id') . ' = ' . $this->_db->qn('b.course_id') .
		'AND' . $this->_db->qn('ct.user_id') . ' = ' . $this->_db->qn('b.user_id') . ')');

		$query->join('INNER', $this->_db->qn('#__categories', 'cat') . ' ON (' . $this->_db->qn('cat.id') . ' = ' . $this->_db->qn('co.catid') . ')');
		$query->join('LEFT', $this->_db->qn('#__jlike_content', 'c') . ' ON ((
		' . $this->_db->qn('c.element_id') . ' = ' . $this->_db->qn('co.id') . ' ) AND (' . $this->_db->qn('c.element') . ' = "com_tjlms.course" )) ');
		$query->join('LEFT', $this->_db->qn('#__jlike_todos', 'a') . ' ON ((
		' . $this->_db->qn('c.id') . ' = ' . $this->_db->qn('a.content_id') . ' ) AND (
		' . $this->_db->qn('a.state') . ' =1 ) AND ( ' . $this->_db->qn('a.assigned_to') . ' = ' . $this->_db->qn(
		'uc.id') . ' ) AND (' . $this->_db->qn('a.type') . ' <> "reco"))');
		$query->where($this->_db->qn('co.state') . ' = 1 AND ' . $this->_db->qn('cat.published') . ' = 1 ');

		$created_by = $this->getState('filter.created_by', 0);

		if ($created_by)
		{
			$query->where($this->_db->qn('co.created_by') . ' = ' . (int) $created_by);
		}

		$user_id = $this->getState('filter.user_id', 0);

		if ($user_id)
		{
			$query->where($this->_db->qn('b.user_id') . ' = ' . (int) $user_id);
		}

		// Filter by published state
		$published = $this->getState('filter.state');

		if (is_numeric($published))
		{
			$query->where($this->_db->qn('b.state') . ' = ' . (int) $published);
		}
		elseif ($published === '')
		{
			$query->where($this->_db->qn('b.state') . ' IN (0, 1, -2)');
		}

		$input    = Factory::getApplication()->input;
		$courseId = $input->get('course_id', '', 'INT');

		if ($courseId)
		{
			$query->where($this->_db->qn('b.state') . ' = 1 ');
		}

		// Filtering type
		$filter_type = $this->getState("filter.type");

		if (is_numeric($filter_type))
		{
			$query->where($this->_db->qn('co.type') . ' = ' . $this->_db->q($filter_type));
		}

		// Get user ID if view called from course list view to view enrolled users.
		if (!$courseId)
		{
			$courseId = $this->getState('filter.coursefilter');
		}

		// Filtering type
		if ($courseId != '')
		{
			$query->where($this->_db->qn('b.course_id') . ' = ' . $this->_db->q((int) $courseId));
		}

		// Filter by search in title
		$search = $this->getState('filter.search');

		if (!empty($search))
		{
			$search = $this->_db->q('%' . $this->_db->escape($search, true) . '%');
			$query->where('((' . $this->_db->qn('uc.name') . ' LIKE ' . $search . ' ) OR ( ' . $this->_db->qn('uc.username') . ' LIKE ' . $search . ' ) OR ( ' . $this->_db->qn('uc.email') . ' LIKE ' . $search . ' ))');
		}

		// Add the list ordering clause.
		$orderCol  = $this->state->get('list.ordering');
		$orderDirn = $this->state->get('list.direction');

		if ($orderCol && $orderDirn)
		{
			$query->order($this->_db->escape($orderCol . ' ' . $orderDirn));
		}

			// Date filters
		$enroll_starts = $this->getState('filter.enroll_starts', '');
		$enroll_starts_date = date("Y-m-d", strtotime($enroll_starts));

		$enroll_ends = $this->getState('filter.enroll_ends', '');
		$enroll_ends_date = date("Y-m-d", strtotime($enroll_ends));

		if (!empty($enroll_starts) && empty($enroll_ends))
		{
			$query->where('DATE(a.start_date)>=' . $this->_db->q($enroll_starts_date) . ' OR DATE(b.enrolled_on_time)>=' . $this->_db->q($enroll_starts_date));
		}

		if (!empty($enroll_ends) && empty($enroll_starts))
		{
			$query->where('DATE(a.due_date)<=' . $this->_db->q($enroll_ends_date));
		}

		if (!empty($enroll_starts) && !empty($enroll_ends))
		{
			if ($enroll_starts == $enroll_ends)
			{
					$query->where('(DATE(a.start_date)=' . $this->_db->q($enroll_starts_date) .
				' OR DATE(b.enrolled_on_time)=' . $this->_db->q($enroll_starts_date) . ')');
				$query->where('DATE(a.due_date)=' . $this->_db->q($enroll_ends_date));
			}
			else
			{
				$query->where('(DATE(a.start_date)>=' . $this->_db->q($enroll_starts_date) .
				' OR DATE(b.enrolled_on_time)>=' . $this->_db->q($enroll_starts_date) . ')');
				$query->where('DATE(a.due_date)<=' . $this->_db->q($enroll_ends_date));
			}
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

			$query->where($this->_db->qn('uc.id') . ' IN(' . implode(',', $hasUsers) . ')');
		}

		return $query;
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
		$items = parent::getItems();

		foreach ($items as $ind => $courseUser)
		{
			$items[$ind]->groups     = $this->getGroups($courseUser->user_id);

			$data               = array();
			$data['element']    = 'com_tjlms.course';
			$data['element_id'] = $courseUser->course_id;
			$course_url         = 'index.php?option=com_tjlms&view=course&id=' . $courseUser->course_id;

			if ($this->comtjlmsHelper)
			{
				$itemId      = $this->comtjlmsHelper->getitemid($course_url);
				$data['url'] = $course_url . '&Itemid=' . $itemId;
			}
			else
			{
				$data['url'] = $course_url;
			}

			$techjoomlaCommon = new TechjoomlaCommon;

			$items[$ind]->enrolled_on_time = $techjoomlaCommon->getDateInLocal($courseUser->enrolled_on_time);
		}

		return $items;
	}

	/**
	 * To plublish and unpublish enrolledment.
	 *
	 * @param   JRegistry  $items     The item to update.
	 * @param   JRegistry  $state     The state for the item.
	 * @param   int        $courseId  The course ID
	 *
	 * @return  true or false
	 *
	 * @since  1.0.0
	 */
	public function setItemState($items, $state, $courseId)
	{
		if (is_array($items))
		{
			Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjlms/tables');
			$enrollTableObj = Table::getInstance('Enrolledusers', 'TjlmsTable', array('dbo', $this->_db));

			foreach ($items as $id)
			{
				$enrollTableObj->load(array('id' => $id));

				if (!$enrollTableObj->id)
				{
					$this->setError(Text::_('COM_TJLMS_COURSE_ENROLLMENT_NOT_FOUND'));

					return false;
				}

				$object                = new stdClass;
				$object->id            = $enrollTableObj->id;
				$object->state         = $state;
				$object->modified_time = Factory::getDate()->toSql(true);

				if (!$this->_db->updateObject('#__tjlms_enrolled_users', $object, 'id'))
				{
					$this->setError($this->_db->getErrorMsg());

					return false;
				}

				if ($state == '1')
				{
					$plan_id = $this->getPlanId($id);

					if (!empty($plan_id))
					{
						$tjlmsCoursesHelper = new tjlmsCoursesHelper;
						$endTime            = $tjlmsCoursesHelper->updateEndTimeForCourse($plan_id, $id);
					}
				}

				PluginHelper::importPlugin('system');
				Factory::getApplication()->triggerEvent('onAfterApprovecourseEnrolement', array(
																				$id,
																				$state,
																				Factory::getUser()->id
																			)
									);
			}
		}

		return true;
	}

	/**
	 * To get subscription plan ID
	 *
	 * @param   int  $enrollmentId  The enrollment table ID
	 *
	 * @return  mixed
	 *
	 * @since  1.0.0
	 */
	public function getPlanId($enrollmentId)
	{
		try
		{
			$query = $this->_db->getQuery(true);
			$query->select($this->_db->qn(array('course_id','user_id')));
			$query->from($this->_db->qn('#__tjlms_enrolled_users'));
			$query->where($this->_db->qn('id') . ' = ' . $this->_db->q((int) $enrollmentId));
			$this->_db->setQuery($query);
			$enrollmentDetails = $this->_db->loadObject();

			$plan_id = '';

			if ($enrollmentDetails->course_id)
			{
				$tjlmsCoursesHelper = new tjlmsCoursesHelper;
				$courseInfo         = $tjlmsCoursesHelper->getCourseColumn($enrollmentDetails->course_id, array('id','type'));

				if ($courseInfo && $courseInfo->id > 0 && $courseInfo->type == 1)
				{
					// Get plan id from order item table
					$query = $this->_db->getQuery(true);
					$query->select($this->_db->qn('oi.plan_id'));
					$query->from($this->_db->qn('#__tjlms_order_items', 'oi'));
					$query->join('INNER', $this->_db->qn('#__tjlms_orders', 'o') . ' ON (' . $this->_db->qn('o.id') . ' = ' . $this->_db->qn('oi.order_id') . ')');
					$query->where($this->_db->qn('o.course_id') . ' = ' . $this->_db->q((int) $enrollmentDetails->course_id));
					$query->where($this->_db->qn('o.user_id') . ' = ' . $this->_db->q((int) $enrollmentDetails->user_id));
					$this->_db->setQuery($query);
					$plan_id = $this->_db->loadResult();
				}
			}

			return $plan_id;
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}
	}

	/**
	 * To get User Groups
	 *
	 * @param   int  $user_id  The user ID
	 *
	 * @return  string  $groups_str
	 *
	 * @since  1.0.0
	 */
	public function getGroups($user_id)
	{
		try
		{
			$query  = $this->_db->getQuery(true);
			$groups = array();
			$query->select($this->_db->qn('ug.title'));
			$query->from($this->_db->qn('#__usergroups', 'ug') . ' , ' . $this->_db->qn('#__user_usergroup_map', 'uum'));
			$query->where($this->_db->qn('uum.group_id') . ' = ' . $this->_db->qn('ug.id'));
			$query->where($this->_db->qn('uum.user_id') . ' = ' . $this->_db->q((int) $user_id));
			$this->_db->setQuery($query);
			$groups     = $this->_db->loadColumn();
			$groups_str = '';

			for ($i = 0; $i < count($groups); $i++)
			{
				$groups_str .= $groups[$i] . '<br />';
			}

			return $groups_str;
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}
	}

	/**
	 * To All courses
	 *
	 * @return  obj
	 *
	 * @since  1.0.0
	 */
	public function getAllCourses()
	{
		try
		{
			$query->select($this->_db->qn(array('tc.id', 'tc.title'), array('value', 'text')));
			$query->from($this->_db->qn('#__tjlms_courses', 'tc'));
			$this->_db->setQuery($query);

			return $this->_db->loadObjectList();
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}
	}

	/**
	 * Delet orders
	 *
	 * @param   ARRAY  $cid  array of order id
	 *
	 * @return  true
	 *
	 * @since   1.0.0
	 */
	public function delete($cid)
	{
		try
		{
			$not_allowed_del = $enrollDetails = array();
			$app             = Factory::getApplication();

			// Add Table Path
			Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjlms/tables');

			foreach ($cid as $key => $eachEnrollment)
			{
				$details = new stdClass;
				$Enrolledusers = Table::getInstance('Enrolledusers', 'TjlmsTable', array('dbo', $this->_db));
				$Enrolledusers->load(array('id' => $eachEnrollment));

				$course = Table::getInstance('course', 'TjlmsTable', array('dbo', $this->_db));
				$course->load(array('id' => $Enrolledusers->course_id));

				$subQuery = $this->_db->getQuery(true);
				$subQuery->select('lt.id');
				$subQuery->from($this->_db->qn('#__tjlms_lessons', 'l'));
				$subQuery->join('INNER', $this->_db->qn('#__tjlms_lesson_track', 'lt') . ' ON (' . $this->_db->qn('lt.lesson_id') . ' = ' . $this->_db->qn('l.id') . ')');
				$subQuery->where($this->_db->qn('lt.user_id') . ' = ' . $this->_db->q($Enrolledusers->user_id));
				$subQuery->where($this->_db->qn('l.course_id') . ' = ' . $this->_db->q($Enrolledusers->course_id));
				$subQuery->order($this->_db->qn('lt.id') . " " . 'DESC');
				$this->_db->setQuery($subQuery);
				$lessonTrack = $this->_db->loadAssocList();

				$subQuery2 = $this->_db->getQuery(true);
				$subQuery2->select('lta.id');
				$subQuery2->from($this->_db->qn('#__tjlms_lessons', 'tl'));
				$subQuery2->join('INNER', $this->_db->qn('#__tjlms_lesson_track_archive', 'lta') . ' ON (' . $this->_db->qn('lta.lesson_id') . ' = ' . $this->_db->qn('tl.id') . ')');
				$subQuery2->where($this->_db->qn('lta.user_id') . ' = ' . $this->_db->q($Enrolledusers->user_id));
				$subQuery2->where($this->_db->qn('tl.course_id') . ' = ' . $this->_db->q($Enrolledusers->course_id));
				$this->_db->setQuery($subQuery2);
				$lessonTrackArchive = $this->_db->loadAssocList();

				$lessonTrackArray = array_column($lessonTrack, 'id');
				$lessonTrackArchiveArray = array_column($lessonTrackArchive, 'id');

				$attemptIds = array_merge($lessonTrackArray, $lessonTrackArchiveArray);

				$allowed_delete = 1;

				if ($course->type == 1)
				{
					if (($key = array_search($eachEnrollment, $cid)) !== false)
					{
						$allowed_delete    = 0;
						$not_allowed_del[] = $eachEnrollment . ' : ' . $Enrolledusers->user_id . ' : ' . $course->title;

						unset($cid[$key]);
					}
				}

				if ($allowed_delete == 1)
				{
					$details->course_id =	$Enrolledusers->course_id;
					$details->user_id =	$Enrolledusers->user_id;
					$enrollDetails[$eachEnrollment] = $details;

					// Get content Id
					$data 				= array();
					$data['element']    = 'com_tjlms.course';
					$data['element_id'] = $Enrolledusers->course_id;

					$content_id         = $this->JlikeModelContentForm->getConentId($data);

					// Get assignment Details
					$this->JlikeModelRecommendations = new JlikeModelRecommendations;

					if ($content_id)
					{
						$this->JlikeModelRecommendations->setState("content_id", $content_id);
					}

					$this->JlikeModelRecommendations->setState("type", "myassign");
					$this->JlikeModelRecommendations->setState("user_id", $Enrolledusers->user_id);
					$this->JlikeModelRecommendations->filter = 0;

					$assigndetails     = $this->JlikeModelRecommendations->getItems();

					if ($assigndetails[0])
					{
						$this->comjlikeMainHelper->deleteTodo($assigndetails[0]->id);
					}
				}
			}

			if ($cid != null)
			{
				PluginHelper::importPlugin('system');

				// Plugin trigger before enrollement delete
				Factory::getApplication()->triggerEvent('onBeforeEnrolementsDelete', array($cid));

				ArrayHelper::toInteger($cid);
				$enrollmentToDelet = implode(',', $cid);

				$query = $this->_db->getQuery(true);

				// Delete all orders as selected
				$conditions = $this->_db->qn('id') . ' IN ( ' . $enrollmentToDelet . ')';

				$query->delete($this->_db->qn('#__tjlms_enrolled_users'));
				$query->where($conditions);

				$this->_db->setQuery($query);

				if (!$this->_db->execute())
				{
					$this->setError($this->_db->getErrorMsg());

					return false;
				}

				// Plugin trigger after enrollement delete
				Factory::getApplication()->triggerEvent('onAfterEnrolementsDelete', array($cid, $enrollDetails));
			}

			$attemptreportModel = BaseDatabaseModel::getInstance('Attemptreport', 'TjlmsModel', array('ignore_request' => true));
			$result = $attemptreportModel->delete($attemptIds);

			if (count($not_allowed_del))
			{
				$app->enqueueMessage(Text::sprintf(Text::_('COM_TJLMS_CAN_DELETE_ENROLLMENT'), implode('<br>', $not_allowed_del)), 'notice');
			}

			return count($cid);
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}
	}

	/**
	 * Get Enrollment Details
	 *
	 * @param   INT  $enrollmentId  id of enrollment
	 *
	 * @return  true
	 *
	 * @since   1.0.0
	 */
	public function getenrollmentdetails($enrollmentId='')
	{
		if ($enrollmentId)
		{
			// Add Table Path
			Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjlms/tables');

			// First parameter file name and second parameter is prefix
			$table = Table::getInstance('Enrolledusers', 'TjlmsTable', array('dbo', $this->_db));

			// Check if already Enrolled User
			$table->load(array('id' => (int) $enrollmentId));

			return $table;
		}

		return false;
	}

	/**
	 * Update assignment dates
	 *
	 * @param   ARRAY  $data  complete data
	 *
	 * @return  true
	 *
	 * @since   1.0.0
	 */
	public function updateAssignmentDate($data)
	{
		// Set the plugin details
		$plg_name   = 'jlike_tjlms';
		$plg_type   = 'content';
		$element    = 'com_tjlms.course';
		$element_id = $data['element_id'];
		$options    = array('element' => $element, 'element_id' => $element_id, 'plg_name' => $plg_name, 'plg_type' => $plg_type);

		if (!empty($data))
		{
			if (empty($data['start_date']))
			{
				$data['start_date'] = Factory::getDate()->toSql();
			}

			JLoader::register('JlikeModelRecommend', JPATH_SITE . '/components/com_jlike/models/recommend.php');
			JLoader::load('JlikeModelRecommend');

			$recommendModel = BaseDatabaseModel::getInstance('Recommend', 'JlikeModel');
			$res = $recommendModel->assignRecommendUsers($data, $options, $data['notify_user']);

			return $res;
		}
	}

	/**
	* check selected content follows criteria to send reminder
	*
	* @param   INT  $course_id  course ID
	* @param   INT  $user_id    user_id ID
	*
	* @return reminder Array.
	*/
	public function checkIfEnrollmentPublished($course_id, $user_id)
	{
		try
		{
			$conditions = array('eu.course_id =' . (int) $course_id, 'eu.user_id =' . (int) $user_id, 'eu.state=1');

			$query = $this->_db->getQuery(true);
			$query->select('count(*)');
			$query->from($this->_db->qn('#__tjlms_enrolled_users', 'eu'));
			$query->where($conditions);
			$this->_db->setQuery($query);
			$published = $this->_db->loadResult();

			return $published;
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}
	}


	//<!--DPE HACK-->
	/**
	* get user enrolled to course 
	*
	* @param   INT  $course_id  course ID
	*
	* @return  Array.
	*/
	public function getEnrolledUserByCourseId($courseId)
	{
		if (!$courseId)
		{
			return false;
		}

		$db = Factory::getDbo();
		$query = $db->getQuery(true);

		$query->select('user_id')
		    ->from($db->quoteName('#__tjlms_enrolled_users'))
		    ->where($db->quoteName('course_id') . ' = ' . $db->quote($courseId));

		$db->setQuery($query);
		return $results = $db->loadAssocList();
	}
}
