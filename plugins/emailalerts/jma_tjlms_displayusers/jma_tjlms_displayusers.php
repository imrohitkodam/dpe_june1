<?php
/**
 * @package     JMailAlerts
 * @subpackage  jma_tjlms_displayusers
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

// Do not allow direct access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Mail\Mail;

use Joomla\CMS\Factory;
use Joomla\CMS\User\User;
use Joomla\CMS\Filesystem\File;

$jmailAlertsPluginPath    = JPATH_SITE . '/components/com_jmailalerts/helpers/plugins.php';
$jmaIntegrationHelperPath = JPATH_SITE . '/plugins/system/plg_sys_jma_integration/plg_sys_jma_integration/plugins.php';

// Include plugin helper file
// Else condition is needed when JMA integration plugin is used on sites where JMA is not installed
if (File::exists($jmailAlertsPluginPath))
{
	include_once $jmailAlertsPluginPath;
}
elseif (File::exists($jmaIntegrationHelperPath))
{
	include_once $jmaIntegrationHelperPath;
}

/**
 * This plugin pulls the inprogress courses data of user
 *
 * @since  1.3.32
 */
class PlgEmailalertsjma_Tjlms_Displayusers extends MailAlertsPlugin
{
	public $extension = 'com_tjlms';

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
	 * @since  1.3.32
	 */
	public function onEmail_jma_tjlms_displayusers($id, $lastEmailDate, $userParams, $fetchOnlyLatest)
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
	 * @since  1.3.32
	 */
	public function getList($id, $lastEmailDate, $userParams, $fetchOnlyLatest)
	{
		$db = Factory::getDbo();

		if ($this->params->get('show_recent_completions_users'))
		{
			$query = $db->getQuery(true);
			$query->select('u.id,u.name, c.title, ct.timeend, c.id as courseId');
			$query->from($db->qn('#__users', 'u'));
			$query->join('INNER', $db->qn('#__tjlms_course_track', 'ct') . ' ON (' . $db->qn('ct.user_id') . ' = ' . $db->qn('u.id') . ')');
			$query->join('INNER', $db->qn('#__tjlms_courses', 'c') . ' ON (' . $db->qn('c.id') . ' = ' . $db->qn('ct.course_id') . ')');
			$query->join('INNER', $db->qn('#__categories', 'cat') . ' ON (' . $db->qn('cat.id') . ' = ' . $db->qn('c.catid') . ')');
			$query->where($db->qn('c.state') . '= 1');
			$query->where($db->qn('cat.published') . '= 1');
			$query->where($db->qn('ct.status') . '= "C"');
			$query->order('ct.timeend DESC');
			$query->setLimit($this->params->get('no_of_recent_completions_users'));
			$db->setQuery($query);
			$list = $db->loadObjectList();
		}

		return $list;
	}
}
