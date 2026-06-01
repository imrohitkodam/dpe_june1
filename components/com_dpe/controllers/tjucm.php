<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Dpe
 * @copyright  Copyright (c) 2009-2018 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Factory;
jimport('techjoomla.tjnotifications.tjnotifications');
JLoader::import('components.com_dpe.includes.dpe', JPATH_SITE);
use Joomla\CMS\User\User;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\Registry\Registry;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Uri\Uri;
jimport("mpdf.mpdf");

/**
 * Tjucm controller class.
 *
 * @since  1.0
 */
class DpeControllerTjucm extends BaseController
{
	// To send mail before defined days
	const SETDAYS = 7;

	/**
	 * Function tjListFieldDataMigration used to update tjlist field data
	 *
	 * @return  null
	 *
	 * @since  1.2.3
	 */
	public function tjListFieldDataMigration()
	{
		$app = Factory::getApplication();
		$isMultiple = $app->input->getInt('multiple', 0);

		$db = Factory::getDbo();
		$subquery = $db->getQuery(true);
		$subquery->select('id');
		$subquery->from($db->quoteName('#__tjfields_fields', 'fl'));
		$subquery->where($db->qn('state') . " = 1");
		$subquery->where($db->qn('type') . " = 'tjlist'");
		$subquery->where($db->qn('params') . " LIKE '%\"other\":\"1\"%'");
		$subquery->where($db->qn('params') . " LIKE '%\"multiple\":\"" . $isMultiple . "\"%'");

		$query = $db->getQuery(true);
		$query->select('fv.*');
		$query->from($db->quoteName('#__tjfields_fields_value', 'fv'));
		$query->join('LEFT', $db->qn('#__tjfields_options', 'fo') . ' ON (' .
			$db->qn('fo.field_id') . ' = ' . $db->qn('fv.field_id') . ' AND ' . $db->qn('fo.value') . ' = ' . $db->qn('fv.value') . ')');
		$query->where($db->qn('fv.value') . " NOT LIKE 'tjlist:-%'");
		$query->where($db->qn('fo.field_id') . " IS NULL");
		$query->where($db->qn('fv.field_id') . " IN ( " . $subquery . ")");

		$db->setQuery($query);
		$fieldValues = $db->loadObjectList();

		if (empty($fieldValues))
		{
			die("Content migrated successfully");
		}

		jimport('joomla.log.logger.formattedtext');

		// Add the logger.
		Log::addLogger(
			// Pass an array of configuration options
			array(
				'text_file' => 'tjListFieldDataMigration.log',
				'text_file_path' => 'logs'
			),
			Log::ALL
		);

		Log::add("ID | FIELD_ID |  CONTENT_ID  | CLIENT | USER_ID | VALUE");

		JLoader::import('components.com_tjfields.tables.fieldsvalue', JPATH_ADMINISTRATOR);
		$fieldsValueTable = Table::getInstance('FieldsValue', 'TjfieldsTable', array('dbo', Factory::getDbo()));

		foreach ($fieldValues as $key => $fieldVale)
		{
			$fieldsValueTable->id = $fieldVale->id;
			$fieldsValueTable->field_id = $fieldVale->field_id;
			$fieldsValueTable->content_id = $fieldVale->content_id;
			$fieldsValueTable->user_id = $fieldVale->user_id;
			$fieldsValueTable->client = $fieldVale->client;

			// Check its multiple type list field and value containing multiple values
			if ($isMultiple == 1 && strpos($fieldVale->value, ','))
			{
				$fieldVal = explode(',', $fieldVale->value);

				// Add prefix for first element of array and update record
				$fieldsValueTable->value = 'tjlist:-' . $fieldVal[0];
				$fieldsValueTable->store();
				unset($fieldVal[0]);

				// Create log for updated record
				Log::add(
					" Updating the field value record => " . $fieldsValueTable->id . " | "
					. $fieldsValueTable->field_id . " | " . $fieldsValueTable->content_id . " | "
					. $fieldsValueTable->client . " | " . $fieldsValueTable->user_id . " | " . $fieldsValueTable->value
				);

				foreach ($fieldVal as $fieldData)
				{
					// Insert the remaining other fields value
					$fieldvTable = Table::getInstance('FieldsValue', 'TjfieldsTable', array('dbo', Factory::getDbo()));
					$fieldvTable->field_id = $fieldVale->field_id;
					$fieldvTable->content_id = $fieldVale->content_id;
					$fieldvTable->user_id = $fieldVale->user_id;
					$fieldvTable->client = $fieldVale->client;
					$fieldvTable->value = 'tjlist:-' . $fieldData;
					$fieldvTable->store();

					// Create log for newly added record
					Log::add(
						" Insert new field value record => " . $fieldsValueTable->id . " | "
						. $fieldsValueTable->field_id . " | " . $fieldsValueTable->content_id . " | "
						. $fieldsValueTable->client . " | " . $fieldsValueTable->user_id . " | " . $fieldsValueTable->value
					);
				}
			}
			elseif ($fieldVale->value != 'tjlistothervalue')
			{
				// Add prefix to other value of list field and update record
				$fieldsValueTable->value = 'tjlist:-' . $fieldVale->value;
				$fieldsValueTable->store();

				// Create log for updated record
				Log::add(
					" Updating the field value record => " . $fieldsValueTable->id . " | "
					. $fieldsValueTable->field_id . " | " . $fieldsValueTable->content_id . " | "
					. $fieldsValueTable->client . " | " . $fieldsValueTable->user_id . " | " . $fieldsValueTable->value
				);
			}
		}
	}

	/**
	 * Breach log cron (Every 24 hours after creation
	 * IF Reported to ICO = No, or null School manager/school admin/DPE notified via email)
	 *
	 * @return  boolean
	 *
	 * @since   1.6
	 */
	public function breachlog()
	{
		$secretKey = Factory::getApplication()->input->get('key', '', 'STRING');

		// 1.Get config of reported to ico field id
		$params = ComponentHelper::getParams('com_dpe');
		$reportedToICO = (int) $params->get('reportedToICO', '0');
		$tjucmType = (string) $params->get('tjucmBreachType', '0');
		$breachStatus = (int) $params->get('breachStatus', '0');

		if ($secretKey != (string) $params->get('cronSecretKey', ''))
		{
			return false;
		}

		// 2. Get ICO reported logs
		$db = Factory::getDBO();
		$subInQuery = $db->getQuery(true);
		$subInQuery->select('fv.content_id')
		->from($db->qn('#__tjfields_fields_value', 'fv'))
		->join('INNER', $db->qn('#__tjfields_fields_value', 'fcv') . ' ON (' .
			$db->qn('fcv.content_id') . ' = ' . $db->qn('fv.content_id') . ')')
		->where($db->qn('fv.field_id') . ' = ' . $db->q($reportedToICO))
		->where($db->qn('fv.value') . ' in ("no","not_required")')
		->where($db->qn('fcv.field_id') . ' = ' . $db->q($breachStatus))
		->where($db->qn('fcv.value') . ' in ("In progress")');
		$db->setQuery($subInQuery);

		$reportedLogs = $db->loadColumn();

		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_tjucm/models');
		$TjucmModel = BaseDatabaseModel::getInstance('ItemForm', 'TjucmModel', array('ignore_request' => true));

		$params   = ComponentHelper::getParams('com_tjucm');
		$TjucmModel->setState('params', $params);

		if (count($reportedLogs) > 0)
		{
			$params = ComponentHelper::getParams('com_multiagency');
			$dpeAdmin = (int) $params->get('multyagency_admin_role_id', '0');
			$schoolAdmin = (int) $params->get('school_admin_role_id', '0');
			// $manager = (int) $params->get('manager_role_id', '0');

			// Get All dpe admin users
			$query = $db->getQuery(true);
			$query->select("distinct(u.id)");
			$query->from($db->qn("#__tjsu_users", "su"));
			$query->join('INNER', '#__users AS u ON u.id = su.user_id');
			$query->where($db->qn('u.block') . " = 0");
			$query->where($db->qn('su.role_id') . " = " . $db->q($dpeAdmin));
			$db->setQuery($query);

			$dpeuserIds = $db->loadColumn();

			foreach ($reportedLogs as $log)
			{
				// Get Logs from data
				$logFormdata = $TjucmModel->getData($log);
				$cluster = ClusterCluster::getInstance($logFormdata->cluster_id);

				$createdDate = new Date($logFormdata->created_date . '+1 day', 'UTC');

				$currentTime = new Date('now', 'UTC');

				if ($currentTime > $createdDate && $cluster->client_id)
				{
					$schoolId = $cluster->client_id;

					// Get Logs field data
					$logFieldData = $TjucmModel->getFormExtra(
						array(
							"clientComponent" => 'com_tjucm',
							"client" => 'com_tjucm.' . $tjucmType,
							"view" => $tjucmType,
							"layout" => 'edit',
							"content_id" => $log)
					);

					if (!empty((int) $schoolId))
					{
					/**
					 * Restrict to send mail to SA, Manager users

						$query = $db->getQuery(true);
						$query->select("distinct(u.id)");
						$query->from($db->qn("#__tjsu_users", "su"));
						$query->join('INNER', '#__users AS u ON u.id = su.user_id');
						$query->where($db->qn('u.block') . " = 0");
						$query->where($db->qn('su.client_id') . " = " . $db->q($schoolId));
						$query->where($db->qn('su.role_id') . " in (" . $schoolAdmin . "," . $manager . ")");

						$db->setQuery($query);
						$userIds = $db->loadColumn();

						// Merge dpeadmin users and manager/school admin users with unique ids
						$usersIds = array_unique(array_merge($userIds, $dpeuserIds));
					*/

						$key = 'ReportedToICOCron';

						foreach ($dpeuserIds as $uId)
						{
							$recipients->email = Factory::getUser($uId)->get('email');
							$replacements = new stdClass;
							$replacements->reportedToICOCron->content_id = $log;
							$replacements->reportedToICOCron->username = Factory::getUser($uId)->get('name');
							$replacements->reportedToICOCron->school = $cluster->name;
							$replacements->reportedToICOCron->status = $logFieldData->getData()->get('com_tjucm_breachlog_breachstatus');
							$options = new Registry;

							$res = Tjnotifications::send("com_dpe", $key, array($recipients), $replacements, $options);

							if ($res['success'] == 1)
							{
								echo 'email send to -' . $cluster->name . '-' . Factory::getUser($uId)->get('email') . '<br>';
							}
						}
					}
				}
			}
		}
		else
		{
			echo 'Not found reported To ICO field value no / not required';
		}
	}

	/**
	 * Sar log cron (7 days before Date to respond IF Request status = In progress School manager/school
	 * admin/DPE/Staff member responsible notified via email)
	 *
	 * @return  boolean
	 *
	 * @since   1.6
	 */
	public function sarlog()
	{
		$secretKey = Factory::getApplication()->input->get('key', '', 'STRING');

		// 1.Get config of reported to ico field id
		$params = ComponentHelper::getParams('com_dpe');
		$dateToRespond = (int) $params->get('dateToRespond', '0');
		$requestStatus = (int) $params->get('requestStatus', '0');
		$tjucmsarType = (string) $params->get('tjucmsarType', '0');

		if ($secretKey != (string) $params->get('cronSecretKey', ''))
		{
			return false;
		}

		// 2. Get ICO reported logs
		$currentDate = new Date('now', 'UTC');
		$db = Factory::getDBO();
		$subInQuery = $db->getQuery(true);

		$dbDateFormat = "DATE_FORMAT(" . $db->quote($currentDate) . ", '%Y-%m-%d')";
		$dueDateFormat = "DATE_FORMAT(" . $db->qn('fcv.value') . ", '%Y-%m-%d')";

		$subInQuery->select('fv.content_id , CASE WHEN ( ' . self::SETDAYS . ' >= DATEDIFF( ' . $dueDateFormat . ','
			. $dbDateFormat . ')) THEN 1 ELSE 0 END AS reminderStatus')
		->from($db->qn('#__tjfields_fields_value', 'fv'))
		->join('INNER', $db->qn('#__tjfields_fields_value', 'fcv') . ' ON (' .
			$db->qn('fcv.content_id') . ' = ' . $db->qn('fv.content_id') . ')')
		->where($db->qn('fv.field_id') . ' = ' . $db->q($requestStatus))
		->where($db->qn('fv.value') . ' in ("In progress")')
		->where($db->qn('fcv.field_id') . ' = ' . $db->q($dateToRespond))
		->where($db->qn('fcv.value') . '  >= DATE_FORMAT(' . $db->quote($currentDate) . ", '%Y-%m-%d')");

		$db->setQuery($subInQuery);

		$inProgressLogs = $db->loadObjectList();

		if (count($inProgressLogs) > 0)
		{
			BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_tjucm/models');
			$TjucmModel = BaseDatabaseModel::getInstance('ItemForm', 'TjucmModel', array('ignore_request' => true));

			$params   = ComponentHelper::getParams('com_tjucm');
			$TjucmModel->setState('params', $params);

			// Get SA, Manager and dep-admin email address
			$params = ComponentHelper::getParams('com_multiagency');
			$dpeAdmin = (int) $params->get('multyagency_admin_role_id', '0');
			$schoolAdmin = (int) $params->get('school_admin_role_id', '0');
			// $manager = (int) $params->get('manager_role_id', '0');

			// Get All dpe admin users
			$query = $db->getQuery(true);
			$query->select("distinct(u.id)");
			$query->from($db->qn("#__tjsu_users", "su"));
			$query->join('INNER', '#__users AS u ON u.id = su.user_id');
			$query->where($db->qn('u.block') . " = 0");
			$query->where($db->qn('su.role_id') . " = " . $db->q($dpeAdmin));
			$db->setQuery($query);

			$dpeuserIds = $db->loadColumn();

			foreach ($inProgressLogs as $log)
			{
				if (!$log->reminderStatus)
				{
					continue;
				}

				// Get Logs from data
				$logFormdata = $TjucmModel->getData($log->content_id);

				$cluster = ClusterCluster::getInstance($logFormdata->cluster_id);

				if ($cluster->client_id)
				{
					$schoolId = $cluster->client_id;

					// Get Logs field data
					$logFieldData = $TjucmModel->getFormExtra(
						array(
							"clientComponent" => 'com_tjucm',
							"client" => 'com_tjucm.' . $tjucmsarType,
							"view" => $tjucmsarType,
							"layout" => 'edit',
							"content_id" => $log->content_id)
					);

					/**
					* Restrict to send mail to SA, Manager users

					$query = $db->getQuery(true);
					$query->select("distinct(u.id)");
					$query->from($db->qn("#__tjsu_users", "su"));
					$query->join('INNER', '#__users AS u ON u.id = su.user_id');
					$query->where($db->qn('u.block') . " = 0");
					$query->where($db->qn('su.client_id') . " = " . $db->q($schoolId));
					$query->where($db->qn('su.role_id') . " in (" . $schoolAdmin . "," . $manager . ")");

					$db->setQuery($query);
					$userIds = $db->loadColumn();

					// Merge dpeadmin users and manager/school admin users with unique ids
					$usersIds = array_unique(array_merge($userIds, $dpeuserIds));

					*/

					$key = 'sarlog';

					foreach ($dpeuserIds as $uId)
					{
						$recipients->email = Factory::getUser($uId)->get('email');
						$replacements = new stdClass;
						$replacements->sarlog->username = Factory::getUser($uId)->get('name');
						$replacements->sarlog->school = $cluster->name;
						$replacements->sarlog->content_id = $log->content_id;
						$replacements->sarlog->status = $logFieldData->getData()->get('com_tjucm_sarlog_requeststatus');
						$options = new Registry;

						$res = Tjnotifications::send("com_dpe", $key, array($recipients), $replacements, $options);

						if ($res['success'] == 1)
						{
							echo 'email send to -' . $cluster->name . '-' . Factory::getUser($uId)->get('email') . '<br>';
						}
					}
				}
			}
		}
	}


	public function saveonboardusers()
	{   
		$app          = Factory::getApplication();
		$formData     = $app->input->post->getArray();
		
		if(empty($formData))
		{
			return false;
		}

		JLoader::import('components.com_dpe.models.users', JPATH_SITE);
		$model = BaseDatabaseModel::getInstance('Users', 'DpeModel');
		$result = $model->saveonboardusers($formData);

		$message['success'] =  ($result)?true:false;
		
		echo new JsonResponse($message);
		$app->close();

	}
	
	/**
	 * Stores the selected cluster ID in the session.
	 *
	 * This function is used to retrieve the cluster ID from an AJAX request and 
	 * store it in the user's session for future reference.
	 *
	 * @return void
	 */
	public function storeClusterIdInSession()
	{
		// Get Joomla session and input
		$session = Factory::getSession();
		$input = Factory::getApplication()->input;
		
		// Get cluster_id from the AJAX request
		$cluster_id = $input->get('cluster_id', ''); // Use getString instead of getInt

		// Set response header to JSON
		header('Content-Type: application/json');

		if (!empty($cluster_id)) {
			// Clear previous value if it exists
			$session->clear('selectedCluster');

			// Store new cluster ID in session
			$session->set('selectedCluster', $cluster_id);

			// Send JSON response
			echo json_encode([
				'status' => 'success',
				'message' => 'Cluster ID stored in session',
				'cluster_id' => $cluster_id
			]);
		} else {
			echo json_encode([
				'status' => 'error',
				'message' => 'No cluster ID received'
			]);
		}

		jexit(); // Properly terminate the request
	}

	public function getReportContent($reportDatas)
	{
		$importReportModel = DPE::model('import', array('ignore_request' => true));

		// Extract metadata from the first item
		$metaData   = $reportDatas[0];
		$userId     = $metaData['userId'];
		$exportType = isset($metaData['export_type']) ? $metaData['export_type'] : 'separate';
		
		// Map indices for iteration
		$reportHtmlData = [];
		$allClusters    = [];
		$fileTitle     = '';

		foreach($reportDatas as $key => $reportData)
		{
			if (!isset($reportData['id'])) continue;

			foreach($reportData['id'] as $reId => $ucmId)
			{
				$data = $importReportModel->getDatasOfUcmRecordId($ucmId, $reportData['client'], $reportData['formName']);
				if ($data) {
					$reportHtmlData[$reId] = $data;
					$allClusters[] = $data['cluster_id'];
					if (empty($fileTitle)) {
						$fileTitle = $data['title'];
					}
				}
			}
		}

		$uniqueClusters = array_values(array_unique($allClusters));

		if ($exportType === 'combined') {
			$downloadUrl = $this->generateCombinedPdf($reportHtmlData);
		} else {
			$downloadUrl = $this->generateZipWithPdfs($reportHtmlData);
		}

		if ($downloadUrl) {
			try {
				$importReportModel->storeReportDetailsForUser($downloadUrl, $uniqueClusters, $userId, $fileTitle);
			} catch (\Throwable $e) {
				Log::add("Error calling storeReportDetailsForUser: " . $e->getMessage(), Log::ERROR, 'com_dpe');
			}
		}
	}

    /**
     * Helper method to prepare the HTML content for a single report.
     * Centralizes the template, CSS, and header/footer logic.
     *
     * @param   array   $reportData  Data for a single report record.
     * @param   string  $imageUrl    The absolute URL to the branding logo.
     *
     * @return  string  The fully prepared HTML string for mPDF.
     */
    private function prepareReportHtml($reportData, $imageUrl)
    {
        $reportData['title']        = html_entity_decode($reportData['title'], ENT_QUOTES | ENT_HTML5);
        $reportData['organisation'] = html_entity_decode($reportData['organisation'], ENT_QUOTES | ENT_HTML5);

        $htmlContent = $reportData['html'];

        // Insert page break between sections if needed (internal breaks within one report)
        $htmlContent = preg_replace(
            '/(<\/div>\s*)<div class="tab-section">/i',
            '$1<div class="page-break"></div><div class="tab-section">',
            $htmlContent
        );

        // Clean HTML
        $htmlContent = str_replace(['<em></em>', '<br></div>'], ['&nbsp;', '</div>'], $htmlContent);

        return '<!DOCTYPE html>
        <html>
        <head>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta charset="utf-8"/>
        <style>
        @page{margin-top:120px;margin-bottom:80px;header:html_myHeader;footer:html_myFooter}
        body{font-family:"Open Sans",sans-serif!important;margin-bottom:60px;padding:0;margin-left:25px}
        .content-wrapper{margin-top:120px;display:flex;justify-content:space-between;align-items:center;gap:10px}
        .value{padding:8px;display:inline-block;word-wrap:break-word;vertical-align:top}
        .accordspan{text-decoration:underline;margin-top:10px border-bottom: 1px solid #000;font-size: 18px;font-weight: 600;}.detailnumeric{width:65%}
        .section{margin-top:20px}
        .label {flex: 1;padding: 10px;width: 30%;display: inline-block;
            padding: 8px;word-wrap: break-word;vertical-align: top;font-weight: 400;} .numericcalculation {background: #d3d3da; padding: 4px;    width: 100%;font-weight: 700;color: white;margin-left: -1px;    text-align: center;font-size: inherit;} .value{flex:1;width:65%;color:#777}.section-header{font-size:30px;color:#333;margin:20px 0 10px;font-weight:400}
            .feedback-row{padding-top:5px;padding-bottom:15px;width:65%}.feedback-content{color:#000;font-size:.9em}
            a{color:#22b8f0;text-decoration:none}img{max-width:200px;max-height:150px;margin:5px 0}
            .page-break{page-break-before:always}.page-number:before{content:counter(page);font-size:12px;color:grey}
            .headingOfReport{font-size:40px;color:#000;font-weight:700;text-align:center;margin-top:10px}
            .flex-container{display:flex;align-items:flex-start;gap:10px}
            .numericcalculation{background:#d3d3da;padding:4px;width:100%;font-weight:700;color:#fff;margin-left:-1px;text-align:center;font-size:inherit}.freetextMod { width: 100% !important; font-size: 16px !important; line-height: 25px !important;}
            </style>
            </head>
            <body>

            <htmlpageheader name="myHeader" style="margin-bottom:20px;">
            <table width="100%">
            <tr>
            <td width="60%">
            <p style="margin: 0;">' . $reportData['title'] . '</p>
            <p style="margin: 0;">' . $reportData['organisation'] . ' ' . $reportData['date'] . '</p>
            </td>
            <td width="40%" align="right">
            <img src="' . $imageUrl . '" style="height: 40px;">
            </td>
            </tr>
            </table>
            </htmlpageheader>

            <htmlpagefooter name="myFooter">
            <hr style="border-top: 1px solid black;">
            <table width="100%">
            <tr>
            <td align="right" style="font-size: 10px;">Page {PAGENO} of {nbpg}</td>
            </tr>
            </table>
            </htmlpagefooter>

            <div class="content-wrapper">
            <div class="section" style="text-align: center; padding-top: 90px;">
            <h1 class="headingOfReport">' . $reportData['title'] . '</h1>
            <h1 class="headingOfReport">Report</h1><br>
            <h3 style="font-size: 24px; margin-top: 30px;">' . $reportData['organisation'] . '</h3>
            <h4 style="font-size: 24px; margin-top: 25px;">' . $reportData['conductedBy'] . '</h4>
            <h4 style="font-size: 24px; margin-top: 20px;">' . $reportData['date'] . '</h4>
            </div>
            </div>

            <div class="page-break"></div>
            ' . $htmlContent . '

            </body>
            </html>';
    }

    /**
     * Generates a single merged PDF for multiple reports.
     * Reuses the same mPDF instance for all records and wraps the result in a ZIP.
     *
     * @param   array  $reportDataArray  The array of report data to be merged.
     *
     * @return  string|false  URL of the generated ZIP file containing the single PDF.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function generateCombinedPdf($reportDataArray)
    {
        if (empty($reportDataArray))
        {
            return false;
        }

        $config   = Factory::getConfig();
        $liveSite = $config->get('live_site');
        $imageUrl = $liveSite . "/images/DataProtectionEd_Logo150H.jpg";

        try {
            $mpdf = new \Mpdf\Mpdf([
                "mode" => "utf-8",
                "format" => "A4",
                "shrink_tables_to_fit" => 0,
                "default_font_size" => 13,
                "allow_output_buffering" => true,
            ]);

            $first = true;
            foreach ($reportDataArray as $reportData) {
                if (!$first) {
                    $mpdf->AddPage();
                }

                $html = $this->prepareReportHtml($reportData, $imageUrl);
                $mpdf->WriteHTML($html);
                $first = false;
            }

            $titleClean = $this->cleanFilename(html_entity_decode($reportDataArray[0]['title'], ENT_QUOTES | ENT_HTML5));
            $organisationClean = $this->cleanFilename(html_entity_decode($reportDataArray[0]['organisation'], ENT_QUOTES | ENT_HTML5));
            $pdfFilename = "Combined_" . str_replace(' ', '_', $titleClean) ."_" . str_replace(' ', '_', $organisationClean) .  "_" . date('YmdHis') . ".pdf";
            $pdfPath = JPATH_SITE . "/tmp/" . $pdfFilename;
            
            $mpdf->Output($pdfPath, \Mpdf\Output\Destination::FILE);

            // Zip the combined PDF
            $titleDecoded = html_entity_decode($reportDataArray[0]['title'], ENT_QUOTES | ENT_HTML5);
            $organisationDecoded = html_entity_decode($reportDataArray[0]['organisation'], ENT_QUOTES | ENT_HTML5);
            
            $titleClean = $this->cleanFilename($titleDecoded);
            $organisationClean = $this->cleanFilename($organisationDecoded);
            
            $zipFilename = "Reports_Combined_" . str_replace(' ', '_', $titleClean) . 
                          "_" . str_replace(' ', '_', $organisationClean) . 
                          "_" . date('YmdHis') . ".zip";
            $zipPath = JPATH_SITE . "/tmp/" . $zipFilename;
            $zip = new ZipArchive();

            if ($zip->open($zipPath, ZipArchive::CREATE) === true) {
                $zip->addFile($pdfPath, basename($pdfPath));
                $zip->close();
                
                if (file_exists($pdfPath)) {
                    @unlink($pdfPath);
                }
                
                return $liveSite . "/tmp/" . $zipFilename;
            } else {
                return $liveSite . "/tmp/" . $pdfFilename;
            }

        } catch (\Throwable $e) {
            Log::add("Combined PDF generation failed: " . $e->getMessage(), Log::ERROR, 'com_dpe');
            return false;
        }
    }


    /**
     * Method to sanitize a string for safe use as a filename on Windows systems.
     * Replaces characters not allowed in Windows filenames (\/:*?"<>|) with underscores.
     *
     * @param   string  $string  The input string to be sanitized.
     *
     * @return  string  The sanitized string safe to use in file names.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function cleanFilename($string) {
        // Replace all characters not allowed in Windows filenames
        return preg_replace('/[\/:*?"<>|]/', '_', $string);
    }
	
    /**
     * Method to create pdf reports of ucm forms and pushed it into zip files and share the 
     * url of the zip to download.
     *
     * @return  Json repsonse 
     *
     * @since   __DEPLOY_VERSION__
     */
    public function generateZipWithPdfs($reportDataArray)
    {

    	if (empty($reportDataArray))
    	{
    		return false;
    	}

    	$config   = Factory::getConfig();
    	$liveSite = $config->get('live_site');
		
		$pdfFolder = JPATH_SITE . "/tmp/generatedpdf_".date('YmdHis')."/";
    	
    	if (!Folder::exists($pdfFolder)) {
    		Folder::create($pdfFolder);
    	}
    	
    	$imageUrl = $liveSite . "/images/DataProtectionEd_Logo150H.jpg";
    	$pdfFiles = [];

    	foreach ($reportDataArray as $key => $reportData) {
    		
    	    $html = $this->prepareReportHtml($reportData, $imageUrl);

    			 try {
    			 	$mpdf = new \Mpdf\Mpdf([
    			"mode" => "utf-8",
    			"format" => "A4",
    			"shrink_tables_to_fit" => 0,
    			"default_font_size" => 13,
    			"allow_output_buffering" => true,
    		]);
			        $mpdf->WriteHTML($html);

					$titleDecoded        = html_entity_decode($reportData['title'], ENT_QUOTES | ENT_HTML5);
					$organisationDecoded = html_entity_decode($reportData['organisation'], ENT_QUOTES | ENT_HTML5);

					$title        = $this->cleanFilename($titleDecoded);
					$organisation = $this->cleanFilename($organisationDecoded);

			        $pdfFilename = str_replace(' ', '_', $title) .
			            '_' . str_replace(' ', '_', $organisation) .
			            '_' . date('YmdHis') . "_$key.pdf";

			        $pdfPath = $pdfFolder . "/" . $pdfFilename;
			        $mpdf->Output($pdfPath, \Mpdf\Output\Destination::FILE);

			        $pdfFiles[] = $pdfPath;

			        unset($mpdf); // Free memory
			        gc_collect_cycles(); // Force garbage collection
			    } catch (\Mpdf\MpdfException $e) {
			        file_put_contents('/tmp/pdf_error_log.txt', "PDF $key failed: " . $e->getMessage() . PHP_EOL, FILE_APPEND);
			    }
    		}

    // Create ZIP file with all PDFs
    		$titleDecoded        = html_entity_decode($reportData['title'], ENT_QUOTES | ENT_HTML5);
    		$organisationDecoded = html_entity_decode($reportData['organisation'], ENT_QUOTES | ENT_HTML5);

    		$titleClean        = $this->cleanFilename($titleDecoded);
    		$organisationClean = $this->cleanFilename($organisationDecoded);
    		
    		$zipFilename = "Reports_" . str_replace(' ', '_', $titleClean) . 
    		              "_" . str_replace(' ', '_', $organisationClean) . 
    		              "_" . date('YmdHis') . ".zip";
  	
    		$zipPath = JPATH_SITE . "/tmp/" . $zipFilename;
    		$zip = new ZipArchive();

    		$zipStatus = $zip->open($zipPath, ZipArchive::CREATE);
    		if ($zipStatus === true) {
    			foreach ($pdfFiles as $pdfFile) {
    				$zip->addFile($pdfFile, basename($pdfFile));
    			}
    			$zip->close();
    		} else {
    			echo "Zip creation failed with code: $zipStatus";
    		}

    // Cleanup PDF files and folder
    		Folder::delete($pdfFolder);
    		return $liveSite . "/tmp/" . $zipFilename;
    	}


	/**
	 * Method to dynamically load specific tab fieldsets via AJAX in the DPE component.
	 * This pulls fieldset layouts from com_tjucm based on the provided client, view, and content ID.
	 * 
	 * The generated HTML is rendered using the `form.fieldset_ajax` layout and returned directly
	 * to the browser for partial page updates (lazy loading).
	 *
	 * @return  void  Outputs HTML directly and terminates the application.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function loadTabFields()
	{
		// Get Joomla application & input
		$app   = JFactory::getApplication();
		$input = $app->input;

		// Retrieve request data
		$fieldsetName = $input->get('fieldset', '', 'RAW'); // Fieldset name from AJAX request
		$copyRecID    = $input->getInt('copyRecID');        // Record ID to copy from
		$client       = $input->get('client', '', 'CMD');   // Client identifier e.g., com_dpe.itemform
		$contentId    = $input->getInt('content_id');       // Content ID for the form data

		// Ensure view array from client param (format: component.view)
		$viewParts = explode('.', $client);
		$viewName  = isset($viewParts[1]) ? $viewParts[1] : 'itemform';

		// Load model from com_tjucm
		JModelLegacy::addIncludePath(JPATH_SITE . '/components/com_tjucm/models', 'TjucmModel');
		$model = JModelLegacy::getInstance('ItemForm', 'TjucmModel');

		// Get the form object
		$form = $model->getFormExtra([
			"clientComponent" => 'com_tjucm',
			"client"          => $client,
			"view"            => $viewName,
			"layout"          => 'edit',
			"content_id"      => $contentId,
		]);

		// Handle form loading failure
		if (!$form instanceof JForm)
		{
			echo '<div class="alert alert-warning">Form could not be loaded. Check client/view/layout/content_id.</div>';
			$app->close();
		}

		// Prepare data for layout rendering
		$layoutData = [
			'fieldset'    => $fieldsetName,
			'form'        => $form,
			'layouts'     => new JLayoutFile('field.input'),
			'copyRecId'   => $copyRecID,
			'content_id'  => $contentId,
			'client'      => $client
		];

		// Load & render the custom AJAX fieldset layout from com_dpe
		$layout = new JLayoutFile('form.fieldset_lazyload', JPATH_SITE . '/components/com_dpe/layouts');
		echo $layout->render($layoutData);

		// End execution to prevent extra output
		$app->close(); 
	}

}



