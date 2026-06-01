/**
 * @package     Jlike
 * @subpackage  com_jlike
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2021 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */
var jlike = {
	escapeHtml: function (text) {
		if (text === null || text === undefined) {
			return '';
		}
		var map = {
			'&': '&amp;',
			'<': '&lt;',
			'>': '&gt;',
			'"': '&quot;',
			"'": '&#039;'
		};
		return String(text).replace(/[&<>"']/g, function (m) { return map[m]; });
	},
	getAgencyUsers: function (clusterObj) {
		var formData = {};
		var clusterusers = jQuery('#jform_assigned_to');
		var multiUsers = jQuery('#jform_assigned_to_users');
		var ccusers = jQuery('#jform_cc_users');
		var assignedUser = jQuery('#assigned_user_id').val();
		var savedCCusers = jQuery('#cc_users').val();
		formData['cluster_id'] = jQuery(clusterObj).val();

		var ccUsersarray = savedCCusers.split(',');

		var promise = jlikeService.getAgencyUsers(formData);

		promise.fail(
			function (response) {
				var messages = {
					"error": [response.responseText]
				};
				Joomla.renderMessage(messages);
			}
		).done(function (response) {

			if (!response) {
				return false;
			}

			if (response.success) {
				clusterusers.empty();
				clusterusers.trigger("chosen:updated");
				clusterusers.append('<option value="">' + Joomla.JText._('COM_JLIKE_SELECT_USER') + '</option>');

				multiUsers.empty();
				multiUsers.trigger("chosen:updated");

				ccusers.empty();
				ccusers.trigger("chosen:updated");

				var data = response.data;

				for (var index = 0; index < data.length; ++index) {
					selectOption = '';
					ccSelectOption = '';

					if (assignedUser == data[index].value) {
						selectOption = ' selected="selected" ';
					}

					// Select saved cc users on edit form
					if (jQuery.inArray(data[index].value, ccUsersarray) >= 0) {
						ccSelectOption = ' selected="selected" ';
					}

					userOptions = "<option value='" + data[index].value + "' " + selectOption + " > " + jlike.escapeHtml(data[index]['text']) + "</option>";
					ccUserOptions = "<option value='" + data[index].value + "' " + ccSelectOption + " > " + jlike.escapeHtml(data[index]['text']) + "</option>";
					multiUsersOptions = "<option value='" + data[index].value + "'> " + jlike.escapeHtml(data[index]['text']) + "</option>";
					clusterusers.append(userOptions);
					ccusers.append(ccUserOptions);
					multiUsers.append(multiUsersOptions);
				}

				/* IMP : to update to chz-done selects*/
				clusterusers.trigger("chosen:updated");
				ccusers.trigger("chosen:updated");
				multiUsers.trigger("chosen:updated");
			}
		});
	},
	init: function () {

		// Commented code used for tool specific notifications

		/*
		var element    = parent.document.getElementById('element').value;
		var element_id = parent.document.getElementById('element_id').value;
		var url        = parent.document.getElementById('url').value;

		document.getElementById('element').value        = element;
		document.getElementById('element_id').value     = element_id;
		document.getElementById('url').value            = url;
		*/

		if (parent.document.getElementById('cluster_id')) {
			var cluster_id = parent.document.getElementById('cluster_id').value;
		}

		var itemId = document.getElementById('id').value;

		if (!itemId) {
			if (cluster_id) {
				document.getElementById('jform_clusters').value = cluster_id;
			}
		}

		Joomla.submitbutton = function (task) {
			if (task == "recommendationform.cancel" || document.formvalidator.isValid(document.getElementById("adminForm"))) {
				Joomla.submitform(task, document.getElementById("adminForm"));
			}
			else {
				jQuery("html, body").animate({ scrollTop: 0 }, "slow");
			}
		};
	},
	markComplete: function (todoId, obj) {
		var completedCount = jQuery('#todo_completed').text();
		completedCount = parseInt(completedCount);
		var formData = {};
		formData['todo_id'] = todoId;
		formData['status'] = "C";

		var promise = jlikeService.markComplete(formData);

		promise.fail(
			function (response) {
				var messages = {
					"error": [jlike.escapeHtml(response.responseText)]
				};
				jlike.renderMessage(messages);
			}
		).done(function (response) {

			if (!response.success && response.message) {
				var messages = {
					"error": [jlike.escapeHtml(response.message)]
				};
				jlike.renderMessage(messages);
			}

			if (response.messages) {
				jlike.renderMessage(response.messages);
			}

			if (response.success) {
				jlike.renderMessage(response.message);
				jQuery('#todo_completed').text(completedCount + 1);
			}

			jQuery(obj).closest("tr").remove();
		});
	},
	addTodos: function () {
		jlike.showLoader();
		var formData = jQuery('.add-todos').serialize();
		var params = {};
		params['async'] = true;
		var promise = jlikeService.addTodos(formData, params);

		promise.fail(
			function (response) {
				var messages = { "error": [jlike.escapeHtml(response.responseText)] };
				Joomla.renderMessages(messages);
			}
		).done(function (response) {
			jQuery.LoadingOverlay("hide");

			jQuery('.addTodoForm').prop('disabled', true);
			if (!response.success && response.message) {
				var messages = { "error": [jlike.escapeHtml(response.message)] };
				Joomla.renderMessages(messages);
				jQuery('.addTodoForm').prop('disabled', false);
			}

			if (response.success) {
				jlike.renderMessage(response.data.msg);
				jQuery('#adminForm').trigger("reset");

				jQuery("#system-message-container").fadeTo(4000, 500, function () {
					window.parent.SqueezeBox.close();
				});
			}
		});
	},
	validationDueDate: function (dueDateObj) {
		var dueDate = jQuery(dueDateObj).val();
		var today = new Date();
		today.setHours(0, 0, 0, 0);
		var todaysDate = today.format("%Y-%m-%d");
		dueDate = dueDate.split(' ')[0]
		dueDate = dueDate.split("-").reverse().join("-");

		// Dpe Hack end to convert case

		jQuery(document).ready(function () {
			document.formvalidator.setHandler('duedate', function (value) {
				if (dueDate < todaysDate) {
					jlike.renderMessage(Joomla.JText._('COM_JLIKE_DUE_DATE_VALIDATION_MESSAGE'));
					jQuery('#jform_due_date').val("");

					return false;
				}

				return true;

			});
		});
	},
	renderMessage: function (msg) {
		Joomla.renderMessages({
			'success': [msg]
		});
		jQuery("html, body").animate({
			scrollTop: 0
		}, 2000);
	},
	showLoader: function () {
		loadpreloader();
		// jQuery.LoadingOverlay("show", {
		// 	image : Joomla.getOptions('system.paths').root + "/media/com_jlike/images/loader/loader.gif",
		// });
	}
}

jQuery(document).on('click', '.closepopup', function () {

	if (jQuery(this).data('refresh') == 1) {
		window.parent.document.location.reload(true);
	}

	window.parent.SqueezeBox.close();
});

jQuery(document).ready(function () {
	jQuery('#jform_clusters').trigger('change');
});


// DPE hack - Can go in core?
function actionOnMultipleTodos(clickedElement) {
	var todoFromReport = 0;
	var completeNonAutoMarkedTodoCount = 0;
	var inCompleteNonAutoMarkedTodoCount = 0;

	var todoValues = jQuery('[name="wcid[]"]:checked').map(function () {

		var tr = jQuery(this).closest('tr');
		var isCompleteMarkPresent = tr.find('.complete-mark').length > 0;
		var isinCompleteMarkPresent = tr.find('.incomplete-mark').length > 0;

		if ((isinCompleteMarkPresent) && (jQuery(clickedElement).data('value') == "C")) {
			completeNonAutoMarkedTodoCount++;
		}
		else if ((isCompleteMarkPresent) && (jQuery(clickedElement).data('value') == "I")) {

			inCompleteNonAutoMarkedTodoCount++;
		}


		if (isCompleteMarkPresent || isinCompleteMarkPresent || (jQuery(clickedElement).data('task') == 'recommendation.delete') || (jQuery(clickedElement).data('task') == 'recommendation.addToQueue')) {
			return jQuery(this).val();
		}
		else {
			todoFromReport++
		}
	}).get();


	if (completeNonAutoMarkedTodoCount > 0) {
		alert(Joomla.Text._('COM_JLIKE_CANT_COMPLETE_TODO'))
		return false;
	}
	else if (inCompleteNonAutoMarkedTodoCount > 0) {
		alert(Joomla.Text._('COM_JLIKE_CANT_INCOMPLETE_TODO'))

		return false;
	}


	var status = jQuery(clickedElement).data('value');
	var task = jQuery(clickedElement).data('task');



	if (todoValues.length == 0 && (todoFromReport == 0)) {
		alert(Joomla.Text._('COM_JLIKE_SELECT_ANY_MULTPLE_TODO'))
		return false;
	}
	else if ((task !== 'recommendation.delete') && (todoValues.length == 0) && (todoFromReport != 0) && (status != 'resend')) {
		alert(Joomla.Text._('COM_JLIKE_SELECT_ANY_AUTOMULTPLE_TODO'))
		return false;
	}



	if ((todoValues.length >= 1) && (status != 'resend')) {
		var userConfirmed = (status) ? confirm(Joomla.Text._('COM_JLIKE_CHANGE_STATUS_OF_MULTIPLETODO')) : confirm(Joomla.Text._('COM_JLIKE_DELETE_MULTPLE_TODO'));

		if (!userConfirmed) {
			return false;
		}
	}
	else if ((todoValues.length >= 1) && (status == 'resend')) {
		var userConfirmed = confirm(Joomla.Text._('COM_JLIKE_RESEND_TODO_OF_MULTIPLETODO'));

		if (!userConfirmed) {
			return false;
		}
	}

	jQuery.ajax({
		url: Joomla.getOptions('system.paths').root + "/index.php?option=com_jlike&task=" + task,
		type: "POST",
		dataType: 'json',
		data: { 'mcid': todoValues, 'status': status },

		success: function (response) {


			if ((response.data.success == true) && (status != 'resend')) {
				jQuery('<div id="system-message-container"></div>').insertBefore('#adminForm');

				if ((todoFromReport) && (status == 'C')) {
					var msg = response.data.msg + '  and ' + todoFromReport + " " + Joomla.Text._('COM_JLIKE_CHANGE_STATUS_OF_AUTOCOMPLETED_MULTIPLETODO');

					Joomla.renderMessages({
						'success': [msg]
					});
				}
				else if ((todoFromReport) && (status == 'I')) {
					msg = response.data.msg + '  and ' + todoFromReport + " " + Joomla.Text._('COM_JLIKE_CHANGE_STATUS_OF_AUTOINCOMPLETED_MULTIPLETODO');
					Joomla.renderMessages({
						'success': [msg]
					});
				}
				else {
					Joomla.renderMessages({
						'success': [response.data.msg]
					});
				}

				setTimeout(function () { location.reload(); }, 2000);
			}
			else if ((response.success == true) && (status == 'resend')) {
				var ids = [];
				var sucessId = [];
				var key = 0;
				var successkey = 0;

				jQuery.each(response.data, function (index, item) {


					if (item.message == 'complete') {
						ids[key] = item.id;
						key++;
					}
					if (item.message == 'Success') {
						sucessId[successkey] = item.id;
						successkey++;
					}
				});


				var completedTodod = ids.join(", ");
				var successTodo = sucessId.join(", ");

				jQuery('joomla-alert').each(function (index, element) {

					setTimeout(function () {
						jQuery(element).fadeOut('slow');
					});
				});

				if (completedTodod) {
					msg = "The to-do's with ID " + completedTodod + " have already been completed. They cannot be resent.";
					Joomla.renderMessages({
						'error': [msg]
					});
				}
				if (successTodo) {
					msg = 'The to-dos have been successfully added to the resend queue. Emails will be sent out shortly by the system.';
					Joomla.renderMessages({
						'success': [msg]
					});
				}



			}
		}

	});

}
