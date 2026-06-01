<?php
/**
 * @package     JMailAlerts
 * @subpackage  com_jmailalerts
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2022 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// Do not allow direct access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Access\Rules;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

/**
 * Subscriber table
 *
 * @since  1.0.0
 */
class JmailalertsTablesubscriber extends Table
{
	/**
	 * Constructor
	 *
	 * @param   \JDatabaseDriver  $db  Database driver object.
	 *
	 * @since   1.7.0
	 */
	public function __construct($db)
	{
		parent::__construct('#__jma_subscribers', 'id', $db);
	}

	/**
	 * Overloaded bind function
	 *
	 * @param   array  $array   Named array
	 * @param   mixed  $ignore  An optional array or space separated list of properties
	 * to ignore while binding.
	 *
	 * @return  mixed  Null if operation was satisfactory, otherwise returns an error
	 *
	 * @see     Table::bind()
	 * @since   1.7.0
	 */
	public function bind($array, $ignore = '')
	{
		$input = Factory::getApplication()->input;

		$task = $input->getString('task', '');

		if (($task == 'save' || $task == 'apply') && (!Factory::getUser()->authorise('core.edit.state', 'com_jmailalerts') && $array['state'] == 1))
		{
			$array['state'] = 0;
		}
		// @TODO - Make validation that User should not subcribe one alert more than one time (Means no duplicate entries of alert against user)
		elseif (($task == 'save' || $task == 'apply' || $task == 'save2new' || $task == 'save2copy'))
		{
			$db    = Factory::getDBO();
			$query = $db->getQuery(true);
			$query->SELECT('id, alert_id');
			$query->from('`#__jma_subscribers` AS subs');

			// If is not guest then check alert subcribed against user id
			if (!$array['user_id'] == 0)
			{
				$query->where('alert_id=' . $array['alert_id'] . ' AND user_id=' . $array['user_id']);
			}
			else
			{
				// If is guest then check alert subcribed against user email id
				$query->where("alert_id=" . $array['alert_id'] . " AND email_id='" . $array['email_id'] . "'");
			}

			$db->setQuery($query);
			$result = $db->loadColumn();

			if ($array['id'])
			{
				// Check for update the user alert, if alert present against the user id then skip the count 1 (means updating the alert)
				// Otherwise avoid to add same alert agains the user
				if (count($result) > 1)
				{
					$this->setError(Text::_('COM_JMAILALERTS_DUPLICATE_ALERT_ERROR'));

					return false;
				}
			}
			elseif (count($result) >= 1)
			{
				// Check for adding the new alert against the user , if alert present against user id then it will give atleast one count of ids
				// & avoid to add same alert agains the user
				$this->setError(Text::_('COM_JMAILALERTS_DUPLICATE_ALERT_ERROR'));

				return false;
			}

			if ($array['user_id'] != 0)
			{
				// If subscription is new or user details not present then get user name & email id $array['name']=user name
				$query = $db->getQuery(true);
				$query->SELECT('id,name,email');
				$query->from('`#__users`');
				$query->where('id=' . $array['user_id']);
				$db->setQuery($query);
				$userData = $db->loadObject();

				if ($userData)
				{
					$array['name']     = $userData->name;
					$array['email_id'] = $userData->email;
				}
				else
				{
					// This 'user id' is not present in our database, Please check the 'user id' !
					$this->setError(Text::_('COM_JMAILALERTS_USER_NOT_PRESENT_ERROR'));

					return false;
				}
			}

			if (!$array['id'])
			{
				// Adding the new alert against the user then add plugins for the user
				$manageUserHelper               = new ManageUserHelper;
				$userData                       = $manageUserHelper->subscribeUser($array);
				$array['plugins_subscribed_to'] = $userData['plugins_subscribed_to'];
				$array['date']                  = $userData['date'];
			}
		}

		if (isset($array['params']) && is_array($array['params']))
		{
			$registry = new Registry;
			$registry->loadArray($array['params']);
			$array['params'] = (string) $registry;
		}

		if (isset($array['metadata']) && is_array($array['metadata']))
		{
			$registry = new Registry;
			$registry->loadArray($array['metadata']);
			$array['metadata'] = (string) $registry;
		}

		// Bind the rules.
		if (isset($array['rules']) && is_array($array['rules']))
		{
			$rules = new Rules($array['rules']);
			$this->setRules($rules);
		}

		return parent::bind($array, $ignore);
	}

	/**
	 * Method to set the publishing state for a row or list of rows in the database
	 * table.  The method respects checked out rows by other users and will attempt
	 * to checkin rows that it can after adjustments are made.
	 *
	 * @param   mixed    $pks     An optional array of primary key values to update.  If not
	 *                            set the instance property value is used.
	 * @param   integer  $state   The publishing state. eg. [0 = unpublished, 1 = published]
	 * @param   integer  $userId  The user id of the user performing the operation.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   1.7.0
	 */
	public function publish($pks = null, $state = 1, $userId = 0)
	{
		// Initialise variables.
		$k = $this->_tbl_key;

		// Sanitize input.
		ArrayHelper::toInteger($pks);
		$userId = (int) $userId;
		$state  = (int) $state;

		// If there are no primary keys set check to see if the instance key is set.
		if (empty($pks))
		{
			if ($this->$k)
			{
				$pks = array($this->$k);
			}
			// Nothing to set publishing state on, return false.
			else
			{
				$this->setError(Text::_('JLIB_DATABASE_ERROR_NO_ROWS_SELECTED'));

				return false;
			}
		}

		// Build the WHERE clause for the primary keys.
		$where = $k . '=' . implode(' OR ' . $k . '=', $pks);

		// Determine if there is checkin support for the table.
		if (!property_exists($this, 'checked_out') || !property_exists($this, 'checked_out_time'))
		{
			$checkin = '';
		}
		else
		{
			$checkin = ' AND (checked_out = 0 OR checked_out = ' . (int) $userId . ')';
		}

		// Update the publishing state for rows with the given primary keys.
		$this->_db->setQuery('
			UPDATE `' . $this->_tbl . '`' .
			' SET `state` = ' . (int) $state .
			' WHERE (' . $where . ')' .
			$checkin
		);

		// Check for a database error.
		try
		{
			$this->_db->execute();
		}
		catch (\RuntimeException $e)
		{
			$this->setError($e->getMessage());

			return false;
		}

		// If checkin is supported and all rows were adjusted, check them in.
		if ($checkin && (count($pks) == $this->_db->getAffectedRows()))
		{
			// Checkin each row.
			foreach ($pks as $pk)
			{
				$this->checkin($pk);
			}
		}

		// If the Table instance value is in the list of primary keys that were set, set the instance.
		if (in_array($this->$k, $pks))
		{
			$this->state = $state;
		}

		$this->setError('');

		return true;
	}
}
