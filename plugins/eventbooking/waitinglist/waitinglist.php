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

class plgEventBookingWaitinglist extends CMSPlugin implements SubscriberInterface
{
	use RADEventResult;

	/**
	 * Application object.
	 *
	 * @var    \Joomla\CMS\Application\CMSApplication
	 */
	protected $app;

	/**
	 * @return array
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			'onEventDisplay' => 'onEventDisplay',
		];
	}

	/**
	 * Display event's waiting list
	 *
	 * @param   Event  $eventObj
	 *
	 * @return void
	 */
	public function onEventDisplay(Event $eventObj): void
	{
		/* @var EventbookingTableEvent $row */
		[$row] = array_values($eventObj->getArguments());

		// Require library + register autoloader
		require_once JPATH_ADMINISTRATOR . '/components/com_eventbooking/libraries/rad/bootstrap.php';

		if (!EventbookingHelper::callOverridableHelperMethod('Acl', 'canViewRegistrantList', [$row->id]))
		{
			return;
		}

		EventbookingHelper::loadLanguage();
		$request = [
			'option'          => 'com_eventbooking',
			'view'            => 'registrantlist',
			'id'              => $row->id,
			'registrant_type' => 3,
			'hmvc_call'       => 1,
			'Itemid'          => $this->app->getInput()->getInt('Itemid'),
			'limit'           => 1000,
		];
		$input   = new RADInput($request);
		$config  = require JPATH_ADMINISTRATOR . '/components/com_eventbooking/config.php';
		ob_start();

		//Initialize the controller, execute the task
		RADController::getInstance('com_eventbooking', $input, $config)
			->execute();

		$form = ob_get_clean();

		$result = [
			'title'    => Text::_('EB_WAITING_LIST'),
			'form'     => $form,
			'name'     => $this->_name,
			'position' => $this->params->get('output_position', 'before_register_buttons'),
		];

		$this->addResult($eventObj, $result);
	}
}
