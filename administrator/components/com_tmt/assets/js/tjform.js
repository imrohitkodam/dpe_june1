var tjform = {

		init: function(form_id, qztype)
		{
			var lesson_format_form	=jQuery('#lesson-format-form_'+form_id);
			var lesson_basic = jQuery('#lesson-basic-form_'+form_id);

			// Turn radios into btn-group
			jQuery('.radio.btn-group label').addClass('btn');
			jQuery('.btn-group label:not(.active)').click(function(){
				var label = jQuery(this);
				var input = jQuery('#' + label.attr('for'));

				if (!input.prop('checked')) {
					label.closest('.btn-group').find('label').removeClass('active btn-success btn-danger btn-primary');
					if (input.val() == '') {
						label.addClass('active btn-primary');
					} else if (input.val() == 0) {
						label.addClass('active btn-danger');
					} else {
						label.addClass('active btn-success');
					}
					input.prop('checked', true);
					input.trigger('change');
				}
			});

			jQuery(".btn-group.radio").each(function(){
				jQuery('label', jQuery(this)).attr('class',"btn");

				var checked_in = jQuery('input:checked', jQuery(this));
				var checked_id = jQuery(checked_in).attr('id');
				var sibling_label = jQuery('label[for="'+ checked_id +'"]');

				if(jQuery(checked_in).val() == '1')
				{
					jQuery(sibling_label).addClass("active btn-success");
				}
				else
				{
					jQuery(sibling_label).addClass("active btn-danger");
				}
			});

			//On load to hide and show Time Finished Alert
			var show_time_finish = parseInt(jQuery("input[name='lesson_format["+qztype+"][show_time_finished]']:checked",lesson_format_form).val(),10);

			if(show_time_finish == 0)
			{
				jQuery("input[name='lesson_format["+qztype+"][time_finished_duration]']",lesson_format_form).parent().parent().hide();
			}
			else
			{
				jQuery("input[name='lesson_format["+qztype+"][time_finished_duration]']",lesson_format_form).parent().parent().show();
			}

			//On load to hide and show Time Finished Alert end

			// Time Duration(in minutes) validation
			jQuery("input[name='lesson_format["+qztype+"][time_duration]']",lesson_format_form).blur(function()
			{
				jQuery(this).val(jQuery.trim(jQuery(this).val()));

				if(jQuery(this).val() != '' && jQuery(this).val() != '0' && !jQuery(this).hasClass('invalid'))
				{
					jQuery('#jform_show_time label').removeClass("disabledradio");
					jQuery('#jform_show_time_finished label').removeClass("disabledradio");
				}
				else
				{
					jQuery('.quizmetadata_questions #jform_show_time1').trigger("click");
					jQuery('.quizmetadata_questions #jform_show_time_finished1').trigger("click");

					jQuery('#jform_show_time label',lesson_format_form).addClass("disabledradio");
					jQuery('#jform_show_time_finished label',lesson_format_form).addClass("disabledradio");

					jQuery("input[name='lesson_format["+qztype+"][time_finished_duration]']").parent().parent().hide();
				}
			});
			// Time Duration(in minutes) validation ends

			// trigger called above
			jQuery('#jform_show_time_finished input').click(function()
			{
				jQuery('#jform_time_finished_duration',lesson_format_form).val('');
				if(jQuery(this).val() == 1)
				{
					jQuery("input[name='lesson_format["+qztype+"][time_finished_duration]']",lesson_format_form).parent().parent().show();
				//	jQuery('#lesson_format_quiz__time_finished_duration_-lbl',format_lesson_form).parent().parent().show();
				}
				else
				{
					jQuery("input[name='lesson_format["+qztype+"][time_finished_duration]']",lesson_format_form).parent().parent().hide();
					//jQuery('#lesson_format_quiz__time_finished_duration_-lbl',format_lesson_form).parent().parent().hide();
				}
			});
			// trigger called above

			// Show message of Time Finished Alert
			jQuery("input[name='lesson_format["+qztype+"][time_finished_duration]']",lesson_format_form).blur(function(){

				var time_duration = jQuery("input[name='lesson_format["+qztype+"][time_duration]']",lesson_format_form).val();
				var time_finished_duration = jQuery("input[name='lesson_format["+qztype+"][time_finished_duration]']",lesson_format_form).val();

				if( time_duration !== '' && time_finished_duration !=='' )
				{
					var atd = (time_duration - time_finished_duration);

					if(! isNaN(atd) && (atd > 0) )
					{
						jQuery('#time_finished_duration_minute').remove('');
						jQuery("input[name='lesson_format["+qztype+"][time_finished_duration]']").parent().append('<div id="time_finished_duration_minute" class="text text-info"><span class="center"><em>' + Joomla.JText._('COM_TMT_TEST_FORM_TIME_FINISHED_ALERT_MSG_1')  + ' ' + atd +Joomla.JText._('COM_TMT_TEST_FORM_TIME_FINISHED_ALERT_MSG_2') +'</em></span></div>');
					}
					else
					{
						jQuery('#time_finished_duration_minute').html('');
					}
				}
			});
			// Show message of Time Finished Alert

			var state = jQuery("input[type='radio'][name='lesson_format["+qztype+"][state]']:checked", lesson_basic).val();
			jQuery("input[name='lesson_format["+qztype+"][state]']",lesson_format_form).val(state);

			jQuery('.add_existing_quiz', lesson_format_form).hide();
			jQuery('.quiz_jform_field', lesson_format_form).hide();
			getFormsubFormat(form_id);

		},

		getTotal: function()
		{
			sum=0;
			jQuery("div[name^='td_marks']").each(function() {
				var ele=jQuery(this);
				if( isNaN(jQuery(ele).text()) ) /* if not number, enter 0 */
				{
					/* Do nothing*/
				}
				else
				{
					if( jQuery(ele).text() ) /* if not empty */
					{
						sum +=parseInt(jQuery(ele).text(),10); /* 10 is for decimal system*/
					}
				}
			});
			jQuery('#total-marks-content').html(sum);
		},

		editSection: function(lesson_id, section_id)
		{
			if(section_id > 0){
				var section_lms	=	'sectionlist_'+section_id;
				jQuery('#'+section_lms+' #section_row_'+section_id).hide();
				jQuery('#'+section_lms+' #add_section_form_'+section_id).show();
				jQuery('.section-title').focus();
			}
			else{
				var add_sectionfor_test	=	'add_section_form_0';
				jQuery('#'+add_sectionfor_test).show();
				jQuery('.add-section-div').hide();
				jQuery('.section-title').focus();
			}
			return false;
		},

		noScript: function(str)
		{
			var div = jQuery('<div>').html(str);
			div.find('script').remove();

			var noscriptStr = str = div.html();
			return noscriptStr;
		},

		success_popup: function(str)
		{
			location.reload(true);
		},

		hideeditSection: function(test_id,section_id)
		{
			if(section_id > 0){
				var section_lms	=	'sectionlist_'+section_id;
				jQuery('#'+section_lms + " .tjlms_section").show();
				jQuery('#'+section_lms + " .section-edit-form").hide();
			}
			else{
				var add_sectionfor_quiz	=	'add_section_form_0';
				jQuery('#'+add_sectionfor_quiz).hide();
				jQuery('.add-section-div').show();
			}
			return false;
		},

		fixDuplicatesForm: function()
		{
			var allQuestions=[];
			jQuery("#quiz-sections .question_paper .reorder",window.parent.document).find("input[type*='checkbox']").each(function(cbs,cb) {
				allQuestions.push( jQuery(cb).attr('value') );
				allQuestions=allQuestions.getUnique();
			});

			var duplicatedFound=0;
			jQuery.each(allQuestions, function (qst, q) {
				jQuery("#quiz-sections .question_paper .reorder",window.parent.document).find("input[value='"+q+"']").each(function(cbs,cb) {
					jQuery(cb).parent().parent().removeClass('error');
					if(cbs > 0 )
					{
						jQuery(cb).parents('.question_layout').addClass('alert-danger');
						duplicatedFound++;
					}
					else
					{
						jQuery(".tmt_form_errors").hide();
					}
				});
			});
			return duplicatedFound;
		},

		validatequizquiz: function(form_id,qztype)
		{
			var lesson_format_form	=jQuery('#lesson-format-form_'+form_id);
			var lesson_basic = jQuery('#lesson-basic-form_'+form_id);
			var total_marks = parseInt(jQuery("input[name='lesson_format["+qztype+"][total_marks]']",lesson_format_form).val(),10);
			var pass_marks = parseInt(jQuery("input[name='lesson_format["+qztype+"][passing_marks]']",lesson_format_form).val(),10);
			var show_time_finish = parseInt(jQuery("input[name='lesson_format["+qztype+"][show_time_finished]']:checked",lesson_format_form).val(),10);
			var res = {check: 1, message: ""};

			if(qztype == 'quiz' || qztype == 'exercise')
			{
				if (!total_marks > 0 || total_marks < 0)
				{
					jQuery("input[name='lesson_format["+qztype+"][total_marks]']",lesson_format_form).val('');
					jQuery("input[name='lesson_format["+qztype+"][total_marks]']",lesson_format_form).focus();
					res.check = 0;
					res.message = Joomla.JText._('COM_TMT_TEST_FORM_NON_ZERO_VALUE_MARKS');

					return res;
				}

				if (!pass_marks > 0 || pass_marks < 0)
				{
					jQuery("input[name='lesson_format["+qztype+"][passing_marks]']",lesson_format_form).val('');
					jQuery("input[name='lesson_format["+qztype+"][passing_marks]']",lesson_format_form).focus();
					res.check = 0;
					res.message = Joomla.JText._('COM_TMT_TEST_FORM_NON_ZERO_VALUE_MARKS');

					return res;
				}

				if (pass_marks > total_marks )
				{
					jQuery("input[name='lesson_format["+qztype+"][total_marks]']",lesson_format_form).focus();
					res.check = 0;
					res.message = Joomla.JText._('COM_TMT_TEST_FORM_MSG_MIN_MARKS_HIGHER');

					return res;
				}
			}

			if(show_time_finish == 1)
			{
				if ( parseInt(jQuery("input[name='lesson_format["+qztype+"][time_finished_duration]']").val(),10) >= parseInt(jQuery("input[name='lesson_format["+qztype+"][time_duration]']").val(),10) )
				{
					jQuery('#jform_time_duration').focus();
					res.check = 0;
					res.message = Joomla.JText._('COM_TMT_TEST_FORM_MSG_TIME_FINISHED_DURATION_HIGHER');

					return res;
				}
			}

			return res;
		},

		getQuestion: function(thisbutton,form_id,qztype)
		{
			var lesson_format_form	=jQuery('#lesson-format-form_'+form_id);
			var lessonform	=	jQuery('#tjlms_add_lesson_form_'+form_id);
			var lesson_basic = jQuery('#lesson-basic-form_'+form_id);

			var wwidth = jQuery(window).width();
			var wheight = jQuery(window).height();

			jQuery('.tjlms_form_errors').hide();
			jQuery("#addq",lesson_format_form).val(1);

			var format = jQuery("#jform_format",lesson_format_form).val();
			var subformat = jQuery("#jform_subformat",lesson_format_form).val();

			var check_validation = tjform.validatequizquiz(form_id,qztype);

			if (check_validation.check == '0')
			{
				show_lessonform_error(1, check_validation.message, lessonform);
				return false;
			}

			jQuery('.tjlms_form_errors').hide();
			jQuery(".format_types").css('display','none');

			// Submit file through Ajax
			jQuery.ajax({
				url: 'index.php?option=com_tmt&view=test&task=test.save&format=json',
				dataType: 'json',
				type: 'POST',
				data:  jQuery('input[name^="lesson_format"]',lesson_format_form).serializeArray(),
				async:false,
				success: function (data)
				{

					data = JSON.stringify(data);
					var response = jQuery.parseJSON(data);
					var output	=	response.OUTPUT;
					var res	=	output['result'];
					var msg	=	output['msg'];
					var test_id = output['test_id'];
					var set_id = output['set_id'];
					var lesson_id = jQuery("#lesson_id",lesson_format_form).val();
					var course_id = jQuery("#course_id",lesson_basic).val();

					if(res == 1)
					{
						var questions_link = "index.php?option=com_tmt&view=test&layout=quiz&tmpl=component&id="+test_id+"&qztype="+subformat+"&unique="+lesson_id+"&course_id="+course_id+"&set_id="+set_id;

						SqueezeBox.open(questions_link, {
							handler: 'iframe',
							closable:false,
							size: {x: wwidth, y: wheight},
							sizeLoading: { x: wwidth, y: wheight },
							classWindow: 'tjlms_lesson_screen',
							classOverlay: 'tjlms_lesson_screen_overlay',
							onOpen:function() {
								jQuery('iframe').load( function() {
									//jQuery('iframe').contents().find("#final_total_marks").val(total_marks);
								});
							}
						});

						//~ jQuery('#tjlms_add_lesson_form_'+form_id + ' .nav-tabs li').removeClass('active');
						//~ jQuery('#tjlms_add_lesson_form_'+form_id + ' .tab-content .tab-pane').removeClass('active');
					}
					else
					{
						show_lessonform_error(1,'something went wrong',lessonform);
					}
					jQuery('.loadingsquares',lesson_format_form).hide();
				},
				error: function()
				{
						show_lessonform_error(1,'something went wrong',lessonform);
						jQuery('.loadingsquares',lesson_format_form).hide();
				},
				complete: function(xhr) {
					jQuery('.loadingsquares',lesson_format_form).hide();
				}
			});
		},

		delete: function(test_id,section_id)
		{
			var comfirmDelete = confirm(Joomla.JText._('COM_TMT_SURE_DELETE_SECTION'));

			if(comfirmDelete == true)
			{
				jQuery.ajax({
						url: "index.php?option=com_tmt&task=section.delete&test_id="+test_id+"&section_id="+section_id,
						type: "GET",
						dataType: "json",
						success: function(result)
						{
							if(result.data == 1)
							{
								jQuery('#sectionlist_'+section_id).remove();
								if(jQuery("#quiz-sections .mod_outer").length == 0)
								{
									jQuery('.hero-unit').show();
									jQuery('#marks_tr').hide();
								}
							}
							else
							return false;
						}

					});
			}
			else
			return false;
		}
	}
