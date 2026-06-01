<?php
/**
 * @package    Com_Multiagency
 * @author     Techjoomla <contact@techjoomla.com>
 * @copyright  2017 Techjoomla
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$user = Factory::getUser();
$canEdit = $user->authorise('core.edit', 'com_multiagency.' . $this->item->id);

if (!$canEdit && $user->authorise('core.edit.own', 'com_multiagency' . $this->item->id))
{
	$canEdit = $user->id == $this->item->created_by;
}
?>

<div class="item_fields">

	<table class="table">


		<tr>
			<th><?php echo Text::_('COM_MULTIAGENCY_FORM_LBL_SUBSCRIPTION_COURSE_ID'); ?></th>
			<td><?php echo $this->item->course_id; ?></td>
		</tr>

		<tr>
			<th><?php echo Text::_('COM_MULTIAGENCY_FORM_LBL_SUBSCRIPTION_TOTAL_SEATS'); ?></th>
			<td><?php echo $this->item->total_seats; ?></td>
		</tr>

		<tr>
			<th><?php echo Text::_('COM_MULTIAGENCY_FORM_LBL_SUBSCRIPTION_USED_SEATS'); ?></th>
			<td><?php echo $this->item->used_seats; ?></td>
		</tr>

		<tr>
			<th><?php echo Text::_('COM_MULTIAGENCY_FORM_LBL_SUBSCRIPTION_START_DATE'); ?></th>
			<td><?php echo $this->item->start_date; ?></td>
		</tr>

		<tr>
			<th><?php echo Text::_('COM_MULTIAGENCY_FORM_LBL_SUBSCRIPTION_END_DATE'); ?></th>
			<td><?php echo $this->item->end_date; ?></td>
		</tr>

		<tr>
			<th><?php echo Text::_('COM_MULTIAGENCY_FORM_LBL_SUBSCRIPTION_COMMENT'); ?></th>
			<td><?php echo nl2br($this->item->comment); ?></td>
		</tr>

	</table>

</div>

<?php if($canEdit && $this->item->checked_out == 0): ?>

	<a class="btn" href="<?php echo Route::_('index.php?option=com_multiagency&task=licence.edit&id='.$this->item->id); ?>"><?php echo Text::_("COM_MULTIAGENCY_EDIT_ITEM"); ?></a>

<?php endif; ?>

<?php if (Factory::getUser()->authorise('core.delete','com_multiagency.licence.'.$this->item->id)) : ?>

	<a class="btn btn-danger" href="#deleteModal" role="button" data-toggle="modal">
		<?php echo Text::_("COM_MULTIAGENCY_DELETE_ITEM"); ?>
	</a>

	<div id="deleteModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="deleteModal" aria-hidden="true">
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
			<h3><?php echo Text::_('COM_MULTIAGENCY_DELETE_ITEM'); ?></h3>
		</div>
		<div class="modal-body">
			<p><?php echo Text::sprintf('COM_MULTIAGENCY_DELETE_CONFIRM', $this->item->id); ?></p>
		</div>
		<div class="modal-footer">
			<button class="btn" data-dismiss="modal">Close</button>
			<a href="<?php echo Route::_('index.php?option=com_multiagency&task=licence.remove&id=' . $this->item->id, false, 2); ?>" class="btn btn-danger">
				<?php echo Text::_('COM_MULTIAGENCY_DELETE_ITEM'); ?>
			</a>
		</div>
	</div>

<?php endif; ?>
