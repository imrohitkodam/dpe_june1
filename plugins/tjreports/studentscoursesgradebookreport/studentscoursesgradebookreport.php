<?php
/**
 * @package     Tjlms.Plugin
 * @subpackage  Tjlms,TJReport,studentscoursesgradebookreport
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
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 * Course grade book report plugin of TJReport
 *
 * @since  1.3.30
 */
class TjreportsModelStudentscoursesgradebookreport extends TjreportsModelReports
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
			'user_id'       => array('table_column' => 'u.id', 'not_show_hide' => true),
			'course_state'  => array('table_column' => 'c.state', 'not_show_hide' => true),
			'title'         => array('table_column' => 'c.title', 'title' => 'COM_TJLMS_COURSE_NAME'),
			'percentage'    => array('title' => 'PLG_TJREPORTS_STUDENTSCOURSESGRADEBOOK_PERCENTAGE'),
			'lesson::title' => array('title' => 'COM_TJLMS_REPORT_QUIZ_NAME'),
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

		// Course states (unpublished, published)
		$courseStates      = array(0, 1);
		$courseFilter      = $TjlmsModelReports->getCourseFilter($created_by, $courseStates);
		$userFilter        = $this->getEnrolUserFilter();

		$courseStateArray = array();
		$courseStateArray[] = HTMLHelper::_('select.option', '', Text::_('PLG_TJREPORTS_STUDENTSCOURSESGRADEBOOK_COURSE_STATE')
			);
		$courseStateArray[] = HTMLHelper::_('select.option', '1', Text::_('JPUBLISHED'));
		$courseStateArray[] = HTMLHelper::_('select.option', '0', Text::_('JUNPUBLISHED'));

		$attemptStateArray = array();
		$attemptStateArray[] = HTMLHelper::_('select.option', '', Text::_('COM_TJLMS_FILTER_SELECT_STATE'));
		$attemptStateArray[] = HTMLHelper::_('select.option', '1', Text::_('COM_TJLMS_FILTER_STATE_ACTIVE'));
		$attemptStateArray[] = HTMLHelper::_('select.option', '0', Text::_('COM_TJLMS_FILTER_STATE_EXPIRED'));

		$dispFilters = array(
			array(
				'title' => array(
					'search_type' => 'select', 'select_options' => $courseFilter, 'type' => 'equal', 'searchin' => 'c.id'
				)
			),
			array(
				'user_id' => array(
					'search_type' => 'select', 'select_options' => $userFilter, 'type' => 'equal', 'searchin' => 'u.id'
				),
				'course_state' => array(
					'search_type' => 'select', 'select_options' => $courseStateArray, 'type' => 'equal', 'searchin' => 'c.state'
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
		$query   = parent::getListQuery();
		$filters = $this->getState('filters');

		// Must have columns to get details of non linked data like completion
		$query->select(array('eu.user_id as user_id', 'c.id as course_id','l.total_marks'));
		$query->from($this->_db->quoteName('#__tjlms_enrolled_users', 'eu'));
		$query->join('INNER', $this->_db->quoteName('#__users', 'u') . 'ON ('
			. $this->_db->quoteName('u.id') . ' = ' . $this->_db->quoteName('eu.user_id') . ')' );
		$query->join('INNER', $this->_db->quoteName('#__tjlms_courses', 'c') . 'ON ('
			. $this->_db->quoteName('c.id') . ' = ' . $this->_db->quoteName('eu.course_id') . ')' );
		$query->join('INNER', $this->_db->quoteName('#__tjlms_lessons', 'l') . 'ON ('
			. $this->_db->quoteName('l.course_id') . ' = ' . $this->_db->quoteName('c.id') . ')' );
		$query->where($this->_db->quoteName('l.format') . ' IN ("quiz", "exercise")');

		$filters = (array) $this->getState('filters');

		if (empty($filters['user_id']))
		{
			$loginUserId = Factory::getUser()->id;
			$query->where($this->_db->quoteName('eu.user_id') . ' = ' . (int) $loginUserId);
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
				$query->where('eu.user_id IN(' . implode(',', $hasUsers) . ')');
			}
			else
			{
				$query->where('eu.user_id=0');
			}
		}

		$query->where($this->_db->quoteName('c.state') . 'IN(0, 1)');
		$query->where($this->_db->quoteName('eu.state') . ' = ' . 1);
		$query->group('eu.user_id,eu.course_id');

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
		$detail = array('client' => 'com_tjlms', 'title' => Text::_('PLG_TJREPORTS_STUDENTSCOURSESGRADEBOOK_TITLE'));

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

		$this->getAdditionalColNames($filters['user_id']);

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
			$course_id     = $item['course_id'];
			$assigned_user = $item['user_id'];
			$totalScore    = 0;
			$totalMarks    = 0;
			$lessonCount   = 1;

			foreach ($this->lessonColumns[$course_id] as $key => $detail)
			{
				$score = ' - ';
				$attemptStatus = ' - ';

				$lessonAttempt = (array) $trackingHelper->getLessonattemptsGrading($detail['detail'], $assigned_user);

				// Return $score and $lesson_status
				extract($lessonAttempt);

				$newKey = $lessonCount . '::' . Text::_('PLG_TJREPORTS_STUDENTSCOURSESGRADEBOOK_LESSON') . $lessonCount;

				$item[$newKey]                  = array();
				$item[$newKey]['lesson::title'] = $detail['detail']->title;
				$item[$newKey]['lesson::score'] = round($score);

				if ($lessonAttempt['lesson_status'] != 'not_started')
				{
					$attemptStatus = $item['lesson_track_id'] ? Text::_('COM_TJLMS_ATTEMPTREPORT_STATE_EXPIRED') : Text::_('COM_TJLMS_ATTEMPTREPORT_STATE_ACTIVE');
				}

				$item[$newKey]['lesson::attempt_state'] = $attemptStatus;

				$totalScore = $totalScore + $score;
				$totalMarks = $totalMarks + $detail['detail']->total_marks;

				$lessonCount++;
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
	 * @param   INT  $userId  User ID
	 *
	 * @return    void
	 *
	 * @since   1.3.30
	 */
	private function getAdditionalColNames($userId)
	{
		$query = $this->_db->getQuery(true);

		$query->select(
			array('l.id', 'l.title', 'l.format', 'l.attempts_grade',
			'l.total_marks', 'l.course_id AS courseId')
			);
		$query->from($this->_db->quoteName('#__tjlms_lessons', 'l'));
		$query->join('INNER', $this->_db->quoteName('#__tjlms_enrolled_users', 'eu')
			. 'ON (' . $this->_db->quoteName('eu.course_id') . ' = ' . $this->_db->quoteName('l.course_id') . ')' );

		if (empty($userId))
		{
			$loginUserId = Factory::getUser()->id;
			$query->where($this->_db->quoteName('eu.user_id') . ' = ' . (int) $loginUserId);
		}
		else
		{
			$query->where($this->_db->quoteName('eu.user_id') . ' = ' . (int) $userId);
		}

		$query->where($this->_db->quoteName('l.format') . ' IN ("quiz", "exercise")');
		$query->order('l.ordering asc');

		$this->_db->setQuery($query);

		$lessons = $this->_db->loadObjectList();

		$lessons = ArrayHelper::pivot((array) $lessons, 'courseId');

		$colToshow = $this->getState('colToshow', Array());

		if (!empty($lessons))
		{
			$this->headerLevel = 2;

			$lessonCount = 1;

			foreach ($lessons as $key => $lesson)
			{
				$lessonArray = $lesson;

				if (is_object($lesson))
				{
					$lessonArray = array();
					$lessonArray[] = $lesson;
				}

				foreach ($lessonArray as $lessonkey => $singleLesson)
				{
					$colKey = $lessonCount . '::' . Text::_('PLG_TJREPORTS_STUDENTSCOURSESGRADEBOOK_LESSON') . $lessonCount;

					$this->lessonColumns[$key][$lessonkey] = $colToshow[$colKey] = array(
						'lesson::title'         => ' - ',
						'lesson::score'         => ' - ',
						'lesson::attempt_state' => ' - '
					);

					$this->lessonColumns[$key][$lessonkey]['detail'] = $singleLesson;

					$lessonCount++;
				}
			}
		}

		$this->setState('colToshow', $colToshow);
	}

	/**
	 * Function to get the user filter
	 *
	 * @return  Array
	 *
	 * @since 1.0.0
	 */
	private function getEnrolUserFilter()
	{
		$user       = Factory::getUser();
		$userFilter = array();

		// If user don't have manage all and manage own enrollment permission then only show his enrolments
		if (!$user->authorise('view.manageenrollment', 'com_tjlms') && !$user->authorise('view.own.manageenrollment', 'com_tjlms'))
		{
			$userFilter[] = HTMLHelper::_('select.option', $user->id, $user->name);

			return $userFilter;
		}

		$query = $this->_db->getQuery(true);
		$query->select((array(('DISTINCT u.id'),'u.name')));
		$query->from($this->_db->qn('#__users', 'u'));
		$query->join('INNER', $this->_db->quoteName('#__tjlms_enrolled_users', 'eu')
			. 'ON (' . $this->_db->quoteName('u.id') . ' = ' . $this->_db->quoteName('eu.user_id') . ')' );

		$query->join('INNER', $this->_db->quoteName('#__tjlms_courses', 'c')
			. 'ON (' . $this->_db->quoteName('c.id') . ' = ' . $this->_db->quoteName('eu.course_id') . ')' );

		if ($user->authorise('view.own.manageenrollment', 'com_tjlms') && !$user->authorise('view.manageenrollment', 'com_tjlms'))
		{
			$query->where($this->_db->quoteName('c.created_by') . ' = ' . (int) $user->id);
		}

		$query->where($this->_db->qn('u.block') . ' <> 1');
		$query->where($this->_db->qn('c.state') . 'IN(0, 1)');

		$this->_db->setQuery($query);
		$users = $this->_db->loadObjectList();

		$userFilter   = array();
		$userFilter[] = HTMLHelper::_('select.option', '', Text::_('COM_TJLMS_FILTER_SELECT_USER'));

		foreach ($users as $eachUser)
		{
			$userFilter[] = HTMLHelper::_('select.option', $eachUser->id, $eachUser->name);
		}

		return $userFilter;
	}
}
