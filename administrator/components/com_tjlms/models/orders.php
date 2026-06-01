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

jimport('joomla.application.component.modellist');

use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

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
	 * @since   1.0.0
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
				'userfilter','statusfilter','coursefilter'
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

		// Load the filter state.
		$search = $app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		$published = $app->getUserStateFromRequest($this->context . '.filter.state', 'filter_published', '', 'string');
		$this->setState('filter.state', $published);

		$userfilter = $app->getUserStateFromRequest($this->context . '.filter.userfilter', 'userfilter', '', 'string');
		$this->setState('filter.userfilter', $userfilter);

		$statusfilter = $app->getUserStateFromRequest($this->context . '.filter.statusfilter', 'statusfilter', '', 'string');
		$this->setState('filter.statusfilter', $statusfilter);

		$coursefilter = $app->getUserStateFromRequest($this->context . '.filter.coursefilter', 'coursefilter', '', 'string');
		$this->setState('filter.coursefilter', $coursefilter);

		// Load the parameters.
		$params = ComponentHelper::getParams('com_tjlms');
		$this->setState('params', $params);

		// List state information.
		parent::populateState('a.id', 'asc');
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
	 * @return   string  A store id.
	 *
	 * @since  1.0.0
	 */
	protected function getStoreId($id = '')
	{
		// Compile the store id.
		$id .= ':' . $this->getState('filter.search');
		$id .= ':' . $this->getState('filter.state');
		$id .= ':' . $this->getState('filter.gateway');

		return parent::getStoreId($id);
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return	JDatabaseQuery
	 *
	 * @since	1.0
	 */
	protected function getListQuery()
	{
		// Create a new query object.
		$query = $this->_db->getQuery(true);

		$user = Factory::getUser();
		$olUserid = $user->id;
		$isroot = $user->authorise('core.admin');

		// Select the required fields from the table.
		$query->select(
				$this->getState(
						'list.select', 'a.*,oi.plan_id'
				)
		);
		$query->select($this->_db->qn('a.user_id', 'u_id'));
		$query->from($this->_db->qn('#__tjlms_orders', 'a'));
		$query->join('LEFT', $this->_db->qn('#__tjlms_order_items', 'oi') . ' ON (
		' . $this->_db->qn('oi.order_id') . ' = ' . $this->_db->qn('a.id') . ')');
		$query->join('LEFT',  $this->_db->qn('#__tjlms_courses', 'c') . ' ON (' . $this->_db->qn('c.id') . ' = ' . $this->_db->qn('a.course_id') . ')');

		// Join over the user field 'user_id'
		$query->select($this->_db->qn('user_id.name', 'user_id'));
		$query->join('LEFT', $this->_db->qn('#__users', 'user_id') . ' ON (' . $this->_db->qn('user_id.id') . ' = ' . $this->_db->qn('a.user_id') . ')');
		$query->where($this->_db->qn('a.processor') . ' <> ""');

		if (!$user->authorise('view.orders', 'com_tjlms'))
		{
			$query->where($this->_db->qn('c.created_by') . ' = ' . (int) $olUserid);
		}

		// Filter the items over the search string if set.
		if ($this->getState('filter.search') !== '' && $this->getState('filter.search') !== null)
		{
			$search = trim($this->getState('filter.search'));

			// Escape the search token.
			$search = '%' . $this->_db->escape($search, true) . '%';

			// Compile the different search clauses.
			$searches   = array();
			$searches[] = $this->_db->qn('c.title') . ' LIKE ' . $this->_db->q($search, false);
			$searches[] = $this->_db->qn('a.id') . ' LIKE ' . $this->_db->q($search, false);
			$searches[] = $this->_db->qn('a.order_id') . ' LIKE ' . $this->_db->q($search, false);
			$searches[] = $this->_db->qn('user_id.name') . ' LIKE ' . $this->_db->q($search, false);
			$searches[] = $this->_db->qn('user_id.username') . ' LIKE ' . $this->_db->q($search, false);
			$searches[] = $this->_db->qn('user_id.email') . ' LIKE ' . $this->_db->q($search, false);

			// Add the clauses to the query.
			$query->where('(' . implode(' OR ', $searches) . ')');
		}

		$userfilter = $this->state->get('filter.userfilter');

		if ($userfilter)
		{
			$query->where($this->_db->qn('a.user_id') . ' = ' . (int) $userfilter);
		}

		$coursefilter = $this->state->get('filter.coursefilter');

		if ($coursefilter)
		{
			$query->where($this->_db->qn('a.course_id') . ' = ' . (int) $coursefilter);
		}

		$enrollmentId = $this->state->get('filter.enrollment_id');

		// 0 is also allowed to set.
		if ($enrollmentId != '')
		{
			$query->where('a.enrollment_id=' . $enrollmentId);
		}

		$statusfilter = $this->state->get('filter.statusfilter');

		if ($statusfilter)
		{
			$query->where($this->_db->qn('a.status') . ' = ' . $this->_db->q($this->_db->escape($statusfilter, true)));
		}

		// Add the list ordering clause.
		$orderCol = $this->state->get('list.ordering');
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

			foreach ($items as $item)
			{
				$item->courseName = $this->getCourseName($item->course_id);
			}

			return $items;
	}

	/**
	 * Get total number of orders by user and course id
	 *
	 * @param   Int      $userId    User Id
	 *
	 * @param   Integer  $courseId  Course Id
	 *
	 * @return  Object of order record
	 *
	 * @since   1.0.0
	 */
	public function totalOrdersByCourseId($userId, $courseId)
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true);

		$query->select('count(*) AS total_orders');
		$query->from($db->quoteName('#__tjlms_orders'));
		$query->where($db->quoteName('course_id') . ' = ' . (int) $courseId);
		$query->where($db->quoteName('user_id') . ' = ' . (int) $userId);
		$db->setQuery($query);

		return $db->loadObject();
	}

	/**
	 * Function to update order status
	 *
	 * @param   INT  $order_id  Order ID
	 * @param   INT  $status    Order status
	 *
	 * @return  $order_id
	 *
	 * @since   1.0.0
	 */
	public function updateOrderStatus($order_id, $status)
	{
		JLoader::register('TjlmsHelper', JPATH_ADMINISTRATOR . '/components/com_tjlms/helpers/tjlms.php');

		$canDo = TjlmsHelper::getActions();

		if ($canDo->get('view.orders'))
		{
			try
			{
				$query = $this->_db->getQuery(true);

				// Add Table Path
				Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjlms/tables');

				$order_table = Table::getInstance('Orders', 'TjlmsTable', array('dbo', $this->_db));
				$order_table->load(array('id' => $order_id));
				$order_table->status = $status;

				$params               = ComponentHelper::getParams('com_tjlms');
				$allowFlexiEnrolments = $params->get('allow_flexi_enrolments', 0, 'INT');
				$enrollmentId         = 0;

				$query->select($this->_db->qn('course_id'));
				$query->from($this->_db->qn('#__tjlms_orders'));
				$query->where($this->_db->qn('id') . ' = ' . (int) $order_id);
				$this->_db->setQuery($query);
				$course = $this->_db->loadObject();

				$totalOrdersByCourseId = $this->totalOrdersByCourseId($order_table->user_id, $course->course_id);

				// Don't enrol the user to the course if the admin has allowed flexi enrolment
				if (!$allowFlexiEnrolments)
				{
					$enrollmentId = $this->updateEnrolment($order_id, $status);
				}

				if ($totalOrdersByCourseId->total_orders > 1)
				{
					JLoader::register('TjlmsCoursesHelper', JPATH_SITE . '/components/com_tjlms/helpers/courses.php');
					$tjlmsCoursesHelper = new TjlmsCoursesHelper;
					$getCoursePlanId = (int) $tjlmsCoursesHelper->getCoursePlanId($order_id);

					$planinfo       = $tjlmsCoursesHelper->getPlanDetails($getCoursePlanId);
					$endTime        = '';
					$unlimited_plan = 0;

					switch ($planinfo->time_measure)
					{
						case 'day':
							$endTime = $planinfo->duration . ' day';
							break;
						case 'week':
							$endTime = ($planinfo->duration * 7) . ' day';
							break;
						case 'month':
							$endTime = ($planinfo->duration * 30) . ' day';
							break;
						case 'year':
							$endTime = $planinfo->duration . ' year';
							break;
						case 'unlimited':
							$endTime = '10 year';
							$unlimited_plan = 1;
							break;
					}

					if ($endTime)
					{
						$endTime = 'now + ' . $endTime;
					}
					else
					{
						$endTime = 'now';
					}

					$db = Factory::getDbo();

					$query = $db->getQuery(true);

					// Fields to update.
					$fields = array(
						$db->quoteName('end_time') . ' = ' . $db->quote(Factory::getDate($endTime)->toSql(true)),
						$db->quoteName('modified_time') . ' = ' . $db->quote(Factory::getDate()->toSql(true)),
						$db->quoteName('unlimited_plan') . ' = ' . $unlimited_plan
					);

					// Conditions for which records should be updated.
					$conditions = array(
						$db->quoteName('user_id') . ' = ' . $order_table->user_id,
						$db->quoteName('course_id') . ' = ' . $planinfo->course_id
					);

					$query->update($db->quoteName('#__tjlms_enrolled_users'))->set($fields)->where($conditions);
					$db->setQuery($query);
					$db->execute();

					$enrolmentModel = BaseDatabaseModel::getInstance('Enrolment', 'TjlmsModel');
					$enrollmentId = (int) $enrolmentModel->checkUserEnrollment($planinfo->course_id, $order_table->user_id);

					if ($enrollmentId > 0)
					{
						$enrolmentModel->updateEnrollmentId($planinfo->course_id, $enrollmentId, $order_table->user_id);
					}
				}

				$query = $this->_db->getQuery(true);
				$query->select($this->_db->qn('l.id'));
				$query->from($this->_db->qn('#__tjlms_lessons', 'l'));
				$query->select($this->_db->qn('m.params'));
				$query->join('LEFT', $this->_db->qn('#__tjlms_media', 'm') . ' ON (' . $this->_db->qn('m.id') . ' = ' . $this->_db->qn('l.media_id') . ')');
				$query->where($this->_db->qn('l.course_id') . ' = ' . (int) $course->course_id);
				$this->_db->setQuery($query);
				$lessonsParams = $this->_db->loadObjectList();

				$query = $this->_db->getQuery(true);
				$query->select($this->_db->qn('l.id'));
				$query->from($this->_db->qn('#__tjlms_lessons', 'l'));
				$query->select($this->_db->qn('m.params'));
				$query->join('LEFT', $this->_db->qn('#__tjlms_media', 'm') . ' ON (' . $this->_db->qn('m.id') . ' = ' . $this->_db->qn('l.media_id') . ')');
				$query->where($this->_db->qn('l.course_id') . ' = ' . (int) $course->course_id);
				$this->_db->setQuery($query);
				$lessonsParams = $this->_db->loadObjectList();

				PluginHelper::importPlugin('tjevent');

				foreach ($lessonsParams as $lesson)
				{
					if (!empty($lesson->params))
					{
						$lesson->params = json_decode($lesson->params, true);

						if (array_key_exists("ticketid", $lesson->params))
						{
							Factory::getApplication()->triggerEvent('onAfterCourseStatusChanges', array($course->course_id, $lesson->id, $lesson->params, $status, $order_id));
						}
					}
				}

				// UPDATE ORDER TABEL FOR ENROLLMENT
				$order_table->enrollment_id = $enrollmentId;
				$order_table->store();

				// Send invoice mail
				if ($status == 'C')
				{
					JLoader::import('components.com_tjlms.helpers.mailcontent', JPATH_SITE);
					$tjlmsMailcontentHelper = new TjlmsMailcontentHelper;
					$tjlmsMailcontentHelper->sendInvoiceEmail($order_id);
				}

				PluginHelper::importPlugin('system');
				Factory::getApplication()->triggerEvent('onOrderAfterStatusChange', array($order_id, $status));

				// After order table status update, count the used_count Check if coupon code is used
				if ($order_table->coupon_code)
				{
					// Load jlike reminders model to call api to send the reminders
					require_once JPATH_ADMINISTRATOR . '/components/com_tjlms/models/coupons.php';

					// Call the actual cron code which will send the reminders
					$model         = BaseDatabaseModel::getInstance('Coupons', 'TjlmsModel');
					$model->updateCouponUsedcount($order_table->coupon_code);
				}

				return $order_id;
			}
			catch (Exception $e)
			{
				$this->setError($e->getMessage());

				return false;
			}
		}
		else
		{
			$this->setError(Text::_('JERROR_ALERTNOAUTHOR'));

			jexit();
		}
	}

	/**
	 * Function to update Enrollment status
	 *
	 * @param   INT  $order_id  Order ID
	 * @param   INT  $status    Order status
	 *
	 * @return  $order_id
	 *
	 * @since 1.0.0
	 */
	public function updateEnrolment($order_id, $status)
	{
		try
		{
			$tjlmsCoursesHelper = new tjlmsCoursesHelper;
			$comtjlmsHelper = new ComtjlmsHelper;

			// Create a new query object.
			$query = $this->_db->getQuery(true);

			// Select all records from the user profile table where key begins with "custom.".
			// Order it by the ordering field.
			$query->select($this->_db->qn(array('oi.course_id', 'o.enrollment_id', 'oi.plan_id', 'o.user_id')));
			$query->from($this->_db->qn('#__tjlms_order_items', 'oi'));
			$query->join('LEFT', $this->_db->qn('#__tjlms_orders', 'o') . ' ON (' . $this->_db->qn('o.id') . ' = ' . $this->_db->qn('oi.order_id') . ')');
			$query->where($this->_db->qn('o.id') . ' = ' . (int) $order_id);

			$this->_db->setQuery($query);

			$info = $this->_db->loadAssoc();

			$enrollmentId = $info['enrollment_id'];

			if ($status == 'C')
			{
				// Add enrollment entry
				$enrollmentId = $tjlmsCoursesHelper->addEnrolmentEntry($info['user_id'], $info['course_id'], $status);
				$enrollmentHistory = $tjlmsCoursesHelper->addEnrolmentHistory($order_id, $enrollmentId);
				$endTime = $tjlmsCoursesHelper->updateEndTimeForCourse($info['plan_id'], $enrollmentId, 1);
			}

			$orginalStatus = $comtjlmsHelper->getOrderStatus($order_id);

			if ($orginalStatus == 'C' && $status != 'C')
			{
				if ($info['enrollment_id'])
				{
					$enrollmentId = $this->updateEnrolmentStatus($info['enrollment_id'], $status);
					$enrollmentHistory = $tjlmsCoursesHelper->deleteEnrolmentHistory($order_id, $info['enrollment_id']);
					$endTime = $tjlmsCoursesHelper->updateEndTimeForCourse($info['plan_id'], $info['enrollment_id'], 0);
				}
			}

			return $enrollmentId;
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}
	}

	/**
	 * Get Course Name
	 *
	 * @param   INT  $id  Course ID
	 *
	 * @return  Object of course record
	 *
	 * @since   1.0.0
	 */
	public function getCourseName($id)
	{
		try
		{
			$query = $this->_db->getQuery(true);

			$query->select($this->_db->qn('title'));
			$query->from($this->_db->qn('#__tjlms_courses'));
			$query->where($this->_db->qn('id') . ' = ' . (int) $id);
			$this->_db->setQuery($query);

			return $this->_db->loadresult();
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}
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
		try
		{
			$enrollment_ids = array();

			// Get enrollment id's
			foreach ($cid as $eachId)
			{
				$query = $this->_db->getQuery(true);

				$query->select($this->_db->qn('enrollment_id'));
				$query->from($this->_db->qn('#__tjlms_orders'));
				$query->where($this->_db->qn('id') . ' = ' . (int) $eachId);
				$this->_db->setQuery($query);
				$enrollment_ids[] = $this->_db->loadresult();
			}

			ArrayHelper::toInteger($cid);
			$group_to_delet = implode(',', $cid);

			$query = $this->_db->getQuery(true);

			// Delete all orders as selected
			$conditions = $this->_db->qn('id') . ' IN ( ' . $group_to_delet . ' )';

			$query->delete($this->_db->qn('#__tjlms_orders'));
			$query->where($conditions);

			$this->_db->setQuery($query);

			if (!$this->_db->execute())
			{
					$this->setError($this->_db->getErrorMsg());

					return false;
			}

			// Delete Order items
			$query = $this->_db->getQuery(true);

			// Delete all orders as selected
			$conditions = $this->_db->qn('order_id') . ' IN ( ' . $group_to_delet . ' )';

			$query->delete($this->_db->qn('#__tjlms_order_items'));
			$query->where($conditions);

			$this->_db->setQuery($query);

			if (!$this->_db->execute())
			{
					$this->setError($this->_db->getErrorMsg());

					return false;
			}

			$query = $this->_db->getQuery(true);
			$query->delete($this->_db->quoteName('#__tjlms_users'));
			$query->where($conditions);
			$this->_db->setQuery($query);

			if (!$this->_db->execute())
			{
					$this->setError($this->_db->getErrorMsg());

					return false;
			}

			// Delete Emrollment entry.
			if (!empty($enrollment_ids))
			{
				$deleteEnrollment = $this->deleteEnrollmentEntry($enrollment_ids);
			}

			return true;
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}
	}

	/**
	 * Delet enrollments
	 *
	 * @param   ARRAY  $enrollment_ids  array of enrollment id
	 *
	 * @return  true
	 *
	 * @since   1.0.0
	 */
	public function deleteEnrollmentEntry($enrollment_ids)
	{
		try
		{
			if (!empty($enrollment_ids))
			{
				ArrayHelper::toInteger($enrollment_ids);
				$enrollment_to_delet = implode(',', $enrollment_ids);

				// Delete enrollment items
				$query = $this->_db->getQuery(true);

				// Delete all enrollment as selected
				$conditions = $this->_db->qn('id') . ' IN ( ' . $enrollment_to_delet . ' )';

				$query->delete($this->_db->qn('#__tjlms_enrolled_users'));
				$query->where($conditions);

				$this->_db->setQuery($query);

				if (!$this->_db->execute())
				{
						$this->setError($this->_db->getErrorMsg());

						return false;
				}
			}

			return true;
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}
	}

	/**
	 * Function to update enrollment entry status
	 *
	 * @param   INT     $enrollment_id  Enrollment Id
	 * @param   STRING  $orderStatus    Order status
	 *
	 * @return  INT  Table ID
	 *
	 * @since  1.0.0
	 */
	public function updateEnrolmentStatus($enrollment_id, $orderStatus = 'P')
	{
		try
		{
			$enrollment_id = (int) $enrollment_id;

			if ($enrollment_id)
			{
				// Create a new query object.
				$query = $this->_db->getQuery(true);

				$query->select('*');
				$query->from($this->_db->qn('#__tjlms_enrolled_users', 'c'));
				$query->where($this->_db->qn('c.id') . ' = ' . (int) $enrollment_id);

				// Reset the query using our newly populated query object.
				$this->_db->setQuery($query);

				// Load the results as a list of stdClass objects
				$enrollmentData = $this->_db->loadObject();

				if (!empty($enrollmentData) && $enrollmentData->id)
				{
					$res                = new stdClass;
					$res->id            = $enrollmentData->id;
					$enrolledOnDate     = Factory::getDate('now');
					$enrolledOnDate     = $enrolledOnDate->toSql();
					$res->modified_time = $enrolledOnDate;

					// All status other than Complete
					$res->state            = ($orderStatus == 'C') ? 1 : '-1';

						$this->_db->updateObject('#__tjlms_enrolled_users', $res, 'id');

						PluginHelper::importPlugin('system');
						Factory::getApplication()->triggerEvent('onAfterEnrolUpdate', array(
																			$res->id,
																			$res->state
																		)
											);

					return $res->id;
				}
			}
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}
	}

	/**
	 * function to place order
	 *
	 * @param   INT  $courseId  course id to which is to be bought
	 * @param   INT  $userId    user id who is buying the course
	 *
	 * @return  INT order id
	 *
	 * @since  1.1.8
	 */
	public function placeOrder($courseId, $userId)
	{
		$loggedInUser = Factory::getUser()->id;

		$this->tjlmsCoursesHelper = new tjlmsCoursesHelper;

		// $course_info              = $this->tjlmsCoursesHelper->getcourseInfo($courseId);

		JLoader::register('TjlmsHelper', JPATH_ADMINISTRATOR . '/components/com_tjlms/helpers/tjlms.php');
		$canEnroll = TjlmsHelper::canSelfEnrollCourse($courseId, $userId);

		if (!$canEnroll && $loggedInUser == $userId)
		{
			$this->setError(Text::sprintf('COM_TJLMS_COURSE_ENROLL_NOT_ALLOWED'));

			return false;
		}

		// If enrolling user is different
		if ($loggedInUser != $userId)
		{
			$canManageEnrollment = TjlmsHelper::canManageCourseEnrollment($courseId, $loggedInUser);

			if (!$canManageEnrollment)
			{
				$this->setError(Text::sprintf('COM_TJLMS_COURSE_MANAGE_ENROLL_NOT_ALLOWED'));

				return false;
			}
		}

		$this->tjlmsCoursesHelper = new tjlmsCoursesHelper;
		$orderData['user_id']     = $userId;
		$orderData['course_id']   = $courseId;
		$orderData['coupon_code'] = '';
		$plan_info                = $this->tjlmsCoursesHelper->getCourseSubplans($courseId);
		$plan_id                  = 0;

		// Check if course has unlimited plan otherwise assign last plan
		foreach ($plan_info as $plan)
		{
			if ($plan->time_measure == 'unlimited')
			{
				$plan_id = $plan->id;
				break;
			}
		}

		if (!$plan_id)
		{
			$end     = end($plan_info);
			$plan_id = $end->id;
		}

		$orderData['plan_id']        = $plan_id;
		$orderData['processor']      = 'manualSubscribe';
		$orderData['status']         = 'C';
		$orderData['bill']['email1'] = Factory::getUser($userId)->email;

		JLoader::register('TjlmsModelbuy', JPATH_SITE . '/components/com_tjlms/models/buy.php');
		JLoader::load('TjlmsModelbuy');
		$buymodel          = new TjlmsModelbuy;
		$successfulOrdered = $buymodel->createOrder($orderData, 'step_select_subsplan');
		$billingInfo       = $buymodel->createOrder($orderData, 'save_step_billinginfo');

		if ($successfulOrdered)
		{
			$successfulOrdered['plan_id']      = $plan_id;
			$successfulOrdered['time_measure'] = $plan->time_measure;
		}
		else
		{
			$this->setError(Text::_('COM_TJLMS_COURSE_ENROLL_ORDER_FAIL'));

			return false;
		}

		return $successfulOrdered;
	}

	/**
	 * Get one or more columns from orders table
	 *
	 * @param   Int  $enrolmentId  Enrolment Id
	 *
	 * @return  Object of order record
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
	 * Function to get the payment status filter
	 *
	 * @return  object
	 *
	 * @since 1.0.0
	 */
	public function getPaymentStatusFilter()
	{
		$paymentStatusArray = array();
		$paymentStatusArray[] = HTMLHelper::_('select.option', 'I', Text::_('COM_TJLMS_PSTATUS_INITIATED'));
		$paymentStatusArray[] = HTMLHelper::_('select.option', 'P', Text::_('COM_TJLMS_PSTATUS_PENDING'));
		$paymentStatusArray[] = HTMLHelper::_('select.option', 'C', Text::_('COM_TJLMS_PSTATUS_COMPLETED'));
		$paymentStatusArray[] = HTMLHelper::_('select.option', 'D', Text::_('COM_TJLMS_PSTATUS_DECLINED'));
		$paymentStatusArray[] = HTMLHelper::_('select.option', 'E', Text::_('COM_TJLMS_PSTATUS_FAILED'));
		$paymentStatusArray[] = HTMLHelper::_('select.option', 'UR', Text::_('COM_TJLMS_PSTATUS_UNDERREVIW'));
		$paymentStatusArray[] = HTMLHelper::_('select.option', 'RF', Text::_('COM_TJLMS_PSTATUS_REFUNDED'));
		$paymentStatusArray[] = HTMLHelper::_('select.option', 'CRV', Text::_('COM_TJLMS_PSTATUS_CANCEL_REVERSED'));
		$paymentStatusArray[] = HTMLHelper::_('select.option', 'RV', Text::_('COM_TJLMS_PSTATUS_REVERSED'));

		return $paymentStatusArray;
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
