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
use Joomla\CMS\Table\Table;

jimport('joomla.application.component.modellist');

/**
 * Methods supporting a list of Tjlms records.
 *
 * @since  1.0.0
 */
class TjlmsModelCoupons extends ListModel
{
	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @since   1.6
	 * @see     JController
	 */
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'id',
				'a.id',
				'ordering',
				'a.ordering',
				'state',
				'a.state',
				'created_by',
				'a.created_by',
				'name',
				'a.name',
				'code',
				'a.code',
				'value',
				'a.value',
				'val_type',
				'a.val_type',
				'max_use',
				'a.max_use',
				'max_per_user',
				'a.max_per_user',
				'description',
				'a.description',
				'params',
				'a.params',
				'from_date',
				'a.from_date',
				'exp_date',
				'a.exp_date',
				'a.used_count'
			);
		}

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

		// Set ordering.
		$orderCol = $app->getUserStateFromRequest($this->context . '.filter_order', 'filter_order');

		if (!in_array($orderCol, $this->filter_fields))
		{
			$orderCol = 'a.id';
		}

		$this->setState('list.ordering', $orderCol);

		// Set ordering direction.
		$listOrder = $app->getUserStateFromRequest($this->context . 'filter_order_Dir', 'filter_order_Dir');

		if (!in_array(strtoupper($listOrder), array('ASC', 'DESC', '')))
		{
			$listOrder = 'ASC';
		}

		// Load the filter state.
		$search = $app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		$published = $app->getUserStateFromRequest($this->context . '.filter.state', 'filter_published', '', 'string');
		$this->setState('filter.state', $published);

		// Filtering val_type
		$this->setState('filter.val_type', $app->getUserStateFromRequest($this->context . '.filter.val_type', 'val_type', '', 'string'));

		// Load the parameters.
		$params = ComponentHelper::getParams('com_tjlms');
		$this->setState('params', $params);

		// List state information.
		parent::populateState('a.name', 'asc');
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
	 * @return   string        A store id.
	 *
	 * @since    1.0.0
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
	 * @since    1.0.0
	 */
	protected function getListQuery()
	{
		// Create a new query object.

		$query = $this->_db->getQuery(true);

		// Get used count
		$used_count_query = $this->_db->getQuery(true);
		$used_count_query->select('count(*)')
			->from($this->_db->qn('#__tjlms_orders') . ' as  o')
			->where('o.coupon_code = a.code AND UPPER(o.status)="C"');
		$query->select('(' . $used_count_query->__toString() . ') as used_count');

		$tjlmsparams = ComponentHelper::getParams('com_tjlms');
		$show_user_or_username = $tjlmsparams->get('show_user_or_username', 'name');

		// Select the required fields from the table.
		$query->select($this->getState('list.select', 'a.*'));
		$query->from($this->_db->qn('#__tjlms_coupons', 'a'));

		// Join over the users for the checked out user
		$query->select($this->_db->qn('uc.name', 'editor'));
		$query->join('LEFT', $this->_db->qn('#__users', 'uc') . ' ON (' . $this->_db->qn('uc.id') . ' = ' . $this->_db->qn('a.checked_out') . ')');

		if ($show_user_or_username == 'name')
		{
			// Join over the user field 'created_by'
			$query->select($this->_db->qn('created_by.name', 'created_by'));
		}
		elseif ($show_user_or_username == 'username')
		{
			// Join over the user field 'created_by'
			$query->select($this->_db->qn('created_by.username', 'created_by'));
		}

		$query->join('LEFT', $this->_db->qn('#__users', 'created_by') . ' ON (
		' . $this->_db->qn('created_by.id') . ' = ' . $this->_db->qn('a.created_by') . ')');

		// Filter by search in title
		$search = $this->getState('filter.search');

		if (!empty($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where($this->_db->qn('a.id') . ' = ' . (int) substr($search, 3));
			}
			else
			{
				$search = '%' . $this->_db->escape($search, true) . '%';
				$query->where($this->_db->qn('a.name') . ' LIKE ' . $this->_db->q($search, false));
			}
		}

		// Filtering val_type
		$filter_val_type = $this->state->get("filter.val_type");

		if ($filter_val_type != '')
		{
			$query->where($this->_db->qn('a.val_type') . ' = ' . $this->_db->q($filter_val_type));
		}

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

		// Add the list ordering clause.
		$orderCol  = $this->state->get('list.ordering');
		$orderDirn = $this->state->get('list.direction');

		if ($orderCol && $orderDirn)
		{
			$query->order($this->_db->escape($orderCol . ' ' . $orderDirn));
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

		return $items;
	}

/**
	* To get the records
	*
	* @param   INT  $course_ids  unique course_ids
	*
	* @return  Object
	*
	* @since  1.0.0
	*/
	public function getCouponCourseId($course_ids)
	{
		try
		{
			$query = $this->_db->getQuery(true);
			$query->select($this->_db->qn('c.title'));
			$query->from($this->_db->qn('#__tjlms_courses', 'c'));
			$query->where($this->_db->qn('c.id') . ' IN(' . $course_ids . ')');
			$this->_db->setquery($query);

			return $this->_db->loadObjectList();
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}
	}

	/**
	 * Calculated used_count and store it in the counpons table
	 *
	 * @param   INT  $coupon_code  unique coupon_code
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	public function updateCouponUsedcount($coupon_code)
	{
		try
		{
			// Add Table Path

			Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjlms/tables');

			$coupon_table = Table::getInstance('coupon', 'TjlmsTable', array('dbo', $this->_db));
			$coupon_table->load(array('code' => $coupon_code));

			if ($coupon_table->id)
			{
				// Get a db connection.
				$query = $this->_db->getQuery(true);
				$query->select('count(o.id)');
				$query->from($this->_db->qn('#__tjlms_orders', 'o'));
				$query->where($this->_db->qn('o.coupon_code') . ' = ' . $this->_db->q($coupon_code));
				$query->where($this->_db->qn('o.status') . ' = "C"');
				$this->_db->setQuery($query);
				$used_count = $this->_db->loadResult();

				// Update the used_count in the table
				$coupon_table->used_count = $used_count;
				$coupon_table->store();
			}
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}
		return true;
	}
}
