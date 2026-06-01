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
RSTicketsProHelper::tooltipLoad();
// Load JavaScript message titles
JText::script('ERROR');
JText::script('WARNING');
JText::script('NOTICE');
JText::script('MESSAGE');
JHtml::_('behavior.formvalidator');
?>
<script type="text/javascript">
Joomla.submitbutton = function(task)
{
	if (task == 'kbtemplate.cancel' || document.formvalidator.isValid(document.getElementById('adminForm'))) {
		Joomla.submitform(task, document.getElementById('adminForm'));
	} else {
		alert('<?php echo $this->escape(JText::_('COM_RSTICKETSPRO_PLEASE_COMPLETE_ALL_FIELDS'));?>');
	}
}
</script>

<form action="<?php echo JRoute::_('index.php?option=com_rsticketspro&view=kbtemplate'); ?>" method="post" name="adminForm" id="adminForm" class="form-validate" enctype="multipart/form-data">
	<div id="j-sidebar-container" class="span2">
		<?php echo $this->sidebar; ?>
	</div>
	<div id="j-main-container" class="span10">
	<?php
	$this->field->startFieldset();
	foreach ($this->form->getFieldset() as $field) {
		$start 	= $field->fieldname == 'kb_template_body' || $field->fieldname == 'kb_template_ticket_body' ? '<div class="rst_editor">' : '';
		$end 	= $field->fieldname == 'kb_template_body' || $field->fieldname == 'kb_template_ticket_body' ? '</div>' : '';
		$this->field->showField($field->hidden ? '' : $field->label, $start.$field->input.$end);
	}
	$this->field->endFieldset();
	?>
	</div>
	
	<div>
		<?php echo JHtml::_('form.token'); ?>
		<input type="hidden" name="task" value="" />
	</div>
</form>