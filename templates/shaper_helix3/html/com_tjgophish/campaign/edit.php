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
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;

use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\HTML\HTMLHelper;

HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('formbehavior.chosen', 'select');

Factory::getDocument()->addScriptDeclaration("
	Joomla.submitbutton = function(task)
	{
		if (task == 'campaign.cancel' || document.formvalidator.isValid(document.getElementById('campaign-form')))
		{
			if (confirm('".Text::_('COM_TJGOPHISH_CAMPAIGN_CREATE_CONFIRM_MESSAGE')."'))
			{
				Joomla.submitform(task, document.getElementById('campaign-form'));
			}
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
$campaignsMenuItem = $menu->getItems( 'link', 'index.php?option=com_tjgophish&view=campaigns', true );

?>
<script type="text/javascript">
function updateChzn()
{
	var clusterField = jQuery("#jform_cluster_id");

	if (clusterField.val())
	{
		clusterField.trigger("change");
	}
}
	
jQuery(document).ready(function()
{
	/* It restrict the user for manual input in datepicker field */
	jQuery(document).delegate('.calendar-textfield-class', 'focusin', function(event) {
		event.preventDefault();
		jQuery(this).parent().siblings(':eq(0)').show();
	});

	jQuery(document).delegate('.calendar-textfield-class', 'keydown contextmenu', function() {
			return false;
	});
});
</script>
<style>
	.hidden{ display: none; }
</style>
<div id="tjgophish-wrapper" class="container">
	<form action="<?php echo Route::_('index.php?option=com_tjgophish&view=campaign&layout=edit&id=' . (int) $id.'&Itemid=' . $itemId, false); ?>" method="POST" name="adminForm" id="campaign-form" class="form-validate ucm-form-styling create-campaign">
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
						<?php echo strtoupper(Text::_("COM_TJGOPHISH_EDIT_CAMPAIGN") . " : " . $this->item->gophish_campaign_title); ?>
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
						<?php echo strtoupper(Text::_("COM_TJGOPHISH_CREATE_CAMPAIGN")); ?>
					<h1>
				</div>
			</div>
-->
			<?php
			}
			?>
			<?php
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
			<?php 
			
				$groupFormLink = 'index.php?option=com_tjgophish&view=group&layout=edit_popup';
				$addGroupFormLink = Route::_($groupFormLink . '&tmpl=component');?>
				<div class="add-group-link clearfix">
					<a class="btn-small pull-right" href="javascript:void(0);"
					onclick="groupForm.openGroupFormPopup('<?php echo addslashes($addGroupFormLink);?>','groupadd-form-popup')">
						<i class="icon-plus"></i><?php echo Text::_('COM_TJGOPHISH_ADD_GROUP'); ?>
					</a>
				</div>
				<div class="mt-3 campaign-buttonss float-end">
					<?php
					if (empty($this->item->id))
					{
						?>
						<div class="btn-group">
							<button type="button" class="btn btn-primary" onclick="Joomla.submitbutton('campaign.save')">
								<?php echo Text::_('COM_TJGOPHISH_CAMPAIGN_SAVE_AND_CLOSE') ?>
							</button>
						</div>
						<?php
					}
					?>
					<div class="btn-group">
						<a  href="<?php echo Route::_('index.php?option=com_tjgophish&view=campaigns&Itemid='.$campaignsMenuItem->id, false); ?>">
							<button type="button" class="btn">
								<?php echo Text::_('JCANCEL'); ?>
							</button>
						</a>
					</div>
				</div>
				<div class="clearfix"></div>
				</div>
				<div class="col-xs-12 col-md-6">
					<div class="tjgophish-template-preview hidden">
						<h4>
						<?php echo Text::_("COM_TJGOPHISH_TEMPLATE_PREVIEW");?>
						<h4>
						<hr>
					</div>
					<div class="tjgophish-template-subject-title hidden">
						<strong>
							<?php echo Text::_("COM_TJGOPHISH_TEMPLATE_SUBJECT_TITLE");?>
						</strong>
					</div>
					<div class="tjgophish-template-subject">
					</div>
					<br>
					<div class="tjgophish-template-text-title hidden">
						<strong>
							<?php echo Text::_("COM_TJGOPHISH_TEMPLATE_TEXT_TITLE");?>
						</strong>
					</div>
					<div class="tjgophish-template-text">
					</div>
					<br>
					<div class="tjgophish-template-html-title hidden">
						<strong>
							<?php echo Text::_("COM_TJGOPHISH_TEMPLATE_HTML_TITLE");?>
						</strong>
					</div>
					<div class="tjgophish-template-html">
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
