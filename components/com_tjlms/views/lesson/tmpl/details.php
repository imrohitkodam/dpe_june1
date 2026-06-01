<?php
/**
 * @version    SVN: <svn_id>
 * @package    Tjlms
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access.
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;

HTMLHelper::_('bootstrap.renderModal', 'a.tjmodal');
$input = Factory::getApplication()->input;
$lesson_id = $input->get('lesson_id', '', 'int');
$this->lesson_data = $this->model->getlessondata($lesson_id);

?>
<div class="<?php echo COM_TJLMS_WRAPPER_DIV; ?>" >
	<div class="com_tjlms_content row-fluid">
		<div class="page-header">
			<div class="media" style="background-color:lightgrey">
				<div class="media-body">
					<div style="padding:15px">
						<div><h1 class="media-heading"><b><?php	echo strtoupper($this->lesson_data->name);?></b></h1>
						</div>
						<div class="media-content">

							<div class="long_desc">
							<?php

							if(strlen($this->lesson_data->description) > 75 )
								echo "<h3>".nl2br($this->tjlmshelperObj->html_substr($this->lesson_data->description, 0, 75 )).'<a href="javascript:" class="r-more">' . Text::_("COM_TJLMS_TOC_COURSE_DESC_MORE") . '</a></h3>';
							else
								echo nl2br($this->lesson_data->description);
							?>
							</div>
							<div class="long_desc_extend" style="display:none;">
							<?php
								echo "<h3>".nl2br($this->lesson_data->description).'<a href="javascript:" class="r-less">' . Text::_("COM_TJLMS_TOC_COURSE_DESC_LESS") . '</a></h3>';
							?>
							</div>
						</div><!--media-contenta-->
						<div class="pull-right">
							<?php
								if($this->lesson_data->end_date=="0000-00-00 00:00:00")
								{
									$text=Text::_("COM_TJLMS_AVAILABLE");
									echo "<div class='btn nav nav-pills btn-success'>". $text . "</div>";
								}
								else
								{
									if($this->lesson_data->end_date < date("Y-m-d H:i:s"))
									{
										$text=Text::_("COM_TJLMS_EXPIRED");
										echo "<div class='btn nav nav-pills btn-danger'>". $text . "</div>";

									}
									else
									{
										$text=Text::_("COM_TJLMS_AVAILABLE");
										echo "<div class='btn nav nav-pills btn-success'>". $text . "</div>";
									}
								}
							?>
						</div>
					</div>
				</div><!-- media-body DIV-->
			</div><!-- Media DIV-->
			<div class="clearfix"></div>
		</div><!-- page-header-->

		<div class="lesson_info">
			<table class="table table-bordered">
				<div class="row-fluid lesson_info_details">
					<tr><td>
					<div class="span6 tjlms-bold-text right-border"><?php echo Text::_("COM_TJLMS_FORM_LESSON_NO_OF_ATTEMPTS")?> </div></td>
					<td><div class="span5"><?php if($this->lesson_data->no_of_attempts==0){echo "Unlimited";}else{echo $this->lesson_data->no_of_attempts;} ?></div></td></tr>
				</div>

				<div class="row-fluid lesson_info_details">
					<tr><td>
					<div class="span6 tjlms-bold-text right-border"><?php echo Text::_("COM_TJLMS_FORM_LESSON_ATTEMPTS_GRADE")?>  </div></td>
					<td><div class="span5">
						<?php
						switch($this->lesson_data->attempts_grade)
						{
							case '0':
								echo Text::_("COM_TJLMS_GRADE_HIGHEST_ATTEMPT");
							break;
							case '1':
								echo Text::_("COM_TJLMS_GRADE_AVERAGE_ATTEMPT");
							break;
							case '2':
								echo Text::_("COM_TJLMS_GRADE_FIRST_ATTEMPT");
							break;
							case '3':
								echo Text::_("COM_TJLMS_GRADE_LAST_COMPLETED_ATTEMPT");
							break;

						}
						?>
					</div></td></tr>
				</div>

				<div class="row-fluid lesson_info_details">
					<tr><td>
					<div class="span6 tjlms-bold-text right-border"><?php echo Text::_("COM_TJLMS_FORM_LESSON_CONSIDER_MARKS")?> </div></td>
					<td><div class="span5">

						<?php
						switch($this->lesson_data->consider_marks)
						{
							case '0':
								echo Text::_("COM_TJLMS_NO");
							break;
							case '1':
								echo Text::_("COM_TJLMS_YES");
							break;
						}

						?></div></td></tr>
				</div>
				<?php if(!($this->lesson_data->start_date == "0000-00-00 00:00:00")){?>
				<div class="row-fluid lesson_info_details">
					<tr><td>
					<div class="span6 tjlms-bold-text right-border"><?php echo "Start date";?> </div></td>
					<td><div class="span5">
								<?php echo $this->lesson_data->start_date;?>
					</div></td></tr>
				</div>
				<?php }?>
				<?php if(!($this->lesson_data->end_date == "0000-00-00 00:00:00")){?>
				<div class="row-fluid lesson_info_details">
					<tr><td>
					<div class="span6 tjlms-bold-text right-border"><?php echo "end date";?> </div></td>
					<td><div class="span5">
								<?php echo $this->lesson_data->end_date;?>
					</div></td></tr>
				</div>
				<?php }?>
				<?php if(isset($this->lesson_data->eligibilty_lessons)){?>
					<div class="row-fluid lesson_info_details">
						<tr><td>
						<div class="span6 tjlms-bold-text right-border">
							<?php
									if($this->lesson_data->format=="textmedia")
									{
										$contentformat=Text::_("TJLMS_LESSON");
									}
									else
									{
										$contentformat=Text::_("TJLMS_".strtoupper($this->lesson_data->format));
									}
									echo Text::sprintf("TJLMS_ELIGIBILITY_CRITERIA",$contentformat);
							?></div></td>
						<td><div class="span5">
									<?php echo implode(',',$this->lesson_data->eligibilty_lessons);?>
						</div></td></tr>
					</div>
				<?php }?>
				<div class="row-fluid lesson_info_details">
					<tr><td>
					<div class="span6 tjlms-bold-text right-border"><?php echo Text::_('TJLMS_CONTENT_FORMAT');?> </div></td>
					<td><div class="span5">
								<?php echo Text::_("TJLMS_".strtoupper($this->lesson_data->format));?>
					</div></td><tr>
				</div>
				<div class="row-fluid lesson_info_details">
					<tr><td>
					<div class="span6 tjlms-bold-text right-border"><?php echo Text::_('TJLMS_MARKING_PASSING');?></div></td>
					<td><div class="span5">
								<?php
								if($this->lesson_data->consider_marks)
								{
									echo Text::_("COM_TJLMS_YES");
								}
								else
								{
									echo Text::_("COM_TJLMS_NO");
								}
								?>
					</div></td></tr>
				</div>
			</table>
		</div>
	</div>
</div>
