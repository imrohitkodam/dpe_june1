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
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Factory;


/**
 * Coupon controller class.
 *
 * @since  1.0.0
 */
class TjlmsControllerCoupon extends FormController
{
	public $view_list = '';

	public $text_prefix = '';
	/**
	 * Constructor.
	 *
	 * @see     JControllerLegacy
	 *
	 * @since   1.0.0
	 *
	 * @throws  Exception
	 */
	public function __construct()
	{
		parent::__construct();
		$this->view_list = 'coupons';
		$this->text_prefix = 'COM_TJLMS_COUPON';
	}

	/**
	 * Function to validate coupon code
	 * Ajax Call from backend coupon form view
	 *
	 * @return  void
	 *
	 * @since 1.0.0
	 */
	public function validatecode()
	{
		$app = Factory::getApplication();
		$input = $app->input;
		$code = $input->get('couponcode', '', 'CMD');
		$model = $this->getModel('coupon');

		$value = $model->validatecode($code);

		echo $value;
		jexit();
	}
}
