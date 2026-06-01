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
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
HTMLHelper::_('bootstrap.renderModal', 'a.modal');
HTMLHelper::_('behavior.formvalidator');
$liveTrackReviews = 0;

if (!empty($this->item->livetrackReviews))
{
	$livetrackReviews = $this->item->livetrackReviews;
}
?>
	<?php if (!empty($this->item->max_attempt) && $this->item->max_attempt > 0) : ?>
					<div class="alert alert-info">
						<?php echo Text::sprintf('COM_TJLMS_LESSON_ASSESSMENT_DISABLED_AS_ATTEMPTED');?>
					</div>
				<?php endif;?>

				<form action="" method="post" enctype="multipart/form-data" name="adminForm" id="assessmentform" class="form-validate form-horizontal" >
				<input type="hidden" class="extra_validations" data-js-validation-functions="tjlmsAdmin.assessmentform.validate">
					<!--container-fluid-->
				<div class="container">
					<div class="row" data-js-id="disable-if-reviewed">
						<div class="col-md-6">
							<div class="control-group">
								<div class="control-label"><?php echo $this->form->getLabel('add_assessment'); ?></div>
								<div class="controls"><?php echo $this->form->getInput('add_assessment'); ?></div>
							</div>
						</div>
					</div>
				<div data-js-id="assessment-details">
					<div class="row">
							<div class="control-group">
								<div class="control-label"><?php echo $this->form->getLabel('assessment_params'); ?></div>
								<div class="controls"><?php echo $this->form->getInput('assessment_params'); ?></div>
							</div>
					</div>

					<div class="row">
						<div class="col-md-6">
							<div class="control-group">
								<div class="control-label"><?php echo $this->form->getLabel('total_marks'); ?></div>
								<div class="controls"><?php echo $this->form->getInput('total_marks'); ?></div>
							</div>
						</div>
						<div class="col-md-6" data-js-id="disable-if-reviewed">
							<div class="control-group">
								<div class="control-label"><?php echo $this->form->getLabel('passing_marks'); ?></div>
								<div class="controls"><?php echo $this->form->getInput('passing_marks'); ?></div>
							</div>
						</div>
					</div>

					<div class="row">
						<div class="col-md-6">
							<div class="control-group row">
								<div class="control-label"><?php echo $this->form->getLabel('assessment_attempts'); ?></div>
								<div class="controls"><?php echo $this->form->getInput('assessment_attempts'); ?></div>
								<div class="text-info controls"><?php echo Text::_('COM_TJLMS_FORM_DESC_MINIMUM_NO_OF_ASSESSMENT_NOTE'); ?></div>
								<input type="hidden" name="max_assessment" id="max_assessment" value="<?php echo $livetrackReviews;?>">
								<input type="hidden" name="no_assessment" id="no_assessment" value="<?php echo $this->form->getValue('assessment_attempts'); ?>">
							</div>
						</div>
						<div class="col-md-6" data-js-id="disable-if-reviewed">
							<div class="control-group row">
								<div class="control-label"><?php echo $this->form->getLabel('assessment_attempts_grade'); ?></div>
								<div class="controls"><?php echo $this->form->getInput('assessment_attempts_grade'); ?></div>
							</div>
						</div>
						<div class="col-md-6">
							<div class="control-group">
								<div class="control-label"><?php echo $this->form->getLabel('assessment_student_name'); ?></div>
								<div class="controls"><?php echo $this->form->getInput('assessment_student_name'); ?></div>
							</div>
						</div>
					</div>

					<div class="row">

						<div class="col-md-6">
							<div class="control-group">
								<div class="control-label"><?php echo $this->form->getLabel('assessment_answersheet'); ?></div>
								<div class="controls"><?php echo $this->form->getInput('assessment_answersheet'); ?></div>
							</div>
						</div>

						<div class="col-md-6" data-js-id="answersheet_options">
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

				</div><!--assessment-->
			</div><!--container-fluid-->

					<input type="hidden" name="jform[allow_attachments]" value="0"/>
					<input type="hidden" name="jform[set_id]" value="<?php echo (!empty($this->item->set_id)) ? $this->item->set_id : 0;?>"/>
					<input type="hidden" name="jform[course_id]" value="<?php echo $this->cid;?>"/>
					<input type="hidden" name="jform[id]" data-js-id="id" value="<?php echo $this->item->id;?>"/>
					<input type="hidden" name="jform[mod_id]" value="<?php echo $this->mid;?>"/>
					<input type="hidden" name="option" value="com_tjlms"/>
					<input type="hidden" name="task" value="lesson.saveassessment"/>
				</form>

