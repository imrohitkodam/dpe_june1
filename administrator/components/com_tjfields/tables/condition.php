<?php
/**
 * @package    Tjfields
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2023 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Table\Table;
use Joomla\CMS\Factory;

/**
 * Table class for selfassessment
 *
 * @package  Emc
 *
 * @since    __DEPLOY_VERSION__
 */
class TjfieldsTableCondition extends Table
{
	/**
	 * Constructor
	 *
	 * @param   \JDatabaseDriver  &$db  \JDatabaseDriver object.
	 */
	public function __construct(&$db)
	{
		parent::__construct('#__tjfields_fields_conditions', 'id', $db);
		$this->setColumnAlias('published', 'state');
	}

	/**
	 * Method to store a row in the database from the Table instance properties.
	 *
	 * If a primary key value is set the row with that primary key value will be updated with the instance property values.
	 * If no primary key value is set a new row will be inserted into the database with the properties from the Table instance.
	 *
	 * @param   boolean  $updateNulls  True to update fields even if they are null.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   1.7.0
	 */
	public function store($updateNulls = false)
	{
		$date = Factory::getDate();
		$user = Factory::getUser();

		if ($this->id)
		{
			// Existing item
			$this->modified_date = $date->toSql();
			$this->modified_by = $user->get('id');
		}
		else
		{
			// So we don't touch either of these if they are set.
			if (!(int) $this->created_date)
			{
				$this->created_date = $date->toSql();
			}

			if (empty($this->created_by))
			{
				$this->created_by = $user->get('id');
			}
		}

		return parent::store($updateNulls);
	}
}
