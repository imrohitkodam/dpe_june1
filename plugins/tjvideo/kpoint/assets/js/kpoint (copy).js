var lessonStartTime = new Date();
var tjkpoint_Pause = false;
var tjkpoint_Finish = false;

// Function to play kpoint video
jQuery(document).ready(function() {
		var tag = document.createElement("script");
		tag.src = "http://d2hi01dxs6qk7k.cloudfront.net/js/fapi/v1/fapi.min.js";
		var firstScriptTag = document.getElementsByTagName("script")[0];
		firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
		
		var lessonStoptime = '';
		var lessonStartTime = new Date();
	});

	// Function to play video
	function onKPointIframeAPIReady() {
		fapi = new Player("main_kapsule", {
			width: plugdataObject.width,
			kapsuleId: plugdataObject.file_id ,
			domain: plugdataObject.domain,
			autoplay : true,
			tokenParams: {
				type : "TOKEN",
				clientId : "cif8d2b9423416454ca9967cb2f16da36d",
				userEmail : "admin",
				userName : "snehal_patil@tekdi.net",
				challenge : '' ,
				xauth_token : "sk5bfbb34130e24cc2985e40b81cb8a119"
			}
		});
		
		var total_content = fapi.duration(); 
		total_content = Math.floor(total_content / 1000) ;
		var current_position = fapi.currentTime();
		current_position = Math.floor(current_position / 1000) ;
		lessonStoptime = new Date();
		var timespentonLesson = lessonStoptime - lessonStartTime;
		var timeinseconds = Math.round(timespentonLesson / 1000);
		
		
		
		// Call this function on being of video 
		plugdataObject.current_position = current_position;
		plugdataObject.total_content = total_content;
		plugdataObject.lesson_status = "started";
		plugdataObject.time_spent = timeinseconds;
		updateData(plugdataObject);
		
		var myVar = setInterval(timeUpdateEvent, 10000);
		
		// Update values after evry 10 secs
		//fapi.on("evtTimeUpdate",timeUpdateEvent);
		
		// Event to fetch changed state of video
		fapi.on(fapi.events.stateChange,onStateChanged);
		
		// Function to contionously update the video time log
		var player_id = "kpoint";
	}
	
	function timeUpdateEvent(info) {
		var total_content = fapi.duration(); 
		total_content = Math.floor(total_content / 1000) ;
		var current_position = fapi.currentTime();
		current_position = Math.floor(current_position / 1000) ;
		lessonStoptime = new Date();
		var timespentonLesson = lessonStoptime - lessonStartTime;
		var timeinseconds = Math.round(timespentonLesson / 1000);
		
		if(!tjkpoint_Pause && !tjkpoint_Finish)
		{
			console.log("after every 10 sec"+total_content+"curren position"+current_position);
			plugdataObject.current_position = current_position;
			plugdataObject.total_content = total_content;
			plugdataObject.lesson_status = "incomplete";
			plugdataObject.time_spent = timeinseconds;
			updateData(plugdataObject);
		}
	}
	
	// Function to update state change of the video
	function onStateChanged(info)
	{
		lessonStoptime = new Date();
		var timespentonLesson = lessonStoptime - lessonStartTime;
		var total_content = fapi.duration(); 
		total_content = Math.floor(total_content / 1000) ;
		var current_position = fapi.currentTime();
		current_position = Math.floor(current_position / 1000) ;
		var timeinseconds = Math.round(timespentonLesson / 1000);
			
		if (info.state == fapi.playStates.PAUSED) 
		{
			tjkpoint_Pause = true;
			if(!tjkpoint_Finish)
			{
				plugdataObject.current_position = current_position;
				plugdataObject.total_content = total_content;
				plugdataObject.lesson_status = "incomplete";
				plugdataObject.time_spent = timeinseconds;
				updateData(plugdataObject);
			}
		}
		
		if (info.state == fapi.playStates.ENDED) 
		{ 
			tjkpoint_Finish = true;
			plugdataObject.current_position = current_position;
			plugdataObject.total_content = total_content;
			plugdataObject.lesson_status = "complete";
			plugdataObject.time_spent = timeinseconds;
			updateData(plugdataObject);
		}
	}
