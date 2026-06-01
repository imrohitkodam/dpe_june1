<?php
/**
 * @package     TMT
 * @subpackage  com_shika
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
use Joomla\CMS\Layout\LayoutHelper;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;

HTMLHelper::_('behavior.keepalive');
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.multiselect');
// HTMLHelper::_('formbehavior.chosen', 'select');

$app      = Factory::getApplication();
$document = Factory::getDocument();

$document->addStylesheet(Uri::root() . 'components/com_tmt/assets/css/tmt.css');

$qztype = $app->input->get('qztype', '', 'STRING');
$unique = $app->input->get('unique', '', 'STRING');
$unique = (string) preg_replace('/[^0-9_]/i', '', $unique);

$fromPlugin = 0;

$options['relative'] = true;
HTMLHelper::_('script', 'com_tjlms/tjService.js', $options);
HTMLHelper::_('script', 'com_tmt/tmt.js', $options);
?>
<script type="text/javascript">
Joomla.submitbutton = function(task)
{
	if(task=='insertQuestions'){

		if(document.adminForm.boxchecked.value==0)
		{
			alert('<?php echo Text::_("COM_TMT_MESSAGE_SELECT_ITEMS");?>');
			return false;
		}

<?php if($fromPlugin == 1)
	{ ?>
		var qids = [];
		var section_ids = [];
		var test_id = "<?php echo $test_id;?>";
		jQuery("input[id*='cb']:checked").each(function() {
			var curRow=this;

			var qid = jQuery(this).val();
			qids.push(qid);
			var qtitle = jQuery(this).closest('tr').find('.question-title').text();
			var section_id = jQuery(this).closest('tr').find('.section_id').val();
			section_ids.push(section_id);
			var qcategory = jQuery(this).closest('tr').find('.question-cat').text();
			var qtype = jQuery(this).closest('tr').find('.question-type').text();
			var qmarks = jQuery(this).closest('tr').find('.question-marks').text();

			showQuestionsOnParentForm(qid, qtitle, qcategory, qtype, qmarks,section_id);
			jQuery('#questions_container .thead',window.parent.document).show();
			jQuery("#questions_container .tbody .clone",window.parent.document).addClass('question_row');
//			jQuery('#marks_tr',window.parent.document).removeClass('question_row');
			jQuery('#questions_block .row-fluid',window.parent.document).first().show();
			jQuery('#total_marks',window.parent.document).show();
			<?php if($qztype == 'quiz')
			{	?>
				jQuery('#marks_tr',window.parent.document).show();
	<?php	}
			else
			{	?>
				jQuery('#marks_tr',window.parent.document).hide();
	<?php	}	?>

			question_ids = JSON.stringify(qids);
			jsonsection_ids = JSON.stringify(section_ids);
		});
		jQuery.ajax({
			url: "index.php?option=com_tmt&task=questions.testQuestions&test_id=" + test_id,
			type: "post",
			dataType: "json",

			data:"question_ids=" + question_ids + "&section_ids=" + jsonsection_ids,
			aync:true,
			success: function(data)
			{
				if(data)
				{
					closePopupForm();
				}
			}
		});
<?php
	}
	else
	{
?>
		jQuery("input[id*='cb']").each(function() {
			var curRow=this;
			if( jQuery(curRow).is(":checked") )
			{

				var newRow= jQuery(curRow).parent().parent();

				jQuery(newRow).children().each(function(tds,td) {
					currTd=td;
					jQuery(currTd).find("input[type*='checkbox']").each(function(nodes,n) {
						var cb=n;
						jQuery(cb).toggle();
						jQuery(cb).attr('name','cid[]');

						jQuery( currTd ).append( '<span class="btn btn-small sortable-handler" id="reorder" title="<?php echo Text::_('COM_TMT_TEST_FORM_REORDER'); ?>"style="cursor: move;"><i class="icon-move"> </i></span>' );
					});
				});
//console.log(newRow);
				jQuery(curRow).parent().parent().append( '<td><span class="btn btn-small " id="remove" onclick="removeRow(this);" title="<?php echo Text::_('COM_TMT_TEST_FORM_DELETE'); ?>"><i class="icon-trash"> </i></span></td>' );
				jQuery(window.parent.document.getElementById("idIframe_<?php echo $this->unique ?>").contentWindow.marks_tr).before( newRow );
				jQuery( window.parent.document.getElementById("idIframe_<?php echo $this->unique ?>").contentWindow.question_paper ).show();
			}
		});

		if (typeof window.parent.document.getElementById("idIframe_<?php echo $this->unique ?>").contentWindow.hideDynamicDiv != "undefined")
		{
			window.parent.document.getElementById("idIframe_<?php echo $this->unique ?>").contentWindow.hideDynamicDiv();
		}
		window.parent.document.getElementById("idIframe_<?php echo $this->unique ?>").contentWindow.closePopup();
<?php

	}
?>
	}
	else
	{
		Joomla.submitform(task);
	}
}
</script>

<div id="tmt_questions" class="row pick-questions-modal">
	<div class="col-lg-12">

		<form method="post" name="adminForm" id="adminForm">
			<div class="top-heading pickQuesalign">
				<!-- set componentheading -->
				<!-- <button type="button" class="close" onclick="closebackendPopup(1);" data-dismiss="modal" aria-hidden="true">×</button> -->
				<strong class="componentheading"><h2><?php echo Text::_('COM_TMT_FORM_TEST_ADD_QUESTIONS');?></h2></strong>
				<hr/>
				<!--Header containing filters-->

					<div class="row">
						<div class="filteralign">
							<?php
								// Search tools bar
								echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this));
							?>
						</div>

							<input type="hidden" name="filter_order" value="<?php echo $this->filter_order; ?>" />
							<input type="hidden" name="filter_order_Dir" value="<?php echo $this->filter_order_Dir; ?>" />

							<input type="hidden" name="option" value="com_tmt" />
							<input type="hidden" name="view" value="questions" />
							<input type="hidden" name="controller" value="" />

							<input type="hidden" name="task" value="" />
							<input type="hidden" name="boxchecked" value="0" />
							<?php echo HTMLHelper::_( 'form.token' ); ?>

					</div><!--row-fluid-->
					<div class="row add-ques-btn-div">
							<div class="btn-group clearfix pull-right">
									<button type="button" class="btn btn-primary com_tmt_button" onclick="tmt.question.batchaddToSection('<?php echo $unique;?>')">
											<span class="icon-apply"></span>&#160;<?php echo Text::_('COM_TMT_ADD_QUESTIONS') ?>
									</button>
							</div>
					</div>

					<div>&nbsp;</div>

				<!--ENDS FILTERS-->
			</div>


			<div class="pickQuesalign">

				<table class="category table table-striped table-bordered table-hover">
					<thead>
						<tr>
							<th class="center com_tmt_width1">
								<?php //echo HTMLHelper::_('grid.checkall','', 'COM_TMT_CHECK_ALL');
								echo HTMLHelper::_('grid.checkall'); ?>
							</th>
							<th>
								<?php echo HTMLHelper::_('grid.sort', 'COM_TMT_Q_LIST_TITLE', 'title', $this->filter_order_Dir, $this->filter_order ); ?>
							</th>
							<th class="nowrap hidden-phone com_tmt_width20">
								<?php echo HTMLHelper::_('grid.sort', 'COM_TMT_Q_LIST_CATEGORY', 'category', $this->filter_order_Dir, $this->filter_order ); ?>
							</th>
							<th class="nowrap hidden-phone center com_tmt_width20">
								<?php echo HTMLHelper::_('grid.sort', 'COM_TMT_Q_LIST_TYPE', 'type', $this->filter_order_Dir, $this->filter_order ); ?>
							</th>
							<?php
								if(isset($this->gradingtype))
								{
									if($this->gradingtype == 'feedback' || $this->gradingtype == 'exercise' ) {


									} else{?>
									<th class="nowrap center com_tmt_width1">
										<?php echo HTMLHelper::_('grid.sort', 'COM_TMT_Q_LIST_MARKS', 'marks', $this->filter_order_Dir, $this->filter_order ); ?>
									</th>
							<?php	}
								}	?>
						</tr>
					</thead>

					<tbody>
						<?php
						$n=count( $this->items );
						for($i=0; $i < $n ; $i++)
						{
							$row=$this->items[$i];
							?>
							<tr>
								<td class="center">
									<?php echo HTMLHelper::_('grid.id', $i, $row->id ); ?>
								</td>
								<td class="question-title">
									<label class="block" for="cb<?php echo $i; ?>">
										<?php echo htmlentities($row->title); ?>
										<div class="small">
											<span class="break-word">
												<?php echo Text::sprintf('COM_TMT_QUESTIONS_QUESTION_ALIAS', htmlentities($row->alias)); ?>
											</span>
										</div>
									</label>
								</td>
								<td class="question-cat small nowrap hidden-phone">
									<label class="block" for="cb<?php echo $i; ?>"><?php echo $this->escape($row->category); ?></label>
								</td>
								<td class="question-type small nowrap hidden-phone">
									<label class="block" for="cb<?php echo $i; ?>"><?php echo $this->escape($row->type); ?></label>
								</td>
								<?php if(isset($this->gradingtype))
									   {
										 if($this->gradingtype == 'feedback' || $this->gradingtype == 'exercise' ) {


										}else{?>
								<td class="question-marks small nowrap center" name="td_marks">
									<label class="block" for="cb<?php echo $i; ?>"><?php echo $row->marks; ?></label>
								</td>
						<?php				}
										}	?>
							</tr>
						<?php
						}//end if
						?>
					</tbody>
				</table>

				<!-- show message if no items found -->
				<?php if (empty($this->items)) : ?>
					<div class="alert"><?php echo Text::_('COM_TMT_Q_LIST_MSG_NO_Q_FOUND_TO_ADD');?></div>
				<?php endif; ?>

			</div>
			<div class="row-fluid">
				<div class="span12">
					<?php echo $this->pagination->getListFooter(); ?>
					<hr class="hr hr-condensed"/>
				</div><!--span12-->
			</div><!--row-fluid-->
		</form>

	</div><!--span12-->
</div><!--row-fluid-->
