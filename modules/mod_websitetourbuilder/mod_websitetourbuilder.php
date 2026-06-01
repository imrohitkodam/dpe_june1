<?php
/**
 * @version     1.5
 * @package     mod_websitetourbuilder
 * @copyright   Copyright (C) 2013. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      JoomlaForce Team <support@joomlaforce.com> - http://www.joomlaforce.com
 */

// no direct access
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Version;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Helper\ModuleHelper;
require_once (dirname(__FILE__).'/helper.php');
jimport('jforce.libraries');
$class_suffix = $module->id;
$doc = Factory::getDocument();
global $websiteTourModuleId;
$websiteTourModuleId = $module->id;
global $mainframe;		
$app = Factory::getApplication();
//check to see if we are in the front-end or back-end
if($app->isClient('administrator'))
{
	return; 
	//leave since we are in the back-end. bye.
}


// test which version of Joomla! is used
$version = new Version();
$jversion = explode('.', $version->getShortVersion());
$isjoomla3plus = false;
if (intval($jversion[0]) > 2) { // Joomla! 3+
	$isjoomla3plus = true;
}

// call jQuery libraries
$jquery_var = 'gQuery';
if ($isjoomla3plus) { // Joomla! 3+
	
	HTMLHelper::_('jquery.framework');
	//JHtml::_('bootstrap.framework');
	//JHtml::_('jquery.framework', true, null, false);
	$jquery_var = 'gQuery';
	JForceLibraries::loadJQueryNoConflict();
	
} else { // Joomla 2.5
	$load_jquery = $params->get('load_jquery', 0);
	if ($load_jquery > 0) {
		$jquery_version = $params->get('jquery_version', '1.8.3');
		
		JForceLibraries::loadJQuery($load_jquery == 1 ? false : true, $jquery_version);
	}
	JForceLibraries::loadJQueryNoConflict();
}

//print_r($jquery_var);
$urlPath = JURI::base()."modules/mod_websitetourbuilder/";
$doc->addStyleSheet($urlPath."style.css.php?suffix=".$class_suffix);
$doc->addScriptDeclaration(modWebsiteTourHelper::getJavascript($class_suffix, $jquery_var));


$items = modWebsiteTourHelper::getItems($params);
$scriptjs = modWebsiteTourHelper::DoScripts($items,$params,$jquery_var);

// display the module
require ModuleHelper::getLayoutPath('mod_websitetourbuilder', $params->get('layout', 'default'));
