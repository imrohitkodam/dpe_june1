<?php
/**
 * @package     Shika
 * @subpackage  mod_lms_filter
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access.
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Helper\ModuleHelper;

$app = Factory::getApplication();
require_once __DIR__ . '/helper.php';
jimport('joomla.filesystem.file');

if (File::exists(JPATH_SITE . '/components/com_tjlms/tjlms.php'))
{
	// Load js assets
	$tjStrapperPath = JPATH_SITE . '/media/techjoomla_strapper/tjstrapper.php';

	if (File::exists($tjStrapperPath))
	{
		require_once $tjStrapperPath;
		TjStrapper::loadTjAssets('com_tjlms');
	}

	$mod_filter = new stdClass;

	if ($params->get('category', 1) == 1)
	{
		if (!class_exists('comtjlmsHelper'))
		{
			$path = JPATH_SITE . DS . 'components' . DS . 'com_tjlms' . DS . 'helpers' . DS . 'main.php';
			JLoader::register('comtjlmsHelper', $path);
		}

		$comtjlmsHelper              = new comtjlmsHelper;
		$coursesUrl                  = 'index.php?option=com_tjlms&view=courses';
		$CoursesItemId               = $comtjlmsHelper->getitemid($coursesUrl);

		$cats                        = ModLmsfilterHelper::getLMScategorys();
		$mod_filter->category_filter = $app->getUserStateFromRequest('com_tjlms' . '.filter.category_filter', 'category_filter', 0, 'INTEGER');
	}
	// LOAD LANGUAGE FILES
	$doc  = Factory::getDocument();
	$lang = Factory::getLanguage();
	$lang->load('mod_lms_categorylist', JPATH_SITE);

	if ($params->get('course_type', 1) == 1)
	{
		$mod_filter->course_type = $app->getUserStateFromRequest('com_tjlms' . '.filter.course_type', 'course_type', -1, 'INTEGER');
	}

	if ($params->get('search', 1) == 1)
	{
		$mod_filter->search = $app->getUserStateFromRequest('com_tjlms' . '.filter.filter_search', 'filter_search', '', 'STRING');
	}

	if ($params->get('creator', 0) == 1)
	{
		$courseCreators = ModLmsfilterHelper::getCourseCreators();
		$mod_filter->creator_filter = $app->getUserStateFromRequest('com_tjlms' . '.filter.creator_filter', 'creator_filter', 0, 'INTEGER');
	}

	if ($params->get('course_status', 0) == 1)
	{
		$mod_filter->course_status = $app->getUserStateFromRequest('com_tjlms' . '.filter.course_status', 'course_status', '', 'STRING');
	}

	if ($params->get('filter_tag', 0) == 1)
	{
		$tags = ModLmsfilterHelper::getTags();
		$mod_filter->course_tag_filter = $app->getUserStateFromRequest('com_tjlms' . '.filter.filter_tag', 'filter_tag', '', 'STRING');
	}

	require ModuleHelper::getLayoutPath('mod_lms_filter');
}
