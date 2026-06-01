<?php
/**
 * @package     TJDashboard
 * @subpackage  tjdashboardsource
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;

$lang      = Factory::getLanguage();
$lang->load('plg_tjdashboardsource_dashboard', JPATH_ADMINISTRATOR);

JLoader::import("/components/com_cluster/includes/cluster", JPATH_ADMINISTRATOR);

/**
 * Plugin for tjdashboardsource to get dashboard filters
 *
 * @since  __DEPLOY_VERSION__
 */

class DashboardWidgetDateRangeFiltersDatasource
{
	public $dataSourceName = "PLG_TJDASHBOARDSOURCE_DASHBOARD_WIDGET_DATE_FILTERS";

	/**
	 * Get Data for Filter
	 *
	 * @return string dataArray
	 *
	 * @since   __DEPLOY_VERSION__
	 * */
	public function getDataDateRangeTjdashfilter()
	{
		$items = [];

		return json_encode($items);
	}

	/**
	 * Get supported Renderers List
	 *
	 * @return array supported renderes for this data source
	 *
	 * @since   __DEPLOY_VERSION__
	 * */
	public function getSupportedRenderers()
	{
		return array('daterange.tjdashfilter' => "PLG_TJDASHBOARDRENDERER_DATE_RANGE_FILTER");
	}
}
