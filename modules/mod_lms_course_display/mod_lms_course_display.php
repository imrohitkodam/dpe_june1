<?php
/**
 * @package     Shika
 * @subpackage  mod_lms_course_display
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Registry\Registry;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

JLoader::register('TjlmsModelCourses', JPATH_SITE . '/components/com_tjlms/models/courses.php');

if (File::exists(JPATH_SITE . '/components/com_tjlms/tjlms.php'))
{
	// Load js assets
	jimport('joomla.filesystem.file');
	$tjStrapperPath = JPATH_SITE . '/media/techjoomla_strapper/tjstrapper.php';

	if (File::exists($tjStrapperPath))
	{
		require_once $tjStrapperPath;
		TjStrapper::loadTjAssets('com_tjlms');
	}

	$path = JPATH_SITE . '/components/com_tjlms/helpers/courses.php';

	if (!class_exists('TjlmsCoursesHelper'))
	{
		JLoader::register('TjlmsCoursesHelper', $path);
		JLoader::load('TjlmsCoursesHelper');
	}

	$tjlmshelperObjPath = JPATH_SITE . '/components/com_tjlms/helpers/main.php';

	if (!class_exists('ComtjlmsHelper'))
	{
		JLoader::register('ComtjlmsHelper', $tjlmshelperObjPath);
		JLoader::load('ComtjlmsHelper');
	}

	$model              = BaseDatabaseModel::getInstance('Courses', 'TjlmsModel', array('ignore_request' => true));
	$comtjlmshelper     = new ComtjlmsHelper;
	$tjlmsCoursesHelper = new TjlmsCoursesHelper;
	$tjlmsparams        = ComponentHelper::getParams('com_tjlms');

	// GETTING MODULE PARAMS
	$prodLimit               = $params->get('limit', 5);
	$displayLimit            = $params->get('displayLimit', 2);
	$pinWidth                = $xwidth = $params->get('pin_width', 170);
	$pinPadding              = $params->get('pin_padding', 3);
	$showTitle               = $params->get('show_course_title', 1);
	$titleHeight             = $params->get('title_height', '40');
	$iheight                 = $xheight = $tjlmsparams->get('small_height', '128', 'INT');
	$fixed_H_pin_desc_height = 3 * $tjlmsparams->get('fixed_H_pin_desc_height', '60', 'INT');
	$short_desc_char         = $tjlmsparams->get('pin_short_desc_char', 50, 'INT');

	if ($showTitle == 1)
	{
		$xheight = $xheight + $titleHeight;
	}

	$input = Factory::getApplication()->input;
	$tagId = $input->get('tagid');

	if (empty($tagId))
	{
		$tagId = $params->get('tags');
	}

	if (!empty($tagId))
	{
		// Filer by tag
		$model->setState('filter.tag', $tagId);
	}

	$mode          = 'horizontal';
	$auto          = 0;
	$direction     = 'left';
	$iwidth        = 'auto';
	$delaytime     = 5000;
	$animationtime = '1000';
	$module_mode   = $params->get('module_mode', 'lms_featured');

	$order                     = 'a.created';
	$direction                 = 'DESC';
	$model->filter_user        = Factory::getUser()->id;
	$model->course_images_size = $params->get('course_images_size', 'S_');
	$model->setState('list.ordering', $order);
	$model->setState('list.direction', $direction);
	$model->setState('list.limit', $prodLimit);
	$menuParams                = new Registry;
	$model->setState('params', $menuParams);
	$course_category           = $params->get('course_category', '');
	$showEnrolledCourses       = $params->get('include_enrolled_courses', '');

	if (!empty($course_category))
	{
		$model->setState('com_tjlms.filter.category_filter', $course_category);
	}

	switch ($module_mode)
	{
		case 'lms_featured':
			$model->setState('com_tjlms.filter.featured', 1);
			$model->courses_to_show = $showEnrolledCourses ? '' : 'notEnrolled';
			$courses = $model->getItems();
		break;

		case 'lms_enrolled':
			$model->setState('com_tjlms.filter.course_status', 'incompletedcourses');
			$model->courses_to_show = 'enrolled';
			$courses                = $model->getItems();
		break;

		case 'lms_recentlyAdded':

		// Deprecated
		case 'lms_notEnrolled':
			$model->courses_to_show = 'notEnrolled';
			$courses                = $model->getItems();
		break;

		case 'lms_upcoming':
			$model->courses_to_show = 'upcomingCourses';
			$courses                = $model->getItems();
		break;

		case 'lms_completed':
			$model->courses_to_show = 'completed';
			$courses                = $model->getItems();
		break;

		case 'lms_recommended':
			$model->courses_to_show = 'recommended';
			$courses                = $model->getItems();
		break;

		case 'lms_suggestedCategory':
			require_once JPATH_ROOT . '/components/com_tjlms/libraries/suggestcourses.php';
			$suggestCourses   = new TjSuggestCourses;
			$options['limit'] = $prodLimit;
			$questions        = array();
			$courses          = $suggestCourses->suggestCourses($questions, $options);
		break;

		default:
			$courses = $model->getItems();
		break;
	}

	require ModuleHelper::getLayoutPath('mod_lms_course_display', $params->get('layout', 'default'));
}
