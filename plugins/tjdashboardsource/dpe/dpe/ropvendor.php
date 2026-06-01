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
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Component\ComponentHelper;

$lang      = Factory::getLanguage();
$lang->load('plg_tjdashboardsource_dpe', JPATH_ADMINISTRATOR);

/**
 * DPE plugin for tjdashboardsource
 *
 * @since  __DEPLOY_VERSION__
 */

class DpeRopvendorDatasource
{
	public $dataSourceName = "PLG_TJDASHBOARDSOURCE_ROPVENDOR";

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
			$db             = Factory::getDbo();
			$app            = Factory::getApplication();
			$input          = $app->input;
			$user           = Factory::getUser();
			$params         = DPE::config();
			$requestStatus  = (int) $params->get('ropvendorsStatus', '0');
			$requestExtraStatus = (int) $params->get('ropvendorsDuediligenceStatus', '0');

			// Create a new query object.
			$query = $db->getQuery(true);

			// Select the required fields from the table.
			$query->select("
				count(a.id) AS ucmcount,
				SUM(CASE WHEN fcv.value = 'Low' THEN 1 ELSE 0 END) as low,
				SUM(CASE WHEN fcv.value = 'Medium' THEN 1 ELSE 0 END) as medium,
				SUM(CASE WHEN fcv.value = 'High' THEN 1 ELSE 0 END) as high,
				SUM(CASE WHEN fcv.value = 'Unassessed' THEN 1 ELSE 0 END) as unassessed,
				SUM(CASE WHEN fev.value IN ('Queried', 'Initialised', 'Returned from supplier', 'Sent to supplier') THEN 1 ELSE 0 END) as in_progress,SUM(CASE WHEN fev.value = 'Failed to respond' THEN 1 ELSE 0 END) as failedtorespond,SUM(CASE WHEN fev.value = 'Complete' THEN 1 ELSE 0 END) as vendorComplete
				");


			$query->from($db->quoteName('#__tj_ucm_data', 'a'));

			$query->join('LEFT', $db->qn('#__tjfields_fields_value', 'fcv') . ' ON (' .
				$db->qn('fcv.content_id') . ' = ' . $db->qn('a.id') . ' AND ' . $db->qn('fcv.field_id') . ' = ' . $db->q($requestStatus) . ')');
			$query->join('LEFT', $db->qn('#__tjfields_fields_value', 'fev') . ' ON (' .
				$db->qn('fev.content_id') . ' = ' . $db->qn('a.id') . ' AND ' . $db->qn('fev.field_id') . ' = ' . $db->q($requestExtraStatus) . ')');

			$query->where($db->qn('a.client') . " = 'com_tjucm.ropvendors'");

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

					$agencyTags = $filters['tags'];
					$filters['cluster_id'] = $dashBoardModel->getClusterIdsByTags($agencyTags);
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
						$query->where($db->qn('a.cluster_id') . ' IN (' .  implode(',', $filters['cluster_id']). ")");
					}
					else
					{
						$query->where($db->qn('a.cluster_id') . ' = ' .  (INT) $filters['cluster_id']);
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
			$softwareData = $db->loadAssoc();
		}
		catch (Exception $e)
		{
			throw new Exception($e->getMessage());
		}

		$low = ($softwareData['low']) ? $softwareData['low'] : 0;
		$medium     = ($softwareData['medium']) ? $softwareData['medium'] : 0;
		$high     = ($softwareData['high']) ? $softwareData['high'] : 0;
		$unassessed     = ($softwareData['unassessed']) ? $softwareData['unassessed'] : 0;
		$ucmCount      = ($softwareData['ucmcount']) ? $softwareData['ucmcount'] : 0;

		$recordInfo = array();
		$recordInfo['widgetcolor'] = array(
			Text::_('PLG_TJDASHBOARDSOURCE_DPE_VENDOR_LOW') => (($low == 0) ? 'text-success' : 'text-success'),
			Text::_('PLG_TJDASHBOARDSOURCE_DPE_VENDOR_MEDIUM') => (($medium == 0) ? 'text-success' : 'text-amber'),
			Text::_('PLG_TJDASHBOARDSOURCE_DPE_VENDOR_HIGH') => (($high == 0) ? 'text-success' : 'text-amber'),
			Text::_('PLG_TJDASHBOARDSOURCE_DPE_VENDOR_UNASSSESSED') => (($unassessed == 0) ? 'text-success' : 'text-lightblue')
		);

		$recordInfo['widgetdata'] = array(
			Text::_('PLG_TJDASHBOARDSOURCE_DPE_VENDOR_LOW') => $low,
			Text::_('PLG_TJDASHBOARDSOURCE_DPE_VENDOR_MEDIUM') => $medium,
			Text::_('PLG_TJDASHBOARDSOURCE_DPE_VENDOR_HIGH') => $high,
			Text::_('PLG_TJDASHBOARDSOURCE_DPE_VENDOR_UNASSSESSED') => $unassessed
		);

		$recordInfo['total']       = $ucmCount;
		$recordInfo['id']          = "ropvendors";
		$recordInfo['vendorinprogress']       =$softwareData['in_progress'];
		$recordInfo['vendorfailedtorespond']       =$softwareData['failedtorespond'];
		$recordInfo['vendorComplete']       =$softwareData['vendorComplete'];



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
		$link     = 'index.php?option=com_tjucm&view=items&client=com_tjucm.ropvendors';
		JLoader::import("/components/com_tjucm/helpers/tjucm", JPATH_SITE);
		$tjucmHelper = new TjucmHelpersTjucm;
		$itemId      = $tjucmHelper->getItemId($link);

		$app      = Factory::getApplication();
		$menu     = $app->getMenu();
		$user     = Factory::getUser();

		// Get Filters
		$filters = Factory::getApplication()->input->get('filter', '', 'Array');
		$clusterFilter = '';

		// Check school filter applied  or not
		if (!empty($filters['cluster_id']))
		{
			// Add filter in ULR to apply filter on list views
			$clusterFilter = '&cluster=' . (INT) $filters['cluster_id'];
		}
		elseif (Factory::getUser()->authorise('core.manageall', 'com_cluster') && empty($filters['cluster_id']))
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
			if (count(isset($clusterIds)?$clusterIds:0) > 1)
			{
				$clusterFilter = '&cluster=' . $clusterIds[0];
			}
		}

		
		if (!empty($filters['tags']))
		{
			// Add filter in ULR to apply filter on list views
			foreach ($filters['tags'] as $key => $tag)
			{
				$tags[$key] = '&tags[]=' .  $tag;
			}
			$clusterFilter = '';
		}
		else
		{
			$tags[0] = '&filter_tags[]=';	
		}

		$tags = is_array($tags)? implode('', $tags):'';

		$items = [];
		$items['data'] = ['count' => $this->getData()
		,'titleLink'  => Route::_(
			$link . $clusterFilter . $tags . '&Itemid=' . $itemId, false
		)
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
