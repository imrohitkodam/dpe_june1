/*
 * @package    Com_Multiagency
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2017 TechJoomla. All rights reserved
 * @license    GNU General Public License version 2, or later
 */

if(typeof(techjoomla) == 'undefined') {
	var techjoomla = {};
}

if(typeof techjoomla.jQuery == "undefined")
{
	techjoomla.jQuery = jQuery;
}

var comMultiagency = {

	multiagency:{

		generateStoreState: function(admin)
		{
			country_id = techjoomla.jQuery('#jform_country_id').val();

			if (admin == Number(1))
			{
				var url = "?option=com_multiagency&task=multiagency.getRegions&country_id="+country_id;
			}
			else
			{
				var url = "?option=com_multiagency&task=multiagencyForm.getRegions&country_id="+country_id;
			}

			techjoomla.jQuery.ajax({
				type : "POST",
				url : url,
				success : function(response)
				{
					techjoomla.jQuery('#jform_state_id').html(response);
				}
			}).done(function (){
				jQuery('#jform_state_id').trigger("liszt:updated");
				});
		},
		addMultiagency: function(root_url)
		{
			var register = root_url+"index.php?option=com_multiagency&view=userform&tmpl=component&isMultiagencyUser=1";

			if (window.innerWidth <= 468)
			{
				width = (window.innerWidth - 20);
				height = (window.innerHeight - 20)
			}
			else
			{
				width = 600;
				height = 300;
			}

			SqueezeBox.open(register ,{handler: 'iframe', size: {x: width, y: height}});
		},
		closeModal: function()
		{
			window.close();
		}
	}
}
function mangerAssign(id)
{
      let fieldId = id.id;
           fieldId =  fieldId.split("__");
      let fname = fieldId[0]+'__'+fieldId[1]+'__first_name';
      let email = fieldId[0]+'__'+fieldId[1]+'__email';

   	if(id.value == 0)
	{
		techjoomla.jQuery('#'+fname).val('');
		techjoomla.jQuery('#'+email).val('');
		techjoomla.jQuery('#'+fname).removeAttr('readonly');
		techjoomla.jQuery('#'+email).removeAttr('readonly');
	}
	else
	{
		jQuery.ajax(
		{
			url: JUriRoot + "index.php?option=com_multiagency&task=multiagencyform.getUser&tmpl=component&userId="+id.value,
			type: "GET",
			dataType: "json",
			success: function(result)
			{
				techjoomla.jQuery('#'+fname).val(result.name);
				techjoomla.jQuery('#'+email).val(result.email);
				techjoomla.jQuery('#'+fname).attr('readonly', true);
				techjoomla.jQuery('#'+email).attr('readonly', true);
			}
		});
	}
}

function checkDuplicates() {

	// we store the inputs value inside this array
	var values = [];
	// return this
	var isDuplicated = false;
	// loop through elements
	jQuery('.emailIds').each(function () {
	//If value is empty then move to the next iteration.
	if(!this.value) return true;
	//If the stored array has this value, break from the each method
	if(values.indexOf(this.value) !== -1) {
		isDuplicated = true;
		return false;
	 }
	// store the value
	values.push(this.value);
	});
	if(isDuplicated){
		alert(Joomla.JText._('COM_MULTIAGENCY_DUPLICATE_MANAGER'));
		return false;
	}else
	{
		  var flag = false;
			jQuery('.emailIds').each(function () {
			   let checkEmailAttr = jQuery(this).attr('readonly');

			   if (typeof(checkEmailAttr) == "undefined")
			  {
					 jQuery.ajax(
					 {
						url: JUriRoot + "index.php?option=com_multiagency&task=userform.validateEmail&tmpl=component&email="+this.value,
						type: "GET",
						dataType: "json",
						async: false,
						success: function(result)
						{
									if(result.data == 'failure')
									{
									   flag = true;
									   alert(Joomla.JText._('COM_MULTIAGENCY_DUPLICATE_MANAGER'));
									}
						}
					 });
			   }
			   if(flag){
				  return false;
			   }
		 });
		 if(flag){
			return false;
		 }
	}
	return true;
}

function enroll()
{
	if (document.adminForm.boxchecked.value == 0)
	{
				alert(Joomla.JText._("COM_MULTIAGENCY_ENROLMENT_SELECT_ENROLL_ITEMS"));
				return false;
	}
	else
	{
		techjoomla.jQuery('#task').val('courses.enrollment');
		Joomla.submitform('courses.enrollment');
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
	let redirectURL = baseUrl + '/index.php?option=com_multiagency&task=multiagencyform.remove&id='+id;
	if (!confirm(Joomla.JText._('COM_MULTIAGENCY_DELETE_MESSAGE'))) {
		return false;
	}
	window.location.href = redirectURL;
}

jQuery(document).ready(function(){
	jQuery('.subform-repeatable').children('.btn-toolbar').remove();

	jQuery('#clear-search-button').on('click', function () {
		jQuery('#filter_search').val('');
		jQuery('#adminForm').submit();
	});

	// DPE Hack: Below code used to show the agency details

	var element = jQuery('#element').val();

	if (element)
	{
		element = element.split('.').pop();
	}

	jQuery('#jform_com_tjucm_'+element+'_clusterclusterid, #ticketcluster').on('change', function()
	{
		var formData  = {};
		var clusterId = jQuery(this).val();

			formData['clusterId'] = clusterId;

			var promise = multiagencyService.getAgencyInfo(formData);

			promise.fail(
				function(response) {
				}
			).done(function(response) {
				if (!response) {

					jQuery('#agencyInfoLink').removeAttr('onclick');
					jQuery('#agencyInfoLink').hide();
					return false;
				}

				if (response.success) {
					var domain = window.location.href.split('/').slice(0, 3).join('/');
					var viewUrl = domain+'/'+response.data.url;
					jQuery('#agencyInfoLink').show();
					jQuery('#agencyInfoLink').attr("onclick","agencyInfo.openPopup("+JSON.stringify(viewUrl)+")");
				}
			});

	});

	jQuery('#jform_com_tjucm_'+element+'_clusterclusterid, #ticketcluster').trigger('change');
})

// DPE Hack to show agency details popup
var agencyInfo = {
    openPopup: function(url) {
        SqueezeBox.open(url, {
                handler: 'iframe',
                size: {
                    x: window.innerWidth - 200,
                    y: window.innerHeight - 200
                },
                classWindow: 'agency-info-popup tjucm-addprocess-doc',
        });
    },
}
