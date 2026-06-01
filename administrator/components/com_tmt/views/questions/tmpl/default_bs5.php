<?php
/**
 * @version     1.0.0
 * @package     com_tmt
 * @copyright   Copyright (C) 2023. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Techjoomla <contact@techjoomla.com> - http://techjoomla.com
 */
defined('_JEXEC') or die;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;

// HTMLHelper::_('bootstrap.tooltip');
// HTMLHelper::_('behavior.multiselect');

// $jversion = new Joomla\CMS\Version;
// if (!$jversion->isCompatible('4.0'))
// {
// 	HTMLHelper::_('formbehavior.chosen', 'select');
// 	HTMLHelper::_('behavior.tooltip');
// }

jimport('techjoomla.common');

echo JHtmlBootstrap::renderModal('questioncsv', $this->questions_csv_params);

$this->techjoomlacommon = new TechjoomlaCommon;
$lmsparams   = ComponentHelper::getParams('com_tjlms');
$date_format_show = $lmsparams->get('date_format_show', 'Y-m-d H:i:s');

$document=Factory::getDocument();
$document->addStylesheet(Uri::root(true).'/components/com_tmt/assets/css/tmt.css');
$document->addStyleSheet(Uri::root(true).'/media/com_tjlms/font-awesome/css/font-awesome.min.css');

$user	= Factory::getUser();

/*
<script type="text/javascript">
	jQuery(document).ready(function(){
		jQuery('#tmt_questions-csv').on('hidden', function () {
			   window.location.reload(true);
			});
	});
	//Joomla.submitbutton = function(task)
	{
		/*if(task=='questions.create' || task=='questions.backToDashboard' || task=='questions.edit')
		{
			Joomla.submitform(task);
		}
		else
		{
			if(document.adminForm.boxchecked.value==0)
			{
				alert('<?php echo Text::_("COM_TMT_MESSAGE_SELECT_ITEMS");?>');
				return false;
			}
			switch(task)
			{
				case 'questions.publish':
					jQuery('#tmt_questions .btn').prop('disabled', true);
					Joomla.submitform(task);
				break
				case 'questions.unpublish':
					jQuery('#tmt_questions .btn').prop('disabled', true);
					Joomla.submitform(task);
				break
				case 'questions.delete':
					jQuery('#tmt_questions .btn').prop('disabled', true);
					Joomla.submitform(task);
				break;
				case 'questions.trash':
					jQuery('#tmt_questions .btn').prop('disabled', true);
					Joomla.submitform(task);
				break
			}
		}
	}
</script>
*/
?>

<div id="tmt_questions" class="tjlms-wrapper row">
		<!-- set componentheading
		<h2 class="componentheading"><?php echo Text::_('COM_TMT_Q_LIST_HEADING_MANAGE');?></h2>-->

			<!-- show form/items if items found -->
		<form action="<?php echo Route::_('index.php?option=com_tmt&view=questions'); ?>" method="post" name="adminForm" id="adminForm" class="form-inline">

				<?php
					// Search tools bar
					echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this));
				?>

				<input type="hidden" name="filter_order" value="<?php echo $this->filter_order; ?>" />
				<input type="hidden" name="filter_order_Dir" value="<?php echo $this->filter_order_Dir; ?>" />

				<input type="hidden" name="option" value="com_tmt" />
				<input type="hidden" name="view" value="questions" />
				<input type="hidden" name="controller" value="" />

				<input type="hidden" name="task" value="" />
				<input type="hidden" name="boxchecked" value="0" />

				<!-- show message if no items found -->
				<?php if (empty($this->items)) : ?>
					<div class="alert Qbankalign"><?php echo Text::_('COM_TMT_Q_LIST_MSG_NO_Q_FOUND');?></div>
					<?php return false;	?>
				<?php endif; ?>
				<?php echo HTMLHelper::_( 'form.token' ); ?>

					<div class="row">
						<div class="col-md-12 tjlms-tbl">
							<table class="table table-striped left_table">
								<thead>
									<tr>
										<th class="center com_tmt_width1">
											<input type="checkbox" name="checkall-toggle" value="" title="<?php echo Text::_('JGLOBAL_CHECK_ALL'); ?>" onclick="Joomla.checkAll(this)" />
										</th>
										<th class="center hidden-phone com_tmt_width1">
											<?php echo HTMLHelper::_('grid.sort', 'JSTATUS', 'state', $this->filter_order_Dir, $this->filter_order ); ?>
										</th>
										<th class="questionTh">
											<?php echo HTMLHelper::_('grid.sort', 'COM_TMT_Q_LIST_TITLE', 'title', $this->filter_order_Dir, $this->filter_order ); ?>
										</th>
										<th class="center nowrap com_tmt_width5">
											<?php echo HTMLHelper::_('grid.sort', 'COM_TMT_DIFFICULTY_LEVEL', 'level', $this->filter_order_Dir, $this->filter_order ); ?>
										</th>
										<th class="center nowrap com_tmt_width5">
											<?php echo HTMLHelper::_('grid.sort', 'COM_TMT_CREATED_ON', 'created_on', $this->filter_order_Dir, $this->filter_order ); ?>
										</th>
										</th>
										<?php if ($this->canManageQB > 0) {?>
										<th class="center nowrap com_tmt_width5">
											<?php echo HTMLHelper::_('grid.sort', 'COM_TMT_QUESTIONS_CREATED_BY', 'a.created_by', $this->filter_order_Dir, $this->filter_order ); ?>
										</th>
										<?php } ?>
										<th class="com_tmt_width5">
											<?php echo HTMLHelper::_('grid.sort', 'COM_TMT_Q_LIST_CATEGORY', 'category', $this->filter_order_Dir, $this->filter_order ); ?>
										</th>
										<th class="com_tmt_width10">
											<?php echo HTMLHelper::_('grid.sort', 'COM_TMT_Q_LIST_TYPE', 'type', $this->filter_order_Dir, $this->filter_order ); ?>
										</th>
										<th class="com_tmt_width10">
											<?php echo HTMLHelper::_('grid.sort', 'COM_TMT_Q_LIST_GRADING_TYPE', 'gradingtype', $this->filter_order_Dir, $this->filter_order ); ?>
										</th>
										<th class="center com_tmt_width5">
											<?php echo HTMLHelper::_('grid.sort', 'COM_TMT_Q_LIST_MARKS', 'marks', $this->filter_order_Dir, $this->filter_order ); ?>
										</th>
										<th class="center com_tmt_width5">
											<?php echo HTMLHelper::_('grid.sort', 'COM_TMT_FORM_LBL_TEST_ID', 'id', $this->filter_order_Dir, $this->filter_order ); ?>
										</th>
									</tr>
								</thead>

								<tbody>
									<?php
									$n=count( $this->items );
									for($i=0; $i < $n ; $i++)
									{
										$row		= $this->items[$i];
										$link		= Route::_('index.php?option=com_tmt&task=question.edit&id='.$row->id,false);
										$canChange	= ($this->canManageQB == 1) || ($this->canManageQB == -1 && $row->created_by == $this->user_id);
										?>
										<tr>
											<td class="center com_tmt_width1">
												<?php echo HTMLHelper::_('grid.id', $i, $row->id ); ?>
											</td>
											<td class="center hidden-phone com_tmt_width1">
												<div class="btn-group">
													<?php echo HTMLHelper::_('jgrid.published', $row->state, $i, 'questions.', $canChange, 'cb'); ?>
												</div>
											</td>
											<td class="questionTd">
												<a href="<?php echo $link; ?>" title="<?php echo Text::_('COM_TMT_EDIT'); ?>">
													<?php echo $this->escape($row->title); ?>
												</a>
												<div class="small">
													<span class="break-word">
														<?php echo Text::sprintf('COM_TMT_QUESTIONS_QUESTION_ALIAS', $this->escape($row->alias)); ?>
													</span>
												</div>
												<!--
												<div class="small">
													<?php // echo JText::_('JCATEGORY') . ": " . $this->escape($row->category); ?>
												</div>
												-->
											</td>
											<td class="center com_tmt_width5">
												<?php echo  $this->escape(ucfirst($row->level)); ?>
											</td>
											<td class="center small nowrap com_tmt_width5">
												<!--span class="badge badge-info"-->
													<?php {
													if ($row->created_on == '0000-00-00 00:00:00')
													{
														$row->created_on = '-';
														echo $row->created_on;
													}

													if ($row->created_on != '-')
													{
														echo $this->techjoomlacommon->getDateInLocal($row->created_on, 0, $date_format_show);
													}
												} //echo JFactory::getDate($row->created_on)->Format(JText::_('DATE_FORMAT_LC4')); ?>
												<!--/span-->
											</td>
											<?php if ($this->canManageQB > 0) {?>
											<td class="center small com_tmt_width5">
												<?php echo $row->created_by_alias; ?>
											</td>
											<?php } ?>
											<td class="small com_tmt_width5">
												<?php echo $row->category; ?>
											</td>
											<td class="small com_tmt_width10">
												<?php echo $this->escape($row->type); ?>
											</td>
											<td class="small com_tmt_width10">
												<?php echo $this->escape(ucfirst($row->gradingtype)); ?>
											</td>
											<td class="center small com_tmt_width5">
												<?php echo $row->marks; ?>
											</td>
											<td class="center small com_tmt_width5">
												<?php echo $row->id; ?>
											</td>
										</tr>
									<?php
									}//end if
									?>
								</tbody>
							</table>

						</div><!--col-md-12-->
					</div><!--row-->

					<div class="row">
						<div class="col-md-12">
							<?php echo $this->pagination->getListFooter(); ?>
							<hr class="hr hr-condensed"/>
						</div><!--col-md-12-->
					</div><!--row-->
		</div><!--j-main-container-->
	</form>
</div><!--row-fluid-->
