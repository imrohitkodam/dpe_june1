jQuery(function(){
	{
		var lessonStartTime = new Date();
		var wheight = jQuery(window.parent).height()-50;
		var wwidth = jQuery(window.parent).width();
		var options = {
			id: parseInt(plugdataObject.file_id, 10),
			width: wwidth,
			height: wheight - 10
		};

		var videoResumed = false, resetTime = false;
		var videoLength = 0, lessonStartTime = 0, lessonStoptime = 0;
		
		if (plugdataObject.embedMethod == 'iframe-script')
		{
			jQuery('#shika_vimeoplayer').html('');
			var player = new Vimeo.Player('shika_vimeoplayer', options);
		}
		else
		{
			var iframe = document.querySelector('#shika_vimeoplayer iframe');
			var player = new Vimeo.Player(iframe);
		}
		
		jQuery('#shika_vimeoplayer iframe').attr('width', wwidth).attr('height', wheight);

		player.ready().then(function() {
			hideImage();
			player.play();
			player.on('play', function() {
				lessonStartTime = new Date();
				resetTime = false;
				if (!videoResumed)
				{
					player.setCurrentTime(plugdataObject.seekTo);
					videoResumed = true;
					player.getDuration().then(function(duration) {
						videoLength = duration;
						tjvimeo_myInterval = setInterval(function () {
							sendTrackingData();
						},10000);
					}).catch(function(error) {
						// an error occurred
					});
				}
			});

			player.on('pause', function(data) {
				resetTime = true;
				sendTrackingData();
			});

			player.on('ended', function(data) {
				resetTime = true;
				sendTrackingData();
			});
		}).catch(function(data) {
			alert(data);
		});

		function sendTrackingData(data) {
			player.getCurrentTime().then(function(current_position) {
				if (lessonStartTime)
				{
					var lessonStoptime = new Date();
					var timeinseconds  = Math.round((lessonStoptime - lessonStartTime) / 1000);
					current_position  = Math.round(current_position);
					var total_content  = Math.round(videoLength);
					plugdataObject.current_position = current_position;
					plugdataObject.total_content = total_content;
					plugdataObject.lesson_status = (current_position >= total_content) ? "completed" : "incomplete";
					plugdataObject.time_spent = timeinseconds > 10 ? 10 : timeinseconds;
					lessonStartTime = resetTime ? 0 : new Date();
					//~ console.log(plugdataObject, timeinseconds, lessonStoptime, lessonStartTime, 'endededdddddddddlessonStartTime')
					updateData(plugdataObject);
				}
			});
		}

		jQuery.fn.onBeforeUnloadLessonPageUnload = sendTrackingData;
	}
});
