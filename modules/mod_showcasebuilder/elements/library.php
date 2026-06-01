<?php
/**
 * @copyright	Copyright (C) 2014 JoomlaForce, Inc. All rights reserved.
 * @license		GNU General Public License version 3 or later; see LICENSE.txt
 */

// no direct access
defined('_JEXEC') or die ;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Language\Text;

jimport('joomla.form.formfield');
jimport('joomla.version');
jimport('joomla.filesystem.folder');


class FormFieldLibrary extends FormField {
	
		
	public $type = 'library';
	static $minimum_needed_library_version = '1.0.0';
	static $download_link = 'http://www.joomlaforce.com'; // full URL link to the library

	/**
	 * Method to get the field options.
	 *
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
	 *
	 * @return	string	The field input markup.
	 * @since	1.6
	 */
	protected function getInput() {
		
		//JFactory::getLanguage()->load('lib_jforce.sys', JPATH_SITE, 'en-GB', true);	
		$style = 'margin: 5px 0; padding: 8px 35px 8px 14px; border-radius: 4px; border: 1px solid #EED3D7; background-color: #F2DEDE; color: #B94A48;';
		
		//Check if Library is enabled since J32+
		//echo JLibraryHelper::isEnabled('jforce');
		
		if (!Folder::exists(JPATH_ROOT.'/libraries/jforce')) {			
			echo '<div style="'.$style.'">';
			echo '    <span>'.Text::_('JFORCE_MISSING_LIBRARY').'</span><br />';
			echo '    <a href="'.self::$download_link.'" target="_blank">'.Text::_('JFORCE_DOWNLOAD_LIBRARY').'</a>';
			echo '</div>';
		} else {
			jimport('jforce.version');			
			if (JForceVersion::isCompatible(self::$minimum_needed_library_version)) {	
		
				$style = 'margin: 5px 0; padding: 8px 35px 8px 14px; border-radius: 4px; border: 1px solid #D6E9C6; background-color: #DFF0D8; color: #468847;';
			
				echo '<div style="'.$style.'">';
				echo '    <span>'.Text::_('JFORCE_COMPATIBLE_LIBRARY').'</span>';
				echo '</div>';
			} else {
				echo '<div style="'.$style.'">';
				echo '    <span>'.Text::_('JFORCE_NONCOMPATIBLE_LIBRARY').'</span><br />';
				echo '    <span>'.Text::_('JFORCE_UPDATE_LIBRARY').Text::_('JFORCE_OR').'</span>';
				echo '    <a href="'.self::$download_link.'" target="_blank">'.Text::_('JFORCE_DOWNLOAD_LIBRARY').'</a>';
				echo '</div>';
			}
		}	
		
		return ;
	}

}
?>