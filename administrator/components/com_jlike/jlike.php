<?php
/**
 * @package     JLike
 * @subpackage  com_jlike
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\MVC\Controller\BaseController;

// Access check.
if (!Factory::getUser()->authorise('core.manage', 'com_jlike'))
{
	throw new Exception(Text::_('JERROR_ALERTNOAUTHOR'));
}
$language = Factory::getLanguage();
 $language->load('com_jlike', JPATH_ADMINISTRATOR);
$tjStrapperPath = JPATH_SITE . '/media/techjoomla_strapper/tjstrapper.php';

if (File::exists($tjStrapperPath))
{
	require_once $tjStrapperPath;
	TjStrapper::loadTjAssets('com_jlike');
}

$document = Factory::getDocument();
$document->addStyleSheet(JURI::root() . 'components/com_jlike/assets/css/like.css');
$document->addStyleSheet(JURI::base() . 'components/com_jlike/assets/css/like.css');
$helperPath = JPATH_SITE . '/' . 'components/com_jlike/helper.php';

if (!class_exists('comjlikeHelper'))
{
	// Require_once $path;
	JLoader::register('comjlikeHelper', $helperPath);
	JLoader::load('comjlikeHelper');
}
// Load laguage constant in javascript
ComjlikeHelper::getLanguageConstant();

$helperPath = JPATH_ADMINISTRATOR . '/' . 'components/com_jlike/helpers/jlike.php';

if (!class_exists('JLikeHelper'))
{
	// Require_once $path;
	JLoader::register('JLikeHelper', $helperPath);
	JLoader::load('JLikeHelper');
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

// Include dependancies

$controller	= BaseController::getInstance('Jlike');
$controller->execute(Factory::getApplication()->input->get('task'));
$controller->redirect();
