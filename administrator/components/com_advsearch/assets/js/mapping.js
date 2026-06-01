jQuery('document').ready(function(){

	var id = getUrlVars()["id"];

	if (id)
	{
		var indexer_1 =  jQuery('#indexer_1').val();
		console.log (indexer_1);

		jQuery.ajax ({
			type : 'POST',
			url: 'index.php?option=com_advsearch&task=createmapping.getIndexerFields',
			data:{indexerValue:indexer_1, primary_index:1, id:indexer_1},
			success: function(data) {
				if (data) {
					jQuery("#indexer_1_data").empty();
					jQuery("#indexer_1_data").append(data);
				}
			}
		});

		var indexer_2 =  jQuery('#indexer_2').val();
		console.log (indexer_2);

		jQuery.ajax ({
			type : 'POST',
			url: 'index.php?option=com_advsearch&task=createmapping.getIndexerFields',
			data:{indexerValue:indexer_2, primary_index:2, id:indexer_1},
			success: function(data) {
				if (data) {
					jQuery("#indexer_2_data").empty();
					jQuery("#indexer_2_data").append(data);
				}
			}
		});
	}

	// primary_index ajax call
	jQuery("#indexer_1").change(function() {
		jQuery("#indexer_1_data").empty();
		var indexer_1 = jQuery('#indexer_1').val();

		if(indexer_1 == 0) {
			jQuery("#indexer_1_data").empty();
			return false;
		}

		jQuery.ajax ({
			type : 'POST',
			url: 'index.php?option=com_advsearch&task=createmapping.getIndexerFields',
			data:{indexerValue:indexer_1, primary_index:1},
			success: function(data) {
				if (data) {
					jQuery("#indexer_1_data").empty();
					jQuery("#indexer_1_data").append(data);
				}
			}
		});
	});

	// primary_index ajax call
	jQuery("#indexer_2").change(function(){
		jQuery("#indexer_2_data").empty();
		var indexer_2 = jQuery('#indexer_2').val();

		if(indexer_2 == 0) {
			jQuery("#indexer_2_data").empty();
			return false;
		}

		jQuery.ajax ({
			type : 'POST',
			url: 'index.php?option=com_advsearch&task=createmapping.getIndexerFields',
			data:{indexerValue:indexer_2, primary_index:2},
			success: function(data) {
				if (data) {
					jQuery("#indexer_2_data").empty();
					jQuery("#indexer_2_data").append(data);
				}
			}
		});
	});
});

function getUrlVars()
{
	var vars = {};
	var parts = window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(m,key,value) {
	vars[key] = value;
	});
	return vars;
}
