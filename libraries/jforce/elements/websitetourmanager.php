<?php

/**
 * @copyright	Copyright (C) 2014 JoomlaForceTeam. All rights reserved.
 * @license		GNU General Public License version 3 or later; see LICENSE.txt
 */
 
// no direct access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Language\Text;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Version;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;


HTMLHelper::_('bootstrap.tooltip');

//Pass Language to Javascript File in Backend Options
Text::script('MOD_WEBSITE_ADDSTEPS');
Text::script('MOD_WEBSITETOUR_REMOVE');
Text::script('MOD_WEBSITETOUR_STEP_TYPE');
Text::script('MOD_WEBSITETOUR_STEP_WRAPPERTYPE');
Text::script('MOD_WEBSITETOUR_STEP_WRAPPERID');
Text::script('MOD_WEBSITETOUR_STEP_POSITION');
Text::script('MOD_WEBSITETOUR_STEP_WIDTH');
Text::script('MOD_WEBSITETOUR_STEP_DRAGGABLE');
Text::script('MOD_WEBSITETOUR_STEP_ROTATION');
Text::script('MOD_WEBSITETOUR_STEP_ROTATION_DESC');
Text::script('MOD_WEBSITETOUR_STEP_REDIRECT');
Text::script('MOD_WEBSITETOUR_STEP_TIME');
Text::script('MOD_WEBSITETOUR_STEP_TITLE');
Text::script('MOD_WEBSITETOUR_STEP_TEXT');



class FormFieldWebsitetourmanager extends FormField {

    protected $type = 'websitetourmanager';

    protected function getInput() {
        $version = new Version();
		$jversion = explode('.', $version->getShortVersion());
		$jversion3 = false;
		if (intval($jversion[0]) > 2) {
			$jversion3=true;
		}
        
        $document = Factory::getDocument();
        $document->addScriptDeclaration("JURI='" . JURI::root() . "';");
        //$path = 'modules/mod_websitetourbuilderforjomsocial/elements/websitetourmanager/';
		$path = 'libraries/jforce/elements/websitetourmanager/';
        HTMLHelper::_('bootstrap.modal','a.modal');
	
		
        if($jversion3)
        {
            $document->addScriptDeclaration("var jversion3=true;");
        }
        else
        {
            $document->addScriptDeclaration("var jversion3=false;");
        }
		
		
        HTMLHelper::_('script', $path.'websitetourmanager.js' );
        HTMLHelper::_('stylesheet', $path.'websitetourmanager.css');
		$uri = JURI::root();
		
		

        if($jversion3)
        { 
         			
			//LOAD BOOTSTRAP EDITOR CSS
            $document->addStyleSheet( $uri.'libraries/jforce/elements/editor/lib/css/bootstrap.min.css' );
    		$document->addStyleSheet( $uri.'libraries/jforce/elements/editor/lib/css/prettify.css' );
    		$document->addStyleSheet( $uri.'libraries/jforce/elements/editor/src/bootstrap-wysihtml5.css' );
            //END BOOTSTRAP EDITOR CSS
            
    		//***** LOAD BOOTSTRAP EDITOR JS ***********
    		$document->addScript($uri.'libraries/jforce/elements/editor/lib/js/wysihtml5-0.3.0.js');
    		$document->addScript($uri.'libraries/jforce/elements/editor/lib/js/prettify.js');
    		$document->addScript($uri.'libraries/jforce/elements/editor/src/bootstrap-wysihtml5.js');
    		//END LOAD BOOTSTRAP EDITOR JS
			
		
			//jimport ('joomla.html.html.bootstrap');
			
			
        }
        $html = '<input name="' . $this->name . '" id="jftoursstep" type="hidden" value="' . $this->value . '" />'
                . '<input name="jfaddsteps" id="jfaddsteps" type="button" value="' . Text::_('MOD_WEBSITE_ADDSTEPS') . '" onclick="javascript:addstepstour();"/>'
                //.'<input name="ckstoreslide" id="ckstoreslide" type="button" value="Save" onclick="javascript:storesteptour();"/>'
                . '<ul id="jfsteplist" style="clear:both; margin:0px; padding:0px;"></ul>';	
	
        return $html;
    }


    protected function getLabel() {

        return '';
    }

   

}

