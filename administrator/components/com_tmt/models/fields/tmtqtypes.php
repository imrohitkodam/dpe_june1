<?php
/**
 * @package     TMT
 * @subpackage  com_shika
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_BASE') or die;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Form\Field\ListField;

/**
 * Supports an HTML select list of question types
 *
 * @since  1.0
 */
class JFormFieldTmtqtypes extends ListField
{
	/**
	 * The form field type.
	 *
	 * @var  string
	 * @since  1.0
	 */
	protected $type = 'tmtqtypes';

	/**
	 * Method to get a list of options for a list input.
	 *
	 * @return  array       An array of JHtml options.
	 *
	 * @since   1.0
	 */
	protected function getOptions()
	{
		$options = array();

		$options = TMT::Utilities()->questionTypes(true);

		// Merge any additional options in the XML definition.
		$options = array_merge(parent::getOptions(), $options);

		return $options;
	}
}
