/**
 * @package     Jlike
 * @subpackage  com_jlike
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2021 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

var addtodoservice = {

	siteRoot: Joomla.getOptions("system.paths").base,
	getAgencyUsersUrl: '/index.php?option=com_jlike&task=agency.getAgencyUsers&format=json',
	markTodoCompleteUrl: '/index.php?option=com_jlike&task=recommendation.markComplete&format=json',
	addTodosUrl: '/dpejuly24/dpe/code/index.php?option=com_dpe&task=users.todoSave',

	postData: function(url, formData, params) {
		if(!params){
			params = {};
		}
		params['url']		    = this.siteRoot + url;
		params['data'] 		    = formData;
		params['type'] 		    = typeof params['type'] != "undefined" ? params['type'] : 'POST';
		params['async'] 	    = typeof params['async'] != "undefined" ? params['async'] :false;
		params['dataType'] 	    = typeof params['datatype'] != "undefined" ? params['datatype'] : 'json';
		params['contentType'] 	= typeof params['contentType'] != "undefined" ? params['contentType'] : 'application/x-www-form-urlencoded; charset=UTF-8';
		params['processData'] 	= typeof params['processData'] != "undefined" ? params['processData'] : true;

		var promise = jQuery.ajax(params);
		return promise;
	},
	getAgencyUsers: function (formData, params) {
		return this.postData(this.getAgencyUsersUrl, formData, params);
	},
	markComplete: function (formData, params) {
		return this.postData(this.markTodoCompleteUrl, formData, params);
	},
}
