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
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;

jimport('joomla.application.component.modelform');
jimport('joomla.event.dispatcher');

/**
 * Methods for lesson.
 *
 * @since  1.3
 */
class TjlmsModelAssessment extends ListModel
{
	/**
	 * Method to save Assessments
	 *
	 * @param   ARRAY  $assessmentData  JForm Data
	 *
	 * @return  boolean.
	 *
	 * @since   1.2
	 */
	public function save($assessmentData)
	{
		JLoader::import('assessments', JPATH_SITE . '/components/com_tjlms/models');
		$assessmentsModel	= BaseDatabaseModel::getInstance('assessments', 'TjlmsModel');

		$olUser = Factory::getUser();
		$ltId = $assessmentData['ltId'];
		$reviewId = $assessmentData['reviewId'];
		$reviewerId = !empty($assessmentData['reviewerId']) ? $assessmentData['reviewerId'] : $olUser->id;
		$reviewer = Factory::getUser($reviewerId);

		if (!$ltId && !$reviewId)
		{
			$this->setError(Text::_('COM_TJLMS_ASSESSMENTS_MISSING_TRACKID'));

			return false;
		}

		if ($reviewId)
		{
			$assessment_data = $assessmentsModel->getLessonTrack($reviewId);

			if (empty($assessment_data))
			{
				$this->setError(Text::_('COM_TJLMS_ASSESSMENT_SUBMISSION_NOT_ADDED'));

				return false;
			}

			$reviewer = Factory::getUser($assessment_data[1]);
			$reviewerId  = $reviewer->id;
		}

		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjlms/tables');
		$lessonTrack = Table::getInstance('Lessontrack', 'TjlmsTable');
		$lessonTrack->load($ltId);
		$lessonTable = Table::getInstance('Lesson', 'TjlmsTable');
		$lessonTable->load($lessonTrack->lesson_id);

		if (!$lessonTable->id || !$lessonTrack->id)
		{
			$this->setError(Text::_('COM_TJLMS_LESSON_INVALID_URL'));

			return false;
		}

		$lessonAssessment	= $assessmentsModel->getLessonAssessments($lessonTable->id);

		if (!$lessonAssessment->set_id)
		{
			$this->setError(Text::_('COM_TJLMS_ASSESSMENTS_NOT_ADDED'));

			return false;
		}

		/*Now check if user can submit the assessment*/
		JLoader::register('TjlmsHelper', JPATH_ADMINISTRATOR . '/components/com_tjlms/helpers/tjlms.php');
		$canAssess = TjlmsHelper::canDoAssessment($lessonTable->course_id, $olUser->id);

		if (!$canAssess)
		{
			$this->setError(403, Text::_('JERROR_ALERTNOAUTHOR'));

			return false;
		}

		$trackReview = Table::getInstance('Assessmentreviews', 'TjlmsTable');
		$trackReview->load(array("lesson_track_id" => $ltId, "reviewer_id" => $reviewerId));

		$submissionsCount = $assessmentsModel->getAssessmentSubmissionsCount($ltId);

		$canEdit = 0;
		$score = 0;
		$reviewStatus = $assessmentData['reviewStatus'];

		if (!$trackReview->id && $reviewerId == $olUser->id && $submissionsCount < $lessonAssessment->assessment_attempts)
		{
			$canEdit = 1;
		}
		elseif ($trackReview->id)
		{
			/* Now check if user is submitting new or editing his or any others sumission*/

			if ($trackReview->review_status == 1 && $reviewStatus == 1)
			{
				$canEditOwnAssessment = TjlmsHelper::canEditOwnAssessment($lessonTable->course_id, $olUser->id);
				$canEditAllAssess = TjlmsHelper::canEditAllAssessment($lessonTable->course_id, $olUser->id);

				if (($reviewerId == $olUser->id && $canEditOwnAssessment) || ($reviewerId != $olUser->id && $canEditAllAssess))
				{
					$canEdit = 1;
				}
			}
			elseif ($trackReview->review_status == 0 && $submissionsCount < $lessonAssessment->assessment_attempts && $reviewerId == $olUser->id)
			{
				$canEdit = 1;
			}
		}

		if (!$canEdit)
		{
			$this->setError(Text::_('COM_TJLMS_ASSESSMENT_CANNOT_SUBMIT_ASSESSMENT'));

			return false;
		}

		if ($assessmentData['gradingtype'] == 'quiz')
		{
			Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tmt/tables');

			foreach ($assessmentData['reviewerMarks'] as $q => $marks)
			{
				$testAnswersTable = Table::getInstance('testanswers', 'TmtTable');

				$testAnswersTable->load(
									array("question_id" => $q,
									"invite_id" => $ltId)
									);

				$testAnswersTable->marks = $marks;
				$testAnswersTable->store();
			}

			if ($reviewStatus == 1)
			{
				/*$testattendees = JTable::getInstance('testattendees', 'TmtTable');
				$testattendees->load(array("invite_id" => $ltId));
				$tesId = $testattendees->test_id;
				$test = Table::getInstance('test', 'TmtTable');
				$test->load($tesId);*/

				$query = $this->_db->getQuery(true);
				$query->select('SUM(marks)');
				$query->from($this->_db->quoteName('#__tmt_tests_answers'));
				$query->where($this->_db->quoteName('invite_id') . " = " . $this->_db->quote($ltId));
				$this->_db->setQuery($query);
				$score = $this->_db->loadResult();

				/*if ($test->passing_marks <= $finalMarks)
				{
					$testStatus = "P";
					$ltStatus = "passed";
				}
				else
				{
					$testStatus = "F";
					$ltStatus = "failed";
				}

				$ltTable = Table::getInstance('Lessontrack', 'TjlmsTable');
				$ltTable->load($ltId);
				$ltTable->score = $finalMarks;
				$ltTable->lesson_status = $ltStatus;
				$ltTable->modified_by = $reviewerId;
				$ltTable->store();

				$testattendees->score = $finalMarks;
				$testattendees->review_status = 1;
				$testattendees->result_status = $testStatus;
				$testattendees->store();

				$score = $finalMarks;*/
			}
		}
		else
		{
			$trackRatings = $assessmentsModel->getTrackRating($ltId, $reviewerId);

			if (!empty($lessonAssessment->assessmentParams) && !empty($assessmentData["assessmentParams"]))
			{
				$inputs = $assessmentData["assessmentParams"];

				foreach ($lessonAssessment->assessmentParams as $param)
				{
					$ratingsTable = Table::getInstance('assessmentratings', 'TjlmsTable');
					$paramsTable = Table::getInstance('assessmentparameter', 'TjlmsTable');

					$paramInput = $inputs[$param->id];

					if (!empty($paramInput))
					{
						$paramsTable->load($param->id);
						$ratingsTable->load(array("rating_id" => $param->id, "lesson_track_id" => $ltId, "reviewer_id" => $reviewerId));

						if (!$ratingsTable->id)
						{
							$ratingsTable->rating_id = $param->id;
							$ratingsTable->lesson_track_id = $ltId;
							$ratingsTable->reviewer_id = $reviewerId;
						}

						if (isset($paramInput['rating_comment']))
						{
							$ratingsTable->rating_comment     = strip_tags($paramInput['rating_comment']);
						}

						$ratingsTable->rating_value   = isset($paramInput['rating_value']) ? (float) $paramInput['rating_value'] : 0;

						// Do not let user enter score more than assigned to the param

						$maxRatingValue = (float) $paramsTable->value;

						if ($ratingsTable->rating_value > $maxRatingValue)
						{
							$ratingsTable->rating_value = $maxRatingValue;
						}

						$score += $ratingsTable->rating_value * $paramsTable->weightage;

						if (!$ratingsTable->store())
						{
							$this->setError($db->stderr());

							return false;
						}
					}
				}
			}
		}

		$date = Factory::getDate();
		$curDate = $date->toSql(true);

		if ($trackReview->id)
		{
			$trackReview->modified_date  = $curDate;
			$trackReview->modified_by = $reviewerId = 0;
		}
		else
		{
			$trackReview->lesson_track_id    = (int) $ltId;
			$trackReview->reviewer_id        = (int) $reviewerId;
			$trackReview->created_date       = $curDate;
		}

		$trackReview->review_status = $reviewStatus;
		$trackReview->score = $score;

		if (isset($assessmentData['feedback']))
		{
			$trackReview->feedback   = strip_tags($assessmentData['feedback']);
		}

		if (!$trackReview->store())
		{
			$this->setError($this->_db->stderr());

			return false;
		}

		$assessmentCount = $assessmentsModel->getAssessmentSubmissionsCount($ltId);

		if ($lessonAssessment->assessment_attempts <= $assessmentCount)
		{
			$finalScore = $assessmentsModel->getAssessmentScore($ltId, $lessonAssessment->assessment_attempts_grade);
			$status = $assessmentsModel->getLessonStatusByAssessment($lessonTable->id, $finalScore);

			$lessonTrack->load($ltId);

			$trackingHelper = new comtjlmstrackingHelper;
			$trackObj = new stdClass;
			$trackObj->attempt = $lessonTrack->attempt;
			$trackObj->score = $finalScore;
			$trackObj->lesson_status = $status;
			$lessonTrackId = $trackingHelper->storeTrack($lessonTrack->lesson_id, $lessonTrack->user_id, $trackObj);

			if ($assessmentData['gradingtype'] == 'quiz' || $assessmentData['gradingtype'] == 'exercise')
			{
				/* Update attendees for score and status*/
				Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tmt/tables');
				$testattendees = Table::getInstance('testattendees', 'TmtTable');
				$testattendees->load(array("invite_id" => $ltId));
				$testattendees->score = $finalScore;
				$testattendees->review_status = 1;

				if ($status == 'passed')
				{
					$testattendees->result_status = 'P';
				}
				else
				{
					$testattendees->result_status = 'F';
				}

				if (!$testattendees->store())
				{
					$this->setError($this->_db->stderr());

					return false;
				}

				// Send thank you email to candidate.
				if ($lessonAssessment->assessment_attempts == $assessmentCount)
				{
					$tjlmsMailcontentHelper      = new TjlmsMailcontentHelper;
					$tjlmsMailcontentHelper->sendThankYouEmailToCandidate($ltId, $lessonTrack->user_id, $lessonTable->course_id);
				}
			}
		}

		return $score;
	}
}
