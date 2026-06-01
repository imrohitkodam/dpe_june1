<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_users
 *
 * @copyright   Copyright (C) 2005 - 2020 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');

JHtml::_('bootstrap.tooltip', '.hasTooltip', array('placement' => 'bottom'));
JHtml::_('bootstrap.popover', '.hasPopover', array('placement' => 'bottom'));
JHtml::_('formbehavior.chosen', '.multipleFrameworks', null, array('placeholder_text_multiple' => JText::_('COM_COMPETENCY_COMPETENCY_FRAMEWORK_FIELD_SELECT')));
JHtml::_('formbehavior.chosen', 'select');
JHtml::_('behavior.multiselect');

// Special case for the search field tooltip.
$searchFilterDesc = $this->filterForm->getFieldAttribute('search', 'description', null, 'filter');
JHtml::_('bootstrap.tooltip', '#filter_search', array('title' => JText::_($searchFilterDesc), 'placement' => 'bottom'));

$input           = JFactory::getApplication()->input;
$field           = $input->getCmd('field');
$frameworkId     = $input->getInt('framework_id');
$listOrder       = $this->escape($this->state->get('list.ordering'));
$listDirn        = $this->escape($this->state->get('list.direction'));
$enabledStates   = array(0 => 'icon-publish', 1 => 'icon-unpublish');
$activatedStates = array(0 => 'icon-publish', 1 => 'icon-unpublish');
$skillRequired    = (int) $input->get('required', 0, 'int');

?>
<div class="container-popup">
	<form action="<?php echo JRoute::_('index.php?option=com_tjcompetency&view=skills&layout=modal&tmpl=component'); ?>" method="post" name="adminForm" id="adminForm">
		<?php if (!$skillRequired) : ?>
		<div class="pull-left">
			<button type="button" class="btn button-select" data-user-value="0" data-user-name="<?php echo $this->escape(JText::_('COM_COMPETENCY_COMPETENCY_SKILLCONTENTMAP_FORM_SELECT_SKILL')); ?>"
				data-user-field="<?php echo $this->escape($field); ?>"><?php echo JText::_('COM_COMPETENCY_COMPETENCY_SKILLCONTENTMAP_FORM_NO_SKILL'); ?></button>&nbsp;
		</div>
		<?php endif; ?>
		<?php echo JLayoutHelper::render('joomla.searchtools.default', array('view' => $this)); ?>
		<?php if (empty($this->items)) : ?>
		<div class="alert alert-no-items">
			<?php echo JText::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
		</div>
		<?php else : ?>
		<table class="table table-striped table-condensed">
			<thead>
					<tr>
						<th class="nowrap">
							<?php echo JHtml::_('searchtools.sort', 'JGLOBAL_TITLE', 'a.title', $listDirn, $listOrder); ?>
						</th>
						<th class="nowrap">
							<?php echo JHtml::_('searchtools.sort', 'COM_COMPETENCY_COMPETENCY_SKILL_LIST_VIEW_FRAMEWORK', 'a.framework_id', $listDirn, $listOrder); ?>
						</th>
						<th width="1%" class="nowrap hidden-phone">
							<?php echo JHtml::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
						</th>
					</tr>
				</thead>
			<tfoot>
				<tr>
					<td colspan="6">
						<?php echo $this->pagination->getListFooter(); ?>
					</td>
				</tr>
			</tfoot>

			<tbody>
					<?php foreach ($this->items as $i => $item) : ?>
						<?php
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
						<tr class="row<?php echo $i % 2; ?>" item-id="<?php echo $item->id ?>" parents="<?php echo $parentsStr ?>" level="<?php echo $item->level ?>">
							<td>
								<?php echo JLayoutHelper::render('joomla.html.treeprefix', array('level' => $item->level)); ?>
									<a class="pointer button-select" href="#" data-user-value="<?php echo $item->id; ?>" data-user-name="<?php echo $this->escape($item->title); ?>"
								data-user-field="<?php echo $this->escape($field); ?>">
								<?php echo $this->escape($item->title); ?>
							</a>
								<span class="small" title="<?php echo $this->escape($item->path); ?>">
									<?php echo JText::sprintf('JGLOBAL_LIST_ALIAS', $this->escape($item->alias)); ?>
								</span>
							</td>

							<td class="hidden-phone">
								<?php echo $item->framework_title; ?>
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
		<input type="hidden" name="field" value="<?php echo $this->escape($field); ?>" />
		<input type="hidden" name="boxchecked" value="0" />
		<input type="hidden" name="required" value="<?php echo $skillRequired; ?>" />
		<input type="hidden" name="framework_id" value="<?php echo $frameworkId; ?>" />
		<?php echo JHtml::_('form.token'); ?>
	</form>
</div>
