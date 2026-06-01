jQuery(document).on('click', '#submit-records', function(e) {

            var el = jQuery(this);
            var formData = jQuery('.assign-certificates').serialize();
            
            console.log(formData);

			var ajaxTime= new Date().getTime();
            jQuery.ajax({
                url: Joomla.getOptions('system.paths').base + '/index.php?option=com_tjcertificate&task=trainingrecords.addCertificates&format=json',
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
					var totalTime = new Date().getTime()-ajaxTime;

					console.log(response);
					console.log("total timing: " + totalTime);
                },
            });   
    });

