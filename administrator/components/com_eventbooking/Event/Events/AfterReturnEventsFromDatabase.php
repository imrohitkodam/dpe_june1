<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

namespace OSSolution\EventBooking\Admin\Event\Events;

class AfterReturnEventsFromDatabase extends \RADEventBase
{
	protected $requiredArguments = ['rows'];

	public function __construct(array $arguments = [])
	{
		parent::__construct('onAfterReturnEventsFromDatabase', $arguments);
	}
}