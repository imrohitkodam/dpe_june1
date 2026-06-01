var esGroup = {
	enrolledUsers: 0,
	groups: 0,
	init: function (){
		const thisenrolledUsers = this.enrolledUsers;
		const thisgroups = this.groups;

		jQuery(document).ready(function()
		{
			jQuery("#grpCategoriesField").hide();
			jQuery("#grpTypeField").hide();
			jQuery('#jform_params_esgroup_groupCategory-lbl').hide();
			jQuery('#jform_params_esgroup_groupType-lbl').hide();
			jQuery("#jform_params_esgroup_coursegroup").click(function()
			{
				if (jQuery("#jform_params_esgroup_coursegroup1").is(":checked"))
				{
					jQuery("#grpCategoriesField").show();
					jQuery("#grpTypeField").show();
					jQuery('#jform_params_esgroup_groupCategory-lbl').show();
					jQuery('#jform_params_esgroup_groupType-lbl').show();
					jQuery('#jform_params_esgroup_onAfterEnrollEsGroups_chzn').hide();
					jQuery('#jform_params_esgroup_onAfterEnrollEsGroups-lbl').hide();
				}
				else
				{
					jQuery("#grpCategoriesField").hide();
					jQuery("#grpTypeField").hide();
					jQuery('#jform_params_esgroup_groupCategory-lbl').hide();
					jQuery('#jform_params_esgroup_groupType-lbl').hide();
					jQuery('#jform_params_esgroup_onAfterEnrollEsGroups_chzn').show();
					jQuery('#jform_params_esgroup_onAfterEnrollEsGroups-lbl').show();
				}
			});

			if (thisenrolledUsers >= 1 && thisgroups)
			{
				jQuery("#jform_params_esgroup_coursegroup").hide();
				jQuery("#jform_params_esgroup_coursegroup-lbl").hide();
				jQuery("#jform_params_esgroup_onAfterEnrollEsGroups").attr("disabled", true);
			}
		});
	}
}
