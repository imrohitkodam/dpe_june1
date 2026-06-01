<?php
/**
* @package RSSeo!
* @copyright (C) 2016 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Language\Text;
?>
<?php if (is_array($this->visits)) { ?>
<script type="text/javascript">
	// Create and populate the data table.
	var data = new google.visualization.DataTable();

	data.addColumn('string', '<?php echo Text::_('COM_RSSEO_GA_CHART_DATE',true); ?>');
	data.addColumn('number', '<?php echo Text::_('COM_RSSEO_GA_CHART_VISITS',true); ?>');
	data.addRows(<?php echo count($this->visits); ?>);

	<?php $i = 0; ?>
	<?php if (!empty($this->visits)) { ?>
	<?php foreach ($this->visits as $visit) { ?>
			data.setCell(<?php echo $i; ?>, 0, '<?php echo $visit->date; ?>');
			data.setCell(<?php echo $i; ?>, 1, <?php echo $visit->sessions; ?>);
	<?php	
			if ($i > count($this->visits)) break;
			$i++;
		}}
	?>

	var areaOptions = {
		'legend': 'none',
		'height': '250',
		'width': '100%',
		'hAxis': {'textPosition': 'none'},
		'pointSize': '6',
		'title': '<?php echo Text::_('COM_RSSEO_GA_CHART_VISITS',true); ?>',
		'backgroundColor': {
			stroke:'#666', 
			fill:'#FFFFFF', 
			strokeSize: 1
		}
	};
	
	// Create and draw the visualization.
	var chart = new google.visualization.AreaChart(document.getElementById('rss_visualization'));
	chart.draw(data, areaOptions);
	
	jQuery(document).resize(function() {
		chart.draw(data, areaOptions);
	});
</script>

	<fieldset class="options-form">
		<legend><?php echo Text::_('COM_RSSEO_GA_VISITSPERDAY'); ?></legend>
		<table class="table table-striped table-bordered">
			<thead>
				<tr>
					<th width="15%"><?php echo Text::_('COM_RSSEO_GA_VPD_DATE'); ?></th>
					<th><?php echo Text::_('COM_RSSEO_GA_VPD_VISITS'); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php if (!empty($this->visits)) { ?>
			<?php $i = 0; ?>
			<?php foreach ($this->visits as $result) { ?>
				<tr class="row<?php echo $i % 2; ?>">
					<td><?php echo $result->date; ?></td>
					<td>
						<div class="rss_graph" style="width: <?php echo str_replace(' ','',$result->percent); ?>"></div>
						<?php echo $result->percent.' ('.$result->sessions.')'; ?>
					</td>
				</tr>
			<?php $i++; ?>
			<?php } ?>
			<?php } ?>
			<tbody>
		</table>
	</fieldset>
<?php } else echo $this->visits; ?>