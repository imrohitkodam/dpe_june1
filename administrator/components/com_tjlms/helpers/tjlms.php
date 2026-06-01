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
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Uri\Uri;

/**
 * Tjlms helper.
 *
 * @since  1.0.0
 */
class TjlmsHelper
{
	/**
	 * Configure the Linkbar.
	 *
	 * @param   STRING  $vName  View name
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	public static function addSubmenu($vName = '')
	{
		if (JVERSION < '4.0.0')
		{
			// To add the css to show the sidemenu wherever the tjlms addSubmenu gets overriden.
			// Moved from plugin to submenu function to show the sidebar css all the  time inside tjlms component.
			$document = Factory::getDocument();
			$document->addStyleSheet(Uri::root(true) . '/media/com_tjlms/css/tjlms_backend.css');
			$document->addStyleSheet(Uri::root(true) . '/media/com_tjlms/font-awesome/css/font-awesome.min.css');
			$js = "jQuery(document).ready(function(){jQuery('.container-main').addClass('tjlms-wrapper')});";
			$document->addScriptDeclaration($js);

			$user = Factory::getUser();
			$canDo = self::getActions();

			// Get component params
			$tjlmsparams = ComponentHelper::getParams('com_tjlms');
			$catTmtUrl = 'index.php?option=com_categories&view=categories&extension=com_tmt.questions';
			$manageEnrollmentUrl = 'index.php?option=com_tjlms&view=manageenrollments';
			$singlecoursereporturl = 'index.php?option=com_tjlms&view=singlecoursereport';

			$option = Factory::getApplication()->input->get('option', '', 'STRING');

			JHtmlSidebar::addEntry(
				Text::_('COM_TJLMS_TITLE_DASHBOARD'), 'index.php?option=com_tjlms&view=dashboard',
				$vName == 'dashboard' && $option == 'com_tjlms'
				);

			JHtmlSidebar::addEntry(
				Text::_('COM_TJLMS_ADD_TJDASHBOARD_MENUE'), 'index.php?option=com_tjdashboard&view=dashboard&layout=default&client=com_tjlms&dashboard_id=1',
				$vName == 'dashboard' && $option == 'com_tjdashboard'
			);

			if ($canDo->get('view.coursecategories'))
			{
				$catTjlmsUrl = 'index.php?option=com_categories&extension=com_tjlms';
				JHtmlSidebar::addEntry(Text::_('COM_TJLMS_SUBMENU_CATEGORIES'), $catTjlmsUrl, $vName == 'categories');
			}

			$canManageEnroll = self::canManageEnrollment();

			if ($canDo->get('core.create') || $canDo->get('core.delete') || $canDo->get('core.edit')
				|| $canDo->get('core.edit.state') || $canDo->get('core.manage.material') || $canManageEnroll)
			{
				JHtmlSidebar::addEntry(
				Text::_('COM_TJLMS_TITLE_COURSES'), 'index.php?option=com_tjlms&view=courses&filter[type]=&filter[state]=', $vName == 'courses');
			}

			if ($canDo->get('core.create') || $canDo->get('core.delete') || $canDo->get('core.edit'))
			{
				// Add submenus for course fields and course field group.
				JHtmlSidebar::addEntry(
						Text::_('COM_TJLMS_TITLE_COURSES_FIELD_GROUPS'), 'index.php?option=com_fields&view=groups&context=com_tjlms.course', $vName == 'fields.groups');
				JHtmlSidebar::addEntry(
						Text::_('COM_TJLMS_TITLE_COURSES_FIELDS'), 'index.php?option=com_fields&context=com_tjlms.course', $vName == 'fields.fields');
			}

			if ($canDo->get('core.manage.material'))
			{
				$catLessonsUrl = 'index.php?option=com_categories&extension=com_tjlms.lessons';
				JHtmlSidebar::addEntry(Text::_('COM_TJLMS_SUBMENU_LESSON_CATEGORIES'), $catLessonsUrl, $vName == 'categories.lessons');

				$lessonsUrl = 'index.php?option=com_tjlms&view=lessons';
				JHtmlSidebar::addEntry(Text::_('COM_TJLMS_SUBMENU_LESSONS'), $lessonsUrl, $vName == 'lessons');
			}

			if ($canDo->get('view.questioncategories'))
			{
				JHtmlSidebar::addEntry(Text::_('COM_TJLMS_SUBMENU_QUIZ_CATEGORIES'), $catTmtUrl, $vName == 'categories.questions');
			}

			$canManageQB = self::canManageQuestions();

			if ($canManageQB)
			{
				JHtmlSidebar::addEntry(Text::_('COM_TJLMS_SUBMENU_QUIZ_QUESTIONS'), 'index.php?option=com_tmt&view=questions', $vName == 'questions');
			}

			if ($canManageEnroll === 1 || $canManageEnroll === -2)
			{
				JHtmlSidebar::addEntry(Text::_('COM_TJLMS_TITLE_MANAGE_ENROLLMENT'), $manageEnrollmentUrl, $vName == 'manageenrollments');
			}

			if ($canDo->get('core.create') || $canDo->get('core.delete') || $canDo->get('core.edit'))
			{
				// Add submenus for manageenrollment fields and manageenrollment field group.
				JHtmlSidebar::addEntry(
						Text::_('COM_TJLMS_TITLE_MANAGEENROLLMENT_FIELD_GROUPS'), 'index.php?option=com_fields&view=groups&context=com_tjlms.manageenrollment', $vName == 'fields.groups');
				JHtmlSidebar::addEntry(
						Text::_('COM_TJLMS_TITLE_MANAGEENROLLMENT_FIELDS'), 'index.php?option=com_fields&context=com_tjlms.manageenrollment', $vName == 'fields.fields');
			}

			if ($canDo->get('view.certificatetemplate'))
			{
				JHtmlSidebar::addEntry(
					Text::_('COM_TJLMS_TITLE_CERTIFICATE'), 'index.php?option=com_tjcertificate&extension=com_tjlms.course', $vName == "templates");
				JHtmlSidebar::addEntry(
					Text::_('COM_TJLMS_CERTIFICATE_ISSUED'),
					'index.php?option=com_tjcertificate&view=certificates&extension=com_tjlms.course', $vName == "certificates");
			}

			if ($canDo->get('view.coupons'))
			{
				JHtmlSidebar::addEntry(Text::_('COM_TJLMS_TITLE_COUPONS'), 'index.php?option=com_tjlms&view=coupons', $vName == 'coupons');
			}

			if ($canDo->get('view.reminder'))
			{
				// Added to add link for assignment reminders report view
				$reminders = 'index.php?option=com_jlike&view=reminders&extension=com_tjlms';
				JHtmlSidebar::addEntry(Text::_('COM_TJLMS_TITLE_REMINDERS'), $reminders, $vName == 'reminders');
			}

			if ($canDo->get('view.orders'))
			{
				JHtmlSidebar::addEntry(Text::_('COM_TJLMS_TITLE_ORDERS'), 'index.php?option=com_tjlms&view=orders', $vName == 'orders');
			}

			/*
			if ($canDo->get('view.activities'))
			{
				JHtmlSidebar::addEntry(Text::_('COM_TJLMS_TITLE_ACTIVITIES'), 'index.php?option=com_tjlms&view=activities', $vName == 'activities');
			}
			*/
			// Tjlms Report Link
			if ($canDo->get('view.reports'))
			{
				JHtmlSidebar::addEntry(
					Text::_('COM_TJLMS_TITLE_ATTEMPTS'), 'index.php?option=com_tjlms&view=attemptreport', $vName == 'attemptreport');
			/*	PluginHelper::importPlugin('tjlmsreports');
				$dispatcher = JDispatcher::getInstance();
				$getpluginsInfo = $dispatcher->trigger('getpluginInfo');
				$getpluginInfo = $getpluginsInfo[0];
				$report = 'index.php?option=com_tjlms&view=reports&reportToBuild=' . $getpluginInfo;
				JHtmlSidebar::addEntry(Text::_('COM_TJLMS_TITLE_REPORT'), $report, $vName == 'reports');
			*/
			}

			if ($canDo->get('core.create') || $canDo->get('core.delete') || $canDo->get('core.edit'))
			{
				JHtmlSidebar::addEntry(
					Text::_('COM_TJLMS_NOTIFICATION_TEMPLATES'), 'index.php?option=com_tjnotifications&extension=com_tjlms',
					$vName == 'notifications');

				JHtmlSidebar::addEntry(
				Text::_('COM_TJLMS_NOTIFICATIONS_SUBSCRIPTIONS'), '
						index.php?option=com_tjnotifications&view=subscriptions&extension=com_tjlms', $vName == 'subscriptions');
			}

			// TJReport Link
			$isTjreportEnabled = self::isComponentEnabled('tjreports');

			if ($canDo->get('view.reports')
				&& $user->authorise('core.view', 'com_tjreports')
				&& $user->authorise('core.manage', 'com_tjreports')
				&& $isTjreportEnabled)
			{
				$enabledPlugins = PluginHelper::getPlugin('tjreports');

				if (!empty($enabledPlugins))
				{
					$enabledPlugin = $enabledPlugins[0]->name;
					$report = 'index.php?option=com_tjreports&client=com_tjlms&task=reports.defaultReport';
					JHtmlSidebar::addEntry(Text::_('COM_TJLMS_TITLE_REPORT'), $report, $vName == 'reports');
				}
			}

			JHtmlSidebar::addEntry(
				Text::_('COM_TJLMS_TITLE_TOOLS'), 'index.php?option=com_tjlms&view=tools',
				($vName == 'tools' && $option == 'com_tjlms')
				);

			if ($canDo->get('core.admin'))
			{
				JHtmlSidebar::addEntry(
								Text::_('COM_TJLMS_TITLE_FILE_DOWNLAOD_STATUS_SIDE_MENU'),
								'index.php?option=com_tjlms&view=filedownloadstats', $vName == 'filedownloadstats'
								);
			}

			JHtmlSidebar::addEntry(Text::_('COM_TJLMS_TITLE_HELP'), 'index.php?option=com_tjlms&view=help', $vName == 'help');
		}
	}

	/**
	 * Gets a list of the actions that can be performed.
	 *
	 * @return  JObject
	 *
	 * @since    1.6
	 */
	public static function getActions()
	{
		$user   = Factory::getUser();
		$result = new CMSObject;

		$assetName = 'com_tjlms';

		$actions = array(
			'core.admin',
			'core.manage',
			'core.create',
			'core.edit',
			'core.edit.own',
			'core.edit.state',
			'core.delete',
			'view.reports',
			'view.coursecategories',
			'view.courses',
			'view.questioncategories',
			'core.all.questionbank',
			'core.own.questionbank',
			'view.manageenrollment',
			'view.certificatetemplate',
			'view.coupons',
			'view.orders',
			'view.activities',
			'view.coursefields',
			'view.coursefieldsgroups',
			'view.singlecoursereport',
			'view.reminder',
			'core.manage.material',
			'view.own.manageenrollment'
		);

		foreach ($actions as $action)
		{
			$result->set($action, $user->authorise($action, $assetName));
		}

		return $result;
	}

	/**
	 * Function to upload files
	 *
	 * @param   ARRAY  $post   Post array
	 * @param   ARRAY  $files  File array
	 *
	 * @return  INT
	 *
	 * @since  1.0.0
	 */
	public function upload_files_store($post, $files)
	{
		$user = Factory::getUser();
		$db   = Factory::getDBO();

		if (!Folder::exists(JPATH_SITE . '/media/com_tjlms' . '/associatefiles'))
		{
			Folder::create(JPATH_SITE . '/media/com_tjlms' . '/associatefiles', 0777);
		}

		$lessonsFiles  = $files->get('lesson_files', '', 'ARRAY');
		$lessonDetails = $post->get('lesson_files', '', 'ARRAY');

		foreach ($lessonsFiles as $k => $v)
		{
			$filepath    = 'media/com_tjlms' . '/associatefiles/' . $v['file']['name'];
			$uploads_dir = JPATH_SITE . '/' . $filepath;

			if ($v['file']['name'])
			{
				if (!File::exists(JPATH_SITE . $uploads_dir))
				{
					$src = $v['file']['tmp_name'];
					File::upload($src, $uploads_dir);
				}

				$file_data           = new stdClass;
				$file_data->filename = $lessonDetails[$k]['title'];
				$file_data->path     = $filepath;
				$file_data->user_id  = $user->id;

				if (!empty($file_data->filename) && !empty($file_data->path))
				{
					if (!$db->insertObject('#__tjlms_media', $file_data, 'id'))
					{
						echo $this->_db->stderr();

						return false;
					}
					else
					{
						$file_id[] = $file_data->id;
					}
				}
			}
		}

		return $file_id;
	}

	/**
	 * Get all access levels of joomla
	 *
	 * @return  ARRAY
	 *
	 * @since  1.0.0
	 */
	public function getJoomlaAccessLevels()
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select('a.id as value,a.title as text');
		$query->from('`#__viewlevels` AS a');
		$db->setQuery($query);

		return $db->loadObjectList();
	}

	/**
	 * Get all the dates converted to utc
	 *
	 * @param   date  $date  date of lesson
	 *
	 * @return   date in utc format
	 *
	 * @since   1.0
	 */
	public function getDateInUtc($date)
	{
		// Change date in UTC
		$config = Factory::getConfig();
		$offset = $config->get('offset');

		$lessonDate = Factory::getDate(strtotime($date), 'UTC', true);
		$date = $lessonDate->toSql(true);

		return $date;
	}

	/**
	 * Get all the dates converted to utc
	 *
	 * @param   date  $date  date of lesson
	 *
	 * @return   date in utc format
	 *
	 * @since   1.0
	 */
	public function getDateInLocal($date)
	{
		// Get some system objects.
		$config = Factory::getConfig();
		$user   = Factory::getUser();

		$offset = $config->get('offset');

		$mydate = Factory::getDate(strtotime($date), $offset);

		return $mydate;
	}

	/**
	 * Get all text for javascript
	 *
	 * @return   void
	 *
	 * @since   1.0
	 */
	public static function getLanguageConstant()
	{
		Text::script('COM_TJLMS_NO_OF_ATTEMPT_VALIDATION_MSG');
		Text::script('COM_TJLMS_MAX_ATTEMPT_VALIDATION_MSG');
		Text::script('COM_TJLMS_MAX_ATTEMPT_VALIDATION_MSG2');
		Text::script('COM_TJLMS_MAX_ATTEMPT_VALIDATION_MSG3');
		Text::script('COM_TJLMS_EMPTY_TITLE_ISSUE');
		Text::script('COM_TJLMS_COURSE_DURATION_VALIDATION');
		Text::script('COM_TJLMS_LESSON_UPDATED_SUCCESSFULLY');
		Text::script('COM_TJLMS_MODULE_PUBLISHED_SUCCESSFULLY');
		Text::script('COM_TJLMS_MODULE_UNPUBLISHED_SUCCESSFULLY');
		Text::script('COM_TJLMS_REPORTS_CANNOT_SELECT_NONE');
		Text::script('COM_TJLMS_ENTER_NUMERNIC_MARKS');
		Text::script('COM_TJLMS_NO_NEGATIVE_NUMBER');
		Text::script('COM_TJLMS_UPDATED_MARKS_SUCCESSFULLY');
		Text::script('COM_TJLMS_ENTER_MARKS_GRT_TOTALMARKS');
		Text::script('COM_TJLMS_END_DATE_CANTBE_GRT_TODAY');
		Text::script('COM_TJLMS_SURE_PAID_TO_FREE');
		Text::script('COM_TJLMS_VALID_MODULE_TITLE');

		// For date valiation
		Text::script('COM_TJLMS_SELECT_FILL_DATES');
		Text::script('COM_TJLMS_INVALID_DATE_FORMAT');
		Text::script('COM_TJLMS_DUE_DATE_EMPTY');
		Text::script('COM_TJLMS_START_GT_THAN_DUE_DATE');
		Text::script('COM_TJLMS_START_GT_THAN_TODAY');
		Text::script('COM_TJLMS_DATE_VALIDATION_MONTH_INCORRECT');
		Text::script('COM_TJLMS_DATE_VALIDATION_DATE_INCORRECT');
		Text::script('COM_TJLMS_DATE_VALIDATION');
		Text::script('COM_TJLMS_DATE_VALIDATION_DATE_RANGE');
		Text::script('COM_TJLMS_DATE_RANGE_VALIDATION');
		Text::script('COM_TJLMS_DATE_TIME_VALIDATION');
		Text::script('COM_TJLMS_COUPON_DATE_VALIDATION');
		Text::script('COM_TJLMS_DASHBOARD_DATE_RANGE_VALIDATION');
		Text::script('COM_TJLMS_CLOSE_PREVIEW_LESSON');
		Text::script('COM_TJLMS_SURE_DELETE_MODULE');
		Text::script('COM_TJLMS_REPORTS_VALID_DATE');
		Text::script('COM_TJLMS_INVALID_START_DATE');
		Text::script('COM_TJLMS_INVALID_END_DATE');

		Text::script('COM_TJLMS_SELECT_COURSE_TO_ENROLL');
		Text::script('COM_TJLMS_FORM_INVALID_FIELD');
		Text::script('COM_TJLMS_DATE_RANGE_VALIDATION');
		Text::script('COM_TJLMS_CERTIFICATE_ACCESS_MSG');
		Text::script('COM_TJLMS_ATTEMPTREPORT_DATE_RANGE_VALIDATION');
		Text::script('COM_TJLMS_ATTEMPTREPORT_INVALID_DATE_FORMAT');
		Text::script('COM_TJLMS_MANAGEENROLLMENTS_DATE_RANGE_VALIDATION');
		Text::script('COM_TJLMS_MANAGEENROLLMENTS_INVALID_DATE_FORMAT');
		Text::script('COM_TJLMS_MAX_USER_VALIDATION');

		Text::script('COM_TJLMS_TOOLBAR_DATABASE_FIXFIXCOURSEALIAS');
		Text::script('COM_TJLMS_TOOLBAR_DATABASE_FIXFIXLESSONALIAS');
		Text::script('COM_TJLMS_TOOLBAR_DATABASE_FIXFIXCOLUMNINDEXES');
		Text::script('COM_TJLMS_TOOLBAR_DATABASE_FIXFIXOTHERDBCHANGES');
		Text::script('COM_TJLMS_TOOLBAR_DATABASE_FIXMIGRATECOURSETRACK');
		Text::script('COM_TJLMS_TOOLBAR_DATABASE_FIXMIGRATETESTS');
		Text::script('COM_TJLMS_TOOLBAR_DATABASE_FIX_SUCCESS_MSG');

		Text::script('COM_TJLMS_ASSESMENT_SCORE_MSG');
		Text::script('COM_TJLMS_EMPTY_ASSESMENT_TITLE');
		Text::script('COM_TJLMS_TOTAL_MARK_PASSING_MARK_VALIDATION');
		Text::script('COM_TJLMS_EMPTY_MARKS_VALIDATION');

		// Assessment
		Text::script('COM_TJLMS_LESSON_FORM_MSG_MIN_MARKS_HIGHER');
		Text::script('COM_TJLMS_ASSESSMENT_FORM_TITLE');
		Text::script('COM_TJLMS_TOOLBAR_DATABASE_FIXREMOVEORPHANEDLESSONFILES');
		Text::script('COM_TJLMS_TOOLBAR_DATABASE_FIXUPDATECERTIFICATETAGS');
		Text::script('COM_TJLMS_TOOLBAR_DATABASE_ADDREMINDERTEMPLATES');
		Text::script('COM_TJLMS_TOOLBAR_DATABASE_ADDREMINDERTEMPLATES_CREATING');
		Text::script('COM_TJLMS_ASSESSMENT_CANT_SAVE');

		Text::script('COM_TJLMS_ASSESSMENT_TOTAL_MARKS_EQUAL');
		Text::script('COM_TJLMS_ASSESSMENT_PASSING_MARKS_EQUAL');
		Text::script('COM_TJLMS_ASSESSMENT_CANT_SAVE');
		Text::script('COM_TJLMS_ASSESSMENT_MARKS_VALIDATION_MSG');
		Text::script('COM_TJLMS_DATE_RANGE_VALIDATION');
		Text::script('COM_TJLMS_UPLOAD_EXTENSION_ERROR');
		Text::script('COM_TJLMS_UPLOAD_SIZE_ERROR');
		Text::script('COM_TJLMS_MODUELS_MODULE_WITH_LESSONS_DELETE_ERROR');
		Text::script('COM_TJLMS_SURE_DELETE');
		Text::script('COM_TJLMS_LESSON_DELETED_SUCCESS_MESSAGE');
		Text::script('COM_TJLMS_MODULE_DELETED_SUCCESS_MESSAGE');

		// File error
		Text::script('COM_TJLMS_ERROR_FILENOTSAFE_TO_UPLOAD');

		// 1.3.5
		Text::script('JGLOBAL_CONFIRM_DELETE');
		Text::script('COM_TJLMS_ADDITIONAL_DETAILS_HIDE');
		Text::script('COM_TJLMS_ADDITIONAL_DETAILS');
		Text::script('COM_TJLMS_SUCCESS_UPLOAD');
		Text::script('COM_TJLMS_ALLOWED_FILE_EXTENSION_ERROR_MSG');
		Text::script('COM_TJLMS_ALLOWED_FILE_SIZE_ERROR_MSG');
		Text::script('COM_TJLMS_MAX_NUMBER_OF_FILE_UPLOAD_ERROR_MSG');

		// 1.3.8
		Text::script('COM_TJLMS_ASSESSMENT_MARKS_MISMATCH');
		Text::script('COM_TJLMS_MIN_NO_OF_ASSESSMENT_VALIDATION_MSG');

		Text::script('JGLOBAL_VALIDATION_FORM_FAILED');
		Text::script('COM_TJLMS_ASSOCIATE_MESSAGE_SELECT_ITEMS');
		Text::script('COM_TJLMS_REMOVE_ASSOCIATE_FILE_MESSAGE');

		// Tools
		Text::script('COM_TJLMS_INVALID_COURSE');
		Text::script('COM_TJLMS_TOOLS_COMPLETED_SUCCESSFULLY');
		Text::script('COM_TJLMS_TOOLS_PROGRESS_MESSAGE');
		Text::script('COM_TJLMS_CALCULATE_COURSE_PROGRESS_FAILURE');
		Text::script('COM_TJLMS_CALCULATE_COURSE_TRACK_ENROLLED_USER_COUNT');

		// 1.3.41
		Text::script('COM_TJLMS_CHANGE_COURSE_STATUS');
	}

	/**
	 * Check if user can manage this course
	 *
	 * @param   INT  $course_id       Course ID
	 * @param   MIX  $userId          User ID
	 * @param   MIX  $course_creator  Course creator
	 *
	 * @return  mixed
	 *
	 * @since   1.0
	 */
	public static function canManageCourseMaterial($course_id, $userId=null, $course_creator=null)
	{
		if ($userId !== null)
		{
			$user 	= Factory::getUser($userId);
		}
		else
		{
			$user 	= Factory::getUser();
		}

		if ($course_creator === null)
		{
			$course_creator = self::getCourseCreator($course_id);
		}

		$userId = $user->get('id');

		if ($user->authorise('core.create', 'com_tjlms') && $course_creator == $userId)
		{
			return true;
		}
		elseif ($user->authorise('core.manage.material', 'com_tjlms.course.' . $course_id))
		{
			return true;
		}

		return false;
	}

	/**
	 * Check if user can manage this course
	 *
	 * @param   INT  $lessonId  Lesson ID
	 * @param   MIX  $userId    User ID
	 *
	 * @return  mixed
	 *
	 * @since   1.0
	 */
	public static function canManageTrainingMaterial($lessonId, $userId=null)
	{
		if ($userId !== null)
		{
			$user 	= Factory::getUser($userId);
		}
		else
		{
			$user 	= Factory::getUser();
		}

		$canManageMaterial      = $user->authorise('core.manage.material', 'com_tjlms');
		$canManageMaterialOwn   = $user->authorise('core.own.manage.material', 'com_tjlms');
		$creator = self::getLessonCreator($lessonId);

		if (!$lessonId && !$canManageMaterial && !$canManageMaterialOwn)
		{
			return false;
		}
		elseif (!$lessonId && ($canManageMaterial || $canManageMaterialOwn))
		{
			return true;
		}
		elseif ($lessonId && ($canManageMaterial || ($canManageMaterialOwn && $creator == $user->id)))
		{
			return true;
		}

		return false;
	}

	/**
	 * Check if user can manage this course
	 *
	 * @param   INT  $course_id       Course ID
	 * @param   MIX  $userId          User ID
	 * @param   MIX  $course_creator  course_creator
	 *
	 * @return  mixed
	 *
	 * @since   1.0
	 */
	public static function canManageCourseEnrollment($course_id, $userId=null, $course_creator=null)
	{
		if ($userId !== null)
		{
			$user 	= Factory::getUser($userId);
		}
		else
		{
			$user 	= Factory::getUser();
		}

		if (!$user->authorise('view.own.manageenrollment', 'com_tjlms'))
		{
			return false;
		}

		$userId   = $user->get('id');
		$hasUsers = self::getSubusers($userId);

		if ($course_creator === null)
		{
			$course_creator = self::getCourseCreator($course_id);
		}

		// Manager and can manage course enrollment
		if (count($hasUsers) && ($course_creator == $userId || $user->authorise('view.manageenrollment', 'com_tjlms.course.' . $course_id)))
		{
			return -1;
		}
		// Only manager but cannot manage course enrollment
		elseif (count($hasUsers) && ($course_creator != $userId && !$user->authorise('view.manageenrollment', 'com_tjlms.course.' . $course_id)))
		{
			return -2;
		}
		// Only course creator
		elseif ($course_creator == $userId)
		{
			return true;
		}
		// Can manage course enrollment
		elseif ($user->authorise('view.manageenrollment', 'com_tjlms.course.' . $course_id))
		{
			return true;
		}

		return false;
	}

	/**
	 * Check if user can manage this course report
	 *
	 * @param   INT  $course_id       Course ID
	 * @param   MIX  $userId          User ID
	 * @param   MIX  $course_creator  course_creator
	 *
	 * @return  mixed
	 *
	 * @since   1.0
	 */
	public static function canManageCourseReport($course_id, $userId=null, $course_creator=null)
	{
		if ($userId !== null)
		{
			$user 	= Factory::getUser($userId);
		}
		else
		{
			$user 	= Factory::getUser();
		}

		$userId = $user->get('id');

		if ($course_creator === null)
		{
			$course_creator = self::getCourseCreator($course_id);
		}

		if ($course_creator == $userId)
		{
			return true;
		}
		elseif ($user->authorise('view.reports', 'com_tjlms.course.' . $course_id))
		{
			return true;
		}

		return false;
	}

	/**
	 * Check if user can manage this lesson report
	 *
	 * @param   INT  $lesson_id  Lesson ID
	 * @param   MIX  $userId     User ID
	 *
	 * @return  mixed
	 *
	 * @since   1.0
	 */
	public static function canManageLessonReport($lesson_id, $userId=null)
	{
		$course_id = self::getLessonCourse($lesson_id);
		$canManage = self::canManageCourseReport($course_id, $userId);

		return $canManage;
	}

	/**
	 * Check if user can manage this course
	 *
	 * @return  mixed
	 *
	 * @since   1.0
	 */
	public static function canManageEnrollment()
	{
		$user 		= Factory::getUser();
		$hasUsers   = self::getSubusers();

		// Should have access to own enrollments then only can access all
		if ($user->authorise('view.manageenrollment', 'com_tjlms') && $user->authorise('view.own.manageenrollment', 'com_tjlms'))
		{
			return 1;
		}
		elseif ($user->authorise('view.own.manageenrollment', 'com_tjlms') && !count($hasUsers))
		{
			return -1;
		}
		elseif ($user->authorise('view.own.manageenrollment', 'com_tjlms') && count($hasUsers))
		{
			// Only Manager
			return -2;
		}

		return false;
	}

	/**
	 * Get Course Creator
	 *
	 * @param   INT  $lessonId  Lesson id ID
	 *
	 * @return  mixed
	 *
	 * @since   1.0
	 */
	public static function getLessonCreator($lessonId)
	{
		static $lessonCreatorList = array();
		$lessonId = (int) $lessonId;

		if (!$lessonId)
		{
			return false;
		}

		if (!isset($lessonCreatorList[$lessonId]))
		{
			JLoader::register('TjlmsLessonHelper', JPATH_SITE . '/components/com_tjlms/helpers/courses.php');
			$tjlmsLessonHelper = new TjlmsLessonHelper;
			$lessonInfo   = $tjlmsLessonHelper->getLessonColumn($lessonId, array('created_by'));
			$lessonCreatorList[$lessonId] = $lessonInfo->created_by;
		}

		return $lessonCreatorList[$lessonId];
	}

	/**
	 * Get Course Creator
	 *
	 * @param   INT  $course_id  Course ID
	 *
	 * @return  mixed
	 *
	 * @since   1.0
	 */
	public static function getCourseCreator($course_id)
	{
		static $creatorList = array();
		$course_id = (int) $course_id;

		if (!$course_id)
		{
			return false;
		}

		if (!isset($creatorList[$course_id]))
		{
			JLoader::register('TjlmsCoursesHelper', JPATH_SITE . '/components/com_tjlms/helpers/courses.php');
			$tjlmsCoursesHelper = new TjlmsCoursesHelper;
			$courseInfo   = $tjlmsCoursesHelper->getCourseColumn($course_id, array('created_by'));
			$creatorList[$course_id] = $courseInfo->created_by;
		}

		return $creatorList[$course_id];
	}

	/**
	 * Check if User can manage perticular lesson
	 *
	 * @param   INT  $lessonid  Lesson ID
	 *
	 * @param   INT  $userid    User ID
	 *
	 * @return   boolean
	 *
	 * @since   1.0
	 */
	public static function canManageLesson($lessonid, $userid = null)
	{
		$courseid  = self::getLessonCourse($lessonid);
		$canAccess = self::canManageCourseMaterial($courseid, $userid);

		return $canAccess;
	}

	/**
	 * Get course id of a lesson
	 *
	 * @param   INT  $lessonid  Lesson ID
	 *
	 * @return  INT
	 *
	 * @since   1.0
	 */
	public static function getLessonCourse($lessonid)
	{
		static $courseLessonMap = Array();

		if (!isset($courseLessonMap[$lessonid]))
		{
			$db = Factory::getDBO();
			$query = $db->getQuery(true);
			$query->select('course_id');
			$query->from($db->quoteName('#__tjlms_lessons'));
			$query->where($db->quoteName('id') . " = " . (int) $lessonid);
			$db->setQuery($query);
			$courseLessonMap[$lessonid] = $db->loadResult();
		}

		return $courseLessonMap[$lessonid];
	}

	/**
	 * Check if User can change lesson state
	 *
	 * @param   INT  $lessonid  Lesson ID
	 *
	 * @param   INT  $userId    User ID
	 *
	 * @return   boolean
	 *
	 * @since   1.0
	 */
	public static function canChangeLessonState($lessonid, $userId = null)
	{
		if ($userId !== null)
		{
			$user 	= Factory::getUser($userId);
		}
		else
		{
			$user 	= Factory::getUser();
		}

		$userId		= $user->get('id');
		$canCreate 	= $user->authorise('core.create', 'com_tjlms');
		$canEditS	= $user->authorise('core.edit.state', 'com_tjlms.course');

		if (!$lessonid)
		{
			return ($canCreate || $canEditS);
		}

		$courseid   = self::getLessonCourse($lessonid);
		$created_by = self::getCourseCreator($courseid);
		$manageOwn	= $canCreate && $userId == $created_by;
		$canChange 	= $user->authorise('core.edit.state', 'com_tjlms.course.' . $courseid) || $manageOwn;

		return $canChange;
	}

	/**
	 * Check if User can change test state
	 *
	 * @param   INT  $testid  Test ID
	 *
	 * @param   INT  $userId  User ID
	 *
	 * @return   boolean
	 *
	 * @since   1.0
	 */
	public static function canChangeTestState($testid, $userId = null)
	{
		if ($userId !== null)
		{
			$user 	= Factory::getUser($userId);
		}
		else
		{
			$user 	= Factory::getUser();
		}

		$userId		= $user->get('id');
		$canCreate 	= $user->authorise('core.create', 'com_tjlms');
		$canEditS	= $user->authorise('core.edit.state', 'com_tjlms.course');

		if (!$testid)
		{
			return ($canCreate || $canEditS);
		}

		$lessonid   = self::getTestLesson($testid);
		$courseid   = self::getLessonCourse($lessonid);
		$created_by = self::getCourseCreator($courseid);
		$manageOwn	= $canCreate && $userId == $created_by;
		$canChange 	= $user->authorise('core.edit.state', 'com_tjlms.course.' . $courseid) || $manageOwn;

		return $canChange;
	}

	/**
	 * Get lesson id of a test
	 *
	 * @param   INT  $test_id  Test ID
	 *
	 * @return  mixed
	 *
	 * @since   1.0
	 */
	public static function getTestLesson($test_id)
	{
		static $testLessonMap = Array();

		if (!isset($testLessonMap[$test_id]))
		{
			$db = Factory::getDBO();
			$query = $db->getQuery(true);
			$query->select('lesson_id');
			$query->from($db->quoteName('#__tjlms_tmtquiz'));
			$query->where($db->quoteName('test_id') . " = " . (int) $test_id);
			$db->setQuery($query);
			$testLessonMap[$test_id] = $db->loadResult();
		}

		return $testLessonMap[$test_id];
	}

	/**
	 * Check if User can change module state
	 *
	 * @param   INT  $modid   Module ID
	 *
	 * @param   INT  $userId  User ID
	 *
	 * @return   boolean
	 *
	 * @since   1.0
	 */
	public static function canChangeModuleState($modid, $userId = null)
	{
		if ($userId !== null)
		{
			$user 	= Factory::getUser($userId);
		}
		else
		{
			$user 	= Factory::getUser();
		}

		$userId		= $user->get('id');
		$canCreate 	= $user->authorise('core.create', 'com_tjlms');
		$canEditS	= $user->authorise('core.edit.state', 'com_tjlms.course');

		if (!$modid)
		{
			return ($canCreate || $canEditS);
		}

		$courseid   = self::getModuleCourse($modid);
		$created_by = self::getCourseCreator($courseid);
		$manageOwn	= $canCreate && $userId == $created_by;
		$canChange 	= $user->authorise('core.edit.state', 'com_tjlms.course.' . $courseid) || $manageOwn;

		return $canChange;
	}

	/**
	 * Get course id id of a Course
	 *
	 * @param   INT  $modid  Test ID
	 *
	 * @return   boolean
	 *
	 * @since   1.0
	 */
	public static function getModuleCourse($modid)
	{
		static $moduleCourseMap = Array();

		if (!isset($moduleCourseMap[$modid]))
		{
			$db = Factory::getDBO();
			$query = $db->getQuery(true);
			$query->select('course_id');
			$query->from($db->quoteName('#__tjlms_modules'));
			$query->where($db->quoteName('id') . " = " . (int) $modid);
			$db->setQuery($query);
			$moduleCourseMap[$modid] = $db->loadResult();
		}

		return $moduleCourseMap[$modid];
	}

	/**
	 * Check if User can Manage Question
	 *
	 * @param   INT  $question_id     Question ID
	 * @param   MIX  $userId          User ID
	 * @param   MIX  $course_creator  Course creator
	 *
	 * @return   boolean
	 *
	 * @since   1.0
	 */
	public static function canManageQuestion($question_id, $userId=null, $course_creator=null)
	{
		if ($userId !== null)
		{
			$user 	= Factory::getUser($userId);
		}
		else
		{
			$user 	= Factory::getUser();
		}

		$userId	= $user->get('id');
		$allQBs	= $user->authorise('core.all.questionbank', 'com_tjlms');
		$ownQBs	= $user->authorise('core.own.questionbank', 'com_tjlms');

		if (!$question_id)
		{
			return self::canManageQuestions($userId);
		}

		if ($course_creator === null)
		{
			$course_creator = self::getQuestionCreator($question_id);
		}

		$manageOwn	= $ownQBs && $userId == $course_creator;
		$canManage 	= ($allQBs && $ownQBs) || $manageOwn;

		return $canManage;
	}

	/**
	 * Check if User can Manage Questions
	 *
	 * @param   INT  $userId  User ID
	 *
	 * @return  mixed
	 *
	 * @since   1.0
	 */
	public static function canManageQuestions($userId = null)
	{
		if ($userId !== null)
		{
			$user 	= Factory::getUser($userId);
		}
		else
		{
			$user 	= Factory::getUser();
		}

		$userId	= $user->get('id');
		$allQBs	= $user->authorise('core.all.questionbank', 'com_tjlms');
		$ownQBs	= $user->authorise('core.own.questionbank', 'com_tjlms');

		if ($allQBs && $ownQBs)
		{
			return 1;
		}
		elseif ($ownQBs)
		{
			return -1;
		}

		return false;
	}

	/**
	 * Check if User can Edit all assessments
	 *
	 * @param   INT  $courseId  courseId ID
	 * @param   INT  $userId    User ID
	 *
	 * @return  mixed
	 *
	 * @since   1.3
	 */
	public static function canDoAssessment($courseId, $userId = null)
	{
		if ($userId !== null)
		{
			$user 	= Factory::getUser($userId);
		}
		else
		{
			$user 	= Factory::getUser();
		}

		$userId	= $user->get('id');

		return $user->authorise('core.assessment', 'com_tjlms.course.' . (int) $courseId);
	}

	/**
	 * Check if User can Edit all assessments
	 *
	 * @param   INT  $courseId  courseId ID
	 * @param   INT  $userId    User ID
	 *
	 * @return  mixed
	 *
	 * @since   1.3
	 */
	public static function canEditOwnAssessment($courseId, $userId = null)
	{
		if ($userId !== null)
		{
			$user 	= Factory::getUser($userId);
		}
		else
		{
			$user 	= Factory::getUser();
		}

		$userId	= $user->get('id');

		return $user->authorise('core.assessment.editown', 'com_tjlms.course.' . (int) $courseId);
	}

	/**
	 * Check if User can Edit all assessments
	 *
	 * @param   INT  $courseId  courseId ID
	 * @param   INT  $userId    User ID
	 *
	 * @return  mixed
	 *
	 * @since   1.3
	 */
	public static function canEditAllAssessment($courseId, $userId = null)
	{
		if ($userId !== null)
		{
			$user 	= Factory::getUser($userId);
		}
		else
		{
			$user 	= Factory::getUser();
		}

		$userId	= $user->get('id');

		return $user->authorise('core.assessment.editall', 'com_tjlms.course.' . (int) $courseId);
	}

	/**
	 * Get creator id of a Question
	 *
	 * @param   INT  $qid  question id
	 *
	 * @return  mixed
	 *
	 * @since   1.0
	 */
	public static function getQuestionCreator($qid)
	{
		static $question_creator = Array();

		if (!isset($question_creator[$qid]))
		{
			$db = Factory::getDBO();
			$query = $db->getQuery(true);
			$query->select('created_by');
			$query->from($db->quoteName('#__tmt_questions'));
			$query->where($db->quoteName('id') . " = " . (int) $qid);
			$db->setQuery($query);
			$question_creator[$qid] = $db->loadResult();
		}

		return $question_creator[$qid];
	}

	/**
	 * Get creator id of a Question
	 *
	 * @param   INT     $courseId  course id
	 * @param   INT     $userId    user id
	 * @param   STRING  $for       For which to test
	 *
	 * @return   boolean
	 *
	 * @since   1.0
	 */
	public static function canSelfEnrollCourse($courseId, $userId=null, $for = 'enroll')
	{
		if ($userId !== null)
		{
			$user 	= Factory::getUser($userId);
		}
		else
		{
			$user 	= Factory::getUser();
		}

		$userId = $user->get('id');

		$can_enroll = $user->authorise('core.enroll', 'com_tjlms.course.' . $courseId);

		if (!$can_enroll)
		{
			return false;
		}

		// Is paid course?
		if (!class_exists('comtjlmsHelper'))
		{
			JLoader::register('TjlmsCoursesHelper', JPATH_SITE . '/components/com_tjlms/helpers/courses.php');
		}

		if (!class_exists('comtjlmsHelper'))
		{
			JLoader::register('comtjlmsHelper', JPATH_ROOT . '/components/com_tjlms/helpers/main.php');
		}

		$tjlmsCoursesHelper = new TjlmsCoursesHelper;
		$courseInfo   		= $tjlmsCoursesHelper->getCourseColumn($courseId, 'type');

		// Can assign if paid course but cannot enroll
		if (!$courseInfo)
		{
			return false;
		}
		elseif ($courseInfo->type)
		{
			$comtjlmsHelper = new comtjlmsHelper;
			$isEnrolled = $comtjlmsHelper->getEnrollmentDetails($courseId, $userId);

			// Should be enrolled for paid
			if ($isEnrolled && $for == 'assign')
			{
				return true;
			}

			// In case of paid course allow self enrollment for logged-in user.
			if (!$isEnrolled)
			{
				return true;
			}

			return false;
		}

		return true;
	}

	/**
	 * Check if hierarchy integration is enabled
	 *
	 * @return   boolean
	 *
	 * @since   1.0
	 */
	public static function isHierarchyEnabled()
	{
		static $isEnabled;

		if (!isset($isEnabled))
		{
			$isEnabled = self::isComponentEnabled('hierarchy');
		}

		return $isEnabled;
	}

	/**
	 * Method to get sub users
	 *
	 * @param   INT  $userId  Userid whose managers to get
	 *
	 * @return  mixed  An array of data items on success, false on failure.
	 *
	 * @since   1.6.1
	 */
	public static function getSubusers($userId = null)
	{
		static $subusers = array();

		if ($userId === null)
		{
			$user 	= Factory::getUser();
			$userId	= $user->get('id');
		}

		if (!isset($subusers[$userId]))
		{
			$subusers[$userId] = array();

			if (self::isHierarchyEnabled())
			{
				JLoader::import('administrator.components.com_hierarchy.models.hierarchy', JPATH_SITE);
				$hierarchyModel = BaseDatabaseModel::getInstance('Hierarchy', 'HierarchyModel');
				$subuser = $hierarchyModel->getSubUsers($userId, true);

				if (is_array($subuser))
				{
					$subusers[$userId] = $subuser;
				}
			}
		}

		return $subusers[$userId];
	}

	/**
	 * Check if heirarchy integration is enabled
	 *
	 * @param   STRING  $component  Component Name
	 *
	 * @return   boolean
	 *
	 * @since   1.0
	 */
	private static function isComponentEnabled($component)
	{
		jimport('joomla.filesystem.file');

		$isEnabled = false;

		if (File::exists(JPATH_ADMINISTRATOR . '/components/com_' . $component . '/' . $component . '.php'))
		{
			if (ComponentHelper::isEnabled('com_' . $component, true))
			{
				$isEnabled = true;
			}
		}

		return $isEnabled;
	}

	/**
	 * Method to get sub users
	 *
	 * @param   Boolean  $all        Option to see all reports
	 * @param   INT      &$selected  First selected option
	 *
	 * @return  mixed  An array of options
	 *
	 * @since   1.6.1
	 */
	public static function getReportFilterOptions($all = true, &$selected = null)
	{
		$options 	= array();

		$subUsers 	= self::getSubusers();

		if (count($subUsers))
		{
			$selected = -1;
			array_unshift($options, JHTML::_('select.option', $selected, Text::_('COM_TJLMS_REPORT_MY_TEAM')));
		}

		$canDo = self::getActions();

		if ($canDo->get('core.create'))
		{
			$selected = 1;
			array_unshift($options, JHTML::_('select.option', $selected, Text::_('COM_TJLMS_REPORT_CREATED_BY_ME')));
		}

		if ($all)
		{
			$selected = 0;
			array_unshift($options, JHTML::_('select.option', $selected, Text::_('COM_TJLMS_REPORT_ALL')));
		}

		return $options;
	}

	/**
	 * Method to get sub users
	 *
	 * @param   MIX      $model        Model class object
	 * @param   INT      &$selected    First selected option
	 * @param   INT      &$created_by  Course creator id
	 * @param   Boolean  &$myTeam      Sets whether user is a manager or not
	 *
	 * @return  mixed  An array of options
	 *
	 * @since   1.6.1
	 */
	public static function getReportFilterValues($model, &$selected, &$created_by, &$myTeam)
	{
		$reportId       = $model->getState('reportId', 0);
		$user           = Factory::getUser();
		$userId         = $user->id;
		$viewAll        = $user->authorise('core.viewall', 'com_tjreports.tjreport.' . $reportId);
		$reportOptions  = self::getReportFilterOptions($viewAll, $selectedReport);

		$filters = $model->getState('filters');

		if (empty($filters['report_filter']))
		{
			$filters['report_filter'] = $selectedReport;
			$model->setState('filters', $filters);
		}

		$created_by			= (int) $filters['report_filter'] === 1 ? $userId : 0;
		$myTeam				= (int) $filters['report_filter'] === -1 ? true : false;

		return $reportOptions;
	}

	/**
	 * Configure the Linkbar.
	 *
	 * @param   STRING  $vName  View name
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	public static function addJLikeSubmenu($vName = '')
	{
		if (JVERSION < '4.0.0')
		{
			// To add the css to show the sidemenu wherever the tjlms addSubmenu gets overriden.
			// Moved from plugin to submenu function to show the sidebar css all the  time inside tjlms component.
			$document = Factory::getDocument();
			$document->addStyleSheet(Uri::root(true) . '/media/com_tjlms/css/tjlms_backend.css');
			$document->addStyleSheet(Uri::root(true) . '/media/com_tjlms/font-awesome/css/font-awesome.min.css');
			$js = "jQuery(document).ready(function(){jQuery('.container-main').addClass('tjlms-wrapper')});";
			$document->addScriptDeclaration($js);

			$user = Factory::getUser();
			$canDo = self::getActions();

			// Get component params
			$tjlmsparams = ComponentHelper::getParams('com_tjlms');
			$catTmtUrl = 'index.php?option=com_categories&view=categories&extension=com_tmt.questions';
			$manageEnrollmentUrl = 'index.php?option=com_tjlms&view=manageenrollments';
			$singlecoursereporturl = 'index.php?option=com_tjlms&view=singlecoursereport';

			$option = Factory::getApplication()->input->get('option', '', 'STRING');

			JHtmlSidebar::addEntry(
				Text::_('COM_TJLMS_TITLE_DASHBOARD'), 'index.php?option=com_tjlms&view=dashboard',
				$vName == 'dashboard' && $option == 'com_tjlms'
				);

			JHtmlSidebar::addEntry(
				Text::_('COM_TJLMS_ADD_TJDASHBOARD_MENUE'), 'index.php?option=com_tjdashboard&view=dashboard&layout=default&client=com_tjlms&dashboard_id=1',
				$vName == 'dashboard' && $option == 'com_tjdashboard'
			);

			if ($canDo->get('view.coursecategories'))
			{
				$catTjlmsUrl = 'index.php?option=com_categories&extension=com_tjlms';
				JHtmlSidebar::addEntry(Text::_('COM_TJLMS_SUBMENU_CATEGORIES'), $catTjlmsUrl, $vName == 'categories');
			}

			$canManageEnroll = self::canManageEnrollment();

			if ($canDo->get('core.create') || $canDo->get('core.delete') || $canDo->get('core.edit')
				|| $canDo->get('core.edit.state') || $canDo->get('core.manage.material') || $canManageEnroll)
			{
				JHtmlSidebar::addEntry(
				Text::_('COM_TJLMS_TITLE_COURSES'), 'index.php?option=com_tjlms&view=courses&filter[type]=&filter[state]=', $vName == 'courses');
			}

			if ($canDo->get('core.create') || $canDo->get('core.delete') || $canDo->get('core.edit'))
			{
				// Add submenus for course fields and course field group.
				JHtmlSidebar::addEntry(
						Text::_('COM_TJLMS_TITLE_COURSES_FIELD_GROUPS'), 'index.php?option=com_fields&view=groups&context=com_tjlms.course', $vName == 'fields.groups');
				JHtmlSidebar::addEntry(
						Text::_('COM_TJLMS_TITLE_COURSES_FIELDS'), 'index.php?option=com_fields&context=com_tjlms.course', $vName == 'fields.fields');
			}

			if ($canDo->get('view.questioncategories'))
			{
				JHtmlSidebar::addEntry(Text::_('COM_TJLMS_SUBMENU_QUIZ_CATEGORIES'), $catTmtUrl, $vName == 'categories.questions');
			}

			$canManageQB = self::canManageQuestions();

			if ($canManageQB)
			{
				JHtmlSidebar::addEntry(Text::_('COM_TJLMS_SUBMENU_QUIZ_QUESTIONS'), 'index.php?option=com_tmt&view=questions', $vName == 'questions');
			}

			if ($canManageEnroll === 1 || $canManageEnroll === -2)
			{
				JHtmlSidebar::addEntry(Text::_('COM_TJLMS_TITLE_MANAGE_ENROLLMENT'), $manageEnrollmentUrl, $vName == 'manageenrollments');
			}

			if ($canDo->get('view.certificatetemplate'))
			{
				JHtmlSidebar::addEntry(
					Text::_('COM_TJLMS_TITLE_CERTIFICATE'),
					'index.php?option=com_tjcertificate&extension=com_tjlms.course', $vName == "templates");
				JHtmlSidebar::addEntry(
					Text::_('COM_TJLMS_CERTIFICATE_ISSUED'),
					'index.php?option=com_tjcertificate&view=certificates&extension=com_tjlms.course', $vName == "certificates");
			}

			if ($canDo->get('view.coupons'))
			{
				JHtmlSidebar::addEntry(Text::_('COM_TJLMS_TITLE_COUPONS'), 'index.php?option=com_tjlms&view=coupons', $vName == 'coupons');
			}

			if ($canDo->get('view.reminder'))
			{
				// Added to add link for assignment reminders report view
				$reminders = 'index.php?option=com_jlike&view=reminders&extension=com_tjlms';
				JHtmlSidebar::addEntry(Text::_('COM_TJLMS_TITLE_REMINDERS'), $reminders, $vName == 'reminders');
			}

			if ($canDo->get('view.orders'))
			{
				JHtmlSidebar::addEntry(Text::_('COM_TJLMS_TITLE_ORDERS'), 'index.php?option=com_tjlms&view=orders', $vName == 'orders');
			}

			/*
			if ($canDo->get('view.activities'))
			{
				JHtmlSidebar::addEntry(Text::_('COM_TJLMS_TITLE_ACTIVITIES'), 'index.php?option=com_tjlms&view=activities', $vName == 'activities');
			}
			*/
			// Tjlms Report Link
			if ($canDo->get('view.reports'))
			{
				JHtmlSidebar::addEntry(
					Text::_('COM_TJLMS_TITLE_ATTEMPTS'), 'index.php?option=com_tjlms&view=attemptreport', $vName == 'attemptreport');
			/*	PluginHelper::importPlugin('tjlmsreports');
				$dispatcher = JDispatcher::getInstance();
				$getpluginsInfo = $dispatcher->trigger('getpluginInfo');
				$getpluginInfo = $getpluginsInfo[0];
				$report = 'index.php?option=com_tjlms&view=reports&reportToBuild=' . $getpluginInfo;
				JHtmlSidebar::addEntry(Text::_('COM_TJLMS_TITLE_REPORT'), $report, $vName == 'reports');
			*/
			}

			// Tjnotifications Link.
			if ($canDo->get('core.create') || $canDo->get('core.delete') || $canDo->get('core.edit'))
			{
				JHtmlSidebar::addEntry(
					Text::_('COM_TJLMS_NOTIFICATION_TEMPLATES'), 'index.php?option=com_tjnotifications&extension=com_tjlms',
					$vName == 'notifications');

				JHtmlSidebar::addEntry(
				Text::_('COM_TJLMS_NOTIFICATIONS_SUBSCRIPTIONS'), '
						index.php?option=com_tjnotifications&view=subscriptions&extension=com_tjlms', $vName == 'subscriptions');
			}

			// TJReport Link
			$isTjreportEnabled = self::isComponentEnabled('tjreports');

			if ($canDo->get('view.reports')
				&& $user->authorise('core.view', 'com_tjreports')
				&& $user->authorise('core.manage', 'com_tjreports')
				&& $isTjreportEnabled)
			{
				$enabledPlugins = PluginHelper::getPlugin('tjreports');

				if (!empty($enabledPlugins))
				{
					$enabledPlugin = $enabledPlugins[0]->name;
					$report = 'index.php?option=com_tjreports&client=com_tjlms&task=reports.defaultReport';
					JHtmlSidebar::addEntry(Text::_('COM_TJLMS_TITLE_REPORT'), $report, $vName == 'reports');
				}
			}

			JHtmlSidebar::addEntry(
				Text::_('COM_TJLMS_TITLE_TOOLS'), 'index.php?option=com_tjlms&view=tools',
				($vName == 'tools' && $option == 'com_tjlms')
				);

			JHtmlSidebar::addEntry(Text::_('COM_TJLMS_TITLE_HELP'), 'index.php?option=com_tjlms&view=help', $vName == 'help');
		}
	}
}
