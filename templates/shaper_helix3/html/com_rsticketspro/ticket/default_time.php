<?php
/**
 * @package    RSTicketsPro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Language\Text;
?>

<div class="col-xs-12 col-md-6">
	<?php 
	$this->field->startFieldset();
		$label           = $this->form->getLabel('time_spent');
		$input           = $this->form->getInput('time_spent');
		$time_spent_text = Text::_('RST_TIME_UNIT_' . RSTicketsProHelper::getConfig('time_spent_unit'));
		$this->field->showField($label, $input . ' ' . $time_spent_text);
	$this->field->endFieldset();
	?>

	<button type="button" onclick="Joomla.submitbutton('ticket.savetimespent')" class="btn btn-primary pull-right">
		<?php echo Text::_('RST_UPDATE');?>
	</button>
</div>
