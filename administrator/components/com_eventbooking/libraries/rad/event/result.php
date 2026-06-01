<?php
/**
 * @package     RAD
 * @subpackage  Event
 *
 * @copyright   Copyright (C) 2015 Ossolution Team, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

use Joomla\Event\Event;

trait RADEventResult
{
	/**
	 * Set return result for event trigger
	 *
	 * @param   Event  $event
	 * @param   mixed  $data
	 *
	 * @return void
	 */
	public function setResult(Event $event, $data)
	{
		$event->setArgument('result', $data);
	}

	/**
	 * Set result for event trigger
	 *
	 * @param   Event  $event
	 * @param   mixed  $data
	 *
	 * @return void
	 */
	public function addResult(Event $event, $data)
	{
		$result   = $event->getArgument('result', []);
		$result[] = $data;
		$event->setArgument('result', $result);
	}
}