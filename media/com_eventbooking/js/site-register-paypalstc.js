(function (document, Joomla) {
    document.addEventListener('DOMContentLoaded', function () {
        paypal.Buttons({
            style: {
                layout: Joomla.getOptions('pp_stc_buttons_layout', 'vertical'),
                color: Joomla.getOptions('pp_stc_buttons_color', 'gold'),
                shape: Joomla.getOptions('pp_stc_buttons_shape', 'rect'),
            },

            // Call your server to set up the transaction
            createOrder: function (data, actions) {
                return actions.order.create(
                    Joomla.getOptions('orderData')
                );
            },

            // Call your server to finalize the transaction
            onApprove: function (data, actions) {
                return fetch(Joomla.getOptions('fullSiteUrl') + 'index.php?option=com_eventbooking&task=payment_confirm&payment_method=os_paypalstc&order_id=' + data.orderID + '&registration_code=' + Joomla.getOptions('registrationCode') + '&Itemid=' + Joomla.getOptions('Itemid'), {
                    method: 'post'
                }).then(function (res) {
                    return res.json();
                }).then(function (orderData) {
                    if (orderData.redirectUrl) {
                        actions.redirect(orderData.redirectUrl);

                        return;
                    }

                    var errorDetail = Array.isArray(orderData.details) && orderData.details[0];

                    if (errorDetail && errorDetail.issue === 'INSTRUMENT_DECLINED') {
                        return actions.restart(); // Recoverable state, per:
                    }
                });
            }
        }).render('#paypal-button-container');
    });
})(document, Joomla);