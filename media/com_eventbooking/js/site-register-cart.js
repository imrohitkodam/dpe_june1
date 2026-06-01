(function (document, $) {
    $(document).ready(function () {
        $("#adminForm").validationEngine('attach', {
            onValidationComplete: function (form, status) {
                if (status === true) {
                    form.on('submit', function (e) {
                        e.preventDefault();
                    });

                    form.find('#btn-submit').prop('disabled', true);

                    return paymentMethodCallbackHandle();
                }

                return false;
            }
        });

        buildStateFields('state', 'country', Joomla.getOptions('selectedState'));

        var numberMembers = Joomla.getOptions('numberMembers');

        for (var i = 1; i <= numberMembers; i++) {
            buildStateFields('state_' + i, 'country_' + i, '');
        }

        createStripeCardElement();

        if (Joomla.getOptions('squareAppId')) {
            createSquareCardElement();
        }

        var emailElement = $('#email');

        if (emailElement.val()) {
            emailElement.validationEngine('validate');
        }

        if (Joomla.getOptions('isCountryBaseTax')) {
            $("#country").change(calculateCartRegistrationFee);
        }

        if (Joomla.getOptions('isEUTaxRuleEnabled')) {
            var euVatNumberField = Joomla.getOptions('euVatNumberField');
            var euVatNumberFieldInput = $('#' + euVatNumberField);
            euVatNumberFieldInput.after('<span class="invalid" id="vatnumber_validate_msg" style="display: none;">' + Joomla.JText._('EB_INVALID_VATNUMBER') + '</span></div>');

            euVatNumberFieldInput.change(calculateCartRegistrationFee);

            var showVatNumberField = Joomla.getOptions('showVatNumberField');

            if (showVatNumberField) {
                $('#field_' + euVatNumberField).show();
            } else {
                $('#field_' + euVatNumberField).hide();
            }
        }

        if (Joomla.getOptions('hidePaymentInformation')) {
            $('.payment_information').css('display', 'none');
        }

        EBMaskInputs(document.getElementById('adminForm'));

        initializeTermsAndConditionsModal();

        // Dispatch custom event
        document.dispatchEvent(new CustomEvent("onEBAfterRegistrationFormLoaded", {
            detail: { registrationType: 'cartRegistration'}
        }));
    });

    updateCart = function () {
        location.href = Joomla.getOptions('cartUrl');
    };

    calculateCartRegistrationFee = function () {
        var $btnSubmit = $('#btn-submit'),
            $loadingAnimation = $('#ajax-loading-animation'),
            $totalAmount = $('#total_amount'),
            $amount = $('#amount');

        $btnSubmit.attr('disabled', 'disabled');
        $loadingAnimation.show();

        var euVatNumberField = Joomla.getOptions('euVatNumberField');

        var formFieldsSelector = 'select.ticket_type_quantity, #adminForm input[name="coupon_code"], #adminForm .payment-calculation input[type="text"], #adminForm .payment-calculation input[type="number"], #adminForm .payment-calculation input[type="checkbox"]:checked, #adminForm .payment-calculation input[type="radio"]:checked, #adminForm .payment-calculation select, #adminForm input.eb-hidden-field:hidden, #adminForm select[name="country"], #adminForm select[name="state"]';

        if (euVatNumberField) {
            formFieldsSelector = formFieldsSelector + ', #adminForm input[name="' + euVatNumberField + '"]';
        }

        if ($('input:radio[name^=payment_method]').length) {
            formFieldsSelector = formFieldsSelector + ', input:radio[name^=payment_method]:checked';
        } else {
            formFieldsSelector = formFieldsSelector + ', input[name^=payment_method]';
        }

        var ajaxUrl = Joomla.getOptions('calculateCartRegistrationFeeUrl');

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

                if (($amount.length || $totalAmount.length) && msg.payment_amount == 0) {
                    $('.payment_information').css('display', 'none');
                } else {
                    $('.payment_information').css('display', '');
                    updatePaymentMethod();
                }

                if (msg.coupon_valid == 1) {
                    $('#coupon_validate_msg').hide();
                } else {
                    $btnSubmit.attr('disabled', 'disabled');
                    $('#coupon_validate_msg').show();
                }

                if (euVatNumberField) {
                    if (msg.show_vat_number_field == 1) {
                        $('#field_' + euVatNumberField).show();
                    } else {
                        $('#field_' + euVatNumberField).hide();
                    }

                    if (msg.vat_number_valid == 1) {
                        $('#vatnumber_validate_msg').hide();
                    } else {
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