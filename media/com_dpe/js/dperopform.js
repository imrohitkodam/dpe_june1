jQuery(document).ready(function(){ 
	jQuery(document).on('subform-row-add', function(event, row){
		var PrvFieldName     = event.detail.row.previousSibling.getAttribute('data-group');
		var CurrentFieldName = event.detail.row.getAttribute('data-group');

		if (CurrentFieldName.includes('com_tjucm_rop_locationsystem'))
		{
			var PrevFieldCounter = parseInt(event.detail.row.previousSibling.getAttribute('data-group').match(/[0-9]+/)[0]);
			var CurrFieldCounter = parseInt(CurrentFieldName.match(/[0-9]+/)[0]);
			var PreElementValue = jQuery('#jform_com_tjucm_rop_locationsystem__com_tjucm_rop_locationsystem'+PrevFieldCounter+'__com_tjucm_ropdataflow_contentid').val();
			jQuery('#jform_com_tjucm_rop_locationsystem__com_tjucm_rop_locationsystem'+CurrFieldCounter+'__com_tjucm_ropdataflow_dataflowstepdescription').focus();
			jQuery('#jform_com_tjucm_rop_locationsystem__com_tjucm_rop_locationsystem'+CurrFieldCounter+'__com_tjucm_ropdataflow_dataflowstepdescription').attr('data-prev-element-id', PreElementValue);
		}
	});

	jQuery(document).on('subform-row-remove', function(event, row)
	{
		var CurrentFieldName = event.detail.row.getAttribute('data-group');

		if (CurrentFieldName.includes('com_tjucm_rop_locationsystem'))
		{
			var CurrFieldCounter = parseInt(CurrentFieldName.match(/[0-9]+/)[0]);
			var CurrElementValue = jQuery('#jform_com_tjucm_rop_locationsystem__com_tjucm_rop_locationsystem'+CurrFieldCounter+'__com_tjucm_ropdataflow_contentid').val();
			var recordId         = jQuery('#recordId').val();

			if (!CurrElementValue)
			{
				return false;
			}

			// Update parent IDs
			jQuery.ajax({
					url: Joomla.getOptions("system.paths").root + '/index.php?option=com_dpe&task=tjucm.ropSubformRemove&format=json',
					type: "POST",
					data: {'ucmDataId':CurrElementValue},
					dataType: 'json',
					complete: function()
					{
					}
				}).done(
				function(response)
				{
					console.log(response.data);

					if( typeof response.data !== 'undefined' )
					{
						jQuery.ajax({
								url: Joomla.getOptions("system.paths").root + "/index.php?option=com_dpe&task=tjucm.getDynamicTree&format=json",
								type: "POST",
								data: {'ucmId':recordId},
								dataType: 'json',
								complete: function()
								{
								}
							}).done(
							function(response)
							{
								// On repose
								document.getElementById("DragDropHTMLCover").innerHTML = response.data.html;
								jQuery("#DragDropHTMLCover").find("script").each(function(){
									 eval(jQuery(this).text());
								});

								// Show Message on Data flow Relation Update
								document.querySelector("#SuccessMsg").removeClass("hide");
								setTimeout(function(){
									document.querySelector("#SuccessMsg").addClass("hide");
								},1000);

							}).fail(function(result) {
								// console.log(result);
							})
							.always(function() {
								// el.removeClass('btn-loading');
							});
					}
				}).fail(function(result) {
					// console.log(result);
				})
				.always(function() {
					// el.removeClass('btn-loading');
				});
		}
	});
	
});

jQuery(document).ready(function(){ 

	window. getSubFormFeedback = 	function(evt,url){
		
		var radioButton   = selectorId = jQuery(evt.target).attr('id');
		var checkBoxButon = jQuery(evt.target).attr('id');
		var buttonType    = jQuery(evt.target).attr('type');

		// Only call for tjlist field 
		if ((evt.target.tagName == 'SELECT') ) 
		{  
			var	fieldId = selectorId.replace('_chzn','');
					fieldIds = fieldId.replace('jform_','');
	    	var isTagPresent = jQuery("div").hasClass(fieldIds);
			if (isTagPresent == false)
			{
				jQuery( "<div class="+fieldIds+"></div>" ).insertAfter( jQuery( "#"+selectorId+"_chzn" ) );
			}

					var ListData = jQuery("#"+fieldId).chosen().val();
					jQuery.ajax({
            'type': 'POST',
            'url':  url+"index.php?option=com_tjucm&format=json&task=itemform.getFeedBack",
            'data': {fieldName:fieldIds,fieldValue:ListData},
            'success': function (data)
            {

            	var output = JSON.parse(data);

            	 var isTagAvail = jQuery("div").hasClass(fieldIds);
                if (isTagAvail)
                { 
                	jQuery('.'+fieldIds).empty();
                	if(Array.isArray(output.data))
					{  
						jQuery('.'+fieldIds).append(output.data.join("<br>"));
                	}
                	else{
                		jQuery('.'+fieldIds).append(output.data);
                	}
                	
                }
            }
          });	
			
	} // // Only call for radio field 
	else if ((radioButton != undefined) && (buttonType == 'radio'))
	{
			var	radioButtonfield = radioButton.slice(0,-1);

			radioButtonfield = radioButtonfield.replace('jform_','');
			var isTagPresent = jQuery("div").hasClass(radioButtonfield);

			if (isTagPresent == false)
			{	
				jQuery( "<div class="+radioButtonfield+"></div>" ).insertAfter( jQuery( "#jform_"+radioButtonfield ));
			}

			var ListData = jQuery("#"+radioButton).val();
			jQuery.ajax({
            'type': 'POST',
            'url':  url +"index.php?option=com_tjucm&format=json&task=itemform.getFeedBack",
            'data': {fieldName:radioButton,fieldValue:ListData,type:'radio'},
            'success': function (data)
            {
            	 var output = JSON.parse(data);
            	 var isTagAvail = jQuery("div").hasClass(radioButtonfield);
                if (isTagAvail)
                {   
                	jQuery('.'+radioButtonfield).empty();
                	jQuery('.'+radioButtonfield).append(output.data);
                }
            }
          });
	}// Only call for checkBox field 
	else if ((checkBoxButon != undefined) && (buttonType == 'checkbox'))
	{

					checkBoxButon = checkBoxButon.replace('jform_','');

					var isTagPresent = jQuery("div").hasClass(checkBoxButon);

					if (isTagPresent == false)
					{	
						jQuery( "<div class="+checkBoxButon+"></div>" ).insertAfter( jQuery( "#jform_"+checkBoxButon ));
					}
					var ListData = '';
					if (jQuery("#jform_"+checkBoxButon).is(":checked"))
					{
						 ListData = jQuery("#jform_"+checkBoxButon).val();
					}

					if (!ListData)
						{
							 ListData = 2;
						}

			jQuery.ajax({
            'type': 'POST',
            'url':  url +"index.php?option=com_tjucm&format=json&task=itemform.getFeedBack",
            'data': {fieldName:checkBoxButon,fieldValue:ListData,type:'checkbox'},
            'success': function (data)
            {
            	var output = JSON.parse(data);
            	 var isTagAvail = jQuery("div").hasClass(checkBoxButon);
                if (isTagAvail)
                {
                	jQuery('.'+checkBoxButon).empty();
                	jQuery('.'+checkBoxButon).append(output.data);
                }
            }
          });

		}
		
	}



});
