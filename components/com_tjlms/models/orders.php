<?php
/**
 * @package     TJLms
 * @subpackage  com_shika
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Table\Table;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Factory;

/**
 * Methods supporting a list of Tjlms records.
 *
 * @since  1.0.0
 */
class TjlmsModelOrders extends ListModel
{
	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @since    1.0.0
	 */
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'id', 'a.id',
				'order_id', 'a.order_id',
				'plan_id', 'oi.plan_id',
				'name', 'a.name',
				'email', 'a.email',
				'user_id', 'a.user_id',
				'cdate', 'a.cdate',
				'mdate', 'a.mdate',
				'transaction_id', 'a.transaction_id',
				'payee_id', 'a.payee_id',
				'original_amount', 'a.original_amount',
				'coupon_discount', 'a.coupon_discount',
				'coupon_discount_details', 'a.coupon_discount_details',
				'amount', 'a.amount',
				'coupon_code', 'a.coupon_code',
				'status', 'a.status',
				'processor', 'a.processor',
				'ip_address', 'a.ip_address',
				'extra', 'a.extra',
				'order_tax', 'a.order_tax',
				'order_tax_details', 'a.order_tax_details',
				'customer_note', 'a.customer_note',
			);
		}

		$this->params = ComponentHelper::getParams('com_tjlms');
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
		parent::populateState('a.id', 'asc');

		// Initialise variables.
		$app = Factory::getApplication();

		// Load the filter state.
		$search = $app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		$published = $app->getUserStateFromRequest($this->context . '.filter.state', 'filter_published', '', 'string');
		$this->setState('filter.state', $published);

		$search_gateway = $app->getUserStateFromRequest($this->context . '.filter.selectgateway', 'search_gateway', '', 'string');
		$this->setState('filter.selectgateway', $search_gateway);

		// Load the parameters.
		$this->setState('params', $this->params);
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return	JDatabaseQuery
	 *
	 * @since	1.0.0
	 */
	protected function getListQuery()
	{
		// Create a new query object.
		$db = $this->getDbo();
		$query = $db->getQuery(true);

		$input = Factory::getApplication()->input;
		$layout = $input->get('layout', 'default', 'STRING');
		$user = Factory::getUser();

		// Select the required fields from the table.
		$query->select(
				$this->getState(
						'list.select', 'a.*,oi.plan_id'
				)
		);
		$query->from('`#__tjlms_orders` AS a');
		$query->join('LEFT', '`#__tjlms_order_items` AS oi ON oi.order_id=a.id');

		// Join over the user field 'user_id'
		$query->select('user_id.name AS user_id');
		$query->join('LEFT', '#__users AS user_id ON user_id.id = a.user_id');
		$query->where('a.processor <> ""');
		$query->where('a.status <> "I"');

		// Filter by user
		$filterUserId = $this->getState('filter.user_id');

		if ($filterUserId)
		{
			$query->where('a.user_id=' . $filterUserId);
		}

		// Filter by course
		$courseId = $this->getState('filter.course_id');

		if ($courseId)
		{
			$query->where('a.course_id=' . $courseId);
		}

		// Filter by search in title
		$search = $this->getState('filter.search');

		if (!empty($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where('a.id = ' . (int) substr($search, 3));
			}
			else
			{
				$search = $db->Quote('%' . $db->escape($search, true) . '%');
				$query->where('( a.order_id LIKE ' . $search . ' )');
			}
		}

		if ($layout == 'my')
		{
			$query->where('a.user_id=' . $user->id);
		}

		$search_gateway = $this->state->get('filter.selectgateway');

		if ($search_gateway)
		{
			$query->where('a.status=' . $db->Quote($db->escape($search_gateway, true)));
		}

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
	 * Function to update order status
	 *
	 * @return  $items
	 *
	 * @since   1.0.0
	 */
	public function getItems()
	{
		$items = parent::getItems();
		$dateFormat = $this->params->get('date_format_show', 'Y-m-d H:i:s');

			foreach ($items as $item)
			{
				$item->courseName = $this->getCourseName($item->course_id);
				$item->local_cdate = HTMLHelper::date($item->cdate, $dateFormat);
			}

		return $items;
	}

	/**
	 * Get Course Name
	 *
	 * @param   Int  $id  course ID
	 *
	 * @return  Object of course record
	 *
	 * @since   1.0.0
	 */
	public function getCourseName($id)
	{
		$db = $this->getDbo();
		$query = $db->getQuery(true);

		$query->select('title');
		$query->from('#__tjlms_courses');
		$query->where('id=' . $id);
		$db->setQuery($query);

		return $db->loadresult();
	}

	/**
	 * Get one or more columns from orders table
	 *
	 * @param   Int  $enrolmentId  Enrolment Id
	 *
	 * @return  Object of enrolment record
	 *
	 * @since   1.0.0
	 */
	public function getOrderByEnrollmentId($enrolmentId)
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true);

		$query->select($db->quoteName('id'));
		$query->from($db->quoteName('#__tjlms_orders'));
		$query->where($db->quoteName('enrollment_id') . ' = ' . (int) $enrolmentId);
		$db->setQuery($query);

		return $db->loadObject();
	}

	/**
	 * Get order data
	 *
	 * @param   Int  $orderId  order Id
	 *
	 * @return  Object of order record
	 *
	 * @since   1.0.0
	 */
	public function getEcTrackingData($orderId)
	{
		require_once JPATH_SITE . '/components/com_tjlms/models/orders.php';
		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjlms/tables');
		$order_table = Table::getInstance('Orders', 'TjlmsTable', array('dbo', $this->_db));
		$order_table->load(array('id' => $orderId));

		$tjlmsCoursesHelper = new tjlmsCoursesHelper;
		$ecTrackingData = $tjlmsCoursesHelper->getcourseInfo($order_table->course_id);
		$categoryTitle = str_replace("/", "-", $ecTrackingData->category_title);
		$ecTrackingData->category = $categoryTitle;

		$subscriptionId = $tjlmsCoursesHelper->getCoursePlanId($orderId);
		$subscriptionInfo = $tjlmsCoursesHelper->getPlanDetails($subscriptionId);
		$subscriptionPlan = $subscriptionInfo->duration . ' ' . $subscriptionInfo->time_measure;
		$ecTrackingData->subscription = $subscriptionPlan;
		$ecTrackingData->quantity = 1;
		$ecTrackingData->brand = '';
		$ecTrackingData->price = $order_table->amount;
		$ecTrackingData->option = '';
		$ecTrackingData->order_id = $order_table->order_id;
		$ecTrackingData->revenue = $order_table->amount;
		$ecTrackingData->tax = $order_table->order_tax;
		$ecTrackingData->coupon_code = $order_table->coupon_code;
		$ecTrackingData->params = '';
		$ecTrackingData->option = $order_table->processor;

		return $ecTrackingData;
	}
}
