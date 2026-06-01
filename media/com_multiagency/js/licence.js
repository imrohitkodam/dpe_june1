// Valid from and valid to date*/
function setStartDate()
{
	var selectedFromDate = document.getElementById('jform_start_date').value;
	var selectedToDate = document.getElementById('jform_end_date').value;
	var today = new Date();
	today.setHours(0, 0, 0, 0);
	var todaysDate = today.format("%d-%m-%Y");

	if (selectedFromDate)
	{
		dateFormatValidation(selectedFromDate, 'jform_start_date');
	}
	if (selectedToDate)
	{
		dateFormatValidation(selectedToDate, 'jform_end_date');
	}

	todaysDateArr = todaysDate.split("-");
	todaysDateArr = new Date(todaysDateArr[2], todaysDateArr[1]-1, todaysDateArr[0]);
	todaysDate = todaysDateArr.getTime();

	selectedFromDateArr = selectedFromDate.split("-");
	selectedFromDateArr = new Date(selectedFromDateArr[2], selectedFromDateArr[1]-1, selectedFromDateArr[0]);
	selectedFromDate = selectedFromDateArr.getTime();

	selectedToDateArr = selectedToDate.split("-");
	selectedToDateArr = new Date(selectedToDateArr[2], selectedToDateArr[1] - 1 , selectedToDateArr[0]);
	selectedToDate = selectedToDateArr.getTime();

	// DPE hack to remove start date validation
	/*
	if (selectedFromDate < todaysDate)
	{
		alert(Joomla.JText._('COM_MULTIAGENCY_LICENCES_START_DATE_ERROR'));
		document.getElementById('jform_start_date').value= "";
	}
	*/ 

	if (selectedFromDate > selectedToDate)
	{
		alert(Joomla.JText._('COM_MULTIAGENCY_LICENCES_START_END_DATE_ERROR'));
		document.getElementById('jform_start_date').value= "";
		document.getElementById('jform_end_date').value= "";
	}

	startDate = new Date(selectedFromDate);
	startDate.setHours(0, 0, 0, 0);
	endData = new Date(selectedToDate);
	endData.setHours(0, 0, 0, 0);
	
	var agencyId  = jQuery("#jform_multiagency_id").val();
	var url       = Joomla.getOptions("system.paths").root + "/index.php?option=com_multiagency&task=licenceform.checkExistingLicence&format=json";
	var promise   = jQuery.ajax({
		url: url,
		type: 'POST',
		data: {'agencyId':agencyId},
		dataType: 'json'
	});
	
			promise.fail(
			function(response) {
				// Ajax request failed
			}
		).done(
			function(response) {
				if (response)
				{
					if (response.success) {

						if (isNaN(selectedFromDate))
						{
							document.getElementById('jform_start_date').value= response.data.nextDate;
						}

						var nextDateArray = response.data.nextDate.split("-");
						var selectedNextDateArray = new Date(nextDateArray[2], nextDateArray[1]-1, nextDateArray[0]);
						var startDate = document.getElementById('jform_start_date').value;
						var startDateArray = startDate.split("-");
						var selectedstartDate = new Date(startDateArray[2], startDateArray[1]-1, startDateArray[0]);

						if (selectedstartDate < selectedNextDateArray)
						{
							alert(Joomla.JText._('COM_MULTIAGENCY_LICENCES_INVALID_DATE'));
							document.getElementById('jform_start_date').value= response.data.nextDate;
						}
					}
				}
			}
		);

}

function setEndDate()
{
	var selectedFromDate = document.getElementById('jform_start_date').value;
	var selectedToDate = document.getElementById('jform_end_date').value;
	var today = new Date();
	today.setHours(0, 0, 0, 0);
	var todaysDate = today.format("%d-%m-%Y");


	if (selectedFromDate)
	{
		dateFormatValidation(selectedFromDate, 'jform_start_date');
	}
	if (selectedToDate)
	{
		dateFormatValidation(selectedToDate, 'jform_end_date');
	}

	todaysDateArr = todaysDate.split("-");
	todaysDateArr = new Date(todaysDateArr[2], todaysDateArr[1]-1, todaysDateArr[0]);
	todaysDate = todaysDateArr.getTime();

	selectedFromDateArr = selectedFromDate.split("-");
	selectedFromDateArr = new Date(selectedFromDateArr[2], selectedFromDateArr[1]-1, selectedFromDateArr[0]);
	selectedFromDate = selectedFromDateArr.getTime();

	selectedToDateArr = selectedToDate.split("-");
	selectedToDateArr = new Date(selectedToDateArr[2], selectedToDateArr[1] - 1 , selectedToDateArr[0]);
	selectedToDate = selectedToDateArr.getTime();

	// DPE Hack start: Removed commented code to check end date
	if (selectedToDate < todaysDate)
	{
		alert(Joomla.JText._('COM_MULTIAGENCY_LICENCES_END_DATE_ERROR'));
		document.getElementById('jform_end_date').value= "";
	}
	// DPE Hack end

	if (selectedToDate < selectedFromDate)
	{
		alert(Joomla.JText._('COM_MULTIAGENCY_LICENCES_END_START_DATE_ERROR'));
		document.getElementById('jform_end_date').value= "";
	}

	startDate = new Date(selectedFromDate);
	startDate.setHours(0, 0, 0, 0);
	endData = new Date(selectedToDate);
	endData.setHours(0, 0, 0, 0);

}

function dateFormatValidation(dateVal, errorFlag)
{
	var validatePattern = /^(\d{1,2})(\/|-)(\d{1,2})(\/|-)(\d{4})$/;
	dateValues = dateVal.match(validatePattern);

	if (dateValues == null)
	{
		document.getElementById(errorFlag).value = '';
	}
}

function setSeats()
{
	var totalSeats = parseInt(document.getElementById('jform_total_seats').value);

	if (totalSeats > 0)
		{
			var usedSeats = parseInt(document.getElementById('jform_used_seats').value);

			if (totalSeats < usedSeats)
			{
					alert(Joomla.JText._('COM_MULTIAGENCY_LICENCES_TOTAL_SEATS_ERROR'));
					document.getElementById('jform_total_seats').value= "0";
			}
		}
	else
		{
			alert(Joomla.JText._('COM_MULTIAGENCY_LICENCES_TOTAL_SEATS_NEGATIVE_ERROR'));
			document.getElementById('jform_total_seats').value= "0";
		}
}

function setCourses()
{
  checkExistingCourse();
  let licenceType =  jQuery('#jform_licence_type').val();

  if (licenceType == 'per')
  {
      jQuery('.courseList').removeClass('hide');
      jQuery('#jform_course_id').attr('required', true);
      jQuery('#jform_course_id').addClass('required');
  }
  else
  {
      jQuery('.courseList').addClass('hide');
      jQuery('#jform_course_id').removeAttr('required');
      jQuery('#jform_course_id').removeClass('required');
  }
}


function checkExistingCourse()
  {
	let licenceType =  jQuery('#jform_licence_type').val();
	let agencyId =  parseInt(jQuery('#jform_multiagency_id').val());
	let courseId =  parseInt(jQuery('#jform_course_id').val());
	jQuery('.licenceform').removeAttr('disabled');

	if (licenceType && agencyId)
	{
	jQuery.ajax(
		  {
			 url: Joomla.getOptions('system.paths').root + '/index.php?option=com_multiagency&task=licenceform.checkExistingCourse',
			 type: "post",
				data : {'agencyId' : agencyId,'licenceType': licenceType, 'courseId' : courseId},
				dataType: 'json',
				success: function(result)
				{
				 if(result.data)
					 {
						 if (result.data.msg)
						{
							alert(result.data.msg)
							jQuery('.licenceform').attr('disabled','disabled');
						}
					 }
				 else
					 {
						 jQuery('.licenceform').removeAttr('disabled');
					 }
			 }
		  });
	  }
  }

function deleteItem(itemId)
{
	let id = window.atob(itemId) ;
	if(isNaN(id) || id =='')
	{
		return false;
	}
	let baseUrl = Joomla.getOptions('system.paths').base;
	let redirectURL = baseUrl + '/index.php?option=com_multiagency&task=licenceform.remove&id='+id;
	if (!confirm(Joomla.JText._('COM_MULTIAGENCY_DELETE_MESSAGE'))) {
		return false;
	}
	window.location.href = redirectURL;
}

jQuery(document).ready(function(){
	jQuery('#clear-search-button').on('click', function () {
		jQuery('#filter_search').val('');
		jQuery('#adminForm').submit();
	});
})
