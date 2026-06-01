<?php
/**
 * @package     TMT
 * @subpackage  com_shika
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\Registry\Registry;

/**
 * TMT email class for all kinds of email methods
 *
 * @since  _DEPLOY_VERSION_
 */
class TmtEmail
{
	public $app;

	public $global;

	public $options;

	public $sitename;

	public $loggedInUser;

	public $tjnotificationClient = 'com_tjlms';

	/**
	 * Method acts as a consturctor
	 *
	 * @since   _DEPLOY_VERSION_
	 */
	public function __construct()
	{
		$this->app              = Factory::getApplication();
		$this->loggedInUser     = Factory::getUser();
		$this->sitename         = $this->app->getCfg('sitename');
		$this->global           = new stdClass;
		$this->global->sitename = $this->sitename;
		$this->options          = new Registry;
	}

	/**
	 * Send thank you email to candidate
	 *
	 * @param   int  $inviteId  Invite Id
	 * @param   int  $testId    Test Id
	 * @param   int  $courseId  Course Id
	 *
	 * @return  void
	 *
	 * @since  _DEPLOY_VERSION_
	 */
	public function sendThankYouEmailToCandidate($inviteId, $testId, $courseId)
	{
		$test = TMT::Test($testId);

		// Get time spent
		$testAttendeesModel = TMT::model('TestAttendees');

		$testAttendeesModel->setState('filter.invite_id', $inviteId);
		$testAttendeesModel->setState('filter.user_id', $this->loggedInUser->id);

		$testAttendeeItems = $testAttendeesModel->getItems();

		$testAttendee = new stdClass;
		$testAttendee = $testAttendeeItems[0];

		$testAttendee->time_taken = TMT::Utilities()->timeFormat($testAttendee->time_taken);
		$testAttendee->id         = $this->loggedInUser->id;
		$testAttendee->name       = $this->loggedInUser->name;
		$testAttendee->username   = $this->loggedInUser->username;
		$testAttendee->email      = $this->loggedInUser->email;

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
			$key                   = 'quizResult#' . $testId;
			$replacements->quiz    = $test;
			$replacements->student = $testAttendee;
			$replacements->global  = $this->global;
		}
		elseif ($test->gradingtype == 'feedback')
		{
			$key                    = 'userFeedback#' . $testId;
			$replacements->feedback = $test;
			$replacements->student  = $testAttendee;
			$replacements->global   = $this->global;
		}

		$recipient = array (

			Factory::getUser($this->loggedInUser->id),
			// Add specific to, cc (optional), bcc (optional)
			'email' => array (
				'to' => array ($this->loggedInUser->email)
			)
		);

		$this->options->set('subject', $test);

		Tjnotifications::send($this->tjnotificationClient, $key, $recipient, $replacements, $this->options);
	}

	/**
	 * Send new test paper pending email to assigned to all related hiring managers & recruiters.
	 *
	 * @param   int  $inviteId  Invite Id
	 * @param   int  $testId    Test Id
	 *
	 * @return  void
	 *
	 * @since  1.0
	 */
	public function sendNewPaperPendingReviewEmail($inviteId, $testId)
	{
		$test = TMT::Test($testId);

		// Get test name & details
		$testAttendeesModel = TMT::model('TestAttendees');

		$testAttendeesModel->setState('filter.invite_id', $inviteId);
		$testAttendeesModel->setState('filter.test_id', $testId);

		$testAttendeeItems = $testAttendeesModel->getItems();

		$testAttendee = new stdClass;
		$testAttendee = $testAttendeeItems[0];

		$testUser = Factory::getUser($testAttendee->user_id);

		$testAttendee->name     = $testUser->name;
		$testAttendee->username = $testUser->username;
		$testAttendee->id       = $testAttendee->user_id;
		$testAttendee->email    = $testUser->email;

		$link                   = 'index.php?option=com_tjlms&view=assesslesson';
		$rootLink               = Uri::root() . substr(Route::_($link . '&ltId=' . $inviteId . '&tmpl=component'), strlen(Uri::base(true)) + 1);
		$pendingPapersLink      = ' <a href=" ' . $rootLink . ' " >' . Text::_('COM_TMT_CLICK_HERE') . ' </a> ';
		$pendingPapersPlainLink = $rootLink;

		$test->link       = $pendingPapersLink;
		$test->plain_link = $pendingPapersPlainLink;
		$test->creator    = Factory::getUser($test->created_by)->name;
		$test->sitename   = $this->sitename;

		if ($test->gradingtype == 'quiz')
		{
			$test->format = $test->gradingtype;
		}
		elseif ($test->gradingtype == 'exercise')
		{
			$test->format = $test->gradingtype;
		}

		$replacements             = new stdClass;
		$replacements->assessment = $test;
		$replacements->student    = $testAttendee;
		$replacements->global     = $this->global;

		// Set receipint
		$recipientUser = Factory::getUser($test->created_by);
		$recipient = array (

			Factory::getUser($recipientUser->id),
			// Add specific to, cc (optional), bcc (optional)
			'email' => array (
				'to' => array ($recipientUser->email)
			)
		);

		$this->options->set('subject', $test);

		$key = 'pendingAssessment#' . $testId;

		Tjnotifications::send($this->tjnotificationClient, $key, $recipient, $replacements, $this->options);
	}
}
