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


class JFormFieldJForceheader extends JFormField {
	
		
	public $type = 'jforceheader';
	
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
		
		JFactory::getLanguage()->load('lib_jforce.sys', JPATH_SITE, 'en-GB', true);
		$style="background:#eee;padding:5px;margin:3px 0;";	
		//echo "<img src='../media/jforce/img/logo.png' style='float:top !important;' />";
		echo '<div style="'.$style.'">';
		echo '<span>'.JText::_('JFORCE_HEADER');
		//Fan
		//echo '<img style="float:left;" src="../libraries/jforce/elements/assets/images/supportus.png">';
		echo '<iframe src="//www.facebook.com/plugins/likebox.php?href=https%3A%2F%2Fwww.facebook.com%2Fjoomlaforceteam&amp;width=200&amp;height=62&amp;colorscheme=light&amp;show_faces=false&amp;header=false&amp;stream=false&amp;show_border=true&amp;appId=437825646345725" scrolling="no" frameborder="0" style="border:none; overflow:hidden; width:200px; height:62px;" allowTransparency="true"></iframe>';
		
		//Like
		//echo '<iframe src="//www.facebook.com/plugins/like.php?href=http%3A%2F%2Fjoomlaforce.com&amp;width=100&amp;layout=button&amp;action=like&amp;show_faces=false&amp;share=true&amp;height=35&amp;appId=437825646345725" scrolling="no" frameborder="0" style="border:none; overflow:hidden; width:160px; height:35px;" allowTransparency="true"></iframe>';
		
		
		echo '</span>';
		echo '</div>';

		return ;
	}

}
?>