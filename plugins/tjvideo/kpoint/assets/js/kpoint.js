var lessonStartTime = new Date();
tjKpointPause       = false,
tjKpointFinish      = false,
lessonStoptime      = '';
var miliseconds     = 1000;
var seekDone        = false;
var player          = null;

jQuery(window).on('load',function() {
	var player = kPoint.Player(document.getElementById("player-container"), {
		"kvideoId"  : plugdataObject.file_id,
		"videoHost" : plugdataObject.domain,
		"params"    : {"autoplay" : true,
				"xt" : plugdataObject.xauth_token,
				"hide" : plugdataObject.show_like,
				"playercontrols": plugdataObject.show_seekbar
			}
	});

	setUpdateData(player, "incomplete");

	var myVar = setInterval(timeUpdateEvent, 10000, player);

	player.addEventListener(player.events.onStateChange, function() {
		onStateChanged(player);
	});
});

// Set update data
function setUpdateData(player, status)
{
	plugdataObject.current_position = getCurrentPosition(player);
	plugdataObject.total_content    = getTotalContent(player);
	plugdataObject.lesson_status    = status;
	plugdataObject.time_spent       = getTimeSpent();

	if(!tjKpointPause && !tjKpointFinish)
	{
		lessonStartTime = new Date();
	}

	updateData(plugdataObject);
}

// Get total content/duration
function getTotalContent(player)
{
	return Math.floor(player.getDuration() / miliseconds);
}

// Get current position
function getCurrentPosition(player)
{
	return Math.floor(player.getCurrentTime() / miliseconds);
}

// Get time spent
function getTimeSpent()
{
	lessonStoptime        = new Date();
	var timespentonLesson = lessonStoptime - lessonStartTime;

	return Math.round(timespentonLesson / miliseconds);
}

function timeUpdateEvent(player) {
	if(!tjKpointPause && !tjKpointFinish)
	{
		setUpdateData(player, "incomplete");
	}
}

// Function to update state change of the video
function onStateChanged(player)
{
	if (!seekDone && plugdataObject.seekTo > 0)
	{
		var miliseconds = plugdataObject.seekTo * 1000;

		setTimeout(function(){ player.seekTo(miliseconds); }, 3000);
		seekDone = true;
	}

	if (player.getPlayState() == player.playStates.PAUSED)
	{
		tjKpointPause = true;
		if(!tjKpointFinish)
		{
			setUpdateData(player, "incomplete");
		}
	}

	if (player.getPlayState() == player.playStates.ENDED)
	{
		tjKpointFinish = true;
		setUpdateData(player, "completed");
	}
}

function detectIE() {
    var ua = window.navigator.userAgent;

    var msie = ua.indexOf('MSIE ');
    if (msie > 0) {
        // IE 10 or older => return version number
        return parseInt(ua.substring(msie + 5, ua.indexOf('.', msie)), 10);
    }

    var trident = ua.indexOf('Trident/');
    if (trident > 0) {
        // IE 11 => return version number
        var rv = ua.indexOf('rv:');
        return parseInt(ua.substring(rv + 3, ua.indexOf('.', rv)), 10);
    }

    var edge = ua.indexOf('Edge/');
    if (edge > 0) {
       // IE 12 => return version number
       return parseInt(ua.substring(edge + 5, ua.indexOf('.', edge)), 10);
    }

    // other browser
    return false;
}
