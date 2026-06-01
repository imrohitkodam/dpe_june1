<?php
/**
 * @package     Tjlms.Plugin
 * @subpackage  Tjlms,TJReport,attempreport
 *
 * @copyright   Copyright (C) 2005 - 2020 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Language\Text;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\HTML\HTMLHelper;

JLoader::import('com_tjreports.models.reports', JPATH_SITE . '/components');

/**
 * Attempt report plugin of TJReport
 *
 * @since  1.0.0
 */
class TjreportsModelAttemptreport extends TjreportsModelReports
{
	protected $default_order = 'name';

	protected $default_order_dir = 'ASC';

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
			'attempt'          => array('table_column' => 'lt.attempt', 'title' => 'COM_TJLMS_TITLE_ATTEMPTS'),
			'name'             => array('table_column' => 'l.title', 'title' => 'COM_TJLMS_ATTEMPTREPORT_NAME'),
			'lessonFormat'      => array('table_column' => 'l.format', 'title' => 'COM_TJLMS_LESSONS_FORMAT', 'not_show_hide' => false),
			'lesson_id'        => array('table_column' => 'lt.lesson_id', 'title' => 'COM_TJLMS_ATTEMPTREPORT_LESSONID'),
			'username'         => array('table_column' => 'u.username', 'title' => 'COM_TJLMS_REPORT_USERUSERNAME', 'isPiiColumn' => true),
			'user_id'          => array('table_column' => 'lt.user_id', 'title' => 'COM_TJLMS_REPORT_USERUSERID'),
			'usergroup'        => array('title' => 'COM_TJLMS_REPORT_USERGROUP', 'disable_sorting' => true),
			'time_spent'       => array('table_column' => 'lt.time_spent', 'title' => 'COM_TJLMS_REPORT_LESSON_TIMESPENT'),
			'lesson_status'    => array('table_column' => 'lt.lesson_status', 'title' => 'COM_TJLMS_REPORT_LESSON_STATUS'),
			'score'            => array('table_column' => 'lt.score', 'title' => 'COM_TJLMS_REPORT_LESSON_SCORE'),
			'timestart'        => array('title' => 'COM_TJLMS_LESSONREPORT_STARTDATE'),
			'timeend'          => array('title' => 'COM_TJLMS_LESSONREPORT_ENDDATE'),
			'last_accessed_on' => array('table_column' => 'lt.last_accessed_on', 'title' => 'COM_TJLMS_ATTEMPTREPORT_LASTACCESS'),
			'attempt_state' => array('title' => 'COM_TJLMS_ATTEMPTREPORT_STATE')
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
		$detail = array('client' => 'com_tjlms', 'title' => Text::_('PLG_TJREPORTS_ATTEMPTREPORT_TITLE'));

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
		$userFilter 		= $TjlmsModelReports->getUserFilter($myTeam);

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

		$lessonFormatFilters = array();
		$lessonFormatFilters[] = HTMLHelper::_('select.option', '', Text::_('COM_TJLMS_FILTER_SELECT_LESSON_FORMAT'));

		$lesson_formats_array = array('scorm','htmlzips','tincanlrs','video',
		'document','textmedia','externaltool','event', 'survey','form', 'quiz','exercise','feedback');

		foreach ($lesson_formats_array as $formatName)
		{
			$langVar = "COM_TJLMS_" . strtoupper($formatName) . "_LESSON";
			$lessonFormatFilters[] = JHTML::_('select.option', $formatName, Text::_($langVar));
		}

		$dispFilters = array(
			array(
				'attempt' => array(
					'search_type' => 'text', 'type' => 'equal', 'searchin' => 'lt.attempt'),
				'name' => array(
					'search_type' => 'select', 'select_options' => $lessonFilter, 'type' => 'equal', 'searchin' => 'lt.lesson_id'
				),
				'lesson_id' => array(
					'search_type' => 'text', 'type' => 'equal', 'searchin' => 'lt.lesson_id'
				),
				'username' => array(
					'search_type' => 'select', 'select_options' => $userFilter, 'type' => 'equal', 'searchin' => 'lt.user_id'
				),
				'user_id' => array(
					'search_type' => 'text', 'type' => 'equal', 'searchin' => 'lt.user_id'
				),
				'lesson_status' => array(
					'search_type' => 'select', 'select_options' => $statusArray, 'type' => 'equal', 'searchin' => 'lt.lesson_status'
				),
				'usergroup' => array(
					'search_type' => 'select', 'select_options' => $this->getUserGroupFilter()
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
				'attempt_state' => array(
					'search_type' => 'select', 'select_options' => $attemptStateArray, 'type' => 'equal'
				),
			),
			array(
				'last_accessed_on' => array(
					'search_type' => 'date.range',
					'searchin' => 'last_accessed_on',
					'last_accessed_on_from' => array('attrib' => array('placeholder' => 'FROM (YYYY-MM-DD)')),
					'last_accessed_on_to' => array('attrib' => array('placeholder' => 'TO (YYYY-MM-DD)')),
				),
				'lessonFormat' => array(
					'search_type' => 'select', 'select_options' => $lessonFormatFilters, 'type' => 'equal', 'searchin' => 'l.format'
				)
			)
		);

		if (count($reportOptions) > 1)
		{
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
		$colToshow = (array) $this->getState('colToshow');
		$filters = $this->getState('filters');
		$user     = Factory::getUser();
		$userId   = $user->id;

		$query->select(array('lt.user_id', 'lt.timestart', 'lt.timeend'));

		// Must have columns to get details of non linked data like completion
		// $query->select(array('user_id'));
		if ($filters['attempt_state'] != '' && (int) $filters['attempt_state'] === 0)
		{
			$query->select('lt.lesson_track_id');
			$query->from($db->quoteName('#__tjlms_lesson_track_archive', 'lt'));
		}
		else
		{
			$query->from($db->quoteName('#__tjlms_lesson_track', 'lt'));
		}

		$query->join('INNER', $db->quoteName('#__tjlms_lessons', 'l') . ' ON (' . $db->quoteName('lt.lesson_id') . ' = ' . $db->quoteName('l.id') . ')');
		$query->join('INNER', $db->quoteName('#__users', 'u') . ' ON (' . $db->quoteName('lt.user_id') . ' = ' . $db->quoteName('u.id') . ')');

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

		$reportId = $this->getDefaultReport($this->name);
		$viewAll = $this->checkpermissions($reportId);

		if ((int) $filters['report_filter'] === 1)
		{
			$query->where('l.created_by = ' . (int) $userId);
		}
		elseif ((int) $filters['report_filter'] === -1)
		{
			$hasUsers = TjlmsHelper::getSubusers();
			$query->where('lt.user_id IN(' . implode(',', $hasUsers) . ')');
		}
		elseif(!$viewAll)
		{
			$query->where('lt.user_id=0');
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
		$items = parent::getItems();

		$lmsparams = ComponentHelper::getParams('com_tjlms');
		$dateFormatShow = $lmsparams->get('date_format_show', 'Y-m-d H:i:s');

		jimport('techjoomla.common');
		$tjCommon = new TechjoomlaCommon;

		$colToshow	= $this->getState('colToshow');

		if (empty($items))
		{
			return;
		}

		foreach ($items as &$item)
		{
			if (empty($item['last_accessed_on']) || $item['last_accessed_on'] == '0000-00-00 00:00:00')
			{
				$item['last_accessed_on'] = ' - ';
			}
			else
			{
				$item['last_accessed_on'] = $tjCommon->getDateInLocal($item['last_accessed_on'], 0, $dateFormatShow);
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

			if (in_array('attempt_state', $colToshow))
			{
				$item['attempt_state'] = ($item['lesson_track_id']) ?
					Text::_('COM_TJLMS_ATTEMPTREPORT_STATE_EXPIRED') : Text::_('COM_TJLMS_ATTEMPTREPORT_STATE_ACTIVE');
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
	 * @since   1.3.30
	 */
	public function getGDSFields()
	{
		return array(
			array('name' => 'attempt', 'label' => Text::_('COM_TJLMS_TITLE_ATTEMPTS'),
				'dataType' => 'NUMBER', 'semantics' => array('conceptType' => 'DIMENSION')),
			array('name' => 'name', 'label' => Text::_('COM_TJLMS_ATTEMPTREPORT_NAME'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION')),
			array('name' => 'lessonFormat', 'label' => Text::_('COM_TJLMS_LESSONS_FORMAT'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION')),
			array('name' => 'lesson_id', 'label' => Text::_('COM_TJLMS_ATTEMPTREPORT_LESSONID'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION')),
			array('name' => 'username', 'label' => Text::_('COM_TJLMS_REPORT_USERUSERNAME'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION')),
			array('name' => 'user_id', 'label' => Text::_('COM_TJLMS_REPORT_USERUSERID'),
				'dataType' => 'NUMBER', 'semantics' => array('conceptType' => 'DIMENSION')),
			array('name' => 'usergroup', 'label' => Text::_('COM_TJLMS_REPORT_USERGROUP'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION')),
			array('name' => 'time_spent', 'label' => Text::_('COM_TJLMS_REPORT_LESSON_TIMESPENT'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION')),
			array('name' => 'lesson_status', 'label' => Text::_('COM_TJLMS_REPORT_LESSON_STATUS'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION')),
			array('name' => 'score', 'label' => Text::_('COM_TJLMS_REPORT_LESSON_SCORE'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION')),
			array('name' => 'timestart', 'label' => Text::_('COM_TJLMS_LESSONREPORT_STARTDATE'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION', 'semanticType' => 'YEAR_MONTH_DAY')),
			array('name' => 'timeend', 'label' => Text::_('COM_TJLMS_LESSONREPORT_ENDDATE'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION', 'semanticType' => 'YEAR_MONTH_DAY')),
			array('name' => 'last_accessed_on', 'label' => Text::_('COM_TJLMS_ATTEMPTREPORT_LASTACCESS'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION', 'semanticType' => 'YEAR_MONTH_DAY')),
		);
	}
}
