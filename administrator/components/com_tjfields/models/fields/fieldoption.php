<?php
	/**
	 * @version    SVN: <1.0.0>
	 * @package    Com_Tjfields
	 * @author     TechJoomla <extensions@techjoomla.com>
	 * @website    http://techjoomla.com
	 * @copyright  Copyright © 2009-2013 TechJoomla. All rights reserved.
	 * @license    GNU General Public License version 2, or later.
	 */

defined('JPATH_BASE') or die;

jimport('joomla.html.html');
jimport('joomla.form.formfield');


?>

<?php
	/**
	 * Supports an HTML select list of categories
	 *
	 * @since  1.6
	 */
class JFormFieldFieldoption extends JFormField
{
	protected $type = 'text';

	/**
	 * The form field type.
	 *
	 * @var		string
	 *
	 * @since	1.6
	 */
	public function __construct ()
	{
		parent::__construct();
		$this->countoption = 0;

		$this->tjfield_icon_plus = "icon-plus-2 ";
		$this->tjfield_icon_minus = "icon-minus-2 ";
		$this->tjfield_icon_emptystar = "icon-unfeatured";
	}

	/**
	 * Method to get the field input markup.
	 *
	 * @return	string	The field input markup.
	 *
	 * @since	1.6
	 */
	protected function getInput()
	{
		$countoption = empty($this->value) ? 0 : count($this->value);

		$k = 0;

		$html = '
		<script>var field_lenght=' . $countoption . '
			var tjfield_icon_emptystar = "icon-unfeatured";
			var tjfield_icon_minus = "icon-minus-2 ";

			function tjFieldsOptionChange(currInput){
				var optionInputs = jQuery(currInput).parent().find("input");
				var required = 0;

				jQuery.each(optionInputs, function(index, value){
					if (jQuery(value).val() != "")
					{
						required = 1;

						return false;
					}
				});

				if (required == 1)
				{
					jQuery.each(optionInputs, function(index, value){
						var name = jQuery(this).attr("name");
						
						if (name.indexOf("hidden") === -1)
						{
							jQuery(this).attr("required", true);
						}
					});
				}
				else
				{
					jQuery.each(optionInputs, function(index, value){
						jQuery(this).removeAttr("required");
						jQuery(this).removeClass("invalid");
					});
				}
			}
		</script>';

		$html .= '<div class="techjoomla-bootstrap">
			<div id="tjfield_container" class="tjfield_container" >';

		if ($this->value)
		{
			for ($k = 0;$k <= count($this->value);$k++)
			{
				$required = true;

				if ($k == count($this->value))
				{
					$required = false;
				}

				$html .= '<div id="com_tjfields_repeating_block' . $k . '"    class="com_tjfields_repeating_block span7">
							<div class="form-inline">
								' . $this->fetchOptionName(
								$this->name, (isset($this->value[$k]->options))?$this->value[$k]->options:"", $this->element, $this->options['control'], $k, $required
								) . $this->fetchOptionValue(
								$this->name, (isset($this->value[$k]->value))?$this->value[$k]->value:"", $this->element, $this->options['control'], $k, $required
								) . $this->fetchhiddenoption(
								$this->name, "", $this->element, $this->options['control'], $k
								) . $this->fetchhiddenoptionid(
								$this->name, (isset($this->value[$k]->id))?$this->value[$k]->id:"", $this->element, $this->options['control'], $k
								) . '
							</div>
						</div>';

				if ($k < count($this->value))
				{
					$html .= '<div id="remove_btn_div' . $k . '" class="com_tjfields_remove_button span3">
						<div class="com_tjfields_remove_button">
							<button class="btn btn-small btn-danger" type="button" id="remove'
							. $k . '" onclick="removeClone(\'com_tjfields_repeating_block'
							. $k . '\',\'remove_btn_div' . $k . '\');" >
											<i class="' . $this->tjfield_icon_minus
											. '"></i></button>
						</div>
					</div>';
				}
			}
		}
		else
		{
			$html .= '<div id="com_tjfields_repeating_block0" class="com_tjfields_repeating_block span7">
						<div class="form-inline">
							' . $this->fetchOptionName(
							$this->name, (isset($this->value[$k]->options))?$this->value[$k]->options:"", $this->element, $this->options['control'], $k, true
							)
							. $this->fetchOptionValue(
							$this->name, (isset($this->value[$k]->value))?$this->value[$k]->value:"", $this->element, $this->options['control'], $k, true
							)
							. $this->fetchhiddenoption(
							$this->name, "", $this->element, $this->options['control'], $k
							)
							. $this->fetchhiddenoptionid(
							$this->name, (isset($this->value[$k]->id))?$this->value[$k]->id:"", $this->element, $this->options['control'], $k
							)
							. '
						</div>
					</div>';
		}

		$html .= '<div class="com_tjfields_add_button span3">
					<button class="btn btn-small btn-success" type="button" id="add"
						onclick="addClone(\'com_tjfields_repeating_block\',\'com_tjlms_repeating_block\');"
						title=' . JText::_("COM_TJFIELDS_ADD_BUTTON") . '>
						<i class="' . $this->tjfield_icon_plus . '"></i>
					</button>
				</div>
				<div style="clear:both"></div>
				<div class="row-fluid">
				</div>
			</div>
		</div>';

		return $html;
	}

	protected $name = 'fieldoption';
	/**
	 * Method to fetch option name.
	 *
	 * @param   string   $fieldName     A new field name.
	 * @param   string   $value         A new field value.
	 * @param   string   &$node         A new field node.
	 * @param   string   $control_name  A new field control name.
	 * @param   string   $k             A new field k value.
	 * @param   boolean  $required      flag for required
	 *
	 * @return int option name.
	 *
	 * @since 1.6
	 */
	public function fetchOptionName($fieldName, $value, &$node, $control_name, $k, $required = false)
	{
		$required = ($required) ? ' required="true" ' : '';

		return $OptionName = '<input type="text" id="tjfields_optionname_' . $k .
		'"	 name="tjfields[' . $k . '][optionname]" class="tjfields_optionname " ' . $required . ' onchange="tjFieldsOptionChange(this)" placeholder="Name" value="'
		. $value . '">';
	}

	/**
	 * Method to fetch option value.
	 *
	 * @param   string   $fieldName     A new field name.
	 * @param   string   $value         A new field value.
	 * @param   string   &$node         A new field node.
	 * @param   string   $control_name  A new field control name.
	 * @param   string   $k             A new field k value.
	 * @param   boolean  $required      flag for required
	 *
	 * @return int option value.
	 *
	 * @since 1.6
	 */
	public function fetchOptionValue($fieldName, $value, &$node, $control_name, $k, $required = false)
	{
		$required = ($required) ? ' required="true" ' : '';

		return $OptionValue = '<input type="text" id="tjfields_optionvalue_' . $k .
		'" name="tjfields[' . $k . '][optionvalue]"  class="tjfields_optionvalue " ' . $required . ' onchange="tjFieldsOptionChange(this)" placeholder="Value"  value="'
		. $value . '">';
	}

	/**
	 * Method to fetch hide option.
	 *
	 * @param   string  $fieldName     A new field name.
	 * @param   string  $value         A new field value.
	 * @param   string  &$node         A new field node.
	 * @param   string  $control_name  A new field control name.
	 * @param   string  $k             A new field k value.
	 *
	 * @return int hide option.
	 *
	 * @since 1.6
	 */
	public function fetchhiddenoption($fieldName, $value, &$node, $control_name,$k)
	{
		return $hiddenoption = '<input type="hidden" id="tjfields_hiddenoption_' . $k .
		'" name="tjfields[' . $k . '][hiddenoption]"  class="tjfields_hiddenoption "  placeholder="Value"  value="'
		. $value . '">';
	}

	/**
	 * Method to fetch hide option id.
	 *
	 * @param   string  $fieldName     A new field name.
	 * @param   string  $value         A new field value.
	 * @param   string  &$node         A new field node.
	 * @param   string  $control_name  A new field control name.
	 * @param   string  $k             A new field k value.
	 *
	 * @return int hide option id.
	 *
	 * @since 1.6
	 */
	public function fetchhiddenoptionid($fieldName, $value, &$node, $control_name, $k)
	{
		return $hiddenoptionid = '<input type="hidden" id="tjfields_hiddenoptionid_' .
		$k . '" name="tjfields[' . $k . '][hiddenoptionid]"  class="tjfields_hiddenoptionid "  placeholder="Value"  value="' . $value . '">';
	}
}
