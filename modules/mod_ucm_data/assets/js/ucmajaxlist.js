/* JS Document */
jQuery.noConflict();

jQuery(document).on('change', '#jform_params_ucmtypename', function() {
	var ucm_id = jQuery('#jform_params_ucmtypename').val();

	jQuery.ajax({
		type:'POST',
		url:site_root+'index.php?option=com_tjfields&task=fields.getUcmFieldsUsingType&format=json',
		data:{'ucm_id': ucm_id},
		success : function(resp){
			jQuery( "#jform_params_ucmfieldname").empty();
			jQuery( "#jform_params_ucmfieldname" ).append(resp).trigger( "liszt:updated" );
		}
	});
});

jQuery(document).ready(function(){
	jQuery('#no_data_ucmlist').hide();
	hideLoader(); 
});

function loadMore()
{
	var typeId       = jQuery('#typeId').val();
	var limit        = jQuery('#limit').val();
	var client       = jQuery('#client').val();
	var cluster_id   = jQuery('#cluster_id').val();
	var newlimit     = jQuery('#paginationIndex').val();
	var totalRecords = jQuery('#total').val();
	var ucmfields    = jQuery('#ucmfields').val();
	var filterSearch = jQuery('#filter_search').val();
	var paramEdit      = jQuery('#editparam').val();
	// Set limit when for last ajax request
	if (parseInt(totalRecords) <= parseInt(newlimit) + parseInt(limit))
	{
		limit = parseInt(totalRecords) - parseInt(newlimit);
	}
	showLoader();
	jQuery.ajax({
		type:'POST',
		url:site_root+'index.php?option=com_tjucm&task=items.getAjaxItems',
		dataType: 'json',
		data:{'typeId': typeId, 'paramEdit':paramEdit,'limit': limit, 'client':client, 'paginationIndexAjax': newlimit, 'ucmfields':ucmfields, 'cluster_id': cluster_id, 'filterSearch': filterSearch},
		success : function(resp){ 
			hideLoader();
			newlimit     = resp['data']['paginationIndexAjax'];
			totalRecords = resp['data']['total'];

			// Update new limit and total count used for next Ajax call
			jQuery('#paginationIndex').val(newlimit);
			jQuery('#total').val(totalRecords);

			// Append HTML
			jQuery("#tjucm_items_list_table").append(resp['data']['records']);

			if (!resp['data']['records'] || totalRecords <= newlimit)
			{
				jQuery('#ucm_list_counter').html(totalRecords + ' / ' + totalRecords);
				jQuery("#btn_showMore").hide();
            }
            else
            {
				jQuery('#ucm_list_counter').html(newlimit + ' / ' + totalRecords);
				jQuery("#btn_showMore").show();
			}
		}
	});
}

function applyFilters(el)
{
	var clusterElement = document.getElementById('cluster');
	var filterSearch   = jQuery('#filter_search').val();
	var clusterValue   = clusterElement.value;
	var newlimit       = 0;
	var totalRecords   = 0;
	var limit          = jQuery('#limit').val();
	var client         = jQuery('#client').val();
	var typeId         = jQuery('#typeId').val();
	var limitstart     = jQuery('#limitstart').val();
	var ucmfields      = jQuery('#ucmfields').val();
	var parentDiv      = jQuery('#ucmListModule');
	var paramEdit      = jQuery('#editparam').val();

	if (clusterValue)
	{
		jQuery('#cluster_id').val(clusterValue);
	}
	else
	{
		jQuery('#cluster_id').val(0);
	}

	// Set value to 0 after aplying filter
	jQuery('#paginationIndex').val(0);
	jQuery('#total').val(0);

	// Delete old list
	jQuery('#tjucm_items_list_body').empty();

	showLoader();

	jQuery.ajax({
		type:'POST',
		url:site_root+'index.php?option=com_tjucm&task=items.getAjaxItems',
		dataType: 'json',
		data:{'typeId': typeId, 'paramEdit':paramEdit,'limit': limit, 'client':client, 'paginationIndexAjax': newlimit, 'cluster_id': clusterValue, 'ucmfields':ucmfields, 'filterSearch': filterSearch},
		success : function(resp){ hideLoader();
			newlimit     = resp['data']['paginationIndexAjax'];
			totalRecords = resp['data']['total'];
			if (totalRecords == 0)
			{
				jQuery('.ucm-list-footer').hide();
				jQuery('#tjucm_items_list_head').hide();
				jQuery('#no_data_ucmlist').show();
			}
			else
			{
				jQuery('.ucm-list-footer').show();
				jQuery('#no_data_ucmlist').hide();
				jQuery('#tjucm_items_list_head').show();

				jQuery('#paginationIndex').val(newlimit);
				jQuery('#total').val(totalRecords);
				jQuery('#tjucm_items_list_body').empty();
				jQuery('#tjucm_items_list_table td').remove();
				// Apend HTML
				jQuery("#tjucm_items_list_table").append(resp['data']['records']);

				if (!resp['data']['records'] || totalRecords <= newlimit)
				{
					jQuery('#ucm_list_counter').html(totalRecords + ' / ' + totalRecords);
					jQuery("#btn_showMore").hide();
				}
				else
				{
					jQuery('#ucm_list_counter').html(newlimit + ' / ' + totalRecords);
					jQuery("#btn_showMore").show();
				}
			}
		}
	});
}

    function showLoader() {
    jQuery('#ajax_loader_ucm').show();
	}

	function hideLoader() {
    jQuery('#ajax_loader_ucm').hide();
}