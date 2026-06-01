(function (document, Joomla) {
    Joomla.submitbutton = function (pressbutton) {
        const form = document.adminForm;

        if (pressbutton === 'cancel') {
            Joomla.submitform(pressbutton);
        } else {
            if (form.name.value === '') {
                alert(Joomla.JText._('EB_ENTER_FIELD_NAME'));
                form.name.focus();
                return;
            }

            if (form.title.value === '') {
                alert(Joomla.JText._('EB_ENTER_FIELD_TITLE'));
                form.title.focus();
                return;
            }

            Joomla.submitform(pressbutton);
        }
    };

    function setFieldValidationRules() {
        const form = document.adminForm;
        const validationRulesInput = form.validation_rules;

        if (form.name.value === 'email') {
            //Hard code the validation rule for email
            validationRulesInput.value = 'validate[required,custom[email],ajax[ajaxEmailCall]]';
            return;
        }

        const validateRules = Joomla.getOptions('validateRules');
        const validateType = parseInt(form.datatype_validation.value);
        const required = form.required.value;

        let rules = [];
        let validationString = '';

        if (required === '1') {
            rules.push('required');
        }

        if (validateRules[validateType].length) {
            rules.push(validateRules[validateType]);
        }

        if (rules.length > 0) {
            validationString = 'validate[' + rules.join(',') + ']';
        }

        validationRulesInput.value = validationString;
    }

    document.addEventListener('DOMContentLoaded', function () {
        const form = document.adminForm;
        form.name.addEventListener('change', function () {
            let fieldName = form.name.value;
            fieldName = fieldName.replace('eb_', '');
            fieldName = fieldName.replace(/[^a-zA-Z0-9_]*/ig, '');
            form.name.value = fieldName;
        });

        document.getElementById('required').addEventListener('click', setFieldValidationRules);

        form.datatype_validation.addEventListener('change', setFieldValidationRules);

        const dependOnFieldId = document.getElementById('depend_on_field_id');

        dependOnFieldId.addEventListener('change', function () {
            let siteUrl = Joomla.getOptions('siteUrl');
            let fieldId = dependOnFieldId.value;

            if (fieldId > 0) {
                Joomla.request({
                    url: siteUrl + '/index.php?option=com_eventbooking&view=field&format=raw&field_id=' + fieldId,
                    method: 'POST',
                    onSuccess: function (resp) {
                        document.getElementById('options_container').innerHTML = resp;
                        document.getElementById('depend_on_options_container').style.display = '';
                    },
                    onError: function (error) {
                        alert(error.statusText);
                    }
                });
            } else {
                document.getElementById('options_container').innerHTML = '';
                document.getElementById('depend_on_options_container').style.display = 'none';
            }
        });
    });
})(document, Joomla);