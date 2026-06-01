function startUploading(file,status,thisfile,invite_id,qid,test_id)
{
	var filename = file.name;
	if(window.FormData !== undefined)  // for HTML5 browsers
	{
		status.setMsg('Validating file...');
		var newfilename	=	sendFileToServer(file,status,thisfile,invite_id,qid,test_id);

		return false;
	}
	else  //for older browsers
	{
		alert("You need to upgrade your browser as it does not support FormData");
	}
}

function sendFileToServer(file,status,fileinputtag,invite_id,qid,test_id)
{
	var formData = new FormData();
	formData.append( 'FileInput', file );
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
			var output = response['OUTPUT'];
			var result	=	output['flag'];

			if(result == 0)
			{
				status.setMsg(output['msg'],'alert-error');
			}
			if(result == 1)
			{
				status.setProgress(100);
				/* Start further process..*/
				status.setProcess(10);

				/* File uploading on local is done*/

				/* Start further process..*/
				var newfilename = output['msg'];

				uploadFormatsonResServer(file,newfilename,status,fileinputtag,invite_id,qid,test_id);
			}
		},
		error: function(jqXHR, textStatus, errorThrown)
		{
			status.setMsg(jqXHR.responseText,'alert-error');
			returnvar	= false;
		}
	});
	return returnvar;
	status.setAbort(jqXHR);
}

function uploadFormatsonResServer(file,newfilename,status,fileinputtag,invite_id,qid,test_id)
{
	status.setProcess(50);
	populate_tables(file,newfilename,fileinputtag,status,invite_id,qid,test_id);

	return true;
}

function populate_tables(file,newfilename,fileinputtag,status,invite_id,qid,test_id)
{
	var ptformData = new FormData();
	ptformData.append( 'filename', file.name );
	ptformData.append( 'newfilename', newfilename );
	ptformData.append( 'invite_id', invite_id );
	ptformData.append( 'qid', qid );
	ptformData.append( 'test_id', test_id );


	jQuery.ajax({
		url: 'index.php?option=com_tmt&task=fileupload.populate_tables&tmpl=component',
		type: 'POST',
		data: ptformData,
		dataType:'json',
		contentType: false,
		processData: false,
		success: function(response)
		{
			var output = response['OUTPUT'];

			var format_id	=	output['format_id'];
			var newfilename	=	output['newfilename'];

			if(format_id)
			{
				jQuery('#lesson_files_hidden_'+qid).val(newfilename);
				jQuery('#lesson_files_'+qid).attr({ value: '' });
			}
			else
			{
				jQuery('#lesson_files_hidden_'+qid).val('');
			}
			status.setProcess(100);

			status.setProcess(100);
		},
		error: function(jqXHR, textStatus, errorThrown)
		{
			/*Enable the prev next buttons and tabs*/
			disable = 0;

			status.setMsg(jqXHR.responseText,'alert-error');
		}
	});

	return true;
}

function createStatusbar(obj)
{
	this.statusbar = jQuery("<div class='statusbar'></div>");
	this.filename = jQuery("<div class='filename'></div>").appendTo(this.statusbar);
	this.size = jQuery("<div class='filesize'></div>").appendTo(this.statusbar);
	this.msg = jQuery('<div class="msg alert"></div>').appendTo(this.statusbar);
	this.progressBar = jQuery('<div class="progress"><div class="progress-bar progress-bar-uploading"><span class="progress_bar_text">Uploading <span class="progress_per"></span></div></div>').appendTo(this.statusbar);
	this.abort = jQuery("<div class='abort'><span>Abort</span></div>").appendTo(this.statusbar);
    this.processBar = jQuery('<div class="process"><div class="progress-bar-processing"><span class="process_bar_text">Processing <span class="process_per"></span></div></div>').appendTo(this.statusbar);
    this.processBarStatus = jQuery('<div class="process_done alert alert-success"></div>').appendTo(this.statusbar);

	obj.closest('.row-fluid').after(this.statusbar);
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
    this.setProgress = function(progress)
    {
		this.statusbar.show();
		this.msg.hide();
		this.progressBar.show();
        var progressBarWidth =progress*this.progressBar.width()/ 100;
        this.progressBar.find('.progress-bar').animate({ width: progressBarWidth }, 10);
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
			obj.closest('.span12').find('.fileupload-preview').text('');
            obj.closest('.span12').find('input:file').val("");
            jqxhr.abort();
            sb.hide();
        });
    }
}
