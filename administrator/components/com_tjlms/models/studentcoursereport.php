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
class TjlmsModelstudentcoursereport extends ListModel
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
		$this->TjlmsCoursesHelper = new TjlmsCoursesHelper;
		$this->ComtjlmstrackingHelper = new ComtjlmstrackingHelper;

		$this->columnsWithDirectSorting = array('c.id','eu.course_id','c.title','cat.title','eu.enrolled_on_time','eu.end_time');

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
		$query->select('eu.*,c.title,c.catid,c.access,c.certificate_term');
		$query->select('cat.title as cat_title');
		$query->from('`#__tjlms_enrolled_users` AS eu');
		$query->join('right', '`#__users` as u ON u.id = eu.user_id');
		$query->join('LEFT', '#__tjlms_courses as c ON c.id = eu.course_id');
		$query->join('LEFT', '#__categories AS cat ON c.catid = cat.id');

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
			$query->where($db->quoteName('eu.user_id') . " = " . $db->quote($courseuserfilter));
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
		$items = parent::getItems();

		foreach ($items as $ind => $course)
		{
			if ($course->end_time == '0000-00-00 00:00:00')
			{
				$course->end_time = '-';
			}

			$items[$ind]->user_name = Factory::getUser($course->user_id)->name;
			$items[$ind]->user_username = Factory::getUser($course->user_id)->username;
			$items[$ind]->useremail = Factory::getUser($course->user_id)->email;

			$cer_term = Text::_("COM_TJLMS_FORM_OPT_COURSE_CERTIFICATE_TERM_NOCERTI");

			if ($course->certificate_term == "1")
			{
				$cer_term = Text::_("COM_TJLMS_FORM_OPT_COURSE_CERTIFICATE_TERM_COMPALL");
			}
			elseif ($course->certificate_term == "2")
			{
				$cer_term = Text::_("COM_TJLMS_FORM_OPT_COURSE_CERTIFICATE_TERM_PASSALL");
			}

			$items[$ind]->certificate_term = $cer_term;

			// Get access level titles.
			$query = $db->getQuery(true);
			$query->select('title');
			$query->from('#__viewlevels');
			$query->where("id='" . $course->access . "'");
			$db->setQuery($query);
			$items[$ind]->access_level_title = $db->loadResult();

			// Get %completion
			$progress = $this->TjlmsCoursesHelper->getCourseProgress($course->course_id, $course->user_id);
			$items[$ind]->completion = $progress['completionPercent'];

			// Get total time spent
			$query = $db->getQuery(true);
			$query->select('SEC_TO_TIME(SUM(TIME_TO_SEC(time_spent))) as totalTimeSpent');
			$query->from('`#__tjlms_lesson_track` AS lt');
			$query->join('INNER', '`#__tjlms_lessons` as l ON lt.lesson_id = l.id');
			$query->where('lt.user_id = ' . $course->user_id);
			$query->where('l.course_id = ' . $course->course_id);
			$db->setQuery($query);
			$items[$ind]->totaltimespent = $db->loadResult();
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
