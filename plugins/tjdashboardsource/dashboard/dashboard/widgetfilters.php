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

class DashboardWidgetFiltersDatasource
{
	public $dataSourceName = "PLG_TJDASHBOARDSOURCE_DASHBOARD_WIDGET_FILTERS";

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
			$clusterList = array();

			// Check if com_cluster component is installed
			if (ComponentHelper::getComponent('com_cluster', true)->enabled)
			{
				$user = Factory::getUser();
				$clusterUserModel = ClusterFactory::model('ClusterUser', array('ignore_request' => true));
				$clusters = $clusterUserModel->getUsersClusters($user->id);

				if (count($clusters) > 1)
				{
					$params                   = ComponentHelper::getParams('com_multiagency');
					$multiagencyTrusteeRoleId = (int) $params->get('multiagency_trustee_group');
				
					if ($user->authorise('core.manageall', 'com_cluster') || in_array($multiagencyTrusteeRoleId, $user->groups))
					{
						$clusterList['cluster_id'][] = HTMLHelper::_('select.option', "", Text::sprintf('PLG_TJDASHBOARDSOURCE_DASHBOARD_SELECT_CLUSTER', Text::_('COM_MULTIAGENCY_ORGANISATION')));
					}
				}

				// Get com_subusers component status
				$subUserExist = ComponentHelper::getComponent('com_subusers', true)->enabled;

				if ($subUserExist)
				{
					JLoader::import("/components/com_subusers/includes/rbacl", JPATH_ADMINISTRATOR);
				}

				// Create oprion for each cluster
				foreach ($clusters as $cluster)
				{
					// DPE - Hack to check its manager
					if ($subUserExist)
					{
						// Check user have permission to manage all clusters
						if (!$user->authorise('core.manageall', 'com_cluster'))
						{
							// Check user having permission to add staff
							if (RBACL::check($user->id, 'com_cluster', 'core.view.own', 'com_tjdashboard', $cluster->cluster_id))
							{
								$clusterList['cluster_id'][] = HTMLHelper::_('select.option', $cluster->cluster_id, trim($cluster->name));
							}
						}
						else
						{
							$clusterList['cluster_id'][] = HTMLHelper::_('select.option', $cluster->cluster_id, trim($cluster->name));
						}
					}
					else
					{
						$clusterList['cluster_id'][] = HTMLHelper::_('select.option', $cluster->cluster_id, trim($cluster->name));
					}
				}
			}
		}
		catch (Exception $e)
		{
			throw new Exception($e->getMessage());
		}

		$recordInfo = array(Text::sprintf('PLG_TJDASHBOARDSOURCE_DASHBOARD_CLUSTER_FILTER', Text::_('COM_MULTIAGENCY_ORGANISATION')) => '');
		$recordInfo[Text::sprintf('PLG_TJDASHBOARDSOURCE_DASHBOARD_CLUSTER_FILTER', Text::_('COM_MULTIAGENCY_ORGANISATION'))] = $clusterList;

		return $recordInfo;
	}

	/**
	 * Get Data for Filter
	 *
	 * @return string dataArray
	 *
	 * @since   __DEPLOY_VERSION__
	 * */
	public function getDataFilterboxTjdashfilter()
	{
		$items = [];
		$items['data'] = ['filters' => $this->getData(),
		'title' => Text::sprintf('PLG_TJDASHBOARDSOURCE_DASHBOARD_WIDGET_LABEL', Text::_('COM_MULTIAGENCY_ORGANISATION'))
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
		return array('filterbox.tjdashfilter' => "PLG_TJDASHBOARDRENDERER_FILTERBOX");
	}
}
