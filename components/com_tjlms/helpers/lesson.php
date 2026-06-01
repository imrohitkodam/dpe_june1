<?php
/**
 * @package     TJLms
 * @subpackage  com_shika
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Table\Table;
jimport('joomla.filesystem.file');
jimport('joomla.filesystem.folder');
jimport('techjoomla.common');

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Registry\Registry;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Filesystem\File;

/**
 * Tjlms course helper.
 *
 * @since  1.0.0
 */
class TjlmsLessonHelper
{
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
		$this->comtjlmsHelper       = new comtjlmsHelper;
		$this->techjoomlacommon = new TechjoomlaCommon;
	}

	/**
	 * Function to get lesson
	 *
	 * @param   int  $lesson_id  id of lesson
	 *
	 * @return  Object  $statusDetails
	 *
	 * @since  1.0.0
	 */
	public function getLesson($lesson_id)
	{
		try
		{
			$db   = Factory::getDBO();
			$query = $db->getQuery(true);
			$query->select(array('l.*', $db->qn('m.sub_format'), $db->qn('m.source')));
			$query->from($db->qn('#__tjlms_lessons') . ' as l');
			$query->join('left', $db->qn('#__tjlms_media') . ' m on m.id=l.media_id');
			$query->where($db->qn('l.id') . " = " . $db->q((int) $lesson_id));
			$db->setQuery($query);

			return $db->loadObject();
		}
		catch (Exception $e)
		{
			return false;
		}
	}

	/**
	 * Function to get specific col of specific lesson
	 *
	 * @param   int  $lesson_id      id of lesson
	 * @param   ARR  $columns_array  array of teh columns
	 *
	 * @return  Object  $statusDetails
	 *
	 * @since  1.0.0
	 */
	public function getLessonColumn($lesson_id,$columns_array)
	{
		try
		{
			$db   = Factory::getDBO();
			$query = $db->getQuery(true);
			$query->select($columns_array);
			$query->from($db->qn('#__tjlms_lessons'));
			$query->where($db->qn('id') . " = " . $db->q((int) $lesson_id));
			$db->setQuery($query);

			return $db->loadObject();
		}
		catch (Exception $e)
		{
			return false;
		}
	}

	/**
	 * Function to get last attempt status information for lesson
	 *
	 * @param   int     $lesson_id     id of lesson
	 * @param   int     $oluser_id     id of user
	 * @param   int     $last_attempt  number of attempts
	 * @param   string  $format        lesson format
	 * @param   string  $fromTestView  is the function is called from test view. Introduced not to allow user to
	 * take the lesson if the quiz resume is off and he has closed browser in between while taking the quiz.
	 *
	 * @return  mixed  $statusDetails
	 *
	 * @since  1.0.0
	 */
	public function getLastAttemptStatus($lesson_id, $oluser_id, $last_attempt, $format, $fromTestView=0)
	{
		$completedLastAttempt = 0;

		try
		{
			$db = Factory::getDBO();
			$query = $db->getQuery(true);
			$query->select($db->qn('lesson_status'));
			$query->from($db->qn('#__tjlms_lesson_track'));
			$query->where($db->qn('lesson_id') . " = " . $db->q((int) $lesson_id));
			$query->where($db->qn('user_id') . " = " . $db->q((int) $oluser_id));
			$query->order($db->qn('attempt') . ' DESC');
			$query->setLimit(1);
			$db->setQuery($query);
			$lesson_status = $db->loadResult();
		}
		catch (Exception $e)
		{
			return false;
		}

		if (($format == 'quiz' || $format == 'exercise' || $format == 'feedback') && !$fromTestView)
		{
			$TjlmsCoursesHelper = new TjlmsCoursesHelper;
			$ifResume = $TjlmsCoursesHelper->getQuizResumeAllowd($lesson_id);

			if ($lesson_status && !$ifResume)
			{
				$completedLastAttempt = 1;
			}
		}

		if (($lesson_status == 'completed' || $lesson_status == 'passed' || $lesson_status == 'failed'))
		{
			$completedLastAttempt = 1;
		}

		return $completedLastAttempt;
	}

	/**
	 * Used to get which attempt should user start on clicking on Launch button
	 *
	 * @param   INT  $lessonId  lesson id
	 *
	 * @return 0  if last attempt is incomplete
	 * return attempt > 0 if new attempt
	 * return -1 if attempts are exhausted
	 *
	 * @since 1.0.0
	 * */
	public function getAttempttobeLaunched($lessonId)
	{
		$db = Factory::getDBO();
		$oluser_id = Factory::getUser()->id;

		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjlms/tables');
		$lesson = Table::getInstance('Lesson', 'TjlmsTable', array('dbo', $db));
		$lesson->load($lessonId);

		$attemptsDone = $this->getlesson_total_attempts_done($lesson->id, $oluser_id);

		if ($attemptsDone == 0)
		{
			$attempt = 1;

			return $attempt;
		}

		$attempt = 0;
		$attempts_allowed = $lesson->no_of_attempts;

		/*ATTEMPT == 0 --> FOR OLD ATTEMPT....WILL ASK FOR RESUME
		ATTEMPT + 1 --> NEW ATTEMPT
		ATTEMPT -1 --> ATTMEPT NOT ALLOWED*/

		if ($oluser_id > 0)
		{
			$trackdata = $this->comtjlmstrackingHelper->istrackpresent($lesson->id, $attemptsDone, $oluser_id);

			if (!empty($trackdata))
			{
				$status = $trackdata->lesson_status;

				if (!$lesson->resume)
				{
					$status = 'completed';
				}

				// Last attempt is complete
				if (($status == 'completed' || $status == 'passed' || $status == 'failed'))
				{
					if ($attempts_allowed > 0)
					{
						if ($attemptsDone < $attempts_allowed)
						{
							$attempt = $attemptsDone + 1;
						}
						else
						{
							$attempt = -1;
						}
					}
					else
					{
						$attempt = $attemptsDone + 1;
					}
				}
				elseif ($attemptsDone == $attempts_allowed)
				{
					// If last attempt is the last allowed attempt
					$attempt = $attemptsDone;
				}
			}
		}

		return $attempt;
	}

	/**
	 * Get no of completed atttempts by a user for a lesson
	 *
	 * @param   int  $lesson_id  id of lesson
	 *
	 * @param   int  $oluser_id  id of user
	 *
	 * @return   attempts count
	 *
	 * @since   1.0
	 */
	public function getLessonCompletedattempts($lesson_id, $oluser_id)
	{
		try
		{
			$db = Factory::getDBO();
			$query = $db->getQuery(true);
			$query->select("distinct(attempt)");
			$query->from($db->qn('#__tjlms_lesson_track'));
			$query->where($db->qn('lesson_id') . "=" . $db->q((int) $lesson_id));
			$query->where($db->qn('user_id') . " = " . $db->q((int) $oluser_id));
			$query->where("(lesson_status='completed' OR lesson_status='passed' OR lesson_status ='failed')");
			$db->setQuery($query);

			return $db->loadColumn();
		}
		catch (Exception $e)
		{
			return false;
		}
	}

	/**
	 * Get number of attempts done or can called as last attempt of a user against a lesson
	 *
	 * @param   int  $lesson_id  id of lesson
	 *
	 * @param   int  $user_id    id of user
	 *
	 * @return   attempts count
	 *
	 * @since   1.0
	 */
	public function getlesson_total_attempts_done($lesson_id, $user_id)
	{
		try
		{
			$db   = Factory::getDBO();
			$query = $db->getQuery(true);
			$query->select("MAX(attempt) as total_attempt");
			$query->from($db->quoteName('#__tjlms_lesson_track'));
			$query->where($db->quoteName('lesson_id') . " = " . $db->quote((int) $lesson_id));
			$query->where($db->quoteName('user_id') . " = " . $db->quote((int) $user_id));
			$db->setQuery($query);
			$total_attempts = $db->loadResult();
		}
		catch (Exception $e)
		{
			return false;
		}

		if ($total_attempts > 0)
		{
			return $total_attempts;
		}
		else
		{
			return 0;
		}
	}

	/**
	 * function get Lesson Format data
	 *
	 * @param   int    $lesson_id  Lesson ID
	 * @param   Array  $select     Array
	 *
	 * @return Quiz resume support
	 *
	 * @since 1.0.0
	 */
	public function getLessonFormatdata($lesson_id, $select)
	{
		try
		{
			$db = Factory::getDbo();
			$query = $db->getQuery(true);
			$query->select($select);
			$query->from($db->quoteName('#__tjlms_media', 'm'));
			$query->join('LEFT', $db->quoteName('#__tjlms_lessons') . ' as l ON l.media_id=m.id');
			$query->where($db->quoteName('l.id') . ' = ' . $db->quote((int) $lesson_id));
			$db->setQuery($query);

			return $db->loadObject();
		}
		catch (Exception $e)
		{
			return false;
		}
	}

	/**
	 * Get grade user has achieved with his all atttemptsfor a lesson
	 *
	 * @param   int  $lesson_id     id of lesson
	 * @param   int  $oluser_id     id of user
	 * @param   int  $attemptsdone  total attempts done by a user
	 *
	 * @return   Oject of lesson status and score
	 *
	 * @since   1.0
	 */
	public function getLessonScorebyAttemptsgrading($lesson_id, $oluser_id, $attemptsdone = 0)
	{
		$lesson = self::getLesson($lesson_id);

		$statusandscore = $this->comtjlmstrackingHelper->getLessonattemptsGrading($lesson, $oluser_id, $attemptsdone);

		return $statusandscore;
	}

	/**
	 * Get all the lessons name
	 *
	 * @param   STRING  $lesson_array  lessons ID , seperated
	 *
	 * @return  array
	 *
	 * @since  1.0.0
	 */
	public function getLessonsName($lesson_array)
	{
		$allLessons = array();
		$lessons = array_filter(explode(',', $lesson_array));

		try
		{
			$db = Factory::getDBO();

			foreach ($lessons as $eachLesson)
			{
				$query = $db->getQuery(true);
				$query->select($db->qn('l.title'));
				$query->from($db->qn('#__tjlms_lessons', 'l'));
				$query->where($db->qn('l.id') . '=' . $db->q((int) $eachLesson));
				$db->setQuery($query);

				$allLessons[] = $db->loadresult();
			}

			return $allLessons;
		}
		catch (Exception $e)
		{
			return false;
		}
	}

	/**
	 * Function to get attempt status information for lesson
	 *
	 * @param   int  $lesson_id     id of lesson
	 * @param   int  $oluser_id     id of user
	 * @param   int  $last_attempt  number of attempts
	 *
	 * @return  Object  $statusDetails
	 *
	 * @since  1.0.0
	 */
	public function getLessonStatusDetails($lesson_id, $oluser_id, $last_attempt)
	{
		$statusDetails = '';

		if ($track = $this->comtjlmstrackingHelper->istrackpresent($lesson_id, '1', $oluser_id))
		{
			$statusDetails = new stdClass;
			$statusDetails->started_on = Text::_('COM_TJLMS_NEVER');
			$statusDetails->last_accessed_on = Text::_('COM_TJLMS_NEVER');
			$statusDetails->total_time_spent = 0;

			$db = Factory::getDBO();

			// Add Table Path
			Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjlms/tables');
			$lessonTrack = Table::getInstance('lessontrack', 'TjlmsTable', array('dbo', $db));
			$lessonTrack->load(array('lesson_id' => $lesson_id, 'user_id' => $oluser_id, 'attempt' => 1));

			if ($lessonTrack->id)
			{
				$lmsparams = ComponentHelper::getParams('com_tjlms');
				$date_format_show = $lmsparams->get('date_format_show', 'Y-m-d H:i:s');

				$statusDetails->started_on = $this->techjoomlacommon->getDateInLocal($lessonTrack->timestart, 0, $date_format_show);

				$lessonTrack->load(array('lesson_id' => $lesson_id, 'user_id' => $oluser_id, 'attempt' => $last_attempt));

				$statusDetails->last_accessed_on = $this->techjoomlacommon->getDateInLocal($lessonTrack->last_accessed_on, 0, $date_format_show);

				$total_time_spent = $this->tjlmsdbhelperObj->get_records('SUM(TIME_TO_SEC(time_spent)) as time_spent', 'tjlms_lesson_track', array(
				'lesson_id' => $lesson_id,
				'user_id' => $oluser_id
				), '', 'loadResult');

				$statusDetails->total_time_spent = $this->comtjlmsHelper->secToHours($total_time_spent);
			}
		}

		return $statusDetails;
	}

	/**
	 * Function to get eligibility criteria of user
	 *
	 * @param   int  $lesson_id        id of lesson
	 * @param   int  $oluser_id        id of user
	 * @param   int  $eligibility_str  eligibility criteria
	 *
	 * @return  array  $res
	 *
	 * @since  1.0.0
	 */
	public function getLessonEligibiltyCriteria($lesson_id, $oluser_id, $eligibility_str)
	{
		$eligibilty_criteria = $res = array();
		$res['eligible_toaceess'] = 1;

		if (!empty($eligibility_str))
		{
			$prerequisites = explode(',', $eligibility_str);
			$prerequisites = array_filter($prerequisites);
			$completed_prerequisites = 0;

			try
			{
				foreach ($prerequisites as $eligibal_lesson_id)
				{
					$eligibal_lesson = $this->tjlmsdbhelperObj->get_records('*', 'tjlms_lessons', array(
						"id" => (int) $eligibal_lesson_id
					), '', 'loadObject');
					$eligibility = $this->comtjlmstrackingHelper->getLessonattemptsGrading($eligibal_lesson, $oluser_id);
					$eligibilty_criteria[$eligibal_lesson_id] = $eligibility;

					if ($eligibility)
					{
						if ($eligibility->lesson_status == 'completed' || $eligibility->lesson_status == 'passed')
						{
							$completed_prerequisites++;
						}
					}
				}
			}
			catch (Exception $e)
			{
				return false;
			}

			if ($completed_prerequisites < count($prerequisites))
			{
				$res['eligible_toaceess'] = 0;
			}
		}

		$res['eligibilty_criteria'] = $eligibilty_criteria;

		return $res;
	}

	/**
	 * Get all the SCOs of a lesosn and users status against each
	 *
	 * @param   int  $lesson_id  id of lesson
	 * @param   int  $oluser_id  id of user
	 *
	 * @return  array  $toc_tree
	 *
	 * @since  1.0.0
	 */
	public function getLesosnScormData($lesson_id, $oluser_id)
	{
		try
		{
			$comtjlmsScormHelper = new comtjlmsScormHelper;
			$scorm_id = $this->tjlmsdbhelperObj->get_records('id', 'tjlms_scorm', array(
				'lesson_id' => (int) $lesson_id
			), '', 'loadResult');
			$toc_tree = $comtjlmsScormHelper->getTocTree($scorm_id, $oluser_id);

			return $toc_tree;
		}
		catch (Exception $e)
		{
			return false;
		}
	}

	/**
	 * Function used to lesson image
	 *
	 * @param   Array   $lesson     Lesson
	 * @param   STRING  $imageSize  Image size
	 *
	 * @return  STRING  Image to use path
	 *
	 * @since  1.0.0
	 */
	/*public function getLessonImage($lesson, $imageSize)
	{
		require_once JPATH_ROOT . '/components/com_tjlms/libraries/storage.php';
		$this->Tjstorage = new Tjstorage;

		$tjlmsparams      = ComponentHelper::getParams('com_tjlms');
		$lessonImgPath    = JPATH_SITE . '/' . $tjlmsparams->get('lesson_image_upload_path');
		$lessonimgRelPath = Uri::root(true) . '/' . $tjlmsparams->get('lesson_image_upload_path');
		$lessonDefaultImg = Uri::root(true) . '/media/com_tjlms/images/default/lesson.png';

		$lessonImage = $lesson['image'];

		if (!empty($lessonImage))
		{
			$storage   = $this->Tjstorage->getStorage($lesson['storage']);
			$imageToUse = $storage->getURI($tjlmsparams->get('lesson_image_upload_path') . $imageSize . $lesson['image']);

			if ($lesson['storage'] == 'local')
			{
				if (!File::exists($lessonImgPath . $imageSize . $lessonImage))
				{
					$imageToUse = $lessonDefaultImg;
				}
			}
		}
		else
		{
			$imageToUse = $lessonDefaultImg;
		}

		return $imageToUse;
	}*/

	/**
	 * function used to get Quiz resume support.
	 *
	 * @param   int  $lesson_id  Lesson ID
	 *
	 * @return Quiz resume support
	 *
	 * @since 1.0.0
	 */
	public function getQuizResumeAllowd($lesson_id)
	{
		try
		{
			$db = Factory::getDbo();
			$query = $db->getQuery(true);
			$query->select($db->quoteName('q.resume'));
			$query->from($db->quoteName('#__tmt_tests', 'q'));
			$query->join('INNER', $db->quoteName('#__tjlms_tmtquiz') . ' as t ON t.test_id=q.id');
			$query->where($db->quoteName('t.lesson_id') . ' = ' . $db->quote($lesson_id));
			$db->setQuery($query);

			return $db->loadResult();
		}
		catch (Exception $e)
		{
			return false;
		}
	}

	/**
	 * Function used to delete lesson
	 *
	 * @param   string  $courseId  A prefix for the store id.
	 * @param   string  $modId     A prefix for the store id.
	 * @param   string  $lessonId  A prefix for the store id.
	 *
	 * @return  JSON
	 */
	public function deletLesson($courseId, $modId, $lessonId)
	{
		$db	= Factory::getDBO();

		if (!is_array($lessonId))
		{
			$lessonIds[] = $lessonId;
		}
		else
		{
			$lessonIds = $lessonId;
		}

		$lessonIdString = implode(',', $db->q($lessonIds));

		try
		{
			if ($modId != 0)
			{
				$query = $db->getQuery(true);
				$query->select($db->qn('ordering'));
				$query->from($db->qn('#__tjlms_lessons'));
				$query->where($db->qn('course_id') . ' = ' . $db->q($courseId));
				$query->where($db->qn('mod_id') . ' = ' . $db->q($modId));
				$query->where('id IN (' . $lessonIdString . ')');
				$db->setQuery($query);
				$currentOrder = $db->loadResult();

				if (!$currentOrder)
				{
					$currentOrder = 0;
				}

				$query = $db->getQuery(true);
				$fields = array($db->quoteName('ordering') . ' = ordering-1');

				$conditions = array(
					$db->quoteName('course_id') . ' = ' . $db->quote((int) $courseId),
					$db->quoteName('mod_id') . ' = ' . $db->quote((int) $modId),
					$db->quoteName('ordering') . ' > ' . $db->quote($currentOrder)
				);

				$query->update($db->quoteName('#__tjlms_lessons'))->set($fields)->where($conditions);
				$db->setQuery($query);
				$db->execute();
			}

			if (!empty($lessonIds))
			{
				// Get media Ids of selected course
				$query = $db->getQuery(true);
				$query->select($db->qn('media_id'));
				$query->from($db->qn('#__tjlms_lessons'));
				$query->where($db->qn('id') . 'IN (' . $lessonIdString . ')');
				$db->setQuery($query);
				$mediaIds = $db->loadColumn();

				PluginHelper::importPlugin('system');

				if (!Factory::getApplication()->triggerEvent('onBeforeLessonDelete', array($courseId, $lessonIds, $mediaIds)))
				{
					return 0;
				}

				// Delete all the lessons related to course
				$query = $db->getQuery(true);
				$conditions = array($db->quoteName('id') . ' IN (' . $lessonIdString . ')');
				$query->delete($db->quoteName('#__tjlms_lessons'));
				$query->where($conditions);
				$db->setQuery($query);
				$results = $db->execute();

				$lessonDeletion = Factory::getApplication()->triggerEvent('onAfterLessonDelete', array($courseId, $lessonIds, $mediaIds));
			}
		}
		catch (Exception $e)
		{
			return false;
		}

		if ($lessonDeletion)
		{
			return 1;
		}
		else
		{
			return 0;
		}
	}

	/**
	 * Delete all lesson tracks for the lessons
	 *
	 * @param   ARRAY  $mediaIds  array of media ids
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	public function deleteLessonMedia($mediaIds)
	{
		try
		{
			$db = Factory::getDbo();
			$mediaIdsString = implode(',', $db->q($mediaIds));

			// Delete media entries
			$query = $db->getQuery(true);
			$query->select('source');
			$query->from($db->quoteName('#__tjlms_media'));
			$query->where('id IN (' . $mediaIdsString . ')');
			$db->setQuery($query);
			$mediaFilesName = $db->loadColumn();

			foreach ($mediaFilesName as $mediaFile)
			{
				$file = JPATH_SITE . '/media/com_tjlms/lessons/' . $mediaFile;

				if (File::exists($file))
				{
					File::delete($file);
				}
			}

			// Delete media entry
			$query      = $db->getQuery(true);
			$conditions = array($db->quoteName('id') . ' IN (' . $mediaIdsString . ')');
			$query->delete($db->quoteName('#__tjlms_media'));
			$query->where($conditions);
			$db->setQuery($query);
			$mediaResult = $db->execute();
		}
		catch (Exception $e)
		{
			return false;
		}
	}

	/**
	 * Delete all lesson tracks for the lessons or Delete single track for specific to user id and course id
	 *
	 * @param   ARRAY  $lessonIds  array of lesson ids
	 * @param   INT    $userId     user Id
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	public function deleteLessonTracks($lessonIds, $userId = null)
	{
		try
		{
			$db = Factory::getDbo();
			$lessonIdsString = implode(',', $db->quote($lessonIds));

			// Delete all lesson tracks related to selected course lessons
			$query      = $db->getQuery(true);
			$conditions = array(
				$userId ? $db->quoteName('user_id') . '=' . $db->quote((int) $userId)  : true,
				$db->quoteName('lesson_id') . ' IN (' . $lessonIdsString . ')'
			);

			$query->delete($db->quoteName('#__tjlms_lesson_track'));
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
	 * Delete all scorm lesson data tracks for the lessons
	 *
	 * @param   ARRAY  $lessonIds  array of lesson ids
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	public function deleteScormData($lessonIds)
	{
		try
		{
			$db = Factory::getDbo();
			$lessonIdsString = implode(',', $db->q($lessonIds));

			// Get scorm id
			$query = $db->getQuery(true);
			$query->select(array($db->qn('sc.id', 'scorm_id'), $db->qn('scos.id', 'scoes_id'), $db->qn('lesson_id', 'les_id')));
			$query->from($db->qn('#__tjlms_scorm', 'sc'));
			$query->join('INNER', '#__tjlms_scorm_scoes as scos ON scos.scorm_id=sc.id');
			$query->where($db->qn('sc.lesson_id') . 'IN (' . $lessonIdsString . ')');
			$db->setQuery($query);
			$lessonsInfo = $db->loadObjectList();

			if ($lessonsInfo)
			{
				foreach ($lessonsInfo as $lessonInfo)
				{
					$scormIds[] = $lessonInfo->scorm_id;
					$scoesIds[] = $lessonInfo->scoes_id;

					$folderScorm = JPATH_SITE . '/media/com_tjlms/lessons/' . $lessonInfo->les_id;

					if (Folder::exists($folderScorm))
					{
						Folder::delete($folderScorm);
					}
				}

				// Delete from SCORM TABLE
				$query = $db->getQuery(true);
				$conditions = array($db->quoteName('lesson_id') . ' IN (' . $lessonIdsString . ')');
				$query->delete($db->quoteName('#__tjlms_scorm'));
				$query->where($conditions);
				$db->setQuery($query);
				$scormDelete = $db->execute();

				// Delete from scoes tables
				$scormIdsString = implode(',', $db->q($scormIds));

				// Entries from Table #_tjlms_scorm_scoes deleted
				$query = $db->getQuery(true);
				$conditions_scormid = array(
					$db->quoteName('scorm_id') . ' IN (' . $scormIdsString . ')'
				);

				$query->delete($db->quoteName('#__tjlms_scorm_scoes'));
				$query->where($conditions_scormid);
				$db->setQuery($query);
				$result = $db->execute();

				// Delete Sceos table data for this scorm
				$deleteScormScoesData = $this->deleteScormScoesData($scoesIds);
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
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	public function deleteScormScoesData($scoesIds)
	{
		try
		{
			$db = Factory::getDbo();
			$scoesIdsString = implode(',', $db->qn($scoesIds));

			// Entries from Table #_tjlms_scorm_scoes_data deleted
			$query = $db->getQuery(true);
			$conditions_scoesid = array(
				$db->quoteName('sco_id') . ' IN (' . $scoesIdsString . ')'
			);

			$query->delete($db->quoteName('#__tjlms_scorm_scoes_data'));
			$query->where($conditions_scoesid);
			$db->setQuery($query);
			$scormdata = $db->execute();

			// Entries from  Table #_tjlms_scorm_scoes_track deleted
			$query = $db->getQuery(true);
			$query->delete($db->quoteName('#__tjlms_scorm_scoes_track'));
			$query->where($conditions_scoesid);
			$db->setQuery($query);
			$scormtrack = $db->execute();

			// Table #_tjlms_scorm_seq_mapinfo
			$query = $db->getQuery(true);
			$query->delete($db->quoteName('#__tjlms_scorm_seq_mapinfo'));
			$query->where($conditions_scoesid);
			$db->setQuery($query);
			$scormtrack1 = $db->execute();

			// Table #_tjlms_scorm_seq_objective
			$query = $db->getQuery(true);
			$query->delete($db->quoteName('#__tjlms_scorm_seq_objective'));
			$query->where($conditions_scoesid);
			$db->setQuery($query);
			$scormtrack = $db->execute();

			// Table #_tjlms_scorm_seq_rolluprule
			$query = $db->getQuery(true);
			$query->delete($db->quoteName('#__tjlms_scorm_seq_rolluprule'));
			$query->where($conditions_scoesid);
			$db->setQuery($query);
			$scormtrack2 = $db->execute();

			// Table #_tjlms_scorm_seq_rolluprulecond
			$query = $db->getQuery(true);
			$query->delete($db->quoteName('#__tjlms_scorm_seq_rolluprulecond'));
			$query->where($conditions_scoesid);
			$db->setQuery($query);
			$scormtrack3 = $db->execute();

			// Table #_tjlms_scorm_seq_rulecond
			$query = $db->getQuery(true);
			$query->delete($db->quoteName('#__tjlms_scorm_seq_rulecond'));
			$query->where($conditions_scoesid);
			$db->setQuery($query);
			$scormtrack4 = $db->execute();

			// Table #_tjlms_scorm_seq_ruleconds
			$query = $db->getQuery(true);
			$query->delete($db->quoteName('#__tjlms_scorm_seq_ruleconds'));
			$query->where($conditions_scoesid);
			$db->setQuery($query);
			$scormtrack5 = $db->execute();
		}
		catch (Exception $e)
		{
			return false;
		}
	}

	/**
	 * Delete all lesson quize
	 *
	 * @param   ARRAY  $lessonIds  array of lesson ids
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	public function deleteQuiz($lessonIds)
	{
		try
		{
			$db = Factory::getDbo();
			$lessonIdsString = implode(',', $lessonIds);

			// Delete all lesson tracks related to selected course lessons
			$query      = $db->getQuery(true);
			$conditions = array($db->quoteName('lesson_id') . ' IN (' . $lessonIdsString . ')');
			$query->delete($db->quoteName('#__tjlms_tmtquiz'));
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
	 * Function to get Item id
	 *
	 * @param   INTEGER  $lessonId  Id of the lesson
	 * @param   STRING   $layout    layout of view
	 *
	 * @return  INT  Item id
	 *
	 * @since  1.0.0
	 */
	public function getLessonItemid($lessonId, $layout = '')
	{
		$app      = Factory::getApplication();
		$menu     = $app->getMenu();

		$itemid = 0;

		if ($lessonId)
		{
			/*Get the itemid of the menu which is pointed to individual course URL*/
			$menuItem = $menu->getItems('link', 'index.php?option=com_tjlms&view=lesson&lesson_id=' . $lessonId, true);

			if (!empty($menuItem))
			{
					return $menuItem->id;
			}

			/*Get the itemid of the menu which is pointed to course category URL*/
			$course = $this->getLessonColumn($lessonId, array('course_id'));
			$tjlmsCoursesHelper = new TjlmsCoursesHelper;
			$itemid = $tjlmsCoursesHelper->getCourseItemid($course->course_id);
		}

		return $itemid;
	}

	/**
	 * Used to update the tracking
	 *
	 * @param   ARRAY   $lesson        Lesson object
	 * @param   ARRAY   $course        Course object
	 * @param   INT     $userId        User id
	 * @param   string  $fromTestView  is the function is called from test view. Introduced not to allow user to
	 * take the lesson if the quiz resume is off and he has closed browser in between while taking the quiz.
	 *
	 * @return  true
	 *
	 * @since 1.0.0
	 * */
	public function usercanAccess($lesson, $course, $userId, $fromTestView=0)
	{
		$quiz_formats = array('tmtQuiz', 'form', 'quiz', 'exercise', 'feedback');
		$db           = Factory::getDBO();
		$params       = ComponentHelper::getParams('com_tjlms');

		$comtjlmsHelper = new comtjlmsHelper;
		$courseLink     = $comtjlmsHelper->tjlmsRoute('index.php?option=com_tjlms&view=course&id=' . $course->id);

		$input     = Factory::getApplication()->input;
		$lessonUrl = $input->get('lesson_id', '', 'INT');
		$link      = '<a href="' . $courseLink . '">' . Text::_('COM_TJLMS_CLICK_HERE') . '</a>';

		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjlms/tables');
		$category = Table::getInstance('Category', 'JTable', array('dbo', $db));
		$category->load(array('id' => $course->catid, 'extension' => 'com_tjlms'));

		$result['msg']         = '';
		$result['access']      = 1;
		$result['no_time_log'] = 0;

		// Check if lesson, course, Category is published.
		if (!$category->id || $category->published != 1 || $course->state != 1 || $lesson->state != 1)
		{
			$result['msg']    = Text::_('JNOTPUBLISHEDYET');
			$result['access'] = 0;

			return $result;
		}

		$currentDate = Factory::getDate()->toSql();

		// Check if the lesson has started
		if ((strtotime($lesson->start_date) > strtotime($currentDate)))
		{
			$result['msg']    = Text::_('JNOTPUBLISHEDYET');
			$result['access'] = 0;

			return $result;
		}

		// Check if the lesson has expired
		if ((strtotime($lesson->end_date) < strtotime($currentDate)) && $lesson->end_date != Factory::getDbo()->getNullDate())
		{
			$result['msg']    = Text::_('JEXPIRED');
			$result['access'] = 0;

			return $result;
		}

		if ($userId > 0)
		{
			// Check if the user is the course creator
			$allow_creator = $params->get('allow_creator');

			if ($allow_creator == 1 && ($userId == $course->created_by))
			{
				$checkifuserenroled    = 1;
				$result['no_time_log'] = 1;
			}
			else
			{
				JLoader::import('components.com_tjlms.models.course', JPATH_SITE);
				$TjlmsModelcourse   = new TjlmsModelcourse;
				$checkifuserenroled = $TjlmsModelcourse->checkifuserenroled($course->id, $userId, $course->type);
			}

			if ($checkifuserenroled != 1)
			{
				$result['no_time_log'] = 1;

				if ($lesson->in_lib != 1)
				{
					$result['msg']    = Text::_('COM_TJLMS_ENROLL_ACCESS');
					$result['access'] = 0;
				}

				return $result;
			}

			if ($checkifuserenroled == 1)
			{
				// Attempts Exhausted
				if ($lesson->no_of_attempts > 0)
				{
					$attemptsdonebyuser = $this->getlesson_total_attempts_done($lesson->id, $userId);

					$completed_last_attempt = $this->getLastAttemptStatus($lesson->id, $userId, $attemptsdonebyuser, $lesson->format, $fromTestView);

					if ($attemptsdonebyuser >= $lesson->no_of_attempts && $completed_last_attempt	== 1)
					{
						$result['msg']    = Text::_('COM_TJLMS_ATTEMPTS_EXHAUSTED_TOOLTIP');
						$result['access'] = 0;

						return $result;
					}
				}

				// Elegilibility criteria
				if ($lesson->eligibility_criteria)
				{
					$elegibilityResult = $this->getLessonEligibiltyCriteria($lesson->id, $userId, $lesson->eligibility_criteria);

					if ($elegibilityResult['eligible_toaceess'] == 0)
					{
						$eligibilty_lessons = $this->getLessonsName($lesson->eligibility_criteria);
						$eligibilty_lessons = implode(',', $eligibilty_lessons);

						$result['msg'] = Text::sprintf('COM_TJLMS_NOT_COMPLETED_PREREQUISITES_TOOLTIP', Text::_("COM_TJLMS_TYPE_LESSON"), $eligibilty_lessons);
						$result['access'] = 0;

						return $result;
					}
				}

				$plugformat       = 'tj' . $lesson->format;
				$format_subformat = explode('.', $lesson->sub_format);
				$sub_format       = $format_subformat[0];

				$attemptsdonebyuser = $this->getlesson_total_attempts_done($lesson->id, $userId);

				$lesson_status = '';
				$db = Factory::getDbo();

				// Create a new query object.
				$query = $db->getQuery(true);

				$query->select('lesson_status');
				$query->from($db->quoteName('#__tjlms_lesson_track'));
				$query->where($db->quoteName('lesson_id') . " = " . $db->quote($lesson->id));
				$query->where($db->quoteName('user_id') . " = " . $db->quote($userId));
				$query->where($db->quoteName('attempt') . " = " . $db->quote($attemptsdonebyuser));

				// Reset the query using our newly populated query object.
				$db->setQuery($query);

				// Load the results as a list of stdClass objects (see later for more options on retrieving data).
				$results = $db->loadColumn();
				$db->setQuery($query);

				$lesson_status = $db->loadResult();

				if (!empty($lesson_status && $lesson_status == 'AP') && $attemptsdonebyuser != 0)
				{
					$result['access'] = 0;
					$result['msg'] = Text::_('COM_TJLMS_QUIZ_WAIT_FOR_REVIEW');
				}
			}
			elseif ($course->type == 0)
			{
				$quiz_format = array('tmtQuiz', 'form', 'quiz', 'exercise', 'feedback');

				if ($course->access != 1 || in_array($lesson->format, $quiz_format)  || $lesson->eligibility_criteria)
				{
					// Check if course is free and do not have public access or having quiz type
					if ($lessonUrl)
					{
						$result['msg'] = Text::sprintf('COM_TJLMS_ENROLL_TO_ACCESS', $link);
					}
					else
					{
						$result['msg'] = Text::sprintf('COM_TJLMS_ENROLL_ACCESS');
					}

					$result['access'] = 0;
				}
				else
				{
					$result['no_time_log'] = 1;
				}

				return $result;
			}
			elseif ($course->type == 1 && $lesson->free_lesson == 0)
			{
				// Check if course is paid and lesson is not a free sample and user dont have a subscription
				if ($lessonUrl)
				{
					$result['msg'] = Text::sprintf('COM_TJLMS_ENROLL_TO_ACCESS', $link);
				}
				else
				{
					$result['msg'] = Text::sprintf('COM_TJLMS_ENROLL_ACCESS');
				}

				$result['access'] = 0;

				return $result;
			}
		}
		elseif (in_array($lesson->format, $quiz_formats))
		{
			$result['msg']    = Text::_('COM_TJLMS_GUEST_NOATTEMPT_QUIZ');
			$result['access'] = 0;

			return $result;
		}
		elseif ($lesson->format == 'event')
		{
			$result['msg']    = Text::_('COM_TJLMS_GUEST_NOATTEMPT_EVENT');
			$result['access'] = 0;

			return $result;
		}
		elseif (($course->type == 1 && $lesson->free_lesson == 0) ||
				($course->type == 0 && $course->access != 1) ||
				($lesson->eligibility_criteria))
		{
			$result['msg']    = Text::_('COM_TJLMS_LOGIN_TO_ACCESS');
			$result['access'] = 0;

			return $result;
		}

		if ($result['access'] && $lesson->format != 'tmtQuiz' && isset($lesson->sub_format) && $lesson->sub_format
			&& isset($lesson->format) && $lesson->format)
		{
			$plg_type         = 'tj' . $lesson->format;
			$format_subformat = !empty($lesson->sub_format) ? explode('.', $lesson->sub_format) : '';
			$plg_name         = isset($format_subformat[0])?$format_subformat[0]:'';
			$enabled          = PluginHelper::isEnabled($plg_type, $plg_name);

			if (!$enabled)
			{
				$result['access'] = 0;
				$result['msg']    = Text::_('COM_TJLMS_PLUGIN_DISABLED');
			}
		}

		return $result;
	}
}
