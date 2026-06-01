<?php
/**
 * @package     JMailAlerts
 * @subpackage  com_jmailalerts
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2022 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// Do not allow direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView;

/**
 * Ajax sync view class
 *
 * @since  2.5.0
 */
class JmailalertsViewajaxsync extends HtmlView
{
	/**
	 * Display the view
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise an Error object.
	 */
	public function display($tpl = null)
	{
		// Get the instance to joomla database
		$db	   = Factory::getDBO();
		$input = Factory::getApplication()->input;

		// Get the all variables value form ajax request from ajax request url
		// If this variable value is zero the it identifies that this is the first ajax request to get the total number of users
		$setFirstAjaxCall = $input->get('set_firs_ajax_call', '', 'INT');
		$alertid          = $input->get('alertid', '0', 'INT');
		$lastEmailDate    = $input->get('last_email_date', '', 'STRING');
		$groupid          = $input->get('groupid', '0', 'INT');

		// $defaultFrequency        = $input->get('default_frequency', '0', 'INT');

		// $batchSize               = $input->get('batch_size', '400', 'INT');

		// $advancedOptionsChecked = $input->get('advanced_options_checked', '0', 'INT');

		$overwriteUserPref = $input->get('overwrite_user_pref', '0', 'INT');
		$readdUnsubUser    = $input->get('readd_unsub_user', '0', 'INT');

		if ($setFirstAjaxCall == 0)
		{
			// If this variable value is zero the it identifies that this is the first ajax request to get the total number of users
			// Overwrite existing user prefereces yes & readd usub user is yes (both are yes then)

			if ($groupid > 1)
			{
					if ($overwriteUserPref && $readdUnsubUser)
					{ 
						$db	 = Factory::getDBO();
						$db->setQuery("
							DELETE FROM #__jma_subscribers
							WHERE alert_id=" . $alertid . " AND user_id IN (SELECT users.id FROM #__users as users  LEFT JOIN #__user_usergroup_map as usermap ON users.id = usermap.user_id  WHERE group_id = ". $groupid ." ORDER BY users.id AND users.block=0)");

						$db->execute();
					}
					elseif ($overwriteUserPref)
					{ 
						// Overwrite existing user prefereces is 'yes' & readd usub user is 'No' then delete the entries from subscriber table for current 'alert id'
						// Where 'frequency' not zero (means don't delete the unsubscribe users entries)
						$db	 = Factory::getDBO();
						$db->setQuery("
							DELETE FROM #__jma_subscribers
							WHERE alert_id=" . $alertid . "
							AND frequency <> 0 AND user_id IN (SELECT users.id  FROM #__users as users  LEFT JOIN #__user_usergroup_map as usermap ON users.id = usermap.user_id  WHERE group_id = ". $groupid ." AND users.block=0)");

						$db->execute();
					}

			}
			else
			{
				if ($overwriteUserPref && $readdUnsubUser)
				{ 
					$db	 = Factory::getDBO();
					$db->setQuery("
						DELETE FROM #__jma_subscribers
						WHERE alert_id=" . $alertid . " AND user_id <> 0"
					);
					$db->execute();
				}
				elseif ($overwriteUserPref)
				{
					// Overwrite existing user prefereces is 'yes' & readd usub user is 'No' then delete the entries from subscriber table for current 'alert id'
					// Where 'frequency' not zero (means don't delete the unsubscribe users entries)
					$db	 = Factory::getDBO();
					$db->setQuery("
						DELETE FROM #__jma_subscribers
						WHERE alert_id=" . $alertid . "
						AND frequency <> 0 AND user_id <> 0");
						
					$db->execute();
				}
			}
			// Delete the all entries from subscriber table for current alert id
			// Get the user to sync
			// Get limited (decided by the batch_size) users from the joomla users table
		}

			if ($groupid > 1)
			{ 
				$db->setQuery("SELECT users.id ,users.name,users.email FROM #__users as users  LEFT JOIN #__user_usergroup_map as usermap ON users.id = usermap.user_id  WHERE group_id = ". $groupid . " AND users.id NOT IN (SELECT user_id FROM  
				#__jma_subscribers WHERE alert_id=$alertid) AND block=0 ORDER BY id LIMIT  0, " . $input->get('batch_size', '400', 'INT'));
				$usersTosync = $db->loadObjectList();


				$db->setQuery("SELECT count(users.id) as usercnt FROM #__users as users LEFT JOIN #__user_usergroup_map as usermap ON users.id = usermap.user_id  WHERE group_id = ". $groupid . " AND users.id NOT IN (SELECT user_id FROM  
				#__jma_subscribers WHERE alert_id=$alertid) AND block=0 ORDER BY id LIMIT  0, " . $input->get('batch_size', '400', 'INT'));

				$totalNumberOfUsers = $db->LoadObject();
			}
			else
			{
				$db->setQuery("SELECT id,name,email FROM #__users WHERE id NOT IN (SELECT user_id FROM  
				#__jma_subscribers WHERE alert_id=$alertid)". "AND block=0 ORDER BY id LIMIT  0, " . $input->get('batch_size', '400', 'INT'));

				$usersTosync = $db->loadObjectList();

				$db->setQuery("SELECT count(id) as usercnt FROM #__users WHERE id NOT IN (SELECT user_id FROM  
						#__jma_subscribers WHERE alert_id=$alertid)". "AND block=0 ORDER BY id LIMIT  0, " . $input->get('batch_size', '400', 'INT'));
				$totalNumberOfUsers = $db->LoadObject();
			}
		

		// If there are no rows, then all users ar synced; return 'No rows'
		if (count($usersTosync) == 0)
		{
			echo "No Users";
		}
		else
		{
			echo $totalNumberOfUsers->usercnt;
		}

		/**
		 * identify the alertid entry in old_sync_data
		 */
		$db->setQuery("SELECT id FROM #__jma_old_sync_data WHERE alert_id=" . $alertid);
		$alertPresent = $db->loadResult();

		// @exit;
		// $alertqry = null;

		// Load the template & default frequencies for the selected alert
		$query = "SELECT id,default_freq,template FROM #__jma_alerts WHERE id=$alertid";
		$db->setQuery($query);
		$alertdata = $db->loadObject();

		// FIRST GET THE EMAIL-ALERTS RELATED PLUGINS
		$db->setQuery('SELECT element FROM #__extensions WHERE folder = \'emailalerts\'  AND enabled = 1');

		// Get the plugin names and store in an array
		$plgInstalled = $db->loadColumn();

		// Return the array eg. Array ( [0] => jma_latestdownload [1] => jma_latestusers [2] => jma_latestnews_js )
		// $plgInTemplate store the plg-ins actualy used in template
		$plgInTemplate = array();

		for ($i = 0; $i < count($plgInstalled); $i++)
		{
			if (strstr($alertdata->template, $plgInstalled[$i]))
			{
				$plgInTemplate[] = $plgInstalled[$i];
			}
		}

		$entry = "";

		foreach ($plgInTemplate as $plug)
		{
			$query = "select params from #__extensions where element='" . $plug . "' && folder='emailalerts'";
			$db->setQuery($query);
			$plugParams = $db->loadResult();
			$paramList  = json_decode($plugParams, true);

			/**
			 * Add the entries in old_sync_data if alert id not present in old_sunc_data
			 */
			if (!$alertPresent)
			{
				$oldSyncData           = new stdClass;
				$oldSyncData->date     = date("Y-m-d H:i:s", mktime(0, 0, 0, date("m"), date("d"), date("Y")));
				$oldSyncData->alert_id = $alertid;
				$oldSyncData->plugin   = $plug;
				$oldSyncData->plg_data = $plugParams;

				if (!$db->insertObject('#__jma_old_sync_data', $oldSyncData))
				{
					echo "Insertion error";

					// @echo $db->stderr();

					exit;
				}
			}

			// @json_decode gives array from plug_params
			$plugentry = "";

			foreach ($paramList as $key => $value)
			{
				if (is_array($value))
				{
					// If value is array such as catagory,catid etc is an array
					// ConverT array of catid,catagory etc to list and then make string with seperated by comma
					$selected = implode(',', $value);
					$plugentry .= $plug . '|' . $key . "=" . $selected . "\n";
				}
				else
				{
					$plugentry .= $plug . '|' . $key . "=" . $value . "\n";
				}
			}

			if ($plug == 'jma_latestnews_js')
			{
				$plugentry = str_replace('category', 'catid', $plugentry);
			}

			// Plugentry for specific  plugin
			$entry .= $plugentry;
		}

		// Start of the mega-loop to insert data into the `email_alert` table
		$emailAlertEntryObject            = new stdClass;
		$emailAlertEntryObject->alert_id  = $alertid;
		$emailAlertEntryObject->frequency = $alertdata->default_freq;
		$emailAlertEntryObject->date      = $lastEmailDate;

		foreach ($usersTosync as $user)
		{
			$emailAlertEntryObject->user_id               = $user->id;
			$emailAlertEntryObject->name                  = $user->name;
			$emailAlertEntryObject->email_id              = $user->email;
			$emailAlertEntryObject->plugins_subscribed_to = $entry;
			
			if (!$db->insertObject('#__jma_subscribers', $emailAlertEntryObject))
			{
				echo "Insertion error";

				// @echo $db->stderr();

				exit;
			}
		}
	}
}
