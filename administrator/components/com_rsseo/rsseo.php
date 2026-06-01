<?php
/**
* @package RSSeo!
* @copyright (C) 2016 www.rsjoomla.com
* @license GPL, http://www.gnu.org/licenses/gpl-2.0.html
*/
defined( '_JEXEC' ) or die( 'Restricted access' );

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\MVC\Controller\BaseController;

if (!Factory::getUser()->authorise('core.manage', 'com_rsseo')) {
	throw new Exception(Text::_('JERROR_ALERTNOAUTHOR'), 404);
}

require_once(JPATH_COMPONENT.'/helpers/rsseo.php');
require_once(JPATH_COMPONENT.'/helpers/adapter/adapter.php');
require_once(JPATH_COMPONENT.'/controller.php');
HTMLHelper::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_rsseo/helpers');

// Load scripts
rsseoHelper::setScripts('administrator');
// Check for keywords config
rsseoHelper::keywords();

$controller	= BaseController::getInstance('RSSeo');
$controller->execute(Factory::getApplication()->input->get('task'));
$controller->redirect();