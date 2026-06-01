<?php
/**
 * @package    Shika
 * @author     TechJoomla | <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

// No direct access
defined('JPATH_BASE') or die();
use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;

jimport("joomla.html.parameter.element");
jimport('joomla.html.html');
jimport('joomla.form.formfield');

$lang = Factory::getLanguage();
$lang->load('plug_boxapi', JPATH_ADMINISTRATOR);

/**
 * JFormFieldPathapi
 *
 * @since  1.0
 */
class FormFieldPathapi extends FormField
{
	public $type = 'Pathapi';

	/**
	 * Method to get the field input markup.
	 *
	 * TODO: Add access check.
	 *
	 * @return	string	The field input markup.
	 *
	 * @since	1.0.0
	 */
	protected function getInput()
	{
		if ($this->id == 'jform_params_pathapi_boxapi')
		{
			$link = "http://techjoomla.com/documentation-for-invitex/configuring-clickatell-api-plugin.html";
			$return	= "<div class="instructions">
					<a href='" . $link . "' target='_blank'>How to configure Box API</a><br />
					</div>";

			return $return;
		}
	}
}
