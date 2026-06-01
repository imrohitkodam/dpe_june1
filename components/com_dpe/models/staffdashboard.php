<?php
/**
 * @package     DPE
 * @subpackage  com_dpe
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2021 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Form\Form;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Date\Date;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Form\FormHelper;
use Joomla\Registry\Registry;

JLoader::import("/components/com_dpe/includes/dpe", JPATH_SITE);
JLoader::import("/components/com_cluster/includes/cluster", JPATH_ADMINISTRATOR);

/**
 * Staff dashboard
 *
 * @since  __DEPLOY_VERSION__
 */
class DpeModelStaffDashboard extends AdminModel
{
	// To set hours due days
	const SETHOURS = 72;

	const SETDAYS = 7;

	/**
	 * Function to get ticket data
	 *
	 * @return Array data.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function getTicketData()
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

					if (!empty($status->value) && !empty($filters['operator']))// php8 test change
					{
						switch ($filters['operator'])
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

			if (!$user->authorise('core.manageall', 'com_cluster'))
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

				// Allocated Agencies as a manager & school admin
				$agencyCondition = '';

				if (!empty($allocatedAgency) && empty($filters['cluster_id']) && !$user->authorise('core.manageall', 'com_cluster'))
				{
					$agencyCondition = 'rsxref.agency_id  = ' . $allocatedAgency[0];
				}

				// Allocated Agencies as a staff
				$staffAgencyCondition = '';

				if (!empty($staffAgency))
				{
					$staffAgencyCondition = 'rsxref.agency_id  IN ( ' . implode(',', $staffAgency) . ')';
				}

				// This code will fetch the tickets of staff user(added by user and cc user)
				if (!empty($staffAgencyCondition))
				{
					$query->where('((' . $staffAgencyCondition . ' AND ' . $db->qn('a.customer_id') . '='
					. $db->q($user->id) . ') OR (' . $db->qn('rsxref.emails') . ' LIKE ' . $db->q('%' . $user->email . '%') . '))');
				}

				// Following code is needed when we provide this page for org admin

				/*
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
					$query->Where($db->qn('rsxref.emails') . ' LIKE ' . $db->q('%' . $user->email . '%'));
				}
				*/

				if (!empty($filters['cluster_id']))
				{
					$query->andWhere($db->qn('tjc.id') . ' = ' . (INT) $filters['cluster_id']);
				}
			}
			else
			{
				if (!empty($filters['cluster_id']))
				{
					$query->where($db->qn('tjc.id') . ' = ' . (INT) $filters['cluster_id']);
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

		$recordInfo = array('widgetdata' => array());

		if (!empty($this->statusIds))
		{
			foreach ($this->statusIds as $key => $status)
			{
				if (strtolower($status) != 'closed')
				{
					$recordInfo['widgetdata'][$status] = (($ticketData["count" . $key]) ? $ticketData["count" . $key] : 0);
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
	 * Get formatted data for layout
	 *
	 * @return string dataArray
	 *
	 * @since   __DEPLOY_VERSION__
	 * */
	public function getTicketFormattedData()
	{
		$link       = 'index.php?option=com_dpe&view=rsticketspro';
		$dpeUtility = DPE::utilities();
		$itemId     = $dpeUtility->getItemId($link);

		// Get Filters
		$filters = Factory::getApplication()->input->get('filter', '', 'Array');
		$clusterFilter = '';

		// Check school filter applied  or not
		if (!empty($filters['cluster_id']))
		{
			// Add filter in ULR to apply filter on list views
			$clusterFilter = '&filter[agencies]=' . (INT) $filters['cluster_id'];
		}
		else
		{
			$clusterFilter = '&filter[agencies]=all';
		}

		$items = array();
		$items['data']['count'] = $this->getTicketData();
		$items['data']['titleLink'] = Route::_($link . $clusterFilter . '&Itemid=' . $itemId, false);

		return $items;
	}

	/**
	 * Function to get data of compliance maneger
	 *
	 * @return Array data.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function getComplianceData()
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

			// Create a new query object.
			$query = $db->getQuery(true);

			// Select the required fields from the table.
			$query->select('(CASE WHEN ( a.state = 1) THEN 1 ELSE 0 END) AS active');

			$query->from($db->qn('#__tjlms_lessons', 'a'));

			if (!$user->authorise('core.manageall', 'com_cluster') || !empty($filters['cluster_id']))
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

			$query->select(' ( ' . $subqueryQuery2 . ' ) AS readUnderstood ');

			if (!empty($filters['cluster_id']))
			{
				$query->where($db->qn('tjc.cluster_id') . ' = ' . (INT) $filters['cluster_id']);
			}

			if (!$user->authorise('core.manageall', 'com_cluster'))
			{
				JLoader::import("/components/com_cluster/includes/cluster", JPATH_ADMINISTRATOR);
				$clusterUserModel = ClusterFactory::model('ClusterUser', array('ignore_request' => true));
				$clusters         = $clusterUserModel->getUsersClusters($user->id);
				$orgAdmin         = ComponentHelper::getParams('com_multiagency')->get('school_admin_role_id');
				$clusterIds = array();

				foreach ($clusters as $cluster)
				{
					// Check user have permission to manage all clusters
					if (!$user->authorise('core.manageall', 'com_cluster'))
					{
						$coreRoleId = RBACL::getCoreRoleByUser($user->id, 'com_cluster', $cluster->cluster_id);

						// Check user having permission to view dashboard and is not org admin
						if (RBACL::check($user->id, 'com_cluster', 'view.compliancemanagerDashboard', 'com_multiagency', $cluster->cluster_id)
							&& (!in_array($orgAdmin, $coreRoleId)))
						{
							$clusterIds[] = $cluster->cluster_id;
						}
					}
					else
					{
						$clusterIds[] = $cluster->cluster_id;
					}
				}

				if (empty($clusterIds))
				{
					return false;
				}

				if ($clusterIds && empty($filters['cluster_id']) && !$user->authorise('core.manageall', 'com_cluster'))
				{
					$query->where($db->qn('tjc.cluster_id') . ' IN (' . implode(',', $clusterIds) . ')');
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

		$recordInfo['widgetdata'] = array(
		Text::_('PLG_TJDASHBOARDSOURCE_DPE_COMPLIANCE_MANAGER_DOCUMENT_RNU') => $readUnderstood,
		Text::_('PLG_TJDASHBOARDSOURCE_DPE_COMPLIANCE_MANAGER_DOCUMENT_ASSIGNED') => $documentAssigned,
		// Text::_('PLG_TJDASHBOARDSOURCE_DPE_COMPLIANCE_MANAGER_ACTIVE_DOC') => $activeDocument,
		Text::_('PLG_TJDASHBOARDSOURCE_DPE_COMPLIANCE_MANAGER_DOCUMENT_OVERDUE') => $documentOverDue
		);

		$recordInfo['id'] = "compliance_manager";

		// Active document count is a count of total published document
		$recordInfo['total'] = $activeDocument;

		return $recordInfo;
	}

	/**
	 * Get formatted data for layout
	 *
	 * @return string dataArray
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function getComplianceFormattedData()
	{
		$link = 'index.php?option=com_tjlms&view=managelessons';
		JLoader::import("/components/com_tjlms/helpers/main", JPATH_SITE);
		$tjlmsHelper = new ComtjlmsHelper;
		$itemId      = $tjlmsHelper->getItemId($link);
		$user        = Factory::getUser();
		$clusterIds  = array();

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
				if (RBACL::check($user->id, 'com_cluster', 'view.compliancemanagerDashboard', 'com_multiagency', $cluster->cluster_id))
				{
					$clusterIds[] = $cluster->cluster_id;
				}
			}

			// Set first cluster in the widget url
			if (count($clusterIds) > 1)
			{
				$clusterFilter = '&clusters=' . $clusterIds[0];
			}
		}

		$items = [];
		$items['data'] = ['count' => $this->getComplianceData()
		,'titleLink'  => Route::_(
			$link . $clusterFilter . '&Itemid=' . $itemId, false
			)
		];

		return $items;
	}

	/**
	 * Function to get data of breach log
	 *
	 * @return Array data.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function getBreachLogData()
	{
		try
		{
			// Create a new query object.
			$db               = Factory::getDbo();
			$app              = Factory::getApplication();
			$input            = $app->input;
			$user             = Factory::getUser();
			$clusterIds       = array();
			$params           = DPE::config();
			$breachStatus     = (int) $params->get('breachStatus', '0');
			$reportedToICO    = (int) $params->get('reportedToICO', '0');
			$currentDate      = new Date('now', 'UTC');
			$dbDateFormat     = "DATE_FORMAT(" . $db->quote($currentDate) . ", '%Y-%m-%d %H:%i')";
			$createDateFormat = "DATE_FORMAT(" . $db->qn('a.created_date') . ", '%Y-%m-%d %H:%i')";

			$query = $db->getQuery(true);

			// Select the required fields from the table.
			$query->select("count(a.id) AS ucmcount, SUM(CASE WHEN fcv.value = 'In progress' THEN 1 ELSE 0 END) as progresscount,
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

			if (!empty($filters['cluster_id']))
			{
				$query->where($db->qn('a.cluster_id') . ' = ' . (INT) $filters['cluster_id']);
			}

			$clusterUserModel = ClusterFactory::model('ClusterUser', array('ignore_request' => true));
			$clusters         = $clusterUserModel->getUsersClusters($user->id);
			$orgAdmin         = ComponentHelper::getParams('com_multiagency')->get('school_admin_role_id');

			foreach ($clusters as $cluster)
			{
				// Check user have permission to manage all clusters
				if (!$user->authorise('core.manageall', 'com_cluster'))
				{
					$coreRoleId = RBACL::getCoreRoleByUser($user->id, 'com_cluster', $cluster->cluster_id);

					// Check user having permission to add staff
					if (RBACL::check($user->id, 'com_cluster', 'view.breachlogDashboard', 'com_multiagency', $cluster->cluster_id)
						&& (!in_array($orgAdmin, $coreRoleId)))
					{
						$clusterIds[] = $cluster->cluster_id;
					}
				}
				else
				{
					$clusterIds[] = $cluster->cluster_id;
				}
			}

			if (empty($clusterIds))
			{
				return false;
			}

			$addedBy = '';

			if (!$user->authorise('core.manageall', 'com_cluster'))
			{
				$addedBy = $db->qn('a.created_by') . ' = ' . $user->id . ' AND ';
			}

			if ($clusterIds && empty($filters['cluster_id']) && !$user->authorise('core.manageall', 'com_cluster'))
			{
				$query->where("(" . $db->qn('a.cluster_id') . " IN ( " . implode(',', $clusterIds) . ") )");
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

		$recordInfo = array();

		$recordInfo['widgetdata'] = array(
		Text::sprintf('PLG_TJDASHBOARDSOURCE_DPE_BREACHLOG_OVERDUE', self::SETHOURS) => $dueDate,
		Text::_('PLG_TJDASHBOARDSOURCE_DPE_BREACHLOG_OPEN') => $progresscount,
		Text::_('PLG_TJDASHBOARDSOURCE_DPE_BREACHLOG_REPORTED_TO_ICO') => $reportedToICO
		// Text::_('PLG_TJDASHBOARDSOURCE_DPE_BREACHLOG_CLOSED') => $closecount
		);

		$recordInfo['total']       = $ucmCount;
		$recordInfo['id']          = "breach_log";
		$recordInfo['closedCount'] = ($closecount) ? $closecount : 0;

		return $recordInfo;
	}

	/**
	 * Get formatted data for widget
	 *
	 * @return string dataArray
	 *
	 * @since   __DEPLOY_VERSION__
	 *
	 * */
	public function getBreachLogFormattedData()
	{
		$link = 'index.php?option=com_tjucm&view=items&client=com_tjucm.breachlog';
		JLoader::import("/components/com_tjucm/helpers/tjucm", JPATH_SITE);
		$tjucmHelper = new TjucmHelpersTjucm;
		$itemId      = $tjucmHelper->getItemId($link);
		$user        = Factory::getUser();
		$clusterIds  = array();

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

		$urls = array(Text::_('PLG_TJDASHBOARDSOURCE_DPE_BREACHLOG_OPEN') => '', Text::_('PLG_TJDASHBOARDSOURCE_DPE_BREACHLOG_CLOSED') => '');

		$urls[Text::_('PLG_TJDASHBOARDSOURCE_DPE_BREACHLOG_OPEN')] = Route::_(
		$link . $clusterFilter . '&com_tjucm_breachlog_breachstatus=In progress&Itemid=' . $itemId, false
		);

		$urls[Text::_('PLG_TJDASHBOARDSOURCE_DPE_BREACHLOG_CLOSED')] = Route::_(
		$link . $clusterFilter . '&com_tjucm_breachlog_breachstatus=Closed&Itemid=' . $itemId, false
		);

		$items = [];
		$items['data'] = ['count' => $this->getBreachLogData()
		,'titleLink'  => Route::_(
			$link . $clusterFilter . '&Itemid=' . $itemId, false
			)
		// ,'link'  => $urls
		];

		return $items;
	}

	/**
	 * Function to get data of sar log
	 *
	 * @return Array data.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function getSarLogData()
	{
		try
		{
			$db            = Factory::getDbo();
			$app           = Factory::getApplication();
			$input         = $app->input;
			$user          = Factory::getUser();
			$clusterIds    = array();
			$params        = DPE::config();
			$requestStatus = (int) $params->get('requestStatus', '0');
			$dateToRespond = (int) $params->get('dateToRespond', '0');
			$currentDate   = new Date('now', 'UTC');
			$dbDateFormat  = "DATE_FORMAT(" . $db->quote($currentDate) . ", '%Y-%m-%d')";
			$dueDateFormat = "DATE_FORMAT(" . $db->qn('fv.value') . ", '%Y-%m-%d')";

			// Create a new query object.
			$query = $db->getQuery(true);

			// Select the required fields from the table.
			$query->select("count(a.id) AS ucmcount, SUM(CASE WHEN fcv.value = 'In progress' THEN 1 ELSE 0 END) as progresscount ,
			SUM(CASE WHEN fcv.value = 'Closed' THEN 1 ELSE 0 END) as closecount");

			$query->from($db->quoteName('#__tj_ucm_data', 'a'));

			$query->join('LEFT', $db->qn('#__tjfields_fields_value', 'fcv') . ' ON (' .
			$db->qn('fcv.content_id') . ' = ' . $db->qn('a.id') . ' AND ' . $db->qn('fcv.field_id') . ' = ' . $db->q($requestStatus) . ')');

			// To get due date in specified days
			$query->select("SUM(CASE WHEN ( fcv.value = 'In progress' AND " . self::SETDAYS . " >= DATEDIFF( " . $dueDateFormat . ","
			. $dbDateFormat . ") AND 0 <= DATEDIFF( " . $dueDateFormat . ","
			. $dbDateFormat . " )) THEN 1 ELSE 0 END) AS dueDate");

			// To get overdate
			$query->select("SUM(CASE WHEN ( fcv.value = 'In progress' AND DATEDIFF( " . $dueDateFormat . ","
			. $dbDateFormat . ") < 0 ) THEN 1 ELSE 0 END) AS overDate");

			$query->join('LEFT', $db->qn('#__tjfields_fields_value', 'fv') . ' ON (' .
			$db->qn('fv.content_id') . ' = ' . $db->qn('a.id') . ' AND ' . $db->qn('fv.field_id') . ' = ' . $db->q($dateToRespond) . ')');

			$query->where($db->qn('a.client') . " = 'com_tjucm.sarlog'");

			// Get Filters
			$filters = $input->get('filter', '', 'Array');

			if (!empty($filters['cluster_id']))
			{
				$query->where($db->qn('a.cluster_id') . ' = ' . (INT) $filters['cluster_id']);
			}

			$clusterUserModel = ClusterFactory::model('ClusterUser', array('ignore_request' => true));
			$clusters         = $clusterUserModel->getUsersClusters($user->id);
			$orgAdmin         = ComponentHelper::getParams('com_multiagency')->get('school_admin_role_id');

			foreach ($clusters as $cluster)
			{
				// Check user have permission to manage all clusters
				if (!$user->authorise('core.manageall', 'com_cluster'))
				{
					$coreRoleId = RBACL::getCoreRoleByUser($user->id, 'com_cluster', $cluster->cluster_id);

					// Check user having sar admin role and don't core org admin role to load widgets for staff org only

					if (RBACL::check($user->id, 'com_cluster', 'view.sarlogDashboard', 'com_multiagency', $cluster->cluster_id)
						&& (!in_array($orgAdmin, $coreRoleId)))
					{
						$clusterIds[] = $cluster->cluster_id;
					}
				}
				else
				{
					$clusterIds[] = $cluster->cluster_id;
				}
			}

			if (empty($clusterIds))
			{
				return false;
			}

			$addedBy = '';

			if (!$user->authorise('core.manageall', 'com_cluster'))
			{
				$addedBy = $db->qn('a.created_by') . ' = ' . $user->id . ' AND ';
			}

			if (empty($filters['cluster_id']) && !$user->authorise('core.manageall', 'com_cluster'))
			{
				$query->where("(" . $db->qn('a.cluster_id') . " IN (" . implode(',', $clusterIds) . ") )");
			}

			$query->where($db->qn('a.state') . " = 1 AND " . $db->qn('a.draft') . " = 0 ");

			$db->setQuery($query);

			$sarData = $db->loadAssoc();
		}
		catch (Exception $e)
		{
			throw new Exception($e->getMessage());
		}

		$progresscount = ($sarData['progresscount']) ? $sarData['progresscount'] : 0;
		$closecount    = ($sarData['closecount']) ? $sarData['closecount'] : 0;
		$dueDate       = ($sarData['dueDate']) ? $sarData['dueDate'] : 0;
		$overDate      = ($sarData['overDate']) ? $sarData['overDate'] : 0;
		$ucmCount      = ($sarData['ucmcount']) ? $sarData['ucmcount'] : 0;

		$recordInfo = array();

		$recordInfo['widgetdata'] = array(
		Text::_('PLG_TJDASHBOARDSOURCE_DPE_SARLOG_OPEN') => $progresscount,
		Text::sprintf('PLG_TJDASHBOARDSOURCE_DPE_SARLOG_DUE', self::SETDAYS) => $dueDate,
		Text::_('PLG_TJDASHBOARDSOURCE_DPE_SARLOG_OVERDUE') => $overDate,
		// Text::_('PLG_TJDASHBOARDSOURCE_DPE_SARLOG_CLOSED') => $closecount
		);

		$recordInfo['total']       = $ucmCount;
		$recordInfo['id']          = "sar_log";
		$recordInfo['closedCount'] = ($closecount) ? $closecount : 0;

		return $recordInfo;
	}

	/**
	 * Get formatted data for widget
	 *
	 * @return string dataArray
	 *
	 * @since   __DEPLOY_VERSION__
	 *
	 * */
	public function getSarLogFormattedData()
	{
		$link = 'index.php?option=com_tjucm&view=items&client=com_tjucm.sarlog';
		JLoader::import("/components/com_tjucm/helpers/tjucm", JPATH_SITE);
		$tjucmHelper = new TjucmHelpersTjucm;
		$itemId      = $tjucmHelper->getItemId($link);
		$user        = Factory::getUser();
		$clusterIds  = array();

		// Get Filters
		$filters = Factory::getApplication()->input->get('filter', '', 'Array');
		$clusterFilter = '';

		// Check school filter applied  or not
		if (!empty($filters['cluster_id']))
		{
			// Add filter in ULR to apply filter on list views
			$clusterFilter = '&cluster=' . (INT) $filters['cluster_id'];
		}
		elseif ($user->authorise('core.manageall', 'com_cluster') && empty($filters['cluster_id']))
		{
			$clusterFilter = '&cluster=all';
		}
		else
		{
			$clusterUserModel = ClusterFactory::model('ClusterUser', array('ignore_request' => true));
			$clusters = $clusterUserModel->getUsersClusters($user->id);

			foreach ($clusters as $cluster)
			{
				if (RBACL::check($user->id, 'com_cluster', 'view.sarlogDashboard', 'com_multiagency', $cluster->cluster_id))
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

		$urls = array(Text::_('PLG_TJDASHBOARDSOURCE_DPE_SARLOG_OPEN') => '', Text::_('PLG_TJDASHBOARDSOURCE_DPE_SARLOG_CLOSED') => '');

		$urls[Text::_('PLG_TJDASHBOARDSOURCE_DPE_SARLOG_OPEN')] = Route::_(
		$link . $clusterFilter . '&com_tjucm_sarlog_requeststatus=In progress&Itemid=' . $itemId, false
		);

		$urls[Text::_('PLG_TJDASHBOARDSOURCE_DPE_SARLOG_CLOSED')] = Route::_(
		$link . $clusterFilter . '&com_tjucm_sarlog_requeststatus=Closed&Itemid=' . $itemId, false
		);

		$items = [];
		$items['data'] = ['count' => $this->getSarLogData()
		,'titleLink'  => Route::_(
			$link . $clusterFilter . '&Itemid=' . $itemId, false
			)
		// ,'link'  => $urls
		];

		return $items;
	}

	/**
	 * Function to get data of foi log
	 *
	 * @return Array data.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function getFoiLogData()
	{
		try
		{
			$db            = Factory::getDbo();
			$app           = Factory::getApplication();
			$input         = $app->input;
			$user          = Factory::getUser();
			$clusterIds    = array();
			$params        = DPE::config();
			$requestStatus = (int) $params->get('foirequestStatus', '0');
			$dateToRespond = (int) $params->get('foiDateToRespond', '0');
			$currentDate   = new Date('now', 'UTC');
			$dbDateFormat  = "DATE_FORMAT(" . $db->quote($currentDate) . ", '%Y-%m-%d')";
			$dueDateFormat = "DATE_FORMAT(" . $db->qn('fv.value') . ", '%Y-%m-%d')";

			// Create a new query object.
			$query = $db->getQuery(true);

			// Select the required fields from the table.
			$query->select("count(a.id) AS ucmcount, SUM(CASE WHEN fcv.value = 'In progress' THEN 1 ELSE 0 END) as progresscount ,
			 SUM(CASE WHEN fcv.value = 'Closed' THEN 1 ELSE 0 END) as closecount");

			$query->from($db->quoteName('#__tj_ucm_data', 'a'));

			$query->join('LEFT', $db->qn('#__tjfields_fields_value', 'fcv') . ' ON (' .
			$db->qn('fcv.content_id') . ' = ' . $db->qn('a.id') . ' AND ' . $db->qn('fcv.field_id') . ' = ' . $db->q($requestStatus) . ')');

			// To get due date in specified days
			$query->select("SUM(CASE WHEN ( fcv.value = 'In progress' AND " . self::SETDAYS . " >= DATEDIFF( " . $dueDateFormat . ","
			. $dbDateFormat . ") AND 0 <= DATEDIFF( " . $dueDateFormat . ","
			. $dbDateFormat . " )) THEN 1 ELSE 0 END) AS dueDate");

			// To get overdate
			$query->select("SUM(CASE WHEN ( fcv.value = 'In progress' AND DATEDIFF( " . $dueDateFormat . ","
			. $dbDateFormat . ") < 0 ) THEN 1 ELSE 0 END) AS overDate");

			$query->join('LEFT', $db->qn('#__tjfields_fields_value', 'fv') . ' ON (' .
			$db->qn('fv.content_id') . ' = ' . $db->qn('a.id') . ' AND ' . $db->qn('fv.field_id') . ' = ' . $db->q($dateToRespond) . ')');

			$query->where($db->qn('a.client') . " = 'com_tjucm.FOIlog'");

			// Get Filters
			$filters = $input->get('filter', '', 'Array');

			if (!empty($filters['cluster_id']))
			{
				$query->where($db->qn('a.cluster_id') . ' = ' . (INT) $filters['cluster_id']);
			}

			$clusterUserModel = ClusterFactory::model('ClusterUser', array('ignore_request' => true));
			$clusters         = $clusterUserModel->getUsersClusters($user->id);
			$orgAdmin         = ComponentHelper::getParams('com_multiagency')->get('school_admin_role_id');

			foreach ($clusters as $cluster)
			{
				$coreRoleId = RBACL::getCoreRoleByUser($user->id, 'com_cluster', $cluster->cluster_id);

				// Check user have permission to manage all clusters
				if (!$user->authorise('core.manageall', 'com_cluster'))
				{
					// Check user having permission to add staff
					if (RBACL::check($user->id, 'com_cluster', 'view.foilogDashboard', 'com_multiagency', $cluster->cluster_id)
						&& (!in_array($orgAdmin, $coreRoleId)))
					{
						$clusterIds[] = $cluster->cluster_id;
					}
				}
				else
				{
					$clusterIds[] = $cluster->cluster_id;
				}
			}

			if (empty($clusterIds))
			{
				return false;
			}

			$addedBy = '';

			if (!$user->authorise('core.manageall', 'com_cluster'))
			{
				$addedBy = $db->qn('a.created_by') . ' = ' . $user->id . ' AND ';
			}

			if (empty($filters['cluster_id']) && !$user->authorise('core.manageall', 'com_cluster'))
			{
				$query->where("(" . $db->qn('a.cluster_id') . " IN (" . implode(',', $clusterIds) . ") )");
			}

			$query->where($db->qn('a.state') . " = 1 AND " . $db->qn('a.draft') . " = 0 ");

			$db->setQuery($query);

			$foiData = $db->loadAssoc();
		}
		catch (Exception $e)
		{
			throw new Exception($e->getMessage());
		}

		$progresscount = ($foiData['progresscount']) ? $foiData['progresscount'] : 0;
		$closecount = ($foiData['closecount']) ? $foiData['closecount'] : 0;
		$dueDate = ($foiData['dueDate']) ? $foiData['dueDate'] : 0;
		$overDate = ($foiData['overDate']) ? $foiData['overDate'] : 0;
		$ucmCount = ($foiData['ucmcount']) ? $foiData['ucmcount'] : 0;

		$recordInfo = array();

		$recordInfo['widgetdata'] = array(
		Text::_('PLG_TJDASHBOARDSOURCE_DPE_FOILOG_OPEN') => $progresscount,
		Text::sprintf('PLG_TJDASHBOARDSOURCE_DPE_FOILOG_DUE', self::SETDAYS) => $dueDate,
		Text::_('PLG_TJDASHBOARDSOURCE_DPE_FOILOG_OVERDUE') => $overDate,
		// Text::_('PLG_TJDASHBOARDSOURCE_DPE_FOILOG_CLOSED') => $closecount
		);

		$recordInfo['total']       = $ucmCount;
		$recordInfo['closedCount'] = ($closecount) ? $closecount : 0;
		$recordInfo['id']          = "foi_log";

		return $recordInfo;
	}

	/**
	 * Get formatted data for widget
	 *
	 * @return string dataArray
	 *
	 * @since   __DEPLOY_VERSION__
	 * */
	public function getFoiLogFormattedData()
	{
		$link = 'index.php?option=com_tjucm&view=items&client=com_tjucm.FOIlog';
		JLoader::import("/components/com_tjucm/helpers/tjucm", JPATH_SITE);
		$tjucmHelper = new TjucmHelpersTjucm;
		$itemId      = $tjucmHelper->getItemId($link);
		$user        = Factory::getUser();
		$clusterIds  = array();

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
				if (RBACL::check($user->id, 'com_cluster', 'view.foilogDashboard', 'com_multiagency', $cluster->cluster_id))
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

		$urls = array(Text::_('PLG_TJDASHBOARDSOURCE_DPE_FOILOG_OPEN') => '', Text::_('PLG_TJDASHBOARDSOURCE_DPE_FOILOG_CLOSED') => '');

		$urls[Text::_('PLG_TJDASHBOARDSOURCE_DPE_FOILOG_OPEN')] = Route::_(
		$link . $clusterFilter . '&com_tjucm_FOIlog_requeststatus=In progress&Itemid=' . $itemId, false
		);

		$urls[Text::_('PLG_TJDASHBOARDSOURCE_DPE_FOILOG_CLOSED')] = Route::_(
		$link . $clusterFilter . '&com_tjucm_FOIlog_requeststatus=Closed&Itemid=' . $itemId, false
		);

		$items = [];
		$items['data'] = ['count' => $this->getFoiLogData()
		,'titleLink'  => Route::_(
			$link . $clusterFilter . '&Itemid=' . $itemId, false
			)
		// ,'link'  => $urls
		];

		return $items;
	}

	/**
	 * Function to get data of rop
	 *
	 * @return Array data.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function getRopData()
	{
		try
		{
			$db             = Factory::getDbo();
			$app            = Factory::getApplication();
			$input          = $app->input;
			$user           = Factory::getUser();
			$clusterIds     = array();
			$params         = DPE::config();
			$requestStatus  = (int) $params->get('ropRequestStatus', '0');

			// Create a new query object.
			$query = $db->getQuery(true);

			// Select the required fields from the table.
			$query->select("count(a.id) AS ucmcount, SUM(CASE WHEN fcv.value = 'In progress' THEN 1 ELSE 0 END) as progresscount
			, SUM(CASE WHEN fcv.value = 'ToDo' THEN 1 ELSE 0 END) as todocount
			, SUM(CASE WHEN fcv.value = 'DPO Review' THEN 1 ELSE 0 END) as reviewcount,
			SUM(CASE WHEN fcv.value = 'Complete' THEN 1 ELSE 0 END) as complete");

			$query->from($db->quoteName('#__tj_ucm_data', 'a'));

			$query->join('LEFT', $db->qn('#__tjfields_fields_value', 'fcv') . ' ON (' .
			$db->qn('fcv.content_id') . ' = ' . $db->qn('a.id') . ' AND ' . $db->qn('fcv.field_id') . ' = ' . $db->q($requestStatus) . ')');

			$query->where($db->qn('a.client') . " = 'com_tjucm.rop'");

			// Get Filters
			$filters = $input->get('filter', '', 'Array');

			if (!empty($filters['cluster_id']))
			{
				$query->where($db->qn('a.cluster_id') . ' = ' . (INT) $filters['cluster_id']);
			}

			$clusterUserModel = ClusterFactory::model('ClusterUser', array('ignore_request' => true));
			$clusters         = $clusterUserModel->getUsersClusters($user->id);
			$orgAdmin         = ComponentHelper::getParams('com_multiagency')->get('school_admin_role_id');

			foreach ($clusters as $cluster)
			{
				// Check user have permission to manage all clusters
				if (!$user->authorise('core.manageall', 'com_cluster'))
				{
					$coreRoleId = RBACL::getCoreRoleByUser($user->id, 'com_cluster', $cluster->cluster_id);

					// Check user having permission to add staff
					if (RBACL::check($user->id, 'com_cluster', 'view.ropDashboard', 'com_multiagency', $cluster->cluster_id) && (!in_array($orgAdmin, $coreRoleId)))
					{
						$clusterIds[] = $cluster->cluster_id;
					}
				}
				else
				{
					$clusterIds[] = $cluster->cluster_id;
				}
			}

			if (empty($clusterIds))
			{
				return false;
			}

			if (empty($filters['cluster_id']) && !$user->authorise('core.manageall', 'com_cluster'))
			{
				$query->where("(" . $db->qn('a.cluster_id') . " IN (" . implode(',', $clusterIds) . ") )");
			}

			$query->where($db->qn('a.draft') . " = 0 ");

			$db->setQuery($query);

			$ropData = $db->loadAssoc();
		}
		catch (Exception $e)
		{
			throw new Exception($e->getMessage());
		}

		$progresscount = ($ropData['progresscount']) ? $ropData['progresscount'] : 0;
		$reviewcount   = ($ropData['reviewcount']) ? $ropData['reviewcount'] : 0;
		$completecount = ($ropData['complete']) ? $ropData['complete'] : 0;
		$ucmCount      = ($ropData['ucmcount']) ? $ropData['ucmcount'] : 0;

		$recordInfo = array();

		$recordInfo['widgetdata'] = array(
		Text::_('PLG_TJDASHBOARDSOURCE_DPE_ROP_COMPLETE') => $completecount,
		Text::_('PLG_TJDASHBOARDSOURCE_DPE_ROP_OPEN') => $progresscount,
		Text::_('PLG_TJDASHBOARDSOURCE_DPE_ROP_DPO_REVIEW') => $reviewcount
		);

		$recordInfo['total']       = $ucmCount;
		$recordInfo['id']          = "rop";

		return $recordInfo;
	}

	/**
	 * Get formatted data for widget
	 *
	 * @return string dataArray
	 *
	 * @since   __DEPLOY_VERSION__
	 *
	 * */
	public function getRopFormattedData()
	{
		$link = 'index.php?option=com_tjucm&view=items&client=com_tjucm.rop';
		JLoader::import("/components/com_tjucm/helpers/tjucm", JPATH_SITE);
		$tjucmHelper = new TjucmHelpersTjucm;
		$itemId      = $tjucmHelper->getItemId($link);
		$user        = Factory::getUser();
		$clusterIds  = array();

		// Get Filters
		$filters = Factory::getApplication()->input->get('filter', '', 'Array');
		$clusterFilter = '';

		// Check school filter applied  or not
		if (!empty($filters['cluster_id']))
		{
			// Add filter in ULR to apply filter on list views
			$clusterFilter = '&cluster=' . (INT) $filters['cluster_id'];
		}
		elseif (Factory::getUser()->authorise('core.manageall', 'com_cluster') && empty($filters['cluster_id']))
		{
			$clusterFilter = '&cluster=all';
		}
		else
		{
			$clusterUserModel = ClusterFactory::model('ClusterUser', array('ignore_request' => true));
			$clusters = $clusterUserModel->getUsersClusters($user->id);

			foreach ($clusters as $cluster)
			{
				if (RBACL::check($user->id, 'com_cluster', 'view.ropDashboard', 'com_multiagency', $cluster->cluster_id))
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

		$items = [];
		$items['data'] = ['count' => $this->getRopData()
		,'titleLink'  => Route::_(
			$link . $clusterFilter . '&Itemid=' . $itemId, false
			)
		];

		return $items;
	}

	/**
	 * Function to get data of checklist
	 *
	 * @return Array data.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function getChecklistData()
	{
		try
		{
			$db         = Factory::getDbo();
			$app        = Factory::getApplication();
			$input      = $app->input;
			$user       = Factory::getUser();
			$clusterIds = array();

			// Create a new query object.
			$query      = $db->getQuery(true);

			// Select the required fields from the table.
			$query->select("a.id AS ucmcount");

			// Create a new subquery for todos record
			$todosubquery = $db->getQuery(true);
			$todosubquery->select("SUM(CASE WHEN fcv.value = 'todo' THEN 1 ELSE 0 END)");
			$todosubquery->from($db->qn('#__tjfields_fields_value', 'fcv'));
			$todosubquery->where($db->qn('fcv.content_id') . ' = ' . $db->qn('a.id'));
			$query->select("( " . $todosubquery . ") AS todocount");

			// Create a new subquery for inprogress record
			$inprogresssubquery = $db->getQuery(true);
			$inprogresssubquery->select("SUM(CASE WHEN fcvi.value = 'inprogress' THEN 1 ELSE 0 END)");
			$inprogresssubquery->from($db->qn('#__tjfields_fields_value', 'fcvi'));
			$inprogresssubquery->where($db->qn('fcvi.content_id') . ' = ' . $db->qn('a.id'));
			$query->select("( " . $inprogresssubquery . ") AS inprogresscount");

			// Create a new subquery for done record
			$donesubquery = $db->getQuery(true);
			$donesubquery->select("SUM(CASE WHEN fcvd.value = 'done' THEN 1 ELSE 0 END)");
			$donesubquery->from($db->qn('#__tjfields_fields_value', 'fcvd'));
			$donesubquery->where($db->qn('fcvd.content_id') . ' = ' . $db->qn('a.id'));
			$query->select("( " . $donesubquery . ") AS donecount");

			$query->from($db->quoteName('#__tj_ucm_data', 'a'));
			$query->join('INNER', $db->qn('#__tj_ucm_types', 't') . ' ON (' .
			$db->qn('a.type_id') . ' = ' . $db->qn('t.id') . ')');

			$query->where($db->quoteName('t.params') . ' LIKE "%dpe_checklist=1%"');
			$query->where($db->quoteName('t.state') . ' = ' . $db->q('1'));

			// Get Filters
			$filters = $input->get('filter', '', 'Array');

			if (!empty($filters['cluster_id']))
			{
				$query->where($db->qn('a.cluster_id') . ' = ' . (INT) $filters['cluster_id']);
			}

			if ($user->id && !$user->authorise('core.manageall', 'com_cluster'))
			{
				JLoader::import("/components/com_cluster/includes/cluster", JPATH_ADMINISTRATOR);
				$clusterUserModel = ClusterFactory::model('ClusterUser', array('ignore_request' => true));
				$clusters         = $clusterUserModel->getUsersClusters($user->id);
				$orgAdmin         = ComponentHelper::getParams('com_multiagency')->get('school_admin_role_id');

				foreach ($clusters as $cluster)
				{
					// Check user have permission to manage all clusters
					if (!$user->authorise('core.manageall', 'com_cluster'))
					{
						$coreRoleId = RBACL::getCoreRoleByUser($user->id, 'com_cluster', $cluster->cluster_id);

						// Check user having permission to add staff
						if (RBACL::check($user->id, 'com_cluster', 'view.checklistDashboard', 'com_multiagency', $cluster->cluster_id)
							&& (!in_array($orgAdmin, $coreRoleId)))
						{
							$clusterIds[] = $cluster->cluster_id;
						}
					}
					else
					{
						$clusterIds[] = $cluster->cluster_id;
					}
				}

				if (empty($clusterIds))
				{
					return false;
				}

				if (empty($filters['cluster_id']))
				{
					$query->where($db->qn('a.cluster_id') . " IN (" . implode(',', $clusterIds) . ")");
				}
			}

			$mainQuery = $db->getQuery(true);
			$mainQuery->select('count(ucmcount) AS totalChecklist
			, SUM(todocount) AS todochecklist
			, SUM(inprogresscount)AS progressChecklist
			, SUM(donecount) AS donechecklist');
			$mainQuery->from('( ' . $query . ' ) AS checklistUcm');

			$db->setQuery($mainQuery);

			$checklistData = $db->loadAssoc();
		}
		catch (Exception $e)
		{
			throw new Exception($e->getMessage());
		}

		$todochecklist     = ($checklistData['todochecklist']) ? $checklistData['todochecklist'] : 0;
		$progressChecklist = ($checklistData['progressChecklist']) ? $checklistData['progressChecklist'] : 0;
		$donechecklist     = ($checklistData['donechecklist']) ? $checklistData['donechecklist'] : 0;
		$ucmCount          = ($checklistData['totalChecklist']) ? $checklistData['totalChecklist'] : 0;

		$recordInfo = array();

		$recordInfo['widgetdata'] = array(
			Text::_('PLG_TJDASHBOARDSOURCE_DPE_CHECKLIST_DONE') => $donechecklist,
			Text::_('PLG_TJDASHBOARDSOURCE_DPE_CHECKLIST_INPROGRESS') => $progressChecklist,
			Text::_('PLG_TJDASHBOARDSOURCE_DPE_CHECKLIST_TODO') => $todochecklist
		);

		$recordInfo['total'] = $ucmCount;
		$recordInfo['id']    = "checklist";

		return $recordInfo;
	}

	/**
	 * Get formatted data for widget
	 *
	 * @return string dataArray
	 *
	 * @since   __DEPLOY_VERSION__
	 *
	 * */
	public function getChecklistFormattedData()
	{
		// Get Filters
		$filters       = Factory::getApplication()->input->get('filter', '', 'Array');
		$clusterFilter = '';
		$user          = Factory::getUser();
		$clusterIds    = array();

		// Check school filter applied  or not
		if (!empty($filters['cluster_id']))
		{
			// Add filter in ULR to redirect with filter
			$clusterFilter = '&filter[cluster_id]=' . (INT) $filters['cluster_id'];
		}
		elseif (!$user->authorise('core.manageall', 'com_cluster') && empty($filters['cluster_id']))
		{
			// This block is executes when user is not dpe admin and landing on dashboard by login, by clicking menu
			$clusterUserModel = ClusterFactory::model('ClusterUser', array('ignore_request' => true));
			$clusters = $clusterUserModel->getUsersClusters($user->id);

			foreach ($clusters as $cluster)
			{
				if (RBACL::check($user->id, 'com_cluster', 'view.checklistDashboard', 'com_multiagency', $cluster->cluster_id))
				{
					$clusterIds[] = $cluster->cluster_id;
				}
			}

			$clusterFilter = '&filter[cluster_id]=' . $clusterIds[0];
		}

		$link       = 'index.php?option=com_dpe&view=dashboard';
		$dpeUtility = DPE::utilities();
		$itemId = $dpeUtility->getItemId($link);

		$items = [];
		$items['data'] = ['count' => $this->getChecklistData()
		,'titleLink'  => Route::_(
		$link . $clusterFilter . '&Itemid=' . $itemId, false
		)
		];

		return $items;
	}

	/**
	 * Function to get data of the whole block
	 *
	 * @return Array data.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function getPhishingData()
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

			if (!empty($filters['cluster_id']))
			{
				$query->where($db->qn('tjc.id') . ' = ' . (INT) $filters['cluster_id']);
			}

			if (!$user->authorise('core.manageall', 'com_cluster'))
			{
				JLoader::import("/components/com_cluster/includes/cluster", JPATH_ADMINISTRATOR);
				$clusterUserModel = ClusterFactory::model('ClusterUser', array('ignore_request' => true));
				$clusters         = $clusterUserModel->getUsersClusters($user->id);
				$orgAdmin         = ComponentHelper::getParams('com_multiagency')->get('school_admin_role_id');
				$clusterIds = array();

				foreach ($clusters as $cluster)
				{
					$coreRoleId = RBACL::getCoreRoleByUser($user->id, 'com_cluster', $cluster->cluster_id);

					if (RBACL::check($user->id, 'com_cluster', 'view.phishingsimulationDashboard', 'com_multiagency', $cluster->cluster_id)
						&& (!in_array($orgAdmin, $coreRoleId)))
					{
						$clusterIds[] = $cluster->cluster_id;
					}
				}

				if (empty($clusterIds))
				{
					return false;
				}

				if (empty($filters['cluster_id']))
				{
					$query->where($db->qn('tjc.id') . " IN ( " . implode(',', $clusterIds) . ")");
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

		$recordInfo['widgetdata'] = array(
			Text::_('PLG_TJDASHBOARDSOURCE_DPE_GOPHISH_CAMPAIGNS') => (
			($gophishData['campaignscount']) ? $gophishData['campaignscount'] : 0),
			Text::_('PLG_TJDASHBOARDSOURCE_DPE_GOPHISH_GROUPS') => (
			($gophishData['groupscount']) ? $gophishData['groupscount'] : 0)
		);

		return $recordInfo;
	}

	/**
	 * Get Data for phishing simulation
	 *
	 * @return string dataArray
	 *
	 * @since   __DEPLOY_VERSION__
	 * */
	public function getPhishingFormattedData()
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

		$urls = array(
			Text::_('PLG_TJDASHBOARDSOURCE_DPE_GOPHISH_CAMPAIGNS') => ''
			, Text::_('PLG_TJDASHBOARDSOURCE_DPE_GOPHISH_GROUPS') => ''
		);

		$urls[Text::_('PLG_TJDASHBOARDSOURCE_DPE_GOPHISH_CAMPAIGNS')] = Route::_(
		$campaignsLink . $clusterFilter . '&Itemid=' . $campaignsMenuItem->id, false
		);

		$urls[Text::_('PLG_TJDASHBOARDSOURCE_DPE_GOPHISH_GROUPS')] = Route::_(
		$groupsLink . $clusterFilter . '&Itemid=' . $groupsMenuItem->id, false
		);

		$items = [];
		$items['data'] = ['count' => $this->getPhishingData(),'titleLink'  =>
		Route::_($campaignsLink . $clusterFilter . '&Itemid=' . $campaignsMenuItem->id, false),
		'link'  => $urls
		];

		return $items;
	}

	/**
	 * Get Data for Redaction Tool
	 *
	 * @return string dataArray
	 *
	 * @since   __DEPLOY_VERSION__
	 * */
	public function getRedactionFormattedData()
	{
		$app               = Factory::getApplication();
		$menu              = $app->getMenu();
		$groupsMenuItem    = $menu->getItems('link', 'index.php?option=com_dpe&view=redaction&tmpl=component', true);
		$user          = Factory::getUser();
		$coreRoleId = RBACL::getCoreRoleByUser($user->id, 'com_cluster', $cluster->cluster_id);


		if ($user->id && !$user->authorise('core.manageall', 'com_cluster'))
		{

		$dpeParams    = ComponentHelper::getParams('com_dpe');
		$allTools     = new Registry($dpeParams->get('allTools'));
		$allToolsdata = $allTools->get('tools');
		$redactionTool = 'com_dpe.redaction';


		$db    = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select('tr.role_id');
		$query->from($db->qn('#__tjsu_users', 'tr'));
		$query->where($db->quoteName('tr.user_id'). " = ".  $user->id);
		$query->where($db->qn('tr.state') . '=' . 1);

		$db->setQuery($query);
		$allRoles = $db->loadColumn();

		if (in_array($allToolsdata->$redactionTool->role_ids[0], $allRoles))
			{
				$items = [];
			$items['data'] = ['titleLink'  =>
			Route::_('index.php?option=com_dpe&view=redaction&tmpl=component&Itemid=' . $groupsMenuItem->id, false)
			];

			}
		}

		
		return $items;
	}

	/**
	 * Method to get the record form.
	 *
	 * @param   array    $data      Data for the form.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  mixed    A Form object on success, false on failure
	 *
	 * @since   0.0.1
	 */
	public function getForm($data = array(), $loadData = true)
	{
		return true;
	}
}
