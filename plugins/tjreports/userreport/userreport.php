<?php
/**
 * @package     Tjlms.Plugin
 * @subpackage  Tjlms,TJReport, user report
 *
 * @copyright   Copyright (C) 2005 - 2020 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Component\ComponentHelper;

JLoader::import('com_tjreports.models.reports', JPATH_SITE . '/components');

/**
 * User report plugin of TJReport
 *
 * @since  1.0.0
 */
class TjreportsModelUserreport extends TjreportsModelReports
{
	protected $default_order = 'u.id';

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
		$this->customFieldsQueryJoinOn = 'u.id';

		if (method_exists($this, 'tableExists'))
		{
			$this->customFieldsTableExists = $this->tableExists();
		}

		JLoader::import('administrator.components.com_tjlms.helpers.tjlms', JPATH_SITE);

		$lang = Factory::getLanguage();
		$base_dir = JPATH_SITE . '/administrator';
		$lang->load('com_tjlms', $base_dir);

		$this->columns = array(
			'user_id'              => array('title' => 'COM_TJLMS_ENROLMENT_USER_ID', 'table_column' => 'u.id'),
			'name'                 => array('table_column' => 'u.name', 'title' => 'COM_TJLMS_ENROLMENT_USER_NAME'),
			'username'             => array('table_column' => 'u.username' , 'title' => 'COM_TJLMS_REPORT_USERUSERNAME'),
			'email'                => array('table_column' => 'u.email', 'title' => 'COM_TJLMS_ENROLMENT_USER_EMAIL', 'emailColumn' => true),
			'block'                => array('table_column' => '', 'title' => 'COM_TJLMS_ENROLMENT_USER_BLOCKED'),
			'activation'           => array('table_column' => '', 'title' => 'COM_TJLMS_ENROLMENT_USER_ACTIVATED'),
			'usergroup'            => array('title' => 'COM_TJLMS_REPORT_USERGROUP', 'disable_sorting' => true),
			'enrolledUsers'        => array('title' => 'COM_TJLMS_ENROLMENT_TOTAL_COURSES_ENROLLED', 'table_column' => ''),
			'pendingEnrollment'    => array('title' => 'COM_TJLMS_ENROLMENT_TOTAL_PENDING_ENROLLED', 'table_column' => ''),
			'completedCourses'     => array('title' => 'COM_TJLMS_ENROLMENT_TOTAL_COURSES_COMPLETED', 'table_column' => ''),
			'inCompletedCourses'   => array('title' => 'COM_TJLMS_ENROLMENT_TOTAL_COURSES_INCOMPLETED', 'disable_sorting' => true),
			'timeSpentOnLesson'    => array('title' => 'COM_TJLMS_REPORT_TIMESPENT', 'table_column' => ''),
			'lastVisitDate'        => array('table_column' => 'u.lastvisitDate', 'title' => 'COM_TJLMS_USER_LAST_VISIT_DATE'),
			'registerDate'         => array('table_column' => 'u.registerDate', 'title' => 'COM_TJLMS_USER_REGISTRATION_DATE'),
			'likeCount'            => array('title' => 'COM_TJLMS_LIKES_CNT', 'table_column' => ''),
			'dislikeCount'         => array('title' => 'COM_TJLMS_DISLIKES_CNT', 'table_column' => ''),
			'commentsCount'        => array('title' => 'COM_TJLMS_COMMENTS_CNT', 'table_column' => ''),
			'notesCount'           => array('title' => 'COM_TJLMS_NOTES_CNT', 'table_column' => ''),
			'certCount'            => array('title' => 'COM_TJLMS_CERTIFICATES_CNT', 'table_column' => ''),
			'recommendRcvCount'    => array('title' => 'COM_TJLMS_RECO_RCV_CNT', 'table_column' => ''),
			'recommendMadeCount'   => array('title' => 'COM_TJLMS_RECO_MADE_CNT', 'table_column' => ''),
			'goalCount'            => array('title' => 'COM_TJLMS_GOAL_CNT', 'table_column' => ''),
			'assignCount'          => array('title' => 'COM_TJLMS_ASSIGN_CNT', 'table_column' => ''),
			'completedAssignment'  => array('title' => 'COM_TJLMS_ASSIGN_COMPLETE_CNT', 'table_column' => ''),
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

		$nameFilter     = $TjlmsModelReports->getNameFilter($myTeam);
		$userNameFilter = $TjlmsModelReports->getUserFilter($myTeam);

		$activeArray   = array();
		$activeArray[] = HTMLHelper::_('select.option', '', Text::_('COM_TJLMS_COURSES_TYPE_FILTER'));
		$activeArray[] = HTMLHelper::_('select.option', '0', Text::_('COM_TJLMS_YES'));
		$activeArray[] = HTMLHelper::_('select.option', '1', Text::_('COM_TJLMS_NO'));

		$dispFilters = array(
			array(
					'user_id'     => array(
					'search_type' => 'text', 'type' => 'equal', 'searchin' => 'u.id'
					),
					'name'        => array(
					'search_type' => 'select', 'select_options' => $nameFilter, 'type' => 'equal', 'searchin' => 'u.id'
					),
					'username'    => array(
					'search_type' => 'select', 'select_options' => $userNameFilter, 'type' => 'equal', 'searchin' => 'u.id'
					),
					'email'       => array(
					'search_type' => 'text', 'searchin' => 'u.email'
					),
					'block'       => array(
					'search_type' => 'select', 'select_options' => $activeArray, 'type' => 'equal', 'searchin' => 'u.block'
					),
					'activation'  => array(
					'search_type' => 'select', 'select_options' => $activeArray, 'type' => 'custom'
					),
					'usergroup'   => array(
					'search_type' => 'select', 'select_options' => $this->getUserGroupFilter()
					)
				)
			);

		if (count($reportOptions) > 1)
		{
			$dispFilters[1] = array();
			$dispFilters[1]['report_filter'] = array(
					'search_type' => 'select', 'select_options' => $reportOptions
				);
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

		$createdByClause = $myTeamClause = false;
		$hasUsers        = array();
		$user            = Factory::getUser();
		$userId          = $user->id;

		if ((int) $filters['report_filter'] === 1)
		{
			$createdByClause = true;
		}
		elseif ((int) $filters['report_filter'] === -1)
		{
			$hasUsers     = TjlmsHelper::getSubusers();
			$myTeamClause = true;
		}

		$query->select($db->quoteName('u.id', 'user_id'));
		$query->from($db->quoteName('#__users', 'u'));

		$reportId = $this->getDefaultReport($this->name);
		$viewAll  = $this->checkpermissions($reportId);

		if ($createdByClause)
		{
			$query->where('c.created_by = ' . (int) $userId);
		}
		elseif ($myTeamClause && $hasUsers)
		{
			$query->where('u.id IN(' . implode(',', $hasUsers) . ')');
		}
		elseif (!$viewAll)
		{
			$query->where('u.id=0');
		}

		if (in_array('block', $colToshow))
		{
			$query->select('IF(u.block=1,"' . Text::_('JNO') . '","' . Text::_('JYES') . '") AS block');
		}

		if (in_array('activation', $colToshow))
		{
			$query->select('u.activation');
		}

		$active = $filters['activation'];

		if (is_numeric($active))
		{
			if ($active == '0')
			{
				$query->where('u.activation IN (' . $db->quote('') . ', ' . $db->quote('0') . ')');
			}
			elseif ($active == '1')
			{
				$query->where($query->length('u.activation') . ' > 1');
			}
		}

		if (in_array('enrolledUsers', $colToshow))
		{
			$subQuery = $db->getQuery(true);
			$subQuery->select('COUNT(eu.id)');
			$subQuery->from($db->quoteName('#__tjlms_enrolled_users', 'eu'));
			$subQuery->join('INNER', $db->quoteName('#__tjlms_courses', 'c')
				. ' ON (' . $db->quoteName('c.id') . ' = ' . $db->quoteName('eu.course_id') . ')');
			$subQuery->where($db->quoteName('eu.user_id') . ' = ' . $db->quoteName('u.id'));
			$subQuery->where($db->quoteName('eu.state') . ' = 1');
			$subQuery->where($db->quoteName('c.state') . ' = 1');

			if ($createdByClause )
			{
				$subQuery->where('c.created_by = ' . (int) $userId);
			}

			$query->select('(' . $subQuery . ') as enrolledUsers');
		}

		if (in_array('pendingEnrollment', $colToshow))
		{
			$subQuery = $db->getQuery(true);
			$subQuery->select('COUNT(eu.id)');

			$subQuery->from($db->quoteName('#__tjlms_enrolled_users', 'eu'));
			$subQuery->join('INNER', $db->quoteName('#__tjlms_courses', 'c')
				. ' ON (' . $db->quoteName('c.id') . ' = ' . $db->quoteName('eu.course_id') . ')');
			$subQuery->where($db->quoteName('eu.user_id') . ' = ' . $db->quoteName('u.id'));
			$subQuery->where($db->quoteName('eu.state') . ' = 0');

			if ($createdByClause )
			{
				$subQuery->where('c.created_by = ' . (int) $userId);
			}

			$query->select('(' . $subQuery . ') as pendingEnrollment');
		}

		if (in_array('completedCourses', $colToshow))
		{
			$subQuery = $db->getQuery(true);
			$subQuery->select('COUNT(ct.id) as completedCourses');
			$subQuery->from($db->quoteName('#__tjlms_course_track') . ' as ct');
			$subQuery->join('INNER', $db->quoteName('#__tjlms_courses') . ' as cc ON cc.id=ct.course_id');
			$subQuery->join('INNER', $db->qn('#__tjlms_enrolled_users', 'euu') . ' ON (' . $db->qn('ct.course_id') . ' = ' . $db->qn('euu.course_id') . ')');
			$subQuery->where($db->quoteName('ct.user_id') . ' = ' . $db->quoteName('euu.user_id'));
			$subQuery->where($db->quoteName('ct.user_id') . ' = ' . $db->quoteName('u.id'));
			$subQuery->where($db->quoteName('ct.status') . ' = "C"');
			$subQuery->where($db->quoteName('cc.state') . ' = 1');
			$subQuery->where($db->qn('euu.state') . '=1');

			if ($createdByClause )
			{
				$subQuery->where('cc.created_by = ' . (int) $userId);
			}

			$query->select('(' . $subQuery . ') as completedCourses');
		}

		if (in_array('inCompletedCourses', $colToshow))
		{
			$subQuery = $db->getQuery(true);
			$subQuery->select('COUNT(euu.id) as inCompletedCourses');
			$subQuery->from($db->quoteName('#__tjlms_enrolled_users') . ' as euu');
			$subQuery->join('INNER', $db->qn('#__tjlms_courses') . ' as c ON c.id=euu.course_id');
			$subQuery->join('LEFT', $db->qn('#__tjlms_course_track', 'ct') . ' ON (' . $db->qn('ct.course_id') . ' = ' . $db->qn('euu.course_id') . ')');
			$subQuery->where($db->quoteName('ct.user_id') . ' = ' . $db->quoteName('euu.user_id'));
			$subQuery->join('INNER', $db->qn('#__categories', 'cat') . ' ON (' . $db->qn('cat.id') . ' = ' . $db->qn('c.catid') . ')');
			$subQuery->where($db->qn('cat.published') . ' = 1 ');
			$subQuery->where($db->quoteName('ct.user_id') . ' = ' . $db->quoteName('u.id'));
			$subQuery->where($db->quoteName('ct.status') . ' != "C"');
			$subQuery->where($db->quoteName('c.state') . ' = 1');
			$subQuery->where($db->qn('euu.state') . '=1');

			if ($createdByClause )
			{
				$subQuery->where('c.created_by = ' . (int) $userId);
			}

			$query->select('(' . $subQuery . ') as inCompletedCourses');
		}

		if (in_array('timeSpentOnLesson', $colToshow))
		{
			$subQuery = $db->getQuery(true);
			$subQuery->select('SEC_TO_TIME(SUM(TIME_TO_SEC(time_spent)))');
			$subQuery->from($db->quoteName('#__tjlms_lesson_track') . ' as lt');
			$subQuery->where($db->quoteName('lt.user_id') . ' = ' . $db->quoteName('u.id'));

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

		if (in_array('likeCount', $colToshow))
		{
			$subQuery = $db->getQuery(true);
			$subQuery->select('COUNT(`like`) as likeCount');
			$subQuery->from($db->quoteName('#__jlike_likes') . ' as jl');
			$subQuery->where($db->quoteName('jl.userid') . ' = ' . $db->quoteName('u.id'));
			$subQuery->where($db->quoteName('jl.like') . ' = ' . $db->quote(1));
			$query->select('(' . $subQuery . ') as likeCount');
		}

		if (in_array('dislikeCount', $colToshow))
		{
			$subQuery = $db->getQuery(true);
			$subQuery->select('COUNT(`dislike`) as dislikeCount');
			$subQuery->from($db->quoteName('#__jlike_likes') . ' as jl');
			$subQuery->where($db->quoteName('jl.userid') . ' = ' . $db->quoteName('u.id'));
			$subQuery->where($db->quoteName('jl.dislike') . ' = ' . $db->quote(1));
			$query->select('(' . $subQuery . ') as dislikeCount');
		}

		if (in_array('commentsCount', $colToshow))
		{
			$subQuery = $db->getQuery(true);
			$subQuery->select('COUNT(*) as commentsCount');
			$subQuery->from($db->quoteName('#__jlike_annotations') . ' as ja');
			$subQuery->where($db->quoteName('ja.user_id') . ' = ' . $db->quoteName('u.id'));
			$subQuery->where($db->quoteName('ja.note') . ' = ' . $db->quote(0));
			$query->select('(' . $subQuery . ') as commentsCount');
		}

		if (in_array('notesCount', $colToshow))
		{
			$subQuery = $db->getQuery(true);
			$subQuery->select('COUNT(*) as notesCount');
			$subQuery->from($db->quoteName('#__jlike_annotations') . ' as ja');
			$subQuery->where($db->quoteName('ja.user_id') . ' = ' . $db->quoteName('u.id'));
			$subQuery->where($db->quoteName('ja.note') . ' = ' . $db->quote(1));
			$query->select('(' . $subQuery . ') as notesCount');
		}

		if (in_array('certCount', $colToshow))
		{
			$subQuery = $db->getQuery(true);
			$subQuery->select('COUNT(*) as certCount');
			$subQuery->from($db->quoteName('#__tj_certificate_issue') . ' as tc');
			$subQuery->where($db->quoteName('tc.user_id') . ' = ' . $db->quoteName('u.id'));
			$query->select('(' . $subQuery . ') as certCount');
		}

		if (in_array('recommendRcvCount', $colToshow))
		{
			$subQuery = $db->getQuery(true);
			$subQuery->select('COUNT(*) as recommendRcvCount');
			$subQuery->from($db->quoteName('#__jlike_todos') . ' as jt');
			$subQuery->where($db->quoteName('jt.assigned_to') . ' = ' . $db->quoteName('u.id'));
			$subQuery->where($db->quoteName('jt.type') . ' = ' . $db->quote('reco'));
			$query->select('(' . $subQuery . ') as recommendRcvCount');
		}

		if (in_array('recommendMadeCount', $colToshow))
		{
			$subQuery = $db->getQuery(true);
			$subQuery->select('COUNT(*) as recommendMadeCount');
			$subQuery->from($db->quoteName('#__jlike_todos') . ' as jt');
			$subQuery->where($db->quoteName('jt.assigned_by') . ' = ' . $db->quoteName('u.id'));
			$subQuery->where($db->quoteName('jt.type') . ' = ' . $db->quote('reco'));
			$query->select('(' . $subQuery . ') as recommendMadeCount');
		}

		if (in_array('goalCount', $colToshow))
		{
			$subQuery = $db->getQuery(true);
			$subQuery->select('COUNT(*) as goalCount');
			$subQuery->from($db->quoteName('#__jlike_todos') . ' as jt');
			$subQuery->where($db->quoteName('jt.assigned_by') . ' = ' . $db->quoteName('u.id'));
			$subQuery->where($db->quoteName('jt.type') . ' = ' . $db->quote('assign'));
			$subQuery->where($db->quoteName('jt.assigned_by') . ' = ' . $db->quoteName('jt.assigned_to'));
			$query->select('(' . $subQuery . ') as goalCount');
		}

		if (in_array('assignCount', $colToshow))
		{
			$subQuery = $db->getQuery(true);
			$subQuery->select('COUNT(*) as assignCount');
			$subQuery->from($db->quoteName('#__jlike_todos') . ' as jt');
			$subQuery->where($db->quoteName('jt.assigned_by') . ' = ' . $db->quoteName('u.id'));
			$subQuery->where($db->quoteName('jt.type') . ' = ' . $db->quote('assign'));
			$subQuery->where($db->quoteName('jt.assigned_by') . ' <> ' . $db->quoteName('jt.assigned_to'));
			$query->select('(' . $subQuery . ') as assignCount');
		}

		if (in_array('completedAssignment', $colToshow))
		{
			$subQuery = $db->getQuery(true);
			$subQuery->select('COUNT(ct.status) as completedAssignment');
			$subQuery->from($db->quoteName('#__jlike_todos') . ' as jt');
			$subQuery->join('INNER', $db->quoteName('#__jlike_content', 'jc')
					. ' ON (' . $db->quoteName('jt.content_id') . ' = ' . $db->quoteName('jc.id') . ')');
			$subQuery->join('INNER', $db->quoteName('#__tjlms_course_track', 'ct')
					. ' ON (' . $db->quoteName('ct.course_id') . ' = ' . $db->quoteName('jc.element_id') . ')');
			$subQuery->where($db->quoteName('ct.user_id') . ' = ' . $db->quoteName('u.id'));
			$subQuery->where($db->quoteName('jc.element') . ' = ' . $db->quote('com_tjlms.course'));
			$subQuery->where($db->quoteName('ct.status') . ' = ' . $db->quote('C'));
			$subQuery->where($db->quoteName('jt.assigned_by') . ' <> ' . $db->quoteName('jt.assigned_to'));

			$query->select('(' . $subQuery . ') as completedAssignment');
		}

		if (in_array('incompleteAssignment', $colToshow))
		{
			$subQuery = $db->getQuery(true);
			$subQuery->select('COUNT(*) as incompleteAssignment');
			$subQuery->from($db->quoteName('#__jlike_todos') . ' as jt');
			$subQuery->join('INNER', $db->quoteName('#__jlike_content', 'jc')
					. ' ON (' . $db->quoteName('jt.content_id') . ' = ' . $db->quoteName('jc.id') . ')');
			$subQuery->join('INNER', $db->quoteName('#__tjlms_course_track', 'ct')
					. ' ON (' . $db->quoteName('ct.course_id') . ' = ' . $db->quoteName('jc.element_id') . ')');
			$subQuery->where($db->quoteName('ct.user_id') . ' = ' . $db->quoteName('u.id'));
			$subQuery->where($db->quoteName('jt.assigned_to') . ' = ' . $db->quoteName('u.id'));
			$subQuery->where($db->quoteName('jc.element') . ' = ' . $db->quote('com_tjlms.course'));
			$subQuery->where($db->quoteName('ct.status') . ' <> ' . $db->quote('C'));
			$subQuery->where($db->quoteName('jt.assigned_by') . ' <> ' . $db->quoteName('jt.assigned_to'));
			$query->select('(' . $subQuery . ') as incompleteAssignment');
		}

		$query->group('u.id');

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

		if (empty($items))
		{
			return;
		}

		$colToshow      = $this->getState('colToshow');
		$lmsparams      = ComponentHelper::getParams('com_tjlms');
		$dateFormatShow = $lmsparams->get('date_format_show', 'Y-m-d H:i:s');

		jimport('techjoomla.common');
		$tjCommon = new TechjoomlaCommon;

		foreach ($items as &$item)
		{
			if ($item['activation'] == '0' || $item['activation'] == "")
			{
				$item['activation'] = Text::_('JYES');
			}
			else
			{
				$item['activation'] = Text::_('JNO');
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
	 * @since   1.3.31
	 */
	public function getGDSFields()
	{
		return array(
				array('name' => 'user_id', 'label' => Text::_('COM_TJLMS_ENROLMENT_USERID'),
				'dataType'   => 'NUMBER', 'semantics' => array('conceptType' => 'DIMENSION')),
				array('name' => 'name', 'label' => Text::_('COM_TJLMS_ENROLMENT_USER_NAME'),
				'dataType'   => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION')),
				array('name' => 'username', 'label' => Text::_('COM_TJLMS_REPORT_USERUSERNAME'),
				'dataType'   => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION')),
				array('name' => 'email', 'label' => Text::_('COM_TJLMS_REPORT_USEREMAIL'),
				'dataType'   => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION')),
				array('name' => 'usergroup', 'label' => Text::_('COM_TJLMS_REPORT_USERGROUP'),
				'dataType'   => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION')),
				array('name' => 'block', 'label' => Text::_('COM_TJLMS_ENROLMENT_USER_BLOCKED'),
				'dataType'   => 'NUMBER', 'semantics' => array('conceptType' => 'DIMENSION')),
				array('name' => 'enrolledUsers', 'label' => Text::_('COM_TJLMS_ENROLMENT_TOTAL_COURSES_ENROLLED'),
				'dataType'   => 'NUMBER', 'semantics' => array('conceptType' => 'METRIC')),
				array('name' => 'pendingEnrollment', 'label' => Text::_('COM_TJLMS_ENROLMENT_TOTAL_PENDING_ENROLLED'),
				'dataType'   => 'NUMBER', 'semantics' => array('conceptType' => 'METRIC')),
				array('name' => 'completedCourses', 'label' => Text::_('COM_TJLMS_ENROLMENT_TOTAL_COURSES_COMPLETED'),
				'dataType'   => 'NUMBER', 'semantics' => array('conceptType' => 'METRIC')),
				array('name' => 'inCompletedCourses', 'label' => Text::_('COM_TJLMS_ENROLMENT_TOTAL_COURSES_INCOMPLETED'),
				'dataType'   => 'NUMBER', 'semantics' => array('conceptType' => 'METRIC')),
				array('name' => 'timeSpentOnLesson', 'label' => Text::_('COM_TJLMS_REPORT_TIMESPENT'),
				'dataType'   => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION')),
				array('name' => 'lastVisitDate', 'label' => Text::_('COM_TJLMS_USER_LAST_VISIT_DATE'),
				'dataType'   => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION', 'semanticType' => 'YEAR_MONTH_DAY')),
				array('name' => 'registerDate', 'label' => Text::_('COM_TJLMS_USER_REGISTRATION_DATE'),
				'dataType'   => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION', 'semanticType' => 'YEAR_MONTH_DAY')),
				array('name' => 'likeCount', 'label' => Text::_('COM_TJLMS_LIKES_CNT'),
				'dataType'   => 'NUMBER', 'semantics' => array('conceptType' => 'METRIC')),
				array('name' => 'dislikeCount', 'label' => Text::_('COM_TJLMS_DISLIKES_CNT'),
				'dataType'   => 'NUMBER', 'semantics' => array('conceptType' => 'METRIC')),
				array('name' => 'notesCount', 'label' => Text::_('COM_TJLMS_NOTES_CNT'),
				'dataType'   => 'NUMBER', 'semantics' => array('conceptType' => 'METRIC')),
				array('name' => 'commentsCount', 'label' => Text::_('COM_TJLMS_COMMENTS_CNT'),
				'dataType'   => 'NUMBER', 'semantics' => array('conceptType' => 'METRIC')),
				array('name' => 'certCount', 'label' => Text::_('COM_TJLMS_CERTIFICATES_CNT'),
				'dataType'   => 'NUMBER', 'semantics' => array('conceptType' => 'METRIC')),
				array('name' => 'recommendRcvCount', 'label' => Text::_('COM_TJLMS_RECO_RCV_CNT'),
				'dataType'   => 'NUMBER', 'semantics' => array('conceptType' => 'METRIC')),
				array('name' => 'recommendMadeCount', 'label' => Text::_('COM_TJLMS_RECO_MADE_CNT'),
				'dataType'   => 'NUMBER', 'semantics' => array('conceptType' => 'METRIC')),
				array('name' => 'goalCount', 'label' => Text::_('COM_TJLMS_GOAL_CNT'),
				'dataType'   => 'NUMBER', 'semantics' => array('conceptType' => 'METRIC')),
				array('name' => 'assignCount', 'label' => Text::_('COM_TJLMS_ASSIGN_CNT'),
				'dataType'   => 'NUMBER', 'semantics' => array('conceptType' => 'METRIC')),
				array('name' => 'completedAssignment', 'label' => Text::_('COM_TJLMS_ASSIGN_COMPLETE_CNT'),
				'dataType'   => 'NUMBER', 'semantics' => array('conceptType' => 'METRIC')),
				array('name' => 'incompleteAssignment', 'label' => Text::_('COM_TJLMS_ASSIGN_COMPLETE_CNT'),
				'dataType'   => 'NUMBER', 'semantics' => array('conceptType' => 'METRIC')),
			);
	}
}
