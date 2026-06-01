<?php
/**
 * @package     Shika
 * @subpackage  com_tmt
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Component\ComponentHelper;

HTMLHelper::_('behavior.keepalive');
HTMLHelper::_('behavior.tooltip');
HTMLHelper::_('behavior.formvalidation');
HTMLHelper::_('jquery.framework');
HTMLHelper::_('behavior.modal');

$params         = ComponentHelper::getParams('com_tjlms');
$quiz_articleId = $params->get('quiz_articleId', '0', 'INT');

?>
<script type="text/javascript">
	Joomla.submitbutton = function(task){
		jQuery('#tmt_testpremise .btn').prop('disabled', true);
		Joomla.submitform(task);
	}

	jQuery(document).ready(function(){

		hideImage();

		<?php
		if ($this->item->termscondi == 1)
		{
		?>

			jQuery('.tmt-start-quiz').attr('disabled', 'disabled');

			jQuery('#terms').change(function()
			{
				if (this.checked)
				{
					jQuery('.tmt-start-quiz').removeAttr('disabled');
				}
				else
				{
					jQuery('.tmt-start-quiz').attr('disabled', 'disabled');
				}
			});
		<?php
		}
		?>
	});
</script>

<div id="tmt_testpremise" class="tmt_testpremise mt-30">
	<div class="row">
		<div class="col-md-4 col-md-offset-4 col-sm-12">
			<div class="tmt  tmt-start mb-20 br-0">
				<?php
				if ($this->item->description) : ?>
					<div class="row">
						<div class="col-sm-12">
							<div class="tmt-quiz-desc">
								<div class="panel panel-default br-0 m-0">
									<div class="panel-heading text-center">
									<h3 class="mt-0 text-center test-heading p-10 border-0">
										<?php echo Text::_('COM_TMT_TEST_PREMISE_SHORT_DESCRIPATION');?>
									</h3>
									<div class="px-15 long_desc">
										<span>
											<?php
												if ($this->item->description)
												{
													if (strlen(strip_tags($this->item->description)) > 150 )
													{
														echo $this->tjlmshelperObj->html_substr(htmlentities($this->item->description), 0, 150) .
														'<a href="javascript:" class="r-more">' . Text::_("COM_TMT_TEST_DESC_MORE") . '</a>';
													}
													else
													{
														echo $this->tjlmshelperObj->html_substr(htmlentities($this->item->description), 0);
													}
												}
											?>
										</span>
									</div>
									<div class="long_desc_extend long_desc_extend_more">
										<?php
											echo htmlentities($this->item->description) . '<a href="javascript:" class="r-less">' .
											Text::_("COM_TMT_TEST_DESC_LESS") . '</a>';
										?>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<?php endif; ?>
				<!--row-mb-10-->
				<div class="row my-10 mx-0">
					<div class="col-sm-12">
						<p>
							<i class="fa fa-question" aria-hidden="true"></i>
							<?php echo Text::sprintf('COM_TMT_TEST_PREMISE_QUESTION_CNT', $this->item->test_questions); ?>
						</p>
							<?php if (!($this->item->gradingtype == 'feedback')) :?>
						<hr class="my-10">
						<p>
							<i class="fa fa-plus" aria-hidden="true"></i>
							<?php echo Text::_('COM_TMT_TEST_PREMISE_TOTALMARKS') . $this->item->total_marks; ?>
						</p>

						<hr class="my-10">

						<?php endif; ?>

					</div>
					<div class="col-sm-12 col-sm-12">

						<?php if (!($this->item->gradingtype == 'feedback')) :?>

						<p>
							<i class="fa fa-check" aria-hidden="true"></i>
							<?php echo Text::_('COM_TMT_TEST_PREMISE_PASSINGMARKS') . $this->item->passing_marks; ?>
						</p>

						<?php endif; ?>

						<hr class="my-10">

						<p>
							<i class="fa fa-clock-o" aria-hidden="true"></i>
							<?php
								if($this->item->time_duration == 0 || $this->item->time_duration == '' ){
									echo Text::sprintf('COM_TMT_TEST_NO_TIME_LIMIT', $this->item->time_duration);
								}
								else{
								echo Text::sprintf('COM_TMT_TEST_PREMISE_TEST_TIME', $this->item->time_duration);
							} ?>
						</p>

					</div>
				</div><!--/row-mb-10-->
			<div class="panel-footer">
				<div class="row">
				<!-- show form/items if items found -->
					<form method="post" name="adminForm" id="adminForm" class="form-inline">
						<?php if($this->item->termscondi){ ?>
							<div class="col-sm-12 mb-20 text-center test-question__answers">
								<label class="input-label"><input type="checkbox" name="terms" id="terms" value="0">
								<?php
									echo Text::_('COM_TMT_TERM_CONDITION_TEXT');
								?>
									<span class="checkmark"></span>
								</label>
								<span>
								<?php
									if ($quiz_articleId != 0)
									{
										$contenTable = Table::getInstance('content');
										$contenTable->load(array('id' => $quiz_articleId,'state' => '1'));
										 $links = Route::_('index.php?option=com_content&view=article&id=' . $quiz_articleId . '&catid=' .$contenTable->catid. '&tmpl=component');
										?>
										<a onclick="tjLmsCommon.loadPopup('<?php echo $links; ?>')" class="tjlms-override-modal tjmodal">
										<?php echo Text::_('COM_TMT_TERM_CONDITION_ARTICLE'); ?></a>
									<?php
									}
									?>
								</span>
							</div><!--col-sm-12-->
						<?php } ?>

						<?php
						$curr_date  = Factory::getDate()->Format(Text::_('COM_TMT_DATE_FORMAT_LONG_MYSQL'));
						?>
							<div id="bgrp">
								<?php
									if (!empty($this->item->attemptData) && $this->item->resume)
									{
										// Quiz has resume  support , so show already test
										if ($this->item->attemptData->lesson_status == 'started' || $this->item->attemptData->lesson_status == 'incomplete')
										{
											$link = 'index.php?option=com_tmt&tmpl=component&view=test&id=' . $this->item->id . '&invite_id=' . $this->item->attemptData->id . '&course_id=' . $this->item->courseId;

											$resumeLink = Route::_($link . '&page=' . $this->item->attemptData->current_position);
											?>

											<div class="col-md-4 col-sm-12  mb-10">
												<a type="button" class="btn btn-primary btn-block tmt-start-quiz" href="<?php echo $link; ?>">
													<?php echo Text::_('COM_TMT_BUTTON_START_TEST_FROM_BEGINING'); ?>
												</a>
											</div>

											<div class="col-md-4 col-sm-12  mb-10">
												<a type="button" class="btn btn-success btn-block tmt-start-quiz" href="<?php echo $resumeLink; ?>">
													<?php echo Text::_('COM_TMT_BUTTON_START_TEST_RESUME'); ?>
												</a>
											</div>
											<?php
										}
									}
									else
									{
									?>
										<div class="col-md-4 col-sm-12  mb-10">
											<button type="button" class="btn btn-info btn-block tmt-start-quiz" onclick="Joomla.submitbutton('testpremise.startTest')">
												<?php echo Text::_('COM_TMT_BUTTON_START_TEST'); ?>
											</button>
										</div>
									<?php
									}
									?>

								<div class="col-md-4 col-sm-12  mb-10">
									<button type="button" class="btn btn-danger btn-block closeBtn" data-js-id="test-premise-close">
										<?php echo Text::_('COM_TMT_BUTTON_CANCEL_TEST'); ?>
									</button>
								</div>
							</div>

						<input type="hidden" name="option" value="com_tmt" />
						<input type="hidden" name="view" value="testpremise" />
						<input type="hidden" name="task" value="" />
						<!--input type="hidden" name="resume" value="<?php echo $this->item->resume;?>" /-->
						<input type="hidden" name="course_id" value="<?php echo $this->item->courseId;?>" />
						<input type="hidden" name="attempt" value="<?php echo $attempt;?>" />
						<input type="hidden" name="lesson_id" value="<?php echo $this->item->lesson_id;?>" />
						<input type="hidden" name="id" value="<?php echo $this->item->id;?>" />
						<?php echo HTMLHelper::_('form.token'); ?>
					</form>
				</div><!--row-->
	         </div><!--footer-->
			</div>
		<div class="alert alert-info">
		<?php
		if ($this->item->resume)
		{
		?>
			<p>
				<?php echo Text::_('COM_TMT_TEST_PREMISE_RESUME_ALLOWED_MSG'); ?>
			</p>
		<?php
		}
		else
		{
		?>
			<p>
				<i class="fa fa-exclamation-triangle"></i>
				<?php echo Text::_('COM_TMT_TEST_PREMISE_RESUME_NOT_ALLOWED_MSG'); ?>
			</p>
		<?php
		}
		?>
		</div>
		</div>
	</div>
</div><!--tmt_testpremise-->
