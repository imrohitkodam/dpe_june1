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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;

class plgEventbookingEasySocial extends CMSPlugin implements SubscriberInterface
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
			'onGetFields'      => 'onGetFields',
			'onGetProfileData' => 'onGetProfileData',
		];
	}

	/**
	 * Method to get list of custom fields in Jomsocial used to map with fields in Membership Pro
	 *
	 * @param   Event  $eventObj
	 *
	 * @return void
	 */
	public function onGetFields(Event $eventObj): void
	{
		$this->app->getLanguage()->load('com_easysocial', JPATH_ADMINISTRATOR);

		$db  = $this->db;
		$sql = 'SELECT unique_key AS `value`, title AS `text` FROM #__social_fields WHERE state=1 AND title != ""';
		$db->setQuery($sql);
		$rows = $db->loadObjectList();

		foreach ($rows as $row)
		{
			$row->text = Text::_($row->text);
		}

		$this->addResult($eventObj, $rows);
	}

	/**
	 * Method to get data stored in Jomsocial profile of the given user
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

		$synchronizer = new RADSynchronizerEasysocial();

		$result = $synchronizer->getData($userId, $mappings);

		$this->addResult($eventObj, $result);
	}

	/**
	 * Override registerListeners method to only register listeners if needed
	 *
	 * @return void
	 */
	public function registerListeners()
	{
		if (!ComponentHelper::isEnabled('com_easysocial'))
		{
			return;
		}

		parent::registerListeners();
	}

	/**
	 * Method to create Jomsocial account for subscriber and assign him to selected Jomsocial groups when subscription is active
	 *
	 * @param $row
	 *
	 * @return bool
	 */
	/*public function onAfterStoreRegistrant(Event $eventObj)
	{
		if (!$this->canRun)
		{
			return;
		}

		if ($row->user_id)
		{
			$db  = $this->db;
			$sql = 'SELECT COUNT(*) FROM #__social_users WHERE user_id=' . $row->user_id;
			$db->setQuery($sql);
			$count = $db->loadResult();
			if (!$count)
			{
				$sql = 'INSERT INTO #__social_users(user_id) VALUES(' . $row->user_id . ')';
				$db->setQuery($sql);
				$db->execute();
			}

			$sql = 'SELECT id, title FROM #__social_fields WHERE state=1 AND title != ""';
			$db->setQuery($sql);
			$rowFields = $db->loadObjectList();
			$fieldList = array();
			foreach ($rowFields as $rowField)
			{
				$fieldList[$rowField->fieldcode] = $rowField->id;
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

			$fieldValues = array();
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

			if (count($fieldValues))
			{
				foreach ($fieldValues as $fieldCode => $fieldValue)
				{
					if (isset($fieldList[$fieldCode]))
					{
						$fieldId = $fieldList[$fieldCode];
						if ($fieldId)
						{
							$fieldValue = $db->quote($fieldValue);
							$sql        = "INSERT INTO #__social_fields_data(uid, field_id, `data`) VALUES($row->user_id, $fieldId, $fieldValue)";
							$db->setQuery($sql);
							$db->execute();
						}
					}
				}
			}

		}

		return true;
	}*/
}
