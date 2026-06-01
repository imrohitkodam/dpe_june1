<?php
/**
 * @package     XT Transitional Package from FrameworkOnFramework
 * @subpackage  model
 *  @copyright   Copyright (c)2012-2020 Extly, CB. All rights reserved. / Based on FrameworkOnFramework of Akeeba
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
// Protect from unauthorized access
defined('XTF0F_INCLUDED') or die;

/**
 * FrameworkOnFramework model behavior class
 *
 * @package  FrameworkOnFramework
 * @since    2.1
 */
class XTF0FModelFieldText extends XTF0FModelField
{
	/**
	 * Constructor
	 *
	 * @param   XTF0FDatabaseDriver  $db     The database object
	 * @param   object           $field  The field informations as taken from the db
	 */
	public function __construct($db, $field, $table_alias = false)
	{
		parent::__construct($db, $field, $table_alias);

		$this->null_value = '';
	}

	/**
	 * Returns the default search method for this field.
	 *
	 * @return  string
	 */
	public function getDefaultSearchMethod()
	{
		return 'partial';
	}

	/**
	 * Perform a partial match (search in string)
	 *
	 * @param   mixed  $value  The value to compare to
	 *
	 * @return  string  The SQL where clause for this search
	 */
	public function partial($value)
	{
		if ($this->isEmpty($value))
		{
			return '';
		}

		return '(' . $this->getFieldName() . ' LIKE ' . $this->_db->quote('%' . $value . '%') . ')';
	}

	/**
	 * Perform an exact match (match string)
	 *
	 * @param   mixed  $value  The value to compare to
	 *
	 * @return  string  The SQL where clause for this search
	 */
	public function exact($value)
	{
		if ($this->isEmpty($value))
		{
			return '';
		}

		return '(' . $this->getFieldName() . ' LIKE ' . $this->_db->quote($value) . ')';
	}

	/**
	 * Dummy method; this search makes no sense for text fields
	 *
	 * @param   mixed    $from     Ignored
	 * @param   mixed    $to       Ignored
	 * @param   boolean  $include  Ignored
	 *
	 * @return  string  Empty string
	 */
	public function between($from, $to, $include = true)
	{
		return '';
	}

	/**
	 * Dummy method; this search makes no sense for text fields
	 *
	 * @param   mixed    $from     Ignored
	 * @param   mixed    $to       Ignored
	 * @param   boolean  $include  Ignored
	 *
	 * @return  string  Empty string
	 */
	public function outside($from, $to, $include = false)
	{
		return '';
	}

	/**
	 * Dummy method; this search makes no sense for text fields
	 *
	 * @param   mixed    $value     Ignored
	 * @param   mixed    $interval  Ignored
	 * @param   boolean  $include   Ignored
	 *
	 * @return  string  Empty string
	 */
	public function interval($value, $interval, $include = true)
	{
		return '';
	}

	/**
	 * Dummy method; this search makes no sense for text fields
	 *
	 * @param   mixed    $from     Ignored
	 * @param   mixed    $to       Ignored
	 * @param   boolean  $include  Ignored
	 *
	 * @return  string  Empty string
	 */
	public function range($from, $to, $include = false)
	{
		return '';
	}

	/**
	 * Dummy method; this search makes no sense for text fields
	 *
	 * @param   mixed    $from     Ignored
	 * @param   mixed    $to       Ignored
	 * @param   boolean  $include  Ignored
	 *
	 * @return  string  Empty string
	 */
	public function modulo($from, $to, $include = false)
	{
		return '';
	}
}
