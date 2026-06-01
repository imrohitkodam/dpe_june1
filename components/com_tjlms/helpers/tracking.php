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
use Joomla\CMS\Table\Table;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Date\Date;

jimport('joomla.html.html');
jimport('joomla.html.parameter');
jimport('joomla.utilities.date');
jimport('techjoomla.common');

/**
 * Tracking helper
 *
 * @since  1.0.0
 */
class ComtjlmstrackingHelper
{
	/**
	 * Method acts as a consturctor
	 *
	 * @since   1.0.0
	 */
	public function __construct()
	{
		$this->techjoomlacommon = new TechjoomlaCommon;
	}

	/**
	 * Used to update the tracking
	 *
	 * @param   INT     $lesson_id      id of the lesson whose tracking data is getting updated
	 * @param   INT     $oluser_id      logged in User id
	 * @param   OBJECT  $trackingData   object contating data
	 * @param   INT     $add_prev_time  optional : if 1 the time spent will be added in prevous time
	 *
	 * @return  if of the tracking row
	 *
	 * @since 1.0.0
	 * */
	public function update_lesson_track($lesson_id, $oluser_id, $trackingData, $add_prev_time = 1)
	{
		// Do not log time if course if public and user is not enrolled
		JLoader::import('components.com_tjlms.helpers.lesson', JPATH_SITE);
		JLoader::import('components.com_tjlms.models.course', JPATH_SITE);
		JLoader::import('components.com_tjlms.models.lesson', JPATH_SITE);

		$lessonModel = BaseDatabaseModel::getInstance('Lesson', 'TjlmsModel');
		$courseModel = BaseDatabaseModel::getInstance('Course', 'TjlmsModel');
		$lesson = $lessonModel->getlessondata($lesson_id);
		$course_info = $courseModel->getcourseinfo($lesson->course_id);
		$tjlmsLessonHelper = new TjlmsLessonHelper;

		$usercanAccess = $lessonModel->canUserLaunch($lesson->id, $oluser_id);

		if (!$usercanAccess['access'] || !$usercanAccess['track'])
		{
			return true;
		}

		JLoader::import('components.com_tjlms.models.assessments', JPATH_SITE);

		$assessmentsModel = BaseDatabaseModel::getInstance('Assessments', 'TjlmsModel');
		$assessmentSet = $assessmentsModel->getLessonAssessSet($lesson_id);

		if (!empty($assessmentSet) && ($trackingData->lesson_status == 'completed' || $trackingData->lesson_status == 'passed'))
		{
			$trackingData->lesson_status = 'AP';
			$trackingData->score = 0;
		}

		// End log time

		return $this->storeTrack($lesson_id, $oluser_id, $trackingData, $add_prev_time);
	}

	/**
	 * Used to update the tracking
	 *
	 * @param   INT     $lesson_id      id of the lesson whose tracking data is getting updated
	 * @param   INT     $oluser_id      logged in User id
	 * @param   OBJECT  $trackingData   object contating data
	 * @param   INT     $add_prev_time  optional : if 1 the time spent will be added in prevous time
	 *
	 * @return  if of the tracking row
	 *
	 * @since 1.0.0
	 * */
	public function storeTrack($lesson_id, $oluser_id, $trackingData, $add_prev_time = 1)
	{
		$comtjlmsHelper = new comtjlmsHelper;
		$db = Factory::getDBO();
		$lesson_status = $prev_status = '';

		$attempt = $trackingData->attempt;

		// If lesson_status is provided
		if (isset($trackingData->lesson_status))
		{
			$lesson_status = $trackingData->lesson_status;
		}

		// If lesson id and user id are valid
		if ($lesson_id > 0 && $oluser_id > 0)
		{
			$object                   = new stdClass;
			$object->lesson_id        = $lesson_id;
			$object->user_id          = $oluser_id;
			$object->attempt          = $attempt;
			$object->last_accessed_on = empty($trackingData->last_accessed_on) ? Factory::getDate()->toSQL() :
			$trackingData->last_accessed_on;
			$object->timeend          = Factory::getDate()->toSQL();
			$object->total_content = !empty($trackingData->total_content) ? $trackingData->total_content : 1;

			if (isset($trackingData->score))
			{
				$object->score = $trackingData->score;
			}

			if (!empty($trackingData->lesson_status))
			{
				$object->lesson_status = $trackingData->lesson_status;
			}

			if (!empty($trackingData->total_content))
			{
				$object->total_content = $trackingData->total_content;
			}

			if (isset($trackingData->current_position))
			{
				$object->current_position = $trackingData->current_position;
			}

			$object->modified_date = Factory::getDate()->toSQL();

			Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjlms/tables');
			$lessonTrack = Table::getInstance('Lessontrack', 'TjlmsTable', array('dbo', $db));

			// If entry for attempt, lesson, and user id is present, update the row
			if ($track = $this->istrackpresent($lesson_id, $attempt, $oluser_id))
			{
				$object->id = $track->id;

				if (!empty($trackingData->time_spent))
				{
					$time_spent = $trackingData->time_spent;

					if ($add_prev_time == 1)
					{
						$total_time = $track->time_spent + $time_spent;
						$object->time_spent = $this->secToTime($total_time);
					}
					else
					{
						$object->time_spent = $this->secToTime($time_spent);
					}
				}

				// Get previous status
				$prev_status = $this->getLessonStatus($track->id);

				$TjlmsLessonHelper = new TjlmsLessonHelper;
				$lessonFormat      = $TjlmsLessonHelper->getLessonColumn($lesson_id, 'format');

				if ($lessonFormat->format == 'scorm' && $prev_status == 'passed')
				{
					$object->lesson_status = $prev_status;
				}
				elseif ($lessonFormat->format == 'scorm' && $prev_status == 'incomplete' && $object->lesson_status == 'started')
				{
					$object->lesson_status = $prev_status;
				}
				elseif ($lessonFormat->format == 'scorm' && $prev_status == 'failed' && $object->lesson_status == 'incomplete')
				{
					$object->lesson_status = $prev_status;
				}

				if ($prev_status == 'completed' && $lessonFormat->format != 'scorm')
				{
					$object->lesson_status = $prev_status;
				}

				$lessonTrack->save($object);
				$track_id = $track->id;
			}
			else
			{
				$object->id = '';

				if (!empty($trackingData->time_spent))
				{
					$object->time_spent = $this->secToTime($trackingData->time_spent);
				}
				else
				{
					$object->time_spent = '00:00:00';
				}

				if (empty($trackingData->lesson_status))
				{
					$object->lesson_status = 'started';
				}

				$object->live = 0;
				$object->modified_by = 0;
				$object->timestart = empty($trackingData->timestart) ? Factory::getDate()->toSQL() :
				$trackingData->timestart;
				$lessonTrack->save($object);
				$track_id = $db->insertid();

				PluginHelper::importPlugin('system');
				Factory::getApplication()->triggerEvent('onAfterLessonAttemptstarted', array( $lesson_id, $attempt, $oluser_id ));
			}

			// TRIGGERS....FOR LESSON AND COURSE COMPLETION.
			if ($lesson_status == 'completed' || $lesson_status == 'passed' || $lesson_status == 'failed')
			{
				$TjlmsLessonHelper = new TjlmsLessonHelper;
				$courseObj         = $TjlmsLessonHelper->getLessonColumn($lesson_id, 'course_id');
				$courseId          = $courseObj->course_id;

				if ($prev_status != 'completed' && $prev_status != 'passed' && $prev_status != 'failed')
				{
					$lessonFormat = $TjlmsLessonHelper->getLessonColumn($lesson_id, 'format');

					// TRRIGER FOR SIMPLE LESSON COMPLETION WITHOUT CONSIDERING ATTEMPT GRADING
					PluginHelper::importPlugin('system');

					Factory::getApplication()->triggerEvent('onAfterLessonAttemptEnd', array(
																		$lesson_id,
																		$attempt,
																		$oluser_id,
																		$lessonFormat->format
																	)
										);

					if ($lesson_status == 'completed' || $lesson_status == 'passed')
					{
						$consider_marksFlag = $TjlmsLessonHelper->getLessonColumn($lesson_id, 'consider_marks');

						if ($consider_marksFlag->consider_marks == 1)
						{
							$statusandscore            = $TjlmsLessonHelper->getLessonScorebyAttemptsgrading($lesson_id, $oluser_id);

							if ($statusandscore->lesson_status == 'completed' || $statusandscore->lesson_status == 'passed')
							{
								// TRRIGER FOR SIMPLE LESSON COMPLETION WITH CONSIDERING ATTEMPT GRADING
								Factory::getApplication()->triggerEvent('onAfterLessonCompletion', array(
																		$lesson_id,
																		$attempt,
																		$oluser_id
																	)
										);
							}
						}

						// CODE TO CHECK FOR COURSE COMPLETION
						$isCourseCompleted = $this->checkIfCourseCompletd($lesson_id, $attempt, $oluser_id);
					}
				}
				elseif ($courseId && TjLms::course($courseId)->certificate_id)
				{
					$this->addCourseTrackEntry($courseId, $oluser_id, $lesson_id);
				}
			}

			return $track_id;
		}

		return true;
	}

	/**
	 * Function to get lesson status
	 *
	 * @param   INT  $trackId  Lesson Track ID
	 *
	 * @return  $lessonStatus
	 *
	 * @since  1.0.0
	 */
	public function getLessonStatus($trackId)
	{
		try
		{
			$db = Factory::getDBO();
			$query = $db->getQuery(true);
			$query->select($db->quoteName(array('lesson_status')));
			$query->from($db->quoteName('#__tjlms_lesson_track'));
			$query->where($db->quoteName('id') . " = " . $db->quote((int) $trackId));
			$db->setQuery($query);

			return $db->loadresult();
		}
		catch (Exception $e)
		{
			return false;
		}
	}

	/**
	 * Function to check if course completed
	 *
	 * @param   INT  $lesson_id  Lesson ID
	 * @param   INT  $attempt    Attempt
	 * @param   INT  $oluser_id  User ID
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	public function checkIfCourseCompletd($lesson_id, $attempt, $oluser_id)
	{
		$TjlmsLessonHelper = new TjlmsLessonHelper;
		$obj = $TjlmsLessonHelper->getLessonColumn($lesson_id, 'course_id');

		try
		{
			$db   = Factory::getDBO();
			$query = $db->getQuery(true);
			$query->select($db->quoteName(array("no_of_lessons","completed_lessons")));
			$query->from($db->quoteName('#__tjlms_course_track'));
			$query->where($db->quoteName('course_id') . " = " . $db->quote((int) $obj->course_id));
			$query->where($db->quoteName('user_id') . " = " . $db->quote((int) $oluser_id));
			$db->setQuery($query);
			$track = $db->loadObject();
		}
		catch (Exception $e)
		{
			return false;
		}

		$courseComplete = 0;

		if ($track->no_of_lessons == $track->completed_lessons)
		{
			$courseComplete = 1;
		}

		if ($courseComplete == 1)
		{
			// DPE - Hack - This can go in core
			if (!$obj->course_id)
			{
				return false;
			}
			// DPE - Hack - ends here

			PluginHelper::importPlugin('system');
			Factory::getApplication()->triggerEvent('onAfterCourseCompletion', array(
														$oluser_id,
														$obj->course_id,
														$lesson_id
													)
								);
		}

		return $courseComplete;
	}

	/**
	 * Function to get course ID
	 *
	 * @param   INT  $lesson_id  LEsson ID
	 *
	 * @return  INT  $results Course ID
	 *
	 * @since  1.0.0
	 */
	public function getcourseId($lesson_id)
	{
		try
		{
			$db = Factory::getDbo();
			$query = $db->getQuery(true);
			$query->select($db->quoteName('course_id'));
			$query->from($db->quoteName('#__tjlms_lessons'));
			$query->where($db->quoteName('id') . ' = ' . $db->quote((int) $lesson_id));
			$db->setQuery($query);

			return $db->loadresult();
		}
		catch (Exception $e)
		{
			return false;
		}
	}

	/**
	 * Used to add two provided times in hours:minutes:seconds
	 *
	 * @param   INT  $time1  time to be added
	 * @param   INT  $time2  time to be added
	 *
	 * @return  total time in hours:minutes:seconds
	 *
	 * @since 1.0.0
	 * */
	public function sum_the_time($time1, $time2)
	{
		$times = array($time1, $time2);
		$seconds = 0;

		foreach ($times as $time)
		{
			list($hour, $minute, $second) = explode(':', $time);
			$seconds += $hour * 3600;
			$seconds += $minute * 60;
			$seconds += $second;
		}

		$hours = floor($seconds / 3600);
		$seconds -= $hours * 3600;
		$minutes = floor($seconds / 60);
		$seconds -= $minutes * 60;

		return "{$hours}:{$minutes}:{$seconds}";

		// Return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
	}

	/**
	 * Used to check if there is already entry in lesson_track table for user,lesson and attempt
	 *
	 * @param   INT  $lesson_id  id of the lesson whose tracking data is to check
	 * @param   INT  $attempt    attempt number
	 * @param   INT  $oluser_id  logged in User id
	 *
	 * @return  object form lesson_track
	 *
	 * @since 1.0.0
	 * */
	public function istrackpresent($lesson_id, $attempt, $oluser_id)
	{
		try
		{
			$db = Factory::getDBO();
			$query	= $db->getQuery(true);

			$query->select($db->quoteName(array('id','score','lesson_status')));
			$query->select('TIME_TO_SEC(time_spent) as time_spent');
			$query->from($db->quoteName('#__tjlms_lesson_track'));
			$query->where($db->quoteName('lesson_id') . " = " . $db->quote((int) $lesson_id));
			$query->where($db->quoteName('user_id') . " = " . $db->quote((int) $oluser_id));

			// DPE Hack and Patch From Shika (Core Shika Discussion in ongoing to take into core)
			if ($attempt)
			{
				$query->where($db->quoteName('attempt') . " = " . $db->quote((int) $attempt));
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
	 * Used to get score and lesson status of a lesson by its attempt grading set
	 *
	 * @param   INT  $lesson        lesson whose grade is to get
	 * @param   INT  $user_id       logged in User id
	 * @param   INT  $attemptsdone  Attempts done by a user aginst the lesson
	 *
	 * @return  object of score and status of lesson
	 *
	 * @since 1.0.0
	 * */
	public function getLessonattemptsGrading($lesson, $user_id, $attemptsdone = 0)
	{
		$db	= Factory::getDBO();
		$res	=	new stdClass;
		$res->score = '0';
		$res->lesson_status = 'not_started';

		if (!empty($lesson))
		{
			// DPE Hack and Patch From Shika (Core Shika Discussion in ongoing to take into core)
			if ($attemptsdone == 1)
			{
				$track_entry = $this->istrackpresent($lesson->id, 1, $user_id);

				return $track_entry;
			}
			else
			{
				// Get Lesson
				JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjlms/tables');
				$lessonData = JTable::getInstance('lesson', 'TjlmsTable');
				$lessonData->load(array('id' => $lesson->id));

				if ($lesson->no_of_attempts)
				{
					$track_entry = $this->istrackpresent($lesson->id, 1, $user_id);
				}
				else
				{
					$track_entry = $this->istrackpresent($lesson->id, 0, $user_id);
				}

				// If no track of any attempt
				if (!$track_entry)
				{
					return $res;
				}
			}
			
			// DPE Hack ends here


			try
			{
				$sql = $db->getQuery(true);

				switch ($lesson->attempts_grade)
				{
					case "0" :
						$subQuery = $db->getQuery(true);
						$subQuery->select('max(score)');
						$subQuery->from($db->qn('#__tjlms_lesson_track', 'lt1'));

						$subQuery->where($db->qn('lt1.lesson_id') . ' = ' . $db->q((int) $lesson->id));
						$subQuery->where($db->qn('lt1.user_id') . ' = ' . $db->q((int) $user_id));

						$subQuery->order($db->quoteName('attempt') . ' DESC');
						$subQuery->setLimit('1');
						$db->setQuery($subQuery);

						$sql->select($db->qn(array('score', 'lesson_status', 'lesson_id')));
						$sql->from($db->qn('#__tjlms_lesson_track', 'lt'));

						$sql->where($db->qn('score') . ' = (' . $subQuery->__toString() . ')');
						$sql->where($db->qn('lt.user_id') . ' = ' . $db->q((int) $user_id));
						$sql->where($db->qn('lt.lesson_id') . ' = ' . $db->q((int) $lesson->id));

						$sql->order($db->quoteName('attempt') . ' DESC');
						$sql->setLimit('1');

						$db->setQuery($sql);
						$res = $db->loadObject();
						break;

					case "1" :
						$sql = $db->getQuery(true);
						$sql->select('AVG(score)');
						$sql->from($db->qn('#__tjlms_lesson_track'));

						$sql->where($db->qn('lesson_id') . ' = ' . $db->q((int) $lesson->id));
						$sql->where($db->qn('user_id') . ' = ' . $db->q((int) $user_id));

						$db->setQuery($sql);
						$score = $db->loadResult();

						$sql = $db->getQuery(true);
						$sql->select($db->qn(array('lesson_status')));
						$sql->from($db->qn('#__tjlms_lesson_track'));
						$sql->where($db->qn('lesson_id') . ' = ' . $db->q((int) $lesson->id));
						$sql->where($db->qn('user_id') . ' = ' . $db->q((int) $user_id));

						$db->setQuery($sql);
						$status = $db->loadColumn();

						$maxs = array();

						if (!empty($status))
						{
							$status_cnt	= array_count_values($status);
							$allStatus = array_keys($status_cnt, max($status_cnt));
						}

						$lesson_status = '';

						if (isset($allStatus[0]))
						{
							$lesson_status = array_search('passed', $allStatus) === false ? $allStatus[0] : 'passed';
						}

						$res->score = $score;
						$res->lesson_status = $lesson_status;
						$res->lesson_id = $lesson->id;

						break;

					case "2" :
						$sql->select($db->qn(array('score','lesson_status', 'lesson_id')));
						$sql->from($db->qn('#__tjlms_lesson_track'));

						$sql->where($db->qn('lesson_id') . ' = ' . $db->q((int) $lesson->id));
						$sql->where($db->qn('attempt') . ' =1');
						$sql->where($db->qn('user_id') . ' = ' . $db->q((int) $user_id));

						$db->setQuery($sql);
						$res = $db->loadObject();
						break;

					case "3" :
						$sql->select($db->qn(array('score','lesson_status', 'lesson_id')));
						$sql->from($db->qn('#__tjlms_lesson_track'));

						$sql->where($db->qn('lesson_id') . ' = ' . $db->q((int) $lesson->id));
						$sql->where($db->qn('user_id') . ' = ' . $db->q((int) $user_id));
						$sql->where($db->qn('lesson_status') . 'IN (' . "'completed', 'failed', 'passed'" . ')');

						$sql->order($db->qn('attempt') . ' DESC');

						$db->setQuery($sql);
						$res = $db->loadObject();
						break;
				}
			}
			catch (Exception $e)
			{
				return false;
			}
		}

		return $res;
	}

	/**
	 * Function to add course track entry
	 *
	 * @param   INT     $courseId      Course ID
	 * @param   INT     $actorId       User to completed the course
	 * @param   INT     $lessonId      Lesson id
	 * @param   String  $courseStatus  User to completed the course
	 * @param   String  $timestart     Start time
	 * @param   String  $timeend       End time
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	public function addCourseTrackEntry($courseId , $actorId = '', $lessonId = 0, $courseStatus = '', $timestart = '', $timeend = '')
	{
		$db = Factory::getDbo();

		$TjlmsCoursesHelper = new TjlmsCoursesHelper;
		$courseProgress = $TjlmsCoursesHelper->getCourseProgress($courseId, $actorId);
		$courseProgress['status'] = !empty($courseStatus) ? $courseStatus : $courseProgress['status'];

		if (!empty($timestart))
		{
			$timestart = date('Y-m-d H:i:s', strtotime($timestart));
		}

		if (!empty($timeend))
		{
			$timeend = date('Y-m-d H:i:s', strtotime($timeend));
		}

		$courseTrack = new stdclass;
		$courseTrack->course_id = $courseId;
		$courseTrack->no_of_lessons = $courseProgress['totalLessons'];

		$query = $db->getQuery(true);

		$query->select($db->quoteName(array('id', 'user_id', 'timeend', 'status')));
		$query->from($db->quoteName('#__tjlms_course_track'));
		$conditions = array($db->quoteName('course_id') . ' = ' . $db->quote((int) $courseId));

		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjlms/tables');
		$courseTrackTable = Table::getInstance('Coursetrack', 'TjlmsTable', array('dbo', $db));

		$lessonObj = Tjlms::lesson($lessonId);

		try
		{
			if (!empty($actorId))
			{
				$conditions[] = $db->quoteName('user_id') . ' = ' . $db->quote((int) $actorId);
				$query->where($conditions);
				$db->setQuery($query);
				$result = $db->loadObject();

				$courseTrack->user_id           = $actorId;
				$courseTrack->completed_lessons = $courseProgress['completedLessons'];
				$courseTrack->status            = $courseProgress['status'];

				// Add issued certificate if course status is completed after recalculating the course progress.
				JLoader::import('components.com_tjlms.models.course', JPATH_SITE);
				$tjlmsModelcourse = BaseDatabaseModel::getInstance('Course', 'TjlmsModel', array('ignore_request' => true));

				// If certificate generation date from tools.
				$issuedCertData = '';

				if (!empty($fromTools))
				{
					$issuedCertData = $result->timeend;
				}

				if (!$result)
				{
					if (empty($timestart))
					{
						$courseTrack->timestart = Factory::getDate()->toSQL();
					}
					else
					{
						$courseTrack->timestart = $timestart;
					}

					if (!empty($timeend))
					{
						$courseTrack->timeend = $timeend;
					}
					else
					{
						$courseTrack->timeend = '0000-00-00 00:00:00';
					}

					$courseTrack->last_accessed_date = '0000-00-00 00:00:00';
					$courseTrack->cert_gen_date = '0000-00-00 00:00:00';
					
					if (!$courseTrackTable->save($courseTrack))
					{
						echo $db->stderr();
					}
				}
				else
				{
					$courseTrack->id                 = $result->id;
					$courseTrack->last_accessed_date = Factory::getDate()->toSQL();
					$courseData                      = TjLms::course($courseId);

					if (!empty($timestart))
					{
						$courseTrack->timestart = $timestart;
					}

					if (!empty($timeend))
					{
						$courseTrack->timeend = $timeend;
					}

					// If course term is pass all lessons
					if ($courseData->certificate_term == 2)
					{
						$courseTrackObj        = Tjlms::coursetrack($actorId, $courseId);
						$passableLessonsPassed = $courseTrackObj->checkPassableLessonsPassed();

						// If passable lessons are passed
						if ($passableLessonsPassed && $lessonObj->consider_marks)
						{
							// Generate certificate
							if ($tjlmsModelcourse->addCertEntry($courseId, $actorId))
							{
								$courseTrack->cert_gen_date = Factory::getDate()->toSQL();
							}
						}
					}

					/* If course is completed then it will update timeend and also,
					It will generate a certificate for complete all lesson term,
					if course is incompleted then course track data updated in else part
					*/

					if ($courseProgress['status'] == 'C')
					{
						// Timeend will not update once course is completed
						if ($result->status != "C" )
						{
							if (empty($timeend))
							{
								$courseTrack->timeend = Factory::getDate()->toSQL();
							}
							else
							{
								$courseTrack->timeend = $timeend;
							}
						}

						//  If course term is complete all lessons
						if ($courseData->certificate_term == 1 && $lessonObj->consider_marks)
						{
							// Generate certificate
							if ($tjlmsModelcourse->addCertEntry($courseId, $actorId))
							{
								$courseTrack->cert_gen_date = Factory::getDate()->toSQL();
							}
						}

						if (!$courseTrackTable->save($courseTrack))
						{
							echo $db->stderr();
						}
					}
					else
					{
						if (!$courseTrackTable->save($courseTrack))
						{
							echo $db->stderr();
						}
					}
				}
			}
			else
			{
				$query->where($conditions);
				$db->setQuery($query);
				$result = $db->loadObjectList();

				if (!empty($result))
				{
					foreach ($result as $track)
					{
						if ($track->id)
						{
							$courseTrack->id = $track->id;

							$TjlmsCoursesHelper = new TjlmsCoursesHelper;
							$courseProgress = $TjlmsCoursesHelper->getCourseProgress($courseId, $track->user_id);
							$courseProgress['status'] = !empty($courseStatus) ? $courseStatus : $courseProgress['status'];
							$courseTrack->completed_lessons = $courseProgress['completedLessons'];
							$courseTrack->status = $courseProgress['status'];

							if (!$courseTrackTable->save($courseTrack))
							{
								echo $db->stderr();
							}
						}
					}
				}
			}
		}
		catch (Exception $e)
		{
			return false;
		}

		return true;
	}

	/**
	 * Get record from tjlms_course_track for course and user id
	 *
	 * @param   int  $courseId  ID of course
	 * @param   int  $oluserId  ID of user
	 *
	 * @return   Courseprogress
	 *
	 * @since   1.0
	 */
	public function getCourseTrackEntry($courseId, $oluserId)
	{
		$db   = Factory::getDBO();
		$input = Factory::getApplication()->input;

		$courseProgress = array();

		if ($courseId > 0 && $oluserId)
		{
			try
			{
				$query = $db->getQuery(true);
				$query->select(array("*"));

				$query->from($db->qn('#__tjlms_course_track'));
				$query->where($db->qn('course_id') . ' = ' . $db->q((int) $courseId));
				$query->where($db->qn('user_id') . ' = ' . $db->q((int) $oluserId));

				$db->setQuery($query);
				$track = $db->loadObject();
			}
			catch (Exception $e)
			{
				return false;
			}

			$courseProgress['completionPercent'] = 0;

			if (!empty($track))
			{
				$courseProgress['totalLessons'] = $track->no_of_lessons;
				$courseProgress['completedLessons'] = $track->completed_lessons;
				$courseProgress['status'] = $track->status;
				$courseProgress['completionPercent'] = 0;
				$courseProgress['completion_date'] = $track->timeend;

				if ($track->no_of_lessons > 0 && $track->completed_lessons > 0)
				{
					if ($track->no_of_lessons == $track->completed_lessons)
					{
						$courseProgress['status'] = 'C';
						$courseProgress['completionPercent'] = 100;
					}
					else
					{
						$courseProgress['status'] = 'I';
						$courseProgress['completionPercent'] = round($track->completed_lessons * 100 / $track->no_of_lessons, 2);
					}
				}
			}
		}

		return $courseProgress;
	}

	/**
	 * Used activity and session count of a user by date
	 *
	 * @param   INT  $activityData  All data
	 *
	 * @return  object of score and status of lesson
	 *
	 * @since 1.0.0
	 * */
	public function getactivity($activityData = array())
	{
		try
		{
			$db = Factory::getDbo();
			$query = $db->getQuery(true);

			$query->select('DATE(added_time) as time, COUNT(IF(action="LOGIN",1, NULL)) as session_count');
			$query->select('COUNT(IF(action<>"LOGIN" && action<>"LOGOUT",1, NULL)) as activity_count');
			$query->from($db->qn('#__tjlms_activities'));

			if (isset($activityData['course_id']) && $activityData['course_id'] != '')
			{
				$query->where($db->qn('parent_id') . '=' . $db->q((int) $activityData['course_id']));
			}

			if (isset($activityData['user_id']) && $activityData['user_id'] != '')
			{
				$query->where($db->qn('actor_id') . '=' . $db->q((int) $activityData['user_id']));
			}

			// Filter by date range
			if (isset($activityData['start']) && $activityData['start'] != '' && isset($activityData['end']) && $activityData['end'] != '')
			{
				$query->where("(added_time BETWEEN " . $db->quote($activityData['start']) . " AND " . $db->quote($activityData['end']) . " )");
			}

			$query->group('DATE(added_time)');
			$db->setQuery($query);

			return $db->loadObjectlist();
		}
		catch (Exception $e)
		{
			return false;
		}
	}

	/**
	 * Method for Converting timestamp to time ago
	 *
	 * @param   DATETIME  $datetime  Any supported date and time format (2013-05-01 00:22:35, @1367367755)
	 * @param   boolean   $full      boolean passed
	 *
	 * @return  string (4 months, 1 hour ago)
	 *
	 * @since   1.0
	 */
	public function time_elapsed_string($datetime, $full = false)
	{
		$timezone = new DateTimeZone('UTC');
		$now = new DateTime(null,  $timezone);
		$ago = new DateTime($datetime, $timezone);
		$diff = $now->diff($ago);

		$diff->w = floor($diff->d / 7);
		$diff->d -= $diff->w * 7;

		// @TODO show direct date if time is greater than 1 week
		$string = array(
			'y' => Text::_('COM_TJLMS_YEAR'),
			'm' => Text::_('COM_TJLMS_MONTH'),
			'w' => Text::_('COM_TJLMS_WEEK'),
			'd' => Text::_('COM_TJLMS_DAY'),
			'h' => Text::_('COM_TJLMS_HOUR_TITLE'),
			'i' => Text::_('COM_TJLMS_MINUTE_TITLE'),
			/*'s' => 'second',*/
		);

		foreach ($string as $k => &$v)
		{
			if ($diff->$k)
			{
				$v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? '(s)' : '');
			}
			else
			{
				unset($string[$k]);
			}
		}

		if (!$full)
		{
			$string = array_slice($string, 0, 1);
		}

		return $string ? implode(' ', $string) . Text::_('COM_TJLMS_AGO') : Text::_('COM_TJLMS_JUST_NOW');
	}

	/**
	 *  Function to get Total Time spent on Course
	 *
	 * @param   INT  $user_id  logged in user_id
	 *
	 * @return  totalSpentTime.
	 *
	 * @since 1.0.0
	 */

	public function getTotalTimeSpent($user_id)
	{
		try
		{
			$db = Factory::getDbo();
			$query = $db->getQuery(true);

			$query->select('SEC_TO_TIME(SUM(TIME_TO_SEC(time_spent))) as timeSpentOnLesson');
			$query->from($db->quoteName('#__tjlms_lesson_track', 'lt'));
			$query->where($db->quoteName('lt.user_id') . ' = ' . $db->quote((int) $user_id));

			$db->setQuery($query);
			$totalSpentTime = $db->loadresult();

			return $totalSpentTime;
		}
		catch (Exception $e)
		{
			return false;
		}
	}

	/**
	 *  Function to get Total Ideal Time of lesson Attempted
	 *
	 * @param   INT  $user_id  logged in user_id
	 *
	 * @return  totalIdealTime.
	 *
	 * @since 1.0.0
	 */
	public function getTotalIdealTime($user_id)
	{
		try
		{
			$db = Factory::getDbo();
			$query = $db->getQuery(true);

			$query->select('DISTINCT lesson_id');
			$query->from($db->quoteName('#__tjlms_lesson_track'));
			$query->where($db->quoteName('user_id') . ' = ' . (int) $user_id);

			$db->setQuery($query);
			$lessonIds = $db->loadColumn();

			// Create a new query object.
			$query = $db->getQuery(true);
			$query->select('SEC_TO_TIME(SUM(ideal_time * 60)) as lessonIdealTime');
			$query->from($db->quoteName('#__tjlms_lessons'));

			if (!empty($lessonIds))
			{
				$query->where('id IN(' . implode(',', $db->quote($lessonIds)) . ')');
			}

			$db->setQuery($query);

			return $db->loadresult();
		}
		catch (Exception $e)
		{
			return false;
		}
	}

	/**
	 *  Function to get formated Time (H:m:s) from seconds
	 *
	 * @param   INT  $seconds  seconds
	 *
	 * @return  formated Time (H:m:s).
	 *
	 * @since 1.0.0
	 */
	public function secToTime($seconds)
	{
		$hours = floor($seconds / 3600);
		$minutes = floor($seconds % 3600 / 60);
		$seconds = $seconds % 60;
		$timestamp = $hours . ":" . $minutes . ":" . $seconds;

		return $timestamp;
	}
}
