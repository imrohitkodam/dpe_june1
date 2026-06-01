<?php
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Component\ComponentHelper;

JLoader::import('components.com_cluster.includes.cluster', JPATH_ADMINISTRATOR);

$records      = $displayData['records'];
$formMenuId   = $displayData['formMenuId'];
$TitleField   = $displayData['field_id'];
$viewAllLink  = $displayData['viewAllLink'];
$listMenuId   = $displayData['listMenuId'];
$itemLink     = $displayData['itemLink'];
$titleFieldId = $displayData['titleFieldId'];
$logHeading   = $displayData['logHeading'];
$params       = DPE::config();
$sarStatus    = (int) $params->get('requestStatus', '0');
$breachStatus = (int) $params->get('breachStatus', '0');
$foiStatus    = (int) $params->get('foirequestStatus', '0');
?>
<div class="row">
	<div class="col-xs-9">
		<strong><?php echo $logHeading;?></strong>
	</div>
	<div class="col-xs-3">
		<a class="hasTooltip pull-right" target="_blank"
		href="<?php echo Route::_($viewAllLink . '&Itemid=' . $listMenuId); ?>" target="_blank">
			<?php echo Text::_('COM_DPE_DASHBOARD_VIEW_ALL_LOG'); ?>
		</a>
	</div>
</div>
<table class="table table-hover">
	<thead>
		<tr>
			<th style="width:250px;"><?php echo Text::_('COM_DPE_ASSIGNED_RECORD_TITLE');?></th>
			<th><?php echo Text::_('COM_DPE_ASSIGNED_RECORD_STATUS');?></th>
			<th><?php echo Text::_('COM_DPE_ASSIGNED_RECORD_AGENCY');?></th>
		</tr>
	</thead>
	<tbody>
	<?php 
	foreach($records as $record)
	{
		$record       = (array) $record;
		$clusterTable = ClusterFactory::table("clusters");
		$clusterTable->load(array('id' => $record['cluster_id']));

		if ($record['client'] == 'com_tjucm.sarlog')
		{
			$logStatus = $record[$sarStatus];
		}
		elseif($record['client'] == 'com_tjucm.breachlog')
		{
			$logStatus = $record[$breachStatus];
		}
		elseif($record['client'] == 'com_tjucm.FOIlog')
		{
			$logStatus = $record[$foiStatus];
		}
	?>
		<tr>
			<td>
				<a class="hasTooltip" target="_blank"
				href="<?php echo Route::_($itemLink .'&id=' . $record['id'] . '&Itemid=' . $formMenuId); ?>" target="_blank">
					<?php echo $record[$titleFieldId];?>
				</a>
			</td>
			<td><?php echo $logStatus;?></td>
			<td><?php echo ucwords($clusterTable->name);?></td>
		</tr>
	<?php
	}
	?>
	</tbody>
</table>
<hr>
