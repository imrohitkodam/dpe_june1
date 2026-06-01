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
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
 
 HTMLHelper::addIncludePath(JPATH_COMPONENT.'/helpers/html');
 
 if (JVERSION >= '3.0')
 {
 	HTMLHelper::_('bootstrap.tooltip');
 	HTMLHelper::_('behavior.multiselect');
 	HTMLHelper::_('formbehavior.chosen', 'select');
 }
 HTMLHelper::_('bootstrap.renderModal');
 // Import CSS
 $document = Factory::getDocument();
 
 // Import js
 HTMLHelper::script(Uri::base() . '/administrator/components/com_tjlms/assets/js/tjlms.js', true);
 
 $user	= Factory::getUser();
 $userId	= $user->get('id');
 $listOrder	= $this->state->get('list.ordering');
 $listDirn	= $this->state->get('list.direction');
 $canOrder	= $user->authorise('core.edit.state', 'com_tjlms');
 $saveOrder	= $listOrder == 'a.ordering';
 if ($saveOrder)
 {
 	$saveOrderingUrl = 'index.php?option=com_tjlms&task=courses.saveOrderAjax&tmpl=component';
 	HTMLHelper::_('sortablelist.sortable', 'courseList', 'adminForm', strtolower($listDirn), $saveOrderingUrl);
 }
 //~ $sortFields = $this->getSortFields();
 
 $input = Factory::getApplication()->input;
 $defaultclass = !($input->get('course_cat','','STRING')) ? "class='catvisited'" : '';
 $active_cat = '';
 $course_cat = $input->get('course_cat', '', 'INT');
 $filter_menu_category = $this->state->get('filter.menu_category');
 $tjlmsparams = $this->tjlmsparams;
 
 $category_listHTML = '';
 
 $renderer	= $document->loadRenderer('module');
 $modules = ModuleHelper::getModules( 'tjlms_category' );
 
 ob_start();
 
 foreach ($modules as $module)
 {
 	$category_listHTML .=  $renderer->render($module);
 }
 
 ob_get_clean();
 
 $filters = '';
 $renderer	= $document->loadRenderer('module');
 $modules = ModuleHelper::getModules( 'tjlms_filters' );
 
 ob_start();
 foreach ($modules as $module)
 {
 	$module->params = json_decode($module->params);
 	$module->params->without_form = true;
 	$module->params = json_encode($module->params);
 	$filters .=  $renderer->render($module);
 }
 
 ob_get_clean();
 
 foreach ($this->course_cats as $ind => $cat)
 {
 
 	if (isset($course_cat) && !empty($course_cat))
 	{
 		if ($course_cat == $cat->value)
 		{
 			$catclass = "class='catvisited'" ;
 			$active_cat = $cat->text;
 		}
 	}
 	else if (isset($filter_menu_category))
 	{
 
 		if ($filter_menu_category == $cat->value)
 		{
 			$catclass = "class='catvisited'" ;
 			$active_cat = $cat->text;
 		}
 	}
 
 }
 
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
 
 <div class="row-fluid tjlms_courses_head ">
 	<div class="tjlms_head_title pull-left">
 		<h3><?php echo Text::_("COM_TJLMS_MY_COURSES")?></h3>
 		</div>
 </div>
 <div class="<?php echo COM_TJLMS_WRAPPER_DIV ?>">
 <form action="" method="post" name="adminForm" id="adminForm">
 
 	<!-- Display message if no data found-->
 	
 		<div class="row-fluid tjlms-filters">
 
 			<div  class="span11">
 				<?php echo $filters; ?>
 			</div>
 			<div  class="span1">
 
 				<?php if (JVERSION >= '3.0') : ?>
 				<!--<form name="adminForm11" id="adminForm11" class="form-validate" method="post">-->
 						<div class="btn-group pull-right">
 							<label for="limit" class="element-invisible">
 								<?php echo Text::_('COM_TJLMS_SEARCH_SEARCHLIMIT_DESC'); ?>
 							</label>
 							<?php echo $this->pagination->getLimitBox(); ?>
 						</div>
 				<!--</form>-->
 					<?php endif; ?>
 
 			</div>
 		</div>
 
 		<div class="row-fluid">
 
 			<?php if($this->tjlmsparams->get('filter_alignment','','STRING') == 'left'): ?>
 			<div class="span3 hidden-phone hidden-tablet">
 
 				<!-- for category list-->
 				<?php echo $category_listHTML; ?>
 			</div>
 			<?php endif; ?>
 
 
 		<div class="clearfix"> </div>
 		<?php if (empty($this->items)) : ?>
 			<div class="alert alert-no-items">
 				<?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
 			</div>
 		<?php else : ?>
 		<div class="tjlms-tbl">
 			<table class="table table-striped" id="courseList">
 				<thead>
 					<tr>
 <!--
 					<?php if (isset($this->items[0]->ordering)): ?>
 
 						<th width="1%" class="nowrap center hidden-phone">
 							<?php echo HTMLHelper::_('grid.sort', '<i class="icon-menu-2"></i>', 'a.ordering', $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING'); ?>
 						</th>
 					<?php endif; ?>
 						<th width="1%" class="hidden-phone">
 							<input type="checkbox" name="checkall-toggle" value="" title="<?php echo Text::_('JGLOBAL_CHECK_ALL'); ?>" onclick="Joomla.checkAll(this)" />
 						</th>
 
 					<?php if (isset($this->items[0]->state)): ?>
 						<th width="1%" class="nowrap center">
 							<?php echo HTMLHelper::_('grid.sort', 'JSTATUS', 'a.state', $listDirn, $listOrder); ?>
 						</th>
 					<?php endif; ?>
 -->
 
 					<th class='left'>
 					<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_COURSES_TITLE', 'a.title', $listDirn, $listOrder); ?>
 					</th>
 
 <!--
 					<th width="8%" class='left'>
 					<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_COURSES_CREATED_BY', 'a.created_by', $listDirn, $listOrder); ?>
 					</th>
 -->
 					<th width="8%"><?php
 							echo JHTML::tooltip(Text::_('COM_TJLMS_TOTAL_ENROLLED_USERS'), '','', Text::_('COM_TJLMS_TOTAL_ENROLLED_USERS'));
 					?></th>
 
 <!--
 					<th width="10%" class='left'>
 					<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_COURSES_START_DATE', 'a.start_date', $listDirn, $listOrder); ?>
 					</th>
 -->
 
 					<th width="8%"><?php
 							echo JHTML::tooltip(Text::_('COM_TJLMS_ACCESS_LEVEL'), '','', Text::_('COM_TJLMS_ACCESS_LEVEL'));
 					?></th>
 
 			<?php if($this->tjlmsparams->get('allow_paid_courses') == 1){ ?>
 
 					<th width="5%" class='left'>
 					<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_COURSES_TYPE', 'a.type', $listDirn, $listOrder); ?>
 					</th>
 
 					<th width="8%" class='left'>
 						<?php	echo JHTML::tooltip(Text::_('COM_TJLMS_SUBSCRIPTION_PLAN'), '','', Text::_('COM_TJLMS_SUBSCRIPTION_PLAN'));
 					?></th>
 			<?php } ?>
 
 					<th width="5%"></th>
 
 					<?php if (isset($this->items[0]->id)): ?>
 						<th width="1%" class="nowrap center hidden-phone">
 							<?php echo HTMLHelper::_('grid.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
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
 
 				<?php foreach ($this->items as $i => $item) :
 					$ordering   = ($listOrder == 'a.ordering');
 					$canCreate	= $user->authorise('core.create',		'com_tjlms');
 					$canEdit	= $user->authorise('core.edit',			'com_tjlms');
 					$canCheckin	= $user->authorise('core.manage',		'com_tjlms');
 					$canChange	= $user->authorise('core.edit.state',	'com_tjlms');
 
 					// Needed for enroll new user.
 					$enrolment_link = JURI::root() . 'index.php?option=com_tjlms&view=enrolment&tmpl=component&course_id=' . $item->id . '&course_al=' . $item->access;
 					?>
 					<tr class="row<?php echo $i % 2; ?> tjlms-courses-list">
 <!--
 
 							<?php if (isset($this->items[0]->ordering)): ?>
 
 								<td class="order nowrap center hidden-phone">
 								<?php if ($canChange) :
 									$disableClassName = '';
 									$disabledLabel	  = '';
 									if (!$saveOrder) :
 										$disabledLabel    = Text::_('JORDERINGDISABLED');
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
 -->
 <!--
 
 								<td class="center hidden-phone">
 									<?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
 								</td>
 -->
 
 
 <!--
 							<?php if (isset($this->items[0]->state)): ?>
 								<td class="center ">
 									<div class="btn-group">
 										<?php echo HTMLHelper::_('jgrid.published', $item->state, $i, 'courses.', $canChange, 'cb'); ?>
 
 										<?php //echo JHtml::_('TjlmsAdministrator.featured', $i, $canChange, $item->featured); ?>
 									</div>
 								</td>
 							<?php endif; ?>
 -->
 
 							<td>
 							<?php if (isset($item->checked_out) && $item->checked_out) : ?>
 								<?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'courses.', $canCheckin); ?>
 							<?php endif; ?>
 
 							<?php //if ($canEdit) : ?>
 <!--
 								<a href="<?php echo Route::_('index.php?option=com_tjlms&view=course&id='.(int) $item->id); ?>">
 -->
 								<?php echo $this->escape($item->title); ?></a>
 							<?php //else : ?>
 								<?php //echo $this->escape($item->title); ?>
 							<?php //endif; ?>
 								<span class="small break-word">
 									<?php echo Text::sprintf('JGLOBAL_LIST_ALIAS', $this->escape($item->alias)); ?>
 								</span>
 								<div class="small">
 									<?php echo Text::_('JCATEGORY') . ": " . $this->escape($item->cat); ?>
 								</div>
 							</td>
 
 <!--
 							<td>
 								<?php echo $item->created_by; ?>
 							</td>
 -->
 
 							<td class="tjlmscenter">
 								<?php if ($item->state == 1 && $item->cat_status == 1)
 								{
 								echo $item->enrolled_users; ?>
 								<?php
 								}
 								else
 								{
 									echo '-';
 								}
 								?>
 
 							</td>
 <!--
 							<td>
 								<?php echo $item->start_date; ?>
 							</td>
 -->
 							<td>
 								<?php echo $item->access_level_title; ?>
 							</td>
 
 					<?php if($this->tjlmsparams->get('allow_paid_courses') == 1){ ?>
 							<td>
 								<?php if($item->type == 0)
 										{
 											echo Text::_('COM_TJLMS_FREE');
 										}
 										else
 										{
 											echo Text::_('COM_TJLMS_PAID');
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
 									$date = Factory::getDate('now');
 
 									$InactiveBtn = '';
 
 									if ($item->state != 1 || $item->start_date > $date)
 									{
 										$InactiveBtn = 'disabled inactiveLink';
 									}
 									?>
 									<a title="<?php echo Text::_( 'COM_TJLMS_ENROLL_USER' ); ?> "  onclick="opentjlmsSqueezeBox('<?php echo $enrolment_link; ?>')" class="modal  btn enroll_users <?php echo $InactiveBtn; ?>">
 										<i class="fa fa-users"></i>
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
 		<?php endif ; ?>
 		<input type="hidden" name="task" value="" />
 		<input type="hidden" name="boxchecked" value="0" />
 		<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
 		<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />
 		<?php echo HTMLHelper::_('form.token'); ?>
	
 		</div><!--j-main-container-->
 	</div><!--row-fluid-->
 </form>
 
 </div>
 
