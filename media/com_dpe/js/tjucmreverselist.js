/*
 * @package    Com_Tjfields
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2019 TechJoomla. All rights reserved
 * @license    GNU General Public License version 2, or later
 */

var tjucmreverselist = {

	reverseListUrl: Joomla.getOptions('system.paths').base + "/index.php?option=com_dpe&task=tjucm.getReverseList&format=json",

	/* This function to show reverse list on UCM form */
	getReverseListUrl: function (){
		var clusterFieldUniqueName = jQuery("#clusterFieldUniqueName").val();
		var clusterFieldValue      = jQuery("#jform_"+jQuery("#clusterFieldUniqueName").val()).val();
		var recordId = jQuery("#recordId").val();

		// Update parent IDs
		jQuery.ajax({
				url: tjucmreverselist.reverseListUrl,
				type: "POST",
				data: {'clusterFieldValue': clusterFieldValue, 'recordId': recordId},
				dataType: 'json',
				complete: function()
				{
				}
			}).done(
			function(response)
			{
				// On repose
				document.getElementById("reverseListCover").innerHTML = response.data.html;

			}).fail(function(result) {
				// console.log(result);
			})
			.always(function() {
				// el.removeClass('btn-loading');
			});
	}
}
