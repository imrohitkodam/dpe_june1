
<div class="ml-30 mb-10 pt-5 pl-10">

<span class="rating star-cb-group">
	<?php
	$paramValue = (int) $displayData['param']->value;

	for($i=$paramValue; $i >0; $i--)
	{
		$checked = ($displayData['reviewerRating']->rating_value == $i) ? 'checked="checked"' : '';
	?>
		<input type="radio" id="star<?php echo $displayData['param']->id . '_'.$i?>" name="assessmentParams[<?php echo $displayData['param']->id?>][rating_value]" value="<?php echo $i; ?>" <?php echo $checked?> />
		<label for="star<?php echo $displayData['param']->id . '_'.$i?>" title="<?php echo $i; ?>">
		</label>
	<?php
	}
	

	/*$increment = $parameter_value * 0.1;
	for($i=10; $i>0; --$i)
	{
		$value   = $increment * $i;
		$checked = ($displayData['reviewerRating']->rating_value == $value) ? 'checked="checked"' : '';
		$startClass = ($i%2==0) ? 'full':'half';
		?>
		<input type="radio" id="star<?php echo $displayData['param']->id . '_'.$i?>" name="jform[param][<?php echo $displayData['param']->id?>][rating_value]" value="<?php echo $value; ?>" <?php echo $checked?> />
			<label class = "<?php echo $startClass?>" for="star<?php echo $displayData['param']->id . '_'.$i?>" title="<?php echo $value; ?>">
		</label>
	<?php
	}*/
?>
</span>
</div>
<style type="text/css">
.star-cb-group {
  /* remove inline-block whitespace */
  font-size: 0;
  /* flip the order so we can use the + and ~ combinators */
  unicode-bidi: bidi-override;
  direction: rtl;
  /* the hidden clearer */
}
.star-cb-group * {
  font-size: 1.5rem;
}
.star-cb-group > input {
  display: none;
}
.star-cb-group > input + label {
  /* only enough room for the star */
  display: inline-block;
  text-indent: 9999px;
  width: 1em;
  white-space: nowrap;
  cursor: pointer;
}
.star-cb-group > input + label:before {
  display: inline-block;
  text-indent: -9999px;
  content: "☆";
  color: #888;
}
.star-cb-group > input:checked ~ label:before, .star-cb-group > input + label:hover ~ label:before, .star-cb-group > input + label:hover:before {
  content: "★";
  color: #ffd700;
  text-shadow: 0 0 1px #333;
}
.star-cb-group > .star-cb-clear + label {
  text-indent: -9999px;
  width: 0.5em;
  margin-left: -0.5em;
}
.star-cb-group > .star-cb-clear + label:before {
  width: 0.5em;
}
.star-cb-group:hover > input + label:before {
  content: "☆";
  color: #888;
  text-shadow: none;
}
.star-cb-group:hover > input + label:hover ~ label:before, .star-cb-group:hover > input + label:hover:before {
  content: "★";
  color: #ffd700;
  text-shadow: 0 0 1px #333;
}
.star-cb-group > input:disabled + label {
  pointer-events:none;
}
</style>
