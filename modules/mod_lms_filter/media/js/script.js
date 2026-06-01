var tjlmsfilter = {
	init : function() {
		jQuery(window).load(function()
		{
			var creator_filter = jQuery('#creator_filter').val();

			if (creator_filter > 0)
			{
				jQuery('#creator_filter_chzn').addClass('filterActive');
			}

			var category_filter = jQuery('#category_filter').val();

			if (category_filter > 0)
			{
				jQuery('#category_filter_chzn').addClass('filterActive');
			}

			var type_filter = jQuery('#course_type').val();

			if (type_filter > -1)
			{
				jQuery('#course_type_chzn').addClass('filterActive');
			}
			
			if(jQuery(window).width() < 767)
			{
				jQuery('[data-id="filter-category"]').addClass('col-xxs-6');
				jQuery('[data-id="filter-type"]').addClass('col-xxs-6');
				jQuery('[data-id="filter-author"]').addClass('col-xxs-6');
				jQuery('[data-id="filter-tag"]').addClass('col-xxs-6');
			}
			
			if (jQuery('[data-id="filter-search"]').hasClass('filterActive') ||
				jQuery('[data-id="filter-type"]').hasClass('filterActive')   ||
				jQuery('[data-id="filter-category"]').hasClass('filterActive') ||
				jQuery('[data-id="filter-author"]').hasClass('filterActive') ||
				jQuery('[data-id="filter-tag"]').hasClass('filterActive'))
			{
				jQuery('.tjlms-filters').show();
				jQuery('[data-identifier="tjlms-filters-menu"]').find('i').addClass('fa-angle-down').removeClass('fa-angle-right');
			}
				
		}); 
	},
	reset: function(){
		jQuery('#course_cat, #course_type, #creator_filter, #course_status, #filter_tag').prop('selectedIndex',0);
		jQuery('#filter_search').val('');
	},
	toggle: function(element){
		jQuery('.tjlms-filters').toggle();
		
		if (jQuery('.tjlms-filters').is(':hidden'))
		{
			jQuery(element).children('i').addClass('fa-angle-right').removeClass('fa-angle-down');
		}
		else
		{
			jQuery(element).children('i').addClass('fa-angle-down').removeClass('fa-angle-right');
		}
	}
}
