<?php
/**
 * @version     1.0.0
 * @package     com_tjfields
 * @copyright   Copyright (C) 2014. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      TechJoomla <extensions@techjoomla.com> - www.techjoomla.com
 */
// no direct access
defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('behavior.keepalive');

// Import CSS
$document = Factory::getDocument();
$document->addStyleSheet('components/com_tjfields/assets/css/tjfields.css');
$input = Factory::getApplication()->input;

// Import helper for declaring language constant
JLoader::import('TjfieldsHelper', Uri::root().'administrator/components/com_tjfields/helpers/tjfields.php');
// Call helper function
TjfieldsHelper::getLanguageConstant();
?>
<script type="text/javascript">
	Joomla.submitbutton = function(task)
	{
		if(task == 'group.cancel')
		{
			Joomla.submitform(task, document.getElementById('group-form'));
		}
		else
		{
			if (task != 'group.cancel' && document.formvalidator.isValid(document.getElementById('group-form')))
			{
				if (techjoomla.jQuery('#jform_name').val().trim() == '')
				{
					alert(Joomla.Text._('COM_TJFIELDS_LABEL_WHITESPACES_NOT_ALLOWED'));
					techjoomla.jQuery('#jform_name').val('');
					techjoomla.jQuery('#jform_name').focus();
					return false;
				}

				Joomla.submitform(task, document.getElementById('group-form'));
			}
			else
			{
				alert('<?php echo $this->escape(Text::_('JGLOBAL_VALIDATION_FORM_FAILED')); ?>');
			}
		}
	}
</script>
<?php // DPE hack To show navigation link of field groups and condition as per there ucm type 
$category_url    = Route::_('index.php?option=com_categories&extension=' . $this->item->client);
$field_group_url = Route::_('index.php?option=com_tjfields&view=groups&client=' . $this->item->client);
$fields_url      = Route::_('index.php?option=com_tjfields&view=fields&client=' . $this->item->client . '&extension=' . $this->item->client);
$conditionsUrl   = Route::_('index.php?option=com_tjfields&view=conditions&client=' . $this->item->client);

?>
<div class="float-end">
	<a href="<?php echo $field_group_url;?>" class="btn btn-success"><i class="fa fa-link" aria-hidden="true"></i>&nbsp<?php echo Text::_('COM_TJUCM_TYPES_FIELD_GROUP_URL');?></a>
<a href="<?php echo $fields_url;?>" class="btn btn-success"><i class="fa fa-link" aria-hidden="true"></i>&nbsp <?php echo Text::_('COM_TJUCM_TYPES_FIELDS_URL');?></a>
<a href="<?php echo $conditionsUrl;?>" class="btn btn-success"><i class="fa fa-link" aria-hidden="true"></i>&nbsp <?php echo Text::_('COM_TJUCM_TYPES_CONDITIONS_URL');?></a>
<br>
</div>
<br>
<br>
<br> 
<div class="techjoomla-bootstrap">
	<form action="<?php echo Route::_('index.php?option=com_tjfields&layout=edit&id=' . (int) $this->item->id).' &client='.$input->get('client','','STRING'); ?>" method="post" enctype="multipart/form-data" name="adminForm" id="group-form" class="form-validate">
		<?php echo HTMLHelper::_('bootstrap.startTabSet', 'myTab', array('active' => 'general')); ?>
		<?php echo HTMLHelper::_('bootstrap.addTab', 'myTab', 'general', Text::_('COM_TJFIELDS_TITLE_FIELD_GROUP', true)); ?>
		<div class="row">
			<div>&nbsp;</div>
			<div class="col-12">
				<fieldset class="adminform">
					<div class="control-group">
						<div class="control-label"><?php echo $this->form->getLabel('id'); ?></div>
						<div class="controls"><?php echo $this->form->getInput('id'); ?></div>
					</div>
					<div class="control-group">
						<div class="control-label"><?php echo $this->form->getLabel('state'); ?></div>
						<div class="controls"><?php echo $this->form->getInput('state'); ?></div>
					</div>
					<div class="control-group">
						<div class="control-label"><?php echo $this->form->getLabel('created_by'); ?></div>
						<div class="controls"><?php echo $this->form->getInput('created_by'); ?></div>
					</div>
					<div class="control-group">
						<div class="control-label"><?php echo $this->form->getLabel('name'); ?></div>
						<div class="controls"><?php echo $this->form->getInput('name'); ?></div>
					</div>
					<?php echo $this->form->getInput('title');?>
					<input type="hidden" name="jform[client]" value="<?php echo $input->get('client','','STRING'); ?>" />
				</fieldset>
			</div>
			<input type="hidden" name="task" value="" />
			<?php echo HTMLHelper::_('form.token'); ?>
		</div>
		<?php echo HTMLHelper::_('bootstrap.endTab'); ?>
		<?php if (Factory::getUser()->authorise('core.admin','com_tjfields')) : ?>
			<?php echo HTMLHelper::_('bootstrap.addTab', 'myTab', 'permissions', Text::_('JGLOBAL_ACTION_PERMISSIONS_LABEL', true)); ?>
			<?php echo $this->form->getInput('rules'); ?>
			<?php echo HTMLHelper::_('bootstrap.endTab'); ?>
		<?php endif; ?>
		<?php echo HTMLHelper::_('bootstrap.endTabSet'); ?>
	</form>
</div>
