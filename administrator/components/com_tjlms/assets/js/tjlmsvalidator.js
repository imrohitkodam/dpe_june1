jQuery(window).on('load', function() {
	document.formvalidator.setHandler('natural-number', function(value, element) {
		value = punycode.toASCII(value);
		var regex = /^[1-9]\d*$/;
		return regex.test(value);
	});

	document.formvalidator.setHandler('whole-number', function(value, element) {
		value = punycode.toASCII(value);
		var regex = /^[0-9]{1,9}$/;
		return regex.test(value);
	});

	document.formvalidator.setHandler('ymd-date', function(value, element) {
		//value = punycode.toASCII(value);
		var regex = /^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/;
		return regex.test(value);
	});

	document.formvalidator.setHandler('dmy-date', function(value, element) {
		var regex = /^(0[1-9]|[1-2][0-9]|3[0-1])-(0[1-9]|1[0-2])-[0-9]{4}$/;
		return regex.test(value);
	});

	document.formvalidator.setHandler('datetime', function(value, element) {
		//value = punycode.toASCII(value);
		var regex = /^(\d{4})(\/|\-)(\d{1,2})(\/|\-)(\d{1,2})\s(\d{1,2})(\/|\:)(\d{1,2})(\/|\:)(\d{1,2})$/;
		return regex.test(value);
	});

	document.formvalidator.setHandler('positive-number', function(value, element) {
		//value = punycode.toASCII(value);
		var regex = /^[+]?([0-9]+(?:[\.][0-9]*)?|\.[0-9]+)$/;
		return regex.test(value);
	});

	document.formvalidator.setHandler('imagetype', function(value, element) {
		/* image/jpeg,image/png,image/jpg */
		var allowedType = jQuery(element).attr('accept');
		if (allowedType && value)
		{
			allowedType 	= allowedType.replace(/image\//g , "");
			allowedType 	= allowedType.split(',');
			var ext = value.substr(value.lastIndexOf('.') + 1);
				ext = ext.toLocaleLowerCase();
			if(allowedType.indexOf(ext) == -1)
			{
				value.value = '';
				return false;
			}
		}
		return true;
	});
});

function valid_dates(batch_start_date,batch_due_date,task)
{
	var start_date = jQuery('#'+batch_start_date).val();
	var due_date   = jQuery('#'+batch_due_date).val();

	if (start_date ==  "" || due_date ==  "")
		{
			alert(Joomla.JText._("COM_TJLMS_SELECT_FILL_DATES"));

			if (start_date == "")
			{
				jQuery('#start_date').focus();
			}
			else if(due_date == "")
			{
				jQuery('#due_date').focus();
			}

			return false;
		}

		var res = checkDateFormat(start_date);

		if (res == false)
		{
			alert(Joomla.JText._("COM_TJLMS_INVALID_DATE_FORMAT") + start_date);
			jQuery('#start_date').val("");
			return false;
		}

		var res = checkDateFormat(due_date);

		if (res == false)
		{
			alert(Joomla.JText._("COM_TJLMS_INVALID_DATE_FORMAT") + due_date);
			jQuery('#due_date').val("");
			return false;
		}


		var today = new Date();
		due_date = new Date(due_date)
		start_date = new Date(start_date)

		today.setHours(0, 0, 0, 0);
		due_date.setHours(0, 0, 0, 0);
		start_date.setHours(0, 0, 0, 0);

		if (start_date && due_date)
		{
			if(due_date < start_date)
			{
				alert(Joomla.JText._("COM_TJLMS_START_GT_THAN_DUE_DATE"));
				return false;
			}
		}

		if (task == "batchAssign")
		{
			Joomla.submitbutton('enrolment.batchAssign');
		}

	return true;
}

function valid_dates_manage(start_date,due_date)
{
	var today  = new Date();
		if (due_date ==  "")
		{
			alert(Joomla.JText._("COM_TJLMS_DUE_DATE_EMPTY"));

			if(due_date == "")
			{
				jQuery('#due_date').focus();
			}

			return false;
		}

		var res = checkDateFormat(start_date);

			if (res == false)
			{
				alert(Joomla.JText._("COM_TJLMS_INVALID_DATE_FORMAT") +  start_date);
				jQuery('#' + start_date).val("");
				return false;
			}

			start_date = new Date(start_date);
			today.setHours(0, 0, 0, 0);
			start_date.setHours(0, 0, 0, 0);

			var res = checkDateFormat(due_date);

			if (res == false)
			{
				alert(Joomla.JText._("COM_TJLMS_INVALID_DATE_FORMAT") +due_date);
				jQuery('#' + due_date).val('');
				return false;
			}

			due_date = new Date(due_date)
			if (start_date && due_date)
			{
				if(due_date < start_date)
				{
					alert(Joomla.JText._("COM_TJLMS_START_GT_THAN_DUE_DATE"));
				return false;
				}
			}
		return true;
}

// Validate date format
function checkDateFormat(datevalue)
{
	// regular expression to match required date format
	regExp = /^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/;

	if (datevalue != '' && !(datevalue.match(regExp)))
	{
		return false;
	}

	return true;
}
