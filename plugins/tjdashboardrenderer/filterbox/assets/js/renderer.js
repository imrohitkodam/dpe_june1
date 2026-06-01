var TJDashboardFilterbox = {
    renderData: function(e, t) {
        var a, l = jQuery("#dpeadminuser").val(),
            s = jQuery('[name="Itemid"]').val();
        jQuery.ajax({
            type: "GET",
            url: root_url + "index.php?option=com_dpe&task=school.getJsonTagsList&Itemid=" + s,
            dataType: "json",
            data: {
                role: l
            },
            async: !1,
            success: function(e) {
                a = e
            }
        }), this[e](t, a)
    },
    tjdashfilter: function(e, t) {
        var a = '<div class="panel-body"><div class="row">';
        a += '<form action="#" method="POST" name="searchDashboardForm" id="searchDashboardForm">';
        var l = JSON.parse(e.data),
            s = l.data.filters,
            i = "",
            d = jQuery("#" + e.element).prev().closest(".widget-title").text(),
            a = '<h1 class="col-sm-4 widget-heading widget-label-title font-500 ps-3 mb-3 pt-2">',
            l = JSON.parse(e.data);
        "" != d && void 0 != d ? a += d : a += l.data.widgetlabel, a += "</h1>";
        var o = l.data.title;
        if ("" != e.params && void 0 != e.params && !1 == e.params.title && (o = ""), "" != o && void 0 != o && (a += '<div  class="col-lg-8 col-sm-8 mb-3 d-flex gap-2 justify-content-end"> <div class="row clusterrow d-inline-block"><div class="col-lg-6 col-sm-6"><label class="filterbox-label">' + o + "</label></div>"), "object" == typeof s && null !== s) {
            var r = s.Organisation.cluster_id.length;
            jQuery.each(s, function(e, t) {
                a += '<div class="col-lg-11 col-sm-6 dashboard-selectfilters">', jQuery.each(t, function(e, t) {
                    e != i && (a += '<select id="' + e + '" name="filter[' + e + ']" class="w-100 widget-filters cluster-filter" >', i = e), clusterId = jQuery("#dashboardClusterId").val();
                    var l = jQuery("#dashboardTagId").val();
                    "All" != this[0].text && r > 1 && (a += "<option value='' selected>All</option>"), jQuery(t).each(function() {
                        this.value != clusterId || l ? a += "<option value='" + this.value + "'>" + this.text + "</option>" : a += "<option value='" + this.value + "' selected>" + this.text + "</option>"
                    }), e != i && (a += "</select>")
                }), a += "</div>"
            })
        }
        var c = jQuery("#dpeadminuser").val();
        if (c) {
            var n = '<div class="clearfix"></div><div class="row justify-content-end d-inline-block"><div class="col-lg-6 col-sm-6"><label class="filterbox-label filter-tag">Select Tag</label></div>';
            n += '<div class="col-lg-12 col-sm-6 dashboard-selectfilters">', n += '<select id="tags" name="filter[tags][]" data-placeholder="Select tags" multiple="multiple" class="w-100 widget-filters tag-filters" >';
            var v = jQuery("#dashboardTagId").val();
            v = v.split(","), clusterId = jQuery("#dashboardClusterId").val(), t.data.forEach(function(e, t) {
                let a = v.includes(e.value.toString());
                null !== v && a && !clusterId ? n += "<option value='" + e.value + "' selected>" + e.text + "</option>" : n += "<option value='" + e.value + "'>" + e.text + "</option>"
            }), n += "</select>", n += "</div></div>"
        }
        a += "</form></div></div></div>", jQuery("#" + e.element).html(a), c && jQuery(n).insertAfter(".clusterrow"), jQuery(".widget-filters").chosen({
            disable_search_threshold: 8
        })
    }
};
jQuery(document).ready(function() {
    var e = 1;
    if (jQuery(".widget-boxes").each(function() {
            "" == jQuery(".dashboard-widget-row-" + e).html() && jQuery(".dashboard-widget-row-" + e).remove(), e++
        }), sessionStorage.getItem("selectedTags")) {
        var t = sessionStorage.getItem("selectedTags"),
            a = JSON.parse(t);
        jQuery("#tags").val(a), jQuery("#tags").trigger("chosen:updated")
    }
    if (sessionStorage.getItem("selectedCluster")) {
        var t = sessionStorage.getItem("selectedCluster"),
            a = JSON.parse(t);
        jQuery("#cluster_id").val(a), jQuery("#cluster_id").trigger("chosen:updated")
    }
    jQuery("#tags").on("change", function() {
        var e = JSON.stringify($(this).val());
        sessionStorage.setItem("selectedTags", e), sessionStorage.setItem("selectedCluster", "")
    }), jQuery("#cluster_id").on("change", function() {
        var e = JSON.stringify($(this).val());
        sessionStorage.setItem("selectedCluster", e), sessionStorage.setItem("selectedTags", "")
    })
});