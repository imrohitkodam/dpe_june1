/*
 * @package    Com_Sla
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2021 TechJoomla. All rights reserved
 * @license    GNU General Public License version 2, or later
 */
var slaTools = {
	showTools: function (slaObj) {

		var formData = {};
		formData['slaId']     = jQuery(slaObj).val();;

		var promise = slaService.showTools(formData);

		promise.fail(
			function(response) {
				var messages = { "error": [response.responseText]};
				Joomla.renderMessages(messages);
			}
		).done(function(response) {
			jQuery('.load-tools').empty();
			
			if (response != null)
			{
				if (!response.success && response.message)
				{
					var messages = { "error": [response.message]};
					Joomla.renderMessages(messages);
				}

				if (response.messages){
					Joomla.renderMessages(response.messages);
				}

				if (response.success && response.data) {
					jQuery('.load-tools').append(response.data);
				}
			}
		});
	},
	showNewSlaTextbox: function (obj){
		
		if(jQuery(obj).prop('checked') == true)
		{
			jQuery("#licenceFormNewSlaName").removeClass("hide");
		}
		else
		{
			jQuery("#licenceFormNewSlaName").addClass("hide");
		}
	}
}
