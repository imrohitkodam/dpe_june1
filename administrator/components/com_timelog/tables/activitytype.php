<?php
/**
 * @package    Com_Timelog
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;
use Joomla\Data\DataObject;

use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;

/**
 * TimelogTableactivitytype Table class
 *
 * @since  1.0.0
 */
class TimelogTableactivitytype extends Table
{
	/**
	 * Constructor
	 *
	 * @param   \DataObjectbase  &$db  A database connector object
	 */
	public function __construct(&$db)
	{
		parent::__construct('#__timelog_activity_type', 'id', $db);
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
		if (!$this->id)
		{
			$this->created_by = Factory::getUser()->id;
		}

		return parent::store($updateNulls);
	}
}
