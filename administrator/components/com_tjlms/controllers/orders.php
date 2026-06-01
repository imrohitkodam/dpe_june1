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
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Log\Log;
use Joomla\Utilities\ArrayHelper;

jimport('joomla.application.component.controlleradmin');

/**
 * Orders list controller class.
 *
 * @since  1.0.0
 */
class TjlmsControllerOrders extends AdminController
{
	/**
	 * Proxy for getModel.
	 *
	 * @param   STRING  $name    model name
	 * @param   STRING  $prefix  model prefix
	 * @param   ARRAY   $config  configuration
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	public function getModel($name = 'orders', $prefix = 'TjlmsModel', $config = Array())
	{
		$model = parent::getModel($name, $prefix, array('ignore_request' => true));

		return $model;
	}

	/**
	 * Method to save the submitted ordering values for records via AJAX.
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	public function saveOrderAjax()
	{
		// Get the input
		$input = Factory::getApplication()->input;
		$pks = $input->post->get('cid', array(), 'array');
		$order = $input->post->get('order', array(), 'array');

		// Sanitize the input
		ArrayHelper::toInteger($pks);
		ArrayHelper::toInteger($order);

		// Get the model
		$model = $this->getModel();

		// Save the ordering
		$return = $model->saveorder($pks, $order);

		if ($return)
		{
			echo "1";
		}

		// Close the application
		Factory::getApplication()->close();
	}

	/**
	 * Method to save/change the order status
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	public function save()
	{
		// Check for request forgeries
		Session::checkToken() or die(Text::_('JINVALID_TOKEN'));

		$mainframe = Factory::getApplication();
		$model = $this->getModel('orders');
		$input = Factory::getApplication()->input;
		$post = $input->post;
		$order_id = $post->get('order_id');
		$status = $post->get('payment_status');

		$updated_order = $model->updateOrderStatus($order_id, $status);

		if ($updated_order)
		{
			// Add a message to the message queue
			$mainframe->enqueueMessage(Text::_('COM_TJLMS_ORDER_STATUS_CHANGE'));
		}
		else
		{
			// Add a message to the message queue
			$mainframe->enqueueMessage(Text::_('COM_TJLMS_ORDER_STATUS_CHANGE_FAILED'), 'error');
		}

		$this->setRedirect(Route::_('index.php?option=com_tjlms&view=orders', false));
	}

	/**
	 * Removes an item.
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	public function delete()
	{
		// Check for request forgeries
		Session::checkToken() or die(Text::_('JINVALID_TOKEN'));

		// Get items to remove from the request.
		$cid = Factory::getApplication()->input->get('cid', array(), 'array');

		if (!is_array($cid) || count($cid) < 1)
		{
			Log::add(Text::_($this->text_prefix . '_NO_ITEM_SELECTED'), Log::WARNING, 'jerror');
		}
		else
		{
			// Get the model.
			$model = $this->getModel('orders');

			// Make sure the item ids are integers
			jimport('joomla.utilities.arrayhelper');
			ArrayHelper::toInteger($cid);

			// Remove the items.
			if ($model->delete($cid))
			{
				$this->setMessage(Text::plural($this->text_prefix . '_ORDER_ITEMS_DELETED', count($cid)));
			}
			else
			{
				$this->setMessage($model->getError());
			}
		}

		$this->setRedirect(Route::_('index.php?option=com_tjlms&view=orders', false));
	}
}
