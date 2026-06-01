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
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Date\Date;

jimport('joomla.filesystem.folder');
jimport('joomla.plugin.plugin');

$lang = Factory::getLanguage();
$lang->load('plg_tjlmsdashboard_activitygraph', JPATH_ADMINISTRATOR);

/**
 * Vimeo plugin from techjoomla
 *
 * @since  1.0.0
 */

class PlgTjlmsdashboardActivitygraph extends CMSPlugin
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
	public function onactivitygraphRenderPluginHTML($plg_data, $layout = 'default')
	{
		$yourActivities = $this->getData($plg_data);
		$html = '';

		if (!empty($yourActivities))
		{
			// Get plugin params
			$plg_data->session_line_color = $this->params->get('session_line_color');
			$plg_data->activity_line_color = $this->params->get('activity_line_color');
			$dash_icons_path = Uri::root(true) . '/media/com_tjlms/images/default/icons/';

			// Load the layout & push variables
			ob_start();
			$layout = PluginHelper::getLayoutPath($this->_type, $this->_name, $this->params->get('layout', $layout));
			include $layout;

			$html = ob_get_contents();
			ob_end_clean();
		}

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
		$path = JPATH_SITE . '/components/com_tjlms/helpers/tracking.php';

		if (!class_exists('comtjlmstrackingHelper'))
		{
			JLoader::register('comtjlmstrackingHelper', $path);
			JLoader::load('comtjlmstrackingHelper');
		}

		$comtjlmstrackingHelper = new comtjlmstrackingHelper;
		$activityData = array();
		$startDate = new Date('now -1 month');
		$endDate = new Date('now');
		$activityData['user_id'] = $plg_data->user_id;
		$activityData['start'] = $startDate;
		$activityData['end'] = $endDate;
		$yourActivities      = $comtjlmstrackingHelper->getactivity($activityData);

		return $yourActivities;
	}
}
