<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;

class plgActionlogEventbooking extends CMSPlugin implements SubscriberInterface
{
	/**
	 * Application object.
	 *
	 * @var    \Joomla\CMS\Application\CMSApplication
	 */
	protected $app;

	public static function getSubscribedEvents(): array
	{
		return [
			'onAfterSaveEvent'         => 'onAfterSaveEvent',
			'onEventChangeState'       => 'onEventChangeState',
			'onEventsAfterDelete'      => 'onEventsAfterDelete',
			'onRegistrantAfterSave'    => 'onRegistrantAfterSave',
			'onRegistrantChangeState'  => 'onRegistrantChangeState',
			'onRegistrantsAfterDelete' => 'onRegistrantsAfterDelete',
			'onRegistrantsExport'      => 'onRegistrantsExport',
		];
	}

	/**
	 * Log add/edit event action
	 *
	 * @param   Event  $eventObj
	 */
	public function onAfterSaveEvent(Event $eventObj): void
	{
		/**
		 * @var EventbookingTableEvent $row
		 * @var array                  $data
		 * @var bool                   $isNew
		 */
		[$row, $data, $isNew] = array_values($eventObj->getArguments());

		$message = [];

		if ($isNew)
		{
			$messageKey = 'EB_LOG_EVENT_ADDED';
		}
		else
		{
			$messageKey = 'EB_LOG_EVENT_UPDATED';
		}

		$message['eventlink'] = 'index.php?option=com_eventbooking&view=event&id=' . $row->id;
		$message['title']     = $row->title;

		$this->addLog($message, $messageKey);
	}

	/**
	 * Log publish/unpublish event action
	 *
	 * @param   Event  $eventObj
	 *
	 * @return void
	 *
	 */
	public function onEventChangeState(Event $eventObj): void
	{
		/**
		 * @var string $context
		 * @var array  $pks
		 * @var int    $value
		 */

		[$context, $pks, $value] = array_values($eventObj->getArguments());

		$message = [];

		if ($value)
		{
			$messageKey = 'EB_LOG_EVENTS_PUBLISHED';
		}
		else
		{
			$messageKey = 'EB_LOG_EVENTS_UNPUBLISHED';
		}

		$message['ids']          = implode(',', $pks);
		$message['numberevents'] = count($pks);

		$this->addLog($message, $messageKey);
	}

	/**
	 * Log delete events action
	 *
	 * @param   Event  $eventObj
	 *
	 * @return void
	 */
	public function onEventsAfterDelete(Event $eventObj): void
	{
		/**
		 * @var string $context
		 * @var array  $pks
		 */

		[$context, $pks] = array_values($eventObj->getArguments());

		$message = [];

		$messageKey              = 'EB_LOG_EVENTS_DELETED';
		$message['ids']          = implode(',', $pks);
		$message['numberevents'] = count($pks);

		$this->addLog($message, $messageKey);
	}

	/**
	 * Log add/edit registrant action
	 *
	 * @param   Event  $eventObj
	 *
	 * @return void
	 */
	public function onRegistrantAfterSave(Event $eventObj): void
	{
		/**
		 * @var string                      $context
		 * @var EventbookingTableRegistrant $row
		 * @var bool                        $isNew
		 */

		[$context, $row, $isNew] = array_values($eventObj->getArguments());

		$message = [];

		if ($isNew)
		{
			$messageKey = 'EB_LOG_REGISTRANT_ADDED';
		}
		else
		{
			$messageKey = 'EB_LOG_REGISTRANT_UPDATED';
		}

		$message['id']             = $row->id;
		$message['name']           = trim($row->first_name . ' ' . $row->last_name);
		$message['registrantlink'] = 'index.php?option=com_eventbooking&view=registrant&id=' . $row->id;

		$this->addLog($message, $messageKey);
	}

	/**
	 * Log delete registrants action
	 *
	 * @param   Event  $eventObj
	 *
	 * @return void
	 */
	public function onRegistrantsAfterDelete(Event $eventObj): void
	{
		/**
		 * @var string $context
		 * @var array  $pks
		 */
		[$context, $pks] = array_values($eventObj->getArguments());

		$message = [];

		$messageKey                   = 'EB_LOG_REGISTRANTS_DELETED';
		$message['ids']               = implode(',', $pks);
		$message['numberregistrants'] = count($pks);

		$this->addLog($message, $messageKey);
	}

	/**
	 * Log publish/unpublish event action
	 *
	 * @param   Event  $eventObj
	 *
	 * @return void
	 */
	public function onRegistrantChangeState(Event $eventObj): void
	{
		[$context, $pks, $value] = array_values($eventObj->getArguments());

		$message = [];

		if ($value)
		{
			$messageKey = 'EB_LOG_REGISTRANTS_PUBLISHED';
		}
		else
		{
			$messageKey = 'EB_LOG_REGISTRANTS_UNPUBLISHED';
		}

		$message['ids']               = implode(',', $pks);
		$message['numberregistrants'] = count($pks);

		$this->addLog($message, $messageKey);
	}

	/**
	 * Log publish/unpublish event action
	 *
	 * @param   Event  $eventObj
	 *
	 * @return void
	 */
	public function onRegistrantsExport(Event $eventObj): void
	{
		[$state, $numberRegistrants] = array_values($eventObj->getArguments());

		$message = [];

		$message['numberregistrants'] = $numberRegistrants;

		if ($eventId = $state->get('filter_event_id'))
		{
			$messageKey    = 'EB_LOG_EVENT_REGISTRANTS_EXPORTED';
			$message['id'] = $eventId;
		}
		else
		{
			$messageKey = 'EB_LOG_REGISTRANTS_EXPORTED';
		}

		$this->addLog($message, $messageKey);
	}

	/**
	 * Override registerListeners method to only register listeners if needed
	 *
	 * @return void
	 */
	public function registerListeners()
	{
		if (!ComponentHelper::isEnabled('com_eventbooking'))
		{
			return;
		}

		// No point in logging guest actions
		if ($this->app->getIdentity()->guest)
		{
			return;
		}

		parent::registerListeners();
	}

	/**
	 * Log an action
	 *
	 * @param   array   $message
	 * @param   string  $messageKey
	 */
	private function addLog($message, $messageKey)
	{
		$user = $this->app->getIdentity();

		if (!array_key_exists('userid', $message))
		{
			$message['userid'] = $user->id;
		}

		if (!array_key_exists('username', $message))
		{
			$message['username'] = $user->username;
		}

		if (!array_key_exists('accountlink', $message))
		{
			$message['accountlink'] = 'index.php?option=com_users&task=user.edit&id=' . $user->id;
		}

		try
		{
			/** @var \Joomla\Component\Actionlogs\Administrator\Model\ActionlogModel $model */
			$model = $this->app->bootComponent('com_actionlogs')
				->getMVCFactory()->createModel('Actionlog', 'Administrator', ['ignore_request' => true]);

			$model->addLog([$message], $messageKey, 'com_eventbooking', $user->id);
		}
		catch (\Exception $e)
		{
			// Ignore any error
		}
	}
}
