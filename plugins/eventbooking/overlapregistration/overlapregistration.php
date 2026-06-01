<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;

class plgEventbookingOverlapRegistration extends CMSPlugin implements SubscriberInterface
{
	use RADEventResult;

	/**
	 * Application object
	 *
	 * @var \Joomla\CMS\Application\CMSApplication
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
			'onEBCheckAcceptRegistration' => 'onEBCheckAcceptRegistration',
			'onEBValidateFormData'        => 'onEBValidateFormData',
		];
	}

	/**
	 * Check to see if the event is still accept registration
	 *
	 * @param   Event  $eventObj
	 *
	 * @return void
	 */
	public function onEBCheckAcceptRegistration(Event $eventObj): void
	{
		/* @var EventbookingTableEvent $event */
		[$event] = array_values($eventObj->getArguments());

		$user = $this->app->getIdentity();

		// Do not check further if user is not logged in
		if (!$user->id)
		{
			return;
		}

		if ($this->checkOverlap($event, $user->id))
		{
			$event->cannot_register_reason = 'overlap_registration';

			$this->addResult($eventObj, false);
		}
	}

	/**
	 * @param   Event  $eventObj
	 *
	 * @return void
	 */
	public function onEBValidateFormData(Event $eventObj): void
	{
		/**
		 * @var stdClass $event
		 * @var int      $registrationType
		 * @var array    $data
		 */
		[$event, $registrationType, $data] = array_values($eventObj->getArguments());

		$errors = [];
		$user   = $this->app->getIdentity();

		if ($user->id)
		{
			$isOverlap = $this->checkOverlap($event, $user->id);
		}
		else
		{
			$email = $data['email'] ?? '';

			if ($email)
			{
				$isOverlap = $this->checkOverlap($event, 0, $email);
			}
			else
			{
				$isOverlap = false;
			}
		}

		if ($isOverlap)
		{
			$errors[] = Text::_('EB_OVERLAP_REGISTRATION_DETECTED');
			$this->addResult($eventObj, $errors);
		}
	}

	/**
	 * Check to see if this event causes overlap registration with existing registered events
	 *
	 * @param   EventbookingTableEvent  $event
	 * @param   int                     $userId
	 * @param   string                  $email
	 *
	 * @return bool
	 */
	private function checkOverlap($event, $userId = 0, $email = ''): bool
	{
		$db           = $this->db;
		$eventDate    = $db->quote($event->event_date);
		$eventEndDate = $db->quote($event->event_end_date);

		$query = $db->getQuery(true)
			->select('COUNT(*)')
			->from('#__eb_registrants')
			->where('event_id != ' . $event->id)
			->where('(published = 1 OR (published = 0 AND payment_method LIKE "os_offline%"))');

		if ($userId > 0)
		{
			$query->where('user_id = ' . $userId);
		}
		else
		{
			$query->where('email = ' . $db->quote($email));
		}

		$eventsQuery = $db->getQuery(true)
			->select('ev.id FROM #__eb_events AS ev')
			->where('ev.published = 1');

		$whereOrs = [];

		if ((int) $event->event_end_date)
		{
			/**
			 * There are 3 cases:
			 *
			 * 1. event_date is between the start and end date of the provided event
			 * 2. event_end_date is between the start and end date of the provided event
			 * 3. the provided event is between start date and end date of the event
			 */
			$whereOrs[] = "(ev.event_date >= $eventDate AND ev.event_date <= $eventEndDate)";
			$whereOrs[] = "(ev.event_end_date >= $eventDate AND ev.event_end_date <= $eventEndDate)";
			$whereOrs[] = "(ev.event_date <= $eventDate AND ev.event_end_date >= $eventEndDate)";
		}
		else
		{
			// There is only event date, in this case, the event date must be between start date and end date if the filtered events
			$whereOrs[] = '(ev.event_date <= ' . $eventDate . ' AND ev.event_end_date >= ' . $eventDate . ')';
		}

		$eventsQuery->where('(' . implode(' OR ', $whereOrs) . ')');

		$query->where('event_id IN (' . $eventsQuery . ')');
		$db->setQuery($query);

		if ($db->loadResult() > 0)
		{
			return true;
		}

		return false;
	}
}
