<?php
/**
* @package RSSeo!
* @copyright (C) 2016 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\Registry\Registry;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Factory;

require_once JPATH_ADMINISTRATOR.'/components/com_rsseo/helpers/version.php';

class rsseoHelper {
	
	// Get component configuration
	public static function getConfig($name = null, $default = null) {
		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		static $config;
		
		if (empty($config)) {
			$query->clear();
			$query->select($db->qn('params'));
			$query->from($db->qn('#__extensions'));
			$query->where($query->qn('type') . ' = ' . $db->q('component'));
			$query->where($query->qn('element') . ' = ' . $db->q('com_rsseo'));
			$db->setQuery($query);
			$params = $db->loadResult();
			
			$registry = new Registry;
			$registry->loadString($params);
			$config = $registry->toObject();
		}
		
		if ($name != null) {
			if (isset($config->$name)) { 
				return $config->$name;
			} else {
				if (!is_null($default))
					return $default;
				else
					return false;
			}
		}
		else return $config;
	}
	
	// Update configuration
	public static function updateConfig($name, $value) {
		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		
		$query->select($db->qn('extension_id'))->select($db->qn('params'))
			->from($db->qn('#__extensions'))
			->where($query->qn('type') . ' = ' . $db->q('component'))
			->where($query->qn('element') . ' = ' . $db->q('com_rsseo'));
		$db->setQuery($query);
		if ($extension = $db->loadObject()) {
			$registry = new Registry;
			$registry->loadString($extension->params);
			$registry->set($name, $value);
				
			$query->clear()
				->update($db->qn('#__extensions'))
				->set($db->qn('params'). ' = '.$db->q((string) $registry->toString()))
				->where($db->qn('extension_id'). ' = '. $db->q($extension->extension_id));
			
			$db->setQuery($query);
			$db->execute();
		}
	}
	
	// Get key code for update
	public static function genKeyCode() {
		$code = rsseoHelper::getConfig('global_register_code');
		$version = new RSSeoVersion();
		return md5($code.$version->key);
	}
	
	// Check for Joomla! version
	public static function isJ3() {
		return version_compare(JVERSION, '3.0', '>=');
	}
	
	// Check for Joomla! version
	public static function isJ4() {
		return version_compare(JVERSION, '4.0', '>=');
	}
	
	// Load jQuery
	public static function loadjQuery($noconflict = true) {
		$enabled = rsseoHelper::getConfig('load_jquery',1);
		
		if ($enabled) {
			HTMLHelper::_('jquery.framework', $noconflict);
		}
	}
	
	// Add backend submenus
	public static function addSubmenu($vName) {
		if (rsseoHelper::isJ4()) {
			return;
		}

		JHtmlSidebar::addEntry(Text::_('COM_RSSEO_MENU_DASHBOARD'),		'index.php?option=com_rsseo',						$vName == '' || $vName == 'default');
		JHtmlSidebar::addEntry(Text::_('COM_RSSEO_MENU_PAGES'),			'index.php?option=com_rsseo&view=pages',			$vName == 'pages');
		JHtmlSidebar::addEntry(Text::_('COM_RSSEO_MENU_CRAWLER'),			'index.php?option=com_rsseo&view=crawler',			$vName == 'crawler');
		JHtmlSidebar::addEntry(Text::_('COM_RSSEO_MENU_SITEMAP' ),		'index.php?option=com_rsseo&view=sitemap',			$vName == 'sitemap');
		JHtmlSidebar::addEntry(Text::_('COM_RSSEO_MENU_ROBOTS' ),			'index.php?option=com_rsseo&view=robots',			$vName == 'robots');
		JHtmlSidebar::addEntry(Text::_('COM_RSSEO_MENU_ERRORS'),			'index.php?option=com_rsseo&view=errors',			$vName == 'errors');
		JHtmlSidebar::addEntry(Text::_('COM_RSSEO_MENU_ERROR_LINKS'),		'index.php?option=com_rsseo&view=errorlinks',		$vName == 'errorlinks');
		JHtmlSidebar::addEntry(Text::_('COM_RSSEO_MENU_REDIRECTS'),		'index.php?option=com_rsseo&view=redirects',		$vName == 'redirects');
		JHtmlSidebar::addEntry(Text::_('COM_RSSEO_MENU_KEYWORDS' ),		'index.php?option=com_rsseo&view=keywords',		$vName == 'keywords');
		JHtmlSidebar::addEntry(Text::_('COM_RSSEO_MENU_GKEYWORDS'),		'index.php?option=com_rsseo&view=gkeywords',		$vName == 'gkeywords');
		JHtmlSidebar::addEntry(Text::_('COM_RSSEO_MENU_BACKUP_RESTORE'),	'index.php?option=com_rsseo&view=backup',			$vName == 'backup');
		JHtmlSidebar::addEntry(Text::_('COM_RSSEO_MENU_ANALYTICS'),		'index.php?option=com_rsseo&view=analytics',		$vName == 'analytics');
		JHtmlSidebar::addEntry(Text::_('COM_RSSEO_MENU_STRUCTURED_DATA'),	'index.php?option=com_rsseo&view=data',			$vName == 'data');
		JHtmlSidebar::addEntry(Text::_('COM_RSSEO_MENU_STATISTICS'),		'index.php?option=com_rsseo&view=statistics',		$vName == 'statistics');
		JHtmlSidebar::addEntry(Text::_('COM_RSSEO_MENU_REPORT'),			'index.php?option=com_rsseo&view=report',			$vName == 'report');
	}
	
	// Set scripts and stylesheets
	public static function setScripts($from) {
		$doc	= Factory::getDocument();
		$tmpl	= Factory::getApplication()->input->get('tmpl') == 'component';
		
		if ($from == 'administrator') {
			HTMLHelper::_('bootstrap.tooltip', '.hasTooltip');
			
			if (!file_exists(JPATH_SITE.'/components/com_rsseo/sef.php')) {
				Factory::getApplication()->enqueueMessage(Text::sprintf('COM_RSSEO_NO_ROUTER_FILE', realpath(JPATH_SITE.'/components/com_rsseo/sef.php')),'error');
			}
			
			// Load jQuery
			self::loadjQuery();
			
			HTMLHelper::_('behavior.core');
			HTMLHelper::script('com_rsseo/admin.js', array('relative' => true, 'version' => 'auto'));
			
			if (!rsseoHelper::isJ4()) {
				HTMLHelper::script('com_rsseo/validation.js', array('relative' => true, 'version' => 'auto'));
			}
			
			HTMLHelper::stylesheet('com_rsseo/admin.css', array('relative' => true, 'version' => 'auto'));
			HTMLHelper::stylesheet('com_rsseo/font-awesome.min.css', array('relative' => true, 'version' => 'auto'));
		}
	}
	
	// Main function to get content
	public static function fopen($url, $headers = 1, $test = false, $onlyHeaders = false, $crawl = false) {
		require_once JPATH_ADMINISTRATOR.'/components/com_rsseo/helpers/http.php';
		
		$config	= rsseoHelper::getConfig();
		$proxy	= false;
		
		if ($config->proxy_enable) {
			$proxy = array();
			$proxy['proxy_server'] = $config->proxy_server;
			$proxy['proxy_port'] = $config->proxy_port;
			$proxy['proxy_usrpsw'] = $config->proxy_username.':'.$config->proxy_password;
		}
		
		$options	= array('test' => $test, 'url' => $url, 'proxy' => $proxy, 'crawl' => $crawl);
		$http		= rsseoHttp::getInstance($options);
		
		if ($onlyHeaders) {
			return $http->getStatus();
		}
		
		if ($test) {
			return $http->getErrors();
		}
		
		$response 	= $http->getResponse();
		
		if (empty($response)) {
			return 'RSSEOINVALID';
		}
		
		return $response;
	}
	
	// Convert time in a readable format
	public static function convertseconds($sec) {
		$sec	= (int) $sec;
		$text	= '';

		$hours = intval($sec / 3600); 
		$text .= str_pad($hours, 2, "0", STR_PAD_LEFT). ":";

		$minutes = (int) ($sec / 60) % 60; 
		$text .= str_pad($minutes, 2, "0", STR_PAD_LEFT). ":";

		$seconds = intval($sec % 60); 
		$text .= str_pad($seconds, 2, "0", STR_PAD_LEFT);

		return $text;
	}
	
	// Convert a timestamp to a years-days format
	public static function convertage($time) {
		$years	= floor($time / 31556926);
		$days	= floor(($time % 31556926) / 86400);
		
		if ($years == '1') {
			$y = '1 '.Text::_('COM_RSSEO_YEAR');
		} else {
			$y = $years.' '.Text::_('COM_RSSEO_YEARS');
		}
		
		if ($days == '1') {
			$d = '1 '.Text::_('COM_RSSEO_DAY');
		} else {
			$d = $days.' '.Text::_('COM_RSSEO_DAYS');
		}
		
		return $y.', '.$d;
	}
	
	// Copy keywords to density keywords
	public static function keywords() {
		$db		= Factory::getDbo();
		$query	= $db->getQuery(true);
		$config = rsseoHelper::getConfig();
		
		if ($config->copykeywords) {
			$query->clear();
			$query->update($db->qn('#__rsseo_pages'))->set($db->qn('keywordsdensity').' = '.$db->qn('keywords'));
			
			if (!$config->overwritekeywords)
				$query->where($db->qn('keywordsdensity').' = '.$db->q(''));
			
			$db->setQuery($query);
			if ($db->execute()) {
				$component	= ComponentHelper::getComponent('com_rsseo');
				$cparams	= $component->params;
				
				if ($cparams instanceof Registry) {
					$cparams->set('copykeywords', 0);
					$cparams->set('overwritekeywords', 0);
					$query->clear();
					$query->update($db->qn('#__extensions'));
					$query->set($db->qn('params'). ' = '.$db->q((string) $cparams));
					$query->where($db->qn('extension_id'). ' = '. $db->q($component->id));
					
					$db->setQuery($query);
					$db->execute();
				}
			}
		}
	}
	
	// Check broken URLs
	public static function checkBroken($id, $pageId) {
		$db			= Factory::getDbo();
		$query		= $db->getQuery(true);
		$response	= array('finished' => 1);
		
		require_once JPATH_ADMINISTRATOR. '/components/com_rsseo/helpers/phpQuery.php';
		
		// Get all internal/external links
		if (!$pageId) {
			$query->select($db->qn('url'))
				->from($db->qn('#__rsseo_pages'))
				->where($db->qn('id').' = '.(int) $id);
			$db->setQuery($query);
			$url = $db->loadResult();
			
			$url		= Uri::root().$url;
			$url		= str_replace(' ','%20',$url);
			$contents	= rsseoHelper::fopen($url,1);
			
			if (strpos($contents,'<html') === false || (strpos($contents,'RSSEOINVALID') !== false && $url != '')) {
				return json_encode($response);
			}
			
			$contents	= preg_replace('#<script.*?>.*?</script>#is','',$contents);
			$dom		= phpQuery::newDocumentHTML($contents);
			
			$query->clear()
				->delete($db->qn('#__rsseo_broken_links'))
				->where($db->qn('pid').' = '.(int) $id);
			$db->setQuery($query);
			$db->execute();
			
			$brokenLinks = array();
			foreach ($dom->find('a') as $href) {
				$href = phpQuery::pq($href)->attr('href');
				if ($href = rsseoHelper::getUrl($href)) {
					$brokenLinks[]  = $href;
				}
			}
			
			if ($brokenLinks = array_unique($brokenLinks)) {
				foreach ($brokenLinks as $brokenLink) {
					$query->clear()
						->insert($db->qn('#__rsseo_broken_links'))
						->set($db->qn('pid').' = '.(int) $id)
						->set($db->qn('url').' = '.$db->q($brokenLink))
						->set($db->qn('published').' = 0');
					
					$db->setQuery($query);
					$db->execute();
				}
				
				$query->clear()
					->select($db->qn('id'))
					->from($db->qn('#__rsseo_broken_links'))
					->where($db->qn('published').' = 0')
					->where($db->qn('pid').' = '.(int) $id);
				
				$db->setQuery($query,0,1);
				$nextId = (int) $db->loadResult();
				
				if ($nextId) {
					$response['finished']	= 0;
					$response['id']			= $nextId;
					$response['percent']	= 0;
				}
				
				return json_encode($response);
			} else {
				return json_encode($response);
			}
		} else {
			// Check URL's
			$query->clear()
				->select($db->qn('url'))
				->from($db->qn('#__rsseo_broken_links'))
				->where($db->qn('id').' = '.(int) $pageId);
			$db->setQuery($query);
			$currentUrl = $db->loadResult();
			
			$code = rsseoHelper::fopen($currentUrl, 0, false, true);
			
			$query->clear()
				->update($db->qn('#__rsseo_broken_links'))
				->where($db->qn('id').' = '.(int) $pageId);
			
			if (intval($code) == 200) {
				$query->set($db->qn('published').' = '.$db->q('-1'));
			} else {
				$query->set($db->qn('code').' = '.$db->q($code));
				$query->set($db->qn('published').' = '.$db->q(1));
			}
			
			$db->setQuery($query);
			$db->execute();
			
			$query->clear()
				->select('COUNT('.$db->qn('id').')')
				->from($db->qn('#__rsseo_broken_links'))
				->where($db->qn('pid').' = '.(int) $id);
			
			$db->setQuery($query);
			$total = (int) $db->loadResult();
			
			$query->clear()
				->select('COUNT('.$db->qn('id').')')
				->from($db->qn('#__rsseo_broken_links'))
				->where($db->qn('published').' = 0')
				->where($db->qn('pid').' = '.(int) $id);
			
			$db->setQuery($query);
			$remaining = (int) $db->loadResult();
			
			$query->clear()
				->select($db->qn('id'))
				->from($db->qn('#__rsseo_broken_links'))
				->where($db->qn('published').' = 0')
				->where($db->qn('pid').' = '.(int) $id);
			
			$db->setQuery($query,0,1);
			$nextId = (int) $db->loadResult();
			
			if ($nextId) {
				$response['finished']	= 0;
				$response['id']			= $nextId;
				$response['percent']	= ceil(($total - $remaining) * 100 / $total);
			}
			
			return json_encode($response);
		}
	}
	
	// Correctly build the URL
	public static function getUrl($url) {
		// Skip unwanted links
		if (strpos($url,'mailto:') !== FALSE) return false;
		if (strpos($url,'javascript:') !== FALSE) return false;
		if (strpos($url,'ymsgr:im') !== FALSE) return false;
		if (substr($url,0,1) == '#') return false;
		
		$uri	= Uri::getInstance();
		$root	= Uri::root();
		$base	= Uri::root(true);
		$site	= $uri->toString(array('scheme','host'));
		
		// Internal link
		if (substr($url,0,4) == 'http' && strpos($url,$root) !== false)
			return $url;
		
		// External link
		if (substr($url,0,4) == 'http' && strpos($url,$root) === false)
			return $url;
		
		// Internal link
		if (substr($url,0,strlen($base)) == $base)
			return $site.$url;
		
		// Internal link
		if (substr($url,0,9) == 'index.php')
			return $root.$url;
		
		return $url;
	}
	
	// Get error message
	public static function getError($code) {
		$db		= Factory::getDbo();
		$query	= $db->getQuery(true);
		
		$query->select('*')
			->from($db->qn('#__rsseo_errors'))
			->where($db->qn('error').' = '.(int) $code)
			->where($db->qn('published').' = 1');
		
		$db->setQuery($query);
		return $db->loadObject();
	}
	
	// Log errors
	public static function saveURL($code) {
		$db		 = Factory::getDbo();
		$query	 = $db->getQuery(true);
		$url	 = (string) Uri::getInstance();
		$enable	 = rsseoHelper::getConfig('log_errors',1);
		$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
		$crawl	 = isset($_SERVER['HTTP_X_RSSEO_CRAWLER']) ? (int) $_SERVER['HTTP_X_RSSEO_CRAWLER'] : 0;
		
		if ($crawl || !$enable) {
			return false;
		}
		
		$query->select($db->qn('id'))
			->from($db->qn('#__rsseo_error_links'))
			->where($db->qn('url').' = '.$db->q($url));
		$db->setQuery($query);
		$id = (int) $db->loadResult();
		
		if ($id) {
			$query->clear()
				->update($db->qn('#__rsseo_error_links'))
				->set($db->qn('count').' = '.$db->qn('count').' + 1')
				->where($db->qn('id').' = '.$db->q($id));
			$db->setQuery($query);
			$db->execute();
		} else {
			$query->clear()
				->insert($db->qn('#__rsseo_error_links'))
				->set($db->qn('url').' = '.$db->q($url))
				->set($db->qn('code').' = '.$db->q($code))
				->set($db->qn('count').' = 1');
			$db->setQuery($query);
			$db->execute();
			$id = $db->insertid();
		}
		
		if ($referer) {
			$query->clear()
				->insert($db->qn('#__rsseo_error_links_referer'))
				->set($db->qn('idl').' = '.$db->q($id))
				->set($db->qn('referer').' = '.$db->q($referer))
				->set($db->qn('date').' = '.$db->q(Factory::getDate()->toSql()));
			$db->setQuery($query);
			$db->execute();
		}
	}
	
	// Get a list of response codes
	public static function getResponseMessage($code) {
		$http_status_codes = array(	100 => "Continue", 
									101 => "Switching Protocols", 
									102 => "Processing", 
									200 => "OK", 
									201 => "Created", 
									202 => "Accepted", 
									203 => "Non-Authoritative Information", 
									204 => "No Content", 
									205 => "Reset Content", 
									206 => "Partial Content", 
									207 => "Multi-Status", 
									300 => "Multiple Choices", 
									301 => "Moved Permanently", 
									302 => "Found", 
									303 => "See Other", 
									304 => "Not Modified", 
									305 => "Use Proxy", 
									306 => "(Unused)", 
									307 => "Temporary Redirect", 
									308 => "Permanent Redirect", 
									400 => "Bad Request", 
									401 => "Unauthorized", 
									402 => "Payment Required", 
									403 => "Forbidden", 
									404 => "Not Found", 
									405 => "Method Not Allowed", 
									406 => "Not Acceptable", 
									407 => "Proxy Authentication Required", 
									408 => "Request Timeout", 
									409 => "Conflict", 
									410 => "Gone", 
									411 => "Length Required", 
									412 => "Precondition Failed", 
									413 => "Request Entity Too Large", 
									414 => "Request-URI Too Long", 
									415 => "Unsupported Media Type", 
									416 => "Requested Range Not Satisfiable", 
									417 => "Expectation Failed", 
									418 => "I'm a teapot", 
									419 => "Authentication Timeout", 
									420 => "Enhance Your Calm", 
									422 => "Unprocessable Entity", 
									423 => "Locked", 
									424 => "Failed Dependency", 
									424 => "Method Failure", 
									425 => "Unordered Collection", 
									426 => "Upgrade Required", 
									428 => "Precondition Required", 
									429 => "Too Many Requests", 
									431 => "Request Header Fields Too Large", 
									444 => "No Response", 
									449 => "Retry With", 
									450 => "Blocked by Windows Parental Controls", 
									451 => "Unavailable For Legal Reasons", 
									494 => "Request Header Too Large", 
									495 => "Cert Error", 
									496 => "No Cert", 
									497 => "HTTP to HTTPS", 
									499 => "Client Closed Request", 
									500 => "Internal Server Error", 
									501 => "Not Implemented", 
									502 => "Bad Gateway", 
									503 => "Service Unavailable", 
									504 => "Gateway Timeout", 
									505 => "HTTP Version Not Supported", 
									506 => "Variant Also Negotiates", 
									507 => "Insufficient Storage", 
									508 => "Loop Detected", 
									509 => "Bandwidth Limit Exceeded", 
									510 => "Not Extended", 
									511 => "Network Authentication Required", 
									598 => "Network read timeout error", 
									599 => "Network connect timeout error",
									0 => "Unknown error"
								);
		return isset($http_status_codes[$code]) ? $http_status_codes[$code] : '';
	}
	
	// Get the most visited pages
	public static function getMostVisited() {
		$db			= Factory::getDbo();
		$query		= $db->getQuery(true);
		
		$query->select($db->qn('id'))->select($db->qn('url'))
			->select($db->qn('sef'))->select($db->qn('hits'))
			->from($db->qn('#__rsseo_pages'))
			->where($db->qn('hits').' > 0')
			->order($db->qn('hits').' DESC');
		
		$db->setQuery($query,0,10);
		return $db->loadObjectList();
	}
	
	// Set the sitemap cron function
	public static function cronSitemap() {
		$db			= Factory::getDbo();
		$query		= $db->getQuery(true);
		$config		= rsseoHelper::getConfig();

		try {
			
			Factory::getCache('page')->clean();

			if (!file_exists(JPATH_SITE . '/sitemap.xml') && !file_exists(JPATH_SITE . '/ror.xml')) {
				throw new Exception(Text::_('COM_RSSEO_SITEMAP_MISSING_FILES'));
			}

			$query->clear()
				->select($db->qn('id'))->select($db->qn('url'))->select($db->qn('sef'))->select($db->qn('title'))
				->select($db->qn('level'))->select($db->qn('priority'))->select($db->qn('frequency'))->select($db->qn('date'))
				->from($db->qn('#__rsseo_pages'))
				->where($db->qn('sitemap') . ' = 0')
				->where($db->qn('insitemap') . ' = 1')
				->where($db->qn('published') . ' != -1')
				->where($db->qn('canonical') . ' = ' . $db->q(''))
				->order($db->qn('level'));
			
			if ($config->exclude_noindex) {
				$query->where($db->qn('robots').' NOT LIKE '.$db->q('%"index":"0"%'));
			}
			
			if ($config->exclude_autocrawled) {
				$query->where($db->qn('level').' <> '.$db->q('127'));
			}

			$db->setQuery($query, 0, 250);
			if ($pages = $db->loadObjectList()) {
				require_once JPATH_ADMINISTRATOR . '/components/com_rsseo/helpers/sitemap.php';
				$modified_date = isset($config->sitemapmodified_date) ? $config->sitemapmodified_date : 1;
				$modified = isset($config->sitemapmodified) ? $config->sitemapmodified : HTMLHelper::_('date', 'NOW', 'Y-m-d');
				$website = isset($config->sitemapwebsite) ? $config->sitemapwebsite : '';
				$images = isset($config->sitemapimages) ? $config->sitemapimages : 0;
				
				$options = array('new' => 0, 'modified_date' => $modified_date, 'modified' => $modified, 'website' => $website, 'images' => $images);
				$sitemap = sitemapHelper::getInstance($options);

				if ((file_exists(JPATH_SITE . '/sitemap.xml') && filesize(JPATH_SITE . '/sitemap.xml') < 99) || (file_exists(JPATH_SITE . '/ror.xml') && filesize(JPATH_SITE . '/ror.xml') < 103)) {
					$sitemap->clear();
					$sitemap->setHeader(true);
					$sitemap->close();
				}

				foreach ($pages as $page) {
					$page->url = rsseoHelper::showURL($page->url, $page->sef);
					
					$sitemap->add($page, true);

					$query->clear()
						->update($db->qn('#__rsseo_pages'))
						->set($db->qn('sitemap') . ' = 1')
						->where($db->qn('id') . ' = ' . $db->q($page->id));
					$db->setQuery($query);
					$db->execute();
				}
			}
		} catch (Exception $e) {
			//echo $e->getMessage(); Perhaps send an email to the admin in a future version?
		}
	}
	
	// Get IP address
	public static function getIP($check_for_proxy = false) {
		$ip = $_SERVER['REMOTE_ADDR'];

		if ($check_for_proxy) {
			$headers = array('HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'HTTP_VIA', 'HTTP_X_COMING_FROM', 'HTTP_COMING_FROM');
			foreach ($headers as $header)
				if (!empty($_SERVER[$header]))
					$ip = $_SERVER[$header];
		}

		return $ip;
	}
	
	// Check if a url is internal or not
	public static function isInternal($url) {
		try
		{
			$uri = Uri::getInstance($url);
		}
		catch (Exception $e)
		{
			// malformed URL
			return false;
		}

		$base = $uri->toString(array('scheme', 'host', 'port', 'path'));
		$host = $uri->toString(array('scheme', 'host', 'port'));

		if (stripos($base, Uri::base()) !== 0 && !empty($host)) {
			return false;
		}
		
		return true;
	}
	
	// Check for a valid chmod permission
	public static function validatePermission($string, $default = '644') {
		return preg_match('/^[0-7]{3}$/', $string) ? $string : $default;
	}
	
	// Check if the IP address is IPV4
	public static function isIPV4($ip) {
		if (defined('FILTER_VALIDATE_IP') && defined('FILTER_FLAG_IPV4')) {
			return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
		} else {
			return (strpos($ip, '.') !== false && strpos($ip, ':') === false);
		}
	}
	
	// Check if the IP address is IPV6
	public static function isIPV6($ip) {
		if (defined('FILTER_VALIDATE_IP') && defined('FILTER_FLAG_IPV6')) {
			return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
		} else {
			return (strpos($ip, ':') !== false);
		}
	}
	
	// Obfuscate IP address
	public static function obfuscateIP($ip) {
		if ($ip == '::1' || $ip == '127.0.0.1') {
			return 'localhost';
		}
		
		if (!rsseoHelper::getConfig('obfuscate_visitor_ip', 0)) {
			return $ip;
		}
		
		if (strpos($ip,'%') !== false) {
			$ip = substr_replace($ip, '', strpos($ip,'%'), strlen($ip));
		}
		
		if (rsseoHelper::isIPV4($ip)) {
			list($p1, $p2, $p3, $p4) = explode('.', $ip);
			$p4 = 'x';
			
			return $p1.'.'.$p2.'.'.$p3.'.'.$p4;
		} elseif (rsseoHelper::isIPV6($ip)) {
			$parts = explode(':', $ip);
			array_pop($parts);
			$parts[] = '---';
			
			return implode(':',$parts);
		} else {
			return $ip;
		}
	}
	
	// Show custom URL for page
	public static function showURL($url, $custom) {
		$db		= Factory::getDbo();
		$config = Factory::getConfig();
		$lang	= '';
		
		if (!rsseoHelper::getConfig('enable_sef')) {
			return $url;
		}
		
		$url = str_replace(array('&amp;','&apos;'), array('&',"'"), $url);
		$url = str_replace(array('&',"'"), array('&amp;','&apos;'), $url);
		
		$query = $db->getQuery(true)->select($db->qn('published'))->from($db->qn('#__rsseo_pages'))->where($db->qn('url').' = '.$db->q($url));
		$db->setQuery($query);
		if ($published = $db->loadResult()) {
			if ($config->get('sef')) {
				// If no custom SEF URL , then return the page URL
				if (empty($custom)) {
					return $url;
				}
				
				if (PluginHelper::isEnabled('system','languagefilter')) {
					$lang_codes = LanguageHelper::getContentLanguages();
					$parts 		= explode('/',$url);
					$codes	 	= array();
					
					foreach ($lang_codes as $code) {
						$codes[] = $code->sef;
					}
					
					if (in_array($parts[0], $codes)) {
						$lang = $parts[0];
					}
				}
				
				// Check for the .htaccess file
				if (!file_exists(JPATH_SITE.'/.htaccess')) {
					$custom = 'index.php/'.($lang ? $lang.'/' : '').$custom;
				} else {
					if ($lang) {
						$custom = $lang.'/'.$custom;
					}
				}
				
				// Append the SEF suffix
				if ($config->get('sef_suffix')) {
					$custom .= '.html';
				}
				
				return $custom;
			}
		}
		
		return $url;
	}
	
	// Get a page custom SEF URL
	public static function getSEF($url) {
		static $SEFurls = array();
		
		$db		= Factory::getDbo();
		$config = Factory::getConfig();
		$hash	= md5($url);
		$lang	= '';
		
		if (!rsseoHelper::getConfig('enable_sef')) {
			return $url;
		}
		
		if (!isset($SEFurls[$hash])) {
			$query = $db->getQuery(true)->select($db->qn('sef'))->from($db->qn('#__rsseo_pages'))->where($db->qn('hash').' = '.$db->q($hash))->where($db->qn('published').' = '.$db->q(1));
			$db->setQuery($query);
			if ($sef = $db->loadResult()) {
				if ($config->get('sef')) {
					
					if (PluginHelper::isEnabled('system','languagefilter')) {
						$lang_codes = LanguageHelper::getContentLanguages();
						$parts 		= explode('/',$url);
						$codes	 	= array();
						
						foreach ($lang_codes as $code) {
							$codes[] = $code->sef;
						}
						
						if (in_array($parts[0], $codes)) {
							$lang = $parts[0];
						}
					}
					
					// Check for the .htaccess file
					if (!file_exists(JPATH_SITE.'/.htaccess')) {
						$sef = 'index.php/'.($lang ? $lang.'/' : '').$sef;
					} else {
						if ($lang) {
							$sef = $lang.'/'.$sef;
						}
					}
					
					// Append the SEF suffix
					if ($config->get('sef_suffix')) {
						$sef .= '.html';
					}
					
					$SEFurls[$hash] = $sef;
				}
			} else {
				$SEFurls[$hash] = $url;
			}
		}
		
		return $SEFurls[$hash];
	}
	
	// Save log
	public static function saveLog($type, $message) {
		$db			= Factory::getDbo();
		$query		= $db->getQuery(true);
		
		$query->insert($db->qn('#__rsseo_logs'))
			->set($db->qn('type').' = '.$db->q($type))
			->set($db->qn('date').' = '.$db->q(Factory::getDate()->toSql()))
			->set($db->qn('message').' = '.$db->q($message));
			
		$db->setQuery($query);
		$db->execute();
	}
	
	public static function short($id) {
		require_once JPATH_ADMINISTRATOR.'/components/com_rsseo/helpers/hashids/hashgenerator.php';
		require_once JPATH_ADMINISTRATOR.'/components/com_rsseo/helpers/hashids/hashids.php';
		
		$secret = Factory::getConfig()->get('secret');
		
		$hashids = new RSSeoHashids\RSSeoHashids($secret, 8);
		return $hashids->encode($id);
	}
	
	public static function shortEnabled() {
		return file_exists(JPATH_SITE.'/.htaccess') && Factory::getConfig()->get('sef_rewrite');
	}
	
	public static function deleteVisitorsData() {
		$config		= rsseoHelper::getConfig();
		$db			= Factory::getDbo();
		$query		= $db->getQuery(true);
		$now		= Factory::getDate()->toUnix();
		$last_run	= isset($config->lastrunvisitors) ? $config->lastrunvisitors : '';
		$first		= empty($last_run);
		$interval	= 10;
		
		if ($config->track_visitors && $config->autodeletevisitors) {
			if (!$first && $last_run + ($interval * 60) > $now) {
				return false;
			}
			
			rsseoHelper::updateConfig('lastrunvisitors', $now);
			
			$date = Factory::getDate()->modify("-{$config->autodeletevisitors} months")->toSql();
			
			$query->clear()
				->delete($db->qn('#__rsseo_visitors'))
				->where($db->qn('date').' < '.$db->q($date));
			$db->setQuery($query);
			$db->execute();
		}
	}
}