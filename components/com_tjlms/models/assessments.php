<?php
/**
 * @package     Shika
 * @subpackage  com_tjlms
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        http://www.techjoomla.com
 */
// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Table\Table;

jimport('joomla.event.dispatcher');
jimport('joomla.application.component.modelform');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Registry\Registry;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 * Methods for lesson.
 *
 * @since  1.0.0
 */
class TjlmsModelAssessments extends ListModel
{
	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @since   1.6
	 * @see     JController
	 */
	public function __construct ($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'lesson','l.title',
				'course','c.title',
				'attempt_starts','lt.timestart',
				'attempt_ends','lt.timeend',
				'status','lt.lesson_status',
				'score','lt.score',
				'name','u.name'
			);
		}

		parent::__construct($config);
	}

	/**
	 * Get lesson tracks of all lesson of course
	 *
	 * @return  Object list
	 *
	 * @since 1.2
	 * */
	public function getLessonTracksofAllLessons()
	{
		$user = Factory::getUser();

		$db = Factory::getDbo();
		$query = $db->getQuery(true);

		// Get lesson_track_ids of all lessons of particular course
		$query->select('lt.id');
		$query->from($db->quoteName('#__tjlms_lesson_track', 'lt'));
		$query->where(
					$db->quoteName('lt.lesson_status')
				. ' IN(' . implode(',', $db->quote(array('AP', 'passed', 'failed'))) . ')');
		$db->setQuery($query);

		$results1 = $db->loadColumn();

		if (!empty($results1))
		{
			// Get lesson_track_ids which are assessed by me.
			$query = $db->getQuery(true);
			$query->select('ar.lesson_track_id');
			$query->from($db->quoteName('#__tjlms_assessment_reviews', 'ar'));
			$query->where($db->quoteName('ar.lesson_track_id') . ' IN(' . implode(',', $db->quote($results1)) . ')');

			$db->setQuery($query);
			$results2 = $db->loadColumn();

			// Get lesson_track_id which are not yet assessed or which reviewed by the login user.
			$query = $db->getQuery(true);
			$query->select('lt.id');
			$query->from($db->quoteName('#__tjlms_lesson_track', 'lt'));

			if ($results2)
			{
				$query->where($db->quoteName('lt.id') . ' NOT IN(' . implode(',', $db->quote($results2)) . ')');
			}

			$query->join('left', $db->quoteName('#__tjlms_lessons', 'l') . 'ON ' . $db->quoteName('lt.lesson_id') . '=' . $db->quoteName('l.id'));
			$query->where($db->quoteName('lt.lesson_status') . ' = ' . $db->quote('AP'));
			/*$query->where($db->quoteName('l.course_id') . ' = ' . $db->quote($course_id));*/
			$conditions = $db->quoteName('lt.modified_by') . ' = ' . $db->quote($user->id);
			$query->extendWhere('OR', $conditions, 'OR');
			$db->setQuery($query);

			$results3 = $db->loadColumn();

			$results = array_merge($results2, $results3);

			return $results;
		}
	}

	/**
	 * Function to get the lesson from couses which user can assess
	 *
	 * @param   ARRAY  $courseswithAccess  ARRAY of courses user can assess
	 *
	 * @return  ARRAY
	 */
	public function getLessonsWithAssessments($courseswithAccess)
	{
		/*Get all lessons for which assessment is added*/
		$query = $this->_db->getQuery(true);
		$query->select(array('l.id'));
		$query->from($this->_db->quoteName('#__tjlms_lessons', 'l'));
		$query->join('left', $this->_db->quoteName('#__tjlms_assessmentset_lesson_xref', 'axref') .
				' ON ' . $this->_db->quoteName('l.id') . '=' . $this->_db->quoteName('axref.lesson_id')
				);
		$query->join('INNER', $this->_db->quoteName('#__tjlms_assessment_set', 'as') .
					'ON ' . $this->_db->quoteName('axref.set_id') . '=' . $this->_db->quoteName('as.id')
					);
		$query->where($this->_db->quoteName('l.course_id') . " IN (" . implode(",", $courseswithAccess) . ")");
		$this->_db->setQuery($query);
		$assessments = $this->_db->loadColumn();

		return $assessments;
	}

	/**
	 * Get all the lessons i.e. tests having subjective questions those user can assess
	 *
	 * @param   ARRAY  $courseswithAccess  ARRAY of courseIds user can access
	 *
	 * @return  ARRAY
	 */
	public function getAllSubjectiveLessons($courseswithAccess)
	{
		// Get the tests with Subjetctive questions first
		$squery = $this->_db->getQuery(true);
		$squery->select(array('t.id'));
		$squery->from($this->_db->quoteName('#__tmt_tests', 't')) .
		$squery->join('left', $this->_db->quoteName('#__tmt_tests_questions', 'tq') .
					'ON ' . $this->_db->quoteName('tq.test_id') . '=' . $this->_db->quoteName('t.id')
					);
		$squery->join('left', $this->_db->quoteName('#__tmt_questions', 'q') .
					'ON ' . $this->_db->quoteName('q.id') . '=' . $this->_db->quoteName('tq.question_id')
					);
		$squery->where($this->_db->quoteName('q.type') . " NOT IN ('radio','checkbox')");
		$squery->where($this->_db->quoteName('t.gradingtype') . " = " . $this->_db->quote("quiz"));

		// Get the lessons from the tests
		$query = $this->_db->getQuery(true);
		$query->select(array('l.id'));
		$query->from($this->_db->quoteName('#__tjlms_lessons', 'l'));
		$query->join("left", $this->_db->quoteName('#__tjlms_media', 'm') . " ON l.media_id=m.id");
		$query->where($this->_db->quoteName('l.format') . " = 'Quiz'");
		$query->where($this->_db->quoteName('m.source') . " IN(" . $squery . ")");
		$query->where($this->_db->quoteName('l.course_id') . " IN (" . implode(",", $courseswithAccess) . ")");
		$this->_db->setQuery($query);
		$subjectives = $this->_db->loadColumn();

		return $subjectives;
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return    JDatabaseQuery
	 *
	 * @since    1.0
	 */
	public function getListQuery()
	{
		// Filter by course
		$filter_course = $this->getState("filter.course");
		$db = $this->_db;
		/*Get ALL the attempts assessed by me or which are yet to be assessed*/
		$lessonTrackList = $this->getLessonTracksofAllLessons();

		$user = Factory::getUser();
		$query = $this->_db->getQuery(true);
		$query->select("c.id");
		$query->from($this->_db->quoteName('#__tjlms_courses', 'c'));
		$query->where("c.state = 1");
		$this->_db->setQuery($query);
		$courses = $this->_db->loadColumn();

		$assessAccess = array();

		foreach ($courses as $c)
		{
			if ($user->authorise('core.assessment', 'com_tjlms.course.' . (int) $c))
			{
				$assessAccess[] = $c;
			}
		}

		$lessonsCanReview = array();

		if (!empty($assessAccess))
		{
			$lessonsCanReview = $this->getLessonsWithAssessments($assessAccess);
		}

		JLoader::import('administrator.components.com_tjlms.helpers.tjlms', JPATH_SITE);
		$hasUsers = TjlmsHelper::getSubusers();

		$query = $this->_db->getQuery(true);
		$query->select(
			$this->getState(
				'list.select', 'lt.id as lessonTrackId,lt.timestart,lt.timeend,
				lt.lesson_status,lt.attempt,lt.score,lt.modified_by,lt.user_id,l.id as lessonId,
				l.title,u.name as user_name,l.format,c.id as courseId, c.title as courseTitle'
			)
		);

		$query->join(
				'left', $db->quoteName('#__users', 'u') .
				'ON ' . $db->quoteName('lt.user_id') . '=' . $db->quoteName('u.id')
			);

		$query->join('left', $db->quoteName('#__tjlms_lessons', 'l') . 'ON ' . $db->quoteName('lt.lesson_id') . '=' . $db->quoteName('l.id'));

		$query->from($db->quoteName('#__tjlms_lesson_track', 'lt'));

		$query->join(
				'left', $db->quoteName('#__tjlms_courses', 'c') .
				'ON ' . $db->quoteName('l.course_id') . '=' . $db->quoteName('c.id')
			);

		if (!empty($lessonTrackList) && !empty($assessAccess))
		{
			$query->where($db->quoteName('lt.id') . ' IN(' . implode(',', $db->quote($lessonTrackList)) . ')');

			if (!empty($lessonsCanReview))
			{
				$query->extendWhere("AND", array($db->quoteName('l.id') . ' IN(' . implode(',', $db->quote($lessonsCanReview)) . ')', 'l.in_lib = 1'), "OR");
			}

			// Filter by search in title
			$search = $this->getState('filter.search');

			if (!empty($search))
			{
				if (stripos($search, 'title:') === 0)
				{
					$query->where('l.title = ' . (int) substr($search, 3));
				}
				else
				{
					$search = $db->Quote('%' . $db->escape($search, true) . '%');
					$query->where('( l.title LIKE ' . $search . ' )');
				}
			}

			// Filter by lesson name
			$filter_lesson = $this->getState("filter.lesson");

			if ($filter_lesson)
			{
				$query->where($db->quoteName('l.id') . ' = ' . $db->quote($filter_lesson));
			}

			// Filter by lesson status
			$filter_status = $this->getState("filter.status");

			$conditions = Array();

			if ($filter_status == 'save')
			{
				$conditions[] = $db->quoteName('lt.lesson_status') . ' <> ' . $db->quote('AP');
				$query->extendWhere('AND', $conditions, 'OR');
			}
			elseif ($filter_status)
			{
				$conditions[] = $db->quoteName('lt.lesson_status') . ' = ' . $db->quote('AP');
				$query->extendWhere('AND', $conditions, 'OR');
			}

			// Filter by attendee
			$filter_user = $this->getState("filter.name");

			if ($filter_user)
			{
				$query->where($db->quoteName('lt.user_id') . ' = ' . $db->quote($filter_user));
			}

			// Filter by course
			$filter_course = $this->getState("filter.course");

			if ($filter_course)
			{
				$query->where($db->quoteName('l.course_id') . ' = ' . $db->quote($filter_course));
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
				$query->where('DATE(lt.timestart)>=' . "'" . $attempt_starts_date . "'");
			}

			if (!empty($attempt_ends) and empty($attempt_starts))
			{
				$query->where('DATE(lt.timeend)<=' . "'" . $attempt_ends_date . "'");
			}

			if (!empty($attempt_starts) and !empty($attempt_ends))
			{
				if ($attempt_starts == $attempt_ends)
				{
					$query->where('DATE(lt.timeend)=' . "'" . $attempt_ends_date . "'");
				}
				else
				{
					$query->where('DATE(lt.timeend)>=' . "'" . $attempt_starts_date . "'");
					$query->where('DATE(lt.timeend)<=' . "'" . $attempt_ends_date . "'");
				}
			}

			// Add the list ordering clause.

			$orderCol = $this->state->get('list.ordering');
			$orderDirn = $this->state->get('list.direction');

			if ($orderCol && $orderDirn)
			{
				$query->order($db->escape($orderCol . ' ' . $orderDirn));
			}

			return $query;
		}
		elseif (!empty($hasUsers))
		{
			$query->where($db->qn('lt.user_id') . 'IN(' . implode(',', $db->q($hasUsers)) . ')');

			return $query;
		}
		else
		{
			$query->where($db->quoteName('lt.id') . " = false");

			return $query;
		}
	}

	/**
	 * Method to get a list of courses.
	 *
	 * @return  mixed  An array of data items on success, false on failure.
	 *
	 * @since   1.0.0
	 */
	public function getItems()
	{
		$items = parent::getItems();

		foreach ($items as $item)
		{
			$item->lessonAssessment = $this->getLessonAssessSet($item->lessonId);

			// Get the users who already reviewed
			$query = $this->_db->getQuery(true);
			$query->select('count(*)');
			$query->from($this->_db->quoteName('#__tjlms_assessment_reviews', 'ar'));
			$query->where($this->_db->quoteName('ar.lesson_track_id') . " = " . (int) $item->lessonTrackId);
			$query->where($this->_db->quoteName('ar.review_status') . " = " . 1);
			$this->_db->setQuery($query);
			$item->livetrackReviews = (int) $this->_db->loadResult();

			/* Check assessment submitted by logged in user*/
			$trackReview = $this->getTable('Assessmentreviews', 'TjlmsTable');
			$trackReview->load(array("lesson_track_id" => $item->lessonTrackId, "reviewer_id" => Factory::getUser()->id));

			$item->trackReview = new stdClass;

			if ($trackReview->id)
			{
				$item->trackReview = $trackReview;
			}

			// Set the student name for Anonymous assessment.
			if (empty($item->lessonAssessment->assessment_student_name))
			{
				$item->user_name = Text::_('COM_TJLMS_ASSESSMENTS_ANONYMOUS_STUDENT');
			}
		}

		return $items;
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   string  $ordering   Elements order
	 * @param   string  $direction  Order direction
	 *
	 * @return void
	 *
	 * @throws Exception
	 *
	 * @since    1.6
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		$app = Factory::getApplication('site');

		$lesson = $app->getUserStateFromRequest($this->context . '.filter.lesson', 'filter_lesson', '0', 'INT');
		$this->setState('filter.lesson', $lesson);

		$course = $app->getUserStateFromRequest($this->context . '.filter.course', 'filter_course', '0', 'INT');
		$this->setState('filter.course', $course);

		$status = $app->getUserStateFromRequest($this->context . '.filter.status', 'filter_status', '', 'INT');
		$this->setState('filter.status', $status);

		$attempt_starts = $app->getUserStateFromRequest($this->context . '.filter.attempt_starts', 'filter_attempt_starts', '', 'INT');
		$this->setState('filter.attempt_starts', $attempt_starts);

		$attempt_ends = $app->getUserStateFromRequest($this->context . '.filter.attempt_ends', 'filter_attempt_ends', '', 'INT');
		$this->setState('filter.attempt_ends', $attempt_ends);

		$limit = $app->getUserStateFromRequest('global.list.limit', 'limit', $app->getCfg('list_limit'));
		$this->setState('list.limit', $limit);

		$limitstart = Factory::getApplication()->input->getInt('limitstart', 0);

		if ($limit == 0)
		{
			$this->setState('list.start', 0);
		}
		else
		{
			$this->setState('list.start', $limitstart);
		}

		$listOrder = $app->getUserStateFromRequest($this->context . '.filter_order_Dir', 'filter_order_Dir');

		if (!in_array(strtoupper($listOrder), array('ASC', 'DESC', '')))
		{
			$listOrder = 'DESC';
		}

		$this->setState('list.direction', $listOrder);

		// List state information.
		parent::populateState('lt.last_accessed_on', 'DESC');
	}

	/**
	 * Method to get a list of courses.
	 *
	 * @return  mixed  An array of data items on success, false on failure.
	 *
	 * @since   1.0.0
	 */
	public function getAssessmentPlugin()
	{
		static $assessLessonPlugin;

		if (!isset($assessLessonPlugin))
		{
			$assessLessonPlugin = Array();
			$lesson_formats_array = array('scorm', 'htmlzips', 'tincanlrs', 'video', 'document', 'textmedia', 'externaltool', 'event',  'survey', 'form');

			foreach ($lesson_formats_array as $format_name)
			{
				$plugformat = 'tj' . $format_name;
				$plugins    = PluginHelper::getPlugin($plugformat);

				if (!empty($plugins))
				{
					foreach ($plugins as $plugin)
					{
						$params     = new Registry($plugin->params);

						$isAssess   = $params->get('assessment', 0);
						$plg_name   = $plugin->name;

						if ($isAssess)
						{
							$assessLessonPlugin[] = $plugin->name;
						}
					}
				}
			}
		}

		return $assessLessonPlugin;
	}

	/**
	 * Function used to get track assessment detail
	 *
	 * @param   INT  $lTrackId     of the lesson_track
	 * @param   INT  $reviewer_id  of the user_id
	 *
	 * @return  Object
	 *
	 * @since  1.0.0
	 *
	 */
	public function getTrackAssessmentDetails($lTrackId, $reviewer_id = null)
	{
		$db     = Factory::getDBO();
		$query  = $db->getQuery(true);

		if (! (int) $reviewer_id)
		{
			$reviewer_id = Factory::getUser()->id;
		}

		$result = array();
		$result['track'] = $this->getTrackDetail($lTrackId);

		if (!empty($result['track']->id))
		{
			$result['ratings'] = $this->getTrackRating($lTrackId, $reviewer_id);
			$result['reviews'] = $this->getTrackReviews($lTrackId, $reviewer_id);
		}
		else
		{
			return false;
		}

		return $result;
	}

	/**
	 * Function used to get track detail
	 *
	 * @param   INT  $lTrackId  of the lesson_track
	 *
	 * @return  Object
	 *
	 * @since  1.0.0
	 *
	 */
	public function getTrackDetail($lTrackId)
	{
		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjlms/tables');
		$lessonTrack = Table::getInstance('Lessontrack', 'TjlmsTable');
		$lessonTrack->load($lTrackId);

		return $lessonTrack;
	}

	/**
	 * Function used to get track rating detail
	 *
	 * @param   INT  $lTrackId     of the lesson_track
	 * @param   INT  $reviewer_id  of the reviewer
	 *
	 * @return  Object
	 *
	 * @since  1.0.0
	 *
	 */
	public function getTrackRating($lTrackId, $reviewer_id = null)
	{
		$db     = Factory::getDBO();
		$query  = $db->getQuery(true);
		$result = array();

		if (! (int) $reviewer_id)
		{
			$reviewer_id = Factory::getUser()->id;
		}

		$query->select('lar.*');
		$query->from($db->quoteName('#__tjlms_lesson_assessment_ratings', 'lar'));
		$query->where($db->quoteName('lar.lesson_track_id') . ' = ' . (int) $lTrackId);
		$query->where($db->quoteName('lar.reviewer_id') . ' = ' . (int) $reviewer_id);
		$db->setQuery($query);
		$result = $db->loadObjectList('rating_id');

		return $result;
	}

	/**
	 * Function used to get track review detail
	 *
	 * @param   INT  $lTrackId     of the lesson_track
	 * @param   INT  $reviewer_id  of the reviewer
	 *
	 * @return  Object
	 *
	 * @since  1.0.0
	 *
	 */
	public function getTrackReviews($lTrackId, $reviewer_id = null)
	{
		$db     = Factory::getDBO();
		$query  = $db->getQuery(true);
		$result = array();

		if (! (int) $reviewer_id)
		{
			$reviewer_id = Factory::getUser()->id;
		}

		$query->select('ar.*');
		$query->from($db->quoteName('#__tjlms_assessment_reviews', 'ar'));
		$query->where($db->quoteName('ar.lesson_track_id') . ' = ' . (int) $lTrackId);
		$query->where($db->quoteName('ar.reviewer_id') . ' = ' . (int) $reviewer_id);
		$db->setQuery($query);
		$result = $db->loadObject();

		return $result;
	}

	/**
	 * Function used to get lesson assessment detail
	 *
	 * @param   INT  $lesson_id  of the lesson id
	 *
	 * @return  Object
	 *
	 * @since  1.0.0
	 *
	 */
	public function getLessonAssessments($lesson_id)
	{
		$db     = Factory::getDBO();
		$query  = $db->getQuery(true);

		$result = array();
		$result = $this->getLessonAssessSet($lesson_id);

		if (!empty($result->set_id))
		{
			$result->assessmentParams = $this->getAssessParams($result->set_id);

			return $result;
		}

		return false;
	}

	/**
	 * Function used to get lesson assessment set
	 *
	 * @param   INT  $lesson_id  of the lesson id
	 *
	 * @return  Object
	 *
	 * @since  1.0.0
	 *
	 */
	public function getLessonAssessSet($lesson_id)
	{
		$db = Factory::getDBO();

		$query = $db->getQuery(true);
		$query->select('as.id as set_id, as.assessment_title, as.assessment_attempts,
			as.assessment_attempts_grade, as.allow_attachments, as.assessment_answersheet, as.answersheet_options, sl.*,
				l.title as lesson_title, as.assessment_student_name');
		$query->from($db->quoteName('#__tjlms_assessment_set', 'as'));
		$query->join('left', $db->quoteName('#__tjlms_assessmentset_lesson_xref', 'sl') . ' ON sl.set_id = as.id');
		$query->join('left', $db->quoteName('#__tjlms_lessons', 'l') . ' ON l.id = sl.lesson_id');
		$query->where($db->quoteName('sl.lesson_id') . ' = ' . (int) $lesson_id);
		$db->setQuery($query);
		$result = $db->loadObject();

		return $result;
	}

	/**
	 * Function used to get assessment params
	 *
	 * @param   INT  $set_id  set id of assessment set
	 *
	 * @return  Object
	 *
	 * @since  1.0.0
	 *
	 */
	public function getAssessParams($set_id)
	{
		$db     = Factory::getDBO();
		$query  = $db->getQuery(true);
		$query->select('*');
		$query->from($db->quoteName('#__tjlms_assessment_rating_parameters', 'arp'));
		$query->where($db->quoteName('arp.set_id') . ' = ' . (int) $set_id);
		$db->setQuery($query);
		$result = $db->loadObjectList();

		return $result;
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
		$invId = $data->invite_id;
		$user_id = $data->candid_id;
		$marks = $data->marks;
		$test_data = $this->getLessonTest($data->lesson_id);
		$test_id = $test_data[0];
		$qztype = $test_data[1];

		$this->update_lesson_test($invId, $user_id, $test_id, $isfinal, $marks, $qztype);

		return true;
	}

	/**
	 * Method to update marks and lesson status after review.
	 *
	 * @param   int     $invId    invite id
	 * @param   int     $user_id  user id
	 * @param   int     $test_id  id of test
	 * @param   int     $isfinal  post data
	 * @param   int     $marks    obtained marks
	 * @param   string  $qztype   quiz type
	 *
	 * @return  boolean  true/false
	 *
	 * @since    1.6
	 */
	public function update_lesson_test($invId, $user_id, $test_id, $isfinal,$marks, $qztype)
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
			Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjlms/tables');
			$db = Factory::getDbo();
			$table = Table::getInstance('Lessontrack', 'TjlmsTable', array('dbo', $db));
			$table->load(array('id' => $invId));

			$table->modified_by = Factory::getUser()->id;
			$table->score = $marks;
			$table->lesson_status = $lesson_status;
			$table->store();
		}

		if ($isfinal == 0 &&  $qztype == 'exercise')
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

			$result = $db->execute();
		}

		return true;
	}

	/**
	 * Method to get test data from lesson id.
	 *
	 * @param   int  $lesson_id  lesson id
	 *
	 * @return  array
	 *
	 * @since    1.2
	 */
	public function getLessonTest($lesson_id)
	{
		$db = Factory::getDbo();

		// Get passing marks for test
		$query = $db->getQuery(true);
		$query->select('tjt.test_id,tt.gradingtype');
		$query->from($db->quoteName('#__tjlms_tmtquiz', 'tjt'));
		$query->join('inner', $db->quoteName('#__tmt_tests', 'tt') . ' ON tjt.test_id = tt.id');
		$query->where($db->quoteName('lesson_id') . '=' . $db->quote($lesson_id));
		$db->setQuery($query);
		$result = $db->loadRow($query);

		return $result;
	}

	/**
	 * Method to save Assessments
	 *
	 * @param   ARRAY  $jform  JForm Data
	 *
	 * @return  boolean.
	 *
	 * @since   1.2
	 */
	public function save($jform)
	{
		if ((int) $jform['assessor_id'])
		{
			$this->user_id = (int) $jform['assessor_id'];
		}
		else
		{
			$this->user_id  = Factory::getUser()->id;
		}

		$this->lesson_track_id = (int) $jform['lesson_track_id'];

		if (!$this->lesson_track_id)
		{
			$this->setError(Text::_('COM_TJLMS_ASSESSMENTS_MISSING_TRACKID'));

			return false;
		}

		$this->assess_model = BaseDatabaseModel::getInstance('assessments', 'TjlmsModel');

		// Get lesson id
		$this->lesson_track_data = $this->assess_model->getTrackDetail($this->lesson_track_id);

		if (empty($this->lesson_track_data))
		{
			$this->setError(Text::_('COM_TJLMS_ASSESSMENTS_INVALID_TRACKID'));

			return false;
		}

		$this->lesson_track_assess_data = $this->assess_model->getTrackAssessmentDetails($this->lesson_track_id, $this->user_id);
		$this->lesson_assess_data       = $this->assess_model->getLessonAssessments($this->lesson_track_data->lesson_id);

		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjlms/tables');

		$usedid = Array();
		$assessmentParams = $this->lesson_assess_data['assess'];
		$score = 0;

		if (!empty($assessmentParams))
		{
			foreach ($assessmentParams as $assessParam)
			{
				$ratingid = $assessParam->id;
				$table = Table::getInstance('assessmentratings', 'TjlmsTable');

				if (!empty($jform['assess'][$ratingid]))
				{
					Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjlms/tables');
					$paramDetails = Table::getInstance('assessmentparameter', 'TjlmsTable');
					$paramDetails->load($ratingid);

					$detail = $jform['assess'][$ratingid];
					$assessData = new stdClass;
					$assessData->rating_id          = $ratingid;
					$assessData->lesson_track_id    = (int) $this->lesson_track_id;
					$assessData->reviewer_id        = (int) $this->user_id;

					if ($detail['rating_comment'])
					{
						$assessData->rating_comment     = strip_tags($detail['rating_comment']);
					}

					$assessData->rating_value   = isset($detail['rating_value']) ? (float) $detail['rating_value'] : 0;
					$score += $assessData->rating_value * $paramDetails->weightage;

					$assessData->id = 0;

					if ((int) $detail['id'])
					{
						$assessData->id = (int) $detail['id'];
						$usedid[] = $assessData->id;
					}

					if (!($table->save($assessData) === true))
					{
						$this->setError($db->stderr());
					}
				}
			}
		}
		else
		{
			$score = $jform['assess']['score'];
		}

		$table      = Table::getInstance('Assessmentreviews', 'TjlmsTable');
		$reviewData = new stdClass;
		$date = Factory::getDate();
		$curDate = $date->toSql(true);

		if (!empty($this->lesson_track_assess_data['reviews']))
		{
			$reviewData->id             = $this->lesson_track_assess_data['reviews']->id;
			$reviewData->modified_date  = $curDate;
		}
		else
		{
			$reviewData->lesson_track_id    = (int) $this->lesson_track_id;
			$reviewData->reviewer_id        = (int) $this->user_id;
			$reviewData->created_date       = $curDate;
		}

		if ($jform['status'] == 'savenclose')
		{
			$status = 'save';
		}
		else
		{
			$status = $jform['status'];
		}

		$reviewData->review_status = $status;
		$reviewData->score = $score;
		$reviewData->feedback   = strip_tags($jform['feedback']);
		$reviewData->modified_by = 0;

		if ((int) $jform['assessor_id'])
		{
			$reviewData->modified_by = Factory::getUser()->id;
		}

		if (!($table->save($reviewData) === true))
		{
			$this->setError($db->stderr());
		}

		/* PluginHelper::importPlugin('system');
		$dispatcher = JDispatcher::getInstance();
		$courseId = $dispatcher->trigger('onAfterAssessmentSave', array($result));*/

		$assessmentCount = $this->getAssessmentSubmissionsCount($this->lesson_track_id);

		if ($this->lesson_assess_data['assess_set']->attempts <= $assessmentCount)
		{
			$lessonTrackTable = Table::getInstance('Lessontrack', 'TjlmsTable');
			$finalScore = $this->getAssessmentScore($this->lesson_track_id, $this->lesson_assess_data['assess_set']->attempts_grade);
			$status = $this->getLessonStatusByAssessment($this->lesson_track_data->lesson_id, $finalScore);

			$lessonTrackTable->load(array('id' => $this->lesson_track_id));
			$lessonTrackTable->score = $finalScore;
			$lessonTrackTable->lesson_status = $status;

			$lessonTrackTable->store();
		}
	}

	/**
	 * Method to get all assessments submiited by reviewres for an attempt
	 *
	 * @param   INT  $ltId  lesson id
	 *
	 * @return  INT.
	 *
	 * @since   1.2
	 */
	public function getAssessmentSubmissions($ltId)
	{
		$db = Factory::getDBO();

		$query = $db->getQuery(true);
		$query->select('*');
		$query->from($db->quoteName('#__tjlms_assessment_reviews', 'ar'));
		$query->where($db->quoteName('ar.lesson_track_id') . ' = ' . (int) $ltId);
		$query->where($db->quoteName('ar.review_status') . ' = 1');
		$db->setQuery($query);
		$reviews = $db->loadObjectList();

		foreach ($reviews as $review)
		{
			$query  = $db->getQuery(true);
			$query->select('lar.*');
			$query->from($db->quoteName('#__tjlms_lesson_assessment_ratings', 'lar'));
			$query->where($db->quoteName('lar.lesson_track_id') . ' = ' . (int) $ltId);
			$query->where($db->quoteName('lar.reviewer_id') . ' = ' . (int) $review->reviewer_id);
			$db->setQuery($query);
			$review->assessment_params_ratings = $db->loadObjectList('rating_id');
		}

		return $reviews;
	}

	/**
	 * Method to get number of time that attempt is assessed
	 *
	 * @param   INT  $lessonTrackid  lesson id
	 *
	 * @return  INT.
	 *
	 * @since   1.2
	 */
	public function getAssessmentSubmissionsCount($lessonTrackid)
	{
		$db = Factory::getDBO();

		$query = $db->getQuery(true);
		$query->select('COUNT(*)');
		$query->from($db->quoteName('#__tjlms_assessment_reviews', 'ar'));
		$query->where($db->quoteName('ar.lesson_track_id') . ' = ' . (int) $lessonTrackid);
		$query->where($db->quoteName('ar.review_status') . ' = 1');
		$db->setQuery($query);
		$count = $db->loadResult();

		return $count;
	}

	/**
	 * Method to get assessment score as per assessment grading configuration
	 *
	 * @param   INT  $lesson_track_id  lesson track id
	 * @param   INT  $attempts_grade   assessment grade
	 *
	 * @return  INT.
	 *
	 * @since   1.2
	 */
	public function getAssessmentScore($lesson_track_id, $attempts_grade = 0)
	{
		$db = Factory::getDBO();

		if (!empty($lesson_track_id))
		{
			switch ($attempts_grade)
			{
				case "0" :
						$sql = "select max(score)
								from  #__tjlms_assessment_reviews as ar
								where ar.lesson_track_id='" . $lesson_track_id . "'
								AND ar.review_status= 1";

						$db->setQuery($sql);
						$score = $db->loadResult();
						break;

				case "1" :
						$sql = "select (AVG(score)) from  #__tjlms_assessment_reviews
								where lesson_track_id='" . $lesson_track_id . "' AND review_status=1";
						$db->setQuery($sql);
						$score = $db->loadResult();
						break;

				case "2" :
						$sql = "select ar.score from #__tjlms_assessment_reviews as ar
								where ar.created_date = (select MIN(created_date)
								from  #__tjlms_assessment_reviews ar1
								where ar1.lesson_track_id='" . $lesson_track_id . "'
								AND ar1.review_status= 1) AND ar.lesson_track_id=" . $lesson_track_id . "
								AND ar.review_status=1";

						$db->setQuery($sql);
						$score = $db->loadResult();
						break;

				case "3" :
						$sql = "select ar.score from #__tjlms_assessment_reviews as ar
								where ar.lesson_track_id=" . $lesson_track_id . "
								AND ar.review_status=1 ORDER BY ar.modified_date DESC , ar.created_date DESC";
						$db->setQuery($sql);
						$score = $db->loadResult();
						break;
			}

			return $score;
		}
	}

	/**
	 * Method to get lesson status by assessment
	 *
	 * @param   INT  $lesson_id   lesson id
	 * @param   INT  $finalScore  gained marks
	 *
	 * @return  STRING.
	 *
	 * @since   1.2
	 */
	public function getLessonStatusByAssessment($lesson_id, $finalScore)
	{
		$db = Factory::getDbo();

		// Get passing marks for test
		$query = $db->getQuery(true);
		$query->select($db->quoteName('passing_marks'));
		$query->from($db->quoteName('#__tjlms_lessons'));
		$query->where($db->quoteName('id') . '=' . $db->quote($lesson_id));
		$db->setQuery($query);
		$passing_marks = $db->loadResult($query);

		if ($finalScore >= $passing_marks)
		{
			$lesson_status = 'passed';
		}
		else
		{
			$lesson_status = 'failed';
		}

		return $lesson_status;
	}

	/** Function used to get Assessment value
	 *
	 * @param   string  $format      format of the lesson
	 *
	 * @param   string  $sub_format  sub format of the lesson
	 *
	 * @return  Object list of files
	 *
	 * @since  1.0.0
	 *
	 **/
	public function getAssessmentValue($format, $sub_format)
	{
		$plugformat = 'tj' . $format;
		$retAssessment = 0;

		if (!empty($sub_format))
		{
			$plugins = PluginHelper::getPlugin($plugformat, $sub_format);
			$params = new Registry($plugins->params);
			$retAssessment = $params->get('assessment', '0');
		}

		return $retAssessment;
	}

	/** Function used to get Lesson track id from assessment id
	 *
	 * @param   string  $assessment_id  assessment id
	 *
	 * @return  Object list of files
	 *
	 * @since  1.0.0
	 *
	 **/
	public function getLessonTrack($assessment_id)
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select($db->quoteName(array('lesson_track_id', 'reviewer_id')));
		$query->from($db->quoteName('#__tjlms_assessment_reviews'));
		$query->where($db->quoteName('id') . '=' . $db->quote($assessment_id));
		$db->setQuery($query);
		$lesson_track_id = $db->loadRow($query);

		return $lesson_track_id;
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
		$db = Factory::getDbo();
		$query = $db->getQuery(true);

		$query->select('tm.source');
		$query->from($db->quoteName('#__tjlms_media', 'tm'));
		$query->join('left', $db->quoteName('#__tjlms_lessons', 'l') . ' ON (' . $db->quoteName('l.media_id') . ' = ' . $db->quoteName('tm.id') . ')');
		$query->join('left', $db->quoteName('#__tjlms_lesson_track', 'lt') .
		' ON (' . $db->quoteName('lt.lesson_id') . ' = ' . $db->quoteName('l.id') . ')');
		$query->where($db->quoteName('lt.id') . " = " . $db->quote($lesson_track_id));

		$db->setQuery($query);
		$test_id = $db->loadResult();

		return $test_id;
	}
}
