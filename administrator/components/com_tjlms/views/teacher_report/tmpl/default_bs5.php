<?php
/**
 * @version     1.0.0
 * @package     com_tjlms
 * @copyright   Copyright (C) 2014. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      TechJoomla <extensions@techjoomla.com> - www.techjoomla.com
 */

// no direct access
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

if (JVERSION >= '3.0')
{
	HTMLHelper::_('bootstrap.tooltip');
	HTMLHelper::_('behavior.multiselect');
}
else
{
	HTMLHelper::_('behavior.formvalidator');
	HTMLHelper::_('formbehavior.chosen', 'select');
	HTMLHelper::_('behavior.modal');
}

$document = Factory::getDocument();

$this->currentVersion = 1.0;
// Load jQuery.
if (JVERSION >= '3.0')
{
	HTMLHelper::_('jquery.framework');
}

// Take date a one year back in past.
$backdate = date('Y-m-d', strtotime(date('Y-m-d').' - 365 days'));

?>
<script>
jQuery(document).ready(function() {
	var width = jQuery(window).width();
	var height = jQuery(window).height();
	jQuery('a.teacher_report').attr('rel','{handler: "iframe", size: {x: '+(width-(width*0.10))+', y: '+(height-(height*0.10))+'}, classWindow: "tjlms-modal"}');
});
</script>

<div class="<?php echo COM_TJLMS_WRAPPER_DIV;?> tjlms-teacher-dashboard">
	<!--HEADER TEACHER_DASHBOARD-->
	<div class="row page-header">
		<div class="col-md-12">
			<!--DASHBOARD HEADING-->
			<h3><?php echo Text::sprintf('COM_TJLMS_TEACHER_DASHBOARD_HEADING',$this->CourseName); ?></h3>
		</div>
	</div>
	<!--HEADER TEACHER_DASHBOARD ENDS-->

	<div id="main-content" class="">
		<div class="row">
				<div class="statbox col-md-3">
					<div class="statbox-overlay statbox-blue">
						<div class="inline-block-class">
							<i class="fa fa-users fa-2x fa-white statbox-icons"></i>
						</div>
						<div class="inline-block-class parent-statbox-value">
							<div class="statbox-value">
								<?php echo !empty($this->EnrollStudent) ? $this->EnrollStudent : "0"; ?>
							</div>
						</div>
						<div>
							<span class="statbox-title"><?php echo Text::_('COM_TJLMS_TOTAL_COURSE_ENROL_NUM'); ?></span>
						</div>
					</div>
				</div>

				<div class="statbox col-md-3">
					<div class="statbox-overlay statbox-red">
						<div class="inline-block-class">
							<i class="fa fa-thumbs-up fa-2x fa-white statbox-icons"></i>
						</div>
						<div class="inline-block-class parent-statbox-value">
							<div class="statbox-value">
								<?php echo !empty($this->pendingenrolStudent) ? $this->pendingenrolStudent : "0"; ?>
							</div>
						</div>
						<div>
							<span class="statbox-title"><?php echo Text::_('COM_TJLMS_TOTAL_COURSE_PENDING_ENROLMETS'); ?></span>
						</div>
					</div>
				</div>

				<div class="statbox col-md-3">
					<div class="statbox-overlay statbox-green">
						<div class="inline-block-class">
							<i class="fa fa-thumbs-up fa-2x fa-white statbox-icons"></i>
						</div>
						<div class="inline-block-class parent-statbox-value">
							<div class="statbox-value">
								<?php echo !empty($this->CompleteStudent) ? $this->CompleteStudent : "0"; ?>
							</div>
						</div>
						<div>
							<span class="statbox-title"><?php echo Text::_('COM_TJLMS_TOTAL_COURSE_COMPLETED'); ?></span>
						</div>
					</div>
				</div>

				<div class="statbox col-md-3">
					<div class="statbox-overlay statbox-yellow">
							<div class="inline-block-class">
								<i class="fa fa-thumbs-down fa-2x fa-white statbox-icons"></i>
							</div>
							<div class="inline-block-class parent-statbox-value">
								<div class="statbox-value">
									<?php echo !empty($this->IncompleteStudent) ? $this->IncompleteStudent : "0"; ?>
								</div>
							</div>
							<div>
								<span class="statbox-title"><?php echo Text::_('COM_TJLMS_TOTAL_COURSE_INCOMP_NUM'); ?></span>
							</div>
					</div>
				</div>

		</div>

		<div style="clear:both"></div>
		<?php

		if ($this->TopScorer)
		{
			?>
			<div class="row top-users">
				<div  class="panel-heading" >
					<?php echo Text::_('COM_TJLMS_TOP10_COURSE_SCORER'); ?>

					<?php if (count($this->TopScorer) > 10): ?>
						<a class="pull-right" href="<?php echo Uri::root().'administrator/index.php?option=com_tjlms&view=manageenrollments&tmpl=component&course_id='.$this->course_id.'&filter[coursefilter]='.$this->course_id;?>"><?php echo Text::_('COM_TJLMS_COURSE_VIEW_ALL');?></a>
					<?php endif; ?>
				</div>
				<div id="topscorer">
					<table class="table table-condensed  table-bordered">
						<thead>
							<tr>
								<th ><?php echo Text::_('COM_TJLMS_COURSE_ENROLL_USERNAME'); ?></th>
								<th ><?php echo Text::_('COM_TJLMS_COURSE_ENROLL_USER_PERCENTAGE_COMPLETION'); ?></th>
								<th ><?php echo Text::_('COM_TJLMS_TITLE_REPORT'); ?></th>
								<th ></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach($this->TopScorer as $scorer ){ ?>
							<tr>
								<td> <?php echo $scorer->uname; ?> </td>
								<td><?php echo floor($scorer->percentage); ?> </td>
								<!--<td><?php echo $scorer->status; ?> </td>-->
								<td><a  class="teacher_report tjlms-override-modal" href="<?php echo $scorer->path; ?>"><?php echo Text::_('COM_TJLMS_COURSE_ENROLL_USER_REPORT_VIEW'); ?></a></td>
							</tr>
						<?php } ?>
						</tbody>
					</table>
				</div>
			</div>

		<?php
		}
		?>
		<div class="row detail-charts">

			<?php if (isset($this->orderReport) && !empty($this->orderReport)): ?>
				<div class="span6">
			<?php else:	?>
				<div class="span12">
			<?php endif; ?>
					<div class="panel-heading grey_border_class ">
						<i class="fa fa-line-chart"></i>
						<span><?php echo Text::_('COM_TJLMS_ACTIVITYGRAPH_ACTIVITY_CHARTS'); ?></span>

						<div class="chart_legends">
							<span class="activities_legend">
								<span class="legend_title"><?php echo Text::_('COM_TJLMS_ACTIVITYGRAPH_ACTIVITIES'); ?></span>
								<span class="legend_color"></span>
							</span>
							<span class="sessions_legend">
								<span class="legend_title"><?php echo Text::_('COM_TJLMS_ACTIVITYGRAPH_SESSIONS'); ?></span>
								<span class="legend_color"></span>
							</span>
						</div>

					</div>
					<div class="side_padding">
						<div id="teacher-course-activity-chart-div"></div>
					</div>
				</div>

				<?php if (isset($this->orderReport) && !empty($this->orderReport)): ?>
					<div class="sales-graph span6">
						<div class="panel-heading grey_border_class ">
							<i class="fa fa-area-chart"></i>
							<span><?php echo Text::_('COM_TJLMS_SALES_AMOUNT');?></span>
						</div>
						<div class="panel-body">
							<div id="teacher-course-sales-graph">
								<?php if (empty($this->orderReport)): ?>
								<div class="alert alert-success">
									<?php echo Text::_('COM_TJLMS_NO_DATA_PRESENT'); ?>
								</div>
								<?php endif; ?>
							</div>
						</div>
					</div>
				<?php endif; ?>
		</div>

		<?php
		if (!empty($this->StudentwhoLiked))
		{
			?>
				<?php $noMarginSpan = ''; ?>
			<div class="row <?php echo $noMarginSpan; ?> ">
				<div  class="panel-heading" >
					<?php echo Text::_('COM_TJLMS_COURSE_LIKED_USER'); ?>
				</div>
					<!-- CHANGED BY RENU -->
					<?php
						foreach ($this->StudentwhoLiked as $index=>$popStudent)
						{ ?>
							<div class="col-md-3 media likedusers">
							<?php if (empty($popStudent->avatar)) : ?>
								<?php	$popStudent->avatar = Uri::root(true).'/media/com_tjlms/images/default/user.png';	?>
							<?php endif;	?>
							<?php if (!empty($popStudent->profileurl)) : ?>
								<a class="pull-left" target="_blank" href="<?php echo $popStudent->profileurl?>" >
							<?php endif;	?>
									<img class="img-circle solid-border pull-left" title="<?php echo $popStudent->username;?>" src="<?php echo $popStudent->avatar;?>" />
							<?php if (!empty($popStudent->profileurl)) : ?>
								</a>
							<?php endif;	?>

										<div class="media-body">
											<h6 class="media-heading"><?php echo $popStudent->username; ?></h6>
											<div class="media">
												<span style="display:none;"><?php echo Text::sprintf('COM_TJLMS_STUDENT_ENROLLED_IN',$popStudent->enrolledIn); ?></span>
											</div>
										</div>
									</div>
						<?php

						}
						?>
			</div><!--TOP ENROLLED STUDENT ENDS-->
		<?php
		}
		?>
	</div><!--MAIN ENDs-->
</div><!--BOOTSTRAP DIV-->
<style type="text/css">
@media (max-width: 767px) {
	.likedusers
	{
		display:inline-block !important;
		width:auto !important;
	}
}
</style>

<script>
Morris.Line({
		element: 'teacher-course-activity-chart-div',
		data :<?php echo json_encode($this->courseActivities);?>,
		xkey: 'time',
		ykeys: ['activity_count','session_count'],
		labels: ['Activities','Sessions'],
		xLabels: 'day',
		lineColors: ['#FFA500','#3EA99F'],
		hideHover: 'auto',
		 resize: true,
	});


<?php if (!empty($this->orderReport)): ?>
	Morris.Area({
		element: 'teacher-course-sales-graph',
		data: <?php echo json_encode($this->orderReport);?>,
		xkey: 'date',
		ykeys: ['amount'],
		labels: ['<?php echo Text::_('COM_TJLMS_STORE_SALES_AMOUNT'); ?>'],
		lineWidth: 2,
		hideHover: 'auto',
		lineColors: ["#30a1ec"]
	});

<?php endif; ?>
</script>
