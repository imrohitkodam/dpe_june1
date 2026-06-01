jQuery(document).ready(function() {
    getDataChecklist();
});

jQuery(document).on('change', '.ucm-checklist-cluster', function(e) {
    getDataChecklist();
});

function getDataChecklist() {
    var container = jQuery('.ucm-module-checklist-container');
    var ucmType = container.data("ucm-type");
    container.empty();

    container.addClass('isloading');

    jQuery.ajax({
        url: Joomla.getOptions('system.paths').base + "/index.php?option=com_tjucm",
        type: "POST",
        data: {
            view: 'itemform',
            format: 'json',
            client: ucmType,
            task: 'itemform.display',
            cluster_id: jQuery('.ucm-checklist-cluster').val()
        },
        dataType: 'json'
    }).done(function(result) {

		if (result.data !== null)
		{
			// Append Form HTML to Page
			container.html(result.data.html);
			if (result.data.script)
			{
				try
				{
					eval(result.data.script)
				}catch (err)
				{
					console.log(err);
				};
			}

			// Hard coded due to the UCM bug
			jQuery('#item-form .nav-tabs li a').first().click();
			container.removeClass('isloading');
		}
		else
		{
			// Show error message
			container.html(result.message);
		}



		/*
        if (result.data.script) {
            try {
                eval(result.data.script)
            } catch (err) {
                console.log(err);
            };
        }
        */

    }).fail(function(result) {
        container.html(Joomla.JText._("COM_DPE_INTERACTION_AJAX_ERROR"));
        container.removeClass('isloading');
        console.log(result);
    }).always(function() {
        container.removeClass('isloading');
    });
}

jQuery(document).on('click', '.print-ucm-checklist', function(e) {
    window.print();
});

jQuery(document).on('click', '.checklist-close-alert', function(e) {
    jQuery('.checklist-help').addClass('hide');
});
