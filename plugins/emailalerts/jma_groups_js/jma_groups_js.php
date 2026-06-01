<?php
/**
 * @package     JMailAlerts
 * @subpackage  jma_groups_js
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

/**
 * Latest groups plugin for JMailAlerts Component.
 *
 * @since  2.5.1
 */
class PlgEmailalertsJma_Groups_Js extends JMailAlertsPlugin
{
	public $extension = 'com_community';

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
	public function onEmail_jma_groups_js($id, $lastEmailDate,$userParams, $fetchOnlyLatest)
	{
		// This function is just a dummy
		// Let's call parent function
		return $this->onEmailTrigger($id, $lastEmailDate, $userParams, $fetchOnlyLatest);
	}

	/**
	 * Method to get latest groups
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
		$list = array();

		// If no userid or no guest user, return blank array for HTML and CSS.
		if ($id === null)
		{
			return $list;
		}

		$db = JFactory::getDbo();

		// Get Parameters
		$params = $this->params;

		// Get user preferences for this plugin parameters(shown in frontend)
		if (isset($userParams['catid']) && !empty($userParams['catid']))
		{
			$gcatid = $userParams['catid'];
		}

		$no_of_users = (int) $userParams['no_of_users'];

		$qry = $db->getQuery(true);
		$qry->select('DISTINCT g.id, g.name AS title, g.membercount, g.created AS date');
		$qry->from($db->quoteName('#__community_groups', 'g'));

		if (!empty($gcatid))
		{
			$qry->where($db->quoteName('g.categoryid') . " IN ($gcatid)");
		}

		$qry->where($db->quoteName('g.published') . " = 1 ");

		// Get only fresh content
		if ($fetchOnlyLatest)
		{
			$qry->where("UNIX_TIMESTAMP(g.created) > UNIX_TIMESTAMP('$lastEmailDate')");
		}

		$qry->order('g.created DESC');
		$qry->setLimit($no_of_users);

		$db->setQuery($qry);
		$newGroups = $db->loadObjectList();

		if ($newGroups != null)
		{
			$link    = 'index.php?option=com_community&view=groups';
			$Itemid  = $this->getItemId($link);
			$app     = JFactory::getApplication();
			$replace = JUri::root();

			if ($app->isClient('administrator'))
			{
				foreach ($newGroups as $k => $newgroup)
				{
					$grouplink           = "index.php?option=com_community&view=groups&task=viewgroup&groupid=";
					$newGroups[$k]->link = JRoute::_($replace . $grouplink . $newgroup->id . "&amp;Itemid=" . $Itemid, false);
				}
			}
			else
			{
				foreach ($newGroups as $k => $newgroup)
				{
					$grouplink           = "index.php?option=com_community&view=groups&task=viewgroup&groupid=";
					$newGroups[$k]->link = JUri::root() . substr(CRoute::_($grouplink . $newgroup->id), strlen(JUri::base(true)) + 1);
				}
			}
		}

		return $newGroups;
	}
}
