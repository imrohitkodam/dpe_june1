var RSTicketsPro = {
	getHttp               : function () {
		var xmlHttp;
		try {
			// Firefox, Opera 8.0+, Safari
			xmlHttp = new XMLHttpRequest();
		} catch (e) {
			// Internet Explorer
			try {
				xmlHttp = new ActiveXObject('Msxml2.XMLHTTP');
			} catch (e) {
				xmlHttp = new ActiveXObject('Microsoft.XMLHTTP');
			}
		}
		return xmlHttp;
	},
	sendHttp              : function (httpUrl, httpParams, httpType) {
		xmlHttp = this.getHttp();
		if (typeof httpParams == 'array') {
			httpParams = httpParams.join('&');
		} else if (typeof httpParams == 'object') {
			var tmpParams = [];
			for (var k in httpParams) {
				tmpParams.push(k + '=' + httpParams[k]);
			}
			httpParams = tmpParams.join('&');
		}
		httpType = httpType.toUpperCase();

		xmlHttp.open(httpType, httpUrl, true);
		if (httpType == 'POST') {
			xmlHttp.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
		}

		xmlHttp.send(httpParams);
	},
	flagTicket            : function (button, ticket_id) {
		var flagged;
		var url = Joomla.getOptions('system.paths').base + '/index.php';
		// no flag
		if (button.classList.contains('rst_not_flagged')) {
			button.classList.remove('rst_not_flagged');
			button.classList.add('rst_flagged');
			flagged = 1;
		} else {
			button.classList.remove('rst_flagged');
			button.classList.add('rst_not_flagged');
			flagged = 0;
		}

		this.sendHttp(
			url, {
				'option' : 'com_rsticketspro',
				'task'   : 'ticket.flag',
				'cid'    : ticket_id,
				'flagged': flagged
			},
			'POST'
		);
	},
	disableBulk           : function (value) {
		value = value == 0 ? false : true;

		if (document.getElementById('bulk_staff_id')) {
			document.getElementById('bulk_staff_id').disabled = value;
		}
		if (document.getElementById('bulk_priority_id')) {
			document.getElementById('bulk_priority_id').disabled = value;
		}
		if (document.getElementById('bulk_status_id')) {
			document.getElementById('bulk_status_id').disabled = value;
		}
		if (document.getElementById('bulk_notify')) {
			document.getElementById('bulk_notify').disabled = value;
		}
		if (document.getElementById('bulk_department_id')) {
			document.getElementById('bulk_department_id').disabled = value;
		}

		if (typeof jQuery != 'undefined') {
			jQuery('#bulk_staff_id').trigger('liszt:updated');
			jQuery('#bulk_priority_id').trigger('liszt:updated');
			jQuery('#bulk_status_id').trigger('liszt:updated');
			jQuery('#bulk_notify').trigger('liszt:updated');
		}
	},
	departments           : {},
	getDepartment         : function () {
		if (typeof this.departments[document.getElementsByName('jform[department_id]')[0].value] !== 'undefined')
		{
			return this.departments[document.getElementsByName('jform[department_id]')[0].value];
		}

		return {
			id: 0,
			priority: '',
			uploads: {
				required: false,
				allowed: false,
				message: '',
				message_max_files: '',
				message_max_size: '',
				max: 0
			}
		};
	},
	changeDepartment      : function () {
		var department = this.getDepartment();

		this.changePriority(department);
		this.showFiles(department);
		this.showPredefinedSubjects(department);
	},
	changePriority        : function (department) {
		document.getElementsByName('jform[priority_id]')[0].value = department.priority;
	},
	showFiles             : function (department) {
		var containers = {
			message: document.getElementById('rst_files_message_container'),
			files  : document.querySelector('.rst_files_container'),
			label  : document.getElementById('jform_files-lbl'),
			heading: document.getElementById('rst_files_message_heading')
		};

		// hide the containers
		containers.message.style.display = 'none';
		containers.files.classList.add('hidden');
		containers.label.innerText = Joomla.JText._('RST_TICKET_ATTACHMENTS');

		if (containers.heading && containers.heading.parentNode)
		{
			containers.heading.parentNode.style.display = 'none';
		}

		if (department.uploads.allowed) {
			// set the message
			containers.message.innerHTML = '<p>' + department.uploads.message + ' ' + department.uploads.message_max_files + ' ' + department.uploads.message_max_size + '</p>';

			var currentFiles = document.getElementsByName('jform[files][]');

			// adjust the number of upload fields
			if (department.uploads.max > 0 && currentFiles.length >= department.uploads.max) {
				for (var i = currentFiles.length - 1; i > department.uploads.max - 1; i--) {
					currentFiles[i].parentNode.removeChild(currentFiles[i]);
				}
			}

			if (department.uploads.required)
			{
                containers.label.innerText = Joomla.JText._('RST_TICKET_ATTACHMENTS_REQUIRED');
			}

			// display the containers
			containers.message.style.display = 'block';
			containers.files.classList.remove('hidden');
			if (containers.heading && containers.heading.parentNode)
			{
				containers.heading.parentNode.style.display = 'initial';
			}
		}
	},
	addReplyFile          : function () {
		var department = this.getDepartment();
		var currentFiles = document.getElementsByName('ticket[files][]');

		if (department.uploads.max > 0 && currentFiles.length >= department.uploads.max) {
			alert(Joomla.JText._('RST_MAX_UPLOAD_FILES_REACHED'));
		} else {
			var file = currentFiles[0];
			var newUpload = document.createElement('input');
			newUpload.setAttribute('name', 'ticket[files][]');
			newUpload.setAttribute('type', 'file');
			newUpload.setAttribute('id', 'jform_files' + currentFiles.length);
			newUpload.setAttribute('class', 'rst_file_block');
			newUpload.className = 'rst_file_block';
			var newLabel = document.createElement('label');

			file.parentNode.appendChild(newLabel);
			file.parentNode.appendChild(newUpload);
		}
	},
	addSubmitFile         : function () {
		var department = this.getDepartment();
		var currentFiles = document.getElementsByName('jform[files][]');

		if (department.uploads.max > 0 && currentFiles.length >= department.uploads.max) {
			alert(Joomla.JText._('RST_MAX_UPLOAD_FILES_REACHED'));
		} else {
			var file = currentFiles[0];
			var newUpload = document.createElement('input');
			newUpload.setAttribute('name', 'jform[files][]');
			newUpload.setAttribute('type', 'file');
			newUpload.setAttribute('id', 'jform_files' + currentFiles.length);
			newUpload.setAttribute('class', 'rst_file_block');
			newUpload.className = 'rst_file_block';
			var newLabel = document.createElement('label');

			file.parentNode.appendChild(newLabel);
			file.parentNode.appendChild(newUpload);
		}
	},

	showPredefinedSubjects: function (department) {

	},
	populateSelect        : function (select, values) {
		select.options.length = 0;
		for (k in values) {
			var option = document.createElement('option');
			option.text = values[k];
			option.value = k;

			// check if it findsthe [c] tag
			if (option.value.indexOf('[c]') > -1) {
				option.defaultSelected = true;
				
				// we will remove the tag after we set the select true
				option.text = option.text.replace('[c]', '');
				option.value = option.value.replace('[c]', '');
			}

			try {
				select.add(option, null); // standards compliant; doesn't work in IE
			}
			catch (ex) {
				select.add(option); // IE only
			}
		}
	},
	disableStaff          : function (departmentDropdown, staffDropdown) {
		var departmentText;
		var departmentValue;

		if (!departmentDropdown) {
			if (document.getElementsByName('ticket[department_id]').length > 0) {
				departmentDropdown = document.getElementsByName('ticket[department_id]')[0];
				departmentText = departmentDropdown.options[departmentDropdown.selectedIndex].text;
				departmentValue = departmentDropdown.options[departmentDropdown.selectedIndex].value;
			} else if (document.getElementsByName('hidden_department_id').length > 0) {
				departmentText = document.getElementsByName('hidden_department_id')[0].value;
			}
		} else {
			departmentText = departmentDropdown.options[departmentDropdown.selectedIndex].text;
			departmentValue = departmentDropdown.options[departmentDropdown.selectedIndex].value;
		}

		if (!staffDropdown) {
			if (document.getElementsByName('ticket[staff_id]').length > 0) {
				staffDropdown = document.getElementsByName('ticket[staff_id]')[0];
			}
		}

		if (!staffDropdown) {
			return false;
		}

		if (typeof departmentText === 'undefined') {
			return false;
		}

		var optgroups = staffDropdown.getElementsByTagName('optgroup');
		var optgroup, options;
		var i;

		for (i = 0; i < optgroups.length; i++) {
			optgroup = optgroups[i];
			optgroup.style.display = 'none';
			options = optgroup.getElementsByTagName('option');
			for (j = 0; j < options.length; j++) {
				options[j].disabled = true;
				if (optgroup.getAttribute('label') === departmentText || departmentValue === '0') {
					optgroup.style.display = '';
					options[j].disabled = false;
				}
			}
		}

		if (staffDropdown.options[staffDropdown.selectedIndex].disabled) {
			var found = false;
			// search if the selected staff is in this department
			for (i = 0; i < optgroups.length; i++) {
				optgroup = optgroups[i];
				if (optgroup.getAttribute('label') === departmentText) {
					options = optgroup.getElementsByTagName('option');
					for (var j = 0; j < options.length; j++) {
						if (!options[j].disabled && options[j].value == staffDropdown.options[staffDropdown.selectedIndex].value) {
							found = true;
							options[j].selected = true;
						}
					}
				}
			}

			if (!found) {
				staffDropdown.selectedIndex = 0;
			}
		}

		if (typeof jQuery !== 'undefined') {
			jQuery(staffDropdown).trigger('liszt:updated');
		}
	},
	showReply             : function (button) {
		button.className = 'hidden';
		document.getElementById('com-rsticketspro-reply-box').className = '';
	},
	sendRating            : function (url, rating, id) {
		this.sendHttp(
			url, {
				'option': 'com_rsticketspro',
				'task'  : 'ticket.rate',
				'cid'   : id,
				'rating': rating
			},
			'POST'
		);
	},
	refreshCaptcha        : function (route) {
		document.getElementById('submit_captcha_image').src = route + (route.indexOf('?') > -1 ? '&' : '?') + 'sid=' + Math.floor((Math.random() * 1000) + 1);
	},
	
	openMagnificModal 	  : function(evt,modal_id) {
		evt.preventDefault();
		if (typeof jQuery == 'undefined') {
			alert(Joomla.JText._('RST_JQUERY_NOT_FOUND'));
		} else {
			jQuery.magnificPopup.open({
				type: 'inline',
				preloader: true,
				overflowY: 'scroll',
				items: {
					src: modal_id,
					callbacks: {
						beforeOpen: function () {
							jQuery(modal_id).show();
						},
						close: function () {
							jQuery(modal_id).hide();
						}
					}
				}
			});
		}
	},
	addEvent: function(obj, evType, fn) {
		if (obj.addEventListener)
		{
			obj.addEventListener(evType, fn, false);
			return true;
		}
		else if (obj.attachEvent)
		{
			var r = obj.attachEvent("on"+evType, fn);
			return r;
		}
		else
		{
			return false;
		}
	},
	timeCounter: function(startTime) {
		var start = new Date(startTime);
		var end;
		var timediff = start.getTimezoneOffset();
		start = start.getTime();

		self.chrono = function (){
			end = new Date();
			end = end.getTime();

			if (timediff != 0) {
				end = end + timediff * 60000;
			}

			var duration = end - start;

			var seconds = Math.floor((duration / 1000) % 60),
				minutes = Math.floor((duration / (1000 * 60)) % 60),
				hours = Math.floor(duration / (1000 * 60 * 60));

			hours = (hours < 10) ? "0" + hours : hours;
			minutes = (minutes < 10) ? "0" + minutes : minutes;
			seconds = (seconds < 10) ? "0" + seconds : seconds;

			jQuery('.hours').html(hours);
			jQuery('.minutes').html(minutes);
			jQuery('.seconds').html(seconds);

			setTimeout("self.chrono()", 1000);
		};
		self.chrono();
	}
};

/*
 Developed by Robert Nyman, http://www.robertnyman.com
 Code/licensing: http://code.google.com/p/getelementsbyclassname/
 */
RSTicketsPro.getElementsByClassName = function (className, tag, elm) {
	if (document.getElementsByClassName) {
		getElementsByClassName = function (className, tag, elm) {
			elm = elm || document;
			var elements = elm.getElementsByClassName(className),
				nodeName = (tag) ? new RegExp("\\b" + tag + "\\b", "i") : null,
				returnElements = [],
				current;
			for (var i = 0, il = elements.length; i < il; i += 1) {
				current = elements[i];
				if (!nodeName || nodeName.test(current.nodeName)) {
					returnElements.push(current);
				}
			}
			return returnElements;
		};
	}
	else if (document.evaluate) {
		getElementsByClassName = function (className, tag, elm) {
			tag = tag || "*";
			elm = elm || document;
			var classes = className.split(" "),
				classesToCheck = "",
				xhtmlNamespace = "http://www.w3.org/1999/xhtml",
				namespaceResolver = (document.documentElement.namespaceURI === xhtmlNamespace) ? xhtmlNamespace : null,
				returnElements = [],
				elements,
				node;
			for (var j = 0, jl = classes.length; j < jl; j += 1) {
				classesToCheck += "[contains(concat(' ', @class, ' '), ' " + classes[j] + " ')]";
			}
			try {
				elements = document.evaluate(".//" + tag + classesToCheck, elm, namespaceResolver, 0, null);
			}
			catch (e) {
				elements = document.evaluate(".//" + tag + classesToCheck, elm, null, 0, null);
			}
			while ((node = elements.iterateNext())) {
				returnElements.push(node);
			}
			return returnElements;
		};
	}
	else {
		getElementsByClassName = function (className, tag, elm) {
			tag = tag || "*";
			elm = elm || document;
			var classes = className.split(" "),
				classesToCheck = [],
				elements = (tag === "*" && elm.all) ? elm.all : elm.getElementsByTagName(tag),
				current,
				returnElements = [],
				match;
			for (var k = 0, kl = classes.length; k < kl; k += 1) {
				classesToCheck.push(new RegExp("(^|\\s)" + classes[k] + "(\\s|$)"));
			}
			for (var l = 0, ll = elements.length; l < ll; l += 1) {
				current = elements[l];
				match = false;
				for (var m = 0, ml = classesToCheck.length; m < ml; m += 1) {
					match = classesToCheck[m].test(current.className);
					if (!match) {
						break;
					}
				}
				if (match) {
					returnElements.push(current);
				}
			}
			return returnElements;
		};
	}
	return getElementsByClassName(className, tag, elm);
};

// Legacy functions
function rst_get_xml_http_object() {
	return RSTicketsPro.getHttp();
}

function rst_flag_ticket(url, button, ticket_id) {
	RSTicketsPro.flagTicket(button, ticket_id);
}

function rst_feedback(url, value, ticket_id) {
	if (window.rsticketspro_rating.options.disabled)
		return false;

	rst_feedback_message();

	var xmlHttp = new XMLHttpRequest();

	var params = 'option=com_rsticketspro';
	params += '&controller=ticket';
	params += '&task=feedback';
	params += '&cid=' + ticket_id;
	params += '&feedback=' + value;
	xmlHttp.open("POST", url, true);

	//Send the proper header information along with the request
	xmlHttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");

	xmlHttp.send(params);

	window.rsticketspro_rating.options.disabled = true;
}

var rst_buffer;

function rst_search(value) {
	if (value.length == 0) {
		rst_close_search();
		return;
	}

	if (rst_buffer)
		clearTimeout(rst_buffer);
	rst_buffer = setTimeout(function () {
		rst_search_ajax(value);
	}, 300);
}

function rst_search_ajax(value) {
	var xmlHttp = new XMLHttpRequest();

	var url = 'index.php?option=com_rsticketspro&task=kbsearch';
	url += '&filter=' + value;
	url += '&sid=' + Math.random();
	xmlHttp.onreadystatechange = function () {
		if (xmlHttp.readyState == 4) {
			document.getElementById('rst_livesearch').innerHTML = xmlHttp.responseText;
			document.getElementById('rst_livesearch').style.border = '1px solid #A5ACB2';
			document.getElementById('rst_livesearch').style.display = '';
		}
	}
	xmlHttp.open("GET", url, true);
	xmlHttp.send(null);
}

function rst_close_search() {
	document.getElementById('rst_search_value').value = '';
	document.getElementById('rst_livesearch').style.display = 'none';
	document.getElementById('rst_livesearch').innerHTML = '';
	document.getElementById('rst_livesearch').style.border = '0px';

	return false;
}

function rst_disable_staff() {

}

RSTicketsPro.removeData = function(button) {
    jQuery(button).fadeOut({
        complete: function() {
            jQuery('#rsticketspro_remove_data_and_close_account').fadeIn();
        }
    });
};

RSTicketsPro.requestRemoveData = function(button) {
    jQuery(button).prop('disabled', true).addClass('disabled');
    var container = jQuery('#rsticketspro_remove_data_and_close_account');

    var url = Joomla.getOptions('system.paths').root + '/index.php';
    var token = Joomla.getOptions('csrf.token');
    var data = {
        'option': 'com_rsticketspro',
        'task': 'removedata.request'
    };
    data[token] = 1;
    jQuery.post(url, data, function(response){
        container.fadeOut({
            complete: function() {
                container.find('.alert-warning').removeClass('alert-warning').addClass('alert-info');
                container.find('.alert').text(response);
                container.fadeIn();
            }
        })
    });
};

RSTicketsPro.initRaty = function(params) {
	jQuery(document).ready(function($) {
		params.click = function(score, evt) {
			var url = Joomla.getOptions('system.paths').base;
			var ticketId = document.getElementsByName('id')[0].value;
			$(this).data('raty').readOnly(true);
			$('#com-rsticketspro-rated-message').hide().html(Joomla.JText._('RST_TICKET_FEEDBACK_SENT')).fadeIn();
			RSTicketsPro.sendRating(url + '/index.php?option=com_rsticketspro', score, ticketId);
		};

		params.starType = 'i';

		$('#star').raty(params);
	});
};

RSTicketsPro.sendKBFeedback = function (article_id) {
	var token = Joomla.getOptions('csrf.token');
	var data = {};
	data['cid'] = article_id;
	data[token] = 1;

	jQuery.ajax({
		type		: 'POST',
		url			: Joomla.getOptions('system.paths').base + '/index.php?option=com_rsticketspro&task=sendfeedback',
		dataType	: 'json',
		data		: data,
		success		: function(response) {
			if (response.valid)
			{
				Joomla.renderMessages({'message': [response.message]});
			}
			else
			{
				Joomla.renderMessages({'error': [response.message]});
			}

			var feedbackContainer = document.querySelector('.rst-kb-helpful-container');
			if (typeof feedbackContainer === 'object')
			{
				feedbackContainer.parentNode.removeChild(feedbackContainer);
			}

			var messageContainer = document.getElementById('system-message-container');
			if (typeof messageContainer === 'object') {
				messageContainer.scrollIntoView(true);
			}
		}
	});
}