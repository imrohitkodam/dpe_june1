<?php
/**
 * @package     RAD
 * @subpackage  Event
 *
 * @copyright   Copyright (C) 2015 Ossolution Team, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

use Joomla\CMS\Event;

abstract class RADEventBase extends Event\AbstractEvent
{
	/**
	 * Required arguments for event
	 *
	 * @var array
	 */
	protected $requiredArguments = [];

	/**
	 * Constructor
	 *
	 * @param   string  $name
	 * @param   array   $arguments
	 *
	 * @throws \BadMethodCallException
	 */
	public function __construct(string $name, array $arguments = [])
	{
		if (count($this->requiredArguments) > 0)
		{
			foreach ($this->requiredArguments as $argument)
			{
				if (!\array_key_exists($argument, $arguments))
				{
					throw new \BadMethodCallException("Argument '{$argument}' of event {$name} is required but has not been provided");
				}
			}
		}

		parent::__construct($name, $arguments);
	}
}