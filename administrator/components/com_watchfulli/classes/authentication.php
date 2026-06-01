<?php

/**
 * @version     admin/classes/authentication.php 2024-03-27 zanardigit
 * @package     Watchful Client
 * @author      Watchful
 * @authorUrl   https://watchful.net
 * @copyright   Copyright (c) 2012-2023 Watchful
 * @license     GNU/GPL v3 or later
 */

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;

defined('_JEXEC') or die();
defined('WATCHFULLI_PATH') or die();

class WatchfulliAuthentication
{
	private $private_key;
	private $verify_key;
	private $public_content;
	private $stamp;

	/**
	 * Authentication class
	 *
	 * @param   string  $private_key
	 *
	 * @throws Exception
	 */
	public function __construct($private_key)
	{
		$this->private_key = $private_key;

		$input                = Factory::getApplication()->input;
		$this->stamp          = $input->get('stamp', '', 'int');
		$this->verify_key     = $input->get('verify_key', '', 'base64');
		$this->public_content = $input->get('stamp', '', 'raw');
	}

	/**
	 * Check verify_key, hash_mac and timestamp
	 *
	 * @return boolean
	 */
	public function checkAuthentication()
	{
		if (!$this->verify_key)
		{
			echo json_encode('no-verification-key');
			exit;
		}

		if (!$this->checkKey())
		{
			echo json_encode('bad-authentication');
			exit;
		}

		if (!$this->validateTimestamp($this->stamp))
		{
			echo json_encode('bad-timestamp');
			exit;
		}

		return true;
	}

	/**
	 * Validate timestamp. The meaning of this check is to enhance security by
	 * making sure any token can only be used in a short period of time.
	 *
	 * @param   int  $timestamp
	 *
	 * @return boolean  true if timestamp is correct or if check is disabled in
	 *                  component options
	 */
	private function validateTimestamp($timestamp)
	{
		if (ComponentHelper::getParams('com_watchfulli')->get('disable_timestamp_check', 0))
		{
			return true;
		}

		if (($timestamp > time() - WatchfulliHelper::MAX_TIME_DEVIATION) && ($timestamp < time() + WatchfulliHelper::MAX_TIME_DEVIATION))
		{
			return true;
		}

		return false;
	}

	/**
	 * Calculate the hash from the $_POST
	 *
	 * @return string
	 */
	private function generateHash()
	{
		return hash_hmac('sha256', $this->public_content, $this->private_key);
	}

	/**
	 * Check that the provided key is valid
	 *
	 * @return bool
	 */
	private function checkKey()
	{
		$key        = $this->verify_key;
		$controlKey = $this->generateHash();
		$status     = 0;

		if (!is_string($key) || !is_string($controlKey))
		{
			return false;
		}

		$len = strlen($key);
		if ($len !== strlen($controlKey))
		{
			return false;
		}

		for ($i = 0; $i < $len; $i++)
		{
			$status |= ord($key[$i]) ^ ord($controlKey[$i]);
		}

		return $status === 0;
	}
}
