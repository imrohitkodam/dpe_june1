(function(jQuery) {
    jQuery.fn.notificationlistwidget = function(options) {
        var actualurl= window.location.href.split('/').slice(0, 3).join('/');
        jQuery('<a id="notificationlistwidget" class="d-inline-block timelogwidget" href="javascript:void(0);" onclick="notificationlistwidget.openPopup(\''+ actualurl + '/index.php?option=com_jlike&tmpl=component&view=recommendations\')" title="View To-dos"><i class="fa fa-list fa-2x"></i></a>').appendTo('body');
    }
})(jQuery);

var notificationlistwidget = {
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
