var TJDashboardPiechart = {
	renderData: function (a, t) {
		this[a](t);
	},
	tjdashcount: function (sourceData) {
		var renderData = JSON.parse(sourceData.data);
		var chartId = 'piechart_' + renderData.data.count.id;
		var widgetData = renderData.data.count.widgetdata;
		var widgetParams = sourceData.params;
		var colors = widgetParams.piecolors;
		var clrs = colors ? colors.split(', ') : ["#4CB03B", "#F1CF0D", "#D84123"];
		var pieWidth = widgetParams.piewidth ? widgetParams.piewidth : 250;
		var pieHeight = widgetParams.pieheight ? widgetParams.pieheight : 125;
		var datalableClr = widgetParams.datalabelcolor ? widgetParams.datalabelcolor : "white";

		// Data Validation
		var cleanLabels = [];
		var cleanValues = [];

		if (widgetData) {
			for (var key in widgetData) {
				if (Object.prototype.hasOwnProperty.call(widgetData, key)) {
					cleanLabels.push(String(key));
					cleanValues.push(Number(widgetData[key]));
				}
			}
		}

		// Handle "All Closed" scenario
		if (renderData.data.count.total == renderData.data.count.closedCount) {
			clrs = ["#898989"];
			cleanLabels = ["Closed"];
			cleanValues = [Number(renderData.data.count.total)];
		}

		// Prepare HTML content
		var subContent0 = subContent1 = subContent2 = subContent3 = '';
    if (typeof renderData.data.count.total !== "undefined") {
      var subContent0 =
        '<div class="col-md-6"><div class="total-count text-end pr-25"><strong>Total: </strong> ' +
        renderData.data.count.total +
        "</div></div>";
    }

    if (typeof renderData.data.count.closedCount !== "undefined") {
      var subContent1 =
        '<div class="col-md-6"><div class="total-count text-end pr-25"><strong>Closed: </strong> ' +
        renderData.data.count.closedCount +
        "</div></div>";
    }

    if (typeof renderData.data.count.totalCourses !== "undefined") {
      var subContent2 =
        '<div class="col-md-6"><div class="total-count text-end pr-25"><strong>Training Courses: </strong> ' +
        renderData.data.count.totalCourses +
        "</div></div>";
    }

    if (typeof renderData.data.count.vendorinprogress !== "undefined") {
      var subContent3 =
        '<div class="col-md-6"><div class="total-count text-end pr-25"><strong>In Progress: </strong> ' +
        renderData.data.count.vendorinprogress +
        "</div></div>";
    }

    if (typeof renderData.data.count.vendorComplete !== "undefined") {
      var subContent4 =
        '<div class="col-md-6"><div class="total-count text-end pr-25"><strong>Assessed: </strong> ' +
        renderData.data.count.vendorComplete +
        "</div></div>";
    }

    if (typeof renderData.data.count.vendorfailedtorespond !== "undefined") {
      var subContent5 =
        '<div class="col-md-6"><div class="total-count text-end pr-25"><strong>Failed to respond: </strong> ' +
        renderData.data.count.vendorfailedtorespond +
        "</div></div>";
    }

		var content = '<div class="chart-panel-body">' +
      '<div class="topleft">' +
      '<div id="cover_' +
      chartId +
      '" class="chart mt-2 mb-2">' +
      '<div class="pie">' +
      '<canvas class="chartdesign" id="' +
      chartId +
      '" width="' +
      pieWidth +
      '" height="' +
      pieHeight +
      '"></canvas>' +
      "</div>" +
      '<div id="legend' +
      chartId +
      '" class="legend pie-colors-data ml-10"></div>' +
      "</div>" +
      '<div class="summary-section">' +
      '<div class="total-div row pt-2">' +
      (subContent1 || "") +
      (subContent0 || "") +
      (subContent2 || "") +
      (subContent3 || "") +
      (subContent4 || "") +
      (subContent5 || "") +
      "</div>" +
      "</div>" +
      "</div>" +
      "</div>";
		jQuery("#" + sourceData.element).html(content);

		var chartElement = document.getElementById(chartId);
		if (!chartElement) return;

		var myChart;
		try {
			// console.log("TJDashboardPiechart: Initializing with Chart.js version", Chart.version);

			// Local plugin registration for v4
			var plugins = [];
			if (typeof ChartDataLabels !== 'undefined') {
				plugins.push(ChartDataLabels);
			}

			// Cleanup
			var existingChart = Chart.getChart(chartElement);
			if (existingChart) {
				existingChart.destroy();
			}

			// Native v4 initialization (no shield needed if MooTools is gone)
			myChart = new Chart(chartElement, {
        type: 'doughnut',
        data: {
            labels: cleanLabels,
            datasets: [{
                backgroundColor: clrs,
                data: cleanValues,
                borderWidth: 2,
                borderColor: '#ffffff',
                hoverOffset: 0, 
            }]
        },
        plugins: plugins,
        options: {
          responsive: false,
          maintainAspectRatio: false,
          cutout: '55%',
          animation: false,
      
          // ✅ FAKT slice var hover asel tarach tooltip
          interaction: {
              mode: 'nearest',
              intersect: true
          },
      
          hover: {
              mode: 'nearest',
              intersect: true
          },
      
          plugins: {
              legend: { display: false },
      
              tooltip: {
                  enabled: true,
                  backgroundColor: '#000000',
                  padding: 10,
                  cornerRadius: 0,
                  displayColors: true,
                  boxWidth: 10,
                  boxHeight: 10,
                  boxPadding: 6,
      
                  callbacks: {
                      title: function () { return ''; },
                      label: function (context) {
                          return ' ' + context.label + ': ' + context.raw;
                      }
                  }
              },
      
              datalabels: { display: false }
          }
      }
    });

    jQuery('#legend' + chartId + ' .wrapper')
.on('mouseenter', function () {

    var index = jQuery(this).attr('id').split('-').pop();

    var meta = myChart.getDatasetMeta(0);
    var arc = meta.data[index];

    // Activate specific slice
    myChart.setActiveElements([{
        datasetIndex: 0,
        index: Number(index)
    }]);

    // Show tooltip exactly on that slice
    myChart.tooltip.setActiveElements([{
        datasetIndex: 0,
        index: Number(index)
    }], {
        x: arc.x,
        y: arc.y
    });

    myChart.update();
})
.on('mouseleave', function () {

    myChart.setActiveElements([]);
    myChart.tooltip.setActiveElements([], {x:0,y:0});
    myChart.update();
});
    
    // --- 🎯 मॅन्युअल लेजंडवर हॉवर केल्यावर टूलटिप दाखवण्यासाठीचा कोड ---
    
    var legendContainer = document.getElementById('legend' + chartId);
    if (legendContainer && myChart) {
        var legendItems = legendContainer.querySelectorAll('.wrapper');
        
        legendItems.forEach(function(item, index) {
            // १. लेजंडवर माऊस नेल्यावर
            item.addEventListener('mouseenter', function() {
                myChart.setActiveElements([{
                    datasetIndex: 0,
                    index: index,
                }]);
                myChart.tooltip.setActiveElements([{
                    datasetIndex: 0,
                    index: index,
                }], {
                    x: myChart.chartArea.left, // टूलटिपची जागा सेट करण्यासाठी
                    y: myChart.chartArea.top,
                });
                myChart.update();
            });
    
            // २. लेजंडवरून माऊस काढल्यावर
            item.addEventListener('mouseleave', function() {
                myChart.setActiveElements([]);
                myChart.tooltip.setActiveElements([], { x: 0, y: 0 });
                myChart.update();
            });
        });
    }

		} catch (e) {
			console.error("TJDashboardPiechart: Chart.js initialization failed", e);
			return;
		}

		// Manual Legend
		var legendContainer = document.getElementById('legend' + chartId);
		if (legendContainer && myChart) {
			var legendText = [];
			for (var i = 0; i < myChart.data.datasets[0].data.length; i++) {
				legendText.push('<div class="wrapper" id="legend-item-' + i + '" style="cursor: pointer;"><div class="box-colors-circle mt-1 box boxLeft mr-5" style="background-color:' + myChart.data.datasets[0].backgroundColor[i] + '"></div><div class="boxRight box-colors-text mr-cut"><p id="legend-label-' + i + '">' + myChart.data.labels[i] + '</p></div></div>');
			}
			legendContainer.innerHTML = legendText.join("");

			jQuery('#legend' + chartId + ' .wrapper').on('click', function () {
				var index = jQuery(this).attr('id').split('-').pop();
				onLegendClicked(index);
			});
		}

		function onLegendClicked(i) {
			if (myChart) {
				myChart.toggleDataVisibility(i);
				var hidden = !myChart.getDataVisibility(i);
				var legendLabelSpan = document.getElementById("legend-label-" + i);
				if (legendLabelSpan) {
					legendLabelSpan.style.textDecoration = hidden ? 'line-through' : '';
				}
				myChart.update();
			}
		};

		// "No Records"
		var noRecords = 0;
		var ZeroRecordcount = 0;
		for (var idx = 0; idx < cleanValues.length; idx++) {
			if (cleanValues[idx] < 1) {
				ZeroRecordcount++;
			}
		}
		if (cleanValues.length === ZeroRecordcount) noRecords = 1;

		if (noRecords == 1) {
			var coverElement = document.getElementById('cover_' + chartId);
			if (coverElement) {
				coverElement.innerHTML = '<div class="alert alert-warning text-wrap ml-10 mr-10">There are no records currently open</div>';
			}
		}

		// TitleLink
		if (typeof renderData.data.titleLink === "string" && renderData.data.titleLink !== null) {
			var widgetParts = (sourceData.element).split("-");
			if (widgetParts.length > 2) {
				jQuery(".title-link-" + widgetParts[2]).attr('href', renderData.data.titleLink);
			}
		}
	}
};