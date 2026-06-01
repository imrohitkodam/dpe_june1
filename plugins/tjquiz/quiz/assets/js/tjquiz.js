	var tjquiz = {

		quizSpecificMetadata: function(form_id)
		{
			var lesson_format_form = techjoomla.jQuery('#lesson-format-form_'+form_id);
			var time_duration = techjoomla.jQuery("input[name='lesson_format[quiz][time_duration]']", lesson_format_form).val();

			var time_finished_duration = techjoomla.jQuery("input[name='lesson_format[quiz][time_finished_duration]']", lesson_format_form).val();

			techjoomla.jQuery('.quiz_allow_existing').hide();
			techjoomla.jQuery('.quizmetadata_questions').show();
			techjoomla.jQuery('.quizmetadata_questions .quiz_metadata').show();
			techjoomla.jQuery('.quizmetadata_questions .formquiz-actions').show();

			if ( time_duration == '' || time_finished_duration == 0)
			{
				techjoomla.jQuery('#jform_show_time label', lesson_format_form).addClass("disabledradio");
				techjoomla.jQuery('#jform_show_time_finished label', lesson_format_form).addClass("disabledradio");
			}
		}
	}
