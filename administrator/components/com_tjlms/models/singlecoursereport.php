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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Access\Access;
jimport('joomla.application.component.modellist');

/**
 * Methods supporting a list of Tjlms records.
 *
 * @since  1.0.0
 */
class TjlmsModelSinglecoursereport extends ListModel
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
		$path = JPATH_SITE . '/components/com_tjlms/models/' . 'course.php';

		if (!class_exists('TjlmsModelcourse'))
		{
			JLoader::register('TjlmsModelcourse', $path);
			JLoader::load('TjlmsModelcourse');
		}

		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'id',
				'u.id',
				'name',
				'u.name',
				'username',
				'u.username',
				'email',
				'u.email'
			);
		}

		$this->TjlmsModelcourse = new TjlmsModelcourse;
		$this->ComtjlmsHelper = new ComtjlmsHelper;
		$this->TjlmsCoursesHelper = new TjlmsCoursesHelper;
		$this->ComtjlmstrackingHelper = new ComtjlmstrackingHelper;
		$this->totallessonsattempted = '';
		$this->columnsWithDirectSorting = array('eu.course_id','c.title','cat.title','eu.enrolled_on_time');

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
		parent::populateState('c.id', 'desc');

		$app = Factory::getApplication('administrator');

		// Load the filter state.
		$search = $app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		$enroll_starts = $app->getUserStateFromRequest($this->context . '.filter.enroll_starts', 'filter_enroll_starts');
		$this->setState('filter.enroll_starts', $enroll_starts);

		$enroll_ends = $app->getUserStateFromRequest($this->context . '.filter.enroll_ends', 'filter_enroll_ends');
		$this->setState('filter.enroll_ends', $enroll_ends);

		// Filtering course
		$coursefilter = $app->getUserStateFromRequest($this->context . '.filter.coursefilter', 'filter_coursefilter', '', 'INT');
		$this->setState('filter.coursefilter', $coursefilter);

		// Filtering Category
		$categoryfilter = $app->getUserStateFromRequest($this->context . '.filter.categoryfilter', 'filter_categoryfilter', '', 'INT');
		$this->setState('filter.categoryfilter', $categoryfilter);

		// Filtering Accesslevel
		$accessfilter = $app->getUserStateFromRequest($this->context . '.filter.accessfilter', 'filter_accessfilter', '', 'INT');
		$this->setState('filter.accessfilter', $accessfilter);

		// Filtering courseType
		$coursetypefilter = $app->getUserStateFromRequest($this->context . '.filter.coursetypefilter', 'filter_coursetypefilter', '', 'INT');
		$this->setState('filter.coursetypefilter', $coursetypefilter);

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

		// Filter by Course
		$coursefilter = $this->getState('filter.coursefilter');

		// Join over the user field 'created_by'
		$query->select('eu.*,coursetrack.timeend,c.title, c.catid, c.access, c.certificate_term');
		$query->select('u.name AS user_name, u.username, u.email');
		$query->select('cat.title as cat_title');
		$query->from('#__tjlms_enrolled_users AS eu');
		$query->join('RIGHT', '#__users as u ON u.id = eu.user_id');
		$query->join('LEFT', '#__tjlms_course_track as coursetrack ON coursetrack.course_id = eu.course_id AND coursetrack.user_id = eu.user_id');
		$query->join('LEFT', '#__tjlms_courses as c ON c.id = eu.course_id');
		$query->join('LEFT', '#__categories AS cat ON c.catid = cat.id');

		// Filter by search in title
		$search = $this->getState('filter.search');
		$enroll_starts = $this->getState('filter.enroll_starts');
		$enroll_starts_time = strtotime($enroll_starts);
		$enroll_starts_date = date("Y-m-d", $enroll_starts_time);
		$enroll_ends = $this->getState('filter.enroll_ends');
		$enroll_ends_time = strtotime($enroll_ends);
		$enroll_ends_date = date("Y-m-d", $enroll_ends_time);

		if (!empty($enroll_starts) and empty($enroll_ends))
		{
			$query->where('DATE(enrolled_on_time)>=' . "'" . $enroll_starts_date . "'");
		}

		if (!empty($enroll_ends) and empty($enroll_starts))
		{
			$query->where('DATE(enrolled_on_time)<=' . "'" . $enroll_ends_date . "'");
		}

		if (!empty($enroll_starts) and !empty($enroll_ends))
		{
			if ($enroll_starts == $enroll_ends)
			{
				$query->where('DATE(enrolled_on_time)=' . "'" . $enroll_ends_date . "'");
			}
			else
			{
				$query->where('DATE(enrolled_on_time)>=' . "'" . $enroll_starts_date . "'");
				$query->where('DATE(enrolled_on_time)<=' . "'" . $enroll_ends_date . "'");
			}
		}

		// Filter by search in title
		$course_completion_starts = $this->getState('filter.course_completion_starts');
		$course_completion_starts_time = strtotime($course_completion_starts);
		$course_completion_starts_date = date("Y-m-d H:i:s", $course_completion_starts_time);
		$course_completion_ends = $this->getState('filter.course_completion_ends');
		$course_completion_ends_time = strtotime($course_completion_ends);
		$course_completion_ends_date = date("Y-m-d H:i:s", $course_completion_ends_time);

		if (!empty($course_completion_starts) and empty($course_completion_ends))
		{
			$query->where('coursetrack.timeend>=' . "'" . $course_completion_starts_date . "'");
			$query->where("coursetrack.timeend<>'0000-00-00 00:00:00'");
		}

		if (!empty($course_completion_ends) and empty($course_completion_starts))
		{
			$query->where('coursetrack.timeend<=' . "'" . $course_completion_ends_date . "'");
			$query->where("coursetrack.timeend<>'0000-00-00 00:00:00'");
		}

		if (!empty($course_completion_starts) and !empty($course_completion_ends))
		{
			if ($course_completion_starts == $course_completion_ends)
			{
				$query->where('DATE(coursetrack.timeend)=' . "'" . $course_completion_ends_date . "'");
			}
			else
			{
				$query->where('coursetrack.timeend>=' . "'" . $course_completion_starts_date . "'");
				$query->where('coursetrack.timeend<=' . "'" . $course_completion_ends_date . "'");
				$query->where("coursetrack.timeend<>'0000-00-00 00:00:00'");
			}
		}

		if (!empty($search))
		{
			$search = $db->Quote('%' . $db->escape($search, true) . '%');
			$query->where('((( c.title LIKE ' . $search . ' )
			OR ( cat.title LIKE ' . $search . ' ))
			OR ( u.name LIKE ' . $search . ' )
			OR ( u.username LIKE ' . $search . ' )
			OR (u.email LIKE ' . $search . ' ))');
		}

		// Filter by Course
		$coursefilter = $this->getState('filter.coursefilter');

		if (!empty($coursefilter))
		{
			$query->where($db->quoteName('eu.course_id') . " = " . $db->quote($coursefilter));
		}

		// Filter by Category
		$categoryfilter = $this->getState('filter.categoryfilter');

		if (!empty($categoryfilter))
		{
			$query->where($db->quoteName('c.catid') . " = " . $db->quote($categoryfilter));
		}

		// Filter by accesslevel
		$courseaccesslevelfilter = $this->getState('filter.accessfilter');

		if (!empty($courseaccesslevelfilter))
		{
			$query->where($db->quoteName('c.access') . " = " . $db->quote($courseaccesslevelfilter));
		}

		// Filter by Users
		$courseuserfilter = $this->getState('filter.userfilter');

		if (!empty($courseuserfilter))
		{
			$query->where($db->quoteName('u.id') . " = " . $db->quote($courseuserfilter));
		}

		// Add the list ordering clause.
		$orderCol  = $this->state->get('list.ordering');
		$orderDirn = $this->state->get('list.direction');

		if ($orderCol && $orderDirn)
		{
			if (in_array($orderCol, $this->columnsWithDirectSorting))
			{
				$query->order($db->escape($orderCol . ' ' . $orderDirn));
			}
		}

		$query->where('c.state=1');
		$query->where('eu.state=1');
		$query->group('eu.course_id,eu.user_id');

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
		$db = Factory::getDBO();
		$itemsnew = $items = parent::getItems();

		// Filter by Course
		$coursefilter = $this->getState('filter.coursefilter');

		if (empty($coursefilter) or empty($items))
		{
			return false;
		}

		foreach ($itemsnew as $ind1 => $course1)
		{
			$assigned_user_array[] = $course1->user_id;
		}

		$totallessonsattempted = $cnt = 0;

		if (!empty($assigned_user_array))
		{
			$assigned_user_array = array_unique($assigned_user_array);
			$first_course_id = '';

			foreach ($assigned_user_array as $key => $assigned_user)
			{
				foreach ($items as $ind => $course)
				{
					if ($assigned_user != $course->user_id)
					{
						continue;
					}

					if ($cnt == 0)
					{
						$first_course_id = $course->course_id;
					}

					$cer_term = Text::_("COM_TJLMS_FORM_OPT_COURSE_CERTIFICATE_TERM_NOCERTI");

					if ($course->certificate_term == "1")
					{
						$cer_term = Text::_("COM_TJLMS_FORM_OPT_COURSE_CERTIFICATE_TERM_COMPALL");
					}
					elseif ($course->certificate_term == "2")
					{
						$cer_term = Text::_("COM_TJLMS_FORM_OPT_COURSE_CERTIFICATE_TERM_PASSALL");
					}

					$items[$cnt]->certificate_term = $cer_term;

					// Get access level titles.
					$query = $db->getQuery(true);
					$query->select('title');
					$query->from('#__viewlevels');
					$query->where("id='" . $course->access . "'");
					$db->setQuery($query);
					$items[$cnt]->access_level_title = $db->loadResult();

					// Get groups to which user belongs
					$userGroups = Access::getGroupsByUser($course->user_id);
					$userGroups = array_diff($userGroups, array('1'));
					$group = array();

					// Get All lessons data
					$query = $db->getQuery(true);
					$query->select('lt.*,COUNT(lt.attempt) as attemptsDone');
					$query->select('SEC_TO_TIME(SUM(TIME_TO_SEC(time_spent))) as timeSpentOnLesson');
					$query->select('c.title as courseTitle, l.title AS lessonname, l.start_date,
					l.end_date, l.no_of_attempts, l.attempts_grade, l.format, l.consider_marks');
					$query->from('`#__tjlms_lesson_track` AS lt');
					$query->join('INNER', '`#__tjlms_lessons` as l ON lt.lesson_id = l.id');
					$query->join('INNER', '`#__tjlms_courses` as c ON l.course_id = c.id');
					$query->group('lt.lesson_id,lt.user_id');
					$query->where('lt.user_id=' . $assigned_user);
					$query->where('c.id=' . $course->course_id);
					$query->order('l.title,l.start_date');
					$db->setQuery($query);
					$items[$cnt]->lessondata = $db->loadObjectlist('lessonname');

					if (count($items[$cnt]->lessondata) > $totallessonsattempted)
					{
						$totallessonsattempted = count($items[$cnt]->lessondata);
						$lessonheader = $items[$cnt]->lessondata;
					}

					// Create a new query object.
					$query = $db->getQuery(true);
					$query->select($db->quoteName('title'));
					$query->from($db->quoteName('#__usergroups'));
					$query->where($db->quoteName('id') . ' IN (' . implode(',', $userGroups) . ' ) ');
					$db->setQuery($query);
					$group = $db->loadColumn();
					$items[$cnt]->groups = implode('<br />', $group);

					// Get %completion
					$progress = $this->TjlmsCoursesHelper->getCourseProgress($course->course_id, $course->user_id);
					$items[$cnt]->completion = $progress['completionPercent'];

					// Select content_id for the course
					$query = $db->getQuery(true);
					$query->select('id');
					$query->from('#__jlike_content AS con');
					$query->where('con.element_id = ' . $course->course_id);
					$query->where($db->quoteName('con.element') . ' LIKE \'com_tjlms.course%\'');
					$db->setQuery($query);
					$content_id = $db->loadResult();

					if ($content_id)
					{
						$query = $db->getQuery(true);
						$query->select('due_date');
						$query->from('#__jlike_todos AS todos');
						$query->where('todos.assigned_to = ' . $course->user_id);
						$query->where('todos.content_id = ' . $content_id);
						$db->setQuery($query);
						$items[$ind]->course_due_date = $db->loadResult();
					}
					else
					{
						$items[$ind]->course_due_date = '';
					}

					// Get total time spent
					$query = $db->getQuery(true);
					$query->select('SEC_TO_TIME(SUM(TIME_TO_SEC(time_spent))) as totalTimeSpent');
					$query->from('`#__tjlms_lesson_track` AS lt');
					$query->join('INNER', '`#__tjlms_lessons` as l ON lt.lesson_id = l.id');
					$query->where('lt.user_id = ' . $course->user_id);
					$query->where('l.course_id = ' . $course->course_id);
					$db->setQuery($query);
					$items[$cnt]->totaltimespent = $db->loadResult();

					// Get Time end
					if ($items[$cnt]->completion == 100)
					{
						$query = $db->getQuery(true);
						$query->select('MAX(timeend) as completion_date');
						$query->from('`#__tjlms_course_track` AS lt');
						$query->where('lt.user_id = ' . $course->user_id);
						$query->where('lt.course_id = ' . $course->course_id);
						$db->setQuery($query);
						$items[$cnt]->completion_date = $db->loadResult();
					}

					$cnt++;
				}
			}
		}

	// Add the list ordering clause.
	$orderCol  = $this->state->get('list.ordering');
	$orderDirn = $this->state->get('list.direction');

	if ($orderCol && $orderDirn)
	{
		if (!in_array($orderCol, $this->columnsWithDirectSorting))
		{
			$items = $this->ComtjlmsHelper->multi_d_sort($items, $orderCol, $orderDirn);
		}
	}

	if (!empty($items['0']))
	{
		$items['0']->totallessonsattempted = $totallessonsattempted;
		$items['0']->lessonheader = $lessonheader;
	}

		return $items;
	}
}
