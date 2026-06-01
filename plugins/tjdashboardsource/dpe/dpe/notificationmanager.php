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
use Joomla\CMS\Date\Date;
use Joomla\CMS\Component\ComponentHelper;

$lang      = Factory::getLanguage();
$lang->load('plg_tjdashboardsource_dpe', JPATH_ADMINISTRATOR);

JLoader::import("/components/com_sla/includes/sla", JPATH_SITE);
JLoader::import("/components/com_subusers/includes/rbacl", JPATH_ADMINISTRATOR);

/**
 * DPE plugin for tjdashboardsource to get notification manager info
 *
 * @since  __DEPLOY_VERSION__
 */

class DpeNotificationManagerDatasource
{
	public $dataSourceName = "PLG_TJDASHBOARDSOURCE_DPE_NOTIFICATIONMANAGER";

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
			$app   = Factory::getApplication();
			$input = $app->input;
			$user  = Factory::getUser();
			$query = $db->getQuery(true);

			$currentDate = new Date('now', 'UTC');
			$dbDateFormat = "DATE_FORMAT(" . $db->quote($currentDate) . ", '%Y-%m-%d')";
			$dueDateFormat = "DATE_FORMAT(" . $db->qn('todo.due_date') . ", '%Y-%m-%d')";

			// Select the required fields from the table.
			$query->select("count(DISTINCT(todo.id)) AS  todocount
			, SUM(CASE WHEN todo.status = 'C' THEN 1 ELSE 0 END) as completecount
			");
			$query->select("SUM(CASE WHEN ( todo.status != 'C' AND DATEDIFF( " . $dueDateFormat . ","
			. $dbDateFormat . ") < 0 ) THEN 1 ELSE 0 END) AS overdue");

			$query->from($db->qn('#__jlike_todos', 'todo'));

			$query->join('INNER', $db->quoteName('#__jlike_todos_cluster_xref', 'todoxref') . ' ON (' .
			$db->qn('todo.id') . ' = ' . $db->qn('todoxref.todo_id') . ')');

			$query->join('INNER', $db->qn('#__tj_clusters', 'tjc') . ' ON (' .
			$db->qn('tjc.id') . ' = ' . $db->qn('todoxref.cluster_id') . ')');

			// Get count of active activities only
			$query->where($db->qn('todo.state') . ' = 1');
			$query->where($db->qn('tjc.state') . ' = 1');

			// Get Filters
			$filters = $input->get('filter', '', 'Array');

			//Filter for tags and checked for Dpe admin here
			if (!empty($filters))
			{	
				$params     			  = ComponentHelper::getParams('com_multiagency');
				$multiagencyTrusteeRoleId = (int) $params->get('multiagency_trustee_group');
				$orgAdminRoleId           = (int) $params->get('multiagency_school_admin_group', '0', 'INT');
				$orgAdminRole           = in_array($orgAdminRoleId , $user->groups);

				if ($filters['tags'] && !$filters['cluster_id'] && ($user->authorise('core.manageall', 'com_cluster') || $orgAdminRole))
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

				foreach ($clusters as $cluster)
				{
					// Check user have permission to manage all clusters
					if (!$user->authorise('core.manageall', 'com_cluster'))
					{
						if (RBACL::check($user->id, 'com_cluster', 'core.manageNotificationManager', 'com_jlike', $cluster->cluster_id))
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
					$query->where($db->qn('tjc.id') . " = " . $clusterIds[0]);
				}
			}

			// Filter by cluster End

			$db->setQuery($query);

			$todoData = $db->loadAssoc();
		}
		catch (Exception $e)
		{
			throw new Exception($e->getMessage());
		}

		$recordInfo = array();

		$recordInfo = array();
		$recordInfo['widgetcolor'] = array(
			Text::_('PLG_TJDASHBOARDSOURCE_DPE_TOTAL_NOTIFICATIONS') => '',
			Text::_('PLG_TJDASHBOARDSOURCE_DPE_NOTIFICATION_COMPLETE') => '',
			Text::_('PLG_TJDASHBOARDSOURCE_DPE_NOTIFICATION_OVERDUE') => '',
		);

		$recordInfo['widgetdata'] = array(
			Text::_('PLG_TJDASHBOARDSOURCE_DPE_TOTAL_NOTIFICATIONS') => (
			($todoData['todocount']) ? $todoData['todocount'] : 0),
			Text::_('PLG_TJDASHBOARDSOURCE_DPE_NOTIFICATION_COMPLETE') => (
			($todoData['completecount']) ? $todoData['completecount'] : 0),
			Text::_('PLG_TJDASHBOARDSOURCE_DPE_NOTIFICATION_OVERDUE') => (
			($todoData['overdue']) ? $todoData['overdue'] : 0)
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
		$link       = 'index.php?option=com_jlike&view=recommendations';
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
				if (RBACL::check($user->id, 'com_cluster', 'core.manageNotificationManager', 'com_jlike', $cluster->cluster_id))
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

		if ($clusterId)
		{
			// Add filter in URL to apply filter on list views
			$agencyFilter = '&filter[agency_id]=' . $clusterId;
		}


		if (!empty($filters['tags']))
		{
			// Add filter in ULR to apply filter on list views
			foreach ($filters['tags'] as $key => $tag)
			{
				$tags[$key] = '&filter[tags][]=' .  $tag;
			}
			$agencyFilter = '';
		}
		else
		{
			$tags[0] = '&filter[tags]=';	
		}

		$tags = is_array($tags)? implode('', $tags):'';

		$urls = array(
			Text::_('PLG_TJDASHBOARDSOURCE_DPE_TOTAL_NOTIFICATIONS') => ''
			, Text::_('PLG_TJDASHBOARDSOURCE_DPE_NOTIFICATION_COMPLETE') => ''
			, Text::_('PLG_TJDASHBOARDSOURCE_DPE_NOTIFICATION_OVERDUE') => ''
		);

		$urls[Text::_('PLG_TJDASHBOARDSOURCE_DPE_TOTAL_NOTIFICATIONS')] = Route::_(
		$link . $agencyFilter . '&Itemid=' . $itemId, false
		);

		$urls[Text::_('PLG_TJDASHBOARDSOURCE_DPE_NOTIFICATION_COMPLETE')] = Route::_(
		$link . $agencyFilter . '&filter[status]=C&Itemid=' . $itemId, false
		);

		$urls[Text::_('PLG_TJDASHBOARDSOURCE_DPE_NOTIFICATION_OVERDUE')] = Route::_(
		$link . $agencyFilter . '&filter[status]=O&Itemid=' . $itemId, false
		);

		$items = [];
		$items['data'] = ['count' => $this->getData()
		,'titleLink'  => Route::_(
			$link . $agencyFilter . $tags . '&Itemid=' . $itemId, false
			), 'link'  => $urls
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
