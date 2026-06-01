<?php
/**
 * @package     Shika
 * @subpackage  com_tmt
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;
use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\Exception\ExceptionHandler;

use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Language\Text;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\MVC\Model\FormModel;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 * Tmt model.
 *
 * @since  1.0
 */
class TmtModelTest extends FormModel
{
	protected $item = null;

	private $testId = null;

	private $lessonTrackId = null;

	protected $questionMediaClient = 'tjlms.question';

	protected $answerMediaClient = 'tjlms.answer';

	/**
	 * Method to validate test attempt.
	 *
	 * @param   int  $lessonTrackId  id of lesson track
	 *
	 * @return  boolean  true/false
	 *
	 * @since  1.0
	 *
	 * @deprecated  1.4.0  This function will be removed and no replacement will be provided
	 */
	public function validateStep($lessonTrackId)
	{
		$db = $this->getDbo();
		$app = Factory::getApplication();

		// Get attempt data;
		$query = $db->getQuery(true);
		$query->select('attempt_status');
		$query->from('#__tmt_tests_attendees');
		$query->where('invite_id =' . (int) $lessonTrackId);
		$db->setQuery($query);
		$attempt_status = $db->loadResult();

		if ($attempt_status == 0)
		{
			return true;
		}
		elseif ($attempt_status == 1)
		{
			$app->enqueueMessage(Text::_('COM_TMT_TEST_ERROR_SAVING_DATA_MSG_TEST_SUBMITTED'), 'error');

			return false;
		}
		elseif ($attempt_status == 2)
		{
			$app->enqueueMessage(Text::_('COM_TMT_TEST_ERROR_SAVING_DATA_MSG_TEST_REJECTED'), 'error');

			return false;
		}

		return false;
	}

	/**
	 * Method to save test data in database.
	 *
	 * @param   mixed  $attemptData  Array of userid, lessontrack id, testid
	 *
	 * @return  boolean  true/false
	 *
	 * @since  1.0
	 */
	public function submitTest($attemptData)
	{
		$userId        = $attemptData['userId'];
		$lessonTrackId = $attemptData['ltId'];
		$testId        = $attemptData['testId'];

		$test = TMT::Test($testId);

		$markStatus = $finalMarks = 0;
		$testStatus = $ltStatus   = "AP";

		if ($test->gradingtype == 'feedback')
		{
			$testStatus = "C";
			$ltStatus   = "completed";
			$markStatus = 1;
		}

		// Check if Quiz has only MCQ, MRQs and objtext.
		$isObjective = $this->checkIfObjective($testId);

		if ($test->gradingtype == 'quiz' && $isObjective)
		{
			$markStatus = 1;
			$finalMarks = $this->getTotalScore($lessonTrackId);

			if ($test->passing_marks <= $finalMarks)
			{
				$testStatus = "P";
				$ltStatus   = "passed";
			}
			else
			{
				$testStatus = "F";
				$ltStatus   = "failed";
			}
		}

		$lessonDetails  = $this->lessonDetailsFromLessonTrack($lessonTrackId);
		$lessonObj      = $lessonDetails->lesson;
		$lessonTrackObj = $lessonDetails->lessonTrack;

		// Update lesson track for score and status
		$trackingHelper          = new comtjlmstrackingHelper;
		$trackObj                = new stdClass;
		$trackObj->attempt       = $lessonTrackObj->attempt;
		$trackObj->score         = $finalMarks;
		$trackObj->lesson_status = $ltStatus;
		$lessonTrackId           = $trackingHelper->storeTrack($lessonTrackObj->lesson_id, $userId, $trackObj);

		// Update attendees for score and status
		$taObject                         = $this->getTable('testattendees');
		$taObject->load(array('invite_id' => $lessonTrackId));
		$taObject->score                  = $finalMarks;
		$taObject->review_status          = $markStatus;
		$taObject->result_status          = $testStatus;

		$taObject->store();

		// Send thank you email to candidate.
		if ($ltStatus != 'AP')
		{
			TMT::Email()->sendThankYouEmailToCandidate($lessonTrackId, $testId, $lessonObj->course_id);
		}

		if ((!$isObjective && $lessonObj->format != 'feedback') || ($lessonObj->format == 'exercise'))
		{
			// Send email to all hiring managers associated with this test.
			TMT::Email()->sendNewPaperPendingReviewEmail($lessonTrackId, $testId);
		}

		return true;
	}

	/**
	 * Check if the given test is objective
	 *
	 * @param   INT  $testId  Id of the test
	 *
	 * @return INTEGER
	 *
	 * @since 1.3
	 */
	public function checkIfObjective($testId)
	{
		$query = $this->_db->getQuery(true);
		$query->select($this->_db->quoteName('tq.id'))
			->from($this->_db->quoteName('#__tmt_tests_questions', 'tq'))
			->join("INNER", $this->_db->quoteName('#__tmt_questions', 'q') . " ON q.id = tq.question_id")
			->where("q.type NOT IN ('radio', 'checkbox', 'objtext')")
			->where("tq.test_id =" . $testId);
		$this->_db->setQuery($query);
		$total = $this->_db->loadColumn();

		return (count($total) > 0) ? 0 : 1;
	}

	/**
	 * Method to sync Time with database.
	 *
	 * @param   INT  $lessonTrackId  post data
	 * @param   INT  $testId         test id
	 * @param   INT  $timeSpent      timeSpent
	 *
	 * @return  INTEGER
	 *
	 * @since  1.0
	 */
	public function updateTimeSpent($lessonTrackId, $testId, $timeSpent)
	{
		$test = TMT::Test($testId);

		// Take total time for test
		$testTime = ($test->time_duration) * 60;
		$userId   = Factory::getUser()->id;

		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjlms/tables');
		$lessonTrack = Table::getInstance('Lessontrack', 'TjlmsTable', array('dbo', $this->_db));
		$lessonTrack->load($lessonTrackId);

		if (!$lessonTrack->id)
		{
			return 0;
		}

		$trackingHelper = new comtjlmstrackingHelper;
		$trackData = $trackingHelper->istrackpresent($lessonTrack->lesson_id, $lessonTrack->attempt, $userId);

		// Prepare data entry
		$obj            = new stdClass;
		$obj->invite_id = $lessonTrackId;

		// If time duration set for Quiz, minus the remaining time from total time duration
		if ($trackData)
		{
			$total_time      = $trackData->time_spent + $timeSpent;
			$obj->time_taken = (int) $total_time;
		}

		if (!$this->_db->updateObject('#__tmt_tests_attendees', $obj, 'invite_id'))
		{
			echo $this->_db->stderr();

			return 0;
		}

		$trackObj             = new stdClass;
		$trackObj->attempt    = $lessonTrack->attempt;
		$trackObj->time_spent = $timeSpent;

		$trackingid = $trackingHelper->storeTrack($lessonTrack->lesson_id, $userId, $trackObj);

		return $trackingid;
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @return void
	 *
	 * @since  1.0
	 */
	protected function populateState()
	{
		$app           = Factory::getApplication();
		$input         = $app->input;
		$id            = $input->get('id');
		$page          = $input->getInt('page', '1');
		$lessonTrackId = $input->getInt('invite_id', '0');

		// Load state from the request userState on edit or from the passed variable on default
		if ($input->get('view') == 'test')
		{
			$app->setUserState('test.id', $id);
		}

		$this->setState('test.id', $id);
		$this->setState('test.page', $page);
		$this->setState('test.lessonTrackId', $lessonTrackId);
		$app->setUserState('test.' . $id, $this->getTestPageQuestions($id));

		// Deprecated code. It will be removed in version 1.4.0
		if (empty($app->getUserState('test.lessontrack.' . $lessonTrackId, array())))
		{
			$lessonId = $this->getLessonIdFromLT($lessonTrackId);
			$app->setUserState('test.lessontrack.' . $lessonTrackId, array('lesson_id' => $lessonId));
		}
	}

	/**
	 * function to get the total questions and Questions per page for a test
	 *
	 * @param   INT  $id  Id of the test
	 *
	 * @return  Array
	 */
	public function getTestPageQuestions($id)
	{
		/* With this we will be able to sort the test questions in pages
		 * totalQuestions = no of questions in a test
		 * questionsPerPage will be an array
		 * If per page 5 questions to be shown and section1 has 8 questions and, section 2 has 6 questions
		 * So total pages will be 4
		 * [1] => 5
		 * [2] => 3
		 * [3] => 5
		 * [4] => 1
		 * */
		$testState = array();

		if ($id)
		{
			$test             = TMT::Test($id);
			$allSections      = $this->getSectionsByTest($id, 0);
			$totalPages       = $totalQuestions = 0;
			$questionsPerPage = $sectionsPerPage = array();

			$i = $j = 1;

			// If we are not showing all questions on same page, and if pagination count is set, set it as limit
			// Default limit is 5 to avoid divide by 0 error
			$limit = ($test->pagination_limit) ? $test->pagination_limit : 5;

			foreach ($allSections as $section)
			{
				// Get total questions
				$query = $this->_db->getQuery(true);
				$query->select('COUNT(tq.id) AS count');
				$query->from('#__tmt_tests_questions AS tq');
				$query->where('tq.test_id =' . (int) $id);
				$query->where('tq.section_id =' . (int) $section->id);

				$this->_db->setQuery($query);
				$sectionQuestions = $this->_db->loadResult();

				$totalQuestions += $sectionQuestions;

				$qclimitStart = 0;

				if ($test->show_all_questions == 0)
				{
					$sectionwisepages = ceil($sectionQuestions / $limit);

					while ($sectionQuestions > $limit)
					{
						$tempSection               = clone $section;
						$tempSection->qctofetch    = $limit;
						$tempSection->qclimitStart = $qclimitStart;
						$sectionsPerPage[$j++][]   = $tempSection;
						$questionsPerPage[$i++]    = $limit;
						$qclimitStart              = $qclimitStart + $limit;
						$sectionQuestions          = $sectionQuestions - $limit;
					}

					$tempSection               = clone $section;
					$tempSection->qctofetch    = $limit;
					$tempSection->qclimitStart = $qclimitStart;
					$sectionsPerPage[$j++][]   = $tempSection;
					$questionsPerPage[$i++]    = $sectionQuestions;
					$totalPages                = $totalPages + $sectionwisepages;
				}
				else
				{
					$section->qctofetch    = $sectionQuestions;
					$section->qclimitStart = $qclimitStart;
					$sectionsPerPage[$j][] = $section;
					$questionsPerPage[$i]  = $totalQuestions;
				}
			}

			$testState = array("totalQuestions" => $totalQuestions, "questionsPerPage" => $questionsPerPage, "sectionsPerPage" => $sectionsPerPage);
		}

		return $testState;
	}

	/**
	 * Method to get an ojbect.
	 *
	 * @param   integer  $id  The id of the object to get.
	 *
	 * @return  mixed  Object on success, false on failure.
	 */
	public function getData($id = null)
	{
		if (empty($id))
		{
			$id = $this->getState('test.id');
		}

		$this->item = TMT::Test($id);

		if (!$this->item->state)
		{
			return false;
		}

		$userId         = Factory::getUser()->id;
		$lessonTrackId  = $this->getState('test.lessonTrackId');
		$lessonDetails  = $this->lessonDetailsFromLessonTrack($lessonTrackId);
		$lessonObj      = $lessonDetails->lesson;
		$lessonTrackObj = $lessonDetails->lessonTrack;

		if (!empty($lessonObj->id))
		{
			$this->item->resume = $lessonObj->resume;
		}

		$this->item->currentPage = $this->getState("test.page");
		$this->item->invite_id   = $lessonTrackId;
		$this->item->timeSpent   = $lessonTrackObj->time_spent;

		/* Get number of questions user has attempted. answer should be non blank or should not with -.
		* 4th param tells if we want count only*/
		$this->item->attemptedCount = $this->getAttemptedQuestions($this->item->id, $userId, $lessonTrackId, 1);

		return $this->item;
	}

	/**
	 * Function used to get the data requried on test page and answersheet
	 *
	 * @param   MIXED  $lessonTrackId  lesson track id
	 * @param   INT    $testId         testId
	 * @param   INT    $pageNo         pageNo
	 *
	 * @return  MIXED  $item
	 *
	 * @since  1.3
	 */
	public function getuserTestSectionsQuestions($lessonTrackId, $testId, $pageNo = 1)
	{
		$app       = Factory::getApplication();
		$thisState = $app->getUserState("test." . $testId);
		$test      = TMT::Test($testId);

		// Check if test exists and is published
		if (!$test->id || !$test->state)
		{
			return false;
		}

		$return         = array();
		$return['test'] = $test;

		if (!empty($thisState['sectionsPerPage'][$pageNo]))
		{
			$sections = $thisState['sectionsPerPage'][$pageNo];

			foreach ($sections as $ind => $section)
			{
				$testData = $this->getTestData(
					$lessonTrackId, $test->id, Factory::getUser()->id,
					$section->id, $section->qclimitStart, $section->qctofetch
				);

				$section->questions = $testData->questions;
				$sections[$ind]     = $section;
			}

			$return['sections'] = $sections;
		}

		return $return;
	}

	/**
	 * Function used to get the data requried on test page and answersheet
	 *
	 * @param   INT  $lessonTrackId  lesson track id
	 * @param   INT  $userId         userId
	 *
	 * @return  MIXED  $item
	 *
	 * @since  1.3
	 */
	private function checkIfAttemptIsRegistered($lessonTrackId, $userId)
	{
		static $registeredAttempt = array();

		$hash = md5($lessonTrackId . $userId);

		if (isset($registeredAttempt[$hash]))
		{
			return $registeredAttempt[$hash];
		}

		// Get time spent
		$query = $this->_db->getQuery(true);

		$query->select("TIME_TO_SEC(lt.time_spent) as time_spent, lt.lesson_status");
		$query->from("#__tmt_tests_attendees AS ta");
		$query->join("INNER", $this->_db->quoteName("#__tjlms_lesson_track", "lt") . " ON ta.invite_id=lt.id");
		$query->where("ta.invite_id =" . (int) $lessonTrackId);
		$query->where("ta.user_id =" . (int) $userId);
		$this->_db->setQuery($query);

		return $registeredAttempt[$hash] = $this->_db->loadObject();
	}

	/**
	 * Function used to get Test related data
	 *
	 * @param   INT  $lessonTrackId  lesson track id
	 * @param   INT  $testId         Test id
	 * @param   INT  $userId         User id
	 * @param   INT  $sectionId      Section id
	 * @param   INT  $limitStart     Limit start
	 * @param   INT  $limit          Number of records to fetch
	 *
	 * @return  object
	 *
	 * @since  1.3.31
	 */
	public function getTestData($lessonTrackId, $testId, $userId = 0, $sectionId = 0, $limitStart = 0, $limit = 0)
	{
		$testData = new stdClass;

		if (!$userId)
		{
			$userId = Factory::getUser()->id;
		}

		$attemptData = $this->checkIfAttemptIsRegistered($lessonTrackId, $userId);

		if (!empty($attemptData))
		{
			BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjlms/models');

			// Get test questions
			$query = $this->_db->getQuery(true);

			// Columns from __tmt_tests_answers(ta)
			$query->select(
				'ta.question_id, ta.test_id, ta.id as userAnswerId, ta.answer as userAnswer, ta.anss_order,
				ta.marks as userMarks, ta.flagged'
			);

			// Columns from __tmt_questions(q)
			$query->select('q.title, q.description, q.type, q.category_id, q.level, q.marks, q.params, q.gradingtype');

			// Columns from __tmt_tests_questions(ttq)
			$query->select('ttq.section_id, ttq.is_compulsory');

			// Columns from __categories(c)
			$query->select('c.title AS category');

			// Fetch question media elements
			$query->select('mediaQr.media_id, mediaQr.client_id, mediaQr.source, mediaQr.original_filename, mediaQr.type AS media_type');

			$query->from('#__tmt_tests_answers AS ta');
			$query->join('INNER', '#__tmt_questions AS q ON q.id = ta.question_id');
			$query->join('INNER', '#__tmt_tests_questions AS ttq ON ttq.question_id = q.id');
			$query->join('INNER', '#__categories AS c ON c.id = q.category_id');

			// Question Media query join - start
			$questionMediaQuery = $this->_db->getQuery(true);
			$questionMediaQuery->select($this->_db->qn(array('xref.id', 'xref.media_id', 'xref.client_id', 'mf.source', 'mf.original_filename', 'mf.type')));
			$questionMediaQuery->from($this->_db->qn('#__tj_media_files_xref', 'xref'));
			$questionMediaQuery->join('INNER', $this->_db->qn('#__tj_media_files', 'mf')
					. ' ON (' . $this->_db->qn('xref.media_id') . ' = ' . $this->_db->qn('mf.id') . ')');
			$questionMediaQuery->where($this->_db->qn('xref.client') . '=' . $this->_db->quote($this->questionMediaClient));

			// Join here
			$query->leftJoin('(' . $questionMediaQuery . ') AS mediaQr
				ON ( ' . $this->_db->qn('ta.question_id') . ' = ' . $this->_db->qn('mediaQr.client_id') . ')');

			// Question Media query join - end

			$query->where('ttq.test_id  =' . (int) $testId);
			$query->where('ta.user_id   =' . $userId);
			$query->where('ta.invite_id =' . (int) $lessonTrackId);

			if ($sectionId)
			{
				$query->where('ttq.section_id =' . (int) $sectionId);
			}

			// Ordering
			$query->order($this->_db->quoteName('ta.id') . ' ASC');

			$this->_db->setQuery($query, $limitStart, $limit);

			$questions = $this->_db->loadObjectList();

			// Get answers options for all quesions
			foreach ($questions as $q)
			{
				$anssOrderData = $answerOrders = array();

				$query = $this->_db->getQuery(true);
				$query->select('a.answer, a.id, a.is_correct, a.comments, a.marks');

				// Fetch answer media elements
				$query->select('mediaQr.media_id, mediaQr.client_id, mediaQr.source, mediaQr.original_filename, mediaQr.type AS media_type');
				$query->from('#__tmt_answers AS a');

				// Answer Media query join - start
				$answerMediaQuery = $this->_db->getQuery(true);
				$answerMediaQuery->select($this->_db->qn(array('xref.id', 'xref.media_id', 'xref.client_id', 'mf.source', 'mf.original_filename', 'mf.type')));
				$answerMediaQuery->from($this->_db->qn('#__tj_media_files_xref', 'xref'));
				$answerMediaQuery->join('INNER', $this->_db->qn('#__tj_media_files', 'mf')
						. ' ON (' . $this->_db->qn('xref.media_id') . ' = ' . $this->_db->qn('mf.id') . ')');

				$answerMediaQuery->where($this->_db->qn('xref.client') . '=' . $this->_db->quote($this->answerMediaClient));

				// Join here
				$query->leftJoin('(' . $answerMediaQuery . ') AS mediaQr
					ON ( ' . $this->_db->qn('a.id') . ' = ' . $this->_db->qn('mediaQr.client_id') . ')');

				// Answer Media query join - end

				$query->where('a.question_id =' . (int) $q->question_id);

				// Ordering
				$query->order('a.order', 'ASC');

				$this->_db->setQuery($query);
				$answersData = $this->_db->loadObjectList();

				$tempanswerOrders = json_decode($q->anss_order);

				foreach ($tempanswerOrders as $tempans)
				{
					$answerOrders[$tempans[0]] = $tempans[1];
				}

				// Add 0 as default value. This will get incremented in case of number of correct ans of checkbox type question
				$q->correct_answer = 0;

				// Get all answer Ids
				$answerIds = array_map(
					function($e)
					{
						return is_object($e) ? $e->id : $e['id'];
					},
					$answersData
				);

				foreach ($answerOrders as $key => $ans)
				{
					$ansKey          = array_search($ans, $answerIds);
					$anssOrderData[] = $answersData[$ansKey];

					// Increment 'correct_answer' key in question obj in case of `checkbox` type question
					if ($q->type == 'checkbox' && $answersData[$ansKey]->is_correct == 1)
					{
						$q->correct_answer++;
					}
				}

				$q->answers = $anssOrderData;

				if (!empty($q->userAnswer))
				{
					switch ($q->type)
					{
						case "file_upload":
							$mediaIds = json_decode($q->userAnswer);
							$temp     = array();
							$i        = 0;

							foreach ($mediaIds as $mid)
							{
								$mediaModel = BaseDatabaseModel::getInstance('Media', 'TjlmsModel', array('ignore_request' => true));
								$res = $mediaModel->getItem($mid);

								if ($res->id)
								{
									$temp[$i]           = new stdClass;
									$temp[$i]->media_id = $res->id;
									$temp[$i]->source   = $res->source;

									// Get media URL
									$mediaDownloadUrl    = $mediaModel->getMediaUrl($res);
									$mediaTimelyUrl      = $mediaModel->getMediaUrl($res, true);
									$temp[$i]->path      = '#';
									$temp[$i]->timelyUrl = '#';

									if (!empty($mediaDownloadUrl))
									{
										$temp[$i]->path = $mediaDownloadUrl;
									}

									if (!empty($mediaTimelyUrl))
									{
										$temp[$i]->mediaTimelyUrl = $mediaTimelyUrl;
									}

									$temp[$i++]->org_filename = $res->org_filename;
								}
							}
						break;

						case "radio":
						case "checkbox":
							$temp = json_decode($q->userAnswer);
						break;

						case "text":
						case "objtext":
						case "textarea":
						case "rating":
							$temp = $q->userAnswer;
						break;

						default:
							$temp = $q->userAnswer;
					}

					$q->userAnswer = $temp;

					if (in_array($q->type, array('checkbox', 'radio')))
					{
						foreach ($q->answers as $ans)
						{
							$q->correct = 0;

							if ($ans->is_correct == 1)
							{
								if (!empty($q->userAnswer) && in_array($ans->id, $q->userAnswer))
								{
									$q->correct = 1;
									break;
								}
								else
								{
									$q->correct = 0;
								}
							}
						}
					}
					elseif (in_array($q->type, array('file_upload', 'rating', 'text', 'textarea', 'objtext')))
					{
						$q->correct = 0;

						if ($q->userMarks > 0)
						{
							$q->correct = 1;
						}
					}
				}
			}

			/* Get number of questions user has attempted.
			 * Answer should be non blank or should not with -.
			 * 4th param tells if we want count only
			* */
			$attemptedCount = $this->getAttemptedQuestions($testId, $userId, $lessonTrackId, 1);

			$testData->questions       = $questions;
			$testData->isObjective     = $this->checkIfObjective($testId);
			$testData->attempted_count = $attemptedCount;
			$testData->invite_id       = $lessonTrackId;
			$testData->time_spent      = $attemptData->time_spent;
			$testData->attempt_status  = $attemptData->lesson_status;
		}

		return $testData;
	}

	/**
	 * Function used to get the data requried on test page and answersheet
	 *
	 * @param   MIXED  &$section       Section object
	 * @param   INT    $lessonTrackId  lesson track id
	 * @param   INT    $test           test object
	 *
	 * @return  MIXED  $item
	 *
	 * @since  1.3
	 *
	 * @deprecated  1.4.0  This function will be removed and replacement is getTestData()
	 */
	private function getUserSectionAttemptData(&$section, $lessonTrackId, $test)
	{
		$userId      = Factory::getUser()->id;
		$testId      = $test->id;
		$attemptData = $this->checkIfAttemptIsRegistered($lessonTrackId, $userId);

		if (!empty($attemptData))
		{
			BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjlms/models');

			$limit      = $section->qctofetch;
			$limitstart = $section->qclimitStart;

			// Get test questions
			$query = $this->_db->getQuery(true);

			// Columns from __tmt_tests_answers(ta)
			$query->select(
				'ta.question_id, ta.test_id, ta.id as userAnswerId, ta.answer as userAnswer, ta.anss_order,
				ta.marks as userMarks, ta.flagged'
			);

			// Columns from __tmt_questions(q)
			$query->select('q.title, q.description, q.type, q.category_id, q.level, q.marks, q.params, q.gradingtype');

			// Columns from __tmt_tests_questions(ttq)
			$query->select('ttq.section_id, ttq.is_compulsory');

			// Columns from __categories(c)
			$query->select('c.title AS category');

			// Fetch question media elements
			$query->select('mediaQr.media_id, mediaQr.client_id, mediaQr.source, mediaQr.original_filename, mediaQr.type AS media_type');

			$query->from('#__tmt_tests_answers AS ta');
			$query->join('INNER', '#__tmt_questions AS q ON q.id = ta.question_id');
			$query->join('INNER', '#__tmt_tests_questions AS ttq ON ttq.question_id = q.id');
			$query->join('INNER', '#__categories AS c ON c.id = q.category_id');

			// Question Media query join - start
			$questionMediaQuery = $this->_db->getQuery(true);
			$questionMediaQuery->select($this->_db->qn(array('xref.id', 'xref.media_id', 'xref.client_id', 'mf.source', 'mf.original_filename', 'mf.type')));
			$questionMediaQuery->from($this->_db->qn('#__tj_media_files_xref', 'xref'));
			$questionMediaQuery->join('INNER', $this->_db->qn('#__tj_media_files', 'mf')
					. ' ON (' . $this->_db->qn('xref.media_id') . ' = ' . $this->_db->qn('mf.id') . ')');

			$questionMediaQuery->where($this->_db->qn('xref.client') . '=' . $this->_db->quote($this->questionMediaClient));

			// Join here
			$query->leftJoin('(' . $questionMediaQuery . ') AS mediaQr
				ON ( ' . $this->_db->qn('ta.question_id') . ' = ' . $this->_db->qn('mediaQr.client_id') . ')');

			// Question Media query join - end

			$query->where('ttq.test_id    =' . (int) $testId);
			$query->where('ttq.section_id =' . (int) $section->id);
			$query->where('ta.user_id     =' . $userId);
			$query->where('ta.invite_id   =' . (int) $lessonTrackId);

			// Ordering
			$query->order($this->_db->quoteName('ta.id') . ' ASC');

			$this->_db->setQuery($query, $limitstart, $limit);

			$questions = $this->_db->loadObjectList();

			// Get answers options for all quesions
			foreach ($questions as $q)
			{
				$anssOrderData = $answerOrders = array();

				$query = $this->_db->getQuery(true);
				$query->select('a.answer, a.id, a.is_correct, a.comments, a.marks');

				// Fetch answer media elements
				$query->select('mediaQr.media_id, mediaQr.client_id, mediaQr.source, mediaQr.original_filename, mediaQr.type AS media_type');
				$query->from('#__tmt_answers AS a ');

				// Answer Media query join - start
				$answerMediaQuery = $this->_db->getQuery(true);
				$answerMediaQuery->select($this->_db->qn(array('xref.id', 'xref.media_id', 'xref.client_id', 'mf.source', 'mf.original_filename', 'mf.type')));
				$answerMediaQuery->from($this->_db->qn('#__tj_media_files_xref', 'xref'));
				$answerMediaQuery->join('INNER', $this->_db->qn('#__tj_media_files', 'mf')
						. ' ON (' . $this->_db->qn('xref.media_id') . ' = ' . $this->_db->qn('mf.id') . ')');

				$answerMediaQuery->where($this->_db->qn('xref.client') . '=' . $this->_db->quote($this->answerMediaClient));

				// Join here
				$query->leftJoin('(' . $answerMediaQuery . ') AS mediaQr
					ON ( ' . $this->_db->qn('a.id') . ' = ' . $this->_db->qn('mediaQr.client_id') . ')');

				// Answer Media query join - end

				$query->where('a.question_id =' . (int) $q->question_id);

				// Ordering
				$query->order('a.order', 'ASC');

				$this->_db->setQuery($query);
				$a_data = $this->_db->loadObjectList();

				$tempanswerOrders = json_decode($q->anss_order);

				foreach ($tempanswerOrders as $tempans)
				{
					$answerOrders[$tempans[0]] = $tempans[1];
				}

				// Add 0 as default value. This will get incremented in case of number of correct ans of checkbox type question
				$q->correct_answer = 0;

				foreach ($a_data as $anss)
				{
					$order           = array_search($anss->id, $answerOrders);
					$anss->order     = $order;
					$anssOrderData[] = $anss;

					// Increment correct_answer key in question obj in case of `checkbox` type question
					if ($q->type == 'checkbox' && $anss->is_correct == 1)
					{
						$q->correct_answer++;
					}
				}

				$q->answers = $anssOrderData;

				if (!empty($q->userAnswer))
				{
					switch ($q->type)
					{
						case "file_upload":
							$mediaIds = json_decode($q->userAnswer);
							$temp     = array();
							$i        = 0;

							foreach ($mediaIds as $mid)
							{
								$mediaModel = BaseDatabaseModel::getInstance('Media', 'TjlmsModel', array('ignore_request' => true));
								$res = $mediaModel->getItem($mid);

								if ($res->id)
								{
									$temp[$i]           = new stdClass;
									$temp[$i]->media_id = $res->id;
									$temp[$i]->source   = $res->source;

									// Get media URL
									$mediaDownloadUrl    = $mediaModel->getMediaUrl($res);
									$mediaTimelyUrl      = $mediaModel->getMediaUrl($res, true);
									$temp[$i]->path      = '#';
									$temp[$i]->timelyUrl = '#';

									if (!empty($mediaDownloadUrl))
									{
										$temp[$i]->path = $mediaDownloadUrl;
									}

									if (!empty($mediaTimelyUrl))
									{
										$temp[$i]->mediaTimelyUrl = $mediaTimelyUrl;
									}

									$temp[$i++]->org_filename = $res->org_filename;
								}
							}
						break;

						case "radio":
						case "checkbox":
							$temp = json_decode($q->userAnswer);
						break;

						case "text":
						case "textarea":
						case "rating":
							$temp = $q->userAnswer;
						break;

						default:
							$temp = $q->userAnswer;
					}

					$q->userAnswer = $temp;

					if (in_array($q->type, array('checkbox', 'radio')))
					{
						foreach ($q->answers as $ans)
						{
							$q->correct = 0;

							if ($ans->is_correct == 1)
							{
								if (!empty($q->userAnswer) && in_array($ans->id, $q->userAnswer))
								{
									$q->correct = 1;
									break;
								}
								else
								{
									$q->correct = 0;
								}
							}
						}
					}
					elseif (in_array($q->type, array('file_upload', 'rating', 'text', 'textarea')))
					{
						$q->correct = 0;

						if ($q->userMarks > 0)
						{
							$q->correct = 1;
						}
					}
				}
			}

			$section->questions = $questions;
		}
	}

	/**
	 * Function used to get the data requried on test page and answersheet
	 *
	 * @param   MIXED  &$item          item with test table object
	 * @param   INT    $lessonTrackId  lesson track id
	 * @param   INT    $userId         user id
	 * @param   INT    $testId         test id
	 * @param   INT    $limitStart     limitStart to get questions
	 * @param   INT    $limit          limit to get questions
	 *
	 * @return  MIXED  $item
	 *
	 * @since  1.3
	 *
	 * @deprecated  1.4.0  This function will be removed and replacement is getTestData()
	 */
	public function getUserTestAttemptData(&$item, $lessonTrackId, $userId, $testId, $limitStart = 0, $limit = 0)
	{
		$ta_data = $this->checkIfAttemptIsRegistered($lessonTrackId, $userId);

		if (!empty($ta_data))
		{
			/* Get number of questions user has attempted. answer should be non blank or should not with -.
			* 4th param tells if we want count only*/
			$attemptedCount = $this->getAttemptedQuestions($testId, $userId, $lessonTrackId, 1);

			// Get test questions
			$query = $this->_db->getQuery(true);

			$query->select(
				'ta.question_id, ta.test_id, ttq.section_id, ta.id as userAnswerId, ta.answer as userAnswer,
				 ta.anss_order, ta.marks as userMarks, ta.flagged,ttq.is_compulsory'
			);
			$query->select('q.title, q.description, q.type, q.category_id, q.level, q.marks, q.params');

			$query->select('c.title AS category ');

			// Fetch question media elements
			$query->select('mediaQr.media_id, mediaQr.client_id, mediaQr.source, mediaQr.original_filename, mediaQr.type AS media_type');

			$query->from('#__tmt_tests_answers AS ta');
			$query->join('LEFT', '#__tmt_questions AS q ON q.id = ta.question_id ');
			$query->join('LEFT', '#__tmt_tests_questions AS ttq ON ttq.question_id = q.id ');
			$query->join('LEFT', '#__categories AS c ON c.id = q.category_id ');

			// Question Media query join - start
			$questionMediaQuery = $this->_db->getQuery(true);
			$questionMediaQuery->select($this->_db->qn(array('xref.id', 'xref.media_id', 'xref.client_id', 'mf.source', 'mf.original_filename', 'mf.type')));
			$questionMediaQuery->from($this->_db->qn('#__tj_media_files_xref', 'xref'));
			$questionMediaQuery->join('INNER', $this->_db->qn('#__tj_media_files', 'mf')
					. ' ON (' . $this->_db->qn('xref.media_id') . ' = ' . $this->_db->qn('mf.id') . ')');

			$questionMediaQuery->where($this->_db->qn('xref.client') . '=' . $this->_db->quote($this->questionMediaClient));

			// Join here
			$query->leftJoin('(' . $questionMediaQuery . ') AS mediaQr
				ON ( ' . $this->_db->qn('ta.question_id') . ' = ' . $this->_db->qn('mediaQr.client_id') . ')');

			// Question Media query join - end

			$query->where('ttq.test_id =' . (int) $testId);
			$query->where('ta.user_id =' . $userId);
			$query->where('ta.invite_id =' . (int) $lessonTrackId);
			$query->order($this->_db->quoteName('ta.id') . ' ASC');

			if ($limit)
			{
				$this->_db->setQuery($query, $limitStart, $limit);
			}
			else
			{
				$this->_db->setQuery($query);
			}

			$questions = $this->_db->loadObjectList();

			// Get answers options for all quesions
			foreach ($questions as $q)
			{
				$anssOrderData = $answerOrders = array();

				$query = $this->_db->getQuery(true);
				$query->select('a.answer, a.id, a.is_correct, a.comments, a.marks');

				// Fetch answer media elements
				$query->select('mediaQr.media_id, mediaQr.client_id, mediaQr.source, mediaQr.original_filename, mediaQr.type AS media_type');
				$query->from('#__tmt_tests_answers AS ta ');
				$query->join('INNER', '#__tmt_answers AS a ON a.question_id = ta.question_id ');

				// Answer Media query join - start
				$answerMediaQuery = $this->_db->getQuery(true);
				$answerMediaQuery->select($this->_db->qn(array('xref.id', 'xref.media_id', 'xref.client_id', 'mf.source', 'mf.original_filename', 'mf.type')));
				$answerMediaQuery->from($this->_db->qn('#__tj_media_files_xref', 'xref'));
				$answerMediaQuery->join('INNER', $this->_db->qn('#__tj_media_files', 'mf')
						. ' ON (' . $this->_db->qn('xref.media_id') . ' = ' . $this->_db->qn('mf.id') . ')');

				$answerMediaQuery->where($this->_db->qn('xref.client') . '=' . $this->_db->quote($this->answerMediaClient));

				// Join here
				$query->leftJoin('(' . $answerMediaQuery . ') AS mediaQr
					ON ( ' . $this->_db->qn('a.id') . ' = ' . $this->_db->qn('mediaQr.client_id') . ')');

				// Answer Media query join - end

				$query->where('ta.question_id =' . (int) $q->question_id);
				$query->where('ta.test_id =' . (int) $testId);
				$query->where('ta.user_id =' . $userId);
				$query->where('ta.invite_id =' . (int) $lessonTrackId);

				// Ordering
				$query->order('a.order', 'ASC');

				$this->_db->setQuery($query);
				$a_data = $this->_db->loadObjectList();

				$tempanswerOrders = json_decode($q->anss_order);

				foreach ($tempanswerOrders as $tempans)
				{
					$answerOrders[$tempans[0]] = $tempans[1];
				}

				foreach ($a_data as $anss)
				{
					$order = array_search($anss->id, $answerOrders);

					$anss->id = $anss->id;
					$anss->order = $order;
					$anss->is_correct = $anss->is_correct;
					$anss->comments = $anss->comments;
					$anssOrderData[] = $anss;
				}

				$q->answers = $anssOrderData;

				// Get the count of correct answer
				$query = $this->_db->getQuery(true);
				$query->select('count(a.marks) as correct_options');
				$query->from('#__tmt_answers AS a');
				$query->where('a.question_id =' . (int) $q->question_id);
				$query->where('a.is_correct!=0');
				$query->order($this->_db->quoteName('a.id') . ' ASC');
				$this->_db->setQuery($query);
				$a_count = $this->_db->loadObject();
				$q->correct_answer = $a_count->correct_options;

				if (!empty($q->userAnswer))
				{
					switch ($q->type)
					{
						case "file_upload":
							$mediaIds = json_decode($q->userAnswer);
							$temp = array();
							$i = 0;

							foreach ($mediaIds as $mid)
							{
								BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjlms/models');
								$mediaModel = BaseDatabaseModel::getInstance('Media', 'TjlmsModel');
								$res = $mediaModel->getItem($mid);

								if ($res->id)
								{
									$temp[$i] = new stdClass;
									$temp[$i]->media_id = $res->id;
									$temp[$i]->source = $res->source;

									// Get media URL
									$mediaDownloadUrl = $mediaModel->getMediaUrl($res);

									$mediaTimelyUrl = $mediaModel->getMediaUrl($res, true);

									$temp[$i]->path = '#';

									$temp[$i]->timelyUrl = '#';

									if (!empty($mediaDownloadUrl))
									{
										$temp[$i]->path = $mediaDownloadUrl;
									}

									if (!empty($mediaTimelyUrl))
									{
										$temp[$i]->mediaTimelyUrl = $mediaTimelyUrl;
									}

									$temp[$i++]->org_filename = $res->org_filename;
								}
							}
						break;

						case "radio":
						case "checkbox":
							$temp = json_decode($q->userAnswer);
						break;

						case "text":
						case "textarea":
						case "rating":
							$temp = $q->userAnswer;
						break;

						default:
						$temp = $q->userAnswer;
					}

					$q->userAnswer = $temp;

					if (in_array($q->type, array('checkbox', 'radio')))
					{
						foreach ($q->answers as $ans)
						{
							$q->correct = 0;

							if ($ans->is_correct == 1)
							{
								if (!empty($q->userAnswer) && in_array($ans->id, $q->userAnswer))
								{
									$q->correct = 1;
									break;
								}
								else
								{
									$q->correct = 0;
								}
							}
						}
					}
					elseif (in_array($q->type, array('file_upload', 'rating', 'text', 'textarea')))
					{
						$q->correct = 0;

						if ($q->userMarks > 0)
						{
							$q->correct = 1;
						}
					}
				}
			}

			$item->questions       = $questions;
			$item->isObjective     = $this->checkIfObjective($testId);
			$item->attempted_count = $attemptedCount;
			$item->invite_id       = $lessonTrackId;
			$item->time_spent      = $ta_data->time_spent;
			$item->attempt_status  = $ta_data->lesson_status;
		}

		return $item;
	}

	/**
	 * Method to get attempt data of test
	 *
	 * @param   integer  $id  The id of test.
	 *
	 * @return  mixed  data of test
	 *
	 * @since  1.0
	 *
	 * @deprecated  1.4.0  This function will be removed and no replacement will be provided
	 */
	public function getTestAttemptData($id = null)
	{
		if (empty($id))
		{
			$id = $this->getState('test.id');
		}

		$table = $this->getTable();
		$user = Factory::getUser();
		$userId = $user->id;

		if (!$table->load(array("id" => $id, "state" => 1)))
		{
			return false;
		}

		$properties = $table->getProperties(1);
		$this->item = ArrayHelper::toObject($properties, 'JObject');

		$lessonTrackId = $this->getState('test.lessonTrackId');

		$lesson = $this->getLessonDetailsFromLT($lessonTrackId);

		if (!empty($lesson->id))
		{
			$this->item->resume = $lesson->resume;
		}

		$query = $this->_db->getQuery(true);
		$query->select("ta.id, TIME_TO_SEC(lt.time_spent) as time_spent, lt.lesson_status,  lt.score");
		$query->from("#__tmt_tests_attendees AS ta");
		$query->join("INNER", $this->_db->quoteName("#__tjlms_lesson_track", "lt") . " ON ta.invite_id=lt.id");
		$query->where("ta.invite_id =" . (int) $lessonTrackId);
		$query->where("ta.user_id =" . (int) $userId);
		$this->_db->setQuery($query);
		$ta_data = $this->_db->loadObject();

		if (!empty($ta_data->id))
		{
			$query = $this->_db->getQuery(true);
			$query->select('COUNT(tq.id) AS count');
			$query->from('#__tmt_tests_questions AS tq');
			$query->where('tq.test_id =' . (int) $id);
			$this->_db->setQuery($query);
			$count = $this->_db->loadResult();
			$this->item->q_count = $count;

			$this->item->attempted_count = $this->getAttemptedQuestions($id, $userId, $lessonTrackId, 1);
			$this->item->isObjective = $this->checkIfObjective($id);

			$this->item->attempt['user_id'] = $userId;
			$this->item->attempt['invite_id'] = $lessonTrackId;
			$this->item->attempt['time_spent'] = $ta_data->time_spent;
			$this->item->attempt['score'] = $ta_data->score;
			$this->item->attempt['lesson_status'] = $ta_data->lesson_status;
		}
		else
		{
			return false;
		}

		return $this->item;
	}

	/**
	 * REMOVE THIS FUNCTION CALL IN TJLMS WHILE DEPRECATING
	 *
	 * Get number of questions user has traversed. It may be answered or unanswered.
	 * This will be used to keep get the next page and questions to be shown on next page
	 * 4th param tells if we want count only
	 *
	 * @param   string  $test_id        test id for which data needs to be taken
	 * @param   string  $user_id        user id for which data needs to be taken
	 * @param   array   $lessonTrackId  id of the lesson_track table
	 * @param   array   $only_cnt       Param to get the count only
	 *
	 * @return  Table  A database object
	 *
	 * @since  1.0
	 *
	 * @deprecated  1.4.0  This function will be removed and replacement is getAttemptedQuestions()
	 */
	public function getTraversedQuestions($test_id, $user_id, $lessonTrackId, $only_cnt = 1)
	{
		$db = Factory::getDBO();

		$query = $db->getQuery(true);

		if ($only_cnt == 1)
		{
			$query->select('COUNT(ta.id) ');
		}
		else
		{
			$query->select('ta.* ');
		}

		$query->from('`#__tmt_tests_answers` AS ta ');
		/*$query->where(' ta.test_id =' . (int) $test_id);*/
		$query->where(' ta.user_id =' . (int) $user_id);
		$query->where(' ta.invite_id =' . (int) $lessonTrackId);
		$query->where(' ta.answer !=' . '" "');
		$db->setQuery($query);

		if ($only_cnt == 1)
		{
			$questions_traversed = $db->loadResult();
		}
		else
		{
			$questions_traversed = $db->loadObjectList();
		}

		return $questions_traversed;
	}

	/**
	 * Get number of questions user has attempted. answer should be non blank or should not with -.
	 * 4th param tells if we want count only
	 *
	 * @param   string  $test_id        test id for which data needs to be taken
	 * @param   string  $user_id        user id for which data needs to be taken
	 * @param   INT     $lessonTrackId  id of the lesson_track table
	 * @param   INT     $only_cnt       Param to get the count only
	 *
	 * @return  Table  A database object
	 *
	 * @since  1.0
	 */
	public function getAttemptedQuestions($test_id, $user_id, $lessonTrackId, $only_cnt = 1)
	{
		$db    = Factory::getDBO();
		$query = $db->getQuery(true);

		if ($only_cnt == 1)
		{
			$query->select('COUNT(ta.id)');
		}
		else
		{
			$query->select('ta.*');
		}

		$query->from('`#__tmt_tests_answers` AS ta ');
		$query->where('ta.test_id =' . (int) $test_id);
		$query->where('ta.user_id =' . (int) $user_id);
		$query->where('ta.invite_id =' . (int) $lessonTrackId);
		$query->where('ta.answer !=' . '" "');
		$db->setQuery($query);

		if ($only_cnt == 1)
		{
			$questions_attempted = $db->loadResult();
		}
		else
		{
			$questions_attempted = $db->loadObjectList();
		}

		return $questions_attempted;
	}

	/**
	 * Returns a reference to the a Table object, always creating it.
	 *
	 * @param   string  $type    The table type to instantiate
	 * @param   string  $prefix  A prefix for the table class name. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return  Table  A database object
	 *
	 * @since  1.0
	 */
	public function getTable($type = 'Test', $prefix = 'TmtTable', $config = array())
	{
		$this->addTablePath(JPATH_ADMINISTRATOR . '/components/com_tmt/tables');

		return Table::getInstance($type, $prefix, $config);
	}

	/**
	 * Method to check in an item.
	 *
	 * @param   integer  $id  The id of the row to check out.
	 *
	 * @return  boolean  True on success, false on failure.
	 *
	 * @since  1.0
	 */
	public function checkin($id = null)
	{
		// Get the id.
		$id = (!empty($id)) ? $id : (int) $this->getState('test.id');

		if ($id)
		{
			// Initialise the table
			$table = $this->getTable();

			// Attempt to check the row in.
			if (method_exists($table, 'checkin'))
			{
				if (!$table->checkin($id))
				{
					$this->setError($table->getError());

					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Method to check out an item for editing.
	 *
	 * @param   integer  $id  The id of the row to check out.
	 *
	 * @return  boolean  True on success, false on failure.
	 *
	 * @since  1.0
	 */
	public function checkout($id = null)
	{
		// Get test id.
		$id = (!empty($id)) ? $id : (int) $this->getState('test.id');

		if ($id)
		{
			// Initialise the table
			$table = $this->getTable();

			// Get the current user object.
			$user = Factory::getUser();

			// Attempt to check the row out.
			if (method_exists($table, 'checkout'))
			{
				if (!$table->checkout($user->get('id'), $id))
				{
					$this->setError($table->getError());

					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Method to get the profile form.
	 *
	 * The base form is loaded from XML
	 *
	 * @param   array    $data      An optional array of data for the form to interogate.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  JForm  A JForm object on success, false on failure
	 *
	 * @since  1.0
	 */
	public function getForm($data = array() , $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm('com_tmt.test', 'test', array( 'control' => 'jform', 'load_data' => $loadData ));

		if (empty($form))
		{
			return false;
		}

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return  mixed  The data for the form.
	 *
	 * @since  1.0
	 */
	protected function loadFormData()
	{
		$data = $this->getData();

		return $data;
	}

	/**
	 * Method to save the form data.
	 *
	 * @param   array  $data  The form data.
	 *
	 * @return  mixed  The user id on success, false on failure.
	 *
	 * @since  1.0
	 */
	public function save($data)
	{
		$app     = Factory::getApplication();
		$id    = (!empty($data['id'])) ? $data['id'] : (int) $this->getState('test.id');
		$state = (!empty($data['state'])) ? 1 : 0;
		$user  = Factory::getUser();

		if ($id)
		{
			// Check the user can edit this item
			$authorised = $user->authorise('core.edit', 'com_tmt') || $user->authorise('core.edit.own', 'com_tmt');
		}
		else
		{
			// Check the user can create new items in this section
			$authorised = $user->authorise('core.create', 'com_tmt');
		}

		if ($authorised !== true)
		{
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
            $app->setHeader('status', 403, true);


			return false;
		}

		if ($user->authorise('core.edit.state', 'com_tmt') !== true && $state == 1)
		{
			// The user cannot edit the state of the item.
			$data['state'] = 0;
		}

		$table = $this->getTable();

		if ($table->save($data) === true)
		{
			return $id;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Method to delete test
	 *
	 * @param   array  $data  data array
	 *
	 * @return  boolean  true/false or int $id
	 *
	 * @since  1.0
	 */
	public function delete($data)
	{
		$app     = Factory::getApplication();

		if (Factory::getUser()->authorise('core.delete', 'com_tmt') !== true)
		{
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
            $app->setHeader('status', 403, true);

			return false;
		}

		$id = (!empty($data['id'])) ? $data['id'] : (int) $this->getState('test.id');

		$table = $this->getTable();

		if ($table->delete($data['id']) === true)
		{
			return $id;
		}
		else
		{
			return false;
		}

		return true;
	}

	/**
	 * Method to get category name
	 *
	 * @param   int  $id  category id
	 *
	 * @return  $title
	 *
	 * @since  1.0
	 *
	 * @deprecated  1.4.0  This function will be removed no replacement will be provided
	 */
	public function getCategoryName($id)
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select('title')->from('#__categories')->where('id = ' . (int) $id);
		$db->setQuery($query);

		return $db->loadObject();
	}

	/**
	 * Method to get Sections
	 *
	 * @param   int  $testId   test id
	 * @param   int  $onlyIds  If 1, only ids will be taken
	 *
	 * @return  section object
	 *
	 * @since  1.0
	 */
	public function getSectionsByTest($testId, $onlyIds = 1)
	{
		$subquery = $this->_db->getQuery(true);
		$subquery->select('distinct(ttq.section_id)');
		$subquery->from('#__tmt_tests_questions AS ttq');

		$query = $this->_db->getQuery(true);

		if ($onlyIds == 0)
		{
			$query->select('*');
		}

		$query->select('ts.id');
		$query->from('#__tmt_tests_sections AS ts');
		$query->where('ts.test_id = ' . (int) $testId);
		$query->where('ts.state = ' . 1);
		$query->where('ts.id IN (' . $subquery . ')');
		$query->order('ts.ordering', "ASC");

		$this->_db->setQuery($query);

		return $this->_db->loadObjectList();
	}

	/**
	 * Method to update answers by question type
	 *
	 * @param   ARRAY   $questions         id of questions
	 * @param   ARRAY   $testAnwerIdsforQ  old answers saved
	 * @param   STRING  $qtype             type of questions
	 *
	 * @return  BOOLEAN
	 *
	 * @since  1.0
	 *
	 * @deprecated  1.4.0  This function will be removed and no replacement will be provided
	 */
	public function updateByQtype($questions, $testAnwerIdsforQ, $qtype)
	{
		// Save questions against this test
		foreach ($questions as $qid => $answers)
		{
			// Don't insert/update empty text/textarea answer because
			// It is equal to question non attempted
			if (!empty($answers))
			{
				// Check if the current question is already been answered & have answer in db
				if (array_key_exists($qid, $testAnwerIdsforQ))
				{
					$testAnswerId = $testAnwerIdsforQ[$qid];

					$result = $this->saveAnswers($qid, $answers, $testAnswerId, $qtype);

					if (!$result)
					{
						return false;
					}
				}
			}
		}

		return true;
	}

	/**
	 * Method to get lesson detail from lesson track id
	 *
	 * @param   int  $ltId  lesson track id
	 *
	 * @return  object
	 *
	 * @since  1.0
	 *
	 * @deprecated  1.4.0  This function will be removed and replacement is lessonDetailsFromLessonTrack()
	 */
	public function getLessonDetailsFromLT($ltId)
	{
		$this->addTablePath(JPATH_ADMINISTRATOR . '/components/com_tjlms/tables');
		$table = Table::getInstance('lessontrack', 'TjlmsTable');
		$table->load($ltId);
		$lessonId = $table->lesson_id;

		$lesson = new stdClass;

		if ($lessonId)
		{
			JLoader::import('components.com_tjlms.models.lesson', JPATH_SITE);
			$lessonModel = BaseDatabaseModel::getInstance('Lesson', 'TjlmsModel');
			$lesson = $lessonModel->getlessondata($lessonId);

			if ($lesson->id)
			{
				return $lesson;
			}
		}

		return $lesson;
	}

	/**
	 * Method to get lesson & lessonTrack detail from lesson track id
	 *
	 * @param   int  $ltId  Lesson track id
	 *
	 * @return  object
	 *
	 * @since  1.0
	 */
	public function lessonDetailsFromLessonTrack($ltId)
	{
		$obj = new stdClass;

		$this->addTablePath(JPATH_ADMINISTRATOR . '/components/com_tjlms/tables');
		$table    = Table::getInstance('lessontrack', 'TjlmsTable');
		$table->load($ltId);
		$lessonId = $table->lesson_id;

		if ($lessonId)
		{
			JLoader::import('components.com_tjlms.models.lesson', JPATH_SITE);
			$lessonModel = BaseDatabaseModel::getInstance('Lesson', 'TjlmsModel');
			$obj->lesson = $lessonModel->getlessondata($lessonId);
		}

		$properties       = $table->getProperties(1);
		$obj->lessonTrack = ArrayHelper::toObject($properties, 'JObject');

		return $obj;
	}

	/**
	 * Method to save answers of question from a test for an attempt
	 *
	 * @param   INT  $answertableId      Id of the answer table
	 * @param   INT  $userAnswerMediaId  Id of the user answer table
	 *
	 * @return  BOOLEAN
	 *
	 * @since  1.3
	 */
	public function removeFileuploadAnswer($answertableId, $userAnswerMediaId)
	{
		$lessonStatus = array('passed', 'failed', 'completed', 'AP');
		$testAnswers  = $this->getTable('testanswers');
		$testAnswers->load($answertableId);

		if (!$testAnswers->id)
		{
			return false;
		}

		$userAnswers = json_decode($testAnswers->answer);

		if (in_array($userAnswerMediaId, $userAnswers))
		{
			$userId      = Factory::getUser()->id;
			$mediaId     = $userAnswerMediaId;
			BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjlms/models');
			$mediaModel  = BaseDatabaseModel::getInstance('Media', 'TjlmsModel');
			$mediaObject = $mediaModel->getItem($userAnswerMediaId);

			$this->addTablePath(JPATH_ADMINISTRATOR . '/components/com_tjlms/tables');
			$lessonTracktable = $this->getTable("lessontrack", "TjlmsTable");
			$lessonTracktable->load($testAnswers->invite_id);

			if ($mediaObject->id && $userId == $mediaObject->created_by && !in_array($lessonTracktable->lesson_status, $lessonStatus))
			{
				$result = $mediaModel->delete($userAnswerMediaId);

				if ($result)
				{
					if (($key = array_search($mediaId, $userAnswers)) !== false)
					{
						unset($userAnswers[$key]);
					}

					// If answers not empty
					if (!empty(array_values($userAnswers)))
					{
						$testAnswers->answer = json_encode(array_values($userAnswers));
					}
					// If answers reset / deleted
					else
					{
						$testAnswers->answer = '';
					}

					$testAnswers->store();

					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Method to save answers of question from a test for an attempt
	 *
	 * @param   ARRAY  $queAnswerData  Array consisting lessontrackid, testid, question id and answer added by user
	 *
	 * @return  BOOLEAN|INEGER
	 *
	 * @since  1.3
	 */
	public function saveTestQuestionAnswers($queAnswerData)
	{
		$app           = Factory::getApplication();
		$olUserId      = Factory::getUser()->id;
		$lessonTrackId = $queAnswerData['ltid'];
		$testId        = $queAnswerData['testid'];
		$questionId    = $queAnswerData['qid'];
		$answer        = $queAnswerData['answer'];

		$question = TMT::Question($questionId);
		$test     = TMT::Test($testId);

		if (!$question->id || !$test->id || !$lessonTrackId || !$olUserId)
		{
			return false;
		}

		$this->addTablePath(JPATH_ADMINISTRATOR . '/components/com_tjlms/tables');
		$lessonTracktable = $this->getTable("lessontrack", "TjlmsTable");
		$lessonTracktable->load($lessonTrackId);

		if ($lessonTracktable->lesson_status == 'passed' || $lessonTracktable->lesson_status == 'failed')
		{
			return false;
		}

		// Check user authentication w.r.t lesson access
		if ($lessonTracktable->user_id != $olUserId)
		{
			$app->enqueueMessage(Text::_('COM_TMT_ACCESS_TEST'), 'error');
            $app->setHeader('status', 403, true);

			return false;
		}

		$testAnswersTable = $this->getTable('testanswers');

		$testAnswersTable->load(
			array(
				"question_id" => $question->id,
				"user_id"     => $olUserId,
				"test_id"     => $test->id,
				"invite_id"   => $lessonTrackId
			)
		);

		$testAnswersTable->question_id = $question->id;
		$testAnswersTable->user_id     = $olUserId;
		$testAnswersTable->test_id     = $testId;

		if ($question->type == 'radio' || $question->type == 'checkbox' || $question->type == 'file_upload')
		{
			if (!empty($answer))
			{
				$answers    = explode(",", $answer);
				$totalMarks = 0;

				/* In case of file_upload type question, $answers array contains media ids not answer ids,
					that is why below condition calculates marks/totalMarks for radio & checkbox type questions.*/
				if ($question->type != 'file_upload')
				{
					$answersTable = $this->getTable('answers');

					foreach ($answers as $ans)
					{
						$answersTable->load(array('id' => $ans));
						$totalMarks = $totalMarks + $answersTable->marks;
					}
				}

				$testAnswersTable->answer = json_encode(array_values($answers));
				$testAnswersTable->marks  = $totalMarks;
			}
			// Skip question is selected
			else
			{
				$testAnswersTable->answer = '';
				$testAnswersTable->marks  = 0;
			}
		}
		elseif ($question->type == 'objtext')
		{
			$answer = trim($answer);

			if (!empty($answer))
			{
				$totalMarks = 0;

				$answersTable = $this->getTable('answers', "TmtTable");
				$answersTable->load(array('question_id' => $question->id));

				if (strpos($answersTable->answer, ",") !== false)
				{
					$answerList = explode(",", $answersTable->answer);

					foreach ($answerList as $answerL)
					{
						if ((strcasecmp($answer, $answerL)) == 0)
						{
							$totalMarks = $totalMarks + $question->marks;

							break;
						}
					}
				}
				else
				{
					if ((strcasecmp($answer, $answersTable->answer)) == 0)
					{
						$totalMarks = $totalMarks + $question->marks;
					}
				}

				$testAnswersTable->answer = $answer;
				$testAnswersTable->marks  = $totalMarks;
			}
			// Skip question is selected
			else
			{
				$testAnswersTable->answer = '';
				$testAnswersTable->marks  = 0;
			}
		}
		else
		{
			$testAnswersTable->answer = $answer;
		}

		$testAnswersTable->store();

		return $testAnswersTable->id;
	}

	/**
	 * Method to save answers od Mcq questions
	 *
	 * @param   int    $questionId     id of questions
	 * @param   MIXED  $answers        answer given to the question
	 * @param   int    $testAnswersId  id of submitted answers if already submitted
	 * @param   int    $qtype          type of question answer is to be saved
	 *
	 * @return  object
	 *
	 * @since  1.0
	 */
	private function saveAnswers($questionId, $answers, $testAnswersId, $qtype)
	{
		$db = Factory::getDbo();
		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tmt/tables');
		$testAnswersTable = Table::getInstance('testanswers', 'TmtTable', array('dbo', $db));

		if ($testAnswersId)
		{
			$testAnswersTable->load($testAnswersId);
		}

		$testAnswersTable->question_id = $questionId;
		$testAnswersTable->user_id     = Factory::getUser()->id;
		$testAnswersTable->test_id     = $this->testId;

		if ($qtype == 'mcqs')
		{
			$total_marks = 0;
			$table       = Table::getInstance('answers', 'TmtTable', array('dbo', $db));

			foreach ($answers as $answer)
			{
				$table->load(array('id' => $answer));
				$total_marks = $total_marks + $table->marks;
			}

			$testAnswersTable->answer = json_encode($answers);
			$testAnswersTable->marks  = $total_marks;
		}
		else
		{
			$testAnswersTable->answer = $answers;
		}

		$testAnswersTable->invite_id = $this->lessonTrackId;
		$result                      = $testAnswersTable->store();

		return $result;
	}

	/**
	 * Method to get lesson id from lesson track id
	 *
	 * @param   int  $id  lesson track id
	 *
	 * @return  object
	 *
	 * @since  1.0
	 *
	 * @deprecated  1.4.0  This function will be removed and replacement is lessonDetailsFromLessonTrack()
	 */
	public function getLessonIdFromLT($id)
	{
		$lessonId = 0;

		if ($id)
		{
			$db 	= Factory::getDbo();
			$query  = $db->getQuery(true);
			$query->select('lesson_id')
				->from('#__tjlms_lesson_track')
				->where('id = ' . (int) $id);
			$db->setQuery($query);
			$lessonId = $db->loadResult();
		}

		return $lessonId;
	}

	/**
	 * Add the answered in tmt_answeres table when user clicks on start button from test premises
	 *
	 * @param   INT  $testId         test id
	 * @param   INT  $lessonTrackId  id of the lesson track table
	 *
	 * @return   void
	 *
	 * @since  1.0.0
	 */
	public function addtmtTestAnswers($testId, $lessonTrackId)
	{
		$oluserId = Factory::getUser()->id;

		if (empty($this->getAttemptedQuestions($testId, $oluserId, $lessonTrackId, 1)))
		{
			// Get all sections of test
			$sections = $this->getSectionsByTest($testId);
			$test     = TMT::Test($testId);

			foreach ($sections as $sec)
			{
				$section = $sec->id;
				$query   = $this->_db->getQuery(true);
				$query->select('tq.*');
				$query->from('#__tmt_tests_questions AS tq');

				// Join over the foreign key 'question_id'
				$query->join('INNER', '#__tmt_questions AS q ON q.id = tq.question_id');
				$query->where('tq.test_id    =' . (int) $testId);
				$query->where('tq.section_id =' . (int) $section);

				// Check if answer shuffle, resume and question shuffle is on for test
				if ($test->questions_shuffle == 1)
				{
					$query->order('RAND()');
				}
				else
				{
					$query->order('tq.order ASC');
				}

				$this->_db->setQuery($query);
				$questions = $this->_db->loadObjectList();

				foreach ($questions as $question)
				{
					$obj              = new stdClass;
					$obj->id 		  = '';
					$obj->question_id = $question->question_id;
					$obj->user_id     = $oluserId;
					$obj->test_id     = $testId;
					$obj->invite_id   = $lessonTrackId;

					$query = $this->_db->getQuery(true);
					$query->select('a.id, a.answer, a.order');
					$query->from('#__tmt_answers AS a');
					$query->where(' a.question_id =' . (int) $question->question_id);

					if ($test->answers_shuffle == 1)
					{
						$query->order('RAND()');
					}
					else
					{
						$query->order($this->_db->quoteName('a.id') . ' ASC');
					}

					$this->_db->setQuery($query);
					$answers  = $this->_db->loadObjectList();
					$ansOrder = array();

					foreach ($answers as $a)
					{
						$ansOrder[] = array($a->order, $a->id);
					}

					$ansOrder        = json_encode($ansOrder, true);
					$obj->answer = '';
					$obj->anss_order = $ansOrder;
					$obj->marks = 0;
					$obj->flagged = 0;
			
					try
					{
						$result = $this->_db->insertObject('#__tmt_tests_answers', $obj);
					}
					catch (Exception $e)
					{
						ExceptionHandler::render(new Exception(Text::_('COM_TMT_ATTEMPTS_EXHAUSTED_TOOLTIP'), 500, $e));
					}
				}
			}
		}
	}

	/**
	 * Method to get test id from lesson track id.
	 *
	 * @param   int  $lesson_track_id  lesson track id
	 *
	 * @return  boolean  true/false
	 *
	 * @since    1.2
	 */
	public function getTestIdFromLessonTrack($lesson_track_id)
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true);

		$query->select('ta.test_id');
		$query->from($db->quoteName('#__tmt_tests_attendees', 'ta'));
		$query->join('INNER', $db->quoteName('#__tmt_tests', 't') .
		' ON (' . $db->quoteName('t.id') . ' = ' .
		$db->quoteName('ta.test_id') . ')');
		$query->where($db->quoteName('ta.invite_id') . " = " . $db->quote($lesson_track_id));

		$db->setQuery($query);
		$test_id = $db->loadResult();

		return $test_id;
	}

	/**
	 * Function is used to get the score of the user when user submit the test
	 *
	 * @param   INT  $inviteId  primary if of the lesson_track table
	 *
	 * @return  INT
	 *
	 * @since  1.3
	 */
	public function getTotalScore($inviteId)
	{
		$scoredMarks = 0;
		$db          = Factory::getDbo();

		$query = $db->getQuery(true);
		$query->select($db->qn(array('tta.question_id','tta.user_id','tta.answer','tta.marks','tta.test_id')));
		$query->select($db->qn('tq.marks', 'quemarks'));
		$query->from($db->quoteName('#__tmt_tests_answers', 'tta'));
		$query->join('INNER', $db->quoteName('#__tmt_questions', 'tq') . ' on (' . $db->qn('tta.question_id') . ' = ' . $db->qn('tq.id') . ')');
		$query->where($db->quoteName('invite_id') . ' = ' . (int) $inviteId);
		$db->setQuery($query);
		$testAnswers = $db->loadObjectList();

		if (!empty($testAnswers))
		{
			// Get the Questions added against test
			$query = $db->getQuery(true);
			$query->select('distinct tq.id AS question_id');
			$query->from($db->quoteName('#__tmt_tests_questions', 't'));
			$query->join('INNER', $db->quoteName('#__tmt_questions', 'tq') . ' on ( ' . $db->qn('t.question_id') . ' = ' . $db->qn('tq.id') . ')');
			$query->where($db->quoteName('t.test_id') . ' = ' . (int) $testAnswers[0]->test_id);
			$db->setQuery($query);
			$testQues = $db->loadColumn();

			$testQuestions = array();

			JLoader::import('questionform',  JPATH_SITE . '/components/com_tmt/models');

			foreach ($testQues as $qid)
			{
				$questionModel = BaseDatabaseModel::getInstance('questionform', 'TmtModel');

				$questionModel->setState('question.correct_answers', '1');
				$testQuestions[$qid] = $questionModel->getItem($qid);
			}

			foreach ($testAnswers as $ta)
			{
				$question = $testQuestions[$ta->question_id];

				if ($ta->marks)
				{
					$scoredMarks += $ta->marks;
				}
				else
				{
					$userAnswers = json_decode($ta->answer);

					if ($question && $userAnswers)
					{
						foreach ($question->answers as $comp)
						{
							if (in_array($comp->id, $userAnswers))
							{
								$scoredMarks += $comp->marks;
							}
						}
					}
				}
			}
		}

		return $scoredMarks;
	}

	/**
	 * Method to flag/unflag a question while attempting a test
	 *
	 * @param   array  $data  Question data
	 *
	 * @return  boolean
	 *
	 * @since  _DEPLOY_VERSION__
	 */
	public function flagQuestion($data)
	{
		$db = Factory::getDbo();
		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tmt/tables');
		$testAnswersTable = Table::getInstance('testanswers', 'TmtTable', array('dbo', $db));

		if ($data['qId'] && $data['inviteId'] && $data['testId'])
		{
			$testAnswersTable->load(
				array (
					'question_id' => $data['qId'],
					'invite_id'   => $data['inviteId'],
					'test_id'     => $data['testId']
				)
			);
		}

		if ($testAnswersTable->flagged == 1)
		{
			$testAnswersTable->flagged = 0;
		}
		else
		{
			$testAnswersTable->flagged = 1;
		}

		$result = $testAnswersTable->store();

		return $result;
	}

	/**
	 * Method to validate the form data
	 *
	 * @param   array  $data  The data to validate.
	 *
	 * @return  boolean true if valid, false otherwise.
	 *
	 * @since   1.3.14
	 */
	public function validateAnswer($data)
	{
		JLoader::import('questionform', JPATH_SITE . '/components/com_tmt/models');
		$questionModel = BaseDatabaseModel::getInstance('questionform', 'TmtModel');
		$quetionItems  = $questionModel->getItem($data['qid']);

		if ($quetionItems->type == 'rating')
		{
			$ans = (int) $data['answer'];
			$min = (int) $quetionItems->answers[0]->answer;
			$max = (int) $quetionItems->answers[1]->answer;

			if ($ans && (($ans > $max) || ($ans < $min)))
			{
				$this->setError(Text::_('COM_TMT_TEST_INVALID_ANSWER'));

				return false;
			}
		}

		return true;
	}
}
