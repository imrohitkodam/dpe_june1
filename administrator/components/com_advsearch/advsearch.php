<?php
/**
 * @package     Joomla.Site
 * @subpackage  Com_Advsearch
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (c) 2009-2017 TechJoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license     GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>.
 * @link        http://techjoomla.com.
 */

// No direct access
defined('_JEXEC') or die;

// Access check.
if (!JFactory::getUser()->authorise('core.manage', 'com_advsearch'))
{
	throw new Exception(JText::_('JERROR_ALERTNOAUTHOR'));
}

// Include dependancies
jimport('joomla.application.component.controller');
$path = JPATH_COMPONENT . '/classes/' . 'common.php';

if (!class_exists('AdvsearchHelper'))
{
	// Require_once $path;
	JLoader::register('AdvsearchHelper', $path);
	JLoader::load('AdvsearchHelper');
}

$controller = JControllerLegacy::getInstance('Advsearch');
$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();
