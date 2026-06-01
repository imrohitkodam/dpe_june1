<?php
/**
 * @package     DPE
 * @subpackage  com_dpe
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\MVC\Model\FormModel;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Form\Form;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Language\Multilanguage;
use Joomla\Filesystem\Folder;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Component\ComponentHelper;

/**
 * School import model class.
 *
 * @since  1.0.0
 */
class DpeModelSchoolImport extends FormModel
{
	/**
	 * Method to get the table
	 *
	 * @param   string  $type    Name of the Table class
	 * @param   string  $prefix  Optional prefix for the table class name
	 * @param   array   $config  Optional configuration array for Table object
	 *
	 * @return  Table|boolean Table if found, boolean false on failure
	 */
	public function getTable($type = 'multiagency', $prefix = 'MultiagencyTable', $config = array())
	{
		$this->addTablePath(JPATH_ADMINISTRATOR . '/components/com_multiagency/tables');

		return Table::getInstance($type, $prefix, $config);
	}

	/**
	 * Method to get the profile form.
	 *
	 * Uses existing multiagency form with extra fields from Joomla
	 *
	 * @param   array    $data      An optional array of data for the form to interogate.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return    Form    A Form object on success, false on failure
	 *
	 * @since    1.0.0
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Use existing multiagency form
		$form = $this->loadForm(
			'com_multiagency.multiagency',
			'multiagencyform',
			array(
				'control'   => 'jform',
				'load_data' => $loadData
			)
		);

		if (empty($form))
		{
			return false;
		}

		return $form;
	}

	/**
	 * Get all CSV data rows
	 *
	 * @param   string  $fileName    file name
	 * @param   INT     $cluster_id  cluster id
	 *
	 * @return  ARRAY
	 *
	 * @since  1.0.0
	 */
	public function getAllCsvData($fileName, $cluster_id)
	{
		$user = Factory::getUser();
		$result = new stdClass;
		$result->success = true;
		$result->fileName = $fileName;
		$result->cluster_id = $cluster_id;

		$result->total_records = 0;
		$result->new_schools = 0;
		$result->already_exist = 0;
		$result->bad_schools = 0;
		$result->missing_data = 0;
		$result->miss_cols = 0;
		$result->invalid_records = 0;

		// Load multiagency model
		BaseDatabaseModel::addIncludePath(
			JPATH_SITE . '/components/com_multiagency/models'
		);

		// Get the Form model
		$userImportModel = BaseDatabaseModel::getInstance(
			'UserImport',
			'MultiagencyModel',
			['ignore_request' => true]
		);
		try 
		{
			// Check if file exists
			$filePath = JPATH_SITE . '/tmp/' . $fileName;
			if (!file_exists($filePath))
			{
				$result->success = false;
				$result->message = Text::sprintf('COM_DPE_SCHOOL_IMPORT_FILE_NOT_FOUND', $filePath);
				return $result;
			}

			// Get file handler
			$handle = fopen($filePath, 'r');
			
			if (!$handle)
			{
				$result->success = false;
				$result->message = Text::_('COM_DPE_SCHOOL_IMPORT_OPEN_FAIL');
				return $result;
			}

			// Get the column names from first row
			$colNames = fgetcsv($handle);
			
			if (!$colNames)
			{
				$result->success = false;
				$result->message = Text::_('COM_DPE_SCHOOL_IMPORT_HEADER_FAIL');
				fclose($handle);
				return $result;
			}

			// Log the columns found
			$userImportModel->writeLog($fileName, '', Text::sprintf('COM_DPE_SCHOOL_IMPORT_COLS_FOUND', implode(', ', $colNames)), "INFO");
			
			// Validate required columns
			$requiredColumns = ['organisation_name','county', 'dpe_lead_consultant'];
			$missingColumns = array_diff($requiredColumns, $colNames);
			
			if (!empty($missingColumns))
			{
				$result->success = false;
				$result->message = Text::sprintf('COM_DPE_SCHOOL_IMPORT_MISSING_COLS', implode(', ', $missingColumns));
				$result->miss_cols = 1;
				fclose($handle);
				return $result;
			}

			// Read all data rows (don't process yet)
			$allRows = array();
			$rowCount = 0;
			
			while (($row = fgetcsv($handle)) !== false)
			{
				if (count($colNames) == count($row))
				{
					$rowData   = array_combine($colNames, $row);
					$allRows[] = $rowData;
					$rowCount++;
				}
			}

			fclose($handle);
			
			// Now process all the schools using the model
			$this->processAllSchools($allRows, $fileName, $result);
			
			$result->total_records = $rowCount;
			$result->message = Text::sprintf('COM_DPE_SCHOOL_IMPORT_COMPLETED', $rowCount);

			// Clean up - delete the uploaded file
			if (file_exists($filePath))
			{
				unlink($filePath);
			}
			
			// Also delete pointer file if exists
			$pointerFile = JPATH_SITE . '/tmp/' . $fileName . 'pointer.txt';
			if (file_exists($pointerFile))
			{
				unlink($pointerFile);
			}

		}
		catch (Exception $e)
		{
			$result->success = false;
			$result->message = Text::sprintf('COM_DPE_SCHOOL_IMPORT_PROCESS_ERROR', $e->getMessage());
			
			// Log the full exception
			$userImportModel->writeLog($fileName, '', 'Exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ' line ' . $e->getLine(), "ERROR");
		}

		return $result;
	}

	/**
	 * Process all schools from CSV data
	 *
	 * @param   array     $allRows   All CSV data rows
	 * @param   string    $fileName  File name for logging
	 * @param   stdClass  $result    Result object to update counters
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	private function processAllSchools($allRows, $fileName, &$result)
	{
		$user = Factory::getUser();
		

	
		// Process each school
		foreach ($allRows as $rowData)
		{
			$this->processSchoolRow($rowData, $fileName, $result);
		}
	}

	/**
	 * Process individual school row from CSV
	 *
	 * @param   array     $rowData           School data from CSV row
	 * @param   string    $fileName          File name for logging
	 * @param   stdClass  $result            Result object to update counters
	 * @param   object    $multiagencyModel  Multiagency model instance
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	private function processSchoolRow($rowData, $fileName, &$result)
	{
		$user = Factory::getUser();

		// Load multiagency model
		BaseDatabaseModel::addIncludePath(
			JPATH_SITE . '/components/com_multiagency/models'
		);

		// Get the Form model
		$userImportModel = BaseDatabaseModel::getInstance(
			'UserImport',
			'MultiagencyModel',
			['ignore_request' => true]
		);

		// Check for Organization Name, county, and DPE Lead Consultant
		if (empty(trim($rowData['organisation_name'])) || empty(trim($rowData['county'])) || empty(trim($rowData['dpe_lead_consultant'])))
		{
			$result->missing_data++;
			$result->invalid_records++;
			$userImportModel->writeLog($fileName, $rowData, Text::_('COM_DPE_SCHOOL_IMPORT_MISSING_MANDATORY'), "ERROR");
			return;
		}

		// Validate Lead Consultant
		$leadName = trim($rowData['dpe_lead_consultant']);
		$leadId = $this->getLeadConsultantIdByName($leadName);

		$tagsTittle=trim($rowData['tags']);
		$tagsIds=$this->getTagIdsByTitle($tagsTittle);

		

		if (!$leadId)
		{
			$result->missing_data++;
			$result->invalid_records++;
			$userImportModel->writeLog($fileName, $rowData, Text::sprintf('COM_DPE_SCHOOL_IMPORT_INVALID_LEAD', $leadName), "ERROR");
			return;
		}

		// Add ID to rowData so it can be used in mapping
		$rowData['dpe_lead_consultant'] = $leadId;
		$rowData['tags'] = $tagsIds;


		// Validate email format
		if (!empty(trim($rowData['generic_school_email'])) && !filter_var(trim($rowData['generic_school_email']), FILTER_VALIDATE_EMAIL))
		{
			$result->missing_data++;
			$result->invalid_records++;
			$userImportModel->writeLog($fileName, $rowData, Text::sprintf('COM_DPE_SCHOOL_IMPORT_INVALID_EMAIL', $rowData['generic_school_email']), "ERROR");
			return;
		}

		// Check if school already exists
		$schoolExists = $this->checkSchoolExists(trim($rowData['organisation_name']), 0);
		
		if ($schoolExists)
		{
			$result->already_exist++;
			$userImportModel->writeLog($fileName, $rowData, Text::sprintf('COM_DPE_SCHOOL_IMPORT_ALREADY_EXISTS', $rowData['organisation_name']), "INFO");
			return;
		}

		// Prepare data for multiagency model
		$schoolData = $this->mapCsvToMultiagencyData($rowData, $user);
		
		try 
		{
			// Prepare data for multiagency model save function
			$schoolData = $this->mapCsvToMultiagencyData($rowData, $user);
			
			// Load multiagency model
			BaseDatabaseModel::addIncludePath(
				JPATH_SITE . '/components/com_multiagency/models'
			);

			// Get the Form model
			$multiagencyModel = BaseDatabaseModel::getInstance(
				'MultiagencyForm',
				'MultiagencyModel',
				['ignore_request' => true]
			);
			
			if (!$multiagencyModel) 
			{
				$result->bad_schools++;
				$userImportModel->writeLog($fileName, $rowData, Text::_('COM_DPE_SCHOOL_IMPORT_MODEL_LOAD_FAIL'), "ERROR");
				return;
			}
			
			// Use model save function
			if ($multiagencyModel->save($schoolData))
			{
				$result->new_schools++;
				$userImportModel->writeLog($fileName, $rowData, Text::sprintf('COM_DPE_SCHOOL_IMPORT_CREATED', $rowData['organisation_name']), "INFO");
			}
			else
			{
				$result->bad_schools++;
				$errors = $multiagencyModel->getErrors();
				$error = is_array($errors) ? implode(', ', $errors) : ($multiagencyModel->getError() ?: 'Unknown error occurred');
				$userImportModel->writeLog($fileName, $rowData, Text::sprintf('COM_DPE_SCHOOL_IMPORT_SAVE_FAILED', $error), "ERROR");
			}
		}
		catch (Exception $e)
		{
			$result->bad_schools++;
			$userImportModel->writeLog($fileName, $rowData, Text::sprintf('COM_DPE_SCHOOL_IMPORT_EXCEPTION', $e->getMessage()), "ERROR");
		}
	}





	/**
	 * Map CSV data to multiagency model format
	 *
	 * @param   array  $csvData  CSV row data
	 * @param   object $user     Current user object
	 *
	 * @return  array  Formatted data for multiagency model
	 *
	 * @since   1.0.0
	 */
	private function mapCsvToMultiagencyData($csvData, $user)
	{
		$data = array(
			'id' => '',
			'ordering' => 0,
			'state' => 1,
			'checked_out' => $user->id,
			'title' => trim($csvData['organisation_name']),
			'tags' => $csvData['tags'],
			'lead_consultant_id' => $csvData['dpe_lead_consultant'],
			'com_fields' => array()
		);

		// Map CSV fields to com_fields array
		$fieldMapping = array(
			'organisation_telephone' => 'school-telephone',
			'generic_school_email' => 'generic-school-email',
			'website' => 'website',
			'address' => 'address',
			'address_2' => 'address2',
			'county' => 'county-location',
			'town_city' => 'town-city',
			'postcode' => 'postcode',
			'dpe_region' => 'dpe-region',
			'notes' => 'notes',
			'ico_registration_number' => 'ico-registration-number',
			'name_of_finance_contact' => 'name-of-admin-contact',
			'gdpr_lead_name' => 'name-of-gdpr-lead',
			'gdpr_lead_title' => 'gdpr-lead-title',
			'gdpr_lead_email' => 'gdpr-lead-email-if-not-on-kb',
			'financial_year_start' => 'financial-year-start',
			'stage' => 'stage',
			'source_of_lead' => 'source-of-lead',
			'dpe_sales_lead' => 'dpe-sales-lead',
			'school_sales_contact' => 'school-sales-contact',
			'school_sales_contact_email' => 'school-sales-contact-email',
			'school_sales_contact_telephone' => 'school-sales-contact-telephone',
			'single_or_MAT/cluster' => 'single-or-mat',
			'number_of_organisations' => 'number-of-schools',
			'contract_value_per_year' => 'contract-value',
			'date_lead_added' => 'date-lead-added',
			'expected_close' => 'expected-close',
			'actual_close(won_only)' => 'actual-close-won-only',
			'date_lost_or_archived' => 'date-lost-or-archived',
			'length_of_contract' => 'length-of-contract',
			'probability_of_closing' => 'probability-of-closing',
			'next_steps/comments' => 'next-steps-comments'
		);

		// Initialize com_fields with default values
		$data['com_fields'] = array(
			'school-telephone' => '',
			'generic-school-email' => '',
			'website' => '',
			'address' => '',
			'address2' => '',
			'address3' => '',
			'county-location' => '',
			'town-city' => '',
			'file' => array(),
			'postcode' => '',
			'dpe-region' => 'Select...',
			'notes' => 'e.g. ease of access, car parking etc',
			'ico-registration-number' => '',
			'dpe-registered-as-dpo' => 'Don\'t know',
			'name-of-admin-contact' => '',
			'name-of-gdpr-lead' => '',
			'gdpr-lead-title' => '',
			'gdpr-lead-email-if-not-on-kb' => '',
			'financial-year-start' => 'Select...',
			'stage' => 'Select...',
			'source-of-lead' => 'Select...',
			'dpe-sales-lead' => '',
			'school-sales-contact' => '',
			'school-sales-contact-email' => '',
			'school-sales-contact-telephone' => '',
			'single-or-mat' => 'Single school',
			'number-of-schools' => '0',
			'contract-value' => '0',
			'date-lead-added' => '',
			'expected-close' => '',
			'actual-close-won-only' => '',
			'date-lost-or-archived' => '',
			'length-of-contract' => '1',
			'probability-of-closing' => 'Select...',
			'next-steps-comments' => '',
			'source-details' => '',
			'chargeable-work' => array()
		);

		// Map CSV data to com_fields
		foreach ($fieldMapping as $csvField => $comField)
		{
			if (isset($csvData[$csvField]) && !empty(trim($csvData[$csvField])))
			{
				$data['com_fields'][$comField] = trim($csvData[$csvField]);
			}
		}

		return $data;
	}

	/**
	 * Check if school exists.
	 *
	 * @param   string  $schoolName  School name
	 * @param   int     $clusterId   Cluster ID
	 *
	 * @return  int  Return school id if exists, false otherwise
	 *
	 * @since   1.0
	 */
	public function checkSchoolExists($schoolName, $clusterId = 0)
	{
		if ($schoolName)
		{
			$db = Factory::getDbo();

			// Check if school already exists in multiagency table
			$query = $db->getQuery(true);

			$query->select($db->quoteName(array('id')));
			$query->from($db->quoteName('#__tjmultiagency_multiagency'));
			$query->where($db->quoteName('title') . ' = ' . $db->quote($schoolName));
			$query->where($db->quoteName('state') . ' != -2'); // Not trashed

			$db->setQuery($query);

			return $db->loadResult();
		}

		return false;
	}

	/**
	 * Upload file
	 *
	 * @return  boolean  Return true.
	 *
	 * @since   1.0
	 */
	public function upload()
	{
		$app                         = Factory::getApplication();
		$input                       = $app->input;
		$server                      = $input->server;
		$REQUEST_METHOD              = $server->get('REQUEST_METHOD', '', 'STRING');
		$post                        = $input->post;
		$response['validate']        = new stdclass;
		$response['validate']->error = 0;
		$response['fileUpload']      = new stdclass;

		// Check if request is GET and the requested chunk exists or not. this makes testChunks work
		if ($REQUEST_METHOD === 'GET')
		{
			$temp_dir   = JPATH_SITE . '/tmp/' . $input->get('resumableIdentifier');
			$chunk_file = $temp_dir . '/' . $input->get('resumableFilename') . '.part' . $input->get('resumableChunkNumber');

			if (file_exists($chunk_file))
			{
				header("HTTP/1.0 200 Ok");
			}
			else
			{
				header("HTTP/1.0 404 Not Found");
			}
		}

		// Loop through files and move the chunks to a temporarily created directory
		if (!empty($_FILES))
		{
			foreach ($_FILES as $file)
			{
				// Check the error status
				if ($file['error'] != 0)
				{
					$response['validate']->error = 1;
					continue;
				}

				/* Init the destination file (format <filename.ext>.part<#chunk>
				 the file is stored in a temporary directory
				*/
				$temp_dir  = JPATH_SITE . '/tmp/' . $post->get('resumableIdentifier');
				$dest_file = $temp_dir . '/' . $post->get('resumableFilename') . '.part' . $post->get('resumableChunkNumber');

				// Create the temporary directory
				if (!is_dir($temp_dir))
				{
					Folder::create($temp_dir);
				}

				// Move the temporary file
				if (!File::move($file['tmp_name'], $dest_file))
				{
					$response['validate']->error = 1;
				}
				else
				{
					// Check if all the parts present, and create the final destination file
					$fileName = $this->createFileFromChunks(
						$temp_dir, $post->get('resumableFilename'),
						$post->get('resumableChunkSize'),
						$post->get('resumableTotalSize')
						);

					if ($fileName)
					{
						$response['fileUpload']->complete = 1;
						$response['fileUpload']->fileName = $fileName;
					}
					else
					{
						$response['fileUpload']->complete = 0;
					}
				}
			}
		}

		header('Content-type: application/json');
		echo new JsonResponse($response);
		jexit();
	}

	/**
	 * Create file from chunks
	 *
	 * @param   string  $temp_dir   the temporary directory holding all the parts of the file
	 * @param   string  $fileName   the original file name
	 * @param   string  $chunkSize  each chunk size (in bytes)
	 * @param   string  $totalSize  original file size (in bytes)
	 *
	 * @return  string|boolean  filename on success, false on failure
	 */
	public function createFileFromChunks($temp_dir, $fileName, $chunkSize, $totalSize)
	{
		// Count all the parts of this file
		$total_files = 0;

		foreach (scandir($temp_dir) as $file)
		{
			if (stripos($file, $fileName) !== false)
			{
				$total_files++;
			}
		}

		// Check that all the parts are present the size of the last part is between chunkSize and 2*$chunkSize
		if ($total_files * $chunkSize >= ($totalSize - $chunkSize + 1))
		{
			// Create the final destination file
			if (($fp = fopen(JPATH_SITE . '/tmp/' . $fileName, 'w')) !== false)
			{
				for ($i = 1; $i <= $total_files; $i++)
				{
					fwrite($fp, file_get_contents($temp_dir . '/' . $fileName . '.part' . $i));
				}

				fclose($fp);

				// Concurrent chunks uploads) and than delete it
				if (rename($temp_dir, $temp_dir . '_UNUSED'))
				{
					$this->rrmdir($temp_dir . '_UNUSED');
				}
				else
				{
					$this->rrmdir($temp_dir);
				}

				// Lets make a unique safe file name for each upload
				$name     = JPATH_SITE . '/tmp/' . $fileName;
				$fileInfo = pathinfo($name);
				$fileExt  = $fileInfo['extension'];
				$fileBase = $fileInfo['filename'];

				// Add logged in userid to file name
				$fileBase = Factory::getUser()->id . '_' . $fileBase;

				// Add timestamp to file name
				$timestamp = microtime();
				$fileBase = $fileBase . '_' . $timestamp;

				// Clean up filename to get rid of strange characters like spaces etc
				$fileBase = File::makeSafe($fileBase);

				// Lose any special characters in the filename
				$fileBase = preg_replace("/[^A-Za-z0-9]/i", "_", $fileBase);

				// Use lowercase
				$fileBase = strtolower($fileBase);

				$finalFileName = $fileBase . '.' . $fileExt;

				if (rename($name, JPATH_SITE . '/tmp/' . $finalFileName))
				{
					return $finalFileName;
				}
			}
			
			return false;
		}

		// Not all chunks received yet
		return false;
	}

	public function rrmdir($dir)
	{
		if (is_dir($dir))
		{
			$objects = scandir($dir);

			foreach ($objects as $object)
			{
				if ($object != "." && $object != "..")
				{
					if (filetype($dir . "/" . $object) == "dir")
					{
						$this->rrmdir($dir . "/" . $object);
					}
					else
					{
						File::delete($dir . "/" . $object);
					}
				}
			}

			reset($objects);
			rmdir($dir);
		}
	}

	/**
	 * Get Lead Consultant ID by Name
	 * Checks against allowed groups from com_multiagency config
	 *
	 * @param   string  $name  User Name
	 *
	 * @return  int|boolean    User ID if found and authorized, false otherwise
	 */
	private function getLeadConsultantIdByName($name)
	{
		static $consultantMap = null;

		if (empty($name))
		{
			return false;
		}

		// Build the map once
		if ($consultantMap === null)
		{
			$consultantMap = array();
			$user = Factory::getUser();
			$params = ComponentHelper::getParams('com_multiagency');
			$dpeAdminGroupId = (int) $params->get('multiagency_admin_group', '0');
			$leadConsultantGroupId = (int) $params->get('multiagency_leadconsultant_group', '0');

			$db = Factory::getDbo();

			// 1. Get DPE Admin List
			if ($dpeAdminGroupId)
			{
				$query = $db->getQuery(true);
				$query->select(array('u.id', 'u.name'));
				$query->from('`#__users` AS u');
				$query->join('LEFT', $db->quoteName('#__user_usergroup_map', 'map') . ' ON ' . $db->quoteName('u.id') . ' = ' . $db->quoteName('map.user_id'));
				$query->where($db->quoteName('map.group_id') . '= ' . (int) $dpeAdminGroupId);
				$query->where('u.block = 0');
				$query->group($db->quoteName('u.id'));
				
				$db->setQuery($query);
				$admins = $db->loadObjectList();
				
				if ($admins)
				{
					foreach ($admins as $admin)
					{
						$consultantMap[trim($admin->name)] = $admin->id;
					}
				}
			}

			// 2. Get External LC List (if authorized)
			if ($leadConsultantGroupId && $user->authorise('core.manageall', 'com_cluster'))
			{
				$query = $db->getQuery(true);
				$query->select(array('u.id', 'u.name'));
				$query->from('`#__users` AS u');
				$query->join('LEFT', $db->quoteName('#__user_usergroup_map', 'map') . ' ON ' . $db->quoteName('u.id') . ' = ' . $db->quoteName('map.user_id'));
				$query->where($db->quoteName('map.group_id') . '= ' . (int) $leadConsultantGroupId);
				$query->where('u.block = 0');
				$query->group($db->quoteName('u.id'));
				
				$db->setQuery($query);
				$lcs = $db->loadObjectList();
				
				if ($lcs)
				{
					foreach ($lcs as $lc)
					{
						$consultantMap[trim($lc->name)] = $lc->id;
					}
				}
			}
		}

		$trimmedName = trim($name);
		
		if (isset($consultantMap[$trimmedName]))
		{
			return $consultantMap[$trimmedName];
		}
		
		// Try case-insensitive scan if not found
		foreach ($consultantMap as $cName => $cId)
		{
			if (strcasecmp($cName, $trimmedName) == 0)
			{
				return $cId;
			}
		}

		return false;
	}

	/**
	 * Get tgas ID by Title
	 * Checks against allowed groups from com_multiagency config
	 *
	 * @param   string  $title  tags names with array
	 *
	 * @return  array    Tags ID if found and authorized, empty array otherwise
	 */
	private function getTagIdsByTitle($titles): array
	{
		// Convert comma-separated string → array
		if (is_string($titles))
		{
			$titles = array_map('trim', explode(',', $titles));
		}

		if (empty($titles) || !is_array($titles))
		{
			return [];
		}

		$db  = Factory::getDbo();
		$app = Factory::getApplication();

		$published = [0, 1];
		$language  = null;

		$query = $db->getQuery(true)
			->select([
				$db->quoteName('a.id', 'value'),
				$db->quoteName('a.title', 'text'),
				$db->quoteName('a.language'),
				$db->quoteName('a.lft'),
				$db->quoteName('a.published')
			])
			->from($db->quoteName('#__tags', 'a'))
			->where($db->quoteName('a.lft') . ' > 0')
			->whereIn($db->quoteName('a.published'), $published)
			->order($db->quoteName('a.lft') . ' ASC');

		// Language filter (Joomla 5/6 safe)
		if ($app->isClient('site') && \Joomla\CMS\Language\Multilanguage::isEnabled())
		{
			if (ComponentHelper::getParams('com_tags')->get('tag_list_language_filter') === 'current_language')
			{
				$language = [$app->getLanguage()->getTag(), '*'];
				$query->whereIn(
					$db->quoteName('a.language'),
					$language,
					ParameterType::STRING
				);
			}
		}

		$db->setQuery($query);

		try
		{
			$rows = $db->loadObjectList();
		}
		catch (\RuntimeException $e)
		{
			return [];
		}

		// Build normalized tag map
		$normalizedMap = [];

		foreach ($rows as $row)
		{
			$normalizedMap[strtolower(trim($row->text))] = (int) $row->value;
		}

		// Match CSV titles → IDs
		$ids = [];

		foreach ($titles as $title)
		{
			$key = strtolower(trim($title));

			if ($key !== '' && isset($normalizedMap[$key]))
			{
				$ids[] = $normalizedMap[$key];
			}
		}

		if(!empty($ids)){
		return array_values(array_unique($ids));
		}else{
			return [];
		}
	}

}