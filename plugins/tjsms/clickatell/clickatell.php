<?php
/**
 * @version    SVN: <svn_id>
 * @package    Techjoomla_API
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2021 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;

/**
 * Class for Clickatell Tjsms Plugin
 *
 * @since  1.0.0
 */
class PlgtjsmsClickatell extends CMSPlugin
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
	 * Functions to send SMS
	 *
	 * @param   string  $phone       phone (if multiple phone numbers then comma seperated numbers)
	 * @param   string  $message     message
	 * @param   int     $templateId  SMS provider template Id
	 *
	 * @return  array
	 *
	 * @since  1.0.0
	 */
	public function onSend_SMS($phone, $message, $templateId = 0)
	{
		$serviceAccount = $this->params->get('service_account');
		$apiKey = $this->params->get('apiKey');
		$appUserName = $this->params->get('appUsername');
		$appPassword = $this->params->get('appPassword');
		$appKey = $this->params->get('appkey');
		$from = $this->params->get('from');
		$mobFlag = $this->params->get('mobileoriginated');

		// Check if keys are set
		if ($serviceAccount == "central")
		{
			if (empty($appKey) || empty($appUserName) || empty($appPassword))
			{
				return 0;
			}
		}
		else
		{
			if (empty($apiKey))
			{
				return 0;
			}
		}

		// Check if message and mobile number are provided
		if (empty($message) || empty($phone))
		{
			return;
		}

		$messageStatus = $this->send($serviceAccount, $appUserName, $appPassword, $appKey, $apiKey, $message, $phone, $from, $mobFlag);

		return $messageStatus;
	}

	/**
	 * Helper functions to send SMS
	 *
	 * @param   STRING  $serviceAccount  service account type
	 * @param   OBJECT  $appUserName     user
	 * @param   STRING  $appPassword     Password
	 * @param   STRING  $appKey          API id
	 * @param   STRING  $apiKey          API key
	 * @param   STRING  $text            TEXT in the SMS
	 * @param   INT     $to              Number to which SMS is to be sent
	 * @param   STRING  $from            source number
	 * @param   INT     $mobFlag         mobile oreiented flag
	 *
	 * @return  void
	 *
	 * @since  1.0
	 */
	private function send($serviceAccount, $appUserName, $appPassword, $appKey, $apiKey, $text, $to, $from = 0,$mobFlag = 0)
	{
		$return = array();

		if ($serviceAccount == 'central')
		{
			$text = urlencode($text);
			$baseurl = "https://api.clickatell.com";

			// OAuth URL
			$url = $baseurl . "/http/auth?user=" . $appUserName . "&password=" . $appPassword . "&api_id=" . $appKey;

			// Do OAuth call
			$response = file($url);

			// Get OAuth response
			$oauthResponse = explode(":", $response[0]);

			if ($oauthResponse[0] == "OK")
			{
				// Remove any whitespace
				$sessionId = trim($oauthResponse[1]);
				$url = $baseurl . "/http/sendmsg?user=" . $appUserName . "&password=" . $appPassword
				. "&api_id=" . $appKey . "&session_id=" . $sessionId . "&to=" . $to . "&text=" . $text . "&callback=6";

				if (!empty($from))
				{
					$url .= "&from=" . $from;
				}

				if ($mobFlag == 1)
				{
					$url .= "&mo=1";
				}

				// Do sendmsg call
				$response = file($url);
				$send = explode(":", $response[0]);
			}
			else
			{
				echo "Authentication failure: " . $response[0];
			}

			if ($send[0] == "ID")
			{
				$return[0] = 1;
				$return[1] = $send[1];
			}
			else
			{
				$return[0] = -1;
				$return[1] = $send[0] . $send[1];
			}
		}
		else
		{
			$text = urlencode($text);
			$baseurl = "https://platform.clickatell.com/messages/http/send";

			$url = $baseurl . "?apiKey=" . $apiKey . "&to=" . $to . "&content=" . $text;

			if ($mobFlag == 1)
			{
				if (!empty($from))
				{
					$url .= "&from=" . $from;
				}
			}

			$response = file($url);
			$response = json_decode($response[0]);
			$status = empty($response->messages[0]->accepted)?'0':$response->messages[0]->accepted;
			$apiMessageId = empty($response->messages[0]->apiMessageId)?'0':$response->messages[0]->apiMessageId;

			if ($status == 1)
			{
				$return[0] = 1;
				$return[1] = $apiMessageId;
			}
			else
			{
				$msg = Text::_('PLG_TJSMS_CLICKATELL_ERROR');
				$return[0] = 0;
				$return[1] = empty($response->messages[0]->error)?$msg:$response->messages[0]->error;
			}
		}

		return $return;
	}
}
