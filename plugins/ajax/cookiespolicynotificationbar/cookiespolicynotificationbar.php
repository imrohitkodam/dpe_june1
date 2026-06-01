<?php
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
# Last modified: 26 Nov 2020, 01:41:46
========================================================= */

defined('_JEXEC') or die;

// Import library dependencies
jimport('joomla.plugin.plugin');

class plgAjaxCookiesPolicyNotificationBar extends JPlugin
{
    function onAjaxCookiespolicynotificationbar()
    {
		$app = JFactory::getApplication();
		$jcookies = $app->input->cookie->getArray(); // $_COOKIE

		// Define vars
		$html = '';
		$cookies_num = 0;

		// Load the plugin language file
		$lang = JFactory::getLanguage();
		$current_lang_tag = $lang->getTag();
		$lang = JFactory::getLanguage();
		$extension = 'plg_system_cookiespolicynotificationbar';
		$base_dir = JPATH_SITE.'/plugins/system/cookiespolicynotificationbar/';
		$language_tag = (!empty($current_lang_tag)) ? $current_lang_tag : 'en-GB';
		$reload = true;
		$lang->load($extension, $base_dir, $language_tag, $reload);

		// Vars
		$jinput = $app->input;
		$method = $jinput->get('method', 'displayCookiesTable', 'STRING');
		$get_cookie_name = $jinput->get('cookie_name', '', 'STRING');

		// Plugin params
		$db = JFactory::getDBO();	
		$db->setQuery("SELECT params FROM #__extensions WHERE element = 'cookiespolicynotificationbar' AND folder = 'system'");
		$plugin = $db->loadObject();
		$params = new JRegistry();
		$params->loadString($plugin->params);
		$prm_cookie_name = $params->get('cookie_name', 'cookiesDirective');
		$store_acceptance_logs_into_db = $params->get('store_acceptance_logs_into_db', '1');
		$shortcode_tag = $params->get('shortcode_tag', 'cookiesinfo');
		$allowSessionCookies = $params->get('allowSessionCookies', '1');
		$hide_cookies_from_table = $params->get('hide_cookies_from_table', '');
	
		// Hide Cookies from Table
		if (!empty($hide_cookies_from_table))
		{
			$hide_cookies_from_table_arr = preg_replace('#\s+#', '', trim($hide_cookies_from_table));
			$hide_cookies_from_table_arr = explode(',', $hide_cookies_from_table_arr);
		}
		else
		{
			$hide_cookies_from_table_arr = array();
		}

		// Check if the user declined
		$cookiesDeclined = $app->input->cookie->get('cookiesDeclined', '', 'INT');

		if ($method == 'displayCookiesTable')
		{
			// get lang tag for the variable
			$lang_tag = str_replace("-", "_", $language_tag);
			$lang_tag = !empty($lang_tag) ? $lang_tag : "en_GB";

			// Control Shortcode's content
			$shortcode_text_before_accept_or_decline = html_entity_decode($params->get('shortcode_text_before_accept_or_decline_'.$lang_tag, JText::_('PLG_SYSTEM_CPNB_TEXT_BEFORE_ACCEPT_DECLINE_DEFAULT')));
			$shortcode_text_after_accept = html_entity_decode($params->get('shortcode_text_after_accept_'.$lang_tag, JText::_('PLG_SYSTEM_CPNB_TEXT_AFTER_ACCEPT_DEFAULT')));
			$shortcode_text_after_decline = html_entity_decode($params->get('shortcode_text_after_decline_'.$lang_tag, JText::_('PLG_SYSTEM_CPNB_TEXT_AFTER_DECLINE_DEFAULT')));
			
			// Get HTML Code for each situation
			$buttons_before_accept_or_decline = $this->showButtons($params, $lang_tag, 'before_accept_or_decline'); 
			$info_table_before_accept_or_decline = $this->showCookiesInfoTable($params, $lang_tag, 'before_accept_or_decline', $hide_cookies_from_table_arr);
			$buttons_after_accept = $this->showButtons($params, $lang_tag, 'after_accept');
			$info_table_after_accept = $this->showCookiesInfoTable($params, $lang_tag, 'after_accept', $hide_cookies_from_table_arr);
			$buttons_after_decline = $this->showButtons($params, $lang_tag, 'after_decline');
			$info_table_after_decline = $this->showCookiesInfoTable($params, $lang_tag, 'after_decline', $hide_cookies_from_table_arr);

			// BEFORE ACCEPT OR DECLINE
			preg_match('/{cpnb_buttons}/i', $shortcode_text_before_accept_or_decline, $matches_btns_a);
			if ($matches_btns_a)
			{
				$shortcode_text_before_accept_or_decline = str_replace($matches_btns_a[0], $buttons_before_accept_or_decline, $shortcode_text_before_accept_or_decline);
			}
			preg_match('/{cpnb_cookies_info_table}/i', $shortcode_text_before_accept_or_decline, $matches_tbl_a);
			if ($matches_tbl_a)
			{
				$shortcode_text_before_accept_or_decline = str_replace($matches_tbl_a[0], $info_table_before_accept_or_decline, $shortcode_text_before_accept_or_decline);
			}
			
			// AFTER ACCEPT
			preg_match('/{cpnb_buttons}/i', $shortcode_text_after_accept, $matches_btns_b);
			if ($matches_btns_b)
			{
				$shortcode_text_after_accept = str_replace($matches_btns_b[0], $buttons_after_accept, $shortcode_text_after_accept);
			}
			preg_match('/{cpnb_cookies_info_table}/i', $shortcode_text_after_accept, $matches_tbl_b);
			if ($matches_tbl_b)
			{
				$shortcode_text_after_accept = str_replace($matches_tbl_b[0], $info_table_after_accept, $shortcode_text_after_accept);
			}

			// AFTER DECLINE
			preg_match('/{cpnb_buttons}/i', $shortcode_text_after_decline, $matches_btns_c);
			if ($matches_btns_c)
			{
				$shortcode_text_after_decline = str_replace($matches_btns_c[0], $buttons_after_decline, $shortcode_text_after_decline);
			}
			preg_match('/{cpnb_cookies_info_table}/i', $shortcode_text_after_decline, $matches_tbl_c);
			if ($matches_tbl_c)
			{
				$shortcode_text_after_decline = str_replace($matches_tbl_c[0], $info_table_after_decline, $shortcode_text_after_decline);
			}

			foreach ($jcookies as $cookie_name=>$cookie_value)
			{
				if (isset($jcookies[$cookie_name]))
				{
					// Display only Persistent cookies and avoid Session Cookies
					// Also do not display the cookiesDeclined cookie in the list
					if (!$this->isSessionCookie($cookie_value) && !in_array($cookie_name, $hide_cookies_from_table_arr))
					{
						$cookies_num++;
					}
				}
			}

			if ($cookies_num == 0 && !$cookiesDeclined)
			{
				$html = $shortcode_text_before_accept_or_decline;
			}
			elseif ($cookies_num > 0 && !$cookiesDeclined)
			{
				$html = $shortcode_text_after_accept;
			}
			elseif ($cookies_num > 0 && $cookiesDeclined)
			{
				$html = $shortcode_text_after_decline;
			}

			// clean the cache first
			$this->cleanTheCache();
		}
		
		// DECLINE COOKIES
		if ($method == 'declineCookies')
		{
			// clean the cache first
			$this->cleanTheCache();

			if (isset($jcookies))
			{
				// block joomla session cookies
				if (!$allowSessionCookies)
				{
					header_remove('Set-Cookie');
				}

				// Delete all cookies
				foreach ($jcookies as $key=>$val)
				{
					if (isset($jcookies[$key]))
					{
						// Delete only the Persistent cookies and avoid Session Cookies (Avoid logged out of Joomla Administrator)
						if (!$allowSessionCookies || !$this->isSessionCookie($val))
						{
							setcookie($key, '', time() - 1000, '/', $this->getFullDomain()); // example: test.google.com
							setcookie($key, '', time() - 1000, '/', $this->getDomainOnly()); // example: google.com
							$app->input->cookie->set($key, '', time() - 1000, $app->get('cookie_path', '/'), $app->get('cookie_domain'), $app->isSSLConnection());
						}
					}
				}

				// Store the visitor decision into the database.
				if ($store_acceptance_logs_into_db)
				{
					$this->storeDecision(JText::_('PLG_SYSTEM_CPNB_DECLINED_DB_VALUE'), $jcookies['cpnb_cookiesSettings']);
				}

				$return = array();
				$return["offline"] = $app->getCfg('offline');
				$return["json"] = json_encode($return);
				echo json_encode($return);
			}

			// set cookie to remember tha the cookies Declined
			$expires = time() + 60*60*24*30; // 30 days
			$app->input->cookie->set('cookiesDeclined', '1', $expires, $app->get('cookie_path', '/'), $app->get('cookie_domain'), $app->isSSLConnection());
			$cookiesDeclined = $app->input->cookie->get('cookiesDeclined', '', 'INT');
		}

		// DELETE COOKIES
		if ($method == 'deleteCookies')
		{
			// clean the cache first
			$this->cleanTheCache();

			if (isset($jcookies))
			{
				// block joomla session cookies
				if (!$allowSessionCookies)
				{
					header_remove('Set-Cookie');
				}

				// Delete all cookies
				foreach ($jcookies as $key=>$val)
				{
					if (isset($jcookies[$key]))
					{
						// Delete only the Persistent cookies and avoid Session Cookies (Avoid logged out of Joomla Administrator)
						if (!$allowSessionCookies || !$this->isSessionCookie($val))
						{
							setcookie($key, '', time() - 1000, '/', $this->getFullDomain()); // example: test.google.com
							setcookie($key, '', time() - 1000, '/', $this->getDomainOnly()); // example: google.com
							$app->input->cookie->set($key, '', time() - 1000, $app->get('cookie_path', '/'), $app->get('cookie_domain'), $app->isSSLConnection());
						}
					}
				}

				// Store the visitor decision into the database.
				if ($store_acceptance_logs_into_db)
				{
					$this->storeDecision(JText::_('PLG_SYSTEM_CPNB_DECLINED_DB_VALUE'), $jcookies['cpnb_cookiesSettings']);
				}

				$return = array();
				$return["offline"] = $app->getCfg('offline');
				$return["json"] = json_encode($return);
				echo json_encode($return);
			}
		}

		// ALLOW COOKIES
		if ($method == 'allowCookies' && $get_cookie_name == $prm_cookie_name)
		{
			// clean the cache first
			$this->cleanTheCache();
			
			// allow cookies
			$cookie_expiration = time() + (86400 * 30); // 30 days
			$app->input->cookie->set($prm_cookie_name, 1, $cookie_expiration, $app->get('cookie_path', '/'), $app->get('cookie_domain'), $app->isSSLConnection());

			// delete the "cookiesDeclined" cookie bececause the user allows now.
			setcookie('cookiesDeclined', '', time() - 1000, '/', $this->getFullDomain()); // example: test.google.com
			setcookie('cookiesDeclined', '', time() - 1000, '/', $this->getDomainOnly()); // example: google.com
			$app->input->cookie->set('cookiesDeclined', '', time() - 1000, $app->get('cookie_path', '/'), $app->get('cookie_domain'), $app->isSSLConnection());
			$cookiesDeclined = $app->input->cookie->get('cookiesDeclined', '', 'INT');
			
			// Store the visitor decision into the database.
			if ($store_acceptance_logs_into_db)
			{
				$this->storeDecision(JText::_('PLG_SYSTEM_CPNB_ACCEPTED_DB_VALUE'), $jcookies['cpnb_cookiesSettings']);
			}
		}
		
		// Show the acceptance logs in a nice modal at backend
		if ($method == 'displayAcceptanceLogs')
		{
			return $this->getAcceptanceLogs();
		}

		// Method to delete the acceptance logs at backend
		if ($method == 'deleteAcceptanceLogs')
		{
			return $this->deleteAcceptanceLogs();
		}

		// Method to restore the default plugin settings
		if ($method == 'restoreToDefaults')
		{
			return $this->restoreToDefaults();
		}

		// COOKIES ACCEPTED
		if ($method == 'cpnbCookiesAccepted')
		{
			// Store the visitor decision into the database.
			if ($store_acceptance_logs_into_db)
			{
				$this->storeDecision(JText::_('PLG_SYSTEM_CPNB_ACCEPTED_DB_VALUE'), $jcookies['cpnb_cookiesSettings']);
			}
		}
		
		return $html;
    }

	/**
	 * Get the descriptions of cookies
	 */
	public function getCookieDescription($cookie_name = '', $params)
	{
		$cookie_description = '';
		if (!empty($cookie_name))
		{
			$cookie_descriptions_group = $params->get('cookie_descriptions_group', '');

			if (!empty($cookie_descriptions_group) && is_object($cookie_descriptions_group))
			{
				foreach($cookie_descriptions_group as $group=>$cookie_obj)
				{
					if ($cookie_obj->cookie_status && $cookie_obj->cookie_name == $cookie_name)
					{
						$cookie_description = JText::_($cookie_obj->cookie_description);
					}
				}
			}
		}

		return $cookie_description;
	}

	/**
	 * Get the expiration of cookie
	 */
	public function getCookieExpiration($cookie_name = '', $params)
	{
		$cookie_expiration = '';
		if (!empty($cookie_name))
		{
			$cookie_descriptions_group = $params->get('cookie_descriptions_group', '');

			if (!empty($cookie_descriptions_group) && is_object($cookie_descriptions_group))
			{
				foreach($cookie_descriptions_group as $group=>$cookie_obj)
				{
					if ($cookie_obj->cookie_status && $cookie_obj->cookie_name == $cookie_name)
					{
						$cookie_expiration = JText::_($cookie_obj->cookie_expiration);
					}
				}
			}
		}

		return $cookie_expiration;
	}

	/**
	 * Display the Info Table
	 * 
	 * STATUS
	 * before_accept_or_decline
	 * after_accept
	 * after_decline
	 */
	private function showCookiesInfoTable($params, $lang_tag, $status = '', $hide_cookies_from_table_arr = array())
	{
		// Other texts for translations
		$no_cookies_here_txt = $params->get('no_cookies_here_txt_'.$lang_tag, JText::_('PLG_SYSTEM_CPNB_NO_COOKIES_HERE'));
		
		// define vars
		$html = '';
		$html_row = '';
		$cookies_num = 0;
		$app = JFactory::getApplication();
		$jcookies = $app->input->cookie->getArray(); // $_COOKIE

		foreach ($jcookies as $cookie_name=>$cookie_value)
		{
			if (isset($jcookies[$cookie_name]))
			{
				// Display only Persistent cookies and avoid Session Cookies
				// Also do not display the cookiesDeclined cookie in the list
				if (!$this->isSessionCookie($cookie_value) && !in_array($cookie_name, $hide_cookies_from_table_arr))
				{
					$cookie_description = $this->getCookieDescription($cookie_name, $params);
					$cookie_expiration = $this->getCookieExpiration($cookie_name, $params);

					$html_row .= '<tr>';
					$html_row .= '<td class="cpnb-cookie-name-col" data-label="'.JText::_('PLG_SYSTEM_CPNB_COOKIE_NAME').'">'.$cookie_name.'</td>';
					$html_row .= '<td class="cpnb-cookie-value-col" data-label="'.JText::_('PLG_SYSTEM_CPNB_COOKIE_VALUE').'">'.$cookie_value.'</td>';
					$html_row .= '<td class="cpnb-cookie-expiration-col" data-label="'.JText::_('PLG_SYSTEM_CPNB_COOKIE_EXPIRATION').'">'.(!empty($cookie_expiration) ? $cookie_expiration : JText::_('---')).'</td>';
					$html_row .= '<td class="cpnb-cookie-desc-col" data-label="'.JText::_('PLG_SYSTEM_CPNB_COOKIE_DESCRIPTION').'">'.(!empty($cookie_description) ? $cookie_description : JText::_('PLG_SYSTEM_CPNB_COOKIE_EMPTY_DESCRIPTION')).'</td>';

					$cookies_num++;
				}
			}
		}
			
		if ($cookies_num > 0)
		{
			// Cookies are enabled - Delete
			$html .= '<div class="cpnb-margin cpnb-cookies-table-container">';
			$html .= '<table width="100%" border="1" cellpadding="5" cellspacing="5" class="cpnb-cookies-table">';
			$html .= '<thead>';
			$html .= '<tr>';
			$html .= '<th class="cpnb-cookie-name-heading-col" scope="col">'.JText::_('PLG_SYSTEM_CPNB_COOKIE_NAME').'</th>';
			$html .= '<th class="cpnb-cookie-value-heading-col" scope="col">'.JText::_('PLG_SYSTEM_CPNB_COOKIE_VALUE').'</th>';
			$html .= '<th class="cpnb-cookie-expiration-heading-col" scope="col">'.JText::_('PLG_SYSTEM_CPNB_COOKIE_EXPIRATION').'</th>';
			$html .= '<th class="cpnb-cookie-desc-heading-col" scope="col">'.JText::_('PLG_SYSTEM_CPNB_COOKIE_DESCRIPTION').'</th>';
			$html .= '</tr>';
			$html .= '</thead>';
			$html .= '<tbody>';
			$html .= $html_row;
			$html .= '</tbody>';
			$html .= '</table>';
			$html .= '</div>';
		}
		else
		{
			$html .= '<div class="cpnb-margin cpnb-no-cookies-here">';
			$html .= JText::_($no_cookies_here_txt);
			$html .= '</div>';
		}

		return $html;
	}

	/**
	 * Display the buttons
	 * 
	 * STATUS
	 * before_accept_or_decline
	 * after_accept
	 * after_decline
	 */
	private function showButtons($params, $lang_tag, $status = '')
	{
		// Other texts for translations
		$allow_cookies_btn_text = $params->get('allow_cookies_btn_text_'.$lang_tag, JText::_('PLG_SYSTEM_CPNB_ALLOW_COOKIES'));
		$delete_cookies_btn_text = $params->get('delete_cookies_btn_text_'.$lang_tag, JText::_('PLG_SYSTEM_CPNB_DELETE_COOKIES'));
		$reload_cookies_btn_text = $params->get('reload_cookies_btn_text_'.$lang_tag, JText::_('PLG_SYSTEM_CPNB_RELOAD'));
		
		// allow btn
		$allow_btn = '<button class="cpnb-btn cpnb-allow-btn cpnb-margin-right">'.JText::_($allow_cookies_btn_text).'</button>';

		// delete btn
		$delete_btn = '<button class="cpnb-btn cpnb-delete-btn cpnb-margin-right">'.JText::_($delete_cookies_btn_text).'</button>';

		// reload btn
		$reload_btn = '<button class="cpnb-btn cpnb-reload-btn">'.JText::_($reload_cookies_btn_text).'</button>';

		// display
		switch ($status)
		{
			case "before_accept_or_decline":
				$html = $allow_btn;
				break;
			case "after_accept":
				$html = $delete_btn . ' ' . $reload_btn;
				break;
			case "after_decline":
				$html = $allow_btn . ' ' . $delete_btn . ' ' . $reload_btn;
				break;
			default:
				$html = $allow_btn . ' ' . $delete_btn . ' ' . $reload_btn;
		}

		return $html;
	}

	/**
	 * Clean the cache
	 */
	private function cleanTheCache()
	{
		// check first if the page cache ise enabled
		JFactory::getCache()->clean('com_content');
		JFactory::getCache()->clean('_system');
		JFactory::getCache()->clean('plg_jch_optimize');
		JFactory::getCache()->clean('page');
	}

	/**
	 * Get Acceptance logs
	 * 
	 * return HTML table
	 */
	private function getAcceptanceLogs()
	{
		$app = JFactory::getApplication();
		if ($app->isClient('administrator'))
		{	
			// data from db
			$db = JFactory::getDbo();
			$query = $db->getQuery(true);
			$query
				->select($db->quoteName('logs.id'))
				->select($db->quoteName('logs.ip_address'))
				->select($db->quoteName('logs.user_id'))
				->select($db->quoteName('logs.status'))
				->select($db->quoteName('logs.datetime'))
				->select($db->quoteName('logs.cookiesinfo'))
				->select($db->quoteName('users.name'))
				->select($db->quoteName('users.username'))
				->from($db->quoteName('#__plg_system_cookiespolicynotificationbar_logs', 'logs'))
				->join('LEFT', $db->quoteName('#__users', 'users') . ' ON ' . $db->quoteName('users.id') . ' = ' . $db->quoteName('logs.user_id'))
				->order('logs.datetime DESC');
			$db->setQuery($query);
			$log_results = $db->loadObjectList();

			// create the html table (css credits: http://johnsardine.com/freebies/dl-html-css/simple-little-tab/)
			$print_logs  = '';

			// reload btn
			$print_logs .= '<div class="text-left"><button class="btn btn-primary cpnb-reload-acceptance-logs-btn" type="button"><span class="icon-refresh"></span> Reload</button></div>';
			$print_logs .= '<div class="cpnb-loading-gif text-center" style="display:none;"></div>';

			// data table
			$print_logs .= '<table cellspacing="0" width="95%" class="w357_modal_table">';
			$print_logs .= '<thead>';
			$print_logs .= '<tr>';
			$print_logs .= '<th>'.JText::_('#').'</th>';
			$print_logs .= '<th>'.JText::_('PLG_SYSTEM_CPNB_IP_ADDRESS').'</th>';
			$print_logs .= '<th>'.JText::_('PLG_SYSTEM_CPNB_USER').'</th>';
			$print_logs .= '<th>'.JText::_('PLG_SYSTEM_CPNB_STATUS').'</th>';
			$print_logs .= '<th>'.JText::_('PLG_SYSTEM_CPNB_COOKIES_INFO_LBL').'</th>';
			$print_logs .= '<th>'.JText::_('PLG_SYSTEM_CPNB_DATETIME').'</th>';
			$print_logs .= '<th>'.JText::_('ID').'</th>';
			$print_logs .= '</tr>';
			$print_logs .= '</thead>';
			
			$print_logs .= '<tbody>';
			if (!empty($log_results)):
				$logs_info_message = '';
				$i=0;
				foreach ($log_results as $result):
					
					// cookies info in a list
					$cookiesinfo = json_decode($result->cookiesinfo, true);
					$accepted_cats = array();
					$declined_cats = array();
					unset($cookiesinfo['required-cookies']);
					if(!empty($cookiesinfo))
					{
						foreach ($cookiesinfo as $cookie_name => $cookie_value)
						{
							if ($cookie_value)
							{
								$accepted_cats[] = $cookie_name;
							}
							else
							{
								$declined_cats[] = $cookie_name;
							}
						}
					}
					
					if (!empty($accepted_cats) || !empty($declined_cats))
					{
						$cookies_info_html  = '<ul>';
						$cookies_info_html .= (!empty($accepted_cats)) ? '<li style="list-style:none;">Accepted: <i>'.implode(', ', $accepted_cats).'</i></li>' : '';
						$cookies_info_html .= (!empty($declined_cats)) ? '<li style="list-style:none;">Declined: <i>'.implode(', ', $declined_cats).'</i></li>' : '';
						$cookies_info_html .= '</ul>';
					}
					else
					{
						$cookies_info_html = '---';
					}

					// row
					$print_logs .= '<tr class="'.(($i % 2 == 0) ? "odd" : "even").'">';
					$print_logs .= '<td>'.(int) ($i+1).'</td>';
					$print_logs .= '<td>'.$result->ip_address.'</td>';
					$print_logs .= (!empty($result->user_id)) ? '<td>'.$result->user_id.'. '.$result->name.' ('.$result->username.')</td>' : '<td>'.JText::_('PLG_SYSTEM_CPNB_GUEST').'</td>';
					$print_logs .= '<td>'.$result->status.'</td>';
					$print_logs .= '<td>'.$cookies_info_html.'</td>';
					$print_logs .= '<td>'.JHtml::date($result->datetime, 'l d F Y, H:i').'</td>';
					$print_logs .= '<td>'.$result->id.'</td>';
					$print_logs .= '</tr>';
				$i++;
				endforeach;
			else:
				$logs_info_message = '<p class="logs_info_message">'.JText::_('PLG_SYSTEM_CPNB_NO_LOGS').'</p>';
			endif;;
			$print_logs .= '</tbody>';
			
			$print_logs .= '</table>';
			$print_logs .= $logs_info_message;

			return $print_logs;
		}
		else
		{
			JError::raiseError(403, '');
			return;
		}
	}

	/**
	 * Delete the Acceptance Logs from database
	 */
	private function deleteAcceptanceLogs()
	{
		$app = JFactory::getApplication();
		if ($app->isClient('administrator'))
		{			
			$db = JFactory::getDbo();
			$query = $db->getQuery(true);
			$query->delete($db->quoteName('#__plg_system_cookiespolicynotificationbar_logs'));
			$db->setQuery($query);
			$db->execute();

			$html = '';
			$html .= '<div class="cpnb-loading-gif text-center"></div>';
			$html .= '<div class="alert alert-success alert-dismissible cpnb-acceptance-logs-deleted-msg" style="display:none;"><a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a><span class="icon-save"></span> '.JText::_('PLG_SYSTEM_CPNB_LOGS_DELETED').'</div>';

			return $html;
		}
		else
		{
			JError::raiseError(403, '');
			return;
		}
	}

	/**
	 * Restore to Default settings
	 */
	private function restoreToDefaults()
	{
		$app = JFactory::getApplication();
		if ($app->isClient('administrator'))
		{			
			$db = JFactory::getDbo();
			$query = $db->getQuery(true);
			
			// Fields to update.
			$fields = array(
				$db->quoteName('params') . ' = ' . $db->quote('')
			);
			
			// Conditions for which records should be updated.
			$conditions = array(
				$db->quoteName('type') . ' = ' . $db->quote('plugin'),
				$db->quoteName('element') . ' = ' . $db->quote('cookiespolicynotificationbar'),
				$db->quoteName('folder') . ' = ' . $db->quote('system'),
			);
			
			$query->update($db->quoteName('#__extensions'))->set($fields)->where($conditions);
			$db->setQuery($query);
			$db->execute();

			$html = '';
			$html .= '<div class="cpnb-loading-gif text-center"></div>';
			$html .= '<div class="alert alert-success alert-dismissible cpnb-restore-to-defaults-msg" style="display:none;"><a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a><span class="icon-save"></span> '.JText::_('PLG_SYSTEM_CPNB_SETTINGS_RESTORED_SUCCESSFULLY').'</div>';
			
			return $html;
		}
		else
		{
			JError::raiseError(403, '');
			return;
		}
	}

	/**
	 * Get the full domain of a URL
	 * Example: for https://tests.google.com it prints tests.google.com
	 */
	public function getFullDomain()
	{
		$url = JURI::base();
		$parse_url = parse_url($url);
		return $parse_url['host'];
	}

	/**
	 * Get only the domain of a URL
	 * Example: for https://tests.google.com it prints google.com, even if its a subdomain.
	 */
	public function getDomainOnly()
	{
		$url = JURI::base();
		$pieces = parse_url($url);
		$domain = isset($pieces['host']) ? $pieces['host'] : $pieces['path'];
		if (preg_match('/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i', $domain, $regs)) 
		{
			return $regs['domain'];
		}
		return false;
	}

	/**
	 * What are the different types of cookies?
	 * A cookie can be classified by its lifespan and the domain to which it belongs. By lifespan, a cookie is either a: 
	 * - session cookie which is erased when the user closes the browser or
	 * - persistent cookie which remains on the user's computer/device for a pre-defined period of time.
	 * 
	 * return: true if is a session cookie.
	 */
	public static function isSessionCookie($cookie_name)
	{
		if (!empty($cookie_name))
		{
			$db = JFactory::getDbo();
			$query = $db->getQuery(true);
			$query->select('COUNT('.$db->quoteName('session_id').')');
			$query->from($db->quoteName('#__session'));
			$query->where($db->quoteName('session_id'). ' = ' . $db->quote((string) $cookie_name));

			try
			{
				$db->setQuery($query);
				return (int) $db->loadResult();
			}
			catch (RuntimeException $e)
			{
				JError::raiseError(500, $e->getMessage());
				return false;
			}
		}
		else
		{
			return false;
		}
	}

	/**
	 * Store the visitor decision into the database.
	 */
	private function storeDecision($status = 'accepted', $cookiesinfo = '') // status: accepted or declined
	{
		// Get a db connection.
		$db = JFactory::getDbo();
		
		// Create a new query object.
		$query = $db->getQuery(true);
		
		// Insert columns
		$columns = array('ip_address', 'status', 'datetime', 'cookiesinfo');
		
		// Insert values
		$values = array(); 
		$values['ip_address'] = $db->quote($_SERVER['REMOTE_ADDR']);
		$values['state'] = $db->quote(JText::_($status));
		$values['datetime'] = $db->quote(JFactory::getDate()->toSql());
		$values['cookiesinfo'] = $db->quote($cookiesinfo);
		
		// Prepare the insert query.
		$query
			->insert($db->quoteName('#__plg_system_cookiespolicynotificationbar_logs'))
			->columns($db->quoteName($columns))
			->values(implode(',', $values));
		
		// Set the query using our newly populated query object and execute it.
		$db->setQuery($query);
		$db->execute();
	}
}