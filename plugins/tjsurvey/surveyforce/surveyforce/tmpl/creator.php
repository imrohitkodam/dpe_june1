<?php
/**
 * @package Tjlms
 * @copyright Copyright (C) 2009 -2010 Techjoomla, Tekdi Web Solutions . All rights reserved.
 * @license GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link     http://www.com
 */
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Language\Text;

$subformat = $lesson->sub_format;

if (!empty($subformat))
{
	$subformat_source_options = json_decode($lesson->params);
	$surveyid = $lesson->source;
} ?>

<div class="control-group">
	<div class="control-label">
		<?php echo Text::_("PLG_TJSURVEY_LAUNCH_LESSON"); ?>
	</div>
	<div class="controls">
		<?php
		$options[] = JHTML::_('select.option',0, Text::_('PLG_TJSURVEY_SELECT_OPTION'));

		foreach ($surveylist as $survey)
		{
			$options[] = JHTML::_('select.option', $survey->id, $survey->sf_name);
			if ($survey->id == $surveyid)
			{
				$title = $survey->sf_name;
			}
		}

		echo JHTML::_('select.genericlist', $options, 'lesson_format[surveyforce][survey]', 'title="'. $title .'" class = "inputbox required"', 'value','text', $surveyid); ?>
		<input type="hidden" id="subformatoption" name="lesson_format[surveyforce][subformatoption]" value="survey"/>
	</div>
</div>

<script type="text/javascript">
	/* Function to load the loading image. */
	function validatesurveysurveyforce(formid,format,subformat,media_id)
	{
		var res = {check: 1, message: "PLG_TJEXTERNAL_LTIRESOURCE_VAL_PASSES"};

		var val_passed = '0';
		var format_lesson_form = jQuery("#lesson-format-form_"+ formid);
		var surveyid = jQuery("#lesson_formatsurveyforcesurvey", format_lesson_form).val();

		if (surveyid == '' || surveyid == 0)
		{
			res.check = '0';
			res.message = "<?php echo Text::_('PLG_TJSURVEY_EURVEYFORCE_SURVEY_VALIDATION');?>";
		}

		if(res.check == 1)
		{
			var source = {surveyid: surveyid};
			var jsonString = JSON.stringify(source);

			jQuery("#surveyforce_params", format_lesson_form).val(jsonString);
		}
		return res;
	}
</script>
