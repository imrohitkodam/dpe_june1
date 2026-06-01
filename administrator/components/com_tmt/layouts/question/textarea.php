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
$answer = $q->answers[0];
$params = $q->params;
?>

<div class="answer-template-textarea form-inline clearfix" id="answer-template-textarea<?php echo $i;?>">
	<div class="control-group" data-js-id="textinput-input">
		<div class="control-label" title="<?php echo Text::_('COM_TMT_Q_FORM_ANSWER_TEXT_LABEL');?>">
			<?php echo Text::_('COM_TMT_Q_FORM_ANSWER_TEXT_LABEL');?>
		</div>
		<div class="controls">

			<input type="hidden" name="answer_id_hidden[]" value="<?php echo $answer->id;?>" />

			<textarea type="text" name="answers_text[]" class="inputbox answers_text" rows="5" cols="50"><?php echo $this->escape($answer->answer);?></textarea>
		</div>
	</div>
	<div class="alert alert-info" data-js-id="textinput-messsage">
		<?php echo Text::_("COM_TMT_QUESTION_TYPE_TEXT_FEEDBACK_MSG"); ?>
	</div>
	<div class="question-params-textarea form-inline clearfix" id="question-params-textarea">
		<div class="control-group">
			<div class="control-label" title="<?php echo Text::_('COM_TMT_Q_FORM_PARAMS_TEXTAREA_MINLENGTH'); ?>">
				<?php echo Text::_('COM_TMT_Q_FORM_PARAMS_TEXTAREA_MINLENGTH');?>
			</div>
			<div class="controls">
			<input type="text" name="jform[params][minlength]" class="inputbox question_params" data-js-id="answers_min_length" size="20"
			value="<?php echo !empty($params['minlength']) ? $params['minlength']:''; ?>" />
			</div>
		</div>
		<div class="control-group">
			<div class="control-label" title="<?php echo Text::_('COM_TMT_Q_FORM_PARAMS_TEXTAREA_MAXLENGTH'); ?>">
				<?php echo Text::_('COM_TMT_Q_FORM_PARAMS_TEXTAREA_MAXLENGTH');?>
			</div>
			<div class="controls">
			<input type="text" name="jform[params][maxlength]" class="inputbox question_params" data-js-id="answers_max_length" size="20"
			value="<?php echo !empty($params['maxlength']) ? $params['maxlength']:''; ?>" />
			</div>
		</div>
	</div>
</div>
