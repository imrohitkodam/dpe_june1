<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Tjsms.textlocal
 *
 * @copyright   Copyright (C) 2009 - 2021 Techjoomla. All rights reserved.
 * @license     http:/www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Language\Text;
use Joomla\Registry\Registry;
use Joomla\CMS\Http\Http;
use Joomla\CMS\Plugin\CMSPlugin;

/**
 * Class for Textlocal Tjsms Plugin
 *
 * @since  1.0.0
 */
class PlgTjsmsTextlocal extends CMSPlugin
{
	/**
	 * Load the language file on instantiation.
	 *
	 * @var    boolean
	 *
	 * @since  3.2.11
	 */
	protected $autoloadLanguage = true;

	protected $apikey;

	protected $sender;

	protected $timeout;

	protected $url = '';

	/**
	 * Constructor
	 *
	 * @param   string  $subject  subject
	 * @param   array   $config   config
	 *
	 * @since   1.0
	 */
	public function __construct($subject, $config)
	{
		parent::__construct($subject, $config);

		$this->apikey = $this->params->get('apikey');
		$this->sender = $this->params->get('sender');
		$this->timeout  = 30;
		$this->url      = 'https://api.textlocal.in/send/';
	}

	/**
	 * Function to send the message
	 *
	 * @param   string  $phone       Phone (if multiple phone numbers then comma seperated numbers)
	 * @param   string  $message     Message
	 * @param   int     $templateId  SMS provider template Id
	 *
	 * @return  array  Returns array containing keys as phone, message and status
	 *
	 * @since  1.0
	 */
	protected function send($phone, $message, $templateId)
	{
		// Check phone
		if (trim($phone) == "" || strlen($phone) == 0)
		{
			return array("error" => Text::_('PLG_TJSMS_TEXTLOCAL_ERROR_INVALID_NUMBER'));
		}

		// Check the message
		if (trim($message) == "" || strlen($message) == 0)
		{
			return array("error" => Text::_('PLG_TJSMS_TEXTLOCAL_ERROR_INVALID_MESSAGE'));
		}

		$return = array();

		$unicode = false;

		// If message have unicode language then set unicode to true.
		if (strlen($message) != strlen(utf8_decode($message)))
		{
			$unicode = true;
		}

		// Urlencode your message
		$message = rawurlencode($message);

		// Create jhttp object
		$headers = array('Content-Type' => 'application/x-www-form-urlencoded');
		$options = new Registry;
		$options->set('timeout', $this->timeout);
		$http    = new Http($options);

		try
		{
			$this->url .= '?apiKey=' . urlencode($this->apikey);
			$this->url .= '&sender=' . urlencode($this->sender);
			$this->url .= '&numbers=' . $phone;
			$this->url .= '&message=' . $message;
			$this->url .= '&unicode=' . $unicode;

			$response  = $http->get($this->url, $headers);

			if ($response->code !== 200)
			{
				throw new Exception($response->body, $response->code);
			}

			$return['success'] = 1;
		}
		catch (Exception $e)
		{
			$return['success'] = 0;
			$return['code']    = $e->getCode();
			$return['message'] = $e->getMessage();
			$return['trace']   = $e->getTrace();

			throw new Exception($e->getMessage(), $e->getCode());
		}

		return $return;
	}

	/**
	 * Functions to send SMS
	 *
	 * @param   string  $phone       phone
	 * @param   string  $message     message
	 * @param   int     $templateId  SMS provider template Id
	 *
	 * @return  array  Returns array containing keys as phone, message and status
	 *
	 * @since  1.0
	 */
	public function onSend_SMS($phone, $message, $templateId = 0)
	{
		return $this->send($phone, $message, $templateId);
	}
}
