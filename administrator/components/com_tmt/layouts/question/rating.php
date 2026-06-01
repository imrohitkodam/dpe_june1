<?php
/**
 * @package     TMT
 * @subpackage  com_tmt
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Language\Text;

$q = $displayData['item'];
$answers = $q->answers;
$ratinglabels = $q->params['rating_label'];
?>
<div class="answer-template-rating form-inline row-fluid" id="answer-template-rating<?php echo $i;?>">
	<div class="control-group">
	<?php
	foreach ($answers as $i => $answer)
	{
		if ($i == 0)
		{
			?>
			<div class="span6">
				<div class="control-label" title="<?php echo Text::_('COM_TMT_Q_FORM_ANSWER_TEXT_LABEL');?>">
					<label id="answers_text<?php echo $i;?>-lbl"
						for="answers_text<?php echo $i;?>"
						class="required lbl_answers_text<?php echo $i;?>" title="">
						<?php echo Text::_('COM_TMT_Q_FORM_LOWER_RATING_LABEL');?>
					</label>
				</div>
				<div class="controls">
					<input type="hidden" name="answer_id_hidden[]" id="answer_id<?php echo $i;?>" value="0" />
					<input type="text" name="answers_text[]"
					id="answers_text<?php echo $i;?>" class="inputbox answers_text span2 lower_range required validate-numeric"
					size="20" value="<?php echo (float) $answer->answer;?>" data-js-id="answers_lower_text" />
				</div>
			</div>
			<?php
		}
		else
		{
			?>
			<div class="span6">
				<div class="control-label" title="<?php echo Text::_('COM_TMT_Q_FORM_ANSWER_TEXT_LABEL');?>">
					<label id="answers_text<?php echo $i;?>-lbl"
						for="answers_text<?php echo $i;?>"
						class="required lbl_answers_text<?php echo $i;?>" title="">
						<?php echo Text::_('COM_TMT_Q_FORM_UPPER_RATING_LABEL');?>
					</label>
				</div>
				<div class="controls">
					<input type="hidden" name="answer_id_hidden[]" id="answer_id<?php echo $i;?>" value="0" />

					<input type="text" name="answers_text[]" id="answers_text<?php echo $i;?>"
					class="inputbox answers_text span2 upper_range required validate-numeric" size="20"
					value="<?php echo (float) $answer->answer;?>" data-js-id="answers_upper_text"/>
				</div>
			</div>
			<?php
		}
	}
	?>
	</div>
</div>
<div class="control-group">
<div class="control-label"><label id="jform_params_rating_label-lbl"
	for="jform_params_rating_label1" class="hasPopover"
	title="<?php echo Text::_('COM_TMT_FORM_RATING_LABEL');?>"
	data-content="<?php echo Text::_('COM_TMT_FORM_RATING_LABEL_DESC');?>">
	<?php echo Text::_('COM_TMT_FORM_RATING_LABEL');?></label>
</div>
	<div class="controls">
	<textarea name="jform[params][rating_label]" id="jform_params_rating_label1" class="textarea" value="<?php echo $ratinglabels;?>"><?php echo $ratinglabels;?></textarea>
	</div>
</div>
