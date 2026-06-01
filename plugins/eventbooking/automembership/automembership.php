<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

class plgEventbookingAutoMembership extends CMSPlugin implements SubscriberInterface
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
			'onEditEvent'           => 'onEditEvent',
			'onAfterSaveEvent'      => 'onAfterSaveEvent',
			'onAfterPaymentSuccess' => 'onAfterPaymentSuccess',
		];
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
			'title' => Text::_('EB_AUTO_MEMBERSHIP'),
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

		// The plugin will only be available in the backend
		if (!$this->canRun($row))
		{
			return;
		}

		$params = new Registry($row->params);
		$params->set('auto_membership_plan_ids', implode(',', $data['auto_membership_plan_ids'] ?? []));
		$row->params = $params->toString();
		$row->store();
	}

	/**
	 * Generate invoice number after registrant complete payment for registration
	 *
	 * @param   Event  $eventObj
	 *
	 * @return void
	 */
	public function onAfterPaymentSuccess(Event $eventObj): void
	{
		/* @var EventbookingTableRegistrant $row */
		[$row] = array_values($eventObj->getArguments());

		// Do not handle this if registrant does not have user account or he is a group member
		if (!$row->user_id || $row->group_id > 0)
		{
			return;
		}

		$params = new Registry($row->params);

		// This was processed before, do not process further
		if ($params->get('auto_membership_processed'))
		{
			return;
		}

		$db    = $this->db;
		$query = $db->getQuery(true);

		$config = EventbookingHelper::getConfig();

		if ($config->multiple_booking)
		{
			$query->select('*')
				->from('#__eb_registrants')
				->where('(id = ' . $row->id . ' OR cart_id = ' . $row->id . ')');
			$db->setQuery($query);
			$rowRegistrants = $db->loadObjectList();
		}
		else
		{
			$rowRegistrants = [$row];
		}

		$autoSubscribePlanIds = [];

		foreach ($rowRegistrants as $rowRegistrant)
		{
			$event   = EventbookingHelperDatabase::getEvent($rowRegistrant->event_id);
			$params  = new Registry($event->params);
			$planIds = $params->get('auto_membership_plan_ids', '');

			// This event is not configured to subscribe registrants to Membership Plans
			if (empty($planIds))
			{
				continue;
			}

			$planIds = array_filter(ArrayHelper::toInteger(explode(',', $planIds)));

			if (count($planIds))
			{
				$autoSubscribePlanIds = array_merge_recursive($autoSubscribePlanIds, $planIds);
			}
		}

		// No plan to subscribe
		if (count($autoSubscribePlanIds) === 0)
		{
			return;
		}

		// Get data from registration records, map it to the fields in Membership Pro base on fields mapping
		if ($config->multiple_booking)
		{
			$rowFields = EventbookingHelperRegistration::getFormFields($row->id, 4);
		}
		elseif ($row->is_group_billing)
		{
			$rowFields = EventbookingHelperRegistration::getFormFields($row->event_id, 1);
		}
		else
		{
			$rowFields = EventbookingHelperRegistration::getFormFields($row->event_id, 0);
		}

		$registrantData = EventbookingHelperRegistration::getRegistrantData($row, $rowFields);

		$subscriptionData = [];

		foreach ($rowFields as $rowField)
		{
			if ($rowField->field_mapping && array_key_exists($rowField->name, $registrantData))
			{
				$subscriptionData[$rowField->field_mapping] = $registrantData[$rowField->name];
			}
		}

		// No fields mapped, do not process it further
		if (!count($subscriptionData))
		{
			return;
		}

		if (!array_key_exists('email', $subscriptionData))
		{
			$subscriptionData['email'] = $row->email;
		}

		// Require library + register autoloader
		require_once JPATH_ADMINISTRATOR . '/components/com_osmembership/loader.php';

		foreach ($autoSubscribePlanIds as $planId)
		{
			$data            = $subscriptionData;
			$data['plan_id'] = $planId;

			$model = new OSMembershipModelApi();

			try
			{
				$model->store($data);
			}
			catch (Exception $e)
			{
				// Ignore error for now
			}
		}

		$params->set('auto_membership_processed', 1);
		$row->params = $params->toString();
		$row->store();
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
		$params = new Registry($row->params);

		$db    = $this->db;
		$query = $db->getQuery(true)
			->select('id, title')
			->from('#__osmembership_plans')
			->where('published = 1')
			->order('ordering');
		$db->setQuery($query);

		$lists['auto_membership_plan_ids'] = HTMLHelper::_(
			'select.genericlist',
			$db->loadObjectList(),
			'auto_membership_plan_ids[]',
			'multiple class="form-select"',
			'id',
			'title',
			explode(',', $params->get('auto_membership_plan_ids', ''))
		);

		require PluginHelper::getLayoutPath($this->_type, $this->_name, 'form');
	}

	/**
	 * Override registerListeners method to only register listeners if needed
	 *
	 * @return void
	 */
	public function registerListeners()
	{
		if (!ComponentHelper::isEnabled('com_osmembership'))
		{
			return;
		}

		parent::registerListeners();
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
