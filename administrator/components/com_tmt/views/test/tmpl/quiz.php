<?php
/**
 * @version     1.0.0
 * @package     com_tmt
 * @copyright   Copyright (C) 2013. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Techjoomla <contact@techjoomla.com> - http://techjoomla.com
 */
// no direct access
defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;

HTMLHelper::_('behavior.keepalive');
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.formvalidation');
HTMLHelper::_('bootstrap.renderModal', 'a.modal');

$qztype = Factory::getApplication()->input->get('qztype', 'quiz', 'STRING');
$set_id = Factory::getApplication()->input->get('set_id', 0, 'INT');
$test_id = Factory::getApplication()->input->get('id', 0, 'INT');
$this->unique = Factory::getApplication()->input->get('unique', '', 'STRING');

$document = Factory::getDocument();
$document->addScript(JURI::root(true) . '/administrator/components/com_tmt/assets/js/tjform.js');
$document->addStylesheet(JURI::root(true) . '/administrator/components/com_tmt/assets/css/tjform.css');
$allow_paid_courses = $this->tjlmsparams->get('allow_paid_courses','0','INT');
$maxattempt = '';

$rules_checked  = 0;
$rules 	= isset($this->item->rules) ? $this->item->rules:'' ;
if($this->item->type != 'plain' && $rules)
{
	$rules_checked     = 1;
}

if (!isset($this->item->id))
{
	$this->item->id = 0;
}
else
{
	$lesson_id = $this->item->qid;
	$maxattempt=$this->item->max_attempt;
}

if (isset($lesson_id) && $lesson_id > 0){
	$maxattempt = $this->item->max_attempt;

	if (empty ($maxattempt))
	{
		$maxattempt = 0;
	}
}
?>

<script type="text/javascript">

	var appendToClass='question-container';

	jQuery(document).ready(function(){
		tjform.getTotal();
		calculateAlertTime();
		loadalerttimeshow();
		var qztype = '<?php echo $qztype; ?>';
		var qztype1 ='<?php echo $this->item->qztype; ?>';
		if(qztype !='quiz')
		{
			jQuery('#marks_tr').hide();
		}
		else
		{
			jQuery('#marks_tr').show();
		}

		if(qztype != qztype1)
		{
			jQuery('#questions_container thead').hide();
			jQuery('#questions_block .row').first().hide();
		}

		var total_marks = jQuery('#total-marks-content').text();
		if(total_marks == 0)
		{
			jQuery('#marks_tr').hide();
		}

		var quizTotalMarks = jQuery('#lesson_format_quiz__total_marks_',window.parent.document).val();
		jQuery('#final_total_marks').text(quizTotalMarks);
		var check_value = jQuery('#rules_checked').val();
		if(parseInt(check_value) == 1)
		{
			jQuery('#jform_quiz_type').prop('checked', true);
			jQuery('#rule_sets').show();
		}
		else
		{
			jQuery('#jform_quiz_type').prop('checked', false);
			jQuery('#rule_sets').hide();
		}

		var hiddenR=[];

		if (jQuery('#jform_time_duration').val() == '' || jQuery('#jform_time_duration').val() == 0)
		{
			jQuery('#jform_show_time label').addClass("disabledradio");
			jQuery('#jform_show_time_finished label').addClass("disabledradio");
		}

		jQuery('#jform_nums_of_sets').hide();
		// jQuery('#rule_sets').hide();
		jQuery('#jform_quiz_type').on('click',function()
		{
			var temp;
			temp = jQuery('#jform_quiz_type').attr('checked');
			if(temp == 'checked')
			{
				jQuery('#rule_sets').show();
				addRuleClone('rule-template','rules_block','checkbox');
				// we can use this later
				// jQuery('#jform_nums_of_sets').show();
			}
			else
			{
				jQuery('#rule_sets').hide();
				// jQuery('#jform_nums_of_sets').hide();
			}
		});

		// Code to make blank row hidden while we edit the quiz
		if(jQuery('#rules_checked').val() == 1)
		{
			jQuery('#rule-template0 .new_rules').css('display','none');
		}

		var editQuizID = jQuery('.test_id').val();
		if(editQuizID != '')
		{
			jQuery(".edit_category").attr("disabled", true);
			jQuery(".edit_difficultyLevel").attr("disabled", true);
			jQuery(".edit_qTypes").attr("disabled", true);
		}
	});

	/* Hide fetch questions button */
	function hideFetchQuestions()
	{
		var num = jQuery('.rule-template').filter(':visible').length;

		if(num == 0)
		{
			//jQuery('#fetch_questions').css('display', 'none');
		}
	}
</script>

<div class="techjoomla-strapper">
	<div id="tmt_test_form" class="tjlms_add_quiz_form">
		<fieldset>
			<div class='row'>
				<legend>
					<h2 class="componentheading"><?php echo Text::_('COM_TMT_HEADING_ADD_QUESTIONS');?></h2>
				</legend>
				<hr/>
				<div class="tmt_form_errors alert alert-danger tmt-display-none">
					<div class="msg"></div>
				</div>

				<div class="row">
					<div class="col-md-12">
						<?php
						$disp_none = "";
						if (!empty($this->item->sections))
						{
							$disp_none = "tmt-display-none";
						}
						?>
						<div class="hero-unit <?php echo $disp_none; ?>">
							<p><?php echo Text::_('COM_TMT_ADD_QUESTION_FOR_QUIZ_MSG'); ?></p>
						</div>
						<ul id="quiz-sections" class="curriculum-ul ui-sortable">
						<div id="tobeCloned" class="question_layout tmt-display-none">
							<div  class='question_row clone row '>
								<div class="center reorder col-md-1">
									<input type="checkbox" id="cb" name="cid[]" class="tmt-display-none" value="" onclick="Joomla.isChecked(this.checked);" checked>

									<span class="btn btn-small sortable-handler recorder_move" id="reorder" title="<?php echo Text::_('COM_TMT_TEST_FORM_REORDER'); ?>">
										<i class="icon-move questionSortingHandler"> </i>
									</span>
								</div>

								<div class="question_title col-md-6"></div>

								<div class="small question_cat col-md-2"></div>

								<div class="small question_type col-md-1"></div>
									<?php if($qztype == 'quiz' ) { ?>
								<div class="small center question_marks col-md-1" name="td_marks"></div>
									<?php	}?>
								<div class="remove tr question_remove col-md-1 center"><input type='hidden' name='sid[]' value="" class='section_id' />
									<span class="btn btn-small" id="remove" onclick="removeRow(this);" title="<?php echo Text::_('COM_TMT_TEST_FORM_DELETE'); ?>">
										<i class="icon-trash"> </i>
									</span>
								</div>
							</div><!--tobeCloned-->
						</div>
					<div id="sortable" class="question-container curriculum-ul ui-sortable" >
						<?php
						if (!empty($this->item->sections))
						{
							foreach ($this->item->sections as $section=>$s)
							{
							?>
							<li id="sectionlist_<?php echo $s->id; ?>" class="mod_outer">
								<div class="row tjlms_section" id='section_row_<?php echo $s->id; ?>'>
									<div class="content-li   col-md-10">
										<i class="icon-menu sectionSortingHandler" title="<?php echo Text::_('COM_TMT_TEST_FORM_SORT_SECTION'); ?>"></i>
										<i class="icon-book icon-white"></i>
										<span class="tmt_section_title"><?php echo $s->title; ?></span>
									</div>
									<div class="tjlms-actions btn-group non-accordian col-md-2">
										<div class="section-functionality-icons row">
											<a class="editsectionlink" title="<?php echo Text::_('COM_TMT_TEST_FORM_EDIT_SECTION'); ?>" onclick="tjform.editSection('<?php echo $this->item->id; ?>','<?php echo $s->id ?>')">
												<span class="icon-edit"></span>
											</a>
											<a class="sectiondelete tjlms_display_none" title="<?php echo Text::_('COM_TMT_TEST_FORM_DELETE_SECTION'); ?>" onclick="tjform.delete('<?php echo $this->item->id;?>','<?php echo $s->id; ?>');">
												<span class="icon-trash"></span>
											</a>
										</div>
									</div>
								</div><!--tjlms_section question_row-->
								<div class="section-edit-form" id="add_section_form_<?php echo $s->id;?>">
									<?php
										require_once JPATH_ROOT . '/libraries/techjoomla/common.php';
										$section_html='';
										$test_id = $test_id;
										$section_id = $s->id;
										$section_name = $s->title;
										$section_state = $s->state;
										//~ $section_state	= 1;
										$tjcommon	=	new TechjoomlaCommon();
										$layout = $tjcommon->getViewpath('com_tmt','section','section','ADMIN','ADMIN');
										ob_start();
										include($layout);
										$section_html .= ob_get_contents();
										ob_end_clean();
										echo $section_html;
									?>
								</div>
								<div class="row action">
								<?php if($this->questions_count)
									{ ?>
									<?php $link = Route::_(Uri::base()."index.php?option=com_tmt&view=questions&layout=qpopup&tmpl=component&fromPlugin=1&qztype=". $qztype . "&unique=".$this->unique."&test_id=".$test_id  ); ?>
									<div class="col-md-4">
										<input type="hidden" name="section_id" value="<?php echo $section_id; ?>" />
										<a onclick="opentmtSqueezeBoxForm('<?php echo $link?>','<?php echo $this->unique?>','<?php echo $section_id; ?>')" class="btn btn-primary btn-block"><?php echo Text::_( 'COM_TMT_FORM_TEST_ADD_QUESTIONS'); ?></a>
									</div>
									<?php $link = Route::_(Uri::base()."index.php?option=com_tmt&view=test&layout=addrules&tmpl=component&fromPlugin=1&qztype=". $qztype . "&unique=".$this->unique."&test_id=".$test_id); ?>

									<div class="col-md-4">
										<a class="btn btn-primary btn-block" href="#" onclick="opentmtSqueezeBoxForm('<?php echo $link ?>','<?php echo $this->unique?>','<?php echo $section_id; ?>')" ><?php echo Text::_( 'COM_TMT_FORM_TEST_AUTO_GENERATE_QP' ); ?></a>
									</div>
								<?php } ?>

									<?php $link = Route::_(Uri::base()."index.php?option=com_tmt&view=question&fromPlugin=1&qztype=". $qztype . "&test_id=".$test_id."&unique=".$this->unique."&tmpl=component".( isset($this->addquiz)? "&addquiz=1" : "") ); ?>

									<div class="col-md-4">
										<a onclick="opentmtSqueezeBoxForm('<?php echo $link?>','<?php echo $this->unique?>','<?php echo $section_id; ?>')" class="btn btn-primary btn-block"><?php echo Text::_( 'COM_TMT_FORM_TEST_ADD_QUESTION' ); ?></a>
									</div>
								</div><!--row action-->

								<input type="hidden" name="section_id" value="<?php echo $s->id; ?>">

								<?php
								// Load previous answers as per answer-template

								if($this->item->questions)
								{
									?>
									<div class='thead row'>
										<div class='tr row question_head_row ques-alignment'>
											<div class="reorder col-md-1 center"><?php echo Text::_('COM_TMT_TEST_FORM_ORDER'); ?></div>
											<div class='col-md-6'><?php echo Text::_('COM_TMT_TEST_FORM_QUESTION'); ?></div>
											<div class='col-md-2'><?php echo Text::_('COM_TMT_TEST_FORM_CATEGORY'); ?></div>
											<div class='col-md-1'><?php echo Text::_('COM_TMT_TEST_FORM_TYPE'); ?></div>
												<?php if($qztype == 'quiz' ) { ?>
											<div class='marks_head col-md-1 center'><?php echo Text::_('COM_TMT_TEST_FORM_MARKS'); ?></div>
											<?php } ?>
											<div class="question_remove col-md-1 center"><?php echo Text::_('COM_TMT_TEST_FORM_REMOVE'); ?></div>
										</div>
									</div><!--thead-->
								<div id="question_paper_<?php echo $s->id; ?>" class= "questionInSection ui-sortable connectedSortable curriculum-lesson-ul question_paper"style="<?php echo (empty($this->item->questions)) ?  'display:none' :  '' ; ?> ">
									<?php
									foreach($this->item->questions as $key=>$value)
									{
										foreach($value as $q)
										{
											if($q->section_id == $s->id)
											{
											?>
										<div id ="questionlist_<?php echo $q->question_id; ?>" class="question_layout">
											<div class="question_row row clone">
												<div class="col-md-1 center reorder">
													<input type="checkbox" id="cb<?php echo $q->question_id; ?>" name="lesson_format[quiz][cid][]" value="<?php echo $q->question_id; ?>" onclick="Joomla.isChecked(this.checked);" style="display: none;" checked>

													<span class="btn btn-small sortable-handler recorder_move" id="reorder" title="<?php echo Text::_('COM_TMT_TEST_FORM_REORDER'); ?>">
														<i class="icon-move questionSortingHandler"> </i>
													</span>
												</div>

												<div class="col-md-6 question-title">
													<label for="cb<?php echo $q->question_id; ?>" class="block"> <?php echo htmlentities($q->title);?> </label>
												</div>

												<div class="col-md-2 question-cat small nowrap hidden-phone">
													<label for="cb<?php echo $q->question_id; ?>" class="block"><?php echo $q->category; ?> </label>
												</div>

												<div class="col-md-1 question-type small hidden-phone">
													<label for="cb<?php echo $q->question_id; ?>" class="block"> <?php echo $q->type; ?></label>
												</div>
												<?php if ($qztype == 'quiz' ):?>
												<div class="col-md-1 question-marks small nowrap center marks_td" name="td_marks">
													<label for="cb0" class="block"><?php echo $q->marks; ?></label>
												</div>
												<?php endif;?>
												<div class="col-md-1 question_remove center">
													<input type="hidden" name="lesson_format[quiz][sid][]" value="<?php echo $q->section_id; ?>" class="section_id">
													<span class="btn btn-small" id="remove" onclick="removeRow(this);" title="<?php echo Text::_('COM_TMT_TEST_FORM_DELETE'); ?>">
														<i class="icon-trash"> </i>
													</span>
												</div>
											</div><!--question_row-->
										</div>
												<?php
											}	//if close.
										} //inner froeach close
									} //outer foreach close.
								} //if close.	?>
								</div><!--question_paper-->
							</li>
					<?php	}	?><!--foreach sections-->
				<?php	}	?>
			</div>
						</ul><!--if close -->
						<?php	if($qztype == 'quiz'){	?>
						<div class="row" id='total_marks'>
							<div class="col-md-5 offset1">
								<b><?php echo Text::_('COM_TMT_FORM_TEST_TOTAL_MARKS_FOR_QUIZ'); ?> </b>
								<span type="text" id="final_total_marks" ><?php echo $this->item->total_marks;?></span>
							</div>
							<div id="marks_tr" class="center span6 non-sortable-tr-quiz">
								<div class='col-md-7'>
									<strong class='pull-right'><?php echo Text::_('COM_TMT_TEST_FORM_TOTAL_MARKS');?></strong>
								</div>
								<div class='col-md-5'>
									<strong id="total-marks-content" class='pull-left'> </strong>
								</div>
							</div>
						</div> <!--row-->
				<?php	}	?>



					</div>	<!--col-md-12-->
				</div><!--row-->

				<?php if($this->addquiz != 0 ): ?>
			<!-- show action buttons/toolbar -->
				<div class="row">
					<div class="col-md-12">
						<div class="btn-toolbar form-actions clearfix">
							<div class="btn-group">
								<button style="display:none" type="button" id="button_quiz_prev_tab" class="btn btn-primary com_tmt_button" onclick="quizNexttab(this,'test')">
									<i class="fa fa-arrow-circle-o-left"></i>  <?php echo Text::_('COM_TMT_BUTTON_PREV') ?>
								</button>
								<button type="button" id="button_quiz_next_tab" class="btn btn-primary com_tmt_button" onclick="quizNexttab(this,'test')">
									<?php echo Text::_('COM_TMT_BUTTON_NEXT') ?> <i class="fa fa-arrow-circle-o-right"></i>
								</button>
								<button style="display:none" type="button" id="button_save_and_close" class="btn btn-primary com_tmt_button" onclick="quizactions(this,'test.save','<?php echo $this->mod_id;?>')">
									<!--<span class="icon-ok"></span>&#160; --><?php echo Text::_('COM_TMT_BUTTON_SAVE_AND_CLOSE') ?>
								</button>
							</div>
							<div class="btn-group">
								<?php if($this->addquiz == 1 )
								{
									if (empty($this->item->id))
									{	?>
										<button type="button" id="button_cancel" class="btn com_tmt_button" onclick="parent.hide_add_quizs_wizard('<?php echo $this->mod_id;?>')"><?php echo Text::_('COM_TMT_BUTTON_CANCEL') ?>
										</button>
									<?php
									}
									else
									{ ?>
										<button type="button" id="button_cancel" class="btn com_tmt_button" onclick="parent.showHideEditLesson('<?php echo $this->mod_id;?>','<?php echo $this->unique ?>',0)"><?php echo Text::_('COM_TMT_BUTTON_CANCEL') ?>
										</button>
							<?php	}	?>

						<?php	}
								else
								{	?>
								<button type="button" id="button_cancel" class="btn com_tmt_button" onclick="Joomla.submitbutton('test.cancel')">
									<!--<span class="icon-cancel"></span>&#160;--><?php echo Text::_('COM_TMT_BUTTON_CANCEL') ?>
								</button>
					<?php		}	?>
							</div><!--btn-group-->
						</div><!--toolbar-->
					</div><!--col-md-12-->
				</div><!--row-->
			<?php endif; ?>

			<?php if(empty($this->item->created_by)){ ?>
				<input type="hidden" name="jform[created_by]" value="<?php echo Factory::getUser()->id; ?>" />
				<input type="hidden" name="jform[reviewers][]" value="<?php echo Factory::getUser()->id; ?>" />
			<?php }
			else{ ?>
				<input type="hidden" name="jform[created_by]" value="<?php echo $this->item->created_by; ?>" />
				<input type="hidden" name="jform[reviewers][]" value="<?php echo Factory::getUser()->id; ?>" />
			<?php } ?>

			<?php if($this->course_id) {  ?>
			<input type="hidden" name="course_id" value="<?php echo $this->course_id; ?>" />
			<?php } ?>

			<?php if($this->unique) {  ?>
			<input type="hidden" name="unique" value="<?php echo $this->unique; ?>" />
			<?php } ?>

			<input type="hidden" name="jform[id]" value="<?php echo $this->item->id; ?>" />
			<input type="hidden" name="option" value="com_tmt" />
			<input type="hidden" name="controller" value="" />

			<input type="hidden" name="task" value="test.save" />

			<input type="hidden" name="id" value="<?php if (!empty($this->item->id)) echo $this->item->id; ?>" class="test_id" />
			<input type="hidden" name="set_id" value="<?php if (!empty($set_id)) echo $set_id; ?>" id="set_id" />
			<input type="hidden" name="rules_checked"  id="rules_checked" value="<?php  echo $rules_checked; ?>"/>

			<input type="hidden" name="invalid_rule_count"  id="invalid_rule_count" value="0" />

			<?php echo JHTML::_('form.token'); ?>

<div class="add_section_area">
				<div class="col-md-3">
					<a onclick="tjform.editSection('<?php echo $test_id;?>','0')" class="btn btn-primary btn-block">
						<?php echo Text::_( 'COM_TMT_FORM_TEST_ADD_SECTION'); ?>
					</a>
				</div>
				<div class="section-edit-form" id="add_section_form_0" style='padding-top:40px;'>
					<?php
						require_once JPATH_ROOT . '/libraries/techjoomla/common.php';
						$section_html='';
						$test_id = $test_id;
						$section_id = 0;
						$section_name = '';
						$section_state	= 1;
						$qztype = $qztype;
						$tjcommon = new TechjoomlaCommon();
						$layout = $tjcommon->getViewpath('com_tmt','section','section','ADMIN','ADMIN');
						ob_start();
						include($layout);
						$section_html.= ob_get_contents();
						ob_end_clean();
						echo $section_html;
					?>
				</div>
</div>
<div class="row center save_close">
							<button type="button" class="btn btn-success" onclick="getQuestions()">Add</button>
							<input type="hidden" name="form-id" id="form_id" />
						</div>
			</div><!--row-->
		</fieldset>
	</div><!--tmt_test_form-->
</div><!--techjoomla-strapper-->
<script>
function sectionActions(thiselement,action,originalvalue)
{
	var sectionform	= jQuery(thiselement).closest('.tjlms_section_form');
	var test_id	= jQuery("#test_id", sectionform).val();
	var section_id	= jQuery("#section_id", sectionform).val();
	var section_title = '';
	var sectionTitle = jQuery.trim(jQuery('#title',sectionform).val());

	sectionTitle = tjform.noScript(sectionTitle);

	jQuery('#title',sectionform).val(sectionTitle);
	/* populate the input task hidden field with module action*/
	jQuery("#task", sectionform).val(action);

	var sectionform	= jQuery('#tjlms_section_form_'+section_id);

	if (action == 'section.sectionCancel')
	{

		/* Get original section title*/
		if (originalvalue)
		{
			section_title = originalvalue;
		}

		/* Added to refesh textbox data on cancel button*/
		jQuery("#title", sectionform).val(section_title);
		tjform.hideeditSection(test_id,section_id);
		return false;
	}
	else
	{
		jQuery(sectionform).ajaxSubmit({
			datatype:'HTML',
			beforeSend: function() {
				var sectionTitle = jQuery.trim(jQuery('#title',sectionform).val());

				if(sectionTitle == '')
				{
					jQuery(".tjlms_section_errors .msg", sectionform).html('Invalid field: Title' );
					jQuery(".tjlms_section_errors", sectionform).show();
					return false;
				}
			},
			success: function(response)
			{
				if(section_id != 0)
				{
					tjform.success_popup();
				}
				else
				{
					jQuery('#tjlms_section_form_0 #title').val('');
					jQuery('.hero-unit').hide();
					jQuery('.question_paper').show();
					jQuery('#total_marks').show();
					jQuery('#sortable').append(response);
					tjform.hideeditSection(test_id,section_id);
					initSortingHandler();
				}
			},
			error: function()
			{
				jQuery(".tmt_form_errors .msg").html(Joomla.Text._('COM_TMT_SAVE_SECTION_ERROR'));
				jQuery(".tmt_form_errors").show();
				return false;
			},
			complete: function(xhr) {
			}
		});

	}
	return false;
}
function saveQuiz()
{
	var quizMetaData = [];
	var format,sub_format,lesson_format_id,lesson_id;
	var form_id = jQuery('#form_id').val();

	format = jQuery('#lesson-format-form_'+form_id ,window.parent.document).find('#jform_format').val();
	sub_format = jQuery('#lesson-format-form_'+form_id ,window.parent.document).find('#jform_subformat').val();
	lesson_format_id = jQuery('#lesson-format-form_'+form_id ,window.parent.document).find('#lesson_format_id').val();
	lesson_id = jQuery('#lesson-format-form_'+form_id ,window.parent.document).find('#lesson_id').val();

	quizMetaData['format']=format;
	quizMetaData['subformat']=sub_format;
	quizMetaData['format_id']=lesson_format_id;
	quizMetaData['id']=lesson_id;

	jQuery.ajax({
		url: 'index.php?option=com_tjlms&task=lesson.updateformat',
		dataType: 'json',
		type: 'POST',
		data: quizMetaData ,
		success: function (data)
		{
			console.log("POST SUCCESS"+data);
		}
	 });
}

function getQuestions()
{
	var result = checkQuestionsAddedtoSections();

	if(result)
	{
		var c = fixDuplicates();
		if (c > 0 )
		{
			jQuery(".tmt_form_errors .msg").html(Joomla.Text._('COM_TMT_QUIZ_DUPLICATE_QUESTIONS'));
			jQuery(".tmt_form_errors").show();
			return false;
		}

		var qztype = "<?php echo $qztype;?>";
		if (qztype =='quiz')
		{
			if( parseInt(jQuery('#total-marks-content').text(),10) === 0)
			{
				/* if sum does not match total marks for questions */
				jQuery('#final_total_marks').focus();
				jQuery("#system-message-container").show();
				enqueueSystemMessage(Joomla.Text._('COM_TMT_TEST_FORM_MSG_ADD_Q'), "");
				return false;
			}

			if( parseInt(jQuery('#total-marks-content').text(),10) != jQuery('#final_total_marks').text())
			{
				/* if sum does not match total marks for questions */
				jQuery('#final_total_marks').focus();
				jQuery("#system-message-container").show();
				enqueueSystemMessage(Joomla.Text._('COM_TMT_TEST_FORM_MSG_MARKS_MISMATCH'), "");
				return false;
			}
			else
			{
				var marks_msg = Joomla.Text._('COM_TMT_TEST_FORM_MSG_MARKS_MISMATCH');
				if (jQuery(".msg").html() ==  marks_msg )
				{
					jQuery(".tmt_form_errors").hide();
				}
			}
		}

		jQuery(".no-questions-msg",window.parent.document).hide();
		jQuery("#questions_container",window.parent.document).show();

		if (jQuery("#test_id",window.parent.document).val() != 0)
		{
			jQuery("#questions_container .question_row",window.parent.document).remove();
		}

		var test_id = jQuery("#test_id").val()

		if (test_id)
		{
			jQuery("#test_id",window.parent.document).val(test_id);
		}

		var set_id = jQuery("#set_id").val()

		if (set_id)
		{
			jQuery("input[name='lesson_format[exercise][set_id]']",window.parent.document).val(set_id);
		}
		jQuery("#quiz-sections",window.parent.document).html(jQuery('.mod_outer'));
		window.parent.tjform.getTotal();
		window.parent.SqueezeBox.close();
	}
}

function hideButtons()
{
	var btnval = jQuery(".question_paper").is(':visible');
}

function checkQuestionsAddedtoSections()
{
	var error_saving = 0;
	jQuery("#quiz-sections .mod_outer").each(function() {

		if(jQuery(this).find('.question_paper .clone').length ==  0)
		{
			error_saving = 1;
		}
	});

	if (error_saving == 1)
	{
		enqueueSystemMessage(Joomla.Text._('COM_TMT_NO_SECTION_QUESTION'), "");
		return false;
	}

	return true;
}
</script>
