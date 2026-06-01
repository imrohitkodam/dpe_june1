<?php
/**
 * @package     Tjlms.Plugin
 * @subpackage  Tjlms,TJReport,coursereport
 *
 * @copyright   Copyright (C) 2005 - 2020 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Component\ComponentHelper;

JLoader::import('com_tjreports.models.reports', JPATH_SITE . '/components');

/**
 * Lesson report plugin of TJReport
 *
 * @since  1.0.0
 */
class TjreportsModelLessonreport extends TjreportsModelReports
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
		// Joomla fields integration
		// Define custom fields table, alias, and table.column to join on
		$this->customFieldsTable       = '#__tjreports_com_users_user';
		$this->customFieldsTableAlias  = 'tjrcuu';
		$this->customFieldsQueryJoinOn = 'lt.user_id';

		if (method_exists($this, 'tableExists'))
		{
			$this->customFieldsTableExists = $this->tableExists();
		}

		JLoader::import('administrator.components.com_tjlms.helpers.tjlms', JPATH_SITE);

		$lang = Factory::getLanguage();
		$base_dir = JPATH_SITE . '/administrator';
		$lang->load('com_tjlms', $base_dir);

		$this->columns = array(
			'id'                => array('table_column' => 'l.id', 'title' => 'COM_TJLMS_LESSONREPORT_ID'),
			'course_id'         => array('table_column' => 'l.course_id', 'title' => 'COM_TJLMS_LESSONREPORT_COURSEID'),
			'name'              => array('table_column' => 'l.title', 'title' => 'COM_TJLMS_LESSONREPORT_NAME'),
			'lessonFormat'      => array('table_column' => 'l.format', 'title' => 'COM_TJLMS_LESSONREPORT_FORMAT'),
			'courseTitle'       => array('table_column' => 'c.title', 'title' => 'COM_TJLMS_LESSONREPORT_COURSENAME'),
			'username'          => array('table_column' => 'u.username', 'title' => 'COM_TJLMS_REPORT_USERUSERNAME'),
			'user_id'           => array('table_column' => 'lt.user_id', 'title' => 'COM_TJLMS_ENROLMENT_USERID'),
			'usergroup'         => array('title' => 'COM_TJLMS_REPORT_USERGROUP', 'disable_sorting' => true),
			'timestart'         => array('title' => 'COM_TJLMS_LESSONREPORT_STARTDATE'),
			'timeend'           => array('title' => 'COM_TJLMS_LESSONREPORT_ENDDATE'),
			'timeSpentOnLesson' => array('title' => 'COM_TJLMS_LESSONREPORT_TIMESPENT'),
			'idealTime'         => array('table_column' => 'l.ideal_time', 'title' => 'COM_TJLMS_ATTEMPTREPORT_IDEAL_TIME'),
			'score'             => array('title' => 'COM_TJLMS_LESSONREPORT_SCORE', 'disable_sorting' => true),
			'status'            => array('table_column' => 'lt.lesson_status','title' => 'COM_TJLMS_REPORT_LESSON_STATUS'),
			'attemptsAllowed'   => array('table_column' => 'l.no_of_attempts', 'title' => 'COM_TJLMS_LESSONREPORT_ALLOWEDATTEMPTS'),
			'attemptsDone'      => array('title' => 'COM_TJLMS_LESSONREPORT_ATTEMPTSMADE'),
			'attemptsGrade'     => array('table_column' => 'l.attempts_grade', 'title' => 'COM_TJLMS_LESSONREPORT_GRADINGMETHOD'),
			'considerMarks'     => array('table_column' => 'l.consider_marks', 'title' => 'COM_TJLMS_LESSONREPORT_COMSIDERMARKS'),
			'lastAccessedOn'    => array('table_column' => 'lt.last_accessed_on', 'title' => 'COM_TJLMS_LESSONREPORT_LAST_ACCESSED_ON'),
			'modifiedDate'      => array('table_column' => 'lt.modified_date', 'title' => 'COM_TJLMS_LESSONREPORT_MODIFIED_DATE'),
			'totalContent'      => array('table_column' => 'lt.total_content', 'title' => 'COM_TJLMS_LESSONREPORT_TOTAL_CONTENT'),
			'modifiedBy'        => array('table_column' => 'lt.modified_by', 'title' => 'COM_TJLMS_LESSONREPORT_MODIFIED_BY')
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
		$detail = array('client' => 'com_tjlms', 'title' => Text::_('PLG_TJREPORTS_LESSONREPORT_TITLE'));

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
		// Add additional columns which are not part of the query
		$items = parent::getItems();

		if (empty($items))
		{
			return;
		}

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
				$item['status']   = $result->lesson_status;
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

			if (empty($item['course_id']))
			{
				$item['course_id'] = 'NA';
			}
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
		$colToshow = (array) $this->getState('colToshow');
		$filters   = $this->getState('filters');
		$createdByClause = $myTeamClause = false;
		$hasUsers = array();
		$userId     = Factory::getUser()->id;

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

		if ( $filters['attempt_state'] != '' && (int) $filters['attempt_state'] === 0)
		{
			$query->from($db->quoteName('#__tjlms_lesson_track_archive', 'lt'));
		}
		else
		{
			$query->from($db->quoteName('#__tjlms_lesson_track', 'lt'));
		}

		$query->join('INNER', $db->quoteName('#__tjlms_lessons', 'l') . ' ON (' . $db->quoteName('lt.lesson_id') . ' = ' . $db->quoteName('l.id') . ')');

		if (in_array('courseTitle', $colToshow) || $createdByClause)
		{
			$query->join('Left', $db->quoteName('#__tjlms_courses', 'c') . 'ON (' . $db->quoteName('l.course_id') . ' = ' . $db->quoteName('c.id') . ')');

			if ($createdByClause )
			{
				$query->where('l.created_by = ' . (int) $userId);
			}
		}

		// Columns with no extra processing
		$finalColsToShow = array ('modifiedDate', 'lastAccessedOn', 'totalContent', 'modifiedBy');

		foreach ($this->columns as $c => $colArray)
		{
			if (!in_array($c, $finalColsToShow))
			{
				continue;
			}

			if (in_array($c, $colToshow))
			{
				$query->select($colArray['table_column']);
			}
		}

		$reportId = $this->getDefaultReport($this->name);
		$viewAll = $this->checkpermissions($reportId);

		if ($myTeamClause && $hasUsers)
		{
			$query->where('lt.user_id IN(' . implode(',', $hasUsers) . ')');
		}
		elseif (!$viewAll)
		{
			$query->where('lt.user_id=0');
		}

		if (in_array('username', $colToshow))
		{
			$query->join('INNER', $db->quoteName('#__users', 'u') . 'ON (' . $db->quoteName('lt.user_id') . ' = ' . $db->quoteName('u.id') . ')');
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
		$userFilter 		= $TjlmsModelReports->getUserFilter($myTeam);
		$courseFilter 		= $TjlmsModelReports->getCourseFilter($created_by);
		$lessonFilter 		= $TjlmsModelReports->getLessonFilter($created_by);

		$typeArray = array();
		$typeArray[] = HTMLHelper::_('select.option', '', Text::_('COM_TJLMS_LESSONS_ATTEMPTS_GRADE'));
		$typeArray[] = HTMLHelper::_('select.option', '0', Text::_('COM_TJLMS_HIGHEST_ATTEMPT'));
		$typeArray[] = HTMLHelper::_('select.option', '1', Text::_('COM_TJLMS_AVERAGE_ATTEMPT'));
		$typeArray[] = HTMLHelper::_('select.option', '2', Text::_('COM_TJLMS_FIRST_ATTEMPT'));
		$typeArray[] = HTMLHelper::_('select.option', '3', Text::_('COM_TJLMS_LAST_COMPLETED_ATTEMPT'));

		$statusArray = array();
		$statusArray[] = HTMLHelper::_('select.option', '', Text::_('COM_TJLMS_FILTER_SELECT_STATUS'));
		$statusArray[] = HTMLHelper::_('select.option', 'started', Text::_('COM_TJLMS_FILTER_STATUS_STARTED'));
		$statusArray[] = HTMLHelper::_('select.option', 'passed', Text::_('COM_TJLMS_FILTER_STATUS_PASSED'));
		$statusArray[] = HTMLHelper::_('select.option', 'failed', Text::_('COM_TJLMS_FILTER_STATUS_FAILED'));
		$statusArray[] = HTMLHelper::_('select.option', 'completed', Text::_('COM_TJLMS_FILTER_STATUS_COMPLETED'));
		$statusArray[] = HTMLHelper::_('select.option', 'incomplete', Text::_('COM_TJLMS_LESSONSTATUS_INCOMPLETE'));
		$statusArray[] = HTMLHelper::_('select.option', 'ap', Text::_('COM_TJLMS_LESSONSTATUS_ASSESSMENT_PENDING'));

		$attemptStateArray = array();
		$attemptStateArray[] = HTMLHelper::_('select.option', '', Text::_('COM_TJLMS_FILTER_SELECT_STATE'));
		$attemptStateArray[] = HTMLHelper::_('select.option', '1', Text::_('COM_TJLMS_FILTER_STATE_ACTIVE'));
		$attemptStateArray[] = HTMLHelper::_('select.option', '0', Text::_('COM_TJLMS_FILTER_STATE_EXPIRED'));

		$groups  = HTMLHelper::_('user.groups', true);
		array_unshift($groups, HTMLHelper::_('select.option', '', Text::_('COM_TJLMS_ENROLLED_USER_ACCESS')));

		$filters = $this->getState('filters');

		$dispFilters = array(
			array(
				'id' => array('search_type' => 'text', 'type' => 'equal', 'searchin' => 'l.id'),
				'name' => array(
					'search_type' => 'select', 'select_options' => $lessonFilter, 'type' => 'equal', 'searchin' => 'lt.lesson_id'
				),
				'lessonFormat' => array('search_type' => 'text', 'searchin' => 'l.format'),
				'courseTitle' => array(
					'search_type' => 'select', 'select_options' => $courseFilter, 'type' => 'equal', 'searchin' => 'c.id'
				),
				'username' => array(
					'search_type' => 'select', 'select_options' => $userFilter, 'type' => 'equal', 'searchin' => 'u.id'
				),
				'usergroup' => array(
					'search_type' => 'select', 'select_options' => $groups
				),
				'timestart' => array(
					'search_type' => 'date.range',
					'searchin' => 'timestart',
					'timestart_from' => array('attrib' => array('placeholder' => 'YYYY-MM-DD', 'onChange' => 'tjrContentUI.report.attachCalSubmit(this);')),
					'timestart_to' => array('attrib' => array('placeholder' => 'YYYY-MM-DD', 'onChange' => 'tjrContentUI.report.attachCalSubmit(this);'))
				),
				'timeend' => array(
					'search_type' => 'date.range',
					'searchin' => 'timeend',
					'timeend_from' => array('attrib' => array('placeholder' => 'YYYY-MM-DD', 'onChange' => 'tjrContentUI.report.attachCalSubmit(this);')),
					'timeend_to' => array('attrib' => array('placeholder' => 'YYYY-MM-DD', 'onChange' => 'tjrContentUI.report.attachCalSubmit(this);'))
				),
				'attemptsGrade' => array(
					'search_type' => 'select', 'select_options' => $typeArray, 'type' => 'equal', 'searchin' => 'l.attempts_grade'
				),
				'attemptsAllowed' => array('search_type' => 'text', 'type' => 'equal', 'searchin' => 'l.no_of_attempts'),
				'timeSpentOnLesson' => array('search_type' => 'text', 'type' => 'equal', 'searchin' => 'lt.time_spent'),
				'status' => array(
					'search_type' => 'select', 'select_options' => $statusArray, 'type' => 'equal', 'searchin' => 'lt.lesson_status'
				)
			),
			array(
				'attempt_state' => array(
					'search_type' => 'select', 'select_options' => $attemptStateArray, 'type' => 'equal'
				)
			)
		);

		if (count($reportOptions) > 1)
		{
			$filterHtml = HTMLHelper::_('select.genericlist', $reportOptions, 'filters[report_filter]',
					'class="filter-input input-medium" size="1" ',
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
	 * Create an array of fields in the form of Google data studio requires
	 * Array(
	 *   array(
	 *		'name' => internal name of the field
	 * 		'label' => Name to be displayed on the report
	 *      'dataType' => 'NUMBER' OR 'STRING' OR 'BOOLEAN'
	 * 		'semantics' => array('conceptType' => 'DIMENSION' OR 'METRIC')
	 * 	  ),
	 * )
	 *
	 * More information about fields https://developers.google.com/datastudio/connector/reference#data_types
	 *
	 * @return  ARRAY
	 *
	 * @since   1.3.30
	 */
	public function getGDSFields()
	{
		return array(
			array('name' => 'id', 'label' => Text::_('COM_TJLMS_LESSONREPORT_ID'),
				'dataType' => 'NUMBER', 'semantics' => array('conceptType' => 'DIMENSION')),
			array('name' => 'course_id', 'label' => Text::_('COM_TJLMS_LESSONREPORT_COURSEID'),
				'dataType' => 'NUMBER','semantics' => array('conceptType' => 'DIMENSION')),
			array('name' => 'name', 'label' => Text::_('COM_TJLMS_LESSONREPORT_NAME'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION')),
			array('name' => 'lessonFormat', 'label' => Text::_('COM_TJLMS_LESSONREPORT_FORMAT'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION')),
			array('name' => 'courseTitle', 'label' => Text::_('COM_TJLMS_LESSONREPORT_COURSENAME'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION')),
			array('name' => 'username', 'label' => Text::_('COM_TJLMS_REPORT_USERUSERNAME'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION')),
			array('name' => 'user_id', 'label' => Text::_('COM_TJLMS_ENROLMENT_USERID'),
				'dataType' => 'NUMBER', 'semantics' => array('conceptType' => 'DIMENSION')),
			array('name' => 'usergroup', 'label' => Text::_('COM_TJLMS_REPORT_USERGROUP'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION')),
			array('name' => 'timestart', 'label' => Text::_('COM_TJLMS_LESSONREPORT_STARTDATE'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION', 'semanticType' => 'YEAR_MONTH_DAY')),
			array('name' => 'timeend', 'label' => Text::_('COM_TJLMS_LESSONREPORT_ENDDATE'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION','semanticType' => 'YEAR_MONTH_DAY')),
			array('name' => 'timeSpentOnLesson', 'label' => Text::_('COM_TJLMS_LESSONREPORT_TIMESPENT'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'METRIC')),
			array('name' => 'idealTime', 'label' => Text::_('COM_TJLMS_ATTEMPTREPORT_IDEAL_TIME'),
				'dataType' => 'NUMBER', 'semantics' => array('conceptType' => 'DIMENSION')),
			array('name' => 'score', 'label' => Text::_('COM_TJLMS_LESSONREPORT_SCORE'),
				'dataType' => 'NUMBER', 'semantics' => array('conceptType' => 'METRIC')),
			array('name' => 'status', 'label' => Text::_('COM_TJLMS_REPORT_LESSON_STATUS'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION')),
			array('name' => 'attemptsAllowed', 'label' => Text::_('COM_TJLMS_LESSONREPORT_ALLOWEDATTEMPTS'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION')),
			array('name' => 'attemptsDone', 'label' => Text::_('COM_TJLMS_LESSONREPORT_ATTEMPTSMADE'),
				'dataType' => 'NUMBER', 'semantics' => array('conceptType' => 'METRIC')),
			array('name' => 'attemptsGrade', 'label' => Text::_('COM_TJLMS_LESSONREPORT_GRADINGMETHOD'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION')),
			array('name' => 'considerMarks', 'label' => Text::_('COM_TJLMS_LESSONREPORT_COMSIDERMARKS'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION')),
			array('name' => 'lastAccessedOn', 'label' =>
			Text::_('COM_TJLMS_LESSONREPORT_LAST_ACCESSED_ON'), 'dataType' =>
			'STRING', 'semantics' => array('conceptType' => 'DIMENSION', 'semanticType' => 'YEAR_MONTH_DAY')),
			array('name' => 'modifiedDate', 'label' =>
			Text::_('COM_TJLMS_LESSONREPORT_MODIFIED_DATE'), 'dataType' =>
			'STRING', 'semantics' => array('conceptType' => 'DIMENSION', 'semanticType' => 'YEAR_MONTH_DAY')),
			array('name' => 'totalContent', 'label' =>
			Text::_('COM_TJLMS_LESSONREPORT_TOTAL_CONTENT'), 'dataType' =>
			'NUMBER', 'semantics' => array('conceptType' => 'DIMENSION')),
			array('name' => 'modifiedBy', 'label' => Text::_('COM_TJLMS_LESSONREPORT_MODIFIED_BY'),
				'dataType' => 'NUMBER', 'semantics' => array('conceptType' => 'DIMENSION')),
		);
	}
}
