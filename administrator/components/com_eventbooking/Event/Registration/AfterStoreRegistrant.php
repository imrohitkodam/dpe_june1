<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

namespace OSSolution\EventBooking\Admin\Event\Registration;

class AfterStoreRegistrant extends \RADEventBase
{
	protected $requiredArguments = ['row'];

	/**
	 * Get the registration record which payment processed
	 *
	 * @return \EventbookingTableRegistrant
	 */
	public function getRegistrant()
	{
		return $this->getArgument('row');
	}
}