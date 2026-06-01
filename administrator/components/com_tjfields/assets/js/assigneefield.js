/*
 * @package    Com_Tjfields
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2021 TechJoomla. All rights reserved
 * @license    GNU General Public License version 2, or later
 */

var assignee = {

	clusterUrl: Joomla.getOptions('system.paths').base + "/index.php?option=com_cluster&task=clusterusers.getUsersByClientId&format=json",
	userUrl: Joomla.getOptions('system.paths').base + "/index.php?option=com_tjfields&task=fields.getAllUsers&format=json",

	/* This function to get all users in tjucm via ajax */
	getUsers: function (clusterUserData, ajaxUrl, assigneeFieldId) {
		jQuery('#'+assigneeFieldId+', .chzn-results').empty();
		jQuery.ajax({
			url: ajaxUrl,
			type: 'POST',
			data: clusterUserData,
			dataType:"json",
			success: function (response) {
				var selectOption = '';
				var op = '';
				var data = response.data;

				for(var index = 0; index < data.length; ++index)
				{
					selectOption = '';

					if (jQuery.inArray(data[index].value, clusterUserData.user_id.split(',').map(Number)) >= 0)
					{
						selectOption = ' selected="selected" ';
					}
					op="<option value='"+data[index].value+"' "+selectOption+" > " + data[index]['text'] + "</option>" ;
					jQuery('#'+assigneeFieldId).append(op);
				}

				/* IMP : to update to chz-done selects*/
				jQuery("#"+assigneeFieldId).trigger("liszt:updated");
			}
		});
	},
	/* This function to populate all users in assignee field of tjucm form */
	setUsers: function (clusterUserData, clusterInputId) {alert();
		var clusterId = '';
		var ajaxUrl = this.userUrl;
		var assigneeFieldId = clusterInputId.replace("clusterclusterid", "assignee");

		clusterUserData.user_id = jQuery("#"+assigneeFieldId+"value").val();

		// Check class exists or not
		if (jQuery("#"+clusterInputId).length > 0)
		{
			clusterId = jQuery("#"+clusterInputId).val();

			clusterUserData.cluster_id = clusterId;
			ajaxUrl = this.clusterUrl;
		}

		if ((jQuery.trim(clusterId) != '' && clusterId != 'undefined') || (jQuery("#"+clusterInputId).length == 0))
		{
			this.getUsers(clusterUserData, ajaxUrl, assigneeFieldId);
		}
	},
	/* This function to get users based on cluster value in tjucm via ajax */
	updateAssigneeField: function (e,multiple){
		var clusterFieldId = jQuery(e).attr('id');
		var assigneeFieldId = clusterFieldId.replace("clusterclusterid", "assignee");

		// If there is no assignee field in the form then we do not need to update assginee field after cluster onchange event
		if (jQuery("#"+assigneeFieldId).length == 0)
		{
			return e.preventDefault();
		}

		var dataFields = {cluster_id: jQuery("#"+clusterFieldId).val() , user_id: jQuery("#"+assigneeFieldId+"value").val(), multiple:multiple};
		var ajaxUrl = assignee.clusterUrl;
		//Get All associated users
		assignee.getUsers(dataFields, ajaxUrl, assigneeFieldId);
	}
}
