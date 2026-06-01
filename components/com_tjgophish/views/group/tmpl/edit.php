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
		if (task == 'group.cancel' || document.formvalidator.isValid(document.getElementById('group-form')))
		{
			Joomla.submitform(task, document.getElementById('group-form'));
		}

		return false;
	};
");

$fieldSetCounter = 0;
$id = isset($this->item->id) ? $this->item->id : 0;
?>
<div id="tjgophish-wrapper" class="container">
	<form action="<?php echo 'index.php?option=com_tjgophish&view=group&layout=edit&id=' . (int) $id; ?>" method="POST" name="adminForm" id="group-form" class="form-validate">
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
						<?php echo strtoupper(Text::_("COM_TJGOPHISH_EDIT_GROUP") . " : " . $this->item->gophish_group_title); ?>
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
						<?php echo strtoupper(Text::_("COM_TJGOPHISH_CREATE_GROUP")); ?>
					<h1>
				</div>
			</div>
			<?php
			}

			// Iterate through the form fieldsets and display each one
			$fieldSets = $this->form->getFieldsets();
			?>
			<div class="row">
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
			<?php
		}
		?>
		</div>
		<input type="hidden" name="task" value="" />
		<?php echo HTMLHelper::_('form.token'); ?>
		<div class="row">
			<div class="btn-group">
				<button type="button" class="btn btn-primary" onclick="Joomla.submitbutton('group.apply')">
					<?php echo Text::_('JSAVE') ?>
				</button>
			</div>
			<div class="btn-group">
				<button type="button" class="btn btn-primary" onclick="Joomla.submitbutton('group.save')">
					<?php echo Text::_('COM_TJGOPHISH_GROUP_SAVE_AND_CLOSE') ?>
				</button>
			</div>
			<div class="btn-group">
				<button type="button" class="btn" onclick="Joomla.submitbutton('group.cancel')">
					</span><?php echo Text::_('JCANCEL') ?>
				</button>
			</div>
		</div>
	</form>
</div>
