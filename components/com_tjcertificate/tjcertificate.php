<?php
/**
 * @package     TJCertificate
 * @subpackage  com_tjcertificate
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;

JLoader::import('components.com_tjcertificate.includes.tjcertificate', JPATH_ADMINISTRATOR);
TJCERT::init('site');

JLoader::registerPrefix('TjCertificate', JPATH_COMPONENT);
JLoader::register('TjCertificateController', JPATH_COMPONENT . '/controller.php');

// Execute the task.
$controller = BaseController::getInstance('TjCertificate');
$controller->execute(Factory::getApplication()->input->get('task'));
$controller->redirect();
