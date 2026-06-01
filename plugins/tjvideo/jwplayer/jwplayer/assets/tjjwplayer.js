var lessonStartTime = new Date();
var lessonStoptime = '';
var wheight	= jQuery(window).height();
if(wheight == 0)
	wheight	= jQuery(window.parent).height();

wheight	=	wheight-80;



var jwplayerisPaused = false;
var jwplayerisComplete = false;



/*var jwplayermyInterval = setInterval(function () {
		if(!jwplayerisPaused) {
			jwplayer_lastcounter = jwplayercounter;
			++jwplayercounter;
			newtime = jwplayercounter - jwplayer_lastcounter;
		}
}, 1000);*/

jwplayer("shika_jwplayer").setup({
	file:plugdataObject.file_id,
	type: plugdataObject.type,
	width: "100%",
	height: wheight ,
	autostart:true
});


jwplayer().onReady( function(event){
	hideImage();
	tjjwplayer_myInterval = setInterval(function () {

		lessonStoptime = new Date();
		var timespentonLesson = lessonStoptime - lessonStartTime;
		var timeinseconds = Math.round(timespentonLesson / 1000);

		if(!jwplayerisComplete && !jwplayerisPaused)
		{
			plugdataObject.current_position = jwplayer().getPosition();
			plugdataObject.total_content = jwplayer().getDuration();
			plugdataObject.lesson_status = "incomplete";
			plugdataObject.time_spent = timeinseconds;
			lessonStartTime = new Date();
			updateData(plugdataObject);
		}

}, 10000);

});

jwplayer().onTime( function(event){
	plugdataObject.seekTo = event.position;
});

jwplayer().onComplete( function(event){
	jwplayerisComplete = true;

	lessonStoptime = new Date();
	var timespentonLesson = lessonStoptime - lessonStartTime;

	var timeinseconds = Math.round(timespentonLesson / 1000);

	plugdataObject.current_position = jwplayer().getPosition();
	plugdataObject.total_content = jwplayer().getDuration();
	plugdataObject.lesson_status = 'completed';
	plugdataObject.time_spent = timeinseconds;
	lessonStartTime = new Date();
	updateData(plugdataObject);
});

jwplayer().onPlay( function(event){
	jwplayer().seek(plugdataObject.seekTo);
});

/* When the user Pauses you want to send the current position to Database & status Incomplete*/
jwplayer().onPause( function(event){
	jwplayerisPaused = true;

	lessonStoptime = new Date();
	var timespentonLesson = lessonStoptime - lessonStartTime;
	var timeinseconds = Math.round(timespentonLesson / 1000);

	plugdataObject.current_position = jwplayer().getPosition();
	plugdataObject.total_content = jwplayer().getDuration();
	plugdataObject.lesson_status = "incomplete";
	plugdataObject.time_spent = timeinseconds;
	lessonStartTime = new Date();
	updateData(plugdataObject);
});

