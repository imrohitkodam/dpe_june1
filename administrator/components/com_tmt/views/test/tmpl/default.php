<?php
/**
 * @version     1.0.0
 * @package     com_tmt
 * @copyright   Copyright (C) 2013. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Techjoomla <contact@techjoomla.com> - http://techjoomla.com
 */
// no direct access
defined('_JEXEC') or die;

JHtml::_('behavior.keepalive');
JHtml::_('behavior.tooltip');
JHtml::_('behavior.formvalidation');
JHtml::_('behavior.modal', 'a.modal');
JHtml::_('jquery.ui', array('core', 'sortable'));


$allow_paid_courses = $this->tjlmsparams->get('allow_paid_courses','0','INT');
$terms_n_condition = $this->tjlmsparams->get('quiz_articleId','0','INT');
$maxattempt = '';
$count=0;

if (!isset($this->item->id))
{
	$this->item->id = 0;
}
else
{
	$lesson_id = $this->item->qid;
	$maxattempt=$this->item->max_attempt;
}

if (isset($lesson_id) && $lesson_id > 0){
	$maxattempt = $this->item->max_attempt;

	if (empty ($maxattempt))
	{
		$maxattempt = 0;
	}
}
?>

<script type="text/javascript">

	jQuery(document).ready(function(){

		getTotal();
		calculateAlertTime();
		loadalerttimeshow();
		var test_id = jQuery('.test_id').val();
		//jQuery('#allow_quiz').hide();
		jQuery('.set-quiz-rule').hide();

		var check_value = jQuery('#rules_checked').val();
		if(parseInt(check_value) == 1)
		{
			jQuery('#jform_quiz_type').prop('checked', true);
			jQuery('.set-quiz-rule').show();
	
		}
		else
		{
			jQuery('#jform_quiz_type').prop('checked', false);
			//jQuery('#rule_sets').hide();
			jQuery('.set-quiz-rule').hide();
		}

		var hiddenR=[];

		if (jQuery('#jform_time_duration').val().trim() == '' || jQuery('#jform_time_duration').val() == 0)
		{
			jQuery('#jform_show_time label').addClass("disabledradio");
			jQuery('#jform_show_time_finished label').addClass("disabledradio");
		}

		jQuery('#jform_nums_of_sets').hide();

		jQuery('#jform_quiz_type').on('click',function()
		{
			var temp = jQuery('#jform_quiz_type').attr('checked');
			if(temp == 'checked')
			{
				jQuery('.set-quiz-rule').show();
				var haveAny = jQuery('.rules-container .rule-template').length;
				if(haveAny < 2)// 1 hidden
				{
					addRuleClone('rule-template','rules_block','checkbox');
				}
				jQuery('#questions_btns').hide();
				jQuery('.single-set-marks').show();
				jQuery('#button_save_and_close').addClass('inactivelink');
			}
			else
			{
				// jQuery('#rule_sets').hide();
				jQuery('.set-quiz-rule').hide();
				jQuery('#questions_btns').show();
				jQuery('.single-set-marks').hide();
				jQuery('#button_save_and_close').removeClass('inactivelink');
			}
		});

		// Code to make blank row hidden while we edit the quiz
		if(jQuery('#rules_checked').val() == 1)
		{
			jQuery('#rule-template0 .new_rules').css('display','none');
		}

		if (test_id != '')
		{
			jQuery('#jform_quiz_type').closest(".control-group").hide();;
			return false;
			var params;
			params = ({'test_id':test_id});
			jQuery.ajax({
				url: 'index.php?option=com_tmt&view=test&task=test.isInUse',
				dataType: 'json',
				type: 'POST',
				data: params ,
				success: function (data) {

				if(data == 1)
				{
					jQuery('#jform_quiz_type').closest(".control-group").hide();
				}
				}
			});
		}

	});

jQuery(function()
{
	jQuery("tbody").sortable({
	scroll: false,
	items: "> tr:not(.non-sortable-tr-quiz)",
	start: function(event, ui) {
	}
	});
	jQuery("tbody").disableSelection();
});

/* Hide fetch questions button */
function hideFetchQuestions()
{
	var num = jQuery('.rule-template').filter(':visible').length;


	if(num == 0)
	{
		//jQuery('#fetch_questions').css('display', 'none');
	}
}

</script>

<div class="techjoomla-strapper">
<div id="tmt_test_form" class="tjlms_add_quiz_form">
	<fieldset>
		<legend>
		<?php if (!empty($this->item->id)): ?>
			<h2 class="componentheading"><?php echo JText::_('COM_TMT_HEADING_TEST_EDIT').$this->item->title; ?></h2>
		<?php else: ?>
			<h2 class="componentheading"><?php echo JText::_('COM_TMT_HEADING_TEST_CREATE');?></h2>
		<?php endif; ?>
		</legend>

		<form action="<?php echo JRoute::_('index.php?option=com_tmt&view=test&tmpl=component' . ( isset($this->addquiz)? '&addquiz=1' : '') . '&course_id=' . $this->course_id . '&mod_id=' . $this->mod_id . '&unique=' . $this->unique); ?>" method="post" name="adminForm" id="quiz-form_<?php echo $this->mod_id;?>" class="form-validate form-horizontal lesson_basic_form">
			<div class="tmt_form_errors alert alert-danger tmt-display-none">
				<div class="msg"></div>
			</div>

			<?php echo JHtml::_('bootstrap.startTabSet', 'myTab', array('active' => 'details')); ?>

				<?php echo JHtml::_('bootstrap.addTab', 'myTab', 'details', JText::_('COM_TMT_TEST_DETAILS', true)); ?>

				<div class="row-fluid">
						<div class="span6">
							<div class="control-group">
								<div class="control-label"><?php echo $this->form->getLabel('title'); ?></div>
								<div class="controls"><?php echo $this->form->getInput('title'); ?></div>
							</div>
							<div class="control-group">
								<div class="control-label"><?php echo $this->form->getLabel('alias'); ?></div>
								<div class="controls"><?php echo $this->form->getInput('alias'); ?></div>
							</div>
							<div class="control-group">
								<div class="control-label"><?php echo $this->form->getLabel('state'); ?></div>
								<div class="controls"><?php echo $this->form->getInput('state'); ?></div>
							</div>
							<div class="control-group">
								<div class="control-label"><?php echo $this->form->getLabel('description'); ?></div>
								<div class="controls"><?php echo $this->form->getInput('description'); ?></div>
							</div>

							<div class="control-group">
								<div class="control-label"><?php echo $this->form->getLabel('start_date'); ?></div>
								<div class="controls"><?php echo $this->form->getInput('start_date'); ?></div>
							</div>

							<div class="control-group">
								<div class="control-label"><?php echo $this->form->getLabel('end_date'); ?></div>
								<div class="controls"><?php echo $this->form->getInput('end_date'); ?></div>
							</div>
						</div>
							<div class="span6">
								<div class="control-group">
									<div class="control-label"><?php echo $this->form->getLabel('no_of_attempts'); ?></div>
									<div class="controls"><?php echo $this->form->getInput('no_of_attempts'); ?>
									<div class="text-info"><?php echo JText::_("COM_TMT_NOTE");?></div>
									<input type="hidden" name="max_attempt" id="max_attempt" value="<?php echo $maxattempt;?>">
									<input type="hidden" name="no_attempts" id="no_attempts" value="<?php echo $this->form->getValue('no_of_attempts'); ?>">
									</div>
								</div>

								<div class="control-group">
									<div class="control-label"><?php echo $this->form->getLabel('attempts_grade'); ?></div>
									<div class="controls"><?php echo $this->form->getInput('attempts_grade'); ?></div>
								</div>

								<div class="control-group">
									<div class="control-label"><?php echo $this->form->getLabel('resume'); ?></div>
									<div class="controls"><?php echo $this->form->getInput('resume'); ?></div>
								</div>
								<?php
								$css = 'display:none;';

								if ($terms_n_condition)
								{
									$css = 'display:block;';
								}
								?>
								<div class="control-group" style="<?php echo $css; ?>">
									<div class="control-label"><?php echo $this->form->getLabel('termscondi'); ?></div>
									<div class="controls"><?php echo $this->form->getInput('termscondi'); ?></div>
								</div>

								<div class="control-group">
									<div class="control-label"><?php echo $this->form->getLabel('answer_sheet'); ?></div>
									<div class="controls"><?php echo $this->form->getInput('answer_sheet'); ?></div>
								</div>

								<div class="control-group">
									<div class="control-label"><?php echo $this->form->getLabel('eligibility_criteria'); ?></div>
									<div class="controls"><?php echo $this->form->getInput('eligibility_criteria'); ?></div>
								</div>

								<div class="control-group">
									<div class="control-label"><?php echo $this->form->getLabel('consider_marks'); ?></div>
									<div class="controls"><?php echo $this->form->getInput('consider_marks'); ?></div>
								</div>

								<div class="control-group">
									<div class="control-label"><?php echo $this->form->getLabel('questions_shuffle'); ?></div>
									<div class="controls"><?php echo $this->form->getInput('questions_shuffle'); ?></div>
								</div>

								<div class="control-group">
									<div class="control-label"><?php echo $this->form->getLabel('answers_shuffle'); ?></div>
									<div class="controls"><?php echo $this->form->getInput('answers_shuffle'); ?></div>
								</div>

								<div class="control-group">
									<div class="control-label"><?php echo $this->form->getLabel('ideal_time'); ?></div>
									<div class="controls"><?php echo $this->form->getInput('ideal_time'); ?></div>
								</div>

							</div>
						</div><!--row-fluid-->

						<?php echo JHtml::_('bootstrap.endTab'); ?>

						<?php echo JHtml::_('bootstrap.addTab', 'myTab', 'time', JText::_('COM_TMT_TEST_TIME', true)); ?>

							<div class="row-fluid">
								<div id="show_time_duration" class="control-group">
									<div class="control-label"><?php echo $this->form->getLabel('time_duration'); ?></div>
									<div class="controls">
										<?php echo $this->form->getInput('time_duration'); ?>
										<span class="help-block">

											<?php

											echo JText::sprintf('COM_TMT_TEST_TIME_DURATION_HELP', JText::_('COM_TMT_FORM_LBL_TEST_SHOW_TIME_FINISHED')) ?>
										</span>
									</div>
								</div>

								<div class="control-group">
									<div class="control-label"><?php echo $this->form->getLabel('show_time'); ?></div>
									<div class="controls" ><?php echo $this->form->getInput('show_time'); ?></div>
								</div>

								<div class="control-group">
									<div class="control-label"><?php echo $this->form->getLabel('show_time_finished'); ?></div>
									<div onchange="alerttimetoggle()" class="controls"><?php echo $this->form->getInput('show_time_finished'); ?></div>
								</div>

								<div id="show_duration" class="control-group">
									<div class="control-label"><?php echo $this->form->getLabel('time_finished_duration'); ?></div>
									<div class="controls">
										<?php echo $this->form->getInput('time_finished_duration'); ?>

										<div id='time_finished_duration_minute' class='text text-info'></div>
									</div>
								</div>

							</div><!--row-fluid-->

						<?php echo JHtml::_('bootstrap.endTab'); ?>

						<?php echo JHtml::_('bootstrap.addTab', 'myTab', 'scoreAndResult', JText::_('COM_TMT_TEST_SCORE_RESULT', true)); ?>

							<div class="row-fluid">
									<div class="control-group ">
										<div class="control-label"><?php echo $this->form->getLabel('total_marks'); ?></div>
										<div class="controls"><?php echo $this->form->getInput('total_marks'); ?></div>
									</div>

									<div class="control-group">
										<div class="control-label"><?php echo $this->form->getLabel('passing_marks'); ?></div>
										<div class="controls"><?php echo $this->form->getInput('passing_marks'); ?></div>
									</div>

							</div><!--row-fluid-->

						<?php echo JHtml::_('bootstrap.endTab'); ?>

						<?php echo JHtml::_('bootstrap.addTab', 'myTab', 'questions', JText::_('COM_TMT_TEST_ADD_QUESTIONS', true)); ?>

								<div class="row-fluid" id="questions_btns">

									<?php if( $this->questions_count){ ?>
										<?php $link = JRoute::_(JUri::base()."index.php?option=com_tmt&view=questions&layout=qpopup&tmpl=component&unique=".$this->unique  ); ?>
										<a onclick="opentmtSqueezeBox('<?php echo $link?>')" class="btn btn-primary btn-small">
											<?php echo JText::_( 'COM_TMT_FORM_TEST_ADD_QUESTIONS' ); ?>
										</a>

<!--
										<a class="btn btn-primary btn-small" href="#" onclick="loadautoQuestion('<?php echo $this->unique ?>')" >
											<?php echo JText::_( 'COM_TMT_FORM_TEST_AUTO_GENERATE_QP' ); ?>
										</a>
-->
										<?php $link = JRoute::_(JUri::base()."index.php?option=com_tmt&view=test&layout=rules&tmpl=component&unique=".$this->unique  ); ?>
										<a class="btn btn-primary btn-small" href="#" onclick="opentmtSqueezeBox('<?php echo $link ?>')" >
											<?php echo JText::_( 'COM_TMT_FORM_TEST_AUTO_GENERATE_QP' ); ?>
										</a>
								<?php } ?>

								<?php $link = JRoute::_(JUri::base()."index.php?option=com_tmt&view=question&layout=edit&unique=".$this->unique."&tmpl=component".( isset($this->addquiz)? "&addquiz=1" : "") ); ?>
										<a onclick="opentmtSqueezeBox('<?php echo $link?>')" class="btn btn-primary btn-small">
											<?php echo JText::_( 'COM_TMT_FORM_TEST_ADD_QUESTION' ); ?>
										</a>
								</div><!--row-fluid-->

								<div class="row-fluid">
									<div class="control-group" id="quiz_type_div">
										<div class="control-label"><?php echo $this->form->getLabel('quiz_type'); ?></div>
										<div class="controls"><?php echo $this->form->getInput('quiz_type'); ?></div>
									</div>
								</div>


								<?php

									// Load all default answer-templates
									$path = JPATH_ADMINISTRATOR. '/components/com_tmt/views/test/tmpl/dynamicrules.php';
									ob_start();
									include($path);
									$html = ob_get_contents();
									ob_end_clean();
									echo $html;
								?>
									<div id="multiplication_factor">

									</div>

									<div id="question_paper" style="<?php echo (isset($this->item->questions)) ?  '' :  'display:none' ; ?> ">
									<div class="row-fluid">
											<hr/>
									</div><!--row-fluid-->

									<div class="row-fluid">

										<div id="questions_block">

											<div class="row-fluid">
												<div class="span4">
													<span>
														<b>
															<?php echo JText::_('COM_TMT_FORM_TEST_TOTAL_MARKS_FOR_QUIZ'); ?>
														</b>

														<b id="final_total_marks">
															<?php if(isset($this->item->total_marks)) {echo $this->item->total_marks;}?>
														</b>

													</span>
												</div> <!--span4-->

												<div class="span8 single-set-marks">
														<span>
															<?php echo JText::_('COM_TMT_TEST_FORM_TOTAL_SET_MARKS_AVAILABEL');?>
															<b  id="single-set-marks"><?php echo $this->item->id ? $this->form->getValue('total_marks') : 0;?></b>
														</span>
												</div> <!--span4-->

											</div> <!--row-fluid-->

											<div class="row-fluid">
												<div class="span12">

													<table id="questions_container" class="table table-condensed table-striped table-bordered table-hover" width="90%">

														<thead>
															<tr>
																<th><?php echo JText::_('COM_TMT_TEST_FORM_ORDER'); ?></th>
																<th><?php echo JText::_('COM_TMT_TEST_FORM_QUESTION'); ?></th>
																<th><?php echo JText::_('COM_TMT_TEST_FORM_CATEGORY'); ?></th>
																<th><?php echo JText::_('COM_TMT_TEST_FORM_TYPE'); ?></th>
																<th><?php echo JText::_('COM_TMT_TEST_FORM_MARKS'); ?></th>
																<th><?php echo JText::_('COM_TMT_TEST_FORM_REMOVE'); ?></th>
															</tr>
														</thead>

														<tbody>
															<?php
															// Load previous answers as per answer-template
															$i=1;
															if(isset($this->item->questions))
															{
																foreach($this->item->questions as $q)
																{
																	?>
																	<tr class="rule-template-edit1">

																		<td class="center">
																			<input type="checkbox" id="cb<?php echo $q->question_id; ?>" name="cid[]" value="<?php echo $q->question_id; ?>" onclick="Joomla.isChecked(this.checked);" style="display: none;" checked>
																			<span class="btn btn-small sortable-handler" id="reorder" title="<?php echo JText::_('COM_TMT_TEST_FORM_REORDER'); ?>" style="cursor: move;">
																				<i class="icon-move"> </i>
																			</span>
																		</td>

																		<td> <?php echo htmlentities($q->title);?> </td>

																		<td class="small"> <?php echo $q->category; ?> </td>

																		<td class="small"> <?php echo $q->type; ?> </td>

																		<td class="small center" name="td_marks"> <?php echo $q->marks; ?> </td>

																		<td>
																			<?php
																			if ($rules_checked || $maxattempt)
																			{?>
																				<span class="btn btn-small" id="remove" onclick="removeRow(this);" title="<?php echo JText::_('COM_TMT_TEST_FORM_CANNOT_DELETE'); ?>">
																				<i class="icon-trash"> </i>
																			</span>
																			<?php }
																			else
																			{?>
																			<span class="btn btn-small" id="remove" onclick="removeRow(this);" title="<?php echo JText::_('COM_TMT_TEST_FORM_DELETE'); ?>">
																				<i class="icon-trash"> </i>
																			</span>
																			<?php } ?>
																		</td>

																	</tr>
																	<?php
																}
															}
															?>

															<tr id="marks_tr" class="non-sortable-tr-quiz">

																<td colspan="4" align="right"><strong class="pull-right"><?php echo JText::_('COM_TMT_TEST_FORM_TOTAL_MARKS');?></strong></td>
																<td class="center" colspan="1" align="center"> <strong > <span id="total-marks-content"></span> </strong></td>
																<td ></td>
															</tr>
														</tbody>
													</table>

												</div> <!--span12-->
											</div>  <!--row-fluid-->

										</div> <!--questions_block-->

									</div>  <!--row-fluid-->
								</div>
						<?php echo JHtml::_('bootstrap.endTab'); ?>
						<?php echo JHtml::_('bootstrap.endTabSet'); ?>

						<?php if($this->addquiz != 0 ): ?>
							<!-- show action buttons/toolbar -->
							<div class="row-fluid">
								<div class="span12">
									<div class="btn-toolbar form-actions clearfix">
										<div class="btn-group">
											<button style="display:none" type="button" id="button_quiz_prev_tab" class="btn btn-primary com_tmt_button" onclick="quizNexttab(this,'test')">
												<i class="fa fa-arrow-circle-o-left"></i>  <?php echo JText::_('COM_TMT_BUTTON_PREV') ?>
											</button>
											<button type="button" id="button_quiz_next_tab" class="btn btn-primary com_tmt_button" onclick="quizNexttab(this,'test')">
												<?php echo JText::_('COM_TMT_BUTTON_NEXT') ?> <i class="fa fa-arrow-circle-o-right"></i>
											</button>
											<button style="display:none" type="button" id="button_save_and_close" class="btn btn-primary com_tmt_button" onclick="quizactions(this,'test.save','<?php echo $this->mod_id;?>')">
												<!--<span class="icon-ok"></span>&#160; --><?php echo JText::_('COM_TMT_BUTTON_SAVE_AND_CLOSE') ?>
											</button>
										</div>
										<div class="btn-group">
											<?php if($this->addquiz == 1 ){ ?>
												<?php if (empty($this->item->id)){ ?>
													<button type="button" id="button_cancel" class="btn com_tmt_button" onclick="parent.hide_add_quizs_wizard('<?php echo $this->mod_id;?>')">
												<?php }else{ ?>
													<button type="button" id="button_cancel" class="btn com_tmt_button" onclick="parent.showHideEditLesson('<?php echo $this->mod_id;?>','<?php echo $this->unique ?>',0)">
												<?php } ?>
												<!--<span class="icon-cancel"></span>&#160;--><?php echo JText::_('COM_TMT_BUTTON_CANCEL') ?>
											</button>
											<?php }else{ ?>
											<button type="button" id="button_cancel" class="btn com_tmt_button" onclick="Joomla.submitbutton('test.cancel')">
												<!--<span class="icon-cancel"></span>&#160;--><?php echo JText::_('COM_TMT_BUTTON_CANCEL') ?>
											</button>
											<?php } ?>
										</div>
									</div>
								</div><!--span12-->
							</div><!--row-fluid-->
						<?php endif; ?>
					<?php if(empty($this->item->created_by)){ ?>
						<input type="hidden" name="jform[created_by]" value="<?php echo JFactory::getUser()->id; ?>" />
						<input type="hidden" name="jform[reviewers][]" value="<?php echo JFactory::getUser()->id; ?>" />
					<?php }
					else{ ?>
						<input type="hidden" name="jform[created_by]" value="<?php echo $this->item->created_by; ?>" />
						<input type="hidden" name="jform[reviewers][]" value="<?php echo JFactory::getUser()->id; ?>" />
					<?php } ?>

					<?php if($this->course_id) {  ?>
					<input type="hidden" name="course_id" value="<?php echo $this->course_id; ?>" />
					<?php } ?>

					<?php if($this->mod_id) {  ?>
					<input type="hidden" name="mod_id" value="<?php echo $this->mod_id; ?>" />
					<?php } ?>

					<?php if($this->unique) {  ?>
					<input type="hidden" name="unique" value="<?php echo $this->unique; ?>" />
					<?php } ?>

					<input type="hidden" name="jform[id]" value="<?php echo $this->item->id; ?>" />
					<input type="hidden" name="option" value="com_tmt" />
					<input type="hidden" name="controller" value="" />

					<input type="hidden" name="task" value="test.save" />

					<input type="hidden" name="id" value="<?php if (!empty($this->item->id)) echo $this->item->id; ?>" class="test_id" />
					<input type="hidden" name="rules_checked"  id="rules_checked" value="<?php  echo $rules_checked; ?>"/>

					<input type="hidden" name="invalid_rule_count"  id="invalid_rule_count" value="0" />

					<?php echo JHTML::_('form.token'); ?>
				</form>
		</fieldset>
	</div><!--span12-->
</div><!--row-fluid-->
