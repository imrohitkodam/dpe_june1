<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */
defined('_JEXEC') or die;

if (!class_exists('Koowa'))
{
	$error = sprintf(JText::_('JOOMLATOOLS_FRAMEWORK_ERROR'), JRoute::_('index.php?option=com_plugins&view=plugins&filter_folder=system'));

    $app = \Joomla\CMS\Factory::getApplication();
    $app->enqueueMessage($error, 'error');
    return $app->redirect(JURI::base());
}

KObjectManager::getInstance()->getObject('com://admin/logman.dispatcher.http')->dispatch();
