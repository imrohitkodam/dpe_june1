<?php
/**
* @package RSSeo!
* @copyright (C) 2019 www.rsjoomla.com
* @license GPL, http://www.gnu.org/licenses/gpl-2.0.html
*/

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );
$_SESSION['VMCHECK'] = 'NOCHECK';

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Router\SiteRouter;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Filesystem\File;
use Joomla\Registry\Registry;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Cache\Cache;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Factory;

/**
 * RSSeo system plugin
 */
class plgSystemRsseo extends CMSPlugin
{
	protected $autoloadLanguage = true;
	
	public $url;

	protected $hasWebpSupport;
	
	/**
	 * Object Constructor.
	 *
	 * @access	public
	 * @param	object	The object to observe -- event dispatcher.
	 * @param	object	The configuration object for the plugin.
	 * @return	void
	 * @since	1.6
	 */
	public function __construct(&$subject, $config) {
		parent::__construct($subject, $config);

		if (!Factory::getApplication()->isClient('cli')) {
			$this->setURL();
		}
		
		if (Factory::getApplication()->isClient('site')) {
			// Output the compressed file
			$this->outputGzipedFile();
			
			if (!self::isJ4() && $this->canRun()) {
				$custom_errors = rsseoHelper::getConfig('custom_errors', 0);
				
				if ($custom_errors) {
					JError::setErrorHandling(E_ERROR, 'callback', array('plgSystemRsseo', 'handleError'));
					set_exception_handler(array('plgSystemRsseo', 'handleError'));
				}
			}
		}
	}
	
	/**
	 *	Get and set the current URL
	 */
	public function setURL() {
		$uri = Uri::getInstance();
		$url = rsseoUri::getUrl($uri);
		$this->url = urldecode(str_replace(Uri::root(), '', $url));
	}
	
	/**
	 *	Get the current version of Joomla!
	 */
	protected static function isJ4() {
		return version_compare(JVERSION, '4.0', '>=');
	}
	
	/**
	 *	Check if the plugin can run
	 */
	protected static function canRun() {
		if (Factory::getApplication()->isClient('cli')) {
			return false;
		}
		
		if (file_exists(JPATH_ADMINISTRATOR.'/components/com_rsseo/helpers/rsseo.php')) {
			Factory::getLanguage()->load('plg_system_rsseo',JPATH_ADMINISTRATOR);
			require_once JPATH_ADMINISTRATOR.'/components/com_rsseo/helpers/rsseo.php';
			return true;
		}
		
		return false;
	}
	
	public function onAfterPurge($group = array()) {
		if (is_array($group) && empty($group)) {
			$options	= array('storage' => 'file', 'defaultgroup' => 'plg_system_rsseo', 'cachebase' => realpath(Factory::getConfig()->get('cache_path', JPATH_SITE.'/cache')));
			$jcache		= Cache::getInstance('callback', $options);
			
			$jcache->clean();
		}
	}
	
	/**
	 *	onAfterDispatch method
	 */
	public function onAfterDispatch() {
		$doc		= Factory::getDocument();
		$app 		= Factory::getApplication();
		$jconfig	= Factory::getConfig();
		
		if (!$this->canRun()) {
			return false;
		}
		
		// Run sitemap cron
		$this->sitemap();
		
		// Remove old visitor data
		rsseoHelper::deleteVisitorsData();
		
		if (!$app->isClient('site')) {
			return false;
		}
		
		// Redirect old link to the new SEF URL
		$this->redirect();
		
		$config		= rsseoHelper::getConfig();
		
		// Set Yandex site verification key
		if ($this->params->get('enabley',0)) {
			$doc->setMetaData('yandex-verification', $this->params->get('contenty',''));
		}
		// Set Bing site verification key
		if ($this->params->get('enableb',0)) {
			$doc->setMetaData('msvalidate.01', $this->params->get('contentb',''));
		}
		// Set Google site verification key
		if ($this->params->get('enable',0)) {
			$doc->setMetaData($this->params->get('type','google-site-verification'), $this->params->get('content',''));
		}
		
		// Add site name in title
		$sitename = $jconfig->get('sitename');
		if ($config->site_name_in_title != 0 && !empty($sitename)) {
			if ($oldtitle = $doc->getTitle()) {
				if (strpos($oldtitle, $sitename) === FALSE) {
					if ($config->site_name_in_title == 1) {
						$doc->setTitle($oldtitle.' '.$config->site_name_separator.' '.$sitename);
					} else if ($config->site_name_in_title == 2) {
						$doc->setTitle($sitename.' '.$config->site_name_separator.' '.$oldtitle);
					}
				}
			}
		}
		
		// Add page if auto-crawler is ON
		$this->auto();
		
		// Set new metadata
		$this->meta();
		
		// Set visit
		$this->visit();
	}
	
	/**
	 *	onAfterInitialise method
	 */
	public function onAfterInitialise() {
		$db		= Factory::getDbo();
		$query	= $db->getQuery(true);
		$app	= Factory::getApplication();
		$sef	= Factory::getConfig()->get('sef');
		
		if (!$this->canRun() || !$app->isClient('site')) {
			return false;
		}

		// Check for WebP support
		if ($this->params->get('enable_webp'))
		{
			$this->hasWebpSupport = $this->hasWebpSupport();
		}
		
		if ($app->input->getInt('rsseoInit',0) == 1) {
			$app->logout();
		}
		
		// Get current URL
		$url = $this->getURL();
		$url = str_replace(array('www.',Uri::root(),'&amp;'), array('','','&'), $url);
		$url = str_replace('&', '&amp;', $url);

		// Custom SEF URLs
		if ($sef && rsseoHelper::getConfig('enable_sef') && file_exists(JPATH_SITE.'/components/com_rsseo/sef.php')) {
			require_once JPATH_SITE.'/components/com_rsseo/sef.php';

			$rsseoRouter	= new RsseoSef();

			if (self::isJ4()) {
				if (version_compare(JVERSION, '4.2.0', '<')) {
					$router = $app->getRouter();
				} else {
					$router = Factory::getContainer()->get(SiteRouter::class);
				}

				$router->attachBuildRule(array($rsseoRouter, 'buildRule'));
				$rules = $router->getRules();

				foreach($rules['parsepreprocess'] as $rule) {
					$router->detachRule('parse', $rule , 'preprocess');
				}

				$router->attachParseRule(array($rsseoRouter, 'parseRule'), 'preprocess');

				foreach($rules['parsepreprocess'] as $rule) {
					$router->attachParseRule( $rule , 'preprocess');
				}

			} else {
				$router			= $app->getRouter();

				$router->attachBuildRule(array($rsseoRouter, 'buildRule'));
				$router->attachParseRule(array($rsseoRouter, 'parseRule'), 'preprocess');
			}
		}
		
		// Redirect page if available
		$query->clear()
			->select('*')
			->from($db->qn('#__rsseo_redirects'))
			->where($db->qn('published').' = 1');
		$db->setQuery($query);
		
		if ($redirects = $db->loadObjectList()) {
			foreach ($redirects as $redirect) {
				$regex = $this->ignore($url, array($redirect->from));
				$hasRegex = strpos($redirect->from,'{?') !== false || strpos($redirect->from,'{*') !== false;
				$redirect->from = str_replace('&amp;', '&', $redirect->from);
				$redirect->from = str_replace('&', '&amp;', $redirect->from);
				
				if (urldecode(trim($redirect->from)) == urldecode($url) || ($regex && $hasRegex)) {
					if (empty($redirect->to)) 
						continue;
					
					$redirectURL = substr($redirect->to,0,4) != 'http' ? Uri::root().$redirect->to : $redirect->to;
					
					$query->clear()
						->update($db->qn('#__rsseo_redirects'))
						->set($db->qn('hits').' = '.$db->qn('hits').' + 1')
						->where($db->qn('id').' = '.(int) $redirect->id);
					$db->setQuery($query);
					$db->execute();
					
					$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
					$query->clear()
						->insert($db->qn('#__rsseo_redirects_referer'))
						->set($db->qn('rid').' = '.(int) $redirect->id)
						->set($db->qn('referer').' = '.$db->q($referer))
						->set($db->qn('date').' = '.$db->q(Factory::getDate()->toSql()));
						
					if ($regex && (strpos($redirect->from,'{*}') !== false || strpos($redirect->from,'{?') !== false)) {
						$query->set($db->qn('url').' = '.$db->q($url));
					}
					
					$db->setQuery($query);
					$db->execute();
					
					if ($redirect->type == 301) {
						header("HTTP/1.1 301 Moved Permanently");
						header("Location: ".$redirectURL);
						$app->close();
					} else {
						header("Location: ".$redirectURL);
						$app->close();
					}
				}
			}
		}
		
		// Short URL
		if (!empty($url)) {
			$query->clear()
				->select($db->qn('url'))->select($db->qn('sef'))
				->from($db->qn('#__rsseo_pages'))
				->where($db->qn('short').' = '.$db->q($url));
			$db->setQuery($query);
			if ($page = $db->loadObject()) {
				if (!empty($page->sef)) {
					$rURL = rsseoHelper::getSEF($page->url);
				} else {
					if ($page->url) {
						$rURL = Uri::root().$page->url;
					}
				}
				
				if (isset($rURL)) {			
					header("HTTP/1.1 301 Moved Permanently");
					header("Location: ".$rURL);
					$app->close();
				}
			}
		}
		
		// Canonicalization
		if ($this->params->get('enablecan','0')) {
			$host = $this->params->get('domain','');
			$host = trim($host);
			
			if ($host) {
				$host = str_replace(array('http://','https://'), '', $host);
				if(@$_SERVER['HTTP_HOST'] == $host) {
					return true;	
				}
				// Get protocol
				$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https://' : 'http://';
				
				$url = $protocol . $host . $_SERVER['REQUEST_URI'];
				header("HTTP/1.1 301 Moved Permanently");
				header('Location: '. $url);
				$app->close();
			}
		}
	}
	
	/**
	 *	onBeforeRender method
	 */
	public function onBeforeRender() {
		$db		= Factory::getDbo();
		$query	= $db->getQuery(true);
		
		if (!$this->canRun() || !Factory::getApplication()->isClient('site')) {
			return false;
		}
		
		$query->select('*')
			->from($db->qn('#__rsseo_data'));
		$db->setQuery($query);
		if ($data = $db->loadObjectList()) {
			require_once JPATH_ADMINISTRATOR.'/components/com_rsseo/helpers/structured.php';
			
			foreach ($data as $object) {
				RSSeoStructuredData::getInstance($object);
			}
			
			RSSeoStructuredData::generate();
		}
	}
	
	/**
	 *	onAfterRender method
	 */
	public function onAfterRender() {
		$db		= Factory::getDbo();
		$query	= $db->getQuery(true);
		$app	= Factory::getApplication();
		$change = false;
		
		if (!$this->canRun() || !$app->isClient('site')) {
			return false;
		}
		
		$config	= rsseoHelper::getConfig();
		
		// Get page body
		$body = Factory::getApplication()->getBody();
		
		// Remove the meta generator
		if ($this->params->get('generator',0)) {
			$body = preg_replace('/<meta.*name=[\",\']generator[\",\'].*\/?>/i', '', $body);
			$change = true;
		}
		
		// Replace keywords
		if ($config->enable_keyword_replace == 1) {
			$change = true;
			
			// Get all the keywords
			$query->clear()
				->select('*')
				->from($db->qn('#__rsseo_keywords'))
				->order($query->charLength('keyword').' DESC');
			$db->setQuery($query);
			if ($keywords = $db->loadObjectList()) {
				// Get current URL
				$url = $this->getURL();
				$url = str_replace(array(Uri::root(),'&amp;'), array('','&'), $url);
				$url = str_replace('&', '&amp;', $url);
				
				// Get all links from our page
				preg_match_all('#<a\s[^>]*href=(\"??)([^\" >]*?)\\1[^>]*>(.*)<\/a>#siU', $body, $links);
				if (!empty($links)) {
					foreach($links[0] as $i => $link) {
						$body = str_replace($link,'{rsseo '.$i.'}', $body);
					}
				}
				
				foreach ($keywords as $keyword) {
					if (!empty($keyword->link) && ($keyword->link == $url || $keyword->link == Uri::root().$url))
						continue;
					
					$lowerK = mb_strtolower($keyword->keyword);
					$lowerB = mb_strtolower($body);
					
					if (strpos($lowerB, $lowerK) !== FALSE || strpos($lowerB, htmlentities($lowerK, ENT_COMPAT, 'utf-8')) !== false) {
						$body = $this->replace($body, $keyword->keyword, $this->_setOptions($keyword->keyword, $keyword->bold, $keyword->underline, $keyword->link, $keyword->attributes), $keyword->limit);
						if ($keyword->keyword !== htmlentities($keyword->keyword, ENT_COMPAT, 'utf-8')) {
							$body = $this->replace($body, htmlentities($keyword->keyword, ENT_COMPAT, 'utf-8'), $this->_setOptions($keyword->keyword, $keyword->bold, $keyword->underline, $keyword->link, $keyword->attributes), $keyword->limit);
						}
						
						preg_match_all('#<a\s[^>]*href=(\"??)([^\" >]*?)\\1[^>]*>(.*)<\/a>#siU', $body, $links2[$keyword->keyword]);
						if (!empty($links2)) {
							foreach ($links2[$keyword->keyword][0] as $j => $link) {
								$body = str_replace($link,'{rsseo '.md5($keyword->keyword).' '.$j.'}', $body);
							}
						}
					}
				}
				
				foreach ($links[0] as $i => $link)
					$body = str_replace('{rsseo '.$i.'}', $link, $body);
					
				foreach ($keywords as $keyword) {
					if (!empty($links2[$keyword->keyword][0])) {
						foreach ($links2[$keyword->keyword][0] as $i => $link) {
							$body = str_replace('{rsseo '.md5($keyword->keyword).' '.$i.'}', $link, $body);
						}
					}
				}
				
			}
		}
		
		// Add Google tracking code
		if ($config->ga_tracking) {
			$code = $config->ga_code;
			if (!empty($code)) {
				if (strpos($body,$code) === false) {
					
					if ($config->ga_type == 2) {
						$text = '<script async src="https://www.googletagmanager.com/gtag/js?id='.$code.'"></script>'."\n";
						$text .= "\t".'<script>'."\n";
						$text .= "\t".'window.dataLayer = window.dataLayer || [];'."\n";
						$text .= "\t".'function gtag(){dataLayer.push(arguments);}'."\n";
						$text .= "\t".'gtag(\'js\', new Date());'."\n";
						$text .= "\t".'gtag(\'config\', \''.$code.'\');'."\n";
						
						if ($config->ga_options_4) {
							$options = str_replace("\r", "", $config->ga_options_4);
							if ($options = explode("\n", $options)) {
								foreach ($options as $option) {
									$text .= "\t".$option."\n";
								}
							}
						}
						
						$text .= "\t".'</script>'."\n";
						$text .= '</head>'."\n";
					} else if ($config->ga_type == 1) {
						$text = '<script type="text/javascript">'."\n";
						$text .= "\t".'(function(i,s,o,g,r,a,m){i[\'GoogleAnalyticsObject\']=r;i[r]=i[r]||function(){'."\n";
						$text .= "\t".'(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),'."\n";
						$text .= "\t".'m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)'."\n";
						$text .= "\t".'})(window,document,\'script\',\'//www.google-analytics.com/analytics.js\',\'ga\');'."\n\n";
						$text .= "\t".'ga(\'create\', \''.$code.'\', \'auto\');'."\n";
						
						if ($config->ga_options) {
							$options = str_replace("\r", "", $config->ga_options);
							if ($options = explode("\n", $options)) {
								foreach ($options as $option) {
									$text .= "\t".$option."\n";
								}
							}
						}
						
						$text .= "\t".'ga(\'send\', \'pageview\');'."\n";
						$text .= '</script>'."\n";
						$text .= '</head>'."\n";
					} else {
						$text = '<script type="text/javascript">'."\n";
						$text .= "\t".'var _gaq = _gaq || [];'."\n";
						$text .= "\t".'_gaq.push([\'_setAccount\', \''.$code.'\']);'."\n";
						$text .= "\t".'_gaq.push([\'_trackPageview\']);'."\n";
						$text .= "\t".'(function() {'."\n";
						$text .= "\t\t".'var ga = document.createElement(\'script\'); ga.type = \'text/javascript\'; ga.async = true;'."\n";
						$text .= "\t\t".'ga.src = (\'https:\' == document.location.protocol ? \'https://ssl\' : \'http://www\') + \'.google-analytics.com/ga.js\';'."\n";
						$text .= "\t\t".'var s = document.getElementsByTagName(\'script\')[0]; s.parentNode.insertBefore(ga, s);'."\n";
						$text .= "\t".'})();'."\n";
						$text .= '</script>'."\n";
						$text .= '</head>'."\n";
					}
					
					$change = true;
					$body	= str_replace('</head>', $text, $body);
				}
			}
		}
		
		// Set the cookie accept
		if ($this->params->get('cookie_accept',0)) {
			if (!isset($_COOKIE['rsseoaccept'])) {
				Factory::getLanguage()->load('plg_system_rsseo',JPATH_ADMINISTRATOR);
				
				$position = strtolower($this->params->get('cookie_position','down'));
				$position = !in_array($position, array('up','down')) ? 'down' : $position;
				
				$change = true;
				$info	= $this->params->get('cookie_info','');
				$css	= '<link rel="stylesheet" href="'.HTMLHelper::stylesheet('com_rsseo/cookieaccept.css', array('relative' => true, 'version' => 'auto', 'pathOnly' => true)).'" type="text/css" />';
				$js		= '<script src="'.HTMLHelper::script('com_rsseo/cookieaccept.js', array('relative' => true, 'version' => 'auto', 'pathOnly' => true)).'" type="text/javascript"></script>';
				$html	= '<div id="rsseo-cookie-accept" class="rsseo-cookie-'.$position.'" style="opacity: 0.8;">';
				$html	.= Text::_('RSSEO_COOKIE_TEXT');
				
				if (!empty($info)) {
					$html	.= Text::sprintf('RSSEO_COOKIE_INFO',$info);
				}
				
				$html	.= '<button type="button" id="rsseo-cookie-accept-btn">'.Text::_('RSSEO_COOKIE_ACCEPT_I_UNDERSTAND').'</button>';
				$html	.= '</div>';
				
				$body	= str_replace(array('</head>','</body>'), array($css."\n".$js."\n </head>",$html."\n </body>"), $body);
			}
		}
		
		if ($this->params->get('frontend_seo',0)) {
			$allowed = $this->params->get('frontend_seo_groups','');
			
			if ($allowed) {
				$allowed = array_map('intval', $allowed);
				
				$groups  = Factory::getUser()->getAuthorisedGroups();
			
				if (array_intersect($allowed, $groups)) {
					Factory::getLanguage()->load('plg_system_rsseo',JPATH_ADMINISTRATOR);
					
					require_once JPATH_ADMINISTRATOR.'/components/com_rsseo/helpers/adapter/adapter.php';
					
					$change = true;
					$page   = $this->getPage();
					
					$css	= '<link rel="stylesheet" href="'.HTMLHelper::stylesheet('com_rsseo/edit.css', array('relative' => true, 'version' => 'auto', 'pathOnly' => true)).'" type="text/css" />';
					$js		= '<script src="'.HTMLHelper::script('com_rsseo/edit.js', array('relative' => true, 'version' => 'auto', 'pathOnly' => true)).'" type="text/javascript"></script>';
					
					$view = new HtmlView(array(
						'name' => 'edit',
						'layout' => 'default',
						'base_path' => JPATH_SITE.'/components/com_rsseo'
					));
					
					$view->page = $page;
					$view->metatypes = array(HTMLHelper::_('select.option', 'name', Text::_('RSSEO_EDIT_METADATA_TYPE_NAME')), HTMLHelper::_('select.option', 'property', Text::_('RSSEO_EDIT_METADATA_TYPE_PROPERTY')));
					$view->robotsOptions = array(HTMLHelper::_('select.option', 1, Text::_('JYES')), HTMLHelper::_('select.option', 0, Text::_('JNO')), HTMLHelper::_('select.option', 'off', Text::_('RSSEO_ROBOTS_OPTION_OFF')));
					$view->addTemplatePath(JPATH_THEMES . '/' . Factory::getApplication()->getTemplate() . '/html/com_rsseo/' . $view->getName());
					
					$html = $view->loadTemplate();
					$body = str_replace(array('</head>','</body>'), array($css."\n".$js."\n </head>", $html."\n </body>"), $body);
				}
			}
		}
		
		if ($config->img_auto_alt || $config->img_auto_title) {
			ini_set("pcre.backtrack_limit", "23001337");
			ini_set("pcre.recursion_limit", "23001337");
			
			$change	 		= true;
			$imgpattern		= '/<img[^>]+>/i';
			$pattern		= '/(src|alt|title)=["|\'](.*?)["|\']/i';
			$doc			= Factory::getDocument();
			$pageTitle		= $doc->getTitle();
			$pageKeywords	= $doc->getMetaData('keywords');
			$pageDescription= $doc->getDescription();
			
			if (preg_match_all($imgpattern, $body, $matches)) {
				if (isset($matches[0])) {
					// Get javascripts
					$javascript = '#<script(.*?)<\/script>#is';
					preg_match_all($javascript, $body, $jmatches);
					
					if (isset($jmatches[0])) {
						foreach ($jmatches[0] as $j => $jmatch) {
							$body = str_replace($jmatch, 'rsseo_'.$j.'_javascript', $body);
						}
					}
					
					foreach ($matches[0] as $i => $image) {
						$src 		 = false;
						$alt 		 = false;
						$title		 = false;
						$changeImage = false;
						
						if (preg_match('/src=["|\'](.*?)["|\']/i', $image, $srcMatch)) {
							if (isset($srcMatch[1])) {
								$src = $srcMatch[1];
							}
						}
						
						if (empty($src)) {
							continue;
						}
						
						if (preg_match('/alt=["|\'](.*?)["|\']/i', $image, $altMatch)) {
							if (isset($altMatch[1])) {
								$alt = $altMatch[1];
							}
						}
						
						if (preg_match('/title=["|\'](.*?)["|\']/i', $image, $titleMatch)) {
							if (isset($titleMatch[1])) {
								$title = $titleMatch[1];
							}
						}
						
						$hasAlt		= $alt !== false;
						$hasTitle	= $title !== false;
						
						// Get the name of the image
						$name = File::stripExt(basename($src));
						
						// Replace ALT tag
						if ($config->img_auto_alt == 1) {
							if (empty($alt) && !empty($config->img_auto_alt_rule)) {
								$alt = str_replace(array('{name}','{title}','{keywords}','{description}'), array($name, $pageTitle, $pageKeywords, $pageDescription), $config->img_auto_alt_rule);
							}
						} elseif ($config->img_auto_alt == 2) {
							if (!empty($config->img_auto_alt_rule)) {
								$alt = str_replace(array('{name}','{title}','{keywords}','{description}'), array($name, $pageTitle, $pageKeywords, $pageDescription), $config->img_auto_alt_rule);
							}
						}
						
						// Replace TITLE tag
						if ($config->img_auto_title == 1) {
							if (empty($title) && !empty($config->img_auto_title_rule)) {
								$title = str_replace(array('{name}','{title}','{keywords}','{description}'), array($name, $pageTitle, $pageKeywords, $pageDescription), $config->img_auto_title_rule);
							}
						} elseif ($config->img_auto_title == 2) {
							if (!empty($config->img_auto_title_rule)) {
								$title = str_replace(array('{name}','{title}','{keywords}','{description}'), array($name, $pageTitle, $pageKeywords, $pageDescription), $config->img_auto_title_rule);
							}
						}
						
						if ($alt) {
							$changeImage = true;
							$alt = htmlentities($alt, ENT_COMPAT, 'UTF-8');
							
							if ($hasAlt) {
								$image = str_replace($altMatch[0], 'alt="'.$alt.'"', $image);
							} else {
								$image = str_replace($srcMatch[0], $srcMatch[0].' alt="'.$alt.'"', $image);
							}
						}
						
						if ($title) {
							$changeImage = true;
							$title = htmlentities($title, ENT_COMPAT, 'UTF-8');
							
							if ($hasTitle) {
								$image = str_replace($titleMatch[0], 'title="'.$title.'"', $image);
							} else {
								$image = str_replace($srcMatch[0], $srcMatch[0].' title="'.$title.'"', $image);
							}
						}
						
						if ($image && $changeImage) {
							$body = str_replace($matches[0][$i], $image, $body);
						}
					}
					
					if (isset($jmatches[0])) {
						foreach ($jmatches[0] as $k => $jmatch) {
							$body = str_replace('rsseo_'.$k.'_javascript', $jmatch, $body);
						}
					}
				}
			}
		}
		
		if (isset($config->customhead) && !empty($config->customhead)) {
			$body = str_replace('</head>', $config->customhead."\n</head>", $body);
			$change = true;
		}

		if ($this->params->get('enable_webp') && $this->hasWebpSupport) {
			$this->addWebpToBody($body, $change);
		}
		
		// Remove CSS/JS files
		$this->removeCSSJs($body, $change);
		
		// RSSeo! CSS/JS Optimization
		$this->optimize($body, $change);
		
		if ($change) {
			Factory::getApplication()->setBody($body);
		}
	}

	protected function addWebpToBody(&$body, &$change) {
		$root = Uri::root(true);
		$uri  = Uri::getInstance();
		
		if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
			return false;
		}
		
		if (preg_match_all('/\ src=["|\']([^\"]+)\.(png|jpg|jpeg)(.*?)["|\']/is', $body, $matches)) {
			foreach ($matches[0] as $index => $match) {
				$imageUrl = $matches[1][$index] . '.' . $matches[2][$index];
				$originalImageUrl = $imageUrl;
				
				if (substr($imageUrl,0,2) == '//') {
					$imageUrl = substr_replace($imageUrl, $uri->toString(array('scheme')), 0, 2);
				}
				
				if (preg_match('/^(http|https):\/\//', $imageUrl) && strstr($imageUrl, Uri::root())) {
					$imageUrl = str_replace(Uri::root(), '', $imageUrl);
				}

				if (!empty($root) && substr($imageUrl,0,strlen($root.'/')) == $root.'/') {
					$imageUrl = str_replace(Uri::root(true).'/', '', $imageUrl);
				}
				
				$imagePath = JPATH_ROOT . '/' . $imageUrl;

				if (is_file($imagePath)) {
					// Skip this image
					if ($this->skipImagePath($imagePath)) {
						continue;
					}

					// Construct the webP image
					$webpPath	= preg_replace('/\.(png|jpg|jpeg)$/', '.webp', $imagePath);
					
					if ($this->params->get('webp_image_location', 1) == 1) {
						$hash		= md5($webpPath);
						$webpPath	= JPATH_ROOT.'/media/com_rsseo/images/webp/'.$hash.'.webp';
					}
					
					// Check if we need to create a WebP image (if it doesn't exist yet, or if the original image is modified)
					if (is_file($webpPath) == false || filemtime($imagePath) > filemtime($webpPath)) {
						// Convert to WebP
						$converted = $this->convertToWebp($imagePath, $webpPath);

						if (!$converted) {
							continue;
						}
					}

					// Only replace the WebP image if it exists
					if (is_file($webpPath) && filesize($webpPath) > 0) {
						// Add the image to the list
						$image = $originalImageUrl;
						
						if ($this->params->get('webp_image_location', 1) == 1) {
							$webpImage = Uri::root().'media/com_rsseo/images/webp/'.$hash.'.webp';
						} else {
							$webpImage = preg_replace('/\.(png|jpg|jpeg)$/', '.webp', $image);

							if (preg_match('/^(http:|https:|\/)/', $webpImage) == false) {
								$webpImage = Uri::root() . $webpImage;
							}
						}
						
						// Change the image
						$htmlTag = $matches[0][$index];
						$newHtmlTag = ' data-orig="' . $image . '" data-webp="' . $webpImage . '"';

						$body = str_replace($htmlTag, $newHtmlTag, $body);

						$foundWebp = true;
					}
				}
			}

			if (!empty($foundWebp)) {
				$body = str_replace('</body>', '<script src="' . HTMLHelper::_('script', 'com_rsseo/webp.js', array('pathOnly' => true, 'relative' => true, 'version' => 'auto')) . '" type="text/javascript"></script></body>', $body);
				$change = true;
			}
		}
	}

	protected function convertToWebp($imagePath, $webpPath) {
		// Detect alpha-transparency in PNG-images and skip it
		if (preg_match('/\.png$/', $imagePath)) {
			if (is_file($imagePath) == false) {
				return false;
			}

			$imageContents = file_get_contents($imagePath);
			$colorType = ord(file_get_contents($imagePath, false, null, 25, 1));

			if ($colorType == 6 || $colorType == 4) {
				return false;
			} elseif (stripos($imageContents, 'PLTE') !== false && stripos($imageContents, 'tRNS') !== false) {
				return false;
			}
		}

		if (preg_match('/\.png$/', $imagePath) && function_exists('imagecreatefrompng')) {
			$image = @imagecreatefrompng($imagePath);
		} elseif (preg_match('/\.(jpg|jpeg)$/', $imagePath) && function_exists('imagecreatefromjpeg')) {
			$image = @imagecreatefromjpeg($imagePath);
		} else {
			return false;
		}
		
		if (!imageistruecolor($image)) {
			imagepalettetotruecolor($image);
		}
		
		if ($image) {
			return imagewebp($image, $webpPath);
		}

		return false;
	}

	protected function skipImagePath($imagePath) {
		// Detect excluded image paths and skip it
		$excludes = $this->getWebpExclusions();

		if (!empty($excludes)) {
			foreach ($excludes as $exclude) {
				if (stristr($imagePath, $exclude)) {
					return true;
				}
			}
		}

		return false;
	}

	protected function getWebpExclusions() {
		static $exclusions;

		if (!is_array($exclusions)) {
			$exclusions = $this->params->get('exclusions');
			$exclusions = $exclusions ? str_replace(array("\r\n", "\r"), "\n", $exclusions) : '';
			$exclusions = trim($exclusions);

			if (!empty($exclusions)) {
				$excludeValues = explode("\n", $exclusions);
				$exclusions = array();

				foreach ($excludeValues as $exclude) {
					$exclude = trim($exclude);

					if (empty($exclude)) {
						continue;
					}

					$exclusions[] = $exclude;
				}
			} else {
				$exclusions = array();
			}
		}

		return $exclusions;
	}
	
	/**
	 *	Method to upload the .json Google Services Account Key file
	 */
	public function onExtensionAfterSave($context, $table) {
		if (!$this->canRun()) return false; 
		
		if ($context == 'com_config.component') {
			$app		= Factory::getApplication();
			$component	= ComponentHelper::getComponent('com_rsseo');
			$secret		= Factory::getConfig()->get('secret');
			
			if ($component->id == $table->extension_id) {
				$files = $app->input->files->get('jform');
				$private = $files['key'];
				
				if ($private['error'] == 0 && $private['size'] > 0) {
					$extension = File::getExt($private['name']);
					
					if (strtolower($extension) == 'json') {
						if (File::upload($private['tmp_name'], JPATH_ADMINISTRATOR.'/components/com_rsseo/assets/keys/'.md5($secret.'private_key').'.json')) {
							$app->enqueueMessage(Text::_('COM_RSSEO_GSA_KEY_UPLOADED'));
						} else {
							$app->enqueueMessage(Text::_('COM_RSSEO_GSA_KEY_UPLOADED_ERROR'), 'error');
						}
					} else {
						$app->enqueueMessage(Text::_('COM_RSSEO_GSA_KEY_WRONG_EXTENSION'), 'error');
					}
				}
			}
		}
	}
	
	/**
	 *	Method to get the current URL
	 */
	protected function getURL() {
		return $this->url;
	}
	
	/**
	 *	Method to add a page to database trough the auto-crawler
	 */
	protected function auto() {
		$db		= Factory::getDbo();
		$doc	= Factory::getDocument();
		$app	= Factory::getApplication();
		$query	= $db->getQuery(true);
		$sef	= Factory::getConfig()->get('sef');
		
		if (!$this->canRun() || !$app->isClient('site')) {
			return false;
		}
		
		$config	= rsseoHelper::getConfig();
		
		if ($config->crawler_enable_auto) {
			$ignored = $config->crawler_ignore;
			$ignored = str_replace("\r",'',$ignored);
			$ignored = explode("\n",$ignored);
			$allowed = $config->sitemap_autocrawled_rule;
			$allowed = str_replace("\r",'',$allowed);
			$allowed = explode("\n",$allowed);
			
			// Get current URL
			$url = $this->getURL();
			$url = $this->clean_url($url);
			if (!$url) return;
			
			$url	= str_replace(array(Uri::root(),'&amp;'), array('','&'), $url);
			$url	= str_replace('&', '&amp;', $url);
			$sefURL = Factory::getConfig()->get('sef_suffix') ? str_replace('.html','',$url) : $url;
			
			// Check for the .htaccess file
			if (!file_exists(JPATH_SITE.'/.htaccess') && $sef) {
				$sefURL = str_replace('index.php/', '', $sefURL);
			}
			
			if (Factory::getApplication()->getLanguageFilter()) {
				$parts 		= explode('/',$sefURL);
				$lang_codes = LanguageHelper::getLanguages('lang_code');
				$current	= Factory::getLanguage()->getTag();
				$lang_sef 	= isset($lang_codes[$current]->sef) ? $lang_codes[$current]->sef : '';
				
				if ($parts[0] == $lang_sef) {
					array_shift($parts);
				}
				
				$sefURL = implode('/', $parts);
			}
			
			$query->clear()
				->select($db->qn('id'))
				->from($db->qn('#__rsseo_pages'))
				->where('('.$db->qn('url').' = '.$db->q($url).' OR '.$db->qn('sef').' = '.$db->q($sefURL).')');
			$db->setQuery($query);
			$pageID = $db->loadResult();
			
			if (empty($pageID) && !$this->ignore($url,$ignored)) {
				$query->clear()
					->insert($db->qn('#__rsseo_pages'))->set($db->qn('url').' = '.$db->q($url))->set($db->qn('hash').' = '.$db->q(md5($url)))->set($db->qn('title').' = '.$db->q($doc->getTitle()))
					->set($db->qn('keywords').' = '.$db->q($doc->getMetaData('keywords')))->set($db->qn('description').' = '.$db->q($doc->getDescription()))
					->set($db->qn('sitemap').' = 0')->set($db->qn('crawled').' = 0')->set($db->qn('level').' = 127')
					->set($db->qn('date').' = '.$db->q(Factory::getDate()->toSql()))
					->set($db->qn('keywordsdensity').' = '.$db->q(''))->set($db->qn('params').' = '.$db->q(''))
					->set($db->qn('densityparams').' = '.$db->q(''))->set($db->qn('imagesnoalt').' = '.$db->q(''))
					->set($db->qn('imagesnowh').' = '.$db->q(''))->set($db->qn('customhead').' = '.$db->q(''))
					->set($db->qn('scripts').' = '.$db->q(''))->set($db->qn('css').' = '.$db->q(''))
					->set($db->qn('hits').' = 1');
				
				if ($config->sitemap_autocrawled == 1) {
					$query->set($db->qn('insitemap').' = '.$db->q(1));
				} elseif ($config->sitemap_autocrawled == 2) {
					$query->set($db->qn('insitemap').' = '.$db->q(0));
				} else {
					if ($this->ignore($url, $allowed)) {
						$query->set($db->qn('insitemap').' = '.$db->q(1));
					}
				}
				
				$db->setQuery($query);
				$db->execute();
			}
		}
	}
	
	/**
	 *	Method to set metadata
	 */
	protected function meta() {
		$db		= Factory::getDbo();
		$doc	= Factory::getDocument();
		$app	= Factory::getApplication();
		$query	= $db->getQuery(true);
		$sef	= Factory::getConfig()->get('sef');
		
		if (!$this->canRun() || !$app->isClient('site') || $doc->getType() != 'html') {
			return false;
		}

		if ($app->input->getInt('rsseoOriginal',0) == 1) {
			return;
		}
		
		$config	= rsseoHelper::getConfig();
		
		// Get current URL
		$url = $this->getURL();
		$url = str_replace(array(Uri::root(),'&amp;','&apos;'), array('','&',"'"), $url);
		$url = str_replace(array('&',"'"), array('&amp;','&apos;'), $url);
		$sefURL = Factory::getConfig()->get('sef_suffix') ? str_replace('.html','',$url) : $url;
		
		// Check for the .htaccess file
		if (!file_exists(JPATH_SITE.'/.htaccess') && $sef) {
			$sefURL = str_replace('index.php/', '', $sefURL);
		}
		
		if (Factory::getApplication()->getLanguageFilter()) {
			$parts 		= explode('/',$sefURL);
			$lang_codes = LanguageHelper::getLanguages('lang_code');
			$current	= Factory::getLanguage()->getTag();
			$lang_sef 	= isset($lang_codes[$current]->sef) ? $lang_codes[$current]->sef : '';
			
			if ($parts[0] == $lang_sef) {
				array_shift($parts);
			}
			
			$sefURL = implode('/', $parts);
		}
		
		// Get page
		$query->clear()
			->select($db->qn('id'))->select($db->qn('title'))->select($db->qn('description'))
			->select($db->qn('keywords'))->select($db->qn('level'))->select($db->qn('crawled'))
			->select($db->qn('modified'))->select($db->qn('canonical'))->select($db->qn('customhead'))
			->select($db->qn('robots'))->select($db->qn('custom'))
			->from($db->qn('#__rsseo_pages'))
			->where($db->qn('published').' = 1');
		
		if (empty($sefURL)) {
			$query->where($db->qn('url').' = '.$db->q($url));
		} else {
			$query->where('('.$db->qn('url').' = '.$db->q($url).' OR '.$db->qn('sef').' = '.$db->q($sefURL).')');
		}
		
		$db->setQuery($query,0,1);
		$page = $db->loadObject();
		
		// Increment the hits counter
		if (!empty($page)) {
			$query->clear()
				->update($db->qn('#__rsseo_pages'))
				->set($db->qn('hits').' = '.$db->qn('hits').' + 1')
				->where($db->qn('id').' = '.$db->q($page->id));
			$db->setQuery($query);
			$db->execute();
		}
		
		// Set the new Title , MetaKeywords , and the Description
		if (!empty($page) && (($page->crawled == 1 || $page->level == 0) || $page->modified == 1 )) {
			if (!($page->level == 0 && $page->title == null)) {
				$page->title		= str_replace('&#039;', "'", $page->title);
				$page->keywords		= str_replace('&#039;', "'", $page->keywords);
				$page->description	= str_replace('&#039;', "'", $page->description);
				
				// Set page title
				$doc->setTitle($page->title);
				
				// Set canonical link
				$canonical = trim($page->canonical);
				if (!empty($canonical)) {
					$doc->addCustomTag('<link href="'.htmlentities($canonical, ENT_QUOTES, 'UTF-8').'" rel="canonical">');
				}
				
				// Set Meta Keywords
				$doc->setMetaData('keywords',$page->keywords);
				// Set Meta Description
				$doc->setDescription($page->description);
				// Set Robots
				if (!empty($page->robots)) 
					$this->addRobots($page->robots);
				// Set custom metadata
				if (!empty($page->custom))
					$this->addCustom($page->custom);
				
				// Set custom HEAD scripts
				if ($page->customhead)
					$doc->addCustomTag(trim($page->customhead));
			}
		}
	}
	
	/**
	 *	Method to add robots
	 */
	protected function addRobots($robots) {
		$doc = Factory::getDocument();
		
		try {
			$registry = new Registry;
			$registry->loadString($robots);
			$robots = $registry->toArray();
		} catch (Exception $e) {
			$robots = array();
		}
		
		if (!empty($robots)) {
			$therobots = array();
			
			foreach($robots as $robot => $value) {
				if ($robot == 'index' && $value == '1')
					$therobots[] = 'index'; 
				elseif ($robot == 'index' && $value == '0')
					$therobots[] = 'noindex';
				
				if ($robot == 'follow' && $value == '1')
					$therobots[] = 'follow'; 
				elseif ($robot == 'follow' && $value == '0')
					$therobots[] = 'nofollow';
				
				if ($robot == 'archive' && $value == '1')
					$therobots[] = 'archive'; 
				elseif ($robot == 'archive' && $value == '0')
					$therobots[] = 'noarchive';
				
				if ($robot == 'snippet' && $value == '1')
					$therobots[] = 'snippet'; 
				elseif ($robot == 'snippet' && $value == '0')
					$therobots[] = 'nosnippet';
			}
			
			if (!empty($therobots)) {
				$therobots = implode(',',$therobots);
				$doc->setMetaData('robots',$therobots);
			}
		}
	}
	
	/**
	 *	Method to add custom metadata
	 */
	protected function addCustom($custom) {
		$doc = Factory::getDocument();
		
		try {
			$registry = new Registry;
			$registry->loadString($custom);
			$custom = $registry->toArray();
		} catch (Exception $e) {
			$custom = array();
		}
		
		if (!empty($custom)) {
			foreach ($custom as $meta) {
				$type = !empty($meta['type']) ? $meta['type'] : 'name';
				
				if (!empty($meta['name'])) {
					$doc->addCustomTag('<meta '.$type.'="'.htmlentities($meta['name'],ENT_COMPAT,'UTF-8').'" content="'.htmlentities($meta['content'],ENT_COMPAT,'UTF-8').'" />');
				}
			}
		}
	}
	
	/**
	 *	Method to ignore a link from beeing added to the pages database
	 */
	protected function ignore($url, $pattern_array) {
		$return = false;
		if (is_array($pattern_array)) {
			foreach ($pattern_array as $pattern) {
				if (empty($pattern)) continue;				
				
				$pattern = str_replace('&', '&amp;', $pattern);
				$pattern = $this->_transform_string($pattern);
				preg_match_all($pattern, $url, $matches);
				
				if (count($matches[0]) > 0)
					$return = true;
			}
		}
		return $return;
	}

	/**
	 *	Method to create the ignore pattern
	 */
	protected function _transform_string($string) {
		$start	= substr($string, 0, 1) != '{' ? '^' : '';
		$string = preg_quote($string, '/');
		$string = str_replace(preg_quote('{*}', '/'), '(.*)', $string);
		
		$pattern = '#\\\{(\\\\\?){1,}\\\}#';
		preg_match_all($pattern, $string, $matches);
		if (count($matches[0]) > 0) {
			foreach ($matches[0] as $match) {
				$count = count(explode('\?', $match)) - 1;
				$string = str_replace($match, '(.){'.$count.'}', $string);
			}
		}
		
		return '#'.$start.$string.'$#';
	}
	
	/**
	 *	Method to add custom attributes to the keyword
	 */
	protected function _setOptions($text, $bold = 0, $underline = 0, $link = '', $attributes = '') {
		$pattern = '/^(https?|ftp):\/\/(?#)(([a-z0-9$_\.\+!\*\'\(\),;\?&=-]|%[0-9a-f]{2})+(?#)(:([a-z0-9$_\.\+!\*\'\(\),;\?&=-]|%[0-9a-f]{2})+)?(?#)@)?(?#)((([a-z0-9][a-z0-9-]*[a-z0-9]\.)*(?#)[a-z][a-z0-9-]*[a-z0-9](?#)|((\d|[1-9]\d|1\d{2}|2[0-4][0-9]|25[0-5])\.){3}(?#)(\d|[1-9]\d|1\d{2}|2[0-4][0-9]|25[0-5])(?#))(:\d+)?(?#))(((\/+([a-z0-9$_\.\+!\*\'\(\),;:@&=-]|%[0-9a-f]{2})*)*(?#)(\?([a-z0-9$_\.\+!\*\'\(\),;:@&=-]|%[0-9a-f]{2})*)(?#)?)?)?(?#)(#([a-z0-9$_\.\+!\*\'\(\),;:@&=-]|%[0-9a-f]{2})*)?(?#)$/i';
		
		if ($bold == 1) {
			$startB = '<strong>';
			$endB = '</strong>';
		} elseif ($bold == 2) {
			$startB = '<b>';
			$endB = '</b>';
		} elseif ($bold == 0) {
			$startB = '';
			$endB = '';
		}
		
		if ($underline == 1) {
			$startU = '<u>';
			$endU = '</u>';
		} else {
			$startU = '';
			$endU = '';
		}
		
		$valid_url = preg_match($pattern,$link);
		
		if ($valid_url) {
			return $startB.$startU.'<a href="'.$link.'" '.trim($attributes).'>'.$text.'</a>'.$endU.$endB;
		} else {
			return $startB.$startU.$text.$endU.$endB;
		}
	}
	
	/**
	 *	Method to replace keywords
	 */
	protected function replace($bodyText, $searchTerm, $replaceWith, $limit) {
		$app = Factory::getApplication();
		if (!$this->canRun() || !$app->isClient('site')) {
			return false;
		}

		$config			= rsseoHelper::getConfig();
		$original		= $replaceWith;
		$originalwith	= $replaceWith;
		$newText		= '';
		$i				= -1;
		$lcSearchTerm	= mb_strtolower($searchTerm);
		$lcBodyText		= mb_strtolower($bodyText);
		$chars			= $config->approved_chars."\n\r\t";
		$counter		= 0;
		
		while (strlen($bodyText) > 0) {				
			// Get index of search term
			$i = $this->_indexOf($lcBodyText, $lcSearchTerm, $i+1);
			if ($i < 0) {
				$newText .= $bodyText;
				$bodyText = '';
			} else {
				// Skip anything inside an HTML tag
				if (($this->_lastIndexOf($bodyText,">",$i) >= $this->_lastIndexOf($bodyText,"<",$i))) {
					// Skip anything inside a <script> or <style> block
					if (($this->_lastIndexOf($lcBodyText,"/script>",$i) >= $this->_lastIndexOf($lcBodyText,"<script",$i)) && ($this->_lastIndexOf($lcBodyText,"/style>",$i) >= $this->_lastIndexOf($lcBodyText,"<style",$i)) && ($this->_lastIndexOf($lcBodyText,"/button>",$i) >= $this->_lastIndexOf($lcBodyText,"<button",$i)) && ($this->_lastIndexOf($lcBodyText,"/textarea>",$i) >= $this->_lastIndexOf($lcBodyText,"<textarea",$i)) && ($this->_lastIndexOf($lcBodyText,"/select>",$i) >= $this->_lastIndexOf($lcBodyText,"<select",$i)) && ($this->_lastIndexOf($lcBodyText,"/a>",$i) >= $this->_lastIndexOf($lcBodyText,"<a ",$i)) && ($this->_lastIndexOf($lcBodyText,"/title>",$i) >= $this->_lastIndexOf($lcBodyText,"<title",$i)) && ($this->_lastIndexOf($lcBodyText,"/h1>",$i) >= $this->_lastIndexOf($lcBodyText,"<h1",$i)) && ($this->_lastIndexOf($lcBodyText,"/h2>",$i) >= $this->_lastIndexOf($lcBodyText,"<h2",$i)) && ($this->_lastIndexOf($lcBodyText,"/h3>",$i) >= $this->_lastIndexOf($lcBodyText,"<h3",$i)) && ($this->_lastIndexOf($lcBodyText,"/h4>",$i) >= $this->_lastIndexOf($lcBodyText,"<h4",$i)) && ($this->_lastIndexOf($lcBodyText,"/h5>",$i) >= $this->_lastIndexOf($lcBodyText,"<h5",$i)) )
					{
						
						$word		= substr($bodyText, $i - 1, strlen($searchTerm) + 2);
						$firstChar	= substr($word, 0, 1);
						$lastChar	= substr($word, -1);							
						
						if ((strpos($chars,$firstChar) !== FALSE) && (strpos($chars,$lastChar) !== FALSE)) {
							$exact_word = ltrim($word,$firstChar);
							$exact_word = rtrim($exact_word,$lastChar);
							
							if (mb_strtolower($exact_word) === $lcSearchTerm) {
								$pattern = '#href="(.*?)"#is';
								preg_match($pattern,$replaceWith,$matches);								
								if (!empty($matches) && !empty($matches[1]))
									$replaceWith = str_replace($matches[1], '{r_s_s_e_o_l_i_n_k}', $replaceWith);
								
								$replaceWith = str_replace(mb_strtolower($exact_word),$exact_word,mb_strtolower($replaceWith));					
								
								if (!empty($matches) && !empty($matches[1]))
									$replaceWith = str_replace('{r_s_s_e_o_l_i_n_k}', $matches[1], $replaceWith);
								
								//$replaceWith = $originalwith;
								if (empty($limit)) {
									$newText .= substr($bodyText, 0, $i) . $replaceWith;
								} else {
									if ($counter < $limit) {
										$newText .= substr($bodyText, 0, $i) . $replaceWith;
									} else {
										$newText .= substr($bodyText, 0, $i) . $exact_word;
									}
								}
								$bodyText = substr($bodyText, $i+strlen($searchTerm));
								$lcBodyText = mb_strtolower($bodyText);
								$i = -1;
								$counter++;
								$replaceWith = $original;
							}
						}
					}
				}
			}
		}
		return $newText;
	}
	
	/**
	 *	Helper method for replacing keywords
	 */
	protected function _indexOf($text, $search, $i) {
		$return = strpos($text, $search, $i);
		if ($return === false)
			$return = -1;
		
		return $return;
	}
	
	/**
	 *	Helper method for replacing keywords
	 */
	protected function _lastIndexOf($text, $search, $i) {
		$length = strlen($text);
		$i = ($i > 0)?($length - $i):abs($i);
		$pos = strpos(strrev($text), strrev($search), $i);
		return ($pos === false)? -1 : ( $length - $pos - strlen($search) );
	}
	
	/**
	 *	Method to clean the url
	 */
	protected function clean_url($url) {
		$internal_links[] = Uri::root();
		$internal_links[] = Uri::root(true);
		
		foreach($internal_links as $internal_link) {
			if (substr($url,0,strlen($internal_link)) == $internal_link) {
				$url = substr_replace($url, '', 0, strlen($internal_link));
			}
		}
		
		// If url still contains http:// it's an external link
		if (strpos($url,'http://') !== false || strpos($url,'https://') !== false || strpos($url,'ftp://') !== false) {
			return false;
		}
		
		//let's clear anything after #
		$url_exp = explode('#',$url);
		$url = $url_exp[0];
		
		$array_extensions = array('jpg','jpeg','gif','png','pdf','doc','xls','odt','mp3','wav','wmv','wma','evy','fif','spl','hta','acx','hqx','doc','dot','bin','class','dms','exe','lha','lzh','oda','axs','pdf','prf','p10','crl','ai','eps','ps','rtf','setpay','setreg','xla','xlc','xlm','xls','xlt','xlw','msg','sst','cat','stl','pot','pps','ppt','mpp','wcm','wdb','wks','wps','hlp','bcpio','cdf','z','tgz','cpio','csh','dcr','dir','dxr','dvi','gtar','gz','hdf','ins','isp','iii','js','latex','mdb','crd','clp','dll','m13','m14','mvb','wmf','mny','pub','scd','trm','wri','cdf','nc','pma','pmc','pml','pmr','pmw','p12','pfx','p7b','spc','p7r','p7c','p7m','p7s','sh','shar','sit','sv4cpio','sv4crc','tar','tcl','tex','texi','texinfo','roff','t','tr','man','me','ms','ustar','src','cer','crt','der','pko','zip','au','snd','mid','rmi','mp3','aif','aifc','aiff','m3u','ra','ram','wav','bmp','cod','gif','ief','jpe','jpeg','jpg','jfif','svg','tif','tiff','ras','cmx','ico','pnm','pbm','pgm','ppm','rgb','xbm','xpm','xwd','nws','css','323','stm','uls','bas','c','h','txt','rtx','sct','tsv','htt','htc','etx','vcf','mp2','mpa','mpe','mpeg','mpg','mpv2','mov','qt','lsf','lsx','asf','asr','asx','avi','movie','flr','vrml','wrl','wrz','xaf','xof','swf');
		
		for ($i = 0; $i < count($array_extensions); $i++) {
			if (strtolower(substr($url, strlen($url) - (strlen($array_extensions[$i]) + 1))) == '.'.$array_extensions[$i]) {
				return false;
			}
		}
		
		if (substr($url,0,1) == '/') 
			$url = substr($url,1);
		
		return $url;
	}
	
	/**
	 *	Method to handle custom error pages on Joomla! 4
	 */
	public static function onError($error) {
		self::handleError($error);
	}
	
	/**
	 *	Method to handle custom error pages
	 */
	public static function handleError($error) {
		$app		= Factory::getApplication();
		$document	= Factory::getDocument();
		
		if (!self::canRun()) {
			self::showStandardError($error);
			$app->close(0);
		}
		
		$enable		= rsseoHelper::getConfig('custom_errors',1);
		
		if (!$enable) {
			// Add the URL to our database
			rsseoHelper::saveUrl($error->getCode());
			
			self::showStandardError($error);
			$app->close(0);
		}
		
		// Backend errors
		if ($app->isClient('administrator')) {
			// Render the error page.
			self::showStandardError($error);
		} else {
			$errorObject = rsseoHelper::getError($error->getCode());
			
			// Add the URL to our database
			rsseoHelper::saveUrl($error->getCode());
			
			if (!empty($errorObject)) {
				if ($errorObject->type == 1) {
					if ($document) {
						$errorMessage = $errorObject->layout;
						
						// Do we have a custom error page?
						if ($errorMessage) {
							// Set the document type : HTML
							$document->setType('html');
							
							if ($itemid = (int) $errorObject->itemid) {
								$app->input->set('Itemid',$itemid);
							}
							
							// Set the title
							@ob_end_clean();
							$document->setTitle(Text::_('Error') . ': ' . $error->getCode() . ' ' . str_replace("\n", ' ', $error->getMessage()));
							
							// Set the status header
							$app->setHeader('status', $error->getCode() . ' ' . str_replace("\n", ' ', $error->getMessage()));
							
							// Get content of custom error page
							$contents = self::getContents($errorMessage, $error);
							
							// Set the base
							$document->setBase(Uri::base());
							
							// Set the document buffer
							$document->setBuffer($contents, 'component');
							
							// Get the template
							$template = $app->getTemplate(true);
							
							if (self::isJ4()) {
								$document->getWebAssetManager()->getRegistry()->addTemplateRegistryFile($template->template, $app->getClientId());
								$app->loadDocument($document);
							}
							
							// Set params for the template to load
							$params = array(
								'template' 	=> $template->template,
								'file'		=> 'index.php',
								'directory'	=> JPATH_THEMES,
								'params'    => $template->params
							);
							
							// Render the template
							$data = $document->render(false, $params);
							
							// Do not allow cache
							Factory::getApplication()->allowCache(false);
							
							// Output code
							Factory::getApplication()->setBody($data);
							
							if (!Factory::getApplication()->getDocument()) {
                                Factory::getApplication()->loadDocument();
                            }

							if (!self::isJ4()) {
								Factory::getApplication()->triggerEvent('onAfterRender');
							}
							
							echo Factory::getApplication()->toString();
						} else {
							// Render the error page.
							self::showStandardError($error);
						}
					} else {
						// Render the error page.
						self::showStandardError($error);
					}
				} elseif ($errorObject->type == 2) {
					if (!empty($errorObject->url)) {
						$app->redirect($errorObject->url);
					} else {
						self::showStandardError($error);
					}
				} else {
					self::showStandardError($error);
				}
			} else {
				self::showStandardError($error);
			}
		}
		
		$app->close(0);
	}
	
	/**
	 *	Method to get the contents of a custom error page
	 */
	protected static function getContents($errorMessage, $error) {
		$view = new HtmlView(array(
			'name' => 'error',
			'layout' => 'default',
			'base_path' => JPATH_SITE.'/components/com_rsseo'
		));
		
		$view->errorMessage = $errorMessage;
		$view->addTemplatePath(JPATH_THEMES . '/' . Factory::getApplication()->getTemplate() . '/html/com_rsseo/' . $view->getName());
		
		$backtrace = LayoutHelper::render('joomla.error.backtrace', array('backtrace' => $error->getTrace()));
		$contents  = $view->loadTemplate();
		$contents  = str_replace(array('{errorCode}','{errorMessage}','{backtrace}'),array($error->getCode(), str_replace("\n", ' ', $error->getMessage()), $backtrace),$contents);
		
		return $contents;
	}
	
	/**
	 *	Method to run the sitemap cron manual
	 */
	protected function sitemap() {
		if (!$this->canRun()) {
			return;
		}
		
		$now	= Factory::getDate()->toUnix();
		$config = rsseoHelper::getConfig();
		$token	= Factory::getApplication()->input->getString('rstoken','');
		$token	= trim($token);
		
		if ($config->enable_sitemap_cron) {
			// Manual triggering
			if (in_array($config->sitemap_cron_type, array(0,2))) {
				
				if ($config->sitemap_timestamp + 600 > $now)
					return;
				
				rsseoHelper::updateConfig('sitemap_timestamp',$now);
				rsseoHelper::cronSitemap();
			}
			
			// Automatic triggering
			if (in_array($config->sitemap_cron_type, array(1,2))) {
				if ($config->sitemap_cron_security == $token) {
					rsseoHelper::cronSitemap();
				}
			}
		}
	}
	
	protected function visit() {
		$db		 = Factory::getDbo();
		$doc	 = Factory::getDocument();
		$app	 = Factory::getApplication();
		$session = Factory::getSession();
		$server	 = $app->input->server;
		$query	 = $db->getQuery(true);
		$ipblock = false;
		
		if (!$this->canRun() || !$app->isClient('site') || $doc->getType() != 'html') {
			return;
		}
		
		$enabled = rsseoHelper::getConfig('track_visitors',1);
		
		if (!$enabled) {
			return;
		}
		
		// Get information
		$ip		= rsseoHelper::getIP(true);
		$uid	= Factory::getUser()->get('id');
		$now	= Factory::getDate();
		$agent	= $server->getString('HTTP_USER_AGENT','');
		$referer= $server->getString('HTTP_REFERER','');
		$crawl	= $server->getInt('HTTP_X_RSSEO_CRAWLER',0);
		$ips	= rsseoHelper::getConfig('visitors_ip');
		
		if ($ips) { 
			$ips = str_replace("\r", '', $ips);
			if ($ips = explode("\n", $ips)) {
				foreach ($ips as $ipb) {
					if (trim($ip) == trim($ipb)) {
						$ipblock = true;
					}
				}
			}
		}
		
		if (strpos($agent, 'RSSeo') !== false || $crawl || $ipblock) {
			return;
		}
		
		// Get current URL
		$url = $this->getURL();
		$url = str_replace(array(Uri::root(),'&amp;'), array('','&'), $url);
		$url = str_replace('&', '&amp;', $url);
		
		$query->select($db->qn('id'))
			->select($db->qn('date'))
			->from($db->qn('#__rsseo_visitors'))
			->where($db->qn('session_id').' = '.$db->q($session->getId()))
			->order($db->qn('date').' DESC');
		$db->setQuery($query);
		$visitor = $db->loadObject();
		
		$query->clear()
			->insert($db->qn('#__rsseo_visitors'))
			->set($db->qn('session_id').' = '.$db->q($session->getId()))
			->set($db->qn('date').' = '.$db->q($now->toSql()))
			->set($db->qn('ip').' = '.$db->q(rsseoHelper::obfuscateIP($ip)))
			->set($db->qn('user_id').' = '.$db->q($uid))
			->set($db->qn('agent').' = '.$db->q($agent))
			->set($db->qn('referer').' = '.$db->q($referer))
			->set($db->qn('page').' = '.$db->q($url));
		
		$db->setQuery($query);
		$db->execute();
		
		if ($visitor) {
			$d1 = Factory::getDate($visitor->date)->toUnix();
			$d2 = $now->toUnix();
			$diff = $d2 - $d1;
			
			if ($diff > 0) {
				$time = $this->secondsToTime($diff);
				
				$query->clear()
					->update($db->qn('#__rsseo_visitors'))
					->set($db->qn('time').' = '.$db->q($time))
					->where($db->qn('id').' = '.$db->q($visitor->id));
				$db->setQuery($query);
				$db->execute();
			}
		}
	}
	
	protected function secondsToTime($seconds) {
		// extract hours
		$hours = floor($seconds / (60 * 60));

		// extract minutes
		$divisor_for_minutes = $seconds % (60 * 60);
		$minutes = floor($divisor_for_minutes / 60);

		// extract the remaining seconds
		$divisor_for_seconds = $divisor_for_minutes % 60;
		$seconds = ceil($divisor_for_seconds);

		$hours = strlen($hours) < 2 ? '0'.$hours : $hours;
		$minutes = strlen($minutes) < 2 ? '0'.$minutes : $minutes;
		$seconds = strlen($seconds) < 2 ? '0'.$seconds : $seconds;

		return $hours.':'.$minutes.':'.$seconds;
	}
	
	protected function getPage() {
		$db		= Factory::getDbo();
		$query	= $db->getQuery(true);
		$doc	= Factory::getDocument();
		$select = array('url', 'title', 'keywords', 'description', 'robots', 'customhead', 'custom');
		$robots = array('index' => 1, 'follow' => 1, 'archive' => 'off', 'snippet' => 'off');
		$sef	= Factory::getConfig()->get('sef');
		$sefURL = Factory::getConfig()->get('sef_suffix') ? str_replace('.html','',$this->url) : $this->url;
		
		// Check for the .htaccess file
		if (!file_exists(JPATH_SITE.'/.htaccess') && $sef) {
			$sefURL = str_replace('index.php/', '', $sefURL);
		}
		
		if (Factory::getApplication()->getLanguageFilter()) {
			$parts 		= explode('/',$sefURL);
			$lang_codes = LanguageHelper::getLanguages('lang_code');
			$current	= Factory::getLanguage()->getTag();
			$lang_sef 	= isset($lang_codes[$current]->sef) ? $lang_codes[$current]->sef : '';
			
			if ($parts[0] == $lang_sef) {
				array_shift($parts);
			}
			
			$sefURL = implode('/', $parts);
		}
		
		$query->select($select)
			->from($db->qn('#__rsseo_pages'))
			->where($db->qn('published').' = '.$db->q(1));
			
		if (empty($sefURL)) {
			$query->where($db->qn('url').' = '.$db->q($this->url));
		} else {
			$query->where('('.$db->qn('url').' = '.$db->q($this->url).' OR '.$db->qn('sef').' = '.$db->q($sefURL).')');
		}
		
		$db->setQuery($query);
		if ($page = $db->loadObject()) {
			if (!empty($page->robots)) {
				try {
					$registry = new Registry;
					$registry->loadString($page->robots);
					$page->robots = $registry->toArray();
				} catch (Exception $e) {
					$page->robots = array();
				}
			} else {
				$page->robots = $robots;
			}
			
			if (!empty($page->custom)) {
				try {
					$registry = new Registry;
					$registry->loadString($page->custom);
					$page->custom = $registry->toArray();
				} catch (Exception $e) {
					$page->custom = array();
				}
			} else {
				$page->custom = array();
			}

			return $page;
		} else {
			$url = $this->getUrl();
			$url = str_replace(array(Uri::root(),'&amp;','&apos;'), array('','&',"'"), $url);
			$url = str_replace(array('&',"'"), array('&amp;','&apos;'), $url);
			
			return (object) array('url' => $url, 'title' => $doc->getTitle(), 'keywords' => $doc->getMetaData('keywords'), 'description' => $doc->getDescription(), 'robots' => $robots, 'customhead' => '', 'custom' => array());
		}
	}
	
	protected function redirect() {
		$app	= Factory::getApplication();
		$crawl	= $app->input->server->getInt('HTTP_X_RSSEO_CRAWLER',0) || $app->input->server->getString('HTTP_USER_AGENT') == 'RSSeo! Crawler';
		
		if (!Factory::getConfig()->get('sef') || $crawl || !$app->isClient('site')) {
			return;
		}
		
		if (!rsseoHelper::getConfig('enable_sef')) {
			return;
		}
		
		$db		= Factory::getDbo();
		$query	= $db->getQuery(true);
		$url	= $this->getURL();
		$url	= str_replace(array('www.',Uri::root(),'&amp;'), array('','','&'), $url);
		$url	= str_replace('&', '&amp;', $url);
		
		$query->clear()
			->select($db->qn('sef'))
			->from($db->qn('#__rsseo_pages'))
			->where($db->qn('url').' = '.$db->q($url))
			->where($db->qn('published').' = '.$db->q(1));
		$db->setQuery($query);
		if ($sefURL = $db->loadResult()) {
			$lang_codes = LanguageHelper::getLanguages('lang_code');
			$current	= Factory::getLanguage()->getTag();
			$lang_sef 	= Factory::getApplication()->getLanguageFilter() ? (isset($lang_codes[$current]->sef) ? $lang_codes[$current]->sef : '') : '';
			
			// Check for the .htaccess file
			if (!file_exists(JPATH_SITE.'/.htaccess')) {
				$sefURL = 'index.php/'.($lang_sef ? $lang_sef.'/' : '').$sefURL;
			} else {
				$sefURL = $lang_sef ? $lang_sef.'/'.$sefURL : $sefURL;
			}
			
			if (Factory::getConfig()->get('sef_suffix')) {
				$sefURL = $sefURL.'.html';
			}
			
			header("Location: ".Uri::root().$sefURL);
			$app->close();
		}
	}
	
	public function onrsseo_cache($vars) {
		$vars['data'] = $this->getCacheInfo();
	}
	
	protected function removeCSSJs(&$content, &$change) {
		if (!Factory::getApplication()->isClient('site') || Factory::getDocument()->getType() != 'html') {
			return false;
		}
		
		$db		= Factory::getDbo();
		$query	= $db->getQuery(true);
		$app	= Factory::getApplication();
		$sef	= Factory::getConfig()->get('sef');
		$remove	= array();
		
		// Get current URL
		$url = $this->getURL();
		$url = str_replace(array(Uri::root(),'&amp;','&apos;'), array('','&',"'"), $url);
		$url = str_replace(array('&',"'"), array('&amp;','&apos;'), $url);
		$sefURL = Factory::getConfig()->get('sef_suffix') ? str_replace('.html','',$url) : $url;
		
		// Check for the .htaccess file
		if (!file_exists(JPATH_SITE.'/.htaccess') && $sef) {
			$sefURL = str_replace('index.php/', '', $sefURL);
		}
		
		if (Factory::getApplication()->getLanguageFilter()) {
			$parts 		= explode('/',$sefURL);
			$lang_codes = LanguageHelper::getLanguages('lang_code');
			$current	= Factory::getLanguage()->getTag();
			$lang_sef 	= isset($lang_codes[$current]->sef) ? $lang_codes[$current]->sef : '';
			
			if ($parts[0] == $lang_sef) {
				array_shift($parts);
			}
			
			$sefURL = implode('/', $parts);
		}
		
		// Get page
		$query->clear()
			->select($db->qn('id'))->select($db->qn('css'))->select($db->qn('scripts'))
			->from($db->qn('#__rsseo_pages'))
			->where($db->qn('published').' = 1');
		
		if (empty($sefURL)) {
			$query->where($db->qn('url').' = '.$db->q($url));
		} else {
			$query->where('('.$db->qn('url').' = '.$db->q($url).' OR '.$db->qn('sef').' = '.$db->q($sefURL).')');
		}
		
		$db->setQuery($query,0,1);
		if ($page = $db->loadObject()) {
			if (!empty($page->css) || !empty($page->scripts)) {
				if (!empty($page->css)) {
					$page->css = str_replace("\r",'',$page->css);
					$page->css = explode("\n", $page->css);
					
					if ($page->css) {
						if (preg_match_all('#<link[^>]*href=["|\']([^>]*\.css)(\?[^>]*)?["|\'][^>]*/?>#isU', $content, $matches)) {
							if (isset($matches[1])) {
								foreach($matches[1] as $i => $match) {
									foreach ($page->css as $cssFile) {
										if (strpos($match, $cssFile) !== false) {
											$remove[] = $matches[0][$i];
										}
									}
								}
							}
						}
					}
				}
				
				if (!empty($page->scripts)) {
					$page->scripts = str_replace("\r",'',$page->scripts);
					$page->scripts = explode("\n", $page->scripts);
					
					if ($page->scripts) {
						if (preg_match_all('#<script[^>]*src=["|\']([^>]*\.js)(\?[^>]*)?["|\'][^>]*/?>.*</script>#isU', $content, $matches)) {
							if (isset($matches[1])) {
								foreach($matches[1] as $i => $match) {
									foreach ($page->scripts as $scriptFile) {
										if (strpos($match, $scriptFile) !== false) {
											$remove[] = $matches[0][$i];
										}
									}
								}
							}
						}
					}
				}
			}
		}
		
		if (!empty($remove)) {
			foreach ($remove as $file) {
				$content = str_replace($file, '', $content);
			}
		}
	}
	
	protected function optimize(&$content, &$change) {
		// Do not run this plugin for the administration area or when debug is enabled
		if (!Factory::getApplication()->isClient('site') || JDEBUG || Factory::getDocument()->getType() != 'html') {
			return false;
		}
		
		// Verify that the server supports gzip compression before we attempt to gzip encode the files
		if (!extension_loaded('zlib') || ini_get('zlib.output_compression')) {
			$this->params->set('gzip', 0);
		}
		
		require_once dirname(__FILE__).'/helpers/optimizer.php';
		
		$optimizer = new RSOptimizer($this->params);
		$optimizer->optimize($content);
		$change = true;
	}
	
	protected function getCacheInfo() {
		$size	= 0;
		$count	= 0;
		$path	= Factory::getConfig()->get('cache_path', JPATH_SITE . '/cache').'/plg_system_rsseo';
		
		if (is_dir($path)) {
			$files = new FilesystemIterator($path, FilesystemIterator::SKIP_DOTS);
			
			foreach ($files as $file) {
				$size += $file->getSize();
			}
			
			$count = iterator_count($files) - 1;
		}
		
		return (object) array('size' => HTMLHelper::_('number.bytes', $size), 'files' => $count);
	}
	
	protected function outputGzipedFile() {
		$gzipFile = Factory::getApplication()->input->get('rsogzip');
		
		if ($gzipFile) {
			$ext	= pathinfo($gzipFile, PATHINFO_EXTENSION);
			$file	= JPATH_SITE.'/cache/plg_system_rsseo/'.$gzipFile;
			
			if (file_exists($file)) {
				@ob_clean();
				$contents = file_get_contents($file);
				
				if ($ext == 'css') {
					header('Content-type: text/css');
				} elseif ($ext == 'js') {
					header('Content-type: application/javascript');
				}
				
				header('Content-Encoding: gzip');
				header('Accept-Ranges: bytes');
				header('Cache-Control: Public');
				header('Vary: Accept-Encoding');
				
				echo $contents;
			} else {
				echo Text::_('PLG_RSOPTIMIZER_ERROR_GZIP');
			}
			
			Factory::getApplication()->close();
		}
	}
	
	protected function escape($string) {
		return htmlentities($string, ENT_COMPAT, 'UTF-8');
	}

	protected function hasWebpSupport() {
		// Check for GD support
		if (function_exists('imagewebp')) {
			return true;
		}

		return false;
	}
	
	protected static function showStandardError($error) {
		\Joomla\CMS\Exception\ExceptionHandler::render($error);
		
		if (self::isJ4()) {
			echo Factory::getApplication()->toString();
		}
	}
}

class rsseoUri extends Uri {
	public static function getUrl($uri) {
		return version_compare(JVERSION, '3.0', '>=') ? $uri->uri : $uri->_uri;
	}
}