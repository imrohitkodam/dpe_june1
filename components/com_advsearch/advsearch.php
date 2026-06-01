<?php
/**
 * @version     1.0.0
 * @package     com_advsearch
 * @copyright   Copyright (C) 2012. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      amol <amol_p@tekdi.net> - http://tekdi.net
 */

defined('_JEXEC') or die; 
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Factory;

// Include dependancies
jimport('joomla.application.component.controller');

// Execute the task.
$controller	= BaseController::getInstance('Advsearch');
$controller->execute(Factory::getApplication()->input->get('task'));
$controller->redirect();
