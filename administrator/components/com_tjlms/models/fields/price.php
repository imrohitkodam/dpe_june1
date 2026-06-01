<?php
/**
 * @package     Shika
 * @subpackage  com_tjlms
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_BASE') or die;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\Component\ComponentHelper;

jimport('joomla.form.formfield');

/**
 * Supports an HTML price with currency symbol
 *
 * @since  1.3.31
 */
class JFormFieldPrice extends JFormFieldText
{
	/**
	 * The form field type.
	 *
	 * @var		string
	 * @since	1.3.31
	 */
	protected $type = 'price';

	/**
	 * Method to get the field input markup.
	 *
	 * @return	string	The field input markup.
	 *
	 * @since	1.3.31
	 */
	protected function getInput()
	{
		$input = parent::getInput();

		$comtjlmsHelper = new ComtjlmsHelper;
		$currSymbol     = $comtjlmsHelper->getCurrencySymbol();
		$params         = ComponentHelper::getParams('com_tjlms');

		$currencyDisplayFormat = $params->get('currency_display_format');
		$currFormatPosition    = strpos($currencyDisplayFormat, '{CURRENCY_SYMBOL}');

		if ($currFormatPosition == 0)
		{
			$posClass = (JVERSION >= '4.0.0') ? 'input-group' : 'input-prepend';
		}
		else
		{
			$posClass = (JVERSION >= '4.0.0') ? 'input-group' : 'input-append';
		}

		$class = (JVERSION >= '4.0.0') ? 'input-group-text' : 'add-on';

		$currencyDisplayFormatstr = str_replace('{AMOUNT}', "&nbsp;" . $input, $currencyDisplayFormat);
		$currencyFormat = $currencyDisplayFormatstr;
		$currencyDisplayFormatstr = str_replace('{CURRENCY_SYMBOL}', "&nbsp;" . "<span class='" . $class . "'>" . $currSymbol . '</span>', $currencyFormat);

		// Initialize variables.
		$html = array();

		$html[] = "<span class='" . $posClass . "'>" . $currencyDisplayFormatstr . "</span>";

		return implode($html);
	}
}
