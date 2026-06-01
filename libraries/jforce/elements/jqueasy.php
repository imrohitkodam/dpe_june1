<?php
/**
 * @copyright	Copyright (C) 2014 JoomlaForceTeam. All rights reserved.
 * @license		GNU General Public License version 3 or later; see LICENSE.txt
 */

// no direct access
defined('_JEXEC') or die ;

jimport('joomla.form.formfield');
jimport('joomla.version');
jimport('joomla.filesystem.folder');


class JFormFieldJqueasy extends JFormField {
	
		
	public $type = 'jqueasy';
	
	/**
	 * Method to get the field options.
	 * @return	array	The field option objects.
	 * @since	1.6
	 */
	protected function getLabel() {
				
		$html = '';
		
		$html .= '<div style="clear: both;"></div>';
		
		return $html;
	}

	/**
	 * Method to get the field input markup.
	 * @return	string	The field input markup.
	 * @since	1.6
	 */
	protected function getInput() {
		
		JFactory::getLanguage()->load('lib_jforce.sys', JPATH_SITE, 'en-GB', true);
		$style="";
		//$style="border: 1px solid #BCE8F1; background-color: #D9EDF7; color: #3A87AD";
		$html  = '<div style="'.$style.'">';
		$html .= '<span>'.JText::_('MOD_WEBSITETOUR_JQEASY');
		$html .= '</span>';
		$html .= '</div>';

		
		return $html;
	}

}
?>