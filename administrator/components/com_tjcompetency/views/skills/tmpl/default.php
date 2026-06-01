<?php
/**
 * @package     TjCompetency
 * @subpackage  com_tjcompetency
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2021 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

// Include the component HTML helpers.
HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers/html');

HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.multiselect');
HTMLHelper::_('formbehavior.chosen', '.multipleFrameworks', null, array('placeholder_text_multiple' => Text::_('COM_COMPETENCY_COMPETENCY_FRAMEWORK_FIELD_SELECT')));
HTMLHelper::_('formbehavior.chosen', 'select');

$app         = Factory::getApplication();
$input       = $app->input;
$frameworkId = $input->getInt('framework_id');
$user        = Factory::getUser();
$userId      = $user->get('id');
$listOrder   = $this->escape($this->state->get('list.ordering'));
$listDirn    = $this->escape($this->state->get('list.direction'));
$saveOrder   = ($listOrder == 'a.lft' && strtolower($listDirn) == 'asc');
$columns     = 7;

if ($saveOrder)
{
	$saveOrderingUrl = 'index.php?option=com_tjcompetency&task=skills.saveOrderAjax&tmpl=component';
	HTMLHelper::_('sortablelist.sortable', 'skillList', 'adminForm', strtolower($listDirn), $saveOrderingUrl, false, true);
}
?>
<form action="<?php echo Route::_('index.php?option=com_tjcompetency&view=skills'); ?>" method="post" name="adminForm" id="adminForm">
	<div id="j-sidebar-container" class="span2">
		<?php echo $this->sidebar; ?>
	</div>
	<div id="j-main-container" class="span10">
		<?php
		// Search tools bar
		echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this));
		?>
		<?php if (empty($this->items)) : ?>
			<div class="alert alert-no-items">
				<?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
			</div>
		<?php else : ?>
			<table class="table table-striped" id="skillList">
				<thead>
					<tr>
						<th width="1%" class="nowrap center hidden-phone">
							<?php echo HTMLHelper::_('searchtools.sort', '', 'a.lft', $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING', 'icon-menu-2'); ?>
						</th>
						<th width="1%" class="center">
							<?php echo HTMLHelper::_('grid.checkall'); ?>
						</th>
						<th width="1%" class="nowrap center">
							<?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'a.state', $listDirn, $listOrder); ?>
						</th>
						<th class="nowrap">
							<?php echo HTMLHelper::_('searchtools.sort', 'JGLOBAL_TITLE', 'a.title', $listDirn, $listOrder); ?>
						</th>						
						<th class="nowrap">
							<?php echo HTMLHelper::_('searchtools.sort', 'COM_COMPETENCY_COMPETENCY_SKILL_FORM_PATH_LABEL', 'a.path', $listDirn, $listOrder); ?>
						</th>						
						<th width="1%" class="nowrap center hidden-phone hidden-tablet">
							<span class="icon-user hasTooltip" aria-hidden="true" title="<?php echo Text::_('COM_COMPETENCY_COMPETENCY_SKILL_LIST_VIEW_USER_COUNT'); ?>"><span class="element-invisible"><?php echo Text::_('COM_COMPETENCY_COMPETENCY_SKILL_LIST_VIEW_USER_COUNT'); ?></span>
							</span>
						</th>
						<th width="1%" class="nowrap center hidden-phone hidden-tablet">
							<span class="icon-publish hasTooltip" aria-hidden="true" title="<?php echo Text::_('COM_COMPETENCY_COMPETENCY_SKILL_LIST_VIEW_PUBLISHED_CONTENT_COUNT'); ?>"><span class="element-invisible"><?php echo Text::_('COM_COMPETENCY_COMPETENCY_SKILL_LIST_VIEW_PUBLISHED_CONTENT_COUNT'); ?></span></span>
						</th>
						<th width="1%" class="nowrap center hidden-phone hidden-tablet">
							<span class="icon-unpublish hasTooltip" aria-hidden="true" title="<?php echo Text::_('COM_COMPETENCY_COMPETENCY_SKILL_LIST_VIEW_UNPUBLISHED_CONTENT_COUNT'); ?>"><span class="element-invisible"><?php echo Text::_('COM_COMPETENCY_COMPETENCY_SKILL_LIST_VIEW_UNPUBLISHED_CONTENT_COUNT'); ?></span></span>
						</th>
						<th class="nowrap">
							<?php echo HTMLHelper::_('searchtools.sort', 'COM_COMPETENCY_COMPETENCY_SKILL_LIST_VIEW_FRAMEWORK', 'a.framework_id', $listDirn, $listOrder); ?>
						</th>
						<th width="1%" class="nowrap hidden-phone">
							<?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
						</th>
					</tr>
				</thead>
				<tfoot>
					<tr>
						<td colspan="<?php echo $columns; ?>">
							<?php echo $this->pagination->getListFooter(); ?>
						</td>
					</tr>
				</tfoot>
				<tbody>
					<?php foreach ($this->items as $i => $item) : ?>
						<?php
						$canEdit    = $user->authorise('core.edit',       'com_tjcompetency.skill.' . $item->id);
						$canCheckin = $user->authorise('core.admin',      'com_checkin') || $item->checked_out == $userId || $item->checked_out == 0;
						$canEditOwn = $user->authorise('core.edit.own',   'com_tjcompetency.skill.' . $item->id) && $item->created_by == $userId;
						$canChange  = $user->authorise('core.edit.state', 'com_tjcompetency.skill.' . $item->id) && $canCheckin;

						// Get the parents of item for sorting
						if ($item->level > 1)
						{
							$parentsStr = '';
							$_currentParentId = $item->parent_id;
							$parentsStr = ' ' . $_currentParentId;
							for ($i2 = 0; $i2 < $item->level; $i2++)
							{
								foreach ($this->ordering as $k => $v)
								{
									$v = implode('-', $v);
									$v = '-' . $v . '-';
									if (strpos($v, '-' . $_currentParentId . '-') !== false)
									{
										$parentsStr .= ' ' . $k;
										$_currentParentId = $k;
										break;
									}
								}
							}
						}
						else
						{
							$parentsStr = '';
						}
						?>
						<tr class="row<?php echo $i % 2; ?>" sortable-group-id="<?php echo $item->parent_id; ?>" item-id="<?php echo $item->id ?>" parents="<?php echo $parentsStr ?>" level="<?php echo $item->level ?>">
							<td class="order nowrap center hidden-phone">
								<?php
								$iconClass = '';
								if (!$canChange)
								{
									$iconClass = ' inactive';
								}
								elseif (!$saveOrder)
								{
									$iconClass = ' inactive tip-top hasTooltip" title="' . HTMLHelper::_('tooltipText', 'JORDERINGDISABLED');
								}
								?>
								<span class="sortable-handler<?php echo $iconClass ?>">
									<span class="icon-menu"></span>
								</span>
								<?php if ($canChange && $saveOrder) : ?>
									<input type="text" style="display:none" name="order[]" size="5" value="<?php echo $item->lft; ?>" />
								<?php endif; ?>
							</td>
							<td class="center">
								<?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
							</td>
							<td class="center">
								<div class="btn-group">
									<?php echo HTMLHelper::_('jgrid.published', $item->state, $i, 'skills.', $canChange); ?>
									<?php
									if ($canChange)
									{
										// Create dropdown items
										HTMLHelper::_('actionsdropdown.' . ((int) $item->state === 2 ? 'un' : '') . 'archive', 'cb' . $i, 'skills');
										HTMLHelper::_('actionsdropdown.' . ((int) $item->state === -2 ? 'un' : '') . 'trash', 'cb' . $i, 'skills');

										// Render dropdown list
										echo HTMLHelper::_('actionsdropdown.render', $this->escape($item->title));
									}
									?>
								</div>
							</td>
							<td>
								<?php echo LayoutHelper::render('joomla.html.treeprefix', array('level' => $item->level)); ?>
								<?php if ($item->checked_out) : ?>
									<?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'skills.', $canCheckin); ?>
								<?php endif; ?>
								<?php if ($canEdit || $canEditOwn) : ?>
									<a class="hasTooltip" href="<?php echo Route::_('index.php?option=com_tjcompetency&task=skill.edit&id=' . $item->id); ?>" title="<?php echo Text::_('JACTION_EDIT'); ?>">
										<?php echo $this->escape($item->title); ?></a>
								<?php else : ?>
									<?php echo $this->escape($item->title); ?>
								<?php endif; ?>
								<span class="small" title="<?php echo $this->escape($item->path); ?>">
									<?php echo Text::sprintf('JGLOBAL_LIST_ALIAS', $this->escape($item->alias)); ?>
								</span>
							</td>
							<td>
								<?php 
								if(strlen(strip_tags($item->path)) > 25 )
								{
									echo substr($item->path, 0, 25) . '...';
								}
								else
								{
									 echo $this->escape($item->path);
								}
								?>
								<div class="btn-group">
									<a id="copyurl<?php echo $item->id;?>" data-toggle="popover"
										data-placement="bottom" data-content="Copied!"
										data-alt-url="<?php echo $item->path;?>" class="btn" type="button"
										onclick="copyUrl('copyurl<?php echo $item->id;?>');">
										<?php echo Text::_('COM_COMPETENCY_COMPETENCY_SKILL_LIST_VIEW_PATH_COPY');?>
									</a>
								</div>
							</td>

							<td class="center btns hidden-phone hidden-tablet">
								<a class="badge <?php if ($item->userCount > 0) echo 'badge-success'; ?>" title="<?php echo Text::_('COM_COMPETENCY_COMPETENCY_SKILL_LIST_VIEW_USER_COUNT'); ?>" href="<?php echo Route::_('index.php?option=com_tjcompetency&view=skillcontentusermaps&filter[skill_id][0]=' . (int) $item->id . '&filter[state]=1'); ?>">
									<?php echo $this->escape($item->userCount); ?></a>
							</td>
							
							<td class="center btns hidden-phone hidden-tablet">
								<a class="badge <?php if ($item->published_contents > 0) echo 'badge-success'; ?>" title="<?php echo Text::_('COM_COMPETENCY_COMPETENCY_SKILL_LIST_VIEW_PUBLISHED_CONTENT_COUNT'); ?>" href="<?php echo Route::_('index.php?option=com_tjcompetency&view=skillcontentmaps&filter[skill_id][0]=' . (int) $item->id . '&filter[state]=1'); ?>">
									<?php echo $this->escape($item->published_contents); ?></a>
							</td>

							<td class="center btns hidden-phone hidden-tablet">
								<a class="badge <?php if ($item->unpublished_contents > 0) echo 'badge-important'; ?>" title="<?php echo Text::_('COM_COMPETENCY_COMPETENCY_SKILL_LIST_VIEW_UNPUBLISHED_CONTENT_COUNT'); ?>" href="<?php echo Route::_('index.php?option=com_tjcompetency&view=skillcontentmaps&filter[skill_id][0]=' . (int) $item->id . '&filter[state]=0'); ?>">
									<?php echo $this->escape($item->unpublished_contents); ?></a>
							</td>

							<td class="hidden-phone">
								<?php echo $this->escape($item->framework_title); ?>
							</td>

							<td class="hidden-phone">
								<span title="<?php echo sprintf('%d-%d', $item->lft, $item->rgt); ?>">
									<?php echo (int) $item->id; ?></span>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>

		<input type="hidden" name="task" value="" />
		<input type="hidden" name="boxchecked" value="0" />
		<input type="hidden" name="framework_id" value="<?php echo $frameworkId; ?>" />
		<?php echo HTMLHelper::_('form.token'); ?>
	</div>
</form>

<?php
	$displayData['view'] = Factory::getApplication()->input->get('view');
	echo LayoutHelper::render('importcsv', $displayData);
?>

<script type="text/javascript">
    function copyUrl(element) 
    {
        element = '#' + element;
        var inputDump = document.createElement('input'),
        hrefText = jQuery(element).attr('data-alt-url');
        jQuery(element).popover("show");
        document.body.appendChild(inputDump);
        inputDump.value = hrefText;
        inputDump.select();
        document.execCommand('copy');
        document.body.removeChild(inputDump);

        setTimeout(function() {
            jQuery(element).popover("hide");
        }, 1000);
	}
</script>
