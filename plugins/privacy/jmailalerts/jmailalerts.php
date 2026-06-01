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

// User table alias is loaded
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\User as UserTable;
use Joomla\CMS\User\User;

JLoader::register('PrivacyPlugin', JPATH_ADMINISTRATOR . '/components/com_privacy/helpers/plugin.php');
JLoader::register('PrivacyRemovalStatus', JPATH_ADMINISTRATOR . '/components/com_privacy/helpers/removal/status.php');

/**
 * Privacy plugin managing JMailAlerts user data
 *
 * @since  3.2.11
 */
class PlgPrivacyJmailalerts extends PrivacyPlugin
{
	/**
	 * Load the language file on instantiation.
	 *
	 * @var    boolean
	 *
	 * @since  3.2.11
	 */
	protected $autoloadLanguage = true;

	/**
	 * Database object
	 *
	 * @var    JDatabaseDriver
	 * @since  2.6.1
	 */
	protected $db;

	/**
	 * Reports the privacy related capabilities for this plugin to site administrators.
	 *
	 * @return  array
	 *
	 * @since   2.6.1
	 */
	public function onPrivacyCollectAdminCapabilities()
	{
		$this->loadLanguage();

		return array(
			Text::_('PLG_PRIVACY_JMAILALERTS') => array(
				Text::_('PLG_PRIVACY_JMAILALERTS_PRIVACY_CAPABILITY_USER_DETAIL')
			)
		);
	}

	/**
	 * Processes an export request for Jmailalerts user data
	 *
	 * This event will collect data for the following tables:
	 *
	 * - #__jma_subscribers
	 *
	 * @param   PrivacyTableRequest  $request  The request record being processed
	 * @param   JUser                $user     The user account associated with this request if available
	 *
	 * @return  PrivacyExportDomain[]
	 *
	 * @since   2.6.1
	 */
	public function onPrivacyExportRequest(PrivacyTableRequest $request, User $user = null)
	{
		if (!$user)
		{
			return array();
		}

		/** @var JTableUser $userTable */
		$userTable = UserTable::getTable();
		$userTable->load($user->id);

		$domains = array();

		// Create the domain for the JMailAlerts User Subscription data
		$domains[] = $this->createJmailalertsSubscriptionDomain($userTable);

		return $domains;
	}

	/**
	 * Create the domain for the JMailAlerts User Subscription data
	 *
	 * @param   JTableUser  $user  The JTableUser object to process
	 *
	 * @return  PrivacyExportDomain
	 *
	 * @since   2.6.1
	 */
	private function createJmailalertsSubscriptionDomain(User $user)
	{
		$domain = $this->createDomain('Jmailalerts subscription', 'Jmailalerts subscription data');

		$query = $this->db->getQuery(true)
			->select('id, ordering, state, user_id, alert_id, name, email_id, frequency, date, plugins_subscribed_to')
			->from($this->db->quoteName('#__jma_subscribers'))
			->where(
				$this->db->quoteName('user_id') . ' = ' . $this->db->quote($user->id) .
				' OR ' . $this->db->quoteName('email_id') . ' = ' . $this->db->quote($user->email)
			);

		$userSubscriptionData = $this->db->setQuery($query)->loadAssocList();

		if (!empty($userSubscriptionData))
		{
			foreach ($userSubscriptionData as $subscriptionData)
			{
				$domain->addItem($this->createItemFromArray($subscriptionData, $subscriptionData['id']));
			}
		}

		return $domain;
	}

	/**
	 * Performs validation to determine if the data associated with a remove information request can be processed
	 *
	 * This event will not allow a super user account to be removed
	 *
	 * @param   PrivacyTableRequest  $request  The request record being processed
	 * @param   JUser                $user     The user account associated with this request if available
	 *
	 * @return  PrivacyRemovalStatus
	 *
	 * @since   2.6.1
	 */
	public function onPrivacyCanRemoveData(PrivacyTableRequest $request, User $user = null)
	{
		$status = new PrivacyRemovalStatus;

		if (!$user)
		{
			return $status;
		}

		return $status;
	}

	/**
	 * Removes the data associated with a remove information request
	 *
	 * This event will pseudoanonymise the user account
	 *
	 * @param   PrivacyTableRequest  $request  The request record being processed
	 * @param   JUser                $user     The user account associated with this request if available
	 *
	 * @return  void
	 *
	 * @since   2.6.1
	 */
	public function onPrivacyRemoveData(PrivacyTableRequest $request, User $user = null)
	{
		// This plugin only processes data for registered user accounts
		if (!$user)
		{
			return;
		}

		// If there was an error loading the user do nothing here
		if ($user->guest)
		{
			return;
		}

		$db = $this->db;

		// 1. Delete Jmailalerts user subscription data :
		$query1 = $db->getQuery(true)
			->delete($db->quoteName('#__jma_subscribers'))
			->where('user_id = ' . $user->id . ' OR email_id = ' . $db->quote($user->email));
		$db->setQuery($query1);
		$db->execute();
	}
}
