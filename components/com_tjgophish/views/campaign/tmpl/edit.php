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

JFactory::getDocument()->addScriptDeclaration("
	Joomla.submitbutton = function(task)
	{
		if (task == 'campaign.cancel' || document.formvalidator.isValid(document.getElementById('campaign-form')))
		{
			Joomla.submitform(task, document.getElementById('campaign-form'));
		}

		return false;
	};
");

$fieldSetCounter = 0;
$id = isset($this->item->id) ? $this->item->id : 0;
?>

<div id="tjgophish-wrapper" class="container">
	<form action="<?php echo 'index.php?option=com_tjgophish&view=campaign&layout=edit&id=' . (int) $id; ?>" method="POST" name="adminForm" id="campaign-form" class="form-validate">
		<div class="form-horizontal">
		<?php
		if ($this->form)
		{
			if ($id != '0')
			{
				?>
				<div class="row">
					<div class="page-header">
						<h1 class="page-title">
						<?php echo strtoupper(Text::_("COM_TJGOPHISH_EDIT_CAMPAIGN") . " : " . $this->item->gophish_campaign_title); ?>
						<h1>
					</div>
				</div>
				<?php
			}
			else
			{
			?>
			<div class="row">
				<div class="page-header">
					<h1 class="page-title">
						<?php echo strtoupper(Text::_("COM_TJGOPHISH_CREATE_CAMPAIGN")); ?>
					<h1>
				</div>
			</div>
			<?php
			}
			?>
			<div class="row">
				<div class="alert alert-info"><?php echo Text::_("COM_TJGOPHISH_FORM_CAMPAIGN_UPDATE_NOT_ALLOWED");?></div>
			</div>
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
		<div class="row">
			<?php
			if (empty($this->item->id))
			{
				?>
				<div class="btn-group">
					<button type="button" class="btn btn-primary" onclick="Joomla.submitbutton('campaign.apply')">
						<?php echo Text::_('JSAVE') ?>
					</button>
				</div>
				<div class="btn-group">
					<button type="button" class="btn btn-primary" onclick="Joomla.submitbutton('campaign.save')">
						<?php echo Text::_('COM_TJGOPHISH_CAMPAIGN_SAVE_AND_CLOSE') ?>
					</button>
				</div>
				<?php
			}
			?>
			<div class="btn-group">
				<button type="button" class="btn" onclick="Joomla.submitbutton('campaign.cancel')">
					</span><?php echo Text::_('JCANCEL') ?>
				</button>
			</div>
		</div>
	</form>
</div>
