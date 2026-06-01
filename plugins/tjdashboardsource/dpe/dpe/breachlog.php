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

class DpeBreachLogDatasource
{
	public $dataSourceName = "PLG_TJDASHBOARDSOURCE_DPE_BREACHLOG";

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
			$breachStatus = (int) $params->get('breachStatus', '0');
			$reportedToICO = (int) $params->get('reportedToICO', '0');

			$currentDate = new Date('now', 'UTC');
			$dbDateFormat = "DATE_FORMAT(" . $db->quote($currentDate) . ", '%Y-%m-%d %H:%i')";
			$createDateFormat = "DATE_FORMAT(" . $db->qn('a.created_date') . ", '%Y-%m-%d %H:%i')";

			$query = $db->getQuery(true);

			// Select the required fields from the table.
			$query->select("count(a.id) AS ucmcount, SUM(CASE WHEN fcv.value = 'In progress' THEN 1 ELSE 0 END) as progresscount,SUM(CASE WHEN fcv.value = 'Open' THEN 1 ELSE 0 END) as opencount,
				SUM(CASE WHEN fcv.value = 'Closed' THEN 1 ELSE 0 END) as closecount");

			// To get due date in specified hours
			$query->select("SUM(CASE WHEN ( fcv.value = 'In progress' AND " . self::SETHOURS . " >= TIMESTAMPDIFF( HOUR , "
				. $createDateFormat . "," . $dbDateFormat . ") AND 0 <= TIMESTAMPDIFF( HOUR , " . $createDateFormat . ","
				. $dbDateFormat . " )) THEN 1 ELSE 0 END) AS dueDate");

			$query->from($db->quoteName('#__tj_ucm_data', 'a'));

			$query->join('LEFT', $db->qn('#__tjfields_fields_value', 'fcv') . ' ON (' .
				$db->qn('fcv.content_id') . ' = ' . $db->qn('a.id') . ' AND ' . $db->qn('fcv.field_id') . ' = ' . $db->q($breachStatus) . ')');

			// To get overdate
			$query->select("SUM(CASE WHEN (fv.value = 'Yes') THEN 1 ELSE 0 END) AS reportedToICO");

			$query->join('LEFT', $db->qn('#__tjfields_fields_value', 'fv') . ' ON (' .
				$db->qn('fv.content_id') . ' = ' . $db->qn('a.id') . ' AND ' . $db->qn('fv.field_id') . ' = ' . $db->q($reportedToICO) . ')');

			$query->where($db->qn('a.client') . " = 'com_tjucm.breachlog'");

			// Get Filters
			$filters = $input->get('filter', '', 'Array');


			// checked for Dpe admin here.  
			if (!empty($filters))
			{	
				$params     			  = ComponentHelper::getParams('com_multiagency');
				$multiagencyTrusteeRoleId = (int) $params->get('multiagency_trustee_group');
				$orgAdminRoleId           = (int) $params->get('multiagency_school_admin_group', '0', 'INT');
				$orgAdminRole          = in_array($orgAdminRoleId , $user->groups);
				if ($filters['tags'] && !$filters['cluster_id'] && ($user->authorise('core.manageall', 'com_cluster') || in_array($multiagencyTrusteeRoleId, $user->groups) ||$orgAdminRole ))
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

			$clusterUserModel = ClusterFactory::model('ClusterUser', array('ignore_request' => true));
			$clusters = $clusterUserModel->getUsersClusters($user->id);

			foreach ($clusters as $cluster)
			{
				// Check user have permission to manage all clusters organisation admin
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

			// checked for org admin here.  

			if (empty($filters['cluster_id']) && !$user->authorise('core.manageall', 'com_cluster'))
			{
				$clusterIds =implode(', ', $clusterIds);
				$query->where("(" . $db->qn('a.cluster_id') . "IN (" . $clusterIds . "))");
			}

			$query->where($db->qn('a.state') . " = 1 AND " . $db->qn('a.draft') . " = 0 ");

			$db->setQuery($query);

			$breachData = $db->loadAssoc();
		}
		catch (Exception $e)
		{
			throw new Exception($e->getMessage());
		}

		$progresscount = ($breachData['progresscount']) ? $breachData['progresscount'] : 0;
		$closecount    = ($breachData['closecount']) ? $breachData['closecount'] : 0;
		$reportedToICO = ($breachData['reportedToICO']) ? $breachData['reportedToICO'] : 0;
		$dueDate       = ($breachData['dueDate']) ? $breachData['dueDate'] : 0;
		$ucmCount      = ($breachData['ucmcount']) ? $breachData['ucmcount'] : 0;
		$opencount = ($breachData['opencount']) ? $breachData['opencount'] : 0;


		$recordInfo = array();
		$recordInfo['widgetcolor'] = array(
			Text::_('PLG_TJDASHBOARDSOURCE_DPE_BREACHLOG_OPEN') => (($opencount == 0) ? 'text-success' : 'text-lightblue'),
			Text::sprintf('PLG_TJDASHBOARDSOURCE_DPE_BREACHLOG_OVERDUE', self::SETHOURS) => (($dueDate == 0) ? 'text-success' : 'text-red-500'),
			Text::_('PLG_TJDASHBOARDSOURCE_DPE_BREACHLOG_INPROGRESS') => (($progresscount == 0) ? 'text-success' : 'text-amber'),
			Text::_('PLG_TJDASHBOARDSOURCE_DPE_BREACHLOG_REPORTED_TO_ICO') => (($reportedToICO == 0) ? 'text-success' : 'text-red-500'),
			

		// Text::_('PLG_TJDASHBOARDSOURCE_DPE_BREACHLOG_CLOSED') => 'text-success'
		);

		$recordInfo['widgetdata'] = array(
			Text::_('PLG_TJDASHBOARDSOURCE_DPE_BREACHLOG_OPEN') => $opencount,
			Text::sprintf('PLG_TJDASHBOARDSOURCE_DPE_BREACHLOG_OVERDUE', self::SETHOURS) => $dueDate,
			Text::_('PLG_TJDASHBOARDSOURCE_DPE_BREACHLOG_INPROGRESS') => $progresscount,
			Text::_('PLG_TJDASHBOARDSOURCE_DPE_BREACHLOG_REPORTED_TO_ICO') => $reportedToICO,
			
		// Text::_('PLG_TJDASHBOARDSOURCE_DPE_BREACHLOG_CLOSED') => $closecount
		);

		$recordInfo['total']       = $ucmCount;
		$recordInfo['id']          = "breach_log";
		$recordInfo['closedCount'] = ($closecount) ? $closecount : 0;

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
		$link = 'index.php?option=com_tjucm&view=items&client=com_tjucm.breachlog';
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
			$tags = '&filter.tags[]=';	
		}

		$tags = is_array($tags)? implode('', $tags):'';

		$urls = array(Text::_('PLG_TJDASHBOARDSOURCE_DPE_BREACHLOG_INPROGRESS') => '', Text::_('PLG_TJDASHBOARDSOURCE_DPE_BREACHLOG_CLOSED') => '');

		$urls[Text::_('PLG_TJDASHBOARDSOURCE_DPE_BREACHLOG_INPROGRESS')] = Route::_(
			$link . $clusterFilter . '&com_tjucm_breachlog_breachstatus=In progress&Itemid=' . $itemId, false
		);

		$urls[Text::_('PLG_TJDASHBOARDSOURCE_DPE_BREACHLOG_CLOSED')] = Route::_(
			$link . $clusterFilter . '&com_tjucm_breachlog_breachstatus=Closed&Itemid=' . $itemId, false
		);

		$items = [];
		$items['data'] = ['count' => $this->getData()
		,'titleLink'  => Route::_(
			$link . $clusterFilter . $tags . '&Itemid=' . $itemId, false
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
