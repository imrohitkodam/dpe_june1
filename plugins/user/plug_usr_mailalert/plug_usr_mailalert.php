<?php
/**
 * @package     JMailAlerts
 * @subpackage  plug_usr_mailalert
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2022 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access.
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\User\UserHelper;


/**
 * JMA uer plugin
 *
 * @since  1.5.0
 */
class PlgUserPlug_Usr_Mailalert extends CMSPlugin
{
	/**
	 * Add/update entries for email subscriptions for user being edted
	 *
	 * @param   array    $user    entered user data
	 * @param   boolean  $isNew   true if this is a new user
	 * @param   boolean  $result  true if saving the user worked
	 * @param   string   $error   error message
	 *
	 * @return  boolean
	 */
	public function onUserAfterSave($user, $isNew, $result, $error)
	{
		$db     = Factory::getDBO();
		$userid = $user['id'];
		$userGroupOfalertUser = UserHelper::getUserGroups($userid);

		if ($isNew)
		{
			$query  = $db->getQuery(true);

			// Get new user details
			$query->SELECT(' id, name, email');
			$query->from('`#__users`');
			$query->where('id = ' . $userid);
			$db->setQuery($query);
			$userData = $db->loadObject();

			// Get array of alert ids which are set to default for new users.
			$query = 'SELECT id FROM #__jma_alerts WHERE is_default = 1 AND state =  1';
			$db->setQuery($query);
			$alertid       = $db->loadColumn();
			$alertidString = $alertid;

			$alertqry = "";

			if (count($alertidString))
			{
				for ($i = 0; $i < count($alertidString); $i++)
				{
					$alertqry .= " id = " . $alertidString[$i];

					if ($i != (count($alertidString) - 1))
					{
						$alertqry .= " OR ";
					}
				}
			}
			else
			{
				return false;
			}

			$query = "SELECT element FROM #__extensions WHERE folder = 'emailalerts'  AND enabled = 1";
			$db->setQuery($query);
			$plugnamecompair = $db->loadColumn();
			$plugnamesend    = implode(',', $plugnamecompair);
			$plugnamecompair = explode(',', $plugnamesend);

			$cnt = 0;
			$rnt = 99;

			if (!empty($alertqry))
			{
				$query = "SELECT id, default_freq, usergroup, is_default, template FROM #__jma_alerts WHERE " . $alertqry;
				$db->setQuery($query);
				$result = $db->loadObjectList();
			}
			else
			{
				return false;
			}

			if (count($result))
			{
				foreach ($result as $key)
				{	
					if ($key->is_default)
					{
						$alertUsergroup = json_decode($key->usergroup);
						$matchedUserGroup = array_intersect($alertUsergroup, $userGroupOfalertUser);

						if (!$matchedUserGroup)
						{
							continue;
						}
					}
					

					$emailAlertEntryObject            = new stdClass;
					$emailAlertEntryObject->user_id   = $userid;
					$emailAlertEntryObject->alert_id  = $key->id;
					$emailAlertEntryObject->frequency = $key->default_freq;

					// If new user added the email will sent imidiately for the alert if the date is null.
					$emailAlertEntryObject->date      = '0000-00-00 00:00:00';//date("Y-m-d H:i:s", mktime(0, 0, 0, date("m"), date("d") - $key->default_freq, date("Y")));

					$entry = "";

					if ($userData)
					{
						$emailAlertEntryObject->name     = $userData->name;
						$emailAlertEntryObject->email_id = $userData->email;
					}

					if (count((array) $plugnamecompair))
					{

						for ($i = 0; $i < count($plugnamecompair); $i++)
						{
							if (strstr($key->template, $plugnamecompair[$i]))
							{
								$pluginNameString[] = $plugnamecompair[$i];
							}
						}
					}

					if (count((array) $pluginNameString))
					{

						foreach ($pluginNameString as $plug)
						{
							$query = "SELECT params
							 FROM #__extensions
							 WHERE element = '" . $plug . "'
							 AND folder = 'emailalerts' ";
							 $db->setQuery($query);
							 $plugParams = $db->loadResult();

							 if (preg_match_all('/\[(.*?)\]/', $plugParams, $match))
							 {
							 	foreach ($match[1] as $mat)
							 	{
							 		$match = str_replace(',', '|', $mat);
							 		$plugParams = str_replace($mat, $match, $plugParams);
							 	}
							 }

							 $newlin = explode(",", $plugParams);

							 foreach ($newlin as $v)
							 {
							 	if (!empty($v))
							 	{
							 		$v = str_replace('{', '', $v);
							 		$v = str_replace(':', ' = ', $v);
							 		$v = str_replace('"', '', $v);
							 		$v = str_replace('}', '', $v);
							 		$v = str_replace('[', '', $v);
							 		$v = str_replace(']', '', $v);
							 		$v = str_replace('|', ',', $v);

							 		if (!($cnt > $rnt))
							 		{
							 			$entry .= $plug . '|' . $v . "\n";
							 		}
							 	}
							 }

							 $cnt = 0;
							}
						}

						unset($pluginNameString);
						unset($match);

						$emailAlertEntryObject->plugins_subscribed_to = $entry;

						if (!$db->insertObject('#__jma_subscribers', $emailAlertEntryObject))
						{
						// @echo "Insertion error";
							return false;
						}
					}
				}
			}
			else
			{

				$query  = $db->getQuery(true);

			// Get new user details
				$query->SELECT(' id, name, email');
				$query->from('`#__users`');
				$query->where('id = ' . $userid);
				$db->setQuery($query);
				$userData = $db->loadObject();

			// Get array of alert ids which are set to default for new users.
				$query = 'SELECT id FROM #__jma_alerts WHERE is_default = 1 AND state =  1';
				$db->setQuery($query);
				$alertid       = $db->loadColumn();
				$alertidString = $alertid;

				$alertqry = "";

				if (count($alertidString))
				{
					for ($i = 0; $i < count($alertidString); $i++)
					{
						$alertqry .= " id = " . $alertidString[$i];

						if ($i != (count($alertidString) - 1))
						{
							$alertqry .= " OR ";
						}
					}
				}
				else
				{
					return false;
				}

				if (!empty($alertqry))
				{
					$query = "SELECT id, default_freq, usergroup, is_default, template FROM #__jma_alerts WHERE " . $alertqry;
					$db->setQuery($query);
					$result = $db->loadObjectList();
				}
				else
				{
					return false;
				}

				if (count($result))
				{
					foreach ($result as $key)
					{	
						if ($key->is_default)
						{
							$alertUsergroup = json_decode($key->usergroup);
							$matchedUserGroup = array_intersect($alertUsergroup, $userGroupOfalertUser);

							if (!$matchedUserGroup)
							{


					   // Conditions for which records should be Deleted.
								$deleteConditions = array(
									$db->quoteName('user_id') . ' = ' . $userid,$db->quoteName('alert_id') . ' = ' . $key->id
								);
								$query  = $db->getQuery(true);

								$query->delete($db->quoteName('#__jma_subscribers'));
								$query->where($deleteConditions);

								$db->setQuery($query);

								$deleteResult = $db->execute();

							}
							else
							{

								$emailAlertEntryObject            = new stdClass;
								$emailAlertEntryObject->user_id   = $userid;
								$emailAlertEntryObject->alert_id  = $key->id;
								$emailAlertEntryObject->frequency = $key->default_freq;			
							
								// Check user avial for the alert or not
								$checkQuery  = $db->getQuery(true);

								$checkQuery->SELECT(' id');
								$checkQuery->from('`#__jma_subscribers`');
								$checkQuery->where('user_id = ' . $userid);
								$checkQuery->where('alert_id = ' . $key->id);
								$db->setQuery($checkQuery);
								$userAvailData = $db->loadObject();
								
								if ($userAvailData)
								{
									continue;
								}

					// If new user added the email will sent imidiately for the alert if the date is null.
					$emailAlertEntryObject->date      = '0000-00-00 00:00:00';//date("Y-m-d H:i:s", mktime(0, 0, 0, date("m"), date("d") - $key->default_freq, date("Y")));

					$entry = "";

					if ($userData)
					{
						$emailAlertEntryObject->name     = $userData->name;
						$emailAlertEntryObject->email_id = $userData->email;
					}

					if (count((array) $plugnamecompair))
					{

						for ($i = 0; $i < count($plugnamecompair); $i++)
						{
							if (strstr($key->template, $plugnamecompair[$i]))
							{
								$pluginNameString[] = $plugnamecompair[$i];
							}
						}
					}

					if (count((array) $pluginNameString))
					{

						foreach ($pluginNameString as $plug)
						{
							$query = "SELECT params
							 FROM #__extensions
							 WHERE element = '" . $plug . "'
							 AND folder = 'emailalerts' ";
							 $db->setQuery($query);
							 $plugParams = $db->loadResult();

							 if (preg_match_all('/\[(.*?)\]/', $plugParams, $match))
							 {
							 	foreach ($match[1] as $mat)
							 	{
							 		$match = str_replace(',', '|', $mat);
							 		$plugParams = str_replace($mat, $match, $plugParams);
							 	}
							 }

							 $newlin = explode(",", $plugParams);

							 foreach ($newlin as $v)
							 {
							 	if (!empty($v))
							 	{
							 		$v = str_replace('{', '', $v);
							 		$v = str_replace(':', ' = ', $v);
							 		$v = str_replace('"', '', $v);
							 		$v = str_replace('}', '', $v);
							 		$v = str_replace('[', '', $v);
							 		$v = str_replace(']', '', $v);
							 		$v = str_replace('|', ',', $v);

							 		if (!($cnt > $rnt))
							 		{
							 			$entry .= $plug . '|' . $v . "\n";
							 		}
							 	}
							 }

							 $cnt = 0;
							}
						}

						unset($pluginNameString);
						unset($match);

						$emailAlertEntryObject->plugins_subscribed_to = $entry;

						if (!$db->insertObject('#__jma_subscribers', $emailAlertEntryObject))
						{
						// @echo "Insertion error";
							return false;
						}

					}

				}

			}
		}

		$query  = $db->getQuery(true);

			// Fields to update.
		$fields = array(
			$db->quoteName('email_id') . ' = ' . $db->quote($user['email'])
		);

			// Conditions for which records should be updated.
		$conditions = array(
			$db->quoteName('user_id') . ' = ' . $userid
		);

		$query->update($db->quoteName('#__jma_subscribers'))->set($fields)->where($conditions);
		$db->setQuery($query);
		$db->execute();
	}

	return true;
}

	/**
	 * Remove all email alert subscriptions for the user
	 *
	 * Method is called after user data is deleted from the database
	 *
	 * @param   array    $user     Holds the user data
	 * @param   boolean  $success  True if user was successfully stored in the database
	 * @param   string   $msg      Message
	 *
	 * @return  boolean
	 *
	 * @since   1.6
	 */
	public function onUserAfterDelete($user, $success, $msg)
	{
		if (!$success)
		{
			return false;
		}

		$db = Factory::getDbo();
		$query = $db->getQuery(true)
		->delete($db->quoteName('#__jma_subscribers'))
		->where($db->quoteName('user_id') . ' = ' . (int) $user['id']);

		$db->setQuery($query)->execute();

		return true;
	}
}
