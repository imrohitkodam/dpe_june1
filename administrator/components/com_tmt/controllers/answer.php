<?php
/**
 * @version     1.0.0
 * @package     com_tmt
 * @copyright   Copyright (C) 2013. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Techjoomla <contact@techjoomla.com> - http://techjoomla.com
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\MVC\Controller\FormController;


/**
 * Answer controller class.
 */
class TmtControllerAnswer extends FormController
{

    function __construct() {
        $this->view_list = 'answers';
        parent::__construct();
    }

}