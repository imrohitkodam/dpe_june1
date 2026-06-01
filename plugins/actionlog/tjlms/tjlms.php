<?php
/**
 * @package     PeopleSuggest
 * @subpackage  Plg_Actionlog_PeopleSuggest
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (c) 2009-2018 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later.
 */

// No direct access.
defined('_JEXEC') or die();

JLoader::register('ActionlogsHelper', JPATH_ADMINISTRATOR . '/components/com_actionlogs/helpers/actionlogs.php');
JLoader::import('components.com_tjlms.includes.tjlms', JPATH_ADMINISTRATOR);

use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

jimport('joomla.application.component.helper');

/**
 * People Suggest Actions Logging Plugin.
 *
 * @since  1.3.1
 */
class PlgActionlogTjlms extends CMSPlugin
{
	/**
	 * Application object.
	 *
	 * @var    JApplicationCms
	 * @since  1.3.1
	 */
	protected $app;

	/**
	 * Database object.
	 *
	 * @var    JDatabaseDriver
	 * @since  1.3.1
	 */
	protected $db;

	/**
	 * Constructor - Function used as a contructor
	 *
	 * @param   object  $subject  The object to observe
	 * @param   array   $config   An array that holds the plugin configuration
	 *
	 * @since  1.3.1
	 */
	public function __construct($subject, $config)
	{
		parent::__construct($subject, $config);

		$CoursesHelperPath = JPATH_SITE . '/components/com_tjlms/helpers/courses.php';

		if (!class_exists('TjlmsCoursesHelper'))
		{
			JLoader::register('TjlmsCoursesHelper', $CoursesHelperPath);
			JLoader::load('TjlmsCoursesHelper');
		}

		$this->TjlmsCoursesHelper = new TjlmsCoursesHelper;
	}

	/**
	 * Proxy for ActionlogsModelUserlog addLog method
	 *
	 * This method adds a record to #__action_logs contains (message_language_key, message, date, context, user)
	 *
	 * @param   array   $messages            The contents of the messages to be logged
	 * @param   string  $messageLanguageKey  The language key of the message
	 * @param   string  $context             The context of the content passed to the plugin
	 * @param   int     $userId              ID of user perform the action, usually ID of current logged in user
	 *
	 * @return  void
	 *
	 * @since   1.3.1
	 */
	protected function addLog($messages, $messageLanguageKey, $context, $userId = null)
	{
		/** @var \Joomla\Component\Actionlogs\Administrator\Model\ActionlogModel $model */
		$model = $this->app->bootComponent('com_actionlogs')
			->getMVCFactory()->createModel('Actionlog', 'Administrator', ['ignore_request' => true]);

		$model->addLog($messages, $messageLanguageKey, $context, $userId);
	}

	/**
	 * On recommending a friend
	 *
	 * Method is called after a user recommends a friend to his friend
	 * This method logs which user recommended which user to which user
	 *
	 * @param   INT     $courseId       course id of course created
	 * @param   INT     $courseCreator  user id of user who created the course
	 * @param   STRING  $courseTitle    user id of user who was recommended
	 *
	 * @return  void
	 *
	 * @since   1.3.1
	 */
	public function onAfterCourseCreation($courseId, $courseCreator, $courseTitle)
	{
		if (!$this->params->get('logActionForCourseCreate', 1))
		{
			return;
		}

		$option = $this->app->input->getCmd('option');
		$action = 'COURSE_CREATED';

		// Course URL to redirect from stream to course landing page.
		$courseUrl = 'index.php?option=com_tjlms&view=modules&course_id=' . $courseId;

		$messageLanguageKey = 'PLG_ACTIONLOG_TJLMS_COURSE_CREATED';

		$message = array(
			'action'      => $action,
			'username'    => Factory::getUser($courseCreator)->username,
			'accountlink' => 'index.php?option=com_users&task=user.edit&id=' . $courseCreator,
			'coursename'  => $courseTitle,
			'courselink'  => $courseUrl,
		);

		$this->addLog(array($message), $messageLanguageKey, $option, $courseCreator);
	}

	/**
	 * Function used as a trigger after user successfully enrolled  for a course.
	 *
	 * @param   INT  $actorId      user has been enrolled
	 * @param   INT  $state        Enrollment state
	 * @param   INT  $courseId     course ID
	 * @param   INT  $enrolledBy   user who enrolled the actor
	 * @param   INT  $notify_user  send notification or Not
	 *
	 * @return  void
	 *
	 * @since  1.3.1
	 */
	public function onAfterCourseEnrol($actorId, $state, $courseId, $enrolledBy, $notify_user = 1)
	{
		if (!$this->params->get('logActionForCourseEnrol', 1))
		{
			return;
		}

		$option = $this->app->input->getCmd('option');
		$action = 'ENROLL';

		// Course URL to redirect from stream to course landing page.
		$courseUrl = 'index.php?option=com_tjlms&view=modules&course_id=' . $courseId;

		$courseTitle = TjLms::Course($courseId)->title;

		$message               = array();
		$message['action']     = $action;
		$message['coursename'] = $courseTitle;
		$message['courselink'] = $courseUrl;

		if ($actorId == $enrolledBy)
		{
			$message['username']    = Factory::getUser($actorId)->username;
			$message['accountlink'] = 'index.php?option=com_users&task=user.edit&id=' . $actorId;
			$messageLanguageKey     = 'PLG_ACTIONLOG_TJLMS_ENROLL';
		}
		else
		{
			$message['enrollbyusername']    = Factory::getUser($enrolledBy)->username;
			$message['enrollbyaccountlink'] = 'index.php?option=com_users&task=user.edit&id=' . $enrolledBy;
			$message['enrolltousername']    = Factory::getUser($actorId)->username;
			$message['enrolltoaccountlink'] = 'index.php?option=com_users&task=user.edit&id=' . $actorId;
			$messageLanguageKey             = 'PLG_ACTIONLOG_TJLMS_ENROLL_BY';
		}

		$this->addLog(array($message), $messageLanguageKey, $option, $enrolledBy);
	}

	/**
	 * Function used as a trigger after user send a recommendation for course successfully.
	 *
	 * @param   INT  $todoId  Todo table Id
	 * @param   INT  $to      User to whom recommended
	 * @param   INT  $from    User who recommend
	 * @param   INT  $params  Course which is recommended
	 *
	 * @return  void
	 *
	 * @since  1.3.1
	 */
	public function onAfterRecommend($todoId, $to, $from, $params)
	{
		if (!$this->params->get('logActionForCourseRecommend', 1))
		{
			return;
		}

		if ($params['element'] == 'com_tjlms.course')
		{
			$option = $this->app->input->getCmd('option');
			$action = 'COURSE_RECOMMENDED';

			// Course URL to redirect from stream to course landing page.
			$courseUrl = 'index.php?option=com_tjlms&view=modules&course_id=' . $params['element_id'];

			$message                    = array();
			$message['action']          = $action;
			$message['coursename']      = $params['element_title'];
			$message['courselink']      = $courseUrl;
			$message['recommendbyname'] = Factory::getUser($from)->username;
			$message['recommendbylink'] = 'index.php?option=com_users&task=user.edit&id=' . $from;
			$message['recommendtoname'] = Factory::getUser($to)->username;
			$message['recommendtolink'] = 'index.php?option=com_users&task=user.edit&id=' . $to;
			$messageLanguageKey         = 'PLG_ACTIONLOG_TJLMS_RECOMMEND_COURSE';

			$this->addLog(array($message), $messageLanguageKey, $option, $from);
		}
	}

	/**
	 * Function used as a trigger after user complete a course.
	 *
	 * @param   INT  $actorId   User to completed the course
	 * @param   INT  $courseId  Course ID
	 *
	 * @return  void
	 *
	 * @since  1.3.1
	 */
	public function onAfterCourseCompletion($actorId, $courseId)
	{
		if (!$this->params->get('logActionForCourseCompletion', 1))
		{
			return;
		}

		// Action performed
		$action   = 'COURSE_COMPLETED';
		$option   = $this->app->input->getCmd('option');
		$username = Factory::getUser($actorId)->username;

		// Course URL to redirect from stream to course landing page.
		$courseUrl = 'index.php?option=com_tjlms&view=modules&course_id=' . $courseId;
		$courseTitle = TjLms::Course($courseId)->title;
		$messageLanguageKey = 'PLG_ACTIONLOG_TJLMS_COURSE_COMPLETED';

		$message = array(
			'action'      => $action,
			'username'    => $username,
			'accountlink' => 'index.php?option=com_users&task=user.edit&id=' . $actorId,
			'coursename'  => $courseTitle,
			'courselink'  => $courseUrl,
		);

		$this->addLog(array($message), $messageLanguageKey, $option, $actorId);
	}

	/**
	 * Function used as a trigger after user start a lesson.
	 *
	 * @param   INT  $lessonId  Lesson ID
	 * @param   INT  $attempt   attempt number of the user
	 * @param   INT  $actorId   User who is attempting the lesson
	 *
	 * @return  void
	 *
	 * @since  1.3.1
	 */
	public function onAfterLessonAttemptstarted($lessonId, $attempt, $actorId)
	{
		if (!$this->params->get('logActionForLessonAttemptStart', 1))
		{
			return;
		}

		$db    = $this->db;
		$query = $db->getQuery(true)
				->select($db->quoteName(array('l.id', 'l.course_id', 'c.title')))
				->select($db->quoteName('l.title', 'lesson_title'))
				->from($db->quoteName('#__tjlms_lessons', 'l'))
				->join(
					'INNER', $this->db->quoteName('#__tjlms_courses', 'c') . ' ON ' . $this->db->quoteName('c.id') . ' = ' . $this->db->quoteName('l.course_id')
					)
				->where($db->quoteName('l.id') . '=' . $db->quote($lessonId));

		// Reset the query using our newly populated query object.
		$db->setQuery($query);

		// Load the results as a list of stdClass objects
		$element = $db->loadObject();

		$action   = 'ATTEMPT';
		$option   = $this->app->input->getCmd('option');
		$username = Factory::getUser($actorId)->username;

		// Course URL to redirect from stream to course landing page.
		$courseUrl = 'index.php?option=com_tjlms&view=modules&course_id=' . $element->course_id;

		$messageLanguageKey = 'PLG_ACTIONLOG_TJLMS_ATTEMPT_START';

		$message = array(
			'action'      => $action,
			'lessonname'  => $element->lesson_title,
			'attempt'     => $attempt,
			'username'    => $username,
			'accountlink' => 'index.php?option=com_users&task=user.edit&id=' . $actorId,
			'coursename'  => $element->title,
			'courselink'  => $courseUrl,
		);

		$this->addLog(array($message), $messageLanguageKey, $option, $actorId);
	}

	/**
	 * Function used as a trigger after user finish a attempt for a lesson.
	 *
	 * @param   INT  $lessonId      Lesson ID
	 * @param   INT  $attempt       attempt number of the user
	 * @param   INT  $actorId       User who is attempting the lesson
	 * @param   INT  $lessonFormat  Format of the lesson
	 *
	 * @return  void
	 *
	 * @since  1.3.1
	 */
	public function onAfterLessonAttemptEnd($lessonId, $attempt, $actorId, $lessonFormat)
	{
		if (!$this->params->get('logActionForLessonAttemptEnd', 1))
		{
			return;
		}

		$db    = $this->db;
		$query = $db->getQuery(true)
				->select($db->quoteName(array('l.id', 'l.course_id', 'c.title')))
				->select($db->quoteName('l.title', 'lesson_title'))
				->from($db->quoteName('#__tjlms_lessons', 'l'))
				->join(
					'INNER', $this->db->quoteName('#__tjlms_courses', 'c') . ' ON ' . $this->db->quoteName('c.id') . ' = ' . $this->db->quoteName('l.course_id')
					)
				->where($db->quoteName('l.id') . '=' . $db->quote($lessonId));

		// Reset the query using our newly populated query object.
		$db->setQuery($query);

		// Load the results as a list of stdClass objects
		$element = $db->loadObject();

		$action   = 'ATTEMPT_END';
		$option   = $this->app->input->getCmd('option');
		$username = Factory::getUser($actorId)->username;

		// Course URL to redirect from stream to course landing page.
		$courseUrl = 'index.php?option=com_tjlms&view=modules&course_id=' . $element->course_id;

		$messageLanguageKey = 'PLG_ACTIONLOG_TJLMS_ATTEMPT_END';

		$message = array(
			'action'      => $action,
			'lessonname'  => $element->lesson_title,
			'attempt'     => $attempt,
			'username'    => $username,
			'accountlink' => 'index.php?option=com_users&task=user.edit&id=' . $actorId,
			'coursename'  => $element->title,
			'courselink'  => $courseUrl,
		);

		$this->addLog(array($message), $messageLanguageKey, $option, $actorId);
	}

	/**
	 * Added by Deepali Function used as a trigger after user send a assignment for course successfully.
	 *
	 * @param   INT  $todoId      Todo table Id
	 * @param   INT  $to          User to whom recommended
	 * @param   INT  $from        User who recommend
	 * @param   INT  $params      Course which is recommended
	 * @param   INT  $notifyUser  notifyUser notifyUser
	 *
	 * @return  void
	 *
	 * @since  1.3.1
	 */
	public function onAfterAssignment($todoId, $to, $from, $params, $notifyUser = 0)
	{
		if (!$this->params->get('logActionForCourseAssignment', 1))
		{
			return;
		}

		if ($params['element'] == 'com_tjlms.course')
		{
			$option = $this->app->input->getCmd('option');
			$action = 'COURSE_ASSIGNMENT';

			// Course URL to redirect from stream to course landing page.
			$courseUrl = 'index.php?option=com_tjlms&view=modules&course_id=' . $params['element_id'];

			$message                   = array();
			$message['action']         = $action;
			$message['coursename']     = $params['element_title'];
			$message['courselink']     = $courseUrl;
			$message['assignedbyname'] = Factory::getUser($from)->username;
			$message['assignedbylink'] = 'index.php?option=com_users&task=user.edit&id=' . $from;
			$message['assignedtoname'] = Factory::getUser($to)->username;
			$message['assignedtolink'] = 'index.php?option=com_users&task=user.edit&id=' . $to;
			$messageLanguageKey        = 'PLG_ACTIONLOG_TJLMS_ASSIGNENROLL';

			$this->addLog(array($message), $messageLanguageKey, $option, $from);
		}
	}

	/**
	 * On after deleting lesson attempt data logging method
	 *
	 * Method is called after lesson attempt data is deleted from  the database.
	 *
	 * @param   object  $attemptData  Holds the lesson attempt data.
	 *
	 * @return  void
	 *
	 * @since   1.3.31
	 */
	public function onAfterLessonAttemptDelete($attemptData)
	{
		if (!$this->params->get('logActionForLessonAttemptDelete', 1))
		{
			return;
		}

		$context    = Factory::getApplication()->input->get('option');
		$lessonInfo = TjLms::Lesson($attemptData->lesson_id);
		$courseInfo = TjLms::Course($lessonInfo->course_id);

		// Course URL to redirect from stream to course landing page.
		$courseUrl = 'index.php?option=com_tjlms&view=modules&course_id=' . $lessonInfo->course_id;

		$user               = Factory::getUser();
		$messageLanguageKey = 'PLG_ACTIONLOGS_TJLMS_LESSON_ATTEMPT_DELETED';
		$action             = 'delete';
		$attendeeUser       = Factory::getUser($attemptData->user_id);

		$message = array(
			'action'       => $action,
			'type'         => 'PLG_ACTIONLOGS_TJLMS_LESSON_TYPE_ATTEMPT',
			'attempt'      => $attemptData->attempt,
			'lessonname'   => $lessonInfo->title,
			'userid'       => $user->id,
			'username'     => $user->username,
			'accountlink'  => 'index.php?option=com_users&task=user.edit&id=' . $user->id,
			'coursename'   => $courseInfo->title,
			'courselink'   => $courseUrl,
			'attendeename' => $attendeeUser->username,
			'attendeelink' => 'index.php?option=com_users&task=user.edit&id=' . $attendeeUser->id,
		);

		$this->addLog(array($message), $messageLanguageKey, $context, $userId);
	}

	/**
	 * On after updating lesson attempt score logging method
	 *
	 * Method is called after lesson attempt score is updated from  the database.
	 *
	 * @param   array  $attemptData  Holds the lesson attempt data.
	 *
	 * @return  void
	 *
	 * @since   1.3.31
	 */
	public function onAfterLessonAttemptScoreUpdate($attemptData)
	{
		if (!$this->params->get('logActionForLessonAttemptScoreUpdate', 1))
		{
			return;
		}

		$context    = Factory::getApplication()->input->get('option');
		$lessonInfo = TjLms::Lesson($attemptData['lesson_id']);
		$courseInfo = TjLms::Course($lessonInfo->course_id);

		// Course URL to redirect from stream to course landing page.
		$courseUrl = 'index.php?option=com_tjlms&view=modules&course_id=' . $lessonInfo->course_id;

		$user               = Factory::getUser();
		$messageLanguageKey = 'PLG_ACTIONLOGS_TJLMS_LESSON_ATTEMPT_SCORE_UPDATED';
		$action             = 'update';
		$attendeeUser       = Factory::getUser($attemptData['user_id']);

		$message = array(
			'action'        => $action,
			'type'          => 'PLG_ACTIONLOGS_TJLMS_LESSON_TYPE_ATTEMPT',
			'attempt'       => $attemptData['attempt'],
			'lessonname'    => $lessonInfo->title,
			'userid'        => $user->id,
			'username'      => $user->username,
			'accountlink'   => 'index.php?option=com_users&task=user.edit&id=' . $user->id,
			'coursename'    => $courseInfo->title,
			'courselink'    => $courseUrl,
			'attendeename'  => $attendeeUser->username,
			'attendeelink'  => 'index.php?option=com_users&task=user.edit&id=' . $attendeeUser->id,
			'previousscore' => $attemptData['previous_score'],
			'latestscore'   => $attemptData['score'],
		);

		$this->addLog(array($message), $messageLanguageKey, $context, $userId);
	}

	/**
	 * On after changing lesson attempt status logging method
	 *
	 * Method is called after lesson attempt status is changed from  the database.
	 *
	 * @param   array  $attemptData  Holds the lesson attempt data.
	 *
	 * @return  void
	 *
	 * @since   1.3.31
	 */
	public function onAfterLessonAttemptStatusChange($attemptData)
	{
		if (!$this->params->get('logActionForLessonAttemptStatusChange', 1))
		{
			return;
		}

		$context    = Factory::getApplication()->input->get('option');
		$lessonInfo = TjLms::Lesson($attemptData['lesson_id']);
		$courseInfo = TjLms::Course($lessonInfo->course_id);

		// Course URL to redirect from stream to course landing page.
		$courseUrl = 'index.php?option=com_tjlms&view=modules&course_id=' . $lessonInfo->course_id;

		$user               = Factory::getUser();
		$messageLanguageKey = 'PLG_ACTIONLOGS_TJLMS_LESSON_ATTEMPT_STATUS_CHANGED';
		$action             = 'statusChanged';
		$attendeeUser       = Factory::getUser($attemptData['user_id']);

		$message = array(
			'action'         => $action,
			'type'           => 'PLG_ACTIONLOGS_TJLMS_LESSON_TYPE_ATTEMPT',
			'attempt'        => $attemptData['attempt'],
			'lessonname'     => $lessonInfo->title,
			'userid'         => $user->id,
			'username'       => $user->username,
			'accountlink'    => 'index.php?option=com_users&task=user.edit&id=' . $user->id,
			'coursename'     => $courseInfo->title,
			'courselink'     => $courseUrl,
			'attendeename'   => $attendeeUser->username,
			'attendeelink'   => 'index.php?option=com_users&task=user.edit&id=' . $attendeeUser->id,
			'previousstatus' => $attemptData['previous_lesson_status'],
			'lateststatus'   => $attemptData['lesson_status'],
		);

		$this->addLog(array($message), $messageLanguageKey, $context, $userId);
	}
}
