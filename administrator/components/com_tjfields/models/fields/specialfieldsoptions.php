<?php
/**
 * @package    Tjfields
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2023 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access.
defined('_JEXEC') or die();
use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;

FormHelper::loadFieldClass('list');

/**
 * Supports an HTML select list of categories
 */
class JFormFieldSpecialfieldsoptions extends JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var		string
	 * @since	1.6
	 */
	protected $type = 'specialfieldsoptions';

	/**
	 * Fiedd to decide if options are being loaded externally and from xml
	 *
	 * @var		integer
	 * @since	2.2
	 */
	protected $loadExternally = 0;

	/**
	 * Method to get a list of options for a list input.
	 *
	 * @return	array		An array of JHtml options.
	 *
	 * @since   11.4
	 */
	protected function getOptions()
	{
		$fieldOnShow = $this->form->getValue('field_on_show');

		$fieldTable = Table::getInstance('field', 'TjfieldsTable');
		$fieldTable->load((int) $fieldOnShow);
		$fieldType = $fieldTable->type;
		$isOther = json_decode($fieldTable->params)->other;

		$db = Factory::getDbo();
		$query = $db->getQuery(true);

		// Select the required fields from the table.
		$query->select('t.id, t.options');
		$query->from('`#__tjfields_options` AS t');

		if ($fieldOnShow)
		{
			$query->where('t.field_id = ' . $db->quote($fieldOnShow));
		}

		$query->order($db->escape('t.ordering ASC'));

		$db->setQuery($query);

		// Get all countries.
		$specialFieldsOptions = $db->loadObjectList();

		$options = array();
		
		$options[] = HTMLHelper::_('select.option', '', Text::_('COM_TJFIELDS_CONDITION_SELCET_FIELD_OPTIONS'));
		
		if ($fieldType == "checkbox")
		{
			$options[] = HTMLHelper::_('select.option', 'yes', "Yes");
		}
		
		foreach ($specialFieldsOptions as $c)
		{
			$options[] = HTMLHelper::_('select.option', $c->id, $c->options);
		}
		
		if ($isOther)
		{
			$options[] = HTMLHelper::_('select.option', 'tjlistothervalue', "Other");
		}

		return $options;
	}

	/**
	 * Method to get a list of options for a list input externally and not from xml.
	 *
	 * @return	array		An array of JHtml options.
	 *
	 * @since   2.2
	 */
	public function getOptionsExternally()
	{
		$this->loadExternally = 1;

		return $this->getOptions();
	}
}
