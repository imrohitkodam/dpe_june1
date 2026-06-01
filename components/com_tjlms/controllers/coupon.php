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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Router\Route;

require_once JPATH_ADMINISTRATOR . '/components/com_tjlms/tables/coupon.php';

/**
 * Coupon controller class.
 *
 * @since  1.0.0
 */
class TjlmsControllerCoupon extends FormController
{
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
		$code = $input->get('couponcode', '', 'STRING');
		$course_id = $input->get('course_id', '0', 'INT');
		$model = $this->getModel('coupon');

		$value = $model->validatecode($code);

		echo $value;
		jexit();
	}

	/**
	 * Method to edit an existing record.
	 *
	 * @param   string  $key     The name of the primary key of the URL variable.
	 * @param   string  $urlVar  The name of the URL variable if different from the primary key
	 *                           (sometimes required to avoid router collisions).
	 *
	 * @return  boolean  True if access level check and checkout passes, false otherwise.
	 *
	 * @since   12.2
	 */
	public function edit($key = null, $urlVar = null)
	{
		$model = $this->getModel();
		$table = $model->getTable();
		$cid   = $this->input->post->get('cid', array(), 'array');

		// Determine the name of the primary key for the data.
		if (empty($key))
		{
			$key = $table->getKeyName();
		}

		// To avoid data collisions the urlVar may be different from the primary key.
		if (empty($urlVar))
		{
			$urlVar = $key;
		}

		// Get the previous record id (if any) and the current record id.
		$recordId = (int) (count($cid) ? $cid[0] : $this->input->getInt($urlVar));

		if (!$recordId)
		{
			Log::add(Text::_('COM_TJLMS_COUPONS_NO_ITEM_SELECTED'), Log::WARNING, 'jerror');
			$input = $app->input;
			$ComtjlmsHelper = new ComtjlmsHelper;
			$itemid = $ComtjlmsHelper->getitemid('index.php?option=com_tjlms&view=coupons');
			$this->setRedirect(Route::_('index.php?option=com_tjlms&view=coupons&Itemid=' . $itemid), false);

			return false;
		}
		else
		{
			return parent::edit($key, $urlVar);
		}
	}
}
