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

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\MVC\Controller\AdminController;

jimport('joomla.application.component.controlleradmin');

/**
 * Coupons list controller class.
 *
 * @since  1.0.0
 */
class TjlmsControllerCoupons extends AdminController
{
	public $text_prefix;

	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see     JControllerLegacy
	 * @since   1.0.0
	 * @throws  Exception
	 */
	public function __construct($config = array())
	{
		parent::__construct($config);
		$this->text_prefix = 'COM_TJLMS_COUPONS';
	}

	/**
	 * Proxy for getModel.
	 *
	 * @param   STRING  $name    model name
	 * @param   STRING  $prefix  model prefix
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	public function getModel($name = 'coupon', $prefix = 'TjlmsModel', $config = Array())
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
		$app   = Factory::getApplication();
		$input = $app->input;
		$pks   = $input->post->get('cid', array(), 'array');
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
		$app->close();
	}

	/**
	 * Method to delete the model state.
	 *
	 * @return  void
	 *
	 * @since   1.3.30
	 */
	public function delete()
	{
		$app   = Factory::getApplication();
		$user  = Factory::getUser();
		$input = $app->input;
		$cid   = $input->post->get('cid', array(), 'array');

		if (!$user->authorise('core.delete', 'com_tjlms'))
		{
			$app->enqueueMessage(Text::_('JERROR_CORE_DELETE_NOT_PERMITTED'), 'error');
            $app->setHeader('status', 403, true);

			return;
		}

		foreach ($cid as $id)
		{
			// Get the model
			$model = $this->getModel();

			// Delete coupons
			$model->delete($id);
		}

		$redirect = Route::_('index.php?option=com_tjlms&view=coupons', false);
		$this->setRedirect($redirect);
	}
}
