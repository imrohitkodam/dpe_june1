(function (document, Joomla) {
    Joomla.submitbutton = function (pressbutton)
    {
        const form = document.adminForm;

        if (pressbutton === 'cancel')
        {
            Joomla.submitform(pressbutton);
        }
        else
        {
            if (form.title.value === '')
            {
                alert(Joomla.JText._('EB_PLEASE_ENTER_TITLE'));
                form.title.focus();
                return;
            }

            if (form.main_category_id.value === '0')
            {
                alert(Joomla.JText._('EB_CHOOSE_CATEGORY'));
                form.category_id.focus();
                return;
            }

            if (form.event_date.value === '')
            {
                alert(Joomla.JText._('EB_ENTER_EVENT_DATE'));
                form.event_date.focus();
                return;
            }

            if (document.getElementById('activate_recurring_events').value === '1' && form.recurring_type)
            {
                var recurringType = form.recurring_type.value;

                if (recurringType > 0)
                {
                    if (form.recurring_frequency.value === '')
                    {
                        alert(Joomla.JText._('EB_ENTER_RECURRING_INTERVAL'));
                        form.recurring_frequency.focus();
                        return;
                    }

                    // Weekly recurring, at least one weekday needs to be selected
                    if (recurringType === '2')
                    {
                        if (form.querySelectorAll(['input[name="weekdays[]"]:checked']).length === 0) {
                            alert(Joomla.JText._('EB_CHOOSE_ONE_DAY'));
                            form['weekdays[]'][0].focus();
                            return;
                        }
                    }

                    if (recurringType === '3')
                    {
                        if (form.monthdays.value === '')
                        {
                            alert(Joomla.JText._('EB_ENTER_DAY_IN_MONTH'));
                            form.monthdays.focus();

                            return;
                        }
                    }

                    if (form.recurring_end_date.value === '' && form.recurring_occurrencies.value <= 0)
                    {
                        alert(Joomla.JText._('EB_ENTER_RECURRING_ENDING_SETTINGS'));
                        form.recurring_end_date.focus();

                        return;
                    }
                }

            }

            Joomla.submitform(pressbutton);
        }
    };

    addRow = function() {
        var table = document.getElementById('price_list');
        var newRowIndex = table.rows.length - 1;
        var row = table.insertRow(newRowIndex);
        var registrantNumber = row.insertCell(0);
        var price = row.insertCell(1);
        var inputClasses = Joomla.getOptions('groupRateInputClasses', 'input-mini form-control');
        registrantNumber.innerHTML = '<input type="text" class="' + inputClasses + '" name="registrant_number[]" size="10" />';
        price.innerHTML = '<input type="text" class="' + inputClasses + '" name="price[]" size="10" />';
    };

    removeRow = function()
    {
        var table = document.getElementById('price_list');
        var deletedRowIndex = table.rows.length - 2;

        if (deletedRowIndex >= 1)
        {
            table.deleteRow(deletedRowIndex);
        }
        else
        {
            alert(Joomla.JText._('EB_NO_ROW_TO_DELETE'));
        }
    };

    document.addEventListener('DOMContentLoaded', function () {
        document.getElementById('addGroupRateButton').addEventListener('click', addRow);
        document.getElementById('removeGroupRateButton').addEventListener('click', removeRow);
    });
})(document, Joomla);