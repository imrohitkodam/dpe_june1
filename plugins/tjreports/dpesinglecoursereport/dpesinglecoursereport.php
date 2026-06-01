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
use Joomla\CMS\Component\ComponentHelper;
use Joomla\Data\DataObject;

JLoader::import('com_tjreports.models.reports', JPATH_SITE . '/components');
use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\HTML\HTMLHelper;

/**
 * Attempt report plugin of TJReport
 *
 * @since  1.0.0
 */
class TjreportsModelDpeSinglecoursereport extends TjreportsModelReports
{
	protected $default_order = 'name';

	protected $default_order_dir = 'ASC';

	public $showSearchResetButton = -1;

	public $courseStatus = '';

	public $courseIds = array();

	private $lessonColumns = array();

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

		Factory::getApplication()->input->set('report', 'dpesinglecoursereport');

		$this->columns = array(
			'course_id' => array('table_column' => 'c.id', 'not_show_hide' => true),
			'name' => array('table_column' => 'u.name', 'title' => 'COM_TJLMS_REPORT_USERNAME'),
			'username' => array('table_column' => 'u.username', 'title' => 'COM_TJLMS_REPORT_USERUSERNAME'),
			'email' => array('table_column' => 'u.email', 'title' => 'COM_TJLMS_REPORT_USEREMAIL'),
			'usergroup' => array('title' => 'COM_TJLMS_REPORT_USERGROUP', 'disable_sorting' => true),
			/*'cat_title' => array('table_column' => 'cat.title', 'title' => 'COM_TJLMS_COURSE_CAT'),*/
			'enrolled_on_time' => array('table_column' => 'eu.enrolled_on_time', 'title' => 'COM_TJLMS_USER_ENROLLED_ON'),
			/*'assigned_by' => array('table_column' => 'ut.name', 'title' => 'COM_TJLMS_REPORT_ASSIGNED_BY'),
			'due_date' => array('table_column' => 'td.due_date', 'title' => 'COM_TJLMS_DUE_DATE'),*/
			'timeend' => array('table_column' => 'cst.timeend', 'title' => 'COM_TJLMS_COURSE_COMPLETED_DATE'),
			'cstatus' => array('table_column' => 'cst.status', 'title' => 'COM_TJLMS_REPORT_COURSE_COMPLETION_STATUS'),
			'completion' => array('title' => 'COM_TJLMS_COMPLETION', 'disable_sorting' => true),
			'totaltimespent' => array('title' => 'COM_TJLMS_REPORT_TIMESPENT', 'disable_sorting' => true),
			'lesson::attempts_done' => array('title' => 'COM_TJLMS_REPORT_LESSON_ATTEMPTS_DONE'),
			'lesson::time_spent' => array('title' => 'COM_TJLMS_REPORT_LESSON_TIMESPENT'),
			'lesson::score' => array('title' => 'COM_TJLMS_REPORT_LESSON_SCORE'),
			'lesson::lesson_status' => array('title' => 'COM_TJLMS_REPORT_LESSON_STATUS')
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
		$detail = array('client' => 'com_tjlms', 'title' => Text::_('PLG_DPE_SINGLECOURSEREPORT_TITLE'));

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
	public function getCourse()
	{
		FormHelper::addFieldPath(JPATH_ADMINISTRATOR . '/components/com_tjlms/models/fields/');
		$Courses = FormHelper::loadFieldType('courses', false);
		$Courses->__set('order', 'c.id');
		$Courses->__set('direction', ' ASC');
		$courses = $Courses->getOptionsExternally();

		$helperPath = JPATH_ADMINISTRATOR . '/components/com_tjlms/helpers/tjlms.php';
		JLoader::register('TjlmsHelper', $helperPath);
		JLoader::load('TjlmsHelper');
		$i = 0;
		$coursIds = array();

		foreach ($courses as $course)
		{
			$coursIds[] = $course->value;
		}

		return $coursIds;
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
		$this->courseIds = $this->getCourse();
		$session = Factory::getSession();

		$filters = (array) $this->getState('filters');

		if (empty($filters['course_id']))
		{
			$filters['course_id'] = $this->courseIds[0];
			$this->setState('filters', $filters);
			$this->getAdditionalColNames($this->courseIds[0]);
		}
		else
		{
			$this->getAdditionalColNames($filters['course_id']);
		}

		if (!empty($filters['cluster_id']) && $filters['cluster_id'] != 'All') {
	    	// Add session when filter has some value
			$session->set('reportCluster', $filters['cluster_id']);

		}elseif($filters['cluster_id'] == 'All')
		{
			$session->clear('reportCluster');
			$filters['cluster_id'] = '';
			$this->setState('filters', $filters);
		} else {
			$filters['cluster_id'] = $session->get('reportCluster');
			$this->setState('filters', $filters);
		}


		// Add additional columns which are not part of the query
	$items = parent::getItems();

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

			// Get the user object.
		if (in_array('usergroup', $colToshow) )
		{
			$userGroup = $this->getUserGroups($item['user_id']);
			$item['usergroup']  = implode(",", $userGroup);
		}

		$progress = $trackingHelper->getCourseTrackEntry($course_id, $assigned_user);

		if (in_array('completion', $colToshow) && isset($progress['completionPercent']))
		{
			$item['completion'] = floor($progress['completionPercent']);
		}

		if (in_array('cstatus', $colToshow))
		{
			$item['cstatus'] = ' - ';

			if (isset($progress['status']))
			{
				if ($progress['status'] == 'C')
				{
					$item['cstatus'] = "Completed";
				}
				else
				{
					$item['cstatus'] = "Incomplete";
				}
			}
		}

		if (in_array('timeend', $colToshow))
		{
			$item['timeend'] = ' - ';

			if (!empty((int) preg_replace('~[-:/s]~', '', $progress['completion_date'])))
			{
				$item['timeend'] = $tjCommon->getDateInLocal($progress['completion_date'], 0, $dateFormatShow);
			}
		}

		if (in_array('enrolled_on_time', $colToshow))
		{
			if (!empty((int) preg_replace('~[-:/s]~', '', $item['enrolled_on_time'])))
			{
				$item['enrolled_on_time'] = $tjCommon->getDateInLocal($item['enrolled_on_time'], 0, $dateFormatShow);
			}
			else
			{
				$item['enrolled_on_time'] = ' - ';
			}
		}

		if (in_array('due_date', $colToshow) && isset($item['due_date']))
		{
			if (!empty((int) preg_replace('~[-:/s]~', '', $item['due_date'])))
			{
				$item['due_date'] = $tjCommon->getDateInLocal($item['due_date'], 0, $dateFormatShow);
			}
			else
			{
				$item['due_date'] = ' - ';
			}
		}

		$item['totaltimespent'] = 0;

		foreach ($this->lessonColumns as $key => $detail)
		{
			$lessonId 		= (int) $key;
			$score = $lesson_status = $attempts_done = ' - ';
			$time_spent = 0;
			$lessonAttempt 	= (array) $trackingHelper->getLessonattemptsGrading($detail['detail'], $assigned_user);

			extract($lessonAttempt);

			$item[$key] = array();

			$item[$key]['lesson::score'] = round(gettype($score)!='string'?$score:0);
			$item[$key]['lesson::score'] = ($item[$key]['lesson::score']!= 0 ? $item[$key]['lesson::score'] : '-');

			$item[$key]['lesson::lesson_status'] = str_replace("_", " ", $lesson_status);

			if (strtolower($lesson_status) == 'not_started')
			{
				$item[$key]['lesson::score'] = '-';
			}

			$query = $db->getQuery(true);
			$query->select('SUM(TIME_TO_SEC(time_spent)) as time_spent, max(attempt) as attempts_done');
			$query->from($db->qn('#__tjlms_lesson_track', 'lt'));
			$query->where($db->qn('lt.user_id') . ' = ' . $db->quote($assigned_user));
			$query->where($db->qn('lt.lesson_id') . ' = ' . $db->quote($lessonId));

			$db->setQuery($query);
			$timeAttempts = $db->loadAssoc();

			extract($timeAttempts);

			$item[$key]['lesson::time_spent'] = $time_spent ? $this->formatTime($time_spent) : ' - ';

			if (trim($item[$key]['lesson::time_spent']) != '-')
			{
				$item[$key]['lesson::time_spent'] = substr($item[$key]['lesson::time_spent'], 0, strrpos($item[$key]['lesson::time_spent'], ":"));
			}

			$item[$key]['lesson::attempts_done'] = ' - ';

			if ($attempts_done)
			{
				$filters = array('name' => $lessonId, 'username' => $assigned_user);

				$link = $this->getReportLink('attemptreport', $filters);
				$item[$key]['lesson::attempts_done'] = $attempts_done;
			}

			$item['totaltimespent'] = $item['totaltimespent'] + (int) $time_spent;
		}

		$item['totaltimespent'] = $item['totaltimespent'] ? $this->formatTime($item['totaltimespent']) : ' - ';

		if (trim($item['totaltimespent']) != '-')
		{
			$item['totaltimespent'] = substr($item['totaltimespent'], 0, strrpos($item['totaltimespent'], ":"));
		}
	}

		// If organisation don't have elearning access then show no access message

	$user          = Factory::getUser();
	$usersClusters = array();

	if (!$user->authorise('core.manageall', 'com_cluster'))
	{
		if (! empty($filters['cluster_id']))
		{
			if (!RBACL::check($user->id, 'com_cluster', 'core.viewShika', 'com_tjlms', $filters['cluster_id']))
			{
				$items['noaccessmessage'] = Text::_('PLG_TJREPORTS_DPE_NO_ELEARNING_ACCESS');
			}
		}
		elseif (empty($filters['cluster_id']))
		{
				// If cluster is not set then check cluster is having elearning tool access if not then show no access message
			$cluster = FormHelper::loadFieldType('cluster', false);
			$clusterList = $cluster->getOptionsExternally();

			if (!empty($clusterList))
			{
				foreach ($clusterList as $clusterList)
				{
					if (!empty($clusterList->value))
					{
							// Load data for org's who have elearing access
						if (RBACL::check($user->id, 'com_cluster', 'core.viewShika', 'com_tjlms', $clusterList->value))
						{
							$usersClusters[] = $clusterList->value;
						}
					}
				}

				$usersClusters = array_filter($usersClusters);

				if (empty($usersClusters))
				{
					$items['noaccessmessage'] = Text::_('PLG_TJREPORTS_DPE_NO_ELEARNING_ACCESS');
				}
			}
		}
	}

	$items = $this->sortCustomColumns($items);

	return $items;
}

	/**
	 * Method to get a DataObjectbaseQuery object for retrieving the data set from a database.
	 *
	 * @param   boolean  $countQuery  Check its count query or not
	 *
	 * @return  DataObjectbaseQuery  A DataObjectbaseQuery object to retrieve the data set.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	protected function getListQuery($countQuery = false)
	{ 
	$db      = $this->_db;
	$filters = $this->getState('filters');

		// Remove the cstatus filter from the filters
	if (array_key_exists("cstatus", $filters))
	{
		if (empty($this->courseStatus))
		{
			$this->courseStatus = $filters["cstatus"];
		}

			// comment below code as cs status need to show the course status in filter
		/* unset($filters["cstatus"]);*/
		$this->setState('filters', $filters);
	}

	$query     = parent::getListQuery();
	$colToshow = $this->getState('colToshow');
	$user      = Factory::getUser();
	$userId    = $user->id;

	if ($countQuery)
	{
		$query->select('COUNT(eu.id)');
	}
	else
	{
		$query->select(array('u.id as user_id', 'c.id as course_id'));
	}

	$query->from('#__tjlms_enrolled_users AS eu');

	$query->join('INNER', $db->qn('#__users', 'u') . ' ON (' .
		$db->qn('u.id') . ' = ' . $db->qn('eu.user_id') . ')');

	if (array_intersect(array('timeend', 'cstatus'), $colToshow))
	{
		$query->join('LEFT', $db->qn('#__tjlms_course_track', 'cst') . ' ON (' . $db->qn('cst.course_id') . ' = ' .
			$db->qn('eu.course_id') . ' AND ' . $db->qn('cst.user_id') . ' = ' . $db->qn('eu.user_id') . ')');
	}

	$query->join('LEFT', $db->qn('#__tjlms_courses', 'c') . ' ON (' . $db->qn('c.id') . ' = ' .
		$db->qn('eu.course_id') . ')');
	$query->join('LEFT', $db->qn('#__tjlms_lessons', 'l') . ' ON (' . $db->qn('l.course_id') . ' = ' .
		$db->qn('c.id') . ')');

	if (array_intersect(array('due_date', 'assigned_by'), $colToshow))
	{
		$query->join('LEFT', $db->qn('#__jlike_content', 'jc') . ' ON (' . $db->qn('jc.element_id') . ' = ' .
			$db->qn('c.id') . ')');
		$query->join('LEFT', $db->qn('#__jlike_todos', 'td') . ' ON (' . $db->qn('td.content_id') . ' = ' .
			$db->qn('jc.id') . ' AND ' . $db->qn('td.assigned_to') . ' = ' .
			$db->qn('eu.user_id') . ')');
		$query->join('LEFT', $db->qn('#__users', 'ut') . ' ON (' . $db->qn('ut.id') . ' = ' .
			$db->qn('td.assigned_by') . ')');
	}

	if (in_array('cat_title', $colToshow))
	{
		$query->join('LEFT', '#__categories AS cat ON c.catid = cat.id');
	}

	$filters = (array) $this->getState('filters');

	$courses = $this->courseIds;

	if (!empty($courses) && count($courses) > 1)
	{
		$query->where("c.id IN('" . implode("','", $courses) . "')");
	}

	$usersClusters = array();

	if (!$user->authorise('core.manageall', 'com_cluster'))
	{
		$cluster = FormHelper::loadFieldType('cluster', false);
		$clusterList = $cluster->getOptionsExternally();

		if (!empty($clusterList))
		{
			foreach ($clusterList as $clusterList)
			{
				if (!empty($clusterList->value))
				{
						// Load data for org's who have elearing access
					if (RBACL::check($user->id, 'com_cluster', 'core.viewShika', 'com_tjlms', $clusterList->value))
					{
						$usersClusters[] = $clusterList->value;
					}
				}
			}
		}

		$query->where($db->qn('tjc.id') . " IN ('" . implode("','", $usersClusters) . "')");
	}

	if (!empty($this->courseStatus))
	{
		if ($this->courseStatus == "I")
		{
			$query->where("(cst.status= '' OR cst.status= 'I')");
		}
		elseif ($this->courseStatus == "C")
		{
			$query->where("cst.status='C'");
		}
	}

	if (empty($filters['course_id']))
	{
		$query->where('c.id=0');
	}

	if (isset($filters['usergroup']) && (! empty($filters['usergroup'])))
	{
		$query->join('INNER', $db->qn('#__user_usergroup_map', 'ugm') . ' ON (' .
			$db->qn('ugm.user_id') . ' = ' . $db->qn('eu.user_id') . ')');
		$query->where($db->qn('ugm.group_id') . ' = ' . (int) $filters['usergroup']);
	}

	if ((isset($filters['cluster_id'])) && (! empty($filters['cluster_id'])) || (count($usersClusters)))
	{
		$query->join('INNER', $db->qn('#__tj_cluster_nodes', 'tjcn') . ' ON (' .
			$db->qn('eu.user_id') . ' = ' . $db->qn('tjcn.user_id') . ')');
		$query->join('INNER', $db->qn('#__tj_clusters', 'tjc') . ' ON (' .
			$db->qn('tjcn.cluster_id') . ' = ' . $db->qn('tjc.id') . ')');

		if (!empty($filters['cluster_id']))
		{
			$query->where($db->qn('tjc.id') . ' = ' . (int) $filters['cluster_id']);
		}
	}

	if ((int) $filters['report_filter'] === -1)
	{
		$hasUsers = TjlmsHelper::getSubusers();

		if ($hasUsers)
		{
			$query->where("eu.user_id IN('" . implode("','", $hasUsers) . "')");
		}
	}

	$query->where($db->qn('u.block') . ' = 0');
	$query->where($db->qn('c.state') . ' = 1');
	$query->where($db->qn('eu.state') . ' = 1');
	$query->group('eu.course_id,eu.user_id');
	return $query;
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
		$agencyId = array();
		$team = array();

		$user = Factory::getUser();

		if (!$user->authorise('core.manageall', 'com_cluster'))
		{
			BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_multiagency/models');
			$MultiagencyModel = BaseDatabaseModel::getInstance('Multiagency', 'MultiagencyModel', array('ignore_request' => true));
			$params = ComponentHelper::getParams('com_multiagency');
			$memberRole = $params->get('member_role_id', '0', 'INT');
			$agencies = $MultiagencyModel->getAllocatedAgencies(Factory::getUser()->id, array($memberRole));

			foreach ($agencies as $agency)
			{
				$agencyId[] = $agency->id;
			}
		}

		$query = $db->getQuery(true);
		$query->select('distinct(user_id)');
		$query->from($db->qn('#__tjsu_users'));
		$query->where($db->qn('client') . '= "com_multiagency"');

		if (!$user->authorise('core.manageall', 'com_cluster'))
		{
			$query->where($db->qn('client_id') . " in ( '" . implode("','", $agencyId) . "')");
		}

		$db->setQuery($query);
		$team = $db->loadColumn();

		if (count($team) > 0)
		{
			$query = $db->getQuery(true);
			$query->select('u.id,u.name');
			$query->from('#__users as u');
			$query->where($db->qn('u.block') . ' = 0');
			$query->where("u.id in ('" . implode("','", $team) . "')");
			$db->setQuery($query);
			$users = $db->loadObjectList();
		}

		$userFilter[] = HTMLHelper::_('select.option', '', Text::_('COM_TJREPORTS_FILTER_SELECT_USER'));

		if (!empty($users))
		{
			foreach ($users as $eachUser)
			{
				$userFilter[] = HTMLHelper::_('select.option', $eachUser->id, $eachUser->name);
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
	public function getCourseFilter()
	{
		FormHelper::addFieldPath(JPATH_ADMINISTRATOR . '/components/com_tjlms/models/fields/');
		$Courses = FormHelper::loadFieldType('courses', false);
		$Courses->__set('order', 'c.id');
		$Courses->__set('direction', ' ASC');
		$courses = $Courses->getOptionsExternally();

		// Currently only one course is present so we are hiding default option
		$coursesFilter[] = HTMLHelper::_('select.option', '', '- ' . Text::_('COM_TJLMS_LESSONREPORT_COURSENAME') . ' -');

		if (!empty($courses))
		{
			foreach ($courses as $course)
			{
				$coursesFilter[] = HTMLHelper::_('select.option', $course->value, $course->text);
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
		$userFilter 		= $this->getUserFilter();
		$courseFilter 		= $this->getCourseFilter($created_by);

		// To get Agency dropdown list
		FormHelper::addFieldPath(JPATH_ADMINISTRATOR . '/components/com_tjfields/models/fields');
		$clusterList = FormHelper::loadFieldType('Cluster', false);
		$clusterOptions = $clusterList->getOptionsExternally();
		$user          = Factory::getUser();

		// To add all value if the user has admin dpe admin acess and admin acess with multiple organisation.
		if ($user->authorise('core.manageall', 'com_cluster') && count($clusterOptions)>1)
		{
		  $clusterOptions[0]->value = "All" ;
		}

		$groups  = HTMLHelper::_('user.groups', true);
		array_unshift($groups, HTMLHelper::_('select.option', '', Text::_('COM_TJLMS_ENROLLED_USER_ACCESS')));

		$filters = $this->getState('filters');
		$courseStatusFilter = array("" => "Select Status","C" => "Completed", "I" => "Incomplete");
		$dispFilters = array(
			array(
				'username' => array(
					'search_type' => 'select', 'select_options' => $userFilter, 'type' => 'equal', 'searchin' => 'u.id'
				),
				/*
				'cat_title' => array(
					'search_type' => 'select', 'select_options' => $catFilter, 'type' => 'equal', 'searchin' => 'c.catid'
				),
				*/
				'cstatus' => array(
					'search_type' => 'select', 'select_options' => $courseStatusFilter, 'type' => 'equal', 'searchin' => 'cst.status'
				),
			),
			array(
				'course_id' => array(
					'search_type' => 'select', 'select_options' => $courseFilter, 'type' => 'equal', 'searchin' => 'c.id'
				),
				'cluster_id' => array(
					'search_type' => 'select', 'select_options' => $clusterOptions, 'type' => 'equal', 'searchin' => 'tjc.id'
				)
				/*,
				'usergroup' => array(
						'search_type' => 'select', 'select_options' => $groups, 'type' => 'equal', 'searchin' => 'ugm.group_id'
				)
				*/
			)
		);

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
					'lesson::score'		 => ' - ',
					'lesson::lesson_status' => ' - '
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

	/**
	 * Method to get the total number of items for the data set.
	 *
	 * @return  integer  The total number of items available in the data set.
	 *
	 * @since   1.6
	 */
	public function getTotal()
	{
		// Get a storage key.
		$store = $this->getStoreId('getTotal');

		// Try to load the data from internal storage.
		if (isset($this->cache[$store]))
		{
			return $this->cache[$store];
		}

		try
		{
			// Load the total and add the total to the internal cache.
			$query = $this->getListQuery(true);
			$query->clear('select')->clear('order')->clear('limit')->clear('offset')->select('COUNT(eu.id)');
			$this->cache[$store] = (int) $this->_getListCount($query);
		}
		catch (\RuntimeException $e)
		{
			$this->setError($e->getMessage());

			return false;
		}

		return $this->cache[$store];
	}
}
