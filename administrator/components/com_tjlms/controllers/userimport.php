<?php
/**
 * @package    Shika
 * @author     TechJoomla | <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\User\User;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Access\Access;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\User\UserHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Controller\FormController;

jimport('joomla.user.user');
JLoader::import('components.com_users.models.user', JPATH_ADMINISTRATOR);
JLoader::import('components.com_tjlms.helpers.main', JPATH_SITE);
jimport('joomla.filesystem.file');

/**
 * File upload controller class.
 *
 * @since  1.0.0
 */
class TjlmsControllerUserimport extends FormController
{
	protected $defaultUserGroup = 0;

	/**
	 * CSV file data store in entroll table of Tjlms.
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	public function csvImport()
	{
		header('Cache-Control: no-cache, must-revalidate');
		header('Content-type: application/json');
		jimport('joomla.log.log');
		$dateTime = str_replace(array(' ', '-', ':'), '_', Factory::getDate());
		$logFileName = 'com_tjlms.manage_enrollment_import_' . $dateTime . '.log';

		Log::addLogger(array('text_file' => $logFileName), Log::ALL, array('com_tjlms'));
		$oluser_id = Factory::getUser()->id;

		// Set log file name to session
		$session     = Factory::getSession();
		$session->set('enrollment_filename', $logFileName);

		/* If user is not logged in*/
		if (!$oluser_id)
		{
			$ret['OUTPUT']['flag'] = 0;
			$ret['OUTPUT']['msg'] = Text::_('COM_TJLMS_MUST_LOGIN_TO_UPLOAD');
			echo json_encode($ret);
			jexit();
		}

		$input = Factory::getApplication()->input;
		$tjlmsparams = ComponentHelper::getParams('com_tjlms');

		$files = $input->files;
		$post = $input->post;

		$file_to_upload = $files->get('FileInput', '', 'ARRAY');
		$filepath = 'media/com_tjlms/userimport/';
		$notify_user = $post->get('notify_user_import', '', 'INT');

		$return = 1;
		$msg = '';

		$file_attached    = $file_to_upload['tmp_name'];
		$filename = $file_to_upload['name'];
		$filepath_with_file = $filepath . $filename;
		$newfilename = $filename;

			/* Save csv content to question table */
		$result = $this->saveCsvContent($file_to_upload, $notify_user);

		$filename = $file_to_upload['name'];

		$ret['OUTPUT'] = $result;
		echo json_encode($ret);
		jexit();
	}

	/**
	 * Save question to table from csv
	 *
	 * @param   MIXED  $file_to_upload  file object
	 * @param   INT    $notify_user     notify user
	 *
	 * @return  ARRAY
	 *
	 * @since   1.0.0
	 */
	public function saveCsvContent($file_to_upload, $notify_user)
	{
		$lmsparams     = ComponentHelper::getParams('com_tjlms');
		$adminApproval = $lmsparams->get('admin_approval');

		$params = ComponentHelper::getParams('com_users');
		$this->defaultUserGroup = $params->get('new_usertype');

		// Initialize values
		$tzList = DateTimeZone::listIdentifiers(DateTimeZone::ALL);
		$olUserId   = Factory::getUser()->id;
		$headerRow  = true;
		$messages   = $invalidCourses = $badGroups = array();
		$newUsers = $badUserAccess = $userExists = $missingDetails = $notCreateUsers = $invalidRows = $updateUsers = $badTimeZoneCnt = $lineno = 0;
		$assignSuccess = $assignedUpdated = $invalidDate = $startLessToday = $startGtDue = $alreadyNotEnrolledCnt = 0;
		$alreadyEnrolledCnt = $enrollSuccess = $deenrollSuccess = $prerequisiteCourse = 0;
		$userId = 0;
		$logLink = '';
		$output = array('return' => 1, 'messages' => array());

		// Include path of jlike model file and call api to assign user
		JLoader::import('components.com_jlike.models.recommend', JPATH_SITE);
		$recommendModel = null;

		if (class_exists('JlikeModelRecommend'))
		{
			$recommendModel = new JlikeModelRecommend;
		}

		$csvFileName = $file_to_upload['name'];

		Log::add(Text::sprintf('COM_TJLMS_MANAGEENROLLMENTS_LOG_CSV_FILE_NAME', $csvFileName), Log::INFO, 'com_tjlms');

		Log::add(Text::_("COM_TJLMS_MANAGEENROLLMENTS_LOG_CSV_START"), Log::INFO, 'com_tjlms');

		$handle = fopen($file_to_upload['tmp_name'], 'r');

		while (($data = fgetcsv($handle)) !== false)
		{
			if ($headerRow)
			{
				$lineno++;

				// Parsing the CSV header
				$headers    = array();
				$userFieldsName = array();
				$enrollmentFieldsName = array();

				foreach ($data as $d)
				{
					$pattern     = "/U_fields/";

					if (preg_match($pattern, $d))
					{
						$userReplaceHead = preg_replace($pattern, " ", $d);
					}

					$pattern     = "/E_fields/";

					if (preg_match($pattern, $d))
					{
						$enrollmentReplaceHead = preg_replace($pattern, " ", $d);
					}

					if (preg_match('/[\[\]\']/', $userReplaceHead))
					{
						$userFieldsName[] = trim(str_replace (array('[', ']'), '' , $userReplaceHead));
						$header       = $d;
					}

					if (preg_match('/[\[\]\']/', $enrollmentReplaceHead))
					{
					    $enrollmentFieldsName[] = trim(str_replace (array('[', ']'), '' , $enrollmentReplaceHead));
						$header       = $d;
					}

					$header = strtolower(preg_replace('/[^A-Za-z0-9\-]/', '', $d));

					if (strpos($header, 'assignmentstart') === 0)
					{
						$header = 'assignmentstart';
					}

					if (strpos($header, 'assignmentdue') === 0)
					{
						$header = 'assignmentdue';
					}

					if (strpos($header, 'coursestartdate') === 0)
					{
						$header = 'startdate';
					}

					if (strpos($header, 'courseenddate') === 0)
					{
						$header = 'enddate';
					}

					if (strpos($header, 'disableuser') === 0)
					{
						$header = 'block';
					}

					$headers[] = $header;
				}

				$headerRow = false;
			}
			elseif (count($headers) == count($data))
			{
				$data = array_map("trim", $data);
				$userData[] = array_combine($headers, $data);
			}
			else
			{
				$invalidRows++;
			}
		}

		if (!empty($userFieldsName) || !empty($enrollmentFieldsName))
		{
			$fieldIds = array();

			foreach ($userFieldsName as $fieldName)
			{
				$field = $this->checkFieldExist($fieldName, 'com_users.user');

				if ($field)
				{
					$fieldIds[$fieldName] = $field['id'];
				}
				else
				{
					array_push($messages, array('error' => Text::sprintf('COM_TJLMS_TITLE_MANAGEENROLLMENTS_IMPORT_FIELD_NAME_NOT_EXIST', $fieldName)));
					$output['messages'] = $messages;

					return $output;
				}
			}

			foreach ($enrollmentFieldsName as $fieldName)
			{
				$field = $this->checkFieldExist($fieldName, 'com_tjlms.manageenrollment');

				if ($field)
				{
					$fieldIds[$fieldName] = $field['id'];
				}
				else
				{
					array_push($messages, array('error' => JText::sprintf('COM_TJLMS_TITLE_MANAGEENROLLMENTS_IMPORT_FIELD_NAME_NOT_EXIST', $fieldName)));
					$output['messages'] = $messages;

					return $output;
				}
			}
		}

		if (empty($userData))
		{
			array_push($messages, array('error' => Text::_('COM_TJLMS_TITLE_MANAGEENROLLMENTS_IMPORT_BLANK_FILE')));
			$output['messages'] = $messages;

			return $output;
		}

		$optionalKeys = array('id', 'lastname', 'username', 'block', 'startdate', 'duedate', 'addgroups', 'removegroups' , 'timezone');

		$missingKeys  = array_diff($optionalKeys, $headers);

		$dummyData  = array_combine($missingKeys, array_fill(0, count($missingKeys), ""));

		$totalUser    = count($userData);
		$model = $this->getModel('Enrolment', 'TjlmsModel');

		// If courseprerequisite plugin enable then create dispatcher Object
		if (PluginHelper::isEnabled('tjlms', 'courseprerequisite'))
		{
			PluginHelper::importPlugin('tjlms');
		}

		foreach ($userData as $key => $eachUser)
		{
			$updateUsersFlag = 0;
			$lineno++;
			$userFieldArray = array();
			$enrollmentFieldArray = array();

			if (!empty($eachUser['startdate']) && !empty($eachUser['enddate']))
			{
				if ($this->checkDateTime($eachUser['startdate']) && $this->checkDateTime($eachUser['enddate']))
				{
					$eachUser['startdate'] = (!empty($eachUser['startdate'])) ? date('Y-m-d H:i:s', strtotime($eachUser['startdate'])) : '0000-00-00 00:00:00';
					$eachUser['enddate'] = (!empty($eachUser['enddate'])) ? date('Y-m-d H:i:s', strtotime($eachUser['enddate'])) : '0000-00-00 00:00:00';

					if ($eachUser['startdate'] > $eachUser['enddate'])
					{
						array_push($messages, array('error' => JText::_('COM_TJLMS_CSV_IMPORT_START_DATE_IS_GREATER_THAN_END_DATE')));
						$output['messages'] = $messages;

						return $output;
					}
				}
				else
				{
					array_push($messages, array('error' => JText::_('COM_TJLMS_CSV_IMPORT_START_DATE_AND_END_DATE_IS_INVALID')));
						$output['messages'] = $messages;

						return $output;
				}
			}

			foreach ($eachUser as $i => $eUser)
			{
				$pattern     = "/ufields/";
				$userReplaceHead = preg_replace($pattern, " ", $i);

				if (preg_match('/[\[\]\'^£$%&*()}{@#~?><>,|=_+¬-]/', $userReplaceHead))
				{
					$userFieldName              = trim(str_replace (array('[', ']'), '' , $userReplaceHead));
					$userFieldArray[$userFieldName] = $eUser;
				}

				$pattern     = "/efields/";
				$enrollmentReplaceHead = preg_replace($pattern, " ", $i);

				if (preg_match('/[\[\]\'^£$%&*()}{@#~?><>,|=_+¬-]/', $enrollmentReplaceHead))
				{
					$enrollmentFieldName              = trim(str_replace (array('[', ']'), '' , $enrollmentReplaceHead));
					$enrollmentFieldArray[$enrollmentFieldName] = $eUser;
				}
			}

			if (!empty($eachUser['timezone']))
			{
				$timezone = array_map('trim', explode("/", $eachUser['timezone']));
				$eachUser['timezone'] = implode("/", array_map('ucwords', $timezone));

				if (in_array($eachUser['timezone'], $tzList))
				{
					$eachUser['params']['timezone'] = $eachUser['timezone'];
				}
				else
				{
					$msg = "COM_TJLMS_MANAGEENROLLMENTS_BAD_TIMEZONE_USER";
					Log::add(Text::sprintf($msg, $lineno), Log::ERROR, 'com_tjlms');
					$badTimeZoneCnt++;
					continue;
				}
			}

			// Avoid warning for missing keys

			$eachUser = array_merge($eachUser, $dummyData);

			$identifier = $eachUser['usermatchkey'];

			if (!$identifier || $identifier == 'id')
			{
				$userId = $eachUser['id'];
			}
			else
			{
				if (!in_array($identifier, array('id', 'email', 'username')))
				{
					array_push($messages, array('error' => Text::_('COM_TJLMS_CSV_IMPORT_COLUMN_MISSING')));
					$output['messages'] = $messages;

					return $output;
				}

				if ($identifier == 'username')
				{
					$userId = UserHelper::getUserId($eachUser['username']);
				}
				elseif ($identifier == 'email')
				{
					$userId = $this->getUserId($eachUser['email']);
				}
			}

			$email  = $eachUser['email'];
			$firstname = $eachUser['firstname'];

			if (!empty($userId))
			{
				$user = Factory::getUser($userId);
				$userId = $user->id;
			}

			if (empty($userId) && (empty($email) || empty($firstname)))
			{
				$missingDetails++;
				continue;
			}

			if ($userId)
			{
				$updateUsersFlag = 1;
			}

			if (!empty($userFieldArray))
			{
				$eachUser['com_fields'] = $userFieldArray;
			}

			$eachUser['id'] = $userId;
			$userId  = $this->createUpdateUser($eachUser);

			if (empty($userId))
			{
				$notCreateUsers++;
				continue;
			}
			else
			{
				if ($updateUsersFlag == 1)
				{
					$updateUsers++;
				}
				else
				{
					$newUsers++;
				}
			}

			// User validation ended
			$user       = Factory::getUser($userId);
			$userAccess = Access::getAuthorisedViewLevels($userId);

			$addCourses = $eachUser['addcourses'];

			if (!empty($addCourses))
			{
				$courseIds = explode("|", $addCourses);

				// Enrollment starts here
				foreach ($courseIds as $courseId)
				{
					if ($courseId)
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
							// Trigger all "checkPrerequisiteCourseStatus" plugins method
							$result = Factory::getApplication()->triggerEvent('onCheckPrerequisiteCourseStatus', array($courseId, $userId));

							if (!$result[0])
							{
								$prerequisiteCourse++;

								continue;
							}
						}
					}

					$startDate = $eachUser['assignmentstart'];
					$dueDate   = $eachUser['assignmentdue'];
					$date      = new Date($startDate);

					$enrolledOnTime  = $date->toSql(true);
					$alreadyEnrolled = $this->checkIfuserEnrolled($userId, $courseId);

					$checkCourseTrack = TJLMS::Coursetrack($userId, $courseId);

					if ($checkCourseTrack->status == 'C')
					{
						$eachUser['coursestatus'] = 'C';
					}

					$assignmentMsg = 0;

					// Course assignment CSV Import
					// If start date and Due date then assign User

					if (!empty($startDate) && !empty($dueDate))
					{
						if (!($this->checkDateTime($startDate)) || !($this->checkDateTime($dueDate)))
						{
							$invalidDate++;
							continue;
						}
						elseif (date("Y-m-d", strtotime($startDate)) < date("Y-m-d"))
						{
							$msg = "COM_TJLMS_MANAGEENROLLMENTS_LOG_SDATE_LESS_THAN_TODAY";
							Log::add(
							Text::sprintf($msg, date("Y-m-d", strtotime($startDate)), date("Y-m-d"), $userId, $courseId), Log::ERROR, 'com_tjlms');
							$startLessToday++;
							continue;
						}
						elseif (date("Y-m-d", strtotime($startDate)) > date("Y-m-d", strtotime($dueDate)))
						{
							$msg = "COM_TJLMS_MANAGEENROLLMENTS_LOG_EDATE_LESS_THAN_SDATE";
							Log::add(
							Text::sprintf($msg, date("Y-m-d", strtotime($dueDate)), date("Y-m-d", strtotime($startDate)), $userId, $courseId), Log::ERROR, 'com_tjlms');
							$startGtDue++;
							continue;
						}
						else
						{
							$data = array();
							$alreadyassigned = $this->checkIfuserAssigned($userId, $courseId);

							// Already assigned then take id of the record and increment count
							if (isset($alreadyassigned))
							{
								Log::add(Text::sprintf("COM_TJLMS_MANAGEENROLLMENTS_LOG_ALREADY_ASSIGNED", $userId, $courseId), Log::INFO, 'com_tjlms');

								$data['todo_id']    = $alreadyassigned;
								$assignedUpdated++;
							}

							$data['type']              = 'assign';
							$data['sender_msg']        = '';
							$data['start_date']        = date("Y-m-d", strtotime($startDate));
							$data['due_date']          = date("Y-m-d", strtotime($dueDate));
							$data['recommend_friends'] = array($userId);

							$options = array('element' => 'com_tjlms.course', 'element_id' => $courseId, 'plg_name' => 'jlike_tjlms', 'plg_type' => 'content', 'coursestatus' => $eachUser['coursestatus']);

							if ($recommendModel)
							{
								$recommendModel->assignRecommendUsers($data, $options, $notify_user);
								$msg = "COM_TJLMS_MANAGEENROLLMENTS_LOG_NEW_ASSIGN";
								Log::add(Text::sprintf($msg, $userId, $courseId, $data['start_date'], $data['due_date']), Log::INFO, 'com_tjlms');
								$assignmentMsg = 1;
							}

							// If user not aready assigned then increment assigned count
							if (!$alreadyassigned)
							{
								$assignSuccess++;
							}
						}
					}

					if (!$alreadyEnrolled)
					{
						$notify_user_enroll = $notify_user;
						$state = '1';

						if ($adminApproval == '1' && $userId == $olUserId)
						{
							$state = '0';
						}

						// Import CSV notify users by only one mail after Enroll/Assign

						if (!empty($startDate) && !empty($dueDate))
						{
							$notify_user_enroll = 0;
						}

						if (!empty($eachUser['startdate']))
						{
							$enrolledOnTime = $eachUser['startdate'];
						}

						$enrollArray = array();
						$enrollArray['user_id']     = $userId;
						$enrollArray['course_id']   = $courseId;
						$enrollArray['state']       = $state;
						$enrollArray['notify_user'] = $notify_user_enroll;
						$enrollArray['enrolled_on_time'] = $enrolledOnTime;
						$enrollArray['coursestatus'] = $eachUser['coursestatus'];
						$enrollArray['timestart']   = $eachUser['startdate'];
						$enrollArray['timeend']     = $eachUser['enddate'];

						if (!empty($enrollmentFieldArray))
						{
							$enrollArray['com_fields'] = $enrollmentFieldArray;
						}

						$successfulEnroled = $model->userEnrollment($enrollArray);

						if ($state && $successfulEnroled && !$assignmentMsg)
						{
							Log::add(Text::sprintf("COM_TJLMS_MANAGEENROLLMENTS_LOG_NEW_ENROLL", $userId, $courseId), Log::INFO, 'com_tjlms');
							$enrollSuccess ++;
						}
					}
					elseif (!$assignmentMsg)
					{
						Log::add(Text::sprintf("COM_TJLMS_MANAGEENROLLMENTS_LOG_ALREADY_ENROLL", $userId, $courseId), Log::INFO, 'com_tjlms');

						$alreadyEnrolledCnt ++;
					}
				}
			}

			$removeCourses = $eachUser['removecourses'];

			if (!empty($removeCourses))
			{
				$courseIds = explode("|", $removeCourses);

				foreach ($courseIds as $courseId)
				{
					if ($courseId)
					{
						$course = $this->checkCourseExist($courseId);

						if (empty($course))
						{
							$invalidCourses[$courseId] = $courseId;

							continue;
						}
					}

					$alreadyEnrolled = $this->checkIfuserEnrolled($userId, $courseId);

					if (!empty($alreadyEnrolled))
					{
						$state = '0';

						$enrollArray = array();
						$enrollArray['user_id']     = $userId;
						$enrollArray['course_id']   = $courseId;
						$enrollArray['state']       = $state;
						$enrollArray['timestart']	= $eachUser['startdate'];
						$enrollArray['timeend']		= $eachUser['enddate'];


						if (!empty($enrollmentFieldArray))
						{
							$enrollArray['com_fields'] = $enrollmentFieldArray;
						}


						$successfulEnroled = $model->userEnrollment($enrollArray);


						if ($state == 0 && $successfulEnroled)
						{
							Log::add(Text::sprintf("COM_TJLMS_MANAGEENROLLMENTS_LOG_DE_ENROLL", $userId, $courseId), Log::INFO, 'com_tjlms');
							$deenrollSuccess ++;
						}
					}
					else
					{
						Log::add(Text::sprintf("COM_TJLMS_MANAGEENROLLMENTS_LOG_NOT_ALREADY_ENROLL", $userId, $courseId), Log::INFO, 'com_tjlms');
						$alreadyNotEnrolledCnt ++;
					}
				}
			}
		}

		Log::add(Text::_("COM_TJLMS_MANAGEENROLLMENTS_LOG_CSV_END"), Log::INFO, 'com_tjlms');

		// Log file Path
		$logFilepath = Route::_('index.php?option=com_tjlms&view=manageenrollments&task=downloadLog&prefix=enrollment');

		$session  = Factory::getSession();
		$config   = Factory::getConfig();
		$filename = $session->get('enrollment_filename');
		$logfile  = $config->get('log_path') . '/' . $filename;

		if (File::exists($logfile))
		{
			$logLink = '<a href="' . $logFilepath . '" >' . Text::_("COM_TJLMS_ENROLLMENT_CSV_SAMPLE") . '</a>';
			$logLink =	Text::sprintf('COM_TJLMS_LOG_FILE_PATH', $logLink);
		}
		// Handle Messages
		$message = Text::sprintf('COM_TJLMS_MANAGEENROLLMENTS_IMPORT_TOTAL_ROWS_CNT_MSG', $totalUser) . ' ' . $logLink;
		array_push($messages, array('success' => $message));

		if ($missingDetails > 0)
		{
			$message = ($missingDetails == 1) ? 'COM_TJLMS_MANAGEENROLLMENTS_MANDATORY_FIELDS_ONE' : 'COM_TJLMS_MANAGEENROLLMENTS_MANDATORY_FIELDS';
			array_push($messages, array('error' => Text::sprintf($message, $missingDetails)));
		}

		if ($userExists > 0)
		{
			$message = 'COM_TJLMS_TITLE_MANAGEENROLLMENTS_IMPORT_USER_EXIST';
			array_push($messages, array('notice' => Text::sprintf($message, $userExists)));
		}

		if ($newUsers > 0)
		{
			$message = ($newUsers == 1) ? 'COM_TJLMS_TITLE_MANAGEENROLLMENTS_IMPORT_NEW_USER_MSG' : 'COM_TJLMS_TITLE_MANAGEENROLLMENTS_IMPORT_NEW_USERS_MSG';
			array_push($messages, array('success' => Text::sprintf($message, $newUsers)));
		}

		if ($updateUsers > 0)
		{
			$message = ($updateUsers == 1) ? 'COM_TJLMS_TITLE_MANAGEENROLLMENTS_IMPORT_UPDATE_USER_MSG' :
			'COM_TJLMS_TITLE_MANAGEENROLLMENTS_IMPORT_UPDATE_USERS_MSG';
			array_push($messages, array('success' => Text::sprintf($message, $updateUsers)));
		}

		if ($notCreateUsers > 0)
		{
			$message = ($notCreateUsers == 1) ? 'COM_TJLMS_MANAGEENROLLMENTS_BAD_USERDATA' : 'COM_TJLMS_MANAGEENROLLMENTS_BAD_USERDATA';
			array_push($messages, array('error' => Text::sprintf($message, $notCreateUsers)));
		}

		if ($enrollSuccess > 0)
		{
			$message = ($enrollSuccess == 1) ?
				'COM_TJLMS_TITLE_MANAGEENROLLMENTS_IMPORT_NEWLY_SINGLE_USER_ENROLLED_MSG' :
				'COM_TJLMS_TITLE_MANAGEENROLLMENTS_IMPORT_NEWLY_ENROLLED_MSG';
			array_push($messages, array('success' => Text::sprintf($message, $enrollSuccess)));
		}

		if ($deenrollSuccess > 0)
		{
			$message = ($deenrollSuccess == 1) ?
				'COM_TJLMS_TITLE_MANAGEENROLLMENTS_IMPORT_SINGLE_USER_DE_ENROLLED_MSG' :
				'COM_TJLMS_TITLE_MANAGEENROLLMENTS_IMPORT_DE_ENROLLED_MSG';
			array_push($messages, array('success' => Text::sprintf($message, $deenrollSuccess)));
		}

		if ($assignSuccess > 0)
		{
			$message = ($enrollSuccess == 1) ?
				'COM_TJLMS_TITLE_MANAGEENROLLMENTS_IMPORT_NEWLY_SINGLE_USER_ASSIGN_MSG' :
				'COM_TJLMS_TITLE_MANAGEENROLLMENTS_IMPORT_NEWLY_ASSIGN_MSG';
			array_push($messages, array('success' => Text::sprintf($message, $assignSuccess)));
		}

		if ($alreadyEnrolledCnt > 0)
		{
			$message = ($alreadyEnrolledCnt == 1) ?
				'COM_TJLMS_TITLE_MANAGEENROLLMENTS_IMPORT_ALREADY_ENROLLED_MSG_ONE' :
				'COM_TJLMS_TITLE_MANAGEENROLLMENTS_IMPORT_ALREADY_ENROLLED_MSG';
			array_push($messages, array('notice' => Text::sprintf($message, $alreadyEnrolledCnt)));
		}

		if ($alreadyNotEnrolledCnt > 0)
		{
			$message = ($alreadyNotEnrolledCnt == 1) ?
				'COM_TJLMS_TITLE_MANAGEENROLLMENTS_IMPORT_ALREADY_NOT_ENROLLED_MSG_ONE' :
				'COM_TJLMS_TITLE_MANAGEENROLLMENTS_IMPORT_ALREADY_NOT_ENROLLED_MSG';
			array_push($messages, array('notice' => Text::sprintf($message, $alreadyNotEnrolledCnt)));
		}

		if ($assignedUpdated > 0)
		{
			$message = ($assignedUpdated == 1) ?
				'COM_TJLMS_TITLE_MANAGEENROLLMENTS_IMPORT_ALREADY_ASSIGNED_MSG_ONE' :
				'COM_TJLMS_TITLE_MANAGEENROLLMENTS_IMPORT_ALREADY_ASSIGNED_MSG';
			array_push($messages, array('notice' => Text::sprintf($message, $assignedUpdated)));
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

		$badGroupsCnt = count($badGroups);

		if ($badGroupsCnt > 0)
		{
			$message = ($badGroupsCnt == 1) ? 'COM_TJLMS_MANAGEENROLLMENTS_BAD_GROUP_ONE' : 'COM_TJLMS_MANAGEENROLLMENTS_BAD_GROUP';
			array_push($messages, array('error' => Text::sprintf($message, implode(',', $badGroups))));
		}

		if ($invalidDate > 0)
		{
			$message = ($invalidDate == 1) ? 'COM_TJLMS_MANAGEENROLLMENTS_ASSIGNMENT_INVALID_DATE_ONE' : 'COM_TJLMS_MANAGEENROLLMENTS_ASSIGNMENT_INVALID_DATE';
			array_push($messages, array('error' => Text::sprintf($message, $invalidDate)));
		}

		if ($startLessToday > 0)
		{
			$message = ($startLessToday == 1) ?
				'COM_TJLMS_MANAGEENROLLMENTS_ASSIGNMENT_START_DATE_NOT_VALID_ONE' :
				'COM_TJLMS_MANAGEENROLLMENTS_ASSIGNMENT_START_DATE_NOT_VALID';
			array_push($messages, array('error' => Text::sprintf($message, $startLessToday)));
		}

		if ($startGtDue > 0)
		{
			$message = ($startGtDue == 1) ?
				'COM_TJLMS_MANAGEENROLLMENTS_ASSIGNMENT_START_GT_DUEDATE_ONE' :
				'COM_TJLMS_MANAGEENROLLMENTS_ASSIGNMENT_START_GT_DUEDATE';
			array_push($messages, array('error' => Text::sprintf($message, $startGtDue)));
		}

		if ($badTimeZoneCnt > 0)
		{
			$message = ($badTimeZoneCnt == 1) ? 'COM_TJLMS_MANAGEENROLLMENTS_BAD_TIMEZONE_ONE' : 'COM_TJLMS_MANAGEENROLLMENTS_BAD_TIMEZONE';
			array_push($messages, array('error' => Text::sprintf($message, $badTimeZoneCnt)));
		}

		$output['messages'] = $messages;

		return $output;
	}

	/**
	 * Check course exist in lms.
	 *
	 * @param   INT  $courseId  login user email.
	 *
	 * @return  mixed  Return course id.
	 *
	 * @since   1.0
	 */
	public function checkCourseExist($courseId)
	{
		static $checkedCourses = array();

		if (!isset($checkedCourses[$courseId]))
		{
			$db = Factory::getDbo();

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

	/**
	 * Create joomla user.
	 *
	 * @param   array  $userData  A record object formfield.
	 *
	 * @return  mixed  Return user id.
	 *
	 * @since   1.0
	 */

	public function createUpdateUser($userData)
	{
		$addGroups = empty($userData["addgroups"]) ? '' : $userData["addgroups"];
		$user           = new User($userData['id']);
		$userData['id'] = $user->id;

		$newUser = 0;

		if (empty($userData['id']))
		{
			$newUser = 1;

			$userData['groups'] = (array) $this->defaultUserGroup;

			if (!empty($addGroups))
			{
				$userData['groups'] = explode("|", $addGroups);
			}
		}

		if (!$newUser && !empty($userData['password']))
		{
			$userData['password2'] = $userData['password'];
		}

		$name     = $userData['firstname'] . ' ' . $userData['lastname'];
		$username = '';

		if ($newUser)
		{
			$username = empty($userData['username']) ? trim($userData['email']) : $userData['username'];
		}

		$userData['name']     = empty(trim($name)) ? $user->name : $name;
		$userData['username'] = empty($username) ? $user->username : $username;
		$userData['email']    = empty(trim($userData['email'])) ? $user->email : trim($userData['email']);

		if (!$newUser)
		{
			$userData['registerDate'] = $user->registerDate;
			$userData['lastvisitDate'] = $user->lastvisitDate;
		}

		$user = new User($userData['id']);

		if ($user->bind($userData))
		{
			if ($user->save())
			{
				if ($newUser)
				{
					Log::add(Text::sprintf("COM_TJLMS_MANAGEENROLLMENTS_LOG_NEW_USER", $user->id, json_encode($user->groups)), Log::INFO, 'com_tjlms');
				}
				else
				{
					Log::add(Text::sprintf("COM_TJLMS_MANAGEENROLLMENTS_LOG_UPDATED_USER", $user->id), Log::INFO, 'com_tjlms');
				}

				if (!$newUser)
				{
					$userId = $user->id;

					if (!empty($addGroups))
					{
						$groupIds = explode("|", $addGroups);

						foreach ($groupIds as $groupId)
						{
							try
							{
								UserHelper::addUserToGroup($userId, $groupId);
							}
							catch (Exception $e)
							{
								Log::add(
								Text::sprintf("COM_TJLMS_MANAGEENROLLMENTS_LOG_USERGROUP_ADD_FAIL", $userData['email'], $groupId, $e->getMessage()
								), Log::ERROR, 'com_tjlms'
								);
							}
						}
					}

					if (!empty($userData["removegroups"]))
					{
						$groupIds = explode("|", $userData["removegroups"]);

						foreach ($groupIds as $groupId)
						{
							try
							{
								UserHelper::removeUserFromGroup($userId, $groupId);
							}
							catch (Exception $e)
							{
								Log::add(
								Text::sprintf("COM_TJLMS_MANAGEENROLLMENTS_LOG_USERGROUP_REMOVE_FAIL",
								$userData['email'], $groupId, $e->getMessage()
								), Log::ERROR, 'com_tjlms'
								);
							}
						}
					}
				}

				return $user->id;
			}
		}

		Log::add(Text::sprintf("COM_TJLMS_MANAGEENROLLMENTS_LOG_ERROR_NEW_USER", $user->getError(), $userData['email']), Log::ERROR, 'com_tjlms');

		return false;
	}

	/**
	 * For user enroll data
	 *
	 * @param   Int  $userId    User id.
	 * @param   Int  $courseId  course id.
	 *
	 * @return  mixed  Return User Enroll Data.
	 *
	 * @since   1.0
	 */

	public function checkIfuserEnrolled($userId, $courseId)
	{
		if ($userId && $courseId)
		{
			$db = Factory::getDbo();

			// Check the customer id (in users table) already exist or not
			$query = $db->getQuery(true);
			$query->select('id');
			$query->from('`#__tjlms_enrolled_users`');
			$query->where('user_id = ' . (int) $userId);
			$query->where('course_id = ' . (int) $courseId);
			$db->setQuery($query);

			return $db->loadResult();
		}
	}

	/**
	 * For user ASSIGN data
	 *
	 * @param   Int  $userId    User id.
	 * @param   Int  $courseId  course id.
	 *
	 * @return  mixed  Return User Assign Data.
	 *
	 * @since   1.0
	 */
	public function checkIfuserAssigned($userId,$courseId)
	{
		if ($userId && $courseId)
		{
			$db = Factory::getDbo();

			// Check if user already assigned
			$query = $db->getQuery(true);
			$query->select('id');
			$query->from('`#__jlike_todos`');
			$query->where('assigned_to = ' . ($userId));
			$query->where('title = ' . ($courseId));
			$db->setQuery($query);

			return $db->loadResult();
		}
	}

	/**
  * Check user group exist in joomla.
  *
  * @param   INT  $gid  Gourp Id.
  *
  * @return  mixed  Return Gourp id.
  *
  * @since   1.0
  */
	/*public function checkGroupExist($gid)
	{
		static $allGroups;

		if (!isset($allGroups))
		{
			$db = Factory::getDbo();

			$query = $db->getQuery(true);
			$query->select('id');
			$query->from('`#__usergroups`');
			$db->setQuery($query);

			$allGroups = $db->loadColumn();
		}

		if (in_array((int) $gid, $allGroups))
		{
			return $gid;
		}
		else
		{
			Log::add(Text::sprintf("COM_TJLMS_MANAGEENROLLMENTS_LOG_INVALID_USER_GROUP", $gid), Log::WARNING, 'com_tjlms');

			return false;
		}
	}*/

	/**
	 * Check if valid date.
	 *
	 * @param   Date  $data  date.
	 *
	 * @return  boolean
	 *
	 * @since   1.0
	 */
	public function checkDateTime($data)
	{
		if (date('Y-m-d', strtotime($data)) == $data)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Returns userid if a user exists
	 *
	 * @param   string  $email  The email to search on.
	 *
	 * @return  integer  The user id or 0 if not found.
	 *
	 * @since   11.1
	 */
	private function getUserId($email)
	{
		// Initialise some variables
		$db = Factory::getDbo();
		$query = $db->getQuery(true)
			->select($db->quoteName('id'))
			->from($db->quoteName('#__users'))
			->where($db->quoteName('email') . ' = ' . $db->quote($email));
		$db->setQuery($query, 0, 1);

		return $db->loadResult();
	}

	/**
	 * Check field exist in lms.
	 *
	 * @param   string  $fieldName  The field name.
	 *
	 * @param   string  $context  The context name.
	 *
	 * @return  integer  The field id or 0 if not found.
	 *
	 * @since   1.5.0
	 */
	public function checkFieldExist($fieldName, $context)
	{
		$checkedfield = 0;

		if (!empty($fieldName))
		{
			$db = JFactory::getDbo();

			$query = $db->getQuery(true);
			$query->select('id');
			$query->from('`#__fields`');
			$query->where('name = "' . (string) $fieldName . '"');
			$query->where('context = "' . (string) $context . '"');

			$db->setQuery($query);

			$checkedfield = $db->loadAssoc();
		}

		return $checkedfield;
	}

	/**
	 * Update User group of User.
	 *
	 * @param   INT    $userId    User Id of User
	 * @param   ARRAY  $eachUser  user array
	 *
	 * @return  boolean
	 *
	 * @since   1.0
	 */
	/*public function updateUserGroups($userId, $eachUser)
	{
		$addGroups = empty($eachUser["addgroup"]) ? 0 : $eachUser["addgroup"];
		$removeGroups = empty($eachUser["removegroup"]) ? 0 : $eachUser["removegroup"];

		if ($userId)
		{
			$user               = new User($userId);
			$userData           = array();
			$userData['id']     = $userId;
			$userData['groups'] = $user->groups;

			if ($removeGroups)
			{
				$removeGroups = explode("|", $removeGroups);

				foreach ($removeGroups as $key => $removeGroup)
				{
					$validGroup = $this->checkGroupExist($removeGroup);

					if ($validGroup)
					{
						$arrayIndex = array_search($validGroup, $userData['groups']);

						if ($arrayIndex !== false)
						{
						Log::add(Text::sprintf("COM_TJLMS_MANAGEENROLLMENTS_LOG_REMOVED_USER_GROUP", $userId, $removeGroup), Log::WARNING, 'com_tjlms');
						unset($userData['groups'][$arrayIndex]);
						}
					}
				}
			}

			if ($addGroups)
			{
				$addGroups = explode("|", $addGroups);

				foreach ($addGroups as $key => $addGroup)
				{
					$validGroup = $this->checkGroupExist($addGroup);

					if ($validGroup)
					{
						$msg = Text::sprintf("COM_TJLMS_MANAGEENROLLMENTS_LOG_USER_ADDED_TO_GROUP", $userId, $validGroup);
						Log::add($msg, Log::INFO, 'com_tjlms');
						array_push($userData['groups'], $validGroup);
					}
				}
			}

			if ($user->bind($userData))
			{
				if ($user->save())
				{
					return true;
				}

				return false;
			}
		}

		return false;
	}*/
}
