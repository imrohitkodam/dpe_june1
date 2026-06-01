<?php
/**
 * @package     Shika
 * @subpackage  com_tjlms
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;

$source_url = $consumer_key = $secret_key = $launchin = '';

$subformat = $lesson->sub_format;

if (!empty($subformat))
{
	$source_plugin = explode('.', $subformat)[0];

	if ($source_plugin == 'ltiresource')
	{
		$source_url  = $lesson->media['source'];
		$mediaParams = $lesson->media['params'];

		$mediaParams = (!empty($mediaParams)) ? json_decode($mediaParams) : "";

		if (!empty($mediaParams))
		{
			$consumer_key = $mediaParams->key;
			$secret_key   = $mediaParams->secret;
			$launchin     = $mediaParams->launchin;
		}
	}
} ?>

<div class="control-group">
	<div class="control-label ">
		<label title="<?php echo Text::_("PLG_TJEXTERNALTOOL_SOURCE_URL") ?>">
			<?php echo Text::_("PLG_TJEXTERNALTOOL_SOURCE_URL") ?> *
		</label>
	</div>

	<div  class="controls">
		<input class= "lti-resorce"  type="url" id="ltiresource_url" name="lesson_format[ltiresource][url]" value="<?php echo $source_url;?>"/>
	</div>
</div>
<div class="control-group">
	<div class="control-label">
		<label title="<?php echo Text::_("PLG_TJEXTERNALTOOL_KEY") ?>">
			<?php echo Text::_("PLG_TJEXTERNALTOOL_KEY")?> *
		</label>
	</div>
	<div class="controls">
		<input class= "lti-resorce"  type="text"
					id="key"
					name="key"
					value="<?php echo $consumer_key;?>"/>
	</div>
</div>
<div class="control-group">
	<div class="control-label">
		<label title="<?php echo Text::_("PLG_TJEXTERNALTOOL_SECRET") ?>">
			<?php echo Text::_("PLG_TJEXTERNALTOOL_SECRET")?> *
		</label>
	</div>
	<div class="controls">
		<input class= "lti-resorce" type="text"
					id="secret"
					name="secret"
					value="<?php echo $secret_key;?>"/>
	</div>
</div>
<div class="control-group">
	<div class="control-label">
		<label title="<?php echo Text::_("PLG_TJEXTERNALTOOL_LAUNCH_LESSON") ?>">
			<?php echo Text::_("PLG_TJEXTERNALTOOL_LAUNCH_LESSON")?> *
		</label>
	</div>
	<div class="controls">
		<?php

		$options[] = HTMLHelper::_('select.option', 'iframe', Text::_('PLG_TJEXTERNALTOOL_LAUNCH_LESSON_IFRAME'));
		$options[] = HTMLHelper::_('select.option', 'same_view', Text::_('PLG_TJEXTERNALTOOL_LAUNCH_LESSON_SAME_WINDOW'));
		$options[] = HTMLHelper::_('select.option', 'new_tab', Text::_('PLG_TJEXTERNALTOOL_LAUNCH_LESSON_NEW_TAB'));
		echo HTMLHelper::_('select.genericlist', $options, 'launchin', 'class = "inputbox"', 'value', 'text', $launchin);

		?>
	</div>
</div>
<input type="hidden" id="subformatoption" name="lesson_format[ltiresource][subformatoption]" value="url"/>
<input type="hidden" id="ltiresource_params" name="lesson_format[ltiresource][params]" value=""/>

<script type="text/javascript">
/* Function to load the loading image. */
function validateexternaltoolltiresource(formid,format,subformat,media_id)
{
	var res                = {check: 1, message: "PLG_TJEXTERNAL_LTIRESOURCE_VAL_PASSES"};
	var val_passed         = '0';
	var format_lesson_form = jQuery("#lesson-format-form_"+ formid);
	var url                = jQuery("#ltiresource_url", format_lesson_form).val();
	var key                = jQuery("#key", format_lesson_form).val();
	var secret             = jQuery("#secret", format_lesson_form).val();
	var launchin           = jQuery("#launchin", format_lesson_form).val();

	if (!url || !key || !secret)
	{
		res.check   = '0';
		res.message = "<?php echo Text::_('PLG_TJEXTERNAL_LTIRESOURCE_SOMETHING_MISSING');?>";
	}

	if(res.check == 1)
	{
		var source = {key: key, secret: secret, launchin: launchin};

		var jsonString = JSON.stringify(source);

		jQuery("#ltiresource_params", format_lesson_form).val(jsonString);
	}
	return res;
}

</script>
