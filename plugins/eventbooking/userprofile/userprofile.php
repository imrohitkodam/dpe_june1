<?php

/**
 * @package        Joomla
 * @subpackage     Events Booking
 * @author         Tuan Pham Ngoc
 * @copyright      Copyright (C) 2012 - 2015 Ossolution Team
 * @license        GNU/GPL, see LICENSE.php
 */
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Database\ParameterType;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;

class plgEventbookingUserprofile extends CMSPlugin implements SubscriberInterface
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
	 * Get list of profile fields used for mapping with fields in Events Booking
	 *
	 * @param   Event  $eventObj
	 *
	 * @return void
	 */
	public function onGetFields(Event $eventObj): void
	{
		$options = [];

		$options[] = HTMLHelper::_('select.option', 'username', 'username');
		$options[] = HTMLHelper::_('select.option', 'name', 'name');
		$options[] = HTMLHelper::_('select.option', 'email', 'email');

		if (PluginHelper::isEnabled('user', 'profile'))
		{
			$fields = ['address1', 'address2', 'city', 'region', 'country', 'postal_code', 'phone', 'website', 'favoritebook', 'aboutme', 'dob'];

			foreach ($fields as $field)
			{
				$options[] = HTMLHelper::_('select.option', $field, $field);
			}
		}

		foreach ($this->getUserFields() as $field)
		{
			$options[] = HTMLHelper::_('select.option', $field->name, $field->title);
		}

		$this->addResult($eventObj, $options);
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

		$synchronizer = new RADSynchronizerJoomla();

		$data = $synchronizer->getData($userId, $mappings);

		$fields = $this->getUserFields();

		if (count($fields))
		{
			/* @var \Joomla\Component\Fields\Administrator\Model\FieldModel $model */
			$model = $this->app->bootComponent('com_fields')->getMVCFactory()
				->createModel('Field', 'Administrator', ['ignore_request' => true]);

			$fieldIds = [];

			foreach ($mappings as $mappingFieldName)
			{
				if ($mappingFieldName && isset($fields[$mappingFieldName]))
				{
					$fieldIds[] = $fields[$mappingFieldName]->id;
				}
			}

			$fieldValues = $model->getFieldValues($fieldIds, $userId);

			foreach ($mappings as $fieldName => $mappingFieldName)
			{
				if ($mappingFieldName && isset($fields[$mappingFieldName]))
				{
					$fieldId = $fields[$mappingFieldName]->id;

					if (isset($fieldValues[$fieldId]))
					{
						$data[$fieldName] = $fieldValues[$fieldId];
					}
				}
			}
		}

		/* @var \Joomla\CMS\User\User $user */
		$user = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById((int) $userId);

		foreach ($mappings as $fieldName => $mappingFieldName)
		{
			if (in_array($mappingFieldName, ['username', 'name', 'email']))
			{
				$data[$fieldName] = $user->{$mappingFieldName};
			}
		}

		$this->addResult($eventObj, $data);
	}

	/**
	 * Store data from registration record back to user account
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

		$config = EventbookingHelper::getConfig();
		$db     = $this->db;
		$query  = $db->getQuery(true);
		$userId = $row->user_id;

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

		$userProfilePluginEnabled = PluginHelper::isEnabled('user', 'profile');
		$profileFields            = [
			'address1',
			'address2',
			'city',
			'region',
			'country',
			'postal_code',
			'phone',
			'website',
			'favoritebook',
			'aboutme',
			'dob',
		];
		$userFields               = $this->getUserFields();
		$userFieldsName           = array_keys($userFields);
		$profileFieldsMapping     = [];
		$userFieldsMapping        = [];

		foreach ($rowFields as $rowField)
		{
			if (!$rowField->field_mapping)
			{
				continue;
			}

			if ($userProfilePluginEnabled && in_array($rowField->field_mapping, $profileFields))
			{
				$profileFieldsMapping[$rowField->field_mapping] = $rowField->name;

				continue;
			}

			if (in_array($rowField->field_mapping, $userFieldsName))
			{
				$userFieldsMapping[$rowField->field_mapping] = $rowField->name;
			}
		}

		// Store user profile data
		if (count($profileFieldsMapping) > 0)
		{
			//Delete old profile data
			$fields = $keys = array_keys($profileFieldsMapping);

			for ($i = 0, $n = count($keys); $i < $n; $i++)
			{
				$keys[$i] = 'profile.' . $keys[$i];
			}

			$query->delete('#__user_profiles')
				->where('user_id = ' . $userId)
				->whereIn('profile_key', $keys, ParameterType::STRING);
			$db->setQuery($query);
			$db->execute();

			$order = 1;

			$query->clear()
				->insert('#__user_profiles');

			foreach ($fields as $field)
			{
				$fieldMapping = $profileFieldsMapping[$field];

				$value = $data[$fieldMapping] ?? '';

				$query->values(implode(',', $db->quote([$userId, 'profile.' . $field, json_encode($value), $order++])));
			}

			$db->setQuery($query);
			$db->execute();
		}

		if (count($userFields) > 0)
		{
			/* @var \Joomla\Component\Fields\Administrator\Model\FieldModel $model */
			$model = $this->app->bootComponent('com_fields')->getMVCFactory()
				->createModel('Field', 'Administrator', ['ignore_request' => true]);

			foreach ($userFields as $field)
			{
				$fieldName = $field->name;

				if (isset($userFieldsMapping[$fieldName]))
				{
					$fieldMapping = $userFieldsMapping[$fieldName];

					$fieldValue = $data[$fieldMapping] ?? '';

					if (is_string($fieldValue) && is_array(json_decode($fieldValue)))
					{
						$fieldValue = json_decode($fieldValue);
					}

					$model->setFieldValue($field->id, $userId, $fieldValue);
				}
			}
		}
	}

	/**
	 * Get list of custom fields belong to com_users
	 *
	 * @return array
	 */
	private function getUserFields(): array
	{
		$db    = $this->db;
		$query = $db->getQuery(true)
			->select('id, name, title')
			->from('#__fields')
			->where($db->quoteName('context') . '=' . $db->quote('com_users.user'))
			->where($db->quoteName('state') . ' = 1');
		$db->setQuery($query);

		return $db->loadObjectList('name');
	}

	/**
	 * Override registerListeners method to only register listeners if needed
	 *
	 * @return void
	 */
	public function registerListeners()
	{
		if (!(PluginHelper::isEnabled('user', 'profile') || count($this->getUserFields())))
		{
			return;
		}

		parent::registerListeners();
	}
}
