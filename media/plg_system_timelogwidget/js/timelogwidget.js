(function($) {
    $.fn.timelogwidget = function(options) {

        $('<a id="timelogwidget" class="d-inline-block timelogwidget" href="javascript:void(0);" onclick="timeLogwidget.openTimeLogPopup(\'index.php?option=com_timelog&tmpl=component&task=dpeactivityform.edit\')" title="Add Time Log"><i class="fa fa-history fa-2x"></i></a>').appendTo('body');
    }
})(jQuery);

var timeLogwidget = {
    openTimeLogPopup: function(url) {
        var fullUrl = window.location.href;

            url = window.location.origin +"/component/timelog/activityform?layout=edit&tmpl=component";

        
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
            classWindow: 'timelog-activities-popup',
        });
    }
}
