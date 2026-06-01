<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
$doc = Factory::getDocument();
$doc->addStyleSheet(Uri::root() . 'templates/shaper_helix3/css/custom.css');


HTMLHelper::_('behavior.keepalive');
RSTicketsProHelper::tooltipLoad();
// Load JavaScript message titles
Text::script('ERROR');
Text::script('WARNING');
Text::script('NOTICE');
Text::script('MESSAGE');
HTMLHelper::_('behavior.formvalidator');

?>
<script type="text/javascript">
Joomla.submitbutton = function(task)
{
	if (task == 'note.cancel' || document.formvalidator.isValid(document.getElementById('adminForm'))) {
		Joomla.submitform(task, document.getElementById('adminForm'));
	} else {
		alert('<?php echo $this->escape(Text::_('COM_RSTICKETSPRO_PLEASE_COMPLETE_ALL_FIELDS'));?>');
	}
}
</script>

<form action="<?php echo Route::_('index.php?option=com_rsticketspro&view=note&tmpl=component&layout=edit&id='.(int) $this->item->id.'&ticket_id='.$this->ticket_id); ?>" method="post" name="adminForm" id="adminForm" class="form-validate note-view"> 
<!-- Override code start here...Added class note-view -->
<div class="btn-group float-end mb-3 me-3">
	<button type="button" class="btn btn-primary me-2" onclick="Joomla.submitbutton('note.apply');"><i class="icon-apply icon-white"></i> <?php echo Text::_('JAPPLY'); ?></button>
	<button type="button" class="btn" onclick="Joomla.submitbutton('note.cancel');"><i class="icon-cancel"></i> <?php echo Text::_('JCANCEL'); ?></button>
</div>
<div class="clearfix"></div>
	<?php
	$legend = $this->item->id ? Text::_('RST_EDIT_NOTE') : Text::_('RST_ADD_NOTE');

	$this->field->startFieldset($legend);
	foreach ($this->form->getFieldset() as $field) {
		$this->field->showField($field->hidden ? '' : $field->label, $field->input);
	}
	$this->field->endFieldset();
	?>
	
	<?php echo HTMLHelper::_('form.token'); ?>
	<input type="hidden" name="task" value="" />
</form>
