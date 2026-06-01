<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Tjlms
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

// No direct access.
defined('_JEXEC') or die;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;

jimport('joomla.application.component.modeladmin');

require_once JPATH_SITE . '/components/com_tjlms/helpers/main.php';

/**
 * Tjlms model.
 *
 * @since  1.6
 */
class TjlmsModelTeacher_Report extends BaseDatabaseModel
{
	/**
	 * @var		string	The prefix to use with controller messages.
	 *
	 * @since	1.6
	 */
	protected $text_prefix = 'COM_TJLMS';

	/**
	 * constructor
	 *
	 * @param   ARRAY  $config  id of course
	 *
	 * @since	1.0
	 */
	public function __construct($config = array())
	{
		$item = parent::__construct($config);

		return $item;
	}

	/**
	 * Method to fetch total students for the status
	 *
	 * @param   int  $course_id  id of course
	 *
	 * @return	count of the status
	 *
	 * @since	1.0
	 */
	public function coursecompletedusers($course_id)
	{
		$db = Factory::getDBO();
		$query = $db->getQuery(true);
		$query->select('COUNT(DISTINCT ct.user_id)')
		->from(' #__tjlms_course_track AS ct')
		->join('right', ' #__tjlms_enrolled_users as eu ON (eu.user_id=ct.user_id) AND (eu.course_id=ct.course_id)')
		->where('ct.course_id =' . (int) $course_id)
		->where('ct.status = "C"');
		$db->setQuery($query);

		$courseCompletedUsers = $db->loadResult();

		return $courseCompletedUsers;
	}

	/**
	 * Get top 10 scorers for the teacher
	 *
	 * @param   INT  $course_id  course_id
	 *
	 * @return  OBJECT  $topscorers
	 */
	public function courseTotalEnrolled($course_id)
	{
		$db = Factory::getDBO();
		$query = $db->getQuery(true);
		$query->select('COUNT(DISTINCT(eu.user_id))')
		->from(' #__tjlms_enrolled_users AS eu')
		->join('inner', '`#__users` AS u ON eu.user_id = u.id')
		->where('eu.course_id =' . (int) $course_id)
		->where('eu.state = 1');
		$db->setQuery($query);
		$enrolled = $db->loadResult();

		return $enrolled;
	}

	/**
	 * Get top 10 scorers for the teacher
	 *
	 * @param   INT  $course_id  course_id
	 *
	 * @return  OBJECT  $topscorers
	 */
	public function coursePendingEnrollments($course_id)
	{
		$db = Factory::getDBO();
		$query = $db->getQuery(true);
		$query->select('COUNT(DISTINCT(eu.user_id))')
		->from(' #__tjlms_enrolled_users AS eu')
		->where('eu.course_id =' . (int) $course_id)
		->where('eu.state != 1');
		$db->setQuery($query);
		$enrolled = $db->loadResult();

		return $enrolled;
	}

	/**
	 * Get top 10 scorers for the teacher
	 *
	 * @param   INT  $course_id  course_id
	 * @param   INT  $limit      limit
	 *
	 * @return  OBJECT  $topscorers
	 */
	public function getTopScorer($course_id,$limit)
	{
		$db = Factory::getDBO();
		$query = $db->getQuery(true);
		$query->select('ct.user_id, ct.no_of_lessons, ct.completed_lessons, ct.status')
		->from(' #__tjlms_course_track as ct')
		->join('LEFT', ' #__tjlms_enrolled_users as eu ON (eu.user_id=ct.user_id) AND (eu.course_id=ct.course_id)')
		->where('ct.course_id =' . (int) $course_id)
		->where('eu.state = 1')
		->order('ct.completed_lessons DESC')
		->setLimit($limit);
		$db->setQuery($query);
		$topscorers = $db->loadObjectList();

		foreach ($topscorers as $topscorer)
		{
			$topscorer->percentage = !empty($topscorer->no_of_lessons) ? $topscorer->completed_lessons / $topscorer->no_of_lessons * 100 : 0;
			$topscorer->uname = Factory::getUser($topscorer->user_id)->name;
			$mainUrl = 'administrator/index.php?option=com_tjlms&view=lessonreport';
			$url = $mainUrl . '&filter[coursefilter]=' . $course_id . '&filter[userfilter]=' . $topscorer->user_id;
			$url = $url . '&usedAsPopupReport=1&tmpl=component&filter[lessonfilter]=0';
			$topscorer->path = Uri::root() . $url;
		}

		return $topscorers;
	}

	/**
	 * Get popular student for dashboard based on active courses
	 *
	 * @param   INT  $course_id  course id
	 *
	 * @return  OBJECT  $popularStudent
	 */
	public function getStudentwhoLiked($course_id)
	{
		$comtjlmsHelper   = new comtjlmsHelper;

		$db    = Factory::getDBO();
		$query = $db->getQuery(true);
		$query->select('DISTINCT(l.userid)');
		$query->from('#__jlike_likes as l');
		$query->join('LEFT', '#__jlike_content AS c ON c.id = l.content_id ');
		$query->join('INNER', '#__users AS u ON u.id = l.userid ');
		$query->where('l.like > 0');
		$query->where('c.element_id =' . (int) $course_id);
		$db->setQuery($query);
		$popularStudent = $db->loadobjectlist();

		foreach ($popularStudent as $index => $enrolment_info)
		{
			$student = Factory::getUser($enrolment_info->userid);
			$enrol_userid = $enrolment_info->userid;
			$popularStudent[$index]->username = Factory::getUser($enrol_userid)->username;
			$popularStudent[$index]->avatar = $comtjlmsHelper->sociallibraryobj->getAvatar($student, 50);
			$link = '';
			$link = $profileUrl = $comtjlmsHelper->sociallibraryobj->getProfileUrl($student);

			if ($profileUrl)
			{
				if (!parse_url($profileUrl, PHP_URL_HOST))
				{
					$link = Uri::root() . substr(Route::_($comtjlmsHelper->sociallibraryobj->getProfileUrl($student)), strlen(Uri::base(true)) + 1);
				}
			}

			$popularStudent[$index]->profileurl = $link;
		}

		return $popularStudent;
	}

	/**
	 * Get activity of student for dashboard based on active courses
	 *
	 * @param   INT  $courseId  course id
	 *
	 * @return  ARRAY $yourActivities
	 *
	 * @since  1.0.0
	 */
	public function getactivity($courseId)
	{
		// Set start and end date
		$start = $this->getState('filter.begin');
		$end   = $this->getState('filter.end');

		require_once JPATH_SITE . '/components/com_tjlms/helpers/tracking.php';
		$comtjlmstrackingHelper = new comtjlmstrackingHelper;

		// Get activity for all students
		$activitydata = array();
		$activitydata['user_id'] = '';
		$activitydata['start'] = '';
		$activitydata['end'] = '';
		$activitydata['course_id'] = $courseId;

		$courseActivities = $comtjlmstrackingHelper->getactivity($activitydata);

		return $courseActivities;
	}

	/**
	 * Get revenue data
	 *
	 * @param   INT  $courseId  course id
	 *
	 * @return  ARRAY   $revenueData
	 *
	 * @since  1.0.0
	 */
	public function getrevenueData($courseId)
	{
		$comtjlmsHelper = new comtjlmsHelper;
		$data = array();
		$data['course_id'] = $courseId;
		$revenueData = $comtjlmsHelper->getrevenueData($data);

		return $revenueData;
	}
}
