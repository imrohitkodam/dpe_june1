<?php
/**
 * @package     JMailAlerts
 * @subpackage  com_jmailalerts
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2022 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// Do not allow direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\MVC\Controller\FormController;

/**
 * Subscriber controller class.
 *
 * @package     JMailAlerts
 * @subpackage  com_jmailalerts
 * @since       2.6.1
 */
class JmailalertsControllerSubscriber extends FormController
{
	/**
	 * Constructor
	 *
	 * @throws Exception
	 */
	public function __construct()
	{
		$this->view_list = 'subscribers';
		parent::__construct();
	}

	/**
	 * Calls the model method to return email address
	 *
	 * @return void
	 */
	public function preview()
	{
		$model = $this->getModel();
		$model->preview();
	}
}
