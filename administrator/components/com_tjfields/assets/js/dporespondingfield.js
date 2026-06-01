/*
 * @package    Com_Tjfields
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2019 TechJoomla. All rights reserved
 * @license    GNU General Public License version 2, or later
 */

var dporesponding = {

	clusterUrl: Joomla.getOptions('system.paths').base + "/index.php?option=com_cluster&task=dporesponding.getDpoResponding&format=json",

	/* This function to get all users in tjucm via ajax */
	getUsers: function (clusterUserData, ajaxUrl, dporespondingFieldId) {
		jQuery('#'+dporespondingFieldId+', .chzn-results').empty();
		jQuery.ajax({
			url: ajaxUrl,
			type: 'POST',
			data:  { clusterUserData : clusterUserData, dporespondingFieldId : dporespondingFieldId },
			dataType:"json",
			success: function (response) {
				var selectOption = '';
				var op = '';
				var data = response.data;

				for(var index = 0; index < data.length; ++index)
				{
					selectOption = '';
					if(typeof clusterUserData.users !== 'undefined' && clusterUserData.users.length > 0)
					{
						if (clusterUserData.users.includes(data[index].value))
						{
							selectOption = ' selected="selected" ';
						}
					}
					op="<option value='"+data[index].value+"' "+selectOption+" > " + data[index]['text'] + "</option>" ;
					jQuery('#'+dporespondingFieldId).append(op);
				}

				/* IMP : to update to chz-done selects*/
				jQuery("#"+dporespondingFieldId).trigger("liszt:updated");
			}
		});
	},
	/* This function to populate all users in dporesponding field of tjucm form */
	setUsers: function (clusterUserData, clusterInputId) {

		var clusterId = '';
		var dporespondingFieldId = clusterInputId.replace("clusterclusterid", "dporesponding");
		clusterUserData.users = jQuery("#"+dporespondingFieldId+"value").val();

		// Check class exists or not
		if (jQuery("#"+clusterInputId).length > 0)
		{
			clusterId = jQuery("#"+clusterInputId).val();

			clusterUserData.cluster_id = clusterId;
			ajaxUrl = this.clusterUrl;
		}
		else
		{
			return e.preventDefault();
		}

		if ((jQuery.trim(clusterId) != '' && clusterId != 'undefined') || (jQuery("#"+clusterInputId).length == 0))
		{
			this.getUsers(clusterUserData, ajaxUrl, dporespondingFieldId);
		}
	},
	/* This function to get users based on cluster value in tjucm via ajax */
	updateDporespondingField: function (e){
		var clusterFieldId = jQuery(e).attr('id');
		var dporespondingFieldId = clusterFieldId.replace("clusterclusterid", "dporesponding");

		// If there is no dporesponding field in the form then we do not need to update dporesponding field after cluster onchange event
		if (jQuery("#"+dporespondingFieldId).length == 0)
		{
			return e.preventDefault();
		}

		var dataFields = {cluster_id: jQuery("#"+clusterFieldId).val() , user_id: jQuery("#"+dporespondingFieldId+"value").val()};
		var ajaxUrl = dporesponding.clusterUrl;
		//Get All associated users
		dporesponding.getUsers(dataFields, ajaxUrl, dporespondingFieldId);
	}
}
