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

if ($this->saved)
{
	JFactory::getDocument()->addScriptDeclaration('if (window.parent) { window.parent.document.location.reload(true); }');
}
?>
<form action="<?php echo JRoute::_('index.php?option=com_rsticketspro&view=ticketmessage&tmpl=component&layout=edit&id=' . (int) $this->item->id); ?>" method="post" name="adminForm" id="adminForm" class="form-validate form-vertical">
	<button type="button" class="btn btn-success" onclick="Joomla.submitbutton('ticketmessage.apply');"><i class="icon-apply icon-white"></i> <?php echo JText::_('JSAVE'); ?></button>
	<?php
	foreach ($this->form->getFieldsets() as $fieldset)
	{
		echo $this->form->renderFieldset($fieldset->name);
	}
	?>

	<?php echo JHtml::_('form.token'); ?>
	<input type="hidden" name="task" value="" />
</form>