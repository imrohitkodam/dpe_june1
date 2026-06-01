var TJDashboardLabelbox = {
	renderData: function(a, t) {
			this[a](t);
	},
	tjdashdata: function(sourceData)
	{
		var widgetTitle = jQuery("#"+sourceData.element).prev().closest('.widget-title').text();

		var content = '<h1 class="widget-label col-lg-3 widget-heading font-500 widget-heading  " >';

		var renderData = JSON.parse(sourceData.data);

		if (widgetTitle != '' && widgetTitle != undefined)
		{
			content += widgetTitle;
		}
		else
		{
			content += renderData.data.widgetlabel;
		}

		content += "</h1>";

		jQuery("#"+sourceData.element).html(content);
	}
};