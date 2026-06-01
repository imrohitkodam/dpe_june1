// Only define the Joomla namespace if not defined.
Joomla = window.Joomla || {};

!(function(document) {	
	window.jQuery && (function($){
		$(document).ready(function () {

		if (jQuery('.tjlms_add_quiz_form #jform_time_duration').val() == 0)
		{
			jQuery('.tjlms_add_quiz_form #jform_time_duration').val('');
		}

		jQuery('.tjlms_add_quiz_form #jform_total_marks').change(function(){
			jQuery('#final_total_marks').text(jQuery('#jform_total_marks').val());
		});

		jQuery('.tjlms_add_quiz_form .quiztitle').blur(function()
		{
			jQuery(this).val(jQuery.trim(jQuery(this).val()));
		});

		jQuery('#jform_time_duration').blur(function()
		{
			var td = jQuery.trim(jQuery("#jform_time_duration").val());
			td = parseInt(td, 10);

			if(!isNaN(td) && td > 0 && document.formvalidator.validate('#jform_time_duration'))
			{
				jQuery('#jform_show_time label,#jform_show_time_finished label').removeClass("disabledradio");
			}
			else
			{
				jQuery('label[for="jform_show_time_finished1"').trigger('click');
				jQuery('#jform_show_time label,#jform_show_time_finished label').addClass("disabledradio");
			}
		});

		/*Added for no_of_attempts validation */
		jQuery('.numberofQuizattempts').blur(function()
		{
			newval = this.value;
			var id = this.id;

			var msg = Joomla.JText._('COM_TJLMS_NO_OF_ATTEMPT_VALIDATION_MSG');
			var msg1;
			var lesson_basic_form	=	jQuery(this).closest('.lesson_basic_form');

			var original_attempt = jQuery('#no_attempts',lesson_basic_form).val();
			var max_attempt = jQuery('#max_attempt',lesson_basic_form).val();
			var form_id        = lesson_basic_form.attr('id').replace('quiz-form_','');

			var lessonform	=	jQuery('.tjlms_add_quiz_form');
			var msg1 = Joomla.JText._('COM_TJLMS_MAX_ATTEMPT_VALIDATION_MSG1').concat(max_attempt) + Joomla.JText._('COM_TJLMS_MAX_ATTEMPT_VALIDATION_MSG2');

			// Check if attempt is less than max_attempts
			if(original_attempt == 0 && newval > 0)
			{
				if (newval)
				{
					if (newval < max_attempt)
					{
						msg1 = Joomla.JText._('COM_TJLMS_MAX_ATTEMPT_VALIDATION_MSG1').concat(max_attempt) + Joomla.JText._('COM_TJLMS_MAX_ATTEMPT_VALIDATION_MSG2');

							jQuery('#system-message-container').html('');
							jQuery(".tmt_form_errors .msg", lessonform).text(msg1);
							jQuery(".tmt_form_errors", lessonform).show();
							jQuery('#'+id,lesson_basic_form).val(original_attempt);
					}
				}
			}
			else
			{
				if ((newval < max_attempt) && newval != 0)
				{
					jQuery('#system-message-container').html('');
					jQuery(".tmt_form_errors .msg", lessonform).text(msg1);
					jQuery(".tmt_form_errors", lessonform).show();
					jQuery('#'+id,lesson_basic_form).val(original_attempt);
					jQuery('#'+id,lesson_basic_form).focus();
				}
			}

			// Check if attempt empty
			if (newval== '' && original_attempt == '')
			{
				jQuery('#'+id,lesson_basic_form).val(0);
			}
		});
		/*Added for no_of_attempts validation */


		$(document).on('keyup','.new_rules input[name="questions_count[]"],.new_rules input[name="pull_questions_count[]"],.new_rules input[name="questions_marks[]"]',function(){
			var val = parseInt(jQuery(this).val(),10);
			if (isNaN(val) || val < 1)
			{
				jQuery(this).val('')
				return false;
			}
			else
			{
				jQuery(this).val(val)
			}
		});

		});

		$(window).on("load", function () {

			/*validation on each tab click*/
			jQuery(".tjlms_add_quiz_form#tmt_test_form .nav-tabs li").click(function(e)
			{
				var linumber  = jQuery(this).index();
				var errorOccured = 0;
				for(var i=0 ; i < linumber ; i++)
				{
					var navli = jQuery(".tjlms_add_quiz_form .nav-tabs li").get( i );
					var thiscontenttab	= jQuery('a',jQuery(navli)).attr('href');
					var functionToCall = thiscontenttab.replace("#", "");
					var check_validation = eval('validate' + functionToCall)();

					if(!check_validation)
					{
						errorOccured = 1;
						quizTestTabsFlow(navli);

						jQuery('.nav-tabs li').removeClass('active');

						jQuery(navli).addClass('active');
						var tabToShow = jQuery('a',navli).attr('href');

						jQuery('.tab-content .tab-pane').removeClass('active');
						jQuery('a[href="'+tabToShow+'"]').closest('li').addClass('active');
						jQuery('.tab-content '+tabToShow+'').addClass('active');
						return false;
						break;
					}
				}

				if(errorOccured == 0)
				{
					quizTestTabsFlow(jQuery(this));
				}
			});

		});

	})(jQuery);

})(document, Joomla);

/*triggered when the save&next, prev buttons are clicked from Quiz view*/
function quizNexttab(btn, view,unique)
{
	var test =  jQuery(window.parent.document.getElementById("idIframe_"+unique)).contents().find('#jform_qztype').val();

	var btnid = jQuery(btn).attr('id');
	var thisli = jQuery('.nav-tabs li.active');
	var thiscontenttab	= jQuery('a',thisli).attr('href');

	var functionToCall = thiscontenttab.replace("#", "");

	if (btnid !== 'button_quiz_prev_tab')
	{
		if(test == 'feedback')
		{
			var check_validation = eval('validate' + functionToCall)(test);
		}
		else
		{
			var check_validation = eval('validate' + functionToCall)();
		}

		if(!check_validation)
		{
			return false;
		}
	}

	if (btnid == 'button_quiz_prev_tab')
	{
		var nextLi = jQuery('.nav-tabs li.active').prev();
	}
	else
	{
		var nextLi = jQuery('.nav-tabs li.active').next();
	}

	/*decide when to show prev next and save and close buttons*/
	quizTestTabsFlow(nextLi);

	jQuery('.nav-tabs li').removeClass('active');

	jQuery(nextLi).addClass('active');
	var tabToShow = jQuery('a',nextLi).attr('href');

	jQuery('.tab-content .tab-pane').removeClass('active');
	jQuery('a[href="'+tabToShow+'"]').closest('li').addClass('active');
	jQuery('.tab-content '+tabToShow+'').addClass('active');
}

function validatedetails()
{
	if (!document.formvalidator.isValid('#details'))
	{
		return false;
	}

	/* Validate time_finished_duration < time_duration */
	if (jQuery('#jform_start_date').val() != '' && jQuery('#jform_end_date').val() != '' )
	{
		if (jQuery('#jform_start_date').val() > jQuery('#jform_end_date').val())
		{
			enqueueSystemMessage(Joomla.JText._('COM_TMT_DATE_ISSUE'), "");
			return false;
		}
	}

	if (jQuery('#jform_end_date').val() != '')
	{
		var selectedDate = jQuery('#jform_end_date').val();
		var today = new Date();
		today.setHours(0, 0, 0, 0);
		quizEndDate = new Date(selectedDate);
		quizEndDate.setHours(0, 0, 0, 0);

		if(quizEndDate < today)
		{
			enqueueSystemMessage(Joomla.JText._('COM_TMT_END_DATE_CANTBE_GRT_TODAY'), "");
			return false;
		}
	}

	return true;
}

function validatetime()
{

	if (!document.formvalidator.isValid('#time'))
	{
		return false;
	}

	/* Validate time_finished_duration < time_duration */
	if ( parseInt(jQuery('#jform_time_finished_duration').val(),10) >= parseInt(jQuery('#jform_time_duration').val(),10) )
	{
		jQuery('#jform_time_finished_duration').focus();
		enqueueSystemMessage(Joomla.JText._('COM_TMT_TEST_FORM_MSG_TIME_FINISHED_DURATION_HIGHER'), "");
		return false;
	}

	return true;
}

function validatescoreAndResult(qztype)
{
	if (!document.formvalidator.isValid('#scoreAndResult'))
	{
		return false;
	}

	if(qztype != 'feedback')
	{
		/* validate total marks */
		if((! parseInt(jQuery('#jform_total_marks').val(),10) > 0) || jQuery('#jform_total_marks').val() < 0)
		{
			jQuery('#jform_total_marks').val('');
			jQuery('#jform_total_marks').focus();
			enqueueSystemMessage(Joomla.JText._('COM_TMT_TEST_FORM_NON_ZERO_VALUE_MARKS'), "");

			return false;
		}

		/* validate passing marks */
		if ((!parseInt(jQuery('#jform_passing_marks').val(),10) > 0) || jQuery('#jform_passing_marks').val() < 0)
		{
			jQuery('#jform_passing_marks').val('');
			jQuery('#jform_passing_marks').focus();
			enqueueSystemMessage(Joomla.JText._('COM_TMT_TEST_FORM_NON_ZERO_VALUE_MARKS'), "");
			return false;
		}

		/* Validate min marks < total */
		if ( parseInt(jQuery('#jform_passing_marks').val(),10) > parseInt(jQuery('#jform_total_marks').val(),10) )
		{

			jQuery('#jform_passing_marks').focus();
			enqueueSystemMessage(Joomla.JText._('COM_TMT_TEST_FORM_MSG_MIN_MARKS_HIGHER'), "");
			return false;
		}
	}

	// If checkbox is not checked allow regular quiz to go to the next tab.

	if(jQuery("#jform_quiz_type").prop("checked") !== true)
	{
		jQuery('.add-question-bar').show();
		jQuery('.rand_questions').remove();

		if(jQuery(".ui-sortable tr").length <2)
		{
			jQuery('.question_paper').css("display","none");
		}
		jQuery(".question_paper .alert.alert-info").remove();

		return true;
	}
	else
	{
		jQuery('#questions_btns').hide();
	}

	return true;
}

function validatequestions(unique)
{
	if (document.formvalidator.isValid(document.id('adminForm')) )
	{
		var c = fixDuplicates();

		if(c > 0 )
		{
			enqueueSystemMessage(Joomla.JText._('COM_TMT_TEST_FORM_MSG_FIX_DUPLI'), "" , true);
			return false;
		}

		if( parseInt(jQuery('#total-marks-content').text(),10) === 0)
		{
			/* if sum does not match total marks for questions */
			jQuery('#jform_total_marks').focus();
			enqueueSystemMessage(Joomla.JText._('COM_TMT_TEST_FORM_MSG_ADD_Q'), "", true);
			return false;
		}

		// This validation for set based quiz(i.e if checkbox checked)
		var temp;
		temp = jQuery('#jform_quiz_type').attr('checked');

		if(temp == 'checked')
		{
			checkRules();

			var perfect_rules = jQuery('#perfectrules').val();
			var not_valid_for_set = jQuery('#readytosaverules').val();
			var not_valid_for_quiz = jQuery('#invalidrules').val();

			if (not_valid_for_quiz > 0)
			{
				enqueueSystemMessage(Joomla.JText._('COM_TMT_TEST_DYNAMIC_RULE_INSUFFICIENT'), "", true);
				return false;
			}

			/*if (not_valid_for_set > 0)
			{
				var allow_less = jQuery('#allow_set_less_questions').attr('checked');
				if (allow_less != "checked")
				{
					enqueueSystemMessage(Joomla.JText._('COM_TMT_TEST_DYNAMIC_RULE_SUFFICIENT_FOR_SET'), "");

					return false;
				}
			}*/

			var set_marks = jQuery('#single-set-marks').text();

			if (parseInt(set_marks,10) !== parseInt(jQuery('#jform_total_marks').val(),10))
			{
				enqueueSystemMessage(Joomla.JText._('COM_TMT_TEST_DYNAMIC_MISMATCH_SET_MARKS'), "", true);
				return false;
			}
		}

		if ((temp == 'checked' && !isValidDynamicTotal()) || (temp != 'checked' && parseInt(jQuery('#total-marks-content').text(),10) != jQuery('#jform_total_marks').val()))
		{
			/* if sum does not match total marks for questions */
			jQuery('#jform_total_marks').focus();
			enqueueSystemMessage(Joomla.JText._('COM_TMT_TEST_FORM_MSG_MARKS_MISMATCH'), "", true);
			return false;
		}
		else
		{
			var marks_msg = Joomla.JText._('COM_TMT_TEST_FORM_MSG_MARKS_MISMATCH');
			if (jQuery(".msg").text() ==  marks_msg )
			{
				jQuery(".tmt_form_errors").hide();
			}
		}

		return true;
	}

	return false;
}
/*Check if dynamic questions have valid marks*/
function isValidDynamicTotal(){
	var totalMarks = 0;
	jQuery('#questions .set-quiz-rule .rule-template:not(:first-child)').each(function(index,elem){
		var questions = jQuery(elem).find("input[name='questions_count[]']").val();
		var marks = jQuery(elem).find("input[name='questions_marks[]']").val();
		totalMarks = totalMarks + (parseInt(questions, 10) * parseInt(marks,10));
	});
	jQuery('#questions .rule-template-edit').each(function(index,elem){
		var questions = jQuery(elem).find("input[name='edit_q_count[]']").val();
		var marks = jQuery(elem).find("input[name='edit_m_count[]']").val();
		totalMarks = totalMarks + (parseInt(questions, 10) * parseInt(marks,10));
	})
	var totalReqMarks = jQuery('#jform_total_marks').val();
		totalReqMarks = parseInt(totalReqMarks, 10);
	if (totalReqMarks != totalMarks)
	{
		return false;
	}
	else
	{
		return true;
	}
}
/*triggered when the save&next, prev buttons are clicked from Question view*/
/*function questionNexttab(btn)
{
	var btnid = jQuery(btn).attr('id');
	var thisli = jQuery('.nav-tabs li.active');
	var thiscontenttab	= jQuery('a',thisli).attr('href');

	var functionToCall = thiscontenttab.replace("#", "");
	var check_validation = eval('validateQuestion' + functionToCall)();
	if(!check_validation)
	{
		return false;
	}

	if (btnid == 'button_quiz_prev_tab')
	{
		var nextLi = jQuery('.nav-tabs li.active').prev();
	}
	else
	{
		var nextLi = jQuery('.nav-tabs li.active').next();
	}

	quizQuesTabsFlow(nextLi);

	jQuery('.nav-tabs li').removeClass('active');

	jQuery(nextLi).addClass('active');
	var tabToShow = jQuery('a',nextLi).attr('href');

	jQuery('.tab-content .tab-pane').removeClass('active');
	jQuery('a[href="'+tabToShow+'"]').closest('li').addClass('active');
	jQuery('.tab-content '+tabToShow+'').addClass('active');
}*/

function validateQuestionquestion()
{
	if (!document.formvalidator.isValid('#question'))
	{
		return false;
	}

	return true;
}

function validateQuestionanswers()
{

	if(!document.formvalidator.isValid('#answers'))
	{
		return false;
	}

	return true;
}

function questionactionsAjax(action,reload,section_id)
{
	var qztype = jQuery('#qzType').val();
	var qtype = jQuery('#quetype').val();

	if (action == 'question.cancel')
	{
		/* Disable all buttons. */
		jQuery('#tmt_question_form .btn').prop('disabled', true);
		Joomla.submitform(action);
		return 1;
	}

	var quizform	= jQuery('#adminForm');
	jQuery('#task', quizform).val(action);

	if(document.formvalidator.isValid(quizform))
	{
		var boolenflag = findDuplicatesAnswers();

		if(boolenflag == false)
		{
			return false;
		}

		if(! (qtype=='text' || qtype=='textarea' || qtype=='file_upload' || qtype =='rating') )
		{
			var checkedFlag=0;

			/* Check if at least 1 radio and checkbox answer option is selected. */
			if( qtype=='radio' || qtype=='checkbox' )
			{
				jQuery(".answers-container .answer-template").each(function() {

					if( jQuery(this).find('.answers_iscorrect').is(":checked") )
					{
						checkedFlag=1;
					}
					else
					{
						jQuery(this).find('.answers_marks').val('0');
					}
				});
				if(qztype =='feedback')
				{
					checkedFlag=1
				}
			}

			/* Check if at least 1 answer option is selected. */
			if( checkedFlag === 0 )
			{
				enqueueSystemMessage(Joomla.JText._('COM_TMT_Q_FORM_NO_CORRECT_ANSWER'), "");
				return false;
			}

			/* Check if sum does not match total marks for questions. */
			if( jQuery('#jform_marks').val() )
			{
				if( jQuery('#total-marks-content').html() != jQuery('#jform_marks').val())
				{
					enqueueSystemMessage(Joomla.JText._('COM_TMT_Q_FORM_MARKS_MISMATCH'), "");
					return false;
				}
			}
		}

		/* Disable all buttons. */
		jQuery('#tmt_question_form .btn').prop('disabled', true);

		jQuery(quizform).ajaxSubmit({
			datatype:'json',
			 beforeSend: function() {
				//return false;
			},
			success: function(data)
			{
				var res = jQuery.parseJSON(data);
				showQuestionsOnParentForm(res.output.id, res.output.title, res.output.category, res.output.type, res.output.marks,section_id)

				jQuery('#questions_container .thead',window.parent.document).show();
				jQuery("#questions_container .tbody .clone",window.parent.document).addClass('question_row');
			//	jQuery('#marks_tr',window.parent.document).removeClass('question_row');
				jQuery('#questions_block .row-fluid',window.parent.document).first().show();
				jQuery('#total_marks',window.parent.document).show();

				if(qztype == 'quiz')
				{
					jQuery('#marks_tr',window.parent.document).show();
				}
				else
				{
					jQuery('#marks_tr',window.parent.document).hide();
				}
				if(reload == 1)
				{
					window.location.reload();
				}
				if(reload == 2)
				{
					window.parent.SqueezeBox.close();
				}
			}
		});
	}
}

function showQuestionsOnParentForm(qid, qtitle, qcategory, qtype, qmarks,section_id)
{
	var row_count = jQuery("window.parent.questions_container tr:not(#marks_tr)").length;

	var new_id  = row_count++;

	var newElem = jQuery(window.parent.tobeCloned).clone();
	jQuery(newElem).attr('id','');
	jQuery(".reorder input[type*='checkbox']", newElem).attr('id', 'cb'+new_id).attr('name','lesson_format[quiz][cid][]').val(qid);
	jQuery(".question_remove input[name='sid[]']", newElem).attr('name','lesson_format[quiz][sid][]').val(section_id);
	jQuery(".question_title", newElem).text(qtitle);
	jQuery(".question_cat", newElem).text(qcategory);
	jQuery(".question_type", newElem).text(qtype);
	jQuery(".question_marks", newElem).text(qmarks);
	jQuery("#sectionlist_"+section_id+" .question_paper" ,window.parent.document).append(newElem);
	jQuery(newElem).show();
	window.parent.jQuery('.question_paper').show();

	window.parent.tjform.getTotal();
	jQuery(".hero-unit",window.parent.document).hide();
}

function closePopupForm()
{
	var c = tjform.fixDuplicatesForm();

	if(c > 0 )
	{
		window.parent.jQuery(".tmt_form_errors .msg").html(Joomla.JText._('COM_TMT_QUIZ_DUPLICATE_QUESTIONS'));
		window.parent.jQuery(".tmt_form_errors").show();
		window.parent.jQuery("#system-message-container").hide();
	}

	tjform.	getTotal();
	parent.SqueezeBox.close();
}

function fixDuplicatesForm()
{
	var allQuestions=[];
	jQuery("#quiz-sections",window.parent.document).find("input[type*='checkbox']").each(function(cbs,cb) {
		allQuestions.push( jQuery(cb).attr('value') );
		allQuestions=allQuestions.getUnique();
	});

	var duplicatedFound=0;
	jQuery.each(allQuestions, function (qst, q) {
		jQuery("#quiz-sections",window.parent.document).find("input[value='"+q+"']").each(function(cbs,cb) {
			jQuery(cb).parent().parent().removeClass('error');
			if(cbs > 0 )
			{
				jQuery(cb).parent().parent().addClass('error');
				duplicatedFound++;
			}
			else
			{
				jQuery(".tmt_form_errors").hide();
			}
		});
	});
	return duplicatedFound;
}


function fixDuplicates()
{
	var allQuestions=[];
	jQuery('#questions_container').find("input[name='lesson_format[quiz][cid][]']").each(function(cbs,cb) {
		allQuestions.push( jQuery(cb).attr('value') );
		allQuestions=allQuestions.getUnique();
	});

	var duplicatedFound=0;
	jQuery.each(allQuestions, function (qst, q) {
		console.log(q);
		jQuery('#questions_container').find("input[value='"+q+"']").each(function(cbs,cb) {
			jQuery(cb).parent().parent().removeClass('error');
			if(cbs > 0 )
			{
				jQuery(cb).parent().parent().addClass('error');
				duplicatedFound++;
			}
			else
			{
				jQuery(".tmt_form_errors").hide();
			}
		});
	});
	return duplicatedFound;
}

function questionactions(action)
{
	var qztype = jQuery('#qzType').val();
	var qtype = jQuery('#quetype').val();

	if (action == 'question.cancel')
	{
		/* Disable all buttons. */
		jQuery('#tmt_question_form .btn').prop('disabled', true);
		Joomla.submitform(action);
		return 1;
	}

	var quizform	= jQuery('#adminForm');

	if(document.formvalidator.isValid(quizform))
	{
		var boolenflag = findDuplicatesAnswers();

		if(boolenflag == false)
		{
			return false;
		}

		if(! (qtype=='text' || qtype=='textarea' || qtype=='file_upload' || qtype =='rating') )
		{
			var checkedFlag=0;

			/* Check if at least 1 radio and checkbox answer option is selected. */
			if( qtype=='radio' || qtype=='checkbox' )
			{
				jQuery(".answers-container .answer-template").each(function() {

					if( jQuery(this).find('.answers_iscorrect').is(":checked") )
					{
						checkedFlag=1;
					}
					else
					{
						jQuery(this).find('.answers_marks').val('0');
					}
				});
				if(qztype =='feedback')
				{
					checkedFlag=1
				}
			}

			/* Check if at least 1 answer option is selected. */
			if( checkedFlag === 0 )
			{
				enqueueSystemMessage(Joomla.JText._('COM_TMT_Q_FORM_NO_CORRECT_ANSWER'), "");
				return false;
			}

			/* Check if sum does not match total marks for questions. */
			if( jQuery('#jform_marks').val() )
			{
				if( jQuery('#total-marks-content').html() != jQuery('#jform_marks').val())
				{
					enqueueSystemMessage(Joomla.JText._('COM_TMT_Q_FORM_MARKS_MISMATCH'), "");
					return false;
				}
			}
		}
		else if (qtype =='rating')
		{
			if(jQuery('.lower_range').val() > jQuery('.upper_range').val())
			{
				enqueueSystemMessage(Joomla.JText._('COM_TMT_Q_RATING_UPPER_LOWER_RANGE'), "");
				return false;
			}
		}

		/* Disable all buttons. */
		jQuery('#tmt_question_form .btn').prop('disabled', true);
		Joomla.submitform(action);
	}
	else
	{
		return false;
	}
}

function findDuplicatesAnswers()
{
	var dup = [];

	var isDuplicate = false;

	var  k = 0;

	jQuery(".answers_text").each(function (i,el1)
	{
		var current_value = jQuery(el1).val();


		var current_val = jQuery.trim(current_value);
		el1.value = current_val;

		if (current_val != "") {
			dup[k++] = current_val;
		}

	});

   for (var i = 0; i < dup.length; i++)
	{
		for (var j = 0; j < dup.length; j++)
		{
			if (i != j)
			{
				if (dup[i] == dup[j])
				{
					// Means there are duplicate answers found
					enqueueSystemMessage(Joomla.JText._('COM_TMT_Q_DUPLICATE_ANS'), "");
					return false;

				}
			}
		}
	}

	return true;
}


/*
function isCorrect(ele)
{
	var marksEle=jQuery(ele).closest('.answer-template').find("input.answers_marks");
	var isCorrectHiddenEle=jQuery(ele).closest('.answer-template').find("input.answers_iscorrect_hidden");
	var qztype = jQuery('#qzType').val();
	var qtype = jQuery('#quetype').val();

	var qtype = '';

	if (jQuery('.answers_iscorrect').is(':radio')) {
		qtype = "radio";
	}
	else if(jQuery('.answers_iscorrect').is(':checkbox'))
	{
		qtype = "checkbox";
	}

	if(qtype=='radio')
	{
		jQuery("input[id*='answers_iscorrect_hidden']").each(function() {
			jQuery(this).val(0);
		});
		jQuery("input[id*='answers_marks']").filter(':visible').each(function() {
			jQuery(this).val(0);
		});
	}

	if( jQuery(ele).is(":checked") )
	{
		if(qtype=='radio')
		{
			isCorrectHiddenEle.val(1);
			correctMark = jQuery('#jform_marks').val();
			marksEle.val(correctMark);
			marksEle.focus();
		}

		if(qtype=='checkbox')
		{
			isCorrectHiddenEle.val(1);
			if (qztype == 'feedback' || qztype == 'exercise'){
				marksEle.val(0);
			}else{
				marksEle.val('');
				}
			marksEle.focus();
		}
	}
	else
	{
		isCorrectHiddenEle.val(0);
		marksEle.val('0');
		marksEle.focus();
	}
}*/

/* Add clone script. */
function addAnswerClone(qtype,appendToClass)
{

	qtype = jQuery("#jform_type").val();

	if (qtype == 'radio')
	{
		qtype = 'checkbox';
	}

	var cloneID='answer-template-'+qtype;
	var cloneClass='answer-template-'+qtype;
	var num=jQuery('.'+cloneClass).length;
	var newElem=jQuery('#'+cloneID).clone().attr('id',cloneID+num);

	jQuery(newElem).find('*').each(function()
	{
		var kid=jQuery(this);

		/* Change id to incremental id. */
		if(kid.attr('id')!=undefined)
		{
			var idOrig=kid.attr('id');  /* e.g. id-> answers_marks */
			kid.attr('id',idOrig+num).attr('id',idOrig+num); /* e.g. id-> answers_marks2 */
		}

		/* Set default marks-> 0 for each new option. */
		if(kid.attr('name')=='answers_marks[]')
		{
			kid.attr('value',0);
		}

		/* Set LABEL for colned inpputs. */
		if(kid.prop('nodeName')=='LABEL')
		{

			if(kid.attr('for')!=undefined)
			{
				var forNew=kid.attr('for');
				kid.attr('for',forNew+num).attr('for',forNew+num);
			}
		}
	});

	/* Append new cloned element after last element in container element. */
	/*jQuery('.'+appendToClass+' :last').append(newElem);*/
	jQuery('.'+appendToClass).append(newElem);

	/** focus on answer text box after adding new box */
	jQuery("#answers_text"+num).focus();

	jQuery("#answers_text"+num).addClass('option-value');
}

/* Function to remove cloned div. */
function removeAnswerClone(ele)
{
	var deletmsg = Joomla.JText._('COM_TMT_QUESTION_ANSWER_OPTION_DELETE_CONFIRMATION_MSG');
	var successmsg = Joomla.JText._('COM_TMT_QUESTION_ANSWER_OPTION_DELETE_SUCCESSFULLY_MSG');
	var comfirmDelete = confirm(deletmsg);

	if(comfirmDelete == true)
	{
		jQuery(ele).closest(".answer-template").remove();
		alert(successmsg);
		/*getTotalMarks();*/
	}
}

/* Change Quiz type */
function changeQzType(newType)
{
	qztype = newType;

	if(qztype == 'feedback' || qztype == 'exercise')
	{
		if(qztype == 'feedback')
		{
			jQuery('#jform_passing_marks-lbl').hide();
			jQuery('#jform_passing_marks').hide();

			jQuery('#jform_total_marks-lbl').hide();
			jQuery('#jform_total_marks').hide();

			jQuery('#jform_total_marks').removeClass('required');
			jQuery('#jform_total_marks').removeAttr('required');

			jQuery('#jform_passing_marks').removeClass('required');
			jQuery('#jform_passing_marks').removeAttr('required');
		}
		else
		{
			jQuery('#jform_passing_marks-lbl').show();
			jQuery('#jform_passing_marks').show();

			jQuery('#jform_total_marks-lbl').show();
			jQuery('#jform_total_marks').show();

			jQuery('#jform_total_marks').addClass('required');
			jQuery('#jform_total_marks').attr('required', true);

			jQuery('#jform_passing_marks').addClass('required');
			jQuery('#jform_passing_marks').attr('required', true);
		}
		jQuery('#qmarks').hide();
		jQuery('#marks_tr').hide();
	}
	else
	{
		jQuery('#marks_tr').show();
		jQuery('#qmarks').show();
	}
}


/* Change question type. */
/*function changeQType(newType)
{
	jQuery('#quetype').val(newType);
	qtype=newType;
	var qztype = jQuery('#qzType').val();
	if(qtype == 'rating')
	{
		jQuery('#jform_qztype option[value=quiz]').hide();
	}
	else
	{
		jQuery('#jform_qztype option[value=quiz]').show();
	}

	changeQnQztype(qztype, qtype);

	jQuery('.answers-container').html('');

	if (qtype == '')
	{
		jQuery('#ans-div').html('');
	}

	addAnswerClone(qtype,appendToClass);
}*/

function enqueueSystemMessage(message, parentDiv, moveToError)
{
	if(parentDiv == 'contentpane')
	{
		parentDiv = '';
		jQuery(parentDiv + " #system-message-container").empty();
		jQuery(parentDiv + " #system-message-container").append("<div class='alert alert-success'><p>"+message+"</p></div>");
	}
	else
	{
		jQuery(parentDiv + " #system-message-container").empty();
		jQuery(parentDiv + " #system-message-container").append("<div class='alert alert-error'><p>"+message+"</p></div>");
		if (moveToError)
		{
			jQuery(window).scrollTop(jQuery('#system-message-container').offset().top);
			var unique = jQuery('input[name="unique"]').val();
			if (typeof window.parent.document != 'undefined' && parseInt(unique,10) && jQuery(window.parent.document).find('#idIframe_' + unique).length)
			{
				jQuery(window.parent).scrollTop(jQuery(window.parent.document).find('#idIframe_' + unique).offset().top - 75);
			}
		}
	}
}

function fixDuplicates()
{
	var allQuestions=[];
	jQuery('#questions_container').find("input[type*='checkbox']").each(function(cbs,cb) {
		allQuestions.push( jQuery(cb).attr('value') );
		allQuestions=allQuestions.getUnique();
	});

	var duplicatedFound=0;
	jQuery.each(allQuestions, function (qst, q) {
		jQuery('#questions_container').find("input[value='"+q+"']").each(function(cbs,cb) {
			jQuery(cb).parent().parent().removeClass('error');
			if(cbs > 0 )
			{
				jQuery(cb).parent().parent().addClass('error');
				duplicatedFound++;
			}
			else
			{
				jQuery(".tmt_form_errors").hide();
			}
		});
	});
	return duplicatedFound;
}

/* Define a new array method that return a unique array for given array.
**/
Array.prototype.getUnique = function () {
	var a = [],
		o = {},
		i, e;
	for (i = 0; e = this[i]; i++) {
		o[e] = 1
	};
	for (e in o) {
		a.push(e)
	};
	return a;
}


/*function quizactions(thiselement,action,mod_id,unique)
{
	jQuery('#button_save_and_close').addClass('inactivelink');

	var quizform	= jQuery('#quiz-form_'+mod_id);
	if(document.formvalidator.isValid(quizform)){
		jQuery(quizform).ajaxSubmit({
			datatype:'json',
			 beforeSend: function()
			 {
				if(validatequestions(unique) == false)
				{
					jQuery('#button_save_and_close').removeClass('inactivelink');
					return false;
				}
			},
			success: function(data)
			{
				var response = jQuery.parseJSON(data)
				var output	=	response.OUTPUT;
				var res	=	output[0];
				var msg	=	output[1];
				var s_msg	=	output[2];

				if(res == 1)
				{
					alert(s_msg);
					parent.location.reload();
				}
			},
			complete: function(xhr) {

			}
		});
	}
	else
	{
		return false;
	}

		// always return false to prevent standard browser submit and page navigation
		return false;
}*/


function getTotal()
{
	sum=0;
	jQuery("td[name^='td_marks']").each(function() {
		var ele=jQuery(this);
		if( isNaN(jQuery(ele).text()) ) /* if not number, enter 0 */
		{
			/* Do nothing*/
		}
		else
		{
			if( jQuery(ele).text() ) /* if not empty */
			{
				sum +=parseInt(jQuery(ele).text(),10); /* 10 is for decimal system*/
			}
		}
	});
	jQuery('#total-marks-content').html(sum);
}

function loadalerttimeshow()
{
	if(jQuery('#jform_show_time_finished0').attr("checked")=="checked")
	{
		jQuery('#show_duration').show();
	}
	else
	{
		jQuery('#show_duration').hide();
	}
}
function alerttimetoggle()
{
	if (jQuery("#jform_show_time_finished").find(":checked").val() == 1)
	{
		jQuery('#show_duration').show();
		jQuery('#jform_time_finished_duration').attr('required','required').attr('aria-required','true').addClass('required');
	}
	else
	{
		//Remove the required attribute
		jQuery('#jform_time_finished_duration').removeAttr('required').removeAttr('aria-required').removeClass('required');
		jQuery('#jform_time_finished_duration').val('');
		jQuery('#show_duration').hide();
	}
}
function alertdurationtoggle()
{
	if (jQuery("#jform_show_time").find(":checked").val() == 1)
	{
		jQuery('#show_time_duration').show();
		jQuery('#jform_time_duration').attr('required','required').attr('aria-required','true').addClass('required');
	}
	else
	{
		//Remove the required attribute
		jQuery('#jform_time_duration').removeAttr('required').removeAttr('aria-required').removeClass('required');
		jQuery('#show_time_duration').hide();
	}
}
//~ function quizNexttab(btn, view)
//~ {
	//~ var res;
//~
	//~ /* Get which action to do */
	//~ var btnid = jQuery(btn).attr('id');
//~
//~
	//~ var thisli = jQuery('.nav-tabs li.active');
	//~ var thiscontenttab	= jQuery('a',thisli).attr('href');
//~
	//~ if (btnid == 'button_quiz_prev_tab')
	//~ {
		//~ res = timeLimitvalidation();
		//~ var nextLi = jQuery('.nav-tabs li.active').prev();
	//~ }
	//~ else
	//~ {
		//~ res = tab1validation();
		//~ var nextLi = jQuery('.nav-tabs li.active').next();
	//~ }
//~
	//~ if (view == 'question')
	//~ {
		//~ if (res == 1)
		//~ {
			//~ return false;
		//~ }
//~
		//~ quizQuesTabsFlow(nextLi);
	//~ }
	//~ else if (view == 'test')
	//~ {
		//~ quizTestTabsFlow(nextLi);
	//~ }
//~
	//~ jQuery('.nav-tabs li').removeClass('active');
//~
	//~ jQuery(nextLi).addClass('active');
	//~ var tabToShow = jQuery('a',nextLi).attr('href');
//~
	//~ jQuery('.tab-content .tab-pane').removeClass('active');
	//~ jQuery('a[href="'+tabToShow+'"]').closest('li').addClass('active');
	//~ jQuery('.tab-content '+tabToShow+'').addClass('active');
//~ }

/*function quizTestTabsFlow(tabs)
{
	jQuery('#button_quiz_prev_tab').show();
	jQuery('#button_quiz_next_tab').show();
	jQuery('#button_save_and_close').hide();

	if (jQuery(tabs).is(':first-child'))
	{
		jQuery('#button_quiz_prev_tab').hide();
	}

	if (jQuery(tabs).is(':last-child'))
	{
		jQuery('#button_save_and_close').show();
		jQuery('#button_quiz_next_tab').hide();
	}
}

function quizQuesTabsFlow(tabs)
{
	jQuery('#button_quiz_prev_tab').show();
	jQuery('#button_quiz_next_tab').show();
	jQuery('#button_save_and_close').hide();

	if (jQuery('#button_save'))
	{
		jQuery('#button_save').hide();
	}

	if (jQuery('#button_save_and_new'))
	{
		jQuery('#button_save_and_new').hide();
	}

	if (jQuery(tabs).is(':first-child'))
	{
		jQuery('#button_quiz_prev_tab').hide();
	}

	if (jQuery(tabs).is(':last-child'))
	{
		jQuery('#button_quiz_next_tab').hide();
		jQuery('#button_save_and_close').show();

		if (jQuery('#button_save'))
		{
			jQuery('#button_save').show();
		}

		if (jQuery('#button_save_and_new'))
		{
			jQuery('#button_save_and_new').show();
		}
	}
}*/


function checkfordate(key)
{
	var time_date_value_validation_msg = Joomla.JText._('COM_TMT_DATE_TIME_VALIDATION');

	var i = 0 ;

	for(i=0;i<key.value.length;i++)
	{
		if((key.value.charCodeAt(i) < 48 || key.value.charCodeAt(i) > 58) && (key.value.charCodeAt(i) != 45) && (key.value.charCodeAt(i) != 32) && (key.value.charCodeAt(i) != 8))
		{
			jQuery(".tmt_form_errors .msg").html(time_date_value_validation_msg);
			jQuery(".tmt_form_errors").show();
			key.value = key.value.substring(0,i);
			//break;
		}
	}

	return true;
}

function opentmtSqueezeBoxForm(tmtlink,unique,section_id)
{
	if(section_id)
	{
		tmtlink = tmtlink + '&section_id=' + section_id;
	}
	var width = jQuery(parent.window).width();
	var height = jQuery(parent.window).height();
	//~ if(dynamic)
	//~ {
		//~ var tmplnum = jQuery(dynamic).parents('div.rule-template').attr('data-tmplnum');
		//~ if(tmplnum)
		//~ {
			//~ tmtlink = tmtlink + '&fordynamic=' + tmplnum;
			//~ var marks = jQuery(dynamic).parents('div.rule-template').find('input[name="questions_marks[]"]').val();
			//~ tmtlink = (marks ? (tmtlink + '&fdata[marks]=' + marks):tmtlink);
			//~ var cat = jQuery(dynamic).parents('div.rule-template').find('select[name="questions_category[]"]').val();
			//~ tmtlink = (cat ? (tmtlink + '&fdata[category_id]=' + cat):tmtlink);
			//~ var level = jQuery(dynamic).parents('div.rule-template').find('select[name="questions_level[]"]').val();
			//~ tmtlink = (level ? (tmtlink + '&fdata[level]=' + level):tmtlink);
			//~ var type = jQuery(dynamic).parents('div.rule-template').find('select[name="questions_type[]"]').val();
			//~ tmtlink = (type ? (tmtlink + '&fdata[type]=' + type):tmtlink);
		//~ }
	//~ }
	var wwidth = width-(width*0.10);
	var hheight = height-(height*0.10);
	SqueezeBox.open(tmtlink, { handler: 'iframe', size: {x: wwidth, y: hheight},classWindow: 'tjlms-modal'});
}

/*function createLinkforQzType(tmtlink,unique)
{
	var test = jQuery(window.parent.document.getElementById("idIframe_"+unique)).contents().find('#jform_qztype').val();

	var tmtlink = tmtlink + '&qztype=' + test;
	opentmtSqueezeBox(tmtlink);
}*/

function opentmtSqueezeBox(tmtlink,dynamic)
{
	var width = jQuery(parent.window).width();
	var height = jQuery(parent.window).height();
	if(dynamic)
	{
		var tmplnum = jQuery(dynamic).parents('div.rule-template').attr('data-tmplnum');
		if(tmplnum)
		{
			tmtlink = tmtlink + '&fordynamic=' + tmplnum;
			var marks = jQuery(dynamic).parents('div.rule-template').find('input[name="questions_marks[]"]').val();
			tmtlink = (marks ? (tmtlink + '&fdata[marks]=' + marks):tmtlink);
			var cat = jQuery(dynamic).parents('div.rule-template').find('select[name="questions_category[]"]').val();
			tmtlink = (cat ? (tmtlink + '&fdata[category_id]=' + cat):tmtlink);
			var level = jQuery(dynamic).parents('div.rule-template').find('select[name="questions_level[]"]').val();
			tmtlink = (level ? (tmtlink + '&fdata[level]=' + level):tmtlink);
			var type = jQuery(dynamic).parents('div.rule-template').find('select[name="questions_type[]"]').val();
			tmtlink = (type ? (tmtlink + '&fdata[type]=' + type):tmtlink);
		}
	}
	var wwidth = width-(width*0.10);
	var hheight = height-(height*0.10);
	SqueezeBox.open(tmtlink, { handler: 'iframe', size: {x: wwidth, y: hheight},classWindow: 'tjlms-modal'});
}


function closePopup()
{
	//SqueezeBox.close();
	parent.SqueezeBox.close();
	var c=fixDuplicates();

	if(c > 0 )
	{
		jQuery(".tmt_form_errors .msg").html(Joomla.JText._('COM_TMT_QUIZ_DUPLICATE_QUESTIONS'));
		jQuery(".tmt_form_errors").show();
	}
	getTotal();
}

/*function updateTestQuestions(ele)
{
	var section_id = jQuery(ele).closest('div').find('input[name="section_id[quiz][sid][]"]').val();
	var question_id = jQuery(ele).closest('div').find('input[name="question_id[quiz][cid][]"]').val();
	var required_value = jQuery("#required"+question_id).is(':checked');
	var required_checkbox_value = required_value ? 1 : 0;
	jQuery.ajax({
					url: "index.php?option=com_tmt&task=test.updateTestQuestions&question_id="+question_id+"&section_id="+section_id+"&required_value="+required_checkbox_value,
					type: "GET",
					dataType: "json",
					success: function(msg)
					{
						if(msg == 1)
						{

						}
						else
						{
							return false;
						}
					}
				});
}*/

function removeRow(ele)
{
	if (confirm(Joomla.JText._('COM_TMT_QUESTION_DELETE_ALERT')) == false)
	{
		return false;
	}

	var section_id = jQuery(ele).closest('div').find('input[name="lesson_format[quiz][sid][]"]').val();
	var question_id = jQuery(ele).closest('.question_row').find('input[name="lesson_format[quiz][cid][]"]').val();

	jQuery.ajax({
					url: "index.php?option=com_tmt&task=test.deleteTestQuestion&question_id="+question_id+"&section_id="+section_id,
					type: "GET",
					dataType: "json",
					success: function(msg)
					{
						if(msg == 1)
						{
							var questionCount = jQuery('#questions_container tbody tr').length;

							if(questionCount <=2)
							{
								jQuery("#question_paper").hide();
							}

							var test = jQuery('#jform_qztype').val();
							jQuery(ele).parents('.question_layout').remove();
							tjform.fixDuplicatesForm();
							tjform.getTotal();

							var total_sum = parseInt( jQuery('#total-marks-content').html());
							var total_marks = jQuery('#jform_total_marks').val();
							if(test == 'quiz')
							{
								if (jQuery(".tmt_form_errors").css('display') == 'none')
								{
									if (total_sum != total_marks)
									{
										enqueueSystemMessage(Joomla.JText._('COM_TMT_TEST_FORM_MSG_MARKS_MISMATCH'), "");
										return false;

									}
									else
									{
										jQuery(".tmt_form_errors").hide();
									}
								}
							}
						}
						else
						return false;
					}

				});


}

function showRules()
{
	jQuery('#rules_block').css('display', 'block');
	addClone('rule-template','rules-container');
/*
	jQuery('#rules_block').removeClass('span1');
	jQuery('#rules_block').addClass('span4');

	jQuery('#questions_block').removeClass('span11');
	jQuery('#questions_block').addClass('span8');
*/
	hideFetchQuestions();
}

function loadautoQuestion (unqiue)
{
	var width = jQuery(window).width();
	var height = jQuery(window).height();

	// ADDED FOR DOUBLE QUERY BOX QUERY SECOND TIME
	var flag = 1;

	//	window.parent.SqueezeBox.setContent('clone', jQuery('#autoQuestionModal'));
	/*window.parent.SqueezeBox.open(window.parent.document.getElementById("idIframe_"+unqiue).contentWindow.autoQuestionModal, {
		handler: 'clone',
		onOpen: function(){ if (flag == 1) { window.parent.document.getElementById("idIframe_"+unqiue).contentWindow.addClone('rule-template','sbox-content-clone .rules-container'); flag = 2; } },
		size: {x: (width-(width*0.15)), y : (height-(height*0.45)) }
	});*/

	window.parent.SqueezeBox.open(jQuery('rule-template'), {
		handler: 'html',
		size: {x: (width-(width*0.15)), y : (height-(height*0.45)) }
	});
}


function closebackendPopup(donotload)
{
	if (donotload == '1')
	{
		window.parent.Joomla.Modal.getCurrent().close();
	}
	else
	{
	window.parent.location.reload();
	}
}

function getCntNeededForPull(cntbox,multiplicationFactor)
{
	var qcnt = jQuery(cntbox).val();
	var pull_qcnt = jQuery(cntbox).closest('.rule-template').find('.pull_questions_count').val();

	if (pull_qcnt !==0 )
	{
		pull_qcnt = qcnt * multiplicationFactor;
		jQuery(cntbox).closest('.rule-template').find('.pull_questions_count').val(pull_qcnt);
	}
}
function removeClone(removeBtn)
{
	var rule_template = jQuery(removeBtn).closest('.rule-template');
	jQuery('#questions_container').find("." + jQuery(rule_template).attr('id')).remove();
	jQuery(removeBtn).closest('.rule-template').remove();
	checkRules();
}

function fetchQuestions(rule_template)
{
	/* Get the test id */
	var test_id = jQuery('.test_id').val();

	/* Check if dynamic Quetsions checkbox is checked */
	var is_type = jQuery('#jform_quiz_type').is(':checked');

	jQuery(rule_template).removeClass('perfect').removeClass('insufficient_for_quiz').removeClass('insufficient_for_set');
	jQuery(".addButtons",rule_template).addClass('tmt-display-none');

	/* Get the questions count marks category level type added against this rule*/
	var question_count = jQuery(rule_template).find('.questions_count').val();
	var pull_question_count = jQuery(rule_template).find('.pull_questions_count').val();
	var questions_marks = jQuery(rule_template).find('.questions_marks').val();

	jQuery('#questions_container').find("." + jQuery(rule_template).attr('id')).remove();

	if (parseInt(question_count) > 0 && parseInt(question_count) > 0 && parseInt(pull_question_count) > 0 && parseInt(pull_question_count) >= parseInt(question_count) && parseInt(questions_marks))
	{
		var params = jQuery(rule_template).find("input, select").serializeArray();

		var questions_array = jQuery('input[name="cid[]"]').serializeArray();
		jQuery.merge(params,questions_array);

		params.push({name : 'test_id',value:test_id});
		params.push({name : 'quiz_type',value:is_type});

		/* The ajax requests fetches the questions required to make a pool. The Questions are fetched according to multiplication factor*/
		jQuery.ajax({
			url: 'index.php?option=com_tmt&view=test&task=test.fetchQuestions',
			dataType: 'json',
			type: 'POST',
			data: params ,
			async:false,
			success: function (data)
			{
				/* Response give
					que_available = question count matching to the rule (rule question_count * multiplication factor)
					que_remaining = question count remaining to make a pool
					questions = actual questions array
				*/
				response = data[0];
				jQuery(rule_template).find('.addButtons').hide();
				jQuery('.extra-info-needof',jQuery(rule_template)).text(response.que_need).removeClass('tmt-display-none');
				jQuery('.extra-info-available',jQuery(rule_template)).text(response.que_available).removeClass('tmt-display-none');
				jQuery('.extra-info-remain',jQuery(rule_template)).text(response.que_remaining);

				if (response.que_available > 0)
				{
					/* If the questions fetched are greater than 0*/
					if (response.que_available > 0)
					{
						jQuery.each(response.questions, function (i, q)
						{
							var htm='<tr data-tmplid="' + jQuery(rule_template).attr('id')  + '" class="rand_questions ' + jQuery(rule_template).attr('id')  + '" >';
							htm +='<td class="center"> <input type="checkbox" class="set_random_question" id="cb'+q.id+'" name="cid[]" value="'+q.id+'" onclick="Joomla.isChecked(this.checked);" style="display: none;" checked> <span class="btn btn-small sortable-handler" id="reorder" title="Reorder question" style="cursor: move;"> <i class="icon-move"> </i> </span> </td>';
							htm +=' <td class="small" > '+q.title+' </td>';
							htm +=' <td class="small"> '+q.category+' </td>';
							htm +=' <td class="small"> '+q.type+' </td>';
							htm +=' <td class="small center" name="td_marks"> '+q.marks+' </td>';
							htm +=' <td> <span class="btn btn-small" id="remove" onclick="removeRow(this);" title="Delete this question from test"><i class="icon-trash"> </i> </span> </td>';
							htm +=' </tr>';

							jQuery('#marks_tr').before(htm);
							jQuery('#question_paper').show();

						});

						getTotal();
						//getTotalSetMarks();
					}
				}

				/* If the questions got i.e. response.que_available are exact to make pull*/
				if(response.que_remaining == 0)
				{
					jQuery(rule_template).addClass('perfect');
					jQuery('.add-rule').removeClass('inactivelink');
				}
				else if(response.que_remaining != 0)
				{
					/*If the questions got i.e. response.que_available are less than the questions demanded show pick / add button against the rule*/
					jQuery(".addButtons",rule_template).show();
				}

				/* If the questions fetched are not enough to make single quiz only*/
				if (response.que_remaining != 0 && response.que_available < question_count)
				{
					jQuery(rule_template).addClass('insufficient_for_quiz');
				}
				else if (response.que_remaining != 0 && response.que_available >= question_count)
				{
					/* This means the questions are not sufficient to make the pool but is sufficient to make a Quiz */
					jQuery(rule_template).addClass('insufficient_for_set');
				}
			}
		});
	}
	else if(!parseInt(pull_question_count) || parseInt(pull_question_count) < parseInt(question_count) || !parseInt(questions_marks))
	{
		jQuery(rule_template).addClass('insufficient_for_quiz');
	}
}

function checkRules()
{
	var not_valid_for_quiz = not_valid_for_set  = avail_que_marks_sum = perfect_rules = 0;

	jQuery('.rule-template:not(.hidden-row)').each(function()
	{
		fetchQuestions(this);

		var question_count = jQuery(this).find('.questions_count').val();
		var question_marks =  jQuery(this).find(".questions_marks").val();
		var que_available = parseInt(jQuery('.extra-info-available',jQuery(this)).text(),10);
		var que_remaining =	parseInt(jQuery('.extra-info-remain',jQuery(this)).text(),10);

		if (que_remaining == 0)
		{
			perfect_rules++;
		}

		/* If the questions fetched are not enough to make single quiz only*/
		if (que_remaining != 0 && que_available < question_count)
		{
			not_valid_for_quiz++;
		}
		else if (que_remaining != 0 && que_available >= question_count)
		{
			not_valid_for_set++;
		}

		if (que_available >= question_count)
		{
			avail_que_marks_sum += question_count * question_marks
		}
	});

	jQuery('#perfectrules').val(perfect_rules);
	jQuery('#readytosaverules').val(not_valid_for_set);
	jQuery('#invalidrules').val(not_valid_for_quiz);


	jQuery('.rule-template-edit').each(function()
	{
		fetchQuestions(this);
		var question_count = jQuery(this).find('.questions_count').val();
		var question_marks =  jQuery(this).find(".questions_marks").val();
		var que_available = parseInt(jQuery('.extra-info-available',jQuery(this)).text(),10);
		var que_remaining =	parseInt(jQuery('.extra-info-remain',jQuery(this)).text(),10);

		if (que_remaining == 0)
		{
			perfect_rules++;
		}

		/* If the questions fetched are not enough to make single quiz only*/
		if (que_remaining != 0 && que_available < question_count)
		{
			not_valid_for_quiz++;
		}
		else if (que_remaining != 0 && que_available >= question_count)
		{
			not_valid_for_set++;
		}

		if (que_available >= question_count)
		{
			avail_que_marks_sum += question_count * question_marks
		}
	});

	avail_que_marks_sum = parseInt(avail_que_marks_sum,10);
	jQuery('#single-set-marks').text(avail_que_marks_sum);

	if(not_valid_for_quiz == 0 && not_valid_for_set == 0)
	{
		jQuery('#button_save_and_close').removeClass('inactivelink');
		jQuery('.add-rule').removeClass('inactivelink');
	}
	else if (not_valid_for_quiz > 0)
	{
		jQuery('#button_save_and_close').addClass('inactivelink');
		jQuery('.add-rule').addClass('inactivelink');
	}
	else if(not_valid_for_set > 0)
	{
		/* This means the questions are not sufficient to make the pool but is sufficient to make a Quiz */
		jQuery('#button_save_and_close').removeClass('inactivelink');
		jQuery('.add-rule').removeClass('inactivelink');
	}

	/*if(parseInt(avail_que_marks_sum) !== parseInt(jQuery('#jform_total_marks').val()))
	{
		enqueueSystemMessage("The total marks given for test does not match with the test marks according to questions fetched <br> Please correct the rules", "");
		jQuery('#button_save_and_close').addClass('inactivelink');

		return 'fail';
	}*/


	getTotal();
	return true;
}

/*function addRuleClone(newType,appendToClass,inputType)
{
	var lastId = jQuery('.rule-template:last').attr('id');
	lastId = lastId.replace('rule-template', '');

	var num = parseInt(lastId) + 1;

	var newElem = jQuery('#'+newType+'0').clone().attr('id',newType+num).attr('data-tmplnum',num).removeClass('hidden-row');

	jQuery(newElem).children().find("[id]").each(function()
	{
		var kid=jQuery(this);
		if(kid.attr('id')!=undefined)
		{
			var idOrig=kid.attr('id');
			kid.attr('id',idOrig+num).attr('id',idOrig+num);
			kid.val('');
		}
	});

	jQuery('.rule-template .remove-rule').show();
	jQuery(newElem).find('.add-rule').remove();
	jQuery('.rule-template .add-rule').appendTo(newElem);

	jQuery('.'+appendToClass).append(newElem);
	jQuery('#'+newType+num+' .new_rules').show();
	jQuery('#check_avalibility').show();
}*/

function hideDynamicDiv()
{
	jQuery('#quiz_type_div,.single-set-marks').hide();
}
function showDynamicDiv()
{
	jQuery('#quiz_type_div,.single-set-marks').show();
}
function fetchNewTmplQuestions(fordynamic)
{
	//fetchQuestions(jQuery('#rule-template'+fordynamic)[0]);
	checkRules();
}

/*
function assignval(val_select)
{
	jQuery('#qzType').val(val_select);
	var qtype = jQuery('#quetype').val();

	if(val_select == 'quiz')
	{
		jQuery('#jform_type option[value=rating').hide();
	}else
	{
		jQuery('#jform_type option[value=rating').show();
	}
	qztype = val_select;
	changeQnQztype(qztype, qtype);
}
*/

/*function changeQnQztype(qztype,qtype)
{
	if(qtype == 'file_upload' || qtype == 'text' || qtype == 'textarea' || qtype == 'rating')
	{
		jQuery('#answers-heading').hide();
		jQuery('#answers-options-labels').hide();
		jQuery('#add_answer').hide();
		if(qtype == 'rating')
		{
			jQuery('#total-marks').hide();
		}else
		{
			jQuery('#total-marks').show();
		}
	}
	else
	{
		jQuery('#answers-heading').show();
		jQuery('#answers-options-labels').show();
		jQuery('#add_answer').show();
	}

	if(qtype == 'file_upload' || qtype == 'text' || qtype == 'textarea' || qztype == 'feedback' || qztype == 'exercise')
	{
		jQuery('#total-marks').hide();
		if(((qtype == 'file_upload' || qtype == 'text' || qtype == 'textarea') && (qztype == 'feedback')) || ((qtype == 'file_upload') && (qztype == 'exercise')))
		{
			jQuery( "#myTabTabs li:nth-child(2)").hide();
			jQuery('#button_quiz_next_tab').hide();
			jQuery('#button_save_and_close').show();
			jQuery('#button_save_and_new').show();
			jQuery('#button_save').show();
		}
		else
		{
			jQuery( "#myTabTabs li:nth-child(2)").show();
			jQuery('#button_quiz_next_tab').show();
			jQuery('#button_save_and_close').hide();
			jQuery('#button_save_and_new').hide();
			jQuery('#button_save').hide();
		}
	}
	else
	{
		jQuery('#total-marks').show();
	}

	if(qztype == 'feedback' || qztype == 'exercise')
	{
		jQuery('.answers_iscorrect').parent().removeClass('span2').addClass('span3');
		jQuery('.is_correct_head').parent().removeClass('span2').addClass('span3');
		jQuery('.lbl_answers_marks').parent().hide();
		jQuery('.marks_head').hide();
		jQuery('#total-marks').hide();
		jQuery('#answers > .control-group').hide();
		jQuery('#jform_marks').removeClass('required');
		jQuery('#jform_marks').removeClass('validate-natural-number').addClass('validate-whole-number');
		jQuery('#jform_marks').removeAttr('required');
		jQuery('#jform_marks').val(0);
		jQuery('.answers_marks').val(0);
		jQuery('#total-marks-content').text(0);

		if(((qztype == 'feedback' ) && (qtype == 'text' || qtype == 'textarea' )) || qtype == 'file_upload')
		{
			jQuery('.answers-container').hide();
			jQuery('#no_answers_marks').show();
		}
		else
		{
			jQuery('.answers-container').show();
			jQuery('#no_answers_marks').hide();
			jQuery('#no-answer').html('');
		}
	}else
	{
		jQuery('.answers_iscorrect').parent().removeClass('span3').addClass('span2');
		jQuery('.is_correct_head').parent().removeClass('span3').addClass('span2');
		jQuery('.lbl_answers_marks').parent().show();
		jQuery('.marks_head').show();
		jQuery('#answers > .control-group').show();
		jQuery('.answers_marks').val('');
		jQuery('#jform_marks').val('');
	}
}*/
