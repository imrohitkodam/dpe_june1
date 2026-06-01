window.onload = function(){
	jQuery(".icon-calendar").addClass('far fa-calendar-alt');
  	var element = document.getElementsByClassName('btn-clear');
 	element[0].classList.remove('btn-clear');
}

var valid = {
	positiveNumber : function(el)
	{
		var enable = jQuery('#allowfield').val();

		if (jQuery('.titlevalid').val() == '')
		{
			alert(Joomla.JText._('COM_JTICKETING_TICKET_TITLE_EMPTY'));
		}

		let returnValue = valid.getRoundedValue(el.value);

		if (returnValue)
		{
			jQuery(el.id).focus();

			let msg = {
				warning: [returnValue],
			};

			Joomla.renderMessages(msg);
		}

		let i = 0;
		for (i = 0; i < el.value.length; i++)
		{
			if ((el.value.charCodeAt(i) > 64 && el.value.charCodeAt(i) < 92) || (el.value.charCodeAt(i) > 96 && el.value.charCodeAt(i) < 123))
			{
				alert(Joomla.JText._('COM_JTICKETING_ENTER_NUMERICS'));
				el.value = el.value.substring(0, i);
				break;
			}
		}
		if (el.value < 0 || el.value == -0)
		{
			alert(Joomla.JText._('COM_JTICKETING_ENTER_VALID_TICKET_AMOUNT'));
			el.value = 0;
		}
	},

	getRoundedValue: function(value) {
            var errorMsg = '';

            jQuery.ajax({
                type: "POST",
                dataType: "json",
                data: value,
                async: false,
                url: Joomla.getOptions('system.paths').base + "/index.php?option=com_jticketing&format=json&task=eventform.getRoundedValue&price=" + value,
                success: function(data) {

                    if (data.data != value) {
                        roundedPrice = data.data;
                        errorMsg = Joomla.JText._('COM_JTICKETING_VALIDATE_ROUNDED_PRICE').concat(roundedPrice);
                    }

                },
            });

            return errorMsg;
        },

	seatsValidation : function(intergration)
	{
		if (jQuery('.titlevalid').val() == '')
		{
			alert(Joomla.JText._('COM_JTICKETING_TICKET_TITLE_EMPTY'));
		}

		if (intergration == "es")
		{
			var ticketCount = jQuery('input[name="guestlimit"]').val();
		}
		else
		{
			var ticketCount = jQuery('input[name="ticket"]').val();
		}

		var seatAvailbility = jQuery(".unlimitedseats option:selected").val();

		var res = 0;

		if (seatAvailbility == 0)
		{
			jQuery(".avail").each(function()
			{
				if (jQuery(this).val())
				{
					if(jQuery(this).val() < 0 || !/^\d+$/.test(jQuery(this).val()))
					{
						alert(Joomla.JText._('COM_JTICKETING_TICKET_SEAT_COUNT_ERROR'));

						jQuery(this).val('0');
					}

					res = res + parseInt(jQuery(this).val(), 10)
				}
			});
		}

		if (res > ticketCount && ticketCount > 0)
		{
			alert(seatCountMsg);

			jQuery(".avail").val('0');
		}
	},

	fieldDisplay : function()
	{

		var value = jQuery('.ticketFields').val();

		if (value == 1)
		{
			jQuery("#fieldTicket").css({ 'display': "block" });
		}
		else
		{
			jQuery("#fieldTicket").css({ 'display': "none" });
		}
	},

	ticketDateValidation : function(intergration) {

		var ticketEndDate = document.activeElement.value;

		if (jQuery('.titlevalid').val() == '')
		{
			alert(Joomla.JText._('COM_JTICKETING_TICKET_TITLE_EMPTY'));
		}

		if (intergration == 'es')
		{
			var eventDate = jQuery('input[name="endDatetime"]').val();

			if (eventDate == '')
			{
				eventDate = new Date(jQuery('input[name="startDatetime"]').val()).toLocaleDateString();
				ticketEndDate = new Date(ticketEndDate).toLocaleDateString()

				if (ticketEndDate > eventDate  && ticketEndDate != 'Invalid Date')
				{
					alert(eventDateMsg);
				}
			}
			else
			{
				if (ticketEndDate > eventDate)
				{
					alert(eventDateMsg);
				}
			}
		}
		else if (intergration == 'js')
		{
			var eventDate = jQuery('#enddate').val();
			eventDate = eventDate + ' ' + jQuery('#endtime-hour').val() + ':' + jQuery('#endtime-min').val() + ':00';
			if (ticketEndDate > eventDate)
			{
				alert(eventDateMsg);
			}
		}
	},

	titleValidation : function(es) {
		if (es.value == '')
		{
			alert(Joomla.JText._('COM_JTICKETING_TICKET_TITLE_EMPTY'));
		}
	}
};
