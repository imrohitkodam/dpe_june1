jQuery(window).on('load', function() {
	jQuery('.inputbox.radio').removeClass('t3onoff').addClass('btn-group');
});

jQuery(document).ready(function(){
		/*
		* get char remianing for short desc
		*/
		jQuery( "#jform_short_desc" ).keyup(function() {
			var desc_length = jQuery("#jform_short_desc").val().length;

			/** global: lesson_characters_allowed */
			var characters = lesson_characters_allowed;
			var sht_desc_length = characters - desc_length;

			if(sht_desc_length <= 0)
			{
				sht_desc_length = 0;

				jQuery('#max_body1').html('<span class="disable"> '+sht_desc_length+' </span>');
				document.getElementById('jform_short_desc').value = document.getElementById('jform_short_desc').value.substr(0,characters);
				jQuery('#sBann1').show();
			}
			else
			{
				jQuery( '#max_body1' ).html(sht_desc_length);
				jQuery('#sBann1').show();
			}
		});

		jQuery(".assign-date").hide();
		jQuery("#assign").hide();

		jQuery('#select-option').change(function()
		{
			if(this.checked) {
				jQuery("#assign").show();
				jQuery(".assign-date").show();
				jQuery("#enrol").hide();
				jQuery(".assign-date").css('display', 'inline-block');
			}else
			{
				jQuery("#assign").hide();
				jQuery("#enrol").show();
				jQuery(".assign-date").hide();
			}
		});

	jQuery('.tjlms_add_lesson_form label').hover(function(){

		var offset = jQuery(this).offset();
		var tooltipContainer = jQuery(this).attr('aria-describedby');
		jQuery("#" + tooltipContainer).css('left',offset.left + 10);

		var contents = jQuery("#" + tooltipContainer + ' .ui-tooltip-content').html();
		jQuery("#" + tooltipContainer + ' .ui-tooltip-content').remove();

		jQuery("#" + tooltipContainer).text('');
		var html = "<div class='ui-tooltip-content'>" + dencodeEntities(contents) + "</div>";
		jQuery("#" + tooltipContainer).append(html);
	});
});

function dencodeEntities(s) {
	return jQuery("<div/>").html(s).text();
}

function getUploadedfilename() {
	var filename ='';
	var format = jQuery('#jform_format option:selected').val();
	var fileobj = jQuery('#'+ format + ' input[type="file"]')[0].files[0];
	if(fileobj != undefined) {
	  filename = fileobj.name;
	}
	return filename;
}

function setUploaderroSuccess(format, success , error, msg) {

	jQuery('#lesson_format #'+format+' .format_upload_error').hide();
	jQuery('#lesson_format #'+format+' .format_upload_error').html('');
	jQuery('#lesson_format #'+format+' .format_upload_success').hide();
	jQuery('#lesson_format #'+format+' .format_upload_success').html('');

	if(success == 1)
	{
		jQuery('#lesson_format #'+format+' .format_upload_success').show();
		jQuery('#lesson_format #'+format+' .format_upload_success').html(msg);
	}
	else if(error == 1)
	{
		jQuery('#lesson_format #'+format+' .format_upload_error').show();
		jQuery('#lesson_format #'+format+' .format_upload_error').html(msg);

	}
}

function checkforalpha(el,allowed_ascii)
{
	allowed_ascii= (typeof allowed_ascii === 'undefined') ? '' : allowed_ascii;
	var i =0 ;
	for(i=0;i<el.value.length;i++){
		if((el.value.charCodeAt(i) <= 47 || el.value.charCodeAt(i) >= 58) || (el.value.charCodeAt(i) == 45 )){
			if(allowed_ascii !=el.value.charCodeAt(i) ){
				/** global: numeric_value_validation_msg */
				alert(numeric_value_validation_msg);
				el.value = el.value.substring(0,i);
				 break;
			}
		}
	}
}


/*
*	to make duration drop down readable for unlimited plans
*/
function checkForUnlimited(time_measure,time_measure_id)
{
	//slipt the ID
	var split_id = time_measure_id.split('_');
	var textbox;

	if(time_measure=='unlimited')
	{
		jQuery('#subs_plan_duration_'+split_id[4]).val(1);
		jQuery('#subs_plan_duration_'+split_id[4]).hide();
		textbox = document.getElementById('subs_plan_duration_'+split_id[4]);
		textbox.readOnly = "readonly";
	}
	else
	{
		jQuery('#subs_plan_duration_'+split_id[4]).show();
		textbox = document.getElementById('subs_plan_duration_'+split_id[4]);
		textbox.readOnly = false;
	}
}
//repective input to show depending on video format if lesson format is video...
/*function getVideoFormat1(subformat,thiselement)
{
	var format_lesson_form = jQuery(thiselement).closest('.lesson-format-form');
	var thiselementval = jQuery(thiselement).val();

	if(thiselementval != 'upload')
	{
		jQuery('.video_subformat #video_package',format_lesson_form).hide();
		jQuery('.video_subformat #video_textarea',format_lesson_form).show();
	}
	else
	{
		jQuery('.video_subformat #video_package',format_lesson_form).show();
		jQuery('.video_subformat #video_textarea',format_lesson_form).hide();
	}
}*/
/*respective HTML to show depending on video sub format...*/
/*function getVideosubFormat(thiselement)
{
	var format_lesson_form = jQuery(thiselement).closest('.lesson-format-form');
	var thiselementval = jQuery(thiselement).val();
	jQuery('[id^="video_subformat_"]',format_lesson_form).hide();
	jQuery('#video_subformat_'+thiselementval,format_lesson_form).show();
}*/
/*respective input to show depending on lesson format...
function lesson_format(formatid,form_id)
{
	var format_lesson_form	= jQuery('#lesson-format-form_'+form_id);
	seteditformformat(formatid,format_lesson_form);
}


function seteditformformat(formatid,format_lesson_form)
{

	// make the format link active
	jQuery('.format_types a',format_lesson_form).removeClass('active');
	/*var format_datatype	= formatid.toLowerCase().replace(/^[\u00C0-\u1FFF\u2C00-\uD7FF\w]|\s[\u00C0-\u1FFF\u2C00-\uD7FF\w]/g, function(letter){
							return letter.toUpperCase();});

	jQuery('a.' + formatid, format_lesson_form).addClass('active');

	//populate the hidden filed with selected format
	jQuery('#jform_format',format_lesson_form).val(formatid);

	jQuery('#lesson_format',format_lesson_form).show();
	jQuery('#lesson_format .lesson_format',format_lesson_form).hide();

	if(formatid == 'scorm'){
		jQuery('#lesson_format .lesson_format[id="'+formatid+'"] #passing_score',format_lesson_form).show();
		jQuery('#lesson_format .lesson_format[id="'+formatid+'"] #grademethod',format_lesson_form).show();
	}

	if( formatid == 'tjscorm' || formatid == 'tinCan'){
		formatid= 'scorm';
		jQuery('#lesson_format .lesson_format[id="'+formatid+'"] #passing_score',format_lesson_form).hide();
		jQuery('#lesson_format .lesson_format[id="'+formatid+'"] #grademethod',format_lesson_form).hide();
	}
	jQuery('#lesson_format .lesson_format[id="'+formatid+'"]',format_lesson_form).show();
}

/*Get the all the plugins for selected format
 * formatid can be video, tincanlrs or document
 * form_id is the unique id appended to id of each lesson form
 *
function get_lesson_format(formatid,form_id)
{
	var format_lesson_form	= jQuery('#lesson-format-form_'+form_id);
	jQuery.ajax({
		url: "index.php?option=com_tjlms&task=modules.getSubFormats&lesson_format="+formatid,
		type: "GET",
		dataType: "json",
		beforeSend: function() {
			jQuery('.loading',format_lesson_form).show();
		},
		success: function(data) {
			if(data.result == 1)
			{
				if(data.html == '')
				{
					jQuery('#'+formatid+' .lesson_format_msg',format_lesson_form).show();
				}
				else
				{
					formatdata = data.html;
					datahtml = '<select id="lesson_format'+formatid+'_subformat" name="lesson_format['+formatid+'_subformat]" class="class_'+formatid+'_subformat" onchange="getsubFormat(this,\''+formatid+'\');">';
					for (i=0;i<formatdata.length;i++)
					{
						datahtml += '<option value="'+formatdata[i].id+'" selected>'+formatdata[i].name+'</option>';
					}
					datahtml += '</select>';
					jQuery('#'+formatid+'_subformat_options',format_lesson_form).html(datahtml);

					if(formatdata.length == 1){
						jQuery('#'+formatid+'_subformat_options',format_lesson_form).parent().hide();
					}
					else{
						jQuery('#'+formatid+'_subformat_options',format_lesson_form).parent().show();
					}
					getsubFormat(jQuery('#lesson_format'+formatid+'_subformat',format_lesson_form),formatid);
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
			//show_lessonform_error(1,'something went wrong',lessonform);
		},
		complete: function(xhr) {
			jQuery('.loading',format_lesson_form).hide();
		}

	});
	// make the format link active
	jQuery('.format_types a',format_lesson_form).removeClass('active');
	var format_datatype	= formatid.toLowerCase().replace(/^[\u00C0-\u1FFF\u2C00-\uD7FF\w]|\s[\u00C0-\u1FFF\u2C00-\uD7FF\w]/g, function(letter){
							return letter.toUpperCase();});

	jQuery('a.' + formatid, format_lesson_form).addClass('active');

	//populate the hidden filed with selected format
	jQuery('#jform_format',format_lesson_form).val(formatid);

	jQuery('#lesson_format',format_lesson_form).show();
	jQuery('#lesson_format .lesson_format',format_lesson_form).hide();

	if( formatid == 'tjscorm' || formatid == 'tinCan'){
		formatid= 'scorm';
		jQuery('#lesson_format .lesson_format[id="'+formatid+'"] #passing_score',format_lesson_form).hide();
		jQuery('#lesson_format .lesson_format[id="'+formatid+'"] #grademethod',format_lesson_form).hide();
	}
	jQuery('#lesson_format .lesson_format[id="'+formatid+'"]',format_lesson_form).show();
}
*/
function remove_file(id)
{

	var required_id = parseInt(id.replace('remove_file_btn',''));
	jQuery('#tr_'+required_id).remove();
}

function remove_selected_files_btn()
{
	jQuery('.remove_selected_file').each(function(){
			if(jQuery(this).is(":checked"))
			{
				var table_id= jQuery(this).attr('id');
				var required_id = parseInt(table_id.replace('remove_selected_file',''));
				jQuery('#tr_'+required_id).remove();
			}
	});

}
function getDoc(frame) {
	 var doc = null;

	 // IE8 cascading access check
	 try {
		 if (frame.contentWindow) {
			 doc = frame.contentWindow.document;
		 }
	 } catch(err) {
	 }

	 if (doc) { // successful getting content
		 return doc;
	 }

	 try { // simply checking may throw in ie8 under ssl or mismatched protocol
		 doc = frame.contentDocument ? frame.contentDocument : frame.document;
	 } catch(err) {
		 // last attempt
		 doc = frame.document;
	 }
	 return doc;
}

/*Function to check if a file with valid extension has been uploaded for lesson*/
function validate_file(thisfile,mod_id,subformat)
{
		/*remove status bar if already appneded*/
		jQuery(thisfile).closest('.controls').children( ".statusbar" ).remove();

		/*remove missing file alert*/
		jQuery('.tjlms_form_errors .msg').html('').hide();

		/*Disable create lesson and add quiz button*/
		jQuery(".btn-add-lesson").attr("disabled",true);
		jQuery(".btn-add-lesson").css("pointer-events", "none");

		var format_lesson_form	= jQuery(thisfile).closest('form');

		var format	= jQuery('#jform_format',format_lesson_form).val();

		/* Hide all alerts msgs */
		var obj = jQuery(thisfile);
		var status = new createStatusbar(obj, format); //Using this we can set progress.


		/* Get uploaded file object */
		var uploadedfile	=	jQuery(thisfile)[0].files[0];

		/* Get uploaded file name */
		var filename = uploadedfile.name;

		/* pop out extension of file*/
		var ext = filename.split('.').pop().toLowerCase();

		/* Get valid extension availiable for chosen lesson format*/
		if (format != 'associate')
		{
			var valid_extensions_str	= jQuery('#lesson_format #'+format+' [data-subformat="'+subformat+'"] .valid_extensions',format_lesson_form).val();

			var valid_extensions = valid_extensions_str.split(',');

			/* If extension is not in provided valid extensions*/
			if(jQuery.inArray(ext, valid_extensions) == -1)
			{
				/** global: nonvalid_extension */
				var msg = Joomla.JText._('COM_TJLMS_UPLOAD_EXTENSION_ERROR');
				status.setMsg(msg,'alert-error');
				return false;
			}
		}


		/* if file size is greater than allowed*/
		/*if((lesson_upload_size * 1024 * 1024) < uploadedfile.size)
		{
			var msg = Joomla.JText._('COM_TJLMS_UPLOAD_SIZE_ERROR').replace("%s", lesson_upload_size);
			status.setMsg(filesize_exceeded,'alert-error');
			return false;
		}*/

		/* IF evrything is correct so far, popolate file name in fileupload-preview*/

		var file_name_container	=	jQuery(".fileupload-preview",jQuery(thisfile).closest('.fileupload-new'));

		//	var file_name_container	=	jQuery('#'+format+' #'+format+'_subformat .fileupload-preview',format_lesson_form);
		jQuery(file_name_container).show();
		jQuery(file_name_container).text(filename);

		startUploading(uploadedfile,format,subformat,format_lesson_form,status,thisfile);

		return true;

}

function getReportdata(page, colToShow, limit, sortCol, sortOrder, action)
{
	var filter = [];
	var filterTitle = [];
	var filterValue;
	var filterName;
	jQuery('#report-containing-div .alert').remove();

	jQuery('th .filter-input').each(function(index) {
		filterValue = jQuery(this).val();
		filterName = jQuery(this).attr('id');
		filterName = filterName.replace('search-filter-','');

		if (filterName == 'id' || filterName == 'attempt')
		{
			if (isNaN(filterValue))
			{
				var msg = Joomla.JText._('COM_TJLMS_NO_NEGATIVE_NUMBER');
				alert(msg);

				return false;
			}
		}

		var ifInColsToShow = jQuery.inArray(filterName, colToShow);

		if (filterValue != '' && ifInColsToShow != -1)
		{
			filterTitle.push(filterName);
			filter.push(filterValue);
		}
	});

	var fromDate = jQuery('#attempt_begin').val();
	var toDate = jQuery('#attempt_end').val();
	var invalidDate = Joomla.JText._('COM_TJLMS_REPORTS_VALID_DATE');

	if (action == 'datesearch')
	{
		if (!isValidDate(fromDate))
		{
			jQuery('#report-containing-div').prepend("<div class='alert alert-error'><p>"+invalidDate+"</p></div>");
			return false;
		}

		if (!isValidDate(toDate))
		{
			jQuery('#report-containing-div').prepend("<div class='alert alert-error'><p>"+invalidDate+"</p></div>");
			return false;
		}
	}

	if (fromDate)
	{
		filterName = 'fromDate';
		filterValue = fromDate;
		filterTitle.push(filterName);
		filter.push(filterValue);
	}

	if (toDate)
	{
		filterName = 'toDate';
		filterValue = toDate;
		filterTitle.push(filterName);
		filter.push(filterValue);
	}

	jQuery.ajax({
		url:"index.php?option=com_tjlms&task=reports.getFilterData",
		type: "POST",
		dataType: "json",
		data:{filterValue:filter, filterName:filterTitle, limit:limit, page:page, colToShow:colToShow, sortCol:sortCol, sortOrder:sortOrder,action:action},
		success: function(data)
		{
			jQuery('#report-containing-div').html('');
			jQuery('.user-report').remove();
			jQuery('#report-containing-div').html(data.html);
			jQuery('#totalRows').val(data.total_rows);
			jQuery('#report-containing-div select').chosen();
			getPaginationBar(action, data.total_rows);
		}
	});
}

function getFilterdata(page, event, action, sortCol, sortOrder)
{
	sortCol = typeof sortCol !== 'undefined' ? sortCol : '';
	sortOrder = typeof sortOrder !== 'undefined' ? sortOrder : '';

	var isPaginationBarHidden = jQuery("#pagination-demo").is(':hidden');

	if (isPaginationBarHidden == 0 && (typeof page == 'undefined' || page == -1))
	{
		page = jQuery('#pagination-demo li.active a').html();
	}

	var limit = jQuery('#reportPagination #list_limit').val();

	var colToShow = [];

	jQuery('.ColVis_collection input').each(function(){
		var isChecked = jQuery(this).is(":checked");

		if (isChecked == 1)
		{
			var eachColName = jQuery(this).attr('id');
			colToShow.push(eachColName);
		}
	});

	var ifInColsToShow = jQuery.inArray(sortCol, colToShow);

	if (ifInColsToShow == -1)
	{
		sortCol = '';
	}

	if (colToShow.length === 0) {
		msg = Joomla.JText._('COM_TJLMS_REPORTS_CANNOT_SELECT_NONE');
		alert(msg);
		return false;
	}

	if (action == 'search')
	{
		if(event.which == 13)
		{
			getReportdata(page, colToShow, limit, sortCol, sortOrder, action);
		}
	}
	else
	{
		getReportdata(page, colToShow, limit, sortCol, sortOrder, action);
	}
}

function getPaginationBar(action, totalRows)
{
	action = typeof action !== 'undefined' ? action : '';
	totalRows = typeof totalRows !== 'undefined' ? totalRows : jQuery('#totalRows').val();

	if (action !== 'paginationPage')
	{
		jQuery('.pagination').html('');
		jQuery('.pagination').html('<ul id="pagination-demo" class="pagination-sm "></ul>');
	}

	var limit = jQuery('#reportPagination #list_limit').val();

	var totalpages = 0;

	if (limit != 0)
	{
		var totalpages = totalRows/limit;
		totalpages = Math.ceil(totalpages);
	}

	if (totalpages > 1)
	{
		var pagesToShow = totalpages;

		if (totalpages > 5)
		{
			pagesToShow = 5;
		}

		jQuery('#pagination-demo').twbsPagination({
			totalPages: totalpages,
			visiblePages: pagesToShow,
			startPage:1,
			onPageClick: function (event, page) {
				getFilterdata(page, '', 'paginationPage');
			}
		});
		jQuery('#pagination-demo').show();
	}
	else
	{
		jQuery('#pagination-demo').hide();
	}
}

function sortColumns(label)
{

	var sortOrder = 'asc';

	// Check if the th has class
	var colOrder = jQuery(label).closest('th').hasClass('hearderSorted');
	var sortCol = jQuery(label).attr('data-value');

	if (colOrder == true)
	{
		sortOrder = 'desc';
		jQuery(label).closest('th').removeClass('hearderSorted');
	}
	else
	{
		jQuery(label).closest('th').addClass('hearderSorted');
	}

	getFilterdata(1, '', 'hideShowCols', sortCol, sortOrder);
}

function csvExport()
{
	Joomla.submitbutton();
}

function saveThisQuery()
{
	var inputHidden = jQuery('#queryName').is(":hidden");

	if (inputHidden == 1)
	{
		jQuery('#queryName').show().addClass('span6');
		jQuery('#saveQuery').val('Save Query').addClass('span4').removeClass('span9');
	}
	else
	{
		var queryName = jQuery('input[name="queryName"]').val();

		if (queryName === '')
		{
			alert('Enter title for the Query');
			return false;
		}
		else
		{
			jQuery.ajax({
				url:"index.php?option=com_tjlms&task=reports.saveQuery",
				type: "POST",
				dataType: "json",
				data:{queryName:queryName},
				success: function(data)
				{
					if (data == 1)
					{
						window.location.reload();
					}
				}
			});
		}
	}
}

function validatezero(oldval, e)
{
	var value = e.value;
	var msg = Joomla.JText._('COM_TJLMS_COURSE_DURATION_VALIDATION');
	if(e.value == 0)
	{
		if (oldval)
		{
			jQuery('#'+e.id).val(oldval);
		}

		else
		{
			jQuery('#'+e.id).val('');
			alert(msg);
		}
	}
}

/* Function to load the loading image. */
function loadingImage(format_lesson_form)
{
	jQuery('.formatloadingcontainer',format_lesson_form).show();
	jQuery('.formatloading',format_lesson_form).show();
}

/* Function to hide the loading image. */
function hideImage(format_lesson_form)
{
	jQuery('.formatloading',format_lesson_form).hide();
	jQuery('.formatloadingcontainer',format_lesson_form).hide();
}

/* prev button on create lesson page*/
function lessonBackButton(formId)
{
	var lesson_format_form	=jQuery('#lesson-format-form_'+formId);
	var lessonform	=	jQuery('#tjlms_add_lesson_form_'+formId);
	var nextLi = jQuery('#tjlmsTab_'+formId+'Tabs li.active').prev();

	jQuery('#tjlmsTab_'+formId+'Tabs li').removeClass('active');

	jQuery(nextLi).addClass('active');

	var tabToShow = jQuery('a',nextLi).attr('href');

	jQuery('#tjlms_add_lesson_form_'+formId+' .tab-content .tab-pane').removeClass('active');
	jQuery('a[href="'+tabToShow+'"]').closest('li').addClass('active');
	jQuery('.tab-content '+tabToShow+'').addClass('active');

	if(tabToShow == '#assessment_' +  formId)
	{
		var assessmentTab = jQuery('a[href="#assessment_'+ formId  +'"]').is(":visible");

		if (assessmentTab)
			{
				jQuery('#tjlms_add_lesson_form_'+formId + ' a[href="#assessment_'+ formId  +'"]').closest('li').addClass('active');
				jQuery('#tjlms_add_lesson_form_'+formId + ' .tab-content #assessment_' + formId).addClass('active');
			}
			else
			{
				jQuery('#tjlms_add_lesson_form_'+formId + ' .tab-content #assessment_' + formId).removeClass('active');
				jQuery('#tjlms_add_lesson_form_'+formId + ' a[href="#assessment_'+ formId  +'"]').closest('li').removeClass('active');
				jQuery('#tjlms_add_lesson_form_'+formId + ' .tab-content #format_' + formId).addClass('active');
				jQuery('#tjlms_add_lesson_form_'+formId + ' a[href="#format_'+ formId  +'"]').closest('li').addClass('active');
			}
	}

	if(tabToShow == '#format_' +  formId)
	{
		/*This is to get the lesson format html from respective plugin and show*/
		var format = jQuery('#format_' + formId + ' #jform_format').val();
		var subformat = jQuery('#format_' + formId + ' #jform_subformat').val();
		var lesson_id = jQuery('#format_' + formId + ' #lesson_id').val();

		var lesson_basic_form	= jQuery('#lesson-basic-form_'+formId);
		var mod_id = jQuery('#mod_id', lesson_basic_form).val();

		getsubFormatHTML(formId,format,mod_id,lesson_id,subformat);
	}
}

/*This is to enque any messgae in the system-container div of a page*/
function enqueueSystemMessage(message, parentDiv)
{
	jQuery(parentDiv + " #system-message-container").empty();
	dispEnqueueMessage(message, null, 'error', parentDiv);
}

/*This is to get the invalid fields from the form and show the message above the form
You will use this if you do not want to show message in system-container of joomla
@parm form_to_be_validated  is the jQuery(FORMTOBEVALIDATED)
@parm form_container  the wrapper of form where the tjlms_form_errors div is added*/
function formTrackinvalidFields(form_to_be_validated, form_container)
{
	var msg = new Array();

	jQuery("label.invalid", form_to_be_validated).each(function() {
		msg.push(Joomla.JText._("COM_TJLMS_FORM_INVALID_FIELD") + jQuery(this).text());
	});

	jQuery(".tjlms_form_errors .msg", form_container).html('<div>'+msg.join('<br />')+'</div>');
	jQuery(".tjlms_form_errors", form_container).show();
    return false;
}

/*Disable the prev next tabs browse button
 *shouldbedisabled = 1 disable them
 * */
function changeformatbtnstate(form_id, shouldbedisabled)
{
	var format_form = jQuery("#lesson-format-form_" + form_id);
	if (shouldbedisabled == '1')
	{
		jQuery("a.lecture-icons",format_form).addClass("inactiveLink");
		jQuery("button",format_form).attr("disabled",true);
	}
	else
	{
		jQuery("a.lecture-icons",format_form).removeClass("inactiveLink");
		jQuery("button",format_form).removeAttr("disabled");
	}
}

/*Disable all the links and tabs
 *shouldbedisabled = 1 disable them
 * */
function inactivelinks(shouldbedisabled)
{
	if (shouldbedisabled == '1')
	{
		jQuery(".admin.com_tjlms input").attr("disabled",true);
		jQuery(".admin.com_tjlms button").attr("disabled",true);
		jQuery(".admin.com_tjlms a").addClass("inactiveLink");
		jQuery('.admin.com_tjlms .nav-tabs li').addClass('inactiveLink');
		jQuery(".btn-add-lesson").attr("disabled",true);	// Disable create lesson and add quiz button
		jQuery(".btn-add-lesson").css("pointer-events", "none");

	}
	else
	{
		jQuery(".admin.com_tjlms button").removeAttr("disabled");
		jQuery(".admin.com_tjlms input").removeAttr("disabled");
		jQuery(".admin.com_tjlms a").removeClass("inactiveLink");
		jQuery('.admin.com_tjlms .nav-tabs li').removeClass('inactiveLink');
		jQuery(".btn-add-lesson").attr("disabled",false);	// Enable create lesson and add quiz button
		jQuery(".btn-add-lesson").css("pointer-events", "auto");
	}
}

/*
function removeAssocFile(id, formId)
{
	var removeConfirm = confirm("Do you really want to remove this file?");

	if (removeConfirm == 1)
	{
		var required_id = parseInt(id.replace('removeFile',''));
		var lesson_id = jQuery('#lesson-associatefile-form_'+formId+' #lesson_id' ).val();

		jQuery.ajax({
			url: 'index.php?option=com_tjlms&task=lessons.removeAssocFiles&media_id='+required_id+'&lesson_id='+lesson_id,
			datatype:'json',
			success: function(data)
			{
				jQuery(".assocFileMedia").val(null);
				if (data == 1)
				{
					jQuery('#lesson-associatefile-form_'+formId+' #assocfiletr_'+required_id).remove();

					var rowCount = jQuery('#lesson-associatefile-form_'+formId + ' .list_selected_files tr').length;

					if (rowCount == 1)
					{
						jQuery('#lesson-associatefile-form_'+formId+' .list_selected_files').hide();
						jQuery('#lesson-associatefile-form_'+formId+' .no_selected_files').show();
					}
				}
				else
				{
					alert("Some error occured.");
				}
			},
			error: function()
			{
				alert("Some error occured.");
			}
		});
	}
	else
	{
		return false;
	}
}*/

function closePopup(donotload)
{
	if (donotload == '1')
	{
		parent.SqueezeBox.close();
	}
	else
	{
		window.parent.location.reload();
	}
}

function opentjlmsSqueezeBox(link, modalId = 'addModal', id = null)
{
	jQuery("#" + modalId + id).modal('show');
}

function getTimeStampFromDate(dateVal, format)
{
	// Extended from Calendar
	if(typeof Date.parseDate != 'undefined')
	{
		if(!format)
		{
			format = '%d-%m-%Y';
		}
		var dateObj = Date.parseDate(dateVal,format);

		if(dateObj)
		{
			return dateObj.getTime()
		}
	}
	return dateVal;
}

/*
 * Dispaly Error message same as Joomla
 *
 * @msg String Message body
 * @key String Uniquely identify that message to add or remove
 * @type String same as different bootstrap messages eg. error,warning.
 * @moveToError Boolean whether to move to Message container
 * @moveToErrorParams Object check moveToMessageDiv function for different params
 *
 * */
function dispEnqueueMessage(msg, key, type, parentDiv, moveToError, moveToErrorParams)
{
	//True for iframe
	if(parentDiv === true)
	{
		parentDiv = window.parent.document;
	}
	else
	{
		parentDiv = parentDiv ? parentDiv : "body";
	}
	var $msgContainer = jQuery('#system-message-container', parentDiv);

	if((msg || key) && $msgContainer.length)
	{
		var finalMsg = '';
		if(!type)
		{
			type = 'error'
		}

		if(!key)
		{
			key = ''
		}

		if(key)
		{
			$msgContainer.find('.' + key).remove();
			//remove empty alerts
			if($msgContainer.find('.alert:not(:has(p))').length)
			{
				 $msgContainer.find('.alert:not(:has(p))').remove();
			}
			if(!msg){
				return;
			}
			msg = '<p class="' + key + '">' + msg + '</p>';
		}
		//if alert div exists
		if($msgContainer.find('.alert.alert-' + type).length)
		{
			$msgContainer.find('.alert.alert-' + type).append(msg);
		}
		else
		{
			finalMsg = '<div class="alert alert-' + type + '">' + msg + '</div>';
			$msgContainer.append(finalMsg);
		}
		if(moveToError)
		{
			moveToMessageDiv(moveToErrorParams);
		}
	}
}

/*
 * Move to message div so that user can see that an error has occurred
 *
 * @msgSelector css selector for message div
 * @msMargin Message selector Margin
 * @inIframe    where msgSelector inside iframe
 * @iframeSelector iframe selector
 * @iFMargin Iframe Margin
 *
 * */
function moveToMessageDiv(moveToErrorParams)
{
	var msgSelector, msMargin, inIframe, iframeSelector, iFMargin;
	if(typeof moveToErrorParams != "object")
	{
		moveToErrorParams = {};
	}

	//move to same body selector
	msgSelector = moveToErrorParams.msgSelector ? moveToErrorParams.msgSelector : '#system-message-container';
	if(jQuery(msgSelector).length)
	{
		msMargin = moveToErrorParams.msMargin ? moveToErrorParams.msMargin : 0;
		msMargin = parseInt(msMargin, 10) ? parseInt(msMargin, 10) : 75;
		jQuery(window).scrollTop(jQuery(msgSelector).offset().top - msMargin);
	}

	//if selector inside an iframe
	inIframe = moveToErrorParams.inIframe ? moveToErrorParams.inIframe : false;
	iframeSelector = moveToErrorParams.msgSelector ? moveToErrorParams.msgSelector : '';

	if(inIframe && iframeSelector && typeof window.parent.document != 'undefined')
	{
		iFMargin = moveToErrorParams.iFMargin ? moveToErrorParams.iFMargin : 0;
		iFMargin = parseInt(iFMargin, 10) ? parseInt(iFMargin, 10) : 75;

		if (jQuery(window.parent.document).find(iframeSelector).length)
		{
			jQuery(window.parent).scrollTop(jQuery(window.parent.document).find(iframeSelector).offset().top - iFMargin);
		}
	}
}
