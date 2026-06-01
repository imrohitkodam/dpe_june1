<?php
/**
 * @package     JMailAlerts
 * @subpackage  com_jmailalerts
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2022 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access.
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;

/**
 * JMailAlerts Component Controller
 *
 * @since  1.0
 */
class JmailalertsController extends BaseController
{
	/**
	 * Method to display a view.
	 *
	 * @param   boolean  $cachable   If true, the view output will be cached.
	 * @param   boolean  $urlparams  An array of safe URL parameters and their variable types, for valid values see {@link InputFilter::clean()}.
	 *
	 * @return  void
	 *
	 * @since   1.5
	 */
	public function display($cachable = false, $urlparams = false)
	{
		$this->input = Factory::getApplication()->input;
		$vName = $this->input->get('view', 'emails');
		$this->input->set('view', $vName);

		$vLayout = 'default';
		$this->input->set('layout', $vLayout);

		parent::display($cachable, $urlparams);
	}

	/**
	 * Method to save preferences
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	public function savePref()
	{
		$model = $this->getModel('emails');
		$model->savePref();
	}

	/**
	 * Method to send mails
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	public function processMailAlerts()
	{
		$model = $this->getModel('emails');
		$model->processMailAlerts();
	}
	/**
	 * Method to send OTP
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	public function sendOtp()
	{
		$model = $this->getModel('emails');
		$model->sendOtp();
	}

	/**
	 * Method to cleanup OTP records
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	public function cleanupOtp()
	{
		$model = $this->getModel('emails');
		$model->cleanupOtp();
		Factory::getApplication()->close();
	}

	/**
	 * Method to verify OTP via AJAX
	 *
	 * @return  void
	 */
	public function verifyOtpAjax()
	{
		$model = $this->getModel('emails');
		$model->verifyOtpAjax();
	}
}
