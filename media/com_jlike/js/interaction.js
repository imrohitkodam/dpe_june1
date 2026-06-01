var basepath = Joomla.getOptions('system.paths').base + "/index.php?option=com_jlike";

jQuery(document).on('click', '#interactionRead', function () {
    var read = jQuery(this);

    if (read.is(':checked')) {
        jQuery('#JlikeInteractionModal').modal({
            keyboard: false,
            backdrop: 'static',
        });
        jQuery('#interactionUsed').prop('disabled', false);
    } else {
        jQuery('#interactionUsed').prop('disabled', true);
        jQuery('#interactionUsed').prop('checked', false);
        jQuery('#interactionRead').prop('checked', false);

        jQuery('.usedActions').addClass('hide');
        jQuery(".success-msg").text('');
        interactionUsedSave(read.val());
        jQuery("[data-js-id='used-description']").val('');
    }
});

jQuery(document).on('click', '#interactionCancel', function () {
    jQuery('#JlikeInteractionModal').modal('hide');
    jQuery('#interactionUsed').prop('disabled', true);
    jQuery('#interactionUsed').prop('checked', false);
    jQuery('#interactionRead').prop('checked', false);
    jQuery('.readInfo-help').addClass('hide');
});

jQuery(document).on('click', '#interactionAgree', function () {
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
    }).done(function (data) {
        if (data.success) {
            jQuery('#interactionRead').prop('disabled', true);
            jQuery('#JlikeInteractionModal').modal('hide');
        } else {
            jQuery('.readInfo-help .content').text(data.message);
            jQuery('.readInfo-help').removeClass('hide');
        }
    }).fail(function () {
        jQuery('.readInfo-help .content').text(Joomla.JText._("COM_JLIKE_INTERACTION_AJAX_ERROR"));
        jQuery('.readInfo-help').removeClass('hide');
    })
        .always(function () {
            interactionAgree.removeClass('btn-loading');
        });
});

jQuery(document).on('click', '#interactionUsed', function () {
    var used = jQuery(this);

    if (used.is(':checked')) {
        jQuery('.usedActions').removeClass('hide');
        jQuery('#interactionUsedText').prop('disabled', false);
        jQuery('#interactionUsedSave').prop('disabled', false);

        return true;
    }

    if (jQuery("[data-js-id='used-description']").val() != '') {
        if (!confirm(Joomla.JText._("COM_JLIKE_INTERACTION_USED_ROLLBACK_CONFIRMATION"))) {
            used.prop('checked', !used.prop("checked"));

            return false;
        }
    }

    jQuery('.usedActions').addClass('hide');
    jQuery(".success-msg").text('');

    jQuery("[data-js-id='used-description']").val('');
    interactionUsedSave(used.val());
    return true;
});

jQuery(document).on('click', '#interactionUsedSave', function () {
    jQuery('.interactionInfo-help').addClass('hide');
    var interactionSave = jQuery(this);
    if (jQuery("[data-js-id='used-description']").val() == '' || jQuery("[data-js-id='used-description']").val() == 'undefined') {
        alert(Joomla.JText._("COM_JLIKE_INTERACTION_USED_TEXT_PLACEHOLDER"));
        return false;
    }

    interactionSave.addClass('btn-loading');
    interactionUsedSave(interactionSave.data('todo-id'));
    return true;
});

function interactionUsedSave(todoId) {
    var interactionUsed = jQuery('#interactionUsed');
    var interactionAgree = jQuery(this);

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
    }).done(function (data) {
        if (data.success) {

            if (interactionUsed.prop("checked") === true) {
                jQuery(".success-msg").text(data.message);
            }
        } else {
            jQuery('.interactionInfo-help .content').text(data.message);
            jQuery('.interactionInfo-help').removeClass('hide');
        }
    }).fail(function () {
        jQuery('.interactionInfo-help .content').text(Joomla.JText._("COM_JLIKE_INTERACTION_AJAX_ERROR"));
        jQuery('.interactionInfo-help').removeClass('hide');
    })
        .always(function () {
            interactionAgree.removeClass('btn-loading');
        });
}

var documentCompleted = parseInt(Joomla.getOptions('isDocumentCompleted'));

jQuery(document).on('click', '.interaction-close-alert', function () {
    jQuery(this).closest(".alert").addClass('hide');
});

jQuery(document).ready(function () {
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

    // DPE Hack - Show Read interaction by default checked while creating document
    read.attr('disabled', 'true');
    read.prop('checked', true);

    /*
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
    */
});

jQuery(document).on("tjlmsLessonCompleted", function () {
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
