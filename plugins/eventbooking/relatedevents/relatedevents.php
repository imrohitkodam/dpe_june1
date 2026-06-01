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
use Joomla\Utilities\ArrayHelper;

class plgEventBookingRelatedEvents extends CMSPlugin implements SubscriberInterface
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
			'onEditEvent'      => 'onEditEvent',
			'onAfterSaveEvent' => 'onAfterSaveEvent',
			'onEventDisplay'   => 'onEventDisplay',
		];
	}

	/**
	 * Constructor.
	 *
	 * @param   \Joomla\Event\DispatcherInterface  $subject
	 * @param   array                              $config
	 */
	public function __construct($subject, $config)
	{
		parent::__construct($subject, $config);

		$this->app->getLanguage()->load('plg_eventbooking_relatedevents', JPATH_ADMINISTRATOR);
	}

	/**
	 * Render setting form
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
			'title' => Text::_('PLG_EB_RELATED_EVENTS'),
			'form'  => ob_get_clean(),
		];

		$this->addResult($eventObj, $result);
	}

	/**
	 * Store setting into database, in this case, use params field of events table
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

		$params->set('related_event_ids', $data['related_event_ids'] ?? '');

		$row->params = $params->toString();

		$row->store();
	}


	/**
	 * Display event speakers
	 *
	 * @param   Event  $eventObj
	 *
	 * @return void
	 */
	public function onEventDisplay(Event $eventObj): void
	{
		/* @var EventbookingTableEvent $row */
		[$row] = array_values($eventObj->getArguments());

		$params   = new Registry($row->params);
		$eventIds = $params->get('related_event_ids', '');

		if (!$eventIds)
		{
			return;
		}

		$eventIds = array_filter(ArrayHelper::toInteger(explode(',', $eventIds)));

		if (count($eventIds) === 0)
		{
			return;
		}

		// Get list of events
		/* @var EventbookingModelList $model */
		$model = RADModel::getTempInstance('List', 'EventbookingModel', ['table' => '#__eb_events']);

		$model->setState('event_ids', $params->get('related_event_ids', ''));
		$events = $model->getData();

		if (empty($events))
		{
			return;
		}

		// Prepare display data
		$config = EventbookingHelper::getConfig();
		$Itemid = EventbookingHelper::getItemid();

		EventbookingHelper::callOverridableHelperMethod('Data', 'prepareDisplayData', [$events, 0, $config, $Itemid]);

		$result = [
			'title'    => Text::_('EB_EVENT_RELATED_EVENTS'),
			'form'     => EventbookingHelperHtml::loadCommonLayout('plugins/relatedevents.php', ['events' => $events, 'params' => $this->params]),
			'position' => $this->params->get('output_position', 'before_register_buttons'),
			'name'     => $this->_name,
		];

		$this->addResult($eventObj, $result);
	}

	/**
	 * Display form allows users to change settings on subscription plan add/edit screen
	 *
	 * @param   EventbookingTableEvent  $row
	 *
	 * @return void
	 */
	private function drawSettingForm($row): void
	{
		if ($row->id)
		{
			$params          = new Registry($row->params);
			$relatedEventIds = $params->get('related_event_ids');
		}
		else
		{
			$relatedEventIds = '';
		}

		require PluginHelper::getLayoutPath($this->_type, $this->_name, 'form');
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
