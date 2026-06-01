<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\Mail\MailHelper;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;

class plgEventbookingCheckedinNotification extends CMSPlugin implements SubscriberInterface
{
	/**
	 * Application object.
	 *
	 * @var    \Joomla\CMS\Application\CMSApplication
	 */
	protected $app;

	/**
	 * Database object.
	 *
	 * @var    \Joomla\Database\DatabaseDriver
	 */
	protected $db;

	/**
	 * @return array
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			'onAfterCheckinRegistrant' => 'onAfterCheckinRegistrant',
		];
	}

	/**
	 * Send notification to registrant after successful checked in
	 *
	 * @param   Event  $eventObj
	 *
	 * @return void
	 */
	public function onAfterCheckinRegistrant(Event $eventObj): void
	{
		/**
		 * @var EventbookingTableRegistrant $row
		 * @var bool                        $success
		 * @var bool                        $result
		 */

		[$row, $success, $result] = array_values($eventObj->getArguments());

		// Only send notification if checked in success
		if (!$success)
		{
			return;
		}

		$subject = trim($this->params->get('subject', ''));
		$body    = $this->params->get('message', '');
		$email   = trim($row->email);

		if (!$subject || !MailHelper::isEmailAddress($email))
		{
			return;
		}

		$config = EventbookingHelper::getConfig();
		$mailer = EventbookingHelperMail::getMailer($config);

		$replaces = EventbookingHelperRegistration::getRegistrationReplaces($row, null, $this->app->getIdentity()->id);

		foreach ($replaces as $key => $value)
		{
			$value   = (string) $value;
			$subject = str_ireplace("[$key]", $value, $subject);
			$body    = str_ireplace("[$key]", $value, $body);
		}

		EventbookingHelperMail::send($mailer, [$email], $subject, $body, true, EventbookingHelperMail::SEND_TO_REGISTRANT, 'checked_in_notification');
	}
}
