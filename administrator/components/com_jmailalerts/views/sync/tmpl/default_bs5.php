<?php
/**
 * @package     JMailAlerts
 * @subpackage  com_jmailalerts
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2022 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// Do not allow direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

HTMLHelper::_('jquery.framework');
HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('behavior.keepalive');
?>

<?php
// Add Javascript
$doc = Factory::getDocument();
$doc->addScriptDeclaration("
	jQuery(document).ready(function()
	{
		jQuery('.advanceoption').hide();
		loadFrequencies('alert_list');
		jQuery('#batch_size').val(400);
		jQuery('.showhide_progressbar').hide();
	});

	/*Convert the php date format to standard javascript date format*/
	function parseDate(input, format) {
		/*Default format*/
		format = format || 'yyyy-mm-dd';
		var parts = input.match(/(\d+)/g),
		i = 0, fmt = {};

		/*Extract date-part indexes from the format*/
		format.replace(/(yyyy|dd|mm)/g, function(part) { fmt[part] = i++; });

		return new Date(parts[fmt['yyyy']], parts[fmt['mm']]-1, parts[fmt['dd']]);
	}

	function generateFreq(data) {
		var rr = new Date();
		var daterangefrom = jQuery('#last_email_date').val();
		var select = jQuery('#freq_id');
		select.find('option').remove().end();
		options = data.options;

		for (index = 0; index < data.length; ++index) {
		 	var freq = data[index];
			var op = '<option value=\"'  + freq['alertid'] + '\">' + freq['name'] + '</option>';
			jQuery('#freq_id').append(op);
			jQuery('select').trigger('liszt:updated');
			jQuery('#last_email_date').val(freq['last_email_date']);
		}

		jQuery('#freq_id').trigger('chosen:updated');
		jQuery('#freq_id').trigger('liszt:updated');
	}

	function advanceoption_hideshow() {
		if (document.getElementById('advaced_options').checked === true) {
			jQuery('.advanceoption').show();
		}
		else {
			jQuery('.advanceoption').hide();
		}

		/*Hide option readd usub user on document ready call show hide function*/
		ShowHideReaddUnsubsUser();
	}

	function loadFrequencies(alertid) {
		var id = jQuery('#'+alertid).val();

		/*Call ajax function to get list of frequencies*/
		jQuery.ajax({
			url: '" . Uri::base() . "' + 'index.php?option=com_jmailalerts&task=sync.loadFrequencies&alertid=' + id,
			type: 'GET',
			async: false,
			dataType: 'json',
			success: function(data) {
				if (data === undefined || data === null || data.length <= 0) {
					var op = '<option>' + '" . Text::_('COM_JMAILALERTS_FREQUENCIES') . "' + '</option>';
					select = jQuery('#freq_id');
					select.find('option').remove().end();
					select.append(op);
				}
				else {
					generateFreq(data);
				}
			}
		});

		jQuery('#freq_id').trigger('chosen:updated');
		jQuery('#freq_id').trigger('liszt:updated');

		/*Call function to load subscription report*/
		loadSubscriptionReport(id);
	}

	/*Load the Subscription report*/
	/*Global variable for data*/
	var data1, id1;
	function loadSubscriptionReport(id) {
		if (!id) {
			return false;
		}

		jQuery.ajax({
			url: '" . Uri::base() . "' + 'index.php?option=com_jmailalerts&task=sync.getSubscribesCount&alertid=' + id,
			type: 'GET',
			async: false,
			dataType: 'json',
			success: function(data) {
				data1 = data;
				id1 = id;
				getSubscriptionReport(data, id);
			}
		});
	}

	/*Method to generate subscription Report the Subscription report*/
	function getSubscriptionReport(data, id) {
		/*Before Sync*/
		const beforeSyncUserCount = data[id]['registed_users'];
		jQuery('.subs_registerd').html(beforeSyncUserCount);
		// jQuery('.subs_guest').html(data[id]['guest_users']);
		jQuery('.unsub_registerd').html(data[id]['unsubscribed_users']);
		// jQuery('.unsubs_guest').html(data[id]['unsub_guest_users']);
		jQuery('.never_opted_in').html(data[id]['not_opted_user']);

		/*After Sync*/
		var after_sync_subs_registerd, overwrite_user_pref, after_sync_guest, after_sync_unsub_registerd, after_sync_unsub_guest;

		/*Registerd count*/
		after_sync_subs_registerd = parseInt(data[id]['registed_users']);
		// after_sync_guest          = parseInt(data[id]['guest_users']);

		/*Unsub count*/
		after_sync_unsub_registerd = parseInt(data[id]['unsubscribed_users']);
		// after_sync_unsub_guest     = parseInt(data[id]['unsub_guest_users']);

		if (document.adminForm.advaced_options.checked === true) {
			overwrite_user_pref = jQuery('input:radio[name=\"user_pref\"]:checked').val();

			/*If overwrite user pref 'yes' then check the option Re-add unsubscribed user again option value 'Yes/No'*/
			if (overwrite_user_pref != 0) {
				var readd_unsub_user = jQuery('input:radio[name=\"unsub_user\"]:checked').val();

				if (readd_unsub_user != 0) {
					after_sync_subs_registerd = parseInt(data[id]['registed_users']) + parseInt(data[id]['not_opted_user']) + parseInt(data[id]['unsubscribed_users']);
					// after_sync_guest          = parseInt(data[id]['guest_users']) + parseInt(data[id]['unsub_guest_users']);

					// after_sync_unsub_registerd = 0;
					// after_sync_unsub_guest     = 0;
				}
			}
		}
		else {
			/*After normal sync only means no other options*/
		}

		/*After sync registerd user count*/
		jQuery('.after_sync_subs_registerd').html(after_sync_subs_registerd);

		/*After sync guest user count*/
		// jQuery('.after_sync_guest').html(after_sync_guest);

		/*After sync unsub register count*/
		jQuery('.after_unsub_registerd').html(after_sync_unsub_registerd);

		/*After sync unsub guest count*/
		// jQuery('.after_unsub_guest').html(after_sync_unsub_guest);

		/*Calculate TOTAL BEFORE SYNC*/
		var column1_total = parseInt(data[id]['registed_users']) + parseInt(data[id]['unsubscribed_users']) + parseInt(data[id]['not_opted_user']);
		jQuery('.column1_total').html(column1_total);

		var column2_total = parseInt(data[id]['guest_users']) + parseInt(data[id]['unsub_guest_users']);
		// jQuery('.column2_total').html(column2_total);

		/*Calculate TOTAL AFTER SYNC*/
		var column3_total = parseInt(after_sync_subs_registerd) + parseInt(after_sync_unsub_registerd);
		// jQuery('.column3_total').html(column3_total);

		var column4_total = parseInt(after_sync_guest) + parseInt(after_sync_unsub_guest);
		// jQuery('.column4_total').html(column4_total);
	}

	var percent = 0;

	/*set_firs_ajax_call => if it is zero then , this is the first ajax request to get the total number of user to sync*/
	function sync(batch_size, set_firs_ajax_call, completed_batch_users)
	{
		/*Get the selected alert id*/
		let alertid = document.getElementById('alert_list').value;

		if (!alertid) {
			return false;
		}

		jQuery('.showhide_progressbar').show();
		jQuery('.bar').css('width',0+'%');
		jQuery('.completed_percent').html(0+'%');

		setTimeout(sync2(batch_size, set_firs_ajax_call, completed_batch_users), 10000);
	}

	function sync2(batch_size, set_firs_ajax_call, completed_batch_users)
	{
		var xmlhttp;
		var alertid, last_email_date, default_frequency = 0;
		var advanced_options_checked = 0, overwrite_user_pref = 0;
		var readd_unsub_user = 0;

		if (window.XMLHttpRequest) {
			/*Code for IE7+, Firefox, Chrome, Opera, Safari*/
			xmlhttp = new XMLHttpRequest();
		}
		else {
			/*Code for IE6, IE5*/
			xmlhttp = new ActiveXObject('Microsoft.XMLHTTP');
		}

		xmlhttp.onreadystatechange = function() {
			if (xmlhttp.readyState == 4) {
				var server_data = xmlhttp.responseText;

				if (server_data == 'No Users') {
					percent = 100;
					jQuery('.bar').css('width', Math.round(percent) + '%');
					jQuery('.completed_percent').html(Math.round(percent) + '%');
					loadSubscriptionReport(alertid);

					setTimeout(function(){
						alert('The sync process has been completed.');
					}, 500);

					return;
				}
				else if (server_data == 'Insertion error'){
					alert('Some error occured while inserting data into the #__email_alert table. Retry.');

					return;
				}
				else {
					if (set_firs_ajax_call == 0) {
						total_users=server_data;
					}

					set_firs_ajax_call = set_firs_ajax_call + 1;

					/*Calulate the sync completeness percentage*/
					completed_batch_users = parseInt(completed_batch_users) + parseInt(batch_size);
					if (parseInt(completed_batch_users) >= parseInt(total_users)) {
						percent = 100;
						jQuery('.progress-bar').css('width', Math.round(percent) + '%');
						jQuery('.completed_percent').html(Math.round(percent) + '%');

					}
					else {
						percent = (parseInt(completed_batch_users) / parseInt(total_users)) * 100;
						jQuery('.progress-bar').css('width',Math.round(percent) + '%');
						jQuery('.completed_percent').html(Math.round(percent) + '%');
					}

					/*Call recursively sync function for batch size*/
					/*E.g total number of user is 20 & batch size is 5 then 4 times sync is call means 4 timens ajax request*/
					sync2(batch_size, set_firs_ajax_call, completed_batch_users);
				}
			}
		}

		/*Get the selected alert id*/
		alertid = document.getElementById('alert_list').value;
		groupid = document.getElementById('group_list').value;

		if (document.adminForm.advaced_options.checked === true) {
			advanced_options_checked = 1;
			/*Get the last email date being synced*/
			last_email_date = document.getElementById('last_email_date').value;

			/*Get the default frequency id*/
			default_frequency = document.getElementById('freq_id').value;
			batch_size        = document.getElementById('batch_size').value;
			overwrite_user_pref = jQuery('input:radio[name=\"user_pref\"]:checked').val();

			/*If overwrite user pref 'yes' then check the option Re-add unsubscribed user again option value 'Yes/No'*/
 			if (overwrite_user_pref != 0) {
				readd_unsub_user = jQuery('input:radio[name=\"unsub_user\"]:checked').val();
			}
		}

		last_email_date = (last_email_date != undefined)?'&last_email_date=' + last_email_date :'';

		xmlhttp.open(
			'GET',
			'index.php?option=com_jmailalerts&view=ajaxsync&format=raw&set_firs_ajax_call=' + set_firs_ajax_call + '&alertid=' + alertid + '&groupid=' + groupid + '&advanced_options_checked=' + advanced_options_checked + '&default_frequency=' + default_frequency + last_email_date +  '&batch_size=' + batch_size + '&overwrite_user_pref=' + overwrite_user_pref + '&readd_unsub_user=' + readd_unsub_user,
			true
		);

		xmlhttp.send(null);
	}

	function ShowHideReaddUnsubsUser() {
		var status;
		status=jQuery('input:radio[name=\"user_pref\"]:checked').val();

		if (status == 1) {
			jQuery('.ShowHideReaddUnsubsUserCls').show();
		}
		else {
			jQuery('.ShowHideReaddUnsubsUserCls').hide();
		}

		getSubscriptionReport(data1, id1);
	}

	/*Method to change the subscription report value on click of radio options*/
	function chaneSubsreport() {
		getSubscriptionReport(data1, id1);
	}

	/*On click on joomla toolbar button cancel it will redirect to the cp view of jmailalerts*/
	Joomla.submitbutton = function(task) {
		if (task == 'adminForm.cancel') {
			Joomla.submitform(task, document.getElementById('adminForm'));
		}
		else {
			if (task != 'adminForm.cancel' && document.formvalidator.isValid(document.id('adminForm'))) {
				Joomla.submitform(task, document.getElementById('adminForm'));
			}
			else {
				alert('" . $this->escape(Text::_('JGLOBAL_VALIDATION_FORM_FAILED')) . "');
			}
		}
	}"
);
?>

<div class="<?php echo JMAILALERTS_WRAPPER_CLASS; ?>" id="jmailalerts-sync">
	<form action="index.php?option=com_jmailalerts" method="POST" name="adminForm"
		ENCTYPE="multipart/form-data"
		id="adminForm" class="form-horizontal">

		<div id="j-main-container" class="j-main-container">
			<?php
			if (!empty($this->plugin_data))
			{
				// If there are plugins found in the `plugins` table, only then add the HTMl controls; else, display message
			}
			?>

			<div class="row">
				<div class="col-xs-8 col-sm-8 col-md-8 col-lg-8">
					<div class="control-group">
						<div class="control-label">
							<label class="control-label" for="alert_list">
								<?php echo Text::_('COM_JMAILALERTS_ALERT_TITLE'); ?>
							</label>
						</div>

						<div class="controls">
							<?php
							echo $this->dropdown = HTMLHelper::_(
								'select.genericlist', $this->alertname, 'alert_name',
								'required="required" aria-invalid="false" size="1" onchange="loadFrequencies(id)" class="form-select inputbox"',
								'value', 'text', '', 'alert_list'
							);
							?>

							<div id="alert_list-desc">
								<small class="form-text"><?php echo Text::_('COM_JMAILALERTS_ALERT_TITLE_TOOLTIP'); ?></small>
							</div>
						</div>
					</div>
					<div class="control-group">
					<label class="control-label" for="alert_list" title="<?php echo Text::_('COM_JMAILALERTS_GROUP_TITLE_TOOLTIP'); ?>">
						<?php echo Text::_('COM_JMAILALERTS_GROUP_TITLE'); ?>
					</label>
					<div class="controls">
						<?php
						echo $this->dropdown = HTMLHelper::_(
							'select.genericlist', $this->groups, 'group_list',
							'required="required" aria-invalid="false" size="1" class="form-select inputbox"',
							'value', 'text', '', 'group_list'
						);
						?>
					</div>
				</div>

					<div class="control-group">
						<div class="control-label">
							<label class="control-label" for="advaced_options">
								<?php echo Text::_('COM_JMAILALERTS_ADVANCE_OPTION'); ?>
							</label>
						</div>

						<div class="controls">
							<input type="checkbox" name="advaced_options" id="advaced_options" onchange="advanceoption_hideshow()" class="form-check-input"/>

							<div id="advaced_options-desc">
								<small class="form-text"><?php echo Text::_('COM_JMAILALERTS_ADVANCE_OPTION_TOOLTIP'); ?></small>
							</div>
						</div>
					</div>

					<div class="control-group advanceoption">
						<div class="control-label">
							<label class="control-label" for="freq_id">
								<?php echo Text::_('COM_JMAILALERTS_FREQ'); ?>
							</label>
						</div>

						<div class="controls">
							<select disabled name="freq_name" id="freq_id" class="form-select inputbox"></select>

							<div id="freq_id-desc">
								<small class="form-text"><?php echo Text::_('COM_JMAILALERTS_FREQ_TOOLTIP'); ?></small>
							</div>
						</div>
					</div>

					<div class="control-group advanceoption">
						<div class="control-label">
							<label class="control-label" for="last_email_date" title="<?php echo Text::_('COM_JMAILALERTS_LAST_EMAIL_DATE_TOOLTIP'); ?>">
								<?php echo Text::_('COM_JMAILALERTS_LAST_EMAIL_DATE'); ?>
							</label>
						</div>

						<div class="controls">
							<?php
							$date = Factory::getDate()->Format(Text::_('COM_JMAILALERTS_DATE_FORMAT_PHP'));

							// Set date to current date
							echo $calendar = HTMLHelper::_(
								'calendar', $date, 'last_email_date', 'last_email_date',
								Text::_('COM_JMAILALERTS_DATE_FORMAT_JOOMLA'), [ 'showTime'=>'showTime' ], 'class="input input-medium"'
							);
							?>

							<div id="last_email_date-desc">
								<small class="form-text"><?php echo Text::_('COM_JMAILALERTS_LAST_EMAIL_DATE_TOOLTIP'); ?></small>
							</div>
						</div>
					</div>

					<div class="control-group advanceoption">
						<div class="control-label">
							<label class="control-label" for="batch_size">
								<?php echo Text::_('COM_JMAILALERTS_BATCH_SIZE'); ?>
							</label>
						</div>

						<div class="controls">
							<input type="number" name="batch_size" id="batch_size" class="valid-numeric form-control"/>

							<div id="batch_size-desc">
								<small class="form-text"><?php echo Text::_('COM_JMAILALERTS_BATCH_SIZE_TOOLTIP'); ?></small>
							</div>
						</div>
					</div>

					<div class="control-group advanceoption">
						<div class="control-label">
							<label class="control-label" for="user_pref">
								<?php echo Text::_('COM_JMAILALERTS_OVERWRITE_USER_PREF'); ?>
							</label>
						</div>

						<div class="controls">
							<div class="form-check form-check-inline">
								<input class="form-check-input" type="radio" name="user_pref" id="user_pref1"  value="1" onclick="ShowHideReaddUnsubsUser()">
								<label class="form-check-label" for="user_pref1"><?php echo Text::_('COM_JMAILALERTS_YES'); ?></label>
							</div>
							<div class="form-check form-check-inline">
								<input class="form-check-input" type="radio" name="user_pref" id="user_pref2"  value="0" checked="checked" onclick="ShowHideReaddUnsubsUser()">
								<label class="form-check-label" for="user_pref2"><?php echo Text::_('COM_JMAILALERTS_NO'); ?></label>
							</div>

							<div id="user_pref-desc">
								<small class="form-text"><?php echo Text::_('COM_JMAILALERTS_OVERWRITE_USER_PREF_TOOLTIP'); ?></small>
							</div>
						</div>
					</div>

					<div class="control-group advanceoption ShowHideReaddUnsubsUserCls">
						<div class="control-label">
							<label class="control-label" for="unsub_user">
								<?php echo Text::_('COM_JMAILALERTS_ADD_UNSUB_USERS'); ?>
							</label>
						</div>

						<div class="controls">
							<div class="form-check form-check-inline">
								<input class="form-check-input" type="radio" name="unsub_user" id="unsub_user1"  value="1" onclick="chaneSubsreport()">
								<label class="form-check-label" for="unsub_user1"><?php echo Text::_('COM_JMAILALERTS_YES'); ?></label>
							</div>
							<div class="form-check form-check-inline">
								<input class="form-check-input" type="radio" name="unsub_user" id="unsub_user2"  value="0" checked="checked" onclick="chaneSubsreport()">
								<label class="form-check-label" for="unsub_user2"><?php echo Text::_('COM_JMAILALERTS_NO'); ?></label>
							</div>

							<div id="unsub_user-desc">
								<small class="form-text"><?php echo Text::_('COM_JMAILALERTS_ADD_UNSUB_USERS_TOOLTIP'); ?></small>
							</div>
						</div>
					</div>

					<!--@TODO Selective sync
					<div class="control-group advanceoption">
						<label class="control-label" for="title"
							title="<?php // @echo Text::_('COM_JMAILALERTS_SELECTIVE_SYNC_TOOLTIP'); ?>">
							<?php // @echo Text::_('COM_JMAILALERTS_SELECTIVE_SYNC'); ?>
						</label>
						<div class="controls">
							<label class="radio inline">
								<input type="radio" name="sel_sync" id="sel_sync1" value="1" />
								<?php // @echo Text::_('COM_JMAILALERTS_YES'); ?>
							</label>
							<label class="radio inline">
								<input type="radio" name="sel_sync" id="sel_sync2" value="0" checked="checked" />
								<?php // @echo Text::_('COM_JMAILALERTS_NO'); ?>
							</label>
						</div>
					</div>
					-->

					<?php
					$tblclass = 'table table-striped table-bordered';
					?>
					<div class="row">
						<div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
							<table class="<?php echo $tblclass; ?>">
								<tr>
									<th width="33%;">
										<?php echo Text::_('COM_JMAILALERTS_USERS'); ?>
									</th>
									<th width="33%;" class="text text-center">
										<?php echo Text::_('COM_JMAILALERTS_BEFORE_SYNC'); ?>
										<hr class="hr hr-condensed"/>
										<?php echo Text::_('COM_JMAILALERTS_REGISTERD_USER'); ?> 
										<?php //echo Text::_('COM_JMAILALERTS_GUEST_USER'); ?>
									</th>
									<th width="33%;" class="text text-center">
										<?php echo Text::_('COM_JMAILALERTS_AFTER_SYNC'); ?>
										<hr class="hr hr-condensed"/>
										<?php echo Text::_('COM_JMAILALERTS_REGISTERD_USER'); ?> 
										<?php //echo Text::_('COM_JMAILALERTS_GUEST_USER'); ?>
										</th>
									</th>
								</tr>

								<tr>
									<td width="33%;">
										<?php echo Text::_('COM_JMAILALERTS_CURRN_SUBSCRIBED_USERS'); ?>
									</td>
									<td width="33%;" class="text text-center">
										<div class="subs_registerd subscription_report"></div>
										<!-- <span class="subs_guest" ></span> -->
									</td>
									<td class="text text-center">
										<div class="subscription_report after_sync_subs_registerd"></div>
										<!-- <span class="after_sync_guest" ></span> -->
									</td>
								</tr>

								<tr>
									<td>
									<?php echo Text::_('COM_JMAILALERTS_CURRN_UNSUBSCRIBED_USERS'); ?>
									</td>
									<td width="33%;" class="text text-center">
										<div class="unsub_registerd subscription_report" ></div>
										<!-- <span class="unsubs_guest" ></span> -->
									</td>
									<td class="text text-center">
										<div class="subscription_report after_unsub_registerd"></div>
										<!-- <span class="after_unsub_guest" ></span> -->
									</td>
								</tr>

								<tr>
									<td>
										<?php echo Text::_('COM_JMAILALERTS_NOT_OPTED_IN_USERS'); ?>
									</td>
									<td class="text text-center">
										<div class="never_opted_in subscription_report"></div>
										<span class="" ></span>
									</td>
									<td class="text text-center">
										<div class="never_opted_in subscription_report">0</div>
										<span class="" ></span>
									</td>
								</tr>

								<tr>
									<td>
										<strong><?php echo Text::_('COM_JMAILALERTS_USERS_TOTAL'); ?></strong>
									</td>
									<td class="text text-center">
										<strong>
											<div class="column1_total subscription_report"></div>
											<span class="column2_total"></span>
										</strong>
									</td>
									<td class="text text-center">
										<strong>
											<div class="column1_total subscription_report"></div>
											<span class="column4_total" ></span>
										</strong>
									</td>
								</tr>
							</table>
						</div>
					</div>

					<div class="row">
						<div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
							<div class="showhide_progressbar progress">
								<div class="completed_percent progress-bar progress-bar-striped progress-bar-animated"
									role="progressbar"
									style="width: <?php echo "0"; ?>%;">
								</div>
							</div>
						</div>
					</div>

					<div class="row">
						<div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
							<div>&nbsp;</div>
							<div class="text text-center">
								<button class="btn btn-success btn-large" type="button" onclick='sync(400, 0, 0);'><?php echo Text::_('COM_JMAILALERTS_SYNC_BUTTON'); ?></button>
							</div>
						</div>
					</div>

					<!--
						sync(400,0)
						sync paramerter 400=> is default batch size
						0 => identify that this is the first ajax request call
						0 => completed_batch_users
					-->
				</div>

				<div class="col-xs-4 col-sm-4 col-md-4 col-lg-4">
					<div class="card small">
						<div class="card-header">
							<?php echo Text::_('COM_JMAILALERTS_SYNC_NOTE'); ?>
						</div>
						<div class="card-body">
							<div class="card-text">
								<h5 class="small"><?php echo Text::_('COM_JMAILALERTS_SYNC_SYNC_NEW_USERS'); ?></h5>
								<ul>
									<li><?php echo Text::_('COM_JMAILALERTS_SYNC_SYNC_NEW_USERS_DESC'); ?></li>
								</ul>

								<h5 class="small"><?php echo Text::_('COM_JMAILALERTS_SYNC_SYNC_OVERWRITE'); ?></h5>
								<ol>
									<li><?php echo Text::_('COM_JMAILALERTS_SYNC_SYNC_OVERWRITE_DESC1'); ?></li>
									<li><?php echo Text::_('COM_JMAILALERTS_SYNC_SYNC_OVERWRITE_DESC2'); ?></li>
								</ol>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<input type="hidden" name="task" value="" />
		<?php echo HTMLHelper::_('form.token'); ?>
	</form>
</div>
