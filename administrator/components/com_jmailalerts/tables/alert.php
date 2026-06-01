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
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

/**
 * Alert table
 *
 * @since  1.0.0
 */
class JmailalertsTableAlert extends Table
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
		parent::__construct('#__jma_alerts', 'id', $db);
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
		$task  = $input->getString('task', '');

		if (($task == 'save' || $task == 'apply') && (!Factory::getUser()->authorise('core.edit.state', 'com_jmailalerts') && $array['state'] == 1))
		{
			$array['state'] = 0;
		}

		// Support for multiple field: allowed_freq
		if (is_array($array['allowed_freq']))
		{
			$array['allowed_freq'] = json_encode($array['allowed_freq']);
		}

		// DPE Hack to store the usergroup
		if (is_array($array['usergroup']))
		{
			$array['usergroup'] = json_encode($array['usergroup']);
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

		// Get the base url of images which are used in template
		// $tp_data['message_body'];
		$array['template'] = $this->getImageUrl($array['template']);

		// Apply css to template before saving template
		require_once JPATH_SITE . DS . "components" . DS . "com_jmailalerts" . DS . "models" . DS . "emogrifier.php";
		$emogr             = new TJEmogrifier($array['template'], $array['template_css']);
		$emorgdata         = $emogr->emogrify();
		$emorgdatanew      = stripslashes($emorgdata);
		$array['template'] = $emorgdatanew;

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
		$this->_db->setQuery(
				'UPDATE `' . $this->_tbl . '`' .
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

	/**
	 * Get image URL
	 * This function change relative image path and relative href path to absolute path
	 *
	 * @param   string  $templateBody  Template HTML
	 *
	 * @return  string  a string with absolute links
	 */
	public function getImageUrl($templateBody)
	{
		if (!function_exists('isRelativeUrl'))
		{
			function isRelativeUrl($str)
			{
				// If string doesn't start with http
				if (substr($str[0], 0, 4) != "http")
				{
					$str[0] = Uri::root() . $str[0];
				}

				return $str[0];
			}
		}

		// MAKE href absolute path
		$hrefpattern = "/(?<=href=(\"|'))[^\"']+(?=(\"|'))/";
		$templateBody      = preg_replace_callback($hrefpattern, 'isRelativeUrl', $templateBody);

		// MAKE <img src absolute path
		$srcpattern = "/(?<=src=(\"|'))[^\"']+(?=(\"|'))/";

		return preg_replace_callback($srcpattern, 'isRelativeUrl', $templateBody);
	}
}
