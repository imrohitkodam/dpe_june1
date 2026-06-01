<?php
/**
 * @package     MPF
 * @subpackage  Form
 *
 * @copyright   Copyright (C) 2016 Ossolution Team, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */
defined('_JEXEC') or die;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Form\FormHelper;

FormHelper::loadFieldClass('list');


/**
 * Supports a custom field which display list of countries
 *
 * @package     Joomla.MPF
 * @subpackage  Form
 */
class JFormFieldUcmfields extends JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 */
	public $type = 'ucmfields';

	/**
	 * Method to get a list of options for a list input.
	 *
	 * @return	array		An array of HTMLHelper options.
	 *
	 * @since   1.3.39
	 */
	protected function getOptions()
	{
		HTMLHelper::script('modules/mod_ucm_data/assets/js/ucmajaxlist.min.js');

		$params = $this->form->getValue('params');

		$db    = Factory::getDbo();
		$app   = Factory::getApplication();
		$query = $db->getQuery(true);


		// Select the required fields from the table.
		$query->select('tf.id, tf.label');
		$query->from($db->qn('#__tjfields_fields', 'tf'));
		$query->where($db->qn('tf.state') . '= 1');

		if (!empty($params->ucmtypename))
		{
			$subquery = $db->getQuery(true);

			// Select the required fields from the table.
			$subquery->select('tut.unique_identifier');
			$subquery->from($db->qn('#__tj_ucm_types', 'tut'));
			$subquery->where($db->qn('tut.id') . '= ' . $params->ucmtypename);
			$db->setQuery($subquery);
			$client2 = $db->loadResult();

			$query->where($db->qn('tf.client') . ' = "' . $client2 . '"');
		}

		$query->order($db->escape('tf.ordering ASC'));
		$db->setQuery($query);

		// Get all countries.
		$allUcmfields = $db->loadObjectList();

		$options = array();
		$options[] = HTMLHelper::_('select.option', '0', Text::_('MOD_UCM_DATA_UCM_FIELDS_DEFAULT_OPTION'));

		foreach ($allUcmfields as $field)
		{
			$options[] = HTMLHelper::_('select.option', $field->id, $field->label);
		}

		return $options;
	}
}
