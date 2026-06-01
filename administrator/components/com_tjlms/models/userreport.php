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
class TjlmsModelUserreport extends ListModel
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

		$this->columnsWithDirectSorting = array('u.id','u.name','u.username','u.email','u.block');

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

		// Filtering access
		$accesslevel = $app->getUserStateFromRequest($this->context . '.filter.accesslevel', 'accesslevel', '', 'INT');
		$this->setState('filter.accesslevel', $accesslevel);

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

		parent::populateState('u.id', 'desc');
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

		// Select the required fields from the table.
		$query->select($this->getState('list.select', 'u.*'));
		$query->from('`#__users` AS u');
		$query->join('inner', '`#__tjlms_enrolled_users` as eu ON u.id = eu.user_id');

		$userfilter = $this->getState('filter.userfilter');

		if ($userfilter)
		{
			$query->where('u.id=' . $userfilter);
		}

		$accesslevel = $this->getState('filter.accesslevel');

		if ($accesslevel)
		{
			$query->join('left', '`#__user_usergroup_map` as uum ON u.id = uum.user_id');
			$query->where('uum.group_id=' . $accesslevel);
		}

		// Filter by search in title
		$search = $this->getState('filter.search');

		if (!empty($search))
		{
			$search = $db->Quote('%' . $db->escape($search, true) . '%');
			$query->where('(( u.name LIKE ' . $search . ' ) OR ( u.username LIKE ' . $search . ' ) OR ( u.email LIKE ' . $search . ' ))');
		}

		$query->group('u.id');

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

		foreach ($items as $ind => $User)
		{
			$items[$ind]->groups = $this->getGroups($User->id);

			// Get Enrollment Data
			$db = Factory::getDBO();

			$query = $db->getQuery(true);
			$query->select('COUNT(IF(eu.state="1",1, NULL)) as enrolled_courses, COUNT(IF(eu.state="0",1, NULL)) as pending_enrollment');
			$query->from('#__tjlms_enrolled_users as eu');
			$query->join('RIGHT', '#__tjlms_courses as c ON c.id = eu.course_id');
			$query->where('c.state=1');
			$query->where('eu.user_id=' . $User->id);
			$db->setQuery($query);
			$EnrollmentData = $db->loadAssoc();

			$items[$ind]->enrolled_courses = $EnrollmentData['enrolled_courses'];
			$items[$ind]->pending_enrollment = $EnrollmentData['pending_enrollment'];

			// Get count of enrolled courses for the user
			$db = Factory::getDbo();

			// Create a new query object.
			$query = $db->getQuery(true);

			// Select all records from the user profile table where key begins with "custom.".
			// Order it by the ordering field.
			$query->select('COUNT(ct.id) as totalCompletedCourses');
			$query->from($db->quoteName('#__tjlms_course_track') . ' as ct');
			$query->join('INNER', $db->quoteName('#__tjlms_courses') . ' as c ON c.id=ct.course_id');
			$query->where($db->quoteName('ct.user_id') . ' = ' . $User->id);
			$query->where($db->quoteName('ct.status') . ' = "C"');
			$query->where($db->quoteName('c.state') . ' = 1');

			// Reset the query using our newly populated query object.
			$db->setQuery($query);

			// Load the results as a list of stdClass objects (see later for more options on retrieving data).
			$items[$ind]->totalCompletedCourses = $db->loadresult();

			$items[$ind]->inCompletedCourses = $items[$ind]->enrolled_courses - $items[$ind]->totalCompletedCourses;
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

	/**
	 * To get User Groups
	 *
	 * @param   int  $user_id  The user ID
	 *
	 * @return  string  $groups_str
	 *
	 * @since  1.0.0
	 */
	public function getGroups($user_id)
	{
		$db     = Factory::getDBO();
		$groups = array();
		$query  = "SELECT ug.title FROM #__usergroups as ug, #__user_usergroup_map as uum where uum.group_id= ug.id and user_id=" . $user_id;
		$db->setQuery($query);
		$groups     = $db->loadColumn();
		$groups_str = '';

		for ($i = 0; $i < count($groups); $i++)
		{
			$groups_str .= $groups[$i] . ' ';
		}

		return $groups_str;
	}
}
