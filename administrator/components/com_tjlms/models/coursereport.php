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
jimport('joomla.application.component.modellist');

/**
 * Methods supporting a list of Tjlms records.
 *
 * @since  1.0.0
 */
class TjlmsModelCoursereport extends ListModel
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
				'u.id',
				'name',
				'u.name',
				'username',
				'u.username',
				'email',
				'u.email'
			);
		}

		$this->ComtjlmsHelper = new ComtjlmsHelper;

		$this->columnsWithDirectSorting = array('c.id','c.title','cat.title','c.type','COUNT(l.id)');

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

		// Join over the user field 'created_by'
		$query->select('c.id as course_id, c.title as course_title, c.type as course_type,c.access');
		$query->select('cat.title as cat_title');
		$query->from('`#__tjlms_courses` AS c');
		$query->join('LEFT', '#__categories AS cat ON c.catid = cat.id');

		$query->select('COUNT(l.id) as lessons_cnt');
		$query->join('LEFT', '#__tjlms_lessons as l ON c.id = l.course_id');

		// Filter by search in title
		$search = $this->getState('filter.search');

		if (!empty($search))
		{
			$search = $db->Quote('%' . $db->escape($search, true) . '%');
			$query->where('(( c.title LIKE ' . $search . ' ) OR ( cat.title LIKE ' . $search . ' ))');
		}

		// Filter by Course
		$coursefilter = $this->getState('filter.coursefilter');

		if (!empty($coursefilter))
		{
			$query->where($db->quoteName('c.id') . " = " . $db->quote($coursefilter));
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

		// Filter by courseType
		$coursetypefilter = $this->getState('filter.coursetypefilter');

		if (!empty($coursetypefilter))
		{
			if ($coursetypefilter == 'free')
			{
				$query->where($db->quoteName('c.type') . " = 0");
			}
			else
			{
				$query->where($db->quoteName('c.type') . " = 1");
			}
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
		$query->group($db->quoteName('c.id'));

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
		$items = parent::getItems();

		foreach ($items as $ind => $course)
		{
			$type = Text::_("COM_TJLMS_COURSES_TYPE_FILTER_FREE");

			if ($course->course_type == "1")
			{
				$type = Text::_("COM_TJLMS_COURSES_TYPE_FILTER_PAID");
			}

			$items[$ind]->type = $type;

			// Get access level titles.
			$query = $db->getQuery(true);
			$query->select('title');
			$query->from('#__viewlevels');
			$query->where("id='" . $course->access . "'");
			$db->setQuery($query);
			$items[$ind]->access_level_title = $db->loadResult();

			// Get Enroled student Data
			$query = $db->getQuery(true);
			$query->select('COUNT(IF(eu.state="1",1, NULL)) as enrolled_users, COUNT(IF(eu.state="0",1, NULL)) as pending_enrollment');
			$query->from('#__tjlms_enrolled_users as eu');
			$query->where('eu.course_id=' . $course->course_id);

			$db->setQuery($query);
			$enrollmentData = $db->loadObject();

			$items[$ind]->enrolled_users = $enrollmentData->enrolled_users;
			$items[$ind]->pending_enrollment = $enrollmentData->pending_enrollment;

			// Get number of users who has completed the course
			$query = $db->getQuery(true);

			$query->select('COUNT(ct.id)');
			$query->from($db->quoteName('#__tjlms_course_track') . ' as ct');

			$query->where($db->quoteName('ct.status') . " = 'C'");
			$query->where('ct.course_id=' . $course->course_id);

			$db->setQuery($query);
			$items[$ind]->totalCompletedUsers = $db->loadresult();

			// Get number of users who has liked/Disliked the course
			$query = $db->getQuery(true);
			$query->select('jc.like_cnt,jc.dislike_cnt,COUNT(ja.id) as comments_cnt');
			$query->from($db->quoteName('#__jlike_content') . ' as jc');
			$query->join('LEFT', '#__jlike_annotations AS ja ON jc.id = ja.content_id');
			$query->where($db->quoteName('jc.element') . " = 'com_tjlms.course'");
			$query->where('jc.element_id=' . $course->course_id);
			$query->where('ja.note=0');
			$query->where('ja.parent_id=0');

			$db->setQuery($query);
			$jlikeData = $db->loadObject();

			$items[$ind]->likeCnt = 0;

			if (!empty($jlikeData->like_cnt))
			{
				$items[$ind]->likeCnt = $jlikeData->like_cnt;
			}

			$items[$ind]->dislikeCnt = 0;

			if (!empty($jlikeData->dislike_cnt))
			{
				$items[$ind]->dislikeCnt = $jlikeData->dislike_cnt;
			}

			$items[$ind]->commnetsCnt = 0;

			if (!empty($jlikeData->comments_cnt))
			{
				$items[$ind]->commnetsCnt = $jlikeData->comments_cnt;
			}

			// Get number of users to whom this course is recommneded or asssigned
			$query = $db->getQuery(true);
			$query->select('COUNT(IF(jt.type="reco",1, NULL)) as recommend_cnt, COUNT(IF(jt.type="assign",1, NULL)) assign_cnt');
			$query->from('#__jlike_todos as jt');

			$query->join('LEFT', '#__jlike_content AS jc ON jc.id = jt.content_id');
			$query->where('jc.element_id=' . $course->course_id);
			$query->where('jc.element=' . '"com_tjlms.course"');

			$db->setQuery($query);
			$jlikeData = $db->loadObject();

			$items[$ind]->recommendCnt = 0;

			if (!empty($jlikeData->recommendCnt))
			{
				$items[$ind]->recommendCnt = $jlikeData->recommend_cnt;
			}

			$items[$ind]->assignCnt = 0;

			if (!empty($jlikeData->assign_cnt))
			{
				$items[$ind]->assignCnt = $jlikeData->assign_cnt;
			}
		}

		// Add the list ordering clause.
		$orderCol  = $this->state->get('list.ordering');
		$orderDirn = $this->state->get('list.direction');

		if (!in_array($orderCol, $this->columnsWithDirectSorting))
		{
			$items = $this->ComtjlmsHelper->multi_d_sort($items, $orderCol, $orderDirn);
		}

		return $items;
	}
}
