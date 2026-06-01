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

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Form\FormField;

jimport('joomla.html.html');
jimport('joomla.form.formfield');

/**
 * Supports an HTML select list of categories
 *
 * @since  1.0.0
 */
class JFormFieldReview extends JFormField
{
	protected $type = 'text';

	/**
	 * Constructor.
	 *
	 * @since   1.0.0
	 */
	public function __construct()
	{
		parent::__construct();
		$this->countoption = 0;

		if (JVERSION >= 3.0)
		{
			$this->tjlms_icon_plus      = "icon-plus-2 ";
			$this->tjlms_icon_minus     = "icon-minus-2 ";
			$this->tjlms_icon_star      = "icon-featured";
			$this->tjlms_icon_emptystar = "icon-unfeatured";
		}
		else
		{
			$this->tjlms_icon_plus      = "icon-plus ";
			$this->tjlms_icon_minus     = "icon-minus ";
			$this->tjlms_icon_star      = "icon-star";
			$this->tjlms_icon_emptystar = "icon-star-empty";
		}
	}

	/**
	 * Method to get the field input markup.
	 *
	 * @return    string    The field input markup.
	 *
	 * @since    1.0.0
	 */
	protected function getInput()
	{
		/* $formdata = $this->form->getData();
		// print_r($this->form->getData()->get('mod_id')); die('432');
		$form_id = $formdata->get('mod_id');
		if(!empty($formdata->get('id')))
		{
			$form_id .= '_'.$formdata->get('id');

		}*/

		$input = Factory::getApplication()->input;
		$form_id = $input->get('form_id', '', 'STRING');

		$countoption = count($this->value);

		if (empty($this->value))
		{
			$countoption = 0;
		}

		$k    = 0;
		$html = '';

		$html .= '<script>var length_review' . $form_id . '=' . $countoption . ';</script>';

		$html .= '<div id="assignment_review_params" class="assignment_review_params  row-fluid ">';

		if ($this->value)
		{
			for ($k = 0; $k <= count($this->value); $k++)
			{
				$p_name = $p_type = $p_value = $p_weightage = $p_desc = $review_id = '';

				if (isset($this->value[$k]) && $k != count($this->value))
				{
					$p_name = $this->value[$k]->parameter_name;
					$p_type = $this->value[$k]->parameter_type;
					$p_value = $this->value[$k]->parameter_value;
					$p_weightage = $this->value[$k]->parameter_weightage;
					$p_desc = $this->value[$k]->parameter_description;
					$review_id = $this->value[$k]->id;
				}

					$html .= '<div class="row-fluid">
					<div id="com_tjlms_repeating_block_review' . $k . '"    class="com_tjlms_repeating_block_review span9" style="margin-left:0px;">
										<div class="form-inline">
									' . $this->fetchName($this->name, (isset($p_name)) ? $p_name : "", $this->element, $this->options['control'], $k) .

										$this->fetchType($this->name, (isset($p_type)) ? $p_type : "", $this->element, $this->options['control'], $k) .

										$this->fetchValue($this->name, (isset($p_value)) ? $p_value : "", $this->element, $this->options['control'], $k) .

									$this->fetchweightage($this->name, (isset($p_weightage)) ? $p_weightage : "", $this->element, $this->options['control'], $k) .

									$this->fetchDescription($this->name, (isset($p_desc)) ? $p_desc : "", $this->element, $this->options['control'], $k) . '

									<input type="hidden" name="jform[review][' . $k . '][review_id]" value="' . $review_id . '">
									</div>
								</div>';

				if ($k < count($this->value))
				{
					$html .= '<div id="remove_btn_div' . $k . '" class=" span2"  style="padding-left:20px;" >
										<div class="com_tjlms_review_remove_button">';
					$html .= '<button class="btn btn-small btn-danger" type="button" id="remove';
					$html .= $k . '" onclick="removeCloneReview(\'com_tjlms_repeating_block_review';
					$html .= $k . '\',\'remove_btn_div' . $k . '\', \'' . $form_id . '\');" >
							<i class="' . $this->tjlms_icon_minus . '"></i></button>
						</div></div></div><br>';
				}
			}
		}
		else
		{
			$p_name = $p_type = $p_value = $p_weightage = $p_desc = $review_id = '';

			if (!empty($this->value[$k]))
			{
				$p_name = $this->value[$k]->parameter_name;
				$p_type = $this->value[$k]->parameter_type;
				$p_value = $this->value[$k]->parameter_value;
				$p_weightage = $this->value[$k]->parameter_weightage;
				$p_desc = $this->value[$k]->parameter_description;
				$review_id = $this->value[$k]->id;
			}

			$html .= '<div class="row-fluid">
			<div id="com_tjlms_repeating_block_review0"  class="com_tjlms_repeating_block_review span9">
										<div class="form-inline">
											' . $this->fetchName($this->name, (isset($p_name)) ? $p_name : "", $this->element, $this->options['control'], $k) .

											$this->fetchType($this->name, (isset($p_type)) ? $p_type : "", $this->element, $this->options['control'], $k) .

											$this->fetchValue($this->name, (isset($p_value)) ? $p_value : "", $this->element, $this->options['control'], $k) .

											$this->fetchweightage($this->name, (isset($p_weightage)) ? $p_weightage : "", $this->element, $this->options['control'], $k) .

											$this->fetchDescription($this->name, (isset($p_desc)) ? $p_desc : "", $this->element, $this->options['control'], $k) . '
											<input type="hidden" name="jform[review][' . $k . '][review_id]" value="' . $review_id . '">
										</div>
									</div>';
		}

		$html .= '<div class="com_tjlms_add_button span2">
			<button style = "margin-left: 70px;"class="btn btn-small btn-success" type="button" id="add"';
		$html .= 'onclick="addCloneReview(\'com_tjlms_repeating_block_review\',\'com_tjlms_repeating_block_review\', \'' . $form_id . '\');"';
		$html .= 'title=' . Text::_("COM_TJLMS_ADD_BUTTON") . '>
								<i class="' . $this->tjlms_icon_plus . '"></i>
							</button>
					</div>
					<div style="clear:both"></div>

				</div></div><br>';

		return $html;
	}

	/**
	 * Method to get a duration field
	 *
	 * @param   string  $fieldName     name of the field
	 * @param   string  $value         value of the field
	 * @param   string  &$node         element
	 * @param   string  $control_name  control
	 * @param   int     $k             A counter
	 *
	 * @return  string  A store id.
	 *
	 * @since	1.0.0
	 */
	public function fetchName($fieldName, $value, &$node, $control_name, $k)
	{
		$readOnly = '';

		return $field_name = '<div class="control-group">
								<div class="control-label">
									' . Text::_('COM_TJLMS_ASSESMENT_CRITERIA_NAME') . '
								</div>
								<div class="controls">
									<input type="text" id="review_name_' . $k . '" name="jform[review][' . $k . '][parameter_name]"  class="review_name "
									placeholder="Name"  ' . $readOnly . ' value="' . $value . '">
								</div>
								</div>';
	}

	/**
	 * Method to get a duration field
	 *
	 * @param   string  $fieldName     name of the field
	 * @param   string  $value         value of the field
	 * @param   string  &$node         element
	 * @param   string  $control_name  control
	 * @param   int     $k             A counter
	 *
	 * @return  string  A store id.
	 *
	 * @since	1.0.0
	 */
	public function fetchValue($fieldName, $value, &$node, $control_name, $k)
	{
		$readOnly = '';

		return $field_value = '<div class="control-group">
									<div class="control-label">
										' . Text::_('COM_TJLMS_ASSESMENT_VALUE') . '
									</div>
									<div class="controls">
									<input type="text" id="review_value_' . $k . '" name="jform[review][' . $k . '][parameter_value]"  class="review_value "
									 Onkeyup= "checkforalpha(this,46);" placeholder="Value"  ' . $readOnly . ' value="' . $value . '">
									</div>
								</div>';
	}

	/**
	 * Method to get a weightage field
	 *
	 * @param   string  $fieldName     name of the field
	 * @param   string  $value         value of the field
	 * @param   string  &$node         element
	 * @param   string  $control_name  control
	 * @param   int     $k             A counter
	 *
	 * @return  string  A store id.
	 *
	 * @since	1.0.0
	 */
	public function fetchWeightage($fieldName, $value, &$node, $control_name, $k)
	{
		$readOnly = '';

		return $field_weightage = '<div class="control-group">
								<div class="control-label">
									' . Text::_('COM_TJLMS_ASSESMENT_WEIGHTAGE') . '
								</div>
								<div class="controls">
									<input type="text" id="review_weightage_' . $k . '" name="jform[review][' . $k . '][parameter_weightage]"
									class="review_weightage validate-whole-number" Onkeyup= "checkforalpha(this,46);"
									placeholder="weightage"  ' . $readOnly . ' value="' . $value . '">
								</div>
								</div>';
	}

	/**
	 * Method to get a duration field
	 *
	 * @param   string  $fieldName     name of the field
	 * @param   string  $value         value of the field
	 * @param   string  &$node         element
	 * @param   string  $control_name  control
	 * @param   int     $k             A counter
	 *
	 * @return  string  A store id.
	 *
	 * @since	1.0.0
	 */
	public function fetchDescription($fieldName, $value, &$node, $control_name, $k)
	{
		$readOnly = '';

		return $field_desc = '<div class="control-group">
									<div class="control-label">
										' . Text::_('COM_TJLMS_ASSESMENT_DESCRIPTION') . '
									</div>
									<div class="controls">
										<textarea  id="review_description_' . $k . '" name="jform[review][' . $k . '][parameter_description]"  class="review_description "
										placeholder="Description"  ' . $readOnly . '>' . $value .
										'</textarea>
									</div>
								</div>';
	}

	/**
	 * Method to get a duration field
	 *
	 * @param   string  $fieldName     name of the field
	 * @param   string  $value         value of the field
	 * @param   string  &$node         element
	 * @param   string  $control_name  control
	 * @param   int     $k             A counter
	 *
	 * @return  string  A store id.
	 *
	 * @since	1.0.0
	 */
	public function fetchType($fieldName, $value, &$node, $control_name, $k)
	{
		$readOnly = '';
		$star = $yesno = $textinput = $checkbox = "";

		// Set selected value
		switch ($value)
		{
			case 'star' :
			$star = 'selected = "selected"';
			break;

			case 'yesno' :
			$yesno = 'selected = "selected"';
			break;

			case 'textinput' :
			$textinput = 'selected = "selected"';
			break;

			case 'checkbox' :
			$checkbox = 'selected = "selected"';
			break;

			default:
		}

		$type = '<div class="control-group">
					<div class="control-label">
						' . Text::_('COM_TJLMS_ASSESMENT_TYPE') . '
					</div>
					<div class="controls">
						<select  id="review_type_' . $k . '" name="jform[review][' . $k . '][parameter_type]"  class="review_type " ' . $readOnly . '>
						<option value="star" ' . $star . ' >Star</option>
						<option value="yesno" ' . $yesno . '>Yes No</option>
						<option value="textinput" ' . $textinput . ' >Text input</option>
						<option value="checkbox" ' . $checkbox . ' >checkbox</option>
						</select>
					</div>
				</div>';

		return $type;
	}
}
