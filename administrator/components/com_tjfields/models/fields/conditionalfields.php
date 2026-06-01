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

FormHelper::loadFieldClass('list');

/**
 * Supports an HTML select list of categories
 */
class JFormFieldConditionalfields extends JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var		string
	 * @since	1.6
	 */
	protected $type = 'conditionalfields';

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
		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		$client = Factory::getApplication()->input->get('client', '', 'STRING');
		
		// Select the required fields from the table.
		$query->select($db->qn(array('tf.id', 'tf.label')));
		$query->from('`#__tjfields_fields` AS tf');
		$query->where($db->quoteName('tf.state') . ' = ' . $db->quote('1'));

		if ($client)
		{
			$query->where($db->quoteName('tf.client') . ' = ' . $db->quote($client));
		}

		$query->order($db->escape('tf.ordering ASC'));

		$db->setQuery($query);

		// Get all countries.
		$conditionalfields = $db->loadObjectList();

		$options = array();
		
		$options[] = HTMLHelper::_('select.option', '', Text::_('COM_TJFIELDS_CONDITION_SELCET_FIELD'));
		
		foreach ($conditionalfields as $c)
		{
			$options[] = HTMLHelper::_('select.option', $c->id, $c->label);
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
