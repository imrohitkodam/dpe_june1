/**
 * @package    TJUCM
 *
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2019 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

/**
 * Front end JavaScript
 */
var tjucm = {
    itmes: {
        /* Initialize items list js*/
        init: function() {
            jQuery(document).ready(function() {
                jQuery("#ropProcessCheck").click(function() {
                    business_function = jQuery('#business_function').val();

                    var url = Joomla.getOptions('system.paths').base + '/index.php?option=com_tjucm&view=items&client=com_tjucm.rop';

                    if (jQuery(this).prop("checked") == true) {
                        window.location = url + '&business_function=' + business_function + '&filter_process=generic&Itemid=' + menuItemId;
                    } else {
                        window.location = url + '&business_function=' + business_function + '&Itemid=' + menuItemId;
                    }
                });
            });
        },

        scrolledLoadMoreItems: function(el) {
            el.addClass('isloading');
            var currentRecord = el.data('accordian-id');

            var form = jQuery("#ropBusinessFunctionForm" + currentRecord);
            form.find('[name=task]').val("items.loadMore");
            var limit = parseInt(form.find('[name=limit]').val());
            limit = limit ? limit : 20;

            var total = parseInt(form.find('[name=total]').val());
            var limitstart = parseInt(form.find('[name=limitstart]').val());
            var newlimit = limitstart + limit;
            form.find('[name=limitstart]').val(newlimit);

            if (newlimit >= total) {
                jQuery('#rop-loadmore' + currentRecord).addClass('hide');
                el.removeClass('isloading');
                return;
            }

            var formData = form.serializeArray();

            jQuery.ajax({
                url: Joomla.getOptions('system.paths').base + "/index.php?option=com_tjucm",
                type: "POST",
                data: formData,
                dataType: 'json',
                headers: {'X-CSRF-Token': Joomla.getOptions('csrf.token', '')},
            }).done(function(data) {
                if (data && (data.success == true)) {
                    var table = jQuery('#' + form.prop('id') + ' table').children('tbody');
                    var tbody = jQuery(jQuery.parseHTML(data.data.html)).find('tbody');
                    jQuery(table).append(jQuery(tbody[0]).html());
                    jQuery(document).find('.hasPopover').popover();
                    form.find('[name=total]').val(data.data.total);

                        // Avoid new call
                    if ((newlimit + limit) >= total)
                    {

                     jQuery('#recordcounter' + el.data('accordian-id')).html( total + ' / ' + total);
                     jQuery('#rop-loadmore' + currentRecord).addClass('hide');
                 }
                 else
                 {
                    jQuery('#rop-loadmore' + currentRecord).removeClass('hide');
                    jQuery('#recordcounter' + el.data('accordian-id')).html( newlimit + limit + ' / ' + total);
                }
                jQuery(".rop-documents").chosen({max_selected_options: 1});
            }
        }).fail(function(result) {
            console.log(result);

                    // Revert the limitstart
            var newlimit = Math.abs(limitstart - limit);
            form.find('[name=limitstart]').val(newlimit);

            if (newlimit < total) {
                jQuery('#rop-loadmore' + currentRecord).removeClass('hide');
            }
        })
        .always(function() {
            el.removeClass('isloading');
        });
    },

    openRopPopups: function(url, element = '') {
        var isCopy = url.indexOf("iscopy=1");

        if (isCopy !== -1) {
            if (element == '') {
                return;
            }
            var elementForm = jQuery(element).data('target-form');
            var recordIds = [];

            jQuery("#" + elementForm + " input[name='cid[]']").each(function() {

                if (jQuery(this).prop("checked") == true) {
                    recordIds.push(jQuery(this).val());
                }
            });

            if (recordIds.length >= 1)
            {
               url += '&recordIds=' + JSON.stringify(recordIds);
           }
       }

       SqueezeBox.open(url, {
        handler: 'iframe',
        size: {
            x: window.innerWidth - 200,
            y: window.innerHeight - 200
        },
        classWindow: 'tjucm-addprocess-doc',
    });
   },
   openMasterlistPopups: function(url, element = '') {
      jQuery('.create-new-parent').removeClass('create-new-parent');

      SqueezeBox.open(url, {
        handler: 'iframe',
        size: {
            x: window.innerWidth - 200,
            y: window.innerHeight - 200
        },
        classWindow: 'tjucm-addprocess-doc',
    });
  },
  closePopup: function(message) {
     var timmer = 1;

     setInterval(function()
     {
        timmer = (timmer - 1);

        jQuery("#countermsg").removeClass("d-none");

        if (timmer == 0)
        {
           window.parent.document.location.reload(true);
           window.parent.SqueezeBox.close();
       }
   }, 2000);

 },
 createRopProcess: function() {

    var redirectUrl = jQuery('#ropForm').attr('action');
    var clusterId = jQuery('.cluster-info').val();
    var isCopy = jQuery('#copyRop').val();
    var recordId = jQuery('#recordId').val();

    if (jQuery.trim(clusterId) != '' && jQuery.isNumeric(clusterId)) {
        if (jQuery.isNumeric(isCopy) && isCopy == 1 && jQuery.isNumeric(recordId)) {
            redirectUrl += '&id=' + recordId;
        }

        redirectUrl += '&cluster_id=' + clusterId;
        parent.document.location.href = redirectUrl;
        window.parent.SqueezeBox.close();
    } else {
        alert(Joomla.JText._("COM_DPE_SELECT_SCHOOL_ROP"));
    }
},
copyItemRop: function()
{
    var redirectUrl  = document.getElementById('ropCopyRedirectUrl').value;
    var clusterId    = jQuery('.cluster-info').val();
    var recordIds    = document.getElementById('recordIds').value;
    var client       = document.getElementById('client').value;
    var recordIdsArr = recordIds.split(",");
    filterArr = [];
    filterArr["cluster_list"] = clusterId;

    if (!filterArr["cluster_list"] || filterArr["cluster_list"].length === 0)
        {
            alert(Joomla.JText._('COM_DPE_SELECT_SCHOOL_ROP'));
            return false;
        }

    // Show loader progress bar
    jQuery("#ropCopypopCover").addClass("hide");
    // jQuery("#ropCopyLoader").removeClass("hide");
    jQuery("#ropCopyProgress").removeClass("hide");
    jQuery("#ropCopyProgressBar").css("width", "0%");

    var total = recordIdsArr.length;
    var completed = 0;

    function updateProgress() {
        completed++;
        var percent = Math.round((completed / total) * 100);
        jQuery("#ropCopyProgressBar").css("width", percent + "%").text(percent + "%");
    }

    if(total > 20){

    jQuery("#ropCopyProgress").addClass("hide");

            jQuery.ajax({
                url: Joomla.getOptions('system.paths').base + "/index.php?option=com_dpe&format=json&task=tjucm.UcmCopyItem&cluster_list="+clusterId,
                type: "POST",
                dataType: 'json',
                data: jQuery.param({ 'client':client, 'filter':filterArr, 'cid':recordIdsArr}),
                contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
                headers: {'X-CSRF-Token': Joomla.getOptions('csrf.token', '')},
                complete: function()
                {
                jQuery("#ropCopyLoader").addClass("hide");
                jQuery("#ropCopypopCover").removeClass("hide");
            },
        }).done(function(response)
        {
        
            Joomla.renderMessages({"success":[response.message]});
            tjucm.itmes.closePopup(response.message);
        }).fail(function(result)
        {
            jQuery('.no-items-result').removeClass("hide");
            console.log(result);
        });
    }
    else{

            // Simulate progress in loop if you call one record at a time:
            var ajaxCalls = recordIdsArr.map(function(recordId) {
                return jQuery.ajax({
                    url: Joomla.getOptions('system.paths').base + "/index.php?option=com_tjucm&format=json&task=itemform.copyItem&cluster_list=" + clusterId,
                    type: "POST",
                    dataType: 'json',
                    data: jQuery.param({ 'client': client, 'filter': filterArr, 'cid': [recordId] }),
                    contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
                    headers: { 'X-CSRF-Token': Joomla.getOptions('csrf.token', '') },
                    success: function(response) {
                        updateProgress();
                    }
                });
            });

            // When all AJAX calls complete
            Promise.all(ajaxCalls).then(function(responses) {
                jQuery("#ropCopyLoader").addClass("hide");
                jQuery("#ropCopyProgress").addClass("hide");
                jQuery("#ropCopypopCover").removeClass("hide");
            
            
                Joomla.renderMessages({ success: [responses[0].message] });
                tjucm.itmes.closePopup(responses[0].message);
            }).catch(function(error) {
                jQuery('.no-items-result').removeClass("hide");
                jQuery("#ropCopyProgress").addClass("hide");
                console.log("Copy failed: ", error);
            });
    }
    
},
        /** Delete ROP Record
         */
deleteItem: function(recordId, client, token) {
    var redirectURL = Joomla.getOptions('system.paths').base + '/index.php?option=com_tjucm&task=itemform.remove&Itemid=' + menuItemId + '&id=' + recordId + '&' + token + '=1&client=' + client;

    if (!confirm(Joomla.JText._('COM_TJUCM_DELETE_MESSAGE'))) {
        return false;
    }

    window.location.href = redirectURL;
},

        /** Delete Master List Records*/
deleteMasterListItem: function(masterRecordId, client, token) {
    var redirectURL = Joomla.getOptions('system.paths').base + '/index.php?option=com_tjucm&task=itemform.remove&Itemid=' + menuItemId + '&id=' + masterRecordId + '&' + token + '=1&client=' + client;

    if (!confirm(Joomla.JText._('COM_TJUCM_DELETE_MESSAGE'))) {
        return false;
    }

    window.location.href = redirectURL;
}
},

itemform: {
        /** ROP Form Next date for review field validation */
    validateROPFormDates: function(currentData) {
        var selectedText = currentData.value;
        var selectedDate = new Date(selectedText);
        var now = new Date();

        if (selectedDate < now) {
            alert(Joomla.JText._('COM_TJUCM_ROP_ITEM_FORM_NEXT_DATE_REVIEW_VALIDATION_MESSAGE'));
            currentData.value = "";
        }
    }
}
}

jQuery(document).on('click', '.ucm-loadmore-tab', function(e) {
    var el = jQuery(this);
    var form = jQuery('#' + el.data('target-form'));

    if (form.find('[name=loaded]').val() == 'true') {
        return;
    }

    ucmRopLoadData(el);
});

/*
jQuery(document).on('change', '.dataFlowParentMainFields', function(e) {


});
*/

jQuery(document).on('change', '.ucm-rop-search', function(e) {
    var el = jQuery(this);
    jQuery('#rop-loadmore' + el.data('accordian-id')).addClass('hide');
    ucmRopLoadData(el);
});

jQuery(document).on('click', '.rop-loadmore', function(e) {
    var el = jQuery(this);
    tjucm.itmes.scrolledLoadMoreItems(el);
});

function ucmRopLoadData(target) {
    var el = target;
    el.addClass('isloading');

    var container = jQuery('.ucm-loadmore-tab-content' + el.data('accordian-id')).empty();
    var form = jQuery('#' + el.data('target-form'));
    // Always reset the limit and limitstart
    form.find('[name=limitstart]').val(0);
    form.find('[name=total]').val(0);
    var limit = parseInt(form.find('[name=limit]').val());
    limit = limit ? limit : 20;
    form.find('[name=limit]').val(limit);
    var formData = form.serializeArray();

    jQuery.ajax({
        url: Joomla.getOptions('system.paths').base + "/index.php?option=com_tjucm",
        type: "POST",
        dataType: 'json',
        headers: {'X-CSRF-Token': Joomla.getOptions('csrf.token', '')},
        data: formData
    }).done(function(data) {
        if (data && (data.success == true)) {
            jQuery('.no-items-result').addClass("hide");
            form.find('[name=total]').val(data.data.total);
            form.find('[name=limitstart]').val(0);

            if (data.data.html != undefined) {
                container.html(data.data.html);
                jQuery(document).find('.hasPopover').popover();
                form.find('[name=loaded]').val(true);
            }

            var limit = parseInt(form.find('[name=limit]').val());
            limit = limit ? limit : 20;
            form.find('[name=limit]').val(limit)

            if (data.data.total > limit)
            {
                jQuery('#recordcounter' + el.data('accordian-id')).html(limit + ' / ' + data.data.total);
                jQuery('#rop-loadmore' + el.data('accordian-id')).removeClass('hide');
            }else
            {
                if (parseInt(data.data.total) === 0)
                {
                   jQuery('#recordcounter' + el.data('accordian-id')).addClass('hide');
               }
               else
               {
                   jQuery('#recordcounter' + el.data('accordian-id')).html(data.data.total + ' / ' + data.data.total);
               }

               jQuery('#rop-loadmore' + el.data('accordian-id')).addClass('hide');
           }

           jQuery(".rop-documents").chosen({max_selected_options: 1});
       } else {
        jQuery('.no-items-result').removeClass("hide");
    }
}).fail(function(result) {
    jQuery('.no-items-result').removeClass("hide");
    console.log(result);
}).always(function() {
    el.removeClass('isloading');
});
}

function openDocumentPopup(url)
{
	var wwidth = jQuery(window).width() - 200;
	var wheight = jQuery(window).height() - 200;

	SqueezeBox.open(url, {
		handler: 'iframe',
		closable: true,
		size: {
			x: wwidth,
			y: wheight
        },
        classWindow: 'tjucm-rop-doc',
    });
}
/**
 * Custom Modal for Export Options (No Bootstrap dependency)
 */
function openExportOptionsModal(onConfirm) {
    if (jQuery('#customExportModalContainer').length > 0) {
        jQuery('#customExportModalContainer').remove();
    }

    var modalHtml = `
    <div id="customExportModalContainer" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 10000; display: flex; align-items: center; justify-content: center; font-family: 'Open Sans', sans-serif;">
        <div id="customExportModalOverlay" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); backdrop-filter: blur(2px);"></div>
        <div id="customExportModalBox" style="position: relative; background: #fff; width: 480px; max-width: 95%; border-radius: 12px; box-shadow: 0 15px 35px rgba(0,0,0,0.25); overflow: hidden; animation: customModalFadeIn 0.3s ease-out;">
            <style>
              
            </style>
            <div style="padding: 22px 25px; border-bottom: 1px solid #f0f0f0; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0; font-size: 19px; font-weight: 700; color: #2c3e50;">Export Options</h3>
                <span id="closeCustomExportModal" style="cursor: pointer; font-size: 28px; color: #bdc3c7; line-height: 1; transition: color 0.2s;">&times;</span>
            </div>
            <div style="padding: 25px;">
                <p style="margin-top: 0; margin-bottom: 25px; color: #7f8c8d; font-size: 15px;">Choose your preferred method for exporting the reports:</p>
                
                <div class="custom-export-option active" data-type="separate">
                    <input type="radio" name="tjExportType" value="separate" checked class="custom-export-radio">
                    <div>
                        <strong style="display: block; color: #2c3e50; margin-bottom: 5px; font-size: 16px;">Separate Files</strong>
                        <span style="font-size: 13.5px; color: #7f8c8d; line-height: 1.4;">Each record will be saved as an individual document.</span>
                    </div>
                </div>
                
                <div class="custom-export-option" data-type="combined">
                    <input type="radio" name="tjExportType" value="combined" class="custom-export-radio">
                    <div>
                        <strong style="display: block; color: #2c3e50; margin-bottom: 5px; font-size: 16px;">One Combined Document</strong>
                        <span style="font-size: 13.5px; color: #7f8c8d; line-height: 1.4;">All selected records will be merged into a single comprehensive file.</span>
                    </div>
                </div>
            </div>
            <div class="custom-export-footer">
                <button type="button" class="custom-export-btn custom-export-btn-cancel" id="cancelCustomExport">Cancel</button>
                <button type="button" class="custom-export-btn custom-export-btn-primary" id="confirmCustomExport">Export Reports</button>
            </div>
        </div>
    </div>`;

    jQuery('body').append(modalHtml);

    // Hover effect for close button
    jQuery('#closeCustomExportModal').hover(
        function () { jQuery(this).css('color', '#34495e'); },
        function () { jQuery(this).css('color', '#bdc3c7'); }
    );

    // Click handler for options
    jQuery('.custom-export-option').on('click', function () {
        jQuery('.custom-export-option').removeClass('active');
        jQuery(this).addClass('active').find('input').prop('checked', true);
    });

    // Close logic
    function hideModal() {
        jQuery('#customExportModalBox').css({
            'opacity': '0',
            'transform': 'translateY(-30px)',
            'transition': 'all 0.2s ease-in'
        });
        jQuery('#customExportModalOverlay').fadeOut(200);
        setTimeout(function () {
            jQuery('#customExportModalContainer').remove();
        }, 250);
    }

    jQuery('#closeCustomExportModal, #customExportModalOverlay, #cancelCustomExport').on('click', hideModal);

    // Confirm logic
    jQuery('#confirmCustomExport').on('click', function () {
        var selectedType = jQuery('input[name="tjExportType"]:checked').val();
        hideModal();
        onConfirm(selectedType);
    });
}


function exportItems(isDpeadmin) {

    if (!document.getElementById('cb')) {

        alert('No record present to export'); return false;
    }
    if (!confirm("Are you sure you want to download all the reports?")) {

        return false;
    }
    let client = jQuery('#client').val();

    const cluster = document.getElementById("cluster");
    const selectedCluster = cluster.value;
    const selectTags = document.getElementById("tags");
    const selectedOptions = Array.from(selectTags.selectedOptions).map(option => option.value);

    let clusterValues = [];

    if ((selectedCluster === "all") && (selectedOptions.length === 0)) {

        if (isDpeadmin) {
            messageDisplay('As a DPE admin, please select a specific organisation to export its report. Exporting records for all organisations at once is not supported.', 'warning');
            return false;
        }
        const select = document.getElementById('cluster');

        if (select) {
            const clusterValues = Array.from(select.options)
                .filter(option => option.value !== "all")
                .map(option => option.value);

            console.log(clusterValues); // Use as needed
        }
    } else {
        // Only selected one
        clusterValues = [selectedCluster];
    }

    const filterSearchEl = document.getElementById("filter_search");
    const tabSearchEl = jQuery('.ucm-rop-search:visible')[0];
    const searchQuery = (tabSearchEl ? tabSearchEl.value : (filterSearchEl ? filterSearchEl.value : '')).trim();

    // Show custom modal instead of confirm
    openExportOptionsModal(function (exportType) {
        const exportData = [];
        const requests = [];
        // const headingText = document.querySelector('.sp-page-title h2').innerHTML;
        const headingEl = document.querySelector('.sp-page-title h2');
        const headingText = headingEl ? headingEl.innerHTML : '';


        const collectedData = [];
        collectedData.push({
            client: jQuery('#client').val(),
            cluster: clusterValues,
            tags: selectedOptions,
            formName: headingText,
            search: searchQuery,
            export_type: exportType
        });
            document.getElementById('loader-overlay').style.display = 'block';

        jQuery.ajax({
            url: Joomla.getOptions("system.paths").root +
                '/index.php?option=com_dpe&task=tjucm.storeReportDataInQueue&format=json',
            type: 'POST',
            data: { 'data': collectedData },
            dataType: 'json'
        }).then(response => {
            if (response.success == true) {
                document.getElementById('loader-overlay').style.display = 'none';

                messageDisplay(response.message, 'success');
            }
        });
    });
}

function parseTabContent(htmlContent) {
    var tempDom = $('<div>').html(htmlContent); // wrap html string as jQuery object
    var uniqueAccordions = new Set();
    var dataToSend = { content: [] }; // Main container

    tempDom.find(".tab-content").find('.tab-pane').each(function () {
        var navBar = jQuery(this).attr('id');
        var tabTitle = tempDom.find('a[href="#' + navBar + '"]').text();
        var content = { title: tabTitle, fields: [] };

        jQuery(this).find('.field-label').each(function () {
            var $fieldLabel = jQuery(this);
            var $fieldData = $fieldLabel.next();
            var $accordion = $fieldLabel.closest('.accordDetail');
            let accordionFormat = '';

            if ($accordion.length > 0 && $accordion.find('.accordspan').length > 0) {
                var accordionData = $accordion.html();
                accordionFormat = $(accordionData).find('span.accordspan').prop('outerHTML');
            }

            if (!uniqueAccordions.has(accordionFormat)) {
                uniqueAccordions.add(accordionFormat);
            } else {
                accordionFormat = '';
            }

            var fieldDataText = $fieldData.prop('outerHTML');
            var $image = $fieldData.find('img');
            var anchorTag = $fieldData.find("a").attr("onclick");
            var imageurl = "";

            if (anchorTag) {
                var match = anchorTag.match(/tjFieldsFileField\.previewMedia\('([^']+)'/);
                if (match && match[1]) {
                    imageurl = match[1];
                }
            }

            var imagePath = $image.length > 0 ? $image.attr('src') : imageurl;
            var fieldFeedback = '';
            var feedbackStyles = '';
            var $feedback = $fieldData.find('.feedbackDetailColor');

            if ($feedback.length > 0) {
                fieldFeedback = $feedback.html();
                feedbackStyles = $feedback.attr('class') || '';
            }

            if ((fieldDataText || '').includes($feedback.text().trim())) {
                fieldDataText = (fieldDataText || '').replace($feedback.text().trim(), '').trim();
            }

            content.fields.push({
                label: $fieldLabel.prop('outerHTML'),
                value: fieldDataText,
                accordion: accordionFormat,
                image: imagePath,
                feedback: fieldFeedback,
                feedbackstyle: feedbackStyles
            });
        });

        dataToSend.content.push(content);
    });

    return dataToSend;
}

function messageDisplay(msg, type){
    jQuery('<div id="system-message-container"></div>').appendTo('#system-message-container');
    Joomla.renderMessages({[type] : [msg]}); 
    jQuery('html, body').animate({ scrollTop: 0 }, 'slow');
    setTimeout(function() {
       jQuery('joomla-alert').fadeOut('slow', function() {
        $(this).remove();
    });
   }, 10000); 
}

function deleteBulkReport(id)
{
    if (!id || id.length < 1)
    {
        messageDisplay('Something went wrong! Please try again later.', 'error');
        return;
    }

     if (!confirm("Are you sure you want to delete this?")) {

        return false;
    }

    jQuery.ajax({
        url: Joomla.getOptions("system.paths").root +
             '/index.php?option=com_dpe&task=ucmbulkdownload.deleteZipFile',
        type: 'POST',
        data: { id: id },
        dataType: 'json',
        headers: {'X-CSRF-Token': Joomla.getOptions('csrf.token', '')},
    }).then(response => {
        if (response.data.success == true)
        {
            messageDisplay(response.data.msg, 'success');

            jQuery('td[data-title="Id' + id + '"]').each(function () 
            {
                if (jQuery(this).text().trim() == id) {
                    jQuery(this).closest('tr').remove();
                }
            });
        }
        else
        {
            messageDisplay(response.data.msg, 'error');
        }
    }).catch(() => {
        messageDisplay('Something went wrong! Please try again later.', 'error');
    });
}

function downloadUcmZip(zipUrl) {
  fetch(zipUrl, { method: 'HEAD' })  // HEAD request to check if the file exists
    .then(response => {
      if (response.ok) {
        // File exists, trigger download
        const link = document.createElement('a');
        link.href = zipUrl;
        link.download = ''; // Optional: specify filename here
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
      } else {
        // File not found
              messageDisplay("Zip file is already expired. You can't downlaod it", 'error');
      }
    })
    .catch(error => {
      // Error (e.g., server unreachable)
      console.error('Error checking zip file:', error);
      alert("An error occurred while checking for the zip file.");
    });
}
function saveJobtitleWithTags(csrfToken) {
    var selectedTags = jQuery("#jform_tags option:selected").map(function () {
        return jQuery(this).val();
    }).get();

    var roleName = jQuery("#jform_com_tjucm_role_name").val() || "";

    if (!selectedTags.length) {
        renderModalAlert('warning', 'Please select at least one tag.');
        return;
    }
    if (!roleName) {
        renderModalAlert('warning', 'Please Provide Job Title.');
        return;
    }

    var $btn = jQuery("#quick-add-submit");
    var $loader = jQuery("#tjucm_loader");
    if ($btn && $btn.length) { $btn.prop("disabled", true); }
    if ($loader && $loader.length) { $loader.show(); }

    var baseUrl = Joomla.getOptions('system.paths').base; // Joomla base path

    jQuery.ajax({
        url: baseUrl + "/index.php?option=com_dpe&task=tjucm.getClusterIdsByTags&format=json",
        type: "POST",
        data: {
            tag_ids: selectedTags.join(","),
            [csrfToken]: "1"
        },
        success: function (response) {
            var clusterIds = [];
            try {
                var res = JSON.parse(response);
                if (res && res.data) {
                    // If data is an object, convert values to array
                    if (Array.isArray(res.data)) {
                        clusterIds = res.data;
                    } else if (typeof res.data === 'object') {
                        clusterIds = Object.values(res.data);// convert object to array
                    }
                }
        
            } catch (e) {
                console.error("Organizations response parse error", e);
            }

            if (!clusterIds.length) {
                renderModalAlert('warning', 'No Organizations found for selected tags.');
                if ($btn && $btn.length) { $btn.prop("disabled", false); }
                if ($loader && $loader.length) { $loader.hide(); }
                return;
            }

            var completed = 0;
            clusterIds.forEach(function (cid) {
                var saveData = new FormData();
                saveData.append("client", "com_tjucm.role");
                saveData.append("com_tjucm_role_clusterclusterid", cid);
                saveData.append(csrfToken, "1");

                jQuery.ajax({
                    url: baseUrl + "/index.php?option=com_tjucm&format=json&task=itemform.save", 
                    type: "POST",
                    data: saveData,
                    processData: false,
                    contentType: false,
                    success: function (res1) {
                        try {
                            var r1 = JSON.parse(res1);
                            if (r1.success) {
                                var recordId = r1.data.id;

                                var sfData = new FormData();
                                sfData.append("jform[id]", recordId);
                                sfData.append("jform[client]", "com_tjucm.role");
                                sfData.append("jform[com_tjucm_role_clusterclusterid]", cid);
                                sfData.append("jform[com_tjucm_role_name]", roleName);
                                sfData.append("recordid", recordId);
                                sfData.append("cluster_id", cid);
                                sfData.append("clusterFieldName", "com_tjucm_role_clusterclusterid");
                                sfData.append("client", "com_tjucm.role");
                                sfData.append(csrfToken, "1");

                                jQuery.ajax({
                                    url: baseUrl + "/index.php?option=com_tjucm&format=json&task=itemform.saveFormData", 
                                    type: "POST",
                                    data: sfData,
                                    processData: false,
                                    contentType: false,
                                    success: function (res2) {
                                        try {
                                            var r2 = JSON.parse(res2);
                                            if (r2.success) {
                                                // ok
                                            }
                                        } catch (e) {
                                            console.error("Invalid JSON from second call", e);
                                        }
                                    },
                                    error: function () {
                                        renderModalAlert('danger', 'Error in second save for cluster ' + cid);
                                    },
                                    complete: function () {
                                        completed++;
                                        if (completed === clusterIds.length) {
                                            if ($loader && $loader.length) { $loader.hide(); }
                                            renderModalAlert('success', 'Job title saved successfully for selected tags.');
                                            setTimeout(function () { 
                                                window.parent ? window.parent.location.reload() : window.location.reload(); 
                                            }, 1000);
                                        }
                                    }
                                });

                            } else {
                                console.log('Failed first save for Organizations ' + cid + ': ' + r1.message);
                                completed++;
                            }
                        } catch (e) {
                            console.error("Invalid JSON response", e);
                            completed++;
                        }
                    },
                    error: function () {
                        renderModalAlert('danger', 'Error while saving cluster ' + cid);
                        completed++;
                    }
                });
            });

        },
        error: function () {
            renderModalAlert('danger', 'Unable to fetch Organizations for selected tags.');
            if ($btn && $btn.length) { $btn.prop("disabled", false); }
            if ($loader && $loader.length) { $loader.hide(); }
        }
    });
}

function renderModalAlert(type, message) {
    var container = jQuery('#modal-message-container');
    if (!container.length) { return; }
    var alertType = (type === 'success') ? 'success' : (type === 'warning') ? 'warning' : 'danger';
    var html = '<joomla-alert type="' + alertType + '" close-text="JCLOSE" dismiss="true" role="alert"><div class="alert-heading"><span class="' + alertType + '"></span><span class="visually-hidden">' + alertType + '</span></div><div class="alert-wrapper"><div class="alert-message">' + message + '</div></div></joomla-alert>';
    container.html(html);
    container.find('.joomla-alert--close').on('click', function () { container.empty(); });
}

function closeParentModal() {
    if (window.parent && window.parent.jQuery) {
        window.parent.jQuery('#job-add-modal').modal('hide');
    } else {
        window.close();
    }
}
	// Open modal with instant spinner, then load iframe
	function openJobTitleModal(url) {
        var html = '\
            <div class="modal fade" id="job-add-modal" tabindex="-1" role="dialog" aria-hidden="true">\
                <div class="modal-dialog job-add-sm" role="document">\
                    <div class="modal-content" style="min-height: 200px; position: relative;">\
                        <div class="job-modal-loader" id="job-modal-loader">\
                            <div class="spinner"></div>\
                        </div>\
                        <iframe id="job-add-iframe" src="about:blank" style="width:100%;height:450px;border:0;display:block;"></iframe>\
                    </div>\
                </div>\
            </div>';
    
        jQuery('#jobtitle-modal-wrapper').html(html);
        jQuery('#job-add-modal').modal('show');
    
        var $iframe = jQuery('#job-add-iframe');
        var $loader = jQuery('#job-modal-loader');
        $loader.show();
    
        // Set source after showing modal for perceived performance
        $iframe.on('load', function() {
            // Hide loader when iframe content is ready
            $loader.fadeOut(150);
        });
        $iframe.attr('src', url);
    }


	jQuery(document).ready(function ($) {
        var $radio = $('input[name="jform[use_tags]"]');
        var $clusterForm = $('.control-group.cluster-form');
        var $tagForm = $('.control-group.tags-form');
        var $tagsSelect = $('#jform_tags');
        var $jobTitleSelect = $('#jform_jobtitle');
        var $clusterInput = $('#jform_cluster_ids'); // hidden field to store cluster client IDs
    
        // ---- Toggle cluster/tag forms ----
        function toggleClusterForm(value) {
            if (value === '1') {

                // NO → Show cluster form, hide tags form
                $clusterForm.show();
                $tagForm.hide();
                    
                $tagForm.find('[name*="[tags]"], [name*="[client_id]"], [name*="[rolelist]"]')
                .removeClass('required').prop('required', false);
                    
                $clusterForm.find('[name*="[client_id]"], [name*="[rolelist]"]')
                .addClass('required').prop('required', true);
               
            } else {

                 // YES → Hide cluster form, show tags form
                 $clusterForm.hide();
                 $tagForm.show();
     
                 $clusterForm.find('[name*="[client_id]"], [name*="[rolelist]"]')
                 .removeClass('required').prop('required', false);
     
                 $tagForm.find('[name*="[client_id]"], [name*="[rolelist]"]')
                 .addClass('required').prop('required', true);

            }
    
            // Reattach Joomla validator
            if (document.formvalidator) {
                document.formvalidator.attachToForm($('#adminForm')[0] || $('form.form-validate')[0]);
            }
        }
    
        var itemId = $('#itemId').val();
        
        
        if(!itemId){
        // Initial toggle
        toggleClusterForm($radio.filter(':checked').val());
        }
        $radio.on('change', function () {

            toggleClusterForm($(this).val());
        });
    
        // ---- Update Job Titles ----
        function updateJobTitles(clusterIds) {
            if (!clusterIds || clusterIds.length === 0) {
                $jobTitleSelect.html('<option value="0">Select Job Title</option>').trigger('chosen:updated');
                return;
            }
    
            $.ajax({
                url: Joomla.getOptions('system.paths').base + '/index.php?option=com_dpe&task=users.getJobTitleByClusters&format=json',
                type: 'POST',
                data: { clusterIds: clusterIds },
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        $jobTitleSelect.empty().append(response.data).trigger('chosen:updated');
                    } else {
                        $jobTitleSelect.html('<option value="0">Select Job Title</option>').trigger('chosen:updated');
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Error fetching job titles:', error);
                }
            });
        }
    
        // ---- Tag change handler ----
        $tagsSelect.on('change', function () {
            var selectedTags = $(this).val() || [];
    
            if (!selectedTags.length) {
                $clusterInput.val('');
                updateJobTitles([]);
                return;
            }
    
            $.ajax({
                url: Joomla.getOptions('system.paths').base + '/index.php?option=com_dpe&task=users.getClusterClientIdsByTags&format=json',
                type: 'POST',
                data: { tag_ids: selectedTags.join(',') },
                dataType: 'json',
                success: function (response) {
                    if (response.success && response.data.activeLicenceClusterClientIds.length) {
                        // Store client IDs in hidden input
                        $clusterInput.val(response.data.activeLicenceClusterClientIds.join(','));
    
                        // Update job titles using activeLicenceClusterIds
                        updateJobTitles(response.data.activeLicenceClusterIds);
                    } else {
                        $clusterInput.val('');
                        updateJobTitles([]);
                        alert('No active Organization found for selected tags.');
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Error fetching clusters:', error);
                }
            });
        });
    
        // Trigger change on load if tags preselected
        if ($tagsSelect.val() && $tagsSelect.val().length) {
            $tagsSelect.trigger('change');
        }
    });
    

