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
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Form\FormHelper;
use Joomla\Registry\Registry;

use Joomla\CMS\User\User;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Component\ComponentHelper;


HTMLHelper::_('behavior.keepalive');
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('formbehavior.chosen', 'select');
HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('script', 'media/com_dpe/js/rsticket.min.js');
HTMLHelper::_('jquery.token');
HTMLHelper::script('media/com_dpe/js/logsticket.js');
HTMLHelper::script('media/system/js/messages.min.js');

Text::script('RST_PLEASE_SELECT_CUSTOMER_OPTION');
JLoader::import('main', JPATH_SITE . '/components/com_dpe/helpers');
DpeMainHelper::getLanguageConstant();

$user = Factory::getUser();
$app  = Factory::getApplication();
$this->agency_id = '';

Text::script("RST_TICKET_SELECT_SCHOOL_ERROR");
Text::script("RST_TICKET_SELECT_SAME_SCHOOL");
Text::script("RST_TICKET_EMPTY_SCHOOL");



// Dont allow Trustee to add ticket
$params                    = ComponentHelper::getParams('com_multiagency');
$groupMultiagecnyTrusteeId = (INT) $params->get('multiagency_trustee_group');

/*
// Validate user login.
if (in_array($groupMultiagecnyTrusteeId, $user->groups))
{
	$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'warning');
	$link = base64_encode((string) Uri::getInstance());
	$app->redirect(Route::_('index.php?option=com_users&view=login&return=' . $link, false));
}
*/
$urlForBreach = Uri::getInstance()->toString();
$startPos = strpos($urlForBreach, 'clientType=');
$clientType =  substr($urlForBreach, $startPos + strlen('clientType='));

$currentUrl = JUri::current();

// Check if "add-ticket" is present in the URL
if (strpos($currentUrl, 'add-ticket') === false) {

	$doc = Factory::getDocument();
	$doc->addStyleSheet('templates/shaper_helix3/css/custom.css');
}

// Check user is logged-in or not
if (!$user->id)
{
	$app->enqueueMessage(Text::_('RST_CANNOT_SUBMIT_TICKET'), 'warning');
	$link = base64_encode((string) Uri::getInstance());
	$app->redirect(RSTicketsProHelper::route('index.php?option=com_users&view=login&return=' . $link, false));
}
?>

<div class="com-rsticketspro-submit-ticket<?php echo $this->escape($this->params->get('pageclass_sfx')); ?>">
	<div class="timelog-add-form activity-edit front-end-edit jlike-timelog modal-header addticketLogpop">
		
		<h3 class="activity-header fs-20">
			<?php echo Text::_('COM_DPE_UCM_CREATE_TICKET_POPUP_LABLE')?>	</h3>
		</div>
		<br>
<!--
	<div class="row-fluid">
		<div class="page-header">
			<h2><?php echo $this->escape($this->params->get('page_heading')); ?></h2>
		</div>
	</div>
-->

<?php  
$session = Factory::getSession();
if($session->get('modelError'))
{
	?>
	<div class="alert alert-warning"><?php echo $session->get('modelError');?></div>
	<?php
}

$session->clear('modelError');
if ($this->globalMessage)
{
	?>
	<div class="alert alert-warning"><?php echo $this->globalMessage;?></div>
	<?php
}

if ($this->submitMessage)
{
	?>
	<div class="alert alert-warning"><?php echo $this->submitMessage;?></div>
	<?php
}
?>

<form action="<?php echo Route::_('index.php?option=com_rsticketspro&view=submit'); ?>" method="post" name="adminForm"
	id="adminForm" class="form-validate form-horizontal add-todos ucm-form-styling notification-add-form col-sm-9" enctype="multipart/form-data">
	<?php
	$this->field->startFieldset();

			// Only staff members with enough permissions Can select existing users from the database
			/* @TO DO Will Remove code after discussion and getting feedback from client
			Code - This code shows super user can add ticket on behalf of other registered users.
			if ($this->canChangeSubmitType)
			{
				// Submit type
				$label = $this->form->getLabel('submit_type');
				$input = $this->form->getInput('submit_type');
				$this->field->showField($label, $input);

				// Email
				$label = $this->form->getLabel('email');
				$input = $this->form->getInput('email');
				$this->field->showField($label, $input, array('id' => 'rst_email_container'));

				// Name
				$label = $this->form->getLabel('name');
				$input = $this->form->getInput('name');
				$this->field->showField($label, $input, array('id' => 'rst_name_container'));

				// Customer
				$label = $this->form->getLabel('customer_id');
				$input = $this->form->getInput('customer_id');
				$this->field->showField($label, $input, array('id' => 'rst_customer_id_container', 'class' => 'clearfix'));
			}
			else*/
			{
				// Email
				$label = $this->form->getLabel('email');

				if ($this->user->get('id'))
				{
					$input = $this->escape($this->user->get('email'));
				}
				else
				{
					$input = $this->form->getInput('email');
				}

				$this->field->showField($label, $input, array('class' => 'clearfix'));

				if (!$this->user->get('id') && (bool) RSTicketsProHelper::getConfig('allow_password_change'))
				{
					$label = $this->form->getLabel('password');
					$input = $this->form->getInput('password');
					$this->field->showField($label, $input);
				}

				// Name
				$label = $this->form->getLabel('name');

				if ($this->user->get('id'))
				{
					$input = $this->escape($this->user->get('name'));
				}
				else
				{
					$input = $this->form->getInput('name');
				}

				$this->field->showField($label, $input, array('class' => 'clearfix'));
			}
			?>
			<div class="controls" hidden>

				<input type="checkbox" id="mulTciket" name="jform[mulTciket]" value="multipleTicket" onchange="showForm(this);">
				<label for="mulTciket"> Create Multiple Ticket </label><br>


			</div>
			
			<div class="hide" id="multiFormdata"> 

				<?php
				$label = $this->form->getLabel('cluster_id');
				$input = $this->form->getInput('cluster_id');
				$this->field->showField($label, $input);?>
			</div>

			

			
			<div class="control-group">
				<label for="jform_country" class="control-label">
					<?php echo HTMLHelper::tooltip(Text::sprintf('RST_TICKET_SCHOOL_TOOLTIP', Text::_('COM_MULTIAGENCY_ORGANISATION')), Text::sprintf('RST_TICKET_SCHOOL_DESC', Text::_('COM_MULTIAGENCY_ORGANISATION')), '', Text::sprintf('RST_TICKET_SCHOOL_LBL', Text::_('COM_MULTIAGENCY_ORGANISATION')) . ' * '); ?>
				</label>
				<div class="controls">
					<?php
					// To get Agency dropdown list
					FormHelper::addFieldPath(JPATH_ADMINISTRATOR . '/components/com_tjfields/models/fields');
					$agencyList = FormHelper::loadFieldType('cluster', false);
					$this->agencyOptions = $agencyList->getOptionsExternally();
					echo HTMLHelper::_(
						'select.genericlist', $this->agencyOptions,
						'jform[cluster]', 'class="inputbox required" required="required" name="cluster"'  , 'value', 'text'
					);
					?>
				</div>
			</div>
			<div class="control-group">
				<label class="control-label orgcc">
					<?php echo HTMLHelper::tooltip(Text::_('RST_TICKET_CC_USERS'), Text::sprintf('RST_TICKET_CC_USERS_DESC', Text::_('COM_MULTIAGENCY_ORGANISATION')), '', Text::_('RST_TICKET_CC_USERS')); ?>
				</label>
				<div class="controls">
					<?php
					// To get Agency dropdown list
					echo HTMLHelper::_(
						'select.genericlist', '',
						'jform[clusterusers][]', 'class="inputbox" name="clusterusers" multiple="true"'  , 'value', 'text'
					);
					?>
					<i id="loader"></i>
				</div>
			</div>
			<?php
			// Department

			// Get plugin 'ticket' of type 'rsticketspro'
			$plugin = PluginHelper::getPlugin('rsticketspro', 'ticket');

			// Check if plugin is enabled
			if ($plugin)
			{
				// Get plugin params
				$pluginParams = new Registry($plugin->params);
				$defaultDptId = $pluginParams->get('select_department');
			}

			$label = $this->form->getLabel('department_id');
			$input = $this->form->getInput('department_id', null, $defaultDptId);
			$this->field->showField($label, $input, array('class' => 'hide'));

			// Append the custom fields after the department
			foreach ($this->customFields as $customField)
			{
				if (in_array($customField->type, array('freetext','radio','checkbox')))
				{
					$input = '<div class="rst_editor">';

					if (in_array($customField->type, array('radio','checkbox')))
					{
						$input .= '<div class="rst_custom_field">';
						$input .= $customField->input;
					}

					if (in_array($customField->type, array('radio','checkbox')))
					{
						$input .= '</div>';
						$input .= '</div>';
					}
				}
				else
				{
					$input = $customField->input;
				}

				$this->field->showField(
					$customField->label, $input, array('class' => 'rst_field_container clearfix rst_field_container_' . $customField->department_id)
				);


			}
			$user    = Factory::getUser();
			$dpeAdmin      = $user->authorise('core.manageall', 'com_cluster');
			$orgAdmin      = (int) $params->get('multiagency_school_admin_group', '0', 'INT');			
			$trusteeUser   = ComponentHelper::getParams('com_multiagency')->get('multiagency_trustee_group');


			if (in_array($dpeAdmin,$user->groups) || in_array($orgAdmin,$user->groups) || in_array($trusteeUser,$user->groups) || ($clientType == 'com_tjucm.breachlog'))
			{

				?>

				<div class="control-group  customerclass">
					<div class="control-label">
						<label id="ticket_customer_id-lbl" for="ticket_customer_id" class="required">Customer<span class="">&nbsp;</span></label>
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


							<input type='hidden' id = 'jformcustomer_id' name="jform[customer_id]" value='<?php echo $this->ticket->customer->params ?>'>
							
						</div>
					</div>
					</div><?php }?>
					
					<div class="control-group">
						<input type="hidden" name="jform[is_allow]" value="0">
						<div class="controls d-flex align-items-center">
							<input type="checkbox" id="jform_is_allow" name="jform[is_allow]" value="1" onchange="toggleUserSelect();">
							<label for="jform_is_allow" class="ms-2 mb-0">
								<?php echo Text::_('Select users with access to this ticket'); ?>
							</label>
						</div>
					</div>


					<div class="control-group" id="userSelectField" style="display: none;">
							<div class="controls">
								<?php
										// Build cluster user options

								// To get Agency dropdown list
								echo HTMLHelper::_(
									'select.genericlist','',
									'jform[admins][]',
									'class="inputbox" multiple="true"',
									'value',
									'text'
								);
								?>
							</div>
					</div>
					<input type='hidden' id = 'jformucmpopup' name="jform[ucmpopup]" value=''>
					<?php 
				// Subject
					$label = $this->form->getLabel('subject');
					$input = $this->form->getInput('subject');
					$this->field->showField($label, $input);

				// Message
					$label = $this->form->getLabel('message');
					$input = $this->form->getInput('message');
					$this->field->showField($label, '<div class="rst_editor">' . $input . '</div>');

				// Priority
					$label = $this->form->getLabel('priority_id');
					$input = $this->form->getInput('priority_id');
					$this->field->showField($label, $input);

				// Prepend the upload message
					$label = '';
					$input = '';
					?>
					<div class='text-center'>
						<?php	$this->field->showField($label, $input, array('id' => 'rst_files_message_container'));?>
					</div>
					<?php
				// Files
					$label = $this->form->getLabel('files');
					$input = $this->form->getInput('files');
					$this->field->showField($label, $input, array('id' => 'rst_files_container'));

				// Captcha
					if ($this->hasCaptcha)
					{
						$label = $this->form->getLabel('captcha');
						$input = $this->form->getInput('captcha');
						$this->field->showField($label, $input);
					}

				// DPE - Hack for DPE specific - Client don't want conset from the user who has addding a ticket
					$label = $this->form->getLabel('consent');
					$input = $this->form->getInput('consent', array(), '1');
					$this->field->showField($label, $input, array('class' => 'hide'));

					// Submit button
					$label = '';
					$input = '<button type="button" class="btn btn-primary float-end" onclick="Joomla.submitbutton(\'submit.save\');">' . Text::_('RST_SUBMIT') . '</button>';
					$this->field->showField($label, $input, array('class' => 'col-sm-4 text-right pull-right ticketcore'));
					$this->field->endFieldset();
					if(in_array($dpeAdmin,$user->groups)){ ?>
					<div class="control-group">
    <div class="control-label">
        <label for="hours">Time</label>
    </div>
    <div class="controls">
        <input type="number" name="jform[hours]" id="hours" min="0" max="23" value="0" style="width:62px !important; display:inline-block;" />
        :
        <input type="number" name="jform[minutes]" id="minutes" min="0" max="59" step="5" value="5" style="width:62px !important; display:inline-block;" />
    </div>
</div><?php } ?>

					<div>
					<button type="button" id='ucmpopupBtn'class="btn btn-primary float-end" onclick="logticket.saveTicketInLinkField(event)">Submit</button>
						<div>
							<?php echo HTMLHelper::_('form.token'); ?>
							<input type="hidden" name="task" value="" />
						</div>
					</form>
				</div>
				<script type="text/javascript">

					Joomla.submitbutton = function(task) {
						if (task == 'submit.save') {
							var agency = document.getElementById('jformcluster').value;
							if ((agency == '') && (!jQuery('#mulTciket').prop('checked'))) {
								alert(Joomla.Text._('RST_TICKET_SELECT_SCHOOL_ERROR'));
								return false;
							}else if(jQuery('#mulTciket').prop('checked')){


								var values = jQuery('.cluster-info').map(function() {

									if ((jQuery(this).val() != ''))
									{
										return jQuery(this).val();	
									}


								}).get();

								var uniqueValues = {};
								values.forEach(function(value) {
									uniqueValues[value] = true;
								});

		// Check if the number of unique values matches the number of total values
		var allDifferent = Object.keys(uniqueValues).length === values.length;

		if (!allDifferent)
		{
			alert(Joomla.Text._('RST_TICKET_SELECT_SAME_SCHOOL'));
			return false;
		}
		else if((jQuery('.cluster-info').length) != (values.length))
		{
			alert(Joomla.Text._('RST_TICKET_EMPTY_SCHOOL'));

		}else {
			Joomla.submitform(task);

		}

	}
	else {
		Joomla.submitform(task);

	}
}
}

RSTicketsPro.departments[''] = {
	id: 0,
	priority: '',
	uploads: {
		allowed: false,
		message: '',
		max: 0
	}
};
<?php
foreach ($this->departments as $department)
{
	?>
	RSTicketsPro.departments[<?php echo $department->id; ?>] = {
		id: <?php echo $department->id; ?>,
		priority: <?php echo $department->priority_id; ?>,
		uploads: {
			allowed: <?php echo $department->upload ? 'true' : 'false'; ?>,
			message: '<?php echo addslashes($department->upload_message); ?>',
			max: <?php echo $department->upload_files; ?>
		}
	};
	<?php
}?>
RSTicketsPro.changeDepartment();
<?php
if ($this->canChangeSubmitType)
{
	?>
	RSTicketsPro.changeSubmitType();
	<?php
}?>
</script>

<!-- Code to handle the form elements and prepopulate-->
<script>

	jQuery(document).ready(function(){
		jQuery('#jform_cluster_id-lbl').css('margin-left', '179px');
		jQuery('#jform_email-lbl').attr('title', 'Your email');
		jQuery('#jform_name-lbl').attr('title', 'Your name');
		jQuery('controlgroup').css('margin-bottm','25px');
		jQuery('.control-label').css('width','15%');
		var span = $('label[for="jform_country"] .hasTooltip');
		span.attr('title', 'New Organisation');

		var spancc = jQuery('label.control-label.orgcc span.hasTooltip');
		spancc.attr('title', 'Organisation CC');

		if (window.location.href.indexOf('add-ticket') !== -1)
		{
			jQuery('#ucmpopupBtn').show();
			jQuery(".addticketLogpop").hide();	

		}
		jQuery('.closeticket').click(function(){
			closepopUp();
		})
    // Delegate the click event to a parent element
    jQuery(document).on('click', '.joomla-alert--close', function() {
    	jQuery(this).closest('joomla-alert').remove();

    });


    function closepopUp()
    {
    	localStorage.removeItem('formData');
    	localStorage.removeItem('fieldDatas');
    	localStorage.removeItem('client');
    	localStorage.removeItem('ticket');setTimeout(function(){
    		window.parent.location.reload();
    		window.onbeforeunload=null;
    		window.parent.SqueezeBox.close();
    	},700);

    }

    if(!jQuery('#jformcluster').val())
    {
    	jQuery('#ticketcustomer_id').empty();

    	jQuery('#ticketcustomer_id').append('<option value="">'+Joomla.JText._('RST_PLEASE_SELECT_CUSTOMER_OPTION')+'</option>');
    	jQuery('#ticketcustomer_id').trigger('chosen:updated');
    	jQuery("#jformcustomer_id").val();
    }

})

	var org = JSON.parse(localStorage.getItem('ticket'));

	if ((window.location.href.indexOf('add-ticket') == -1))
	{	
		jQuery('#mulTciket').closest('.controls').hide();

		jQuery('.ticketcore').hide();
		// jQuery("#rst_files_container").hide();
		jQuery('.closeticket').css('border','0');
		jQuery('.closeticket').css('background','none');
		jQuery('.closeticket').css('font-size','large');
		//jQuery('.controls').css('width','80%');
		jQuery("#jformucmpopup").val('ucmpopup');
		setTimeout(function(){
			jQuery("#ticketcustomer_id").val(org.toUserId);
			jQuery("#ticketcustomer_id").trigger("chosen:updated");

			jQuery("#jformcustomer_id").val(org.toUserId);

			var  useEmail = org.toUser; 
			var  regex = /\(([^)]+)\)/;
			useEmail = useEmail.match(regex);
			useEmails = useEmail[1].split(',');
			jQuery('.tox').css('width','100%');
		// jQuery("#jformclusterusers").val(useEmails).trigger("chosen:updated");
	},1000)

		jQuery("#jformcluster").val(org.clusterId);
		jQuery("#jformcluster").trigger("chosen:updated");
		jQuery("#jform_subject").val(org.subject);
		jQuery("#jform_message").val(org.message);


		setTimeout(function(){
			jQuery("#ticketcustomer_id").val(org.toUserId);
			jQuery("#ticketcustomer_id").trigger("chosen:updated");
		},5000)
	}

	jQuery('#ticketcustomer_id').on('change', function () {
            // Your code to handle the change event
            var selectedValue = jQuery(this).val();
            jQuery("#jformcustomer_id").val(selectedValue);
            
        });

	jQuery('document').ready(function(){
		jQuery('#ucmpopupBtn').hide();
		jQuery('#sbox-btn-close').click(function(){window.parent.SqueezeBox.close();})

		if ((window.location.href.indexOf('add-ticket') == -1))
		{	 
			jQuery('#ucmpopupBtn').css('display','block');
			jQuery('.adminform').css('margin-left','5%');
		}
	})
	jQuery(document).ready(function() {
		
		var isDpeAdmin = "<?php echo $user->authorise('core.manageall', 'com_cluster'); ?>";

		if (!isDpeAdmin)
		{
			jQuery('#mulTciket').closest('.controls').hide();
		}
		var windowWidth = jQuery(window).width();
		var form = jQuery('#adminform'); 

		if (windowWidth <= 767) {
			form.addClass('recommendation-form'); 
		} else {
			form.removeClass('recommendation-form'); 
		}
	})

	

</script>

<script type="text/javascript">

	function getCCusers(e)
	{
		var clusterId = jQuery(e).val();
		var id = jQuery(e).attr('id');
		var clusterusers = jQuery('#jformclusterusers');
		customerFieldId = id.replace(/(cluster_id)(?=[^_]*$)/, "MultiOrgCustomer");
		cccustomerFieldId = id.replace(/(cluster_id)(?=[^_]*$)/, "clusterusers");

		jQuery.ajax({
			url: Joomla.getOptions('system.paths').base + "/index.php?option=com_dpe",
			type: 'POST',
			data: {
				clusterId: clusterId,
				task: 'rsticket.getClusterUsers',
				format: 'json'
			},
			dataType: "json"
		}).done(function(data) {
			var userdata = data.data;
			var options = [];

           var options = []; // Assuming options is an array of objects

			// Iterate through the properties of the first object in `userdata`
			jQuery.each(userdata[0], function(val, text) {
				if (text.name) {
			        // Create an option object with the name and email
			        var option = {
			        	text: text.name + ' (' + text.email + ')',
			        	value: text.user_id,
			        	email: text.email
			        };
			        options.push(option); // Push the option object to the options array
			    }
			});
			var selectElementId = customerFieldId;
			var selectCCElementId = cccustomerFieldId;

			var selectElement = jQuery('#' + selectElementId);
			var selectCcElement = jQuery('#' + selectCCElementId);

			jQuery.each(options, function(index, option) {
				selectElement.append('<option value="' + option.value + '">' + option.text + '</option>');
				selectCcElement.append('<option value="' + option.email + '">' + option.text + '</option>');
			});
			selectElement.trigger("chosen:updated");
			selectCcElement.trigger("chosen:updated");

		})
	}

	function showForm(checkboxElem) {		
		if (checkboxElem.checked) {
			jQuery('#multiFormdata').removeClass('hide');
			jQuery('#jformcluster_chosen').closest('.control-group').hide();
			jQuery('#jformclusterusers_chosen').closest('.control-group').hide();
			jQuery('#ticketcustomer_id_chosen').closest('.control-group').hide();


		} else {
			jQuery('#multiFormdata').addClass('hide');
			jQuery('#jformcluster_chosen').closest('.control-group').show();
			jQuery('#jformclusterusers_chosen').closest('.control-group').show();
			jQuery('#ticketcustomer_id_chosen').closest('.control-group').show();
		}
	}
	
	$(document).on('click', '.chosen-with-drop', function(event) {

		event.preventDefault();
		setTimeout(function(){

			var idWithChosen = event.target.parentElement.offsetParent.id;
			var modifiedId = idWithChosen.replace("_chosen", "");
			var selectIds = [];

// Find all select elements with class 'cluster-info'
$('select.cluster-info').each(function() {
	
	if ($(this).attr('id') != modifiedId)
	{
		selectIds.push($(this).attr('id'));
		var selectedValue = $(this).chosen().find(":selected").text(); 
		if (selectedValue && (selectedValue!='Select Organisation'))
		{
			jQuery('.chosen-results li').filter(function() {
				return $(this).text().trim() === selectedValue;
			}).css('display', 'none');

		}
	}
});

},2000)
		
	})
</script> 
<?php if(in_array($dpeAdmin,$user->groups)){?>
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
<?php } ?>