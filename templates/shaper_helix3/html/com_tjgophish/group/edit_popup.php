<?php
/**
 * @package     TjGoPhish
 * @subpackage  com_tjgophish
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\HTML\HTMLHelper;

HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('formbehavior.chosen', 'select');

Factory::getDocument()->addScriptDeclaration("
	Joomla.submitbutton = function(task)
	{
		if (task == 'group.cancel' || document.formvalidator.isValid(document.getElementById('group-form')))
		{
			Joomla.submitform(task, document.getElementById('group-form'));
		}

		return false;
	};
");

$fieldSetCounter = 0;
$id = isset($this->item->id) ? $this->item->id : 0;

// Get Active Item ID
$app = Factory::getApplication();
$itemId = $app->getMenu()->getActive()->id;

// Get create group Item ID
$app  = Factory::getApplication();
$menu = $app->getMenu();
$groupsMenuItem = $menu->getItems( 'link', 'index.php?option=com_tjgophish&view=groups', true );

$doc = Factory::getDocument();
$doc->addStyleSheet('templates/shaper_helix3/css/custom.css');
?>

<div id="tjgophish-wrapper" class="container group-popup">
	<button type="button" class="close" onclick="groupForm.closePopup();">&times;</button>
	<h3 class="activity-header">
		<?php
			echo (empty($this->item->id)) ? Text::_('COM_TJGOPHISH_ADD_GROUP') : Text::_('COM_TJGOPHISH_EDIT_GROUP');
		?>
	</h3>
	<div class="clearfix"></div>
	<form method="POST" name="adminForm" id="group-form" class="form-validate ucm-form-styling create-group mt-20">
		<div class="form-horizontal">
		<?php
		if ($this->form)
		{
			if ($id != '0')
			{
				?>
<!--
				<div class="row">
					<div class="page-header">
						<h1 class="page-title">
						<?php echo strtoupper(Text::_("COM_TJGOPHISH_EDIT_GROUP") . " : " . $this->item->gophish_group_title); ?>
						<h1>
					</div>
				</div>
-->
				<?php
			}
			else
			{
			?>
<!--
			<div class="row">
				<div class="page-header">
					<h1 class="page-title">
						<?php echo strtoupper(Text::_("COM_TJGOPHISH_CREATE_GROUP")); ?>
					<h1>
				</div>
			</div>
-->
			<?php
			}

			// Iterate through the form fieldsets and display each one
			$fieldSets = $this->form->getFieldsets();
			?>
		<div class="row">
		<div class="col-xs-12 col-md-6">
			<?php
			foreach ($fieldSets as $fieldset)
			{
				// Iterate through the fields and display them
				foreach ($this->form->getFieldset($fieldset->name) as $field)
				{
					?>
					<div>
						<?php echo $field->renderField();?>
					</div>
					<?php
				}
			}
			?>
			<div class="campaign-buttons mt-20">
				<div class="controls">
					<button type="button" class="btn btn-primary" onclick="Joomla.submitbutton('group.apply')">
						<?php echo Text::_('JSAVE') ?>
					</button>
					<a onclick="groupForm.closePopup();" class="btn btn-default" href="javascript:void(0);" title="<?php echo Text::_('JCANCEL'); ?>">
						<?php echo Text::_('JCANCEL'); ?>
					</a>
				</div>
		</div>
		</div>
		</div>
			<?php
		}
		?>
		</div>
		<input type="hidden" name="task" value="" />
		<?php echo HTMLHelper::_('form.token'); ?>
	</form>
</div>

<script type="text/javascript">

var targetLbl = "<?php echo Text::_('COM_TJGOPHISH_GROUP_TARGETS_LBL');?>";

jQuery(document).ready(function()
{
	// Show target field mandatory
	jQuery('#jform_targets-lbl').html(targetLbl+' <span class="star">*</span>');

	// Remove selected users from target field when "Add all users to the group?" is Yes
	jQuery("#jform_all_cluster_users").change(function() {

		// Check field option is Yes
		if (jQuery("input[name='jform[all_cluster_users]']:checked").val() == 1)
		{
			// Remove selected options from target dropdown
			jQuery("#jform_targets option:selected").removeAttr("selected");

			// Update the chosen dropdown after removing selected options
			jQuery("#jform_targets").trigger("chosen:updated");
		}
	});
});

</script>
