<?php
/**
 * @package     JMailAlerts
 * @subpackage  com_jmailalerts
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2022 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;

// Access check.
if (!Factory::getUser()->authorise('core.manage', 'com_jmailalerts'))
{
	throw new Exception(Text::_('JERROR_ALERTNOAUTHOR'));
}

if (!defined('DS'))
{
	define('DS', DIRECTORY_SEPARATOR);
}

HTMLHelper::_('stylesheet', 'administrator/components/com_jmailalerts/assets/css/jmailalerts.css');

// Define constants
// Define wrapper class
define('JMAILALERTS_WRAPPER_CLASS', "jmailalerts-wrapper");

if (JVERSION < '4.0.0')
{
	HTMLHelper::_('behavior.tabstate');
	HTMLHelper::_('behavior.multiselect');
	HTMLHelper::_('formbehavior.chosen', 'select');
}

HTMLHelper::_('bootstrap.tooltip');

// Load manage user helper
$manageUserHelperPath = JPATH_ADMINISTRATOR . '/components/com_jmailalerts/helpers/manageuser.php';

if (!class_exists('ManageUserHelper'))
{
	JLoader::register('ManageUserHelper', $manageUserHelperPath);
	JLoader::load('ManageUserHelper');
}

// Include dependancies
$controller = BaseController::getInstance('Jmailalerts');
$controller->execute(Factory::getApplication()->input->get('task'));
$controller->redirect();
