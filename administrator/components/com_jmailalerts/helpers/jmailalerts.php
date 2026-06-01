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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Helper\ContentHelper;

/**
 * JMA helper class
 *
 * @since  2.5.0
 */
class JmailalertsHelper
{
	/**
	 * Add sub menu
	 *
	 * @param   string  $vName  View Name
	 *
	 * @return void
	 */
	public static function addSubmenu($vName = '')
	{
		if (JVERSION < '4.0.0')
		{
			JHtmlSidebar::addEntry(
				Text::_('COM_JMAILALERTS_TITLE_DASHBOARD'),
				'index.php?option=com_jmailalerts&view=dashboard',
				$vName == 'dashboard'
			);
			JHtmlSidebar::addEntry(
				Text::_('COM_JMAILALERTS_TITLE_FREQUENCIES'),
				'index.php?option=com_jmailalerts&view=frequencies',
				$vName == 'frequencies'
			);

			JHtmlSidebar::addEntry(
				Text::_('COM_JMAILALERTS_TITLE_ALERTS'),
				'index.php?option=com_jmailalerts&view=alerts',
				$vName == 'alerts'
			);

			JHtmlSidebar::addEntry(
				Text::_('COM_JMAILALERTS_TITLE_SYNC'),
				'index.php?option=com_jmailalerts&view=sync',
				$vName == 'sync'
			);

			JHtmlSidebar::addEntry(
				Text::_('COM_JMAILALERTS_MAILSIMULATE'),
				'index.php?option=com_jmailalerts&view=mailsimulate',
				$vName == 'mailsimulate'
			);

			JHtmlSidebar::addEntry(
				Text::_('COM_JMAILALERTS_TITLE_SUBSCRIBERS'),
				'index.php?option=com_jmailalerts&view=subscribers',
				$vName == 'subscribers'
			);

			JHtmlSidebar::addEntry(
				Text::_('COM_JMAILALERTS_HEALTHCHECK'),
				'index.php?option=com_jmailalerts&view=healthcheck',
				$vName == 'healthcheck'
			);
		}
	}

	/**
	 * Gets a list of the actions that can be performed.
	 *
	 * @param   string   $component  The component name.
	 * @param   string   $section    The access section name.
	 * @param   integer  $id         The item ID.
	 *
	 * @return  \JObject
	 *
	 * @since   3.2
	 */
	public static function getActions($component = 'com_jmailalerts', $section = 'component', $id = 0)
	{
		// Get list of actions
		return ContentHelper::getActions($component, $section, $id);
	}

	/**
	 * Method to get the number of users subscribe for alerts
	 *
	 * @param   int  $alertid  Alert id
	 *
	 * @return void
	 */
	public function getSubscribesCount($alertid)
	{
		$db = Factory::getDBO();

		// Get the all alert id
		if ($alertid == 0)
		{
			// This getSubscribesCount method call from Manageuser view for multibel alerts
			// &  its alertid=0, if it call from syn view then alert id !=0
			$query = $db->getQuery(true);
			$query->select('alert.id as alerts_id');
			$query->from('`#__jma_alerts` as alert');
			$query->group('alert.id');
			$db->setQuery($query);
			$alertsId = $db->loadColumn();
		}
		else
		{
			$alertsId[0] = $alertid;
		}

		$alertSubscribedReport = array();

		foreach ($alertsId as $singleAlert)
		{
			// Get the registered user count against alert
			$query = $db->getQuery(true);
			$query->select('count(subs.user_id) as registered_users');
			$query->from('`#__jma_subscribers` as subs');
			$query->join('LEFT', '`#__jma_alerts` as alerts ON alerts.id=subs.alert_id');
			$query->where('subs.user_id>0 AND subs.state <> 0 AND subs.alert_id=' . $singleAlert);
			$db->setQuery($query);
			$alertSubscribedReport[$singleAlert]['registed_users'] = $db->loadResult();

			// Get the guest user count
			$query = $db->getQuery(true);
			$query->select('count(subs.user_id) as guest_users');
			$query->from('`#__jma_subscribers` as subs');
			$query->join('LEFT', '`#__jma_alerts` as alerts ON alerts.id=subs.alert_id');
			$query->where('subs.user_id=0 AND subs.state <> 0 AND subs.alert_id=' . $singleAlert);
			$db->setQuery($query);
			$alertSubscribedReport[$singleAlert]['guest_users'] = $db->loadResult();

			// Get the unsubscribed registerd users
			$query = $db->getQuery(true);
			$query->select('count(subs.user_id) as unsubscribed_users');
			$query->from('`#__jma_subscribers` as subs');
			$query->join('LEFT', '`#__jma_alerts` as alerts ON alerts.id=subs.alert_id');
			$query->where('subs.user_id>0 AND subs.state=0 AND subs.alert_id=' . $singleAlert);
			$db->setQuery($query);
			$alertSubscribedReport[$singleAlert]['unsubscribed_users'] = $db->loadResult();

			// Get the unsubscribed guest users
			$query = $db->getQuery(true);
			$query->select('count(subs.user_id) as unsub_guest_users,alerts.id as alert_id');
			$query->from('`#__jma_subscribers` as subs');
			$query->join('LEFT', '`#__jma_alerts` as alerts ON alerts.id=subs.alert_id');
			$query->where('subs.user_id=0 AND subs.state=0 AND subs.alert_id=' . $singleAlert);
			$db->setQuery($query);
			$alertSubscribedReport[$singleAlert]['unsub_guest_users'] = $db->loadResult();

			// Start not_opted_user for alert **************************
			// Get the alert subscribed users id
			$query = $db->getQuery(true);
			$query->select('subs.user_id');
			$query->from('`#__jma_subscribers` as subs');
			$query->where('subs.alert_id=' . $singleAlert);
			$db->setQuery($query);
			$user = $db->loadColumn();

			// Not_opted_user for alert
			if (!empty($user))
			{
				// Get the users count who not subscribed for this alert
				$user = implode(',', $user);

				// Get the user count form #__users where user not in subscriber
				$query = $db->getQuery(true);
				$query->select('count(user.id) as not_opted_user');
				$query->from('`#__users` as user');
				$query->where('user.id NOT IN (' . $user . ')');
				$db->setQuery($query);
				$notOptedUser = $db->loadResult();
				$alertSubscribedReport[$singleAlert]['not_opted_user'] = $notOptedUser;
			}
			else
			{
				// Get the $user is empty means no user subscribe for this alert , get the all user from users table
				$query = $db->getQuery(true);
				$query->select('count(user.id) as not_opted_user');
				$query->from('`#__users` as user');
				$db->setQuery($query);
				$notOptedUser = $db->loadResult();
				$alertSubscribedReport[$singleAlert]['not_opted_user'] = $notOptedUser;
			}

			// End not_opted_user for alert
		}

		return $alertSubscribedReport;
	}
}
