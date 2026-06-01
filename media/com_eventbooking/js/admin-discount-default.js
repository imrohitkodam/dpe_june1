(function (document, Joomla) {
    Joomla.submitbutton = function(pressbutton)
    {
        const form = document.adminForm;

        if (pressbutton === 'cancel')
        {
            Joomla.submitform(pressbutton);
        }
        else if (form.discount_amount.value === '')
        {
            alert(Joomla.JText._('EB_ENTER_DISCOUNT_AMOUNT'));
            form.discount_amount.focus();
        }
        else
        {
            Joomla.submitform(pressbutton);
        }
    }
})(document, Joomla);