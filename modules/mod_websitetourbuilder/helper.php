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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;

//Pass Language to Javascript File in frontend
JText::script('MOD_WEBSITE_CLICKTOCLOSE');


class modWebsiteTourHelper {

static function getList($params) 
	{				
		return null;
	}
    
    static function getJavascript($class_suffix, $jquery_var) 
    {    	
    	$js = $jquery_var.'(document).ready(function() {
    			'.$jquery_var.'("#message_lib_'.$class_suffix.'").removeClass("alert-error");
    			'.$jquery_var.'("#message_lib_'.$class_suffix.'").addClass("alert-success");
    			'.$jquery_var.'("#message_lib_'.$class_suffix.'").html("'.Text::_('MOD_WEBSITETOUR_JQUERY_INFO_SUCCESS').'");
    			'.$jquery_var.'("#message_lib_'.$class_suffix.'").append(" jQuery v" + gQuery.fn.gquery);
    		});
    	';
    	
    	return $js;
    }
	
	static function getItems(&$params) {

		$items = json_decode(str_replace("|qq|", "\"", $params->get('slides')));
		foreach ($items as $i => $item) {
			if (!$item->stepcontent) {
				unset($items[$i]);
				continue;
			}
		}
		return $items;
	}

	static function DoScripts($items,$params,$jquery_var){

		//print_r($items);
		$document = Factory::getDocument();
		$uri = JURI::root();
        $popupTheme = $params->get('popupTheme','default');
        $popupThemeCSSPath = JPATH_SITE.'/modules/mod_websitetourbuilder/theme/popup/'.$popupTheme.'.css';
        if(is_file($popupThemeCSSPath))
        {
            $document->addStyleSheet( $uri.'modules/mod_websitetourbuilder/theme/popup/'.$popupTheme.'.css' );
        }
        else
        {
            $document->addStyleSheet( $uri.'modules/mod_websitetourbuilder/assets/css/jquery.gt_websitetour_1.2.css' );
        }

		$document->addStyleSheet( $uri.'modules/mod_websitetourbuilder/assets/css/style.css' );
        $document->addScript($uri.'modules/mod_websitetourbuilder/assets/js/gquery-1.7.2.js');
		$document->addScript($uri.'modules/mod_websitetourbuilder/assets/js/jquery.gt_websitetour_1.2.js');
        
		if($params->get('show_timer_controls',0))
            $document->addScript($uri.'modules/mod_websitetourbuilder/assets/js/jquery.gotour.chrony.js');
	
		$doscript = modWebsiteTourHelper::DoSteps($items,$params,$jquery_var);
		$document->addScriptDeclaration($doscript);
		//return $doscripts;

	}

	

	static function DoSteps($items,$params,$jquery_var)

	{

		//print_r($items);
		//require_once 'modules/mod_websitetourbilder/helpers/steps.php';	
		require_once JPATH_SITE.'/libraries/jforce/helpers/websitetourbuilder.php';	
		$createtour =  DoTourHelper::CreateTour($items,$params,$jquery_var);
		//$document = JFactory::getDocument();
		//$document->addScriptDeclaration ($createtour);
		
		return $createtour;

	}

}

