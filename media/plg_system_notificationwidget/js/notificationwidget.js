(function(jQuery) {
    jQuery.fn.notificationwidget = function(options) {
    }
})(jQuery);

var notificationwidget = {
    openPopup: function(url) {
        var wwidth = jQuery(window).width() - 50;
        var wheight = jQuery(window).height() - 50;

        SqueezeBox.open(url, {
            handler: 'iframe',
            closable: false,
            size: {
                x: wwidth,
                y: wheight
            },
            sizeLoading: {
                x: wwidth,
                y: wheight
            },
            classWindow: 'timelog-activities-popup todo-popup',
        });
    }
}
