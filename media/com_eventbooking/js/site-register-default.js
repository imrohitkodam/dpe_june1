(function (document, $) {
    var card = null;
    $(document).ready(function () {
        var emailElement = $('#email');
        $("#eb-login-form").validationEngine();

        if (emailElement.val())
        {
            emailElement.validationEngine('validate');
        }

        if (Joomla.getOptions('hidePaymentInformation'))
        {
            $('.payment_information').css('display', 'none');
        }

        createStripeCardElement(card);

        if (Joomla.getOptions('squareAppId'))
        {
            createSquareCardElement();
        }

        buildStateFields('state', 'country', Joomla.getOptions('selectedState'));

        if (Joomla.getOptions('isCountryBaseTax'))
        {
            $("#country").change(calculateIndividualRegistrationFee);
        }

        if (Joomla.getOptions('isEUTaxRuleEnabled'))
        {
            var euVatNumberField = Joomla.getOptions('euVatNumberField');
            var euVatNumberFieldInput = $('#' + euVatNumberField);
            var showVatNumberField = Joomla.getOptions('showVatNumberField');

            euVatNumberFieldInput.after('<span class="invalid" id="vatnumber_validate_msg" style="display: none;">' + Joomla.JText._('EB_INVALID_VATNUMBER') + '</span></div>');

            euVatNumberFieldInput.change(calculateIndividualRegistrationFee);

            if (showVatNumberField)
            {
                $('#field_' + euVatNumberField).show();
            }
            else
            {
                $('#field_' + euVatNumberField).hide();
            }
        }

        $("#adminForm").validationEngine('attach', {
            onValidationComplete: individualRegistrationValidationComplete
        });

        EBMaskInputs(document.getElementById('adminForm'));

        initializeTermsAndConditionsModal();

        // Dispatch custom event
        document.dispatchEvent(new CustomEvent("onEBAfterRegistrationFormLoaded", {
            detail: { registrationType: 'individualRegistration'}
        }));
    });

    individualRegistrationValidationComplete = function (form, status)
    {
        if (status === true) {
            form.on('submit', function (e) {
                e.preventDefault();
            });

            // Check and make sure at least one ticket type quantity is selected
            if (Joomla.getOptions('hasTicketTypes')) {
                var ticketTypesValue = '';
                var ticketName = '';
                var ticketQuantity = 0;

                $('select.ticket_type_quantity').each(function () {
                    ticketName = $(this).attr('name');
                    ticketQuantity = $(this).val();

                    if (ticketQuantity > 0) {
                        ticketTypesValue = ticketTypesValue + ticketName + ':' + ticketQuantity + ',';
                    }
                });

                if (ticketTypesValue.length > 0) {
                    ticketTypesValue = ticketTypesValue.substring(0, ticketTypesValue.length - 1);
                }

                $('#ticket_type_values').val(ticketTypesValue);
            }

            form.find('#btn-submit').prop('disabled', true);

            return paymentMethodCallbackHandle();
        }

        return false;
    };

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
                $amount.val(msg.amount);
                $totalAmount.val(msg.total_amount);
                $('#discount_amount').val(msg.discount_amount);
                $('#tax_amount').val(msg.tax_amount);
                $('#payment_processing_fee').val(msg.payment_processing_fee);
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
                    if (msg.show_vat_number_field === 1)
                    {
                        $('#field_' + euVatNumberField).show();
                    }
                    else
                    {
                        $('#field_' + euVatNumberField).hide();
                    }

                    if (msg.vat_number_valid === 1)
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
})(document, Eb.jQuery);