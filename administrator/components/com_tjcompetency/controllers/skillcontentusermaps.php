<?php
/**
 * @package     TjCompetency
 * @subpackage  com_tjcompetency
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2021 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Factory;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 * SkillContentUserMap list controller class.
 *
 * @since  1.0.0
 */
class TjCompetencyControllerSkillContentUserMaps extends AdminController
{
	const CSV_FILE_LOCATION     = JPATH_SITE . '/media/com_tjcompetency/csvimport/';

	public $csvProgressFilePath = self::CSV_FILE_LOCATION . 'contentusermap_progress.txt';
	/**
	 * Proxy for getModel.
	 *
	 * @param   STRING  $name    model name
	 * @param   STRING  $prefix  model prefix
	 *
	 * @return  object  The model.
	 *
	 * @since  1.0.0
	 */
	public function getModel($name = 'SkillContentUserMap', $prefix = 'TjCompetencyModel')
	{
		return parent::getModel($name, $prefix, array('ignore_request' => true));
	}

	/**
	 * CSV file data store in Skill Content User Maps
	 *
	 * @return  void|string
	 *
	 * @since   1.0.0
	 */
	public function csvImport()
	{
		$app = Factory::getApplication();

		if (!Session::checkToken())
		{
			$output = array(
				'error' => Text::_('JINVALID_TOKEN')
			);

			echo json_encode($output);
			jexit();
		}

		// Log::addLogger(array('text_file' => $logFileName), Log::ALL, array('com_tjcompetency'));
		$userId = Factory::getUser()->id;

		// Set log file name to session
		$session     = Factory::getSession();

		/* If user is not logged in*/
		if (!$userId)
		{
			$output = array(
				'error' => Text::_('COM_TJCOMPETENCY_LOGIN_NEEDED')
			);

			echo json_encode($output);
			jexit();
		}

		$input  = $app->input;
		$notify = $input->get('notify_user_import', 0);
		$session->set('skillcontentusermap_notify_users', $notify);

		$files        = $input->files;
		$fileToUpload = $files->get('csvfile', '', 'ARRAY');

		$csvFileName = $fileToUpload['name'];
		$session->set('skillcontentusermap_original_filename', $csvFileName);

		if ($fileToUpload['name'] != '')
		{
			$allowedExtension = array('csv');
			$ext = File::getExt($fileToUpload['name']);

			if (in_array($ext, $allowedExtension))
			{
				$fileName    = File::makeSafe(rand()) . '_skillusercontent.' . $ext;
				$uploadPath  = self::CSV_FILE_LOCATION . $fileName;

				$session->set('skillcontentusermap_filename', $fileName);

				if (!File::upload($fileToUpload['tmp_name'], $uploadPath))
				{
					$output = array(
						'error' => Text::_('COM_TJCOMPETENCY_ERROR_IN_MOVING'),
					);

					echo json_encode($output);
					jexit();
				}

				$fileContent = file($uploadPath, FILE_SKIP_EMPTY_LINES);
				$totalLines  = count($fileContent);

				$output = array(
					'success'  => true,
					'total_line' => ($totalLines - 1)
				);

				echo json_encode($output);
				jexit();
			}
			else
			{
				$output = array(
					'error' => Text::_('COM_TJCOMPETENCY_ONLY_CSV_FILE_FORMAT')
				);

				echo json_encode($output);
				jexit();
			}
		}
		else
		{
			$output = array(
				'error' => Text::_('COM_TJCOMPETENCY_NO_FILE_SELECTED')
			);

			echo json_encode($output);
			jexit();
		}
	}

	/**
	 * Process uploaded CSV file
	 *
	 * @return  void|string
	 *
	 * @since   1.0.0
	 */
	public function csvProcess()
	{
		if (!Session::checkToken())
		{
			$output = array(
				'error' => Text::_('JINVALID_TOKEN')
			);

			echo json_encode($output);
			jexit();
		}

		header('Cache-Control: no-cache, must-revalidate');
		header('Content-type: application/json');
		jimport('joomla.log.log');

		$dateTime = str_replace(array(' ', '-', ':'), '_', Factory::getDate());
		$logFileName = 'com_tjcompetency.skillcontentusermap_import_' . $dateTime . '.log';

		Log::addLogger(array('text_file' => $logFileName), Log::ALL, array('com_tjcompetency'));
		$userId = Factory::getUser()->id;

		// Set log file name to session
		$session  = Factory::getSession();
		$session->set('skillcontentusermap_log_filename', $logFileName);
		$fileName = $session->get('skillcontentusermap_filename');
		$filePath = self::CSV_FILE_LOCATION . $fileName;
		$notify   = $session->get('skillcontentusermap_notify_users');

		Log::add(Text::_("COM_TJCOMPETENCY_SKILLCONTENTUSERMAPS_LOG_CSV_START"), Log::INFO, 'com_tjcompetency');

		$csvFileName = $session->get('skillcontentusermap_original_filename');

		Log::add(Text::sprintf("COM_TJCOMPETENCY_SKILLCONTENTUSERMAPS_LOG_CSV_FILE_NAME", $csvFileName), Log::INFO, 'com_tjcompetency');

		if (isset($fileName) && file_exists($filePath))
		{
			if ($file = fopen($filePath, "r"))
			{
				$rowNum  = 0;
				$emptyUser = $emptySkill = 0;
				$csvData = array ();

				while (($data = fgetcsv($file)) !== false)
				{
					if ($rowNum == 0)
					{
						// Parsing the CSV header
						$headers = array();

						foreach ($data as $d)
						{
							$headers[] = $d;
						}
					}
					else
					{
						// Parsing the data rows
						$rowData = array();

						foreach ($data as $d)
						{
							$rowData[] = $d;
						}

						if (isset($headers))
						{
							$csvData[] = array_combine($headers, $rowData);
						}
					}

					$rowNum++;
				}

				fclose($file);
			}

			if (!empty($csvData))
			{
				$rowCount = 0;

				foreach ($csvData as $eachRecord)
				{
					$data = array ();

					foreach ($eachRecord as $key => $value)
					{
						switch ($key)
						{
							case 'id' :
								$data['id'] = 0;

								if (!empty ($value))
								{
									$data['id'] = $value;
								}
								break;

							case 'user_id' :
								$data['user_id'] = $value;
								break;							

							case 'username' :
								$data['username'] = $value;
								break;

							case 'skill_id' :
								$data['skill_id'] = $value;
								break;

							case 'skill_path' :
								$data['skill_path'] = $value;
								break;

							case 'scale_id' :
								$data['scale_id'] = $value;
								break;

							case 'content_type' :
								$data['client'] = $value;
								break;

							case 'content_id' :
								$data['client_id'] = $value;
								break;

							case 'note' :
								$data['note'] = $value;
								break;

							case 'reviewer_id' :
								$data['reviewer_id'] = $value;
								break;

							case 'state' :
								$data['state'] = 0;

								if (!empty($value))
								{
									$data['state'] = $value;
								}
								break;

							default :
								break;
						}
					}

					if($data['username'])
					{
						$data['user_id'] = JUserHelper::getUserId($data['username']);
					}

					if($data['skill_path'])
					{
						BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_tjcompetency/models', 'TjCompetencyModel');
						$skillmodel = BaseDatabaseModel::getInstance('Skills', 'TjCompetencyModel', array('ignore_request' => true));
						$skillmodel->setState("filter.path", $data['skill_path']);
						$skillData = $skillmodel->getItems();

						$data['skill_id'] = $skillData[0]->id;
					}

					if (empty($data['user_id']))
					{
						$emptyUser++;
					}					

					if (empty($data['skill_id']))
					{
						$emptySkill++;
					}

					$model = $this->getModel('SkillContentUserMap');
					$model->notify = $notify;

					if ($model->save($data))
					{
						Log::add(Text::sprintf("COM_TJCOMPETENCY_SKILLCONTENTUSERMAPS_LOG_IMPORT_USER_SUCCESSFULLY", $data['user_id'], $data['skill_id']), Log::INFO, 'com_tjcompetency');

						$rowCount++;
					}
					
					// Reading file here for getting progress
					$progressFile = fopen($this->csvProgressFilePath, 'w+');
					
					if ($progressFile != false && empty($emptyUser) && empty($emptySkill))
					{
						$newContents = "progress:" . ((int) $rowCount);
						fwrite($progressFile, $newContents);
						fclose($progressFile);
					}
				}
			}

			$msg = array();

			if (!empty($emptyUser))
			{
				$message = ($emptyUser == 1) ? 'COM_TJCOMPETENCY_SKILLCONTENTUSERMAPS_USER_ID_EMPTY_ERROR' : 'COM_TJCOMPETENCY_SKILLCONTENTUSERMAPS_USER_IDS_EMPTY_ERROR';

				$msg[] = Text::sprintf($message, $emptyUser) . '<br>'; 
			}		

			if (!empty($emptySkill))
			{
				$message = ($emptySkill == 1) ? 'COM_TJCOMPETENCY_SKILLCONTENTUSERMAPS_SKILL_ID_EMPTY_ERROR' : 'COM_TJCOMPETENCY_SKILLCONTENTUSERMAPS_SKILL_IDS_EMPTY_ERROR';
				$msg[] = Text::sprintf($message, $emptySkill); 
			}

			Log::add(Text::_("COM_TJCOMPETENCY_SKILLCONTENTUSERMAPS_LOG_CSV_END"), Log::INFO, 'com_tjcompetency');

			// Log file Path
			$logFilepath = JRoute::_('index.php?option=com_tjcompetency&view=skillcontentusermaps&task=downloadLog&prefix=skillcontentusermap_log');

			$session  = Factory::getSession();
			$config   = Factory::getConfig();
			$filename = $session->get('skillcontentusermap_log_filename');
			$logfile  = $config->get('log_path') . '/' . $filename;

			if (JFile::exists($logfile))
			{
				$logLink = '<a href="' . $logFilepath . '" >' . Text::_("COM_TJCOMPETENCY_SKILLCONTENTUSERMAPS_CSV_SAMPLE") . '</a>';
				$logLink = Text::sprintf('COM_TJCOMPETENCY_SKILLCONTENTUSERMAPS_LOG_FILE_PATH', $logLink);
			}

			$output = array(
				'log' => $logLink
			);

			if (!empty($msg))
			{
				$output = array_merge($output, array('error' => $msg));
			}

			echo json_encode($output);
			jexit();
		}
	}

	/**
	 * Get CSV upload progress
	 *
	 * @return  void|string
	 *
	 * @since   1.0.0
	 */
	public function csvProgress()
	{
		if (!Session::checkToken())
		{
			$output = array(
				'error' => Text::_('JINVALID_TOKEN')
			);

			echo json_encode($output);
			jexit();
		}

		$count = 0;

		$fileData = file($this->csvProgressFilePath);

		if (strpos($fileData[0], 'progress') !== false)
		{
			$count = explode(":", $fileData[0])[1];
		}

		$output = array(
			'progress_count' => $count
		);

		echo json_encode($output);
		jexit();
	}

	/**
	 * Delete CSV file
	 *
	 * @return  void|string
	 *
	 * @since   1.0.0
	 */
	public function csvFileDelete()
	{
		if (!Session::checkToken())
		{
			$output = array(
				'error' => Text::_('JINVALID_TOKEN')
			);

			echo json_encode($output);
			jexit();
		}

		$session  = Factory::getSession();
		$fileName = $session->get('skillcontentusermap_filename');
		$filePath = self::CSV_FILE_LOCATION . $fileName;

		if (isset($fileName) && file_exists($filePath))
		{
			unlink($filePath);
			unlink($this->csvProgressFilePath);
		}
	}
}
