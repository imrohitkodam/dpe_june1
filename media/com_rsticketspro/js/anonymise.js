jQuery(function($){
    $('#rst_anonymise_button').click(function(){
        if (!confirm(Joomla.JText._('COM_RSTICKETSPRO_ARE_YOU_SURE_YOU_WANT_TO_ANONYMISE')))
        {
            return false;
        }

        var $button = $(this);
        $button.prop('disabled', true).addClass('disabled');

        var url = Joomla.getOptions('system.paths').base + '/index.php';
        var token = Joomla.getOptions('csrf.token');
        var data = {
            'option': 'com_rsticketspro',
            'task': 'removedata.process',
            'id': $('#jform_id').val()
        };
        data[token] = 1;
        $.post(url, data, function(response){
            var messages = JSON.parse(response);
            Joomla.renderMessages(messages);
            $button.prop('disabled', false).removeClass('disabled');
        });
    });
});