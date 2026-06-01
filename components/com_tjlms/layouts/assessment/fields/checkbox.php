<?php
use Joomla\CMS\Language\Text;

$param = $displayData['param'];
$parameter_value = (int) $displayData['param']->value;
$checked = ($parameter_value == $displayData['reviewerRating']->rating_value) ? 'checked="checked"' : '';
?>

<div class="container-fluid">
	<div class="row">
		<div class="no-gutters">
			<div class="col-sm-10 col-xs-8">
				<div class="d-flex align-items-start">
					<div class="font-bold text-center">
						<div class="assessment-param__number img-circle">	
							<span><?php echo --$displayData['index'];?></span>
						</div>
					</div>
					<div class="assessment-param__title test-question__answers pl-10 pt-5">
						<label class="custom-control custom-checkbox input-label">
							<input <?php echo $checked ?> type="checkbox" class="custom-control-input" name="assessmentParams[<?php echo $displayData['param']->id?>][rating_value]" value="<?php echo $parameter_value?>">
							<span class="custom-control-indicator checkmark"></span>
							<span class="custom-control-description"><?php echo htmlentities($displayData['param']->title);?></span>
					  </label>
					</div>
				</div>
				<?php if(trim($param->description)):?>
					<div class="ml-30 mb-10 pt-5 pl-10">
						<em><?php echo htmlentities($param->description)?></em>
					</div>
				<?php endif;?>
			</div>
			<div class="col-sm-2 col-xs-4">
				<span class="assessment-param__marks p-5 text-center pull-right">
					<?php echo $param->value;
					echo Text::_('COM_TJLMS_ASSESSMENTS_PARAMS_MARKS');?>
				</span>
			</div>
		</div>
	</div>
</div>
