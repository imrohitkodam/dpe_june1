<?php
/**
 * @version     1.0.0
 * @package     com_tjlms
 * @copyright   Copyright (C) 2014. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      TechJoomla <extensions@techjoomla.com> - http://www.techjoomla.com
 */

// no direct access
defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;

JHtml::_('formbehavior.chosen', 'select');
JHtml::addIncludePath(JPATH_COMPONENT.'/helpers/html');
$mainframe  = Factory::getApplication('admin');

HTMLHelper::_('script', '/components/com_tjlms/assets/js/jquery.twbsPagination.js');
HTMLHelper::_('script', '/components/com_tjlms/assets/js/tjlms_admin.js');

$input = Factory::getApplication()->input;
$queryId = $input->get('queryId', '', 'INT');
$report = $input->get('reportToBuild','','string');
$currentQuery = $report . '_' . $queryId;
?>

<script>
	jQuery(document).click(function(e)
	{
		if (!jQuery(e.target).closest('#ul-columns-name').length && e.target.id != 'show-hide-cols-btn')
		{
			jQuery(".ColVis_collection").hide();
		}
	});

	jQuery(document).ready(function(){
		getPaginationBar();
	});
</script>

<div class="<?php echo COM_TJLMS_WRAPPER_DIV ?>">

		<?php
			ob_start();
			include JPATH_BASE . '/components/com_tjlms/layouts/header.sidebar.php';
			$layoutOutput = ob_get_contents();
			ob_end_clean();
			echo $layoutOutput;

		?> <!--// JHtmlsidebar for menu ends-->


		<form action="<?php echo Route::_('index.php?option=com_tjlms&view=reports'); ?>" method="post" name="adminForm" id="adminForm">
			<div class="report-top-bar row-fluid">
				<?php if (empty($this->items)): ?>
					<div class="alert alert-warning">
						<?php echo Text::_('COM_TJLMS_NO_REPORT'); ?>
					</div>
				<?php else: ?>

			<div class="span6">
				<div class="span12">
					<div class="show-hide-cols span6">
						<input type="button" id="show-hide-cols-btn" class="btn btn-success" onclick="getColNames(); return false;" value="<?php echo Text::_('COM_TJLMS_HIDE_SHOW_COL_BUTTON'); ?>"></button>
						<ul id="ul-columns-name" class="ColVis_collection" style="display:none">

					<?php	if (!empty($this->colToshow)):
									$this->colToshow = json_decode($this->colToshow);
								endif;
							foreach ($this->colNames as $constant => $colName):
						?>
								<li>
									<label>
										<?php
											$disabled = '';
										 	if ($colName == 'id'):
												$disabled = 'disabled';
											endif;

											$checked = 'checked="checked"';
											if (!empty($this->colToshow)):
												if (!in_array($colName, $this->colToshow)):
													$checked = '';
												endif;
											endif;
										?>

										<input type="checkbox" <?php echo $checked; ?> name="<?php echo $colName;	?>" <?php echo $disabled; ?> id="<?php echo $colName;	?>">
											<span><?php echo Text::_($constant);	?></span>
									</label>
								</li>
							<?php endforeach; ?>
						</ul>
					</div>
					<?php if (!empty($this->saveQueriesList)): ?>
						<div class="span6">
								<?php echo HTMLHelper::_('select.genericlist', $this->saveQueriesList, "filter_saveQuery", 'class="" size="1" onchange="getQueryResult(this.value);" name="filter_saveQuery"', "value", "text", $currentQuery);
								?>
						</div>
					<?php endif; ?>
			</div>
	</div>
			<div class="span6">
				<div class="span12">
					<div class="hide" id="queryName" >
						<input type="text" class="input-medium" name="queryName" placeholder="<?php echo Text::_('COM_TJLMS_TITLE_FOR_QUERY'); ?>"/>
					</div>

					<div class="span9" id="saveQuery">
						<input type="button" class="btn btn-primary pull-right" id="saveQuery" onclick="saveThisQuery();" value="<?php echo Text::_('COM_TJLMS_SAVE_THIS_QUERY'); ?>" />
					</div>
					<div class="span2 pull-right">
						<div id="reportPagination" class="pull-right ">
							<select id="list_limit" name="list[limit]" class="input-mini chzn-done" onchange="getFilterdata(0, '','paginationLimit')">
								<option value="5" >5</option>
								<option value="10">10</option>
								<option value="15">15</option>
								<option value="20" selected="selected">20</option>
								<option value="25">25</option>
								<option value="30">30</option>
								<option value="50">50</option>
								<option value="100">100</option>
								<option value="0">All</option>
							</select>
						</div>
				</div>
		</div>
</div>



				<?php if ($report == 'attemptreport'): ?>
					<div>
						<hr class="hr hr-condensed" />
						<div class="pull-right">
							<?php $tableFilters = $mainframe->getUserState("com_tjlms." . $report ."_table_filters", '');	?>
							<?php $fromdate = isset($tableFilters['fromDate']) ? $tableFilters['fromDate'] : ''; ?>
							<?php $toDate = isset($tableFilters['toDate']) ? $tableFilters['toDate'] : ''; ?>
							<div class="filter-search btn-group ">
								<?php echo HTMLHelper::_('calendar', $fromdate, 'attempt_begin', 'attempt_begin', '%Y-%m-%d', array('value'=>date("Y-m-d") ,'class'=>'dash-calendar validate-ymd-date required', 'size' => 10,'placeholder'=>"From (YYYY-MM-DD)")); ?>
							</div>
							<div class="filter-search btn-group ">
								<?php echo HTMLHelper::_('calendar', $toDate, 'attempt_end', 'attempt_end', '%Y-%m-%d', array('class'=>'dash-calendar required validate-ymd-date','size' => 10,'placeholder'=>"To (YYYY-MM-DD)")); ?>
							</div>

							<div class="btn-group filter-btn-block input-append">
								<button class="btn hasTooltip" onclick="getFilterdata('-1','','datesearch'); return false;" title="<?php echo Text::_('JSEARCH_FILTER_SUBMIT'); ?>"><i class="icon-search"></i></button>
								<button class="btn hasTooltip"  type="button" title="<?php echo Text::_('JSEARCH_FILTER_CLEAR'); ?>" onclick="cleardate(); return false;"><i class="icon-remove"></i></button>
							</div>
						</div>
					</div>

					<div style="clear:both"></div>
				<hr class="hr hr-condensed" />
				<?php endif; ?>

				<div id="report-containing-div">
					<?php echo $this->items['html']; ?>
				</div>
					<div class="pagination">
						<ul id="pagination-demo" class="pagination-sm ">
						</ul>
					</div>
					<input type="hidden" id="task" name="task" value="" />
					<input type="hidden" name="boxchecked" value="0" />
					<input type="hidden" name="totalRows" id="totalRows" value="<?php echo $this->items['total_rows']; ?>" />
					<?php echo HTMLHelper::_('form.token'); ?>
			<?php endif; ?>
		</form>
</div>
<script>

function getColNames()
{
	jQuery('.ColVis_collection').toggle();
}

function getQueryResult(id)
{
	var queryId = id.split("_");

	window.location.href = 'index.php?option=com_tjlms&view=reports&savedQuery=1&reportToBuild='+queryId[0]+'&queryId='+queryId[1];
}

jQuery(document).ready(function()
{
	var report = '<?php echo $report; ?>';
	jQuery('#'+report).addClass('active btn-primary');

	jQuery('.ColVis_collection input').click(function(){

		if (jQuery(".ColVis_collection input:checkbox:checked").length > 0)
		{
			getFilterdata(-1, '', 'hideShowCols');
		}
		else
		{
			var msg = Joomla.Text._('COM_TJLMS_REPORTS_CANNOT_SELECT_NONE');
			alert(msg);
			return false;
		}
	});
});

function loadReport(reportToLoad)
{
	var action = document.adminForm.action;
	var newAction = action+'&reportToBuild='+reportToLoad;

	window.location.href = newAction;
}

function cleardate()
{
	jQuery("#attempt_begin").val('');
	jQuery("#attempt_end").val('');
	getFilterdata(-1, '', 'dateSearch');
}
</script>

<style>
.show-hide-cols
{
	position:relative;
}

.ColVis_collection
{
	list-style: none;
	width: 150px;
	padding: 8px 8px 4px 8px;
	margin: 0;
	border: 1px solid #ccc;
	border: 1px solid rgba( 0, 0, 0, 0.4 );
	background-color: #f3f3f3;
	overflow: hidden;
	display: block;
	opacity: 1;
	position: absolute;
	z-index: 1;
}

.report-top-bar
{
	margin-top:10px;
}
</style>
