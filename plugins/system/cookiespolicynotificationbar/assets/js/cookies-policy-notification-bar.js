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

var categoriesSettingsCookie = {}; // Create array with all categories ID for: modalManager
var categoriesSettingsCookieOuter = {}; //Create array with first id (outter) and second id (inner)
var categoriesSettingsCookieInner = {}; //Create array with first id (inner) and second id (outter)
var m_modalManager_confirmationText = ''; //Modal LOCKED-CHECKBOX Confirmation Alert
var modalManagerSettings = [];

var afterCategoriesSettingsCookie;
var afterExpirationCookies;

window.addEventListener('load', function ()
{
	// Default Settings
	var settings = {
		w357_position: 'bottom',
		w357_hide_after_time: 'yes',
		w357_duration: 60,
		w357_animate_duration: 1000,
		w357_limit: 0,
		w357_message: null,
		w357_display_ok_btn: 1,
		w357_buttonText: "Ok, I've understood!",
		w357_display_decline_btn: 1,
		w357_buttonDeclineText: "Decline",
		w357_display_cancel_btn: 1,
		w357_buttonCancelText: "Cancel",
		w357_display_settings_btn: 1,
		w357_buttonSettingsText: "Settings",
		w357_buttonMoreText: "More Info",
		w357_buttonMoreLink: "",
		w357_display_more_info_btn: 1,
		w357_fontColor: '#F1F1F3',
		w357_linkColor: '#FFF',
		w357_fontSize: '12px',
		w357_backgroundColor: '#323A45',
		w357_borderWidth: 1,
		w357_body_cover: 1,
		w357_overlay_state: 0,
		w357_overlay_color: 'rgba(10,10,10, 0.3)',
		w357_height: 'auto',
		w357_cookie_name: 'cookiesDirective',
		w357_link_target: '_self',
		w357_popup_width: '800',
		w357_popup_height: '600',
		w357_customText: 'Your custom text for the cookies policy goes here...',
		w357_more_info_btn_type: 'custom_text',
		w357_blockCookies: 0,
		w357_autoAcceptAfterScrolling: 0,
		w357_numOfScrolledPixelsBeforeAutoAccept: 300,
		w357_reloadPageAfterAccept: 0,
		w357_enableConfirmationAlerts: 0,
		w357_enableConfirmationAlertsForAcceptBtn: 0,
		w357_enableConfirmationAlertsForDeclineBtn: 0,
		w357_enableConfirmationAlertsForDeleteBtn: 0,
		w357_confirm_allow_msg: 'Performing this action will enable all cookies set by this website. Are you sure that you want to enable all cookies on this website?',
		w357_confirm_delete_msg: 'Performing this action will remove all cookies set by this website. Are you sure that you want to disable and delete all cookies from your browser?',
		w357_show_in_iframes: 0,
		w357_shortcode_is_enabled_on_this_page: 1,
		w357_base_url: '',
		w357_current_url: '',
		w357_always_display: 0,
		w357_show_notification_bar: true,
		w357_expiration_cookies: 365, // days
		w357_expiration_cookieSettings: 365, // days
		w357_expiration_cookieAccept: 365, // days
		w357_expiration_cookieDecline: 180, // days
		w357_expiration_cookieCancel: 3, // days
		w357_accept_button_class_notification_bar: 'cpnb-accept-btn',
		w357_decline_button_class_notification_bar: 'cpnb-decline-btn',
		w357_cancel_button_class_notification_bar: 'cpnb-cancel-btn',
		w357_settings_button_class_notification_bar: 'cpnb-settings-btn',
		w357_moreinfo_button_class_notification_bar: 'cpnb-moreinfo-btn',
		w357_accept_button_class_notification_bar_modal_window: 'cpnb-accept-btn-m',
		w357_decline_button_class_notification_bar_modal_window: 'cpnb-decline-btn-m',
		w357_save_button_class_notification_bar_modal_window: 'cpnb-save-btn-m',
	}

	var modalManager = {
		// base settings
		w357_m_modalState : 1,
		w357_m_floatButtonState : 1,
		w357_m_floatButtonPosition : "bottom_left",
		w357_m_HashLink : "cookies",

		// styling
		w357_m_modal_menuItemSelectedBgColor: "rgba(200, 200, 200, 1)",
		w357_m_saveChangesButtonColorAfterChange: "rgba(255, 202, 152, 1)",
		w357_m_floatButtonIconSrc: "media/plg_system_cookiespolicynotificationbar/icons/cpnb-cookies-manager-icon-1-64x64.png",

		// texts
		w357_m_floatButtonText : "Cookies Manager",
		w357_m_modalHeadingText: "Advanced Cookie Settings",
		w357_m_checkboxText : "Enable",
		w357_m_lockedText : "(Locked)",
		w357_m_EnableAllButtonText : "Allow All Cookies",
		w357_m_DeclineAllButtonText : "Decline All Cookies",
		w357_m_SaveChangesButtonText : "Save Settings",
		w357_m_confirmationAlertRequiredCookies: "These cookies are strictly necessary for this website. You can't disable this category of cookies. Thank you for understanding!"
	}

	// var cpnb_config = {};

	// Get data from server, check for each if empty or null then use default settings
	// EXAMPLE: PHP ECHO SCRIPT -> var newSettings = { .. }
	var varsToBeParsedInt = ["w357_overlay_state", "w357_borderWidth", "w357_body_cover", "w357_expiration_cookies", "w357_expiration_cookieSettings", "w357_expiration_cookieAccept", "w357_expiration_cookieDecline", "w357_expiration_cookieCancel", "w357_duration", "w357_animate_duration", "w357_limit", "w357_display_ok_btn", "w357_display_decline_btn", "w357_display_cancel_btn", "w357_display_settings_btn", "w357_display_more_info_btn", "w357_blockCookies", "w357_autoAcceptAfterScrolling", "w357_numOfScrolledPixelsBeforeAutoAccept", "w357_reloadPageAfterAccept", "w357_enableConfirmationAlerts", "w357_enableConfirmationAlertsForAcceptBtn", "w357_enableConfirmationAlertsForDeclineBtn", "w357_enableConfirmationAlertsForDeleteBtn", "w357_show_in_iframes", "w357_shortcode_is_enabled_on_this_page", "w357_always_display"];
	for (var obj in cpnb_config)
	{
		if (cpnb_config[obj] !== null & cpnb_config[obj] !== "")
		{
			if (varsToBeParsedInt.indexOf(obj) >= 0)
			{
				settings[obj] = parseInt(cpnb_config[obj]); // Overwrite settings
			}
			else
			{
				settings[obj] = cpnb_config[obj]; //Overwrite settings
			}
		}
	}

	// Create-Read Cookie Limit => cpnb_cookie_limit + Check if higher + counter warning seen
	if (settings.w357_limit > 0)
	{
		if (cpnb_readCookie("cpnb_cookie_limit") == null)
		{
			// Create cookie limit
			cpnb_createCookie("cpnb_cookie_limit", 1, settings.w357_expiration_cookies);
		}
		else
		{
			// Counter +1 and Check if higher
			if (settings.w357_blockCookies == 0)
			{
				var counterCookie = parseInt(cpnb_readCookie("cpnb_cookie_limit")) + 1;
				cpnb_createCookie("cpnb_cookie_limit", counterCookie, settings.w357_expiration_cookies);
			}
		}
	}

	// Used in "if statement" for design perpuse (margin-top), so as not to hide text
	var warning_div_position = ["top-left", "top-right", "bottom-left", "bottom-right", "center"];

	// Check if Iframe
	function cpnb_checkDIfIframeDisplay()
	{
		if (settings.w357_show_in_iframes == 0)
		{
			if (self == top)
			{
				return true; // Not In frame
			}
			else
			{
				return false; // In frame
			}
		}
		else
		{
			return true;
		}
	}

	// Check Always Display
	// cpnb_createCookie("cpnb_cookie_alwaysDisplay", settings.w357_always_display, settings.w357_expiration_cookies);
	function cpnb_checkAlwaysDisplay()
	{
		if ( ((settings.w357_always_display == 1)) )
		{
			return true;
		}
		else if ((settings.w357_always_display !== 1) && (cpnb_readCookie(settings.w357_cookie_name) == null) && (cpnb_readCookie("cpnbCookiesDeclined") == null) && (cpnb_readCookie("cpnbCookiesCancelled") == null))
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	// Check Limit (cookie: cpnb_cookie_limit)
	function cpnb_checkCookieLimitDisplay()
	{
		if (settings.w357_always_display !== 1)
		{
			if (settings.w357_limit > 0)
			{
				if (cpnb_readCookie("cpnb_cookie_limit") !== null)
				{
						var counterCookie = parseInt(cpnb_readCookie("cpnb_cookie_limit"));
						if (counterCookie > settings.w357_limit)
						{
							return false;
						}
						else
						{
							return true;
						}
				}
			}
			else
			{
				return true;
			}
		}
		else
		{
			return true;
		}
	}

	// Max-height create
	var windowHeight = window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight;
	var maxHeight = windowHeight - 0.2*windowHeight;
	maxHeight = parseInt(maxHeight.toFixed(0));
	var marginTopWindow = windowHeight*0.06;
	marginTopWindow = parseInt(marginTopWindow.toFixed(0));

	// Check if => iFrame && Always Display (w357_always_display) && Limit (cpnb_cookie_limit)
	if (cpnb_checkDIfIframeDisplay() && cpnb_checkAlwaysDisplay() && cpnb_checkCookieLimitDisplay() && settings.w357_show_notification_bar == true)
	{
		// Create js to show warning
		var elem = document.createElement('div');

		// MaxHeight for WarningBox
		var maxHeightWarningBox = window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight;
		maxHeightWarningBox -= 40;

		var cnbwb = '';
			cnbwb += ' <div id="cpnb" class="cpnb" data-cookie-name="'+decodeURI(settings.w357_cookie_name)+'">';
			cnbwb += '	<div class="cpnb-warningBox-show-fade-in cpnb-outer cpnb-div-position-'+settings.w357_position+'" id="w357_cpnb_outer" style=" display: flex; justify-content: center; flex-direction: column; position: fixed; height: '+settings.w357_height+'; background: '+settings.w357_backgroundColor+'; color: '+settings.w357_fontColor+'; font-size: '+settings.w357_fontSize+';">';

		if (settings.w357_message !== null & settings.w357_message !== "")
		{
			cnbwb += '		<div class="cpnb-inner" style="max-height: '+maxHeightWarningBox+'px;"><div class="cpnb-message">'+decodeURI(settings.w357_message)+'</div>';
		}
		else
		{
			cnbwb += '		<div class="cpnb-inner" style="max-height: '+maxHeightWarningBox+'px;"><div class="cpnb-message">We use cookies to improve your experience on our website. By browsing this website, you agree to our use of cookies.</div>';
		}

		if (warning_div_position.indexOf(settings.w357_position) >= 0)
		{
			cnbwb += '			<div class="cpnb-buttons" style="margin-top:10px;">';
		}
		else
		{
			cnbwb += '			<div class="cpnb-buttons">';
		}

		// ACCEPT BUTTON
		if (settings.w357_display_ok_btn == 1 )
		{
			var enableConfirFun	= settings.w357_enableConfirmationAlertsForAcceptBtn;
			var msgFun 			= settings.w357_confirm_allow_msg;
			var cookieNameFun 	= settings.w357_cookie_name;
			var linkFun 		= settings.w357_current_url + (settings.w357_current_url.match(/\?/) ? '&' : '?') + 'cpnb_method=cpnbCookiesAccepted';
			var expiration 		= settings.w357_expiration_cookieAccept;
			cnbwb += '				<a id="cpnb-accept-btn" rel="nofollow" onClick="cpnb_warning_accept_button(\''+decodeURI(enableConfirFun)+'\', \''+decodeURI(msgFun)+'\', \''+cookieNameFun+'\', \''+linkFun+'\', \''+expiration+'\', \''+settings.w357_base_url+'\', \'notification_bar\', \''+settings.w357_reloadPageAfterAccept+'\', \''+settings.w357_overlay_state+'\')" class="cpnb-button cpnb-button-ok '+decodeURI(settings.w357_accept_button_class_notification_bar)+'">'+decodeURI(settings.w357_buttonText)+'</a>';
		}

		// DECLINE BUTTON
		if (settings.w357_display_decline_btn == 1 )
		{
			var enableConfirFun = settings.w357_enableConfirmationAlertsForDeclineBtn;
			var msgFun 			= settings.w357_confirm_delete_msg;
			var cookieNameFun 	= settings.w357_cookie_name;
			var linkFun 		= settings.w357_current_url + (settings.w357_current_url.match(/\?/) ? '&' : '?') + 'cpnb_method=cpnbCookiesDeclined';
			var expiration 		= settings.w357_expiration_cookieDecline;
			cnbwb += '				<a id="cpnb-decline-btn" rel="nofollow" onClick="cpnb_warning_decline_button(\''+decodeURI(enableConfirFun)+'\', \''+decodeURI(msgFun)+'\', \''+cookieNameFun+'\', \''+linkFun+'\', \''+expiration+'\')" class="cpnb-button cpnb-button-decline '+decodeURI(settings.w357_decline_button_class_notification_bar)+'">'+decodeURI(settings.w357_buttonDeclineText)+'</a>';
		}

		// CANCEL BUTTON
		if (settings.w357_display_cancel_btn == 1 )
		{
			var cookieNameFun 	= settings.w357_cookie_name;
			var linkFun 		= settings.w357_current_url + (settings.w357_current_url.match(/\?/) ? '&' : '?') + 'cpnb_method=CookiesCancelled';
			var expiration 		= settings.w357_expiration_cookieCancel;
			cnbwb += '				<a id="cpnb-cancel-btn" rel="nofollow" onClick="cpnb_warning_cancel_button(\'' + cookieNameFun + '\', \''+expiration+'\', \''+linkFun+'\', \''+settings.w357_overlay_state+'\')" class="cpnb-button cpnb-button-cancel '+decodeURI(settings.w357_cancel_button_class_notification_bar)+'">'+decodeURI(settings.w357_buttonCancelText)+'</a>';
		}

		// SETTINGS BUTTON
		if (settings.w357_display_settings_btn == 1 )
		{
			cnbwb += '				<a id="cpnb-settings-btn" rel="nofollow" onClick="cpnb_m_openModal()" class="cpnb-button cpnb-button-settings '+decodeURI(settings.w357_settings_button_class_notification_bar)+'">'+decodeURI(settings.w357_buttonSettingsText)+'</a>';
		}

		if (settings.w357_display_more_info_btn == 1)
		{
			if (settings.w357_more_info_btn_type == "link" || settings.w357_more_info_btn_type == "menu_item")
			{
				if (settings.w357_link_target == "_self")
				{
					cnbwb += '				<a id="cpnb-moreinfo-btn" rel="nofollow" href="'+settings.w357_buttonMoreLink+'" target="_self" class="cpnb-button cpnb-button-more-default '+decodeURI(settings.w357_moreinfo_button_class_notification_bar)+'">'+decodeURI(settings.w357_buttonMoreText)+'</a>';
				}
				else if (settings.w357_link_target == "_blank")
				{
					cnbwb += '				<a id="cpnb-moreinfo-btn" rel="nofollow" href="'+settings.w357_buttonMoreLink+'" target="_blank" class="cpnb-button cpnb-button-more-default '+decodeURI(settings.w357_moreinfo_button_class_notification_bar)+'">'+decodeURI(settings.w357_buttonMoreText)+'</a>';
				}
				else if (settings.w357_link_target == "popup")
				{
					cnbwb += '				<a id="cpnb-moreinfo-btn" rel="nofollow" onClick="w357_openPopUpWindowMoreInfo(\''+settings.w357_buttonMoreLink+'\',\'titlos\', \''+settings.w357_popup_height+'\', \''+settings.w357_popup_width+'\')" class="cpnb-button cpnb-button-more-default '+decodeURI(settings.w357_moreinfo_button_class_notification_bar)+'">'+decodeURI(settings.w357_buttonMoreText)+'</a>';
				}
			}
			else if (settings.w357_more_info_btn_type == "custom_text")
			{
				cnbwb += '				<a id="cpnb-moreinfo-btn" rel="nofollow" onClick="cpnb_openModalMoreInfo()" class="cpnb-button cpnb-button-more-default '+decodeURI(settings.w357_moreinfo_button_class_notification_bar)+'" id="cpnb_button_more_modal">'+decodeURI(settings.w357_buttonMoreText)+'</a>';
			}
		}

			cnbwb += '				<div class="cpnb-clear-both"></div>';
			cnbwb += '			</div>';
			cnbwb += '		</div>';
			cnbwb += '	</div>';
			cnbwb += '</div>';
                
		// Check if background overlay is enabled
		if (settings.w357_overlay_state == 1) 
		{
			cnbwb += '<div id="cpnb_warningBoxBgOverlay" style="background-color:' + settings.w357_overlay_color + ';">';
		}

		elem.innerHTML = cnbwb;
		document.body.appendChild(elem);
                
                //Center WarningBox Style
                if (settings.w357_position == "center") 
                {
                    var windowWidth = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;
                    var warningBoxHeight = document.getElementById("w357_cpnb_outer").clientHeight;
                    var pus = document.createElement('style');
                    pus.type = 'text/css';
                    pus.innerHTML = '.cpnb-div-position-center {top:0; left:0; margin-top: '+((windowHeight-warningBoxHeight)/2)+'px; margin-left: '+((windowWidth-500)/2)+'px} ';
                    document.getElementsByTagName('head')[0].appendChild(pus);
                }
		
		// Auto-accept after scrolling
		if (settings.w357_autoAcceptAfterScrolling)
		{
			var scroll_counter = 0; 
			window.addEventListener("scroll", function() 
			{
				if(scroll_counter == 0) // if counter is 1, it will not execute
				{
					if(window.pageYOffset > settings.w357_numOfScrolledPixelsBeforeAutoAccept) 
					{
						var enableConfirFun	= settings.w357_enableConfirmationAlertsForAcceptBtn;
						var msgFun 			= settings.w357_confirm_allow_msg;
						var cookieNameFun 	= settings.w357_cookie_name;
						var linkFun 		= settings.w357_current_url + (settings.w357_current_url.match(/\?/) ? '&' : '?') + 'cpnb_method=cpnbCookiesAccepted';
						var expiration 		= settings.w357_expiration_cookieAccept;

						cpnb_warning_accept_button(decodeURI(enableConfirFun), decodeURI(msgFun), cookieNameFun, linkFun, expiration, settings.w357_base_url, 'notification_bar', settings.w357_reloadPageAfterAccept, settings.w357_overlay_state);

						scroll_counter++; // increment the counter by 1, new value = 1
					}
				}
			});
		}
		
		// Timeout Duration function, Hide warning box box-shadow => w357_duration
		function cpnb_hideInfoBox(ms, gapMs)
		{
			setTimeout(function(){
				var e = document.getElementById("w357_cpnb_outer");
				e.classList.remove("cpnb-warningBox-show-fade-in");
				e.classList.add("cpnb-warningBox-show-fade-out");
				
				if (settings.w357_overlay_state == 1) 
				{
					document.getElementById("cpnb_warningBoxBgOverlay").classList.add("cpnb-warningBox-show-fade-out");
				}
			}, ms);
			// Hide warning
			setTimeout(function(){
				document.getElementById("w357_cpnb_outer").style.display = "none";
				if (settings.w357_overlay_state == 1) 
				{
					document.getElementById("cpnb_warningBoxBgOverlay").style.display = "none";
				}
			}, ms+gapMs);
		}

		// Hide the notification bar after X seconds
		if (settings.w357_hide_after_time != 'display_always')
		{
			cpnb_hideInfoBox(settings.w357_duration*1000, settings.w357_animate_duration);
		}

		// Create style WarningBox duration open & close
		var stDur = settings.w357_animate_duration/1000;

		// If border on warning box, problem with animation height.
		var offsetHeightBorderx1 = ["top", "bottom"];
		if (offsetHeightBorderx1.indexOf(settings.w357_position) >= 0) {
			var subOffsetHeight = settings.w357_borderWidth;
		} else {
			var subOffsetHeight = settings.w357_borderWidth*2;
		}

		var offsetHeight = document.getElementById('w357_cpnb_outer').offsetHeight - subOffsetHeight;
		var w357_cpnb_outer_height = document.getElementById('w357_cpnb_outer').offsetHeight;

		var pus = document.createElement('style');
		pus.type = 'text/css';
		pus.innerHTML = '.cpnb-warningBox-show-fade-in {overflow:hidden; animation-name: slidein; animation-duration: '+stDur+'s;} ';
		pus.innerHTML += '.cpnb-warningBox-show-fade-out { opacity:0; overflow:hidden; padding:0; animation-name: slideout; animation-duration: '+stDur+'s;} ';
		pus.innerHTML += '@keyframes slidein { from { height:0px; opacity:0; } to { height:'+offsetHeight+'px; opacity:1;} }';
		pus.innerHTML += '@keyframes slideout { from {  opacity:1; } to { opacity:0; } }';
		document.getElementsByTagName('head')[0].appendChild(pus);

		// Create Modal
		if (settings.w357_more_info_btn_type == "custom_text")
		{
			// Create style with modal duration open & close
			var stDur = settings.w357_animate_duration/5000;
			var pus = document.createElement('style');
			pus.type = 'text/css';
			pus.innerHTML = '.cpnb-modal-show-fade-in { visibility: visible; opacity: 1; -webkit-transition: opacity '+stDur+'s, visibility '+stDur+'s; transition: opacity '+stDur+'s, visibility '+stDur+'s; } ';
			pus.innerHTML += '.cpnb-modal-show-fade-out { visibility: hidden; opacity: 0; -webkit-transition: opacity '+stDur+'s, visibility '+stDur+'s; transition: opacity '+stDur+'s, visibility '+stDur+'s; } ';
			document.getElementsByTagName('head')[0].appendChild(pus);

			var elem = document.createElement('div');
			var cnbwb = '<div id="cpnb_modal_wrap" class="cpnb-modal-wrap cpnb-modal-show-fade-out">';
				cnbwb += '		<div class="cpnb-modal-outer cpnb-modal-bg cpnb-close" id="cpnb_outter-moreinfo" style="top: 0px; left: 0px; align-items: flex-start;">';
				cnbwb += '				<div class="cpnb-modal-inner cpnb-modal--medium" id="cpnb_inner-moreinfo" style="z-index: 100000000; max-height:'+maxHeight+'px; margin-top:'+marginTopWindow+'px;">';
				cnbwb += '						<div class="cpnb-modal-inner-text">'+settings.w357_customText+'</div>';
				cnbwb += '						<span onClick="cpnb_closeModalMoreInfo()" id="cpnb-modal-close-moreinfo" class="cpnb-modal-close cpnb-close cpnb-close-icon-x"></span>';
				cnbwb += '						<div id="close_cpnb_modal_footer" class="cpnb-modal-footer">';
				cnbwb += '								<div id="close_cpnb_modal_actions" class="cpnb-modal-actions">';

				// ACCEPT BUTTON
				if (settings.w357_display_ok_btn == 1)
				{
					var msgFun 			= settings.w357_confirm_allow_msg;
					var expiration 		= settings.w357_expiration_cookieAccept;
					var linkFun 		= settings.w357_current_url + (settings.w357_current_url.match(/\?/) ? '&' : '?') + 'cpnb_method=cpnbCookiesAccepted';
					cnbwb += '				<a id="cpnb-accept-btn-m-info" rel="nofollow" onClick="cpnb_warning_accept_button(\''+decodeURI(enableConfirFun)+'\', \''+decodeURI(msgFun)+'\', \''+cookieNameFun+'\', \''+linkFun+'\', \''+expiration+'\', \''+settings.w357_base_url+'\', \'modal\', \''+settings.w357_reloadPageAfterAccept+'\', \''+settings.w357_overlay_state+'\')" class="cpnb-button cpnb-button-ok '+decodeURI(settings.w357_accept_button_class_notification_bar)+'">'+decodeURI(settings.w357_buttonText)+'</a>';
				}

				// DECLINE BUTTON
				if (settings.w357_display_decline_btn == 1 )
				{
					var msgFun 			= settings.w357_confirm_delete_msg;
					var expiration 		= settings.w357_expiration_cookieDecline;
					var linkFun 		= settings.w357_current_url + (settings.w357_current_url.match(/\?/) ? '&' : '?') + 'cpnb_method=cpnbCookiesDeclined';
					cnbwb += '				<a id="cpnb-decline-btn-m-info" rel="nofollow" onClick="cpnb_warning_decline_button(\''+decodeURI(enableConfirFun)+'\', \''+decodeURI(msgFun)+'\', \''+cookieNameFun+'\', \''+linkFun+'\', \''+expiration+'\')" class="cpnb-button cpnb-button-decline '+decodeURI(settings.w357_decline_button_class_notification_bar)+'">'+decodeURI(settings.w357_buttonDeclineText)+'</a>';
				}

				// CANCEL BUTTON
				if (settings.w357_display_cancel_btn == 1 )
				{
					var expiration 		= settings.w357_expiration_cookieCancel;
					var linkFun 		= settings.w357_current_url + (settings.w357_current_url.match(/\?/) ? '&' : '?') + 'cpnb_method=CookiesCancelled';
					cnbwb += '				<a id="cpnb-cancel-btn-m-info" rel="nofollow" onClick="cpnb_warning_cancel_button(\'' + cookieNameFun + '\', \''+expiration+'\', \''+linkFun+'\', \''+settings.w357_overlay_state+'\')" class="cpnb-button cpnb-button-cancel '+decodeURI(settings.w357_cancel_button_class_notification_bar)+'">'+decodeURI(settings.w357_buttonCancelText)+'</a>';
				}

				// SETTINGS BUTTON
				if (settings.w357_display_settings_btn == 1 )
				{
					cnbwb += '				<a id="cpnb-settings-btn-m-info" rel="nofollow" onClick="cpnb_m_openModal()" class="cpnb-button cpnb-button-settings '+decodeURI(settings.w357_settings_button_class_notification_bar)+'">'+decodeURI(settings.w357_buttonSettingsText)+'</a>';
				}

				cnbwb += '								</div>';
				cnbwb += '						</div>';
				cnbwb += '				</div>';
				cnbwb += '				<div class="cpnb-modal-show-fade-out" id="modal_close_bg" onClick="cpnb_closeModalMoreInfo()" style=" z-index: 99999999; top:0; left:0; position:fixed; width:100%; height:100%;"></div>';
				cnbwb += '		</div>';
				cnbwb += '</div>';
			elem.innerHTML = cnbwb;
			document.body.appendChild(elem);
		}
	} // END: Check if => iFrame && Always Display (w357_always_display) && Limit (cpnb_cookie_limit)

	// Do not cover the body of the page.
	if (settings.w357_body_cover === 0)
	{
		if (w357_cpnb_outer_height != null && w357_cpnb_outer_height > 0) 
		{
			if (settings.w357_position == 'top')
			{
				document.getElementsByTagName("body")[0].style.paddingTop = w357_cpnb_outer_height + 'px';
			}
			else
			{
				document.getElementsByTagName("body")[0].style.paddingBottom = w357_cpnb_outer_height + 'px';
			}
		}
	}

////***************** START MANAGER *****************////
// Get data from server, check for each if empty or null then use default settings
// EXAMPLE: PHP ECHO SCRIPT -> var newSettings = { .. }

    //Overide ModalManager settings
    var varsToBeParsedInt = ["w357_m_modalState", "w357_m_floatButtonState"];
    //var cpnb_manager = [];
    for (var obj in cpnb_manager)
    {
        if (cpnb_manager[obj] !== null & cpnb_manager[obj] !== "")
        {
            if (varsToBeParsedInt.indexOf(obj) >= 0)
            {
                modalManager[obj] = parseInt(cpnb_manager[obj]); // Overwrite settings
            }
            else
            {
                modalManager[obj] = cpnb_manager[obj]; //Overwrite settings
            }
        }

    }

	modalManagerSettings = modalManager;
	
	//Get array with information about cookies categories (Read Cookie)
	if (cpnb_readCookie("cpnb_cookiesSettings") != null) {
		categoriesSettingsCookie = JSON.parse(cpnb_readCookie("cpnb_cookiesSettings"));
	} else {
		categoriesSettingsCookie = {};
		//categoriesSettingsCookie = {"required-cookies":1,"analytical-cookies":0,"social-media-cookies":0,"targeted-advertising-cookies":0};
	}

	//cpnb_createCookie("cpnb_cookiesSettings", JSON.stringify(categoriesSettingsCookie), settings.w357_expiration_cookies);
	afterCategoriesSettingsCookie = categoriesSettingsCookie;
	afterExpirationCookies = settings.w357_expiration_cookieSettings;

    //Check if ModalManager is enabled
    if (cpnb_checkDIfIframeDisplay() && settings.w357_blockCookies == 1 && modalManager.w357_m_modalState == 1) {

        //Pass Modal LOCKED-CHECKBOX Confirmation Alert
        m_modalManager_confirmationText = modalManager.w357_m_confirmationAlertRequiredCookies;

        //Create Left Menu Item Selected Class
        var pus = document.createElement('style');
            pus.type = 'text/css';
            pus.innerHTML = '.cpnb-manager-modal-left-item-selected, .cpnb-manager-modal-left-item-unSelected:hover, .cpnb-manager-modal-left-item-unSelected:focus {background: '+modalManager.w357_m_modal_menuItemSelectedBgColor+';} ';
            document.getElementsByTagName('head')[0].appendChild(pus);

        var HTMLleftMenu = '';
        var HTMLinformationBoxes = '';
        var categoriesCounter = 0; //Pick first category and first box with information to show.

		// Open-Close Left Menu Button
		HTMLinformationBoxes += '<span class="cpnb-left-menu-toggle" onClick="cpnb_toggle_responsive_menu()"><img class="cpnb-left-menu-toggle-button" src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAADIAAAAyCAYAAAAeP4ixAAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAADsMAAA7DAcdvqGQAAABxSURBVGhD7dYxDYBAEETRFYECKkRQIgARBA1IQAEi0IInmKPeGu4m/yW/2mq6DQDAX3aDZhW3QYdKD63FkNp6h6wGjQoAABSnQYtKf5fW4vutLa8hk0GDAgAAxWXQptLfpbX4fmvLa0hvUKcAAF+LeABZddsp++ZlVAAAAABJRU5ErkJggg=="></span>';

        // Modal Manager HEADER
        HTMLinformationBoxes += '<div class="cpnb-m-header">'+modalManager.w357_m_modalHeadingText+'</div>';

		for (var categoryID in cpnb_cookiesCategories) 
		{
			if (cpnb_cookiesCategories[categoryID].cookie_category_status == 1) 
			{
				var innerCookieCategory = cpnb_cookiesCategories[categoryID].cookie_category_id;

				categoriesCounter++;

				// Check if First Category
				if (categoriesCounter == 1) 
				{
					// Create menu item
					HTMLleftMenu += '<div onClick="cpnb_m_changeModalCategory(\''+categoryID+'\')" id="mlm_menu_category_id_'+categoryID+'" class="cpnb-manager-modal-left-item-selected cpnb-manager-modal-left-item modal-left-menu-item-id-'+categoryID+'">'+cpnb_cookiesCategories[categoryID].cookie_category_name+'</div>';
					//Create new information box (right)
					HTMLinformationBoxes += '<div id="mmr_box_category_id_'+categoryID+'" class="cpnb-manager-modal-right-selected cpnb-manager-modal-right cpnb-manager-modal-right-scrollbar">';
				} else {
					// Create menu item
					HTMLleftMenu += '<div onClick="cpnb_m_changeModalCategory(\''+categoryID+'\')" id="mlm_menu_category_id_'+categoryID+'" class="cpnb-manager-modal-left-item-unSelected cpnb-manager-modal-left-item modal-left-menu-item-id-'+categoryID+'">'+cpnb_cookiesCategories[categoryID].cookie_category_name+'</div>';
					// Create new information box (right)
					HTMLinformationBoxes += '<div id="mmr_box_category_id_'+categoryID+'" class="cpnb-manager-modal-right-unSelected cpnb-manager-modal-right cpnb-manager-modal-right-scrollbar">';
				}

				HTMLinformationBoxes += '<h3 class="cpnb-cookies-category-heading-responsive">' + cpnb_cookiesCategories[categoryID].cookie_category_name + '</h3>';
				HTMLinformationBoxes += cpnb_cookiesCategories[categoryID].cookie_category_description;

				//Initialize vars for saveCategoryState
				var init_genericExp = settings.w357_expiration_cookies;
				var init_baseUrl    = settings.w357_current_url;
				var init_cookieName = settings.w357_cookie_name;
				var init_acceptExp  = settings.w357_expiration_cookieAccept;
				var init_declineExp = settings.w357_expiration_cookieDecline;

				//(ENABLE-DISABLE BUTTON) - Check if cookie for category state exists. If exists check what state has been saved.
				if (categoriesSettingsCookie[innerCookieCategory] == null) {
					if (cpnb_cookiesCategories[categoryID].cookie_category_checked_by_default == 1) {
						HTMLinformationBoxes += '<div class="modalCheckBox"><input onClick="cpnb_m_saveCategoryCookiesState(\''+categoryID+'\', \''+init_genericExp+'\', \''+init_baseUrl+'\', \''+init_cookieName+'\', \''+init_acceptExp+'\', \''+init_declineExp+'\')" class="cpnb-checkbox" type="checkbox" value="1" id="modalCheckBox_id_'+categoryID+'" name="" checked/><label for="modalCheckBox_id_'+categoryID+'">'+modalManager.w357_m_checkboxText+'</label></div>';
						categoriesSettingsCookie[innerCookieCategory] = 1;
					} else if (cpnb_cookiesCategories[categoryID].cookie_category_checked_by_default == 2) {
						//DISABLED CHECKBOX - cpnb_m_lockedCheckbox
						HTMLinformationBoxes += '<div class="modalCheckBox"><input onClick="cpnb_m_lockedCheckbox(\''+categoryID+'\', \''+settings.w357_expiration_cookies+'\', \''+settings.w357_enableConfirmationAlerts+'\')" class="cpnb-checkbox" type="checkbox" value="1" id="modalCheckBox_id_'+categoryID+'" name="" checked/><label for="modalCheckBox_id_'+categoryID+'">'+modalManager.w357_m_checkboxText+'</label></div>';
						categoriesSettingsCookie[innerCookieCategory] = 1;
					} else {
						HTMLinformationBoxes += '<div class="modalCheckBox"><input onClick="cpnb_m_saveCategoryCookiesState(\''+categoryID+'\', \''+init_genericExp+'\', \''+init_baseUrl+'\', \''+init_cookieName+'\', \''+init_acceptExp+'\', \''+init_declineExp+'\')" class="cpnb-checkbox" type="checkbox" value="1" id="modalCheckBox_id_'+categoryID+'" name=""/><label for="modalCheckBox_id_'+categoryID+'">'+modalManager.w357_m_checkboxText+'</label></div>';
						categoriesSettingsCookie[innerCookieCategory] = 0;
					}
				} else {
					//Check if LOCKED
					if (cpnb_cookiesCategories[categoryID].cookie_category_checked_by_default != 2) {
						if (categoriesSettingsCookie[""+innerCookieCategory+""] == 1) {
							HTMLinformationBoxes += '<div class="modalCheckBox"><input onClick="cpnb_m_saveCategoryCookiesState(\''+categoryID+'\', \''+init_genericExp+'\', \''+init_baseUrl+'\', \''+init_cookieName+'\', \''+init_acceptExp+'\', \''+init_declineExp+'\')" type="checkbox" value="1" id="modalCheckBox_id_'+categoryID+'" name="" checked/><label for="modalCheckBox_id_'+categoryID+'">'+modalManager.w357_m_checkboxText+'</label></div>';
							categoriesSettingsCookie[innerCookieCategory] = 1;
						} else {
							HTMLinformationBoxes += '<div class="modalCheckBox"><input onClick="cpnb_m_saveCategoryCookiesState(\''+categoryID+'\', \''+init_genericExp+'\', \''+init_baseUrl+'\', \''+init_cookieName+'\', \''+init_acceptExp+'\', \''+init_declineExp+'\')" class="cpnb-checkbox" type="checkbox" value="1" id="modalCheckBox_id_'+categoryID+'" name=""/><label for="modalCheckBox_id_'+categoryID+'">'+modalManager.w357_m_checkboxText+'</label></div>';
							categoriesSettingsCookie[innerCookieCategory] = 0;
						}
					} else {
						//DISABLED CHECKBOX - cpnb_m_lockedCheckbox
						HTMLinformationBoxes += '<div class="modalCheckBox"><input onClick="cpnb_m_lockedCheckbox(\''+categoryID+'\', \''+settings.w357_expiration_cookies+'\', \''+settings.w357_enableConfirmationAlerts+'\')" class="cpnb-checkbox" type="checkbox" value="1" id="modalCheckBox_id_'+categoryID+'" name="" checked/><label for="modalCheckBox_id_'+categoryID+'">'+modalManager.w357_m_checkboxText+' '+modalManager.w357_m_lockedText+'</label></div>';
						categoriesSettingsCookie[innerCookieCategory] = 1;
					}
				}

					HTMLinformationBoxes += '<div style="display:block; width:100%; height:30px;"></div>';
				HTMLinformationBoxes += '</div>';

				//Create IO ARRAY with IDs
				categoriesSettingsCookieInner[innerCookieCategory] = categoryID;
				categoriesSettingsCookieOuter[categoryID] = innerCookieCategory;
			}
        }

        HTMLinformationBoxes += '<div class="cpnb-m-bottom-buttons">';

            var enableConfirFun = settings.w357_enableConfirmationAlertsForAcceptBtn;
            var cookieNameFun   = settings.w357_cookie_name;
            var msgFun          = settings.w357_confirm_allow_msg;
            var expiration      = settings.w357_expiration_cookieAccept;
			var linkFun 		= settings.w357_current_url + (settings.w357_current_url.match(/\?/) ? '&' : '?') + 'cpnb_method=cpnbCookiesAccepted';
            HTMLinformationBoxes += '<input id="cpnb-accept-btn-m" class="cpnb-button cpnb-m-enableAllButton '+decodeURI(settings.w357_accept_button_class_notification_bar_modal_window)+'" type="button" onClick="cpnb_warning_accept_button(\''+decodeURI(enableConfirFun)+'\', \''+decodeURI(msgFun)+'\', \''+cookieNameFun+'\', \''+linkFun+'\', \''+expiration+'\', \''+settings.w357_base_url+'\', \'modal\', \''+settings.w357_reloadPageAfterAccept+'\', \''+settings.w357_overlay_state+'\')" value="'+modalManager.w357_m_EnableAllButtonText+'">';

            var enableConfirFun = settings.w357_enableConfirmationAlertsForDeclineBtn;
            var msgFun 	= settings.w357_confirm_delete_msg;
            var expiration 	= settings.w357_expiration_cookieDecline;
			var linkFun 	= settings.w357_current_url + (settings.w357_current_url.match(/\?/) ? '&' : '?') + 'cpnb_method=cpnbCookiesDeclined&cpnb_btn_area=cookiesManager';
            HTMLinformationBoxes += '<input id="cpnb-decline-btn-m" class="cpnb-button cpnb-m-DeclineAllButton '+decodeURI(settings.w357_decline_button_class_notification_bar_modal_window)+'" type="button" onClick="cpnb_warning_decline_button(\''+decodeURI(enableConfirFun)+'\', \''+decodeURI(msgFun)+'\', \''+cookieNameFun+'\', \''+linkFun+'\', \''+expiration+'\')" value="'+modalManager.w357_m_DeclineAllButtonText+'">';

			var linkFun = settings.w357_current_url + (settings.w357_current_url.match(/\?/) ? '&' : '?') + 'cpnb_method=cpnbCookiesManagerSaveSettings';
			var init_genericExp = settings.w357_expiration_cookies;
			var init_baseUrl    = settings.w357_current_url;
			var init_cookieName = settings.w357_cookie_name;
			var init_acceptExp  = settings.w357_expiration_cookieAccept;
			var init_declineExp = settings.w357_expiration_cookieDecline;
            HTMLinformationBoxes += '<input id="cpnb-save-btn-m" class="cpnb-button cpnb-m-SaveChangesButton '+decodeURI(settings.w357_save_button_class_notification_bar_modal_window)+'" type="button" value="'+modalManager.w357_m_SaveChangesButtonText+'" onClick="cpnb_warning_save_settings_button(\''+linkFun+'\', \''+init_genericExp+'\', \''+init_baseUrl+'\', \''+init_cookieName+'\', \''+init_acceptExp+'\', \''+init_declineExp+'\')">';
        HTMLinformationBoxes += '</div>';

		// Get the total of enabled categories and if is more than one then create the cpnb_cookiesSettings cookie, or if is only the required-cookies category do not create the cpnb_cookiesSettings cookie to pass the Cookiebot validation service
		var categoriesSettingsCookie_arr_values = Object.keys(categoriesSettingsCookie).map(function(itm) { return categoriesSettingsCookie[itm]; }); // IE compatibility

		var categoriesSettingsCookie_count_of_enabled = categoriesSettingsCookie_arr_values.reduce(function(a, b){
			return a + b;
		}, 0);

		if (categoriesSettingsCookie_count_of_enabled > 1)
		{
			cpnb_createCookie("cpnb_cookiesSettings", JSON.stringify(categoriesSettingsCookie), settings.w357_expiration_cookieSettings);
		}
	
        afterCategoriesSettingsCookie = categoriesSettingsCookie;
        afterExpirationCookies = settings.w357_expiration_cookies;
		
            var cnbwb = '<div id="cpnb_manager_wrap" class="cpnb-modal-wrap cpnb-modal-show-fade-out">';
                cnbwb += '		<div class="cpnb-modal-outer cpnb-modal-bg cpnb-close" id="cpnb_outter" style="top: 0px; left: 0px; align-items: flex-start;">';
                cnbwb += '				<div class="cpnb-modal-inner cpnb-modal--medium" id="cpnb_inner" style="z-index: 100000000; height:'+maxHeight+'px; margin-top:'+marginTopWindow+'px; overflow:hidden;">';
                cnbwb += '						<span onClick="cpnb_m_closeModal()" id="cpnb-modal-close" class="cpnb-modal-close cpnb-close cpnb-close-icon-x"></span>';

                cnbwb += '						<div id="cpnb_manager_modal_left" class="cpnb-manager-modal-left">';
                cnbwb += 							HTMLleftMenu;
                cnbwb += '						</div>';

                cnbwb +=                                                HTMLinformationBoxes;

                cnbwb += '				</div>';
                cnbwb += '				<div class="cpnb-modal-show-fade-out" id="cpnb_manager_wrap_close_bg" onClick="cpnb_m_closeModal()" style=" z-index: 99999999; top:0; left:0; position:fixed; width:100%; height:100%;"></div>';
                cnbwb += '		</div>';
                cnbwb += '</div>';
            var elem = document.createElement('div');
            elem.innerHTML = cnbwb;
            document.body.appendChild(elem);

		// Check if floatButton is enabled
		if (modalManager.w357_m_floatButtonState == 1 && (cpnb_readCookie("cpnbCookiesCancelled") != null || cpnb_readCookie("cpnbCookiesDeclined") != null || cpnb_readCookie(settings.w357_cookie_name) != null))
		{
			//Floating Button
			var tooltipPosition = (modalManager.w357_m_floatButtonPosition === 'bottom_right') ? 'left' : 'right';
			var floatingButton = '<div onClick="cpnb_m_openModal()" class="cpnb-m-cookies-floatButton cpnb-m-cookies-floatButtonPosition_'+modalManager.w357_m_floatButtonPosition+'"><div class="cpnb-m-cookies-floatButton-icon" data-balloon="'+modalManager.w357_m_floatButtonText+'" data-balloon-pos="'+tooltipPosition+'"><img alt="Cookies manager" class="cpnb-m-cookies-floatButton-icon-img" src="'+modalManager.w357_m_floatButtonIconSrc+'"/></div></div>';
			var elem = document.createElement('div');
			elem.innerHTML = floatingButton;
			document.body.appendChild(elem);
		}

		var el_w357_m_HashLink = document.getElementById(modalManager.w357_m_HashLink);
		if(el_w357_m_HashLink)
		{
			var click_to_open_modal = el_w357_m_HashLink.addEventListener("click", cpnb_m_openModal, false);
		}
		else
		{
			var click_to_open_modal = null;
		}

		// Check Hash Link to open modal
		if (window.location.hash == '#'+modalManager.w357_m_HashLink || click_to_open_modal) {
			var e = document.getElementById("cpnb_manager_wrap");
			e.classList.remove("cpnb-modal-show-fade-out");
			e.classList.add("cpnb-modal-show-fade-in");

			var ec = document.getElementById("cpnb_manager_wrap_close_bg");
			ec.classList.remove("cpnb-modal-show-fade-out");
			ec.classList.add("cpnb-modal-show-fade-in");
		}

    } //End - Check if ModalManager is enabled

////***************** END MANAGER *****************////

}, false); // END: WINDOW.ONLOAD

/////******* START MANAGER *******/////

//DISABLED CHECKBOX - cpnb_m_lockedCheckbox
function cpnb_m_lockedCheckbox(id, exp, confirmationAlert) {
    document.getElementById("modalCheckBox_id_"+id).checked = true;
    //if (confirmationAlert == 1) {
        alert(m_modalManager_confirmationText);
    //}
}

// Save Settings Button
function cpnb_warning_save_settings_button(link, exp, baseUrl, acceptCookieName, acceptCookieExpiration, declineCookieExpiration)
{
    cpnb_createCookie("cpnb_cookiesSettings", JSON.stringify(afterCategoriesSettingsCookie), afterExpirationCookies);
	// Use the temp settings until the "cpnb_warning_save_settings_button" function is called
	if (cpnb_readCookie("cpnb_cookiesSettingsTemp") == null) {
		cpnb_createCookie("cpnb_cookiesSettingsTemp", cpnb_readCookie("cpnb_cookiesSettings"), exp);
	}

	cpnb_createCookie("cpnb_cookiesSettings", cpnb_readCookie("cpnb_cookiesSettingsTemp"), exp);
	var funCookieSettings = JSON.parse(cpnb_readCookie("cpnb_cookiesSettings"));

	//CHECK+CHANGE CookieName & cpnb_cookieDeclined = 1
	var countAllCategoriesCookies = 0;
	var countDisabledCategoriesCookies = 0;
	var countLockedCategoriesCookies = 0;
	for (var item in funCookieSettings) {
		countAllCategoriesCookies++;

        if (cpnb_cookiesCategories[categoriesSettingsCookieInner[item]]) 
        {
            if (cpnb_cookiesCategories[categoriesSettingsCookieInner[item]].cookie_category_checked_by_default == 2) 
            {
                countLockedCategoriesCookies++;
            }

            if (funCookieSettings[item] == 0) 
            {
                countDisabledCategoriesCookies++;
            }
        }
	}

	//If remaining cookies are all disabled, delete cookieName and create cookieDeclined=1
	if( countAllCategoriesCookies-countLockedCategoriesCookies == countDisabledCategoriesCookies ) {
		if (cpnb_readCookie("cpnbCookiesDeclined") == null) {
			cpnb_createCookie("cpnbCookiesDeclined", "1", declineCookieExpiration);
			cpnb_eraseCookie(acceptCookieName);
			cpnb_eraseCookie("cpnbCookiesCancelled");
			location.href = baseUrl + (baseUrl.match(/\?/) ? '&' : '?') + 'cpnb_method=cpnbCookiesDeclined';
		}
	} else {
		if (cpnb_readCookie(acceptCookieName) == null) {
			cpnb_createCookie(acceptCookieName, "1", acceptCookieExpiration);
			cpnb_eraseCookie("cpnbCookiesDeclined");
			cpnb_eraseCookie("cpnbCookiesCancelled");
			location.href = baseUrl + (baseUrl.match(/\?/) ? '&' : '?') + 'cpnb_method=cpnbCookiesAccepted';
		}
	}

	cpnb_eraseCookie("cpnb_cookiesSettingsTemp");

	location.href= link;
}

function cpnb_m_saveCategoryCookiesState(id, exp, baseUrl, acceptCookieName, acceptCookieExpiration, declineCookieExpiration) 
{
    cpnb_createCookie("cpnb_cookiesSettings", JSON.stringify(afterCategoriesSettingsCookie), afterExpirationCookies);
    if (cpnb_cookiesCategories[id].cookie_category_checked_by_default != 2) {

		// Use the temp settings until the "cpnb_warning_save_settings_button" function is called
		if (cpnb_readCookie("cpnb_cookiesSettingsTemp") == null) {
			cpnb_createCookie("cpnb_cookiesSettingsTemp", cpnb_readCookie("cpnb_cookiesSettings"), exp);
		}
		var funCookieSettings = JSON.parse(cpnb_readCookie("cpnb_cookiesSettingsTemp"));

        if (document.getElementById("modalCheckBox_id_"+id).checked === true) {
            funCookieSettings[categoriesSettingsCookieOuter[id]] = 1;
        } else {
            funCookieSettings[categoriesSettingsCookieOuter[id]] = 0;
        }

        document.getElementById("cpnb-save-btn-m").style.background = modalManagerSettings.w357_m_saveChangesButtonColorAfterChange;
		
		cpnb_createCookie("cpnb_cookiesSettingsTemp", JSON.stringify(funCookieSettings), exp);

    }

}

function cpnb_m_changeModalCategory(id) {
    cpnb_createCookie("cpnb_cookiesSettings", JSON.stringify(afterCategoriesSettingsCookie), afterExpirationCookies);
    //CHANGE LEFT MENU ITEM
    var x = document.getElementsByClassName("cpnb-manager-modal-left-item-selected");
        x[0].classList.add("cpnb-manager-modal-left-item-unSelected");
        x[0].classList.remove("cpnb-manager-modal-left-item-selected");
    var x = document.getElementById("mlm_menu_category_id_"+id);
        x.classList.remove("cpnb-manager-modal-left-item-unSelected");
        x.classList.add("cpnb-manager-modal-left-item-selected");
    //CHANGE RIGHT INFORMATION BOX
    var x = document.getElementsByClassName("cpnb-manager-modal-right-selected");
        x[0].classList.add("cpnb-manager-modal-right-unSelected");
        x[0].classList.remove("cpnb-manager-modal-right-selected");
    var x = document.getElementById("mmr_box_category_id_"+id);
        x.classList.remove("cpnb-manager-modal-right-unSelected");
        x.classList.add("cpnb-manager-modal-right-selected");

		//Responsive Hide SelectCategoryMenu on ChangeCategory
		cpnb_toggle_responsive_menu_hide_menu();
}

function cpnb_m_openModal() {
  var e = document.getElementById("cpnb_manager_wrap");
	e.classList.remove("cpnb-modal-show-fade-out");
	e.classList.add("cpnb-modal-show-fade-in");

	var ec = document.getElementById("cpnb_manager_wrap_close_bg");
	ec.classList.remove("cpnb-modal-show-fade-out");
	ec.classList.add("cpnb-modal-show-fade-in");
}

function cpnb_m_closeModal() {
	var e = document.getElementById("cpnb_manager_wrap");
	e.classList.remove("cpnb-modal-show-fade-in");
	e.classList.add("cpnb-modal-show-fade-out");

	var ec = document.getElementById("cpnb_manager_wrap_close_bg");
	ec.classList.remove("cpnb-modal-show-fade-in");
	ec.classList.add("cpnb-modal-show-fade-out");
}
/////******* END MANAGER *******/////

// OK - Accept Button
function acceptFunction(confirmationAlert, confirmMsg, AcceptCookieName, link, expiration, base_url, type, reloadPageAfterAccept, overlayState)
{
    cpnb_createCookie("cpnb_cookiesSettings", JSON.stringify(afterCategoriesSettingsCookie), afterExpirationCookies);
	cpnb_createCookie(AcceptCookieName, "1", expiration);
	cpnb_eraseCookie("cpnbCookiesCancelled");
	cpnb_eraseCookie("cpnbCookiesDeclined");

	// Modal Manager
	var funCookieSettings = JSON.parse(cpnb_readCookie("cpnb_cookiesSettings"));
	for (var item in funCookieSettings) {
		funCookieSettings[item] = 1;
	}
	cpnb_createCookie("cpnb_cookiesSettings", JSON.stringify(funCookieSettings), afterExpirationCookies);

	if (type == 'notification_bar')
	{
		// hide notification bar
		if (reloadPageAfterAccept == 1)
		{
			location.reload();
			location.href= link;
		}
		else
		{
			// Store decision to DB
			var xmlhttp = new XMLHttpRequest();
			xmlhttp.onreadystatechange = function() {
				if (xmlhttp.readyState == XMLHttpRequest.DONE) {   // XMLHttpRequest.DONE == 4
					if (xmlhttp.status == 200) {
						// all done
					}
					else if (xmlhttp.status == 400) {
						// There was an error 400
					}
					else {
						// something else other than 200 was returned
					}
				}
			};
			var url = base_url+"index.php?option=com_ajax&plugin=cookiespolicynotificationbar&format=raw&method=cpnbCookiesAccepted";
			xmlhttp.open("GET", url, true);
			xmlhttp.send();

			// hide the notification bar
			document.getElementById("w357_cpnb_outer").classList.add("cpnb-warningBox-show-fade-out");
			if (overlayState == 1) 
			{
				document.getElementById("cpnb_warningBoxBgOverlay").style.display = "none";
			}
		}
	}
	else // shortcode_table or modal
	{
		location.reload();
		location.href= link;
	}
}

function checkCookieCategoriesAfterAccept(expiration)
{
	// after accept check all checkboxes asynchronously.
	cpnb_checkboxes = document.getElementsByClassName('cpnb-checkbox');
	for(var i=0, n=cpnb_checkboxes.length;i<n;i++) {
		cpnb_checkboxes[i].checked = true;
	}

	for (var categoryID in cpnb_cookiesCategories) 
	{
		if (cpnb_cookiesCategories[categoryID].cookie_category_status == 1) 
		{
			var innerCookieCategory = cpnb_cookiesCategories[categoryID].cookie_category_id;
			categoriesSettingsCookie[innerCookieCategory] = 1;
		}
	}

	// Get the total of enabled categories and if is more than one then create the cpnb_cookiesSettings cookie, or if is only the required-cookies category do not create the cpnb_cookiesSettings cookie to pass the Cookiebot validation service
	var categoriesSettingsCookie_arr_values = Object.keys(categoriesSettingsCookie).map(function(itm) { return categoriesSettingsCookie[itm]; }); // IE compatibility

	var categoriesSettingsCookie_count_of_enabled = categoriesSettingsCookie_arr_values.reduce(function(a, b){
		return a + b;
	}, 0);

	if (categoriesSettingsCookie_count_of_enabled > 1)
	{
		cpnb_createCookie("cpnb_cookiesSettings", JSON.stringify(categoriesSettingsCookie), expiration);
	}
}

function cpnb_warning_accept_button(confirmationAlert, confirmMsg, AcceptCookieName, link, expiration, base_url, type, reloadPageAfterAccept, overlayState)
{
	if (confirmationAlert == 1)
	{
		if (confirm(confirmMsg))
		{
			checkCookieCategoriesAfterAccept(expiration);
			acceptFunction(confirmationAlert, confirmMsg, AcceptCookieName, link, expiration, base_url, type, reloadPageAfterAccept, overlayState);
		}
	}
	else
	{
		checkCookieCategoriesAfterAccept(expiration);
		acceptFunction(confirmationAlert, confirmMsg, AcceptCookieName, link, expiration, base_url, type, reloadPageAfterAccept, overlayState);
	}

	setTimeout(function(){
		document.getElementById("w357_cpnb_outer").remove();
	}, 500);
}

// Decline Button
function cpnb_warning_decline_button(confirmationAlert, confirmMsg, cookieName, link, expiration)
{
    cpnb_createCookie("cpnb_cookiesSettings", JSON.stringify(afterCategoriesSettingsCookie), afterExpirationCookies);
	if (confirmationAlert == 1)
	{
		if (confirm(confirmMsg))
		{
			cpnb_createCookie("cpnbCookiesDeclined", "1", expiration);
			cpnb_eraseCookie(cookieName);
			cpnb_eraseCookie("cpnbCookiesCancelled");

				// Modal Manager
			    var funCookieSettings = JSON.parse(cpnb_readCookie("cpnb_cookiesSettings"));
			    for (var item in funCookieSettings) {
			        funCookieSettings[item] = 0;
			    }
			    cpnb_createCookie("cpnb_cookiesSettings", JSON.stringify(funCookieSettings), afterExpirationCookies);
			    location.reload();

			location.href= link;
		}
	}
	else
	{
		cpnb_createCookie("cpnbCookiesDeclined", "1", expiration);
		cpnb_eraseCookie(cookieName);
		cpnb_eraseCookie("cpnbCookiesCancelled");

			// Modal Manager
		    var funCookieSettings = JSON.parse(cpnb_readCookie("cpnb_cookiesSettings"));
		    for (var item in funCookieSettings) {
		        funCookieSettings[item] = 0;
		    }
		    cpnb_createCookie("cpnb_cookiesSettings", JSON.stringify(funCookieSettings), afterExpirationCookies);
		    location.reload();

		location.href= link;
	}
}

// Delete Button
function cpnb_warning_delete_button(confirmationAlert, confirmMsg, cookieName, link)
{
    cpnb_createCookie("cpnb_cookiesSettings", JSON.stringify(afterCategoriesSettingsCookie), afterExpirationCookies);
	if (confirmationAlert == 1)
	{
		if (confirm(confirmMsg))
		{
			cpnb_eraseCookie("cpnb_cookiesSettings");
			cpnb_eraseCookie("cpnbCookiesDeclined");
			cpnb_eraseCookie(cookieName);
			cpnb_eraseCookie("cpnbCookiesCancelled");
			location.href= link;
		}
	}
	else
	{
		cpnb_eraseCookie("cpnb_cookiesSettings");
		cpnb_eraseCookie("cpnbCookiesDeclined");
		cpnb_eraseCookie(cookieName);
		cpnb_eraseCookie("cpnbCookiesCancelled");
		location.href= link;
	}
}

// Cancel Button
function cpnb_warning_cancel_button(cookieName, expiration, link, overlayState)
{
    cpnb_createCookie("cpnb_cookiesSettings", JSON.stringify(afterCategoriesSettingsCookie), afterExpirationCookies);
	cpnb_createCookie("cpnbCookiesCancelled", "1", expiration);
	cpnb_eraseCookie(cookieName);
	cpnb_eraseCookie("cpnbCookiesDeclined");

	// hide the notification bar
	document.getElementById("w357_cpnb_outer").classList.add("cpnb-warningBox-show-fade-out");
	if (overlayState == 1) 
	{
		document.getElementById("cpnb_warningBoxBgOverlay").style.display = "none";
	}

	setTimeout(function(){
		document.getElementById("w357_cpnb_outer").remove();
	}, 500);

	cpnb_closeModalMoreInfo();
}

// Open POP UP
function w357_openPopUpWindowMoreInfo(url, title, w, h)
{
	wLeft = window.screenLeft ? window.screenLeft : window.screenX;
	wTop = window.screenTop ? window.screenTop : window.screenY;

	var left = wLeft + (window.innerWidth / 2) - (w / 2);
	var top = wTop + (window.innerHeight / 2) - (h / 2);
	window.open(url, title, 'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=no, resizable=no, copyhistory=no, width=' + w + ', height=' + h + ', top=' + top + ', left=' + left);
}

// Open More Info Modal
function cpnb_openModalMoreInfo()
{
	var e = document.getElementById("cpnb_modal_wrap");
	e.classList.remove("cpnb-modal-show-fade-out");
	e.classList.add("cpnb-modal-show-fade-in");

	var ec = document.getElementById("modal_close_bg");
	ec.classList.remove("cpnb-modal-show-fade-out");
	ec.classList.add("cpnb-modal-show-fade-in");
}

function cpnb_closeModalMoreInfo()
{
	var e = document.getElementById("cpnb_modal_wrap");
	e.classList.remove("cpnb-modal-show-fade-in");
	e.classList.add("cpnb-modal-show-fade-out");

	var ec = document.getElementById("modal_close_bg");
	ec.classList.remove("cpnb-modal-show-fade-in");
	ec.classList.add("cpnb-modal-show-fade-out");
}

function cpnb_createCookie(name, value, days)
{
	var expires;
	if (days)
	{
		var date = new Date();
		date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
		expires = "; expires=" + date.toGMTString();
	}
	else
	{
		expires = "";
	}
	document.cookie = encodeURIComponent(name) + "=" + encodeURIComponent(value) + expires + "; path=/";
}

function cpnb_readCookie(name)
{
	var nameEQ = encodeURIComponent(name) + "=";
	var ca = document.cookie.split(';');
	for (var i = 0; i < ca.length; i++)
	{
		var c = ca[i];
		while (c.charAt(0) === ' ')
			c = c.substring(1, c.length);
		if (c.indexOf(nameEQ) === 0)
			return decodeURIComponent(c.substring(nameEQ.length, c.length));
	}
	return null;
}

function cpnb_eraseCookie(name)
{
	cpnb_createCookie(name, "", -1);
}

// Responsive left menu
function cpnb_toggle_responsive_menu() 
{
	if (document.getElementsByClassName('cpnb-manager-modal-left')[0].style.display == 'block')
	{
		document.getElementsByClassName('cpnb-manager-modal-left')[0].style.display = 'none';
	} 
	else 
	{
		document.getElementsByClassName('cpnb-manager-modal-left')[0].style.display = 'block';
	}
}

function cpnb_toggle_responsive_menu_hide_menu() 
{
	if (window.innerWidth < 601) 
	{
		document.getElementsByClassName('cpnb-manager-modal-left')[0].style.display = 'none';
	}
}

/**
 * TRIGGER EVENTS
 */
window.addEventListener('load', function ()
{
	var cpnb_accept_btn = document.getElementById("cpnb-accept-btn");
	if(cpnb_accept_btn)
	{
		cpnb_accept_btn.addEventListener("click", function(){
			// alert("You clicked " + cpnb_accept_btn.id); 
		});
	}

	var cpnb_decline_btn = document.getElementById("cpnb-decline-btn");
	if(cpnb_decline_btn)
	{
		cpnb_decline_btn.addEventListener("click", function(){
			// alert("You clicked " + cpnb_decline_btn.id); 
		});
	}

	var cpnb_cancel_btn = document.getElementById("cpnb-cancel-btn");
	if(cpnb_cancel_btn)
	{
		cpnb_cancel_btn.addEventListener("click", function(){
			// alert("You clicked " + cpnb_cancel_btn.id); 
		});
	}

	var cpnb_accept_btn_m_info = document.getElementById("cpnb-accept-btn-m-info");
	if(cpnb_accept_btn_m_info)
	{
		cpnb_accept_btn_m_info.addEventListener("click", function(){
			// alert("You clicked " + cpnb_accept_btn_m_info.id); 
		});
	}

	var cpnb_decline_btn_m_info = document.getElementById("cpnb-decline-btn-m-info");
	if(cpnb_decline_btn_m_info)
	{
		cpnb_decline_btn_m_info.addEventListener("click", function(){
			// alert("You clicked " + cpnb_decline_btn_m_info.id); 
		});
	}

	var cpnb_cancel_btn_m_info = document.getElementById("cpnb-cancel-btn-m-info");
	if(cpnb_cancel_btn_m_info)
	{
		cpnb_cancel_btn_m_info.addEventListener("click", function(){
			// alert("You clicked " + cpnb_cancel_btn_m_info.id); 
		});
	}

	var cpnb_accept_btn_cit = document.getElementById("cpnb-accept-btn-cit");
	if(cpnb_accept_btn_cit)
	{
		cpnb_accept_btn_cit.addEventListener("click", function(){
			// alert("You clicked " + cpnb_accept_btn_cit.id); 
		});
	}

	var cpnb_delete_btn_cit = document.getElementById("cpnb-delete-btn-cit");
	if(cpnb_delete_btn_cit)
	{
		cpnb_delete_btn_cit.addEventListener("click", function(){
			// alert("You clicked " + cpnb_delete_btn_cit.id); 
		});
	}

	var cpnb_reload_btn_cit = document.getElementById("cpnb-reload-btn-cit");
	if(cpnb_reload_btn_cit)
	{
		cpnb_reload_btn_cit.addEventListener("click", function(){
			// alert("You clicked " + cpnb_reload_btn_cit.id); 
		});
	}

	var cpnb_accept_btn_m = document.getElementById("cpnb-accept-btn-m");
	if(cpnb_accept_btn_m)
	{
		cpnb_accept_btn_m.addEventListener("click", function(){
			// alert("You clicked " + cpnb_accept_btn_m.id); 
		});
	}

	var cpnb_decline_btn_m = document.getElementById("cpnb-decline-btn-m");
	if(cpnb_decline_btn_m)
	{
		cpnb_decline_btn_m.addEventListener("click", function(){
			// alert("You clicked " + cpnb_decline_btn_m.id); 
		});
	}

	var cpnb_save_btn_m = document.getElementById("cpnb-save-btn-m");
	if(cpnb_save_btn_m)
	{
		cpnb_save_btn_m.addEventListener("click", function(){
			// alert("You clicked " + cpnb_save_btn_m.id); 
		});
	}

}, false);