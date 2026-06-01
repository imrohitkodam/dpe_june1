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

HTMLHelper::_('formbehavior.chosen', '.multipleSkills', null, array('placeholder_text_multiple' => Text::_('COM_COMPETENCY_COMPETENCY_SKILLCONTENTUSERMAP_SKILL_FIELD_SELECT')));
HTMLHelper::_('formbehavior.chosen', '.multipleUsers', null, array('placeholder_text_multiple' => Text::_('COM_COMPETENCY_COMPETENCY_SKILLCONTENTUSERMAP_FILTER_USER_SELECT')));

HTMLHelper::_('formbehavior.chosen', 'select');

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
$saveOrder = $listOrder == 'a.user_id';

if ( $saveOrder )
{
	// $saveOrderingUrl = 'index.php?option=com_tjcompetency&task=skillusermaps.saveOrderAjax';
	// HTMLHelper::_('sortablelist.sortable', 'skillusermapList', 'adminForm', strtolower($listDirn), $saveOrderingUrl);
}
?>

<div class="tj-page">
	<div class="row-fluid">
		<form action="<?php echo Route::_('index.php?option=com_tjcompetency&view=skillusermaps'); ?>" method="post" name="adminForm" id="adminForm">

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
					<table class="table table-striped" id="skillusermapList">
						<thead>
							<tr>
								<th width="1%" class="nowrap center hidden-phone"></th>

								<th>
									<?php echo HTMLHelper::_('searchtools.sort', 'COM_COMPETENCY_COMPETENCY_SKILLUSERMAP_LIST_VIEW_USER', 'a.user_id', $listDirn, $listOrder); ?>
								</th>

								<th>
									<?php echo HTMLHelper::_('searchtools.sort', 'COM_COMPETENCY_COMPETENCY_SKILLUSERMAP_LIST_VIEW_SKILL', 'a.skill_title', $listDirn, $listOrder); ?>
								</th>

								<th>
									<?php echo Text::_('COM_COMPETENCY_COMPETENCY_SKILLCONTENTUSERMAP_LIST_VIEW_SCALE'); ?>
								</th>
								<!-- <th>
									<?php // echo Text::_('COM_COMPETENCY_COMPETENCY_SKILLUSERMAP_LIST_VIEW_SEQUENCE_NUMBER'); ?>
								</th> -->

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
								?>
								<tr class="row <?php echo $i % 2; ?>" sortable-group-id="">

								<td><?php echo $this->escape($item->user_name); ?></td>
								<td><?php echo $this->escape(ucfirst($item->skill_title)); ?></td>
								<td><?php echo $this->escape($item->scale_title); ?></td>
								<!-- <td><?php // echo $this->escape($item->max_sequence_number); ?></td> -->
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
