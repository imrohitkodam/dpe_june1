/****
 * Payment method class
 * @param name
 * @param creditCard
 * @param cardType
 * @param cardCvv
 * @param cardHolderName
 * @return
 */
function PaymentMethod(name, creditCard, cardType, cardCvv, cardHolderName) {
    this.name = name;
    this.creditCard = creditCard;
    this.cardType = cardType;
    this.cardCvv = cardCvv;
    this.cardHolderName = cardHolderName;
}

/***
 * Get name of the payment method
 * @return string
 */
PaymentMethod.prototype.getName = function () {
    return this.name;
};
/***
 * This is creditcard payment method or not
 * @return int
 */
PaymentMethod.prototype.getCreditCard = function () {
    return this.creditCard;
};
/****
 * Show creditcard type or not
 * @return string
 */
PaymentMethod.prototype.getCardType = function () {
    return this.cardType;
};
/***
 * Check to see whether card cvv code is required
 * @return string
 */
PaymentMethod.prototype.getCardCvv = function () {
    return this.cardCvv;
};
/***
 * Check to see whether this payment method require entering card holder name
 * @return
 */
PaymentMethod.prototype.getCardHolderName = function () {
    return this.cardHolderName;
};

/***
 * Payment method class, hold all the payment methods
 */
function PaymentMethods() {
    this.length = 0;
    this.methods = [];
}

/***
 * Add a payment method to array
 * @param paymentMethod
 * @return
 */
PaymentMethods.prototype.Add = function (paymentMethod) {
    this.methods[this.length] = paymentMethod;
    this.length = this.length + 1;
};
/***
 * Find a payment method based on it's name
 * @param name
 * @return {@link PaymentMethod}
 */
PaymentMethods.prototype.Find = function (name) {
    for (var i = 0; i < this.length; i++) {
        if (this.methods[i].name === name) {
            return this.methods[i];
        }
    }

    return null;
};

function removeSpace(obj) {
    obj.value = obj.value.replace(/\s/g, '');
}

EBMaskInputs = function (form) {
    form.querySelectorAll('input[data-input-mask]').forEach(function (input) {
        var mask = input.dataset.inputMask;

        // Assume this is a regular expression
        if (mask.slice(0, 1) === '/' && mask.slice(-1) === '/') {
            mask = mask.slice(1); // Remove first character
            mask = mask.slice(0, -1); // Remove last character
            mask = new RegExp(mask);
        }

        var regExpMask = IMask(
            input,
            {
                mask: mask
            });
    });
};

(function ($) {
    updatePaymentMethod = function () {
        var paymentMethod, method;

        if ($('input:radio[name^=payment_method]').length) {
            paymentMethod = $('input:radio[name^=payment_method]:checked').val();
        } else {
            paymentMethod = $('input[name^=payment_method]').val();
        }

        method = methods.Find(paymentMethod);

        if (!method) {
            return;
        }

        if (method.getCreditCard()) {
            $('#tr_card_number').show();
            $('#tr_exp_date').show();
            $('#tr_cvv_code').show();

            if (method.getCardHolderName()) {
                $('#tr_card_holder_name').show();
            } else {
                $('#tr_card_holder_name').hide();
            }
        } else {
            $('#tr_card_number').hide();
            $('#tr_exp_date').hide();
            $('#tr_cvv_code').hide();
            $('#tr_card_holder_name').hide();
        }

        if (typeof stripe !== 'undefined') {
            if (paymentMethod.indexOf('os_stripe') === 0) {
                $('#stripe-card-form').show();
            } else {
                $('#stripe-card-form').hide();
            }
        }

        if (paymentMethod.indexOf('os_squarecard') === 0) {
            $('#square-card-form').show();
        } else {
            $('#square-card-form').hide();
        }

        ebShowPaymentMethodFields(paymentMethod);

        // Dispatch event
        document.dispatchEvent(new CustomEvent("onEBAfterPaymentMethodChange", {
            detail: { paymentMethod: paymentMethod }
        }));
    };

    changePaymentMethod = function (registrationType) {
        updatePaymentMethod();

        if (document.adminForm.show_payment_fee.value == 1) {
            // Re-calculate subscription fee in case there is payment fee associated with payment method
            if (registrationType === 'individual') {
                calculateIndividualRegistrationFee();
            } else if (registrationType === 'group') {
                calculateGroupRegistrationFee();
            } else {
                calculateCartRegistrationFee();
            }
        }
    };

    showHideDependFields = function (fieldId, fieldName, fieldType, fieldSuffix) {
        var masterFieldsSelector,
            $loadingAnimation = $('#ajax-loading-animation');

        $loadingAnimation.show();

        if (fieldSuffix) {
            masterFieldsSelector = '.master-field-' + fieldSuffix + ' input[type=\'checkbox\']:checked,' + ' .master-field-' + fieldSuffix + ' input[type=\'radio\']:checked,' + ' .master-field-' + fieldSuffix + ' select';
        } else {
            masterFieldsSelector = '.master-field input[type=\'checkbox\']:checked, .master-field input[type=\'radio\']:checked, .master-field select';
        }

        $.ajax({
            type: 'POST',
            url: siteUrl + 'index.php?option=com_eventbooking&task=get_depend_fields_status&field_id=' + fieldId + '&field_suffix=' + fieldSuffix + langLinkForAjax,
            data: $(masterFieldsSelector),
            dataType: 'json',
            success: function (msg, textStatus, xhr) {
                $loadingAnimation.hide();
                var hideFields = [], showFields = [], i;

                if (msg.hide_fields.length > 0) {
                    hideFields = msg.hide_fields.split(',');
                }

                if (msg.show_fields.length > 0) {
                    showFields = msg.show_fields.split(',');
                }

                for (i = 0; i < hideFields.length; i++) {
                    $('#' + hideFields[i]).hide();
                }

                for (i = 0; i < showFields.length; i++) {
                    $('#' + showFields[i]).show();
                }

                if (typeof eb_current_page === 'undefined') {

                } else {
                    if (eb_current_page === 'default') {
                        calculateIndividualRegistrationFee();
                    } else if (eb_current_page === 'group_billing') {
                        calculateGroupRegistrationFee();
                    } else if (eb_current_page === 'cart') {
                        calculateCartRegistrationFee();
                    }
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                alert(textStatus);
            }
        });
    };

    buildStateFields = function (stateFieldId, countryFieldId, defaultState) {
        var $country = $('#' + countryFieldId), $state = $('#' + stateFieldId);
        var taxStateCountries = Joomla.getOptions('taxStateCountries');

        if ($country.length && $state.length && $state.is('select')) {
            //Bind onchange event to the country
            $country.change(function () {
                var countryName = $(this).val();
                $.ajax({
                    type: 'GET',
                    url: siteUrl + 'index.php?option=com_eventbooking&task=get_states&country_name=' + countryName + '&field_name=' + stateFieldId + '&state_name=' + defaultState + langLinkForAjax,
                    success: function (data) {
                        if ($('#field_' + stateFieldId + ' .eb-form-control').length) {
                            $('#field_' + stateFieldId + ' .eb-form-control').html(data);
                        } else if ($('#field_' + stateFieldId + ' .controls').length) {
                            $('#field_' + stateFieldId + ' .controls').html(data);
                        } else if ($('#field_' + stateFieldId + ' .col-sm-9').length) {
                            $('#field_' + stateFieldId + ' .col-sm-9').html(data);
                        } else if ($('#field_' + stateFieldId + ' .col-md-9').length) {
                            $('#field_' + stateFieldId + ' .col-md-9').html(data);
                        } else {
                            $('#field_' + stateFieldId + ' .uk-form-controls').html(data);
                        }

                        // Tax states country
                        if (stateFieldId === 'state' && taxStateCountries.indexOf(countryName) !== -1) {
                            $('#state').change(function () {
                                if (eb_current_page === 'default') {
                                    calculateIndividualRegistrationFee();
                                } else if (eb_current_page === 'group_billing') {
                                    calculateGroupRegistrationFee();
                                } else if (eb_current_page === 'cart') {
                                    calculateCartRegistrationFee();
                                }
                            });
                        }
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        alert(textStatus);
                    }
                });

            });
        }
    };

    showDepositAmount = function (paymentTypeSelect) {
        if ($(paymentTypeSelect).val() == 1) {
            $('#deposit_amount_container').show();
        } else {
            $('#deposit_amount_container').hide();
        }
    };

    createStripeCardElement = function () {
        if (typeof stripe === 'undefined' || $('#stripe-card-element').length === 0) {
            return;
        }

        if (stripeCard === null) {
            var style = {
                base: {
                    // Add your base input styles here. For example:
                    fontSize: '16px',
                    color: "#32325d",
                }
            };

            // Create an instance of the card Element.
            stripeCard = elements.create('card', {style: style});
        }

        // Add an instance of the card Element into the `card-element` <div>.
        stripeCard.mount('#stripe-card-element');
    };

    paymentMethodCallbackHandle = function () {
        var paymentMethod;

        if ($('input:radio[name="payment_method"]').length) {
            paymentMethod = $('input:radio[name="payment_method"]:checked').val();
        } else {
            paymentMethod = $('input[name="payment_method"]').val();
        }

        if (paymentMethod === undefined) {
            return true;
        }

        // Stripe payment method
        if (paymentMethod.indexOf('os_stripe') === 0) {
            // Old Stripe method
            if (typeof stripePublicKey !== 'undefined' && $('#tr_card_number').is(":visible")) {
                Stripe.card.createToken({
                    number: $('#x_card_num').val(),
                    cvc: $('#x_card_code').val(),
                    exp_month: $('select[name^=exp_month]').val(),
                    exp_year: $('select[name^=exp_year]').val(),
                    name: $('#card_holder_name').val()
                }, stripeResponseHandler);

                return false;
            }

            // Stripe card element
            if (typeof stripe !== 'undefined' && $('#stripe-card-form').is(":visible")) {
                stripe.createToken(stripeCard).then(function (result) {
                    if (result.error) {
                        // Inform the customer that there was an error.
                        //var errorElement = document.getElementById('card-errors');
                        //errorElement.textContent = result.error.message;
                        alert(result.error.message);

                        // Enable process button so that customers can correct card number and process registration again
                        var $form = $('#adminForm');
                        $form.find('#btn-submit').prop('disabled', false);
                        $form.find('#btn-process-group-billing').prop('disabled', false);
                    } else {
                        // Send the token to your server.
                        stripeTokenHandler(result.token);
                    }
                });

                return false;
            }
        }

        if (paymentMethod.indexOf('os_squarecard') === 0 && $('#square-card-form').is(':visible')) {
            squareCardCallBackHandle();

            return false;
        }

        return true;
    };

    stripeResponseHandler = function (status, response) {
        var $form = $('#adminForm');

        if (response.error) {
            // Show the errors on the form
            //$form.find('.payment-errors').text(response.error.message);
            alert(response.error.message);
            $form.find('#btn-submit').prop('disabled', false);
            $form.find('#btn-process-group-billing').prop('disabled', false);
        } else {
            // token contains id, last4, and card type
            var token = response.id;
            // Empty card data since we now have token
            $('#x_card_num').val('');
            $('#x_card_code').val('');
            $('#card_holder_name').val('');
            // Insert the token into the form so it gets submitted to the server
            $form.append($('<input type="hidden" name="stripeToken" />').val(token));
            // and re-submit
            $form.get(0).submit();
        }
    };

    stripeTokenHandler = function (token) {
        // Insert the token ID into the form so it gets submitted to the server
        var form = document.getElementById('adminForm');
        var hiddenInput = document.createElement('input');
        hiddenInput.setAttribute('type', 'hidden');
        hiddenInput.setAttribute('name', 'stripeToken');
        hiddenInput.setAttribute('value', token.id);
        form.appendChild(hiddenInput);

        // Submit the form
        form.submit();
    };

    EBInitReCaptcha2 = function () {
        var item = document.getElementById('eb_dynamic_recaptcha_1'),
            option_keys = ['sitekey', 'theme', 'size', 'tabindex', 'callback', 'expired-callback', 'error-callback'],
            options = {},
            option_key_fq
        ;

        if (item.dataset) {
            options = item.dataset;
        } else {
            for (var j = 0; j < option_keys.length; j++) {
                option_key_fq = ('data-' + option_keys[j]);
                if (item.hasAttribute(option_key_fq)) {
                    options[option_keys[j]] = item.getAttribute(option_key_fq);
                }
            }
        }

        // Set the widget id of the recaptcha item
        item.setAttribute(
            'data-recaptcha-widget-id',
            grecaptcha.render(item, options)
        );
    };

    EBInitReCaptchaInvisible = function () {
        var item = document.getElementById('eb_dynamic_recaptcha_1'),
            option_keys = ['sitekey', 'badge', 'size', 'tabindex', 'callback', 'expired-callback', 'error-callback'],
            options = {},
            option_key_fq
        ;

        if (item.dataset) {
            options = item.dataset;
        } else {
            for (var j = 0; j < option_keys.length; j++) {
                option_key_fq = ('data-' + option_keys[j]);
                if (item.hasAttribute(option_key_fq)) {
                    options[option_keys[j]] = item.getAttribute(option_key_fq);
                }
            }
        }
        // Set the widget id of the recaptcha item
        item.setAttribute(
            'data-recaptcha-widget-id',
            grecaptcha.render(item, options)
        );
        // Execute the invisible reCAPTCHA
        grecaptcha.execute(item.getAttribute('data-recaptcha-widget-id'));
    };

    initializeTermsAndConditionsModal = function () {
        var links = document.querySelectorAll('a.eb-colorbox-term');

        if (links.length > 0) {
            [].slice.call(links).forEach(function (link) {
                link.addEventListener('click', function (e) {
                    e.preventDefault();
                    var modal = new tingle.modal({
                        cssClass: ['eb-terms-and-conditions-modal'],
                        onClose: function () {
                            modal.destroy();
                        }
                    });
                    modal.setContent('<iframe width="100%" height="480px" src="' + link.href + '" frameborder="0" allowfullscreen></iframe>');
                    modal.open();
                });
            })
        }
    };

    ebShowPaymentMethodFields = function (paymentMethod) {
        var allPaymentMethods = Joomla.getOptions('all_payment_method_fields', []);
        var selectedPaymentMethodFields = Joomla.getOptions(paymentMethod + '_fields', []);
        var fieldContainer;

        allPaymentMethods.forEach(function (field) {
            if (selectedPaymentMethodFields.indexOf(field) === -1) {
                fieldContainer = document.getElementById('field_' + field);

                if (fieldContainer) {
                    fieldContainer.style.display = 'none';
                }
            }
        });

        selectedPaymentMethodFields.forEach(function (field) {
            fieldContainer = document.getElementById('field_' + field);

            if (fieldContainer) {
                fieldContainer.style.display = '';
            }
        });

    };

})(Eb.jQuery);