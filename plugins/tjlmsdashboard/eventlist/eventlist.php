<?php
/**
 * @package    Shika
 * @author     TechJoomla | <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */
// No direct access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Component\ComponentHelper;

jimport('joomla.filesystem.folder');
jimport('joomla.plugin.plugin');

$lang = Factory::getLanguage();
$lang->load('plg_tjlmsdashboard_eventlist', JPATH_ADMINISTRATOR);

/**
 * Vimeo plugin from techjoomla
 *
 * @since  1.0.0
 */

class PlgTjlmsdashboardEventlist extends CMSPlugin
{
	public $dash_icons_path;

	public $params;
	/**
	 * Function to render the whole block
	 *
	 * @param   ARRAY  $plg_data  data to be used to create whole block
	 * @param   mixed  $layout    Layout to be used
	 *
	 * @return  mixed
	 *
	 * @since 1.0.0
	 */
	public function oneventlistRenderPluginHTML($plg_data, $layout = 'default')
	{
		$eventList = $this->getData($plg_data);

		$this->dash_icons_path = Uri::root(true) . '/media/com_tjlms/images/default/icons/';

		// Load the layout & push variables
		ob_start();
		$layout = $this->buildLayoutPath($layout);
		include $layout;

		$html = ob_get_contents();
		ob_end_clean();

		return $html;
	}

	/**
	 * Function to get the layout for the block
	 *
	 * @param   mixed  $layout  Layout to be used
	 *
	 * @return  mixed
	 *
	 * @since  1.0.0
	 */
	protected function buildLayoutPath($layout)
	{
		if (empty($layout))
		{
			$layout = "default";
		}

		$app = Factory::getApplication();
		$core_file 	= dirname(__FILE__) . '/' . $this->_name . '/' . 'tmpl' . '/' . $layout . '.php';
		$override = JPATH_BASE . '/templates/' . $app->getTemplate() . '/html/plugins/' . $this->_type . '/' . $this->_name . '/' . $layout . '.php';

		if (File::exists($override))
		{
			return $override;
		}
		else
		{
			return  $core_file;
		}
	}

	/**
	 * Function to get data of the whole block
	 *
	 * @param   ARRAY  $plg_data  data to be used to create whole block
	 *
	 * @return  mixed
	 *
	 * @since 1.0.0
	 */
	public function getData($plg_data)
	{
		$eventList = '';

		if (ComponentHelper::isEnabled('com_jticketing', true))
		{
			$path = JPATH_SITE . '/components/com_jticketing/helpers/main.php';

			if (!class_exists('Jticketingmainhelper'))
			{
				JLoader::register('Jticketingmainhelper', $path);
				JLoader::load('Jticketingmainhelper');
			}

			$Jticketingmainhelper = new Jticketingmainhelper;

			$eventList = $Jticketingmainhelper->geteventnamesBybuyer($plg_data->user_id, $this->params);
		}

		return $eventList;
	}
}
