<?php
/**
 * @version    CVS: 1.0.0
 * @package    Com_Timelog
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2018 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers/html');
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('formbehavior.chosen', 'select');
HTMLHelper::_('behavior.keepalive');

?>
<script type="text/javascript">
	js = jQuery.noConflict();
	js(document).ready(function () {

	});

	Joomla.submitbutton = function (task) {
		if (task == 'activity.cancel' || document.formvalidator.isValid(document.getElementById('activity-form'))) {

			Joomla.submitform(task, document.getElementById('activity-form'));
		}
		else {
			alert('<?php echo $this->escape(Text::_('JGLOBAL_VALIDATION_FORM_FAILED')); ?>');
		}
	}
</script>

<form
	action="<?php echo Route::_('index.php?option=com_timelog&layout=edit&id=' . (int) $this->item->id); ?>"
	method="post" enctype="multipart/form-data" name="adminForm" id="activity-form" class="form-validate">

	<div class="form-horizontal">
		<div class="row-fluid">
			<div class="span10 form-horizontal">
				<fieldset class="adminform">
				<?php echo $this->form->renderField('id'); ?>
				<?php echo $this->form->renderField('activity_type_id'); ?>
				<?php echo $this->form->renderField('client'); ?>
				<?php echo $this->form->renderField('client_id'); ?>
				<?php echo $this->form->renderField('activity_note'); ?>
				<?php echo $this->form->renderField('created_date'); ?>
				<?php echo $this->form->renderField('spent_time'); ?>
				<?php echo $this->form->renderField('state'); ?>
				<?php echo $this->form->renderField('attachment'); ?>
				<?php echo $this->form->renderField('created_by'); ?>
				<?php echo $this->form->renderField('modified_by'); ?>
				</fieldset>
			</div>
		</div>
		<input type="hidden" name="task" value=""/>
		<?php echo HTMLHelper::_('form.token'); ?>

	</div>
</form>
