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
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;

HTMLHelper::_('behavior.keepalive');
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('formbehavior.chosen', 'select');

?>

<div class="activity-edit front-end-edit">
	<?php if (!$this->canEdit) : ?>
		<h3><?php throw new Exception(Text::_('COM_TIMELOG_ERROR_MESSAGE_NOT_AUTHORISED'), 403); ?></h3>
	<?php else : ?>
		<?php if (!empty($this->item->id)): ?>
			<h1><?php echo Text::sprintf('COM_TIMELOG_EDIT_ITEM_TITLE', $this->item->id); ?></h1>
		<?php else: ?>
			<h1><?php echo Text::_('COM_TIMELOG_ADD_ITEM_TITLE'); ?></h1>
		<?php endif;
		?>

		<form id="form-activity"
		action="<?php echo Route::_('index.php?option=com_timelog&task=activity.save'); ?>" method="post"
		class="form-validate form-horizontal" enctype="multipart/form-data">
			<?php
				echo $this->form->renderField('id');
				echo $this->form->renderField('client_id');
				echo $this->form->renderField('activity_type_id');
				echo $this->form->renderField('client');
				echo $this->form->renderField('activity_note');
				echo $this->form->renderField('created_date');
				echo $this->form->renderField('spent_time');
				echo $this->form->renderField('state');
				echo $this->form->renderField('attachment');
				echo $this->form->getInput('created_by');
				echo $this->form->getInput('modified_by');
			?>
			<div class="control-group">
				<div class="controls">
					<?php if ($this->canSave): ?>
						<button type="submit" class="validate btn btn-primary"><?php echo Text::_('JSUBMIT');?></button>
					<?php endif; ?>
					<a class="btn" href="<?php echo Route::_('index.php?option=com_timelog&task=activityform.cancel'); ?>" title="<?php echo Text::_('JCANCEL'); ?>"><?php echo Text::_('JCANCEL');?></a>
				</div>
			</div>

			<input type="hidden" name="option" value="com_timelog"/>
			<input type="hidden" name="task" value="activityform.save"/>
			<?php echo HTMLHelper::_('form.token'); ?>
		</form>
	<?php endif; ?>
</div>
