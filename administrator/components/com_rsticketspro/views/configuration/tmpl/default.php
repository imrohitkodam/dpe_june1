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
JHtml::_('formbehavior.chosen', '.advancedSelect');
?>
<form action="<?php echo JRoute::_('index.php?option=com_rsticketspro&view=configuration'); ?>" method="post" name="adminForm" id="adminForm">
	<?php
	echo RsticketsproAdapterGrid::sidebar();
	?>
	<fieldset class="form-horizontal">
	<?php
	foreach ($this->fieldsets as $name => $fieldset)
	{
		// add the tab title
		$this->tabs->addTitle($fieldset->label, $fieldset->name);
		
		// prepare the content
		$this->fieldset =& $fieldset;
		$this->fields 	= $this->form->getFieldset($fieldset->name);

		$content = $this->loadTemplate('fieldset');
		
		// add the tab content
		$this->tabs->addContent($content);
	}
	
	// render tabs
	$this->tabs->render();
	?>
	</fieldset>
	</div>
	
	<div>
		<?php echo JHtml::_('form.token'); ?>
		<input type="hidden" name="option" value="com_rsticketspro" />
		<input type="hidden" name="task" value="" />
	</div>
</form>