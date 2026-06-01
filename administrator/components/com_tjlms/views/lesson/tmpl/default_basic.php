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
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;

HTMLHelper::_('bootstrap.renderModal', 'a.modal');
HTMLHelper::_('behavior.formvalidator');
$maxattempt = 0;

if ($this->lessonId > 0)
{
	$maxattempt = $this->item->max_attempt;

	if (empty ($maxattempt))
	{
		$maxattempt = 0;
	}
}
?>
<form action="<?php echo Route::_('index.php?option=com_tjlms&view=lesson&id='. $this->lessonId); ?>" method="post" enctype="multipart/form-data" name="adminForm" id="lesson-basic-form_<?php echo $this->formId;?>" class="form-validate form-horizontal lesson_basic_form" >
	<div class="container-fluid">
		<div class="row">
			<div class="col-md-6">
				<fieldset class="adminform">

					<input type="hidden" class="extra_validations" data-js-validation-functions="tjlmsAdmin.validateDates,tjlmsAdmin.basicForm.validate">

					<div class="control-group" style="display:none;">
						<div class="control-label"><?php echo $this->form->getLabel('id'); ?></div>
						<div class="controls"><?php echo $this->form->getInput('id'); ?></div>
					</div>

					<div class="control-group row-fluid">
						<div class="control-label"><?php echo $this->form->getLabel('title'); ?></div>
						<div class="controls"><?php echo $this->form->getInput('title'); ?></div>
					</div>

					<div class="control-group">
						<div class="control-label"><?php echo $this->form->getLabel('alias'); ?></div>
						<div class="controls"><?php echo $this->form->getInput('alias'); ?></div>
					</div>

					<div class="control-group row-fluid">
						<div class="control-label"><?php echo $this->form->getLabel('state'); ?></div>
						<div class="controls"><?php echo $this->form->getInput('state'); ?></div>
					</div>

					<div class="control-group">
						<label class="control-label"><?php echo $this->form->getLabel('catid'); ?></label>
						<div class="controls"><?php echo $this->form->getInput('catid'); ?></div>
					</div>


					<div class="control-group row-fluid">
						<div class="control-label"><?php echo $this->form->getLabel('description'); ?></div>
						<div class="controls"><?php echo $this->form->getInput('description'); ?></div>
					</div>

					<div class="control-group row-fluid">
						<div class="control-label"><?php echo $this->form->getLabel('start_date'); ?></div>
						<div class="controls"><?php echo $this->form->getInput('start_date'); ?></div>
					</div>

					<div class="control-group row-fluid">
						<div class="control-label"><?php echo $this->form->getLabel('end_date'); ?></div>
						<div class="controls"><?php echo $this->form->getInput('end_date'); ?></div>
					</div>

					<div class="control-group">
						<div class="control-label"><?php echo $this->form->getLabel('image'); ?></div>
						<div class="controls">
							<?php echo $this->form->getInput('image'); ?>
							<span class="help-block alert-info"><?php echo Text::_('COM_TJLMS_SUPPORTED_MEDIA_FILES_COURSE'); ?></span>
						</div>
					</div>

					<!-- If edit show IMage of lesson-->
					<?php if (!empty($this->item->image)) : ?>
						<?php //$lesson_imgPath = $this->tjlmsLessonHelper->getLessonImage((array)$lesson, "M_");?>
						<img src="<?php echo $this->item->image;?>" />
					<?php endif; ?>

				</fieldset>
			</div>
			<div class="col-md-6">
				<fieldset class="adminform">
					<div class="control-group">

						<div class="control-label"><?php echo $this->form->getLabel('no_of_attempts'); ?></div>
						<div class="controls"><?php echo $this->form->getInput('no_of_attempts'); ?>
						<div class="text-info alert-info"><?php echo Text::_('COM_TJLMS_FORM_DESC_LESSON_NO_OF_ATTEMPTS_NOTE'); ?></div>
						<input type="hidden" name="max_attempt" id="max_attempt" value="<?php echo $maxattempt;?>">
						<input type="hidden" name="no_attempts" id="no_attempts" value="<?php echo $this->form->getValue('no_of_attempts'); ?>">
						</div>
					</div>

					<div class="control-group">
						<div class="control-label"><?php echo $this->form->getLabel('attempts_grade'); ?></div>
						<div class="controls"><?php echo $this->form->getInput('attempts_grade'); ?></div>
					</div>

				<?php if (!empty($this->course->id)) :?>

					<div class="control-group">
						<div class="control-label"><?php echo $this->form->getLabel('eligibility_criteria'); ?></div>
						<div class="controls"><?php echo $this->form->getInput('eligibility_criteria'); ?></div>
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
				<?php endif; ?>


				<?php if($this->params->get('allow_paid_courses','0','INT') == 1 && (!empty($this->course->id) && $this->course->type == 1)): ?>

					<div class="control-group">
						<div class="control-label"><?php echo $this->form->getLabel('free_lesson'); ?></div>
						<div class="controls"><?php echo $this->form->getInput('free_lesson'); ?></div>
					</div>

				<?php endif; ?>

				<div class="control-group">
						<div class="control-label"><?php echo $this->form->getLabel('in_lib'); ?></div>
						<div class="controls"><?php echo $this->form->getInput('in_lib'); ?></div>
				</div>

				<div class="control-group">
					<div class="control-label"><?php echo $this->form->getLabel('ideal_time'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('ideal_time'); ?></div>
				</div>

				<?php foreach ($this->form->getGroup('params') as $field) : ?>
					<?php echo $field->renderField(); ?>
				<?php endforeach; ?>
			</fieldset>

			</div>
		</div>
	</div>
		<input type="hidden" name="option" value="com_tjlms" />
		<input type="hidden" name="task" value="lesson.save" />
		<input type="hidden" name="jform[format]" id="course_id" value="<?php echo $this->format; ?>" />
		<input type="hidden" name="jform[course_id]" id="course_id" value="<?php echo $this->courseId; ?>" />
		<input type="hidden" name="jform[mod_id]" id="mod_id" value="<?php echo $this->moduleId; ?>" />
		<input type="hidden" name="jform[id]" data-js-id="id" value="<?php echo $this->item->id;?>"/>
		<?php echo HTMLHelper::_('form.token'); ?>
</form>

