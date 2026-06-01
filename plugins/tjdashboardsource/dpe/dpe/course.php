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
JLoader::import("/components/com_subusers/includes/rbacl", JPATH_ADMINISTRATOR);
JLoader::import("/components/com_cluster/includes/cluster", JPATH_ADMINISTRATOR);

/**
 * DPE plugin for tjdashboardsource to get Course details
 *
 * @since  __DEPLOY_VERSION__
 */

class DpeCourseDatasource
{
	public $dataSourceName = "PLG_TJDASHBOARDSOURCE_DPE_COURSE";

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
			$query->select("
			SUM(CASE WHEN (eu.user_id != '' AND (cst.status= '' OR cst.status IS NULL OR cst.status= 'I') ) THEN 1 ELSE 0 END) as incompleteEnrollment
			, SUM(CASE WHEN ( eu.user_id ='' OR eu.user_id IS NULL ) THEN 1 ELSE 0 END) as unenrolled
			, SUM(CASE WHEN cst.status = 'C' THEN 1 ELSE 0 END) as courseCompletion, count(DISTINCT(u.id)) as totalUsers");

			$query->from('#__users AS u');

			$query->join('LEFT', $db->qn('#__tjlms_enrolled_users', 'eu') . ' ON (' .
				$db->qn('u.id') . ' = ' . $db->qn('eu.user_id') . ')');

			$query->join('LEFT', $db->qn('#__tjlms_courses', 'c') . ' ON (' . $db->qn('c.id') . ' = ' .
				$db->qn('eu.course_id') . ')');

			/*
			$query->join('LEFT', $db->qn('#__jlike_content', 'jc') . ' ON (' . $db->qn('jc.element_id') . ' = ' .
			$db->qn('eu.course_id') . ' AND ' . $db->qn('jc.element') . " = 'com_tjlms.course'" . ')');

			$query->join('LEFT', $db->qn('#__jlike_todos', 'jtodo') . ' ON (' . $db->qn('jtodo.content_id') . ' = ' .
			$db->qn('jc.id') . ' AND ' . $db->qn('jtodo.assigned_to') . ' = ' .
				$db->qn('u.id') . ')');
			*/

			$query->join('LEFT', $db->qn('#__tjlms_course_track', 'cst') . ' ON (' . $db->qn('cst.course_id') . ' = ' .
			$db->qn('eu.course_id') . ' AND ' . $db->qn('cst.user_id') . ' = ' . $db->qn('eu.user_id') . ')');

			// Get Filters
			$filters = $input->get('filter', '', 'Array');
			// Filter for tags and checked for Dpe admin here. 
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

			// its checking for both org admin and cluster id , either or condition is true it will  add the query. 
			if (!$user->authorise('core.manageall', 'com_cluster') || !empty($filters['cluster_id']))
			{
				$query->join('LEFT', $db->qn('#__tj_cluster_nodes', 'tjcn') . ' ON (' .
					$db->qn('u.id') . ' = ' . $db->qn('tjcn.user_id') . ')');
				$query->join('LEFT', $db->qn('#__tj_clusters', 'tjc') . ' ON (' .
					$db->qn('tjcn.cluster_id') . ' = ' . $db->qn('tjc.id') . ')');

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
			}

			$query->where($db->qn('u.block') . ' = 0');

			// To get due date count by using sub query applied on jlike todos
			$subqueryQuery = $db->getQuery(true);
			$subqueryQuery->select("count(DISTINCT(cs.id))");
			$subqueryQuery->from($db->qn('#__tjlms_courses', 'cs'));
			$subqueryQuery->where($db->qn('cs.state') . " = 1");

			$query->select(' ( ' . $subqueryQuery . ' ) AS courseCount ');

			// checked for org admin here.  
			if (!$user->authorise('core.manageall', 'com_cluster'))
			{
				JLoader::import("/components/com_cluster/includes/cluster", JPATH_ADMINISTRATOR);
				$clusterUserModel = ClusterFactory::model('ClusterUser', array('ignore_request' => true));
				$clusters = $clusterUserModel->getUsersClusters($user->id);

				foreach ($clusters as $cluster)
				{
					// Check user having permission to add staff
					if (RBACL::check($user->id, 'com_cluster', 'core.view.own', 'com_tjdashboard', $cluster->cluster_id))
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

			$courseData = $db->loadAssoc();
		}
		catch (Exception $e)
		{
			throw new Exception($e->getMessage());
		}

		$courseCount = ($courseData['courseCount']) ? $courseData['courseCount'] : 0;
		$incompleteEnrollment = ($courseData['incompleteEnrollment']) ? $courseData['incompleteEnrollment'] : 0;
		$unenrolled = ($courseData['unenrolled']) ? $courseData['unenrolled'] : 0;
		$courseCompletion = ($courseData['courseCompletion']) ? $courseData['courseCompletion'] : 0;

		$recordInfo = array();
		$recordInfo['widgetcolor'] = array(
		Text::_('PLG_TJDASHBOARDSOURCE_DPE_COURSE_COMPLETED_USERS') => 'text-success',
		Text::_('PLG_TJDASHBOARDSOURCE_DPE_COURSE_INCOMPLETE_ENROLLMENT') => (($incompleteEnrollment == 0) ? 'text-success' : 'text-amber'),
		Text::_('PLG_TJDASHBOARDSOURCE_DPE_COURSE_UNENROLLED') => (($unenrolled == 0) ? 'text-success' : 'text-red-500')
		);

		$recordInfo['widgetdata'] = array(
		//~ Text::_('PLG_TJDASHBOARDSOURCE_DPE_COURSE_TOTAL') => $courseCount,
		Text::_('PLG_TJDASHBOARDSOURCE_DPE_COURSE_COMPLETED_USERS') => $courseCompletion,
		Text::_('PLG_TJDASHBOARDSOURCE_DPE_COURSE_INCOMPLETE_ENROLLMENT') => $incompleteEnrollment,
		Text::_('PLG_TJDASHBOARDSOURCE_DPE_COURSE_UNENROLLED') => $unenrolled
		);

		$recordInfo['totalCourses'] = $courseCount;
		$recordInfo['id']           = "course";

		// Its not logical, its total of all the counts shown on dashbaord
		$recordInfo['total']        = $courseData['totalUsers'];

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
	 * Get Data for Tabulator Table
	 *
	 * @return string dataArray
	 *
	 * @since   __DEPLOY_VERSION__
	 * */
	public function getFormattedData()
	{
		/*
		$link = 'index.php?option=com_tjlms&view=courses';
		JLoader::import("/components/com_tjlms/helpers/main", JPATH_SITE);
		$tjlmsHelper = new ComtjlmsHelper;
		$itemId = $tjlmsHelper->getItemId($link);
		*/

		$items = [];
		$items['data'] = ['count' => $this->getData()
		,'titleLink'  => Route::_($this->getRedirectUrl(), false)
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

	/**
	 * Get redirect URL
	 *
	 * @return string redirection URL
	 *
	 * @since   __DEPLOY_VERSION__
	 *
	 */
	public function getRedirectUrl()
	{
		$user  = Factory::getUser();

		// Get Filters
		$filters = Factory::getApplication()->input->get('filter', '', 'Array');
		$clusterFilter = '';

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
			if (count($clusterIds) > 1)
			{
				$clusterId = $clusterIds[0];
			}
		}

		if ($clusterId)
		{
			$cluster = ClusterCluster::getInstance($clusterId);

			// Add filters in ULR to apply filter on list views
			$clusterFilter = '&filters[cluster_id]=' . $clusterId . '&filters[agency]=' . $cluster->client_id;
		}

		if (!empty($filters['tags']))
		{
			// Add filter in ULR to apply filter on list views
			foreach($filters['tags'] as $key => $tag)
			{
				$tags[$key] = '&filters[tags][]=' .  $tag;
			}
			$clusterFilter = '&filters[cluster_id]=&filters[agency]=';
			
		}
		$tags = is_array($tags)? implode('', $tags):'';

		$courseEnrollementReportId = ComponentHelper::getParams('com_dpe')->get('courseEnrollReportId');

		$link = 'index.php?option=com_tjreports&view=reports';
		$dpeUtility = DPE::utilities();
		$link .= $clusterFilter . $tags . '&reportId=' . $courseEnrollementReportId . '&Itemid=' . $dpeUtility->getItemId($link);

		return $link;
	}
}
