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
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

require_once JPATH_COMPONENT . DS . 'controller.php';
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

jimport('joomla.application.component.controller');

/**
 * Tjmodules list controller class.
 *
 * @since  1.0.0
 */
class TjlmsControllerbuy extends tjlmsController
{
	protected $tnc;

	protected $article;

	protected $doesArticleExists;

	protected $res;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct()
	{
		parent::__construct();

		// Initialise the session object
		$this->session = Factory::getSession();

		// Language
		$language = Factory::getLanguage();

		// Set the base directory for the language
		$base_dir = JPATH_SITE;

		// Load the language. IMPORTANT Becase we use ajax to load cart
		$language->load('com_tjlms', $base_dir, $language->getTag(), true);
	}

	/**
	 * Function used to load state of a country.
	 *
	 * @return  json
	 *
	 * @since  1.0.0
	 */
	public function loadState()
	{
			$db = Factory::getDBO();
			$jinput = Factory::getApplication()->input;
			$country = $jinput->get('country', '', 'STRING');

			$model = $this->getModel('buy');
			$state = $model->getuserState($country);
			echo json_encode($state);
			jexit();
	}

	/**
	 * Save step 1 of check out...save order details
	 *
	 * @return  mixed object
	 *
	 * @since  1.0.0
	 */
	public function save_step_select_subsplan()
	{
		$session = Factory::getSession();
		$model = $this->getModel('buy');
		$post = Factory::getApplication()->input->post;
		$orderData = array();
		$orderData['user_id'] = Factory::getUser()->id;
		$orderData['course_id'] = $post->get('course_id', '', 'INT');
		$orderData['plan_id'] = $post->get('selected_plan', '', 'INT');
		$orderData['coupon_code'] = $post->get('coupon_code', '', 'STRING');
		$res = $model->createOrder($orderData, 'step_select_subsplan');

		echo json_encode($res);
		jexit();
	}

	/**
	 * Save step 2 of check out...save billing details
	 *
	 * @return  json
	 *
	 * @since  1.0.0
	 */
	public function save_step_billinginfo()
	{
		$res = array();
		$session = Factory::getSession();
		$post = Factory::getApplication()->input->post;
		$model = $this->getModel('buy');
		$orderData['user_id'] = Factory::getUser()->id;
		$orderData['bill'] = $post->get('bill', '', 'ARRAY');
		$orderData['comment'] = $post->get('comment', '', 'STRING');
		$orderData['accpt_terms'] = $post->get('accpt_terms', 'off', 'STRING');

		$com_params = ComponentHelper::getParams('com_tjlms');
		$this->tnc = $com_params->get('terms_condition', 0, 'INT');

		if ($this->tnc)
		{
			$this->article = $com_params->get('tnc_article', '', 'INT');

			// Check if the article exists
			$this->doesArticleExists = $model->doesArticleExists($this->article);
		}

		if ($this->tnc && $this->doesArticleExists)
		{
			if ($orderData['accpt_terms'] == 'on')
			{
				$res = $model->createOrder($orderData, 'save_step_billinginfo');

				if (!empty($res['order_id']))
				{
					// Terms & condition for techjoomla extension
					$userPrivacyData = array();

					$userPrivacyData['client'] = 'com_tjlms.buy';
					$userPrivacyData['client_id'] = $res['order_id'];
					$userPrivacyData['user_id'] = $orderData['user_id']?$orderData['user_id']:0;
					$userPrivacyData['purpose'] = Text::_('COM_TJLMS_USER_PRIVACY_TERMS_PURPOSE_PAYMENT');
					$userPrivacyData['accepted'] = ($orderData['accpt_terms'] === 'on')?1:0;
					$userPrivacyData['date'] = Factory::getDate('now')->toSQL();

					$model->savePrivacyData($userPrivacyData);
				}
			}
			else
			{
				$res['tnc'] = 0;
			}
		}
		else
		{
				$res = $model->createOrder($orderData, 'save_step_billinginfo');
		}

		echo json_encode($res);
		jexit();
	}

	/**
	 * Function called for applying tax.
	 *
	 * @return  json
	 *
	 * @since  1.0.0
	 */
	public function applytax()
	{
		$input = Factory::getApplication()->input;
		$post = $input->post;
		$total_calc_amt = $input->get('total_calc_amt', '', 'STRING');
		PluginHelper::importPlugin('lmstax');

		// Call the plugin and get the result
		$taxresults = Factory::getApplication()->triggerEvent('onAddTax', array($total_calc_amt));

		echo json_encode($taxresults['0']);
		jexit();
	}

	/**
	 * Function used to get coupon
	 *
	 * @return  json
	 *
	 * @since  1.0.0
	 */
	public function getcoupon()
	{
		$user = Factory::getUser();
		$db = Factory::getDBO();
		$input = Factory::getApplication()->input;
		$data = $input->post;
		$course_id = $data->get('course_id', '0', 'int');
		$subscriptionPlan = $data->get('selected_plan', '', 'int');
		$c_code = $data->get('coupon_code', '0', 'STRING');

		$count = '';
		$model = $this->getModel('buy');
		$count = $model->getcoupon($c_code, $course_id, $subscriptionPlan);

		switch ($count->status)
		{
			case 'invalid' :
			$c[] = array("error" => 1, "msg" => Text::_('COM_TJLMS_COP_INVALID'));
			break;

			case 'none' :
			$c[] = array("error" => 1, "msg" => Text::_('COM_TJLMS_COP_EXISTS'));
			break;

			case 'expired' :
			$c[]  = array("error" => 1, "msg" => Text::_('COM_TJLMS_COP_EXPIRED'));
			break;

			case 'exceed' :
			$c[]  = array("error" => 1, "msg" => Text::_('COM_TJLMS_COP_EXCEEDS'));
			break;

			case 'ok' :
			$data = $count->data;
			$c[] = array("value" => $data[0]->value, "val_type" => $data[0]->val_type);
			break;
		}

		echo json_encode($c);
		jexit();
	}

	/**
	 * Function to get order data for google analytics.
	 *
	 * @return  json
	 *
	 * @since  1.3.20
	 */
	public function generateOrderData()
	{
		$com_params = ComponentHelper::getParams('com_tjlms');
		$ecTrackingDataArray = array();
		$input = Factory::getApplication()->input;
		$orderId = $input->get('order_id', '', 'INT');
		$orderModel = BaseDatabaseModel::getInstance('Orders', 'TjlmsModel', array('ignore_request' => true));
		$ecTrackingData   = $orderModel->getEcTrackingData($orderId);
		$ecTrackingData->step_number = 4;
		$dimension = $com_params->get('ga_product_type_dimension');

		if ($com_params->get('track_attendee_step') == 1)
		{
			$ecTrackingData->step_number = 5;
		}

		$ecTrackingData->productTypeDimensionValue = $dimension ? $dimension : '';

		$ecTrackingDataArray[] = $ecTrackingData;
		echo json_encode($ecTrackingDataArray);
		jexit();
	}
}
