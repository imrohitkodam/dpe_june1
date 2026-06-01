var basepath = Joomla.getOptions('system.paths').base + "/index.php?option=com_dpe";

jQuery(document).on('click', '#interactionconsented', function(e) {
    var consented = jQuery(this);
    consented.prop('disabled', true);
    jQuery('.interactionInfo-help').addClass('hide');

    jQuery.ajax({
        url: basepath,
        type: "POST",
        data: {
            task: 'interaction.Save',
            format: 'json',
            consented: consented.prop("checked"),
            todo_id: consented.val(),
            type: 'consent'
        },
        dataType: 'json'
    }).done(function(data) {
        if (!data.success) {
            consented.prop('disabled', false);
            consented.prop('checked', !consented.prop("checked"));
            jQuery('.interactionInfo-help .content').html(data.message);
            jQuery('.interactionInfo-help').removeClass('hide');

        }
    }).fail(function(result) {
        consented.prop('disabled', false);
        consented.prop('checked', !consented.prop("checked"));

        jQuery('.interactionInfo-help .content').html(Joomla.JText._("COM_DPE_INTERACTION_AJAX_ERROR"));
        jQuery('.interactionInfo-help').removeClass('hide');

        console.log(result);
    });
});

jQuery(document).on('click', '#interactionRead', function(e) {
    var read = jQuery(this);

    if (read.is(':checked')) {
        jQuery('#myModal').modal({
            keyboard: false,
            backdrop: 'static',
        });
        jQuery('#interactionUsed').prop('disabled', false);
    } else {
        jQuery('#interactionUsed').prop('disabled', true);
        jQuery('#interactionUsed').prop('checked', false);
        jQuery('#interactionRead').prop('checked', false);

        jQuery('.usedActions').addClass('hide');
        jQuery(".success-msg").html('');
        interactionUsedSave(read.val());
        jQuery("[data-js-id='used-description']").val('');
    }
});

jQuery(document).on('click', '#interactionCancel', function(e) {
    jQuery('#myModal').modal('hide');
    jQuery('#interactionUsed').prop('disabled', true);
    jQuery('#interactionUsed').prop('checked', false);
    jQuery('#interactionRead').prop('checked', false);
    jQuery('.readInfo-help').addClass('hide');
});

jQuery(document).on('click', '#interactionAgree', function(e) {
    var interactionAgree = jQuery(this);
    interactionAgree.addClass('btn-loading');
    jQuery('.readInfo-help').addClass('hide');

    jQuery.ajax({
            url: basepath,
            type: "POST",
            data: {
                task: 'interaction.Save',
                format: 'json',
                read: true,
                todo_id: interactionAgree.val(),
                type: 'read'
            },
            dataType: 'json'
        }).done(function(data) {
            if (data.success) {
                jQuery('#interactionRead').prop('disabled', true);
                jQuery('#myModal').modal('hide');
            } else {
                jQuery('.readInfo-help .content').html(data.message);
                jQuery('.readInfo-help').removeClass('hide');
            }
        }).fail(function(result) {
            jQuery('.readInfo-help .content').html(Joomla.JText._("COM_DPE_INTERACTION_AJAX_ERROR"));
            jQuery('.readInfo-help').removeClass('hide');
            console.log(result);
        })
        .always(function() {
            interactionAgree.removeClass('btn-loading');
        });
});

jQuery(document).on('click', '#interactionUsed', function(e) {
    var used = jQuery(this);

    if (used.is(':checked')) {
        jQuery('.usedActions').removeClass('hide');
    } else {
        if (jQuery("[data-js-id='used-description']").val() != '') {
            if (!confirm(Joomla.JText._("COM_DPE_INTERACTION_USED_ROLLBACK_CONFIRMATION"))) {
                used.prop('checked', !used.prop("checked"));

                return false;
            }
        }

        jQuery('.usedActions').addClass('hide');
        jQuery(".success-msg").html('');

        jQuery("[data-js-id='used-description']").val('');
        interactionUsedSave(used.val());
    }
});

jQuery(document).on('click', '#interactionUsedSave', function(e) {
    jQuery('.interactionInfo-help').addClass('hide');
    var interactionSave = jQuery(this);
    if (jQuery("[data-js-id='used-description']").val() == '' || jQuery("[data-js-id='used-description']").val() == 'undefined') {
        alert(Joomla.JText._("COM_DPE_INTERACTION_USED_TEXT_PLACEHOLDER"));
        return false;
    }

    interactionSave.addClass('btn-loading');
    var interactionUsed = jQuery('#interactionUsed');
    interactionUsedSave(interactionSave.data('todo-id'));
});

function interactionUsedSave(todoId) {
    var interactionUsed = jQuery('#interactionUsed');

    jQuery.ajax({
            url: basepath,
            type: "POST",
            data: {
                task: 'interaction.Save',
                format: 'json',
                used: interactionUsed.prop("checked"),
                todo_id: todoId,
                desc: jQuery("[data-js-id='used-description']").val(),
                type: 'used'
            },
            dataType: 'json'
        }).done(function(data) {
            if (data.success) {

                if (interactionUsed.prop("checked") == true) {
                    jQuery(".success-msg").html(data.message);
                }
            } else {
                jQuery('.interactionInfo-help .content').html(data.message);
                jQuery('.interactionInfo-help').removeClass('hide');
            }
        }).fail(function(result) {
            jQuery('.interactionInfo-help .content').html(Joomla.JText._("COM_DPE_INTERACTION_AJAX_ERROR"));
            jQuery('.interactionInfo-help').removeClass('hide');
            console.log(result);
        })
        .always(function() {
            interactionAgree.removeClass('btn-loading');
        });
}

var documentCompleted = parseInt(Joomla.getOptions('isDocumentCompleted'));

jQuery(document).on('click', '.interaction-close-alert', function(e) {
    jQuery(this).closest(".alert").addClass('hide');
});

jQuery(document).ready(function() {
    if (!documentCompleted) {
        jQuery("#interactionForm :input").prop("disabled", true);
    } else {
        if (jQuery('#interactionRead').is(':checked')) {
            jQuery('#interactionRead').prop('disabled', true);
        } else {
            jQuery("#interactionForm :input").prop("disabled", true);
            jQuery('#interactionRead').prop('disabled', false);
        }
    }

    var practice = jQuery('#jform_practice_interaction');
    var read = jQuery('#jform_read_interaction');

    if (practice.is(':checked') || read.is(':checked')) {
        practice.removeAttr('disabled');
    } else {
        practice.attr('disabled', 'true');
    }

    read.click(function() {
        if (read.is(':checked')) {
            practice.removeAttr('disabled');
        } else {
            practice.attr('checked', false);
            practice.attr('disabled', true);
        }
    });
});

jQuery(document).on("tjlmsLessonCompleted", function() {
    if (!documentCompleted) {
        jQuery("#interactionForm :input").prop("disabled", false);

        if (jQuery('#interactionRead').is(':checked')) {
            jQuery('#interactionRead').prop('disabled', true);
        } else {
            jQuery('#interactionUsed').prop('disabled', true);
        }

        documentCompleted = true;
    }
});