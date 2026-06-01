<?php
/**
* @package RSSeo!
* @copyright (C) 2016 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\Registry\Registry;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Factory;

class sitemapHelper {
	
	protected $sitemap;
	protected $ror;
	protected $new;
	protected $modified_date;
	protected $modified;
	protected $replace = array();
	protected $root;
	protected $images;
	
	public function __construct($options) {
		// The sitemap.xml path
		$this->sitemap = JPATH_SITE.'/sitemap.xml';
		// The ror.xml path
		$this->ror = JPATH_SITE.'/ror.xml';
		// Do we create a new sitemap ?
		$this->new = isset($options['new']) ? $options['new'] : 0;
		// Set last modified type
		$this->modified_date = isset($options['modified_date']) ? $options['modified_date'] : 1;
		// Set last modified time
		$this->modified = isset($options['modified']) ? $options['modified'] : HTMLHelper::_('date', 'NOW', 'Y-m-d');
		// Include images?
		$this->images = isset($options['images']) ? $options['images'] : 0;
		// Set root
		$this->root = isset($options['website']) && !empty($options['website']) ? $options['website'] : $this->getRoot();
		
		if (substr($this->root,-1) != '/') {
			$this->root .= '/';
		}
		
		$this->update();
		
		$empty = '';
		if (file_exists($this->sitemap) && $this->new) {
			$this->write($this->sitemap,$empty,'w');
		}
		
		if (file_exists($this->ror) && $this->new) {
			$this->write($this->ror,$empty,'w');
		}
		
		// Reset file 
		$this->reset();
		
		$this->redirects();
	}
	
	public static function getInstance($options) {
		$modelClass = 'sitemapHelper';
		return new $modelClass($options);
	}
	
	/**
	 *	Add XML Headers
	 */
	public function setHeader($update = false) {
		if ($this->new || $update) {
			if (file_exists($this->sitemap)) {
				$header = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
				$header .= '<?xml-stylesheet type="text/xsl" href="'.Uri::root().'components/com_rsseo/sitemap.xsl"?>'."\n";
				$header .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"';
				
				if ($this->images) {
					$header .= ' xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"';
				}
				
				$header .= '>'."\n";
				
				$this->write($this->sitemap,$header,'a');
			}
			
			if (file_exists($this->ror)) {
				$header = '<?xml version="1.0" encoding="utf-8"?>'."\n";
				$header .= '<rss version="2.0" xmlns:ror="http://rorweb.com/0.1/">'."\n";
				$header .= '<channel>'."\n";
				$header .= "\t".'<title>ROR Sitemap for '.$this->root.'</title>'."\n";
				$header .= "\t".'<description>ROR Sitemap for '.$this->root.'</description>'."\n";
				$header .= "\t".'<link>'.$this->root.'</link>'."\n";
				$header .= "\t".'<item>'."\n";
				$header .= "\t\t".'<title>ROR Sitemap for '.$this->root.'</title>'."\n";
				$header .= "\t\t".'<link>'.$this->root.'</link>'."\n";
				$header .= "\t\t".'<ror:about>sitemap</ror:about>'."\n";
				$header .= "\t\t".'<ror:type>SiteMap</ror:type>'."\n";
				$header .= "\t".'</item>'."\n";
				
				$this->write($this->ror,$header,'a');
			}
		}
	}
	
	protected function update() {
		if ($this->new) {
			$db			= Factory::getDbo();
			$query		= $db->getQuery(true);
			$component	= ComponentHelper::getComponent('com_rsseo');
			$cparams	= $component->params;
			
			if ($cparams instanceof Registry) {
				$cparams->set('sitemapmodified_date', $this->modified_date);
				$cparams->set('sitemapmodified', $this->modified);
				$cparams->set('sitemapwebsite', $this->root);
				$cparams->set('sitemapimages', $this->images);
				$query->clear();
				$query->update($db->qn('#__extensions'));
				$query->set($db->qn('params'). ' = '.$db->q((string) $cparams));
				$query->where($db->qn('extension_id'). ' = '. $db->q($component->id));
				
				$db->setQuery($query);
				$db->execute();
			}
		}
	}
	
	public function add($page, $update = false) {
		if (file_exists($this->sitemap)) {
			$this->addSitemap($page, $update);
		}
		
		if (file_exists($this->ror)) {
			$this->addRor($page, $update);
		}
	}
	
	public function close() {
		if (file_exists($this->sitemap)) {
			$this->closeSitemap();
		}
		
		if (file_exists($this->ror)) {
			$this->closeRor();
		}
	}
	
	public function clear() {
		if (file_exists($this->sitemap)) {
			$fh = fopen($this->sitemap,'w');
			fclose($fh);
		}
		
		if (file_exists($this->ror)) {
			$fh = fopen($this->ror,'w');
			fclose($fh);
		}
	}
	
	protected function addSitemap($page, $update) {
		if (!empty($this->replace[$page->url])) {
			$page->url = $this->replace[$page->url];
		}
		
		if (strpos($page->url,$this->root) === false) {
			$href = $this->root.$page->url;
		} else {
			$href = $page->url;
		}
		
		$string = "\t".'<url>'."\n";
		$string .= "\t\t".'<loc>'.$this->xmlentities($href).'</loc>'."\n";
		$string .= "\t\t".'<priority>'.($page->priority ? number_format($page->priority,1) : '0.5').'</priority>'."\n";
		
		if ($page->frequency != 'none')
			$string .= "\t\t".'<changefreq>'.($page->frequency ? $page->frequency : 'weekly').'</changefreq>'."\n";
		
		$string .= "\t\t".'<lastmod>'.($this->modified_date ? $this->modified : HTMLHelper::_('date', $page->date, 'Y-m-d')).'</lastmod>'."\n";
		
		if ($this->images && $images = $this->getImages($page->id)) {
			foreach ($images as $image) {
				$string .= "\t\t".'<image:image>'."\n";
				$string .= "\t\t\t".'<image:loc>'.$this->xmlentities($image).'</image:loc>'."\n";
				$string .= "\t\t".'</image:image>'."\n";
			}
		}
		
		$string .= "\t".'</url>'."\n";
		
		$this->write($this->sitemap, $string, 'a', $update, 'sitemap');
	}
	
	protected function addRor($page, $update) {
		if (!empty($this->replace[$page->url])) {
			$page->url = $this->replace[$page->url];
		}
		
		if (strpos($page->url,$this->root) === false) {
			$href = $this->root.$page->url;
		} else {
			$href = $page->url;
		}
		
		$string = "\t".'<item>'."\n";
		$string .= "\t\t".'<link>'.$this->xmlentities($href).'</link>'."\n";
		$string .= "\t\t".'<title>'.$this->xmlentities($page->title).'</title>'."\n";
		
		if ($page->frequency != 'none')
			$string .= "\t\t".'<ror:updatePeriod>'.($page->frequency ? $page->frequency : 'weekly').'</ror:updatePeriod>'."\n";
		
		$string .= "\t\t".'<ror:sortOrder>'.$page->level.'</ror:sortOrder>'."\n";
		$string .= "\t\t".'<ror:resourceOf>sitemap</ror:resourceOf>'."\n";
		$string .= "\t".'</item>'."\n";
		
		$this->write($this->ror, $string, 'a', $update, 'ror');
	}
	
	protected function closeSitemap() {
		$string = '</urlset>';
		$this->write($this->sitemap, $string, 'a');
	}
	
	protected function closeRor() {
		$string = '</channel>'."\n";
		$string .= '</rss>';
		
		$this->write($this->ror, $string, 'a');
	}
	
	protected function redirects() {
		$db		= Factory::getDbo();
		$query	= $db->getQuery(true);
		
		$query->clear()
			->select($db->qn('from'))->select($db->qn('to'))
			->from($db->qn('#__rsseo_redirects'))
			->where($db->qn('published').' = '.$db->q(1));
			
		$db->setQuery($query);
		if ($redirects = $db->loadObjectList()) {
			foreach ($redirects as $redirect) {
				$redirect->from = htmlentities($redirect->from);
				$redirect->to = htmlentities($redirect->to);
				$this->replace[$redirect->from] = $redirect->to;
			}
		}
	}
	
	protected function reset() {
		if ($this->new) {
			$db		= Factory::getDbo();
			$query	= $db->getQuery(true);
			
			$query->update($db->qn('#__rsseo_pages'))->set($db->qn('sitemap').' = 0');
			$db->setQuery($query);
			$db->execute();
		}
	}
	
	protected function xmlentities($string) {
		$string = str_replace('&amp;','&',$string);
		$string = htmlspecialchars($string);
		
		return $string;
	}
	
	protected function write($filename, $string, $write_type, $update = false, $type = null) {
		$write_type = $update ? 'r+' : $write_type;
		
		if (!is_null($type) && $update) {
			if ($type == 'sitemap')
				$string = $string."</urlset>";
			elseif ($type == 'ror') {
				$string = $string."</channel>\n</rss>";
			}
		}
		
		if (is_writable($filename)) {
			if (!$handle = fopen($filename, $write_type)) {
				throw new Exception(Text::sprintf('COM_RSSEO_SITEMAP_CANNOT_OPEN_FILE', $filename));
			}
			if ($update) {
				if (!is_null($type)) {
					if ($type == 'sitemap') {
						fseek($handle, -9, SEEK_END);
					} elseif ($type == 'ror') {
						fseek($handle, -17, SEEK_END);
					}
				}
			}
			// Write $somecontent to our opened file.
			if (fwrite($handle, $string) === FALSE) {
				throw new Exception(Text::sprintf('COM_RSSEO_SITEMAP_CANNOT_OPEN_FILE', $filename));
			}
			fclose($handle);
		} else {
			throw new Exception(Text::sprintf('COM_RSSEO_SITEMAP_CANNOT_WRITE_FILE', $filename));
		}
	}
	
	protected function getImages($id) {
		$db	= Factory::getDbo();
		
		$query = $db->getQuery(true)->clear()
			->select($db->qn('url'))
			->from($db->qn('#__rsseo_page_images'))
			->where($db->qn('idp').' = '.$db->q($id));
		$db->setQuery($query);
		
		return $db->loadColumn();
	}
	
	protected function getRoot() {
		$uri	 = Uri::getInstance(Uri::base());
		$prefix  = $uri->toString(array('scheme', 'host', 'port'));
		$path	 = $uri->toString(array('path'));
		$path	 = strpos($path, 'administrator') !== false ? rtrim($path, '/\\') : $path;
		
		return str_replace('administrator', '', $prefix.$path);
	}
}