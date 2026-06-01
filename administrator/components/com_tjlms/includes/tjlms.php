<?php
/**
 * @package     TJLms
 * @subpackage  com_shika
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die();

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Table\Table;
use Joomla\String\StringHelper;
use Joomla\CMS\Factory;

/**
 * TjLms factory class.
 *
 * @since  1.3.30
 */
class TjLms
{
	/**
	 * Holds the record of the loaded TjLms classes
	 *
	 * @var    array
	 * @since  1.3.30
	 */
	private static $loadedClass = array();

	/**
	 * Holds the record of the component config
	 *
	 * @var    Joomla\Registry\Registry
	 * @since  1.3.30
	 */
	private static $config = null;

	/**
	 * Retrieves a table from the table folder
	 *
	 * @param   string  $name    The table file name
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return  Table|boolean object or false on failure
	 *
	 * @since   1.3.30
	 **/
	public static function table($name, $config = array())
	{
		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjlms/tables');
		$table = Table::getInstance($name, 'TjlmsTable', $config);

		return $table;
	}

	/**
	 * Retrieves a model from the model folder
	 *
	 * @param   string  $name    The model name
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return  BaseDatabaseModel|boolean object or false on failure
	 *
	 * @since   1.3.30
	 **/
	public static function model($name, $config = array())
	{
		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_TjLms/models');
		$model = BaseDatabaseModel::getInstance($name, 'TjLmsModel', $config);

		return $model;
	}

	/**
	 * Magic method to create instance of TjLms library
	 *
	 * @param   string  $name       The name of the class
	 * @param   mixed   $arguments  Arguments of class
	 *
	 * @return  mixed   return the Object of the respective class if exist OW return false
	 *
	 * @since   1.3.30
	 **/
	public static function __callStatic($name, $arguments)
	{
		self::loadClass($name);

		$className = 'TjLms' . StringHelper::ucfirst($name);

		if (class_exists($className))
		{
			if (method_exists($className, 'getInstance'))
			{
				return call_user_func_array(array($className, 'getInstance'), $arguments);
			}

			return new $className;
		}

		return false;
	}

	/**
	 * Load the class library if not loaded
	 *
	 * @param   string  $className  The name of the class which required to load
	 *
	 * @return  boolean True on success
	 *
	 * @since   1.3.30
	 **/
	public static function loadClass($className)
	{
		if (! isset(self::$loadedClass[$className]))
		{
			$className = (string) StringHelper::strtolower($className);

			$path = JPATH_ADMINISTRATOR . '/components/com_tjlms/includes/' . $className . '.php';

			include_once $path;

			self::$loadedClass[$className] = true;
		}

		return self::$loadedClass[$className];
	}

	/**
	 * Load the component configuration
	 *
	 */
	public static function config()
	{
		if (empty(self::$config))
		{
			self::$config = ComponentHelper::getParams('com_tjlms');
		}

		return self::$config;
	}

	/**
	 * Initializes the css, js and necessary dependencies
	 *
	 * @param   string  $location  The location where the assets needs to load
	 *
	 * @return  void
	 *
	 * @since   1.3.30
	 */
	public static function init($location = 'site')
	{
		static $loaded = null;
		$docType = Factory::getDocument()->getType();

		if (!isset($loaded[$location]) && ($docType == 'html'))
		{
			$app    = Factory::getApplication();
			$input  = $app->input;
			$view   = $input->get('view', '', 'string');
			$layout = $input->get('layout');

			define('COM_TJLMS_WRAPPER_DIV', 'tjlms-wrapper');

			if (file_exists(JPATH_ROOT . '/media/techjoomla_strapper/tjstrapper.php'))
			{
				require_once JPATH_ROOT . '/media/techjoomla_strapper/tjstrapper.php';
				TjStrapper::loadTjAssets('com_tjlms');
			}

			HTMLHelper::stylesheet('media/com_tjlms/vendors/artificiers/artficier.css');

			$options['relative'] = true;
			HTMLHelper::script('com_tjlms/tjService.js', $options);
			HTMLHelper::script('com_tjlms/common.js', $options);

			if (self::config()->get('load_bootstrap')
				|| $view == 'buy' || $view == 'lesson' || $view == 'enrolluser' || $view == 'assesslesson' || ($view == 'reports' && $layout == 'attempts'))
			{
				HTMLHelper::StyleSheet('media/techjoomla_strapper/bs3/css/bootstrap.css');
				HTMLHelper::_('bootstrap.framework');
			}

			// Load custom css of template.
			if (file_exists(JPATH_ROOT . '/templates/' . $app->getTemplate() . '/css/custom.css'))
			{
				HTMLHelper::StyleSheet('templates/' . $app->getTemplate() . '/css/custom.css');
			}

			HTMLHelper::script('media/com_tjlms/vendors/loader/js/loadingoverlay.min.js');
			HTMLHelper::_('bootstrap.tooltip');
			HTMLHelper::_('behavior.multiselect');

			$loadChosen = 1;

			if ($view == 'buy' || $view == 'lesson' || $view == 'assesslesson')
			{
				$loadChosen = 0;
			}

			if ($loadChosen == 1)
			{
				HTMLHelper::_('formbehavior.chosen', 'select');
			}

			$loaded[$location] = true;
		}
	}
}
