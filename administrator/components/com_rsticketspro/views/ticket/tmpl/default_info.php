<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

echo '<div class="form-horizontal">';

// subject
echo $this->form->getField('subject')->renderField();
	
// department
echo $this->form->getField('department_id')->renderField();
echo '<input type="hidden" name="hidden_department_id" value="' . $this->escape(JText::_($this->ticket->department->name)) . '">';

// date
echo $this->form->getField('date')->renderField();

// status
echo $this->form->getField('status_id')->renderField();

// code
echo $this->form->getField('code')->renderField();

// priority
echo $this->form->getField('priority_id')->renderField();

// staff
echo $this->form->getField('staff_id')->renderField();

// customer
echo $this->form->getField('customer_id')->renderField();

// alternative email
if ($this->showAltEmail)
{
	echo $this->form->getField('alternative_email')->renderField();
}

if (!empty($this->permissions->update_ticket))
{
	?>
	<button type="button" onclick="Joomla.submitbutton('ticket.updateinfo')" class="btn btn-primary"><?php echo JText::_('RST_UPDATE'); ?></button>
	<?php
}

echo '</div>';