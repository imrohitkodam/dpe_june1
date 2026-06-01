<?php
/**
 * @version     1.1
 * @package     mod_showcasebuilder
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
//global $HelloModuleId;
$ModuleId = $module->id;

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
$jquery_var = 'jforce';
if ($isjoomla3plus) { // Joomla! 3+
	
	HTMLHelper::_('jquery.framework');
	$jquery_var = 'jforce';
	JForceLibraries::loadJQueryNoConflict();
	
} else { // Joomla 2.5
	$load_jquery = $params->get('load_jquery', 0);
	if ($load_jquery > 0) {
		$jquery_version = $params->get('jquery_version', '1.8.3');
		
		JForceLibraries::loadJQuery($load_jquery == 1 ? false : true, $jquery_version);
	}
	JForceLibraries::loadJQueryNoConflict();
}

//Parametri oggetti da visualizzare
$show_title_article = $params->get('enable_titlearticle', '1');
$show_author_article = $params->get('enable_authorarticle', '1');
$show_category_article = $params->get('enable_categoryarticle', '1');
$show_hits_article = $params->get('enable_hitsarticle', '1');
$show_description_article = $params->get('enable_descriptionarticle', '1');
$show_article_btn_more_info = $params->get('enable_articlebtnmorearticle', '1');
$hv_switch = $params->get('hv_switch', '0');

$urlPath = JURI::base()."modules/mod_showcasebuilder/";
$doc->addStyleSheet($urlPath."style.css.php?suffix=".$class_suffix);
$items ="";
$list = modShowcaseBuilderHelper::getList($params);
$scriptjs = modShowcaseBuilderHelper::DoScripts($items,$list,$params,$jquery_var,$ModuleId);

// display the module
require ModuleHelper::getLayoutPath('mod_showcasebuilder', $params->get('layout', 'default'));