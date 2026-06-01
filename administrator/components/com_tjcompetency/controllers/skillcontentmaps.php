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
/**
 * SkillContentMap list controller class.
 *
 * @since  1.0.0
 */
class TjCompetencyControllerSkillContentMaps extends AdminController
{
	const CSV_FILE_LOCATION     = JPATH_SITE . '/media/com_tjcompetency/csvimport/';

	public $csvProgressFilePath = self::CSV_FILE_LOCATION . 'contentmap_progress.txt';

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
	public function getModel($name = 'SkillContentMap', $prefix = 'TjCompetencyModel')
	{
		return parent::getModel($name, $prefix, array('ignore_request' => true));
	}

	/**
	 * CSV file data store in Skill Content Maps
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

		header('Cache-Control: no-cache, must-revalidate');
		header('Content-type: application/json');
		jimport('joomla.log.log');
		$dateTime = str_replace(array(' ', '-', ':'), '_', Factory::getDate());
		$logFileName = 'com_tjcompetency.skillcontentmap_import_' . $dateTime . '.log';

		// Log::addLogger(array('text_file' => $logFileName), Log::ALL, array('com_tjcompetency'));
		$userId = Factory::getUser()->id;

		// Set log file name to session
		$session     = Factory::getSession();
		$session->set('skillcontentmap_log_filename', $logFileName);

		/* If user is not logged in*/
		if (!$userId)
		{
			$output = array(
				'error' => Text::_('COM_TJCOMPETENCY_LOGIN_NEEDED')
			);

			echo json_encode($output);
			jexit();
		}

		$input        = $app->input;
		$files        = $input->files;
		$fileToUpload = $files->get('csvfile', '', 'ARRAY');

		if ($fileToUpload['name'] != '')
		{
			$allowedExtension = array('csv');
			$ext = File::getExt($fileToUpload['name']);

			if (in_array($ext, $allowedExtension))
			{
				$fileName    = File::makeSafe(rand()) . '_skillcontent.' . $ext;
				$uploadPath  = self::CSV_FILE_LOCATION . $fileName;

				$session->set('skillcontentmap_filename', $fileName);

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

		$session  = Factory::getSession();
		$fileName = $session->get('skillcontentmap_filename');
		$filePath = self::CSV_FILE_LOCATION . $fileName;

		if (isset($fileName) && file_exists($filePath))
		{
			if ($file = fopen($filePath, "r"))
			{
				$rowNum  = 0;
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

							case 'skill_id' :
								$data['skill_id'] = $value;
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

							case 'description' :
								$data['description'] = $value;
								break;

							case 'upon_course_completion' :
								$data['outcome_rule'] = $value;
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

					$model = $this->getModel('SkillContentMap');
					$model->save($data);
					$rowCount++;

					// Reading file here for getting progress
					$progressFile = fopen($this->csvProgressFilePath, 'w+');

					if ($progressFile != false)
					{
						$newContents = "progress:" . ((int) $rowCount);
						fwrite($progressFile, $newContents);
						fclose($progressFile);
					}
				}
			}
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
		$fileName = $session->get('skillcontentmap_filename');
		$filePath = self::CSV_FILE_LOCATION . $fileName;

		if (isset($fileName) && file_exists($filePath))
		{
			unlink($filePath);
			unlink($this->csvProgressFilePath);
		}
	}
}
