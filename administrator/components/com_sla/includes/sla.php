<?php
/**
 * @package    Sla
 *
 * @author     Techjoomla <contact@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die();

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Table\Table;

JLoader::discover("Sla", JPATH_ADMINISTRATOR . '/components/com_sla/libraries');

/**
 * Sla factory class.
 *
 * This class creates table and model object by instantiating
 *
 * @since  1.0.0
 */
class SlaFactory
{
	/**
	 * Retrieves a table from the table folder
	 *
	 * @param   string  $name  The table file name
	 *
	 * @return	Table object
	 *
	 * @since 	1.0.0
	 **/
	public static function table($name)
	{
		// @TODO Improve file loading with specific table file.

		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_sla/tables');

		// @TODO Add support for cache
		return Table::getInstance($name, 'SlaTable');
	}

	/**
	 * Retrieves a model from the model folder
	 *
	 * @param   string  $name    The model name to instantiate
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return	BaseDatabaseModel object
	 *
	 * @since 	1.0.0
	 **/
	public static function model($name, $config = array())
	{
		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_sla/models');

		// @TODO Add support for cache
		return BaseDatabaseModel::getInstance($name, 'SlaModel', $config);
	}
}
