<?php

/**
 * @version     admin/classes/apps/AppAlert.php 2020-05-27 zanardigit
 * @package     Watchful Client
 * @author      Watchful
 * @authorUrl   https://watchful.net
 * @copyright   Copyright (c) 2012-2023 Watchful
 * @license     GNU/GPL v3 or later
 */

/**
 * This class manages AppAlert. A AppAlert has a level and a message and a few parameters.
 * The parameters will be passed as variables to language strings
 */
class AppAlert
{
	/**
	 * Alert importance level:
	 * "1" means that the alert is an information.
	 * "2" means that the alert is an error.
	 *
	 * @var int
	 */
	public $level;

	/**
	 * Alert message.
	 *
	 * @var string
	 */
	public $message;

	/**
	 * Optional parameter.
	 *
	 * @var mixed
	 */
	public $parameter1;

	/**
	 * Optional parameter.
	 *
	 * @var mixed
	 */
	public $parameter2;

	/**
	 * Optional parameter.
	 *
	 * @var mixed
	 */
	public $parameter3;

	/**
	 * AppAlert constructor.
	 *
	 * @param         $level
	 * @param         $message
	 * @param   null  $parameter1
	 * @param   null  $parameter2
	 * @param   null  $parameter3
	 *
	 * @throws Exception
	 */
	public function __construct($level, $message, $parameter1 = null, $parameter2 = null, $parameter3 = null)
	{
		if (empty($level) || empty($message))
		{
			throw new Exception("Unable to create an AppAlert with empty level or message");
		}

		$this->level      = $level;
		$this->message    = $message;
		$this->parameter1 = $parameter1;
		$this->parameter2 = $parameter2;
		$this->parameter3 = $parameter3;
	}
}
