// Declare Namespace
/** global: window */
window.com_tjgophish = {};
var Services = function () {};
var UI = function () {};
window.com_tjgophish.Services = new Services();
window.com_tjgophish.UI = new UI();
Services = undefined;
UI = undefined;

var groupForm = {
    openGroupFormPopup: function(url, popupclass="groupadd-form") {
        var wwidth = jQuery(window).width() - 300;
        var wheight = jQuery(window).height() - 200;
        SqueezeBox.open(url, {
            handler: 'iframe',
            closable: false,
            size: {
                x: wwidth,
                y: wheight
            },
            /*iframePreload:true,*/
            sizeLoading: {
                x: wwidth,
                y: wheight
            },
            classWindow: popupclass,
        });
    },
    closePopup: function() {
        window.parent.SqueezeBox.close();
    }
}
