<?php
/**
 * @version     1.0.0
 * @package     com_tjlms
 * @copyright   Copyright (C) 2014. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Aniket <aniket_c@tekdi.net> - http://www.techjoomla.com
 */
// no direct access
defined('_JEXEC') or die;

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');

JHtml::_('behavior.tooltip');
JHtml::_('behavior.formvalidation');
JHtml::_('behavior.keepalive');

if(JVERSION >= '3.0')
{
	JHtml::_('bootstrap.tooltip');
	JHtml::_('formbehavior.chosen', 'select');
	JHtml::_('behavior.multiselect');
}

$input = JFactory::getApplication()->input;

?>
<script type="text/javascript">


	Joomla.submitbutton = function(task)
	{

		if (task == 'module.cancel')
		{
			Joomla.submitform(task, document.getElementById('module-form'));
			window.parent.SqueezeBox.close();
		}
		else
		{
			if (task != 'module.cancel' && document.formvalidator.isValid(document.id('module-form')))
			{
				Joomla.submitform(task, document.getElementById('module-form'));
				//window.parent.SqueezeBox.close();
			}
			else
			{
				alert('<?php echo $this->escape(JText::_('JGLOBAL_VALIDATION_FORM_FAILED')); ?>');
			}
		}
	}

</script>

<?php if (JVERSION < '3.0'): ?>
<div class="techjoomla-bootstrap">
<?php endif; ?>

<form action="<?php echo JRoute::_('index.php?option=com_tjlms&layout=edit&id=' . (int) $this->item->id) ?>" method="post" enctype="multipart/form-data" name="adminForm" id="module-form" class="form-validate">

	<div class="form-horizontal">
		<div class="form-actions">
			<?php echo $this->toolbarHTML;?>
		</div>
		<div class="row-fluid">
			<div class="span10 form-horizontal">
				<fieldset class="adminform">

					<div class="control-group" style="display:none">
						<div class="control-label"><?php echo $this->form->getLabel('id'); ?></div>
						<div class="controls"><?php echo $this->form->getInput('id'); ?></div>
					</div>
					<div class="control-group">
						<div class="control-label"><?php echo $this->form->getLabel('name'); ?></div>
						<div class="controls"><?php echo $this->form->getInput('name'); ?></div>
					</div>

					<div class="control-group">
						<div class="control-label"><?php echo $this->form->getLabel('state'); ?></div>
						<div class="controls"><?php echo $this->form->getInput('state'); ?></div>
					</div>
					<div class="control-group">
						<div class="control-label"><?php echo $this->form->getLabel('created_by'); ?></div>
						<div class="controls"><?php echo $this->form->getInput('created_by'); ?></div>
					</div>

					<input type="hidden" name="jform[course_id]" value="<?php echo $input->get('course_id','','INT'); ?>" />

				</fieldset>
			</div>
		</div>


		<input type="hidden" name="task" value="" />

		<?php echo JHtml::_('form.token'); ?>

	</div>
</form>
<?php if (JVERSION < '3.0'): ?>
</div>
<?php endif; ?>
