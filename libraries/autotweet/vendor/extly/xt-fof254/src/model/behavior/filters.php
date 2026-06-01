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
class XTF0FModelBehaviorFilters extends XTF0FModelBehavior
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
		$table = $model->getTable();
		$tableName = $table->getTableName();
		$tableKey = $table->getKeyName();
		$db = $model->getDBO();

		$filterzero = $model->getState('_emptynonzero', null);

		$fields = $model->getTableFields();
		$backlist = $model->blacklistFilters();

		foreach ($fields as $fieldname => $fieldtype)
		{
			if (in_array($fieldname, $backlist)) {
				continue;
			}
			$field = new stdClass;
			$field->name = $fieldname;
			$field->type = $fieldtype;
			$field->filterzero = $filterzero;

			$filterName = ($field->name == $tableKey) ? 'id' : $field->name;
			$filterState = $model->getState($filterName, null);

			$field = XTF0FModelField::getField($field, array('dbo' => $db, 'table_alias' => $model->getTableAlias()));

			if ((is_array($filterState) && (
					array_key_exists('value', $filterState) ||
					array_key_exists('from', $filterState) ||
					array_key_exists('to', $filterState)
				)) || is_object($filterState))
			{
				$options = new JRegistry($filterState);
			}
			else
			{
				$options = new JRegistry;
				$options->set('value', $filterState);
			}

			$methods = $field->getSearchMethods();
			$method = $options->get('method', $field->getDefaultSearchMethod());

			if (!in_array($method, $methods))
			{
				$method = 'exact';
			}

			switch ($method)
			{
				case 'between':
				case 'outside':
				case 'range' :
					$sql = $field->$method($options->get('from', null), $options->get('to'));
					break;

				case 'interval':
				case 'modulo':
					$sql = $field->$method($options->get('value', null), $options->get('interval'));
					break;

				case 'exact':
				case 'partial':
				case 'search':
				default:
					$sql = $field->$method($options->get('value', null));
					break;
			}

			if ($sql)
			{
				$query->where($sql);
			}
		}
	}
}
