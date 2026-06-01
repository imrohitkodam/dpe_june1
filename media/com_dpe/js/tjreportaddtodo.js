	function getalluser(e)
	{ 
		if (jQuery(e).is(':checked')) 
		{ 
			jQuery('#task').val('users.getUserCount');
			jQuery('#filters_allUser').val('add_all_users_with_filters');
			jQuery("input[name='cid[]']:checkbox").prop('checked',false);
			jQuery("input[name='checkall-toggle']:checkbox").prop('checked',false);
			var formData    = jQuery('.manage-reports').serialize();
			var params      = {};
			var baseurl = window.location.href;
			baseurl =  Joomla.getOptions("system.paths").root;
				jQuery.ajax({
				type:'POST',
				url:window.location.origin + baseurl + "/index.php?option=com_dpe",
				data: formData,
				async: false,
				success:function(response)
						{	var responsedata = jQuery.parseJSON(response);

							{ 
								jQuery('<input>').attr({
							    type: 'hidden',
							    name: 'juserCount',
							    value: responsedata.data.count
								}).appendTo('form');

								  jQuery('<input>').attr({
								    type: 'hidden',
								    name: 'assigned_to_users',
								    value: responsedata.data.userIds
									}).appendTo('form');
							}
							
							
						}
			});
		}
		else
		{
			jQuery('#task').val('');
		    jQuery('#filters_allUser').val('0');
		}
	}
	function check()
	{		
		openAddtodo();
		return true;	
	}
	function openAddtodo()
	{ 
			if (jQuery('#filters_allUser').prop('checked') == true || 
				jQuery("input[name='cid[]']").is(':checked') == true) 
			{
				openPopup(Joomla.getOptions('system.paths').base+'/index.php?option=com_jlike&tmpl=component&task=recommendationform.edit&layout=editreport');
			}
			else
			{
				alert(Joomla.JText._('PLG_SYSTEM_ADDTODO_BTN_VALIDATION'));
			}
	}
	function uncheck(f)
	{ 
		if (jQuery('#filters_allUser').is(':checked'))
		{
			jQuery('#filters_allUser').val('');
			jQuery("#filters_allUser").prop('checked',false);
		}
	}
function openPopup(url) {
        var wwidth = jQuery(window).width() - 50;
        var wheight = jQuery(window).height() - 50;

        SqueezeBox.open(url, {
            handler: 'iframe',
            closable: false,
            size: {
                x: wwidth,
                y: wheight
            },
            sizeLoading: {
                x: wwidth,
                y: wheight
            },
            classWindow: 'timelog-activities-popup todo-popup',
        });
    }