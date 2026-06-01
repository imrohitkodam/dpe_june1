<?php
/**
 * @package     Shika
 * @subpackage  com_tjlms
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;

HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('bootstrap.renderModal');
HTMLHelper::_('bootstrap.tooltip');

$options['relative'] = true;
HTMLHelper::_('script', 'com_tjlms/tjService.js', $options);
HTMLHelper::_('script', 'com_tjlms/tjlmsAdmin.js', $options);
HTMLHelper::_('script', 'com_tjlms/common.js', $options);
HTMLHelper::script(Uri::root() . 'administrator/components/com_tjlms/assets/js/ajax_file_upload.js');
$livetrackReviews = 0;

if (!empty($this->item->livetrackReviews))
{
	$livetrackReviews = $this->item->livetrackReviews;
}
?>

<?php

if (!$this->format && !$this->lessonId)
{
	echo $this->loadTemplate('lessontypesmodal');
}
else
{
?>
<div class="<?php echo COM_TJLMS_WRAPPER_DIV ?> tjBs3 manage-lesson manage-<?php echo $this->lessonId ? 'edit' : 'new';?>-lesson admin com_tjlms" id="lesson-form">

	<fieldset class="btn-toolbar center clearfix" id="tjtoolbar">
		<?php echo $this->toolbar->render();?>
	</fieldset>

	<hr class="hr hr-condensed"/>

	<div class="tjlms_add_lesson_form" data-js-unique="<?php echo $this->formId;?>">

		<fieldset>
			<?php if ($this->lessonId == 0)
			{
			?>
				<legend><h2><?php echo Text::_('COM_TJLMS_TITLE_ADD_LESSON')?></h2></legend>
			<?php
			}
			?>

			<?php echo HTMLHelper::_('bootstrap.startTabSet', 'lessonform', array('active' => 'general')); ?>

			<?php echo HTMLHelper::_('bootstrap.addTab', 'lessonform', 'general', Text::_('COM_TJLMS_TITLE_LESSON_DETAILS', true)); ?>

				<?php
					echo $this->loadTemplate('basic');
				?>
				<!--GENERAL TAB ENDS-->
			<?php echo HTMLHelper::_('bootstrap.endTab'); ?>

			<?php echo HTMLHelper::_('bootstrap.addTab', 'lessonform', 'format', Text::_('COM_TJLMS_TITLE_LESSON_FORMAT', true)); ?>

					<?php
						echo $this->loadTemplate('format');
					?>
			<?php echo HTMLHelper::_('bootstrap.endTab'); ?>

			<!--ASSOCIATE FILES STARTS-->
			<?php $allowAssocFiles = $this->params->get('allow_associate_files', '0', 'INT'); ?>

			<?php if ($allowAssocFiles == 1): ?>

					<?php echo HTMLHelper::_('bootstrap.addTab', 'lessonform', 'assocFiles', Text::_('COM_TJLMS_TITLE_LESSON_ASSOCIATE_FILES', true)); ?>

					<?php echo $this->loadTemplate('associatefiles');?>

					<?php echo HTMLHelper::_('bootstrap.endTab'); ?>

			<?php endif; ?>
			<!--ENDS-->

		<!--ASSESSMENT STARTS-->

		<?php if ($this->assessment): ?>

			<?php echo HTMLHelper::_('bootstrap.addTab', 'lessonform', 'assessment', Text::_('COM_TJLMS_TITLE_LESSON_ASSESSMENT', true)); ?>

						<?php echo $this->loadTemplate('assessment');?>

			<?php echo HTMLHelper::_('bootstrap.endTab'); ?>

		<?php endif; ?>

		<?php echo HTMLHelper::_('bootstrap.endTabSet'); ?>
		<?php if ($this->ifintmpl == 'component'): ?>
					<!-- show action buttons/toolbar -->
					<!--container-fluid-->
					<div class="container-fluid">
						<div class="row">
							<div class="form-actions pt-5 pb-5">
								<div class="btn-toolbar clearfix text-right" data-js-attr="form-actions">
									<div id="toolbar-prev" class="btn-wrapper d-inline-block">
										<button type="button" data-js-attr="form-actions-prev" class="btn com_tmt_button hide">
											<span class="valign-middle"><i class="icon-arrow-left"></i></span>
											<?php echo Text::_('COM_TJLMS_PREV') ?>
										</button>
									</div>

									<div id="toolbar-next" class="btn-wrapper d-inline-block">
										<button type="button"data-js-attr="form-actions-next" class="btn btn-success ml-10 com_tmt_button">
												<?php echo Text::_('COM_TJLMS_SAVE_NEXT') ?>
												<span class="valign-middle"><i class="icon-arrow-right"></i></span>
										</button>
									</div>

									<div id="toolbar-apply" class="btn-wrapper d-inline-block">
										<button type="button" id="button_save" class="btn btn-success com_tmt_button hide" onclick="Joomla.submitbutton('lesson.apply');">
											<span class="fa fa-check mr-0"></span>
											<?php echo Text::_('COM_TJLMS_BUTTON_SAVE') ?>
										</button>
									</div>

									<div id="toolbar-save" class="btn-wrapper d-inline-block">
										<button type="button" id="button_save_and_close" class="btn btn-success mr-5 com_tmt_button hide" onclick="Joomla.submitbutton('lesson.save')">
											<?php //echo (!$this->parentDiv) ? Text::_('COM_TMT_BUTTON_SAVE_AND_CLOSE') : Text::_('COM_TMT_BUTTON_SAVE_AND_ADD_TOQUIZ');?>
											<span class="fa fa-check mr-0"></span>
											<?php echo Text::_('COM_TJLMS_SAVE_CLOSE');?>
										</button>
									</div>

									<div id="toolbar-cancel" class="btn-wrapper d-inline-block">
										<button type="button" class="btn ml-5 com_tmt_button" onclick="Joomla.submitbutton('lesson.cancel')">
											<span class="icon-delete valign-middle"></span>
											<?php echo Text::_('COM_TJLMS_CANCEL') ?>
										</button>
									</div>
								</div><!--btn-toolbar-->
							</div>
						</div><!--row-fluid-->
					</div><!--container-fluid-->
				<?php endif; ?>
		</fieldset>
	</div>
</div>
<?php

$redirectURL = "index.php?option=com_tjlms&view=managelessons";
$redirectURL = $this->comtjlmsHelper->tjlmsRoute($redirectURL);

?>
<script>
	tjlmsAdmin.stepform.init("<?php echo $this->ifintmpl;?>", 1);
	tjlmsAdmin.lesson.init("<?php echo $this->ifintmpl;?>", "<?php echo $this->courseId;?>", "<?php echo $this->params->get('lesson_upload_size','0','INT'); ?>", "<?php echo $redirectURL;?>", "<?php echo $livetrackReviews; ?>");
</script>
<?php
	}
?>
