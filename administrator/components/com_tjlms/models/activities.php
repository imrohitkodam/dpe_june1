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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;

jimport('joomla.application.component.modellist');

/**
 * Methods supporting a list of Tjlms records.
 *
 * @since  1.0.0
 */
class TjlmsModelActivities extends ListModel
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
				'actionfilter',
				'userfilter'
			);
		}

		$this->ComtjlmsHelper = new ComtjlmsHelper;

		$this->columnsWithDirectSorting = array('username');

		parent::__construct($config);
	}

	/**
	 * Method to auto-populate the model state.
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   int  $ordering   course_id
	 * @param   int  $direction  course_id
	 *
	 * @return  JDatabaseQuery
	 *
	 * @since   1.0.0
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		// Initialise variables.
		$app = Factory::getApplication('site');

		// List state information
		$limit = $app->getUserStateFromRequest('global.list.limit', 'limit', $app->getCfg('list_limit'));
		$this->setState('list.limit', $limit);

		$limitstart = Factory::getApplication()->input->getInt('limitstart', 0);
		$this->setState('list.start', $limitstart);

		// Load the filter state.
		$search = $app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		// Filtering user
		$userfilter = $app->getUserStateFromRequest($this->context . '.filter.userfilter', 'userfilter', '', 'INT');
		$this->setState('filter.userfilter', $userfilter);

		// Filtering Action
		$actionfilter = $app->getUserStateFromRequest($this->context . '.filter.actionfilter', 'actionfilter', '', 'INT');
		$this->setState('filter.actionfilter', $actionfilter);

		$action = $app->getUserStateFromRequest($this->context . '.filter.action', 'filter_action');
		$this->setState('filter.action', $action);

		$action = $app->getUserStateFromRequest($this->context . '.filter.type', 'filter_type');
		$this->setState('filter.type', $action);

		// Load the parameters.
		$params = ComponentHelper::getParams('com_tjlms');
		$this->setState('params', $params);

		// List state information.
		parent::populateState('a.id', 'desc');
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return	JDatabaseQuery
	 *
	 * @since	1.6
	 */
	protected function getListQuery()
	{
		$user = Factory::getUser();

		$query = $this->_db->getQuery(true);

		// Select the required fields from the table.
		$query->select(
			$this->getState(
				'list.select', 'a.*, c.title, c.id as course_id, uc.id as user_id, uc.name')
			);

		$query->from($this->_db->qn('#__tjlms_activities', 'a'));
		$query->join('LEFT', $this->_db->qn('#__tjlms_courses', 'c') . ' ON (' . $this->_db->qn('a.parent_id') . '=' . $this->_db->qn('c.id') . ')');
		$query->join('INNER', $this->_db->qn('#__users', 'uc') . ' ON (' . $this->_db->qn('a.actor_id') . ' = ' . $this->_db->qn('uc.id') . ')');

		// Filter by alluser state
		$userfilter = $this->getState('filter.userfilter');

		if ($userfilter)
		{
			$query->where($this->_db->qn('a.actor_id') . '=' . (int) $userfilter);
		}

		// Filter by alluser state
		$action = $this->getState('com_tjlms.filter.type');

		if ($action)
		{
			$query->where($this->_db->qn('a.action') . ' =' . $this->_db->quote($action));
		}

		// Filter by search in title
		$search = $this->getState('com_tjlms.filter.filter_search');

		// Filter the items over the search string if set.
		if ($search !== '' && $search !== null)
		{
			$search = $this->_db->q('%' . $this->_db->escape($search, true) . '%');
			$query->where('((' . $this->_db->qn('uc.name') . ' LIKE ' . $search . ' ) OR ( ' . $this->_db->qn('uc.username') . ' LIKE ' . $search . ' ))');
		}

		$startdate = $this->getState('com_tjlms.filter.startdate');

		if (!empty($startdate))
		{
			$fromTime = $startdate . ' 00:00:00';
			$query->where($this->_db->qn('a.added_time') . ' >= ' . $this->_db->quote($fromTime));
		}

		$enddime = $this->getState('com_tjlms.filter.enddate');

		if (!empty($enddime))
		{
			$toTime = $enddime . ' 23:59:59';
			$query->where($this->_db->qn('a.added_time') . ' <= ' . $this->_db->quote($toTime));
		}

		$query->order('added_time DESC');

		return $query;
	}

	/**
	 * Get Items functions
	 *
	 * @return	Object
	 *
	 * @since	1.0.0
	 */
	public function getItems()
	{
		$items = parent::getItems();

		$tjlmsparams = ComponentHelper::getParams('com_tjlms');
		$show_user_or_username = $tjlmsparams->get('show_user_or_username', 'name', 'string');

		foreach ($items as $index => $activity)
		{
			if ($show_user_or_username == 'name')
			{
				// Set user_name is user login name
				$user_name	= Factory::getUser($activity->actor_id)->name;
			}
			elseif ($show_user_or_username == 'username')
			{
				// Set user_name is user actual name
				$user_name	= Factory::getUser($activity->actor_id)->username;
			}

			switch ($activity->action)
			{
					case "ENROLL":
						$course_title = "<strong>" . $activity->element . "</strong>";
						$text_to_show = Text::sprintf('COM_TJLMS_ACTIVITY_STREAM_ENROLL', $user_name, $course_title);
						break;
					case "ATTEMPT":
						$lesson_link = "<em><b>" . $activity->element . "</b></em>";
						$course_title = "<strong>" . $activity->title . "</strong>";
						$params       = json_decode($activity->params);
						$attempt      = $params->attempt;
						$text_to_show = Text::sprintf('COM_TJLMS_ACTIVITY_STREAM_ATTEMPT', $user_name, $attempt, $lesson_link, $course_title);

						break;
					case "ATTEMPT_END":
						$lesson_link = "<em><b>" . $activity->element . "</b></em>";
						$course_title = "<strong>" . $activity->title . "</strong>";
						$params       = json_decode($activity->params);
						$attempt      = $params->attempt;
						$text_to_show = Text::sprintf('COM_TJLMS_ACTIVITY_STREAM_ATTEMPT_END', $user_name, $attempt, $lesson_link, $course_title);
						break;
					case "COURSE_CREATED":
							$course_title = "<strong>" . $activity->element . "</strong>";
							$text_to_show = Text::sprintf('COM_TJLMS_COURSE_CREATED_STREAM', $user_name, $course_title);
						break;
					case "COURSE_RECOMMENDED":
							$params = json_decode($activity->params);
							$text_to_show = '';

							if (isset($params->target_id))
							{
								$target_id = $params->target_id;
								$course_title = "<strong>" . $activity->element . "</strong>";
								$text_to_show = Text::sprintf('COM_TJLMS_ON_RECOMMEND_COURSE_AS_LMS', $user_name, $course_title, Factory::getUser($target_id)->name);
							}
						break;
					case "COURSE_COMPLETED":
							$course_title = "<strong>" . $activity->element . "</strong>";
							$text_to_show = Text::sprintf('COM_TJLMS_COURSE_COMPLETED_STREAM', $user_name, $course_title);
						break;

					case "LOGIN":
							$user_name = (isset($user_name) ? $user_name : '');
							$text_to_show = Text::sprintf('COM_TJLMS_ACTIVITY_LOGGEDIN', $user_name);
						break;
					case "LOGOUT":
							$user_name = (isset($user_name) ? $user_name : '');
							$text_to_show = Text::sprintf('COM_TJLMS_ACTIVITY_LOGGEDOUT', $user_name);
						break;
					default:
						$text_to_show = '';
						break;
			}

			$items[$index]->actionString = $text_to_show;
		}

		return $items;
	}

	/**
	 * Render view.
	 *
	 * @param   string  $type    An optional associative array of configuration settings.
	 * @param   string  $prefix  An optional associative array of configuration settings.
	 * @param   array   $config  An optional associative array of configuration settings.
	 *
	 * @since   2.2
	 * @return   null
	 */
	public function getTable($type = 'activity', $prefix = 'TjlmsTable', $config = array())
	{
		return Table::getInstance($type, $prefix, $config);
	}

	/**
	 * get All users
	 *
	 * @return   ARRAY
	 *
	 * @since   2.2
	 */
	public function getAllUsers()
	{
		$query = $this->_db->getQuery(true);

		// Select the required fields from the table.
		$query->select(
			$this->getState(
				'list.select', 'u.id,u.name'
			)
		);
		$query->from($this->_db->qn('#__users', 'u'));
		$query->where($this->_db->qn('u.block') . '<> 1');
		$this->_db->setQuery($query);

		$allUsers = $this->_db->loadobjectlist();

		return $allUsers;
	}
}
