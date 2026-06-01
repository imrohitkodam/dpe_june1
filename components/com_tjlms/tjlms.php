<?php
/**
 * @package     TJLms
 * @subpackage  com_shika
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;

if (!defined('DS'))
{
	define('DS', '/');
}

$lang = Factory::getLanguage();
$lang->load('com_tjlms', JPATH_ADMINISTRATOR);

include_once  JPATH_ADMINISTRATOR . '/components/com_tjlms/includes/tjlms.php';
Tjlms::init();

// Load js assets
jimport('joomla.filesystem.file');
require_once JPATH_SITE . '/components/com_tjlms/defines.php';

$path = JPATH_COMPONENT . '/helpers/' . 'main.php';

if (!class_exists('comtjlmsHelper'))
{
	// Require_once $path;
	JLoader::register('comtjlmsHelper', $path);
	JLoader::load('comtjlmsHelper');
}

$path = JPATH_COMPONENT . '/helpers/' . 'courses.php';

if (!class_exists('tjlmsCoursesHelper'))
{
	// Require_once $path;
	JLoader::register('tjlmsCoursesHelper', $path);
	JLoader::load('tjlmsCoursesHelper');
}

$path = JPATH_COMPONENT . '/libraries/scorm/' . 'scormhelper.php';

if (!class_exists('comtjlmsScormHelper'))
{
	// Require_once $path;
	JLoader::register('comtjlmsScormHelper', $path);
	JLoader::load('comtjlmsScormHelper');
}

$path = JPATH_COMPONENT . '/helpers/' . 'tjdbhelper.php';

if (!class_exists('tjlmsdbhelper'))
{
	// Require_once $path;
	JLoader::register('tjlmsdbhelper', $path);
	JLoader::load('tjlmsdbhelper');
}

$path = JPATH_SITE . '/components/com_tjlms/helpers/tracking.php';

if (!class_exists('comtjlmstrackingHelper'))
{
	JLoader::register('comtjlmstrackingHelper', $path);
	JLoader::load('comtjlmstrackingHelper');
}

$path = JPATH_COMPONENT . '/helpers/' . 'lesson.php';

if (!class_exists('TjlmsLessonHelper'))
{
	// Require_once $path;
	JLoader::register('TjlmsLessonHelper', $path);
	JLoader::load('TjlmsLessonHelper');
}

// Require specific controller if requested

require_once JPATH_COMPONENT . DS . 'controller.php';

if ($controller = Factory::getApplication()->input->get->getWord('controller'))
{
	$path = JPATH_COMPONENT . DS . 'controllers' . DS . $controller . '.php';

	if (file_exists($path))
	{
		require_once $path;
	}
	else
	{
		$controller = '';
	}
}

// Execute the task.
$controller = BaseController::getInstance('tjlms');
$controller->execute(Factory::getApplication()->input->get('task'));
$controller->redirect();
