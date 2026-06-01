<?php
use Joomla\CMS\Language\Text;
$param = $displayData['param'];
$paramId = $displayData['param']->id;
?>
<div class="ml-30 mb-10 pt-5 pl-10">
	<div class="form-group input-field d-table-row">
		<label class="d-table-cell">Enter Marks:</label><input data-maxval="<?php echo (int) $param->value?>" class="input input-mini d-table-cell valign-middle ml-5 mb-0 <?php echo $param->type; ?> " name="assessmentParams[<?php echo $paramId;?>][rating_value]" type="text" value="<?php echo $displayData['reviewerRating']->rating_value;?>">
	</div>
</div>
<div class='invalid msg hide'><?php echo Text::sprintf('COM_TJLMS_ASSESSMENTS_MAX_MARKS', $param->value);?></div>

