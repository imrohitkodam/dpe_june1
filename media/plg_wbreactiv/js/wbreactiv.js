/**
 * Activation email resend helper
 *
 * @author       ant_author_ant
 * @copyright    ant_copyright_ant
 * @package      ant_package_ant
 * @license      ant_license_ant
 * @version      ant_version_ant
 *
 * ant_current_date_ant
 *
 */

;
(function (_s, window, document, $) {
    "use strict";

    var _wrapperDiv, _button, _status;
    var _resending = false;
    var _type = '';

    /**
     * Fetch a given URL
     */
    function fetch(fetchUrl) {
        $.ajax({
                url: fetchUrl,
                error: handleFetchError,
                success: handleFetchSuccess,
                cache: false
            }
        );
    }

    function handleFetchError(jqXHR, textStatus, errorThrown) {
        var msg = jqXHR.responseText || '';
        console.log('handleFetchError message: ' + msg);
        _resending = false;
        _type == 'form' && _button.text(weeblrApp.resendActivationText);
        _status.html('Error talking to server, see javascript console for more details');
    }

    function handleFetchSuccess(data, textStatus, jqXHR) {
        // decode response, must be json
        try {
            if ('object' != typeof data) {
                data = JSON.parse(data);
            }
            if ('object' == typeof data) {
                var response = data.data[0];
                switch (response.success) {
                    case true:
                        _button.hide();
                        _status.html(response.message);
                        break;
                    default:
                        _type == 'form' && _button.text(weeblrApp.resendActivationText);
                        _type == 'list' && _button.html('');
                        _status.html(response.message);
                        break;
                }
            }
            else {
                console.log('wbreactiv: no object in return, something\'s wrong');
            }
        } catch (e) {
            console.log('Error decoding wbreactiv response: ' + e.message);
            console.debug(data);
            console.debug(e);
        }

        _resending = false;
    }

    function triggerResend() {

        if (_resending) {
            return;
        }

        var userId = $(this).data('userid');
        var fullUrl = _s.resendActivationUrl.replace('{{user_id}}', userId);
        fullUrl = fullUrl.replace('{{type}}', _type);

        var confirmed = confirm(_s.confirmResendActivationText);
        if (confirmed) {
            _resending = true;
            _status = $('#wb-resend-activation-status-' + userId);
            _wrapperDiv = $('#wb-resend-activation-' + userId);
            _button = $('#wb-resend-activation-button-' + userId);

            var spinner = $('<img class="wb-resend-activation" src="'
                + _s.resendActivationBase + 'media/plg_wbreactiv/images/spinner.gif'
                + '"/>');
            _button.html(spinner);
            fetch(fullUrl);
        }
    }

    function processUserForm() {

        _type = 'form';

        var $targetInput = $('input#jform_name');
        if ($targetInput) {
            var wrapperDiv = $('<div id="wb-resend-activation-' + _s.confirmResendActivationUserId + '" class="wb-resend-activation"><button id="wb-resend-activation-button-' + _s.confirmResendActivationUserId + '" data-userid="' + _s.confirmResendActivationUserId + '" type="button" class="btn btn-warning wb-resend-activation-button">'
                + _s.resendActivationText + '</button>'
                + '<span id="wb-resend-activation-status-' + _s.confirmResendActivationUserId + '" class="wb-resend-activation-status"></span>'
                + '</div>');
            $targetInput.after(wrapperDiv);
            $('#wb-resend-activation-button-' + _s.confirmResendActivationUserId).click(triggerResend);
        }
        else {
            console.log('Sibling field not found');
        }
    }

    function processUsersList() {
        _type = 'list';
        var unpublishIcons = $('#userList span.icon-unpublish');
        var nonActivatedUsers = [];
        $.each(unpublishIcons, function (index, icon) {
            var a = $(icon).parent();
            var onclick = $(icon).parent().attr('onclick');
            if (onclick.indexOf('users.activate') > -1) {
                var td = a.parent();
                var $siblings = td.siblings();
                var $id = $siblings.last();
                nonActivatedUsers.push({'link': a, 'id': parseInt($id.text())});
            }
        });
        $.each(nonActivatedUsers, function (index, record) {

            var wrapperDiv = $('<div id="wb-resend-activation-' + record.id + '" class="wb-resend-activation-list"><span id="wb-resend-activation-button-' + record.id + '" data-userid="' + record.id + '" class="btn btn-warning wb-resend-activation-icon icon-envelope hasTooltip"'
                + ' title="' + _s.resendActivationText + '" />'
                + '<span id="wb-resend-activation-status-' + record.id + '" class="wb-resend-activation-status-list"></span>'
                + '</div>');
            record.link.after(wrapperDiv);
            $('#wb-resend-activation-button-' + record.id).click(triggerResend);
        });
    }

    function onReady() {
        try {
            var url = window.location.href;
            if (url.indexOf('view=user') > -1 && url.indexOf('layout=edit') > -1) {
                _type = 'form';
                processUserForm();
            } else if (url.indexOf('view=users') > -1) {
                _type = 'list';
                processUsersList();
            }
        }
        catch (e) {
            console.log('Error doing sub request: ' + e.message);
        }
    }

    $(document).ready(onReady);

    return _s;

})
(window.weeblrApp = window.weeblrApp || {}, window, document, jQuery);
