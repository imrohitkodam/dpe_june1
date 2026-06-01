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
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Component\ComponentHelper;

$lang      = Factory::getLanguage();
$lang->load('plg_tjdashboardsource_dpe', JPATH_ADMINISTRATOR);

JLoader::import("/components/com_dpe/includes/dpe", JPATH_SITE);

/**
 * DPE plugin for tjdashboardsource to get School information
 *
 * @since  __DEPLOY_VERSION__
 */

class DpeOrganisationDatasource
{
	public $dataSourceName = "PLG_TJDASHBOARDSOURCE_DPE_SCHOOL";

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
			$query->select('count(DISTINCT(a.id)) AS  schoolcount, count(DISTINCT(l.id)) AS  licencecount');

			$query->from($db->qn('#__tjmultiagency_multiagency', 'a'));

			$query->join('INNER', $db->qn('#__tj_clusters', 'tjc') . ' ON (' .
			$db->qn('a.id') . ' = ' . $db->qn('tjc.client_id') . ')');

			$query->join('LEFT', $db->qn('#__tjmultiagency_licences', 'l') . ' ON (' .
			$db->qn('l.multiagency_id') . ' = ' . $db->qn('a.id') . ' )');

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
					$query->where($db->qn('tjc.id') . 'IN ( ' .  implode(',',  $filters['cluster_id'] ) . ')');
				}
				else
				{
					$query->where($db->qn('tjc.id') . '= ' .  (int) $filters['cluster_id']);
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
					if (!empty($cluster->cluster_id) && RBACL::check($user->id, 'com_cluster', 'core.view.own', 'com_tjdashboard', $cluster->cluster_id))
					{
						$clusterIds[] = $cluster->cluster_id;
					}
				}

				$query->where($db->qn('tjc.id') . " IN ('" . implode("','", $clusterIds) . "')");
			}

			// Filter by cluster End

			$db->setQuery($query);

			$schoolData = $db->loadAssoc();
		}
		catch (Exception $e)
		{
			throw new Exception($e->getMessage());
		}

		$recordInfo = array();
		$recordInfo['widgetcolor'] = array(
		Text::sprintf('PLG_TJDASHBOARDSOURCE_DPE_SCHOOL_TOTAL_SCHOOL', Text::_('COM_MULTIAGENCY_ORGANISATIONS')) => '',
		Text::_('PLG_TJDASHBOARDSOURCE_DPE_SCHOOL_TOTAL_SLA') => ''
		);

		$recordInfo['widgetdata'] = array(
			Text::sprintf('PLG_TJDASHBOARDSOURCE_DPE_SCHOOL_TOTAL_SCHOOL', Text::_('COM_MULTIAGENCY_ORGANISATIONS'))
			=> (($schoolData['schoolcount']) ? $schoolData['schoolcount'] : 0),
			Text::_('PLG_TJDASHBOARDSOURCE_DPE_SCHOOL_TOTAL_SLA') => (($schoolData['licencecount']) ? $schoolData['licencecount'] : 0)
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
		$items = [];
		$items['data'] = ['count' => $this->getData()];

		if (Factory::getUser()->authorise('core.manageall', 'com_cluster'))
		{
			$schoolMgmtlink = 'index.php?option=com_dpe&view=schools';
			$dpeUtility     = DPE::utilities();
			$itemId         = $dpeUtility->getItemId($schoolMgmtlink);

			// Get Filters
			$filters = Factory::getApplication()->input->get('filter', '', 'Array');
			$clusterFilter = '';

			// Check school filter applied  or not
			if (!empty($filters['cluster_id']))
			{
				// Add filter in ULR to apply filter on list views
				$clusterFilter = '&filter[cluster_id]=' . (INT) $filters['cluster_id'];
			}
			else
			{
				$clusterFilter = '&filter[cluster_id]=all';
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
		$tags = is_array($tags)? implode('', $tags):'';

			$items['data']['titleLink'] = Route::_($schoolMgmtlink . $clusterFilter . $tags . '&Itemid=' . $itemId, false);
		}

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
