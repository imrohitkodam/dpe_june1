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

$this->field->startFieldset();
	foreach ($this->ticket->fields as $field) {
		list($label, $input, $tooltip) = RSTicketsProHelper::showCustomField($field, array(), $this->permissions && $this->permissions->update_ticket_custom_fields);

		if (in_array($field->type, array('freetext','radio','checkbox'))) {
			$html =  '<div class="rst_editor">';
			if (in_array($field->type, array('radio','checkbox'))) $html .= '<div class="rst_custom_field">';
			$html .= $input;
			if (in_array($field->type, array('radio','checkbox'))) $html .= '</div>';
			$html .= '</div>';
			$input = $html;
		}

		$hasTooltip = !empty($tooltip) ? ' class="'.RSTicketsProHelper::tooltipClass().'" title="'.RSTicketsProHelper::tooltipText($tooltip).'"' : '';

		$this->field->showField('<label'.$hasTooltip.'>'.$label.'</label>', $input, array('class' => 'clearfix'));
	}
$this->field->endFieldset();

if ($this->permissions && $this->permissions->update_ticket_custom_fields) { ?>
	<button type="button" onclick="Joomla.submitbutton('ticket.updatefields')" class="btn btn-primary"><?php echo Text::_('RST_UPDATE'); ?></button>
<?php } ?>
