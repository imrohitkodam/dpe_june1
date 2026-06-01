<?php
/**
 * @package     TjCompetency
 * @subpackage  com_tjcompetency
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;

JLoader::import('components.com_tjcompetency.includes.tjcompetency', JPATH_ADMINISTRATOR);
TjCompetency::init('admin');

JLoader::registerPrefix('TjCompetency', JPATH_ADMINISTRATOR);
JLoader::register('TjCompetencyController', JPATH_ADMINISTRATOR . '/controller.php');

define('MEDIA_ROOT', JPATH_ROOT . '/media');

// Execute the task.
$controller = BaseController::getInstance('TjCompetency');
$controller->execute(Factory::getApplication()->input->get('task'));
$controller->redirect();
