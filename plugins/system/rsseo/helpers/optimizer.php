<?php
/**
* @package RSSeo!
* @copyright (C) 2019 www.rsjoomla.com
* @license GPL, http://www.gnu.org/licenses/gpl-2.0.html
*/

defined( '_JEXEC' ) or die( 'Restricted access' );

use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Cache\Cache;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Factory;

class RSOptimizer
{
	// Web path
	protected $root;
	
	// Using a special delimiter for files
	protected $delimiter;
	
	// Path to the cache folder
	protected $cachePath;
	
	// Plug-in parameters
	protected $params;
	
	// Conditional statements
	protected $conditional_statements = array();
	
	public function __construct($params) {
		$this->root			= Uri::root(true);
		$this->delimiter 	= substr(PHP_OS, 0, 3) == 'WIN' ? ';' : ':';
		$this->cachePath	= JPATH_SITE.'/cache/plg_system_rsseo';
		$this->params	    = $params;
	}
	
	// Main optimization function
	public function optimize(&$content) {
		// Clean the cache if plugin options are changed
		$hash = md5($this->params->get('minify_js',0).$this->params->get('merge_all_js',0).$this->params->get('minify_css',0).$this->params->get('merge_all_css',0).$this->params->get('gzip',0).$this->params->get('try_catch',0));
		$key  = 'rsoptimizer';
		
		$options	= array('storage' => 'file', 'defaultgroup' => 'plg_system_rsseo', 'cachebase' => realpath(Factory::getConfig()->get('cache_path', JPATH_SITE.'/cache')));
		$cache		= Cache::getInstance('output', $options);
		$cache->setCaching(true);
		
		if ($cache->contains($key)) {
			$data = $cache->get($key);
			
			if (isset($data['hash']) && $data['hash'] != $hash) {
				Factory::getCache('plg_system_rsseo')->clean();
				
				$cache->store(array('hash' => $hash), $key);
			}
		} else {
			$cache->store(array('hash' => $hash), $key);
		}
		
		// Do not run this if the current URL is in the excluded list
		if ($this->isUrlExcluded() || $this->isEditorEnabled()) {
			return false;
		}
		
		// Get conditional statements
		$this->detectHTMLConditions($content);
	
		// Optimize CSS files
		if ($this->params->get('minify_css', 0) || $this->params->get('merge_inline_css', 0)) {
			require_once dirname(__FILE__).'/css.php';
			$css = new RSOptimizerCSS($this->params);
			$css->optimize($content);
		}
		
		// Optimize Javascript files
		if ($this->params->get('minify_js', 0) || $this->params->get('merge_inline_js', 0)) {
			require_once dirname(__FILE__).'/js.php';
			$js = new RSOptimizerJS($this->params);
			$js->optimize($content);
		}
		
		// Restore conditional statements
		$this->restoreHTMLConditions($content);
		
		// CDN optimization
		if ($this->params->get('enable_cdn', 0) && $this->params->get('cdn_url')) {
			require_once dirname(__FILE__).'/cdn.php';
			$cdn = new RSOptimizerCDN($this->params);
			$cdn->optimize($content);
		}
		
		// Clean the HEAD section
		if ($this->params->get('minify_css', 0) || $this->params->get('merge_inline_css', 0) || $this->params->get('minify_js', 0) || $this->params->get('merge_inline_js', 0)) {
			$this->clean($content);
		}
		
		// Minify the HTML
		if ($this->params->get('minify_html', 0)) {
			require_once dirname(__FILE__).'/html.php';
			$html = new RSOptimizerHTML($this->params);
			$html->optimize($content);
		}
		
		$content = trim($content);
	}
	
	// Check if the current URL is in the excluded URLs
	public function isUrlExcluded() {
		$exclusions = $this->getExcluded('urls');
		$currentUrl = Uri::getInstance()->toString();
		
		if (empty($exclusions)) {
			return false;
		}
		
		foreach ($exclusions as $URL) {
			if (!empty($URL)) {
				if ($this->isIgnored($currentUrl, $URL))
					return true;
			}
		}
		
		return false;
	}
	
	// Check if the given file is in the excluded files
	public function isFileExcluded($url) {
		static $exclusions;
		
		if (is_null($exclusions)) {
			$exclusions = $this->getExcluded('files');
		}
		
		if (empty($exclusions)) {
			return false;
		}
		
		foreach ($exclusions as $file) {
			if (!empty($file)) {
				if ($this->isIgnored($url, $file))
					return true;
			}
		}
		
		return false;
	}
	
	// Check if the current page loads the Editor
	protected function isEditorEnabled() {
		$editors = PluginHelper::getPlugin('editors');

		foreach($editors as $editor) {
			if (class_exists('plgEditor' . $editor->name, false)) {
				return true;
			}
		}

		return false;
	}
	
	// Get excluded files/URLs
	protected function getExcluded($type) {
		$exclusions = $this->params->get('exclude_'.$type, '');
		$exclusions = trim($exclusions);
		if (!empty($exclusions)) {
			$exclusions = str_replace("\r", '', $exclusions);
			$exclusions = explode("\n", $exclusions);
			return array_map('trim', $exclusions);
		}
		
		return array();
	}
	
	// Detect conditional statements in the HTML
	protected function detectHTMLConditions(&$content) {
		if (preg_match_all('#<!--[\[](.|\s)*?-->#i', $content, $matches)) {
			foreach ($matches[0] as $condition) {
				if (!in_array($condition,  $this->conditional_statements)) {
					$this->conditional_statements[] = $condition;
					$content = str_replace($condition, '', $content);
				}
			}
		}
	}
	
	// Restore the conditional statements to the HTML
	protected function restoreHTMLConditions(&$content) {
		if (!empty($this->conditional_statements)) {
			$content = str_replace('</head>', "\r\n".implode("\r\n", $this->conditional_statements)."\r\n".'</head>', $content);
		}
	}
	
	// Clean the HEAD of the given page
	protected function clean(&$content) {
		if (preg_match('#<head>([\s\S]*)<\/head>#is', $content, $match)) {
			$head = $match[1];
			$head = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $head);
			$head = preg_replace('/\t+/', '', $head);
			$head = str_replace(array("\r", "\n"), array('',"\n\t"), $head);
			$head = trim($head);
			
			$content = str_replace($match[1], "\n\t".$head."\n", $content);
		}
	}
	
	// Grab all the files from the queue and merge them
	protected function buildCache() {
		$result = false;
		
		foreach ($this->queue as $reference => $queue) {
			$cache  = $this->cachePath.'/'.$this->getCachedFile($reference);
			
			if (!file_exists($cache)) {
				if (!is_dir($this->cachePath)) {
					@mkdir($this->cachePath, 0755);
				}
				
				$content = '';
				foreach ($queue as $file) {
					$content .= $this->readFile($file)."\r\n";
				}
				
				// Gzip the content of the file
				if ($content && $this->params->get('gzip', 0)) {
					$content = gzencode($content, 9);
				}				
				
				$result = file_put_contents($cache, $content);
			} else {
				$result = true;
			}
			
			if (!$result) {
				break;
			}
		}
		
		return $result;
	}
	
	// Translate an URL to the server's absolute path
	protected function getAbsolutePath($url) {
		$uri  = Uri::getInstance($url);
		$path = $uri->getPath();
		
		if (strpos($path, '?') !== false) {
			list($path, $query) = explode('?', $path, 2);
		}
		
		if ($this->root) {
			$path = substr_replace($path, '', strpos($path, $this->root), strlen($this->root));
		}
		
		$path = str_replace('/', DIRECTORY_SEPARATOR, $path);
		$path = ltrim($path, DIRECTORY_SEPARATOR);
		$realpath = realpath(JPATH_SITE . DIRECTORY_SEPARATOR . $path);
		
		return is_file($realpath) && is_readable($realpath) ? $realpath : false;
	}
	
	// Create a MD5 hash cache identifier
	protected function getHash($reference) {
		static $hash = array();
		
		if (!isset($hash[$reference])) {
			$hash[$reference] = md5(implode($this->delimiter, array_keys($this->queue[$reference]))); 
		}
		
		return $hash[$reference];
	}
	
	// Read the contents of the given file
	public function readFile($path) {
		$data = file_get_contents($path);
		
		// Remove BOM encoding
		$data = str_replace(chr(0xEF).chr(0xBB).chr(0xBF), '', $data);
		
		return $data;
	}
	
	protected function isInternal($url) {
		$parsed = \Joomla\Uri\UriHelper::parse_url($url);
		
		$host = isset($parsed['host']) ? $parsed['host'] : false;
		$path = isset($parsed['path']) ? $parsed['path'] : false;
		
		$base = Uri::base();
		$parsed_base = \Joomla\Uri\UriHelper::parse_url($base);
		
		// If the provided $url has a host specified
		if ($host && $host == $parsed_base['host']) {
			return true;
		}
		
		// If the $url doesn't have a host specified
		if (!$host) {
			$base_path_lenght = (int) strlen($parsed_base['path']);
			if ($path && substr_compare($path, $parsed_base['path'], 0, $base_path_lenght) === 0) {
				return true;
			}
		}
		
		return false;
	}
	
	protected function isIgnored($url, $pattern) {
		return strpos($url, $pattern) !== FALSE;
	}
}