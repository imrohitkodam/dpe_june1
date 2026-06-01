<?php
/**
 * @package	 TjGoPhish
 * @subpackage  com_tjgophish
 *
 * @author	  Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license	 http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

Factory::getDocument()->addScriptDeclaration("
	exportAsCSV = function(exportdataas)
	{
		var exportTypeElement = document.getElementById('exportas');
		exportTypeElement.value = exportdataas;
		Joomla.submitform('campaign.getCampaignReportAsCSV', document.getElementById('adminForm'));
	};
");

$id = isset($this->item->id) ? $this->item->id : 0;
$app  = Factory::getApplication();
$menu = $app->getMenu();
$menuItem = $menu->getItems('link', 'index.php?option=com_tjgophish&view=campaigns', true );
?>
<div id="tjgophish-wrapper" class="container">
	<?php
	if (!empty($id))
	{
	?>
	<form action="<?php echo Route::_('index.php?option=com_tjgophish&view=campaignreport&id=' . (int) $id);?>" method="POST" name="adminForm" id="adminForm">
		<div class="row">
			<div class="col-xs-12">
				<h1>
					<?php echo Text::sprintf("COM_TJGOPHISH_CAMPAIGN_REPORT_PAGE_TITLE", $this->item->gophish_campaign_title);?>
				</h1>
			</div>
			<hr>
		</div>
		<br>
		<div class="row">
			<div class="col-xs-12">
				<div class="btn-group btn-export">
					<button type="button" id="exportButton" class="btn btn-primary dropdown-toggle" data-toggle="dropdown">
						<i class="icon-download"></i> <?php echo Text::_("COM_TJGOPHISH_CAMPAIGNS_REPORT_EXPOR_CSV");?>
						<i class="icon-arrow-down-3"></i>
					</button>
					<ul class="dropdown-menu" aria-labelledby="exportButton" id='exportButtonul'>
						<li>
							<a href="javascript:void(0);" onclick="exportAsCSV('results')"><?php echo Text::_("COM_TJGOPHISH_CAMPAIGNS_REPORT_EXPOR_CSV_RESULTS");?></a>
						</li>
						<li>
							<a href="javascript:void(0);" onclick="exportAsCSV('events')"><?php echo Text::_("COM_TJGOPHISH_CAMPAIGNS_REPORT_EXPOR_CSV_RAW_EVENTS");?></a>
						</li>
						<?php if ($this->item->summary->stats->submitted_data) : ?>
							<li>
								<a href="javascript:void(0);" onclick="exportAsCSV('submitted_data')"><?php echo Text::_("COM_TJGOPHISH_CAMPAIGNS_REPORT_EXPOR_CSV_SUBMITTED_DATA");?></a>
							</li>
						<?php endif; ?>
					</ul>
				</div>
				<a class="btn btn-warning" href="<?php echo Route::_('index.php?option=com_tjgophish&view=campaigns&Itemid=' . $menuItem->id, false)?>">
					<?php echo Text::_("COM_TJGOPHISH_CAMPAIGNS_REPORT_BACK")?>
				</a>
			</div>
		</div>
		<br><br>
		<div class="row">
			<div class="col-xs-12 col-md-1">
				<div>&nbsp;</div>
			</div>
			<div class="col-xs-12 col-md-2">
				<div class="chart-label"><?php echo Text::_("COM_TJGOPHISH_CAMPAIGN_REPORT_EMAIL_SENT");?></div>
				<br>
				<?php
				if($this->item->summary->stats->total != 0)
				{
					$percent = ($this->item->summary->stats->sent / $this->item->summary->stats->total) * 100;

				}				?>
				<div class="progress-pie-chart progress-pie-chart-sent" data-percent="<?php echo round($percent);?>">
					<div class="ppc-progress">
						<div class="ppc-progress-fill ppc-progress-fill-sent"></div>
					</div>
					<div class="ppc-percents">
						<div class="pcc-percents-wrapper">
							<span class="chart-sent-percent">
								<?php
								echo round($percent) . '%';
								?>
							</span>
						</div>
					</div>
				</div>
			</div>
			<div class="col-xs-12 col-md-2">
				<div class="chart-label"><?php echo Text::_("COM_TJGOPHISH_CAMPAIGN_REPORT_OPENED");?></div>
				<br>
				<?php
								if($this->item->summary->stats->total != 0)
{
						$percent = ($this->item->summary->stats->opened / $this->item->summary->stats->total) * 100;

}
				?>
				<div class="progress-pie-chart progress-pie-chart-opened" data-percent="<?php echo round($percent);?>">
					<div class="ppc-progress">
						<div class="ppc-progress-fill ppc-progress-fill-opened"></div>
					</div>
					<div class="ppc-percents">
						<div class="pcc-percents-wrapper">
							<span class="chart-opened-percent">
								<?php
								echo round($percent) . '%';
								?>
							</span>
						</div>
					</div>
				</div>
			</div>
			<div class="col-xs-12 col-md-2">
				<div class="chart-label"><?php echo Text::_("COM_TJGOPHISH_CAMPAIGN_REPORT_CLICKED");?></div>
				<br>
				<?php
												if($this->item->summary->stats->total != 0){
																		$percent = ($this->item->summary->stats->clicked / $this->item->summary->stats->total) * 100;

												}

				?>
				<div class="progress-pie-chart progress-pie-chart-clicked" data-percent="<?php echo round($percent);?>">
					<div class="ppc-progress">
						<div class="ppc-progress-fill ppc-progress-fill-clicked"></div>
					</div>
					<div class="ppc-percents">
						<div class="pcc-percents-wrapper">
							<span class="chart-clicked-percent">
								<?php
								echo round($percent) . '%';
								?>
							</span>
						</div>
					</div>
				</div>
			</div>
			<div class="col-xs-12 col-md-2">
				<div class="chart-label"><?php echo Text::_("COM_TJGOPHISH_CAMPAIGN_REPORT_SUBMITTED_DATA");?></div>
				<br>
				<?php
																if($this->item->summary->stats->total != 0){

					$percent = ($this->item->summary->stats->submitted_data / $this->item->summary->stats->total) * 100;
				}
				?>
				<div class="progress-pie-chart progress-pie-chart-submitted" data-percent="<?php echo round($percent);?>">
					<div class="ppc-progress">
						<div class="ppc-progress-fill ppc-progress-fill-submitted"></div>
					</div>
					<div class="ppc-percents">
						<div class="pcc-percents-wrapper">
							<span class="chart-submitted-percent">
								<?php
								echo round($percent) . '%';
								?>
							</span>
						</div>
					</div>
				</div>
			</div>
<!--
			<div class="col-xs-12 col-md-2">
				<div class="chart-label"><?php // echo Text::_("COM_TJGOPHISH_CAMPAIGN_REPORT_EMAIL_REPORTED");?></div>
				<br>
				<?php
					//$percent = ($this->item->summary->stats->email_reported / $this->item->summary->stats->total) * 100;
				?>
				<div class="progress-pie-chart progress-pie-chart-reported" data-percent="<?php echo round($percent);?>">
					<div class="ppc-progress">
						<div class="ppc-progress-fill ppc-progress-fill-reported"></div>
					</div>
					<div class="ppc-percents">
						<div class="pcc-percents-wrapper">
							<span class="chart-reported-percent">
								<?php
								// echo round($percent) . '%';
								?>
							</span>
						</div>
					</div>
				</div>
			</div>
-->
			<div class="col-xs-12 col-md-1">
				<div>&nbsp;</div>
			</div>
		</div>
		<br><br>
		<?php
		if (!empty($this->item->report->results))
		{
		?>
			<div class="row">
				<h1>
					<?php echo Text::_("COM_TJGOPHISH_CAMPAIGN_REPORT_DETAILS");?>
				</h1>
				<br>
				<table class="table table-striped">
					<thead>
						<tr>
							<th width="30%">
								<?php echo Text::_('COM_TJGOPHISH_CAMPAIGNS_REPORT_NAME'); ?>
							</th>
							<th width="30%">
								<?php echo Text::_('COM_TJGOPHISH_CAMPAIGNS_REPORT_EMAIL'); ?>
							</th>
							<th width="20%">
								<?php echo Text::_('COM_TJGOPHISH_CAMPAIGNS_REPORT_STATUS'); ?>
							</th>
<!--
							<th width="20%">
								<?php // echo Text::_('COM_TJGOPHISH_CAMPAIGNS_REPORT_REPORTED'); ?>
							</th>
-->
						</tr>
					</thead>
					<tbody>
					<?php
					foreach ($this->item->report->results as $row)
					{
						?>
						<tr>
							<td>
								<?php echo $row->first_name; ?>
							</td>
							<td>
								<?php echo $row->email; ?>
							</td>
							<td>
								<?php echo $row->status; ?>
							</td>
<!--
							<td>
								<?php //echo (empty($row->reported)) ? Text::_("JNO") : Text::_("JYES"); ?>
							</td>
-->
						</tr>
					<?php
					}
					?>
					</tbody>
				</table>
			</div>
		<?php
		}
		?>
		<input type="hidden" name="id" value="<?php echo $id?>" />
		<input type="hidden" name="exportas" id="exportas" value="" />
		<input type="hidden" name="task" value="" />
		<?php echo HTMLHelper::_('form.token'); ?>
	</form>
	<?php
	}
	else
	{
		?>
		<div class="alert alert-warning">
		<?php echo Text::_("COM_TJGOPHISH_NO_RECORD_FOUND")?>
		</div>
		<?php
	}
	?>
</div>
<script>
	jQuery(document).ready(function() {
		jQuery(".progress-pie-chart").each(function( index ) {

		var $ppc = jQuery(this),
			percent = parseInt($ppc.data('percent')),
			deg = 360 * percent / 100;

			if (percent > 50) {
				$ppc.addClass('gt-50');
			}

		jQuery(this).find('.ppc-progress-fill').css('transform', 'rotate(' + deg + 'deg)');
		});
	});

	jQuery(document).ready(function() {
  // Attach a click event handler to the exportButton
 jQuery('#exportButton').click(function() {
    var btnGroup = jQuery('.btn-group');
    if (!btnGroup.hasClass('open')) {
      btnGroup.addClass('open');
      jQuery('#exportButtonul').css('display','block');
      jQuery('#exportButtonul').css('top','100%');
      if (!jQuery('#exportButton').attr('aria-expanded') || jQuery('#exportButton').attr('aria-expanded') === 'false') {
        // Add 'aria-expanded="true"' to the exportButton
        jQuery('#exportButton').attr('aria-expanded', 'true');
      }
    }else
    {
    	btnGroup.removeClass('open');
    	jQuery('#exportButton').attr('aria-expanded', 'false');
    	jQuery('#exportButtonul').css('display','none');
    }
  });
});
</script>
