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
use Joomla\CMS\Date\Date;
use Joomla\CMS\Component\ComponentHelper;

$lang      = Factory::getLanguage();
$lang->load('plg_tjdashboardsource_dpe', JPATH_ADMINISTRATOR);
JLoader::import("/components/com_subusers/includes/rbacl", JPATH_ADMINISTRATOR);

/**
 * DPE plugin for tjdashboardsource to get document or front end lesson details
 *
 * @since  __DEPLOY_VERSION__
 */

class DpeComplianceManagerDatasource
{
	public $dataSourceName = "PLG_TJDASHBOARDSOURCE_DPE_COMPLIANCE_MANAGER";

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

			$currentDate = new Date('now', 'UTC');
			$dbDateFormat = "DATE_FORMAT(" . $db->quote($currentDate) . ", '%Y-%m-%d')";
			$dueDateFormat = "DATE_FORMAT(" . $db->qn('jtodo.due_date') . ", '%Y-%m-%d')";

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

					$filters['cluster_id'] = $dashBoardModel->getClusterIdsByTags($filters['tags']);
				}
			}
			
			// Create a new query object.
			$query = $db->getQuery(true);

			// Select the required fields from the table.
			$query->select('(CASE WHEN ( a.state = 1) THEN 1 ELSE 0 END) AS active');

			$query->from($db->qn('#__tjlms_lessons', 'a'));

			if (!$user->authorise('core.manageall', 'com_cluster') || (!empty($filters['cluster_id'])))
			{
				$query->join('INNER', $db->qn('#__tjlms_lesson_cluster_xref', 'tjc') . 'ON(' . $db->qn('tjc.lesson_id') . '=' . $db->qn('a.id') . ')');
				$query->join('INNER', $db->qn('#__tj_clusters', 'cl') . 'ON(' . $db->qn('tjc.cluster_id') . '=' . $db->qn('cl.id') . ')');
				$query->where($db->quoteName('cl.state') . ' = 1');
			}

			$query->join('INNER', $db->qn('#__tjlms_media', 'tm') . 'ON(' . $db->qn('a.media_id') . '=' . $db->qn('tm.id') . ')');
			$query->where($db->qn('a.in_lib') . ' = 1');

			// To get due date count by using sub query applied on jlike todos
			$subqueryQuery = $db->getQuery(true);
			$subqueryQuery->select("count(DISTINCT(jc.element_id))");
			$subqueryQuery->from($db->qn('#__jlike_content', 'jc'));
			$subqueryQuery->join('LEFT', $db->qn('#__jlike_todos', 'jtodo') . ' ON (' . $db->qn('jtodo.content_id') . ' = ' .
			$db->qn('jc.id') . ')');
			$subqueryQuery->where("year(jtodo.due_date) != 0 AND " . $dbDateFormat . " > " . $dueDateFormat);
			$subqueryQuery->where($db->qn('jtodo.status') . " = 'I'");
			$subqueryQuery->where($db->qn('jc.element') . " = 'com_tjlms.lesson'");
			$subqueryQuery->where($db->qn('jc.element_id') . ' = ' . $db->qn('a.id'));

			$query->select(' ( ' . $subqueryQuery . ' ) AS documentoverdue ');

			// To get due date count by using sub query applied on jlike todos
			$subqueryQuery1 = $db->getQuery(true);
			$subqueryQuery1->select("count(DISTINCT(jc.element_id))");
			$subqueryQuery1->from($db->qn('#__jlike_content', 'jc'));
			$subqueryQuery1->join('LEFT', $db->qn('#__jlike_todos', 'jtodo') . ' ON (' . $db->qn('jtodo.content_id') . ' = ' .
			$db->qn('jc.id') . ')');
			$subqueryQuery1->where($db->qn('jc.element') . " = 'com_tjlms.lesson'");
			$subqueryQuery1->where($db->qn('jc.element_id') . ' = ' . $db->qn('a.id'));

			$query->select(' ( ' . $subqueryQuery1 . ' ) AS documentAssigned ');

			// To get due date count by using sub query applied on jlike todos
			$subqueryQuery2 = $db->getQuery(true);
			$subqueryQuery2->select("count(DISTINCT(te.read))");
			$subqueryQuery2->from($db->qn('#__jlike_content', 'jc'));
			$subqueryQuery2->join('LEFT', $db->qn('#__jlike_todos', 'jtodo') . ' ON (' . $db->qn('jtodo.content_id') . ' = ' .
			$db->qn('jc.id') . ')');
			$subqueryQuery2->where($db->qn('jc.element') . " = 'com_tjlms.lesson'");
			$subqueryQuery2->where($db->qn('jc.element_id') . ' = ' . $db->qn('a.id'));
			$subqueryQuery2->join('LEFT', $db->qn('#__jlike_todos_extended', 'te') .
			'ON(' . $db->qn('jtodo.id') . '=' . $db->qn('te.todo_id') . ') AND te.read =1');

			// $subqueryQuery2->where($db->qn('te.read') . " = ". (int) 1 );

			$query->select(' ( ' . $subqueryQuery2 . ' ) AS readUnderstood ');
			
			
			if (!empty($filters['cluster_id']))
			{
				if (is_array($filters['cluster_id']))
				{
					$query->where($db->qn('tjc.cluster_id') . 'IN ( ' . implode(',', $filters['cluster_id']) . ')');
				}
				else
				{
					$query->where($db->qn('tjc.cluster_id') . '= ' . (INT) $filters['cluster_id']);
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

				// Following condition is needed in all cases if clusters are there are not

				if (empty($filters['cluster_id']))
				{
					// $query->where($db->qn('tjc.cluster_id') . " = " . $clusterIds[0]);
					$clusterIds =implode(', ', $clusterIds);
					$query->where("(" . $db->qn('tjc.cluster_id') . "IN (" . $clusterIds . "))");
				}
			}

			$mainQuery = $db->getQuery(true);
			$mainQuery->select('SUM(active) AS activeDocument , SUM(documentoverdue) AS documentOverDue,
				SUM(readUnderstood) AS readUnderstood, SUM(documentAssigned) AS documentAssigned');
			$mainQuery->from('( ' . $query . ' ) AS complianceManager');

			$db->setQuery($mainQuery);
			$complianceManager = $db->loadAssoc();
		}
		catch (Exception $e)
		{
			throw new Exception($e->getMessage());
		}

		$readUnderstood   = ($complianceManager['readUnderstood']) ? $complianceManager['readUnderstood'] : 0;
		$activeDocument   = ($complianceManager['activeDocument']) ? $complianceManager['activeDocument'] : 0;
		$documentOverDue  = ($complianceManager['documentOverDue']) ? $complianceManager['documentOverDue'] : 0;
		$documentAssigned = ($complianceManager['documentAssigned']) ? $complianceManager['documentAssigned'] : 0;

		$recordInfo = array();
		$recordInfo['widgetcolor'] = array(
		Text::_('PLG_TJDASHBOARDSOURCE_DPE_COMPLIANCE_MANAGER_DOCUMENT_RNU') => (($readUnderstood == 0) ? 'text-success' : 'text-red-500'),
		Text::_('PLG_TJDASHBOARDSOURCE_DPE_COMPLIANCE_MANAGER_DOCUMENT_ASSIGNED') => (($documentAssigned == 0) ? 'text-success' : 'text-red-500'),
		// Text::_('PLG_TJDASHBOARDSOURCE_DPE_COMPLIANCE_MANAGER_ACTIVE_DOC') => (($activeDocument == 0) ? 'text-success' : 'text-amber'),
		Text::_('PLG_TJDASHBOARDSOURCE_DPE_COMPLIANCE_MANAGER_DOCUMENT_OVERDUE') => (($documentOverDue == 0) ? 'text-success' : 'text-red-500')
		);

		$recordInfo['widgetdata'] = array(
		Text::_('PLG_TJDASHBOARDSOURCE_DPE_COMPLIANCE_MANAGER_DOCUMENT_RNU') => $readUnderstood,
		Text::_('PLG_TJDASHBOARDSOURCE_DPE_COMPLIANCE_MANAGER_DOCUMENT_ASSIGNED') => $documentAssigned,
		// Text::_('PLG_TJDASHBOARDSOURCE_DPE_COMPLIANCE_MANAGER_ACTIVE_DOC') => $activeDocument,
		Text::_('PLG_TJDASHBOARDSOURCE_DPE_COMPLIANCE_MANAGER_DOCUMENT_OVERDUE') => $documentOverDue
		);

		$lessonCount = ($complianceManager['lessoncount']) ? $complianceManager['lessoncount'] : 0;

		$recordInfo['id'] = "compliance_manager";

		// Active document count is a count of total published document
		$recordInfo['total'] = $activeDocument;

		return $recordInfo;
	}

	/**
	 * Get Data for Countlink box
	 *
	 * @return string dataArray
	 *
	 * @since   __DEPLOY_VERSION__
	 */
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
	 */
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
	 */
	public function getFormattedData()
	{
		$link = 'index.php?option=com_tjlms&view=managelessons';
		JLoader::import("/components/com_tjlms/helpers/main", JPATH_SITE);
		$tjlmsHelper = new ComtjlmsHelper;
		$itemId      = $tjlmsHelper->getItemId($link);
		$user        = Factory::getUser();

		// Get Filters
		$filters = Factory::getApplication()->input->get('filter', '', 'Array');
		$clusterFilter = '';

		// Check school filter applied  or not
		if (!empty($filters['cluster_id']))
		{
			// Add filter in ULR to redirect with filter
			$clusterFilter = '&clusters=' . (INT) $filters['cluster_id'];
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
			if (count(isset($clusterIds)?$clusterIds : 0) > 1)
			{
				$clusterFilter = '&clusters=' . $clusterIds[0];
			}
		}

		if (!empty($filters['tags']))
		{
			// Add filter in ULR to apply filter on list views
			foreach ($filters['tags'] as $key => $tag)
			{
				$tags[$key] = '&filter.tags[]=' .  $tag;
			}
			$clusterFilter = '&clusters=';
		}
		else
		{
			$tags[0] = '&filter.tags[]=';	
		}

		$items = [];
		$items['data'] = ['count' => $this->getData()
		,'titleLink'  => Route::_(
			$link . $clusterFilter . implode('', $tags) . '&Itemid=' . $itemId, false
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
