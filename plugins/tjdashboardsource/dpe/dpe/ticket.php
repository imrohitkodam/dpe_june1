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
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Component\ComponentHelper;

$lang      = Factory::getLanguage();
$lang->load('plg_tjdashboardsource_dpe', JPATH_ADMINISTRATOR);

JLoader::import("/components/com_dpe/includes/dpe", JPATH_SITE);
JLoader::import("/components/com_cluster/includes/cluster", JPATH_ADMINISTRATOR);

/**
 * DPE plugin for tjdashboardsource to get Ticket information
 *
 * @since  __DEPLOY_VERSION__
 */

class DpeTicketDatasource
{
	public $dataSourceName = "PLG_TJDASHBOARDSOURCE_DPE_TICKET";

	/**
	 * Variable to add all status of ticket
	 *
	 * @var		array
	 * @since	__DEPLOY_VERSION__
	 */
	protected $statusIds = array();

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
			$query = $db->getQuery(true);

			$app = Factory::getApplication();
			$input = $app->input;
			$user  = Factory::getUser();

			// Get all statuses of RSticket
			FormHelper::addFieldPath(JPATH_SITE . '/components/com_dpe/models/fields/');
			$ticketstatus = FormHelper::loadFieldType('rsticketstatus', false);
			$rsticketstatus = $ticketstatus->getOptionsExternally();

			// Create select option for query
			$selectQuery = 'count(DISTINCT(a.id)) AS  ticketcount ';

			// Get Filters
			$filters = $input->get('filter', '', 'Array');

			// Filter for tags and checked for Dpe admin here
		if (!empty($filters))
		{	
			    $params     			  = ComponentHelper::getParams('com_multiagency');
				$multiagencyTrusteeRoleId = (int) $params->get('multiagency_trustee_group');
				$trustee = in_array($multiagencyTrusteeRoleId, $user->groups);
				$orgAdminRoleId           = (int) $params->get('multiagency_school_admin_group', '0', 'INT');
				$orgAdminRole           = in_array($orgAdminRoleId , $user->groups);

			if (($filters['tags'] && !$filters['cluster_id']) && ($user->authorise('core.manageall', 'com_cluster') || $trustee || $orgAdminRole))
			{	
				BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_dpe/models', 'DpeModel');
				$dashBoardModel        = BaseDatabaseModel::getInstance('Dashboard', 'DpeModel');
				$agencyTags     	   =  $filters['tags'];
				$filters['cluster_id'] = $dashBoardModel->getClusterIdsByTags($agencyTags);
				
			}
		}

			if (!empty($rsticketstatus))
			{
				$dateFormat = '%Y-%m-%d';
				$col = "";

				foreach ($rsticketstatus as $status)
				{
					if ($status->value == 1 || $status->value == 3)
					{
						$col = 'a.date';
					}
					elseif ($status->value == 2)
					{
						$col = 'a.closed';
					}

					if (!empty($status->value))
					{
						switch (!empty($filters) && $filters['operator'])
						{
							case "=":
								$extraQuery = " AND DATE_FORMAT(" . $col . ", '$dateFormat')" . ' = ' . $db->q($filters['date_start']);
								break;
							case "between":
								$extraQuery = " AND DATE_FORMAT(" . $col . ", '$dateFormat')" . ' BETWEEN '
								. $db->q($filters['date_start']) . " AND " . $db->q($filters['date_end']);
								break;
							case "gt":
								$extraQuery = " AND DATE_FORMAT(" . $col . ", '$dateFormat')" . ' >= ' . $db->q($filters['date_start']);
								break;
							case "lt":
								$extraQuery = " AND DATE_FORMAT(" . $col . ", '$dateFormat')" . ' <= ' . $db->q($filters['date_start']);
								break;
							case "w":
								$extraQuery = " AND YEARWEEK(" . $col . ") = YEARWEEK(CURRENT_DATE())";
								break;
							case "y":
								$extraQuery = " AND YEAR(" . $col . ") = YEAR(CURRENT_DATE())";
								break;
							case "m":
								$extraQuery = " AND MONTH(" . $col . ") = MONTH(CURRENT_DATE()) AND YEAR(" . $col . ") = EXTRACT(YEAR FROM (CURRENT_DATE()))";
								break;
							default:
								$extraQuery = "";
						}

						// Don't get closed count
						$selectQuery .= " , SUM(CASE WHEN a.status_id = '" . $status->value . "'" . $extraQuery . " THEN 1 ELSE 0 END) as " . " count"
						. $status->value;
						$this->statusIds[$status->value] = $status->text;
					}
				}
			}

			// Select the required fields from the table.
			$query->select($selectQuery);
			$query->from($db->qn('#__rsticketspro_tickets', 'a'));
			$query->join('LEFT', $db->qn('#__rsticket_integration_xref', 'rsxref') . ' ON (' .
			$db->qn('rsxref.ticket_id') . ' = ' . $db->qn('a.id') . ')');
			$query->join('LEFT', $db->qn('#__tj_clusters', 'tjc') . ' ON (' .
			$db->qn('tjc.id') . ' = ' . $db->qn('rsxref.agency_id') . ')');

			if (!$user->authorise('core.manageall', 'com_cluster') )
			{
				$clusterUserModel = ClusterFactory::model('ClusterUser', array('ignore_request' => true));
				$clusters = $clusterUserModel->getUsersClusters($user->id);

				foreach ($clusters as $cluster)
				{
					if (!empty($cluster->cluster_id))
					{
						if (RBACL::check($user->id, 'com_cluster', 'core.view.all', 'com_multiagency', $cluster->cluster_id))
						{
							$staffAgency[] = $cluster->cluster_id;
						}
						else
						{
							$allocatedAgency[] = $cluster->cluster_id;
						}
					}
				}
				
				if (!empty($filters['tags']))
				{
					$filters['cluster_id'] = array_intersect($allocatedAgency,$filters['cluster_id']);
					$allocatedAgency = $filters['cluster_id'];
					$filters['cluster_id']='';
				}

				// Allocated Agencies as a manager & school admin
				$agencyCondition = '';
				


				if (!empty($allocatedAgency) && empty($filters['cluster_id']) && !$user->authorise('core.manageall', 'com_cluster'))
				{
					$agencyCondition = 'rsxref.agency_id  IN (' . implode(',' , (array) $allocatedAgency) . ')' ;
				}
				elseif (!empty($allocatedAgency) && ($filters['cluster_id']) && !$user->authorise('core.manageall', 'com_cluster'))
				{
					$agencyCondition = 'rsxref.agency_id  IN (' . implode(',' , (array) $filters['cluster_id']) . ')' ;   
					//If multiple organisation assigned then it will get the cluster id and show the data.
				}

				// Allocated Agencies as a staff
				$staffAgencyCondition = '';

				if (!empty($staffAgency) )
				{
					$staffAgencyCondition = 'rsxref.agency_id  IN ( ' . implode(',', $staffAgency) . ')';
				}

				if (!empty($staffAgencyCondition) && !empty($agencyCondition))
				{
					$query->where('(' . $staffAgencyCondition . ' AND ' . $db->qn('a.customer_id') . '=' . $db->q($user->id) . ')');

					$query->orWhere('(' . $agencyCondition
					. ' OR ' . $db->qn('rsxref.emails') . ' LIKE ' . $db->q('%' . $user->email . '%')
					. ')');
				}
				elseif (empty($staffAgencyCondition) && !empty($agencyCondition))
				{
					$query->Where('(' . $agencyCondition
					. ' OR ' . $db->qn('rsxref.emails') . ' LIKE ' . $db->q('%' . $user->email . '%')
					. ')');
				}
				elseif (empty($agencyCondition) && !empty($staffAgencyCondition))
				{
					$query->Where('(' . $staffAgencyCondition . ' AND ' . $db->qn('a.customer_id') . '=' . $db->q($user->id) . ')');
				}
				else
				{	
					if (!$trustee )
					{
						$query->Where($db->qn('rsxref.emails') . ' LIKE ' . $db->q('%' . $user->email . '%'));
					}
					
				}
			}
			else
			{
				if (!empty($filters['cluster_id']))
				{			
					if (is_array($filters['cluster_id']))
					{
						$query->where($db->qn('tjc.id') . ' IN (' .  implode(',', $filters['cluster_id']) . ')');
					}
					else
					{
						$query->where($db->qn('tjc.id') . ' = ' .  (INT) $filters['cluster_id'] );
					}
					
				}
			}
		
			// Filter by cluster End

			$db->setQuery($query);
			
			$ticketData = $db->loadAssoc();
		}
		catch (Exception $e)
		{
			throw new Exception($e->getMessage());
		}

		$recordInfo = array('widgetcolor' => array(), 'widgetdata' => array());

		if (!empty($this->statusIds))
		{
			foreach ($this->statusIds as $key => $status)
			{
				if (strtolower($status) != 'closed')
				{
					$recordInfo['widgetdata'][$status] = (($ticketData["count" . $key]) ? $ticketData["count" . $key] : 0);
				}

				if ($recordInfo['widgetdata'][$status] == 0)
				{
					$recordInfo['widgetcolor'][$status] = 'text-success';
				}
				else
				{
					$recordInfo['widgetcolor'][$status] = 'text-amber';

					if (strtolower($status) == 'closed')
					{
						$recordInfo['widgetcolor'][$status] = 'text-success';
					}
					elseif (strtolower($status) == 'open')
					{
						$recordInfo['widgetcolor'][$status] = 'text-red-500';
					}
				}
			}
		}

		$ticketCount = ($ticketData['ticketcount']) ? $ticketData['ticketcount'] : 0;

		$recordInfo['id'] = "ticket";
		$recordInfo['total'] = $ticketCount;
		$recordInfo['closedCount'] = (($ticketData["count2"]) ? $ticketData["count2"] : 0);

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
	 * */
	public function getFormattedData()
	{
		$link   = 'index.php?option=com_dpe&view=rsticketspro';
		$dpeUtility = DPE::utilities();
		$itemId = $dpeUtility->getItemId($link);

		// Get Filters
		$filters = Factory::getApplication()->input->get('filter', '', 'Array');
		$clusterFilter = '';

		// Check school filter applied  or not
		if (!empty($filters['cluster_id']))
		{
			// Add filter in ULR to apply filter on list views
			$clusterFilter = '&filter[agencies]=' . (INT) $filters['cluster_id'];
		}
		elseif(Factory::getUser()->authorise('core.manageall', 'com_cluster') && empty($filters['cluster_id']))
		{
			$clusterFilter = '&filter[agencies]=all';
		}
		else
		{
			$clusterUserModel = ClusterFactory::model('ClusterUser', array('ignore_request' => true));
			$clusters = $clusterUserModel->getUsersClusters(Factory::getUser()->id);

			if ((count(isset($clusters)?$clusters:0) > 1))
			{
				$clusterFilter = '&filter[agencies]=all';
			}else{
				$clusterFilter = '&filter[agencies]='.$clusters[0]->cluster_id;
			}
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
			$tags[0] = '&filter.tags[]=';	
		}
		
		$tags = is_array($tags)? implode('', $tags):'';

		$items = array();
		$items['data']['count'] = $this->getData();
		$items['data']['titleLink'] = Route::_($link . $clusterFilter . $tags . '&Itemid=' . $itemId, false);
/*
		if (!empty($this->statusIds))
		{
			foreach ($this->statusIds as $key => $status)
			{
				$items['data']['link'][$status] = Route::_($link . $clusterFilter . '&filter[ticketStatus]=' . $key . '&Itemid=' . $itemId, false);
			}
		}
*/
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

