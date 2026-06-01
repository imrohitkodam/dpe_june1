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
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;
use Joomla\Registry\Registry;
use OSSolution\EventBooking\Admin\Event\Registration\AfterPaymentSuccess;
use OSSolution\EventBooking\Admin\Event\Registration\AfterStoreRegistrant;

class plgEventBookingMoveRegistrants extends CMSPlugin implements SubscriberInterface
{
	use RADEventResult;

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
			'onEditEvent'             => 'onEditEvent',
			'onAfterSaveEvent'        => 'onAfterSaveEvent',
			'onRegistrationCancelled' => 'onRegistrationCancelled',
		];
	}

	/**
	 * Render settings form
	 *
	 * @param   Event  $eventObj
	 *
	 * @return void
	 */
	public function onEditEvent(Event $eventObj): void
	{
		/* @var EventbookingTableEvent $row */
		[$row] = array_values($eventObj->getArguments());

		if (!$this->canRun($row))
		{
			return;
		}

		ob_start();
		$this->drawSettingForm($row);

		$result = [
			'title' => Text::_('EB_MOVE_REGISTRANTS_SETTINGS'),
			'form'  => ob_get_clean(),
		];

		$this->addResult($eventObj, $result);
	}

	/**
	 * Store setting into database, in this case, use params field of plans table
	 *
	 * @param   Event  $eventObj
	 *
	 * @return void
	 */
	public function onAfterSaveEvent(Event $eventObj): void
	{
		/**
		 * @var EventbookingTableEvent $row
		 * @var array                  $data
		 * @var bool                   $isNew
		 */
		[$row, $data, $isNew] = array_values($eventObj->getArguments());

		if (!$this->canRun($row))
		{
			return;
		}

		$params = new Registry($row->params);

		$params->set('enable_move_registrants', $data['enable_move_registrants']);

		$row->params = $params->toString();

		$row->store();
	}

	/**
	 * Move potential users from waiting list to registrants
	 *
	 * @param   Event  $eventObj
	 *
	 * @return void
	 */
	public function onRegistrationCancelled(Event $eventObj): void
	{
		/**
		 * @var EventbookingTableRegistrant $row
		 * @var int                         $published
		 */

		[$row, $published] = array_values($eventObj->getArguments());

		if (!$this->needToProcess($row, $published))
		{
			return;
		}

		$event = EventbookingHelperDatabase::getEvent($row->event_id, null, null, true);

		$params = new Registry($event->params);

		if (!$params->get('enable_move_registrants', 1))
		{
			return;
		}

		if ($this->isEventFull($event))
		{
			return;
		}

		$app              = $this->app;
		$db               = $this->db;
		$query            = $db->getQuery(true);
		$config           = EventBookingHelper::getConfig();
		$totalRegistrants = 0;
		$now              = gmdate('Y-m-d H:i:s');

		$published = $this->params->get('move_registrant_status', 1);

		while ($totalRegistrants < $row->number_registrants)
		{
			$remainingNumberRegistrants = $row->number_registrants - $totalRegistrants;
			$query->clear()
				->select('id')
				->from('#__eb_registrants')
				->where('event_id = ' . $row->event_id)
				->where('published = 3')
				->where('number_registrants <= ' . $remainingNumberRegistrants)
				->order('id');
			$db->setQuery($query, 0, 1);
			$id = (int) $db->loadResult();

			if (!$id)
			{
				break;
			}

			$registrant = new EventbookingTableRegistrant($this->db);
			$registrant->load($id);
			$registrant->register_date = $now;

			if ($registrant->number_registrants >= 2)
			{
				$registrant->is_group_billing = 1;
			}

			$registrant->published = $published;

			if ($published == 0)
			{
				$registrant->payment_method = 'os_offline';
			}

			$registrant->store();

			if ($registrant->number_registrants >= 2)
			{
				$numberRegistrants = $registrant->number_registrants;

				$rowMember = new EventbookingTableRegistrant($this->db);

				for ($i = 0; $i < $numberRegistrants; $i++)
				{
					$rowMember->id                 = 0;
					$rowMember->group_id           = $registrant->id;
					$rowMember->number_registrants = 1;
					$rowMember->published          = $published;

					if ($published == 0)
					{
						$rowMember->payment_method = 'os_offline';
					}

					$rowMember->register_date = $now;
					$rowMember->store();
				}
			}

			$eventObj = new AfterStoreRegistrant(
				'onAfterStoreRegistrant',
				['row' => $registrant]
			);

			$app->triggerEvent('onAfterStoreRegistrant', $eventObj);

			$eventObj = new AfterPaymentSuccess(
				'onAfterPaymentSuccess',
				['row' => $registrant]
			);

			$app->triggerEvent('onAfterPaymentSuccess', $eventObj);

			EventbookingHelperMail::sendEmails($registrant, $config);

			if ($registrant->number_registrants)
			{
				$totalRegistrants += $registrant->number_registrants;
			}
			else
			{
				$totalRegistrants++;
			}
		}
	}

	/**
	 * Display form allows users to change settings on event add/edit screen
	 *
	 * @param   EventbookingTableEvent  $row
	 *
	 * @return void
	 */
	private function drawSettingForm($row): void
	{
		require PluginHelper::getLayoutPath($this->_type, $this->_name, 'form');
	}

	/**
	 * Check to see if we need to move registrants from waiting list
	 *
	 * @param   EventbookingTableRegistrant  $row
	 * @param   int                          $published
	 *
	 * @return bool
	 */
	private function needToProcess($row, $published): bool
	{
		if ($published == 1 || ($published == 0 && str_contains($row->payment_method, 'os_offline')))
		{
			return true;
		}

		return false;
	}

	/**
	 * Method to check if the event is full
	 *
	 * @param   EventbookingTableEvent  $event
	 *
	 * @return bool
	 */
	private function isEventFull($event): bool
	{
		if ($event->event_capacity > 0 && $event->event_capacity <= $event->total_registrants)
		{
			return true;
		}

		return false;
	}

	/**
	 * Method to check to see whether the plugin should run
	 *
	 * @param   EventbookingTableEvent  $row
	 *
	 * @return bool
	 */
	private function canRun($row): bool
	{
		if ($this->app->isClient('site') && !$this->params->get('show_on_frontend'))
		{
			return false;
		}

		return true;
	}
}
