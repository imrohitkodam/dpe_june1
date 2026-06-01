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

    var loader = jQuery('#loader');
    loader.html('');
    loader.addClass('fa fa-circle-o-notch fa-spin');

    if (!ticketId) {
        ticketId = '';
    }

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

            clusterusers.trigger("liszt:updated");

            var customerOptions = customerClusterUsers.prop('options');

            jQuery.each(userdata[0], function(val, text) {
                if (text.name) {
                    customerOptions[customerOptions.length] = new Option(text.name + ' (' + text.email + ')', text.user_id);
                }
            });

            if (userdata[2] != null && userdata[2].trim() != "") {
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

            customerClusterUsers.trigger("liszt:updated");


        } else if (!data) {
            loader.html(Joomla.JText._("COM_DPE_NO_USERS"));
        }
    }).fail(function(result) {
        console.log(result);
    }).always(function() {
        loader.removeClass('fa fa-circle-o-notch fa-spin');
    });
});

jQuery(document).ready(function() {
    if (jQuery('#jformcluster, #ticketcluster').val())
    {
      jQuery('#jformcluster, #ticketcluster').trigger("change");
    }
});
