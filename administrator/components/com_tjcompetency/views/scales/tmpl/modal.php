<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_users
 *
 * @copyright   Copyright (C) 2005 - 2020 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');

JHtml::_('bootstrap.tooltip', '.hasTooltip', array('placement' => 'bottom'));
JHtml::_('bootstrap.popover', '.hasPopover', array('placement' => 'bottom'));
JHtml::_('formbehavior.chosen', 'select');
JHtml::_('behavior.multiselect');

// Special case for the search field tooltip.
$searchFilterDesc = $this->filterForm->getFieldAttribute('search', 'description', null, 'filter');
JHtml::_('bootstrap.tooltip', '#filter_search', array('title' => JText::_($searchFilterDesc), 'placement' => 'bottom'));

$input           = JFactory::getApplication()->input;
$field           = $input->getCmd('field');
$scaleSetId      = $input->getInt('scale_set_id');
$listOrder       = $this->escape($this->state->get('list.ordering'));
$listDirn        = $this->escape($this->state->get('list.direction'));
$enabledStates   = array(0 => 'icon-publish', 1 => 'icon-unpublish');
$activatedStates = array(0 => 'icon-publish', 1 => 'icon-unpublish');
$skillRequired   = (int) $input->get('required', 0, 'int');

?>
<div class="container-popup">
	<form action="<?php echo JRoute::_('index.php?option=com_tjcompetency&view=scales&layout=modal&tmpl=component'); ?>" method="post" name="adminForm" id="adminForm">
		<?php if (!$skillRequired) : ?>
		<div class="pull-left">
			<button type="button" class="btn button-select" data-user-value="0" data-user-name="<?php echo $this->escape(JText::_('COM_COMPETENCY_COMPETENCY_SKILLCONTENTMAP_FORM_SELECT_SCALE')); ?>"
				data-user-field="<?php echo $this->escape($field); ?>"><?php echo JText::_('COM_COMPETENCY_COMPETENCY_SKILLCONTENTMAP_FORM_NO_SCALE'); ?></button>&nbsp;
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
						<th width="1%" class="nowrap center hidden-phone"></th>
						<th>
							<?php echo HTMLHelper::_('searchtools.sort', 'COM_COMPETENCY_COMPETENCY_SCALE_LIST_VIEW_TITLE', 'a.title', $listDirn, $listOrder); ?>
						</th>

						<th>
							<?php echo HTMLHelper::_('searchtools.sort', 'COM_COMPETENCY_COMPETENCY_SCALE_LIST_VIEW_SCALESET', 'a.scale_set_id', $listDirn, $listOrder); ?>
						</th>

						<th>
							<?php echo HTMLHelper::_('searchtools.sort', 'COM_COMPETENCY_COMPETENCY_SCALE_LIST_VIEW_SEQUENCE_NUMBER', 'a.sequence_number', $listDirn, $listOrder); ?>
						</th>

						<th>
							<?php echo HTMLHelper::_('searchtools.sort', 'COM_COMPETENCY_COMPETENCY_SCALE_LIST_VIEW_CREATED_BY', 'a.created_by', $listDirn, $listOrder); ?>
						</th>
						<th>
							<?php echo HTMLHelper::_('searchtools.sort', 'COM_COMPETENCY_COMPETENCY_SCALE_LIST_VIEW_ID', 'a.id', $listDirn, $listOrder); ?>
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
						<tr class="row <?php echo $i % 2; ?>">
							<td>
								<a class="pointer button-select" href="#" data-user-value="<?php echo $item->id; ?>" data-user-name="<?php echo $this->escape($item->title); ?>"
								data-user-field="<?php echo $this->escape($field); ?>">
								<?php echo $this->escape($item->title); ?>
							</a>
							</td>

							<td><?php echo $this->escape($item->scaleset_title); ?></td>
							<td><?php echo $this->escape($item->sequence_number); ?></td>
							<td><?php echo $this->escape($item->uname); ?></td>
							<td><?php echo (int) $item->id; ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
		</table>
		<?php endif; ?>
		<input type="hidden" name="task" value="" />
		<input type="hidden" name="field" value="<?php echo $this->escape($field); ?>" />
		<input type="hidden" name="boxchecked" value="0" />
		<input type="hidden" name="required" value="<?php echo $skillRequired; ?>" />
		<input type="hidden" name="scale_set_id" value="<?php echo $scaleSetId; ?>" />
		<?php echo JHtml::_('form.token'); ?>
	</form>
</div>
