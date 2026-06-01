<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Multiagency
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2017 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\MVC\Controller\FormController;

jimport('joomla.application.component.controllerform');

/**
 * Subscription controller class.
 *
 * @since  1.6
 */
class MultiagencyControllerLicence extends FormController
{
	/**
	 * Constructor
	 *
	 * @throws Exception
	 */
	public function __construct()
	{
		$this->view_list = 'licences';
		parent::__construct();
	}
}
