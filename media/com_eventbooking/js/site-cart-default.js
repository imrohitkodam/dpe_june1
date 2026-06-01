(function (document) {
    checkOut = function () {
        const form = document.adminForm;
        if (checkQuantity()) {
            form.task.value = 'checkout';
            form.submit();
        }
    };

    continueShopping = function (continueUrl) {
        document.location.href = continueUrl;
    };

    updateCart = function () {
        const form = document.adminForm;
        if (checkQuantity()) {
            form.task.value = 'cart.update_cart';
            form.submit();
        }
    };

    removeItem = function (id) {
        if (confirm(EB_REMOVE_CONFIRM)) {
            const form = document.adminForm;
            form.id.value = id;
            form.task.value = 'cart.remove_cart';
            form.submit();
        }
    };

    checkQuantity = function () {
        var eventId, enteredQuantity, index, availableQuantity;
        var eventIds = [], quantities = [];
        const quantityInputs = document.querySelectorAll('input[name="quantity[]"]');

        document.querySelectorAll('input[name="event_id[]"]').forEach(function (input) {
            eventIds.push(input.value);
        });

        quantityInputs.forEach(function (input) {
            quantities.push(input.value);
        });

        for (var i = 0; i < eventIds.length; i++) {
            eventId = parseInt(eventIds[i], 10);
            enteredQuantity = quantities[i];
            index = arrEventIds.indexOf(eventId);

            if (index !== -1) {
                availableQuantity = arrQuantities[index];

                if ((availableQuantity != -1) && (enteredQuantity > availableQuantity)) {
                    alert(EB_INVALID_QUANTITY + availableQuantity);
                    quantityInputs[i].value = availableQuantity;
                    quantityInputs[i].focus();
                }
            }
        }

        return true;
    };
})(document);