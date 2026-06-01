var logticket = {

	afterSaveLinkFieldUpdate: function(ucmFormId)
	{
		var ucmData = ucmFormId.split(",");
		setTimeout(function(){jQuery.ajax({
			url: Joomla.getOptions('system.paths').base + "/index.php?option=com_dpe&format=json&task=tjucm.getLogFieldValue",
			type: "POST",
			dataType: 'json',
			data: jQuery.param({ 'ucmId':ucmData[0], 'fieldId':ucmData[1]}),
			success:function(response)
			{

				jQuery('#jform_'+response.data.fieldId).val(response.data.fieldValue);
				jQuery('#jform_'+response.data.fieldId).text(response.data.fieldValue);
				jQuery('#jform_'+response.data.fieldId).attr('readonly','readonly');

			}
		})
	}, 2000);
	},
	addTicketfromUcm: function(ucmFieldValue)
	{
		var ucmFieldValue = JSON.parse(ucmFieldValue);
		var toUser        = jQuery("#jform_" + ucmFieldValue.toUser + " :selected").text();
		var toUserval     = jQuery("#jform_" + ucmFieldValue.toUser + " :selected").val();
		var subject       = jQuery("#jform_"+ucmFieldValue.subject).val();
		var message       = jQuery("#jform_"+ucmFieldValue.message).val();
		var linkField	  = jQuery("#jform_"+ucmFieldValue.linkField).val();
		var clusterId     = jQuery("#jform_"+ucmFieldValue.clusterId).val();
		var lableTxt      = '';

		if (!toUserval || !subject || linkField  || !clusterId || !message)
		{	
			if (!clusterId)
			{
				lableTxt = jQuery("#jform_"+ucmFieldValue.clusterId+'-lbl').text().replace(/\*/g,'');
				jQuery('#addTicketMessage').text(lableTxt + Joomla.Text._('COM_DPE_TICKET_FIELD_REQUIRED'));
				jQuery('#addTicketMessage').css('color','red');
			}

			else if (!toUserval)
			{
				lableTxt = jQuery("#jform_"+ucmFieldValue.toUser+'-lbl').text().replace(/\*/g,'');
				jQuery('#addTicketMessage').text(lableTxt+ Joomla.Text._('COM_DPE_TICKET_FIELD_REQUIRED'));
				jQuery('#addTicketMessage').css('color','red');
			}
			else if (!subject)
			{
				lableTxt = jQuery("#jform_"+ucmFieldValue.subject+'-lbl').text().replace(/\*/g,'');
				jQuery('#addTicketMessage').text(lableTxt+ Joomla.Text._('COM_DPE_TICKET_FIELD_REQUIRED'));
				jQuery('#addTicketMessage').css('color','red');
			}
			else if (linkField) 
			{
				jQuery('#addTicketMessage').text(Joomla.Text._('COM_DPE_TICKET_ALREADY_EXIST'));
				jQuery('#addTicketMessage').css('color','red');
			}
			
			else if (!message)
			{
				lableTxt = jQuery("#jform_"+ucmFieldValue.message+'-lbl').text().replace(/\*/g,'');
				jQuery('#addTicketMessage').text(lableTxt + Joomla.Text._('COM_DPE_TICKET_FIELD_REQUIRED'));
				jQuery('#addTicketMessage').css('color','red');
			}

			jQuery('#addTicketMessage').removeClass('d-none');
			setTimeout(function() {
				jQuery('#addTicketMessage').addClass('d-none');
			}, 3000);
			return false;

		}
		else
		{	

			jQuery('#addTicket').prop("disabled", true);
			var client = jQuery('#ucm-client').val();
			var fieldData = {};
			var formData = {};

			jQuery.each(ucmFieldValue, function(key, value) {

				fieldData[value]= jQuery("#jform_"+value).val();
			});

			formData['content_id'] 	= jQuery("#recordId").val();
			formData['client']     	= client;
			formData['created_by']	= jQuery("input[name='jform[checked_out]']").val();
			formData['clusterId']   = clusterId;
			formData['toUser']     	= toUser;
			formData['subject']     = subject;
			formData['message']     = message;
			formData['toUserId']    = toUserval;


const formDatas = JSON.stringify(formData);
const fieldDatas = JSON.stringify(fieldData);

localStorage.setItem('ticket', formDatas);
localStorage.setItem('fieldDatas', fieldDatas);
localStorage.setItem('client', client);
localStorage.setItem('ucmFieldValue',ucmFieldValue.linkField);

url=Joomla.getOptions('system.paths').base+'/index.php?option=com_rsticketspro&view=submit&tmpl=component&clientType='+client; 
var wwidth = jQuery(window).width() -250;
        var wheight = jQuery(window).height() - 150;
 		SqueezeBox.open(url, {
            handler: 'iframe',
            closable: true,
            size: {
                x: wwidth,
                y: wheight
            },
            sizeLoading: {
                x: wwidth,
                y: wheight
            },
            classWindow: '',
            onClose: function()
			{
						localStorage.removeItem('formData');
						localStorage.removeItem('fieldDatas');
						localStorage.removeItem('client');
						localStorage.removeItem('ticket');
						window.parent.jQuery('#addTicket').prop('disabled',false);
			}
          
        });
 

			jQuery('#addTicketMessage').addClass('d-none');
		}

	},

	saveTicketInLinkField: function(event)
	{ event.preventDefault();
		var ucmformDatas 	= JSON.parse(localStorage.getItem('ticket'));
		var fieldDatas 	= JSON.parse(localStorage.getItem('fieldDatas'));
		var client 		= localStorage.getItem('client');
		jQuery('#ucmpopupBtn').prop('disabled',true);


		var formData = new FormData(jQuery("#adminForm")[0]);

			// Append additional form data if needed
			formData.append('data', JSON.stringify(fieldDatas));
			formData.append('client', client);
			formData.append('ucmformDatas', JSON.stringify(ucmformDatas));
		formData.append('task', 'rsticket.addTicketFromUcmLog');

		var editorContent = tinymce.get('jform_message').getContent();
			formData.append('jform[message]',editorContent);


			// Append the file
			var fileInput = jQuery('#jform_files')[0].files[0];
			formData.append('file', fileInput);

		
		jQuery.ajax({
				url: Joomla.getOptions('system.paths').base + "/index.php?option=com_dpe&task=rsticket.addTicketFromUcmLog&format=json",
				type: "POST",
				dataType: 'json',
				 data: formData,
			    contentType: false,
			    processData: false,
				
				success:function(response)
				{ 
					window.onbeforeunload = null;
   								
   								
					if (response.data.success)
					{		

					  window.parent.jQuery("#jform_"+localStorage.getItem('ucmFieldValue')).val(response.data.url);
					 // window.parent.jQuery("#jform_"+localStorage.getItem('ucmFieldValue')).hide();
					  window.parent.jQuery(".ticketbtnclass").hide();
					  window.parent.jQuery('#addTicket').addClass('d-none');

					  // Create the new div with the provided HTML
					var newDiv = jQuery(					    
					    '<div class="col-sm-5 mb-10  fw-bold"><a href="'+response.data.url+'" target="_blank">Go to Ticket </a> </div>');
					// Insert the new div before a specific div with the class 'existingDiv'
					 window.parent.jQuery(newDiv).insertBefore("#jform_"+localStorage.getItem('ucmFieldValue'));


						localStorage.removeItem('formData');
						localStorage.removeItem('fieldDatas');
						localStorage.removeItem('client');
						localStorage.removeItem('ticket');
						localStorage.removeItem('ucmFieldValue');
						jQuery('<div id="system-message-container"></div>').insertBefore('.addticketLogpop');
						Joomla.renderMessages({
			            'success': [response.data.msg]
			        		});
						jQuery("html, body").animate({
				            scrollTop: 0
				        }, 500);
					        
						setTimeout(function(){            
							  window.parent.SqueezeBox.close();
							 },3000);

						
					}else{
						
						jQuery('#ucmpopupBtn').prop('disabled',false);
						jQuery('<div id="system-message-container"></div>').insertBefore('.addticketLogpop');
						Joomla.renderMessages({
			            'warning': [response.data.msg]
			        		});
						jQuery("html, body").animate({
				            scrollTop: 0
				        }, 500);
					}
				}
			})
			
					
	}


}

