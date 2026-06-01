<?php
/**
 * @package     TJLms
 * @subpackage  com_shika
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Uri\Uri;

jimport('joomla.application.component.view');
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Component\ComponentHelper;

/**
 * view of courses
 *
 * @since  1.0
 */
class TjlmsVieworders extends HtmlView
{
	protected $input;

	protected $items;

	protected $pagination;

	public $filterForm;

	protected $state;

	/**
	 * Display the view
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  void
	 */
	public function display($tpl = null)
	{
		global $mainframe, $option;

		$mainframe = Factory::getApplication();
		$this->input = $mainframe->input;
		$this->state = $this->get('State');
		$this->items = $this->get('Items');
		$this->pagination = $this->get('Pagination');
		$this->filterForm = $this->get('FilterForm');

		$option = $this->input->get('option');
		$this->user = Factory::getUser();

		$this->comtjlmsHelper = new comtjlmsHelper;
		$this->input->set('view', 'orders');
		$layout = $this->input->get('layout', 'default');

		$this->selectPaymentStatus = array();
		$this->selectPaymentStatus[] = JHTML::_('select.option', '0', Text::_('SEL_PAY_STATUS'));
		$this->selectPaymentStatus[] = JHTML::_('select.option', 'P', Text::_('COM_TJLMS_PSTATUS_PENDING'));
		$this->selectPaymentStatus[] = JHTML::_('select.option', 'C', Text::_('COM_TJLMS_PSTATUS_COMPLETED'));
		$this->selectPaymentStatus[] = JHTML::_('select.option', 'D', Text::_('COM_TJLMS_PSTATUS_DECLINED'));
		$this->selectPaymentStatus[] = JHTML::_('select.option', 'E', Text::_('COM_TJLMS_PSTATUS_FAILED'));
		$this->selectPaymentStatus[] = JHTML::_('select.option', 'UR', Text::_('COM_TJLMS_PSTATUS_UNDERREVIW'));
		$this->selectPaymentStatus[] = JHTML::_('select.option', 'RF', Text::_('COM_TJLMS_PSTATUS_REFUNDED'));
		$this->selectPaymentStatus[] = JHTML::_('select.option', 'CRV', Text::_('COM_TJLMS_PSTATUS_CANCEL_REVERSED'));
		$this->selectPaymentStatus[] = JHTML::_('select.option', 'RV', Text::_('COM_TJLMS_PSTATUS_REVERSED'));

		$this->params = ComponentHelper::getParams('com_tjlms');
		$orderId = $this->input->get('orderid', '', 'STRING');

		if (empty($orderId))
		{
			$orderId = $this->input->get->getData('orderid', '', 'STRING');
		}

		$oid = $this->comtjlmsHelper->getIDFromOrderID($orderId);
		$order = $this->comtjlmsHelper->getorderinfo($oid);
		$this->order_authorized = 0;

		if (!$this->user->id)
		{
			$mainframe->enqueueMessage(Text::_('TJLMS_NOT_AUTHORISED'), 'error');
            $mainframe->setHeader('status', 403, true);

			return false;
		}

		if (!empty($order))
		{
			$this->order_authorized = $this->comtjlmsHelper->getorderAuthorization($order["order_info"][0]->user_id);
			$this->orderinfo = $order['order_info'];
			$this->orderitems = $order['items'];
			$this->setOrderDetails();
		}
		else
		{
				$this->noOrderDetails = 1;
		}

		$this->paymentStatus = array();
		$this->paymentStatus['I'] = Text::_('COM_TJLMS_PSTATUS_INITIATED');
		$this->paymentStatus['P'] = Text::_('COM_TJLMS_PSTATUS_PENDING');
		$this->paymentStatus['C'] = Text::_('COM_TJLMS_PSTATUS_COMPLETED');
		$this->paymentStatus['D'] = Text::_('COM_TJLMS_PSTATUS_DECLINED');
		$this->paymentStatus['E'] = Text::_('COM_TJLMS_PSTATUS_FAILED');
		$this->paymentStatus['UR'] = Text::_('COM_TJLMS_PSTATUS_UNDERREVIW');
		$this->paymentStatus['RF'] = Text::_('COM_TJLMS_PSTATUS_REFUNDED');
		$this->paymentStatus['CRV'] = Text::_('COM_TJLMS_PSTATUS_CANCEL_REVERSED');
		$this->paymentStatus['RV'] = Text::_('COM_TJLMS_PSTATUS_REVERSED');

		$this->myorderslink = $this->comtjlmsHelper->tjlmsRoute('index.php?option=com_tjlms&view=orders&layout=my');

		parent::display($tpl);
	}

	/**
	 * Get order course and invoice details
	 *
	 * @return  void
	 */
	public function setOrderDetails()
	{
		$this->billInfo = $this->orderdetails = '';

		if (isset($this->orderinfo))
		{
			$this->coupon_code = $this->orderinfo[0]->coupon_code;

			if (isset($this->orderinfo[0]->address_type) && $this->orderinfo[0]->address_type == 'BT')
			{
				$this->billinfo = $this->orderinfo[0];
			}
			elseif (isset($this->orderinfo[1]->address_type) && $this->orderinfo[1]->address_type == 'BT')
			{
				$this->billinfo = $this->orderinfo[1];
			}
		}

		if (isset($this->orderinfo))
		{
			$where = " o.id=" . $this->orderinfo['0']->order_id;

			if ($this->orderinfo['0']->order_id)
			{
				$orderdetails = $this->comtjlmsHelper->getallCourseDetailsByOrder($where);
				$this->orderDetails = $orderdetails['0'];
			}

			$this->orderinfo = $this->orderinfo[0];
		}

		// Create data to sending google analytics.
		$ecTrackId = Factory::getApplication()->input->get('ecTrackId', '', 'STRING');
		$googleAnalyticsOrderId = base64_decode($ecTrackId);
		$com_params = ComponentHelper::getParams('com_tjlms');

		// Send order data for google analytics in 'order incomplete state'
		$sendTransData = $com_params->get('send_ga_data');

		if ( $sendTransData == 1 || $this->orderinfo->status === 'C')
		{
			if ($googleAnalyticsOrderId == $this->orderinfo->orderid_with_prefix)
			{
				$orderData = array();
				$orderModel = BaseDatabaseModel::getInstance('Orders', 'TjlmsModel', array('ignore_request' => true));
				$objOrder   = $orderModel->getEcTrackingData($this->orderinfo->order_id);
				$dimension = $com_params->get('ga_product_type_dimension');

				$objOrder->productTypeDimensionValue = $dimension ? $dimension : '';

				$orderData[] = $objOrder;
				$ecTrackingData = json_encode($orderData);
				$ecStepData = json_encode($orderData['0']);
				$document = Factory::getDocument();
				$document->addScriptDeclaration("
					jQuery(document).ready(function()
					{
						var ecTrackingData= " . $ecTrackingData . ";
						var ecStepData= " . $ecStepData . ";
						ecTrackingData = JSON.stringify(ecTrackingData);
						ecStepData = JSON.stringify(ecStepData);
						ecTrackingData = JSON.parse(ecTrackingData);
						ecStepData = JSON.parse(ecStepData);
						tjanalytics.ga.addProduct(ecTrackingData);
						tjanalytics.ga.setTransaction(ecStepData);
					});
				");
				$uri = Uri::getInstance();
				$currentUrl = $uri->toString();
				$currentUrl = str_replace("ecTrackId=" . $ecTrackId, "ecTrackId=0", $currentUrl);
				$document->addScriptDeclaration("window.history.pushState( '', '', '" . $currentUrl . "');");
			}
		}
	}
}
