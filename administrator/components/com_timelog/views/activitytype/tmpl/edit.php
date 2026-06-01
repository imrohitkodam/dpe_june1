<?php
/**
 * @package    Com_Timelog
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
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

?>
<script type="text/javascript">
	js = jQuery.noConflict();
	js(document).ready(function () {

	});

	Joomla.submitbutton = function (task) {
		if (task == 'activitytype.cancel' || document.formvalidator.isValid(document.getElementById('activitytype-form'))) {
			Joomla.submitform(task, document.getElementById('activitytype-form'));
		}
		else {
			alert('<?php echo $this->escape(Text::_('JGLOBAL_VALIDATION_FORM_FAILED')); ?>');
		}
	}
</script>

<form
	action="<?php echo Route::_('index.php?option=com_timelog&layout=edit&id=' . (int) $this->item->id); ?>"
	method="post" enctype="multipart/form-data" name="adminForm" id="activitytype-form" class="form-validate">

	<div class="form-horizontal">
		<div class="row-fluid">
			<div class="span10 form-horizontal">
				<?php echo $this->form->renderField('id'); ?>
				<?php echo $this->form->renderField('title'); ?>
				<?php echo $this->form->renderField('description'); ?>
				<?php echo $this->form->renderField('state'); ?>
			</div>
		</div>
		<input type="hidden" name="task" value=""/>
		<?php echo HTMLHelper::_('form.token'); ?>

	</div>
</form>
