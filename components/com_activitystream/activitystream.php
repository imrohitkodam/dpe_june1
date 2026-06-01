<?php
/**
 * @version    SVN: <svn_id>
 * @package    ActivityStream
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2017 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;

// Include dependancies
jimport('joomla.application.component.controller');

JLoader::registerPrefix('Activitystream', JPATH_COMPONENT);
JLoader::register('ActivitystreamController', JPATH_COMPONENT . '/controller.php');

$lang = Factory::getLanguage();
$lang->load('com_activitystream', JPATH_SITE);

// Execute the task.
$controller = BaseController::getInstance('Activitystream');
$controller->execute(Factory::getApplication()->input->get('task'));
$controller->redirect();
