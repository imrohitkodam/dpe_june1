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

use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;

HTMLHelper::_('behavior.keepalive');
HTMLHelper::_('behavior.tooltip');
HTMLHelper::_('behavior.formvalidation');
HTMLHelper::_('behavior.modal', 'a.modal');
HTMLHelper::_('jquery.ui', array('core', 'sortable'));

$allow_paid_courses = $this->tjlmsparams->get('allow_paid_courses','0','INT');
$terms_n_condition = $this->tjlmsparams->get('quiz_articleId','0','INT');
$maxattempt = $count = $livetrackReviews = 0;
$typeClass = '';

if (!isset($this->item->id))
{
	$this->item->id = 0;
}

if (!empty($this->item->max_attempt))
{
	$maxattempt = $this->item->max_attempt;
}

if (!empty($this->item->livetrackReviews))
{
	$livetrackReviews = $this->item->livetrackReviews;
}

if (!empty($this->item->id) &&  !empty($this->item->type))
{
	$typeClass = 'd-none';
}
?>

<script type="text/javascript">

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

</script>
<?php
$options['relative'] = true;
HTMLHelper::_('script', 'com_tjlms/tjService.js', $options);
HTMLHelper::_('script', 'com_tmt/tmt.js', $options);
HTMLHelper::_('script', 'com_tjlms/tjlmsAdmin.js', $options);
?>
<!--techjoomla-strapper-->
<div class="tjlms-wrapper tjBs3">
	<?php
		ob_start();
		include JPATH_BASE . '/components/com_tjlms/layouts/header.sidebar.php';
		$layoutOutput = ob_get_contents();
		ob_end_clean();
		echo $layoutOutput;
	?>

	<!--form-->
	<div id="form">
		<!--fieldset-->
		<fieldset>
			<?php echo HTMLHelper::_('bootstrap.startTabSet', 'testform', array('active' => 'details')); ?>

				<?php echo HTMLHelper::_('bootstrap.addTab', 'testform', 'details', Text::_('COM_TMT_TEST_DETAILS', true)); ?>
					<!--testFormdetails-->
					<form action="<?php echo Route::_('index.php?option=com_tmt&view=test&tmpl=component');?>" class="form-validate form-horizontal" method="post" enctype="multipart/form-data" name="adminForm" id="testFormdetails">
						<input type="hidden" class="extra_validations" data-js-validation-functions="tmt.test.validateBasic">

						<!--container-fluid-->
						<div class="container-fluid">
							<!--row-fluid-->
							<div class="row-fluid">
								<!--span6-->
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
										<label class="control-label"><?php echo $this->form->getLabel('catid'); ?></label>
										<div class="controls"><?php echo $this->form->getInput('catid'); ?></div>
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

									<?php echo $this->form->renderField('image'); ?>
									<span class="help-block alert alert-info"><?php echo Text::_('COM_TJLMS_SUPPORTED_MEDIA_FILES_COURSE'); ?></span>

									<!-- If edit show IMage of Test-->
									<?php if (!empty($this->item->image)) : ?>
										<img src="<?php echo $this->item->image;?>" />
									<?php endif; ?>
								<!--div class="control-group">
								<div class="control-label"><?php echo $this->form->getLabel('qztype'); ?></div>
								<div class="controls"><?php echo $this->form->getInput('qztype'); ?></div>
								</div-->
							</div><!--/span6-->
							<!--span6-->
							<div class="span6">
								<div class="control-group">
									<div class="control-label"><?php echo $this->form->getLabel('no_of_attempts'); ?></div>
									<div class="controls"><?php echo $this->form->getInput('no_of_attempts'); ?>
									<div class="text-info alert alert-info"><?php echo Text::_("COM_TJLMS_FORM_DESC_LESSON_NO_OF_ATTEMPTS_NOTE");?></div>
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

								<?php echo $this->form->renderField('show_correct_answer'); ?>
								<?php echo $this->form->renderField('print_answersheet'); ?>

								<div class="control-group">
									<div class="control-label"><?php echo $this->form->getLabel('eligibility_criteria'); ?></div>
									<div class="controls"><?php echo $this->form->getInput('eligibility_criteria');  ?></div>
								</div>

								<?php if ($this->course->certificate_term == 2 && $this->isPassable) { ?>
										<div class="control-group">
											<div class="control-label"><?php echo $this->form->getLabel('consider_marks'); ?></div>
											<div class="controls"><?php echo $this->form->getInput('consider_marks'); ?></div>
										</div>
								<?php }
									elseif ($this->course->certificate_term == 1 || empty($this->course->certificate_term))
									{ ?>
										<div class="control-group">
											<div class="control-label"><?php echo $this->form->getLabel('consider_marks'); ?></div>
											<div class="controls"><?php echo $this->form->getInput('consider_marks'); ?></div>
										</div>
								<?php } ?>

								<div class="control-group">
									<div class="control-label"><?php echo $this->form->getLabel('questions_shuffle'); ?></div>
									<div class="controls"><?php echo $this->form->getInput('questions_shuffle'); ?></div>
								</div>

								<div class="control-group">
									<div class="control-label"><?php echo $this->form->getLabel('answers_shuffle'); ?></div>
									<div class="controls"><?php echo $this->form->getInput('answers_shuffle'); ?></div>
								</div>

								<?php echo $this->form->renderField('show_all_questions'); ?>

								<?php echo $this->form->renderField('pagination_limit'); ?>

								<?php echo $this->form->renderField('show_questions_overview'); ?>

								<div class="control-group">
									<div class="control-label"><?php echo $this->form->getLabel('ideal_time'); ?></div>
									<div class="controls"><?php echo $this->form->getInput('ideal_time'); ?></div>
								</div>

								<?php echo $this->form->renderField('show_thankyou_page'); ?>

								<?php if ($this->gradingtype == 'quiz') : ?>
									<?php echo $this->form->renderField('show_quiz_marks'); ?>
								<?php endif; ?>

								<?php //echo $this->form->renderField('in_lib'); ?>

								</div><!--/span6-->
							</div><!--/row-fluid-->
						</div><!--/container-fluid-->
						<input type="hidden" name="option" value="com_tmt"/>
						<input type="hidden" name="task" value="test.saveBasic"/>
						<input type="hidden" name="jform[id]" data-js-id="id" value="<?php echo $this->item->id;?>"/>
						<input type="hidden" name="jform[gradingtype]" value="<?php echo $this->form->getValue("gradingtype");?>"/>
						<input type="hidden" name="jform[lesson_id]" data-js-id="lesson_id"  value="<?php echo $this->lid;?>" />
						<input type="hidden" name="jform[course_id]" value="<?php echo $this->cid;?>"/>
						<input type="hidden" name="jform[mod_id]" value="<?php echo $this->mid;?>"/>
						<?php echo HTMLHelper::_('form.token'); ?>
					</form><!--testFormdetails-->

				<?php echo HTMLHelper::_('bootstrap.endTab'); ?>

				<?php echo HTMLHelper::_('bootstrap.addTab', 'testform', 'time', Text::_('COM_TMT_TEST_TIME', true)); ?>
					<!--testFormtime-->
					<form action="<?php echo Route::_('index.php?option=com_tmt&view=test&tmpl=component');?>" class="form-validate form-horizontal" method="post" name="adminForm" id="testFormtime">
						<input type="hidden" class="extra_validations" data-js-validation-functions="tmt.test.validateTime">
						<!--container-fluid-->
						<div class="container-fluid">
							<!--row-fluid-->
							<div class="row-fluid">
								<!--show_time_duration-->
								<div id="show_time_duration" class="control-group">
									<div class="control-label"><?php echo $this->form->getLabel('time_duration'); ?></div>
									<div class="controls">
										<?php echo $this->form->getInput('time_duration'); ?>
										<span class="help-block alert alert-info">
											<?php echo Text::sprintf('COM_TMT_TEST_TIME_DURATION_HELP', Text::_('COM_TMT_FORM_LBL_TEST_SHOW_TIME_FINISHED')) ?>
										</span>
									</div>
								</div><!--/show_time_duration-->
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
						</div><!--container-fluid-->
						<input type="hidden" name="task" value="test.saveTimeDuration"/>
						<input type="hidden" name="jform[gradingtype]" value="<?php echo $this->form->getValue("gradingtype");?>"/>
						<input type="hidden" name="jform[id]" data-js-id="id" value="<?php echo $this->item->id;?>"/>
						<input type="hidden" name="jform[lesson_id]" data-js-id="lesson_id"  value="<?php echo $this->lid;?>" />
						<input type="hidden" name="jform[course_id]" value="<?php echo $this->cid;?>"/>
						<input type="hidden" name="jform[mod_id]" value="<?php echo $this->mid;?>"/>
						<?php echo HTMLHelper::_('form.token'); ?>
					</form><!--/testFormtime-->
			<?php echo HTMLHelper::_('bootstrap.endTab'); ?>

			<?php echo HTMLHelper::_('bootstrap.addTab', 'testform', 'questions', Text::_('COM_TMT_TEST_ADD_QUESTIONS', true)); ?>

				<?php if ($maxattempt > 0) : ?>
					<div class="alert alert-info">
						<?php echo Text::sprintf('COM_TMT_TEST_DISABLED_AS_ATTEMPTED');?>
					</div>
				<?php endif;?>

				<!--testFormquestions-->
				<form action="<?php echo Route::_('index.php?option=com_tmt&view=test&tmpl=component');?>" class="form-validate form-horizontal" method="post" name="adminForm" id="testFormquestions">
					<input type="hidden" class="extra_validations" data-js-validation-functions="tmt.test.validateQuestionsMarks">

					<?php if ($this->gradingtype == 'quiz') : ?>
						<!--container-fluid-->
						<div class="container-fluid">
							<div class="row-fluid">
								<div class="control-group span6">
									<div class="control-label"><?php echo $this->form->getLabel('total_marks'); ?></div>
									<div class="controls"><?php echo $this->form->getInput('total_marks'); ?></div>
								</div>

								<div class="control-group span6">
									<div class="control-label"><?php echo $this->form->getLabel('passing_marks'); ?></div>
									<div class="controls"><?php echo $this->form->getInput('passing_marks'); ?></div>
								</div>
							</div>
						</div><!--container-fluid-->
					<?php endif; ?>

						<!--container-fluid-->
						<div class="container-fluid <?php echo $typeClass;?>">
							<div class="row-fluid">
								<div class="control-group" id="quiz_type_div">
									<div class="control-label"><?php echo $this->form->getLabel('type'); ?></div>
									<div class="controls"><?php echo $this->form->getInput('type'); ?></div>
								</div>
							</div>
						</div><!--container-fluid-->

						<input type="hidden" name="jform[gradingtype]" data-js-id="gradingtype" value="<?php echo $this->gradingtype;?>"/>
						<input type="hidden" name="jform[course_id]" value="<?php echo $this->cid;?>"/>
						<input type="hidden" name="jform[id]" data-js-id="id" value="<?php echo $this->item->id;?>"/>
						<input type="hidden" name="jform[mod_id]" value="<?php echo $this->mid;?>"/>
						<input type="hidden" name="task" value="test.saveMarks"/>
						<input type="hidden" name="jform[lesson_id]" data-js-id="lesson_id"  value="<?php echo $this->lid;?>"/>
						<?php echo HTMLHelper::_('form.token'); ?>
					</form><!--testFormquestions-->

					<!--container-fluid-->
					<div class="container-fluid">
					<?php if($this->gradingtype == 'quiz' && $this->item->type == "set"){ ?>
						<div class="row-fluid">
							<div data-js-attr="set-options">
								<div class="bg-info p-15">
									<?php echo Text::_("COM_TMT_TEST_SET_MSG_REFRESH_RULES"); ?>
									<button type="button" class="btn btn-info" data-js-id="test-set-refresh"><?php echo Text::_("COM_TMT_TEST_SET_REFRESH_RULES"); ?></button>
								</div>
							</div>
						</div>
					<?php } ?>
						<div>&nbsp;</div>
						<div class="row-fluid">
							<div class="alert alert-info" data-js-attr="set-options">
								<?php echo Text::sprintf('COM_TMT_TEST_DYNAMIC_QUIZ_RULE_VALIDATION_MSG','<i class="fa fa-thumbs-up"></i>','<i class="fa fa-thumbs-o-up insufficient_for_set"></i>','<i class="fa fa-close insufficient_for_quiz"></i>');?>
							</div>
						</div>
						<div class="row-fluid">
							<div class="test-sections" data-js-id="test-sections">
					<?php
						require_once JPATH_ROOT . '/libraries/techjoomla/common.php';
						$tjcommon	=	new TechjoomlaCommon();

						foreach ($this->item->sections as $section)
						{
							$layout = $tjcommon->getViewpath('com_tmt','test','section','ADMIN','ADMIN');
							ob_start();
							include($layout);
							$temp = ob_get_contents();
							ob_end_clean();
							echo $temp;
						}

						$section =  new stdClass;
						$section->id = '';
						$section->title = '';
						$section->questions = array();
						$section->qcnt =  $section->marks = $section->state = 0;

						$layout = $tjcommon->getViewpath('com_tmt','test','section','ADMIN','ADMIN');
						ob_start();
						include($layout);
						$section_html = ob_get_contents();
						ob_end_clean();
						echo $section_html;
					?>
							</div>
						</div>

						<div class="row-fluid" data-js-id="section-create-action">
							<div class="span12">
							<a onclick="tmt.section.toggleSave()" class="btn btn-primary btn-block btn-large br-0">
									<i class="fa fa-plus-circle"></i>
									<?php echo Text::_( 'COM_TMT_SECTION_CREATE'); ?>
								</a>
							</div>
						</div>

						<div class="row-fluid hide" data-js-id="section-create-form">
							<?php
								require_once JPATH_ROOT . '/libraries/techjoomla/common.php';
								$tjcommon = new TechjoomlaCommon();
								$layout = $tjcommon->getViewpath('com_tmt','test','sectioncreate','ADMIN','ADMIN');
								ob_start();
								include($layout);
								$section_html = ob_get_contents();
								ob_end_clean();
								echo $section_html;
							?>
						</div>
					</div><!--container-fluid-->
			<?php echo HTMLHelper::_('bootstrap.endTab'); ?>

		<?php if ($this->assessment): ?>

			<?php echo HTMLHelper::_('bootstrap.addTab', 'testform', 'assessment', Text::_('COM_TJLMS_LESSON_ASSESSMENT', true)); ?>

				<?php if ($maxattempt > 0) : ?>
					<div class="alert alert-info">
						<?php echo Text::sprintf('COM_TMT_TEST_DISABLED_AS_ATTEMPTED');?>
					</div>
				<?php endif;?>

				<form action="" method="post" enctype="multipart/form-data" name="adminForm" id="testFormAssessment" class="form-validate form-horizontal" >
				<input type="hidden" class="extra_validations" data-js-validation-functions="tjlmsAdmin.assessmentform.validate">
					<!--container-fluid-->
				<div class="container-fluid">
					<div class="row-fluid">
						<div class="span6">
							<div class="control-group hide">
								<div class="control-label"><?php echo $this->form->getLabel('add_assessment'); ?></div>
								<div class="controls"><?php echo $this->form->getInput('add_assessment'); ?></div>
							</div>
						</div>
					</div>

					<div class="row-fluid">
							<div class="control-group">
								<div class="control-label"><?php echo $this->form->getLabel('assessment_params'); ?></div>
								<div class="controls"><?php echo $this->form->getInput('assessment_params'); ?></div>
							</div>
					</div>

			<?php if ($this->gradingtype != 'quiz') : ?>

					<div class="row-fluid">
						<div class="span6">
							<div class="control-group">
								<div class="control-label"><?php echo $this->form->getLabel('total_marks'); ?></div>
								<div class="controls"><?php echo $this->form->getInput('total_marks'); ?></div>
							</div>
						</div>
						<div class="span6">
							<div class="control-group" data-js-id="disable-if-reviewed">
								<div class="control-label"><?php echo $this->form->getLabel('passing_marks'); ?></div>
								<div class="controls"><?php echo $this->form->getInput('passing_marks'); ?></div>
							</div>
						</div>
					</div>
			<?php endif; ?>
					<div class="row-fluid">
						<div class="span6">
							<div class="control-group row-fluid">
								<div class="control-label"><?php echo $this->form->getLabel('assessment_attempts'); ?></div>
								<div class="controls"><?php echo $this->form->getInput('assessment_attempts'); ?></div>
								<div class="text-info controls"><?php echo Text::_('COM_TJLMS_FORM_DESC_MINIMUM_NO_OF_ASSESSMENT_NOTE'); ?></div>
								<input type="hidden" name="max_assessment" id="max_assessment" value="<?php echo $livetrackReviews;?>">
								<input type="hidden" name="no_assessment" id="no_assessment" value="<?php echo $this->form->getValue('assessment_attempts'); ?>">
							</div>
						</div>
						<div class="span6">
							<div class="control-group row-fluid" data-js-id="disable-if-reviewed">
								<div class="control-label"><?php echo $this->form->getLabel('assessment_attempts_grade'); ?></div>
								<div class="controls"><?php echo $this->form->getInput('assessment_attempts_grade'); ?></div>
							</div>
						</div>
						<div class="span6">
							<div class="control-group">
								<div class="control-label"><?php echo $this->form->getLabel('assessment_student_name'); ?></div>
								<div class="controls"><?php echo $this->form->getInput('assessment_student_name'); ?></div>
							</div>
						</div>
					</div>

					<div class="row-fluid">

						<div class="span6">
							<div class="control-group">
								<div class="control-label"><?php echo $this->form->getLabel('assessment_answersheet'); ?></div>
								<div class="controls"><?php echo $this->form->getInput('assessment_answersheet'); ?></div>
							</div>
						</div>
						<div class="span6" data-js-id="answersheet_options">
							<div class="control-group">
								<div><?php echo $this->form->getLabel('answersheet_options'); ?></div>
								<div><?php echo $this->form->getInput('answersheet_options'); ?></div>
							</div>
						</div>
							<!-- <div class="control-group">
								<div class="control-label"><?php echo $this->form->getLabel('allow_attachments'); ?></div>
								<div class="controls"><?php echo $this->form->getInput('allow_attachments'); ?></div>
							</div> -->
							<!--div class="control-group row-fluid">
								<div class="control-label"><?php echo $this->form->getLabel('assessment_title'); ?></div>
								<div class="controls"><?php echo $this->form->getInput('assessment_title'); ?></div>
							</div-->
					</div>
				</div><!--container-fluid-->

					<input type="hidden" name="jform[allow_attachments]" value="0"/>
					<input type="hidden" name="jform[gradingtype]" data-js-id="gradingtype" value="<?php echo $this->form->getValue("gradingtype");?>"/>
					<input type="hidden" name="jform[set_id]" value="<?php echo (!empty($this->item->set_id)) ? $this->item->set_id : 0;?>"/>
					<input type="hidden" name="jform[course_id]" value="<?php echo $this->cid;?>"/>
					<input type="hidden" name="jform[id]" data-js-id="id" value="<?php echo $this->item->id;?>"/>
					<input type="hidden" name="jform[mod_id]" value="<?php echo $this->mid;?>"/>
					<input type="hidden" name="task" value="test.saveAssessment"/>
					<input type="hidden" name="jform[lesson_id]" data-js-id="lesson_id"  value="<?php echo $this->lid;?>"/>
				</form>

			<?php echo HTMLHelper::_('bootstrap.endTab'); ?>

		<?php endif;?>
		<?php echo HTMLHelper::_('bootstrap.endTabSet'); ?>

			<?php if ($this->ifintmpl == 'component'): ?>
				<!-- show action buttons/toolbar -->
				<!--container-fluid-->
				<div class="container-fluid">
					<div class="row-fluid">
						<div class="form-actions pt-5 pb-5">
							<div class="btn-toolbar clearfix text-right" data-js-attr="form-actions">
								<div id="toolbar-prev" class="btn-wrapper">
									<button type="button" data-js-attr="form-actions-prev" class="btn  com_tmt_button hide">
										<span class="valign-middle"><i class="icon-arrow-left"></i></span>
										<?php echo Text::_('COM_TMT_BUTTON_PREV') ?>
									</button>
								</div>

								<div id="toolbar-next" class="btn-wrapper">
									<button type="button" data-js-attr="form-actions-next" class="btn btn-success ml-10 com_tmt_button">
											<?php echo Text::_('COM_TMT_BUTTON_NEXT') ?>
											<span class="valign-middle"><i class="icon-arrow-right"></i></span>
									</button>
								</div>

								<div id="toolbar-apply" class="btn-wrapper hide">
									<button type="button" id="button_save" class="btn btn-success com_tmt_button hide" onclick="Joomla.submitbutton('quiz.apply');">
										<span class="fa fa-check mr-0"></span>
										<?php echo Text::_('COM_TMT_BUTTON_SAVE') ?>
									</button>
								</div>

								<div id="toolbar-save" class="btn-wrapper hide">
									<button type="button" id="button_save_and_close" class="btn btn-success mr-10 com_tmt_button hide" onclick="Joomla.submitbutton('quiz.save')">
										<?php //echo (!$this->parentDiv) ? Text::_('COM_TMT_BUTTON_SAVE_AND_CLOSE') : Text::_('COM_TMT_BUTTON_SAVE_AND_ADD_TOQUIZ');?>
										<span class="fa fa-check mr-0"></span>
										<?php echo Text::_('COM_TMT_BUTTON_SAVE_AND_CLOSE');?>
									</button>
								</div>

								<div id="toolbar-cancel" class="btn-wrapper">
									<button type="button" class="btn com_tmt_button" onclick="Joomla.submitbutton('quiz.cancel')">
										<span class="icon-delete valign-middle"></span>
										<?php echo Text::_('COM_TMT_BUTTON_CANCEL') ?>
									</button>
								</div>
							</div><!--btn-toolbar-->
						</div>
					</div><!--row-fluid-->
				</div><!--container-fluid-->
			<?php endif; ?>
		</fieldset>
	</div><!--#form-->
</div><!--techjoomla-strapper-->
<script>
tmt.stepform.init("<?php echo $this->ifintmpl;?>", 1);
tmt.test.init("<?php echo $this->gradingtype;?>", "<?php echo $this->ifintmpl;?>", "<?php echo $this->cid;?>", "<?php echo $this->mid;?>", <?php echo $this->assessment;?>, "<?php echo $maxattempt;?>", "<?php echo $livetrackReviews; ?>");
</script>
<style>
	.d-inline-block {
		display: inline-block;
	}

	.d-none {
		display:none;
	}
	.test_section__header [data-toggle="collapse"] [data-jstoggle="collapse"]::before {
	  content: "\f107";
	  font-size: 20px;
	}

	.test_section__header [data-toggle="collapse"].collapsed [data-jstoggle="collapse"]::before {
	  content: "\f105";
	  font-size: 20px;
	}

	.test_section__header [data-toggle="collapse"].collapsed .test-section__header-edit-action {
		display:none;
	}

	.test_section__header [data-toggle="collapse"] .test-section__header-edit-action {
		display:inline-block;
	}

	.test-section__header-edit-action {
		padding: 0 5px;
	}
	.questions_container table {
		width: 100%;
		border-top: 1px solid #ccc;
	}
</style>
