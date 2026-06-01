<?php
/**
 * @package     Multiagency
 * @subpackage  com_multiagency
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

use Joomla\Filesystem\Folder;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\User\UserHelper;
use Joomla\Registry\Registry;

/**
 * File upload controller class.
 *
 * @since  __DEPLOY__VERSION__
 */
class MultiagencyModelUserImport extends FormModel
{
	public $phisingConsent = '0';

	/**
	 * Method to get the table
	 *
	 * @param   string  $type    Name of the Table class
	 * @param   string  $prefix  Optional prefix for the table class name
	 * @param   array   $config  Optional configuration array for Table object
	 *
	 * @return  Table|boolean Table if found, boolean false on failure
	 */
	public function getTable($type = 'User', $prefix = 'MultiagencyTable', $config = array())
	{
		$this->addTablePath(JPATH_ADMINISTRATOR . '/components/com_multiagency/tables');

		return Table::getInstance($type, $prefix, $config);
	}

	/**
	 * Method to get the profile form.
	 *
	 * The base form is loaded from XML
	 *
	 * @param   array    $data      An optional array of data for the form to interogate.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return    Form    A Form object on success, false on failure
	 *
	 * @since    __DEPLOY__VERSION__
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm(
			'com_multiagency.user',
			'userform',
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
	 * Save question to table from csv
	 *
	 * @param   string  $fileName       file name
	 * @param   INT     $notify_user    notify user
	 * @param   INT     $client_id      agency id
	 *
	 * @return  ARRAY
	 *
	 * @since  __DEPLOY__VERSION__
	 */
	public function saveCsvContent($fileName, $notify_user, $client_id)
	{ 
		$user       = Factory::getUser();
		$addOwnUser = RBACL::authorise($user->id, 'com_multiagency', 'core.own.adduser', 'com_multiagency');
		$addUser    = RBACL::authorise($user->id, 'com_multiagency', 'core.adduser', 'com_multiagency');
		$phisingConsentData = $this->phisingConsent; 

		if (!$addOwnUser && !$addUser)
		{
			return false;
		}

		$params          = ComponentHelper::getParams('com_multiagency');
		$totalImportUser = $params->get('import_user_count', '1', 'INT');
		$userData        = [];
		$result          = new stdClass;

		// Get file handler
		$handle = fopen(JPATH_SITE . '/tmp/' . $fileName, 'r');

		// Get the colNames data
		$colNames = $this->getColNames($handle);

		// Get current pointer position
		$fp = $this->getFilePointerPosition($fileName);
		fseek($handle, $fp);

		for ($rowNum = 0; $rowNum < $totalImportUser; $rowNum++)
		{
			$userInfo = fgetcsv($handle);

			// Skip if $fp == 0, If $fp ==0 means these are column heading/Name so ignore it and move to next line to get user data
			if ($fp == 0)
			{
				// Get total records ( minus one from count as first row is for header)
				$result->total_records = count(file(JPATH_SITE . '/tmp/' . $fileName, FILE_SKIP_EMPTY_LINES)) - 1;
				$this->writeLog($fileName, '', Text::_("COM_MULTIAGENCY_IMPORT_INFO_MESG"));
				$fp++;
				continue;
			}

			// If no data left to import
			if ($userInfo === false)
			{
				break;
			}

			// Parsing the data rows
			if (count($colNames) == count($userInfo))
			{
				$userData[] = array_combine($colNames, $userInfo);
			}
		}

		// Set file pointer to updated location
		$this->setFilePointerPosition($fileName, $handle);

		// Check if end of file
		$result->feof = feof($handle);
		fclose($handle);

		$allowedColumns = array('first_name', 'last_name', 'username', 'email', 'job_title');

		// Field validations - Throw error if coloum of csv files are wrong
		foreach ($colNames as $key => $colName)
		{
			if (!in_array($colName, $allowedColumns))
			{
				if (strpos($colName, 'course') || strpos($colName, 'groupId'))
				{
					$output['return'] = 1;
					$output['errormsg'] = Text::sprintf('COM_USER_TITLE_MANAGEENROLLMENTS_IMPORT_ERROR_MSG', $colName, '');
					$output['successmsg'] = '';

					return $output;
				}
			}
		}

		$result->alreadyAssigned = $result->bad_users = $result->new_users = $result->already_exist = 0;
		$result->missing_data    = 0;
		$result->newlyAssigned   = 0;
		$result->updated   		 = 0;
		$result->miss_cols       = 0;
		$result->emptyfile       = count($userData) >= 1 ? 0 : 1;

		if (count($userData))
		{
			$helperPath = JPATH_COMPONENT_SITE . '/helpers/multiagency.php';

			if (!class_exists('MultiagencyFrontendHelpers'))
			{
				// Require_once $path;
				JLoader::register('MultiagencyFrontendHelpers', $helperPath);
				JLoader::load('MultiagencyFrontendHelpers');
			}

			BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_multiagency/models');
			$MultiagencyModel = BaseDatabaseModel::getInstance('MultiagencyForm', 'MultiagencyModel', array('ignore_request' => true));
			$agencyData       = $MultiagencyModel->getData($client_id);
			$helperObject     = new MultiagencyFrontendHelpers;

			$ClusterModel = ClusterFactory::model('Cluster', array('ignore_request' => true));
			$clusterData = $ClusterModel::getClusterByClient('com_multiagency', $client_id);

			$clusterTable = ClusterFactory::table("Clusters");
			$clusterTable->load(array('id' => $client_id));
			foreach ($userData as $eachUser)
			{
					//  DPE Hack 
					// Get the Job title and Match with the value of fields_value table and if exsist then get the conent_id and insert the  content id along with user and cluster details in Job-title_xref table.
					$contentId = "" ;
					$jobTitle = StrToLower($eachUser['job_titles']);

					BaseDatabaseModel::addIncludePath(JPATH_ROOT . '/components/com_dpe/models');
					$schoolModel      = BaseDatabaseModel::getInstance('School', 'DpeModel');
					$clusterJobTitles = $schoolModel->getJobTitlesByClusterId($clusterTable->id);	
					

					$keyOfUcmId       =  array_search($jobTitle, array_map('strtolower', array_column($clusterJobTitles, 'value')));
					
  	                $contentId        =  is_numeric($keyOfUcmId)?$clusterJobTitles[$keyOfUcmId]->id : null;
					
					// If content Id is not present the save the job title and get the content Id.
  	                
  	                if (!$contentId)
  	                {  
	  	                $contentId = $schoolModel->saveJobTitleFromCsv($clusterTable->id, $eachUser['job_titles']);
  	                }

				if (trim($eachUser['first_name']) == '' || trim($eachUser['email']) == '' || (!filter_var(trim($eachUser['email']), FILTER_VALIDATE_EMAIL)) )//|| empty($contentId))
				{
					$message = trim($eachUser['first_name']) == '' ? Text::sprintf('COM_MULTIAGENCY_IMPORT_ERROR', 'first_name') : '';
					$message .= trim($eachUser['email']) == '' ? Text::sprintf('COM_MULTIAGENCY_IMPORT_ERROR', 'email') : '';
					$message .= (!filter_var(trim($eachUser['email']), FILTER_VALIDATE_EMAIL)) ? Text::sprintf('COM_MULTIAGENCY_IMPORT_INVALID_EMAIL', 'email') : '';
					// $message .=  empty($contentId)? Text::sprintf('COM_MULTIAGENCY_IMPORT_INVALID_JOB_TITLE', 'Job Title') : '';

					$result->missing_data++;
					$this->writeLog($fileName, $eachUser, $message, "ERROR");
				}
				else
				{ 

					foreach ($eachUser as $key => $value)
					{

						if (!array_key_exists('first_name', $eachUser) || !array_key_exists('last_name', $eachUser) || !array_key_exists('email', $eachUser))
						{
							$message = !array_key_exists('first_name', $eachUser) ? "first_name" : '';
							$message .= !array_key_exists('last_name', $eachUser) ? " last_name" : '';
							$message .= !array_key_exists('email', $eachUser) ? " email" : '';

							$this->writeLog($fileName, $eachUser, 'Missing- ' . $message, "ERROR");

							$result->miss_cols = 1;
							break;
						}
						if (!empty($value))
						{
							if ($key == 'email')
							{
								$userexist = $this->checkUserExit($value);

								if (!$userexist)
								{
									$key                        = "addUser";
									$randomPassword             = UserHelper::genRandomPassword(6);
									$eachUser['password']       = $randomPassword;
									$eachUser['name']           = htmlspecialchars(trim($eachUser['first_name'])) . ' ' . htmlspecialchars(trim($eachUser['last_name']));
									$eachUser['username']       = trim($eachUser['email']);
									$eachUser['email']          = trim($eachUser['email']);
									$eachUser['title']          = $agencyData->title;
									$eachUser['requireReset']   = 1;
									$eachUser['reset_password'] = 1;


									switch ($params->get('social_integration', '', 'STRING'))
									{
										case "easysocial":
											$userobj = $helperObject->createESuser($eachUser);
											break;
										default:

											$userobj = $helperObject->createnewuser($eachUser);
									}

									if ($userobj->id > 0)
									{

									$this->assignUser($userobj->id, $clusterTable->client_id, $clusterTable->id);
									
										// DPE hack  If content ID present save the  jobtitle details in xref Table

										if ($contentId)
										{   
											$schoolModel->saveJobTitle($clusterTable->id, $userobj->id, $contentId);
										}

										// Job Title end

										// DPE Hack to dont send user credential to phising related users.
										if(!$phisingConsentData)
										{
											$helperObject->SendMailNewUser($eachUser, $randomPassword, $key);
										}
										//Hack End

										$result->new_users++;
									}
									else
									{
										// User already present or invalid user id
										$result->bad_users++;
										$this->writeLog($fileName, $eachUser, Text::_("COM_MULTIAGENCY_IMPORT_USER_ERROR"), "ERROR");
									}
								}
								else
								{
									$userAssigned = RBACL::getRoleByUser($userexist, 'com_multiagency');

									if (empty($userAssigned))
									{
										$this->assignUser($userexist, $clusterTable->client_id, $clusterTable->id);
										$result->newlyAssigned++;
									}
									elseif($jobTitle)
									{
										$schoolModel->saveJobTitle($clusterTable->id, $userexist, $contentId);
										$result->updated++; 
										$result->alreadyAssigned++;
									}
									else
									{
										// Already assigned user
										$result->alreadyAssigned++;
									}

									// If old user check alredy entry created for enrollment for same user same course.
									$result->already_exist++;
								}
							}
						}
					}
				}
			}
		}

		// Delete file once import is done

		if ($result->feof == true)
		{
			File::delete(JPATH_SITE . '/tmp/' . $fileName);
			File::delete(JPATH_SITE . '/tmp/' . $fileName . 'pointer.txt');
		}

		return $result;
	}

	/**
	 * Write the log
	 *
	 * @param   string  $fileName  File Name
	 * @param   array   $data      Data
	 * @param   string  $message   Log message
	 * @param   string  $type      Message type
	 *
	 * @return  void
	 *
	 * @since 1.0
	 */
	public function writeLog($fileName, $data, $message, $type = "INFO")
	{
		$logData = $data;

		if (is_array($data))
		{
			$logData = implode(',', $data);
		}

		// Add to log
		$logFields = [
			"data" => $logData,
			"message" => $message,
		];

		// Convert logFields to string implode by pipe(|)
		$logMessage = implode(
			' | ',
			array_map(
				function ($v, $k)
				{
					if (is_array($v))
					{
						return $k . '[]: ' . implode('&' . $k . '[]: ', $v);
					}
					else
					{
						return $k . ': ' . $v;
					}
				},
				$logFields,
				array_keys($logFields)
			)
		);
		Log::addLogger(
			array(
				'text_file'         => $fileName . 'log.php',
				'text_entry_format' => '{DATETIME} | {PRIORITY} | {MESSAGE}'
			),
			Log::ALL,
			array($category = 'tjlogs')
		);

		$priority = ($type == "INFO") ? Log::INFO : Log::ERROR;
		Log::add($logMessage, $priority, $category = 'tjlogs');
	}

	/**
	 * Get column name
	 *
	 * @param   object  $handle  File object
	 *
	 * @return  array  colNames
	 *
	 * since __DEPLOY_VERSION
	 */
	public function getColNames($handle)
	{
		$colNames = fgetcsv($handle);

		return $colNames;
	}

	/**
	 * get File pointer position
	 *
	 * @param   string  $fileName  File nanme
	 *
	 * @return  int  file pointer position
	 *
	 * @since   1.0
	 */
	public function getFilePointerPosition($fileName)
	{
		$fpFile = JPATH_SITE . '/tmp/' . $fileName . "pointer.txt";
		$fp = 0;

		if (File::exists($fpFile))
		{
			$fp = file_get_contents(JPATH_SITE . '/tmp/' . $fileName . "pointer.txt");
		}
		else
		{
			File::write($fpFile, $fp);
		}

		return $fp;
	}

	/**
	 * get File pointer position
	 *
	 * @param   string  $fileName  File nanme
	 * @param   string  $handle    File handler
	 *
	 * @return  boolean  true/false
	 *
	 * @since   1.0
	 */
	public function setFilePointerPosition($fileName, $handle)
	{
		$fpFile = JPATH_SITE . '/tmp/' . $fileName . "pointer.txt";
		$fpNow  = ftell($handle);

		if (!File::write($fpFile, $fpNow))
		{
			return false;
		}

		return true;
	}

	/**
	 * Check user exist in joomla.
	 *
	 * @param   string  $useremail  login user email.
	 *
	 * @return  int  Return user id.
	 *
	 * @since   1.0
	 */
	public function checkUserExit($useremail)
	{
		if ($useremail)
		{
			$db = Factory::getDbo();

			// Check the customer id (in users table) already exist or not
			$query = $db->getQuery(true);

			$query->select($db->quoteName(array('id')));
			$query->from($db->quoteName('#__users'));
			$query->where($db->quoteName('email') . ' = ' . $db->quote($useremail) . 'OR' . $db->quoteName('username') . ' = ' . $db->quote($useremail));

			$db->setQuery($query);

			return $db->loadResult();
		}
	}

	/**
	 * Check user exist in joomla.
	 *
	 * @param   INT  $userId         login user email.
	 * @param   INT  $multiagencyId  agency id.
	 * @param   INT  $clusterId      cluster id.
	 *
	 * @return  boolean  Return true.
	 *
	 * @since   1.0
	 */
	public function assignUser($userId, $multiagencyId, $clusterId)
	{
		if ($userId && $multiagencyId && $clusterId)
		{
			$helperPath = JPATH_COMPONENT_SITE . '/helpers/multiagency.php';

			if (!class_exists('MultiagencyFrontendHelpers'))
			{
				// Require_once $path;
				JLoader::register('MultiagencyFrontendHelpers', $helperPath);
				JLoader::load('MultiagencyFrontendHelpers');
			}

			$helperObject = new MultiagencyFrontendHelpers;
			
			PluginHelper::importPlugin('system');
			$params = ComponentHelper::getParams('com_multiagency');
			$memberRoleId = $params['member_role_id'];

			$dpeParams              = ComponentHelper::getParams('com_dpe');
			$additionalRole         = new Registry($dpeParams->get('additional_role'));
			$roleIdstoSaved         = array();
			$licenceAssignedRoleIds = $helperObject->getLicenceAssignedRoleIds($multiagencyId);
			$dpeTools               = new Registry($dpeParams->get('dpe_role_ids'));
			$managerRoleId          = $params->get('member_role_id', '0', 'INT');

			if (count($licenceAssignedRoleIds))
			{
				$additionRoles = array_intersect($dpeTools->get($memberRoleId), $licenceAssignedRoleIds);
			}

			// Add additinal roles
			if (!empty($additionRoles))
			{
				$roleIdstoSaved = array_merge($roleIdstoSaved, $additionRoles);
			}

			if (count($additionalRole[$memberRoleId]))
			{
				$roleIdstoSaved = array_merge($roleIdstoSaved, $additionalRole[$memberRoleId]);
			}

			array_push($roleIdstoSaved, $memberRoleId);

			// Add subusers entries for below client context
			$subUserClients = array('com_multiagency','com_cluster');

			foreach ($roleIdstoSaved as $roleId)
			{
				foreach ($subUserClients as $content)
				{
					// Assign manager and school admin
					$tableInstance = RBACL::table('user');

					// Insert the record into subuser  table.
					$sudata['user_id'] = $userId;

					if ($content == 'com_multiagency')
					{
						$sudata['client_id'] = $multiagencyId;
					}
					else
					{
						$sudata['client_id'] = $clusterId;
					}

					$sudata['client'] = $content;
					$sudata['role_id'] = $roleId;

					if (!empty($sudata['user_id']))
					{
						$tableInstance->save($sudata);
					}
				}
			}

			Factory::getApplication()->triggerEvent('onAfterAddUser', array($clusterId, array($userId)));

			return true;
		}
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
				// WebApplication::setHeader('HTTP/1.0 200 Ok');
				header("HTTP/1.0 200 Ok");
			}
			else
			{
				// WebApplication::setHeader('HTTP/1.0 404 Not Found');
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

				/* IIII init the destination file (format <filename.ext>.part<#chunk>
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
	 * [createFileFromChunks description]
	 *
	 * @param   [string]  $temp_dir   [the temporary directory holding all the parts of the file]
	 * @param   [string]  $fileName   [the original file name]
	 * @param   [string]  $chunkSize  [each chunk size (in bytes)]
	 * @param   [string]  $totalSize  [original file size (in bytes)]
	 *
	 * @return  [type]              [description]
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
			}
			else
			{
				return false;
			}

			// Concurrent chunks uploads) and than delete it
			if (rename($temp_dir, $temp_dir . '_UNUSED'))
			{
				$this->rrmdir($temp_dir . '_UNUSED');
			}
			else
			{
				$this->rrmdir($temp_dir);
			}
		}

		// Lets make a unique safe file name for each upload
		$name     = JPATH_SITE . '/tmp/' . $fileName;
		$fileInfo = pathinfo($name);
		$fileExt  = $fileInfo['extension'];
		$fileBase = $fileInfo['filename'];

		// Add logggedin userid to file name
		$fileBase = Factory::getUser()->id . '_' . $fileBase;

		/*add timestamp to file name
		http://www.php.net/manual/en/function.microtime.php
		http://php.net/manual/en/function.uniqid.php
		microtime â�� Return current Unix timestamp with microseconds
		uniqid â�� Generate a unique ID
		*/

		$timestamp = microtime();

		$fileBase = $fileBase . '_' . $timestamp;

		// Clean up filename to get rid of strange characters like spaces etc
		$fileBase = File::makeSafe($fileBase);

		// Lose any special characters in the filename
		$fileBase = preg_replace("/[^A-Za-z0-9]/i", "_", $fileBase);

		// Use lowercase
		$fileBase = strtolower($fileBase);

		$fileName = $fileBase . '.' . $fileExt;

		rename($name, JPATH_SITE . '/tmp/' . $fileName);

		return $fileName;
	}

	/**
	 * [Delete a directory RECURSIVELY]
	 *
	 * @param   [type]  $dir  [description]
	 *
	 * @return  [type]        [description]
	 */
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
}
