<?php
/**
 * @package     Shika
 * @subpackage  com_tjlms
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;
jimport('techjoomla.tjnotifications.tjnotifications');

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use Joomla\Registry\Registry;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Component\ComponentHelper;
/**
 * Tjlms course helper.
 *
 * @since  1.0.0
 */
class TjlmsMailcontentHelper
{
	public $sitename;

	public $global;

	public $options;

	public $comtjlmsHelper;

	public $tjlmsCoursesHelper;

	/**
	 * Method acts as a consturctor
	 *
	 * @since   1.0.0
	 */
	public function __construct ()
	{
		$app            = Factory::getApplication();
		$this->sitename = method_exists($app, 'getCfg') ? $app->getCfg('sitename') : $app->get('sitename');

		$this->global           = new stdClass;
		$this->global->sitename = $this->sitename;
		$this->options          = new Registry;

		$this->comtjlmsHelper     = new comtjlmsHelper;
		$this->tjlmsCoursesHelper = new TjlmsCoursesHelper;
	}

	/**
	 * Function use to create mail content
	 *
	 * @param   INT  $actorId          user has been enrolled
	 * @param   INT  $courseCreator    Course creator
	 * @param   INT  $courseId         course ID
	 * @param   INT  $courseLink       Link of a course
	 * @param   INT  $state            Enrollment state
	 * @param   INT  $coursePlainLink  Plain link of a course
	 *
	 * @return  boolean  true or false
	 *
	 * @since  1.0.0
	 */
	public function onAfterCourseEnrolMail($actorId, $courseCreator, $courseId, $courseLink, $state, $coursePlainLink)
	{
		$course                    = $this->tjlmsCoursesHelper->getcourseInfo($courseId);
		$course->creator_name      = Factory::getUser($course->created_by)->name;
		$course->course_link       = $courseLink;
		$course->course_plain_link = $coursePlainLink;

		$enrollment = $this->comtjlmsHelper->getEnrollmentDetails($courseId, $actorId);

		$replacements                = new stdClass;
		$replacements->course        = $course;
		$replacements->student       = $enrollment;
		$replacements->course_author = Factory::getUser($course->created_by);
		$replacements->global        = $this->global;

		if ($state == 1)
		{
			$key = 'courseEnroll#' . $courseId;
		}
		else
		{
			$key = 'enrollmentApprove#' . $courseId;
		}

		$client = "com_tjlms";
		$this->options->set('subject', $course);

		$recipientsCourseCreator = Factory::getUser($course->created_by);

		$recipients = array (

			Factory::getUser($recipientsCourseCreator->id),

			// Add specific to, cc (optional), bcc (optional)
			'email' => array (
				'to' => array ($recipientsCourseCreator->email)
			)
		);

		Tjnotifications::send($client, $key, $recipients, $replacements, $this->options);

		$date = Factory::getDate('now');

		// Send mail to actor
		if ($course->state == 1 && date("Y-m-d", strtotime($course->start_date)) <= date("Y-m-d", strtotime($date)))
		{
			// Enroll user email
			$recipientsEnrollUser = Factory::getUser($enrollment->user_id);

			$recipients = array (

				Factory::getUser($recipientsEnrollUser->id),
				// Add specific to, cc (optional), bcc (optional)
				'email' => array (
					'to' => array ($recipientsEnrollUser->email)
				)
			);

			$key = 'userEnroll#' . $courseId;

			Tjnotifications::send($client, $key, $recipients, $replacements, $this->options);
		}
	}

	/**
	 * Function use to create mail content
	 *
	 * @param   INT  $actorId          user has been enrolled
	 * @param   INT  $courseId         course ID
	 * @param   INT  $courseLink       Link to course
	 * @param   INT  $state            Enrollment state
	 * @param   INT  $coursePlainLink  Plain link to course
	 *
	 * @return  boolean  true or false
	 *
	 * @since  1.0.0
	 */
	public function onAfterCourseEnrolApproveMail($actorId, $courseId, $courseLink, $state, $coursePlainLink)
	{
		if ($state == 1)
		{
			$course                    = $this->tjlmsCoursesHelper->getcourseInfo($courseId);
			$course->course_link       = $courseLink;
			$course->course_plain_link = $coursePlainLink;

			$course_author = Factory::getUser($course->created_by);

			$enrollment = $this->comtjlmsHelper->getEnrollmentDetails($courseId, $actorId);

			$replacements                = new stdClass;
			$replacements->course        = $course;
			$replacements->student       = $enrollment;
			$replacements->course_author = $course_author;
			$replacements->global        = $this->global;

			$client = "com_tjlms";
			$key    = 'userEnrollApproved#' . $courseId;
			$this->options->set('subject', $course);

			$recipientsEnrollUser = Factory::getUser($enrollment->user_id);
			$recipients = array (

				Factory::getUser($recipientsEnrollUser->id),

				// Add specific to, cc (optional), bcc (optional)
				'email' => array (
					'to' => array ($recipientsEnrollUser->email)
				)
			);

			Tjnotifications::send($client, $key, $recipients, $replacements, $this->options);
		}
	}

	/**
	 * Function use to create mail content
	 *
	 * @param   INT  $to               user to whom course is recommended
	 * @param   INT  $courseId         course ID
	 * @param   INT  $courseLink       Link to course
	 * @param   INT  $from             User who recommended the course
	 * @param   INT  $coursePlainLink  Plain link to course
	 *
	 * @return  boolean  true or false
	 *
	 * @since  1.0.0
	 */
	public function onAfterRecommendMail($to, $courseId, $courseLink, $from, $coursePlainLink)
	{
		// Tags Replacement
		$course                    = $this->tjlmsCoursesHelper->getcourseInfo($courseId);
		$course->course_link       = $courseLink;
		$course->course_plain_link = $coursePlainLink;
		$course->recommedBy        = Factory::getUser($from)->name;

		$replacements                = new stdClass;
		$replacements->course        = $course;
		$replacements->recommendedTo = Factory::getUser($to);
		$replacements->recommendedBy = Factory::getUser($from);
		$replacements->global        = $this->global;

		$client = 'com_tjlms';
		$key    = 'courseRecommed#' . $courseId;

		$this->options->set('subject', $course);

		$recipientsRecomUser   = Factory::getUser($to);
		$recipients = array (

			Factory::getUser($recipientsRecomUser->id),
			// Add specific to, cc (optional), bcc (optional)
			'email' => array (
				'to' => array ($recipientsRecomUser->email)
			)
		);

		Tjnotifications::send($client, $key, $recipients, $replacements, $this->options);
	}

	/**
	 * Function use to create mail content
	 *
	 * @param   INT  $actorId          user has been enrolled
	 * @param   INT  $courseId         course ID
	 * @param   INT  $courseLink       Link to course
	 * @param   INT  $coursePlainLink  Plain link to course
	 *
	 * @return  boolean  true or false
	 *
	 * @since  1.0.0
	 */
	public function onAfterCourseCompletionMail($actorId, $courseId, $courseLink, $coursePlainLink)
	{
		$course                    = $this->tjlmsCoursesHelper->getcourseInfo($courseId);
		$course->creator_name      = Factory::getUser($course->created_by)->name;
		$course->course_link       = $courseLink;
		$course->course_plain_link = $coursePlainLink;

		$enrollment = $this->comtjlmsHelper->getEnrollmentDetails($courseId, $actorId);

		$replacements          = new stdClass;
		$replacements->course  = $course;
		$replacements->student = $enrollment;
		$replacements->global  = $this->global;

		$client = "com_tjlms";
		$key    = 'courseComplete#' . $courseId;

		$this->options->set('subject', $course);

		$recipientsEnrollUser = Factory::getUser($enrollment->user_id);
		$recipients = array (

			Factory::getUser($recipientsEnrollUser->id),

			// Add specific to, cc (optional), bcc (optional)
			'email' => array (
				'to' => array ($recipientsEnrollUser->email)
			)
		);

		Tjnotifications::send($client, $key, $recipients, $replacements, $this->options);
	}

	/**
	 * Function use to create mail content
	 *
	 * @param   INT  $assignTo         user to whom the course is assign
	 * @param   INT  $params           param object
	 * @param   INT  $courseLink       Link to course
	 * @param   INT  $assignBy         User who assigned the course
	 * @param   INT  $coursePlainLink  Plain link to course
	 *
	 * @return  boolean  true or false
	 *
	 * @since  1.0.0
	 */
	public function onAfterCourseAssignMail($assignTo, $params, $courseLink, $assignBy, $coursePlainLink)
	{
		$com_params	= ComponentHelper::getParams('com_tjlms');
		$dateFormat = $com_params->get('date_format_show', 'Y-m-d h:i:s');

		// Tags Replacement
		$course                    = $this->tjlmsCoursesHelper->getcourseInfo($params['element_id']);
		$course->course_link       = $courseLink;
		$course->course_plain_link = $coursePlainLink;
		$course->assigner          = Factory::getUser($assignBy)->name;

		$assignment             = new stdClass;
		$assignment->sender_msg = $params['sender_msg'];
		$assignment->start_date = HTMLHelper::date($params['start_date'], $dateFormat);
		$assignment->end_date   = HTMLHelper::date($params['due_date'], $dateFormat);

		$replacements             = new stdClass;
		$replacements->course     = $course;
		$replacements->student    = Factory::getUser($assignTo);
		$replacements->assigner   = Factory::getUser($assignBy);
		$replacements->assignment = $assignment;
		$replacements->global     = $this->global;

		$client = 'com_tjlms';
		$key    = 'courseAssign#' . $params['element_id'];

		$this->options->set('subject', $course);

		$recipientsAssignUser   = Factory::getUser($assignTo);
		$recipients = array (

			Factory::getUser($recipientsAssignUser->id),
			// Add specific to, cc (optional), bcc (optional)
			'email' => array (
				'to' => array ($recipientsAssignUser->email)
			)
		);

		Tjnotifications::send($client, $key, $recipients, $replacements, $this->options);
	}

	/**
	 * Function use to create mail content
	 *
	 * @param   INT     $userId           User ID
	 * @param   INT     $courseId         Course ID
	 * @param   INT     $courseLink       Link to course
	 * @param   String  $elementTitle     course name
	 * @param   INT     $coursePlainLink  Plain link to course
	 *
	 * @return  boolean  true or false
	 *
	 * @since  1.0.0
	 */
	public function onAfterSubscriptionExpiredMail($userId, $courseId, $courseLink, $elementTitle, $coursePlainLink)
	{
		// Tags Replacement
		$course                    = $this->tjlmsCoursesHelper->getcourseInfo($courseId);
		$course->course_link       = $courseLink;
		$course->course_plain_link = $coursePlainLink;

		// Get enrollment data
		$enrollment = $this->comtjlmsHelper->getEnrollmentDetails($courseId, $userId);

		$replacements          = new stdClass;
		$replacements->course  = $course;
		$replacements->student = $enrollment;
		$replacements->global  = $this->global;

		$client = 'com_tjlms';
		$key    = 'courseSubscriptionExpired#' . $courseId;

		$this->options->set('subject', $course);

		$recipientsSubExpUser = Factory::getUser($userId);
		$recipients = array (

			Factory::getUser($recipientsSubExpUser->id),
			// Add specific to, cc (optional), bcc (optional)
			'email' => array (
				'to' => array ($recipientsSubExpUser->email)
			)
		);

		$result = Tjnotifications::send($client, $key, $recipients, $replacements, $this->options);

		if ($result['success'])
		{
			$this->tjlmsCoursesHelper->updateCourseEnrolledParams($courseId, $userId, '1', 'after_expiry_mail');
		}
	}

	/**
	 * Function use to create mail content
	 *
	 * @param   INT     $userId           User ID
	 * @param   INT     $courseId         Course ID
	 * @param   String  $courseLink       Link to course
	 * @param   String  $coursePlainLink  Link to course
	 *
	 * @return  boolean  true or false
	 *
	 * @since  1.3.2
	 */
	public function onBeforeSubscriptionExpiredMail($userId, $courseId, $courseLink, $coursePlainLink)
	{
		// Tags Replacement
		$course                    = $this->tjlmsCoursesHelper->getcourseInfo($courseId);
		$course->course_link       = $courseLink;
		$course->course_plain_link = $coursePlainLink;
		$enrollment                = $this->comtjlmsHelper->getEnrollmentDetails($courseId, $userId);

		$replacements          = new stdClass;
		$replacements->course  = $course;
		$replacements->student = $enrollment;
		$replacements->global  = $this->global;

		$client = 'com_tjlms';
		$key    = 'courseSubscriptionReminder#' . $courseId;

		$this->options->set('subject', $course);

		$recipientsSubReminUser = Factory::getUser($userId);
		$recipients = array (

			Factory::getUser($recipientsSubReminUser->id),
			// Add specific to, cc (optional), bcc (optional)
			'email' => array (
				'to' => array ($recipientsSubReminUser->email)
			)
		);

		$result = Tjnotifications::send($client, $key, $recipients, $replacements, $this->options);

		if ($result['success'])
		{
			$this->tjlmsCoursesHelper->updateCourseEnrolledParams($courseId, $userId, '1', 'before_expiry_mail');
		}
	}

	/**
	 * Function used to get course creator.
	 *
	 * @param   INT  $courseId  Course ID
	 *
	 * @return  INT  $courseCreator  Creator of the course
	 *
	 * @since  1.0.0
	 */
	public function getCourseCreator($courseId)
	{
		try
		{
			$db    = Factory::getDBO();
			$query = $db->getQuery(true);

			$query->select($db->qn('created_by'));
			$query->from($db->qn('#__tjlms_courses'));
			$query->where($db->qn('id') . '=' . $db->q((int) $courseId));
			$db->setQuery($query);

			return $db->loadResult();
		}
		catch (Exception $e)
		{
			return false;
		}
	}

	/**
	 * Function to get html and send invoice mail
	 *
	 * @param   INT  $id  Order ID
	 *
	 * @return  boolean
	 *
	 * @since 1.0.0
	 */
	public function sendInvoiceEmail($id)
	{
		$paymentStatus        = array();
		$paymentStatus['I']   = Text::_('COM_TJLMS_PSTATUS_INITIATED');
		$paymentStatus['P']   = Text::_('COM_TJLMS_PSTATUS_PENDING');
		$paymentStatus['C']   = Text::_('COM_TJLMS_PSTATUS_COMPLETED');
		$paymentStatus['D']   = Text::_('COM_TJLMS_PSTATUS_DECLINED');
		$paymentStatus['E']   = Text::_('COM_TJLMS_PSTATUS_FAILED');
		$paymentStatus['UR']  = Text::_('COM_TJLMS_PSTATUS_UNDERREVIW');
		$paymentStatus['RF']  = Text::_('COM_TJLMS_PSTATUS_REFUNDED');
		$paymentStatus['CRV'] = Text::_('COM_TJLMS_PSTATUS_CANCEL_REVERSED');
		$paymentStatus['RV']  = Text::_('COM_TJLMS_PSTATUS_REVERSED');

		$techjoomlacommon = new TechjoomlaCommon;
		$params           = ComponentHelper::getParams('com_tjlms');
		$orderCurrency    = $params->get('currency');
		$dateFormat       = $params->get('date_format_show', 'Y-m-d H:i:s');

		$orderItemid = $this->comtjlmsHelper->getItemId('index.php?option=com_tjlms&view=orders');
		$order       = $this->comtjlmsHelper->getorderinfo($id);

		$orderinfo  = $order['order_info'];
		$orderitems = $order['items'];
		$buyerEmail = array();
		$buyer      = new stdClass;

		if ($orderinfo[0]->address_type == 'BT')
		{
			$buyerEmail[] = $orderinfo[0]->user_email;
			$buyerPhone   = $orderinfo[0]->phone;
			$buyer        = $orderinfo[0];

			if (empty($buyerPhone))
			{
				$recipients = array (

					Factory::getUser($orderinfo[0]->user_id),

					// Add specific to, cc (optional), bcc (optional)
					'email' => array (
						'to' => $buyerEmail
					)
				);
			}
			else
			{
				$recipients = array (
					// Add specific to, cc (optional), bcc (optional)
					'email' => array (
						'to' => $buyerEmail
					),
					'sms' => array (
						$buyerPhone
					)
				);
			}
		}
		elseif ($orderinfo[1]->address_type == 'BT')
		{
			$buyerEmail[] = $orderinfo[1]->user_email;
			$buyerPhone   = $orderinfo[1]->phone;
			$buyer        = $orderinfo[1];

			if (empty($buyerPhone))
			{
				$recipients = array (

					Factory::getUser($orderinfo[1]->user_id),

					// Add specific to, cc (optional), bcc (optional)
					'email' => array (
						'to' => $buyerEmail
					)
				);
			}
			else
			{
				$recipients = array (
					// Add specific to, cc (optional), bcc (optional)
					'email' => array (
						'to' => $buyerEmail
					),
					'sms' => array (
						$buyerPhone
					)
				);
			}
		}

		$courseDetails = new stdClass;

		if (isset($orderinfo))
		{
			$where = " o.id=" . $orderinfo['0']->order_id;

			if ($orderinfo['0']->order_id)
			{
				$courseDetails = $this->comtjlmsHelper->getallCourseDetailsByOrder($where);
			}

			$orderinfo               = $orderinfo[0];
			$orderinfo->orderSuffix  = $orderinfo->orderid_with_prefix;
			$orderinfo->status       = $paymentStatus[$orderinfo->status];
			$orderinfo->created_date = $techjoomlacommon->getDateInLocal($orderinfo->cdate, 0, $dateFormat);
		}

		$course = $courseDetails[0];

		if (isset($course->image))
		{
			$courseArray = (array) $course;

			$course->image = $this->tjlmsCoursesHelper->getCourseImage($courseArray, 'S_');
		}

		$elementUrl                = $this->comtjlmsHelper->tjlmsRoute('index.php?option=com_tjlms&view=course&id=' . $course->course_id, false, -1);
		$elementTitle              = $this->tjlmsCoursesHelper->courseName($course->course_id);
		$course->course_link       = '<a href="' . $elementUrl . '">' . $elementTitle . '</a>';
		$course->course_plain_link = $elementUrl;
		$course->sitename          = $this->sitename;

		$company             = new stdClass;
		$company->name       = $params->get('company_name', '', 'STRING');
		$company->address    = $params->get('company_address', '', 'STRING');
		$company->vat_number = $params->get('company_vat_no', '', 'STRING');

		$oWithSuf  = $order['order_info'][0]->orderid_with_prefix;
		$processor = $order['order_info'][0]->processor;
		$orderUrl  = 'index.php?option=com_tjlms&view=orders&layout=order&orderid=' . $oWithSuf . '&processor=' . $processor . '&Itemid=' . $orderItemid;

		$currenturl = Uri::root() . substr(Route::_($orderUrl, false), strlen(Uri::base(true)) + 1);

		$orderinfo->order_link = $currenturl;

		$subscription        = new stdClass;
		$subscription->name  = $orderitems[0]->order_item_name;
		$subscription->price = $this->comtjlmsHelper->getFromattedPrice(number_format($orderitems[0]->price, 2), $orderCurrency);

		$subscription->total_price = $subscription->price;

		$totalAmountAfterDisc   = $orderinfo->original_amount;
		$totalAmountAfterDisc   = $totalAmountAfterDisc - $orderinfo->coupon_discount;
		$subscription->discount = $this->comtjlmsHelper->getFromattedPrice(number_format($orderinfo->coupon_discount, 2), $orderCurrency);
		$subscription->amt_after_discount = $this->comtjlmsHelper->getFromattedPrice(number_format($totalAmountAfterDisc, 2), $orderCurrency);

		$taxJson  = $orderinfo->order_tax_details;
		$taxArray = json_decode($taxJson, true);
		$subscription->tax_percent = ' 0 %';

		if ($taxArray['percent'])
		{
			$subscription->tax_percent = $taxArray['percent'];
		}

		$subscription->order_tax = $this->comtjlmsHelper->getFromattedPrice(number_format($orderinfo->order_tax, 2), $orderCurrency);

		$subscription->final_amt = $this->comtjlmsHelper->getFromattedPrice(number_format($orderinfo->amount, 2), $orderCurrency);

		$key = 'courseInvoice#' . $course->course_id;

		if ($course->status == 'I')
		{
			$key = 'courseOrder#' . $course->course_id;
		}

		$replacements                = new stdClass;
		$replacements->course        = $course;
		$replacements->company       = $company;
		$replacements->buyer         = $buyer;
		$replacements->order         = $orderinfo;
		$replacements->subscription  = $subscription;
		$replacements->global        = $this->global;
		$courseCreator               = $this->getCourseCreator($course->course_id);
		$replacements->course_author = Factory::getUser($courseCreator);

		$client     = 'com_tjlms';

		$this->options->set('subject', $course);

		Tjnotifications::send($client, $key, $recipients, $replacements, $this->options);

		if ($course->status == 'C')
		{
			$recipients = array();
			$db = Factory::getDBO();

			// Get all admin users
			$query = $db->getQuery(true);

			$query->select('id');
			$query->from($db->quoteName('#__users'));
			$query->where($db->quoteName('sendEmail') . '= 1');
			$db->setQuery($query);
			$adminUsers = $db->loadObjectList();

			foreach ($adminUsers as $adminUser)
			{
				$recipients[]                = Factory::getUser($adminUser->id);
				$recipients['email']['to'][] = Factory::getUser($adminUser->id)->email;
			}

			$options = new Registry;
			$options->set('subject', $course);
			$key = 'courseOrderComfirmationAdmin#' . $course->course_id;

			Tjnotifications::send($client, $key, $recipients, $replacements, $options);

			$recipients    = array();
			$courseCreator = $this->getCourseCreator($course->course_id);

			$options = new Registry;
			$options->set('subject', $course);
			$key = 'courseOrderComfirmationCreator#' . $course->course_id;

			$recipients = array (
				// Add specific to, cc (optional), bcc (optional)
				'email' => array (
					'to' => array (Factory::getUser($courseCreator)->email)
				)
			);

			Tjnotifications::send($client, $key, $recipients, $replacements, $options);
		}

		return true;
	}

	/**
	 * Send thank you email to candidate
	 *
	 * @param   int  $invite_id  id of the invite
	 * @param   int  $userId     id of the test
	 *
	 * @return  void
	 *
	 * @since  1.0
	 */
	public function sendThankYouEmailToCandidate($invite_id, $userId)
	{
		$tjlmsparams = ComponentHelper::getParams('com_tjlms');
		$adminKey    = $tjlmsparams->get('admin_key_review_answersheet', 'abcd1234', 'STRING');
		$adminKey    = md5($adminKey);

		$db = Factory::getDBO();

		// Get time spent
		$query = $db->getQuery(true);
		$query->select(array('ta.time_taken', 'ta.score', 'ta.attempt_status', 'ta.test_id'));
		$query->from($db->quoteName('#__tmt_tests_attendees', 'ta'));
		$query->where($db->quoteName('ta.invite_id') . ' = ' . (int) $invite_id);
		$query->where($db->quoteName('ta.user_id') . ' = ' . (int) $userId);
		$db->setQuery($query);
		$testAttendee = $db->loadObject();

		// Get test name & details
		$query = $db->getQuery(true);
		$query->select(array('t.id', 't.title', 't.passing_marks', 't.total_marks', 't.gradingtype'));
		$query->from($db->quoteName('#__tmt_tests', 't'));
		$query->where($db->quoteName('t.id') . ' = ' . (int) $testAttendee->test_id);
		$db->setQuery($query);
		$test = $db->loadObject();
		$test->sitename = $this->sitename;

		$testUser = Factory::getUser($userId);

		$testAttendee->time_taken = $this->comtjlmsHelper->secToHours($testAttendee->time_taken);
		$testAttendee->id         = $userId;
		$testAttendee->name       = $testUser->name;
		$testAttendee->username   = $testUser->username;
		$testAttendee->email      = $testUser->email;

		if ($testAttendee->score >= $test->passing_marks)
		{
			$test->result = Text::sprintf('COM_TJLMS_EMAIL_SUBJECT_THANK_YOU_FOR_APPEAR_TEST_PASSING_MSG');
		}
		else
		{
			$test->result = Text::sprintf('COM_TJLMS_EMAIL_SUBJECT_THANK_YOU_FOR_APPEAR_TEST_FAILING_MSG');
		}

		$replacements = new stdClass;
		$key = '';

		$assessmentUrl = Route::_(
			Uri::root() . 'index.php?option=com_tmt&view=answersheet&tmpl=component&adminKey=' .
			$adminKey . '&id=' . $testAttendee->test_id . '&ltId=' . $invite_id . '&isSite=1');

		// Set subject
		if ($test->gradingtype == 'quiz')
		{
			$key                   = 'quizResult#' . $testAttendee->test_id;
			$replacements->quiz    = $test;
			$replacements->student = $testAttendee;
			$replacements->global  = $this->global;
		}
		elseif ($test->gradingtype == 'exercise')
		{
			$key                    = 'userExercise#' . $testAttendee->test_id;
			$test->assessmentUrl    = '<a href="' . $assessmentUrl . '">Click here </a>';
			$replacements->exercise = $test;
			$replacements->student  = $testAttendee;
			$replacements->global   = $this->global;
		}

		$client     = 'com_tjlms';
		$recipientsTestUser = Factory::getUser($userId);
		$recipients = array (

			Factory::getUser($userId),

			// Add specific to, cc (optional), bcc (optional)
			'email' => array (
				'to' => array ($recipientsTestUser->email)
			)
		);
		$this->options->set('subject', $test);

		Tjnotifications::send($client, $key, $recipients, $replacements, $this->options);
	}

	/**
	 * Function use to sent certificate expire email to candidate.
	 *
	 * @param   INT  $actorId   user has been enrolled
	 * @param   INT  $courseId  course ID
	 *
	 * @return  boolean  true or false
	 *
	 * @since  1.4.0
	 */
	public function onAfterCertificateExpired($actorId, $courseId)
	{
		$course                    = $this->tjlmsCoursesHelper->getcourseInfo($courseId);
		$course->creator_name      = Factory::getUser($course->created_by)->name;

		$elementUrl                = $this->comtjlmsHelper->tjlmsRoute('index.php?option=com_tjlms&view=course&id=' . $courseId, false, -1);
		$course->course_link       = '<a href="' . $elementUrl . '">' . $course->title . '</a>';
		$course->course_plain_link = $elementUrl;

		$enrollment = $this->comtjlmsHelper->getEnrollmentDetails($courseId, $actorId);

		$replacements                = new stdClass;
		$replacements->course        = $course;
		$replacements->student       = $enrollment;
		$replacements->global        = $this->global;

		$key    = 'certificateExpired';
		$client = "com_tjlms";
		$this->options->set('subject', $course);

		$recipientsEnrollUser = Factory::getUser($actorId);

		$recipients = array (

			Factory::getUser($recipientsEnrollUser->id),

			// Add specific to, cc (optional), bcc (optional)
			'email' => array (
				'to' => array ($recipientsEnrollUser->email)
			)
		);

		Tjnotifications::send($client, $key, $recipients, $replacements, $this->options);
	}
}
