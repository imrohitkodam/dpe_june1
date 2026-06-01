<?php
/**
 * @package     TJLms
 * @subpackage  com_shika
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die();
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Object\CMSObject;

/**
 * TjLms course track class
 *
 * @since  1.3.30
 */
class TjLmsCourseTrack extends CMSObject
{
	/**
	 * Id
	 *
	 * @var    int
	 * @since  1.3.30
	 */
	public $id = 0;

	/**
	 * Course id
	 *
	 * @var    int
	 * @since  1.3.30
	 */
	public $course_id = 0;

	/**
	 * User id
	 *
	 * @var    int
	 * @since  1.3.30
	 */
	public $user_id = 0;

	/**
	 * Start time
	 *
	 * @var    string
	 * @since  1.3.30
	 */
	public $timestart = null;

	/**
	 * End time
	 *
	 * @var    string
	 * @since  1.3.30
	 */
	public $timeend = null;

	/**
	 * Number of lessons
	 *
	 * @var    int
	 * @since  1.3.30
	 */
	public $no_of_lessons = 0;

	/**
	 * Completed lessons
	 *
	 * @var    int
	 * @since  1.3.30
	 */
	public $completed_lessons = 0;

	/**
	 * Status
	 *
	 * @var    string
	 * @since  1.3.30
	 */
	public $status = null;

	/**
	 * Array to hold the object instances
	 *
	 * @var    object
	 * @since  1.3.30
	 */
	public static $instances = array();

	/**
	 * Constructor
	 *
	 * @param   int  $userId    User id
	 *
	 * @param   int  $courseId  Course id
	 *
	 * @since   1.3.39
	 */
	public function __construct($userId, $courseId)
	{
		if (!empty($userId) && !empty($courseId))
		{
			$this->load($userId, $courseId);
		}
	}

	/**
	 * Load course track
	 *
	 * @param   integer  $userId    user id
	 * @param   integer  $courseId  course id
	 *
	 * @return  void
	 *
	 * @since  1.3.39
	 */
	public function load($userId, $courseId)
	{
		JLoader::import("/components/com_tjlms/tables", JPATH_ADMINISTRATOR);
		$table = Table::getInstance('Coursetrack', 'TjlmsTable');

		$hash = md5($courseId . $userId);

		if (isset(self::$instances[$hash]))
		{
			return self::$instances[$hash];
		}

		// Load the object based on the id or throw a warning.
		if (!$table->load(array("course_id" => $courseId, "user_id" => $userId)))
		{
			$this->setError(Text::_("COM_TJLMS_NO_COURSETRACK"));

			return false;
		}

		$this->setProperties($table->getProperties());

		self::$instances[$hash] = $this;

		return self::$instances[$hash];
	}

	/**
	 * Returns the global object
	 *
	 * @param   integer  $userId    User id
	 * @param   integer  $courseId  Course id
	 *
	 * @return  TjLmsCourseTrack  Object.
	 *
	 * @since   1.3.30
	 */
	public static function getInstance($userId, $courseId)
	{
		if (!$userId || !$courseId)
		{
			return new TjLmsCourseTrack($userId, $courseId);
		}

		$hash = md5($userId . $courseId);

		if (empty(self::$instances[$hash]))
		{
			self::$instances[$hash] = new TjLmsCourseTrack($userId, $courseId);
		}

		return self::$instances[$hash];
	}

	/**
	 * Generate course progress based on course track for each module
	 *
	 * @param   array  $courseTrackData  course progress object
	 *
	 * @return  array
	 *
	 * @since   1.3.39
	 */
	public function getProgress($courseTrackData)
	{
		if (!empty($courseTrackData['totalLessons']) > 0 && $courseTrackData['completedLessons'] > 0)
		{
			if ($courseTrackData['totalLessons'] == $courseTrackData['completedLessons'])
			{
				$courseTrackData['status'] = 'C';
				$courseTrackData['completionPercent'] = 100;
			}
			else
			{
				$courseTrackData['status'] = 'I';
				$courseTrackData['completionPercent'] = round(($courseTrackData['completedLessons'] * 100) / $courseTrackData['totalLessons'], 2);
			}
		}

		return $courseTrackData;
	}

	/**
	 * Get current module and current lesson
	 *
	 * @param   array  $moduleData  module data given by the tjlms course object
	 *
	 * @return  array|void
	 *
	 * @since   1.3.39
	 */
	public function getCourseResumeModule($moduleData)
	{
		$return = array();

		// Get last accessed lesson and limit is set in the getListQuery of lessonTrack model.
		JLoader::import('components.com_tjlms.models.lessontrack', JPATH_SITE);
		$lessonTrackmodel = BaseDatabaseModel::getInstance('lessonTrack', 'tjlmsModel', array('ignore_request' => true));
		$lessonTrackmodel->setState('last_attempt_track', 'DESC');
		$lessonTrackmodel->setState("user_id", $this->user_id);
		$lessonTrackmodel->setState('course_id', $this->course_id);
		$lastAccessedLesson = $lessonTrackmodel->getItems();

		// If no progress at all then set first element of module and lesson.
		if (empty($lastAccessedLesson))
		{
			$firstModule = reset($moduleData);
			$firstLesson = !empty($firstModule) ? reset($firstModule->lessons) : '';
			$return['currentModule'] = $firstModule;
			$return['currentLesson'] = $firstLesson;

			return $return;
		}

		// Using array_search we are fetching the last accessed lesson object from $moduleData array.
		$lastAccessedLessonKey = array_search($lastAccessedLesson[0]->id, array_column((array)$moduleData[$lastAccessedLesson[0]->mod_id]->lessons, 'id'));

		$lastAccessedLesson = $moduleData[$lastAccessedLesson[0]->mod_id]->lessons[$lastAccessedLessonKey];

		$completedStatus = array('passed', 'failed', 'completed', 'AP');

		// Check whether last accessed lesson is complete or not
		if (!in_array($lastAccessedLesson->userStatus['status'], $completedStatus))
		{
			$return['currentModule'] = $moduleData[$lastAccessedLesson->mod_id];
			$return['currentLesson'] = $lastAccessedLesson;

			return $return;
		}

		// Check for incomplete lesson from module
		foreach ($moduleData as $module)
		{
			foreach ($module->lessons as $lesson)
			{
				// Get incomplete lesson
				if (!in_array($lesson->userStatus['status'], $completedStatus))
				{
					$return['currentModule'] = $moduleData[$lesson->mod_id];
					$return['currentLesson'] = $lesson;

					return $return;
				}
			}
		}

		// Make sure you return default lesson here
		$return['currentModule'] = $moduleData[$lastAccessedLesson->mod_id];
		$return['currentLesson'] = $lastAccessedLesson;

		return $return;
	}

	/**
	 * Generate additional stat required to show on the course module
	 *
	 * @param   array  $moduleStat  Module stat generated for additional data
	 *
	 * @return  array
	 *
	 * @since   1.3.39
	 */
	public function getModuleProgress($moduleStat)
	{
		$totalModuleLessons = 0;
		$totalCompletedLessons = 0;
		$lessonCompletionData  = new stdclass;

		$completedStatus = array('passed', 'failed', 'completed');

		if (!empty($moduleStat))
		{
			foreach ($moduleStat->lessons as $lesson)
			{
				// Get total completed count from module
				if (in_array($lesson->userStatus['status'], $completedStatus))
				{
					$totalCompletedLessons++;
				}

				$totalModuleLessons++;
			}
		}

		$lessonCompletionData->totalCompletedLessons = $totalCompletedLessons;
		$lessonCompletionData->totalModuleLessons = $totalModuleLessons;

		return $lessonCompletionData;
	}

	/**
	 * This function checks the passable lessons are passed or not
	 *
	 * @return  boolean True on passed all the lessons
	 *
	 * @since   1.3.39
	 */
	public function checkPassableLessonsPassed()
	{
		$courseData = TjLms::course($this->course_id);

		$comTjlmsTrackingHelper = new ComtjlmstrackingHelper;

		$passableLessons = $courseData->getPassableLessons();

		if (!empty($passableLessons))
		{
			$passedLessons = array();
			$lessonsTrack = array();

			foreach ($passableLessons as $passableLesson)
			{
				$lessonsTrack[] = $comTjlmsTrackingHelper->getLessonattemptsGrading($passableLesson, $this->user_id);
			}

			foreach ($lessonsTrack as $lessonTrack)
			{
				// If lesson is passed then make array of lesson ids
				if ($lessonTrack->lesson_status === "passed")
				{
					$passedLessons[] = $lessonTrack->lesson_id;
				}
			}

			// Check count of passable lesson ids and passed lesson id
			if (count($passableLessons) == count($passedLessons))
			{
				return true;
			}
		}

		return false;
	}
}
