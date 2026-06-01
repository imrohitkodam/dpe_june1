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

					sessionStorage.setItem("ropProcessCheck", this.checked ? "1" : "0");
					handleRopProcessCheckChange();
					/*
                    var url = Joomla.getOptions('system.paths').base + '/index.php?option=com_tjucm&view=items&client=com_tjucm.rop';
                    business_function = jQuery('#business_function').val();

                    if (jQuery(this).prop("checked") == true) {
                        window.location = url + '&business_function=' + business_function + '&filter_process=generic&Itemid=' + menuItemId;
                    } else {
                        window.location = url + '&business_function=' + business_function + '&Itemid=' + menuItemId;
                    }
                    */
                });
  				jQuery("#ropProcessHighLevelCheck").click(function() {

					sessionStorage.setItem('ropProcessHighLevelCheck', this.checked ? '1' : '0');
					handleRopHighLevelCheckChange();

 					

					/*
                    var url = Joomla.getOptions('system.paths').base + '/index.php?option=com_tjucm&view=items&client=com_tjucm.rop';
                    business_function = jQuery('#business_function').val();

                    if (jQuery(this).prop("checked") == true) {
                        window.location = url + '&business_function=' + business_function + '&filter_process=generic&Itemid=' + menuItemId;
                    } else {
                        window.location = url + '&business_function=' + business_function + '&Itemid=' + menuItemId;
                    }
                    */
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
            var tags = jQuery("#tags").chosen().val();

    
		    if ( tags )
		    { 	jQuery.each(tags, function(key,val)
		    	{             
		           formData.push({ name: "tags[]", value:val });      
		        });  
		    	
		    }   

            jQuery.ajax({
                    url: Joomla.getOptions('system.paths').base + "/index.php?option=com_tjucm",
                    type: "POST",
                    data: formData,
                    dataType: 'json'
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
							jQuery('#recordcounter' + el.data('accordian-id')).removeClass('hide');
                        }
                        else
                        {
                            jQuery('#rop-loadmore' + currentRecord).removeClass('hide');
                            jQuery('#recordcounter' + el.data('accordian-id')).removeClass('hide');
							jQuery('#recordcounter' + el.data('accordian-id')).html( newlimit + limit + ' / ' + total);
                        }
						 jQuery(".rop-documents").chosen({max_selected_options: 1});
                    }
                }).fail(function(result) {

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
		openUcmPopups: function(url, element = '') {
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
        deleteMultipleItems: function(element) {

			var elementForm = jQuery(element).data('target-form');
			var redirectUrl = jQuery('#ropForm').attr('action');
			var client      = jQuery('#client').val();
			var recordIds   = [];

			if ( jQuery( "input[name='cid[]']:checked").length > 0) {

					if (!confirm(Joomla.JText._('COM_TJUCM_DELETE_MESSAGE'))) {
						 return false;
					}
				}
				else
				{ 
					
                alert(Joomla.JText._('COM_TJUCM_NO_ITEM_SELECTED'));
				return false;
				}

			jQuery("#" + elementForm + " input[name='cid[]']").each(function() {

				if (jQuery(this).prop("checked") == true) {
					recordIds.push(jQuery(this).val());
				}
			});

            	if (recordIds.length >= 1)
            	{
            		jQuery("#ropCopyLoader").remove("hide");
				document.querySelector("#ropCopyLoader").classList.remove("hide"); // DPE Hack
				document.querySelector("#ropCopyLoader").style.bottom = '80%';
				// Update parent IDs
				jQuery.ajax({
					url: Joomla.getOptions("system.paths").root + "/index.php?option=com_dpe&task=tjucm.remove&format=json",
					type: "POST",
					data: {'recordIds':recordIds,'client':client},
					dataType: 'json',
					complete: function()
					{
						jQuery("#ropCopyLoader").addClass("hide");
					},
				}).done(function(data) {
					
					if (data && (data.success == true))
					{
							document.querySelector("#ropCopyLoader").classList.add("hide"); // DPE Hack
							Joomla.renderMessages({"success":[data.message]});


							var timmer = 1;

							setInterval(function()
							{
								timmer = (timmer - 1);

								if (timmer == 0)
								{
									recordIds.forEach(function(id) {
                            // Use the id to find elements and add CSS
                            var element =  document.querySelector('.row' + id);
                            if (element) {
                            	if (element) {
                            		element.parentNode.removeChild(element); 
                            	}

                            }
                        });
									// window.document.location.reload(true);
								}
							}, 2000);

						}
						else
						{

							Joomla.renderMessages(data.messages);
						}
					}).fail(function(result) {
					})
					.always(function() {
						// el.removeClass('btn-loading');
						document.querySelector("#ropCopyLoader").classList.add("hide"); // DPE Hack
					});
			}
		},
        closePopup: function(message) {
			var timmer = 1;

			setInterval(function()
			{
				timmer = (timmer - 1);

				jQuery("#countermsg").removeClass("d-none");

				if (timmer == 0)
				{
					window.parent.parent.SqueezeBox.close();
				}
			}, 3000);

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

			document.querySelector("#ropCopypopCover").addClass("hide");
			document.querySelector("#ropCopyLoader").removeClass("hide");

			jQuery.ajax({
				url: Joomla.getOptions('system.paths').base + "/index.php?option=com_tjucm&format=json&task=itemform.copyItem&cluster_list="+clusterId,
				type: "POST",
				dataType: 'json',
				data: jQuery.param({ 'client':client, 'filter':filterArr, 'cid':recordIdsArr}),
				contentType: 'application/x-www-form-urlencoded; charset=UTF-8',headers: {'X-CSRF-Token': Joomla.getOptions('csrf.token', '')},
				complete: function()
				{
					document.querySelector("#ropCopyLoader").addClass("hide");
					document.querySelector("#ropCopypopCover").removeClass("hide");
				},
			}).done(function(response)
			{

				Joomla.renderMessages({"success":[response.message]});
				tjucm.itmes.closePopup(response.message);
			}).fail(function(result)
			{
				jQuery('.no-items-result').removeClass("hide");
			});

		},
		copyItemCoreData: function()
		{
			var isMasterList     = 1;
			var redirectUrl      = document.getElementById('ropCopyRedirectUrl').value;
			var recordTitle      = jQuery('#recordTitle').val();
			var clusterId        = jQuery('#clusterId').val();
			var fieldGroupValues = jQuery('#fieldGroupValues').val();
			var fieldGroupCount  = jQuery('#fieldGroupCount').val();
			var client           = jQuery('#client').val();
			var recordIds        = document.getElementById('recordIds').value;
			var recordIdsArr     = recordIds.split(",");
			var isROP            = parseInt(window.parent.parent.document.getElementById('isROPForm').value);

			if (isROP == 0)
			{
				clusterId = window.parent.parent.document.getElementById('cluster_id').value;
			}

			filterArr = [];
			filterArr["cluster_list"] = clusterId;

			if (fieldGroupValues === "" && fieldGroupCount > 1)
			{
				alert(Joomla.JText._("COM_DPE_SELECT_FIELDSET_FOR_COPY"));
				return false;
			}

			if (fieldGroupValues && fieldGroupCount > 1)
			{
				fieldGroupValues = jQuery('#fieldGroupValues').val().split(',');
			}

			jQuery("#ropCopypopCover").addClass("hide");
			jQuery("#ropCopyLoader").removeClass("hide");

			jQuery.ajax({
				url: Joomla.getOptions('system.paths').base + "/index.php?option=com_tjucm&format=json&task=itemform.copyItem&cluster_list="+clusterId,
				type: "POST",
				dataType: 'json',
				data: jQuery.param({ 'client':client, 'filter':filterArr, 'cid':recordIdsArr, 'fieldGroupValues':fieldGroupValues,'isMasterList':isMasterList, 'recordTitle':recordTitle}),
				contentType: 'application/x-www-form-urlencoded; charset=UTF-8',headers: {'X-CSRF-Token': Joomla.getOptions('csrf.token', '')},
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
			});

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
	
	const tabId = jQuery(this).data('accordian-id'); // Tab index

	sessionStorage.setItem("ropBusinessFunctionId", tabId);

	
	handleUcmLoadMoreTabClick(jQuery(this)); // Wrap in jQuery
});

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
    // el.addClass('isloading');

    var container = jQuery('.ucm-loadmore-tab-content' + el.data('accordian-id')).empty();

    var form = jQuery('#' + el.data('target-form'));
    // Always reset the limit and limitstart
    form.find('[name=limitstart]').val(0);
    form.find('[name=total]').val(0);
    var limit = parseInt(form.find('[name=limit]').val());
    limit = limit ? limit : 20;
    form.find('[name=limit]').val(limit);
    jQuery('#business_function').val(el.data('business-function'));

	if (jQuery('#filterSearch_'+el.data('accordian-id')).val().length != '')
	{
		form.find('[name=field_data]').val('');
		jQuery('#ropBusinessFunctionLi'+  el.data('accordian-id')).removeClass('active');
	}
	else
	{
		jQuery(".nav-tabs li").removeClass("active"); 
		jQuery('#ropBusinessFunctionLi'+  el.data('accordian-id')).addClass('active');
	}

    var formData = form.serializeArray(); 
    
    // DPE Hack 
    var tags = jQuery("#tags").chosen().val();

   	var clusterId = jQuery('input[name="filter[cluster_id]"]').val();
    
    if ( tags && (clusterId === 'all'))
    { 	
    	jQuery.each(tags, function(key,val)
    	{             
           formData.push({ name: "tags[]", value:val });      
        });  
    	
    }   

    jQuery.ajax({
        url: Joomla.getOptions('system.paths').base + "/index.php?option=com_tjucm",
        type: "POST",
        dataType: 'json',
        data: formData
    }).done(function(data) {
        if (data && (data.success == true)) {
            // jQuery('.no-items-result').addClass("hide");
            form.find('[name=total]').val(data.data.total);
            form.find('[name=limitstart]').val(0);

            if (data.data.html != undefined) {
                container.html(data.data.html);
                jQuery(document).find('.hasPopover').popover();
               // form.find('[name=loaded]').val(true);
            }

            var limit = parseInt(form.find('[name=limit]').val());
            limit = limit ? limit : 20;
            form.find('[name=limit]').val(limit)

			var ordering = form.find('[name=filter_order_Dir]').val();
			var ropRequestStatus = form.find('[name=request_status_field_value]').val();
			var ropProcessStatus = form.find('[name=exisitng_process_field_value]').val();

			if (ordering == 'desc')
			{
				jQuery('.date-of-review').addClass('icon-arrow-down-3').removeClass('icon-arrow-up-3');
			}
			else
			{
				jQuery('.date-of-review').addClass('icon-arrow-up-3').removeClass('icon-arrow-down-3');
			}

			if (ropRequestStatus)
			{
				jQuery('.rop-request-status').val(ropRequestStatus);
			}

			if (ropProcessStatus)
			{
				jQuery('.rop-process-status').val(ropProcessStatus);
			}

            if (data.data.total > limit)
            {
				jQuery('#recordcounter' + el.data('accordian-id')).html(limit + ' / ' + data.data.total);
                jQuery('#recordcounter' + el.data('accordian-id')).removeClass('hide');
                jQuery('#rop-loadmore' + el.data('accordian-id')).removeClass('hide');
            }else
            {
				if (parseInt(data.data.total) === 0)
				{
					jQuery('#recordcounter' + el.data('accordian-id')).addClass('hide');
					jQuery('#rop-loadmore' + el.data('accordian-id')).addClass('hide');
				}
				else
				{
					jQuery('#recordcounter' + el.data('accordian-id')).html(data.data.total + ' / ' + data.data.total);
					jQuery('#rop-loadmore' + el.data('accordian-id')).addClass('hide');
				}

                // jQuery('#rop-loadmore' + el.data('accordian-id')).addClass('hide');
            }

		jQuery(".rop-documents").chosen({max_selected_options: 1});
        } else {
            jQuery('.no-items-result').removeClass("hide");
        }
    }).fail(function(result) {
        jQuery('.no-items-result').removeClass("hide");
    }).always(function() {
        el.removeClass('isloading');
    });
}

function openDocumentPopup(url)
{
	var wwidth = jQuery(window).width() - 200;
	var wheight = jQuery(window).height() - 200;

	if (url) {

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
	
}


function deleteROP(accordion)
{
	jQuery('#task' + accordion).val("tjucm.delete")
	jQuery('#controller' + accordion).val("tjucm")
	jQuery('#option' + accordion).val("com_dpe")
	alert(accordion);
	alert('Delete In progress');
}


function handleRopProcessCheckChange() {


	const form = $('#ropBusinessFunctionForm' + $('#activeAccordion').val());
	const isChecked = $('#ropProcessCheck').is(':checked');

	if (!isChecked) {
		$('#rop_cluster_field_cover').show();
		form.find('input[name="filter[process]"]').val('myprocess');
	} else {
		$('#rop_cluster_field_cover').hide();
		form.find('input[name="filter[process]"]').val('generic');
	}

	const isCategoryChecked = $('#ropProcessHighLevelCheck').is(':checked');
	form.find('input[name="process_category"]').val(isCategoryChecked ? 'High Level' : '');

	const el = $('#ropBusinessFunctionAccordian' + $('#activeAccordion').val());
	ucmRopLoadData(el);
}

function handleUcmLoadMoreTabClick(el) {
	var form = jQuery('#' + el.data('target-form'));

	jQuery('#activeAccordion').val(el.data('accordian-id'));

	var ischecked = jQuery('#ropProcessCheck').is(':checked');
	form.find('input[name="filter[process]"]').val(ischecked ? 'generic' : 'myprocess');

	var isCategoryChecked = jQuery('#ropProcessHighLevelCheck').is(':checked');
	form.find('input[name="process_category"]').val(isCategoryChecked ? 'High Level' : '');

	var prevAccId = jQuery('.currentActive').data('accordian-id');
	var prevFormElement = jQuery("#ropBusinessFunctionForm" + prevAccId);

	if (jQuery('#filterSearch_' + prevAccId).val()) {
		jQuery('#filterSearch_' + prevAccId).val('');
		document.getElementById("ropProcessList_" + prevAccId).innerHTML = "";
		prevFormElement.find('[name=loaded]').val('false');
		prevFormElement.find('[name=field_data]').val(prevFormElement.find('[name=field_data]').data('field-data'));
	}

	jQuery('.currentActive').addClass('hide').removeClass('currentActive');
	jQuery('#panelBody' + el.data('accordian-id')).removeClass('hide').addClass('currentActive');

	if (form.find('[name=loaded]').val() === 'true') {
		return;
	}

	ucmRopLoadData(el);
}

function handleRopHighLevelCheckChange() {
	var form = jQuery('#ropBusinessFunctionForm'+jQuery('#activeAccordion').val());

	var ischecked= jQuery('#ropProcessHighLevelCheck').is(':checked');

	if(ischecked)
	{
		// jQuery('#rop_cluster_field_cover').hide();
		form.find('input[name="process_category"]').val('High Level');
	}
	else
	{
		form.find('input[name="process_category"]').val('');
	}
	var el = jQuery('#ropBusinessFunctionAccordian' + jQuery('#activeAccordion').val());

	ucmRopLoadData(el);
}
