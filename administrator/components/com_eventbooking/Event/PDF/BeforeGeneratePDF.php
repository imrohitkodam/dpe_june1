<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

namespace OSSolution\EventBooking\Admin\Event\PDF;

class BeforeGeneratePDF extends \RADEventBase
{
	protected $requiredArguments = ['pages', 'filePath', 'options'];
}