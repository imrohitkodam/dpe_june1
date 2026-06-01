<?php
/**
 * @package     TjGoPhish
 * @subpackage  com_tjgophish
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\HTML\HTMLHelper;

// Set some global property
$document = Factory::getDocument();

// Initialise GoPhish
JLoader::register('TjGoPhishAccess', JPATH_SITE . '/components/com_tjgophish/includes/access.php');
JLoader::register('TJGOPHISH', JPATH_SITE . '/components/com_tjgophish/includes/tjgophish.php');
TJGOPHISH::init();

// Get an instance of the controller prefixed by TjGoPhish
$controller = BaseController::getInstance('TjGoPhish');

// Perform the Request task
$controller->execute(Factory::getApplication()->input->get('task'));

// Redirect if set by the controller
$controller->redirect();
