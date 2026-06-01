<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

JHtml::_('behavior.keepalive');
JHtml::_('behavior.formvalidator');

// Load JavaScript message titles
JText::script('ERROR');
JText::script('WARNING');
JText::script('NOTICE');
JText::script('MESSAGE');
?>
<form action="<?php echo JRoute::_('index.php?option=com_rsticketspro&view=note&tmpl=component&layout=edit&id='.(int) $this->item->id.'&ticket_id='.$this->ticket_id); ?>" method="post" name="adminForm" id="adminForm" class="form-validate">
	<div class="rst_button_spacer">
		<button type="button" class="btn btn-success" onclick="Joomla.submitbutton('note.apply');"><i class="icon-apply icon-white"></i> <?php echo JText::_('JAPPLY'); ?></button>
		<button type="button" class="btn btn-danger" onclick="Joomla.submitbutton('note.cancel');"><i class="icon-cancel"></i> <?php echo JText::_('JCANCEL'); ?></button>
	</div>
	<?php
	foreach ($this->form->getFieldsets() as $fieldset)
	{
		echo $this->form->renderFieldset($fieldset->name);
	}
	?>

	<?php echo JHtml::_('form.token'); ?>
	<input type="hidden" name="task" value="" />
</form>