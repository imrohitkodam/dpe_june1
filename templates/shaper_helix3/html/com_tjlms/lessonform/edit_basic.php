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
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Factory;

$maxattempt = 0;

if ($this->lessonId > 0)
{
	$maxattempt = $this->item->max_attempt;

	if (empty ($maxattempt))
	{
		$maxattempt = 0;
	}
}

// Get Current url for notification manager widget
$app         = Factory::getApplication()->input;
$extraParams = Uri::getInstance()->toString(array('query'));
$extraParams = str_replace('?', '&', $extraParams);
$currentUrl  = 'index.php?option=' . $app->get('option') . '&view=' . $app->get('view') . $extraParams .'&Itemid=' . $app->get('Itemid');
?>
<form action="<?php echo Route::_('index.php?option=com_tjlms&view=lessonform&id=' . $this->lessonId); ?>" method="post" enctype="multipart/form-data" name="adminForm" id="lesson-basic-form_<?php echo $this->formId;?>" class="form-validate form-horizontal lesson_basic_form ucm-form-styling col-sm-9	" >
	<div class="clearfix mb-10"> </div>
	<div class="container-fluid">
		<div class="row">
			<!-- <div class="col-sm-6"> -->
				<fieldset class="adminform">
					<input type="hidden" class="extra_validations" data-js-validation-functions="tjlmsAdmin.validateDates,tjlmsAdmin.basicForm.validate">
					<div class="form-group" style="display:none;">
						<div class="col-sm-3"><?php echo $this->form->getLabel('id'); ?></div>
						<div class="col-sm-9"><?php echo $this->form->getInput('id'); ?></div>
					</div>

<!--
DPE - Hack - Start
-->
					<div class="form-group">
						<div class="col-sm-3"><?php echo $this->form->getLabel('cluster_id'); ?></div>
						<div class="col-sm-9"><?php echo $this->form->getInput('cluster_id'); ?></div>
					</div>
<!--
DPE - Hack - End
-->

					<div class="form-group">
						<div class="col-sm-3"><?php echo $this->form->getLabel('title'); ?></div>
						<div class="col-sm-9"><?php echo $this->form->getInput('title'); ?></div>
					</div>

					<div class="form-group">
						<div class="col-sm-3"><?php echo $this->form->getLabel('description'); ?></div>
						<div class="col-sm-9"><?php echo $this->form->getInput('description'); ?></div>
					</div>

					<div class="form-group hide">
						<div class="col-sm-3"><?php echo $this->form->getLabel('start_date'); ?></div>
						<div class="col-sm-9"><?php echo $this->form->getInput('start_date'); ?></div>
					</div>

					<div class="form-group hide">
						<div class="col-sm-3"><?php echo $this->form->getLabel('image'); ?></div>
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
			<!-- </div> -->
			<div class="col-sm-6">
				<fieldset class="adminform">
					<div class="form-group hide">
						<div class="col-sm-3"><?php echo $this->form->getLabel('alias'); ?></div>
						<div class="col-sm-9"><?php echo $this->form->getInput('alias'); ?></div>
					</div>
					<div class="form-group hide">
						<div class="col-sm-3"><?php echo $this->form->getLabel('state'); ?></div>
						<div class="col-sm-9 lesson-state"><?php echo $this->form->getInput('state'); ?></div>
					</div>
					<div class="form-group hide">
						<div class="col-sm-3"><?php echo $this->form->getLabel('end_date'); ?></div>
						<div class="col-sm-9"><?php echo $this->form->getInput('end_date'); ?></div>
					</div>

					<div class="form-group hide">

						<div class="col-sm-3"><?php echo $this->form->getLabel('no_of_attempts'); ?></div>
						<div class="col-sm-9"><?php echo $this->form->getInput('no_of_attempts'); ?>
						<div class="text-info"><?php echo Text::_('COM_TJLMS_FORM_DESC_LESSON_NO_OF_ATTEMPTS_NOTE'); ?></div>
						<input type="hidden" name="max_attempt" id="max_attempt" value="<?php echo $maxattempt;?>">
						<input type="hidden" name="no_attempts" id="no_attempts" value="<?php echo $this->form->getValue('no_of_attempts'); ?>">
						</div>
					</div>

					<div class="form-group hide">
						<div class="col-sm-3"><?php echo $this->form->getLabel('attempts_grade'); ?></div>
						<div class="col-sm-9"><?php echo $this->form->getInput('attempts_grade'); ?></div>
					</div>

				<?php if (!empty($this->course->id)) :?>

					<div class="form-group hide">
						<div class="col-sm-3"><?php echo $this->form->getLabel('eligibility_criteria'); ?></div>
						<div class="col-sm-9"><?php echo $this->form->getInput('eligibility_criteria'); ?></div>
					</div>

					<div class="form-group">
						<div class="col-sm-3"><?php echo $this->form->getLabel('consider_marks'); ?></div>
						<div class="col-sm-9"><?php echo $this->form->getInput('consider_marks'); ?></div>
					</div>

				<?php endif; ?>

				<?php if ($this->params->get('allow_paid_courses', '0', 'INT') == 1 && $this->course->type == 1): ?>

					<div class="form-group hide">
						<div class="col-sm-2"><?php echo $this->form->getLabel('free_lesson'); ?></div>
						<div class="col-sm-9"><?php echo $this->form->getInput('free_lesson'); ?></div>
					</div>

				<?php endif; ?>

					<div class="form-group hide">
						<div class="col-sm-3"><?php echo $this->form->getLabel('ideal_time'); ?></div>
						<div class="col-sm-9"><?php echo $this->form->getInput('ideal_time'); ?></div>
					</div>
			</fieldset>

			</div>
		</div>
	</div>
		<input type="hidden" name="option" value="com_tjlms" />
		<input type="hidden" name="task" value="lessonform.save" />
		<input type="hidden" name="jform[format]" id="course_id" value="<?php echo $this->format; ?>" />
		<input type="hidden" name="jform[course_id]" id="course_id" value="<?php echo ($this->courseId)?$this->courseId:0; ?>" />
		<input type="hidden" name="jform[mod_id]" id="mod_id" value="<?php echo $this->moduleId; ?>" />
		<input type="hidden" name="jform[id]" data-js-id="id" value="<?php echo $this->item->id;?>"/>
		<input type="hidden" name="jform[in_lib]" id="in_lib" value="1" />

		<!-- DPE Hack start to add hidden fields to create content for notification manager -->
		<input type="hidden" name="url" id="url" value="<?php echo $currentUrl;?>"/>
		<input type="hidden" name="element" id="element" value="com_tjlms.lesson"/>
		<input type="hidden" name="element_id" id="element_id" value="<?php echo $this->item->id;?>"/>
		<input type="hidden" name="cluster_id" id="cluster_id" value="<?php echo $this->form->getValue('cluster_id');?>"/>
		<!-- DPE Hack end -->

		<?php echo HTMLHelper::_('form.token'); ?>
</form>

