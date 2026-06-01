<?php
/**
 * @version     1.0.0
 * @package     com_tjlms
 * @copyright   Copyright (C) 2014. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      TechJoomla <extensions@techjoomla.com> - www.techjoomla.com
 */

// no direct access
defined('_JEXEC') or die;

JHtml::addIncludePath(JPATH_COMPONENT.'/helpers/html');

if (JVERSION >= '3.0')
{
	JHtml::_('bootstrap.tooltip');
	JHtml::_('behavior.multiselect');
	JHtml::_('formbehavior.chosen', 'select');
}
JHTML::_('behavior.modal');
// Import CSS
$document = JFactory::getDocument();


$user	= JFactory::getUser();
$userId	= $user->get('id');
$listOrder	= $this->state->get('list.ordering');
$listDirn	= $this->state->get('list.direction');
$canOrder	= $user->authorise('core.edit.state', 'com_tjlms');
$saveOrder	= $listOrder == 'a.ordering';
if ($saveOrder)
{
	$saveOrderingUrl = 'index.php?option=com_tjlms&task=courses.saveOrderAjax&tmpl=component';
	JHtml::_('sortablelist.sortable', 'courseList', 'adminForm', strtolower($listDirn), $saveOrderingUrl);
}
$sortFields = $this->getSortFields();
?>
<script type="text/javascript">
	Joomla.orderTable = function() {
		table = document.getElementById("sortTable");
		direction = document.getElementById("directionTable");
		order = table.options[table.selectedIndex].value;
		if (order != '<?php echo $listOrder; ?>') {
			dirn = 'asc';
		} else {
			dirn = direction.options[direction.selectedIndex].value;
		}
		Joomla.tableOrdering(order, dirn, '');
	}
</script>

<?php
//Joomla Component Creator code to allow adding non select list filters
if (!empty($this->extra_sidebar))
{
    $this->sidebar .= $this->extra_sidebar;
}
?>


<div class="<?php echo COM_TJLMS_WRAPPER_DIV ?>">

<form action="<?php echo JRoute::_('index.php?option=com_tjlms&view=courses'); ?>" method="post" name="adminForm" id="adminForm">

	<?php
		ob_start();
		include JPATH_BASE . '/components/com_tjlms/layouts/header.sidebar.php';
		$layoutOutput = ob_get_contents();
		ob_end_clean();
		echo $layoutOutput;

		echo JLayoutHelper::render('joomla.searchtools.default', array('view' => $this));
	?>
	<!-- Display message if no data found-->
		<div class="clearfix"> </div>
		<?php if (empty($this->items)) : ?>
			<div class="alert alert-no-items">
				<?php echo JText::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
			</div>
		<?php else : ?>
		<div class="tjlms-tbl">
			<table class="table table-striped" id="courseList">
				<thead>
					<tr>
					<?php if (isset($this->items[0]->ordering)): ?>
						<th width="1%" class="nowrap center hidden-phone">
							<?php echo JHtml::_('grid.sort', '<i class="icon-menu-2"></i>', 'a.ordering', $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING'); ?>
						</th>
					<?php endif; ?>
						<th width="1%" class="hidden-phone">
							<input type="checkbox" name="checkall-toggle" value="" title="<?php echo JText::_('JGLOBAL_CHECK_ALL'); ?>" onclick="Joomla.checkAll(this)" />
						</th>
					<?php if (isset($this->items[0]->state)): ?>
						<th width="1%" class="nowrap center">
							<?php echo JHtml::_('grid.sort', 'JSTATUS', 'a.state', $listDirn, $listOrder); ?>
						</th>
					<?php endif; ?>

					<th class='left'>
					<?php echo JHtml::_('grid.sort',  'COM_TJLMS_COURSES_TITLE', 'a.title', $listDirn, $listOrder); ?>
					</th>

					<th width="8%" class='left'>
					<?php echo JHtml::_('grid.sort',  'COM_TJLMS_COURSES_CREATED_BY', 'a.created_by', $listDirn, $listOrder); ?>
					</th>

					<?php
                        $canManageEnroll = TjlmsHelper::canManageEnrollment();
                        if($canManageEnroll):?>
						<th width="5%" class='tjlmscenter'>
						<?php echo JHTML::tooltip(JText::_('COM_TJLMS_TOTAL_ENROLLED_USERS'), '','', JText::_('COM_TJLMS_TOTAL_ENROLLED_USERS')); ?>
						</th>
						<?php endif;?>

					<th width="10%" class='left'>
					<?php echo JHtml::_('grid.sort',  'COM_TJLMS_COURSES_START_DATE', 'a.start_date', $listDirn, $listOrder); ?>
					</th>

					<th width="8%"><?php
							echo JHTML::_('grid.sort', ('COM_TJLMS_ACCESS_LEVEL'), 'a.access', $listDirn, $listOrder);
					?></th>

			<?php if($this->tjlmsparams->get('allow_paid_courses') == 1){ ?>

					<th width="5%" class='left'>
					<?php echo JHtml::_('grid.sort',  'COM_TJLMS_COURSES_TYPE', 'a.type', $listDirn, $listOrder); ?>
					</th>

					<th width="8%" class='left'>
						<?php	echo JHTML::tooltip(JText::_('COM_TJLMS_SUBSCRIPTION_PLAN'), '','', JText::_('COM_TJLMS_SUBSCRIPTION_PLAN'));
					?></th>
			<?php } ?>

					<th width="5%"></th>

					<?php if (isset($this->items[0]->id)): ?>
						<th width="1%" class="nowrap center hidden-phone">
							<?php echo JHtml::_('grid.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
						</th>
					<?php endif; ?>

					</tr>
				</thead>
				<tfoot>
				<?php
					if(isset($this->items[0]))
					{
						$colspan = count(get_object_vars($this->items[0]));
					}
					else
					{
						$colspan = 10;
					}
				?>
				<tr>
					<td colspan="<?php echo $colspan ?>">
						<?php echo $this->pagination->getListFooter(); ?>
					</td>
				</tr>
				</tfoot>
				<tbody>
				<?php
				$canCreate 	= $user->authorise('core.create', 'com_tjlms');
				$canManage	= $user->authorise('core.manage', 'com_checkin');
				foreach ($this->items as $i => $item) :
					$ordering 	= ($listOrder == 'a.ordering');
					$manageOwn	= $canCreate && $userId == $item->created_by;
					$canEdit 	= $user->authorise('core.edit', 'com_tjlms.course.' . $item->id) || $manageOwn;
					$canCheckin = $canManage || $item->checked_out == $userId || $item->checked_out == 0;
					$canChange 	= $user->authorise('core.edit.state', 'com_tjlms.course.' . $item->id) || $manageOwn;
					$canManageReport = TjlmsHelper::canManageCourseReport($item->id, $userId, $item->created_by);
					$canEditCat    = $user->authorise('core.edit',       'com_tjlms.category.' . $item->catid);
					$canEditOwnCat = $user->authorise('core.edit.own',   'com_tjlms.category.' . $item->catid) && $item->category_uid == $userId;
					$canEditParCat    = $user->authorise('core.edit',       'com_tjlms.category.' . $item->parent_category_id);
					$canEditOwnParCat = $user->authorise('core.edit.own',   'com_tjlms.category.' . $item->parent_category_id) && $item->parent_category_uid == $userId;
					// Needed for enroll new user.
					$enrolment_link = JRoute::_( 'index.php?option=com_tjlms&view=enrolment&tmpl=component&selectedcourse[]=' . $item->id . '&course_al=' . $item->access );

					// Link for Teachers Course Dashboard
					$courseReportLink = 'index.php?option=com_tjlms&view=teacher_report&tmpl=component&courseid='.$item->id.'&Itemid='.$this->teacherCourseDashboardItemid;
					?>
					<tr class="row<?php echo $i % 2; ?> tjlms-courses-list">

							<?php if (isset($this->items[0]->ordering)): ?>
								<td class="order nowrap center hidden-phone">
								<?php if ($canChange) :
									$disableClassName = '';
									$disabledLabel	  = '';
									if (!$saveOrder) :
										$disabledLabel    = JText::_('JORDERINGDISABLED');
										$disableClassName = 'inactive tip-top';
									endif; ?>
									<span class="sortable-handler hasTooltip <?php echo $disableClassName?>" title="<?php echo $disabledLabel?>">
										<i class="icon-menu"></i>
									</span>
									<input type="text" style="display:none" name="order[]" size="5" value="<?php echo $item->ordering;?>" class="width-20 text-area-order " />
								<?php else : ?>
									<span class="sortable-handler inactive" >
										<i class="icon-menu"></i>
									</span>
								<?php endif; ?>
								</td>
							<?php endif; ?>
								<td class="center hidden-phone">
									<?php echo JHtml::_('grid.id', $i, $item->id); ?>
								</td>
							<?php if (isset($this->items[0]->state)): ?>
								<td class="center ">
									<div class="btn-group">
										<?php echo JHtml::_('jgrid.published', $item->state, $i, 'courses.', $canChange, 'cb'); ?>
										<?php echo JHtml::_('TjlmsAdministrator.featured', $i, $canChange, $item->featured); ?>
									</div>
								</td>
							<?php endif; ?>

							<td>
							<?php if (isset($item->checked_out) && $item->checked_out) : ?>
								<?php echo JHtml::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'courses.', $canCheckin); ?>
							<?php endif; ?>
							<?php if ($canEdit) : ?>
								<a href="<?php echo JRoute::_('index.php?option=com_tjlms&task=course.edit&id='.(int) $item->id); ?>">
								<?php echo $this->escape($item->title); ?></a>
							<?php else : ?>
								<?php echo $this->escape($item->title); ?>
							<?php endif; ?>
								<span class="small break-word">
									<?php echo JText::sprintf('JGLOBAL_LIST_ALIAS', $this->escape($item->alias)); ?>
								</span>
								<div class="small">
									<?php
									$ParentCatUrl = JRoute::_('index.php?option=com_categories&task=category.edit&id=' . $item->parent_category_id . '&extension=com_tjlms');
									$CurrentCatUrl = JRoute::_('index.php?option=com_categories&task=category.edit&id=' . $item->catid . '&extension=com_tjlms');
									$EditCatTxt = JText::_('COM_TJLMS_EDIT_CATEGORY');

										echo JText::_('JCATEGORY') . ': ';

										if ($item->category_level != '1') :
											if ($item->parent_category_level != '1') :
												echo ' &#187; ';
											endif;
										endif;

										if (JFactory::getLanguage()->isRtl())
										{
											if ($canEditCat || $canEditOwnCat) :
												echo '<a class="hasTooltip" href="' . $CurrentCatUrl . '" title="' . $EditCatTxt . '">';
											endif;
											echo $this->escape($item->category_title);
											if ($canEditCat || $canEditOwnCat) :
												echo '</a>';
											endif;

											if ($item->category_level != '1') :
												echo ' &#171; ';
												if ($canEditParCat || $canEditOwnParCat) :
													echo '<a class="hasTooltip" href="' . $ParentCatUrl . '" title="' . $EditCatTxt . '">';
												endif;
												echo $this->escape($item->parent_category_title);
												if ($canEditParCat || $canEditOwnParCat) :
													echo '</a>';
												endif;
											endif;
										}
										else
										{
											if ($item->category_level != '1') :
												if ($canEditParCat || $canEditOwnParCat) :
													echo '<a class="hasTooltip" href="' . $ParentCatUrl . '" title="' . $EditCatTxt . '">';
												endif;
												echo $this->escape($item->parent_category_title);
												if ($canEditParCat || $canEditOwnParCat) :
													echo '</a>';
												endif;
												echo ' &#187; ';
											endif;
											if ($canEditCat || $canEditOwnCat) :
												echo '<a class="hasTooltip" href="' . $CurrentCatUrl . '" title="' . $EditCatTxt . '">';
											endif;
											echo $this->escape($item->category_title);
											if ($canEditCat || $canEditOwnCat) :
												echo '</a>';
											endif;
										}
									?>
								</div>
							</td>

							<td>
								<?php echo $item->created_by_alias; ?>
							</td>

							<?php
		                    $canManageEnroll = TjlmsHelper::canManageEnrollment();
		                    if($canManageEnroll):?>
							<td class="tjlmscenter">
								<?php
								$canManageEnroll = TjlmsHelper::canManageCourseEnrollment($item->id, null, $item->created_by);
								if ($item->state == 1 && $item->cat_status == 1 && $canManageEnroll)
								{
								?>
								<a onclick=opentjlmsSqueezeBox("<?php echo JUri::root().'administrator/index.php?option=com_tjlms&view=manageenrollments&tmpl=component&course_id='.$item->id.'&filter_published=&coursefilter='.$item->id ?>") ><?php echo $item->enrolled_users; ?>
								</a>
								<?php
								}
								else
								{
								echo '-';
								}
								?>
							</td>
							<?php endif;?>
							<td>
								<?php echo $item->start_date; ?>
							</td>
							<td>
								<?php echo $item->access_level_title; ?>
							</td>

					<?php if($this->tjlmsparams->get('allow_paid_courses') == 1){ ?>
							<td>
								<?php if($item->type == 0)
										{
											echo JText::_('COM_TJLMS_FREE');
										}
										else
										{
											echo JText::_('COM_TJLMS_PAID');
										} ?>
							</td>

							<td class="center">
								<?php if($item->type == 0)
										{
											echo '--';
										}
										else
										{
											echo $item->subscription_plans ? $item->subscription_plans:'--';
										} ?>

							</td>
					<?php } ?>

							<td>
								<div class="btn-group">

									<?php
									$canManageMaterial	= TjlmsHelper::canManageCourseMaterial($item->id, null, $item->created_by);
									if(!$canManageMaterial){
										?>
										<a title="<?php echo JText::_('COM_TJLMS_ADD_TRAINING_MATERIAL'); ?>" class="btn disabled inactiveLink" href="#">
										<i class="fa fa-list-ul"></i>
										</a>
										<?php
									}
									else
									{?>
										<a title="<?php echo JText::_('COM_TJLMS_ADD_TRAINING_MATERIAL'); ?>" class="btn" href="<?php echo JRoute::_('index.php?option=com_tjlms&view=modules&course_id='.$item->id); ?>"  >
										<i class="fa fa-list-ul"></i>
										</a>
									<?php
									}
									?>

									<?php
									$canManageEnroll = TjlmsHelper::canManageCourseEnrollment($item->id, null, $item->created_by);
									$date = JFactory::getDate('now');

 									$InactiveBtn = '';

									if ($item->state != 1 || date("Y-m-d", strtotime($item->start_date)) > date("Y-m-d", strtotime($date)) || !$canManageEnroll)
									{
										$InactiveBtn = 'disabled inactiveLink';
									}
									$reportBtn = !$canManageReport ? 'disabled inactiveLink' : '';
									?>
									<a title="<?php echo JText::_( 'COM_TJLMS_ENROLL_OR_ASSIGN_USERS' ); ?> "  onclick="opentjlmsSqueezeBox('<?php echo $enrolment_link; ?>')" class="modal  btn enroll_users <?php echo $InactiveBtn; ?>">
										<i class="fa fa-users"></i>
									</a>
									<a  title="<?php echo JText::_( 'COM_TJLMS_COURSE_REPORT_DASHBOARD' ); ?>" onclick="opentjlmsSqueezeBox('<?php echo $courseReportLink; ?>')" class="modal btn teacher_report <?php echo $reportBtn; ?>">
											<i class="fa fa-bar-chart"></i>
									</a>
								</div>
							</td>

							<?php if (isset($this->items[0]->id)): ?>
								<td class="hidden-phone tjlmscenter">
									<?php echo (int) $item->id; ?>
								</td>
							<?php endif; ?>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
</div>
		<?php endif ; ?>
		<input type="hidden" name="task" value="" />
		<input type="hidden" name="boxchecked" value="0" />
		<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
		<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />
		<?php echo JHtml::_('form.token'); ?>
		</div><!--j-main-container-->
	</div><!--row-fluid-->
</form>

</div>

