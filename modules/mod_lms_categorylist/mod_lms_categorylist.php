<?php
/**
 * @package     LMS_Shika
 * @subpackage  mod_lms_categorylist
 * @copyright   Copyright (C) 2009-2014 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license     GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link        http://www.techjoomla.com
 */
// No direct access.
defined('_JEXEC') or die;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Helper\ModuleHelper;

jimport('joomla.filesystem.file');
jimport('joomla.application.component.model');

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

	if (!class_exists('comtjlmsHelper'))
	{
		$path = JPATH_SITE . '/components/com_tjlms/helpers/main.php';
		JLoader::register('comtjlmsHelper', $path);
	}

	$comtjlmsHelper = new comtjlmsHelper;

	// Get menuparams
	$app = Factory::getApplication('site');
	$menuParams = $app->getParams();
	$courses_to_show = $menuParams->get('courses_to_show');
	$show_courses_from_cat = $menuParams->get('show_courses_from_cat', 0);
	$show_subcat_courses = $app->getParams()->get('show_subcat_courses');

	BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_tjlms/models/');
	$model = BaseDatabaseModel::getInstance('Categories', 'TjlmsModel');
	$model->menu_category = $show_courses_from_cat;
	$model->active_category = $activeCat = $app->getUserStateFromRequest('com_tjlms' . '.filter.category_filter', 'category_filter', 0, 'INTEGER');

	if (!$model->active_category)
	{
		$model->active_category = $activeCat = $model->menu_category;
	}

	$categories = $model->getItems();

	require ModuleHelper::getLayoutPath('mod_lms_categorylist');
}
