<?php
/**
 * @package Tjlms
 * @copyright Copyright (C) 2009 -2010 Techjoomla, Tekdi Web Solutions . All rights reserved.
 * @license GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link     http://www.com
 */
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
$user_id = JFactory::getUser()->id;		// Get current logged user ID

// Check for editing the quiz
$subformat = $lesson->sub_format;
$test_id = 0;
$qztype = "quiz";

$document=Factory::getDocument();


if (!empty($subformat))
{
	$subformat_source_options = explode('.', $subformat);
	$source_plugin = $subformat_source_options[0];
	$source_option = $subformat_source_options[1];

	if (!empty($source_option) && $source_plugin == 'quiz')
	{
		$test_id = $lesson->source;
		$qztype = $source_option;
	}
}

?>
<div class="tmt_form_errors alert alert-danger tmt-display-none">
	<div class="msg"></div>
</div>

<div class="quiz_allow_existing">
	<div class="control-group">
		<div class="center">
			<span class="input-append">
				<a onclick="opentjlmsSqueezeBox('index.php?option=com_tmt&view=tests&tmpl=component&addquiz=1&course_id=<?php echo $lesson->course_id; ?>&mod_id=<?php echo $lesson->mod_id; ?>&from_plugin=1&lesson_id=<?php echo $lesson->lesson_id; ?>&qztype=quiz')"
				class="btn btn-info quizaction btn-add-existquiz" title="Add existing quiz">
				<?php echo Text::sprintf('COM_TMT_ADD_EXISTQUIZ',$this->_name);?></a>
			</span>

			<span class="input-append">
				<a class="btn btn-primary btn-create-new-quiz" role="button" onclick="tjquiz.quizSpecificMetadata()" ><?php echo Text::_('COM_TMT_CREATE_NEW');?>	</a></span>
			</span>
		</div>
	</div>	<!--control-group-->
</div>	<!--tjquiz_add_quiz-->

<div class="quizmetadata_questions">
	<h3 class='center'><?php echo Text::_('PLG_TJQUIZ_QUIZ_HEADING'); ?></h3>
	<div class="quiz_metadata row-fluid">
		<?php echo $jformElement; ?>
	</div>

	<div id="questions_container" class="row-fluid">
		<div class="control-group formquiz-actions">
			<div class="center">
				<a data-bs-toggle="modal" data-bs-target="#tjquiz_get_quiz_question" onclick="tjform.getQuestion(this,'<?php echo $form_id;?>','<?php echo $qztype; ?>' );" class="btn btn-primary tjquiz_get_options" id="tjlessonasform_format">
				<?php
					if (!empty($questions) && !empty($sections))
					{
						echo Text::_('COM_TMT_VIEW_QUESTION');
					}
					else
					{
						echo Text::_('COM_TMT_ADD_QUESTION');
					}
				?>
				</a>
			</div>
		</div>	<!--control-group-->
	</div>	<!--questions_container-->

	<input type="hidden" name="lesson_format[quiz][addq]" id="addq" value = 0 >
	<input type="hidden" name="lesson_format[quiz][test_id]" id="test_id" value="<?php echo $test_id;?>" >
	<input type="hidden" id="subformatoption" name="lesson_format[quiz][subformatoption]" value="test" >
	<input type="hidden" id="qztype" name="lesson_format[quiz][qztype]" value="quiz" >
	<input type="hidden" name="lesson_format[quiz][created_by]" value="<?php echo $user_id; ?>" >
	<input type="hidden" name="lesson_format[quiz][lesson_id]" value="<?php echo $lesson->lesson_id; ?>" >
	<input type="hidden" name="lesson_format[quiz][state]" value="1" >
	<input type="hidden" name="lesson_format[quiz][reviewers][]" value="<?php echo $user_id; ?>" >
	<input type="hidden" name="course_id"  value="<?php echo $lesson->course_id; ?>" >
</div>	<!--quizmetadata_questions-->

<script>

	var form_id = '<?php echo $form_id;?>';
	var qztype = '<?php echo $qztype;?>';
	var tjlmsLesson = [];
	tjlmsLesson['<?php echo $form_id?>'] = [];
	tjlmsLesson['<?php echo $form_id?>']['allow_to_add_existing'] = <?php echo $allow_to_add_existing;?>;

	tjlmsLesson['<?php echo $form_id?>']['qztype'] = '<?php echo $qztype;?>';

	var lesson_format_form = techjoomla.jQuery('#lesson-format-form_'+form_id);
	var lesson_basic = techjoomla.jQuery('#lesson-basic-form_'+form_id);

	jQuery(document).ready(function(){
		tjform.init(form_id, qztype);
	});

	function getFormsubFormat(form_id)
	{
		var lesson_format_form	=techjoomla.jQuery('#lesson-format-form_'+form_id);
		var test_id = techjoomla.jQuery('#test_id',lesson_format_form).val();

		if (eval(tjlmsLesson[form_id]['allow_to_add_existing']) > 0 && test_id == 0)
		{
			techjoomla.jQuery('.quiz_allow_existing',lesson_format_form).show();
			techjoomla.jQuery('.quizmetadata_questions',lesson_format_form).hide();
		}
		else
		{
			tjquiz.quizSpecificMetadata(form_id);
		}
	}

	function validatequizquiz()
	{
		var res =tjform.validatequizquiz(form_id,qztype);

		return res;
	}
</script>
