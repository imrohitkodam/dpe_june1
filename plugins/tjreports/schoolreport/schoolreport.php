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
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\Data\DataObject;
use Joomla\CMS\Component\ComponentHelper;

JLoader::import('com_tjreports.models.reports', JPATH_SITE . '/components');

/**
 * Attempt report plugin of TJReport
 *
 * @since  1.0.0
 */
class TjreportsModelSchoolreport extends TjreportsModelReports
{
	protected $default_order = 'title';

	protected $default_order_dir = 'ASC';

	public $showSearchResetButton = -1;

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
			'course_id' => array('table_column' => 'tjc.id', 'not_show_hide' => true),
			'title' => array('table_column' => 'tjc.title', 'title' => 'COM_TJLMS_COURSE_NAME'),
			'enrolledUsers' => array('title' => 'COM_TJLMS_ENROLLED_USERS_CNT', 'table_column' => ''),
			'pendingEnrollment' => array('title' => 'COM_TJLMS_LESSON_STATUS_STARTED', 'table_column' => ''),
			'completedUsers' => array('title' => 'COM_TJLMS_LESSON_STATUS_COMPLETED', 'table_column' => '')
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
		$detail = array('client' => 'com_tjlms', 'title' => Text::sprintf('PLG_TJREPORTS_SCHOOLREPORT_TITLE'), Text::_('COM_MULTIAGENCY_ORGANISATION'));

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
	 * Function to get the course filter
	 *
	 * @return  object
	 *
	 * @since 1.0.0
	 */
	public function getCourseIds()
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
		$courseFilter 		= $this->getCourseFilter($created_by);

		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_multiagency/models');
		$multiagencyModel = BaseDatabaseModel::getInstance('Multiagency', 'MultiagencyModel');

		$agencyFilter = $multiagencyModel->getAgencyFilter();

		$groups  = HTMLHelper::_('user.groups', true);
		array_unshift($groups, HTMLHelper::_('select.option', '', Text::_('COM_TJLMS_ENROLLED_USER_ACCESS')));

		$filters = $this->getState('filters');

		$dispFilters = array(
			array(
				'title' => array(
					'search_type' => 'select', 'select_options' => $courseFilter, 'type' => 'equal', 'searchin' => 'tjc.id'
				)
			),
			array(
				'agency' => array(
							'search_type' => 'select', 'select_options' => $agencyFilter, 'type' => 'equal', 'searchin' => 'c.client_id'
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
		$colToshow = $this->getState('colToshow');
		$filters  = $this->getState('filters');
		$user     = Factory::getUser();
		$userId   = $user->id;
		$myTeamClause = false;

		if ((int) $filters['report_filter'] === 1 || (int) $filters['report_filter'] === 0)
		{
			$createdByClause = true;
		}
		elseif ((int) $filters['report_filter'] === -1)
		{
			$hasUsers = TjlmsHelper::getSubusers();
			$myTeamClause = true;
		}

		$query->select(
		array(' eu.course_id as course_id',
		'eu.user_id',
		'sum(IF(eu.state="1", 1, 0)) as enrolledUsers'
		, 'sum(IF(lt.lesson_status="started", 1, 0)) as pendingEnrollment',
		'sum(IF(lt.lesson_status="completed", 1, 0)) as completedUsers')
		);
		$query->from($db->qn('#__tjlms_enrolled_users', 'eu'));
		$query->join('LEFT', $db->qn('#__tjlms_course_track', 'tjcs') . ' ON (' . $db->qn('tjcs.course_id') . ' = ' . $db->qn(
		'eu.course_id') . ' AND ' . $db->qn('tjcs.user_id') . ' = ' . $db->qn('eu.course_id') . ')');
		$query->join('LEFT', $db->qn('#__tjlms_courses', 'tjc') . ' ON (' . $db->qn('tjc.id') . ' = ' . $db->qn('eu.course_id') . ')');
		$query->join('LEFT', $db->qn('#__tjlms_lesson_track', 'lt') . ' ON (' . $db->qn('lt.user_id') . ' = ' . $db->qn('eu.user_id') . ')');
		$query->join('LEFT', $db->qn('#__tj_cluster_nodes', 'cn') . ' ON (' . $db->qn('cn.user_id') . ' = ' . $db->qn('eu.user_id') . ')');
		$query->join('LEFT', $db->qn('#__tj_clusters', 'c') . ' ON (' . $db->qn('c.id') . ' = ' . $db->qn('cn.cluster_id') . ')');

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
			$query->where('c.client_id IN(' . implode(',', $clients) . ')');
		}
		elseif (empty(count($clients)))
		{
			$query->where('lt.user_id=0');
		}

		$query->where('eu.course_id IN(' . implode(',', $this->getCourseIds()) . ')');

		$query->where('c.state=1');
		$query->group('eu.course_id');

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
		$filters = (array) $this->getState('filters');

		// Add additional columns which are not part of the query
		$items = parent::getItems();

		$lmsparams = ComponentHelper::getParams('com_tjlms');
		$dateFormatShow = $lmsparams->get('date_format_show', 'Y-m-d H:i:s');

		jimport('techjoomla.common');
		JLoader::import('components.com_tjlms.helpers.tracking', JPATH_SITE);
		JLoader::import('components.com_tjlms.helpers.main', JPATH_SITE);
		$tjCommon 		= new TechjoomlaCommon;
		$trackingHelper = new ComtjlmstrackingHelper;

		$db = $this->_db;
		$colToshow = $this->getState('colToshow');

		foreach ($items as &$item)
		{
			$courseId 		= $item['course_id'];
			$assignedUser 	= $item['user_id'];

			$progress = $trackingHelper->getCourseTrackEntry($courseId, $assignedUser);
		}

		$items = $this->sortCustomColumns($items);

		return $items;
	}
}
