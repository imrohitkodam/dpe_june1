<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;
use Joomla\Registry\Registry;

class plgEventBookingFENotification extends CMSPlugin implements SubscriberInterface
{
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
			'onAfterStoreRegistrant' => 'onAfterStoreRegistrant',
			'onAfterPaymentSuccess'  => 'onAfterPaymentSuccess',
		];
	}

	/**
	 * Add registrant to Mailchimp when they perform registration uses offline payment
	 *
	 * @param   Event  $eventObj
	 *
	 * @return void
	 */
	public function onAfterStoreRegistrant(Event $eventObj): void
	{
		/* @var EventbookingTableRegistrant $row */
		[$row] = array_values($eventObj->getArguments());

		if (str_contains($row->payment_method, 'os_offline'))
		{
			$this->sendFullEventNotification($row);
		}
	}

	/**
	 * Add registrants to Mailchimp when payment for registration completed or registration is approved
	 *
	 * @param   Event  $eventObj
	 *
	 * @return void
	 */
	public function onAfterPaymentSuccess(Event $eventObj): void
	{
		/* @var EventbookingTableRegistrant $row */
		[$row] = array_values($eventObj->getArguments());

		if (!str_contains($row->payment_method, 'os_offline'))
		{
			$this->sendFullEventNotification($row);
		}
	}

	/**
	 *  Send full event notification to emails list
	 *
	 * @param   EventbookingTableRegistrant  $row
	 *
	 * @return void
	 */
	private function sendFullEventNotification($row): void
	{
		// Do not email if the user joins waiting list
		if ($row->published == 3)
		{
			return;
		}

		$event = EventbookingHelperDatabase::getEvent($row->event_id, null, null, true);

		if (!$event->event_capacity || ($event->event_capacity > $event->total_registrants))
		{
			return;
		}

		$params = new Registry($event->params);

		// Do not send notification if it was sent before already
		if ($params->get('fe_notification_sent'))
		{
			return;
		}

		$subject            = $this->params->get('subject');
		$body               = $this->params->get('message');
		$notificationEmails = trim($this->params->get('notification_emails', ''));

		if (!$subject || !$notificationEmails)
		{
			return;
		}

		$config = EventbookingHelper::getConfig();
		$mailer = EventbookingHelperMail::getMailer($config);

		$replaces = EventbookingHelperRegistration::buildEventTags($event, $config);

		foreach ($replaces as $key => $value)
		{
			$value   = (string) $value;
			$subject = str_ireplace("[$key]", $value, $subject);
			$body    = str_ireplace("[$key]", $value, $body);
		}

		EventbookingHelperMail::send($mailer, explode(',', $notificationEmails), $subject, $body, true);

		$params->set('fe_notification_sent', 1);
		$db    = $this->db;
		$query = $db->getQuery(true)
			->update('#__eb_events')
			->set($db->quoteName('params') . ' = ' . $db->quote($params->toString()))
			->where('id = ' . $event->id);
		$db->setQuery($query)
			->execute();
	}
}
