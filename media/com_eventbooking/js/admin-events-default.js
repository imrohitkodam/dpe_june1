(function (document, Joomla) {
    document.addEventListener('DOMContentLoaded', function () {
        const filterState = document.getElementById('filter_state');
        filterState.classList.add('input-medium');
        filterState.classList.remove('inputbox');
    });

    Joomla.submitbutton = function(pressbutton)
    {
        if (pressbutton === 'cancel_event')
        {
            if (!confirm(Joomla.JText._('EB_CANCEL_EVENT_CONFIRM')))
            {
                return;
            }
        }

        Joomla.submitform( pressbutton );

        if (pressbutton === 'export')
        {
            const form = document.adminForm;
            form.task.value = '';
        }
    }
})(document, Joomla);