<?php
/**
 * @package    JLike
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Component\ComponentHelper;

// Import library dependencies
jimport('joomla.plugin.plugin');
jimport('joomla.filesystem.file');

/**
 * jLike Tjlms plugin class.
 *
 * @since  1.0.0
 */
class PlgContentJLike_Tjlmslesson extends CMSPlugin
{
	public $jlikehelperObj;

	/**
	 * Constructor - note in Joomla 2.5 PHP4.x is no longer supported so we can use this.
	 *
	 * @param   object  &$subject  The object to observe
	 * @param   array   $config    An array that holds the plugin configuration
	 *
	 * @since  1.0.0
	 */
	public function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);
		$lang = Factory::getLanguage();
		$lang->load('plg_content_jlike_tjlmslesson', JPATH_ADMINISTRATOR);

		$path = JPATH_SITE . '/components/com_tjlms/helpers/main.php';
		$this->comtjlmsHelper = '';

		if (File::exists($path))
		{
			if (!class_exists('comtjlmsHelper'))
			{
				JLoader::register('comtjlmsHelper', $path);
				JLoader::load('comtjlmsHelper');
			}

			$this->comtjlmsHelper = new comtjlmsHelper;
		}

		$helperPath = JPATH_SITE . '/components/com_jlike/helper.php';
		$this->jlikehelperObj = '';

		if (file_exists(JPATH_SITE . '/components/com_jlike/helper.php'))
		{
			// Require_once $path;
			JLoader::register('ComjlikeHelper', $helperPath);
			JLoader::load('ComjlikeHelper');

			$this->jlikehelperObj = new comjlikeHelper;
		}
	}

	/**
	 * Function used to get the HTML for Notes to be shown for item
	 *
	 * @param   STRING  $context      The view and layout of item e.g.com_tjlms.course
	 * @param   INT     $lesson_id    Id of lesson
	 * @param   STRING  $lessontitle  Title of the lesson
	 *
	 * @return  $html
	 *
	 * @since  1.0.0
	 */
	public function onShowlikebuttonforlesson($context, $lesson_id, $lessontitle)
	{
		$app = Factory::getApplication();

		if ($app->getName() != 'site')
		{
			return;
		}

		if (($app->scope != 'com_tjlms' AND $context != 'com_tjlms.lesson'))
		{
			return;
		}

		$html = '';

		$item_url = 'index.php?option=com_tjlms&view=lesson&lesson_id=' . $lesson_id;

		$data_toset	=	array();
		$data_toset['cont_id']	=	$lesson_id;
		$data_toset['element']	=	$context;
		$data_toset['title']	=	$lessontitle;
		$data_toset['url']	=	$item_url;
		$data_toset['plg_type'] = 'content';
		$data_toset['plg_name'] = 'jlike_tjlmslesson';
		$data_toset['show_like_buttons'] = 1;
		$data_toset['show_comments'] = -1;
		$data_toset['show_note'] = 0;
		$data_toset['show_list'] = 0;
		$data_toset['showrecommendbtn'] = 0;

		$app->input->set('data', json_encode($data_toset));

		if ($this->jlikehelperObj)
		{
			$html = $this->jlikehelperObj->showlikebuttons();
		}

		return $html;
	}

	/**
	 * Function used to get the HTML for Lists to be shown for item
	 *
	 * @param   STRING  $context      The view and layout of item e.g.com_tjlms.course
	 * @param   INT     $lesson_id    Id of lesson
	 * @param   STRING  $lessontitle  Title of the lesson
	 *
	 * @return  $html
	 *
	 * @since  1.0.0
	 */
	public function onShowLists($context, $lesson_id, $lessontitle)
	{
		$app = Factory::getApplication();

		if ($app->getName() != 'site')
		{
			return;
		}

		if (($app->scope != 'com_tjlms' AND $context != 'com_tjlms.lesson'))
		{
			return;
		}

		$html = '';

		$item_url = 'index.php?option=com_tjlms&view=lesson&lesson_id=' . $lesson_id;

		$data_toset	=	array();
		$data_toset['cont_id']	=	$lesson_id;
		$data_toset['element']	=	$context;
		$data_toset['title']	=	$lessontitle;
		$data_toset['url']	=	$item_url;
		$data_toset['plg_type'] = 'content';
		$data_toset['plg_name'] = 'jlike_tjlmslesson';
		$data_toset['show_like_buttons'] = 0;
		$data_toset['show_comments'] = 0;
		$data_toset['show_note'] = 0;
		$data_toset['show_list'] = 1;
		$data_toset['toolbar_buttons'] = 0;
		$data_toset['showrecommendbtn'] = 0;

		$app->input->set('data', json_encode($data_toset));

		if ($this->jlikehelperObj)
		{
			$html = $this->jlikehelperObj->showlikebuttons();
		}

		return $html;
	}

	/**
	 * Function used to get the HTML for Notes to be shown for item
	 *
	 * @param   STRING  $context      The view and layout of item e.g.com_tjlms.course
	 * @param   INT     $lesson_id    Id of lesson
	 * @param   STRING  $lessontitle  Title of the lesson
	 *
	 * @return  $html
	 *
	 * @since  1.0.0
	 */
	public function onShowNotes($context, $lesson_id, $lessontitle)
	{
		$app = Factory::getApplication();

		if ($app->getName() != 'site')
		{
			return;
		}

		if (($app->scope != 'com_tjlms' AND $context != 'com_tjlms.lesson'))
		{
			return;
		}

		$html = '';

		$item_url = 'index.php?option=com_tjlms&view=lesson&lesson_id=' . $lesson_id;

		$data_toset	=	array();
		$data_toset['cont_id']	=	$lesson_id;
		$data_toset['element']	=	$context;
		$data_toset['title']	=	$lessontitle;
		$data_toset['url']	=	$item_url;
		$data_toset['plg_type'] = 'content';
		$data_toset['plg_name'] = 'jlike_tjlmslesson';
		$data_toset['show_like_buttons'] = 0;
		$data_toset['show_comments'] = 0;
		$data_toset['show_note'] = 1;
		$data_toset['show_list'] = 0;
		$data_toset['toolbar_buttons'] = 0;
		$data_toset['showrecommendbtn'] = 0;

		$app->input->set('data', json_encode($data_toset));

		if ($this->jlikehelperObj)
		{
			$html = $this->jlikehelperObj->showlikebuttons();
		}

		return $html;
	}

	/**
	 * Function used to get the HTML for Cooments to be shown for item
	 *
	 * @param   STRING  $context      The view and layout of item e.g.com_tjlms.course
	 * @param   INT     $lesson_id    Id of lesson
	 * @param   STRING  $lessontitle  Title of the lesson
	 *
	 * @return  $html
	 *
	 * @since  1.0.0
	 */
	public function onShowComments($context, $lesson_id, $lessontitle)
	{
		$app = Factory::getApplication();

		if ($app->getName() != 'site')
		{
			return;
		}

		if (($app->scope != 'com_tjlms' AND $context != 'com_tjlms.lesson'))
		{
			return;
		}

		$html = '';

		$item_url = 'index.php?option=com_tjlms&view=lesson&lesson_id=' . $lesson_id;

		$data_toset	=	array();
		$data_toset['cont_id']	=	$lesson_id;
		$data_toset['element']	=	$context;
		$data_toset['title']	=	$lessontitle;
		$data_toset['url']	=	$item_url;
		$data_toset['plg_name'] = 'jlike_tjlmslesson';
		$data_toset['plg_type'] = 'content';
		$data_toset['show_like_buttons'] = 0;
		$data_toset['show_comments'] = 1;
		$data_toset['show_note'] = 0;
		$data_toset['show_list'] = 0;
		$data_toset['toolbar_buttons'] = 0;
		$data_toset['showrecommendbtn'] = 0;

		$app->input->set('data', json_encode($data_toset));

		if ($this->jlikehelperObj)
		{
			$html = $this->jlikehelperObj->showlike();
		}

		return $html;
	}

	/**
	 * Function used to get social integration
	 *
	 * @return  $socialIntegration
	 *
	 * @since  1.0.0
	 */
	public function jlike_tjlmslessonGetSocialIntegration()
	{
		$params = ComponentHelper::getParams('com_tjlms');

		return $socialIntegration = strtolower($params->get('social_integration', 'joomla', 'STRING'));
	}

	/**
	 * Function used to get user which should be removed from the list
	 *
	 * @param   INT  $courseId  Id of course
	 *
	 * @return  $enroledUsers
	 *
	 * @since  1.0.0
	 */
	public function jlike_tjlmslessonGetAdditionalWhereCondition($courseId)
	{
		$where = '';
		$comtjlmsHelper = new comtjlmsHelper;
		$options = array('IdOnly' => 1, 'getResultType' => 'loadColumn');

		return $enroledUsers   = $comtjlmsHelper->getCourseEnrolledUsers($courseId, $options);
	}

	/**
	 * Function used to get course data
	 *
	 * @param   INT  $courseId  Id of course
	 *
	 * @return  $enroledUsers
	 *
	 * @since  1.0.0
	 */
	public function jlike_tjlmslessonGetElementData($courseId)
	{
		$data = array();
		$data['url'] = 'index.php?option=com_tjlms&id=' . $courseId;

		require_once JPATH_SITE . '/components/com_tjlms/helpers/courses.php';
		$TjlmsCoursesHelper = new TjlmsCoursesHelper;
		$data['title'] = $TjlmsCoursesHelper->courseName;

		return $data;
	}

	/**
	 * Function used to plugin params
	 *
	 * @return  $socialIntegration
	 *
	 * @since  1.0.0
	 */
	public function onJlikeTjlmslessonGetParams()
	{
		$app = Factory::getApplication();

		// Merge plugin params plugin params override jlike component params
		$component_params = ComponentHelper::getParams('com_jlike');

		// Temp is the params of plugins
		$temp         = clone $this->params;

		$component_params->merge($temp);

		return $component_params;
	}

	/**
	 * Function used to get course creator
	 *
	 * @param   INT  $lesson_id  Id of lesson
	 *
	 * @return  creator
	 *
	 * @since  1.0.0
	 */
	public function getjlike_tjlmslessonOwnerDetails($lesson_id)
	{
		require_once JPATH_SITE . '/components/com_tjlms/helpers/lesson.php';
		$tjlmsLessonHelper = new TjlmsLessonHelper;
		$res = $tjlmsLessonHelper->getLessonColumn($lesson_id, 'created_by');

		if (!empty($res->course_id))
		{
			if (!$res->created_by)
			{
				require_once JPATH_SITE . '/components/com_tjlms/helpers/courses.php';
				$tjlmsCourseHelper = new TjlmsCoursesHelper;
				$res = $tjlmsCourseHelper->getCourseColumn($res->course_id, 'created_by');
			}
		}

		return $creator = $res->created_by;
	}

/**
	* check selected content follows criteria to send reminder
	*
	* @param   INT  $user_id     user_id ID
	* @param   INT  $element_id  lesson ID
	*
	* @return reminder Array.
	*/
	public function onAfterJlikelessonContentCheckforReminder($user_id, $element_id)
	{
		$db                  = Factory::getDBO();

		$path = JPATH_SITE . '/components/com_tjlms/helpers/tracking.php';
		$this->ComtjlmstrackingHelper = "";

		if (File::exists($path))
		{
			if (!class_exists('ComtjlmstrackingHelper'))
			{
				JLoader::register('ComtjlmstrackingHelper', $path);
				JLoader::load('ComtjlmstrackingHelper');
			}
		}

		$this->ComtjlmstrackingHelper = new ComtjlmstrackingHelper;

		// Get lesson deatails if lesson,course and course_category published
		$query = $db->getQuery(true);
		$query->select('*');
		$query->from($db->quoteName('#__tjlms_lessons') . 'as l');
		$query->join('LEFT', $db->quoteName('#__tjlms_courses') . 'as c on c.id=l.course_id');
		$query->join('LEFT', $db->quoteName('#__categories') . 'as cat on cat.id=c.catid');
		$query->where('l.id =' . $element_id);
		$query->where('c.state = 1');
		$query->where('cat.published = 1');
		$query->where('l.state = 1');
		$db->setQuery($query);
		$lesson = $db->loadObject();

			if ($lesson)
			{
				// Get lesson_status according to attempt_grading
				$result = $this->ComtjlmstrackingHelper->getLessonattemptsGrading($lesson, $user_id);

				// Lesson incomplete send reminder
				if ($result->lesson_status != 'completed')
				{
					return 1;
				}
			}

		return 0;
	}
}
