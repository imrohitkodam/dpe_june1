/*function validate import*/
function validate_import(thisfile,importType,loaderId,csvType)
{
	if (!jQuery(thisfile).val()) {
		return false;
	}

	jQuery(thisfile).closest('.controls').children( ".statusbar" ).remove();

	var format_lesson_form	= jQuery(thisfile).closest('form');

	/* Hide all alerts msgs */
	var obj = jQuery(thisfile);
	var status = new createStatusbar(obj); //Using this we can set progress.

	/* Get uploaded file object */
	var uploadedfile	=	jQuery(thisfile)[0].files[0];

	/* Get uploaded file name */
	var filename = uploadedfile.name;


	/* pop out extension of file*/
	var ext = filename.split('.').pop().toLowerCase();

	var fileExt = filename.split('.').pop();

	if (fileExt != 'csv')
	{
		var finalMsg =new Object();
		finalMsg['errormsg'] = nonvalid_extension;
		status.setMsg(finalMsg,'alert-error');

		return false;
	}

	progressBar(loaderId, true);

	/* IF evrything is correct so far, popolate file name in fileupload-preview*/

	var file_name_container	=	jQuery(".fileupload-preview",jQuery(thisfile).closest('.fileupload-new'));

	jQuery(file_name_container).show();
	jQuery(file_name_container).text(filename);

	startImporting(uploadedfile,status,thisfile,importType,loaderId,csvType);
}

function createStatusbar(obj)
{
	this.statusbar = jQuery("<div class='statusbar'></div>");
	this.filename = jQuery("<div class='filename'></div>").appendTo(this.statusbar);
	this.size = jQuery("<div class='filesize'></div>").appendTo(this.statusbar);
	this.success = jQuery('<div class="msg alert"></div>').appendTo(this.statusbar);
	this.error = jQuery('<div class="msg alert"></div>').appendTo(this.statusbar);
	//this.progressBar = jQuery('<div class="progress"><div class="progress-bar progress-bar-uploading"><span class="progress_bar_text">Uploading <span class="progress_per"></span></div></div>').appendTo(this.statusbar);
	//this.abort = jQuery("<div class='abort'>Abort</div>").appendTo(this.statusbar);
	//this.processBar = jQuery('<div class="process"><div class="progress-bar-processing"><span class="process_bar_text">Processing <span class="process_per"></span></div></div>').appendTo(this.statusbar);
	//this.processBarStatus = jQuery('<div class="process_done alert alert-success"></div>').appendTo(this.statusbar);

	obj.closest('.controls').append(this.statusbar);

	this.setFileNameSize = function(name,size)
	{
		var sizeStr="";
		var sizeKB = size/1024;
		if(parseInt(sizeKB) > 1024)
		{
			var sizeMB = sizeKB/1024;
			sizeStr = sizeMB.toFixed(2)+" MB";
		}
		else
		{
			sizeStr = sizeKB.toFixed(2)+" KB";
		}

		this.filename.html(name);
		this.size.html(sizeStr);
	}
	this.setMsg = function(msg,classname)
	{
		this.statusbar.show();
		//this.progressBar.hide();
		if(msg['errormsg'])
		{
			var error = msg['errormsg'];
			this.error.addClass("alert alert-error");
			this.error.html(error);
			this.error.show();
		}

		if(msg['successmsg'])
		{
			var success = msg['successmsg'];
			this.success.addClass("alert alert-success");
			this.success.html(success);
			this.success.show();
		}

		if(msg['messages'])
		{
			var $message = jQuery('<div>').addClass('import-messages');
			this.success.removeClass('msg alert');

			jQuery.each(msg['messages'], function(i,value){
				var key = Object.keys(value)[0];
				var curMessage = jQuery('<div>').addClass('alert alert-' + key).html(value[key]).get(0);
				$message.append(curMessage);
			});

			this.success.html($message);
			this.success.show();
		}

	}
	/*this.setProgress = function(progress)
	{
		this.statusbar.show();
		this.msg.hide();
		this.progressBar.show();
		var progressBarWidth =progress*this.progressBar.width()/ 100;
		this.progressBar.find('.progress-bar').animate({ width: progressBarWidth }, 10);
		//this.progressBar.find('.progress_text').html(text);
		this.progressBar.find('.progress_per').html(progress + "% ");
		if(parseInt(progress) >= 100)
		{
			this.abort.hide();
		}
	}*/
	/* this.setProcess = function(progress)
	{
		this.statusbar.show();
		this.msg.hide();
		this.processBar.show();
		var progressBarWidth = progress*this.processBar.width()/ 100;
		this.processBar.find('.progress-bar-processing').animate({ width: progressBarWidth }, 10);
		//this.progressBar.find('.progress_text').html(text);
		this.processBar.find('.process_per').html(progress + "% ");

		if(parseInt(progress) >= 100)
		{
			this.setProcessMSG('Lesson format successfully uploaded!')
		}
	}*/
	/*this.setProcessMSG = function(msg)
	{
		this.processBarStatus.html(msg);
		this.processBarStatus.show();
	}
	this.setAbort = function(jqxhr)
	{
		this.abort.show();
		var sb = this.statusbar;
		this.abort.click(function()
		{
			jqxhr.abort();
			sb.hide();
		});
	}*/
}


function startImporting(file,status,thisfile,importType,loaderId,csvType)
{
	var finalMsg = new Object();
		if(file == undefined)
		{
			finalMsg['errormsg'] = file_not_selected_error;
			status.setMsg(finalMsg, 'alert-error');
			return false;
		}

		var filename = file.name;

		if(window.FormData !== undefined)  // for HTML5 browsers
		{
			if (importType == 1)
			{
				var userImports =1;

				var newfilename = sendImportFileToServer(file,status,thisfile,userImports,loaderId);
			}
			else if(importType == 'historicalData')
			{
				var newfilename = sendHistoricalImportFileToServer(file,status,thisfile,loaderId);
			}
			else
			{
				var newfilename = sendFileToServer(file,status,thisfile,importType,loaderId,csvType);

			}
			return false;

		}
	   else  /*for older browsers*/
		{
			alert("You need to upgrade your browser as it does not support FormData");
		}
}

/*
 * filename = namne of the file
 * lesson_id =  id of the lesson against which we are uploading file
 * formData =  Formdata object- file-format-and lessonid
 * format_lesson_form =  the form in which this uploading is going on
 * format = format
 * subformat = pugin of selected format type
 * fileinputtag = the <input type=file>
 * */
function sendFileToServer(file,status,fileinputtag,importType,loaderId,csvType)
{
	var formData = new FormData();
	formData.append( 'FileInput', file );
	formData.append('CsvType', csvType);
	formData.append(Joomla.getOptions('csrf.token'), 1);

	var returnvar	= true;
	var jqXHR = jQuery.ajax({
		 xhr: function() {
			var xhrobj = jQuery.ajaxSettings.xhr();
			if (xhrobj.upload) {
				xhrobj.upload.addEventListener('progress', function(event) {
					var percent = 0;
					var position = event.loaded || event.position;
					var total = event.total;
					if (event.lengthComputable) {
						percent = Math.ceil(position / total * 100);
					}
					/*status.setProgress(percent);*/
				}, false);
			}
			return xhrobj;
		},
		url: 'index.php?option=com_tmt&task=fileupload.processupload&tmpl=component',
		type: 'POST',
		data:  formData,
		mimeType:"multipart/form-data",
		contentType: false,
		dataType:'json',
		cache: false,
		processData:false,
		success: function(response)
		{
			console.log(response);
			var output = response['OUTPUT'];
			var result	=	output['flag'];
			var finalMsg =  new Object();

			/* File uploading on local is done*/
			/*status.setProgress(100);*/
			if (result == 0)
			{
				finalMsg['errormsg'] = output['msg'];
				status.setMsg(finalMsg,'alert-error');
				progressBar(loaderId, false);
			}
			else
			{
				finalMsg['successmsg'] = output['msg'];
				status.setMsg(finalMsg,'alert-success');
				progressBar(loaderId, false);
			}

			if (csvType === 'quiz-csv')
			{
				jQuery('#question-csv-upload-quiz').val('');
			}
			else
			{
				jQuery('#question-csv-upload-exe-feed').val('');
			}
		},
		error: function(jqXHR, textStatus, errorThrown)
		{
			finalMsg['errormsg'] = jqXHR.responseText;
			status.setMsg(finalMsg,'alert-error');
			returnvar	= false;
			progressBar(loaderId, false);
		}
   });
	return returnvar;
	status.setAbort(jqXHR);
}

function sendImportFileToServer(file,status,fileinputtag,importType,loaderId)
{
	var formData = new FormData();
	var notify = 1;

	if (jQuery('#notify_user_import').is(":checked"))
	{
		notify_user = 1;
	}
	else
	{
		notify_user = 0;
	}

	formData.append( 'FileInput', file );
	formData.append( 'notify_user_import', notify_user );

	var returnvar	= true;

var jqXHR = jQuery.ajax({
url: 'index.php?option=com_tjlms&task=userimport.csvImport&tmpl=component',
		type: 'POST',
		data:  formData,
		mimeType:"multipart/form-data",
		contentType: false,
		dataType:'json',
		cache: false,
		processData:false,
		success: function(response)
		{
			console.log(response);
			var output = response['OUTPUT'];
			var result	=	output['return'];
			/*if(result == 0)
			{
				status.setMsg(output['successmsg'],'alert-error');
			}*/
			if(result == 1)
			{
				status.setMsg(output);

				jQuery("#user-csv-upload").val('');
				progressBar(loaderId, false);
			}
		},
		error: function(jqXHR, textStatus, errorThrown)
		{
			var finalMsg = new Object();
			finalMsg['errormsg'] = jqXHR.responseText;
			status.setMsg(finalMsg,'alert-error');
			progressBar(loaderId, false);

			returnvar	= false;
		}
   });

	return returnvar;
	status.setAbort(jqXHR);
}

function sendHistoricalImportFileToServer(file,status,fileinputtag,loaderId)
{
	var formData = new FormData();

	formData.append('FileInput', file);
	formData.append(Joomla.getOptions('csrf.token'), 1);

	var returnvar	= true;

	var jqXHR = jQuery.ajax({
	url: 'index.php?option=com_tjlms&view=tools&task=tools.historicalDataCSVImport&format=json',
		type: 'POST',
		data:  formData,
		mimeType:"multipart/form-data",
		contentType: false,
		dataType:'json',
		cache: false,
		processData:false,
		success: function(response)
		{
			console.log(response);
			var output = response['OUTPUT'];
			var result = output['return'];

			if(result == 1)
			{
				status.setMsg(output);

				jQuery("#historical-csv-upload").val('');
				progressBar(loaderId, false);
			}
		},
		error: function(jqXHR, textStatus, errorThrown)
		{
			var finalMsg = new Object();
			finalMsg['errormsg'] = jqXHR.responseText;
			status.setMsg(finalMsg,'alert-error');
			progressBar(loaderId, false);

			returnvar	= false;
		}
	});

	return returnvar;
	status.setAbort(jqXHR);
}

var progressBar = function(loaderId, action){

	if (!loaderId) {
		return false;
	}

	if (action == true) {
		jQuery("<div class='control-group'><div class='progress-line'></div></div>").prependTo(loaderId);
	}else
	{
		jQuery(loaderId + " .progress-line").remove();
	}

	jQuery(loaderId +" :input").prop("disabled", action);
	jQuery(loaderId +" :button").prop("disabled", action);

	return true;
};
