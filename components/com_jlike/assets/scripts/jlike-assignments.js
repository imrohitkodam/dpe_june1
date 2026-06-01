jQuery(document).ready(function() {
    initAssignments();
});

var initAssignments = function() {
    jQuery('div[data-for-assignment="assignment"]').each(function() {
        var el = jQuery(this);
        el.addClass('isloading');
        jQuery.ajax({
                url: Joomla.getOptions('system.paths').base + "/index.php?option=com_jlike",
                type: "POST",
                dataType: 'json',
                data: {
                    element: el.data('assignment-client'),
                    element_id: el.data('assignment-content-id'),
                    task: "extendedtodos.display",
                    view: "extendedtodos",
                    format: "json",
                }
            }).done(function(data) {
                if (data && (data.success == true)) {
                    el.html(data.data);
                    jQuery(document).find('.hasPopover').popover();
                    jQuery("#assignmentTodos").scroll(function(e) {
                        if (jQuery(this).scrollTop() + jQuery(this).innerHeight() >= jQuery(this)[0].scrollHeight && (jQuery(this)[0].scrollHeight)) {
                            scrolledLoadMore(e);
                        }
                    });
                } else {
                    jQuery('.assignment-todos-unabletoload').removeClass('hide');

                }
            }).fail(function(result) {
                jQuery('.assignment-todos-unabletoload').removeClass('hide');
                console.log(result);
            })
            .always(function() {
                el.removeClass('isloading');
            });
    });
}

function applyFilters(el) {
    var el = jQuery(el);
    var form = el.closest('form');
    form.find('[name=task]').val("extendedtodos.loadMOre");
    form.find('[name=limitstart]').val(0);
    jQuery('.assignment-todos-noresult').addClass("hide");

    if (form.find("[name='filter[read]']").val() || form.find("[name='filter[used]']").val()) {
        jQuery('.notification-dot').removeClass("hide");
    } else {
        jQuery('.notification-dot').addClass("hide");
    }

    var formData = form.serializeArray();
    var parentDiv = jQuery('#assignmentTodos');
    parentDiv.addClass('isloading');
    jQuery('#assignmentTodos table').addClass('hide');
    jQuery('#assignmentTodos table tbody').empty();

    jQuery.ajax({
            url: Joomla.getOptions('system.paths').base + "/index.php?option=com_jlike&format=json",
            type: "POST",
            data: formData,
            dataType: 'json'
        }).done(function(data) {
            if (data && (data.success == true)) {
                if (data.data.html) {
                    jQuery('#assignmentTodos table tbody').append(data.data.html);
                    jQuery(document).find('.hasPopover').popover();
                    form.find('[name=total]').val(data.data.total);
                    jQuery('#assignmentTodos table').removeClass('hide');
                } else {
                    jQuery('.assignment-todos-noresult').removeClass("hide");
                }
            }
        }).fail(function(result) {
            console.log(result);
        })
        .always(function() {
            parentDiv.removeClass('isloading');
        });
}

var scrolledLoadMore = function(el) {
    var el = jQuery('#assignmentTodos');
    el.addClass('isloading');
    var form = el.closest('form');
    form.find('[name=task]').val("extendedtodos.loadMOre");
    var limit = parseInt(form.find('[name=limit]').val());
    var total = parseInt(form.find('[name=total]').val());
    var limitstart = parseInt(form.find('[name=limitstart]').val());
    var newlimit = limitstart + limit;

    if (newlimit >= total) {
        el.removeClass('isloading');
        return;
    }

    form.find('[name=limitstart]').val(newlimit);
    var formData = form.serializeArray();

    jQuery.ajax({
            url: Joomla.getOptions('system.paths').base + "/index.php?option=com_jlike&format=json",
            type: "POST",
            data: formData,
            dataType: 'json'
        }).done(function(data) {
            if (data && (data.success == true)) {
                if (data.data.html) {
                    jQuery('#assignmentTodos table tbody').append(data.data.html);
                    jQuery(document).find('.hasPopover').popover();
                    form.find('[name=total]').val(data.data.total);
                }
            }
        }).fail(function(result) {
            console.log(result);
        })
        .always(function() {
            el.removeClass('isloading');
        });
}

jQuery(document).on('click', '.filter-container .dropdown-menu', function(e) {
    e.stopPropagation();
});
