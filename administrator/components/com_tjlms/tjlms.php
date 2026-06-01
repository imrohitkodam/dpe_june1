<?php
/**
 * @package     TJLms
 * @subpackage  com_shika
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;
jimport('joomla.filesystem.file');
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Controller\BaseController;

$tjlmsParams = ComponentHelper::getParams('com_tjlms');

// Access check.
if (!Factory::getUser()->authorise('core.manage', 'com_tjlms'))
{
	throw new Exception(Text::_('JERROR_ALERTNOAUTHOR'));
}

if (!defined('DS'))
{
	define('DS', '/');
}

global $wrapperDiv;

/* define wrapper div*/
if (JVERSION < '3.0')
{
	define('COM_TJLMS_WRAPPER_DIV', 'techjoomla-bootstrap tjlms-wrapper  row-fluid');
}
else
{
	define('COM_TJLMS_WRAPPER_DIV', 'tjlms-wrapper row-fluid');
}

$document = Factory::getDocument();

// Load js assets
$tjStrapperPath = JPATH_SITE . '/media/techjoomla_strapper/tjstrapper.php';

if (File::exists($tjStrapperPath))
{
	require_once $tjStrapperPath;
	TjStrapper::loadTjAssets('com_tjlms');
}

$path = JPATH_SITE . '/components/com_tjlms/helpers/courses.php';

if (!class_exists('tjlmsCoursesHelper'))
{
	JLoader::register('tjlmsCoursesHelper', $path);
	JLoader::load('tjlmsCoursesHelper');
}

$helperPath = dirname(__FILE__) . DS . 'helpers' . DS . 'tjlms.php';

if (!class_exists('TjlmsHelper'))
{
	JLoader::register('TjlmsHelper', $helperPath);
	JLoader::load('TjlmsHelper');
}

$path = JPATH_SITE . '/components/com_tjlms/helpers/' . 'tjdbhelper.php';

if (!class_exists('tjlmsdbhelper'))
{
	JLoader::register('tjlmsdbhelper', $path);
	JLoader::load('tjlmsdbhelper');
}

/*$path = JPATH_COMPONENT . '/helpers/scormlib.php';

if (!class_exists('tjlmsscormlib'))
{
	JLoader::register('tjlmsscormlib', $path);
	JLoader::load('tjlmsscormlib');
}*/

$path = JPATH_SITE . '/components/com_tjlms/helpers/' . 'main.php';

if (!class_exists('comtjlmsHelper'))
{
	JLoader::register('comtjlmsHelper', $path);
	JLoader::load('comtjlmsHelper');
}

include_once  JPATH_ADMINISTRATOR . '/components/com_tjlms/includes/tjlms.php';
$path = JPATH_SITE . '/components/com_tjlms/helpers/' . 'tracking.php';

if (!class_exists('comtjlmstrackingHelper'))
{
	JLoader::register('comtjlmstrackingHelper', $path);
	JLoader::load('comtjlmstrackingHelper');
}

$options['relative'] = true;
JHtml::_('script', 'com_tjlms/tjService.js', $options);
JHtml::_('script', 'com_tjlms/common.js', $options);
JHtml::_('script', 'com_tjlms/tjlmsAdmin.js', $options);
JHtml::script(Uri::root() . 'administrator/components/com_tjlms/assets/js/ajax_file_upload.js');

// Define batch size.
define('COM_TJLMS_BATCH_SIZE_FOR_AJAX', $tjlmsParams->get('batch_size', '10'));
$document->addScriptDeclaration(' var batchsize = ' . COM_TJLMS_BATCH_SIZE_FOR_AJAX);

// Include dependancies
jimport('joomla.application.component.controller');
$controller = BaseController::getInstance('Tjlms');
$controller->execute(Factory::getApplication()->input->get('task'));
$controller->redirect();
