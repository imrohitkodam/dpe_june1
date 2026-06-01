<?php
/**
 * @ version    SVN: <svn_id>
 *
 * @ package    Com_Tmt
 *
 * @copyright  Copyright (C) 2013 - 2014 All rights reserved
 *
 * @license    GNU General Public License version 2 or later
 *
 * @ author     Display Name <contact@techjoomla.com>
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\MVC\Controller\FormController;


/**
 * Category controller class.
 *
 * @copyright  Copyright (C) 2013 -2014 All rights reserved.
 *
 * @since      1.0.0
 */
class TmtControllerCategory extends FormController
{
		public $view_list;
		/**
		 * Constructor.
		 *
		 * @see     JControllerLegacy
		 *
		 * @since   1.0.0
		 *
		 * @throws  Exception
		 */

		public function __construct()
		{
			$this->view_list = 'categories';
			parent::__construct();
		}
}
