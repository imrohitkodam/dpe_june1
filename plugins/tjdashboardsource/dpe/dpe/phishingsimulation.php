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
 * DPE plugin for tjdashboardsource to get Gophish information
 *
 * @since  __DEPLOY_VERSION__
 */

class DpePhishingsimulationDatasource
{
	public $dataSourceName = "PLG_TJDASHBOARDSOURCE_DPE_GOPHISH";

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
			$query->select('count(DISTINCT(tjcamp.id)) AS  campaignscount, count(DISTINCT(tjgrp.id)) AS  groupscount');

			$query->from($db->qn('#__tjmultiagency_multiagency', 'a'));

			$query->join('INNER', $db->qn('#__tj_clusters', 'tjc') . ' ON (' .
			$db->qn('a.id') . ' = ' . $db->qn('tjc.client_id') . ')');

			$query->join('LEFT', $db->qn('#__tjgophish_campaign_ref', 'tjcamp') . ' ON (' .
			$db->qn('tjcamp.cluster_id') . ' = ' . $db->qn('tjc.id') . ' )');

			$query->join('LEFT', $db->qn('#__tjgophish_group_ref', 'tjgrp') . ' ON (' .
			$db->qn('tjgrp.cluster_id') . ' = ' . $db->qn('tjc.id') . ' )');

			$query->where($db->qn('a.state') . '=1');

			// Get Filter
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

			// checked for org admin here.  
			if (!$user->authorise('core.manageall', 'com_cluster'))
			{
				JLoader::import("/components/com_cluster/includes/cluster", JPATH_ADMINISTRATOR);
				$clusterUserModel = ClusterFactory::model('ClusterUser', array('ignore_request' => true));
				$clusters = $clusterUserModel->getUsersClusters($user->id);
				$clusterIds = array();

				foreach ($clusters as $cluster)
				{
					if (!empty($cluster->cluster_id) && RBACL::check($user->id, 'com_cluster', 'core.view.own', 'com_tjdashboard', $cluster->cluster_id))
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

			$gophishData = $db->loadAssoc();
		}
		catch (Exception $e)
		{
			throw new Exception($e->getMessage());
		}

		$recordInfo = array();
		$recordInfo['widgetcolor'] = array(
			Text::_('PLG_TJDASHBOARDSOURCE_DPE_GOPHISH_CAMPAIGNS') => '',
			Text::_('PLG_TJDASHBOARDSOURCE_DPE_GOPHISH_GROUPS') => '',
		);

		$recordInfo['widgetdata'] = array(
			Text::_('PLG_TJDASHBOARDSOURCE_DPE_GOPHISH_CAMPAIGNS') => (
			($gophishData['campaignscount']) ? $gophishData['campaignscount'] : 0),
			Text::_('PLG_TJDASHBOARDSOURCE_DPE_GOPHISH_GROUPS') => (
			($gophishData['groupscount']) ? $gophishData['groupscount'] : 0)
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
		$campaignsLink     = 'index.php?option=com_tjgophish&view=campaigns';
		$groupsLink        = 'index.php?option=com_tjgophish&view=groups';
		$app               = Factory::getApplication();
		$menu              = $app->getMenu();
		$groupsMenuItem    = $menu->getItems('link', 'index.php?option=com_tjgophish&view=groups', true);
		$campaignsMenuItem = $menu->getItems('link', 'index.php?option=com_tjgophish&view=campaigns', true);

		// Get Filters
		$filters       = $app->input->get('filter', '', 'Array');
		$clusterFilter = '';

		// Check school filter applied  or not
		if (!empty($filters['cluster_id']))
		{
			// Add filter in ULR to apply filter on list views
			$clusterFilter = '&filter[cluster_id]=' . $filters['cluster_id'];
		}

		if (!empty($filters['tags']))
		{
			// Add filter in ULR to apply filter on list views
			foreach ($filters['tags'] as $key => $tag)
			{
				$tags[$key] = '&filter[tags][]=' .  $tag;
			}
			$clusterFilter = '';

		}
		else
		{
			$tags[0] = '&filter[tags]=';	
		}
		$tags = is_array($tags)? implode('', $tags):'';

		$urls = array(
			Text::_('PLG_TJDASHBOARDSOURCE_DPE_GOPHISH_CAMPAIGNS') => ''
			, Text::_('PLG_TJDASHBOARDSOURCE_DPE_GOPHISH_GROUPS') => ''
		);

		$urls[Text::_('PLG_TJDASHBOARDSOURCE_DPE_GOPHISH_CAMPAIGNS')] = Route::_(
		$campaignsLink . $clusterFilter . $tags . '&Itemid=' . $campaignsMenuItem->id, false
		);

		$urls[Text::_('PLG_TJDASHBOARDSOURCE_DPE_GOPHISH_GROUPS')] = Route::_(
		$groupsLink . $clusterFilter . $tags . '&Itemid=' . $groupsMenuItem->id, false
		);

		$items = [];
		$items['data'] = ['count' => $this->getData(),'titleLink'  =>
		Route::_($campaignsLink . $clusterFilter . '&Itemid=' . $campaignsMenuItem->id, false),
		'link'  => $urls
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
