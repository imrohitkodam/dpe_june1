jQuery(document).ready(function() {
    jQuery('#start_date').val(jQuery('#lesson_start_date').val());
    jQuery('#due_date').val(jQuery('#lesson_due_date').val());

    var multiple = jQuery('#jform_cluster_id').prop('multiple');

    if (multiple === true) {
        jQuery("#jform_cluster_id option[value='']").remove();
        jQuery('#jform_cluster_id').trigger("liszt:updated");
    }

    // Hide end-date on multi-year check
    jQuery('#jform_multiyearlicence').on('change', function(){
        if(jQuery(this).is(':checked')) {
            jQuery('#jform_end_date').closest('div.control-group').hide();
            jQuery('#jform_end_date').val('');
        }
        else
        {
            jQuery('#jform_end_date').closest('div.control-group').show();
        }
    });

    //document.formvalidator.setHandler('yearcount', function (value)
});

function validateTimeMeasureCount(fieldObj)
{
    var field = jQuery(fieldObj);

    // Don't allow float value
    field.val(parseInt(field.val()));

    if (parseInt(field.val()) > parseInt(licenceLimit))
    {
        return showErrorMsg(field, licenceLimitMsg);
    }
    else if (parseInt(field.val()) < parseInt(minLicenceLimit))
    {
        return showErrorMsg(field, minLicenceLimitMsg);
    }

    Joomla.removeMessages();
    return true;
}

function validateDuration(fieldObj)
{
    var field = jQuery(fieldObj);

    // Don't allow float value
    field.val(parseInt(field.val()));

    if (parseInt(field.val()) < 1)
    {
        return showErrorMsg(field, Joomla.JText._("COM_MULTIAGENCY_LICENCES_DURATION_MESSAGE"));
    }

    return true;
}

function showErrorMsg(field,msg)
{
    var message = {"error": [msg]};
    field.val("");
    licence.renderMessage(message);
    return false;
}

// Set dates in hidden fields

function setDate(data) {
    if (data.id == 'lesson_start_date') {
        jQuery('#start_date').val(data.value);
    } else if (data.id == 'lesson_due_date') {
        jQuery('#due_date').val(data.value);
    }
}

function closeAssignRecommendPopups() {
    window.parent.document.location.reload(true);
    window.parent.SqueezeBox.close();
}

 function setNewTableHeight() {
    var element = document.getElementById("search-tool");
    element.classList.toggle("tbody-height");

 }
 jQuery(document).ready(function() {
    setTimeout(setTablebody(),3000);

});
function setTablebody(){
    if( jQuery('#dropdown-div').css('display') == 'block') {
        jQuery("#search-tool").addClass("tbody-height");
      }
     else if(jQuery("dropdown-div").hasClass("js-stools-container-filters-visible") ) {
        jQuery("#search-tool").addClass("tbody-height");
      }
      else {
        jQuery("#search-tool").removeClass("tbody-height");
      }
    }
function redirectToassignUser() {
    window.document.location.reload(true);
}

jQuery(document).on('click', '#assign', function(e) {
    if (document.adminForm.boxchecked.value == 0) {
        alert(Joomla.JText._("COM_DPE_ASSIGNMENT_SELECT_USERS"));
        return false;
    } else if (document.adminForm.due_date.value == '' || document.adminForm.start_date.value == '') {
        alert(Joomla.JText._("COM_DPE_SELECT_DATE_FOR_ASSIGNMENT"));
        return false;
    } else {

        // Start to validate Due date
        var currentDate = new Date();
        currentDate.setHours(0, 0, 0, 0);

        var dueDate = jQuery('#lesson_due_date').val();
        dueDate = dueDate.split(' ');

        var fieldDate = dueDate[0];

        if (fieldDate.indexOf('-')) {
            fieldDate = fieldDate.split('-');
        } else if (fieldDate.indexOf('/')) {
            fieldDate = fieldDate.split('/');
        }

        var fieldDateFormat = jQuery('#lesson_due_date').next('button').data('date-format');
        fieldDateFormat = fieldDateFormat.split(' ');

        if (fieldDateFormat[0] == '%d-%m-%Y') {
            fieldDate = fieldDate.reverse().join("-")
        }

        if (new Date(fieldDate) < currentDate) {
            alert(Joomla.JText._("COM_DPE_COMPLIANCE_ASSIGN_USER_DUE_DATE_VALIDATION"));
            return false;
        }

        //End to validate field date

        if (confirm(Joomla.JText._('COM_DPE_COMPLIANCE_ASSIGN_USER_CONFIRMATION_MESSAGE')) == true) {
            var el = jQuery(this);
            el.addClass('btn-loading');
            el.removeAttr('id');
            jQuery('#task').val('users.assignUser');
            jQuery('#option').val('com_dpe');
            jQuery('.redirect_url').val('');
            var actionUrl = jQuery('.assign-userfrm').attr('action');
            var formData = jQuery('.assign-userfrm').serialize();

            jQuery.ajax({
                url: actionUrl,
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        jQuery('.assign-popup').removeClass('hide');
                        jQuery('.assign-popup').addClass('alert alert-success');
                        jQuery('.assign-response').html(response.message);
                        el.removeClass('btn-loading');
                        setTimeout("closeAssignRecommendPopups()", 2000);
                    } else {
                        jQuery('.assign-popup').removeClass('hide');
                        jQuery('.assign-popup').addClass('alert alert-error');
                        jQuery('.assign-response').html(response.message);
                        setTimeout("redirectToassignUser()", 2000);
                    }
                },
            });
        }
    }
});

function nxtBtnValidation() {
    jQuery('#select_user').removeClass("active in");
    jQuery('#assign_due_date').addClass("active in");
    jQuery('#userList').removeClass("active");
    jQuery('#dateTab').addClass("active");
}

function validate_import(thisfile) {
    var agency = jQuery('#jform_cluster_id option:selected').val();

    if (jQuery.trim(agency) == '' || typeof(agency) == 'undefined') {
        alert(Joomla.Text._('COM_DPE_SELECT_SCHOOL_ROP'));
        return false;
    }
}

jQuery(document).on('click', '#toolbar-save #button_save_and_close', function(e) {
    var schools = jQuery('#jform_cluster_id');

    if (schools.length <= 0) {
        alert(Joomla.Text._('COM_DPE_SELECT_SCHOOL_ROP'));
    }

    jQuery('#documentProgress .progress-bar').css('width', 0 + '%').attr('aria-valuenow', 0);
    jQuery('#documentProgress #progress-value').html(0 + '%');
    jQuery('#documentProgress .failed-documents-content').empty();
    jQuery('#documentProgress .failed-documents').addClass('hide');
    jQuery('#documentProgress .completed-info').addClass('hide');

    jQuery('#documentProgress').modal({
        keyboard: false,
        backdrop: 'static',
    });

    var schoolValues = schools.val();
    var totalSchools = 1;

    if (Array.isArray(schoolValues)) {
        totalSchools = schoolValues.length;
    }

    var currentSchool = 0;
    var lessonId = jQuery('[data-js-id="id"]').val();
    var form = jQuery('form[name="lesson-interaction"]');
    var isFailedDocuments = false;
    form.find('input[name=task]').remove();
    form.find('input[name=option]').remove();
    var singleDocument = '';

    if (totalSchools == 1) {
        singleDocument = '&singleDocument=1';
    }

    var createDocument = function(schoolId) {
        jQuery.ajax({
                url: Joomla.getOptions('system.paths').base + "/index.php?option=com_dpe&format=json",
                type: "POST",
                data: form.serialize() + "&task=lesson.copyLessons&lessonId=" + lessonId + "&schoolId=" + schoolId + singleDocument,
                dataType: 'json',
                headers: { 'X-CSRF-Token': Joomla.getOptions('csrf.token', '') },
            }).done(function(data) {
                if (data && (data.success == true)) {
                    var perc = "";
                    if (isNaN(totalSchools) || isNaN(currentSchool)) {
                        perc = " ";
                    } else {
                        perc = (((currentSchool + 1) / totalSchools) * 100).toFixed(0);
                    }

                    jQuery('#documentProgress .progress-bar').css('width', perc + '%').attr('aria-valuenow', perc);
                    jQuery('#documentProgress #progress-value').html(perc + '%');


                } else {
                    jQuery('#documentProgress .failed-documents-content').append('<li>' + jQuery('#jform_cluster_id  option[value="' + schoolId + '"]').text() + '</li>');
                    isFailedDocuments = true;
                }
            }).fail(function(result) {
                // console.log(result);
                jQuery('#documentProgress .failed-documents-content').append('<li>' + jQuery('#jform_cluster_id  option[value="' + schoolId + '"]').text() + '</li>');
                isFailedDocuments = true;
            })
            .always(function() {
                currentSchool = currentSchool + 1;

                if (currentSchool < totalSchools) {
                    createDocument(schoolValues[currentSchool]);
                } else {
                    //Now all the school document created process the after request
                    if (!isFailedDocuments) {
                        window.location = lessonredirectURL;
                    } else {
                        jQuery('#documentProgress .failed-documents').removeClass('hide');
                        jQuery('#documentProgress .modal-footer').removeClass('hide');
                    }
                }
            });
    }

    if (Array.isArray(schoolValues)) {
        createDocument(schoolValues[0]);
    } else {
        createDocument(schoolValues);
    }
});

jQuery(document).on('click', '#documentProgress .close-modal', function(e) {
    jQuery(this).addClass('btn-loading');
    window.location = lessonredirectURL;
});

jQuery(document).on('click', '#deassign', function(e) {
    var el = jQuery(this);
    var form = el.closest('form');
    form.find('[name=task]').val("users.deassign");
    var formData = form.serializeArray();
    var popupMessage = jQuery('.assign-popup');
    var responseMessage = jQuery('.assign-response');
    el.addClass('btn-loading');
    el.removeAttr('id');

    if (confirm(Joomla.JText._('COM_DPE_COMPLIANCE_DE_ASSIGN_USER_CONFIRMATION_MESSAGE')) == true) {
        jQuery.ajax({
                url: Joomla.getOptions('system.paths').base + "/index.php?option=com_dpe&format=json",
                type: "POST",
                data: formData,
                dataType: 'json'
            }).done(function(data) {
                if (data && (data.success == true)) {
                    popupMessage.removeClass('hide');
                    popupMessage.addClass('alert alert-success');
                    responseMessage.html(data.message);
                    setTimeout("closeAssignRecommendPopups()", 2000);
                } else {
                    popupMessage.removeClass('hide');
                    popupMessage.addClass('alert alert-error');
                    responseMessage.html(data.message);
                    setTimeout("redirectToassignUser()", 2000);
                }
            }).fail(function(result) {
                console.log(result);
            })
            .always(function() {
                el.removeClass('btn-loading');
            });
    }
});

jQuery(document).on('click', '.delete-record', function() {

    if (!confirm(Joomla.JText._('COM_DPE_DELETE_MESSAGE'))) {
        return false;
    }

    var recordId = window.atob(jQuery(this).data('deleterecid'));

    if(isNaN(recordId) || recordId =='')
    {
        return false;
    }

    jQuery("#entityName").val(jQuery(this).data('formname'));
    jQuery("#entityRecordId").val(recordId);
    jQuery("#task").val('school.delete');
    jQuery("#adminForm").submit();
});

jQuery(document).on('click', '.archive-licence', function() {

    if (!confirm(Joomla.JText._('COM_MULTIAGECNY_ARCHIVE_LICENCE_MESSAGE'))) {
        return false;
    }
    var recordId = window.atob(jQuery(this).data('licencerecid'));

    if(isNaN(recordId) || recordId =='')
    {
        return false;
    }

    jQuery("#entityRecordId").val(recordId);
    jQuery("#task").val('school.archiveLicence');
    jQuery("#adminForm").submit();
});

function openActivityPopup(url, popupclass="timelog-activities") {
    var wwidth = jQuery(window).width() - 50;
    var wheight = jQuery(window).height() - 50;
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
};

// Js code to save licence using ajax

var licence = {
    save: function() {

        var id = jQuery('#id').val();

        if (!id)
        {
            if(jQuery('#jform_show_in_sla_list').prop('checked') == true)
            {
                if (jQuery('#jform_new_sla').val().trim() == "")
                {
                    var message = {"error": [Joomla.JText._('COM_MULTIAGENCY_FORM_INVALID_SLA_NAME')]};
                    licence.renderMessage(message);

                    return false;
                }
            }

            if (jQuery('#jform_start_date').val().trim() == "")
            {
                var message = {"error": [Joomla.JText._('COM_MULTIAGENCY_FORM_INVALID_START_DATE')]};
                licence.renderMessage(message);

                return false;
            }
        }

        // Show loader
        licence.showLoader();

        // Disable submit button
        var submitBtn = jQuery("#save_licence");
        submitBtn.prop('disabled', true);

        var formData = jQuery('.add-licence').serialize();
        var url      = Joomla.getOptions("system.paths").root + "/index.php?option=com_multiagency&task=licenceform.save&format=json";
        var promise  = jQuery.ajax({
            url: url,
            type: 'POST',
            data: formData,
            dataType: 'json'
        });

        promise.fail(
            function(response) {
                jQuery.LoadingOverlay("hide");
                submitBtn.prop('disabled', false);
                var message = {"error": [response.responseText]};
                licence.renderMessage(message);
            }
        ).done(
            function(response) {
                jQuery.LoadingOverlay("hide");

                if (response)
                {
                    if (!response.success && response.message) {
                        var message = { "error": [response.message]};
                        licence.renderMessage(message);
                        submitBtn.prop('disabled', false);
                    }

                    if (response.success) {
                        var message = {"success": [response.data.msg]};
                        licence.renderMessage(message);

                        // Redirect on organisations view
                        setTimeout(function()
                        {
                            window.location = response.data.redirectUrl;
                        }, 3000);
                    }
                }
            }
        );
    },
    checkExistingLicence: function(fieldObj) {
        var submitBtn = jQuery("#save_licence");
        submitBtn.prop('disabled', false);
        Joomla.removeMessages();

        var agencyId  = jQuery(fieldObj).val();
        var url       = Joomla.getOptions("system.paths").root + "/index.php?option=com_multiagency&task=licenceform.checkExistingLicence&format=json";
        var promise   = jQuery.ajax({
            url: url,
            type: 'POST',
            data: {'agencyId':agencyId},
            dataType: 'json'
        });

        promise.fail(
            function(response) {
                // Ajax request failed
            }
        ).done(
            function(response) {
                if (response)
                {
                    if (response.success) {
                        jQuery('.licenceinfo-template-html').html(response.data.licenceInfoHtml);
                        jQuery('#jform_start_date').attr('minDate', response.data.nextDate);
                        jQuery('#jform_start_date').attr('data-alt-value', response.data.nextDate);
                        jQuery("#jform_start_date").val(response.data.nextDate);
                        jQuery("#jform_end_date").val(response.data.nextDate);
                        jQuery('#jform_end_date').attr('data-alt-value', response.data.nextDate);
                    }
                }
            }
        );
    },
    showLoader: function() {
        jQuery.LoadingOverlay("show", {
            image : Joomla.getOptions('system.paths').root + "/media/com_tjcertificate/images/loader/loader.gif",
        });
    },
    renderMessage: function(msg) {
        Joomla.renderMessages(msg);
        jQuery("html, body").animate({
            scrollTop: 0
        }, 1000);
    },
    closePopup: function() {
        window.parent.SqueezeBox.close();
    }
};


function getRelatedJobTitles(field, relatedrole){

    var agencyId         = jQuery(field).val();
    
    var fieldId = field.id;
    fieldId = fieldId.split("__");
    var jfromId = fieldId[1].replace("agency_role_map", "");
    var relatedRoleField = jQuery('#jform_agency_role_map__agency_role_map' + jfromId + '__jobtitle');

    jQuery.ajax({
    url: Joomla.getOptions('system.paths').base + '/index.php?option=com_dpe&task=school.getJobTitleByCluster&tmpl=component',
        type: 'POST',
        data:  {agencyId:agencyId},
        dataType:'json',
        success: function(response)
        {
            
            if (response.success == true)
            {  
                relatedRoleField.find('option').remove().end().append(response.data);
                relatedRoleField.trigger("chosen:updated");
            }
        },
        error: function(jqXHR, textStatus, errorThrown)
        {
            returnvar   = false;
        }
   });  
}

jQuery(document).ready(function(){

    var countSubform = jQuery('.subform-repeatable-group').length;
    var organisationId=[];
   
    for(var index=0; index < countSubform;index++)
    { 
       organisationId[index] = jQuery( "#jform_agency_role_map__agency_role_map"+index+"__client_id").chosen().val();
    }
 
 if (organisationId!== undefined) 
 {  
    for(var orgcount=0; orgcount < organisationId.length;orgcount++)
    { 
         (function(index){
          jQuery.ajax({
            url: Joomla.getOptions('system.paths').base + '/index.php?option=com_dpe&task=school.getJobTitleByCluster&tmpl=component',
                type: 'POST',
                data:  {agencyId:organisationId[orgcount]},
                dataType:'json',
                success: function(responses)
                {     
                    if (responses.success == true)
                    {   
                         var userId = jQuery('#itemId').val();
                         jQuery.ajax({
                         url: Joomla.getOptions('system.paths').base + '/index.php?option=com_dpe&task=school.getJobtitleByuserDetails&tmpl=component',
                         type: 'POST',
                         async: false,
                         data:  {agencyId:organisationId[index],userid:userId},
                         dataType:'json',
                         success: function(response)
                         {
                            if (response.success == true)
                            { jQuery( "#jform_agency_role_map__agency_role_map"+index+"__jobtitle").find('option').remove().end().append(responses.data);
                                
                                if (response.data.ucm_id !== null)
                                {
                                    jQuery( "#jform_agency_role_map__agency_role_map"+index+"__jobtitle").val(response.data.ucm_id).trigger("chosen:updated");
                                }
                                else{
                                jQuery( "#jform_agency_role_map__agency_role_map"+index+"__jobtitle").val(responses.data).trigger("chosen:updated");
                                }
                              jQuery( "#jform_agency_role_map__agency_role_map"+index+"__jobtitle").trigger("chosen:updated");
                            }
                                if(response.data.dpelead == 1)
                                {
                                    jQuery("#jform_agency_role_map__agency_role_map" + index + "__dpelead0").prop('checked',true);

                                }else
                                {   
                                    var radioCount = parseInt(index) + 1;
                                    jQuery("#jform_agency_role_map__agency_role_map" + radioCount + "__dpelead1").prop('checked',true);
                                }
                          },
                            error: function(jqXHR, textStatus, errorThrown)
                            {
                               returnvar   = false;
                               }
                            });                      
                    }
                },
                error: function(jqXHR, textStatus, errorThrown)
                {
                    returnvar   = false;
                }
           });
       })(orgcount);
    }
}
})

/**
 * Sends an AJAX request to fetch TODO items from the queue
 * where the "assigned_by" in the body JSON matches the current user ID
 * and "properties" JSON contains {"client":"jlike.todos"}.
 *
 * @param {number} userID - The ID of the current user to match against "assigned_by"
 */
function fetchTodosAssignedByUser(userID) {

	jQuery.ajax({
		url: "index.php?option=com_dpe&task=lesson.getTodosFromQueue&format=json", // Call to Joomla controller task
		type: "POST",
		data: { userID: userID },   // Send userID to server
		dataType: 'json',
		success: function (response) {

			if (response && response.success) {

				const messageHtml = `
					<div id="copySuccessMessage"
						class="position-fixed bottom-0 end-0 mb-4 p-3 rounded shadow"
						style="background-color: #5ec576; color: #fff; z-index: 1050; max-width: 550px;
						height: 80px;
						margin-right: 80px;">
						
						<button type="button" class="btn-close btn-close-white"
							style="position: absolute; top: 8px; right: 8px; filter: brightness(0) invert(1);"
							aria-label="Close" onclick="this.parentElement.remove();"></button>

						<div class="d-flex justify-content-between align-items-start h-100">
                            <div class="flex-grow-1 pe-2 mt-3 d-flex align-items-center" style="font-size: 20px; font-weight: 600;">
                                ${response.data.message}
                            </div>
						</div>
					</div>
				`;

				document.body.insertAdjacentHTML("beforeend", messageHtml);
			}
		},
		error: function (xhr, status, error) {
			console.error("AJAX Error:", error);
		}
	});
}

function showSystemMessage(type, heading, message) {
    const container = document.getElementById('system-message-container');
    
    // Clear previous messages
    container.innerHTML = '';

    // Create alert div
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    
    // Close button
    const closeBtn = document.createElement('button');
    closeBtn.type = 'button';
    closeBtn.className = 'btn-close';
    closeBtn.setAttribute('data-bs-dismiss', 'alert');
    closeBtn.setAttribute('aria-label', 'Close');
    
    // Heading
    const h4 = document.createElement('h4');
    h4.className = 'alert-heading';
    h4.textContent = heading;

    // Message
    const msgDiv = document.createElement('div');
    const p = document.createElement('p');
    p.textContent = message;
    msgDiv.appendChild(p);

    // Assemble
    alertDiv.appendChild(closeBtn);
    alertDiv.appendChild(h4);
    alertDiv.appendChild(msgDiv);

    container.appendChild(alertDiv);
}

jQuery(document).ready(function($) {
    $('.edit-licence-btn').on('click', function() {
        var licenceId = $(this).data('id');
        var itemId    = $(this).data('itemid');
        var loader    = $('#loader-overlay');
         var JoomlaToken = $(this).data('token'); 
        loader.show();

        $.ajax({
            url: 'index.php?option=com_dpe&task=school.getLicenceDetails',
            type: 'POST',
            data: { id: licenceId },
            dataType: 'json',
            success: function(response) {
                loader.hide();

                if (response.success) {
                    var data = response.data;

                    // Directly call another AJAX with this data
                    var saveUrl = Joomla.getOptions("system.paths").root +
                        "/index.php?option=com_multiagency&task=licenceform.save&format=json";

                    $.ajax({
                        url: saveUrl,
                        type: 'POST',
                        data: {'formdata':data,
							[JoomlaToken]: 1 // send token
						}, // sending fetched data directly
                        dataType: 'json',
                        success: function(response) {
                            if (response.data.success) {
								showSystemMessage('success', 'Success', 'Licence Saved successfully');

								setTimeout(function() {
									window.location.href = response.data.redirectUrl;
								}, 2000);

							} else {
								showSystemMessage('danger', 'Error', 'You can add a new licence only after the current licence expires.');
							}
                        },
                        error: function(xhr, status, error) {
                            console.error('Error saving licence: ' + error);
                        }
                    });
                } else {
                    console.error('Error fetching licence: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                loader.hide();
                console.error('Error fetching licence: ' + error);
            }
        });
    });
});

jQuery(function () {
    // Ported from mod_jmailalertguestsubscription
    if (typeof JMailAlertsConfig !== 'undefined') {
        
        jQuery('.alertname').click(function(event) {
           // event.preventDefault(); // Prevents checkbox state from changing
        });

        jQuery('#userunsubscribe').click(function(){
            var unsubemail = jQuery('#unsubemail').val();
            if(!unsubemail) {
                alert(JMailAlertsConfig.lang.email_not_found);
                return false;
            }

            jQuery.ajax({
                url: Joomla.getOptions("system.paths").root + "/index.php?option=com_dpe&task=users.unsubJmailAlert&format=json",
                type: "POST",
                data: {'emailid':unsubemail},
                dataType: 'json',
                success:function(response) {   
                    if(response.data == true) {   
                        alert(response.message);
                        location.reload();
                    }
                }
            });
        });

        const closeBtn = document.getElementById('modalCloseBtn');
        if (closeBtn) {
            closeBtn.addEventListener('click', function () {
                const modalElement = document.getElementById('unsubscribeModal');
                const modalInstance = bootstrap.Modal.getInstance(modalElement) || new bootstrap.Modal(modalElement);
                modalInstance.hide();
                jQuery('.modal-backdrop').remove();
            });
        }

        const unsubscribeModal = document.getElementById('unsubscribeModal');
        if (unsubscribeModal) {
            unsubscribeModal.addEventListener('shown.bs.modal', function () {
              setTimeout(() => {
                document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
              });
            });
        }

        // Handle main subscribe button click
        jQuery('#main_subscribe_btn').click(function() {
            var email = jQuery('#user_email').val();
            var name = jQuery('#user_name').val();
            
            if (!email || !name) {
                alert(JMailAlertsConfig.lang.fill_name_email);
                return;
            }

            var subBtn = jQuery(this);
            var originalBtnText = subBtn.text();
            subBtn.prop('disabled', true);

            jQuery.ajax({
                url: Joomla.getOptions("system.paths").root + "/index.php?option=com_jmailalerts&task=sendOtp",
                type: "POST",
                data: {
                    'user_email': email,
                    'user_name': name
                },
                dataType: "json",
                success: function(data) {
                    if (data.status === 'success') {
                        var otpModal = new bootstrap.Modal(document.getElementById('otpModal'));
                        otpModal.show();
                        subBtn.prop('disabled', false).text(originalBtnText);
                    } else {
                        jQuery('#otp_msg').html('<span class="otp-error-msg">' + data.message + '</span>');
                        subBtn.prop('disabled', false).text(originalBtnText);
                    }
                },
                error: function(error) {
                    console.error("Error:", error);
                    subBtn.prop('disabled', false).text(originalBtnText);
                }
            });
        });

        // Handle Resend button from modal
        jQuery('#resend_otp_modal_btn').click(function() {
            var email = jQuery('#user_email').val();
            var resendBtn = jQuery(this);
            resendBtn.prop('disabled', true);

            jQuery.ajax({
                url: Joomla.getOptions("system.paths").root + "/index.php?option=com_jmailalerts&task=sendOtp",
                type: "POST",
                data: {
                    'user_email': email,
                    'user_name': name
                },
                dataType: "json",
                success: function(data) {
                    resendBtn.prop('disabled', false).text(JMailAlertsConfig.lang.resend_otp);
                    if (data.status === 'success') {
                        jQuery('#otp_modal_banner_alert').removeClass('otp-modal-banner-error').addClass('alert-success');
                        jQuery('#otp_modal_banner_text').text(JMailAlertsConfig.lang.otp_sent_success);
                        jQuery('#otp_modal_msg').html(''); 
                    } else {
                        jQuery('#otp_modal_banner_alert').removeClass('alert-success').addClass('otp-modal-banner-error');
                        jQuery('#otp_modal_banner_text').text(data.message);
                        jQuery('#otp_modal_msg').html('');
                    }
                },
                error: function() {
                    resendBtn.prop('disabled', false).text(JMailAlertsConfig.lang.resend_otp);
                }
            });
        });

        // Handle OTP verification/Subscribe from modal
        jQuery('#verify_otp_btn').click(function() {
            var code = jQuery('#otp_code_modal').val();
            var email = jQuery('#user_email').val();
            
            if (code.length < 6) {
                jQuery('#otp_modal_banner_alert').removeClass('alert-success').addClass('otp-modal-banner-error');
                jQuery('#otp_modal_banner_text').text(JMailAlertsConfig.lang.enter_6_digit);
                return;
            }

            var verifyBtn = jQuery(this);
            verifyBtn.prop('disabled', true);

            jQuery.ajax({
                url: Joomla.getOptions("system.paths").root + "/index.php?option=com_jmailalerts&task=verifyOtpAjax",
                type: "POST",
                data: {
                    'user_email': email,
                    'otp_code': code
                },
                dataType: "json",
                success: function(data) {
                    if (data.status === 'success') {
                        jQuery('#otp_code').val(code);
                        jQuery('#otp_modal_banner_alert').removeClass('otp-modal-banner-error').addClass('alert-success');
                        jQuery('#otp_modal_banner_text').text(data.message);
                        setTimeout(function() {
                            jQuery('#adminform').submit();
                        }, 1000);
                    } else {
                        jQuery('#otp_modal_banner_alert').removeClass('alert-success').addClass('otp-modal-banner-error');
                        jQuery('#otp_modal_banner_text').text(data.message);
                        verifyBtn.prop('disabled', false).text(JMailAlertsConfig.lang.subscribe);
                    }
                },
                error: function(error) {
                    console.error("Error:", error);
                    verifyBtn.prop('disabled', false).text(JMailAlertsConfig.lang.subscribe);
                }
            });
        });
    }
});