<?php
/**
* @package RSSeo!
* @copyright (C) 2019 www.rsjoomla.com
* @license GPL, http://www.gnu.org/licenses/gpl-2.0.html
*/

defined( '_JEXEC' ) or die( 'Restricted access' );

class RSOptimizerCSS extends RSOptimizer
{
	protected $queue = array();
	
	public function optimize(&$content) {
		// Minify the inline styles if the option is active
		if ($this->params->get('merge_inline_css', 0)) {
			// Grab inline styles
			$inline_styles = array();
			
			if (preg_match_all('#<style.*?>(.*?)<\/style>#is', $content, $matches)) {
				foreach ($matches[0] as $i => $fullmatch) {
					$inline_styles[$fullmatch] = $this->minifyContent($matches[1][$i]); 
				}
			}
			
			// If there are inline styles then put them before the head tag is closed
			if ($inline_styles) {
				// Strip all inline styles found
				$content = str_replace(array_keys($inline_styles), '', $content);
				
				// Add the new minified styles
				$content = str_replace('</title>', "</title>\r\n".'<style type="text/css">'.implode("\r\n", $inline_styles).'</style>'."\r\n", $content);
			}
		}
		
		// Minify the available CSS files if the option is enabled
		if ($this->params->get('minify_css', 0)) {
			// Grab all stylesheets
			$used_keys = array();
			
			if (preg_match_all('#(\n)?<link.*?>(\n)?#i', $content, $matches)) {
				foreach ($matches[0] as $i => $fullmatch) {
					if (preg_match('#href="(.*?)"#i', $fullmatch, $match)) {
						$url = html_entity_decode($match[1]);
						
						if ($this->isInternal($url)) {
							$media = 'all';
							
							if (preg_match('#media="(.*?)"#i', $fullmatch, $match)) {
								$media = strtolower($match[1]);
							}
							
							$rel = 'stylesheet';
							
							if (preg_match('#rel="(.*?)"#i', $fullmatch, $match)) {
								$rel = strtolower($match[1]);
							}
							
							$reference = $media;
							
							if ($rel == 'stylesheet' && !$this->isFileExcluded($url) && ($file = $this->getAbsolutePath($url))) {
								$delimiter = 0; // Used for checking the unique id at the end of the reference
								if (!$this->params->get('merge_all_css', 0)) {
									$type = 'other';
									
									if (preg_match('#templates|system|media|components|plugins|modules#i', $file, $match)) {
										$type = strtolower($match[0]);
									}
									
									$reference .= '|'.$type; 
									$delimiter = 1; // If type is present there will be 2 pipelines that seperate the media | type | unique id
								}
								
								$last_key = key(array_slice($this->queue, -1, 1, TRUE));
								$old_reference = $last_key;
								
								if ($last_key && (substr_count($last_key, '|')) > $delimiter) {
									$parts = explode('|', $last_key);
									array_pop($parts);
									$old_reference = implode('|', $parts);
								} 
								
								if ($old_reference != $reference && in_array($reference, $used_keys)) {
									$reference = $reference.'|'.rand(1,99);
								} else if ($old_reference == $reference) {
									$reference = $last_key;
								}
								
								$this->queue[$reference][$fullmatch] = $file;
								$used_keys[] = $reference;
							}
						}
					}
				}
			}
			
			// If there are files in the queue and the cache has been built successfully, go on with replacing the content
			if ($this->queue && $this->buildCache()) {
				// Strip all stylesheets
				foreach ($this->queue as $queue) {
					$content = str_replace(array_keys($queue), '', $content);
				}
				
				$minifiedCssFiles = array();
				foreach ($this->queue as $reference => $queue) {
					// Establish the media for this CSS file
					list($media) = explode('|', $reference, 2);
					
					if ($this->params->get('gzip', 0)) {
						$minifiedCssFiles[] = '<link type="text/css" rel="stylesheet" '.($media != 'all' ? 'media="'.$media.'" ' : '').'href="'.$this->root.'/index.php?rsogzip='.$this->getCachedFile($reference).'" />';
					} else {
						$minifiedCssFiles[] = '<link type="text/css" rel="stylesheet" '.($media != 'all' ? 'media="'.$media.'" ' : '').'href="'.$this->root.'/cache/plg_system_rsseo/'.$this->getCachedFile($reference).'" />';
					}
				}
				
				// Add our own stylesheets			
				$content = str_replace('</title>', "</title>\r\n".implode("\r\n", $minifiedCssFiles), $content);
			}
		}
	}
	
	// Method to read the contents of the file (for caching) and apply fixes.
	public function readFile($path) {
		$buffer = parent::readFile($path);
		
		// Must fix any "url()" declarations
		if (preg_match_all('#url\((.*?)\)#i', $buffer, $matches)) {
			$replacements = array();
			
			foreach ($matches[0] as $i => $fullmatch) {
				// Replace some characters
				$url = str_replace(array('"', "'"), '', $matches[1][$i]);
				
				@list($filename, $query) = explode('?', $url, 2);
				
				// Figure out our current path (the URL will be relative to the file's path)
				if ($dir = realpath(dirname($path).'/'.$filename)) {					
					// Replace the root - we don't need it
					$file = substr_replace($dir, '', 0, strlen(JPATH_SITE) + 1);
					
					// Replace directory separators
					$file = str_replace(DIRECTORY_SEPARATOR, '/', $file);
					
					// This is our computed, web accessible path to the file
					$file = $this->root.'/'.$file;
					
					// Add the missing query if we have one
					if ($query && strlen($query)) {
						$file .= '?'.$query;
					}
					
					// Add it to the replacement list
					$replacements[$url] = $file;
				}
			}
			
			if ($replacements) {
				$buffer = str_replace(array_keys($replacements), array_values($replacements), $buffer);
			}
		}
	
		// Find imported files if they exists
		if (preg_match_all('#@import\surl\((.*?)\)#i', $buffer, $matches) ){
			$replacements = array();
			
			foreach ($matches[1] as $i => $file) {
				$file = str_replace(array('"', "'"), '', $file);
				if ($file = $this->getAbsolutePath($file)) {
					$replacements[$matches[0][$i]] = $this->readFile($file);
				}
			}
			
			if ($replacements) {
				$buffer = implode("\r\n", $replacements).(str_replace(array_keys($replacements), '', $buffer));
			}
		}
		
		$path = str_replace(array(JPATH_SITE.DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR), array('','/'), $path);
		return "/* @original $path */\r\n".$this->minifyContent($buffer);
	}
	
	// We need to return a .css extension file
	public function getCachedFile($reference = array()) {
		return $this->getHash($reference).'.css';
	}
	
	// We need to minify the output of the file before we store it to the cached file
	public function minifyContent($content) {
		$content = str_replace("\r\n", "\n", $content);
		// Remove comments
        $content = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $content);
        // Remove tabs
        $content = str_replace("\t", '', $content);
         // Replace any ws involving newlines with a single newline
        $content = preg_replace('/[ \\t]*\\n+\\s*/', "\n", $content);
        // Remove ws around { } and last semicolon in declaration block
        $content = preg_replace('/\\s*{\\s*/', '{', $content);
        $content = preg_replace('/;?\\s*}\\s*/', '}', $content);
		// Remove ws surrounding semicolons
        $content = preg_replace('/\\s*;\\s*/', ';', $content);
		// Remove ws surrounding commas
        $content = preg_replace('/\\s*,\\s*/', ',', $content);
		
		// Remove ws around urls
        $content = preg_replace('/
                url\\(      # url(
                \\s*
                ([^\\)]+?)  # 1 = the URL (really just a bunch of non right parenthesis)
                \\s*
                \\)         # )
            /x', 'url($1)', $content);
			
		// Remove ws between rules and colons
        $content = preg_replace('/
                \\s*
                ([{;])              # 1 = beginning of block or rule separator 
                \\s*
                ([\\*_]?[\\w\\-]+)  # 2 = property (and maybe IE filter)
                \\s*
                :
                \\s*
                (\\b|[#\'"])        # 3 = first character of a value
            /x', '$1$2:$3', $content);
		
        return trim($content);
	}
}