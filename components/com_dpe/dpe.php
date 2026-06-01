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
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Factory;

// Register class prefix
JLoader::registerPrefix('Dpe', JPATH_COMPONENT);

// Load the controller
JLoader::import('components.com_dpe.includes.dpe', JPATH_SITE);
JLoader::import("/components/com_subusers/includes/rbacl", JPATH_ADMINISTRATOR);
DPE::utilities()->getLanguageConstant();

$controller	= BaseController::getInstance('Dpe');
$controller->execute(Factory::getApplication()->input->get('task'));
$controller->redirect();

JLoader::import('components.com_dpe.includes.dpe', JPATH_SITE);
JLoader::import("/components/com_subusers/includes/rbacl", JPATH_ADMINISTRATOR);
