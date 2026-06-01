<?php
/**
 * @package    Shika_Document_Viewer
 * @copyright  Copyright (C) 2009 -2010 Techjoomla, Tekdi Web Solutions . All rights reserved.
 * @license    GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link       http://www.techjoomla.com
 */

// Check to ensure this file is within the rest of the framework
defined('JPATH_BASE') or die();
use Joomla\CMS\Form\FormField;

/**
 * JFormFieldPathapi
 *
 * @since  1.0.0
 */
class FormFieldMigration extends FormField
{
	/**
	 * The form field type.
	 *
	 * @var		string
	 * @since	1.6
	 */
	public $type = 'Migration';

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
		JLoader::import('plugins.tjdocument.boxapi2.classes.migration', JPATH_SITE);

		$migrate = new BoxApiMigration;

		$canMigrate = $migrate->canMigrate();

		if (!$canMigrate)
		{
			return null;
		}

		$html = $migrate->displayMigrateButton();

		return $html;
	}
}
