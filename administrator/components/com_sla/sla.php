<?php
/**
 * @package    Sla
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;

JLoader::import('components.com_sla.includes.sla', JPATH_ADMINISTRATOR);

JLoader::registerPrefix('Sla', JPATH_ADMINISTRATOR);
JLoader::register('SlaController', JPATH_ADMINISTRATOR . '/controller.php');

// Execute the task.
$controller = BaseController::getInstance('Sla');
$controller->execute(Factory::getApplication()->input->get('task'));
$controller->redirect();
