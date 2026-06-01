(function (document, Joomla) {
    Joomla.submitbutton = function (pressbutton)
    {
        const form = document.adminForm;
        if (pressbutton === 'cancel' || pressbutton === 'close') {
            Joomla.submitform(pressbutton);
        } else {
            // Check event title
            if (form.title.value === '') {
                alert(Joomla.JText._('EB_PLEASE_ENTER_TITLE'));
                form.title.focus();
                return;
            }

            // Check event date
            if (form.event_date.value === '') {
                alert(Joomla.JText._('EB_ENTER_EVENT_DATE'));
                form.event_date.focus();
                return;
            }

            // Force user to select at least one category
            if (form.main_category_id.value === '0') {
                alert(Joomla.JText._('EB_CHOOSE_CATEGORY'));
                return;
            }

            // Check recurring events parameters
            if (Joomla.getOptions('activateRecurringEvent') && form.recurring_type) {
                var recurringType = form.recurring_type.value;

                if (recurringType > 0) {
                    if (form.recurring_frequency.value === '') {
                        alert(Joomla.JText._('EB_ENTER_RECURRING_INTERVAL'));
                        form.recurring_frequency.focus();
                        return;
                    }

                    // Weekly recurring, at least one weekday needs to be selected
                    if (recurringType === '2') {
                        if (form.querySelectorAll(['input[name="weekdays[]"]:checked']).length === 0) {
                            alert(Joomla.JText._('EB_CHOOSE_ONE_DAY'));
                            form['weekdays[]'][0].focus();
                            return;
                        }
                    }

                    if (recurringType === '3') {
                        if (form.monthdays.value === '') {
                            alert(Joomla.JText._('EB_ENTER_DAY_IN_MONTH'));
                            form.monthdays.focus();
                            return;
                        }
                    }

                    if (form.recurring_end_date.value === '' && form.recurring_occurrencies.value <= 0) {

                        alert(Joomla.JText._('EB_ENTER_RECURRING_ENDING_SETTINGS'));

                        form.recurring_end_date.focus();

                        return;
                    }
                }
            }

            Joomla.submitform(pressbutton);
        }
    };

    addRow = function () {
        var table = document.getElementById('price_list');
        var newRowIndex = table.rows.length - 1;
        var row = table.insertRow(newRowIndex);
        var registrantNumber = row.insertCell(0);
        var price = row.insertCell(1);
        registrantNumber.innerHTML = '<input type="text" class="input-small form-control" name="registrant_number[]" size="10" />';
        price.innerHTML = '<input type="text" class="input-small form-control" name="price[]" size="10" />';
    };

    removeRow = function () {
        var table = document.getElementById('price_list');
        var deletedRowIndex = table.rows.length - 2;

        if (deletedRowIndex >= 1) {
            table.deleteRow(deletedRowIndex);
        } else {
            alert(Joomla.JText._('EB_NO_ROW_TO_DELETE'));
        }
    };

    setDefaultData = function () {
        var form = document.adminForm;
        if (form.recurring_type[1].checked) {
            if (form.number_days.value == '') {
                form.number_days.value = 1;
            }
        } else if (form.recurring_type[2].checked) {
            if (form.number_weeks.value == '') {
                form.number_weeks.value = 1;
            }
        } else if (form.recurring_type[3].checked) {
            if (form.number_months.value == '') {
                form.number_months.value = 1;
            }
        }
    };

})(document, Joomla);