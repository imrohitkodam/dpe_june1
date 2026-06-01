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
use Joomla\CMS\Table\Table;


/**
 * GMail Authentication Plugin
 *
 * @since  1.5
 */
class PlgAuthenticationDpeMfaLogin extends CMSPlugin
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

		$app = Factory::getApplication();
		$loginwithlink	= $app->input->get('loginwithlink', '', 'STRING');
		$loginwithotp	= $app->input->get('loginwithotp', '', 'STRING');
		$otp = $app->input->get('otp', '', 'STRING');
		$otpSuccess	= $app->input->get('otpsuccess', '', 'STRING');


		if($loginwithotp && (!$otpSuccess))
		{
			                    $app = Factory::getApplication();
								$menu       = $app->getMenu();
								$menuId = $menu->getItems('link', 'index.php?option=com_users&view=login', true);
								$msg =  Text::_('DPE_MSG_FOR_MFA_LOGIN_NO_OTP');
								$app->enqueueMessage($msg, 'Warning');
								$app->redirect(Route::_('index.php?option=com_users&view=login&Itemid='.$menuId->id, false));
		}
		// Load plugin language
		$this->loadLanguage();

		if (Factory::getApplication()->isClient('administrator'))
		{
			return;
		}

		// if(!$loginwithlink  && !$loginwithotp && !Factory::getApplication()->isClient('administrator')) 
		// {
		// 	$menu       = $app->getMenu();
		// 	$menuId = $menu->getItems('link', 'index.php?option=com_users&view=login', true);
		// 	$msg =  Text::_('DPE_MSG_FOR_MFA_LOGIN_PLEASE_CHOSSE_ONEOF_THE_OPTION');
		// 	$app->enqueueMessage($msg, 'success');
		// 	$app->redirect(Route::_('index.php?option=com_users&view=login&Itemid='.$menuId->id, false));
		// }

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
		->select('id, password, params')
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
			if ((isset($credentials['password_clear'])?$credentials['password_clear'] == $userData->password:$match))
			{

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

				// Query to get user related to any organization of logged in user
				$query = $db->getQuery(true);
				$query->select('a.id');
				$query->from($db->qn('#__tjsu_users', 'a'));
				$query->where($db->quoteName('a.user_id') . ' = ' . $db->quote((int) $user->id));
				$db->setQuery($query);

				$existingUsers  = $db->loadObjectList();


				if (!$existingUsers)
				{
					$app = Factory::getApplication();
					$menu       = $app->getMenu();
					$redirectUrl = Route::_('index.php?option=com_sppagebuilder&view=page&id=125',false);
					$menuId = $menu->getItems('link', 'index.php?option=com_users&view=login', true);
					$msg = Text::sprintf('JGLOBAL_AUTH_FAILED', Text::sprintf('DPE_AUTH_ACCESS_PUBLIC_DENIED', $redirectUrl));
					$app->enqueueMessage($msg, 'Warning');
					$app->redirect(Route::_('index.php?option=com_users&view=login&Itemid='.$menuId->id, false));

					return false;
				}

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

					$isRegistration = false;
					$timestamp = Factory::getDate()->format('U');
					$token     = JUserHelper::genRandomPassword();

					$hash = base64_encode($user->id.";".$token);

					if($loginwithlink == 'loginwithlink')
					{	
						
					// Restrict Super User to Generate Link
						if(!$user->authorise('core.admin'))
						{
							$userDatas['generationTime']=$timestamp;
							$userDatas['userToken'] = $token;
							$userDatas['userId'] = $user->id;
							$userDatas['params'] = ($userData->params != '')?$userData->params:'{"generationTime":"","userToken":""}';				

							if(!$this->updateTokenOfUsers($userDatas))
							{
								$app = Factory::getApplication();
								$menu       = $app->getMenu();
								$menuId = $menu->getItems('link', 'index.php?option=com_users&view=login', true);
								$msg =  Text::_('DPE_MSG_FOR_MFA_LOGIN_ERROR');
								$app->enqueueMessage($msg, 'error');
								$app->redirect(Route::_('index.php?option=com_users&view=login&Itemid='.$menuId->id, false));
							}

						}

						$config  = Factory::getConfig();
						$data    = $user->getProperties();
						$data['fromname']	= $config->get('fromname');
						$data['mailfrom']	= $config->get('mailfrom');
						$data['sitename']	= $config->get('sitename');
						$data['siteurl']	= JUri::base();

		//get return url

						$data['activate'] = Route::_($data['siteurl'].'index.php?option=plg_bbpass&action=verifyUser&check='.$hash, false);

		// Set body of mail
						if(!$isRegistration)
						{
			// DPE - Hack Dont send registration email
							$body = Text::sprintf("PLG_BBPASS_LOGIN_LINK", $user->name, $data['activate']);
							$mailer = Factory::getMailer();
							$mailer->isHtml(true);

							if(empty($options['type']) && ($options['type']!='bbpautologin'))
							{

						//send link to login the user
								$mailer->setSender( array(
									$data['mailfrom'],
									$data['fromname']
								))
								->addRecipient($data['email'])
								->setSubject(Text::_('PLG_BBPASS_EMAIL_SUBJECT'))
								->setBody($body)
								->Send();

								$app = Factory::getApplication();
								$menu       = $app->getMenu();
								$menuId = $menu->getItems('link', 'index.php?option=com_users&view=login', true);
								$msg =  Text::_('DPE_MSG_FOR_MFA_LOGIN');
								$app->enqueueMessage($msg, 'success');
								$app->redirect(Route::_('index.php?option=com_users&view=login&Itemid='.$menuId->id, false));
							}
							else
							{
								$response->status = 1;
							}

						}

					}
				}
			}
		}

	}
	protected function updateTokenOfUsers($userData)
	{
		if(empty($userData))
		{
			return false;
		}

		$userId = $userData['userId'];
		$tokenData = json_decode($userData['params']);

		$tokenData->generationTime = $userData['generationTime'];
		$tokenData->userToken = $userData['userToken'];
		$finalTokenData = json_encode($tokenData);


		$db = Factory::getDbo();
		$query = $db->getQuery(true);

		// Fields to update.
		$fields = [
			$db->quoteName('params') . ' = ' . $db->quote($finalTokenData)
		];

		// Conditions for which records should be updated.
		$conditions = [
			$db->quoteName('id') . ' = ' . $db->quote($userId)
		];

		$query->update($db->quoteName('#__users'))->set($fields)->where($conditions);


		$db->setQuery($query);
		$result = $db->execute();

		if ($result)
		{
			return true;
		}else
		{
			return false;
		}

	}

}
