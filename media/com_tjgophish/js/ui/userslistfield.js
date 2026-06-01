var userslist = {
	clusterUrl: Joomla.getOptions('system.paths').base + "/index.php?option=com_cluster&task=clusterusers.getUsersByClientId&format=json",

	/* This function to get all users ajax */
	getUsers: function (clusterUserData, userslistFieldId) {
		jQuery('#'+userslistFieldId+', .chzn-results').empty();
		jQuery.ajax({
			url: this.clusterUrl,
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
					if (clusterUserData.user_id == data[index].value)
					{
						selectOption = ' selected="selected" ';
					}
					op="<option value='"+data[index].value+"' "+selectOption+" > " + data[index]['text'] + "</option>" ;
					jQuery('#'+userslistFieldId).append(op);
				}

				/* IMP : to update to chz-done selects*/
				jQuery("#"+userslistFieldId).trigger("liszt:updated");
			}
		});
	},
	/* This function to get users based on cluster value ajax */
	updateUserslistField: function (clusterFieldId, userslistFieldId){
		// If there is no userslist field in the form then we do not need to update userslist field after cluster onchange event
		if (jQuery("#"+userslistFieldId).length == 0)
		{
			return e.preventDefault();
		}

		var dataFields = {cluster_id: jQuery("#"+clusterFieldId).val() , user_id: jQuery("#"+userslistFieldId+"value").val()};

		//Get All associated users
		this.getUsers(dataFields, userslistFieldId);
	}
}
