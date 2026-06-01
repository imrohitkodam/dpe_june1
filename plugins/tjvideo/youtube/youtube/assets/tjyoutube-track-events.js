/* _optimizely_evaluate=force */
/*this is a boilerplate set of calls to append a new script to your head tag*/
var head = document.getElementsByTagName('head')[0];
var script = document.createElement('script');
script.type = 'text/javascript';
script.src = "//www.youtube.com/iframe_api";
head.appendChild(script);
lessonStartTime = new Date();

/**
* iFrame API (for iframe videos)
* onYouTubeIframeAPIReady is called for each player when it is ready
*/
var player;
var tjyoutube_Finish = false;
var tjyoutube_Pause = false;
var tjyoutube_tracked = false;
var seekDone = false;
window.onYouTubeIframeAPIReady = function(){
	hideImage();
    jQuery('.video-tracking').each(function() {
        var iframe = jQuery(this);
        // get the player(s)
        player = new YT.Player(iframe[0], {
            events: {
                'onReady': function(e){
					hideImage();
					plugdataObject.current_position = player.getCurrentTime();
					plugdataObject.total_content = player.getDuration();
					plugdataObject.lesson_status = "incomplete";

					lessonStartTime = new Date();
					updateData(plugdataObject);

						console.log('YouTube player \'' +iframe.attr('id') +'\': ready');
						e.target._donecheck=true;
					},
                'onStateChange': function(e){
					onStateChange(iframe.attr('id'), e);
                }
            }
        });
    });

	tjInterval = setInterval(function () {
		if(!tjyoutube_Pause && !tjyoutube_Finish && !tjyoutube_tracked)
		{
			plugdataObject.current_position = player.getCurrentTime();
			plugdataObject.total_content = player.getDuration();
			plugdataObject.lesson_status = "incomplete";
			plugdataObject.time_spent = 1;
			lessonStartTime = new Date();
			updateData(plugdataObject);
		}
	}, 1000);

    tjvimeo_myInterval = setInterval(function () {

			lessonStoptime = new Date();
			var timespentonLesson = lessonStoptime - lessonStartTime;
			var timeinseconds = Math.round(timespentonLesson / 1000);
			lessonStartTime = new Date();

			if(!tjyoutube_Pause && !tjyoutube_Finish)
			{
				tjyoutube_tracked = true;
				plugdataObject.current_position = player.getCurrentTime();
				plugdataObject.total_content = player.getDuration();
				plugdataObject.lesson_status = "incomplete";
				plugdataObject.time_spent = 10;
				lessonStartTime = new Date();
				updateData(plugdataObject);
			}
	}, 10000);

};

/*execute the API calls for play, pause, and finish*/
window.onStateChange = function(playerid, state) {
    if(state.data === 0) {
        onFinish(playerid);
    } else if(state.data === 1) {
        onPlay(playerid);
    } else if(state.data === 2) {
        onPause(playerid);
    }
};

/*for each of the above three states, make a custom event API call to Optimizely*/
window.onPause = function(id) {
	console.log('YouTube player \'' +id +'\': pause');
	tjyoutube_Pause = true;
	lessonStoptime = new Date();
	var timespentonLesson = lessonStoptime - lessonStartTime;
	var timeinseconds = Math.round(timespentonLesson / 1000);

	plugdataObject.current_position = player.getCurrentTime();
	plugdataObject.total_content = player.getDuration();
	plugdataObject.lesson_status = "incomplete";
	plugdataObject.time_spent = timeinseconds;
	updateData(plugdataObject);
};

window.onFinish = function(id) {
	console.log('YouTube player \'' +id +'\': finish');
	tjyoutube_Finish = true;
	lessonStoptime = new Date();
	var timespentonLesson = lessonStoptime - lessonStartTime;

	var timeinseconds = Math.round(timespentonLesson / 1000);
	plugdataObject.current_position = player.getCurrentTime();
	plugdataObject.total_content = player.getDuration();
	plugdataObject.lesson_status = 'completed';
	plugdataObject.time_spent = timeinseconds;
	lessonStartTime = null;
	delete lessonStartTime;
	updateData(plugdataObject);
};

window.onPlay = function(id) {
	tjyoutube_Pause = false;
	tjyoutube_Finish = false;

    console.log('YouTube player \'' +id +'\': play');
    if(!seekDone)
    {
		player.seekTo(parseFloat(plugdataObject.seekTo));
		seekDone = true;
	}

    window['optimizely'] = window['optimizely'] || [];
    window.optimizely.push(["trackEvent", id +"Play"]);
};

