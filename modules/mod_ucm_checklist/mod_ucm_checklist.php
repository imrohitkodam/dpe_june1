<?php
/**
 * @package     DPE
 * @subpackage  mod_ucm_checklist
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\HTML\HTMLHelper;


// Include the archive functions only once
$lang = Factory::getLanguage();
$lang->load('com_dpe');
$lang->load('com_tjucm', JPATH_SITE);
HTMLHelper::_('script', 'media/system/js/fields/calendar.min.js');
HTMLHelper::StyleSheet('media/system/css/fields/calendar.min.css');

JLoader::register('TjucmHelpersTjucm', JPATH_SITE . '/components/com_tjucm/helpers/tjucm.php');
JLoader::load('TjucmHelpersTjucm');
TjucmHelpersTjucm::getLanguageConstantForJs();

$tjStrapperPath = JPATH_SITE . '/media/techjoomla_strapper/tjstrapper.php';

if (File::exists($tjStrapperPath))
{
	require_once $tjStrapperPath;
	TjStrapper::loadTjAssets('checklist');
}

$moduleclassSfx = htmlspecialchars($params->get('moduleclass_sfx'), ENT_COMPAT, 'UTF-8');
$user = Factory::getUser();

if (!$user->id)
{
	return false;
}

if (empty($params->get('ucm_type')))
{
	return false;
}

Text::script('COM_DPE_INTERACTION_AJAX_ERROR');

/**
 * Now get the record id for the user
 * We have ucm type and user id
 * Choose the latest record
 */

// Show records belonging to users cluster if com_cluster is installed and enabled - start
$clusterExist = ComponentHelper::getComponent('com_cluster', true)->enabled;
$usersClusters = array();

if ($clusterExist)
{
	JLoader::import('components.com_tjfields.tables.field', JPATH_ADMINISTRATOR);
	FormHelper::addFieldPath(JPATH_ADMINISTRATOR . '/components/com_tjfields/models/fields/');
	$cluster = FormHelper::loadFieldType('cluster', false);
	$clusterList = $cluster->getOptionsExternally();

	if (!empty($clusterList))
	{
		foreach ($clusterList as $clusterList)
		{
			if (!empty($clusterList->value))
			{
				$usersClusters[$clusterList->value] = $clusterList->text;
			}
		}
	}
}

if (empty($clusterList))
{
	return;
}

require ModuleHelper::getLayoutPath('mod_ucm_checklist', $params->get('layout', 'default'));
