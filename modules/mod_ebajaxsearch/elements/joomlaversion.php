<?php 
/**
 * @package Module EB Ajax Search for Joomla!
 * @version 1.39: mod_ebajaxsearch.php Dec 2023
 * @author url: https://www/extnbakers.com
 * @copyright Copyright (C) 2022 extnbakers.com. All rights reserved.
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
**/
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');
class JFormFieldJoomlaversion extends JFormField {
	protected $type = 'Joomlaversion';
	
	public function getInput() { // added class hidden in fieldset
	
		$document = JFactory::getDocument();
		$document->addScriptDeclaration('
		
		setTimeout(
			function(){				
				jQuery("#joomlaversion_id").parent().parent().hide();				
		}, 1000);
	 
			
		');
		
		$dot_seprated = explode('.', JVERSION);	
		$return12 = '<input type="radio" id="joomlaversion_id" name="'.$this->name.'" value="'.$dot_seprated[0].'" checked >';
		return $return12;
		
		
	}
}