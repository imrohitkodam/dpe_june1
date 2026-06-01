<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

// Load JavaScript message titles
JText::script('ERROR');
JText::script('WARNING');
JText::script('NOTICE');
JText::script('MESSAGE');
JText::script('RST_PLEASE_SELECT');
JText::script('RST_DEPARTMENT');
JText::script('RST_TICKET_SUBJECT');
JText::script('RST_TICKET_MESSAGE');
JText::script('RST_PRIORITY');
JText::script('RST_TICKET_STATUS');
JText::script('RST_CUSTOM_FIELD');
JText::script('RST_IS_EQUAL');
JText::script('RST_IS_NOT_EQUAL');
JText::script('RST_IS_LIKE');
JText::script('RST_IS_NOT_LIKE');
JText::script('RST_AND');
JText::script('RST_OR');
JText::script('RST_IF');

JHtml::_('behavior.formvalidator');
JHtml::_('behavior.keepalive');
JHtml::_('jquery.framework');
JHtml::_('script', 'com_rsticketspro/kbrules.js', array('relative' => true, 'version' => 'auto'));
?>
<form action="<?php echo JRoute::_('index.php?option=com_rsticketspro&view=kbrule&layout=edit&id='.(int) $this->item->id); ?>" method="post" name="adminForm" id="adminForm" class="form-validate form-horizontal">
	<?php
	foreach ($this->form->getFieldsets() as $fieldset)
	{
		echo $this->form->renderFieldset($fieldset->name);
	}
	?>
	
	<div>
		<?php echo JHtml::_('form.token'); ?>
		<input type="hidden" name="task" value="" />
	</div>
</form>