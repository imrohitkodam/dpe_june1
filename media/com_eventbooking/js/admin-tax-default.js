(function (document) {
    Joomla.submitbutton = function (pressbutton) {
        const form = document.adminForm;

        if (pressbutton === 'cancel') {
            Joomla.submitform(pressbutton, form);
        }
        else
        {
            if (form.rate.value === "")
            {
                alert(Joomla.JText._('EB_ENTER_TAX_RATE'));
                form.rate.focus();
            }
            else
            {
                Joomla.submitform(pressbutton, form);
            }
        }
    }
})(document);