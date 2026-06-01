<?php
/**
 * @package     Joomla.Site
 * @subpackage  Com_Advsearch
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (c) 2009-2017 TechJoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license     GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>.
 * @link        http://techjoomla.com.
 */

defined('JPATH_BASE') or die;

jimport('joomla.html.html');
jimport('joomla.form.formfield');

/**
 * Class Supports an HTML select list of categories
 *
 * @since  3.3
 */
class JFormFieldCustom_Field extends JFormField
{
	/**
	 * The form field type.
	 *
	 * @var		string
	 * @since	1.6
	 */
	protected $type = 'text';

	/**
	 * Method to get the field input markup.
	 *
	 * @return	string	The field input markup.
	 *
	 * @since	1.6
	 */
	protected function getInput()
	{
		// Initialize variables.
		$html = array();

		return implode($html);
	}
}
