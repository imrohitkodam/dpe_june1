<?php
/**
 * @package    Sla
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;

use Joomla\CMS\Router\Route;

HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers/html');

HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('behavior.keepalive');
HTMLHelper::_('formbehavior.chosen', 'select');

Factory::getDocument()->addScriptDeclaration('
	Joomla.submitbutton = function(task)
	{
		if (task == "sla.cancel" || document.formvalidator.isValid(document.getElementById("adminForm")))
		{
			jQuery("#permissions-sliders select").attr("disabled", "disabled");
			Joomla.submitform(task, document.getElementById("adminForm"));
		}
	};
');


?>
<div class="row-fluid">
	<form action="<?php echo Route::_('index.php?option=com_sla&view=sla&layout=edit&id=' . (int) $this->item->id, false);?>"
	 method="post" enctype="multipart/form-data" name="adminForm" id="adminForm" class="form-validate">

		<?php
		if (!empty( $this->sidebar))
		{
			?>
			<div id="j-sidebar-container" class="span2">
				<?php  echo $this->sidebar; ?>
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
		?>

		<?php echo HTMLHelper::_('bootstrap.startTabSet', 'myTab', array('active' => 'general')); ?>
		<?php echo HTMLHelper::_('bootstrap.addTab', 'myTab', 'general', Text::_('COM_SLA_TITLE_SLA')); ?>

		<div class="form-horizontal">
			<div class="row-fluid">
				<div class="span9">
					<?php echo $this->form->renderField('title'); ?>
					<?php echo $this->form->renderField('description'); ?>
					<div class="control-group">
						<div class="control-label"><?php echo $this->form->getLabel('params'); ?></div>
							<?php if ($this->item->params) : ?>
						<div class="controls">
							<textarea name="jform[params]" id="jform_params" cols="80" rows="7" aria-invalid="false"><?php echo json_encode($this->item->params); ?></textarea>
						</div>
						<?php else: ?>
							<div class="controls"><?php echo $this->form->getInput('params'); ?></div>
						<?php endif; ?>

					</div>
					<?php echo $this->form->renderField('state'); ?>
					<?php echo $this->form->getInput('created_by'); ?>
					<?php echo $this->form->getInput('modified_on'); ?>
					<?php echo $this->form->getInput('modified_by'); ?>
					<?php echo $this->form->getInput('ordering'); ?>
					<?php echo $this->form->getInput('checked_out'); ?>
					<?php echo $this->form->getInput('checked_out_time'); ?>
				</div>
			</div>
		</div>
		<?php echo HTMLHelper::_('bootstrap.endTab'); ?>

		<?php if (Factory::getUser()->authorise('core.admin','sla')) : ?>
			<?php echo HTMLHelper::_('bootstrap.addTab', 'myTab', 'permissions', Text::_('JGLOBAL_ACTION_PERMISSIONS_LABEL', true)); ?>
			<?php echo $this->form->getInput('rules'); ?>
			<?php echo HTMLHelper::_('bootstrap.endTab'); ?>
		<?php endif; ?>

		<?php echo HTMLHelper::_('bootstrap.endTabSet'); ?>

		<input type="hidden" name="task" value="" />
		<?php echo HTMLHelper::_('form.token'); ?>
	</div>
	</form>
</div>
