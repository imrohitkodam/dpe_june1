var groupslist = {
	groupsUrl: Joomla.getOptions('system.paths').base + "/index.php?option=com_tjgophish&task=groups.getClusterGroups&format=json",

	/* This function to get groups list ajax */
	getGroups: function (clusterData, groupslistFieldId) {
		jQuery('#'+groupslistFieldId+', .chzn-results').empty();
		jQuery.ajax({
			url: this.groupsUrl,
			type: 'POST',
			data: clusterData,
			dataType:"json",
			headers: {'X-CSRF-Token': Joomla.getOptions('csrf.token', '')},
			success: function (response) {
				var selectOption = '';
				var op = '';
				var data = response.data;

				for(var index = 0; index < data.length; ++index)
				{
					selectOption = '';
					if (clusterData.user_id == data[index].value)
					{
						selectOption = ' selected="selected" ';
					}
					op="<option value='"+data[index].value+"' "+selectOption+" > " + data[index]['text'] + "</option>" ;
					jQuery('#'+groupslistFieldId).append(op);
				}

				/* IMP : to update to chz-done selects*/
				jQuery("#"+groupslistFieldId).trigger("chosen:updated");
			}
		});
	},
	/* This function to get users based on cluster value ajax */
	updategroupslistField: function (clusterFieldId, groupslistFieldId){
		// If there is no groupslist field in the form then we do not need to update groupslist field after cluster onchange event
		if (jQuery("#"+groupslistFieldId).length == 0)
		{
			return e.preventDefault();
		}

		var dataFields = {cluster_id: jQuery("#"+clusterFieldId).val()};

		//Get All associated users
		this.getGroups(dataFields, groupslistFieldId);
	}
}
