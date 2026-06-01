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

use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\MVC\Model\FormModel;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 * Tmt answersheet model.
 *
 * @since  1.6
 */
class TmtModelAnswersheet extends FormModel
{
	protected $item = null;

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @since    1.6
	 *
	 * @return populate state
	 */
	protected function populateState()
	{
		$app   = Factory::getApplication();
		$input = $app->input;

		// Get limit value from component params
		$params                = ComponentHelper::getParams('com_tjlms');
		$test_pagination_limit = $params->get('test_pagination_limit', 5);

		if ($test_pagination_limit < 1)
		{
			$test_pagination_limit = 9999;
		}

		$limit = $input->getInt('l', $test_pagination_limit);
		$this->setState('qlist.limit', $limit);

		$limitstart = $input->getInt('ls', 0);
		$this->setState('qlist.limitstart', $limitstart);

		// Load state from the request userState on edit or from the passed variable on default
		if ($input->get('view') == 'test')
		{
			$id = $input->get('id');
			$app->setUserState('test.id', $id);
		}

		$inviteId = $input->get("ltId", "", "INT");
		$reviewId = $input->get('reviewId', 0, 'INT');

		if (!$inviteId && $reviewId)
		{
			$assessModel     = BaseDatabaseModel::getInstance('assessments', 'TjlmsModel');
			$assessment_data = $assessModel->getLessonTrack($reviewId);
			$inviteId        = $assessment_data[0];
		}

		$this->setState('invite_id', $inviteId);
	}

	/**
	 * Method to get an ojbect.
	 *
	 * @param   integer  $id  of the object to get.
	 *
	 * @return  mixed    Object on success, false on failure.
	 */
	public function getData($id = null)
	{
		$inviteId = $this->getState('invite_id');

		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjlms/tables');
		$lessonTrack = Table::getInstance('Lessontrack', 'TjlmsTable');
		$lessonTrack->load($inviteId);

		$this->setState('candid_id', $lessonTrack->user_id);

		$id           = $this->getTestIdFromLessonTrack($lessonTrack->id);
		$table        = $this->getTable("test", "TmtTable");

		/* Check if test exists and is published*/
		if (!$table->load(array("id" => $id, "state" => 1)))
		{
			return false;
		}

		// Convert the Table to a clean JObject.
		$properties = $table->getProperties(1);
		$this->item = ArrayHelper::toObject($properties, 'JObject');

		$userId    = $lessonTrack->user_id;
		JLoader::import('test', JPATH_SITE . '/components/com_tmt/models');
		$testModel = BaseDatabaseModel::getInstance('test', 'TmtModel');
		$testData  = $testModel->getTestData($lessonTrack->id, $this->item->id, $userId);
		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tmt/models', 'TmtModel');
		$model = BaseDatabaseModel::getInstance('TestAttendees', 'TmtModel', array('ignore_request' => true));
		$model->setState('filter.invite_id', $lessonTrack->id);
		$attendeeData = $model->getItems();

		$this->item->questions       = $testData->questions;
		$this->item->isObjective     = $testData->isObjective;
		$this->item->attempted_count = $testData->attempted_count;
		$this->item->invite_id       = $testData->invite_id;
		$this->item->time_spent      = $testData->time_spent;
		$this->item->attempt_status  = $testData->attempt_status;
		$this->item->q_count         = count($this->item->questions);
		$this->item->test_attendee   = $attendeeData[0]->user_id;

		return $this->item;
	}

	/**
	 * Method to get table
	 *
	 * @param   integer  $type    type of table
	 * @param   integer  $prefix  name of the table.
	 * @param   array    $config  array
	 *
	 * @return mixed    Object on success, false on failure.
	 */
	public function getTable($type = 'Test', $prefix = 'TmtTable', $config = array())
	{
		$this->addTablePath(JPATH_ADMINISTRATOR . '/components/com_tmt/tables');

		return Table::getInstance($type, $prefix, $config);
	}

	/**
	 * Method to get the profile form.
	 *
	 * The base form is loaded from XML
	 *
	 * @param   array    $data      An optional array of data for the form to interogate.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  Form     A JForm object on success, false on failure
	 *
	 * @since    1.6
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm('com_tmt.test', 'test', array('control' => 'jform', 'load_data' => $loadData));

		if (empty($form))
		{
			return false;
		}

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @param   integer  $id  to get category name
	 *
	 * @return    mixed       The data for the form.
	 *
	 * @since    1.6
	 *
	 * @deprecated  1.4.0  This function will be removed no replacement will be provided
	 */
	public function getCategoryName($id)
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select('title')->from('#__categories')->where('id = ' . (int) $id);
		$db->setQuery($query);

		return $db->loadObject();
	}

	/**
	 * Method to update marks and lesson status after review.
	 *
	 * @param   mixed  $data     post data
	 * @param   int    $isfinal  post data
	 *
	 * @return  boolean  true/false
	 *
	 * @since    1.6
	 */
	public function update_lesson_test_quiz($data, $isfinal)
	{
		$marks       = $data->get('marks', '', 'ARRAY');
		$invId       = $data->get('invite_id', '', 'INT');
		$user_id     = $data->get('candid_id', '', 'INT');
		$test_id     = $data->get('id', '', 'INT');
		$gradingtype = $data->get('gradingtype', '', 'STRING');
		$subtotal    = 0;
		$db          = Factory::getDbo();

		// Update marks for each question's answer
		foreach ($marks as $que => $marks)
		{
			$query = $db->getQuery(true);

			$fields = array(
				$db->quoteName('marks') . ' = ' . $db->quote($marks)
			);

			$conditions = array(
				$db->quoteName('invite_id') . '   = ' . $db->quote($invId),
				$db->quoteName('question_id') . ' = ' . $db->quote($que)
			);

			$query->update($db->quoteName('#__tmt_tests_answers'))->set($fields)->where($conditions);
			$db->setQuery($query);
			$db->execute();

			$subtotal = $subtotal + $marks;
		}

		// Get score of objective questions
		$query = $db->getQuery(true);
		$query->select($db->quoteName('score'));
		$query->from($db->quoteName('#__tjlms_lesson_track'));
		$query->where($db->quoteName('id') . '=' . $db->quote($invId));
		$db->setQuery($query);

		$result = $db->loadResult($query);

		// Calculate total obtained marks
		$gained_marks = $result + $subtotal;

		$this->update_lesson_test($invId, $user_id, $test_id, $isfinal, $gained_marks, $gradingtype);

		return true;
	}

	/**
	 * Method to update marks and lesson status after review.
	 *
	 * @param   mixed  $data     post data
	 * @param   int    $isfinal  post data
	 *
	 * @return  boolean  true/false
	 *
	 * @since    1.6
	 */
	public function update_lesson_test_exercise($data, $isfinal)
	{
		$invId       = $data->get('invite_id', '', 'INT');
		$user_id     = $data->get('candid_id', '', 'INT');
		$test_id     = $data->get('id', '', 'INT');
		$marks       = $data->get('marks', '', 'INT');
		$gradingtype = $data->get('gradingtype', '', 'STRING');

		$this->update_lesson_test($invId, $user_id, $test_id, $isfinal, $marks, $gradingtype);

		return true;
	}

	/**
	 * Method to update marks and lesson status after review.
	 *
	 * @param   int     $invId        invite id
	 * @param   int     $user_id      user id
	 * @param   int     $test_id      id of test
	 * @param   int     $isfinal      post data
	 * @param   int     $marks        obtained marks
	 * @param   string  $gradingtype  quiz type
	 *
	 * @return  boolean  true/false
	 *
	 * @since    1.6
	 */
	public function update_lesson_test($invId, $user_id, $test_id, $isfinal,$marks, $gradingtype)
	{
		$db = Factory::getDbo();

		// Get passing marks for test
		$query = $db->getQuery(true);
		$query->select($db->quoteName('passing_marks'));
		$query->from($db->quoteName('#__tmt_tests'));
		$query->where($db->quoteName('id') . '=' . $db->quote($test_id));
		$db->setQuery($query);
		$passing_marks = $db->loadResult($query);

		if ($marks >= $passing_marks)
		{
			$lesson_status = 'passed';
		}
		else
		{
			$lesson_status = 'failed';
		}

		if ($isfinal == 1)
		{
			$query = $db->getQuery(true);

			$fields = array(
				$db->quoteName('modified_by') . '   = ' . $db->quote(Factory::getUser()->id),
				$db->quoteName('score') . '         = ' . $db->quote($marks),
				$db->quoteName('lesson_status') . ' = ' . $db->quote($lesson_status)
			);

			$conditions = array(
				$db->quoteName('id') . ' = ' . $db->quote($invId),
			);

			$query->update($db->quoteName('#__tjlms_lesson_track'))->set($fields)->where($conditions);
			$db->setQuery($query);
			$db->execute();
		}

		if ($isfinal == 0 && $gradingtype == 'exercise')
		{
			$query = $db->getQuery(true);

			$fields = array(
				$db->quoteName('score') . ' = ' . $db->quote($marks),
			);

			$conditions = array(
				$db->quoteName('id') . ' = ' . $db->quote($invId),
			);

			$query->update($db->quoteName('#__tjlms_lesson_track'))->set($fields)->where($conditions);
			$db->setQuery($query);
			$db->execute();
		}

		return true;
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
}
