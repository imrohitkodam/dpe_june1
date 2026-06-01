<?php

/**
 * @package Tjlms
 * @copyright Copyright (C) 2009 -2010 Techjoomla, Tekdi Web Solutions . All rights reserved.
 * @license GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link     http://www.techjoomla.com
*/
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Plugin\CMSPlugin;

$input = Factory::getApplication()->input;

$plgType = $input->get('plgType','','STRING');
$plgName = $input->get('plgName','','STRING');
// Trigger all sub format  video plugins method that renders the video player
PluginHelper::importPlugin($plgType, $plgName);
Factory::getApplication()->triggerEvent('getPluginHTML');
