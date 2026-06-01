<?php
/**
 * @package    Com_Timelog
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2018 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

if (!$this->canEdit && $this->canEditOwn)
{
	$this->canEdit = $this->user->id == $this->item->created_by;
}
?>

<div class="item_fields">

	<table class="table">
		<tr>
			<th><?php echo Text::_('COM_TIMELOG_FORM_LBL_ACTIVITY_ACTIVITY_TYPE_ID'); ?></th>
			<td><?php echo $this->item->activity_type_id; ?></td>
		</tr>

		<tr>
			<th><?php echo Text::_('COM_TIMELOG_FORM_LBL_ACTIVITY_CLIENT'); ?></th>
			<td><?php echo $this->item->client; ?></td>
		</tr>

		<tr>
			<th><?php echo Text::_('COM_TIMELOG_FORM_LBL_ACTIVITY_CLIENT_ID'); ?></th>
			<td><?php echo $this->item->client_id; ?></td>
		</tr>

		<tr>
			<th><?php echo Text::_('COM_TIMELOG_FORM_LBL_ACTIVITY_ACTIVITY_NOTE'); ?></th>
			<td><?php echo $this->item->activity_note; ?></td>
		</tr>

		<tr>
			<th><?php echo Text::_('COM_TIMELOG_FORM_LBL_ACTIVITY_LOG_DATE'); ?></th>
			<td><?php echo $this->item->created_date; ?></td>
		</tr>

		<tr>
			<th><?php echo Text::_('COM_TIMELOG_FORM_LBL_ACTIVITY_LOG_TIME'); ?></th>
			<td><?php echo $this->item->spent_time; ?></td>
		</tr>

		<tr>
			<th><?php echo Text::_('COM_TIMELOG_FORM_LBL_ACTIVITY_ATTACHMENT'); ?></th>
			<td><?php echo $this->item->attachment; ?></td>
		</tr>

	</table>

</div>

<?php if($this->canEdit): ?>

	<a class="btn" href="<?php echo Route::_('index.php?option=com_timelog&task=activityform.edit&id='.$this->item->id); ?>"><?php echo Text::_("COM_TIMELOG_EDIT_ITEM"); ?></a>

<?php endif; ?>

<?php if ($this->canDelete) : ?>

	<a class="btn btn-danger" href="#deleteModal" role="button" data-toggle="modal">
		<?php echo Text::_("COM_TIMELOG_DELETE_ITEM"); ?>
	</a>

	<div id="deleteModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="deleteModal" aria-hidden="true">
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
			<h3><?php echo Text::_('COM_TIMELOG_DELETE_ITEM'); ?></h3>
		</div>
		<div class="modal-body">
			<p><?php echo Text::sprintf('COM_TIMELOG_DELETE_CONFIRM', $this->item->id); ?></p>
		</div>
		<div class="modal-footer">
			<button class="btn" data-dismiss="modal">Close</button>
			<a href="<?php echo Route::_('index.php?option=com_timelog&task=activity.remove&id=' . $this->item->id); ?>" class="btn btn-danger">
				<?php echo Text::_('COM_TIMELOG_DELETE_ITEM'); ?>
			</a>
		</div>
	</div>

<?php endif; ?>
