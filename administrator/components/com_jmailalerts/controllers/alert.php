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

use Joomla\CMS\MVC\Controller\FormController;

/**
 * Alert controller class.
 *
 * @since  2.5.0
 */
class JmailalertsControllerAlert extends FormController
{
	/**
	 * Constructor.
	 *
	 * @see     \JControllerLegacy
	 * @since   1.6
	 * @throws  \Exception
	 */
	public function __construct()
	{
		$this->view_list = 'alerts';
		parent::__construct();
	}

	/**
	 * Load template
	 *
	 * @return void
	 */
	public function loadTemplate()
	{
		$model = $this->getModel();
		$model->loadTemplate();
	}
}
