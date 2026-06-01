/**
 * @version     1.0.0
 * @package     mod_cookienotificationsbuilder
 * @copyright   Copyright (C) 2014. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      JoomlaForce Team <support@joomlaforce.com> - http://www.joomlaforce.com
 */

/**
 * Function for hide options.
 * 
 * @param array
 *            sub_fields The list of fields to Hide.
 */
function js_HideOptions(sub_fields) {
	if ((/^\s*$/).test(sub_fields)) {
		return;
	}

	fields = sub_fields.split(',');
	for ( var i = 0; i < fields.length; i++) {
		js_HideOption(fields[i]);
	}
}

/**
 * Function for show options.
 * 
 * @param array
 *            sub_fields The list of fields to Show.
 */
function js_ShowOptions(sub_fields) {
	if ((/^\s*$/).test(sub_fields)) {
		return;
	}

	fields = sub_fields.split(',');

	for ( var i = 0; i < fields.length; i++) {
		if ((/^\s*$/).test(fields[i])) {
			continue;
		}
		js_ShowOption(fields[i]);
	}
}

/**
 * Function for show options.
 * 
 * @param array
 *            sub_fields The list of fields to Show.
 */
function js_ShowOptionsByControl(control_field, sub_fields_array) {
	if ((/^\s*$/).test(control_field)) {
		return;
	}

	if ($(control_field) == null) {
		return;
	}

	var key = $(control_field).get("value");
	var sub_fields = sub_fields_array[key];

	if (sub_fields === undefined) {
		return;
	}

	fields = sub_fields.split(',');

	for ( var i = 0; i < fields.length; i++) {
		if ((/^\s*$/).test(fields[i])) {
			continue;
		}
		js_ShowOption(fields[i]);
	}
}

/**
 * Function for Show one options
 * 
 * @param string
 *            field_name Name of Field to show.
 */
function js_ShowOption(field_id) {
	var field = $(field_id);
	if (field == null) {
		field = $(field_id + '-lbl');
	}

	if (field == null) {
		return;
	}

	// Joomla 3.0
	var control = field.getParent('div.control-group');

	// Joomla 2.5 field
	if (control == null) {
		control = field.getParent('li');
	}

	// Show
	if (control !== null && control.hasClass('hide')) {
		control.removeClass('hide');
	}
}

/**
 * Function for Hide one options
 * 
 * @param string
 *            field_name Name of Field to hide.
 */
function js_HideOption(field_id) {
	var field = $(field_id);
	if (field == null) {
		field = $(field_id + '-lbl');
	}

	if (field == null) {
		return;
	}

	// Joomla 3.0
	var control = field.getParent('div.control-group');

	// Joomla 2.5 field
	if (control == null) {
		control = field.getParent('li');
	}

	// Hide
	if (control !== null && !control.hasClass('hide')) {
		control.addClass('hide');
	}
}

function showDefaultTabIndex(option_name) {
	toggleDefaultTabIndex(option_name, true);
}

function hideDefaultTabIndex(option_name) {
	toggleDefaultTabIndex(option_name, false);
}

function toggleDefaultTabIndex(option_name, show) {
	// Get the default tab index
	var default_tab_index = $('jform_params_' + 'mod_tabFirstIndex');

	// Loop the select options:
	for ( var i = 0; i < default_tab_index.length; i++) {
		if (default_tab_index[i].value != option_name) {
			continue;
		} else {
			// get the Bootstrap element:
			var bootstrap_element = $('jform_params_' + 'mod_tabFirstIndex'
					+ '_chzn_o_' + i);

			if (show && default_tab_index[i].hasClass('hide')) {
				bootstrap_element.setStyle('display', null);
				default_tab_index[i].removeClass('hide');

				if (default_tab_index.selectedIndex == -1) {
					changeSelectedDefaultTabIndex(default_tab_index);
				}
			}

			if (!show && !default_tab_index[i].hasClass('hide')) {
				bootstrap_element.setStyle('display', 'none');
				default_tab_index[i].addClass('hide');
				if (default_tab_index[i].selected) {
					default_tab_index[i].removeAttribute('selected');
					changeSelectedDefaultTabIndex(default_tab_index);
				}
			}

			break;
		}
	}
}

function changeSelectedDefaultTabIndex(default_tab_index) {
	// Get the single bootstrap
	chzn_DIV = $('jform_params_' + 'mod_tabFirstIndex' + '_chzn');
	chzn_single = chzn_DIV.firstChild;

	// Loop the select options:
	for ( var i = 0; i < default_tab_index.length; i++) {
		// get the Bootstrap element:
		var bootstrap_element = $('jform_params_' + 'mod_tabFirstIndex'
				+ '_chzn_o_' + i);

		if (default_tab_index[i].hasClass('hide')) {
			continue;
		} else {
			default_tab_index[i].selected = true;
			chzn_single.firstChild.innerHTML = default_tab_index[i].innerHTML;
			break;
		}
	}

	if (i == default_tab_index.length) {
		default_tab_index.selectedIndex = -1;
		chzn_single.firstChild.innerHTML = '';
	}
}
