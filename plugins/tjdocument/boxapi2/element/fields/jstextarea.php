<?php
/**
 * @package    Shika_Document_Viewer
 * @copyright  Copyright (C) 2009 -2010 Techjoomla, Tekdi Web Solutions . All rights reserved.
 * @license    GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link       http://www.techjoomla.com
 */

// Check to ensure this file is within the rest of the framework
defined('JPATH_BASE') or die();
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Form\Field\TextareaField;

/**
 * JFormFieldParsejson
 *
 * @since  1.0.0
 */
class FormFieldJstextarea extends TextareaField
{
	/**
	 * The form field type.
	 *
	 * @var		string
	 * @since	1.6
	 */
	public $type = 'Jstextarea';

	/**
	 * Method to get the field input markup for the editor area
	 *
	 * @return  string  The field input markup.
	 *
	 * @since   1.6
	 */
	public function getInput()
	{
		$document = Factory::getDocument();
		$document->addScript(Juri::root(true) . '/plugins/tjdocument/boxapi2/assets/js/boxapi2.js?v=1.1');
		Text::script('PLG_TJDOCUMENT_BOXAPI2_INVALID_JSON');

		$result = parent::getInput();

		return $result;
	}
}
