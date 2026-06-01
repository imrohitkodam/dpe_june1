<?php
// Import helper for declaring language constant
JLoader::import('TmtHelper', JUri::root().'administrator/components/com_tmt/helpers/tmt.php');
// Call helper function
TmtHelper::getLanguageConstant();
JHtml::_('behavior.formvalidation');
?>
<div id="autoQuestionModal">
		<button type="button" class="close" onclick="closebackendPopup(1);" data-dismiss="modal" aria-hidden="true">×</button>
		<strong class="componentheading"><h2><?php echo JText::_('COM_TMT_FORM_TEST_AUTO_GENERATE_QP');?></h2></strong>
		<div class="alert alert-info"><?php echo JText::_('COM_TMT_FORM_TEST_AUTO_GENERATE_QUIZ_NOTICE');?></div>
		<hr/>
	<div class="rules_block rules-container" id="rules_block">
		<div class="rule-template" id="rule-template0">
			<div class="clearfix">
				<span class="small"><?php echo JText::_('COM_TMT_TEST_FORM_LBL_ADD');?></span>
				<span class="autopicquestion">*</span>
				<input type="text" name="questions_count[]" id="questions_count"
				class="inputbox input-mini questions_count" value=""
				style="width:30px !important;"
				placeholder="<?php echo JText::_('0');?>" />
				<span class="small"><?php echo JText::_('COM_TMT_TEST_FORM_LBL_QUESTIONS');?></span>

				<span class="small"><?php echo JText::_('COM_TMT_TEST_FORM_LBL_EACH');?></span>
				<span class="autopicquestion">*</span>
				<input type="text" name="questions_marks[]" id="questions_marks"
				class="inputbox input-mini questions_marks" value=""
				style="width:30px !important;"
				placeholder="<?php echo JText::_('0');?>"/>
				<span class="small"><?php echo JText::_('COM_TMT_TEST_FORM_LBL_MARKS');?></span>

				<span class="small"><?php echo JText::_('COM_TMT_TEST_FORM_LBL_FROM');?></span>
				<?php
					echo JHtml::_('select.genericlist', $this->categories, "questions_category[]",
					'class="input input-medium small" name="questions_category[]"', "value", "text", '');
				?>

				<span class="small"><?php echo JText::_('COM_TMT_TEST_FORM_LBL_HAVING_D_LEVEL');?></span>
				<?php
					echo JHtml::_('select.genericlist', $this->difficultyLevels, "questions_level[]",
						'class="input input-medium small" name="questions_level[]"', "value", "text", '');
				?>

				<span class="small"><?php echo JText::_('COM_TMT_TEST_FORM_LBL_Q_TYPE');?></span>
				<?php
					echo JHtml::_('select.genericlist', $this->qTypes, "questions_type[]",
						'class="input input-large small" name="questions_type[]"', "value", "text", '');
				?>

				<button type="button" class="btn btn-danger btn-small remove-rule tmt-display-none" onclick="removeClone(this);">
					<i class="icon-trash"></i>
				</button>

				<button type="button" class="btn btn-primary btn-small add-rule" onclick="addRuleClone('rule-template','rules_block');" id="add_answer" title="<?php echo JText::_('COM_TMT_TEST_FORM_RULES_ADD_NEW'); ?>">
					<i class="icon-plus"></i>
				</button>
			</div>
		</div>
	</div>
	<div class="row-fluid">
		<div class="span12 form-actions">

<!--
				<button type="button" class="btn btn-success btn-small add-question" onclick="window.parent.document.getElementById('idIframe_<?php echo $this->unique ?>').contentWindow.fetchQuestions();" id="fetch_questions">
					<i class="icon-thumbs-up"></i>
					<?php echo JText::_('COM_TMT_TEST_FORM_RULES_ADD_QUESTIONS'); ?>
				</button>
-->
				<button type="button" class="btn btn-success btn-small add-question" onclick="fetchQuestions();" id="fetch_questions">
					<i class="icon-thumbs-up"></i>
					<?php echo JText::_('COM_TMT_TEST_FORM_RULES_ADD_QUESTIONS'); ?>
				</button>

		</div><!--span12-->
	</div> <!--row-fluid-->

</div>
<script>
function addRuleClone(newType,appendToClass)
{
	var cloneID = newType;

	var lastId = jQuery('.rule-template:last').attr('id');

	lastId = lastId.replace('rule-template', '');

	var num = parseInt(lastId) + 1;

	var newElem = jQuery('#'+cloneID+lastId).clone().attr('id',cloneID+num);
	/*var addruleHtml = jQuery(newElem).find('.add-rule');*/
	jQuery(newElem).children().find("input").each(function()
	{
		var kid=jQuery(this);
		/* change id to incremental id */
		if(kid.attr('id')!=undefined)
		{
			var idOrig=kid.attr('id'); /* e.g. id-> answers_marks */
			kid.attr('id',idOrig+num).attr('id',idOrig+num); /* e.g. id-> answers_marks2 */
			kid.val('');
		}
	});

	jQuery(newElem).children().find("select").each(function()
	{
		var kid=jQuery(this);
		/* change id to incremental id */
		if(kid.attr('id')!=undefined)
		{
			var idOrig=kid.attr('id'); /* e.g. id-> answers_marks */
			kid.attr('id',idOrig+num).attr('id',idOrig+num); /* e.g. id-> answers_marks2 */
			kid.val('');
		}
	});

	jQuery('.rule-template .remove-rule').show();
	jQuery(newElem).find('.remove-rule').hide();
	jQuery(newElem).find('.add-rule').remove();

	jQuery('.rule-template .add-rule').appendTo(newElem);

	jQuery('.'+appendToClass).append(newElem);

}

function checkrules()
{
	var rules = jQuery(".rules-container");
	var ruleCount = 0;
	var flag = 0;
	jQuery(rules).find(".rule-template").each(function()
	{
		ruleCount++;
		var rule = jQuery(this);
		var ruleOK = 0;
		var invalidField = 0;

		/* Check if at least one text area is selected. */
		jQuery(rule).children().find("input[type^='text']").each(function()
		{
			if(ruleOK === 0)
			{
				var kid = jQuery(this);

				if( kid.val() === '')
				{
					invalidField = 1;
				}

				if( isNaN(kid.val()) )
				{
					invalidField = 1;
					kid.val('');
					kid.focus();
				}

				if( (kid.val() !=='') && (! parseInt(kid.val(),10) > 0 ) )
				{
					invalidField = 1;
					kid.val('');
					kid.focus();
				}

				if( (kid.val() !=='') && (kid.val() < 0) )
				{
					invalidField = 1;
					kid.val('');
					kid.focus();
				}

				if(invalidField === 0)
				{
					ruleOK = 1;
				}
			}
		});

		/* Check if at least one select list is selected. */
		jQuery(rule).children().find("select").each(function()
		{

			if(ruleOK === 0)
			{
				var kid = jQuery(this);

				if( kid.val() === '')
				{
					invalidField = 1;
				}
				else
				{
					invalidField = 0;
				}

				if(invalidField === 0)
				{
					ruleOK = 1;
				}
			}
		});

		if(ruleOK === 0 )
		{
			if(flag !== 1)
			{
				flag = 1;
			}
		}

	});

	return flag;
}

function removeClone(removeBtn)
{
	jQuery(removeBtn).closest('.rule-template').remove();
	//jQuery('hr:last').remove();
}

function fetchQuestions()
{
	var flag = 0;
	jQuery('.rule-template').each(function()
	{
		var rule_template_length = jQuery('.rule-template').length;
		console.log(rule_template_length);
		var questionCount = jQuery(this).find('.questions_count').val();
		var questionMark = jQuery(this).find('.questions_marks').val();

		if (!questionCount || !questionMark)
		{
			if(rule_template_length == 1)
			{
				flag = 1;
			}
		}
	});

	if(flag === 1 )
	{
		var msg_error_html = "<div class='alert alert-error'>"  + Joomla.JText._('COM_TMT_TEST_FORM_INALID_RULES') +"</div>";
		jQuery(".rules-container .alert.alert-error").remove();
		jQuery(".rules-container").prepend(msg_error_html);
		return false;
	}

	var params = jQuery(".rules-container").find("input, select").serializeArray();
	jQuery.ajax({
		url: 'index.php?option=com_tmt&view=test&task=test.fetchQuestions',
		dataType: 'json',
		type: 'POST',
		data: params ,
		success: function (data) {

			if(!data.length)
			{

				var msg_error_html = "<div class='alert alert-error'>"  + Joomla.JText._('COM_TMT_TEST_FORM_MSG_NO_Q_FOUND') +"</div>";
				jQuery(".rules-container .alert.alert-error").remove();
				jQuery(".rules-container").prepend(msg_error_html);

				return;
			}

			jQuery.each(data, function (i, q) {

				var htm='<tr class="plain_quiz_question">';
				htm +='<td class="center"> <input type="checkbox" id="cb'+q.id+'" name="cid[]" value="'+q.id+'" onclick="Joomla.isChecked(this.checked);" style="display: none;" checked> <span class="btn btn-small sortable-handler" id="reorder" title="Reorder question" style="cursor: move;"> <i class="icon-move"> </i> </span> </td>';
				htm +=' <td class="small" > '+q.title+' </td>';
				htm +=' <td class="small"> '+q.category+' </td>';
				htm +=' <td class="small"> '+q.type+' </td>';
				htm +=' <td class="small center" name="td_marks"> '+q.marks+' </td>';
				htm +=' <td> <span class="btn btn-small" id="remove" onclick="removeRow(this);" title="Delete this question from test"><i class="icon-trash"> </i> </span> </td>';
				htm +=' </tr>';


				var question_tr = jQuery(window.parent.document.getElementById("idIframe_<?php echo $this->unique ?>")).contents().find('#marks_tr');

				jQuery(question_tr).before( htm );

				jQuery(window.parent.document.getElementById("idIframe_<?php echo $this->unique ?>")).contents().find('#question_paper').show();

			});

			var c = window.parent.document.getElementById('idIframe_<?php echo $this->unique ?>').contentWindow.fixDuplicates();
			if(c > 0 ){
				jQuery(window.parent.document.getElementById("idIframe_<?php echo $this->unique ?>")).contents().find(".tmt_form_errors .msg").html(Joomla.JText._('COM_TMT_TEST_FORM_MSG_FIX_DUPLI'));
				jQuery(window.parent.document.getElementById("idIframe_<?php echo $this->unique ?>")).contents().find(".tmt_form_errors").show();
			}
			if (typeof window.parent.document.getElementById("idIframe_<?php echo $this->unique ?>").contentWindow.hideDynamicDiv != "undefined")
			{
				window.parent.document.getElementById("idIframe_<?php echo $this->unique ?>").contentWindow.hideDynamicDiv();
			}
			window.parent.document.getElementById('idIframe_<?php echo $this->unique ?>').contentWindow.getTotal();
			window.parent.document.getElementById('idIframe_<?php echo $this->unique ?>').contentWindow.closePopup();
		}
	});
}
</script>
<style>
.tmt-display-none{display:none;}
.rules_block{margin:10px;}
.rule-template .clearfix{display:inline-block;}
</style>
