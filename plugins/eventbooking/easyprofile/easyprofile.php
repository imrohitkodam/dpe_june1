<?php

/**
 * @package        Joomla
 * @subpackage     Events Booking
 * @author         Tuan Pham Ngoc
 * @copyright      Copyright (C) 2010 - 2024 Ossolution Team
 * @license        GNU/GPL, see LICENSE.php
 */
defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;

class plgEventbookingEasyprofile extends CMSPlugin implements SubscriberInterface
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
			'onGetFields'            => 'onGetFields',
			'onGetProfileData'       => 'onGetProfileData',
			'onAfterStoreRegistrant' => 'onAfterStoreRegistrant',
		];
	}

	/**
	 * Method to get data stored in EasyProfile of the given user
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

		$synchronizer = new RADSynchronizerEasyprofile();

		$result = $synchronizer->getData($userId, $mappings);

		$this->addResult($eventObj, $result);
	}

	/**
	 * Method to get list of custom fields in Easyprofile used to map with fields in Membership Pro
	 *
	 * @param   Event  $eventObj
	 *
	 * @return void
	 */
	public function onGetFields(Event $eventObj): void
	{
		$db     = $this->db;
		$fields = array_keys($db->getTableColumns('#__jsn_users'));
		$fields = array_diff($fields, ['id', 'params']);

		$options = [];

		foreach ($fields as $field)
		{
			$options[] = HTMLHelper::_('select.option', $field, $field);
		}

		$this->addResult($eventObj, $options);
	}

	/**
	 * Method to create a CB account for subscriber if it does not exist yet
	 *
	 * @param   Event  $eventObj
	 *
	 * @return void
	 */
	public function onAfterStoreRegistrant(Event $eventObj): void
	{
		/* @var EventbookingTableRegistrant $row */
		[$row] = array_values($eventObj->getArguments());

		if (!$row->user_id)
		{
			return;
		}

		$db = $this->db;

		// Check if user exist
		$query = $db->getQuery(true);
		$query->select('a.id')->from('#__jsn_users AS a')->where('a.id = ' . $row->user_id);
		$db->setQuery($query);
		$profileId = $db->loadResult();

		// Get list of fields in #__jsn_users table
		$fieldList = array_keys($db->getTableColumns('#__jsn_users'));

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

		if (!count($fieldValues))
		{
			return;
		}

		// Write Jsn User
		if ($profileId)
		{
			// Update User
			$query = $db->getQuery(true);
			$query->update('#__jsn_users');

			foreach ($fieldValues as $key => $value)
			{
				$query->set($db->quoteName($key) . ' = ' . $db->quote($value));
			}

			$query->where('id = ' . $row->user_id);
			$db->setQuery($query);
			$db->execute();
		}
		else
		{
			// New User
			$fields = [];
			$values = [];

			foreach ($fieldValues as $key => $value)
			{
				$fields[] = $db->quoteName($key);
				$values[] = $db->quote($value);
			}

			$query = 'INSERT INTO #__jsn_users(id,' . implode(', ', $fields) . ') VALUES(' . $row->user_id . ', ' . implode(', ', $values) . ')';
			$db->setQuery($query);
			$db->execute();
		}
	}

	/**
	 * Override registerListeners method to only register listeners if needed
	 *
	 * @return void
	 */
	public function registerListeners()
	{
		if (!ComponentHelper::isEnabled('com_jsn'))
		{
			return;
		}

		parent::registerListeners();
	}
}
