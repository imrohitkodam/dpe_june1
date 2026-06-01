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
                    var url = Joomla.getOptions('system.paths').base + '/index.php?option=com_tjucm&view=items&client=com_tjucm.rop';

                    if (jQuery(this).prop("checked") == true) {
                        window.location = url + '&filter_process=generic&Itemid=' + menuItemId;
                    } else {
                        window.location = url + '&Itemid=' + menuItemId;
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
			}, 1000);

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
			var recordIdsArr = recordIds.split(",");
			filterArr = [];
			filterArr["cluster_list"] = clusterId;

			jQuery.ajax({
				url: Joomla.getOptions('system.paths').base + "/index.php?option=com_tjucm&format=json&task=itemform.copyItem&clusterId="+clusterId,
				type: "POST",
				dataType: 'json',
				data: jQuery.param({ 'client':"com_tjucm.rop", 'filter':filterArr, 'cid':recordIdsArr}),
				contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
			}).done(function(response)
			{

				Joomla.renderMessages({"success":[response.message]});
				tjucm.itmes.closePopup(response.message);
			}).fail(function(result)
			{
				jQuery('.no-items-result').removeClass("hide");
				console.log(result);
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
    var el = jQuery(this);
    var form = jQuery('#' + el.data('target-form'));

    if (form.find('[name=loaded]').val() == 'true') {
        return;
    }

    ucmRopLoadData(el);
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
