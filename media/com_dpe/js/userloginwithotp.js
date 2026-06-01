jQuery(document).ready(function() {
	

			jQuery('form').on('submit', function(e) {
      e.preventDefault(); // Prevent the form from submitting initially

    var username = encodeURIComponent(jQuery('#username').val());

    jQuery.ajax({
        url: Joomla.getOptions('system.paths').base + "/index.php?option=com_dpe&task=users.checkUserValidation",
        type: 'POST',
        data: {
            'username': username
        },
        dataType: "json",
        headers: {'X-CSRF-Token': Joomla.getOptions('csrf.token', '')},
        success: function(response) {
            if (response.data == true) {
                // If the response is valid, allow the form to be submitted
                var form = jQuery('form')[0];
          						form.submit();
            } else {
                // Check if the user is not a DPE admin and no login option is selected
                    if (!jQuery('#loginwithlink').is(':checked') && !jQuery('#loginwithotp').is(':checked')) {
                        alert('Please select at least one login option.');

                    }else
                    {
                    	var form = jQuery('form')[0];
          						form.submit();
                    }
               
                // Do not submit the form
                return false;
            }
        }
    });
});
		jQuery('#loginwithlink').click(function(){
			if(jQuery('#loginwithlink').is(':checked')){
				jQuery('#loginwithotp').prop('checked', false);
				 checkCredential();
				jQuery('#loginbtn').prop('disabled',false);
				jQuery('#getotp').addClass('hide');

			}
		})
jQuery('#loginwithotp').click(function(){

				if(jQuery('#loginwithotp').is(':checked')){
					jQuery('#loginwithlink').prop('checked', false);
					jQuery('#loginbtn').prop('disabled',true);
					checkCredential();
					
				}else
				{
					jQuery('#loginbtn').prop('disabled',false);
					jQuery('#getotp').addClass('hide');
				}

			})
})
		function openPopupForOtp(msg, type)
		{  

			jQuery('#loginbtn').prop('disabled',true);

			var url = Joomla.getOptions('system.paths').base +'/index.php?option=com_dpe&view=import&layout=useloginwithotp&tmpl=component&fromlogin=otp&msg='+msg+'&type='+type;
			var wwidth = jQuery(window).width() -850;
			var wheight = jQuery(window).height() - 350;
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
				onOpen: function() {
					jQuery('#loader').show();
            // Monitor when the iframe content is fully loaded
            jQuery('iframe').on('load', function() {
                // Hide the loader once the iframe content is fully loaded
                jQuery('#loader').hide();
              });
          },
          onClose: function()
          {
          	if(jQuery('#loginwithotp').is(':checked')){

          		// jQuery('#loginbtn').prop('disabled',false);
          		jQuery('#getotp').removeClass('hide');
          	}

          	if(jQuery('#otpsuccess').val() == 'success')
          	{
          		var form = jQuery('form')[0];
          		form.submit();
          	}
          	
          }

        });
		}

		function checkCredential()
		{
			var username = encodeURIComponent(window.parent.jQuery('#username').val());
			var password =  encodeURIComponent(window.parent.jQuery('#password').val());

			if(username.length < 5)
			{
				alert(Joomla.JText._('COM_DPE_USERNAME_REQUIRED'));
				jQuery('#loginwithotp').prop('checked', false);
				jQuery('#loginwithlink').prop('checked', false);
				jQuery('#loginbtn').prop('disabled',false);
				jQuery('#getotp').addClass('hide');
				return false;
			}
			if(password.length < 1)
			{
								alert(Joomla.JText._('COM_DPE_PASSWORD_REQUIRED'));

				jQuery('#loginbtn').prop('disabled',false);
				jQuery('#loginwithotp').prop('checked', false);
				jQuery('#loginwithlink').prop('checked', false);
				jQuery('#getotp').addClass('hide');
				return false;
			}
			jQuery('#getotp').removeClass('hide');
		}

jQuery(document).ready(function() {
    jQuery(document).on("contextmenu", function(e) {
       // e.preventDefault(); // Prevent the default context menu from appearing
    });
})

function getOtp()
{
    var username = encodeURIComponent(jQuery('#username').val());
    var password =  encodeURIComponent(jQuery('#password').val());
    var returnid = jQuery('#return').val();

    jQuery.ajax({
      url: Joomla.getOptions('system.paths').base + "/index.php?option=com_dpe&task=users.sendOtpToUser",
      type: 'POST',
      data:{
       'username': username,'password':password
   },
   dataType:"json",
   headers: {'X-CSRF-Token': Joomla.getOptions('csrf.token', '')}, 
   success: function (response) {

      var msg = response.data.msg;
      var type = response.data.type;

     if(response.data.action == true)
     {
     	 openPopupForOtp(msg, type);
     }
     else{
      messageDisplay(msg, type);

     }
   }
});
}

function messageDisplay(msg, type){

  jQuery('<div id="system-message-container"></div>').appendTo('#system-message-container');
  Joomla.renderMessages({[type] : [msg]}); 
  setTimeout(function() {
           jQuery('joomla-alert').fadeOut('slow', function() {
                $(this).remove();
            });
        }, 10000); 
}