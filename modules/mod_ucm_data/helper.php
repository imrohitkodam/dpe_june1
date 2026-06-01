<?php
/**
 * @package     UCM
 * @subpackage  module-ucm
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 * Helper for mod_ucm_data
 *
 * @since  1.0
 */
abstract class ModUcmHelper
{
	/**
	 * Get a list of the UCM data
	 *
	 * @param   \Joomla\Registry\Registry  &$params  object holding the models parameters
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	public static function getList(&$params)
	{
		// Get the data needed
		$data      = array();
		$jinput    = Factory::getApplication()->input;
		$listLimit = $params->get('limit');
		$ucmTypeId = $params->get('ucmtypename');

		if (!$ucmTypeId || !$listLimit)
		{
			return $data;
		}

		// Get the data used for first time data display
		JLoader::import('components.com_tjucm.tables.type', JPATH_ADMINISTRATOR);
		$ucmTypeTable = Table::getInstance('type', 'TjucmTable');
		$ucmTypeTable->load($ucmTypeId);
		JLoader::import('components.com_tjucm.models.items', JPATH_SITE);
		$itemsModel = BaseDatabaseModel::getInstance('Items', 'TjucmModel', array('ignore_request' => true));
		$itemsModel->setState('filter.state', 1);
		$itemsModel->setState('filter.draft', 0);

		if (property_exists($ucmTypeTable, 'unique_identifier'))
		{
			$itemsModel->setState('ucm.client', $ucmTypeTable->unique_identifier);
		}

		$itemsModel->setState('ucmType.id', (int) $ucmTypeId);
		$itemsModel->setState('list.ordering', 'a.id');
		$itemsModel->setState('list.direction', 'DESC');
		$itemsModel->setState('showall', 1);
		$itemsModel->setState('list.start', 0);
		$itemsModel->setState('list.limit', $listLimit);
		$items        = $itemsModel->getItems();
		$fieldsColumn = $itemsModel->getFields();

		// Contruct data in well format
		$data['items']        = $items;
		$data['fieldsColumn'] = $fieldsColumn;
		$data['client']       = $ucmTypeTable->unique_identifier;
		/** @scrutinizer ignore-call */
		$data['total']        = $itemsModel->getTotal();

		return $data;
	}

	/**
	 * Get a list of the UCM data
	 *
	 * @param   Array                      $fieldsColumn  selected fields column
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	public static function getColumnsData($params, $fieldsColumn)
	{
		$columnsListToShow = array();
		$ucmFields         = $params->get('ucmfieldname');

		foreach ($ucmFields as $field)
		{
			foreach ($fieldsColumn as $key => $col)
			{
				if ($field == $col->id)
				{
					$columnsListToShow[$key] = $col;
				}
			}
		}

		return $columnsListToShow;
	}
}
