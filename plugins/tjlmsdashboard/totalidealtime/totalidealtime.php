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
use Joomla\CMS\Plugin\PluginHelper;

jimport('joomla.filesystem.folder');
jimport('joomla.plugin.plugin');

$lang = Factory::getLanguage();
$lang->load('plg_tjlmsdashboard_totalidealtime', JPATH_ADMINISTRATOR);

/**
 * Vimeo plugin from techjoomla
 *
 * @since  1.0.0
 */

class PlgTjlmsdashboardTotalidealtime extends CMSPlugin
{
	/**
	 * Function to render the whole block
	 *
	 * @param   ARRAY  $plg_data  data to be used to create whole block
	 * @param   ARRAY  $layout    Layout to be used
	 *
	 * @return  complete html.
	 *
	 * @since 1.0.0
	 */
	public function ontotalidealtimeRenderPluginHTML($plg_data, $layout = 'default')
	{
		$totalIdealTime = $this->getData($plg_data);

		// Get plugin params
		$plg_data->background_color = $this->params->get('background_color');
		$plg_data->border_color = $this->params->get('border_color');
		$plg_data->text_color = $this->params->get('text_color');

		// Load the layout & push variables
		ob_start();
		$layout = PluginHelper::getLayoutPath($this->_type, $this->_name, $this->params->get('layout', 'default'));
		include $layout;

		$html = ob_get_contents();
		ob_end_clean();

		return $html;
	}

	/**
	 * Function to get data of the whole block
	 *
	 * @param   ARRAY  $plg_data  data to be used to create whole block
	 *
	 * @return  data.
	 *
	 * @since 1.0.0
	 */
	public function getData($plg_data)
	{
		// Create object of ComtjlmstrackingHelper class.
		$tjlmsTrackingHelperObj = new ComtjlmstrackingHelper;

		// Call getTotalIdealTime function from ComtjlmstrackingHelper class and get the total spent time.
		$totalIdealTime = $tjlmsTrackingHelperObj->getTotalIdealTime($plg_data->user_id);

		return $totalIdealTime;
	}
}
