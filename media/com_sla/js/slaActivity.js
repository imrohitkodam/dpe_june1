var slaActivity = {
	'setuser' : function(dataFields) {
		var clusterUsers = jQuery('.cluster_user, .chzn-results');
		clusterUsers.empty();
		clusterUsers.trigger("liszt:updated");
		jQuery.ajax({
			url: Joomla.getOptions('system.paths').base + "/index.php?option=com_sla&task=slaactivity.getUsersByClusterId&format=json",
			type: 'POST',
			data:{
				license: dataFields.licenseId
			},
			dataType:"json",
			 headers: {'X-CSRF-Token': Joomla.getOptions('csrf.token', '')}, 
			success: function (response) {

				let selectOption = '';
				let op = '';
				let data = response.data;

				if (data != null)
				{
					for(index = 0; index < data.length; ++index)
					{
						selectOption = '';
						if (dataFields.userId == data[index].value)
						{
							selectOption = ' selected="selected" ';
						}
						op="<option value='"+data[index].value+"' "+selectOption+" > " + data[index]['text'] + "</option>" ;
						jQuery('.cluster_user').append(op);
					}

					/* IMP : to update to chz-done selects*/
					jQuery(".cluster_user").trigger("chosen:updated");
				}
			}
		});
	},
	'setLeadConsultant' : function(dataFields) {
		var clusterUsers = jQuery('.lead_consultant_id, .chzn-results');
		clusterUsers.empty();
		clusterUsers.trigger("liszt:updated");
		jQuery.ajax({
			url: Joomla.getOptions('system.paths').base + "/index.php?option=com_sla&task=slaactivity.getLeadConsultantByClusterId&format=json",
			type: 'POST',
			data:{
				license: dataFields.licenseId
			},
			dataType:"json",
			 headers: {'X-CSRF-Token': Joomla.getOptions('csrf.token', '')}, 
			success: function (response) {

				let selectOption = '';
				let op = '';
				let data = response.data;

				if (data != null)
				{
					for(index = 0; index < data.length; ++index)
					{
						selectOption = '';
						if (dataFields.LeadConsultantId == data[index].value)
						{
							selectOption = ' selected="selected" ';
						}
						op="<option value='"+data[index].value+"' "+selectOption+" > " + data[index]['text'] + "</option>" ;
						jQuery('.lead_consultant_id').append(op);
					}

					/* IMP : to update to chz-done selects*/
					jQuery(".lead_consultant_id").trigger("chosen:updated");
				}
			}
		});
	}
}

jQuery(document).ready(function() {
	jQuery('#filter_tags').on('change', function() {
		var selectData = jQuery(this).val();
		if (selectData && selectData.length > 0) {
			jQuery('#filter_license_id').val('');
			jQuery('#filter_license_id').trigger('chosen:updated');
		}
	});

	jQuery('#filter_license_id').on('change', function() {
		var selectData = jQuery(this).val();
		if (selectData && selectData !== '') {
			jQuery('#filter_tags').val('');
			jQuery('#filter_tags').trigger('chosen:updated');
		}
	});
});

