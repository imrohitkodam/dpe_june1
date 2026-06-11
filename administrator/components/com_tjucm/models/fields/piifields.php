<?php
/**
 * @package     TJ-UCM
 * @subpackage  com_tjucm
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access.
defined('_JEXEC') or die();

use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Form\Field\ListField;

JFormHelper::loadFieldClass('list');

/**
 * Custom field to list fields for the UCM content type for PII redaction selection.
 */
class JFormFieldPiiFields extends jFormFieldList
{
	public $type = 'piifields';

	/**
	 * Function to get options list (custom fields for the current UCM type)
	 *
	 * @return  array
	 */
	public function getOptions()
	{
		$options = array();
		$unique_identifier = $this->form->getValue('unique_identifier');

		if (empty($unique_identifier)) {
			return $options;
		}

		$db = Factory::getDbo();
		$query = $db->getQuery(true)
			->select($db->quoteName(array('name', 'title')))
			->from($db->quoteName('#__tjfields_fields'))
			->where($db->quoteName('client') . ' = ' . $db->quote($unique_identifier))
			->where($db->quoteName('state') . ' = 1')
			->order($db->quoteName('title') . ' ASC');

		$db->setQuery($query);
		$fields = $db->loadObjectList();

		if (!empty($fields)) {
			foreach ($fields as $field) {
				$title = $field->title ? strip_tags(Text::_($field->title)) : $field->name;
				$options[] = HTMLHelper::_('select.option', $field->name, $title);
			}
		}

		return $options;
	}
}
