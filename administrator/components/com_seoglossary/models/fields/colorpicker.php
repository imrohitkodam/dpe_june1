<?php
/**    
 * SEO Glossary Colorpicker field
 *
 * We developed this code with our hearts and passion.
 * We hope you found it useful, easy to understand and change.
 * Otherwise, please feel free to contact us at contact@joomunited.com
 *
 * @package 	SEO Glossary
 * @copyright 	Copyright (C) 2012 JoomUnited (http://www.joomunited.com). All rights reserved.
 * @license 	GNU General Public License version 2 or later; http://www.gnu.org/licenses/gpl-2.0.html
 */

defined('_JEXEC') or die('Restricted access');

jimport('joomla.form.formfield');

/**
 * Form Field class for the Joomla Framework.
 */
class JFormFieldColorpicker extends JFormField
{
	protected $type = 'Colorpicker';

	/**
	 */
	protected function getInput()
	{
		
        $baseurl = JURI::base();
		$baseurl = str_replace('administrator/','',$baseurl);	

		$scriptname	 = $this->element['scriptpath'] ?(string) $this->element['scriptpath'] : $baseurl.'administrator/components/com_seoglossary/assets/color/jscolor.js';

		//try to find the script
		if($scriptname == 'self')
		{
           $filedir = str_replace(JPATH_SITE . '/','',dirname(__FILE__));
    	   $filedir = str_replace('\\','/',$filedir);
           $scriptname = $baseurl . $filedir . '/jscolor.js';
		}
					
		$doc = JFactory::getDocument();
		$doc->addScript($scriptname);


		return '<input type="text" name="'.$this->name.'" id="'.$this->id.'"' .
				' value="'.htmlspecialchars($this->value, ENT_COMPAT, 'UTF-8').'" class="color { hash: true }" />';
	}
}
