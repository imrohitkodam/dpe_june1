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
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Database\ParameterType;

class PlgUserEventbooking extends CMSPlugin
{
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
	 * Utility method to act on a user after it has been saved.
	 *
	 * This method creates a subscription record for the saved user
	 *
	 * @param   array   $user     Holds the new user data.
	 * @param   bool    $isnew    True if a new user is stored.
	 * @param   bool    $success  True if user was successfully stored in the database.
	 * @param   string  $msg      Message.
	 *
	 * @return  void
	 *
	 * @since   2.6.0
	 */
	public function onUserAfterSave($user, $isnew, $success, $msg)
	{
		if (!$this->params->get('synchronize_data'))
		{
			return;
		}

		// If the user wasn't stored we don't resync
		if (!$success)
		{
			return;
		}

		// If the user isn't new we don't sync
		if ($isnew)
		{
			return;
		}

		// Ensure the user id is really an int
		$userId = (int) $user['id'];

		// If the user id appears invalid then bail out just in case
		if (empty($userId))
		{
			return;
		}

		$app    = $this->app;
		$option = $app->getInput()->getCmd('option');
		$task   = $app->getInput()->getCmd('task');

		if ($app->isClient('site') || $app->isClient('administrator'))
		{
			// Only update data if data is updated via com_users
			if ($option != 'com_users')
			{
				return;
			}

			if ($app->isClient('administrator') && !in_array($task, ['save', 'apply', 'save2new']))
			{
				return;
			}

			if ($app->isClient('site') && $task != 'save')
			{
				return;
			}
		}

		if (!PluginHelper::isEnabled('eventbooking', 'userprofile'))
		{
			return;
		}

		$userFields = $this->getUserFields();

		if (count($userFields) == 0)
		{
			return;
		}

		$db    = $this->db;
		$query = $db->getQuery(true)
			->select('id')
			->from('#__eb_registrants')
			->where('user_id = ' . $userId)
			->where('group_id = 0')
			->where('(published = 1 OR payment_method LIKE "os_offline%")');
		$db->setQuery($query);
		$registrantIds = $db->loadColumn();

		if (!count($registrantIds))
		{
			return;
		}

		$this->updateRegistrationsFromUserCustomFields($user, $registrantIds, $userFields);
	}

	/**
	 * Remove all subscriptions for the user if configured
	 *
	 * Method is called after user data is deleted from the database
	 *
	 * @param   array    $user     Holds the user data
	 * @param   boolean  $success  True if user was successfully stored in the database
	 * @param   string   $msg      Message
	 *
	 * @return  boolean
	 *
	 * @since   1.6
	 */
	public function onUserAfterDelete($user, $success, $msg)
	{
		/* @var \Joomla\Database\DatabaseDriver $db */
		$db     = $this->db;
		$query  = $db->getQuery(true);
		$userId = (int) $user['id'];

		if (!$userId)
		{
			return true;
		}

		if ($this->params->get('delete_user_events'))
		{
			$query->select('id')
				->from('#__eb_events')
				->where('created_by = ' . $userId);
			$db->setQuery($query);
			$eventIds = $db->loadColumn();

			if (count($eventIds))
			{
				$this->deleteEvents($eventIds);
			}
		}

		if ($this->params->get('delete_user_registrations'))
		{
			$query->clear()
				->select('id')
				->from('#__eb_registrants')
				->where('user_id = ' . $userId);
			$db->setQuery($query);
			$registrantIds = $db->loadColumn();

			if (count($registrantIds))
			{
				$this->deleteRegistrants($registrantIds);
			}
		}

		return true;
	}

	/**
	 * Method to delete events
	 *
	 * @param   array  $cid
	 */
	private function deleteEvents($cid)
	{
		$db    = $this->db;
		$query = $db->getQuery(true);
		$query->select('id')
			->from('#__eb_events')
			->whereIn('parent_id', $cid);
		$db->setQuery($query);
		$cid = array_merge($cid, $db->loadColumn());

		//Delete price setting for events
		$query->clear()
			->delete('#__eb_event_group_prices')
			->whereIn('event_id', $cid);
		$db->setQuery($query);
		$db->execute();

		//Delete categories for the event
		$query->clear()
			->delete('#__eb_event_categories')
			->whereIn('event_id', $cid);
		$db->setQuery($query);
		$db->execute();

		// Delete ticket types related to events
		$query->clear()
			->delete('#__eb_ticket_types')
			->whereIn('event_id', $cid);
		$db->setQuery($query);
		$db->execute();

		// Delete the URLs related to event
		$query->clear()
			->delete('#__eb_urls')
			->where($db->quoteName('view') . '=' . $db->quote('event'))
			->whereIn('record_id', $cid);
		$db->setQuery($query);
		$db->execute();

		//Delete events themself
		$query->clear()
			->delete('#__eb_events')
			->whereIn('id', $cid);
		$db->setQuery($query);
		$db->execute();
	}

	/**
	 * Method to delete given registrants
	 *
	 * @param   array  $cid
	 *
	 * @return bool
	 */
	private function deleteRegistrants($cid = [])
	{
		$db    = $this->db;
		$query = $db->getQuery(true)
			->select('id')
			->from('#__eb_registrants')
			->whereIn('group_id', $cid);
		$db->setQuery($query);

		$registrantIds = array_merge($cid, $db->loadColumn());

		$query->clear()
			->delete('#__eb_field_values')
			->whereIn('registrant_id', $registrantIds);
		$db->setQuery($query)
			->execute();

		$query->clear()
			->delete('#__eb_registrants')
			->whereIn('id', $registrantIds);
		$db->setQuery($query)
			->execute();

		$query->clear()
			->delete('#__eb_registrant_tickets')
			->whereIn('registrant_id', $registrantIds);
		$db->setQuery($query);

		return true;
	}

	/**
	 * Get list of custom fields belong to com_users
	 *
	 * @return array
	 */
	private function getUserFields()
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
		if (!ComponentHelper::isEnabled('com_eventbooking'))
		{
			return;
		}

		parent::registerListeners();
	}

	/***
	 * Update subscription data from user custom fields data
	 *
	 * @param   array  $user
	 * @param   array  $registrantIds
	 * @param   array  $userFields
	 */
	private function updateRegistrationsFromUserCustomFields($user, $registrantIds, $userFields)
	{
		// Require library + register autoloader
		require_once JPATH_ADMINISTRATOR . '/components/com_eventbooking/libraries/rad/bootstrap.php';

		$userFieldIds           = [];
		$userFieldNames         = [];
		$userFieldNameIdMapping = [];

		foreach ($userFields as $field)
		{
			$userFieldIds[]                       = $field->id;
			$userFieldNames[]                     = $field->name;
			$userFieldNameIdMapping[$field->name] = $field->id;
		}

		$db    = $this->db;
		$query = $db->getQuery(true)
			->select('id, name, field_mapping, is_core')
			->from('#__eb_fields')
			->where('published = 1')
			->whereIn('field_mapping', $userFieldNames, ParameterType::STRING);
		$db->setQuery($query);
		$rowFields = $db->loadObjectList();

		// No fields are mapped, don't process further
		if (!count($rowFields))
		{
			return;
		}

		$userId = (int) $user['id'];

		/* @var \Joomla\Component\Fields\Administrator\Model\FieldModel $model */
		$model = $this->app->bootComponent('com_fields')->getMVCFactory()
			->createModel('Field', 'Administrator', ['ignore_request' => true]);

		$userFieldValues = $model->getFieldValues($userFieldIds, $userId);

		foreach ($registrantIds as $registrantId)
		{
			$rowRegistrant = new EventbookingTableRegistrant($this->db);
			$rowRegistrant->load($registrantId);
			$rowRegistrant->email = $user['email'];

			$query->clear()
				->select('*')
				->from('#__eb_field_values')
				->where('registrant_id = ' . $registrantId);
			$db->setQuery($query);
			$fieldValues = $db->loadObjectList('field_id');

			foreach ($rowFields as $rowField)
			{
				if (!isset($userFieldNameIdMapping[$rowField->field_mapping]))
				{
					continue;
				}

				$userFieldId = $userFieldNameIdMapping[$rowField->field_mapping];

				if (array_key_exists($userFieldId, $userFieldValues))
				{
					$userFieldValue = $userFieldValues[$userFieldId];
				}
				else
				{
					$userFieldValue = '';
				}

				if (is_array($userFieldValue))
				{
					$userFieldValue = json_encode($userFieldValue);
				}

				if ($rowField->is_core)
				{
					$rowRegistrant->{$rowField->name} = $userFieldValue;
				}
				elseif (array_key_exists($rowField->id, $fieldValues))
				{
					// Field is already exist, update
					$query->clear()
						->update('#__eb_field_values')
						->set('field_value = ' . $db->quote($userFieldValue))
						->where('id = ' . $fieldValues[$rowField->id]->id);
					$db->setQuery($query)
						->execute();
				}
				else
				{
					// Field is not existed, insert new record
					$query->clear()
						->insert('#__eb_field_values')
						->columns($db->quoteName(['registrant_id', 'field_id', 'field_value']))
						->values(implode(',', $db->quote([$registrantId, $rowField->id, $userFieldValue])));
					$db->setQuery($query)
						->execute();
				}
			}

			// Store change back to the registration record
			$rowRegistrant->store();
		}
	}
}
