<?php
/**
 * @package    Shika
 * @author     TechJoomla | <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */
// No direct access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Form\FormHelper;
use Joomla\Data\DataObject;

JLoader::import('com_tjreports.models.reports', JPATH_SITE . '/components');

/**
 * User report plugin of TJReport
 *
 * @since  1.0.0
 */
class TjreportsModelDpeUserreport extends TjreportsModelReports
{
	protected $default_order = 'eu.user_id';

	protected $default_order_dir = 'ASC';

	public $showSearchResetButton = false;

	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see     BaseDatabaseModel
	 * @since   1.6
	 */
	public function __construct($config = array())
	{
		JLoader::import('administrator.components.com_tjlms.helpers.tjlms', JPATH_SITE);

		$lang = Factory::getLanguage();
		$base_dir = JPATH_SITE . '/administrator';
		$lang->load('com_tjlms', $base_dir);
		$lmsparams = ComponentHelper::getParams('com_tjlms');
		$showNameOrUsername = $lmsparams->get('show_user_or_username', 'name');

		if ($showNameOrUsername == 'name')
		{
			$th = 'COM_TJLMS_ENROLMENT_USER_NAME';
		}
		else
		{
			$th = 'COM_TJLMS_REPORT_USERUSERNAME';
		}

		$this->columns = array(
			'eu.user_id' => array('title' => 'COM_TJLMS_ENROLMENT_USER_ID', 'table_column' => 'eu.user_id'),
			$showNameOrUsername => array('table_column' => 'u.' . $showNameOrUsername, 'title' => $th),
			'email' => array('table_column' => 'u.email', 'title' => 'COM_TJLMS_ENROLMENT_USER_EMAIL'),
			'block' => array('table_column' => '', 'title' => 'COM_TJLMS_ENROLMENT_USER_BLOCKED'),
			'usergroup' => array('title' => 'COM_TJLMS_REPORT_USERGROUP', 'disable_sorting' => true),
			'enrolledUsers' => array('title' => 'COM_TJLMS_ENROLMENT_TOTAL_COURSES_ENROLLED', 'table_column' => ''),
			'pendingEnrollment' => array('title' => 'COM_TJLMS_ENROLMENT_TOTAL_PENDING_ENROLLED', 'table_column' => ''),
			'completedCourses' => array('title' => 'COM_TJLMS_ENROLMENT_TOTAL_COURSES_COMPLETED', 'table_column' => ''),
			'inCompletedCourses' => array('title' => 'COM_TJLMS_ENROLMENT_TOTAL_COURSES_INCOMPLETED', 'disable_sorting' => true),
			'timeSpentOnLesson' => array('title' => 'COM_TJLMS_REPORT_TIMESPENT', 'table_column' => ''),
			'lastVisitDate' => array('table_column' => 'u.lastvisitDate', 'title' => 'COM_TJLMS_USER_LAST_VISIT_DATE'),
			'registerDate' => array('table_column' => 'u.registerDate', 'title' => 'COM_TJLMS_USER_REGISTRATION_DATE'),
			'likeCount' => array('title' => 'COM_TJLMS_LIKES_CNT', 'table_column' => ''),
			'dislikeCount' => array('title' => 'COM_TJLMS_DISLIKES_CNT', 'table_column' => ''),
			'commentsCount' => array('title' => 'COM_TJLMS_COMMENTS_CNT', 'table_column' => ''),
			'notesCount' => array('title' => 'COM_TJLMS_NOTES_CNT', 'table_column' => ''),
			'certCount' => array('title' => 'COM_TJLMS_CERTIFICATES_CNT', 'table_column' => ''),
			'recommendRcvCount' => array('title' => 'COM_TJLMS_RECO_RCV_CNT', 'table_column' => ''),
			'recommendMadeCount' => array('title' => 'COM_TJLMS_RECO_MADE_CNT', 'table_column' => ''),
			'goalCount' => array('title' => 'COM_TJLMS_GOAL_CNT', 'table_column' => ''),
			'assignCount' => array('title' => 'COM_TJLMS_ASSIGN_CNT', 'table_column' => ''),
			'completedAssignment' => array('title' => 'COM_TJLMS_ASSIGN_COMPLETE_CNT', 'table_column' => ''),
			'incompleteAssignment' => array('title' => 'COM_TJLMS_ASSIGN_INCOMPLETE_CNT', 'table_column' => '')
		);

		parent::__construct($config);
	}

	/**
	 * Get client of this plugin
	 *
	 * @return STRING Client
	 *
	 * @since   2.0
	 * */
	public function getPluginDetail()
	{
		$detail = array('client' => 'com_tjlms', 'title' => Text::_('PLG_TJREPORTS_USERREPORT_TITLE'));

		return $detail;
	}

	/**
	 * Get style for left sidebar menu
	 *
	 * @return ARRAY Keys of data
	 *
	 * @since   2.0
	 * */
	public function getStyles()
	{
		return array(
			Uri::root(true) . '/media/com_tjlms/css/tjlms_backend.css',
			Uri::root(true) . '/media/com_tjlms/font-awesome/css/font-awesome.min.css'
		);
	}

	/**
	 * Function to get the user filter
	 *
	 * @return  object
	 *
	 * @since 1.0.0
	 */
	public function getUserFilter()
	{
		$db = Factory::getDbo();

		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_multiagency/models');
		$MultiagencyModel = BaseDatabaseModel::getInstance('Multiagency', 'MultiagencyModel', array('ignore_request' => true));
		$params = ComponentHelper::getParams('com_multiagency');
		$memberRole = $params->get('member_role_id', '0', 'INT');
		$agencies = $MultiagencyModel->getAllocatedAgencies(Factory::getUser()->id, array($memberRole));
		$agencyId = array();
		$team = array();

		foreach ($agencies as $agency)
		{
			$agencyId[] = $agency->id;
		}

		if (count($agencyId) > 0)
		{
			$query = $db->getQuery(true);
			$query->select('distinct(user_id)');
			$query->from($db->quoteName('#__tjsu_users'));
			$query->where($db->qn('client') . '= "com_multiagency"');
			$query->where($db->quoteName('client_id') . " in ( " . implode(',', $agencyId) . ")");
			$db->setQuery($query);
			$team = $db->loadColumn();
		}

		if (count($team) > 0)
		{
			$query = $db->getQuery(true);
			$query->select('u.id,u.name');
			$query->from('#__users as u');
			$query->where($db->qn('u.block') . ' = 0');
			$query->where('u.id in (' . implode(',', $team) . ')');
			$db->setQuery($query);
			$users = $db->loadObjectList();
		}

		$userFilter[] = JHTML::_('select.option', '', Text::_('COM_TJREPORTS_FILTER_SELECT_USER'));

		if (!empty($users))
		{
			foreach ($users as $eachUser)
			{
				$userFilter[] = JHTML::_('select.option', $eachUser->id, $eachUser->name);
			}
		}

		return $userFilter;
	}

	/**
	 * Function to get the course filter
	 *
	 * @return  object
	 *
	 * @since 1.0.0
	 */
	public function getCourse()
	{
		FormHelper::addFieldPath(JPATH_ADMINISTRATOR . '/components/com_tjlms/models/fields/');
		$Courses = FormHelper::loadFieldType('courses', false);
		$courses = $Courses->getOptionsExternally();

		$helperPath = JPATH_ADMINISTRATOR . '/components/com_tjlms/helpers/tjlms.php';
		JLoader::register('TjlmsHelper', $helperPath);
		JLoader::load('TjlmsHelper');
		$i = 0;
		$coursIds = array();

		foreach ($courses as $course)
		{
			$canEnroll = TjlmsHelper::canManageCourseEnrollment($course->value);

			if ($canEnroll != 1)
			{
				unset($courses[$i]);
			}
			else
			{
				$coursIds[] = $course->value;
			}

			$i++;
		}

		return $coursIds;
	}

	/**
	 * Function to get the userName filter
	 *
	 * @return  object
	 *
	 * @since 1.0.0
	 */
	public function getUserNameFilter()
	{
		$db = Factory::getDbo();

		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_multiagency/models');
		$MultiagencyModel = BaseDatabaseModel::getInstance('Multiagency', 'MultiagencyModel', array('ignore_request' => true));
		$params = ComponentHelper::getParams('com_multiagency');
		$memberRole = $params->get('member_role_id', '0', 'INT');
		$agencies = $MultiagencyModel->getAllocatedAgencies($userId, array($memberRole));
		$agencyId = array();
		$team = array();

		foreach ($agencies as $agency)
		{
			$agencyId[] = $agency->id;
		}

		if (count($agencyId) > 0)
		{
			$query = $db->getQuery(true);
			$query->select('distinct(user_id)');
			$query->from($db->quoteName('#__tjsu_users'));
			$query->where($db->qn('client') . '= "com_multiagency"');
			$query->where($db->quoteName('client_id') . " in ( " . implode(',', $agencyId) . ")");
			$db->setQuery($query);
			$team = $db->loadColumn();
		}

		if (count($team) > 0)
		{
			$query = $db->getQuery(true);
			$query->select('u.id,u.username');
			$query->from('#__users as u');
			$query->where($db->qn('u.block') . ' = 0');
			$query->where('u.id in (' . implode(',', $team) . ')');
			$db->setQuery($query);
			$users = $db->loadObjectList();
		}

		$userFilter[] = JHTML::_('select.option', '', Text::_('COM_TJREPORTS_FILTER_SELECT_USER'));

		if (!empty($users))
		{
			foreach ($users as $eachUser)
			{
				$userFilter[] = JHTML::_('select.option', $eachUser->id, $eachUser->username);
			}
		}

		return $userFilter;
	}

	/**
	 * Create an array of filters
	 *
	 * @return    void
	 *
	 * @since    1.0
	 */
	public function displayFilters()
	{
		$reportOptions  = TjlmsHelper::getReportFilterValues($this, $selected, $created_by, $myTeam);

		JLoader::import('components.com_tjlms.models.reports', JPATH_ADMINISTRATOR);
		$TjlmsModelReports 	= new TjlmsModelReports;
		$lessonFilter 		= $TjlmsModelReports->getLessonFilter($created_by);
		$lmsparams = ComponentHelper::getParams('com_tjlms');
		$showNameOrUsername = $lmsparams->get('show_user_or_username', 'name');
		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_multiagency/models');
		$multiagencyModel = BaseDatabaseModel::getInstance('Multiagency', 'MultiagencyModel');
		$agencyFilter 		= $multiagencyModel->getAgencyFilter();

		$nameUserNameFilter 		= $this->getUserFilter($myTeam);

		$activeArray = array();
		$activeArray[] = JHTML::_('select.option', '', Text::_('COM_TJLMS_COURSES_TYPE_FILTER'));
		$activeArray[] = JHTML::_('select.option', '0', Text::_('COM_TJLMS_YES'));
		$activeArray[] = JHTML::_('select.option', '1', Text::_('COM_TJLMS_NO'));

		$dispFilters = array(
			array(
				'eu.user_id' => array(
					'search_type' => 'text', 'type' => 'equal', 'searchin' => 'eu.user_id'
				),
				$showNameOrUsername => array(
					'search_type' => 'select', 'select_options' => $nameUserNameFilter, 'type' => 'equal', 'searchin' => 'u.id'
				),
				'email' => array(
					'search_type' => 'text', 'searchin' => 'u.email'
				),
				'block' => array(
					'search_type' => 'select', 'select_options' => $activeArray, 'type' => 'equal', 'searchin' => 'u.block'
				),
				'usergroup' => array(
					'search_type' => 'select', 'select_options' => $this->getUserGroupFilter()
				)
			),
				array(
						'agency' => array(
								'search_type' => 'select', 'select_options' => $agencyFilter, 'type' => 'equal', 'searchin' => 'tjc.client_id'
						),
				)
		);

		// Need to check for following condition, this is a temp fix

		/*
		if (count($reportOptions) > 1)
		{
			$dispFilters[1]['report_filter'] = array(
					'search_type' => 'select', 'select_options' => $reportOptions
				);
		}
		*/

		return $dispFilters;
	}

	/**
	 * Method to get a DataObjectbaseQuery object for retrieving the data set from a database.
	 *
	 * @return  DataObjectbaseQuery  A DataObjectbaseQuery object to retrieve the data set.
	 *
	 * @since   1.6
	 */
	protected function getListQuery()
	{
		$db        = $this->_db;
		$query     = parent::getListQuery();
		$colToshow = $this->getState('colToshow');
		$filters = $this->getState('filters');

		$createdByClause = $myTeamClause = false;
		$hasUsers = array();
		$user     = Factory::getUser();
		$userId   = $user->id;

		if ((int) $filters['report_filter'] === 1 || (int) $filters['report_filter'] === 2)
		{
			$createdByClause = true;
		}
		elseif ((int) $filters['report_filter'] === -1)
		{
			$hasUsers = TjlmsHelper::getSubusers();
			$myTeamClause = true;
		}

		$query->select($db->quoteName('eu.user_id', 'user_id'));
		$query->from($db->quoteName('#__tjlms_enrolled_users', 'eu'));
		$query->join('INNER', $db->quoteName('#__tjlms_courses', 'c') . ' ON (' . $db->quoteName('c.id') . ' = ' . $db->quoteName('eu.course_id') . ')');
		$query->join('INNER', $db->quoteName('#__users', 'u') . ' ON (' . $db->quoteName('eu.user_id') . ' = ' . $db->quoteName('u.id') . ')');
		$query->join('INNER', $db->quoteName('#__tj_cluster_nodes', 'tjcn') . ' ON (' .
		$db->quoteName('eu.user_id') . ' = ' . $db->quoteName('tjcn.user_id') . ')');
		$query->join('INNER', $db->quoteName('#__tj_clusters', 'tjc') . ' ON (' .
		$db->quoteName('tjcn.cluster_id') . ' = ' . $db->quoteName('tjc.id') . ')');
		$reportId = $this->getState('reportId');
		$viewAll = $this->checkpermissions($reportId);

		$courses = $this->getCourse();
		$query->where($db->qn('u.block') . ' = 0');
		$query->where('c.id IN(' . implode(',', $courses) . ')');

		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_multiagency/models');
		$MultiagencyModel = BaseDatabaseModel::getInstance('Multiagency', 'MultiagencyModel', array('ignore_request' => true));
		$params = ComponentHelper::getParams('com_multiagency');
		$memberRole = $params->get('member_role_id', '0', 'INT');
		$agencies = $MultiagencyModel->getAllocatedAgencies($userId, array($memberRole));
		$clients = array();

		foreach ($agencies as $agency)
		{
			$clients[] = $agency->id;
		}

		if (count($clients))
		{
			$query->where('tjc.client_id IN(' . implode(',', $clients) . ')');
		}
		elseif (empty(count($clients)))
		{
			$query->where('lt.user_id=0');
		}

		if ($createdByClause)
		{
			$query->where('c.created_by = ' . (int) $userId);
		}
		elseif ($myTeamClause && $hasUsers)
		{
			$query->where('eu.user_id IN(' . implode(',', $hasUsers) . ')');
		}

		if (in_array('block', $colToshow))
		{
			$query->select('IF(u.block=1,"' . Text::_('JNO') . '","' . Text::_('JYES') . '") AS block');
		}

		if (array_intersect(array('enrolledUsers','inCompletedCourses', 'pendingEnrollment'), $colToshow))
		{
			$query->select('sum(IF(eu.state="1", 1, 0)) as enrolledUsers');
			$query->select('sum(IF(eu.state="0", 1, 0)) as pendingEnrollment');
		}

		if (array_intersect(array('completedCourses','inCompletedCourses'), $colToshow))
		{
			$subQuery = $db->getQuery(true);
			$subQuery->select('COUNT(ct.id) as completedCourses');
			$subQuery->from($db->quoteName('#__tjlms_course_track') . ' as ct');
			$subQuery->join('INNER', $db->quoteName('#__tjlms_courses') . ' as cc ON cc.id=ct.course_id');
			$subQuery->where($db->quoteName('ct.user_id') . ' = ' . $db->quoteName('eu.user_id'));
			$subQuery->where($db->quoteName('ct.status') . ' = "C"');
			$subQuery->where($db->quoteName('cc.state') . ' = 1');

			if ($createdByClause )
			{
				$subQuery->where('cc.created_by = ' . (int) $userId);
			}

			$query->select('(' . $subQuery . ') as completedCourses');
		}

		if (in_array('timeSpentOnLesson', $colToshow))
		{
			$subQuery = $db->getQuery(true);
			$subQuery->select('SEC_TO_TIME(SUM(TIME_TO_SEC(time_spent)))');
			$subQuery->from($db->quoteName('#__tjlms_lesson_track') . ' as lt');
			$subQuery->where($db->quoteName('lt.user_id') . ' = ' . $db->quoteName('eu.user_id'));

			if ($createdByClause )
			{
				$subQuery->join('INNER', $db->quoteName('#__tjlms_lessons', 'l')
					. ' ON (' . $db->quoteName('lt.lesson_id') . ' = ' . $db->quoteName('l.id') . ')');
				$subQuery->join('INNER', $db->quoteName('#__tjlms_courses', 'tsc')
					. ' ON (' . $db->quoteName('l.course_id') . ' = ' . $db->quoteName('tsc.id') . ')');
				$subQuery->where('tsc.created_by = ' . (int) $userId);
			}

			$query->select('(' . $subQuery . ') as timeSpentOnLesson');
		}

		if (in_array('usergroup', $colToshow))
		{
			$subQuery = $db->getQuery(true);
			$subQuery->select('ugm.group_id');
			$subQuery->from($db->quoteName('#__user_usergroup_map') . ' as ugm');
			$subQuery->where($db->quoteName('ugm.user_id') . ' = ' . $db->quoteName('eu.user_id'));
			$query->select('(SELECT GROUP_CONCAT(ug.title SEPARATOR ", ") from  #__usergroups ug where ug.id IN(' . $subQuery . ')) as usergroup');

			if (isset($filters['usergroup']) && !empty($filters['usergroup']))
			{
				$subQuery = $db->getQuery(true);
				$subQuery->select('ugm.user_id');
				$subQuery->from($db->quoteName('#__user_usergroup_map') . ' as ugm');
				$subQuery->where($db->quoteName('ugm.group_id') . ' = ' . (int) $filters['usergroup']);
				$query->where('eu.user_id IN(' . $subQuery . ')');
			}
		}

		if (array_intersect(array('likeCount'), $colToshow))
		{
			$subQuery = $db->getQuery(true);
			$subQuery->select('COUNT(`like`) as likeCount');
			$subQuery->from($db->quoteName('#__jlike_likes') . ' as jl');
			$subQuery->where($db->quoteName('jl.userid') . ' = ' . $db->quoteName('eu.user_id'));
			$subQuery->where($db->quoteName('jl.like') . ' = ' . $db->quote(1));
			$query->select('(' . $subQuery . ') as likeCount');
		}

		if (array_intersect(array('dislikeCount'), $colToshow))
		{
			$subQuery = $db->getQuery(true);
			$subQuery->select('COUNT(`dislike`) as dislikeCount');
			$subQuery->from($db->quoteName('#__jlike_likes') . ' as jl');
			$subQuery->where($db->quoteName('jl.userid') . ' = ' . $db->quoteName('eu.user_id'));
			$subQuery->where($db->quoteName('jl.dislike') . ' = ' . $db->quote(1));
			$query->select('(' . $subQuery . ') as dislikeCount');
		}

		if (array_intersect(array('commentsCount'), $colToshow))
		{
			$subQuery = $db->getQuery(true);
			$subQuery->select('COUNT(*) as commentsCount');
			$subQuery->from($db->quoteName('#__jlike_annotations') . ' as ja');
			$subQuery->where($db->quoteName('ja.user_id') . ' = ' . $db->quoteName('eu.user_id'));
			$subQuery->where($db->quoteName('ja.note') . ' = ' . $db->quote(0));
			$query->select('(' . $subQuery . ') as commentsCount');
		}

		if (array_intersect(array('notesCount'), $colToshow))
		{
			$subQuery = $db->getQuery(true);
			$subQuery->select('COUNT(*) as notesCount');
			$subQuery->from($db->quoteName('#__jlike_annotations') . ' as ja');
			$subQuery->where($db->quoteName('ja.user_id') . ' = ' . $db->quoteName('eu.user_id'));
			$subQuery->where($db->quoteName('ja.note') . ' = ' . $db->quote(1));
			$query->select('(' . $subQuery . ') as notesCount');
		}

		if (array_intersect(array('certCount'), $colToshow))
		{
			$subQuery = $db->getQuery(true);
			$subQuery->select('COUNT(*) as certCount');
			$subQuery->from($db->quoteName('#__tjlms_certificate') . ' as tc');
			$subQuery->where($db->quoteName('tc.user_id') . ' = ' . $db->quoteName('eu.user_id'));
			$query->select('(' . $subQuery . ') as certCount');
		}

		if (array_intersect(array('recommendRcvCount'), $colToshow))
		{
			$subQuery = $db->getQuery(true);
			$subQuery->select('COUNT(*) as recommendRcvCount');
			$subQuery->from($db->quoteName('#__jlike_todos') . ' as jt');
			$subQuery->where($db->quoteName('jt.assigned_to') . ' = ' . $db->quoteName('eu.user_id'));
			$subQuery->where($db->quoteName('jt.type') . ' = ' . $db->quote('reco'));
			$query->select('(' . $subQuery . ') as recommendRcvCount');
		}

		if (array_intersect(array('recommendMadeCount'), $colToshow))
		{
			$subQuery = $db->getQuery(true);
			$subQuery->select('COUNT(*) as recommendMadeCount');
			$subQuery->from($db->quoteName('#__jlike_todos') . ' as jt');
			$subQuery->where($db->quoteName('jt.assigned_by') . ' = ' . $db->quoteName('eu.user_id'));
			$subQuery->where($db->quoteName('jt.type') . ' = ' . $db->quote('reco'));
			$query->select('(' . $subQuery . ') as recommendMadeCount');
		}

		if (array_intersect(array('goalCount'), $colToshow))
		{
			$subQuery = $db->getQuery(true);
			$subQuery->select('COUNT(*) as goalCount');
			$subQuery->from($db->quoteName('#__jlike_todos') . ' as jt');
			$subQuery->where($db->quoteName('jt.assigned_by') . ' = ' . $db->quoteName('eu.user_id'));
			$subQuery->where($db->quoteName('jt.type') . ' = ' . $db->quote('assign'));
			$subQuery->where($db->quoteName('jt.assigned_by') . ' = ' . $db->quoteName('jt.assigned_to'));
			$query->select('(' . $subQuery . ') as goalCount');
		}

		if (array_intersect(array('assignCount'), $colToshow))
		{
			$subQuery = $db->getQuery(true);
			$subQuery->select('COUNT(*) as assignCount');
			$subQuery->from($db->quoteName('#__jlike_todos') . ' as jt');
			$subQuery->where($db->quoteName('jt.assigned_by') . ' = ' . $db->quoteName('eu.user_id'));
			$subQuery->where($db->quoteName('jt.type') . ' = ' . $db->quote('assign'));
			$subQuery->where($db->quoteName('jt.assigned_by') . ' <> ' . $db->quoteName('jt.assigned_to'));
			$query->select('(' . $subQuery . ') as assignCount');
		}

		if (array_intersect(array('completedAssignment'), $colToshow))
		{
			$subQuery = $db->getQuery(true);
			$subQuery->select('COUNT(ct.status) as completedAssignment');
			$subQuery->from($db->quoteName('#__jlike_todos') . ' as jt');
			$subQuery->join('INNER', $db->quoteName('#__jlike_content', 'jc')
					. ' ON (' . $db->quoteName('jt.content_id') . ' = ' . $db->quoteName('jc.id') . ')');
			$subQuery->join('INNER', $db->quoteName('#__tjlms_course_track', 'ct')
					. ' ON (' . $db->quoteName('ct.course_id') . ' = ' . $db->quoteName('jc.element_id') . ')');
			$subQuery->where($db->quoteName('ct.user_id') . ' = ' . $db->quoteName('eu.user_id'));
			$subQuery->where($db->quoteName('jc.element') . ' = ' . $db->quote('com_tjlms.course'));
			$subQuery->where($db->quoteName('ct.status') . ' = ' . $db->quote('C'));
			$subQuery->where($db->quoteName('jt.assigned_by') . ' <> ' . $db->quoteName('jt.assigned_to'));

			$query->select('(' . $subQuery . ') as completedAssignment');
		}

		if (array_intersect(array('incompleteAssignment'), $colToshow))
		{
			$subQuery = $db->getQuery(true);
			$subQuery->select('COUNT(*) as incompleteAssignment');
			$subQuery->from($db->quoteName('#__jlike_todos') . ' as jt');
			$subQuery->join('INNER', $db->quoteName('#__jlike_content', 'jc')
					. ' ON (' . $db->quoteName('jt.content_id') . ' = ' . $db->quoteName('jc.id') . ')');
			$subQuery->join('INNER', $db->quoteName('#__tjlms_course_track', 'ct')
					. ' ON (' . $db->quoteName('ct.course_id') . ' = ' . $db->quoteName('jc.element_id') . ')');
			$subQuery->where($db->quoteName('ct.user_id') . ' = ' . $db->quoteName('eu.user_id'));
			$subQuery->where($db->quoteName('jt.assigned_to') . ' = ' . $db->quoteName('eu.user_id'));
			$subQuery->where($db->quoteName('jc.element') . ' = ' . $db->quote('com_tjlms.course'));
			$subQuery->where($db->quoteName('ct.status') . ' <> ' . $db->quote('C'));
			$subQuery->where($db->quoteName('jt.assigned_by') . ' <> ' . $db->quoteName('jt.assigned_to'));
			$query->select('(' . $subQuery . ') as incompleteAssignment');
		}

		$query->group('eu.user_id');

		return $query;
	}

	/**
	 * Method to get an array of data items.
	 *
	 * @return  mixed  An array of data items on success, false on failure.
	 *
	 * @since   1.6
	 */
	public function getItems()
	{
		// Add additional columns which are not part of the query
		$items = parent::getItems();

		$colToshow = $this->getState('colToshow');
		$lmsparams = ComponentHelper::getParams('com_tjlms');
		$dateFormatShow = $lmsparams->get('date_format_show', 'Y-m-d H:i:s');

		jimport('techjoomla.common');
		$tjCommon = new TechjoomlaCommon;

		foreach ($items as &$item)
		{
			if (in_array('inCompletedCourses', $colToshow))
			{
				$item['inCompletedCourses'] = $item['enrolledUsers'] - $item['completedCourses'];
			}

			if (empty($item['lastVisitDate']) || $item['lastVisitDate'] == '0000-00-00 00:00:00')
			{
				$item['lastVisitDate'] = ' - ';
			}
			else
			{
				$item['lastVisitDate'] = $tjCommon->getDateInLocal($item['lastVisitDate'], 0, $dateFormatShow);
			}

			if (empty($item['registerDate']) || $item['registerDate'] == '0000-00-00 00:00:00')
			{
				$item['registerDate'] = ' - ';
			}
			else
			{
				$item['registerDate'] = $tjCommon->getDateInLocal($item['registerDate'], 0, $dateFormatShow);
			}
		}

		return $items;
	}
}
