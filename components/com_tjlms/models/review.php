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
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Plugin\PluginHelper;

jimport('joomla.application.component.modelform');
jimport('joomla.event.dispatcher');

/**
 * Methods for lesson.
 *
 * @since  1.0.0
 */
class TjlmsModelReview extends ListModel
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
				'course','l.course_id',
				'attempt_starts','lt.timestart',
				'attempt_ends','lt.timeend',
				'status','lt.lesson_status',
				'score','lt.score'
			);
		}

		parent::__construct($config);
	}

	/**
	 * Function used to get rating parameters of the excersise
	 *
	 * @param   INT  $id  of the lesson_track
	 *
	 * @return  Object
	 *
	 * @since  1.0.0
	 *
	 */
	public function getRatingParams($id)
	{
		$db = Factory::getDBO();
		$query = $db->getQuery(true);

		$query->select('b.*');
		$query->from('`#__tjlms_lesson_track` AS lt');
		$query->leftjoin('`#__tjlms_assessmentset_lesson_xref` as a ON a.lesson_id = lt.lesson_id');
		$query->leftjoin('`#__tjlms_assessment_rating_parameters` as b ON b.set_id = a.set_id');
		$query->where('lt.id=' . $id);
		$db->setQuery($query);

		return $db->loadObjectlist();
	}

	/**
	 * Function used to save the review
	 *
	 * @return  JSON
	 *
	 * @since  1.0.0
	 *
	 */
	public function AddReview()
	{
		$userId = Factory::getUser()->id;

		if (!empty($userId))
		{
			$db = Factory::getDbo();
			$input = Factory::getApplication()->input;
			$post = $input->get('jform', '', 'array');
			$now = new DateTime;

			// Add Review
			$review = new stdclass;
			$review->lesson_track_id = $post['lesson_track_id'];

			$review->reviewer_id = Factory::getUser()->id;
			$review->review_status = $post['review_status'];
			$review->feedback = $post['feedback'];
			$flag = 0;

			if ($post['id'] == 0)
			{
				$review->created_date = HTMLHelper::date($date = 'now', 'Y:m:d H:i:s ', false);
				$review->modified_date = HTMLHelper::date($date = 'now', 'Y:m:d H:i:s ', false);

				if ($db->updateObject('#__tjlms_assessment_reviews', $review, 'id'))
				{
					$flag = 1;
				}
			}

			else
			{
				$review->modified_date = HTMLHelper::date($date = 'now', 'Y:m:d H:i:s ', false);
				$review->id = $post['id'];

				if ($db->updateObject('#__tjlms_assessment_reviews', $review, 'id'))
				{
					$flag = 1;
				}
			}

			if ($flag == 1)
			{
				$query = $db->getQuery(true);
				$query->select('rt.*');
				$query->from('`#__tjlms_lesson_assessment_ratings` AS rt');
				$query->where('rt.lesson_track_id=' . $post['lesson_track_id']);
				$db->setQuery($query);
				$ratings = $db->loadObjectlist();

				for ($i = 0; $i <= count($post['rating']['rating_id']) - 1; $i++)
				{
					// Add Reviews Rating
					$review_rating = new stdclass;
					$review_rating->lesson_track_id = $post['lesson_track_id'];
					$review_rating->rating_id = $post['rating']['rating_id'][$i];
					$review_rating->rating_value = $post['rating']['value'][$i];
					$review_rating->rating_comment = $post['rating']['comment'][$i];

					if ($ratings[0]->lesson_track_id != $post['id'])
					{
						$db->insertObject('#__tjlms_lesson_assessment_ratings', $review_rating, 'id');
					}

					else
					{
						$review_rating->id = $post['rating']['id'][$i];
						$db->updateObject('#__tjlms_lesson_assessment_ratings', $review_rating, 'id');
					}
				}
			}

			if ($review->review_status == 'final')
			{
				PluginHelper::importPlugin('system');

				// Trigger all "sytem" plugins onAfterReviewSubmission method
				Factory::getApplication()->triggerEvent('onAfterReviewSubmission', array($review));
			}
		}
	}

	/**
	 * Function used to get already added review details
	 *
	 * @param   INT  $track_id     lesson track id
	 *
	 * @param   INT  $reviewer_id  reviewer id
	 *
	 * @return  Object
	 *
	 * @since  1.0.0
	 *
	 */
	public function getReviewDetails($track_id, $reviewer_id)
	{
		$db = Factory::getDBO();

		if (!empty($track_id) && !empty($reviewer_id))
		{
			$query = $db->getQuery(true);
			$query->select('r.*');
			$query->from('`#__tjlms_assessment_reviews` as r');
			$query->where('r.lesson_track_id = ' . $track_id . ' AND r.reviewer_id = ' . $reviewer_id);
			$db->setQuery($query);
			$track = $db->loadObject();

			if (!empty($track))
			{
				$query = $db->getQuery(true);
				$query->select('r.*');
				$query->from('`#__tjlms_lesson_assessment_ratings` as r');
				$query->where('r.lesson_track_id=' . $track_id);
				$db->setQuery($query);
				$rating = $db->loadObjectList();

				if (!empty($rating))
				{
					$track->rating = $rating;
				}
			}

			return $track;
		}
	}

	/**
	 * Function used to get rating parameters of the excersise
	 *
	 * @param   INT  $id  of the lesson_track
	 *
	 * @return  Object
	 *
	 * @since  1.0.0
	 *
	 */
	public function getRplList($id)
	{
		$db = Factory::getDBO();
		$query = $db->getQuery(true);

		$query->select('lt.*');
		$query->select('l.title');
		$query->from($db->quoteName('#__tjlms_lesson_track', 'lt'));
		$query->join('left', $db->quoteName('#__tjlms_lessons', 'l') . 'ON(' . $db->quoteName('lt.lesson_id') . '=' . $db->quoteName('l.id') . ') ');

		$query->where("`lesson_id` = '" . $id . "' AND (`lesson_status` = 'RP' OR `modified_by` ='" . Factory::getUser()->id . "')");

		$db->setQuery($query);

		return $db->loadObjectlist();
	}

	/**
	 * Function used to get rating parameters of the excersise
	 *
	 * @return  Object
	 *
	 * @since  1.0.0
	 *
	 */
	public function getListQuery()
	{
		$db = Factory::getDBO();
		$query = $db->getQuery(true);

		$query->select(
				$this->getState(
						'list.select', 'lt.*,l.title'
				)
		);

		$query->select('lt.*');
		$query->select('l.title,l.course_id');
		$query->select('ttq.test_id');
		$query->from($db->quoteName('#__tjlms_lesson_track', 'lt'));
		$query->join('left', $db->quoteName('#__tjlms_lessons', 'l') . 'ON(' . $db->quoteName('lt.lesson_id') . '=' . $db->quoteName('l.id') . ') ');

		$query->join('left', $db->quoteName('#__tjlms_tmtquiz', 'ttq') . 'ON(' . $db->quoteName('l.id') . '=' . $db->quoteName('ttq.lesson_id') . ') ');

		$query->where(
			'l.id IN ' . '(SELECT tjt.lesson_id FROM #__tjlms_tmtquiz AS tjt LEFT JOIN #__tmt_tests AS tt ON(tt.id=tjt.test_id)
			WHERE tt.isObjective="0" OR tt.gradingtype ="exercise" OR tt.gradingtype="feedback")');

		$query->where($db->quoteName('l.created_by') . '=' . $db->quote(Factory::getUser()->id));
		$query->where($db->quoteName('lt.lesson_status') . '<>' . $db->quote('started'));

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

		if ($filter_status == 'NRP')
		{
			$query->where($db->quoteName('lt.lesson_status') . ' <> ' . $db->quote('RP'));
		}

		if ($filter_status == 'RP')
		{
			$query->where($db->quoteName('lt.lesson_status') . ' = ' . $db->quote('RP'));
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
		// Ends here the Date filter

		// Add the list ordering clause.
		$orderCol = $this->state->get('list.ordering');
		$orderDirn = $this->state->get('list.direction');

		if ($orderCol && $orderDirn)
		{
			$query->order($db->escape($orderCol . ' ' . $orderDirn));
		}

		return $query;
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
}
