<?php
/**
 * @copyright  Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Authentication\Authentication;
use Joomla\CMS\User\UserHelper;
use Joomla\Registry\Registry;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/**
 * GMail Authentication Plugin
 *
 * @since  1.5
 */
class PlgAuthenticationDpe extends CMSPlugin
{
	/**
	 * This method should handle any authentication and report back to the subject
	 *
	 * @param   array                   $credentials  Array holding the user credentials
	 * @param   array                   $options      Array of extra options
	 * @param   AuthenticationResponse  &$response    Authentication response object
	 *
	 * @return  void
	 *
	 * @since   1.5
	 */
	public function onUserAuthenticate($credentials, $options, &$response)
	{
		// Load plugin language
		$this->loadLanguage();

		if (Factory::getApplication()->isClient('administrator'))
		{
			return;
		}

		// Code start to restrict site access for expired license school users

		// Joomla does not like blank passwords
		if (empty($credentials['password']))
		{
			$response->status        = Authentication::STATUS_FAILURE;
			$response->error_message = Text::_('JGLOBAL_AUTH_EMPTY_PASS_NOT_ALLOWED');

			return;
		}

		// Get a database object
		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select('id, password')
			->from('#__users')
			->where($db->qn('username') . ' = ' . $db->q($credentials['username']) . 'OR' . $db->qn('email') . ' = ' . $db->q($credentials['username']));

		$db->setQuery($query);
		$userData = $db->loadObject();

		// Check password for user

		if ($userData)
		{
			$match = UserHelper::verifyPassword($credentials['password'], $userData->password, $userData->id);
		}

		// Get user object
		$user      = Factory::getUser($userData->id);
		$allowedUserGroups = $this->params->get('dpe_usergroup');

		if (count(array_intersect($user->groups, $allowedUserGroups)) == 0)
		{
			// Check licenses if password is correct

			if ((isset($credentials['password_clear'])?$credentials['password_clear'] == $userData->password:$match))
			{
				// If the user didn't set a timezone, it will return the server timezone
				$tz          = $user->getTimezone();
				$date        = Factory::getDate('now');
				$currentdate = $date->setTimezone($tz);
				$db          = Factory::getDbo();
				$query       = $db->getQuery(true);

				// Query to get activated licesce school(s) of logged in user
				$query->select('DISTINCT c.id, c.title');
				$query->from($db->qn('#__users', 'a'));
				$query->join('INNER', $db->qn('#__tjsu_users', 'b') .
				' ON (' . $db->qn('a.id') . ' = ' . $db->qn('b.user_id') . ' AND ' . $db->qn('b.client') . ' = "com_multiagency" )');
				$query->join('INNER', $db->qn('#__tjmultiagency_multiagency', 'c') . ' ON (' . $db->qn('b.client_id') . ' = ' . $db->qn('c.id') . ')');
				$query->join('INNER', $db->qn('#__tjmultiagency_licences', 'd') . ' ON (' . $db->qn('d.multiagency_id') . ' = ' . $db->qn('c.id') . ' )');
				$query->where($db->quoteName('a.id') . ' = ' . $db->quote((int) $user->id));
				$query->where($db->quoteName('d.state') . ' = 1');
				$query->where($db->quoteName('d.end_date') . ' >= ' . $db->quote($currentdate));
				$db->setQuery($query);

				$result  = $db->loadObjectList();

				if (!$result)
				{
					$app = Factory::getApplication();
					$menu       = $app->getMenu();
					$menuId = $menu->getItems('link', 'index.php?option=com_users&view=login', true);
					$msg = Text::sprintf('JGLOBAL_AUTH_FAILED', Text::_('DPE_AUTH_ACCESS_DENIED'));
					$app->enqueueMessage($msg, 'Warning');
					$app->redirect(Route::_('index.php?option=com_users&view=login&Itemid='.$menuId->id, false));

					return false;
				}else
				{
					$response->status = 1;
				}
			}
		}
	}
}
