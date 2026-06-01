<?php
/**
 * @version     1.0.0
 * @package     com_tmt
 * @copyright   Copyright (C) 2013. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Techjoomla <contact@techjoomla.com> - http://techjoomla.com
 */
defined('_JEXEC') or die;
?>

<div class="answer-template answer-template-radio form-inline row-fluid" id="answer-template-radio">
	<div class="span4">
		<label id="answer-lbl" for="answers_text" class="required " style="display:none;">
			<?php echo JText::_('COM_TMT_Q_FORM_LBL_ANSWER');?>
		</label>

		<!--
		<input type="text" name="answers_text[]" id="answers_text" class="inputbox required" size="20" value="" placeholder="<?php echo JText::_('COM_TMT_Q_FORM_ANSWER_TEXT_PLACEOLDER');?>"/>
		-->

		<textarea type="text" name="answers_text[]" id="answers_text" class="inputbox required answers_text option-value" required="required" rows="5" cols="50"></textarea>

	</div>

	<input type="hidden" name="answer_id_hidden[]" id="answer_id" value="0" />
	<input type="hidden" name="answers_iscorrect_hidden[]" id="answers_iscorrect_hidden" class="answers_iscorrect_hidden" value="0" />

	<div class="span2">
		<input type="radio"  name="answers_iscorrect[]" id="answers_iscorrect" value="-1" class="answers_iscorrect" onclick="isCorrect(this);" title="<?php echo JText::_('COM_TMT_Q_FORM_TOOLTIP_ANSWER_MARK_CORRECT');?>"/>
	<?php echo JText::_('COM_TMT_Q_FORM_BUTTON_CORRECT');?>
	</div>

	<div class="span1">
		<label id="answers_marks-lbl" for="answers_marks" class="required " title="" style="display:none;">
			<?php echo JText::_('COM_TMT_Q_FORM_LBL_MARKS');?>
		</label>
		<input type="text" name="answers_marks[]" id="answers_marks" class="answers_marks inputbox required validate-number" size="2" value="0" style="width:30px !important;" onblur="getTotalMarks();" />
	</div>

	<div class="span3">
		<label id="answer-comments" for="answers_comments" class="required " style="display:none;">
			<?php echo JText::_('COM_TMT_Q_FORM_LBL_COMMENT');?>
		</label>

		<textarea type="text" name="answers_comments[]" id="answers_comments" class="inputbox option-value" rows="5" cols="50" ></textarea>
	</div>

	<div class="span1">
		<span class="btn" id="remove" onclick="removeAnswerClone(this);" title="<?php echo JText::_('COM_TMT_Q_FORM_TOOLTIP_ANSWER_DELETE');?>"><i class="icon-trash"> </i></span>
	</div>

	<div class="span1">
		<span class="btn sortable-handler" id="reorder" title="<?php echo JText::_('COM_TMT_Q_FORM_TOOLTIP_ANSWER_REORDER');?>"style="cursor: move;"><i class="icon-move"> </i></span>
	</div>
</div>


<div class="answer-template answer-template-checkbox form-inline row-fluid" id="answer-template-checkbox">
	<div class="span4">
		<label id="answer-lbl" for="answers_text" class="required " style="display:none;">
			<?php echo JText::_('COM_TMT_Q_FORM_LBL_ANSWER');?>
		</label>
		<!--
		<input type="text" name="answers_text[]" id="answers_text" class="inputbox required validate-name" size="20" value="" placeholder="<?php echo JText::_('COM_TMT_Q_FORM_ANSWER_TEXT_PLACEOLDER');?>"/>
		-->
		<textarea type="text" name="answers_text[]" id="answers_text" class="inputbox required answers_text" rows="5" cols="50"></textarea>
	</div>

	<input type="hidden" name="answer_id_hidden[]" id="answer_id" value="0" />

	<input type="hidden" name="answers_iscorrect_hidden[]" id="answers_iscorrect_hidden" class="answers_iscorrect_hidden" value="0" />

	<div class="span2">
		<input type="checkbox" name="answers_iscorrect[]" id="answers_iscorrect" class="answers_iscorrect" value="-1" onclick="isCorrect(this);" title="<?php echo JText::_('COM_TMT_Q_FORM_TOOLTIP_ANSWER_MARK_CORRECT');?>"/>
	<?php echo JText::_('COM_TMT_Q_FORM_BUTTON_CORRECT');?>
	</div>

	<div class="span1">
		<label id="answers_marks-lbl" for="answers_marks" class="required " title="" style="display:none;">
			<?php echo JText::_('COM_TMT_Q_FORM_LBL_MARKS');?>
		</label>
		<input type="text" name="answers_marks[]" id="answers_marks" class="answers_marks inputbox required validate-number" size="2" value="0" style="width:30px !important;" onblur="getTotalMarks();"/>
	</div>

	<div class="span3">
		<textarea type="text" name="answers_comments[]" id="answers_comments" class="inputbox" rows="5" cols="50"></textarea>
	</div>

	<div class="span1">
		<span class="btn" id="remove" onclick="removeAnswerClone(this);" title="<?php echo JText::_('COM_TMT_Q_FORM_TOOLTIP_ANSWER_DELETE');?>"><i class="icon-trash"> </i></span>
	</div>

	<div class="span1">
		<span class="btn sortable-handler" id="reorder" title="<?php echo JText::_('COM_TMT_Q_FORM_TOOLTIP_ANSWER_REORDER');?>"style="cursor: move;"><i class="icon-move"> </i></span>
	</div>
</div>


<div class="answer-template-text form-inline clearfix" id="answer-template-text">
	<div class="control-group">
		<div class="control-label" title="<?php echo JText::_('COM_TMT_Q_FORM_ANSWER_TEXT_LABEL');?>">
			<?php echo JText::_('COM_TMT_Q_FORM_ANSWER_TEXT_LABEL');?>
		</div>
		<div class="controls">

			<input type="hidden" name="answer_id_hidden[]" id="answer_id" value="0" />

			<input type="text" name="answers_text[]" id="answers_text" class="inputbox answers_text span2" size="20" value="" />
		</div>
	</div>
</div>

<div class="answer-template-textarea form-inline clearfix" id="answer-template-textarea">
	<div class="control-group">
		<div class="control-label" title="<?php echo JText::_('COM_TMT_Q_FORM_ANSWER_TEXT_LABEL');?>">
			<?php echo JText::_('COM_TMT_Q_FORM_ANSWER_TEXT_LABEL');?>
		</div>
		<div class="controls">

			<input type="hidden" name="answer_id_hidden[]" id="answer_id" value="0" />

			<textarea type="text" name="answers_text[]" id="answers_text" class="inputbox answers_text span4" rows="5" cols="50"></textarea>
		</div>
	</div>
</div>
