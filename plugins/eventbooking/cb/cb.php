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
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;

class plgEventBookingCB extends CMSPlugin implements SubscriberInterface
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
			'onAfterStoreRegistrant' => 'onAfterStoreRegistrant',
			'onGetFields'            => 'onGetFields',
			'onGetProfileData'       => 'onGetProfileData',
		];
	}

	/**
	 * Method to get list of custom fields in Community builder used to map with fields in Membership Pro
	 *
	 * @param   Event  $eventObj
	 *
	 * @return void
	 */
	public function onGetFields(Event $eventObj): void
	{
		$db  = $this->db;
		$sql = 'SELECT name AS `value`, name AS `text` FROM #__comprofiler_fields WHERE `table`="#__comprofiler"';
		$db->setQuery($sql);

		$this->addResult($eventObj, $db->loadObjectList());
	}

	/**
	 * Method to get data stored in CB profile of the given user
	 *
	 * @param   Event  $eventObj
	 *
	 * @return void
	 */
	public function onGetProfileData(Event $eventObj): void
	{
		/**
		 * @var int   $userId
		 * @var array $mappings
		 */
		[$userId, $mappings] = array_values($eventObj->getArguments());

		$synchronizer = new RADSynchronizerCommunitybuilder();

		$result = $synchronizer->getData($userId, $mappings);

		$this->addResult($eventObj, $result);
	}

	/**
	 * Update CB profile data with information which registrant entered on registration form
	 *
	 * @param   Event  $eventObj
	 *
	 * @return void
	 */
	public function onAfterStoreRegistrant(Event $eventObj): void
	{
		/* @var EventbookingTableRegistrant $row */
		[$row] = array_values($eventObj->getArguments());

		if (!$row->user_id || !$this->params->get('update_cb_data', '1'))
		{
			return;
		}

		$db  = $this->db;
		$sql = 'SELECT count(*) FROM `#__comprofiler` WHERE `user_id` = ' . $db->quote($row->user_id);
		$db->setQuery($sql);
		$count = $db->loadResult();
		$sql   = ' SHOW FIELDS FROM #__comprofiler ';
		$db->setQuery($sql);
		$fields    = $db->loadObjectList();
		$fieldList = [];

		foreach ($fields as $field)
		{
			$fieldList[] = $field->Field;
		}

		$config = EventbookingHelper::getConfig();

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

		$data = EventbookingHelperRegistration::getRegistrantData($row, $rowFields);

		$fieldValues = [];

		foreach ($rowFields as $rowField)
		{
			if ($rowField->field_mapping && in_array($rowField->field_mapping, $fieldList) && isset($data[$rowField->name]))
			{
				$fieldValue = $data[$rowField->name];

				if (is_string($fieldValue) && is_array(json_decode($fieldValue)))
				{
					$fieldValues[$rowField->field_mapping] = implode('|*|', json_decode($fieldValue));
				}
				else
				{
					$fieldValues[$rowField->field_mapping] = $fieldValue;
				}
			}
		}

		$profile = new stdClass();

		$profile->id        = $row->user_id;
		$profile->user_id   = $row->user_id;
		$profile->firstname = $row->first_name;
		$profile->lastname  = $row->last_name;

		if (!$config->use_cb_api)
		{
			$profile->confirmed      = 1;
			$profile->avatarapproved = 1;
			$profile->registeripaddr = htmlspecialchars($_SERVER['REMOTE_ADDR']);
			$profile->banned         = 0;
			$profile->acceptedterms  = 1;
		}

		foreach ($fieldValues as $fieldName => $value)
		{
			$profile->{$fieldName} = $value;
		}

		if ($count)
		{
			$db->updateObject('#__comprofiler', $profile, 'id');
		}
		else
		{
			$db->insertObject('#__comprofiler', $profile);
		}
	}

	/**
	 * Override registerListeners method to only register listeners if needed
	 *
	 * @return void
	 */
	public function registerListeners()
	{
		if (!ComponentHelper::isEnabled('com_comprofiler'))
		{
			return;
		}

		parent::registerListeners();
	}
}
