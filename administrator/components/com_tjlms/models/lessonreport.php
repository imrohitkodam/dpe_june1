<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Tjlms
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Factory;
jimport('joomla.application.component.modellist');

/**
 * Methods supporting a list of Tjlms records.
 *
 * @since  1.0.0
 */
class TjlmsModelLessonreport extends ListModel
{
	// Add filter form name in view model
	protected $filterFormName = 'filter_attemptreport';

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
				'l.id',
				'title',
				'l.title',
				'userfilter',
				'lessonfilter',
				'lesonformat'
			);
		}

		$this->ComtjlmsHelper = new ComtjlmsHelper;

		$this->columnsWithoutDirectSorting = array('user_username','user_email','user_id',
											'no_of_attempts','attemptsDone','timeSpentOnLesson',
											'attempts_grade','score');

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
		$app = Factory::getApplication('administrator');

		// Load the filter state.
		$search = $app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		// Filtering user
		$userfilter = $app->getUserStateFromRequest($this->context . '.filter.userfilter', 'userfilter', '', 'INT');
		$this->setState('filter.userfilter', $userfilter);

		// Filtering lessons format
		$lesonformat = $app->getUserStateFromRequest($this->context . '.filter.lesonformat', 'lesonformat', '', 'INT');
		$this->setState('filter.lesonformat', $lesonformat);

		// Filtering user
		$coursefilter = $app->getUserStateFromRequest($this->context . '.filter.coursefilter', 'coursefilter', '', 'INT');
		$this->setState('filter.coursefilter', $coursefilter);

		// Filtering lessons
		$lessonfilter = $app->getUserStateFromRequest($this->context . '.filter.lessonfilter', 'lessonfilter', '', 'INT');
		$this->setState('filter.lessonfilter', $lessonfilter);

		$attemptState = $app->getUserStateFromRequest($this->context . '.filter.attemptState', 'attemptState', '', 'INT');
		$this->setState('filter.attemptState', $attemptState);

		$orderCol = $app->getUserStateFromRequest($this->context . '.filter_order', 'filter_order');

		if (!empty($orderCol))
		{
			$this->setState('list.ordering', $orderCol);
		}

		$listOrder = $app->getUserStateFromRequest($this->context . 'filter_order_Dir', 'filter_order_Dir');

		if (!empty($listOrder))
		{
			$this->setState('list.direction', $listOrder);
		}

		parent::populateState('lt.lesson_id', 'desc');
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
		$query = $this->_db->getQuery(true);

		// Select the required fields from the table.
		$query->select($this->getState('list.select', 'lt.*,COUNT(lt.attempt) as attemptsDone'));
		$query->select('SEC_TO_TIME(SUM(TIME_TO_SEC(time_spent))) as timeSpentOnLesson');
		$query->select($this->_db->qn('c.title', 'courseTitle'));
		$query->select($this->_db->qn(array('l.title', 'l.start_date', 'l.end_date')));
		$query->select($this->_db->qn(array('l.no_of_attempts', 'l.attempts_grade', 'l.format', 'l.consider_marks')));
		$attemptState = $this->getState('filter.attemptState');

		if ($attemptState == '0')
		{
			$query->from($this->_db->qn('#__tjlms_lesson_track_archive', 'lt'));
		}
		else
		{
			$query->from($this->_db->qn('#__tjlms_lesson_track', 'lt'));
		}

		$query->join('INNER', $this->_db->qn('#__tjlms_lessons', 'l') . ' ON (' . $this->_db->qn('lt.lesson_id') . '=' . $this->_db->qn('l.id') . ')');
		$query->join('INNER', $this->_db->qn('#__tjlms_courses', 'c') . ' ON (' . $this->_db->qn('l.course_id') . '=' . $this->_db->qn('c.id') . ')');
		$query->group($this->_db->qn(array('lt.lesson_id','lt.user_id')));

		$coursefilter = $this->getState('filter.coursefilter');

		if (!empty($coursefilter))
		{
			$query->where($this->_db->qn('c.id') . '=' . $this->_db->q((int) $coursefilter));
		}

		$userfilter = $this->getState('filter.userfilter');

		if ($userfilter)
		{
			$query->where($this->_db->qn('lt.user_id') . '=' . (int) $userfilter);
		}

		$lessonfilter = $this->getState('filter.lessonfilter');

		if ($lessonfilter)
		{
			$query->where($this->_db->qn('l.id') . '=' . (int) $lessonfilter);
		}

		$lesonformat = $this->getState('filter.lesonformat');

		if ($lesonformat)
		{
			$query->where($this->_db->qn('l.format') . '=' . $this->_db->q($lesonformat));
		}

		// Filter by search in title
		$search = $this->getState('filter.search');

		if (!empty($search))
		{
			$search = '%' . $this->_db->escape($search, true) . '%';
			$query->where($this->_db->qn('l.title') . ' LIKE ' . $this->_db->q($search, false));
		}

		// Add the list ordering clause.
		$orderCol  = $this->state->get('list.ordering');
		$orderDirn = $this->state->get('list.direction');

		if ($orderCol && $orderDirn)
		{
			if (!in_array($orderCol, $this->columnsWithoutDirectSorting))
			{
				$query->order($this->_db->escape($orderCol . ' ' . $orderDirn));
			}
		}

		return $query;
	}

	/**
	 * To get the records
	 *
	 * @return  Object
	 *
	 * @since  1.0.0
	 */
	public function getItems()
	{
		$items = parent::getItems();

		foreach ($items as $ind => $lessonDetails)
		{
			$ComtjlmstrackingHelper = new ComtjlmstrackingHelper;

			$lesson = new stdclass;
			$lesson->id = $lessonDetails->lesson_id;
			$lesson->attempts_grade = $lessonDetails->attempts_grade;
			$lesson->format = $lessonDetails->format;

			$result = $ComtjlmstrackingHelper->getLessonattemptsGrading($lesson, $lessonDetails->user_id);
			$lessonDetails->status = '';
			$lessonDetails->score = '';

			if ($result)
			{
				$lessonDetails->status = $result->lesson_status;
				$lessonDetails->score = $result->score;
			}

			$lessonDetails->user_name = Factory::getUser($lessonDetails->user_id)->name;
			$lessonDetails->user_username = Factory::getUser($lessonDetails->user_id)->username;
			$lessonDetails->user_email = Factory::getUser($lessonDetails->user_id)->email;
		}

		// Add the list ordering clause.
		$orderCol  = $this->state->get('list.ordering');
		$orderDirn = $this->state->get('list.direction');

		if (in_array($orderCol, $this->columnsWithoutDirectSorting))
		{
			$items = $this->ComtjlmsHelper->multi_d_sort($items, $orderCol, $orderDirn);
		}

		return $items;
	}
}
