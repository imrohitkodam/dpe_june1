<?php
/**
 * @package     JMailAlerts
 * @subpackage  com_jmailalerts
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2022 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access.
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;

if (!defined('DS'))
{
	define('DS', DIRECTORY_SEPARATOR);
}

// Include hepler
$helperPath = JPATH_SITE . '/components/com_jmailalerts/helpers/emailhelper.php';

if (!class_exists('jmailalertsemailhelper'))
{
	JLoader::register('jmailalertsemailhelper', $helperPath);
	JLoader::load('jmailalertsemailhelper');
}

// Define constants
// Define wrapper class
define('JMAILALERTS_WRAPPER_CLASS', "jmailalerts-wrapper");

// Execute the task.
$controller = BaseController::getInstance('Jmailalerts');
$controller->execute(Factory::getApplication()->input->get('task'));
$controller->redirect();
