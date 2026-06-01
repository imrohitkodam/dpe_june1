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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

jimport('techjoomla.common');

use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Component\ComponentHelper;

/**
 * Methods supporting a list of Tjlms records.
 *
 * @since  1.0.0
 */
class TjlmsModelAttemptreport extends ListModel
{
	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @since   1.0.0
	 */
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'id',
				'lt.id',
				'title',
				'l.title',
				'coursefilter',
				'lessonfilter',
				'statusfilter',
				'attemptState',
				'userfilter',
				'attempt_starts',
				'attempt_ends'
			);
		}

		$this->ComtjlmsHelper = new ComtjlmsHelper;
		$this->techjoomlacommon = new TechjoomlaCommon;

		parent::__construct($config);
	}

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
		// List state information.
		$app = Factory::getApplication();

		// Load the filter state.
		$search = $app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		// Filtering user
		$userfilter = $app->getUserStateFromRequest($this->context . '.filter.userfilter', 'userfilter', 0, 'INT');
		$this->setState('filter.userfilter', $userfilter);

		// Filtering course
		$coursefilter = $app->getUserStateFromRequest($this->context . '.filter.coursefilter', 'coursefilter', 0, 'INT');

		$this->setState('filter.coursefilter', $coursefilter);

		// Filtering lessons
		$lessonfilter = $app->getUserStateFromRequest($this->context . '.filter.lessonfilter', 'lessonfilter', 0, 'INT');
		$this->setState('filter.lessonfilter', $lessonfilter);

		// Filtering status
		$statusfilter = $app->getUserStateFromRequest($this->context . '.filter.statusfilter', 'statusfilter', 0, 'INT');
		$this->setState('filter.statusfilter', $statusfilter);

		// Filtering attempt state
		$attemptState = $app->getUserStateFromRequest($this->context . '.filter.attemptState', 'attemptState', 1, 'INT');
		$this->setState('filter.attemptState', $attemptState);

		parent::populateState('lt.id', 'desc');

		$orderCol = $app->getUserStateFromRequest($this->context . '.filter_order', 'filter_order', 'id', 'cmd');

		if (!empty($orderCol))
		{
			$this->setState('list.ordering', $orderCol);
		}

		$listOrder = $app->getUserStateFromRequest($this->context . 'filter_order_Dir', 'filter_order_Dir');

		if (!in_array(strtoupper($listOrder), array('ASC', 'DESC', '')))
		{
			$listOrder = 'DESC';
		}

		if (!empty($listOrder))
		{
			$this->setState('list.direction', $listOrder);
		}
	}

	/**
	 * Method to get a store id based on model configuration state.
	 *
	 * This is necessary because the model is used by the component and
	 * different modules that might need different sets of data or different
	 * ordering requirements.
	 *
	 * @param   int  $id  A prefix for the store id.
	 *
	 * @return    string        A store id.
	 *
	 * @since    1.0
	 */
	protected function getStoreId($id = '')
	{
		// Compile the store id.
		$id .= ':' . $this->getState('filter.search');
		$id .= ':' . $this->getState('filter.state');

		return parent::getStoreId($id);
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return    JDatabaseQuery
	 *
	 * @since    1.0
	 */
	protected function getListQuery()
	{
		// Create a new query object.
		$db    = $this->getDbo();
		$query = $db->getQuery(true);
		$select_fields = 'lt.*,l.title as lesson_title,l.format,l.ideal_time,l.total_marks, u.name as user_name,u.username as user_username,u.email as
		useremail,c.title';

		$attemptState = $this->getState('filter.attemptState');

		$query->select($this->getState('list.select', $select_fields));
		$query->from($db->qn('#__tjlms_courses', 'c'));
		$query->join('INNER', $db->qn('#__tjlms_lessons', 'l') . ' ON (' . $db->qn('l.course_id') . ' = ' . $db->qn('c.id') . ')');

		if ($attemptState != '' && (int) $attemptState === 0)
		{
			$query->join('INNER', $db->qn('#__tjlms_lesson_track_archive', 'lt') . ' ON (' . $db->qn('lt.lesson_id') . ' = ' . $db->qn('l.id') . ')');
		}
		else
		{
			$query->join('INNER', $db->qn('#__tjlms_lesson_track', 'lt') . ' ON (' . $db->qn('lt.lesson_id') . ' = ' . $db->qn('l.id') . ')');
		}

		$query->join('INNER', $db->qn('#__users', 'u') . ' ON (' . $db->qn('lt.user_id') . ' = ' . $db->qn('u.id') . ')');

		$userfilter = $this->getState('filter.userfilter');

		if ($userfilter)
		{
			$query->where($db->qn('lt.user_id') . ' = ' . $db->q($userfilter));
		}

		$lessonfilter = $this->getState('filter.lessonfilter');

		if ($lessonfilter)
		{
			$query->where($db->qn('l.id') . ' = ' . $db->q($lessonfilter));
		}

		$statusfilter = $this->getState('filter.statusfilter');

		if ($statusfilter)
		{
			$query->where($db->qn('lt.lesson_status') . ' = ' . $db->quote($statusfilter));
		}

		$courseId = $this->getState('filter.coursefilter');

		if ($courseId)
		{
			$query->where($db->qn('c.id') . ' = ' . $db->quote($courseId));
		}
		// For the Date filter
		$attempt_starts = $this->getState('filter.attempt_starts');
		$attempt_starts_time = strtotime($attempt_starts);
		$attempt_starts_date = date("Y-m-d", $attempt_starts_time);

		$attempt_ends = $this->getState('filter.attempt_ends');
		$attempt_ends_time = strtotime($attempt_ends);
		$attempt_ends_date = date("Y-m-d", $attempt_ends_time);

		if (!empty($attempt_starts) and empty($attempt_ends))
		{
			$query->where('DATE(lt.last_accessed_on) >= ' . "'" . $attempt_starts_date . "'");
		}

		if (!empty($attempt_ends) and empty($attempt_starts))
		{
			$query->where('DATE(lt.last_accessed_on) <= ' . "'" . $attempt_ends_date . "'");
		}

		if (!empty($attempt_starts) and !empty($attempt_ends))
		{
			if ($attempt_starts == $attempt_ends)
			{
				$query->where('DATE(lt.last_accessed_on)=' . "'" . $attempt_ends_date . "'");
			}
			else
			{
				$query->where('DATE(lt.last_accessed_on)>=' . "'" . $attempt_starts_date . "'");
				$query->where('DATE(lt.last_accessed_on)<=' . "'" . $attempt_ends_date . "'");
			}
		}
		// Ends here the Date filter

		// Filter the items over the search string if set.
		if ($this->getState('filter.search') !== '' && $this->getState('filter.search') !== null)
		{
			// Escape the search token.
			$search = $db->quote('%' . str_replace(' ', '%', $db->escape(trim($this->getState('filter.search')), true) . '%'));

			// Compile the different search clauses.
			$searches   = array();
			$searches[] = 'l.title LIKE ' . $search;
			$searches[] = 'u.username LIKE ' . $search;
			$searches[] = 'u.name LIKE ' . $search;
			$searches[] = 'u.email LIKE ' . $search;

			// Add the clauses to the query.
			$query->where('(' . implode(' OR ', $searches) . ')');
		}

		// Add the list ordering clause.
		$orderCol  = $this->state->get('list.ordering');
		$orderDirn = $this->state->get('list.direction');

		if ($orderCol && $orderDirn)
		{
			$query->order($db->escape($orderCol . ' ' . $orderDirn));
		}

		return $query;
	}

	/**
	 * To get the records
	 *
	 * @param   INT     $attempt_id     lesson track table ID
	 * @param   STRING  $score          Marks that need to be updated
	 * @param   STRING  $lesson_status  Status that need to be updated
	 *
	 * @return  Object
	 *
	 * @since  1.0.0
	 */
	public function updateAttemptData($attempt_id, $score = '', $lesson_status = '')
	{
		// Get a db connection.
		$db         = Factory::getDbo();
		$trackingHelper = new ComtjlmstrackingHelper;

		$table = Table::getInstance('lessontrack', 'TjlmsTable', array('dbo', $db));
		$table->load(array('id' => (int) $attempt_id));

		$data              = array();
		$data['user_id']   = $table->user_id;
		$data['lesson_id'] = $table->lesson_id;
		$data['attempt']   = $table->attempt;

		if ($lesson_status)
		{
			$data['previous_lesson_status'] = $table->lesson_status;
			$table->lesson_status           = $lesson_status;
		}

		if (is_numeric($score))
		{
			$data['previous_score'] = $table->score;
			$table->score  = $score;
		}

		$table->modified_date = Factory::getDate()->toSQL();
		$table->store();

		$data['attempt_id']    = $attempt_id;
		$data['score']         = $score;
		$data['lesson_status'] = $lesson_status;

		if (is_numeric($score))
		{
			// Trigger on after attempt score update
			Factory::getApplication()->triggerEvent('onAfterLessonAttemptScoreUpdate', array($data));
		}

		if ($lesson_status)
		{
			// Trigger on after attempt status change
			Factory::getApplication()->triggerEvent('onAfterLessonAttemptStatusChange', array($data));
		}

		if ($lesson_status == 'completed' || $lesson_status == 'passed' || $lesson_status == 'failed')
		{
			$TjlmsLessonHelper = new TjlmsLessonHelper;
			$courseObj         = $TjlmsLessonHelper->getLessonColumn($data['lesson_id'], 'course_id');
			$courseId          = $courseObj->course_id;

			if ($data['previous_score'] != 'completed' && $data['previous_score'] != 'passed' && $data['previous_score'] != 'failed')
			{
				$lessonFormat = $TjlmsLessonHelper->getLessonColumn($data['lesson_id'], 'format');

				// TRRIGER FOR SIMPLE LESSON COMPLETION WITHOUT CONSIDERING ATTEMPT GRADING
				PluginHelper::importPlugin('system');

				Factory::getApplication()->triggerEvent('onAfterLessonAttemptEnd', array(
																	$data['lesson_id'],
																	$data['attempt'],
																	$data['user_id'],
																	$lessonFormat->format
																)
									);

				if ($lesson_status == 'completed' || $lesson_status == 'passed')
				{
					$consider_marksFlag = $TjlmsLessonHelper->getLessonColumn($data['lesson_id'], 'consider_marks');

					if ($consider_marksFlag->consider_marks == 1)
					{
						$statusandscore            = $TjlmsLessonHelper->getLessonScorebyAttemptsgrading($data['lesson_id'], $data['user_id']);

						if ($statusandscore->lesson_status == 'completed' || $statusandscore->lesson_status == 'passed')
						{
							// TRRIGER FOR SIMPLE LESSON COMPLETION WITH CONSIDERING ATTEMPT GRADING
							Factory::getApplication()->triggerEvent('onAfterLessonCompletion', array(
																	$data['lesson_id'],
																	$data['attempt'],
																	$data['user_id']
																)
									);
						}
					}

					// CODE TO CHECK FOR COURSE COMPLETION
					$isCourseCompleted = $trackingHelper->checkIfCourseCompletd($data['lesson_id'], $data['attempt'], $data['user_id']);
				}
			}
			elseif ($courseId && TjLms::course($courseId)->certificate_id)
			{
				$trackingHelper->addCourseTrackEntry($courseId, $data['user_id'], $data['lesson_id']);
			}
		}

		return $data;
	}

	/**
	 * Delet attempts
	 *
	 * @param   ARRAY  $cid  array of lesson track id
	 *
	 * @return  true
	 *
	 * @since   1.0.0
	 */
	public function delete($cid)
	{
		$db = Factory::getDbo();

		if (!empty($cid))
		{
			// Delete respected data
			foreach ($cid as $lessonTrackId)
			{
				$query = $db->getQuery(true);
				$query->select('lt.lesson_id,lt.user_id,l.format,lt.attempt');
				$query->from('#__tjlms_lesson_track as lt');
				$query->join('LEFT', '#__tjlms_lessons as l ON l.id=lt.lesson_id');
				$query->where('lt.id=' . $lessonTrackId);
				$db->setQuery($query);
				$attemptData = $db->loadobject();

				if (empty($attemptData))
				{
					$query2 = $db->getQuery(true);
					$query2->select('lta.lesson_id,lta.user_id,l.format,lta.attempt');
					$query2->from('#__tjlms_lesson_track_archive as lta');
					$query2->join('LEFT', '#__tjlms_lessons as l ON l.id=lta.lesson_id');
					$query2->where('lta.lesson_track_id=' . $lessonTrackId);
					$db->setQuery($query2);
					$attemptData = $db->loadobject();
					$attemptData->archive = 1;
				}

				JLoader::register('TjlmsLessonHelper', JPATH_SITE . '/components/com_tjlms/helper/lesson.php');
				$lessonHelper = new TjlmsLessonHelper;
				$attemptList = $lessonHelper->getlesson_total_attempts_done($attemptData->lesson_id, $attemptData->user_id);

				if ($attemptList > $attemptData->attempt)
				{
						$this->setError(Text::_('COM_TJLMS_CANNOT_DELETE_LESSON_TRACK'));

						return false;
				}

				if ($attemptData->format == 'quiz' || $attemptData->format == 'exercise' || $attemptData->format == 'feedback')
				{
					// Delete data from tmt_test_answer and tmt_test_atendees
					$deleteTestAttemptDataForQuiz = $this->deleteTestAttemptDataForQuiz($lessonTrackId);
				}
				elseif ($attemptData->format == 'scorm')
				{
					$lessonId = $attemptData->lesson_id;
					$userId = $attemptData->user_id;
					$attempt = $attemptData->attempt;

					// Delete data from tmt_test_answer and tmt_test_atendees
					$deleteTestAttemptDataScorm = $this->deleteTestAttemptDataScorm($lessonTrackId, $lessonId, $userId, $attempt);
				}

				if ($attemptData->archive)
				{
					$query = $db->getQuery(true);
					$query->delete($db->quoteName('#__tjlms_lesson_track_archive'));
					$query->where($db->quoteName('id') . '=' . $lessonTrackId);

					$db->setQuery($query);
				}
				else
				{
					$query = $db->getQuery(true);
					$query->delete($db->quoteName('#__tjlms_lesson_track'));
					$query->where($db->quoteName('id') . '=' . $lessonTrackId);

					$db->setQuery($query);
				}


				if (!$db->execute())
				{
						$this->setError($this->_db->getErrorMsg());

						return false;
				}

				// Trigger on after attempt/s delete
				Factory::getApplication()->triggerEvent('onAfterLessonAttemptDelete', array($attemptData));
			}
		}

		return true;
	}

	/**
	 * Delete all scorm related data for an attempt.
	 *
	 * @param   INT  $lessonTrackId  Track ID
	 * @param   INT  $lessonId       Lesson ID
	 * @param   INT  $userId         User ID
	 * @param   INT  $attempt        Attempt number
	 *
	 * @return  boolean
	 *
	 * @since  1.0.0
	 */
	public function deleteTestAttemptDataScorm($lessonTrackId, $lessonId, $userId, $attempt)
	{
		$db = Factory::getDbo();

		// Get scorm id
		$query = $db->getQuery(true);
		$query->select('id');
		$query->from('#__tjlms_scorm');
		$query->where('lesson_id=' . $lessonId);
		$db->setQuery($query);
		$scormId = $db->loadresult();

		// Delete data as per scorm Id , user Id and attempt
		if ($scormId)
		{
			$db = Factory::getDbo();

			$query = $db->getQuery(true);

			// Delete all test_answer as selected
			$conditions = array(
				$db->quoteName('userid') . ' = ' . $userId,
				$db->quoteName('attempt') . ' = ' . $attempt,
				$db->quoteName('scorm_id') . ' = ' . $scormId
			);

			$query->delete($db->quoteName('#__tjlms_scorm_scoes_track'));
			$query->where($conditions);

			$db->setQuery($query);

			if (!$db->execute())
			{
					$this->setError($this->_db->getErrorMsg());

					return false;
			}
		}
	}

	/**
	 * Delete all test related data for an attempt.
	 *
	 * @param   INT  $lessonTrackId  Track ID
	 *
	 * @return  boolean
	 *
	 * @since  1.0.0
	 */
	public function deleteTestAttemptDataForQuiz($lessonTrackId)
	{
		$db = Factory::getDbo();

		$query = $db->getQuery(true);

		// Delete all test_answer as selected
		$conditions = array(
			$db->quoteName('invite_id') . ' = ' . $lessonTrackId
		);

		$query->delete($db->quoteName('#__tmt_tests_answers'));
		$query->where($conditions);

		$db->setQuery($query);

		if (!$db->execute())
		{
				$this->setError($this->_db->getErrorMsg());

				return false;
		}

		// Delete test_attendees
		$query = $db->getQuery(true);
		$query->delete($db->quoteName('#__tmt_tests_attendees'));
		$query->where($conditions);

		$db->setQuery($query);

		if (!$db->execute())
		{
				$this->setError($this->_db->getErrorMsg());

				return false;
		}

		return true;
	}

	/**
	 * Update course track entry
	 *
	 * @param   INT  $attemptId  attempt Id
	 *
	 * @return  boolean
	 *
	 * since  1.0.0
	 */
	public function updateCouserTrack($attemptId)
	{
		$db = Factory::getDbo();

		// Get scorm id
		$query = $db->getQuery(true);
		$query->select('l.course_id,lt.user_id');
		$query->from('#__tjlms_lesson_track as lt');
		$query->join('LEFT', '#__tjlms_lessons as l On l.id=lt.lesson_id');
		$query->where('lt.id=' . $attemptId);
		$db->setQuery($query);
		$data = $db->loadobject();

		if ($data)
		{
			$TjlmsCoursesHelper = new TjlmsCoursesHelper;
			$courseProgress     = $TjlmsCoursesHelper->getCourseProgress($data->course_id, $data->user_id);

			if ($courseProgress)
			{
				$table = Table::getInstance('Coursetrack', 'TjlmsTable', array('dbo', $db));
				$table->load(array('course_id' => (int) $data->course_id, 'user_id' => (int) $data->user_id));

				if ($courseProgress['status'] == 'C')
				{
					$table->timeend = Factory::getDate()->toSQL();

					PluginHelper::importPlugin('system');
					Factory::getApplication()->triggerEvent('onAfterCourseCompletion',
								array(
									$data->user_id,
									$data->course_id
								)
							);
				}

				if (!$table->id)
				{
					$table->course_id = (int) $data->course_id;
					$table->user_id   = (int) $data->user_id;
				}

				$table->no_of_lessons     = $courseProgress['totalLessons'];
				$table->completed_lessons = $courseProgress['completedLessons'];
				$table->status            = $courseProgress['status'];
				$table->store();

				return $table->id;
			}
		}

		return false;
	}

	/**
	 * get items
	 *
	 * @return  JDatabaseQuery
	 *
	 * @since   1.0.0
	 */
	public function getItems()
	{
		$items = parent::getItems();

		$lmsparams        = ComponentHelper::getParams('com_tjlms');
		$date_format_show = $lmsparams->get('date_format_show', 'Y-m-d H:i:s');

		foreach ($items as $ind => $obj)
		{
			$obj->last_accessed_on = $this->techjoomlacommon->getDateInLocal($obj->last_accessed_on, 0, $date_format_show);
			$obj->modified_date    = $this->techjoomlacommon->getDateInLocal($obj->modified_date, 0, $date_format_show);

			$secs = floor($obj->ideal_time * 60);

			$obj->ideal_time = gmdate("H:i:s", $secs);

			if ($obj->format == 'quiz' || $obj->format == 'exercise' || $obj->format == 'feedback')
			{
				$testData = $this->ComtjlmsHelper->getTestData($obj->lesson_id, $obj->user_id);
				$obj->test_id = $testData->test_id;
				$obj->type = (!empty($testData->type)?$testData->type:0);
			}
		}

		return $items;
	}

	/**
	 * Archive attempts
	 *
	 * @param   ARRAY  $ltIds  array of lesson track id
	 *
	 * @return  true
	 *
	 * @since   1.4.0
	 */
	public function archiveAttempts($ltIds)
	{
		if (!empty($ltIds))
		{
			$db    = Factory::getDBO();
			$query = $db->getQuery(true);
			$query->select('lt.*');
			$query->from($db->quoteName('#__tjlms_lesson_track', 'lt'));
			$query->where('lt.id IN (' . implode(',', $ltIds) . ')');
			$db->setQuery($query);
			$lessonTrackData = $db->loadObjectList();

			// Insert archive attempts
			foreach ($lessonTrackData as $data)
			{
				$insertObj                  = $data;
				$insertObj->lesson_track_id = $data->id;
				$insertObj->archive_date    = Factory::getDate()->toSQL();
				$db->insertObject('#__tjlms_lesson_track_archive', $insertObj);
			}

			// Archive scorm attempts
			$lessonsId = array_column($lessonTrackData, 'lesson_id');
			$usersId   = array_column($lessonTrackData, 'user_id');

			JLoader::import('components.com_tjlms.models.lessontrack', JPATH_SITE);
			$lessonTrackmodel = BaseDatabaseModel::getInstance('lessonTrack', 'tjlmsModel', array('ignore_request' => true));
			$lessonTrackmodel->archiveScormAttempts($lessonsId, $usersId);

			// Delete archive attempts from lesson track table.
			$lessonTrackIds = array_column($lessonTrackData, 'id');
			$db             = Factory::getDbo();
			$query          = $db->getQuery(true)
			->delete($db->quoteName('#__tjlms_lesson_track'))
			->where('id IN (' . implode(',', $lessonTrackIds) . ')');
			$db->setQuery($query);
			$db->execute();

			// Update an course status.
			$trackingHelper = new ComtjlmstrackingHelper;

			foreach ($lessonTrackData as $ltdata)
			{
				$courseId = $trackingHelper->getcourseId($ltdata->lesson_id);
				$trackingHelper->addCourseTrackEntry($courseId, $ltdata->user_id);
			}
		}

		return true;
	}
}
