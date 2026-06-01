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

use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;

HTMLHelper::_('bootstrap.renderModal', 'a.modal');
HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('jquery.framework');

$options['relative'] = true;
HTMLHelper::_('script', 'com_tjlms/tjService.js', $options);
HTMLHelper::_('script', 'com_tjlms/tjlmsAdmin.js', $options);
HTMLHelper::_('script', 'com_tjlms/common.js', $options);
HTMLHelper::script(Uri::root().'administrator/components/com_tjlms/assets/js/ajax_file_upload.js');

?>
<div class="<?php echo COM_TJLMS_WRAPPER_DIV ?> tjBs3">
	<div class="form-horizontal tjlms_add_lesson_form" data-js-unique="<?php echo $this->formId;?>">

		<?php
			ob_start();
			include JPATH_BASE . '/components/com_tjlms/layouts/header.sidebar.php';
			$layoutOutput = ob_get_contents();
			ob_end_clean();
			echo $layoutOutput;
		?>

		<fieldset>
			<div class="tjlms_form_errors alert alert-danger">
				<div class="msg"></div>
			</div>

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
		<?php $allowAssocFiles = $this->params->get('allow_associate_files','0','INT'); ?>

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
		<!--ENDS-->

		<?php echo HTMLHelper::_('bootstrap.endTabSet'); ?>

		<?php if ($this->ifintmpl == 'component'): ?>
					<!-- show action buttons/toolbar -->
					<!--container-fluid-->
					<div class="container-fluid">
						<div class="row-fluid">
							<div class="form-actions mt-10 mb-10">
								<div class="btn-toolbar clearfix text-right" data-js-attr="form-actions">
									<div id="toolbar-prev" class="btn-wrapper tjlms-prev-btn">
										<button type="button" data-js-attr="form-actions-prev" class="btn com_tmt_button  d-none">
											<span class="fas fa-chevron-left valign-middle"></span>
											<?php echo Text::_('COM_TJLMS_PREV') ?>
										</button>
									</div>

									<div id="toolbar-next" class="btn-wrapper">
										<button type="button"data-js-attr="form-actions-next" class="btn btn-success  com_tmt_button">
												<?php echo Text::_('COM_TJLMS_SAVE_NEXT') ?>
												<span class="fas fa-chevron-right valign-middle"></span>
										</button>
									</div>

									<div id="toolbar-apply" class="btn-wrapper">
										<button type="button" id="button_save" class="btn btn-success com_tmt_button d-none" onclick="Joomla.submitbutton('lesson.apply');">
											<span class="fa fa-check mr-0"></span>
											<?php echo Text::_('COM_TJLMS_BUTTON_SAVE') ?>
										</button>
									</div>

									<div id="toolbar-save" class="btn-wrapper">
										<button type="button" id="button_save_and_close" class="btn btn-success  com_tmt_button d-none" onclick="Joomla.submitbutton('lesson.save')">
											<?php //echo (!$this->parentDiv) ? Text::_('COM_TMT_BUTTON_SAVE_AND_CLOSE') : Text::_('COM_TMT_BUTTON_SAVE_AND_ADD_TOQUIZ');?>
											<span class="fa fa-check mr-0"></span>
											<?php echo Text::_('COM_TJLMS_SAVE_CLOSE');?>
										</button>
									</div>

									<div id="toolbar-cancel" class="btn-wrapper tjlms-cancel-btn">
										<button type="button" class="btn com_tmt_button" onclick="Joomla.submitbutton('lesson.cancel')">
											<span class="fa fa-times valign-middle"></span>
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

if ($this->courseId)
{
	$redirectURL = Route::_(Uri::base() . "index.php?option=com_tjlms&view=modules&course_id=" . $this->courseId);
}
else
{
	$redirectURL = "index.php?option=com_tjlms&view=lessons";
}

?>
<script>
	tjlmsAdmin.stepform.init("<?php echo $this->ifintmpl;?>", 1);
	tjlmsAdmin.lesson.init("<?php echo $this->ifintmpl;?>", "<?php echo $this->courseId;?>", "<?php echo $this->params->get('lesson_upload_size','0','INT'); ?>", "<?php echo $redirectURL;?>", "<?php echo isset($this->item->livetrackReviews) ? $this->item->livetrackReviews : 0; ?>");
</script>

