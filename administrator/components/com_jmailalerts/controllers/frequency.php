<?php
/**
 * @package     JMailAlerts
 * @subpackage  com_jmailalerts
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2022 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access.
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\MVC\Controller\FormController;

/**
 * Subscriber controller class.
 *
 * @package     JMailAlerts
 * @subpackage  com_jmailalerts
 * @since       2.6.1
 */
class JmailalertsControllerFrequency extends FormController
{
	/**
	 * Constructor.
	 *
	 * @since   2.6.1
	 * @see     JController
	 */
	public function __construct()
	{
		$this->view_list = 'frequencies';
		parent::__construct();
	}
}
