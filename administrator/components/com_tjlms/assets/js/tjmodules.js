jQuery(document).ready(function(){
	/*jQuery('.tjlms_add_lesson_form #jform_total_marks').change(function(){
		jQuery(this).parents(".tjlms_add_lesson_form").find("#total-marks-value").html(jQuery(this).val());
	});
	jQuery('.tjlms_add_lesson_form #jform_passing_marks').change(function(){
		jQuery(this).parents(".tjlms_add_lesson_form").find("#passing-marks-value").html(jQuery(this).val());
	});*/


	jQuery('input[name="jform[add_assessment]"').on('change',function(){
	var assesment_tab_value = jQuery(this).val();

		if (assesment_tab_value ==1)
		{
			jQuery('.assessment_form').show();
		}
		else
		{
			jQuery('.assessment_form').hide();
		}
	});
});

jQuery(window).on('load', function() {
	/*jQuery('.tjlms_add_lesson_form').each(function(){

		var tjlmsformid	= jQuery(this).attr('id');
		var formid	= tjlmsformid.replace("tjlms_add_lesson_form_", "");
		jQuery('.nav-tabs a[href="#assessment_'+ formid  +'"]').css('display','none');

		jQuery("fieldset.radio",this).each(function(){
			this.id= this.id + formid;
			jQuery(this).children('input').each(function () {
				this.id = this.id + formid;
			});

			jQuery(this).children('label').each(function () {
				jQuery(this).attr('for',jQuery(this).attr('for') + formid);
				jQuery(this).removeClass('active btn-success btn-danger')
			});
			var checkedInputID	=	jQuery(this).children('input:checked').attr('id');
			var checkedInputVal	=	jQuery(this).children('input:checked').val();
			if (checkedInputVal == 0) {
				jQuery("label[for=" + checkedInputID + "]").addClass('active btn-danger');
			} else {
				jQuery("label[for=" + checkedInputID + "]").addClass('active btn-success');
			}
		});
	});
	jQuery("fieldset.radio label").click(function()
	{
		var lessonform = jQuery(this).closest('.tjlms_add_lesson_form');
		var label = jQuery(lessonform).find(this);
		var input = jQuery( this ).siblings( '#' + label.attr('for') );
		jQuery(this).parent().find('label').removeClass('btn-success').removeClass('btn-danger');

		if (input.val() == '') {
			label.addClass('active btn-primary');
		} else if (input.val() == 0) {
			label.addClass('active btn-danger');
		} else {
			label.addClass('active btn-success');
		}
		input.prop('checked', true);

    });*/



	/* function call to able to sort the lessons of each module.*/
	/*sortLessons();*/


	/*VAISHALI for add lesson wizard*/
	/*jQuery('.module_actions .action').click(function()
	{

		var parent_mod	=	jQuery(this).closest('.mod_outer');
		var parent_mod_id	=  parent_mod.attr('id');
		var mod_id	=  parent_mod_id.replace('modlist_','');


		jQuery("#"+parent_mod_id + " .module_actions").hide();
		var module_lms	=	'modlist_'+mod_id;

		if(jQuery(this).hasClass( "btn-add-quizs" )){
			jQuery('#'+module_lms + " .add_quizs_wizard").show();
		}
		else if(jQuery(this).hasClass( "btn-add-lesson")){
			jQuery('#'+module_lms + " .add_lesson_wizard").show();
			jQuery('html,body').animate({
				 scrollTop: jQuery("#"+module_lms + " .add_lesson_wizard").offset().top},
				 'slow');
		}
		return false;

	});*/

	/*jQuery(".tjlms_add_lesson_form .nav-tabs li").click(function()
	{
		var tabToShow = jQuery('a',this).attr('href');
		var lessonform	=	jQuery(this).closest('.tjlms_add_lesson_form')
		var form_id	= lessonform.attr('id').replace('tjlms_add_lesson_form_','');

		if(tabToShow != '#general_' +  form_id)
		{
			/*Check if the first tab is validated*/
			/*if(validate_lessonactions(form_id) == 0)
			{
				return false;
			}*/

			/*if user is clicking on second tab get the we need to get the html of lesson type plugin:
			 By deafult it will be of scorm*/

			/*if(tabToShow == '#format_' +  form_id)
			{
				/*This is to get the lesson format html from respective plugin and show*/
				/*var format = jQuery('#format_' + form_id + ' #jform_format').val();
				var subformat = jQuery('#format_' + form_id + ' #jform_subformat').val();
				var lesson_id = jQuery('#format_' + form_id + ' #lesson_id').val();

				var lesson_basic_form	= jQuery('#lesson-basic-form_'+form_id);
				var mod_id = jQuery('#mod_id', lesson_basic_form).val();

				getsubFormatHTML(form_id,format,mod_id,lesson_id,subformat);
			}*/

			/* if associated files is clicked - check if format is uploaded : or save*/


			/*if(tabToShow == '#assocFiles_' +  form_id)
			{
				if (!formatactions(form_id, 0))
				{
					return false;
				}
			}
		}

		return true;
	});*/

	/* Number_of_attempts validation */
	/*jQuery('.numberofattempts').blur(function()	{
		var newval = parseInt(this.value);
		var id = this.id;
		var msg1;
		var lesson_basic_form	=	jQuery(this).closest('.lesson_basic_form');

		var original_attempt = jQuery('#no_attempts',lesson_basic_form).val();
		var max_attempt = jQuery('#max_attempt',lesson_basic_form).val();
		var form_id        = lesson_basic_form.attr('id').replace('lesson-basic-form_','');
		var conditionSatisfy = 1;
		var lessonform	=	jQuery('#tjlms_add_lesson_form_'+form_id);

		if (isNaN(newval))
		{
			jQuery('#lesson-basic-form_'+form_id+' input[name=\"jform[no_of_attempts]\"]').val(0);
		}
		else
		{
			jQuery('#lesson-basic-form_'+form_id+' input[name=\"jform[no_of_attempts]\"]').val(newval);
		}

		// Check if attempt is less than original attempt
		if (newval != 0 && newval < max_attempt)
		{
				msg1 = Joomla.JText._('COM_TJLMS_MAX_ATTEMPT_VALIDATION_MSG1').concat(max_attempt) + Joomla.JText._('COM_TJLMS_MAX_ATTEMPT_VALIDATION_MSG2');

				if (newval < 0)
				{
					msg1 = Joomla.JText._('COM_TJLMS_MAX_ATTEMPT_VALIDATION_MSG3');
				}

				jQuery('#system-message-container').html('');
				jQuery(".tjlms_form_errors .msg", lessonform).html(msg1);
				jQuery(".tjlms_form_errors", lessonform).show();
				jQuery('#'+id,lesson_basic_form).val(original_attempt);
				jQuery('#'+id,lesson_basic_form).focus();
				conditionSatisfy = 0;
		}

		if (conditionSatisfy == 1)
		{
			jQuery(".tjlms_form_errors", lessonform).hide();
		}

		// Check if attempt empty
		if (newval== '' && original_attempt == '')
		{
			jQuery('#'+id,lesson_basic_form).val(0);
		}
	});*/
});

function moduleactions(thiselement,action,originalvalue)
{
	var moduleform	= jQuery(thiselement).closest('.tjlms_module_form');
	var course_id	= jQuery("#course_id", moduleform).val();
	var mod_id	= jQuery("#mod_id", moduleform).val();
	var mod_title = '';

	/* populate the input task hidden field with module action*/
	jQuery("#task", moduleform).val(action);
	moduleform	= jQuery('#tjlms_module_form_'+mod_id);

	if (action == 'module.cancel')
	{
		/* Get original mod title*/
		if (originalvalue)
		{
			mod_title = originalvalue;
		}

		/* Added to refesh textbox data on cancel button*/
		jQuery("#title", moduleform).val(mod_title);
		hideeditModule(course_id,mod_id);
		return false;
	}
	else
	{
		jQuery(moduleform).ajaxSubmit({
			beforeSend: function() {

				var moduleTitle = jQuery.trim(jQuery('#title',moduleform).val());

				if(moduleTitle == '')
				{
					jQuery(".tjlms_module_errors .msg", moduleform).html(Joomla.JText._('COM_TJLMS_VALID_MODULE_TITLE'));
					jQuery(".tjlms_module_errors", moduleform).show();
					return false;
				}
			},
			success: function(responsejson)
			{
				var response = JSON.parse(responsejson);

				if (!response.success)
				{
					jQuery(".tjlms_module_errors .msg", moduleform).html(response.message);
					jQuery(".tjlms_module_errors", moduleform).show();
					Joomla.renderMessages(response.messages);
				}
				else
				{
					hideeditModule(course_id,mod_id);
					success_popup(course_id,responsejson);
				}

				return false;
			},
			error: function()
			{
				alert('Something went wrong');
			}
		});

	}
	return false;
}

/*Function triggered by clicking on the "Save and next" of the 1st Basic details tab of lesson */
function lessonactions(form_id)
{
	if(validate_lessonactions(form_id) == 1)
	{
		/*Ater validating the next "format" should be avtive*/
		jQuery('#tjlms_add_lesson_form_'+form_id + ' .nav-tabs li').removeClass('active');
		jQuery('#tjlms_add_lesson_form_'+form_id + ' .tab-content .tab-pane').removeClass('active');


		jQuery('#tjlms_add_lesson_form_'+form_id + ' a[href="#format_'+ form_id  +'"]').closest('li').addClass('active');
		jQuery('#tjlms_add_lesson_form_'+form_id + ' .tab-content #format_' + form_id).addClass('active');
	}
}

function validate_lessonactions(form_id)
{
		var lesson_space_msg = Joomla.JText._('COM_TJLMS_EMPTY_TITLE_ISSUE');
		var lesson_basic_form	= jQuery('#lesson-basic-form_'+form_id);
		var lessonform	=	jQuery('#tjlms_add_lesson_form_'+form_id);
		var return_var	=	1;

		/*Make the all form inputs Script safe*/
		formInputsWithoutScript(lesson_basic_form);

		if(document.formvalidator.isValid(lesson_basic_form))
		{
			var lessonTempName = jQuery('#lesson-basic-form_'+form_id+' input[name=\"jform[name]\"]').val();

			var rex = /(<([^>]+)>)/ig;
			var lessonNames = lessonTempName.replace(rex , "");
			jQuery('#lesson-basic-form_'+form_id+' input[name=\"jform[name]\"]').val(lessonNames);

			if (jQuery('#lesson-basic-form_'+form_id+' input[name=\"jform[name]\"]').val().trim() == '')
			{
				jQuery('#lesson-basic-form_'+form_id+' input[name=\"jform[name]\"]').val('');
				jQuery('#lesson-basic-form_'+form_id+' input[name=\"jform[name]\"]').focus();
				jQuery('#system-message-container').html('');
				jQuery(".tjlms_form_errors .msg", lessonform).html(lesson_space_msg);
				jQuery(".tjlms_form_errors", lessonform).show();
				return_var =  0;
				return return_var;
			}

			var courseStartDate, courseEndDate;
			courseStartDate = jQuery('#lesson-basic-form_'+form_id+' input[name=\"jform[start_date]\"]').val();
			courseEndDate = jQuery('#lesson-basic-form_'+form_id+' input[name=\"jform[end_date]\"]').val();

			if (courseStartDate != '' && isValidDate(courseStartDate) == false)
			{
				var invalidStartDate = Joomla.JText._('COM_TJLMS_INVALID_START_DATE');
				jQuery('#system-message-container').html('');
				jQuery(".tjlms_form_errors .msg", lessonform).html(invalidStartDate);
				jQuery(".tjlms_form_errors", lessonform).show();

				return false;
			}else if (courseEndDate != '' && isValidDate(courseEndDate) == false)	/*Validate course end date*/
			{
				var invalidEndDate = Joomla.JText._('COM_TJLMS_INVALID_END_DATE');
				jQuery('#system-message-container').html('');
				jQuery(".tjlms_form_errors .msg", lessonform).html(invalidEndDate);
				jQuery(".tjlms_form_errors", lessonform).show();

				return false;
			}

			/* Validate time_finished_duration < time_duration */
			if (courseStartDate != '' && courseEndDate != '' )
			{
				if (courseStartDate > courseEndDate)
				{
					jQuery('#lesson-basic-form_'+form_id+' input[name=\"jform[end_date]\"]').focus();

					jQuery('#system-message-container').html('');
					/** global: form_date_validation_failed */
					jQuery(".tjlms_form_errors .msg", lessonform).html(form_date_validation_failed);
					jQuery(".tjlms_form_errors", lessonform).show();
					return_var =  0;
					return return_var;
				}
			}

			// Check for only end date
			if (jQuery('#lesson-basic-form_'+form_id+' input[name=\"jform[end_date]\"]').val() != '')
			{
				var selectedDate = jQuery('#lesson-basic-form_'+form_id+' input[name=\"jform[end_date]\"]').val();
				var today = new Date();
				today.setHours(0, 0, 0, 0);
				var lessonEndDate = new Date(selectedDate);
				lessonEndDate.setHours(0, 0, 0, 0);

				if(lessonEndDate < today)
				{
					var msg = Joomla.JText._('COM_TJLMS_END_DATE_CANTBE_GRT_TODAY');
					jQuery('#system-message-container').html('');
					jQuery(".tjlms_form_errors .msg", lessonform).html(msg);
					jQuery(".tjlms_form_errors", lessonform).show();
					return 0;
				}
			}

			jQuery(lesson_basic_form).ajaxSubmit({
				datatype:'json',
				async:false,
				beforeSend: function() {
					jQuery('.loading',lesson_basic_form).show();
				},
				success: function(data)
				{
					var response = JSON.parse(data)
					var output	=	response.OUTPUT;
					var res	=	output[0];
					var msg	=	output[1];

					if(res == 1)
					{
						//Remove shown errors above the form if any
						//show_lessonform_error(0,'',lessonform);

						var lesson_id  = msg;
						//if create lesson- update the id field
						if(jQuery('#jform_id',lesson_basic_form).val() == 0) {
							jQuery('#jform_id',lesson_basic_form).val(lesson_id)
						}

						jQuery('#tjlms_add_lesson_form_'+form_id +' #format_'+ form_id + ' #lesson_id ').val(lesson_id);

						/*This is to get the lesson format html from respective plugin and show*/
						var format = jQuery('#format_' + form_id + ' #jform_format').val();
						var subformat = jQuery('#format_' + form_id + ' #jform_subformat').val();

						if(subformat == '')
						{
							lesson_format(format,form_id);
						}
						else
						{
							var mod_id = jQuery('#mod_id', lesson_basic_form).val();
							getsubFormatHTML(form_id,format,mod_id,lesson_id,subformat);
						}

						/** global: allow_associate_files */
						if (allow_associate_files == 1)
						{
							jQuery('#tjlms_add_lesson_form_'+form_id +' #assocFiles_'+ form_id + ' #lesson_id ').val(lesson_id);
							jQuery('#tjlms_add_lesson_form_'+form_id +' #assocFiles_'+ form_id + ' #selectFileLink').attr('href','index.php?option=com_tjlms&view=modules&layout=selectassociatefiles&lesson_id='+msg+'&tmpl=component&form_id='+form_id);
						}

						return_var	= 1 ;
					}
					else
					{
						jQuery('#system-message-container').html('');
						jQuery(".tjlms_form_errors .msg", lessonform).html(msg);
						jQuery(".tjlms_form_errors", lessonform).show();
						return_var = 0 ;
					}
				},
				complete: function() {
					jQuery('.loading',lesson_basic_form).hide();
				}
			});
		}
		else
		{
			jQuery('#system-message-container').html('');
			formTrackinvalidFields(lesson_basic_form, lessonform);
			return_var =  0;
		}

		if (return_var == 1)
		{
			jQuery(".tjlms_form_errors", lessonform).hide();
		}

		// always return false to prevent standard browser submit and page navigation
		return return_var;
}

function associateaction(formid, reload)
{
	var lesson_format_form = jQuery('#lesson-associatefile-form_'+formid);
	var lessonform = jQuery('#tjlms_add_lesson_form_'+formid);
	var s_msg = Joomla.JText._('COM_TJLMS_LESSON_UPDATED_SUCCESSFULLY');

	jQuery(lesson_format_form).ajaxSubmit({
		datatype:'json',
		 beforeSend: function() {
			jQuery('.loading',lesson_format_form).show();
		},
		success: function(data)
		{
			var response = JSON.parse(data)
			var output	=	response.OUTPUT;
			var res	=	output[0];
			var msg	=	output[1];

			if(res == 1)
			{
				console.log('success');

				if (reload == 1)
				{
					alert(s_msg);
					success_popup('1',msg);
				}
				else
				{
					/*Ater validating the next "format" should be avtive*/
					jQuery('#tjlms_add_lesson_form_'+formid + ' .nav-tabs li').removeClass('active');
					jQuery('#tjlms_add_lesson_form_'+formid + ' .tab-content .tab-pane').removeClass('active');

					jQuery('#tjlms_add_lesson_form_'+formid + ' a[href="#assessment_'+ formid  +'"]').closest('li').addClass('active');
					jQuery('#tjlms_add_lesson_form_'+formid + ' .tab-content #assessment_' + formid).addClass('active');
				}
			}
			else
			{
				show_lessonform_error(1,'something went wrong',lessonform);
			}
		},
		error: function() {
				show_lessonform_error(1,'something went wrong',lessonform);
		},
		complete: function() {
			jQuery('.loading',lesson_format_form).hide();
		}
	});
}

function validateResFormats(formid,format,subformat,media_id,lessonform)
{
	var function_to_call = 'validate'+format+subformat;
	var check_validation = eval(function_to_call)(formid,format,subformat,media_id);

	if (check_validation.check == '0')
	{
		show_lessonform_error(1, check_validation.message, lessonform);
		return false;
	}

	return true;
}

function formatactions(formid,reload)
{
	jQuery('.tmt_form_errors').hide();
	var lesson_format_form	=jQuery('#lesson-format-form_'+formid);
	var lessonform	=	jQuery('#tjlms_add_lesson_form_'+formid);
	var assessform	=	jQuery('#assignment-assessment-form_'+formid);

	var media_id = jQuery("#lesson_format_id",lesson_format_form).val();
	var format = jQuery("#jform_format",lesson_format_form).val();
	var subformat = jQuery("#jform_subformat",lesson_format_form).val();
	var lesson_id = jQuery("#lesson_id",lesson_format_form).val();
	var set_id = jQuery("input[name$='[set_id]']",lesson_format_form).val();

	if (!validateResFormats(formid,format,subformat,media_id,lessonform))
	{
		return false;
	}

	jQuery('.tjlms_form_errors').hide();

	// Submit file through Ajax
	jQuery(lesson_format_form).ajaxSubmit({
		datatype:'json',
		 beforeSend: function() {
			jQuery('.loadingsquares',lesson_format_form).show();
		},
		success: function(data)
		{
			var response = JSON.parse(data)
			var output	=	response.OUTPUT;
			var res	=	output['result'];
			var msg	=	output['msg'];

			if(res == 1)
			{
				if(output['media_id'])
				{
					jQuery("#lesson_format_id",lesson_format_form).val(output['media_id']);
				}

				jQuery('#tjlms_add_lesson_form_'+formid + ' .nav-tabs li').removeClass('active');
				jQuery('#tjlms_add_lesson_form_'+formid + ' .tab-content .tab-pane').removeClass('active');

				if(reload == 1)
				{
					success_popup('1',msg);
				}
				else
				{
					var assessmentTab = jQuery("input[name='assessment']").val();

					if (assessmentTab)
					{
						jQuery('#tjlms_add_lesson_form_'+formid + ' a[href="#assessment_'+ formid  +'"]').closest('li').addClass('active');
						jQuery('#tjlms_add_lesson_form_'+formid + ' .tab-content #assessment_' + formid).addClass('active');
						jQuery("input[name='jform[lesson_id]']", assessform).val(lesson_id);
						jQuery("input[name='jform[subformat]']", assessform).val(subformat);
						jQuery("input[name='jform[set_id]']", assessform).val(set_id);

						if(subformat == 'exercise')
						{
							jQuery(".tjlms_display_none").show();
							jQuery("input[name='jform[add_assessment]']", assessform).closest('.span6').hide();
							jQuery("input[name='jform[add_assessment]']", assessform).val(1);
						}
					}
					else
					{
						jQuery(".format_types",lesson_format_form).css('display','none');
						jQuery(format + "_subformat_options",lesson_format_form).css('display','none');

						jQuery('#tjlms_add_lesson_form_'+formid + ' a[href="#assocFiles_'+ formid  +'"]').closest('li').addClass('active');
						jQuery('#tjlms_add_lesson_form_'+formid + ' .tab-content #assocFiles_' + formid).addClass('active');
					}
				}
			}
			else
			{
				show_lessonform_error(1,'something went wrong',lessonform);
			}
			jQuery('.loadingsquares',lesson_format_form).hide();
			jQuery('.tjlms_form_errors').hide();
		},
		error: function()
		{
				show_lessonform_error(1,'something went wrong',lessonform);
				jQuery('.loadingsquares',lesson_format_form).hide();
		},
		complete: function() {
			jQuery('.loadingsquares',lesson_format_form).hide();
		}
	});

	// always return false to prevent standard browser submit and page navigation
	return false;
}
function tjlms_addnewquiz(ele){

		var parent_mod	=	jQuery(ele).closest('.mod_outer');
		var parent_mod_id	=  parent_mod.attr('id');
		var mod_id	=  parent_mod_id.replace('modlist_','');
		jQuery('#modlist_'+mod_id+' .add_newquiz_wizard').show();
		tjiframeLoaded(mod_id,true);
		jQuery('html,body').animate({
				 scrollTop: jQuery('#modlist_'+mod_id+' .add_newquiz_wizard').offset().top - 135},
				 'slow');
		jQuery("#"+parent_mod_id + " .module_add").hide();

}

function tjiframeLoaded(unique, newFrame) {
	var iFrameID
	if(newFrame){
		iFrameID =  jQuery('#modlist_' + unique + ' .add_newquiz_wizard iframe')[0];
	}else{
		iFrameID = document.getElementById('idIframe_'+unique);
	}

	if(iFrameID) {
		// here you can make the height, I delete it first, then I make it again
		//            iFrameID.height = "";
		//            iFrameID.height = iFrameID.contentWindow.document.body.scrollHeight + "px";
		var oBody = iFrameID.contentWindow.document.body;//idIframe.document.body;
		//console.log(oBody.scrollHeight + (oBody.offsetHeight - oBody.clientHeight));
		//console.log(oBody.scrollWidth + (oBody.offsetWidth - oBody.clientWidth));
		iFrameID.height = oBody.scrollHeight + (oBody.offsetHeight - oBody.clientHeight);
		iFrameID.width = oBody.scrollWidth + (oBody.offsetWidth - oBody.clientWidth);
	}
}

function hide_add_lesson_wizard(mod_id)
{
	var module_lms	=	'modlist_'+mod_id;
	jQuery('#'+module_lms + " .add_lesson_wizard").hide();
	jQuery('#'+module_lms + " .add_quizs_wizard").hide();
	jQuery("#"+module_lms + " .module_actions").show();
	location.reload(true);
}
function hide_add_quizs_wizard(mod_id)
{
	var module_lms	=	'modlist_'+mod_id;
	jQuery('#'+module_lms + " .add_newquiz_wizard").hide();
	jQuery('#'+module_lms + " .add_quizs_wizard").show();
	location.reload(true);
}

/*preview of the uploaded lesson format*/
function previewlesson(thispreviewlink,lesson_id)
{
	/** global: root_url */
	var lesson_preview_link = root_url+ "index.php?option=com_tjlms&view=lesson&tmpl=component&lesson_id=" +  lesson_id + "&mode=preview";
	var wwidth = jQuery(window).width();
		var wheight = jQuery(window).height();
		SqueezeBox.open(lesson_preview_link, {
			handler: 'iframe',
			closable:false,
			size: {x: wwidth, y: wheight},
			//iframePreload:true,
			sizeLoading: { x: wwidth, y: wheight },
			classWindow: 'tjlms_lesson_screen',
			classOverlay: 'tjlms_lesson_screen_overlay',
		});
}

/*close preview*/
function closelesson_preview(thisclosebtn, lesson_id)
{
	jQuery('#tjlmslessonpreviewfor_' +lesson_id+ ' iframe').remove();
	jQuery("div#tjlmslessonpreviewfor_"+lesson_id).remove();
	jQuery(thisclosebtn).siblings('a').show();
	jQuery(thisclosebtn).hide();
}

/*open htmlcontentbuilder*/
function openHtmlContentbuilder(thislink,action,modalId = 'openHtmlContent')
{
	var format_form	=	jQuery(thislink).closest('.lesson-format-form');
	var format_form_id	=	jQuery(format_form).attr('id');
	var form_id	=	format_form_id.replace('lesson-format-form_','')

	var lesson_id	=	jQuery('#lesson_id',format_form).val();
	/** global: root_url */
	var getModalUrl = root_url+
							"index.php?option=com_tjlms&view=lesson&form_id="+ form_id +"&lesson_id=" + lesson_id +
							"&layout=default_html&sub_layout=creator&pluginToTrigger=' . $plugin_name . '&action="+ action +
							"&tmpl=component" ;

	techjoomla.jQuery.ajax({
		url: getModalUrl,
		type: "GET",
		cache: false,
		success: function(response)
		{
			jQuery('#' + modalId + " .modal-dialog").addClass('modal-dialog-scrollable');
			jQuery('#' + modalId + " .modal-content .modal-body").html(response);
		}
	});
}

/*
*	Function helps to sort lessons pf each module.
*/
/*
function sortLessons()
{
	jQuery('.LessonsInModule').sortable({
		scroll: false,
		handle:'.lessonSortingHandler',
		items: "> li:not(.non-sortable-lesson-li)",
		start: function() {
			/*jQuery('.content-li').css({
				"z-index" : -1
			});*/

		/*},
		update: function() {

		/* get Id of the module on which the leeson is droped. */
		/*var mod_id = jQuery(this).parent().attr('id');

		var course_id	= jQuery("#course_id", jQuery(this).closest('.curriculum-container')).val();

		var lessonDiv='';
		var j=0;

			/* All Lessons ordering stored in lessonDiv along with their ID as the key. */
			/*jQuery('#'+mod_id).find('.LessonsInModule > li').each(function(j){
				j++
				lessonDiv += " "+jQuery(this).attr("id") + '=' + j + '&';
			});

			/* Ajax call to save the ordering. */
			/*jQuery.ajax({
						type: "POST",
						url: 'index.php?option=com_tjlms&task=lessons.saveSortingForLessons&course_id=' + course_id + '&mod_id='+mod_id,
						data: lessonDiv
			});
			hideDeleteIcon();
		}
	});
}*/


/*function hideDeleteIcon()
{
	jQuery(".LessonsInModule").each(function(){

		var res = jQuery(this).children('li');

		var mod_id = jQuery(this).parent('li').attr('id');

		jQuery("#"+ mod_id +" .moduledelete").hide();

		if (res.length == 0)
		{
			jQuery("#"+ mod_id +" .moduledelete").show();
		}
	});
}*/

/*
* This functions help to delete the module.
*/
/*function deleteModule(course_id,id)
{
	var comfirmDelete = confirm(Joomla.JText._('COM_TJLMS_SURE_DELETE_MODULE'));

	if(comfirmDelete == true)
	{
		jQuery.ajax({
				url: "index.php?option=com_tjlms&task=modules.deleteModule&course_id="+course_id+"&mod_id="+id,
				type: "GET",
				dataType: "json",
				success: function(msg)
				{
					if(msg == 1)
					{
						alert(delete_success_msg);
						success_popup(course_id,delete_success_msg);
						return true;
					}
					return false;
				}

			});
	}
	else
	return false;
}*/
function success_popup(course_id,msg)
{
	//jQuery('#tjlmsModal .modal-body').html(msg);
	//jQuery('#tjlmsModal').modal('show');
	//jQuery('#course-modules').load("index.php?option=com_tjlms&view=modules&tmpl=component&layout=default_lessons&format=raw&course_id="+course_id);
	location.reload(true);
}

/*
* This functions help to delete the Lesson and rearrange the ordering.
*/
function deletLesson(id,course_id,mod_id,format)
{
	/** global: delete_lesson_msg */
	var comfirm_delete=confirm(delete_lesson_msg);
	if(comfirm_delete==true)
	{
		jQuery.ajax({
				url: "index.php?option=com_tjlms&task=lessons.deletLesson&course_id="+course_id+"&lesson_id="+id+"&mod_id="+mod_id,
				type: "GET",
				dataType: "json",
				success: function(msg)
				{
					if(msg==1)
					{
						if(format=='tmtQuiz'){
							/** global: quiz_delete_success_msg */
							alert(quiz_delete_success_msg);
							success_popup(course_id,msg);
						}
						else
						{
							/** global: lesson_delete_success_msg */
							alert(lesson_delete_success_msg);
							success_popup(course_id,msg);
						}
						return true;
					}
					return false;

				}
			});
	}
	else
	{
			return false;
	}
}


function showHideEditLesson(mod_id,lesson_id,show)
{
	var lesson_edit_li	=	'lesson_edit_li_'+ lesson_id;

	if(show == 1){
		jQuery('#'+lesson_edit_li).show();
		tjiframeLoaded(lesson_id);
		jQuery('.editmodulelink', '#lessonlist_' + lesson_id).hide();
	}
	else
	{
		jQuery('#'+lesson_edit_li).hide();
		jQuery('.editmodulelink', '#lessonlist_' + lesson_id).show();
		location.reload(true);
	}

	if(assessment == 1)
	{
		jQuery('a[href*= "#assessment_"]').show();
	}
	else
	{
		jQuery('a[href*= "#assessment_"]').hide();
	}
}
function hide_edit_lesson_wizard(lesson_id)
{
	var lesson_edit_li	=	'lesson_edit_li_'+ lesson_id;
	jQuery('#'+lesson_edit_li).hide();
}

/*
function editModule(course_id,mod_id)
{

	if(mod_id > 0){
		var module_lms	=	'modlist_'+mod_id;
		jQuery('#'+module_lms + " .tjlms_module").hide();
		jQuery('#'+module_lms + " .module-edit-form").show();
		jQuery('.module-title').focus();
	}
	else{
		var add_modulefor_course	=	'add_module_form_'+course_id
		jQuery('#'+add_modulefor_course).show();
		jQuery('.add-module-div').hide();
		jQuery('.module-title').focus();
	}
	return false;
}*/
/*
function changeState(mod_id,state,name)
{
	var p_msg  = Joomla.JText._('COM_TJLMS_MODULE_PUBLISHED_SUCCESSFULLY');
	var up_msg = Joomla.JText._('COM_TJLMS_MODULE_UNPUBLISHED_SUCCESSFULLY');
	jQuery.ajax({
			url: "index.php?option=com_tjlms&task=modules.changeState&mod_id="+mod_id+"&state="+state,
			type: "GET",
			dataType: "json",
			success: function(msg)
			{
				if(msg == 1){
					if (state == 1)
					{
						alert(name+p_msg);
					}
					else
					{
						alert(name+up_msg);
					}

					success_popup(course_id,'');
					return true;
				}
				return false;
			},
			error: function()
			{
				alert('Something went wrong');
			}


		});
}*/

/*function hideeditModule(course_id,mod_id)
{
	if(mod_id > 0){
		var module_lms	=	'modlist_'+mod_id;
		jQuery('#'+module_lms + " .tjlms_module").show();
		jQuery('#'+module_lms + " .module-edit-form").hide();
	}
	else{
		var add_modulefor_course	=	'add_module_form_'+course_id;
		jQuery('#'+add_modulefor_course).hide();
		jQuery('.add-module-div').show();
	}
	return false;
}*/

function show_lessonform_error(show,msg,addlessonform)
{
	if(show	== 1)
	{
		jQuery(".tjlms_form_errors .msg", addlessonform).html(msg);
		jQuery(".tjlms_form_errors", addlessonform).html(msg);
		jQuery(".tjlms_form_errors", addlessonform).show();
		jQuery(".tjlms_form_errors .msg", addlessonform).show();
	}
	else
	{
		jQuery(".tjlms_form_errors", addlessonform).hide();
	}

}
function checkforuploadedformat(addlessonformatform)
{
	if(jQuery("#lesson_format_id",addlessonformatform).val() == 0)
		return false;
	else
		return true;
}
function checkifFileisPendingforUpload(format,format_lesson_form)
{

	var input_for_file	=   format+"_upload";
	var file	=	jQuery("#lesson_format #"+format + " #"+input_for_file, format_lesson_form)[0].files[0];
	return file;

}

/*Get the all the plugins for selected format
 * formatid can be video, tincanlrs or document
 * form_id is the unique id appended to id of each lesson form
 * */
function lesson_format(formatid,form_id,dispAssessment)
{
	var format_lesson_form	= jQuery('#lesson-format-form_'+form_id);
	jQuery('#lesson_format_msg',format_lesson_form).hide();
	jQuery('a[href = "#assessment_' + form_id + '"]').hide();

	if (dispAssessment == 1){
		jQuery('a[href = "#assessment_' + form_id + '"]').show();
	}

	if (dispAssessment == 1 || allow_associate_files){
		jQuery('.show_savenext_button').show();
	}
	else
	{
		jQuery('.show_savenext_button').hide();
	}

	var mod_les_arr = form_id.split("_");
	var mod_id = mod_les_arr[0];

	//var lesson_id = mod_les_arr[1];

	var lesson_id = jQuery('#lesson_id',format_lesson_form).val();;

	jQuery('#lesson_format .lesson_format', format_lesson_form).hide();

	/*If selected format has plugins written for it*/
	//if(jQuery.inArray(formatid, formatsWithTjplugin) > -1)
	{
		jQuery.ajax({
			url: "index.php?option=com_tjlms&task=modules.getSubFormats&lesson_format="+formatid,
			type: "GET",
			dataType: "json",
			beforeSend: function() {
				loadingImage(format_lesson_form);
				changeformatbtnstate(form_id,1);
			},
			success: function(data)
			{
				if(data.result == 1)
				{
					if(data.html == '')
					{
						jQuery('#'+formatid+' .lesson_format_msg',format_lesson_form).show();
					}
					else
					{
						var formatdata = data.html;
						var datahtml = '<select id="lesson_format'+formatid+'_subformat" name="lesson_format['+formatid+'_subformat]" class="class_'+formatid+'_subformat" onchange="getsubFormatHTML(\''+form_id+'\',\''+formatid+'\','+mod_id+','+lesson_id+',this.value);">';
						for (var i=0;i<formatdata.length;i++)
						{
							var selected_opt = '';

							datahtml += '<option value="'+formatdata[i].id+ '" ' +selected_opt+ '>'+formatdata[i].name+'</option>';
						}
						datahtml += '</select>';
						jQuery('#'+formatid+'_subformat_options',format_lesson_form).html(datahtml);

						if(formatdata.length == 1){
							jQuery('#'+formatid+'_subformat_options',format_lesson_form).parent().hide();
						}
						else{
							jQuery('#'+formatid+'_subformat_options',format_lesson_form).parent().show();
						}

						var subformat = jQuery('#lesson_format'+formatid+'_subformat',format_lesson_form).val();
						jQuery('#jform_subformat',format_lesson_form).val(subformat);

						getsubFormatHTML(form_id,formatid,mod_id,lesson_id,subformat);
					}
				}
				else
				{
					console.log('something went wrong11');
					//show_lessonform_error(1,'something went wrong',lessonform);
				}
			},
			error: function() {
				console.log('something went wrong');
			},
		});
	}
	// make the format link active
	jQuery('.format_types a',format_lesson_form).removeClass('active');
	jQuery('.format_types a.' + formatid, format_lesson_form).addClass('active');

	//populate the hidden field with selected format
	jQuery('#jform_format',format_lesson_form).val(formatid);

	/*jQuery('#lesson_format',format_lesson_form).show();

	// First hide all divs with class lesson_format
	// and then Only show div having id as selected format
	jQuery('#lesson_format .lesson_format',format_lesson_form).hide();
	jQuery('#lesson_format .lesson_format[id="'+formatid+'"]',format_lesson_form).show();*/
}

/*respective HTML to show depending on sub format...*/
function getsubFormatHTML(form_id,format,mod_id,lesson_id,subformat)
{
	var format_lesson_form = jQuery('#lesson-format-form_'+form_id);

	jQuery('#lesson-format-form_'+form_id + ' #jform_subformat').val(subformat);
	jQuery.ajax({
		/*url: "index.php?option=com_tjlms&task=modules.getSubFormatHTML&lesson_format="+format+"&lesson_subformat="+thiselementval+"&mod_id="+mod_id+"&lesson_id="+lesson_id+"&media_id="+formatMediaId,*/
		url: "index.php?option=com_tjlms&task=modules.getSubFormatHTML&lesson_id="+lesson_id+"&lesson_format="+format+"&lesson_subformat="+subformat+"&form_id=" + form_id,
		type: "GET",
		dataType: 'text',
		beforeSend: function() {
			loadingImage(format_lesson_form);
			changeformatbtnstate(form_id,1);
		},
		success: function(data) {
			var output = JSON.parse(data);
			var res	 =	output['result'];
			var html	=	output['html'];
			var assessment_param_value	=	output['assessment'];

			if (assessment_param_value == 1){
				jQuery('a[href = "#assessment_' + form_id + '"]').show();
				jQuery('input[name="assessment"]').remove();
				jQuery('.tjlms-wrapper #lesson_format').append('<input type="hidden" name="assessment" value="' + assessment_param_value + '" />');
			}
			else
			{
				jQuery('a[href = "#assessment_' + form_id + '"]').hide();
				jQuery('input[name="assessment"]').remove();
			}

			// Load any scripts that might be needed by this plugin
			if ('undefined' != typeof(output['scripts']))
			{
				jQuery.each( output['scripts'], function(index, value){
					jQuery.getScript(value);
				});
			}

			if(res == 1)
			{
				jQuery('#lesson_format',format_lesson_form).show();

				// First hide all divs with class lesson_format
				// and then Only show div having id as selected format
				jQuery('#lesson_format .lesson_format',format_lesson_form).hide();

				jQuery('.'+format+'_subformat',format_lesson_form).html(html);
				jQuery('.'+format+'_subformat',format_lesson_form).show();
				jQuery('.lesson_format#'+ format, format_lesson_form).show();
				hideImage(format_lesson_form);
			}
			else
			{
				console.log('something went wrong');
				show_lessonform_error(1,'something went wrong',format_lesson_form);
			}
		},
		error: function() {
			console.log('something went wrong');
			//show_lessonform_error(1,'something went wrong',format_lesson_form);
		},
		complete: function(xhr) {
			hideImage(format_lesson_form);
			changeformatbtnstate(form_id,0);
		}
	});
}

function formInputsWithoutScript(givenform)
{
	jQuery('input[type=text], textarea',givenform).each(
    function(){
        var input = jQuery(this);
		var noScriptVal = noScript(input.val())
        jQuery(this).val(noScriptVal);
		}
	);

}

/*TO remove the script tags from str*/
function noScript(str)
{
	var div = jQuery('<div>').html(str);
	div.find('script').remove();
	var noscriptStr = div.html();
	return noscriptStr;
}

/*Validate date in this format yyyy:mm:dd*/
function isValidDate(dateVal)
{
	dateVal = dateVal.split(" ");
	var validDate = dateVal[0].match(/^\d{4}[-](0?[1-9]|1[012])[-](0?[1-9]|[12][0-9]|3[01])$/);
	if (validDate !=  null)
	{
		return true;
	}
	return false;
}


function assessmentactions(formid, reload)
{
	var lesson_format_form	=jQuery('#lesson-format-form_'+formid);
	var lessonform	=	jQuery('#tjlms_add_lesson_form_'+formid);

	var media_id = jQuery("#lesson_format_id",lesson_format_form).val();
	var format = jQuery("#jform_format",lesson_format_form).val();
	var subformat = jQuery("#jform_subformat",lesson_format_form).val();
	jQuery(".format_types").css('display','none');

	var isValid = assessmentValidation(formid, reload);
	if(!isValid){
		return false;
	}

	var lesson_format_form = jQuery('#assignment-assessment-form_'+formid);
	var lessonform = jQuery('#tjlms_add_lesson_form_'+formid);
	var s_msg = Joomla.JText._('COM_TJLMS_LESSON_UPDATED_SUCCESSFULLY');

	/*PASSING THE LESSON_ID*/
	var format_lesson_form	= jQuery('#lesson-format-form_'+formid);
	var lesson_id = jQuery('#lesson_id',format_lesson_form).val();
	jQuery('#assess_lesson_id',lesson_format_form).val(lesson_id);
	/*PASSING THE LESSON_ID*/

		jQuery(lesson_format_form).ajaxSubmit({
		datatype:'json',
		beforeSend: function()
		{
			jQuery('.loading',lesson_format_form).show();
		},
		success: function(data)
		{
			var response = JSON.parse(data)
			var output	= response.OUTPUT;
			var res	=	output[0];
			var msg	=	output[1];

			if(res == 1)
			{
				console.log('success');
				jQuery('#tjlms_add_lesson_form_'+formid + ' .nav-tabs li').removeClass('active');
				jQuery('#tjlms_add_lesson_form_'+formid + ' .tab-content .tab-pane').removeClass('active');

				if (allow_associate_files == 1)
				{
					jQuery(".format_types",lesson_format_form).css('display','none');
					jQuery(format + "_subformat_options",lesson_format_form).css('display','none');

					jQuery('#tjlms_add_lesson_form_'+formid + ' a[href="#assocFiles_'+ formid  +'"]').closest('li').addClass('active');
					jQuery('#tjlms_add_lesson_form_'+formid + ' .tab-content #assocFiles_' + formid).addClass('active');
				}
				jQuery('#tjlms_add_lesson_form_'+formid + ' .tab-content #assocFiles_' + formid).addClass('active');

				if (reload == 1)
				{
					alert(s_msg);
					success_popup('1',msg);
				}
			}
			else
			{
				show_lessonform_error(1,'something went wrong',lessonform);
			}
		},
		error: function()
		{
			show_lessonform_error(1,'something went wrong',lessonform);
		},
		complete: function(xhr)
		{
			jQuery('.loading',lesson_format_form).hide();
		}
	});
}

function assessmentValidation(formid, reload)
{
	var lesson_assessment_form = jQuery('#assignment-assessment-form_'+formid);
	var lessonform = jQuery('#tjlms_add_lesson_form_'+formid);

	var format_lesson_form	= jQuery('#lesson-format-form_'+formid);
	var lesson_id = jQuery('#lesson_id',format_lesson_form).val();
	jQuery('#assess_lesson_id',lesson_assessment_form).val(lesson_id);
	var total_marks = parseInt(jQuery("#total-marks-value",lesson_assessment_form).text(),10);

	var check_validation = titleValidation(lesson_assessment_form);

	var assesment_tab_value = jQuery('#jform_assesment_tab0').is(":checked");

	if (check_validation.check == '0' && assesment_tab_value)
	{
		show_lessonform_error(1, check_validation.message, lessonform);
		return false;
	}
	else
	{
		jQuery('.tjlms_form_errors').html('');
		jQuery('.tjlms_form_errors').hide();

	}

	var total_added_score = 0;

	jQuery('#assessment_'+formid+' #assignment_review_params .com_tjlms_repeating_block_review').each(function(i,elem)
	{
		var paramVal = jQuery(elem).find("input[name$='[parameter_value]']").val();
		var paramWeightage = jQuery(elem).find("input[name$='[parameter_weightage]']").val();

		if (!isNaN(parseInt(paramVal, 10)) && !isNaN(parseInt(paramWeightage, 10)))
		{
			total_added_score = total_added_score + (parseInt(paramVal, 10) * parseInt(paramWeightage, 10));
		}
	});

	if (total_added_score != total_marks && assesment_tab_value)
	{
		jQuery('#jform_total_marks').val('');
		jQuery('#jform_total_marks', lesson_assessment_form).focus();
		message = Joomla.JText._('COM_TJLMS_ASSESMENT_SCORE_MSG');

		show_lessonform_error(1, message, lessonform);
		return false;
	}

	return true;
}

function titleValidation(lesson_assessment_form)
{
	var assessment_title = jQuery("input[name='jform[assessment_title]']",lesson_assessment_form).val();

	var res = {check: 1, message: ""};

	if(assessment_title == '')
	{
		jQuery('#jform_assessment_title').val('');
		jQuery('#jform_assessment_title', lesson_assessment_form).focus();
		res.check = 0;
		res.message = Joomla.JText._('COM_TJLMS_ASSESSMENT_FORM_TITLE');
		return res;
	}

	return res;
}

function lessonActionValidations(form_id)
{
	var lesson_basic_form	= jQuery('#lesson-basic-form_'+form_id);

	var lessonform	=	jQuery('#tjlms_add_lesson_form_'+form_id);

	var total_marks = parseInt(jQuery("input[name='jform[total_marks]']",lessonform).val(),10);

	var passing_marks = parseInt(jQuery("input[name='jform[passing_marks]']",lessonform).val(),10);

	var courseStartDate = jQuery("input[name='jform[start_date]']",lesson_basic_form).val();

	var courseEndDate = jQuery("input[name='jform[end_date]']",lesson_basic_form).val();

	var lessonTitle = jQuery('#lesson-basic-form_'+form_id+' input[name="jform[name]"]').val().trim();

	var res = {check: 1, message: ""};

	if (lessonTitle == '')
	{
		jQuery("input[name='jform[name]']",lesson_basic_form).val('');
		jQuery("input[name='jform[name]']",lesson_basic_form).focus();

		res.check = 0;
		res.message = Joomla.JText._('COM_TJLMS_EMPTY_TITLE_ISSUE');
		return res;
	}

	if (courseStartDate != '' && isValidDate(courseStartDate) == false)
	{
		res.check = 0;
		res.message = Joomla.JText._('COM_TJLMS_INVALID_START_DATE');
		return res;
	}

	if (courseEndDate != '' && isValidDate(courseEndDate) == false)	/*Validate course end date*/
	{
		res.check = 0;
		res.message = Joomla.JText._('COM_TJLMS_INVALID_END_DATE');
		return res;
	}

	/* Validate time_finished_duration < time_duration */
	if (courseStartDate != '' && courseEndDate != '' )
	{
		if (courseStartDate > courseEndDate)
		{
			jQuery("input[name='jform[end_date]']",lesson_basic_form).focus();
			res.check = 0;
			res.message = form_date_validation_failed;
			return res;
		}
	}

	// Check for only end date
	if (courseEndDate != '')
	{
		var selectedDate = jQuery("input[name='jform[end_date]']",lesson_basic_form).val();
		var today = new Date();
		today.setHours(0, 0, 0, 0);
		lessonEndDate = new Date(selectedDate);
		lessonEndDate.setHours(0, 0, 0, 0);

		if(lessonEndDate < today)
		{
			res.check = 0;
			res.message = Joomla.JText._('COM_TJLMS_END_DATE_CANTBE_GRT_TODAY');
			return res;
		}
	}

	if (passing_marks > total_marks )
	{
		jQuery('#jform_total_marks', lessonform).focus();
		res.check = 0;
		res.message = Joomla.JText._('COM_TJLMS_TEST_FORM_MSG_MIN_MARKS_HIGHER');

		return res;
	}

	return res;
}

	function addCloneReview(rId,rClass,formid)
	{
		var lesson_associatefile_form = jQuery('#assignment-assessment-form_'+formid);

			var str1 = 'length_review';
			length_variable = str1.concat(formid);

			var post = eval(length_variable);

			var pre = post;
			var post = eval("length_review" + formid + "=length_review" + formid + "+1");

			var removeButton="<div id='remove_btn_div"+pre+"' class='com_tjlms_review_remove_button span2'>";
			removeButton+="<button class='btn btn-small btn-danger' type='button' style='margin-left:70px;'id='remove"+pre+"'";
			removeButton+="onclick=\"removeCloneReview('com_tjlms_repeating_block_review"+pre+"','remove_btn_div"+pre+"','"+formid+"');\">";
			removeButton+="<i class=\"icon-minus icon-white\"></i></button>";
			removeButton+="</div>";

			var newElem=jQuery('#'+rId+pre,lesson_associatefile_form).clone().attr('id',rId+post);

			newElem.find(
				'input[name=\"jform[review][' + pre + '][parameter_name]\"]').attr(
				{'name': 'jform[review][' + post + '][parameter_name]'}).val('');
			newElem.find(
				'input[name=\"jform[review][' + pre + '][parameter_value]\"]').attr(
				{'name': 'jform[review][' + post + '][parameter_value]','readonly':false}).val('');
			newElem.find(
				'input[name=\"jform[review][' + pre + '][parameter_weightage]\"]').attr(
				{'name': 'jform[review][' + post + '][parameter_weightage]'}).val('');
			newElem.find(
				'select[name=\"jform[review][' + pre + '][parameter_type]\"]').attr(
				{'name': 'jform[review][' + post + '][parameter_type]','value':'' });
			newElem.find(
				'textarea[name=\"jform[review][' + pre + '][parameter_description]\"]').attr(
				{'name': 'jform[review][' + post + '][parameter_description]'}).val('');
			newElem.find(
				'input[name=\"jform[review][' + pre + '][review_id]\"]').attr(
				{'name': 'jform[review][' + post + '][review_id]'}).val('');

			var lastchild = jQuery(lesson_associatefile_form).find('.com_tjlms_repeating_block_review').last();

			lastchild.after(newElem);

			//jQuery('#'+rId+pre, lesson_associatefile_form).after(removeButton);
			lastchild.after(removeButton);

			jQuery('#review_description_'+post).text('');
			jQuery('#review_description_'+post).val('');
			jQuery('#review_value_'+post).val('');
			jQuery('#review_weightage_'+post).val('');
			jQuery('#review_name_'+post).val('');
			jQuery('#review_id_'+post).val('');


			/*incremnt id*/
			newElem.find('input[id=\"review_name_'+pre+'\"]').attr({'id': 'review_name_'+post,'value':''});
			newElem.find('input[id=\"review_value_'+pre+'\"]').attr({'id': 'review_value_'+post,'value':''});
			newElem.find('input[id=\"review_weightage_'+pre+'\"]').attr({'id': 'review_weightage_'+post,'value':''});
			newElem.find('select[id=\"review_type_'+pre+'\"]').attr({'id': 'review_type_'+post,'value':'' });
			newElem.find('textarea[id=\"review_description_'+pre+'\"]').attr({'id': 'review_description_'+post,'value':'' });
			newElem.find('input[id=\"review_id'+pre+'\"]').attr({'id': 'review_id'+post,'value':'' });

	}

	function removeCloneReview(rId, r_btndivId,formid)
	{
		var lesson_associatefile_form	=jQuery('#assignment-assessment-form_'+formid);

		jQuery('#'+rId, lesson_associatefile_form).remove();
		jQuery('#'+r_btndivId, lesson_associatefile_form).remove();

	}




var tjlms = {
	validatedates : function (startdateField, enddateField)
	{
		var startdate = startdateField.val();
		var enddate = enddateField.val();
		var check = 1;

		/* Validate time_finished_duration < time_duration */
		if (startdate != '' && enddate != '' )
		{
			if (startdate > enddate)
			{
				jQuery(startdateField).focus().addClass('tjinvalid');
				jQuery("label[for='"+jQuery(startdateField).attr('id')+"']").addClass('tjinvalid');

				check = 0;
				Joomla.renderMessages({'error': [Joomla.JText._('COM_TJLMS_DATE_RANGE_VALIDATION')] });
			}
		}
		else if (enddate != '')
		{
			var today = new Date();
			today.setHours(0, 0, 0, 0);

			tempEnddate = new Date(enddate);
			tempEnddate.setHours(0, 0, 0, 0);

			if(tempEnddate < today)
			{
				jQuery(enddateField).focus().addClass('tjinvalid');
				jQuery("label[for='"+jQuery(enddateField).attr('id')+"']").addClass('tjinvalid');

				check = 0;
				Joomla.renderMessages({'error': [Joomla.JText._('COM_TJLMS_END_DATE_CANTBE_GRT_TODAY')] });
			}
		}

		return check;
	},
	submitForm: function(formToSubmit, nextStepWizard, callback)
	{
		var resData = '';
		var  formData = new FormData(jQuery(formToSubmit)[0]);
		jQuery.ajax({
			url: jQuery(formToSubmit).attr("action"),
			type: "POST",
			data: formData,
			contentType: false,
			cache: false,
			processData:false,
			success: function(data)
			{
				callback(data, nextStepWizard);
				jQuery('#loading', formToSubmit).hide();
			}
		});
	},
	basicform: {
		save: function(lessonbasicform, nextStepWizard)
		{
			if (!tjlms.basicform.validate(lessonbasicform))
			{
				return false;
			}

			if (tjlms.submitForm(lessonbasicform, nextStepWizard, tjlms.basicform.onAfterFormSave))
			{
				return true;
			}

			return false;
		},

		validate: function(lessonbasicform)
		{
			if (!document.formvalidator.isValid(lessonbasicform))
			{
				return false;
			}

			var startdateField = jQuery("#jform_start_date",lessonbasicform);
			var enddateField = jQuery("#jform_end_date",lessonbasicform);

			var checkDates = tjlms.validatedates(startdateField, enddateField);

			if (!checkDates)
			{
				return false;
			}

			return true;
		},
		onAfterFormSave: function(data, nextStepWizard)
		{
			var response = JSON.parse(data)
			if (response.success)
			{
				var resData = JSON.parse(response.data)

				if (resData.lesson_id)
				{
					jQuery("input[name='jform[id]']").val(resData.lesson_id);
				}

				nextStepWizard.removeClass('disabled').trigger('click');
			}

			return true;
		}
	},
	trackingform: {
		save:	function(lessontrackingform, nextStepWizard)
		{
			if (!tjlms.trackingform.validate(lessontrackingform))
			{
				return false;
			}

			if (tjlms.submitForm(lessontrackingform, nextStepWizard, tjlms.trackingform.onAfterFormSave))
			{
				return true;
			}

			return false;
		},
		validate:	function(lessontrackingform){
			return true;
		},
		onAfterFormSave: function(data, nextStepWizard)
		{
			var response = JSON.parse(data)
			if (response.success)
			{
				var resData = JSON.parse(response.data)

				if (resData.lesson_id)
				{
					jQuery("input[name='jform[id]']").val(resData.lesson_id);
				}

				nextStepWizard.removeClass('disabled').trigger('click');
			}
			return true;
		}
	},
	formatform: {
		init: function(){


		}
	},
	assessmentform: {
		init: function() {
			jQuery(window).load(function () {
				jQuery(document).on('subform-row-add', function(event, row){

					var assessForm =  jQuery(row).closest("form.assignment_assessment_form");
					var form_id = jQuery(assessForm).attr("id").replace("assignment-assessment-form_",'');
					jQuery(row).find('.param_value').attr('onBlur', "tjlms.assessmentform.calculateTotal('"+ form_id +"')");

					jQuery(row).find('.param_weightage').attr('onBlur', "tjlms.assessmentform.calculateTotal('"+ form_id +"')");
				})


				jQuery(".param_weightage, .param_value").blur(function() {
					var assessForm =  jQuery(this).closest("form.assignment_assessment_form");
				 	var form_id = jQuery(assessForm).attr("id").replace("assignment-assessment-form_",'');
				 	tjlms.assessmentform.calculateTotal(form_id);
				});
			});
		},

		calculateTotal: function(form_id)
		{
			var total_marks = 0;
			var assessForm =  jQuery("form#assignment-assessment-form_" + form_id);
			jQuery(".subform-repeatable-group",assessForm).each(function(){
				total_marks += jQuery('.param_value', this).val() *  jQuery('.param_weightage', this).val();
			});

			jQuery("#jform_total_marks", assessForm).val(total_marks);
		},

		validate: function(form_id)
		{
			if (!document.formvalidator.isValid('#assignment-assessment-form_' + form_id))
			{
				return false;
			}

			var assessForm =  jQuery("#assignment-assessment-form_" + form_id);
			var lessonFormatForm	=jQuery('#lesson-format-form_'+form_id);
			var qztype = jQuery("#qztype", lessonFormatForm).val();
			var count = jQuery(".subform-repeatable-group", assessForm).length;

			if(count)
			{
				tjlms.assessmentform.calculateTotal(form_id);
			}

			var total_marks = parseInt(jQuery("#jform_total_marks", assessForm).val());
			var passing_marks = parseInt(jQuery("#jform_passing_marks", assessForm).val());

			var formatTotalMarks = parseInt(jQuery("input[name$='[total_marks]']", lessonFormatForm).val());
			var formatPassingMarks = parseInt(jQuery("input[name$='[passing_marks]']", lessonFormatForm).val());


			if (passing_marks > total_marks)
			{
				var lessonform	=	jQuery('#tjlms_add_lesson_form_'+form_id);
				show_lessonform_error(1, Joomla.JText._('COM_TJLMS_ASSESSMENT_MARKS_VALIDATION_MSG'), lessonform);
				return false;
			}

			if (passing_marks <= 0 && count)
			{
				jQuery("input[name='jform[passing_marks]']",assessForm).val('');
				jQuery("input[name='jform[passing_marks]']",assessForm).focus();

				var lessonform	=	jQuery('#tjlms_add_lesson_form_'+form_id);
				show_lessonform_error(1, Joomla.JText._('COM_TMT_TEST_FORM_NON_ZERO_VALUE_MARKS'), lessonform);
				return false;
			}

			if(qztype == 'exercise' && count)
			{
				if (total_marks != formatTotalMarks)
				{
					var lessonform	=	jQuery('#tjlms_add_lesson_form_'+form_id);
					show_lessonform_error(1, Joomla.JText._('COM_TJLMS_ASSESSMENT_TOTAL_MARKS_EQUAL'), lessonform);
					return false;
				}

				if(passing_marks != formatPassingMarks)
				{
					var lessonform	=	jQuery('#tjlms_add_lesson_form_'+form_id);
					show_lessonform_error(1, Joomla.JText._('COM_TJLMS_ASSESSMENT_PASSING_MARKS_EQUAL'), lessonform);
					return false;
				}
			}

			return true;
		},

		save: function(form_id, page_reload)
		{
			if (!tjlms.assessmentform.validate(form_id))
			{
				return false;
			}

			var assessForm =  jQuery("#assignment-assessment-form_" + form_id);
			var lessonform	=	jQuery('#tjlms_add_lesson_form_'+form_id);
			var lesson_format_form	=jQuery('#lesson-format-form_'+form_id);
			var s_msg = Joomla.JText._('COM_TJLMS_LESSON_UPDATED_SUCCESSFULLY');
			var add_assessment = parseInt(jQuery("input[name='jform[add_assessment]']:checked", assessForm).val());
			var subFormat = jQuery("input[name='lesson_format[subformat]']", lesson_format_form).val();

			jQuery(assessForm).ajaxSubmit(
			{
				datatype:'json',
				beforeSend: function()
				{
					jQuery('.loading',assessForm).show();
				},
				success: function(data)
				{
					var response = JSON.parse(data)
					var output	= response.OUTPUT;
					var res	=	output[0];
					var msg	=	output[1];
					var set_id = output[2];

					if(res == -1)
					{
						show_lessonform_error(1, Joomla.JText._('COM_TJLMS_ASSESSMENT_CANT_SAVE'), lessonform);

						return false;
					}

					if(add_assessment == 0 && subFormat != 'exercise')
					{
						var res = 1;
						var msg = '';
						var set_id = 0;
					}

					if(res == 1)
					{
						jQuery('#tjlms_add_lesson_form_'+form_id + ' .nav-tabs li').removeClass('active');
						jQuery('#tjlms_add_lesson_form_'+form_id + ' .tab-content .tab-pane').removeClass('active');
						jQuery("input[name='jform[set_id]']", assessForm).val(set_id);

						if (page_reload == 0)
						{
							jQuery('#tjlms_add_lesson_form_'+form_id + ' a[href="#assocFiles_'+ form_id  +'"]').closest('li').addClass('active');
							jQuery('#tjlms_add_lesson_form_'+form_id + ' .tab-content #assocFiles_' + form_id).addClass('active');
						}

						if (page_reload == 1)
						{
							alert(s_msg);
							success_popup('1',msg);
						}
					}
					else
					{
						show_lessonform_error(1,'something went wrong',lessonform);
					}
				},
				error: function()
				{
					show_lessonform_error(1,'something went wrong',lessonform);
				},
				complete: function(xhr)
				{
					jQuery('.loading',assessForm).hide();
				}
			});

			return true;
		},

	},
}
