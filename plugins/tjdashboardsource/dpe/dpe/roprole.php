<?php
/**
 * @package     TJDashboard
 * @subpackage  tjdashboardsource
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2021 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$lang      = Factory::getLanguage();
$lang->load('plg_tjdashboardsource_dpe', JPATH_ADMINISTRATOR);

/**
 * DPE plugin for tjdashboardsource
 *
 * @since  __DEPLOY_VERSION__
 */

class DpeRoproleDatasource
{
	public $dataSourceName = "PLG_TJDASHBOARDSOURCE_ROPROLE";

	/**
	 * Get Data for Tabulator Table
	 *
	 * @return string dataArray
	 *
	 * @since   __DEPLOY_VERSION__
	 * */
	public function getDataCountlinkboxTjdashcount()
	{
		$app              = Factory::getApplication();
		$menu             = $app->getMenu();

		$link        = 'index.php?option=com_tjucm&view=items&client=com_tjucm.role';
		JLoader::import("/components/com_tjucm/helpers/tjucm", JPATH_SITE);
		$tjucmHelper = new TjucmHelpersTjucm;
		$itemId      = $tjucmHelper->getItemId($link);
		
		// Get Filters
		$filters       = Factory::getApplication()->input->get('filter', '', 'Array');
		$clusterFilter = '';
		$pageLink      =  Route::_($link . $clusterFilter . '&Itemid=' . $itemId, false);

		// Check school filter applied  or not
		if (!empty($filters['cluster_id']))
		{  

			// Add filter in ULR to apply filter on list views
			$clusterFilter = '?cluster=' . (INT) $filters['cluster_id'];
			$tags[$key] = '&filter[tags][]=';
		}

		if (!empty($filters['tags']))
		{
			// Add filter in ULR to apply filter on list views
			foreach ($filters['tags'] as $key => $tag)
			{
				$tags[$key] = '&filter_tags[]=' .  $tag;
			}
			$clusterFilter = '?cluster=all';
		}
		
		$tags = is_array($tags)? implode('', $tags):'';


		$urls[Text::_('PLG_TJDASHBOARDSOURCE_ROPROLE_TITLE')] = $pageLink.$clusterFilter.$tags;

		$items = [];
		$items['data'] = ['link'  => $urls
		];

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
		return array('countlinkbox.tjdashcount' => "PLG_TJDASHBOARDRENDERER_COUNTLINKBOX");
	}
}
