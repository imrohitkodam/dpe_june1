/*
 * @package    Com_Jlike
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2019 TechJoomla. All rights reserved
 * @license    GNU General Public License version 2, or later
 */
var slaService = {

	siteRoot: Joomla.getOptions("system.paths").root,
	todoUrl: '/index.php?option=com_sla&task=slaactivity.updateTodo&format=json',
	deleteActivityUrl: '/index.php?option=com_sla&task=slaactivity.deleteActivity&format=json',
	archiveActivityUrl: '/index.php?option=com_sla&task=slaactivity.archiveActivity&format=json',
	showToolsUrl: '/index.php?option=com_sla&task=slatools.getSlaTools&format=json',
	showActivitiesUrl: '/index.php?option=com_sla&task=slaactivity.getSlaActivityTypes&format=json',

	postData: function(url, formData, params) {
		if(!params){
			params = {};
		}

		params['url']		= this.siteRoot + url;
		params['data'] 		= formData;
		params['type'] 		= typeof params['type'] != "undefined" ? params['type'] : 'POST';
		params['async'] 	= typeof params['async'] != "undefined" ? params['async'] :false;
		params['dataType'] 	= typeof params['datatype'] != "undefined" ? params['datatype'] : 'json';
		params['contentType'] 	= typeof params['contentType'] != "undefined" ? params['contentType'] : 'application/x-www-form-urlencoded; charset=UTF-8';
		params['processData'] 	= typeof params['processData'] != "undefined" ? params['processData'] : true;

		var promise = jQuery.ajax(params);
		return promise;
	},
	updateTodo: function (formData, params) {
		return this.postData(this.todoUrl, formData, params);
	},
	deleteActivity: function (formData, params) {
		return this.postData(this.deleteActivityUrl, formData, params);
	},
	showTools: function (formData, params) {
		return this.postData(this.showToolsUrl, formData, params);
	},
	showActivities: function (formData, params) {
		return this.postData(this.showActivitiesUrl, formData, params);
	},
	archiveActivity: function (formData, params) {
		return this.postData(this.archiveActivityUrl, formData, params);
	}
}
