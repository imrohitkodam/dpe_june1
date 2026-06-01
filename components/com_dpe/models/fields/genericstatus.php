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

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

FormHelper::loadFieldClass('list');

/**
 * Supports an HTML select list of generic status
 *
 * @since  __DEPLOY_VERSION__
 */

class JFormFieldGenericStatus extends \JFormFieldList

{
	/**
	 * The form field type.
	 *
	 * @var        string
	 * @since    1.0.0
	 */
	protected $type = 'genericstatus';

	/**
	 * Method to get the field input markup.
	 *
	 * @return    string    The field input markup.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	protected function getOptions()
	{
		// Initialize array to store dropdown options
		$options = array();

		$view = Factory::getApplication()->input->get('view', '', 'STRING');

		if ($view === 'schools')
		{
			$options[] = HTMLHelper::_('select.option', "", Text::_('COM_DPE_GENERICSTATUS_SELECT_STATUS_OPTION'));
		}

		if ($view === 'slaactivities')
		{
			$options[] = HTMLHelper::_('select.option', "all", Text::_('COM_DPE_GENERICSTATUS_ALL_STATUS_OPTION'));
		}

		// Value 1 used for active status
		$options[] = HTMLHelper::_('select.option', 1, Text::_('COM_DPE_GENERICSTATUS_ACTIVE_STATUS_OPTION'));

		// Value 2 used for archived status
		$options[] = HTMLHelper::_('select.option', 2, Text::_('COM_DPE_GENERICSTATUS_ARCHIVED_STATUS_OPTION'));

		if ($view === 'schools' || $view === 'slaactivities')
		{
			// Value 3 used for upcoming status
			$options[] = HTMLHelper::_('select.option', 3, Text::_('COM_DPE_GENERICSTATUS_UPCOMING_STATUS_OPTION'));
		}

		if($view === 'schools'){

			// Value 4 used for No active licence status
			$options[] = HTMLHelper::_('select.option', 4, Text::_('COM_DPE_GENERICSTATUS_NO_ACTIVE_STATUS_OPTION'));
		}

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
