<?php
/**
 * @package     JMailAlerts
 * @subpackage  com_jmailalerts
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2022 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Registry\Registry;

/**
 * Class for plugins helper
 *
 * @package  JMailAlerts
 *
 * @since    2.5
 */
class JMailAlertsPlugin extends CMSPlugin
{
	/**
	 * Affects constructor behavior. If true, language files will be loaded automatically.
	 *
	 * @var    boolean
	 * @since  2.6.0
	 */
	protected $autoloadLanguage = true;

	/**
	 * Decides if parent extension of current plugin exists
	 *
	 * @var    boolean
	 * @since  2.6.0
	 */
	public $parentExtensionExists = false;

	/**
	 * Output array
	 *
	 * @var    array
	 * @since  2.6.0
	 */
	public $returnArray;

	/**
	 * Constructor
	 *
	 * @param   object  &$subject  subject
	 * @param   array   $config    plugin config
	 *
	 * @since   2.5.1
	 */
	public function __construct(&$subject, $config = array())
	{
		parent::__construct($subject, $config);

		// Set array for data to be returned
		// 0, 1, 2 : plugin name, plugin HTML, plugin CSS
		$this->returnArray    = array();
		$this->returnArray[0] = $this->_name;
		$this->returnArray[1] = '';
		$this->returnArray[2] = '';

		// Parent extension installed or not check
		$this->checkExtensionExists($this->extension);

		// If related extension not installed, return
		if (!$this->parentExtensionExists)
		{
			return false;
		}

		// Set plugin params
		if ($this->params === false)
		{
			$jPlugin      = PluginHelper::getPlugin('emailalerts', $this->_name);
			$this->params = new Registry;
			$this->params->loadString($jPlugin->params);
		}

		// Load language file for plugin frontend
		$lang = Factory::getLanguage();
		$lang->load('plg_emailalerts_' . $this->_name, JPATH_ADMINISTRATOR);

		$this->loadLanguage();
	}

	/**
	 * Check if extension is installed
	 *
	 * @param   string  $extension  Extension name
	 *
	 * @since   2.6.0
	 *
	 * @return  void
	 */
	public function checkExtensionExists($extension)
	{
		$extPath = JPATH_ROOT . '/components/' . $extension;

		if (Folder::exists($extPath))
		{
			$this->parentExtensionExists = true;
		}
		else
		{
			$this->parentExtensionExists = false;
		}
	}

	/**
	 * Plugin trigger to get latest matching records
	 *
	 * @param   string  $id               Userid or email id for user whom email will be sent
	 * @param   string  $lastEmailDate    Timestamp when last email was sent to that user
	 * @param   array   $userParams       Array of user's alert preference considering data tags
	 * @param   int     $fetchOnlyLatest  Decide to send only fresh content or not
	 *
	 * @return  array
	 *
	 * @since  2.5.0
	 */
	public function onEmailTrigger($id, $lastEmailDate, $userParams, $fetchOnlyLatest)
	{
		// If related extension not installed, return
		if (!$this->parentExtensionExists)
		{
			return $this->returnArray;
		}

		$result = $this->getList($id, $lastEmailDate, $userParams, $fetchOnlyLatest);

		// Load plugin language files
		$this->loadPluginLanguageFiles($id);

		// Set plugin HTML, CSS
		if (!empty($result))
		{
			$this->setPluginHTML($result);
			$this->setPluginCSS();
		}

		return $this->returnArray;
	}

	/**
	 * Sets plugin HTML o/p in return array
	 *
	 * @param   array  $list  Variables to assign to
	 *
	 * @return  void
	 */
	public function setPluginHTML($list = false)
	{
		$plugin = $this->_name;
		$layout = $this->_name;

		ob_start();
		$layoutPath    = $this->getLayoutPath($plugin, $layout);
		$pluginParams  = $this->params;
		include $layoutPath;
		$html          = ob_get_contents();
		ob_end_clean();

		// Set HTML into return variable
		$this->returnArray[1] = $html;
	}

	/**
	 * Get the path to a layout file
	 *
	 * @param   string  $plugin  The name of the plugin file
	 * @param   string  $layout  The name of the plugin layout file
	 *
	 * @return  string  The path to the plugin layout file
	 */
	public function getLayoutPath($plugin, $layout = 'default')
	{
		$app   = Factory::getApplication();
		$group = 'emailalerts';

		// Get the template and default paths for the layout
		$templatePath = JPATH_SITE . '/templates/' . $app->getTemplate() . '/html/plugins/' . $group . '/' . $plugin . '/' . $layout . '.php';

		$defaultPath = JPATH_SITE . '/plugins/' . $group . '/' . $plugin . '/' . $plugin . '/tmpl/' . $layout . '.php';

		// If the site template has a layout override, use it
		if (File::exists($templatePath))
		{
			return $templatePath;
		}
		else
		{
			return $defaultPath;
		}
	}

	/**
	 * Sets plugin CSS o/p in return array
	 *
	 * @return  void
	 */
	public function setPluginCSS()
	{
		$plugin = $this->_name;
		$layout = $this->_name;
		$group  = 'emailalerts';
		$app    = Factory::getApplication();

		// Get the template and default paths for the layout
		$templateCssPath = JPATH_SITE . '/templates/' . $app->getTemplate() . '/html/plugins/' . $group . '/' . $plugin . '/' . $layout . '.css';

		$defaultCssPath = JPATH_SITE . '/plugins/' . $group . '/' . $plugin . '/' . $plugin . '/tmpl/' . $layout . '.css';

		// If the site template has a layout override, use it
		if (File::exists($templateCssPath))
		{
			$css = file_get_contents($templateCssPath);
		}
		else
		{
			$css = file_get_contents($defaultCssPath);
		}

		// Set CSS into return variable
		$this->returnArray[2] = $css;
	}

	/**
	 * Get itemid for given link
	 *
	 * @param   string   $link          link
	 * @param   integer  $skipIfNoMenu  Decide to use Itemid from $input
	 *
	 * @return  integer
	 *
	 * @since  3.0
	 */
	public function getItemId($link, $skipIfNoMenu = 0)
	{
		$itemid = 0;
		$app    = Factory::getApplication();
		$input  = Factory::getApplication()->input;

		if ($app->isClient('site'))
		{
			// $jSite = new JSite;
			$menu  = $app->getMenu();
			$items = $menu->getItems('link', $link);

			if (isset($items[0]))
			{
				$itemid = $items[0]->id;
			}
		}

		if (!$itemid)
		{
			$db    = Factory::getDBO();
			$query = $db->getQuery(true);
			$query
				->select('id')
				->from('#__menu')
				->where('link LIKE "%' . $link . '%"')
				->where('published = 1')
				->where('client_id = 0')
				->setLimit('1');

			$db->setQuery($query);
			$itemid = $db->loadResult();
		}

		if (!$itemid)
		{
			if ($skipIfNoMenu)
			{
				$itemid = 0;
			}
			else
			{
				$itemid  = $input->get->get('Itemid', '0', 'INT');
			}
		}

		return $itemid;
	}

	/**
	 * Sorts a multidimentional array as per given column
	 *
	 * @param   array   $array   Array of nodes
	 * @param   string  $column  Column based on which sorting will be done
	 * @param   string  $order   Sorting order direction 0(ASC) or 1(DESC)
	 *
	 * @return  array
	 *
	 * @since    3.0
	 */
	public function multi_d_sort($array, $column, $order)
	{
		if (isset($array) && count($array))
		{
			foreach ($array as $key => $row)
			{
				$orderby[$key] = $row->$column;
			}

			if ($order)
			{
				array_multisort($orderby, SORT_DESC, $array);
			}
			else
			{
				array_multisort($orderby, SORT_ASC, $array);
			}
		}

		return $array;
	}

	/**
	 * This method loads language files on basis of user selected language for backend and frontend or default site language configuration
	 *
	 * @param   Integer|String  $userId  Register user id or in Guest case 0 or email id
	 *
	 * @return  void
	 *
	 * @since   2.6.3
	 */
	public function loadPluginLanguageFiles($userId)
	{
		$lang = Factory::getLanguage();

		// In registered user case check has languge configured for that user
		if ($userId)
		{
			$app        = Factory::getApplication();
			$user       = Factory::getUser($userId);
			$userParams = new Registry;

			if (!empty($user->params))
			{
				$userParams->loadString($user->params);
				$userAdminLanguage = $userParams->get('admin_language');
				$userSiteLanguage  = $userParams->get('language');

				if (!empty($userAdminLanguage) && $app->isClient('administrator'))
				{
					$lang->load('plg_emailalerts_' . $this->_name, JPATH_ADMINISTRATOR, $userAdminLanguage, true);
					$this->loadLanguage();
				}

				if (!empty($userSiteLanguage) && $app->isClient('site'))
				{
					$lang->load('plg_emailalerts_' . $this->_name, JPATH_ADMINISTRATOR, $userSiteLanguage, true);
					$this->loadLanguage();
				}
			}
			// If the user has not selected the language of backend or frontend for him then load default site configured language files
			else
			{
				$lang->load('plg_emailalerts_' . $this->_name, JPATH_ADMINISTRATOR);
				$this->loadLanguage();
			}
		}
		// For guest, loads default site language configured files
		else
		{
			$lang->load('plg_emailalerts_' . $this->_name, JPATH_ADMINISTRATOR);
			$this->loadLanguage();
		}
	}
}
