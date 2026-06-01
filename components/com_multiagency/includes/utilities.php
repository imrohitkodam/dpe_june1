<?php
/**
 * @package     MULTIAGENCY
 * @subpackage  com_multiagency
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

/**
 * Multiagency utility class for common methods
 *
 * @since  __DEPLOY_VERSION__
 */
class MultiagencyUtilities
{
	/**
	 * Hold the class instance.
	 *
	 * @var    Object
	 * @since  __DEPLOY_VERSION__
	 */
	private static $instance = null;

	/**
	 * Returns the global Multiagency object
	 *
	 * @return  MultiagencyUtilities The object
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public static function getInstance()
	{
		if (self::$instance == null)
		{
			self::$instance = new MultiagencyUtilities;
		}

		return self::$instance;
	}

	/**
	 * Get item id of url
	 *
	 * @param   string  $link  link
	 *
	 * @return  int  Itemid of the given link
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function getItemId($link)
	{
		$itemid = 0;
		$app    = Factory::getApplication();
		$menu   = $app->getMenu();

		if ($app->isClient('site'))
		{
			$items = $menu->getItems('link', $link);

			if (isset($items[0]))
			{
				$itemid = $items[0]->id;
			}
		}

		if (!$itemid)
		{
			try
			{
				$db = Factory::getDBO();
				$query = $db->getQuery(true);
				$query->select($db->quoteName('id'));
				$query->from($db->quoteName('#__menu'));
				$query->where($db->quoteName('link') . ' LIKE ' . $db->Quote($link));
				$query->where($db->quoteName('published') . '=' . $db->Quote(1));
				$query->where($db->quoteName('type') . '=' . $db->Quote('component'));
				$db->setQuery($query);
				$itemid = $db->loadResult();
			}
			catch (Exception $e)
			{
				return false;
			}
		}

		if (!$itemid)
		{
			$input  = $app->input;
			$itemid = $input->getInt('Itemid', 0);
		}

		return $itemid;
	}

	/**
	 * Get all language constant text for javascript
	 *
	 * @return   void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function getLanguageConstant()
	{
		// JS file upload
		Text::script('COM_MULTIAGENCY_LICENCES_TOTAL_SEATS_ERROR');
		Text::script('COM_MULTIAGENCY_LICENCES_TOTAL_SEATS_NEGATIVE_ERROR');
		Text::script('COM_MULTIAGENCY_LICENCES_START_DATE_ERROR');
		Text::script('COM_MULTIAGENCY_LICENCES_END_DATE_ERROR');
		Text::script('COM_MULTIAGENCY_LICENCES_END_START_DATE_ERROR');
		Text::script('COM_MULTIAGENCY_LICENCES_START_END_DATE_ERROR');
		Text::script('COM_MULTIAGENCY_COURSE_SELECT_ERROR');
		Text::script('COM_MULTIAGENCY_ALREADY_PRESENT_EDIT_ERROR');

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
		Text::script("COM_USER_TITLE_MANAGEENROLLMENTS_IMPORT_UPDATED");
		Text::script("COM_USER_CSV_IMPORT_COLUMN_MISSING");
		Text::script("COM_USER_MANAGEENROLLMENTS_MANDATORY_FIELDS");
		Text::script("COM_USER_MANAGEENROLLMENTS_BAD_USERDATA");
		Text::script("COM_USER_MANAGEENROLLMENTS_IMPORT_ALREADY_USER_MSG");
		Text::script("COM_MULTIAGENCY_CSV_USER_IMPORTED");
		Text::script("COM_MULTIAGENCY_IMPORT_STAFF_CONFIRM");
	}
}
