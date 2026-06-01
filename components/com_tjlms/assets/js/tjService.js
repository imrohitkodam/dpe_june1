/*
 * @version    SVN:<SVN_ID>
 * @package    com_tjlms
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved
 * @license    GNU General Public License version 2, or later
 */

var tjService = {
	postData: function(url, formData, params) {
		var promise = jQuery.ajax({url: url, type: 'POST', async:true, data:formData,dataType: 'json',processData: false,contentType: false});
		return promise;
	}
}
