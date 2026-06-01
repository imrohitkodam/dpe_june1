<?php
/**
 * @package     TMT
 * @subpackage  com_shika
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Table\Table;
use Joomla\String\StringHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

JLoader::import("/techjoomla/media/storage/local", JPATH_LIBRARIES);

/**
 * TMT factory class.
 *
 * @since  1.3.31
 */
class TMT
{
	/**
	 * Holds the record of the loaded Tmt classes
	 *
	 * @var    array
	 * @since  1.3.31
	 */
	private static $loadedClass = array();

	/**
	 * Holds the record of the component config
	 *
	 * @var    Joomla\Registry\Registry
	 * @since  1.3.31
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
	 * @since   1.3.31
	 **/
	public static function table($name, $config = array())
	{
		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tmt/tables');
		$table = Table::getInstance($name, 'TmtTable', $config);

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
	 * @since   1.3.31
	 **/
	public static function model($name, $config = array())
	{
		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tmt/models');
		$model = BaseDatabaseModel::getInstance($name, 'TmtModel', $config);

		return $model;
	}

	/**
	 * Magic method to create instance of Tmt library
	 *
	 * @param   string  $name       The name of the class
	 * @param   mixed   $arguments  Arguments of class
	 *
	 * @return  mixed   return the Object of the respective class if exist OW return false
	 *
	 * @since   1.3.31
	 **/
	public static function __callStatic($name, $arguments)
	{
		self::loadClass($name);

		$className = 'Tmt' . StringHelper::ucfirst($name);

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
	 * @since   1.3.31
	 **/
	public static function loadClass($className)
	{
		if (! isset(self::$loadedClass[$className]))
		{
			$className = (string) StringHelper::strtolower($className);

			$path = JPATH_ADMINISTRATOR . '/components/com_tmt/includes/' . $className . '.php';

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
			self::$config = ComponentHelper::getParams('com_tmt');
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
	 * @since   1.3.31
	 */
	public static function init($location = 'site')
	{
		static $loaded = null;

		$document = Factory::getDocument();
		$docType  = $document->getType();

		if (isset($loaded[$location]) && ($docType != 'html'))
		{
			return;
		}

		// TMT JS - Collection of all JS files
		HTMLHelper::script('media/com_tmt/dist/app.min.js');
		HTMLHelper::script('libraries/techjoomla/assets/js/tjvalidator.js');

		// Added for load ASCII MATH js.
		HTMLHelper::script('https://cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.4/latest.js?config=AM_CHTML');

		$lang = Factory::getLanguage();
		$lang->load('com_tmt', JPATH_SITE);
		$lang->load('com_tmt', JPATH_ADMINISTRATOR);
		$lang->load('com_tjlms', JPATH_ADMINISTRATOR);

		if ($location == 'site')
		{
			self::Language()->siteLanguageConstant();
		}
		else
		{
			self::Language()->adminLanguageConstant();
		}

		$tjStrapperPath = JPATH_SITE . '/media/techjoomla_strapper/tjstrapper.php';

		if (File::exists($tjStrapperPath))
		{
			require_once $tjStrapperPath;
			TjStrapper::loadTjAssets('com_tmt');
		}

		$lmsParams = ComponentHelper::getParams('com_tjlms');

		$mediaLib = TJMediaStorageLocal::getInstance();

		$document->addScriptDeclaration("
			const tjMediaPath = '" . Uri::root() . $mediaLib->mediaUploadPath . "/';
			const lmsLessonUploadSize = '" . $lmsParams->get('lesson_upload_size', '0', 'INT') . "';
		");

		$loaded[$location] = true;
	}
}
