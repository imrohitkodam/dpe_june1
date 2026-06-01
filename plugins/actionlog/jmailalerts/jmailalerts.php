<?php
/**
 * @package     JMailAlerts
 * @subpackage  Actionlog.jmailAlerts
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2022 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access.
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Table\Table;

JLoader::register('ActionlogsHelper', JPATH_ADMINISTRATOR . '/components/com_actionlogs/helpers/actionlogs.php');
Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_jmailalerts/tables');

/**
 * JMailAlerts Actions Logging Plugin.
 *
 * @since  2.6.1
 */
class PlgActionlogJmailalerts extends CMSPlugin
{
	/**
	 * Load plugin language file automatically so that it can be used inside component
	 *
	 * @var    boolean
	 * @since  2.6.1
	 */
	protected $autoloadLanguage = true;

	/**
	 * On saving frequency data logging method
	 *
	 * Method is called after frequency data is stored in the database.
	 *
	 * @param   string   $context  com_jmailalert.
	 * @param   Object   $table    Holds the frequency data.
	 * @param   boolean  $isNew    True if a new alert is stored.
	 *
	 * @return  void
	 *
	 * @since   2.6.1
	 */
	public function onAfterJmaFrequencySave($context, $table, $isNew)
	{
		if (!$this->params->get('logActionForFrequencySave', 1))
		{
			return;
		}

		$context = Factory::getApplication()->input->get('option');
		$jUser   = Factory::getUser();

		if ($isNew)
		{
			$messageLanguageKey = 'PLG_ACTIONLOG_JMAILALERTS_FREQUENCY_ADDED';
			$action             = 'add';
		}
		else
		{
			$messageLanguageKey = 'PLG_ACTIONLOG_JMAILALERTS_FREQUENCY_UPDATED';
			$action             = 'update';
		}

		$userId   = $jUser->id;
		$userName = $jUser->username;

		$message = array(
			'action'      => $action,
			'type'        => 'PLG_ACTIONLOG_JMAILALERTS_TYPE_FREQUENCY',
			'id'          => $table->id,
			'title'       => $table->name,
			'itemlink'    => 'index.php?option=com_jmailalerts&view=frequency&layout=edit&id=' . $table->id,
			'userid'      => $userId,
			'username'    => $userName,
			'accountlink' => 'index.php?option=com_users&task=user.edit&id=' . $userId,
		);

		$this->addLog(array($message), $messageLanguageKey, $context, $userId);
	}

	/**
	 * On after deleting frequency data logging method
	 *
	 * Method is called after frequency data is deleted from  the database.
	 *
	 * @param   string  $context  com_jmailalert.
	 * @param   Object  $table    Holds the frequency data.
	 *
	 * @return  void
	 *
	 * @since   2.6.1
	 */
	public function onAfterJmaFrequencyDelete($context, $table)
	{
		if (!$this->params->get('logActionForFrequencyDelete', 1))
		{
			return;
		}

		$context            = Factory::getApplication()->input->get('option');
		$jUser              = Factory::getUser();
		$messageLanguageKey = 'PLG_ACTIONLOG_JMAILALERTS_FREQUENCY_DELETED';
		$action             = 'delete';
		$userId             = $jUser->id;
		$userName           = $jUser->username;

		$message = array(
			'action'      => $action,
			'type'        => 'PLG_ACTIONLOG_JMAILALERTS_TYPE_FREQUENCY',
			'id'          => $table->id,
			'title'       => $table->name,
			'userid'      => $userId,
			'username'    => $userName,
			'accountlink' => 'index.php?option=com_users&task=user.edit&id=' . $userId,
		);

		$this->addLog(array($message), $messageLanguageKey, $context, $userId);
	}

	/**
	 * On saving alert data logging method
	 *
	 * Method is called after alert data is stored in the database.
	 *
	 * @param   string   $context  com_jmailalert.
	 * @param   Object   $table    Holds the alert data.
	 * @param   boolean  $isNew    True if a new alert is stored.
	 *
	 * @return  void
	 *
	 * @since   2.6.1
	 */
	public function onAfterJmaAlertSave($context, $table, $isNew)
	{
		if (!$this->params->get('logActionForAlertSave', 1))
		{
			return;
		}

		$context = Factory::getApplication()->input->get('option');
		$jUser   = Factory::getUser();

		if ($isNew)
		{
			$messageLanguageKey = 'PLG_ACTIONLOG_JMAILALERTS_ALERT_ADDED';
			$action             = 'add';
		}
		else
		{
			$messageLanguageKey = 'PLG_ACTIONLOG_JMAILALERTS_ALERT_UPDATED';
			$action             = 'update';
		}

		$userId   = $jUser->id;
		$userName = $jUser->username;

		$message = array(
			'action'      => $action,
			'type'        => 'PLG_ACTIONLOG_JMAILALERTS_TYPE_ALERT',
			'id'          => $table->id,
			'title'       => $table->title,
			'itemlink'    => 'index.php?option=com_jmailalerts&view=alert&layout=edit&id=' . $table->id,
			'userid'      => $userId,
			'username'    => $userName,
			'accountlink' => 'index.php?option=com_users&task=user.edit&id=' . $userId,
		);

		$this->addLog(array($message), $messageLanguageKey, $context, $userId);
	}

	/**
	 * On after deleting alert data logging method
	 *
	 * Method is called after alert data is deleted from  the database.
	 *
	 * @param   string  $context  com_jmailalert.
	 * @param   Object  $table    Holds the alert data.
	 *
	 * @return  void
	 *
	 * @since   2.6.1
	 */
	public function onAfterJmaAlertDelete($context, $table)
	{
		if (!$this->params->get('logActionForAlertDelete', 1))
		{
			return;
		}

		$context            = Factory::getApplication()->input->get('option');
		$jUser              = Factory::getUser();
		$messageLanguageKey = 'PLG_ACTIONLOG_JMAILALERTS_ALERT_DELETED';
		$action             = 'delete';
		$userId             = $jUser->id;
		$userName           = $jUser->username;

		$message = array(
			'action'      => $action,
			'type'        => 'PLG_ACTIONLOG_JMAILALERTS_TYPE_ALERT',
			'id'          => $table->id,
			'title'       => $table->title,
			'userid'      => $userId,
			'username'    => $userName,
			'accountlink' => 'index.php?option=com_users&task=user.edit&id=' . $userId,
		);

		$this->addLog(array($message), $messageLanguageKey, $context, $userId);
	}

	/**
	 * On saving subscription data logging method
	 *
	 * Method is called after subscription data is stored in the database.
	 *
	 * @param   array    $data   Holds the subscription data.
	 * @param   boolean  $isNew  True if a new subscription is stored.
	 *
	 * @return  void
	 *
	 * @since   2.6.1
	 */
	public function onAfterJmaAlertSubscriptionSave($data, $isNew)
	{
		if (!$this->params->get('logActionForSubscriptionSave', 1))
		{
			return;
		}

		$app                   = Factory::getApplication();
		$context               = $app->input->get('option');
		$jUser                 = Factory::getUser();
		$jmailalertsTablealert = Table::getInstance('alert', 'JmailalertsTable', array());
		$jmailalertsTablealert->load(array('id' => $data['alert_id']));

		if ($isNew)
		{
			$messageLanguageKey = ($app->isClient('administrator'))
				? 'PLG_ACTIONLOG_JMAILALERTS_USER_ADDED_SUBSCRIPTION_TO'
				: 'PLG_ACTIONLOG_JMAILALERTS_USER_SUBSCRIBED_TO';
			$action             = 'add';
		}
		else
		{
			$messageLanguageKey = ($app->isClient('administrator'))
				? 'PLG_ACTIONLOG_JMAILALERTS_USER_UPDATED_SUBSCRIPTION_FOR'
				: 'PLG_ACTIONLOG_JMAILALERTS_USER_UPDATED_SUBSCRIPTION';
			$action             = 'update';
		}

		$userId   = $jUser->id;
		$userName = $jUser->username;

		// Here logged-in user adding subscription for another another
		if ($app->isClient('administrator'))
		{
			$message = array(
				'action'             => $action,
				'type'               => 'PLG_ACTIONLOG_JMAILALERTS_TYPE_SUBSCRIPTION',
				'id'                 => $data['subscriptionId'],
				'subscribedusername' => $data['name'],
				'alertlink'          => 'index.php?option=com_jmailalerts&view=alert&layout=edit&id=' . $data['alert_id'],
				'alerttitle'         => $jmailalertsTablealert->title,
				'userid'             => $userId,
				'username'           => $userName,
				'accountlink'        => 'index.php?option=com_users&task=user.edit&id=' . $userId,
			);
		}
		// Here site visitor(guest) or register user subscribe to mail alert for self
		else
		{
			$message = array(
				'action'             => $action,
				'type'               => 'PLG_ACTIONLOG_JMAILALERTS_TYPE_SUBSCRIPTION',
				'id'                 => $data['subscriptionId'],
				'alertlink'          => 'index.php?option=com_jmailalerts&view=alert&layout=edit&id=' . $data['alert_id'],
				'alerttitle'         => $jmailalertsTablealert->title,
				'username'           => $data['name'],
				'userid'             => $userId,
				'accountlink'        => 'index.php?option=com_users&task=user.edit&id=' . $userId,
			);
		}

		$this->addLog(array($message), $messageLanguageKey, $context, $userId);
	}

	/**
	 * On after user unsubscribed logging method
	 *
	 * Method is called after subscription data is deleted from  the database.
	 *
	 * @param   string  $context  com_jmailalert.
	 * @param   Object  $table    Holds the subscription data.
	 *
	 * @return  void
	 *
	 * @since   2.6.1
	 */
	public function onAfterJmaAlertSubscriptionDelete($context, $table)
	{
		if (!$this->params->get('logActionForUnsubscribed', 1))
		{
			return;
		}

		$context            = Factory::getApplication()->input->get('option');
		$jUser              = Factory::getUser();
		$messageLanguageKey = 'PLG_ACTIONLOG_JMAILALERTS_USER_UNSUBSCRIPED';
		$action             = 'delete';
		$userId             = $jUser->id;
		$userName           = $jUser->username;

		$jmailalertsTablealert = Table::getInstance('alert', 'JmailalertsTable', array());
		$jmailalertsTablealert->load(array('id' => $table->alert_id));

		$message = array(
			'action'                      => $action,
			'type'                        => 'PLG_ACTIONLOG_JMAILALERTS_TYPE_SUBSCRIPTION_DELETE',
			'id'                          => $table->id,
			'title'                       => $table->title,
			'userid'                      => $userId,
			'username'                    => $userName,
			'accountlink'                 => 'index.php?option=com_users&task=user.edit&id=' . $userId,
			'unsubscribedusername'        => $table->name,
			'unsubscribeduseraccountlink' => 'index.php?option=com_users&task=user.edit&id=' . $table->user_id,
			'alertlink'                   => 'index.php?option=com_jmailalerts&view=alert&layout=edit&id=' . $jmailalertsTablealert->id,
			'alertname'                   => $jmailalertsTablealert->title ? $jmailalertsTablealert->title : $table->title,
		);

		$this->addLog(array($message), $messageLanguageKey, $context, $userId);
	}

	/**
	 * Proxy for ActionlogsModelUserlog addLog method
	 *
	 * This method adds a record to #__action_logs contains (message_language_key, message, date, context, user)
	 *
	 * @param   array   $messages            The contents of the messages to be logged
	 * @param   string  $messageLanguageKey  The language key of the message
	 * @param   string  $context             The context of the content passed to the plugin
	 * @param   int     $userId              ID of user perform the action, usually ID of current logged in user
	 *
	 * @return  void
	 *
	 * @since   2.6.1
	 */
	protected function addLog($messages, $messageLanguageKey, $context, $userId = null)
	{
		$app = Factory::getApplication();

		if (JVERSION >= '4.0.0')
		{
			$model = $app->bootComponent('com_actionlogs')->getMVCFactory()->createModel('Actionlog', 'Administrator', ['ignore_request' => true]);
		}
		else
		{
			JLoader::register('ActionlogsModelActionlog', JPATH_ADMINISTRATOR . '/components/com_actionlogs/models/actionlog.php');

			/* @var ActionlogsModelActionlog $model */
			$model = BaseDatabaseModel::getInstance('Actionlog', 'ActionlogsModel', array('ignore_request' => true));
		}

		$model->addLog($messages, $messageLanguageKey, $context, $userId);
	}
}
