<?php
/**
 * @package    DPE
 *
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2021 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_BASE') or die;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\HTML\HTMLHelper;

use Joomla\Registry\Registry;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Component\ComponentHelper;

FormHelper::loadFieldClass('list');

/**
 * Supports an HTML select list of custom field
 *
 * @since  __DEPLOY_VERSION__
 */

class JFormFieldAgencyFilter extends JFormFieldList

{
	/**
	 * The form field type.
	 *
	 * @var        string
	 * @since    __DEPLOY_VERSION__
	 */
	protected $type = 'agencyfilter';

	/**
	 * Fiedd to decide if options are being loaded externally and from xml
	 *
	 * @var		integer
	 * @since	__DEPLOY_VERSION__
	 */
	protected $loadExternally = 0;

	/**
	 * Method to get the field input markup.
	 *
	 * @return    string    The field input markup.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	protected function getOptions()
	{
		$params      = ComponentHelper::getParams('com_dpe');
		$customField = $params->get('agencyFilterField', '0');


		 $db = Factory::getDbo();
		 Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_fields/Table/');

		 $fieldsValueTable   = new \Joomla\Component\Fields\Administrator\Table\FieldTable($db);
         $fieldsValueTable->load(array('id' => $customField));

		// Initialize array to store dropdown options
		$options   = array();

		if (property_exists($fieldsValueTable, 'fieldparams'))
		{
			$fieldParams = new Registry($fieldsValueTable->fieldparams);

			foreach ($fieldParams['options'] as $fieldParam)
			{
				if ($fieldParam->value)
				{
					$options[] = HTMLHelper::_('select.option', $fieldParam->value, $fieldParam->name);
				}
			}
		}

		$options[] = HTMLHelper::_('select.option', "all", Text::_('COM_DPE_AGANCY_FILTER_ALL_OPTION'));
		$options[] = HTMLHelper::_('select.option', "none", Text::_('COM_DPE_AGANCY_FILTER_NONE_OPTION'));

		return $options;
	}

	/**
	 * Method to get a list of options for a list input externally and not from xml.
	 *
	 * @return	array	An array of HTMLHelper options.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function getOptionsExternally()
	{
		$this->loadExternally = 1;

		return $this->getOptions();
	}
}
