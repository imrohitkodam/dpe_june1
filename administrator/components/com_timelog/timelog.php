<?php
/**
 * @package    Com_Timelog
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;

// Access check.
if (!Factory::getUser()->authorise('core.manage', 'com_timelog'))
{
	throw new Exception(Text::_('JERROR_ALERTNOAUTHOR'));
}

// Include dependancies
JLoader::import('components.com_timelog.includes.timelog', JPATH_ADMINISTRATOR);

// Execute the task.
$controller = BaseController::getInstance('Timelog');
$controller->execute(Factory::getApplication()->input->get('task'));
$controller->redirect();
