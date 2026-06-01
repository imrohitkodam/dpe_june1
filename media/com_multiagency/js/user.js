/*
 * @package    Com_Multiagency
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2017 TechJoomla. All rights reserved
 * @license    GNU General Public License version 2, or later
 */
if (typeof(techjoomla) == 'undefined') {
    var techjoomla = {};
}

if (typeof techjoomla.jQuery == "undefined") {
    techjoomla.jQuery = jQuery;
}
/*function validate import*/
function validate_import(thisfile, userImport) {
    let agency = techjoomla.jQuery('.selectAgency option:selected').val();

    if (techjoomla.jQuery.trim(agency) == '' || typeof(agency) == 'undefined') {
        alert(Joomla.JText._('COM_MULTIAGENCY_FORM_DESC_SELECT_AGENCY_ID'));
        techjoomla.jQuery("#tjlms-csv-upload").val('');
        return false;
    }

    techjoomla.jQuery(thisfile).closest('.controls').children(".statusbar").remove();

    var format_lesson_form = techjoomla.jQuery(thisfile).closest('form');

    /* Hide all alerts msgs */
    var obj = techjoomla.jQuery(thisfile);
    var status = new createStatusbar(obj); //Using this we can set progress.

    /* Get uploaded file object */
    var uploadedfile = techjoomla.jQuery(thisfile)[0].files[0];

    /* Get uploaded file name */
    var filename = uploadedfile.name;


    /* pop out extension of file*/
    var ext = filename.split('.').pop().toLowerCase();

    var fileExt = filename.split('.').pop();

    if (fileExt != 'csv') {
        status.setMsg(nonvalid_extension, 'alert-error');
        return false;
    }

    /* IF evrything is correct so far, popolate file name in fileupload-preview*/

    var file_name_container = techjoomla.jQuery(".fileupload-preview", techjoomla.jQuery(thisfile).closest('.fileupload-new'));

    techjoomla.jQuery(file_name_container).show();
    techjoomla.jQuery(file_name_container).text(filename);

    startImporting(uploadedfile, status, thisfile, userImport);
}

function createStatusbar(obj) {
    this.statusbar = techjoomla.jQuery("<div class='statusbar'></div>");
    this.filename = techjoomla.jQuery("<div class='filename'></div>").appendTo(this.statusbar);
    this.size = techjoomla.jQuery("<div class='filesize'></div>").appendTo(this.statusbar);
    this.msg = techjoomla.jQuery('<div class="msg alert"></div>').appendTo(this.statusbar);
    //this.progressBar = techjoomla.jQuery('<div class="progress"><div class="progress-bar progress-bar-uploading"><span class="progress_bar_text">Uploading <span class="progress_per"></span></div></div>').appendTo(this.statusbar);
    //this.abort = techjoomla.jQuery("<div class='abort'>Abort</div>").appendTo(this.statusbar);
    //this.processBar = techjoomla.jQuery('<div class="process"><div class="progress-bar-processing"><span class="process_bar_text">Processing <span class="process_per"></span></div></div>').appendTo(this.statusbar);
    //this.processBarStatus = techjoomla.jQuery('<div class="process_done alert alert-success"></div>').appendTo(this.statusbar);

    obj.closest('.controls').append(this.statusbar);

    this.setFileNameSize = function(name, size) {
        var sizeStr = "";
        var sizeKB = size / 1024;
        if (parseInt(sizeKB) > 1024) {
            var sizeMB = sizeKB / 1024;
            sizeStr = sizeMB.toFixed(2) + " MB";
        } else {
            sizeStr = sizeKB.toFixed(2) + " KB";
        }

        this.filename.html(name);
        this.size.html(sizeStr);
    }
    this.setMsg = function(msg, classname) {
        this.statusbar.show();
        //this.progressBar.hide();
        this.msg.attr('class', 'msg alert');
        this.msg.addClass(classname);
        this.msg.html(msg);
        this.msg.show();
    }
}


function startImporting(file, status, thisfile, userImport) {
    if (file == undefined) {
        status.setMsg(file_not_selected_error, 'alert-error');
        return false;
    }

    var filename = file.name;

    if (window.FormData !== undefined) // for HTML5 browsers
    {
        status.setMsg('Validating file...');

        if (userImport == 1) {
            var userImports = 1;

            var newfilename = sendImportFileToServer(file, status, thisfile, userImports);
        }
        return false;

    } else //for older browsers
    {
        alert("You need to upgrade your browser as it does not support FormData");
    }
}

function sendImportFileToServer(file, status, fileinputtag, userImport) {
    var formData = new FormData();
    var notify = 1;

    if (techjoomla.jQuery('#notify_user_import').is(":checked")) {
        notify_user = 1;
    } else {
        notify_user = 0;
    }

    formData.append('FileInput', file);
    formData.append('notify_user_import', notify_user);
    let agency = techjoomla.jQuery('.selectAgency option:selected').val();
    formData.append('client_id', agency);

    var returnvar = true;

    var jqXHR = techjoomla.jQuery.ajax({
        url: 'index.php?option=com_multiagency&task=userimport.csvImport&tmpl=component&agency',
        type: 'POST',
        data: formData,
        mimeType: "multipart/form-data",
        contentType: false,
        dataType: 'json',
        cache: false,
        processData: false,
        headers: {'X-CSRF-Token': Joomla.getOptions('csrf.token', '')}, 
        success: function(response) {
            var output = response['OUTPUT'];
            var result = output['return'];
            /*if(result == 0)
            {
                status.setMsg(output['successmsg'],'alert-error');
            }*/
            if (result == 1) {
                /* File uploading on local is done*/
                //status.setProgress(100);
                if (output['errormsg']) {
                    var finalMsg = output['successmsg'] + output['errormsg'];
                    status.setMsg(finalMsg, 'alert-error');
                } else {
                    var finalMsg = output['successmsg'] + output['errormsg'];
                    status.setMsg(finalMsg, 'alert-success');
                }

                jQuery("#user-csv-upload").val('');
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            status.setMsg(jqXHR.responseText, 'alert-error');
            returnvar = false;
        }
    });

    return returnvar;
    //status.setAbort(jqXHR);
}

// Get Related Role
function getRelatedRoles(field, relatedrole) {
    var agencyId         = jQuery(field).val();
    
    var fieldId = field.id;
    fieldId = fieldId.split("__");
    var jfromId = fieldId[1].replace("agency_role_map", "");
    var relatedRoleField = jQuery('#jform_agency_role_map__agency_role_map' + jfromId + '__relatedrole');
    
    jQuery.ajax({
    url: Joomla.getOptions('system.paths').base + '/index.php?option=com_multiagency&task=userform.getUserAgencyRelatedRoleList&tmpl=component',
        type: 'POST',
        data:  {agencyId:agencyId},
        dataType:'json',
        headers: {'X-CSRF-Token': Joomla.getOptions('csrf.token', '')}, 
        success: function(response)
        {
            if (response && (response.success == true))
            {
                relatedRoleField.find('option').remove().end().append(response.data);
                relatedRoleField.trigger("liszt:updated");
            }
        },
        error: function(jqXHR, textStatus, errorThrown)
        {
            returnvar   = false;
        }
   });  
}

function getRole(field, role) {

    if (jQuery.trim(field.value) == '' || field.value == undefined) {
        return false;
    }

    var fieldId = field.id;
    fieldId = fieldId.split("__");
    var jfromId = fieldId[1].replace("agency_role_map", "");
    var aid = jQuery("#jform_agency_role_map__agency_role_map" + jfromId + "__client_id").val();
    var roleField = jQuery('#jform_agency_role_map__agency_role_map' + jfromId + '__rolelist');
    var itemId = techjoomla.jQuery("#itemId").val();
    jQuery('span[for="' + field.id + '"]').remove();

    jQuery.ajax({
        url: Joomla.getOptions('system.paths').base + "/index.php?option=com_multiagency",
        type: 'POST',
        data: {
            agencyId: field.value,
            isAgencyValue: 1,
            task: 'users.validateLicense',
            headers: {'X-CSRF-Token': Joomla.getOptions('csrf.token', '')}, 
            format: 'json'
        },
        dataType: "json"
    }).done(function(data) {
        if (data && (data.success == false)) {
            jQuery('#' + field.id + '_chosen').after('<span class="alert text-danger" for=' + field.id + ' id="add_stff_error">' + data.message + '</span>');
            roleField.prop('disabled', true).trigger("chosen:updated");
        } else if (data && (data.success == true)) {
            jQuery('span[for="' + field.id + '"]').remove();
            roleField.prop('disabled', false).trigger("chosen:updated");

            jQuery.ajax({
                url: Joomla.getOptions('system.paths').root + "/index.php?option=com_multiagency&task=userform.getUserAgencyRoleList&tmpl=component",
                type: "GET",
                data: {
                    'aid': aid,
                    'role': role,
                    'user_id': itemId
                },
                dataType: 'json',
                headers: {'X-CSRF-Token': Joomla.getOptions('csrf.token', '')}, 
                success: function(result) {
                    roleField.find('option').remove().end().append(result);
                    roleField.trigger("chosen:updated");
                }
            });
        }
    }).fail(function(result) {
        jQuery('#' + field.id + '_chosen').after('<span class="alert text-danger" for=' + field.id + ' id="add_stff_error">' + Joomla.JText._("COM_MULTIAGENCY_INTERACTION_AJAX_ERROR") + '</span>');
        roleField.prop('disabled', true).trigger("chosen:updated");
    });
}

function checkDuplicates() {
    var itemId = techjoomla.jQuery("#itemId").val();
    var email = techjoomla.jQuery("#jform_email").val();

    if (itemId == null) {
        var abc = techjoomla.jQuery.ajax({
            url: Joomla.getOptions('system.paths').root + "/index.php?option=com_multiagency&task=userform.validateEmail&tmpl=component",
            type: 'POST',
            data: {
                'email': email
            },
            dataType: 'json',
            headers: {'X-CSRF-Token': Joomla.getOptions('csrf.token', '')}, 
            success: function(response) {
                if (response.data == 'failure') {
                    alert('COM_USER_TITLE_MANAGEENROLLMENTS_IMPORT_ALREADY_ASSIGNED');
                    return false;
                }
            }
        });
    }
}

jQuery(document).ready(function() {
    //~ techjoomla.jQuery(".agencylist").each(function () {

    //~ let fieldId = techjoomla.jQuery(this).attr('id');
    //~ fieldId =  fieldId.split("__");
    //~ let jfromId = fieldId[1].replace("agency_role_map", "");
    //~ let roleId = jQuery("#jform_agency_role_map__agency_role_map"+jfromId+"__rolelist").val();

    //~ getRole(this, roleId);
    //~ })
})

jQuery(document).on('change', '#agency', function(e) {
    var agencyId = jQuery(this).val();

    if (jQuery.trim(agencyId) == '' || agencyId == undefined) {
        return false;
    }

    var errorMsg = jQuery('#license_error');
    var csvField = jQuery('#tjlms-csv-upload');
    var uploadInfo = jQuery('#infoText');

    jQuery.ajax({
        url: Joomla.getOptions('system.paths').base + "/index.php?option=com_multiagency",
        type: 'POST',
        data: {
            agencyId: agencyId,
            task: 'users.validateLicense',
            format: 'json'
        },
        headers: {'X-CSRF-Token': Joomla.getOptions('csrf.token', '')}, 
        dataType: "json"
    }).done(function(data) {
        if (data && (data.success == false)) {
            errorMsg.addClass("alert alert-error");
            errorMsg.html(data.message);
            csvField.attr('disabled', true);
        } else if (data && (data.success == true)) {
            errorMsg.removeClass("alert alert-error");
            errorMsg.html('');
            uploadInfo.html('');
            uploadInfo.hide();
            csvField.attr('disabled', false);
        }
    }).fail(function(result) {
        errorMsg.addClass("alert alert-error");
        errorMsg.html(Joomla.JText._("COM_MULTIAGENCY_INTERACTION_AJAX_ERROR"));
        csvField.attr('disabled', true);
    });
});