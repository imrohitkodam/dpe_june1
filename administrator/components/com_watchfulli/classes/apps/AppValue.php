<?php

/**
 * @version     admin/classes/apps/AppValue.php 2024-03-27 zanardigit
 * @package     Watchful Client
 * @author      Watchful
 * @authorUrl   https://watchful.net
 * @copyright   Copyright (c) 2012-2023 Watchful
 * @license     GNU/GPL v3 or later
 */

class AppValue
{
	/**
	 * @var string Identifier for this value
	 */
	public $name;

	/**
	 * @var mixed Actual value
	 */
	public $value;

	/**
	 * @param   string  $name
	 * @param   mixed   $value
	 *
	 * @throws Exception
	 */
	public function __construct($name, $value)
	{
		if (empty($name) || empty($value))
		{
			throw new Exception("Unable to create an AppValue with empty name or value");
		}

		$this->name  = $name;
		$this->value = $value;
	}
}
