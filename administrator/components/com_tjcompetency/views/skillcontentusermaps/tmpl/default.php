<?php
/**
 * @package     TjCompetency
 * @subpackage  com_tjcompetency
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2021 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers/html');
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.multiselect');

HTMLHelper::_('formbehavior.chosen', '.multipleFrameworks', null, array('placeholder_text_multiple' => Text::_('COM_COMPETENCY_COMPETENCY_FRAMEWORK_FIELD_SELECT')));
HTMLHelper::_('formbehavior.chosen', '.multipleSkills', null, array('placeholder_text_multiple' => Text::_('COM_COMPETENCY_COMPETENCY_SKILLCONTENTUSERMAP_SKILL_FIELD_SELECT')));
HTMLHelper::_('formbehavior.chosen', '.multipleScales', null, array('placeholder_text_multiple' => Text::_('COM_COMPETENCY_COMPETENCY_SKILLCONTENTUSERMAP_SCALE_FIELD_SELECT')));
HTMLHelper::_('formbehavior.chosen', '.multipleContentTypes', null, array('placeholder_text_multiple' => Text::_('COM_COMPETENCY_COMPETENCY_SKILLCONTENTUSERMAP_FILTER_CONTENT_TYPE_LABEL')));
HTMLHelper::_('formbehavior.chosen', '.multipleUsers', null, array('placeholder_text_multiple' => Text::_('COM_COMPETENCY_COMPETENCY_SKILLCONTENTUSERMAP_FILTER_USER_SELECT')));

$contentLangConst = Text::_('COM_COMPETENCY_COMPETENCY_SKILLCONTENTUSERMAP_CONTENT_FIELD_SELECT_WITHOUT_TYPE');
$client = $this->state->get('filter.client');

if (!empty($client))
{
	$contentLangConst = Text::_('COM_COMPETENCY_COMPETENCY_SKILLCONTENTUSERMAP_CONTENT_FIELD_SELECT');
}

HTMLHelper::_('formbehavior.chosen', '.multipleContents', null, array('placeholder_text_multiple' => $contentLangConst));

HTMLHelper::_('formbehavior.chosen', 'select');

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
$saveOrder = $listOrder == 'a.id';

if ( $saveOrder )
{
	// $saveOrderingUrl = 'index.php?option=com_tjcompetency&task=skillcontentusermaps.saveOrderAjax';
	// HTMLHelper::_('sortablelist.sortable', 'skillcontentusermapList', 'adminForm', strtolower($listDirn), $saveOrderingUrl);
}
?>

<div class="tj-page">
	<div class="row-fluid">
		<form action="<?php echo Route::_('index.php?option=com_tjcompetency&view=skillcontentusermaps'); ?>" method="post" name="adminForm" id="adminForm">

			<?php if (!empty( $this->sidebar))
			{
			?>
				<div id="j-sidebar-container" class="span2">
					<?php echo $this->sidebar; ?>
				</div>
				<div id="j-main-container" class="span10">
			<?php
			}
			else
			{
				?>
				<div id="j-main-container">
			<?php
			}
			// Search tools bar
			echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this));
			?>
			<?php
			if (empty($this->items))
			{
			?>
				<div class="alert alert-no-items">
					<?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
				</div>
			<?php
			}
			else
			{
				?>
					<table class="table table-striped" id="skillcontentusermapList">
						<thead>
							<tr>
								<!-- <th width="1%" class="nowrap center hidden-phone"></th> -->

								<th width="1%" class="center">
									<?php echo HTMLHelper::_('grid.checkall'); ?>
								</th>

								<th width="1%" class="nowrap center">
									<?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'a.state', $listDirn, $listOrder); ?>
								</th>

								<th>
									<?php echo HTMLHelper::_('searchtools.sort', 'COM_COMPETENCY_COMPETENCY_SKILLCONTENTUSERMAP_LIST_VIEW_ID', 'a.id', $listDirn, $listOrder); ?>
								</th>

								<th>
									<?php echo HTMLHelper::_('searchtools.sort', 'COM_COMPETENCY_COMPETENCY_SKILLCONTENTUSERMAP_LIST_VIEW_USER', 'a.user_id', $listDirn, $listOrder); ?>
								</th>

								<th>
									<?php echo HTMLHelper::_('searchtools.sort', 'COM_COMPETENCY_COMPETENCY_SKILLCONTENTUSERMAP_LIST_VIEW_CONTENT_TYPE', 'a.client', $listDirn, $listOrder); ?>
								</th>

								<th>
									<?php echo HTMLHelper::_('searchtools.sort', 'COM_COMPETENCY_COMPETENCY_SKILLCONTENTUSERMAP_LIST_VIEW_CONTENT_NAME', 'a.client_id', $listDirn, $listOrder); ?>
								</th>

								<th>
									<?php echo HTMLHelper::_('searchtools.sort', 'COM_COMPETENCY_COMPETENCY_SKILLCONTENTUSERMAP_LIST_VIEW_FRAMEWORK', 'b.framework_id', $listDirn, $listOrder); ?>
								</th>

								<th>
									<?php echo HTMLHelper::_('searchtools.sort', 'COM_COMPETENCY_COMPETENCY_SKILLCONTENTUSERMAP_LIST_VIEW_SKILL', 'a.skill_id', $listDirn, $listOrder); ?>
								</th>

								<th>
									<?php echo HTMLHelper::_('searchtools.sort', 'COM_COMPETENCY_COMPETENCY_SKILLCONTENTUSERMAP_LIST_VIEW_SCALE', 'a.scale_id', $listDirn, $listOrder); ?>
								</th>

								<th>
									<?php echo HTMLHelper::_('searchtools.sort', 'COM_COMPETENCY_COMPETENCY_SKILLCONTENTUSERMAP_LIST_VIEW_CREATED_DATE', 'a.created_on', $listDirn, $listOrder); ?>
								</th>

								<th>
									<?php echo HTMLHelper::_('searchtools.sort', 'COM_COMPETENCY_COMPETENCY_SKILLCONTENTUSERMAP_LIST_VIEW_CREATED_BY', 'a.created_by', $listDirn, $listOrder); ?>
								</th>
							</tr>
						</thead>
						<tfoot>
							<tr>
								<td colspan="12">
									<?php echo $this->pagination->getListFooter(); ?>
								</td>
							</tr>
						</tfoot>
						<tbody>
							<?php
							foreach ($this->items as $i => $item)
							{
								$canEdit    = ($this->canDo->get('core.edit') && $this->canDo->get('skillcontentusermap.edit'));

								$canEditOwn = ($this->canDo->get('skillcontentusermap.edit.own') && ($item->created_by == Factory::getUser()->id));

								$canCheckin = ($this->canDo->get('core.edit.state') && $canEditOwn) || $this->canDo->get('skillcontentusermap.edit');

								$canChange = ($this->canDo->get('core.edit.state') && $canEditOwn) || $this->canDo->get('skillcontentusermap.edit');

								?>
								<tr class=" <?php echo $i % 2; ?>" sortable-group-id="<?php echo $item->id; ?>">

								<td class="center">
									<?php
										// if ($canEditOwn || $canEdit)
										{
											echo HTMLHelper::_('grid.id', $i, $item->id);
										}
									?>
								</td>
								<td class="center">
									<?php echo HTMLHelper::_('jgrid.published', $item->state, $i, 'skillcontentusermaps.', $canChange = true, 'cb'); ?>

									<?php
									if ($item->state == 3)
									{
									?>
									    <a style="margin-top: 5px;" class="badge" title="<?php echo Text::_('COM_COMPETENCY_COMPETENCY_SKILLCONTENTUSERMAP_STATE_OPTION_INREVIEW'); ?>" href="javascript:void(0);">
										<?php echo Text::_('COM_COMPETENCY_COMPETENCY_SKILLCONTENTUSERMAP_STATE_OPTION_INREVIEW'); ?>
										</a>
									<?php
									}
									?>
								</td>
								<td class="has-context">
									<div class="pull-left break-word">
										<?php if ($item->checked_out)
										{
											?>
										<?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->checked_out, $item->checked_out_time, 'skillcontentusermaps.', $canCheckin = true); ?>
										<?php
										}
										?>
										<?php // if ($canEdit || $canEditOwn)
										{
											?>
											<a class="hasTooltip" href="
											<?php echo Route::_('index.php?option=com_tjcompetency&task=skillcontentusermap.edit&id=' . $item->id); ?>" title="
											<?php echo Text::_('JACTION_EDIT'); ?>">
											<?php echo (int) $item->id; ?></a>
											<?php
											}
											/* else
											{
												?>
											<span title="<?php echo Text::sprintf('JFIELD_ALIAS_LABEL', $this->escape($item->title)); ?>">
											<?php echo $this->escape($item->title); ?></span>
										<?php
										} */ ?>

									</div>
								</td>
								<td><?php echo $this->escape($item->user_name); ?></td>
								<td><?php echo $this->escape($item->contentType); ?></td>
								<td><?php echo $this->escape($item->contentName); ?></td>
								<td><?php echo $this->escape($item->framework_title); ?></td>
								<td><?php echo $this->escape($item->skill_title); ?></td>
								<td><?php echo $this->escape($item->scale_title); ?></td>
								<td><?php echo HTMLHelper::_('date', $item->created_on, Text::_('DATE_FORMAT_LC1'), false); ?></td>
								<td><?php echo $this->escape($item->uname); ?></td>
							</tr>
							<?php
								}
							?>
						<tbody>
					</table>
					<?php
					}
					?>
					<input type="hidden" name="task" value="" />
					<input type="hidden" name="boxchecked" value="0" />
					<?php echo HTMLHelper::_('form.token'); ?>
			</div>
		</form>
	</div>
</div>

<?php
	$displayData['view'] = Factory::getApplication()->input->get('view');
	$displayData['notify'] = true;
	echo LayoutHelper::render('importcsv', $displayData);
?>
