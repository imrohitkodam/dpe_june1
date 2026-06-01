var TJDashboardCountlinkbox = {
	renderData: function(a, t) {
			this[a](t);
	},
	tjdashcount: function(sourceData)
	{
		var content = '<div class="panel-body"><div class="row">';

		var renderData = JSON.parse(sourceData.data);

		var link = renderData.data.link;

		// Check optionlist exist in data source
		if (typeof renderData.data.optionlist === "object" && renderData.data.optionlist !== null)
		{
			var fieldname = renderData.data.optionlist.fieldname;
			var fieldOption = renderData.data.optionlist.fieldOption;
			var fieldRedirectlink = renderData.data.optionlist.fieldRedirectlink;
		}

		content += '<div class="col-12">';

		if (typeof renderData.data.count === "object" && renderData.data.count !== null)
		{
			var widgetdata = renderData.data.count;

			if (typeof renderData.data.count.widgetdata === "object" && renderData.data.count.widgetdata !== null)
			{
				widgetdata = renderData.data.count.widgetdata;
			}

			if (typeof renderData.data.count.widgetcolor === "object" && renderData.data.count.widgetcolor !== null)
			{
				var widgetcolor = renderData.data.count.widgetcolor;
			}

			jQuery.each(widgetdata, function(index, item) {
				content += '<div class="huge font-600 row"> ';

				/* Check link object exist ot not & used to add link anchor tag between text */
				if (typeof link === "object" && link[index] !== null && link[index] != undefined)
				{
					content += "<a href='"+ link[index] +"' target=\"_blank\" class=\"row\">";
				}

				/* Check widgetcolor object exist ot not & used to add color classes to value */
				if (typeof widgetcolor === "object" && widgetcolor[index] !== null && widgetcolor[index] != undefined)
				{
					content += '<div class="col-3 text-start '+ widgetcolor[index] + '" >';
				}

				/* To display value/count */
				content += item ;

				/* Check widgetcolor object exist ot not & close the added element */
				if (typeof widgetcolor === "object" && widgetcolor[index] !== null && widgetcolor[index] != undefined)
				{
					content += '</div>';
				}

				/* To display label */
				content += ' <div class="col-8">' + index + '</div>';

				/* Used to add link anchor tag between text */
				if (typeof link === "object" && link[index] !== null && link[index] != undefined)
				{
					content += ' </a>';
				}

				content += '</div>';
			});
		}
		else if (typeof link === "object" && link != null && renderData.data.count == undefined)
		{
			jQuery.each(link, function(index, item) {
					content += "<a href='"+ item +"' target=\"_blank\">";
					content += '<div class="huge mt-2"> ' + index + '</div> </a>';
			});
		}
		else if (typeof fieldOption === "object" && fieldOption != null && link == undefined && renderData.data.count == undefined)
		{
			content += '<form action="'+ fieldRedirectlink +'" method="POST" name="reportForm" id="reportForm">';
			content += '<select id="optionlist" name="'+ fieldname + '" class="w-100 widget-select-options" onchange="this.form.submit();" >';

			jQuery.each(fieldOption, function(index, item) {
				content += "<option value='"+item.value +"'>" + item.text + "</option>";
			});

			content += '</select></form>';
		}

		content += "</div></div></div><div class=\"clearfix\"></div></div>";

		jQuery("#"+sourceData.element).html(content);

		// Check titleLink datasource set or not
		if (typeof renderData.data.titleLink === "string" && renderData.data.titleLink !== null)
		{
			var widgetId = (sourceData.element).split("-");

			// Upgrade the widget title based on filter
			jQuery(".title-link-"+widgetId[2]).attr('href', renderData.data.titleLink);
		}

		/* IMP : to add chz-done into selects*/
		jQuery(".widget-select-options").chosen({disable_search_threshold: 8});
	}
};
