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
TJCERT::init('admin');

JLoader::registerPrefix('TjCertificate', JPATH_ADMINISTRATOR);
JLoader::register('TjCertificateController', JPATH_ADMINISTRATOR . '/controller.php');

define('MEDIA_ROOT', JPATH_ROOT . '/media');
define('TJ_CERTIFICATE_TEMPLATE_FOLDER', 'certificate-templates');
define('TJ_CERTIFICATE_DEFAULT_TEMPLATE', MEDIA_ROOT . '/com_tjcertificate/' . TJ_CERTIFICATE_TEMPLATE_FOLDER);
define('TJ_CERTIFICATE_REPLACEMENT_TAG', JPATH_ADMINISTRATOR . '/components');

// Execute the task.
$controller = BaseController::getInstance('TjCertificate');
$controller->execute(Factory::getApplication()->input->get('task'));
$controller->redirect();
