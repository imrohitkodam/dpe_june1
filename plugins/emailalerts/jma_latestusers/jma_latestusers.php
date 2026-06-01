<?php
/**
 * @package     JMailAlerts
 * @subpackage  jma_latestusers
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2021 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

// Do not allow direct access
defined('_JEXEC') or die('Restricted access');

$jmailAlertsPluginPath    = JPATH_SITE . '/components/com_jmailalerts/helpers/plugins.php';
$jmaIntegrationHelperPath = JPATH_SITE . '/plugins/system/plg_sys_jma_integration/plg_sys_jma_integration/plugins.php';

// Include plugin helper file
// Else condition is needed when JMA integration plugin is used on sites where JMA is not installed
if (JFile::exists($jmailAlertsPluginPath))
{
	include_once $jmailAlertsPluginPath;
}
elseif (JFile::exists($jmaIntegrationHelperPath))
{
	include_once $jmaIntegrationHelperPath;
}

// Included to get jomsocial avatar
$jspath = JPATH_ROOT . '/components/com_community';

if (JFolder::exists($jspath))
{
	include_once $jspath . '/libraries/core.php';
}

/**
 * This plugin pulls the latest users data
 *
 * @since  2.5.1
 */
class PlgEmailalertsjma_Latestusers extends JMailAlertsPlugin
{
	public $extension = 'com_users';

	/**
	 * Plugin trigger to get latest matching records
	 *
	 * @param   string  $id               Userid or email id for user whom email will be sent
	 * @param   string  $lastEmailDate    Timestamp when last email was sent to that user
	 * @param   array   $userParams       Array of user's alert preference considering data tags
	 * @param   int     $fetchOnlyLatest  Decide to send only fresh content or not
	 *
	 * @return  array
	 *
	 * @since  2.5.0
	 */
	public function onEmail_jma_latestusers($id, $lastEmailDate, $userParams, $fetchOnlyLatest)
	{
		// This function is just a dummy
		// Let's call parent function
		return $this->onEmailTrigger($id, $lastEmailDate, $userParams, $fetchOnlyLatest);
	}

	/**
	 * Method to get the records based on user preferences.
	 *
	 * @param   string  $id               Userid or email id for user whom email will be sent
	 * @param   string  $lastEmailDate    Timestamp when last email was sent to that user
	 * @param   array   $userParams       Array of user's alert preference considering data tags
	 * @param   int     $fetchOnlyLatest  Decide to send only fresh content or not
	 *
	 * @return  array
	 *
	 * @since  2.5.0
	 */
	public function getList($id, $lastEmailDate, $userParams, $fetchOnlyLatest)
	{
		// Required Declarations
		$no_of_users = '';
		$db          = JFactory::getDbo();

		// If no userid/or no guest user
		if ($id === null)
		{
			$no_of_users = $this->params->get('no_of_users', 3);
		}
		else
		{
			// Get user preferences for this plugin parameters(shown in frontend)
			$no_of_users = $userParams['no_of_users'];
		}

		// If count param not found in user settings, use from plugin.
		if (!$no_of_users)
		{
			$no_of_users = $this->params->get('no_of_users', 3);
		}

		// Get plugin parameters(not shown in frontend)
		$disp_name              = $this->params->get('disp_name', 0);
		$show_name              = $this->params->get('show_name', 'username');
		$show_users_with_avatar = $this->params->get('show_users_with_avatar', 0);

		switch ($disp_name)
		{
			case 0:
				// Joomla
				$query = $db->getQuery(true);
				$query->select($show_name);
				$query->from($db->quoteName('#__users'));
				$query->where($db->quoteName('block') . " = 0");

				if ($id)
				{
					$query->where($db->quoteName('id') . " <> " . $id);
				}

				// Get only fresh content
				if ($fetchOnlyLatest)
				{
					$query->where($db->quoteName('registerDate') . " >= " . $db->Quote($lastEmailDate));
				}

				$query->order($db->quoteName('registerDate') . " DESC ");

				break;
			case 1:
				// Jomsocial
				$query = $db->getQuery(true);
				$query->select("a." . $show_name);
				$query->select($db->quoteName('a.id'));
				$query->select($db->quoteName('b.thumb'));
				$query->from($db->quoteName('#__users', 'a'));
				$query->join('INNER', $db->quoteName('#__community_users', 'b') . 'ON' . $db->quoteName('a.id') . '=' . $db->quoteName('b.userid'));
				$query->where($db->quoteName('a.block') . " = 0");

				if ($id)
				{
					$query->where($db->quoteName('b.userid') . " <> " . $id);
				}

				if ($show_users_with_avatar)
				{
					$query->where($db->quoteName('b.avatar') . " <> ''");
				}

				// Get only fresh content
				if ($fetchOnlyLatest)
				{
					$query->where($db->quoteName('a.registerDate') . " >= " . $db->Quote($lastEmailDate));
				}

				$query->order($db->quoteName('a.registerDate') . " DESC ");

				break;
			case 2:
				// Community builder
				$query = $db->getQuery(true);
				$query->select("a." . $show_name);
				$query->select($db->quoteName('a.id'));
				$query->select('b.avatar, b.avatarapproved');
				$query->from($db->quoteName('#__users', 'a'));
				$query->join('INNER', $db->quoteName('#__comprofiler', 'b') . ' ON' . $db->quoteName('a.id') . '=' . $db->quoteName('b.user_id'));
				$query->where($db->quoteName('b.approved') . " = 1");
				$query->where($db->quoteName('b.confirmed') . " = 1");

				if ($id)
				{
					$query->where($db->quoteName('b.user_id') . " <> " . $id);
				}

				if ($show_users_with_avatar)
				{
					$query->where($db->quoteName('b.avatar') . " <> ''");
				}

				// Get only fresh content
				if ($fetchOnlyLatest)
				{
					$query->where($db->quoteName('a.registerDate') . " >= " . $db->Quote($lastEmailDate));
				}

				$query->order($db->quoteName('a.registerDate') . " DESC ");

				break;
		}

		// Use user's preferred value for count
		$query->setLimit($no_of_users);

		$db->setQuery($query);
		$list = $db->loadObjectList();

		return $list;
	}
}
