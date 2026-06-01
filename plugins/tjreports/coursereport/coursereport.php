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
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Component\ComponentHelper;

JLoader::import('com_tjreports.models.reports', JPATH_SITE . '/components');

/**
 * Course report plugin of TJReport
 *
 * @since  1.0.0
 */
class TjreportsModelCoursereport extends TjreportsModelReports
{
	protected $default_order = 'name';

	protected $default_order_dir = 'ASC';

	public $showSearchResetButton = false;

	protected $lmsparams;

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
		$this->lmsparams = ComponentHelper::getParams('com_tjlms');

		$lang = Factory::getLanguage();
		$base_dir = JPATH_SITE . '/administrator';
		$lang->load('com_tjlms', $base_dir);

		$this->columns = array(
			'id'                => array('table_column' => 'c.id', 'title' => 'COM_TJLMS_COURSE_ID'),
			'title'             => array('table_column' => 'c.title', 'title' => 'COM_TJLMS_COURSE_NAME'),
			'created_by'        => array('table_column' => 'c.created_by', 'title' => 'JGLOBAL_FIELD_CREATED_BY_LABEL'),
			'type'              => array('title' => 'COM_TJLMS_COURSE_TYPE', 'table_column' => ''),
			'access'            => array('table_column' => 'vl.title', 'title' => 'COM_TJLMS_ACL_GROUP'),
			'cat_title'         => array('table_column' => 'cat.title', 'title' => 'COM_TJLMS_COURSES_CAT_ID'),
			'short_desc'        => array('table_column' => 'c.short_desc', 'title' => 'COM_TJLMS_COURSES_SHORT_DESC', 'disable_sorting' => true),
			'state'             => array('table_column' => 'c.state', 'title' => 'COM_TJLMS_COURSES_STATUS'),
			'featured'          => array('table_column' => 'c.featured', 'title' => 'COM_TJLMS_COURSES_FEATURED'),
			'start_date'        => array('table_column' => 'c.start_date', 'title' => 'COM_TJLMS_COURSES_START_DATE'),
			'certificate_term'  => array('table_column' => 'c.certificate_term', 'title' => 'COM_TJLMS_COURSES_CERTIFICATE_TERM'),
			'metakey'           => array('table_column' => 'c.metakey', 'title' => 'COM_TJLMS_COURSES_METAKEYWORDS', 'disable_sorting' => true),
			'metadesc'          => array('table_column' => 'c.metadesc', 'title' => 'COM_TJLMS_COURSES_DESCRIPTION', 'disable_sorting' => true),
			'lessonsCount'      => array('title' => 'COM_TJLMS_LESSONS_CNT', 'table_column' => ''),
			'enrolledUsers'     => array('title' => 'COM_TJLMS_ENROLLED_USERS_CNT', 'table_column' => ''),
			'pendingEnrollment' => array('title' => 'COM_TJLMS_PENDING_ENROLLED_USERS_CNT', 'table_column' => ''),
			'completedUsers'    => array('title' => 'COM_TJLMS_COMPLETED_USERS_CNT', 'table_column' => ''),
			'likeCount'         => array('title' => 'COM_TJLMS_LIKES_CNT', 'table_column' => ''),
			'dislikeCount'      => array('title' => 'COM_TJLMS_DISLIKES_CNT', 'table_column' => ''),
			'commentsCount'     => array('title' => 'COM_TJLMS_COMMENTS_CNT', 'table_column' => ''),
			'assignCount'       => array('title' => 'COM_TJLMS_ASSIGN_CNT', 'table_column' => ''),
			'recommendCount'    => array('title' => 'COM_TJLMS_RECO_CNT', 'table_column' => ''),
			'courseTags'        => array('title' => 'COM_TJLMS_COURSE_TAGS', 'table_column' => '')
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
		$detail = array('client' => 'com_tjlms', 'title' => Text::_('PLG_TJREPORTS_COURSEREPORT_TITLE'));

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
		$TjlmsModelReports = new TjlmsModelReports;
		$catFilter         = $TjlmsModelReports->getCatFilter();
		$courseFilter      = $TjlmsModelReports->getCourseFilter();
		$accesslevelFilter = HTMLHelper::_('access.assetgroups');
		array_unshift($accesslevelFilter, HTMLHelper::_('select.option', '', Text::_('JOPTION_ACCESS_SHOW_ALL_LEVELS')));

		$typeArray   = array();
		$typeArray[] = HTMLHelper::_('select.option', '', Text::_('COM_TJLMS_COURSES_TYPE_FILTER'));
		$typeArray[] = HTMLHelper::_('select.option', '0', Text::_('COM_TJLMS_COURSES_TYPE_FILTER_FREE'));
		$typeArray[] = HTMLHelper::_('select.option', '1', Text::_('COM_TJLMS_COURSES_TYPE_FILTER_PAID'));

		$statusArray   = array();
		$statusArray[] = HTMLHelper::_('select.option', '', Text::_('COM_TJLMS_COURSES_STATUS_SELECT'));
		$statusArray[] = HTMLHelper::_('select.option', '1', Text::_('COM_TJLMS_COURSES_STATUS_PUBLISHED'));
		$statusArray[] = HTMLHelper::_('select.option', '0', Text::_('COM_TJLMS_COURSES_STATUS_UNPUBLISHED'));
		$statusArray[] = HTMLHelper::_('select.option', '-2', Text::_('COM_TJLMS_COURSES_STATUS_TRASHED'));

		$featuredArray   = array();
		$featuredArray[] = HTMLHelper::_('select.option', '', Text::_('COM_TJLMS_COURSES_FEATURED_SELECT'));
		$featuredArray[] = HTMLHelper::_('select.option', '1', Text::_('COM_TJLMS_COURSES_FEATURED_YES'));
		$featuredArray[] = HTMLHelper::_('select.option', '0', Text::_('COM_TJLMS_COURSES_FEATURED_NO'));

		$certificateTermArray   = array();
		$certificateTermArray[] = HTMLHelper::_('select.option', '', Text::_('COM_TJLMS_COURSES_CERTIFICATE_TERM_SELECT'));
		$certificateTermArray[] = HTMLHelper::_('select.option', '0', Text::_('COM_TJLMS_FORM_OPT_COURSE_CERTIFICATE_TERM_NOCERTI'));
		$certificateTermArray[] = HTMLHelper::_('select.option', '1', Text::_('COM_TJLMS_FORM_OPT_COURSE_CERTIFICATE_TERM_COMPALL'));
		$certificateTermArray[] = HTMLHelper::_('select.option', '2', Text::_('COM_TJLMS_FORM_OPT_COURSE_CERTIFICATE_TERM_PASSALL'));

		if (!class_exists('TjlmsModelcourses'))
		{
			$path = JPATH_SITE . '/components/com_tjlms/models/courses.php';
			JLoader::register('TjlmsModelcourses', $path);
		}

		$tjlmsModelcourses = new TjlmsModelcourses;

		$nameUserNameFilter = array();
		$nameUserNameFilter[] = HTMLHelper::_('select.option', '', Text::_('COM_TJLMS_FILTER_SELECT_USER'));

		$courseCreators = $tjlmsModelcourses->getCourseCreators();

		if (!empty($courseCreators))
		{
			$nameUserNameFilter = array_merge($nameUserNameFilter, $courseCreators);
		}

		$query = $this->_db->getQuery(true);
		$query->select('DISTINCT c.tag_id, t.title');
		$query->from($this->_db->qn('#__contentitem_tag_map', 'c'));
		$query->join('LEFT', $this->_db->qn('#__tags', 't') . ' ON (' . $this->_db->qn('t.id') . ' = '
			. $this->_db->qn('c.tag_id') . ')');
		$query->where($this->_db->qn('c.type_alias') . ' = ' . $this->_db->quote("com_tjlms.course"));
		$this->_db->setQuery($query);
		$courseTags = $this->_db->loadObjectList();

		$courseTagsFilter   = array();
		$courseTagsFilter[] = HTMLHelper::_('select.option', '', '- All Tags -');

		foreach ($courseTags as $courseTag)
		{
			$courseTagsFilter[] = HTMLHelper::_('select.option', $courseTag->tag_id, $courseTag->title);
		}

		$dispFilters = array(
			array(
				'id' => array(
					'search_type' => 'text', 'type' => 'equal', 'searchin' => 'c.id'
				),
				'title' => array(
					'search_type' => 'select', 'select_options' => $courseFilter, 'type' => 'equal', 'searchin' => 'c.id'
				),
				'cat_title' => array(
					'search_type' => 'select', 'select_options' => $catFilter, 'type' => 'equal', 'searchin' => 'cat.id'
				),
				'state' => array(
					'search_type' => 'select', 'select_options' => $statusArray, 'type' => 'equal', 'searchin' => 'c.state'
				),
				'featured' => array(
					'search_type' => 'select', 'select_options' => $featuredArray, 'type' => 'equal', 'searchin' => 'c.featured'
				),
				'certificate_term' => array(
					'search_type' => 'select', 'select_options' => $certificateTermArray, 'type' => 'equal', 'searchin' => 'c.certificate_term'
				),
				'enrolledUsers' => array(
					'search_type' => 'date.range',
					'enrolledUsers_from' => array('attrib' => array('placeholder' => 'YYYY-MM-DD', 'onChange' => 'tjrContentUI.report.attachCalSubmit(this);')),
					'enrolledUsers_to' => array('attrib' => array('placeholder' => 'YYYY-MM-DD', 'onChange' => 'tjrContentUI.report.attachCalSubmit(this);'))
				),
				'completedUsers' => array(
					'search_type' => 'date.range',
					'completedUsers_from' => array('attrib' => array('placeholder' => 'YYYY-MM-DD', 'onChange' => 'tjrContentUI.report.attachCalSubmit(this);')),
					'completedUsers_to' => array('attrib' => array('placeholder' => 'YYYY-MM-DD', 'onChange' => 'tjrContentUI.report.attachCalSubmit(this);'))
				),
				'type' => array(
					'search_type' => 'select', 'select_options' => $typeArray, 'type' => 'equal', 'searchin' => 'c.type'
				),
				'access' => array(
					'search_type' => 'select', 'select_options' => $accesslevelFilter, 'type' => 'equal', 'searchin' => 'c.access'
				),
				'created_by' => array(
					'search_type' => 'select', 'select_options' => $nameUserNameFilter, 'type' => 'equal', 'searchin' => 'c.created_by'
				),
				'courseTags' => array(
					'search_type' => 'select', 'select_options' => $courseTagsFilter, 'type' => 'equal', 'searchin' => 'ctm.tag_id'
				),
				'start_date' => array(
					'search_type' => 'date.range',
					'searchin' => 'start_date',
					'start_date_from' => array('attrib' => array('placeholder' => 'YYYY-MM-DD', 'onChange' => 'tjrContentUI.report.attachCalSubmit(this);')),
					'start_date_to' => array('attrib' => array('placeholder' => 'YYYY-MM-DD', 'onChange' => 'tjrContentUI.report.attachCalSubmit(this);'))
				),
			)
		);

		$filters = $this->getState('filters');

		if (!empty($filters['courseTags']))
		{
			unset($dispFilters[0]['courseTags']['searchin']);
		}

		if (count($reportOptions) > 1)
		{
			$dispFilters[1] = array();
			$dispFilters[1]['report_filter'] = array(
				'search_type' => 'select', 'select_options' => $reportOptions
				);
		}

		JLoader::import('components.com_users.models.groups', JPATH_ADMINISTRATOR);
		$usersGroupsModel = BaseDatabaseModel::getInstance('Groups', 'UsersModel', array('ignore_request' => true));
		$userGroups = $usersGroupsModel->getItems();
		$filterUsersGroups = array();
		$filterUsersGroups[] = (object) array('value' => '', 'text' => Text::_('PLG_TJREPORTS_COURSEREPORT_SELECT_USER_GROUP'), 'disable' => '');

		foreach ($userGroups as $userGroup)
		{
			$filterUsersGroup = new stdClass;
			$filterUsersGroup->value = $userGroup->id;
			$filterUsersGroup->text = $userGroup->title;
			$filterUsersGroup->disable = '';

			$filterUsersGroups[] = $filterUsersGroup;
		}

		$dispFilters[1]['usergroup_filter'] = array(
			'search_type' => 'select', 'select_options' => $filterUsersGroups
			);

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
		$filters   = $this->getState('filters');
		$createdByClause = $myTeamClause = false;
		$hasUsers  = array();
		$user      = Factory::getUser();
		$userId    = $user->id;
		$showNameOrUsername = $this->lmsparams->get('show_user_or_username', 'name');

		if (isset($filters['report_filter']) && !empty($filters['report_filter']))
		{
			if ((int) $filters['report_filter'] === 1)
			{
				$createdByClause = true;
			}
			elseif ((int) $filters['report_filter'] === -1)
			{
				$hasUsers = TjlmsHelper::getSubusers();
				$myTeamClause = true;
			}
		}

		$colToshow = (array) $this->getState('colToshow');

		// Must have columns to get details of non linked data like completion

		// $query->select(array('c.id'));

		// Join over the user field 'created_by'
		$query->from($db->qn('#__tjlms_courses', 'c'));
		$query->join('LEFT', $db->qn('#__categories', 'cat') . ' ON (' . $db->qn('c.catid') . ' = ' . $db->qn('cat.id') . ')');

		if ($createdByClause )
		{
			$query->where('c.created_by = ' . (int) $userId);
		}

		// Filter records by students user group
		if (isset($filters['usergroup_filter']) && $filters['usergroup_filter'] != '')
		{
			$query->join('LEFT', $db->qn('#__tjlms_enrolled_users', 'eut') . ' ON (' . $db->qn('eut.course_id') . ' = ' . $db->qn('c.id') . ')');
			$query->join('LEFT', $db->qn('#__users', 'ut') . ' ON (' . $db->qn('eut.user_id') . ' = ' . $db->qn('ut.id') . ')');
			$query->join('LEFT', $db->qn('#__user_usergroup_map', 'ugm') . ' ON (' . $db->qn('ugm.user_id') . ' = ' . $db->qn('ut.id') . ')');
			$query->where($db->qn('ugm.group_id') . '=' . $filters['usergroup_filter']);
		}

		if (in_array('access', $colToshow))
		{
			$query->join('LEFT', $db->qn('#__viewlevels', 'vl') . ' ON (' . $db->qn('vl.id') . ' = ' . $db->qn('c.access') . ')');
		}

		if (in_array('type', $colToshow))
		{
			$query->select('IF(c.type=1,"' . Text::_('COM_TJLMS_PAID') . '","' . Text::_('COM_TJLMS_FREE') . '") AS type');
		}

		if (in_array('created_by', $colToshow))
		{
			$query->select($db->qn('u.block'));
			$query->select($db->qn('u.' . $showNameOrUsername, 'uname'));
			$query->join('LEFT', $db->qn('#__users', 'u') . ' ON (' . $db->qn('c.created_by') . ' = ' . $db->qn('u.id') . ')');
		}

		if (in_array('courseTags', $colToshow))
		{
			$subQuery = $db->getQuery(true);
			$subQuery->select('ctm.tag_id');
			$subQuery->from($db->qn('#__contentitem_tag_map', 'ctm'));
			$subQuery->where($db->qn('ctm.content_item_id') . ' = ' . $db->qn('c.id'));
			$subQuery->where($db->qn('ctm.type_alias') . ' = ' . $db->quote("com_tjlms.course"));
			$query->select('(SELECT GROUP_CONCAT(t.title SEPARATOR ", ") from  #__tags t where t.id IN(' . $subQuery . ')) as courseTags');

			if (isset($filters['courseTags']) && !empty($filters['courseTags']))
			{
				$subQuery = $db->getQuery(true);
				$subQuery->select('ctm.content_item_id');
				$subQuery->from($db->qn('#__contentitem_tag_map', 'ctm'));
				$subQuery->where($db->qn('ctm.type_alias') . ' = ' . $db->quote("com_tjlms.course"));
				$subQuery->where($db->qn('ctm.tag_id') . ' = ' . (int) $filters['courseTags']);
				$query->where('c.id IN(' . $subQuery . ')');
			}
		}

		if (in_array('lessonsCount', $colToshow))
		{
			$subQuery = $db->getQuery(true);
			$subQuery->select('COUNT(l.id) as lessons_cnt');
			$subQuery->from($db->qn('#__tjlms_lessons', 'l'));
			$subQuery->where($db->qn('l.state') . " = " . $db->quote(1));
			$subQuery->where('l.format<>""');
			$subQuery->where($db->qn('l.media_id') . " >  0");
			$subQuery->where($db->qn('l.media_id') . " <>  ''");
			$subQuery->where($db->qn('l.course_id') . " = " . $db->qn('c.id'));
			$query->select('(' . $subQuery . ') as lessonsCount');
		}

		if (in_array('enrolledUsers', $colToshow))
		{
			$subQuery = $db->getQuery(true);
			$subQuery->select('COUNT(IF(eu.state="1",1, NULL))');
			$subQuery->from($db->qn('#__tjlms_enrolled_users', 'eu'));
			$subQuery->where($db->qn('eu.course_id') . " = " . $db->qn('c.id'));
			$subQuery->join('INNER', $db->qn('#__users', 'u') . ' ON (' . $db->qn('eu.user_id') . ' = ' . $db->qn('u.id') . ')');

			if ($myTeamClause)
			{
				if ($hasUsers)
				{
					$subQuery->where($db->qn('eu.user_id') . ' IN(' . implode(',', $hasUsers) . ')');
				}
				else
				{
					$subQuery->where($db->qn('eu.user_id') . '=0');
				}
			}

			if (isset($filters['enrolledUsers_from']) && $filters['enrolledUsers_from'] != '')
			{
				$fromDate = Factory::getDate($filters['enrolledUsers_from']);
				$subQuery->where($db->qn('eu.enrolled_on_time') . ' >= ' . $db->quote($fromDate->toSql()));
			}

			if (isset($filters['enrolledUsers_to']) && $filters['enrolledUsers_to'] != '')
			{
				$toDate = Factory::getDate($filters['enrolledUsers_to']);
				$subQuery->where($db->qn('eu.enrolled_on_time') . ' <= ' . $db->quote($toDate->toSql()));
			}

			$query->select('(' . $subQuery . ') as enrolledUsers');
		}

		if (in_array('pendingEnrollment', $colToshow))
		{
			$subQuery = $db->getQuery(true);
			$subQuery->select('COUNT(IF(eu.state="0",1, NULL))');
			$subQuery->from($db->qn('#__tjlms_enrolled_users', 'eu'));
			$subQuery->where($db->qn('eu.course_id') . " = " . $db->qn('c.id'));

			if ($myTeamClause)
			{
				if ($hasUsers)
				{
					$subQuery->where('eu.user_id IN(' . implode(',', $hasUsers) . ')');
				}
				else
				{
					$subQuery->where('eu.user_id=0');
				}
			}

			$query->select('(' . $subQuery . ') as pendingEnrollment');
		}

		if (in_array('completedUsers', $colToshow))
		{
			$subQuery = $db->getQuery(true);
			$subQuery->select('COUNT(ct.id)');
			$subQuery->from($db->qn('#__tjlms_course_track') . ' as ct');
			$subQuery->where($db->qn('ct.status') . " = 'C'");
			$subQuery->where($db->qn('ct.course_id') . " = " . $db->qn('c.id'));

			if ($myTeamClause)
			{
				if ($hasUsers)
				{
					$subQuery->where('ct.user_id IN(' . implode(',', $hasUsers) . ')');
				}
				else
				{
					$subQuery->where('ct.user_id=0');
				}
			}

			if (isset($filters['completedUsers_from']) && $filters['completedUsers_from'] != '')
			{
				$fromDate = Factory::getDate($filters['completedUsers_from']);
				$subQuery->where($db->qn('ct.timeend') . ' >= ' . $db->quote($fromDate->toSql()));
			}

			if (isset($filters['completedUsers_to']) && $filters['completedUsers_to'] != '')
			{
				$toDate = Factory::getDate($filters['completedUsers_to']);
				$subQuery->where($db->qn('ct.timeend') . ' <= ' . $db->quote($toDate->toSql()));
			}

			$query->select('(' . $subQuery . ') as completedUsers');
		}

		if (array_intersect(array('likeCount', 'dislikeCount'), $colToshow))
		{
			$query->select(array('like_cnt as likeCount', 'dislike_cnt as dislikeCount'));
			$query->join('LEFT', $db->qn('#__jlike_content', 'jc')
				. ' ON (jc.element_id = c.id) AND ' . $db->qn('jc.element') . ' = "com_tjlms.course" ');
		}

		if (in_array('commentsCount', $colToshow))
		{
			$subQuery = $db->getQuery(true);
			$subQuery->select('COUNT(jac.id)');
			$subQuery->from($db->qn('#__jlike_content') . ' as jcc');
			$subQuery->join('LEFT', '#__jlike_annotations AS jac ON jcc.id = jac.content_id');
			$subQuery->where(array("jcc.element = 'com_tjlms.course'", 'jcc.element_id = c.id', 'jac.note=0'));

			$query->select('(' . $subQuery . ') as commentsCount');
		}

		if (in_array('recommendCount', $colToshow))
		{
			$subQuery = $db->getQuery(true);
			$subQuery->select('COUNT(IF(rjt.type="reco",1, NULL))');
			$subQuery->from($db->qn('#__jlike_todos') . ' as rjt');
			$subQuery->join('LEFT', '#__jlike_content AS rjc ON rjc.id = rjt.content_id');
			$subQuery->where(array("rjc.element = 'com_tjlms.course'", 'rjc.element_id = c.id'));

			$query->select('(' . $subQuery . ') as recommendCount');
		}

		if (in_array('assignCount', $colToshow))
		{
			$subQuery = $db->getQuery(true);
			$subQuery->select('COUNT(IF(ajt.type="assign",1, NULL))');
			$subQuery->from($db->qn('#__jlike_todos') . ' as ajt');
			$subQuery->join('LEFT', '#__jlike_content AS ajc ON ajc.id = ajt.content_id');
			$subQuery->where(array("ajc.element = 'com_tjlms.course'", 'ajc.element_id = c.id'));

			if ($myTeamClause)
			{
				if ($hasUsers)
				{
					$subQuery->where('ajt.assigned_to IN(' . implode(',', $hasUsers) . ')');
				}
				else
				{
					$subQuery->where('ajt.assigned_to=0');
				}
			}

			$query->select('(' . $subQuery . ') as assignCount');
		}

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

		if (empty($items))
		{
			return;
		}

		foreach ($items as &$item)
		{
			if (in_array('likeCount', $colToshow) && empty($item['likeCount']))
			{
				$item['likeCount'] = 0;
			}

			if (in_array('dislikeCount', $colToshow) && empty($item['dislikeCount']))
			{
				$item['dislikeCount'] = 0;
			}

			if (in_array('created_by', $colToshow))
			{
				$item['created_by'] = $item['uname'];

				if (empty($item['uname']) || ($item['block'] == 1))
				{
					$item['created_by'] = Text::_('COM_TJLMS_BLOCKED_USER');
				}
			}

			if (in_array('state', $colToshow))
			{
				if ($item['state'] == 0)
				{
					$item['state'] = Text::_('COM_TJLMS_COURSES_STATUS_UNPUBLISHED');
				}
				elseif ($item['state'] == 1)
				{
					$item['state'] = Text::_('COM_TJLMS_COURSES_STATUS_PUBLISHED');
				}
				elseif ($item['state'] == -2)
				{
					$item['state'] = Text::_('COM_TJLMS_COURSES_STATUS_TRASHED');
				}
			}

			if (in_array('featured', $colToshow))
			{
				if ($item['featured'] == 0)
				{
					$item['featured'] = Text::_('COM_TJLMS_COURSES_FEATURED_NO');
				}
				elseif ($item['featured'] == 1)
				{
					$item['featured'] = Text::_('COM_TJLMS_COURSES_FEATURED_YES');
				}
			}

			if (in_array('certificate_term', $colToshow))
			{
				if ($item['certificate_term'] == 0)
				{
					$item['certificate_term'] = Text::_('COM_TJLMS_FORM_OPT_COURSE_CERTIFICATE_TERM_NOCERTI');
				}
				elseif ($item['certificate_term'] == 1)
				{
					$item['certificate_term'] = Text::_('COM_TJLMS_FORM_OPT_COURSE_CERTIFICATE_TERM_COMPALL');
				}
				elseif ($item['certificate_term'] == 2)
				{
					$item['certificate_term'] = Text::_('COM_TJLMS_FORM_OPT_COURSE_CERTIFICATE_TERM_PASSALL');
				}
			}

			if (in_array('start_date', $colToshow))
			{
				if ($item['start_date'] == '0000-00-00 00:00:00')
				{
					$item['start_date'] = '-';
				}
				else
				{
					$tjCommon       = new TechjoomlaCommon;
					$dateFormatShow = $this->lmsparams->get('date_format_show', 'Y-m-d H:i:s');

					$item['start_date'] = $tjCommon->getDateInLocal($item['start_date'], 0, $dateFormatShow);
				}
			}
		}

		$items = $this->sortCustomColumns($items);

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
			array('name' => 'id', 'label' => Text::_('COM_TJLMS_COURSE_ID'),
				'dataType' => 'NUMBER', 'semantics' => array('conceptType' => 'DIMENSION')),
			array('name' => 'title', 'label' => Text::_('COM_TJLMS_COURSE_NAME'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION')),
			array('name' => 'created_by', 'label' => Text::_('JGLOBAL_FIELD_CREATED_BY_LABEL'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION')),
			array('name' => 'type', 'label' => Text::_('COM_TJLMS_COURSE_TYPE'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION')),
			array('name' => 'access', 'label' => Text::_('COM_TJLMS_ACL_GROUP'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION')),
			array('name' => 'cat_title', 'label' => Text::_('COM_TJLMS_COURSES_CAT_ID'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION')),
			array('name' => 'lessonsCount', 'label' => Text::_('COM_TJLMS_LESSONS_CNT'),
				'dataType' => 'NUMBER', 'semantics' => array('conceptType' => 'METRIC')),
			array('name' => 'enrolledUsers', 'label' => Text::_('COM_TJLMS_ENROLLED_USERS_CNT'),
				'dataType' => 'NUMBER', 'semantics' => array('conceptType' => 'METRIC')),
			array('name' => 'pendingEnrollment', 'label' => Text::_('COM_TJLMS_PENDING_ENROLLED_USERS_CNT'),
				'dataType' => 'NUMBER', 'semantics' => array('conceptType' => 'METRIC')),
			array('name' => 'completedUsers', 'label' => Text::_('COM_TJLMS_COMPLETED_USERS_CNT'),
				'dataType' => 'NUMBER', 'semantics' => array('conceptType' => 'METRIC')),
			array('name' => 'likeCount', 'label' => Text::_('COM_TJLMS_LIKES_CNT'),
				'dataType' => 'NUMBER', 'semantics' => array('conceptType' => 'METRIC')),
			array('name' => 'dislikeCount', 'label' => Text::_('COM_TJLMS_DISLIKES_CNT'),
				'dataType' => 'NUMBER', 'semantics' => array('conceptType' => 'METRIC')),
			array('name' => 'commentsCount', 'label' => Text::_('COM_TJLMS_COMMENTS_CNT'),
				'dataType' => 'NUMBER', 'semantics' => array('conceptType' => 'METRIC')),
			array('name' => 'assignCount', 'label' => Text::_('COM_TJLMS_ASSIGN_CNT'),
				'dataType' => 'NUMBER', 'semantics' => array('conceptType' => 'METRIC')),
			array('name' => 'recommendCount', 'label' => Text::_('COM_TJLMS_RECO_CNT'),
				'dataType' => 'NUMBER', 'semantics' => array('conceptType' => 'METRIC')),
			array('name' => 'courseTags', 'label' => Text::_('COM_TJLMS_RECO_CNT'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION')),
		);
	}
}
