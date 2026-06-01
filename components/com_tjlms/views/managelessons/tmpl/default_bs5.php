<?php
/**
 * @package     Shika
 * @subpackage  com_tjlms
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;

use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\LayoutHelper;

HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers/html');
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.multiselect');
HTMLHelper::_('formbehavior.chosen', '.multipleAuthors', null, array('placeholder_text_multiple' => Text::_('JOPTION_SELECT_AUTHOR')));
HTMLHelper::_('formbehavior.chosen', 'select');

$listOrder     = $this->state->get('list.ordering');
$listDirn      = $this->escape($this->state->get('list.direction'));
$filter_format = $this->state->get('filter.format');
$saveOrder     = $listOrder == 'a.ordering';

if ($saveOrder)
{
	$saveOrderingUrl = 'index.php?option=com_tjlms&task=managelessons.saveOrderAjax&tmpl=component';
	HTMLHelper::_('sortablelist.sortable', 'lessonsList', 'adminForm', strtolower($listDirn), $saveOrderingUrl);
}

Text::script('JOPTION_SELECT_AUTHOR');

?>

<div class="<?php echo COM_TJLMS_WRAPPER_DIV; ?> tjBs3">
	<div class="row">
		<h2><?php echo Text::_("COM_TJLMS_MANAGELESSONS_VIEW_DEFAULT_TITLE"); ?></h2>
	</div>
	<fieldset class="btn-toolbar center clearfix" id="tjtoolbar">
		<?php echo $this->toolbar->render();?>
	</fieldset>

	<hr class="hr hr-condensed"/>
	<form action="<?php echo Route::_('index.php?option=com_tjlms&view=managelessons'); ?>" method="post" name="adminForm" id="adminForm">
		<?php
			$searchData = array('view' => $this);
			$searchData['options']['filtersHidden'] = 1;

			/*
			Condition `count($this->activeFilters) > 1` is needed as config "in_lib"
			is always set in this view, therefore the filters always remains open
			So to hide filters until set by the user, we have used this condition
			*/
			if ($this->activeFilters && count($this->activeFilters) > 1)
			{
				$searchData['options']['filtersHidden'] = 0;
			}

			echo LayoutHelper::render('joomla.searchtools.default', $searchData);
		?>

		<div class="clearfix mb-10"> </div>

		<?php if (empty($this->items)) : ?>
			<div class="alert alert-no-items">
				<?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
			</div>
		<?php else : ?>

			<div class="table-responsive">
				<table class="table table-striped" id="lessonsList">
					<thead>
						<tr>
							<?php if (isset($this->items[0]->ordering)): ?>
								<th width="1%" class="nowrap">
									<?php echo HTMLHelper::_('searchtools.sort', '', 'a.ordering', $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING', 'icon-menu-2'); ?>
								</th>
							<?php endif; ?>

							<th width="1%">
							<input type="checkbox" name="checkall-toggle" value="" title="<?php echo Text::_('JGLOBAL_CHECK_ALL'); ?>" onclick="Joomla.checkAll(this)" />
							</th>

							<?php if (isset($this->items[0]->state)): ?>
								<th>
									<?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'a.state', $listDirn, $listOrder);?>
								</th>
							<?php endif; ?>

							<th>
								<?php echo HTMLHelper::_('searchtools.sort', 'COM_TJLMS_MANAGELESSONS_NAME', 'a.title', $listDirn, $listOrder);?>
							</th>
							<th>
								<?php echo HTMLHelper::_('searchtools.sort', 'COM_TJLMS_MANAGELESSONS_AUTHOR', 'a.created_by', $listDirn, $listOrder);
								?>
							</th>
							<th>
								<?php echo HTMLHelper::_('searchtools.sort', 'COM_TJLMS_MANAGELESSONS_VIEW_START_DATE', 'a.start_date', $listDirn, $listOrder);
								?>
							</th>
							<th>
								<?php echo HTMLHelper::_('searchtools.sort', 'COM_TJLMS_MANAGELESSONS_VIEW_END_DATE', 'a.end_date', $listDirn, $listOrder);
								?>
							</th>
							<th>
								<?php echo HTMLHelper::_('searchtools.sort', 'COM_TJLMS_MANAGELESSONS_VIEW_LESSON_FORMAT', 'a.format', $listDirn, $listOrder);
								?>
							</th>
							<th>
								<?php echo HTMLHelper::_('searchtools.sort', 'COM_TJLMS_MANAGELESSONS_ID', 'a.id', $listDirn, $listOrder);
								?>
							</th>
						</tr>
					</thead>
					<tbody>
						<?php
						foreach ($this->items as $i => $item)
						{
							$createdBy = $item->created_by;
							$createdByName = ($this->showUserOrUsername == 'name')?$item->name:$item->username;

							$canChange = 0;

							if ($this->canManageMaterial || ($this->canManageMaterialOwn && $this->user->id ==$createdBy))
							{
								$canChange  = 1;
							}
							?>

							<tr class="row<?php echo $i % 2; ?>" >
							<?php if (isset($this->items[0]->ordering)): ?>
								<td class="order nowrap">
									<?php
									$disableClassName = $disabledLabel	  = '';

									if (!$canChange)
									{
										$disabledLabel    = Text::_('COM_TJLMS_MANAGELESSONS_VIEW_ORDERINGDISABLED');
										$disableClassName = 'inactive tip-top';
									}
									?>
									<span class="sortable-handler hasTooltip <?php echo $disableClassName?>" title="<?php echo $disabledLabel?>">
										<i class="icon-menu"></i>
									</span>
									<?php if ($canChange) : ?>
										<input type="text" style="display:none" name="order[]" size="5" value="<?php echo $item->ordering; ?>" class="width-20 text-area-order" />
									<?php endif; ?>
								</td>
							<?php endif; ?>
								<td class="center">
									<?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
								</td>

								<?php if (isset($this->items[0]->state)): ?>
									<td class="center">
										<?php echo HTMLHelper::_('jgrid.published', $item->state, $i, 'managelessons.', $canChange, 'cb'); ?>
									</td>
								<?php endif; ?>

								<td class="has-context">
									<div class="pull-left break-word">
										<?php if ($item->checked_out) : ?>
											<?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'managelessons.', $canChange); ?>
										<?php endif; ?>

										<?php if ($canChange) : ?>
											<a class="hasTooltip" href="<?php echo Route::_('index.php?option=com_tjlms&view=lessonform&layout=edit&id=' . $item->id . '&cid=' . $item->course_id . '&mid=' . $item->mod_id); ?>" title="<?php echo Text::_('JACTION_EDIT'); ?>">
												<?php echo $this->escape($item->title); ?></a>
										<?php else : ?>
											<span><?php echo $this->escape($item->title); ?></span>
										<?php endif; ?>
									</div>
								</td>
								<td><?php echo $createdByName; ?></td>
								<td><?php echo $item->start_date; ?></td>
								<td><?php echo $item->end_date; ?></td>
								<td><?php echo $item->format; ?></td>
								<td><?php echo $item->id; ?></td>
							</tr>

							<?php
						}
						?>
					</tbody>
				</table>
			</div>
		<?php endif; ?>

		<input type="hidden" name="task" value="" />
		<input type="hidden" name="boxchecked" value="0" />
		<?php echo HTMLHelper::_('form.token'); ?>

		<!-- Adding pagination. -->
		<div class="pager">
			<?php echo $this->pagination->getPagesLinks(); ?>
		</div>
	</form>
</div>

<!-- Additional JS to show Select Author text as placeholder which is not working by default -->
<script type="text/javascript">
	jQuery(document).ready(function () {
		jQuery('#filter_created_by_chzn ul').find('input.default.active').val(Joomla.Text._('JOPTION_SELECT_AUTHOR'));
	});
</script>
