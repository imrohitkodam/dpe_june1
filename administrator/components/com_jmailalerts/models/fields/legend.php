<?php
/**
 * @package     JMailAlerts
 * @subpackage  com_jmailalerts
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2022 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// Do not allow direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

/**
 * Help
 * How to use this?
 * See the code below that needs to be added in form xml
 * Make sure, you pass a unique id for each field
 * Also pass a hint field as Help text
 *
 * <field menu="hide" type="legend" id="jma-product-display"
 * name="jma-product-display"
 * default="COM_QUICK2CART_DISPLAY_SETTINGS"
 * hint="COM_QUICK2CART_DISPLAY_SETTINGS_HINT" label="" />
 */

/**
 * Custom Legend field for component params.
 *
 * @since  2.5.0
 */
class JFormFieldLegend extends FormField
{
	/**
	 * The form field type.
	 *
	 * @var   string
	 * @since  1.6
	 */
	protected $type = 'Legend';

	/**
	 * Method to get the field input markup.
	 *
	 * @return	string	The field input markup.
	 *
	 * @since	1.5.0
	 */
	protected function getInput()
	{
		$document = Factory::getDocument();
		HTMLHelper::_('stylesheet', 'administrator/components/com_jmailalerts/assets/css/jmailalerts.css');

		$legendClass = 'jma-elements-legend';
		$hintClass   = "jma-elements-legend-hint";

		$hint = $this->hint;

		// Tada... Let's remove controls class from parent
		// And, remove control-group class from grandparent
		HTMLHelper::_('jquery.framework');
		$script = 'jQuery(document).ready(function(){
			jQuery("#' . $this->id . '").parent().removeClass("controls");
			jQuery("#' . $this->id . '").parent().parent().removeClass("control-group");
		});';

		$document->addScriptDeclaration($script);

		// Show them a legend.
		$return = '<legend class="clearfix pull-left ' . $legendClass . '" id="' . $this->id . '">' . Text::_($this->value) . '</legend>';

		// Show them a hint below the legend.
		// Let them go - GaGa about the legend.
		if (!empty($hint))
		{
			$return .= '<span class="disabled ' . $hintClass . '">' . Text::_($hint) . '</span>';
			$return .= '<br/><br/>';
		}

		return $return;
	}
}
