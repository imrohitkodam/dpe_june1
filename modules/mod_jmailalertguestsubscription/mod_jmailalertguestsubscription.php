<?php
/**
 * @package     JmailAlert
 * @subpackage  jmailalert
 * @copyright   Copyright (C) 2009-2024 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license     GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link        http://www.techjoomla.com
 */
defined('_JEXEC') or die;

use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;



BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_jmailalerts/models/');
$model = BaseDatabaseModel::getInstance('Emails', 'JmailalertsModel');
        // Get no of count alert
$cntalert       = $model->gettotalalertcount();


if (trim($cntalert) != 0)
{
            // Creating query for concat from enable plugin for compair to user selected alert
    $qryConcat = $model->alertqryconcat();

            // Get the default alert user selected alerts or default alerts
    $defaultoption = $model->getdefaultalertid();

            // Checking user default alert id or not
    $defaultSetting = $model->isdefaultset();

            // Getting all alert created alert ids
    $altid       = $model->get_all_alertid();
}


require ModuleHelper::getLayoutPath('mod_jmailalertguestsubscription', $params->get('layout', 'default'));
