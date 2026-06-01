<?php
/**
 * @package     Tjlms.Plugin
 * @subpackage  Tjlms,TJReport,coursegradereport
 *
 * @copyright   Copyright (C) 2005 - 2020 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Language\Text;

JLoader::import('com_tjreports.models.reports', JPATH_SITE . '/components');

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 * Course grade book report plugin of TJReport
 *
 * @since  1.3.30
 */
class TjreportsModelCoursegradebookreport extends TjreportsModelReports
{
	protected $default_order      = 'name';

	protected $default_order_dir  = 'ASC';

	public $showSearchResetButton = -1;

	private $lessonColumns        = array();

	/**
	 * Load the language file on instantiation.
	 *
	 * @var    boolean
	 * @since  1.3.30
	 */
	protected $autoloadLanguage = true;

	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see     JModelLegacy
	 * @since   1.3.30
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

		$lang     = Factory::getLanguage();
		$base_dir = JPATH_SITE . '/administrator';
		$lang->load('com_tjlms', $base_dir);

		$this->columns = array(
			'course_id'     => array('table_column' => 'c.id', 'not_show_hide' => true),
			'name'          => array('table_column' => 'u.name', 'title' => 'COM_TJLMS_REPORT_USERNAME'),
			'username'      => array('table_column' => 'u.username', 'title' => 'COM_TJLMS_REPORT_USERUSERNAME'),
			'percentage'    => array('title' => 'PLG_TJREPORTS_COURSEGRADEBOOKREPORT_PERCENTAGE'),
			'lesson::score' => array('title' => 'COM_TJLMS_REPORT_LESSON_SCORE'),
			'lesson::attempt_state' => array('title' => 'COM_TJLMS_REPORT_LESSON_ATTEMPT_STATE')
		);

		parent::__construct($config);
	}

	/**
	 * Add stylesheets
	 *
	 * @return ARRAY Styles url
	 *
	 * @since  1.3.30
	 * */
	public function getStyles()
	{
		return array(
			Uri::root(true) . '/media/com_tjlms/css/tjlms_backend.css',
			Uri::root(true) . '/media/com_tjlms/font-awesome/css/font-awesome.min.css'
		);
	}

	/**
	 * Create an array of filters
	 *
	 * @return    mixed
	 *
	 * @since   1.3.30
	 */
	public function displayFilters()
	{
		$reportOptions = TjlmsHelper::getReportFilterValues($this, $selected, $created_by, $myTeam);

		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjlms/models');
		$TjlmsModelReports = BaseDatabaseModel::getInstance('Reports', 'TjlmsModel', array('ignore_request' => true));
		$userFilter        = $TjlmsModelReports->getUserFilter($myTeam);
		$courseFilter      = $this->getCourseFilter();

		$filters = $this->getState('filters');

		$attemptStateArray = array();
		$attemptStateArray[] = HTMLHelper::_('select.option', '', Text::_('COM_TJLMS_FILTER_SELECT_STATE'));
		$attemptStateArray[] = HTMLHelper::_('select.option', '1', Text::_('COM_TJLMS_FILTER_STATE_ACTIVE'));
		$attemptStateArray[] = HTMLHelper::_('select.option', '0', Text::_('COM_TJLMS_FILTER_STATE_EXPIRED'));

		$dispFilters = array(
			array(
				'username' => array(
					'search_type' => 'select', 'select_options' => $userFilter, 'type' => 'equal', 'searchin' => 'u.id'
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

		// Joomla fields integration
		// Call parent function to set filters for custom fields
		if (method_exists(get_parent_class($this), 'setCustomFieldsDisplayFilters'))
		{
			parent::setCustomFieldsDisplayFilters($dispFilters);
		}

		return $dispFilters;
	}

	/**
	 * Method to get a JDatabaseQuery object for retrieving the data set from a database.
	 *
	 * @return  JDatabaseQuery  A JDatabaseQuery object to retrieve the data set.
	 *
	 * @since   1.3.30
	 */
	protected function getListQuery()
	{
		$query = parent::getListQuery();

		// Must have columns to get details of non linked data like completion
		$query->select(array('u.id as user_id', 'c.id as course_id','l.total_marks'));
		$query->from($this->_db->quoteName('#__tjlms_enrolled_users', 'eu'));
		$query->join('INNER', $this->_db->quoteName('#__users', 'u') . 'ON ('
			. $this->_db->quoteName('u.id') . ' = ' . $this->_db->quoteName('eu.user_id') . ')' );
		$query->join('INNER', $this->_db->quoteName('#__tjlms_courses', 'c') . 'ON ('
			. $this->_db->quoteName('c.id') . ' = ' . $this->_db->quoteName('eu.course_id') . ')' );
		$query->join('INNER', $this->_db->quoteName('#__tjlms_lessons', 'l') . 'ON ('
			. $this->_db->quoteName('l.course_id') . ' = ' . $this->_db->quoteName('c.id') . ')' );
		$query->where($this->_db->quoteName('l.format') . ' IN ("quiz", "exercise")');

		$filters = (array) $this->getState('filters');

		if (empty($filters['course_id']))
		{
			$query->where($this->_db->quoteName('c.id') . ' = ' . 0);
		}

		if ($filters['attempt_state'] == '0')
		{
			$query->select('lt.lesson_track_id');
			$query->join('INNER', $this->_db->quoteName('#__tjlms_lesson_track_archive', 'lt') . 'ON (
			lt.lesson_id = l.id AND lt.user_id = u.id)' );
		}
		elseif ($filters['attempt_state'] == '1')
		{
			$query->join('INNER', $this->_db->quoteName('#__tjlms_lesson_track', 'lt') . 'ON (
			lt.lesson_id = l.id AND lt.user_id = u.id)' );
		}

		if ((int) $filters['report_filter'] === -1)
		{
			$hasUsers = TjlmsHelper::getSubusers();

			if ($hasUsers)
			{
				$query->where($this->_db->quoteName('eu.user_id') . 'IN (' . implode(',', $hasUsers) . ')');
			}
			else
			{
				$query->where($this->_db->quoteName('eu.user_id') . ' = ' . 0);
			}
		}

		$query->where($this->_db->quoteName('eu.state') . ' = ' . 1);
		$query->where($this->_db->quoteName('c.state') . 'IN(0, 1)');
		$query->group('eu.course_id,eu.user_id');

		return $query;
	}

	/**
	 * Get client of this plugin
	 *
	 * @return Array Client
	 *
	 * @since   1.3.30
	 * */
	public function getPluginDetail()
	{
		$detail = array('client' => 'com_tjlms', 'title' => Text::_('PLG_TJREPORTS_COURSEGRADEBOOKREPORT_TITLE'));

		return $detail;
	}

	/**
	 * Method to get an array of data items.
	 *
	 * @return  mixed  An array of data items on success, false on failure.
	 *
	 * @since  1.3.30
	 */
	public function getItems()
	{
		$filters = (array) $this->getState('filters');

		if (empty($filters['course_id']))
		{
			$this->setTJRMessages(Text::_('PLG_TJREPORTS_COURSEGRADEBOOKREPORT_SELECT_COURSE_MESSAGE'));

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

		JLoader::import('components.com_tjlms.helpers.tracking', JPATH_SITE);
		$trackingHelper = new ComtjlmstrackingHelper;

		$colToshow = $this->getState('colToshow');

		foreach ($items as &$item)
		{
			$assigned_user = $item['user_id'];
			$totalScore    = 0;
			$totalMarks    = 0;

			foreach ($this->lessonColumns as $key => $detail)
			{
				$score = ' - ';
				$attemptStatus = ' - ';

				$lessonAttempt = (array) $trackingHelper->getLessonattemptsGrading($detail['detail'], $assigned_user);

				// Return $score and $lesson_status
				extract($lessonAttempt);

				$item[$key]                  = array();
				$item[$key]['lesson::score'] = round($score);

				if ($lessonAttempt['lesson_status'] != 'not_started')
				{
					$attemptStatus = $item['lesson_track_id'] ? Text::_('COM_TJLMS_ATTEMPTREPORT_STATE_EXPIRED') : Text::_('COM_TJLMS_ATTEMPTREPORT_STATE_ACTIVE');
				}

				$item[$key]['lesson::attempt_state'] = $attemptStatus;

				$totalScore = $totalScore + $score;
				$totalMarks = $totalMarks + $detail['detail']->total_marks;
			}

			if (in_array('percentage', $colToshow) )
			{
				$item['percentage'] = floor(($totalScore / $totalMarks) * 100) . '%';
			}
		}

		$items = $this->sortCustomColumns($items);

		return $items;
	}

	/**
	 * Create Extra columns
	 *
	 * @param   INT  $courseId  Course ID
	 *
	 * @return    void
	 *
	 * @since   1.3.30
	 */
	private function getAdditionalColNames($courseId)
	{
		$query = $this->_db->getQuery(true);

		$query->select(array('l.id', 'l.title', 'CONCAT_WS("::", l.id, l.title) AS lessonKey', 'l.format', 'l.attempts_grade', 'l.total_marks'));
		$query->from($this->_db->quoteName('#__tjlms_lessons', 'l'));
		$query->where($this->_db->quoteName('l.course_id') . ' = ' . (int) $courseId);
		$query->where($this->_db->quoteName('l.format') . ' IN ("quiz", "exercise")');
		$query->order('l.ordering asc');
		$this->_db->setQuery($query);

		$lessons = $this->_db->loadObjectList('lessonKey');

		$colToshow = $this->getState('colToshow', Array());

		if (!empty($lessons))
		{
			$this->headerLevel = 2;

			foreach ($lessons as $key => $lesson)
			{
				$this->lessonColumns[$key] = $colToshow[$key] = array(
					'lesson::score'         => ' - ',
					'lesson::attempt_state' => ' - '
				);

				$this->lessonColumns[$key]['detail'] = $lesson;
			}
		}

		$this->setState('colToshow', $colToshow);
	}

	/**
	 * Function to get the course filter
	 *
	 * @return  Array
	 *
	 * @since 1.3.30
	 */
	public function getCourseFilter()
	{
		$user         = Factory::getUser();
		$courseFilter = array();

		$query = $this->_db->getQuery(true);

		$query->select('DISTINCT(id) as id,title');
		$query->from($this->_db->qn('#__tjlms_courses'));

		// If user don't have manage all and manage own enrollment permission then only show his enrolments
		if (!$user->authorise('view.manageenrollment', 'com_tjlms') && !$user->authorise('view.own.manageenrollment', 'com_tjlms'))
		{
			return array();
		}

		if ($user->authorise('view.own.manageenrollment', 'com_tjlms') && !$user->authorise('view.manageenrollment', 'com_tjlms'))
		{
			$query->where($this->_db->qn('created_by') . ' = ' . (int) $user->id);
		}

		$query->where($this->_db->quoteName('state') . 'IN(0, 1)');

		$this->_db->setQuery($query);
		$courses = $this->_db->loadObjectList();

		$courseFilter[] = HTMLHelper::_('select.option', '', Text::_('COM_TJLMS_FILTER_SELECT_COURSE'));

		if (!empty($courses))
		{
			foreach ($courses as $eachCourse)
			{
				$courseFilter[] = HTMLHelper::_('select.option', $eachCourse->id, $eachCourse->title);
			}
		}

		return $courseFilter;
	}
}
