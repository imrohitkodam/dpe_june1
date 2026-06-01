<?php
/**
 * @version     admin/classes/apps.php 2024-03-27 zanardigit
 * @package     Watchful Client
 * @author      Watchful
 * @authorUrl   https://watchful.net
 * @copyright   Copyright (c) 2012-2023 Watchful
 * @license     GNU/GPL v3 or later
 */

defined('_JEXEC') or die();
defined('WATCHFULLI_PATH') or die();

require_once __DIR__ . '/apps/AppAlert.php';
require_once __DIR__ . '/apps/AppValue.php';

/**
 * @abstract
 * @see JPlugin
 */
abstract class WatchfulliApps extends JPlugin
{
	public $name;
	public $description;
	public $values = [];
	public $alerts = [];

	/**
	 * @param   string  $name
	 */
	public function setName($name)
	{
		$this->name = $name;
	}

	/**
	 * @param   string  $description
	 */
	public function setDescription($description)
	{
		$this->description = $description;
	}

	/**
	 * @param   AppValue  $value
	 *
	 * @return boolean
	 */
	public function addValue($value)
	{
		if ($value != null)
		{
			$this->values[$value->name] = $value;

			return true;
		}

		return false;
	}

	/**
	 * @param AppAlert $alert
	 */
	public function addAlert($alert)
	{
		if ($alert != null)
		{
			$this->alerts[] = $alert;
		}
	}

	/**
	 * @param   string  $name
	 * @param   mixed   $value
	 *
	 * @return boolean
	 * @throws Exception
	 * @throws Exception
	 */
	public function createAppValue($name, $value)
	{
		return $this->addValue(new AppValue($name, $value));
	}

	/**
	 * Returns an app value.
	 *
	 * @param   string  $name
	 *
	 * @return AppValue|null
	 */
	public function readAppValue($name)
	{
		if ($name == null)
		{
			return null;
		}

		return $this->values[$name];
	}

	/**
	 * Updates an existing App value.
	 * Returns TRUE if updated, FALSE otherwise.
	 *
	 * @param   string  $name
	 * @param   mixed   $newVal
	 *
	 * @return boolean
	 */
	public function updateAppValue($name, $newVal)
	{
		if ($name == null || $newVal == null)
		{
			return false;
		}

		$value = $this->readAppValue($name);
		if ($value == null)
		{
			return false;
		}

		$value->value = $newVal;

		return true;
	}

	/**
	 * Deletes an existing App value.
	 * Returns TRUE if deleted, FALSE otherwise.
	 *
	 * @param   string  $name
	 *
	 * @return boolean
	 */
	public function deleteAppValue($name)
	{
		if ($name == null)
		{
			return false;
		}

		unset($this->values[$name]);

		return true;
	}

	/**
	 * Get a value from the string that contains the previous plugins values
	 *
	 * @param   string  $exValues
	 *
	 * @return String
	 * @throws Exception
	 */
	public function readExAppValue($exValues)
	{
		if ($this->name == null)
		{
			return null;
		}

		$exValues = unserialize(str_replace('0000000000', ' ', $exValues));
		if ($exValues == null)
		{
			return null;
		}

		foreach ($exValues as $plugin)
		{
			if ($plugin['name'] == $this->name)
			{
				$this->createAppValue($plugin['name'], $plugin['value']);

				return $plugin['value'];
			}
		}

		return null;
	}

	/**
	 * @param   int     $level
	 * @param   string  $message
	 * @param   string  $parameter1
	 * @param   string  $parameter2
	 * @param   string  $parameter3
	 *
	 * @throws Exception
	 * @throws Exception
	 */
	public function createAppAlert($level, $message, $parameter1 = null, $parameter2 = null, $parameter3 = null)
	{
		$alert = new AppAlert($level, $message, $parameter1, $parameter2, $parameter3);
		$this->addAlert($alert);
	}
}

