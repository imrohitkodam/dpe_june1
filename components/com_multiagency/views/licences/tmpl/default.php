<?php
/**
 * @package    Com_Multiagency
 *
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers/html');
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.multiselect');
HTMLHelper::_('formbehavior.chosen', 'select');

$user = Factory::getUser();
$userId = $user->id;
$listOrder = $this->state->get('list.ordering');
$listDirn = $this->state->get('list.direction');
$canCreate = $user->authorise('core.create', 'com_multiagency') &&
		 file_exists(JPATH_COMPONENT . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'forms' . DIRECTORY_SEPARATOR . 'licenceform.xml');
$canEdit = $user->authorise('core.edit', 'com_multiagency') &&
		 file_exists(JPATH_COMPONENT . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'forms' . DIRECTORY_SEPARATOR . 'licenceform.xml');
$canCheckin = $user->authorise('core.manage', 'com_multiagency');
$canChange = $user->authorise('core.edit.state', 'com_multiagency');
$canDelete = $user->authorise('core.delete', 'com_multiagency');

Text::script('COM_MULTIAGENCY_DELETE_MESSAGE');
HTMLHelper::script( Uri::root().'media/com_multiagency/js/licence.js' );
?>

<form action="<?php echo Route::_('index.php?option=com_multiagency&view=licences'); ?>" method="post" name="adminForm" id="adminForm">
	<div class="page-header">
		<h2><?php echo Text::_("COM_MULTIAGENCY_TITLE_LIST_VIEW_LICENCES");?></h2>
	</div>

	<!--Filters start-->


		<div class="col-xs-12">
		<div id="filter-progress-bar" class="row">
			<div class="col-xs-12 col-sm-6 marginb10">
				<?php // echo LayoutHelper::render('default_filter', array('view' => $this), dirname(__FILE__));
				echo $searchTool = LayoutHelper::render('joomla.searchtools.default', array('view' => $this));
					//echo str_replace("icon-search","glyphicon glyphicon-search", $searchTool);
				 ?>
			</div>
			<div class="col-xxxs-12 col-xs-12 col-sm-6 marginb10 text-right">
							<?php if ($canCreate) : ?>
				<a href="<?php echo Route::_('index.php?option=com_multiagency&task=licenceform.edit&id=0'); ?>" class="btn btn-primary btn-small pull-right"><i class="icon-plus"></i> <?php echo Text::_('COM_MULTIAGENCY_ADD_ITEM_LICENCE'); ?></a>
			<?php endif; ?>
             </div>
		</div>
		</div>
	<!--Filters end-->

	<table class="table table-striped" id="licenceList">
		<?php if (!empty($this->items)) { ?>
			<thead>
				<tr>
					<th><?php echo HTMLHelper::_('grid.sort',  'COM_MULTIAGENCY_LICENCES_ID', 'a.id', $listDirn, $listOrder); ?></th>
					<th><?php echo HTMLHelper::_('grid.sort',  'COM_MULTIAGENCY_LICENCES_COURSE_ID', 'course.title', $listDirn, $listOrder); ?></th>
					<th><?php echo HTMLHelper::_('grid.sort',  'COM_MULTIAGENCY_LICENCE_MULTIAGENCY_HEAD', 'multiagency.title', $listDirn, $listOrder); ?></th>
					<th><?php echo HTMLHelper::_('grid.sort',  'COM_MULTIAGENCY_LICENCES_TYPE', 'a.type', $listDirn, $listOrder); ?></th>
					<th><?php echo HTMLHelper::_('grid.sort',  'COM_MULTIAGENCY_LICENCES_TOTAL_LICENCES', 'a.total_seats', $listDirn, $listOrder); ?></th>
					<th><?php echo HTMLHelper::_('grid.sort',  'COM_MULTIAGENCY_LICENCES_USED_LICENCES', 'a.used_seats', $listDirn, $listOrder); ?></th>
					<th><?php echo HTMLHelper::_('grid.sort',  'COM_MULTIAGENCY_FORM_LBL_LICENCE_START_DATE', 'a.start_date', $listDirn, $listOrder); ?></th>
					<th><?php echo HTMLHelper::_('grid.sort',  'COM_MULTIAGENCY_FORM_LBL_LICENCE_END_DATE', 'a.end_date', $listDirn, $listOrder); ?></th>
					<!--th><?php echo HTMLHelper::_('grid.sort',  'COM_MULTIAGENCY_FORM_LBL_LICENCE_COMMENT', 'a.comment', $listDirn, $listOrder); ?></th-->

					<?php if ($canEdit || $canDelete): ?>
						<th class="center"><?php echo Text::_('COM_MULTIAGENCY_LICENCES_ACTIONS'); ?></th>
					<?php endif; ?>
				</tr>
			</thead>

			<tfoot>
				<tr>
					<td colspan="<?php echo isset($this->items[0]) ? count(get_object_vars($this->items[0])) : 10; ?>">
						<?php echo $this->pagination->getListFooter(); ?>
					</td>
				</tr>
			</tfoot>
			<tbody>
				<?php foreach ($this->items as $i => $item) : ?>
					<?php $canEdit = $user->authorise('core.edit', 'com_multiagency'); ?>
					<?php if (!$canEdit && $user->authorise('core.edit.own', 'com_multiagency')): ?>
						<?php $canEdit = $user->id == $item->created_by; ?>
					<?php endif; ?>
					<tr class="row<?php echo $i % 2; ?>">
						<td><?php echo $item->id; ?></td>
						<?php if (!empty($item->title))
						{?>
						<td><?php echo $this->escape($item->title); ?></td>
						<?php
						}
						else
						{
						?>
						<td><?php echo Text::_('COM_MULTIAGENCY_LICENCE_TYPE_ALL');?></td>
						<?php
						}
						?>
						<td><?php echo $this->escape($item->multiagencyname); ?></td>
						<td><?php echo $this->escape($item->type); ?></td>
						<td><?php echo (int) $item->total_seats; ?></td>
						<td><?php echo $item->used_seats; ?></td>
						<td><?php echo Factory::getDate($item->start_date)->format('d-m-Y');  ?></td>
						<td><?php echo Factory::getDate($item->end_date)->format('d-m-Y'); ?></td>
						<!--td><?php echo $item->comment; ?></td-->

						<?php if ($canEdit || $canDelete): ?>
							<td class="center">
								<?php if (isset($this->items[0]->state)){?>
									<?php $class = ($canChange) ? 'active' : 'disabled'; ?>
										<!--
										<a class="btn btn-micro <?php echo $class; ?>" href="<?php echo ($canChange) ? Route::_('index.php?option=com_multiagency&task=licence.publish&id=' . $item->id . '&state=' . (($item->state + 1) % 2)) : '#'; ?>">
										<?php if ($item->state == 1){ ?>
											<i class="icon-publish"></i>
										<?php } else{ ?>
											<i class="icon-unpublish"></i>
										<?php } ?>
										</a> -->
								<?php } ?>

								<?php if ($canEdit): ?>
									<a href="<?php echo Route::_('index.php?option=com_multiagency&task=licenceform.edit&id=' . $item->id); ?>" class="btn btn-mini" type="button">
										<i class="icon-edit" aria-hidden="true"></i>
									</a>
								<?php endif; ?>

								<?php if ($canDelete): ?>
									<a href="javascript:void(0);" onclick="deleteItem('<?php echo base64_encode($item->id); ?>')" class="btn btn-mini delete-button" type="button"><i class="icon-trash" ></i></a>
								<?php endif; ?>
							</td>
						<?php endif; ?>
					</tr>
				<?php endforeach; ?>
			</tbody>
		<?php } else { ?>
			<div class="clearfix">&nbsp;</div>
			<div class="alert alert-info"><?php echo Text::_("COM_MULTIAGENCY_NO_RECORDS_FOUND");?></div>
		<?php } ?>
	</table>
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="boxchecked" value="0" />
	<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
	<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />
	<?php echo HTMLHelper::_('form.token'); ?>
</form>
