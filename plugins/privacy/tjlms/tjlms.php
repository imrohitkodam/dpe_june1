<?php
/**
 * @package     TJLMS
 * @subpackage  Plg_Privacy_TJLMS
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2018 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die();
use Joomla\CMS\User\User;
use Joomla\CMS\Table\User as UserTable;

JLoader::register('PrivacyPlugin', JPATH_ADMINISTRATOR . '/components/com_privacy/helpers/plugin.php');
JLoader::register('PrivacyRemovalStatus', JPATH_ADMINISTRATOR . '/components/com_privacy/helpers/removal/status.php');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 * TJLMS Privacy Plugin.
 *
 * @since  1.3.1
 */
class PlgPrivacyTjlms extends PrivacyPlugin
{
	/**
	 * Load the language file on instantiation.
	 *
	 * @var    boolean
	 *
	 * @since  1.3.1
	 */
	protected $autoloadLanguage = true;

	/**
	 * Database object
	 *
	 * @var    JDatabaseDriver
	 * @since  1.3.1
	 */
	protected $db;

	/**
	 * Reports the privacy related capabilities for this plugin to site administrators.
	 *
	 * @return  array
	 *
	 * @since   1.3.1
	 */
	public function onPrivacyCollectAdminCapabilities()
	{
		$this->loadLanguage();

		return array(
			Text::_('PLG_PRIVACY_TJLMS') => array(
				Text::_('PLG_PRIVACY_TJLMS_PRIVACY_CAPABILITY_USER_ORDERS_DETAIL'),
			)
		);
	}

	/**
	 * Processes an export request for TJLMS user data
	 *
	 * This event will collect data for the following tables:
	 *
	 * - #__tjlms_courses
	 * - #__tjlms_lessons
	 * - #__tjlms_enrolled_users
	 * - #__tjlms_orders
	 * - #__tjlms_users
	 * - #__tjlms_lesson_track
	 * - #__tjlms_course_track
	 *
	 * @param   PrivacyTableRequest  $request  The request record being processed
	 * @param   JUser                $user     The user account associated with this request if available
	 *
	 * @return  PrivacyExportDomain[]
	 *
	 * @since   1.3.1
	 */
	public function onPrivacyExportRequest(PrivacyTableRequest $request, User $user = null)
	{
		if (!$user)
		{
			return array();
		}

		/** @var JTableUser $user */
		$userTable = UserTable::getTable();
		$userTable->load($user->id);

		$domains = array();
		$domains[] = $this->createTjlmsCreatedCourses($userTable);
		$domains[] = $this->createTjlmsCreatedLessons($userTable);
		$domains[] = $this->createTjlmsEnrolments($userTable);
		$domains[] = $this->createTjlmsOrdres($userTable);
		$domains[] = $this->createTjlmsCourseTracking($userTable);
		$domains[] = $this->createTjlmsLessonTracking($userTable);

		return $domains;
	}

	/**
	 * Create the domain for the TJLMS Courses created by user
	 *
	 * @param   JTableUser  $user  The JTableUser object to process
	 *
	 * @return  PrivacyExportDomain
	 *
	 * @since   1.3.1
	 */
	private function createTjlmsCreatedCourses(User $user)
	{
		$domain = $this->createDomain('tjlms_created_courses', 'user_courses_created_data');

		$query = $this->db->getQuery(true)
				->select(
				$this->db->quoteName(
									array(
											'id', 'title', 'alias', 'short_desc','description','catid','type','created_by','created','start_date','end_date'
										)
									)
						)
				->from($this->db->quoteName('#__tjlms_courses'))
				->where($this->db->quoteName('created_by') . '=' . $user->id);

		$courses = $this->db->setQuery($query)->loadAssocList();

		if (!empty($courses))
		{
			foreach ($courses as $course)
			{
				$domain->addItem($this->createItemFromArray($course, $course['id']));
			}
		}

		return $domain;
	}

	/**
	 * Create the domain for the TJLMS Lessons created by user
	 *
	 * @param   JTableUser  $user  The JTableUser object to process
	 *
	 * @return  PrivacyExportDomain
	 *
	 * @since  1.3.1
	 */
	private function createTjlmsCreatedLessons(User $user)
	{
		$domain = $this->createDomain('tjlms_created_lessons', 'user_lessons_created_data');

		$query = $this->db->getQuery(true)
				->select(
						$this->db->quoteName(
								array('id', 'title', 'alias', 'short_desc','description', 'course_id', 'format', 'created_by', 'total_marks', 'passing_marks')
						)
					)
				->from($this->db->quoteName('#__tjlms_lessons'))
				->where($this->db->quoteName('created_by') . '=' . $user->id);

		$lessons = $this->db->setQuery($query)->loadAssocList();

		if (!empty($lessons))
		{
			foreach ($lessons as $lesson)
			{
				$domain->addItem($this->createItemFromArray($lesson, $lesson['id']));
			}
		}

		return $domain;
	}

	/**
	 * Create the domain for the TJLMS Enrollments of user
	 *
	 * @param   JTableUser  $user  The JTableUser object to process
	 *
	 * @return  PrivacyExportDomain
	 *
	 * @since  1.3.1
	 */
	private function createTjlmsEnrolments(User $user)
	{
		$domain = $this->createDomain('tjlms_enrollment_details', 'user_enrollments_data');

		$query = $this->db->getQuery(true)
				->select($this->db->quoteName(array('id', 'user_id','course_id','enrolled_on_time','end_time','enrolled_by')))
				->from($this->db->quoteName('#__tjlms_enrolled_users'))
				->where($this->db->quoteName('user_id') . '=' . $user->id);

		$enrollDetails = $this->db->setQuery($query)->loadAssocList();

		if (!empty($enrollDetails))
		{
			foreach ($enrollDetails as $enroll)
			{
				$domain->addItem($this->createItemFromArray($enroll, $enroll['id']));
			}
		}

		return $domain;
	}

	/**
	 * Create the domain for the TJLMS Orders by user
	 *
	 * @param   JTableUser  $user  The JTableUser object to process
	 *
	 * @return  PrivacyExportDomain
	 *
	 * @since   1.3.1
	 */
	private function createTjlmsOrdres(User $user)
	{
		$domain = $this->createDomain('tjlms_orders_details', 'user_orders data');

		$query = $this->db->getQuery(true)
				->select('o.*')
				->select(
						$this->db->qn(
							array('u.address_type','u.firstname','u.lastname','u.country_code','u.address','u.city','u.state_code','u.zipcode','u.phone','u.approved')
						)
					)
				->from($this->db->quoteName('#__tjlms_orders', 'o'))
				->join('INNER', $this->db->quoteName('#__tjlms_users', 'u') . ' ON ' . $this->db->quoteName('u.order_id') . ' = ' . $this->db->quoteName('o.id'))
				->where($this->db->quoteName('o.user_id') . ' = ' . $user->id);

		$orders = $this->db->setQuery($query)->loadAssocList();

		if (!empty($orders))
		{
			foreach ($orders as $order)
			{
				$domain->addItem($this->createItemFromArray($order, $order['id']));
			}
		}

		return $domain;
	}

	/**
	 * Create the domain for the TJLMS Course tracking details of user
	 *
	 * @param   JTableUser  $user  The JTableUser object to process
	 *
	 * @return  PrivacyExportDomain
	 *
	 * @since  1.3.1
	 */
	private function createTjlmsCourseTracking(User $user)
	{
		$domain = $this->createDomain('tjlms_courses_tracking_details', 'user_courses_tracking_data');

		$query = $this->db->getQuery(true)
				->select('*')
				->from($this->db->quoteName('#__tjlms_course_track', 'ct'))
				->where($this->db->quoteName('ct.user_id') . ' = ' . $user->id);

		$courseTrackDetails = $this->db->setQuery($query)->loadAssocList();

		if (!empty($courseTrackDetails))
		{
			foreach ($courseTrackDetails as $courseTrack)
			{
				$domain->addItem($this->createItemFromArray($courseTrack, $courseTrack['id']));
			}
		}

		return $domain;
	}

	/**
	 * Create the domain for the TJLMS Lessons tracking details of user
	 *
	 * @param   JTableUser  $user  The JTableUser object to process
	 *
	 * @return  PrivacyExportDomain
	 *
	 * @since  1.3.1
	 */
	private function createTjlmsLessonTracking(User $user)
	{
		$domain = $this->createDomain('tjlms_lessons_tracking_details', 'user_lessons_tracking_data');

		$query = $this->db->getQuery(true)
				->select('*')
				->from($this->db->quoteName('#__tjlms_lesson_track', 'lt'))
				->where($this->db->quoteName('lt.user_id') . ' = ' . $user->id);

		$lessonTrackDetails = $this->db->setQuery($query)->loadAssocList();

		if (!empty($lessonTrackDetails))
		{
			foreach ($lessonTrackDetails as $lessonTrack)
			{
				$domain->addItem($this->createItemFromArray($lessonTrack, $lessonTrack['id']));
			}
		}

		return $domain;
	}

	/**
	 * Performs validation to determine if the data associated with a remove information request can be processed
	 *
	 * This event will not allow a super user account to be removed
	 *
	 * @param   PrivacyTableRequest  $request  The request record being processed
	 * @param   JUser                $user     The user account associated with this request if available
	 *
	 * @return  PrivacyRemovalStatus
	 *
	 * @since   1.3.1
	 */
	public function onPrivacyCanRemoveData(PrivacyTableRequest $request, User $user = null)
	{
		$status = new PrivacyRemovalStatus;

		if (!$user->id)
		{
			return $status;
		}

		$db = $this->db;

		// 1. Restrict user deletion if there are any course created by the user
		$query = $db->getQuery(true);
		$query->select($db->quoteName('id'))
				->from($db->quoteName('#__tjlms_courses'))
				->where($db->quoteName('created_by') . '=' . $db->quote($user->id));
		$db->setQuery($query);
		$courses = $db->loadColumn();

		if (!empty($courses))
		{
			$status->canRemove = false;
			$coursesList = 'ID: ' . implode(', ', $courses);
			$status->reason    = Text::sprintf('PLG_PRIVACY_TJLMS_ERROR_USER_COURSES', $coursesList);

			return $status;
		}

		// 2. Restrict user deletion if there are any lesson created by the user
		$query = $db->getQuery(true);
		$query->select($db->quoteName('id'))
				->from($db->quoteName('#__tjlms_lessons'))
				->where($db->quoteName('created_by') . '=' . $db->quote($user->id));
		$db->setQuery($query);
		$lessons = $db->loadColumn();

		if (!empty($lessons))
		{
			$status->canRemove = false;
			$lessonList = 'ID: ' . implode(', ', $lessons);
			$status->reason    = Text::sprintf('PLG_PRIVACY_TJLMS_ERROR_USER_LESSONS', $lessonList);

			return $status;
		}

		// 3. Restrict user deletion if there are any certificate created by the user
		$query = $db->getQuery(true);
		$query->select($db->quoteName('id'))
				->from($db->quoteName('#__tjlms_certificate_template'))
				->where($db->quoteName('created_by') . ' = ' . $db->quote($user->id) . ' OR ' . $db->quoteName('modified_by') . ' = ' . $db->quote($user->id));
		$db->setQuery($query);
		$certificates = $db->loadColumn();

		if (!empty($certificates))
		{
			$status->canRemove = false;
			$certificateList = 'ID: ' . implode(', ', $certificates);
			$status->reason    = Text::sprintf('PLG_PRIVACY_TJLMS_ERROR_USER_CERTIFICATE', $certificateList);

			return $status;
		}

		// 4. Restrict user deletion if there are any coupons created by the user
		$query = $db->getQuery(true);
		$query->select($db->quoteName('id'))
				->from($db->quoteName('#__tjlms_coupons'))
				->where($db->quoteName('created_by') . '=' . $db->quote($user->id));
		$db->setQuery($query);
		$coupens = $db->loadColumn();

		if (!empty($coupens))
		{
			$status->canRemove = false;
			$coupenList = 'ID: ' . implode(', ', $coupens);
			$status->reason    = Text::sprintf('PLG_PRIVACY_TJLMS_ERROR_USER_COUPEN', $coupenList);

			return $status;
		}

		// 5. Restrict user deletion if there are any questions created by the user
		$query = $db->getQuery(true);
		$query->select($db->quoteName('id'))
				->from($db->quoteName('#__tmt_questions'))
				->where($db->quoteName('created_by') . '=' . $db->quote($user->id));
		$db->setQuery($query);
		$questions = $db->loadColumn();

		if (!empty($questions))
		{
			$status->canRemove = false;
			$questionList = 'ID: ' . implode(', ', $questions);
			$status->reason    = Text::sprintf('PLG_PRIVACY_TJLMS_ERROR_USER_QUESTIONS', $questionList);

			return $status;
		}

		// 6. Restrict user deletion if there are any tests/quiz created by the user
		$query = $db->getQuery(true);
		$query->select($db->quoteName('id'))
				->from($db->quoteName('#__tmt_tests'))
				->where($db->quoteName('created_by') . ' = ' . $db->quote($user->id));
		$db->setQuery($query);
		$tests = $db->loadColumn();

		if (!empty($tests))
		{
			$status->canRemove = false;
			$testList = 'ID: ' . implode(', ', $tests);
			$status->reason    = Text::sprintf('PLG_PRIVACY_TJLMS_ERROR_USER_TEST', $testList);

			return $status;
		}

		// 7. Restrict user deletion if there are any assessment review by the user
		$query = $db->getQuery(true);
		$query->select($db->quoteName('id'))
				->from($db->quoteName('#__tjlms_assessment_reviews'))
				->where($db->quoteName('reviewer_id') . ' = ' . $db->quote($user->id) . ' OR ' . $db->quoteName('modified_by') . ' = ' . $db->quote($user->id));
		$db->setQuery($query);
		$reviews = $db->loadColumn();

		if (!empty($reviews))
		{
			$status->canRemove = false;
			$reviewList = 'ID: ' . implode(', ', $reviews);
			$status->reason    = Text::sprintf('PLG_PRIVACY_TJLMS_ERROR_USER_ASSESSMENT_REVIEWS', $reviewList);

			return $status;
		}

		// 8. Restrict user deletion if there are any assessment review by the user
		$query = $db->getQuery(true);
		$query->select($db->quoteName('id'))
				->from($db->quoteName('#__tjlms_lesson_assessment_ratings'))
				->where($db->quoteName('reviewer_id') . ' = ' . $db->quote($user->id));
		$db->setQuery($query);
		$ratings = $db->loadColumn();

		if (!empty($ratings))
		{
			$status->canRemove = false;
			$ratingList = 'ID: ' . implode(', ', $ratings);
			$status->reason    = Text::sprintf('PLG_PRIVACY_TJLMS_ERROR_USER_RATINGS', $ratingList);

			return $status;
		}

		// 9. Restrict user deletion if there are any assessment review by the user
		$query = $db->getQuery(true);
		$query->select($db->quoteName('id'))
				->from($db->quoteName('#__tmt_tests_reviewers'))
				->where($db->quoteName('user_id') . ' = ' . $db->quote($user->id));
		$db->setQuery($query);
		$testreviews = $db->loadColumn();

		if (!empty($testreviews))
		{
			$status->canRemove = false;
			$testreviewList = 'ID: ' . implode(', ', $testreviews);
			$status->reason    = Text::sprintf('PLG_PRIVACY_TJLMS_ERROR_USER_TEST_REVIEWS', $testreviewList);

			return $status;
		}

		// 10. Restrict user deletion if there are any assessment review by the user
		$query = $db->getQuery(true);
		$query->select($db->quoteName('id'))
				->from($db->quoteName('#__tjlms_orders'))
				->where($db->quoteName('user_id') . ' = ' . $db->quote($user->id));
		$db->setQuery($query);
		$orders = $db->loadColumn();

		if (!empty($orders))
		{
			$status->canRemove = false;
			$orderList = 'ID: ' . implode(', ', $orders);
			$status->reason    = Text::sprintf('PLG_PRIVACY_TJLMS_ERROR_USER_ORDERS', $orderList);

			return $status;
		}
	}

	/**
	 * Removes the data associated with a remove information request
	 *
	 * This event will pseudoanonymise the user account
	 *
	 * @param   PrivacyTableRequest  $request  The request record being processed
	 * @param   JUser                $user     The user account associated with this request if available
	 *
	 * @return  void
	 *
	 * @since   1.3.1
	 */
	public function onPrivacyRemoveData(PrivacyTableRequest $request, User $user = null)
	{
		// This plugin only processes data for registered user accounts
		if (!$user)
		{
			return;
		}

		// If there was an error loading the user do nothing here
		if ($user->guest)
		{
			return;
		}

		$db = $this->db;

		// 1. Delete data from #__tjlms_activities
		$query = $db->getQuery(true)
			->delete($db->quoteName('#__tjlms_activities'))
			->where($db->quoteName('actor_id') . ' = ' . $db->quote($user->id));

		$db->setQuery($query);
		$db->execute();

		// 2. Delete data from #__tjlms_certificate
		$query = $db->getQuery(true)
			->delete($db->quoteName('#__tjlms_certificate'))
			->where($db->quoteName('user_id') . ' = ' . $db->quote($user->id));

		$db->setQuery($query);
		$db->execute();

		// 3. Delete data from #__tjlms_reports_queries
		$query = $db->getQuery(true)
			->delete($db->quoteName('#__tjlms_reports_queries'))
			->where($db->quoteName('creator_id') . ' = ' . $db->quote($user->id));

		$db->setQuery($query);
		$db->execute();

		// 4. Delete data from #__tjlms_todos_reminder
		$query = $db->getQuery(true)
			->delete($db->quoteName('#__tjlms_todos_reminder'))
			->where($db->quoteName('user_id') . ' = ' . $db->quote($user->id));

		$db->setQuery($query);
		$db->execute();

		// 5. Delete data from #__tjlms_scorm_scoes_track
		$query = $db->getQuery(true)
			->delete($db->quoteName('#__tjlms_scorm_scoes_track'))
			->where($db->quoteName('userid') . ' = ' . $db->quote($user->id));

		$db->setQuery($query);
		$db->execute();

		// 6. Delete data from #__tmt_tests_answers
		$query = $db->getQuery(true)
			->delete($db->quoteName('#__tmt_tests_answers'))
			->where($db->quoteName('user_id') . ' = ' . $db->quote($user->id));

		$db->setQuery($query);
		$db->execute();

		// 7. Delete data from #__tmt_tests_attendee
		$query = $db->getQuery(true)
			->delete($db->quoteName('#__tmt_tests_attendees'))
			->where($db->quoteName('user_id') . ' = ' . $db->quote($user->id));

		$db->setQuery($query);
		$db->execute();

		// 8. Delete data from #__tjlms_lesson_track
		$query = $db->getQuery(true)
			->delete($db->quoteName('#__tjlms_lesson_track'))
			->where($db->quoteName('user_id') . ' = ' . $db->quote($user->id));

		$db->setQuery($query);
		$db->execute();

		// 9. Delete data from #__tjlms_course_track
		$query = $db->getQuery(true)
			->delete($db->quoteName('#__tjlms_course_track'))
			->where($db->quoteName('user_id') . ' = ' . $db->quote($user->id));

		$db->setQuery($query);
		$db->execute();

		// 10. Delete data from #__tjlms_enrolled_users
		$query = $db->getQuery(true)
			->delete($db->quoteName('#__tjlms_enrolled_users'))
			->where($db->quoteName('user_id') . ' = ' . $db->quote($user->id));

		$db->setQuery($query);
		$db->execute();

		// 11. Delete data from #__tjlms_file_download_stats
		$query = $db->getQuery(true)
			->delete($db->quoteName('#__tjlms_file_download_stats'))
			->where($db->quoteName('user_id') . ' = ' . $db->quote($user->id));

		$db->setQuery($query);
		$db->execute();
	}
}
