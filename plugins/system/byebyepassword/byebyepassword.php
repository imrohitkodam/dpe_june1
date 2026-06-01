<?php
/**
* @package Joomla plugin for byebye password
* @copyright Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
* @license GNU General Public License version 2 or later; see LICENSE
*
* @author Rimjhim
*/

defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;	
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Session\Session;
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserHelper;
use Joomla\CMS\Authentication\Authentication;

jimport('joomla.mail.helper');
require_once dirname(__FILE__)."/helper.php";

class PlgSystemByeByePassword extends CMSPlugin
{
	public function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);
		Factory::getLanguage()->load('plg_system_byebyepassword', __DIR__);
	}

	public function onAfterRoute()
	{
		$option = Factory::getApplication()->input->get('option',null,'STRING');

		//do nothing, if someone is logged-in
		if(Factory::getUser()->id && $option == 'plg_bbpass'){
			$returnIdx = Factory::getApplication()->input->get('return', null, 'BASE64');
			$returnUrl = $this->validateUrl(base64_decode($returnIdx));
			return Factory::getApplication()->redirect(Route::_($returnUrl, false));
		}


		$action = Factory::getApplication()->input->get('action',null,'STRING');

		if($option != 'plg_bbpass'){
			return true;
		}

		//do action
		if($action == 'loginregister'){
        	// Check for request forgeries
			Session::checkToken('post') or Factory::getApplication()->close(Text::_('JINVALID_TOKEN'));

			$this->loginRegister();

		}elseif($action == 'verifyUser'){

			$this->verifyLogin();
		}


		$returnIdx = Factory::getApplication()->input->get('return', null, 'BASE64');
		$returnUrl = $this->validateUrl(base64_decode($returnIdx));
		Factory::getApplication()->redirect(Route::_($returnUrl, false));
	}

	function verifyLogin()
	{
		$hash = Factory::getApplication()->input->get('check', null, 'BASE64');

		if (empty($hash)) {
			return;
		}

		$decodedHash = base64_decode($hash);
		if (empty($decodedHash) || strpos($decodedHash, ';') === false) {
			return;
		}

		$data = explode(';', $decodedHash);
		
		if (count($data) < 2) {
			return;
		}

		// Ensure user ID is an integer
		$userId = (int) $data[0];
		if ($userId <= 0) {
			return;
		}

		$user = Factory::getUser($userId);

		if (!$user || !$user->id || $user->get('block')) {
			return;
		}

		$savedToken     = $user->getParam('userToken', 0);
		$generationTime = (int) $user->getParam('generationTime', 0);

		if (empty($savedToken) || $savedToken != $data[1]) {
			return Factory::getApplication()->enqueueMessage(Text::_("PLG_BBPASS_TOKEN_DOES_NOT_MATCH"), 'error');
		}

		$now = Factory::getDate()->format('U');

		// Check if link is still active
		if (($now - $generationTime) < Factory::getApplication()->getCfg('lifetime') * 60) {
			$this->autoLogin($user);

			// Clear token after use to prevent replay attacks
			if (!$user->authorise('core.admin')) {
				$user->setParam('userToken', '');
				$user->setParam('generationTime', 0);
				$user->save();
			}
		} else {
			return Factory::getApplication()->enqueueMessage(Text::_("PLG_BBPASS_LOGIN_LINK_EXPIRED"), 'error');
		}
	}

	public function autoLogin($user)
	{
			if (empty($user) || empty($user->username)) {
				return;
			}

			// 	Get a database object
			$db	     	 = Factory::getDBO();
			$query       = $db->getQuery(true)
				->select('*')
				->from($db->quoteName('#__users'))
				->where($db->quoteName('username') . ' = ' . $db->quote($user->username));

			$result      = $db->setQuery($query)->loadObject();

			if (empty($result) || empty($result->password)) {
				return;
			}

			$credentials = array('username'=>$user->username,'password'=>$result->password, 'password_clear'=>$result->password);
		 	$options     = array('user_id'=>$user->get('id'),'type'=>'bbpautologin', 'autoregister'=>false, 'user_record'=>$result);

		 	$session = Factory::getSession();
			$session->set('loginwithlink', 'loginwithlink');
			$session->set('bbp_auth_trigger', true); // Session lock

		 	//add authentication plugin, so that we need not to create a different plugin for that
		 	byebyepasswordHelper::setplugin();

			$app = Factory::getApplication();
		 	$app->login($credentials,$options);
	}

   function loginregister()
   {
	   	$email = Factory::getApplication()->input->get('email', null, 'EMAIL');
	   	$msg   = '';
		$valid = true;

		if(!JMailHelper::isEmailAddress($email)){
			$msg = Text::_('PLG_BBPASS_INVALID_EMAIL');
			$valid = false;
		}



		if($valid){
			$db	   	   = Factory::getDBO();
			$query     = $db->getQuery(true)
				->select('*')
				->from($db->quoteName('#__users'))
				->where($db->quoteName('email') . ' = ' . $db->quote($email));

			$result    = $db->setQuery($query)->loadObject();

			//if user is already exist then just send login link
			if(isset($result->id))
			{
				$user = Factory::getUser($result->id);

				if($user->authorise('core.admin'))
				{
					$msg = Text::_('PLG_BBPASS_LOGIN_SUPER_USER_CANT_GENERATE');
					$valid = false;
					Factory::getApplication()->enqueueMessage($msg, 'error');
					$currentIdx = Factory::getApplication()->input->get('currentUrl', null, 'BASE64');
					$currentUrl = $this->validateUrl(base64_decode($currentIdx));
					return Factory::getApplication()->redirect(Route::_($currentUrl) . '&success=1');
				}
				else
				{
					//send activation link and login the user
					$this->sendLink($user);
					$msg = Text::_('PLG_BBPASS_LOGIN_LINK_SENT');
				}
			}
			else
			{
				//register new user and send activation link

				// DPE - Hack Dont allow to create user if not exist

				//~ $msg = Text::_('PLG_BBPASS_REGISTER_SUCCESS');

				$msg = Text::_('PLG_BBPASS_LOGIN_ACCOUNT_NOT_EXIST');
				$valid = false;
				$session = Factory::getSession();
				$session->set('bbpassInvalidEmail', $msg); 
				Factory::getApplication()->enqueueMessage($msg, 'error');
				$currentIdx = Factory::getApplication()->input->get('currentUrl', null, 'BASE64');
				$currentUrl = $this->validateUrl(base64_decode($currentIdx));
				return Factory::getApplication()->redirect(Route::_($currentUrl) . '&success=1');
			}
		}
		
		//return to current url
		Factory::getApplication()->enqueueMessage($msg, 'success');
		$currentIdx = Factory::getApplication()->input->get('currentUrl', null, 'BASE64');
		$currentUrl = $this->validateUrl(base64_decode($currentIdx));
		Factory::getApplication()->redirect(Route::_($currentUrl) . '&success=1');
      }

	function sendLink($user,$isRegistration = false)
	{
		$timestamp = Factory::getDate()->format('U');
		$token     = UserHelper::genRandomPassword();

		//create hash containing userid and token
		$hash = base64_encode($user->id.";".$token);

		// This checking is required for super users
		// as joomla doesn't allow to modify data of super users,directly

		// Restrict Super User to Generate Link
		if(!$user->authorise('core.admin'))
		{
			$user->setParam('generationTime',$timestamp);
			$user->setParam('userToken',$token);
			$user->save();
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

			//send link to login the user
			return  $mailer->setSender( array(
											$data['mailfrom'],
											$data['fromname']
										   ))
							->addRecipient($data['email'])
							->setSubject(Text::_('PLG_BBPASS_EMAIL_SUBJECT'))
							->setBody($body)
							->Send();
		}
	}

	/**
	 * Validate a URL to ensure it is internal to the site.
	 *
	 * @param   string  $url  The URL to validate.
	 *
	 * @return  string  The validated internal URL or the site base URL if invalid.
	 */
	protected function validateUrl($url)
	{
		if (empty($url))
		{
			return Uri::base();
		}

		// Ensure it's not a protocol-relative URL
		if (strpos($url, '//') === 0)
		{
			return Uri::base();
		}

		// Check if it's an absolute URL
		if (preg_match('/^https?:\/\//i', $url))
		{
			$uri = new Uri($url);
			$base = new Uri(Uri::base());

			// Check if host matches
			if ($uri->getHost() !== $base->getHost())
			{
				return Uri::base();
			}
		}

		return $url;
	}
}

class plgAuthenticationbbpAutoLogin extends CMSPlugin
{
	public function onUserAuthenticate($credentials, $options, $response)
	{
		$session = Factory::getSession();
		$trigger = $session->get('bbp_auth_trigger', false);

		if(isset($options['type']) && $options['type'] == 'bbpautologin' && $trigger){
			// Clear trigger immediately
			$session->set('bbp_auth_trigger', false);

			self::_setResponse($options, $response);
			$response->status 	= Authentication::STATUS_SUCCESS;
		}
	}

	protected static function _setResponse($options, &$response)
	{
		$user = User::getInstance($options['user_id']);

		$response->email 			= $user->email;
		$response->fullname 		= $user->name;
		$response->username 		= $user->username;
		$response->language 		= $user->getParam('language');
		$response->error_message 	= '';
		$response->type				= 'bbpautologin';
	}
}
