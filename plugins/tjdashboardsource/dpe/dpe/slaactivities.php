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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Component\ComponentHelper;

$lang      = Factory::getLanguage();
$lang->load('plg_tjdashboardsource_dpe', JPATH_ADMINISTRATOR);

JLoader::import("/components/com_sla/includes/sla", JPATH_SITE);
JLoader::import("/components/com_subusers/includes/rbacl", JPATH_ADMINISTRATOR);

/**
 * DPE plugin for tjdashboardsource to get SlaActivities information
 *
 * @since  __DEPLOY_VERSION__
 */

class DpeSlaActivitiesDatasource
{
	public $dataSourceName = "PLG_TJDASHBOARDSOURCE_DPE_SLA_ACTIVITIES";

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

			$query = $db->getQuery(true);

			// Select the required fields from the table.
			$query->select("count(DISTINCT(sa.id)) AS  activitiescount
			, SUM(CASE WHEN todo.status = 'I' THEN 1 ELSE 0 END) as incompletecount
			, SUM(CASE WHEN todo.status = 'C' THEN 1 ELSE 0 END) as completecount
			, SUM(CASE WHEN todo.status = 'CN' THEN 1 ELSE 0 END) as cancelcount ");

			$query->from($db->qn('#__tj_sla_activities', 'sa'));

			$query->join('INNER', $db->qn('#__tj_slas', 's') . ' ON (' .
			$db->qn('s.id') . ' = ' . $db->qn('sa.sla_id') . ')');

			$query->join('INNER', $db->qn('#__tj_clusters', 'tjc') . ' ON (' .
			$db->qn('tjc.id') . ' = ' . $db->qn('sa.cluster_id') . ')');

			$query->join('INNER', $db->quoteName('#__jlike_todos', 'todo')
			. ' ON (' . $db->quoteName('todo.id') . ' = ' . $db->quoteName('sa.todo_id') . ')');

			// Get count of active activities only
			$query->where($db->qn('sa.state') . ' = 1');

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
					if (is_array($filters['cluster_id']))
					{
						$query->where($db->qn('tjc.id') . ' IN (' . implode(',', $filters['cluster_id']) . ')');
					}
					else
					{
						$query->where($db->qn('tjc.id') . ' = ' . (INT) $filters['cluster_id']);
					}
			}

			if (!$user->authorise('core.manageall', 'com_cluster'))
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
					// $query->where($db->qn('tjc.id') . " = " . $clusterIds[0]);
					$clusterIds =implode(', ', $clusterIds);
					$query->where("(" . $db->qn('tjc.id') . "IN (" . $clusterIds . "))");
				}
			}

			// Filter by cluster End

			$db->setQuery($query);

			$activitiesData = $db->loadAssoc();
		}
		catch (Exception $e)
		{
			throw new Exception($e->getMessage());
		}

		$recordInfo = array();
		$recordInfo['widgetcolor'] = array(
			Text::_('PLG_TJDASHBOARDSOURCE_DPE_SLA_INCOMPLETE') => '',
			Text::_('PLG_TJDASHBOARDSOURCE_DPE_SLA_COMPLETE') => '',
			Text::_('PLG_TJDASHBOARDSOURCE_DPE_SLA_CANCEL') => ''
		);

		$recordInfo['widgetdata'] = array(
			Text::_('PLG_TJDASHBOARDSOURCE_DPE_SLA_INCOMPLETE') => (
			($activitiesData['incompletecount']) ? $activitiesData['incompletecount'] : 0),
			Text::_('PLG_TJDASHBOARDSOURCE_DPE_SLA_COMPLETE') => (
			($activitiesData['completecount']) ? $activitiesData['completecount'] : 0),
			Text::_('PLG_TJDASHBOARDSOURCE_DPE_SLA_CANCEL') => (
			($activitiesData['cancelcount']) ? $activitiesData['cancelcount'] : 0)
		);

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
		$link       = 'index.php?option=com_sla&view=slaactivities';
		$slaUtility = SLA::utilities();
		$itemId     = $slaUtility->getItemId($link);
		$user       = Factory::getUser();

		// Get Filters
		$filters = Factory::getApplication()->input->get('filter', '', 'Array');
		$licenseFilter = '';

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

			// Set first cluster in the widget url
			if (count(isset($clusterIds)?$clusterIds:0) > 1)
			{
				$clusterId = $clusterIds[0];
			}
		}

		// Build filters for URL links (DPE Hack)
		$licenseFilter = '';
		$clusterFilter = '';
		$tagFilter     = '';

		$agencyTags = !empty($filters['tags']) ? $filters['tags'] : array();

		if (!empty($agencyTags))
		{
			if (!is_array($agencyTags))
			{
				$agencyTags = array($agencyTags);
			}

			foreach ($agencyTags as $tag)
			{
				$tagFilter .= '&filter[tags][]=' . (int) $tag;
			}
		}
		else
		{
			// If tags are NOT set, then we can pass cluster and license
			if ($clusterId)
			{
				// Load cluster table to get the agency id
				JLoader::import('/components/com_cluster/includes/cluster', JPATH_ADMINISTRATOR);
				$clustertable = ClusterFactory::table('Clusters');
				$clustertable->load(array('id' => (INT) $clusterId));

				// Get active licence of agency
				JLoader::import('components.com_multiagency.includes.multiagency', JPATH_SITE);
				$licenceTable = Multiagency::table('licence');
				$licenceTable->load(array('multiagency_id' => $clustertable->client_id, 'state' => 1));

				// Add filter in URL to apply filter on list views
				$licenseFilter = '&filter[license_id]=' . $licenceTable->id;
			}
		}

		$urls = array(
			Text::_('PLG_TJDASHBOARDSOURCE_DPE_SLA_INCOMPLETE') => ''
			, Text::_('PLG_TJDASHBOARDSOURCE_DPE_SLA_COMPLETE') => ''
			, Text::_('PLG_TJDASHBOARDSOURCE_DPE_SLA_CANCEL') => ''
		);

		$urls[Text::_('PLG_TJDASHBOARDSOURCE_DPE_SLA_INCOMPLETE')] = Route::_(
		$link . $licenseFilter . $clusterFilter . $tagFilter . '&filter[sla_status]=I&Itemid=' . $itemId, false
		);

		$urls[Text::_('PLG_TJDASHBOARDSOURCE_DPE_SLA_COMPLETE')] = Route::_(
		$link . $licenseFilter . $clusterFilter . $tagFilter . '&filter[sla_status]=C&Itemid=' . $itemId, false
		);

		$urls[Text::_('PLG_TJDASHBOARDSOURCE_DPE_SLA_CANCEL')] = Route::_(
		$link . $licenseFilter . $clusterFilter . $tagFilter . '&filter[sla_status]=CN&Itemid=' . $itemId, false
		);

		$items = [];
		$items['data'] = ['count' => $this->getData()
		,'titleLink'  => Route::_(
			$link . $licenseFilter . $clusterFilter . $tagFilter . '&Itemid=' . $itemId, false
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
		return array('countlinkbox.tjdashcount' => "PLG_TJDASHBOARDRENDERER_COUNTLINKBOX");
	}
}
