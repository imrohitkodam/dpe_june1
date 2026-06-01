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
use Joomla\CMS\Language\Text;

require_once JPATH_COMPONENT . DS . 'controller.php';

jimport('joomla.application.component.controller');

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Table\Table;

/**
 * Tjmodules list controller class.
 *
 * @since  1.0.0
 */
class TjlmsControllerpayment extends BaseController
{
	/**
	 * Function used to get payment gatway HTML
	 *
	 * @return  json html
	 *
	 * @since  1.0.0
	 */
	public function getHTML()
	{
		$model    = $this->getModel('payment');
		$jinput   = Factory::getApplication()->input;
		$user     = Factory::getUser();
		$session  = Factory::getSession();
		$order_id = $session->get('order_id');
		$html     = $model->getHTML($order_id);
		$payhtml  = array();

		if (!empty($html[0]))
		{
			$payhtml['payhtml'] = $html[0];
		}

		echo json_encode($payhtml);
		jexit();
	}

	/**
	 * Function
	 *
	 * @return  redirects
	 *
	 * @since  1.0.0
	 */
	public function confirmpayment()
	{
		$model     = $this->getModel('payment');
		$session   = Factory::getSession();
		$jinput    = Factory::getApplication()->input;
		$order_id  = $session->get('lms_orderid');
		$pg_plugin = $jinput->get('processor');
		$response  = $model->confirmpayment($pg_plugin, $order_id);
	}

	/**
	 * Function
	 *
	 * @return  redirects
	 *
	 * @since  1.0.0
	 */
	public function processpayment()
	{
		$mainframe = Factory::getApplication();
		$jinput    = $mainframe->input;
		$session   = Factory::getSession();

		if ($session->has('payment_submitpost'))
		{
			$post = $session->get('payment_submitpost');
			$session->clear('payment_submitpost');
		}
		else
		{
			$post = $jinput->post->getArray();
		}

		$pg_plugin = $jinput->get('processor');
		$model     = $this->getModel('payment');
		$order_id  = $jinput->get('order_id', '', 'STRING');

		$session        = Factory::getSession();
		$sessionOrderId = $session->get('lms_orderid');

		// Add Table Path
		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjlms/tables');

		$orderTable = Table::getInstance('Orders', 'TjlmsTable');
		$orderTable->load(array('order_id' => $order_id));

		if ($sessionOrderId != $orderTable->id)
		{
			$mainframe->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');

			return;
		}

		if (empty($post) || empty($pg_plugin))
		{
			$mainframe->enqueueMessage(Text::_('SOME_ERROR_OCCURRED'), 'error');

			return;
		}

		$response = $model->processpayment($post, $pg_plugin, $order_id);
		$mainframe->enqueueMessage($response['msg']);
		$mainframe->redirect($response['return']);
	}

	/**
	 * Function to verify the payment status based on the webhooks
	 *
	 * @return  string
	 *
	 * @since  1.0.0
	 */
	public function notify()
	{
		$app = Factory::getApplication();
		$jinput    = $app->input;
		$pgPlugin = $jinput->get('processor', '', 'STRING');

		if (empty($pgPlugin))
		{
			echo new JsonResponse(null, Text::_('SOME_ERROR_OCCURRED'), true);
			$app->close();
		}

		$session   = Factory::getSession();

		if ($session->has('payment_submitpost'))
		{
			$post = $session->get('payment_submitpost');
			$session->clear('payment_submitpost');
		}
		else
		{
			$post = $jinput->post->getArray();
		}

		if (empty($post))
		{
			// Get the input from payment plugin
			PluginHelper::importPlugin('payment', $pgPlugin);

			$data = Factory::getApplication()->triggerEvent('onTP_ProcessInputData');
			$post = $data[0];
		}

		if (empty($post))
		{
			echo new JsonResponse(null, Text::_('SOME_ERROR_OCCURRED'), true);
			$app->close();
		}

		/** @var $model TjlmsModelPayment */
		$model     = $this->getModel('payment');
		$orderId  = $jinput->get('order_id', '', 'STRING');

		if (empty($orderId))
		{
			$orderId = $post['order_id'];
		}

		$response = $model->processpayment($post, $pgPlugin, $orderId);

		echo new JsonResponse($response['status'], $response['msg']);
		$app->close();
	}

	/**
	 * Function
	 *
	 * @return  redirects
	 *
	 * @since  1.0.0
	 */
	public function changegateway()
	{
		JLoader::import('payment', JPATH_SITE . DS . 'components' . DS . 'com_tjlms' . DS . 'models');
		$db              = Factory::getDBO();
		$jinput          = Factory::getApplication()->input;
		$model           = new tjlmsModelpayment;
		$selectedGateway = $jinput->get('gateways', '');
		$order_id        = $jinput->get('order_id', '');
		$return          = '';

		if (!empty($selectedGateway) && !empty($order_id))
		{
			$model->updateOrderGateway($selectedGateway, $order_id);
			$payhtml = $model->getHTML($order_id);
		}

		if (!empty($payhtml))
		{
			echo $payhtml[0];
		}

		jexit();
	}
}
