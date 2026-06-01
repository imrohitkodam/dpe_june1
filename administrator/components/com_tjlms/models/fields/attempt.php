<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Tjlms
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

defined('JPATH_BASE') or die;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Factory;

jimport('joomla.form.formfield');

/**
 * Custom field aded for coupon code
 *
 * @since  1.6
 */
class JFormFieldAttempt extends JFormField
{
	/**
	 * The form field type.
	 *
	 * @var		string
	 * @since	1.6
	 */
	protected $type = 'attempt';

	/**
	 * Method to get the field input markup.
	 *
	 * @return	string	The field input markup.
	 *
	 * @since	1.6
	 */
	protected function getInput()
	{
		$input = Factory::getApplication()->input;

		// Initialize variables.
		$html = array();

		// Load atributes
		$this->required = $this->getAttribute('required');

		$html[] = '<input type="text" name="' . $this->name . '" id= "' . $this->id . '"  value="' . $this->value . '"';
		$html[] .= ' required="' . $this->required . '" class="validate-whole-number numberofattempts form-control"';
		$html[] .= '  filter="int"/>';

		return implode($html);
	}
}
