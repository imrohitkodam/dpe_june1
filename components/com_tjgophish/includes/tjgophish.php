<?php
/**
 * @package     TJ-Gophish
 * @subpackage  com_tjgophish
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Table\Table;
use Joomla\String\StringHelper;

/**
 * TjGoPhish factory class.
 *
 * This class perform the helpful operation required to TjGoPhish package
 *
 * @since  1.0.0
 */
class TJGOPHISH
{
	/**
	 * Holds the record of the loaded TjGoPhish classes
	 *
	 * @var    array
	 * @since  1.0.0
	 */
	private static $loadedClass = array();

	/**
	 * Holds the record of the component config
	 *
	 * @var    Joomla\Registry\Registry
	 * @since  1.0.0
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
	 * @since   1.0.0
	 **/
	public static function table($name, $config = array())
	{
		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjgophish/tables');
		$table = Table::getInstance($name, 'TjGoPhishTable', $config);

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
	 * @since   1.0.0
	 **/
	public static function model($name, $config = array())
	{
		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_tjgophish/models', 'TjGoPhishModel');
		$model = BaseDatabaseModel::getInstance($name, 'TjGoPhishModel', $config);

		return $model;
	}

	/**
	 * Magic method to create instance of TjGoPhish library
	 *
	 * @param   string  $name       The name of the class
	 * @param   mixed   $arguments  Arguments of class
	 *
	 * @return  mixed   return the Object of the respective class if exist OW return false
	 *
	 * @since   1.0.0
	 **/
	public static function __callStatic($name, $arguments)
	{
		self::loadClass($name);

		$className = 'TjGoPhish' . StringHelper::ucfirst($name);

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
	 * @since   1.0.0
	 **/
	public static function loadClass($className)
	{
		if (! isset(self::$loadedClass[$className]))
		{
			$className = (string) StringHelper::strtolower($className);

			$path = JPATH_SITE . '/components/com_tjgophish/includes/' . $className . '.php';

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
			self::$config = ComponentHelper::getParams('com_tjgophish');
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
	 * @since   1.0.0
	 */
	public static function init($location = 'site')
	{
		static $loaded = null;
		$docType = Factory::getDocument()->getType();
		$versionClass = self::Version();

		if (! $loaded[$location] && ($docType == 'html'))
		{
			if (file_exists(JPATH_ROOT . '/media/techjoomla_strapper/tjstrapper.php'))
			{
				JLoader::register('TjStrapper', JPATH_ROOT . '/media/techjoomla_strapper/tjstrapper.php');
				TjStrapper::loadTjAssets();
			}

			$version = '2.0';
			$input   = Factory::getApplication()->input;
			$view    = $input->get('view', '', 'STRING');

			try
			{
				$version = $versionClass->getMediaVersion();
			}
			catch (Exception $e)
			{
				// Silence is peace :)
			}

			$options = array("version" => $version);

			HTMLHelper::script('media/system/js/messages.min.js', $options);
			HTMLHelper::script('media/com_tjgophish/js/com_tjgophish.min.js', $options);
			HTMLHelper::script('media/com_tjgophish/js/core/class.min.js', $options);
			HTMLHelper::script('media/com_tjgophish/js/core/base.min.js', $options);

			HTMLHelper::StyleSheet('media/com_tjgophish/css/tjgophish.css', $options);

			if ($view == 'campaignreport')
			{
				HTMLHelper::StyleSheet('media/com_tjgophish/css/campaignreport.css', $options);
			}

			if ($view == 'campaigns')
			{
				HTMLHelper::script('media/com_tjgophish/js/ui/campaigns.min.js', $options);
			}

			$loaded[$location] = true;
		}
	}
}
