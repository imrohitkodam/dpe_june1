<?php
/**
 * @version     1.0.0
 * @package     com_tmt
 * @copyright   Copyright (C) 2013. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Techjoomla <contact@techjoomla.com> - http://techjoomla.com
 */
defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Layout\LayoutHelper;

HTMLHelper::_('behavior.keepalive');
HTMLHelper::_('bootstrap.tooltip');

$document=Factory::getDocument();
$document->addStylesheet(Uri::root().'components/com_tmt/assets/css/tmt.css');
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
?>

<div id="tmt_tests" class="row-fluid">

<?php if(!empty($this->lesson_id)): ?>
	<button type="button" class="close" onclick="closebackendPopup(1);" data-dismiss="modal" aria-hidden="true">×</button>
		<!-- set componentheading -->
		<h2 class="componentheading">
				<?php echo Text::_('COM_TMT_ADDTOLESSON_TESTS_PAGE_HEADING');?>
		</h2>
<?php endif; ?>


<form action="<?php echo Route::_('index.php?option=com_tmt&view=tests'); ?>" method="post" name="adminForm" id="adminForm">
<?php if (!empty( $this->sidebar)) : ?>
	<div id="j-sidebar-container" class="span2">
		<?php echo $this->sidebar; ?>
	</div>
	<div id="j-main-container" class="span10">
<?php else : ?>
	<div id="j-main-container">
<?php endif; ?>
		<?php
		// Search tools bar
		echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this));
		?>
		<?php if (empty($this->items)) : ?>
			<div class="alert alert-no-items">
				<?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
			</div>
		<?php else : ?>

					<div class="row-fluid">
							<?php
							$addquiz_hide_elements = '';
							if(!empty($this->addquiz)):
								$addquiz_hide_elements = 'style="display:none;"';
							endif;
							 ?>


							<table class="category table table-striped table-bordered table-hover">

								<thead>
									<tr>

										<th class="center com_tmt_width1" <?php echo $addquiz_hide_elements; ?> >
											<?php echo HTMLHelper::_('grid.checkall','',Text::_( 'COM_TMT_CHECK_ALL')); ?>
										</th>

										<th>
											<?php echo HTMLHelper::_('grid.sort', Text::_( 'COM_TMT_TEST_TITLE'), 'title', $listDirn, $listOrder ); ?>
										</th>

										<th class="center hidden-phone com_tmt_width5 nowrap">
											<?php echo HTMLHelper::_('grid.sort', Text::_( 'COM_TMT_CREATED_ON'), 'a.created_on', $listDirn, $listOrder ); ?>
										</th>

										<th <?php echo $addquiz_hide_elements; ?> class="nowrap center hidden-phone com_tmt_width5">
											<?php echo Text::_( 'COM_TMT_TEST_RESPONSES'); ?>
										</th>

										<th <?php echo $addquiz_hide_elements; ?> class="nowrap center hidden-phone com_tmt_width5">
											<?php echo HTMLHelper::_('grid.sort', Text::_( 'Rejected'), 'rejected', $listDirn, $listOrder ); ?>
										</th>

										<th <?php echo $addquiz_hide_elements; ?> class="nowrap center hidden-phone com_tmt_width5">
											<?php echo HTMLHelper::_('grid.sort', Text::_( 'Completed'), 'completed', $listDirn, $listOrder ); ?>
										</th>

										<th <?php echo $addquiz_hide_elements; ?> class="nowrap center hidden-phone com_tmt_width5">
											<?php echo HTMLHelper::_('grid.sort', Text::_( 'COM_TMT_PUBLISHED'), 'state', $listDirn, $listOrder ); ?>
										</th>

										<th class="nowrap center com_tmt_width5">
											<?php echo HTMLHelper::_('grid.sort', Text::_( 'COM_TMT_TEST_MARKS'), 'total_marks', $listDirn, $listOrder ); ?>
										</th>
										<th class="nowrap center com_tmt_width5">
											<?php echo HTMLHelper::_('grid.sort', Text::_( 'COM_TMT_TEST_TIME'), 'time_duration', $listDirn, $listOrder ); ?>

										</th>
										<th class="nowrap center com_tmt_width5">
											<?php echo HTMLHelper::_('grid.sort', 'COM_TMT_TEST_QUES', 'questions_count', $listDirn, $listOrder ); ?>
										</th>

										<th <?php echo $addquiz_hide_elements; ?> class="center hidden-phone com_tmt_width15">
											<?php echo Text::_( 'COM_TMT_TEST_VIEW_REPORTS'); ?>
										</th>

									</tr>
								</thead>

								<tbody>
									<?php
									$n=count( $this->items );
									for($i=0; $i < $n ; $i++)
									{
										$row=$this->items[$i];
										if(!empty($this->addquiz) && $row->state){
											if(empty($this->addquiz)){ //checking if called from LMS
												$link=Route::_('index.php?option=com_tmt&view=test&id='.$row->id.'&Itemid='.$this->create_test_itemid,false);
											}
											else{
												$link = 'javascript:void(0)" onclick="jQuery(\'.quiz_id\').val(\''.trim($row->id).'\'); Joomla.submitbutton(\'tests.assign\'); ';
											}
											?>
											<tr>

												<td class="center" <?php echo $addquiz_hide_elements; ?>><?php echo HTMLHelper::_('grid.id', $i, $row->id ); ?></td>

												<td>
													<input type="hidden" class="quiz_id" name="quiz_id" value="" />
													<a href="<?php echo $link; ?>" title="<?php echo (empty($this->addquiz)) ? Text::_('COM_TMT_EDIT') : Text::_('COM_TMT_ASSIGN_TEST_TO_LESSON_ONHOVER') ;  ?>">
														<?php echo $row->title; ?>
													</a>
												</td>

												<td class="center small nowrap hidden-phone com_tmt_width5">
													<span class="badge badge-info">
														<?php echo $row->created_on;?>
													</span>
												</td>

												<td <?php echo $addquiz_hide_elements; ?> class="small center nowrap hidden-phone com_tmt_width5">
													<?php echo $row->responses; ?>
												</td>
												<td <?php echo $addquiz_hide_elements; ?> class="center"><?php echo $row->completed;?></td>
												<td <?php echo $addquiz_hide_elements; ?> class="center"><?php echo $row->rejected;?></td>

												<!-- end --->

												<td <?php echo $addquiz_hide_elements; ?> class="small center hidden-phone com_tmt_width5">

													<?php
														if($row->questions_count): ?>
															<a class="btn btn-micro active hasTooltip"
															href="javascript:void(0);"
															title="<?php echo ( $row->state ) ? Text::_('COM_TMT_UNPUBLISH') : Text::_('COM_TMT_PUBLISH'); ;?>"
															onclick="document.adminForm.cb<?php echo $i;?>.checked=1; document.adminForm.boxchecked.value=1; Joomla.submitbutton('<?php echo ( $row->state ) ? 'tests.unpublish' : 'tests.publish';?>');">
																<i class=<?php echo ( $row->state ) ? "icon-publish" : "icon-unpublish";?> > </i>
															</a>
														<?php else:?>
															<span class="text text-warning">
																<?php echo Text::_('COM_TMT_TESTS_MSG_ADD_Q_TO_PUB'); ?>
															</span>
														<?php endif; ?>


												</td>

												<td class="small center nowrap com_tmt_width5" >
													<?php echo Text::sprintf('COM_TMT_TEST_TIME_TEXT', $row->time_duration); ?>
												</td>
												<td class="small center nowrap com_tmt_width5" >
													<?php echo $row->questions_count; ?>
												</td>

												<td class="small center nowrap com_tmt_width5" >
													<?php echo $row->total_marks; ?>
												</td>

												<?php
												$report_link = Route::_('index.php?option=com_tmt&view=testreports&testname='.$row->id.'&Itemid='.$this->testreport_itemid,false);
												?>

												<td <?php echo $addquiz_hide_elements; ?> class="small center hidden-phone com_tmt_width15">
													<?php if($row->state):
															if($row->questions_count): ?>
																<a class="" href="<?php echo $report_link; ?>">
																	<?php echo Text::_('COM_TMT_TEST_VIEW_REPORTS'); ?>
																</a>
															<?php else:
																echo Text::_('COM_TMT_NA');
															endif; ?>
													<?php else:
														echo Text::_('COM_TMT_NA');
													endif; ?>
												</td>

											</tr>
									<?php }//end if?>
								<?php }//end for?>
								</tbody>
							</table>

					</div><!--row-fluid-->
				<?php endif; ?>
					<div class="row-fluid">
							<div class="pagination">
								<?php echo $this->pagination->getListFooter(); ?>
							</div>
							<hr class="hr hr-condensed"/>
					</div><!--row-fluid-->


					<input type="hidden" name="task" value="" />
					<input type="hidden" name="boxchecked" value="0" />
					<?php echo HTMLHelper::_('form.token'); ?>
				</form>
</div><!--row-fluid-->

