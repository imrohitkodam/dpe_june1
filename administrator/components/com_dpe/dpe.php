<?php
/**
 * @package    DPE
 *
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2018 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;

$user = Factory::getUser();

// Authorize
if (!$user->authorise('core.manage', 'com_dpe'))
{
	throw new Exception(Text::_('JERROR_ALERTNOAUTHOR'));
}

// Register class prefix
JLoader::registerPrefix('Dpe', JPATH_COMPONENT_ADMINISTRATOR);

// Load the controller

$controller	= BaseController::getInstance('Dpe');
$controller->execute(Factory::getApplication()->input->get('task'));
$controller->redirect();
