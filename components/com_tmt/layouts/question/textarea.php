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

$q = $displayData['question'];
$textAreaMaxlength = $textAreaMinlength = 0;

if (!empty($q->params))
{
	$textAreaQuestionParams = json_decode($q->params);
	$textAreaMaxlength = ($textAreaQuestionParams->maxlength >= 1) ? $textAreaQuestionParams->maxlength : 0;
	$textAreaMinlength = ($textAreaQuestionParams->minlength >= 1) ? $textAreaQuestionParams->minlength : 0;
}

?>

<div class="col-sm-8">
	<textarea type="text"
		name="questions[subjective][<?php echo $q->question_id;?>]"
		id="questions<?php echo $q->question_id;?>"
		class="inputbox form-control"
		rows="10" cols="50"
		<?php echo ($textAreaMaxlength) ? 'maxlength=' . $textAreaMaxlength : ''?>
		<?php echo ($textAreaMinlength) ? 'minlength=' . $textAreaMinlength : ''?>><?php echo htmlentities($q->userAnswer);?></textarea>
<?php
if (!empty($textAreaMaxlength) || !empty($textAreaMinlength))
{
?>
	<span class="charscontainer pull-right">
		<?php
		if (!empty($textAreaMinlength))
		{
		?>
			<?php echo Text::_("COM_TMT_Q_FORM_PARAMS_TEXTAREA_COUNTER_TEXT_MIN") ?>
			<span class="charscontainer_minlength">
				<?php echo $textAreaMinlength; ?>
			</span>
		<?php
		}
		if (!empty($textAreaMaxlength))
		{
		?>
			<?php echo Text::_("COM_TMT_Q_FORM_PARAMS_TEXTAREA_COUNTER_TEXT_MAX") ?>

			<span class="charscontainer_maxlength">
				<?php echo $textAreaMaxlength; ?>
			</span>
		<?php
		}
		?>

		<?php echo Text::_("COM_TMT_Q_FORM_PARAMS_TEXTAREA_COUNTER_TEXT_CHARCTERS_ALLOWED_MSG"); ?>
			<span class="charscontainer_remaining">
				<?php echo $textAreaMaxlength; ?>
			</span>
	</span>
	<?php
}
?>
</div>
