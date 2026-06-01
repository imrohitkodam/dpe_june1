<?php
/**
 * @package     JLike
 * @subpackage  com_jlike
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Controller\BaseController;

HTMLHelper::_('formbehavior.chosen', 'select');

$params = ComponentHelper::getParams('com_jlike');
$bsVersion = $params->get('bootstrap_version', '', 'STRING');

if (empty($bsVersion))
{
	$bsVersion = (JVERSION > '4.0.0') ? 'bs5' : 'bs3';
}

define('JLIKE_LOAD_BOOTSTRAP_VERSION', $bsVersion);

$tjStrapperPath = JPATH_SITE . '/media/techjoomla_strapper/tjstrapper.php';

if (File::exists($tjStrapperPath))
{
	require_once $tjStrapperPath;
	TjStrapper::loadTjAssets('com_jlike');
}

$document = Factory::getDocument();
$document->addScript(Uri::base() . 'components/com_jlike/assets/scripts/jlike.js');
$document->addStyleSheet(Uri::base() . 'components/com_jlike/assets/css/like.css');
$document->addStyleSheet(Uri::root(true) . '/components/com_jlike/assets/css/jlike-tables.css');


$helperPath = JPATH_SITE . '/components/com_jlike/helper.php';

if (!class_exists('comjlikeHelper'))
{
	// Require_once $path;
	JLoader::register('comjlikeHelper', $helperPath);
	JLoader::load('comjlikeHelper');
}

$helperPath = JPATH_SITE . '/components/com_jlike/helpers/main.php';

if (!class_exists('ComjlikeMainHelper'))
{
	// Require_once $path;
	JLoader::register('ComjlikeMainHelper', $helperPath);
	JLoader::load('ComjlikeMainHelper');
}

$helperPath = JPATH_SITE . '/components/com_jlike/helpers/integration.php';

if (!class_exists('comjlikeIntegrationHelper'))
{
	// Require_once $path;
	JLoader::register('comjlikeIntegrationHelper', $helperPath);
	JLoader::load('comjlikeIntegrationHelper');
}

$helperPath = JPATH_SITE . '/components/com_jlike/helpers/socialintegration.php';

if (!class_exists('socialintegrationHelper'))
{
	// Require_once $path;
	JLoader::register('socialintegrationHelper', $helperPath);
	JLoader::load('socialintegrationHelper');
}

// Load bootstrap on joomla > 3.0 ; This option will be usefull if site is joomla 3.0 but not a bootstrap template
if (JVERSION > '3.0')
{
	$params = ComponentHelper::getParams('com_jlike');
	$load_bootstrap = $params->get('load_bootstrap');

	// Check config
	if ($load_bootstrap)
	{
		// Load bootstrap CSS.
		HTMLHelper::_('bootstrap.loadcss');
	}
}

// Load Global language constants to in .js file
ComjlikeHelper::getLanguageConstant();

require_once JPATH_COMPONENT . '/controller.php';

// Require specific controller if requested
if ($controller = Factory::getApplication()->input->get('controller'))
{
	$path = JPATH_COMPONENT . '/controllers/' . $controller . '.php';

	if (file_exists($path))
	{
		require_once $path;
	}
	else
	{
		$controller = '';
	}
}

$controller = BaseController::getInstance('jlike');
$controller->execute(Factory::getApplication()->input->get('task'));
$controller->redirect();
