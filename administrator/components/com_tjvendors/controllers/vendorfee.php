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
use Joomla\CMS\Router\Route;
use Joomla\Registry\Registry;

/**
 * Vendor controller class.
 *
 * @since  1.6
 */
class TjvendorsControllerVendorFee extends FormController
{
	/**
	 * Constructor
	 *
	 * @throws Exception
	 */
	public function __construct()
	{
			$this->view_list = 'vendorfees';
		$this->input = Factory::getApplication()->input;

		if (empty($this->client))
		{
			$this->client = $this->input->get('client', '');
		}

		parent::__construct();
	}

	/**
	 * Gets the URL arguments to append to an item redirect.
	 *
	 * @param   integer  $recordId  The primary key fee_id for the item.
	 * @param   string   $urlVar    The name of the URL variable for the fee_id.
	 *
	 * @return  string  The arguments to append to the redirect URL.
	 *
	 * @since   1.6
	 */
	protected function getRedirectToItemAppend($recordId = null, $urlVar = 'id')
	{
		$input = Factory::getApplication()->input;
		$cid = $input->post->get('cid', array(), 'array');
		$formData = new Registry($input->get('jform', '', 'array'));
		$client = $input->get('client', '', 'STRING');
		$vendor_id = $input->get('vendor_id', '', 'INTEGER');
		$append = parent::getRedirectToItemAppend($recordId);
		$append .= '&vendor_id=' . $vendor_id . '&client=' . $client;

		return $append;
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
		$cid = $input->post->get('cid', array(), 'array');
		$formData = new Registry($input->get('jform', '', 'array'));
		$client = $input->get('client', '', 'STRING');
		$vendor_id = $input->get('vendor_id', '', 'STRING');

		// $vendor_id = (int) $this->getState($this->getName() . '.id');
		$append = parent::getRedirectToItemAppend($recordId);
		$append .= '&vendor_id=' . $vendor_id . '&client=' . $client;

		return $append;
	}

	/**
	 * Function to edit field data
	 *
	 * @param   integer  $key     null.
	 *
	 * @param   integer  $urlVar  null.
	 *
	 * @return  void
	 */
	public function edit($key = null, $urlVar = null)
	{
		$input = Factory::getApplication()->input;
		$cid = $input->post->get('cid', array(), 'array');
		$vendorId = (int) (count($cid) ? $cid[0] : $input->getInt('vendor_id'));
		$client = $input->get('client', '', 'STRING');
		$feeId = (int) (count($cid) ? $cid[0] : $input->getInt('fee_id'));
		$link = Route::_(
		'index.php?option=com_tjvendors&view=vendorfee&layout=edit&id=' . $feeId . '&vendor_id=' . $vendorId . '&client=' . $client, false
		);
		$this->setRedirect($link);
	}
}
