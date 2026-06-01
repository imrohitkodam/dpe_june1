<?php
$parameter_value = (int) $displayData['param']->value;
?>
<div class="ml-30 mb-10 pt-5 pl-10">

<div class="form-group input-field">
	<select data-maxval="<?php echo $parameter_value?>" class="<?php echo $displayData['param']->type; ?> " name="assessmentParams[<?php echo $displayData['param']->id?>][rating_value]">
<?php
if ($parameter_value != '')
{
	for ($i=0;$i<=$parameter_value;$i++)
	{
		?>
			<option value="<?php echo $i; ?>" <?php if($displayData['reviewerRating']->rating_value == $i) echo 'selected="selected"'; ?>><?php echo $i;?></option>
		<?php
	}
}
?>
	</select>
</div>
</div>
