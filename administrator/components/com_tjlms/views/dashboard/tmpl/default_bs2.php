<?php
/**
 * @package     Shika
 * @subpackage  com_tjlms
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Component\ComponentHelper;

$document = Factory::getDocument();
HTMLHelper::stylesheet('media/techjoomla_strapper/bs3/css/bootstrap.css');
$document->addScript(Uri::root(true) . '/administrator/components/com_tjlms/assets/js/ajax_file_upload.js');
$document->addScript(Uri::root(true) . '/administrator/components/com_tjlms/assets/js/migration.js');
$document->addScript(Uri::root() . 'libraries/techjoomla/assets/js/houseKeeping.js');
$document->addScriptDeclaration("var tjHouseKeepingView='dashboard';");


// Load jQuery.
if (JVERSION >= '3.0')
{
	HTMLHelper::_('jquery.framework');
}

//Variables for values in blocks
$TotalCourse            = $this->DashboardDetails['TotalCourse'];
$TotalStudents          = $this->DashboardDetails['TotalStudents'];
$TotalPendingEnrollment = $this->DashboardDetails['TotalPendingEnrollment'];
$TotalPaidCourse        = $this->DashboardDetails['TotalPaidCourse'];
$TotalFreeCourse        = $this->DashboardDetails['TotalFreeCourse'];
$TotalOrders            = $this->DashboardDetails['totalOrders'];
$TotalRevenue           = $this->DashboardDetails['totalRevenueAmount'];

// Import helper for declaring language constant
JLoader::import('TjlmsHelper', Uri::root().'administrator/components/com_tjlms/helpers/tjlms.php');
// Call helper function
TjlmsHelper::getLanguageConstant();
?>
<script type="text/javascript" src="https://www.google.com/jsapi"></script>

<div class="<?php echo COM_TJLMS_WRAPPER_DIV;?> tjlms-dashboard">
	<?php
	ob_start();
	include JPATH_BASE . '/components/com_tjlms/layouts/header.sidebar.php';
	$layoutOutput = ob_get_contents();
	ob_end_clean();
	echo $layoutOutput;
	?>
	<div class="fix-info">
		<div class="progress-container">
		</div>
	</div>
	<?php

		if ($this->isArabic)
		{
		?>
			<div class="alert alert-info">
				<?php echo Text::_('COM_TJLMS_DOWNLOAD_ARABIC_LIB_INFO'); ?>
				<a href="#" onclick="tjlmsAdmin.dashboard.downloadArabicLib()">
					<?php echo Text::_('COM_TJLMS_DOWNLOAD_ARABIC_CLICK_HERE'); ?>
				</a>
			</div>
		<?php
		}
	?>

	<!-- TJ Bootstrap3 -->
	<div class="tjBs3">
		<!-- TJ Dashboard -->
		<div class="tjDB">
			<div id="wrapper">
				<!-- Start - stat boxes -->
				<div class="row">
					<?php if ($this->admin_approval == 1 || ($this->paid_course_admin_approval == 1 && $this->allow_paid_courses == 1)): ?>
								<?php $dashboardStatClass = 'col-lg-4';	?>
					<?php else: ?>
								<?php $dashboardStatClass = 'col-lg-6'; ?>
					<?php endif; ?>
					<div class="<?php echo $dashboardStatClass; ?> col-md-4">
						<div class="panel panel-green">
							<div class="panel-heading">
								<div class="row">
									<div class="col-xs-3 ">
										<i class="fa fa-4x fa-book"></i>
									</div>
									<div class="col-xs-9 text-right">
										<div class="huge"><?php echo !empty($TotalCourse) ? $TotalCourse : "0"; ?></div>
										<div><?php echo Text::_('COM_TJLMS_TOTAL_COURSE_NUM');?></div>
									</div>
								</div>
							</div>
							<a href="<?php echo Route::_('index.php?option=com_tjlms&view=courses&filter[state]=1', false);?>">
								<div class="panel-footer">
									<span class="pull-left">
										<?php echo Text::_('COM_TJLMS_VIEW_DETAILS');?>
									</span>
									<span class="pull-right">
										<i class="fa fa-arrow-circle-right"></i>
									</span>
									<div class="clearfix"></div>
								</div>
							</a>
						</div>
					</div>
					<div class="<?php echo $dashboardStatClass; ?> col-md-4">
						<div class="panel panel-primary">
							<div class="panel-heading">
								<div class="row">
									<div class="col-xs-3">
										<i class="fa  fa-4x fa-user"></i>
									</div>
									<div class="col-xs-9 text-right">
										<div class="huge"><?php echo !empty($TotalStudents) ? $TotalStudents : "0"; ?></div>
										<div><?php echo Text::_('COM_TJLMS_TOTAL_STUDENTS_NUM');?></div>
									</div>
								</div>
							</div>
							<a href="<?php echo Route::_('index.php?option=com_tjlms&view=manageenrollments&filter[state]=1', false);?>">
								<div class="panel-footer">
									<span class="pull-left">
										<?php echo Text::_('COM_TJLMS_VIEW_DETAILS');?>
									</span>
									<span class="pull-right">
										<i class="fa fa-arrow-circle-right"></i>
									</span>
									<div class="clearfix"></div>
								</div>
							</a>
						</div>
					</div>
					<?php if ($this->admin_approval == 1 || ($this->paid_course_admin_approval == 1 && $this->allow_paid_courses == 1)): ?>
					<div class="<?php echo $dashboardStatClass; ?> col-md-4">
						<div class="panel panel-yellow">
							<div class="panel-heading">
								<div class="row">
									<div class="col-xs-3">
										<i class="fa  fa-4x fa-users"></i>
									</div>
									<div class="col-xs-9 text-right">
										<div class="huge"><?php echo !empty($TotalPendingEnrollment) ? $TotalPendingEnrollment : "0"; ?></div>
										<div><?php echo Text::_('COM_TJLMS_TOTAL_PENDING_ENROLLMENT');?></div>
									</div>
								</div>
							</div>
							<a href="<?php echo Route::_('index.php?option=com_tjlms&view=manageenrollments&filter[state]=0', false);?>">
								<div class="panel-footer">
									<span class="pull-left">
										<?php echo Text::_('COM_TJLMS_VIEW_DETAILS');?>
									</span>
									<span class="pull-right">
										<i class="fa fa-arrow-circle-right"></i>
									</span>
									<div class="clearfix"></div>
								</div>
							</a>
						</div>
					</div>
					<?php endif; ?>
				</div>
				<!--STAT BOX ROW1 ENDS-->

				<!--STATBOX ROW2-->
				<?php if ($this->allow_paid_courses == 1): ?>
				<div class="row">

					<div class="col-lg-3 col-md-6">
						<div class="panel panel-red">
							<div class="panel-heading">
								<div class="row">
									<div class="col-xs-3">
										<i class="fa  fa-4x fa-credit-card"></i>
									</div>
									<div class="col-xs-9 text-right">
										<div class="huge"><?php echo !empty($TotalPaidCourse) ? $TotalPaidCourse : "0"; ?></div>
										<div><?php echo Text::_('COM_TJLMS_TOTAL_PAID_COURSE');?></div>
									</div>
								</div>
							</div>
							<a href="<?php echo Route::_('index.php?option=com_tjlms&view=courses&filter[type]=1&filter[state]=1', false);?>">
								<div class="panel-footer">
									<span class="pull-left">
										<?php echo Text::_('COM_TJLMS_VIEW_DETAILS');?>
									</span>
									<span class="pull-right">
										<i class="fa fa-arrow-circle-right"></i>
									</span>
									<div class="clearfix"></div>
								</div>
							</a>
						</div>
					</div>

					<div class="col-lg-3 col-md-6">
						<div class="panel panel-yellow">
							<div class="panel-heading">
								<div class="row">
									<div class="col-xs-3">
										<i class="fa  fa-4x fa-child"></i>
									</div>
									<div class="col-xs-9 text-right">
										<div class="huge"><?php echo !empty($TotalFreeCourse) ? $TotalFreeCourse : "0"; ?></div>
										<div><?php echo Text::_('COM_TJLMS_TOTAL_FREE_COURSE');?></div>
									</div>
								</div>
							</div>
							<a href="<?php echo Route::_('index.php?option=com_tjlms&view=courses&filter[type]=0', false);?>">
								<div class="panel-footer">
									<span class="pull-left">
										<?php echo Text::_('COM_TJLMS_VIEW_DETAILS');?>
									</span>
									<span class="pull-right">
										<i class="fa fa-arrow-circle-right"></i>
									</span>
									<div class="clearfix"></div>
								</div>
							</a>
						</div>
					</div>

					<div class="col-lg-3 col-md-6">
						<div class="panel panel-olive">
							<div class="panel-heading">
								<div class="row">
									<div class="col-xs-3">
										<i class="fa fa-shopping-cart fa-4x"></i>
									</div>
									<div class="col-xs-9 text-right">
										<div class="huge"><?php echo !empty($TotalOrders) ? $TotalOrders : "0"; ?></div>
										<div><?php echo Text::_('COM_TJLMS_TOTAL_ORDERS');?></div>
									</div>
								</div>
							</div>
							<a href="<?php echo Route::_('index.php?option=com_tjlms&view=orders&filter[statusfilter]=C', false);?>">
								<div class="panel-footer">
									<span class="pull-left">
										<?php echo Text::_('COM_TJLMS_VIEW_DETAILS');?>
									</span>
									<span class="pull-right">
										<i class="fa fa-arrow-circle-right"></i>
									</span>
									<div class="clearfix"></div>
								</div>
							</a>
						</div>
					</div>

					<div class="col-lg-3 col-md-6">
						<div class="panel panel-brown">
							<div class="panel-heading">
								<div class="row">
									<div class="col-xs-3">
										<i class="fa fa-money fa-4x"></i>
									</div>
									<div class="col-xs-9 text-right">
										<div class="huge"><?php
										$currencySymbol = $this->tjlmsparams->get('currency_symbol');
										echo !empty($TotalRevenue) ?  $currencySymbol . ' ' . $TotalRevenue : "0"; ?></div>
										<div><?php echo Text::_('COM_TJLMS_TOTAL_REVENUE');?></div>
									</div>
								</div>
							</div>
							<a href="<?php echo Route::_('index.php?option=com_tjlms&view=orders', false);?>">
								<div class="panel-footer">
									<span class="pull-left">
										<?php echo Text::_('COM_TJLMS_VIEW_DETAILS');?>
									</span>
									<span class="pull-right">
										<i class="fa fa-arrow-circle-right"></i>
									</span>
									<div class="clearfix"></div>
								</div>
							</a>
						</div>
					</div>

				</div>
				<!--STAT BOX ROW2 ENDS-->
				<?php endif; ?>

				<!-- Start - Line Chart Activity Status and orders -->
				<div class="row">
					<!-- Date Filter for activities & sales dahboard -->
					<form action="<?php echo Route::_('index.php?option=com_tjlms&view=dashboard');?>" method="post" name="adminForm" id="adminForm" class="col-md-12">
						<div id="filter-bar" class="row date-filter-style">
							<div class="dashboard-filter">
								 <?php if($this->state->get('filter.begin')) {$sdate = $this->state->get('filter.begin');} else {$sdate =  new JDate('now -1 month');} ?>
								  <?php if($this->state->get('filter.end')) {$edate = $this->state->get('filter.end');} else {$edate = new JDate('now');} ?>
							<div class="filter-search inline-block-class">
								<?php echo HTMLHelper::_('calendar', $sdate, 'filter_begin', 'filter_begin', '%Y-%m-%d', array('value'=>date("Y-m-d") ,'class'=>'dash-calendar validate-ymd-date required', 'size' => 10,'placeholder'=>"From (YYYY-MM-DD)")); ?>
							</div>
							<div class="filter-search inline-block-class">
								<?php echo HTMLHelper::_('calendar', $edate, 'filter_end', 'filter_end', '%Y-%m-%d', array('class'=>'dash-calendar required validate-ymd-date','size' => 10,'placeholder'=>"To (YYYY-MM-DD)")); ?>
							</div>
							<div class="btn-group filter-btn-block input-append">
								<button class="btn cal-btn hasTooltip" onclick="return getrecords()" title="<?php echo Text::_('JSEARCH_FILTER_SUBMIT'); ?>"><i class="icon-search"></i></button>
								<button class="btn cal-btn hasTooltip"  type="button" title="<?php echo Text::_('JSEARCH_FILTER_CLEAR'); ?>" onclick=" cleardate(); "><i class="icon-remove"></i></button>
							</div>
							</div>
						</div>
						<input type="hidden" name="task" value="" />
					</form>
					<!-- Date Filter for activities & sales dahboard -->

					<!-- Start - Line Chart Activity Status and orders -->
					<?php if ($this->allow_paid_courses == 1):
						$dc = "col-lg-6 col-md-6";
					else:
						$dc = "col-lg-12 col-md-6";
					endif;
					 ?>
					<div class="<?php echo $dc;?>">
						<div class="panel panel-default panel-activities">
							<div class="panel-heading">
								<i class="fa fa-line-chart fa-fw"></i>
								<?php echo Text::_('COM_TJLMS_ACTIVITIES');?>
								<span class="chart_legends"><span class="activities_legend"><span class="legend_title">Activities : </span><span class="legend_color"></span></span><span class="sessions_legend"><span class="legend_title">Sessions : </span><span class="legend_color"></span></span></span>
							</div>
							<div class="panel-body">
								<div id="activity_chart_div">
									<?php if (empty($this->yourActivities)): ?>
										<div class="alert alert-success">
											<?php echo Text::_('COM_TJLMS_NO_DATA_PRESENT'); ?>
										</div>
									<?php endif; ?>
								</div>
								<div class="center">
									<?php echo Text::_('COM_TJLMS_ACTIVITIES');?>
								</div>
							</div>
						</div>
					</div>

					<?php if ($this->allow_paid_courses == 1): ?>
					<div class="col-lg-6 col-md-6">
						<div class="panel panel-default panel-sales-graph">
							<div class="panel-heading">
								<i class="fa fa-line-chart fa-fw"></i>
								<?php echo Text::_('COM_TJLMS_SALES_AMOUNT');?>
							</div>
							<div class="panel-body">
								<div id="sales_graph">
									<?php if (empty($this->revenueData)): ?>
									<div class="alert alert-success">
										<?php echo Text::_('COM_TJLMS_NO_DATA_PRESENT'); ?>
									</div>
									<?php endif; ?>
								</div>
								<div class="center">
									<?php echo Text::_('COM_TJLMS_SALES_AMOUNT');?>
								</div>
							</div>
						</div>
					</div>
					<?php endif; ?>

				</div>
				<!-- End - Line Chart Activity Status and orders -->

				<div class="row">
					<?php if (!empty($this->popularStudent) ):  ?>
					<div class="col-lg-6 col-md-6">
						<div class="panel panel-default">
							<div class="panel-heading">
								<i class="fa fa-user"></i>
								<?php echo Text::_('COM_TJLMS_MOST_ACTIVE_STUDENT');?>
							</div>

							<div class="panel-body">
							<?php
							$integrationOption = $this->tjlmsparams->get('social_integration', 'joomla');
							$divClosed = true;
							foreach($this->popularStudent as $i => $popStudent)
							{
								// CHange this part. Add actual image of the user later.
								$student = Factory::getUser($popStudent->user_id);
								$profile_url = $this->comtjlmsHelper->sociallibraryobj->getProfileUrl($student);
								$popStudent->path	= str_replace('administrator/' , '',$profile_url);
								$imageToUse =	$this->comtjlmsHelper->sociallibraryobj->getAvatar($student);
								?>
								<?php if (!($i % 2)) : $divClosed = false;?>
									<div class="row">
								<?php endif; ?>
										<div class="col-lg-6 col-md-6">
											<div class="media">
												<?php if (!empty($imageToUse)):	?>
												<div class="pull-left">
													<?php if ($integrationOption != 'joomla') :?>
														<a class="media-left media-middle" href="<?php echo $popStudent->path; ?>">
													<?php endif; ?>
														<img class="media-object img-circle smallcircularimages" src="<?php echo $imageToUse; ?>">
													<?php if ($integrationOption != 'joomla') :?>
														</a>
													<?php endif; ?>
												</div>
												<?php else:	?>
													<i class="pull-left fa fa-4x fa-user"></i>
												<?php endif; ?>
												<div class="media-body">
													<h4 class="media-heading"><?php echo $userName = ( $this->show_user_or_username == 'name' ? $popStudent->name : $popStudent->username ); ?></h4>
														<div class="media">
															<span ><?php echo Text::sprintf('COM_TJLMS_STUDENT_ENROLLED_IN',$popStudent->enrolledIn); ?></span>
														</div>
												</div>
											</div>
										</div>
									<?php if ($i % 2) : $divClosed = true;?>
									</div><!--row-->
									<?php endif; ?>
							<?php } ?>
									<?php if (!$divClosed): ?>
									</div><!--row-->
									<?php endif; ?>
							</div><!--panel-body-->
						</div><!--panel panel-default-->
					</div><!--col-lg-6 col-md-6-->
					<?php endif;	?>

					<?php
					$divClosed = true;
					if (!empty($this->mostLikedCourses) ): ?>
						<div class="col-lg-6 col-md-6">
							<div class="panel panel-default">
								<div class="panel-heading">
									<i class="fa fa-thumbs-up"></i>
									<?php echo Text::_('COM_TJLMS_MOST_POPULAR_COURSE');?>
								</div>

								<div class="panel-body">
									<?php
									foreach ($this->mostLikedCourses as $i => $mostLiked)
									{
										$imageToUse = $this->tjlmsCoursesHelper->getCourseImage((array)$mostLiked, 'S_');
										?>
										<?php if (!($i % 2)) : $divClosed = false;?>
											<div class="row">
										<?php endif; ?>
												<div class="col-lg-6 col-md-6">
													<div class="media">
														<img class="pull-left media-object img-circle smallcircularimages" src="<?php echo $imageToUse; ?>">
														<div class="media-body">
															<h4 class="media-heading"><?php echo $mostLiked->title; ?></h4>
																<div class="media">
																	<span ><?php echo Text::sprintf('COM_TJLMS_COURSE_LIKED',$mostLiked->like_cnt); ?></span>
																</div>
														</div>
													</div>
												</div>
										<?php if ($i % 2) : $divClosed = true;?>
											</div><!--row-->
										<?php endif; ?>
									<?php
									} ?>
									<?php if (!$divClosed): ?>
										</div><!--row-->
									<?php endif; ?>
								</div><!--panel-body-->
							</div><!--panel panel-default-->
						</div><!--col-lg-6 col-md-6-->
					<?php endif;	?>
				</div><!--row-->

			</div><!--ID wrapper ends-->
		</div><!--CLASS tjDB ends-->
	</div><!--CLASS tjBs3 ends-->

	</div><!--j-main-container" // coming from header.sidebar-->
	</div><!--row-fluid // coming from header.sidebar-->
</div><!--tjlms-wrapper-->
<style>
.alert-plain{background-color: #fafafa;color: #55595c;border: 1px solid #fafafa;}
.fix-info{display:none;}
.fix-info .after{display:none}
</style>
<script>
jQuery.event.special.widthChanged = {
	remove: function() {
		jQuery(this).children('iframe.width-changed').remove();
	},
	add: function () {
		var elm = jQuery(this);
		var iframe = elm.children('iframe.width-changed');
		if (!iframe.length) {
			iframe = jQuery('<iframe/>').addClass('width-changed').prependTo(this);
		}
		var oldWidth = elm.width();
		function elmResized() {
			var width = elm.width();
			if (oldWidth != width) {
				elm.trigger('widthChanged', [width, oldWidth]);
				oldWidth = width;
			}
		}

		var timer = 0;
		var ielm = iframe[0];
		(ielm.contentWindow || ielm).onresize = function() {
			clearTimeout(timer);
			timer = setTimeout(elmResized, 20);
		};
	}
}
Joomla.submitbutton = function(task)
{
	if (task == 'fixdatabase')
	{
		fixdatabase();
	}
	else if (task == 'fixColumnIndexes')
	{
		fixColumnIndexes();
	}
	else if (task == 'addReminderTmpls')
	{
		addReminderTemplates();
	}
	else
	{
		Joomla.submitform(task);
		return true;
	}
}

function getrecords()
{
	if (document.formvalidator.isValid(document.adminForm))
	{
		var filter_begin = jQuery('#filter_begin').val();
		var reportStartDate = new Date(filter_begin);
		reportStartDate.setHours(0, 0, 0, 0);

		var filter_end = jQuery('#filter_end').val();
		var reportEndDate = new Date(filter_end);
		reportEndDate.setHours(0, 0, 0, 0);

		if(reportStartDate > reportEndDate)
		{
			var dateValidationmsg = Joomla.JText._('COM_TJLMS_DATE_RANGE_VALIDATION');
			alert(dateValidationmsg);
			return false;
		}

		Joomla.submitform();
	}
	else
	{
		jQuery('#system-message-container').hide();
		alert('<?php echo $this->escape(JText::_('COM_TJLMS_INVALID_DATE_FIELD')); ?>');
		return false;
	}
}

jQuery(document).ready(function(){
	jQuery( ".dash-calendar" ).blur(function() {
		if (!document.formvalidator.isValid(document.adminForm))
		{
			jQuery('#system-message-container').hide();
			return false;
		}
	});
});

function cleardate()
{
	jQuery("#filter_begin").val('');
	jQuery("#filter_end").val('');
	jQuery('#adminForm').submit();
}

<?php if ($this->allow_paid_courses == 1): ?>
	<?php if (!empty($this->revenueData)): ?>
	function getSalesGraph(){
		Morris.Area({
			element: 'sales_graph',
			data: <?php echo json_encode($this->revenueData);?>,
			xkey: 'date',
			ykeys: ['amount'],
			labels: ['<?php echo JText::_('COM_TJLMS_STORE_SALES_AMOUNT'); ?>'],
			lineWidth: 2,
			hideHover: 'auto',
			lineColors: ["#30a1ec"]
		});
	}
	jQuery('.panel-sales-graph').on('widthChanged',function(){
		jQuery('#sales_graph').html('');
		getSalesGraph();
	});
	getSalesGraph();
	<?php endif; ?>
<?php endif; ?>

<?php if (!empty($this->yourActivities)): ?>
	function getActivities(){
		Morris.Line({
			element: 'activity_chart_div',
			data :<?php echo json_encode($this->yourActivities);?>,
			xkey: 'time',
			ykeys: ['activity_count','session_count'],
			labels: ['Activities','Sessions'],
			xLabels: 'day',
			lineColors: ['#FFA500','#3EA99F'],
			hideHover: 'auto',
			 resize: true,
		});
	};
	jQuery('.panel-activities').on('widthChanged',function(){
		jQuery('#activity_chart_div').html('');
		getActivities();
	});
	getActivities();
<?php endif; ?>
</script>
