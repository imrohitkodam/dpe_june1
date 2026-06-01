<?php
/**
 * @package     Shika
 * @subpackage  com_tjlms
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 * Installer Database Controller
 *
 * @since  2.5
 */
class TjlmsControllerDatabase extends BaseController
{
	/**
	 * Tries to fix missing database updates
	 *
	 * @return  void
	 *
	 * @since   2.5
	 * @todo    Purge updates has to be replaced with an events system
	 */
	public function fix()
	{
		// Get a handle to the Joomla! application object
		$application = Factory::getApplication();

		/*Fix course alias*/
		$this->fixAlias('#__tjlms_courses', 'Course');

		/*Fix lesson alias*/
		$this->fixAlias('#__tjlms_lessons', 'Lesson', 'title');

		$model = $this->getModel('database');
		$model->fix();

		// Purge updates
		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_joomlaupdate/models', 'JoomlaupdateModel');
		$updateModel = BaseDatabaseModel::getInstance('default', 'JoomlaupdateModel');
		$updateModel->purge();

		// Refresh versionable assets cache
		Factory::getApplication()->flushAssets();

		$this->fixIndexes();
		echo json_encode(1);
		jexit();
	}

	/**
	 * Tries to fix missing database updates
	 *
	 * @return  void
	 *
	 * @since   2.5
	 * @todo    Purge updates has to be replaced with an events system
	 */
	/*public function fixColumnIndexes()
	{
		/* Get a handle to the Joomla! application object */
		/*$application = JFactory::getApplication();

		$model = $this->getModel('database');
		$model->fix();

		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_joomlaupdate/models', 'JoomlaupdateModel');
		$updateModel = BaseDatabaseModel::getInstance('default', 'JoomlaupdateModel');
		$updateModel->purge();

		Factory::getApplication()->flushAssets();

		/* $model->fixIgnorekeyIndexes(); */

		/* $model->fixColumnChange();*/

		/*echo json_encode(1);
		jexit();
	}*/

	/**
	 * This is the function to be executed to migrate the database to supprt current version
	 *
	 * @return boolean
	 *
	 * @since  1.0.0
	 */
	public function fixOtherDbChanges()
	{
		// Changes needed to support 1.1

		// 1. Set the parent_id as 0 and type as plain for the tests having type as ''
		$db = Factory::getDbo();
		$query = $db->getQuery(true);

		// Fields to update.
		$fields = array(
			$db->quoteName('parent_id') . ' = 0',
			$db->quoteName('type') . " = 'plain'"
		);

		// Conditions for which records should be updated.
		$conditions = array(
			$db->quoteName('type') . " = ''",
		);

		$query->update($db->quoteName('#__tmt_tests'))->set($fields)->where($conditions);
		$db->setQuery($query);
		$result = $db->execute();

		// 2. Set Grading method as 2 for scorms
		$query = $db->getQuery(true);

		$fields = array(
			$db->quoteName('grademethod') . ' = 2',
		);
		$query->update($db->quoteName('#__tjlms_scorm'))->set($fields);
		$db->setQuery($query);
		$result = $db->execute();

		/* 3. Add the entry in the certificate table for default certificate and add that id as a certificate id for the
		courses having certificate*/

		$model = $this->getModel('database');
		$model->setDefaultCertificate();

		// 5. Delete course track entry where course and user ids are zero(0)
		$query = $db->getQuery(true);
		$conditions = array($db->quoteName('user_id') . ' = 0', $db->quoteName('course_id') . ' = 0');
		$query->delete($db->quoteName('#__tjlms_course_track'))->where($conditions);
		$db->setQuery($query);
		$result = $db->execute();

		echo json_encode(1);
		jexit();
	}

	/**
	 * This is the function to be executed to migrate the All the questions belonging to a quiz to the test_answers
	 * table. After introducing question shuffling we add all the questions in a test in a test_answers table
	 *
	 * @return boolean
	 *
	 * @since  1.0.0
	 */
	public function migrateTests()
	{
		// Get the first quiz created as a set... Ideally after that all functionlity should be according to new code
		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select('id');
		$query->from('#__tmt_tests');
		$query->where("id > 0");
		$db->setQuery($query);
		$test_ids = $db->loadColumn();

		echo json_encode($test_ids);
		jexit();

		// JTable::addIncludePath( JPATH_ROOT . '/administrator/components/com_tmt/tables');

		/*foreach ($test_ids as $test_id)
		{

			$this->migrateTestQuestions($test_id);

			/*$table = JTable::getInstance('Test', 'TmtTable', array('dbo', $db));
			$table->id = $test_id;
			$table->parent_id = 0;
			$table->type = 'plain';

			$table->store();*/
		/*}*/
	}

	/**
	 * This is the function to be executed to migrate the All the questions belonging to a quiz
	 *
	 * @return boolean
	 *
	 * @since  1.0.0
	 */
	public function migrateTestQuestions()
	{
		$input = Factory::getApplication()->input;
		$test_id = $input->get('test_id', '', "INT");

		if (!$test_id)
		{
			return;
		}

		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select('tq.question_id');
		$query->from('#__tmt_tests_questions AS tq');
		$query->where("tq.test_id = " . $test_id);
		$db->setQuery($query);
		$questions[$test_id] = $db->loadColumn();

		$attempts_done = $this->getTestAttemptsDone($test_id);

		if ($attempts_done > 50)
		{
			$limit = 50;

			$chunks = ceil($attempts_done / $limit);

			for ($i = 0; $i < $chunks; $i++)
			{
				$start = $i * $limit;
				$this->migrateQuestions($test_id, $questions, $limit, $start);
			}
		}
		else
		{
			$this->migrateQuestions($test_id, $questions);
		}

		echo json_encode('1');
		jexit();
	}

	/**
	 * Get total attempts done aginst a test
	 *
	 * @param   INT  $test_id  test_od
	 *
	 * @return  boolean
	 *
	 * @since  1.0.0
	 */
	public function getTestAttemptsDone($test_id)
	{
		$db = Factory::getDbo();

		// Get the attempts done against the test id
		$query = $db->getQuery(true);
		$query->select('count(ta.invite_id)');
		$query->from('#__tmt_tests_attendees AS ta');
		$query->where("ta.test_id = " . $test_id);
		$db->setQuery($query);
		$attempts = $db->loadResult();

		return $attempts;
	}

	/**
	 * Actual logic for the migration
	 *
	 * @param   INT  $test_id    test_id
	 * @param   INT  $questions  Questions from a test
	 * @param   INT  $limit      limit
	 * @param   INT  $start      start
	 *
	 * @return  boolean
	 *
	 * @since  1.0.0
	 */
	public function migrateQuestions($test_id, $questions, $limit=100, $start=0)
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select('ta.invite_id,ta.user_id');
		$query->from('#__tmt_tests_attendees AS ta');
		$query->where("ta.test_id = " . $test_id);
		$query->setLimit($limit, $start);
		$db->setQuery($query);
		$attempts = $db->loadObjectList();

		if (!empty($attempts))
		{
			foreach ($attempts as $attempt)
			{
				$query = $db->getQuery(true);
				$query->select('ta.question_id');
				$query->from('#__tmt_tests_answers AS ta');
				$query->where("ta.invite_id = " . $attempt->invite_id);
				$query->where("ta.test_id = " . $test_id);
				$db->setQuery($query);
				$answerered_questions = $db->loadColumn();

				$temp_answers[$test_id . '-' . $attempt->invite_id . '-' . $attempt->user_id] = $answerered_questions;

				/*if (!empty($answers_data))
				{
					foreach ($answers_data as $invites)
					{
						$temp_answers[$invites->test_id .'-'. $invites->invite_id.'-'. $invites->user_id][] = $invites->question_id;
					}
				}*/
			}
		}

		if (!empty($temp_answers))
		{
			foreach ($temp_answers as $invkey => $answers)
			{
				$temp = explode('-', $invkey);
				$inv_test_id = $temp[0];
				$inv_invite_id = $temp[1];
				$inv_user_id = $temp[2];

				$diff_result = array_diff($questions[$inv_test_id], $answers);

				if (!empty($diff_result))
				{
					foreach ($diff_result as $res)
					{
						$insert_obj = new stdClass;
						$insert_obj->question_id = $res;
						$insert_obj->test_id = $inv_test_id;
						$insert_obj->invite_id = $inv_invite_id;
						$insert_obj->user_id = $inv_user_id;
						$insert_obj->answer = '-';
						$result = $db->insertObject('#__tmt_tests_answers', $insert_obj);
					}
				}
			}
		}
	}

	/**
	 * Tries to add missing indexes to the tables
	 *
	 * @return  void
	 *
	 * @since   2.5
	 */
	public function fixCourseAlias()
	{
		/*Fix course alias*/
		$model = $this->getModel('database');
		$model->fixAlias('#__tjlms_courses', 'Course');
		echo json_encode('1');
		jexit();
	}

	/**
	 * Tries to add missing indexes to the tables
	 *
	 * @return  void
	 *
	 * @since   2.5
	 */
	public function fixLessonAlias()
	{
		/*Fix lesson alias*/
		$model = $this->getModel('database');
		$model->fixAlias('#__tjlms_lessons', 'Lesson', 'title');

		echo json_encode('1');
		jexit();
	}

	/**
	 * Migrate course track table from old to new. Now we have added total lessons, passed lessons and status columns
	 *
	 * @return  boolean
	 *
	 * @since  1.1
	 */
	public function migrateCourseTrack()
	{
		/*Fix lesson alias*/
		$model = $this->getModel('database');
		$model->migrateCourseTrack();

		echo json_encode('1');
		jexit();
	}

	/**
	 * This function remove unused lesson files from com_tjlms media folder
	 *
	 * @return message
	 *
	 * @since  1.1
	 *
	 */
	public function removeOrphanedLessonFiles()
	{
		// Remove orphaned/unused lesson's files
		require_once JPATH_SITE . '/components/com_tjlms/models/lesson.php';
		$tjlmsModellesson = new TjlmsModellesson;
		$tjlmsModellesson->removeUnusedLessonFiles();

		echo json_encode('1');
		jexit();
	}

	/**
	 * This function update the certificate old tags with new onces
	 *
	 * @return message
	 *
	 * @since  1.1
	 *
	 */
	public function updateCertificateTags()
	{
		require_once JPATH_ADMINISTRATOR . '/components/com_tjlms/models/certificates.php';
		$oldTags = array('[DATE_OF_COMPLETION]', '[DATED]', '[DATE]');
		$newTags = array('[GRANTED_DATE]', '[GRANTED_DATE]', '[GRANTED_DATE]');
		$tjlmsModelCertificates = new TjlmsModelCertificates;
		$tjlmsModelCertificates->updateTags($oldTags, $newTags);

		echo json_encode('1');
		jexit();
	}

	/**
	 * Create Reminder templates if does not exist
	 *
	 * @return  void
	 *
	 * @since   2.5
	 */
	public function addReminderTemplates()
	{
		$model = $this->getModel('database');
		$model->addReminderTemplates();

		echo json_encode(1);
		jexit();
	}
}
