kQuery(function ($) {
    var buttons = {
        '.k-js-refresh-license': {
            tooltip: Koowa.translate('Refreshing license…'),
            payload: {
                _action: 'refresh_license',
            },
            error: 'Could not refresh support license',
            done: () => {
                window.location.reload(true);
            }
        }
    }

    for (let [button, data] of Object.entries(buttons)) {

        let actionButton = $(button);
        let spinner = $('<span class="k-loader" style="display: none">Loading…</span>');

        actionButton.append(spinner);

        actionButton.ktooltip({
            title: data.tooltip,
            placement: 'bottom',
            delay: {show: 200, hide: 50},
            trigger: 'manual',
            container: '.k-ui-namespace'
        });

        actionButton.click(function (event) {
            event.preventDefault();

            $.ajax({
                method: 'post',
                url: $('.k-js-form-controller').attr('action'),
                dataType: 'json',
                data: data.payload,
                beforeSend: function () {
                    actionButton.addClass('k-is-disabled disabled').ktooltip('show');
                    spinner.css('display', '');
                }
            }).fail(function() {
                alert(data.error);
            }).done(function() {
                data.done ? data.done() : null;
            }).always(function () {
                actionButton.append('<span class="k-icon-check" style="color: green" aria-hidden="true"></span>');
                actionButton.removeClass('k-is-disabled disabled').ktooltip('hide');
                spinner.css('display', 'none');
                setTimeout(function () {
                    var check = actionButton.children('.k-icon-check');
                    check.hide(function() {
                        check.remove();
                    });
                }, 2000);
            });
        })
    }
});