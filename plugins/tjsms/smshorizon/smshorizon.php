<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Tjsms.smshorizon
 *
 * @copyright   Copyright (C) 2009 - 2021 Techjoomla. All rights reserved.
 * @license     http:/www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Plugin\CMSPlugin;

/**
 * Class for SMSHorizon Tjsms Plugin
 *
 * @since  1.0.0
 */
class PlgtjsmsSmshorizon extends CMSPlugin
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
	 * sending sms constructor
	 *
	 * @param   string  $subject  subject
	 * @param   array   $config   config
	 *
	 * @since   1.0
	 */
	public function __construct($subject, $config)
	{
		parent::__construct($subject, $config);

		$this->appUserName = $this->params->get('user');
		$this->apiKey = $this->params->get('apikey');
	}

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
		// Check if keys are set
		if ($this->appUserName == '' || $this->apiKey == '' || empty($message) || empty($phone))
		{
			return 0;
		}

		// Replace if you have your own Sender ID, else donot change
		$senderid = "WEBSMS";

		// Replace with the destination mobile Number to which you want to send sms
		$mobile = $phone;

		// Replace with your Message content
		$message = urlencode($message);

		// For Plain Text, use "txt" ; for Unicode symbols or regional Languages like hindi/tamil/kannada use "uni"
		$type = "txt";
		$url  = "http://smshorizon.co.in/api/sendsms.php?user=" . $this->appUserName . "&apikey=" . $this->apiKey;
		$ch   = curl_init($url . "&mobile=" . $mobile . "&senderid=" . $senderid . "&message=" . $message . "&type=" . $type . "");
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$output = curl_exec($ch);
		curl_close($ch);

		// Display MSGID of the successful sms push
		if ($output)
		{
			$url_status = "http://smshorizon.co.in/api/status.php?user=" . $this->appUserName . "&apikey=" . $this->apiKey . "&msgid=" . $output;
			$ch         = curl_init($url_status);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			$output_status = curl_exec($ch);
			curl_close($ch);
			$output_status = trim($output_status);

			if ($output_status == "Message Sent")
			{
				$actual_Send_message = 1;
			}
			else
			{
				$actual_Send_message = $output_status;
			}
		}

		return $actual_Send_message;
	}
}
