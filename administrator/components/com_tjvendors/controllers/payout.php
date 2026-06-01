<?php
/**
 * @package     TJVendors
 * @subpackage  com_tjvendors
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\FormController;

/**
 * Vendor controller class.
 *
 * @since  1.6
 */
class TjvendorsControllerPayout extends FormController
{
	/**
	 * Constructor
	 *
	 * @throws Exception
	 */
	public function __construct()
	{
		$this->view_list = 'payouts';
		$this->input = Factory::getApplication()->input;

		if (empty($this->client))
		{
			$this->client = $this->input->get('client', '');
		}

		parent::__construct();
	}

	/**
	 * Gets the URL arguments to append to a list redirect.
	 *
	 * @return  string  The arguments to append to the redirect URL.
	 *
	 * @since   1.6
	 */

	protected function getRedirectToListAppend()
	{
		$input = Factory::getApplication()->input;
		$vendor_id = $input->get('vendor_id', '', 'INTEGER');
		$client = $input->get('client', '', 'STRING');
		$append = parent::getRedirectToListAppend();
		$append .= '&vendor_id=' . $vendor_id;
		$append .= '&client=' . $client;

		return $append;
	}

	/**
	 * Add credit entry for the purchase.
	 *
	 * @return  null
	 *
	 * @since   1.6
	 */
	public function addCreditEntry()
	{
		$model = $this->getModel('payout');
		$results = $model->addCreditEntry();
	}

	/**
	 * Change payout status.
	 *
	 * @return  null
	 *
	 * @since   1.6
	 */
	public function changePayoutStatus()
	{
		$input  = Factory::getApplication()->input->post;
		$payout_id = $input->get('payout_id', '', 'STRING');
		$paidUnpaid = $input->get('paidUnpaid', '', 'STRING');
		$model = $this->getModel('Payout');
		$results = $model->changePayoutStatus($payout_id, $paidUnpaid);

		echo json_encode($results);
		jexit();
	}
}
