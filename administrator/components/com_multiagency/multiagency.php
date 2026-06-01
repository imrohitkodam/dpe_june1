<?php
/**
 * @package     Multiagency
 * @subpackage  com_multiagency
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;

// Access check.
if (!Factory::getUser()->authorise('core.manage', 'com_multiagency'))
{
	throw new Exception(Text::_('JERROR_ALERTNOAUTHOR'));
}

JLoader::registerPrefix('Multiagency', JPATH_COMPONENT_ADMINISTRATOR);

$controller = BaseController::getInstance('Multiagency');
$controller->execute(Factory::getApplication()->input->get('task'));
$controller->redirect();
