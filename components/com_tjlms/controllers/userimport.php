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
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Component\ComponentHelper;
define('REGISTER_USER_ID', 2);
jimport('joomla.filesystem.folder');
jimport('joomla.html.html');
jimport('joomla.application.component.model');
/**
 * File upload controller class.
 *
 * @since  1.0.0
 */
class TjlmsControllerUserimport extends FormController
{
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

		$oluser_id = Factory::getUser()->id;

		/* If user is not logged in*/
		if (!$oluser_id)
		{
			$ret['OUTPUT']['flag']	=	0;
			$ret['OUTPUT']['msg']	=	Text::_('COM_TJLMS_MUST_LOGIN_TO_UPLOAD');
			echo json_encode($ret);
			jexit();
		}

		$input = Factory::getApplication()->input;
		$tjlmsparams = ComponentHelper::getParams('com_tjlms');

		$files = $input->files;
		$post = $input->post;

		$file_to_upload	=	$files->get('FileInput', '', 'ARRAY');
		$filepath = 'media/com_tjlms/userimport/';

		$return = 1;
		$msg = '';

		$file_attached	= $file_to_upload['tmp_name'];
		$filename = $file_to_upload['name'];
		$filepath_with_file = $filepath . $filename;
		$newfilename = $filename;

			/* Save csv content to question table */
		$result = $this->saveCsvContent($file_to_upload);

		$filename = $file_to_upload['name'];

		/*$return = $result['return'];
		$msg = $result['msg'];
		$msg1 = $result['msg1'];*/

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
	 * @since  1.0.0
	 */
	public function saveCsvContent($file_to_upload)
	{
		$least_data = 0;
		$handle    = fopen($file_to_upload['tmp_name'], 'r');
		$rowNum = '';

		while (($data = fgetcsv($handle)) !== false)
		{
			if ($rowNum == 0)
			{
				// Parsing the CSV header
				$headers = array();

				foreach ($data as $d)
				{
					$headers[] = preg_replace('/[^A-Za-z0-9\-]/', '', $d);
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

				if (count($headers) == count($rowData))
				{
					$userData[] = array_combine($headers, $rowData);
				}
			}

			$rowNum++;
		}

		$valueArray = array ('FirstName','LastName','username','email');

		foreach ($headers as $key => $value)
		{
			if (!in_array($value, $valueArray))
			{
				if (strpos($value, 'course') || strpos($value, 'groupId'))
				{
					$output['return'] = 1;
					$output['errormsg'] = Text::sprintf('COM_TJLMS_TITLE_MANAGEENROLLMENTS_IMPORT_ERROR_MSG', $value, '');
					$output['successmsg'] = '';

					return $output;
				}
			}
		}

		$acceptedQues = 0;

		$db = Factory::getDbo();

		/*$bad = $notinsert = $new = $success = $newbad = $bad_user = $no_users = $bad_course = 0;*/

		$alreadyEnrolledCnt = $bad_group = $bad_user = $bad_course = $new_user = $enrol_success = $col_value = 0;

		$miss_col = 0;
		$emptyfile = 0;

		if (!empty($userData))
		{
			$totalUser = count($userData);

			if ($totalUser < 1)
			{
				$emptyfile = 1;
			}
			else
			{
				foreach ($userData as $eachUser)
				{
					if (!empty($eachUser))
					{
						$lname = '';
						$username = "";

						if (trim($eachUser['FirstName']) == '' || trim($eachUser['email']) == '')
						{
							$col_value++;
						}
						else
						{
							foreach ($eachUser as $key => $value)
							{
								if (!array_key_exists('FirstName', $eachUser) || !array_key_exists('LastName', $eachUser) || !array_key_exists('email', $eachUser))
								{
									$miss_col = 1;
									break;
								}

								if (!empty($value))
								{
									if ($key == 'FirstName')
									{
										$fname = $value;
									}

									if ($key == 'LastName')
									{
										$lname = $value;
									}

									if ($key == 'username')
									{
										$username = $value;
									}

									if ($key == 'email')
									{
										$userexist = $this->checkUserExit($value);

										if (!$userexist)
										{
											/*$no_users ++;*/
											$userid = $this->createUser($value, $fname, $lname, $username);

											if ($userid > 0)
											{
												$new_user ++;
											}
											else
											{
												// User already present or invalid user id
												$bad_user ++;
											}
										}
										else
										{
											// If old user check alredy entry created for enrollment for same user same course.
											$userid = $userexist;
										}
									}

									if ($key != 'Id' && $key != 'email' && $key != 'FirstName' && $key != 'LastName' && $key != 'username' && strpos($key, 'ourse'))
									{
										if (is_numeric($value))
										{
											$CourseExit = $this->checkCourseExit($value);

											if ($userid > 0 && $CourseExit['id'] > 0 && $CourseExit['state'] == 1)
											{
												$alreadyEnrolled = $this->checkIfuserEnrolled($userid, $value);

												if ($alreadyEnrolled == 0 && $value != 0)
												{
													$obj['id'] = '';
													$obj['course_id'] = $value;
													$obj['cid'] = array($userid);
													$obj['csv'] = 1;

													// Call enrollment model for the createOrder()
													$model = $this->getModel('enrolment');
													$successfulEnroled = $model->enroll_user($obj, 1);
													$enrol_success ++;
												}
												else
												{
													$alreadyEnrolledCnt++;
												}
											}
											else
											{
												// Invalid or unpublished course id
												$bad_course ++;
											}
										}
										else
										{
											// Invalid or unpublished course id
											$bad_course  ++;
										}
									}

									elseif ($key != 'Id' && $key != 'email' && $key != 'FirstName' && $key != 'LastName' && $key != 'username' && strpos($key, 'roupId'))
									{
										// Group Mapp
										if (is_numeric($value))
										{
											$GroupExit = $this->checkGroupExit($value);

											if ($GroupExit)
											{
												require_once JPATH_ADMINISTRATOR . '/components/com_users/models/user.php';
												$userModel            = new UsersModelUser;
												$userData             = array();
												$userData['id']    = $userid;
												$userData['groups']    = array($value);
												$userData['block']    = 0;

												if ($userModel->save($userData))
												{
													$success ++;
												}
											}
											else
											{
												$bad = 1;
											}
										}
										else
										{
											$bad = 1;
										}
									}
									else
									{
										// Not yet
									}
								}
							}
						}
					}
				}
			}
		}

		/*if ($bad == 1)
		{
			$newbad ++;
		}*/

		$output['return'] = 1;
		$output['successmsg'] = '';
		$output['errormsg'] = '';

		if (empty($userData))
		{
			$output['errormsg'] = Text::sprintf('COM_TJLMS_TITLE_MANAGEENROLLMENTS_IMPORT_BLANK_FILE');
		}
		else
		{
			if ($emptyfile == 1)
			{
				$output['successmsg'] = "";
				$output['errormsg'] .= Text::_('COM_TJLMS_CSV_IMPORT_EMPTY_FILE');
			}
			else
			{
				if ($miss_col)
				{
					$output['successmsg'] = "";
					$output['errormsg'] = Text::_('COM_TJLMS_CSV_IMPORT_COLUMN_MISSING');
				}
				else
				{
					$output['successmsg'] = Text::sprintf('COM_TJLMS_MANAGEENROLLMENTS_IMPORT_TOTAL_ROWS_CNT_MSG', count($userData));

					if ($new_user > 0)
					{
						if ($new_user == 1)
						{
							$output['successmsg'] .= "<br />" . Text::sprintf('COM_TJLMS_TITLE_MANAGEENROLLMENTS_IMPORT_NEW_USER_MSG', $new_user);
						}
						else
						{
							$output['successmsg'] .= "<br />" . Text::sprintf('COM_TJLMS_TITLE_MANAGEENROLLMENTS_IMPORT_NEW_USERS_MSG', $new_user);
						}
					}

					if ($enrol_success > 0)
					{
						if ($enrol_success == 1)
						{
							$output['successmsg'] .= "<br />" . Text::sprintf('COM_TJLMS_TITLE_MANAGEENROLLMENTS_IMPORT_NEWLY_SINGLE_USER_ENROLLED_MSG', $enrol_success);
						}
						else
						{
							$output['successmsg'] .= "<br />" . Text::sprintf('COM_TJLMS_TITLE_MANAGEENROLLMENTS_IMPORT_NEWLY_ENROLLED_MSG', $enrol_success);
						}
					}

					if ($alreadyEnrolledCnt > 0)
					{
						$output['errormsg'] .= "<br />" . Text::sprintf('COM_TJLMS_TITLE_MANAGEENROLLMENTS_IMPORT_ALREADY_ENROLLED_MSG', $alreadyEnrolledCnt);
					}

					if ($bad_user > 0)
					{
						$output['errormsg'] .= "<br />" . Text::sprintf('COM_TJLMS_MANAGEENROLLMENTS_BAD_USERDATA', $bad_user);
					}

					if ($bad_course > 0)
					{
						$output['errormsg'] .= "<br />";
						$output['errormsg'] .= Text::sprintf('COM_TJLMS_MANAGEENROLLMENTS_BAD_COURSE', $bad_course);
					}

					if ($col_value > 0)
					{
						$output['errormsg'] .= "<br />";
						$output['errormsg'] .= Text::sprintf('COM_TJLMS_MANAGEENROLLMENTS_MANDATORY_FIELDS', $col_value);
					}
				}
			}
		}

		return $output;
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
			$query->select('id');
			$query->from('`#__users`');
			$query->where('email = ' . $db->Quote($useremail));
			$db->setQuery($query);

			return $userexist = $db->loadResult();
		}
	}

	/**
	 * Check course exist in lms.
	 *
	 * @param   INT  $courseId  login user email.
	 *
	 * @return  int  Return course id.
	 *
	 * @since   1.0
	 */
	public function checkCourseExit($courseId)
	{
		if ($courseId)
		{
			$db = Factory::getDbo();

			// Check the customer id (in users table) already exist or not
			$query = $db->getQuery(true);
			$query->select('id,state');
			$query->from('`#__tjlms_courses`');
			$query->where('id = ' . ($courseId));
			$db->setQuery($query);

			return $Courseexist = $db->loadAssoc();
		}
	}

	/**
	 * Create joomla user.
	 *
	 * @param   RAW     $useremail  A record object formfield.
	 * @param   STRING  $fname      A record object formfield.
	 * @param   STRING  $lname      A record object formfield.
	 * @param   STRING  $username   A record object formfield.
	 *
	 * @return  int  Return user id.
	 *
	 * @since   1.0
	 */
	public function createUser($useremail,$fname,$lname,$username = "")
	{
			$db = Factory::getDbo();

			// Create a new user
			require_once JPATH_ADMINISTRATOR . '/components/com_users/models/user.php';
			$userModel            = new UsersModelUser;
			$userData             = array();
			$userData['name']     = $fname . ' ' . $lname;

			if ($username)
			{
				$userData['username'] = $username;
			}
			else
			{
				$userData['username']    = trim($useremail);
			}

			$userData['email']    = trim($useremail);
			$userData['block']    = 0;

			if ($userModel->save($userData))
			{
				$userid = (int) $userModel->getState('user.id');
			}

			if (isset($userid))
			{
				if ($userid)
				{
					$userGroupData             = new stdClass;
					$userGroupData->user_id    = $userid;
					$userGroupData->group_id   = REGISTER_USER_ID;

					$db->insertObject('#__user_usergroup_map', $userGroupData, 'user_id');

					return $userid;
				}
			}
	}

	/**
	 * For user enroll data
	 *
	 * @param   Int  $userId    User id.
	 * @param   Int  $courseId  course id.
	 *
	 * @return  array  Return User Enroll Data.
	 *
	 * @since   1.0
	 */

	public function checkIfuserEnrolled($userId,$courseId)
	{
		if ($userId && $courseId)
		{
			$db = Factory::getDbo();

			// Check the customer id (in users table) already exist or not
			$query = $db->getQuery(true);
			$query->select('id');
			$query->from('`#__tjlms_enrolled_users`');
			$query->where('user_id = ' . ($userId));
			$query->where('course_id = ' . ($courseId));
			$db->setQuery($query);

			return $userEnrollData = $db->loadResult();
		}
	}

	/**
	 * Check user exist in joomla.
	 *
	 * @param   INT  $gid  Gourp Id.
	 *
	 * @return  int  Return user id.
	 *
	 * @since   1.0
	 */
	public function checkGroupExit($gid)
	{
		if ($gid)
		{
			$db = Factory::getDbo();

			// Check the customer id (in users table) already exist or not
			$query = $db->getQuery(true);
			$query->select('id');
			$query->from('`#__usergroups`');
			$query->where('id = ' . $db->Quote($gid));
			$db->setQuery($query);

			return $userexist = $db->loadResult();
		}
	}
}
