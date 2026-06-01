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
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;

use Joomla\CMS\Language\Text;

JLoader::import('com_tjreports.models.reports', JPATH_SITE . '/components');

/**
 * Attempt report plugin of TJReport
 *
 * @since  1.0.0
 */
class TjreportsModelSinglecoursereport extends TjreportsModelReports
{
	protected $default_order = 'name';

	protected $default_order_dir = 'ASC';

	public $showSearchResetButton = false;

	private $lessonColumns = array();

	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see     JModelLegacy
	 * @since   1.6
	 */
	public function __construct($config = array())
	{
		// Joomla fields integration
		// Define custom fields table, alias, and table.column to join on
		$this->customFieldsTable       = '#__tjreports_com_users_user';
		$this->customFieldsTableAlias  = 'tjrcuu';
		$this->customFieldsQueryJoinOn = 'eu.user_id';

		if (method_exists($this, 'tableExists'))
		{
			$this->customFieldsTableExists = $this->tableExists();
		}

		JLoader::import('administrator.components.com_tjlms.helpers.tjlms', JPATH_SITE);

		$lang = Factory::getLanguage();
		$base_dir = JPATH_SITE . '/administrator';
		$lang->load('com_tjlms', $base_dir);

		$this->columns = array(
			'course_id'             => array('table_column' => 'c.id', 'not_show_hide' => true, 'title' => 'COM_TJLMS_COURSE_ID'),
			'name'                  => array('table_column' => 'u.name', 'title' => 'COM_TJLMS_REPORT_USERNAME'),
			'username'              => array('table_column' => 'u.username', 'title' => 'COM_TJLMS_REPORT_USERUSERNAME'),
			'email'                 => array('table_column' => 'u.email', 'title' => 'COM_TJLMS_REPORT_USEREMAIL'),
			'usergroup'             => array('title' => 'COM_TJLMS_REPORT_USERGROUP', 'disable_sorting' => true),
			'cat_title'             => array('table_column' => 'cat.title', 'title' => 'COM_TJLMS_COURSE_CAT'),
			'enrolled_on_time'      => array('table_column' => 'eu.enrolled_on_time', 'title' => 'COM_TJLMS_USER_ENROLLED_ON'),
			'assigned_by'           => array('table_column' => 'ut.name', 'title' => 'COM_TJLMS_REPORT_ASSIGNED_BY'),
			'start_date'              => array('table_column' => 'td.start_date', 'title' => 'PLG_TJREPORTS_SINGLECOURSEREPORT_ASSIGN_START_DATE'),
			'due_date'              => array('table_column' => 'td.due_date', 'title' => 'COM_TJLMS_DUE_DATE'),
			'timeend'               => array('table_column' => 'cst.timeend', 'title' => 'COM_TJLMS_COURSE_COMPLETED_DATE'),
			'cstatus'               => array('table_column' => 'cst.status', 'title' => 'COM_TJLMS_REPORT_COURSE_COMPLETION_STATUS'),
			'completion'            => array('title' => 'COM_TJLMS_COMPLETION'),
			'totaltimespent'        => array('title' => 'COM_TJLMS_REPORT_TIMESPENT'),
			'lesson::attempts_done' => array('title' => 'COM_TJLMS_REPORT_LESSON_ATTEMPTS_DONE'),
			'lesson::time_spent'    => array('title' => 'COM_TJLMS_REPORT_LESSON_TIMESPENT'),
			'lesson::timestart'    => array('title' => 'COM_TJLMS_REPORT_LESSON_TIMESTART'),
			'lesson::timeend'    => array('title' => 'COM_TJLMS_REPORT_LESSON_TIMEEND'),
			'lesson::score'         => array('title' => 'COM_TJLMS_REPORT_LESSON_SCORE'),
			'lesson::lesson_status' => array('title' => 'COM_TJLMS_REPORT_LESSON_STATUS'),
			'lesson::attempt_state' => array('title' => 'COM_TJLMS_REPORT_LESSON_ATTEMPT_STATE')
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
		$detail = array('client' => 'com_tjlms', 'title' => Text::_('PLG_TJREPORTS_SINGLECOURSEREPORT_TITLE'));

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
	 * Method to get an array of data items.
	 *
	 * @return  mixed  An array of data items on success, false on failure.
	 *
	 * @since   1.6
	 */
	public function getItems()
	{
		$filters = (array) $this->getState('filters');

		if (empty($filters['course_id']))
		{
			$this->setTJRMessages(Text::_('PLG_TJREPORTS_SINGLECOURSEREPORT_SELECT_COURSE_MESSAGE'));

			return array();
		}
		else
		{
			$this->getAdditionalColNames($filters['course_id']);
		}

		// Add additional columns which are not part of the query
		$items = parent::getItems();

		if (empty($items))
		{
			return;
		}

		$lmsparams = ComponentHelper::getParams('com_tjlms');
		$dateFormatShow = $lmsparams->get('date_format_show', 'Y-m-d H:i:s');

		jimport('techjoomla.common');
		JLoader::import('components.com_tjlms.helpers.tracking', JPATH_SITE);
		JLoader::import('components.com_tjlms.helpers.main', JPATH_SITE);
		$tjCommon 		= new TechjoomlaCommon;
		$trackingHelper = new ComtjlmstrackingHelper;

		$db        = $this->_db;
		$colToshow = $this->getState('colToshow');

		foreach ($items as &$item)
		{
			$course_id 		= $item['course_id'];
			$assigned_user 	= $item['user_id'];

			$progress = $trackingHelper->getCourseTrackEntry($course_id, $assigned_user);

			if (in_array('completion', $colToshow) )
			{
				$item['completion'] = 0;

				if (isset($progress['completionPercent']))
				{
					$item['completion'] = floor($progress['completionPercent']);
				}
			}

			if (in_array('cstatus', $colToshow))
			{
				$item['cstatus'] = ' - ';

				if (isset($progress['status']))
				{
					$item['cstatus'] = Text::_('PLG_TJREPORTS_SINGLECOURSEREPORT_INCOMPLETE');

					if ($progress['status'] == 'C')
					{
						$item['cstatus'] = Text::_('PLG_TJREPORTS_SINGLECOURSEREPORT_COMPLETED');
					}
				}
			}

			if (in_array('timeend', $colToshow))
			{
				$item['timeend'] = ' - ';

				if (isset($progress['completion_date']))
				{
					$item['timeend'] = $tjCommon->getDateInLocal($progress['completion_date'], 0, $dateFormatShow);
				}
			}

			if (in_array('enrolled_on_time', $colToshow) && isset($item['enrolled_on_time']))
			{
				$item['enrolled_on_time'] = $tjCommon->getDateInLocal($item['enrolled_on_time'], 0, $dateFormatShow);
			}

			if (in_array('start_date', $colToshow) && isset($item['start_date']))
			{
				$item['start_date'] = $tjCommon->getDateInLocal($item['start_date'], 0, $dateFormatShow);
			}

			if (in_array('due_date', $colToshow) && isset($item['due_date']))
			{
				$item['due_date'] = $tjCommon->getDateInLocal($item['due_date'], 0, $dateFormatShow);
			}

			$item['totaltimespent'] = 0;

			foreach ($this->lessonColumns as $key => $detail)
			{
				$lessonId 		= (int) $key;
				$attempts_done = $timestart = $timeend = ' - ';
				$score = $time_spent = 0;
				$lessonAttempt 	= (array) $trackingHelper->getLessonattemptsGrading($detail['detail'], $assigned_user);

				extract($lessonAttempt);

				$item[$key] = array();
				$item[$key]['lesson::score'] 		= round($score);
				$item[$key]['lesson::lesson_status'] = !empty($lesson_status) ? $lesson_status : 'incomplete';

				$query = $db->getQuery(true);
				$query->select('SUM(TIME_TO_SEC(time_spent)) as time_spent, count(attempt) as attempts_done, timestart, timeend');

				if (isset($filters['attempt_state']) && $filters['attempt_state'] != '0')
				{
					$query->from($db->quoteName('#__tjlms_lesson_track', 'lt'));
				}
				else
				{
					$query->select('lesson_track_id');
					$query->from($db->quoteName('#__tjlms_lesson_track_archive', 'lt'));
				}

				$query->where($db->quoteName('lt.user_id') . ' = ' . $db->quote($assigned_user));
				$query->where($db->quoteName('lt.lesson_id') . ' = ' . $db->quote($lessonId));

				$db->setQuery($query);
				$timeAttempts = $db->loadAssoc();

				extract($timeAttempts);

				$item[$key]['lesson::time_spent'] 	  = $time_spent ? $this->formatTime($time_spent) : ' - ';
				$item[$key]['lesson::attempts_done'] = ' - ';
				$item[$key]['lesson::timestart'] = $timestart ? $timestart : ' - ';
				$item[$key]['lesson::timeend'] = $timeend ? $timeend : ' - ';

				$attemptStatus = ' - ';

				if ($lessonAttempt['lesson_status'] != 'not_started')
				{
					$attemptStatus = $lesson_track_id ? Text::_('COM_TJLMS_ATTEMPTREPORT_STATE_EXPIRED') : Text::_('COM_TJLMS_ATTEMPTREPORT_STATE_ACTIVE');
				}

				$item[$key]['lesson::attempt_state'] = $attemptStatus;

				if ($attempts_done)
				{
					$attemptFilters = array('name' => $lessonId, 'username' => $assigned_user);

					$link = $this->getReportLink('attemptreport', $attemptFilters);
					$item[$key]['lesson::attempts_done'] = '<a href="' . Route::_($link) . '">' . $attempts_done . '</a>';
				}

				$item['totaltimespent'] = $item['totaltimespent'] + (int) $time_spent;
			}

			$item['totaltimespent'] = $item['totaltimespent'] ? $this->formatTime($item['totaltimespent']) : ' - ';
		}

		$items = $this->sortCustomColumns($items);

		return $items;
	}

	/**
	 * Method to get a JDatabaseQuery object for retrieving the data set from a database.
	 *
	 * @return  JDatabaseQuery  A JDatabaseQuery object to retrieve the data set.
	 *
	 * @since   1.6
	 */
	protected function getListQuery()
	{
		$db        = $this->_db;
		$query     = parent::getListQuery();
		$colToshow = $this->getState('colToshow');
		$filters   = $this->getState('filters');
		$user      = Factory::getUser();
		$userId    = $user->id;

		// Must have columns to get details of non linked data like completion
		$query->select(array('u.id as user_id', 'c.id as course_id'));
		$query->from('#__tjlms_enrolled_users AS eu');
		$query->join('INNER', '#__users as u ON u.id = eu.user_id');
		$query->join('LEFT', '#__tjlms_course_track as cst ON cst.course_id = eu.course_id AND cst.user_id = eu.user_id');
		$query->join('LEFT', '#__tjlms_courses as c ON c.id = eu.course_id');
		$query->join('LEFT', '#__tjlms_lessons as l ON l.course_id = c.id');
		$query->join('LEFT', '#__jlike_content as jc ON jc.element_id = c.id');
		$query->join('LEFT', '#__jlike_todos as td ON td.content_id = jc.id AND td.assigned_to = eu.user_id');
		$query->join('LEFT', '#__users as ut ON ut.id = td.assigned_by');
		$query->join('LEFT', '#__categories AS cat ON c.catid = cat.id');

		if ($filters['attempt_state'] == '1')
		{
			$query->join('INNER', '#__tjlms_lesson_track as lt ON (lt.lesson_id = l.id AND lt.user_id = u.id AND lt.user_id = eu.user_id)');
		}
		elseif ($filters['attempt_state'] == '0')
		{
			$query->join('INNER', '#__tjlms_lesson_track_archive as lt ON (lt.lesson_id = l.id AND lt.user_id = u.id AND lt.user_id = eu.user_id)');
		}

		$filters = (array) $this->getState('filters');

		if (empty($filters['course_id']))
		{
			$query->where('c.id=0');
		}
		elseif (in_array('usergroup', $colToshow))
		{
			$subQuery = $db->getQuery(true);
			$subQuery->select('ugm.group_id');
			$subQuery->from($db->quoteName('#__user_usergroup_map') . ' as ugm');
			$subQuery->where($db->quoteName('ugm.user_id') . ' = ' . $db->quoteName('u.id'));
			$query->select('(SELECT GROUP_CONCAT(ug.title SEPARATOR ", ") from  #__usergroups ug where ug.id IN(' . $subQuery . ')) as usergroup');

			if (isset($filters['usergroup']) && !empty($filters['usergroup']))
			{
				$subQuery = $db->getQuery(true);
				$subQuery->select('ugm.user_id');
				$subQuery->from($db->quoteName('#__user_usergroup_map') . ' as ugm');
				$subQuery->where($db->quoteName('ugm.group_id') . ' = ' . (int) $filters['usergroup']);
				$query->where('u.id IN(' . $subQuery . ')');
			}
		}

		if ((int) $filters['report_filter'] === -1)
		{
			$hasUsers = TjlmsHelper::getSubusers();

			if ($hasUsers)
			{
				$query->where('eu.user_id IN(' . implode(',', $hasUsers) . ')');
			}
			else
			{
				$query->where('eu.user_id=0');
			}
		}

		$query->where('c.state=1');
		$query->where('eu.state=1');
		$query->group('eu.course_id,eu.user_id');

		return $query;
	}

	/**
	 * Create an array of filters
	 *
	 * @return    mixed
	 *
	 * @since    1.0
	 */
	public function displayFilters()
	{
		$reportOptions  = TjlmsHelper::getReportFilterValues($this, $selected, $created_by, $myTeam);

		JLoader::import('components.com_tjlms.models.reports', JPATH_ADMINISTRATOR);
		$TjlmsModelReports 	= new TjlmsModelReports;
		$catFilter 			= $TjlmsModelReports->getCatFilter();
		$userFilter 		= $TjlmsModelReports->getUserFilter($myTeam);
		$courseFilter 		= $TjlmsModelReports->getCourseFilter($created_by);

		$attemptStateArray = array();
		$attemptStateArray[] = HTMLHelper::_('select.option', '', Text::_('COM_TJLMS_FILTER_SELECT_STATE'));
		$attemptStateArray[] = HTMLHelper::_('select.option', '1', Text::_('COM_TJLMS_FILTER_STATE_ACTIVE'));
		$attemptStateArray[] = HTMLHelper::_('select.option', '0', Text::_('COM_TJLMS_FILTER_STATE_EXPIRED'));

		$groups  = HTMLHelper::_('user.groups', true);
		array_unshift($groups, HTMLHelper::_('select.option', '', Text::_('COM_TJLMS_ENROLLED_USER_ACCESS')));

		$filters = $this->getState('filters');

		$dispFilters = array(
			array(
				'username' => array(
					'search_type' => 'select', 'select_options' => $userFilter, 'type' => 'equal', 'searchin' => 'u.id'
				),
				'cat_title' => array(
					'search_type' => 'select', 'select_options' => $catFilter, 'type' => 'equal', 'searchin' => 'c.catid'
				),
				'usergroup' => array(
					'search_type' => 'select', 'select_options' => $groups
				)
			),
			array(
				'course_id' => array(
					'search_type' => 'select', 'select_options' => $courseFilter, 'type' => 'equal', 'searchin' => 'c.id'
				),
				'attempt_state' => array(
					'search_type' => 'select', 'select_options' => $attemptStateArray, 'type' => 'equal'
				)
			)
		);

		if (count($reportOptions) > 1)
		{
			$filterHtml = HTMLHelper::_('select.genericlist', $reportOptions, 'filters[report_filter]',
					'class="filter-input input-medium" size="1" ' .
					'onchange="document.getElementById(\'filterscourse_id\').selectedIndex=0;tjrContentUI.report.submitTJRData();"',
					'value', 'text', $filters['report_filter']
				);
			$dispFilters[1] = array('report_filter' => array( 'search_type' => 'html', 'html' => $filterHtml)) + $dispFilters[1];
		}

		// Joomla fields integration
		// Call parent function to set filters for custom fields
		if (method_exists(get_parent_class($this), 'setCustomFieldsDisplayFilters'))
		{
			parent::setCustomFieldsDisplayFilters($dispFilters);
		}

		return $dispFilters;
	}

	/**
	 * Create Extra columns
	 *
	 * @param   INT  $courseId  Course ID
	 *
	 * @return    void
	 *
	 * @since    1.0
	 */
	private function getAdditionalColNames($courseId)
	{
		$db     = $this->_db;
		$query 	= $db->getQuery(true);

		$query->select(array('l.id', 'l.title', 'CONCAT_WS("::", l.id, l.title) AS lessonKey', 'l.format', 'l.attempts_grade'));
		$query->from('#__tjlms_lessons l');
		$query->join('LEFT', '#__tjlms_modules m ON m.id=l.mod_id');
		$query->where('l.course_id = ' . (int) $courseId);
		$query->order('m.ordering asc, l.ordering asc');

		$db->setQuery($query);

		$lessons = $db->loadObjectList('lessonKey');

		$colToshow = $this->getState('colToshow', Array());

		if (!empty($lessons))
		{
			$this->headerLevel = 2;

			foreach ($lessons as $key => $lesson)
			{
				$this->lessonColumns[$key] = $colToshow[$key] = array(
					'lesson::attempts_done' => ' - ',
					'lesson::time_spent'	 => ' - ',
					'lesson::timestart'	 => ' - ',
					'lesson::timeend'	 => ' - ',
					'lesson::score'		 => ' - ',
					'lesson::lesson_status' => ' - ',
					'lesson::attempt_state' => ' - '
				);

				$detail = new stdClass;
				$detail->id = $lesson->id;
				$detail->format = $lesson->format;
				$detail->attempts_grade = $lesson->attempts_grade;
				$this->lessonColumns[$key]['detail'] = $detail;
			}
		}

		$this->setState('colToshow', $colToshow);
	}
}
