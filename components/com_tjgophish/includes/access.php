<?php
/**
 * @package     TJ-Gophish
 * @subpackage  com_tjgophish
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Table\Table;
use Joomla\String\StringHelper;

/**
 * TjGoPhish factory class.
 *
 * This class perform the helpful operation required to TjGoPhish package
 *
 * @since  1.0.0
 */
class TjGoPhishAccess
{
	/**
	 * Check if user is authorised to add item
	 *
	 * @param   string  $view    View Name
	 * 
	 * @param   string  $userId  Users Id
	 *
	 * @return  boolean True on success
	 *
	 * @since   1.0.0
	 **/
	public static function canCreate($view, $userId = 0)
	{
		$user = empty($userId) ? Factory::getUser() : Factory::getUser($userId);

		if (ComponentHelper::getComponent('com_cluster', true)->enabled)
		{
			// Get com_subusers component status
			$subUserExist = ComponentHelper::getComponent('com_subusers', true)->enabled;

			// Check user have permission to edit record of assigned cluster
			if ($subUserExist)
			{
				JLoader::import("/components/com_subusers/includes/rbacl", JPATH_ADMINISTRATOR);

				return RBACL::check($user->id, 'com_cluster', 'core.create.' . $view);
			}
		}
		else
		{
			return $user->authorise('core.' . $view . '.create', 'com_tjgophish.' . $view);
		}
	}
}
