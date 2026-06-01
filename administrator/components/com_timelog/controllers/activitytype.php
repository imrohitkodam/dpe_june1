<?php
/**
 * @package    Com_Timelog
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\FormController;

/**
 * Activitytype controller class.
 *
 * @since  __DEPLOY_VERSION__
 */
class TimelogControllerActivitytype extends FormController
{
	/**
	 * The URL view list variable.
	 *
	 * @var    string
	 * @since  __DEPLOY_VERSION__
	 */
	protected $view_list;

	/**
	 * Constructor
	 *
	 * @throws Exception
	 */
	public function __construct()
	{
		$this->view_list = 'activitytypes';
		parent::__construct();
	}
}
