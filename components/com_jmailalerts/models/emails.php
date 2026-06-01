<?php
/**
 * @package     JMailAlerts
 * @subpackage  com_jmailalerts
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2022 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Log\LogEntry;
use Joomla\CMS\Log\Logger\FormattedtextLogger;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\Registry\Registry;

jimport('techjoomla.tjnotifications.tjnotifications');

/**
 * Class will contain function to store alerts records
 *
 * @since  1.0.0
 */
class JmailalertsModelEmails extends BaseDatabaseModel
{
	/**
	 * Log array
	 *
	 * @var    array
	 * @since  2.6.0
	 */
	public $log = null;

	/**
	 * Function for retun alert frequency select box checking allowd frequency , default_freq
	 *
	 * @param   int  $altid  Alert id
	 *
	 * @return  array
	 */
	public function getFreq($altid)
	{
		$user  = Factory::getUser();
		$db    = $this->_db;
		$query = $db->getQuery(true);

		$query->select($db->qn(['title', 'allowed_freq', 'default_freq', 'allow_users_select_plugins', 'description']))
			->from($db->qn('#__jma_alerts'))
			->where($db->qn('id') . ' = ' . (int) $altid);

		$db->setQuery($query);
		$resultfrq = $db->loadObjectList();
		$allowFrq = $resultfrq[0]->allowed_freq;

		// $allowFrq = explode(',', $allowFrq);
		$allowFrq = str_replace('[', '', $allowFrq);
		$allowFrq = str_replace(']', '', $allowFrq);
		$allowFrq = str_replace('"', '', $allowFrq);
		$allowFrq = explode(',', $allowFrq);

		// Get frequency name for each allowed frequency.
		foreach ($allowFrq as $key => $value)
		{
			$query = $db->getQuery(true);
			$query->select($db->qn('name'))
				->from($db->qn('#__jma_frequencies'))
				->where($db->qn('id') . ' = ' . (int) $value);

			$db->setQuery($query);
			$frequencyName[$value] = Text::_($db->loadResult());
		}

		$query = $db->getQuery(true);
		$query->select($db->qn('frequency'))
			->from($db->qn('#__jma_subscribers'))
			->where($db->qn('alert_id') . ' = ' . (int) $altid)
			->where($db->qn('user_id') . ' = ' . (int) $user->id);

		$db->setQuery($query);
		$uerselfrq = $db->loadResult();

		if ($uerselfrq)
		{
			$defaultFreq = $uerselfrq;
		}
		else
		{
			$defaultFreq = $resultfrq[0]->default_freq;
		}

		$setfrqname    = 'c' . $altid;
		$alertfrqdta[] = HTMLHelper::_("select.genericlist", $frequencyName, "$setfrqname", "class='input form-select form-control' ", "value", "text", $defaultFreq);
		$alertfrqdta[] = $resultfrq[0]->title;
		$alertfrqdta[] = $resultfrq[0]->description;
		$alertfrqdta[] = $resultfrq[0]->allow_users_select_plugins;

		return $alertfrqdta;
	}

	/**
	 * Function to save alter day's
	 * Function called from the controller.php file.
	 * This function is called when the user saves the email preferences(Daily, monthly, weekly, etc) from the frontend
	 *
	 * @return  void
	 */
	public function savePref()
	{
		$app                     = Factory::getApplication();

		// CAPTCHA Validation via Plugin
		try {
			$app->triggerEvent('onBeforeJmaAlertSubscriptionSave');
		} catch (Exception $e) {
			$app->enqueueMessage($e->getMessage(), 'error');
			
			if($app->input->get('fromModule', '', 'INT'))
			{	
				$currentUrl = $app->input->get('currentUrl', '', 'STRING');
				$currentUrl = trim($currentUrl);
				$app->redirect($currentUrl);
			}else
			{
				$itemid = $this->getItemid();
				$app->redirect(Route::_('index.php?option=com_jmailalerts&view=emails&Item_id=' . $itemid, false));	
			}
			return;
		}

		$post                    = $app->input->post;
		$unsubscribeChkBoxStatus = $post->get('unsubscribe_chk_box');
		//Dpe hack
		$UserOrganisation = $post->get('user_org');
		// Hack end
		$my                      = Factory::getUser();
		$userState               = 1;

		if ($unsubscribeChkBoxStatus)
		{
			$userState = 0;
		}

		$alt = $post->get('alt', array(), 'array');

		// Gt all alert id
		$query = "SELECT id FROM #__jma_alerts";
		$this->_db->setQuery($query);
		$delaltuser = $this->_db->loadColumn();

		$delalt = array();

		for ($i = 0; $i < count($delaltuser); $i++)
		{
			if (!in_array($delaltuser[$i], $alt))
			{
				$delalt[] = $delaltuser[$i];
			}
		}

		// Query construct for delete
		$tmpdel = array();

		for ($i = 0; $i < count($delalt); $i++)
		{
			$tmpdel[] = (int) $delalt[$i];
		}

		if (!empty($tmpdel))
		{
			$tmpdelStr = $this->_db->qn('alert_id') . " IN (" . implode(',', $tmpdel) . ")";
		}
		else
		{
			$tmpdelStr = "";
		}

		if ($tmpdelStr != "" && ($my->id || $post->get('user_email', '', 'STRING')))
		{

			$db = $this->_db;
			$query = $db->getQuery(true);

			if ($my->id)
			{
				$query->where($db->qn('user_id') . ' = ' . (int) $my->id);
			}
			else
			{
				$query->where($db->qn('email_id') . ' = ' . $db->Quote($post->get('user_email', '', 'STRING')));
			}

			$query->update($db->qn('#__jma_subscribers'))
				->set($db->qn('state') . ' = 0')
				->where($tmpdelStr);

			$db->setQuery($query);
			$db->execute();
		}

		for ($i = 0; $i < count($alt); $i++)
		{
			$dbPlugEntry = "";

			if (!empty($post->get('alt')))
			{
				$tmp = 'ch' . $alt[$i];

				if (count($post->get($tmp, array(), 'ARRAY')) != 0)
				{
					$userSetPlug = array_values($post->get("$tmp", array(), 'array'));
				}

				// Use reflection class to get protected property 'data' from input->post
				// As we are not using jform[field], this is needed to get plugin names used
				$reflectionObjData = new ReflectionClass($post);
				$property          = $reflectionObjData->getProperty('data');
				$property->setAccessible(true);
				$reflectionObjData = $property->getValue($post);

				$plugNames = array_keys($reflectionObjData);

				// Code for converting the plugin params to store in the database
				foreach ($plugNames as $plugName)
				{
					if (count($post->get($tmp, array(), 'ARRAY')) != 0)
					{
						if (in_array($plugName, $userSetPlug))
						{
							foreach ($post->get($plugName, array(), 'array') as $key => $val)
							{
								if (is_array($val))
								{
									$val = implode(',', $val);
								}

								$dbPlugEntry .= $plugName . '|' . $key . '=' . $val . "\n";
							}
						}
					}
				}
			}
			else
			{
				// Space is important
				$dbPlugEntry = " ";
			}

			$dbPlugEntry = str_replace("_$alt[$i]", "", $dbPlugEntry);

			$today = Factory::getDate()->Format('Y-m-d H:i:s');


			$db = $this->_db;
			$query = $db->getQuery(true);
			$query->select('*')
				->from($db->qn('#__jma_subscribers'))
				->where($db->qn('alert_id') . ' = ' . (int) $alt[$i]);

			// For registered user
			if ($my->id)
			{
				$query->where($db->qn('user_id') . ' = ' . (int) $my->id);
			}
			else
			{
				$query->where($db->qn('email_id') . ' = ' . $db->Quote($post->get('user_email', '', 'STRING')));
			}

			$db->setQuery($query);
			$result = $db->loadAssoc();

			$guestUserId = '';
			
			if(!empty($result))
			{
				$guestUserId = $result['user_id'];
			}

			if(($guestUserId === 0) && $result['email_id'] == $post->get('user_email', '', 'STRING'))
			{
				$msg = Text::_('COM_JMAILALERTS_SETTINGS_EMAIL_USED');
				$app->enqueueMessage($msg, 'warning');

				// DPE hack to use as Module
				if($app->input->get('fromModule', '', 'INT'))
				{	
					$currentUrl = $app->input->get('currentUrl', '', 'STRING');

					$currentUrl = trim($currentUrl);
					$app->redirect($currentUrl);
				}else
				{
					$itemid = $this->getItemid();
					$app->redirect(Route::_('index.php?option=com_jmailalerts&view=emails&Item_id=' . $itemid, false));	
				}

			}

			if (empty($result))
			{
				$isNew  = true;

				if ($dbPlugEntry == '')
				{
					$dbPlugEntry = '';
				}
				else
				{
					// For registered user
					if ($my->id)
					{
						$getNameEmail = $this->getname_email($my->id);
						$query = $db->getQuery(true);
						$columns = array('user_id', 'name', 'email_id', 'alert_id', 'frequency', 'date', 'plugins_subscribed_to');
						$values = array(
							(int) $my->id,
							$db->Quote($getNameEmail['username']),
							$db->Quote($getNameEmail['email']),
							(int) $alt[$i],
							(int) $post->get("c$alt[$i]"),
							$db->Quote($today),
							$db->Quote($dbPlugEntry)
						);

						$query->insert($db->qn('#__jma_subscribers'))
							->columns($db->qn($columns))
							->values(implode(',', $values));
					}
					else
					{
						$today = '0000-00-00 00:00:00'; //DPE Hack to make the date null as the email is not sending on the email on after 
						// For guest user...
						$query = $db->getQuery(true);
						$columns = array('user_id', 'alert_id', 'name', 'email_id', 'frequency', 'date', 'plugins_subscribed_to');
						$values = array(
							0,
							(int) $alt[$i],
							$db->Quote($post->get('user_name')),
							$db->Quote($post->get('user_email', '', 'STRING')),
							(int) $post->get("c$alt[$i]"),
							$db->Quote($today),
							$db->Quote($dbPlugEntry)
						);

						$query->insert($db->qn('#__jma_subscribers'))
							->columns($db->qn($columns))
							->values(implode(',', $values));
					}
				}
			}
			else
			{
				$isNew  = false;
				$updateQueryString = '';

				if ($dbPlugEntry != '')
				{
					$db = $this->_db;
					$query = $db->getQuery(true);
					$query->update($db->qn('#__jma_subscribers'))
						->set($db->qn('state') . ' = ' . (int) $userState)
						->set($db->qn('frequency') . ' = ' . (int) $post->get("c$alt[$i]"))
						->set($db->qn('plugins_subscribed_to') . ' = ' . $db->Quote($dbPlugEntry))
						->where($db->qn('alert_id') . ' = ' . (int) $alt[$i]);

					if ($my->id)
					{
						$query->where($db->qn('user_id') . ' = ' . (int) $my->id);
					}
					else
					{
						$query->where($db->qn('email_id') . ' = ' . $db->Quote($post->get('user_email', '', 'STRING')));
					}
				}
				else
				{
					$updateConditionNotSelected = "";
					$updateCondition = "";
					$updateState = "`state`=0";
					$tmp = "ch" . $alt[$i];
					$selectedPlugins = $post->get($tmp, array(), 'ARRAY');
					$updatedSelectedPlugins = array();

					if (count($selectedPlugins) > 0)
					{
						$db = $this->_db;
						foreach ($selectedPlugins as $selectedPlugin)
						{
							$selectedPlugin = str_replace("_". $alt[$i], "", $selectedPlugin);
							
							$query = $db->getQuery(true);
							$selectedFrequency = $post->get("c" . $alt[$i], 0, "INT");

							$query->update($db->qn('#__jma_subscribers'))
								->set($db->qn('state') . ' = 1')
								->set($db->qn('frequency') . ' = ' . (int) $selectedFrequency)
								->where($db->qn('alert_id') . ' = ' . (int) $alt[$i])
								->where($db->qn('plugins_subscribed_to') . ' LIKE ' . $db->Quote('%' . $selectedPlugin . '%'));

							if ($my->id)
							{
								$query->where($db->qn('user_id') . ' = ' . (int) $my->id);
							}
							else
							{
								$query->where($db->qn('email_id') . ' = ' . $db->Quote($post->get('user_email', '', 'STRING')));
							}

							$db->setQuery($query);
							$db->execute();

							$updateConditionNotSelected .= " AND " . $db->qn('plugins_subscribed_to') . " NOT LIKE " . $db->Quote('%' . $selectedPlugin . '%');
						}
					}

					$query = $db->getQuery(true);
					$query->update($db->qn('#__jma_subscribers'))
						->set($db->qn('state') . ' = 0')
						->where($db->qn('alert_id') . ' = ' . (int) $alt[$i]);

					if ($my->id)
					{
						$query->where($db->qn('user_id') . ' = ' . (int) $my->id);
					}
					else
					{
						$query->where($db->qn('email_id') . ' = ' . $db->Quote($post->get('user_email', '', 'STRING')));
					}

					if ($updateConditionNotSelected)
					{
						$query->where("1=1" . $updateConditionNotSelected);
					}
				}
			}

			$this->_db->setQuery($query);
			$this->_db->execute();

			$data = array();
			$data['id']      = (!empty($result['id'])) ? $result['id'] : 0;
			$data['user_id'] = $my->id ? $my->id : 0;

			// New Subscription
			if ($isNew)
			{
				// Register User
				if ($my->id)
				{
					$getNameEmail     = $this->getname_email($my->id);
					$data['name']     = $getNameEmail['username'];
					$data['email_id'] = $getNameEmail['email'];
				}
				else
				{
					$data['name']     = $post->get('user_name');
					$data['email_id'] = $post->get('user_email', '', 'STRING');
				}

				$data['date']                  = $today;
				$data['state']                 = $userState;
				$data['alert_id']              = $alt[$i];
				$data['frequency']             = $post->get("c$alt[$i]");
				$data['plugins_subscribed_to'] = $dbPlugEntry;
				$data['state']                 = $userState;
				$data['subscriptionId']        = $this->_db->insertid();
				$data['userOrganisation']      = $UserOrganisation;


				// DPE HAck
				PluginHelper::importPlugin('jmailalert');
				Factory::getApplication()->triggerEvent('onAfterJmaAlertSubscriptionSave', array($data, $isNew));
				PluginHelper::importPlugin('dpe');
   			    Factory::getApplication()->triggerEvent('onAfterJmaAlertSubscriptionSaveNotifyGuestUser', array($data, $isNew));
				

			}
			// Edit Subscription
			else
			{
				if ($userState != $result['state'] || $result['frequency'] != $post->get("c$alt[$i]"))
				{
					$data['name']     = $result['name'];
					$data['email_id'] = $result['email_id'];
					$data['date']                  = $today;
					$data['state']                 = $userState;
					$data['alert_id']              = $alt[$i];
					$data['frequency']             = $post->get("c$alt[$i]");
					$data['plugins_subscribed_to'] = $dbPlugEntry;
					$data['state']                 = $userState;
					$data['subscriptionId']        = $result['id'];

					PluginHelper::importPlugin('jmailalert');
					Factory::getApplication()->triggerEvent('onAfterJmaAlertSubscriptionSave', array($data, $isNew));
				}
			}
		}

		$msg                    = Text::_('COM_JMAILALERTS_SETTINGS_SAVED_SUCCESSFULLY');
		$jmailalertsModelEmails = new jmailalertsModelEmails;
		$itemid                 = $jmailalertsModelEmails->getItemid();
		
		// DPE hack to use as Module
		$app->enqueueMessage($msg, 'success');

		$currentUrl = $app->input->get('currentUrl', '', 'STRING');

		if($app->input->get('fromModule', '', 'INT'))
		{	$currentUrl = trim($currentUrl);
			$app->redirect($currentUrl);
		}else
		{
			$app->redirect(Route::_('index.php?option=com_jmailalerts&view=emails&Item_id=' . $itemid, false));	
		}
		//end
		
	}

	/**
	 * Get name and email of the user to store it in db
	 *
	 * @param   int  $userId  User id
	 *
	 * @return  array
	 */
	public function getname_email($userId)
	{
		$db    = Factory::getDBO();
		$query = $db->getQuery(true);
		$query->select($db->qn(['username', 'email']))
			->from($db->qn('#__users'))
			->where($db->qn('id') . ' = ' . (int) $userId);
		$db->setQuery($query);

		return $db->loadAssoc();
	}

	/**
	 * Get Itemid
	 *
	 * @return  integer
	 *
	 */
	public function getItemid()
	{
		$db = $this->_db;
		$query = $db->getQuery(true);
		$query->select($db->qn('id'))
			->from($db->qn('#__menu'))
			->where($db->qn('link') . ' LIKE ' . $db->Quote('%com_jmailalerts&view=emails%'))
			->where($db->qn('published') . ' = 1');

		$db->setQuery($query);

		return $db->loadResult();
	}

	/**
	 * Function to send mails
	 * Since this function sends emails, theres a logging code added to log the info abt email sending
	 * i.e. timestamp, recipient-info, failure/success of email sending.
	 *
	 * @return  void
	 */
	public function processMailAlerts()
	{
		$params = ComponentHelper::getParams('com_jmailalerts');

		// Flag to  check whether data must be logged or not.
		$addLog = $params['enable_logging'];

		// @require(JPATH_SITE . DS . "components" . DS . "com_jmailalerts" . DS . "emails" . DS . "config.php");

		$input = Factory::getApplication()->input;

		// $numberofmails = $params->get('inviter_percent');
		// $enableBatch = $params->get('enb_batch');

		$pkey  = $input->get('pkey', '', 'STRING');
		$today = Factory::getDate()->Format('Y-m-d H:i:s');

		$this->log[] = '';
		$this->log[] = Text::sprintf("COM_JMAILALERTS_START", $today);

		if ($pkey != $params->get('private_key_cronjob'))
		{
			$this->log[] = Text::_("COM_JMAILALERTS_NOT_AUTHO");
		}
		else
		{
			// $msgBody = stripslashes($params->get('message_body'));
			$skipTags = array(
				'[SITENAME]',
				'[NAME]',
				'[SITELINK]',
				'[PREFRENCES]',
				'[mailuser]'
			);

			// Get all tags  from the template with whitespace as it is
			// $rememberTags=$this->get_original_tmpl_tags($msgBody,$skipTags);

			// Get all tags  from the template removing whitespace in a correct needed array format
			// $tmplTags=$this->get_tmpl_tags($msgBody,$skipTags);

			// $batchSizeIfEnable = 10;

			// Get all alerts
			$allPublishedAlerts = $this->get_all_alertid();

			foreach ($allPublishedAlerts as $key)
			{
				// Calculate all user as per alerts.
				$emailUsers = array();

				// Get batch size of current alert if batch size is enable..
				// @toDo calculate batch size

				$this->log[]  = Text::sprintf("COM_JMAILALERTS_ALERT_MSG", $key);
				$enableBatch = $this->get_batch_size($key);
				$this->log[]  = Text::sprintf("COM_JMAILALERTS_ENABLE_BATCH", $key, $enableBatch);

				// $enableBatch = 2;

				// Get all the block users
				$query = "SELECT id FROM #__users WHERE block = 1";
				$this->_db->setQuery($query);
				$blockUsers = $this->_db->loadColumn();

				$blockUsersArray = implode(',', $blockUsers);

				$whr = '';

				if ($blockUsersArray)
				{
					$whr = "  AND user_id NOT IN (" . $blockUsersArray . ") ";
				}

				$query = "SELECT e.id as subscriber_id, e.user_id, e.name, e.email_id, e.date, e.plugins_subscribed_to, e.alert_id, e.frequency,
				 a.template, a.template_css, a.email_subject, a.respect_last_email_date
				 FROM #__jma_subscribers AS e ,#__jma_alerts as a
				 WHERE e.alert_id = a.id
				 AND e.frequency > 0
				 AND a.state=1
				 AND e.state=1
				 AND e.alert_id=" . $key . "
				 " . $whr;

				$this->_db->setQuery($query);
				$emailEligibleUsers         = $this->_db->loadObjectList();
				$emailUsersWithoutBatchSize = array();

				foreach ($emailEligibleUsers as $keyUser)
				{
					// Get frequency time in min
					$toTime       = strtotime($today);
					$fromTime     = strtotime($keyUser->date);
					$minDiffrence = round(abs($toTime - $fromTime) / 60, 2);

					$getRequiredMinutes = $this->getrequired_minute($keyUser->frequency);

					if ($minDiffrence >= $getRequiredMinutes)
					{
						$emailUsersWithoutBatchSize[] = $keyUser;
					}
				}

				if ($emailUsersWithoutBatchSize)
				{
					if ($enableBatch)
					{
						// @$emailUsers[] = array_slice($emailUsersWithoutBatchSize, 0, $enableBatch);
						$i = 0;

						for ($i = 0; $i < $enableBatch && $i < count($emailUsersWithoutBatchSize); $i++)
						{
							$emailUsers[] = $emailUsersWithoutBatchSize[$i];
						}
					}
					else
					{
						foreach ($emailUsersWithoutBatchSize as $key)
						{
							$emailUsers[] = $key;
						}
					}
				}

				if ($emailUsers)
				{
					$userCount   = count($emailUsers);
					$this->log[] = Text::sprintf("COM_JMAILALERTS_FOUND_TO_PRO", $userCount);

					echo implode('<br/>', $this->log);

					// Log details
					if ($addLog)
					{
						$this->storeLog($this->log);
					}

					unset($this->log);
					$this->log[] = '';

					$sendMail   = 0;
					$sendNoMail = 0;

					foreach ($emailUsers as $emailUser)
					{
						$userData = Factory::getUser($emailUser->user_id);

						if (isset($userData->params) && !empty($userData->params))
						{
							// This is getting used later to get user's language
							$emailUser->params = $userData->params;
						}

						$rememberTags = $this->get_original_tmpl_tags($emailUser->template, $skipTags);
						$tmplTags     = $this->get_tmpl_tags($emailUser->template, $skipTags);

						$jmailalertsemailhelper = new jmailalertsemailhelper;
						$returnVal              = $jmailalertsemailhelper->getMailcontent($emailUser, 2, $tmplTags, $rememberTags);

						// Explode the array to get the return value as now the return also contain the lof file
						if (isset($returnVal[0]))
						{
							$log = $returnVal[0];

							foreach ($log as $key)
							{
								$this->log[] = $key;
							}
						}

						$val = '';

						if (isset($returnVal[1]))
						{
							$val = $returnVal[1];
						}

						if ($val == 1)
						{
							$sendMail++;
						}
						elseif ($val == 3)
						{
							$this->log[] = Text::sprintf("COM_JMAILALERTS_MAIL_SEND_FAIL", $emailUser->name, $emailUser->user_id);

							$sendNoMail++;
						}
					}

					$this->log[] = Text::sprintf("COM_JMAILALERTS_PRO_OUT_OF", $sendMail, $userCount);

					// @echo Text::sprintf("COM_JMAILALERTS_PRO_OUT_OF", $sendMail, $userCount);
				}
				else
				{
					$this->log[] = Text::_("COM_JMAILALERTS_NO_USER");

					// @echo Text::_("COM_JMAILALERTS_NO_USER");
				}

				$this->log[] = Text::_("COM_JMAILALERTS_FINSH");

				// @echo Text::_("COM_JMAILALERTS_FINSH");
			}

			// Foreach alert.....
		}

		echo implode('<br/>', $this->log);

		if ($addLog)
		{
			$this->storeLog($this->log);
		}
	}

	/**
	 * Store log
	 *
	 * @param   array  $logData  data.
	 *
	 * @since   1.0
	 * @return  list.
	 */
	public function storeLog($logData)
	{
		$jConfig        = Factory::getConfig();
		$logPath        = $jConfig->get('log_path');
		$logFilePath    = $logPath . '/jmailalerts.php';

		$params         = ComponentHelper::getParams('com_jmailalerts');
		$maxLogFileSize = (int) $params->get('log_file_size', 10);

		// 'MB' => 1024 * 1024,
		$maxLogFileSize = $maxLogFileSize * 1024 * 1024;

		JLoader::import('joomla.filesystem.file');

		// Code for if log file exceeds certain size
		if (File::exists($logFilePath))
		{
			// If file size exceeds, rotate file
			if (filesize($logFilePath) > $maxLogFileSize)
			{
				$tempLogFile = $logPath . '/jmailalerts-log-1.php';

				if (File::exists($tempLogFile))
				{
					File::delete($tempLogFile);
				}

				File::copy($logFilePath, $tempLogFile);
				File::delete($logFilePath);
			}
		}

		$config = array(
			'text_file' => 'jmailalerts.php'
		);

		$logger = new FormattedtextLogger($config);

		$finalLogText = implode("\n", $logData);
		$finalLogText .= "\n";

		// FinalLogText is a string
		// $status can be Log::INFO, Log::WARNING, Log::ERROR, Log::ALL, Log::EMERGENCY or Log::CRITICAL
		$entry = new LogEntry($finalLogText, Log::INFO);

		$logger->addEntry($entry);
	}

	/**
	 * Get the batch size of all the alert ids.
	 * Function returns the value of the batch size if the batch size is enable.
	 *
	 * @param   int  $alertid  Alert id
	 *
	 * @return  integer
	 */
	public function get_batch_size($alertid)
	{
		$db = $this->_db;
		$query = $db->getQuery(true);
		$query->select($db->qn('batch_size'))
			->from($db->qn('#__jma_alerts'))
			->where($db->qn('enable_batch') . ' = 1')
			->where($db->qn('id') . ' = ' . (int) $alertid);

		$db->setQuery($query);
		$enableBatch = $db->loadResult();

		return $enableBatch;
	}

	/**
	 * Calculate time diffrence in minute
	 *
	 * @param   int  $frequencyId  [description]
	 *
	 * @return  integer
	 */
	public function getrequired_minute($frequencyId)
	{
		$db = $this->_db;
		$query = $db->getQuery(true);
		$query->select($db->qn(['id', 'time_measure', 'duration', 'name']))
			->from($db->qn('#__jma_frequencies'));
		$db->setQuery($query);
		$frequency = $db->loadObjectList();

		foreach ($frequency as $keyFreq => $valueFreq)
		{
			if ($valueFreq->time_measure == 'days')
			{
				$frquencyInMin[$valueFreq->id] = $valueFreq->duration * 24 * 60;
			}
			elseif ($valueFreq->time_measure == 'hours')
			{
				$frquencyInMin[$valueFreq->id] = $valueFreq->duration * 60;
			}
			elseif ($valueFreq->time_measure == 'minutes')
			{
				$frquencyInMin[$valueFreq->id] = $valueFreq->duration;
			}
		}

		if (array_key_exists($frequencyId, $frquencyInMin))
		{
			return $frquencyInMin[$frequencyId];
		}

		// @return
	}

	/**
	 * Function to call plugins with array of type [param_name]=>param_value and return the output
	 * This function is called from the get_data() function above
	 * [gettriggerPlugins description]
	 *
	 * @param   int     $userid                 User id
	 * @param   string  $lastEmailDate          Last email date
	 * @param   array   $finalPluginParamsData  Params date
	 * @param   string  $latest                 Latest
	 *
	 * @return  array
	 */
	public function gettriggerPlugins($userid, $lastEmailDate, $finalPluginParamsData, $latest)
	{
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

		$results        = array();
		$i              = 0;
		$specialPlugins = array();
		$count          = 0;

		// Important
		$flag = 0;

		$aresults = array();

		foreach ($finalPluginParamsData as $plug)
		{
			// Check if plugin is to be triggered
			if (isset($plug['plug_trigger']))
			{
				// Check if pluign is special
				if (isset($plug['is_special']))
				{
					// If yes add it in new array to process after proceessing normal plugins
					$specialPlugins[$count] = $plug;
					$count++;
				}
				// Normal plugin
				else
				{
					$plug['plug_trigger'];
					PluginHelper::importPlugin('emailalerts', $plug['plug_trigger']);

					// Triger the plugins
					// Parameters passed are userid,last email date,final plugin trigger data,fetch only latest
					$results = Factory::getApplication()->triggerEvent(
						'onEmail_' . $plug['plug_trigger'],
						array(
							$userid,
							$lastEmailDate,
							$plug,
							$latest
						)
					);

					if ($results)
					{
						if (!$flag && $results[0][1] != '')
						{
							// Set flag even if a result is outputted by any of the normal plugin
							$flag = 1;
						}

						$results[0][] = $plug['tag_to_replace'];
						$aresults[$i] = $results[0];
						$i++;
					}
				}
			}
		}

		// If content is outputted by normal plugins
		if ($flag)
		{
			foreach ($specialPlugins as $plug)
			{
				if (isset($plug['plug_trigger']))
				{
					PluginHelper::importPlugin('emailalerts', $plug['plug_trigger']);

					// Triger the plugins
					// Parameters passed are userid,last email date,final plugin trigger data,fetch only latest
					$plug['plug_trigger'];

					$results = Factory::getApplication()->triggerEvent(
						'onEmail_' . $plug['plug_trigger'],
						array(
							$userid,
							$lastEmailDate,
							$plug,
							$latest
						)
					);

					if ($results)
					{
						$results[0][] = $plug['tag_to_replace'];
						$aresults[$i] = $results[0];
						$i++;
					}
				}
			}
		}

		return $aresults;
	}

	/**
	 * Function to get the default alert user selected alerts or default alerts
	 *
	 * @return  array
	 */
	public function getdefaultalertid()
	{
		$db = $this->_db;
		$query = $db->getQuery(true);
		$query->select($db->qn(['alert_id', 'frequency', 'state']))
			->from($db->qn('#__jma_subscribers'))
			->where($db->qn('user_id') . ' = ' . (int) $user->id)
			->where($db->qn('frequency') . ' > 0');

		$db->setQuery($query);
		$tempData = $db->loadObjectList();

		$option = array();

		foreach ($tempData as $td)
		{
			$opt['frequency']      = $td->frequency;
			$opt['state']          = $td->state;
			$option[$td->alert_id] = $opt;
		}

		if (!$option)
		{
			// Get the frequency from default configuration
			// $query="SELECT alert_id  FROM #__jma_subscribers_Default";

			$query = "SELECT id  FROM #__jma_alerts WHERE is_default = 1 AND state=1 ";
			$this->_db->setQuery($query);
			$tempData = $this->_db->loadColumn();

			// $tempData = explode(',',$tempData);

			$option    = array();

			foreach ($tempData as $td)
			{
				$opt['frequency'] = 0;
				$option[$td]      = $opt;
			}
		}

		return $option;

		// O/p

		// Array ( [2] => Array ( [option] => 0 ) [3] => Array ( [option] => 0 ) ) in site model jma

		// Array ( [2] => Array ( [option] => 0 ) [3] => Array ( [option] => 0 )
		// [4] => Array ( [option] => 0 ) [5] => Array ( [option] => 0 ) ) in site model jma
	}

	/**
	 * Function for checking user default alert id or not
	 *
	 * @return  integer|string
	 */
	public function isdefaultset()
	{
		$user  = Factory::getUser();
		$db = $this->_db;
		$query = $db->getQuery(true);
		$query->select($db->qn('alert_id'))
			->from($db->qn('#__jma_subscribers'))
			->where($db->qn('user_id') . ' = ' . (int) $user->id);

		$db->setQuery($query);
		$option = $db->loadColumn();

		if (!$option)
		{
			$defaultSetting = 1;
		}
		else
		{
			$defaultSetting = "";
		}

		return $defaultSetting;
	}

	/**
	 * Function for retun all alert id
	 *
	 * @return  array
	 */
	public function get_all_alertid()
	{
		$db = $this->_db;
		$query = $db->getQuery(true);
		$query->select($db->qn('id'))
			->from($db->qn('#__jma_alerts'))
			->where($db->qn('state') . ' = 1');

		$db->setQuery($query);

		return $db->loadColumn();
	}

	/**
	 * Function for retun no of alerts
	 *
	 * @return  integer
	 */
	public function gettotalalertcount()
	{
		$db = $this->_db;
		$query = $db->getQuery(true);
		$query->select('COUNT(*)')
			->from($db->qn('#__jma_alerts'))
			->where($db->qn('state') . ' = 1');
		$db->setQuery($query);

		return $db->loadResult();
	}

	/**
	 * Function for return query concat
	 *
	 * @return  array
	 */
	public function alertqryconcat()
	{
		$db = $this->_db;
		// Get alerts
		$query = $db->getQuery(true);
		$query->select($db->qn('template'))
			->from($db->qn('#__jma_alerts'))
			->where($db->qn('state') . ' = 1');

		$db->setQuery($query);
		$test2 = $db->loadObjectList();

		// Get the plugin names and store in an array
		$query = $db->getQuery(true);
		$query->select($db->qn('element'))
			->from($db->qn('#__extensions'))
			->where($db->qn('folder') . ' = ' . $db->Quote('emailalerts'))
			->where($db->qn('enabled') . ' = 1');

		$db->setQuery($query);
		$plugnamecompair = $db->loadColumn();

		$qryConcat = array();

		if ($test2)
		{
			foreach ($test2 as $key)
			{
				$pluginNameString = array();

				for ($i = 0; $i < count($plugnamecompair); $i++)
				{
					if (strstr($key->template, $plugnamecompair[$i]))
					{
						$pluginNameString[] = $plugnamecompair[$i];
					}
				}

				$tmp = "";

				for ($i = 0; $i < count($pluginNameString); $i++)
				{
					$tmp .= " element LIKE '" . $pluginNameString[$i] . "' ";

					if ($i != (count($pluginNameString) - 1))
					{
						$tmp .= " OR ";
					}
				}

				$qryConcat[] = $tmp;
				unset($pluginNameString);
			}
		}

		return $qryConcat;
	}

	/**
	 * Get data
	 *
	 * @param   int  $aid  Alert id
	 *
	 * @return  array
	 */
	public function getData($aid)
	{
		$option = array();
		$user   = Factory::getUser();

		// Iif ($user->id) {
		// Get the option saved related to the user-id from the email_alert table
		$where  = '';

		if ($user->id)
		{
			$where = ' AND user_id =' . $user->id;
		}

		if ($aid != "")
		{
			$db = $this->_db;
			$query = $db->getQuery(true);
			$query->select($db->qn(['frequency', 'plugins_subscribed_to']))
				->from($db->qn('#__jma_subscribers'))
				->where($db->qn('alert_id') . ' = ' . (int) $aid);

			if ($user->id)
			{
				$query->where($db->qn('user_id') . ' = ' . (int) $user->id);
			}

			$db->setQuery($query);
			$option = $db->loadRow();
		}

		if (!empty($option[1]))
		{
			// @TODO check function call
			$opt = $this->get_frontend_plugin_data($option[1]);

			if ($opt)
			{
				foreach ($opt as $kk => $vv)
				{
					foreach ($vv as $k => $v)
					{
						$opt1[$kk][] = $k . '=' . $v;
					}
				}
			}

			if (isset($opt1))
			{
				$option[1] = $opt1;
			}
		}

		if (!$option)
		{
			// Just installed and not yet synced
			// @TODO needs test , chk function call
			$opt = $this->get_frontend_plugin_data("");

			// $opt=$this->getUserPlugData($option[1]);

			foreach ($opt as $kk => $vv)
			{
				foreach ($vv as $k => $v)
				{
					if ($kk == 'jma_latestnews_js' && $k == 'category')
					{
						$k = 'catid';
					}

					$opt1[$kk][] = $k . '=' . $v;
				}
			}

			if (isset($opt1))
			{
				$option[1] = $opt1;
			}
		}

		return $option[1];

		// }

		// If ends
	}

	/**
	 * Function will return the user settings for the plugins in format of plugin_name {[para_name1]=para_value1,[para_name2]=para_value2,..}
	 * Function is called from the function getData() and from getMailcontent()
	 *
	 * @param   string  $data  Data
	 *
	 * @return  array
	 */
	public function getUserPlugData($data)
	{
		$newlinePluginsArray = array();
		$newlinePluginsArray = explode("\n", $data);

		foreach ($newlinePluginsArray as $line)
		{
			if (!trim($line))
			{
				continue;
			}

			$pcs                               = explode('|', $line);
			$userconfig                        = explode('=', $pcs[1]);
			$userdata[$pcs[0]][$userconfig[0]] = $userconfig[1];
		}

		$i = 0;

		foreach ($userdata as $key => $u)
		{
			$u['plug_trigger'] = $key;
			$uPlugs[$i]       = $u;
			$i++;
		}

		return $uPlugs;
	}

	/**
	 * Function to get the inline css html code from the emogrifier
	 *
	 * @param   string  $html  Email HTML
	 * @param   string  $css   Email CSS
	 *
	 * @return  string
	 */
	public function getEmogrify($html, $css)
	{
		require_once JPATH_SITE . DS . "components" . DS . "com_jmailalerts" . DS . "models" . DS . "emogrifier.php";

		// Condition to check if mbstring is enabled
		if (!function_exists('mb_convert_encoding'))
		{
			echo Text::_("COM_JMAILALERTS_MB_EXT");

			return $html;
		}

		$emogr    = new TJEmogrifier($html, $css);
		$htmlCss = $emogr->emogrify();

		return $htmlCss;
	}

	/**
	 * Function to get the plugin data(names, elements) related to emailalert
	 *
	 * @param   string  $qryConcat  Query part to be concated
	 *
	 * @return  object|boolean
	 */
	public function getPluginData($qryConcat)
	{
		$db = $this->_db;
		$query = $db->getQuery(true);
		$query->select($db->qn(['name', 'element', 'params']))
			->from($db->qn('#__extensions'))
			->where($db->qn('folder') . ' = ' . $db->Quote('emailalerts'))
			->where($db->qn('enabled') . ' = 1');

		if ($qryConcat != "")
		{
			$query->where("(" . $qryConcat . ")");
		}

		$db->setQuery($query);

		return $db->loadObjectList();
	}

	/*
	 *
	 * ///////////////////////////////////////////////////////
	 * All fuctions below are added in 2.4 version
	 * ///////////////////////////////////////////////////////
	 *
	 */

	/**
	 * Get default plugin params j15
	 *
	 * @param   string  $pluginName  Plugin name
	 *
	 * @return  array
	 */
	public function get_default_plugin_params_j15($pluginName)
	{
		$plugin = PluginHelper::getPlugin('emailalerts', $pluginName);

		if (!$plugin)
		{
			return false;
		}

		$pluginParams = new Registry;
		$pluginParams->loadString($plugin->params);

		$pluginParamsDefault = $pluginParams;
		$newlin              = explode("\n", $pluginParamsDefault);
		$defaultPluginParams = array();

		foreach ($newlin as $v)
		{
			if (!empty($v))
			{
				$v = str_replace('|', ',', $v);
				$v = explode("=", $v);

				if (isset($v[1]))
				{
					$defaultPluginParams[$v[0]] = $v[1];
				}
			}
		}

		return $defaultPluginParams;
	}

	/**
	 * Get default plugin_params j16
	 *
	 * @param   string  $pluginName  Plugin name
	 *
	 * @return  array
	 */
	public function get_default_plugin_params_j16($pluginName)
	{
		$db = $this->_db;
		$query = $db->getQuery(true);
		$query->select($db->qn('params'))
			->from($db->qn('#__extensions'))
			->where($db->qn('element') . ' = ' . $db->Quote($pluginName))
			->where($db->qn('folder') . ' = ' . $db->Quote('emailalerts'))
			->where($db->qn('enabled') . ' = 1');

		$db->setQuery($query);
		$plugParams = $db->loadResult();
		$defaultPluginParams = array();

		if (!$plugParams)
		{
			return false;
		}

		if (preg_match_all('/\[(.*?)\]/', $plugParams, $match))
		{
			foreach ($match[1] as $mat)
			{
				$match       = str_replace(',', '|', $mat);
				$plugParams = str_replace($mat, $match, $plugParams);
			}
		}

		$newlin = explode(",", $plugParams);

		foreach ($newlin as $v)
		{
			// $entry = "";

			if (!empty($v))
			{
				$v = str_replace('{', '', $v);
				$v = str_replace(':', '=', $v);
				$v = str_replace('"', '', $v);
				$v = str_replace('}', '', $v);
				$v = str_replace('[', '', $v);
				$v = str_replace(']', '', $v);
				$v = str_replace('|', ',', $v);

				$v = explode("=", $v);

				if (isset($v[1]))
				{
					$defaultPluginParams[$v[0]] = $v[1];
				}
			}
		}

		return $defaultPluginParams;
	}

	/**
	 * This functions returns an array of all tags from the JMA email-template with whitespace as it is
	 * For example it can detect all tags like [jma_plugin_js|cat=1,2 | sec=5,  6, 8] or [jma_plugin_js|cat=1,2]
	 *
	 * @param   string  $data  A string having user preferences as stored in email_alert table
	 *
	 * @return array $finalFrontendUserdata an array of user preferences as per his ACL for enabled plugins
	 */
	public function get_frontend_plugin_data($data)
	{
		$userdata              = array();
		$newlinePluginsArray = array();
		$newlinePluginsArray = explode("\n", $data);

		$newlinePluginsArray;

		foreach ($newlinePluginsArray as $line)
		{
			if (!trim($line))
			{
				continue;
			}

			$pcs                               = explode('|', $line);
			$userconfig                        = explode('=', $pcs[1]);
			$userdata[$pcs[0]][$userconfig[0]] = $userconfig[1];
		}

		// @$user = Factory::getUser();

		// @TODO acl is remaining for 1.6 onwards
		$query = "SELECT element FROM #__extensions
			WHERE type='plugin' AND folder='emailalerts' AND enabled=1";

		// AND access <=".(int)$access;

		$this->_db->setQuery($query);
		$data = $this->_db->loadObjectList();

		$tempData               = array();
		$finalFrontendUserdata = array();

		if ($data)
		{
			foreach ($data as $d)
			{
				if ($userdata)
				{
					if (!array_key_exists($d->element, $userdata))
					{
						$p            = $this->get_default_plugin_params_j16($d->element);
						$p['checked'] = "''";

						if (!(isset($p['is_special']) && $p['is_special']))
						{
							$tempData[$d->element] = $p;
						}
					}

					// End if array_key_exists
					else
					{
						// If key exists
						$p = $this->get_default_plugin_params_j16($d->element);

						if (!(isset($p['is_special']) && $p['is_special']))
						{
							$tempData[$d->element] = $userdata[$d->element];
						}
					}
				}

				// End of userdara
				// If not userdata
				else
				{
					// @TODO needs testing
					$p            = $this->get_default_plugin_params_j16($d->element);
					$p['checked'] = "''";

					if (!(isset($p['is_special']) && $p['is_special']))
					{
						$tempData[$d->element] = $p;
					}
				}

				// End else if no userdata
			}

			// Code below is added to show plugins at frontend according to ACL of corresponding user

			// $data has appropriate plugin names as per users' ACL
			// $finalFrontendUserdata=array();
			foreach ($data as $d)
			{
				if (array_key_exists($d->element, $tempData))
				{
					$finalFrontendUserdata[$d->element] = $tempData[$d->element];
				}
			}
		}

		return $finalFrontendUserdata;
	}

	/**
	 * This functions returns an array of all tags from the JMA email-template
	 * with whitespace as it is
	 * For example it can detect all tags like [jma_plug |cat=1,2| sec=5,  6, 8]
	 * or [jma_plugin_js|cat=1,2]
	 *
	 * @param   string  $msgBody   This is a JMA email template string
	 * @param   array   $skipTags  This array contains all tags that are not related to any JMA plugin
	 *
	 * @return array $rememberTags an array of all detected tags
	 */
	public function get_original_tmpl_tags($msgBody, $skipTags)
	{
		// Pattern for finding all tags with pipe EXAMPLE [jma_plug |cat=1,2| sec=5,  6, 8]
		$pattern = '/\[[A-Za-z_|=, ][A-Za-z_|=0-9, ]*\]/';
		preg_match_all($pattern, $msgBody, $matches);
		$count         = 0;
		$rememberTags = array();

		foreach ($matches[0] as $match)
		{
			if (!in_array($match, $skipTags))
			{
				$rememberTags[$count] = $match;
				$count++;
			}
		}

		return $rememberTags;
	}

	/**
	 * This functions returns an array of all tags from the JMA email-template
	 * removing whitespace from tag names
	 * For example it can detect all tags like [jma_plugin_js|cat=1,2 | sec=5,  6, 8]
	 * or [jma_plugin_js|cat=1,2]
	 *
	 * @param   string  $msgBody   This is a JMA email template string
	 * @param   array   $skipTags  This array contains all tags that are not related to any JMA plugin
	 *
	 * @return array $finalTmplTags an array of all detected tags
	 */
	public function get_tmpl_tags($msgBody, $skipTags)
	{
		// Pattern find all tags with pipe EXAMPLE [jma_plug |cat=1,2| sec=5,  6, 8]
		$pattern = '/\[[A-Za-z_|=, ][A-Za-z_|=0-9, ]*\]/';
		preg_match_all($pattern, $msgBody, $matches);
		$tmplTags[] = array();
		$count       = 0;

		foreach ($matches[0] as $match)
		{
			// Remove whitespace from a tag name
			$tag = preg_replace('/\s+/', '', $match);

			if (!in_array($match, $skipTags))
			{
				$tmplTags[$count] = $tag;
				$count++;
			}
		}

		$tagsCounter = 0;
		$finalTmplTags = array();

		foreach ($tmplTags as $tmplTag)
		{
			// Important
			$tagToReplace = $tmplTag;

			// Remove square brackets [] from tags like [jma_news|count=6]
			$tmplTag = preg_replace('/(\[)|(\])/', '', $tmplTag);

			// Create array from strings like jma_news|count=6|sec=1,3,4
			$tag = is_string($tmplTag) ? explode('|', $tmplTag) : array();

			// It's a data tag
			if (count($tag) > 1)
			{
				// The first(actually 0th) element of array is the name of the plugin
				// We need to make an array with first element as plugin name AND paramaters as other array elements
				$tempParams = array();

				// Start processing all params for a single plugin
				for ($count = 0; $count < count($tag); $count++)
				{
					// Create array from strings like catid=1,2,3
					$singleParamArray = explode('=', $tag[$count]);

					// @TODO this for is unused

					// @for($ic=1;$ic<count($tag);$ic++) //$ic count is used to process $singleParamArray
					// {

					// Example catid=1,2,3
					if (count($singleParamArray) > 1)
					{
						$tempParams[$tagsCounter][$singleParamArray[0]] = $singleParamArray[1];
					}
					// Example jma_latest_news
					else
					{
						$tempParams[$tagsCounter]['plug_trigger'] = $singleParamArray[0];
					}

					// }
				}

				// End of proceessing all params for a single tag

				$tempParams[$tagsCounter]['tag_to_replace'] = $tagToReplace;
				$finalTmplTags[$tagsCounter]               = $tempParams[$tagsCounter];
			}
			// End of if it is a data tag
			// It is a normal tag
			else
			{
				$tempParams = array();

				for ($count = 0; $count < count($tag); $count++)
				{
					// Create array from strings like count=6
					$singleParamArray = explode('=', $tag[$count]);

					// @TODO this for is unused

					// @for($ic=0;$ic<count($tag);$ic++)
					// {

					// Example catid=1,2,3
					if (count($singleParamArray) > 1)
					{
						$tempParams[$tagsCounter][$singleParamArray[0]] = $singleParamArray[1];
					}
					// Example jma_latest_news
					else
					{
						$tempParams[$tagsCounter]['plug_trigger'] = $singleParamArray[0];
					}

					// }
				}

				$tempParams[$tagsCounter]['tag_to_replace'] = $tagToReplace;
				$finalTmplTags[$tagsCounter]               = $tempParams[$tagsCounter];
			}

			$tagsCounter++;
		}

		// End of foreach

		return $finalTmplTags;
	}

	/**
	 * This functions returns an array of all tags each corresponding to one JMA plugin trigger
	 *
	 * @param   array  $tmplTags  It is an array having all tags from email template along with corresponding paramters
	 * @param   array  $userPlug  It is an array having all tags from user preferences along with corresponding paramters
	 * @param   int    $uid       The user id for user to whom mail will be sent. Needed for ACL
	 *
	 * @return array $finalTriggerTags array of all tags each corresponding to one JMA plugin trigger
	 */
	public function get_final_trigger_tags($tmplTags, $userPlug, $uid)
	{
		$jmailalertsModelEmails = new jmailalertsModelEmails;

		// @TODO aid is remaining for 1.6
		$db = $this->_db;
		$query = $db->getQuery(true);
		$query->select($db->qn('element'))
			->from($db->qn('#__extensions'))
			->where($db->qn('folder') . ' = ' . $db->Quote('emailalerts'))
			->where($db->qn('enabled') . ' = 1');

		$db->setQuery($query);
		$enabledPlugins = $db->loadColumn();

		$i = 0;

		foreach ($tmplTags as $tt)
		{
			// Actual plugin name
			if (isset($tt['plug_trigger']))
			{
				$tags[$i][0] = $tt['plug_trigger'];
			}
			else
			{
				$tags[$i][0] = '';
			}

			// Actual tag/data tag  in email template.
			// This is needed when replacing tags in email with data outputed by corresponding plugin
			$tags[$i][1] = $tt['tag_to_replace'];

			$i++;
		}

		$finalTriggerTags = array();
		$tagsCounter      = 0;

		foreach ($tags as $tag)
		{
			// If plugin is enabled
			if (in_array($tag[0], $enabledPlugins))
			{
				// This foreach is needed
				// Because user preferences array will be having only one instance of a one plugin

				// But in template we may use same tag 3-4 times as a data tag
				// So we need to process each data tag against all user plugins(actually matching corresponding plugin)
				foreach ($userPlug as $u)
				{
					if ($tag[0] == $u['plug_trigger'])
					{
						$singlePluginParams = $jmailalertsModelEmails->get_single_plugin_params($tmplTags[$tagsCounter], $u);

						$finalTriggerTags[$tagsCounter] = $singlePluginParams;
					}
					elseif (isset($tmplTags[$tag[0]]) && isset($userPlug[$tag[0]]))
					{
						// @TODO - fix later Undefined variable '$singlePluginParams'.
						$finalTriggerTags[$tagsCounter] = $singlePluginParams;
					}
				}
			}

			$finalTriggerTags[$tagsCounter]['tag_to_replace'] = $tag[1];

			if (isset($finalTriggerTags[$tagsCounter]['tag_to_replace']) && !isset($finalTriggerTags[$tagsCounter]['plug_trigger']))
			{
				$dp = $jmailalertsModelEmails->get_default_plugin_params_j16($tag[0]);

				if (isset($dp['is_special']) && ($dp['is_special']))
				{
					$singlePluginParams                               = $jmailalertsModelEmails->get_single_plugin_params($tmplTags[$tagsCounter], $dp);
					$finalTriggerTags[$tagsCounter]                   = $singlePluginParams;
					$finalTriggerTags[$tagsCounter]['plug_trigger']   = $tag[0];
					$finalTriggerTags[$tagsCounter]['tag_to_replace'] = $tag[1];
				}
			}

			$tagsCounter++;
		}

		return $finalTriggerTags;
	}

	/**
	 * Compare a single "template tag array" with corresponding "user tag array"
	 * and return a new "tag array" preserving all array indices(actually plugin parameters)
	 * for both arrays
	 * See example given below
	 *
	 * @param   array  $tmplTag  Template tags
	 * @param   array  $userTag  User tags
	 *
	 * @return array $newFinalTags
	 */
	public function get_single_plugin_params($tmplTag, $userTag)
	{
		$jmailalertsModelEmails = new jmailalertsModelEmails;

		if (!isset($userTag))
		{
			$userTag = array();
		}

		$newFinalTags = array();
		$merged       = array_merge($tmplTag, $userTag);

		// Get all parameter names
		$params        = array_keys($merged);

		// Process each parameter
		foreach ($params as $param)
		{
			// If a parameter is specified in a template tag(i.e. data tag)v
			if (isset($tmplTag[$param]) && isset($userTag[$param]))
			{
				$p                     = $jmailalertsModelEmails->get_single_param($tmplTag[$param], $userTag[$param]);
				$newFinalTags[$param] = $p;

				if ($p)
				{
					// If common values found
					$newFinalTags[$param] = $p;
				}
				else
				{
					// If nothing is common , respect template parameter
					// @TODO might need to check
					$newFinalTags[$param] = $tmplTag[$param];
				}

				// @TODO important to preseve count preference set by user.
				// Need to remove this option from user preferences for every plugin
				if ($param == 'no_of_users' || $param == 'count')
				{
					$newFinalTags[$param] = $userTag[$param];
				}
			}
			// Preserve paramters not specified in data tags but are there in user preferences
			else
			{
				if (isset($userTag[$param]))
				{
					$newFinalTags[$param] = $userTag[$param];
				}
			}
		}

		return $newFinalTags;
	}

	/**
	 * Compares "template tag-paramter value" and "user tag-paramater value"
	 * and returns common values(intersection of both)
	 * For example $p1=1,3,5; $p2=1,2,3,4,6,7; it should return $p3=1,3;
	 *
	 * @param   string  $tmplTagParamValue  string like "1,3,5"
	 * @param   string  $userTagParamValue  string like "1,2,3,4,6,7"
	 *
	 * @return array $commonParamValue "1,3"
	 */
	public function get_single_param($tmplTagParamValue, $userTagParamValue)
	{
		$jmailalertsModelEmails = new jmailalertsModelEmails;
		$tmplParamVal     = $jmailalertsModelEmails->get_exploded($tmplTagParamValue);
		$userParamVal     = $jmailalertsModelEmails->get_exploded($userTagParamValue);
		$commonParamValue = array_intersect($tmplParamVal, $userParamVal);
		$commonParamValue = implode(",", $commonParamValue);

		return $commonParamValue;
	}

	/**
	 * converts "1,2,3" like strings into an array
	 *
	 * @param   string  $str  string like "1,2,3,4" or "1"
	 *
	 * @return array $pieces
	 */
	public function get_exploded($str)
	{
		$pieces = explode(",", $str);

		return $pieces;
	}

	/**
	 * Method to send OTP
	 *
	 * @return  boolean
	 */
	public function sendOtp()
	{
		$app   = Factory::getApplication();
		$email = $app->input->get('user_email', '', 'STRING');
		$name  = $app->input->get('user_name', '', 'STRING');
		$ip    = $_SERVER['REMOTE_ADDR'];

		if (empty($email))
		{
			echo json_encode(['status' => 'error', 'message' => Text::_('COM_JMAILALERTS_EMAIL_REQUIRED')]);
			$app->close();
		}

		if (!filter_var($email, FILTER_VALIDATE_EMAIL))
		{
			echo json_encode(['status' => 'error', 'message' => Text::_('COM_JMAILALERTS_INVALID_EMAIL')]);
			$app->close();
		}

		// Check IP Limit (max 3 requests per hour)
		$db    = $this->_db;
		$query = $db->getQuery(true);
		$now   = Factory::getDate()->toSql();
		$oneHourAgo = Factory::getDate('-1 hour')->toSql();

		$query->select('count(*)')
			->from($db->qn('#__jma_otp_subscribers'))
			->where($db->qn('ip_address') . ' = ' . $db->Quote($ip))
			->where($db->qn('created_at') . ' > ' . $db->Quote($oneHourAgo));

		$db->setQuery($query);
		$count = $db->loadResult();

		if ($count >= 3)
		{
			echo json_encode(['status' => 'error', 'message' => Text::_('COM_JMAILALERTS_IP_LIMIT_EXCEEDED')]);
			$app->close();
		}

		// Generate 6-digit OTP
		$otp = mt_rand(100000, 999999);
		$expiry = Factory::getDate('+10 minutes')->toSql();

		// Save to temporary table
		$query = $db->getQuery(true);
		$columns = ['email', 'otp_code', 'otp_expiry', 'ip_address', 'created_at', 'status'];
		$values = [$db->Quote($email), $db->Quote($otp), $db->Quote($expiry), $db->Quote($ip), $db->Quote($now), $db->Quote('pending')];

		$query->insert($db->qn('#__jma_otp_subscribers'))
			->columns($db->qn($columns))
			->values(implode(',', $values));

		$db->setQuery($query);
		$db->execute();

		// Send email using Tjnotifications
		$key = "NewsleterOtpMail";
		$options = new Registry;

		$replacements = new stdClass;
		$replacements->form = new stdClass;
		$replacements->form->otp = $otp;
		$replacements->form->name = $name;

		$recipients = array(
			'email' => array(
				'to' => array($email)
			)
		);
	
		try
		{

				$status = Tjnotifications::send("com_jmailalerts", $key, $recipients, $replacements, $options);
				if ($status)
				{
					echo json_encode(['status' => 'success', 'message' => Text::_('COM_JMAILALERTS_OTP_SENT_SUCCESS')]);
				}
				else
				{
					echo json_encode(['status' => 'error', 'message' => Text::_('COM_JMAILALERTS_OTP_SEND_FAILED')]);
				}
		
		}
		catch (Exception $e)
		{
			echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
		}

		$app->close();
	}

	/**
	 * Method to verify OTP via AJAX
	 *
	 * @return  void
	 */
	public function verifyOtpAjax()
	{
		$app   = Factory::getApplication();
		$email = $app->input->get('user_email', '', 'STRING');
		$otp   = $app->input->get('otp_code', '', 'STRING');

		$db    = $this->_db;
		$query = $db->getQuery(true);
		$now   = Factory::getDate()->toSql();

		$query->select('*')
			->from($db->qn('#__jma_otp_subscribers'))
			->where($db->qn('email') . ' = ' . $db->Quote($email))
			->where($db->qn('otp_code') . ' = ' . $db->Quote($otp))
			->where($db->qn('status') . ' = ' . $db->Quote('pending'))
			->where($db->qn('otp_expiry') . ' > ' . $db->Quote($now))
			->order($db->qn('created_at') . ' DESC');

		$db->setQuery($query, 0, 1);
		$result = $db->loadObject();

		if ($result)
		{
			// Update status to active
			$query = $db->getQuery(true);
			$query->update($db->qn('#__jma_otp_subscribers'))
				->set($db->qn('status') . ' = ' . $db->Quote('active'))
				->set($db->qn('verified_at') . ' = ' . $db->Quote($now))
				->where($db->qn('id') . ' = ' . (int) $result->id);

			$db->setQuery($query);
			$db->execute();

			echo json_encode(['status' => 'success', 'message' => Text::_('COM_JMAILALERTS_OTP_VERIFIED_SUCCESS')]);
		}
		else
		{
			echo json_encode(['status' => 'error', 'message' => Text::_('COM_JMAILALERTS_INVALID_OR_EXPIRED_OTP')]);
		}

		$app->close();
	}

	/**
	 * Method to cleanup old OTP records
	 *
	 * @return  void
	 */
	public function cleanupOtp()
	{
		$db    = $this->_db;
		$query = $db->getQuery(true);
		$now   = Factory::getDate()->toSql();

		// Delete all records that have passed their expiry time
		$query->delete($db->qn('#__jma_otp_subscribers'))
			->where($db->qn('otp_expiry') . ' < ' . $db->Quote($now));

		$db->setQuery($query);
		$db->execute();
	}
}
