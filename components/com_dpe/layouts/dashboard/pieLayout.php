<?php
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;

HTMLHelper::_('script', 'plugins/tjdashboardrenderer/piechart/assets/js/chartjs.js');
HTMLHelper::_('script', 'plugins/tjdashboardrenderer/piechart/assets/js/chartjs-plugin-datalabels.js');


$options['relative'] = true;
HTMLHelper::_('script', 'media/com_dpe/js/staffdashboard.js', $options);


$document = Factory::getDocument();
$document->addStylesheet('templates/shaper_helix3/css/pierenderer.css');

$toolsData = $displayData['toolsData'];
$title     = $displayData['title'];
?>

<?php if ($toolsData['data']['count']['id']) { ?>
<div class="pie-layout">
	<div class="widget-data panel panel-info">
		<div class="widget-title panel-heading">
			<span class="fs-20 pr-5 fa fa-wpforms" aria-hidden="true"></span>
			<a class="text-white title-link-70" href="<?php echo $toolsData['data']['titleLink'];?>" target="_blank">
				<span class="ml-10 fs-18 font-600"><?php echo $title;?></span>
			</a>
		</div>
			<div class="chart-panel-body">
				<div class="topleft mt-minus-22">
					<div class="chart">
						<div id="cover_<?php echo $toolsData['data']['count']['id'];?>" class="chart-1">
							<div class="pie">
								<canvas id="<?php echo $toolsData['data']['count']['id'];?>" width="250" height="125"></canvas>
							</div>
						</div>
						<div id="legend_<?php echo $toolsData['data']['count']['id'];?>" class="legend ml-10">
						</div>
					</div>
					<div id="summary_<?php echo $toolsData['data']['count']['id'];?>">
					</div>
				</div>
			</div>
	</div>
</div>
<?php } ?>

<script type="text/javascript">
var clrs   = ["#4CB03B", "#F1CF0D", "#D84123", "#22b8f0", "#9c27b0", "#ff9800", "#009688", "#795548"];
var renderData = '<?php echo json_encode($toolsData);?>';
renderData = JSON.parse(renderData);


if (renderData.data.count != false)
{
	var widgetData   = renderData.data.count.widgetdata;
	var arrLabels    = [];
	var arrValues    = [];

	jQuery.each(widgetData, function(index, item) {
		arrLabels.push([index]);
		arrValues.push(parseInt(item));
	});

	var ctx = document.getElementById(renderData.data.count.id);
	var existingChart = Chart.getChart(ctx);
	if (existingChart) {
		existingChart.destroy();
	}

	var noRecords       = 0;
	var ZeroRecordcount = 0;

	for (var index = 0; index < arrValues.length; index++) {
		if (arrValues[index] < 1) {
			ZeroRecordcount++;
		}
	}

	if (arrValues.length === ZeroRecordcount)
	{
		noRecords = 1;
	}

	var subContent0 = subContent1 = subContent2 = subContent3 = '';

	if (typeof renderData.data.count.total !== 'undefined')
	{
		subContent0 = '<div class="col-md-6"><div class="total-count text-end pr-25"><strong>Total: </strong> '+renderData.data.count.total+'</div></div>';
	}

	if (typeof renderData.data.count.closedCount !== 'undefined')
	{
		subContent1 = '<div class="col-md-6"><div class="total-count text-end pr-25"><strong>Closed: </strong> '+renderData.data.count.closedCount+'</div></div>';
	}

	if (typeof renderData.data.count.totalCourses !== 'undefined')
	{
		subContent2 = '<div class="col-md-6"><div class="total-count text-end pr-25"><strong>Training Courses: </strong> '+renderData.data.count.totalCourses+'</div></div>';
	}

	if (typeof renderData.data.count.vendorinprogress !== 'undefined')
	{
		subContent3 = '<div class="col-md-6"><div class="total-count text-end pr-25"><strong>In Progress: </strong> '+renderData.data.count.vendorinprogress+'</div></div>';
	}


	var myChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: arrLabels,
            datasets: [{
                backgroundColor: clrs,
                data: arrValues,
                borderWidth: 2,
                borderColor: '#ffffff',
                hoverOffset: 0, 
            }]
        },
        options: {
          responsive: false,
          maintainAspectRatio: false,
          cutout: '55%',
          animation: false,
          interaction: {
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
                  intersect: true,
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

	if (noRecords == 1)
	{
		jQuery("#cover_"+renderData.data.count.id).html('<div class="alert alert-warning text-wrap ml-10 mr-10">No record found</div>');
	}
	else
	{
		// Manual Legend
		var legendContainer = document.getElementById('legend_' + renderData.data.count.id);
		if (legendContainer && myChart) {
			var legendText = [];
			for (var i = 0; i < myChart.data.datasets[0].data.length; i++) {
				legendText.push('<div class="wrapper" id="legend-item-' + i + '" style="cursor: pointer; display: flex; align-items: center; margin-bottom: 5px;"><div class="box-colors-circle mt-1 box boxLeft mr-5" style="width: 12px; height: 12px; border-radius: 50%; background-color:' + myChart.data.datasets[0].backgroundColor[i] + '"></div><div class="boxRight box-colors-text mr-cut"><p id="legend-label-' + i + '" style="margin: 0; font-size: 12px;">' + myChart.data.labels[i] + '</p></div></div>');
			}
			legendContainer.innerHTML = legendText.join("");
			var summaryContainer = document.getElementById('summary_' + renderData.data.count.id);
			if (summaryContainer) {
				summaryContainer.innerHTML = '<div class="summary-section"><div class="total-div row pt-2">' + 
					subContent0 + subContent1 + subContent2 + subContent3 + 
					'</div></div>';
			}

			// Add hover/click events consistent with pierenderer.js
			var legendItems = legendContainer.querySelectorAll('.wrapper');
			legendItems.forEach(function(item, index) {
				item.addEventListener('mouseenter', function() {
					var meta = myChart.getDatasetMeta(0);
					var arc = meta.data[index];
					myChart.setActiveElements([{ datasetIndex: 0, index: index }]);
					myChart.tooltip.setActiveElements([{ datasetIndex: 0, index: index }], {
						x: arc ? arc.x : myChart.chartArea.left,
						y: arc ? arc.y : myChart.chartArea.top,
					});
					myChart.update();
				});
				item.addEventListener('mouseleave', function() {
					myChart.setActiveElements([]);
					myChart.tooltip.setActiveElements([], { x: 0, y: 0 });
					myChart.update();
				});
				item.addEventListener('click', function() {
					myChart.toggleDataVisibility(index);
					var hidden = !myChart.getDataVisibility(index);
					var label = document.getElementById("legend-label-" + index);
					if (label) label.style.textDecoration = hidden ? 'line-through' : '';
					myChart.update();
				});
			});
		}
	}
}
</script>
