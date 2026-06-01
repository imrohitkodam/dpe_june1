<?php
/**
 * @package     XT Transitional Package from FrameworkOnFramework
 * @subpackage  database
 *  @copyright   Copyright (c)2012-2020 Extly, CB. All rights reserved. / Based on FrameworkOnFramework of Akeeba
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 *
 * This file is adapted from the Joomla! Platform. It is used to iterate a database cursor returning XTF0FTable objects
 * instead of plain stdClass objects
 */

// Protect from unauthorized access
defined('XTF0F_INCLUDED') or die;

/**
 * PDO database iterator.
 */
class XTF0FDatabaseIteratorPdo extends XTF0FDatabaseIterator
{
	/**
	 * Get the number of rows in the result set for the executed SQL given by the cursor.
	 *
	 * @return  integer  The number of rows in the result set.
	 *
	 * @see     Countable::count()
	 */
	public function count()
	{
		if (!empty($this->cursor) && $this->cursor instanceof PDOStatement)
		{
			return @$this->cursor->rowCount();
		}
		else
		{
			return 0;
		}
	}

	/**
	 * Method to fetch a row from the result set cursor as an object.
	 *
	 * @return  mixed   Either the next row from the result set or false if there are no more rows.
	 */
	protected function fetchObject()
	{
		if (!empty($this->cursor) && $this->cursor instanceof PDOStatement)
		{
			return @$this->cursor->fetchObject($this->class);
		}
		else
		{
			return false;
		}
	}

	/**
	 * Method to free up the memory used for the result set.
	 *
	 * @return  void
	 */
	protected function freeResult()
	{
		if (!empty($this->cursor) && $this->cursor instanceof PDOStatement)
		{
			@$this->cursor->closeCursor();
		}
	}
}
