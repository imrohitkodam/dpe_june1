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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\Data\DataObject;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\HTML\HTMLHelper;

JLoader::import('com_tjreports.models.reports', JPATH_SITE . '/components');

/**
 * Lesson report plugin of TJReport
 *
 * @since  1.0.0
 */
class TjreportsModelDpeLessonreport extends TjreportsModelReports
{
	protected $default_order = 'name';

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

		$this->columns = array(
			'id' => array('table_column' => 'l.id', 'title' => 'COM_TJLMS_LESSONREPORT_ID'),
			'name' => array('table_column' => 'l.title', 'title' => 'COM_TJLMS_LESSONREPORT_NAME'),
			'lessonFormat' => array('table_column' => 'l.format', 'title' => 'COM_TJLMS_LESSONREPORT_FORMAT'),
			'courseTitle' => array('table_column' => 'c.title', 'title' => 'COM_TJLMS_LESSONREPORT_COURSENAME'),
			'username' => array('table_column' => 'u.username', 'title' => 'COM_TJLMS_REPORT_USERUSERNAME'),
			'usergroup' => array('title' => 'COM_TJLMS_REPORT_USERGROUP', 'disable_sorting' => true),
			'timestart' => array('title' => 'COM_TJLMS_LESSONREPORT_STARTDATE'),
			'timeend' => array('title' => 'COM_TJLMS_LESSONREPORT_ENDDATE'),
			'timeSpentOnLesson' => array('title' => 'COM_TJLMS_LESSONREPORT_TIMESPENT'),
			'idealTime' => array('table_column' => 'l.ideal_time', 'title' => 'COM_TJLMS_ATTEMPTREPORT_IDEAL_TIME'),
			'score' => array('title' => 'COM_TJLMS_LESSONREPORT_SCORE', 'disable_sorting' => true),
			'status' => array('table_column' => 'lt.lesson_status','title' => 'COM_TJLMS_REPORT_LESSON_STATUS'),
			'attemptsAllowed' => array('table_column' => 'l.no_of_attempts', 'title' => 'COM_TJLMS_LESSONREPORT_ALLOWEDATTEMPTS'),
			'attemptsDone' => array('title' => 'COM_TJLMS_LESSONREPORT_ATTEMPTSMADE'),
			'attemptsGrade' => array('table_column' => 'l.attempts_grade', 'title' => 'COM_TJLMS_LESSONREPORT_GRADINGMETHOD'),
			'considerMarks' => array('table_column' => 'l.consider_marks', 'title' => 'COM_TJLMS_LESSONREPORT_COMSIDERMARKS')
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
		$detail = array('client' => 'com_tjlms', 'title' => Text::_('PLG_DPE_LESSONREPORT_TITLE'));

		return $detail;
	}

	/**
	 * Add stylesheets
	 *
	 * @return ARRAY Styles url
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

		jimport('techjoomla.common');
		JLoader::import('components.com_tjlms.helpers.tracking', JPATH_SITE);
		JLoader::import('components.com_tjlms.models.reports', JPATH_ADMINISTRATOR);

		$lmsparams = ComponentHelper::getParams('com_tjlms');
		$dateFormatShow = $lmsparams->get('date_format_show', 'Y-m-d H:i:s');

		$tjCommon 		= new TechjoomlaCommon;
		$trackingHelper = new ComtjlmstrackingHelper;

		$colToshow		= $this->getState('colToshow');

		foreach ($items as &$item)
		{
			if (in_array('timestart', $colToshow))
			{
				if ($item['timestart'] == '0000-00-00 00:00:00')
				{
					$item['timestart'] = '-';
				}
				else
				{
					$item['timestart'] = $tjCommon->getDateInLocal($item['timestart'], 0, $dateFormatShow);
				}
			}

			if (in_array('timeend', $colToshow))
			{
				if ($item['timeend'] == '0000-00-00 00:00:00')
				{
					$item['timeend'] = '-';
				}
				else
				{
					$item['timeend'] = $tjCommon->getDateInLocal($item['timeend'], 0, $dateFormatShow);
				}
			}

			if (in_array('attemptsAllowed', $colToshow))
			{
				if ($item['attemptsAllowed'] == 0)
				{
					$item['attemptsAllowed'] = Text::_('COM_TJLMS_UNLIMITED');
				}
			}

			if (in_array('considerMarks', $colToshow))
			{
				if ($item['considerMarks'] == 0)
				{
					$item['considerMarks'] = Text::_('JNO');
				}
				else
				{
					$item['considerMarks'] = Text::_('JYES');
				}
			}

			if (array_intersect(array('status', 'score'), $colToshow))
			{
				$lesson 		= new stdclass;
				$lesson->id 	= $item['id'];
				$lesson->attempts_grade = $item['attempts_grade'];
				$lesson->format = $item['lessonFormat'];

				$result           = $trackingHelper->getLessonattemptsGrading($lesson, $item['user_id']);
				$item['score']    = isset($result->score) ? floor($result->score) : ' - ';
			}

			if (in_array('attemptsGrade', $colToshow))
			{
				switch ($item['attemptsGrade'])
				{
					case '0':
							$item['attemptsGrade'] = Text::_('COM_TJLMS_HIGHEST_ATTEMPT');
							break;
					case '1':
							$item['attemptsGrade'] = Text::_('COM_TJLMS_AVERAGE_ATTEMPT');
							break;
					case '2':
							$item['attemptsGrade'] = Text::_('COM_TJLMS_FIRST_ATTEMPT');
							break;
					case '3':
							$item['attemptsGrade'] = Text::_('COM_TJLMS_LAST_COMPLETED_ATTEMPT');
							break;
				}
			}

			if (in_array('timeSpentOnLesson', $colToshow))
			{
				if ($item['timeSpentOnLesson'] == '00:00:00')
				{
					$item['timeSpentOnLesson'] = '-';
				}
			}
		}

		$items = $this->sortCustomColumns($items);

		return $items;
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
		$colToshow = (array) $this->getState('colToshow');
		$filters = $this->getState('filters');
		$createdByClause = $myTeamClause = false;
		$hasUsers = array();
		$user     = Factory::getUser();
		$userId   = $user->id;

		if ((int) $filters['report_filter'] === 1)
		{
			$createdByClause = true;
		}
		elseif ((int) $filters['report_filter'] === -1)
		{
			$hasUsers = TjlmsHelper::getSubusers();
			$myTeamClause = true;
		}

		$query->select('COUNT(lt.attempt) as attemptsDone, l.format lessonFormat');
		$query->select('min(timestart) timestart,max(timeend) timeend');
		$query->select('SEC_TO_TIME(SUM(TIME_TO_SEC(time_spent))) as timeSpentOnLesson');
		$query->select('l.attempts_grade,l.ideal_time,lt.lesson_status');

		// Must have columns to get details of non linked data like completion
		$query->select(array('l.id', 'lt.user_id', 'attempts_grade'));
		$query->from($db->quoteName('#__tjlms_lesson_track', 'lt'));
		$query->join('INNER', $db->quoteName('#__tjlms_lessons', 'l') . ' ON (' . $db->quoteName('lt.lesson_id') . ' = ' . $db->quoteName('l.id') . ')');

		$query->join('INNER', $db->quoteName('#__tj_cluster_nodes', 'tjcn') . ' ON (' .
		$db->quoteName('lt.user_id') . ' = ' . $db->quoteName('tjcn.user_id') . ')');
		$query->join('INNER', $db->quoteName('#__tj_clusters', 'tjc') . ' ON (' .
		$db->quoteName('tjcn.cluster_id') . ' = ' . $db->quoteName('tjc.id') . ')');

		$courses = $this->getCourse();
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

		if (in_array('courseTitle', $colToshow) || $createdByClause)
		{
			$query->join('INNER', $db->quoteName('#__tjlms_courses', 'c') . 'ON (' .
			$db->quoteName('l.course_id') . ' = ' . $db->quoteName('c.id') . ')');

			if ($createdByClause )
			{
				$query->where('c.created_by = ' . (int) $userId);
			}
		}

		$reportId = $this->getState('reportId');
		$viewAll = $this->checkpermissions($reportId);

		if ($myTeamClause && $hasUsers)
		{
			$query->where('lt.user_id IN(' . implode(',', $hasUsers) . ')');
		}

		if (in_array('username', $colToshow))
		{
			$query->join('INNER', $db->quoteName('#__users', 'u') . 'ON (' . $db->quoteName('lt.user_id') . ' = ' . $db->quoteName('u.id') . ')');
			$query->where($db->qn('u.block') . ' = 0');
		}

		if (in_array('usergroup', $colToshow))
		{
			$subQuery = $db->getQuery(true);
			$subQuery->select('ugm.group_id');
			$subQuery->from($db->quoteName('#__user_usergroup_map') . ' as ugm');
			$subQuery->where($db->quoteName('ugm.user_id') . ' = ' . $db->quoteName('lt.user_id'));
			$query->select('(SELECT GROUP_CONCAT(ug.title SEPARATOR ", ") from  #__usergroups ug where ug.id IN(' . $subQuery . ')) as usergroup');

			if (isset($filters['usergroup']) && !empty($filters['usergroup']))
			{
				$subQuery = $db->getQuery(true);
				$subQuery->select('ugm.user_id');
				$subQuery->from($db->quoteName('#__user_usergroup_map') . ' as ugm');
				$subQuery->where($db->quoteName('ugm.group_id') . ' = ' . (int) $filters['usergroup']);
				$query->where('lt.user_id IN(' . $subQuery . ')');
			}
		}

		$query->group('lt.user_id, l.id');

		return $query;
	}

	/**
	 * Function to get the course filter
	 *
	 * @return  object
	 *
	 * @since 1.0.0
	 */
	public function getCourseFilter()
	{
		FormHelper::addFieldPath(JPATH_ADMINISTRATOR . '/components/com_tjlms/models/fields/');
		$Courses = FormHelper::loadFieldType('courses', false);
		$courses = $Courses->getOptionsExternally();

		$helperPath = JPATH_ADMINISTRATOR . '/components/com_tjlms/helpers/tjlms.php';
		JLoader::register('TjlmsHelper', $helperPath);
		JLoader::load('TjlmsHelper');
		$i = 0;

		foreach ($courses as $course)
		{
			$canEnroll = TjlmsHelper::canManageCourseEnrollment($course->value);

			if ($canEnroll != 1)
			{
				unset($courses[$i]);
			}

			$i++;
		}

		$coursesFilter[] = JHTML::_('select.option', '', '- ' . Text::_('COM_TJLMS_LESSONREPORT_COURSENAME') . ' -');

		if (!empty($courses))
		{
			foreach ($courses as $course)
			{
				$coursesFilter[] = JHTML::_('select.option', $course->value, $course->text);
			}
		}

		return $coursesFilter;
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
		$userFilter 		= $this->getUserFilter();

		$courseFilter 		= $this->getCourseFilter();

		$lessonFilter 		= $TjlmsModelReports->getLessonFilter($created_by);

		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_multiagency/models');
		$multiagencyModel = BaseDatabaseModel::getInstance('Multiagency', 'MultiagencyModel');
		$agencyFilter 		= $multiagencyModel->getAgencyFilter();

		$typeArray = array();
		$typeArray[] = JHTML::_('select.option', '', Text::_('COM_TJLMS_LESSONS_ATTEMPTS_GRADE'));
		$typeArray[] = JHTML::_('select.option', '0', Text::_('COM_TJLMS_HIGHEST_ATTEMPT'));
		$typeArray[] = JHTML::_('select.option', '1', Text::_('COM_TJLMS_AVERAGE_ATTEMPT'));
		$typeArray[] = JHTML::_('select.option', '2', Text::_('COM_TJLMS_FIRST_ATTEMPT'));
		$typeArray[] = JHTML::_('select.option', '3', Text::_('COM_TJLMS_LAST_COMPLETED_ATTEMPT'));

		$statusArray = array();
		$statusArray[] = JHTML::_('select.option', '', Text::_('COM_TJLMS_FILTER_SELECT_STATUS'));
		$statusArray[] = JHTML::_('select.option', 'started', Text::_('COM_TJLMS_FILTER_STATUS_STARTED'));
		$statusArray[] = JHTML::_('select.option', 'passed', Text::_('COM_TJLMS_FILTER_STATUS_PASSED'));
		$statusArray[] = JHTML::_('select.option', 'failed', Text::_('COM_TJLMS_FILTER_STATUS_FAILED'));
		$statusArray[] = JHTML::_('select.option', 'completed', Text::_('COM_TJLMS_FILTER_STATUS_COMPLETED'));
		$statusArray[] = JHTML::_('select.option', 'incomplete', Text::_('COM_TJLMS_LESSONSTATUS_INCOMPLETE'));

		$groups  = HTMLHelper::_('user.groups', true);
		array_unshift($groups, HTMLHelper::_('select.option', '', Text::_('COM_TJLMS_ENROLLED_USER_ACCESS')));

		$dispFilters = array(
			array(
				'id' => array('search_type' => 'text', 'type' => 'equal', 'searchin' => 'l.id'),
				'name' => array(
					'search_type' => 'text', 'select_options' => $lessonFilter, 'type' => 'equal', 'searchin' => 'l.title'
				),
				'lessonFormat' => array('search_type' => 'text', 'searchin' => 'l.format'),
				'courseTitle' => array(
					'search_type' => 'select', 'select_options' => $courseFilter, 'type' => 'equal', 'searchin' => 'c.id'
				),
				'username' => array(
					'search_type' => 'select', 'select_options' => $userFilter, 'type' => 'equal', 'searchin' => 'u.id'
				),
				'timestart' => array(
					'search_type' => 'date.range',
					'searchin' => 'timestart',
					'timestart_from' => array(
										'attrib' => array(
												'placeholder' => 'YYYY-MM-DD', 'onChange' => 'tjrContentUI.report.attachCalSubmit(this);'
										)
					),
					'timestart_to' => array(
										'attrib' => array('placeholder' => 'YYYY-MM-DD', 'onChange' => 'tjrContentUI.report.attachCalSubmit(this);'
										)
					)
				),
				'timeend' => array(
					'search_type' => 'date.range',
					'searchin' => 'timeend',
					'timeend_from' => array(
									'attrib' => array(
												'placeholder' => 'YYYY-MM-DD', 'onChange' => 'tjrContentUI.report.attachCalSubmit(this);'
										)
					),
					'timeend_to' => array(
									'attrib' => array(
											'placeholder' => 'YYYY-MM-DD', 'onChange' => 'tjrContentUI.report.attachCalSubmit(this);'
									)
								)
				),
				'attemptsGrade' => array(
					'search_type' => 'select', 'select_options' => $typeArray, 'type' => 'equal', 'searchin' => 'l.attempts_grade'
				),
				'timeSpentOnLesson' => array(
						'search_type' => 'text', 'type' => 'equal', 'searchin' => 'lt.time_spent'
				),
				'status' => array(
					'search_type' => 'select', 'select_options' => $statusArray, 'type' => 'equal', 'searchin' => 'lt.lesson_status'
				),
			),
				array(
						'agency' => array(
								'search_type' => 'select', 'select_options' => $agencyFilter, 'type' => 'equal', 'searchin' => 'tjc.client_id'
						),
				)
		);

		// Commented to remove "All" filter for report

		/*
		if (count($reportOptions) > 1)
		{
			$dispFilters[1] = array();
			$dispFilters[1]['report_filter'] = array(
					'search_type' => 'select', 'select_options' => $reportOptions
				);
		}
		*/

		return $dispFilters;
	}
}
