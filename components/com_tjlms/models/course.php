<?php
/**
 * @package     Shika
 * @subpackage  com_tjlms
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;
use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Helper\TagsHelper;
jimport('techjoomla.common');

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\User;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Access\Access;
use Joomla\CMS\Language\Text;
use Joomla\Registry\Registry;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Categories\Categories;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

JLoader::import("/techjoomla/media/storage/local", JPATH_LIBRARIES);
JLoader::import('components.com_tjcertificate.includes.tjcertificate', JPATH_ADMINISTRATOR);

/**
 * Methods supporting course details view.
 *
 * @since  1.0.0
 */
class TjlmsModelcourse extends AdminModel
{
	public $defaultLessonImage = 'media/com_tjlms/images/default/lesson.png';

	public $item;

	public $client = 'com_tjlms.course';

	/**
	 * constructor function
	 *
	 * @since  1.0
	 */
	public function __construct()
	{
		$this->tjlmsdbhelperObj       = new tjlmsdbhelper;
		$this->comtjlmstrackingHelper = new comtjlmstrackingHelper;
		$this->comtjlmsHelper         = new comtjlmsHelper;
		$this->tjlmsCoursesHelper     = new tjlmsCoursesHelper;

		$path = JPATH_COMPONENT . '/helpers/' . 'lesson.php';

		if (!class_exists('TjlmsLessonHelper'))
		{
			// Require_once $path;
			JLoader::register('TjlmsLessonHelper', $path);
			JLoader::load('TjlmsLessonHelper');
		}

		$this->tjlmsLessonHelper = new TjlmsLessonHelper;

		include_once  JPATH_ADMINISTRATOR . '/components/com_tjlms/includes/tjlms.php';

		$path = JPATH_SITE . '/components/com_tjlms/libraries/scorm/scormhelper.php';

		if (!class_exists('comtjlmsScormHelper'))
		{
			// Require_once $path;
			JLoader::register('comtjlmsScormHelper', $path);
			JLoader::load('comtjlmsScormHelper');
		}

		$this->comtjlmsScormHelper = new ComtjlmsScormHelper;
		$this->techjoomlacommon    = new TechjoomlaCommon;

		parent::__construct();
	}

	/**
	 * Abstract method for getting the form from the model.
	 *
	 * @param   array    $data      Data for the form.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  \JForm|boolean  A \JForm object on success, false on failure
	 *
	 * @since   1.3.30
	 */
	public function getForm($data = array(), $loadData = true)
	{
		$form = $this->loadForm($this->client, 'course', array('control' => 'jform', 'load_data' => $loadData));

		if (empty($form))
		{
			return false;
		}

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return    mixed    The data for the form.
	 *
	 * @since    1.3.30
	 */
	public function loadFormData()
	{
		// Check the session for previously entered form data.
		$data = Factory::getApplication()->getUserState('com_tjlms.edit.course.data', array());

		if (empty($data))
		{
			$data = $this->getItem();
		}

		return $data;
	}

	/**
	 * Function to get course details from courses table
	 *
	 * @param   INT  $courseId  id of course
	 *
	 * @return  object course info
	 *
	 * @since  1.0
	 */
	public function getcourseinfo($courseId)
	{
		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjlms/tables');
		$coursetable        = Table::getInstance('Course', 'TjlmsTable');
		$coursetable->load($courseId);
		$properties         = $coursetable->getProperties(1);
		$course             = ArrayHelper::toObject($properties, 'JObject');
		$course->authorized = 0;

		if (!empty($course->id))
		{
			$category                  = Table::getInstance('Category', 'JTable');
			$category->load(array('id' => $course->catid, 'extension' => 'com_tjlms'));
			$course->catState          = $category->published;
			$course->catAccess         = $category->access;
			$course->category_title    = $category->title;

			if ((isset($course->state) && $course->state != 1)
				|| (Factory::getDate()->toSql() < $course->start_date)
				|| (isset($course->catState) && $course->catState != 1))
			{
				$course->state = 0;
			}

			// Get User access level
			$user_access = Factory::getUser()->getAuthorisedViewLevels();

			// Check if user is authorised to take the course
			if (in_array($course->access, $user_access) && in_array($course->catAccess, $user_access))
			{
				$course->authorized = 1;
			}

			// Get name of the creator from users table
			$course->creator_name = $course->creator_username = Text::_('COM_TJLMS_BLOCKED_USER');

			if (User::getTable()->load($course->created_by))
			{
				$userInfo = Factory::getUser($course->created_by);

				if ($userInfo->block == 0)
				{
					$course->creator_name     = $userInfo->name;
					$course->creator_username = $userInfo->username;
				}
			}

			// Get image accorfing to storage
			$course->image = $this->tjlmsCoursesHelper->getCourseImage((array) $course, 'S_');

			// Set UTC date to orig_start_date
			$course->orig_start_date = $course->start_date;
			$lmsparams               = ComponentHelper::getParams('com_tjlms');
			$date_format_show        = $lmsparams->get('date_format_show', 'Y-m-d H:i:s');
			$course->start_date      = $this->techjoomlacommon->getDateInLocal($course->start_date, 0, $date_format_show);
		}

		return $course;
	}

	/**
	 * Method to fetch subs plans assigned for course
	 *
	 * @param   int  $courseId  id of course
	 *
	 * @return  object course Sub plan
	 *
	 * @since  1.0
	 */
	public function getsubs_plan($courseId)
	{
		return $this->tjlmsCoursesHelper->getCourseSubplans($courseId);
	}

	/**
	 * Method to fetch subs plans assigned for course
	 *
	 * @param   int  $courseId  id of course
	 *
	 * @return  object course Sub plan
	 *
	 * @since  1.0
	 */
	/*public function getCourseRemainingSubPlan($courseId)
	{
		$remainDaysOfPlan = $this->tjlmsCoursesHelper->getCourseRemainingDays($courseId);

		if (isset($remainDaysOfPlan))
		{
			$endDate = new DateTime($remainDaysOfPlan->end_time);
			$return = array();
			$return['remain_sub_plan'] = date_format($endDate, "d M Y");
			$return['unlimited_plan'] = $remainDaysOfPlan->unlimited_plan;

			return $return;
		}
	}*/

	/**
	 * Function to check if student is enrolled for a particular course.
	 *
	 * @param   object  &$lesson       Lesson id
	 * @param   object  $lessonTracks  Lesson tracks
	 *
	 * @return  void
	 *
	 * @since  1.3.30
	 */
	public function formatTrackstoGetStatus(&$lesson, $lessonTracks)
	{
		$lesson->userStatus['score']          = Text::_('COM_TJLMS_N_A');
		$lesson->userStatus['status']         = 'not_started';
		$lesson->userStatus['startedOn']      = Text::_('COM_TJLMS_NEVER');
		$lesson->userStatus['lastAccessedOn'] = Text::_('COM_TJLMS_NEVER');
		$lesson->userStatus['totalTimeSpent'] = 0;
		$lesson->userStatus['viewed']         = 1;

		if (!count($lessonTracks))
		{
			$lesson->userStatus['viewed'] = 0;

			return;
		}

		$lmsparams  = ComponentHelper::getParams('com_tjlms');
		$dateFormat = $lmsparams->get('date_format_show', 'Y-m-d H:i:s');

		if (!empty($lesson->ideal_time))
		{
			$lesson->idealTimeInHours = Tjlms::Utilities()->secToHours($lesson->ideal_time * 60, false);
		}

		if (count($lessonTracks) == 1)
		{
			$lesson->userStatus['score']                 = $lessonTracks[0]->score;
			$lesson->userStatus['status']                = $lessonTracks[0]->lesson_status;
			$lesson->userStatus['startedOn']             = $this->techjoomlacommon->getDateInLocal($lessonTracks[0]->timestart, 0, $dateFormat);
			$lesson->userStatus['lastAccessedOn']        = $this->techjoomlacommon->getDateInLocal($lessonTracks[0]->last_accessed_on, 0, $dateFormat);
			$lesson->userStatus['lastAccessedOnUTCdate'] = $lessonTracks[0]->last_accessed_on;

			list($h, $m, $s) = explode(':', $lessonTracks[0]->time_spent);
			$lesson->userStatus['totalTimeSpent'] = Tjlms::Utilities()->secToHours(($h * 3600) + ($m * 60) + $s, false);

			return $lessonTracks[0];
		}

		$templessonTracks = array_map(
			function($track)
			{
				return (array) $track;

			}, $lessonTracks
		);

		$scoresArr    = array_column($templessonTracks, 'score');

		$timeSpentArr = array_column($templessonTracks, 'time_spent');

		$timeSpentSecArr = array_map(
			function($eachTemp)
			{
				list($h, $m, $s) = explode(':', $eachTemp);

				return ($h * 3600) + ($m * 60) + $s;

			}, $timeSpentArr
		);

		$lesson->userStatus['totalTimeSpent'] = Tjlms::Utilities()->secToHours(array_sum($timeSpentSecArr), false);

		// Since the records are fetched in descending order of lesson track id
		$firstTrack = $lessonTracks[array_key_last($lessonTracks)];
		$lastTrack  = $lessonTracks[array_key_first($lessonTracks)];

		$lesson->userStatus['startedOn']             = $this->techjoomlacommon->getDateInLocal($firstTrack->timestart, 0, $dateFormat);
		$lesson->userStatus['lastAccessedOn']        = $this->techjoomlacommon->getDateInLocal($lastTrack->last_accessed_on, 0, $dateFormat);
		$lesson->userStatus['lastAccessedOnUTCdate'] = $lastTrack->last_accessed_on;

		switch ($lesson->attempts_grade)
		{
			// Highest Attempt
			case "0" :
				$maxArrKey                    = array_search(max($scoresArr), $scoresArr);
				$tempTrack                    = $lessonTracks[$maxArrKey];
				$lesson->userStatus['score']  = $tempTrack->score;
				$lesson->userStatus['status'] = $tempTrack->lesson_status;

			break;

			// Average Attempts
			case "1" :
				$score = array_sum($scoresArr) / count($scoresArr);
				$lesson->userStatus['score']  = round($score);
				$lesson->userStatus['status'] = "failed";

				if ($score >= $lesson->passing_score)
				{
					$lesson->userStatus['status'] = "passed";
				}

			break;

			// First Attempt
			case "2" :
				$lesson->userStatus['status'] = $firstTrack->lesson_status;
				$lesson->userStatus['score']  = $firstTrack->score;

			break;

			// Last completed attempt
			case "3" :
				$completStatus = array("completed", "passed", "failed");

				foreach ($lessonTracks as $track)
				{
					if (in_array($track->lesson_status, $completStatus))
					{
						$lesson->userStatus['status'] = $track->lesson_status;
						$lesson->userStatus['score']  = $track->score;
						break;
					}
				}

			break;

			default:
			break;
		}
	}

	/**
	 * Get all the modules/sections and their lessons of a course
	 * Called from a lms_course_blocks to get lesson_count and passed_lessons count
	 *
	 * @param   int  $course_id               id of course
	 * @param   int  $getlessonStatusdetails  set 1 if we want to get the status details,
	 * like lastaccessedon , number of attmempts done by user
	 * @param   int  $oluser_id               user fr whom progress has to be get
	 *
	 * @return  object  $module_data
	 *
	 * @since  1.0.0
	 */
	public function getCourseTocdetails($course_id, $getlessonStatusdetails = 1, $oluser_id = '')
	{
		$lmsparams        = ComponentHelper::getParams('com_tjlms');
		$date_format_show = $lmsparams->get('date_format_show', 'Y-m-d H:i:s');

		$db    = Factory::getDBO();
		$app   = Factory::getApplication();
		$input = $app->input;

		if (empty($oluser_id))
		{
			$oluser_id = Factory::getUser()->id;
		}

		$lessonHelper = $this->tjlmsLessonHelper;
		$toc          = array();
		$lessonCount  = 0;

		// Get data if course if present
		if ($course_id > 0)
		{
			try
			{
				$query = $db->getQuery(true);
				$query->select('*');
				$query->from($db->quoteName('#__tjlms_modules'));
				$query->where($db->quoteName('course_id') . ' = ' . $db->quote((int) $course_id));
				$query->where($db->quoteName('state') . ' = 1');
				$query->order($db->quoteName('ordering') . ' ASC');
				$db->setQuery($query);
				$modules = $db->loadobjectlist('id');
				$modules = ArrayHelper::pivot((array) $modules, 'id');

				$toc = array();
				$lessonLastaccessedon = array();
				$lessonCount = 0;

				if (!empty($modules))
				{
					// Get all published and format uploaded lessons
					$query = $db->getQuery(true);
					$query->select('l.*');
					$query->select('m.sub_format,m.format,m.source');
					$query->from($db->quoteName('#__tjlms_lessons', 'l'));
					$query->join('LEFT', $db->quoteName('#__tjlms_media', 'm') . " ON l.media_id=m.id");
					$query->join('INNER', $db->quoteName('#__tjlms_modules', 'mod') . " ON mod.id=l.mod_id");
					$query->where($db->quoteName('l.state') . ' = 1');
					$query->where($db->quoteName('l.format') . "<> ''");
					$query->where($db->quoteName('l.media_id') . " >  0");
					$query->where($db->quoteName('l.media_id') . " <>  ''");
					$query->where($db->quoteName('l.mod_id') . " IN (" . implode(",", array_keys($modules)) . ")");
					$query->order($db->quoteName('mod.ordering') . ' ASC');
					$query->order($db->quoteName('l.ordering') . ' ASC');

					$db->setQuery($query);
					$lessons = $db->loadobjectlist();

					foreach ($lessons as $ind => $lesson)
					{
						$plg_type         = 'tj' . $lesson->format;
						$format_subformat = explode('.', $lesson->sub_format);
						$plg_name         = $format_subformat[0];

						PluginHelper::importPlugin($plg_type);
						$checkFormat = Factory::getApplication()->triggerEvent('onAdditional' . $plg_name . 'FormatCheck', array($lesson->id, $lesson));

						if (!empty($checkFormat))
						{
							$format_res = $checkFormat[0];

							if (!$format_res)
							{
								unset($lessons[$ind]);
								continue;
							}
						}

						// Lesson start date according to user's timezone
						// $lesson->start_date = $this->techjoomlacommon->getDateInLocal($lesson->start_date);

						// Lesson end date according to user's timezone
						// $lesson->end_date = $this->techjoomlacommon->getDateInLocal($lesson->end_date);

						if ($oluser_id)
						{
							JLoader::import('components.com_tjlms.models.lessontrack', JPATH_SITE);
							$lessonTrackmodel = BaseDatabaseModel::getInstance('lessonTrack', 'tjlmsModel', array('ignore_request' => true));
							$lessonTrackmodel->setState("lesson_id", $lesson->id);
							$lessonTrackmodel->setState("user_id", $oluser_id);
							$lessonTrackmodel->setState("list.ordering", "lt.id");
							$lessonTrackmodel->setState("list.direction", "DESC");
							$lessonTracks = $lessonTrackmodel->getItems();

							$completedTracks = array_map(
								function($track)
								{
									$completStatus = array("completed", "passed", "failed");

									if (in_array($track->lesson_status, $completStatus))
									{
										return ($track);
									}
								}, $lessonTracks
							);

							$completedTracks = array_values(array_filter($completedTracks));
							$lesson->userStatus['completedAttempts'] = count($completedTracks);
							$lesson->userStatus['attemptsDone'] = count($lessonTracks);

							// Get lesson_status and score by attempts grading
							$this->formatTrackstoGetStatus($lesson, $lessonTracks);

							$lessonLastaccessedon[] = !empty($lesson->userStatus['lastAccessedOnUTCdate']) ? $lesson->userStatus['lastAccessedOnUTCdate'] : '';
							/*$lesson->userStatus['score'] = Text::_('COM_TJLMS_N_A');

							if (isset($statusandscore->score) && $statusandscore->score != " ")
							{
								$lesson->userStatus['score'] = round($statusandscore->score);
							}

							if (isset($statusandscore->lesson_status) && !empty($statusandscore->lesson_status))
							{
								$lesson->userStatus['status'] = $statusandscore->lesson_status;
							}
							else
							{
								$lesson->userStatus['status'] = Text::_('COM_TJLMS_NOT_STARTED');
							}*/
						}

						if (!empty($lesson->image))
						{
							try
							{
								$params     = ComponentHelper::getParams('com_tjlms');
								$uploadPath = $params->get('lesson_image_upload_path', "/images/com_tjlms/lessons/");
								$mediaObj   = TJMediaStorageLocal::getInstance(array("id" => $lesson->image ,"uploadPath" => $uploadPath));

								$lesson->image = $mediaObj->media;
							}
							catch (\Exception $e)
							{
								$this->setError($e->getMessage());
							}
						}

						if (empty($lesson->image))
						{
							$lesson->image = $this->defaultLessonImage;
						}

						if (!isset($toc[$lesson->mod_id]))
						{
							$toc[$lesson->mod_id] = $modules[$lesson->mod_id];
						}

						if (!empty($lesson->userStatus['lastAccessedOnUTCdate']))
						{
							$toc[$lesson->mod_id]->moduleLastaccessedon = $lesson->userStatus['lastAccessedOnUTCdate'];
						}

						$toc[$lesson->mod_id]->lessons[] = $lesson;

						$completedLessonsCount = 0;

						foreach ($toc[$lesson->mod_id]->lessons as $lessonData)
						{
							if (!empty($lessonData->userStatus['completedAttempts']))
							{
								$completedLessonsCount++;
							}
						}

						if (!empty($completedLessonsCount))
						{
							$toc[$lesson->mod_id]->completedLessonsCount = $completedLessonsCount;
						}

						$lessonCount++;
					}
				}
			}
			catch (Exception $e)
			{
				$this->setError($e->getMessage());

			return false;
			}
		}

		$result['toc']          = $toc;
		$result['lesson_count'] = $lessonCount;

		return $result;
	}

	/**
	 * Function to check if student is enrolled for a particular course.
	 *
	 * @param   int  $courseId     id of course
	 * @param   int  $userId       id of user
	 * @param   int  $course_type  Course ype
	 *
	 * @return  int  $state
	 *
	 * @since  1.0.0
	 */
	public function checkifuserenroled($courseId, $userId, $course_type)
	{
		JLoader::register('TjlmsModelEnrolment', JPATH_SITE . '/components/com_tjlms/models/enrolment.php');
		$tjlmsModelEnrolment = new TjlmsModelEnrolment;
		$result = $tjlmsModelEnrolment->getEnrolledUserColumn($courseId, $userId, '*');

		$state = '';

		if (!empty($result))
		{
			if ($result->state == 1 && $course_type == 0)
			{
				$state = 1;
			}
			elseif ($result->state == 1 && $course_type == 1)
			{
				$end_time = strtotime($result->end_time);
				$today    = Factory::getDate();
				$curdate  = strtotime($today);

				if ($curdate < $end_time || $result->unlimited_plan == 1)
				{
					$state = 1;
				}
				elseif ($end_time == '')
				{
					$state = -3;
				}
				else
				{
					$state = -2;
				}
			}
			else
			{
				$state = $result->state;
			}
		}

		return $state;
	}

	/**
	 * Get enrolled student for a particular course.
	 *
	 * @param   int    $course_id  id of course
	 *
	 * @param   ARRAY  $options    Optional parameter
	 *
	 * @return  Object  $enroled_users
	 *
	 * @since  1.0.0
	 */
	public function getallenroledUsersinfo($course_id, $options = array ())
	{
		$comtjlmsHelper = new comtjlmsHelper;
		$enroled_users  = $comtjlmsHelper->getCourseEnrolledUsers($course_id, $options);

		foreach ($enroled_users as $index => $enrolment_info)
		{
			$student                       = Factory::getUser($enrolment_info->user_id);
			$enroled_users[$index]->avatar = "";
			$link                          = '';

			$enroled_users[$index]->username = Text::_('COM_TJLMS_BLOCKED_USER');
			$enroled_users[$index]->name     = Text::_('COM_TJLMS_BLOCKED_USER');

			if ($student->block == 0)
			{
				$enroled_users[$index]->avatar   = $comtjlmsHelper->sociallibraryobj->getAvatar($student, 50);
				$enroled_users[$index]->username = $student->username;
				$enroled_users[$index]->name     = $student->name;
				$link                            = $comtjlmsHelper->sociallibraryobj->getProfileUrl($student);

				if ($link)
				{
					if (!parse_url($link, PHP_URL_HOST))
					{
						$link = Uri::root() . substr(Route::_($comtjlmsHelper->sociallibraryobj->getProfileUrl($student)), strlen(Uri::base(true)) + 1);
					}
				}
			}

			$enroled_users[$index]->profileurl = $link;
		}

		return $enroled_users;
	}

	/**
	 * Function used to get creator info
	 *
	 * @param   int  $user_id  id of user
	 *
	 * @return  Object
	 *
	 * @since  1.0.0
	 */
	public function getCreatedInfo($user_id)
	{
		$comtjlmsHelper     = new comtjlmsHelper;
		$taughtBy           = new stdclass;
		$link               = '';
		$taughtBy->avatar   = "";
		$taughtBy->id       = 0;
		$taughtBy->name     = Text::_('COM_TJLMS_BLOCKED_USER');
		$taughtBy->username = Text::_('COM_TJLMS_BLOCKED_USER');

		if (User::getTable()->load($user_id))
		{
			$taughtByUser = Factory::getUser($user_id);
			$taughtBy->id = $taughtByUser->id;

			if ($taughtByUser->block == 0)
			{
				$taughtBy->avatar   = $comtjlmsHelper->sociallibraryobj->getAvatar($taughtByUser, 50);
				$taughtBy->name     = $taughtByUser->name;
				$taughtBy->username = $taughtByUser->username;
				$link               = $profileUrl = $comtjlmsHelper->sociallibraryobj->getProfileUrl($taughtByUser);

				if ($profileUrl)
				{
					if (!parse_url($profileUrl, PHP_URL_HOST))
					{
						$link = Uri::root() . substr(Route::_($comtjlmsHelper->sociallibraryobj->getProfileUrl($taughtByUser)), strlen(Uri::base(true)) + 1);
					}
				}
			}
		}

		$taughtBy->profileurl = $link;

		return $taughtBy;
	}

	/**
	 * Get avatar of user
	 *
	 * @param   int     $id         id of user
	 * @param   string  $to_direct  integration used
	 *
	 * @return  string $avatar
	 *
	 * @since   1.0.0
	 */
	public function getavatar($id, $to_direct)
	{
		$db     = Factory::getDBO();
		$avatar = '';

		if (strcmp($to_direct, "JomSocial") == 0)
		{
			$jspath = JPATH_SITE . '/' . 'components' . '/' . 'com_community';

			if (Folder::exists($jspath))
			{
				// Fetching the avatar fron amazon S3
				include_once $jspath . '/' . 'libraries' . '/' . 'core.php';
				$user1  = CFactory::getUser($id);
				$uimage = $user1->getThumbAvatar();
				$avatar = str_replace('administrator/', '', $uimage);
			}
		}
		elseif (strcmp($to_direct, "Community Builder") == 0)
		{
			$path = JPATH_SITE . '/' . 'components' . '/' . 'com_comprofiler';

			if (Folder::exists($path))
			{
				$query = $db->getQuery(true);
				$query->select($db->quoteName('avatar'));
				$query->from($db->quoteName('#__comprofiler'));
				$query->where($db->quoteName('id') . ' = ' . $db->quote((int) $id));
				$db->setQuery($query);
				$avatar = $db->loadResult();

				if ($avatar)
				{
					$avatar = Uri::base() . "images/comprofiler/" . $avatar;
				}
				else
				{
					$avatar = Uri::base() . "components/com_comprofiler/plugin/language/default_language/images/tnnophoto.jpg";
				}
			}
		}

		return $avatar;
	}

	/**
	 * Get course order info
	 *
	 * @param   int  $course_id  id of course
	 *
	 * @return  int $course_user_order_info
	 *
	 * @since   1.0.0
	 */
	public function course_user_order_info($course_id)
	{
		try
		{
			$db      = Factory::getDBO();
			$user_id = Factory::getUser()->id;
			$query   = $db->getQuery(true);
			$query->select($db->quoteName(array('o.status', 'o.processor')));
			$query->from($db->quoteName('#__tjlms_orders', 'o'));
			$query->join('INNER', $db->qn('#__tjlms_order_items', 'oi') . ' ON (' . $db->qn('oi.order_id') . ' = ' . $db->qn('o.id') . ')');
			$query->where($db->quoteName('o.user_id') . " = " . $db->quote((int) $user_id));
			$query->where($db->quoteName('o.course_id') . " = " . $db->quote((int) $course_id));
			$query->order($db->quoteName('o.id') . ' DESC');
			$db->setQuery($query);

			return $db->loadObject();
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}
	}

	/**
	 * Get assigned courses for the user.
	 *
	 * @param   int  $course_id   id of course
	 * @param   int  $created_by  id of course creator
	 *
	 * @return  ARRAY $record
	 *
	 * @since   1.0.0
	 */
	public function getuserAssignedUsersInfo($course_id, $created_by = 0)
	{
		$comtjlmsHelper   = new comtjlmsHelper;
		$assigned_userids = $this->getuserAssignedUsers($course_id, $created_by);
		$assigned_users   = array();

		foreach ($assigned_userids as $index => $assign_userid)
		{
			$assigned_users[$index]         = new stdClass;
			$student                        = Factory::getUser($assign_userid);
			$assigned_users[$index]->avatar = "";

			$link                             = '';
			$assigned_users[$index]->username = Text::_('COM_TJLMS_BLOCKED_USER');
			$assigned_users[$index]->name     = Text::_('COM_TJLMS_BLOCKED_USER');

			if ($student->block == 0)
			{
				$assigned_users[$index]->avatar   = $comtjlmsHelper->sociallibraryobj->getAvatar($student, 50);
				$assigned_users[$index]->username = $student->username;
				$assigned_users[$index]->name     = $student->name;
				$profileUrl                       = $comtjlmsHelper->sociallibraryobj->getProfileUrl($student);

				if (!empty($profileUrl))
				{
					$link = Route::_($profileUrl);
				}
			}

			$assigned_users[$index]->profileurl = $link;
		}

		return $assigned_users;
	}

	/**
	 * Get assigned courses for the user.
	 *
	 * @param   int  $course_id    id of course
	 * @param   int  $enrolled_by  id of course creator
	 *
	 * @return  ARRAY $record
	 *
	 * @since   1.0.0
	 */
	public function getuserAssignedUsers($course_id, $enrolled_by = 0)
	{
		$app     = Factory::getApplication();
		
		try
		{
			$db      = Factory::getDBO();
			$user_id = Factory::getUser()->id;
			$query   = $db->getQuery(true);
			$query->select($db->quoteName('eu.user_id'));
			$query->from($db->quoteName('#__tjlms_enrolled_users', 'eu'));
			$query->join('INNER', $db->quoteName('#__users', 'u') . ' ON u.id = eu.user_id');
			$query->where($db->quoteName('eu.course_id') . " = " . $db->quote((int) $course_id));

			if ($enrolled_by)
			{
				$query->where($db->quoteName('eu.enrolled_by') . " = " . $db->quote((int) $enrolled_by));
			}

			$query->where($db->quoteName('eu.state') . '=1');
			$query->setLimit('5');
			$db->setQuery($query);

			return $db->loadColumn();
		}
		catch (Exception $e)
		{
			$app->enqueueMessage($e->getMessage(), 'warning');
			$app->setHeader('status', $e->getCode(), true);
		}
	}

	/**
	 * Get recommend user for the course.
	 *
	 * @param   int  $courseId  id of course
	 * @param   int  $userId    id of user
	 *
	 * @return  ARRAY $record
	 *
	 * @since   1.0.0
	 */
	public function getuserRecommendedUsers($courseId, $userId)
	{
		$comtjlmsHelper = new comtjlmsHelper;
		$recommendedusers = array();

		try
		{
			$model = BaseDatabaseModel::getInstance('Recommendations', 'JlikeModel', array('ignore_request' => true));
			$model->setState('type', 'recommendbyme');
			$model->setState("element_id", $courseId);
			$model->setState("list.ordering", "a.created_date");
			$model->setState("list.direction", "DESC");
			$model->setState("list.limit", "6");

			$recommendedCourseData = $model->getItems();

			if (!empty($recommendedCourseData))
			{
				foreach ($recommendedCourseData as $recommendedCourse)
				{
					$recommendedusers[] = $recommendedCourse->assigned_to;
				}
			}
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}

		foreach ($recommendedusers as $index => $recommend_userid)
		{
			$recommendedusers[$index]           = new stdClass;
			$student                            = Factory::getUser($recommend_userid);
			$recommendedusers[$index]->avatar   = "";
			$link                               = '';
			$recommendedusers[$index]->username = Text::_('COM_TJLMS_BLOCKED_USER');
			$recommendedusers[$index]->name     = Text::_('COM_TJLMS_BLOCKED_USER');

			if ($student->block == 0)
			{
				$recommendedusers[$index]->avatar   = $comtjlmsHelper->sociallibraryobj->getAvatar($student, 50);
				$recommendedusers[$index]->username = $student->username;
				$recommendedusers[$index]->name     = $student->name;
				$profileUrl                         = $comtjlmsHelper->sociallibraryobj->getProfileUrl($student);

				if (!empty($profileUrl))
				{
					$link = Route::_($profileUrl);
				}
			}

			$recommendedusers[$index]->profileurl = $link;
		}

		return $recommendedusers;
	}

	/**
	 * Check if user has a course track
	 *
	 * @param   int  $course_id  id of course
	 * @param   int  $user_id    id of user
	 *
	 * @return  boolean
	 *
	 * @since   1.0.0
	 */
	public function CheckIfUserHasProgress($course_id, $user_id)
	{
		$db = Factory::getDBO();

		// Add Table Path
		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjlms/tables');
		$courseTrack = Table::getInstance('Coursetrack', 'TjlmsTable', array('dbo', $db));
		$courseTrack->load(array('course_id' => (int) $course_id, 'user_id' => (int) $user_id));

		if ($courseTrack->id)
		{
			return 1;
		}
		else
		{
			return 0;
		}
	}

	/**
	 * Function to add certificate  entry
	 *
	 * @param   INT          $courseId    Course ID
	 * @param   INT          $userId      user ID
	 * @param   Date|String  $issuedDate  Certificate grant date
	 *
	 * @return  INT|BOOLEAN
	 *
	 * @since  1.0.0
	 */
	public function addCertEntry($courseId, $userId = '', $issuedDate = '')
	{
		if (empty($courseId))
		{
			return;
		}

		$courseData = TjLms::course($courseId);

		// Check there is a certificate against the course
		if (!empty($courseData->certificate_id))
		{
			$checkIfIssued       = $this->checkCertificateIssued($courseId, $userId);
			$tjCert              = TJCERT::Certificate();

			if ($checkIfIssued[0]->id)
			{
				$tjCert::validateCertificate($checkIfIssued[0]->unique_certificate_id);

				// If certificate issued and not expired then set id and unique_certificate_id of certificate
				$tjCert->id = $checkIfIssued[0]->id;
				$tjCert->unique_certificate_id = $checkIfIssued[0]->unique_certificate_id;
			}

			$certificateExpiryDate   = '';
			$certificateExpiryInDays = $courseData->expiry;

			if (!empty($certificateExpiryInDays) && $certificateExpiryInDays > 0)
			{
				$certificateExpiryDate = Factory::getDate(($issuedDate)?$issuedDate:'now ', 'UTC');// DPE HACK
				$certificateExpiryDate->modify("+" . $certificateExpiryInDays . " days");
				$certificateExpiryDate = $certificateExpiryDate->toSql();
			}

			$tjCert->setCertificateTemplate($courseData->certificate_id);
			$tjCert->setClient($this->client);
			$tjCert->setClientId($courseData->id);
			$tjCert->setUserId($userId);

			if (!empty($certificateExpiryDate))
			{
				$tjCert->setExpiry($certificateExpiryDate);
			}

			// Set certificate issue date
			$tjCert->setIssuedDate(Factory::getDate(($issuedDate)?$issuedDate:'')->toSQL()); // DPE HACK

			// Get Certificate Replacements //DPE Hack to get the replacement value by month name.
			$lmsparams  = JComponentHelper::getParams('com_tjlms');
			$dateFormat = $lmsparams->get('certificate_date_format', 'j F Y');
			$issuedDate = HTMLHelper::date($issuedDate, $dateFormat);
			$replacements 	= $this->getReplacementTags($courseId, $userId, $issuedDate);

			$options        = new Registry;
			$certificateObj = $tjCert->issueCertificate($replacements, $options);

			return $certificateObj->id;
		}
	}

	/**
	 * function to get html and params for certificate
	 *
	 * @param   INT  $courseId  Course ID
	 *
	 * @param   INT  $userId    User ID
	 *
	 * @return  Array
	 *
	 * @since 1.0
	 *
	 * @deprecated _DEPLOY_VERSION_
	 */
	public function getcertificateHTML($courseId, $userId = '')
	{
		$db             = Factory::getDBO();
		$comtjlmsHelper = new comtjlmsHelper;
		$html           = array();

		$msg = $this->getReplacementTags($courseId, $userId);
		$msg['course_id'] = $courseId;

		$course = $this->getItem($courseId);

		if (!empty($course->fields))
		{
			foreach ($course->fields as $field)
			{
				$msg['course.field.' . $field->name] = $field->value;
			}
		}

		$result = isset($course->certificate_id) ? (int) $course->certificate_id : 0;

		$certtmpl = Table::getInstance('certificatetemplate', 'TjlmsTable', array('dbo', $db));
		$certtmpl->load(array('id' => (int) $result));

		// Get the params for the current certificate template.
		$html['params'] = isset($certtmpl->params) ? $certtmpl->params : '';
		$result         = isset($certtmpl->body) ? $certtmpl->body : '';

		// Trigger to replace tag
		PluginHelper::importPlugin('system');
		Factory::getApplication()->triggerEvent('onBeforeTjlmsTagReplace', array($course, $userId, &$msg));

		if (!empty($result))
		{
			$msg['msg_body'] = $result;
		}

		// Replace Special Tags from Backend Ticket Template
		$html['html']   = $comtjlmsHelper->tagreplace($msg);

		// On after tag replace
		PluginHelper::importPlugin('system');
		Factory::getApplication()->triggerEvent('onAfterTagReplace', array($html['html']));

		return $html;
	}

	/**
	 * Function to get certificate date
	 *
	 * @param   INT          $courseId    Course ID
	 *
	 * @param   INT          $userId      User ID
	 *
	 * @param   Date|String  $issuedDate  Certificate grant date.
	 *
	 * @return  ARRAY
	 *
	 * @since  1.0.0
	 */
	public function getReplacementTags($courseId, $userId = '', $issuedDate = '')
	{
		$lmsparams  = ComponentHelper::getParams('com_tjlms');
		$dateFormat = $lmsparams->get('certificate_date_format', 'j F Y');

		// Check ES and JS for tag replacement.
		$esFilePath = JPATH_ADMINISTRATOR . '/components/com_easysocial/includes/foundry.php';
		$jsFilePath = JPATH_ROOT . '/components/com_community/libraries/core.php';

		if ($courseId)
		{
			if ($userId != '')
			{
				$user = Factory::getUser($userId);
			}
			else
			{
				$user = Factory::getUser();
			}

			$result      = new stdClass;
			$course      = new stdClass;
			$enroll      = new stdClass;
			$certificate = new stdClass;

			$db                      = Factory::getDBO();
			$courseDetails           = TjLms::course($courseId);
			$course->title           = $courseDetails->title;
			$enroll->studentname     = $user->name;
			$enroll->studentusername = $user->username;
			$courseCreatorData       = Factory::getUser($courseDetails->created_by);
			$course->creator         = $courseCreatorData->name ? $courseCreatorData->name : '';

			Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjlms/tables');
			$coursetrack = Table::getInstance('coursetrack', 'TjlmsTable', array('dbo', $db));
			$coursetrack->load(array('course_id' => (int) $courseId, 'user_id' => (int) $user->id));

			$certificateData        = $this->checkCertificateIssued($courseId, $userId);
			$certificate->cert_id   = $certificateData[0]->unique_certificate_id;
			$course->completed_date = HTMLHelper::date($coursetrack->timeend, $dateFormat);
			$course->first_completed_date  = HTMLHelper::date($coursetrack->first_completed_date, $dateFormat);

			if (file_exists($esFilePath))
			{
				require_once $esFilePath;
				$esuser   = Foundry::user();
				$pattern  = "/{([^}]*)}/";
				$template = TJCERT::Template($courseDetails->certificate_id);
				$esfield  = new stdClass;

				preg_match_all($pattern, $template->body, $matches);

				$ESValueArray = preg_grep('/^esfield\.*/', $matches[1]);

				foreach ($ESValueArray as $key => $value)
				{
					$value   = str_replace("esfield.", "", $value);
					$ESValue = $esuser->getFieldValue($value);

					if (is_array($ESValue))
					{
						$ESValue = implode(",", $ESValue);
					}

					$esfield->$value = $ESValue;
					$result->esfield = $esfield;
				}
			}

			if (file_exists($jsFilePath))
			{
				include_once $jsFilePath;
				$jsprofile = CFactory::getUser();
				$pattern   = "/{([^}]*)}/";
				$template  = TJCERT::Template($courseDetails->certificate_id);
				$jsfield   = new stdClass;

				preg_match_all($pattern, $template->body, $matches);

				$JSValueArray = preg_grep('/^jsfield\.*/', $matches[1]);

				foreach ($JSValueArray as $key => $value)
				{
					$value   = str_replace("jsfield.", "", $value);
					$JSValue = $jsprofile->getInfo($value);

					if (is_array($JSValue))
					{
						$JSValue = implode(",", $JSValue);
					}

					if ($value == 'FIELD_GENDER')
					{
						$JSValue = Text::_($JSValue);
					}

					$jsfield->$value = $JSValue;
					$result->jsfield = $jsfield;
				}
			}

			$courseFieldsData = $this->getData($courseId);

			if (!empty($courseFieldsData->fields))
			{
				$pattern     = "/{([^}]*)}/";
				$template    = TJCERT::Template($courseDetails->certificate_id);
				$coursefield = new stdClass;

				preg_match_all($pattern, $template->body, $matches);

				$courseFieldsValueArray = preg_grep('/^coursefield\.*/', $matches[1]);

				foreach ($courseFieldsValueArray as $key => $value)
				{
					$value   = str_replace("coursefield.", "", $value);
					$courseFieldsValue = '';

					foreach ($courseFieldsData->fields as $field)
					{
						if ($value == $field->name)
						{
							$courseFieldsValue = $field->value;

							break;
						}
					}

					if (is_array($courseFieldsValue))
					{
						$courseFieldsValue = implode(",", $courseFieldsValue);
					}

					$coursefield->$value = $courseFieldsValue;
					$result->coursefield = $coursefield;
				}
			}

			$certificate->granted_date = (!empty($issuedDate) &&
					$issuedDate != '0000-00-00 00:00:00')  ? $issuedDate : HTMLHelper::date(Factory::getDate()->toSQL(), $dateFormat);

			$certificateExpiryDate = $courseDetails->expiry;

			if (!empty($certificateExpiryDate) && $certificateExpiryDate > 0)
			{
				$certificateExpiryDate = Factory::getDate($this->startdate, 'UTC');
				$certificateExpiryDate->modify("+" . $courseDetails->expiry . " days");
				$certificateExpiryDate = $certificateExpiryDate->toSql();
			}

			if ($certificateExpiryDate == '0000-00-00 00:00:00' || $certificateExpiryDate == 0)
			{
				$certificate->expiry_date = '';
			}
			else
			{
				$certificate->expiry_date = HTMLHelper::date($certificateExpiryDate, $dateFormat);
			}

			$course->total_time = $this->getTotalTimeSpentOnCourse($courseId, $user->id);

			$result->course      = $course;
			$result->enroll      = $enroll;
			$result->certificate = $certificate;

			// Trigger to replace tag
			PluginHelper::importPlugin('system');
			Factory::getApplication()->triggerEvent('onBeforeTjlmsTagReplace', array($courseId, $userId, &$result));

			return $result;
		}
	}

	/**
	 * Get recommend user for the course.
	 *
	 * @param   int  $courseId  id of course
	 * @param   int  $userId    id of user
	 *
	 * @return  ARRAY $record
	 *
	 * @since   1.0.0
	 */
	public function getAssignedDueDate($courseId, $userId)
	{
		$comtjlmsHelper = new comtjlmsHelper;

		try
		{
			$db = Factory::getDBO();
			$query = $db->getQuery(true);
			$query->select($db->quoteName('lr.due_date'));
			$query->from($db->quoteName('#__jlike_todos', 'lr'));
			$query->join('INNER', $db->qn('#__jlike_content', 'lc') . ' ON (' . $db->qn('lc.id') . ' = ' . $db->qn('lr.content_id') . ')');
			$query->where($db->quoteName('lr.assigned_to') . '=' . $db->quote((int) $userId));
			$query->where($db->quoteName('lr.type') . '=' . $db->quote('assign'));
			$query->where($db->quoteName('lc.element_id') . '=' . $db->quote((int) $courseId));
			$db->setQuery($query);

			return $db->loadResult();
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}
	}

	/**
	 *  Function to get Total Time spent on Course
	 *
	 * @param   INT  $courseId  course_id
	 *
	 * @param   INT  $userId    logged in userId
	 *
	 * @return  totalSpentTime.
	 *
	 * @since 1.0.0
	 */
	public function getTotalTimeSpentOnCourse($courseId, $userId)
	{
		try
		{
			$db    = Factory::getDbo();
			$query = $db->getQuery(true);
			$query->select('SEC_TO_TIME(SUM(TIME_TO_SEC(lt.time_spent))) as timeSpentOnLesson');
			$query->from($db->quoteName('#__tjlms_lesson_track', 'lt'));
			$query->join('LEFT', $db->quoteName('#__tjlms_lessons', 'l') . ' ON (' . $db->quoteName('l.id') . ' = ' . $db->quoteName('lt.lesson_id') . ')');
			$query->where($db->quoteName('lt.user_id') . ' = ' . $db->quote((int) $userId));
			$query->where($db->quoteName('l.course_id') . ' = ' . $db->quote((int) $courseId));
			$db->setQuery($query);

			return $db->loadresult();
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @since   1.6
	 *
	 * @return void
	 */
	protected function populateState()
	{
		$app = Factory::getApplication('site');

		if (!$app->isClient('administrator'))
		{
			// Load state from the request.
			$pk = $app->input->getInt('id');

			if (!$pk)
			{
				$pk = $app->input->getInt('course_id');
			}

			$this->setState('course.id', $pk);

			// Load the parameters.
			$params = $app->getParams();
			$this->setState('params', $params);
		}
		else
		{
			$params = ComponentHelper::getParams('com_tjlms');
			$this->setState('params', $params);
		}
	}

	/**
	 * Method to get a single record.
	 *
	 * @param   integer  $pk  The id of the primary key.
	 *
	 * @return	mixed	Object on success, false on failure.
	 *
	 * @since	1.6
	 */
	public function getData($pk = null)
	{
		$course   = $this->getItem($pk);
		$courseId = $course->id;

		// Course Tags
		$params      = $this->getState('params');
		$enable_tags = $params->get('enable_tags', '0', 'INT');

		if ($enable_tags == 1)
		{
			$course->course_tags = new TagsHelper;
			$course->course_tags->getItemTags($this->client, $courseId);
		}

		// Get Course's custom fields
		JLoader::register('FieldsHelper', JPATH_ADMINISTRATOR . '/components/com_fields/helpers/fields.php');
		$customFields = FieldsHelper::getFields($this->client, $course, true);

		if (!empty($customFields))
		{
			$course->fields = $customFields;
		}

		// Get course TOC details
		$temp                 = $this->getCourseTocdetails($courseId, 1);
		$course->toc          = $temp['toc'];
		$course->lesson_count = $temp['lesson_count'];

		if ($course->type == 1)
		{
			$course->subscriptionPlans = $this->getSubscriptionPlans($course->id);
		}

		$enrollmentData = $this->enrollmentStatus($course);
		$course = (object) array_merge((array) $course, (array) $enrollmentData);

		$userId  = Factory::getUser()->id;

		if ($course->userEnrollment->id)
		{
			$course->userCourseTrack = TjLms::Coursetrack($userId, $courseId);
		}

		if ($userId)
		{
			JLoader::import('components.com_jlike.models.recommendations', JPATH_SITE);
			$jlikeRecommModel = BaseDatabaseModel::getInstance('Recommendations', 'JlikeModel', array('ignore_request' => true));
			$jlikeRecommModel->setState("user_id", $userId);
			$jlikeRecommModel->setState("element_id", $courseId);
			$jlikeRecommModel->setState("element", $this->client);
			$jlikeRecommModel->setState("type", 'myassign');
			$jlikeRecommData           = $jlikeRecommModel->getItems();
			$course->assignmentDueDate = !empty($jlikeRecommData[0]->due_date) ? $jlikeRecommData[0]->due_date : '';
		}

		return $course;
	}

	/**
	 * Method to get a single record.
	 *
	 * @param   Object  $courseData  The course data.
	 *
	 * @return	mixed	Object on success, false on failure.
	 *
	 * @since	1.6
	 */
	public function enrollmentStatus($courseData)
	{
		$user = Factory::getUser();
		$course = new stdClass;

		// Get TjLms params
		$params       = ComponentHelper::getParams('com_tjlms');
		$autoEnroll   = $params->get('auto_enroll', 0, 'INT');
		$allowCreator = $params->get('allow_creator', 0, 'INT');

		$course->allowBuy = $course->allowEnroll = $course->canEnroll = 0;
		$course->enrolled = 0;

		if (!$user->id)
		{
			if ($courseData->type == 1)
			{
				$course->allowBuy = 1;
			}
			elseif(!$autoEnroll)
			{
				$course->allowEnroll = 1;
			}
		}
		else
		{
			$course->userEnrollment = TjLms::Enrollment($user->id, $courseData->id);

			$course->enrolled = ($course->userEnrollment->id) ? 1 : 0;

			JLoader::import('components.com_tjlms.models.orders', JPATH_SITE);
			$ordersModel = BaseDatabaseModel::getInstance('Orders', 'TjlmsModel', array('ignore_request' => true));
			$ordersModel->setState('filter.user_id', $user->id);
			$ordersModel->setState('filter.course_id', $courseData->id);
			$ordersModel->setState('list.ordering', 'a.id');
			$ordersModel->setState('list.direction', 'DESC');
			$ordersModel->setState('list.limit', 1);

			$orders = $ordersModel->getItems();

			$course->userOrder = isset($orders[0]) ? $orders[0] : '';

			$course->canEnroll = $user->authorise('core.enroll', $this->client . '.' . $courseData->id);

			if ($courseData->type == 1)
			{
				// Get whether the admin has allowed flexi enrolment
				$allowFlexiEnrolments = $params->get('allow_flexi_enrolments', 0, 'INT');

				// If not enrolled or If order status other than Pending or completed then show buy
				if ((!empty($course->userOrder->id)  && !in_array($course->userOrder->status, array("P", "C")) && !$course->userEnrollment->id)
					|| ($course->userEnrollment->id && $course->userEnrollment->expired == 1 && $course->userOrder->status != "P")
					|| empty($course->userOrder->id) || (!in_array($course->userOrder->status, array("I", "P", "C"))))
				{
					$course->allowBuy    = 1;
					$course->allowEnroll = 0;
				}
				elseif ($course->userOrder->status == "C" && !$course->userEnrollment->id && $allowFlexiEnrolments == 1)
				{
					$course->allowEnroll = 1;
					$course->allowBuy    = 0;
				}
			}
			elseif (!$course->userEnrollment->id && $course->canEnroll && !$autoEnroll &&
				(!$allowCreator || ($allowCreator && $user->id != $courseData->created_by)))
			{
				$course->allowBuy    = 0;
				$course->allowEnroll = 1;
			}
		}

		return $course;
	}

	/**
	 * Method to get a single record.
	 *
	 * @param   integer  $pk  The id of the primary key.
	 *
	 * @return	mixed	Object on success, false on failure.
	 *
	 * @since	1.6
	 */
	public function getItem($pk = null)
	{
		$user   = Factory::getUser();
		$userId = $user->id;

		$pk = (!empty($pk)) ? $pk : (int) $this->getState('course.id');

		if ($this->item === null)
		{
			$this->item = array();
		}

		if (!isset($this->item[$pk]))
		{
			try
			{
				$course  = TjLms::course($pk);

				if (empty($course->id))
				{
					$this->setError(Text::_("COM_TJLMS_ERROR_CONTENT_NOT_FOUND"));

					return false;
				}

				$categories        = Categories::getInstance('Tjlms');
				$category          = $categories->get($course->catid);
				$course->catState  = $category->published;
				$course->catAccess = $category->access;

				$params = clone $this->getState('params');

				$registry       = new Registry;
				$courseParams   = $registry->loadString($course->params);
				$course->params = $params->merge($courseParams);

				if ((isset($course->state) && $course->state != 1)
					|| (Factory::getDate()->toSql() < $course->start_date)
					|| (isset($course->catState) && $course->catState != 1))
				{
					$this->setError(Text::_("COM_TJLMS_ERROR_CONTENT_NOT_FOUND"));

					return false;
				}

				// If no access filter is set, the layout takes some responsibility for display of limited information.
				$groups = $user->getAuthorisedViewLevels();

				if ($course->catid == 0 || $course->catAccess === null)
				{
					$authorize = in_array($course->access, $groups);
				}
				else
				{
					$authorize = in_array($course->access, $groups) && in_array($course->catAccess, $groups);
				}

				$course->params->set('access-view', true);

				if (empty($authorize))
				{
					$course->params->set('access-view', false);
				}

				// Get name of the creator from users table
				$course->creator_name = $course->creator_username = Text::_('COM_TJLMS_BLOCKED_USER');

				if (User::getTable()->load($course->created_by))
				{
					$userInfo = Factory::getUser($course->created_by);

					if ($userInfo->block == 0)
					{
						$course->creator_name     = $userInfo->name;
						$course->creator_username = $userInfo->username;
					}
				}

				$course->originalImage = $course->image;

				// Get image accorfing to storage
				$course->image = $this->tjlmsCoursesHelper->getCourseImage((array) $course, 'S_');

				// Set UTC date to orig_start_date
				$course->orig_start_date = $course->start_date;
				/* $lmsparams               = ComponentHelper::getParams('com_tjlms');
				$date_format_show        = $lmsparams->get('date_format_show', 'Y-m-d H:i:s');
				$course->start_date      = $this->techjoomlacommon->getDateInLocal($course->start_date, 0, $date_format_show);*/

				$this->item[$pk] = $course;

				unset($course);
			}
			catch (Exception $e)
			{
				if ($e->getCode() == 404)
				{
					// Need to go thru the error handler to allow Redirect to work.
					$this->setError($e->getMessage());
				}
				else
				{
					$this->setError($e);
					$this->item[$pk] = false;
				}
			}
		}

		return $this->item[$pk];
	}

	/**
	 * Method to get a single record.
	 *
	 * @param   integer  $courseId  Course id
	 * @param   integer  $userId    User id
	 *
	 * @return	mixed	Object on success, false on failure.
	 *
	 * @since	1.6
	 */
	public function getLastAccessedFromCourse($courseId, $userId)
	{
		$lastAttemptedLessonId = $lastAttemptedModuleId = 0;
		$query = $this->_db->getQuery(true);
		$query->select($this->_db->qn(array('lt.lesson_id', 'l.mod_id')));
		$query->from($this->_db->qn('#__tjlms_lesson_track', 'lt'));
		$query->join('INNER', $this->_db->qn('#__tjlms_lessons', 'l') . ' ON (' . $this->_db->qn('l.id') . ' = ' . $this->_db->qn('lt.lesson_id') . ')');
		$query->join('LEFT', $this->_db->qn('#__tjlms_modules', 'm') . ' ON (' . $this->_db->qn('l.mod_id') . ' = ' . $this->_db->qn('m.id') . ')');
		$query->where($this->_db->qn('l.state') . ' = 1 ');
		$query->where($this->_db->qn('m.state') . ' = 1 ');
		$query->where($this->_db->qn('l.course_id') . ' = ' . $this->_db->q((int) $courseId));
		$query->where($this->_db->qn('lt.user_id') . ' = ' . $this->_db->q((int) $userId));

		$query->order($this->_db->qn('lt.last_accessed_on') . " DESC");
		$query->setlimit(1);
		$this->_db->setQuery($query);
		$lesson = $this->_db->loadobject();

		if (!empty($lesson))
		{
			$lastAttemptedLessonId = $lesson->lesson_id;
			$lastAttemptedModuleId = $lesson->mod_id;
		}

		return array("lessonId" => $lastAttemptedLessonId, "moduleId" => $lastAttemptedModuleId);
	}

	/**
	 * Method to fetch subs plans assigned for course
	 *
	 * @param   int  $courseId  id of course
	 * @param   int  $userId    id of user
	 *
	 * @return  object course Sub plan
	 *
	 * @since  1.0
	 */
	public function getSubscriptionPlans($courseId, $userId = 0)
	{
		try
		{
			$userId             = empty($userId) ? Factory::getUser()->id : $userId;
			$allowedViewLevels  = Access::getAuthorisedViewLevels($userId);
			$implodedViewLevels = implode('","', $allowedViewLevels);

			$db    = Factory::getDBO();
			$query = $db->getQuery(true);
			$query->select('*');
			$query->from($db->quoteName('#__tjlms_subscription_plans'));
			$query->where($db->quoteName('course_id') . " = " . $db->quote((int) $courseId));
			$query->where('access IN ("' . $implodedViewLevels . '")');
			$db->setQuery($query);

			return $db->loadobjectlist();
		}
		catch (Exception $e)
		{
			return false;
		}
	}

	/**
	 * Function is used to get lowest and highest price range of the course
	 *
	 * @param   INT  $courseId  course Id.
	 *
	 * @return  Object price range
	 *
	 * @since   _DEPLOY_VERSION_
	 */
	public function coursePriceRange($courseId)
	{
		$courseSubPlans = $this->getSubscriptionPlans($courseId);
		$price          = new stdClass;

		if (count($courseSubPlans) == 1)
		{
			$price->lowestPrice = $courseSubPlans[0]->price;
		}
		else
		{
			usort(
				$courseSubPlans,
				function($plan1, $plan2)
				{
					return $plan1->price > $plan2->price;
				}
			);

			$price->lowestPrice  = reset($courseSubPlans)->price;
			$price->highestPrice = end($courseSubPlans)->price;
		}

		return $price;
	}

	/**
	 * Function is used to display the course price range.
	 *
	 * @param   void  $lowestPrice   lowest price of course.
	 *
	 * @param   void  $highestPrice  highest Price price of course.
	 *
	 * @return  Object displayPrice range
	 *
	 * @since   _DEPLOY_VERSION_
	 */
	public function displayCoursePrice($lowestPrice, $highestPrice)
	{
		$displayPrice = '';

		if ($lowestPrice && $highestPrice)
		{
			$displayPrice = $lowestPrice . ' - ' . $highestPrice;
		}
		else
		{
			$displayPrice = $lowestPrice;
		}

		return $displayPrice;
	}

	/**
	 * Check if Certificate already issued
	 *
	 * @param   INT  $courseId  Course ID
	 *
	 * @param   INT  $userId    User ID
	 *
	 * @return  object Tj Certificate's Certificate object
	 *
	 * @since  1.3.32
	 */
	public function checkCertificateIssued($courseId, $userId)
	{
		$tjCert = TJCERT::Certificate();

		return $tjCert::getIssued($this->client, $courseId, $userId);
	}
}
