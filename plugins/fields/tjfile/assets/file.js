function getFileChange(itemId)
{
	var actualFieldId = itemId.replace('getFileUpload','');
	jQuery("#loader"+actualFieldId).removeClass('d-none');
	jQuery("#loader"+actualFieldId).addClass('d-show');

    var file = event.target.files[0];
    var form_data = new FormData();
    let pluginUrl =
      Joomla.getOptions("system.paths").base +
      "/index.php?option=com_ajax&group=fields&format=json&plugin=validateAndUpload";

    form_data.append("file", file);

    jQuery.ajax({
      type: "POST",
      url: pluginUrl,
      cache: false,
      contentType: false,
      processData: false,
      data: form_data,
      dataType: 'json',
      beforeSend: function () {
        jQuery("#loader"+actualFieldId).removeClass('d-none');
		jQuery("#loader"+actualFieldId).addClass('d-show');
      },
      success: function (data) {
		
      },
      complete: function (data) {console.log( data.responseText);
        var dataResponse = JSON.parse(data.responseText)  

        if(dataResponse.success){
        if (dataResponse.data) {
          jQuery("#"+actualFieldId).val(dataResponse.data);
        }
        jQuery("#getFileUpload"+actualFieldId).prop("disabled", false);
        }
        else if(dataResponse.success==false)
        {
          a
        }
        jQuery("#loader"+actualFieldId).removeClass('d-show');
	    jQuery("#loader"+actualFieldId).addClass('d-none');
      },
    });
}
function removeFile(itemId)
{
	var removeFieldId = itemId.replace('remove_','');
	
	if (confirm("Are you sure, you want to delete this file?")) {
      var deleteFile = jQuery("#remove_"+removeFieldId).val();
      var form_data = new FormData();
      form_data.append("button", deleteFile);

      let pluginUrl =
        Joomla.getOptions("system.paths").base +
        "/index.php?option=com_ajax&group=fields&format=json&plugin=DeleteFile";
      jQuery.ajax({
        type: "POST",
        url: pluginUrl,
        cache: false,
        contentType: false,
        processData: false,
        data: form_data,
        success: function (e) {
          jQuery("#"+removeFieldId).val("");
          jQuery("#uploaded-file_"+removeFieldId).remove();
          jQuery("#remove_"+removeFieldId).attr("disabled", false);
        },
      });
    }
}
