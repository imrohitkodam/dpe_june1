<?php
/**
 * @package     TJDashboard
 * @subpackage  tjdashboardsource
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\Registry\Registry;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Component\ComponentHelper;

$lang      = Factory::getLanguage();
$lang->load('plg_tjdashboardsource_dpe', JPATH_ADMINISTRATOR);

JLoader::import("/components/com_dpe/includes/dpe", JPATH_SITE);
JLoader::import("/components/com_cluster/includes/cluster", JPATH_ADMINISTRATOR);
JLoader::import("components.com_subusers.includes.rbacl", JPATH_ADMINISTRATOR);

/**
 * DPE plugin for tjdashboardsource to get Report detais
 *
 * @since  __DEPLOY_VERSION__
 */

class DpeReportDatasource
{
	public $dataSourceName = "PLG_TJDASHBOARDSOURCE_DPE_REPORT";

	/**
	 * Function to get data of the whole block
	 *
	 * @return Array data.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function getData()
	{
		try
		{
			// Create a new query object.
			$db    = Factory::getDbo();
			$user  = Factory::getUser();
			$query = $db->getQuery(true);

			// Get all report plugin
			$plugins    = PluginHelper::getPlugin('tjreports');

			// Get decoded data object
			$pluginExists = new Registry($plugins);
			$pluginNames  = array_column(json_decode($pluginExists, true), 'name');

			$query->select('rp.id, title');
			$query->from($db->qn('#__tj_reports', 'rp'));
			$query->where($db->qn('rp.plugin') . ' IN (' . implode(',', $db->q($pluginNames)) . ')');
			$query->where($db->qn('rp.userid') . ' = ' . $db->q(0));

			$db->setQuery($query);
			$reportData = $db->loadObjectList();

			$recordInfo                      = array();
			$recordInfo['fieldname']         = 'reportId';
			$recordInfo['fieldOption'][]     = HTMLHelper::_('select.option', "", Text::_('PLG_TJDASHBOARDSOURCE_DPE_SELECT_REPORT'));
			$recordInfo['fieldRedirectlink'] = Route::_($this->getRedirectUrl(), false);
			$elearningReports                = ComponentHelper::getParams('com_dpe')->get('elearningReports');
			$clusterId                       = $this->setClusterFilter();

			foreach ($reportData as $report)
			{
				if ($user->authorise('core.view', 'com_tjreports.tjreport.' . $report->id))
				{
					$reportOptions = HTMLHelper::_('select.option', $report->id, trim($report->title));

					if (!$user->authorise('core.manageall', 'com_cluster'))
					{
						if (in_array($report->id, $elearningReports))
						{
							// Check org having elearning tool access
							if (RBACL::check($user->id, 'com_cluster', 'core.viewShika', 'com_tjlms', $clusterId))
							{
								$recordInfo['fieldOption'][trim($report->title)] = $reportOptions;
							}
						}
						else
						{
							$recordInfo['fieldOption'][trim($report->title)] = $reportOptions;
						}
					}
					else
					{
						$recordInfo['fieldOption'][trim($report->title)] = $reportOptions;
					}
				}
			}
		}
		catch (Exception $e)
		{
			throw new Exception($e->getMessage());
		}

		return $recordInfo;
	}

	/**
	 * Get Data for Tabulator Table
	 *
	 * @return string dataArray
	 *
	 * @since   __DEPLOY_VERSION__
	 * */
	public function getDataCountlinkboxTjdashcount()
	{
		$items = [];
		$items['data'] = ['optionlist' => $this->getData()
		,'titleLink'  => Route::_($this->getRedirectUrl(), false)
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

	/**
	 * Get redirect URL
	 *
	 * @return string redirection URL
	 *
	 * @since   __DEPLOY_VERSION__
	 *
	 */
	public function getRedirectUrl()
	{
		$clusterId     = $this->setClusterFilter();
		$clusterFilter = "";

		if ($clusterId)
		{
			$cluster = ClusterCluster::getInstance($clusterId);

			// Add filters in ULR to apply filter on list views
			$clusterFilter = '&filters[cluster_id]=' . $clusterId . '&filters[agency]=' . $cluster->client_id;
		}

		// Get Filters
		$app = Factory::getApplication();
		$filters       = $app->input->get('filter', '', 'Array');


		if (!empty($filters['tags']))
		{
			// Add filter in ULR to apply filter on list views
			foreach ($filters['tags'] as $key => $tag)
			{
				$tags[$key] = '&filters[tags][]=' .  $tag;
			}
			$clusterFilter = '&filters[cluster_id]=all&filters[agency]=';
		}
		else
		{
			$tags[$key] = '&filters[tags][]=';	
		}
		$tags = is_array($tags)? implode('', $tags):'';


		$link = 'index.php?option=com_tjreports&view=reports';
		$dpeUtility = DPE::utilities();
		$link .= $clusterFilter . $tags . '&Itemid=' . $dpeUtility->getItemId($link);

		return $link;
	}

	/**
	 * Function used to set cluster filter
	 *
	 * @return Integer cluster id
	 *
	 * @since   __DEPLOY_VERSION__
	 *
	 */
	public function setClusterFilter()
	{
		$user    = Factory::getUser();
		$filters = Factory::getApplication()->input->get('filter', '', 'Array');

		// Check school filter applied  or not
		if (!empty($filters['cluster_id']))
		{
			$clusterId = (int) $filters['cluster_id'];
		}
		elseif (!$user->authorise('core.manageall', 'com_cluster') && empty($filters['cluster_id']))
		{
			// This block is executes when user is not dpe admin and landing on dashboard by login, by clicking menu
			$clusterUserModel = ClusterFactory::model('ClusterUser', array('ignore_request' => true));
			$clusters = $clusterUserModel->getUsersClusters($user->id);

			foreach ($clusters as $cluster)
			{
				if (RBACL::check($user->id, 'com_cluster', 'core.view.own', 'com_tjdashboard', $cluster->cluster_id))
				{
					$clusterIds[] = $cluster->cluster_id;
				}
			}

			// Set first cluster id
			$clusterId = $clusterIds[0];
		}

		return $clusterId;
	}
}
