<?php
/**
 * @package     SLA
 * @subpackage  sla_activity
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2019 - 2019 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

jimport('joomla.form.formfield');
use Joomla\CMS\Factory;

/**
 * Legend field for component params.
 *
 * @package  Sla_Activity
 * @since    __DEPLOY_VERSION__
 */
class JFormFieldLegend extends JFormField
{
	/**
	 * The form field type.
	 *
	 * @var		string
	 * @since	__DEPLOY_VERSION__
	 */
	protected $type = 'Legend';

	/**
	 * Method to get the field input markup.
	 *
	 * @return   string  The field input markup.
	 *
	 * @since    __DEPLOY_VERSION__
	 */
	public function getInput()
	{
		$document    = Factory::getDocument();
		$legendClass = 'sla-elements-legend';
		$hintClass   = "sla-elements-legend-hint";
		$hint        = $this->hint;
		$script      = 'jQuery(document).ready(function(){
			jQuery("#' . $this->id . '").parent().removeClass("controls");
			jQuery("#' . $this->id . '").parent().parent().removeClass("control-group");
		});';

		$document->addScriptDeclaration($script);

		// Show them a legend.
		$return = '<legend class="clearfix ' . $legendClass . '" id="' . $this->id . '">' . JText::_($this->value) . '</legend>';

		// Show them a hint below the legend.
		// Let them go - GaGa about the legend.
		if (!empty($hint))
		{
			$return .= '<span class="disabled ' . $hintClass . '">' . JText::_($hint) . '</span>';
			$return .= '<br/><br/>';
		}

		return $return;
	}
}
