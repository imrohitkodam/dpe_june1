<?php
/**
 * @package    Com_Timelog
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2018 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;
use Joomla\Data\DataObject;

use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;

/**
 * TimelogTableactivity Table class
 *
 * @since  1.0.0
 */
class TimelogTableactivity extends Table
{
	/**
	 * The activity created date and time
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	public $created_date = '';

	/**
	 * The activity modified date and time
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	public $modified_date = '';

	/**
	 * joomla user id of the creator
	 *
	 * @var    integer
	 * @since  1.0.0
	 */
	public $created_by = 0;

	/**
	 * joomla user id of the modifier
	 *
	 * @var    integer
	 * @since  1.0.0
	 */
	public $modified_by = 0;

	/**
	 * Constructor
	 *
	 * @param   DataObjectbase  &$db  A database connector object
	 */
	public function __construct(&$db)
	{
		parent::__construct('#__timelog_activities', 'id', $db);
		$this->setColumnAlias('published', 'state');
	}

	/**
	 * Overrides Table::store to set modified data and user id.
	 *
	 * @param   boolean  $updateNulls  True to update fields even if they are null.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   1.0.0
	 */
	public function store($updateNulls = false)
	{
		$date = Factory::getDate();
		$user = Factory::getUser();

		if ($this->id)
		{
			$this->modified_by = $user->id;
			$this->modified_date = $date->toSql();
		}
		else
		{
			$this->created_by = $this->modified_by = $user->id;
		}

		return parent::store($updateNulls);
	}
}
