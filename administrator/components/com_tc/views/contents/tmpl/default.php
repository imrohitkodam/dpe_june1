<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Tc
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2016-2017 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/');
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\LayoutHelper;

HTMLHelper::_('behavior.tooltip');
HTMLHelper::_('behavior.multiselect');
HTMLHelper::_('formbehavior.chosen', 'select');

$listOrder = $this->state->get('list.ordering');
$listDirn  = $this->state->get('list.direction');
?>
<form action="<?php echo JRoute::_('index.php?option=com_tc&view=contents'); ?>" method="post" name="adminForm" id="adminForm">
	<?php
	if (!empty($this->sidebar)): ?>
	<div id="j-sidebar-container" class="span2">
		<?php echo $this->sidebar; ?>
	</div>
	<div id="j-main-container" class="span10">
	<?php
	else : ?>
		<div id="j-main-container">
	<?php
	endif;
	echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this)); ?>
			<div class="clearfix"></div>
	<?php
	if (empty($this->items)) : ?>
		<div class="alert alert-no-items">
			<?php echo JText::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
		</div>
	<?php
	else : ?>
			<table class="table table-striped" id="contentList">
				<thead>
				<tr>
					<th class="hidden-phone">
						<?php echo HTMLHelper::_('grid.checkall'); ?>
					</th>
					<th>
						<?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'a.state', $listDirn, $listOrder);?>
					</th>
					<th class='left'>
						<?php echo HTMLHelper::_('searchtools.sort',  'COM_TC_CONTENTS_TITLE', 'a.title', $listDirn, $listOrder); ?>
					</th>
					<th class='left'>
						<?php echo HTMLHelper::_('searchtools.sort',  'COM_TC_CONTENTS_VERSION', 'a.version', $listDirn, $listOrder); ?>
					</th>
					<th class='left'>
						<?php echo HTMLHelper::_('searchtools.sort',  'COM_TC_USERTCS_TC_CODE', 'a.client', $listDirn, $listOrder); ?>
					</th>
					<th class='left'>
						<?php echo HTMLHelper::_('searchtools.sort',  'COM_TC_CONTENTS_START_DATE', 'a.start_date', $listDirn, $listOrder); ?>
					</th>
					<th class='left'>
						<?php echo HTMLHelper::_('searchtools.sort',  'COM_TC_CONTENTS_ID', 'a.tc_id', $listDirn, $listOrder); ?>
					</th>
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
					<?php
					foreach ($this->items as $i => $item) :
						$canCreate  = $this->user->authorise('core.create', 'com_tc');
						$canEdit    = $this->user->authorise('core.edit', 'com_tc');
						$canCheckin = $this->user->authorise('core.manage', 'com_tc');
						$canChange  = $this->user->authorise('core.edit.state', 'com_tc');

						$startDate = HTMLHelper::_('date', $item->start_date, $this->dateFormat, true);
					?>
					<tr class="row<?php echo $i % 2; ?>">
						<td class="hidden-phone">
								<?php echo HTMLHelper::_('grid.id', $i, $item->tc_id); ?>
						</td>
							<?php
							if (isset($this->items[0]->state)): ?>
								<td>
									<?php echo HTMLHelper::_('jgrid.published', $item->state, $i, 'contents.', $canChange, 'cb'); ?>
					</td>
							<?php
							endif; ?>
			<td>
				<?php if (isset($item->checked_out) && $item->checked_out && ($canEdit || $canChange)) : ?>
									<?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'contents.', $canCheckin); ?>
				<?php endif; ?>
				<?php if ($canEdit) : ?>
					<a href="<?php echo JRoute::_('index.php?option=com_tc&task=content.edit&tc_id='.(int) $item->tc_id); ?>">
					<?php echo $this->escape($item->title); ?></a>
				<?php else : ?>
					<?php echo $this->escape($item->title); ?>
				<?php endif; ?>
				</td>
				<td>
					<?php echo $item->version; ?>
				</td>
				<td>
								<?php echo $this->escape($item->client); ?>
				</td>
				<td>
								<?php echo $startDate; ?>
							</td>
							<td>
								<?php echo $item->tc_id; ?>
				</td>
			</tr>
					<?php
					endforeach; ?>
				</tbody>
			</table>
		<?php
		endif; ?>
			<input type="hidden" name="task" value=""/>
			<input type="hidden" name="boxchecked" value="0"/>
			<?php echo JHtml::_('form.token'); ?>
		</div>
</form>
