<?php
/**
 * @package     Tjlms.Plugin
 * @subpackage  Tjlms,TJReport,StudentCourse
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
 * Student course report plugin of TJReport
 *
 * @since  1.0.0
 */
class TjreportsModelStudentcoursereport extends TjreportsModelReports
{
	protected $default_order       = 'id';

	protected $default_order_dir   = 'ASC';

	public $showSearchResetButton  = false;

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
			'course_id'        => array('title' => 'COM_TJLMS_COURSE_ID'),
			'title'            => array('table_column' => 'c.title', 'title' => 'COM_TJLMS_COURSE_NAME'),
			'cat_title'        => array('table_column' => 'cat.title', 'title' => 'COM_TJLMS_COURSES_CAT_ID'),
			'user_id'          => array('title' => 'COM_TJLMS_ENROLMENT_USERID'),
			'name'             => array('table_column' => 'u.name', 'title' => 'COM_TJLMS_ENROLMENT_USER_NAME'),
			'username'         => array('table_column' => 'u.username', 'title' => 'COM_TJLMS_REPORT_USERUSERNAME'),
			'email'            => array('table_column' => 'u.email', 'title' => 'COM_TJLMS_ENROLMENT_USER_EMAIL_ADDRESS'),
			'block'                => array('table_column' => '', 'title' => 'COM_TJLMS_ENROLMENT_USER_BLOCKED'),
			'usergroup'        => array('title' => 'COM_TJLMS_REPORT_USERGROUP', 'disable_sorting' => true),
			'status'           => array('table_column' => '', 'title' => 'COM_TJLMS_ATTEMPTREPORT_STATUS'),
			'certificate_term' => array('table_column' => 'c.certificate_term', 'title' => 'COM_TJLMS_CERTIFICATE_TERM'),
			'enrolled_on_time' => array('table_column' => 'eu.enrolled_on_time', 'title' => 'COM_TJLMS_USER_ENROLLED_ON'),
			'timestart'        => array('table_column' => 'ct.timestart', 'title' => 'COM_TJLMS_USER_START_DATE'),
			'timeend'          => array('table_column' => 'ct.timeend', 'title' => 'COM_TJLMS_USER_COMPLETED_DATE'),
			'acl_title'        => array('table_column' => 'vl.title', 'title' => 'COM_TJLMS_ACCESS_LEVEL'),
			'completion'       => array('title' => 'COM_TJLMS_COMPLETION'),
			'totaltimespent'   => array('title' => 'COM_TJLMS_REPORT_TIMESPENT'),
			'lastVisitDate'    => array('table_column' => 'u.lastvisitDate', 'title' => 'COM_TJLMS_USER_LAST_LOGIN_DATE'),
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
		$detail = array('client' => 'com_tjlms', 'title' => Text::_('PLG_TJREPORTS_STUDENTCOURSEREPORT_TITLE'));

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
		$catFilter 			= $TjlmsModelReports->getCatFilter();
		$userFilter 		= $TjlmsModelReports->getUserFilter($myTeam);
		$courseFilter 		= $TjlmsModelReports->getCourseFilter($created_by);

		$statusArray   = array();
		$statusArray[] = HTMLHelper::_('select.option', '', Text::_('COM_TJLMS_FILTER_SELECT_STATUS'));
		$statusArray[] = HTMLHelper::_('select.option', 'I', Text::_('COM_TJLMS_LESSONSTATUS_INCOMPLETE'));
		$statusArray[] = HTMLHelper::_('select.option', 'C', Text::_('COM_TJLMS_FILTER_STATUS_COMPLETED'));

		$activeArray   = array();
		$activeArray[] = HTMLHelper::_('select.option', '', Text::_('COM_TJLMS_COURSES_TYPE_FILTER'));
		$activeArray[] = HTMLHelper::_('select.option', '0', Text::_('COM_TJLMS_YES'));
		$activeArray[] = HTMLHelper::_('select.option', '1', Text::_('COM_TJLMS_NO'));

		$dispFilters = array(
			array(
				'course_id' => array(
					'search_type' => 'text', 'type' => 'equal', 'searchin' => 'c.id'
				),
				'title' => array(
					'search_type' => 'select', 'select_options' => $courseFilter, 'type' => 'equal', 'searchin' => 'c.id'
				),
				'cat_title' => array(
					'search_type' => 'select', 'select_options' => $catFilter, 'type' => 'equal', 'searchin' => 'c.catid'
				),
				'user_id' => array(
					'search_type' => 'text', 'type' => 'equal', 'searchin' => 'eu.user_id'
				),
				'name' => array(
					'search_type' => 'text', 'searchin' => 'u.name'
				),
				'username' => array(
					'search_type' => 'select', 'select_options' => $userFilter, 'type' => 'equal', 'searchin' => 'eu.user_id'
				),
				'email'       => array(
					'search_type' => 'text', 'searchin' => 'u.email'
					),
				'block'       => array(
					'search_type' => 'select', 'select_options' => $activeArray, 'type' => 'equal', 'searchin' => 'u.block'
				),
				'usergroup' => array(
					'search_type' => 'select', 'select_options' => $this->getUserGroupFilter()
				),
				'status' => array(
					'search_type' => 'select', 'select_options' => $statusArray
				),
				'enrolled_on_time' => array(
					'search_type' => 'date.range',
					'searchin'    => 'enrolled_on_time',
					'enrolled_on_time_from' => array('attrib' => array('placeholder' => 'YYYY-MM-DD', 'onChange' => 'tjrContentUI.report.attachCalSubmit(this);')),
					'enrolled_on_time_to'   => array('attrib' => array('placeholder' => 'YYYY-MM-DD', 'onChange' => 'tjrContentUI.report.attachCalSubmit(this);'))
				),
				'timestart' => array(
					'search_type'    => 'date.range',
					'searchin'       => 'timestart',
					'timestart_from' => array('attrib' => array('placeholder' => 'YYYY-MM-DD', 'onChange' => 'tjrContentUI.report.attachCalSubmit(this);')),
					'timestart_to'   => array('attrib' => array('placeholder' => 'YYYY-MM-DD', 'onChange' => 'tjrContentUI.report.attachCalSubmit(this);'))
				),
				'timeend' => array(
					'search_type'  => 'date.range',
					'searchin'     => 'timeend',
					'timeend_from' => array('attrib' => array('placeholder' => 'YYYY-MM-DD', 'onChange' => 'tjrContentUI.report.attachCalSubmit(this);')),
					'timeend_to'   => array('attrib' => array('placeholder' => 'YYYY-MM-DD', 'onChange' => 'tjrContentUI.report.attachCalSubmit(this);'))
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
		$user      = Factory::getUser();
		$userId    = $user->id;

		$query->select(array('c.id as course_id', 'eu.user_id as user_id', 'eu.id'));
		$query->from($db->quoteName('#__tjlms_enrolled_users', 'eu'));

		$query->join('INNER', $db->quoteName('#__tjlms_courses', 'c') .
		' ON (' . $db->quoteName('c.id') . ' = ' . $db->quoteName('eu.course_id') . ')');
		$query->where('eu.state=1');
		$query->where('c.state=1');

		if (array_intersect(array('username', 'email', 'name', 'lastVisitDate'), $colToshow))
		{
			$query->join('INNER', $db->quoteName('#__users', 'u') . 'ON (' . $db->quoteName('u.id') . ' = ' . $db->quoteName('eu.user_id') . ')');
		}

		if (in_array('block', $colToshow))
		{
			$query->select('IF(u.block=1,"' . Text::_('JNO') . '","' . Text::_('JYES') . '") AS block');
		}

		if (in_array('cat_title', $colToshow))
		{
			$query->join('LEFT', '#__categories AS cat ON c.catid = cat.id');
		}

		if (in_array('status', $colToshow))
		{
			$query->select(
							'IF(ct.status="c","' . Text::_('COM_TJLMS_FILTER_STATUS_COMPLETED')
							. '","' . Text::_('COM_TJLMS_LESSONSTATUS_INCOMPLETE') . '") AS status'
						);
		}

		if (array_intersect(array('status', 'timestart', 'timeend'), $colToshow))
		{
			$query->join('LEFT', '#__tjlms_course_track AS ct ON (ct.course_id = c.id AND ct.user_id = eu.user_id)');
		}

		if (in_array('acl_title', $colToshow))
		{
			$query->innerjoin($db->quoteName('#__viewlevels', 'vl') . ' ON ' . $db->quoteName('vl.id') . " = " . $db->quoteName('c.access'));
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

		$createdByClause = $myTeamClause = false;
		$reportId        = $this->getDefaultReport($this->name);
		$viewAll         = $this->checkpermissions($reportId);

		if ((int) $filters['report_filter'] === 1)
		{
			$createdByClause = true;
		}
		elseif ((int) $filters['report_filter'] === -1)
		{
			$hasUsers = TjlmsHelper::getSubusers();
			$myTeamClause = true;
		}

		if ($createdByClause)
		{
			$query->where('c.created_by = ' . (int) $userId);
		}
		elseif ($myTeamClause && $hasUsers)
		{
			$query->where('eu.user_id IN(' . implode(',', $hasUsers) . ')');
		}
		elseif (!$viewAll)
		{
			$query->where('eu.user_id=0');
		}

		if (isset($filters['status']) && !empty($filters['status']))
		{
			if ($filters['status'] == "C")
			{
				$query->where('ct.status="C"');
			}
			else
			{
				$query->where('ct.status!="C"');
			}
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

		if (empty($items))
		{
			return;
		}

		jimport('techjoomla.common');
		JLoader::import('components.com_tjlms.helpers.tracking', JPATH_SITE);
		$tjCommon 		= new TechjoomlaCommon;
		$trackingHelper = new ComtjlmstrackingHelper;

		$db        = $this->_db;
		$colToshow = $this->getState('colToshow');

		$lmsparams      = ComponentHelper::getParams('com_tjlms');
		$dateFormatShow = $lmsparams->get('date_format_show', 'Y-m-d H:i:s');

		JLoader::register('FieldsHelper', JPATH_ADMINISTRATOR . '/components/com_fields/helpers/fields.php');

		foreach ($items as $ind => &$item)
		{
			$course_id = $item['course_id'];
			$user_id   = $item['user_id'];

			if (empty($item['lastVisitDate']) || $item['lastVisitDate'] == '0000-00-00 00:00:00')
			{
				$item['lastVisitDate'] = ' - ';
			}
			else
			{
				$item['lastVisitDate'] = $tjCommon->getDateInLocal($item['lastVisitDate'], 0, $dateFormatShow);
			}

			if (in_array('enrolled_on_time', $colToshow))
			{
				$item['enrolled_on_time'] = $tjCommon->getDateInLocal($item['enrolled_on_time'], 0, $dateFormatShow);
			}

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

			if (in_array('certificate_term', $colToshow))
			{
				$cer_term = Text::_("COM_TJREPORTS_FORM_OPT_COURSE_CERTIFICATE_TERM_NOCERTI");

				if ($item['certificate_term'] == "1")
				{
					$cer_term = Text::_("COM_TJREPORTS_FORM_OPT_COURSE_CERTIFICATE_TERM_COMPALL");
				}
				elseif ($item['certificate_term'] == "2")
				{
					$cer_term = Text::_("COM_TJREPORTS_FORM_OPT_COURSE_CERTIFICATE_TERM_PASSALL");
				}

				$item['certificate_term'] = $cer_term;
			}

			if (in_array('completion', $colToshow))
			{
				$progress = $trackingHelper->getCourseTrackEntry($course_id, $user_id);
				$item['completion'] = floor($progress['completionPercent']);
			}

			if (in_array('totaltimespent', $colToshow))
			{
				// Get total time spent
				$query = $db->getQuery(true);
				$query->select('SEC_TO_TIME(SUM(TIME_TO_SEC(time_spent))) as totalTimeSpent');
				$query->from($db->quoteName('#__tjlms_lesson_track', 'lt'));
				$query->join('INNER', $db->quoteName('#__tjlms_lessons', 'l') . 'ON (' . $db->quoteName('lt.lesson_id') . ' = ' . $db->quoteName('l.id') . ')');
				$query->where($db->quoteName('lt.user_id') . " = " . $db->quote($user_id));
				$query->where($db->quoteName('l.course_id') . " = " . $db->quote($course_id));
				$db->setQuery($query);
				$totaltimespent = $db->loadResult();

				$item['totaltimespent'] = '-';

				if (!empty($totaltimespent) && $totaltimespent != '00:00:00')
				{
					$item['totaltimespent'] = $totaltimespent;
				}
			}
			$enrolmentFields = FieldsHelper::getFields('com_tjlms.manageenrollment', $item, true);

			if (!empty($enrolmentFields))
			{
				foreach ($enrolmentFields as $field)
				{
					$fieldName = "+" . $field->title;
					$colToshow[$fieldName] = $fieldName;
					$this->columns[$fieldName] = array('title' => ucwords($fieldName));

					$items[$ind][$fieldName] = $field->value;
				}

				$this->setState('colToshow', $colToshow);
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
			array('name' => 'course_id', 'label' => Text::_('COM_TJLMS_COURSE_ID'),
				'dataType' => 'NUMBER', 'semantics' => array('conceptType' => 'DIMENSION')),
			array('name' => 'title', 'label' => Text::_('COM_TJLMS_COURSE_NAME'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION')),
			array('name' => 'cat_title', 'label' => Text::_('COM_TJLMS_COURSE_CAT'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION')),
			array('name' => 'user_id', 'label' => Text::_('COM_TJLMS_ENROLMENT_USERID'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION')),
			array('name' => 'name', 'label' => Text::_('COM_TJLMS_ENROLMENT_USER_NAME'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION')),
			array('name' => 'username', 'label' => Text::_('COM_TJLMS_REPORT_USERUSERNAME'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION')),
			array('name' => 'email', 'label' => Text::_('COM_TJLMS_REPORT_USEREMAIL'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION')),
			array('name' => 'usergroup', 'label' => Text::_('COM_TJLMS_REPORT_USERGROUP'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION')),
			array('name' => 'timestart', 'label' => Text::_('COM_TJLMS_USER_START_DATE'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION', 'semanticType' => 'YEAR_MONTH_DAY')),
			array('name' => 'timeend', 'label' => Text::_('COM_TJLMS_COURSE_COMPLETED_DATE'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION', 'semanticType' => 'YEAR_MONTH_DAY')),
			array('name' => 'status', 'label' => Text::_('COM_TJLMS_ATTEMPTREPORT_STATUS'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION')),
			array('name' => 'certificate_term', 'label' => Text::_('COM_TJLMS_CERTIFICATE_TERM'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION')),
			array('name' => 'enrolled_on_time', 'label' => Text::_('COM_TJLMS_USER_ENROLLED_ON'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION', 'semanticType' => 'YEAR_MONTH_DAY')),
			array('name' => 'completion', 'label' => Text::_('COM_TJLMS_COMPLETION'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'METRIC')),
			array('name' => 'totaltimespent', 'label' => Text::_('COM_TJLMS_REPORT_TIMESPENT'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION')),
			array('name' => 'lastVisitDate', 'label' => Text::_('COM_TJLMS_USER_LAST_LOGIN_DATE'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION', 'semanticType' => 'YEAR_MONTH_DAY')),
			array('name' => 'acl_title', 'label' => Text::_('COM_TJLMS_ACCESS_LEVEL'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION')),
		);
	}
}
