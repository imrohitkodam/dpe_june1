<?php
/**
* @package RSSeo!
* @copyright (C) 2016 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\Registry\Registry;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Factory;

class ajaxCrawlerHelper {
	protected $id;
	protected $initialize;
	protected $original;
	
	public function __construct($initialize, $id, $original = 0) {
		// Initialize crawler
		$this->initialize = (int) $initialize;
		// Set page ID
		$this->id = (int) $id;
		// Set original
		$this->original = (int) $original;
	}
	
	public static function getInstance($initialize, $id, $original = 0) {
		$modelClass = 'ajaxCrawlerHelper';
		return new $modelClass($initialize, $id, $original);
	}
	
	/**
	 *	Method to crawl a page
	 */
	public function crawl() {
		// Initialize crawler
		$this->initialize();
		
		$db		= Factory::getDbo();
		$query	= $db->getQuery(true);
		$config	= rsseoHelper::getConfig();
		
		if ($this->id) {
			// Save the current crawled page
			$page = $this->save($this->id);
			
			// Get next crawling page
			$query->select($db->qn('id'))
				->select($db->qn('url'))
				->from($db->qn('#__rsseo_pages'))
				->where($db->qn('crawled').' = 0')
				->where($db->qn('level').' != 127')
				->where($db->qn('published').' != -1')
				->order($db->qn('level').' ASC')
				->order($db->qn('id').' ASC');
			$db->setQuery($query);
			$next = $db->loadObject();
			
			// Count the number of pages crawled
			$query->clear()
				->select('COUNT(id)')
				->from($db->qn('#__rsseo_pages'))
				->where($db->qn('crawled').' != 0')
				->where($db->qn('level').' != 127')
				->where($db->qn('published').' != -1');
			
			$db->setQuery($query);
			$pages_crawled = $db->loadResult();
			
			// Count the number of pages left on this level..
			$query->clear()
				->select('COUNT(id)')
				->from($db->qn('#__rsseo_pages'))
				->where($db->qn('crawled').' = 0')
				->where($db->qn('level').' = '.$db->q($page->level))
				->where($db->qn('published').' != -1');
			
			$db->setQuery($query);
			$pages_left = $db->loadResult();
			
			// Count total pages crawled
			$query->clear()
				->select('COUNT(id)')
				->from($db->qn('#__rsseo_pages'))
				->where($db->qn('published').' != -1');
			
			$db->setQuery($query);
			$total_pages = $db->loadResult();
			
			$color = '';
			switch($page->grade) {
				case ($page->grade >= 0 && $page->grade < 33): 
					$color = 'red'; 
				break;
				
				case ($page->grade >= 33 && $page->grade < 66):
					$color = 'orange'; 
				break;
				
				case -1:
					$color = '';
				break;
				
				default: 
					$color = 'green'; 
				break;
			}
			
			$values = array('id' => $page->id, 'url' => $page->url, 'title' => $page->title, 'level' => $page->level,
				'grade' => ceil($page->grade), 'date' => HTMLHelper::_('date', $page->date, $config->global_dateformat), 'crawled' => $pages_crawled,
				'remaining' => $pages_left, 'total' => $total_pages, 'finished' => 0, 'color' => $color, 'status' => $page->status
			);
			
			$values['next']		= $next ? Uri::root().$next->url: '';
			$values['nextid']	= $next ? $next->id : 0;
			
		} else {
			$values = array('finished' => 1);
			
			// Turn on the auto crawler
			if (Factory::getApplication()->input->getInt('auto') == 1)
				$this->auto(1);
		}
		
		$values['finishtext'] = Text::_('COM_RSSEO_GLOBAL_FINISH',true);
		
		return json_encode($values);
	}
	
	/**
	 *	Method to save a page
	 */
	protected function save($id) {
		Table::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_rsseo/tables');
		
		$db		= Factory::getDbo();
		$query	= $db->getQuery(true);
		$root	= Uri::getInstance()->toString(array('scheme','host','port'));
		$page	= Table::getInstance('Page', 'rsseoTable');
		$config = rsseoHelper::getConfig();
		$input	= Factory::getApplication()->input;
		$data	= $input->get('data', array(), 'array');
		$urls	= $input->get('urls', array(), 'array');
		$sef	= Factory::getConfig()->get('sef');
		$suffix	= Factory::getConfig()->get('sef_suffix');
		
		// Keywords Density
		if (isset($data['densityparams'])) {
			if (is_array($data['densityparams'])) {
				foreach ($data['densityparams'] as $keyword => $percentage) {
					$data['densityparams'][$keyword] = number_format($percentage,2).' %';
				}				
			}
			
			$registry = new Registry;
			$registry->loadArray($data['densityparams']);
			$data['densityparams'] = $registry->toString();
		}
		
		// Images with no ALT attribute
		if (isset($data['imagesnoalt']) && is_array($data['imagesnoalt'])) {
			$registry = new Registry;
			$registry->loadArray($data['imagesnoalt']);
			$data['imagesnoalt'] = $registry->toString();
		} else {
			$data['imagesnoalt'] = '';
		}
		
		// Images with no WIDTH or HEIGHT attribute
		if (isset($data['imagesnowh']) && is_array($data['imagesnowh'])) {
			$registry = new Registry;
			$registry->loadArray($data['imagesnowh']);
			$data['imagesnowh'] = $registry->toString();
		} else {
			$data['imagesnowh'] = '';
		}
		
		// Load page details
		$page->load($id);
		$custom = $page->custom;
		$page->bind($data);
		$page->custom = $custom;
		
		// The page is not valid
		if ($page->published == -1) {
			$page->store();
			return $page;
		}
		
		// Add images to database
		$query->clear()
			->delete($db->qn('#__rsseo_page_images'))
			->where($db->qn('idp').' = '.$db->q($page->id));
		$db->setQuery($query);
		$db->execute();
		
		if (isset($data['images']) && is_array($data['images'])) {
			$data['images'] = array_unique($data['images']);
			
			foreach($data['images'] as $src) {
				if (empty($src)) continue;
				if (substr($src, 0, 10) == 'data:image') continue;
				
				$src = rsseoHelper::getUrl($src);
				
				$query->clear()
					->insert($db->qn('#__rsseo_page_images'))
					->set($db->qn('idp').' = '.$db->q($page->id))
					->set($db->qn('url').' = '.$db->q($src));
				$db->setQuery($query);
				$db->execute();
			}
		}
		
		$page->title		= html_entity_decode($page->title, ENT_COMPAT, 'UTF-8');
		$page->description	= html_entity_decode($page->description, ENT_COMPAT, 'UTF-8');
		$page->keywords		= html_entity_decode($page->keywords, ENT_COMPAT, 'UTF-8');
		
		$params = is_array($page->params) ? $page->params : array();
		
		$query->clear()
			->select('COUNT('.$db->qn('id').')')
			->from($db->qn('#__rsseo_pages'))
			->where($db->qn('title').' = '.$db->q($page->title))
			->where($db->qn('published').' = 1');
		$db->setQuery($query);
		$params['duplicate_title'] = (int) $db->loadResult();
		
		$query->clear()
			->select('COUNT('.$db->qn('id').')')
			->from($db->qn('#__rsseo_pages'))
			->where($db->qn('description').' = '.$db->q($page->description))
			->where($db->qn('published').' = 1');
		$db->setQuery($query);
		$params['duplicate_desc'] = (int) $db->loadResult();
		
		$registry = new Registry;
		$registry->loadArray($params);
		$page->params = $registry->toString();
		
		$url	= Uri::root().$page->url;
		$url	= str_replace(' ','%20',$url);
		$parent	= str_replace(Uri::root(),'',$url);
		
		// Set the page HTTP status
		$sefURL = rsseoHelper::getSEF($page->url);
		
		// Calculate the page grade
		$grade = $total = 0;
		
		if ($params['url_sef'] == 1 && $config->crawler_sef) $grade ++;
		if ($params['duplicate_title'] == 1 && $config->crawler_title_duplicate) $grade ++;
		if ($params['title_length'] >= 10 && $params['title_length'] <= 70 && $config->crawler_title_length) $grade ++;
		if ($params['duplicate_desc'] == 1 && $config->crawler_description_duplicate) $grade ++;
		if ($params['description_length'] >= 70 && $params['description_length'] <= 300 && $config->crawler_description_length) $grade ++;
		if ($params['keywords'] <= 10 && $config->crawler_keywords) $grade ++;
		if ($params['headings'] > 0 && $config->crawler_headings) $grade ++;
		if ($params['images'] <= 10 && $config->crawler_images) $grade ++;
		if ($params['images_wo_alt'] == 0 && $config->crawler_images_alt) $grade ++;
		if ($params['images_wo_hw'] == 0 && $config->crawler_images_hw) $grade ++;
		if ($params['links'] <= 100 && $config->crawler_intext_links) $grade ++;
		
		if ($config->crawler_sef) $total ++;
		if ($config->crawler_title_duplicate) $total ++;
		if ($config->crawler_title_length) $total ++;
		if ($config->crawler_description_duplicate) $total ++;
		if ($config->crawler_description_length) $total ++;
		if ($config->crawler_keywords) $total ++;
		if ($config->crawler_headings) $total ++;
		if ($config->crawler_images) $total ++;
		if ($config->crawler_images_alt) $total ++;
		if ($config->crawler_images_hw) $total ++;
		if ($config->crawler_intext_links) $total ++;
		
		$page->grade	= $total == 0 ? 0 : ($grade * 100 / $total);
		$page->crawled	= 1;
		$page->date		= Factory::getDate()->toSql();
		
		$ignored	= $config->crawler_ignore;
		$ignored	= str_replace("\r",'',$ignored);
		$ignored	= explode("\n", $ignored);
		$nofollow	= rsseoHelper::getConfig('crawler_nofollow',0);
		$internal	= 0;
		$external	= 0;
		
		// Get page URL's
		if ($urls) {
			foreach ($urls as $url) {
				$urlObj = json_decode($url);
				$href	= $urlObj->href;
				$rel	= $urlObj->rel;
				$URL	= str_replace($root, '', $href);
				$href 	= $this->clean_url($href);
				
				if (rsseoHelper::isInternal($URL)) {
					$internal++;
				} else {
					$external++;
				}
				
				// Skip URLs that have rel="nofollow" attribute
				if ($nofollow && !empty($rel) && strpos($rel,'nofollow') !== false) {
					continue;
				}
				
				if ($href) {
					// Skip unwanted links
					if (substr($href,0,7) == 'mailto:' || substr($href,0,11) == 'javascript:' || substr($href,0,6) == 'ymsgr:' || substr($href,0,1) == '#' || substr($href,0,4) == 'tel:' || substr($href,0,6) == 'skype:' || substr($href,0,9) == 'facetime:' || substr($href,0,13) == 'administrator' || substr($href,0,14) == '/administrator' || $href === 'http://' || $href === 'https://' || $page->level >= 127 || mb_strlen($href) > 333) {
						continue;
					}
					
					// Skip ignored links
					foreach($ignored as $ignore) {
						if(!empty($ignore)) {
							$ignore = str_replace('&', '&amp;', $ignore);
							if ($this->ignored($href, $ignore))
								continue 2;
						}
					}
					
					// Replace the root if any
					$href = str_replace(Uri::root(),'',$href);
					
					// Add URL to database
					if ($input->get('task','') == 'ajaxcrawl' || (in_array($input->get('task',''),array('apply','save','refresh')) && $config->crawler_save)) {
						if ($config->crawler_level == -1 || ($config->crawler_level != -1 && $page->level < $config->crawler_level)) {
							$custom_sef_url = $suffix ? str_replace('.html', '', $href) : $href;
							
							// Check for the .htaccess file
							if (!file_exists(JPATH_SITE.'/.htaccess') && $sef) {
								$custom_sef_url = str_replace('index.php/', '', $custom_sef_url);
							}
							
							if (PluginHelper::isEnabled('system','languagefilter')) {
								$custom_sef_url = trim($custom_sef_url, '/');
								
								$parts 		= explode('/',$custom_sef_url);
								$lang_codes = LanguageHelper::getContentLanguages();
								$codes	 	= array();
								
								foreach ($lang_codes as $code) {
									$codes[] = $code->sef;
								}
								
								if (count($parts) > 1 && in_array($parts[0], $codes)) {
									array_shift($parts);
								}
								
								$custom_sef_url = implode('/', $parts);
							}
							
							$query->clear()
								->select('COUNT(id)')
								->from($db->qn('#__rsseo_pages'))
								->where('('.$db->qn('url').' = '.$db->q($href).' OR '.$db->qn('sef').' = '.$db->q($custom_sef_url).')');
							$db->setQuery($query);
							
							if ($db->loadResult() == 0) {
								$query->clear()
									->insert($db->qn('#__rsseo_pages'))
									->set($db->qn('url').' = '.$db->q($href))
									->set($db->qn('hash').' = '.$db->q(md5($href)))
									->set($db->qn('parent').' = '.$db->q($parent))
									->set($db->qn('level').' = '.$db->q($page->level + 1))
									->set($db->qn('frequency').' = '.$db->q('weekly'))
									->set($db->qn('priority').' = '.$db->q('0.5'))
									->set($db->qn('insitemap').' = '.$db->q('1'))
									->set($db->qn('sitemap').' = '.$db->q('0'))
									->set($db->qn('crawled').' = '.$db->q('0'))
									->set($db->qn('title').' = '.$db->q(''))
									->set($db->qn('keywords').' = '.$db->q(''))
									->set($db->qn('keywordsdensity').' = '.$db->q(''))
									->set($db->qn('description').' = '.$db->q(''))
									->set($db->qn('params').' = '.$db->q(''))
									->set($db->qn('densityparams').' = '.$db->q(''))
									->set($db->qn('imagesnoalt').' = '.$db->q(''))
									->set($db->qn('imagesnowh').' = '.$db->q(''))
									->set($db->qn('custom').' = '.$db->q(''))
									->set($db->qn('customhead').' = '.$db->q(''))
									->set($db->qn('scripts').' = '.$db->q(''))
									->set($db->qn('css').' = '.$db->q(''))
									->set($db->qn('published').' = '.$db->q('1'))
									->set($db->qn('robots').' = '.$db->q('{"index":"1","follow":"1","archive":"off","snippet":"off"}'))
									->set($db->qn('date').' = '.$db->q(Factory::getDate()->toSql()));
								
								$db->setQuery($query);
								$db->execute();
							}
						}
					}
				}
			}
		}
		
		$page->internal = $internal;
		$page->external = $external;
		
		if ($page->level <= 127) {
			$page->store();
		}
		
		return $page;
	}
	
	/**
	 *	Method to get internal / external URLs
	 */
	public function links() {
		$db			= Factory::getDbo();
		$query		= $db->getQuery(true);
		$root		= Uri::getInstance()->toString(array('scheme','host','port'));
		$urls		= Factory::getApplication()->input->get('urls', array(), 'array');
		$data		= array();
		$internal	= 0;
		$external	= 0;
		
		if ($urls) {
			foreach ($urls as $href => $count) {
				// Skip unwanted links
				if (substr($href,0,7) == 'mailto:' || substr($href,0,11) == 'javascript:' || substr($href,0,6) == 'ymsgr:' || substr($href,0,1) == '#' || substr($href,0,4) == 'tel:' || substr($href,0,6) == 'skype:' || substr($href,0,9) == 'facetime:' || $href === 'http://' || $href === 'https://') {
					continue;
				}
				
				$URL		= str_replace($root, '', $href);
				$isInternal = rsseoHelper::isInternal($URL);
				
				if ($isInternal) {
					$internal++;
					
					if (strpos($href,$root) !== false) {
						$link = $href;
					} else {
						$link = substr($href,0,1) != '/' ? $root.'/'.$href : $root.$href;
					}
					
					$data['internal'][$link] = $count;
				} else {
					$external++;
					
					$data['external'][$href] = $count;
				}
			}
		}
		
		$query->clear()
			->update($db->qn('#__rsseo_pages'))
			->set($db->qn('internal').' = '.$db->q($internal))
			->set($db->qn('external').' = '.$db->q($external))
			->where($db->qn('id').' = '.$db->q($this->id));
		$db->setQuery($query);
		$db->execute();
		
		return json_encode($data);
	}
	
	/**
	 *	Method to check add broken links to the databse
	 */
	public static function broken() {
		$db		= Factory::getDbo();
		$query	= $db->getQuery(true);
		$input	= Factory::getApplication()->input;
		$id		= $input->getInt('id');
		$urls	= $input->get('urls', array(), 'array');
		
		$query->clear()
			->delete($db->qn('#__rsseo_broken_links'))
			->where($db->qn('pid').' = '.(int) $id);
		$db->setQuery($query);
		$db->execute();
		
		if ($urls) {
			foreach ($urls as $url => $code) {
				if ($url = rsseoHelper::getUrl($url)) {
					$query->clear()
						->insert($db->qn('#__rsseo_broken_links'))
						->set($db->qn('pid').' = '.(int) $id)
						->set($db->qn('url').' = '.$db->q($url))
						->set($db->qn('code').' = '.$db->q($code))
						->set($db->qn('published').' = 1');
					
					$db->setQuery($query);
					$db->execute();
				}
			}
		}
	}
	
	/**
	 *	Method to initialize the crawler
	 */
	protected function initialize() {
		$db		= Factory::getDbo();
		$query	= $db->getQuery(true);
		$config = rsseoHelper::getConfig();
		
		if ($this->initialize) {
			$query->clear();
			$query->update($db->qn('#__rsseo_pages'))->set($db->qn('crawled').' = 0')->where($db->qn('published').' != -1');
			$db->setQuery($query);
			$db->execute();
			
			// Turn off the auto crawler
			if ($config->crawler_enable_auto)
				$this->auto(0);
		}
		
		if ($this->original) {
			$query->clear();
			$query->update($db->qn('#__rsseo_pages'))->set($db->qn('crawled').' = 0')->set($db->qn('modified').' = 0')->where($db->qn('id').' = '.$db->q($this->id));
			$db->setQuery($query);
			$db->execute();
		}
	}
	
	/**
	 *	Method to check if a link is ignored
	 */
	protected function ignored($url, $pattern) {
		$pattern = $this->transform_string($pattern);	
		preg_match_all($pattern, $url, $matches);
		
		if (count($matches[0]) > 0) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 *	Method to transform a string
	 */
	protected function transform_string($string) {
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
		
		return '#'.$string.'$#';
	}
	
	/**
	 *	Method to clean URL
	 */
	protected static function clean_url($url) {
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
		
		// Check if the link is external
		$uri = Uri::getInstance($url);
		$base = $uri->toString(array('scheme', 'host', 'port', 'path'));
		$host = $uri->toString(array('scheme', 'host', 'port'));

		if (stripos($base, Uri::base()) !== 0 && !empty($host)) {
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
		
		$url = str_replace(array('&amp;','&apos;','&quot;','&gt;','&lt;'),array("&","'",'"',">","<"),$url);
		$url = str_replace(array("&","'",'"',">","<"),array('&amp;','&apos;','&quot;','&gt;','&lt;'),$url);
		$url = urldecode($url);
		
		return $url;
	}
	
	protected function auto($value) {
		if (!$this->id) {
			$db		= Factory::getDbo();
			$query	= $db->getQuery(true);
			$component	= ComponentHelper::getComponent('com_rsseo');
			$cparams	= $component->params;
			
			if ($cparams instanceof Registry) {
				$cparams->set('crawler_enable_auto', $value);
				$query->clear();
				$query->update($db->qn('#__extensions'));
				$query->set($db->qn('params'). ' = '.$db->q((string) $cparams));
				$query->where($db->qn('extension_id'). ' = '. $db->q($component->id));
				
				$db->setQuery($query);
				$db->execute();
			}
		}
		return true;
	}
}