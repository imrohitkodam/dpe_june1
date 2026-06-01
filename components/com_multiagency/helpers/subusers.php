<?php

/**
 * @version    CVS: 1.0.0
 * @package    Com_Multiagency
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2017 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

/**
 * Class SubusersFrontendHelper
 *
 * @since  1.6
 */
class MultiagencyFrontendHelper
{
	/**
	 * Get an instance of the named model
	 *
	 * @param   string  $name  Model name
	 *
	 * @return null|object
	 */
	public static function getModel($name)
	{
		$model = null;

		// If the file exists, let's
		if (file_exists(JPATH_SITE . '/components/com_multiagency/models/' . strtolower($name) . '.php'))
		{
			require_once JPATH_SITE . '/components/com_multiagency/models/' . strtolower($name) . '.php';
			$model = BaseDatabaseModel::getInstance($name, 'MultiagencyModel');
		}

		return $model;
	}

	/**
	 * Add menu
	 *
	 * @param   string  $vName  Model name
	 *
	 * @return null|object
	 */

	public static function addSubmenu($vName)
	{
		JHtmlSidebar::addEntry(
			'test',
			'index.php?option=com_multiagency&view=users',
			$vName == 'users'
		);
	}

	/**
	 * Get language constant
	 *
	 * @return String
	 */

	public static function getLanguageConstant()
	{
		// JS file upload
		Text::script('COM_MULTIAGENCY_EMAIL_ALREADY_EXISTS');
		Text::script('COM_MULTIAGENCY_PASSWORD_DOES_NOT_MATCH');
		Text::script('COM_MULTIAGENCY_ERR_MSG_JS_FILE_TYPES');
		Text::script('COM_MULTIAGENCY_ERR_MSG_JS_FILE_SIZE');
		Text::script('COM_MULTIAGENCY_USER_NAME_ALREADY_EXISTS');

		// Organization form
		Text::script('COM_MULTIAGENCY_ORGANIZATION_ID_ALREADY_EXISTS');
		Text::script('COM_MULTIAGENCY_ORGANIZATION_EMAIL_ALREADY_EXISTS');
		Text::script('COM_SUBUSER_MSG_ERR');

		// CSV Import
		Text::script("COM_USER_MANAGEENROLLMENTS_IMPORT_TOTAL_ROWS_CNT_MSG");
		Text::script("COM_USER_MANAGEENROLLMENTS_IMPORT_NEW_USERS_MSG");
		Text::script("COM_USER_MANAGEENROLLMENTS_IMPORT_ALREADY_USER");
		Text::script("COM_USER_TITLE_MANAGEENROLLMENTS_IMPORT_NEWLY_ASSIGNED");
		Text::script("COM_USER_TITLE_MANAGEENROLLMENTS_IMPORT_ALREADY_ASSIGNED");
		Text::script("COM_USER_CSV_IMPORT_COLUMN_MISSING");
		Text::script("COM_USER_MANAGEENROLLMENTS_MANDATORY_FIELDS");
		Text::script("COM_USER_MANAGEENROLLMENTS_BAD_USERDATA");
		Text::script("COM_USER_MANAGEENROLLMENTS_IMPORT_ALREADY_USER_MSG");
		Text::script("COM_MULTIAGENCY_CSV_USER_IMPORTED");
		Text::script("COM_MULTIAGENCY_IMPORT_STAFF_CONFIRM");
	}
}
