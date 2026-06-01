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

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Application\CMSApplication;

use Joomla\CMS\Access\Access;
jimport('techjoomla.common');

/**
 * Tjlms course helper.
 *
 * @since  1.0.0
 */
class TjlmsCoursesHelper
{
	private $tjlmsdbhelper = '';

	private $tjlmsdbhelperObj = '';

	private $comtjlmstrackingHelper = '';

	private $comtjlmsHelperPath = '';

	private $comtjlmsHelper = '';

	private $tjlmsLessonHelper = '';

	private $techjoomlacommon = '';

	/**
	 * Method acts as a consturctor
	 *
	 * @since   1.0.0
	 */
	public function __construct()
	{
		$this->tjlmsdbhelper = JPATH_ROOT . '/components/com_tjlms/helpers/tjdbhelper.php';

		if (!class_exists('tjlmsdbhelper'))
		{
			JLoader::register('tjlmsdbhelper', $this->tjlmsdbhelper);
			JLoader::load('tjlmsdbhelper');
		}

		$this->tjlmsdbhelperObj = new tjlmsdbhelper;

		$this->comtjlmstrackingHelper = JPATH_ROOT . '/components/com_tjlms/helpers/tracking.php';

		if (!class_exists('comtjlmstrackingHelper'))
		{
			JLoader::register('comtjlmstrackingHelper', $this->comtjlmstrackingHelper);
			JLoader::load('comtjlmstrackingHelper');
		}

		$this->comtjlmstrackingHelper = new comtjlmstrackingHelper;

		$this->comtjlmsHelperPath = JPATH_ROOT . '/components/com_tjlms/helpers/main.php';

		if (!class_exists('comtjlmsHelper'))
		{
			JLoader::register('comtjlmsHelper', $this->comtjlmsHelperPath);
			JLoader::load('comtjlmsHelper');
		}

		$this->comtjlmsHelper = new comtjlmsHelper;

		$path = JPATH_SITE . '/components/com_tjlms/helpers/lesson.php';

		if (!class_exists('TjlmsLessonHelper'))
		{
			// Require_once $path;
			JLoader::register('TjlmsLessonHelper', $path);
			JLoader::load('TjlmsLessonHelper');
		}

		$this->tjlmsLessonHelper = new TjlmsLessonHelper;
		$this->techjoomlacommon = new TechjoomlaCommon;
	}

	/**
	 * Method to all hirarchy categories
	 *
	 * @param   INT     $catid      The cat id whose child categories are to be taken
	 *
	 * @param   STRING  $extension  The extension whose cats are to be taken
	 *
	 * @return  array
	 *
	 * @since   1.0.0
	 */
	public static function getCatHierarchyLink($catid, $extension = 'com_tjlms')
	{
		// GETTING PARENT CATS
		$parentCatArray = tjlmsCoursesHelper::getCatParents($catid, $extension);
		$catcount      = (int) count($parentCatArray);

		// GETTING ITEM ID
		$comtjlmsHelper = new comtjlmsHelper;

		$linkArray = $linkHtmlArray = array();

		for ($i = ($catcount - 1); $i >= 0; $i--)
		{
			// CAT LINKS HREF
			$link = $comtjlmsHelper->tjlmsRoute('index.php?option=com_tjlms&view=courses&course_cat=' . $parentCatArray[$i]["id"], false);

			$linkArray[] = $link;

			// CAT LINKS html code
			$linkHtmlArray[] = '<a href="' . $link . '">' . $parentCatArray[$i]["title"] . '</a>';
		}

		return $linkHtmlArray;
	}

	/**
	 * This function return array of parent hirarchey childcat-....>topCat
	 *
	 * @param   INT     $catid      The cat id whose child categories are to be taken
	 * @param   STRING  $extension  The extension whose cats are to be taken
	 *
	 * @return  array
	 *
	 * @since   1.0.0
	 */
	public static function getCatParents($catid, $extension = 'com_tjlms')
	{
		$parentCats = array();

		do
		{
			$category = tjlmsCoursesHelper::getCatDetail($catid, $extension);

			if (!empty($category) && !empty($category['parent_id']))
			{
				$parentCats[] = $category;

				// Shift to parent catid
				$catid = $category['parent_id'];
			}
			else
			{
				break;
			}
		}

		while (!empty($category));

		return $parentCats;
	}

	/**
	 * This function return category detail
	 *
	 * @param   INT     $catid      The cat id whose child categories are to be taken
	 *
	 * @param   STRING  $extension  The extension whose cats are to be taken
	 *
	 * @return  array | false
	 *
	 * @since   1.0.0
	 */
	public static function getCatDetail($catid, $extension = 'com_tjlms')
	{
		try
		{
			$db   = Factory::getDBO();
			$query = $db->getQuery(true);
			$query->select($db->qn(array('id','title','parent_id','path')));
			$query->from($db->qn('#__categories'));
			$query->where($db->qn('extension') . ' = ' . $db->q($extension));
			$query->where($db->qn('id') . ' = ' . $db->q((int) $catid));
			$db->setQuery($query);

			return $db->loadAssoc();
		}
		catch (Exception $e)
		{
			return false;
		}
	}

	/**
	 * Method to fetch course name from passed id
	 *
	 * @param   int  $course_id  ID of course
	 *
	 * @return  STRING
	 *
	 * @since   1.0
	 */
	public function courseName($course_id)
	{
		$db   = Factory::getDBO();

		// Add Table Path
		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjlms/tables');
		$table = Table::getInstance('Course', 'TjlmsTable', array('dbo', $db));
		$table->load(array('id' => (int) $course_id));

		return $table->title;
	}

	/**
	 * Method to fetch course details from passed id
	 *
	 * @param   int  $course_id  ID of course
	 *
	 * @return   MIXED
	 *
	 * @since   1.0
	 */
	public function getcourseInfo($course_id)
	{
		try
		{
			$db   = Factory::getDBO();
			$query = $db->getQuery(true);
			$query->select(array('c.*', $db->qn('cat.published'), $db->qn('cat.title', 'category_title'), $db->qn('cat.access', 'catAccess')));
			$query->from($db->qn('#__tjlms_courses', 'c'));
			$query->join('INNER', $db->qn('#__categories', 'cat') . ' ON (' . $db->qn('c.catid') . ' = ' . $db->qn('cat.id') . ')');
			$query->where('c.id = ' . $db->q((int) $course_id));
			$db->setQuery($query);

			$result = $db->loadObject();

			if ($result && Factory::getDate()->toSql() < $result->start_date)
			{
				$result->state = 0;
			}

			return $result;
		}
		catch (Exception $e)
		{
			return false;
		}
	}

	/**
	 * Function to get specific col of specific course
	 *
	 * @param   int  $course_id      id of course
	 * @param   ARR  $columns_array  array of teh columns
	 *
	 * @return  Object|BOOLEAN  $statusDetails
	 *
	 * @since  1.0.0
	 */
	public function getCourseColumn($course_id, $columns_array)
	{
		try
		{
			if ($course_id)
			{
				$db   = Factory::getDBO();
				$query = $db->getQuery(true);
				$query->select($columns_array);
				$query->from($db->quoteName('#__tjlms_courses'));
				$query->where($db->quoteName('id') . " = " . $db->quote((int) $course_id));
				$db->setQuery($query);
				$course = $db->loadObject();

				return $course;
			}
		}
		catch (Exception $e)
		{
			return false;
		}
	}

	/**
	 * Method to fetch subs plans assigned for course
	 *
	 * @param   int  $courseId  id of course
	 *
	 * @param   int  $userId    id of user
	 *
	 * @return  Object|BOOLEAN Sub plan object
	 *
	 * @since   1.0
	 */
	public function getCourseSubplans($courseId, $userId = 0)
	{
		try
		{
			$db   = Factory::getDBO();
			$query = $db->getQuery(true);
			$query->select('*');
			$query->from($db->quoteName('#__tjlms_subscription_plans'));
			$query->where($db->quoteName('course_id') . " = " . $db->quote((int) $courseId));

			if ($userId)
			{
				$allowedViewLevels  = Access::getAuthorisedViewLevels($userId);
				$implodedViewLevels = implode('","', $allowedViewLevels);

				$query->where('access IN ("' . $implodedViewLevels . '")');
			}

			$db->setQuery($query);

			return $db->loadobjectlist();
		}
		catch (Exception $e)
		{
			return false;
		}
	}

	/**
	 * Get all the modules/sections and their lessons of a coures
	 *
	 * @param   int  $courseId  ID of course
	 * @param   int  $oluserId  ID of course
	 *
	 * @return  OBJECT|BOOLEAN of all modules
	 *
	 * @since   1.0
	 */
	public function getCourseProgress($courseId, $oluserId)
	{
		$db   = Factory::getDBO();
		$totalLessons = $passedLessons = 0;

		if ($courseId > 0)
		{
			try
			{
				$query = $db->getQuery(true);
				$query->select(array($db->qn('l.id')));
				$query->from($db->quoteName('#__tjlms_lessons', 'l'));
				$query->join('INNER', $db->qn('#__tjlms_modules', 'm') . ' ON (' . $db->qn('l.mod_id') . ' = ' . $db->qn('m.id') . ')');
				$query->where($db->qn('l.state') . ' = 1');
				$query->where($db->qn('m.state') . ' = 1');
				$query->where($db->qn('l.course_id') . ' = ' . $db->q((int) $courseId));
				$query->where($db->qn('l.format') . " <> '' ");
				$query->where($db->quoteName('media_id') . " >  0");
				$query->where($db->quoteName('media_id') . " <>  ''");
				$query->where($db->qn('l.consider_marks') . " = 1 ");
				$db->setQuery($query);
				$lessons = $db->loadColumn();
			}
			catch (Exception $e)
			{
				return false;
			}

			$totalLessons  = count($lessons);

			if ($oluserId > 0 && $totalLessons > 0)
			{
				foreach ($lessons as $lessonId)
				{
					$statusandscore = $this->tjlmsLessonHelper->getLessonScorebyAttemptsgrading($lessonId, $oluserId);

					if ($statusandscore)
					{
						if ($statusandscore->lesson_status == 'completed' || $statusandscore->lesson_status == 'passed' )
						{
							$passedLessons++;
						}
					}
				}
			}
		}

		$courseProgress = array();
		$courseProgress['totalLessons'] = $totalLessons;
		$courseProgress['completedLessons'] = $passedLessons;
		$courseProgress['status'] = '';
		$courseProgress['completionPercent'] = 0;

		if ($totalLessons > 0 && $passedLessons > 0)
		{
			if ($totalLessons == $passedLessons)
			{
				$courseProgress['status'] = 'C';
				$courseProgress['completionPercent'] = 100;
			}
			else
			{
				$courseProgress['status'] = 'I';
				$courseProgress['completionPercent'] = round($passedLessons * 100 / $totalLessons, 2);
			}
		}

		return $courseProgress;
	}

	/**
	 * Get lesson according to condition provided
	 *
	 * @param   int  $course_id  id of course
	 * @param   int  $columns    array of columns to be fetched
	 *
	 * @return  ARRAY|BOOLEAN  lessons
	 *
	 * @since   1.0
	 */
	public function getLessonsByCourse($course_id, $columns = array("*"))
	{
		try
		{
			$db = Factory::getDBO();
			$query = $db->getQuery(true);
			$query->select($columns);
			$query->from($db->qn('#__tjlms_lessons', 'l'));
			$query->join('LEFT', $db->qn('#__tjlms_media', 'm') . ' ON (' . $db->qn('l.media_id') . ' = ' . $db->qn('m.id') . ')');
			$query->where($db->qn('course_id') . " = " . $db->q((int) $course_id));
			$query->where($db->qn('state') . " = 1");
			$db->setQuery($query);

			return $db->loadobjectList();
		}
		catch (Exception $e)
		{
			return false;
		}
	}

	/**
	 * Get lesson according to condition provided
	 *
	 * @param   int  $course_id  id of course
	 * @param   int  $condition  can be recentaly accessed or last attempted
	 * @param   int  $order      can be recentaly accessed or last attempted
	 * @param   int  $user_id    id of user
	 * @param   int  $columns    columns to be fetched
	 *
	 * @return  object|BOOLEAN lesson
	 *
	 * @since   1.0
	 */
	public function getLessonBycondition($course_id, $condition, $order, $user_id, $columns = array("l.*"))
	{
		if (!$user_id)
		{
			$user_id = Factory::getUser()->id;
		}

		try
		{
			$db = Factory::getDBO();
			$query = $db->getQuery(true);
			$query->select($db->qn('lt.lesson_id'));
			$query->select($columns);
			$query->from($db->qn('#__tjlms_lesson_track', 'lt'));
			$query->join('INNER', $db->qn('#__tjlms_lessons', 'l') . ' ON (' . $db->qn('l.id') . ' = ' . $db->qn('lt.lesson_id') . ')');
			$query->where($db->qn('l.state') . ' = 1 ');
			$query->where($db->qn('l.course_id') . ' = ' . $db->q((int) $course_id));
			$query->where($db->qn('lt.user_id') . ' = ' . $db->q((int) $user_id));

			$query->order($db->qn($condition) . " " . $order);
			$query->setlimit(1);
			$db->setQuery($query);
			$lesson = $db->loadobject();
		}
		catch (Exception $e)
		{
			return false;
		}

		if (isset($lesson->image))
		{
			require_once JPATH_ROOT . '/components/com_tjlms/models/lesson.php';
			$lessonModel = new TjlmsModelLesson;
			$lesson->image = $lessonModel->getLessonImage($lesson->id, 'media_m');
		}

		if (isset($lesson->attemptsdonebyuser))
		{
			$lesson->attemptsdonebyuser = $this->tjlmsLessonHelper->getlesson_total_attempts_done($lesson->id, $user_id);
		}

		return $lesson;
	}

	/**
	 * Function to save user enrollment
	 * Call from backend enrollment view and from frontend while enrolling
	 *
	 * @param   int    $admin_approval  admin approval 1 or 0
	 * @param   int    $enrolled_by     id of user who has enrolle the user
	 * @param   array  $post            post array
	 * @param   array  $csv             std object array
	 * @param   int    $notify_user     notify User
	 *
	 * @return  boolean  true or false
	 *
	 * @since 1.0.0
	 */
	public function enroll_user($admin_approval = 0, $enrolled_by = 0, $post = array(), $csv = 0, $notify_user = 1)
	{
		$input = Factory::getApplication()->input;
		$db = Factory::getDBO();

		if ($csv == 1)
		{
			$c_id = $post['course_id'];
		}
		else
		{
			if (!empty($post))
			{
				$c_id = $post->get('course_id', '', 'INT');
			}
			else
			{
				$c_id = $input->get('id', '', 'INT');
			}
		}

		$data = new stdclass;
		$data->course_id = $c_id;
		$data->enrolled_on_time = Factory::getDate()->toSql(true);

		if ($admin_approval == '1')
		{
			$data->state = '0';
		}
		else
		{
			$data->state = '1';
		}

		if (!empty($post))
		{
			if ($csv == 1)
			{
				$users          = $post['cid'];
				$data->enrolled_by = Factory::getUser()->id;
			}
			else
			{
				$users          = $post->get('cid', '', 'ARRAY');
				$data->enrolled_by = $post->get('enrolled_by', '', 'INT');
			}
		}
		else
		{
			$users          = array();
			$users[]         = Factory::getUser()->id;
			$data->enrolled_by = Factory::getUser()->id;
		}

		foreach ($users as $userToEnroll)
		{
			$data->user_id  = $userToEnroll;
			$data->id      = '';

			try
			{
				$query = $db->getQuery(true);
				$query->select($db->qn('id'));
				$query->from($db->qn('#__tjlms_enrolled_users', 'a'));
				$query->where($db->qn('a.course_id') . ' = ' . $db->q((int) $data->course_id));
				$query->where($db->qn('a.user_id') . ' = ' . $db->q((int) $data->user_id));
				$query->where($db->qn('a.state') . ' != -2');
				$db->setQuery($query);
				$enrollResult = $db->loadResult();
			}
			catch (Exception $e)
			{
				return false;
			}

			if (empty($enrollResult))
			{
				PluginHelper::importPlugin('tjlms');
				$result = Factory::getApplication()->triggerEvent('onBeforeCourseEnrol', array($data->course_id, $data->user_id));

				if (in_array(false, $result, true))
				{
					Factory::getApplication()->enqueueMessage(Text::_('COM_TJLMS_VIEW_COURSE_PREREQUISITE_RESTRICT_MESSAGE'), 'error');

					return false;
				}

				Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjlms/tables');
				$enrolledTable = Table::getInstance('Enrolledusers', 'TjlmsTable', array('dbo', $db));

				if (!$enrolledTable->save($data))
				{
					return false;
				}

				PluginHelper::importPlugin('system');

				// Trigger all "invitex" plugins method that renders the button/image
				Factory::getApplication()->triggerEvent('onAfterCourseEnrol', array(
																	$data->user_id,
																	$data->state,
																	$data->course_id,
																	$data->enrolled_by,
																	$notify_user
																)
									);
			}
		}

		return true;
	}

	/**
	 * Function to save individual user enrollment
	 *
	 * @param   INTEGER  $userId       userId to be enrolled
	 * @param   INTEGER  $courseId     courseId
	 * @param   INTEGER  $state        Array of status to keep
	 * @param   INTEGER  $enrolled_by  user who is enrolling
	 *
	 * @return boolean
	 *
	 * @since 1.0.0
	 */
	public function enrollUser($userId, $courseId, $state = 1, $enrolled_by = 0)
	{
		$db = Factory::getDBO();

		if (!$this->getcourseInfo($courseId))
		{
			return;
		}

		require_once JPATH_ROOT . '/components/com_tjlms/models/enrolment.php';
		$tjlmsModelEnrolment = new TjlmsModelEnrolment;

		// Check if user is already enrolled for course or not
		$enrollResult = $tjlmsModelEnrolment->checkUserEnrollment($courseId, $userId);

		if (empty($enrollResult))
		{
			PluginHelper::importPlugin('tjlms');
			$result = Factory::getApplication()->triggerEvent('onBeforeCourseEnrol', array($courseId, $userId));

			if (in_array(false, $result, true))
			{
				Factory::getApplication()->enqueueMessage(Text::_('COM_TJLMS_VIEW_COURSE_PREREQUISITE_RESTRICT_MESSAGE'), 'error');

				return false;
			}

			Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjlms/tables');
			$enrolledTable = Table::getInstance('Enrolledusers', 'TjlmsTable', array('dbo', $db));

			$data = new stdclass;
			$data->course_id = $courseId;
			$data->user_id  = $userId;
			$data->state = $state;
			$data->enrolled_by = $enrolled_by;
			$data->enrolled_on_time = Factory::getDate()->toSql(true);

			if (!$enrolledTable->save($data))
			{
				return false;
			}

			PluginHelper::importPlugin('system');

			// Trigger all "invitex" plugins method that renders the button/image
			Factory::getApplication()->triggerEvent('onAfterCourseEnrol', array(
											$userId,
											$data->state,
											$data->course_id,
											$data->enrolled_by
										)
								);

			return true;
		}

		return false;
	}

	/**
	 * Function to save user enrollment
	 * Call from backend enrollment view and from frontend while enrolling
	 *
	 * @param   array  $post  Array of post data
	 *
	 * @return  boolean|VOID
	 *
	 * @since 1.0.0
	 */
	public function assignCourseToUser($post = array())
	{
		$c_id = '';

		if (!empty($post))
		{
			$c_id = $post->get('course_id', '', 'INT');
		}

		if (!$c_id)
		{
			return false;
		}

		$db            = Factory::getDBO();
		$data          = new stdclass;
		$data->course_id  = $c_id;
		$data->start_date = date('Y-m-d H:i:s');

		$users         = $post->get('cid', '', 'ARRAY');
		$data->assign_by = $post->get('enrolled_by', '', 'INT');

		foreach ($users as $userToAssign)
		{
			$data->assign_to = $userToAssign;
			$data->id      = '';

			if (!$db->insertObject('#__tjlms_assignments', $data, 'id'))
			{
				echo $db->stderr();
			}

			PluginHelper::importPlugin('system');

			// Trigger all "invitex" plugins method that renders the button/image
			Factory::getApplication()->triggerEvent('onAfterCourseAssign', array(
																$data->assign_to,
																$data->course_id,
																$data->assign_by
															)
								);
		}

		return true;
	}

	/**
	 * Function to add student for course group
	 *
	 * @param   int  $memberId   id of user
	 * @param   int  $course_id  id of course
	 *
	 * @return  BOOLEAN if successful
	 *
	 * @since 1.0.0
	 */
	public function addMemberToGroup($memberId, $course_id)
	{
		$db        = Factory::getDBO();
		$params     = ComponentHelper::getParams('com_tjlms');
		$integration = $params->get('social_integration');

		$courseInfo = $this->getcourseInfo($course_id);

		if ($integration == 'joomla')
		{
			return false;
		}
		elseif ($integration == 'easysocial')
		{
			require_once JPATH_ROOT . '/administrator/components/com_easysocial/includes/foundry.php';

			$member          = new stdclass;
			$member->cluster_id = (int) $courseInfo[0]->group_id;
			$member->uid      = (int) $memberId;
			$member->type      = SOCIAL_TYPE_USER;
			$member->state     = SOCIAL_GROUPS_MEMBER_PUBLISHED;

			if ($memberId == $courseInfo[0]->created_by)
			{
				$member->admin = true;
				$member->owner = true;
			}
			else
			{
				$member->admin = false;
				$member->owner = false;
			}

			if (!$db->insertObject('#__social_clusters_nodes', $member, 'id'))
			{
				echo $db->stderr();

				return false;
			}
		}
		elseif ($integration == 'jomsocial')
		{
			require_once JPATH_ROOT . '/components/com_community/libraries/core.php';

			// Into the groups members table
			$member         = Table::getInstance('GroupMembers', 'CTable');
			$member->groupid  = (int) $courseInfo[0]->group_id;
			$member->memberid = (int) $memberId;

			// Creator should always be 1 as approved as they are the creator.
			$member->approved = 1;

			// @todo: Setup required permissions in the future
			$member->permissions = '1';
			$member->store();
		}
	}

	/**
	 * Function used to get the group information
	 *
	 * @param   int  $group_id  id of group
	 *
	 * @return  stdclass|BOOLEAN of group
	 *
	 * @since 1.0.0
	 */
	public function getgroupinfo($group_id)
	{
		if (!$group_id)
		{
			return false;
		}

		$params     = ComponentHelper::getParams('com_tjlms');
		$integration = $params->get('social_integration', 'joomla');

		$group_info	= new stdclass;

		if ($integration == 'joomla')
		{
			return;
		}
		elseif ($integration == 'jomsocial')
		{
			if ( !File::exists(JPATH_SITE . '/components/com_community/libraries/core.php') )
			{
				return false;
			}

			require_once JPATH_ROOT . '/components/com_community/libraries/core.php';

			$group_info->userdiscussions = $this->getJSdiscussion($group_id);

			if (!isset($group_info->userdiscussions->id))
			{
				return false;
			}

			$groupLink = 'index.php?option=com_community&view=groups&task=viewdiscussion';
			$group_info->userdiscussions_URL = CRoute::_($groupLink . 's&groupid=' . $group_id, false);

			foreach ($group_info->userdiscussions as $ud)
			{
				$ud->discussion_url = CRoute::_($groupLink . '&groupid=' . $ud->groupid . '&topicid=' . $ud->id, false);
			}
		}
		elseif ($integration == 'easysocial')
		{
			if ( !File::exists(JPATH_ROOT . '/administrator/components/com_easysocial/includes/foundry.php') )
			{
				return false;
			}

			require_once JPATH_ROOT . '/administrator/components/com_easysocial/includes/foundry.php';

			$group = Foundry::group($group_id);

			if (!isset($group->id))
			{
				return false;
			}

			$group_info->name         = $group->title;

			$group_info->userdiscussions = $this->getESdiscussion($group_id);
			$app                   = FD::table('App');
			$app->load(
						array(
								'group' => SOCIAL_TYPE_GROUP,
								'element' => 'discussions',
								'type' => SOCIAL_TYPE_APPS
							)
					);

			$group_info->userdiscussions_URL = FRoute::groups(
																array(
																	'layout' => 'item',
																	'id' => $group->getAlias(),
																	'appId' => $app->getAlias()
																)
															);

			foreach ($group_info->userdiscussions as $userdiscussion)
			{
				$userdiscussion->discussion_url = FRoute::apps(
																array(
																		'layout' => 'canvas',
																		'customView' => 'item',
																		'uid' => $group->getAlias(),
																		'type' => SOCIAL_TYPE_GROUP,
																		'id' => $app->getAlias(),
																		'discussionId' => $userdiscussion->id
																	)
															);
			}
		}

		return $group_info;
	}

	/**
	 * Function used to get the jomsocial groups
	 *
	 * @param   string  $userGroups  All groups comma separated
	 *
	 * @return  object|BOOLEAN of group
	 *
	 * @since  1.0.0
	 */
	public function getJSdiscussion($userGroups)
	{
		try
		{
			$db   = Factory::getDBO();
			$query = $db->getQuery(true);
			$query->select($db->qn(array('gd.title', 'gd.id', 'gd.groupid')));
			$query->from($db->qn('#__community_groups_discuss', 'gd'));
			$query->where($db->qn('gd.groupid') . 'IN (' . $db->q($userGroups) . ')');
			$query->order($db->quoteName('gd.created') . ' DESC');
			$query->setLimit('4');
			$db->setQuery($query);

			return $db->loadObjectlist();
		}
		catch (Exception $e)
		{
			return false;
		}
	}

	/**
	 * Function used to get the easysocial groups
	 *
	 * @param   string  $userGroups  All groups comma separated
	 *
	 * @return  object of group
	 *
	 * @since  1.0.0
	 */
	public function getESdiscussion($userGroups)
	{
		try
		{
			$db   = Factory::getDBO();
			$query = $db->getQuery(true);
			$query->select($db->qn(array('gd.title', 'gd.uid', 'gd.id')));
			$query->from($db->qn('#__social_discussions', 'gd'));
			$query->where($db->qn('gd.type') . '=' . $db->q('group'));
			$query->where($db->qn('gd.uid') . 'IN (' . $db->q($userGroups) . ')');
			$query->order($db->quoteName('gd.created') . ' DESC');
			$query->setLimit('4');
			$db->setQuery($query);

			return $db->loadObjectlist();
		}
		catch (Exception $e)
		{
			return false;
		}
	}

	/**
	 * Function used to update end date of the course
	 *
	 * @param   int  $planId        Id of a subscription plan
	 * @param   int  $enrollmentId  Id of a enrollment table
	 * @param   int  $add           Whether to add or substract end time
	 *
	 * @return  boolean  true or false
	 *
	 * @since  1.0.0
	 */
	public function updateEndTimeForCourse($planId, $enrollmentId = '', $add = 1)
	{
		$planinfo = $this->getPlanDetails($planId);
		$endTime  = '';
		$db      = Factory::getDBO();
		$res            = new stdClass;
		$res->id         = $enrollmentId;
		$res->unlimited_plan = 0;

		switch ($planinfo->time_measure)
		{
			case 'day':
				$endTime = $planinfo->duration . ' day';
				break;
			case 'week':
				$endTime = ($planinfo->duration * 7) . ' day';
				break;
			case 'month':
				$endTime = ($planinfo->duration * 30) . ' day';
				break;
			case 'year':
				$endTime = $planinfo->duration . ' year';
				break;
			case 'unlimited':
				$endTime = '10 year';
				$res->unlimited_plan = 1;
				break;
		}

		if ($endTime)
		{
			$endTime = 'now + ' . $endTime;
		}
		else
		{
			$endTime = 'now';
		}

		$res->end_time      = Factory::getDate($endTime)->toSql(true);
		$res->modified_time = Factory::getDate()->toSql(true);

		// Insert entry if no enrolllment ID.
		if ($enrollmentId == '')
		{
			$res->enrolled_on_time = Factory::getDate('now', 'UTC', true);
			$res->enrolled_on_time = $res->enrolled_on_time->toSql(true);
			$res->state = 1;
		}
		else
		{
			$checkEnrollmentHistory = $this->getEnrollmentHistory($enrollmentId);

			if ($checkEnrollmentHistory['end_date'])
			{
				$res->end_time = $checkEnrollmentHistory['end_date'];
			}
		}

		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjlms/tables');
		$enrolledTable = Table::getInstance('Enrolledusers', 'TjlmsTable', array('dbo', $db));

		if ($enrolledTable->save($res))
		{
			return true;
		}

		return false;
	}

	/**
	 * Function used to get plan details
	 *
	 * @param   int  $planId  Id of a subscription plan
	 *
	 * @return   object for plan details
	 *
	 * @since 1.0.0
	 */
	public function getPlanDetails($planId)
	{
		if (empty($planId))
		{
			return false;
		}

		try
		{
			$db   = Factory::getDBO();
			$query = $db->getQuery(true);
			$query->select('*');
			$query->from($db->qn('#__tjlms_subscription_plans'));
			$query->where($db->qn('id') . '=' . $db->q((int) $planId));
			$db->setQuery($query);

			return $db->loadObject();
		}
		catch (Exception $e)
		{
			return false;
		}
	}

	/**
	 * Function used to get friends to recommend course
	 *
	 * @param   array   $allFriends  All friends
	 * @param   int     $courseId    Id of course
	 * @param   string  $element     Element of course
	 *
	 * @return   object for friends
	 *
	 * @since 1.0.0
	 */
	public function peopleToRecommend($allFriends, $courseId, $element)
	{
		$comtjlmsHelper = new comtjlmsHelper;
		$enroledUsers   = $comtjlmsHelper->getCourseEnrolledUsers($courseId);
		$oluser_id     = Factory::getUser()->id;

		// Ignore student already enrolled in that course
		foreach ($enroledUsers as $enrolledUser)
		{
			if (array_key_exists($enrolledUser->user_id, $allFriends))
			{
				unset($allFriends[$enrolledUser->user_id]);
			}
		}

		// Get all user which are already recommended by this user.
		try
		{
			$db   = Factory::getDBO();
			$query = $db->getQuery(true);
			$query->select('r.*');
			$query->from($db->qn('#__jlike_recommend', 'r'));
			$query->join('INNER', $db->qn('#__jlike_content', 'c') . ' ON (' . $db->qn('c.id') . ' = ' . $db->qn('r.content_id') . ')');
			$query->where($db->qn('r.recommend_by') . '=' . $db->q((int) $oluser_id));
			$query->where($db->qn('c.element_id') . '=' . $db->q((int) $courseId));
			$db->setQuery($query);

			$recommendedUsers = $db->loadObjectlist();
		}
		catch (Exception $e)
		{
			return false;
		}

		// Ignore student already enrolled in that course
		foreach ($recommendedUsers as $recommendedUser)
		{
			if (array_key_exists($recommendedUser->recommend_to, $allFriends))
			{
				unset($allFriends[$recommendedUser->recommend_to]);
			}
		}

		return $allFriends;
	}

	/**
	 * Function is used to transfer the course images according to storage specified
	 *
	 * @param   Array   $course     An optional LIMIT field.
	 *
	 * @param   STRING  $imageSize  specifies 'S_' or 'M_' image provide
	 *
	 * @return STRING image URL
	 *
	 * @since   1.0.0
	 */
	public function getCourseImage($course, $imageSize)
	{
		require_once JPATH_ROOT . '/components/com_tjlms/libraries/storage.php';
		$Tjstorage = new Tjstorage;

		// Get image to be shown for course
		$tjlmsparams     = ComponentHelper::getParams('com_tjlms');
		$courseImgPath   = $tjlmsparams->get('course_image_upload_path');
		$courseDefaultImg = Uri::root() . 'media/com_tjlms/images/default/course.png';

		// For course images that are stored in a remote location, we should return the proper path.
		// If not it is stored locally.
		if (!empty($course['image']) && $course['storage'] != 'invalid')
		{
			$storage   = $Tjstorage->getStorage($course['storage']);
			$imageToUse = $storage->getURI($courseImgPath . $imageSize . $course['image']);

			if ($course['storage'] == 'local')
			{
				if (!File::exists(JPATH_SITE . '/' . $courseImgPath . $imageSize . $course['image']))
				{
					$imageToUse = $courseDefaultImg;
				}
			}
		}
		else
		{
			$imageToUse = $courseDefaultImg;
		}

		return $imageToUse;
	}

	/**
	 * Function is used to transfer the course images according to storage specified
	 *
	 * @param   OBJ     $course  An optional LIMIT field.
	 * @param   STRING  $column  Column to fetch.
	 *
	 * @return  STRING  Cat column
	 *
	 * @since   1.0.0
	 */
	public function getCourseCat($course, $column)
	{
		$db = Factory::getDbo();
		$category = Table::getInstance('Category', 'JTable', array('dbo', $db));
		$category->load(array('id' => (int) $course->catid, 'extension' => 'com_tjlms'));

		return $category->$column;
	}

	/**
	 * Function is used to get Lowest price of the course
	 *
	 * @param   OBJ  $course  Course.
	 *
	 * @return  INT  PRICE
	 *
	 * @since   1.0.0
	 *
	 * @deprecated  1.4.0  This function will be removed and replacements will be provided in course model
	 */
	public function getCourseLowestPrice($course)
	{
		$course_sub_plans = $this->getCourseSubplans($course->id);
		$price = '';

		if (!empty($course_sub_plans))
		{
			$temp_price = $course_sub_plans[0]->price;

			if (is_array($course_sub_plans) && count($course_sub_plans) > 1)
			{
				foreach ($course_sub_plans as $index => $subarr)
				{
					if ($temp_price > $subarr->price)
					{
						$temp_price = $subarr->price;
					}
				}
			}

			$price = $temp_price;
		}

		return $price;
	}

	/**
	 * function used to get Quiz resume support.
	 *
	 * @param   int  $lesson_id  Lesson ID
	 *
	 * @return  BOOLEAN|STRING  Quiz resume support
	 *
	 * @since 1.0.0
	 */
	public function getQuizResumeAllowd($lesson_id)
	{
		try
		{
			$db = Factory::getDbo();
			$query = $db->getQuery(true);
			$query->select($db->qn('q.resume'));
			$query->from($db->qn('#__tmt_tests', 'q'));
			$query->join('INNER', $db->qn('#__tjlms_tmtquiz', 't') . ' ON (' . $db->qn('t.test_id') . ' = ' . $db->qn('q.id') . ')');
			$query->where($db->qn('t.lesson_id') . ' = ' . $db->q((int) $lesson_id));
			$db->setQuery($query);

			return $db->loadResult();
		}
		catch (Exception $e)
		{
			return false;
		}
	}

	/**
	 * Get count of number of lessons presnt in the course and number of lessons completed by user
	 *
	 * @param   int  $courseId  id of course
	 * @param   int  $userId    id of course
	 *
	 * @return  OBJECT|BOOLEAN lesson object
	 *
	 * @since   1.0
	 */
	public function gettotalAndcompletedlessons($courseId, $userId)
	{
		$progressData = $this->getCourseProgress($courseId, $userId);

		return $progressData;
	}

	/**
	 * Delete all course tracks for the course
	 *
	 * @param   ARRAY  $cid  array of course ids
	 *
	 * @return  BOOLEAN
	 *
	 * @since  1.0.0
	 */
	public function deleteCourseTracks($cid)
	{
		try
		{
			$db = Factory::getDbo();
			$cidString = implode(',', $db->q($cid));
			$query = $db->getQuery(true);
			$conditions = array(
				$db->qn('course_id') . ' IN (' . $cidString . ')'
			);
			$query->delete($db->qn('#__tjlms_course_track'));
			$query->where($conditions);
			$db->setQuery($query);

			return $db->execute();
		}
		catch (Exception $e)
		{
			return false;
		}
	}

	/**
	 * Delete all lesson tracks related to selected course lessons
	 *
	 * @param   ARRAY  $lessonIds  array of lesson ids
	 *
	 * @return  BOOLEAN
	 *
	 * @since  1.0.0
	 */
	public function deleteLessonTracks($lessonIds)
	{
		try
		{
			$db = Factory::getDbo();
			$lessonIdsString = implode(',', $db->q($lessonIds));
			$query = $db->getQuery(true);
			$conditions = array(
				$db->quoteName('lesson_id') . ' IN (' . $lessonIdsString . ')'
			);
			$query->delete($db->quoteName('#__tjlms_lesson_track'));
			$query->where($conditions);
			$db->setQuery($query);

			return $db->execute();
		}
		catch (Exception $e)
		{
			return false;
		}
	}

	/**
	 * Delete all scorm lesson data tracks for the lessons
	 *
	 * @param   ARRAY  $lessonIds  array of lesson ids
	 *
	 * @return  void|BOOLEAN
	 *
	 * @since  1.0.0
	 */
	public function deleteScormData($lessonIds)
	{
		try
		{
			$db = Factory::getDbo();
			$query = $db->getQuery(true);
			$query->select(array($db->qn('sc.id', 'scorm_id'), $db->qn('scos.id', 'scoes_id')));
			$query->from($db->qn('#__tjlms_scorm', 'sc'));
			$query->join('INNER', $db->qn('#__tjlms_scorm_scoes', 'scos') . ' ON (' . $db->qn('scos.scorm_id') . ' = ' . $db->qn('sc.id') . ')');
			$query->where($db->qn('sc.lesson_id') . ' = ' . $db->q((int) $lessonIds));
			$db->setQuery($query);
			$lessonsInfo = $db->loadObjectList();

			if ($lessonsInfo)
			{
				$scoesIds = $scormIds = array();

				foreach ($lessonsInfo as $lessonInfo)
				{
					$scormIds[] = $lessonInfo->scorm_id;
					$scoesIds[] = $lessonInfo->scoes_id;
				}

				// Delete from SCORM TABLE
				$query      = $db->getQuery(true);
				$conditions = array(
					$db->qn('lesson_id') . ' = ' . $db->q((int) $lessonIds),
				);

				$query->delete($db->qn('#__tjlms_scorm'));
				$query->where($conditions);

				$db->setQuery($query);
				$db->execute();

				// Delete from scoes tables
				$scormIdsString = implode(',', $db->q($scormIds));

				// Entries from Table #_tjlms_scorm_scoes deleted
				$query      = $db->getQuery(true);
				$conditions_scormid = array(
					$db->qn('scorm_id') . ' IN (' . $scormIdsString . ')'
				);

				$query->delete($db->qn('#__tjlms_scorm_scoes'));
				$query->where($conditions_scormid);

				$db->setQuery($query);
				$db->execute();

				// Delete Sceos table data for this scorm
				$this->deleteScormScoesData($scoesIds);
			}
		}
		catch (Exception $e)
		{
			return false;
		}
	}

	/**
	 * Delete all scorm scoes data tracks for the lessons
	 *
	 * @param   ARRAY  $scoesIds  array of scoes Ids
	 *
	 * @return  void|BOOLEAN
	 *
	 * @since  1.0.0
	 */
	public function deleteScormScoesData($scoesIds)
	{
		try
		{
			$db = Factory::getDbo();

			// Delete from scoes related tables
			$scoesIdsString = implode(',', $db->quote($scoesIds));

			// Entries from Table #_tjlms_scorm_scoes_data deleted
			$query      = $db->getQuery(true);
			$conditions_scoesid = array(
				$db->quoteName('sco_id') . ' IN (' . $scoesIdsString . ')'
			);

			$query->delete($db->quoteName('#__tjlms_scorm_scoes_data'));
			$query->where($conditions_scoesid);

			$db->setQuery($query);
			$db->execute();

			// Entries from  Table #_tjlms_scorm_scoes_track deleted
			$query      = $db->getQuery(true);
			$query->delete($db->quoteName('#__tjlms_scorm_scoes_track'));
			$query->where($conditions_scoesid);

			// $query->where($conditions_scormid);
			$db->setQuery($query);
			$scormtrack = $db->execute();

			// Table #_tjlms_scorm_seq_mapinfo
			$query      = $db->getQuery(true);
			$query->delete($db->quoteName('#__tjlms_scorm_seq_mapinfo'));
			$query->where($conditions_scoesid);
			$db->setQuery($query);
			$db->execute();

			// Table #_tjlms_scorm_seq_objective
			$query      = $db->getQuery(true);
			$query->delete($db->quoteName('#__tjlms_scorm_seq_objective'));
			$query->where($conditions_scoesid);
			$db->setQuery($query);
			$db->execute();

			// Table #_tjlms_scorm_seq_rolluprule
			$query      = $db->getQuery(true);
			$query->delete($db->quoteName('#__tjlms_scorm_seq_rolluprule'));
			$query->where($conditions_scoesid);
			$db->setQuery($query);
			$db->execute();

			// Table #_tjlms_scorm_seq_rolluprulecond
			$query      = $db->getQuery(true);
			$query->delete($db->quoteName('#__tjlms_scorm_seq_rolluprulecond'));
			$query->where($conditions_scoesid);
			$db->setQuery($query);
			$db->execute();

			// Table #_tjlms_scorm_seq_rulecond
			$query      = $db->getQuery(true);
			$query->delete($db->quoteName('#__tjlms_scorm_seq_rulecond'));
			$query->where($conditions_scoesid);
			$db->setQuery($query);
			$db->execute();

			// Table #_tjlms_scorm_seq_ruleconds
			$query      = $db->getQuery(true);
			$query->delete($db->quoteName('#__tjlms_scorm_seq_ruleconds'));
			$query->where($conditions_scoesid);
			$db->setQuery($query);
			$db->execute();
		}
		catch (Exception $e)
		{
			return false;
		}
	}

	/**
	 * Delete all Course Related Data for user
	 *
	 * @param   INT  $UserId  array of lesson ids
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	public function deleteUserCourseRelatedData($UserId)
	{
		// Delete all enrollments of user with respect to course
		$this->deleteEnrollementOfUser($UserId);

		// Delete all lessonstracks with respect to course
		$this->deleteLessonTracksForUser($UserId);

		// Delete all orders with respect to this user
		$this->deleteOrdersForUser($UserId);

		// Delete all orders with respect to this user
		$this->deleteTjlmsUser($UserId);

		// Delete all activities with respect to this user
		$this->deleteActivitiesForUser($UserId);

		// Delete all coursetrack with respect to this user
		$this->deleteCourseTrackForUser($UserId);
	}

	/**
	 * Delete all enrollment of user for course
	 *
	 * @param   INT  $UserId  user_id
	 *
	 * @return  BOOLEAN
	 *
	 * @since  1.0.0
	 */
	public function deleteEnrollementOfUser($UserId)
	{
		try
		{
			$db = Factory::getDbo();
			$query      = $db->getQuery(true);
			$conditions = array(
				$db->quoteName('user_id') . ' = ' . $db->quoteName((int) $UserId)
			);
			$query->delete($db->quoteName('#__tjlms_enrolled_users'));
			$query->where($conditions);
			$db->setQuery($query);

			return $db->execute();
		}
		catch (Exception $e)
		{
			return false;
		}
	}

	/**
	 * Delete all lessonstracks with respect to user
	 *
	 * @param   INT  $UserId  user_id
	 *
	 * @return  BOOLEAN
	 *
	 * @since  1.0.0
	 */
	public function deleteLessonTracksForUser($UserId)
	{
		try
		{
			$db  = Factory::getDbo();
			$query      = $db->getQuery(true);
			$conditions = array(
				$db->quoteName('user_id') . ' = ' . $db->quote((int) $UserId)
			);
			$query->delete($db->quoteName('#__tjlms_lesson_track'));
			$query->where($conditions);
			$db->setQuery($query);

			return $db->execute();
		}
		catch (Exception $e)
		{
			return false;
		}
	}

	/**
	 * Delete all lessonstracks with respect to user
	 *
	 * @param   INT  $UserId  user_id
	 *
	 * @return  BOOLEAN
	 *
	 * @since  1.0.0
	 */
	public function deleteTjlmsUser($UserId)
	{
		try
		{
			$db  = Factory::getDbo();
			$query      = $db->getQuery(true);
			$conditions = array(
				$db->quoteName('user_id') . ' = ' . $db->quote((int) $UserId)
			);
			$query->delete($db->quoteName('#__tjlms_users'));
			$query->where($conditions);
			$db->setQuery($query);

			return $db->execute();
		}
		catch (Exception $e)
		{
			return false;
		}
	}

	/**
	 * Delete all orders with respect to this user
	 *
	 * @param   INT  $UserId  user_id
	 *
	 * @return  BOOLEAN
	 *
	 * @since  1.0.0
	 */
	public function deleteOrdersForUser($UserId)
	{
		try
		{
			$db        = Factory::getDbo();
			$query      = $db->getQuery(true);
			$conditions = array(
				$db->quoteName('user_id') . ' = ' . $db->quote((int) $UserId)
			);
			$query->delete($db->quoteName('#__tjlms_orders'));
			$query->where($conditions);
			$db->setQuery($query);

			return $db->execute();
		}
		catch (Exception $e)
		{
			return false;
		}
	}

	/**
	 * Delete all activities with respect to this user
	 *
	 * @param   INT  $UserId  user_id
	 *
	 * @return  BOOLEAN
	 *
	 * @since  1.0.0
	 */
	public function deleteActivitiesForUser($UserId)
	{
		try
		{
			$db        = Factory::getDbo();
			$query      = $db->getQuery(true);
			$conditions = array(
				$db->quoteName('actor_id') . ' = ' . $db->quote($UserId)
			);
			$query->delete($db->quoteName('#__tjlms_activities'));
			$query->where($conditions);
			$db->setQuery($query);
			$result = $db->execute();

			return $result;
		}
		catch (Exception $e)
		{
			return false;
		}
	}

	/**
	 * Delete all coursetrack with respect to this user
	 *
	 * @param   INT  $UserId  user_id
	 *
	 * @return  BOOLEAN
	 *
	 * @since  1.0.0
	 */
	public function deleteCourseTrackForUser($UserId)
	{
		try
		{
			$db = Factory::getDbo();
			$query = $db->getQuery(true);
			$conditions = array(
				$db->quoteName('user_id') . ' = ' . $db->quote((int) $UserId)
			);
			$query->delete($db->quoteName('#__tjlms_course_track'));
			$query->where($conditions);
			$db->setQuery($query);

			return $db->execute();
		}
		catch (Exception $e)
		{
			return false;
		}
	}

	/**
	 * Function to add enrollment entry
	 *
	 * @param   INT  $userid       User ID
	 * @param   INT  $course_id    Course ID
	 * @param   INT  $orderStatus  Order status
	 *
	 * @return  INT|STRING|BOOLEAN  Table ID
	 *
	 * @since  1.0.0
	 */
	public function addEnrolmentEntry($userid, $course_id, $orderStatus = 'P')
	{
		$params = ComponentHelper::getParams('com_tjlms');
		$approvalForPaidCourses = $params->get('paid_course_admin_approval', '0', 'INT');

		PluginHelper::importPlugin('system');
		PluginHelper::importPlugin('tjlms');

		if ($orderStatus == 'C')
		{
			$result = Factory::getApplication()->triggerEvent('onBeforeCourseEnrol', array($course_id, $userid));

			if (in_array(false, $result, true))
			{
				Factory::getApplication()->enqueueMessage(Text::_('COM_TJLMS_VIEW_COURSE_PREREQUISITE_RESTRICT_MESSAGE'), 'error');

				return false;
			}

			$db = Factory::getDbo();
			$query = $db->getQuery(true);
			$query->select('*');
			$query->from($db->qn('#__tjlms_enrolled_users', 'c'));
			$query->where($db->qn('c.course_id') . ' = ' . $db->q((int) $course_id));
			$query->where($db->qn('c.user_id') . ' = ' . $db->q((int) $userid));
			$db->setQuery($query);
			$enrollmentData = $db->loadObject();

			$res                   = new stdClass;
			$res->user_id          = $userid;
			$res->course_id        = $course_id;
			$res->modified_time    = Factory::getDate()->toSql(true);
			$res->enrolled_by      = $userid;
			$status                = 1;

			if ($approvalForPaidCourses == 1)
			{
				$status = 0;
				Factory::getApplication()->triggerEvent('onAddCourseTrackEntry', array(
																$course_id,
																$userid
															)
								);
			}

			$res->state = $status;

			if (!isset($enrollmentData->id))
			{
				$res->id               = '';
				$res->enrolled_on_time = $res->modified_time;
				$res->end_time = '0000-00-00 00:00:00';
				$res->unlimited_plan = 0;
				$res->before_expiry_mail = 0;
				$res->after_expiry_mail = 0;
				$res->params = 0;
			}
			else
			{
				$res->id = $enrollmentData->id;
				$res->before_expiry_mail = 0;
				$res->after_expiry_mail = 0;
			}

			Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjlms/tables');
			$enrolledTable = Table::getInstance('Enrolledusers', 'TjlmsTable', array('dbo', $db));

			if (!$enrolledTable->save($res))
			{
				return false;
			}

			if ($status == 1)
			{
				Factory::getApplication()->triggerEvent('onAfterCourseEnrol', array(
																$userid,
																$status,
																$course_id,
																$userid
															)
								);
			}

			return $enrolledTable->id;
		}

		return false;
	}

	/**
	 * Function to add enrollment history
	 *
	 * @param   INT  $orderId       Order ID
	 * @param   INT  $enrollmentId  Enrollment ID
	 *
	 * @return  BOOLEAN|STRING  Table ID
	 *
	 * @since  1.0.0
	 */
	public function addEnrolmentHistory($orderId, $enrollmentId)
	{
		// Get a db connection.
		$db = Factory::getDbo();

		if ($enrollmentId)
		{
			$coursePlanId = $this->getCoursePlanId($orderId);

			$planinfo  = $this->getPlanDetails($coursePlanId);
			$endTime   = '';

			switch ($planinfo->time_measure)
			{
				case 'day':
					$endTime = $planinfo->duration . ' day';
					break;
				case 'week':
					$endTime = ($planinfo->duration * 7) . ' day';
					break;
				case 'month':
					$endTime = ($planinfo->duration * 30) . ' day';
					break;
				case 'year':
					$endTime = $planinfo->duration . ' year';
					break;
				case 'unlimited':
					$endTime = '10 year';
					break;
			}

			$query = $db->getQuery(true);
			$query->select('count(*)');
			$query->from($db->qn('#__tjlms_enrolled_users_history', 'c'));
			$query->where($db->qn('c.enrollment_id') . ' = ' . $db->q((int) $enrollmentId));
			$db->setQuery($query);
			$enrollmentHistoryCount = $db->loadResult();

			$res                = new stdClass;
			$res->id            = '';
			$res->enrollment_id = (int) $enrollmentId;
			$res->order_id      = (int) $orderId;
			$res->created_date  = Factory::getDate()->toSql(true);

			if ($enrollmentHistoryCount)
			{
				$enrollmentHistory = $this->getEnrollmentHistory((int) $enrollmentId);

				if (new DateTime($res->created_date) < new DateTime($enrollmentHistory['end_date']))
				{
					$startTime 		 = strtotime($enrollmentHistory['end_date']);
					$res->start_date = Factory::getDate($startTime)->toSql(true);
					$endTime  		 = strtotime($res->start_date . ' + ' . $endTime);
					$res->end_date   = Factory::getDate($endTime)->toSql(true);
				}
				else
				{
					$res->start_date = $res->created_date;
					$endTime  		 = $res->start_date . ' + ' . $endTime;
					$res->end_date 	 = Factory::getDate($endTime)->toSql(true);
				}
			}
			else
			{
				$res->start_date = $res->created_date;
				$endTime  		 = $res->start_date . ' + ' . $endTime;
				$res->end_date 	 = Factory::getDate($endTime)->toSql(true);
			}

			try
			{
				$db->insertObject('#__tjlms_enrolled_users_history', $res, 'id');
			}
			catch (Exception $e)
			{
				return false;
			}

			return $res->id;
		}
	}

	/**
	 * Function to get course plan id
	 *
	 * @param   INT  $orderId  Order ID
	 *
	 * @return  INT  Table ID
	 *
	 * @since  1.0.0
	 */
	public function getCoursePlanId($orderId)
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true);

		$query->select($db->qn('plan_id'));
		$query->from($db->qn('#__tjlms_order_items', 'c'));
		$query->where($db->qn('c.order_id') . ' = ' . (int) $orderId);
		$db->setQuery($query);

		return $db->loadResult();
	}

	/**
	 * Function to get enrollment history
	 *
	 * @param   INT  $enrollmentId  Enrollment ID
	 *
	 * @return  BOOLEAN|ARRAY  Table data
	 *
	 * @since  1.0.0
	 */
	public function getEnrollmentHistory($enrollmentId)
	{
		if (!$enrollmentId)
		{
			return false;
		}

		try
		{
			$db = Factory::getDbo();
			$query = $db->getQuery(true);
			$query->select('*');
			$query->from($db->qn('#__tjlms_enrolled_users_history', 'c'));
			$query->where($db->qn('c.enrollment_id') . ' = ' . $db->q((int) $enrollmentId));
			$query->order($db->quoteName('c.id') . ' DESC');
			$db->setQuery($query);

			return $db->loadAssoc();
		}
		catch (Exception $e)
		{
			return false;
		}
	}

	/**
	 * Function for building hierarchy @Amol T.
	 *
	 * @param   array  $config  Options for hierachy.
	 *
	 * @return  STRING
	 */

	public function build_hierarchy($config)
	{
		$string = 'global';

		foreach ($config as  $key => $value)
		{
			$string .= "." . $key . $value;
		}

		return $string;
	}

	/**
	 * Function to get Item id
	 *
	 * @param   INTEGER  $courseId  Id of the course
	 *
	 * @return  INT  Item id
	 *
	 * @since  1.0.0
	 */
	public function getCourseItemid($courseId)
	{
		$app  = CMSApplication::getInstance('site');
		$menu = $app->getMenu();

		$itemid = 0;

		if ($courseId)
		{
			/*Get the itemid of the menu which is pointed to individual course URL*/
			$menuItem = $menu->getItems('link', 'index.php?option=com_tjlms&view=course&id=' . $courseId, true);

			$active = $app->getMenu()->getActive();

			if ($active)
			{
				$currentLink = $active->link;

				// If the current view is the active item and an course view for this course, then the menu item params take priority
				if (strpos($currentLink, 'view=course') && (strpos($currentLink, '&id=' . $courseId)))
				{
					if (isset($active->query['layout']))
					{
						$menuItem = $menu->getItems('link', 'index.php?option=com_tjlms&view=course&layout=' . $active->query['layout'] . '&id=' . $courseId, true);
					}
				}
			}

			if (!empty($menuItem))
			{
				return $menuItem->id;
			}

			/*Get the itemid of the menu which is pointed to course category URL*/
			$courseInfo = $this->getcourseInfo($courseId);

			if (is_object($courseInfo))
			{
				if ($courseInfo->published == 1)
				{
					$menuItems = $menu->getItems('link', 'index.php?option=com_tjlms&view=category&layout=default');

					if (!empty($menuItems))
					{
						foreach ($menuItems as $menuItem)
						{
							if ($menuItem->params->get('defaultCatId') == $courseInfo->catid)
							{
								return $menuItem->id;
							}
						}

						foreach ($menuItems as $menuItem)
						{
							$cat_details = $this->getCatDetail($courseInfo->catid);

							if ($menuItem->params->get('defaultCatId') == $cat_details['parent_id'])
							{
								return $menuItem->id;
							}
						}
					}
				}
			}

			/*Get the itemid of the menu which is pointed to courses URL*/
			$coursesUrl = 'index.php?option=com_tjlms&view=courses&courses_to_show=all';
			$menuItem = $menu->getItems('link', $coursesUrl, true);

			if ($menuItem)
			{
				return $menuItem->id;
			}
		}

		return $itemid;
	}

	/**
	 * This fucntion is used to delete a enrollment history for a user-course
	 *
	 * @param   INTEGER  $order_id       Order ID
	 * @param   INTEGER  $enrollment_id  Enrollment ID
	 *
	 * @return  BOOLEAN
	 *
	 * @since  1.0.2
	 */
	public function deleteEnrolmentHistory($order_id, $enrollment_id)
	{
		// Add Table Path
		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjlms/tables');
		$db   = Factory::getDBO();

		$enrollmentHistoryTbl = Table::getInstance('enrollmentHistory', 'TjlmsTable', array('dbo', $db));
		$enrollmentHistoryTbl->load(array('order_id' => (int) $order_id, 'enrollment_id' => (int) $enrollment_id));

		if ($enrollmentHistoryTbl->id)
		{
			$enrollmentHistoryTbl->delete();
		}

		return true;
	}

	/**
	 * Method to fetch remaining subs days of assigned for course
	 *
	 * @param   int  $courseId  id of course
	 *
	 * @return  OBJECT|BOOLEAN course Sub plan object
	 *
	 * @since   1.0
	 */
/*	public function getCourseRemainingDays($courseId)
	{
		try
		{
			$db   = Factory::getDBO();
			$query = $db->getQuery(true);
			$userId = $user = Factory::getUser();
			$query->select($db->quoteName(array('end_time', 'unlimited_plan')));
			$query->from($db->quoteName('#__tjlms_enrolled_users'));
			$query->where($db->quoteName('course_id') . " = " . $db->quote((int) $courseId));
			$query->where($db->quoteName('user_id') . " = " . $db->quote((int) $userId->id));
			$db->setQuery($query);

			return $db->loadobject();
		}
		catch (Exception $e)
		{
			return false;
		}
	}*/

	/**
	 * Method to fetch order details of course
	 *
	 * @param   int  $courseId  id of course
	 * @param   int  $Id        orderid or userId of course
	 * @param   int  $uoId      define $Id values
	 *
	 * @return  OBJECT|FALSE course Order details object
	 *
	 * @since   1.0
	 */
	public function getCourseOrderDetails($courseId, $Id, $uoId)
	{
		try
		{
			$db  = Factory::getDBO();
			$query = $db->getQuery(true);
			$query->select($db->quoteName(array('id', 'name', 'email', 'user_id', 'processor')));
			$query->from($db->quoteName('#__tjlms_orders'));
			$query->where($db->quoteName('course_id') . " = " . $db->quote((int) $courseId));

			if ($uoId == 'userId')
			{
				$query->where($db->quoteName('user_id') . " = " . $db->quote((int) $Id));
			}
			elseif ($uoId == 'orderId')
			{
				$query->where($db->quoteName('id') . " = " . $db->quote((int) $Id));
			}

			$db->setQuery($query);

			return $db->loadObject();
		}
		catch (Exception $e)
		{
			return false;
		}
	}

	/**
	 * Get lessons according to format provided with course ID
	 *
	 * @param   int     $course_id  id of course
	 * @param   STRING  $format     format of lesson
	 * @param   ARRAY   $columns    array of columns to be fetched
	 *
	 * @return  ARRAY|BOOLEAN list have the provided format
	 *
	 * @since   1.0
	 */
	public function getSameFormatLessonsByCourse($course_id, $format, $columns = 'l.id')
	{
		try
		{
			$db = Factory::getDBO();
			$query = $db->getQuery(true);
			$query->select($columns);
			$query->from($db->qn('#__tjlms_lessons') . ' as l ');
			$query->join('LEFT', $db->qn('#__tjlms_media', 'm') . ' ON (' . $db->qn('l.media_id') . ' = ' . $db->qn('m.id') . ')');
			$query->where($db->qn('course_id') . " = " . $course_id);
			$query->where($db->qn('state') . " = 1");
			$query->where($db->qn('l.format') . " = " . $db->q($format));
			$db->setQuery($query);

			return array_filter($db->loadColumn());
		}
		catch (Exception $e)
		{
			return false;
		}
	}

	/**
	 * Function to update the enrolled user column
	 *
	 * @param   int    $courseId    course Id
	 * @param   int    $userId      user Id
	 * @param   json   $userParams  array of event ids
	 * @param   array  $col         column name
	 *
	 * @return  boolean  true or false
	 *
	 * @since  1.0.0
	 */
	public function updateCourseEnrolledParams($courseId, $userId, $userParams, $col)
	{
		try
		{
			$db  = Factory::getDBO();
			$query = $db->getQuery(true);
			$query->update($db->quoteName('#__tjlms_enrolled_users', 'eu'));
			$query->set($db->quoteName($col) . " = " . $db->quote($userParams));
			$query->where($db->quoteName('eu.course_id') . " = " . $db->quote((int) $courseId));
			$query->where($db->quoteName('eu.user_id') . " = " . $db->quote((int) $userId));
			$db->setQuery($query);

			return $db->execute();
		}
		catch (Exception $e)
		{
			return false;
		}
	}
}
