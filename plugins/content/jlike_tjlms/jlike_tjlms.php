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
use Joomla\CMS\Table\Table;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

// Import library dependencies
jimport('joomla.plugin.plugin');
jimport('joomla.filesystem.file');

/**
 * jLike Tjlms plugin class.
 *
 * @since  1.0.0
 */
class PlgContentJLike_Tjlms extends CMSPlugin
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
		$lang->load('plg_content_jlike_tjlms', JPATH_ADMINISTRATOR);

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
	 * @param   STRING  $context     The view and layout of item e.g.com_tjlms.course
	 * @param   INT     $course_id   Id of lesson
	 * @param   STRING  $cousetitle  Title of the lesson
	 *
	 * @return  $html
	 *
	 * @since  1.0.0
	 */
	public function onAftercourseTitle($context, $course_id, $cousetitle)
	{
		$app = Factory::getApplication();

		if ($app->getName() != 'site')
		{
			return;
		}

		if (($app->scope != 'com_tjlms' AND $context != 'com_tjlms.course'))
		{
			return;
		}

		$params = $this->onJlike_tjlmsGetParams();

		if (!$params->get('allow_like'))
		{
				return;
		}

		$html = '';

		$item_url = 'index.php?option=com_tjlms&view=course&id=' . $course_id;

		if ($this->comtjlmsHelper)
		{
			$itemId = $this->comtjlmsHelper->getitemid($item_url);
			$item_url .= '&Itemid=' . $itemId;
		}

		$data_toset	=	array();
		$data_toset['cont_id']	=	$course_id;
		$data_toset['element']	=	$context;
		$data_toset['title']	=	$cousetitle;
		$data_toset['url']	=	$item_url;
		$data_toset['plg_name'] = 'jlike_tjlms';
		$data_toset['plg_type'] = 'content';
		$data_toset['show_like_buttons'] = 1;
		$data_toset['show_comments'] = -1;
		$data_toset['showassignbtn'] = 0;
		$data_toset['showrecommendbtn'] = 0;
		$data_toset['showsetgoalbtn'] = 0;

		$app->input->set('data', json_encode($data_toset));

		if ($this->jlikehelperObj)
		{
			$html = $this->jlikehelperObj->showlike();
		}

		return $html;
	}

	/**
	 * Function used to get the HTML for Notes to be shown for item
	 *
	 * @param   STRING   $context     The view and layout of item e.g.com_tjlms.course
	 * @param   integer  $course_id   Id of lesson
	 * @param   STRING   $cousetitle  Title of the lesson
	 *
	 * @return  $html
	 *
	 * @since  1.0.0
	 */
	public function onAftercourseContent($context, $course_id, $cousetitle)
	{
		$params = $this->onJlike_tjlmsGetParams();
		$app = Factory::getApplication();

		if ($app->getName() != 'site')
		{
			return;
		}

		if (($app->scope != 'com_tjlms' AND $context != 'com_tjlms.course'))
		{
			return;
		}

		$isCompInstalled = $this->isComponentEnabled("jlike");

		if (empty($isCompInstalled))
		{
			return;
		}

		$params = $this->onJlike_tjlmsGetParams();

		// If commenting and rating is ON then commenting is considered. If both is OFF then return
		if ($params->get('allow_comments'))
		{
			$show_comments = 1;
			$show_reviews = 0;
			$allowToRate = 0;
		}
		elseif ($params->get('jlike_enable_rating'))
		{
			$isUserAlloedToRate = $this->isUserAlloedToRate($course_id);

			if ($isUserAlloedToRate)
			{
				$show_comments = -1;
				$show_reviews = 1;
				$allowToRate = 1;
			}
			else
			{
				$show_comments = -1;
				$show_reviews = 1;
				$allowToRate = 0;
			}
		}
		else
		{
			// No one option is enabled
			return;
		}

		$html = '';
		$input = Factory::getApplication()->input;
		$option = $input->get('option', '', 'STRING');
		$view = $input->get('view', '', 'STRING');
		$layout = $input->get('layout', '', 'STRING');

		$url = 'index.php?option=com_tjlms&view=course&id=' . $course_id;

		if ($this->comtjlmsHelper)
		{
			$itemId = $this->comtjlmsHelper->getitemid($url);
			$url .= '&Itemid=' . $itemId;
		}

		$data_toset	=	array();
		$data_toset['cont_id']	=	$course_id;
		$data_toset['element']	=	$context;
		$data_toset['title']	=	$cousetitle;
		$data_toset['url']	=	$url;
		$data_toset['plg_name'] = 'jlike_tjlms';
		$data_toset['plg_type'] = 'content';
		$data_toset['show_like_buttons'] = 0;
		$data_toset['show_pwltcb'] = 0;
		$data_toset['show_comments'] = $show_comments;
		$data_toset['show_note'] = 0;
		$data_toset['show_list'] = 0;
		$data_toset['showassignbtn'] = 0;
		$data_toset['showrecommendbtn'] = 0;
		$data_toset['showsetgoalbtn'] = 0;
		$data_toset['show_reviews'] = $show_reviews;
		$data_toset['jlike_allow_rating'] = $allowToRate;
		$app->input->set('data', json_encode($data_toset));

		if ($this->jlikehelperObj)
		{
			$html = $this->jlikehelperObj->showlike();
		}

		return $html;
	}

	/**
	 * Function used to get the HTML for recommend friend layout
	 *
	 * @param   STRING  $context     The view and layout of item e.g.com_tjlms.course
	 * @param   INT     $course_id   Id of course
	 * @param   STRING  $cousetitle  Title of the course
	 *
	 * @return  $html
	 *
	 * @since  1.0.0
	 */
	public function onShowRecommendBtn($context, $course_id, $cousetitle)
	{
		$app = Factory::getApplication();

		if ($app->getName() != 'site')
		{
			return;
		}

		if (($app->scope != 'com_tjlms' AND $context != 'com_tjlms.course'))
		{
			return;
		}

		$html = '';

		$course_url = 'index.php?option=com_tjlms&view=course&id=' . $course_id;

		$data_toset	=	array();
		$data_toset['cont_id']	=	$course_id;
		$data_toset['element']	=	$context;
		$data_toset['title']	=	$cousetitle;
		$data_toset['url']	=	$course_url;
		$data_toset['plg_name'] = 'jlike_tjlms';
		$data_toset['plg_type'] = 'content';
		$data_toset['show_like_buttons'] = 0;
		$data_toset['show_pwltcb'] = 0;
		$data_toset['show_comments'] = -1;
		$data_toset['show_note'] = 0;
		$data_toset['show_list'] = 0;
		$data_toset['toolbar_buttons'] = 0;
		$data_toset['showrecommendbtn'] = 1;
		$data_toset['showsetgoalbtn'] = 0;

		$app->input->set('data', json_encode($data_toset));

		if ($this->jlikehelperObj)
		{
			$html = $this->jlikehelperObj->showlike();
		}

		return $html;
	}

	/**
	 * Function used to get the HTML for assignment
	 *
	 * @param   STRING  $context     The view and layout of item e.g.com_tjlms.course
	 * @param   INT     $course_id   Id of course
	 * @param   STRING  $cousetitle  Title of the course
	 *
	 * @return  $html
	 *
	 * @since  1.0.0
	 */
	public function onShowAssignBtn($context, $course_id, $cousetitle)
	{
		$app = Factory::getApplication();

		if ($app->getName() != 'site')
		{
			return;
		}

		if (($app->scope != 'com_tjlms' AND $context != 'com_tjlms.course'))
		{
			return;
		}

		$html = '';

		$course_url = 'index.php?option=com_tjlms&view=course&id=' . $course_id;

		$data_toset	=	array();
		$data_toset['cont_id']	=	$course_id;
		$data_toset['element']	=	$context;
		$data_toset['title']	=	$cousetitle;
		$data_toset['url']	=	$course_url;
		$data_toset['plg_name'] = 'jlike_tjlms';
		$data_toset['plg_type'] = 'content';
		$data_toset['show_like_buttons'] = 0;
		$data_toset['show_pwltcb'] = 0;
		$data_toset['show_comments'] = -1;
		$data_toset['show_note'] = 0;
		$data_toset['show_list'] = 0;
		$data_toset['toolbar_buttons'] = 0;
		$data_toset['showrecommendbtn'] = 0;
		$data_toset['showassignbtn'] = 1;
		$data_toset['showsetgoalbtn'] = 0;

		$app->input->set('data', json_encode($data_toset));

		if ($this->jlikehelperObj)
		{
			$html = $this->jlikehelperObj->showlike();
		}

		return $html;
	}

	/**
	 * Function used to get the HTML for assignment SET Goal
	 *
	 * @param   STRING  $context     The view and layout of item e.g.com_tjlms.course
	 * @param   INT     $course_id   Id of course
	 * @param   STRING  $cousetitle  Title of the course
	 *
	 * @return  $html
	 *
	 * @since  1.0.0
	 */
	public function onShowSetGoalBtn($context, $course_id, $cousetitle)
	{
		$app = Factory::getApplication();

		if ($app->getName() != 'site')
		{
			return;
		}

		if (($app->scope != 'com_tjlms' AND $context != 'com_tjlms.course'))
		{
			return;
		}

		$html = '';

		$course_url = 'index.php?option=com_tjlms&view=course&id=' . $course_id;

		$params = $this->onJlike_tjlmsGetParams();

		if (!$params->get('set_goal'))
		{
			return;
		}

		$data_toset	=	array();
		$data_toset['cont_id']	=	$course_id;
		$data_toset['element']	=	$context;
		$data_toset['title']	=	$cousetitle;
		$data_toset['url']	=	$course_url;
		$data_toset['plg_name'] = 'jlike_tjlms';
		$data_toset['plg_type'] = 'content';
		$data_toset['show_like_buttons'] = 0;
		$data_toset['show_pwltcb'] = 0;
		$data_toset['show_comments'] = -1;
		$data_toset['show_note'] = 0;
		$data_toset['show_list'] = 0;
		$data_toset['toolbar_buttons'] = 0;
		$data_toset['showrecommendbtn'] = 0;
		$data_toset['showassignbtn'] = 0;
		$data_toset['showsetgoalbtn'] = 1;

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
	public function onAfterjlike_tjlmsGetSocialIntegration()
	{
		$params = ComponentHelper::getParams('com_tjlms');

		return $socialIntegration = strtolower($params->get('social_integration', 'joomla', 'STRING'));
	}

	/**
	 * Function used to get user which should be removed from the list
	 *
	 * @param   INT     $courseId  Id of course
	 * @param   OBJECT  $query     Query object
	 * @param   STRING  $type      Assign/Recommend
	 *
	 * @return  NULL
	 *
	 * @since  1.0.0
	 */
	public function onAfterjlike_tjlmsGetAdditionalWhereCondition($courseId, $query, $type)
	{
		// Only display users who can access this course
		if ($courseId && $type == 'assign')
		{
			$db = Factory::getDbo();
			$comtjlmsHelper = new ComtjlmsHelper;
			Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjlms/tables');
			$courseTable = Table::getInstance('course', 'TjlmsTable');
			$courseTable->load(array('id' => $courseId));
			$groups = $comtjlmsHelper->getACLGroups($courseTable->access);

			if (!empty($groups))
			{
				$groups = array_keys($groups);
				$query->where('a.id IN (
					select user_id from #__user_usergroup_map where group_id IN (' . implode(',', $db->quote($groups)) . ')
				)');
			}
		}
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
	public function onAfterjlike_tjlmsGetElementData($courseId)
	{
		$data = array();

		$data['url'] = 'index.php?option=com_tjlms&view=course&id=' . $courseId;

		JLoader::import('components.com_tjlms.helpers.courses', JPATH_SITE);
		$tjlmsCoursesHelper = new TjlmsCoursesHelper;
		$fields = array('title','short_desc','access');
		$res 	= $tjlmsCoursesHelper->getCourseColumn($courseId, $fields);
		$data['title'] 		= $res->title;
		$data['short_desc'] = $res->short_desc;
		$data['groups'] 	= $this->comtjlmsHelper->getACLGroups($res->access);

		return $data;
	}

	/**
	 * Function used to plugin params
	 *
	 * @return  $socialIntegration
	 *
	 * @since  1.0.0
	 */
	public function onJlike_tjlmsGetParams()
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
	 * @param   INT  $couse_id  Id of course
	 *
	 * @return  creator
	 *
	 * @since  1.0.0
	 */
	public function onAfterGetjlike_tjlmsOwnerDetails($couse_id)
	{
		require_once JPATH_SITE . '/components/com_tjlms/helpers/courses.php';
		$TjlmsCoursesHelper = new TjlmsCoursesHelper;
		$res = $TjlmsCoursesHelper->getcourseInfo($couse_id);

		return $creator = $res->created_by;
	}

	/**
	 * Function used to get the HTML for Notes to be shown for item
	 *
	 * @param   STRING  $context     The view and layout of item e.g.com_tjlms.course
	 * @param   INT     $course_id   Id of lesson
	 * @param   STRING  $cousetitle  Title of the lesson
	 *
	 * @return  $html
	 *
	 * @since  1.0.0
	 */
	public function onGetTjlmsCourseAvgRating($context, $course_id, $cousetitle)
	{
		$app = Factory::getApplication();

		if ($app->getName() != 'site')
		{
			return;
		}

		if (($app->scope != 'com_tjlms' AND $context != 'com_tjlms.course'))
		{
			return;
		}

		$isCompInstalled = $this->isComponentEnabled("jlike");

		if (empty($isCompInstalled))
		{
			return;
		}

		$params = $this->onJlike_tjlmsGetParams();

		if ($params->get('jlike_enable_rating'))
		{
			$show_reviews = 1;
		}
		else
		{
			// No one option is enabled
			return;
		}

		$html = '';

		$course_url = 'index.php?option=com_tjlms&view=course&id=' . $course_id;

		if ($this->comtjlmsHelper)
		{
			$itemId = $this->comtjlmsHelper->getitemid($course_url);
			$course_url .= '&Itemid=' . $itemId;
		}

		$html = '';
		$jlike_allow_rating = $this->params->get('jlike_allow_rating');

		$app->input->set(
		'data', json_encode(
			array(
			'cont_id' => $course_id,
			'element' => $context,
			'title' => $cousetitle,
			'url' => $course_url,
			'plg_name' => 'jlike_tjlms',
			'plg_type' => 'content',
			'show_comments' => 0,
			'show_reviews' => 0,
			'show_like_buttons' => 0,
			'jlike_allow_rating' => $jlike_allow_rating
			)
			)
			);

		if ($this->jlikehelperObj)
		{
			$html = $this->jlikehelperObj->getAvarageRating();
		}

		return $html;
	}

	/**
	 * Method to get allow rating to bought the product user
	 *
	 * @param   string  $option  component name. eg quick2cart for component com_quick2cart etc.
	 *
	 * @return void
	 *
	 * @since 3.0
	 */
	private function isComponentEnabled($option)
	{
		// Load lib
		jimport('joomla.filesystem.file');

		$status = 0;

		if (File::exists(JPATH_ROOT . '/components/com_' . $option . '/' . $option . '.php'))
		{
			if (ComponentHelper::isEnabled('com_' . $option, true))
			{
				$status = 1;
			}
		}

		return $status;
	}

	/**
	 * Method to check whether user is alled to rate or not.[ depending on option]
	 *
	 * @param   integer  $course_id  Id of lesson
	 *
	 * @return void
	 *
	 * @since 3.0
	 */
	private function isUserAlloedToRate($course_id)
	{
		$params = $this->onJlike_tjlmsGetParams();
		$isAllow = 0;
		$jlike_allowUserToRate = $params->get('jlike_allowUserToRate', "enrolledUser");

		// Check for ration option
		if ($params->get('jlike_enable_rating'))
		{
			$userId = Factory::getUser()->id;

			// Check type of used is allowed to rate
			$jlike_allowUserToRate = $params->get('jlike_allowUserToRate', "registoredUser");

			if ($jlike_allowUserToRate == "registoredUser")
			{
				if ($userId)
				{
					$isAllow = 1;
				}
			}
			elseif ($jlike_allowUserToRate = "enrolledUser")
			{
				if ($userId)
				{
					try
					{
						$db    = Factory::getDbo();
						$query = $db->getQuery(true);
						$query->select("eu.id")->from('#__tjlms_enrolled_users as eu')
						->where("eu.course_id=" . $course_id)
						->where("eu.user_id=" . $userId);
						$db->setQuery($query);

						return count($db->loadObjectList());
					}
					catch (Exception $e)
					{
						$this->setError($e->getMessage());

						return 0;
					}
				}
			}
		}

		return $isAllow;
	}

/**
	* check selected content follows criteria to send reminder
	*
	* @param   INT  $user_id     user_id ID
	* @param   INT  $element_id  course ID
	*
	* @return reminder Array.
	*/
	public function onAfterJlikecourseContentCheckforReminder($user_id, $element_id)
	{
		$db                  = Factory::getDBO();

		// Check Course and course category published
		$query = $db->getQuery(true);
		$query->select('c.id');
		$query->from($db->quoteName('#__tjlms_courses') . 'as c');
		$query->join('LEFT', $db->quoteName('#__categories') . 'as cat on cat.id=c.catid');
		$query->where('c.id =' . $element_id);
		$query->where('c.state = 1');
		$query->where('cat.published = 1');
		$db->setQuery($query);
		$course = $db->loadResult();

			// Check course completion status
			if ($course)
			{
				$query = $db->getQuery(true);

				$query->select('id,status');
				$query->from($db->quoteName('#__tjlms_course_track', 'e'));
				$query->where($db->quoteName('e.course_id') . ' = ' . $element_id);
				$query->where($db->quoteName('e.user_id') . ' = ' . $user_id);
				$db->setQuery($query);

				$rec_exist = $db->loadObject();

				// Course attempted and status complete dont send reminder
				if ($rec_exist and $rec_exist->status == 'C')
				{
					return 0;
				}

				// Check if enrollment is published
				require_once JPATH_ADMINISTRATOR . '/components/com_tjlms/models/manageenrollments.php';
				$model     = BaseDatabaseModel::getInstance('Manageenrollments', 'TjlmsModel');
				$published = $model->checkIfEnrollmentPublished($element_id, $user_id);

				if (!$published)
				{
					return 0;
				}
				// End enrollment publish check

				return 1;
			}

		return 0;
	}
}
