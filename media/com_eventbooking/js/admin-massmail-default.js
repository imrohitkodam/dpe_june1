(function (document, Joomla) {
    Joomla.submitbutton = function(pressbutton) {
        const form = document.adminForm;
        if (pressbutton === 'cancel') {
            Joomla.submitform( pressbutton );
        } else {
            if (form.event_id.value == 0) {
                alert(Joomla.JText._('EB_CHOOSE_EVENT'));
                form.event_id.focus() ;
                return ;
            }
            Joomla.submitform( pressbutton );
        }
    };
    
    document.addEventListener('DOMContentLoaded', function () {
        var mmTemplateIdField = document.getElementById('mm_template_id');

        if (!mmTemplateIdField) {
            return;
        }

        mmTemplateIdField.addEventListener('change', function () {
            var siteUrl = Joomla.getOptions('siteUrl');
            var templateId = mmTemplateIdField.value;

            if (templateId > 0) {
                Joomla.request({
                    url: siteUrl + '/index.php?option=com_eventbooking&task=mmtemplate.getMMTempalteMessage&id=' + templateId,
                    method: 'POST',
                    onSuccess: function (resp) {
                        Joomla.editors.instances['description'].setValue(resp);
                    },
                    onError: function (error) {
                        alert(error.statusText);
                    }
                });
            }
        });
    });
})(document, Joomla);