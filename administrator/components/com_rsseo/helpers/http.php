<?php
/**
* @package RSSeo!
* @copyright (C) 2016 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Factory;

class rsseoHttp {
	
	protected $url;
	protected $host;
	protected $proxy;
	protected $status;
	protected $useragent;
	protected $crawl = false;
	protected $test = false;
	protected $errors = array();
	protected $response = false;
	protected static $instances = array();
	
	public function __construct($options = array()) {
		// Do we use this for crawling ?
		if (array_key_exists('crawl',$options)) {
			$this->crawl = $options['crawl'];
		}
		
		// Set the URL
		if (array_key_exists('url',$options)) {
			$this->url = $options['url'];
		}
		
		// Test on ?
		if (array_key_exists('test',$options)) {
			$this->test = $options['test'];
		}
		
		// Get the proxy details
		if (array_key_exists('proxy',$options)) {
			$this->proxy = $options['proxy'];
		}
		
		// Set user-agent
		$this->useragent = $this->useragent();
		
		// Set host
		$this->setHost();
		
		// Connect 
		$this->connect();
	}
	
	/**
	 *	Get an instance of this class
	 */
	public static function getInstance($options) {
		$signature = md5($options['url']);
		
		if (!isset(self::$instances[$signature])) {
			self::$instances[$signature] = new rsseoHttp($options);
		}
		
		return self::$instances[$signature];
	}
	
	public function getResponse() {
		return $this->response;
	}
	
	public function getStatus() {
		return $this->status;
	}
	
	/**
	 *	Connect to the given URL address
	 */
	protected function connect() {
		if ($this->test) {
			$this->openCurl();
			$this->openFGC();
			$this->openFopen();
			$this->openFsokopen();
		} else {
			if (!$this->response) {
				$this->openCurl();
				
				if ($response = $this->response) {
					if (strpos($response,"\r\n\r\n") !== false) {
						list($headers,$content) = explode("\r\n\r\n", $response, 2);
						$this->response = $content;
					}
				}
			}
			
			if (!$this->response) {
				$this->openFGC();
			}
			
			if (!$this->response) {
				$this->openFopen();
			}
			
			if (!$this->response) {
				$this->openFsokopen();
			}
		}
	}
	
	/**
	 *	Get a list of user-agents
	 */
	protected function useragent() {
		$session = Factory::getSession();
		
		if ($this->crawl) {
			return 'RSSeo! Crawler';
		}
		
		if ($customAgent = $session->get('rsseo.custom.agent', null)) {
			$session->clear('rsseo.custom.agent');
			return $customAgent;
		}
		
		$useragents = array(
			'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/32.0.1700.107 Safari/537.36',
			'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_1) AppleWebKit/537.73.11 (KHTML, like Gecko) Version/7.0.1 Safari/537.73.11',
			'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/32.0.1700.107 Safari/537.36',
			'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:27.0) Gecko/20100101 Firefox/27.0',
			'Mozilla/5.0 (Windows NT 6.1; rv:13.0) Gecko/20100101 Firefox/13.0.1',
			'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:13.0) Gecko/20100101 Firefox/13.0.1',
			'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/534.57.2 (KHTML, like Gecko) Version/5.1.7 Safari/534.57.2',
			'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/534.57.2 (KHTML, like Gecko) Version/5.1.7 Safari/534.57.2',
			'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; WOW64; Trident/5.0)',
			'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0)'
		);
		
		$count = count($useragents) - 1;
		return $useragents[rand(0,$count)];
	}
	
	/**
	 *	Connect using cURL
	 */
	protected function openCurl($url = null, $max = 3) {
		if (extension_loaded('curl')) {
			$ch = curl_init();
			
			// The URL
			$url = !is_null($url) ? $url : $this->url;
			
			// Set options
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_HEADER, 1);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_USERAGENT, $this->useragent);
			curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($ch, CURLOPT_TIMEOUT, 5);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 90);

			if ($this->proxy) {
				if (!empty($this->proxy['proxy_server'])) curl_setopt($ch, CURLOPT_PROXY, $this->proxy['proxy_server']);
				if (!empty($this->proxy['proxy_port'])) curl_setopt($ch, CURLOPT_PROXYPORT, $this->proxy['proxy_port']);
				if (!empty($this->proxy['proxy_usrpsw'])) curl_setopt($ch, CURLOPT_PROXYUSERPWD, $this->proxy['proxy_usrpsw']);
			}
			
			// Grab data
			$this->response = $max > 0 ? @curl_exec($ch) : ' ';
			$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			
			// Set the status of the request
			if (!isset($this->status)) {
				$this->status = $httpcode;
			}
			
			if ($httpcode != 200) {
				$lines = explode("\n",$this->response);
				foreach($lines as $line) {
					if (strpos($line,'Location:') !== false) {
						$new = trim(str_replace('Location: ','',$line));
						if (strpos($new,$this->host) !== false) {
							$max--;
							$this->openCurl($new, $max);
						}
					}
				}
			}
			
			curl_close($ch);
			
			if (!$this->response) {
				$this->setError('cURL');
			}
		} else {
			$this->setError('cURL');
		}
	}
	
	/**
	 *	Connect using file_get_contents
	 */
	protected function openFGC($url = null) {
		if (function_exists('file_get_contents') && ini_get('allow_url_fopen')) {
			$url = !is_null($url) ? $url : $this->url;
			$url = str_replace('://localhost', '://127.0.0.1', $url);
			@ini_set('user_agent',$this->useragent);
			$this->response = @file_get_contents($url);
			
			if (isset($http_response_header) && isset($http_response_header[0])) {
				$status = $http_response_header[0];
				if (preg_match("/[0-9]{3}/", $status, $match)) {
					if (isset($match) && isset($match[0]) && !$this->status) {
						$this->status = (int) $match[0];
					}
				}
				
				if ($this->status != 200) {
					foreach ($http_response_header as $header) {
						if (strpos($header,'Location:') !== false) {
							if ($new = trim(str_replace('Location: ','',$header))) {
								$this->openFGC($new);
							}
						}
					}
				}
			}
			
			if (!$this->response) {
				$this->setError('file_get_contents');
			}
		} else {
			$this->setError('file_get_contents');
		}
	}
	
	/**
	 *	Connect using fopen
	 */
	protected function openFopen($url = null) {
		if (function_exists('fopen') && ini_get('allow_url_fopen')) {
			if (ini_get('default_socket_timeout') < 5) ini_set('default_socket_timeout', 5);
			@ini_set('user_agent',$this->useragent);
			
			$url		= !is_null($url) ? $url : $this->url;
			$url		= str_replace('://localhost', '://127.0.0.1', $url);
			$data		= '';
			$handle		= @fopen($url, 'r');
			
			if (isset($http_response_header) && isset($http_response_header[0])) {
				$status = $http_response_header[0];
				if (preg_match("/[0-9]{3}/", $status, $match)) {
					if (isset($match) && isset($match[0]) && !$this->status) {
						$this->status = (int) $match[0];
					}
				}
				
				if ($this->status != 200) {
					foreach ($http_response_header as $header) {
						if (strpos($header,'Location:') !== false) {
							if ($new = trim(str_replace('Location: ','',$header))) {
								$this->openFopen($new);
							}
						}
					}
				}
			}
			
			if ($handle) {
				@stream_set_blocking($handle, 1);
				@stream_set_timeout($handle, 5);
				
				while (!feof($handle))
					$data .= @fread($handle, 8192);
			
				// Clean up
				@fclose($handle);
			} else {
				$this->setError('fopen');
			}
			
			$this->response = $data;
			
			if (!$this->response) {
				$this->setError('fopen');
			}
			
		} else {
			$this->setError('fopen');
		}
	}
	
	/**
	 *	Connect using fsockopen
	 */
	protected function openFsokopen($url = null) {
		if (function_exists('fsockopen')) {
			
			$url		= !is_null($url) ? $url : $this->url;
			$url_info	= parse_url($url);
			
			if (isset($url_info['host']) && $url_info['host'] == 'localhost') {
				$url_info['host'] = '127.0.0.1';
			}
			
			if (!isset($url_info['scheme'])) {
				$url_info['scheme'] = 'http';
			}
			
			switch ($url_info['scheme']) {
				case 'https':
					$scheme = 'ssl://';
					$port = 443;
					break;
				case 'http':
				default:
					$scheme = '';
					$port = 80;   
			}
			
			if ($fsock = @fsockopen($scheme.$url_info['host'], $port, $errno, $errstr, 5)) {
				@fputs($fsock, 'GET '.$url_info['path'].(!empty($url_info['query']) ? '?'.$url_info['query'] : '').' HTTP/1.1'."\r\n");
				@fputs($fsock, 'HOST: '.$url_info['host']."\r\n");
				@fputs($fsock, "User-Agent: ".$this->useragent."\r\n");
				@fputs($fsock, 'Connection: close'."\r\n\r\n");
				
				// Set timeout
				@stream_set_blocking($fsock, 1);
				@stream_set_timeout($fsock, 5);
				
				$status = @fgets($fsock);
				if (preg_match("/[0-9]{3}/", $status, $match)) {
					if (isset($match) && isset($match[0]) && !$this->status) {
						$this->status = (int) $match[0];
					}
				}
				
				if (substr_count($status, '200 OK') > 0) {
					$data = '';
					$passed_header = false;
					while (!@feof($fsock)) {				
						if ($passed_header) {
							$data .= @fread($fsock, 1024);
						} else {
							if (@fgets($fsock,1024) == "\r\n") {
								$passed_header = true;
							}
						}
					}
					
					$this->response = $data;
				} else {
					$headers = '';
					while (!feof($fsock)) {
						$headers .= fgets($fsock, 28);
					}
					
					if (!empty($headers)) {
						$headers = str_replace("\r",'',$headers);
						$headers = trim($headers);
						$headers = explode("\n",$headers);
						
						foreach ($headers as $header) {
							if (strpos($header,'Location:') !== false) {
								if ($new = trim(str_replace('Location: ','',$header))) {
									$this->openFsokopen($new);
								}
							}
						}
					}
				}
				
				if (!$this->response) {
					$this->setError('fsockopen');
				}
			} else {
				$this->setError('fsockopen');
			}
			
		} else {
			$this->setError('fsockopen');
		}
	}
	
	/**
	 *	Set error
	 */
	protected function setError($error) {
		if (!isset($this->errors[$error])) {
			$this->errors[] = $error;
		}
	}
	
	/**
	 *	Get errors
	 */
	public function getErrors() {
		return $this->errors;
	}
	
	/**
	 *	Set the host
	 */
	protected function setHost() {
		$uri		= Uri::getInstance();
		$host		= $uri->getHost();
		$this->host	= str_replace('www.','',$host);
	}
}