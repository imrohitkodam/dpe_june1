<?php
/**
 * @version    SVN: <svn_id>
 * @package    Plg_System_Tjlms
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

// No direct access.
defined('_JEXEC') or die;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

jimport('joomla.filesystem.file');
jimport('joomla.html.parameter');
jimport('joomla.plugin.plugin');
jimport('joomla.application.component.helper');

// Load language file for plugin.
$lang = Factory::getLanguage();
$lang->load('plg_system_tjlms', JPATH_ADMINISTRATOR);
$lang->load('com_tjlms', JPATH_SITE);

/**
 * Methods supporting a list of Tjlms action.
 *
 * @since  1.0.0
 */
class PlgSystemTjlms extends CMSPlugin
{
	private $comtjlmsHelperObj = null;

	private $TjlmsMailcontentHelper = null;

	private $TjlmsCoursesHelper = null;

	private $TjlmsLessonHelper = null;

	private $comCarams = null;

	/**
	 * Constructor - Function used as a contructor
	 *
	 * @param   object  $subject  The object to observe
	 * @param   array   $config   An array that holds the plugin configuration
	 *
	 * @retunr  class object
	 *
	 * @since  1.0.0
	 */
	public function __construct($subject, $config)
	{
		parent::__construct($subject, $config);

		$path = JPATH_SITE . '/components/com_tjlms/helpers/main.php';

		if (File::exists($path))
		{
			if (!class_exists('comtjlmsHelper'))
			{
				JLoader::register('comtjlmsHelper', $path);
				JLoader::load('comtjlmsHelper');
			}

			$mailPath = JPATH_SITE . '/components/com_tjlms/helpers/mailcontent.php';

			if (!class_exists('TjlmsMailcontentHelper'))
			{
				JLoader::register('TjlmsMailcontentHelper', $mailPath);
				JLoader::load('TjlmsMailcontentHelper');
			}

			$CoursesHelperPath = JPATH_SITE . '/components/com_tjlms/helpers/courses.php';

			if (!class_exists('TjlmsCoursesHelper'))
			{
				JLoader::register('TjlmsCoursesHelper', $CoursesHelperPath);
				JLoader::load('TjlmsCoursesHelper');
			}

			$lessonHelperPath = JPATH_SITE . '/components/com_tjlms/helpers/lesson.php';

			if (!class_exists('TjlmsLessonHelper'))
			{
				JLoader::register('TjlmsLessonHelper', $lessonHelperPath);
				JLoader::load('TjlmsLessonHelper');
			}

			JLoader::import('components.com_tjlms.includes.tjlms', JPATH_ADMINISTRATOR);
			$this->tjLmsEmail = TjLms::Email();

			$this->comtjlmsHelperObj = new comtjlmsHelper;

			$this->TjlmsMailcontentHelper = new TjlmsMailcontentHelper;

			$this->TjlmsCoursesHelper = new TjlmsCoursesHelper;

			$this->TjlmsLessonHelper = new TjlmsLessonHelper;

			$this->comCarams = ComponentHelper::getParams('com_tjlms');
		}
	}

	/**
	 * Function onAfterRender user to ad tjlms sidebar styling to category view
	 *
	 * @return  boolean true or false
	 *
	 * @since  1.0.0
	 */
	public function onAfterRoute()
	{
		$app    = Factory::getApplication();
		$jinput = $app->input;
		$option = $jinput->get("option");
		$task   = $jinput->get("task", '');

		if ($app->isClient('administrator'))
		{
			if ($option == 'com_content' && $task == 'articles.delete')
			{
				// Exclude articles ids from cid if set as lesson
				$cid = $jinput->get('cid', array(), 'array');

				if (!empty($cid))
				{
					$articleTitle = $this->_checkLessonArticle($cid);

					if (!empty($articleTitle))
					{
						$lesson_articles = array_keys($articleTitle);
						$cid 			 = array_diff($cid, $lesson_articles);
						$jinput->set('cid', $cid);
						$message = Text::sprintf("PLG_TJLMS_SYSTEM_MESSGE_USED_ARTICLE_DELETE", implode('<br />', $articleTitle));
						$app->enqueueMessage($message, 'Warning');

						if (empty($cid))
						{
							$uri = Uri::getInstance();
							$urUrl = $uri->toString();
							$app->redirect($urUrl);

							return false;
						}
					}
				}
			}
		}
		elseif ($app->isClient('site'))
		{
			if ($option == 'com_tmt')
			{
				$lang = Factory::getLanguage();
				$lang->load('com_tmt.quiz', JPATH_SITE, null, true, true);

				$langFile = 'en-GB.com_tmt.quiz.ini';
				$quizLangs = LanguageHelper::parseIniFile(JPATH_SITE . '/language/en-GB/' . $langFile);

				if (!empty($quizLangs))
				{
					foreach ($quizLangs as $key => $value)
					{
						Text::script($key);
					}
				}
			}
		}
	}

	/**
	 * Function used to get the site url for course
	 *
	 * @param   STRING  $courseUrl  course url
	 * @param   STRING  $xhtml      xhtml
	 * @param   INT     $ssl        Secure url
	 *
	 * @return  string
	 *
	 * @since  1.0.0
	 */
	public function getSiteCourseurl($courseUrl, $xhtml = true, $ssl = 0)
	{
		$courseRoutedUrl = $this->comtjlmsHelperObj->tjlmsRoute($courseUrl, $xhtml, $ssl);

		return $courseRoutedUrl;
	}

	/**
	 * Function used as a trigger after each course creation.
	 *
	 * @param   INT     $courseId       course ID
	 * @param   INT     $courseCreator  course creator user ID
	 * @param   STRING  $courseTitle    course tilte
	 *
	 * @return  boolean true or false
	 *
	 * @since  1.0.0
	 */
	public function onAfterCourseCreation($courseId, $courseCreator, $courseTitle)
	{
		// Execute the code only if the class object exists
		if ($this->comtjlmsHelperObj)
		{
			$command = 'onAfterCourseCreation';

			jimport('joomla.application.component.model');
			BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjlms/models');
			$moduleModel = BaseDatabaseModel::getInstance('Module', 'TjlmsModel');

			$db = Factory::getDbo();
			$db->setQuery('select count(1) from #__tjlms_modules where course_id = ' . (int) $courseId);
			$res = $db->loadResult();

			if (!$res)
			{
				$moduleData = array (
					'name'		=> Text::sprintf('COM_TJLMS_MODULE_CREATE'),
					'course_id' => $courseId,
					'state' 	=> 1
				);
				$moduleModel->save($moduleData);
			}

			$this->addpointstostudent($courseCreator, $command);

			$allowedActivityStream = $this->comCarams->get('activityStreamToAllow', '', 'ARRAY');

			if (!empty($allowedActivityStream) && in_array('onafterCourseCreate', $allowedActivityStream))
			{
				// Action performed
				$action = 'COURSE_CREATED';

				// Course URL to redirect from stream to course landing page.
				$courseUrl = 'index.php?option=com_tjlms&view=course&id=' . $courseId;

				$courseRoutedUrl = $this->getSiteCourseurl($courseUrl);

				$params = '';

				// Add activity for Shika activity stream
				$this->addLmsActivity($courseCreator, $action, $courseId, $courseTitle, $courseId, $courseUrl, $params);

				// Add social stream
				$actAccess   = $title = $content = '';
				$actType     = 'full';
				$contextType = 'course';
				$targetId    = '';

				$courseLink = '<a href="' . $courseRoutedUrl . '">' . $courseTitle . '</a>';

				$actionDescription = Text::sprintf('COM_TJLMS_COURSE_CREATED_STREAM', '', $courseLink);

				$elementInfo         = new stdclass;
				$elementInfo->id     = $courseId;
				$elementInfo->title  = $courseTitle;
				$elementInfo->url    = $courseRoutedUrl;
				$elementInfo->html   = $actionDescription;
				$elementInfo->params = array('id' => $courseId);

				$actorId = $courseCreator;

				$this->advAddSocialActivity($actorId, $actType, $action, $contextType, $targetId, $actAccess, $title, $content, $elementInfo);

				return true;
			}
		}

		return false;
	}

	/**
	 * Function used as a trigger after user successfully enrolled  for a course.
	 *
	 * @param   INT     $actorId       user has been enrolled
	 * @param   INT     $state         Enrollment state
	 * @param   INT     $courseId      course ID
	 * @param   INT     $enrolledBy    user who enrolled the actor
	 * @param   INT     $notify_user   send notification or Not
	 * @param   INT     $courseStatus  Course Track Status
	 * @param   String  $timestart     Start time
	 * @param   String  $timeend       End time
	 *
	 * @return  boolean true or false
	 *
	 * @since  1.0.0
	 */
	public function onAfterCourseEnrol($actorId, $state, $courseId, $enrolledBy, $notify_user = 1, $courseStatus = '', $timestart = '', $timeend = '')
	{
		if ($this->comtjlmsHelperObj)
		{
			$allowedActivityStream = $this->comCarams->get('activityStreamToAllow', '', 'ARRAY');

			$command = 'onAfterCourseEnrol';

			if ($state == 1)
			{
				// Add points to user only if the enrollment is successful.
				$this->addpointstostudent($actorId, $command);
			}

			// Add user to group only if the enrollment is successful.
			$autoCreateGroup = $this->comCarams->get('group_integration', '0', 'INT');

			if ($autoCreateGroup == 1)
			{
				$this->comtjlmsHelperObj->addUserToGroup($actorId, $courseId, $state);
			}

			$courseTitle = $this->getCourseName($courseId);
			$courseCreator = $this->TjlmsMailcontentHelper->getCourseCreator($courseId);

			$courseUrl = 'index.php?option=com_tjlms&view=course&id=' . $courseId;

			$courseRoutedUrl = self::getSiteCourseurl($courseUrl, false, -1);

			// Add activity and send notification
			if ($state == 1)
			{
				// Is state is 1 set action to enroll
				$action = 'ENROLL';

				$params = '';

				if (!empty($allowedActivityStream) && in_array('onafterCourseEnroll', $allowedActivityStream))
				{
					// Add activity for Shika activity stream
					$this->addLmsActivity($actorId, $action, $courseId, $courseTitle, $courseId, $courseUrl, $params);

					// Add social stream
					$actAccess = $title = $content = '';
					$actType = 'full';
					$contextType = 'course';
					$targetId = '';

					// Get course name with its URL.
					$courseLink = '<a href="' . $courseRoutedUrl . '">' . $courseTitle . '</a>';

					$actionDescription = Text::sprintf('COM_TJLMS_ACTIVITY_STREAM_ENROLL', '', $courseLink);

					$elementInfo = new stdclass;
					$elementInfo->id = $courseId;
					$elementInfo->title = $courseTitle;
					$elementInfo->url = $courseRoutedUrl;
					$elementInfo->html = $actionDescription;
					$elementInfo->params = array('id' => $courseId);

					$this->advAddSocialActivity($actorId, $actType, $action, $contextType, $targetId, $actAccess, $title, $content, $elementInfo);
				}

				$command = 'enrollcourse.selfenroll';
				$uniqueElementId = 'selfenroll';
				$actionDescription_N = Text::sprintf('COM_TJLMS_NOTIFICATION_ENROLL', $courseTitle);
				$this->sendNotification($courseCreator, $actorId, $actionDescription_N, $command, $uniqueElementId, $courseRoutedUrl);
			}

			// Get course name with its URL.
			$courseLink = '<a href="' . $courseRoutedUrl . '">' . $courseTitle . '</a>';

			// Get plain coruse link
			$coursePlainLink = $courseRoutedUrl;

			// Send email to course creator about user enrolment

			if ($notify_user == 1)
			{
				$this->TjlmsMailcontentHelper->onAfterCourseEnrolMail($actorId, $courseCreator, $courseId, $courseLink, $state, $coursePlainLink);
			}

			// Add entry in course_track table against the user
			$this->onAddCourseTrackEntry($courseId, $actorId, 0, $courseStatus, $timestart, $timeend);

			$lessons = $this->TjlmsCoursesHelper->getLessonsByCourse($courseId, array('m.format', 'm.sub_format', 'm.source', 'l.id', 'l.media_id'));

			foreach ($lessons as $lesson)
			{
				$plug_type = 'tj' . $lesson->format;

				$temp = explode('.', $lesson->sub_format);
				$plug_name = $temp[0];

				PluginHelper::importPlugin($plug_type);

				// Trigger all "invitex" plugins method that renders the button/image
				Factory::getApplication()->triggerEvent('courseEnrolPostProcessing', array(
												$plug_name,
												$courseId,
												$actorId,
												$state,
												$enrolledBy,
												$lesson
											)
										);
			}

			return true;
		}

		return false;
	}

	/**
	 * Function used as a trigger after admin approve the enrollment for the course
	 *
	 * @param   INT  $enroledTableId  enrolled user table ID
	 * @param   INT  $state           Enrollment status
	 * @param   INT  $approvedBy      User who approved the enrollment
	 *
	 * @return  boolean true or false
	 *
	 * @since  1.0.0
	 */
	public function onAfterApprovecourseEnrolement($enroledTableId, $state, $approvedBy)
	{
		if ($this->comtjlmsHelperObj)
		{
			$allowedActivityStream = $this->comCarams->get('activityStreamToAllow', '', 'ARRAY');

			// Get a db connection.
			$db = Factory::getDbo();

			// Create a new query object.
			$query = $db->getQuery(true);

			$query->select('c.title,c.id,eu.user_id');
			$query->from('#__tjlms_courses as c');
			$query->join('LEFT', '#__tjlms_enrolled_users as eu ON eu.course_id=c.id');
			$query->where('eu.id = ' . $enroledTableId);

			// Reset the query using our newly populated query object.
			$db->setQuery($query);

			// Load the results as a list of stdClass objects
			$element = $db->loadObject();

			if ($state == 1)
			{
				$command = 'onAfterCourseEnrol';
				$addpoints = $this->addpointstostudent($element->user_id, $command);
			}

			// Add user to group only if the enrollment is successful.
			$autoCreateGroup = $this->comCarams->get('group_integration', '0', 'INT');

			if ($autoCreateGroup == 1)
			{
				$this->comtjlmsHelperObj->addUserToGroup($element->user_id, $element->id, $state);
			}

			$actorId = $approvedBy;

			$elementTitle = $element->title;
			$parentId = $elementId = $element->id;

			$courseUrl = 'index.php?option=com_tjlms&view=course&id=' . $element->id;

			$courseRoutedUrl = self::getSiteCourseurl($courseUrl, false, -1);

			// Get course name with its URL.
			$courseLink = '<a href="' . $courseRoutedUrl . '">' . $elementTitle . '</a>';

			if (!empty($allowedActivityStream) && in_array('onafterCourseEnroll', $allowedActivityStream))
			{
				$params = '';

				// Add shika activity
				if ($state == 1)
				{
					$action = 'ENROLL';
					$this->addLmsActivity($element->user_id, $action, $parentId, $elementTitle, $elementId, $courseUrl, $params);

					// Add social stream
					$actType = $actSubtype = $actLink = $actTitle = $actAccess = $title = $content = '';
					$actType = 'full';
					$contextType = 'course';
					$targetId = '';
					$actorId = $element->user_id;

					$actionDescription = Text::sprintf('COM_TJLMS_ACTIVITY_STREAM_ENROLL', '', $courseLink);

					$elementInfo = new stdclass;
					$elementInfo->id = $element->id;
					$elementInfo->title = $elementTitle;
					$elementInfo->url = $courseRoutedUrl;
					$elementInfo->html = $actionDescription;
					$elementInfo->params = array('id' => $element->id);

					$this->advAddSocialActivity($actorId, $actType, $action, $contextType, $targetId, $actAccess, $title, $content, $elementInfo);
				}
			}

			// Get course name with its URL.
			$courseLink = '<a href="' . $courseRoutedUrl . '">' . $elementTitle . '</a>';

			// Get plain coruse link
			$coursePlainLink = $courseRoutedUrl;

			// Send email
			$this->TjlmsMailcontentHelper->onAfterCourseEnrolApproveMail($element->user_id, $element->id, $courseLink, $state, $coursePlainLink);

			$command = 'enrollapproval.approve';
			$uniqueElementId = 'enrollapproval';

			if ($state == 1)
			{
				$actionDescription_N = Text::sprintf('COM_TJLMS_NOTIFICATION_ENROLL_APPROVED', $elementTitle);
			}
			else
			{
				$actionDescription_N = Text::sprintf('COM_TJLMS_NOTIFICATION_ENROLL_DISAPPROVED', $elementTitle);
			}

			$this->sendNotification($element->user_id, $actorId, $actionDescription_N, $command, $uniqueElementId, $courseUrl);

			return true;
		}

		return false;
	}

	/**
	 * Function used as a trigger after user send a recommendation for course successfully.
	 *
	 * @param   INT  $todoId  Todo table Id
	 * @param   INT  $to      User to whom recommended
	 * @param   INT  $from    User who recommend
	 * @param   INT  $params  Course which is recommended
	 *
	 * @return  boolean true or false
	 *
	 * @since  1.0.0
	 */
	public function onAfterRecommend($todoId, $to, $from, $params)
	{
		if ($params['element'] == 'com_tjlms.course')
		{
			$courseUrl = 'index.php?option=com_tjlms&view=course&id=' . $params['element_id'];
			$courseRoutedUrl = self::getSiteCourseurl($courseUrl);

			// Get course name with its URL.
			$courseLink = '<a href="' . $courseRoutedUrl . '">' . $params['element_title'] . '</a>';

			$action = 'COURSE_RECOMMENDED';

			// Add social stream
			$actAccess   = $title = $content = '';
			$actType     = 'full';
			$contextType = 'course';
			$targetId    = $to;

			$actionDescription  = Text::sprintf('COM_TJLMS_ON_RECOMMEND_COURSE_AS', $from, $courseLink, $to);
			$elementInfo        = new stdclass;
			$elementInfo->id    = $params['element_id'];
			$elementInfo->title = $params['element_title'];
			$elementInfo->url   = $courseRoutedUrl;
			$elementInfo->html  = $actionDescription;

			$courseId    = $params['element_id'];
			$courseTitle = $params['element_title'];

			$param = array('target_id' => $targetId);

			$param = json_encode($param);

			$actorId = $from;
			$this->addLmsActivity($actorId, $action, $courseId, $courseTitle, $courseId, $courseUrl, $param);

			$this->advAddSocialActivity($from, $actType, $action, $contextType, $targetId, $actAccess, $title, $content, $elementInfo);
			$courseRoutedUrl = self::getSiteCourseurl($courseUrl, false, -1);

			// Get course name with its URL.
			$courseLink      = '<a href="' . $courseRoutedUrl . '">' . $params['element_title'] . '</a>';
			$coursePlainLink = $courseRoutedUrl;
			$actionDescription_N = Text::sprintf('COM_TJLMS_ON_RECOMMEND_COURSE_NOTIFY', $courseTitle);
			$command = 'recommendcourse.recommend';
			$uniqueElementId = 'recommendcourse';

			$this->sendNotification($to, $from, $actionDescription_N, $command, $uniqueElementId, $courseLink);

			// Send email on after recommend
			$this->TjlmsMailcontentHelper->onAfterRecommendMail($to, $params['element_id'], $courseLink, $from, $coursePlainLink);
		}

		return true;
	}

	/**
	 * Function used as a trigger after user complete a course.
	 *
	 * @param   INT  $actorId   User to completed the course
	 * @param   INT  $courseId  Course ID
	 * @param   INT  $lessonId  Lesson ID
	 *
	 * @return  boolean true or false
	 *
	 * @since  1.0.0
	 */
	public function onAfterCourseCompletion($actorId, $courseId, $lessonId = 0)
	{
		// DPE - Hack - This can go in core
		if (!$courseId)
		{
			return false;
		}
		// DPE - Hack - ends here

		
		// Check if course is aleardy completed
		$ifCourseAlreadyCompleted = $this->checkIfCourseCompletd($actorId, $courseId);

		if (empty($ifCourseAlreadyCompleted))
		{
			$allowedActivityStream = $this->comCarams->get('activityStreamToAllow', '', 'ARRAY');
			$command = 'onAfterCourseCompletion';

			// A course can be configured with specific points to be assigned after completion.
			$params   = ComponentHelper::getParams('com_tjlms');
			$ptOption = $params->get('pt_option', '');
			
			if ($ptOption == 'espt')
			{
				require_once JPATH_ROOT . '/administrator/components/com_easysocial/includes/foundry.php';
				$point 	= FD::table('Points');
				$state 	= $point->load(array( 'command' => 'course.' . $courseId . '.onAfterCourseCompletion' , 'extension' => 'com_tjlms'));

				if ($state)
				{
					$command = $point->command;
				}
			}

			// Add points to user only if the enrollment is successful.
			$this->addpointstostudent($actorId, $command);

			$params              = ComponentHelper::getParams('com_tjlms');
			$courseTitle         = $this->getCourseName($courseId);
			$courseCreator       = $this->TjlmsMailcontentHelper->getCourseCreator($courseId);
			$actionDescription_N = Text::sprintf('COM_TJLMS_COURSE_COMPLETED_NOTIFY', $courseTitle);

			// Add notification
			$command = 'coursecompletion.complete';
			$uniqueElementId = 'coursecompletion';

			$courseUrl = 'index.php?option=com_tjlms&view=course&id=' . $courseId;

			$notificationCourseUrl = self::getSiteCourseurl($courseUrl, false, -1);

			$this->sendNotification($actorId, $courseCreator, $actionDescription_N, $command, $uniqueElementId, $notificationCourseUrl);

			if ($params->get('social_integration', '', 'STRING') == 'easysocial')
			{
				// Check if course has a badge assigned to it.
				$badgeId = $this->getBadgeId($courseId);

				if ($badgeId)
				{
					$db = Factory::getDbo();
					$query = $db->getQuery(true);
					$query->select('bh.*');
					$query->from('`#__social_badges_history` AS bh');
					$query->where('achieved=1');
					$query->where('user_id=' . $actorId);
					$query->where('badge_id=' . $badgeId);
					$db->setQuery($query);
					$ifBadgeAwarded = $db->loadresult();

					if (!$ifBadgeAwarded)
					{
						$db = Factory::getDbo();
						$query = $db->getQuery(true);
						$query->select('a.*');
						$query->from('`#__social_badges` AS a');
						$query->where('extension="com_tjlms"');
						$query->where('id=' . $badgeId);
						$db->setQuery($query);
						$esbadges = $db->loadObject();

						$options['command'] = $esbadges->command;
						$options['extension'] = 'com_tjlms';
						$this->comtjlmsHelperObj->sociallibraryobj->addbadges(Factory::getUser($actorId), $options);
					}
				}
			}

			$courseLink      = '<a href="' . $notificationCourseUrl . '">' . $courseTitle . '</a>';
			$coursePlainLink = $notificationCourseUrl;
			$this->TjlmsMailcontentHelper->onAfterCourseCompletionMail($actorId, $courseId, $courseLink, $coursePlainLink);

			if (!empty($allowedActivityStream) && in_array('onAfterCourseCompletion', $allowedActivityStream))
			{
					// Action performed
					$action = 'COURSE_COMPLETED';
					$params = '';

					// Add activity for Shika activity stream
					$this->addLmsActivity($actorId, $action, $courseId, $courseTitle, $courseId, $courseUrl, $params);

					$courseRoutedUrl = self::getSiteCourseurl($courseUrl);

					// Add social stream
					$actAccess = $title = $content = '';
					$actType = 'full';
					$contextType = 'course';
					$targetId = '';

					$courseLink = '<a href="' . $courseRoutedUrl . '">' . $courseTitle . '</a>';

					$actionDescription = Text::sprintf('COM_TJLMS_COURSE_COMPLETED_STREAM', '', $courseLink);

					$elementInfo = new stdclass;
					$elementInfo->id = $courseId;
					$elementInfo->title = $courseTitle;
					$elementInfo->url = $courseRoutedUrl;
					$elementInfo->html = $actionDescription;
					$elementInfo->params = array('id' => $courseId);

					$this->advAddSocialActivity($actorId, $actType, $action, $contextType, $targetId, $actAccess, $title, $content, $elementInfo);
			}
		}

		// Add entry in course track
		$this->onAddCourseTrackEntry($courseId, $actorId, $lessonId);
		
		/*
			$this->addCertEntry($courseId, $actorId);
		*/

		return true;
	}

	/**
	 * Function to get course track entry
	 *
	 * @param   INT  $actorId   User to completed the course
	 * @param   INT  $courseId  Course ID
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	public function checkIfCourseCompletd($actorId, $courseId)
	{
		if (!empty($actorId) && !empty($courseId))
		{
			// Get a db connection.
			$db = Factory::getDbo();

			// Create a new query object.
			$query = $db->getQuery(true);

			// Select all records from the user profile table where key begins with "custom.".
			// Order it by the ordering field.
			$query->select($db->quoteName(array('id')));
			$query->from($db->quoteName('#__tjlms_activities'));
			$query->where($db->quoteName('actor_id') . ' = ' . $db->quote($actorId));
			$query->where($db->quoteName('parent_id') . ' = ' . $db->quote($courseId));
			$query->where($db->quoteName('element_id') . ' = ' . $db->quote($courseId));
			$query->where($db->quoteName('action') . ' = ' . $db->quote('COURSE_COMPLETED'));

			// Reset the query using our newly populated query object.
			$db->setQuery($query);

			// Load the results as a list of stdClass objects (see later for more options on retrieving data).
			$results = $db->loadResult();

			return $results;
		}
		else {
			return;
		}
	}

	/**
	 * Function to get ES badge id
	 *
	 * @param   INT  $courseId  CourseID
	 *
	 * @return  INT  $badgeId
	 *
	 * @since  1.0.0
	 */
	public function getBadgeId($courseId)
	{
		$courseInfo = $this->TjlmsCoursesHelper->getcourseInfo($courseId);

		$courseParams = json_decode($courseInfo->params);

		$esbadges = '';

		if (isset($courseParams->esbadges) && !empty($courseParams->esbadges))
		{
			$esbadges = $courseParams->esbadges;
		}

		return $esbadges;
	}

	/**
	 * Function used as a trigger after user start a lesson.
	 *
	 * @param   INT  $lessonId  Lesson ID
	 * @param   INT  $attempt   attempt number of the user
	 * @param   INT  $actorId   User who is attempting the lesson
	 *
	 * @return  boolean true or false
	 *
	 * @since  1.0.0
	 */
	public function onAfterLessonAttemptstarted($lessonId, $attempt, $actorId)
	{
		if ($this->comtjlmsHelperObj)
		{
			$allowedActivityStream = $this->comCarams->get('activityStreamToAllow', '', 'ARRAY');

			if (!empty($allowedActivityStream) && in_array('onafterLessonAttemptStart', $allowedActivityStream))
			{
				// Get a db connection.
				$db = Factory::getDbo();

				// Create a new query object.
				$query = $db->getQuery(true);

				$query->select('l.id,l.course_id,l.title as lesson_title,c.title');
				$query->from('#__tjlms_lessons as l');
				$query->join('LEFT', '#__tjlms_courses as c ON c.id=l.course_id');
				$query->where('l.id = ' . $lessonId);

				// Reset the query using our newly populated query object.
				$db->setQuery($query);

				// Load the results as a list of stdClass objects
				$element = $db->loadObject();

				$action = 'ATTEMPT';

				$lessonUrl = 'index.php?option=com_tjlms&view=lesson&lesson_id=' . $lessonId;
				$params = json_encode(array('attempt' => $attempt));

				// Add Shika activity stream
				$this->addLmsActivity($actorId, $action, $element->course_id, $element->lesson_title, $lessonId, $lessonUrl, $params);

				// Add social stream
				$lessonLink = '<strong>' . $element->lesson_title . '</strong>';
				$courseUrl = 'index.php?option=com_tjlms&view=course&id=' . $element->course_id;
				$courseRoutedUrl = self::getSiteCourseurl($courseUrl);

				// Add social stream
				$actAccess = $title = $content = '';
				$actType = 'mini';
				$contextType = 'course';
				$targetId = '';
				$content = $attempt;

				$courseLink = '<a href="' . $courseRoutedUrl . '">' . $element->title . '</a>';
				$actionDescription = Text::sprintf('COM_TJLMS_ACTIVITY_STREAM_ATTEMPT', '', $attempt, $lessonLink, $courseLink);

				$elementInfo = new stdclass;
				$elementInfo->id = $element->id;
				$elementInfo->title = $element->lesson_title;
				$elementInfo->url = $courseRoutedUrl;
				$elementInfo->html = $actionDescription;
				$elementInfo->params = array('id' => $element->course_id, 'attempt' => $attempt, 'lessonId' => $element->id);

				$this->advAddSocialActivity($actorId, $actType, $action, $contextType, $targetId, $actAccess, $title, $content, $elementInfo);
			}

			return true;
		}

		return false;
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
	 * @return  boolean true or false
	 *
	 * @since  1.0.0
	 */
	public function onAfterAssignment($todoId, $to, $from, $params, $notifyUser = 0)
	{
		$enroll_date = Factory::getDate()->toSql(true);

		if ($params['element'] == 'com_tjlms.course')
		{
			$global_params        = ComponentHelper::getParams('com_tjlms');
			$admin_approval       = $global_params->get('admin_approval');
			$allowFlexiEnrolments = $global_params->get('allow_flexi_enrolments', 0, 'INT');

			if ($admin_approval == '1' && $to == Factory::getUser()->id)
			{
				$state = '0';
			}
			else
			{
				$state = '1';
			}

			require_once JPATH_SITE . '/components/com_tjlms/models/enrolment.php';
			$model = BaseDatabaseModel::getInstance('enrolment', 'TjlmsModel', array('ignore_request' => true));

			$data = array();
			$data['user_id'] = $to;
			$data['course_id'] = $params['element_id'];
			$data['state'] = $state;
			$data['notify_user'] = 0;
			$data['coursestatus'] = $params['coursestatus'];
			$isEnrolled = $model->checkIfUserEnrolled($to, $params['element_id']);
			$enrollment_id = false;

			// Get course data.
			$courseData = TjLms::course($data['course_id']);

			// If allowFlexi Enroments is enabled then user should not enrolled to the paid course only user enroll for free course.
			if ((!$isEnrolled) && (!$allowFlexiEnrolments || !$courseData->type) || ($data['coursestatus']))
			{
				$enrollment_id = $model->save($data);
			}

			if (($notifyUser && $enrollment_id) || ($notifyUser && $isEnrolled))
			{
				$courseUrl = 'index.php?option=com_tjlms&view=course&id=' . $params['element_id'];
				$courseRoutedUrl = self::getSiteCourseurl($courseUrl, false, -1);

				// Get course name with its URL.
				$courseLink      = '<a href="' . $courseRoutedUrl . '">' . $params['element_title'] . '</a>';
				$coursePlainLink = $courseRoutedUrl;
				$this->TjlmsMailcontentHelper->onAfterCourseAssignMail($to, $params, $courseLink, $from, $coursePlainLink);
			}

			if ($enrollment_id)
			{
				$courseUrl = 'index.php?option=com_tjlms&view=course&id=' . $params['element_id'];
				$courseRoutedUrl = self::getSiteCourseurl($courseUrl, false, -1);

				// Get course name with its URL.
				$courseLink = '<a href="' . $courseRoutedUrl . '">' . $params['element_title'] . '</a>';

				JLoader::register('TjlmsCoursesHelper', JPATH_SITE . '/components/com_tjlms/helpers/courses.php');
				JLoader::load('TjlmsCoursesHelper');
				$courseHelper = new TjlmsCoursesHelper;

				$orderDetails = $courseHelper->getCourseOrderDetails($params['element_id'], $to, 'userId');

				if ($orderDetails)
				{
					$plan_id = $courseHelper->getCoursePlanId($orderDetails->id);
					require_once JPATH_SITE . '/components/com_tjlms/models/buy.php';
					$buymodel = BaseDatabaseModel::getInstance('buy', 'TjlmsModel');
					$orderInfo['enrollment_id'] = $enrollment_id;
					$buymodel->updateOrderDetails($orderDetails->id, $orderInfo);
					$courseHelper->updateEndTimeForCourse($plan_id, $enrollment_id);
				}

				return true;
			}
		}

		return false;
	}

	/**
	 * Function used as a trigger after user finish a attempt for a lesson.
	 *
	 * @param   INT  $lessonId      Lesson ID
	 * @param   INT  $attempt       attempt number of the user
	 * @param   INT  $actorId       User who is attempting the lesson
	 * @param   INT  $lessonFormat  Format of the lesson
	 *
	 * @return  boolean true or false
	 *
	 * @since  1.0.0
	 */
	public function onAfterLessonAttemptEnd($lessonId, $attempt, $actorId, $lessonFormat)
	{
		if ($lessonFormat == 'quiz')
		{
			// Send mail to admin if the user has exausted the attempts and failed
			$lessonDetails = $this->TjlmsLessonHelper->getLesson($lessonId);

			if ($lessonDetails->no_of_attempts)
			{
				JLoader::import('components.com_tjlms.models.lessontrack', JPATH_SITE);
				$lessonTrackmodel = BaseDatabaseModel::getInstance('lessonTrack', 'tjlmsModel', array('ignore_request' => true));
				$lessonTrackmodel->setState("lesson_id", $lessonDetails->id);
				$lessonTrackmodel->setState("user_id", $actorId);
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
				$lessonDetails->userStatus['completedAttempts'] = count($completedTracks);
				$lessonDetails->userStatus['attemptsDone'] = count($lessonTracks);

				JLoader::import('components.com_tjlms.models.course', JPATH_SITE);
				$courseModel = BaseDatabaseModel::getInstance('Course', 'TjlmsModel', array('ignore_request' => true));
				$courseModel->formatTrackstoGetStatus($lessonDetails, $lessonTracks);

				if ($lessonDetails->userStatus['completedAttempts'] == $lessonDetails->no_of_attempts)
				{
					if ($lessonDetails->userStatus['status'] == 'failed')
					{
						$this->tjLmsEmail->onAfterAttemptsExhaustedAndFailed($actorId, $lessonDetails);
					}
				}
			}
		}

		if ($this->comtjlmsHelperObj)
		{
			$checkIfactivityPresent = 0;

			if ($lessonFormat == 'textmedia')
			{
				$checkIfactivityPresent = $this->checkIfactivityPresent($lessonId, $attempt, $actorId);
			}

			if ($checkIfactivityPresent == 0)
			{
				$allowedActivityStream = $this->comCarams->get('activityStreamToAllow', '', 'ARRAY');

				if (!empty($allowedActivityStream) && in_array('onafterLessonAttemptEnd', $allowedActivityStream))
				{
					$command = 'onAfterLessonAttemptEnd';
					$this->addpointstostudent($actorId, $command);

					// Get a db connection.
					$db = Factory::getDbo();

					// Create a new query object.
					$query = $db->getQuery(true);

					$query->select('l.id,l.course_id,l.title as lesson_title,c.title');
					$query->from('#__tjlms_lessons as l');
					$query->join('LEFT', '#__tjlms_courses as c ON c.id=l.course_id');
					$query->where('l.id = ' . $lessonId);

					// Reset the query using our newly populated query object.
					$db->setQuery($query);

					// Load the results as a list of stdClass objects
					$element = $db->loadObject();

					$action = 'ATTEMPT_END';

					$lessonUrl = 'index.php?option=com_tjlms&view=lesson&lesson_id=' . $lessonId;
					$params = json_encode(array('attempt' => $attempt));

					// Add Shika activity stream
					$this->addLmsActivity($actorId, $action, $element->course_id, $element->lesson_title, $lessonId, $lessonUrl, $params);

					$actAccess = $title = $content = '';
					$actType = 'mini';
					$contextType = 'course';
					$targetId = '';
					$content = $attempt;

					$lessonLink = '<strong>' . $element->lesson_title . '</strong>';

					$courseUrl = 'index.php?option=com_tjlms&view=course&id=' . $element->course_id;

					$courseRoutedUrl = self::getSiteCourseurl($courseUrl);

					$courseLink = '<a href="' . $courseRoutedUrl . '">' . $element->title . '</a>';
					$actionDescription = Text::sprintf('COM_TJLMS_ACTIVITY_STREAM_ATTEMPT_END', '', $attempt, $lessonLink, $courseLink);

					$elementInfo         = new stdclass;
					$elementInfo->id     = $element->id;
					$elementInfo->title  = $element->lesson_title;
					$elementInfo->url    = $courseRoutedUrl;
					$elementInfo->html   = $actionDescription;
					$elementInfo->params = array('id' => $element->course_id, 'attempt' => $attempt, 'lessonId' => $element->id);

					$this->advAddSocialActivity($actorId, $actType, $action, $contextType, $targetId, $actAccess, $title, $content, $elementInfo);
				}
			}

			return true;
		}

		return false;
	}

	/**
	 * Function used to check if activity already done. Only for text and media format
	 *
	 * @param   INT  $lessonId  LessonID
	 * @param   INT  $attempt   Current Attempt
	 * @param   INT  $actorId   user who perform the action ID
	 *
	 * @return  boolean true or false
	 *
	 * @since  1.0.0
	 */
	public function checkIfactivityPresent($lessonId, $attempt, $actorId)
	{
		// Get a db connection.
		$db = Factory::getDbo();

		// Create a new query object.
		$query = $db->getQuery(true);
		$query->select('params');
		$query->from('#__tjlms_activities as a');
		$query->where('a.actor_id = ' . $actorId);
		$query->where('a.action = "ATTEMPT_END"');
		$query->where('a.element_id = ' . $lessonId);
		$query->order('a.id DESC');
		$db->setQuery($query);
		$params = $db->loadObject();

		$lastAttempt = json_decode($params->params);

		if ($lastAttempt->attempt == $attempt)
		{
			return 1;
		}

		return 0;
	}

	/**
	 * Function used as a push activity into Shika activity stream.
	 *
	 * @param   INT     $actorId       user who perform the action ID
	 * @param   STRING  $action        action performed by the user
	 * @param   INT     $parentId      Parent element ID.
	 * @param   STRING  $elementTitle  title for the element
	 * @param   INT     $elementId     Child element ID
	 * @param   STRING  $elementUrl    Child element URL
	 * @param   STRING  $params        additional info if provided
	 *
	 * @return  boolean true or false
	 *
	 * @since  1.0.0
	 */
	public function addLmsActivity($actorId, $action, $parentId, $elementTitle, $elementId , $elementUrl, $params)
	{
		$result = '';

		// Execute the code only if the class object exists
		if ($this->comtjlmsHelperObj)
		{
			$result = $this->comtjlmsHelperObj->addActivity($actorId, $action, $parentId, $elementTitle, $elementId, $elementUrl, $params);
		}

		return $result;
	}

	/**
	 * Function used as a push activity into Social activity stream.
	 *
	 * @param   INT     $actorId      User who perform the action
	 * @param   STRING  $actType      Type of the activity
	 * @param   STRING  $action       Action performed
	 * @param   STRING  $contextType  Element on which action is performed
	 * @param   INT     $targetId     Needed for socialsite
	 * @param   STRING  $actAccess    Access for the activity
	 * @param   STRING  $title        Title for the activity
	 * @param   STRING  $content      Content for the activity
	 * @param   OBJECT  $elementInfo  Element's info
	 *
	 * @return  boolean true or false
	 *
	 * @since  1.0.0
	 */
	public function advAddSocialActivity($actorId, $actType, $action, $contextType, $targetId, $actAccess, $title, $content, $elementInfo)
	{
		$streamOption = array();
		$streamOption['actorId'] = $actorId;
		$streamOption['actType'] = $actType;
		$streamOption['action'] = $action;
		$streamOption['contextType'] = $contextType;
		$streamOption['targetId'] = $targetId;
		$streamOption['actAccess'] = $actAccess;
		$streamOption['elementInfo'] = $elementInfo;
		$streamOption['title'] = $title;
		$streamOption['content'] = $content;

		// Execute the code only if the class object exists
		if ($this->comtjlmsHelperObj)
		{
			$result = $this->comtjlmsHelperObj->sociallibraryobj->advPushActivity($streamOption);
		}

		return $result;
	}

	/**
	 * Function used to get course title
	 *
	 * @param   INT  $courseId  course ID
	 *
	 * @return  STRING  $courseTitle
	 *
	 * @since  1.0.0
	 */
	public function getCourseName($courseId)
	{
		// Call courses helper function to get the name of the course
		$courseTitle = $this->TjlmsCoursesHelper->courseName($courseId);

		return $courseTitle;
	}

	/**
	 * Function used to allocate points to user
	 *
	 * @param   INT     $actorId  user to whom allocate points
	 * @param   STRING  $command  commands depends on the trigger
	 *
	 * @return  boolean  true
	 *
	 * @since  1.0.0
	 */
	public function addpointstostudent($actorId, $command)
	{
		$options   = array();
		$params    = ComponentHelper::getParams('com_tjlms');
		$pt_option = $params->get('pt_option', '', 'STRING');
		$student   = Factory::getUser($actorId);

		// Load main file
		jimport('techjoomla.jsocial.jsocial');
		jimport('techjoomla.jsocial.joomla');

		if ($pt_option == 'none')
		{
			return false;
		}

		// Get command so that can be apply to assign points.
		$commandTogivepoints = $this->getcommand($command, $pt_option);

		if ($commandTogivepoints)
		{
			// Depending on the integration set, set command and then call library function
			if ($pt_option == 'espt')
			{
				jimport('techjoomla.jsocial.easysocial');
				$SocialLibraryObject = new JSocialEasySocial;
				$options['command'] = $commandTogivepoints;
				$options['extension'] = 'com_tjlms';
				$SocialLibraryObject->addpoints($student, $options);
			}
			elseif ($pt_option == 'jspt')
			{
				jimport('techjoomla.jsocial.jomsocial');
				$SocialLibraryObject = new JSocialJomSocial;
				$options['command'] = $commandTogivepoints;
				$SocialLibraryObject->addpoints($student, $options);
			}
			elseif ($pt_option == 'alpha')
			{
				jimport('techjoomla.jsocial.alphauserpoints');
				$SocialLibraryObject = new JSocialAlphauserpoints;
				$referrerid = $SocialLibraryObject->getAnyUserReferreID($student);

				$options = array(
					'keyreference'    => '',
					'datareference'   => Text::_("PUB_AD"),
					'randompoints'    => $commandTogivepoints,
					'feedback'        => true,
					'force'           => '',
					'frontmessage'    => '',
					'plugin_function' => 'tjlms_aup',
					'referrerid'      => $referrerid
				);

				if ($referrerid)
				{
					$SocialLibraryObject->addpoints($student, $options);
				}
			}
		}

		return true;
	}

	/**
	 * Get points command as integration set
	 *
	 * @param   STRING  $command    trigger from which this function is called.
	 * @param   STRING  $pt_option  point system integration
	 *
	 * @return  STRING  $return  unique command name
	 *
	 * @since 1.0.0
	 */
	public function getcommand($command, $pt_option)
	{
		$params = ComponentHelper::getParams('com_tjlms');
		$return = '';

		if ($pt_option == 'espt')
		{
			$return = $command;
		}
		elseif ($pt_option == 'jspt')
		{
			$return = 'com_tjlms.' . $command . '.points';
		}
		elseif ($pt_option == 'alpha')
		{
			$command = $command . 'AupPoints';
			$return = $params->get($command, '', 'STRING');
		}

		return $return;
	}

	/**
	 * Function used as a post a social notification
	 *
	 * @param   INT     $to                 User to whom notification has to sent
	 * @param   INT     $from               User who send notification
	 * @param   STRING  $actionDescription  action performed
	 * @param   STRING  $command            Unique command
	 * @param   STRING  $uniqueElementId    Unique element ID
	 * @param   STRING  $url                Return URL
	 *
	 * @return  boolean true or false
	 *
	 * @since  1.0.0
	 */
	public function sendNotification($to, $from, $actionDescription, $command, $uniqueElementId, $url)
	{
		// Execute the code only if the class object exists
		if ($this->comtjlmsHelperObj)
		{
			$params = ComponentHelper::getParams('com_tjlms');

			$systemOptions = array();

			if ($params->get('social_integration', '', 'STRING') == 'easysocial')
			{
				$systemOptions = array(
					'uid'       => $uniqueElementId,
					'actorId'   => $from,
					'target_id' => $to,
					'type'      => 'Tjlmsnotification',
					'title'     => $actionDescription,
					'image'     => '',
					'cmd'       => $command,
					'url'       => $url
				);
			}

			elseif ($params->get('social_integration', '', 'STRING') == 'jomsocial')
			{
				$systemOptions['cmd']    = 'notif_system_messaging';
				$systemOptions['type']   = '0';
				$systemOptions['params']['url'] = $url;
			}

			// Send notification
			$from = Factory::getUser($from);
			$to = Factory::getUser($to);

			$notificationSend = $this->comtjlmsHelperObj->sociallibraryobj->sendNotification($from, $to, $actionDescription, $systemOptions);

			return $notificationSend;
		}

		return false;
	}

	/**
	 * Function used as a trigger after each module creation.
	 *
	 * @param   INT     $courseId     course ID
	 * @param   INT     $moduleId     Module ID
	 * @param   STRING  $moduleTitle  course tilte
	 *
	 * @return  boolean true or false
	 *
	 * @since  1.0.0
	 */
	public function onAfterModuleCreation($courseId, $moduleId, $moduleTitle)
	{
		return;
	}

	/**
	 * Function used as a trigger after each lesson creation.
	 *
	 * @param   INT     $courseId  course ID
	 * @param   INT     $lessonId  lesson ID
	 * @param   STRING  $lesson    Lesson object
	 *
	 * @return  boolean true or false
	 *
	 * @since  1.0.0
	 */
	public function onAfterLessonCreation($courseId, $lessonId, $lesson)
	{
		// Update course track for number of lessons and status
		$this->onAddCourseTrackEntry($courseId);
	}

	/**
	 * Function used as a trigger after each lesson creation.
	 *
	 * @param   INT     $lessonId  Lesson ID
	 * @param   STRING  $mediaId   Media ID
	 *
	 * @return  boolean true or false
	 *
	 * @since  1.0.0
	 */
	public function onAfterLessonFormatUploaded($lessonId, $mediaId)
	{
		$obj = $this->TjlmsLessonHelper->getLessonColumn($lessonId,  array('course_id', 'start_date', 'end_date', 'consider_marks','format', 'media_id'));

		// Update course track for number of lessons and status
				$this->onAddCourseTrackEntry($obj->course_id);

		$plug_type = 'tj' . $obj->format;
		PluginHelper::importPlugin($plug_type);

		// Trigger all "invitex" plugins method that renders the button/image
		Factory::getApplication()->triggerEvent('onAfterLessonCreationTrigger', array(
										$plug_type,
										$obj->course_id,
										$lessonId,
										$obj
									)
								);

		return true;
	}

	/**
	 * Function used as a trigger after subscribtion expired
	 *
	 * @param   INT  $userId    User ID
	 * @param   INT  $courseId  Course ID
	 *
	 * @return  boolean true or false
	 *
	 * @since  1.0.0
	 */
	public function onAfterSubscriptionExpired($userId, $courseId)
	{
		// Execute the code only if the class object exists
		if ($this->comtjlmsHelperObj)
		{
			$elementUrl = $this->comtjlmsHelperObj->tjlmsRoute('index.php?option=com_tjlms&view=course&id=' . $courseId, false, -1);
			$elementTitle    = $this->getCourseName($courseId);
			$courseLink      = '<a href="' . $elementUrl . '">' . $elementTitle . '</a>';
			$coursePlainLink = $elementUrl;

			$this->TjlmsMailcontentHelper->onAfterSubscriptionExpiredMail($userId, $courseId, $courseLink, $elementTitle, $coursePlainLink);
		}
	}

	/**
	 * Function used as a trigger after subscribtion is about to expired.
	 *
	 * @param   INT  $userId    User ID
	 * @param   INT  $courseId  Course ID
	 *
	 * @return  boolean true or false
	 *
	 * @since  1.0.0
	 */
	public function onBeforeSubscriptionExpired($userId, $courseId)
	{
		// Execute the code only if the class object exists
		if ($this->comtjlmsHelperObj)
		{
			$elementUrl      = $this->comtjlmsHelperObj->tjlmsRoute('index.php?option=com_tjlms&view=course&id=' . $courseId, false, -1);
			$elementTitle    = $this->getCourseName($courseId);
			$courseLink      = '<a href="' . $elementUrl . '">' . $elementTitle . '</a>';
			$coursePlainLink = $elementUrl;

			$this->TjlmsMailcontentHelper->onBeforeSubscriptionExpiredMail($userId, $courseId, $courseLink, $coursePlainLink);
		}
	}

	/**
	 * Function used as a trigger after user com[lete a lesson. While considering attempt grading
	 *
	 * @param   INT  $lessonId  Lesson ID
	 * @param   INT  $attempt   attempt number of the user
	 * @param   INT  $actorId   User who is attempting the lesson
	 *
	 * @return  boolean true or false
	 *
	 * @since  1.0.0
	 */
	public function onAfterLessonCompletion($lessonId, $attempt, $actorId)
	{
		$obj = $this->TjlmsLessonHelper->getLessonColumn($lessonId, 'course_id');
		$this->onAddCourseTrackEntry($obj->course_id, $actorId, $lessonId);

		return true;
	}

	/**
	 * Function to add course track entry
	 *
	 * @param   INT     $courseId      Course ID
	 * @param   INT     $actorId       User to completed the course
	 * @param   INT     $lessonId      Lesson id
	 * @param   String  $courseStatus  Course Track status
	 * @param   String  $timestart     Start time
	 * @param   String  $timeend       End time
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	public function onAddCourseTrackEntry($courseId , $actorId = '', $lessonId = 0, $courseStatus = '', $timestart = '', $timeend = '')
	{
		$comtjlmstrackingHelper = JPATH_ROOT . '/components/com_tjlms/helpers/tracking.php';

		if (!class_exists('comtjlmstrackingHelper'))
		{
			JLoader::register('comtjlmstrackingHelper', $comtjlmstrackingHelper);
			JLoader::load('comtjlmstrackingHelper');
		}

		$comtjlmstrackingHelper = new comtjlmstrackingHelper;

		jimport('joomla.application.component.model');
		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_tjlms/models');
		$tjlmsModelEnrolment = BaseDatabaseModel::getInstance('Enrolment', 'TjlmsModel');

		// Check if user is enrolled for course or not
		$enrollResult = $tjlmsModelEnrolment->checkUserEnrollment($courseId, $actorId);

		if (!empty($enrollResult))
		{
			$comtjlmstrackingHelper->addCourseTrackEntry($courseId, $actorId, $lessonId, $courseStatus, $timestart, $timeend);
		}
	}

	/**
	 * Function used as a trigger after lesson deleted
	 *
	 * @param   INT  $context  the form context
	 * @param   INT  $lesson   lesson obj
	 *
	 * @return  boolean true or false
	 *
	 * @since  1.0.0
	 */
	public function onAfterLessonDelete($context, $lesson)
	{
		if ($context = 'com_tjlms.lessonform' || $context = 'com_tjlms.lesson')
		{
			require_once JPATH_ADMINISTRATOR . '/components/com_tjlms/models/lesson.php';
			$lessonModel  = BaseDatabaseModel::getInstance('lesson', 'TjlmsModel');

			if (!$lessonModel->deleteLesson($lesson))
			{
				JError::raiseWarning(500, $lessonModel->getError());

				return false;
			}
		}

		return true;
	}

	/**
	 * Function used as a trigger after course deleted
	 *
	 * @param   ARRAY  $courseIds  Course IDs
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	public function onAfterCourseDelete($courseIds)
	{
		// For now courseIds array is accepted
		return true;
	}

	/**
	 * Don't allow categories to be deleted if they contain items or subcategories with items
	 *
	 * @param   string  $context  The context for the content passed to the plugin.
	 * @param   object  $data     The data relating to the content that was deleted.
	 *
	 * @return  boolean
	 *
	 * @since   1.6
	 */
	public function onContentBeforeDelete($context, $data)
	{
		if ($context == 'com_content.article' && $data->get('id'))
		{
			$articleTitle = $this->_checkLessonArticle($data->get('id'));

			if (!empty($articleTitle))
			{
				$lang = Factory::getLanguage();
				$lang->load('plg_tjtextmedia_joomlacontent', JPATH_ADMINISTRATOR);
				$application = Factory::getApplication();
				$application->enqueueMessage(Text::sprintf('PLG_TJLMS_SYSTEM_MESSGE_USED_ARTICLE_DELETE', $data->get('title')), 'Warning');

				return false;
			}
		}

		// Skip plugin if we are deleting something other than categories
		if ($context != 'com_categories.category')
		{
			return true;
		}

		$extension = Factory::getApplication()->input->getString('extension');

		if (!($extension == 'com_tjlms' || $extension == 'com_tmt.questions'))
		{
			return true;
		}

		$tableInfo = array(
			'com_tjlms' => array('table_name' => '#__tjlms_courses'),
			'com_tmt.questions' => array('table_name' => '#__tmt_questions')
		);

		// Now check to see if this is a known core extension
		if (isset($tableInfo[$extension]))
		{
			// Get table name for known core extensions
			$table = $tableInfo[$extension]['table_name'];

			// See if this category has any content items
			$count = $this->_countItemsInCategory($table, $data->get('id'));

			// Return false if db error
			if ($count === false)
			{
				$result = false;
			}
			else
			{
				// Show error if items are found in the category
				if ($count > 0)
				{
					$msg = Text::sprintf('COM_CATEGORIES_DELETE_NOT_ALLOWED', $data->get('title')) .
						Text::plural('COM_CATEGORIES_N_ITEMS_ASSIGNED', $count);
					JError::raiseWarning(403, $msg);
					$result = false;
				}

				// Check for items in any child categories (if it is a leaf, there are no child categories)
				if (!$data->isLeaf())
				{
					$count = $this->_countItemsInChildren($table, $data->get('id'), $data);

					if ($count === false)
					{
						$result = false;
					}
					elseif ($count > 0)
					{
						$msg = Text::sprintf('COM_CATEGORIES_DELETE_NOT_ALLOWED', $data->get('title')) .
							Text::plural('COM_CATEGORIES_HAS_SUBCATEGORY_ITEMS', $count);
						JError::raiseWarning(403, $msg);
						$result = false;
					}
				}
			}

			return $result;
		}
	}

	/**
	 * Get count of items in a category
	 *
	 * @param   string   $table  table name of component table (column is catid)
	 * @param   integer  $catid  id of the category to check
	 *
	 * @return  mixed  count of items found or false if db error
	 *
	 * @since   1.6
	 */
	private function _countItemsInCategory($table, $catid)
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true);

		$catIdCol = 'catid';

		if ($table == '#__tmt_questions')
		{
			$catIdCol = 'category_id';
		}

		// Count the items in this category
		$query->select('COUNT(id)')
			->from($table)
			->where($catIdCol . ' = ' . $catid);
		$db->setQuery($query);

		try
		{
			$count = $db->loadResult();
		}
		catch (RuntimeException $e)
		{
			JError::raiseWarning(500, $e->getMessage());

			return false;
		}

		return $count;
	}

	/**
	 * Get count of items in a category's child categories
	 *
	 * @param   string   $table  table name of component table (column is catid)
	 * @param   integer  $catid  id of the category to check
	 * @param   object   $data   The data relating to the content that was deleted.
	 *
	 * @return  mixed  count of items found or false if db error
	 *
	 * @since   1.6
	 */
	private function _countItemsInChildren($table, $catid, $data)
	{
		$db = Factory::getDbo();

		$catIdCol = 'cat_id';

		if ($table == '#__tmt_questions')
		{
			$catIdCol = 'category_id';
		}

		// Create subquery for list of child categories
		$childCategoryTree = $data->getTree();

		// First element in tree is the current category, so we can skip that one
		unset($childCategoryTree[0]);
		$childCategoryIds = array();

		foreach ($childCategoryTree as $node)
		{
			$childCategoryIds[] = $node->id;
		}

		// Make sure we only do the query if we have some categories to look in
		if (count($childCategoryIds))
		{
			// Count the items in this category
			$query = $db->getQuery(true)
				->select('COUNT(id)')
				->from($table)
				->where($catIdCol . ' IN (' . implode(',', $childCategoryIds) . ')');
			$db->setQuery($query);

			try
			{
				$count = $db->loadResult();
			}
			catch (RuntimeException $e)
			{
				JError::raiseWarning(500, $e->getMessage());

				return false;
			}

			return $count;
		}
		else
			// If we didn't have any categories to check, return 0
		{
			return 0;
		}
	}

	/**
	 * Function to add cert track entry
	 *
	 * @param   INT  $courseId  Course ID
	 * @param   INT  $actorId   User to completed the course
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	public function addCertEntry($courseId , $actorId = '')
	{
		$path = JPATH_ROOT . '/components/com_tjlms/models/course.php';

		if (!class_exists('TjlmsModelcourse'))
		{
			JLoader::register('TjlmsModelcourse', $path);
			JLoader::load('TjlmsModelcourse');
		}

		$TjlmsModelcourse = new TjlmsModelcourse;

		$TjlmsModelcourse->addCertEntry($courseId, $actorId);
	}

	/**
	 * Show warning if article is not published and set as lesson.
	 * Content is passed by reference. Method is called after the content is saved.
	 *
	 * @param   string  $context  The context of the content passed to the plugin (added in 1.6).
	 * @param   object  $article  A JTableContent object.
	 * @param   bool    $isNew    If the content is just about to be created.
	 *
	 * @return  void
	 *
	 * @since   2.5
	 */
	public function onContentAfterSave($context, $article, $isNew)
	{
		if (($context == 'com_content.article' || $context == 'com_content.form') && !$isNew && $article->id && $article->state != 1)
		{
			$articleTitle = $this->_checkLessonArticle($article->id);

			if (!empty($articleTitle))
			{
				$lang = Factory::getLanguage();
				$lang->load('plg_tjtextmedia_joomlacontent', JPATH_ADMINISTRATOR);
				$application = Factory::getApplication();
				$application->enqueueMessage(Text::sprintf('PLG_TJLMS_SYSTEM_MESSGE_USED_ARTICLE_SAVE', $article->title), 'Warning');
			}
		}
	}

	/**
	 * Check if the category or its sub categories are used in some of the content
	 *
	 * @param   string  $extension  extension of the cat like com_tjlms or com_tmt.questions
	 * @param   object  $data       A cat table content
	 *
	 * @return  void
	 *
	 * @since   2.5
	 */
	public function checkifcategoryUsed($extension, $data)
	{
		$result = true;

		$tableInfo = array(
			'com_tjlms' => array('table_name' => '#__tjlms_courses'),
			'com_tmt.questions' => array('table_name' => '#__tmt_questions')
		);

		// Now check to see if this is a known core extension
		if (isset($tableInfo[$extension]))
		{
			// Get table name for known core extensions
			$table = $tableInfo[$extension]['table_name'];

			// See if this category has any content items
			$count = $this->_countItemsInCategory($table, $data->get('id'));

			// Return false if db error
			if ($count === false)
			{
				$result = false;
			}
			elseif ($count > 0)
			{
					$result = false;
			}
			elseif (!$data->isLeaf()) // Check for items in any child categories (if it is a leaf, there are no child categories)
			{
					$count = $this->_countItemsInChildren($table, $data->get('id'), $data);

					if ($count === false || $count > 0)
					{
							$result = false;
					}
			}

			return $result;
		}
	}

	/**
	 * Do not change article state is set as Course Lesson.
	 *
	 * @param   string   $context  The context for the content passed to the plugin.
	 * @param   array    $pks      A list of primary key ids of the content that has changed state.
	 * @param   integer  $value    The value of the state that the content has been changed to.
	 *
	 * @return  boolean
	 *
	 * @since   3.1
	 */
	public function onContentChangeState($context, $pks, $value)
	{
		$application = Factory::getApplication();
		$db = Factory::getDBO();

		/* show error message if cat state is chnaged to to unplublish/trash/delete if its been used in courses */
		if ($context == 'com_categories.category' && $value != 1 && !empty($pks))
		{
			$extension = Factory::getApplication()->input->getString('extension');

			if ($extension == 'com_tjlms' || $extension == 'com_tmt.questions')
			{
				foreach ($pks as $cat_id)
				{
					$category = Table::getInstance('Category', 'JTable', array('dbo', $db));
					$category->load(array('id' => $cat_id, 'extension' => $extension));
					$checkifcategoryUsed = $this->checkifcategoryUsed($extension, $category);

					if ($checkifcategoryUsed == false)
					{
						$cats_with_items[] = $category->title;
					}
				}

				if (!empty($cats_with_items))
				{
					$message = Text::sprintf("PLG_TJLMS_SYSTEM_MESSGE_USED_CAT_CHAGE_STATE", implode('<br />', $cats_with_items));
					$application->enqueueMessage($message, 'Warning');
				}
			}
		}

		/* Show error message if article state is changed and set as Course Lesson.*/
		if ($context == 'com_content.article' && $value != 1 && is_array($pks) && !empty($pks))
		{
			$articleTitle = $this->_checkLessonArticle($pks);

			if (!empty($articleTitle))
			{
				$message = Text::sprintf("PLG_TJLMS_SYSTEM_MESSGE_USED_ARTICLE_CHAGE_STATE", implode('<br />', $articleTitle));
				$application->enqueueMessage($message, 'Warning');
			}
		}

		return true;
	}

	/**
	 * Check if articles is set as lesson.
	 *
	 * @param   array  $cid  A list of primary key ids of the content that has changed state.
	 *
	 * @return  Array
	 *
	 * @since   1.1
	 */
	private function _checkLessonArticle($cid)
	{
		PluginHelper::importPlugin('tjtextmedia', 'joomlacontent');
		Factory::getApplication()->triggerEvent('isLessonArticle', array($cid, &$lesson_articles));

		if (!empty($lesson_articles))
		{
			$lesson_articles = (array) $lesson_articles;

			// Get a db connection.
			$db = Factory::getDbo();
			$articleTitle = array();

			foreach ($lesson_articles as $article_id)
			{
				$Content = Table::getInstance('Content', 'Table', array('dbo', $db));
				$Content->load(array('id' => $article_id));
				$articleTitle[$article_id] = $Content->title;
			}

			if (!empty($articleTitle))
			{
				return $articleTitle;
			}
		}
	}

	/**
	 * Function used as a trigger before lesson deleted
	 *
	 * @param   INT  $courseId   Course ID
	 * @param   INT  $lessonIds  Lesson ID
	 * @param   INT  $mediaIds   Media ID
	 *
	 * @return  boolean true or false
	 *
	 * @since  1.0.0
	 */
	public function onBeforeLessonDelete($courseId, $lessonIds, $mediaIds)
	{
		foreach ($lessonIds as $lessonId)
		{
			$obj = $this->TjlmsLessonHelper->getLessonColumn($lessonId,  array('format', 'media_id'));
			$plug_type = 'tj' . $obj->format;
			PluginHelper::importPlugin($plug_type);

			// Trigger all "Shika" plugins method that delete the lesson entery
			Factory::getApplication()->triggerEvent('onBeforeLessonDeletion', array($plug_type, $courseId, $lessonId, $obj));
		}

		return 1;
	}

	/**
	 * Method to get filter post data before group based assignment
	 *
	 * @param   Array  $data     Data array
	 * @param   Array  $options  options array
	 *
	 * @return Array
	 *
	 * @since 3.0
	 */
	public function onBeforeAssignment($data, $options=array())
	{
		// If do not update already enrolled, remove those user_ids from array
		if (!empty($options['element']) && $options['element'] == 'com_tjlms.course' && !empty($data['recommend_friends'])
			&& !empty($data['group_assignment']))
		{
			if (!$data['update_existing_users'])
			{
				$db_options = array('IdOnly' => 1, 'getResultType' => 'loadColumn', 'state' => array(0, 1));

				JLoader::import('components.com_tjlms.helpers.main', JPATH_SITE);
				$comtjlmsHelper = new ComtjlmsHelper;
				$enrolled_users = $comtjlmsHelper->getCourseEnrolledUsers((int) $options['element_id'], $db_options);

				if (!empty($enrolled_users))
				{
					$recommend_friends = array_diff($data['recommend_friends'], $enrolled_users);
					$data['recommend_friends'] = $recommend_friends;
				}
			}

			// Filter Manager related data
			JLoader::import('administrator.components.com_tjlms.helpers.tjlms', JPATH_SITE);
			$canEnroll = TjlmsHelper::canManageEnrollment();

			// If only Manager, send only to subusers
			if ($canEnroll === -2 || !empty($data['onlysubuser']))
			{
				$hasUsers = TjlmsHelper::getSubusers();
				$data['recommend_friends'] = array_intersect($data['recommend_friends'], $hasUsers);
			}
		}

		return $data;
	}

	/**
	 * Function is triggered when enrollment state is changed
	 *
	 * @param   INT  $enrolmentId  primary key of the enrolment table
	 *
	 * @return  boolean true or false
	 *
	 * @since  1.0.0
	 */
	public function onAfterEnrolementStatusChange($enrolmentId)
	{
		$enrollTable = Table::getInstance('Enrolledusers', 'TjlmsTable', array('dbo', $this->db));
		$enrollTable->load($enrolmentId);

		if ($enrollTable->id)
		{
			$this->comtjlmsHelperObj->addUserToGroup($enrollTable->user_id, $enrollTable->course_id, $enrollTable->state);
		}
	}

	/**
	 * Function used as a trigger after order status update
	 *
	 * @param   INT     $orderId  Order ID
	 * @param   STRING  $status   Status of order
	 *
	 * @return  boolean true or false
	 *
	 * @since  1.0.0
	 */
	public function onOrderAfterStatusChange($orderId, $status)
	{
		// For now orderId and status is accepted
		return true;
	}

	/**
	 * Utility method to act on a user after it has been saved.
	 *
	 * This method assign default group, default page and default app.
	 *
	 * @param   array    $user     Holds the new user data.
	 * @param   boolean  $isnew    True if a new user is stored.
	 * @param   boolean  $success  True if user was successfully stored in the database.
	 * @param   string   $msg      Message.
	 *
	 * @return  boolean true or false
	 *
	 * @since   1.3.39
	 */
	public function onUserAfterSave($user, $isnew, $success, $msg)
	{
		if ($user['id'] && $isnew)
		{
			$params = ComponentHelper::getParams('com_tjlms');
			$app    = Factory::getApplication();

			if ($params['social_integration'] == 'easysocial' && $app->isClient('site') && (ComponentHelper::getComponent('com_easysocial', true)->enabled))
			{
				$esuser = ES::user($user['id']);
				$registration = ES::model('Registration');
				$registration->logRegistrationActivity($esuser);

				return true;
			}
		}
	}
}
