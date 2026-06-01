<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

HTMLHelper::_('behavior.keepalive');
// HTMLHelper::_('formbehavior.chosen', 'select');
$document = Factory::getDocument();
$document->addScript(Uri::root() . 'media/vendor/jquery/js/jquery.min.js');
$document->addStyleSheet(Uri::root() . 'media/com_dpe/css/dpe.css');
$document->addScript(Uri::root() . 'media/system/js/messages.min.js');
?>

<div class="">	
	<form action="" method="post" name="adminForm" id="adminForm" enctype="multipart/form-data" class="form-horizontal">
		<div>
			<?php foreach ($this->optionsData as $key => $optionData): ?>
				<?php //echo $optionData->id?>
				<div class="row">
					<div class="control-group col-sm-3">
						
						<div class="control-label">
							<label id="jform_ucmfieldvalue-lbl<?php echo $optionData->id;?>" for="jform_ucmfieldvalue">
								<?php echo Text::_('COM_DPE_UCM_BACKEND_STATUS');?>
							</label>
						</div>
						<div class="controls">

							<input type="text" name="jform[ucmfieldvalue_<?php echo $optionData->id;?>]" id="jform_ucmfieldvalue_<?php echo $optionData->id;?>" value="" class="form-control" readonly="">
						</div>
					</div>
					<div class="control-group col-sm-7">
						<input type="hidden" name="jform[jform_ucmfieldId_<?php echo $optionData->id;?>]" id="jform_ucmfieldId_<?php echo $optionData->id;?>" value="<?php echo $optionData->id;?>">

						<div class="control-label"><?php  $this->form->getLabel('fieldoption'); ?></div>
						<div class="controls"><?php echo $this->form->getInput('fieldoption'); ?></div>

						<?php	

						$options   = array();

						foreach ($this->fields as $this->field)
						{
							$options[] = HTMLHelper::_('select.option', $this->field->id, $this->field->title);
						}

						?>
						
						<script type="text/javascript">

							jQuery('#jform_ucmfieldvalue_'+<?php echo $optionData->id;?>).val("<?php echo $optionData->value;?>");
							jQuery('.group-move').css('display', 'none');

							setTimeout(function(){
								var fieldOption = jQuery('#jform_ucmfieldId_'+'<?php echo $optionData->id;?>');

								var controlGroup = fieldOption.closest('.control-group');

								// Update the list of field.
								var fieldValues   = '<?php echo json_encode($options);?>' 
								fieldValues       =  jQuery.parseJSON(fieldValues);

								var nextControls = controlGroup.find('.controls');
								var adjacentInput = nextControls.find('input[name="jform[fieldoption]"]');
								adjacentInput.attr('name','jform[fieldoption_<?php echo $optionData->id;?>]' )
								var valueToAdd = '<?php echo $optionData->id;?>';
								adjacentInput.val(valueToAdd);
								var container = jQuery('#subfieldList_jform_fieldoption_'+"<?php echo $optionData->id;?>");
								var tdElements = container.find('td[data-column]');

								tdElements.each(function(index, tdElement) {
									var controls = jQuery(tdElement).find('.controls input, .controls select');
									controls.each(function(controlIndex, controlElement) {
										var controlName = jQuery(controlElement).attr('name');
										var controlName = controlName.replace(/jform\[fieldoption\]/g, 'jform[fieldoption_<?php echo $optionData->id;?>]');
										jQuery(controlElement).attr('name',controlName);

										
										if (controlName.includes("sendnotificationfield")) {
											jQuery('select[name="'+controlName+'"]').empty();
											jQuery('select[name="'+controlName+'"]').append('<option value="">Select Field</option>');
											for (var i = 0; i < fieldValues.length; i++) {
												jQuery('select[name="'+controlName+'"]').append('<option value="' + fieldValues[i].value + '">' + fieldValues[i].text + '</option>');
											}
										}
										if (controlName.includes("sendnotificationemail")) {

											var targetTd = $('td:has([name="'+controlName+'"])');
											targetTd.addClass('d-none');
										}
										
									});
								});
							},1000)

							jQuery('#subfieldList_jform_fieldoption').attr("id", 'subfieldList_jform_fieldoption_'+"<?php echo $optionData->id;?>");	
						</script>

						<?php if (is_array($this->item) && !empty($this->item)){

							foreach($this->item as $iter => $item)
							{
								
								if($optionData->id == $item->ucm_field_option_id)
								{
									$subValue = json_decode($item->ucm_field_config);
									$uniqueKeyValue = $subValue->{"uniquekeytjnotification_".$optionData->id};
									unset($subValue->{"uniquekeytjnotification_".$optionData->id});


									?>

									<script type="text/javascript">

										setTimeout(function(){
											var fieldId = '<?php echo $item->ucm_field_option_id; ?>'
											var subformfieldValues   = '<?php echo json_encode($subValue);?>' 
											subformfieldValues       =  jQuery.parseJSON(subformfieldValues);

											console.log(subformfieldValues);
											var container = jQuery('#subfieldList_jform_fieldoption_'+"<?php echo $optionData->id;?>");
											var trElements = container.find('tr[data-group]');
											var tdElements = container.find('td[data-column]');
											var dataGroupValue = '';
												// Iterate over each matching <tr> element

												$.each(subformfieldValues, function(key, value) {								    

													trElements.each(function (index, element) {
														dataGroupValue = jQuery(element).data('group');
													});

													if (dataGroupValue !== key)
													{
														var newRowHtml = '<tr class="subform-repeatable-group" data-base-name="fieldoption" data-group="'+key+'" draggable="false" aria-grabbed="false" tabindex="0">' +
														'<td data-column="Send notification to">' +
														'<div class="control-group">' +
														'<div class="visually-hidden"><label id="jform_fieldoption__fieldoption0__sendNotificationto-lbl" for="jform_fieldoption__fieldoption0__sendNotificationto">Send notification to</label></div>' +
														'<div class="controls">' +
														'<select id="jform_fieldoption__fieldoption0__sendNotificationto" name="jform[fieldoption_'+<?php echo $optionData->id;?>+']['+key+'][sendNotificationto]" class="form-select" onchange="getNotificationType(event);" aria-describedby="jform_fieldoption__fieldoption0__sendNotificationto-desc">' +
														'<option value="userdefinedemail">User defined email</option>' +
														'<option value="specificemail">Specific email</option>' +
														'</select>' +
														'</div>' +
														'</div>' +
														'</td>' +
														'<td data-column="">' +
														'<div class="control-group">' +
														'<div class="visually-hidden"><label id="jform_fieldoption__fieldoption0__sendnotificationfield-lbl" for="jform_fieldoption__fieldoption0__sendnotificationfield"></label></div>' +
														'<div class="controls">' +
														'<select id="jform_fieldoption__fieldoption0__sendnotificationfield" name="jform[fieldoption_'+<?php echo $optionData->id;?>+']['+key+'][sendnotificationfield]" class="form-select">' +
														'<option value="">Select Field</option>' +
														'</select>' +
														'</div>' +
														'</div>' +
														'</td>' +
														'<td data-column="" class="d-none">' +
														'<div class="control-group">' +
														'<div class="visually-hidden"><label id="jform_fieldoption__fieldoption0__sendnotificationemail-lbl" for="jform_fieldoption__fieldoption0__sendnotificationemail"></label></div>' +
														'<div class="controls">' +
														'<input type="text" name="jform[fieldoption_'+<?php echo $optionData->id;?>+']['+key+'][sendnotificationemail]" id="jform_fieldoption__fieldoption0__sendnotificationemail" value="" class="form-control d-none validate-email">' +
														'</div>' +
														'</div>' +
														'</td>' +
														'<td data-column="">' +
														'<div class="control-group">' +
														'<div class="visually-hidden"><label id="jform_fieldoption__fieldoption0__uniquekeytjnotification-lbl" for="jform_fieldoption__fieldoption0__uniquekeytjnotification"></label></div>' +
														'<div class="controls">' +
														'<input type="text" name="jform[fieldoption_'+<?php echo $optionData->id;?>+']['+key+'][uniquekeytjnotification]" id="jform_fieldoption__fieldoption0__uniquekeytjnotification" value="" class="form-control uniquekeytjnotification" placeholder="Add notification key" onchange="getUniqueKeyUrl(this)">' +
														'</div>' +
														'</div>' +
														'</td>' +
														'<td>' +

														'<div class="btn-group">' +
														'<button type="button" class="group-add btn btn-sm btn-success" aria-label="Add">' +
														'<span class="icon-plus" aria-hidden="true"></span>' +
														'</button>' +
														'<button type="button" class="group-remove btn btn-sm btn-danger" aria-label="Remove">' +
														'<span class="icon-minus" aria-hidden="true"></span>' +
														'</button>' +
														'<button type="button" class="group-move btn btn-sm btn-primary" aria-label="Move">' +
														'<span class="icon-arrows-alt" aria-hidden="true"></span>' +
														'</button>' +
														'</div>' +
														'</td>' +
														'</tr>';

											            // Append the new row after the current row
											            container.append(newRowHtml);

											        }						    
											    });
jQuery('.group-move').css('display', 'none');
var container = jQuery('#subfieldList_jform_fieldoption_' + "<?php echo $optionData->id; ?>");
var trElements = container.find('tr[data-group]');

var fieldValues   = '<?php echo json_encode($options);?>' 
fieldValues       =  jQuery.parseJSON(fieldValues);

trElements.each(function (index, trElement) {
	var tdElements = jQuery(trElement).find('td[data-column]');

	tdElements.each(function (index, tdElement) {
		var controls = jQuery(tdElement).find('.controls input, .controls select');

		controls.each(function (controlIndex, controlElement) {
			var controlName = jQuery(controlElement).attr('name');
			controlName = controlName.replace(/jform\[fieldoption\]/g, 'jform[fieldoption_<?php echo $optionData->id; ?>]');

			var matches = controlName.match(/\[([^\]]+)\]\[([^\]]+)\]/);

			if (matches && matches.length > 2) {
				var secondBracketValue = matches[2];
			}

			if (controlName.includes("sendNotificationto")) {																		jQuery('select[name="' + controlName + '"]').val(subformfieldValues[secondBracketValue].sendNotificationto);
		}
		if (controlName.includes("sendnotificationfield")) 
		{
			jQuery('select[name="'+controlName+'"]').empty();
			jQuery('select[name="'+controlName+'"]').append('<option value="">Select Field</option>');

			for (var i = 0; i < fieldValues.length; i++) {

				jQuery('select[name="'+controlName+'"]').append('<option value="' + fieldValues[i].value + '">' + fieldValues[i].text + '</option>');
			}

			jQuery('select[name="' + controlName + '"]').val(subformfieldValues[secondBracketValue].sendnotificationfield);

			if (subformfieldValues[secondBracketValue].sendNotificationto == 'specificemail')
			{
				var targetTd = jQuery('td:has([name="'+controlName+'"])');
				targetTd.addClass('d-none');
			}

		}
		if (controlName.includes("sendnotificationemail")) 
		{
			jQuery('input[name="' + controlName + '"]').val(subformfieldValues[secondBracketValue].sendnotificationemail);

			if (subformfieldValues[secondBracketValue].sendNotificationto == 'specificemail')
			{	
				var targetTd = jQuery('td:has([name="'+controlName+'"])');
				if(targetTd.find('input[type="text"]').is(':hidden'))
				{
					targetTd.removeClass('d-none');
					jQuery('input[name="' + controlName + '"]').removeClass('d-none');
				}
			}						

		}
		if (controlName.includes("uniquekeytjnotification")) 
		{
			jQuery('input[name="' + controlName + '"]').val(subformfieldValues[secondBracketValue].uniquekeytjnotification);		
			getUniqueKeyUrl(jQuery('input[name="' + controlName + '"]')[0])			
		}

	});
	});
});											

},1000)
</script>
<?php	}
}
?>
<?php } 
?>
</div>
<div class="col-sm-2">
	<a href="<?php echo Uri::root();?>administrator/index.php?option=com_tjnotifications&view=notification&layout=edit&from=ucmnotification" target="_blank"class="btn btn-small btn-success"><?php echo Text::_('COM_DPE_UCM_CREATE_NEW_NOTIFICATION_KEY')?></a>
</div>
</div>
<?php endforeach ?>
<div>
	<button type="button" id='ucmpopupBtn'class="btn btn-primary float-end" onclick="ucmNotificationConfig(event);">Submit</button>			</div>

	<div>
	</div>
	<input type="hidden" name="jform[parentId]" value="<?php echo $this->parentId;?>">
</form>
</div>
<script type="text/javascript">

	

	$(document).on('click', '.group-add', function() { 

		var closestTr = $(this).closest('tr');

    // Find the closest table element
    var closestTable = closestTr.closest('table');

    // Get the ID of the closest table
    var tableId = closestTable.attr('id');
    var match = tableId.match(/_(\d+)$/);

	// Check if there is a match
	if (match) {
		var fieldValueId = match[1];
	}
	var fieldValues   = '<?php echo json_encode($options);?>' 
	fieldValues       =  jQuery.parseJSON(fieldValues);

	setTimeout(function(){
		jQuery('.group-move').css('display', 'none');
		var trElements = $('#' + tableId + ' tr');

		trElements.each(function(index, trElement) {

			var tdElements = $(trElement).find('td');

        // Iterate through each td
        tdElements.each(function(index, tdElement) {
            // Find controls within the current td
            var controls = $(tdElement).find('.controls input, .controls select');

            // Update the name attribute for each control
            controls.each(function(index, controlElement) {

            	var originalName = $(controlElement).attr('name');
            	var updatedName = originalName.replace(/jform\[fieldoption\]/g, 'jform[fieldoption_'+fieldValueId+']');
            	var trElements = $('#' + tableId + ' tr');
                // Update the name attribute
                $(controlElement).attr('name', updatedName);

                if (updatedName.includes("sendnotificationfield")) {

                	if (jQuery('select[name="'+updatedName+'"]').find('option:selected').val() == '') {

                		jQuery('select[name="'+updatedName+'"]').empty();
                		jQuery('select[name="'+updatedName+'"]').append('<option value="">Select Field</option>');
                		for (var i = 0; i < fieldValues.length; i++) {
                			jQuery('select[name="'+updatedName+'"]').append('<option value="' + fieldValues[i].value + '">' + fieldValues[i].text + '</option>');
                		}
                	}
                	
                }
                // TO show hide the specific email.
                if (updatedName.includes("sendnotificationemail")) {
                	var targetTd = $('td:has([name="'+updatedName+'"])');

                	if (targetTd.is(':hidden'))
                	{
                		targetTd.addClass('d-none');
                	}
                	else if(targetTd.find('input[type="text"]').is(':hidden'))
                	{
                		targetTd.addClass('d-none');
                	}
                }
            });
        });
    });
	},1500);
});

	
//  "technical backlog" - we need to move this code into js file..
	function ucmNotificationConfig(event)
	{	
		if (!validateEmailFields()) {

			return false;
		}
		var formData      = jQuery('#adminForm').serializeArray();

		var formDataObject = {};
		var isFormSubmit = 0;

		jQuery.each(formData, function(index, key) {

			if (key.name.includes('sendNotificationto')) {

				if (key.value == "userdefinedemail")
				{ 
					var fieldname = key.name.replace('sendNotificationto', 'sendnotificationfield');

					jQuery('select[name="' + fieldname + '"]').next().remove();
					isFormSubmit = 0; 

					if(!jQuery('select[name="' + fieldname + '"]').val())
					{ 
						var message = jQuery('<p>').attr('id', 'response').css('color','red').text('<?php echo Text::_('COM_DPE_UCM_NOTIFICATION_FIEL_CANNOT_EMPTY')?>');
						jQuery('select[name="' + fieldname + '"]').after(message);

						isFormSubmit++;
						return false;
					}
				}
				else if (key.value == "specificemail")
				{ 
					var fieldname = key.name.replace('sendNotificationto', 'sendnotificationemail');
					jQuery('input[name="' + fieldname + '"]').next().remove();
					isFormSubmit = 0; 
					if(!jQuery('input[name="' + fieldname + '"]').val())
					{ 
						var message = jQuery('<p>').attr('id', 'response').css('color','red').text('<?php echo Text::_('COM_DPE_UCM_NOTIFICATION_FIEL_CANNOT_EMPTY')?>');
						jQuery('input[name="' + fieldname + '"]').after(message);
						isFormSubmit++;
						return false;
					}
				}
			}

			if(key.name.includes('uniquekeytjnotification'))
			{ 
				var fieldname = key.name.replace('sendNotificationto', 'uniquekeytjnotification');
				isFormSubmit = 0;

				if(!jQuery('input[name="' + fieldname + '"]').val())
				{	
					jQuery('input[name="' + fieldname + '"]').next().remove();
					var message = jQuery('<p>').attr('id', 'response').css('color','red').text('<?php echo Text::_('COM_DPE_UCM_NOTIFICATION_FIEL_CANNOT_EMPTY')?>');
					jQuery('input[name="' + fieldname + '"]').after(message);
					isFormSubmit++;
					return false;
				}

				if(jQuery('input[name="' + fieldname + '"]').next().hasClass('notifikey') == false)
				{

					isFormSubmit++;
					return false;
				}
			}


		});

		if(isFormSubmit == 0)
		{
			jQuery.ajax({
				url: Joomla.getOptions('system.paths').root + "/administrator/index.php?option=com_dpe&task=ucmnotification.saveucmconfig&format=json",
				type: "POST",
				dataType: 'json',
				data: formData,				
				success:function(response)
				{ 
					if(response.data.result)
					{
						Joomla.renderMessages({"success":[response.data.msg]});
						jQuery('html, body').animate({ scrollTop: 0 }, 'slow');
					}
					else if(response.data.result == false)
					{
						Joomla.renderMessages({"error":[response.data.msg]});
						jQuery('html, body').animate({ scrollTop: 0 }, 'slow');
					}

				}
			})
		}

	}


	function validateEmailFields() {
		var isValid = true;

		jQuery('.validate-email').each(function () {
			var emailValue = jQuery(this).val().trim();

			if (emailValue !== '' && !isValidEmail(emailValue)) {
		            // Show an alert or handle the validation failure
		            alert('<?php echo Text::_('COM_DPE_UCM_EMAIL_FORMAT');?>' + emailValue);

		            isValid = false;
		            return false; // Break out of the loop
		        }
		    });

		return isValid;
	}
	function isValidEmail(email) {
		    // Use a regular expression to validate the email format
		    var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
		    return emailRegex.test(email);
		}



		function getNotificationType(event){

	var selectedOptionValue = $(event.target).val(); // Get the value of the selected option

	var parentTr = $(event.target).closest('tr');

    // Get the first and second td elements after the current select
    var firstTd = parentTr.find('td:eq(1)');
    var secondTd = parentTr.find('td:eq(2)');

    // Show or hide the td elements based on the selected value
    if (selectedOptionValue === 'userdefinedemail') {
    	console.log(firstTd);
    	firstTd.show();
    	firstTd.removeClass('d-none');
    	secondTd.hide();
    } else if (selectedOptionValue === 'specificemail') {

    	firstTd.hide();
    	secondTd.show();
    	secondTd.removeClass('d-none');
    	secondTd.find('.controls input').removeClass('d-none');

    }

}

function getUniqueKeyUrl(element) {
	var value = element.value;
	var notificationfieldName = element.name;

	if (value.length >= 3) {

		jQuery.ajax({
			url: Joomla.getOptions('system.paths').root + "/administrator/index.php?option=com_dpe&task=ucmnotification.getNotificationIdByUniqueKey&format=json",
			type: 'POST',
			data: { uniqueKey: value },
			dataType: 'json',
			success: function(response) {
				if(response.data.success)
				{	
					jQuery('input[name="' + notificationfieldName + '"]').next().remove();

					var messageElement = jQuery('<a>').attr('href', response.data.url).attr('target','_blank').addClass('notifikey').text('Link to Edit').css('font-weight','800').css('font-size','16').css('line-height', '40px');

					jQuery('input[name="' + notificationfieldName + '"]').after(messageElement);
				}
				else{

					jQuery('input[name="' + notificationfieldName + '"]').next().remove();
					var messageElement = jQuery('<p>').attr('id', 'response').css('color','red').text('<?php echo Text::_('COM_DPE_UCM_NOTIFICATION_KEYNOT_PRESENT')?>');

					jQuery('input[name="' + notificationfieldName + '"]').after(messageElement);
				}               
			}
		});
	}
}
</script>


