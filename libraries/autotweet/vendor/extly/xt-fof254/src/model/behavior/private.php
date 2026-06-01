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
 * FrameworkOnFramework model behavior class to filter front-end access to items
 * craeted by the currently logged in user only.
 *
 * @package  FrameworkOnFramework
 * @since    2.1
 */
class XTF0FModelBehaviorPrivate extends XTF0FModelBehavior
{
	/**
	 * This event runs after we have built the query used to fetch a record
	 * list in a model. It is used to apply automatic query filters.
	 *
	 * @param   XTF0FModel        &$model  The model which calls this event
	 * @param   XTF0FDatabaseQuery  &$query  The model which calls this event
	 *
	 * @return  void
	 */
	public function onAfterBuildQuery(&$model, &$query)
	{
		// This behavior only applies to the front-end.
		if (!XTF0FPlatform::getInstance()->isFrontend())
		{
			return;
		}

		// Get the name of the access field
		$table = $model->getTable();
		$createdField = $table->getColumnAlias('created_by');

		// Make sure the access field actually exists
		if (!in_array($createdField, $table->getKnownFields()))
		{
			return;
		}

		// Get the current user's id
		$user_id = XTF0FPlatform::getInstance()->getUser()->id;

		// And filter the query output by the user id
		$db    = XTF0FPlatform::getInstance()->getDbo();

		$alias = $model->getTableAlias();
		$alias = $alias ? $db->qn($alias) . '.' : '';

		$query->where($alias . $db->qn($createdField) . ' = ' . $db->q($user_id));
	}

	/**
	 * The event runs after XTF0FModel has called XTF0FTable and retrieved a single
	 * item from the database. It is used to apply automatic filters.
	 *
	 * @param   XTF0FModel  &$model   The model which was called
	 * @param   XTF0FTable  &$record  The record loaded from the databae
	 *
	 * @return  void
	 */
	public function onAfterGetItem(&$model, &$record)
	{
		if ($record instanceof XTF0FTable)
		{
			$keyName = $record->getKeyName();
			if ($record->$keyName === null)
			{
				return;
			}

			$fieldName = $record->getColumnAlias('created_by');

			// Make sure the field actually exists
			if (!in_array($fieldName, $record->getKnownFields()))
			{
				return;
			}

			$user_id = XTF0FPlatform::getInstance()->getUser()->id;

			if ($record->$fieldName != $user_id)
			{
				$record = null;
			}
		}
	}
}
