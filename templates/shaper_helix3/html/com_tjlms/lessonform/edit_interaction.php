<?php
/**
 * @version    SVN: 539
 * @package    Shika
 * @author     TechJoomla | <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 *
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\Registry\Registry;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/* load language file*/
$lang = Factory::getLanguage();
$extension = 'com_dpe';
$lang->load($extension);

// Get plugin params
$this->pluginParams  = new Registry($this->plugin->params);

HTMLHelper::script('media/com_jlike/js/interaction.min.js');
?>
<form action="<?php echo Route::_('index.php?option=com_tjlms&view=lessonform&id=' . $this->lessonId);?>"
	method="post" enctype="multipart/form-data" name="lesson-interaction" id="lesson-interaction-form_<?php echo $this->formId;?>"
	class="form-validate form-horizontal lesson_interaction_form" >
	<div class="container-fluid">
		<?php
			if ($this->pluginParams->get('read_interaction') == 1)
			{
			?>
		<div class="form-group">
			<div class="col-sm-3"><?php echo $this->form->getLabel('read_interaction');?></div>
			<div class="col-sm-9"><?php echo $this->form->getInput('read_interaction');?></div>
		</div>
		<?php
			}

			if ($this->pluginParams->get('practice_interaction') == 1)
			{
			?>
		<div class="form-group">
			<div class="col-sm-3"><?php echo $this->form->getLabel('practice_interaction');?></div>
			<div class="col-sm-9"><?php echo $this->form->getInput('practice_interaction');?></div>
		</div>
		<?php
			}

			if ($this->pluginParams->get('publicly_interaction') == 1)
			{
			?>
		<div class="form-group">
			<div class="col-sm-3"><?php echo $this->form->getLabel('publicly_interaction');?></div>
			<div class="col-sm-9"><?php echo $this->form->getInput('publicly_interaction');?></div>
		</div>
		<?php
			}

			if (($this->pluginParams->get('read_interaction') === 0) && ($this->pluginParams->get('practice_interaction') === 0))
			{
			?>
		<div class="alert alert-info" role="alert">
			<?php echo Text::_('COM_TJLMS_LESSON_NO_INTERACTION_OPTION_CONFIGURED');?>
		</div>
		<?php
			}
			?>
	</div>
	<input type="hidden" name="option" value="com_tjlms" />
	<input type="hidden" name="task"   value="lessonform.saveInteraction" />
	<input type="hidden" name="doc_interaction_id" data-js-id="id" value="<?php echo (!empty($this->item->id)) ? $this->item->id : 0;?>" />
	<?php echo HTMLHelper::_('form.token'); ?>
</form>

<div class="modal fade dp-modal-center" id="documentProgress" role="dialog">
	<div class="modal-dialog modal-dialog-centered" role="document">
		<div class="modal-content">
			<!-- Modal Header -->
			<div class="modal-header">
				<h4 class="modal-title font-600"><?php echo Text::_("COM_DPE_DOCUMENT_COPY_MODAL_HEADER");?></h4>
			</div>
			<!-- Modal body -->
			<div class="modal-body">
				<div class="progress pb-0 h-20">
					<div class="progress-bar progress-bar-striped active" role="progressbar"
						aria-valuenow="40" aria-valuemin="0" aria-valuemax="100" style="width:0%">
						<?php echo Text::sprintf("COM_DPE_DOCUMENT_COPY_PROGRESS", '0%');?>
					</div>
				</div>
				<div class="failed-documents hide">
					<h5 class="font-600"><?php echo Text::_("COM_DPE_DOCUMENT_COPY_FAILED_DOCUMENTS");?></h5>
					<ul class="failed-documents-content list-unstyled">
					</ul>
				</div>
<!-- 				<div class="completed-info hide">
					<p class="alert alert-success"><?php //echo Text::_("COM_DPE_DOCUMENT_COPY_MODAL_REDIRECTION"); ?></p> -->
<!-- 				</div> -->
			</div>
			<!-- Modal footer -->
			<div class="modal-footer hide">
				<button type="button" class="btn btn-default close-modal" data-dismiss="modal"><?php echo Text::_("COM_DPE_DOCUMENT_COPY_MODAL_CLOSE");?></button>
			</div>
		</div>
	</div>
</div>

<script>
	document.addEventListener("DOMContentLoaded", function () {
		const interactionData = <?php echo json_encode($this->item->interaction); ?>;

		for (const [key, value] of Object.entries(interactionData)) {
			if (value == 1) {
		const checkbox = document.querySelector(`[name="jform[${key}]"]`);
				if (checkbox) {
					checkbox.checked = true;
				}
			}
		}
	});
</script>

