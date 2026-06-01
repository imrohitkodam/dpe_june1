<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Tjlms
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

defined('JPATH_BASE') or die;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Form\Field\ListField;

JFormHelper::loadFieldClass('list');

/**
 * JFormFieldSubuserfilter helper.
 *
 * @since  1.1.8
 */
class JFormFieldSubuserfilter extends JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var		string
	 * @since	1.6
	 */
	protected $type = 'subuserfilter';

	/**
	 * Method to get a list of options for a list input.
	 *
	 * @return	array		An array of JHtml options.
	 *
	 * @since   11.4
	 */
	protected function getInput()
	{
		$hasUsers = $this->getOptions();

		if (!$hasUsers)
		{
			return null;
		}

		$input = parent::getInput();

		// Only 1 option then hide dropdown by jlike and shika classes
		if (count($hasUsers) == 1)
		{
			$input = '<div class="jlike_display_none tjlms_display_none">' . $input . '</div>';
		}

		return $input;
	}

	/**
	 * Method to get the field options.
	 *
	 * @return  array  The field option objects.
	 *
	 * @since   3.7.0
	 */
	protected function getOptions()
	{
		JLoader::import('administrator.components.com_tjlms.helpers.tjlms', JPATH_SITE);
		$hasUsers = TjlmsHelper::getSubusers();

		// If not manager, we do not need to show dropdown
		if (!$hasUsers)
		{
			return null;
		}

		$options 	= array();
		$options[] 	= JHTML::_('select.option', '', Text::_('COM_TJLMS_FILTER_SUBUSERFILTER'));
		$options[] 	= JHTML::_('select.option', 1, Text::_('COM_TJLMS_FILTER_SUBUSERFILTER_UNDER_ME'));

		return $options;
	}
}
