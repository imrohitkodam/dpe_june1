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

jimport('techjoomla.tjnotifications.tjnotifications');
jimport('mpdf.mpdf');

require_once JPATH_ROOT . '/libraries/vendor/autoload.php';

/**
 * DPE RsTicketsPro controller
 *
 * @since  __DEPLOY_VERSION__
 */
class DpeControllerAnnualreports extends \Joomla\CMS\MVC\Controller\BaseController
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
	public function &getModel($name = 'AnnualReports', $prefix = 'DpeModel', $config = array('ignore_request' => true))
	{
		return parent::getModel($name, $prefix, $config);
	}



	/**
	 * Method to Create the report
	 *
	 * @return  void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function createReport()
	{
		JLoader::import('ComtjlmsHelper', JPATH_SITE . '/components/com_tjlms/helpers');
		$app              = Factory::getApplication();
		$comtjlmsHelper   = new ComtjlmsHelper;
		$itemId           = $comtjlmsHelper->getitemid('index.php?option=com_dpe&view=annualreport');
		$submitReportLink = Route::_('index.php?option=com_dpe&view=annualreport&Itemid=' . $itemId, false);
		$app->redirect($submitReportLink);
	}

	/**
	 * Method to redirect to Fetch the report data
	 *
	 * @return  void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function getAnnualReportData(){
		$app = Factory::getApplication();

		if (!Session::checkToken()) {
			echo new JsonResponse(null, Text::_('JINVALID_TOKEN'), true);
			$app->close();
		}
		$result = []; // Initialize the result array

		$data = $app->input->getArray();
		

if (!is_array($data['jform'])) {

		$data['jform'] = $this->parseJsonToArray($data['jform']);
}

$normalStartDate = $data['jform']['start_date'];
 $data['jform']['start_date'] =  Factory::getDate($data['jform']['start_date'])->toSql();

$normalDateEndDate = $data['jform']['end_date'];
 $data['jform']['end_date'] = Factory::getDate($data['jform']['end_date'])->toSql(); 

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
            if ($monthsDiff === $formDateRange) {
                // Assign the dates if matching
                $data['jform']['start_date'] = $orgReportTable->start_date;
                $data['jform']['end_date'] = $orgReportTable->end_date;
                $data['jform']['date_range'] = '';
            }
            // else do nothing if mismatch
        }
    }
}
		$annualReportModel = DPE::model('annualreport');
		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjucm/tables');

		if (!is_array($data['jform'])) {
			$data['jform'] = (array) json_decode($data['jform']);
		}

		if(($data['filter_tags']) || (!$data['jform']['cluster_id']))
		{

			JModelLegacy::addIncludePath(JPATH_SITE . '/components/com_dpe/models', 'DpeModel');
			$dashBoardModel = JModelLegacy::getInstance('Dashboard', 'DpeModel');
			$data['jform']['cluster_id'] = $dashBoardModel->getClusterIdsByTags($data['filter_tags']);

			$user = Factory::getUser();
			// Check user is from trustee group or not
			JLoader::import("/components/com_cluster/includes/cluster", JPATH_ADMINISTRATOR);
			$clusterUserModel = ClusterFactory::model('ClusterUser', array('ignore_request' => true));
			$clusters = $clusterUserModel->getUsersClusters($user->id);
			$clientId = array();


			foreach ($clusters as $key => $cluster)
			{
				$clientId[$key] = $cluster->cluster_id;
				$clusterName[$key] = $cluster->title;
			}

			$data['jform']['cluster_id']= ($user->authorise('core.manageall', 'com_cluster'))?$data['jform']['cluster_id']:array_intersect($clientId, $data['jform']['cluster_id']);

			if(!$user->authorise('core.manageall', 'com_cluster'))
			{
			  foreach ($clusters as $obj) {
				if (in_array($obj->cluster_id, $data['jform']['cluster_id'])) {
					$clusterTitles[] = $obj->title;
				}
			  }	
			}else
			{
				JLoader::import('clusters', JPATH_ADMINISTRATOR . '/components/com_cluster/tables');
				foreach($data['jform']['cluster_id'] as $clusterId)
				{
					$clustersTable = Table::getInstance('clusters', 'ClusterTable', array());
				    $clustersTable->load(array('id' => $clusterId));
				    $clusterTitles[] = $clustersTable->name;
				}
			}
		}

		foreach ($data as $key => $method) {
			if($key == 'filter_tags'){
				continue;
			}

    // Check if the key contains an underscore (_) 
			if ((strpos($key, '_') !== false) && (($key != 'Accountability_Framework_Tracking') && ($key != 'DfE_Digital_Standard'))) {

				if (is_string($method)) {
					$method = trim($method, '"');
				}

        // Initialize the model
				$annualReportModel = DPE::model('annualreport');

				if($key == "DfE_Cyber_Governance_Code_of_Practice")
				{	
					$dpeDfeParam = DPE::config();
					$dfeStatusFieldName = strtolower(trim(str_replace('_', '', $key)));
					$statusFieldValues = $dpeDfeParam->get($dfeStatusFieldName);
					$data['jform'] = [
						'statusFiled' => $dpeDfeParam->get($dfeStatusFieldName, []),
						'client' =>'com_tjucm.cybergovernancecodeofpractice',
						'cluster_id' => $data['jform']['cluster_id'],
						'start_date' => $data['jform']['start_date'],
						'end_date' => $data['jform']['end_date'],
						'date_range' => $data['jform']['date_range'],
					];

				}

        // If the value is a method name, dynamically call the method
				if (method_exists($annualReportModel, $method)) {
					$result[$key] = $annualReportModel->$method($data['jform']);
				}
			}

			/** Handle Accountability Framework Tracking */
			if ($key == 'Accountability_Framework_Tracking') { 
				$methods = json_decode($method);
				$accountabilityMethodName = $methods->method;
				$clients = $methods->client;
				$dpeParams = DPE::config();
				$accountabilityValue = [];

				foreach ($clients as $client) {  
					$db = Factory::getDbo();
					$query = $db->getQuery(true)
					->select($db->quoteName('title'))
					->from($db->quoteName('#__tj_ucm_types'))
					->where($db->quoteName('state') . ' = 1')
					->where($db->quoteName('unique_identifier') . ' = ' . $db->quote($client))
					->order($db->quoteName('title') . ' ASC');

					$db->setQuery($query);
					$uniqueTrackingFormName = $db->loadAssoc();

					$statusId = (int) $dpeParams->get($statusFieldName);
					$statusFieldName = str_replace('com_tjucm.', '', $client);
					$accountabilityData = [
						'statusFiled' => $statusId,
						'client' => $client,
						'cluster_id' => $data['jform']['cluster_id'],
						'start_date' => $data['jform']['start_date'],
						'end_date' => $data['jform']['end_date'],
						'date_range' => $data['jform']['date_range'],
					];

					$accountabilityValue[$statusFieldName] = $annualReportModel->$accountabilityMethodName($accountabilityData);
					$accountabilityValue[$statusFieldName]['title'] = $uniqueTrackingFormName['title'] ?? '';
				}

				$result[$key] = $accountabilityValue;
			}

			/** Handle DFE Digital Standards */
			if ($key == 'DfE_Digital_Standard') { 
				$methods = json_decode($method);
				$dfeMethodName = $methods->method;
				$dfeClients = $methods->client;
				$dpeDfeParams = DPE::config();
				$valuesde = [];

				foreach ($dfeClients as $dfeKey =>  $dfeClient) {  

					$dfeStatusFieldName = trim(str_replace('com_tjucm.', '', $dfeClient));

            // Ensure we are retrieving valid values
					$retrievedValue[$dfeKey] = $dpeDfeParams->get($dfeStatusFieldName);
					$dfeStatusValue = $dpeDfeParams->get($dfeStatusFieldName, []);

					$db = Factory::getDbo();
					$query = $db->getQuery(true)
					->select($db->quoteName('title'))
					->from($db->quoteName('#__tj_ucm_types'))
					->where($db->quoteName('state') . ' = 1')
					->where($db->quoteName('unique_identifier') . ' = ' . $db->quote($dfeClient))
					->order($db->quoteName('title') . ' ASC');

					$db->setQuery($query);
					$uniqueDfeFormName = $db->loadAssoc();

					$dfeData = [
						'statusFiled' => $dpeDfeParams->get($dfeStatusFieldName, []),
						'client' => $dfeClient,
						'cluster_id' => $data['jform']['cluster_id'],
						'start_date' => $data['jform']['start_date'],
						'end_date' => $data['jform']['end_date'],
						'date_range' => $data['jform']['date_range'],
					];

					$dfeValue[$dfeStatusFieldName] = $annualReportModel->$dfeMethodName($dfeData);
					$dfeValue[$dfeStatusFieldName]['title'] = $uniqueDfeFormName['title'] ?? '';
				}

				$result[$key] = $dfeValue;
			}
		} 


		$result['lead_assistant_data'] = $annualReportModel->getDpoUserData($data['jform']['cluster_id']);
		if(($data['filter_tags']) || (!$data['jform']['cluster_id']))
		{
			$result['clusterTitle']=$clusterTitles;
		}
		$result['start_date'] = $normalStartDate;
		$result['end_date'] = $normalEndDate;


		echo new JsonResponse($result);
		$app->close();
	}
/**
 * Parses a JSON string and returns it as an associative array.
 *
 * This function is useful for handling form or AJAX data that may be submitted
 * as a JSON string in Joomla components or plugins. It checks if the input is
 * valid JSON, and if so, decodes it into a PHP associative array.
 *
 * @param string $json The JSON string to parse.
 *
 * @return array|null Returns the decoded array if valid JSON, otherwise null.
 */
function parseJsonToArray($json)
{
    $decoded = json_decode($json, true); // Decode to associative array

    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        return $decoded;
    }

    return null; // Invalid JSON
}

	/**
	 * Method to Save the report data
	 *
	 * @return  void
	 *
	 * @since   __DEPLOY_VERSION__
	 */

	public function saveAnnualReportData()
	{
		$app = Factory::getApplication();

		if (!Session::checkToken())
		{
			echo new JsonResponse(null, Text::_('JINVALID_TOKEN'), true);
			$app->close();
		}

		$feedback = $app->input->get('jform', '', 'RAW');
		$data = $app->input->getArray();
		$data['jform']['dpo_comment'] = $feedback['dpo_comment'];
		
		if (!$data['created_date'])
		{
			Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpe/tables');
			$orgReportTable = Table::getInstance('Annualreport', 'DpeTable');
			$orgReportTable->load(array('id' => $data['jform']['id']));
			$data['created_date'] = $orgReportTable->created_date;
		}
$user = Factory::getUser();

	if (!empty($data['filter_tags']) || empty($data['jform']['cluster_id']))
	{
	JModelLegacy::addIncludePath(JPATH_SITE . '/components/com_dpe/models', 'DpeModel');
	$dashBoardModel = JModelLegacy::getInstance('Dashboard', 'DpeModel');
	$tagBasedClusters = $dashBoardModel->getClusterIdsByTags($data['filter_tags']);

	$isCreator = ($data['created_by'] == $user->id);
	$isSuperAdmin = $user->authorise('core.manageall', 'com_cluster');

	if ($isCreator && $isSuperAdmin)
	{
		// Super admin editing their own report — can assign all clusters
		$data['jform']['cluster_id'] = $tagBasedClusters;
	}
	elseif ($isCreator && !$isSuperAdmin)
	{
		// Org admin editing their own report — limit to their own clusters
		JLoader::import("/components/com_cluster/includes/cluster", JPATH_ADMINISTRATOR);
		$clusterUserModel = ClusterFactory::model('ClusterUser', array('ignore_request' => true));
		$userClusters = $clusterUserModel->getUsersClusters($user->id);
		
		$userClusterIds = array_map(function($c) {
			return $c->cluster_id;
		}, $userClusters);

		$data['jform']['cluster_id'] = array_intersect($tagBasedClusters, $userClusterIds);
	}
	elseif (!$isCreator && $isSuperAdmin && !empty($data['jform']['id']))
	{
		// Super admin editing someone else's report — keep original cluster assignment
		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpe/tables');
		$orgReportTable = Table::getInstance('Annualreport', 'DpeTable');
		$orgReportTable->load(['id' => $data['jform']['id']]);
		$data['jform']['cluster_id'] = $orgReportTable->cluster_ids;
	}
	else
	{   if ($isSuperAdmin)
		{
			$data['jform']['cluster_id'] = $tagBasedClusters;
		}else
		{ 
			JLoader::import("/components/com_cluster/includes/cluster", JPATH_ADMINISTRATOR);
			$clusterUserModel = ClusterFactory::model('ClusterUser', array('ignore_request' => true));
			$userClusters = $clusterUserModel->getUsersClusters($user->id);
			
			$userClusterIds = array_map(function($c) {
				return $c->cluster_id;
			}, $userClusters);


			$data['jform']['cluster_id'] = array_intersect($tagBasedClusters, $userClusterIds);
			}

	}
	// No else case — do not override if not authorized
}



		unset($data['view'], $data['id'], $data['Itemid']);
		$annualReportModel = DPE::model('annualreport');

		$data['jform']['start_date'] = Factory::getDate($data['jform']['start_date'])->toSql();
		$data['jform']['end_date']   = Factory::getDate($data['jform']['end_date'])->toSql();

		$reportId = $annualReportModel->saveReportData($data);

		$result = ($reportId)?$reportId:false;

		echo new JsonResponse($result);
		$app->close();


	}

	/**
	 * Sends the report to the Data Protection Officer (DPO) for review.
	 *
	 * This method updates the report status and notifies the assigned DPO.
	 * Returns `true` if the report was successfully sent, otherwise `false`.
	 *
	 * @return bool True if the report is sent successfully, false otherwise.
	 */
	public function sendToDpoForReview()
	{
		$app = Factory::getApplication();

		if (!Session::checkToken())
		{
			echo new JsonResponse(null, Text::_('JINVALID_TOKEN'), true);
			$app->close();
		}

		$url = $app->input->get('url','','RAW');
		$toAdmin = $app->input->get('to','','RAW');
		$reportStatus = $app->input->get('reportStatus','','RAW');
		$adminIds = $app->input->get('adminIds','','RAW');

		parse_str(parse_url($url, PHP_URL_QUERY), $queryParams);
		$reportId = isset($queryParams['id']) ? $queryParams['id'] : null;
		
		
    	   	Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpe/tables');
			$orgReportTable = Table::getInstance('Annualreport', 'DpeTable');
			$orgReportTable->load(array('id' => $reportId));
			$clusterIds = explode(',',$orgReportTable->cluster_ids);
		

		if (($toAdmin == 'Admin'))
		{
			if ($clusterIds && (in_array('all', $adminIds)))
			{
				$annualReportModel = DPE::model('annualreport');
				foreach($clusterIds as $clusterId)
				{
					$adminUserIds[] = $annualReportModel->getAdminUsersByClusterId($clusterId);
				}
				$adminUserIds = array_merge(...$adminUserIds);

				$adminUserIds =  array_map(function($obj) {
			    return $obj->id;
			}, $adminUserIds);
			}
			else
			{
				$adminUserIds = $adminIds;
			}
			

			foreach($adminUserIds as $adminUserId)
			{
				$userData = Factory::getUser($adminUserId);
				$adminUserEmail = $userData->email;
				$recipients = array(
					'email' => array(
						'to' => array($adminUserEmail)
					)
				);
				$key    = "com_dpe.annualReportsendToAdminForReview";
			

			$options = new Registry;
			$replacements = new stdClass;
			$replacements->user = new stdClass;
			$replacements->user->name = $userData->name;
			$replacements->user->url = Route::_($url);
			$replacements->user->orgadmin = Factory::getUser()->name;		
			$result =  Tjnotifications::send("com_dpe", $key, $recipients, $replacements, $options);
			}

		}else
		{
			$dpoUserId    = json_decode($orgReportTable->section_filters)->jform_leadConsultantDropdown;
			$userData = Factory::getUser($dpoUserId);
			 $dpoUserEmail = $userData->email;
			$recipients = array(
				'email' => array(
					'to' => array($dpoUserEmail)
				)
			);
			$key    = "com_dpe.annualReportsendToDpoForReview";
			$options = new Registry;
			$replacements = new stdClass;
			$replacements->user = new stdClass;
			$replacements->user->name = $userData->name;
			$replacements->user->url = Route::_($url);
			$replacements->user->orgadmin = Factory::getUser()->name;	
			$result =  Tjnotifications::send("com_dpe", $key, $recipients, $replacements, $options);
		}

		
		$result['message'] = ($toAdmin == 'Admin')?Text::_('COM_DPE_ANNUAL_REPORT_SEND_TO_ADMIN_SUCCES_MESSAGE'):Text::_('COM_DPE_ANNUAL_REPORT_SEND_TO_DPO_SUCCES_MESSAGE');

		if (($reportStatus == 'DPO_Finalised') && ($toAdmin == 'Admin')){
			$dpoComment = $app->input->get('editorContent','','RAW');

			$annualReportModel = DPE::model('annualreport');
			$data['report_status']=$reportStatus;
			$data['id']=$app->input->get('id','','INT');
			$data['dpo_comment']=$dpoComment;
			$data['to']='saveForAdmin';

			$reportId = $annualReportModel->saveReportData($data);

		}
		echo new JsonResponse($result);
		$app->close();

	}

	/**
	 * Print the data into PDF format using MPDF
	 *
	 *
	 * @return json response.
	 */
	public function getAnnualReportPdfDownload()
	{
   // Get Joomla application instance
		$app = Factory::getApplication();
		$input = $app->input;
		$rawData = $input->json->getRaw();
		$data = json_decode($rawData, true);

// Validate data
		if (!$data || empty($data['htmlContent'])) {
			header('HTTP/1.1 400 Bad Request');
			echo json_encode(['error' => 'Invalid request: No content received']);
			exit;
		}

// Extract Data
		$htmlContent = $data['htmlContent'];
		$chartImages = $data['charts'] ?? [];
		$title = htmlspecialchars($data['title'] ?? 'Report');
		$orgname = htmlspecialchars($data['orgname'] ?? '');
		$conductedBy = htmlspecialchars($data['conductedBy'] ?? '');
		$reportDate = htmlspecialchars($data['date'] ?? date('Y-m-d'));


		foreach ($chartImages as $chart) {
			$chartId = preg_quote($chart['id'], '/');

    		// Match the entire <td> that contains <div id="chart_xxx">
			$pattern = '/<td[^>]*style="[^"]*width\s*:\s*40%\s*;?\s*vertical-align\s*:\s*top\s*;?[^"]*"[^>]*>\s*<div[^>]*id="' . $chartId . '"[^>]*>.*?<\/div>\s*<\/td>/is';

			$imageTag = '<td style=";">
			<img src="' . htmlspecialchars($chart['src']) . '" style="height: auto;marin-left:20px;    width: 60%;" alt="Chart Image">
			</td>';

    // Replace the whole <td> block
			$htmlContent = preg_replace($pattern, $imageTag, $htmlContent);
		}

		
// Initialize mPDF
// Clean unwanted empty divs and canvas that might be left behind
$htmlContent = preg_replace('/<div[^>]*>\s*<\/div>/', '', $htmlContent); // remove empty divs
$htmlContent = preg_replace('/<canvas[^>]*>.*?<\/canvas>/', '', $htmlContent); // remove stray canvas if left
$htmlContent = preg_replace('/<span[^>]*>\s*<\/span>/', '', $htmlContent); // remove empty spans
$htmlContent = preg_replace('/<p[^>]*>\s*<\/p>/', '', $htmlContent); // remove empty paragraphs
$htmlContent = preg_replace('/<!--(.|\s)*?-->/', '', $htmlContent); // remove HTML comments

$imageUrl = Uri::root() . 'images/DataProtectionEd_Logo150H.jpg';

$mpdf = new \Mpdf\Mpdf([
    'mode' => 'utf-8',
    'format' => 'A4',
    'shrink_tables_to_fit' => 0,
    'default_font_size' => 12,
    'default_font' => 'dejavusans', // Important for bold/italic rendering
    'allow_output_buffering' => true
]);

$currentPageNumber = $mpdf->page;
$css = file_get_contents(JPATH_SITE . '/templates/shaper_helix3/css/bootstrap.min.css');
		// $csscustom = file_get_contents(JPATH_SITE . '/templates/shaper_helix3/css/custom.css');

    // Prepare the HTML content
$html = '<html><head><meta name="viewport" content="width=device-width, initial-scale=1"><meta charset="utf-8"/>';
$html .= '<style>
' . $css . '
@page{margin-top:120px;margin-bottom:80px;header:html_myHeader;footer:html_myFooter}body{font-family:"Open Sans",sans-serif!important;margin:0;padding:0}.page-break{page-break-after:always}.content-wrapper{margin-top:120px;display:flex;justify-content:space-between;align-items:center;gap:10px}.headingOfReport{font-size:32px;font-weight:700;text-align:center;margin-top:50px;color:#333}table{width:100%;border-collapse:collapse}img{max-width:100%;height:auto}.annualth{background:#0e9cd1;color:#fff;padding:20px 5px 17px;margin-bottom:-20px}.page-number:before{content:counter(page);font-size:12px;color:grey}.mb-4{margin-bottom:1.5rem!important}.mt-4{margin-top:1.5rem!important}.control-group{margin-bottom:16px}.createreport{margin-top:-36px!important}.report-section{box-shadow:0 2px 8px rgba(0,0,0,.2)}@media(min-width:993px){.report-organisation-section{height:100vh!important}}.tablepie-section{height:70vh;overflow-y:scroll}#jform_cluster_id_chosen{border:1px solid lightgray;box-shadow:0 1px 5px rgba(0,0,0,.15)}#jform_cluster_id-lbl,#jform_start_date-lbl,#jform_end_date-lbl,#jform_date_range-lbl,#jform_reportStatus-lbl,#dpolist{font-size:15px!important}.report-organisation-section #jform_start_date_btn,.report-organisation-section #jform_end_date_btn{background-color:#f5f5f5!important;color:#333;border-color:#b3b3b3}.check-reports{display:flex;gap:10px}.reportdata.hide{display:none}.chartjs-render-monitor{width:450px!important;height:230px!important;margin-top:5px!important}td,th{word-wrap:break-word;overflow-wrap:break-word}.log-section{margin-bottom:40px}.orgth{background:#1291a0;text-align:center;font-size:16px;padding:26px;color:white}.tab-section { page-break-before: always; }
h1, h2, h3, h4, h5, h6, strong {font-weight: bold; color: #000;}</style>';
$html .= '</head><body>';
$html .= '<htmlpageheader name="myHeader" style="margin-bottom:20px;">
<table width="100%">
<tr>
<td width="60%">
<p style="margin: 0;">' . (($currentPageNumber == 0) ? htmlspecialchars($data['title']) : "") . '</p>

</td>
<td width="40%" align="right">
<img src="' . $imageUrl . '" style="height: 40px;">
</td>
</tr>
</table>
</htmlpageheader>';

// **Define Footer**
$html .= '<htmlpagefooter name="myFooter">
<hr style="border-top: 1px solid black;">
<table width="100%">
<tr>
<td align="right" style="font-size: 10px;">Page {PAGENO} of {nbpg}</td>
</tr>
</table>
</htmlpagefooter>';

$html .= '<div class="content-wrapper">';

    // Cover Page
$html .= '<div class="section" style="text-align: center; padding-top: 90px;">
<h1 class="headingOfReport">' . htmlspecialchars($data['title']) . '</h1>
<h1 class="headingOfReport">Report</h1><br>
<div style="font-size: 14px; text-align: left; margin-top: 20px;"><ul>';

// Split and create list
$entries = explode(',',$data['orgname']);
foreach ($entries as $entry) {
    $subItems = explode(',', $entry);
    foreach ($subItems as $item) {
        $html .= '<li style="font-size:20px;font-weight:500;">' . $item . '</li>';
    }
}
$html .= '</ul></div></div>';

$html .= '<h4 style="font-size: 24px; margin-top: 25px;text-align:center;">' . htmlspecialchars($data['conductedBy']) . '</h4>
<h4 style="font-size: 24px; margin-top: 20px; text-align:center;">' . htmlspecialchars($data['date']) . '</h4>
</div>';

$html .= '<div class="page-break"></div>';

$html .= ' <div class="container-fluid"><div class="row">';

// Main Report Content
$html .= $htmlContent;

 $html .= '</div></div></div></body></html>';

// Generate PDF
$pdfName = $title . '_' . $orgname . '_' . date('YmdHis') . '.pdf';
$mpdf->WriteHTML($html);
$pdfString = $mpdf->Output('', 'S');

// Joomla-friendly way to send binary content
$app->setHeader('Content-Type', 'application/pdf', true);
$app->setHeader('Content-Disposition', 'attachment; filename="' . $pdfName . '"', true);
$app->setHeader('Content-Length', strlen($pdfString), true);
$app->sendHeaders();
echo $pdfString;
$app->close();

}

/**
	 * Method to Save the report data
	 *
	 * @return  void
	 *
	 * @since   __DEPLOY_VERSION__
	 */

	public function deleteAnnualReport()
	{
		$app = Factory::getApplication();

		if (!Session::checkToken())
		{
			echo new JsonResponse(null, Text::_('JINVALID_TOKEN'), true);
			$app->close();
		}

		$reportId = $app->input->get('id', '', 'RAW');

		if($reportId)
		{
			Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpe/tables');
			$orgReportTable = Table::getInstance('Annualreport', 'DpeTable');

			// Load the row you want to update
			if ($orgReportTable->load(['id' => $reportId]))
			{
				 // Update fields
			    $orgReportTable->state = 0;

			    // Save the changes
			    if (!$orgReportTable->store())
			    {
			    	$result['msg'] = $orgReportTable->getError();
       			    $result['success'] = false;

			    }
			    else
			    {	
			     $result['msg'] = Text::_('COM_DPE_ANNUAL_REPORT_DELETE_SUCCES_MESSAGE');
			     $result['success'] = true;
			    }
			}
			else
			{
				$result['msg'] = Text::_('COM_DPE_ANNUAL_REPORT_DELETE_FAIL_MESSAGE');
       			$result['success'] = false;
			}
		}
		echo new JsonResponse($result);
		$app->close();
	}

/**
	 * This function fetch the admins users of cluster/oraganization
	 *
	 * @return  string   html to build a assignment list view
	 *
	 * @since   __DEPLOY__VERSION__
	 */

	public function getAdminList()
	{
			$app = Factory::getApplication();

			if (!Session::checkToken())
			{
				echo new JsonResponse(null, Text::_('JINVALID_TOKEN'), true);
				$app->close();
			}

			$url = $app->input->get('url','','RAW');

			parse_str(parse_url($url, PHP_URL_QUERY), $queryParams);
			$reportId = isset($queryParams['id']) ? $queryParams['id'] : null;
			Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpe/tables');
			$orgReportTable = Table::getInstance('Annualreport', 'DpeTable');
			$orgReportTable->load(array('id' => $reportId));
			$clusterIds = explode(',',$orgReportTable->cluster_ids);

			$annualReportModel = DPE::model('annualreport');
			$adminUserIds = array();

			foreach($clusterIds as $clusterId)
			{				

				$adminUserIds[] = $annualReportModel->getAdminUsersByClusterId($clusterId);
			}
			$adminUserIds = array_merge(...$adminUserIds);

			$result['list']= $adminUserIds;
			$result['success'] = true;

			echo new JsonResponse($result);
			$app->close();

	}

	/**
 * Converts a date string from dd-mm-yyyy to yyyy-mm-dd format.
 *
 * Useful when form inputs provide dates in dd-mm-yyyy format, 
 * but the database (e.g., MySQL) expects yyyy-mm-dd.
 *
 * @param string $date Date string in dd-mm-yyyy format (e.g. '18-06-2025')
 * @return string|null Converted date in yyyy-mm-dd format or null if input is invalid
 */
	function convertToMysqlDate($date)
	{
	    // Validate and parse the date
	    if (!empty($date) && preg_match('/^\d{2}-\d{2}-\d{4}$/', $date)) {
	        list($day, $month, $year) = explode('-', $date);
	        return $year . '-' . $month . '-' . $day;
	    }

	    // Return null if invalid
	    return null;
	}

}