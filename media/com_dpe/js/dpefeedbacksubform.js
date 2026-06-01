
jQuery(document).ready(function(){ 
	window. getSubFormsFeedback = 	function(evt,url){
		
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

			var selectlable=[];
			if (ListData)
			{
				for(var j=0; j< ListData.length; j++)
				{
					selectlable[j] = jQuery("#"+fieldId).find('option[value="'+ ListData[j]+ '"]').text();
				}
			}
			
			

			jQuery.ajax({
            'type': 'POST',
            'url':  url+"index.php?option=com_tjucm&format=json&task=itemform.getFeedBack",
            'data': {fieldName:fieldIds,fieldValue:ListData,type:'list',lable:selectlable},
            'success': function (data)
            {

            	var output = JSON.parse(data);

            	 var isTagAvail = jQuery("div").hasClass(fieldIds);
                if (isTagAvail)
                {
	                 if (jQuery('.subform-repeatable-group').find('.'+fieldIds).html() != undefined)
	                 { 
	                	 	jQuery('.'+fieldIds).empty();
	                 }
 
                	if((Array.isArray(output.data)) && (output.data != undefined))
					{ 	jQuery('.'+fieldIds).empty();
				        for(var i=0;i<output.data.length;i++){
				        	
				        	if(output.data[i].length != 0)
				        	{jQuery('.'+fieldIds).append(output.data[i]+"<br>"); }
				        }
						
                	}
                	else
                	{
                		jQuery('.'+fieldIds).empty();
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
                 if (jQuery('.subform-repeatable-group').find('.'+radioButtonfield).html() != undefined)
                	 {jQuery('.'+radioButtonfield).empty();}
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

                	if (jQuery('.subform-repeatable-group').find('.'+checkBoxButon).html() != undefined)
                	 {
                	 	jQuery('.'+checkBoxButon).empty();
                	 }

                	jQuery('.'+checkBoxButon).empty();
                	jQuery('.'+checkBoxButon).append(output.data);
                }
            }
          });

		}
		
	}



});
