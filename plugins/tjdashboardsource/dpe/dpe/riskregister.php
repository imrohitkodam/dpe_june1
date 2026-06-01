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
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Date\Date;

$lang      = Factory::getLanguage();
$lang->load('plg_tjdashboardsource_dpe', JPATH_ADMINISTRATOR);

JLoader::import("/components/com_dpe/includes/dpe", JPATH_SITE);
JLoader::import("/components/com_cluster/includes/cluster", JPATH_ADMINISTRATOR);
JLoader::import("/components/com_subusers/includes/rbacl", JPATH_ADMINISTRATOR);

/**
 *  DPE plugin for tjdashboardsource to get Breach log details
 *
 * @since  __DEPLOY_VERSION__
 */

class DperiskregisterDatasource
{
	public $dataSourceName = "PLG_TJDASHBOARDSOURCE_DPE_RISKREGISTER";

	// To set hours due days
	const SETHOURS = 72;

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
			$db  = Factory::getDbo();
			$app = Factory::getApplication();
			$input = $app->input;
			$user  = Factory::getUser();

			$params = DPE::config();
			$requestStatus = (int) $params->get('riskregister', '0');
			
			// Create a new query object.
			$query = $db->getQuery(true);

			// Select the required fields from the table.
			$query->select("count(a.id) AS ucmcount, SUM(CASE WHEN fcv.value = '3' THEN 1 ELSE 0 END) as riskremain, SUM(CASE WHEN fcv.value = '2' THEN 1 ELSE 0 END) as partiallymitigated, SUM(CASE WHEN fcv.value = '1' THEN 1 ELSE 0 END) as fullymitigated, SUM(CASE WHEN fcv.value = '1' THEN 1 ELSE 0 END)
			as riskclosed");

			$query->from($db->quoteName('#__tj_ucm_data', 'a'));

			$query->join('LEFT', $db->qn('#__tjfields_fields_value', 'fcv') . ' ON (' .
			$db->qn('fcv.content_id') . ' = ' . $db->qn('a.id') . ' AND ' . $db->qn('fcv.field_id') . ' = ' . $db->q($requestStatus) . ')');

			$query->where($db->qn('a.client') . " = 'com_tjucm.riskregister'");

			// Get Filters
			$filters = $input->get('filter', '', 'Array');

			// Filter for tags and checked for Dpe admin here
			if (!empty($filters))
			{
				$params     			  = ComponentHelper::getParams('com_multiagency');
				$multiagencyTrusteeRoleId = (int) $params->get('multiagency_trustee_group');
				$orgAdminRoleId           = (int) $params->get('multiagency_school_admin_group', '0', 'INT');
				$orgAdminRole           = in_array($orgAdminRoleId , $user->groups);

				if ($filters['tags'] && !$filters['cluster_id'] && ($user->authorise('core.manageall', 'com_cluster') || in_array($multiagencyTrusteeRoleId, $user->groups) || $orgAdminRole))
				{	
					BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_dpe/models', 'DpeModel');
					$dashBoardModel = BaseDatabaseModel::getInstance('Dashboard', 'DpeModel');

					$filters['cluster_id'] = $dashBoardModel->getClusterIdsByTags($filters['tags']);
				}
			}

			if (!empty($filters['cluster_id']))
			{
				// Generic school id in a query
				if ($genericCluster)
				{
					$query->where($db->qn('a.cluster_id') . ' IN (' . (INT) $filters['cluster_id'] . ',' . $genericCluster . ' )');
				}
				else
				{
					if (is_array($filters['cluster_id']))
					{
						$query->where($db->qn('a.cluster_id') . ' IN ( ' . implode(',', $filters['cluster_id']) . ')');
					}
					else
					{
						$query->where($db->qn('a.cluster_id') . ' = ' . (INT) $filters['cluster_id'] );
					}
				}
			}

			$clusterUserModel = ClusterFactory::model('ClusterUser', array('ignore_request' => true));
			$clusters = $clusterUserModel->getUsersClusters($user->id);

			foreach ($clusters as $cluster)
			{
				// Check user have permission to manage all clusters
				if (!$user->authorise('core.manageall', 'com_cluster'))
				{
					// Check user having permission to add staff
					if (RBACL::check($user->id, 'com_cluster', 'core.view.own', 'com_tjdashboard', $cluster->cluster_id))
					{
						$clusterIds[] = $cluster->cluster_id;
					}
				}
				else
				{
					$clusterIds[] = $cluster->cluster_id;
				}
			}

			$addedBy = '';

			// checked for org admin here.  
			if (!$user->authorise('core.manageall', 'com_cluster'))
			{
				$addedBy = $db->qn('a.created_by') . ' = ' . $user->id . ' AND ';
			}

			if (empty($filters['cluster_id']) && !$user->authorise('core.manageall', 'com_cluster'))
			{
				// $query->where("(" . $db->qn('a.cluster_id') . " = " . $clusterIds[0] . ")");
				$clusterIds =implode(', ', $clusterIds);
					$query->where("(" . $db->qn('a.cluster_id') . "IN (" . $clusterIds . "))");
			}

			$query->where($db->qn('a.draft') . " = 0 ");

			$db->setQuery($query);
			$riskRegister = $db->loadAssoc();

			
		}
		catch (Exception $e)
		{
			throw new Exception($e->getMessage());
		}

		$riskremain = ($riskRegister['riskremain']) ? $riskRegister['riskremain'] : 0;
		$partiallymitigated = ($riskRegister['partiallymitigated']) ? $riskRegister['partiallymitigated'] : 0;
		$fullymitigated = ($riskRegister['fullymitigated']) ? $riskRegister['fullymitigated'] : 0;
		$riskclosed     = ($riskRegister['riskclosed']) ? $riskRegister['riskclosed'] : 0;
		$ucmCount      = ($riskRegister['ucmcount']) ? $riskRegister['ucmcount'] : 0;

		$recordInfo = array();
		$recordInfo['widgetcolor'] = array(
		Text::_('PLG_TJDASHBOARDSOURCE_DPE__RISKREGISTER_RSKREMAIN') => (($riskremain == 0) ? 'text-red-500' : 'text-red-500'),
		Text::_('PLG_TJDASHBOARDSOURCE_DPE__RISKREGISTER_PARTIALLY_MITIGATED') => (($partiallymitigated == 0) ? 'text-amber' : 'text-amber'),
		Text::_('PLG_TJDASHBOARDSOURCE_DPE__RISKREGISTER_FULLY_MITIGATED') => (($fullymitigated == 0) ? 'text-red-500' : 'text-red-500'),
		Text::_('PLG_TJDASHBOARDSOURCE_DPE_RISKREGISTER_RISKCLOSED') => (($riskclosed == 0) ? 'text-success' : 'text-success'),
		);

		$recordInfo['widgetdata'] = array(
		Text::_('PLG_TJDASHBOARDSOURCE_DPE__RISKREGISTER_RSKREMAIN') => $riskremain,
		Text::_('PLG_TJDASHBOARDSOURCE_DPE__RISKREGISTER_PARTIALLY_MITIGATED') => $partiallymitigated,
		Text::_('PLG_TJDASHBOARDSOURCE_DPE__RISKREGISTER_FULLY_MITIGATED') => $fullymitigated,
		Text::_('PLG_TJDASHBOARDSOURCE_DPE_RISKREGISTER_RISKCLOSED') => $riskclosed,
		);

		$recordInfo['total']       = $ucmCount;
		$recordInfo['id']          = "riskregister";
		$recordInfo['closedCount'] = ($riskclosed) ? $riskclosed : 0;
		return $recordInfo;
	}

	/**
	 * Get Data for Countlink box
	 *
	 * @return string dataArray
	 *
	 * @since   __DEPLOY_VERSION__
	 * */
	public function getDataCountlinkboxTjdashcount()
	{
		return $this->getFormattedData();
	}

	/**
	 * Get Data for Pie chart
	 *
	 * @return string dataArray
	 *
	 * @since   __DEPLOY_VERSION__
	 * */
	public function getDataPiechartTjdashcount()
	{
		return $this->getFormattedData();
	}

	/**
	 * Get formatted data for widget
	 *
	 * @return string dataArray
	 *
	 * @since   __DEPLOY_VERSION__
	 *
	 * */
	public function getFormattedData()
	{
		$link = 'index.php?option=com_tjucm&view=items&client=com_tjucm.riskregister';
		JLoader::import("/components/com_tjucm/helpers/tjucm", JPATH_SITE);
		$tjucmHelper = new TjucmHelpersTjucm;
		$itemId      = $tjucmHelper->getItemId($link);
		$user        = Factory::getUser();

		// Get Filters
		$filters = Factory::getApplication()->input->get('filter', '', 'Array');
		$clusterFilter = '';

		// Check school filter applied  or not
		if (!empty($filters['cluster_id']))
		{
			// Add filter in ULR to apply filter on list views
			$clusterFilter = '&cluster=' . (INT) $filters['cluster_id'];
		}
		elseif($user->authorise('core.manageall', 'com_cluster') && empty($filters['cluster_id']))
		{
			$clusterFilter = '&cluster=all';
		}
		else
		{
			$clusterUserModel = ClusterFactory::model('ClusterUser', array('ignore_request' => true));
			$clusters = $clusterUserModel->getUsersClusters($user->id);

			foreach ($clusters as $cluster)
			{
				if (RBACL::check($user->id, 'com_cluster', 'core.view.own', 'com_tjdashboard', $cluster->cluster_id))
				{
					$clusterIds[] = $cluster->cluster_id;
				}
			}

			// Set first cluster in the widget url
			if (count($clusterIds) > 1)
			{
				$clusterFilter = '&cluster=' . $clusterIds[0];
			}
		}
		
		if (!empty($filters['tags']))
		{
			// Add filter in ULR to apply filter on list views
			foreach ($filters['tags'] as $key => $tag)
			{
				$tags[$key] = '&filter.tags[]=' .  $tag;
			}
			$clusterFilter = '&cluster=all';
		}
		else
		{
			$tags[$key] = '&filter.tags[]=';	
		}

		// $urls = array(Text::_('PLG_TJDASHBOARDSOURCE_DPE_BREACHLOG_OPEN') => '', Text::_('PLG_TJDASHBOARDSOURCE_DPE_BREACHLOG_CLOSED') => '');

		// $urls[Text::_('PLG_TJDASHBOARDSOURCE_DPE_BREACHLOG_OPEN')] = Route::_(
		// $link . $clusterFilter . '&com_tjucm_breachlog_makingtheroundstatus=In progress&Itemid=' . $itemId, false
		// );

		// $urls[Text::_('PLG_TJDASHBOARDSOURCE_DPE_BREACHLOG_CLOSED')] = Route::_(
		// $link . $clusterFilter . '&com_tjucm_breachlog_makingtheroundstatus=Closed&Itemid=' . $itemId, false
		// );

		$items = [];
		$items['data'] = ['count' => $this->getData()
		,'titleLink'  => Route::_(
			$link . $clusterFilter . implode('',$tags) . '&Itemid=' . $itemId, false
			)
		// ,'link'  => $urls
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
		return array('countlinkbox.tjdashcount' => "PLG_TJDASHBOARDRENDERER_COUNTLINKBOX",
		'piechart.tjdashcount' => "PLG_TJDASHBOARDRENDERER_PIECHART");
	}
}
