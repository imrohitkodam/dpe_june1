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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Form\FormHelper;
use Joomla\Data\DataObject;

JLoader::import('com_tjreports.models.reports', JPATH_SITE . '/components');

/**
 * Attempt report plugin of TJReport
 *
 * @since  1.0.0
 */
class TjreportsModelDpeAttemptreport extends TjreportsModelReports
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
		JLoader::import('administrator.components.com_tjlms.helpers.tjlms', JPATH_SITE);

		$this->columns = array(
			'attempt' => array('table_column' => 'lt.attempt', 'title' => 'COM_TJLMS_TITLE_ATTEMPTS'),
			'name' => array('table_column' => 'l.title', 'title' => 'COM_TJLMS_ATTEMPTREPORT_NAME'),
			'username' => array('table_column' => 'u.username', 'title' => 'COM_TJLMS_REPORT_USERUSERNAME'),
			'usergroup' => array('title' => 'COM_TJLMS_REPORT_USERGROUP', 'disable_sorting' => true),
			'time_spent' => array('table_column' => 'lt.time_spent', 'title' => 'COM_TJLMS_REPORT_LESSON_TIMESPENT'),
			'lesson_status' => array('table_column' => 'lt.lesson_status', 'title' => 'COM_TJLMS_REPORT_LESSON_STATUS'),
			'score' => array('table_column' => 'lt.score', 'title' => 'COM_TJLMS_REPORT_LESSON_SCORE'),
			'timestart' => array('title' => 'COM_TJLMS_LESSONREPORT_STARTDATE'),
			'timeend' => array('title' => 'COM_TJLMS_LESSONREPORT_ENDDATE'),
			'last_accessed_on' => array('table_column' => 'lt.last_accessed_on', 'title' => 'COM_TJLMS_ATTEMPTREPORT_LASTACCESS')
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
	 * Create an array of filters
	 *
	 * @return    void
	 *
	 * @since    1.0
	 */
	public function displayFilters()
	{
		$lang = Factory::getLanguage();
		$base_dir = JPATH_SITE . '/administrator';
		$lang->load('com_tjlms', $base_dir);

		$reportOptions  = TjlmsHelper::getReportFilterValues($this, $selected, $created_by, $myTeam);

		JLoader::import('components.com_tjlms.models.reports', JPATH_ADMINISTRATOR);
		$TjlmsModelReports 	= new TjlmsModelReports;
		$lessonFilter 		= $TjlmsModelReports->getLessonFilter($created_by);
		$userFilter 		= $this->getUserFilter();

		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_multiagency/models');
		$multiagencyModel = BaseDatabaseModel::getInstance('Multiagency', 'MultiagencyModel');

		$agencyFilter = $multiagencyModel->getAgencyFilter();

		$statusArray = array();
		$statusArray[] = JHTML::_('select.option', '', Text::_('COM_TJLMS_FILTER_SELECT_STATUS'));
		$statusArray[] = JHTML::_('select.option', 'started', Text::_('COM_TJLMS_FILTER_STATUS_STARTED'));
		$statusArray[] = JHTML::_('select.option', 'passed', Text::_('COM_TJLMS_FILTER_STATUS_PASSED'));
		$statusArray[] = JHTML::_('select.option', 'failed', Text::_('COM_TJLMS_FILTER_STATUS_FAILED'));
		$statusArray[] = JHTML::_('select.option', 'completed', Text::_('COM_TJLMS_FILTER_STATUS_COMPLETED'));
		$statusArray[] = JHTML::_('select.option', 'incomplete', Text::_('COM_TJLMS_LESSONSTATUS_INCOMPLETE'));

		$dispFilters = array(
			array(
				'attempt' => array(
					'search_type' => 'text', 'type' => 'equal', 'searchin' => 'lt.attempt'),
				'name' => array(
					'search_type' => 'text', 'select_options' => $lessonFilter, 'type' => 'equal', 'searchin' => 'l.title'
				),
				'username' => array(
					'search_type' => 'select', 'select_options' => $userFilter, 'type' => 'equal', 'searchin' => 'u.id'
				),
				'lesson_status' => array(
					'search_type' => 'select', 'select_options' => $statusArray, 'type' => 'equal', 'searchin' => 'lt.lesson_status'
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
				)
			),
			array(
				'last_accessed_on' => array(
					'search_type' => 'date.range',
					'searchin' => 'last_accessed_on',
					'last_accessed_on_from' => array('attrib' => array('placeholder' => 'FROM (YYYY-MM-DD)')),
					'last_accessed_on_to' => array('attrib' => array('placeholder' => 'TO (YYYY-MM-DD)')),
				),
				'agency' => array(
							'search_type' => 'select', 'select_options' => $agencyFilter, 'type' => 'equal', 'searchin' => 'tjc.client_id'
				),
			)
		);

		// Commented to remove "All" filter for report

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
		$colToshow = (array) $this->getState('colToshow');
		$filters = $this->getState('filters');
		$user     = Factory::getUser();
		$userId   = $user->id;

		// Must have columns to get details of non linked data like completion
		$query->select(array('lt.user_id'));
		$query->select(array('lt.timestart', 'lt.timeend'));
		$query->from($db->quoteName('#__tjlms_lesson_track', 'lt'));
		$query->join('INNER', $db->quoteName('#__tjlms_lessons', 'l') . ' ON (' . $db->quoteName('lt.lesson_id') . ' = ' . $db->quoteName('l.id') . ')');
		$query->join('INNER', $db->quoteName('#__tjlms_courses', 'c') . ' ON (' . $db->quoteName('c.id') . ' = ' . $db->quoteName('l.course_id') . ')');
		$query->join('INNER', $db->quoteName('#__users', 'u') . ' ON (' . $db->quoteName('lt.user_id') . ' = ' . $db->quoteName('u.id') . ')');
		$query->join('INNER', $db->quoteName('#__tj_cluster_nodes', 'tjcn') . ' ON (' . $db->quoteName('lt.user_id') . ' = ' .
				$db->quoteName('tjcn.user_id') . ')');
		$query->join('INNER', $db->quoteName('#__tj_clusters', 'tjc') . ' ON (' .
		$db->quoteName('tjcn.cluster_id') . ' = ' . $db->quoteName('tjc.id') . ')');

		$courses = $this->getCourse();
		$query->where('c.id IN(' . implode(',', $courses) . ')');
		$query->where($db->qn('u.block') . ' = 0');

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

		$reportId = $this->getState('reportId');
		$viewAll = $this->checkpermissions($reportId);

		if ((int) $filters['report_filter'] === 1 )
		{
			$query->where('c.created_by = ' . (int) $userId);
		}
		elseif ((int) $filters['report_filter'] === -1)
		{
			$hasUsers = TjlmsHelper::getSubusers();
			$query->where('lt.user_id IN(' . implode(',', $hasUsers) . ')');
		}
		elseif ((int) $filters['report_filter'] === -2)
		{
			$hasUsers = TjlmsHelper::getSubusers();
			$query->where('lt.user_id IN(' . implode(',', $hasUsers) . ')');
		}

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
		}

		return $items;
	}
}
