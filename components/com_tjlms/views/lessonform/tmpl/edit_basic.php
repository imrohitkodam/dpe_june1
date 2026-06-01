<?php
/**
 * @package     Shika
 * @subpackage  com_tjlms
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;

use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;

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
<form action="<?php echo Route::_('index.php?option=com_tjlms&view=lessonform&id='. $this->lessonId); ?>" method="post" enctype="multipart/form-data" name="adminForm" id="lesson-basic-form_<?php echo $this->formId;?>" class="form-validate form-horizontal lesson_basic_form" >
	<div class="clearfix mb-10"> </div>
	<div class="container-fluid">
		<div class="row">
			<div class="col-sm-6">
				<fieldset class="adminform">

					<input type="hidden" class="extra_validations" data-js-validation-functions="tjlmsAdmin.validateDates,tjlmsAdmin.basicForm.validate">

					<div class="form-group" style="display:none;">
						<label class="col-sm-3"><?php echo $this->form->getLabel('id'); ?></label>
						<div class="col-sm-9"><?php echo $this->form->getInput('id'); ?></div>
					</div>

					<div class="form-group">
						<label class="col-sm-3"><?php echo $this->form->getLabel('title'); ?></label>
						<div class="col-sm-9"><?php echo $this->form->getInput('title'); ?></div>
					</div>

					<div class="form-group">
						<label class="col-sm-3"><?php echo $this->form->getLabel('alias'); ?></label>
						<div class="col-sm-9"><?php echo $this->form->getInput('alias'); ?></div>
					</div>

					<div class="form-group">
						<label class="col-sm-3"><?php echo $this->form->getLabel('state'); ?></label>
						<div class="col-sm-9"><?php echo $this->form->getInput('state'); ?></div>
					</div>

					<div class="form-group">
						<label class="col-sm-3"><?php echo $this->form->getLabel('catid'); ?></label>
						<div class="col-sm-9"><?php echo $this->form->getInput('catid'); ?></div>
					</div>

					<div class="form-group">
						<label class="col-sm-3"><?php echo $this->form->getLabel('description'); ?></label>
						<div class="col-sm-9"><?php echo $this->form->getInput('description'); ?></div>
					</div>

					<div class="form-group">
						<label class="col-sm-3"><?php echo $this->form->getLabel('start_date'); ?></label>
						<div class="col-sm-9"><?php echo $this->form->getInput('start_date'); ?></div>
					</div>

					<div class="form-group">
						<label class="col-sm-3"><?php echo $this->form->getLabel('end_date'); ?></label>
						<div class="col-sm-9"><?php echo $this->form->getInput('end_date'); ?></div>
					</div>

					<div class="form-group">
						<label class="col-sm-3"><?php echo $this->form->getLabel('image'); ?></label>
						<div class="col-sm-9">
							<?php echo $this->form->getInput('image'); ?><span class="help-block"><?php echo Text::_('COM_TJLMS_SUPPORTED_MEDIA_FILES_COURSE'); ?></span>
						</div>
					</div>

					<!-- If edit show IMage of lesson-->
						<?php if (!empty($this->item->image)) : ?>
							<?php //$lesson_imgPath = $this->tjlmsLessonHelper->getLessonImage((array)$lesson, "M_");?>
							<img src="<?php echo $this->item->image;?>" />
						<?php endif; ?>

				</fieldset>
			</div>
			<div class="col-sm-6">
				<fieldset class="adminform">
					<div class="form-group">

						<label class="col-sm-3"><?php echo $this->form->getLabel('no_of_attempts'); ?></label>
						<div class="col-sm-9"><?php echo $this->form->getInput('no_of_attempts'); ?>
						<div class="text-info"><?php echo Text::_('COM_TJLMS_FORM_DESC_LESSON_NO_OF_ATTEMPTS_NOTE'); ?></div>
						<input type="hidden" name="max_attempt" id="max_attempt" value="<?php echo $maxattempt;?>">
						<input type="hidden" name="no_attempts" id="no_attempts" value="<?php echo $this->form->getValue('no_of_attempts'); ?>">
						</div>
					</div>

					<div class="form-group">
						<label class="col-sm-3"><?php echo $this->form->getLabel('attempts_grade'); ?></label>
						<div class="col-sm-9"><?php echo $this->form->getInput('attempts_grade'); ?></div>
					</div>

				<?php if (!empty($this->course->id)) :?>

					<div class="form-group">
						<label class="col-sm-3"><?php echo $this->form->getLabel('eligibility_criteria'); ?></label>
						<div class="col-sm-9"><?php echo $this->form->getInput('eligibility_criteria'); ?></div>
					</div>

					<div class="form-group">
						<label class="col-sm-3"><?php echo $this->form->getLabel('consider_marks'); ?></label>
						<div class="col-sm-9"><?php echo $this->form->getInput('consider_marks'); ?></div>
					</div>

				<?php endif; ?>

				<?php if($this->params->get('allow_paid_courses','0','INT') == 1 && (!empty($this->course->id) && $this->course->type == 1)): ?>

					<div class="form-group">
						<label class="col-sm-2"><?php echo $this->form->getLabel('free_lesson'); ?></label>
						<div class="col-sm-9"><?php echo $this->form->getInput('free_lesson'); ?></div>
					</div>

				<?php endif; ?>

					<div class="form-group">
						<label class="col-sm-3"><?php echo $this->form->getLabel('in_lib'); ?></label>
						<div class="col-sm-9"><?php echo $this->form->getInput('in_lib'); ?></div>
					</div>

					<div class="form-group">
						<label class="col-sm-3"><?php echo $this->form->getLabel('ideal_time'); ?></label>
						<div class="col-sm-9"><?php echo $this->form->getInput('ideal_time'); ?></div>
					</div>
			</fieldset>

			</div>
		</div>
	</div>
		<input type="hidden" name="option" value="com_tjlms" />
		<input type="hidden" name="task" value="lessonform.save" />
		<input type="hidden" name="jform[format]" id="course_id" value="<?php echo $this->format; ?>" />
		<input type="hidden" name="jform[course_id]" id="course_id" value="<?php echo $this->courseId; ?>" />
		<input type="hidden" name="jform[mod_id]" id="mod_id" value="<?php echo $this->moduleId; ?>" />
		<input type="hidden" name="jform[id]" data-js-id="id" value="<?php echo $this->item->id;?>"/>
		<?php echo HTMLHelper::_('form.token'); ?>
</form>

