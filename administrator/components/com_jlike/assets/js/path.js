// For editing the path and settting categories.
jQuery(document).ready(function () {
	var categoryid = parseInt(jQuery('#cat_id').val());
	var categoryName = (jQuery('#cat_name').val());

	// It selects single saved category on edit view.
	jQuery("#jform_category_id").html("<option value="+categoryid+">"+categoryName+"</option>");
	jQuery("#jform_category_id").trigger("liszt:updated");

	// For setting categories on selection of the path type.
	jQuery('#jform_path_type').change(function() {

	var extension = jQuery(this).val();

	var data = {
			extension:"com_jlike."+extension
			};

	jQuery.ajax({
	type: "POST",
	dataType: "json",
	timeout: 0,
	url: root_url + "index.php?option=com_jlike&task=path.getCategory",
	data: data,
	success:function(result) {
				if(result.empty != 1)
				{
					//~ jQuery("#jform_category_id").trigger("liszt:updated");
					jQuery("#jform_category_id").html("<option value="+result[0].id+">"+result[0].path+"</option>");

					for(var i=1;i<result.length;i++)
						{
							jQuery("#jform_category_id").append("<option value="+result[i].id+">"+result[i].path+"</option>");
						}

				}
				else
				{
					jQuery("#jform_category_id").empty();
				}

				jQuery("#jform_category_id").trigger("liszt:updated");
			}
		});
	});
});
