(function (document, Joomla, $) {
    $(document).ready(function () {
        var step = window.location.hash.substr(1), ajaxUrl = '';

        if (!step) {
            step = Joomla.getOptions('defaultStep');
        }

        if (step === 'group_billing') {
            ajaxUrl = Joomla.getOptions('groupBillingUrl');

            $.ajax({
                url: ajaxUrl,
                dataType: 'html',
                success: function (html) {
                    var $billingFormContainer = $('#eb-group-billing .eb-form-content'), emailElement = $('#email');
                    $billingFormContainer.html(html);
                    $billingFormContainer.slideDown('slow');
                    groupBillingFormLoaded();
                    if (emailElement.val()) {
                        emailElement.validationEngine('validate');
                    }
                },
                error: function (xhr, ajaxOptions, thrownError) {
                    alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
                }
            });
        } else if (step === 'group_members') {
            ajaxUrl = Joomla.getOptions('groupMembersUrl');

            $.ajax({
                url: ajaxUrl,
                dataType: 'html',
                success: function (html) {
                    var $groupMembersFormContainer = $('#eb-group-members-information .eb-form-content');
                    $groupMembersFormContainer.html(html);
                    $groupMembersFormContainer.slideDown('slow');
                    groupMembersFormLoaded();
                },
                error: function (xhr, ajaxOptions, thrownError) {
                    alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
                }
            });
        } else {

            ajaxUrl = Joomla.getOptions('numberMembersUrl');

            $.ajax({
                url: ajaxUrl,
                dataType: 'html',
                success: function (html) {
                    var $numberMembersFormContainer = $('#eb-number-group-members .eb-form-content');
                    $numberMembersFormContainer.html(html);
                    $numberMembersFormContainer.slideDown('slow');
                    numberMembersFormLoaded();
                },
                error: function (xhr, ajaxOptions, thrownError) {
                    alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
                }
            });
        }
    });

    numberMembersFormLoaded = function()
    {
        var $formNumberGroupMembers = $("#eb-form-number-group-members"),
            $btnProcessNumberMembers = $('#btn-process-number-members');

        $formNumberGroupMembers.validationEngine();

        $btnProcessNumberMembers.click(function(){
            var formValid = $formNumberGroupMembers.validationEngine('validate');
            var ajaxUrl = Joomla.getOptions('storeNumberMembersUrl');

            if (formValid)
            {
                $.ajax({
                    url: ajaxUrl,
                    dataType: 'html',
                    method: 'post',
                    data: {number_registrants: $('input[name="number_registrants"]').val()},
                    beforeSend: function() {
                        $btnProcessNumberMembers.attr('disabled', true);
                        $btnProcessNumberMembers.after('<span class="wait">&nbsp;<img src="' + Joomla.getOptions('ajaxLoadingImageUrl') + '" alt="" /></span>');
                    },
                    complete: function() {
                        $btnProcessNumberMembers.attr('disabled', false);
                        $('.wait').remove();
                    },
                    success: function(html) {
                        var $numberMembersFormContainer = $('#eb-number-group-members .eb-form-content');
                        $numberMembersFormContainer.slideUp('slow');

                        if (Joomla.getOptions('collectMemberInformation'))
                        {
                            var $groupMembersFormContainer = $('#eb-group-members-information .eb-form-content');
                            $groupMembersFormContainer.html(html);
                            $groupMembersFormContainer.slideDown('slow');

                            groupMembersFormLoaded();
                        }
                        else
                        {
                            var $groupBillingFormContainer = $('#eb-group-billing .eb-form-content');
                            $groupBillingFormContainer.html(html);
                            $groupBillingFormContainer.slideDown('slow');

                            groupBillingFormLoaded();
                        }
                    },
                    error: function(xhr, ajaxOptions, thrownError) {
                        alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
                    }
                });
            }
        });
    };

    populateMemberFormData = (function (currentMemberNumber, fromMemberNumber) {
        if (fromMemberNumber != 0)
        {
            var memberFields = Joomla.getOptions('memberFields', []);
            var arrayLength = memberFields.length, selecteds = [], value = '';

            for (var i = 0; i < arrayLength; i++)
            {
                if ($('input[name="' + memberFields[i] + '_' + currentMemberNumber + '[]"]').length)
                {
                    //This is a checkbox or multiple select
                    selecteds = $('input[name="' + memberFields[i] + '_' + fromMemberNumber + '[]"]:checked').map(function(){return $(this).val();}).get();
                    $('input[name="' + memberFields[i] + '_' + currentMemberNumber + '[]"]').val(selecteds);
                }
                else if ($('input[type="radio"][name="' + memberFields[i] + '_' + currentMemberNumber + '"]').length)
                {
                    value = $('input[name="' + memberFields[i] + '_' + fromMemberNumber + '"]:checked').val();
                    $('input[name="' + memberFields[i] + '_' + currentMemberNumber + '"][value="' + value + '"]').attr('checked', 'checked');
                }
                else
                {
                    value = $('#' + memberFields[i] + '_' + fromMemberNumber).val();
                    $('#' + memberFields[i] + '_' + currentMemberNumber).val(value);
                }
            }
        }
    });

    groupMembersFormLoaded = function() {
        var $formGroupMembers = $("#eb-form-group-members"),
            $btnGroupMembersBack = $('#btn-group-members-back');

        $formGroupMembers.validationEngine();

        initCalendarFormFields("#eb-form-group-members .field-calendar");

        var numberRegistrants = $('#eb-form-group-members input[name="number_registrants"]').val();

        for (var i = 0; i < numberRegistrants; i++)
        {
            buildStateFields('state_' + i, 'country_' + i, '');
        }

        if (!Joomla.getOptions('showBillingStep'))
        {
            initRecaptcha();
            initializeTermsAndConditionsModal();
        }
        else
        {
            var $btnProcessGroupMembers = $('#btn-process-group-members');
            $btnProcessGroupMembers.click(btnProcessGroupMembersClickHandle);
        }

        $btnGroupMembersBack.click(btnBackGroupMembersClickHandle);

        if (Eb.jQuery().tooltip){
            $formGroupMembers.find(".hasTooltip").tooltip({"html": true,"container": "body"});
        } else if(bootstrap.Tooltip){
            var groupMembersFormEl = document.getElementById('eb-form-group-members');
            var tooltipTriggerList = [].slice.call(groupMembersFormEl.querySelectorAll('.hasTooltip'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl, {"html": true,"container": "body"});
            });
        }

        EBMaskInputs(document.getElementById('eb-form-group-members'));
    };

    btnProcessGroupMembersClickHandle = function(){
        var $btnProcessGroupMembers = $('#btn-process-group-members');
        var $formGroupMembers = $("#eb-form-group-members");
        var formValid = $formGroupMembers.validationEngine('validate');

        if (formValid)
        {
            var ajaxUrl = Joomla.getOptions('storeGroupMembersDataUrl');

            $.ajax({
                url: ajaxUrl,
                method: 'post',
                data: $formGroupMembers.serialize(),
                dataType: 'json',
                beforeSend: function() {
                    $btnProcessGroupMembers.attr('disabled', true);
                    $btnProcessGroupMembers.after('<span class="wait">&nbsp;<img src="' + Joomla.getOptions('ajaxLoadingImageUrl') + '" alt="" /></span>');
                    $('.eb-field-validation-error').remove();
                },
                complete: function() {
                    $btnProcessGroupMembers.attr('disabled', false);
                    $('.wait').remove();
                },
                success: function(json) {
                    if (json.status == 'OK')
                    {
                        var $groupBillingFormContainer = $('#eb-group-billing .eb-form-content');

                        $('ul.eb-validation_errors').remove();
                        $('#eb-group-members-information .eb-form-content').slideUp('slow');

                        $groupBillingFormContainer.html(json.html);
                        $groupBillingFormContainer.slideDown('slow');

                        groupBillingFormLoaded();
                    }
                    else
                    {
                        for (var field in json.errors)
                        {
                            value = json.errors[field];
                            $('<div class="eb-field-validation-error required"> ' + value + '</div>').insertAfter('#' + field);
                        }

                        $('#eb-group-members-information .eb-form-content').prepend(json.html)
                    }
                },
                error: function(xhr, ajaxOptions, thrownError) {
                    alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
                }
            });
        }
    };

    btnBackGroupMembersClickHandle = function()
    {
        var $btnGroupMembersBack = $('#btn-group-members-back');
        var ajaxUrl = Joomla.getOptions('numberMembersUrl');

        $.ajax({
            url: ajaxUrl,
            method: 'post',
            dataType: 'html',
            beforeSend: function() {
                $btnGroupMembersBack.attr('disabled', true);
            },
            complete: function() {
                $btnGroupMembersBack.attr('disabled', false);
            },
            success: function(html) {
                $('#eb-group-members-information .eb-form-content').slideUp('slow');
                var $numberGroupMembersFormContainer = $('#eb-number-group-members .eb-form-content');
                $numberGroupMembersFormContainer.html(html);
                $numberGroupMembersFormContainer.slideDown('slow');

                numberMembersFormLoaded();
            },
            error: function(xhr, ajaxOptions, thrownError) {
                alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
            }
        });
    };

    groupBillingFormLoaded = function() {
        $("#eb-login-form").validationEngine();
        initCalendarFormFields("#adminForm .field-calendar");
        buildStateFields('state', 'country', $('#group_billing_selected_state').val());

        $("#adminForm").validationEngine('attach', {
            onValidationComplete: groupRegistrationValidationComplete
        });

        var $email = $('#email'), amount = $('#group_billing_payment_amount').val();

        if ($email.val())
        {
            $email.validationEngine('validate');
        }

        if (Joomla.getOptions('squareUpEnabled') && !Joomla.getOptions('waitingList'))
        {
            sqPaymentForm.build();
        }

        if (parseFloat(amount) === 0)
        {
            $('.payment_information').css('display', 'none');
        }

        createStripeCardElement();

        if (Joomla.getOptions('squareAppId'))
        {
            createSquareCardElement();
        }

        EBMaskInputs(document.getElementById('adminForm'));

        initRecaptcha();

        var $btnGroupBillingBack = $('#btn-group-billing-back');
        $btnGroupBillingBack.click(btnGroupBillingBackClickHandle);

        //Terms and Conditions Modal
        initializeTermsAndConditionsModal();

        if (Joomla.getOptions('collectMemberInformation'))
        {
            $('html, body').animate({scrollTop:$('#eb-group-members-information').position().top}, 'slow');
        }

        if (Joomla.getOptions('isCountryBaseTax'))
        {
            $("#country").on('change', calculateGroupRegistrationFee);
        }

        if (Joomla.getOptions('isEUTaxRuleEnabled'))
        {
            var euVatNumberField = Joomla.getOptions('euVatNumberField');
            var euVatNumberFieldInput = $('#' + euVatNumberField);
            euVatNumberFieldInput.after('<span class="invalid" id="vatnumber_validate_msg" style="display: none;">' + Joomla.JText._('EB_INVALID_VATNUMBER') + '</span></div>');

            euVatNumberFieldInput.change(calculateGroupRegistrationFee);

            var showVatNumberField = $('#group_billing_show_vat_number_field').val();

            if (showVatNumberField === '1')
            {
                $('#field_' + euVatNumberField).show();
            }
            else
            {
                $('#field_' + euVatNumberField).hide();
            }
        }

        $('#return_url').val(Joomla.getOptions('returnUrl'));

        if (Eb.jQuery().tooltip){
            $('#adminForm').find(".hasTooltip").tooltip({"html": true,"container": "body"});
        } else if(bootstrap.Tooltip){
            var adminForm = document.getElementById('adminForm');
            var tooltipTriggerList = [].slice.call(adminForm.querySelectorAll('.hasTooltip'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl, {"html": true,"container": "body"});
            });
        }

        // Dispatch custom event
        document.dispatchEvent(new CustomEvent("onEBAfterRegistrationFormLoaded", {
            detail: { registrationType: 'groupRegistration'}
        }));
    };

    btnGroupBillingBackClickHandle = function(){
        var ajaxUrl = Joomla.getOptions('storeGroupBillingDataUrl');
        var $btnGroupBillingBack = $('#btn-group-billing-back');

        $.ajax({
            url: ajaxUrl,
            method: 'post',
            data: $('#adminForm').serialize(),
            dataType: 'html',
            beforeSend: function() {
                $btnGroupBillingBack.attr('disabled', true);
            },
            complete: function() {
                $btnGroupBillingBack.attr('disabled', false);
            },
            success: function(html) {
                var $groupMembersForm = $('#eb-group-members-information .eb-form-content');
                $groupMembersForm.html(html);

                $('#eb-group-billing .eb-form-content').slideUp('slow');

                if (Joomla.getOptions('collectMemberInformation'))
                {
                    $groupMembersForm.slideDown('slow');
                    groupMembersFormLoaded();
                }
                else
                {
                    $('#eb-number-group-members .eb-form-content').slideDown('slow');
                    numberMembersFormLoaded();
                }
            },
            error: function(xhr, ajaxOptions, thrownError) {
                alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
            }
        });
    };

    groupRegistrationValidationComplete = function (form, status)
    {
        if (status === true) {
            form.on('submit', function(e) {
                e.preventDefault();
            });

            form.find('#btn-process-group-billing').prop('disabled', true);

            return paymentMethodCallbackHandle();
        }

        return false;
    };

    initRecaptcha = function()
    {
        var showCaptcha = Joomla.getOptions('showCaptcha');
        var captchaPlugin = Joomla.getOptions('captchaPlugin');

        if (showCaptcha && captchaPlugin === 'recaptcha')
        {
            EBInitReCaptcha2();
        }
        else if (showCaptcha && captchaPlugin === 'recaptcha_invisible')
        {
            EBInitReCaptchaInvisible();
        }
    };

    initCalendarFormFields = function(selector){
        var calendarElements = document.querySelectorAll(selector);

        for (i = 0; i < calendarElements.length; i++) {
            JoomlaCalendar.init(calendarElements[i]);
        }
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
})(document, Joomla ,Eb.jQuery);