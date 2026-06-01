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
 * Annual Report
 * 
 * @since  __DEPLOY_VERSION__
 */
class DpeModelMatViewReport extends AdminModel
{
	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see        JController
	 * @since      1.6
	 */
	public function __construct($config = array())
	{
		
		parent::__construct($config);
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
		// Get the form.
		$form = $this->loadForm('com_dpe.matviewreport', 'matviewreport', array('control' => 'jform', 'load_data' => $loadData));
		return empty($form) ? false : $form;
	}


    /**
     * Function to get dynamic log data (Breach / SAR / FOI)
     *
     * @param string $clientType  The log type: 'breach', 'sar', or 'foi'
     * @param array  $data        The filter data (with jform filters)
     *
     * @return array
     *
     * @since __DEPLOY_VERSION__
     */
    public function getLogData($clientType, $data)
    {
        try
        {
            $db     = Factory::getDbo();
            $params = DPE::config();
            $filters = $data;
            $clusterIds = [];

            // 🔹 Map parameters based on log type
            $clientMap = [
                'breach' => [
                    'client' => 'com_tjucm.breachlog',
                    'statusField' => (int) $params->get('breachStatus', 0),
                    'extraFields' => $params->get('anuualreportbreach', []),
                ],
                'sar' => [
                    'client' => 'com_tjucm.sarlog',
                    'statusField' => (int) $params->get('requestStatus', 0),
                    'extraFields' => [],
                ],
                'foi' => [
                    'client' => 'com_tjucm.foilog',
                    'statusField' => (int) $params->get('foirequestStatus', 0),
                    'extraFields' => [],
                ],
                'dpcomplaintslog' => [
                    'client' => 'com_tjucm.dpcomplaintslog',
                    'statusField' => (int) $params->get('dpeComplaintsLog', 0),
                    'extraFields' => [],
                ],
            ];

            if (!isset($clientMap[$clientType])) {
                throw new Exception("Invalid client type: " . $clientType);
            }

            $config = $clientMap[$clientType];
            $client = $config['client'];
            $requestStatus = $config['statusField'];
            $extraFields = $config['extraFields'];

            // 🔹 Date handling
            if (!empty($filters['jform']['date_range'])) {
                $endDate = new DateTime();
                $startDate = new DateTime();
                $startDate->modify('-' . $filters['jform']['date_range'] . ' months');
                $startDate  = $startDate->format('Y-m-d');
                $endDate    = $endDate->format('Y-m-d');
            } else {
                $startDate = !empty($filters['jform']['start_date'])
                    ? DateTime::createFromFormat('d-m-Y', $filters['jform']['start_date'])->format('Y-m-d 00:00:00')
                    : null;

                $endDate = !empty($filters['jform']['end_date'])
                    ? (preg_match('/\d{2}-\d{2}-\d{4} \d{2}:\d{2}:\d{2}/', $filters['jform']['end_date'])
                        ? DateTime::createFromFormat('d-m-Y H:i:s', $filters['jform']['end_date'])
                        : DateTime::createFromFormat('d-m-Y', $filters['jform']['end_date']))
                        ->format('Y-m-d 23:59:59')
                    : (new DateTime())->format('Y-m-d 23:59:59');
            }

            $quotedStartDate = $db->quote($startDate);
            $quotedEndDate   = $db->quote($endDate);

            // 🔹 Determine grouping interval (daily if < 30 days)
            $start = new DateTime($startDate);
            $end   = new DateTime($endDate);
            $diffDays = $start->diff($end)->days;

            if ($diffDays < 30) {
                $groupBy = "DATE(a.created_date)";
                $reportColumn = "report_day";
            } else {
                $groupBy = "DATE_FORMAT(a.created_date, '%Y-%m')";
                $reportColumn = "report_month";
            }

            // 🔹 Base query
            $query = $db->getQuery(true);
            $selects = [];

            // 🔹 Dynamic extra fields (for breach only)
            if (!empty($extraFields)) {
                $sanitizedIds = array_map('intval', $extraFields);
                $idList = implode(',', $sanitizedIds);

                $fieldQuery = $db->getQuery(true)
                    ->select(['id', 'title'])
                    ->from($db->quoteName('#__tjfields_fields'))
                    ->where('id IN (' . $idList . ')');

                $db->setQuery($fieldQuery);
                $fields = $db->loadAssocList('id', 'title');

                $i = 1;
                foreach ($fields as $fieldId => $title) {
                    $alias = 'fcvl' . $i++;
                    $safeAlias = preg_replace('/[^A-Za-z0-9_]/', '_', str_replace(' ', '_', trim($title)));

                    $query->join(
                        'LEFT',
                        $db->quoteName('#__tjfields_fields_value', $alias) . ' ON ' .
                        $db->quoteName($alias . '.content_id') . ' = ' . $db->quoteName('a.id') .
                        ' AND ' . $db->quoteName($alias . '.field_id') . ' = ' . (int) $fieldId
                    );

                    $selects[] = "
                        COALESCE(SUM(
                            CASE 
                            WHEN a.modified_date BETWEEN $quotedStartDate AND $quotedEndDate 
                            AND ($alias.value = 'Closed') 
                            THEN 1 ELSE 0 
                            END
                        ), 0) AS `indicator__$safeAlias`
                    ";
                }
            }

            // 🔹 Common selects
            $selects = array_merge($selects, [
                "$groupBy AS $reportColumn",
                "COALESCE(SUM(CASE WHEN a.created_date BETWEEN $quotedStartDate AND $quotedEndDate 
                        AND a.modified_date BETWEEN $quotedStartDate AND $quotedEndDate 
                        AND fcv.value = 'Closed' THEN 1 ELSE 0 END), 0) AS Number_Of_logs_closed_during_the_period",
                "COALESCE(SUM(CASE WHEN a.created_date BETWEEN $quotedStartDate AND $quotedEndDate THEN 1 ELSE 0 END), 0) AS New_logs_created_during_the_reporting_period",
            ]);

            // 🔹 Build main query
            $query->select($selects)
                ->from($db->quoteName('#__tj_ucm_data', 'a'))
                ->join(
                    'LEFT',
                    $db->quoteName('#__tjfields_fields_value', 'fcv') . ' ON ' .
                    $db->quoteName('fcv.content_id') . ' = ' . $db->quoteName('a.id') .
                    ' AND ' . $db->quoteName('fcv.field_id') . ' = ' . (int) $requestStatus
                )
                ->where($db->quoteName('a.client') . ' = ' . $db->quote($client))
                ->where($db->quoteName('a.state') . " = 1")
                ->where($db->quoteName('a.draft') . " = 0")
                ->where($db->quoteName('a.created_date') . " BETWEEN $quotedStartDate AND $quotedEndDate")
                ->group($groupBy)
                ->order("$reportColumn ASC");

            // 🔹 Cluster filter
            if (!empty($filters['jform']['cluster_id'])) {
                $clusterIds = is_array($filters['jform']['cluster_id'])
                    ? array_map('intval', $filters['jform']['cluster_id'])
                    : [(int) $filters['jform']['cluster_id']];
                $query->where($db->quoteName('a.cluster_id') . ' IN (' . implode(',', $clusterIds) . ')');
            }

            $db->setQuery($query);
            $dataList = $db->loadAssocList();

            // 🔹 Fill missing dates/months
            if ($startDate && $endDate) {
                $period = new DatePeriod(
                    new DateTime($startDate),
                    new DateInterval($diffDays < 30 ? 'P1D' : 'P1M'),
                    new DateTime($endDate) 
                );

                $records = [];
                foreach ($period as $dt) {
                    $key = ($diffDays < 30) ? $dt->format('Y-m-d') : $dt->format('Y-m');
                    $records[$key] = [
                        $reportColumn => $key,
                        'Number_Of_logs_closed_during_the_period' => 0,
                        'New_logs_created_during_the_reporting_period' => 0,
                    ];
                }

                foreach ($dataList as $row) {
                    $key = $row[$reportColumn];
                    if (!isset($records[$key])) {
                        $records[$key] = [];
                    }
                    $records[$key] = array_merge($records[$key], $row);
                }

                $dataList = array_values($records);
            }

            // 🔹 Average lifecycle duration
            $avgQuery = $db->getQuery(true)
                ->select("ROUND(AVG(
                    CASE 
                        WHEN fcv.value = 'Closed' 
                        THEN GREATEST(DATEDIFF(a.modified_date, a.created_date), 1)
                        ELSE GREATEST(DATEDIFF(LEAST(NOW(), $quotedEndDate), a.created_date), 1)
                    END
                )) AS average_lifecycle_days")
                ->from($db->quoteName('#__tj_ucm_data', 'a'))
                ->join(
                    'LEFT',
                    $db->quoteName('#__tjfields_fields_value', 'fcv') . ' ON ' .
                    $db->quoteName('fcv.content_id') . ' = ' . $db->quoteName('a.id') .
                    ' AND ' . $db->quoteName('fcv.field_id') . ' = ' . (int) $requestStatus
                )
                ->where($db->quoteName('a.client') . ' = ' . $db->quote($client))
                ->where($db->quoteName('a.state') . ' = 1')
                ->where($db->quoteName('a.draft') . ' = 0')
                ->where($db->quoteName('a.created_date') . " BETWEEN $quotedStartDate AND $quotedEndDate");

            if (!empty($clusterIds)) {
                $avgQuery->where($db->quoteName('a.cluster_id') . ' IN (' . implode(',', $clusterIds) . ')');
            }

            $db->setQuery($avgQuery);
            $avg = (int) $db->loadResult();

            $indexedDataList = [];
            $counter = 1;

            foreach ($dataList as $row) {
                $indexedDataList[$counter++] = $row;
            }

            $indexedDataList['Average_lifecycle_duration_initiation_to_resolution_(days)'] = $avg;

            return $indexedDataList;
        }
        catch (Exception $e)
        {
            throw new Exception($e->getMessage());
        }
    }




}