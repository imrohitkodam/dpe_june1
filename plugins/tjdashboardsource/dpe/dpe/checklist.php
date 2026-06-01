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

$lang      = Factory::getLanguage();
$lang->load('plg_tjdashboardsource_dpe', JPATH_ADMINISTRATOR);

JLoader::import("/components/com_dpe/includes/dpe", JPATH_SITE);
JLoader::import("/components/com_subusers/includes/rbacl", JPATH_ADMINISTRATOR);

/**
 * DPE plugin for tjdashboardsource to get Checklist details
 *
 * @since  __DEPLOY_VERSION__
 */

class DpeChecklistDatasource
{
	public $dataSourceName = "PLG_TJDASHBOARDSOURCE_DPE_CHECKLIST";

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

			// Create a new query object.
			$query = $db->getQuery(true);

			// Select the required fields from the table.
			$query->select("a.id AS ucmcount");

			// Create a new subquery for todos record
			$todosubquery = $db->getQuery(true);
			$todosubquery->select("SUM(CASE WHEN fcv.value = 'todo' THEN 1 ELSE 0 END)");
			$todosubquery->from($db->qn('#__tjfields_fields_value', 'fcv'));
			$todosubquery->where($db->qn('fcv.content_id') . ' = ' . $db->qn('a.id'));
			$query->select("( " . $todosubquery . ") AS todocount");

			// Create a new subquery for inprogress record
			$inprogresssubquery = $db->getQuery(true);
			$inprogresssubquery->select("SUM(CASE WHEN fcvi.value = 'inprogress' THEN 1 ELSE 0 END)");
			$inprogresssubquery->from($db->qn('#__tjfields_fields_value', 'fcvi'));
			$inprogresssubquery->where($db->qn('fcvi.content_id') . ' = ' . $db->qn('a.id'));
			$query->select("( " . $inprogresssubquery . ") AS inprogresscount");

			// Create a new subquery for done record
			$donesubquery = $db->getQuery(true);
			$donesubquery->select("SUM(CASE WHEN fcvd.value = 'done' THEN 1 ELSE 0 END)");
			$donesubquery->from($db->qn('#__tjfields_fields_value', 'fcvd'));
			$donesubquery->where($db->qn('fcvd.content_id') . ' = ' . $db->qn('a.id'));
			$query->select("( " . $donesubquery . ") AS donecount");

			$query->from($db->quoteName('#__tj_ucm_data', 'a'));
			$query->join('INNER', $db->qn('#__tj_ucm_types', 't') . ' ON (' .
			$db->qn('a.type_id') . ' = ' . $db->qn('t.id') . ')');

			$query->where($db->quoteName('t.params') . ' LIKE "%dpe_checklist=1%"');
			$query->where($db->quoteName('t.state') . ' = ' . $db->q('1'));

			// Get Filters
			$filters = $input->get('filter', '', 'Array');
			
			// checked for dpe admin here. 
			if (!empty($filters))
				{ 
					$params     			  = ComponentHelper::getParams('com_multiagency');
				    $multiagencyTrusteeRoleId = (int) $params->get('multiagency_trustee_group');
				    $orgAdminRoleId           = (int) $params->get('multiagency_school_admin_group', '0', 'INT');
					$orgAdminRole           = in_array($orgAdminRoleId , $user->groups);
				if ($filters['tags'] && !$filters['cluster_id'] && ($user->authorise('core.manageall', 'com_cluster') || in_array($multiagencyTrusteeRoleId, $user->groups)|| $orgAdminRole))
				{	
					BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_dpe/models', 'DpeModel');
					$dashBoardModel = BaseDatabaseModel::getInstance('Dashboard', 'DpeModel');

					$filters['cluster_id'] = $dashBoardModel->getClusterIdsByTags($filters['tags']);
				}
			}
			
			if (!empty($filters['cluster_id']))
			{
				if (is_array($filters['cluster_id']))
				{
					$query->where($db->qn('a.cluster_id') . ' IN ( ' .  implode(',', $filters['cluster_id']) . ')');
				}
				else
				{
					$query->where($db->qn('a.cluster_id') . ' = ' .  (INT) $filters['cluster_id']);
				}
				
			}

			if ($user->id && !$user->authorise('core.manageall', 'com_cluster'))
			{
				JLoader::import("/components/com_cluster/includes/cluster", JPATH_ADMINISTRATOR);
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

				if (empty($filters['cluster_id']))
				{
					$clusterIds =implode(', ', $clusterIds);
				$query->where("(" . $db->qn('a.cluster_id') . "IN (" . $clusterIds . "))");					
				}
			}

			$mainQuery = $db->getQuery(true);
			$mainQuery->select('count(ucmcount) AS totalChecklist
			, SUM(todocount) AS todochecklist
			, SUM(inprogresscount)AS progressChecklist
			, SUM(donecount) AS donechecklist');
			$mainQuery->from('( ' . $query . ' ) AS checklistUcm');

			$db->setQuery($mainQuery);

			$checklistData = $db->loadAssoc();
		}
		catch (Exception $e)
		{
			throw new Exception($e->getMessage());
		}

		$todochecklist     = ($checklistData['todochecklist']) ? $checklistData['todochecklist'] : 0;
		$progressChecklist = ($checklistData['progressChecklist']) ? $checklistData['progressChecklist'] : 0;
		$donechecklist     = ($checklistData['donechecklist']) ? $checklistData['donechecklist'] : 0;
		$ucmCount          = ($checklistData['totalChecklist']) ? $checklistData['totalChecklist'] : 0;

		$recordInfo = array();
		$recordInfo['widgetcolor'] = array(
			Text::_('PLG_TJDASHBOARDSOURCE_DPE_CHECKLIST_DONE') => 'text-success',
			Text::_('PLG_TJDASHBOARDSOURCE_DPE_CHECKLIST_INPROGRESS') => (($progressChecklist == 0) ? 'text-success' : 'text-amber'),
			Text::_('PLG_TJDASHBOARDSOURCE_DPE_CHECKLIST_TODO') => (($todochecklist == 0) ? 'text-success' : 'text-red-500')
		);

		$recordInfo['widgetdata'] = array(
			Text::_('PLG_TJDASHBOARDSOURCE_DPE_CHECKLIST_DONE') => $donechecklist,
			Text::_('PLG_TJDASHBOARDSOURCE_DPE_CHECKLIST_INPROGRESS') => $progressChecklist,
			Text::_('PLG_TJDASHBOARDSOURCE_DPE_CHECKLIST_TODO') => $todochecklist
		);

		$recordInfo['total'] = $ucmCount;
		$recordInfo['id']    = "checklist";

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
		// Get Filters
		$filters       = Factory::getApplication()->input->get('filter', '', 'Array');
		$clusterFilter = '';
		$user          = Factory::getUser();

		// Check school filter applied  or not
		if (!empty($filters['cluster_id']))
		{
			// Add filter in ULR to redirect with filter
			$clusterFilter = '&filter[cluster_id]=' . (INT) $filters['cluster_id'];
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
			$tags = '&filter.tags[]=';	
		}

		$tags = is_array($tags)? implode('', $tags):'';


		$link       = 'index.php?option=com_dpe&view=dashboardchecklist';
		$dpeUtility = DPE::utilities();
		$itemId = $dpeUtility->getItemId($link);

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
