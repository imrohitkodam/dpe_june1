jQuery(document).ready(function()
{
	jQuery('#MyWizard').on('change', function(e, data)
	{
		values=jQuery('#selectionSubsPlanForm').serialize();
		var ref_this = jQuery("#tjlms-steps li.active");

		var stepId = ref_this[0].id;

		if(stepId === "id_step_payment_info")
		{
			jQuery('#btnWizardNext').hide();
		}
		else
		{
			jQuery('#btnWizardNext').show();
		}

		if(stepId==="id_step_select_subsplan" && data.direction === 'next')
		{
			if (jQuery('#coupon_chk').is(':checked'))
			{
					var cop_applied_div = jQuery('#dis_cop').is(":visible");

					if (!cop_applied_div)
					{
						var msg = Joomla.JText._('COM_TJLMS_WANTED_TO_APPLY_COP_BUT_NOT_APPLIED');
						alert(msg);

						return false;
					}
			}

			jQuery.ajax({
				url: root_url+ 'index.php?option=com_tjlms&task=buy.save_step_select_subsplan&tmpl=component',
				type: 'POST',
				data:values,
				dataType: 'json',
				async:'false',
				beforeSend: function() {
					 loadingImage();
				},
                fail: function() {
					hideImage();
					alert("Something went wrong.");
                },
                success: function(e) {
					hideImage();
					if (e.failure == 1)
					{
						jQuery("#MyWizard").wizard("previous");
						alert(e.message);
					}
					else
					{
						jQuery("html, body").animate({
							scrollTop: 0
						}, 500);

						/** global: tjanalytics */
						if (typeof tjanalytics !== "undefined")
						{
							tjanalytics.ga.addProduct(e.ecTrackingData);
							tjanalytics.ga.setAction(e.ecTrackingData['0']);
						}
					}
                }
			});
		}

		if(stepId === "id_step_billing_info" && data.direction === 'next')
		{
			var f = document.billing_info_form;
			if (!document.formvalidator.isValid(f))
			{
				var req_validation = Joomla.JText._('COM_TJLMS_REQUIRE_FIELDS');
				alert(req_validation);
				jQuery('#btnWizardNext').show();
				jQuery("html, body").animate({ scrollTop: 0 }, 500);

				return false;
			}
			else if (jQuery("#accpt_terms").length > 0)
			{
				if(document.getElementById('accpt_terms').checked == true)
				{
					jQuery('#btnWizardNext').show();
				jQuery("html, body").animate({ scrollTop: 0 }, 500);
				}
				else
				{
					jQuery('#btnWizardNext').show();
					var terms_condition = Joomla.JText._('COM_TJLMS_TERMS_AND_CONDITION');
					alert(terms_condition);

					return false;
				}
			}

			values=jQuery('#billing_info_form').serialize();
			jQuery.ajax({
				url: root_url+ 'index.php?option=com_tjlms&task=buy.save_step_billinginfo&tmpl=component',
				type: 'POST',
				data:values,
				dataType: 'json',
				async:false,
				beforeSend: function()
				{
					loadingImage();
				},
				complete: function()
				{
					hideImage();
				},
				success: function(data)
				{
					if(data.tnc == 0)
					{
						var terms_condition = Joomla.JText._('COM_TJLMS_TERMS_AND_CONDITION');
						alert(terms_condition);
						return false;
					}

					/*Now Set Inner Html of Step No2 to Fill Attendee Fields*/
					if(data.payment_html)
					{
						jQuery('#step_payment_info').html(data.payment_html);
						jQuery('#system-message-container').html('');

						if (data.single_gateway)
						{
							/** global: tjlms */
							tjlms.gateway.gatewayHtml(data.single_gateway,data.order_id);
						}
					}
					if(data.redirect_invoice_view)
					{
						document.location=data.redirect_invoice_view;
					}

					/** global: tjanalytics */
					if (typeof tjanalytics !== "undefined")
					{
						tjanalytics.ga.addProduct(data.ecTrackingData);
						tjanalytics.ga.setAction(data.ecTrackingData['0']);
					}
			   },

			});
		}
	});

	jQuery('#MyWizard').on('changed', function(e, data) {

		var thisactive = jQuery("#tjlms-steps li.active");
		stepthisactive = thisactive[0].id;
		if(stepthisactive == jQuery("#tjlms-steps li").first().attr('id'))
			jQuery(".tjlms-form #btnWizardPrev").hide();
		else
			jQuery(".tjlms-form #btnWizardPrev").show();

		if(stepthisactive == jQuery("#tjlms-steps li").last().attr('id')){
			jQuery(".tjlms-form .prev_next_wizard_actions").hide();
		}
		else
		{
			jQuery(".tjlms-form .prev_next_wizard_actions").show();
			jQuery('#btnWizardNext').show();

		}

	});

	jQuery('#MyWizard').on('finished', function(e, data) {
		var ref_this = jQuery("#tjlms-steps li.active");
		var stepId = ref_this[0].id;

	});

	jQuery('#btnWizardPrev').on('click', function() {
		 var ref_this = jQuery("#tjlms-steps li.active");
		var stepId = ref_this[0].id;
		jQuery('#btnWizardNext').show();
	  jQuery('#MyWizard').wizard('previous');
	});

	jQuery('#btnWizardNext').on('click', function() {
		jQuery('#MyWizard').wizard('next','foo');

	});

	jQuery('#btnWizardStep').on('click', function() {
	  var item = jQuery('#MyWizard').wizard('selectedItem');
	});

	jQuery('#MyWizard').on('stepclick', function(e, data) {
		var ref_this = jQuery("#tjlms-steps li.active");
		var stepId = ref_this[0].id;
		if(stepId==="id_step_payment_info")
		{
			jQuery('#btnWizardNext').show();
		}

	});

	/*optionally navigate back to 2nd step*/
	jQuery('#btnStep2').on('click', function(e, data) {
	  jQuery('[data-target=#step2]').trigger("click");
	});

});
