<?php
/**
 * @package    Com_Timelog
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die();

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Table\Table;

JLoader::import("/techjoomla/media/storage/local", JPATH_LIBRARIES);
JLoader::import("/techjoomla/media/xref", JPATH_LIBRARIES);

$helperPath = JPATH_SITE . '/components/com_timelog/helpers/main.php';

if (!class_exists('TimelogMainHelper'))
{
	// Require_once $helperPath;
	JLoader::register('TimelogMainHelper', $helperPath);
	JLoader::load('TimelogMainHelper');
}

// Load Global language constants to in .js file
TimelogMainHelper::getLanguageConstant();

JLoader::discover("Timelog", JPATH_ADMINISTRATOR . '/components/com_timelog/libraries');

/**
 * Timelog factory class.
 *
 * This class perform the helpful operation for truck app
 *
 * @since  __DEPLOY_VERSION__
 */
class TimelogFactory
{
	/**
	 * Retrieves a table from the table folder
	 *
	 * @param   string  $name    The table file name
	 *
	 * @param   string  $prefix  The table class name prefix
	 *
	 * @param   array   $config  The table file name
	 *
	 * @return	Table|boolean Table
	 *
	 * @since 	__DEPLOY_VERSION__
	 **/
	public static function table($name, $prefix = 'TimelogTable', $config = array())
	{
		// @TODO Improve file loading with specific table file.
		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_timelog/tables');

		// @TODO Add support for cache
		return Table::getInstance($name, $prefix, $config);
	}

	/**
	 * Retrieves a model from the model folder
	 *
	 * @param   string  $name    The model name to instantiate
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return	BaseDatabaseModel|boolean BaseDatabaseModel
	 *
	 * @since 	__DEPLOY_VERSION__
	 **/
	public static function model($name, $config = array())
	{
		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_timelog/models');

		// @TODO Add support for cache
		return BaseDatabaseModel::getInstance($name, 'TimelogModel', $config);
	}
}
