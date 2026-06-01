<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_tjlms
 *
 * @copyright   Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Registry\Registry;
use Joomla\CMS\HTML\HTMLHelper;
HTMLHelper::_('formbehavior.chosen', 'select.eligibilitycriteria');

jimport('joomla.form.formfield');

/**
 * Supports an HTML select list of categories
 *
 * @since  1.0.0
 */
class JFormFieldGatewayplg extends JFormField
{
	protected $type = 'Gatewayplg';

	/**
	 * Method to get the field input markup.
	 *
	 * @return   string  The field input markup.
	 *
	 * @since  1.0.0
	 */
	public function getInput()
	{
		return self::fetchElement($this->name, $this->value, $this->element, $this->options['control']);
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
	 * @since	1.0.0
	 */
	public function fetchElement($name, $value, &$node, $control_name)
	{
		$db = Factory::getDBO();

		$condtion      = array(
			0 => '\'payment\''
		);

		$condtionatype = join(',', $condtion);

		if (JVERSION >= '1.6.0')
		{
			$query = "SELECT extension_id as id,name,element,enabled as published FROM #__extensions WHERE folder in ($condtionatype) AND enabled=1";
		}
		else
		{
			$query = "SELECT id,name,element,published FROM #__plugins WHERE folder in ($condtionatype) AND published=1";
		}

		$db->setQuery($query);
		$gatewayplugin = $db->loadobjectList();

		$options = array();

		foreach ($gatewayplugin as $gateway)
		{
			$plugin = PluginHelper::getPlugin('payment', $gateway->element);

			$pluginParams = new Registry($plugin->params);
			$plgName      = $pluginParams->get('plugin_name', $gateway->element);

			$options[] = JHTML::_('select.option', $gateway->element, $plgName);
		}

		if (JVERSION >= 1.6)
		{
			$fieldName = $name;
		}
		else
		{
			$fieldName = $control_name . '[' . $name . ']';
		}

		$addedField = 'class="form-select inputbox form-control eligibilitycriteria" multiple="true"
			mode="nested"
			custom="false" size="5"';

		return JHTML::_('select.genericlist', $options, $fieldName, $addedField, 'value', 'text', $value, $control_name . $name);
	}
}
