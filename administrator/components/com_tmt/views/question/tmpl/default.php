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

//JHtml::_('behavior.keepalive');
//JHtml::_('behavior.tooltip');
JHtml::_('behavior.formvalidation');

//Load admin language file
$lang = JFactory::getLanguage();
$lang->load('com_tmt', JPATH_ADMINISTRATOR);

JHtml::_('jquery.ui', array('core', 'sortable'));

// Import helper for declaring language constant
JLoader::import('TmtHelper', JUri::root().'administrator/components/com_tmt/helpers/tmt.php');
TmtHelper::getLanguageConstant();

$input = JFactory::getApplication()->input;
$uniqId = $input->get('unique','','INT');
$fordynamic = $input->get('fordynamic','','INT');
$fdata = (array)$input->get('fdata','','ARRAY');
if($fordynamic && !empty($fdata))
{
	$this->form->bind($fdata);
}
if (empty($this->item->id))
{
	$app = JFactory::getApplication();

	if(! $this->categories_count)
	{
		$app->enqueueMessage(JText::_('COM_TMT_MSG_CREATE_PUBLISH_CATEGORIES'), 'warning');
	}
}
?>

<script type="text/javascript">
	var sum=0;
	var qtype='<?php if($this->item) { echo $this->item->type; }?>';
	var appendToClass='answers-container';

	/* Added for answer options sorting. */
	jQuery(function()
	{
		jQuery( "#sortable" ).sortable();
	});


	jQuery(document).ready(function(){
	<?php if (empty($this->item->category_id)) {?>
		if( jQuery('#jform_category_id').has('option').length > 0 && !jQuery('#jform_category_id').val()) {
			jQuery('#jform_category_id option:eq(1)').attr('selected', 'selected');
		}
		<?php } ?>

		<?php if ($this->canBeDeleted === false && $this->item->state == 1) {?>
			jQuery('#jform_marks').attr('readonly','');
		<?php } ?>

		if( (jQuery('#jform_ideal_time').val() !=='') && (! parseInt(jQuery('#jform_ideal_time').val(),10) > 0 ) )
		{
			jQuery('#jform_ideal_time').val('');
		}

		/* Hide answer options for textarea. */
		if(qtype=='text' || qtype=='textarea')
		{
			jQuery('#add_answer').hide();
			jQuery('#answers-options-labels').hide();
			jQuery('#total-marks').hide();
		}
		else
		{
			jQuery('#add_answer').show();
			jQuery('#answers-options-labels').show();
			jQuery('#total-marks').show();
		}
		if(qtype==='')
		{
			jQuery('#add_answer').hide();
			jQuery('#answers-options-labels').hide();
			jQuery('#total-marks').hide();
		}

		<?php if(!$this->item): ?>
			addAnswerClone(qtype,appendToClass);
		<?php endif;?>

		<?php if ((!isset($this->item->type) || !$this->item->type) && isset($fdata['type']) && !empty($fdata['type'])) :?>
			changeQType('<?php echo htmlspecialchars($fdata['type'])?>');
		<?php endif;?>

		getTotalMarks();
	});
</script>

<div id="tmt_question_form" class="row-fluid tjlms_add_quiz_form">
	<div class="span12">
		<fieldset>
			<legend>
			<!-- set componentheading -->
			<?php if (!empty($this->item->id)): ?>
				<h2 class="componentheading"><?php echo JText::_('COM_TMT_Q_FORM_HEADING_Q_EDIT') . htmlentities($this->item->title); ?></h2>
			<?php else: ?>
				<h2 class="componentheading"><?php echo JText::_('COM_TMT_Q_FORM_HEADING_Q_CREATE');?></h2>
			<?php endif; ?>
			<?php
			if($uniqId)
			{
			?>
				<button type="button" class="close" onclick="closebackendPopup(1);" data-dismiss="modal" aria-hidden="true">×</button>
			<?php
			}
			?>
			</legend>
		<div class="row-fluid">
			<div class="span12">

				<form action="" method="post" name="adminForm" id="adminForm" class="form-validate form-horizontal">
<div class="tmt_form_errors alert alert-danger">
			<div class="msg"></div>
		</div>
					<div class="row-fluid">
						<div class="span12">

							<?php echo JHtml::_('bootstrap.startTabSet', 'myTab', array('active' => 'question')); ?>
								<?php echo JHtml::_('bootstrap.addTab', 'myTab', 'question', JText::_('COM_TMT_Q_FORM_QUESTION', true)); ?>

									<div class="control-group">
										<div class="control-label"><?php echo $this->form->getLabel('type'); ?></div>
										<div class="controls"><?php echo $this->form->getInput('type'); ?></div>
									</div>

									<div class="control-group">
										<div class="control-label"><?php echo $this->form->getLabel('title'); ?></div>
										<div class="controls"><?php echo $this->form->getInput('title'); ?></div>
									</div>
									
									
									<div class="control-group">
										<div class="control-label"><?php echo $this->form->getLabel('alias'); ?></div>
										<div class="controls"><?php echo $this->form->getInput('alias'); ?></div>
									</div>

									<div class="control-group">
										<div class="control-label"><?php echo $this->form->getLabel('description'); ?></div>
										<div class="controls"><?php echo $this->form->getInput('description'); ?></div>
									</div>

									<div class="control-group">
										<div class="control-label"><?php echo $this->form->getLabel('ideal_time'); ?></div>
										<div class="controls"><?php echo $this->form->getInput('ideal_time'); ?></div>
									</div>

									<div class="control-group">
										<div class="control-label"><?php echo $this->form->getLabel('category_id'); ?></div>

										<div class="controls"><?php echo $this->form->getInput('category_id'); ?>&nbsp;&nbsp;&nbsp;<br><em><?php echo JText::_('COM_TMT_Q_FORM_CATEGORY_NOT_FOUND');?></em></div>
									</div>

									<div class="control-group">
										<div class="control-label"><?php echo $this->form->getLabel('level'); ?></div>
										<div class="controls"><?php echo $this->form->getInput('level'); ?></div>
									</div>

									<?php if(empty($this->item->created_by)){ ?>
										<input type="hidden" name="jform[created_by]" value="<?php echo JFactory::getUser()->id; ?>" />

									<?php }
									else{ ?>
										<input type="hidden" name="jform[created_by]" value="<?php echo $this->item->created_by; ?>" />

									<?php } ?>

									<input type="hidden" name="jform[id]" value="<?php echo $this->item->id; ?>" />
									<input type="hidden" name="jform[ordering]" value="<?php echo $this->item->ordering; ?>" />
									<input type="hidden" name="jform[checked_out]" value="<?php echo $this->item->checked_out; ?>" />
									<input type="hidden" name="jform[checked_out_time]" value="<?php echo $this->item->checked_out_time; ?>" />
									<input type="hidden" name="jform[created_on]" value="<?php echo $this->item->created_on; ?>" />

								<?php echo JHtml::_('bootstrap.endTab'); ?>

								<?php echo JHtml::_('bootstrap.addTab', 'myTab', 'answers', JText::_('COM_TMT_Q_FORM_ANSWERS', true)); ?>

									<div class="control-group">
										<div class="control-label"><?php echo $this->form->getLabel('marks'); ?></div>
										<div class="controls"><?php echo $this->form->getInput('marks'); ?></div>
										<?php if ($this->canBeDeleted === false && $this->item->state == 1) {?>
										<div class="controls text-info"><?php echo JText::_('COM_TMT_QUESTION_NOT_FOR_EDIT'); ?></div>
										<?php }?>

									</div>

									<div>
										<div id="answers-heading">
											<strong><?php echo JText::_('COM_TMT_Q_FORM_ANSWERS');?></strong>
										</div>
										<hr class="hr hr-condensed"/>

										<div id="answers-options-labels">
											<div class="span4">
											<span class="hasTooltip" title="<?php echo JText::_('COM_TMT_Q_FORM_ANSWER_OPTION_TEXT_DESC'); ?>"><?php echo JText::_('COM_TMT_Q_FORM_ANSWER_OPTION_TEXT'). ' *'; ?></span>
											</div>
											<div class="span2">
											<span class="hasTooltip" title="<?php echo JText::_('COM_TMT_Q_FORM_ANSWER_OPTION_IS_CORRECT_DESC'); ?>">
											<?php echo JText::_('COM_TMT_Q_FORM_ANSWER_OPTION_IS_CORRECT');?></span>
											</div>
											<div class="span1">
											<span class="hasTooltip" title="<?php echo JText::_('COM_TMT_Q_FORM_ANSWER_OPTION_MARKS_DESC'); ?>">
											<?php echo JText::_('COM_TMT_Q_FORM_ANSWER_OPTION_MARKS');?></span>
											</div>
											<div class="span3">
											<span class="hasTooltip" title="<?php echo JText::_('COM_TMT_Q_FORM_ANSWER_OPTION_COMMENTS_DESC'); ?>">
											<?php echo JText::_('COM_TMT_Q_FORM_ANSWER_OPTION_COMMENTS');?></span>
											</div>
											<div class="span1">
											<span class="hasTooltip" title="<?php echo JText::_('COM_TMT_Q_FORM_ANSWER_OPTION_REMOVE_DESC'); ?>">
											<?php echo JText::_('COM_TMT_Q_FORM_ANSWER_OPTION_REMOVE');?></span>
											</div>
											<div class="span1">
											<span class="hasTooltip span1" title="<?php echo JText::_('COM_TMT_Q_FORM_ANSWER_OPTION_REORDER_DESC'); ?>">
											<?php echo JText::_('COM_TMT_Q_FORM_ANSWER_OPTION_REORDER');?></span>
											</div>
										</div>
										<div style="clear:both"></div>
										<div class="answers-container" id="sortable" >
											<?php

											// Load previous answers as per answer-template
											$i=1;
											if(isset($this->item->answers))
											{
												foreach($this->item->answers as $answer)
												{
													$path = JPATH_ADMINISTRATOR.DS.'components'.DS.'com_tmt'.DS.'views'.DS.'question'.DS.'tmpl'.DS.'qtemplates_edit.php';
													ob_start();
													include($path);
													$html = ob_get_contents();
													ob_end_clean();
													echo $html;
													$i++;
												}
											}
											?>
										</div>
										<div>&nbsp;</div>
										<div class="clearfix">
											<button type="button" class="btn btn-primary btn-small " onclick="addAnswerClone('','answers-container');" id="add_answer">
												<i class="icon-plus"></i> &nbsp; <?php echo JText::_('COM_TMT_Q_FORM_BUTTON_ADD_NEW_ANSWER');?>
											</button>
										</div>

										<div class="clearfix"> </div>

										<div id="total-marks">
											<hr class="hr hr-condensed"/>
											<div class="row-fluid">
												<div class="tj_textpullright span6">
													<span id="total-marks-label">
														<strong><?php echo JText::_('COM_TMT_Q_FORM_TOTAL');?></strong>
													</span>
													&nbsp; &nbsp;
													<span id="total-marks-content">0</span>
												</div>
											</div>
											<div class="span5">&nbsp;</div>
										</div>
									</div>

								<?php echo JHtml::_('bootstrap.endTab'); ?>
							<?php echo JHtml::_('bootstrap.endTabSet'); ?>
						</div><!--span12-->
					</div><!--row-fluid-->
					<div class="row-fluid">
						<div class="span12">

							<!-- show action buttons/toolbar -->
							<div class="btn-toolbar form-actions clearfix">
								<button  type="button" id="button_quiz_prev_tab" style="display:none" class="hidebtn btn  com_tmt_button" onclick="questionNexttab(this)">
									<span ><i class="icon-arrow-left"></i><?php echo JText::_('COM_TMT_BUTTON_PREV') ?></span>
								</button>
								<?php if(!$this->addquiz ){ ?>
								<div class="btn-group ">
									<button type="button" style="display:none" id="button_save" class="btn btn-primary com_tmt_button" onclick="return questionactions('question.apply')">
										<!--<span class="icon-apply"></span>&#160;--><?php echo JText::_('COM_TMT_BUTTON_SAVE') ?>
									</button>
									<button type="button" style="display:none" id="button_save_and_new" class="btn btn-primary com_tmt_button hidebtn" onclick="questionactions('question.save2new')">
									<!--<span class="icon-ok"></span>&#160;--><?php echo JText::_('COM_TMT_BUTTON_SAVE_AND_NEW') ?>
								</button>
								</div>
								<?php } ?>

								<button type="button" id="button_quiz_next_tab" class="btn btn-primary com_tmt_button" onclick="questionNexttab(this)">
										<span ><?php echo JText::_('COM_TMT_BUTTON_NEXT') ?><i class="icon-arrow-right"></i></span>
								</button>

								<button type="button" style="display:none" id="button_save_and_close" class="btn btn-primary com_tmt_button hidebtn" onclick="questionactions('question.save')">
									<!--<span class="icon-ok"></span>&#160;--><?php echo JText::_('COM_TMT_BUTTON_SAVE_AND_CLOSE') ?>
								</button>
								<button type="button" class="btn com_tmt_button" onclick="<?php if(!$this->addquiz ){ ?> questionactions('question.cancel') <?php }else{ ?> parent.SqueezeBox.close(); <?php } ?>">
									<!--<span class="icon-cancel"></span>&#160;--><?php echo JText::_('COM_TMT_BUTTON_CANCEL') ?>
								</button>
							</div>
						</div><!--span12-->
					</div><!--row-fluid-->
					<?php
					if ($fordynamic)
					{
						echo '<input type="hidden" name="fordynamic" value="' . $fordynamic . '" />';
					}?>
					<input type="hidden" name="option" value="com_tmt" />
					<input type="hidden" name="controller" value="" />
					<input type="hidden" name="task" value="" />
					<input type="hidden" name="id" value="<?php if (!empty($this->item->id)) echo $this->item->id; ?>"/>
					<?php echo JHTML::_( 'form.token' ); ?>
				</form>
			</div><!--span12-->
		</div><!--row-fluid-->
	</fieldset>

	<div style="display: none;">
		<?php

		// Load all default answer-templates
		$path = JPATH_ADMINISTRATOR.DS.'components'.DS.'com_tmt'.DS.'views'.DS.'question'.DS.'tmpl'.DS.'qtemplates.php';
		ob_start();
		include($path);
		$html = ob_get_contents();
		ob_end_clean();
		echo $html;
		?>
	</div>

	</div><!--span12-->
</div><!--row-fluid-->
