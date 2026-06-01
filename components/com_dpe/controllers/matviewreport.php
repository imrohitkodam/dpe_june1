<?php
/**
 * @package     DPE
 * @subpackage  com_dpe
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Language\Text;
use Joomla\Registry\Registry;
use Joomla\CMS\Uri\Uri;

jimport('mpdf.mpdf');


/**
 * DPE RsTicketsPro controller
 *
 * @since  __DEPLOY_VERSION__
 */
class DpeControllerMatViewReport extends \Joomla\CMS\MVC\Controller\BaseController
{


/**
	 * Proxy for getModel.
	 *
	 * @param   string  $name    The model name. Optional.
	 * @param   string  $prefix  The class prefix. Optional
	 * @param   array   $config  Configuration array for model. Optional
	 *
	 * @return object	The model
	 *
	 * @since	1.6
	 */
public function &getModel($name = 'MatViewReport', $prefix = 'DpeModel', $config = array('ignore_request' => true))
{
	return parent::getModel($name, $prefix, $config);
}



    /**
	 * Method to fetch aggregated logs data, process the data 
	 * to separate log entries from metadata (like averages), and return a JSON response.
	 *
	 * @return  void
	 *
	 * @since 3.2.0
	 */
    public function getMatLogReportData($formData = null)
    {
        $app = Factory::getApplication();

        if (!Session::checkToken()) {
            echo new JsonResponse(null, Text::_('JINVALID_TOKEN'), true);
            $app->close();
        }


        if($formData == null){
        $formData = $app->input->getArray();

        }else{

            $formData = (array) $formData;
            $isDownloadPdf=true;
        }

        $annualReportModel = DPE::model('matviewreport');

        $avgKey = 'Average_lifecycle_duration_initiation_to_resolution_(days)';
        $logTypes = ['breachlog', 'sarlog', 'foilog', 'dpcomplaintslog'];

        if (!empty($formData['filter_tags']) || empty($formData['cluster_id'])) {
            JModelLegacy::addIncludePath(JPATH_SITE . '/components/com_dpe/models', 'DpeModel');
            $dashBoardModel = JModelLegacy::getInstance('Dashboard', 'DpeModel');
            $formData['cluster_id'] = $dashBoardModel->getClusterIdsByTags($formData['filter_tags']);
        }

        // 1. Fetch Raw Data
        $response = [];
        foreach ($logTypes as $logType) {
            // Map 'dpcomplaintslog' differently, others remove 'log'
            $type = ($logType !== 'dpcomplaintslog') ? str_replace('log', '', $logType) : $logType;

            // Condition: Either no checked_logs (first load) OR logType is in checked_logs
            if (empty($formData['checked_logs']) || in_array($logType, $formData['checked_logs'])) {
                $response[$logType] = $annualReportModel->getLogData($type, $formData);
            }
        }

        // 2. Process Logs
        Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjucm/tables');
        foreach ($logTypes as $logType) {
            // Ensure there’s always an array for the log type
            $dataToProcess = isset($response[$logType]) ? (array) $response[$logType] : [];

            // Remove empty scalar values and keep arrays only
            $cleaned = [];
            foreach ($dataToProcess as $item) {
                if (is_array($item)) {
                    $cleaned[] = $item;
                }
            }
            $response[$logType] = $cleaned;

            // Extract the average lifecycle duration if exists, else default to 0
            if (isset($dataToProcess[$avgKey])) {
                $response[$avgKey][$logType] = $dataToProcess[$avgKey];
            } else {
                $response[$avgKey][$logType] = 0;
            }
        }

        // Convert avgKey log type keys to human-readable titles
        if (!empty($response[$avgKey]) && is_array($response[$avgKey])) {
            $updatedData = [];
            foreach ($response[$avgKey] as $logType => $averageTime) {
                $ucmTable = Table::getInstance('type', 'TjucmTable');
                $suffix = ($logType === 'foilog') ? 'FOIlog' : $logType;

                if (in_array($logType, ['breachlog', 'sarlog', 'foilog', 'dpcomplaintslog'])) {
                    $identifier = 'com_tjucm.' . $suffix;
                    $ucmTable->load(['unique_identifier' => $identifier]);
                }

                $ucmTitle = !empty($ucmTable->title) ? $ucmTable->title : ucfirst($logType);
                $updatedData[$ucmTitle] = $averageTime;
            }
            $response[$avgKey] = $updatedData;
        }


            if($isDownloadPdf) {

                return ($response);
            } else {
                echo json_encode($response);
                $app->close();
            }
    }


	/**
	 * Method to fetch logs data aggregated by tags, process the data 
	 * to separate log entries from metadata (like averages), and return a JSON response 
     * with separate arrays for log data and average data.
	 *
	 * @return  void
	 *
	 * @since 3.2.0
	 */
	public function getMatLogReportDataForTag($formData = null)
	{
		$app = Factory::getApplication();

		if (!Session::checkToken()) {
			echo new JsonResponse(null, Text::_('JINVALID_TOKEN'), true);
			$app->close();
		}

        if($formData == null){
            $formData = $app->input->getArray();
        }else{
            $formData = (array) $formData;
		    $isDownloadPdf=true;
        }

		$annualReportModel = DPE::model('matviewreport');
		
		// Initialize the two main arrays for the final structured response
		$logReports = [];
		$averageReports = [];

		$avgKey = 'Average_lifecycle_duration_initiation_to_resolution_(days)';
		$logTypes = ['breachlog', 'sarlog', 'foilog','dpcomplaintslog'];

		if(!empty($formData['filter_tags']) || empty($formData['cluster_id']))
		{
			JModelLegacy::addIncludePath(JPATH_SITE . '/components/com_dpe/models', 'DpeModel');
			$dashBoardModel = JModelLegacy::getInstance('Dashboard', 'DpeModel');
			$formData['cluster_id'] = $dashBoardModel->getClusterIdsByTags($formData['filter_tags']);
		}

		foreach ($formData['cluster_id'] as $key => $clusterId) {

			$formData['jform']['cluster_id'] = $clusterId;
			Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_cluster/tables');

			$clusterInstance = Table::getInstance('Clusters', 'ClusterTable');

			$clusterInstance->load(array('id' => $clusterId));

			$clusterName[$key] = $clusterInstance->name;
			
			// 1. Fetch Raw Data into temporary array
			$rawLogData = [];

			   foreach ($logTypes as $logType) {
			    // Map 'dpcomplaintslog' differently, others remove 'log'

			   	$type = ($logType !== 'dpcomplaintslog') ? str_replace('log', '', $logType) : $logType;


			    // Condition: Either no checked_logs (first load) OR logType is in checked_logs
			   	if (empty($formData['checked_logs']) || in_array($logType, $formData['checked_logs'])) {
			   		// echo $type;
			   		$rawLogData[$logType] = $annualReportModel->getLogData($type, $formData);
			   	}
			   }

			// Initialize container for cleaned data for this cluster
			$processedData = [
				'average_life_cycle' => [],
			];

			// 2. Process and Clean Logs: Extract averages and clean log entries
			foreach ($logTypes as $logType) {
				// Cast to array ensures we work with a true, copyable array structure
				$dataToProcess = (array) $rawLogData[$logType];

				// Extract the Average Lifecycle Duration
				if (isset($dataToProcess[$avgKey])) {
					// Store the average value in the dedicated 'average_life_cycle' array
					$processedData['average_life_cycle'][$logType] = $dataToProcess[$avgKey];
					
					// Remove the average entry to clean the log array
					unset($dataToProcess[$avgKey]);
				} else {
                    // Ensure the key exists even if the value is missing (e.g., null or 0)
					$processedData['average_life_cycle'][$logType] = null; 
				}
				
				// Convert remaining numerically-keyed array/object to a clean, zero-indexed array
				$processedData[$logType] = array_values($dataToProcess);
			}
			
			// 3. Construct the two separate objects
            // Object 1: Log data and optional tag name
			$logData = [
				'breachlog' => $processedData['breachlog'],
				'sarlog'    => $processedData['sarlog'],
				'foilog'    => $processedData['foilog'],
				'dpcomplaintslog' => $processedData['dpcomplaintslog']

			];

			// Optional: Add tag name to the log data object
			if (!empty($formData['filter_tags'][$key])) {
				// $logData['tag_name'] = $formData['filter_tags'][$key];
			}

            // Object 2: Average data
			$avgData = [
				'average_life_cycle' => $processedData['average_life_cycle']
			];
			
			// 4. Append to the respective top-level arrays
			$logReports[] = $logData;
			$averageReports[] = $avgData;
		}


		$logTotals = [
			'breachlog' => 0,
			'sarlog' => 0,
			'foilog' => 0,
			'dpcomplaintslog'=>0
		];

		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjucm/tables');

		foreach ($averageReports as $report) {
    	// Check if the expected key exists
			if (isset($report['average_life_cycle']) && is_array($report['average_life_cycle'])) {

				$lifeCycles = $report['average_life_cycle'];
				$ucmTable = Table::getInstance('type', 'TjucmTable');

			foreach ($logTotals as $logType => $total) {

				$suffix = ($logType === 'foilog') ? 'FOIlog' : $logType;

				if (in_array($logType, ['breachlog', 'sarlog', 'foilog','dpcomplaintslog'])) {
					$identifier = 'com_tjucm.' . $suffix;
					$ucmTable->load(['unique_identifier' => $identifier]);
				}
					$ucmFormName[$logType]= $ucmTable->title;

        // Check if the log type exists in the current report's data
					if (isset($lifeCycles[$logType])) {
                // intval() safely converts strings/empty strings to 0
						$logTotals[$logType] += intval($lifeCycles[$logType]);
					}
				}
			}
		}

foreach ($logTotals as $logKey => $logCount) {
    
    // Check if the current logKey (e.g., 'breachlog') exists in the $ucmFormName array
    if (isset($ucmFormName[$logKey])) {
        
        // 1. Get the user-friendly name (the new key)
        $newKey = $ucmFormName[$logKey];
        
        // 2. Assign the log count to the new key in the $displayTotals array
        $displayTotals[$newKey] = $logCount;
        
    } else {
        // Fallback for any unexpected log types (optional)
        $displayTotals[$logKey] = $logCount;
    }
}

       // 5. Final JSON response containing the two separate root arrays
		$response = [
			'log_reports' => $logReports,
			'Average_lifecycle_duration_initiation_to_resolution' => $displayTotals,
			'orgname'=>$clusterName
		];

        	if($isDownloadPdf) {

                return ($response);
            } else {
                echo new JsonResponse($response);
                $app->close();
            }
	}


	public function migrateSuffixByclusterId()
	{
		$db = Factory::getDbo();

	// Mapping of clusterId => domain replacements
	$clusters = [130 => ['old' => '@nsix.org.uk11', 'new' => '@dataprotection.education1434']
		// 343 => ['old' => '@adlington.cheshire.sch.uk', 'new' => '@adlington.ht.school'],
		// 344 => ['old' => '@brokencross.cheshire.sch.uk', 'new' => '@brokencross.ht.school'],
		// 345 => ['old' => '@gawsworth.cheshire.sch.uk', 'new' => '@gawsworth.ht.school'],
		// 352 => ['old' => '@marlborough.cheshire.sch.uk', 'new' => '@marlborough.ht.school'],
		// 346 => ['old' => '@netheralderley.cheshire.sch.uk', 'new' => '@netheralderley.ht.school'],
		// 350 => ['old' => '@uptonpriory.cheshire.sch.uk', 'new' => '@uptonpriory.ht.school'],
		// 347 => ['old' => '@whirley.cheshire.sch.uk', 'new' => '@whirley.ht.school'],
	];

	foreach ($clusters as $clusterId => $domains)
	{
		// STEP 1: Fetch users for this cluster
		$query = $db->getQuery(true)
			->select([
				'a.id AS user_id',
				'a.email',
				'cluster.id AS cluster_id'
			])
			->from($db->quoteName('#__users', 'a'))
			->join('INNER', $db->quoteName('#__tjsu_users', 'b') . ' ON (' . $db->quoteName('a.id') . ' = ' . $db->quoteName('b.user_id') . ' AND ' . $db->quoteName('b.client') . ' = ' . $db->quote('com_multiagency') . ')')
			->join('INNER', $db->quoteName('#__tjmultiagency_multiagency', 'c') . ' ON (' . $db->quoteName('b.client_id') . ' = ' . $db->quoteName('c.id') . ')')
			->join('INNER', $db->quoteName('#__tj_clusters', 'cluster') . ' ON (' . $db->quoteName('c.id') . ' = ' . $db->quoteName('cluster.client_id') . ')')
			->where($db->quoteName('a.block') . ' = 0')
			->where($db->quoteName('cluster.id') . ' = ' . (int) $clusterId);

		$db->setQuery($query);
		$users = $db->loadObjectList();

		echo "\nProcessing Cluster ID: {$clusterId} (" . $domains['old'] . " → " . $domains['new'] . ")\n";

		// STEP 2: Loop through users and update email if it matches the old domain
		foreach ($users as $user)
		{
			echo"<pre>";
			if (strpos($user->email, $domains['old']) !== false)
			{
				$newEmail = str_replace($domains['old'], $domains['new'], $user->email);

				// Update the user email
				$update = $db->getQuery(true)
					->update($db->quoteName('#__users'))
					->set($db->quoteName('email') . ' = ' . $db->quote($newEmail))
					->where($db->quoteName('id') . ' = ' . (int) $user->user_id);

				$db->setQuery($update);
				$db->execute();

				echo "Updated: {$user->email} → {$newEmail}\n";
			}
			else
			{
				echo "Skipped: {$user->email} (no match)\n";
			}
		}
	}
	}

    /**
     * Normalize the posted form data structure received via AJAX.
     *
     * Handles:
     *  - Converts "formData[]" into a clean associative array
     *  - Extracts jform[field] into $final['jform']
     *  - Converts JSON "tags" into PHP array "filter_tags"
     *  - Removes unwanted items like filter_tags[] and chart_images
     *
     * @param  array $raw   Raw input array
     * @return array         Normalized/clean array
     *
     * @since 3.2.0
     */
    private function normalizePostedFormData($raw)
    {
        $final = [];

        /** --------------------------------------------------------
         * If no formData → return unchanged
         * -------------------------------------------------------- */
        if (!isset($raw['formData']) || !is_array($raw['formData'])) {
            return $raw;
        }

        /** --------------------------------------------------------
         * Extract formData fields
         * -------------------------------------------------------- */
        foreach ($raw['formData'] as $item) {

            $name  = $item['name'] ?? null;
            $value = $item['value'] ?? null;

            if (!$name) {
                continue;
            }

            // Case: jform[field]
            if (preg_match('/^jform\[(.+)\]$/', $name, $m)) {
                $final['jform'][$m[1]] = $value;
            }
            else {
                // Simple name=value
                $final[$name] = $value;
            }
        }

        /** Remove raw formData array */
        unset($raw['formData']);

        /** Merge final cleaned data back into raw */
        $merged = array_merge($raw, $final);

        /** --------------------------------------------------------
         * Convert "tags" JSON → filter_tags[]
         * -------------------------------------------------------- */
        if (!empty($merged['tags'])) {

            $tagList = json_decode($merged['tags'], true);

            if (is_array($tagList)) {
                // ensure indexes: 0,1,2...
                $merged['filter_tags'] = array_values($tagList);
            }

            unset($merged['tags']); // remove original
        }

        /** --------------------------------------------------------
         * Remove unwanted leftover "filter_tags[]"
         * -------------------------------------------------------- */
        if (isset($merged['filter_tags[]'])) {
            unset($merged['filter_tags[]']);
        }

        /** --------------------------------------------------------
         * Remove chart_images from form POST (not needed in model)
         * -------------------------------------------------------- */
        if (isset($merged['chart_images'])) {
            unset($merged['chart_images']);
        }

        return $merged;
    }

    /**
     * Build a unified Monthly/Daily log summary table from raw log arrays.
     *
     * Handles:
     *  - Auto-detection of Day mode vs Month mode
     *  - Accumulating open/closed counts across log types
     *  - Filtering months that contain only zero values
     *  - Sorting month/day keys chronologically
     *
     *
     * @param  array  $data   Log array for one organisation
     *
     * @return array          Structured monthly/daily summary:
     *
     * @since 3.2.0
     */
    private function buildMonthlyTable($data)
    {
        $months = [];

        /** ------------------------------------------------------------
         *  Log types mapping (API keys → table labels)
         * ------------------------------------------------------------ */
        $logTypes = [
            'breachlog'       => 'Breach',
            'sarlog'          => 'SAR',
            'foilog'          => 'FOI',
            'dpcomplaintslog' => 'DP Complaints'
        ];

        /** ------------------------------------------------------------
         *  Determine Day mode vs Month mode
         * ------------------------------------------------------------ */
        $isDayMode = false;

        // Developer flag
        if (($data['dayMonthCloumn'] ?? '') === 'Day') {
            $isDayMode = true;
        } else {
            // Auto-detect by scanning first non-empty log row
            foreach ($logTypes as $key => $_) {
                if (!empty($data[$key]) && is_array($data[$key])) {
                    $firstRow = reset($data[$key]);
                    if (isset($firstRow['report_day'])) {
                        $isDayMode = true;
                    }
                    break;
                }
            }
        }

        /** ------------------------------------------------------------
         *  Accumulate Logs
         * ------------------------------------------------------------ */
        foreach ($logTypes as $srcKey => $label) {

            if (empty($data[$srcKey]) || !is_array($data[$srcKey])) {
                continue;
            }

            foreach ($data[$srcKey] as $row) {

                // Pick the correct date key robustly
                $dateKey = $isDayMode
                    ? ($row['report_day']   ?? ($row['report_month'] ?? null))
                    : ($row['report_month'] ?? ($row['report_day']   ?? null));

                if (empty($dateKey)) {
                    continue; // skip invalid row
                }

                // Numeric values safe cast
                $openVal  = (int)($row['New_logs_created_during_the_reporting_period'] ?? 0);
                $closeVal = (int)($row['Number_Of_logs_closed_during_the_period'] ?? 0);

                // Ensure row exists
                if (!isset($months[$dateKey])) {
                    $months[$dateKey] = [
                        'month'         => $dateKey, // keep original key
                        'Breach'        => ['open' => 0, 'close' => 0],
                        'SAR'           => ['open' => 0, 'close' => 0],
                        'FOI'           => ['open' => 0, 'close' => 0],
                        'DP Complaints' => ['open' => 0, 'close' => 0],
                    ];
                }

                // ACCUMULATE (important)
                $months[$dateKey][$label]['open']  += $openVal;
                $months[$dateKey][$label]['close'] += $closeVal;
            }
        }

        /** ------------------------------------------------------------
         *  Remove months where ALL log types are 0
         * ------------------------------------------------------------ */
        foreach ($months as $k => $vals) {

            $nonZeroFound = false;

            foreach (['Breach','SAR','FOI','DP Complaints'] as $type) {

                if (($vals[$type]['open'] ?? 0) != 0 ||
                    ($vals[$type]['close'] ?? 0) != 0) {

                    $nonZeroFound = true;
                    break;
                }
            }

            if (!$nonZeroFound) {
                unset($months[$k]);
            }
        }

        /** ------------------------------------------------------------
         *  Sort results chronologically
         * ------------------------------------------------------------ */
        ksort($months);

        return $months;
    }

    /**
     * Format a month or date string into a human-readable format.
     *
     * Handles:
     *  - Monthly format: "YYYY-MM"     → "M-Y"     (Nov-2024)
     *  - Daily format:   "YYYY-MM-DD"  → "d-M-Y"   (15-Nov-2024)
     *  - Any other input is returned unchanged.
     *
     * @param   string  $value  Raw month/day string.
     *
     * @return  string          Formatted month/day string.
     *
     * @since 3.2.0
     */
    private function formatMonth($value)
    {
        // Convert safely
        $timestamp = strtotime($value);

        // Invalid format → return as is
        if ($timestamp === false) {
            return $value;
        }

        // Detect formats
        $length = strlen($value);

        switch ($length) {
            case 7:  // YYYY-MM
                return date("M-Y", $timestamp);      // Example: Nov-2024

            case 10: // YYYY-MM-DD
                return date("d-M-Y", $timestamp);    // Example: 15-Nov-2024

            default:
                return $value;
        }
    }
    /**
     * Generate DPE Logs PDF (Optimized, Stable, Chart-Safe Version)
     *
     * - NO LOGIC CHANGED
     * - Chart logic EXACT as earlier (base64 from JS)
     * - Only formatting + structure improved
     *
     * @return void
     */
    public function downloadPdf()
    {
        $app   = Factory::getApplication();
        $input = $app->input;

        // Get data
        $chartImages  = json_decode($input->getRaw('chart_images', '[]'), true);
        $orgaName     = htmlspecialchars($input->getString('cluster_name', ''));
        $tags         = json_decode($input->getRaw('tags', '[]'), true);
        $startDateStr = htmlspecialchars($input->getString('start_date', ''));
        $endDateStr   = htmlspecialchars($input->getString('end_date', ''));
        $fileName     = ($input->getString('pdf_filename', ''));

        $formData     = $app->input->getArray();
        $data         = [];

        $startDate = new DateTime($startDateStr);

        // If endDate empty → use today
        if (empty($endDateStr)) {
            $endDate = new DateTime(); // today
        } else {
            $endDate = new DateTime($endDateStr);
        }

        $diffDays = $startDate->diff($endDate)->days;

        $startDateFormatted = $startDate->format('d-m-Y');
        $endDateFormatted   = $endDate->format('d-m-Y');

        if ($diffDays < 30) {
            $dayMonthCloumn = 'Day';
        } else {
            $dayMonthCloumn = 'Month';
        }

        // Convert formData structure → Proper input array
        $formData = $this->normalizePostedFormData($formData);
        $formData['dayMonthCloumn'] = $dayMonthCloumn;

        if (!empty($tags) && is_array($tags)) {
            $data = $this->getMatLogReportDataForTag($formData);
        } else {
            $data = $this->getMatLogReportData($formData);
        }

        $orgNamesForCover = [];

        if (!empty($tags) && is_array($tags)) {

            foreach ($chartImages as $chart) {

                if (empty($chart['title'])) {
                    continue;
                }

                $orgName = trim($chart['title']);

                // Avoid duplicates
                if (!in_array($orgName, $orgNamesForCover)) {
                    $orgNamesForCover[] = $orgName;
                }
            }

            // Build bullet list for multiple organizations
            if (count($orgNamesForCover) > 1) {
                $orgaName = '<ul style="list-style-type: disc; text-align: left; display: inline-block; margin: 20px auto; padding-left: 30px;">';
                foreach ($orgNamesForCover as $name) {
                    $orgaName .= '<li style="margin: 10px 0; font-size: 24px; line-height: 1.5;">' . htmlspecialchars($name) . '</li>';
                }
                $orgaName .= '</ul>';
            } else {
                // Single organization - no bullet needed
                $orgaName = htmlspecialchars($orgNamesForCover[0] ?? '');
            }
        }

        $title = "DPE Logs Report";

        require_once JPATH_ROOT . '/libraries/vendor/autoload.php';

        $mpdf = new \Mpdf\Mpdf([
            'mode'               => 'utf-8',
            'format'             => 'A4',
            'default_font'       => 'dejavusans',
            'default_font_size'  => 12,
            'shrink_tables_to_fit' => 0
        ]);

        // Logo
        $imageUrl = Uri::root() . 'images/DataProtectionEd_Logo150H.jpg';

        // CSS with page rules
        $html = '<html><head>
        <meta charset="utf-8"/>
        <style>
            @page {
                margin-top: 120px;
                margin-bottom: 80px;
                header: html_myHeader;
                footer: html_myFooter;
            }
            body { font-family: dejavusans; font-size:12px; }
            h1,h2,h3,h4 { font-weight:bold; }
        </style>
        </head><body>';

        // HEADER
        $html .= '
        <htmlpageheader name="myHeader" style="margin-bottom:10px;">
            <table width="100%">
                <tr>
                    <td width="60%">
                        <p style="margin:0; font-size:16px; font-weight:bold;">' . $title . '</p>
                    </td>
                    <td width="40%" align="right">
                        <img src="' . $imageUrl . '" style="height:40px;">
                    </td>
                </tr>
            </table>
        </htmlpageheader>';

        // FOOTER
        $html .= '
        <htmlpagefooter name="myFooter">
            <hr style="border-top:1px solid #000;">
            <table width="100%">
                <tr>
                    <td align="right" style="font-size:10px;">
                        Page {PAGENO} of {nbpg}
                    </td>
                </tr>
            </table>
        </htmlpagefooter>';

        // COVER PAGE
        $html .= '
        <div style="position:absolute; top:220px; left:0; right:0; text-align:center;">

            <h1 style="font-size:48px; font-weight:700; color: #2c3e50; margin-bottom: 50px; letter-spacing: 1px;">
                DPE Logs Report
            </h1>

            <div style="margin: 40px 0; padding: 25px; background: #f8f9fa; border-radius: 8px; display: inline-block; min-width: 65%;">
                <h2 style="font-size:32px; font-weight:600; color: #34495e; margin-bottom: 20px;">
                    Date Range
                </h2>
                <p style="font-size:26px; font-weight:500; color: #555; margin: 0;">
                    ' . $startDateFormatted . ' to ' . $endDateFormatted . '
                </p>
            </div>

            <div style="margin: 40px 0; padding: 25px; background: #f8f9fa; border-radius: 8px; display: inline-block; min-width: 65%;">
                <h2 style="font-size:32px; font-weight:600; color: #34495e; margin-bottom: 20px;">
                    ' . (count($orgNamesForCover) > 1 ? 'Organizations' : 'Organization') . '
                </h2>
                <div style="font-size:24px; font-weight:500; color: #555; line-height: 1.6;">
                    ' . $orgaName . '
                </div>
            </div>

        </div><pagebreak>';

        // MULTI ORG
        if (!empty($tags) && is_array($tags)) {

            $totalCharts = count($chartImages);
            $currentChart = 0;

            foreach ($chartImages as $i => $chart) {

                if (empty($chart['img'])) continue;

                $currentChart++;
                $chartTitle = trim($chart['title']);

                // FIND ORG INDEX
                $orgIndex = array_search($chartTitle, $data['orgname']);
                if ($orgIndex === false) $orgIndex = $i;

                $orgName  = $data['orgname'][$orgIndex] ?? "Unknown Org";
                $cleanImg = preg_replace('#^data:image/[^;]+;base64,#', '', $chart['img']);

                // Chart
                $html .= '
                <div style="margin-bottom:40px; text-align:center;">
                    <h3 style="font-size:26px; font-weight:600">' . htmlspecialchars($chartTitle) . '</h3>
                    <img src="data:image/png;base64,' . $cleanImg . '"
                        style="max-width:100%; height:auto; border:1px solid #ccc;">
                </div>';

                // GET LOGS
                $logs        = $data['log_reports'][$orgIndex] ?? [];
                $monthlyData = $this->buildMonthlyTable($logs);

                // Title
                $html .= '<h3 style="text-align:center; margin-top:20px;">' . $orgName . ' - Log Summary</h3>';

                // TABLE START
                $html .= '<table border="1" cellpadding="5" cellspacing="0" width="100%" 
                            style="border-collapse:collapse; margin-top:15px;">
                            <thead>
                                <tr style="background:#f1f1f1; text-align:center; font-weight:bold;">
                                    <th>' . $dayMonthCloumn . '</th>
                                    <th>Breach (Open / Close)</th>
                                    <th>SAR (Open / Close)</th>
                                    <th>FOI (Open / Close)</th>
                                    <th>DP Complaints (Open / Close)</th>
                                </tr>
                            </thead>
                            <tbody>';

                if (empty($monthlyData)) {
                    $html .= '<tr><td colspan="5" style="text-align:center;">No data available for the selected period.</td></tr>';
                } else {
                    foreach ($monthlyData as $month => $row) {

                        $html .= '<tr>
                            <td>' . $this->formatMonth($month) . '</td>
                            <td>' . $row['Breach']['open'] . ' / ' . $row['Breach']['close'] . '</td>
                            <td>' . $row['SAR']['open'] . ' / ' . $row['SAR']['close'] . '</td>
                            <td>' . $row['FOI']['open'] . ' / ' . $row['FOI']['close'] . '</td>
                            <td>' . $row['DP Complaints']['open'] . ' / ' . $row['DP Complaints']['close'] . '</td>
                        </tr>';
                    }
                }

                $html .= '</tbody></table>';
                
                // Only add pagebreak if not the last chart
                if ($currentChart < $totalCharts) {
                    $html .= '<pagebreak />';
                }
            }
        }

        // SINGLE ORG MODE
        else {

            foreach ($chartImages as $index => $img) {

                $chartTitle = !empty($tags[$index]) ? htmlspecialchars($tags[$index]) : "Chart " . ($index + 1);

                $html .= '
                <div style="margin-bottom:40px; text-align:center;">
                    <h3 style="font-size:26px; font-weight:600">' . $orgaName . '</h3>
                    <img src="' . $img . '" style="max-width:100%; height:auto; border:1px solid #ccc;">
                </div>';
            }

            $monthlyData = $this->buildMonthlyTable($data);

            $html .= '<h3 style="text-align:center; margin-top:20px; font-size:26px; font-weight:600;">' . $orgaName . ' - Log Summary</h3>';

            // TABLE
            $html .= '<table border="1" cellpadding="5" cellspacing="0" width="100%" style="border-collapse:collapse; margin-top:20px;">
                        <thead>
                            <tr style="background:#f1f1f1; font-weight:bold; text-align:center;">
                                <th>' . $dayMonthCloumn . '</th>
                                <th>Breach (Open / Close)</th>
                                <th>SAR (Open / Close)</th>
                                <th>FOI (Open / Close)</th>
                                <th>DP Complaints (Open / Close)</th>
                            </tr>
                        </thead>
                        <tbody>';

            if (empty($monthlyData)) {

                $html .= '<tr><td colspan="5" style="text-align:center;">No data available for the selected period.</td></tr>';

            } else {

                foreach ($monthlyData as $month => $row) {

                    $html .= '<tr>
                        <td>' . $this->formatMonth($month) . '</td>
                        <td>' . ($row['Breach']['open'] ?? 0) . ' / ' . ($row['Breach']['close'] ?? 0) . '</td>
                        <td>' . ($row['SAR']['open'] ?? 0) . ' / ' . ($row['SAR']['close'] ?? 0) . '</td>
                        <td>' . ($row['FOI']['open'] ?? 0) . ' / ' . ($row['FOI']['close'] ?? 0) . '</td>
                        <td>' . ($row['DP Complaints']['open'] ?? 0) . ' / ' . ($row['DP Complaints']['close'] ?? 0) . '</td>
                    </tr>';
                }
            }
        }

        $html .= '</tbody></table>';

        // END HTML
        $html .= '</body></html>';

        $mpdf->WriteHTML($html);
        $mpdf->Output($fileName, 'D');
        $app->close();
    }

    /**
     * Render a formatted monthly/daily log summary table.
     *
     * @param string $orgName        Organisation Name
     * @param array  $monthlyData    Monthly/Daily structured log data
     * @param string $columnTitle    'Day' or 'Month'
     *
     * @return string
     */
    private function renderLogSummaryTable($orgName, $monthlyData, $columnTitle)
    {
        $html = '
        <h3 style="text-align:center; margin-top:20px; font-size:26px; font-weight:600;">'
            . $orgName . ' - Log Summary</h3>

        <table border="1" cellpadding="5" cellspacing="0" width="100%" 
            style="border-collapse:collapse; margin-top:20px;">
            <thead>
                <tr style="background:#f1f1f1; font-weight:bold; text-align:center;">
                    <th>' . $columnTitle . '</th>
                    <th>Breach (Open / Close)</th>
                    <th>SAR (Open / Close)</th>
                    <th>FOI (Open / Close)</th>
                    <th>DP Complaints (Open / Close)</th>
                </tr>
            </thead>
            <tbody>';

        if (empty($monthlyData)) {
            $html .= '<tr><td colspan="5" style="text-align:center;">
                        No data available for the selected period.
                    </td></tr>';
        } else {
            foreach ($monthlyData as $month => $row) {

                $html .= '
                <tr>
                    <td>' . $this->formatMonth($month) . '</td>
                    <td>' . $row['Breach']['open'] . ' / ' . $row['Breach']['close'] . '</td>
                    <td>' . $row['SAR']['open'] . ' / ' . $row['SAR']['close'] . '</td>
                    <td>' . $row['FOI']['open'] . ' / ' . $row['FOI']['close'] . '</td>
                    <td>' . $row['DP Complaints']['open'] . ' / '
                            . $row['DP Complaints']['close'] . '</td>
                </tr>';
            }
        }

        return $html . '</tbody></table>';
    }

}

