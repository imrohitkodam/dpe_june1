<?php
/**
 * @package     TJLms
 * @subpackage  com_shika
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Access\Access;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Component\ComponentHelper;

JLoader::import('components.com_tjlms.helpers.tracking', JPATH_SITE);

/**
 * Tools controller class.
 *
 * @since  1.3.25
 */
class TjlmsControllerTools extends FormController
{
	/**
	 * Method to calculate course progress
	 *
	 * @return  void
	 *
	 * @since   1.3.25
	 */
	public function calculateCourseProgress()
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$app = Factory::getApplication();
		$courseId = $app->input->get('courseId', 0, 'INT');
		$userId = $app->input->get('userId', 0, 'INT');

		$startLimit = $app->input->get('startLimit', 0, 'INT');
		$batchSize = COM_TJLMS_BATCH_SIZE_FOR_AJAX;

		if ($courseId && $batchSize)
		{
			BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjlms/models', 'TjlmsModel');
			$model = BaseDatabaseModel::getInstance('Manageenrollments', 'TjlmsModel', array('ignore_request' => true));
			$model->setState('filter.coursefilter', $courseId);
			$model->setState('list.start', $startLimit);
			$model->setState('list.limit', $batchSize);
// DPE HAck
			if(isset($userId))
			{
				$model->setState('filter.user_id', $userId);
			}
			else
			{
				$model->setState('filter.user_id', '');
			}
// DPE HAck end
			$enrolledUsers = $model->getItems();

			$flag = 0;
			$totalEnrolledUsers = $model->getTotal();
			$trackingHelper     = new ComtjlmstrackingHelper;

			$TjlmsCoursesHelper = new TjlmsCoursesHelper;
			$lessonIds          = $TjlmsCoursesHelper->getLessonsByCourse($courseId, array('l.id'));

			try
			{
				if ($startLimit <= $totalEnrolledUsers)
				{
					$course   = TJLMS::course($courseId);

					foreach ($lessonIds as $lesson)
					{
						foreach ($enrolledUsers as $eUser)
						{	
							
								$trackingHelper->addCourseTrackEntry($courseId, $eUser->user_id, $lesson->id);
						}

						// Archive lesson attempts if certificate is expired.
						$course->expireCertificate($eUser->user_id);
					}

					$flag = 1;
				}

				$result = new stdClass;
				$result->flag = $flag;
				$result->totalEnrolledUsers = $totalEnrolledUsers;
				$result->startLimit = $startLimit;
				echo new JsonResponse($result, Text::_('COM_TJLMS_CALCULATE_COURSE_PROGRESS_SUCCESSFUL'), false);
			}
			catch (Exception $e)
			{
				echo new JsonResponse(null, Text::_('COM_TJLMS_CALCULATE_COURSE_PROGRESS_FAILURE'), true);
			}
		}
		else
		{
			echo new JsonResponse(null, Text::_('COM_TJLMS_INVALID_REQUEST'), true);
		}

		$app->close();
	}

	/**
	 * Method to import CSV historical data
	 *
	 * @return  void
	 *
	 * @since   1.5.0
	 */
	public function historicalDataCSVImport()
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		header('Cache-Control: no-cache, must-revalidate');
		header('Content-type: application/json');
		jimport('joomla.log.log');
		$dateTime = str_replace(array(' ', '-', ':'), '_', Factory::getDate());
		$logFileName = 'com_tjlms.historical_data_import_' . $dateTime . '.log';

		JLog::addLogger(array('text_file' => $logFileName), JLog::ALL, array('com_tjlms'));
		$oluser_id = Factory::getUser()->id;

		// Set log file name to session
		$session     = Factory::getSession();
		$session->set('historical_filename', $logFileName);

		/* If user is not logged in*/
		if (!$oluser_id)
		{
			$ret = array();
			$ret['OUTPUT']['flag'] = 0;
			$ret['OUTPUT']['msg'] = Text::_('COM_TJLMS_MUST_LOGIN_TO_UPLOAD');
			echo json_encode($ret);
			jexit();
		}

		$input = Factory::getApplication()->input;
		$files = $input->files;

		$file_to_upload = $files->get('FileInput', '', 'ARRAY');

		$result = $this->saveCsvContent($file_to_upload);

		$ret['OUTPUT'] = $result;
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
	 * @since   1.0.0
	 */
	public function saveCsvContent($file_to_upload)
	{
		$lmsparams     = ComponentHelper::getParams('com_tjlms');
		$adminApproval = $lmsparams->get('admin_approval');

		// Initialize values
		$olUserId   = Factory::getUser()->id;
		$headerRow  = true;
		$messages   = $invalidCourses = array();
		$badUserAccess = $missingDetails = $invalidRows = $lineno = 0;
		
		$alreadyEnrolledCnt = $enrollSuccess = $prerequisiteCourse = $courseTrack = $lessonTrack = 0;
		$userId = 0;
		$logLink = '';
		$output = array('return' => 1, 'messages' => array());

		$csvFileName = $file_to_upload['name'];

		JLog::add(Text::sprintf('COM_TJLMS_MANAGEENROLLMENTS_LOG_CSV_FILE_NAME', $csvFileName), JLog::INFO, 'com_tjlms');

		JLog::add(Text::_("COM_TJLMS_MANAGEENROLLMENTS_LOG_CSV_START"), JLog::INFO, 'com_tjlms');

		$handle = fopen($file_to_upload['tmp_name'], 'r');

		$historicalData = array();

		while (($data = fgetcsv($handle)) !== false)
		{
			if ($headerRow)
			{
				// Parsing the CSV header
				$headers = array();
				
				$lineno++;

				foreach ($data as $d)
				{
					$header = strtolower(preg_replace('/[^A-Za-z0-9\-]/', '', $d));
					$headers[] = $header;
				}

				$headerRow = false;
			}
			elseif (count($headers) == count($data))
			{
				$data = array_map("trim", $data);
				$historicalData[] = array_combine($headers, $data);
			}
			else
			{
				$invalidRows++;
			}
		}

		if (empty($historicalData))
		{
			array_push($messages, array('error' => Text::_('COM_TJLMS_TITLE_MANAGEENROLLMENTS_IMPORT_BLANK_FILE')));
			$output['messages'] = $messages;

			return $output;
		}

		$optionalKeys = array('course_id', 'timestart', 'timeend', 'score', 'attempt');

		$missingKeys  = array_diff($optionalKeys, $headers);

		$dummyData  = array_combine($missingKeys, array_fill(0, count($missingKeys), ""));

		$totalUser = count($historicalData);
		$model = $this->getModel('Enrolment', 'TjlmsModel');

		foreach ($historicalData as $eachRow)
		{
			$lineno++;

			// Avoid warning for missing keys
			$eachRow = array_merge($eachRow, $dummyData);

			$identifier = $eachRow['datatype'];

			if (!in_array($identifier, array('course', 'lesson')))
			{
				array_push($messages, array('error' => Text::_('COM_TJLMS_CSV_IMPORT_COLUMN_MISSING')));
				$output['messages'] = $messages;

				return $output;
			}

			$courseId = $lessonid = '';

			if ($identifier == 'course')
			{
				$courseId = $eachRow['id'];
			}
			elseif ($identifier == 'lesson')
			{
				$lessonid = $eachRow['id'];
			}

			$email = $eachRow['email'];

			if (empty($eachRow['id']) || empty($email) || empty($eachRow['status']))
			{
				$missingDetails++;
				continue;
			}

			if (!empty($email))
			{
				$userId = $this->getUserId($eachRow['email']);

				if (empty($userId))
				{
					array_push($messages, array('error' => Text::_('COM_TJLMS_TOOLS_INVALID_EMAIL')));
					$output['messages'] = $messages;

					return $output;
				}
			}

			// User validation ended
			$userAccess = Access::getAuthorisedViewLevels($userId);

			$courseId = $courseId ? $courseId : $eachRow['courseid'];

			if (!empty($courseId))
			{
				$course = $this->checkCourseExist($courseId);

				if (empty($course))
				{
					$invalidCourses[$courseId] = $courseId;

					continue;
				}

				if (!in_array($course['access'], $userAccess))
				{
					$badUserAccess++;

					continue;
				}

				// Check prerequisite courses status before enrolling to the course. user need to complete prerequiste courses
				if (PluginHelper::isEnabled('tjlms', 'courseprerequisite'))
				{
					PluginHelper::importPlugin('tjlms');

					// Trigger all "checkPrerequisiteCourseStatus" plugins method
					$result = Factory::getApplication()->triggerEvent('onCheckPrerequisiteCourseStatus', array($courseId, $userId));

					if (!$result[0])
					{
						$prerequisiteCourse++;

						continue;
					}
				}

				$date            = new Date($eachRow['timestart']);
				$enrolledOnTime  = $date->toSql(true);
				$alreadyEnrolled = $model->checkIfuserEnrolled($userId, $courseId);

				if (!$alreadyEnrolled)
				{
					$state = '1';

					if ($adminApproval == '1' && $userId == $olUserId)
					{
						$state = '0';
					}

					$enrollArray = array();
					$enrollArray['user_id']     = $userId;
					$enrollArray['course_id']   = $courseId;
					$enrollArray['state']       = $state;
					$enrollArray['notify_user'] = 0;
					$enrollArray['enrolled_on_time'] = $enrolledOnTime;
					$enrollArray['coursestatus'] = $eachRow['coursestatus'];

					$successfulEnroled = $model->userEnrollment($enrollArray);

					if ($state && $successfulEnroled)
					{
						JLog::add(Text::sprintf("COM_TJLMS_MANAGEENROLLMENTS_LOG_NEW_ENROLL", $userId, $courseId), JLog::INFO, 'com_tjlms');
						$enrollSuccess ++;
					}
				}
				else
				{
					JLog::add(Text::sprintf("COM_TJLMS_MANAGEENROLLMENTS_LOG_ALREADY_ENROLL", $userId, $courseId), JLog::INFO, 'com_tjlms');
					$alreadyEnrolledCnt ++;
				}

				if ($identifier == 'course')
				{
					$trackingHelper = new ComtjlmstrackingHelper;

					if ($eachRow['status'] == 'Completed')
					{
						$eachRow['status'] = 'C';
					}
					elseif ($eachRow['status'] == 'Incomplete')
					{
						$eachRow['status'] = 'I';
					}
					else
					{
						$eachRow['status'] = '';
					}

					$trackingHelper->addCourseTrackEntry($courseId, $userId, 0, $eachRow['status']);

					JLog::add(Text::sprintf("COM_TJLMS_TOOLS_HISTORICAL_DATA_LOG_COURSE_TRACK_UPDATE", $userId, $courseId), JLog::INFO, 'com_tjlms');
						$courseTrack ++;
				}

				if ($identifier == 'lesson')
				{
					$trackingHelper = new ComtjlmstrackingHelper;

					$eachRow['status'] = lcfirst($eachRow['status']);

					$trackObj                = new stdClass;
					$trackObj->attempt       = $eachRow['attempt'] ? $eachRow['attempt'] : '';
					$trackObj->score         = $eachRow['score'] ? $eachRow['score'] : 0;
					$trackObj->lesson_status = $eachRow['status'] ? $eachRow['status'] : '';
					$trackObj->timestart     = $eachRow['timestart'] ? $eachRow['timestart'] : '';
					$trackObj->timeend       = $eachRow['timeend'] ? $eachRow['timeend'] : '';

					$trackingHelper->update_lesson_track($lessonid, $userId, $trackObj);

					$trackingHelper->addCourseTrackEntry($courseId, $userId);

					JLog::add(Text::sprintf("COM_TJLMS_TOOLS_HISTORICAL_DATA_LOG_LESSON_TRACK_UPDATE", $userId, $courseId, $lessonid), JLog::INFO, 'com_tjlms');
						$lessonTrack ++;
				}
			}
		}

		JLog::add(Text::_("COM_TJLMS_MANAGEENROLLMENTS_LOG_CSV_END"), JLog::INFO, 'com_tjlms');

		// Log file Path
		$logFilepath = JRoute::_('index.php?option=com_tjlms&view=tools&task=downloadLog&prefix=historical');

		$session  = JFactory::getSession();
		$config   = JFactory::getConfig();
		$filename = $session->get('historical_filename');
		$logfile  = $config->get('log_path') . '/' . $filename;

		if (JFile::exists($logfile))
		{
			$logLink = '<a href="' . $logFilepath . '" >' . Text::_("COM_TJLMS_ENROLLMENT_CSV_SAMPLE") . '</a>';
			$logLink =	Text::sprintf('COM_TJLMS_LOG_FILE_PATH', $logLink);
		}
		// Handle Messages
		$message = Text::sprintf('COM_TJLMS_MANAGEENROLLMENTS_IMPORT_TOTAL_ROWS_CNT_MSG', $totalUser) . ' ' . $logLink;
		array_push($messages, array('success' => $message));

		if ($adminApproval == 1)
		{
			array_push($messages, array('notice' => Text::_('COM_TJLMS_USER_NEED_ADMIN_APPROVAL')));
		}

		if ($missingDetails > 0)
		{
			$message = ($missingDetails == 1) ? 'COM_TJLMS_MANAGEENROLLMENTS_MANDATORY_FIELDS_ONE' : 'COM_TJLMS_MANAGEENROLLMENTS_MANDATORY_FIELDS';
			array_push($messages, array('error' => Text::sprintf($message, $missingDetails)));
		}

		if ($enrollSuccess > 0)
		{
			$message = ($enrollSuccess == 1) ?
				'COM_TJLMS_TITLE_MANAGEENROLLMENTS_IMPORT_NEWLY_SINGLE_USER_ENROLLED_MSG' :
				'COM_TJLMS_TITLE_MANAGEENROLLMENTS_IMPORT_NEWLY_ENROLLED_MSG';
			array_push($messages, array('success' => Text::sprintf($message, $enrollSuccess)));
		}

		if ($courseTrack > 0)
		{
			$message = ($courseTrack == 1) ?
				'COM_TJLMS_TOOLS_HISTORICAL_DATA_IMPORT_NEWLY_SINGLE_COURSE_TRACK_MSG' :
				'COM_TJLMS_TOOLS_HISTORICAL_DATA_IMPORT_NEWLY_COURSE_TRACK_MSG';
			array_push($messages, array('success' => Text::sprintf($message, $courseTrack)));
		}

		if ($lessonTrack > 0)
		{
			$message = ($lessonTrack == 1) ?
				'COM_TJLMS_TOOLS_HISTORICAL_DATA_IMPORT_NEWLY_SINGLE_LESSON_TRACK_MSG' :
				'COM_TJLMS_TOOLS_HISTORICAL_DATA_IMPORT_NEWLY_LESSON_TRACK_MSG';
			array_push($messages, array('success' => Text::sprintf($message, $lessonTrack)));
		}

		if ($alreadyEnrolledCnt > 0)
		{
			$message = ($alreadyEnrolledCnt == 1) ?
				'COM_TJLMS_TITLE_MANAGEENROLLMENTS_IMPORT_ALREADY_ENROLLED_MSG_ONE' :
				'COM_TJLMS_TITLE_MANAGEENROLLMENTS_IMPORT_ALREADY_ENROLLED_MSG';
			array_push($messages, array('notice' => Text::sprintf($message, $alreadyEnrolledCnt)));
		}

		if ($badUserAccess > 0)
		{
			$message = 'COM_TJLMS_TITLE_MANAGEENROLLMENTS_IMPORT_BAD_USER_ACCESS';
			array_push($messages, array('error' => Text::sprintf($message, $badUserAccess)));
		}

		if ($prerequisiteCourse > 0)
		{
			$message = 'COM_TJLMS_VIEW_COURSE_PREREQUISITE_NOT_ALLOWED_ENROLLMENT';
			array_push($messages, array('error' => Text::sprintf($message, $prerequisiteCourse)));
		}

		$badCourses = count($invalidCourses);

		if ($badCourses > 0)
		{
			$message = ($badCourses == 1) ? 'COM_TJLMS_MANAGEENROLLMENTS_BAD_COURSE_ID' : 'COM_TJLMS_MANAGEENROLLMENTS_BAD_COURSE_IDS';
			array_push($messages, array('error' => Text::sprintf($message, implode(',', $invalidCourses))));
		}

		$output['messages'] = $messages;

		return $output;
	}

	/**
	 * Returns userid if a user exists
	 *
	 * @param   string  $email  The email to search on.
	 *
	 * @return  integer  The user id or 0 if not found.
	 *
	 * @since   1.5.0
	 */
	private function getUserId($email)
	{
		// Initialise some variables
		$db = JFactory::getDbo();
		$query = $db->getQuery(true)
			->select($db->quoteName('id'))
			->from($db->quoteName('#__users'))
			->where($db->quoteName('email') . ' = ' . $db->quote($email));
		$db->setQuery($query, 0, 1);

		return $db->loadResult();
	}

	/**
	 * Method to check course exist.
	 *
	 * @param   INT  $courseId  login user email.
	 *
	 * @return  mixed  Return course id.
	 *
	 * @since   1.5.0
	 */
	private function checkCourseExist($courseId)
	{
		static $checkedCourses = array();

		if (!isset($checkedCourses[$courseId]))
		{
			$db = JFactory::getDbo();

			// Check the customer id (in users table) already exist or not
			$query = $db->getQuery(true);
			$query->select('id, state, access');
			$query->from('`#__tjlms_courses`');
			$query->where('id = ' . (int) $courseId);
			$db->setQuery($query);

			$checkedCourses[$courseId] = $db->loadAssoc();
		}

		return $checkedCourses[$courseId];
	}
}
