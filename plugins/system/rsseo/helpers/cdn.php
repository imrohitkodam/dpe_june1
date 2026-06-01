<?php
/**
* @package RSSeo!
* @copyright (C) 2019 www.rsjoomla.com
* @license GPL, http://www.gnu.org/licenses/gpl-2.0.html
*/

defined( '_JEXEC' ) or die( 'Restricted access' );

use Joomla\CMS\Uri\Uri;

class RSOptimizerCDN extends RSOptimizer
{	
	public function optimize(&$content) {
		$filetypes  = $this->params->get('cdn_filetypes');
		$filetypes	= $filetypes ? implode('|',$filetypes) : false;
		$attribs    = 'href\s*=\s*|src\s*=\s*|@import|name\s*=\s*(?:["\']movie["\']|movie) value\s*=\s*';
		$cdnurl		= $this->params->get('cdn_url');
		$cdnurl		= trim($cdnurl, '/');
		$root		= '/?';
		$searches	= array();
		
		// Domain url or root path
		$url = preg_quote(Uri::root(), '#');
		
		// Both variants of domain url - http or https
		$url = str_replace('https\:', 'https?\:', $url);
		
		$url = '(?:'.$url.'|'.preg_quote(Uri::root(true).'/', '#').')'.$root.'([^\)"\']+\.(?:'.$filetypes.')(?:\?[^\)"\']*)?)';
				
		$searches[] = '#((?:'.$attribs.')\s*(["\']))'.$url.'(\2)#i'; // attrib="..."
		$searches[] = '#(url\(((?:["\'])?))'.$url.'(\2\))#i'; // attrib(...)
		
		// Relative path
		$url = $root.'([a-z0-9-_]+/[^\?\)"\']+\.(?:'.$filetypes.')(?:\?[^\)"\']*)?)';
		$searches[] = '#((?:'.$attribs.')\s*(["\']))'.$url.'(\2)#i';
		$searches[] = '#((?:'.$attribs.'))()'.$url.'([\s|>])#i';
		$searches[] = '#(url\(((?:["\'])?))'.$url.'(\2\))#i';
		
		// Relative path - files in root
		$url = $root.'([a-z0-9-_]+[^\?\/)"\']+\.(?:'.$filetypes.')(?:\?[^\)"\']*)?)';
		$searches[] = '#((?:'.$attribs.')\s*(["\']))'.$url.'(\2)#i';
		$searches[] = '#((?:'.$attribs.'))()'.$url.'([\s|>])#i';
		$searches[] = '#(url\(((?:["\'])?))'.$url.'(\2\))#i';
		
		$jpath = Uri::root(true);
		$jpath = trim($jpath,'/');
		
		$replacements = array();
		foreach ($searches as $search) {
			if (preg_match_all($search, $content, $matches, PREG_SET_ORDER) > 0) {
				foreach ($matches as $match) {
					if (!empty($match[3])) {
						if (!empty($jpath) && substr(trim($match[3],'/'), 0, strlen($jpath)) != $jpath) {
							$match[3] = $jpath.'/'.$match[3];
						}
						
						$replacements[$match[0]] = $match[1].$cdnurl.'/'.$match[3].$match[4];
					}
				}
			}
		}
		
		if ($replacements) {
			$content = str_replace(array_keys($replacements), array_values($replacements), $content);
		}
	}
}