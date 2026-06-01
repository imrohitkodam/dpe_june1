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
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Http\Http;
use Joomla\CMS\Table\Table;



JLoader::import("/components/com_dpe/includes/dpe", JPATH_SITE);
JLoader::import("/components/com_cluster/includes/cluster", JPATH_ADMINISTRATOR);

/**
 * Staff dashboard
 *
 * @since  __DEPLOY_VERSION__
 */
class DpeModelAnnualReport extends AdminModel
{
	// To set hours due days
	const SETHOURS = 72;

	const SETDAYS = 7;


	
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

		// Get the form.
		$form = $this->loadForm('com_dpe.annualreport', 'annualreport', array('control' => 'jform', 'load_data' => $loadData));

		return empty($form) ? false : $form;
	}

	/**
	 * Function to get ticket data
	 *
	 * @return Array data.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function getTicketData($data)
	{
		try
		{
			$db  = Factory::getDbo();
			$query = $db->getQuery(true);

			$app = Factory::getApplication();
			$input = $app->input;
			$user  = Factory::getUser();
			$filters = $data;

			if ((!$filters['date_range']) && (!$filters['start_date'] && !$filters['end_date'])) {
				return false;
			}

			if ($filters['date_range']) {
				$endDate = new DateTime();
				$startDate = new DateTime();
				$startDate->modify('-' . $filters['date_range'] . ' months');
				$startDate  = $startDate->format('Y-m-d');
				$endDate    = $endDate->format('Y-m-d');
			} else {
				$startDate = $filters['start_date'];
				$endDate   = $filters['end_date'] ? $filters['end_date'] : (new DateTime())->format('Y-m-d');
			}

			$today = (new DateTime())->format('Y-m-d');
			if ($filters['end_date'] === $today || empty($filters['end_date'])) {
				$endDate = $today . ' 23:59:59';
			} else {
				$endDate = $endDate . ' 23:59:59';
			}

			$clusterIds = isset($filters['cluster_id']) ? (array) $filters['cluster_id'] : [];
			if (!empty($clusterIds)) {
				$clusterFilter = ' AND tjc.id IN (' . implode(',', array_map('intval', $clusterIds)) . ')';
			} else {
				$clusterFilter = '';
			}

	// Construct the query
			$query = $db->getQuery(true)
			->select([
		// Existing tickets open at the start
				'(SELECT COUNT(DISTINCT a.id) FROM ' . $db->quoteName('#__rsticketspro_tickets') . ' AS a
				LEFT JOIN ' . $db->quoteName('#__rsticket_integration_xref') . ' AS rsxref ON rsxref.ticket_id = a.id
				LEFT JOIN ' . $db->quoteName('#__tj_clusters') . ' AS tjc ON tjc.id = rsxref.agency_id
				WHERE a.date < ' . $db->quote($startDate) . ' 
				AND (a.closed IS NULL OR a.closed >= ' . $db->quote($startDate) . ')
				' . $clusterFilter . ') AS Existing_tickets_open_at_the_start_of_the_reporting_period',

		// Tickets created during the period
				'(SELECT COUNT(DISTINCT a.id) 
				FROM ' . $db->quoteName('#__rsticketspro_tickets') . ' AS a
				LEFT JOIN ' . $db->quoteName('#__rsticket_integration_xref') . ' AS rsxref ON rsxref.ticket_id = a.id
				LEFT JOIN ' . $db->quoteName('#__tj_clusters') . ' AS tjc ON tjc.id = rsxref.agency_id
				WHERE a.date BETWEEN ' . $db->quote($startDate) . ' AND ' . $db->quote($endDate) . '
				' . $clusterFilter . ') AS Tickets_created_during_the_reporting_period',

		// Tickets closed during the period
				'(SELECT COUNT(DISTINCT a.id) 
				FROM ' . $db->quoteName('#__rsticketspro_tickets') . ' AS a
				LEFT JOIN ' . $db->quoteName('#__rsticket_integration_xref') . ' AS rsxref ON rsxref.ticket_id = a.id
				LEFT JOIN ' . $db->quoteName('#__tj_clusters') . ' AS tjc ON tjc.id = rsxref.agency_id
				WHERE a.closed BETWEEN ' . $db->quote($startDate) . ' AND ' . $db->quote($endDate) . ' ' . $clusterFilter . ') AS Total_tickets_closed_during_the_reporting_period',

		// Tickets remaining open at end
				'(SELECT COUNT(DISTINCT a.id) 
				FROM ' . $db->quoteName('#__rsticketspro_tickets') . ' AS a
				LEFT JOIN ' . $db->quoteName('#__rsticket_integration_xref') . ' AS rsxref ON rsxref.ticket_id = a.id
				LEFT JOIN ' . $db->quoteName('#__tj_clusters') . ' AS tjc ON tjc.id = rsxref.agency_id
				WHERE a.date <= ' . $db->quote($endDate) . '
				AND (a.closed IS NULL OR a.status_id IN (1,3))
				' . $clusterFilter . ') AS Tickets_remaining_open_at_the_end_of_the_reporting_period'
			]);

			$db->setQuery($query);
			$results = $db->loadAssoc();
		}
		catch (Exception $e)
		{
			throw new Exception($e->getMessage());
		}


		return $results;
	}



	/**
	 * Function to get data of compliance maneger
	 *
	 * @return Array data.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function getComplianceData($data)
	{
		try
		{
			$db = Factory::getDbo();
			$app = Factory::getApplication();
			$input = $app->input;
			$user = Factory::getUser();
			$filters = $data;

			if ($filters['date_range']) {
				$endDate = new DateTime();
				$startDate = new DateTime();
		    $startDate->modify('-'.$filters['date_range'].' months'); // Subtract months from today's date
		    $filters['start_date'] = $startDate->format('Y-m-d');
		    $filters['end_date'] = $endDate->format('Y-m-d');
		}else{
			if(!$filters['end_date'])
			{
				$endDate = new DateTime();
			}else
			{
				$endDate   = $filters['end_date'];
			}
		}

		$today = (new DateTime())->format('Y-m-d');

		if ($filters['end_date'] === $today || empty($filters['end_date'])) {
				$filters['end_date'] = $today . ' 23:59:59'; // Include today fully
			}else
			{
				$filters['end_date'] = $filters['end_date'] . ' 23:59:59';	
			}

			$query = $db->getQuery(true);
			

		// **Total Aggregated Data (Whole Period)**
			$query->select([
				'COUNT(DISTINCT a.id) AS Total_documents_assigned',
				'ROUND((COUNT(DISTINCT CASE WHEN te.read = 1 THEN user.id END) / COUNT(DISTINCT user.id)) * 100) AS Total_percentage_completed','cl.name as Organisation'
			]);

			$query->from($db->quoteName('#__tjlms_lessons', 'a'))
			->innerJoin($db->quoteName('#__jlike_content', 'jc') . ' ON a.id = jc.element_id AND jc.element = "com_tjlms.lesson"')
			->leftJoin($db->quoteName('#__jlike_todos', 'jt') . ' ON jc.id = jt.content_id')
			->leftJoin($db->quoteName('#__users', 'user') . ' ON jt.assigned_to = user.id')
			->leftJoin($db->quoteName('#__jlike_todos_extended', 'te') . ' ON jt.id = te.todo_id')
			->innerJoin($db->quoteName('#__tjlms_media', 'tm') . ' ON a.media_id = tm.id')
			->innerJoin($db->quoteName('#__tjlms_lesson_cluster_xref', 'lc') . ' ON a.id = lc.lesson_id')
			->innerJoin($db->quoteName('#__tj_clusters', 'cl') . ' ON lc.cluster_id = cl.id');

				// Filters
			$query->where([
				'a.state IN (0, 1)',
				'cl.state = 1',
				'a.format NOT IN ("quiz", "exercise", "feedback")',
				'a.in_lib = 1',
				'(user.id IS NULL OR user.block = 0)'
			]);

			if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
				$query->where('jt.start_date BETWEEN ' . $db->quote($filters['start_date']) . ' AND ' . $db->quote($filters['end_date']));
			}

			if (!empty($filters['cluster_id'])) {
				if (is_array($filters['cluster_id'])) {
					$query->where($db->quoteName('cl.id') . " IN (" . implode(',', array_map([$db, 'quote'], $filters['cluster_id'])) . ")");
				} else {
					$query->where($db->quoteName('cl.id') . ' = ' . (int) $filters['cluster_id']);
				}
			}

				// Grouping by cluster
			$query->group('cl.id');
			$query->order('cl.name ASC');
			$db->setQuery($query);

			$totalData = $db->loadObjectList();  

						// **Documents and Their Respective Data**
			$query->clear()->select(['cl.name as Organisation',
				'a.title AS Document_title',
				'COUNT(user.id) AS Users_assigned',
				'CONCAT(
				"Read and Understood ", SUM(te.read), "/", COUNT(DISTINCT user.id),
				CASE 
				WHEN SUM(te.used) > 0 THEN CONCAT(", Used in Practice ", SUM(te.used), "/", COUNT(DISTINCT user.id))
				ELSE ""
				END
			) AS Document_status'
		]);


			$query->from($db->quoteName('#__tjlms_lessons', 'a'))
			->innerJoin($db->quoteName('#__jlike_content', 'jc') . ' ON a.id = jc.element_id AND jc.element = "com_tjlms.lesson"')
			->leftJoin($db->quoteName('#__jlike_todos', 'jt') . ' ON jc.id = jt.content_id')
			->leftJoin($db->quoteName('#__users', 'user') . ' ON jt.assigned_to = user.id')
			->leftJoin($db->quoteName('#__jlike_todos_extended', 'te') . ' ON jt.id = te.todo_id')
			->innerJoin($db->quoteName('#__tjlms_media', 'tm') . ' ON a.media_id = tm.id')
			->innerJoin($db->quoteName('#__tjlms_lesson_cluster_xref', 'lc') . ' ON a.id = lc.lesson_id')
			->innerJoin($db->quoteName('#__tj_clusters', 'cl') . ' ON lc.cluster_id = cl.id');


			$query->where([
				'a.state IN (0, 1)',
				'cl.state = 1',
				'a.format NOT IN ("quiz", "exercise", "feedback")',
				'a.in_lib = 1',
				'(user.id IS NULL OR user.block = 0)'
			]);
			if (!empty($filters['cluster_id'])) {
				if (is_array($filters['cluster_id'])) {
					$query->where($db->quoteName('cl.id') . " IN (" . implode(',', array_map([$db, 'quote'], $filters['cluster_id'])) . ")");
				} else {
					$query->where($db->quoteName('cl.id') . ' = ' . (int) $filters['cluster_id']);
				}
			}


			if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
				$query->where('jt.start_date BETWEEN ' . $db->quote($filters['start_date']) . ' AND ' . $db->quote($filters['end_date']));
			}

				// Group by document ID for individual document data
			$query->group('a.id');
				$query->order('cl.name ASC');  // Sort by document title
				$db->setQuery($query);

				// Load document data
				$documentData = $db->loadObjectList();

				$complianceManager['totalData'] = $totalData;
				
				foreach($documentData as $document)
				{
					if($document->Document_status == null)
					{
						$document->Document_status = 'Read and Understood 0/'.$document->Users_assigned.', Used in Practice 0/'.$document->Users_assigned;
					}
				}

				$complianceManager['document_list'] = $documentData;
			}
			catch (Exception $e)
			{
				throw new Exception($e->getMessage());
			}

			return $complianceManager;
		}

	/**
	 * Function to get data of Complaints
	 *
	 * @return Array data.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function getComplaintData($data)
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
			$requestComplaintsArray = $params->get('anuualreportbreach', []);
			$requestComplaintsStatus = implode(',', $requestComplaintsArray);
			$requestStatus     = (int) $params->get('dpeComplaintsLog', '0');
			
			$currentDate      = new Date('now', 'UTC');

			$filters = $data;

			if (!empty($filters['date_range'])) {
				$endDate = new DateTime();

			    // Set the start date as 'date_range' months before today
				$startDate = new DateTime();
			    $startDate->modify('-' . $filters['date_range'] . ' months'); // Subtract months from today's date

			    // Format dates
			    $startDate  = $startDate->format('Y-m-d');
			    $endDate    = $endDate->format('Y-m-d');
			} else {
				$startDate = $filters['start_date'];
				if(!$filters['end_date'])
				{
					$endDate = new DateTime();
				}else
				{
					$endDate   = $filters['end_date'];
				}
			}
			$today = (new DateTime())->format('Y-m-d');

			if ($filters['end_date'] === $today || empty($filters['end_date'])) {
			    $endDate = $today . ' 23:59:59'; // Include today fully
			}
			else
			{
				$endDate = $endDate . ' 23:59:59'; 
			}
			// Get DB object
			if (!empty($requestComplaintsStatusArray)) {
				// Sanitize values to prevent SQL injection
				$sanitizedStatusArray = array_map('intval', $requestComplaintsStatusArray);
				$requestComplaintsStatus = implode(',', $sanitizedStatusArray);

				$FeildTittlequery = $db->getQuery(true)
				->select(['id', 'title'])
				->from($db->quoteName('#__tjfields_fields'))
				->where('id IN (' . $requestComplaintsStatus . ')');

				$db->setQuery($FeildTittlequery);
				$fields = $db->loadAssocList('id', 'title'); // Now you get [101 => 'Reported to ICO', ...]
			}
			// Quote the dates to avoid SQL injection issues
			$quotedStartDate = $db->quote($startDate);
			$quotedEndDate   = $db->quote($endDate);

			$query = $db->getQuery(true);

			// Build dynamic selects
			$selects = [];
			$i = 1;

			foreach ($fields as $fieldId => $title) {
				$alias = 'fcvl' . $i++;

				$query->join(
					'LEFT',
					$db->quoteName('#__tjfields_fields_value', $alias) . ' ON ' .
					$db->quoteName($alias . '.content_id') . ' = ' . $db->quoteName('a.id') .
					' AND ' . $db->quoteName($alias . '.field_id') . ' = ' . (int) $fieldId
				);

				$safeAlias = preg_replace('/[^A-Za-z0-9_]/', '_', str_replace(' ', '_', trim($title)));

				$selects[] = "SUM(CASE WHEN a.modified_date BETWEEN $quotedStartDate AND $quotedEndDate AND $alias.value = 'yes' OR $alias.value = 'Closed' THEN 1 ELSE 0 END) AS `breach_indicator__$safeAlias`";
			}
			// Append static selects
			$selects = array_merge($selects, [
				"SUM(CASE WHEN a.created_date < $quotedStartDate AND (a.modified_date IS NULL OR a.modified_date >= $quotedStartDate) THEN 1 ELSE 0 END) AS Existing_logs_open_at_the_start_of_the_reporting_period",
				"SUM(CASE WHEN a.created_date BETWEEN $quotedStartDate AND $quotedEndDate AND a.modified_date BETWEEN $quotedStartDate AND $quotedEndDate AND fcv.value = 'Closed' THEN 1 ELSE 0 END) AS Number_Of_logs_closed_during_the_period",
				"SUM(CASE WHEN a.created_date BETWEEN $quotedStartDate AND $quotedEndDate THEN 1 ELSE 0 END) AS New_logs_created_during_the_reporting_period",
				"SUM(CASE WHEN a.modified_date BETWEEN $quotedStartDate AND $quotedEndDate AND fcv.value = 'Closed' THEN 1 ELSE 0 END) AS Total_logs_closed_during_the_reporting_period",
				"ROUND(AVG(
				CASE 
				WHEN fcv.value = 'Closed' 
				THEN GREATEST(DATEDIFF(a.modified_date, a.created_date), 1)
				ELSE GREATEST(DATEDIFF(LEAST(NOW(), $quotedEndDate), a.created_date), 1)
				END
			)) AS `Average_lifecycle_duration_initiation_to_resolution_(days)`"
		]);




			// Select required statistics
			$query->select($selects);

			// From main UCM data table
			$query->from($db->quoteName('#__tj_ucm_data', 'a'));

			// Join fields value table to fetch the status of logs
			$query->join('LEFT', $db->quoteName('#__tjfields_fields_value', 'fcv') . ' ON ' .
				$db->quoteName('fcv.content_id') . ' = ' . $db->quoteName('a.id') .
				' AND ' . $db->quoteName('fcv.field_id') . ' = ' . $db->quote($requestStatus)
			);

			// Apply filters
			$query->where($db->quoteName('a.client') . " = 'com_tjucm.dpcomplaintslog'");
			$query->where($db->quoteName('a.state') . " = 1");
			$query->where($db->quoteName('a.draft') . " = 0");

			// Filter by cluster if provided
			if (!empty($filters['cluster_id']) && !is_array($filters['cluster_id'])) {
				$query->where($db->quoteName('a.cluster_id') . ' = ' . (int) $filters['cluster_id']);
			} elseif (!empty($filters['cluster_id']) && is_array($filters['cluster_id'])) {
				$clusterIds = array_map('intval', $filters['cluster_id']);
				if (!empty($clusterIds)) {
					$query->where($db->quoteName('a.cluster_id') . " IN (" . implode(',', $clusterIds) . ")");
				}
			}

			// Set the query
			$db->setQuery($query);

			$Complaints = $db->loadAssoc();

			if (
				$Complaints['Existing_logs_open_at_the_start_of_the_reporting_period'] == 0 &&
				$Complaints['Number_Of_Logs_Closed_During_The_Period'] == 0 &&
				$Complaints['New_logs_created_during_the_reporting_period'] == 0 &&
				$Complaints['Total_logs_closed_during_the_reporting_period'] == 0
			) {
				$Complaints['Average_lifecycle_duration_initiation_to_resolution_(days)'] = 0;
			}

		}
		catch (Exception $e)
		{
			throw new Exception($e->getMessage());
		}

		return $Complaints;
	}

	/**
	 * Function to get data of breach log
	 *
	 * @return Array data.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function getBreachLogData($data)
	{
		try
		{
			// Create a new query object
			$db               = Factory::getDbo();
			$app              = Factory::getApplication();
			$input            = $app->input;
			$user             = Factory::getUser();
			$clusterIds       = array();
			$params           = DPE::config();
			$requestbreachStatusArray = $params->get('anuualreportbreach', []);
			$requestbreachStatus = implode(',', $requestbreachStatusArray);
			$requestStatus     = (int) $params->get('breachStatus', '0');
			
			$currentDate      = new Date('now', 'UTC');

			$filters = $data;

			if (!empty($filters['date_range'])) {
				$endDate = new DateTime();

			    // Set the start date as 'date_range' months before today
				$startDate = new DateTime();
			    $startDate->modify('-' . $filters['date_range'] . ' months'); // Subtract months from today's date

			    // Format dates
			    $startDate  = $startDate->format('Y-m-d');
			    $endDate    = $endDate->format('Y-m-d');
			} else {
				$startDate = $filters['start_date'];
				if(!$filters['end_date'])
				{
					$endDate = new DateTime();
				}else
				{
					$endDate   = $filters['end_date'];
				}
			}
			$today = (new DateTime())->format('Y-m-d');

			if ($filters['end_date'] === $today || empty($filters['end_date'])) {
			    $endDate = $today . ' 23:59:59'; // Include today fully
			}
			else
			{
				$endDate = $endDate . ' 23:59:59'; 
			}
			// Get DB object
			if (!empty($requestbreachStatusArray)) {
				// Sanitize values to prevent SQL injection
				$sanitizedStatusArray = array_map('intval', $requestbreachStatusArray);
				$requestbreachStatus = implode(',', $sanitizedStatusArray);

				$FeildTittlequery = $db->getQuery(true)
				->select(['id', 'title'])
				->from($db->quoteName('#__tjfields_fields'))
				->where('id IN (' . $requestbreachStatus . ')');

				$db->setQuery($FeildTittlequery);
				$fields = $db->loadAssocList('id', 'title'); // Now you get [101 => 'Reported to ICO', ...]
			}
			// Quote the dates to avoid SQL injection issues
			$quotedStartDate = $db->quote($startDate);
			$quotedEndDate   = $db->quote($endDate);

			$query = $db->getQuery(true);

			// Build dynamic selects
			$selects = [];
			$i = 1;

			foreach ($fields as $fieldId => $title) {
				$alias = 'fcvl' . $i++;

				$query->join(
					'LEFT',
					$db->quoteName('#__tjfields_fields_value', $alias) . ' ON ' .
					$db->quoteName($alias . '.content_id') . ' = ' . $db->quoteName('a.id') .
					' AND ' . $db->quoteName($alias . '.field_id') . ' = ' . (int) $fieldId
				);

				$safeAlias = preg_replace('/[^A-Za-z0-9_]/', '_', str_replace(' ', '_', trim($title)));

				$selects[] = "SUM(CASE WHEN a.modified_date BETWEEN $quotedStartDate AND $quotedEndDate AND $alias.value = 'yes' OR $alias.value = 'Closed' THEN 1 ELSE 0 END) AS `breach_indicator__$safeAlias`";
			}
			// Append static selects
			$selects = array_merge($selects, [
				"SUM(CASE WHEN a.created_date < $quotedStartDate AND (a.modified_date IS NULL OR a.modified_date >= $quotedStartDate) THEN 1 ELSE 0 END) AS Existing_logs_open_at_the_start_of_the_reporting_period",
				"SUM(CASE WHEN a.created_date BETWEEN $quotedStartDate AND $quotedEndDate AND a.modified_date BETWEEN $quotedStartDate AND $quotedEndDate AND fcv.value = 'Closed' THEN 1 ELSE 0 END) AS Number_Of_logs_closed_during_the_period",
				"SUM(CASE WHEN a.created_date BETWEEN $quotedStartDate AND $quotedEndDate THEN 1 ELSE 0 END) AS New_logs_created_during_the_reporting_period",
				"SUM(CASE WHEN a.modified_date BETWEEN $quotedStartDate AND $quotedEndDate AND fcv.value = 'Closed' THEN 1 ELSE 0 END) AS Total_logs_closed_during_the_reporting_period",
				
				"ROUND(AVG(
				CASE 
				WHEN fcv.value = 'Closed' 
				THEN GREATEST(DATEDIFF(a.modified_date, a.created_date), 1)
				ELSE GREATEST(DATEDIFF(LEAST(NOW(), $quotedEndDate), a.created_date), 1)
				END
			)) AS `Average_lifecycle_duration_initiation_to_resolution_(days)`"
		]);




			// Select required statistics
			$query->select($selects);

			// From main UCM data table
			$query->from($db->quoteName('#__tj_ucm_data', 'a'));

			// Join fields value table to fetch the status of logs
			$query->join('LEFT', $db->quoteName('#__tjfields_fields_value', 'fcv') . ' ON ' .
				$db->quoteName('fcv.content_id') . ' = ' . $db->quoteName('a.id') .
				' AND ' . $db->quoteName('fcv.field_id') . ' = ' . $db->quote($requestStatus)
			);

			// Apply filters
			$query->where($db->quoteName('a.client') . " = 'com_tjucm.breachlog'");
			$query->where($db->quoteName('a.state') . " = 1")->where($db->quoteName('a.created_date') . " BETWEEN $quotedStartDate AND $quotedEndDate");
			$query->where($db->quoteName('a.draft') . " = 0");

			// Filter by cluster if provided
			if (!empty($filters['cluster_id']) && !is_array($filters['cluster_id'])) {
				$query->where($db->quoteName('a.cluster_id') . ' = ' . (int) $filters['cluster_id']);
			} elseif (!empty($filters['cluster_id']) && is_array($filters['cluster_id'])) {
				$clusterIds = array_map('intval', $filters['cluster_id']);
				if (!empty($clusterIds)) {
					$query->where($db->quoteName('a.cluster_id') . " IN (" . implode(',', $clusterIds) . ")");
				}
			}


			// Set the query
			$db->setQuery($query); 


			$breachData = $db->loadAssoc();

			if (
				$breachData['Existing_logs_open_at_the_start_of_the_reporting_period'] == 0 &&
				$breachData['Number_Of_Logs_Closed_During_The_Period'] == 0 &&
				$breachData['New_logs_created_during_the_reporting_period'] == 0 &&
				$breachData['Total_logs_closed_during_the_reporting_period'] == 0
			) {
				$breachData['Average_lifecycle_duration_initiation_to_resolution_(days)'] = 0;
			}

		}
		catch (Exception $e)
		{
			throw new Exception($e->getMessage());
		}

		return $breachData;
	}

	/**
	 * Function to get data of sar log
	 *
	 * @return Array data.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function getSarLogData($data)
	{
		try
		{
			$db            = Factory::getDbo();
			$app           = Factory::getApplication();
			$input         = $app->input;
			$user          = Factory::getUser();
			$params        = DPE::config();
			$requestStatus = (int) $params->get('requestStatus', '0');
			$filters = $data;

			if ($filters['date_range'])
			{
				$endDate = new DateTime();

				// Set the start date as 3 months before today
				$startDate = new DateTime();
				$startDate->modify('-'.$filters['date_range'].' months'); // Subtract  months from today's date

				// Format the dates as 'Y-m-d' (you can change the format as needed)
				$startDate  = $startDate->format('Y-m-d');
				$endDate    = $endDate->format('Y-m-d');
			}
			else
			{
				$startDate = $filters['start_date'];
				if(!$filters['end_date'])
				{
					$endDate = new DateTime();
				}else
				{
					$endDate   = $filters['end_date'];
				}
			}
			
			$today = (new DateTime())->format('Y-m-d');

			if ($filters['end_date'] === $today || empty($filters['end_date'])) {
			    $endDate = $today . ' 23:59:59'; // Include today fully
			}else
			{
				$endDate = $endDate . ' 23:59:59'; 
			}
			$quotedStartDate = $db->quote($startDate);
			$quotedEndDate   = $db->quote($endDate);

			$query = $db->getQuery(true);

		// Select required statistics
			$query->select([

				"SUM(CASE WHEN a.created_date < $quotedStartDate AND (a.modified_date IS NULL OR a.modified_date >= $quotedStartDate) THEN 1 ELSE 0 END) AS Existing_logs_open_at_the_start_of_the_reporting_period",
				" SUM(CASE WHEN a.created_date BETWEEN $quotedStartDate AND $quotedEndDate AND a.modified_date BETWEEN $quotedStartDate AND $quotedEndDate AND fcv.value = 'Closed' THEN 1 ELSE 0 END) AS Number_Of_logs_closed_during_the_period",

				"SUM(CASE WHEN a.created_date BETWEEN $quotedStartDate AND $quotedEndDate THEN 1 ELSE 0 END) AS New_logs_created_during_the_reporting_period
				",
				" SUM(CASE WHEN a.modified_date BETWEEN $quotedStartDate AND $quotedEndDate AND fcv.value = 'Closed' THEN 1 ELSE 0 END) AS Total_logs_closed_during_the_reporting_period",

				"ROUND(AVG(
				CASE 
				WHEN fcv.value = 'Closed' 
				THEN GREATEST(DATEDIFF(a.modified_date, a.created_date), 1)
				ELSE GREATEST(DATEDIFF(LEAST(NOW(), $quotedEndDate), a.created_date), 1)
				END
			)) AS `Average_lifecycle_duration_initiation_to_resolution_(days)`"
			]);

		// From main UCM data table
			$query->from($db->quoteName('#__tj_ucm_data', 'a'));

		// Join fields value table to fetch the status of logs
			$query->join('LEFT', $db->quoteName('#__tjfields_fields_value', 'fcv') . ' ON ' .
				$db->quoteName('fcv.content_id') . ' = ' . $db->quoteName('a.id') .
				' AND ' . $db->quoteName('fcv.field_id') . ' = ' . $db->quote($requestStatus)
			);

		// Apply filters
			$query->where($db->quoteName('a.client') . " = 'com_tjucm.sarlog'");
			$query->where($db->quoteName('a.state') . " = 1")->where($db->quoteName('a.created_date') . " BETWEEN $quotedStartDate AND $quotedEndDate");
			$query->where($db->quoteName('a.draft') . " = 0");

		// Ensure only closed logs are considered for average time calculation
			// $query->where("fcv.value = 'Closed'");

		// Filter by cluster if provided
			if (!empty($filters['cluster_id'])) {
				if (is_array($filters['cluster_id'])) {
					$query->where($db->quoteName('a.cluster_id') . " IN (" . implode(',', array_map([$db, 'quote'], $filters['cluster_id'])) . ")");
				} else {
					$query->where($db->quoteName('a.cluster_id') . ' = ' . (int) $filters['cluster_id']);
				}
			}

			$db->setQuery($query);
			$sarData = $db->loadObject();

			if (
				$sarData->Existing_logs_open_at_the_start_of_the_reporting_period == 0 &&
				$sarData->Number_Of_Logs_Closed_During_The_Period == 0 &&
				$sarData->New_logs_created_during_the_reporting_period == 0 &&
				$sarData->Total_logs_closed_during_the_reporting_period == 0
			) {
				$sarData->{'Average_lifecycle_duration_initiation_to_resolution_(days)'} = 0;

			}

		}
		catch (Exception $e)
		{
			throw new Exception($e->getMessage());
		}

		return $sarData;
	}


	/**
	 * Function to get data of foi log
	 *
	 * @return Array data.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function getFoiLogData($data)
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
			$query = $db->getQuery(true);
			$filters = $data;

			if ($filters['date_range'])
			{
				$endDate = new DateTime();

				// Set the start date as 3 months before today
				$startDate = new DateTime();
				$startDate->modify('-'.$filters['date_range'].' months'); // Subtract  months from today's date

				// Format the dates as 'Y-m-d' (you can change the format as needed)
				$startDate  = $startDate->format('Y-m-d');
				$endDate    = $endDate->format('Y-m-d');
			}
			else
			{
				$startDate = $filters['start_date'];
				if(!$filters['end_date'])
				{
					$endDate = new DateTime();
				}else
				{
					$endDate   = $filters['end_date'];
				}
			}
			$today = (new DateTime())->format('Y-m-d');

			if ($filters['end_date'] === $today || empty($filters['end_date'])) {
			    $endDate = $today . ' 23:59:59'; // Include today fully
			}else
			{
				$endDate = $endDate . ' 23:59:59'; 
			}

			$quotedStartDate = $db->quote($startDate);
			$quotedEndDate   = $db->quote($endDate);

			$query = $db->getQuery(true);

		// Select required statistics
			$query->select([
				"SUM(CASE WHEN a.created_date < $quotedStartDate AND (a.modified_date IS NULL OR a.modified_date >= $quotedStartDate) THEN 1 ELSE 0 END) AS Existing_logs_open_at_the_start_of_the_reporting_period",
				" SUM(CASE WHEN a.created_date BETWEEN $quotedStartDate AND $quotedEndDate AND a.modified_date BETWEEN $quotedStartDate AND $quotedEndDate AND fcv.value = 'Closed' THEN 1 ELSE 0 END) AS Number_Of_logs_closed_during_the_period",
				"SUM(CASE WHEN a.created_date BETWEEN $quotedStartDate AND $quotedEndDate THEN 1 ELSE 0 END) AS New_logs_created_during_the_reporting_period
				",
				" SUM(CASE WHEN a.modified_date BETWEEN $quotedStartDate AND $quotedEndDate AND fcv.value = 'Closed' THEN 1 ELSE 0 END)  AS Total_logs_closed_during_the_reporting_period",
				"ROUND(AVG(
				CASE 
				WHEN fcv.value = 'Closed' 
				THEN GREATEST(DATEDIFF(a.modified_date, a.created_date), 1)
				ELSE GREATEST(DATEDIFF(LEAST(NOW(), $quotedEndDate), a.created_date), 1)
				END
			)) AS `Average_lifecycle_duration_initiation_to_resolution_(days)`
				"
			]);

		// From main UCM data table
			$query->from($db->quoteName('#__tj_ucm_data', 'a'));

		// Join fields value table to fetch the status of logs
			$query->join('LEFT', $db->quoteName('#__tjfields_fields_value', 'fcv') . ' ON ' .
				$db->quoteName('fcv.content_id') . ' = ' . $db->quoteName('a.id') .
				' AND ' . $db->quoteName('fcv.field_id') . ' = ' . $db->quote($requestStatus)
			);

		// Apply filters
			$query->where($db->quoteName('a.client') . " = 'com_tjucm.foilog'");
			$query->where($db->quoteName('a.state') . " = 1")->where($db->quoteName('a.created_date') . " BETWEEN $quotedStartDate AND $quotedEndDate");
			$query->where($db->quoteName('a.draft') . " = 0");

		// Ensure only closed logs are considered for average time calculation
			//$query->where("fcv.value = 'Closed'");

		// Filter by cluster if provided
			if (!empty($filters['cluster_id'])) 
			{
				if (is_array($filters['cluster_id'])) {
					$query->where($db->quoteName('a.cluster_id') . " IN (" . implode(',', array_map([$db, 'quote'], $filters['cluster_id'])) . ")");
				} else {
					$query->where($db->quoteName('a.cluster_id') . ' = ' . (int) $filters['cluster_id']);
				}
			}

			$db->setQuery($query);

			$foiData = $db->loadAssoc();
			if (
				$foiData['Existing_logs_open_at_the_start_of_the_reporting_period'] == 0 &&
				$foiData['Number_Of_Logs_Closed_During_The_Period'] == 0 &&
				$foiData['New_logs_created_during_the_reporting_period'] == 0 &&
				$foiData['Total_logs_closed_during_the_reporting_period'] == 0
			) {
				$foiData['Average_lifecycle_duration_initiation_to_resolution_(days)'] = 0;
			}

		}
		catch (Exception $e)
		{
			throw new Exception($e->getMessage());
		}

		return $foiData;
	}

	/**
	 * Function to get data of rop
	 *
	 * @return Array data.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function getRopData($data)
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
			$ropNextReview  = (int) $params->get('ropNextReviewDate', '0');
			$filters = $data;

			if ($filters['date_range'])
			{
				$endDate = new DateTime();

				// Set the start date as 3 months before today
				$startDate = new DateTime();
				$startDate->modify('-'.$filters['date_range'].' months'); // Subtract  months from today's date

				// Format the dates as 'Y-m-d' (you can change the format as needed)
				$startDate  = $startDate->format('Y-m-d');
				$endDate    = $endDate->format('Y-m-d');
			}
			else
			{
				$startDate = $filters['start_date'];
				if(!$filters['end_date'])
				{
					$endDate = new DateTime();
				}else
				{
					$endDate   = $filters['end_date'];
				}
			}
			
			if (!is_array($filters)) 
			{
		    $filters = []; // Ensure $filters is an array before assigning values
		}

		$query = $db->getQuery(true);
		$query->select([
			'COUNT(a.id) AS Total_number_of_processes_listed',
			'SUM(CASE WHEN fcv.value = "In progress" THEN 1 ELSE 0 END) AS In_Progress',
			'SUM(CASE WHEN fcv.value = "In Progress - DPO Review" THEN 1 ELSE 0 END) AS Dpo_Review_count',
			'SUM(CASE WHEN fcv.value = "Complete - Validated" THEN 1 ELSE 0 END) AS complete',
			'SUM(CASE WHEN fcv_review.value BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 MONTH) THEN 1 ELSE 0 END) AS Number_within_3_months_of_the_review_date',
			'SUM(CASE WHEN fcv_review.value < CURDATE() THEN 1 ELSE 0 END) AS Number_past_review_rate'
		])
		->from($db->quoteName('#__tj_ucm_data', 'a'))
		->leftJoin($db->quoteName('#__tjfields_fields_value', 'fcv') . 
			' ON ' . $db->quoteName('fcv.content_id') . ' = ' . $db->quoteName('a.id') . 
			' AND ' . $db->quoteName('fcv.field_id') . ' =  '.'"'.$requestStatus.'"')
		->leftJoin($db->quoteName('#__tjfields_fields_value', 'fcv_review') . 
			' ON ' . $db->quoteName('fcv_review.content_id') . ' = ' . $db->quoteName('a.id') . 
			' AND ' . $db->quoteName('fcv_review.field_id') . ' = '.'"'.$ropNextReview.'"');
		$today = (new DateTime())->format('Y-m-d');

		if ($filters['end_date'] === $today || empty($filters['end_date'])) {
			    $endDate = $today . ' 23:59:59'; // Include today fully
			} else {
				$endDate = $endDate. ' 23:59:59';
			}

			$query->where($db->quoteName('a.created_date') . ' BETWEEN ' . $db->quote($startDate) . ' AND ' . $db->quote($endDate));
			$query->where($db->qn('a.client') . " = 'com_tjucm.rop'");

			if (!empty($filters['cluster_id'])) {
				if (is_array($filters['cluster_id'])) {
					$query->where($db->quoteName('a.cluster_id') . " IN (" . implode(',', array_map([$db, 'quote'], $filters['cluster_id'])) . ")");
				} else {
					$query->where($db->quoteName('a.cluster_id') . ' = ' . (int) $filters['cluster_id']);
				}
			}

			$query->where([
				$db->quoteName('a.draft') . ' = 0'
			]);

			// Set the query and get results
			$db->setQuery($query);
			$ropReports = $db->loadObject();

			$statusKeys = ['In_Progress', 'Dpo_Review_count', 'complete'];

			$ropReport = new stdClass();
		$status = new stdClass(); // Creating the status object

		foreach ($ropReports as $key => $value) {
			if (in_array($key, $statusKeys)) {
		        $status->$key = $value; // Add to status group
		    } else {
		        $ropReport->$key = $value; // Keep other keys at the top level
		    }
		}

// Append status at the end
		$ropReport->Status = $status;
	}
	catch (Exception $e)
	{
		throw new Exception($e->getMessage());
	}

	return $ropReport;
}

	/**
	 * Function to get data of thirdparty
	 *
	 * @return Array data.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function getThirdParty($data)
	{
		try
		{
			$db             = Factory::getDbo();
			$app            = Factory::getApplication();
			$input          = $app->input;
			$user           = Factory::getUser();
			$clusterIds     = array();
			$params         = DPE::config();
			$riskLevel  	= (int) $params->get('thirdpartyRiskLevel', '0');
			$contractName   = (int) $params->get('thirdpartyContract', '0');
			$filters = $data;

			if ($filters['date_range'])
			{
				$endDate = new DateTime();

				// Set the start date as 3 months before today
				$startDate = new DateTime();
				$startDate->modify('-'.$filters['date_range'].' months'); // Subtract  months from today's date

				// Format the dates as 'Y-m-d' (you can change the format as needed)
				$startDate  = $startDate->format('Y-m-d');
				$endDate    = $endDate->format('Y-m-d');
			}
			else
			{
				$startDate = $filters['start_date'];
				if(!$filters['end_date'])
				{
					$endDate = new DateTime();
				}else
				{
					$endDate   = $filters['end_date'];
				}
			}

			$query = $db->getQuery(true);

			$query->select($db->quoteName('a.id', 'Number_Of_Thirdparty'))
			->select($db->quoteName('fcv.value', 'risk_level'))
			->select('(SELECT ' . $db->quoteName('fcv_review.value') . ' 
				FROM ' . $db->quoteName('#__tjfields_fields_value') . ' AS fcv_review 
				WHERE ' . $db->quoteName('fcv_review.content_id') . ' = vendor_contracts.id 
				AND ' . $db->quoteName('fcv_review.field_id') . ' = "' .$contractName . '" 
				LIMIT 1) AS contractName');

			// From clause
			$query->from($db->quoteName('#__tj_ucm_data', 'a'));

			// Left joins
			$query->leftJoin($db->quoteName('#__tjfields_fields_value', 'fcv') . ' ON ' . 
				$db->quoteName('fcv.content_id') . ' = ' . $db->quoteName('a.id') . ' AND ' . 
				$db->quoteName('fcv.field_id') . ' = "'.$riskLevel.'"');

			$query->leftJoin($db->quoteName('#__tj_ucm_data', 'vendor_contracts') . ' ON ' . 
				$db->quoteName('vendor_contracts.parent_id') . ' = ' . $db->quoteName('a.id') . ' AND ' . 
				$db->quoteName('vendor_contracts.client') . ' = ' . $db->quote('com_tjucm.ropvendorcontracts'));

			// Where clause
			$today = (new DateTime())->format('Y-m-d');

			if ($filters['end_date'] === $today || empty($filters['end_date'])) {
			     $endDate = $db->quote($today . ' 23:59:59'); // Include today fully
			 } else {
			 	$endDate = $db->quote($endDate. ' 23:59:59');
			 }
			 $query->where($db->quoteName('a.created_date') . ' BETWEEN ' . $db->quote($startDate) . ' AND ' . $endDate)->where($db->quoteName('a.client') . ' = ' . $db->quote('com_tjucm.ropvendors'));
			 if (!empty($filters['cluster_id'])) {
			 	if (is_array($filters['cluster_id'])) {
			 		$query->where($db->quoteName('a.cluster_id') . " IN (" . implode(',', array_map([$db, 'quote'], $filters['cluster_id'])) . ")");
			 	} else {
			 		$query->where($db->quoteName('a.cluster_id') . ' = ' . (int) $filters['cluster_id']);
			 	}
			 }

			 $query->where($db->quoteName('a.draft') . ' = ' . $db->quote(0));

				// Set the query
			 $db->setQuery($query);
				// Load the results
			 $thirdPartyDatas = $db->loadObjectList();

			 $query = $db->getQuery(true);
			 $query->select('value');
			 $query->from('`#__tjfields_options`');
			 $query->where($db->quoteName('field_id') . '= ' . (int) $riskLevel);
			 $db->setQuery($query);
			 $riskLevelValue = $db->loadColumn();
			 $riskCount = array_fill_keys($riskLevelValue, 0);

			// Count occurrences of each risk level
			 foreach ($thirdPartyDatas as $record) {
			 	if (!empty($record->risk_level) && isset($riskCount[$record->risk_level])) {
			 		$riskCount[$record->risk_level]++;
			 	}
			 	if (!empty($record->contractName)) {
			 		if (!isset($contractCount[$record->contractName])) {
			 			$contractCount[$record->contractName] = 0;
			 		}
			 		$contractCount[$record->contractName]++;
			 	}
			 }

			 $thirdParty['Number_of_third_party'] = count($thirdPartyDatas);
			 
			 $thirdParty['Number_of_third_parties_with_contracts'] = ($contractCount)?count($contractCount):"0";
			 $thirdParty['Number_by_risk_level'] = $riskCount;

			}
			catch (Exception $e)
			{
				throw new Exception($e->getMessage());
			}

			return $thirdParty;
		}
		

	/**
	 * Function to get data of checklist
	 *
	 * @return Array data.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function getChecklistData($data)
	{
		try
		{
			
			$app        = Factory::getApplication();
			$input      = $app->input;
			$user       = Factory::getUser();

			$filters = $data;
			$clusterIds = $filters['cluster_id'];

			if ($filters['date_range'])
			{
				$endDate = new DateTime();

				// Set the start date as 3 months before today
				$startDate = new DateTime();
				$startDate->modify('-'.$filters['date_range'].' months'); // Subtract  months from today's date

				// Format the dates as 'Y-m-d' (you can change the format as needed)
				$startDate  = $startDate->format('Y-m-d');
				$endDate    = $endDate->format('Y-m-d');
			}
			else
			{
				$startDate = $filters['start_date'];
				if(!$filters['end_date'])
				{
					$endDate = new DateTime();
				}else
				{
					$endDate   = $filters['end_date'];
				}
			}
			$today = (new DateTime())->format('Y-m-d');

			if ($filters['end_date'] === $today || empty($filters['end_date'])) {
			    $endDate = $today . ' 23:59:59'; // Include today fully
			}else
			{
				$endDate = $endDate . ' 23:59:59'; 
			}
			$checklistItems = array();
			$barData = array();
			$agregateClusterData = array();

			foreach ($clusterIds as $key => $clusterId) {
				$db = Factory::getDbo();
				$subQuery = $db->getQuery(true);
				$query = $db->getQuery(true);

				$subQuery->select('ucm.id')
				->from($db->quoteName('#__tj_ucm_data', 'ucm'))
				->where($db->quoteName('ucm.cluster_id') . ' = ' . $db->q($clusterId));

				$query->select(array('t.id as type_id', 't.title', 'd.modified_date', 'd.client', 'd.id', 't.unique_identifier'))
				->from($db->quoteName('#__tj_ucm_types', 't'))
				->join('LEFT', $db->quoteName('#__tj_ucm_data', 'd') . ' ON ' . $db->quoteName('d.type_id') . ' = ' . $db->quoteName('t.id')
					. ' AND d.id IN (' . $subQuery . ')')
				->where($db->quoteName('t.params') . ' LIKE "%dpe_checklist=1%"')
				->where($db->quoteName('t.state') . ' = ' . $db->q('1'))
				->group($db->quoteName('t.id'))->order('t.ordering ASC');

				$db->setQuery($query);
				$checklistItems = $db->loadObjectList();

				BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_dpe/models');
				$dashboardModel = BaseDatabaseModel::getInstance('Dashboard', 'DpeModel', array('ignore_request' => true));

		    $barData = []; // Reset for each cluster
		    foreach ($checklistItems as $index => $checklistItem) {
		    	$barData[$index] = $dashboardModel->getBarData($checklistItem->client, $checklistItem->id);
		    	$barData[$index]->title = $checklistItem->title;
		    }

		    Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_cluster/tables');
		    $clusterInstance = Table::getInstance('Clusters', 'ClusterTable');
		    $clusterInstance->load(array('id' => $clusterId));
		    $clusterTitle = $clusterInstance->name;

		    // Ensure the cluster ID is stored **only once** per cluster
		    $clusterData = new stdClass();
		    $clusterData->clusterTitle = $clusterTitle;
		    $clusterData->data = $barData;

		    // Store the structured data
		    $agregateClusterData[$key] = $clusterData;
		}
	}
	catch (Exception $e)
	{
		throw new Exception($e->getMessage());
	}

	return $agregateClusterData;
}


	/**
	 * Function to get data of the whole block
	 *
	 * @return Array data.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function getPhishingData($data)
	{
		try
		{
			// Create a new query object.
			$db  = Factory::getDbo();
			$app = Factory::getApplication();
			$input = $app->input;
			$user  = Factory::getUser();
			$query = $db->getQuery(true);

			$filters = $data;

			if ($filters['date_range'])
			{
				$endDate = new DateTime();

				// Set the start date as 3 months before today
				$startDate = new DateTime();
				$startDate->modify('-'.$filters['date_range'].' months'); // Subtract  months from today's date

				// Format the dates as 'Y-m-d' (you can change the format as needed)
				$startDate  = $startDate->format('Y-m-d');
				$endDate    = $endDate->format('Y-m-d');
			}
			else
			{
				$startDate = $filters['start_date'];
				if(!$filters['end_date'])
				{
					$endDate = new DateTime();
				}else
				{
					$endDate   = $filters['end_date'];
				}
			}
			$today = (new DateTime())->format('Y-m-d');

			if ($filters['end_date'] === $today || empty($filters['end_date'])) {
			    $endDate = $today . ' 23:59:59'; // Include today fully
			}else
			{
				$endDate = $endDate . ' 23:59:59'; 
			}
    // Select count of campaigns and list of campaign titles
			$query->select([
				'COUNT(DISTINCT tjcamp.id) AS campaignscount',
				'GROUP_CONCAT(DISTINCT tjcamp.gophish_campaign_id ORDER BY tjcamp.gophish_campaign_title ASC SEPARATOR ", ") AS campaign_id'
			])
			->from($db->qn('#__tjgophish_campaign_ref', 'tjcamp'))
			->where($db->qn('tjcamp.created_date') . ' BETWEEN ' . $db->q($startDate) . ' AND ' . $db->q($endDate));
			

			if (!empty($filters['cluster_id'])) {
				if (is_array($filters['cluster_id'])) {
					$query->where($db->quoteName('tjcamp.cluster_id') . " IN (" . implode(',', array_map([$db, 'quote'], $filters['cluster_id'])) . ")");
				} else {
					$query->where($db->quoteName('tjcamp.cluster_id') . ' = ' . (int) $filters['cluster_id']);
				}
			}


			$db->setQuery($query);
			$gophishData = $db->loadObject();

			$goPhisCampaigIds = explode(',', $gophishData->campaign_id);
			$params = ComponentHelper::getParams('com_tjgophish');
			$goPhishApiEnd = $params->get('api_base_url');
			$goPhishApiKey = $params->get('api_key');
			$goPhisCampaingReport = array();


			foreach($goPhisCampaigIds as $key => $goPhisCampaigId)
			{
				if ($goPhisCampaigId)
				{
					$Http = new Http;

					$url = $goPhishApiEnd . 'api/campaigns/' . $goPhisCampaigId . '/summary?api_key=' . $goPhishApiKey;
					$response = $Http->get(str_replace(' ', '', $url));
					$goPhisCampaingReport[$key] = json_decode($response->body);
				}
			}
		}
		catch (Exception $e)
		{
			throw new Exception($e->getMessage());
		}

		$goPhisCampaingReport['campaignscount'] = $gophishData->campaignscount;

		return $goPhisCampaingReport;
	}

	
	/**
	 * Get Data for users
	 *
	 * @return string dataArray
	 *
	 * @since   __DEPLOY_VERSION__
	 * */

	public function getUserData($data)
	{
		try
		{
			$db            = Factory::getDbo();
			$params        = DPE::config();
			$filters = $data;

			BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_multiagency/models');
			$MultiagencyModel = BaseDatabaseModel::getInstance('Multiagency', 'MultiagencyModel', array('ignore_request' => true));
			$params = ComponentHelper::getParams('com_multiagency');
			$staffRole = $params->get('member_role_id', '0', 'INT');
			$adminrole = $params->get('school_admin_role_id', '0', 'INT');

			$query = $db->getQuery(true);


				// Ensure cluster IDs are integers
			$clusterIds = array_map('intval', (array) $clusterIds);
			$clusterIdsList = implode(',', $clusterIds);


				// Select required fields
			$query->select([
				$db->qn('cluster.name') . ' AS Organisation',
				'COUNT(DISTINCT CASE WHEN ' . $db->qn('b.role_id') . ' = '.$staffRole.' THEN ' . $db->qn('a.id') . ' END) AS Staff_users',
				'COUNT(DISTINCT CASE WHEN ' . $db->qn('b.role_id') . ' = '. $adminrole.' THEN ' . $db->qn('a.id') . ' END) AS Admin_users',
				'COUNT(DISTINCT CASE WHEN `job`.`dpelead` = 1 AND `b`.`role_id` =  '. $adminrole.' THEN `a`.`id` END) AS Dpe_leads'
			]);

				// From users table
			$query->from($db->qn('#__users', 'a'));

				// Join related tables
			$query->join('INNER', $db->qn('#__tjsu_users', 'b') . ' ON ' . $db->qn('a.id') . ' = ' . $db->qn('b.user_id') . ' AND ' . $db->qn('b.client') . ' = ' . $db->q('com_multiagency'));
			$query->join('INNER', $db->qn('#__tjmultiagency_multiagency', 'c') . ' ON ' . $db->qn('b.client_id') . ' = ' . $db->qn('c.id'));
			$query->join('INNER', $db->qn('#__tj_clusters', 'cluster') . ' ON ' . $db->qn('c.id') . ' = ' . $db->qn('cluster.client_id'));
			$query->join('INNER', $db->qn('#__tjsu_roles', 'r') . ' ON ' . $db->qn('r.id') . ' = ' . $db->qn('b.role_id') . ' AND ' . $db->qn('r.state') . ' = 1');
			$query->join('LEFT', $db->qn('#__job_title_user_xref', 'job') . ' ON ' . $db->qn('job.user_id') . ' = ' . $db->qn('b.user_id') . ' AND ' . $db->qn('cluster.id') . ' = ' . $db->qn('job.cluster_id'));

				// Add where conditions
			$query->where($db->qn('a.block') . ' = 0');

				// Handle multiple clusters
			if (!empty($filters['cluster_id'])) {
				if (is_array($filters['cluster_id'])) {

					$query->where($db->quoteName('cluster.id') . " IN (" . implode(',', array_map([$db, 'quote'], $filters['cluster_id'])) . ")");
				} else {
					$query->where($db->quoteName('cluster.id') . ' = ' . (int) $filters['cluster_id']);
				}
			}
				// Group by cluster
			$query->group($db->qn('cluster.id'));

				// Set the query and get results
			$db->setQuery($query);
			$results = $db->loadObjectList();

				// Output results
			return $results;

		}
		catch (Exception $e)
		{
			throw new Exception($e->getMessage());
		}
	}

	/**
	 * Function to get data of Course Data 
	 *
	 * @return Array data.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function getCourseReport($data)
	{

		if (empty($data)) {
			return false;
		}

		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		$filters = $data;

// Handle date range
		if (!empty($filters['date_range'])) {
			$endDate = new DateTime();
			$startDate = new DateTime();
			$startDate->modify('-' . (int)$filters['date_range'] . ' months');
		} else {
			$startDate = new DateTime($filters['start_date']);
			$endDate = new DateTime($filters['end_date']);
		}

// Format dates for SQL
		$startDateSql = $db->quote($startDate->format('Y-m-d'));
		$endDateSql = $db->quote($endDate->format('Y-m-d') . ' 23:59:59');

// Build main query
		$query->select([
			'c.id AS course_id',
			'c.title AS Course_title',
			'COUNT(DISTINCT eu.user_id) AS Number_of_users_enrolled_per_course',
			'COUNT(DISTINCT CASE WHEN ct.status = "C" THEN ct.user_id END) AS Total_completed',
			'CONCAT(ROUND((COUNT(DISTINCT CASE WHEN ct.status = "C" THEN ct.user_id END) / COUNT(DISTINCT eu.user_id)) * 100), "%" ) AS Completion_percentage',
			'CONCAT("Enrolled ", COUNT(DISTINCT eu.user_id), "/", COUNT(DISTINCT eu.user_id), ", Completed ", COUNT(DISTINCT CASE WHEN ct.status = "C" THEN ct.user_id END), "/", COUNT(DISTINCT eu.user_id)) AS Course_status'
		])
		->from($db->quoteName('#__tjlms_courses', 'c'))
		->join('INNER', $db->quoteName('#__tjlms_enrolled_users', 'eu') . ' ON eu.course_id = c.id')
		->join('RIGHT', $db->quoteName('#__tjlms_course_track', 'ct') . ' ON ct.course_id = c.id AND ct.user_id = eu.user_id')
		->join('INNER', $db->quoteName('#__tj_cluster_nodes', 'tjcn') . ' ON eu.user_id = tjcn.user_id')
		->join('INNER', $db->quoteName('#__tj_clusters', 'tjc') . ' ON tjcn.cluster_id = tjc.id');

// Filter by cluster
		if (!empty($filters['cluster_id'])) {
			if (is_array($filters['cluster_id'])) {
				$query->where($db->quoteName('tjc.id') . " IN (" . implode(',', array_map([$db, 'quote'], $filters['cluster_id'])) . ")");
			} else {
				$query->where($db->quoteName('tjc.id') . ' = ' . (int) $filters['cluster_id']);
			}
		}

// Filter by enrollment date range
		$query->where($db->quoteName('eu.enrolled_on_time') . ' BETWEEN ' . $startDateSql . ' AND ' . $endDateSql);

		$query->group('c.id')->order('course_id ASC');

		$db->setQuery($query);
		$courseResults = $db->loadObjectList();

// Remove course_id from result output
		$courseResults = array_map(function ($obj) {
			unset($obj->course_id);
			return $obj;
		}, $courseResults);

// Aggregate statistics
		$courseReport['courseResult'] = $courseResults;

		$totalCoursesAssigned = count($courseResults);
		$totalUsersAssigned = 0;
		$totalUsersCompleted = 0;

		foreach ($courseResults as $course) {
			$totalUsersAssigned += (int)$course->Number_of_users_enrolled_per_course;
			$totalUsersCompleted += (int)$course->Total_completed;
		}

		$totalCompletionPercentage = ($totalUsersAssigned > 0)
		? round(($totalUsersCompleted / $totalUsersAssigned) * 100, 2)
		: 0;

		$courseReport['Total_courses_Assigned'] = $totalCoursesAssigned;
		$courseReport['Total_completion_percentage'] = $totalCompletionPercentage;
		$courseReport['Total_users_assigned'] = $totalUsersAssigned;

		return $courseReport;

	}

	/**
	 * Function to get data of Makingthe rounds 
	 *
	 * @return Array data.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function getAccountabilityData($data)
	{
		try
		{
			$db = Factory::getDbo();
			$app = Factory::getApplication();
			$input = $app->input;
			$statusField = $data['statusFiled'];
			$filters = $data;

			if ($filters['date_range'])
			{
				$endDate = new DateTime();

				// Set the start date as months before today
				$startDate = new DateTime();
				$startDate->modify('-'.$filters['date_range'].' months'); // Subtract  months from today's date

				// Format the dates as 'Y-m-d' (you can change the format as needed)
				$startDate  = $startDate->format('Y-m-d');
				$endDate    = $endDate->format('Y-m-d');
			}
			else
			{
				$startDate = $filters['start_date'];
				if(!$filters['end_date'])
				{
					$endDate = new DateTime();
				}else
				{
					$endDate   = $filters['end_date'];
				}
			}
			$today = (new DateTime())->format('Y-m-d');

			if ($filters['end_date'] === $today || empty($filters['end_date'])) {
			    $endDate = $today . ' 23:59:59'; // Include today fully
			}
			else
			{
				$endDate = $endDate . ' 23:59:59';
			}
			$query = $db->getQuery(true);

			// Select required fields
			$query->select(['tjc.name as Organisation','DATE_FORMAT(a.created_date, "%d-%m-%Y") AS Log_Date',
				"MAX(CASE WHEN fcv.field_id = " . $db->quote($statusField) . " THEN fcv.value END) AS Status"
			]);

			// From main UCM data table
			$query->from($db->quoteName('#__tj_ucm_data', 'a'));

			// Join the fields table once to get all specific field values
			$query->join(
				'LEFT',
				$db->quoteName('#__tjfields_fields_value', 'fcv') . 
				' ON ' . $db->quoteName('fcv.content_id') . ' = ' . $db->quoteName('a.id'));
			$query->join('LEFT', $db->quoteName('#__tj_clusters', 'tjc') . ' ON a.cluster_id = tjc.id');

			// Apply filters
			$query->where($db->quoteName('a.client') . ' = ' . $db->quote($data['client']));
			$query->where($db->quoteName('a.state') . " = 1");
			$query->where($db->quoteName('a.draft') . " = 0");

			
		// Filter by date range
			$dateCondition = "(" . $db->quoteName('a.created_date') . " BETWEEN " . $db->quote($startDate) . " AND " . $db->quote($endDate) . ")";

			if (!empty($filters['date_range'])) {

				$dateCondition .= " OR (" . $db->quoteName('a.created_date') . " > " . $db->quote($startDate) . " AND " . $db->quoteName('a.created_date') . " < NOW())";
			}
			$query->where('(' . $dateCondition . ')');

			// Filter by cluster if provided
			if (!empty($filters['cluster_id'])) {
				if (is_array($filters['cluster_id'])) {
					$query->where($db->quoteName('a.cluster_id') . " IN (" . implode(',', array_map([$db, 'quote'], $filters['cluster_id'])) . ")");
				} else {
					$query->where($db->quoteName('a.cluster_id') . ' = ' . (int) $filters['cluster_id']);
				}
			}

			$query->group($db->quoteName('a.id'));
			$db->setQuery($query);
			$accountabilityData = $db->loadObjectList();
		}
		catch (Exception $e)
		{
			throw new Exception($e->getMessage());
		}


		foreach ($accountabilityData as $key => $status)
		{
			$statusValue = $status->Status ?? '';
			$class = '';

			switch ($statusValue) {
				case 'Not Applicable':
				$class = 'danger';
				break;

				case 'Not Meeting Expectation':
				$class = 'warning';
				break;

				case 'Partially Meeting Expectation':
				$class = 'info';
				break;

				case 'Fully Meeting Expectation':
				$class = 'custom-message';
				break;

				default:
            $class = ''; // or 'default-class' if you want a fallback
        }

        if (!empty($statusValue)) {
        	$accountabilityData[$key]->Status = "<p class='$class'>$statusValue</p>";
        } else {
	        $accountabilityData[$key]->Status = "<p>-</p>"; // or leave as-is
	    }
	}

	return $accountabilityData;
}

	/**
	 * Function to get data of Todos 
	 *
	 * @return Array data.
	 *
	 * @since __DEPLOY_VERSION__
	 */

	public function getTododData($data)
	{
		try
		{
				// Create a new query object.
			$db  = Factory::getDbo();
			$query = $db->getQuery(true);

			$filters = $data;

			if (!empty($filters['date_range'])) {
				$endDate = new DateTime();
				$startDate = new DateTime();
				$startDate->modify('-' . (int)$filters['date_range'] . ' months');

				$startDate = $db->quote($startDate->format('Y-m-d'));
				$endDate = $db->quote($endDate->format('Y-m-d'). ' 23:59:59');
			} else {
				$startDate = $db->quote($filters['start_date']);
				$endDate = $db->quote($filters['end_date']. ' 23:59:59');
			}

			$today = (new DateTime())->format('Y-m-d');

			if ($filters['end_date'] === $today || empty($filters['end_date'])) {
			    $endDate = $db->quote($today . ' 23:59:59'); // Include today fully
			}


			// Select fields, including count of todos with status 'C'
			$query->select([
				'DISTINCT a.id,a.status'
			])
			->from($db->quoteName('#__jlike_todos', 'a'))
			->leftJoin($db->quoteName('#__jlike_content', 'c') . ' ON ' . $db->quoteName('c.id') . ' = ' . $db->quoteName('a.content_id'))
			->leftJoin($db->quoteName('#__users', 'uc') . ' ON ' . $db->quoteName('uc.id') . ' = ' . $db->quoteName('a.checked_out'))
			->leftJoin($db->quoteName('#__users', 'users') . ' ON ' . $db->quoteName('users.id') . ' = ' . $db->quoteName('a.created_by'))
			->leftJoin($db->quoteName('#__users', 'usa') . ' ON ' . $db->quoteName('usa.id') . ' = ' . $db->quoteName('a.assigned_to'))
			->leftJoin($db->quoteName('#__jlike_todos_cluster_xref', 'todoxref') . ' ON ' . $db->quoteName('todoxref.todo_id') . ' = ' . $db->quoteName('a.id'))
			->leftJoin($db->quoteName('#__tj_clusters', 'clusters') . ' ON ' . $db->quoteName('clusters.id') . ' = ' . $db->quoteName('todoxref.cluster_id'))
			->leftJoin($db->quoteName('#__tjmultiagency_multiagency', 'tm') . ' ON ' . $db->quoteName('tm.id') . ' = ' . $db->quoteName('clusters.client_id'));

			// Build the WHERE conditions
			$where = [];
			$where[] = $db->quoteName('tm.state') . ' = 1';
			$where[] = $db->quoteName('clusters.state') . ' = 1';
			if (!empty($filters['cluster_id'])) {
				if (is_array($filters['cluster_id'])) {
					$query->where($db->quoteName('clusters.id') . " IN (" . implode(',', array_map([$db, 'quote'], $filters['cluster_id'])) . ")");
				} else {
					$query->where($db->quoteName('clusters.id') . ' = ' . (int) $filters['cluster_id']);
				}
			}


			// Date condition: either created_date is within range OR is NULL
			$dateCondition = '(' .
			'(' . $db->quoteName('a.created_date') . ' >= ' . $startDate . ' AND ' .
			$db->quoteName('a.created_date') . ' <= ' . $endDate . ')' .
			' OR ' . $db->quoteName('a.created_date') . ' IS NULL' .
			')';
			$where[] = $dateCondition;

			$query->where(implode(' AND ', $where));

			// Set the query and load the results
			$db->setQuery($query);

			$results = $db->loadObjectList();

			$todoData['Number_of_to-dos_issued'] = count($results);
			$completedTodoData = 0;

			foreach($results as $todo)
			{
				if ($todo->status == 'C')
				{
					$completedTodoData ++;
				}
			}

			$todoData['Number_of_to-dos_completed'] = $completedTodoData;
			$todoData['Completion_Percentage'] = (count($results))?number_format(($completedTodoData/count($results)) * 100, 2):'0';

		}
		catch (Exception $e)
		{
			throw new Exception($e->getMessage());
		}


		return $todoData ;
	}

	/**
	 * Function to get data of Risk register 
	 *
	 * @return Array data.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function getRiskRegister($data)
	{
		try
		{
			$db             = Factory::getDbo();
			$app            = Factory::getApplication();
			$input          = $app->input;
			$user           = Factory::getUser();
			$clusterIds     = array();
			$params         = DPE::config();
			$requestStatus  = (int) $params->get('riskregister', '0');
			$ropNextReview  = (int) $params->get('riskregisterreview', '0');
			$filters = $data;

			if ($filters['date_range'])
			{
				$endDate = new DateTime();

				// Set the start date as 3 months before today
				$startDate = new DateTime();
				$startDate->modify('-'.$filters['date_range'].' months'); // Subtract  months from today's date

				// Format the dates as 'Y-m-d' (you can change the format as needed)
				$startDate  = $startDate->format('Y-m-d');
				$endDate    = $endDate->format('Y-m-d'). ' 23:59:59';
			}
			else
			{
				$startDate = $filters['start_date'];
				$endDate   = $filters['end_date']. ' 23:59:59';
			}
			$today = (new DateTime())->format('Y-m-d');

			if ($filters['end_date'] === $today || empty($filters['end_date'])) {
			    $endDate = $today . ' 23:59:59'; // Include today fully
			}
			else
			{
				$endDate = $endDate . ' 23:59:59';
			}

			$query = $db->getQuery(true);

			$query->select($db->quoteName('a.id', 'Number_Of_Thirdparty'))
			->select($db->quoteName('fcv.value', 'risk_level'))
			->select('(SELECT ' . $db->quoteName('fcv_review.value') . ' 
				FROM ' . $db->quoteName('#__tjfields_fields_value') . ' AS fcv_review 
				WHERE ' . $db->quoteName('fcv_review.content_id') . ' = a.id 
				AND ' . $db->quoteName('fcv_review.field_id') . ' = "' .$ropNextReview . '" 
				LIMIT 1) AS Risk_Review_Date');

			// From clause
			$query->from($db->quoteName('#__tj_ucm_data', 'a'));

			// Left joins
			$query->leftJoin($db->quoteName('#__tjfields_fields_value', 'fcv') . ' ON ' . 
				$db->quoteName('fcv.content_id') . ' = ' . $db->quoteName('a.id') . ' AND ' . 
				$db->quoteName('fcv.field_id') . ' = "'.$requestStatus.'"');


			// Where clause

			$query->where($db->quoteName('a.created_date') . ' BETWEEN ' . $db->quote($startDate) . ' AND ' . $db->quote($endDate))->where($db->quoteName('a.client') . ' = ' . $db->quote('com_tjucm.riskregister'));

			if (!empty($filters['cluster_id'])) {
				if (is_array($filters['cluster_id'])) {
					$query->where($db->quoteName('a.cluster_id') . " IN (" . implode(',', array_map([$db, 'quote'], $filters['cluster_id'])) . ")");
				} else {
					$query->where($db->quoteName('a.cluster_id') . ' = ' . (int) $filters['cluster_id']);
				}
			}

			$query->where($db->quoteName('a.draft') . ' = ' . $db->quote(0));

			$db->setQuery($query);
				// Load the results
			$riskRegisterValue = $db->loadObjectList();

			// Fetch risk levels from the database
			$query = $db->getQuery(true);
			$query->select('options, value');
			$query->from('`#__tjfields_options`');
			$query->where($db->quoteName('field_id') . '= ' . (int) $requestStatus);
			$db->setQuery($query);
			$riskLevels = $db->loadObjectList(); // Fetch data as objects

			// Create a mapping of value to option
			$riskMapping = [];
			foreach ($riskLevels as $risk) {
			    $riskMapping[$risk->value] = $risk->options; // Map value to option
			}

			// Initialize count array
			$riskCount = array_fill_keys(array_values($riskMapping), 0);



			// Count occurrences per risk option
			foreach ($riskRegisterValue as $record) {
				if (!empty($record->risk_level) && isset($riskMapping[$record->risk_level])) {
			        $option = $riskMapping[$record->risk_level]; // Get the option name
			        $riskCount[$option]++; // Increment count
			    }
			}

				// Get current date
			$currentDate = new DateTime();
			$threeMonthsLater = new DateTime();
			$threeMonthsLater->modify('+3 months');

			$contractCount = [];
			$pastReviewDateCount = 0;

				// Count occurrences of each risk level & check review date
			foreach ($riskRegisterValue as $record) {
				if (!empty($record->risk_level) && isset($riskCount[$record->risk_level])) {
					$riskCount[$record->risk_level]++;
				}

				if (!empty($record->Risk_Review_Date)) {
					$reviewDate = new DateTime($record->Risk_Review_Date);

				        // Check if within 3 months
					if ($reviewDate >= $currentDate && $reviewDate <= $threeMonthsLater) {
						$contractCount[] = $record;
					}

				        // Check if past review date
					if ($reviewDate < $currentDate) {
						$pastReviewDateCount++;
					}
				}
			}

				// Assign values
			$riskRegisterValues['Number_of_risk_register'] = count($riskRegisterValue);
			$riskRegisterValues['Risk_register_within 3 months of review date'] = count($contractCount);
			$riskRegisterValues['Risk_register_review_date'] = $pastReviewDateCount;
			$riskRegisterValues['Risk_register_by_risk_level'] = $riskCount;
		}
		catch (Exception $e)
		{
			throw new Exception($e->getMessage());
		}

		return $riskRegisterValues;
	}


	/**
	 * Function to get data of DPIA Lite 
	 *
	 * @return Array data.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function getDpiaLite($data)
	{
		
		try
		{
			$db             = Factory::getDbo();
			$params         = DPE::config();
			$dpialiteStatus = (int) $params->get('dpialiteAnnual', '0');
			$filters = $data;

			if (!empty($filters['date_range'])) {
				$endDate = new DateTime();
				$startDate = new DateTime();
				$startDate->modify('-' . (int)$filters['date_range'] . ' months');

				$startDate = $db->quote($startDate->format('Y-m-d'));
				$endDate = $endDate->format('Y-m-d');
			} else {
				$startDate = $db->quote($filters['start_date']);
				$endDate = $filters['end_date'];
			}

			$dpiaLiteFieldsArray = $params->get('dpialiteFields', []);

			$fields = [];
			if (!empty($dpiaLiteFieldsArray)) {
				$dpiaLiteFields = implode(',', array_map('intval', $dpiaLiteFieldsArray));
				$FeildTittlequery = $db->getQuery(true)
					->select(['id', 'title', 'type', 'params'])
					->from($db->quoteName('#__tjfields_fields'))
					->where('id IN (' . $dpiaLiteFields . ')');
				$db->setQuery($FeildTittlequery);
				$fields = $db->loadAssocList('id');
			}

			$query = $db->getQuery(true);
			$selects = ['DATE_FORMAT(a.created_date, "%d-%m-%Y") AS Log_Date'];
			$fieldMap = []; // To store safeAlias for each field

			if (!empty($dpiaLiteFieldsArray)) {
				foreach ($dpiaLiteFieldsArray as $fieldId) {
					if (!isset($fields[$fieldId])) continue;
					$field = $fields[$fieldId];
					$safeAlias = preg_replace('/[^A-Za-z0-9_]/', '_', str_replace(' ', '_', trim($field['title'])));
					$fieldMap[$fieldId] = $safeAlias;

					if ($field['type'] == 'related' && !empty($field['params'])) {
						$fieldParams = json_decode($field['params']);
						if (!empty($fieldParams->fieldName->fieldName0->fieldIds[0])) {
							$relatedFieldId = (int) $fieldParams->fieldName->fieldName0->fieldIds[0];
							$selects[] = "(SELECT fcv2.value FROM #__tjfields_fields_value AS fcv2 
								WHERE fcv2.content_id = (SELECT fcv1.value FROM #__tjfields_fields_value AS fcv1 
									WHERE fcv1.content_id = a.id AND fcv1.field_id = $fieldId LIMIT 1) 
								AND fcv2.field_id = $relatedFieldId LIMIT 1) AS $safeAlias";
							continue;
						}
					}
					
					$selects[] = "(SELECT fcv.value FROM #__tjfields_fields_value AS fcv 
						WHERE fcv.content_id = a.id AND fcv.field_id = $fieldId LIMIT 1) AS $safeAlias";
				}
			} else {
				// Fallback to original field
				$selects[] = 'fcv.value AS Dpia_Lite_Status';
				$fieldMap['fallback'] = 'Dpia_Lite_Status';
				$query->join('LEFT', $db->quoteName('#__tjfields_fields_value', 'fcv') . ' ON ' .
					$db->quoteName('fcv.content_id') . ' = ' . $db->quoteName('a.id') .
					' AND ' . $db->quoteName('fcv.field_id') . ' = "' . $dpialiteStatus . '"');
			}

			$query->select($selects)->from($db->quoteName('#__tj_ucm_data', 'a'));

			// Where clause
			$today = (new DateTime())->format('Y-m-d');
			if ($filters['end_date'] === $today || empty($filters['end_date'])) {
			    $endDate = $db->quote($today . ' 23:59:59'); // Include today fully
			} else {
				$endDate = $db->quote($endDate . ' 23:59:59');
			}
			$query->where($db->quoteName('a.created_date') . ' BETWEEN ' . $startDate . ' AND ' . $endDate)
				->where($db->quoteName('a.client') . ' = ' . $db->quote('com_tjucm.dpialite'))
				->where($db->quoteName('a.draft') . ' = ' . $db->quote(0));

			if (!empty($filters['cluster_id'])) {
				if (is_array($filters['cluster_id'])) {
					$query->where($db->quoteName('a.cluster_id') . " IN (" . implode(',', array_map([$db, 'quote'], $filters['cluster_id'])) . ")");
				} else {
					$query->where($db->quoteName('a.cluster_id') . ' = ' . (int) $filters['cluster_id']);
				}
			}

			$query->order($db->quoteName('a.created_date') . ' DESC');

			// Set the query
			$db->setQuery($query);
			$dpiaLites  = $db->loadAssocList();
			
			$dpiaLite['Total_Count_Of_DPIA_Lite'] = count($dpiaLites);

			// Initialize transposed array
			$dpiaLitesArray = ["Log_Date" => []];
			foreach ($fieldMap as $alias) {
				$dpiaLitesArray[$alias] = [];
			}

			// Loop through original data and populate the transformed array
			foreach ($dpiaLites as $entry) {
				$dpiaLitesArray["Log_Date"][] = $entry["Log_Date"];
				foreach ($fieldMap as $alias) {
					$dpiaLitesArray[$alias][] = $entry[$alias] ?? 'N/A';
				}
			}

			$dpiaLite['Dpia_Lite_Data'] = $dpiaLitesArray;
		}
		catch (Exception $e)
		{
			throw new Exception($e->getMessage());
		}

		return $dpiaLite;

	}

	/**
	 * Function to get data of SLA Report 
	 *
	 * @return Array data.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function getDpoSlaData($data)
	{
		try
		{
			$db             = Factory::getDbo();
			$app            = Factory::getApplication();
			$input          = $app->input;
			$params         = DPE::config();
			$filters = $data;

			if (!empty($filters['date_range'])) {
				$endDate = new DateTime();
				$startDate = new DateTime();
				$startDate->modify('-' . (int)$filters['date_range'] . ' months');

				$startDate = $db->quote($startDate->format('Y-m-d'));
				$endDate   = $db->quote($endDate->format('Y-m-d') . ' 23:59:59');
			} else {
				$startDate = $db->quote($filters['start_date']);
				$endDate = $db->quote($filters['end_date']. ' 23:59:59');
			}
			$today = (new DateTime())->format('Y-m-d');

			if ($filters['end_date'] === $today || empty($filters['end_date'])) {
			    $endDate = $db->quote($today . ' 23:59:59'); // Include today fully
			}

			$query = $db->getQuery(true)
			->select([
				$db->quoteName('sa.id'),
				$db->quoteName('cl.name', 'Organisation'),
				$db->quoteName('todo.title', 'Activity_Name'),
				$db->quoteName('todo.due_date', 'Date'),
				$db->quoteName('sat.title', 'Activity_Type'),
				$db->quoteName('sa.license_id', 'License_Id')
				
			])
			->from($db->quoteName('#__tj_sla_activities', 'sa'))
			->innerJoin($db->quoteName('#__tj_sla_activity_types', 'sat') . ' ON ' . $db->quoteName('sat.id') . ' = ' . $db->quoteName('sa.sla_activity_type_id'))
			->innerJoin($db->quoteName('#__tj_clusters', 'cl') . ' ON ' . $db->quoteName('sa.cluster_id') . ' = ' . $db->quoteName('cl.id'))
			->innerJoin($db->quoteName('#__jlike_todos', 'todo') . ' ON ' . $db->quoteName('todo.id') . ' = ' . $db->quoteName('sa.todo_id'))
			->leftJoin($db->quoteName('#__users', 'users') . ' ON ' . $db->quoteName('todo.assigned_to') . ' = ' . $db->quoteName('users.id'))
			->where('(' . $db->quoteName('todo.due_date') . ' BETWEEN ' . $startDate . ' AND ' . $endDate .
				' OR ' . $db->quoteName('todo.due_date') . ' IS NULL' .
				' OR ' . $db->quoteName('todo.due_date') . ' = ' . $db->quote('0000-00-00 00:00:00') . ')');
			
			if (!empty($filters['cluster_id'])) {
				if (is_array($filters['cluster_id'])) {
					$query->where($db->quoteName('sa.cluster_id') . " IN (" . implode(',', array_map([$db, 'quote'], $filters['cluster_id'])) . ")");
				} else {
					$query->where($db->quoteName('sa.cluster_id') . ' = ' . (int) $filters['cluster_id']);
				}
			}
			$query->where($db->quoteName('sa.state') . ' IN (1,2)');
			$query->order($db->quoteName('cl.name') . ' ASC');
			$query->order($db->quoteName('todo.title') . ' ASC');
			$query->order($db->quoteName('todo.due_date') . ' DESC');
			$db->setQuery($query);
			$results = $db->loadObjectList();

			foreach ($results as $key => $item)
			{	
				$results[$key]->Date = ($item->Date == '0000-00-00 00:00:00')?'N/A':date("d-m-Y", strtotime($item->Date));
				$results[$key]->SpentTime = $this->getSpentTime($item->id,$startDate,$endDate);
				unset($item->id);

				Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_multiagency/tables');
				$licenceTable = Table::getInstance('Licence', 'MultiagencyTable');

				// If Due Date is invalid or empty
				if ($item->Date == '0000-00-00 00:00:00' || $item->Date == 'N/A' || empty($item->Date) || is_null($item->Date)) 
				{
					// Load Licence record by License_Id
					if ($licenceTable->load($item->License_Id))
					{
						// If only active license otherwise remove the record
						if ($licenceTable->state != 1)
						{
							unset($results[$key]);
						}
					}
				}
				// Remove License_Id field from the final output
    			unset($results[$key]->License_Id);
			}
			$filteredResults = array_filter($results, function ($item) {
				return !empty($item->SpentTime); // Keep only if SpentTime is not empty
			});

				// Optionally reindex the array
			$results = array_values($filteredResults);
			
		}
		catch (Exception $e)
		{
			throw new Exception($e->getMessage());
		}
		return $results;
	}
	/**
	 * function to check attachment for sla activity
	 *
	 * @param   int  $slaActivityId  id
	 *
	 * @return	Object
	 *
	 * @since	1.0.0
	 */
	private function getSpentTime($slaActivityId,$startDate,$endDate)
	{
		if ($slaActivityId)
		{
			$db    = $this->getDbo();
			$query = $db->getQuery(true);
			$query->select('TIME_FORMAT(SEC_TO_TIME(SUM(TIME_TO_SEC(timelog))), "%Hhr %imin" )');
			$query->from($db->quoteName('#__timelog_activities', 'tl'));
			$query->where($db->quoteName('tl.client_id') . ' = ' . $db->quote($slaActivityId));

			// Apply date range if both are provided (no time portion added)
			if (!empty($startDate) && !empty($endDate))
			{
				if (!empty($startDate) && !empty($endDate))
				{
					$query->where('(' . $db->quoteName('tl.created_date') . ' BETWEEN ' . $startDate . ' AND ' . $endDate .')');
				}
			}
			$db->setQuery($query);

			return $db->loadResult();
		}
	}

	/**
	 * function to check attachment for Making the rounds
	 *
	 * @param   array  $data
	 *
	 * @return	Object
	 *
	 * @since	1.0.0
	 */

	public function getMakingtheroundData($data)
	{
		try {
			$db = Factory::getDbo();
			$params = DPE::config();

    // Get field ID values
			$requestStatus   = (int) $params->get('makingtheRoundsStatus', '0');
			$conductedBy     = (int) $params->get('makingtheRoundsCounductedBy', '0');
			$dpoFeedback     = (int) $params->get('makingtheRoundsDpoFeedback', '0');
			$feedbackStatus  = (int) $params->get('makingtheRoundsFeedbackStatus', '0');
			$mtrOrganisation = (int) $params->get('makingtheRoundsOrg', '0');

			$filters = $data;

			if (!empty($filters['date_range'])) {
				$endDate = new DateTime();
				$startDate = new DateTime();
				$startDate->modify('-' . (int)$filters['date_range'] . ' months');

				$startDate = $db->quote($startDate->format('Y-m-d'));
				$endDate = $endDate->format('Y-m-d');
			} else {
				$startDate = $db->quote($filters['start_date']);
				$endDate = $filters['end_date'];
			}

			$query = $db->getQuery(true);

    // Select required fields
			$query->select([
				
				'DATE_FORMAT(a.created_date, "%d-%m-%Y") AS Log_Date',
				"(SELECT fcv.value FROM #__tjfields_fields_value AS fcv 
				WHERE fcv.content_id = a.id AND fcv.field_id = $requestStatus LIMIT 1) AS Status",
				"(SELECT user.name FROM #__users AS user
				WHERE user.id = (SELECT fcv.value FROM #__tjfields_fields_value AS fcv 
				WHERE fcv.content_id = a.id AND fcv.field_id = $conductedBy LIMIT 1)) AS Conducted_by",
				"(SELECT fcv.value FROM #__tjfields_fields_value AS fcv 
				WHERE fcv.content_id = a.id AND fcv.field_id = $dpoFeedback LIMIT 1) AS CCTV_Compliance_Score",
				"(SELECT fcv.value FROM #__tjfields_fields_value AS fcv 
				WHERE fcv.content_id = a.id AND fcv.field_id = $feedbackStatus LIMIT 1) AS Feedback_Status",
				"(SELECT cl.name FROM #__tj_clusters AS cl 
				WHERE cl.id = (SELECT fcv.value FROM #__tjfields_fields_value AS fcv 
				WHERE fcv.content_id = a.id AND fcv.field_id = $mtrOrganisation LIMIT 1)) AS Organisation_Visited"
			]);

    // From main UCM data table
			$query->from($db->quoteName('#__tj_ucm_data', 'a'));

    // Apply filters
			$query->where($db->quoteName('a.client') . ' = ' . $db->quote('com_tjucm.monitoringcompliancemakingtherounds'));
			$query->where($db->quoteName('a.state') . ' = 1');
			$query->where($db->quoteName('a.draft') . ' = 0');

    // Filter by date range
			$today = (new DateTime())->format('Y-m-d');

			if ($filters['end_date'] === $today || empty($filters['end_date'])) {
			    $endDate = $db->quote($today . ' 23:59:59'); // Include today fully
			} else {
				$endDate = $db->quote($endDate. ' 23:59:59');
			}
			$query->where($db->quoteName('a.created_date') . " BETWEEN $startDate AND $endDate");

    // Filter by cluster if provided
			if (!empty($filters['cluster_id'])) {
				if (is_array($filters['cluster_id'])) {
					$query->where($db->quoteName('a.cluster_id') . " IN (" . implode(',', array_map([$db, 'quote'], $filters['cluster_id'])) . ")");
				} else {
					$query->where($db->quoteName('a.cluster_id') . ' = ' . (int) $filters['cluster_id']);
				}
			}
			$query->group($db->quoteName('a.id'));
			$query->order($db->quoteName('a.created_date') . ' DESC');
			$db->setQuery($query);
			$makingTheRoundData = $db->loadObjectList();

			// Logic to apply Checklist styling dynamically
			$checklistFieldMap = [
				'CCTV_Compliance_Score'    => $dpoFeedback,
				'Feedback_Status' => $feedbackStatus
			];

			foreach ($checklistFieldMap as $alias => $fieldId)
			{
				if (!$fieldId) continue;

				$query = $db->getQuery(true)
					->select(['params', 'type'])
					->from('#__tjfields_fields')
					->where('id = ' . (int) $fieldId);
				$db->setQuery($query);
				$fieldData = $db->loadObject();

				if ($fieldData && $fieldData->type === 'numericcalculation' && $fieldData->params)
				{
					$params = json_decode($fieldData->params);
					if (!empty($params->colorcombination))
					{
						$colorCombinations = json_decode($params->colorcombination);

						foreach ($makingTheRoundData as $key => $row)
						{
							$fieldValue = $row->$alias;
							
							foreach ($colorCombinations as $colorCombination)
							{
								if ($fieldValue >= $colorCombination->min && $fieldValue <= $colorCombination->max)
								{
									$textValueKey = 'textValue_' . $alias;
									$colorValueKey = 'colorValue_' . $alias;
									$makingTheRoundData[$key]->$textValueKey = $colorCombination->value;
									$makingTheRoundData[$key]->$colorValueKey = $colorCombination->color;
									break;
								}
							}
						}
					}
				}
			}
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
		}

		return $makingTheRoundData;
	}

	/**
	 * function to get the Inital trust data
	 *
	 * @param   array  $data
	 *
	 * @return	Object
	 *
	 * @since	1.0.0
	 */
	public function getInitialTrustData($data)
	{
		try {
			$db = Factory::getDbo();
			$query = $db->getQuery(true);
			$query->select($db->quoteName('a.id') . ' AS field_id')
			->from($db->quoteName('#__tjfields_fields', 'a'))
			->where($db->quoteName('a.client') . ' = ' . $db->quote('com_tjucm.trustinitialplan'))
			->where($db->quoteName('a.type') . ' = ' . $db->quote('dpechecklist'))
			->where($db->quoteName('a.state') . ' = 1');

			$db->setQuery($query);
			$fields = $db->loadObjectList();

			$fieldIds = array_map(fn($field) => $field->field_id, $fields);
			$fieldIdString = implode(',', $fieldIds);
			$fieldCount = count($fieldIds); // Total number of checklist fields

			// Define filters
			$params = DPE::config();
			$planNotesId   = (int) $params->get('initialTrustplanNotes', '0');
			$dateLogged   = (int) $params->get('initialTrustLoggedDate', '0');


			$filters = $data;

			$startDate = !empty($filters['date_range']) ? (new DateTime())->modify('-' . (int) $filters['date_range'] . ' months')->format('Y-m-d') : $filters['start_date'];
			$endDate = !empty($filters['date_range']) ? (new DateTime())->format('Y-m-d') : $filters['end_date'];

			$startDate = $db->quote($startDate);

			// Build main query
			$query = $db->getQuery(true);

			$query->select([
				'DATE_FORMAT(fcv3.value, "%d-%m-%Y") AS Date_Logged',
				// $db->quoteName('a.id'),
				"ROUND((SUM(CASE WHEN fcv.value = 'Done' THEN 1 ELSE 0 END) / $fieldCount) * 100, 2) AS Completeness_Percentage",
				$db->quoteName('fcv2.value') . ' AS Plan_Notes'
			]);

			$query->from($db->quoteName('#__tj_ucm_data', 'a'))
			->leftJoin($db->quoteName('#__tjfields_fields_value', 'fcv') . ' ON fcv.content_id = a.id 
				AND fcv.field_id IN (' . $fieldIdString . ')')
			->leftJoin($db->quoteName('#__tjfields_fields_value', 'fcv2') . ' ON fcv2.content_id = a.id 
				AND fcv2.field_id = ' . $db->quote($planNotesId))
			->leftJoin($db->quoteName('#__tjfields_fields_value', 'fcv3') . ' ON fcv3.content_id = a.id 
				AND fcv3.field_id = ' . $db->quote($dateLogged))

			->where($db->quoteName('a.client') . ' = ' . $db->quote('com_tjucm.trustinitialplan'))
			->where($db->quoteName('a.draft') . ' = 0');
			
			$today = (new DateTime())->format('Y-m-d');

			if ($filters['end_date'] === $today || empty($filters['end_date'])) {
			    $endDate = $today.' 23:59:59'; // Include today fully
			} else {
				$endDate = $endDate.' 23:59:59';
			}

			$query->where("STR_TO_DATE(fcv3.value, '%Y-%m-%d') BETWEEN $startDate AND " . $db->quote($endDate))->group($db->quoteName('a.id'));


			if (!empty($filters['cluster_id'])) {
				if (is_array($filters['cluster_id'])) {
					$query->where($db->quoteName('a.cluster_id') . " IN (" . implode(',', array_map([$db, 'quote'], $filters['cluster_id'])) . ")");
				} else {
					$query->where($db->quoteName('a.cluster_id') . ' = ' . (int) $filters['cluster_id']);
				}
			}

// Execute query

			$db->setQuery($query); 
			$initialTrust = $db->loadObjectList();


		} catch (Exception $e) {
			throw new Exception($e->getMessage());
		}

		return $initialTrust;

	}

	/**
	 * function to get Dpo User Data
	 *
	 * @param   array  $data
	 *
	 * @return	Object
	 *
	 * @since	1.0.0
	 */
	public function getDpoUserData($data)
	{

		$filters['cluster_id'] = $data;
		$db = Factory::getDbo();

		// Create a new query object
		$query = $db->getQuery(true);

		// Select distinct columns
		$query->select('DISTINCT u.id, u.name AS lead_consultant')
		->from($db->quoteName('z467w_tjmultiagency_multiagency', 'a'))
		->join('LEFT', $db->quoteName('z467w_users', 'u') . ' ON (' . $db->quoteName('u.id') . ' = ' . $db->quoteName('a.lead_consultant_id') . ' AND ' . $db->quoteName('u.block') . ' = 0)')
		->join('LEFT', $db->quoteName('z467w_tj_clusters', 'cl') . ' ON (' . $db->quoteName('cl.client_id') . ' = ' . $db->quoteName('a.id') . ')')
		->where($db->quoteName('a.state') . ' = 1');
		if (!empty($filters['cluster_id'])) {
			if (is_array($filters['cluster_id'])) {
				$query->where($db->quoteName('cl.id') . " IN (" . implode(',', array_map([$db, 'quote'], $filters['cluster_id'])) . ")");
			} else {
				$query->where($db->quoteName('cl.id') . ' = ' . (int) $filters['cluster_id']);
			}
		}
		$query->order($db->quoteName('u.name') . ' ASC');

		// Set the query and get the results
		$db->setQuery($query);
		$dpoList = $db->loadObjectList();

		return $dpoList;
	}

	/**
	 * Function to Save report data
	 *
	 * $data Array
	 * 
	 * @return Boolean.
	 *
	 * @since __DEPLOY_VERSION__
	 */

	public function saveReportData($data)
	{
		if (empty($data))
		{
			return false;
		}

		// Get the database object
		$db = Factory::getDbo();
		Table::addIncludePath(JPATH_ROOT . '/components/com_dpe/tables');
		$table = Table::getInstance('Annualreport', 'DpeTable');
		$user = Factory::getUser();

		if (!$data['jform']['startDate'])
		{
			$endDate    = new DateTime();
			$startDate  = new DateTime();
			$startDate->modify('-' . (int)$data['jform']['date_range'] . ' months');
			$startDate  = $startDate->format('Y-m-d');
			$endDate    = $endDate->format('Y-m-d');
			$today = (new DateTime())->format('Y-m-d');

			if ($filters['end_date'] === $today || empty($filters['end_date'])) {
			    $endDate = $today . ' 23:59:59'; // Include today fully
			}
		}


		if (!empty($data['jform']['id'])) {
			$hasStartDate = !empty($data['jform']['start_date']);
			$hasEndDate = !empty($data['jform']['end_date']);
			$hasDateRange = !empty($data['jform']['date_range']);

			if (!$hasStartDate && !$hasEndDate && $hasDateRange) {
				Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpe/tables');
				$orgReportTable = Table::getInstance('Annualreport', 'DpeTable');

				$reportId = $data['jform']['id'];

				if ($orgReportTable->load(array('id' => $reportId))) {
            // Calculate months difference between start_date and end_date from table
					$start = new DateTime($orgReportTable->start_date);
					$end = new DateTime($orgReportTable->end_date);
					$interval = $start->diff($end);

            // Calculate total months difference (years * 12 + months)
					$monthsDiff = ($interval->y * 12) + $interval->m;

            // Get the date_range value from form as int
					$formDateRange = (int) $data['jform']['date_range'];

            // Compare months difference with form date_range
					if (($monthsDiff === $formDateRange) && ($orgReportTable->end_date == $endDate) ) {
                // Assign the dates if matching
						$data['jform']['start_date'] = $orgReportTable->start_date;
						$data['jform']['end_date'] = $orgReportTable->end_date;
						$data['jform']['date_range'] = '';
					}
            // else do nothing if mismatch
				}
			}
		}

		if($data['to'] == 'saveForAdmin')// Save the status and comment for DPO Admin
		{
			$reportData['id'] = $data['id'];
			$reportData['dpo_comment'] = $data['dpo_comment'];
			$reportData['report_status'] = $data['report_status'];

		}else
		{
			$reportData['id'] = ($data['jform_id'])?$data['jform_id']:$data['jform']['id'];
			$reportData['cluster_ids'] = is_array( $data['jform']['cluster_id'])?implode(',', $data['jform']['cluster_id']): $data['jform']['cluster_id'];
			$reportData['tags'] = ($data['filter_tags'])?implode(',', $data['filter_tags']):null;
			$reportData['created_by']  =  $user->id;
			$reportData['start_date']  = ($data['jform']['start_date'])?$data['jform']['start_date']:$startDate;
			$reportData['end_date']    = ($data['jform']['end_date'])?$data['jform']['end_date']:$endDate;
			$currentDate  = new DateTime();
			$reportData['created_date'] = ($data['created_date'])?$data['created_date']:$currentDate->format('Y-m-d H:i:s');
			$reportData['section_filters'] = json_encode($data);
			$reportData['dpo_comment'] = $data['jform']['dpo_comment'];
			$reportData['report_status'] = $data['jform']['reportStatus'];
			$reportData['modified_date'] = $currentDate->format('Y-m-d H:i:s');

		}

		// Data to save
		// Bind the data to the table
		if (!$table->bind($reportData)) {
			Factory::getApplication()->enqueueMessage($table->getError(), 'error');
		}

		// Save the record
		if (!$table->store()) {
			return false;
		} else {

			return $table->id;
		}
	}


	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return   DataObjectbaseQuery
	 *
	 * @since    1.6
	 */
	protected function getListQuery()
	{
		$db    = $this->getDbo();
		$query    = $db->getQuery(true);
		$reportId = $this->getState('filter.id');
		
		$db = Factory::getDbo();
		$query->select('*')
		      ->from($db->quoteName('#__organisational_report')) // Replace with actual table prefix
		      ->where($db->quoteName('id') . ' = ' . $db->quote($reportId));

		// Set the query
		      $db->setQuery($query);

		      return $query;
		  }

		  public function getTable($type = 'annualreport', $prefix = 'DpeTable', $config = array())
		  {
		  	return parent::getTable($type, $prefix, $config);
		  }


	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return    mixed    The data for the form.
	 *
	 * @since    1.6
	 */
	protected function loadFormData()
	{
	    $data = $this->getItem(); // Load the existing data from the database
	    

	    if (!json_decode($data->section_filters)->jform->start_date)
	    {
	    	unset($data->start_date);
	    	unset($data->end_date);
	    }
	    if (empty($data)) {
	        $data = $this->getState('com_dpe.annualreport', array()); // Default state
	    }


	    return $data;
	}
	/**
	 * Method to get a single record.
	 *
	 * @param   integer  $pk  The id of the primary key.
	 *
	 * @return  \CMSObject|boolean  Object on success, false on failure.
	 *
	 * @since   1.0.0
	 */
	public function getItem($pk = null)
	{
		$items = parent::getItem($pk);

		if (str_contains($items->cluster_ids, ",")) {
			$multiClusters = explode(',',$items->cluster_ids);

			foreach ($multiClusters as $clusterkey => $multiCluster) {
				$clusterName[$clusterkey] = $this->getClusterName($multiCluster);
			}

			$items->cluster_id_name = implode(', ',$clusterName);
		}
		else
		{
			$items->cluster_id_name =  $this->getClusterName($items->cluster_ids);
		}

		return $items;
	}
	/**
	 * Method to get a single record.
	 *
	 * @param   integer  $clusteIds  The id of the clusters.
	 *
	 * @return  String name of the cluster
	 *
	 * @since   1.0.0
	 */
	public function getClusterName($clusteIds)
	{
		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_cluster/tables');
		$clusterInstance = Table::getInstance('Clusters', 'ClusterTable');
		$clusterInstance->load(array('id' => $clusteIds));
		return $clusterInstance->name;

	}
	/**
	 * Function to get data of Dfe Digital Data 
	 *
	 * @return Array data.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function getDfeDigitalData($data)
	{
		try
		{
			$db = Factory::getDbo();
			$app = Factory::getApplication();
			$input = $app->input;
			$statusFields = $data['statusFiled'];

			$filters = $data;

			if ($filters['date_range'])
			{
				$endDate = new DateTime();
				$startDate = new DateTime();
				$startDate->modify('-'.$filters['date_range'].' months'); 
				$startDate  = $startDate->format('Y-m-d');
				$endDate    = $endDate->format('Y-m-d');
			}
			else
				{	$startDate = $filters['start_date'];
			if(!$filters['end_date'])
			{
				$endDate = new DateTime();
			}else
			{
				$endDate   = $filters['end_date'];
			}
		}
		$today = (new DateTime())->format('Y-m-d');

		if ($filters['end_date'] === $today || empty($filters['end_date'])) {
			    $endDate = $today . ' 23:59:59'; // Include today fully
			}
			else
			{
				$endDate = $endDate . ' 23:59:59';
			}

			$query = $db->getQuery(true);

			$statusCases = [];

			foreach ($statusFields as $statusField) {
    $statusField = (int) $statusField; // Ensure integer casting for security
    $statusCases[] = "MAX(CASE WHEN fcv.field_id = " . $statusField . " THEN fcv.value END) AS Field_" . $statusField;
    $statusCases[] = "MAX(CASE WHEN fc.id = " . $statusField . " THEN fc.title END) AS ftitle_" . $statusField;
    $statusCases[] = "MAX(CASE WHEN fc.id = " . $statusField . " THEN fc.params END) AS params_" . $statusField;
}

		// Convert the array into a comma-separated string
$statusCasesSql = implode(", ", $statusCases);

		// Select required fields
$query->select("DATE_FORMAT(a.created_date, '%d-%m-%Y') AS Log_Date" . (!empty($statusCasesSql) ? ',' . $statusCasesSql : ''));

$query->from($db->quoteName('#__tj_ucm_data', 'a'));

		// Joining field values table
$query->join(
	'LEFT',
	$db->quoteName('#__tjfields_fields_value', 'fcv') . 
	' ON ' . $db->quoteName('fcv.content_id') . ' = ' . $db->quoteName('a.id')
);

		// Joining fields table to get field names and params
$query->join(
	'LEFT',
	$db->quoteName('#__tjfields_fields', 'fc') . 
	' ON ' . $db->quoteName('fc.id') . ' = ' . $db->quoteName('fcv.field_id')
);

		// Applying filters
$query->where($db->quoteName('a.client') . ' = ' . $db->quote($data['client']));
$query->where($db->quoteName('a.state') . " = 1");
$query->where($db->quoteName('a.draft') . " = 0");

		// Filter by date range
$dateCondition = "(" . $db->quoteName('a.created_date') . " BETWEEN " . $db->quote($startDate) . " AND " . $db->quote($endDate) . ")";

if (!empty($filters['date_range'])) {
	$dateCondition .= " OR (" . $db->quoteName('a.created_date') . " > " . $db->quote($startDate) . " AND " . $db->quoteName('a.created_date') . " < NOW())";
}
$query->where('(' . $dateCondition . ')');

		// Filter by cluster if provided
if (!empty($filters['cluster_id'])) {
	if (is_array($filters['cluster_id'])) {
		$query->where($db->quoteName('a.cluster_id') . " IN (" . implode(',', array_map([$db, 'quote'], $filters['cluster_id'])) . ")");
	} else {
		$query->where($db->quoteName('a.cluster_id') . ' = ' . (int) $filters['cluster_id']);
	}
}

		// Group by content ID
$query->group($db->quoteName('a.id'));
$db->setQuery($query);


$dfeDatas = $db->loadObjectList();


foreach ($dfeDatas as $keys => $dfeData) {    
	foreach ($dfeData as $dfeKey => $dfeFieldValue) {

		if (strpos($dfeKey, 'params') !== false) {
			$fieldId = str_replace('params_', '', $dfeKey);
			$paramsData = json_decode($dfeFieldValue, true);

			if (!empty($paramsData['colorcombination'])) {
				$colorCombinations = json_decode($paramsData['colorcombination']);

				foreach ($colorCombinations as $colorCombination) {
					$fieldKey = 'Field_' . $fieldId;

					if (isset($dfeData->$fieldKey)) {
						$fieldValue = $dfeData->$fieldKey;

						if ($fieldValue >= $colorCombination->min && $fieldValue <= $colorCombination->max) {
							$dfeDatas[$keys]->{"textValue_" . $fieldId} = $colorCombination->value;
							$dfeDatas[$keys]->{"colorValue_" . $fieldId} = $colorCombination->color;
							$paramId = 'params_'.$fieldId;
							unset($dfeDatas[$keys]->$paramId);
						}
					}
				}
			}
		}
	}
}
}
catch (Exception $e)
{
	throw new Exception($e->getMessage());
}

return $dfeDatas;
}


/**
	 * Method to get user list depending on the client chosen.
	 *
	 * @return   user list
	 *
	 * @since    1.0.0
	 */
public function getAdminUsersByClusterId($clusterId)
{
	
	
	$app = Factory::getApplication();
	$db = Factory::getDbo();
	$query = $db->getQuery(true);
	$params = ComponentHelper::getParams('com_multiagency');
	$orgAdminRoleId = $params->get('school_admin_role_id', '0', 'INT');

	$query
	->select([
		'DISTINCT a.email,a.id,a.name',
	])
	->from($db->quoteName('#__users', 'a'))
	->innerJoin($db->quoteName('#__tjsu_users', 'b') . ' ON (' . $db->quoteName('a.id') . ' = ' . $db->quoteName('b.user_id') . ' AND ' . $db->quoteName('b.client') . ' = ' . $db->quote('com_multiagency') . ')')
	->innerJoin($db->quoteName('#__tjmultiagency_multiagency', 'c') . ' ON (' . $db->quoteName('b.client_id') . ' = ' . $db->quoteName('c.id') . ')')
	->innerJoin($db->quoteName('#__tj_clusters', 'cluster') . ' ON (' . $db->quoteName('c.id') . ' = ' . $db->quoteName('cluster.client_id') . ')')
	->innerJoin($db->quoteName('#__tjsu_roles', 'r') . ' ON (' . $db->quoteName('r.id') . ' = ' . $db->quoteName('b.role_id') . ' AND ' . $db->quoteName('r.state') . ' = 1)')
	->leftJoin($db->quoteName('#__job_title_user_xref', 'job') . ' ON (' . $db->quoteName('job.user_id') . ' = ' . $db->quoteName('b.user_id') . ' AND ' . $db->quoteName('cluster.id') . ' = ' . $db->quoteName('job.cluster_id') . ')')
	->where($db->quoteName('a.block') . ' = 0')
	->where($db->quoteName('cluster.id') . ' = ' . (int) $clusterId)
	->where($db->quoteName('b.role_id') . ' = ' . (int) $orgAdminRoleId);

	$db->setQuery($query);
	return $results = $db->loadObjectList();
}


}
