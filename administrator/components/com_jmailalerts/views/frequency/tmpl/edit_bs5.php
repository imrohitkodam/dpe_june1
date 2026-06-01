<?php
/**
 * @package     JMailAlerts
 * @subpackage  com_jmailalerts
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2022 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// Do not allow direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('behavior.keepalive');

// Add Javascript
$doc = Factory::getDocument();
$doc->addScriptDeclaration("
	Joomla.submitbutton = function(task)
	{
		if (task == 'frequency.cancel') {
			Joomla.submitform(task, document.getElementById('frequency-form'));
		}
		else {
			if (task != 'frequency.cancel' && document.formvalidator.isValid(document.getElementById('frequency-form'))) {
				Joomla.submitform(task, document.getElementById('frequency-form'));
			}
			else {
				alert('" . $this->escape(Text::_('JGLOBAL_VALIDATION_FORM_FAILED')) . "');
			}
		}
	}"
);
?>

<div class="<?php echo JMAILALERTS_WRAPPER_CLASS; ?>" id="jmailalerts-freqency">
	<form action="<?php echo Route::_('index.php?option=com_jmailalerts&view=frequency&layout=edit&id=' . (int) $this->item->id); ?>"
		method="post" enctype="multipart/form-data"
		name="adminForm" id="frequency-form" class="form-validate">

		<div class="row">
			<fieldset class="adminform">
				<?php echo $this->form->renderField('id'); ?>
				<?php echo $this->form->renderField('state'); ?>
				<?php echo $this->form->renderField('created_by'); ?>
				<?php echo $this->form->renderField('name'); ?>
				<?php echo $this->form->renderField('time_measure'); ?>
				<?php echo $this->form->renderField('duration'); ?>
			</fieldset>
		</div>

		<input type="hidden" name="task" value="" />
		<?php echo HTMLHelper::_('form.token'); ?>
	</form>
</div>
