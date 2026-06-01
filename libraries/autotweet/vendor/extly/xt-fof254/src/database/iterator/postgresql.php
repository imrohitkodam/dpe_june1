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
 * PostgreSQL database iterator.
 */
class XTF0FDatabaseIteratorPostgresql extends XTF0FDatabaseIterator
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
		return @pg_num_rows($this->cursor);
	}

	/**
	 * Method to fetch a row from the result set cursor as an object.
	 *
	 * @return  mixed   Either the next row from the result set or false if there are no more rows.
	 */
	protected function fetchObject()
	{
		return @pg_fetch_object($this->cursor, null, $this->class);
	}

	/**
	 * Method to free up the memory used for the result set.
	 *
	 * @return  void
	 */
	protected function freeResult()
	{
		@pg_free_result($this->cursor);
	}
}
