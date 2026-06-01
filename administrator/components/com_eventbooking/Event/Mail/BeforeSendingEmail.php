<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

namespace OSSolution\EventBooking\Admin\Event\Mail;

use Joomla\CMS\Mail\Mail;

class BeforeSendingEmail extends \RADEventBase
{
	protected $requiredArguments = ['mailer', 'subject', 'body'];

	public function __construct(array $arguments = [])
	{
		parent::__construct('onEBBeforeSendingEmail', $arguments);
	}

	/**
	 * Get mailer object
	 *
	 * @return Mail
	 */
	public function getMailerObject()
	{
		return $this->getArgument('mailer');
	}

	/**
	 * Get subject of the email
	 *
	 * @return string
	 */
	public function getEmailSubject(): string
	{
		return $this->getArgument('subject');
	}

	/**
	 * Get email body
	 *
	 * @return string
	 */
	public function getEmailBody(): string
	{
		return $this->getArgument('body');
	}
}