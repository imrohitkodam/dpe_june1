var TJDashboardDaterange = {
    renderData: function(method, sourceData) {
        this[method](sourceData);
    },
    tjdashfilter: function(sourceData) {
        var content = '<div class=""><div class="row">';

        //content += '<form action="#" method="POST" name="searchDashboardForm" id="searchDashboardForm">';

        var widgetTitle = jQuery("#" + sourceData.element).prev().closest('.widget-title').text();

        content += '<div class="col-md-3"><select id="operator" name="filter[operator]" class="widget-date-operator widget-date-filters" >';
        content += "<option value=''>Any</option>";
        content += "<option value='='>Is</option>";
        content += "<option value='between'>Between</option>";
        content += "<option value='w'>This Week</option>";
        content += "<option value='m'>This Month</option>";
        content += "<option value='y'>This Year</option>";
        content += "<option value='gt'>Greater than</option>";
        content += "<option value='lt'>Less than</option>";
        content += '</select></div>';
        content += '<div class="col-md-3"><input type="date" id="date_start" name="filter[date_start]" class="hide widget-date-filters"></div>';
        content += '<div class="col-md-3"><input type="date" id="date_end" name="filter[date_end]" class="hide widget-date-filters"></div>';
        content += '<div class="btn-group filter-btn-block input-append"><button class="btn cal-btn hasTooltip widget-search" title=""><i class="icon-search"></i></button><button class="btn widget-clear"  type="button" onclick="TJDashboardDaterange.cleardate();"><i class="icon-remove"></i></button></div>';
        content += "</div></div>";

        jQuery("#" + sourceData.element).html(content);

        /* IMP : to add chz-done into selects*/
        jQuery(".widget-date-operator").chosen({
            disable_search_threshold: 8
        });

    },
    cleardate: function() {
        jQuery("#date_start").val('').addClass("hide");
        jQuery("#date_end").val('').addClass("hide");
        jQuery("#operator").val('');
        jQuery('#operator').trigger('liszt:updated');
    }
};

jQuery(document).on("change", "#operator", function() {
    var operator = jQuery(this).val();
    var hideStartDate = jQuery('#date_start').addClass('hide');
    var hideEndDate = jQuery('#date_end').addClass('hide');
    var showStartDate = jQuery('#date_start').removeClass('hide');
    var showEndDate = jQuery('#date_end').removeClass('hide');

    if (operator == 'w' || operator == 'm' || operator == 'y' || operator == "") {
        jQuery('#date_start').addClass('hide').val("");
        jQuery('#date_end').addClass('hide').val("");
    } else if (operator == '=' || operator == 'gt' || operator == 'lt') {
        jQuery('#date_start').removeClass('hide').val("");;
        jQuery('#date_end').addClass('hide').val("");
    } else if (operator == 'between') {
        jQuery('#date_start').removeClass('hide').val("");
        jQuery('#date_end').removeClass('hide').val("");
    }
});

jQuery(document).on("click", ".widget-search, .widget-clear", function() {
    var operator = jQuery('#operator').val();
    var startDate = jQuery('#date_start').val();
    var endDate = jQuery('#date_end').val();

    if (operator == "=" && !startDate) {
        alert("Date cannot be blank");
        return false;
    } else if (operator == "between" && (!startDate && !endDate)) {
        alert("Date cannot be blank");
        return false;
    } else if ((operator == "between" || operator == "lt" || operator == "gt") && !startDate) {
        alert("Date cannot be blank");
        return false;
    }

    TJDashboardUI.widgetListener();
});

