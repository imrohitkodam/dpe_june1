// This is used in addtodo popup tmpl form 
jQuery(document).ready(function () {

	if (jQuery('#system-message div').hasClass('alert alert-success')) {
		jQuery("#system-message-container").fadeTo(4000, 500, function () {
			window.parent.document.location.reload(true);
			window.parent.SqueezeBox.close();
		});
	}
	jQuery.urlParam = function (reportId) {
		var results = new RegExp('[\?&]' + reportId + '=([^&#]*)').exec(currentLink);
		return results[1] || 0;
	}

	function escapeHtml(text) {
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
	}

	jQuery("#course_title").removeAttr("hidden");

	var courseId = window.parent.jQuery("#filterscourse_id").val();
	jQuery("#course_id").val(courseId);

	var form = window.parent.jQuery('#adminForm');
	var formData = form.serializeArray();
	var allusername = '';
	var lessonId = "";
	var matchData = '';
	let anotation = ',';
	var totalCheckbox = window.parent.jQuery('input[name="cid[]"]:checked').length;
	var data = form.serializeArray().reduce(function (obj, item) {

		// get the form data and modified as per form 
		if (item.name.indexOf('filters') != -1) {
			obj[item.name] = item.value;
			matchData = item.name.match(/\[(.*?)\]/);
			var name = '';
			if (matchData) {

				if (item.name == 'filters[allUser]') {
					allusername = matchData[1];
				}
				else {
					name = matchData[1];
				}
			}

			var text = window.parent.jQuery("#filters" + name + " option:selected").text();

			if (!text) {
				text = item.value;
			}
			text = escapeHtml(text);

			name = (name == 'course_id' || name == 'lession_id') ? Joomla.JText._('PLG_CONTENT_JLIKE_MULTIAGENCY_FIELD_COURSE_NAME') : name;
			name = (name == 'cstatus') ? Joomla.JText._('plg_tjreports_addtodo_course_status') : name;

			allusername = Joomla.JText._('PLG_CONTENT_JLIKE_MULTIAGENCY_FIELD_COURSE_EMPLOYEE_TODO');
			item.value = ((item.value == "0") && (name == 'read')) ? "2" : item.value;
			item.value = ((item.value == "0") && (name == 'used')) ? "2" : item.value;

			item.value = escapeHtml(item.value);

			if (name == 'enrolstatus') {
				name = "Enroll status";
			}

			if (item.value && (item.name !== 'filters[allUser]')) {
				jQuery("form").append("<input type='hidden' id='" + name + "' name='jform[" + name + "]' value = " + item.value + " >");

				name = (name == 'cluster_id' || name == 'cluster') ? Joomla.JText._('PLG_CONTENT_JLIKE_MULTIAGENCY_FIELD_ORGANISATION_NAME') : name;

				jQuery("#filters").append("<label for='" + name + "'> <span  value=" + item.value + " id='filter" + name + "' >" + name
					.split(' ')
					.map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
					.join(' ') + ": " + text + "</span></label>" + anotation);
			}
			else if ((item.name == 'filters[allUser]')) {
				jQuery(".alluser").append("<br> <label  for='" + allusername + " style='color:Black;'>" + allusername + ": <span id='nofusercount' name='nofusercount'>  </span> </label>");
			}

			if (name == Joomla.JText._('PLG_CONTENT_JLIKE_MULTIAGENCY_FIELD_ORGANISATION_NAME')) {
				var clusterid = window.parent.jQuery("#filters" + matchData[1] + " option:selected").val();
				jQuery("#jform_clusters").val(clusterid);
			}

			if (name == Joomla.JText._('PLG_CONTENT_JLIKE_MULTIAGENCY_FIELD_COURSE_NAME')) {
				lessonId = window.parent.jQuery("#filters" + matchData[1] + " option:selected").val();

			}

		}

		return obj;
	}, {});

	let filterString = jQuery('#filters').text();
	let modifiedStringforfilters = filterString.replace(/,*$/, '').trim();
	jQuery('#filters').text(modifiedStringforfilters + '.');
	jQuery("<b>Filters Selected:</b> <br>").appendTo("#selectfilters");
	if ((totalCheckbox >= 1) && (window.parent.jQuery('#filters_allUser:checked').length < 1)) {

		jQuery("<br> <label  for='" + allusername + " style='color:Black;'>" + allusername + ": <span id='nofusercount' name='nofusercount'>  </span> </label>").appendTo(".alluser")
	}
	jQuery('#jform_title').val('');
	jQuery('#jform_sender_msg').val('');


	// Added url for course and lesson 
	url = window.parent.jQuery("#courseUrl").val();
	(url) ? jQuery('#current_page_link').val(url) : '';

	// Here to add todo for autocompletion of document and course only for compliance manager and course enrolment 
	if (window.parent.jQuery("#reportToBuild").val() == 'compliancemanagerreport') {
		jQuery('#content_id').val(window.parent.jQuery('#contentId').val());
		jQuery('#element').val('com_tjlms.lesson');
	}

	if (window.parent.jQuery("#reportToBuild").val() == 'dpeenrolmentreport') {
		jQuery('#element').val('com_tjlms.course');
	}


	// add user count to the hidden field
	if (totalCheckbox >= 1) {
		jQuery("#nofusercount").text(totalCheckbox);
	}

	jQuery("#jform_all_cluster_users").val(window.parent.jQuery('#filters_allUser').val());



	var storeAssignTodo = [];
	jQuery.each(window.parent.jQuery("input[name='cid[]']:checked"),
		function (index, obj) {

			var email = jQuery(this).closest("tr").find('.td-addtodo').attr('value');

			storeAssignTodo.push(email);

			jQuery('<input>').attr({
				type: 'hidden',
				name: 'jform[assigned_to_users][' + index + ']',
				value: email
			}).appendTo('form');

		});

	// create hidden fields for the users.
	if (window.parent.jQuery('#filters_allUser').val() === 'add_all_users_with_filters') {
		jQuery('#nofusercount').text(window.parent.jQuery('input[name=juserCount]').val());
		var userValue = {}
		userValue = window.parent.jQuery('input[name=assigned_to_users]').val().split(',');
		jQuery.each(userValue,
			function (index, obj) {
				jQuery('<input>').attr({
					type: 'hidden',
					name: 'jform[assigned_to_users][' + index + ']',
					value: obj
				}).appendTo('form');
			});
	}

});
