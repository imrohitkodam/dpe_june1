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

JLoader::import("/components/com_multiagency/includes/multiagency", JPATH_SITE);
JLoader::import("/components/com_cluster/includes/cluster", JPATH_ADMINISTRATOR);
JLoader::import("/components/com_subusers/includes/rbacl", JPATH_ADMINISTRATOR);

/**
 * DPE plugin for tjdashboardsource to get User information
 *
 * @since  __DEPLOY_VERSION__
 */

class DpeUserDatasource
{
	public $dataSourceName = "PLG_TJDASHBOARDSOURCE_DPE_USER";

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

			$params = Multiagency::config();

			// $managerRoleId = (int) $params->get('manager_role_id', '0');
			$staffRoleId = (int) $params->get('member_role_id', '0');
			$adminRoleId = (int) $params->get('school_admin_role_id', '0');
			$trusteeRoleId = (int) $params->get('organization_trustee_role_id');

			$query = $db->getQuery(true);

			// Select the required fields from the table.
			$query->select(' count(DISTINCT(u.id)) AS usercount
			, SUM(CASE WHEN ( role.id = ' . $staffRoleId . ') THEN 1 ELSE 0 END) AS staff
			, SUM(CASE WHEN ( role.id = ' . $adminRoleId . ') THEN 1 ELSE 0 END) AS admin
			, SUM(CASE WHEN ( role.id = ' . $trusteeRoleId . ') THEN 1 ELSE 0 END) AS trustee');

			$query->from($db->qn('#__users', 'u'));

			$query->join('INNER', $db->qn('#__tjsu_users', 'su') . ' ON (' .
			$db->qn('su.user_id') . ' = ' . $db->qn('u.id') . ' AND ' . $db->qn('su.client') . ' = ' . $db->q('com_multiagency') . ' )');

			$query->join('INNER', $db->qn('#__tjsu_roles', 'role') . ' ON (' .
			$db->qn('su.role_id') . ' = ' . $db->qn('role.id') . ' )');

			$query->join('INNER', $db->qn('#__tj_clusters', 'tjc') . ' ON (' .
			$db->qn('su.client_id') . ' = ' . $db->qn('tjc.client_id') . ')');

			$query->where($db->qn('tjc.state') . '=1');
			$query->where($db->qn('u.block') . '=0');

			// Get Filters
			$filters = $input->get('filter', '', 'Array');

			// Filter for tags and checked for Dpe admin here
		if (!empty($filters))
		{
				$params     			  = ComponentHelper::getParams('com_multiagency');
				$multiagencyTrusteeRoleId = (int) $params->get('multiagency_trustee_group');
				$orgAdminRoleId           = (int) $params->get('multiagency_school_admin_group', '0', 'INT');
				$orgAdminRole             = in_array($orgAdminRoleId , $user->groups);
	
			if ($filters['tags'] && !$filters['cluster_id'] && ($user->authorise('core.manageall', 'com_cluster') || in_array($multiagencyTrusteeRoleId, $user->groups) || $orgAdminRole))
			{	
				BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_dpe/models', 'DpeModel');
				$dashBoardModel = BaseDatabaseModel::getInstance('Dashboard', 'DpeModel');

				$tags = $filters['tags'];
				$filters['cluster_id'] = $dashBoardModel->getClusterIdsByTags($tags);
			}
		}

			if (!empty($filters['cluster_id']))
			{
				if (is_array($filters['cluster_id']))
				{
					$query->where($db->qn('tjc.id') . 'IN ( ' . implode(',', $filters['cluster_id']) . ')');
				}
				else
				{
					$query->where($db->qn('tjc.id') . '= ' . (INT) $filters['cluster_id']);
				}

				// Code use to apply allocated roles condition in query for selected cluster
				if (!$user->authorise('core.manageall', 'com_cluster'))
				{
					$clusterInfo = ClusterCluster::getInstance((INT) $filters['cluster_id']);
					$userFormModel = Multiagency::model('UserForm', array('ignore_request' => true));

					$roles = $userFormModel->getUserAgencyRole($clusterInfo->client_id);

					if (!empty($roles))
					{
						$allowRoles = array_column($roles, 'role_id');

						// Commenting this, We wants to show all counts to all user group.
						// $query->where("su.role_id  IN ('" . implode("','", $allowRoles) . "')");
					}
				}
			}

			// checked for org admin here.  
			if (!$user->authorise('core.manageall', 'com_cluster'))
			{
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

			$userData = $db->loadAssoc();
		}
		catch (Exception $e)
		{
			throw new Exception($e->getMessage());
		}

		$recordInfo = array();

		$recordInfo['widgetcolor'] = array(
		Text::_('PLG_TJDASHBOARDSOURCE_DPE_USER_STAFF') => '',
		Text::_('PLG_TJDASHBOARDSOURCE_DPE_USER_MANAGER') => '',
		Text::_('PLG_TJDASHBOARDSOURCE_DPE_USER_ADMIN') => '',
		Text::_('PLG_TJDASHBOARDSOURCE_DPE_USER_TRUSTEE') => ''
		);

		$recordInfo['widgetdata'] = array(
		Text::_('PLG_TJDASHBOARDSOURCE_DPE_USER_STAFF') => (($userData['staff']) ? $userData['staff'] : 0),
		// Text::_('PLG_TJDASHBOARDSOURCE_DPE_USER_MANAGER') => (($userData['manager']) ? $userData['manager'] : 0),
		Text::_('PLG_TJDASHBOARDSOURCE_DPE_USER_ADMIN') => (($userData['admin']) ? $userData['admin'] : 0),
		Text::_('PLG_TJDASHBOARDSOURCE_DPE_USER_TRUSTEE') => (($userData['trustee']) ? $userData['trustee'] : 0)
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
		$link   = 'index.php?option=com_multiagency&view=users';
		$multiagencyUtility = Multiagency::utilities();
		$itemId = $multiagencyUtility->getItemId($link);

		// Get Filters
		$filters = Factory::getApplication()->input->get('filter', '', 'Array');
		$schoolFilter = '';

		// Check school filter applied  or not
		if (!empty($filters['cluster_id']) && (INT) $filters['cluster_id'])
		{
			/*
			JLoader::import("/components/com_cluster/libraries/cluster", JPATH_ADMINISTRATOR);
			$cluster = ClusterCluster::getInstance((INT) $filters['cluster_id']);
			*/

			// Add filter in ULR to apply filter on list views
			$schoolFilter = '&filter[agencies]=' . $filters['cluster_id'];
		}
		elseif (Factory::getUser()->authorise('core.manageall', 'com_cluster') && empty($filters['cluster_id']))
		{
			$schoolFilter = '&filter[agencies]=all';
		}

		$params = Multiagency::config();

		// $managerRoleId = (int) $params->get('manager_role_id', '0');
		$staffRoleId = (int) $params->get('member_role_id', '0');
		$adminRoleId = (int) $params->get('school_admin_role_id', '0');


		if (!empty($filters['tags']))
		{
			// Add filter in ULR to apply filter on list views
			foreach ($filters['tags'] as $key => $tag)
			{
				$tags[$key] = '&filter[tags][]=' .  $tag;
			}
			$schoolFilter = '';
		}
		else
		{
			$tags[0] = '&filter.tags[]=';	
		}

		$tags  = is_array($tags)? implode('', $tags):'';
		$urls = array();

		/*
			$urls[Text::_('PLG_TJDASHBOARDSOURCE_DPE_USER_MANAGER')] = Route::_(
				$link . $schoolFilter . '&filter[role_id]=' . $managerRoleId . '&Itemid=' . $itemId, false
			);
		*/

		$urls[Text::_('PLG_TJDASHBOARDSOURCE_DPE_USER_STAFF')] = Route::_(
			$link . $schoolFilter . '&filter[role_id]=' . $staffRoleId . '&Itemid=' . $itemId, false
		);
		$urls[Text::_('PLG_TJDASHBOARDSOURCE_DPE_USER_ADMIN')] = Route::_(
			$link . $schoolFilter . '&filter[role_id]=' . $adminRoleId . '&Itemid=' . $itemId, false
		);

		$items = [];
		$items['data'] = ['count' => $this->getData()
		,'titleLink'  => Route::_(
			$link . $schoolFilter . $tags . '&Itemid=' . $itemId, false
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
		return array('countlinkbox.tjdashcount' => "PLG_TJDASHBOARDRENDERER_COUNTLINKBOX");
	}
}
