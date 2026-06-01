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
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Language\Text;
jimport('joomla.application.component.modellist');

/**
 * Methods supporting a list of Tjlms records.
 *
 * @since  1.0.0
 */
class TjlmsModelManageenrollments extends ListModel
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
				'a.id',
				'state',
				'a.state',
				'course_id',
				'a.course_id',
				'user_id',
				'a.user_id',
				'timestart',
				'a.timestart',
				'name',
				'u.name',
				'username',
				'u.username',
				'coursefilter'
			);
		}

		$this->ComtjlmsHelper = new ComtjlmsHelper;
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
		// Initialise variables.
		$app = Factory::getApplication('administrator');

		// Load the filter state.
		$search = $app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		$published = $app->getUserStateFromRequest($this->context . '.filter.state', 'filter_published', '', 'string');
		$this->setState('filter.state', $published);

		// Filtering course
		$coursefilter = $app->getUserStateFromRequest($this->context . '.filter.coursefilter', 'coursefilter', '', 'INT');

		$this->setState('filter.coursefilter', $coursefilter);

		// Load the parameters.
		$params = ComponentHelper::getParams('com_tjlms');
		$this->setState('params', $params);

		// List state information.
		parent::populateState('a.id', 'desc');

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
		$user = Factory::getUser();
		$olUserid = $user->id;
		$isroot = $user->authorise('core.admin');

		// Create a new query object.
		$db    = $this->getDbo();
		$query = $db->getQuery(true);

		// Select the required fields from the table.
		$query->select($this->getState('list.select', 'a.*, c.title, u.name, u.username'));
		$query->from('`#__tjlms_enrolled_users` AS a');
		$query->join('INNER', '`#__tjlms_courses` AS c ON c.id = a.course_id');
		$query->join('INNER', '`#__users` AS u ON a.user_id = u.id');
		$query->join('INNER', '#__categories as cat ON cat.id=c.catid');
		$query->where('c.state=1 AND cat.published=1');

		if (!$isroot)
		{
			$query->where('created_by=' . $olUserid);
		}

		// Filter by published state
		$published = $this->getState('filter.state');

		if (is_numeric($published))
		{
			$query->where('a.state = ' . (int) $published);
		}
		elseif ($published === '')
		{
			$query->where('(a.state IN (0, 1))');
		}

		$input    = Factory::getApplication()->input;
		$courseId = $input->get('course_id', '', 'INT');

		if ($courseId)
		{
			$query->where('(a.state=1)');
		}

		// Get user ID if view called from course list view to view enrolled users.
		if (!$courseId)
		{
			$courseId = $this->getState('filter.coursefilter');
		}

		// Filtering type
		if ($courseId != '')
		{
			$query->where('a.course_id = ' . $courseId);
		}

		// Filter by search in title
		$search = $this->getState('filter.search');

		if (!empty($search))
		{
			$search = $db->Quote('%' . $db->escape($search, true) . '%');
			$query->where('(( u.name LIKE ' . $search . ' ) OR ( u.username LIKE ' . $search . ' ))');
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
	 * @return  Object
	 *
	 * @since  1.0.0
	 */
	public function getItems()
	{
		$items = parent::getItems();

		foreach ($items as $ind => $courseUser)
		{
			$items[$ind]->groups = $this->getGroups($courseUser->user_id);
		}

		return $items;
	}

	/**
	 * To plublish and unpublish enrolledment.
	 *
	 * @param   JRegistry  $items     The item to update.
	 * @param   JRegistry  $state     The state for the item.
	 * @param   int        $courseId  The course ID
	 *
	 * @return  true or false
	 *
	 * @since  1.0.0
	 */
	public function setItemState($items, $state, $courseId)
	{
		$db = Factory::getDBO();

		// $todaydatetime = JFactory::getDate()->toFormat('Y-m-d H:i:s');

		if (is_array($items))
		{
			foreach ($items as $id)
			{
				$object        = new stdClass;
				$object->id    = $id;
				$object->state = $state;

				$date = Factory::getDate();
				$date = $date->toSql(true);
				$object->modified_time = $date;

				if (!$db->updateObject('#__tjlms_enrolled_users', $object, 'id'))
				{
					$this->setError($this->_db->getErrorMsg());

					return false;
				}

				if ($state == '1')
				{
					$plan_id = $this->getPlanId($id);

					if (!empty($plan_id))
					{
						$tjlmsCoursesHelper = new tjlmsCoursesHelper;
						$endTime            = $tjlmsCoursesHelper->updateEndTimeForCourse($plan_id, $id);
					}
				}

				PluginHelper::importPlugin('system');
				Factory::getApplication()->triggerEvent('onAfterApprovecourseEnrolement', array(
																				$id,
																				$state,
																				Factory::getUser()->id
																			)
									);
			}
		}

		return true;
	}

	/**
	 * To get subscription plan ID
	 *
	 * @param   int  $enrollmentId  The enrollment table ID
	 *
	 * @return  true or false
	 *
	 * @since  1.0.0
	 */
	public function getPlanId($enrollmentId)
	{
		$db    = Factory::getDBO();
		$query = $db->getQuery(true);
		$query->select('course_id,user_id');
		$query->from($db->quoteName('#__tjlms_enrolled_users'));
		$query->where($db->quoteName('id') . ' = ' . $enrollmentId);
		$db->setQuery($query);
		$enrollmentDetails = $db->loadObject();

		$plan_id = '';

		if ($enrollmentDetails->course_id)
		{
			$tjlmsCoursesHelper = new tjlmsCoursesHelper;
			$courseInfo            = $tjlmsCoursesHelper->getCourseColumn($enrollmentDetails->course_id, array('id','type'));

			if ($courseInfo && $courseInfo->id > 0 && $courseInfo->type == 1)
			{
				// Get plan id from order item table
				$query = $db->getQuery(true);
				$query->select('oi.plan_id');
				$query->from($db->quoteName('#__tjlms_order_items') . 'AS oi');
				$query->join('INNER', $db->quoteName('#__tjlms_orders') . 'AS o ON o.id=oi.order_id');
				$query->where('o.course_id = ' . $enrollmentDetails->course_id . ' AND o.user_id = ' . $enrollmentDetails->user_id);
				$db->setQuery($query);
				$plan_id = $db->loadResult();
			}
		}

		return $plan_id;
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
			$groups_str .= $groups[$i] . '<br />';
		}

		return $groups_str;
	}

	/**
	 * To All courses
	 *
	 * @return  obj
	 *
	 * @since  1.0.0
	 */
	public function getAllCourses()
	{
		$db    = Factory::getDBO();
		$query = " SELECT tc.id as value, tc.title as text FROM #__tjlms_courses as tc";
		$db->setQuery($query);

		return $db->loadObjectList();
	}

	/**
	 * Delet orders
	 *
	 * @param   ARRAY  $cid  array of order id
	 *
	 * @return  true
	 *
	 * @since   1.0.0
	 */
	public function delete($cid)
	{
		$db = Factory::getDbo();
		$not_allowed_del = array();
		$app = Factory::getApplication();

		// Add Table Path
		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjlms/tables');

		foreach ($cid as $key => $eachEnrollment)
		{
			$Enrolledusers = Table::getInstance('Enrolledusers', 'TjlmsTable', array('dbo', $db));
			$Enrolledusers->load(array('id' => $eachEnrollment));

			$course = Table::getInstance('course', 'TjlmsTable', array('dbo', $db));
			$course->load(array('id' => $Enrolledusers->course_id));

			if ($course->type == 1)
			{
				if (($key = array_search($eachEnrollment, $cid)) !== false)
				{
					$not_allowed_del[] = $eachEnrollment . ' : ' . $Enrolledusers->user_id . ' : ' . $course->title;

					unset($cid[$key]);
				}
			}
		}

		if ($cid != null)
		{
			$enrollmentToDelet = implode(',', $cid);

			$query = $db->getQuery(true);

			// Delete all orders as selected
			$conditions = array(
				$db->quoteName('id') . ' IN ( ' . $enrollmentToDelet . ' )',
			);

			$query->delete($db->quoteName('#__tjlms_enrolled_users'));
			$query->where($conditions);

			$db->setQuery($query);

			if (!$db->execute())
			{
					$this->setError($this->_db->getErrorMsg());

					return false;
			}
		}

		if (count($not_allowed_del))
		{
			$app->enqueueMessage(Text::sprintf(Text::_('COM_TJLMS_CAN_DELETE_ENROLLMENT'), implode('<br>', $not_allowed_del)), 'notice');
		}

		return count($cid);
	}
}
