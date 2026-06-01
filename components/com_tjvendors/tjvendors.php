<?php
/**
 * @package     TJVendors
 * @subpackage  com_tjvendors
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;

include_once JPATH_SITE . '/components/com_tjvendors/includes/tjvendors.php';

JLoader::registerPrefix('Tjvendors', JPATH_COMPONENT);

JLoader::register('TjvendorsController', JPATH_COMPONENT . '/controller.php');
$TjvendorFrontHelper = JPATH_ROOT . '/components/com_tjvendors/helpers/fronthelper.php';

if (!class_exists('TjvendorFrontHelper'))
{
	JLoader::register('TjvendorFrontHelper', $TjvendorFrontHelper);
	JLoader::load('TjvendorFrontHelper');
}

TJVendors::init();

// Execute the task.
$controller = BaseController::getInstance('Tjvendors');
$controller->execute(Factory::getApplication()->input->get('task'));
$controller->redirect();
