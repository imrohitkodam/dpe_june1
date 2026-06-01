(function (document, Joomla) {
    Joomla.submitbutton = function (pressbutton)
    {
        const form = document.adminForm;

        if (pressbutton === 'cancel')
        {
            Joomla.submitform(pressbutton);
        }
        else if (form.code.value === '')
        {
            alert(Joomla.JText._('EB_ENTER_COUPON'));
            form.code.focus();
        }
        else if (form.discount.value === '')
        {
            alert(Joomla.JText._('EN_ENTER_DISCOUNT_AMOUNT'));
            form.discount.focus();
        }
        else
        {
            Joomla.submitform(pressbutton);
        }
    };
})(document, Joomla);