<?php
/**
 * @package     Shika
 * @subpackage  com_tjlms
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\MVC\Model\ListModel;

jimport('techjoomla.common');

/**
 * Methods supporting a list of Tjlms records.
 *
 * @since  1.0.0
 */

class TjlmsModelCourses extends ListModel
{
	protected $tjlmsparams;

	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see        JController
	 *
	 * @since    1.6
	 */
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'id', 'a.id',
				'ordering', 'a.ordering',
				'state', 'a.state',
				'created_by', 'a.created_by',
				'category_id', 'a.catid',
				'title', 'a.title',
				'img', 'a.img',
				'short_desc', 'a.short_desc',
				'description', 'a.description',
				'start_date', 'a.start_date',
				'certificate_term', 'a.certificate_term',
				'type', 'a.type',
				'access', 'a.access'
			);
		}

		$this->tjlmsparams  = ComponentHelper::getParams('com_tjlms');
		$this->columnsWithoutDirectSorting = array('enrolled_users');

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
		$app = Factory::getApplication();

		// Set ordering.
		$orderCol = $app->getUserStateFromRequest($this->context . '.filter_order', 'filter_order');

		$this->setState('list.ordering', $orderCol);

		// Set ordering direction.
		$listOrder = $app->getUserStateFromRequest($this->context . 'filter_order_Dir', 'filter_order_Dir');

		if (!in_array(strtoupper($listOrder), array('ASC', 'DESC', '')))
		{
			$listOrder = 'DESC';
		}

		$this->setState('list.direction', $listOrder);

		// Load the filter state.
		$search = $app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		$published = $app->getUserStateFromRequest($this->context . '.filter.state', 'filter_published', '', 'string');

		$this->setState('filter.state', $published);

		// Filtering cat_id
		$this->setState('filter.catfilter', $app->getUserStateFromRequest($this->context . '.filter.category_id', 'filter_category_id', '', 'string'));

		// Filtering type
		$this->setState('filter.type', $app->getUserStateFromRequest($this->context . '.filter.type', 'filter_type', '', 'string'));

		if (!$app->isClient('administrator'))
		{
			// Load the parameters.
			$params = $app->getParams();
			$this->setState('params', $params);
		}

		$created_by = $app->getUserStateFromRequest($this->context . '.filter.created_by', 'filter_created_by');
		$this->setState('filter.created_by', $created_by);

		// List state information.
		parent::populateState('a.id', 'desc');
	}

	/**
	 * Method to get a store id based on model configuration state.
	 *
	 * This is necessary because the model is used by the component and
	 * different modules that might need different sets of data or different
	 * ordering requirements.
	 *
	 * @param   string  $id  A prefix for the store id.
	 *
	 * @return	string		A store id.
	 *
	 * @since	1.6
	 */
	protected function getStoreId($id = '')
	{
		// Compile the store id.
		$id .= ':' . $this->getState('filter.search');
		$id .= ':' . $this->getState('filter.state');
		$id .= ':' . $this->getState('filter.type');
		$id .= ':' . $this->getState('filter.category_id');

		return parent::getStoreId($id);
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
		$user 	= Factory::getUser();
		$userId	= $user->get('id');

		// Create a new query object.
		$query = $this->_db->getQuery(true);

		// Select the required fields from the table.
		$query->select($this->getState('list.select', 'a.*,c.title as cat,c.published as cat_status,vl.title as access_level_title'));
		$query->select('c.title AS category_title, c.created_user_id AS category_uid, c.level AS category_level');
		$query->from($this->_db->qn('#__tjlms_courses', 'a'));

		// Get course category detail
		$query->JOIN('INNER', $this->_db->qn('#__categories', 'c') . ' ON (' . $this->_db->qn('c.id') . ' = ' . $this->_db->qn('a.catid') . ')');
		$query->where($this->_db->qn('c.published') . ' <> -2');

		// Join over the parent categories.
		$query->select('parent.title AS parent_category_title, parent.id AS parent_category_id,
								parent.created_user_id AS parent_category_uid, parent.level AS parent_category_level')
			->join('LEFT', '#__categories AS parent ON parent.id = c.parent_id');

		// Join over the users for the checked out user
		$query->select($this->_db->qn('uc.name', 'editor'));
		$query->join('LEFT', $this->_db->qn('#__users', 'uc') . ' ON (' . $this->_db->qn('uc.id') . ' = ' . $this->_db->qn('a.checked_out') . ')');

		// Get course access level detail
		$query->join('LEFT', $this->_db->qn('#__viewlevels', 'vl') . ' ON (' . $this->_db->qn('vl.id') . ' = ' . $this->_db->qn('a.access') . ')');

		// Join over the user field 'created_by'
		$show_user_or_username = $this->tjlmsparams->get('show_user_or_username', 'name');

		if ($show_user_or_username == 'username')
		{
			$query->select($this->_db->qn('cr.username', 'created_by_alias'));
		}
		elseif ($show_user_or_username == 'name')
		{
			$query->select($this->_db->qn('cr.name', 'created_by_alias'));
		}
		else
		{
			$query->select($this->_db->qn('cr.id', 'created_by_alias'));
		}

		$query->join('LEFT', $this->_db->qn('#__users', 'cr') . ' ON (' . $this->_db->qn('cr.id') . ' = ' . $this->_db->qn('a.created_by') . ')');

		// Filter by published state
		$published = $this->getState('filter.state');

		if (is_numeric($published))
		{
			$query->where($this->_db->qn('a.state') . ' = ' . (int) $published);
		}
		elseif ($published === '')
		{
		$query->where($this->_db->qn('a.state') . ' IN (0, 1)');
		}

		// Filter by search in title
		$search = trim($this->getState('filter.search'));

		if (!empty($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where($this->_db->qn('a.id') . ' = ' . $this->_db->q((int) substr($search, 3)));
			}
			else
			{
				$search = '%' . $this->_db->escape($search, true) . '%';
				$query->where($this->_db->qn('a.title') . ' LIKE ' . $this->_db->q($search, false));
			}
		}

		// Filter by categories
		$categoryId = $this->getState('filter.category_id', array());

		if (!is_array($categoryId))
		{
			$categoryId = $categoryId ? array($categoryId) : array();
		}

		if (count($categoryId))
		{
			$categoryId = ArrayHelper::toInteger($categoryId);
			$categoryTable = JTable::getInstance('Category', 'JTable');
			$subCatItemsWhere = array();

			foreach ($categoryId as $filter_catid)
			{
				$categoryTable->load($filter_catid);
				$subCatItemsWhere[] = '(' .
					'c.lft >= ' . (int) $categoryTable->lft . ' AND ' .
					'c.rgt <= ' . (int) $categoryTable->rgt . ')';
			}

			$query->where('(' . implode(' OR ', $subCatItemsWhere) . ')');
		}

		// Filtering type
		$filter_type = $this->getState("filter.type");

		if ($filter_type != '')
		{
			$query->where($this->_db->qn('a.type') . ' = ' . $this->_db->q($filter_type));
		}

		$created_by = $this->getState('filter.created_by', 0);

		if ($created_by)
		{
			$query->where($this->_db->qn('a.created_by') . ' = ' . (int) $created_by);
		}

		// Filtering certificate id
		$certificateId = $this->getstate("certificate_id", '');

		if ($certificateId)
		{
			$query->where($this->_db->qn('a.certificate_id') . ' = ' . (int) $certificateId);
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
	 * get items
	 *
	 * @return  JDatabaseQuery
	 *
	 * @since   1.0.0
	 */
	public function getItems()
	{
		$path = JPATH_SITE . '/components/com_tjlms/helpers/main.php';
		JLoader::import('comtjlmsHelper', $path);
		$this->ComtjlmsHelper = new comtjlmsHelper;

		$items = parent::getItems();

		$date_format_show = $this->tjlmsparams->get('date_format_show', 'Y-m-d H:i:s');

		foreach ($items as &$obj)
		{
			$obj->start_date = HTMLHelper::_('date', $obj->start_date, $date_format_show);

			if (!empty($obj->end_date) &&  $obj->end_date != '0000-00-00 00:00:00')
			{
				$obj->end_date = HTMLHelper::_('date', $obj->end_date, $date_format_show);
			}

			// Get subscription titles
			$subscription_plans = $this->subs_plan($obj->id);
			$level_str = '';

			foreach ($subscription_plans as $s_plan)
			{
				$subs_plan = $s_plan->time_measure == 'unlimited' ? $s_plan->time_measure : $s_plan->duration . '-' . $s_plan->time_measure;

				if ($level_str)
				{
					$level_str .= "<br />";
				}

				$level_str .= $subs_plan;
			}

			$obj->subscription_plans = $level_str;

			// Get total enrolled users
			$options['IdOnly'] = 1;
			$obj->enrolled_users = count($this->ComtjlmsHelper->getCourseEnrolledUsers($obj->id, $options));

			if (!$obj->created_by_alias)
			{
				$obj->created_by_alias = Text::_('COM_TJLMS_BLOCKED_USER');
			}
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

	/**
	 * get subscription plan data as per the course ID
	 *
	 * @param   int  $course_id  course_id
	 *
	 * @return  JDatabaseQuery
	 *
	 * @since   1.0.0
	 */
	public function subs_plan($course_id)
	{
		try
		{
			$query = $this->_db->getQuery(true);
			$query->select('*');
			$query->from($this->_db->qn('#__tjlms_subscription_plans'));
			$query->where($this->_db->qn('course_id') . ' = ' . (int) $course_id);
			$this->_db->setQuery($query);
			$subscription_plans = $this->_db->loadobjectlist();

			return $subscription_plans;
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}
	}

	/**
	 * Mathod to Get all access levels of joomla
	 *
	 * @return  JDatabaseQuery
	 *
	 * @since   1.0.0
	 */
	public function getJoomlaAccessLevels()
	{
		$TjlmsHelper    = new TjlmsHelper;
		$jaccess_levels = $TjlmsHelper->getJoomlaAccessLevels();

		return $jaccess_levels;
	}
}
