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
 * Query Building Class.
 *
 * @since  11.1
 */
class XTF0FDatabaseQuerySqlazure extends XTF0FDatabaseQuerySqlsrv
{
	/**
	 * The character(s) used to quote SQL statement names such as table names or field names,
	 * etc.  The child classes should define this as necessary.  If a single character string the
	 * same character is used for both sides of the quoted name, else the first character will be
	 * used for the opening quote and the second for the closing quote.
	 *
	 * @var    string
	 *
	 * @since  11.1
	 */
	protected $name_quotes = '';
}
