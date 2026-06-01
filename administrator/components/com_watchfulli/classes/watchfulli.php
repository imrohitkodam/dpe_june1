<?php

/**
 * @version     admin/classes/watchfulli.php 2024-03-27 zanardigit
 * @package     Watchful Client
 * @author      Watchful
 * @authorUrl   https://watchful.net
 * @copyright   Copyright (c) 2012-2023 Watchful
 * @license     GNU/GPL v3 or later
 */

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Version;

defined('_JEXEC') or die();
defined('WATCHFULLI_PATH') or die();

abstract class Watchfulli
{
	/**
	 * gets a static copy of JVersion for use in determining what platform we're in
	 *
	 * @return Version
	 */
	static public function joomla()
	{
		static $version;
		if (is_null($version))
		{
			$version = new Version();
		}

		return $version;
	}

	/**
	 * @return string
	 */
	static public function getJoomlaVersion()
	{
		return implode(
			".",
			[
				Version::MAJOR_VERSION,
				Version::MINOR_VERSION,
				Version::PATCH_VERSION,
			]
		);
	}

	/**
	 * @return bool
	 */
	static public function canUpdate()
	{
		Watchfulli::debug("We are on a Joomla version that allows remote updates");

		return true;
	}

	/**
	 * @return string
	 */
	static public function getToken()
	{
		static $token;
		if (empty($token))
		{
			$params = ComponentHelper::getParams('com_watchfulli');
			$token  = $params->get('secret_key');
		}

		return $token;
	}

	/**
	 * checks request to ensure it is from a valid source
	 *
	 * @return bool
	 * @throws Exception
	 */
	static public function checkToken()
	{
		$private_key = Watchfulli::getToken();
		Watchfulli::debug("Local secret key: $private_key");
		$authentication = new WatchfulliAuthentication($private_key);

		return $authentication->checkAuthentication();
	}

	/**
	 * Register error & exception catchers
	 */
	static public function registerErrorsCatchers()
	{
		//Remove php error reporting
		error_reporting(0);
		ini_set('error_reporting', 0);

		// Catch PHP errors
		register_shutdown_function(['Watchfulli', 'errorShutdown']);

		// Catch PHP exception
		set_exception_handler(['Watchfulli', 'exceptionHandler']);
	}

	/**
	 * Catch errors and return infos in a JSON object
	 *
	 */
	static public function errorShutdown()
	{
		$lastError     = error_get_last();
		$catchedErrors = [E_ERROR, E_PARSE];

		if (isset($_GET['debug']))
		{
			$catchedErrors = array_merge($catchedErrors, [E_WARNING, E_NOTICE]);
		}

		if ($lastError == null || !in_array($lastError['type'], $catchedErrors))
		{
			return true;
		}

		$error          = new stdClass();
		$error->status  = 'error';
		$error->type    = $lastError['type'];
		$error->message = $lastError['message'];
		$error->file    = $lastError['file'];
		$error->line    = $lastError['line'];

		echo json_encode($error);

		return true;
	}

	/**
	 * Catch Joomla exceptions and return informations in a JSON object
	 *
	 * @param   mixed<Exception|Error>  $e
	 */
	static public function exceptionHandler($e)
	{
		$error         = new stdClass();
		$error->status = 'error';
		$error->type   = 'exception';
		if ($e instanceof Exception || (class_exists('Error') && $e instanceof Error))
		{
			$error->message = $e->getMessage();
			$error->file    = $e->getFile();
			$error->line    = $e->getLine();
            $error->trace   = $e->getTraceAsString();
		}
		else
		{
			$error->message = Text::_('COM_WATCHFULLI_UNKNOWN_ERROR');
			$error->file    = __FILE__;
			$error->line    = __LINE__;
		}

		echo json_encode($error);
		exit();
	}

	/**
	 * Write additional debug informations
	 *
	 * @param   string  $message
	 */
	static public function debug($message)
	{
		if (!defined('WATCHFULLI_DEBUG'))
		{
			return;
		}

		Log::addLogger(["text_file" => "watchful.log.php"], Log::DEBUG, 'watchful');
		Log::add($message, Log::DEBUG, 'watchful');
	}

}
