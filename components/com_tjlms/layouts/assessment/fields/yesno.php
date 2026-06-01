<?php
use Joomla\CMS\Language\Text;
$value = (int) $displayData['param']->value;

if ($displayData['reviewerRating']->rating_value == '')
{
	$yesChecked = $noChecked  = '';
}
else
{
	$yesChecked = ($value == $displayData['reviewerRating']->rating_value) ? 'checked="checked"' : '';
	$noChecked  = ($displayData['reviewerRating']->rating_value == 0) ? 'checked="checked"' : '';
}

$paramId = $displayData['param']->id;
?>
<div class="ml-30 mb-10 pt-5 pl-10 test-question__answers">
	<label class="custom-control custom-radio input-label">
	  <input <?php echo $yesChecked; ?> value="<?php echo $value?>" id="radio1<?php echo $paramId;?>" name="assessmentParams[<?php echo $paramId?>][rating_value]" type="radio" class="custom-control-input">
	  <span class="radiobtn"></span>
	  <span class="custom-control-indicator"></span>
	  <span class="custom-control-description" for="radio1<?php echo $paramId;?>"><?php echo Text::_('COM_TJLMS_ASSESSMENTS_YES');?></span>
	</label>
	<label class="custom-control custom-radio input-label ml-10">
	  <input <?php echo $noChecked; ?> value="0" id="radio2<?php echo $paramId;?>" name="assessmentParams[<?php echo $paramId;?>][rating_value]" type="radio" class="custom-control-input">
	  <span class="radiobtn"></span>
	  <span class="custom-control-indicator"></span>
	  <span class="custom-control-description" for="radio2<?php $paramId;?>"><?php echo Text::_('COM_TJLMS_ASSESSMENTS_NO');?></span>
	</label>
</div>

