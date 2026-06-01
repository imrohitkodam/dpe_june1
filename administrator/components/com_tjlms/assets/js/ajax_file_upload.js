
function startUploading(file,format,subformat,format_lesson_form,status,thisfile)
{
		if(file == undefined)
		{
			status.setMsg(file_not_selected_error,'alert-error');
			return false;
		}
		var lesson_id	= jQuery('[data-js-id="id"]', format_lesson_form).val();

		if(!lesson_id)
		{
			status.setMsg(save_lesson_details_firstmsg,'alert-error');
			return false;
		}

		var filename = file.name;


		if(window.FormData !== undefined)  // for HTML5 browsers
		{
			/*var formData = new FormData();
			formData.append( 'FileInput', file );
			formData.append( 'Format', format );
			formData.append( 'Lessonid', lesson_id );*/


			status.setMsg('Validating file...');

			var newfilename	=	sendFileToServer(file,lesson_id,status,format_lesson_form,format,subformat,thisfile);
			return false;

		}
	   else  //for older browsers
		{
			alert("You need to upgrade your browser as it does not support FormData");
		}
}

/*
 * filename = name of the file
 * lesson_id =  id of the lesson against which we are uploading file
 * formData =  Formdata object- file-format-and lessonid
 * format_lesson_form =  the form in which this uploading is going on
 * format = format
 * subformat = pugin of selected format type
 * fileinputtag = the <input type=file>
 *
 * Called after validating file
 * */
function sendFileToServer(file,lesson_id,status,format_lesson_form,format,subformat,fileinputtag)
{

	/*Disable the prev next buttons and tabs*/
	var disable = 1;
	inactivelinks(disable)


	var formData = new FormData();
	formData.append( 'FileInput', file );
	formData.append( 'Format', format );
	formData.append( 'Lessonid', lesson_id );
	formData.append( 'Subformat', subformat );

	var returnvar	= true;
	var jqXHR = jQuery.ajax({
		 xhr: function() {
			var xhrobj = jQuery.ajaxSettings.xhr();
			status.setAbort(xhrobj);
			if (xhrobj.upload) {
				xhrobj.upload.addEventListener('progress', function(event) {
					var percent = 0;
					var position = event.loaded || event.position;
					var total = event.total;
					if (event.lengthComputable) {
						percent = Math.ceil(position / total * 100);
					}
					status.setProgress(percent);
				}, false);
			}
			return xhrobj;
		},
		url: 'index.php?option=com_tjlms&task=fileupload.processupload&tmpl=component',
		type: 'POST',
		data:  formData,
		mimeType:"multipart/form-data",
		contentType: false,
		dataType:'json',
		cache: false,
		processData:false,
		success: function(response)
		{
			var output = response['OUTPUT'];
			var result	=	output['flag'];
			if(result == 0)
			{
				status.setMsg(output['msg'],'alert-error');

				/*Enable the prev next buttons and tabs*/
				disable = 0;
				inactivelinks(disable);
			}
			if(result == 1)
			{
				/* File uploading on local is done*/
				status.setProgress(100);

				/* Start further process..*/
				status.setProcess(10);
				var newfilename = output['msg'];

				uploadFormatsonResServer(file,format,subformat,newfilename,lesson_id,status,fileinputtag,format_lesson_form);
			}
		},
		error: function(jqXHR, textStatus, errorThrown)
		{
			/*Enable the prev next buttons and tabs*/
			disable = 0;
			inactivelinks(disable);

			status.setMsg(jqXHR.responseText,'alert-error');
			returnvar	= false;
		}
   });
	return returnvar;
	status.setAbort(jqXHR);

}

function uploadFormatsonResServer(file,lessonformat,subformat,newfilename,lesson_id,status,fileinputtag,format_lesson_form)
{
	var rsformData = new FormData();
	rsformData.append( 'filename', newfilename );
	rsformData.append( 'lesson_id', lesson_id );
	rsformData.append( 'lessonformat', lessonformat );
	rsformData.append( 'subformat', subformat );
	jQuery.ajax({
		url: 'index.php?option=com_tjlms&task=fileupload.uploadFormatsonResServer&tmpl=component',
		type: 'POST',
		data: rsformData,
		dataType:'json',
		contentType: false,
		processData: false,
		success: function(response)
		{
			var output = response['OUTPUT'];
			var result	=	output['flag'];
			if (output['warning'])
			{
				status.setWarning(output['warning']);
			}
			if(result == 0)
			{
				status.setMsg(output['msg'],'alert-error');
				disable = 0;
				inactivelinks(disable);
				status.processBar.hide();
			}
			if(result == 1)
			{
				status.setProcess(50);

				var upload_response	=	'';
				if(output['upload'])
				{
					upload_response	=	JSON.stringify(output['upload']);
				}
				populate_tables(upload_response,file,newfilename,lesson_id,lessonformat,subformat,fileinputtag,status,format_lesson_form)
			}
		},
		error: function(jqXHR, textStatus, errorThrown)
		{
			/*Enable the prev next buttons and tabs*/
			disable = 0;
			inactivelinks(disable);

			status.setMsg(jqXHR.responseText,'alert-error');
		}
	});
	return true;
}

function populate_tables(upload_response,file,newfilename,lesson_id,lessonformat,subformat,fileinputtag,status,format_lesson_form)
{
	var ptformData = new FormData();
	ptformData.append( 'filename', file.name );
	ptformData.append( 'newfilename', newfilename );
	ptformData.append( 'lesson_id', lesson_id );
	ptformData.append( 'lessonformat', lessonformat );
	ptformData.append( 'lessonsubformat', subformat );
	ptformData.append( 'upload_response', upload_response );


	jQuery.ajax({
		url: 'index.php?option=com_tjlms&task=fileupload.populate_tables&tmpl=component',
		type: 'POST',
		data: ptformData,
		dataType:'json',
		contentType: false,
		processData: false,
		success: function(response)
		{
			var output = response['OUTPUT'];
			var format_id = output['data'];

			if (output['warning'])
			{
				status.setWarning(output['warning']);
			}
			
			if (format_id == 0)
			{
				status.setMsg(output['msg'],'alert-error');
				return false;
			}
			else
			{
				status.setProcess(80);

				/*if (lessonformat == 'associate')
				{
					jQuery(fileinputtag).next().val(format_id);

					var associatedform_id = jQuery(fileinputtag).closest('form').attr('id');
					var form_id = associatedform_id.replace("lesson-associatefile-form_",'');
					var table_content = '<tr id="assocfiletr_'+format_id+'"><td id="list_select_files'+format_id+'"><span>'+file.name +'</span><input type="hidden" name="lesson_files[][media_id]" value="'+format_id+'"/></td><td class="tjlmscenter"><i id="removeFile'+format_id+'" onclick="removeAssocFile(this.id, \''+form_id+'\')" class="icon-remove"></i></td></tr>';

					jQuery(fileinputtag).closest('form').find('.no_selected_files').hide();
					jQuery(fileinputtag).closest('form').find('#list_selected_files').append(table_content);
					jQuery(fileinputtag).closest('form').find('#list_selected_files').show();
				}
				else*/

				jQuery("#lesson_format_id",format_lesson_form).val(format_id);

				jQuery("#lesson_format #" + lessonformat + " #uploded_lesson_file",format_lesson_form).val(newfilename)
				status.setProcess(80);

				parse_format(newfilename,format_id,lesson_id,lessonformat,subformat,fileinputtag,status,format_lesson_form);

				/*if(lessonformat	!=	'scorm' )
				{
					status.setProcess(100);
				}
				else
				{
					status.setProcess(80);
					parse_format(format_id,lesson_id,status);
				}*/
			}
		},
		error: function(jqXHR, textStatus, errorThrown)
		{
			/*Enable the prev next buttons and tabs*/
			disable = 0;
			inactivelinks(disable);

			status.setMsg(jqXHR.responseText,'alert-error');
		}
	});

	return true;
}

function parse_format(newfilename,format_id,lesson_id,lessonformat,subformat,fileinputtag,status,format_lesson_form)
{
	var pfformData = new FormData();

	pfformData.append( 'format_id', format_id );
	pfformData.append( 'lesson_id', lesson_id );
	pfformData.append( 'lessonformat', lessonformat );
	pfformData.append( 'lessonsubformat', subformat );
	pfformData.append( 'uploded_lesson_file', newfilename );


	jQuery.ajax({
			url: 'index.php?option=com_tjlms&task=fileupload.parse_format&tmpl=component',
			type: 'POST',
			data:pfformData,
			dataType:'json',
			contentType: false,
			processData: false,
			success: function(response){
				status.setProcess(100);

				jQuery(fileinputtag).val('');

				/*Enable the prev next buttons and tabs*/
				disable = 0;
				inactivelinks(disable);
				jQuery("a.lecture-icons",format_lesson_form).addClass("inactiveLink");

			},
			error: function(jqXHR, textStatus, errorThrown)
			{
				status.setMsg(jqXHR.responseText,'alert-error');
			}
		});
}
function createStatusbar(obj, format)
{
	this.statusbar = jQuery("<div class='statusbar'></div>");
	this.filename = jQuery("<div class='filename'></div>").appendTo(this.statusbar);
	this.size = jQuery("<div class='filesize'></div>").appendTo(this.statusbar);
	this.msg = jQuery('<div class="msg alert"></div>').appendTo(this.statusbar);
	this.progressBar = jQuery('<div class="progress"><div class="progress-bar progress-bar-uploading"><span class="progress_bar_text">Uploading <span class="progress_per"></span></div></div>').appendTo(this.statusbar);
	this.abort = jQuery("<div class='abort'><span>Abort</span></div>").appendTo(this.statusbar);
    this.processBar = jQuery('<div class="process"><div class="progress-bar-processing"><span class="process_bar_text">Processing <span class="process_per"></span></div></div>').appendTo(this.statusbar);
    this.processBarStatus = jQuery('<div class="process_done alert alert-success"></div>').appendTo(this.statusbar);
    this.processWarningStatus = jQuery('<div class="alert alert-warning"></div>').appendTo(this.statusbar);

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
		this.progressBar.hide();
		this.msg.attr('class','msg alert');
		this.msg.addClass(classname);
		this.msg.html(msg);
		this.msg.show();
    }
    this.setWarning = function(msg)
    {
		this.processWarningStatus.html(msg).show();
    }
    this.setProgress = function(progress)
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
    }
     this.setProcess = function(progress)
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
			this.setProcessMSG('File successfully uploaded!');

			/* Durgesh added for enabling links*/
			jQuery('.btn').attr('disabled', false);
			jQuery('.nav-tabs a').removeClass('inactiveLink');
			jQuery('.nav-tabs li').removeClass('inactiveLink');
			/* Durgesh added for enabling links*/
        }
    }
     this.setProcessMSG = function(msg)
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
			obj.closest('.controls').find('.fileupload-preview').text('');
            obj.closest('.controls').find('input:file').val("");
            jqxhr.abort();
            sb.hide();
        });
    }
}

function createProgressbar(obj, bartitle)
{
	bartitle = bartitle ? bartitle : 'Fixing database:';
	this.statusbar = jQuery("<div class='statusbar'></div>");
	this.progressBar = jQuery('<div class="progress"><div class="progress-bar progress-bar-uploading"><span class="progress_bar_text">' + bartitle + ' <span class="progress_per"></span></div></div>').appendTo(this.statusbar);

	obj.append(this.statusbar);

    this.setProgress = function(progress)
    {
		this.statusbar.show();
		this.progressBar.show();
        var progressBarWidth =progress*this.progressBar.width()/ 100;
        this.progressBar.find('.progress-bar').animate({ width: progressBarWidth }, 10);
        this.progressBar.find('.progress_per').html(progress + "% ");
    }
}


/*
$(document).ready(function()
{
var obj = $("#dragandrophandler");
obj.on('dragenter', function (e)
{
    e.stopPropagation();
    e.preventDefault();
    $(this).css('border', '2px solid #0B85A1');
});
obj.on('dragover', function (e)
{
     e.stopPropagation();
     e.preventDefault();
});
obj.on('drop', function (e)
{

     $(this).css('border', '2px dotted #0B85A1');
     e.preventDefault();
     var files = e.originalEvent.dataTransfer.files;

     //We need to send dropped files to Server
     handleFileUpload(files,obj);
});
$(document).on('dragenter', function (e)
{
    e.stopPropagation();
    e.preventDefault();
});
$(document).on('dragover', function (e)
{
  e.stopPropagation();
  e.preventDefault();
  obj.css('border', '2px dotted #0B85A1');
});
$(document).on('drop', function (e)
{
    e.stopPropagation();
    e.preventDefault();
});

});*/
