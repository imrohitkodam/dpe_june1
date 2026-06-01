<?php
/**
 * @version     1.0.0
 * @package     com_tmt
 * @copyright   Copyright (C) 2013. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Techjoomla <contact@techjoomla.com> - http://techjoomla.com
 */
defined('_JEXEC') or die;

JHtml::_('behavior.keepalive');
JHtml::_('behavior.tooltip');
JHtml::_('bootstrap.tooltip');
JHtml::_('behavior.multiselect');
JHtml::_('formbehavior.chosen', 'select');

$document=JFactory::getDocument();
$document->addStylesheet(JUri::root().'components/com_tmt/assets/css/tmt.css');
?>

<script type="text/javascript">
Joomla.submitbutton = function(task)
{
	if(task=='insertQuestions'){

		if(document.adminForm.boxchecked.value==0)
		{
			alert('<?php echo JText::_("COM_TMT_MESSAGE_SELECT_ITEMS");?>');
			return false;
		}

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

						jQuery( currTd ).append( '<span class="btn btn-small sortable-handler" id="reorder" title="<?php echo JText::_('COM_TMT_TEST_FORM_REORDER'); ?>"style="cursor: move;"><i class="icon-move"> </i></span>' );
					});
				});
//console.log(newRow);
				jQuery(curRow).parent().parent().append( '<td><span class="btn btn-small " id="remove" onclick="removeRow(this);" title="<?php echo JText::_('COM_TMT_TEST_FORM_DELETE'); ?>"><i class="icon-trash"> </i></span></td>' );
				jQuery(window.parent.document.getElementById("idIframe_<?php echo $this->unique ?>").contentWindow.marks_tr).before( newRow );
				jQuery( window.parent.document.getElementById("idIframe_<?php echo $this->unique ?>").contentWindow.question_paper ).show();
			}
		});

		if (typeof window.parent.document.getElementById("idIframe_<?php echo $this->unique ?>").contentWindow.hideDynamicDiv != "undefined")
		{
			window.parent.document.getElementById("idIframe_<?php echo $this->unique ?>").contentWindow.hideDynamicDiv();
		}
		window.parent.document.getElementById("idIframe_<?php echo $this->unique ?>").contentWindow.closePopup();
	}
	else
	{
		Joomla.submitform(task);
	}
}
</script>

<div id="tmt_questions" class="row-fluid">
	<div class="span12">

		<form method="post" name="adminForm" id="adminForm">
			<div class="top-heading pickQuesalign">
				<!-- set componentheading -->
				<button type="button" class="close" onclick="closebackendPopup(1);" data-dismiss="modal" aria-hidden="true">×</button>
				<strong class="componentheading"><h2><?php echo JText::_('COM_TMT_FORM_TEST_ADD_QUESTIONS');?></h2></strong>
				<hr/>
				<!--Header containing filters-->

					<div class="row-fluid">
						<div class="filteralign">
							<?php
								// Search tools bar
								echo JLayoutHelper::render('joomla.searchtools.default', array('view' => $this));
							?>
						</div>

							<input type="hidden" name="filter_order" value="<?php echo $this->filter_order; ?>" />
							<input type="hidden" name="filter_order_Dir" value="<?php echo $this->filter_order_Dir; ?>" />

							<input type="hidden" name="option" value="com_tmt" />
							<input type="hidden" name="view" value="questions" />
							<input type="hidden" name="controller" value="" />

							<input type="hidden" name="task" value="" />
							<input type="hidden" name="boxchecked" value="0" />
							<?php echo JHtml::_( 'form.token' ); ?>

					</div><!--row-fluid-->
					<div class="row-fluid add-ques-btn-div">
							<div class="btn-group clearfix pull-right">
									<button type="button" class="btn btn-primary com_tmt_button" onclick="Joomla.submitbutton('insertQuestions')">
											<span class="icon-apply"></span>&#160;<?php echo JText::_('COM_TMT_ADD_QUESTIONS') ?>
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
								<?php //echo JHtml::_('grid.checkall','', 'COM_TMT_CHECK_ALL');
								echo JHtml::_('grid.checkall'); ?>
							</th>
							<th>
								<?php echo JHtml::_('grid.sort', 'COM_TMT_Q_LIST_TITLE', 'title', $this->filter_order_Dir, $this->filter_order ); ?>
							</th>
							<th class="nowrap hidden-phone com_tmt_width20">
								<?php echo JHtml::_('grid.sort', 'COM_TMT_Q_LIST_CATEGORY', 'category', $this->filter_order_Dir, $this->filter_order ); ?>
							</th>
							<th class="nowrap hidden-phone center com_tmt_width20">
								<?php echo JHtml::_('grid.sort', 'COM_TMT_Q_LIST_TYPE', 'type', $this->filter_order_Dir, $this->filter_order ); ?>
							</th>
							<th class="nowrap center com_tmt_width1">
								<?php echo JHtml::_('grid.sort', 'COM_TMT_Q_LIST_MARKS', 'marks', $this->filter_order_Dir, $this->filter_order ); ?>
							</th>
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
									<?php echo JHtml::_('grid.id', $i, $row->id ); ?>
								</td>
								<td>
									<label class="block" for="cb<?php echo $i; ?>"><?php echo htmlentities($row->title); ?></label>
								</td>
								<td class="small nowrap hidden-phone">
									<label class="block" for="cb<?php echo $i; ?>"><?php echo $row->category; ?></label>
								</td>
								<td class="small nowrap hidden-phone">
									<label class="block" for="cb<?php echo $i; ?>"><?php echo $row->type; ?></label>
								</td>
								<td class="small nowrap center" name="td_marks">
									<label class="block" for="cb<?php echo $i; ?>"><?php echo $row->marks; ?></label>
								</td>
							</tr>
						<?php
						}//end if
						?>
					</tbody>
				</table>

				<!-- show message if no items found -->
				<?php if (empty($this->items)) : ?>
					<div class="alert"><?php echo JText::_('COM_TMT_Q_LIST_MSG_NO_Q_FOUND_TO_ADD');?></div>
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


