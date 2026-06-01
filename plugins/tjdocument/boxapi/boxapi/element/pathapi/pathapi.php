<?php
/**
 * @package    Shika_Document_Viewer
 * @copyright  Copyright (C) 2009 -2010 Techjoomla, Tekdi Web Solutions . All rights reserved.
 * @license    GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link       http://www.techjoomla.com
 */

// Check to ensure this file is within the rest of the framework
defined('JPATH_BASE') or die();
use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;

jimport("joomla.html.parameter.element");
jimport('joomla.html.html');
jimport('joomla.form.formfield');

$lang = Factory::getLanguage();
$lang->load('plg_tjdocument_boxapi', JPATH_ADMINISTRATOR);

/**
 * JFormFieldPathapi
 *
 * @since  1.0.0
 */
class FormFieldPathapi extends FormField
{
	/**
	 * The form field type.
	 *
	 * @var		string
	 * @since	1.6
	 */
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
			$return	= '<div class="instructions control-label">
					Go to <a href="https://techjoomla.com/documentation-for-shika-lms-for-joomla/box-api"
					target="_blank">How to configure Box API</a><br />
					</div>';
		}

		return $return;
	}
}
