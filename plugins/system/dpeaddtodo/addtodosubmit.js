/**
 * @package     addTodo
 * @subpackage  plg_addTodo
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2021 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */
 var addTodo = {
 	init: function() {

 		if (parent.document.getElementById('cluster_id'))
 		{
 			var cluster_id = parent.document.getElementById('cluster_id').value;
 		}

 		var itemId = document.getElementById('id').value;

 		if (!itemId)
 		{
 			if (cluster_id)
 			{
 				document.getElementById('jform_clusters').value = cluster_id;
 			}
 		}

 		Joomla.submitbutton = function(task)
 		{
 			if (task == "recommendationform.cancel" || document.formvalidator.isValid(document.getElementById("adminForm")))
 			{
 				Joomla.submitform(task, document.getElementById("adminForm"));
 			}
 			else
 			{
 				jQuery("html, body").animate({ scrollTop: 0 }, "slow");
 			}
 		};
 	},
 	addTodos: function() {
 		if (jQuery('#jform_title').val() == '')
 		{
 			alert(Joomla.JText._('PLG_ADDTODO_TITLE_VALIDATION_MESSAGE'));
 			return false;
 		}

 		if (addTodo.validationDueDate(jQuery('#jform_due_date').val()) == false)
 		{
 			return false;
 		}

   		addTodo.showLoader('.preloader-wrap', 0);
 		var formData    = jQuery('.add-todos').serialize();
 		var params      = {};
 		var baseurl = window.location.href;

		baseurl = baseurl.split('/').slice(0, 3).join('/');


setTimeout(function(){
jQuery.ajax({
 			type:'POST',
 			url:Joomla.getOptions('system.paths').base+"/index.php?option=com_dpe&task=users.todoSave",
 			data: formData,
 			async: false,
 			contentType: 'application/x-www-form-urlencoded; charset=UTF-8' ,
 			processData:true,
 			dataType:'json',
 			beforeSend: function() {
	        // Start time before the AJAX request is sent
	        this.startTime = performance.now();
	        
	        // Show the preloader immediately
	        jQuery('.preloader-wrap').css('display', '');
	   	},
 			success:function(response)
 			{	
 				jQuery.LoadingOverlay("hide");

 				const endTime = performance.now();

		        // Calculate time taken for the AJAX call
		        const timeTaken = Math.round(endTime - this.startTime);

		        // Call the loadpreloader function with the calculated time
		        addTodo.showLoader('.preloader-wrap', timeTaken);

 				if (!response.success && response.message){
 					var messages = { "error": [response.message]};
 					Joomla.renderMessages(messages);
 				}

 				if (response.success) {
 					addTodo.renderMessage(response.data.msg);
 					jQuery('#adminForm').trigger("reset");

 					jQuery("#system-message-container").fadeTo(4000, 500, function(){
 						window.parent.SqueezeBox.close();
 					});
 				}else{
 					var messages = {"error": [response.responseText]};
 					Joomla.renderMessages(messages);
 				}

 			},
 		})

},1000)
			
 	},
 	validationDueDate: function(dueDateObj) {

 		var dueDate    = dueDateObj;
 		var today      = new Date();
 		today.setHours(0, 0, 0, 0);
		// var todaysDate = today.format("%Y-%m-%d");

		var year = today.getFullYear();
		var month = String(today.getMonth() + 1).padStart(2, '0');
		var day = String(today.getDate()).padStart(2, '0');
		var todaysDate = year + '-' + month + '-' + day;



		dueDate = dueDate.split(' ')[0]
		dueDate = dueDate.split("-").reverse().join("-");

		if (dueDate < todaysDate) {
			alert(Joomla.JText._('COM_JLIKE_DUE_DATE_VALIDATION_MESSAGE'));
			jQuery('#jform_due_date').val("");
          jQuery('.addTodoReport').prop('disabled',false);
			return false;
		}

	},
	renderMessage: function(msg) {
		Joomla.renderMessages({
			'success': [msg]
		});
		jQuery("html, body").animate({
			scrollTop: 0
		}, 2000);
	},
	showLoader: function(preloaderSelector, timeTaken) { 

   // Validate timeTaken and set a default value if invalid
    if (isNaN(timeTaken) || timeTaken <= 0) {
        console.warn("Invalid timeTaken value. Defaulting to 1000ms.");
        timeTaken = 1000; // Set a default duration (e.g., 1 second)
    }

    // Show the preloader
    jQuery(preloaderSelector).css('display', '');
    jQuery(".percentage").text("0%"); // Reset percentage text

    // Calculate animation duration (with a minimum threshold for smoother animations)
    const duration = timeTaken < 1000 ? timeTaken + 1500 : timeTaken;

    // Animate the loadbar
    jQuery(".loadbar").animate({ width: "100%" }, duration);
    jQuery(".glow").animate({ width: "100%" }, duration);

    // Animate percentage increment
    const percentageID = jQuery(".percentage");
    animateValue(percentageID, 0, 100, duration); // Animate from 0% to 100%

    // Hide preloader and reset bar after animation finishes
    setTimeout(function() {
        jQuery(preloaderSelector).fadeOut(1500); // Fade out preloader
        jQuery(".loadbar").css("width", ""); // Reset loadbar width
        jQuery(".glow").css("width", ""); // Reset glow width
    }, 500);
},
 animateValue: function(id, start, end, duration) {
      
        var range = end - start,
          current = start,
          increment = end > start? 1 : -1,
          stepTime = Math.abs(Math.floor(duration / range)),
          obj = jQuery(id);
       
        var timer = setInterval(function() {
            current += increment;

            jQuery(obj).text(current + "%");
            // jQuery(obj).text(current + "%");

            if (current == end) {

                clearInterval(timer);
            }
        }, stepTime);
    }



	}

jQuery(document).on('click', '.closepopup', function() {

	if (jQuery(this).data('refresh') == 1) {
		window.parent.document.location.reload(true);
	}

	window.parent.SqueezeBox.close();
});

jQuery(document).ready (function()
{
	jQuery('#jform_clusters').trigger('change');
});