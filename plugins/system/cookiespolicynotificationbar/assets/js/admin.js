/* ======================================================
# Cookies Policy Notification Bar for Joomla! - v4.0.0 (Pro version)
# -------------------------------------------------------
# For Joomla! CMS
# Author: Web357 (Yiannis Christodoulou)
# Copyright (©) 2009-2020 Web357. All rights reserved.
# License: GNU/GPLv3, http://www.gnu.org/licenses/gpl-3.0.html
# Website: https:/www.web357.com/
# Demo: https://demo.web357.com/joomla/cookiespolicynotificationbar
# Support: support@web357.com
# Last modified: 26 Nov 2020, 01:41:47
========================================================= */

jQuery(document).ready(function($) {

	var displayAcceptanceLogs = function (is_inside_modal) 
	{
		$.ajax({
			type   : 'GET',
			url: "index.php?option=com_ajax&plugin=cookiespolicynotificationbar&format=raw&method=displayAcceptanceLogs",
			success: function (response) {
				$('.cpnb-acceptance-logs-table').html(response);
			},
			error: function(response) {
				$('.cpnb-acceptance-logs-table').html(response);
			},
			beforeSend: function () {
				$(".cpnb-loading-gif").show();
			},
			complete: function () {
				$(".cpnb-loading-gif").hide();
			}
		});
	}

	var deleteAcceptanceLogs = function () 
	{
		$.ajax({
			type   : 'GET',
			url: "index.php?option=com_ajax&plugin=cookiespolicynotificationbar&format=raw&method=deleteAcceptanceLogs",
			success: function (response) {
				$('.cpnb-acceptance-logs-deleted').html(response);
			},
			error: function(response) {
				$('.cpnb-acceptance-logs-deleted').html(response);
			},
			beforeSend: function () {
				$(".cpnb-loading-gif").show();
			},
			complete: function () {
				setTimeout(function() { 
					$(".cpnb-loading-gif").hide();
					$('.cpnb-acceptance-logs-deleted-msg').css('display', 'block');
				}, 3000);

				setTimeout(function() { 
					$('.cpnb-acceptance-logs-deleted-msg').css('display', 'none');
				}, 15000);
			}
		});
	}

	var restoreToDefaults = function () 
	{
		$.ajax({
			type   : 'GET',
			url: "index.php?option=com_ajax&plugin=cookiespolicynotificationbar&format=raw&method=restoreToDefaults",
			success: function (response) {
				$('.cpnb-settings-restored-to-default').html(response);
			},
			error: function(response) {
				$('.cpnb-settings-restored-to-default').html(response);
			},
			beforeSend: function () {
				$(".cpnb-loading-gif").show();
			},
			complete: function () {
				setTimeout(function() { 
					$(".cpnb-loading-gif").hide();
					$('.cpnb-restore-to-defaults-msg').css('display', 'block');
				}, 3000);

				setTimeout(function() { 
					$('.cpnb-restore-to-defaults-msg').css('display', 'none');
				}, 15000);
			}
		});
	}

	// display logs in the modal
	$('#modal-view-acceptance-logs').on('show.bs.modal', function() {
		displayAcceptanceLogs();
	});

	// Reload button
	$(document).on("click", ".cpnb-reload-acceptance-logs-btn", function(e){
		e.preventDefault();
		displayAcceptanceLogs();
	});

	// Delete logs
	$(document).on("click", ".cpnb-delete-acceptance-logs-btn", function(e){
		e.preventDefault();

		var delete_confirmation_msg =  $('.cpnb-delete-acceptance-logs-btn').data("cpnb-delete-confirmation-msg");
		if (!confirm(delete_confirmation_msg)){
			return false;
		}

		deleteAcceptanceLogs();

	});

	// Restore to Defaults
	$(document).on("click", ".cpnb-restore-to-detafaults-btn", function(e){
		e.preventDefault();

		var restore_to_defaults_confirmation_msg =  $('.cpnb-restore-to-detafaults-btn').data("cpnb-restore-to-defaults-confirmation-msg");
		if (!confirm(restore_to_defaults_confirmation_msg))
		{
			return false;
		}

		restoreToDefaults();

	});

});