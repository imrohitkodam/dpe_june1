/*
 * @package     System.Plugins
 * @subpackage  Plugins,system,tjsendemail
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

jQuery(document).ready(function() {
    tjutilitysendemail.tjTdClass = '.td-sendemail';
    tjutilitysendemail.tjTableId = 'report-table';
    tjutilitysendemail.initialize();

    var isSendEmail = jQuery('body').find(tjutilitysendemail.tjTdClass).length;
    if (isSendEmail) {
        tjutilitysendemail.loadTinymce();
    }
});

var tjutilitysendemail = {
    loadTinymce: function() {
        tinymce.remove();
        tinymce.init({
            selector: 'textarea#email-message',
            setup: function(editor) {
                editor.on('change', function() {
                    tinymce.triggerSave();
                });
            }
        });
    },
    initialize: function() {
        var isSendEmail = jQuery('body').find(tjutilitysendemail.tjTdClass).length;
        var isCheckboxes = jQuery('body').find('input[name="cid[]"]').length;

        if (isSendEmail) {
            /* If on list view already checkbox are present then avoid the add column function*/
            if (!isCheckboxes) {
                this.addColumn();
            }

            this.btnSendEmail();
        }
    },
    addColumn: function() {
        var tr = document.getElementById(tjutilitysendemail.tjTableId).tHead.children[0];
        tr.insertCell(0).outerHTML = '<th><input type="checkbox" name="checkall-toggle" value="" class="hasTooltip" title="' + Joomla.JText._('PLG_SYSTEM_TJSENDEMAIL_CHECKALL') + '" onclick="Joomla.checkAll(this)"></th>'

        var tblBodyObj = document.getElementById(tjutilitysendemail.tjTableId).tBodies[0];
        for (var i = 0; i < tblBodyObj.rows.length; i++) {
            var newCell = tblBodyObj.rows[i].insertCell(0);
            newCell.innerHTML = '<input type="checkbox" id="cb0" name="cid[]" value="' + i + '" onclick="Joomla.isChecked(this.checked);">'
        }
    },
    btnSendEmail: function() {
        try {
            let btnHtml = '<div class="btn-wrapper" id="tj-sendemail">';
            btnHtml += '<button type="button" class="btn btn-primary" id="email-queue-column" data-toggle="modal" data-target="#bulkEmailModal" onclick="tjutilitysendemail.openEmailPopup();">';
            btnHtml += Joomla.JText._('PLG_SYSTEM_TJSENDEMAIL_BTN');
            btnHtml += '</button>';
            btnHtml += '</div>';

            // jQuery('#toolbar').append(btnHtml);
            jQuery('#sendEmail').append(btnHtml);
        } catch (err) {
            /*console.log(err.message);*/
        }
    },
    openEmailPopup: function() {
        try {
            tjutilitysendemail.loadTinymce();

            var emailSubject = 'email-subject';
            var emailMessage = 'email-message';

            if (document.adminForm.boxchecked.value == 0) {
                alert(Joomla.JText._('PLG_SYSTEM_TJSENDEMAIL_SELECT_CHECKBOX'));
                return false;
            }

            // Remove below line removeclass it is temp added
            jQuery("div").find('.tjlms-wrapper').removeClass("tjBs3");

            var modelEmail = '<div id="bulkEmailModal" class="custom-modal modal fade" role="dialog">';
            modelEmail += '<div class="modal-dialog modal-lg">';
            modelEmail += '<div class="modal-content is-progress" id="emailPopup">';
            modelEmail += '<div id="preload"></div>';
            modelEmail += '<div class="modal-header">';
            modelEmail += '<h5 class="modal-title d-inline-block fs-18 font-600" id="exampleModalLabel">' + Joomla.JText._('PLG_SYSTEM_TJSENDEMAIL_POPUP_HEADING') + '</h5>';
            modelEmail += '<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>';
            modelEmail += '<span id="errorMessage"></span>';
            modelEmail += '</div>';

            modelEmail += '<div class="modal-body">';
            modelEmail += '<div class="container-fluid">';
            modelEmail += '<form action="#" method="post" enctype="multipart/form-data" name="emailTemplateForm" id="emailTemplateForm" class="form-validate">';
            modelEmail += '<div class="row">';
            modelEmail += '<div class="col-sm-12">';
            modelEmail += '<div class="control-group">';
            modelEmail += '<div class="control-label">';
            modelEmail += '<label id="email-subject-label" for="email-subject" class="hasPopover required" title="" >';
            modelEmail += Joomla.JText._('PLG_SYSTEM_TJSENDEMAIL_POPUP_EMAIL_SUBJECT') + ' <span class="star">&nbsp;*</span>';
            modelEmail += '</label>';
            modelEmail += '</div>';

            modelEmail += '<div class="controls">';
            modelEmail += '<input type="text" name="template[subject]" id="' + emailSubject + '" value="" class="required w-100" required="required" aria-required="true">';
            modelEmail += '</div>';
            modelEmail += '</div>';
            modelEmail += '<div class="control-group">';
            modelEmail += '<div class="control-label">';
            modelEmail += '<label id="email-message-label" for="email-message" class="hasPopover required" title="" >';
            modelEmail += Joomla.JText._('PLG_SYSTEM_TJSENDEMAIL_POPUP_EMAIL_BODY_MESSAGE') + ' <span class="star">&nbsp;*</span>';
            modelEmail += '</label>';
            modelEmail += '</div>';
            modelEmail += '<div class="controls">';
            modelEmail += '<textarea name="template[message]" rows="7" style="width: 30%;" id="' + emailMessage + '" value="" class="required" required="required" aria-required="true"></textarea>';
            modelEmail += '</div>';
            modelEmail += '</div>';
            modelEmail += '</div>';
            modelEmail += '<div id="emailsDiv"></div>';
            modelEmail += '</div>';
            modelEmail += '</div>';
            modelEmail += '</form>';
            modelEmail += '</div>';
            modelEmail += '<div class="modal-footer">';
            modelEmail += '<button type="button" class="btn btn-primary validate" id="send-email" onclick="tjutilitysendemail.validate();">' + Joomla.JText._('PLG_SYSTEM_TJSENDEMAIL_POPUP_SEND_BTN') + '</button>';
            modelEmail += '</div>';
            modelEmail += '</div>';
            modelEmail += '</div>';
            modelEmail += '</div>';

            // Confirm this class to append popup
            if (!jQuery("#bulkEmailModal").hasClass("custom-modal")) {
                jQuery('#j-main-container').append(modelEmail);
            }

            jQuery('div').find("#preload").hide();
            jQuery('div').find(".is-progress").removeClass('is-progress');
            jQuery('div').find("#send-email").attr("disabled", false);
            jQuery("#" + emailSubject).val('');

            var values = new Array();
            jQuery("#emailsDiv").empty();

            jQuery.each(jQuery("input[name='cid[]']:checked").closest("td").siblings("td" + tjutilitysendemail.tjTdClass), function() {
                values.push(jQuery(this).text());

                var hiddenEle = "<input readonly type='hidden' name='emails[]' value='" + jQuery(this).text() + "'/>";
                jQuery("#bulkEmailModal").find("#emailsDiv").append(hiddenEle);
            });

            // Create editor field
            tinyMCE.execCommand('mceAddEditor', false, "email-message");

            // alert(values.join (", "));
        } catch (err) {
            alert(err.message);
            /*console.log(err.message);*/
        }
    },
    validate: function() {

        var emailSubjectValue = jQuery("#email-subject").val();
        var emailMessageValue = jQuery("#email-message").val();
        jQuery('#email-subject').removeClass("invalid");
        jQuery('#email-subject-label').removeClass("invalid");

        jQuery("#errorMessage").empty();

        if (!emailSubjectValue || !emailMessageValue) {
            if (!emailSubjectValue) {
                jQuery('#email-subject').addClass("invalid");
                jQuery('#email-subject-label').addClass("invalid");

                var errormsg = '<div class="alert alert-error alert-danger"><button type="button" data-dismiss="alert" class="close">×</button><h4 class="alert-heading"></h4><div>' + Joomla.JText._('PLG_SYSTEM_TJSENDEMAIL_INVALID_FIELD').replace("%s", Joomla.JText._("PLG_SYSTEM_TJSENDEMAIL_POPUP_EMAIL_SUBJECT")) + '</div></div>';
                jQuery("#bulkEmailModal").find("#errorMessage").append(errormsg);
            }

            if (!emailMessageValue) {
                jQuery('#email-message').addClass("invalid");
                jQuery('#email-message-label').addClass("invalid");

                var errormsg = '<div class="alert alert-error alert-danger"><button type="button" data-dismiss="alert" class="close">×</button><h4 class="alert-heading"></h4><div>' + Joomla.JText._('PLG_SYSTEM_TJSENDEMAIL_INVALID_FIELD').replace("%s", Joomla.JText._("PLG_SYSTEM_TJSENDEMAIL_POPUP_EMAIL_BODY_MESSAGE")) + '</div></div>';
                jQuery("#bulkEmailModal").find("#errorMessage").append(errormsg);
            }

            return false;
        }

        jQuery('#preload').show();
        jQuery('div').find("#emailPopup").addClass('is-progress');
        jQuery('#send-email').attr("disabled", true);
        var chunk_size = 5;

        var postData = jQuery("#emailTemplateForm").serializeArray();

        var emailContent = postData.splice(0, 2);

        // Remove duplicate values from email array
        postData = postData.map(JSON.stringify).reverse() // convert to JSON string the array content, then reverse it (to check from end to begining)
            .filter(function(item, index, postData) {
                return postData.indexOf(item, index + 1) === -1;
            }) // check if there is any occurence of the item in whole array
            .reverse().map(JSON.parse) // revert it to original state

        // Chuck of 10 emails
        var emails = postData.map(function(e, i) {
            return i % chunk_size === 0 ? postData.slice(i, i + chunk_size) : null;
        }).filter(function(e) {
            return e;
        });

        var batchCount = emails.length;

        jQuery.each(emails, function(i, batch) {
            var emailData = batch.concat(emailContent);

            // Call the send email function
            tjutilitysendemail.send(emailData, i, batchCount);
        });
    },
    send: function(emailData, index, batchCount) {
        index++;

        var url = Joomla.getOptions('system.paths').base + "/index.php?option=com_ajax&plugin=tjsendemail&format=json";

        var promise = jQuery.ajax({
            url: url,
            type: 'POST',
            async: true,
            data: emailData,
            dataType: 'json'
        });

        promise.fail(
            function(response) {

                var errormsg = '<div class="alert alert-error alert-danger"><button type="button" data-dismiss="alert" class="close">×</button><h4 class="alert-heading"></h4><div>' + response.responseText + '</div></div>';
                jQuery("#bulkEmailModal").find("#errorMessage").append(errormsg);
                jQuery('#preload').hide();
            }
        ).done(
            function(response) {

                if (index == batchCount) {
                    jQuery('#preload').hide();
                    jQuery('div').find(".is-progress").removeClass('is-progress');
                    jQuery('#bulkEmailModal').modal('hide');

                    // Remove below line removeclass it is temp added
                    jQuery("div").find('.tjlms-wrapper').addClass("tjBs3");

                    if (response.success) {
                        Joomla.renderMessages({
                            "success": [response.message]
                        });
                    } else {
                        Joomla.renderMessages({
                            "error": [response.message]
                        });
                    }
                }
            }
        );
    }
};
