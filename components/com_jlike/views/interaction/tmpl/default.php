<?php
/**
 * @package     JLike
 * @subpackage  COM_JLIKE
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Registry\Registry;
use Joomla\CMS\HTML\HTMLHelper;

$lang = Factory::getLanguage();
$lang->load('COM_JLIKE');

Text::script('COM_JLIKE_INTERACTION_USED_TEXT_PLACEHOLDER');
Text::script('COM_JLIKE_INTERACTION_USED_ROLLBACK_CONFIRMATION');
Text::script('COM_JLIKE_INTERACTION_AJAX_ERROR');

$descriptionClass = "hide";
$readClass        = '';
$readChecked = $useChecked = $consentChecked = '';

if (!empty($this->interactionData) && $this->interactionData->todo_id)
{
	if ($this->interactionData->read == 1)
	{
		$readChecked = 'checked';
		$readClass = 'disabled';
	}

	if ($this->interactionData->used == 1)
	{
		$useChecked = 'checked';
		$descriptionClass = '';
	}
}


HTMLHelper::script('media/com_jlike/js/interaction.min.js');

$jlikeTjlmslessonPlugin = new Registry($this->jlikeTjlmslessonPlugin->params);
?>
<form method="post" name="adminForm" id="interactionForm">
	<div class="container-fluid">
		<div>
			<?php echo Text::_("COM_JLIKE_INTERACTOIN_NOTE");?>
		</div>

		<?php
		if (!empty($this->contentInteractionDataObj) && isset($this->contentInteractionDataObj['read_interaction']) && $jlikeTjlmslessonPlugin->get('read_interaction') == '1')
		{
			?>
			<!--Read Interaction-->
			<div class="checkbox">
				<label>
					<input
						type="checkbox"
						name = "interaction[]"
						value="<?php echo $this->todo_id; ?>"
						id="interactionRead" <?php echo $readChecked?> <?php echo $readClass;?>/>
						<?php echo Text::_('COM_JLIKE_INTERACTION_READ_TEXT');?>
				</label>
			</div>
		<?php
		}

		if (!empty($this->contentInteractionDataObj) && isset($this->contentInteractionDataObj['practice_interaction']) && $jlikeTjlmslessonPlugin->get('practice_interaction') == '1')
		{
			?>
			<!--Used Interaction-->
			<div class="checkbox">
				<label>
					<input
						type="checkbox"
						name = "interaction[]"
						value="<?php echo $this->todo_id; ?>"
						id="interactionUsed" <?php echo $useChecked?> />
						<?php echo Text::_('COM_JLIKE_INTERACTION_USED_TEXT');?>
				</label>
			</div>

			<div class="usedActions <?php echo $descriptionClass;?>">
				<div>
					<textarea id="interactionUsedText" name="used_text<?php echo $this->todo_id;?>"class="used-description inputbox form-control w-100"required="required" data-js-id="used-description" rows="5" cols="30"placeholder="<?php echo Text::_('COM_JLIKE_INTERACTION_USED_TEXT_PLACEHOLDER');?>"><?php echo $this->escape($this->interactionData->description);?></textarea>
				</div>
				<div>
					<p class="alert-success success-msg"></p>
					<input
						type="button"
						class="btn btn-primary pull-right"
						id="interactionUsedSave"
						data-todo-id=<?php echo $this->todo_id; ?>
						value="<?php echo Text::_('COM_JLIKE_INTERACTION_SUBMIT');?>">
				</div>
			</div>
		<?php
		}
		?>
		<div class="clearfix"></div>
		<div class="alert alert-info interactionInfo-help hide mt-10 alert-dismissible" role="alert">
			<span class="content fs-12"></span>
			<button type="button" class="interaction-close-alert close" data-dismiss="alert" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
		</div>
	</div>
</form>
<div class="modal jlike-interaction-custom-modal" tabindex="-1" role="dialog" id="JlikeInteractionModal" data-dismiss="modal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-body text-center">
			<div>
			<?php echo Text::_('COM_JLIKE_INTERACTION_READ_POPUP_TEXT'); ?>
		</div>
		<div class="my-10">
			<button type="submit" class="btn btn-primary relative btn-lg fs-14" value="<?php echo $this->todo_id; ?>" id="interactionAgree">
				<?php echo Text::_('COM_JLIKE_INTERACTION_READ_POPUP_BUTTON_AGREE');?>
			</button>
			<button type="button" class="btn btn-default btn-lg fs-14" id="interactionCancel">
				<?php echo Text::_('COM_JLIKE_INTERACTION_READ_POPUP_BUTTON_CANCEL');?>
			</button>
		</div>

			<div class="alert alert-info readInfo-help hide alert-dismissible" role="alert">
				<button type="button" class="close" data-dismiss="alert" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
				<span class="content fs-14"></span>
			</div>
      </div>
    </div>
  </div>
</div>
