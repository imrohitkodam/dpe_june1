<?php
/**
 * @package     Multiagency
 * @subpackage  com_multiagency
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Factory;

// Register class prefix
JLoader::registerPrefix('Multiagency', JPATH_COMPONENT);

// Include dependancies
jimport('joomla.application.component.controller');

JLoader::import('components.com_multiagency.includes.multiagency', JPATH_SITE);
Multiagency::utilities()->getLanguageConstant();

JLoader::register('MultiagencyController', JPATH_COMPONENT . '/controller.php');
JLoader::import("/components/com_subusers/includes/rbacl", JPATH_ADMINISTRATOR);
JLoader::import("/components/com_cluster/includes/cluster", JPATH_ADMINISTRATOR);

// Execute the task.
$controller = BaseController::getInstance('Multiagency');
$controller->execute(Factory::getApplication()->input->get('task'));
$controller->redirect();
