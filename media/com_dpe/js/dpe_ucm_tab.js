jQuery(document).ready(function () {

    jQuery(window).off('beforeunload');

    window.onbeforeunload = null
    var url = window.location.href;
    var urlObject = new URL(url);
    var fragment = urlObject.hash;
    var tabId = fragment.slice(1);


    if (url.indexOf("itemform") !== -1) {


        if (tabId.length > 0) {
            var activetabId = tabId.replace(/%20/g, " ");

            // Find all div elements inside the tjucm_myTabTabs div

            // Find all div elements inside the tjucm_myTabTabs div where data-active="active"
            jQuery('#tjucm_myTabContent').find('div.tab-pane').each(function () {
                // Get the ID of each div element
                var id = jQuery(this).attr('id');

                jQuery('#' + id).removeClass('active');

                if (id == activetabId) {
                    jQuery('#' + id).addClass('active');
                }

            });

            // Find all li elements with an anchor tag and anchor tags with the class nav-link
            jQuery('li > a.nav-link').each(function () {
                // Get the text content of each anchor tag
                var tabHref = jQuery(this).attr('href');
                jQuery(this).removeClass('active');

                if (fragment == tabHref) {
                    jQuery(this).addClass('active');
                }
            });

        }


    }
    else {

        if (tabId.length > 0) {

            var activetabId = tabId.replace(/%20/g, "");
            activetabId = '#' + tabId.replace(/-/g, "");
            contentTabId = tabId.replace(/-/g, "");

            jQuery('.nav-tabs a').each(function () {
                var tabHref = jQuery(this).attr('href').toLowerCase(); // Get the href value of the <a> tag
                jQuery(this).parent('li').removeClass('active');

                if (tabHref == activetabId.toLowerCase()) {
                    // Add the 'active' class to its parent <li> element
                    jQuery(this).parent('li').addClass('active');
                }
            });

            jQuery('.tab-content').find('div.tab-pane').each(function () {
                // Get the ID of each div element
                var id = jQuery(this).attr('id');

                jQuery('#' + id).removeClass('active');

                if (id.toLowerCase() == contentTabId.toLowerCase()) {
                    jQuery('#' + id).addClass('active');
                }

            });

        }
    }

});



jQuery(document).ready(function ($) {

    var parentWin = window.parent;
    var $parent = parentWin.jQuery;



    // Export click handler
    $('#export-users-btn').off('click').on('click', function (e) {
        e.preventDefault();

        var slaFilter = $parent('#filter_sla_filter').val() || '';
        var columns = [];

        $('input[name="export_columns[]"]:checked').each(function () {
            columns.push($(this).val());
        });

        var agencies = $parent('#filter_agencies').val() || '';
        var search = $parent('#filter_search').val() || '';
        var role_id = $parent('#filter_role_id').val() || '';
        var tags = $parent('#filter_tags').val() || [];

        // Filename prefix
        var filenamePrefix = 'users_export';

        if (agencies && agencies !== '0' && agencies !== 'all') {
            var agencyName = $parent('#filter_agencies option:selected').text().trim();
            if (agencyName) {
                filenamePrefix = agencyName.replace(/[^a-z0-9]/gi, '_').toLowerCase() + '_users';
            }
        } else if (Array.isArray(tags) && tags.length > 0) {
            var tagNames = [];
            $parent('#filter_tags option:selected').each(function () {
                tagNames.push($(this).text().trim());
            });

            if (tagNames.length) {
                filenamePrefix = tagNames.join('_')
                    .substring(0, 50)
                    .replace(/[^a-z0-9]/gi, '_')
                    .toLowerCase() + '_users';
            }
        }

        var baseUrl = 'index.php?option=com_dpe&task=users.export&format=json';
        var params = new URLSearchParams();

        if (agencies) params.append('agencies', agencies);
        if (search) params.append('filter_search', search);
        if (role_id) params.append('role_id', role_id);
        if (slaFilter) params.append('sla_filter', slaFilter);
        if (columns.length) params.append('export_columns', columns.join(','));

        if (Array.isArray(tags)) {
            tags.forEach(tag => params.append('filter[tags][]', tag));
        } else if (tags) {
            params.append('filter[tags][]', tags);
        }

        var fullUrl = baseUrl + '&' + params.toString();

        $('#export-messages').hide().text('');
        var $btn = $('#export-users-btn');
        var originalText = $btn.text();

        $btn.prop('disabled', true);

        fetch(fullUrl)
            .then(function (response) {
                var contentType = response.headers.get('content-type') || '';

                if (contentType.indexOf('application/json') !== -1) {
                    return response.json().then(function (data) {
                        if (!data.success) {
                            throw new Error(data.message);
                        }
                        return data;
                    });
                }

                return response.blob().then(function (blob) {
                    var url = window.URL.createObjectURL(blob);
                    var a = document.createElement('a');

                    a.href = url;
                    a.download =
                        filenamePrefix + '_' +
                        new Date().toISOString().slice(0, 19).replace(/[-T:]/g, '') +
                        '.xls';

                    document.body.appendChild(a);
                    a.click();
                    a.remove();

                    $btn.prop('disabled', false).text(originalText);
                    parent.document.getElementById('sbox-btn-close')?.click();
                });
            })
            .catch(function (error) {

                var errorHtml =
                    '<span>' + error.message + '</span>' +
                    '<button type="button" ' +
                    'style="position:absolute; right:10px; top:50%; transform:translateY(-50%); ' +
                    'background:none; border:none; font-size:20px; line-height:1; cursor:pointer;" ' +
                    'onclick="jQuery(\'#export-messages\').hide();">&times;</button>';

                $('#export-messages')
                    .html(errorHtml)
                    .show();

                $btn.prop('disabled', false).text(originalText);
            });
    });
});


// Global function for Export Button Validation
window.validateAndExport = function (link, alertMessage) {
    var $tags = $('#filter_tags');
    var $cluster = $('#filter_agencies');

    var selectedTags = $tags.val() || [];
    var selectedCluster = $cluster.val() || '';

    // Define "empty" for cluster if value is empty or 'all' (assuming 'all' means no specific filter selected for this context, 

    var hasAgencies = (selectedCluster && selectedCluster !== '' && selectedCluster != 'all');
    var hasTags = (selectedTags.length > 0);

    if (!hasAgencies && !hasTags) {
        alert(alertMessage);
        return;
    }

    openspotlight(link);
}



// School Import Logic
jQuery(document).ready(function () {
    // Only execute if the school import element exists
    if (!jQuery("#school-file-input").length) {
        return;
    }

    var elementId = "school-file-input"; // ID of the school file input element
    var schoolImport = 0; // 0 for school import, 1 for user import

    // Check if multiAgency.UI.Import exists
    if (typeof multiAgency === 'undefined' || typeof multiAgency.UI === 'undefined' || typeof multiAgency.UI.Import === 'undefined') {
        console.error('multiAgency.UI.Import not defined');
        return;
    }

    var importObj = new multiAgency.UI.Import(elementId, schoolImport);

    // Store the uploaded fileName globally
    var uploadedFileName = null;

    // Override the resumable target for school import
    importObj.getResumable().opts.target = 'index.php?option=com_dpe&task=schoolimport.uploadCSV&tmpl=component';

    // Override the importUsers method to prevent it from being called
    importObj.importUsers = function (fileName) {

        return false;
    };

    // Override any method that might show confirmation dialogs
    if (typeof importObj.showConfirmation === 'function') {
        importObj.showConfirmation = function () {
            return false;
        };
    }

    // Override sprintf method to prevent confirmation dialogs
    if (typeof importObj.sprintf === 'function') {
        var originalSprintf = importObj.sprintf;
        importObj.sprintf = function (text, ...args) {
            if (text && text.includes('COM_MULTIAGENCY_IMPORT_STAFF_CONFIRM')) {
                return '';
            }
            return originalSprintf.apply(this, arguments);
        };
    }

    // Add file selection handler
    jQuery('#school-file-input').change(function () {
        var fileInput = this;
        if (fileInput.files && fileInput.files.length > 0) {
            var file = fileInput.files[0];
            jQuery("#school-file-name").html(file.name);
            // Reset uploaded filename when new file is selected
            uploadedFileName = null;
            jQuery("#downloadSchoolCSVLog").hide();
            jQuery("#schoolInfoText").removeClass().html("");
        }
    });

    // Override file added event for school-specific validation
    importObj.getResumable().on('fileAdded', function (file, event) {
        // Reset previous messages and state
        jQuery("#downloadSchoolCSVLog").hide();
        jQuery("#school-file-name").html("");
        jQuery("#schoolInfoText").removeClass().html("");
        uploadedFileName = null; // Reset uploaded file

        // Show selected file name
        jQuery("#school-file-name").html(file.fileName);
    });

    // Store original alert and confirm functions
    var originalConfirm = window.confirm;
    var originalAlert = window.alert;

    // Block alerts and confirms globally for multiAgency
    window.confirm = function (msg) {
        return false;
    };

    window.alert = function (msg) {
        return false;
    };

    // Override file success event for school import
    importObj.getResumable().on('fileSuccess', function (file, message) {

        // Temporarily disable ALL alert and confirm dialogs during file success
        window.confirm = function (msg) {
            return false;
        };

        window.alert = function (msg) {
            return false;
        };

        var res = JSON.parse(message);

        if (res.success == true) {
            // Check if file upload is complete and fileName is available
            if (res.data && res.data.fileUpload && res.data.fileUpload.complete == 1 && res.data.fileUpload.fileName) {
                uploadedFileName = res.data.fileUpload.fileName;

                // Auto start import
                jQuery("#schoolInfoText").html("").addClass("alert alert-warning").html(Joomla.JText._("COM_DPE_SCHOOL_IMPORT_IMPORTING")).show();
                jQuery('#schoolloader').show();

                importSchools(uploadedFileName);
            } else {
                console.log('File upload condition failed. Data:', res.data);
            }
        } else {
            jQuery("#schoolInfoText").html("");
            jQuery("#schoolInfoText").addClass("alert alert-error").show();
            jQuery("#schoolInfoText").append(res.message + "<br/>");
            jQuery('#schoolloader').hide();
        }

        // Restore original alert and confirm functions after a short delay
        setTimeout(function () {
            window.confirm = originalConfirm;
            window.alert = originalAlert;
        }, 500);
    });

    jQuery("#dpe-school-csv-upload").click(function (e) {
        e.preventDefault(); // Prevent form submission

        // Check if we have an uploaded file
        if (uploadedFileName) {

            // Temporarily restore confirm for our dialog
            window.confirm = originalConfirm;
            var userConfirmed = window.confirm(Joomla.JText._("COM_DPE_SCHOOL_IMPORT_CONFIRM"));

            if (userConfirmed == true) {
                jQuery("#schoolInfoText").html("").addClass("alert alert-warning").html(Joomla.JText._("COM_DPE_SCHOOL_IMPORT_IMPORTING")).show();
                jQuery('#schoolloader').show();
                jQuery("#dpe-school-csv-upload").prop('disabled', true).text(Joomla.JText._("COM_DPE_SCHOOL_IMPORT_IMPORTING"));

                importSchools(uploadedFileName);
            }
        } else {
            var filename = jQuery('#school-file-name').text();
            if (filename) {
                // ADDED CONFIRMATION HERE
                window.confirm = originalConfirm;
                var userConfirmed = window.confirm(Joomla.JText._("COM_DPE_SCHOOL_IMPORT_CONFIRM"));

                if (userConfirmed == true) {
                    jQuery("#schoolInfoText").html("").addClass("alert alert-warning").html(Joomla.JText._("COM_DPE_SCHOOL_IMPORT_UPLOADING")).show();
                    jQuery('#schoolloader').show();
                    jQuery("#dpe-school-csv-upload").prop('disabled', true).text(Joomla.JText._("COM_DPE_SCHOOL_IMPORT_UPLOADING"));

                    // Start the resumable upload
                    importObj.getResumable().upload();
                }
            } else {
                // Temporarily restore alert for this message
                window.alert = originalAlert;
                alert(Joomla.JText._("COM_DPE_SCHOOL_IMPORT_SELECT_FILE"));
            }
        }
    });

    // Override file added event for school-specific validation
    importObj.getResumable().on('fileAdded', function (file, event) {
        // Reset previous messages and state
        jQuery("#downloadSchoolCSVLog").hide();
        jQuery("#school-file-name").html("");
        jQuery("#schoolInfoText").removeClass().html("");
        uploadedFileName = null; // Reset uploaded file

        // Show selected file name
        jQuery("#school-file-name").html(file.fileName);
    });

    function importSchools(fileName) {
        jQuery.ajax({
            url: "index.php?option=com_dpe&task=schoolimport.importSchools&tmpl=component",
            type: "POST",
            data: { fileName: fileName },
            dataType: 'json', // Expect JSON response
            success: function (response) {
                // Response is already parsed as JSON due to dataType: 'json'
                var res = response;
                if (res.success === true) {
                    showSchoolLog(res.data);
                    downloadSchoolLogLink(fileName);
                    // Reset for next import
                    uploadedFileName = null;
                    jQuery("#dpe-school-csv-upload").prop('disabled', false).text(Joomla.JText._("COM_DPE_IMPORT_SCHOOL_BTN"));
                } else {
                    jQuery("#schoolInfoText").html("");
                    jQuery("#schoolInfoText").addClass("alert alert-error").show();
                    jQuery("#schoolInfoText").append(res.message + "<br/>");
                    // Re-enable button on error
                    jQuery("#dpe-school-csv-upload").prop('disabled', false).text(Joomla.JText._("COM_DPE_IMPORT_SCHOOL_BTN"));
                }
                jQuery('#schoolloader').hide();
            },
            error: function (xhr, status, error) {
                jQuery("#schoolInfoText").html("");
                jQuery("#schoolInfoText").addClass("alert alert-error").show();
                jQuery("#schoolInfoText").append(Joomla.JText._("COM_DPE_SCHOOL_IMPORT_JS_ERROR") + error + "<br/>");
                jQuery('#schoolloader').hide();
                // Re-enable button on error
                jQuery("#dpe-school-csv-upload").prop('disabled', false).text(Joomla.JText._("COM_DPE_IMPORT_SCHOOL_BTN"));
            }
        });
    }

    function showSchoolLog(log) {
        jQuery("#schoolInfoText").html("");

        // Mandatory columns missing
        if (log.miss_cols >= 1) {
            jQuery("#schoolInfoText").addClass("alert alert-error");
            jQuery("#schoolInfoText").append(Joomla.JText._("COM_DPE_SCHOOL_IMPORT_COLUMN_MISSING") + "<br/>");
            return;
        }

        var logText = [];

        // Total records
        logText.push(importObj.sprintf(Joomla.JText._("COM_DPE_SCHOOL_IMPORT_TOTAL_RECORDS"), log.total_records));

        // Newly Assigned (New Schools)
        if (log.new_schools) {
            logText.push(importObj.sprintf(Joomla.JText._("COM_DPE_SCHOOL_IMPORT_NEW_SCHOOLS"), log.new_schools));
        } else {
            logText.push(Joomla.JText._("COM_DPE_SCHOOL_IMPORT_NO_NEW_SCHOOLS"));
        }

        // Invalid records
        if (log.invalid_records) {
            logText.push(importObj.sprintf(Joomla.JText._("COM_DPE_SCHOOL_IMPORT_INVALID_RECORDS_LOG"), log.invalid_records));
        }

        jQuery("#schoolInfoText").addClass("alert alert-info");
        jQuery("#schoolInfoText").html(logText.join("<br/>"));
    }

    function downloadSchoolLogLink(fileName) {
        var link = "index.php?option=com_multiagency&task=userimport.downloadLog&tmpl=component&fileName=" + fileName;
        jQuery("#downloadSchoolCSVLog").show().attr("href", link);
    }
});