jQuery(document).on('change', '#jformcluster, #ticketcluster', function(e) {
    var clusterId = jQuery(this).val();
    var ticketId = jQuery('#ticketId').val();
    var clusterusers = jQuery('#jformclusterusers');
    clusterusers.empty();
    clusterusers.trigger("liszt:updated");
    var customerClusterUsers = jQuery('#ticketcustomer_id');
    customerClusterUsers.empty();
    customerClusterUsers.append('<option value="">'+Joomla.JText._('RST_PLEASE_SELECT_CUSTOMER_OPTION')+'</option>');

    customerClusterUsers.trigger("liszt:updated");

        // Clear the new admins field
        var userSelectBox = jQuery('#jformadmins');
        userSelectBox.empty();
        userSelectBox.trigger("liszt:updated"); // if using Chosen

    var loader = jQuery('#loader');
    loader.html('');
    loader.addClass('fa fa-circle-o-notch fa-spin');

    if (!ticketId) {
        ticketId = '';
    }
var clusterId = jQuery(this).val();

jQuery.ajax({
    url: Joomla.getOptions('system.paths').base + "/index.php?option=com_dpe",
    type: 'POST',
    data: {
        clusterId: clusterId,
        ticketId: ticketId,
        task: 'rsticket.getAdminUsersByClusterId',
        format: 'json'
    },
    dataType: "json"
}).done(function(data) {
    if (data && (data.success == true)) {
        var userdata = data.data;
        var options = userSelectBox.prop('options');

        jQuery.each(userdata[0], function(val, text) {
            if (text.name) {
                options[options.length] = new Option(text.name + ' (' + text.email + ')', text.user_id);
            }
        });

        if (userdata[1] != null && userdata[1].trim() != "") {
            var seletedEmails = JSON.parse(userdata[1]); // Contains 'email' array
            var seletedCustomer = userdata[2]; // Single selected customer ID as string or number
        
            jQuery('#jformadmins option').each(function() {
                var clusterUserField = jQuery(this);
                var value = clusterUserField.val();
        
                if (jQuery.inArray(value, seletedEmails['email']) >= 0) {
                    clusterUserField.prop('selected', true);
        
                    // If this option value is same as selected customer ID, disable it
                    if (seletedCustomer && value == seletedCustomer) {
                        clusterUserField.prop('disabled', true);
                    }
                }
            });
        }        
        userSelectBox.trigger("chosen:updated");
    }
}).fail(function(result) {
    console.log(result);
}).always(function() {
    loader.removeClass('fa fa-circle-o-notch fa-spin');
});
    jQuery.ajax({
        url: Joomla.getOptions('system.paths').base + "/index.php?option=com_dpe",
        type: 'POST',
        data: {
            clusterId: clusterId,
            ticketId: ticketId,
            task: 'rsticket.getClusterUsers',
            format: 'json'
        },
        dataType: "json"
    }).done(function(data) {
        if (data && (data.success == true)) {
            var userdata = data.data;

            var options = clusterusers.prop('options');

            jQuery.each(userdata[0], function(val, text) {
                if (text.name) {
                    options[options.length] = new Option(text.name + ' (' + text.email + ')', text.email);
                }
            });

            if (userdata[1] != null && userdata[1].trim() != "") {
                var seletedEmails = JSON.parse(userdata[1]);

                jQuery('#jformclusterusers option').each(function() {
                    var clusterUserField = jQuery(this);
                    var value = clusterUserField.val();
                    if (jQuery.inArray(value, seletedEmails['email']) >= 0) {
                        clusterUserField.attr('selected', 'selected');
                    }
                });
            }

            clusterusers.trigger("chosen:updated");

            var customerOptions = customerClusterUsers.prop('options');
            jQuery.each(userdata[0], function(val, text) {
                if (text.name) {
                    customerOptions[customerOptions.length] = new Option(text.name + ' (' + text.email + ')', text.user_id);
                }
            });

            if (userdata[2] != null && userdata[2].toString().trim() != "") {
				var seletedCustomer = JSON.parse(userdata[2]);
                jQuery('#ticketcustomer_id option').each(function() {
                    var clusterUserField = jQuery(this);
                    var value = clusterUserField.val();
                    if (value == seletedCustomer) {
                        clusterUserField.attr('selected', 'selected');
                    }
                });
            }

			// If customer is guest user then show selected in customer dropdown
			if (userdata[3] != null && userdata[3] != "")
			{
				customerClusterUsers.append('<option value='+userdata[3].id+' selected="selected">'+ userdata[3].name + ' (' + userdata[3].email + ')'+'</option>');
			}

            customerClusterUsers.trigger("chosen:updated");


        } else if (!data) {
            loader.html(Joomla.JText._("COM_DPE_NO_USERS"));
            if(jQuery('#jformclusterusers_chzn ul li').length == 1 && (jQuery("#CustomerOptionparam").val() =='{}' || jQuery("#CustomerOptionparam").val() =='' ) )
            i.empty(),i.append('<option value="'+jQuery("#CustomerOptionid").val()+'">' + jQuery("#CustomerOptionName").val() + "</option>"), i.trigger("liszt:updated");
        } 
    }).fail(function(result) {
        console.log(result);
    }).always(function() {
        loader.removeClass('fa fa-circle-o-notch fa-spin');
    });
});
jQuery(document).ready(function() {

    if (typeof(jQuery("#jformcluster").val()) != 'undefined')
    {
      jQuery('#jformcluster, #ticketcluster').trigger("change");
    }
});

jQuery(document).ready(function () {
    jQuery('select[name="ticket[customer_id]"]').on('change', function () {
        var selectedOption = jQuery(this).find('option:selected');
        var customerId = selectedOption.val();
        var customerText = selectedOption.text();

        var adminSelect = jQuery('select[name="jform[admins][]"]');
        var form = jQuery(this).closest('form');

        // 1. Remove previously added disabled options and hidden inputs
        adminSelect.find('option[disabled]').remove();
        form.find('input.hidden-customer-id').remove();

		// 2. Add new disabled option for UI (only if not already present)
		if (customerId) {
			var existingOption = adminSelect.find('option[value="' + customerId + '"]');

			if (existingOption.length > 0) {
				// If it already exists, mark it as selected and disabled
				existingOption.prop('selected', true).prop('disabled', true);
			} else {
				// Otherwise, add the new option
				adminSelect.append(
					'<option value="' + customerId + '" selected disabled>' + customerText + '</option>'
				);
			}

			// 3. Add hidden input with only the email
			if (form.find('input.hidden-customer-id[value="' + customerId + '"]').length === 0) {
				jQuery('<input>').attr({
					type: 'hidden',
					name: 'jform[admins][]',
					value: customerId,
					class: 'hidden-customer-id'
				}).appendTo(form);
			}
		}


        // 4. Update Chosen or any enhanced select
        adminSelect.trigger("chosen:updated");
    });
});

function toggleUserSelect() {
	var checkbox = document.getElementById('jform_is_allow');
	var field = document.getElementById('userSelectField');
	field.style.display = checkbox.checked ? 'block' : 'none';
}

