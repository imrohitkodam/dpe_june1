(function (document, Joomla) {

	showHideFieldsFromTicketType = function (id, show) {
		var fields = Joomla.getOptions(id + '_fields', []);

		// Ticket type selected
		fields.forEach(function (field) {
			fieldContainer = document.getElementById('field_' + field);

			if (fieldContainer) {
				if (show) {
					fieldContainer.style.display = '';
				} else {
					fieldContainer.style.display = 'none';
				}
			}
		});
	}
	document.addEventListener('DOMContentLoaded', function () {
		const selects = document.querySelectorAll('.ticket_type_quantity');
		[].slice.call(selects).forEach(function (select) {
			select.addEventListener('change', function (e) {

				if (Joomla.getOptions('onlyAllowRegisterOneTicketType')) {
					for (i = 0; i < selects.length; ++i) {
						if (selects[i] !== select) {
							selects[i].value = 0;
							showHideFieldsFromTicketType(selects[i].id, false);
						}
					}
				}

				var showFields = false;

				if (e.target.value > 0) {
					showFields = true;
				}

				showHideFieldsFromTicketType(e.target.id, showFields);

				calculateIndividualRegistrationFee(1);
			});
		});
	});
})(document, Joomla);