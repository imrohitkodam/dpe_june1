var Docman = Docman || {};

kQuery(function($){
    var grid = $('.k-js-grid-controller'),
        controller = grid.data('controller'),
        delete_button = $('#toolbar-delete'),
        message = Koowa.translate('You cannot delete a category while it still has documents'),
        countDocuments = function() {
            var count = 0;

            Koowa.Grid.getAllSelected().each(function() {
                count += parseInt($(this).data('document-count'), 10);
            });

            return count;
        };

    controller.toolbar.find('a.toolbar').ktooltip({
        placement: 'bottom'
    });

    grid.on('k:afterValidate', function() {
        if (countDocuments()) {
            delete_button.addClass('k-is-disabled');
            delete_button.ktooltip('destroy');
            delete_button.ktooltip({title: message, placement: 'bottom'});
        }
    });

    Docman.Dialog = Koowa.Class.extend({
        initialize: function(options) {
            this.supr();

            options.view = $(options.view);
            options.button = $(options.button, options.view);
            options.open_button = $(options.open_button);

            this.setOptions(options);
            this.attachEvents();
        },
        attachEvents: function() {
            var self = this;

            if (this.options.open_button) {
                this.options.open_button.click(function(event) {
                    event.preventDefault();

                    self.show();
                });
            }

            if (this.options.view.find('form')) {
                this.options.view.find('form').submit(function(event) {
                    event.preventDefault();

                    self.submit();
                });
            }
        },
        show: function() {
            var options = this.options,
                count = Koowa.Grid.getAllSelected().length;

            if (options.open_button.hasClass('k-is-unauthorized') || !count) {
                return;
            }

            $.magnificPopup.open({
                items: {
                    src: $(options.view),
                    type: 'inline'
                }
            });
        },
        hide: function() {
            $.magnificPopup.close();
        },
        submit: function() {
            var controller = $('.k-js-grid-controller').data('controller'),
                context = {},
                data = this.getData();

            if (data && Koowa.Grid.getAllSelected().length) {
                context.validate = true;
                context.data     = data;
                context.data[controller.token_name] = controller.token_value;
                context.action = 'edit';

                controller.trigger('execute', [context]);
            }
        },
        getData: function() {
            return null;
        }
    });

    Docman.BatchDialog = Docman.Dialog.extend({
        initialize: function(options) {
            this.supr(options);
        },
        attachEvents: function() {
            this.supr();
        },
        getData: function() {
            var form_data = $('.k-js-batch-form').serializeArray(),
                data = {};
            name_check = /\[\]/g;
            can_send = false;

            $.each(form_data, function(i, field) {
                var name = field.name;

                if (!field.value || field.value === '') {
                    return;
                }

                can_send = true;

                if (name.search(name_check) === -1) {
                    data[name] = field.value;
                } else {
                    name = name.replace(name_check, '');

                    if (!data[name]) {
                        data[name] = [];
                    }

                    data[name].push(field.value);
                }
            });

            if (can_send) {
                return data;
            }

            return null;
        }
    });

    $(function () {

        new Docman.BatchDialog({
            view: '#category-batch-modal',
            button: '.k-button--primary',
            open_button: '#toolbar-batch'
        });
    });

});