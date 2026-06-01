(function (document, $) {
    calculateIndividualRegistrationFee = function(changeTicketQuantity)
    {
        var $btnSubmit = $('#btn-submit'),
            $loadingAnimation = $('#ajax-loading-animation'),
            $totalAmount = $('#total_amount'),
            $amount = $('#amount');

        $btnSubmit.attr('disabled', 'disabled');
        $loadingAnimation.show();

        var euVatNumberField = Joomla.getOptions('euVatNumberField');

        var formFieldsSelector = 'select.ticket_type_quantity, #adminForm input[name="event_id"], #adminForm input[name="coupon_code"], #adminForm .payment-calculation input[type="text"], #adminForm .payment-calculation input[type="number"], #adminForm .payment-calculation input[type="checkbox"]:checked, #adminForm .payment-calculation input[type="radio"]:checked, #adminForm .payment-calculation select, #adminForm input.eb-hidden-field:hidden, #adminForm select[name="country"], #adminForm select[name="state"]';

        if (document.getElementById('tickets_members_information')) {
            formFieldsSelector = formFieldsSelector + ', #tickets_members_information input[type="text"], #tickets_members_information input[type="text"], #tickets_members_information input[type="url"], #tickets_members_information input[type="email"] ,#tickets_members_information input[type="hidden"], #tickets_members_information  input[type="number"], #tickets_members_information input[type="checkbox"]:checked, #tickets_members_information input[type="radio"]:checked, #tickets_members_information select, #tickets_members_information textarea';
        }

        if (euVatNumberField)
        {
            formFieldsSelector = formFieldsSelector + ', #adminForm input[name="' + euVatNumberField + '"]';
        }

        var ajaxUrl = Joomla.getOptions('calculateIndividualRegistrationFeeUrl');

        if (!ajaxUrl)
        {
            ajaxUrl = siteUrl + 'index.php?option=com_eventbooking&task=register.calculate_individual_registration_fee' + langLinkForAjax;
        }

        if ($('input:radio[name^=payment_method]').length)
        {
            formFieldsSelector = formFieldsSelector + ', input:radio[name^=payment_method]:checked';
        }
        else
        {
            formFieldsSelector = formFieldsSelector + ', input[name^=payment_method]';
        }

        $.ajax({
            type: 'POST',
            url: ajaxUrl,
            data: $(formFieldsSelector),
            dataType: 'json',
            success: function (msg, textStatus, xhr) {
                $btnSubmit.removeAttr('disabled');
                $loadingAnimation.hide();
                $totalAmount.val(msg.total_amount);
                $('#discount_amount').val(msg.discount_amount);
                $('#tax_amount').val(msg.tax_amount);
                $('#payment_processing_fee').val(msg.payment_processing_fee);
                $amount.val(msg.amount);
                $('#deposit_amount').val(msg.deposit_amount);

                if (($amount.length || $totalAmount.length) && msg.payment_amount == 0)
                {
                    $('.payment_information').css('display', 'none');
                }
                else
                {
                    $('.payment_information').css('display', '');
                    updatePaymentMethod();
                }

                if (msg.coupon_valid == 1)
                {
                    $('#coupon_validate_msg').hide();
                }
                else
                {
                    $btnSubmit.attr('disabled', 'disabled');
                    $('#coupon_validate_msg').show();
                }

                if ($('#payment_type').val() == 1)
                {
                    $('#deposit_amount_container').show();
                }
                else
                {
                    $('#deposit_amount_container').hide();
                }

                if (typeof changeTicketQuantity !== 'undefined')
                {
                    // the variable is defined
                    $('#tickets_members_information').html(msg.tickets_members);
                }

                if (euVatNumberField)
                {
                    if (msg.show_vat_number_field == 1)
                    {
                        $('#field_' + euVatNumberField).show();
                    }
                    else
                    {
                        $('#field_' + euVatNumberField).hide();
                    }

                    if (msg.vat_number_valid == 1)
                    {
                        $('#vatnumber_validate_msg').hide();
                    }
                    else
                    {
                        $('#vatnumber_validate_msg').show();
                    }
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                alert(textStatus);
            }
        });
    };

    calculateGroupRegistrationFee = function()
    {
        var $btnSubmit = $('#btn-process-group-billing'),
            $loadingAnimation = $('#ajax-loading-animation'),
            $totalAmount = $('#total_amount'),
            $amount = $('#amount');

        $btnSubmit.attr('disabled', 'disabled');
        $loadingAnimation.show();

        var euVatNumberField = Joomla.getOptions('euVatNumberField');

        var formFieldsSelector = '#adminForm input[name="event_id"], #adminForm input[name="coupon_code"], #adminForm .payment-calculation input[type="text"], #adminForm .payment-calculation input[type="number"], #adminForm .payment-calculation input[type="checkbox"]:checked, #adminForm .payment-calculation input[type="radio"]:checked, #adminForm .payment-calculation select, #adminForm input.eb-hidden-field:hidden, #adminForm select[name="country"], #adminForm select[name="state"]';

        if (euVatNumberField)
        {
            formFieldsSelector = formFieldsSelector + ', #adminForm input[name="' + euVatNumberField + '"]';
        }

        if ($('input:radio[name^=payment_method]').length)
        {
            formFieldsSelector = formFieldsSelector + ', input:radio[name^=payment_method]:checked';
        }
        else
        {
            formFieldsSelector = formFieldsSelector + ', input[name^=payment_method]';
        }

        var ajaxUrl = Joomla.getOptions('calculateGroupRegistrationFeeUrl');

        if (!ajaxUrl)
        {
            ajaxUrl = siteUrl + 'index.php?option=com_eventbooking&task=register.calculate_group_registration_fee'  + langLinkForAjax;
        }

        $.ajax({
            type: 'POST',
            url: ajaxUrl,
            data: $(formFieldsSelector),
            dataType: 'json',
            success: function (msg, textStatus, xhr) {
                $btnSubmit.removeAttr('disabled');
                $loadingAnimation.hide();
                $totalAmount.val(msg.total_amount);
                $('#discount_amount').val(msg.discount_amount);
                $('#tax_amount').val(msg.tax_amount);
                $('#payment_processing_fee').val(msg.payment_processing_fee);
                $amount.val(msg.amount);
                $('#deposit_amount').val(msg.deposit_amount);

                if (($amount.length || $totalAmount.length) && msg.payment_amount == 0)
                {
                    $('.payment_information').css('display', 'none');
                }
                else
                {
                    $('.payment_information').css('display', '');
                    updatePaymentMethod();
                }

                if (msg.coupon_valid == 1)
                {
                    $('#coupon_validate_msg').hide();
                }
                else
                {
                    $btnSubmit.attr('disabled', 'disabled');
                    $('#coupon_validate_msg').show();
                }

                if ($('#payment_type').val() == 1)
                {
                    $('#deposit_amount_container').show();
                }
                else
                {
                    $('#deposit_amount_container').hide();
                }

                if (euVatNumberField)
                {
                    if (msg.show_vat_number_field == 1)
                    {
                        $('#field_' + euVatNumberField).show();
                    }
                    else
                    {
                        $('#field_' + euVatNumberField).hide();
                    }

                    if (msg.vat_number_valid == 1)
                    {
                        $('#vatnumber_validate_msg').hide();
                    }
                    else
                    {
                        $('#vatnumber_validate_msg').show();
                    }
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                alert(textStatus);
            }
        });
    };

    calculateCartRegistrationFee = function()
    {
        var $btnSubmit = $('#btn-submit'),
            $loadingAnimation = $('#ajax-loading-animation'),
            $totalAmount = $('#total_amount'),
            $amount = $('#amount');

        $btnSubmit.attr('disabled', 'disabled');
        $loadingAnimation.show();

        var euVatNumberField = Joomla.getOptions('euVatNumberField');

        var formFieldsSelector = 'select.ticket_type_quantity, #adminForm input[name="coupon_code"], #adminForm .payment-calculation input[type="text"], #adminForm .payment-calculation input[type="number"], #adminForm .payment-calculation input[type="checkbox"]:checked, #adminForm .payment-calculation input[type="radio"]:checked, #adminForm .payment-calculation select, #adminForm input.eb-hidden-field:hidden, #adminForm select[name="country"], #adminForm select[name="state"]';

        if (euVatNumberField)
        {
            formFieldsSelector = formFieldsSelector + ', #adminForm input[name="' + euVatNumberField + '"]';
        }

        if ($('input:radio[name^=payment_method]').length)
        {
            formFieldsSelector = formFieldsSelector + ', input:radio[name^=payment_method]:checked';
        }
        else
        {
            formFieldsSelector = formFieldsSelector + ', input[name^=payment_method]';
        }

        var ajaxUrl = Joomla.getOptions('calculateCartRegistrationFeeUrl');

        if (!ajaxUrl)
        {
            ajaxUrl = siteUrl + 'index.php?option=com_eventbooking&task=cart.calculate_cart_registration_fee' + langLinkForAjax;
        }

        $.ajax({
            type: 'POST',
            url: ajaxUrl,
            data: $(formFieldsSelector),
            dataType: 'json',
            success: function (msg, textStatus, xhr) {
                $btnSubmit.removeAttr('disabled');
                $loadingAnimation.hide();
                $totalAmount.val(msg.total_amount);
                $('#discount_amount').val(msg.discount_amount);
                $('#tax_amount').val(msg.tax_amount);
                $('#payment_processing_fee').val(msg.payment_processing_fee);
                $amount.val(msg.amount);
                $('#deposit_amount').val(msg.deposit_amount);

                if (($amount.length || $totalAmount.length) && msg.payment_amount == 0)
                {
                    $('.payment_information').css('display', 'none');
                }
                else
                {
                    $('.payment_information').css('display', '');
                    updatePaymentMethod();
                }

                if (msg.coupon_valid == 1)
                {
                    $('#coupon_validate_msg').hide();
                }
                else
                {
                    $btnSubmit.attr('disabled', 'disabled');
                    $('#coupon_validate_msg').show();
                }

                if (euVatNumberField)
                {
                    if (msg.show_vat_number_field == 1)
                    {
                        $('#field_' + euVatNumberField).show();
                    }
                    else
                    {
                        $('#field_' + euVatNumberField).hide();
                    }

                    if (msg.vat_number_valid == 1)
                    {
                        $('#vatnumber_validate_msg').hide();
                    }
                    else
                    {
                        $('#vatnumber_validate_msg').show();
                    }
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                alert(textStatus);
            }
        });
    };

    buildStateField = function(stateFieldId, countryFieldId, defaultState)
    {
        var $country = $('#' + countryFieldId),
            $state = $('#' + stateFieldId);

        if ($state.length && $state.is('select'))
        {
            var countryName = '';

            //set state
            if ($country.length)
            {
                countryName = $country.val();
            }

            $.ajax({
                type: 'GET',
                url: siteUrl + 'index.php?option=com_eventbooking&task=get_states&country_name=' + countryName + '&field_name=' + stateFieldId + '&state_name=' + defaultState + langLinkForAjax,
                success: function (data) {
                    if ($('#field_' + stateFieldId + ' .controls').length) {
                        $('#field_' + stateFieldId + ' .controls').html(data);
                    }
                    else if ($('#field_' + stateFieldId + ' .col-sm-9').length) {
                        $('#field_' + stateFieldId + ' .col-sm-9').html(data);
                    }
                    else if ($('#field_' + stateFieldId + ' .col-md9').length) {
                        $('#field_' + stateFieldId + ' .col-md-9').html(data);
                    }
                    else {
                        $('#field_' + stateFieldId + ' .uk-form-controls').html(data);
                    }

                },
                error: function (jqXHR, textStatus, errorThrown) {
                    alert(textStatus);
                }
            });

            //Bind onchange event to the country
            if ($country.length)
            {
                $country.change(function ()
                {
                    $.ajax({
                        type: 'GET',
                        url: siteUrl + 'index.php?option=com_eventbooking&task=get_states&country_name=' + $(this).val() + '&field_name=' + stateFieldId + '&state_name=' + defaultState + langLinkForAjax,
                        success: function (data) {
                            if ($('#field_' + stateFieldId + ' .controls').length) {
                                $('#field_' + stateFieldId + ' .controls').html(data);
                            }
                            else if ($('#field_' + stateFieldId + ' .col-sm-9').length) {
                                $('#field_' + stateFieldId + ' .col-sm-9').html(data);
                            }
                            else if ($('#field_' + stateFieldId + ' .col-md-9').length) {
                                $('#field_' + stateFieldId + ' .col-md-9').html(data);
                            }
                            else
                            {
                                $('#field_' + stateFieldId + ' .uk-form-controls').html(data);
                            }
                        },
                        error: function (jqXHR, textStatus, errorThrown) {
                            alert(textStatus);
                        }
                    });

                });
            }
        }
    };
})(document, Eb.jQuery);