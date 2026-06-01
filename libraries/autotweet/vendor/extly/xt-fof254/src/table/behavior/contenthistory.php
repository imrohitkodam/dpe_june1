<?php
/**
 * @package     XT Transitional Package from FrameworkOnFramework
 * @subpackage  table
 *  @copyright   Copyright (c)2012-2020 Extly, CB. All rights reserved. / Based on FrameworkOnFramework of Akeeba
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
// Protect from unauthorized access
defined('XTF0F_INCLUDED') or die;

/**
 * FrameworkOnFramework table behavior class for content History
 *
 * @package  FrameworkOnFramework
 * @since    2.2.0
 */
class XTF0FTableBehaviorContenthistory extends XTF0FTableBehavior
{
	/**
	 * The event which runs after storing (saving) data to the database
	 *
	 * @param   XTF0FTable  &$table  The table which calls this event
	 *
	 * @return  boolean  True to allow saving without an error
	 */
	public function onAfterStore(&$table)
	{
		$aliasParts = explode('.', $table->getContentType());
		$table->checkContentType();

		if (JComponentHelper::getParams($aliasParts[0])->get('save_history', 0))
		{
			$historyHelper = new JHelperContenthistory($table->getContentType());
			$historyHelper->store($table);
		}

		return true;
	}

	/**
	 * The event which runs before deleting a record
	 *
	 * @param   XTF0FTable &$table  The table which calls this event
	 * @param   integer  $oid  The PK value of the record to delete
	 *
	 * @return  boolean  True to allow the deletion
	 */
	public function onBeforeDelete(&$table, $oid)
	{
		$aliasParts = explode('.', $table->getContentType());

		if (JComponentHelper::getParams($aliasParts[0])->get('save_history', 0))
		{
			$historyHelper = new JHelperContenthistory($table->getContentType());
			$historyHelper->deleteHistory($table);
		}

		return true;
	}
}
