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
				<div class="topleft">
					<div class="chart">
						<div id="cover_<?php echo $toolsData['data']['count']['id'];?>" class="chart-1">
							<div class="pie">
								<canvas id="<?php echo $toolsData['data']['count']['id'];?>" width="250" height="125">
							</div>
						</div>
						<div id="legend_<?php echo $toolsData['data']['count']['id'];?>" class="legend ml-10">
						</div>
					</div>
				</div>
			</div>
	</div>
</div>
<?php } ?>

<script type="text/javascript">
var clrs   = ["#4CB03B","#F1CF0D","#D84123"];
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

	var ctx = jQuery("#"+renderData.data.count.id);
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
		subContent0 = '<div class="row"><div class="total-count pull-right mr-30"><strong>Total: </strong> '+renderData.data.count.total+'</div></div>';
	}

	if (typeof renderData.data.count.closedCount !== 'undefined')
	{
		var subContent1 = '<div class="row"><div class="total-count pull-right mr-30"><strong>Closed: </strong> '+renderData.data.count.closedCount+'</div></div>';
	}

	if (typeof renderData.data.count.totalCourses !== 'undefined')
	{
		var subContent2 = '<div class="row"><div class="total-count pull-right  mr-30"><strong>Training Courses: </strong> '+renderData.data.count.totalCourses+'</div></div>';
	}

	if (typeof renderData.data.count.vendorinprogress !== 'undefined')
	{
		var subContent3 = '<div class="row"><div class="total-count pull-right  mr-30"><strong>In Progress: </strong> '+renderData.data.count.vendorinprogress+'</div></div>';
	}


	var myChart = new Chart(ctx,
	{
	  type: 'pie',
	  data: {
				labels: arrLabels,
				datasets: [{
				backgroundColor: clrs,
				data: arrValues
				}]
			},
	  options: {
					responsive: false,
					legend: {
						display: false
					},
					tooltips: {
						mode: 'nearest',
						bodyFontSize: 12,
						yAlign:'top',
						xAlign:'top',
						bodyFontColor:'#fff',
						backgroundColor: '#000',
					},
					legendCallback: function(chart) {
					var text = [];

					for (var i=0; i<chart.data.datasets[0].data.length; i++)
					{
						text.push('<div class="wrapper"><div class="box boxLeft mr-5" style="background-color:'+chart.data.datasets[0].backgroundColor[i]+'"></div><div class="boxRight mr-5"><p>'+chart.data.labels[i]+'</p></div></div>');
					}
					return text.join("");
				  },
				},
	});

	function onLegendClicked(e, i) {
	  let hidden = !myChart.getDatasetMeta(0).data[i].hidden;
	  myChart.getDatasetMeta(0).data[i].hidden = hidden;
	  const legendLabelSpan = document.getElementById("legend-label-" + i);
	  legendLabelSpan.style.textDecoration = hidden ? 'line-through' : '';
	  myChart.update();
	};

	if (noRecords == 1)
	{
		document.getElementById('cover_'+renderData.data.count.id).innerHTML = '<div class="alert alert-warning text-wrap ml-10 mr-10">No record found</div>';
	}
	else
	{
		jQuery('#legend_'+renderData.data.count.id).append(myChart.generateLegend());
		jQuery('#legend_'+renderData.data.count.id).append(subContent1+subContent0+subContent2+subContent3);
	}
}
</script>
