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
use Joomla\CMS\Form\Field\TextField;

jimport('joomla.form.formfield');

/**
 * Custom field aded for coupon code
 *
 * @since  1.6
 */
class JFormFieldCode extends JFormFieldText
{
	/**
	 * The form field type.
	 *
	 * @var		string
	 * @since	1.6
	 */
	protected $type = 'code';

	/**
	 * Method to get the field input markup.
	 *
	 * @return	string	The field input markup.
	 *
	 * @since	1.6
	 */
	protected function getInput()
	{
		$input = parent::getInput();
		$this->onblur = $this->getAttribute('onblur');

		if ($this->onblur)
		{
			$onBlur = ' onblur="' . $this->onblur . '" ';
			$input = str_replace('<input', '<input ' . $onBlur, $input);
		}

		return $input;
	}
}
