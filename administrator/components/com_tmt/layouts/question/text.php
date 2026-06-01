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
?>
<div class="answer-template-text form-inline clearfix" id="answer-template-text<?php echo $i;?>">
	<div class="control-group" data-js-id="textinput-input">
		<div class="control-label" title="<?php echo Text::_('COM_TMT_Q_FORM_ANSWER_TEXT_LABEL');?>">
			<?php echo Text::_('COM_TMT_Q_FORM_ANSWER_TEXT_LABEL');?>
		</div>
		<div class="controls">

			<input type="hidden" name="answer_id_hidden[]" id="answer_id<?php echo $i;?>" value="<?php echo $answer->id;?>" />

			<input type="text" name="answers_text[]" id="answers_text<?php echo $i;?>" class="inputbox answers_text" size="20" value="<?php echo $this->escape($answer->answer);?>"/>
		</div>
	</div>
	<div class="alert alert-info" data-js-id="textinput-messsage">
		<?php echo Text::_("COM_TMT_QUESTION_TYPE_TEXT_FEEDBACK_MSG"); ?>
	</div>
</div>
