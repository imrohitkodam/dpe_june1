<?php
/**
 * @package    Com_Tmt
 * @copyright  Copyright (C) 2009 -2015 Techjoomla, Tekdi Web Solutions . All rights reserved.
 * @license    GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link       http://www.techjoomla.com
 */
defined('_JEXEC') or die;
jimport('joomla.application.component.modellist');

/**
 * Methods supporting a list of Tmt records.
 *
 * @since  1.0
 */
class TmtModelSinglepaper extends JModelList
{
	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   string  $ordering   An optional ordering field.
	 * @param   string  $direction  An optional direction (asc|desc).
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		// Initialise variables.
		$app = JFactory::getApplication();

		// List state information
		$limit = $app->getUserStateFromRequest('global.list.limit', 'limit', $app->getCfg('list_limit'));
		$this->setState('list.limit', $limit);
		$limitstart = JFactory::getApplication()->input->getInt('limitstart', 0);
		$this->setState('list.start', $limitstart);
		$orderCol = $app->input->get('filter_order', 'a.ordering');

		if (!in_array($orderCol, $this->filter_fields))
		{
			$orderCol = 'a.ordering';
		}

		$this->setState('list.ordering', $orderCol);
		$listOrder = $app->input->get('filter_order_Dir', 'ASC');

		if (!in_array(strtoupper($listOrder), array( 'ASC', 'DESC', '' )))
		{
			$listOrder = 'ASC';
		}

		$this->setState('list.direction', $listOrder);
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return  JDatabaseQuery
	 *
	 * @since  1.0
	 */
	protected function getListQuery()
	{
		// Create a new query object.
		$app = JFactory::getApplication();
		$test_id = $app->input->get('test_id', 0, 'INT');
		$candi_id = $app->input->get('candi_id', 0, 'INT');
		$invite_id = $app->input->get('invite_id', 0, 'INT');
		$db = $this->getDbo();
		$query = $db->getQuery(true);
		$user = JFactory::getUser();
		$uid = $user->id;
		$companyuser = "";

		// Select the required fields from the table.
		$query->select($this->getState('list.select', 'a.*'));
		$query->from('`#__tmt_tests_questions` AS a');
		$query->select('tq.title AS que_title,tq.marks,tq.type');
		$query->join('LEFT', '#__tmt_questions AS tq ON tq.id=a.question_id');
		$query->select('ta.id as ansid, ta.answer,ta.is_correct,ta.order');
		$query->join('LEFT', '#__tmt_answers AS ta ON ta.question_id=a.question_id');
		$query->select('tta.answer AS candidate_ans,tta.id AS test_ans_id,tta.marks AS candidate_marks');
		$query->join('LEFT', '#__tmt_tests_answers AS tta ON tta.question_id=a.question_id');

		// $query->where('ta.question_id=tq.id');
		$query->where('tta.question_id=tq.id');
		$query->where('tta.test_id=a.test_id');

		// $query->where("tq.type IN ('text','textarea')");

		// $query->where('ta.is_correct=1');
		$query->where('a.test_id = ' . $test_id);
		$query->where('tta.user_id = ' . $candi_id);
		$query->where('tta.invite_id = ' . $invite_id);

		// $query->group('ta.question_id');

		// $query->where("a.created_by IN (".$companyuser.")");

		// $query->order($this->getState('list.ordering', 'a.ordering') . ' ' . $this->getState('list.direction', 'DESC'));

		return $query;
	}

	/**
	 * Method to get items.
	 *
	 * @return  mixed
	 *
	 * @since  1.0
	 */
	public function getItems()
	{
		$items = parent::getItems();

		return $items;
	}

	/**
	 * Method to save the form data.
	 *
	 * @param   array  $items  The form data.
	 *
	 * @return  void
	 *
	 * @since  1.0
	 */
	public function save($items)
	{
		$finalmarks = 0;
		$db = JFactory::getDbo();
		$app = JFactory::getApplication();
		$user = JFactory::getUser();
		$uid = $user->id;

		if ($app->input->get('id'))
		{
			$test_id = $app->input->get('id', 0, 'INT');
		}
		elseif ($app->input->get('test_id'))
		{
			$test_id = $app->input->get('test_id', 0, 'INT');
		}

		if ($app->input->get('candi_id'))
		{
			$candi_id = $app->input->get('candi_id', 0, 'INT');
		}
		else
		{
			$candi_id = $uid;
		}

		if ($app->input->get('review_status'))
		{
			$review_status = $app->input->get('review_status', 0, 'INT');
		}
		else
		{
			$review_status = 0;
		}

		$invite_id = $app->input->get('invite_id', 0, 'INT');
		$tmtTestsHelper = new tmtTestsHelper;
		$isObjective = $tmtTestsHelper->getObjectiveTest($test_id);

		for ($i = 0;$i < $items->get('count');$i++)
		{
			$test_ans_id = $items->get('test_ans_id' . $i);
			$result = $items->get('result' . $i);
			$test_que = $items->get('test_que' . $i);
			$que_marks = $items->get('que_marks' . $i);

			$fields = array(
				$db->quoteName('marks') . ' = ' . $db->quote($items->get('candi_marks' . $i))
			);

			$conditions = array(
				$db->quoteName('id') . ' = ' . (int) $test_ans_id,
				$db->quoteName('invite_id') . ' = ' . $invite_id,
				$db->quoteName('user_id') . ' = ' . $candi_id,
			);

			$query = $db->getQuery(true);
			$query->update($db->quoteName('#__tmt_tests_answers'))->set($fields)->where($conditions);

			$db->setQuery($query);
			$result = $db->query();
		}

		/* Descripative Ques*/
		$query = $db->getQuery(true);
		$query->select('sum(marks)');
		$query->from('#__tmt_tests_answers');
		$query->where('test_id = ' . $test_id);
		$query->where('user_id = ' . $candi_id);
		$query->where('invite_id = ' . $invite_id);
		$db->setQuery($query);
		$sum = $db->loadResult();

		$query = $db->getQuery(true);
		$query->select('distinct tq.id AS question_id');
		$query->from('#__tmt_tests_questions as t');
		$query->join('inner', '#__tmt_questions as tq on t.question_id = tq.id');
		$query->where('t.test_id = ' . $test_id);
		$query->where("tq.type NOT IN ('text','textarea')");
		$db->setQuery($query);
		$orgData = $db->loadObjectList();

		$object = array();

		foreach ($orgData as $val)
		{
			$query = $db->getQuery(true);
			$query->select('ta.id AS ans_id,ta.marks,ta.is_correct');
			$query->from('#__tmt_answers as ta');
			$query->where('ta.is_correct = 1');
			$query->where('ta.question_id = ' . (int) $val->question_id);
			$db->setQuery($query);
			$queData = $db->loadObjectList();

			foreach ($queData as $que)
			{
				$object[$val->question_id][] = $que;
			}
		}

		$query = $db->getQuery(true);
		$query->select('tta.question_id,tta.user_id,tta.answer,tq.marks AS quemarks');
		$query->from('#__tmt_tests_answers AS tta');
		$query->join('inner', '#__tmt_questions AS tq on tta.question_id = tq.id');
		$query->where('test_id = ' . (int) $test_id);
		$query->where('user_id = ' . (int) $candi_id);
		$query->where('invite_id = ' . (int) $invite_id);
		$query->where('tta.question_id=tq.id');
		$query->where('tq.type NOT IN ("text","textarea")');
		$db->setQuery($query);
		$res = $db->loadObjectList();

		$marktotal = "";

		foreach ($res as $val)
		{
			$candians = json_decode($val->answer);
			$v = $object[$val->question_id];

			if ($v)
			{
				foreach ($v as $comp)
				{
					if (in_array($comp->ans_id, $candians))
					{
						$marktotal = $marktotal + $comp->marks;
					}
				}
			}
		}

		$finalmarks = $sum + $marktotal;

		$query = $db->getQuery(true);
		$query->select('passing_marks,total_marks, notify_candidate_passed , notify_candidate_failed');
		$query->from('#__tmt_tests');
		$query->where('id = ' . (int) $test_id);
		$db->setQuery($query);
		$testpassingmarks = $db->loadObject();

		$passing_marks = $testpassingmarks->passing_marks;
		$notify_candidate_failed = $testpassingmarks->notify_candidate_failed;
		$notify_candidate_passed = $testpassingmarks->notify_candidate_passed;

		if ($passing_marks <= $finalmarks)
		{
			$resultstatus = 1;
		}
		else
		{
			$resultstatus = 0;
		}

		// Vishal - for objective test checking
		if ($isObjective)
		{
			$review_status = 1;
		}

		// End

		$fields = array(
			$db->quoteName('score') . ' = ' . $db->quote($finalmarks),
			$db->quoteName('review_status') . ' = ' . $db->quote($review_status),
			$db->quoteName('result_status') . ' = ' . $db->quote($resultstatus),
		);

		$conditions = array(
			$db->quoteName('test_id') . ' = ' . (int) $test_id,
			$db->quoteName('invite_id') . ' = ' . $invite_id,
			$db->quoteName('user_id') . ' = ' . $candi_id,
		);

		$query = $db->getQuery(true);
		$query->update($db->quoteName('#__tmt_tests_attendees'))->set($fields)->where($conditions);
		$db->setQuery($query);
		$result = $db->query();

		/* Quiz start*/

		$lesson_status = 'incomplete';

		if ($review_status == 1 && $resultstatus == 1)
		{
			$lesson_status = 'passed';
		}
		elseif ($review_status == 1 && $resultstatus == 0)
		{
			$lesson_status = 'failed';
		}
		elseif ($review_status == 0)
		{
			$lesson_status = 'incomplete';
		}
		else
		{
			$lesson_status = 'completed';
		}

		require_once JPATH_SITE . '/components/com_tjlms/helpers/tracking.php';
		$comtjlmstrackingHelper = new comtjlmstrackingHelper;

		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('l.*');
		$query->from('#__tjlms_lesson_track as l');
		$query->where('l.id=' . $invite_id);
		$db->setQuery($query);
		$quizData = $db->loadObject();

		$trackObj = new stdClass;
		$trackObj->current_position = $quizData->current_position;
		$trackObj->total_content = $quizData->total_content;
		$trackObj->time_spent = '';
		$trackObj->attempt = $quizData->attempt;
		$trackObj->score = $finalmarks;
		$trackObj->lesson_status = $lesson_status;
		$trackingData = $comtjlmstrackingHelper->update_lesson_track($quizData->lesson_id, JFactory::getUser()->id, $trackObj);
		/* Quiz end*/

		return;
	}
}
