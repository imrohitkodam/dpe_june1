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
use Joomla\CMS\HTML\HTMLHelper;

use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 * Methods supporting buy process
 *
 * @since  1.0.0
 */
class TjlmsModelpayment extends BaseDatabaseModel
{
	/**
	 * Constructor
	 *
	 * @since   1.0
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

		$this->TjlmsCoursesHelper = new TjlmsCoursesHelper;

		$this->TjGeoHelper = new TjGeoHelper;
		$this->_db         = Factory::getDBO();
	}

	/**
	 * Function
	 *
	 * @param   STRING  $pg_plugin  Payment plugin
	 * @param   STRING  $oid        Order ID
	 *
	 * @return  redirects
	 *
	 * @since  1.0.0
	 */
	public function confirmpayment($pg_plugin, $oid)
	{
		$app   = Factory::getApplication();
		
		$post = $app->input->get->post;
		$vars = $this->getPaymentVars($pg_plugin, $oid);
		$postArray = $post->getArray();
		$postArray['order_id'] = $vars->order_id;

		if (!empty($post) && !empty($vars))
		{
			if (!empty($result))
			{
				$vars = $result[0];
			}

			PluginHelper::importPlugin('payment', $pg_plugin);
			$result = Factory::getApplication()->triggerEvent('onTP_ProcessSubmit', array($postArray, $vars));
		}
		else
		{
			Factory::getApplication()->enqueueMessage(Text::_('SOME_ERROR_OCCURRED'), 'error');
		}
	}

	/**
	 * Function
	 *
	 * @param   STRING  $pg_plugin  Payment plugin
	 * @param   STRING  $orderid    Order ID
	 *
	 * @return  obj  $vars
	 *
	 * @since  1.0.0
	 */
	public function getPaymentVars($pg_plugin, $orderid)
	{
		$comtjlmsHelper = new comtjlmsHelper;
		$params         = ComponentHelper::getParams('com_tjlms');
		$orderItemid    = $comtjlmsHelper->getitemid('index.php?option=com_tjlms&view=orders&layout=my');
		$chkoutItemid   = $comtjlmsHelper->getitemid('index.php?option=com_tjlms&view=courses');

		if (!$orderItemid || $orderItemid == 0)
		{
			$orderItemid = $chkoutItemid;
		}

		$pass_data = $this->getdetails($orderid);

		$vars                 = new stdClass;

		// Append prefix and order_id
		$vars->order_id       = $pass_data->order_id;
		$vars->user_id        = $pass_data->user_id;
		$vars->user_firstname = $pass_data->firstname;
		$vars->user_email     = $pass_data->user_email;
		$guest_email          = '';

		if (!$pass_data->user_id && $params->get('guest'))
		{
			$guest_email = "&email=" . md5($pass_data->user_email);
		}

		$vars->phone     = $pass_data->phone;
		$vars->item_name = $pass_data->order_item_name;
		$vars->submiturl = "index.php?option=com_tjlms&task=payment.confirmpayment&processor={$pg_plugin}";
		$orderUrl        = 'index.php?option=com_tjlms&view=orders';
		$ecTrackId       = base64_encode($pass_data->order_id);
		
		if ($pg_plugin == 'stripe')
		{
			$vars->return    = Uri::root() . $orderUrl . $guest_email . "&orderid=" . $pass_data->order_id . "&Itemid=" . $orderItemid . "&ecTrackId=" . $ecTrackId;
		}
		else
		{
			$vars->return    = Uri::root() . substr(
				Route::_($orderUrl . $guest_email . "&orderid=" . $pass_data->order_id . "&Itemid=" . $orderItemid . "&ecTrackId=" . $ecTrackId),
				strlen(Uri::base(true)) + 1
				);
		}
		
		$vars->cancel_return = $vars->return;

		$processUrl = "index.php?option=com_tjlms&task=payment.processpayment&order_id=" . $pass_data->order_id . $guest_email . "&processor=" . $pg_plugin;

		$vars->notify_url = Uri::root() . "index.php?option=com_tjlms&task=payment.notify&order_id="
				. $pass_data->order_id . $guest_email . "&processor=" . $pg_plugin;
				$vars->url = Uri::root() . $processUrl;
				$vars->currency_code = $pass_data->currency;
				$vars->comment       = $pass_data->customer_note;
				$vars->amount        = $pass_data->order_amt;

				// Get User details
				$vars->userInfo = $this->userInfo($orderid);

				return $vars;
	}

	/**
	 * Get billing details
	 *
	 * @param   integer  $orderid  id of jticketing_order table
	 *
	 * @return  array  $billDetails  billing details
	 *
	 * @since   1.0
	 */
	public function userInfo($orderid)
	{
		$user = Factory::getUser();
		$db   = Factory::getDBO();

		// Create a new query object.
		$query = $db->getQuery(true);

		$query->select($db->qn(array('user_id','user_email','firstname','lastname','country_code','state_code','address','city','phone','zipcode')));
		$query->from($db->qn('#__tjlms_users'));
		$query->where($db->qn('order_id') . ' = ' . $db->q((int) $orderid));
		$query->order($db->qn('id') . ' DESC');

		$db->setQuery($query);
		$billDetails = $db->loadAssoc();

		// Make address in 2 lines
		if (isset($billDetails['country_code']))
		{
			$billDetails['country_code'] = $this->TjGeoHelper->getCountryNameFromId($billDetails['country_code']);
		}

		if (isset($billDetails['state_code']))
		{
			$billDetails['state_code'] = $this->TjGeoHelper->getRegionNameFromId($billDetails['state_code']);
		}

		$billDetails['add_line2'] = '';
		$remove_character         = array(
				"\n",
				"\r\n",
				"\r"
		);

		$billDetails['add_line1'] = str_replace($remove_character, ' ', $billDetails['address']);

		return $billDetails;
	}

	/**
	 * Function used to get payment plugin html
	 *
	 * @param   INT  $order_id  Order ID
	 *
	 * @return  STRING  $html  Plugin HTML
	 *
	 * @since  1.0.0
	 */
	public function getHTML($order_id)
	{
		$pass_data = $this->getdetails($order_id);
		$vars      = $this->getPaymentVars($pass_data->processor, $order_id);

		PluginHelper::importPlugin('payment', $pass_data->processor);
		$html = Factory::getApplication()->triggerEvent('onTP_GetHTML', array(
				$vars
		)
				);

		return $html;
	}

	/**
	 * Function used to get details of order
	 *
	 * @param   INT  $tid  Order ID
	 *
	 * @return  ARRAY  $orderdetails  Order details
	 *
	 * @since  1.0.0
	 */
	public function getdetails($tid)
	{
		$params = ComponentHelper::getParams('com_tjlms');

		$query = $this->_db->getQuery(true);
		$query->select('firstname,user_email,phone,user_id');
		$query->from($this->_db->quoteName('#__tjlms_users'));
		$query->where('order_id = ' . $this->_db->quote($tid));
		$query->where('address_type = ' . $this->_db->quote('BT'));
		$this->_db->setQuery($query);
		$orderdetail = $this->_db->loadObject();

		$query = $this->_db->getQuery(true);
		$query->select('amount,customer_note,processor,order_id');
		$query->from($this->_db->quoteName('#__tjlms_orders'));
		$query->where('id = ' . $this->_db->quote($tid));
		$this->_db->setQuery($query);
		$orderamt = $this->_db->loadObject();
		$orderdetail->order_id = $orderamt->order_id;

		$query = $this->_db->getQuery(true);
		$query->select("CONCAT(s.duration,' ',s.time_measure)");
		$query->from($this->_db->quoteName('#__tjlms_order_items', 'i'));
		$query->join('INNER', '#__tjlms_subscription_plans as s ON s.id=i.plan_id');
		$query->where('i.order_id = ' . $this->_db->quote($tid));
		$query->group('i.plan_id');
		$this->_db->setQuery($query);
		$orderdetail->order_item_name = $this->_db->loadResult();

		$orderdetail->processor       = $orderamt->processor;
		$orderdetail->order_amt       = $orderamt->amount;
		$orderdetail->currency        = $params->get('currency');
		$orderdetail->customer_note   = preg_replace('/\<br(\s*)?\/?\>/i', " ", $orderamt->customer_note);

		return $orderdetail;
	}

	/**
	 * Function used to process payment
	 *
	 * @param   ARRAY   $post       Post Data
	 * @param   STRING  $pg_plugin  Payment plugin used
	 * @param   INT     $order_id   Order data
	 *
	 * @return  INT  status
	 *
	 * @since  1.0.0
	 */
	public function processpayment($post, $pg_plugin, $order_id)
	{
		$comtjlmsHelper = new ComtjlmsHelper;

		// Get order_id in format 12
		$id = $comtjlmsHelper->getIDFromOrderID($order_id);

		if (empty($id))
		{
			$return_resp['msg'] = Text::_('COM_TJLMS_ORDER_ERROR');

			return $return_resp;
		}

		$orderItemid  = $comtjlmsHelper->getitemid('index.php?option=com_tjlms&view=orders&layout=my');
		$chkoutItemid = $comtjlmsHelper->getitemid('index.php?option=com_tjlms&view=buy');

		if (!$orderItemid || $orderItemid == 0)
		{
			$orderItemid = $chkoutItemid;
		}

		$return_resp  = array();
		PluginHelper::importPlugin('payment', $pg_plugin);

		$vars = $this->getPaymentVars($pg_plugin, $id);

		$data = Factory::getApplication()->triggerEvent('onTP_Processpayment', array($post, $vars));
		$data = $data[0];

		// Add logs.
		$this->storelog($pg_plugin, $data);

		if ($data)
		{
			// Get order id
			if (empty($order_id))
			{
				// Here we get order_id in Format JT_JKJKJK_0012
				$order_id = $data['order_id'];

				// Get order_id in format 12
				$id       = $comtjlmsHelper->getIDFromOrderID($order_id);
			}

			$query = $this->_db->getQuery(true);
			$query->select("user_id,user_email");
			$query->from($this->_db->quoteName('#__tjlms_users'));
			$query->where('address_type = ' . $this->_db->quote('BT'));
			$query->where('order_id = ' . $this->_db->quote($id));
			$this->_db->setQuery($query);
			$user_detail = $this->_db->loadObject();

			/*
			 if(!$user_detail->user_id && $params->get( 'guest' ))
			 {
			 $guest_email = "&email=".md5($user_detail->user_email);
			 }
			 /*end for guest checkout*/

			$data['processor'] = $pg_plugin;
			$data['status']    = trim($data['status']);

			$query = $this->_db->getQuery(true);
			$query->select("amount");
			$query->from($this->_db->quoteName('#__tjlms_orders'));
			$query->where('id = ' . $this->_db->quote($id));
			$this->_db->setQuery($query);
			$order_amount          = $this->_db->loadResult();

			$return_resp['status'] = '0';

			if ($order_amount == 0)
			{
				$data['order_id']       = $id;
				$data['total_paid_amt'] = 0;
				$data['processor']      = $pg_plugin;
				$data['status']         = 'C';
			}

			if (($data['status'] == 'C' && $order_amount == $data['total_paid_amt']) or ($data['status'] == 'C' && $order_amount == 0))
			{
				$data['status']        = 'C';
				$return_resp['status'] = '1';
			}
			elseif ($order_amount != $data['total_paid_amt'])
			{
				$data['status']        = 'E';
				$return_resp['status'] = '0';
			}
			elseif (empty($data['status']))
			{
				$data['status']        = 'P';
				$return_resp['status'] = '0';
			}

			if ($data['status'] != 'C' && !empty($data['error']))
			{
				$return_resp['msg'] = $data['error']['code'] . " " . $data['error']['desc'];
			}

			$this->updateOrder($id, $user_detail->user_id, $data, $return_resp);

			if ($post['return'])
			{
				$return_resp['return'] = $post['return'];
			}
			else
			{
				$orderUrl = 'index.php?option=com_tjlms&view=orders&orderid=' . ($order_id)
				. '&processor=' . $pg_plugin . '&Itemid=' . $orderItemid;
				$return_resp['return'] = Uri::root() . substr(Route::_($orderUrl, false), strlen(Uri::base(true)) + 1);
			}

			$db    = Factory::getDbo();
			$query = $db->getQuery(true);
			$query->select('*');
			$query->from($db->quoteName('#__tjlms_orders'));
			$query->where($db->quoteName('order_id') . " = " . $db->quote($order_id));
			$db->setQuery($query);
			$orderDetails = $db->loadObject();

			require_once JPATH_SITE . "/components/com_tjlms/helpers/courses.php";
			$TjlmsCoursesHelper = new TjlmsCoursesHelper;

			$lessons = $TjlmsCoursesHelper->getLessonsByCourse($orderDetails->course_id);

			foreach ($lessons as $lesson)
			{
				$plug_type = 'tj' . $lesson->format;
				PluginHelper::importPlugin($plug_type);

				// Trigger all "invitex" plugins method that renders the button/image
				Factory::getApplication()->triggerEvent('onAfterCourseBuy', array(
						$orderDetails,
						$lesson->params,
						$pg_plugin
				)
						);
			}
		}
		else
		{
			$return_resp['msg'] = Text::_('COM_TJLMS_ORDER_ERROR');
		}

		return $return_resp;
	}

	/**
	 * Function used update order details
	 *
	 * @param   INT    $id           Order ID
	 * @param   INT    $userid       User ID
	 * @param   ARRAY  $data         Order  data
	 * @param   INT    $return_resp  Status
	 *
	 * @return  INT  status
	 *
	 * @since  1.0.0
	 */
	public function updateOrder($id, $userid, $data, $return_resp)
	{
		$processed = 0;

		$comtjlmsHelper = new comtjlmsHelper;
		$oldStatus = $comtjlmsHelper->getOrderStatus($id);

		if ($data['status'] == 'C' and $return_resp['status'] == 1)
		{
			$return_resp['status'] = '1';
			$this->updateStatus($data, $id);

			if ($oldStatus != 'C')
			{
				require_once JPATH_SITE . '/components/com_tjlms/helpers/mailcontent.php';
				$tjlmsMailcontentHelper = new TjlmsMailcontentHelper;
				$tjlmsMailcontentHelper->sendInvoiceEmail($id);
			}
		}
		elseif (!empty($data['status']))
		{
			$this->updateStatus($data, $id);
		}

		return $return_resp;
	}

	/**
	 * Function used to store log
	 *
	 * @param   STRING  $name  plugin name
	 * @param   ARRAY   $data  order data
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	public function storelog($name, $data)
	{
		$data1                 = array();
		$data1['raw_data']     = $data['raw_data'];
		$data1['TJLMS_CLIENT'] = "com_tjlms";

		PluginHelper::importPlugin('payment', $name);
		$data = Factory::getApplication()->triggerEvent('onTP_Storelog', array(
				$data1
		)
				);
	}

	/**
	 * Function used to update status of the order
	 *
	 * @param   ARRAY  $data  Order data
	 * @param   INT    $id    Order ID
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	public function updateStatus($data, $id)
	{
		$comtjlmsHelper         = new comtjlmsHelper;
		$db                     = Factory::getDBO();
		$params                 = ComponentHelper::getParams('com_tjlms');
		$approvalForPaidCourses = $params->get('paid_course_admin_approval', '0', 'INT');
		$allowFlexiEnrolments   = $params->get('allow_flexi_enrolments', 0, 'INT');

		/* add enrollment entry*/
		$query = $this->_db->getQuery(true);
		$query->select("oi.course_id,oi.plan_id,o.id,o.user_id,o.enrollment_id");
		$query->from($this->_db->quoteName('#__tjlms_order_items', 'oi'));
		$query->join('INNER', '#__tjlms_orders as o ON o.id=oi.order_id');
		$query->where('o.id = ' . $this->_db->quote($id));
		$this->_db->setQuery($query);
		$enrol_table_details = $db->loadAssoc();
		$cId = $enrol_table_details['course_id'];

		$enrollmentId = $enrol_table_details['enrollment_id'];

		$orginalStatus = $comtjlmsHelper->getOrderStatus($id);

		if ($orginalStatus == 'C' && $data['status'] != 'C')
		{
			$enrollmentHistory = $this->TjlmsCoursesHelper->deleteEnrolmentHistory($id, $enrollmentId);
		}
		elseif ($orginalStatus != 'C' && $data['status'] == 'C')
		{
			if (!$allowFlexiEnrolments)
			{
				$enrollmentId = $this->TjlmsCoursesHelper->addEnrolmentEntry($enrol_table_details['user_id'], $cId, $data['status']);
				$enrollmentHistory = $this->TjlmsCoursesHelper->addEnrolmentHistory($id, $enrollmentId);
			}
		}

		$res                 = new stdClass;
		$res->id             = $id;
		$res->mdate          = $comtjlmsHelper->getDateInUtc(HTMLHelper::date('now', 'Y-m-d H:i:s', true));
		$res->transaction_id = $data['transaction_id'];
		$res->status         = $data['status'];
		$res->extra          = json_encode($data['raw_data']);
		$res->enrollment_id  = $enrollmentId;

		if (isset($data['processor']))
		{
			$res->processor  = $data['processor'];
		}

		if (!$db->updateObject('#__tjlms_orders', $res, 'id'))
		{
			echo $this->_db->stderr();

			return false;
		}

		if ($data['status'] == 'C' && $approvalForPaidCourses == 0 && !$allowFlexiEnrolments)
		{
			$tjlmsCoursesHelper = new tjlmsCoursesHelper;
			$endTime            = $tjlmsCoursesHelper->updateEndTimeForCourse($enrol_table_details['plan_id'], $enrollmentId);
		}

		PluginHelper::importPlugin('system');
		Factory::getApplication()->triggerEvent('onOrderAfterStatusChange', array($data['id'], $data['status']));
	}

	/**
	 * This function update order gateway on change of gateway
	 *
	 * @param   STRING  $selectedGateway  Payment plugin
	 * @param   STRING  $order_id         Order ID
	 *
	 * @return  boolean  true or false
	 *
	 * @since  1.0.0
	 */
	public function updateOrderGateway($selectedGateway, $order_id)
	{
		$db             = Factory::getDBO();
		$row            = new stdClass;
		$row->id        = $order_id;
		$row->processor = $selectedGateway;

		if (!$this->_db->updateObject('#__tjlms_orders', $row, 'id'))
		{
			echo $this->_db->stderr();

			return 0;
		}

		return 1;
	}
}
