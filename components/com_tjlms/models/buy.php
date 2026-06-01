<?php
/**
 * @package     Shika
 * @subpackage  com_tjlms
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.model');
jimport('joomla.database.table.user');
jimport('techjoomla.common');

use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Access\Access;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 * Methods supporting buy process
 *
 * @since  1.0.0
 */
class TjlmsModelbuy extends BaseDatabaseModel
{
	public $total = null;

	public $pagination = null;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 *
	 */
	public function __construct()
	{
		parent::__construct();

		$TjGeoHelper = JPATH_ROOT . DS . 'components/com_tjfields/helpers/geo.php';

		if (!class_exists('TjGeoHelper'))
		{
			JLoader::register('TjGeoHelper', $TjGeoHelper);
			JLoader::load('TjGeoHelper');
		}

		$this->TjGeoHelper = new TjGeoHelper;
		$this->techjoomlacommon = new TechjoomlaCommon;
	}

	/**
	 * To Fetch country list from Db
	 *
	 * @return  Object of countries
	 *
	 * @since  1.0.0
	 */
	public function getCountry()
	{
		return $this->TjGeoHelper->getCountryList('com_tjlms');
	}

	/**
	 * To Fetch state list from Db
	 *
	 * @param   INT  $country  Country ID
	 *
	 * @return  mixed
	 *
	 * @since  1.0.0
	 */
	public function getuserState($country)
	{
		return $this->TjGeoHelper->getRegionListFromCountryID($country);
	}

	/**
	 * Get user data that would be prefill during billing info.
	 *
	 * @param   INT  $orderid  Order ID
	 *
	 * @return  ARRAY  User data
	 *
	 * @since  1.0.0
	 */
	public function getuserdata($orderid = 0)
	{
		$query = $this->_db->getQuery(true);

		$params   = ComponentHelper::getParams('com_tjlms');
		$user     = Factory::getUser();
		$userdata = array();

		if ($orderid and !($user->id))
		{
			$query->select('u.*');
			$query->select($this->_db->qn('o.accept_terms', 'ptnc'));
			$query->from($this->_db->qn('#__tjlms_users', 'u'));
			$query->join('LEFT', $this->_db->qn('#__tjlms_orders', 'o') . ' ON (' . $this->_db->qn('u.order_id') . ' = ' . $this->_db->qn('o.id') . ')');
			$query->where($this->_db->qn('u.order_id') . ' = ' . $this->_db->q($orderid));
			$query->order($this->_db->qn('u.id') . ' DESC');
			$query->setLimit('1');
		}
		else
		{
			$query->select('u.*');
			$query->select($this->_db->qn('o.accept_terms', 'ptnc'));
			$query->from($this->_db->qn('#__tjlms_users', 'u'));
			$query->join('LEFT', $this->_db->qn('#__tjlms_orders', 'o') . ' ON (' . $this->_db->qn('u.order_id') . ' = ' . $this->_db->qn('o.id') . ')');
			$query->where($this->_db->qn('u.user_id') . ' = ' . $this->_db->q($user->id));
			$query->order($this->_db->qn('u.id') . ' DESC');
			$query->setLimit('1');
		}

		$this->_db->setQuery($query);
		$result = $this->_db->loadObjectList();

		if (!empty($result))
		{
			if ($result[0]->address_type == 'BT')
			{
				$userdata['BT'] = $result[0];
			}
			elseif ($result[1]->address_type == 'BT')
			{
				$userdata['BT'] = $result[1];
			}
		}
		else
		{
			$row             = new stdClass;
			$row->user_email = $user->email;
			$userdata['BT']  = $row;
			$userdata['ST']  = $row;
		}

		return $userdata;
	}

	/**
	 * during checkout...used for recalculating amount...return the price of the plan
	 *
	 * @param   INT  $selectedPlan  Selected plan by the user
	 * @param   INT  $courseId      Selected Course ID
	 *
	 * @return  STRING  Amount for the plan
	 *
	 * @since  1.0.0
	 */
	public function getoriginalAmt($selectedPlan, $courseId)
	{
		$query = $this->_db->getQuery(true);
		$query->select("price");
		$query->from("#__tjlms_subscription_plans");
		$query->where("id = " . $this->_db->quote($selectedPlan));
		$query->where("course_id = " . $this->_db->quote($courseId));

		$this->_db->setQuery($query);
		$price = $this->_db->loadresult();

		return $price;
	}

	/**
	 * Apply coupon to the actual value. during checkout.
	 *
	 * @param   INT     $originalamount  Original amount for ckeckout
	 * @param   STRING  $coupon_code     Coupon applied if any
	 * @param   STRING  $course_id       Couse against which coupon is to apply
	 * @param   STRING  $plan_id         Plan against which coupon of course is to apply
	 *
	 * @return  ARRAY  $vars  Data
	 *
	 * @since  1.0.0
	 */
	public function applycoupon($originalamount, $coupon_code = '', $course_id = '', $plan_id = '')
	{
		$coupon_code     = trim($coupon_code);
		$val             = 0;
		$coupon_discount = $this->getcoupon($coupon_code, $course_id, $plan_id);

		if ($coupon_discount->status == "ok")
		{
			if ($coupon_discount->data[0]->val_type == 1)
			{
				$val = ($coupon_discount->data[0]->value / 100) * ($originalamount);
			}
			else
			{
				$val                             = $coupon_discount->data[0]->value;
				$vars['coupon_discount_details'] = json_encode($coupon_discount);
			}
		}

		$amt                     = $originalamount - $val;
		$vars['original_amt']    = $originalamount;
		$vars['amt']             = $amt;
		$vars['coupon_discount'] = $val;

		return $vars;
	}

	/**
	 * Method to store a order record.
	 * Order is placed using this function.
	 *
	 * @param   ARRAY   $orderdata  Order details
	 * @param   STRING  $step       Checkout step
	 * @param   INT     $return     $return=1 then normal api call
	 *
	 * @return  BOOLEAN|ARRAY|JSON  $data
	 *
	 * @since  1.0.0
	 */
	public function createOrder($orderdata = '', $step = '', $return = 0)
	{
		if (!$orderdata['user_id'])
		{
			echo Text::_('COM_TJLMS_SESSION_EXIRED');

			return false;
		}

		if ($step == 'step_select_subsplan')
		{
			$orderdata['return'] = $return;

			return $this->createSubscriptionOrder($orderdata);
		}

		if ($step == 'save_step_billinginfo')
		{
			return $this->saveBillingInfo($orderdata);
		}
	}

	/**
	 * returns User data
	 *
	 * @param   INT    $uid              User Id
	 * @param   ARRAY  $billingarr       user billing address
	 * @param   INT    $insert_order_id  order ID
	 *
	 * @return  STRING  Amount for the plan
	 *
	 * @since  1.0.0
	 */
	public function billingaddr($uid, $billingarr, $insert_order_id)
	{
		$this->_db->setQuery('SELECT order_id FROM #__tjlms_users WHERE order_id=' . $insert_order_id);
		$order_id = (string) $this->_db->loadResult();

		foreach ($billingarr as &$value)
		{
			$value = strip_tags($value);
		}

		require_once JPATH_SITE . '/components/com_tjlms/helpers/mailcontent.php';
		$tjlmsMailcontentHelper = new TjlmsMailcontentHelper;

		if ($order_id)
		{
			$query = "DELETE FROM #__tjlms_users    WHERE order_id=" . $insert_order_id;
			$this->_db->setQuery($query);

			if (!$this->_db->execute())
			{
				echo $this->_db->stderr();

				return false;
			}
		}

		$row                      = new stdClass;
		$row->user_id             = $uid;
		$row->user_email          = $billingarr['email1'];
		$row->address_type        = 'BT';
		$row->firstname           = !empty($billingarr['fnam']) ? $billingarr['fnam'] : '';
		$row->lastname            = !empty($billingarr['lnam']) ? $billingarr['lnam'] : '';
		$row->country_code        = !empty($billingarr['country']) ? $billingarr['country'] : '';
		$row->country_mobile_code = !empty($billingarr['country_mobile_code']) ? $billingarr['country_mobile_code'] : '0';

		if (!empty($billingarr['vat_num']))
		{
			$row->vat_number = $billingarr['vat_num'];
		}
		else
		{
			$row->vat_number = '';
		}

		$row->tax_exempt = !empty($billingarr['tax_exempt']) ? $billingarr['tax_exempt'] : '0';
		$row->address    = !empty($billingarr['addr']) ? $billingarr['addr'] : '';
		$row->city       = !empty($billingarr['city']) ? $billingarr['city'] : '';
		$row->state_code = !empty($billingarr['state']) ? $billingarr['state'] : '';
		$row->zipcode    = !empty($billingarr['zip']) ? $billingarr['zip'] : '';
		$row->phone      = !empty($billingarr['phon']) ? $billingarr['phon'] : '';
		$row->approved   = '1';
		$row->order_id   = $insert_order_id;

		if (!$this->_db->insertObject('#__tjlms_users', $row, 'id'))
		{
			echo $this->_db->stderr();

			return false;
		}

		$params = ComponentHelper::getParams('com_tjlms');

		// Save customer note in order table
		$order = new stdClass;

		if ($insert_order_id)
		{
			$order->id            = $insert_order_id;
			$order->customer_note = $billingarr['comment'];

			if ($uid)
			{
				$order->name  = Factory::getUser($uid)->name;
				$order->email = Factory::getUser($uid)->email;
			}
			else
			{
				$order->name  = $billingarr['fnam'] . " " . $billingarr['lnam'];
				$order->email = $billingarr['email1'];
			}

			if (!$this->_db->updateObject('#__tjlms_orders', $order, 'id'))
			{
				echo $this->_db->stderr();

				return false;
			}
		}

		// Send mail on new order placed
		$session    = Factory::getSession();
		$sendUpdateMail = $session->get('sendUpdateMail');

		if ($sendUpdateMail == 1)
		{
			$tjlmsMailcontentHelper->sendInvoiceEmail($row->order_id);
		}

		$session->set('sendUpdateMail', '0');

		return $row->user_id;
	}

	/**
	 * Update order details.
	 *
	 * @param   INT    $orderid    Order ID
	 * @param   ARRAY  $orderInfo  Order data
	 *
	 * @return  boolean  true or false
	 *
	 * @since  1.0.0
	 */
	public function updateOrderDetails($orderid, $orderInfo = array())
	{
		$obj = new stdClass;

		$obj->id = $orderid;

		if (isset($orderInfo['user_id']))
		{
			$obj->user_id = $orderInfo['user_id'];
		}

		if (isset($orderInfo['email']))
		{
			$obj->email = $orderInfo['email'];
		}

		if (isset($orderInfo['enrollment_id']))
		{
			$obj->enrollment_id = $orderInfo['enrollment_id'];
		}

		if (isset($orderInfo['accpt_terms']) && $orderInfo['accpt_terms'] == 1)
		{
			$obj->accept_terms = $orderInfo['accpt_terms'];
		}

		// Update order entry.
		if (!$this->_db->updateObject('#__tjlms_orders', $obj, 'id'))
		{
			echo $this->_db->stderr();

			return false;
		}
		else
		{
			return true;
		}
	}

	/**
	 * Function to create oder during checkout
	 *
	 * @param   INT  $orderdata  Orderdata
	 *
	 * @return  mixed
	 *
	 * @since  1.0.0
	 */
	public function createMainOrder($orderdata)
	{
		$res = $this->createOrderObject($orderdata);

		// Update order if orde_id present
		if (isset($orderdata['order_id']))
		{
			$res->id = $orderdata['order_id'];
			$this->_db->updateObject('#__tjlms_orders', $res, 'id');
			$insert_order_id = $orderdata['order_id'];
		}
		else
		{
			if (isset($res->enrollment_id) || empty($res->enrollment_id))
			{	
				$res->enrollment_id = '0';
			}

			if (isset($res->customer_note) || empty($res->customer_note))
			{	
				$res->customer_note = '';
			}

			if (isset($res->accept_terms) || empty($res->accept_terms))
			{	
				$res->accept_terms = '0';
			}

			// Store Order to tjlms Table
			$lang      = Factory::getLanguage();
			$extension = 'com_tjlms';

			$base_dir = JPATH_ROOT;
			$lang->load($extension, $base_dir);

			$com_params     = ComponentHelper::getParams('com_tjlms');
			$integration    = $com_params->get('integration');
			$currency       = $com_params->get('currency');
			$order_prefix   = $com_params->get('order_prefix');
			$separator      = $com_params->get('separator');
			$random_orderid = $com_params->get('random_orderid');
			$padding_count  = $com_params->get('padding_count');

			// Lets make a random char for this order
			// Take order prefix set by admin
			$order_prefix = (string) $order_prefix;

			// String length should not be more than 5
			$order_prefix = substr($order_prefix, 0, 5);

			// Take separator set by admin
			$separator     = (string) $separator;
			$res->order_id = $order_prefix . $separator;

			// Check if we have to add random number to order id
			$use_random_orderid = (int) $random_orderid;

			if ($use_random_orderid)
			{
				$random_numer = $this->_random(5);
				$res->order_id .= $random_numer . $separator;

				/* this length shud be such that it matches the column lenth of primary key
				// it is used to add pading
				// order_id_column_field_length - prefix_length - no_of_underscores - length_of_random number */
				$len = (23 - 5 - 2 - 5);
			}
			else
			{
				/* This length shud be such that it matches the column lenth of primary key
				// It is used to add pading
				// Order_id_column_field_length - prefix_length - no_of_underscores */
				$len = (23 - 5 - 2);
			}

			if (!$this->_db->insertObject('#__tjlms_orders', $res, 'id'))
			{
				echo $this->_db->stderr();

				return false;
			}

			$insert_order_id = $orders_key = $this->_db->insertid();

			$this->_db->setQuery('SELECT order_id FROM #__tjlms_orders WHERE id=' . $orders_key);
			$order_id      = (string) $this->_db->loadResult();
			$maxlen        = 23 - strlen($order_id) - strlen($orders_key);
			$padding_count = (int) $padding_count;

			// Use padding length set by admin only if it is les than allowed(calculate) length
			if ($padding_count > $maxlen)
			{
				$padding_count = $maxlen;
			}

			if (strlen((string) $orders_key) <= $len)
			{
				$append = '';

				for ($z = 0; $z < $padding_count; $z++)
				{
					$append .= '0';
				}

				$append = $append . $orders_key;
			}

			$resd     = new stdClass;
			$resd->id = $orders_key;
			$order_id = $resd->order_id = $order_id . $append;

			if (!$this->_db->updateObject('#__tjlms_orders', $resd, 'id'))
			{
				echo $this->_db->stderr();

				return false;
			}
		}

		PluginHelper::importPlugin('system');
		Factory::getApplication()->triggerEvent('onOrderAfterStatusChange', array($res->id, $res->status));

		return $insert_order_id;
	}

	/**
	 * Function to create the object order
	 *
	 * @param   ARRAY  $data  order data
	 *
	 * @return  object
	 *
	 * @since  1.0.0
	 */
	public function createOrderObject($data)
	{
		$res = new StdClass;

		if (isset($data['name']))
		{
			$res->name = $data['name'];
		}

		if (isset($data['email']))
		{
			$res->email = $data['email'];
		}

		if (isset($data['user_id']))
		{
			$res->user_id = $data['user_id'];
		}

		if (isset($data['enrollment_id']))
		{
			$res->enrollment_id = $data['enrollment_id'];
		}

		$res->coupon_code = $data['coupon_code'];

		$res->course_id               = $data['course_id'];
		$res->coupon_discount         = $data['coupon_discount'];
		$res->coupon_discount_details = $data['coupon_discount_details'];
		$res->order_tax               = $data['order_tax'];
		$res->order_tax_details       = $data['order_tax_details'];

		$res->cdate = $this->techjoomlacommon->getDateInUtc(HTMLHelper::date('now', 'Y-m-d H:i:s', true));
		$res->mdate = $this->techjoomlacommon->getDateInUtc(HTMLHelper::date('now', 'Y-m-d H:i:s', true));

		if (isset($data['processor']))
		{
			$res->processor = $data['processor'];
		}

		if (isset($data['customer_note']))
		{
			$res->customer_note = $data['customer_note'];
		}

		if (isset($data['status']))
		{
			$res->status = $data['status'];
		}
		else
		{
			$res->status          = 'I';
		}

		if (isset($data['status']))
		{
			$res->status = $data['status'];
		}

		// This is calculated amount
		$res->original_amount = $data['original_amt'];
		$res->amount          = $data['amount'];
		$res->ip_address      = $_SERVER["REMOTE_ADDR"];

		return $res;
	}

	/**
	 * Function used to recalculate amount
	 *
	 * @param   ARRAY  $amountdata      Amount data
	 * @param   ARRAY  $allow_taxation  Param
	 *
	 * @return  ARRAY  vars
	 *
	 * @since  1.0.0
	 */
	public function recalculatetotalamount($amountdata, $allow_taxation = 0)
	{
		$com_params = ComponentHelper::getParams('com_tjlms');

		$originalamt = 0;

		// Calculate original Amt to pay Based on Subs plan Price.
		$originalamt = $amountdata['original_amount'];

		if (!empty($amountdata['coupon_code']))
		{
			// Apply coupon if applied. Vars returns us original_amt, amt as amount after discount, coupon_discount;
			$vars = $this->applycoupon($originalamt, $amountdata['coupon_code'], $amountdata['course_id'], $amountdata['plan_id']);
		}
		else
		{
			$vars['original_amt']    = $originalamt;
			$vars['amt']             = $originalamt;
			$vars['coupon_code']     = $amountdata['coupon_code'];
			$vars['coupon_discount'] = 0;
		}

		// If taxation is applied
		if ($allow_taxation)
		{
			$tax_amt = $this->applytax($vars);

			if (isset($tax_amt->taxvalue) and $tax_amt->taxvalue > 0)
			{
				$vars['order_tax']         = $tax_amt->taxvalue;
				$vars['amt']               = $vars['net_amt_after_tax'] = $vars['amt'] + $tax_amt->taxvalue;
				$vars['order_tax_details'] = json_encode($tax_amt);
			}
		}
		// @TODO Sagar to do check for 0 value condition
		return $vars;
	}

	/*
	public function registerUser($regdata1)
	{
	$regdata['fnam'] = $regdata1['fnam'];
	$regdata['user_name'] =$regdata1['email1'];
	$regdata['user_email']=$regdata1['email1'];

	require_once(JPATH_SITE.DS."components".DS."com_tjlms".DS."models".DS."registration.php");
	$tjlmsModelregistration=new tjlmsModelregistration();

	if(!$tjlmsModelregistration->store($regdata))
	return false;
	$user =Factory::getUser();
	return $userid=$user->id;

	}
	*/
	/**
	 * Function used to apply tax
	 *
	 * @param   ARRAY  $vars  tax vars
	 *
	 * @return  ARRAY
	 *
	 * @since  1.0.0
	 */
	public function applytax($vars)
	{
		// @TODO:need to check plugim type..
		PluginHelper::importPlugin('lmstax');

		// Call the plugin and get the result
		$taxresults = Factory::getApplication()->triggerEvent('onAddTax', array(
															$vars['amt']
														)
											);

		if (isset($taxresults[0]) and $taxresults['0']->taxvalue > 0)
		{
			return $taxresults['0'];
		}
		else
		{
			return 0;
		}
	}

	/**
	 * function
	 *
	 * @param   INT  $length  dont know
	 *
	 * @return  STRING
	 *
	 * @since  1.0.0
	 */
	public function _random($length = 5)
	{
		$salt   = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
		$len    = strlen($salt);
		$random = '';

		$stat = @stat(__FILE__);

		if (empty($stat) || !is_array($stat))
		{
			$stat = array(
				php_uname()
			);
		}

		mt_srand(crc32(microtime() . implode('|', $stat)));

		for ($i = 0; $i < $length; $i++)
		{
			$random .= $salt[mt_rand(0, $len - 1)];
		}

		return $random;
	}

	/**
	 * Update order items table
	 *
	 * @param   ARRAY  $data      Order item data
	 * @param   INT    $order_id  Order ID
	 *
	 * @return  boolean  true or false
	 *
	 * @since  1.0.0
	 */
	public function updateOrderItems($data, $order_id)
	{
		$session = Factory::getSession();
		$db      = Factory::getDBO();

		// Get old plan Id
		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjlms/tables');
		$db = Factory::getDbo();
		$table = Table::getInstance('Orderitems', 'TjlmsTable', array('dbo', $db));
		$table->load(array('order_id' => $order_id));

		if (isset($table->plan_id) && $table->plan_id)
		{
			if ($table->plan_id != $data['plan_id'])
			{
				$session->set('sendUpdateMail', 1);
			}
		}
		else
		{
			$session->set('sendUpdateMail', 1);
		}

		$lms_selected_plan = $data['plan_id'];
		$lms_course_id     = $data['course_id'];

		$table->order_id  = $order_id;
		$table->course_id = $lms_course_id;
		$table->plan_id   = $lms_selected_plan;
		$table->amount    = $data['original_amt'];
		$table->store();

		return true;
	}

	/**
	 * Clear session
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	public function clearSession()
	{
		$session = Factory::getSession();
		$session->set('subplanid', '');
		$session->set('lms_orderid', '');
		$session->set('order_id', '');
	}

	/**
	 * Function
	 *
	 * @return  STRING  html
	 *
	 * @since  1.0.0
	 */
	public function buildLayout()
	{
		// Load the layout & push variables
		ob_start();
		$layout = $this->buildLayoutPath($layout);
		include $layout;
		$html = ob_get_contents();
		ob_end_clean();

		return $html;
	}

	/**
	 * Function used to get coupon if avaiable
	 *
	 * @param   STRING  $c_code            Coupon code
	 *
	 * @param   STRING  $course_id         Course id
	 *
	 * @param   STRING  $subscriptionPlan  Subscription plan
	 *
	 * @return  object    Coupon details
	 *
	 * @since  1.0.0
	 */
	public function getcoupon($c_code, $course_id, $subscriptionPlan)
	{
		$user  = Factory::getUser();
		$db    = Factory::getDBO();

		$result = new stdclass;
		$result->status = 'invalid';

		if ($course_id)
		{
			$tjlmsCoursesHelper = new tjlmsCoursesHelper;
			$course_creator = $tjlmsCoursesHelper->getCourseColumn($course_id, 'created_by');
		}

		$query = $db->getQuery(true);
		$query->select("course_id,value,val_type,from_date,exp_date, privacy, created_by, subscription_id");
		$query->from(" #__tjlms_coupons");
		$query->where("code = " . $db->quote($c_code));

		$db->setQuery($query);
		$coupon_obj = $db->loadObject();

		$correctSubscription = '';

		if (!empty($coupon_obj->subscription_id))
		{
			$couponSubscriptionIds = explode(",", $coupon_obj->subscription_id);

			if (in_array($subscriptionPlan, $couponSubscriptionIds))
			{
				$correctSubscription = 'ok';
			}
			else
			{
				$correctSubscription = 'none';
			}
		}

		if (empty($coupon_obj))
		{
			$result->status = 'none';
		}
		elseif (Factory::getDate()->toSql() < $coupon_obj->from_date)
		{
			$result->status = 'none';
		}
		elseif ($coupon_obj->exp_date && $coupon_obj->exp_date != '0000-00-00 00:00:00' &&
		strtotime($coupon_obj->exp_date) <= strtotime(Factory::getDate('now', 'UTC')))
		{
			$result->status = 'expired';
		}
		elseif (!empty($correctSubscription) && $correctSubscription == 'none')
		{
			$result->status = 'none';
		}
		else
		{
			if ($coupon_obj->course_id)
			{
				$coupon_course_id = explode(",", $coupon_obj->course_id);

				if (in_array($course_id, $coupon_course_id))
				{
					$result->status = 'ok';
				}
			}
			elseif ($coupon_obj->privacy == 1)
			{
				$result->status = 'ok';
			}
			elseif ($course_id && $coupon_obj->created_by == $course_creator->created_by)
			{
					$result->status = 'ok';
			}
			elseif (!empty($correctSubscription) && $correctSubscription == 'ok')
			{
				$result->status = 'ok';
			}

			if ($result->status == 'ok')
			{
				$result->status = 'exceed';

				$subquery1 = $db->getQuery(true);
				$subquery1->select('COUNT( api1.coupon_code )');
				$subquery1->from("#__tjlms_orders AS api1");
				$subquery1->where("api1.coupon_code = " . $db->quote($db->escape($c_code)));
				$subquery1->where("(api1.status = 'C' OR (api1.status = 'P' AND ( (api1.processor = 'bycheck') OR (api1.processor = 'byorder')) ))");

				$subquery2 = $db->getQuery(true);
				$subquery2->select('COUNT( api.coupon_code )');
				$subquery2->from("#__tjlms_orders AS api");
				$subquery2->where("api.coupon_code = " . $db->quote($db->escape($c_code)));
				$subquery2->where("api.user_id =" . $user->id);

				$query = $db->getQuery(true);
				$query->select("value, val_type");
				$query->from(" #__tjlms_coupons");
				$query->where("(from_date <= " . $db->quote(Factory::getDate('now', 'UTC', true)) . " OR from_date = '0000-00-00 00:00:00')");
				$query->where("(exp_date >= " . $db->quote(Factory::getDate('now', 'UTC', true)) . "   OR exp_date = '0000-00-00 00:00:00')");
				$query->where("(max_use > (" . $subquery1 . ") OR max_use =0) AND (max_per_user > (" . $subquery2 . ") OR max_per_user =0)");
				$query->where("state =1 AND code=" . $db->quote($db->escape($c_code)));

				$db->setQuery($query);
				$count = $db->loadObjectList();

				if (!empty ($count))
				{
					$result->data  = $count;
					$result->status = 'ok';
				}
			}
		}

		return $result;
	}

	/**
	 * Function used to get Tax if avaiable
	 *
	 * @param   INT  $dis_totalamt  Total amount
	 *
	 * @return  INT  tax value
	 *
	 * @since  1.0.0
	 */
	public function afterTaxPrice($dis_totalamt)
	{
		// @TODO:need to check plugim type..
		PluginHelper::importPlugin('tjlmstax');
		$taxresults = Factory::getApplication()->triggerEvent('onAddTax', array(
															$dis_totalamt
														)
											);

		return $taxresults;
	}

	/**
	 * Function used to get price after discount
	 *
	 * @param   INT     $totalamt  Amount
	 * @param   STRING  $c_code    Coupon code
	 *
	 * @return  INT  Amount for the plan
	 *
	 * @since  1.0.0
	 */
	public function afterDiscountPrice($totalamt, $c_code)
	{
		$coupon       = $this->getcoupon($c_code);
		$coupon       = $coupon ? $coupon : array();
		$dis_totalamt = $totalamt;

		// If user entered code is matched with dDb coupon code
		if (isset($coupon) && $coupon)
		{
			if ($coupon[0]->val_type == 1)
			{
				$cval = ($coupon[0]->value / 100) * $totalamt;
			}
			else
			{
				$cval = $coupon[0]->value;
			}

			$camt = $totalamt - $cval;

			if ($camt <= 0)
			{
				$camt = 0;
			}

			$dis_totalamt = ($camt) ? $camt : $totalamt;
		}

		return $dis_totalamt;
	}

	/**
	 * Get subscription plan for the course.
	 *
	 * @return  mixed  Subscription Info
	 *
	 * @since  1.0.0
	 */
	public function getSubsplan()
	{
		$input     = Factory::getApplication()->input;
		$course_id = $input->get('course_id', '', 'INT');

		$userId             = Factory::getUser()->id;
		$allowedViewLevels  = Access::getAuthorisedViewLevels($userId);
		$implodedViewLevels = implode('","', $allowedViewLevels);

		$db    = Factory::getDBO();
		$query = $db->getQuery(true);
		$query->select('*');
		$query->from($db->quoteName('#__tjlms_subscription_plans'));
		$query->where($db->quoteName('course_id') . " = " . $db->quote((int) $course_id));
		$query->where('access IN ("' . $implodedViewLevels . '")');

		$db->setQuery($query);

		return $db->loadobjectlist('id');
	}

	/**
	 * Get subscription plan for the course.
	 *
	 * @param   INT  $course_id  Course ID
	 *
	 * @return  ARRAY  Course Info
	 *
	 * @since  1.0.0
	 */
	public function getcourseinfo($course_id)
	{
		$tjlmsCoursesHelper = new tjlmsCoursesHelper;
		$course_info        = $tjlmsCoursesHelper->getcourseInfo($course_id);

		if (!empty($course_info))
		{
			$access_l = $course_info->access;

			$user_access = Factory::getUser()->getAuthorisedViewLevels();

			$course_info->authorized = 0;

			if (in_array($access_l, $user_access))
			{
				$course_info->authorized = 1;
			}

			$course_info->creator_name = new stdClass;
			$userDetail = Factory::getUser($course_info->created_by);
			$course_info->creator_name->name = $userDetail->name;
			$course_info->creator_name->username = $userDetail->username;
		}

		return $course_info;
	}

	/**
	 * Check if article exists
	 *
	 * @param   INT  $articleId  Article ID
	 *
	 * @return  INT  Article ID
	 *
	 * @since  1.0.0
	 */
	public function doesArticleExists($articleId)
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select('art.id');
		$query->from($db->quoteName('#__content', 'art'));
		$query->join('INNER', $db->quoteName('#__categories', 'cat') . 'ON(' . $db->quoteName('art.catid') . '=' . $db->quoteName('cat.id') . ') ');
		$query->where($db->quoteName('art.state') . '= 1');
		$query->where($db->quoteName('cat.published') . '= 1');
		$query->where($db->quoteName('art.id') . '=' . (int) $articleId);
		$db->setQuery($query);

		if (!$db->loadResult())
		{
			return 0;
		}

		return 1;
	}

	/**
	 * Function used to check if the user has a suncription
	 *
	 * @param   INT  $userId    User ID
	 * @param   INT  $courseId  Course ID
	 *
	 * @return  boolean
	 *
	 * @since  1.0.0
	 */
	public function checkIfUserHasSubscription($userId, $courseId)
	{
		// Get a db connection.
		$db = Factory::getDbo();

		// Create a new query object.
		$query = $db->getQuery(true);

		// Select all records from the user profile table where key begins with "custom.".
		// Order it by the ordering field.
		$query->select($db->quoteName(array('id', 'end_time', 'state')));
		$query->from($db->quoteName('#__tjlms_enrolled_users'));
		$query->where($db->quoteName('user_id') . '=' . $userId);
		$query->where($db->quoteName('course_id') . '=' . $courseId);

		// Reset the query using our newly populated query object.
		$db->setQuery($query);

		// Load the results as a list of stdClass objects (see later for more options on retrieving data).
		$result = $db->loadobject();

		$currentDate = Factory::getDate();

		if (!empty($result))
		{
			// When enrollment is 1 and subcription is active  || enrolment status is -1 i.e order is pending. Cases when to redirect to course page.
			if (($result->state == 1 && $result->end_time >= $currentDate) || $result->state == 0)
			{
				return 1;
			}
		}
		else
		{
			$query = $db->getQuery(true);
			$query->select($db->quoteName(array('o.status', 'o.processor')));
			$query->from($db->quoteName('#__tjlms_orders') . 'as o');
			$query->join('INNER', $db->quoteName('#__tjlms_order_items') . 'as oi ON oi.order_id=o.id');
			$query->where('o.user_id=' . $userId);
			$query->where('o.course_id=' . $courseId);
			$query->order('o.id DESC');

			// Reset the query using our newly populated query object.
			$db->setQuery($query);

			// Load the results as a list of stdClass objects (see later for more options on retrieving data).
			$result = $db->loadobject();

			if (!$result)
			{
				return -1;
			}
			elseif ($result->status == 'C' || ($result->status == 'P' && $result->processor != ''))
			{
				return 1;
			}
		}

		return -1;
	}

	/**
	 * Method create an order with selected subscription plan
	 *
	 * @param   ARRAY  $orderdata  Order details
	 *
	 * @return  ARRAY  $data
	 *
	 * @since  1.1.4
	 */
	public function createSubscriptionOrder($orderdata)
	{
		$session    = Factory::getSession();
		$com_params = ComponentHelper::getParams('com_tjlms');
		$user  = Factory::getUser($orderdata['user_id']);
		$data = array();

		if ($orderdata)
		{
			$orderdata['name']    = $user->name;
			$orderdata['email']   = $user->email;

			// PlanDate is used to store order item.
			$planData['course_id']       = $orderdata['course_id'];
			$planData['plan_id']         = $orderdata['plan_id'];
			$planData['original_amount'] = $this->getoriginalAmt($orderdata['plan_id'], $orderdata['course_id']);

			if ($planData['original_amount'] === null)
			{
				$data['failure']  = 1;
				$data['message']  = Text::_("COM_TJLMS_ORDER_INVALID_COURSE_ID_OR_PLAN");

				return $data;
			}

			$planData['coupon_code']     = $orderdata['coupon_code'];

			$allow_taxation = $com_params->get('allow_taxation');

			$amountData = $this->recalculatetotalamount($planData, $allow_taxation);
			$planData['original_amt']  = $amountData['original_amt'];
			$orderdata['original_amt'] = $amountData['original_amt'];

			if ($amountData['amt'] < 0)
			{
				$amountData['amt'] = 0;
				$amountData['fee'] = 0;
			}

			$orderdata['amount']          = $amountData['amt'];
			$orderdata['coupon_discount'] = $amountData['coupon_discount'];

			if (isset($amountData['order_tax']))
			{
				$orderdata['order_tax'] = $amountData['order_tax'];
			}
			else
			{
				$orderdata['order_tax'] = 0;
			}

			if (isset($amountData['order_tax']))
			{
				$orderdata['order_tax_details'] = $amountData['order_tax_details'];
			}
			else
			{
				$orderdata['order_tax_details'] = 0;
			}

			$orderdata['coupon_discount_details'] = $orderdata['coupon_code'];
			$orderdata['coupon_code']             = $orderdata['coupon_code'];
		}

		$lms_orderid = $session->get('lms_orderid');
		require_once JPATH_SITE . '/components/com_tjlms/helpers/main.php';
		$comtjlmsHelper = new comtjlmsHelper;

		if (isset($lms_orderid))
		{
			// @To do.
			$orderinfo = $comtjlmsHelper->getorderinfo($lms_orderid, 'step_select_subsplan');

			// Check if orderid is of this plan only
			if (!empty($orderinfo['order_info'])
				&& $orderinfo['order_info']['0']->course_id == $orderdata['course_id']
				&& $orderinfo['order_info']['0']->status == 'I')
			{
				// Check if order is of this plan and is pending
				$orderdata['order_id'] = $lms_orderid;
			}
		}

		// Create Main order
		$order_id = $this->createMainOrder($orderdata);

		if ($orderdata['return'] == 1)
		{
			// Create Order Items for this order
			$this->updateOrderItems($orderdata['plan_info'], $order_id);
		}
		else
		{
			$this->updateOrderItems($orderdata, $order_id);
		}

		$orderModel = BaseDatabaseModel::getInstance('Orders', 'TjlmsModel');
		$ecTrackingData   = $orderModel->getEcTrackingData($order_id);
		$ecTrackingData->step_number = 2;
		$dimension = $com_params->get('ga_product_type_dimension');

		$ecTrackingData->productTypeDimensionValue = $dimension ? $dimension : '';

		$ecTrackingDataArray = array();
		$ecTrackingDataArray[] = $ecTrackingData;

		if ($order_id)
		{
			$session->set('lms_orderid', $order_id);
			$data['success']  = 1;
			$data['order_id'] = $order_id;
			$data['message']  = "Order Created Successfully";
			$data['ecTrackingData'] = $ecTrackingDataArray;
		}
		else
		{
			$data['failure']  = 1;
			$data['message']  = "Something went wrong, order is not created";
		}

		return $data;
	}

	/**
	 * Function save user billing info.
	 *
	 * @param   ARRAY  $orderdata  Order details
	 *
	 * @return  json  $data
	 *
	 * @since  1.1.4
	 */
	public function saveBillingInfo($orderdata)
	{
		$user                    = Factory::getUser($orderdata['user_id']);
		$session                 = Factory::getSession();
		$order_id                = $session->get('lms_orderid');
		$com_params              = ComponentHelper::getParams('com_tjlms');
		$billing_data            = $orderdata['bill'];
		$billing_data['comment'] = $orderdata['comment'];

		if (!$user->id)
		{
			return false;
		}
		else
		{
			if ($order_id)
			{
				$orderInfo = array();

				$orderInfo['user_id'] = $user->id;

				if (isset($orderdata['accpt_terms']) && $orderdata['accpt_terms'] == 'on')
				{
					$orderInfo['accpt_terms'] = 1;
				}

				// Update the order details.
				$this->updateOrderDetails($order_id, $orderInfo);

				/* On After order update trigger */
				PluginHelper::importPlugin('system', 'discount', true, null);
				$discountedprice = Factory::getApplication()->triggerEvent('onAfterOrderUpdate', array($order_id, $billing_data['country']));
				/* END*/
			}
		}

		// If order id present
		if ($order_id)
		{
			require_once JPATH_SITE . '/components/com_tjlms/helpers/main.php';
			$comtjlmsHelper = new comtjlmsHelper;

			$this->billingaddr($user->id, $billing_data, $order_id);
			$order = $comtjlmsHelper->getorderinfo($order_id);

			// If free plan(In case of coupon applied) then confirm automatically and redirect to Invoice View.
			if ($order['order_info']['0']->amount == 0)
			{
				$confirm_order                   = array();
				$confirm_order['buyer_email']    = '';
				$confirm_order['status']         = 'C';
				$confirm_order['processor']      = "Free_plan";
				$confirm_order['transaction_id'] = "";
				$confirm_order['raw_data']       = "";
				$confirm_order['order_id']       = $order_id;

				$paymenthelper = JPATH_ROOT . '/components/com_tjlms/models/payment.php';

				if (!class_exists('tjlmsModelpayment'))
				{
					JLoader::register('tjlmsModelpayment', $paymenthelper);
					JLoader::load('tjlmsModelpayment');
				}

				$tjlmsModelpayment = new tjlmsModelpayment;
				$tjlmsModelpayment->updateStatus($confirm_order, $order_id);

				// Add Table Path
				Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjlms/tables');

				$order_table = Table::getInstance('Orders', 'TjlmsTable', array('dbo', $this->_db));
				$order_table->load(array('id' => $order_id));

				// After order table status update, count the used_count Check if coupon code is used
				if ($order_table->coupon_code)
				{
					// Load jlike reminders model to call api to send the reminders
					require_once JPATH_ADMINISTRATOR . '/components/com_tjlms/models/coupons.php';

					// Call the actual cron code which will send the reminders
					$model         = BaseDatabaseModel::getInstance('Coupons', 'TjlmsModel');
					$model->updateCouponUsedcount($order_table->coupon_code);
				}

				$order_id_with_prefix = $order['order_info']['0']->orderid_with_prefix;
				$orderUrl = 'index.php?option=com_tjlms&view=orders&orderid=';
				$data['redirect_invoice_view'] = $comtjlmsHelper->tjlmsRoute($orderUrl . $order_id_with_prefix . '&processor=Free_plan', false);
			}
			else
			{
				$billpath = $comtjlmsHelper->getViewpath('com_tjlms', 'buy', 'default_payment');
				ob_start();
				include $billpath;
				$html = ob_get_contents();
				ob_end_clean();
				$data['payment_html'] = $html;
			}
		}

		$selected_gateways = $com_params->get('gateways');

		$orderModel = BaseDatabaseModel::getInstance('Orders', 'TjlmsModel');
		$ecTrackingData   = $orderModel->getEcTrackingData($order_id);
		$ecTrackingData->step_number = 3;
		$dimension = $com_params->get('ga_product_type_dimension');

		if ($com_params->get('track_attendee_step') == 1)
		{
			$ecTrackingData->step_number = 4;
		}

		$ecTrackingData->productTypeDimensionValue = $dimension ? $dimension : '';

		$ecTrackingDataArray = array();
		$ecTrackingDataArray[] = $ecTrackingData;

		if (count($selected_gateways) == 1)
		{
			$data['single_gateway'] = $selected_gateways[0];
		}

		if ($order_id)
		{
			$data['success']  = 1;
			$data['order_id'] = $order_id;
			$data['message']  = "Billing Data saved succefully";
			$data['ecTrackingData'] = $ecTrackingDataArray;
		}
		else
		{
			$data['failure']  = 1;
			$data['message']  = "Something went wrong, billing Data is not getting saved";
		}

		@ob_end_clean();

		return $data;
	}

	/**
	 * Function save user privacy info.
	 *
	 * @param   ARRAY  $userPrivacyData  user Privacy Data
	 *
	 * @return  mixed
	 *
	 * @since  1.2.10
	 */
	public function savePrivacyData($userPrivacyData)
	{
		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjprivacy/tables');
		$userPrivacyTable = Table::getInstance('tj_consent', 'TjprivacyTable', array());

		if ($userPrivacyTable)
		{
			try
			{
				$query = $this->_db->getQuery(true);
				$query->select('*');
				$query->from($this->_db->quoteName('#__tj_consent', 'c'));
				$query->where($this->_db->quoteName('c.client_id') . ' = ' . $this->_db->q($userPrivacyData['client_id']));
				$query->where($this->_db->quoteName('c.client') . ' = "com_tjlms.buy"');
				$query->where($this->_db->quoteName('c.accepted') . ' = 1');
				$query->where($this->_db->quoteName('c.user_id') . ' = ' . $this->_db->q($userPrivacyData['user_id']));

				// Reset the query using our newly populated query object.
				$this->_db->setQuery($query);

				// Load the results as a list of stdClass objects (see later for more options on retrieving data).
				$result = $this->_db->loadobject();

				if (empty($result))
				{
					BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_tjprivacy/models', 'tjprivacy');
					$tjprivacyModelObj = BaseDatabaseModel::getInstance('tjprivacy', 'TjprivacyModel');
					$tjprivacyModelObj->save($userPrivacyData);
				}
			}
			catch (exception $e)
			{
				Factory::getApplication()->enqueueMessage(Text::_('COM_TJLMS_ERROR_MSG'), 'error');

				return false;
			}
		}
	}
}
