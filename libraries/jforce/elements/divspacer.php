<?php
/**
 * @version     1.0.0
 * @package     mod_cookienotificationsbuilder
 * @copyright   Copyright (C) 2014. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      JoomlaForce Team <support@joomlaforce.com> - http://www.joomlaforce.com
 */

defined('JPATH_BASE') or die;

jimport('joomla.html.html');
//jimport('joomla.form.fields.spacer');
require_once(JPATH_ROOT.'/libraries/joomla/form/fields/spacer.php');

/**
 * Extent the Spacer element of Joomla
 */
class JFormFieldDivSpacer extends JFormFieldSpacer {

	/**
	 * The form field type.
	 *
	 * @var    string
	 */
	protected $type = 'divspacer';

	/**
	 * Return a blank div instead of blank string for better usage.
	 *
	 * @return  string  The field input markup.
	 *
	 * @since   11.1
	 */
	protected function getInput() {

		$html	= '<div id="'.$this->id.'" style="background-color:#ccc; height:2px; width:100%;"></div>';
		
		return $html;
	}

	/**
	 * Override getLabel function for hiding Label when don't need.
	 * 
	 * @return string	Label if label element is set, blank otherwise
	 */
	protected function getLabel_() {
		if($this->element['label']) {
			return parent::getLabel();
			
		} else {
			return '';
		}
	}
}