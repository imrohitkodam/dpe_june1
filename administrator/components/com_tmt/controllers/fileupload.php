<?php
/**
 * @package     TMT
 * @subpackage  com_tmt
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

jimport('joomla.filesystem.folder');

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Controller\FormController;

/**
 * File upload controller class.
 *
 * @since  1.0.0
 */
class TmtControllerFileUpload extends FormController
{
	/**
	 * The main function triggered after on format upload
	 *
	 * @return object of result and message
	 *
	 * @since 1.0.0
	 * */
	public function processupload()
	{
		// Check for request forgeries.
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		header('Cache-Control: no-cache, must-revalidate');
		header('Content-type: application/json');

		jimport('joomla.log.log');
		$fileName = 'com_tmt.questionimport_' . Factory::getDate() . '.log';

		Log::addLogger(array('text_file' => $fileName), Log::ALL, array('com_tmt'));

		// Set log file name to session
		$session = Factory::getSession();
		$session->set('question_import_filename', $fileName);

		$oluser_id = Factory::getUser()->id;

		/* If user is not logged in*/
		if (!$oluser_id)
		{
			$ret['OUTPUT']['flag']	= 0;
			$ret['OUTPUT']['msg']	= Text::_('COM_TJLMS_MUST_LOGIN_TO_UPLOAD');
			echo json_encode($ret);
			jexit();
		}

		$input       = Factory::getApplication()->input;
		$tjlmsparams = ComponentHelper::getParams('com_tjlms');

		$files = $input->files;
		$post  = $input->post;

		$file_to_upload      = $files->get('FileInput', '', 'ARRAY');
		$file_type_to_upload = $post->get('CsvType', '', 'STRING');

		/* Validate the uploaded file*/
		$validate_result = $this->validateupload($file_to_upload);

		if ($validate_result['res'] != 1)
		{
			$ret['OUTPUT']['flag']	=	$validate_result['res'];
			$ret['OUTPUT']['msg']	=	$validate_result['msg'];
			echo json_encode($ret);
			jexit();
		}

		$return = 1;
		$msg    = '';

		$file_attached = $file_to_upload['tmp_name'];

		/* Save csv content to question table */

		$result = array();

		if ($file_type_to_upload == 'quiz-csv')
		{
			$result = $this->saveCsvContentQuiz($file_to_upload);
		}
		elseif ($file_type_to_upload == 'exe-feed-csv')
		{
			$result = $this->saveCsvContentExerciseFeedback($file_to_upload);
		}

		$filename = $file_to_upload['name'];

		$return = $result['returns'];
		$msg    = $result['msg'];

		$ret['OUTPUT']['flag'] = $return;
		$ret['OUTPUT']['msg']  = $msg;
		echo json_encode($ret);
		jexit();
	}

	/**
	 * Save question to table from csv
	 *
	 * @param   MIXED  $file_to_upload  file object
	 *
	 * @return  ARRAY
	 *
	 * @since  1.0.0
	 */
	public function saveCsvContentQuiz($file_to_upload)
	{
		$msgArray = array();
		$output   = array();

		ini_set('auto_detect_line_endings', true);

		if (($handle = fopen($file_to_upload['tmp_name'], 'r')) !== false)
		{
			$rowNum = 0;
			$lineno = 0;
			$headers = array();

			while (($data = fgetcsv($handle)) !== false)
			{
				if ($rowNum == 0)
				{
					$lineno++;

					// Parsing the CSV header

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

					$questionData[] = array_combine($headers, $rowData);
				}

				$rowNum++;
			}

			ini_set('auto_detect_line_endings', false);
			fclose($handle);

			$logLink      = '';
			$acceptedQues = $updatedQues = $acceptedQuesFail = $updatedQuesFail = 0;

			$col1 = array("id", "alias", "Question type", "Grading Type", "published", "Question Title","published",
				"Unique Id","Question Title", "Question Description");
			$col2 = array("Ideal time", "Category id", "marks", "Marks Difficulty Level (easy|medium|hard)");
			$col3 = array("answer1", "answer2", "answer3", "answer4", "answer5", "answer6", "answer6", "answer7", "answer8", "answer9", "answer10");

			$col1   = array_merge($col1, $col2);
			$column = array_merge($col1, $col3);
			$flag   = 0;

			$csvFileName = $file_to_upload['name'];

			Log::add(Text::sprintf('COM_TMT_QUESTIONS_LOG_CSV_FILE_NAME', $csvFileName), Log::INFO, 'com_tmt');

			Log::add(Text::_("COM_TMT_QUESTIONS_LOG_CSV_START"), Log::INFO, 'com_tmt');

			if (!empty($questionData))
			{
				$totalQuestions         = count($questionData);
				$acceptedQuesFailLineNo = $updatedQuesFailLineNo = 0;

				// Log file Path
				$logFilepath = Route::_('index.php?option=com_tjlms&view=manageenrollments&task=downloadLog&prefix=question_import');

				$session  = Factory::getSession();
				$config   = Factory::getConfig();
				$filename = $session->get('question_import_filename');
				$logfile  = $config->get('log_path') . '/' . $filename;

				if (File::exists($logfile))
				{
					$logLink = '<a href="' . $logFilepath . '" >' . Text::_("COM_TMT_QUESTION_SAMPLE") . '</a>';
					$logLink =	Text::sprintf('COM_TMT_LOG_FILE_PATH', $logLink);
				}

				$msgArray[] = $output['msg'] = Text::sprintf('COM_TMT_QUESTIONS_IMPORT_TOTAL_ROWS_CNT_MSG', $totalQuestions) . ' ' . $logLink;

				foreach ($questionData as $eachQues)
				{
					$lineno++;

					$fileData               = array();
					$fileData['created_by'] = Factory::getUser()->id;
					$fileData['alias']      = '';
					$fileData['level']      = 'medium';
					$fileData['marks']      = 0;
					$fileData['state']      = 1;
					$fileData['is_csv']     = 1;

					foreach ($eachQues as $key => $value)
					{
						$key = trim($key);

						if ($key == 'id')
						{
							$fileData['id'] = $value;
						}
						elseif ($key == 'Question type')
						{
							if ($value != '')
							{
								$fileData['type'] = Text::_(strtoupper($value));
							}
						}
						elseif ($key == 'published')
						{
							if ($value != '')
							{
								$fileData['state'] = $value;
							}
						}
						elseif ($key == 'Question Title')
						{
							if ($value != '')
							{
								$fileData['title'] = $value;
							}
						}
						elseif ($key == 'Unique Id')
						{
							$fileData['alias'] = $value;
						}
						elseif ($key == 'marks')
						{
							if ($value > 0)
							{
								$fileData['marks'] = $value;
							}
						}
						elseif ($key == 'Question Description')
						{
							$fileData['description'] = $value;
						}
						elseif ($key == 'Ideal time')
						{
							$fileData['ideal_time'] = $value;
						}
						elseif ($key == 'Grading Type')
						{
							if ($value == 'quiz')
							{
								$fileData['gradingtype'] = $value;
							}
						}
						elseif ($key == 'Category id')
						{
							if ($value != '')
							{
								$fileData['category_id'] = $value;
							}
						}
						elseif ($key == 'Marks Difficulty Level (easy|medium|hard)')
						{
							if ($value != '')
							{
								$fileData['level'] = $value;
							}
						}
						elseif (substr($key, 0, 6) == 'answer')
						{
							$key = 'answer';

							if (!empty($value))
							{
								$ansData = explode('|', $value);

								if (isset($ansData[1]) && !empty($ansData[1]))
								{
									$ansData[1] = trim($ansData[1]);
								}

								if (isset($ansData[2]) && !empty($ansData[2]))
								{
									$ansData[2] = trim($ansData[2]);
								}

								// Added for column name comparison
								if (!$fileData['type'] == 'file_upload')
								{
									if ($fileData['type'] == 'radio' || !$fileData['type'] == 'checkbox')
									{
										if ((!isset($ansData[0]) || $ansData[0] == '')
											|| (!isset($ansData[1]) || $ansData[1] == '' || $ansData[1] < 0)
											|| (!isset($ansData[2]) || $ansData[2] == '' || ($ansData[1] == '0' && $ansData[2] > '0')))
										{
											$output['returns'] = 0;
											$output['msg'] = Text::_('COM_TMT_CSV_IMPORT_COLUMN_MISSING');

											return $output;
										}
									}
									else
									{
										if ((!isset($ansData[0]) || $ansData[0] == ''))
										{
											$output['returns'] = 0;
											$output['msg'] = Text::_('COM_TMT_CSV_IMPORT_COLUMN_MISSING');

											return $output;
										}
									}
								}

								if (!empty($ansData[2]) && ($ansData[2] <= 0))
								{
									$ansData[1] = '0';
								}

								$fileData['answers_text'][] = $ansData[0];
								$fileData['answers_iscorrect_hidden'][] = !empty($ansData[2]) ? $ansData[2] : '';
								$fileData['answers_marks'][] = !empty($ansData[1]) ? $ansData[1] : '';

								if (!isset($ansData[3]))
								{
									$ansData[3] = '';
								}

								$fileData['answers_comments'][] = $ansData[3];
								$fileData['answer_id_hidden'][] = 0;
							}
						}
						else
						{
							$fileData[$key] = $value;
						}

						if (!in_array($key, $column))
						{
							if (substr($key, 0, 6) == 'answer')
							{
								$flag = 0;
							}
							else
							{
								$flag = 1;
								break;
							}
						}
					}

					if ($flag == 1)
					{
						$output['returns'] = 0;
						$output['msg'] = Text::_('COM_TMT_QUESTIONS_IMPORT_COLUMNS_NAMES_VALIDATION');

						return $output;
					}

					$model  = $this->getModel('Question', 'TmtModel');
					$form   = $model->getForm();
					$result = $model->validate($form, $fileData);

					if ($result == false)
					{
						$output['returns'] = 0;
						$errors = $model->getErrors();

						// Push up to three validation messages out to the user.
						for ($i = 0, $n = count($errors); $i < $n && $i < 3; $i++)
						{
							if ($errors[$i] instanceof Exception)
							{
								$errorMessage = $errors[$i]->getMessage();
								$errorMessage = $errorMessage . ' in row no ' . $lineno;

								$msgArray[] = $errorMessage;

								Log::add($errorMessage, Log::ERROR, 'com_tmt');
							}
						}

						$msgArray[] = $errors;
					}
					else
					{
						$result = $model->save($fileData);

						if ($result)
						{
							if (empty($fileData['id']))
							{
								$acceptedQues++;
							}
							else
							{
								if ($result >= 0)
								{
									$updatedQues++;
								}
								else
								{
									$updatedQuesFail++;
									$updatedQuesFailLineNo = $lineno;
								}
							}
						}
						else
						{
							$acceptedQuesFail++;
							$acceptedQuesFailLineNo = $lineno;
						}
					}
				}

				if ($acceptedQues > 0)
				{
					$output['returns'] = 1;
					$msgArray[] = $output['msg'] = Text::sprintf('COM_TMT_CSV_IMPORT_SUCCESSFUL', $acceptedQues, $totalQuestions);
					Log::add($output['msg'], Log::INFO, 'com_tmt');
				}

				if ($acceptedQuesFail > 0)
				{
					$output['returns'] = 0;
					$msgArray[] = $output['msg'] = Text::sprintf('COM_TMT_CSV_IMPORT_FAIL', $acceptedQuesFail, $totalQuestions);
					Log::add(Text::sprintf('COM_TMT_CSV_IMPORT_FAIL_LOG', $acceptedQuesFailLineNo), Log::ERROR, 'com_tmt');
				}

				if ($updatedQues > 0)
				{
					$output['returns'] = 1;
					$msgArray[] = $output['msg'] = Text::sprintf('COM_TMT_CSV_IMPORT_UPDATE_SUCCESSFUL', $updatedQues, $totalQuestions);
					Log::add($output['msg'], Log::INFO, 'com_tmt');
				}

				if ($updatedQuesFail > 0)
				{
					$output['returns'] = 0;
					$msgArray[] = $output['msg'] = Text::sprintf('COM_TMT_CSV_IMPORT_UPDATE_FAIL', $updatedQuesFail, $totalQuestions);
					Log::add(Text::sprintf('COM_TMT_CSV_IMPORT_UPDATE_FAIL_LOG', $updatedQuesFailLineNo), Log::ERROR, 'com_tmt');
				}

				Log::add(Text::_("COM_TMT_QUESTIONS_LOG_CSV_END"), Log::INFO, 'com_tmt');

				if (!empty($msgArray))
				{
					$output['msg'] = '';

					$errorArray = array();

					foreach ($msgArray as $key => $msg)
					{
						if (is_array($msg))
						{
							foreach ($msg as $k => $value)
							{
								$errorArray[] = $value;
							}
						}
						else
						{
							$output['msg'] .= $msg . "</br>";
						}
					}

					if (!empty($errorArray))
					{
						$errorArray = array_count_values($errorArray);

						foreach ($errorArray as $msg => $count)
						{
							$output['msg'] .= '"' . $msg . '"' . " has occured " . $count . " times </br>";
						}
					}
				}
			}
			else
			{
				$output['returns'] = 1;
				$output['msg'] = Text::sprintf('COM_TMT_CSV_IMPORT_BLANK_DATA');
			}
		}
		else
		{
			$output['returns'] = 1;
			$output['msg'] = Text::sprintf('COM_TMT_FILE_READ_ERROR');
		}

		return $output;
	}

	/**
	 * Save question to table from csv
	 *
	 * @param   MIXED  $file_to_upload  file object
	 *
	 * @return  ARRAY
	 *
	 * @since  1.0.0
	 */
	public function saveCsvContentExerciseFeedback($file_to_upload)
	{
		$msgArray = array();
		$output   = array();

		ini_set('auto_detect_line_endings', true);

		if (($handle = fopen($file_to_upload['tmp_name'], 'r')) !== false)
		{
			$rowNum = 0;
			$lineno = 0;

			while (($data = fgetcsv($handle)) !== false)
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

					$questionData[] = array_combine($headers, $rowData);
				}

				$rowNum++;
			}

			ini_set('auto_detect_line_endings', false);
			fclose($handle);

			$acceptedQues = $updatedQues = $acceptedQuesFail = $updatedQuesFail = 0;

			$col1 = array("id", "alias", "Question type", "Grading Type", "published", "Question Title",
				"published","Unique Id","Question Title", "Question Description");
			$col2 = array("Ideal time", "Category id", "marks", "Marks Difficulty Level (easy|medium|hard)");
			$col3 = array("answer1", "answer2", "answer3", "answer4", "answer5", "answer6", "answer6", "answer7", "answer8", "answer9", "answer10");

			$col1   = array_merge($col1, $col2);
			$column = array_merge($col1, $col3);
			$flag   = 0;

			$csvFileName = $file_to_upload['name'];

			Log::add(Text::sprintf('COM_TMT_QUESTIONS_LOG_CSV_FILE_NAME', $csvFileName), Log::INFO, 'com_tmt');

			Log::add(Text::_("COM_TMT_QUESTIONS_LOG_CSV_START"), Log::INFO, 'com_tmt');

			if (!empty($questionData))
			{
				$totalQuestions         = count($questionData);
				$acceptedQuesFailLineNo = $updatedQuesFailLineNo = 0;

				// Log file Path
				$logFilepath = Route::_('index.php?option=com_tjlms&view=manageenrollments&task=downloadLog&prefix=question_import');

				$session  = Factory::getSession();
				$config   = Factory::getConfig();
				$filename = $session->get('question_import_filename');
				$logfile  = $config->get('log_path') . '/' .
				$filename;
				$logLink = '';

				if (File::exists($logfile))
				{
					$logLink = '<a href="' . $logFilepath . '" >' . Text::_("COM_TMT_QUESTION_SAMPLE") . '</a>';
					$logLink = Text::sprintf('COM_TMT_LOG_FILE_PATH', $logLink);
				}

				$msgArray[] = $output['msg'] = Text::sprintf('COM_TMT_QUESTIONS_IMPORT_TOTAL_ROWS_CNT_MSG', $totalQuestions) . ' ' . $logLink;

				foreach ($questionData as $eachQues)
				{
					$fileData               = array();
					$fileData['created_by'] = Factory::getUser()->id;
					$fileData['alias']      = '';
					$fileData['level']      = 'medium';
					$fileData['marks']      = 1;
					$fileData['state']      = 1;
					$fileData['is_csv']     = 1;

					foreach ($eachQues as $key => $value)
					{
						$key = trim($key);

						if ($key == 'id')
						{
							$fileData['id'] = $value;
						}
						elseif ($key == 'Question type')
						{
							if ($value != '')
							{
								$fileData['type'] = Text::_(strtoupper($value));
							}
						}
						elseif ($key == 'published')
						{
							if ($value != '')
							{
								$fileData['state'] = $value;
							}
						}
						elseif ($key == 'Question Title')
						{
							if ($value != '')
							{
								$fileData['title'] = $value;
							}
						}
						elseif ($key == 'Unique Id')
						{
							$fileData['alias'] = $value;
						}
						elseif ($key == 'Question Description')
						{
							$fileData['description'] = $value;
						}
						elseif ($key == 'Ideal time')
						{
							$fileData['ideal_time'] = $value;
						}
						elseif ($key == 'Grading Type')
						{
							if ($value == 'exercise' || $value == 'feedback')
							{
								$fileData['gradingtype'] = $value;
							}
						}
						elseif ($key == 'Category id')
						{
							if ($value != '')
							{
								$fileData['category_id'] = $value;
							}
						}
						elseif ($key == 'Marks Difficulty Level (easy|medium|hard)')
						{
							if ($value != '')
							{
								$fileData['level'] = $value;
							}
						}
						elseif (substr($key, 0, 6) == 'answer')
						{
							$key = 'answer';
							$output = array();

							if (!empty($value))
							{
								$ansData = explode('|', $value);

								// Added for column name comparison
								if ($fileData['gradingtype'] == 'exercise')
								{
									if (!$fileData['type'] == 'file_upload')
									{
										if ((!isset($ansData[0]) || $ansData[0] == '')
											|| (!isset($ansData[2]) || $ansData[2] == ''))
										{
											$output['returns'] = 0;
											$output['msg'] = Text::_('COM_TMT_CSV_IMPORT_COLUMN_MISSING');

											return $output;
										}
									}
								}
								else
								{
									if (!$fileData['type'] == 'file_upload')
									{
										if ((!isset($ansData[0]) || $ansData[0] == ''))
										{
											$output['returns'] = 0;
											$output['msg'] = Text::_('COM_TMT_CSV_IMPORT_COLUMN_MISSING');

											return $output;
										}
									}
								}

								if (is_numeric($ansData[2]))
								{
									$ansData[1] = '0';
								}

								$fileData['answers_text'][] = $ansData[0];
								$fileData['answers_iscorrect_hidden'][] = $ansData[2];
								$fileData['answers_marks'][] = $ansData[1];

								if (!isset($ansData[3]))
								{
									$ansData[3] = '';
								}

								if ($fileData['marks'])
								{
									$fileData['marks'] = 0;
								}

								$fileData['answers_comments'][] = $ansData[3];
								$fileData['answer_id_hidden'][] = 0;
							}
						}
						else
						{
							$fileData[$key] = $value;
						}

						if (!in_array($key, $column))
						{
							if (substr($key, 0, 6) == 'answer')
							{
								$flag = 0;
							}
							else
							{
								$flag = 1;
								break;
							}
						}
					}

					if ($flag == 1)
					{
						$output['returns'] = 0;
						$output['msg'] = Text::_('COM_TMT_QUESTIONS_IMPORT_COLUMNS_NAMES_VALIDATION');

						return $output;
					}

					$model  = $this->getModel('Question', 'TmtModel');
					$form   = $model->getForm();
					$result = $model->validate($form, $fileData);

					if ($result == false)
					{
						$output['returns'] = 0;
						$errors = $model->getErrors();

						// Push up to three validation messages out to the user.
						for ($i = 0, $n = count($errors); $i < $n && $i < 3; $i++)
						{
							if ($errors[$i] instanceof Exception)
							{
								$errorMessage = $errors[$i]->getMessage();
								$errorMessage = $errorMessage . ' in row no ' . $lineno;

								$msgArray[] = $errorMessage;

								Log::add($errorMessage, Log::ERROR, 'com_tmt');
							}
						}

						$msgArray[] = $errors;
					}
					else
					{
						$model = $this->getModel('Question', 'TmtModel');

						// Attempt to save the data.
						$result = $model->save($fileData);

						if ($result)
						{
							if (empty($fileData['id']))
							{
								$acceptedQues++;
							}
							else
							{
								if ($result >= 0)
								{
									$updatedQues++;
								}
								else
								{
									$updatedQuesFail++;
									$updatedQuesFailLineNo = $lineno;
								}
							}
						}
						else
						{
							$acceptedQuesFail++;
							$acceptedQuesFailLineNo = $lineno;
						}
					}
				}

				if ($acceptedQues > 0)
				{
					$output['returns'] = 1;
					$msgArray[] = $output['msg'] = Text::sprintf('COM_TMT_CSV_IMPORT_SUCCESSFUL', $acceptedQues, $totalQuestions);
					Log::add($output['msg'], Log::INFO, 'com_tmt');
				}

				if ($acceptedQuesFail > 0)
				{
					$output['returns'] = 0;
					$msgArray[] = $output['msg'] = Text::sprintf('COM_TMT_CSV_IMPORT_FAIL', $acceptedQuesFail, $totalQuestions);
					Log::add(Text::sprintf('COM_TMT_CSV_IMPORT_FAIL_LOG', $acceptedQuesFailLineNo), Log::ERROR, 'com_tmt');
				}

				if ($updatedQues > 0)
				{
					$output['returns'] = 1;
					$output['msg'] = Text::sprintf('COM_TMT_CSV_IMPORT_UPDATE_SUCCESSFUL', $updatedQues, $totalQuestions);
					Log::add($output['msg'], Log::INFO, 'com_tmt');
				}

				if ($updatedQuesFail > 0)
				{
					$output['returns'] = 0;
					$msgArray[] = $output['msg'] = Text::sprintf('COM_TMT_CSV_IMPORT_UPDATE_FAIL', $updatedQuesFail, $totalQuestions);
					Log::add(Text::sprintf('COM_TMT_CSV_IMPORT_UPDATE_FAIL_LOG', $updatedQuesFailLineNo), Log::ERROR, 'com_tmt');
				}

				Log::add(Text::_("COM_TMT_QUESTIONS_LOG_CSV_END"), Log::INFO, 'com_tmt');

				if (!empty($msgArray))
				{
					$output['msg'] = '';

					$errorArray = array();

					foreach ($msgArray as $key => $msg)
					{
						if (is_array($msg))
						{
							foreach ($msg as $k => $value)
							{
								$errorArray[] = $value;
							}
						}
						else
						{
							$output['msg'] .= $msg . "</br>";
						}
					}

					if (!empty($errorArray))
					{
						$errorArray = array_count_values($errorArray);

						foreach ($errorArray as $msg => $count)
						{
							$output['msg'] .= '"' . $msg . '"' . " has occured " . $count . " times </br>";
						}
					}
				}
			}
			else
			{
				$output['returns'] = 1;
				$output['msg'] = Text::sprintf('COM_TMT_CSV_IMPORT_BLANK_DATA');
			}
		}
		else
		{
			$output['returns'] = 1;
			$output['msg'] = Text::sprintf('COM_TMT_FILE_READ_ERROR');
		}

		return $output;
	}

	/**
	 * The function to validate the uploaded format file
	 *
	 * @param   MIXED  $file_to_upload  file object
	 *
	 * @return  object of result and message
	 *
	 * @since 1.0.0
	 * */
	public function validateupload($file_to_upload)
	{
		$tjlmsparams = ComponentHelper::getParams('com_tjlms');

		$return = 1;
		$msg	= '';

		if ($file_to_upload["error"] == UPLOAD_ERR_OK)
		{
			/* check if file size in within the uploading limit of site*/
			if (($tjlmsparams->get('csv_question_filesize', 10) * 1024 * 1024) != 0)
			{
				if ( $_SERVER['CONTENT_LENGTH'] > ($tjlmsparams->get('lesson_upload_size', 0) * 1024 * 1024)
					|| $_SERVER['CONTENT_LENGTH'] > (int) (ini_get('upload_max_filesize')) * 1024 * 1024
					|| $_SERVER['CONTENT_LENGTH'] > (int) (ini_get('post_max_size')) * 1024 * 1024
					|| (($_SERVER['CONTENT_LENGTH'] > (int) (ini_get('memory_limit')) * 1024 * 1024) && ((int) (ini_get('memory_limit')) != -1)))
				{
					$return = 0;
					$msg = Text::sprintf('COM_TJLMS_UPLOAD_SIZE_ERROR', $tjlmsparams->get('lesson_upload_size', 10, 'INT') . ' MB');
				}
			}

			/* Check for the type/extensiom of the file*/
			if ($return == 1)
			{
				$filename = $file_to_upload['name'];
				$fileext = File::getExt($filename);

				$valid_extensions_arr = array('csv');

				if (!in_array($fileext, $valid_extensions_arr))
				{
					$msg = Text::_("COM_TMT_VALID_DOCUMENT_UPLOAD");
					$return = 0;
				}
			}
		}
		else
		{
			$return = 0;
			$msg = Text::_("COM_TJLMS_ERROR_UPLOADINGFILE", $filename);
		}

		$output['res'] = $return;
		$output['msg'] = $msg;

		return $output;
	}
}
