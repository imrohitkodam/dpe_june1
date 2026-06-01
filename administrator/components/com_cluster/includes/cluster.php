<?php
/**
 * @package    Cluster
 *
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2018 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die();

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Table\Table;

JLoader::discover("Cluster", JPATH_ADMINISTRATOR . '/components/com_cluster/libraries');

/**
 * Cluster factory class.
 *
 * This class perform the helpful operation for truck app
 *
 * @since  1.0.0
 */
class ClusterFactory
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

		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_cluster/tables');

		// @TODO Add support for cache
		return Table::getInstance($name, 'ClusterTable');
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
		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_cluster/models', 'ClusterModel');

		// @TODO Add support for cache
		return BaseDatabaseModel::getInstance($name, 'ClusterModel', $config);
	}
}
