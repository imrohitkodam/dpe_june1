/**
 * @package     DPE
 * @subpackage  com_dpe
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

var multiagencyService = {

	siteRoot: Joomla.getOptions("system.paths").base,
	getAgencyInfoUrl: '/index.php?option=com_multiagency&task=multiagencyform.getAgencyInfo&format=json',

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
	getAgencyInfo: function (formData, params) {
		return this.postData(this.getAgencyInfoUrl, formData, params);
	},
}
