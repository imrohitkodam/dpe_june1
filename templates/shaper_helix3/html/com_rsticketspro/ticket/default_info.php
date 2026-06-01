<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\HTML\HTMLHelper;

use Joomla\CMS\Language\Text;
use Joomla\Registry\Registry;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;

HTMLHelper::_('formbehavior.chosen', 'select');

HTMLHelper::_('script', 'media/com_dpe/js/rsticket.min.js');
HTMLHelper::_('script', 'media/com_dpe/js/tagsinput.min.js');
HTMLHelper::_('script', 'media/com_multiagency/js/multiagencyService.js');
HTMLHelper::_('script', 'media/com_multiagency/js/multiagency.js');
HTMLHelper::_('stylesheet', 'media/com_dpe/css/tagsinput.css');
HTMLHelper::_('jquery.token');
Text::script('RST_PLEASE_SELECT_CUSTOMER_OPTION');
?>
<div class="com-rsticketspro-edit-ticket col-xs-12 col-md-6">
<?php

JLoader::import('main', JPATH_SITE . '/components/com_dpe/helpers');
DpeMainHelper::getLanguageConstant();

$user = Factory::getUser();
$multiagencyParams = ComponentHelper::getParams('com_multiagency');
$orgAdminRoleId    = (int) $multiagencyParams->get('multiagency_school_admin_group', '0', 'INT');
$isOrgAdmin        = in_array($orgAdminRoleId, $user->groups);

$this->field->startFieldset();

	// Subject
	$label = $this->form->getLabel('subject');

	if ($this->isStaff && $this->permissions->update_ticket)
	{
		$input = $this->form->getInput('subject');
	}
	else
	{
		$input = $this->escape($this->ticket->subject);
	}

	$this->field->showField($label, $input, array('class' => 'clearfix'));


	// School
	if (!empty($this->ticket->school))
	{
		if ($this->canViewHistory)
		{
			// To get Agency dropdown list
			FormHelper::addFieldPath(JPATH_ADMINISTRATOR . '/components/com_tjfields/models/fields');
			$agencyList = FormHelper::loadFieldType('cluster', false);
			$this->agencyOptions = $agencyList->getOptionsExternally();

			$input = HTMLHelper::_(
							'select.genericlist', $this->agencyOptions,
							'ticket[cluster]', 'class="inputbox" name="cluster"', 'value', 'text',
							$this->ticket->school['agency_id']
							);

							$input .= '<a id="agencyInfoLink" href="javascript:void(0);">' . Text::_('COM_DPE_ORGANISATION_INFORMATION') . '</a>';
		}
		else
		{
			$input = '<div class="rst_editor">' . $this->escape($this->ticket->school['name']) . '</div>';
		}
		$label = '<label>' . Text::sprintf('RST_TICKET_SCHOOL_LBL', Text::_('COM_MULTIAGENCY_ORGANISATION')) . '</label>';

		$this->field->showField($label, $input, array('class' => 'clearfix select-ticket-org'));
	}

	?>
		<div class="control-group">
			<label class="control-label">
				<?php echo HTMLHelper::tooltip(Text::_('RST_TICKET_CC_USERS'), Text::sprintf('RST_TICKET_CC_USERS_DESC', Text::_('COM_MULTIAGENCY_ORGANISATION')), '', Text::_('RST_TICKET_CC_USERS')); ?>
			</label>
			<div class="controls">
				<?php
				$emails = new Registry($this->ticket->school['emails']);
				$selectedOptions = $emails['email'];

				if ($this->canViewHistory)
				{
					if(!empty($selectedOptions)?count($selectedOptions):0 == 1)
					{
						$seletedOptions = $selectedOptions[0];
					}

					foreach ($this->clusterUsers as $clusterUser)
					{
						$options[] = HTMLHelper::_('select.option', $clusterUser->email, $clusterUser->name . '(' . $clusterUser->email . ')');
					}

					// To get Agency dropdown list
					echo HTMLHelper::_(
						'select.genericlist', '',
						'jform[clusterusers][]', 'class="inputbox" name="clusterusers" multiple="true"'  , 'value', 'text',
						$selectedOptions);
					?>
					<i id="loader"></i>

				<?php
				}
				else
				{ ?>
					<div class="rst_editor"><?php echo implode(', ', (array) $selectedOptions);?></div>
				<?php
				}
				?>
			</div>
		</div>

<?php 	
	if ($this->canViewHistory)
	{
		$userccEmails = new Registry($this->ticket->school['user_cc_emails']);
		$dpeccEmails  = new Registry($this->ticket->school['dpe_cc_emails']);
		?>
		<div class="control-group">
			<label class="control-label">
				<?php echo Text::_('COM_RSTICKET_CC_USERS_ADDED_BY_USER'); ?>
			</label>
			<div class="controls">
				<input type="text" value="<?php echo implode(',', (array) $userccEmails['email']);?>" data-role="tagsinput" name="jform[user_cc]" id="user_cc" class="focus" aria-invalid="false"/>
			</div>
		</div>

		<div class="control-group">
			<label class="control-label">
				<?php echo Text::_('COM_RSTICKET_DPE_CC_USERS'); ?>
			</label>
			<div class="controls">
				<input type="text" value="<?php echo implode(',', (array) $dpeccEmails['email']);?>" data-role="tagsinput" name="jform[dpe_cc]" id="dpe_cc" class="focus" aria-invalid="false"/>
			</div>
		</div>
<?php } ?>
	<?php
	// Department
	$label = $this->form->getLabel('department_id');
	$input = $this->escape(Text::_($this->ticket->department->name));
	$this->field->showField($label, $input, array('class' => 'clearfix'));

	// Date
	$label = '<label>' . Text::_('RST_TICKET_DATE') . '</label>';
	$input = '<div class="rst_editor">' . HTMLHelper::_('date', $this->ticket->date, $this->dateFormat) . '</div>';
	$this->field->showField($label, $input, array('class' => 'clearfix'));

	// Status
	$label = $this->form->getLabel('status_id');

	if ($this->isStaff && $this->permissions->change_ticket_status)
	{
		$input = $this->form->getInput('status_id');
	}
	else
	{
		$input = $this->escape(Text::_($this->ticket->status->name));
	}

	$this->field->showField($label, $input, array('class' => 'clearfix'));

	// Code
	/*$label = '<label>' . Text::_('RST_TICKET_CODE') . '</label>';
	$input = '<div class="rst_editor">' . $this->escape($this->ticket->code) . '</div>';
	$this->field->showField($label, $input, array('class' => 'clearfix'));*/

	// Priority
	if (($this->isStaff && ($this->permissions->add_ticket_customers || $this->permissions->add_ticket_staff)) || $isOrgAdmin)
	{
		?>
		<div class="control-group clearfix">
			<div class="control-label">
				<label id="ticket_priority_id-lbl" for="ticket_priority_id" class="required">Priority<span class="star">&nbsp;*</span></label>
			</div>
			<div class="controls">
				<div class="input-append">
					<?php
					$priorityOptions = $this->priorities;
					
					// If for some reason it's empty, try to fetch it from the model
					if (empty($priorityOptions))
					{
						$priorityOptions = $this->getModel()->getPriorities();
					}

					echo HTMLHelper::_('select.genericlist', $priorityOptions, 'ticket[priority_id]', 'class="inputbox required" required="required"', 'value', 'text', $this->ticket->priority_id);
					?>
				</div>
			</div>
		</div>
		<?php
	}
	else
	{
		?>
		<div class="control-group clearfix">
			<div class="control-label">
				<label><?php echo Text::_('RST_TICKET_PRIORITY'); ?></label>
			</div>
			<div class="controls">
				<?php echo $this->escape(Text::_($this->ticket->priority->name)); ?>
				<input type="hidden" name="ticket[priority_id]" value="<?php echo (int) $this->ticket->priority_id; ?>" />
			</div>
		</div>
		<?php
	}

	// Staff
	$label = $this->form->getLabel('staff_id');

	if ($this->canAssignTickets)
	{
		$input = $this->form->getInput('staff_id');
	}
	else
	{
		// Assigned?
		if ($this->ticket->staff_id > 0)
		{
			$input = $this->escape($this->ticket->staff->get($this->userField));
		}
		else
		{
			$input = Text::_('RST_UNASSIGNED');
		}
	}

	$this->field->showField($label, $input, array('class' => 'clearfix'));

	// Customer
	$label = $this->form->getLabel('customer_id');

	if ($this->isStaff && ($this->permissions->add_ticket_customers || $this->permissions->add_ticket_staff))
	{
		$input = $this->form->getInput('customer_id');
	}
	else
	{
		$input = $this->escape($this->ticket->customer->get($this->userField));
	}

//	$this->field->showField($label, $input, array('class' => 'clearfix'));
	?>
<?php if ($this->isStaff && ($this->permissions->add_ticket_customers || $this->permissions->add_ticket_staff))
	{?>
	<div class="control-group clearfix">
		<div class="control-label">
			<label id="ticket_customer_id-lbl" for="ticket_customer_id" class="required">Customer<span class="star">&nbsp;*</span></label>
		</div>
		<div class="controls">
			<div class="input-append">
			<?php

			// This value is null if org is not selected in this case we need to show only ticket customer id in dropdown 
			if (!$this->clusterUsers)
			{
				$customerInfo = Factory::getUser($this->ticket->customer_id);
				$customerOptions[] = HTMLHelper::_('select.option', $customerInfo->id, $customerInfo->name . '(' . $customerInfo->email . ')');?>

				<input type='hidden' id = 'CustomerOptionid' value='<?php echo $customerInfo->id ?>'>
				<input type='hidden' id = 'CustomerOptionName' value='<?php echo $customerInfo->name . "(" . $customerInfo->email . ")" ?>'>
				<input type='hidden' id = 'CustomerOptionparam' value='<?php echo $this->ticket->customer->params ?>'>

			<?php }

			foreach ($this->clusterUsers as $clusterUser)
			{
				$customerOptions[] = HTMLHelper::_('select.option', $clusterUser->user_id, $clusterUser->name . '(' . $clusterUser->email . ')');
			}

			// To get Customer dropdown list
			echo  HTMLHelper::_(
							'select.genericlist', $customerOptions,
							'ticket[customer_id]', 'class="inputbox required" required="required" name="cluster"', 'value', 'text',
							$this->ticket->customer_id
							);

			?>
			</div>
		</div>
	</div>
<?php }
else
{?>

<!-- If don't have edit access then show only ticket owner in the drop-down list and this is needed for code written in default_reply -->
	<div class="control-group clearfix">
		<div class="control-label">
			<label id="ticket_customer_id-lbl" for="ticket_customer_id" class="required">Customer<span class="star">&nbsp;*</span></label>
		</div>
		<div class="controls">
			<div class="input-append">
				<?php 
				// To get Customer dropdown list
				$customerInfo = Factory::getUser($this->ticket->customer_id);
				$customerOptions[] = HTMLHelper::_('select.option', $customerInfo->id, $customerInfo->name . '(' . $customerInfo->email . ')');

				echo  HTMLHelper::_(
				'select.genericlist', $customerOptions,
				'ticket[customer_id]', 'class="inputbox required" required="required" name="cluster"', 'value', 'text',
				$this->ticket->customer_id
				);
				?>
			</div>
		</div>
	</div>

<?php 
}
?>
		<div class="control-group">
			<input type="hidden" name="jform[is_allow]" value="0">
			<div class="controls d-flex align-items-center">
				<?php	$Adminemails = new Registry($this->ticket->school['dpe_allow_admin']);?>
				<input type="checkbox"
					id="jform_is_allow"
					name="jform[is_allow]"
					value="1"
					onchange="toggleUserSelect();"
					<?php echo $Adminemails['is_allow'] ? 'checked' : ''; ?>>
					<label for="jform_is_allow" class="ms-2 mb-0">
								<?php echo Text::_('Select users with access to this ticket'); ?>
					</label>
			</div>
		</div>

		<div class="control-group" id="userSelectField" style="display: <?php echo $Adminemails['is_allow'] ? 'block' : 'none'; ?>;">
				<div class="controls">
					<?php

				$allowEmails=$Adminemails['email'];
				$selectedEmails=[];
				foreach($allowEmails as $userId){

					$customerInfo = Factory::getUser($userId);

					$selectedEmails[]=$customerInfo->email;

				}
					if ($this->canViewHistory)
					{
						
						// To get Agency dropdown list
						echo HTMLHelper::_(
							'select.genericlist','',
							'jform[admins][]',
							'class="inputbox" multiple="true"',
							'value',
							'text'
						);
				}
			else
			{ ?>
				<div class="rst_editor"><?php echo implode(', ', (array) $selectedEmails);?></div>
			<?php
			}
			?>
				</div>
		</div>

<input type="hidden" name="schoolId" id = 'schoolId' value="<?php echo $this->ticket->school['agency_id'];?>">
	<input type="hidden" name="ticketId" id="ticketId" value="<?php echo $this->ticket->id; ?>" />
<?php
$this->field->endFieldset();

if (($this->permissions && $this->permissions->update_ticket))
{
	?>
						<div class="control-group">
    <div class="control-label">
        <label for="hours">Time</label>
    </div>
    <div class="controls">
        <input type="number" name="jform[hours]" id="hours" min="0" max="23" value="0" style="width:62px !important; display:inline-block;" />
        :
        <input type="number" name="jform[minutes]" id="minutes" min="0" max="59" step="5" value="5" style="width:62px !important; display:inline-block;" />
    </div>
</div>
<?php
}
if (($this->permissions && $this->permissions->update_ticket) || $isOrgAdmin)
{
	?><button type="button" onclick="Joomla.submitbutton('ticket.updateinfo')" class="btn btn-primary pull-right">
		<?php echo Text::_('RST_UPDATE'); ?>
	</button>
<?php
}
?>
</div>
 <script>
	jQuery(document).ready(function(){
		jQuery(".select-ticket-org .chzn-container").click(function(){
			if(jQuery(".chzn-container").hasClass("chzn-with-drop")){
				jQuery("li:not(:first-child)").click(function(){
					jQuery("#agencyInfoLink").css("display","inline");
				});
			
			}
		});
	});


</script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const hoursField = document.getElementById("hours");
    const minutesField = document.getElementById("minutes");

    if (!hoursField || !minutesField) return;

    let startTime = Date.now();
    let lastElapsedMinutes = 0;

    setInterval(function () {
        let elapsedMs = Date.now() - startTime;
        let elapsedMinutes = Math.floor(elapsedMs / 60000);

        // Only start counting after 5 min
        if (elapsedMinutes >= 5 && elapsedMinutes > lastElapsedMinutes) {
            lastElapsedMinutes = elapsedMinutes;

            // Calculate how many minutes to add
            let extraMinutes = elapsedMinutes - 5; 

            let baseMinutes = 5; // your starting point
            let totalMinutes = baseMinutes + extraMinutes;

            let hours = Math.floor(totalMinutes / 60);
            let minutes = totalMinutes % 60;

            hoursField.value = hours;
            minutesField.value = minutes;
        }
    }, 1000); // check every second
});
</script>
