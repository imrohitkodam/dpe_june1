<?php
/**
 * @package    Cluster
 *
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2018 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;

\JLoader::import("/components/com_cluster/includes/cluster", JPATH_ADMINISTRATOR);

$document = Factory::getDocument();
$script  = 'const root_url = "' . Juri::root() . '";';
$document->addScriptDeclaration($script, 'text/javascript');

\JLoader::registerPrefix('Cluster', JPATH_ADMINISTRATOR);
\JLoader::register('ClusterController', JPATH_ADMINISTRATOR . '/controller.php');

// Execute the task.
$controller = BaseController::getInstance('Cluster');
$controller->execute(Factory::getApplication()->input->get('task'));
$controller->redirect();
