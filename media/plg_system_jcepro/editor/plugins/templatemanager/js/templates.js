/* jce - 2.9.95 | 2025-10-14 | https://www.joomlacontenteditor.net | Source: https://github.com/widgetfactory/jce | Copyright (C) 2006 - 2025 Ryan Demmer. All rights reserved | GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html */
!function($) {
    $(document).ready(function() {
        $(".controls").on("change", ".wf-templatemanager-templates :input", function(e) {
            var $ctrl = $(this).parents(".wf-templatemanager-templates").parent(), items = [];
            $(".wf-templatemanager-templates", $ctrl).each(function() {
                var data = {};
                $(":input[name]", this).each(function() {
                    var name = $(this).attr("name"), val = $(this).val();
                    data[name] = $("<textarea/>").text(val).html();
                }), items.push(data);
            }), $ctrl.find('input[name*="jform"][type="hidden"]').val(JSON.stringify(items)).trigger("change");
        });
    });
}(jQuery);