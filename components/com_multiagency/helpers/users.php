<?php

/**
 * @version    CVS: 1.0.0
 * @package    Com_Multiagency
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2017 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;
use Joomla\CMS\Factory;

/**
 * Class MultiagencyFrontendHelper
 *
 * @since  1.6
 */
class MultiagencyHelper
{
	/**
	 * Check if logged in user is Organsiation admin
	 *
	 * @param   string  $clientId  client id
	 *
	 * @return role
	 */

	public static function getGroupId($clientId)
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true);

		$query->select($db->quoteName('grp_id'));
		$query->from($db->quoteName('#__es_group_xref'));
		$query->where($db->quoteName('client_id') . " = " . $db->quote($clientId));

		$db->setQuery($query);

		$groupId = $db->loadObject();

		// Get groupId
		if ($groupId)
		{
			return $groupId;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Method to get an array object of user data
	 *
	 * @param   string  $pkid  primary key
	 *
	 * @return  mixed An array object of data on success, false on failure.
	 */

	public function getUser($pkid)
	{
		$db = Factory::getDBO();
		$query = $db->getQuery(true);
		$query->select('id, name, email, username');
		$query->from($db->quoteName('#__users'));
		$query->where($db->quoteName('id') . '=' . $db->quote($pkid));
		$db->setQuery($query);
		$userInfo = $db->loadObject();

		return $userInfo;
	}
}
