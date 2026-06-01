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

JLoader::import("/components/com_dpe/includes/dpe", JPATH_SITE);
JLoader::import("/components/com_cluster/includes/cluster", JPATH_ADMINISTRATOR);
JLoader::import("/components/com_subusers/includes/rbacl", JPATH_ADMINISTRATOR);

$lang      = Factory::getLanguage();
$lang->load('plg_tjdashboardsource_dpe', JPATH_ADMINISTRATOR);

/**
 *  DPE plugin for tjdashboardsource to get ROP details
 *
 * @since  __DEPLOY_VERSION__
 */

class DpeRopDatasource
{
	public $dataSourceName = "PLG_TJDASHBOARDSOURCE_DPE_ROP";

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
			$db  = Factory::getDbo();
			$app = Factory::getApplication();
			$input = $app->input;
			$user  = Factory::getUser();

			$params = DPE::config();
			$requestStatus  = (int) $params->get('ropRequestStatus', '0');
			$reviewDate     = (int) $params->get('ropReviewDate', '0');

			$currentDate   = new Date('now', 'UTC');
			$dbDateFormat  = "DATE_FORMAT(" . $db->quote($currentDate) . ", '%Y-%m-%d')";
			$dueDateFormat = "DATE_FORMAT(" . $db->qn('fv.value') . ", '%Y-%m-%d')";

			// Create a new query object.
			$query = $db->getQuery(true);

			// Select the required fields from the table.
			$query->select("count(a.id) AS ucmcount, SUM(CASE WHEN fcv.value = 'In progress' THEN 1 ELSE 0 END) as progresscount
			, SUM(CASE WHEN fcv.value = 'ToDo' THEN 1 ELSE 0 END) as todocount
			, SUM(CASE WHEN fcv.value = 'DPO Review' THEN 1 ELSE 0 END) as reviewcount,
			SUM(CASE WHEN fcv.value = 'Complete' THEN 1 ELSE 0 END) as complete");

/*
	$genericCluster = (int) $params->get('cluster_id', '0');
	SUM(CASE WHEN a.cluster_id = '" . $genericCluster . "' THEN 1 ELSE 0 END) as genericRopcount
*/

			$query->from($db->quoteName('#__tj_ucm_data', 'a'));

			$query->join('LEFT', $db->qn('#__tjfields_fields_value', 'fcv') . ' ON (' .
			$db->qn('fcv.content_id') . ' = ' . $db->qn('a.id') . ' AND ' . $db->qn('fcv.field_id') . ' = ' . $db->q($requestStatus) . ')');

/*
 *
 *  To get overdate
	$query->select("SUM(CASE WHEN ( (lower(fcv.value) ='in progress' OR lower(fcv.value) = 'todo') AND DATEDIFF( " .
	$dueDateFormat . "," . $dbDateFormat . ") < 0 ) THEN 1 ELSE 0 END) AS overDate");

	$query->join('LEFT', $db->qn('#__tjfields_fields_value', 'fv') . ' ON (' .
	$db->qn('fv.content_id') . ' = ' . $db->qn('a.id') . ' AND ' . $db->qn('fv.field_id') . ' = ' . $db->q($reviewDate) . ')');

	Text::_('PLG_TJDASHBOARDSOURCE_DPE_ROP_OVERDUE') => (($ropData['overDate']) ? $ropData['overDate'] : 0)
*/
			$query->where($db->qn('a.client') . " = 'com_tjucm.rop'");

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

				$agencyTags =  $filters['tags'];
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
						$query->where($db->qn('a.cluster_id') . ' IN ( ' .  implode(',', $filters['cluster_id']) . ')' );
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

/**
 *
			// Add generic school id in a query
			if ($genericCluster)
			{
				$clusterIds[] = $genericCluster;
			}
*/

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

			$ropData = $db->loadAssoc();
		}
		catch (Exception $e)
		{
			throw new Exception($e->getMessage());
		}

		$progresscount = ($ropData['progresscount']) ? $ropData['progresscount'] : 0;
		$reviewcount   = ($ropData['reviewcount']) ? $ropData['reviewcount'] : 0;
		$completecount = ($ropData['complete']) ? $ropData['complete'] : 0;
		$ucmCount      = ($ropData['ucmcount']) ? $ropData['ucmcount'] : 0;

		$recordInfo = array();
		$recordInfo['widgetcolor'] = array(
		Text::_('PLG_TJDASHBOARDSOURCE_DPE_ROP_COMPLETE') => (($completecount == 0) ? 'text-success' : 'text-amber'),
		Text::_('PLG_TJDASHBOARDSOURCE_DPE_ROP_OPEN') => (($progresscount == 0) ? 'text-success' : 'text-amber'),
		Text::_('PLG_TJDASHBOARDSOURCE_DPE_ROP_DPO_REVIEW') => (($reviewcount == 0) ? 'text-success' : 'text-amber')
		);

		$recordInfo['widgetdata'] = array(
		Text::_('PLG_TJDASHBOARDSOURCE_DPE_ROP_COMPLETE') => $completecount,
		Text::_('PLG_TJDASHBOARDSOURCE_DPE_ROP_OPEN') => $progresscount,
		Text::_('PLG_TJDASHBOARDSOURCE_DPE_ROP_DPO_REVIEW') => $reviewcount
		);

		$recordInfo['total']       = $ucmCount;
		$recordInfo['id']          = "rop";

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
		$link     = 'index.php?option=com_tjucm&view=items&client=com_tjucm.rop';
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

		// Get tag Filters

		if (!empty($filters['tags']))
		{
			// Add filter in ULR to apply filter on list views
			foreach ($filters['tags'] as $key => $tag)
			{
				$tags[$key] = '&filter[tags][]=' .  $tag;
			}
			$clusterFilter = '&cluster=all';
		}
		else
		{
			$tags[0] = '&filter[tags][]=';	
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
