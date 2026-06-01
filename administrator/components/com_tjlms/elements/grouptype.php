<?php
/**
 * @package     Shika
 * @subpackage  com_tjlms
 *
 * @copyright   Copyright (C) 2005 - 2021 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Component\ComponentHelper;

jimport('joomla.form.formfield');

/**
 * Supports an HTML select list of group types
 *
 * @since  1.3.41
 */
class JFormFieldGrouptype extends JFormField
{
	protected $type = 'grouptype';

	/**
	 * Method to get the field input markup.
	 *
	 * @return   string  The field input markup.
	 *
	 * @since  1.3.41
	 */
	public function getInput()
	{
		return $this->fetchElement($this->name, $this->value, $this->element, $this->options['control']);
	}

	/**
	 * Method to get a element
	 *
	 * @param   string  $name          Field name
	 * @param   string  $value         Field value
	 * @param   string  &$node         Node
	 * @param   string  $control_name  Controler name
	 *
	 * @return  string  A store id.
	 *
	 * @since	1.3.41
	 */
	public function fetchElement($name, $value, &$node, $control_name)
	{
		$tjlmsParams = ComponentHelper::getParams('com_tjlms');
		$integration = $tjlmsParams->get('social_integration', 'joomla');

		$options = array();

		if ($integration == 'easysocial')
		{
			$options[] = HTMLHelper::_('select.option', 1, Text::_('COM_TJLMS_EASYSOCIAL_GROUP_TYPE_PUBLIC'));
			$options[] = HTMLHelper::_('select.option', 4, Text::_('COM_TJLMS_EASYSOCIAL_GROUP_TYPE_SEMI_PUBLIC'));
			$options[] = HTMLHelper::_('select.option', 2, Text::_('COM_TJLMS_EASYSOCIAL_GROUP_TYPE_PRIVATE'));
			$options[] = HTMLHelper::_('select.option', 3, Text::_('COM_TJLMS_EASYSOCIAL_GROUP_TYPE_INVITE_ONLY'));
		}

		$addedField = 'class="inputbox form-control"';
		$fieldName = $name;

		$html = '<div id="grpTypeField">';
		$html .= HTMLHelper::_('select.genericlist', $options, $fieldName, $addedField, 'value', 'text', $value, $control_name . $name);

		$html .= '</div>';

		return $html;
	}
}
