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
# Last modified: 26 Nov 2020, 01:41:48
========================================================= */

/* Cookies Directive - The rewrite. Now a jQuery plugin
 * Version: 2.0.1
 * Author: Ollie Phillips
 * 24 October 2013
 */

function popupwindow(url, title, w, h) {
    wLeft = window.screenLeft ? window.screenLeft : window.screenX;
    wTop = window.screenTop ? window.screenTop : window.screenY;

    var left = wLeft + (window.innerWidth / 2) - (w / 2);
    var top = wTop + (window.innerHeight / 2) - (h / 2);
    return window.open(url, title, 'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=no, resizable=no, copyhistory=no, width=' + w + ', height=' + h + ', top=' + top + ', left=' + left);
}
jQuery.noConflict();
;(function($) {

	$.cookiesDirective = function(options) {
		// Default Cookies Directive Settings
		var settings = $.extend(
		{
			w357_position: 'bottom',
			w357_duration: 60,
			w357_animate_duration: 2000,
			w357_limit: 0,
			w357_message: null,				
			w357_buttonText: "Ok, I've understood!",	
			w357_display_decline_btn: 1,	
			w357_buttonDeclineText: "Decline",
			w357_display_cancel_btn: 0,	
			w357_buttonCancelText: "Cancel",
			w357_buttonMoreText: "More Info",
			w357_buttonMoreLink: "",		
			w357_display_more_info_btn: 1,
			w357_fontColor: '#F1F1F3',
			w357_linkColor: '#FFF',
			w357_fontSize: '12px',
			w357_backgroundColor: '#323A45',
			w357_borderWidth: '1', // in pixels
			w357_body_cover: '1',
			w357_height: 'auto',
			w357_cookie_name: 'cookiesDirective',
			w357_link_target: '_self',
			w357_popup_width: '800',
			w357_popup_height: '600',
			w357_customText: 'Your custom text for the cookies policy goes here...',
			w357_more_info_btn_type: 'link',
			w357_blockCookies: 0,
			w357_enableConfirmationAlerts: 0,
			w357_confirm_allow_msg: 'Performing this action will enable all cookies set by this website. Are you sure that you want to enable all cookies on this website?',
			w357_confirm_delete_msg: 'Performing this action will remove all cookies set by this website. Are you sure that you want to disable and delete all cookies from your browser?',
			w357_show_in_iframes: 0,
			w357_shortcode_is_enabled_on_this_page: 1,
			w357_base_url: '',
			w357_always_display: 0,
			w357_expiration_cookies: 365, // days
			w357_expiration_cookieAccept: 365, // days
			w357_expiration_cookieDecline: 180, // days
			w357_expiration_cookieCancel: 3 // days
		}, options);

		var displayCookies = function () 
		{
			// (1st cookie after accept the primary cookie "cookiesDirective")
			$.ajax({
				type   : 'GET',
				url: ""+settings.w357_base_url+"/index.php?option=com_ajax&plugin=cookiespolicynotificationbar&format=raw&method=displayCookiesTable",
				success: function (response) {
					if(response.data){
						var result = '';
						$.each(response.data, function (index, value) {
							result = result + ' ' + value;
						});
						$('.cpnb-cookies-data').html(result);
					} else {
						$('.cpnb-cookies-data').html(response);
					}
				},
				error: function(response) {
					var data = '',
						obj = $.parseJSON(response.responseText);
					for(key in obj){
						data = data + ' ' + obj[key] + '<br/>';
					}
					$('.cpnb-cookies-data').html(data);
				}
			});
		}

		// loading spinner for ajax request
		$(document).on({
			ajaxStart: function() { 
				$('.cpnb-cookies-data').hide();
				$('.cpnb-cookies-data-ajax').addClass("cpnb-loading-gif");
				$('.cpnb-cookies-data-ajax-waiting-txt').show();
				$('.cpnb-reload-btn').hide();
			},
			ajaxStop: function() { 
				$('.cpnb-cookies-data').show();
				$('.cpnb-cookies-data-ajax').removeClass("cpnb-loading-gif"); 
				$('.cpnb-cookies-data-ajax-waiting-txt').hide();
				$('.cpnb-reload-btn').show();
			}
		});

		// function to decline cookies from browser
		var declineCookies = function ()
		{
			$.ajax({
				type   : 'GET',
				dataType: "json",
				url: ""+settings.w357_base_url+"/index.php?option=com_ajax&plugin=cookiespolicynotificationbar&format=raw&method=declineCookies",
				success: function(response) {
				},
				error: function(response) {
				}
			});
		}

		// function to allow cookies from browser
		var allowCookies = function ()
		{
			var cookie_name =  $('.cpnb-cookies-data').data("cookie-name");

			$.ajax({
				type   : 'GET',
				url: ""+settings.w357_base_url+"/index.php?option=com_ajax&plugin=cookiespolicynotificationbar&format=raw&method=allowCookies&cookie_name=" + cookie_name,
				success: function (response) {
					// reload only if the cookies table is in the same page
					if(settings.w357_blockCookies)
					{
						location.reload(true);
					}
				},
				error: function(response) {
				}
			});
		}

		// reload page
		$(document).on("click", ".cpnb-reload-btn", function(e){
			e.preventDefault();			
			displayCookies();
		});

		// shortcode inside the modal
		$(document).on("click", ".cpnb-button-more-modal", function(e){
			e.preventDefault();
			$(".cpnb-cookies-data").hide();
			$(".cpnb-cookies-data-ajax").addClass("cpnb-loading-gif");
			$(".cpnb-cookies-data-ajax-waiting-txt").show();
			$(".cpnb-reload-btn").hide();
			displayCookies();
		});

		// hide the notification bar after click on cancel button
		$(document).on("click", ".cpnb-button-cancel", function(e){
			e.preventDefault();

			var dp = settings.w357_position.toLowerCase();
			var opts = new Array();
			if(dp == 'top') {
				opts['in'] = {'top':'0'};
				opts['out'] = {'top':'-300'};
			} else if(dp == 'bottom') {
				opts['in'] = {'bottom':'0'};
				opts['out'] = {'bottom':'-300'};
			} else if (dp == 'top-left' || dp == 'top-right') {
				opts['in'] = {'top':'10'};
				opts['out'] = {'top':'-300'};
			} else if (dp == 'bottom-left' || dp == 'bottom-right') {
				opts['in'] = {'bottom':'10'};
				opts['out'] = {'bottom':'-300'};
			}

			// Close the overlay
			$('.cpnb-outer').animate(opts['out'], 1000, function() { 
				// Remove the elements from the DOM and reload page
				$('.cpnb-outer').remove();
			});

		});

		// remove cookies after click on decline button
		$(document).on("click", ".cpnb-button-decline", function(e){
			e.preventDefault();

			if (settings.w357_blockCookies && settings.w357_enableConfirmationAlerts)
			{
				if(!confirm(settings.w357_confirm_delete_msg)) 
				{
					return false;
				}
			}

			var dp = settings.w357_position.toLowerCase();
			var opts = new Array();
			if(dp == 'top') {
				opts['in'] = {'top':'0'};
				opts['out'] = {'top':'-300'};
			} else if(dp == 'bottom') {
				opts['in'] = {'bottom':'0'};
				opts['out'] = {'bottom':'-300'};
			} else if (dp == 'top-left' || dp == 'top-right') {
				opts['in'] = {'top':'10'};
				opts['out'] = {'top':'-300'};
			} else if (dp == 'bottom-left' || dp == 'bottom-right') {
				opts['in'] = {'bottom':'10'};
				opts['out'] = {'bottom':'-300'};
			}

			// Close the overlay
			$('.cpnb-outer').animate(opts['out'], 1000, function() { 
				// Remove the elements from the DOM and reload page
				$('.cpnb-outer').remove();
			});

			declineCookies();

			if(settings.w357_blockCookies)
			{
				location.reload(true);
			}
		});

		var getCookies = function(){
			var pairs = document.cookie.split(";");
			var cookies = {};
			for (var i=0; i<pairs.length; i++){
				var pair = pairs[i].split("=");
				cookies[(pair[0]+'').trim()] = unescape(pair[1]);
			}
			return cookies;
		}

		/**
		 * get cookie by name without using a regular expression
		 */
		var getCookie = function(name) {
			var getCookieValues = function(cookie) {
				var cookieArray = cookie.split('=');
				return cookieArray[1].trim();
			};

			var getCookieNames = function(cookie) {
				var cookieArray = cookie.split('=');
				return cookieArray[0].trim();
			};

			var cookies = document.cookie.split(';');
			var cookieValue = cookies.map(getCookieValues)[cookies.map(getCookieNames).indexOf(name)];

			return (cookieValue === undefined) ? null : cookieValue;
		};

		/**
		 * alternative: get cookie by name with using a regular expression
		 */
		var getCookiebyName = function(name){
			var pair = document.cookie.match(new RegExp(name + '=([^;]+)'));
			return !!pair ? pair[1] : null;
		};

		/**
		 * [Gets the cookie value if the cookie key exists in the right format]
		 * @param  {[string]} name [name of the cookie]
		 * @return {[string]}      [value of the cookie]
		 */
		var getCookie = function (name) {
			return parseCookies()[name] || '';
		};

		/**
		 * [Parsing the cookieString and returning an object of the available cookies]
		 * @return {[object]} [map of the available objects]
		 */
		var parseCookies = function () {
			var cookieData = (typeof document.cookie === 'string' ? document.cookie : '').trim();

			return (cookieData ? cookieData.split(';') : []).reduce(function (cookies, cookieString) {
				var cookiePair = cookieString.split('=');

				cookies[cookiePair[0].trim()] = cookiePair.length > 1 ? cookiePair[1].trim() : '';

				return cookies;
			}, {});
		};

		// remove cookies after click on delete button (in shortcode
		$(document).on("click", ".cpnb-delete-btn", function(e){
			e.preventDefault();

			$('.cpnb-cookies-data').hide();
			$('.cpnb-cookies-data-ajax').addClass("cpnb-loading-gif");
			$('.cpnb-cookies-data-ajax-waiting-txt').show();
			$('.cpnb-reload-btn').hide();

			if (settings.w357_blockCookies && settings.w357_enableConfirmationAlerts)
			{
				if(!confirm(settings.w357_confirm_delete_msg)) 
				{
					return false;
				}
			}

			var dp = settings.w357_position.toLowerCase();
			var opts = new Array();
			if(dp == 'top') {
				opts['in'] = {'top':'0'};
				opts['out'] = {'top':'-300'};
			} else if(dp == 'bottom') {
				opts['in'] = {'bottom':'0'};
				opts['out'] = {'bottom':'-300'};
			} else if (dp == 'top-left' || dp == 'top-right') {
				opts['in'] = {'top':'10'};
				opts['out'] = {'top':'-300'};
			} else if (dp == 'bottom-left' || dp == 'bottom-right') {
				opts['in'] = {'bottom':'10'};
				opts['out'] = {'bottom':'-300'};
			}

			// Close the overlay
			$('.cpnb-outer').animate(opts['out'], 1000, function() { 
				// Remove the elements from the DOM and reload page
				$('.cpnb-outer').remove();
			});

			// DELETE COOKIES
			var getCookies = Object.keys(parseCookies());
			for (index = 0; index < getCookies.length; index++) 
			{
				$.removeCookie(getCookies[index], { path: '/' });
				$.removeCookie(getCookies[index], { path: settings.w357_base_url });
				console.log('the ' + getCookies[index] + ' has been deleted.');
			}

			$.ajax({
				type   : 'GET',
				dataType: "json",
				url: ""+settings.w357_base_url+"/index.php?option=com_ajax&plugin=cookiespolicynotificationbar&format=raw&method=deleteCookies",
				success: function(response) {
					if (response["offline"] == 1)
					{
						// if the website is offline redirect the user to the login page
						location.reload(true);
					}
					else
					{						
						// BEGIN: Display the cookies notification bar again
						// Perform consent checks
						if((!getCookie(settings.w357_cookie_name) && !getCookie('cookiesDeclined')) || settings.w357_always_display) 
						{
							if(settings.w357_limit > 0) {
								// Display limit in force, record the view
								if(!getCookie('cookiesDisclosureCount')) {
									setCookie('cookiesDisclosureCount',1,1);		
								} else {
									var disclosureCount = getCookie('cookiesDisclosureCount');
									disclosureCount ++;
									setCookie('cookiesDisclosureCount',disclosureCount,1);
								}
								
								// Have we reached the display limit, if not make disclosure
								if(settings.w357_limit >= getCookie('cookiesDisclosureCount')) {
									disclosure(settings);
								}
							} else {
								// No display limit
								disclosure(settings);
							}
						}	
						// END: Display the cookies notification bar again
					}
				},
				error: function(response) {
				}
			});

			setTimeout(function(){
					displayCookies();
			}, 1000);

		});

		// allow cookies
		$(document).on("click", ".cpnb-allow-btn", function(e){
			e.preventDefault();
			
			if (settings.w357_blockCookies && settings.w357_enableConfirmationAlerts)
			{
				if(!confirm(settings.w357_confirm_allow_msg)) 
				{
					return false;
				}
			}

			var dp = settings.w357_position.toLowerCase();
			var opts = new Array();
			if(dp == 'top') {
				opts['in'] = {'top':'0'};
				opts['out'] = {'top':'-300'};
			} else if(dp == 'bottom') {
				opts['in'] = {'bottom':'0'};
				opts['out'] = {'bottom':'-300'};
			} else if (dp == 'top-left' || dp == 'top-right') {
				opts['in'] = {'top':'10'};
				opts['out'] = {'top':'-300'};
			} else if (dp == 'bottom-left' || dp == 'bottom-right') {
				opts['in'] = {'bottom':'10'};
				opts['out'] = {'bottom':'-300'};
			}

			// Close the overlay
			$('.cpnb-outer').animate(opts['out'], 1000, function() { 
				// Remove the elements from the DOM and reload page
				$('.cpnb-outer').remove();
			});
			
			allowCookies();
		});

		// get the cookies table - this will run on page load
		if(parseInt(settings.w357_shortcode_is_enabled_on_this_page))
		{
			displayCookies();
		}

		// Perform consent checks
		if((!getCookie(settings.w357_cookie_name) && !getCookie('cookiesDeclined')) || settings.w357_always_display) 
		{
			if(settings.w357_limit > 0) {
				// Display limit in force, record the view
				if(!getCookie('cookiesDisclosureCount')) {
					setCookie('cookiesDisclosureCount',1,1);		
				} else {
					var disclosureCount = getCookie('cookiesDisclosureCount');
					disclosureCount ++;
					setCookie('cookiesDisclosureCount',disclosureCount,1);
				}
				
				// Have we reached the display limit, if not make disclosure
				if(settings.w357_limit >= getCookie('cookiesDisclosureCount')) {
					disclosure(settings);
				}
			} else {
				// No display limit
				disclosure(settings);
			}		
		}		
	};

	// Used to load external javascript files into the DOM
	$.cookiesDirective.loadScript = function(options) {
		var settings = $.extend({
			uri: 		'', 
			appendTo: 	'body'
		}, options);	
		
		var elementId = String(settings.appendTo);
		var sA = document.createElement("script");
		sA.src = settings.uri;
		sA.type = "text/javascript";
		sA.onload = sA.onreadystatechange = function() {
			if ((!sA.readyState || sA.readyState == "loaded" || sA.readyState == "complete")) {
				return;
			} 	
		}
		switch(settings.appendTo) {
			case 'head':			
				$('head').append(sA);
			  	break;
			case 'body':
				$('body').append(sA);
			  	break;
			default: 
				$('#' + elementId).append(sA);
		}
	}	 
	
	// Helper scripts
	// Get cookie
	var getCookie = function(name) {
		var nameEQ = name + "=";
		var ca = document.cookie.split(';');
		for(var i=0;i < ca.length;i++) {
			var c = ca[i];
			while (c.charAt(0)==' ') c = c.substring(1,c.length);
			if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
		}
		return null;
	}
	
	// Set cookie
	var setCookie = function(name,value,days) {
		if (days) {
			var date = new Date();
			date.setTime(date.getTime()+(days*24*60*60*1000));
			var expires = "; expires="+date.toGMTString();
		}
		else var expires = "";
		document.cookie = name+"="+value+expires+"; path=/";
	}
	
	// Detect IE < 9
	var checkIE = function(){
		var version;
		if (navigator.appName == 'Microsoft Internet Explorer') {
	        var ua = navigator.userAgent;
	        var re = new RegExp("MSIE ([0-9]{1,}[\.0-9]{0,})");
	        if (re.exec(ua) != null) {
	            version = parseFloat(RegExp.$1);
			}	
			if (version <= 8.0) {
				return true;
			} else {
				if(version == 9.0) {
					if(document.compatMode == "BackCompat") {
						// IE9 in quirks mode won't run the script properly, set to emulate IE8	
						var mA = document.createElement("meta");
						mA.content = "IE=EmulateIE8";				
						document.getElementsByTagName('head')[0].appendChild(mA);
						return true;
					} else {
						return false;
					}
				}	
				return false;
			}		
	    } else {
			return false;
		}
	}

	// Disclosure routines
	var disclosure = function(options) {
		var settings = options;
		settings.css = 'fixed';
		
		// IE 9 and lower has issues with position:fixed, either out the box or in compatibility mode - fix that
		if(checkIE()) {
			settings.w357_position = 'top';
			settings.css = 'absolute';
		}
		
		// Create overlay, vary the disclosure based on explicit/implied consent
		// Set our disclosure/message if one not supplied
		var html = ''; 
		html += '<div class="cpnb" data-cookie-name="'+settings.w357_cookie_name+'">';

		var default_positions = ["top", "bottom"];
		if (default_positions.indexOf(settings.w357_position) >= 0) 
		{
			html += '<div class="cpnb-outer cpnb-'+ settings.w357_position +'" id="w357_cpnb_outer" style="position:'+ settings.css +';'+ settings.w357_position + ':-300px;'
			html += 'height:' + settings.w357_height + ';background:' + settings.w357_backgroundColor + ';';
			html += 'color:' + settings.w357_fontColor + ';font-size:' + settings.w357_fontSize + ';">';
		} 
		else 
		{
			html += '<div class="cpnb-outer cpnb-div-position-'+settings.w357_position+'" id="w357_cpnb_outer" style="position:'+ settings.css +';'
			
			if (settings.w357_position == 'top-left' || settings.w357_position == 'top-right')
			{
				html += 'top: -300px;'
			}
			else if (settings.w357_position == 'bottom-left' || settings.w357_position == 'bottom-right')
			{
				html += 'bottom: -300px;'
			}

			html += 'display: flex; justify-content: center; flex-direction: column; position: fixed; '; // non-ajax
			html += 'height:' + settings.w357_height + ';background:' + settings.w357_backgroundColor + ';';
			html += 'color:' + settings.w357_fontColor + ';font-size:' + settings.w357_fontSize + ';">';
		}
		
		html += '<div class="cpnb-inner">';
			
		if (!settings.w357_message) 
		{
			settings.w357_message = 'We have placed cookies on your computer to help make this website better.';		
		}	
		html += '<div class="cpnb-message">' + settings.w357_message + '</div>';
		
		// Build the rest of the disclosure for implied and explicit consent
		var popup_onclick = (settings.w357_link_target === 'popup') ? 'onClick="popupwindow(\'' + settings.w357_buttonMoreLink + '?cpnb=1\', \'Cookies Policy\', ' +settings.w357_popup_width + ', ' +settings.w357_popup_height + '); return false;"' : '';
		
		html += '<div class="cpnb-buttons">';

		// ok button
		html += '<a class="cpnb-button cpnb-button-ok">' + settings.w357_buttonText + '</a>';

		// decline button
		if (settings.w357_buttonDeclineText && settings.w357_display_decline_btn)
		{
			html += '<a class="cpnb-button cpnb-button-decline">' + settings.w357_buttonDeclineText + ' </a>';
		}

		// cancel button
		if (settings.w357_buttonCancelText && settings.w357_display_cancel_btn)
		{
			html += '<a class="cpnb-button cpnb-button-cancel">' + settings.w357_buttonCancelText + ' </a>';
		}
		
		// more info button
		if (settings.w357_more_info_btn_type === 'custom_text') // modal
		{
			if (settings.w357_buttonMoreText && settings.w357_display_more_info_btn)
			{
				html += '<a class="cpnb-button cpnb-button-more-modal">' + settings.w357_buttonMoreText + ' </a>';
			}
		}
		else // link or menu item
		{
			if (settings.w357_buttonMoreText && settings.w357_display_more_info_btn)
			{
				html += '<a target="' + settings.w357_link_target + '" href="' + settings.w357_buttonMoreLink + '" class="cpnb-button cpnb-button-more-default" '+ popup_onclick +'>' + settings.w357_buttonMoreText + '</a>';
			}
		}
		
		html += '<div class="cpnb-clear-both"></div>';
		html += '</div>';
		html += '</div></div>';
		$('body').append(html);
		
		// Serve the disclosure, and be smarter about branching
		var dp = settings.w357_position.toLowerCase();
		var opts = new Array();
		if(dp == 'top') {
			opts['in'] = {'top':'0'};
			opts['out'] = {'top':'-300'};
		} else if(dp == 'bottom') {
			opts['in'] = {'bottom':'0'};
			opts['out'] = {'bottom':'-300'};
		} else if (dp == 'top-left' || dp == 'top-right') {
			opts['in'] = {'top':'10'};
			opts['out'] = {'top':'-300'};
		} else if (dp == 'bottom-left' || dp == 'bottom-right') {
			opts['in'] = {'bottom':'10'};
			opts['out'] = {'bottom':'-300'};
		}

		// Start animation
		settings.w357_animate_duration = 2000;
		$('.cpnb-outer').animate(opts['in'], settings.w357_animate_duration, function() 
		{
			// Implied consent, just a button to close it
			$('.cpnb-button-ok').click(function() 
			{
				if (settings.w357_blockCookies && settings.w357_enableConfirmationAlerts)
				{
					if (!confirm(settings.w357_confirm_allow_msg))
					{
						return false;
					}
				}

				// Close the overlay
				$('.cpnb-outer').animate(opts['out'],1000,function() 
				{ 
					// Remove the elements from the DOM and reload page
					$('.cpnb-outer').remove();
				});

				// allow cookies
				$.ajax({
					type   : 'GET',
					url: ""+settings.w357_base_url+"/index.php?option=com_ajax&plugin=cookiespolicynotificationbar&format=raw&method=allowCookies&cookie_name=" + settings.w357_cookie_name,
					success: function (response) {
						// Set a cookie to prevent this being displayed again
						setCookie(settings.w357_cookie_name, 1, settings.w357_expiration_cookieAccept);
						
						// reload only if the cookies table is in the same page
						if(settings.w357_blockCookies)
						{
							location.reload(true);
						}
					},
					error: function(response) {
					}
				});
			});

			// BEGIN: MODAL
			if (settings.w357_more_info_btn_type === 'custom_text') // modal
			{
				// set max-height of modal body
				var windowInnerHeight = window.innerHeight;
				var max_height = Math.max(150, windowInnerHeight - 250) + 'px';
				
				var defaultModalSettings = {
					content: '<span>Easy Modal/Dialog Box</span>',
					size: 'medium',
					fadeInModal: 800,
					fadeOutModal: 800,
					buttonText: "Ok, I've understood."
				};			
				
				$.easyModal = function (modalOptions) {

					var o = $.extend({}, defaultModalSettings, modalOptions),
						$wrap = $('<div class="cpnb-modal-wrap">').hide(); //Modal Wrapper

					// Modal with footer and action buttons
					$easyModal = $('<div class="cpnb-modal-outer cpnb-modal-bg cpnb-close">\
								<div class="cpnb-modal-inner cpnb-modal--' + o.size + '" style="max-height: ' + max_height + '">' + o.content + '<span id="cpnb-modal-close" class="cpnb-modal-close cpnb-close cpnb-close-icon-x"></span>' + '\
								<div class="cpnb-modal-footer">\
								<div class="cpnb-modal-actions"> \
								<a class="cpnb-button cpnb-button-ok-modal">'+ o.buttonText + '</a>\
								'+(o.display_decline_btn ? '<a class="cpnb-button cpnb-button-decline-modal">'+ o.buttonDeclineText + '</a>' : '')+'\
								'+(o.display_cancel_btn ? '<a class="cpnb-button cpnb-button-cancel-modal">'+ o.buttonCancelText + '</a>' : '')+'\
								</div>\
								</div>\
								</div>\
								</div>').hide(); // MAIN MODAL STRUCTURE WITH FOOTER

					// action called on click for show function
					function show() {

						var top, left;

						$wrap.fadeIn(o.fadeInModal, function () {
							$easyModal.fadeIn(o.fadeInModal);
						});

						top = Math.max($(window).height() - $easyModal.outerHeight(), 0) / 2;
						left = Math.max($(window).width() - $easyModal.outerWidth(), 0) / 2;

						$easyModal.css({
							top: top + $(window).scrollTop(),
							left: left + $(window).scrollLeft()
						});

						// hide the scrollbar when the modal window is show up
						$("html").css("overflow", "hidden");

						// get flex align depends on toolbar's position
						if (settings.w357_position.toLowerCase() == 'top')
						{
							$('.cpnb-modal-bg').css('-webkit-align-items', 'center');
							$('.cpnb-modal-bg').css('align-items', 'center');
						}
						else
						{
							$('.cpnb-modal-bg').css('-webkit-align-items', 'flex-start');
							$('.cpnb-modal-bg').css('align-items', 'flex-start');
						}

						// fix modal's height if window is resized
						$(window).on('resize', function(){
							var windowInnerHeight = window.innerHeight;
							$('.cpnb-modal-inner').css('max-height', (windowInnerHeight - 250) + 'px'); //set max height
						});

					}

					// Hiding the Modal function
					function hide() {
						$easyModal.fadeOut(o.fadeOutModal, function () {
							$wrap.fadeOut(o.fadeOutModal).remove();
						});

						// show the scrollbar when the modal window is hidden
						$("html").css("overflow", "visible");

						// Serve the disclosure, and be smarter about branching
						var dp = settings.w357_position.toLowerCase();
						var opts = new Array();
						if(dp == 'top') {
							opts['in'] = {'top':'0'};
							opts['out'] = {'top':'-300'};
						} else if(dp == 'bottom') {
							opts['in'] = {'bottom':'0'};
							opts['out'] = {'bottom':'-300'};
						} else if (dp == 'top-left' || dp == 'top-right') {
							opts['in'] = {'top':'10'};
							opts['out'] = {'top':'-300'};
						} else if (dp == 'bottom-left' || dp == 'bottom-right') {
							opts['in'] = {'bottom':'10'};
							opts['out'] = {'bottom':'-300'};
						}

						// Close the overlay
						$('.cpnb-outer').animate(opts['out'], 1000, function() { 
							// Remove the elements from the DOM and reload page
							$('.cpnb-outer').remove();
						});
					}

					$easyModal.find('.cpnb-close').on('click', function (e) {
						hide();
					});

					$easyModal.find('.cpnb-button-cancel-modal').on('click', function (e) {
						hide();
					});

					$easyModal.find('.cpnb-button-decline-modal').on('click', function (e) {
						
						if (settings.w357_blockCookies && settings.w357_enableConfirmationAlerts)
						{
							if(!confirm(settings.w357_confirm_delete_msg)) 
							{
								return false;
							}
						}

						// decline the cookies
						$.ajax({
							type   : 'GET',
							dataType: "json",
							url: ""+settings.w357_base_url+"/index.php?option=com_ajax&plugin=cookiespolicynotificationbar&format=raw&method=declineCookies",
							success: function(response) {
								if (response["offline"] == 1)
								{
									// if the website is offline redirect the user to the login page
									location.reload(true);
								}
								else
								{
									hide();
								}
							},
							error: function(response) {
							}
						});

						var dp = settings.w357_position.toLowerCase();
						var opts = new Array();
						if(dp == 'top') {
							opts['in'] = {'top':'0'};
							opts['out'] = {'top':'-300'};
						} else if(dp == 'bottom') {
							opts['in'] = {'bottom':'0'};
							opts['out'] = {'bottom':'-300'};
						} else if (dp == 'top-left' || dp == 'top-right') {
							opts['in'] = {'top':'10'};
							opts['out'] = {'top':'-300'};
						} else if (dp == 'bottom-left' || dp == 'bottom-right') {
							opts['in'] = {'bottom':'10'};
							opts['out'] = {'bottom':'-300'};
						}

						// Close the overlay
						$('.cpnb-outer').animate(opts['out'], 1000, function() { 
							// Remove the elements from the DOM and reload page
							$('.cpnb-outer').remove();
						});

					});

					$easyModal.find('.cpnb-button-ok-modal').on('click', function (e) {

						if (settings.w357_blockCookies && settings.w357_enableConfirmationAlerts)
						{
							if (!confirm(settings.w357_confirm_allow_msg))
							{
								return false;
							}
						}

						// Close the overlay
						$('.cpnb-outer').animate(opts['out'],1000,function() { 
							// Remove the elements from the DOM and reload page
							$('.cpnb-outer').remove();
						});
						hide();

						if (settings.w357_blockCookies)
						{
						// allow cookies
							$.ajax({
								type   : 'GET',
								url: ""+settings.w357_base_url+"/index.php?option=com_ajax&plugin=cookiespolicynotificationbar&format=raw&method=allowCookies&cookie_name=" + settings.w357_cookie_name,
								success: function (response) {
									// Set a cookie to prevent this being displayed again
									setCookie(settings.w357_cookie_name, 1, settings.w357_expiration_cookieAccept);

									// reload only if the cookies table is in the same page
									if(settings.w357_blockCookies)
									{
										location.reload(true);
									}
								},
								error: function(response) {
								}
							});
						}
						else
						{
							// Set a cookie to prevent this being displayed again
							setCookie(settings.w357_cookie_name, 1, settings.w357_expiration_cookieAccept);
						}
					});

					$('body').prepend($wrap.append($easyModal));
					show();
				};

				// hide the modal when the user clicks outside of this
				$(document).mouseup(function(e) 
				{
					var container = $('.cpnb-modal-inner');
				
					// if the target of the click isn't the container nor a descendant of the container
					if (!container.is(e.target) && container.has(e.target).length === 0) 
					{
						$('.cpnb-modal-outer').fadeOut(800, function () {
							$('.cpnb-modal-wrap').fadeOut(800).remove();
						});

						$('html').css("overflow-y", "scroll");
					}
				});

				// when the modal is active and the OK button of bar has been clicked
				$('.cpnb-button-ok').click(function() {

					$('.cpnb-modal-outer').fadeOut(800, function () {
						$('.cpnb-modal-wrap').fadeOut(800).remove();
					});
				});
				
				$('.cpnb-button-more-modal').click(function(e){
					$.easyModal({
						content: settings.w357_customText,
						size: 'medium',
						fadeInModal: 0,
						buttonText: settings.w357_buttonText,
						display_decline_btn: settings.w357_display_decline_btn,					
						buttonDeclineText: settings.w357_buttonDeclineText,					
						display_cancel_btn: settings.w357_display_cancel_btn,					
						buttonCancelText: settings.w357_buttonCancelText						
					})
				});
			}
			// END: MODAL
			
			// Do not cover the body of the page.
			var w357_cpnb_outer_height = $('#w357_cpnb_outer').height();
			console.log(w357_cpnb_outer_height + 'xxx');
			if (settings.w357_body_cover === 0)
			{
				if (w357_cpnb_outer_height != null && w357_cpnb_outer_height > 0)
				{
					if (settings.w357_position == 'top')
					{
						$('body').css('padding-top', w357_cpnb_outer_height+'px');
					}
					else
					{
						$('body').css('padding-bottom', w357_cpnb_outer_height+'px');
					}
				}
			}

			// Set a timer to remove the warning after 'settings.w357_duration' seconds
			setTimeout(function(){
				$('.cpnb-outer').animate({
					opacity:'0'
				},settings.w357_animate_duration, function(){
					$('.cpnb-outer').css(dp,'-300px');
				});
			}, settings.w357_duration * 1000);
		});	

	}

	$(window).load(function(){

		// get the config data values
		cpnb = cpnb_config;

		if (cpnb)
		{
			// Do not show the plugin in iFrames (e.g. modal popups)
			if (cpnb.w357_show_in_iframes == 0)
			{
				// hide in iFrames
				if (top != self) 
				{
					return false;
				}
			}

			// override the settings
			$.cookiesDirective({
				w357_position: cpnb.w357_position,
				w357_duration: parseInt(cpnb.w357_duration),
				w357_animate_duration: parseInt(cpnb.w357_animate_duration + 1000),
				w357_limit: parseInt(cpnb.w357_limit),
				w357_message: cpnb.w357_message,
				w357_buttonText: cpnb.w357_buttonText,
				w357_display_decline_btn: parseInt(cpnb.w357_display_decline_btn),
				w357_buttonDeclineText: cpnb.w357_buttonDeclineText,
				w357_display_cancel_btn: parseInt(cpnb.w357_display_cancel_btn),
				w357_buttonCancelText: cpnb.w357_buttonCancelText,
				w357_buttonMoreText: cpnb.w357_buttonMoreText,
				w357_buttonMoreLink: cpnb.w357_buttonMoreLink,
				w357_display_more_info_btn: parseInt(cpnb.w357_display_more_info_btn),
				w357_fontColor: cpnb.w357_fontColor,
				w357_linkColor: cpnb.w357_linkColor,
				w357_fontSize: cpnb.w357_fontSize,
				w357_backgroundColor: cpnb.w357_backgroundColor,
				w357_borderWidth: parseInt(cpnb.w357_borderWidth),
				w357_body_cover: parseInt(cpnb.w357_body_cover),
				w357_height: cpnb.w357_height,
				w357_cookie_name: cpnb.w357_cookie_name,
				w357_link_target: cpnb.w357_link_target,
				w357_popup_width: cpnb.w357_popup_width,
				w357_popup_height: cpnb.w357_popup_height,
				w357_customText: cpnb.w357_customText,
				w357_more_info_btn_type: cpnb.w357_more_info_btn_type,
				w357_blockCookies: parseInt(cpnb.w357_blockCookies),
				w357_enableConfirmationAlerts: parseInt(cpnb.w357_enableConfirmationAlerts),
				w357_confirm_allow_msg: cpnb.w357_confirm_allow_msg,
				w357_confirm_delete_msg: cpnb.w357_confirm_delete_msg,
				w357_show_in_iframes: parseInt(cpnb.w357_show_in_iframes),
				w357_shortcode_is_enabled_on_this_page: parseInt(cpnb.w357_shortcode_is_enabled_on_this_page),
				w357_base_url: cpnb.w357_base_url,
				w357_always_display: parseInt(cpnb.w357_always_display),
				w357_expiration_cookies: parseInt(cpnb.w357_expiration_cookies),
				w357_expiration_cookieAccept: parseInt(cpnb.w357_expiration_cookieAccept),
				w357_expiration_cookieDecline: parseInt(cpnb.w357_expiration_cookieDecline),
				w357_expiration_cookieCancel: parseInt(cpnb.w357_expiration_cookieCancel)
			});
		}
		else
		{
			console.log('error loading cpnb_config json data');
		}
	});
})(jQuery);