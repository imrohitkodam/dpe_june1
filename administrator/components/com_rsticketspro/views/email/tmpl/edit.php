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

<form action="<?php echo JRoute::_('index.php?option=com_rsticketspro&view=email&layout=edit&id='.(int) $this->item->id); ?>" method="post" name="adminForm" id="adminForm" class="form-validate form-horizontal">
	<?php
	foreach ($this->form->getFieldsets() as $fieldset)
	{
		echo $this->form->renderFieldset($fieldset->name);
	}
	?>
	<div class="alert alert-info">
		<p>
		<?php
		switch ($this->item->type)
		{
			case 'add_ticket_customer':
				$placeholders = array('{live_site}', '{ticket}', '{customer_name}', '{customer_username}', '{customer_email}', '{code}', '{subject}', '{priority}', '{status}', '{message}', '{custom_fields}', '{field-<strong>CUSTOM_FIELD_NAME</strong>}', '{department_id}', '{department_name}');
				break;

			case 'add_ticket_staff':
				$placeholders = array('{live_site}', '{ticket}', '{customer_name}', '{customer_username}', '{customer_email}', '{staff_name}', '{staff_username}', '{staff_email}', '{code}', '{subject}', '{priority}', '{status}', '{message}', '{custom_fields}', '{field-<strong>CUSTOM_FIELD_NAME</strong>}', '{department_id}', '{department_name}');
				break;

			case 'add_ticket_notify':
			case 'add_ticket_reply_staff':
			case 'add_ticket_reply_customer':
				$placeholders = array('{live_site}', '{ticket}', '{customer_name}', '{customer_username}', '{customer_email}', '{staff_name}', '{staff_username}', '{staff_email}', '{user_name}', '{user_username}', '{user_email}', '{code}', '{subject}', '{priority}', '{status}', '{message}', '{custom_fields}', '{field-<strong>CUSTOM_FIELD_NAME</strong>}', '{department_id}', '{department_name}');
				break;

			case 'notification_email':
				$placeholders = array('{live_site}', '{ticket}', '{customer_name}', '{customer_username}', '{customer_email}', '{staff_name}', '{staff_username}', '{staff_email}', '{code}', '{subject}', '{priority}', '{status}', '{inactive_interval}', '{close_interval}');
				break;

			case 'reject_email':
				$placeholders = array('{live_site}', '{customer_name}', '{customer_email}', '{department}', '{subject}', '{reason}');
				break;

			case 'new_user_email':
				$placeholders = array('{live_site}', '{username}', '{password}', '{email}');
				break;

			case 'notification_max_replies_nr':
				$placeholders = array('{live_site}', '{ticket}', '{customer_name}', '{customer_username}', '{customer_email}', '{user_name}', '{user_username}', '{user_email}', '{code}', '{subject}', '{priority}', '{status}', '{message}', '{replies}', '{department_id}', '{department_name}');
				break;

			case 'notification_replies_with_no_response_nr':
			case 'notification_not_allowed_keywords':
				$placeholders = array('{live_site}', '{ticket}', '{customer_name}', '{customer_username}', '{customer_email}', '{staff_name}', '{staff_username}', '{staff_email}', '{user_name}', '{user_username}', '{user_email}', '{code}', '{subject}', '{priority}', '{status}', '{message}', '{replies}', '{department_id}', '{department_name}');
				break;

			case 'notification_department_change':
				$placeholders = array('{ticket}', '{message}', '{code}', '{new_code}', '{subject}', '{department_name}', '{department_id}', '{priority}', '{status}', '{customer_name}', '{customer_email}', '{customer_username}', '{staff_name}', '{staff_email}', '{staff_username}', '{department_from}', '{department_to}', '{custom_fields}', '{field-<strong>CUSTOM_FIELD_NAME</strong>}');
				break;

			case 'feedback_followup_email':
				$placeholders = array('{ticket}', '{message}', '{code}', '{subject}', '{department_name}', '{department_id}', '{priority}', '{status}', '{customer_name}', '{customer_email}', '{customer_username}', '{staff_name}', '{staff_email}', '{staff_username}', '{no}', '{yes}', '{feedback}', '{custom_fields}', '{field-<strong>CUSTOM_FIELD_NAME</strong>}');
				break;
		}
		if (!empty($placeholders))
		{
			echo implode(', ', $placeholders);
		}
		?>
		</p>
	</div>
	
	<div>
		<?php echo JHtml::_('form.token'); ?>
		<input type="hidden" name="task" value="" />
	</div>
</form>