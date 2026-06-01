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

use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\CMS\MVC\Model\FormModel;
use Joomla\String\StringHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 * Tmt testpremise model.
 *
 * @since  1.0.0
 */
class TmtModelTestpremise extends FormModel
{
	protected $item = null;

	/**
	 * Method to get the profile form.
	 *
	 * The base form is loaded from XML
	 *
	 * @param   array    $data      An optional array of data for the form to interogate.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return    JForm    A JForm object on success, false on failure
	 *
	 * @since    1.6
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm('com_tmt.test', 'test', array('control' => 'jform',	'load_data' => $loadData));

		if (empty($form))
		{
			return false;
		}

		return $form;
	}

	/**
	 * Method to registered an attempt
	 *
	 * @param   array  $data  The needed data
	 *
	 * @return   viod
	 *
	 * @since  1.0.0
	 */
	public function registerAttempt($data)
	{
		$app      = Factory::getApplication();
		$oluserId = Factory::getUser()->id;

		require_once JPATH_SITE . '/components/com_tjlms/helpers/lesson.php';
		$lessonHelper = new TjlmsLessonHelper;
		$attempt      = $lessonHelper->getAttempttobeLaunched($data['lesson_id']);

		if ($attempt == 0)
		{
			$attempt = $lessonHelper->getlesson_total_attempts_done($data['lesson_id'], $oluserId);
		}

		/*Get the total content and current position as total no of pages and current page as 1*/
		$model = BaseDatabaseModel::getInstance('Test', 'TmtModel');

		// Call function to select test depends on which type of quiz it is.
		$testId = $this->selectSet($data['id'], $data['lesson_id']);

		$testPages = $model->getTestPageQuestions($testId);

		require_once JPATH_SITE . '/components/com_tjlms/helpers/tracking.php';
		$trackingHelper = new comtjlmstrackingHelper;

		$trackData = $trackingHelper->istrackpresent($data['lesson_id'], $attempt, $oluserId);

		if (empty($trackData))
		{
			$trackObj                   = new stdClass;
			$trackObj->current_position = '1';
			$trackObj->total_content    = count($testPages['questionsPerPage']);
			$trackObj->time_spent       = '';
			$trackObj->attempt          = $attempt;
			$trackObj->score            = 0;
			$trackObj->lesson_status    = 'started';
			$lessonTrackId              = $trackingHelper->update_lesson_track($data['lesson_id'], $oluserId, $trackObj);
		}
		else
		{
			$lessonTrackId = $trackData->id;
		}

		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tmt/tables');
		$attendee = Table::getInstance('TestAttendees', 'TmtTable', array('dbo', $this->_db));
		$attendee->load(array('invite_id' => $lessonTrackId));

		if (!$attendee->id)
		{
			$obj             = new stdClass;
			$obj->invite_id  = $lessonTrackId;
			$obj->test_id    = $testId;
			$obj->user_id    = $oluserId;
			$obj->time_taken = 0;
			$obj->company_id = 0;
			$obj->result_status = '';
			$obj->score = 0;
			$obj->attempt_status = 0;
			$obj->review_status = 0;
			$obj->time_taken = 0;

			if (!$this->_db->insertObject('#__tmt_tests_attendees', $obj, 'id'))
			{
				echo $this->_db->stderr();

				return false;
			}
		}

		/* Check if question shuffle is on for the test.. If yes, we will adding the entried in tmt_test_answers before user starts the test*/
		require_once JPATH_SITE . '/components/com_tmt/models/test.php';
		$testModel = new TmtModelTest;
		$testModel->addtmtTestAnswers($testId, $lessonTrackId);

		return $lessonTrackId;
	}

	/**
	 * Method to delete old attempt
	 *
	 * @param   array  $data  The id of the object to get.
	 *
	 * @return   viod
	 *
	 * @since  1.0.0
	 */
	public function deleteOldAttempt($data)
	{
		$testId = $data->get('client_id', '', 'int');
		$userId = Factory::getUser()->id;

		// Delete attendee
		$db    = $this->getDbo();
		$query = $db->getQuery(true)
			->delete($db->quoteName('#__tmt_tests_attendees'))
			->where($db->quoteName('test_id') . ' = ' . (int) $testId)
			->where($db->quoteName('user_id') . ' = ' . (int) $userId);
		$db->setQuery($query);
		$db->execute();

		// Delete test answer
		$query = $db->getQuery(true)
			->delete($db->quoteName('#__tmt_tests_answers'))
			->where($db->quoteName('test_id') . ' = ' . (int) $testId)
			->where($db->quoteName('user_id') . ' = ' . (int) $userId);
		$db->setQuery($query);
		$db->execute();
	}

	/**
	 * Method to reject Invite
	 *
	 * @param   integer  $data  The data needed
	 *
	 * @return   true
	 *
	 * @since  1.0.0
	 */
	public function rejectInvite($data)
	{
		$invite_id      = $data->get('invite_id', '', 'int');
		$invitex_ref_id = $data->get('invitex_ref_id', '', 'int');
		$test_id        = $data->get('client_id', '', 'int');
		$company_id     = $data->get('company_id', '', 'int');

		// Register new attempt as rejected
		$obj             = new stdClass;
		$obj->invite_id  = $invite_id;
		$obj->test_id    = $test_id;
		$obj->user_id    = Factory::getUser()->id;
		$obj->company_id = $company_id;
		$obj->time_taken = 0;

		// Set as rejected
		$obj->attempt_status = 2;

		// Insert object
		if (!$this->_db->insertObject('#__tmt_tests_attendees', $obj, 'id'))
		{
			echo $this->_db->stderr();

			return 0;
		}

		// Register invite as rejected.
		$obj                = new stdClass;
		$obj->invite_id     = $invite_id;
		$obj->response_date = Factory::getDate()->toSql();

		// Set as rejected
		$obj->response_status = 2;

		// Insert object
		if (!$this->_db->updateObject('#__tn_interview_invites', $obj, 'invite_id'))
		{
			echo $this->_db->stderr();

			return 0;
		}

		return 1;
	}

	/**
	 * Method to return set id / test id based on type of quiz (i.e set or regular)
	 *
	 * @param   integer  $testId    The main set id.
	 * @param   integer  $lessonId  The lesson id.
	 *
	 * @return  integer    Test id of quiz.
	 *
	 * @since  1.0.0
	 */

	public function selectSet($testId, $lessonId)
	{
		$db    = $this->getDbo();
		$model = BaseDatabaseModel::getInstance('Test', 'TmtModel');
		$test  = $model->getTable();
		$test->load($testId);

		// Check if the Quiz is plain quiz or set Quiz
		if ($test->type == 'plain')
		{
			return $test->id;
		}
		else
		{
			// Convert the Table to a clean Array.
			$test_data              = $test->getProperties(1);
			$test_data['id']        = '';
			$test_data['parent_id'] = (int) $testId;
			$test_data['type']      = 'plain';
			$alias                  = StringHelper::increment($test->alias, 'dash', mt_rand(100, 1000000));

			while ($test->load(array('alias' => $alias)))
			{
				$alias = StringHelper::increment($alias, 'dash', mt_rand(100, 1000000));
			}

			$test_data['alias'] = $alias;

			if ($test->save($test_data) === true)
			{
				$this->generateNewQuizSet($testId, $test->id);

				return $test->id;
			}
		}
	}

	/**
	 * method to generate question set based on parent set by using rules
	 *
	 * @param   integer  $testId     The main set id.
	 * @param   integer  $setTestId  New Sub test id.
	 *
	 * @return  nothing
	 *
	 * @since  1.0.0
	 */

	public function generateNewQuizSet($testId, $setTestId)
	{
		require_once JPATH_ADMINISTRATOR . '/components/com_tmt/models/section.php';

		// Get parent test questions
		$db = $this->getDbo();

		$model = BaseDatabaseModel::getInstance('Test', 'TmtModel');

		/* Get all sections of test */
		$sections = $model->getSectionsByTest($testId, 0);
		$test     = $model->getTable();
		$test->load($testId);

		foreach ($sections as $sec)
		{
			$section = $sec->id;

			$sectionModel = BaseDatabaseModel::getInstance('Section', 'TmtModel');
			$sectionTable     = $sectionModel->getTable();

			$sectionData             = array();
			$sectionData['id']       = '';
			$sectionData['title']    = $sec->title;
			$sectionData['test_id']  = $setTestId;
			$sectionData['state']    = 1;
			$sectionData['ordering'] = $sec->ordering;
			$sectionModel->save($sectionData);

			$setsectionId = (int) $sectionModel->setState($sectionModel->getName() . '.id', $sectionTable->$key);
		
			$query = $this->_db->getQuery(true);
			$query->select('*');
			$query->from('#__tmt_quiz_rules AS t');
			$query->where('t.quiz_id =' . (int) $testId);
			$query->where('t.section_id =' . (int) $section);
			$this->_db->setQuery($query);
			$rules = $this->_db->loadAssocList();

			$i = 0;

			foreach ($rules as $rule)
			{
				$marks            = $rule['marks'];
				$limitCount       = $rule['questions_count'];
				$category         = $rule['category'];
				$difficulty_level = $rule['difficulty_level'];
				$question_type    = $rule['question_type'];

				$query = $this->_db->getQuery(true);
				$query->select('tq.question_id');
				$query->from('#__tmt_tests_questions AS tq');
				$query->join('inner', '#__tmt_questions as q ');
				$query->where('tq.question_id = q.id');
				$query->where('tq.test_id     ="' . (int) $testId . '"');
				$query->where('tq.section_id  =' . (int) $section);
				$query->where('q.marks        ="' . (int) $marks . '"');

				if (! empty($category) or $category != 0)
				{
					$query->where('q.category_id=' . "'$category'");
				}

				if (!empty($difficulty_level))
				{
					$query->where('q.level=' . "'$difficulty_level'");
				}

				if (!empty($question_type))
				{
					$query->where('q.type=' . "'$question_type'");
				}

				// Get the already added questions
				$qquery = $this->_db->getQuery(true);
				$qquery->select('q.question_id');
				$qquery->from('`#__tmt_tests_questions` AS q ');
				$qquery->where('q.test_id =' . $setTestId);
				$query->where('tq.question_id NOT IN(' . $qquery . ')');
				$query->order('RAND()');
				$query->setLimit($limitCount);
				$this->_db->setQuery($query);
				$ruleQuestions = $this->_db->loadColumn();

				foreach ($ruleQuestions as $question)
				{
					$obj              = new stdClass;
					$obj->question_id = $question;
					$obj->test_id     = $setTestId;
					$obj->order       = $i;
					$obj->section_id  = $setsectionId;
					$result           = $this->_db->insertObject('#__tmt_tests_questions', $obj);

					$i++;
				}
			}
		}
	}
}
