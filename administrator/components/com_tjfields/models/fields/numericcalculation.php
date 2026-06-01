	<?php
	/**
	* @package    Tjfields
	* @author     Techjoomla <extensions@techjoomla.com>
	* @copyright  Copyright (c) 2009-2022 TechJoomla. All rights reserved.
	* @license    GNU General Public License version 2 or later.
	*/

	defined('JPATH_BASE') or die;

	use Joomla\CMS\Factory;
	use Joomla\CMS\Uri\Uri;
	use Joomla\CMS\Form\FormField;
	use Joomla\CMS\Language\Text;

	/**
	* Supports an HTML select list of allocated cluster
	*
	* @since  __DEPLOY_VERSION__
	*/

	class JFormFieldNumericcalculation extends FormField
	{
	/**
	* The form field type.
	*
	* @var    string
	* @since  __DEPLOY_VERSION__
	*/
	protected $type = 'numericcalculation';

	/**
	* Field for getting field labels
	*
	* @return  html mapping fields
	*
	* @since   1.0
	*/
	public function getInput()
	{
		$document = Factory::getDocument();
		$document->addStyleSheet(Uri::base() . 'administrator/components/com_tjfields/assets/css/tjfields.css');
		$formName = explode('.', $this->form->getName());
		$calculationField = $this->getAttribute("calculationfield") ;
		$calculationJsonField = json_encode(trim($this->getAttribute("calculationfield")));
		$calculationFieldName = $this->getAttribute("name");
				$fieldName = array_values(array_filter(preg_replace("/[^a-zA-Z 0-9 _]+/", "", explode(' ',str_replace('-', '_', preg_replace('/[^ \w-]/', '', $calculationField))))));
						$defaultText =  Text::_('COM_TJFIELDS_NUMERICCALCULATION_NO_DATA');

		$doc = Factory::getDocument();
		$doc->addScript(Uri::root() . 'administrator/components/com_tjfields/assets/js/math.min.js');
		$doc->addScript(Uri::root() . 'administrator/components/com_tjfields/assets/js/numericcalculation.min.js');

		// for simple form calulation the js
		if ($formName[0] != 'subform')
		{ 		
			?>
			<script>
				jQuery(document).ready(function(){

						// function call to  do calculation 
						calculateNumeriFieldData(<?php echo $calculationJsonField;?>,<?php echo $this->getAttribute("colorcombination");?>,<?php echo json_encode($fieldName);?>,<?php echo $this->id;?>,'<?php echo $defaultText;?>');
					});
				jQuery('*').on('change',function(){						
					calculateNumeriFieldData(<?php echo $calculationJsonField;?>,<?php echo $this->getAttribute("colorcombination");?>,<?php echo json_encode($fieldName);?>,<?php echo $this->id;?>,'<?php echo $defaultText;?>');
				})

			</script>
		<?php }
		elseif ($formName[0] == 'subform')
			{?>
				<!-- For subform calulation JS is below-->
				<script>
					jQuery(document).ready(function(){
							// function call to  do calculation 
							calculateNumeriFieldDataSubform(<?php echo $this->getAttribute("colorcombination");?>,<?php echo json_encode($fieldName);?>,<?php echo $calculationJsonField;?>,'<?php echo $calculationFieldName;?>','<?php echo $defaultText;?>');
						});
					jQuery(document).on('change', 'select',function(event) {
						calculationOnchangeSubform(event, <?php echo $this->getAttribute("colorcombination");?>,<?php echo json_encode($fieldName);?>,<?php echo $calculationJsonField;?>,'<?php echo $calculationFieldName;?>','<?php echo $defaultText;?>');
					});
					jQuery(document).on('change', 'input[type="radio"]', function() {
						calculationOnchangeSubform(event, <?php echo $this->getAttribute("colorcombination");?>,<?php echo json_encode($fieldName);?>,<?php echo $calculationJsonField;?>,'<?php echo $calculationFieldName;?>','<?php echo $defaultText;?>');
					});
				</script>	

			<?php }		
			if ($formName[0] != 'subform')
			{
				$return = '<div class="col-xs-12 col-md-12 ">
				<div class="form-group">
				<div class="col-sm-5 " style="white-space: nowrap;">
				<span class="numericcalculation" id='.$this->id.'_span style="margin-left:0px">'.$defaultText.'</span><input type="hidden" id="' . $this->id . '" name="'.$this->name.'" >
				</div>
				</div>
				</div>';
			}
			else
			{
				$return = '<div class="col-xs-12 col-md-12 ">
				<div class="form-group">
				<div class="col-sm-5 ">
				<span class="numericcalculation" id='.$this->id.'_span  style="margin-left:0px">'.$defaultText.'</span><input type="hidden" id="' . $this->id . '" name="'.$this->name.'">
				</div>
				</div>
				</div>';
			}



			return $return;
		}


	}
