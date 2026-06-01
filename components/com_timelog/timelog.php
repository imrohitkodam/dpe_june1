<?php
/**
 * @package    Com_Timelog
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

// Include dependancies
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;

// Include dependancies
JLoader::import('components.com_timelog.includes.timelog', JPATH_ADMINISTRATOR);

// Execute the task.
$controller = BaseController::getInstance('Timelog');
$controller->execute(Factory::getApplication()->input->get('task'));
$controller->redirect();
