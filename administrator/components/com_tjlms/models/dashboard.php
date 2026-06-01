<?php
/**
 * @package     Shika
 * @subpackage  com_tjlms
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access.
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 * Methods supporting a list of Tjlms records.
 *
 * @since  1.0.0
 */
class TjlmsModelDashboard extends BaseDatabaseModel
{
	/**
	 * @var		string	The prefix to use with controller messages.
	 * @since	1.6
	 */
	protected $text_prefix = 'COM_TJLMS';

	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @since   1.7
	 * @see     JController
	 */
	public function __construct($config = array())
	{
		parent::__construct();
	}

	/**
	 * Get most of the info for dashboard
	 *
	 * @return  ARRAY $dashboardDetails
	 *
	 * @since   1.0.0
	 */
	public function getDashboardDetails()
	{
		$user     = Factory::getUser();
		$olUserid = $user->id;
		$isroot   = $user->authorise('core.admin');

		try
		{
			// Get course Data
			$db = $this->_db;
			$query = $db->getQuery(true);
			$query->select('COUNT(c.id) as total_course, COUNT(IF(c.type="1", 1, NULL)) as paid_courses , COUNT(IF(c.type="0", 1, NULL)) as free_course');
			$query->from($db->quoteName('#__tjlms_courses', 'c'));
			$query->JOIN('INNER', $db->quoteName('#__categories', 'cat') . ' ON (' . $db->quoteName('cat.id') . ' = ' . $db->quoteName('c.catid') . ')');
			$query->where($db->quoteName('cat.published') . ' <> -2');
			$query->where($db->quoteName('c.state') . ' = 1');

			if (!$isroot)
			{
				$query->where($db->quoteName('created_by') . ' = ' . (int) $olUserid);
			}

			$db->setQuery($query);
			$courseData = $db->loadAssoc();
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}

		$totalcourse     = $courseData['total_course'];
		$totalPaidcourse = $courseData['paid_courses'];
		$totalFreecourse = $courseData['free_course'];

		try
		{
			// Get Enrollment Data
			$query = $db->getQuery(true);
			$query->select('COUNT(DISTINCT eu.user_id) as enrolled_student, SUM(IF(eu.state = 0, 1, 0)) as pending_enrollment');
			$query->from($db->quoteName('#__tjlms_enrolled_users', 'eu'));
			$query->JOIN('LEFT', $db->quoteName('#__tjlms_courses', 'c') . ' ON (' . $db->quoteName('c.id') . ' = ' . $db->quoteName('eu.course_id') . ')');
			$query->where($db->quoteName('c.state') . ' = 1');

			if (!$isroot)
			{
				$query->where($db->quoteName('c.created_by') . ' = ' . (int) $olUserid);
			}

			$db->setQuery($query);
			$EnrollmentData = $db->loadAssoc();
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}

		$totalStudents = $EnrollmentData['enrolled_student'];
		$TotalPendingEnrollment = $EnrollmentData['pending_enrollment'];

		try
		{
			// Get order and revenue data
			$query = $db->getQuery(true);
			$query->select('COUNT(o.id) as orders, SUM(o.amount) as amount');
			$query->from($db->quoteName('#__tjlms_orders', 'o'));
			$query->join('LEFT', $db->quoteName('#__tjlms_courses', 'c') . ' ON (' . $db->quoteName('c.id') . ' = ' . $db->quoteName('o.course_id') . ')');
			$query->where($db->quoteName('o.status') . '="C"');

			if (!$isroot)
			{
				$query->where($db->quoteName('c.created_by') . '=' . (int) $olUserid);
			}

			$db->setQuery($query);
			$OrderData = $db->loadAssoc();
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}

		$totalOrders        = $OrderData['orders'];
		$totalRevenueAmount = $OrderData['amount'];

		$dashboardDetails                           = array();
		$dashboardDetails['TotalCourse']            = $totalcourse;
		$dashboardDetails['TotalFreeCourse']        = $totalFreecourse;
		$dashboardDetails['TotalPaidCourse']        = $totalPaidcourse;
		$dashboardDetails['TotalStudents']          = $totalStudents;
		$dashboardDetails['TotalPendingEnrollment'] = $TotalPendingEnrollment;
		$dashboardDetails['totalOrders']            = $totalOrders;
		$dashboardDetails['totalRevenueAmount']     = $totalRevenueAmount;

		return $dashboardDetails;
	}

	/**
	 * Get popular student for dashboard based on active courses
	 *
	 * @return  ARRAY $popularStudent
	 *
	 * @since  1.0.0
	 */
	public function getpopularStudent()
	{
		$user     = Factory::getUser();
		$olUserid = $user->id;
		$isroot   = $user->authorise('core.admin');

		try
		{
			$db = $this->_db;
			$query = $db->getQuery(true);
			$query->select('eu.user_id,COUNT(*) as enrolledIn, u.name, u.username');
			$query->from($db->quoteName('#__tjlms_enrolled_users', 'eu'));
			$query->join('INNER',  $db->quoteName('#__users', 'u') . ' ON (' . $db->quoteName('u.id') . ' = ' . $db->quoteName('eu.user_id') . ')');
			$query->join('INNER',  $db->quoteName('#__tjlms_courses', 'c') . ' ON (' . $db->quoteName('c.id') . ' = ' . $db->quoteName('eu.course_id') . ')');
			$query->join('INNER', $db->quoteName('#__categories', 'cat') . ' ON (' . $db->quoteName('cat.id') . ' = ' . $db->quoteName('c.catid') . ')');
			$query->where($db->quoteName('eu.state') . ' = 1 AND' . $db->quoteName('c.state') . ' =1 AND' . $db->quoteName('cat.published') . '=1');

			if (!$isroot)
			{
				$query->where($db->quoteName('c.created_by') . ' = ' . (int) $olUserid);
			}

			$query->group($db->quoteName('eu.user_id') . ' ORDER BY enrolledIn DESC LIMIT 0,4');
			$db->setQuery($query);
			$popularStudent = $db->loadobjectlist();

			return $popularStudent;
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}
	}

	/**
	 * Get popular student for dashboard based on active courses
	 *
	 * @return  ARRAY $mostLikedCourses
	 *
	 * @since  1.0.0
	 */
	public function getmostLikedCourses()
	{
		$user     = Factory::getUser();
		$olUserid = $user->id;
		$isroot   = $user->authorise('core.admin');

		try
		{
			$db    = $this->_db;
			$query = $db->getQuery(true);
			$query->select($db->quoteName(array('c.title', 'l.like_cnt', 'c.image', 'c.storage')));
			$query->from($db->quoteName('#__tjlms_courses', 'c'));
			$query->join('INNER', $db->quoteName('#__jlike_content', 'l') . ' ON (' . $db->quoteName('l.element_id') . ' = ' . $db->quoteName('c.id') . ')');

			if (!$isroot)
			{
				$query->where($db->quoteName('c.created_by') . ' = ' . (int) $olUserid);
			}

			$query->where(
			$db->quoteName('l.element') . ' ="com_tjlms.course" AND ' . $db->quoteName(
			'l.like_cnt') . ' > 0 AND' . $db->quoteName('c.state') . ' =1 ORDER BY' . $db->quoteName('l.like_cnt') . ' DESC LIMIT 0,4'
			);
			$db->setQuery($query);
			$mostLikedCourses = $db->loadObjectlist();

			return $mostLikedCourses;
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}
	}

	/**
	 * Get activity of student for dashboard based on active courses
	 *
	 * @return  ARRAY $yourActivities
	 *
	 * @since  1.0.0
	 */
	public function getactivity()
	{
		// Set start and end date
		$start = $this->getState('filter.begin');
		$end   = $this->getState('filter.end');

		require_once JPATH_SITE . '/components/com_tjlms/helpers/tracking.php';
		$comtjlmstrackingHelper = new comtjlmstrackingHelper;

		// Get activity for all students
		$activitydata = array();
		$activitydata['user_id']   = '';
		$activitydata['start']     = date("Y-m-d", strtotime($start));
		$activitydata['end']       = date("Y-m-d", strtotime($end));
		$activitydata['course_id'] = '';

		$yourActivities = $comtjlmstrackingHelper->getactivity($activitydata);

		return $yourActivities;
	}

	/**
	 * Get revenue data
	 *
	 * @return  ARRAY $revenueData
	 *
	 * @since  1.0.0
	 */
	public function getrevenueData()
	{
		$start = $this->getState('filter.begin');
		$end   = $this->getState('filter.end');

		$comtjlmsHelper = new comtjlmsHelper;
		$data           = array();
		$data['start']  = $start;
		$data['end']    = $end;
		$revenueData    = $comtjlmsHelper->getrevenueData($data);

		return $revenueData;
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * @param   string  $ordering   ordering of the search result
	 * @param   string  $direction  direction of search result
	 *
	 * @return  array  An array of conditions to add to add to ordering queries.
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		// Initialise variables.
		$app   = Factory::getApplication();
		$begin = $app->getUserStateFromRequest('filter.begin', 'filter_begin', '', 'string');

		if (empty($begin))
		{
			$begin = HTMLHelper::date($input = 'now -1 month', 'Y-m-d', false);
		}

		$this->setState('filter.begin', $begin);

		$end = $app->getUserStateFromRequest('filter.end', 'filter_end', '', 'string');

		if (empty($end))
		{
			$end = HTMLHelper::date($input = 'now +1 day', 'Y-m-d', false);
		}

		$this->setState('filter.end', $end);

		// List state information.
		parent::populateState($ordering, $direction);
	}

	/**
	 * Function getStoreId for getting all id.
	 *
	 * @param   integer  $id  the current id
	 *
	 * @return   integer  $id  The id is returned
	 */
	protected function getStoreId($id = '')
	{
		// Compile the store id.
		$id .= ':' . $this->getState('filter.state');

		return parent::getStoreId($id);
	}

	/**
	 * Function to check if reminder templates are in jlike or not
	 *
	 * @return  Boolean
	 */
	public function hasReminderTemplates()
	{
		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_jlike/models/', 'JlikeModelReminders');
		$model = BaseDatabaseModel::getInstance('Reminders', 'JlikeModel');

		if ($model)
		{
			$model->setState('filter.content_type', 'com_tjlms.course');
			$exists = $model->getItems();

			return $exists;
		}

		return true;
	}

	/**
	 * Function to check if certificate migration is done.
	 *
	 * @return  Array
	 */
	public function checkMigrationStatus()
	{
		$results               = array();
		$tjlmsCertificateModel = BaseDatabaseModel::getInstance('Certificates', 'TjlmsModel', array('ignore_request' => true));
		$tjlmsCertificateModel = $tjlmsCertificateModel->getItems();
		$results['templates']  = count($tjlmsCertificateModel);

		$db      = Factory::getDbo();
		$query   = $db->getQuery(true);
		$query->select('*');
		$query->from('#__tj_houseKeeping');
		$query->where($db->quoteName('client') . " = " . $db->quote('com_tjlms'));
		$db->setQuery($query);
		$houseKeepData = $db->loadObjectlist();
		$results['houseKeepData'] = count($houseKeepData);

		return $results;
	}
}
