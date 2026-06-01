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
 				jQuery("#ucmprocesschecked").click(function() {
 					var url = window.location.href;

 					if (jQuery(this).prop("checked") == true) {
 						window.location = url + '&filter_process=generic&Itemid=' + menuItemId;
 					} else {
 						window.location = url + '&Itemid=' + menuItemId;
 					}
 				});
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
 				document.querySelector("#ropCopyLoader").removeClass("hide");

				// Update parent IDs
				jQuery.ajax({
					url: Joomla.getOptions("system.paths").root + "/index.php?option=com_dpe&task=tjucm.remove&format=json",
					type: "POST",
					data: {'recordIds':recordIds,'client':client},
					dataType: 'json',
					complete: function()
					{
						
					},
				}).done(function(data) {
					
					if (data && (data.success == true))
						{ document.querySelector("#ropCopyLoader").classList.add("hide");
					Joomla.renderMessages({"success":[data.message]});

					var timmer = 1;

					setInterval(function()
					{
						timmer = (timmer - 1);

						if (timmer == 0)
						{
							o.forEach(function(id) {
                            // Use the id to find elements and add CSS
                            var element =  document.querySelector('.row' + id);
                            if (element) {
                            	if (element) {
                            		element.parentNode.removeChild(element); 
                            	}

                            }
                        });
									//window.document.location.reload(true);
								}
							}, 1000);

				}
				else
				{

					Joomla.renderMessages(data.messages);
				}
			}).fail(function(result) {
			})
			.always(function() {
						// el.removeClass('btn-loading');
					});
		}
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
		var recordId = jQuery("#recordId").val();
		var ucmdataid = jQuery("#ucmdataid").val();
		var clutername = jQuery("#clutername").val();
		url = url + '&ucmdataidcopy=' + ucmdataid;
		url = url + '&clutername=' + clutername;
		url = url + '&ucmdataid=' + recordId;

		SqueezeBox.open(url, {
			handler: 'iframe',
			size: {
				x: window.innerWidth - 200,
				y: window.innerHeight - 200
			},
			classWindow: 'tjucm-copy-process',
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
	copyItemMasterList: function()
	{
		var isMasterList = 1;
		var client       = jQuery('#client').val();
		var recordId     = window.parent.document.getElementById('recordId').value;
		var recordIds    = document.getElementById('recordIds').value;
		var clusterId    = 0;
		var recordIdsArr = recordIds.split(",");
		filterArr        = [];
		var isROP        = parseInt(window.parent.document.getElementById('isROPForm').value);

		if (isROP == 0)
		{
			clusterId = window.parent.document.getElementById('cluster_id').value;
		}

		ucmdataid = document.getElementById('ucmdataid').value;

		if (!ucmdataid)
		{
			alert(Joomla.JText._('COM_TJUCM_ROP_COPY_SELECT_RECORD_VALIDATION_MSG'));
			return false;
		}

		if (recordId && isROP == 1)
		{
			document.querySelector("#ropCopypopCover").addClass("hide");
			document.querySelector("#ropCopyLoader").removeClass("hide");

			// Get Cluster ID
			jQuery.ajax({
				url: Joomla.getOptions("system.paths").root + "/index.php?option=com_dpe&task=tjucm.getClusterId&format=json",
				type: "POST",
				data: {'recordId':recordId},
				dataType: 'json',
				complete: function()
				{
						// Add code here
					},
				}).done(function(data) {
					if (data && (data.success == true))
					{
						if (data.data.clusterId != undefined)
						{
							clusterId = data.data.clusterId;
							filterArr["cluster_list"] = clusterId;

							jQuery.ajax({
								url: Joomla.getOptions('system.paths').base + "/index.php?option=com_tjucm&format=json&task=itemform.copyItem&cluster_list="+clusterId,
								type: "POST",
								dataType: 'json',
								data: jQuery.param({ 'client':client, 'filter':filterArr, 'cid':recordIdsArr,'isMasterList':isMasterList}),
								contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
								complete: function()
								{
									document.querySelector("#ropCopyLoader").addClass("hide");
									document.querySelector("#ropCopypopCover").removeClass("hide");
								},
							}).done(function(response)
							{

								Joomla.renderMessages({"success":[response.message]});

								var timmer = 1;

								setInterval(function()
								{
									timmer = (timmer - 1);

									jQuery("#countermsg").removeClass("d-none");

									if (timmer == 0)
									{
										window.parent.SqueezeBox.close();
									}
								}, 3000);

							}).fail(function(result)
							{
								jQuery('.no-items-result').removeClass("hide");
								console.log(result);
							});
						}
					}
				}).fail(function(result) {
					// console.log(result);
				})
				.always(function() {
					// el.removeClass('btn-loading');
				});
			}
			else if (clusterId)
			{
				document.querySelector("#ropCopypopCover").addClass("hide");
				document.querySelector("#ropCopyLoader").removeClass("hide");

				filterArr["cluster_list"] = clusterId;

				jQuery.ajax({
					url: Joomla.getOptions('system.paths').base + "/index.php?option=com_tjucm&format=json&task=itemform.copyItem&cluster_list="+clusterId,
					type: "POST",
					dataType: 'json',
					data: jQuery.param({ 'client':client, 'filter':filterArr, 'cid':recordIdsArr,'isMasterList':isMasterList}),
					contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
					complete: function()
					{
						document.querySelector("#ropCopyLoader").addClass("hide");
						document.querySelector("#ropCopypopCover").removeClass("hide");
					},
				}).done(function(response)
				{

					Joomla.renderMessages({"success":[response.message]});

					var timmer = 1;

					setInterval(function()
					{
						timmer = (timmer - 1);

						jQuery("#countermsg").removeClass("d-none");

						if (timmer == 0)
						{
							window.parent.SqueezeBox.close();
						}
					}, 3000);

				}).fail(function(result)
				{
					jQuery('.no-items-result').removeClass("hide");
					console.log(result);
				});
			}
			else if (!clusterId && !isROP)
			{
				Joomla.renderMessages({"error":[Joomla.JText._('COM_TJUCM_ROP_COPY_SELECT_RECORD_CLUSTER_VALIDATION_MSG')]});

				var timmer = 1;

				setInterval(function()
				{
					timmer = (timmer - 1);

					jQuery("#countermsg").removeClass("d-none");

					if (timmer == 0)
					{
						window.parent.SqueezeBox.close();
					}
				}, 2000);
			}
		},
	}
}

jQuery(document).on('click', '.ucm-loadmore-tab', function(e) {
	var el = jQuery(this);
	var el1 = jQuery("#ropBusinessFunctionAccordian1");

	console.log(el);
	console.log(el1);

	var form = jQuery('#' + el.data('target-form'));

	jQuery('.currentActive').addClass('hide').removeClass('currentActive');
	jQuery('#panelBody' + el.data('accordian-id')).removeClass('hide').addClass('currentActive');

	if (form.find('[name=loaded]').val() == 'true') {
		return;
	}

	ucmGenericLoadData(el);
});

jQuery(document).on('change', '.ucm-rop-search', function(e) {
	var el = jQuery(this);
	ucmGenericLoadData(el);
});

function ucmGenericLoadData(target) {
	var el = target;
	el.addClass('isloading');

	var container = jQuery('#ropProcessList').empty();

	var form = jQuery('#ropBusinessFunctionForm');
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
    	data: formData
    }).done(function(data) {
    	if (data && (data.success == true)) {
    		jQuery('.no-items-result').addClass("hide");
    		form.find('[name=total]').val(data.data.total);
    		form.find('[name=limitstart]').val(0);

    		console.log('total');
    		console.log(data.data.total);

    		if (data.data.html != undefined) {
    			container.html(data.data.html);
               // console.log(data.data.html);
               // jQuery(document).find('.hasPopover').popover();
               form.find('[name=loaded]').val(true);
           }

           var limit = parseInt(form.find('[name=limit]').val());
           limit = limit ? limit : 20;
           form.find('[name=limit]').val(limit)



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
