(function (document, Joomla) {
    Joomla.submitbutton = function(pressbutton)
    {
        if (pressbutton === 'export' && document.registrantsExportForm)
        {
            var variables = ['filter_search', 'filter_from_date', 'filter_to_date', 'filter_event_id', 'filter_published', 'filter_checked_in'];
            var variable, filterElement;

            for (i = 0 ; i < variables.length; i++)
            {
                variable = variables[i];

                filterElement = document.getElementById(variable);

                if (filterElement) {
                    document.getElementById('export_' + variable).value = filterElement.value;
                }
            }

            var cids = [];

            document.querySelectorAll('input[name="cid[]"]:checked').forEach(function (checkbox) {
                cids.push(checkbox.value);
            });

            document.getElementById('export_cid').value = cids.join(',');

            Joomla.submitform(pressbutton, document.getElementById('registrantsExportForm'));

            return;
        }
        else if (pressbutton === 'add')
        {
            const form = document.adminForm;

            if (form.filter_event_id.value == 0)
            {
                alert(Joomla.JText._('EB_SELECT_EVENT_TO_ADD_REGISTRANT'));
                form.filter_event_id.focus();

                return;
            }
        }
        else if(pressbutton === 'registrant.batch_mail')
        {
            const form = document.adminForm;

            if (form.subject.value === '')
            {
                alert('Please enter email subject');
                form.subject.focus();

                return;
            }
        }

        Joomla.submitform( pressbutton );
    }

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
                        Joomla.editors.instances['message'].setValue(resp);
                    },
                    onError: function (error) {
                        alert(error.statusText);
                    }
                });
            }
        });
    });
})(document, Joomla);