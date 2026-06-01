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
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

jimport('joomla.html.html');
jimport('joomla.form.formfield');

JFormHelper::loadFieldClass('list');

/**
 * Supports an HTML select list of categories
 *
 * @since  1.0.0
 */
class JFormFieldLmssubsplans extends JFormField
{
	protected $type = 'text';

	/**
	 * Constructor.
	 *
	 * @since  1.0.0
	 */
	public function __construct()
	{
		parent::__construct();
		$this->ComtjlmsHelper   = new ComtjlmsHelper;
		$this->countoption = 0;

		if (JVERSION >= 3.0)
		{
			$this->tjlms_icon_plus      = "icon-plus-2 ";
			$this->tjlms_icon_minus     = "icon-minus-2 fa fa-minus-white";
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
	 * @return   string  The field input markup.
	 *
	 * @since  1.0.0
	 */
	protected function getInput()
	{
		$input    = Factory::getApplication()->input;
		$courseId = $input->get('id');
		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjlms/models');
		$courseModel = BaseDatabaseModel::getInstance('course', 'TjlmsModel');
		$courseInfo = $courseModel->getItem($courseId);

		$countoption = count($this->value);

		if (empty($this->value))
		{
			$countoption = 0;
		}

		if (empty($this->value) && !empty($courseId))
		{
			$this->value = $courseInfo->subsplans;
		}

		$k    = 0;
		$html = '';

		if (JVERSION >= 3.0)
		{
			$html .= '

				<script>var length_subs_plan=' . $countoption . '
					var tjlms_icon_emptystar = "icon-unfeatured";
					var tjlms_icon_star = "icon-featured";
					var tjlms_icon_minus = "icon-minus-2 ";
				</script>';
		}
		else
		{
			$html .= '

				<script>var length_subs_plan=' . $countoption . '
					var tjlms_icon_emptystar = "icon-star-empty";
					var tjlms_icon_star = "icon-star";
					var tjlms_icon_minus = "icon-minus ";
				</script>';
		}

		$html .= '
				<div id="subs_plan_container" class="subs_plan_container  row-fluid form-inline">';

		if ($this->value)
		{
			for ($k = 0; $k <= count($this->value); $k++)
			{
				$cond1 = (isset($this->value[$k]->duration)) ? $this->value[$k]->duration : "";
				$cond2 = (isset($this->value[$k]->time_measure)) ? $this->value[$k]->time_measure : "";
				$cond3 = (isset($this->value[$k]->time_measure)) ? $this->value[$k]->time_measure : "";
				$cond4 = (isset($this->value[$k]->price)) ? $this->value[$k]->price : "";
				$cond5 = (isset($this->value[$k]->id)) ? $this->value[$k]->id : "";
				$html .= '<div id="com_tjlms_repeating_block' . $k . '"    class="com_tjlms_repeating_block span10">
							<div class="form-inline">
								' . $this->fetchDuration($this->name, $cond1, $this->element, $this->options['control'], $k, $cond2)
								. $this->fetchTimemeasure($this->name, $cond3, $this->element, $this->options['control'], $k)
								. $this->fetchPrice($this->name, $cond4, $this->element, $this->options['control'], $k)
								. $this->fetchPlanId($this->name, $cond5, $this->element, $this->options['control'], $k) . '
							</div>
						</div>';

				if ($k < count($this->value))
				{
					$html .= '<div id="remove_btn_div' . $k . '" class=" span2">
								<div class="com_tjlms_remove_button">
									<button class="btn btn-medium btn-danger" type="button" id="remove'
									. $k . '" onclick="removeClone(\'com_tjlms_repeating_block' . $k . '\',\'remove_btn_div' . $k . '\');" >
										<i class="' . $this->tjlms_icon_minus . '"></i></button>
								</div>
							</div>';
				}
			}
		}
		else
		{
			$cond1 = (isset($this->value[$k]->duration)) ? $this->value[$k]->duration : "";
			$cond2 = (isset($this->value[$k]->time_measure)) ? $this->value[$k]->time_measure : "";
			$cond3 = (isset($this->value[$k]->price)) ? $this->value[$k]->price : "";
			$cond4 = (isset($this->value[$k]->id)) ? $this->value[$k]->id : "";
			$html .= '<div id="com_tjlms_repeating_block0" class="com_tjlms_repeating_block span10">
						<div class="form-inline">
							' . $this->fetchDuration($this->name, $cond1, $this->element, $this->options['control'], $k, '')
							. $this->fetchTimemeasure($this->name, $cond2, $this->element, $this->options['control'], $k)
							. $this->fetchPrice($this->name, $cond3, $this->element, $this->options['control'], $k)
							. $this->fetchPlanId($this->name, $cond4, $this->element, $this->options['control'], $k) . '
						</div>
					</div>';
		}

		$html .= '<div class="com_tjlms_add_button span2">
					<button class="btn btn-medium btn-success" type="button" id="add"
					onclick="addClone(\'com_tjlms_repeating_block\',\'com_tjlms_repeating_block\');"
					title=' . Text::_("COM_TJLMS_ADD_BUTTON") . '>
						<i class="' . $this->tjlms_icon_plus . '"></i>
					</button>
				</div>
				<div style="clear:both"></div>
		</div>';

		return $html;
	}

	// Functions for each fields
	public $name = 'lmssubsplans';

	/**
	 * Method to get a time measure field
	 *
	 * @param   string  $fieldName     Field name
	 * @param   string  $value         Field value
	 * @param   string  &$node         Node
	 * @param   string  $control_name  Controler name
	 * @param   int     $k             A counter
	 *
	 * @return  string  A store id.
	 *
	 * @since	1.0.0
	 */
	public function fetchTimemeasure($fieldName, $value, &$node, $control_name, $k)
	{
		$time_measure   = array();

		// #$time_measure[] = JHTML::_('select.option','hour', JText::_('LMS_HOUR'));

		$time_measure[] = JHTML::_('select.option', 'day', Text::_('COM_TJLMS_DAYS'));
		$time_measure[] = JHTML::_('select.option', 'week', Text::_('COM_TJLMS_WEEK'));
		$time_measure[] = JHTML::_('select.option', 'month', Text::_('COM_TJLMS_MONTH'));
		$time_measure[] = JHTML::_('select.option', 'year', Text::_('COM_TJLMS_YEAR'));
		$time_measure[] = JHTML::_('select.option', 'unlimited', Text::_('COM_TJLMS_UNLIMITED'));

		$Timemeasure = JHTML::_(
		'select.genericlist', $time_measure, 'subs_plan[' . $k . '][time_measure]', '
		 onchange="checkForUnlimited(this.value,this.id)" class="subs_plan_time_measure" ', 'value', 'text', $value, "subs_plan_time_measure_" . $k
		);

		return $Timemeasure;
	}

	/**
	 * Method to get a duration field
	 *
	 * @param   string  $fieldName     Field name
	 * @param   string  $value         Field value
	 * @param   string  &$node         Node
	 * @param   string  $control_name  Controler name
	 * @param   int     $k             A counter
	 * @param   string  $timeMeasure   timeMeasure
	 *
	 * @return  string  A store id.
	 *
	 * @since	1.0.0
	 */
	public function fetchDuration($fieldName, $value, &$node, $control_name, $k, $timeMeasure)
	{
		// Durgesh added for zero value validation
		$dur = $value;
		$readOnly = '';

		if (empty ($value))
		{
			$dur = 0;
		}

		// Durgesh added for zero value validation

		$readOnly = '';

		if ($timeMeasure == 'unlimited')
		{
			$readOnly = 'readonly';
		}

		$html = '<label id="subs_plan_duration_lbl_' . $k . '" for="subs_plan_duration_' . $k
		. '" aria-invalid="false" style="display:none">' . Text::_('COM_TJLMS_DURATION') . '</label>';

		return $html .= '<input type="text" id="subs_plan_duration_' . $k . '" name="subs_plan['
		. $k . '][duration]"  class="subs_plan_duration validate-natural-number"  placeholder="'
		. Text::_('COM_TJLMS_DURATION') . '"  ' . $readOnly . ' value=' . $value . '>';
	}

	/**
	 * Method to get a price field
	 *
	 * @param   string  $fieldName     Field name
	 * @param   string  $value         Field value
	 * @param   string  &$node         Node
	 * @param   string  $control_name  Controler name
	 * @param   int     $k             A counter
	 *
	 * @return  string  A store id.
	 *
	 * @since	1.0.0
	 */
	public function fetchPrice($fieldName, $value, &$node, $control_name, $k)
	{
		$curr_sym                   = $this->ComtjlmsHelper->getCurrencySymbol();
		$params                     = ComponentHelper::getParams('com_tjlms');
		$currency_display_format    = $params->get('currency_display_format');

		$currFormat_position        = strpos($currency_display_format, '{CURRENCY_SYMBOL}');

		if ($currFormat_position == 0)
		{
			$posClass = "input-prepend";
		}
		else
		{
			$posClass = "input-append";
		}

		$html                       = '<label id="subs_plan_price_lbl_' . $k . '" for="subs_plan_price_' . $k . '"
		 aria-invalid="false" style="display:none">
		' . Text::_('COM_TJLMS_PRICE') . '</label>';
		$PriceTextBox = '<input type="text" id="subs_plan_price_' . $k . '" name="subs_plan['
		. $k . '][price]"  class="subs_plan_price validate-natural-number form-control"  placeholder="'
		. Text::_('COM_TJLMS_PRICE') . '"  value=' . $value . '>';
		$currency_display_formatstr = '';
		$currency_display_formatstr = str_replace('{AMOUNT}', "&nbsp;" . $PriceTextBox, $currency_display_format);
		$currency_format = $currency_display_formatstr;
		$currency_display_formatstr = str_replace('{CURRENCY_SYMBOL}', "&nbsp;" . '<span class="add-on">' . $curr_sym . '</span>', $currency_format);

		$html .= "<span class='" . $posClass . "'>" . $currency_display_formatstr . "</span>";

		return $html;

		/* $Price = '<input type="text" id="subs_plan_price_' . $k . '" name="subs_plan[' . $k . '][price]"';
		$Price .= ' class="subs_plan_price " Onkeyup= "checkforalpha(this,46);" placeholder="' . Text::_('COM_TJLMS_PRICE') . '" value=' . $value . '>';

		return $Price;*/
	}

	/**
	 * Method to get a plan id field
	 *
	 * @param   string  $fieldName     Field name
	 * @param   string  $value         Field value
	 * @param   string  &$node         Node
	 * @param   string  $control_name  Controler name
	 * @param   int     $k             A counter
	 *
	 * @return  string  A store id.
	 *
	 * @since	1.0.0
	 */
	public function fetchPlanId($fieldName, $value, &$node, $control_name, $k)
	{
		$PlanId = '<input type="hidden" id="subs_plan_id_' . $k . '" name="subs_plan[' . $k . '][id]"';
		$PlanId .= ' class="subs_plan_id " placeholder="' . Text::_('COM_TJLMS_PLAN_ID') . '"  value=' . $value . '>';

		return $PlanId;
	}
}
?>
<script>
		jQuery(window).load(function() {

		var pre = length_subs_plan;
		for (i = 0; i < pre; i++) {
        var duration = jQuery('#subs_plan_time_measure_'+ i).val();

        if(duration == 'unlimited')
			{
				jQuery('#subs_plan_duration_'+ i).hide();
			}
		}
		});

	function addClone(rId,rClass)
	{
				var pre = length_subs_plan;
				length_subs_plan++;
				var removeButton="<div id='remove_btn_div"+pre+"' class='com_tjlms_remove_button span2'>";
				removeButton+="<button class='btn btn-medium btn-danger' type='button' id='remove"+pre+"'";
				removeButton+="onclick=\"removeClone('com_tjlms_repeating_block"+pre+"','remove_btn_div"+pre+
				"');\" title=\"<?php echo Text::_('COM_TJLMS_REMOVE_TOOLTIP'); ?>\" >";
				removeButton+="<i class='icon-minus-2 fa fa-minus-white'></i></button>";
				removeButton+="</div>";
				if(jQuery.prototype.chosen){
					jQuery('#subs_plan_container select').chosen();
					jQuery('#subs_plan_container select').chosen("destroy");
				}
				var newElem=jQuery('#'+rId+pre).clone(true).off().attr('id',rId+length_subs_plan);

				jQuery(newElem).find('label[for$="_' + pre + '"]').attr('for',function(i,attrVal){
					return attrVal.replace(new RegExp( "_" + pre + '$'), '_' + length_subs_plan)
				})

				newElem.find('input[name=\"subs_plan[' + pre + '][price]\"]').attr({'name': 'subs_plan[' +
					length_subs_plan + '][price]','value':''});
				newElem.find('input[name=\"subs_plan[' + pre + '][duration]\"]').attr({'name': 'subs_plan[' +
					length_subs_plan + '][duration]','value':'','readonly':false});
				newElem.find('select[name=\"subs_plan[' + pre + '][time_measure]\"]').attr({'name': 'subs_plan[' +
					length_subs_plan + '][time_measure]','value':'' });

				/*incremnt id*/
				newElem.find('input[id=\"subs_plan_price_'+pre+'\"]').attr({'id': 'subs_plan_price_'+length_subs_plan,'value':''});
				newElem.find('input[id=\"subs_plan_duration_'+pre+'\"]').attr({'id': 'subs_plan_duration_'+length_subs_plan,'value':''});
				newElem.find('select[id=\"subs_plan_time_measure_'+pre+'\"]').attr({'id': 'subs_plan_time_measure_'+length_subs_plan,'value':''});

				jQuery('#'+rId+pre).after(newElem);

				jQuery('#subs_plan_time_measure_'+length_subs_plan).val('day');
				jQuery('#subs_plan_duration_'+length_subs_plan).val('');
				jQuery('#subs_plan_price_'+length_subs_plan).val('');
				//get select box work for clone
				//jQuery("#subs_plan_time_measure_"+length_subs_plan).removeClass("chzn-done").css("display", "block").next().remove();
				//jQuery("#subs_plan_time_measure_"+length_subs_plan).chosen();
				//jQuery("select").trigger("liszt:updated");  /* IMP : to update to chz-done selects*/
				//end
				if(jQuery.prototype.chosen){
					jQuery('#subs_plan_container select').chosen();
				}
				jQuery('#'+rId+pre).after(removeButton)
				jQuery('#subs_plan_duration_'+length_subs_plan).show();
		}

		function removeClone(rId, r_btndivId)
		{
			var flag=0;
			jQuery('#'+rId).find('input:text').each(function(){
					if(jQuery(this).val()!='')
					{
						flag++;
					}
			});

			if(flag == 2)
			{
				var remove_comfirm=confirm("<?php echo Text::_('LMS_SURE_REMOVE_SUBS_PLAN'); ?>")

				if (remove_comfirm==true)
				{
					jQuery('#'+rId).remove();
					jQuery('#'+r_btndivId).remove();
				}
				else
				{
					return false;
				}
			}
			else
			{
				jQuery('#'+rId).remove();
				jQuery('#'+r_btndivId).remove();
			}
		}
</script>
