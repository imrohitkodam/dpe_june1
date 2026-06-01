var tjBoxapi2 = typeof tjBoxapi2 == "undefined" ? {} : tjBoxapi2;
tjBoxapi2.debug = false;
tjBoxapi2.initializeViewer = function(document_id, access_token, data){
	var startPage = parseInt(data['current'], 10) ? parseInt(data['current'], 10) : 1;
	var container = '.preview-container';
	var lastSentOn = null;
	var endTime = 0; // End time
	var refreshId = null;
	tjBoxapi2.debug = data['debug'];
	jQuery(document).ready(function(){
		setTimeout(hideImage, 500);
		if (jQuery(container).length)
		{
			var wheight	= jQuery(window).height();
			if(wheight == 0)
			{
				wheight	= jQuery(window.parent).height();
			}
			wheight	= wheight - jQuery(container).offset().top;
			jQuery(container).css('height', wheight);
		}
	});
	var preview = new Box.Preview();
		preview.show(document_id, access_token, {
			container: container,
			showDownload: false,
			logoUrl : '',
			header:'none'
		});

	if (data['mode'] != 'preview')
	{
		preview.addListener('viewer', function(viewer){
			tjBoxapi2.log('viewer',viewer);
			viewer.addListener('pagefocus', function(curPageNum){
				tjBoxapi2.log('pagefocus', curPageNum);
				if (startPage)
				{
					viewer.setPage(startPage);
					initValuesForTracking();
					startPage = null;
				}
				else
				{
					sendTrackingRequest();
				}
			});
			viewer.addListener('load', function(data){
				tjBoxapi2.log('load',data,this,viewer);
				if (startPage)
				{
					viewer.setPage(startPage);
					startPage = null;
				}

				if(data && data.numPages == 1)
				{
					initValuesForTracking(3000);
				}
				else
				{
					initValuesForTracking();
				}
			});
		});
	}
	else
	{
		startPage = 1;
		preview.addListener('viewer', function(viewer){
			viewer.addListener('pagefocus', function(curPageNum){
				if (startPage)
				{
					viewer.setPage(startPage);
					startPage = null;
				}
			});
			viewer.addListener('load', function(data){
				if (startPage)
				{
					viewer.setPage(startPage);
					startPage = null;
				}
			});
		});
	}

	function initValuesForTracking(interval)
	{
		if (!lastSentOn)
		{
			interval = interval ? interval : 10000;
			lastSentOn = !lastSentOn ? Date.now() : lastSentOn;
			sendTrackingRequest();
			refreshId  = setInterval(sendTrackingRequest, interval);
		}
	}

	function sendTrackingRequest(){
		var curViewer  = preview.getCurrentViewer();
		var curTime    = Date.now();
		var total_time = (curTime - lastSentOn) / 1000;
			lastSentOn = curTime;

		// Hack - DPE Need to send it to Core
        if (curViewer.pageControls.getCurrentPageNumber() == curViewer.pageControls.getTotalPages()) {
            jQuery(document).trigger("tjlmsLessonCompleted");
        }
        
		var postData = {
				lesson_id: data["lesson_id"],
				current_position: curViewer.pageControls.getCurrentPageNumber(),
				total_time: Math.round(total_time),
				total_content: curViewer.pageControls.getTotalPages(),
				attempt : data['attempt'],
				mode : ''
			};
		tjBoxapi2.log(postData);
		jQuery.ajax({
				type: "POST",
				url: data['time_sync_url'],
				data: postData,
				dataType: "JSON"
			})
			.done(function(request) {
				tjBoxapi2.log('done', request);
			})
			.fail(function(request) {
				tjBoxapi2.log('fail', request);
			});
	}
}

tjBoxapi2.log = function(){
	if (tjBoxapi2['debug'])
	{
		console.log('arguments', arguments);
	}
}
jQuery('#jform_params_boxapi2_client_id').on('keyup', function(){
	tjBoxapi2.parseJson(this);
})
tjBoxapi2.parseJson = function(elem){
	if (elem && jQuery(elem).val())
	{
		var jsonVal = jQuery(elem).val();
		Joomla.removeMessages();
		try{
			var params = JSON.parse(jsonVal);
			jQuery('#jform_params_boxapi2_client_id').val(params.boxAppSettings.clientID);
			jQuery('#jform_params_boxapi2_client_secret').val(params.boxAppSettings.clientSecret);
			jQuery('#jform_params_boxapi2_publicKeyID').val(params.boxAppSettings.appAuth.publicKeyID);
			jQuery('#jform_params_boxapi2_privatekey').val(params.boxAppSettings.appAuth.privateKey);
			jQuery('#jform_params_boxapi2_passphrase').val(params.boxAppSettings.appAuth.passphrase);
			jQuery('#jform_params_boxapi2_enterpriseID').val(params.enterpriseID);
		}catch(e){
			Joomla.renderMessages({'error' : [Joomla.JText._("PLG_TJDOCUMENT_BOXAPI2_INVALID_JSON")]});
		}
	}
}