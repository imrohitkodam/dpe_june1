<?php
/**
 * @package     TMT
 * @subpackage  com_shika
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\Registry\Registry;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
jimport('techjoomla.tjnotifications.tjnotifications');

/**
 * Class to send out emails from tmt
 *
 * @since       1.0
 *
 * @deprecated  1.4.0  This class will be removed and some replacements will be provided in email library
 */
class TmtEmailsHelper
{
	public $sitename;

	public $global;

	public $options;

	/**
	 * Method acts as a consturctor
	 *
	 * @since   1.0.0
	 */
	public function __construct ()
	{
		$app = Factory::getApplication();
		$this->sitename = $app->getCfg('sitename');

		$this->global = new stdClass;
		$this->global->sitename = $this->sitename;
		$this->options = new Registry;
	}

	/**
	 * Send email
	 *
	 * @param   string  $recipient   email of recipient
	 * @param   string  $subject     subject of email
	 * @param   string  $body        body of email
	 * @param   string  $bcc_string  bcc emails which are comma separated
	 *
	 * @return  void
	 *
	 * @since  1.0
	 */
	public function sendmail($recipient, $subject, $body, $bcc_string)
	{
		// Echo $recipient."<br>".$subject."<br>".$body."<br>".$bcc_string ; die;
		global $mainframe;
		$mainframe = Factory::getApplication();
		$from = $mainframe->getCfg('mailfrom');
		$fromname = $mainframe->getCfg('fromname');
		$recipient = trim($recipient);
		$mode = 1;
		$cc = null;
		$bcc = null;

		if ($bcc_string)
		{
			$bcc = explode(',', $bcc_string);
		}

		$attachment = null;
		$replyto = null;
		$replytoname = null;

		Factory::getMailer()->sendMail($from, $fromname, $recipient, $subject, $body, $mode, $cc, $bcc, $attachment, $replyto, $replytoname);
	}

	/**
	 * Send test assigned to all related reviewers.
	 *
	 * @param   int  $testId  id of the test
	 *
	 * @return  void
	 *
	 * @since  1.0
	 */
	public function sendTestAssignedForReviewEmail($testId)
	{
		$test = self::getTestDetails($testId);

		// Get all test reviewers
		$db = Factory::getDBO();
		$query = $db->getQuery(true);
		$query->select('tr.id, tr.user_id');
		$query->from('#__tmt_tests_reviewers AS tr');
		$query->where('tr.test_id =' . (int) $testId);
		$db->setQuery($query);
		$r_data = $db->loadObjectList();

		// Set subject
		$subject = Text::sprintf('COM_TMT_EMAIL_SUBJECT_NEW_TEST_ASSIGNED_FOR_REVIEW', $this->sitename);
		$tmtFrontendHelper = new tmtFrontendHelper;
		$link = 'index.php?option=com_tmt&view=assigntests';
		$assigned_tests_itemid = $tmtFrontendHelper->getItemId($link);
		$assigned_tests_link = Uri::root() . substr(Route::_($link . '&Itemid=' . $assigned_tests_itemid), strlen(Uri::base(true)) + 1);
		$assigned_tests_link = ' <a href=" ' . $assigned_tests_link . ' " >' . Text::_('COM_TMT_CLICK_HERE') . ' </a> ';

		foreach ($r_data as $r)
		{
			// Don't send email to logged in user
			if ($r->user_id != Factory::getUser()->id)
			{
				// Set receipint
				$recipient = Factory::getUser($r->user_id)->email;

				// Set email message
				$name = Factory::getUser($r->user_id)->name;
				$body = Text::sprintf('COM_TMT_EMAIL_BODY_NEW_TEST_ASSIGNED_FOR_REVIEW', $name, $this->sitename, $test->title, $assigned_tests_link);
				$bcc_string = '';

				// Send email
				$this->sendmail($recipient, $subject, $body, $bcc_string);
			}
		}
	}

	/**
	 * Send thank you email to candidate
	 *
	 * @param   int  $invite_id  id of the invite
	 * @param   int  $test_id    id of the test
	 * @param   int  $courseId   id of the course
	 *
	 * @return  void
	 *
	 * @since  1.0
	 */
	public function sendThankYouEmailToCandidate($invite_id, $test_id, $courseId)
	{
		$test = self::getTestDetails($test_id);

		// Get time spent
		$db = Factory::getDBO();
		$query = $db->getQuery(true);
		$query->select(array('ta.time_taken', 'ta.score', 'ta.attempt_status'));
		$query->from($db->quoteName('#__tmt_tests_attendees', 'ta'));
		$query->where($db->quoteName('ta.invite_id') . ' = ' . (int) $invite_id);
		$query->where($db->quoteName('ta.user_id') . ' = ' . (int) Factory::getUser()->id);

		$db->setQuery($query);
		$testAttendee = $db->loadObject();

		$userInfo = Factory::getUser();

		$testAttendee->time_taken = TMT::Utilities()->timeFormat($testAttendee->time_taken);
		$testAttendee->id       = $userInfo->id;
		$testAttendee->name     = $userInfo->name;
		$testAttendee->username = $userInfo->username;
		$testAttendee->email    = $userInfo->email;

		if ($testAttendee->score >= $test->passing_marks)
		{
			$test->result = Text::sprintf('COM_TMT_EMAIL_SUBJECT_THANK_YOU_FOR_APPEAR_TEST_PASSING_MSG');
		}
		else
		{
			$test->result = Text::sprintf('COM_TMT_EMAIL_SUBJECT_THANK_YOU_FOR_APPEAR_TEST_FAILING_MSG');
		}

		$key = '';
		$replacements         = new stdClass;
		$replacements->course = TjLms::course($courseId);

		// Set subject
		if ($test->gradingtype == 'quiz')
		{
			$key = 'quizResult#' . $test_id;
			$replacements->quiz = $test;
			$replacements->student = $testAttendee;
			$replacements->global = $this->global;
		}
		elseif ($test->gradingtype == 'feedback')
		{
			$key = 'userFeedback#' . $test_id;
			$replacements->feedback = $test;
			$replacements->student = $testAttendee;
			$replacements->global = $this->global;
		}

		$client = 'com_tjlms';
		$recipient = array(Factory::getUser());

		$this->options->set('subject', $test);

		Tjnotifications::send($client, $key, $recipient, $replacements, $this->options);
	}

	/**
	 * Send new test paper pending email to assigned to all related hiring managers & recruiters.
	 *
	 * @param   int  $invite_id  id of the invite
	 * @param   int  $test_id    id of the test
	 *
	 * @return  void
	 *
	 * @since  1.0
	 */
	public function sendNewPaperPendingReviewEmail($invite_id, $test_id)
	{
		$test = self::getTestDetails($test_id);

		// Get test name & details
		$db = Factory::getDBO();
		$query = $db->getQuery(true);
		$query->select('*');
		$query->from($db->quoteName('#__tmt_tests_attendees', 'ta'));
		$query->where($db->quoteName('ta.invite_id') . ' = ' . (int) $invite_id);
		$query->where($db->quoteName('ta.test_id') . '=' . (int) $test_id);

		$db->setQuery($query);
		$testAttendee = $db->loadObject();

		$testUser = Factory::getUser($testAttendee->user_id);

		$testAttendee->name = $testUser->name;
		$testAttendee->username = $testUser->username;
		$testAttendee->id = $testAttendee->user_id;
		$testAttendee->email = $testUser->email;

		$link = 'index.php?option=com_tjlms&view=assesslesson';
		$pending_papers_link = Uri::root() . substr(Route::_($link . '&ltId=' . $invite_id . '&tmpl=component'), strlen(Uri::base(true)) + 1);
		$pending_papers_link = ' <a href=" ' . $pending_papers_link . ' " >' . Text::_('COM_TMT_CLICK_HERE') . ' </a> ';

		$test->link = $pending_papers_link;
		$test->creator = Factory::getUser($test->created_by)->name;
		$test->sitename = $this->sitename;

		if ($test->gradingtype == 'quiz')
		{
			$test->format = $test->gradingtype;
		}
		elseif ($test->gradingtype == 'exercise')
		{
			$test->format = $test->gradingtype;
		}

		$client = 'com_tjlms';
		$key    = 'pendingAssessment#' . $test_id;

		$replacements = new stdClass;
		$replacements->assessment = $test;
		$replacements->student = $testAttendee;
		$replacements->global = $this->global;

		// Set receipint
		$recipient = array(Factory::getUser($test->created_by));

		$this->options->set('subject', $test);

		Tjnotifications::send($client, $key, $recipient, $replacements, $this->options);
	}

	/**
	 * Send Request email to Hiring Manager
	 *
	 * @param   int  $job_id     id of the job
	 * @param   int  $client_id  id of the client
	 * @param   var  $data       email body
	 * @param   int  $client     value is com_tmt or com_vi
	 *
	 * @return  void
	 *
	 * @since  1.0
	 */
	public function sendRequestEmailToHiringManager($job_id, $client_id, $data, $client)
	{
		$db = Factory::getDBO();
		$mainframe = Factory::getApplication();
		$site = $mainframe->getCfg('sitename');
		$emailBody = nl2br($data->get('emailBody', '', 'string'));
		$recipient = '';
		$subject = '';
		$body = '';
		$bcc_string = '';
		$tmtCandidateHelper = new tmtCandidateHelper;
		$jobname = $tmtCandidateHelper->getJobName($job_id);

		// Get test name & details
		$query = $db->getQuery(true);

		if ($client == 'com_tmt')
		{
			$query->select('t.id, t.title,t.created_by');
			$query->from('#__tmt_tests AS t');
			$query->where('t.id =' . (int) $client_id);
			$db->setQuery($query);
			$test = $db->loadObject();
			$recipientx = Factory::getUser($test->created_by)->email;

			// Get all test reviewers
			$query = $db->getQuery(true);
			$query->select('tr.id, tr.user_id');
			$query->from('#__tmt_tests_reviewers AS tr');
			$query->where('tr.test_id =' . (int) $client_id);
			$db->setQuery($query);
			$r_data = $db->loadObjectList();

			// Set subject
			$subject = Text::sprintf('COM_TMT_CONTACT_SUBJECT', $jobname);
			$this->sendmail($recipientx, $subject, $emailBody, $bcc_string);

			foreach ($r_data as $r)
			{
				// Set receipint
				$recipient = Factory::getUser($r->user_id)->email;

				// Send email
				$this->sendmail($recipient, $subject, $emailBody, $bcc_string);
			}
		}
		elseif ($client == 'com_vi')
		{
			$query->select('ti.interview_id, ti.interview_title,ti.created_user_id,ti.hiring_mangares');
			$query->from('#__tn_interviews AS ti');
			$query->where('ti.interview_id =' . (int) $client_id);
			$db->setQuery($query);
			$interview = $db->loadObject();
			$recipientx = Factory::getUser($interview->created_user_id)->email;
			$recipient = Factory::getUser($interview->hiring_mangares)->email;
			$subject = Text::sprintf('COM_TMT_VI_CONTACT_SUBJECT', $jobname);
			$this->sendmail($recipientx, $subject, $emailBody, $bcc_string);
			$this->sendmail($recipient, $subject, $emailBody, $bcc_string);
		}
	}

	/**
	 * Send new Interview pending email to assigned to all related hiring managers & recruiters.
	 *
	 * @param   int  $invite_id     id of the invite
	 * @param   int  $interview_id  id of the interview
	 *
	 * @return  void
	 *
	 * @since  1.0
	 */
	public function sendNewInterviewPendingReviewEmail($invite_id, $interview_id)
	{
		$db = Factory::getDBO();
		$mainframe = Factory::getApplication();
		$site = $mainframe->getCfg('sitename');
		$recipient = '';
		$subject = '';
		$body = '';
		$bcc_string = '';

		// Get interview name & details
		$query = $db->getQuery(true);
		$query->select('ti. interview_id , ti. interview_title ');
		$query->from('#__tn_interviews AS ti');
		$query->where('ti. interview_id  =' . (int) $interview_id);
		$db->setQuery($query);
		$interview = $db->loadObject();

		// Get all interview reviewers
		$query = $db->getQuery(true);
		$query->select('tir.id, tir.user_id');
		$query->from('#__tn_interview_reviewers AS tir');
		$query->where('tir.interview_id =' . (int) $interview_id);
		$db->setQuery($query);
		$r_data = $db->loadObjectList();

		// Get interview name & details
		$query = $db->getQuery(true);
		$query->select('tia.user_id');
		$query->from('#__tn_interviews_attendees AS tia');
		$query->where('tia.invite_id =' . (int) $invite_id);
		$query->where('tia.interview_id  =' . (int) $interview_id);
		$db->setQuery($query);
		$interview_user_id = $db->loadResult();

		// Set subject
		$subject = Text::sprintf('COM_TMT_EMAIL_SUBJECT_NEW_INTERVIEW_PENDING_REVIEW', $site);
		$viFrontendHelper = new tmtFrontendHelper;
		$link = 'index.php?option=com_vi&view=pendinginterview';
		$itemid = $viFrontendHelper->getItemId($link);

		$link = Uri::root() . substr(Route::_($link . '&interview_id=' . $interview_id . '&Itemid=' . $itemid), strlen(Uri::base(true)) + 1);
		$link = ' <a href=" ' . $link . ' " >' . Text::_('COM_TMT_CLICK_HERE') . ' </a> ';
		$candidate_name = Factory::getUser($interview_user_id)->name;

		foreach ($r_data as $r)
		{
			// Set receipint
			$recipient = Factory::getUser($r->user_id)->email;

			// Set email message
			$name = Factory::getUser($r->user_id)->name;
			$const = 'COM_TMT_EMAIL_BODY_NEW_INTERVIEW_PENDING_REVIEW';
			$body = Text::sprintf($const, $name, $site, $interview->interview_title, $candidate_name, $link);

			// Send email
			$this->sendmail($recipient, $subject, $body, $bcc_string);
		}
	}

	/**
	 * Send thank you email to candidate after submit interview
	 *
	 * @param   int  $interview_id  id of the interview
	 *
	 * @return  void
	 *
	 * @since  1.0
	 */
	public function sendSubmitIterviewThankYouEmailToCandidate($interview_id)
	{
		$db = Factory::getDBO();
		$mainframe = Factory::getApplication();
		$site = $mainframe->getCfg('sitename');
		$recipient = '';
		$subject = '';
		$body = '';
		$bcc_string = '';

		// Get interview name & details
		$query = $db->getQuery(true);
		$query->select('ti. interview_id , ti. interview_title ');
		$query->from('#__tn_interviews AS ti');
		$query->where('ti. interview_id  =' . (int) $interview_id);
		$db->setQuery($query);
		$interview = $db->loadObject();

		// Set subject
		$subject = Text::sprintf('COM_TMT_EMAIL_SUBJECT_THANK_YOU_FOR_APPEAR_INTERVIEW', $site);

		// Set receipint, send email to logged in - candidate
		$recipient = Factory::getUser()->email;

		// Set email message
		$name = Factory::getUser()->name;
		$body = Text::sprintf('COM_TMT_EMAIL_BODY_THANK_YOU_FOR_APPEAR_INTERVIEW', $name, $site, $interview->interview_title);

		// Send email
		$this->sendmail($recipient, $subject, $body, $bcc_string);
	}

	/**
	 * get test details
	 *
	 * @param   int  $testId  test id
	 *
	 * @return  object
	 */
	public static function getTestDetails($testId)
	{
		$db = Factory::getDBO();

		// Get test name & details
		$query = $db->getQuery(true);
		$query->select(array('*'));
		$query->from($db->quoteName('#__tmt_tests', 't'));
		$query->where($db->quoteName('t.id') . ' = ' . (int) $testId);
		$db->setQuery($query);

		return $db->loadObject();
	}
}
