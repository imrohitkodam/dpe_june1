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
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 * Helper for mod_lms_filter
 *
 * @since  1.0
 */

class ModLmsfilterHelper
{
	/**
	 * Fetch list of course creator
	 *
	 * @return  mixed  list of course creator
	 *
	 * @since   1.0
	 */
	public static function getLMScategorys()
	{
		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_tjlms/models/courses.php');
		$coursesModel = BaseDatabaseModel::getInstance('Courses', 'TjlmsModel');

		// Category menu
		$app           = Factory::getApplication();
		$menu_category = $app->getParams()->get('show_courses_from_cat', -1);
		$cats          = $coursesModel->getTjlmsCats($menu_category);

		return $cats;
	}

	/**
	 * Fetch list of course creators
	 *
	 * @return  mixed  list of category
	 *
	 * @since   1.0
	 */
	public static function getCourseCreators()
	{
		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_tjlms/models/courses.php');
		$coursesModel = BaseDatabaseModel::getInstance('Courses', 'TjlmsModel');

		return $coursesModel->getCourseCreators();
	}

	/**
	 * Fetch list of tags
	 *
	 * @return  array
	 *
	 * @since   _DEPLOY_VERSION_
	 */
	public static function getTags()
	{
		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_tjlms/models/courses.php');
		$coursesModel = BaseDatabaseModel::getInstance('Courses', 'TjlmsModel');

		return $coursesModel->getTags();
	}
}
