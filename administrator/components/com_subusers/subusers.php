<?php
/**
 * @package    Subusers
 *
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2018 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;

// Access check.
if (!Factory::getUser()->authorise('core.manage', 'com_subusers'))
{
	throw new \Exception(Text::_('JERROR_ALERTNOAUTHOR'));
}

\JLoader::import("/components/com_subusers/includes/rbacl", JPATH_ADMINISTRATOR);

$controller = BaseController::getInstance('Subusers');
$controller->execute(Factory::getApplication()->input->get('task'));
$controller->redirect();
