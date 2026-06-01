<?php
/**
 * @version     admin/watchfulli.php 2020-06-03 zanardi
 * @package     Watchful Client
 * @author      Watchful
 * @authorUrl   https://watchful.net
 * @copyright   Copyright (c) 2012-2023 Watchful
 * @license     GNU/GPL v3 or later
 */

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;

(defined('_JEXEC') or defined('JPATH_PLATFORM')) or die;

defined('WATCHFULLI_PATH') or define('WATCHFULLI_PATH', __DIR__);
defined('WATCHFULLI_ROOT') or define('WATCHFULLI_ROOT', WATCHFULLI_PATH);

// Enable class autoloader, taking care of including the default Joomla one
if (function_exists('__autoload'))
{
	spl_autoload_register('__autoload');
}
require_once JPATH_COMPONENT_ADMINISTRATOR . '/autoloader.php';
spl_autoload_register('classLoader');

Watchfulli::debug("Joomla version: " . Watchfulli::getJoomlaVersion());

$canAdmin = Factory::getUser()->authorise('core.manage', 'com_watchfulli');

try
{
	/** @var CMSApplication $application */
	$application = Factory::getApplication();
	$task        = $application->input->get('task', 'display');
	$controller  = BaseController::getInstance('watchfulli');
}
catch (Exception $ex)
{
	Watchfulli::debug("Exception in JControllerLegacy::getInstance");
	die("Exception in JControllerLegacy::getInstance");
}

if (!$canAdmin && Factory::getApplication()->isClient('administrator'))
{
	throw new Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
}

$controller->execute($task);
$controller->redirect();
