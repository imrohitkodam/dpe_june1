<?php
/**
 * @version     1.0.0
 * @package     com_tmt
 * @copyright   Copyright (C) 2013. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Techjoomla <contact@techjoomla.com> - http://techjoomla.com
 */
defined('_JEXEC') or die;

$value= ( $answer->is_correct ) ? '1' : '0';
$checked= ( $answer->is_correct ) ? 'checked' : '';

switch ($this->item->type)
{
	case "radio":
	?>
		<div class="answer-template answer-template-radio form-inline row-fluid" id="answer-template-radio<?php echo $i;?>">
			<div class="span4">
				<label id="answer-lbl<?php echo $i;?>" for="answers_text<?php echo $i;?>" class="required answers_text" style="display:none;">
					<?php echo JText::_('COM_TMT_Q_FORM_LBL_ANSWER');?>
				</label>
				<!--
				<input type="text" name="answers_text[]" id="answers_text<?php echo $i;?>" class="inputbox required" size="20" value="<?php echo $answer->answer;?>" placeholder="<?php echo JText::_('COM_TMT_Q_FORM_ANSWER_TEXT_PLACEOLDER');?>"/>
				-->
				<textarea type="text" name="answers_text[]" id="answers_text<?php echo $i;?>" class="inputbox required answers_text option-value" required="required" rows="5" cols="50"><?php echo $answer->answer;?></textarea>
			</div>
			<input type="hidden" name="answer_id_hidden[]" id="answer_id<?php echo $i;?>" value="<?php echo $answer->id;?>" />

			<input type="hidden" class="answers_iscorrect_hidden" name="answers_iscorrect_hidden[]" id="answers_iscorrect_hidden<?php echo $i;?>" value="<?php echo $value;?>" />

			<div class="span2">
				<input type="radio" name="answers_iscorrect[]" id="answers_iscorrect<?php echo $i;?>" class="answers_iscorrect" value="-1" onclick="isCorrect(this);" title="<?php echo JText::_('COM_TMT_Q_FORM_TOOLTIP_ANSWER_MARK_CORRECT');?>" <?php echo $checked;?> />
				<?php echo JText::_('COM_TMT_Q_FORM_BUTTON_CORRECT');?>
			</div>

			<div class="span1">
				<label id="answers_marks-lbl<?php echo $i;?>" for="answers_marks<?php echo $i;?>" class="required " title="" style="display:none;">
					<?php echo JText::_('COM_TMT_Q_FORM_LBL_MARKS');?>
				</label>
				<input type="text" name="answers_marks[]" id="answers_marks<?php echo $i;?>" class="answers_marks inputbox required validate-whole-number" size="2" value="<?php echo $answer->marks;?>" style="width:30px !important;" onblur="getTotalMarks();" />
			</div>

			<div class="span3">
				<label id="answer-comments" for="answers_comments<?php echo $i;?>" style="display:none;">
				<?php echo JText::_('COM_TMT_Q_FORM_LBL_COMMENT');?>
				</label>
				<textarea type="text" name="answers_comments[]" id="answers_comments<?php echo $i;?>" class="inputbox option-value" rows="5" cols="50"><?php echo $answer->comments;?></textarea>
			</div>

			<div class="span1">
				<span class="btn" id="remove<?php echo $i;?>" onclick="removeAnswerClone(this);" title="<?php echo JText::_('COM_TMT_Q_FORM_TOOLTIP_ANSWER_DELETE');?>"><i class="icon-trash"> </i></span>
			</div>

			<div class="span1">
				<span class="btn sortable-handler" id="reorder<?php echo $i;?>" title="<?php echo JText::_('COM_TMT_Q_FORM_TOOLTIP_ANSWER_REORDER');?>"style="cursor: move;"><i class="icon-move"> </i></span>
			</div>
		</div>
	<?php
	break;

	case "checkbox":
		?>
		<div class="answer-template answer-template-checkbox form-inline row-fluid" id="answer-template-checkbox<?php echo $i;?>">
			<div class="span4">
				<label id="answer-lbl<?php echo $i;?>" for="answers_text<?php echo $i;?>" class="required answers_text" style="display:none;">
					<?php echo JText::_('COM_TMT_Q_FORM_LBL_ANSWER');?>
				</label>
				<!--
				<input type="text" name="answers_text[]" id="answers_text<?php echo $i;?>" class="inputbox required" size="20" value="<?php echo $answer->answer;?>" placeholder="<?php echo JText::_('COM_TMT_Q_FORM_ANSWER_TEXT_PLACEOLDER');?>"/>
				-->
				<textarea type="text" name="answers_text[]" id="answers_text<?php echo $i;?>" class="inputbox answers_text" rows="5" cols="50"><?php echo $answer->answer;?></textarea>
			</div>

			<input type="hidden" name="answer_id_hidden[]" id="answer_id<?php echo $i;?>" value="<?php echo $answer->id;?>" />

			<input type="hidden" class="answers_iscorrect_hidden" name="answers_iscorrect_hidden[]" id="answers_iscorrect_hidden<?php echo $i;?>" value="<?php echo $value;?>" />

			<div class="span2">
				<input type="checkbox" name="answers_iscorrect[]" id="answers_iscorrect<?php echo $i;?>" class="answers_iscorrect" value="-1" onclick="isCorrect(this);" title="<?php echo JText::_('COM_TMT_Q_FORM_TOOLTIP_ANSWER_MARK_CORRECT');?>" <?php echo $checked;?> />
			<?php echo JText::_('COM_TMT_Q_FORM_BUTTON_CORRECT');?>
			</div>
			<div class="span1">
				<label id="answers_marks-lbl<?php echo $i;?>" for="answers_marks<?php echo $i;?>" class="required " title="" style="display:none;">
					<?php echo JText::_('COM_TMT_Q_FORM_LBL_MARKS');?>
				</label>
				<input type="text" name="answers_marks[]" id="answers_marks<?php echo $i;?>" class="answers_marks inputbox required validate-number" size="2" value="<?php echo $answer->marks;?>" style="width:30px !important;"  onblur="getTotalMarks();" />
			</div>

			<div class="span3">
				<textarea type="text" name="answers_comments[]" id="answers_comments<?php echo $i;?>" class="inputbox" rows="5" cols="50"><?php echo $answer->comments;?></textarea>
			</div>

			<div class="span1">
				<span class="btn" id="remove<?php echo $i;?>" onclick="removeAnswerClone(this);" title="<?php echo JText::_('COM_TMT_Q_FORM_TOOLTIP_ANSWER_DELETE');?>"><i class="icon-trash"> </i></span>
			</div>

			<div class="span1">
				<span class="btn sortable-handler" id="reorder<?php echo $i;?>" title="<?php echo JText::_('COM_TMT_Q_FORM_TOOLTIP_ANSWER_REORDER');?>"style="cursor: move;"><i class="icon-move"> </i></span>
			</div>
		</div>
	<?php
	break;

	case "text":
		?>
		<div class="answer-template-text form-inline clearfix" id="answer-template-text<?php echo $i;?>">
			<div class="control-group">
				<div class="control-label" title="<?php echo JText::_('COM_TMT_Q_FORM_ANSWER_TEXT_LABEL');?>">
					<?php echo JText::_('COM_TMT_Q_FORM_ANSWER_TEXT_LABEL');?>
				</div>
				<div class="controls">

					<input type="hidden" name="answer_id_hidden[]" id="answer_id<?php echo $i;?>" value="<?php echo $answer->id;?>" />

					<input type="text" name="answers_text[]" id="answers_text<?php echo $i;?>" class="inputbox answers_text" size="20" value="<?php echo $answer->answer;?>"/>
				</div>
			</div>
		</div>
		<?php
	break;

	case "textarea":
		?>
		<div class="answer-template-textarea form-inline clearfix" id="answer-template-textarea<?php echo $i;?>">
			<div class="control-group">
				<div class="control-label" title="<?php echo JText::_('COM_TMT_Q_FORM_ANSWER_TEXT_LABEL');?>">
					<?php echo JText::_('COM_TMT_Q_FORM_ANSWER_TEXT_LABEL');?>
				</div>
				<div class="controls">

					<input type="hidden" name="answer_id_hidden[]" id="answer_id<?php echo $i;?>" value="<?php echo $answer->id;?>" />

					<textarea type="text" name="answers_text[]" id="answers_text<?php echo $i;?>" class="inputbox answers_text" rows="5" cols="50"><?php echo $answer->answer;?></textarea>
				</div>
			</div>
		</div>
		<?php
	break;
	default:
		//do nothing
}
?>


