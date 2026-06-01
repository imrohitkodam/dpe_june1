<?php
/**
 * @package    Jlike
 *
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2018 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;

// Import library dependencies
jimport('joomla.plugin.plugin');

/**
 * Jlike Comment
 *
 * @package  Jlike
 * @since    1.0
 */
class PlgContentjlike_Comment extends CMSPlugin
{
	/**
	 * Function used to get the HTML for Cooments to be shown for item
	 *
	 * @param   STRING  $context      The view and layout of item e.g.com_tjlms.course
	 * @param   INT     $lesson_id    Id of lesson
	 * @param   STRING  $lessontitle  Title of the lesson
	 *
	 * @return  STRING  $html
	 *
	 * @since  1.0.0
	 */
	public function showAllComments($context, $lesson_id, $lessontitle)
	{
		$comtjlmsHelper = JPATH_SITE . '/components/com_tjlms/helpers/main.php';

		if (!class_exists('comtjlmsHelper'))
		{
			JLoader::register('ComtjlmsHelper', $comtjlmsHelper);
			JLoader::load('ComtjlmsHelper');
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

		JRequest::setVar('data', json_encode($data_toset));

		$mainframe     = Factory::getApplication();
		$componentPath = JPATH_SITE . '/components/com_dpe';
		require_once $componentPath . '/controller.php';
		$component = new DpeController;
		$component->addViewPath($componentPath . '/views');
		$view  = $component->getView('jlike', 'raw');
		$templatePath = JPATH_SITE . '/templates/' . $mainframe->getTemplate() . '/html/com_dpe/jlike';

		if (File::exists($templatePath . '/default.php'))
		{
			$view->addTemplatePath($templatePath);
		}
		else
		{
			$view->addTemplatePath($componentPath . '/views/jlike/tmpl');
		}

		ob_start();
		$view->display();
		$html = ob_get_contents();
		ob_end_clean();

		return $html;
	}

	/**
	 * Function checkUser to validate the user data is valid or not
	 *
	 * @param   array  $filters  Comment array contain post data
	 *
	 * @return  boolean
	 */
	public function checkUser($filters)
	{
		$user  = Factory::getUser();
		$userId = $user->id;

		$contextData = explode(':', $filters['context']);

		$app = Factory::getApplication();

		$currentUserSuperUser = $user->authorise('core.admin');

		if (!empty($contextData[1]) && ($currentUserSuperUser || ($userId == $contextData[0])))
		{
			$userId = $contextData[1];
		}

		$assignedUsers = $this->getAssignedUser($filters['content_id'], $userId);

		if (!empty($assignedUsers))
		{
			if ($userId == $contextData[1])
			{
				// Return true if user is correct
				return true;
			}
		}

		return false;
	}

	/**
	 * Function to display the comments area
	 *
	 * @param   array  $comment  Comment array contain post data
	 *
	 * @return  boolean
	 */
	public function onBeforeSaveComment($comment)
	{
		if (!empty($comment['context']))
		{
			$isValid = $this->checkUser($comment);

			return $isValid;
		}
		else
		{
			return true;
		}
	}

	/**
	 * Function to get assigned users
	 *
	 * @param   INT  $contentId  Id of jlike content
	 *
	 * @param   INT  $userId     Id of user
	 *
	 * @return  object
	 */
	public function getAssignedUser($contentId, $userId = null)
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true);

		$query->select('u.id, u.name, u.email, u.username');
		$query->from($db->quoteName('#__jlike_todos') . 'as a');
		$query->join('INNER', $db->quoteName('#__users') . 'as u on u.id = a.assigned_to');
		$query->where($db->quoteName('a.content_id') . ' = ' . $db->quote((int) $contentId));

		if (!empty($userId))
		{
			$query->where($db->quoteName('a.assigned_to') . ' = ' . $db->quote((int) $userId));
		}

		$db->setQuery($query);
		$results = $db->loadObjectList();

		return $results;
	}

	/**
	 * Function to display the comments area
	 *
	 * @param   array  $filters  filters array contain post data
	 *
	 * @return  boolean
	 */
	public function onBeforeListComments($filters)
	{
		if (!empty($filters['context']))
		{
			$isValid = $this->checkUser($filters);

			return $isValid;
		}
		else
		{
			return true;
		}
	}
}
