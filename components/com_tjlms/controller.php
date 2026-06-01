<?php

/**
 * @package    Shika
 * @author     TechJoomla | <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 -2010 Techjoomla, Tekdi Web Solutions . All rights reserved.
 * @license    GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link       http://www.techjoomla.com
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Component\ComponentHelper;

jimport('joomla.application.component.controller');

if (!defined('DS'))
{
	define('DS', DIRECTORY_SEPARATOR);
}

/**
 * Controller for LMS
 *
 * @since  1.0
 */

class TjlmsController extends BaseController
{
	/**
	 * Method to display a view.
	 *
	 * @param   boolean  $cachable   If true, the view output will be cached
	 * @param   array    $urlparams  An array of safe url parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
	 *
	 * @return  JController  This object to support chaining.
	 *
	 * @since   1.0
	 */
	public function display($cachable = false, $urlparams = false)
	{
		parent::display();
	}

	/**
	 * method to execute cron job
	 *
	 * @return void
	 *
	 * @since  1.0
	 *
	 **/
	public function cron()
	{
		$params              = ComponentHelper::getParams('com_tjlms');
		$private_key_cronjob = $params->get('private_key_storage_cron');

		$input            = Factory::getApplication()->input;
		$private_keyinurl = $input->get('pkey', '', 'STRING');

		if ($private_key_cronjob != $private_keyinurl)
		{
			echo Text::_("COM_TJLMS_NOT_AUTHORISED_STORAGE_CRON");

			return;
		}

		require_once JPATH_COMPONENT . '/libraries/cron.php';
		$cron = new TjCron;
		$cron->execute();
	}

	/**
	 * This function generates pdf
	 *
	 * @return void
	 *
	 * @since  1.0
	 *
	 */
	public function GeneratePDF()
	{
		$app   = Factory::getApplication();
		
		require_once JPATH_SITE . DS . 'components' . DS . 'com_tjlms' . DS . 'helpers' . DS . 'main.php';
		$c_id = $app->input->get('c_id');
		$user_id = $app->input->get('user_id');
		comtjlmsHelper::generatepdf($c_id, $user_id);
	}

	/**
	 * This function calls respective task on respective plugin
	 *
	 * @return json output for plugin trigger
	 *
	 * @since  1.0
	 *
	 */
	public function callSysPlgin()
	{
		$input   = Factory::getApplication()->input;
		$plgType = $input->get("plgType", "", "STRING");
		$plgName = $input->get("plgName", "", "STRING");
		$plgtask = $input->get("plgtask", "", "STRING");

		// Called from Ajax(0) or URL (1)
		$callType = $input->get("callType", 0);

		// START Q2C Sample development
		PluginHelper::importPlugin($plgType, $plgName);

		// Call the plugin and get the
		$result = Factory::getApplication()->triggerEvent('on' . $plgtask);

		// Result
		$ontrigger = '';

		if (!empty($result))
		{
			$ontrigger = $result[0];
		}

		if (empty($callType))
		{
			header('Content-type: application/json');
			echo json_encode($ontrigger);
			jexit();
		}
		else
		{
			echo $ontrigger;
			jexit();
		}
	}

	/**
	 * Function used to check if subscription expired (hit from CRON).
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	public function expiredsubscron()
	{
		$params              = ComponentHelper::getParams('com_tjlms');
		$private_key_cronjob = $params->get('private_key_storage_cron');
		$daysBefore          = $params->get('before_subscription_expired');

		$input            = Factory::getApplication()->input;
		$private_keyinurl = $input->get('pkey', '', 'STRING');

		if ($private_key_cronjob != $private_keyinurl)
		{
			echo Text::_("COM_TJLMS_NOT_AUTHORISED_STORAGE_CRON");

			return;
		}

		$db    = Factory::getDBO();
		$query = $db->getQuery(true);

		$query->select('eu.*');
		$query->from('#__tjlms_enrolled_users as eu');
		$query->where('state=1 AND end_time <> "0000-00-00 00:00:00" ');
		$query->where('unlimited_plan = 0');
		$db->setQuery($query);
		$rows = $db->loadObjectList();

		if (!empty($rows))
		{
			require_once JPATH_SITE . "/components/com_tjlms/models/enrolment.php";
			$tjlmsModelEnrolment = new TjlmsModelEnrolment;

			foreach ($rows as $row)
			{
				$end_time = strtotime($row->end_time);
				$today    = Factory::getDate();
				$curdate  = strtotime($today);

				// About to expire code
				$aboutToExpireDate = strtotime('-' . $daysBefore . 'day', $end_time);
				$colArray = array('before_expiry_mail', 'after_expiry_mail');
				$mailFlag = $tjlmsModelEnrolment->getEnrolledUserColumn($row->course_id, $row->user_id, $colArray);

				if ($curdate > $end_time && $mailFlag->after_expiry_mail != 1)
				{
					$query = $db->getQuery(true);

					// Fields to update.
					$fields = array(
						$db->quoteName('state') . ' = -2'
					);

					// Conditions for which records should be updated.
					$conditions = array(
						$db->quoteName('id') . ' = ' . $row->id
					);

					$query->update($db->quoteName('#__tjlms_enrolled_users'))->set($fields)->where($conditions);
					$db->setQuery($query);
					$result = $db->execute();

					PluginHelper::importPlugin('system');
					Factory::getApplication()->triggerEvent('onAfterSubscriptionExpired', array($row->user_id, $row->course_id));
				}
				elseif ($aboutToExpireDate < $curdate && $mailFlag->before_expiry_mail != 1)
				{
					PluginHelper::importPlugin('system');
					Factory::getApplication()->triggerEvent('onBeforeSubscriptionExpired', array($row->user_id, $row->course_id));
				}
			}
		}
	}

	/**
	 * Clean result set
	 *
	 * @return  ture
	 *
	 * @since  1.0.0
	 */
	public function cleanResultSet()
	{
		$app   = Factory::getApplication();
		$input = Factory::getApplication()->input;
		$start = $input->get('start', '0', 'INT');
		$limit = $input->get('limit', '100', 'INT');

		$db    = Factory::getDBO();
		$query = $db->getQuery(true);

		/* Get all attempt(as per limit applied) for the lesson which is a quiz.*/
		$query->select($db->quoteName(array('lt.id', 'lt.attempt', 'lt.lesson_id', 'lt.user_id', 'tmt.test_id', 'lt.score')));
		$query->from($db->quoteName('#__tjlms_lesson_track') . ' as lt');
		$query->join('INNER', $db->quoteName('#__tjlms_lessons') . ' as l ON l.id=lt.lesson_id');
		$query->join('LEFT', $db->quoteName('#__tjlms_tmtquiz') . ' as tmt ON tmt.lesson_id=l.id');
		$query->where('l.format=' . $db->quote('tmtQuiz'));
		$query->where('lt.lesson_status=' . $db->quote('started'));
		$query->where('DATE(last_accessed_on) < CURDATE()');
		$query->order('lt.id ASC');
		$query->setLimit($limit, $start);

		$db->setQuery($query);
		$allQuizesAttempts = $db->loadObjectList();

		foreach ($allQuizesAttempts as $eachQuizAttempt)
		{
			// Get all answer which are given by the users
			$query = $db->getQuery(true);
			$query->select($db->quoteName(array('ans.id', 'ans.question_id', 'ans.invite_id', 'ans.answer')));
			$query->from($db->quoteName('#__tmt_tests_answers') . ' as ans');
			$query->where('ans.invite_id=' . $eachQuizAttempt->id);
			$db->setQuery($query);
			$allAnswerForInviteId = $db->loadObjectList();

			if (!empty($allAnswerForInviteId))
			{
				$getCorrectedMarks = $this->getCorrectedMarks($allAnswerForInviteId, $eachQuizAttempt->test_id);

				if ($getCorrectedMarks != -1)
				{
					$updateMarksStatus = $this->updateMarks($getCorrectedMarks, $eachQuizAttempt->test_id, $eachQuizAttempt->id, $eachQuizAttempt->score);
				}

				if (isset($updateMarksStatus) && $updateMarksStatus == 1)
				{
					$user_id = $eachQuizAttempt->user_id;
					$lesson_id = $eachQuizAttempt->lesson_id;
					$oldScore = $eachQuizAttempt->score;
					$finalMarks = $getCorrectedMarks['finalMarks'];

					$app->enqueueMessage(Text::sprintf(Text::_('COM_TJLMS_MARKS_UPDATED'), $user_id, $lesson_id, $oldScore, $finalMarks));
				}
			}
		}

		return true;
	}

	/**
	 * Update marks for incorrects entires
	 *
	 * @param   int  $correctedMarks  id of invite
	 * @param   int  $testId          id of invite
	 * @param   int  $lessonTrackId   id of invite
	 * @param   int  $oldMarks        id of invite
	 *
	 * @return  boolean
	 *
	 * @since  1.0.0
	 */
	public function updateMarks($correctedMarks, $testId, $lessonTrackId, $oldMarks)
	{
		if ($correctedMarks['finalMarks'] != $oldMarks || $correctedMarks['status'] != 'started')
		{
			$app = Factory::getApplication();

			// Create an object for the record we are going to update.
			$object = new stdClass;

			// Must be a valid primary key value.
			$object->id = $lessonTrackId;
			$object->score = $correctedMarks['finalMarks'];
			$object->lesson_status = $correctedMarks['status'];

			// Update their details in the users table using id as the primary key.
			$resultForLessonTrack = Factory::getDbo()->updateObject('#__tjlms_lesson_track', $object, 'id');

			// Create an object for the record we are going to update.
			$object = new stdClass;

			// Must be a valid primary key value.
			$object->invite_id = $lessonTrackId;
			$object->id = $testId;
			$object->score = $correctedMarks['finalMarks'];

			// Update their details in the users table using id as the primary key.
			$resultForTestAttendees = Factory::getDbo()->updateObject('#__tmt_tests_attendees', $object, 'invite_id');

			return 1;
		}

		return -1;
	}

	/**
	 * Calculate the marks as per answers given
	 *
	 * @param   ARRAY  $allAnswerForInviteId  All attempt data
	 * @param   INT    $test_id               Test ID
	 *
	 * @return INT
	 *
	 * @since  1.0.0
	 */
	public function getCorrectedMarks($allAnswerForInviteId, $test_id)
	{
		$db = Factory::getDBO();

		$isObjective = TMT::Test($test_id)->isObjective;

		if ($isObjective)
		{
			$finalMarks = 0;

			foreach ($allAnswerForInviteId as $eachAnswer)
			{
				$answerId = json_decode($eachAnswer->answer);

				// Get corrected marks
				$query = $db->getQuery(true);
				$query->select($db->quoteName(array('ans.marks')));
				$query->from($db->quoteName('#__tmt_answers') . ' as ans');
				$query->where('ans.id=' . $answerId[0]);
				$query->where('ans.is_correct = 1');
				$db->setQuery($query);
				$marks = $db->loadresult();

				$finalMarks = $finalMarks + $marks;
			}

			$query = $db->getQuery(true);
			$query->select($db->quoteName(array('test.passing_marks')));
			$query->from($db->quoteName('#__tmt_tests') . ' as test');
			$query->where('test.id=' . $test_id);
			$db->setQuery($query);
			$passingMarks = $db->loadresult();

			$resultstatus = 'failed';

			if ($passingMarks <= $finalMarks)
			{
				$resultstatus = 'passed';
			}

			$result['status'] = $resultstatus;
			$result['finalMarks'] = $finalMarks;

			return $result;
		}
		else
		{
			return -1;
		}
	}

	/**
	 * Delete all duplicate entries from test_answers table
	 *
	 * @param   ARRAY  $duplicateAnswers  Duplicate answers entry
	 *
	 * @return boolean
	 *
	 * @since  1.0.0
	 */
	public function deletDuplicateAnswer($duplicateAnswers)
	{
		$db    = Factory::getDbo();
		$app   = Factory::getApplication();
		$query = $db->getQuery(true);

		// Delete all custom keys for user 1001.
		$conditions = array(
			$db->quoteName('id') . ' IN ( ' . $duplicateAnswers . ')'
		);

		$query->delete($db->quoteName('#__tmt_tests_answers'));
		$query->where($conditions);
		$db->setQuery($query);

		$result = $db->execute();

		if ($result)
		{
			$app->enqueueMessage(Text::sprintf(Text::_('COM_TJLMS_DELETED_ENTRIES'), $duplicateAnswers), 'success');
		}

		return $result;
	}

	/**
	 * Send Reminders to Users before due date
	 *
	 * @param   INT  $reminder_batch_size  reminders batch size
	 *
	 * @return nothing
	 */
	public function sendReminders($reminder_batch_size = 1)
	{
		require_once JPATH_ADMINISTRATOR . '/components/com_tjlms/models/reminder.php';
		$reminder_count = 0;
		$model = $this->getModel('reminder');
		$this->tjlmshelperObj = new comtjlmsHelper;

		// Get All Unique courses from reminders_xref
		$getAllCourses = $model->getAllCourses();

		// Send reminder for each asssigned User
		foreach ($getAllCourses as $course_id)
		{
			$getDuedate = $model->getCoursesDuedate($course_id);

			if (isset($getDuedate))
			{
				$getreminderdays = $model->getAllReminders($course_id);

				foreach ($getreminderdays as $reminder)
				{
					if (isset($reminder->days))
					{
						$reminder_date = strtotime('-' . $reminder->days . 'day', strtotime($getDuedate));
						$reminder_date = date('Y-m-d ', $reminder_date);

						$reminder_date = strtotime($reminder_date);
						$current_date    = strtotime(date("Y-m-d"));

						if ($reminder_date == $current_date)
						{
							// Site Details to send mail
							$app                  = Factory::getApplication();
							$mailfrom             = $app->getCfg('mailfrom');
							$fromname             = $app->getCfg('fromname');
							$sitename             = $app->getCfg('sitename');

							$getallusers = $model->getAllUsers($course_id, $reminder_batch_size);

							foreach ($getallusers as $id)
							{
								$db = Factory::getDbo();
								Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjlms/tables');

								// First parameter file name and second parameter is prefix
								$table = Table::getInstance('Todosreminder', 'TjlmsTable', array('dbo', $db));

								// Check if already reminder send to the User
								$table->load(array('course_id' => (int) $course_id, 'reminder_id' => (int) $reminder->id, 'user_id' => (int) $id));

								if (!$table->id)
								{
									// Create JUser Object
									$user = Factory::getUser($id);
									$comtjlmsHelper = new comtjlmsHelper;
									$tjlmsCoursesHelper = new TjlmsCoursesHelper;

									// Store All Details in the array to replace the email template tags
									$this->course_enroll_mail = array();

									// To change the date format create call LMS function
									$this->techjoomlacommon = new TechjoomlaCommon;
									$lmsparams   = ComponentHelper::getParams('com_tjlms');
									$date_format_show = $lmsparams->get('date_format_show', 'Y-m-d H:i:s');

									$this->course_enroll_mail['courses'] = $tjlmsCoursesHelper->getcourseInfo($course_id);

										if (!empty($getDuedate))
										{
											if ($getDuedate != '-')
											{
												$getDuedate = $this->techjoomlacommon->getDateInLocal($getDuedate, 0, $date_format_show);
											}

											if ($getDuedate == '0000-00-00 00:00:00')
											{
												$getDuedate = '-';
											}
										}

										$this->course_enroll_mail['courses']->due_date = $getDuedate;
										$this->course_enroll_mail['course_creator'] = $comtjlmsHelper->getCourseCreatorDetails($course_id);

										$this->course_enroll_mail['enrollment'] = new stdClass;

										if (isset($id))
										{
											$this->course_enroll_mail['enrollment']->id = $id;
										}

										if (!empty($user->email))
										{
											$this->course_enroll_mail['enrollment']->email = $user->email;
										}

										if (!empty($user->username))
										{
											$this->course_enroll_mail['enrollment']->username = $user->username;
										}

										if (!empty($user->name))
										{
											$this->course_enroll_mail['enrollment']->name = $user->name;
										}

									// ACTOR MAIL SUBJECT
									if (isset($reminder->subject))
									{
										$actor_mail_subject = $reminder->subject;
										$actor_mail_subject = TjMail::TagReplace($actor_mail_subject, $this->course_enroll_mail);
									}

									// Get itemidof single courses view
									$courseItemid = $this->tjlmshelperObj->getItemId('index.php?option=com_tjlms&view=course&id=' . $course_id);

									if (empty($courseItemid))
									{
										// Get itemidof all courses view
										$courseItemid = $this->tjlmshelperObj->getItemId('index.php?option=com_tjlms&view=courses&layout=all');
									}

									$itemid_str = '';

									if (!empty($courseItemid))
									{
										$itemid_str = "&Itemid=" . $courseItemid;
									}

									// ACTOR MAIL BODY TO ADD COURSE LINK
									if (Factory::getApplication()->isSite())
									{
										$courseUrl = $comtjlmsHelper->tjlmsRoute('index.php?option=com_tjlms&view=course&id=' . $course_id . $itemid_str, false, -1);
									}
									else
									{
										$courseUrl = $comtjlmsHelper->tjlmsRoute(Uri::root() . 'index.php?option=com_tjlms&view=course&id=' . $course_id . $itemid_str, false, -1);
									}

									$this->course_enroll_mail['course_link'] = '<a href="' . $courseUrl . '">' . $this->course_enroll_mail['courses']->title . '</a>';

									$body_for_actor = $reminder->email_template;
									$actor_mail_body = TjMail::TagReplace($body_for_actor, $this->course_enroll_mail);

									// Send mail to actor
									if ($comtjlmsHelper->sendmail($user->email, $actor_mail_subject, $actor_mail_body, '', 0, ''))
									{
										// Add new Entry in the todos_reminder table
										$table->course_id   = $course_id;
										$table->user_id     = $id;
										$table->reminder_id = $reminder->id;
										$table->store();
										$reminder_count++;
									}
								}
							}
						}
					}
				}
			}
		}

		return $reminder_count;
	}

	/**
	 * Migration script to update the used_count and store it in the counpons table
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	public function migrationOfCouponsUsedcount()
	{
		// Load tjlms coupons model to get all coupons
		require_once JPATH_ADMINISTRATOR . '/components/com_tjlms/models/coupons.php';

		// Call the actual cron code which will send the reminders
		$model         = BaseDatabaseModel::getInstance('Coupons', 'TjlmsModel');
		$coupons     = $model->getItems();

		foreach ($coupons as $coupon)
		{
				$model->updateCouponUsedcount($coupon->code);
		}

		echo Text::sprintf('COM_TJLMS_COUPONS_USED_COUNT', count($coupons));
	}

	/**
	 * This function calls respective task on respective plugin
	 *
	 * @return json output for plugin trigger
	 *
	 * @since  1.0
	 *
	 */
	public function listener()
	{
		$input = Factory::getApplication()->input;
		$type_str = $input->get("type", "", "STRING");

		if ($type_str)
		{
			$type_array = explode('.', $type_str);
			$plgType = $type_array[0];
			$plgName = $type_array[1];

			PluginHelper::importPlugin($plgType, $plgName);

			// Call the plugin and get the
			$result = Factory::getApplication()->triggerEvent($plgName . 'listener');
		}
	}

	/**
	 * This function is to add entry in the tjlms_certificate for the already completed courses by users
	 *
	 * @return message
	 *
	 * @since  1.0
	 *
	 */
	public function migrationCertificateUniqueId()
	{
		$cnt = 0;

		// Get All records which are there in cetificate table
		$db      = Factory::getDBO();
		$ltquery = $db->getQuery(true);
		$ltquery->select('*');
		$ltquery->from($db->quoteName('#__tjlms_certificate') . 'as c');
		$ltquery->where('ct.user_id = c.user_id');
		$ltquery->where('ct.course_id = c.course_id');

		$query = $db->getQuery(true);
		$query->select('ct.*');
		$query->from($db->quoteName('#__tjlms_course_track') . 'as ct');
		$query->join('INNER', $db->quoteName('#__tjlms_courses') . ' as co ON co.id=ct.course_id');
		$query->where($db->quoteName('co.certificate_term') . ' IN(1,2)');
		$query->where($db->quoteName('ct.status') . ' = "C"');
		$query->where('not EXISTS (' . $ltquery . ')');
		$query->order('ct.id ASC');
		$db->setQuery($query);
		$users_cert = $db->loadObjectList();

		require_once JPATH_SITE . '/components/com_tjlms/models/course.php';

		// Load course tjlms model
		$model = BaseDatabaseModel::getInstance('course', 'TjlmsModel');

		foreach ($users_cert as $user_record)
		{
			$success = $model->addCertEntry($user_record->course_id, $user_record->user_id);

			if ($success)
			{
				$cnt++;
			}
		}

		if ($cnt)
		{
				echo Text::sprintf('COM_TJLMS_TOTAL_USERS_COURSE_CETIFICATE_ID_UPDATED', $cnt);
		}
		else
		{
			echo Text::sprintf('COM_TJLMS_TOTAL_USERS_COURSE_CETIFICATE_ID_NOT_UPDATED', $cnt);
		}
	}
}
